<?php

use Webling\CacheAdapters\WordpressCacheAdapter;
use Webling\Cache\Cache;

/**
 * save form submissions and redirect
 *
 * @return void
 * @throws Exception
 */
function webling_form_submit(){
	global $wpdb;

	if(isset($_POST['webling-form-id'])) {
		try {
			$_POST = stripslashes_deep($_POST);
			$id = intval($_POST['webling-form-id']);

			if (isset($_POST['webling-form-field'][0]) && strlen($_POST['webling-form-field'][0]) > 0) {
				// Honeypot data was submitted
				throw new Exception('Sweet, you just discovered the honeypot');
			}

			$options = get_option('webling-options');

			// support for https://wordpress.org/plugins/advanced-nocaptcha-recaptcha/
			if (isset($options['anr_enabled']) && $options['anr_enabled'] === '1') {
				if (function_exists('anr_captcha_form_field') && function_exists('anr_verify_captcha')) {
					if (!anr_verify_captcha()) {
						throw new Exception('Could not verify captcha');
					}
				}
				if (function_exists('c4wp_captcha_form_field') && function_exists('c4wp_verify_captcha')) {
					if (!c4wp_verify_captcha()) {
						throw new Exception('Could not verify captcha');
					}
				}
			}

			// support for https://wordpress.org/plugins/friendly-captcha/
			if (isset($options['friendlycaptcha_enabled']) && $options['friendlycaptcha_enabled'] === '1') {
				if (class_exists('FriendlyCaptcha_Plugin') && function_exists('frcaptcha_get_sanitized_frcaptcha_solution_from_post') && function_exists('frcaptcha_verify_captcha_solution')) {
					$plugin = FriendlyCaptcha_Plugin::$instance;
					if ($plugin->is_configured()) {
						$errorPrefix = '<strong>' . __('Error', 'wp-captcha') . '</strong> : ';
						$solution = frcaptcha_get_sanitized_frcaptcha_solution_from_post();

						if (empty($solution)) {
							wp_die($errorPrefix . FriendlyCaptcha_Plugin::default_error_user_message() . __(" (captcha missing)", "frcaptcha"));
						}

						$verification = frcaptcha_verify_captcha_solution($solution, $plugin->get_sitekey(), $plugin->get_api_key());
						if (!$verification["success"]) {
							wp_die($errorPrefix . FriendlyCaptcha_Plugin::default_error_user_message());
						}
					}
				}
			}

			// Load form config
			$formconfig = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}webling_forms WHERE id = " . esc_sql($id), 'ARRAY_A');
			if (!$formconfig) {
				throw new Exception('No form with ID '.$id.' found');
			}

			// check if max signups is reached (not from cache, ask real api)
			if ($formconfig['max_signups'] > 0) {
				$client = WeblingApiHelper::Instance()->client();
				$response = $client->get('membergroup/' . $formconfig['group_id']);
				if ($response->getStatusCode() !== 200) {
					throw new Exception('Error fetching membergroup');
				}
				$membergroup = $response->getData();
				$childrenCount = 0;
				if (isset($membergroup['children']['member'])) {
					$childrenCount = count($membergroup['children']['member']);
				}
				if ($childrenCount >= $formconfig['max_signups']) {
					wp_redirect(add_query_arg('webling_form_error_max_signups', 'true', $_POST['webling-form-redirect']));
					exit;
				}
			}

			$sql = "SELECT * FROM {$wpdb->prefix}webling_form_fields WHERE form_id = " . esc_sql($formconfig['id']) . " ORDER BY `order` ASC";
			$formfields = $wpdb->get_results($sql, 'ARRAY_A');

			$memberfields = WeblingApiHelper::Instance()->getMemberFields();
			$definitions = WeblingApiHelper::Instance()->getMemberFieldDefinitionsById();
			$client = WeblingApiHelper::Instance()->client();
			$apiCache = new Cache($client, new WordpressCacheAdapter());

			$newdata = [];
			$emaildata = [];

			foreach ($formfields as $field) {
				$fieldId = $field['id'];
				$weblingFieldId = $field['webling_field_id'];
				if (isset($_POST['webling-form-field'][$fieldId]) || isset($_FILES['webling-form-field-' . $fieldId])) {
					if (isset($definitions[$weblingFieldId])) {
						$fieldDefinition = $definitions[$weblingFieldId];
						$fieldName = $memberfields[$weblingFieldId];

						if ($fieldDefinition['datatype'] == 'file' || $fieldDefinition['datatype'] == 'image') {
							$value = $_FILES['webling-form-field-' . $fieldId];
							// if size is 0, no fiel was uploaded
							if ($value['size'] == 0) {
								$value = null;
							}
						} else {
							$value = $_POST['webling-form-field'][$fieldId];
						}

						// abort if not all required fields are filled
						if ($field['required'] && empty($value)) {
							wp_redirect(add_query_arg('webling_form_error_required_fields', 'true', $_POST['webling-form-redirect']));
							exit;
						}

						switch ($fieldDefinition['datatype']){
							case 'text':
								$value = substr($value, 0, 255);
								break;
							case 'longtext':
								// leave as it is
								break;
							case 'bool':
								$value = true;
								break;
							case 'enum':
								if (!WeblingApiHelper::Instance()->isValidMemberFieldValue($fieldName, $value)) {
									$value = '';
								}
								break;
							case 'multienum':
								$options = [];
								foreach ($value as $opt => $isOn) {
									$option = base64_decode($opt);
									if (WeblingApiHelper::Instance()->isValidMemberFieldValue($fieldName, $option)) {
										$options[] = $option;
									}
								}
								$value = $options;
								break;
							case 'date':
								if ($value) {
									$dateparts = date_parse($value);
									if ($dateparts['year'] !== false && $dateparts['month'] !== false && $dateparts['day'] !== false ) {
										if ($dateparts['year'] < 100) {
											$dateparts['year'] += 2000;
										}
										$value = sprintf("%04d", $dateparts['year']).'-'.sprintf("%02d", $dateparts['month']).'-'.sprintf("%02d", $dateparts['day']);
									} else {
										$value = '';
									}
								}
								break;
							case 'int':
								$value = intval($value);
								break;
							case 'numeric':
								$value = floatval(number_format(floatval($value), 2, '.', ''));
								break;
							case 'file':
							case 'image':
								if ($value) {
									$file_data = base64_encode(file_get_contents($value['tmp_name']));
									$value = [
										'content' => $file_data,
										'name' => $value['name']
									];
								}
								break;
							case 'autoincrement':
							default:
								// autoincrement, images and files are currently not supported
								$value = '';
								break;
						}

						// do not send empty data to allow defaults
						if ($value) {
							$newdata[$fieldName] = $value;
						}

						// also send empty data via email
						if ($fieldDefinition['datatype'] === 'file' || $fieldDefinition['datatype'] === 'image') {
							if (is_array($value) && strlen($value['content']) > 0) {
								$estimatedFileSize = size_format(strlen($value['content']) * (3/4), 1);
								$emaildata[$fieldName] = __('Ja', 'webling') . ' (' . $estimatedFileSize .')';
							} else {
								$emaildata[$fieldName] = __('Nein', 'webling');
							}
						} else {
							$emaildata[$fieldName] = $value;
						}
					}
				} else {
					// abort if not all required fields are sent
					if ($field['required']) {
						wp_redirect(add_query_arg('webling_form_error_required_fields', 'true', $_POST['webling-form-redirect']));
						exit;
					}
				}
			}

			// create new member
			$newmember = [
				'properties' => $newdata,
				'parents' => [$formconfig['group_id']]
			];
			$response = $client->post('member', json_encode($newmember));
			if ($response->getStatusCode() != 201) {
				throw new Exception($response->getRawData(), $response->getStatusCode());
			}

			// send email notifications if the email addresses are valid
			if ($formconfig['notification_email']) {
				$to_emails = explode(',', $formconfig['notification_email']);
				foreach ($to_emails as $to) {
					$to = sanitize_email($to);
					if (is_email($to)) {
						$subject = __('Neue Anmeldung via Formular:', 'webling') . ' ' . $formconfig['title'];
						$body  = __("Guten Tag,\n\nDein WordPress Formular wurde mit folgenden Daten abgeschickt:", 'webling').PHP_EOL.PHP_EOL;

						foreach ($emaildata as $field => $val) {
							if (is_array($val)) {
								$body .= $field . ': ' . implode(', ', $val) . PHP_EOL;
							} else if(is_bool($val)) {
								$body .= $field . ': ' . ($val ? __('Ja', 'webling') : __('Nein', 'webling')) . PHP_EOL;
							} else {
								$body .= $field.': '.$val.PHP_EOL;
							}
						}

						$body .= PHP_EOL.PHP_EOL.__("Ein Mitglied mit diesen Daten wurde in deinem Webling erfasst.\n\nDein WordPress", 'webling');
						$body .= PHP_EOL.get_site_url();

						wp_mail($to, $subject, $body);
					}
				}
			}

			// send email confirmation to user
			if ($formconfig['confirmation_email_enabled']) {
				// validate email field
				$fields = WeblingApiHelper::Instance()->getMemberFields();
				if ($formconfig['confirmation_email_webling_field'] && isset($fields[$formconfig['confirmation_email_webling_field']])) {
					// fetch member data to replace placeholders in email text
					$apiCache = new Cache($client, new WordpressCacheAdapter());
					$newmember = $apiCache->getObject('member', $response->getData());
					$emailfield = $fields[$formconfig['confirmation_email_webling_field']];
					$email = sanitize_email($newmember['properties'][$emailfield]);

					// only send if email address is valid
					if (is_email($email)) {
						$subject = trim(__($formconfig['confirmation_email_subject'], 'webling-dynamic'));
						if(!$subject) {
							// if subject is empty, add default subject
							$subject = __("Ihre Anmeldung", 'webling');
						}
						$body = trim(__($formconfig['confirmation_email_text'], 'webling-dynamic'));

						// only send if mail body is not empty
						if ($body) {
							foreach ($fields as $fieldname) {
								$val = $newmember['properties'][$fieldname];
								if (is_array($val)) {
									if (isset($val['size'])) {
										// images/files
										$estimatedFileSize = size_format($val['size'] * (3/4), 1);
										$val = __('Ja', 'webling') . ' (' . $estimatedFileSize .')';
									} else {
										// multienum
										$val = implode(', ', $val);
									}
								} else if(is_bool($val)) {
									// checkboxes
									$val = ($val ? __('Ja', 'webling') : __('Nein', 'webling'));
								}
								$subject = str_replace('[['.$fieldname.']]', $val, $subject);
								$body = str_replace('[['.$fieldname.']]', $val, $body);
							}
							wp_mail($email, $subject, $body);
						}
					}
				}
			}

			// force cache update because we know there are changes
			$apiCache->updateCache(true);

		} catch (Exception $e) {
			if ( WP_DEBUG ) {
				trigger_error($e->getMessage());
			}
			wp_redirect(add_query_arg('webling_form_submitted', 'false', $_POST['webling-form-redirect']));
			exit;
		}

		wp_redirect(add_query_arg('webling_form_submitted', 'true', $_POST['webling-form-redirect']));
		exit;
	}
}
