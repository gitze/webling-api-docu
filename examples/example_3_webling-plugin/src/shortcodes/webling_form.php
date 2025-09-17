<?php

use Webling\CacheAdapters\WordpressCacheAdapter;
use Webling\Cache\Cache;

class webling_form_shortcode {

	/**
	 * Shortcode Handler for [webling_form]
	 *
	 * @param $atts array shortcode attributes
	 * @return string HTML code for the from
	 */
	public static function handler($atts) {

		global $wpdb;

		try {
			// filter shortcode attributes
			$attributes = shortcode_atts( array(
				'id' => null
			), $atts );

			if (!$attributes['id']) {
				throw new Exception('No ID in shortcode');
			}

			// Load form config
			$id = intval($attributes['id']);
			$config = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}webling_forms WHERE id = " . esc_sql($id), 'ARRAY_A');
			if (!$config) {
				throw new Exception('No form with ID '.$id.' found');
			}

			// Load form data
			try {
				if (isset($_REQUEST['webling_form_submitted'])) {
					if ($_REQUEST['webling_form_submitted'] == 'true') {
						return '<div class="webling-form webling-form__submitted ' . esc_attr($config['class']) . '">'
							. __($config['confirmation_text'], 'webling-dynamic')
							. '</div>';
					} else {
						return '<div class="webling-form webling-form__error ' . esc_attr($config['class']) . '">'
							. __('Sorry, Das Formular konnte nicht gesendet werden, beim Verarbeiten der Daten ist ein Fehler aufgetreten. Versuche es später noch einmal.', 'webling')
							. '</div>';
					}
				} else if (isset($_REQUEST['webling_form_error_max_signups'])) {
					return '<div class="webling-form webling-form__max-signups ' . esc_attr($config['class']) . '">'
							. __($config['max_signups_text'], 'webling-dynamic')
							. '</div>';
				} else if (isset($_REQUEST['webling_form_error_required_fields'])) {
					return '<div class="webling-form webling-form__error ' . esc_attr($config['class']) . '">'
						. '<h2>' . __('Fehler', 'webling') . '</h2>'
						. __('Nicht alle benötigten Felder ausgefüllt!', 'webling')
						. '<br><br>'
						. '<a href="?">' . __('Zurück zum Formular', 'webling') . '</a>'
						. '</div>';
				} else {
					// check if max signups is reached (use cache)
					if ($config['max_signups'] > 0) {
						$client = WeblingApiHelper::Instance()->client();
						$apiCache = new Cache($client, new WordpressCacheAdapter());
						$membergroup = $apiCache->getObject('membergroup', $config['group_id']);
						$childrenCount = 0;
						if (isset($membergroup['children']['member'])) {
							$childrenCount = count($membergroup['children']['member']);
						}
						if ($childrenCount >= $config['max_signups']) {
							return '<div class="webling-form webling-form__max-signups ' . esc_attr($config['class']) . '">'
								. __($config['max_signups_text'], 'webling-dynamic')
								. '</div>';
						}
					}

					// show form
					return '<div class="webling-form '.esc_attr($config['class']).'">'
						. self::get_html($config)
						. '</div>';
				}

			} catch (\Webling\API\ClientException $exception) {
				throw new Exception('Connection Error: '.$exception->getMessage());
			}
		} catch (Exception $exception) {
			if ( WP_DEBUG ) {
				trigger_error($exception->getMessage());
			}
			return '<p class="webling-form__error">[webling_form] Fehler: '.$exception->getMessage().'</p>';
		}

	}

	/**
	 * @param $formconfig array - form configuration from database
	 * @return string HTML Markup
	 * @throws Exception
	 */
	protected static function get_html($formconfig) {
		global $wpdb;
		global $wp;

		// load field definitions
		$definitions = WeblingApiHelper::Instance()->getMemberFieldDefinitionsById();
		$sql = "SELECT * FROM {$wpdb->prefix}webling_form_fields WHERE form_id = " . esc_sql($formconfig['id']) . " ORDER BY `order` ASC";
		$formfields = $wpdb->get_results($sql, 'ARRAY_A');

		if (!is_array($formfields) || count($formfields) == 0) {
			throw new Exception('Keine Formularfelder konfiguriert');
		}

		$html = '';

		foreach ($formfields as $field) {
			switch ($field['field_name_position']){
				case 'HIDDEN':
					$positionclass = 'webling-form__group--hidden';
					break;
				case 'LEFT':
					$positionclass = 'webling-form__group--left';
					break;
				case 'TOP':
				default:
					$positionclass = 'webling-form__group--top';
			}

			$fieldnameclass = 'webling-form__group--field-'.preg_replace("/[^A-Za-z0-9]/", '', $field['field_name']);

			$html .= '<div class="webling-form__group '.$positionclass.' '.$field['class'].' '.$fieldnameclass.'">';

			$html .= '<label for="webling-form-field-' . $field['id'] . '" class="webling-form__label">';
			$html .= esc_html(__($field['field_name'], 'webling-dynamic'));
			if ($field['required']) {
				$html .= ' <span class="webling-form__required">*</span>';
			}
			$html .= '</label>';

			$html .= '<div class="webling-form__field">';
			$html .= self::getInputForType($field, $definitions);
			if ($field['description_text']) {
				$html .= '<small class="webling-form__description">'. self::linkifyText(esc_html(__($field['description_text'], 'webling-dynamic'))).'</small>';
			}
			$html .= '</div>';
			$html .= '</div>';
		}

		$form  = '<form action="?" method="post" enctype="multipart/form-data" onsubmit="webling_disableSubmitButton()">';
		$form .= '<input type="hidden" name="webling-form-id" value="'.$formconfig['id'].'"/>';
		$form .= '<input type="hidden" name="webling-form-redirect" value="'.esc_attr(get_permalink()).'"/>';
		$form .= $html;

		// add honeypot field ("display: none" via css)
		$form .= '<input type="text" id="webling-form-field_0" autocomplete="off" name="webling-form-field[0]" value=""/>';

		// support for https://wordpress.org/plugins/advanced-nocaptcha-recaptcha/
		$options = get_option('webling-options');
		if (isset($options['anr_enabled']) && $options['anr_enabled'] === '1') {
			if (function_exists('anr_captcha_form_field') && function_exists('anr_verify_captcha')) {
				$form .= '<div class="webling-form__captcha">';
				// unfortunately the anr plugin echoes the html by default, so we use the function directly to overwrite the parameter
				$form .= anr_captcha_form_field(false);
				$form .= '</div>';
			}
			if (function_exists('c4wp_captcha_form_field') && function_exists('c4wp_verify_captcha')) {
				$form .= '<div class="webling-form__captcha">';
				// unfortunately the anr plugin echoes the html by default, so we use the function directly to overwrite the parameter
				$form .= c4wp_captcha_form_field(false);
				$form .= '</div>';
			}
		}

		// support for https://wordpress.org/plugins/friendly-captcha/
		if (isset($options['friendlycaptcha_enabled']) && $options['friendlycaptcha_enabled'] === '1') {
			if (class_exists('FriendlyCaptcha_Plugin') && function_exists('frcaptcha_generate_widget_tag_from_plugin') && function_exists('frcaptcha_enqueue_widget_scripts')) {
				$plugin = FriendlyCaptcha_Plugin::$instance;
				if ($plugin->is_configured()) {
					$form .= frcaptcha_generate_widget_tag_from_plugin($plugin);
					frcaptcha_enqueue_widget_scripts();
				}
			}
		}

		$form .= '<p><input type="submit" value="'.esc_attr(__($formconfig['submit_button_text'], 'webling-dynamic')).'" class="webling-form__submit" id="webling-form__submit"></p>';
		$form .= '</form>';

		// load inline script from file
		$form .= '<script type="text/javascript">'.file_get_contents(WEBLING_PLUGIN_DIR . '/js/frontend.js').'</script>';

		return $form;
	}

	protected static function getInputForType($field, $definitions) {
		if (!isset($definitions[$field['webling_field_id']])) {
			return '';
		}
		$datatype = $definitions[$field['webling_field_id']]['datatype'];
		$label = (isset($definitions[$field['webling_field_id']]['type']) ? $definitions[$field['webling_field_id']]['type'] : '');
		$required = ($field['required'] ? 'required' : '');
		$id = 'webling-form-field-'.$field['id'];
		$name = 'webling-form-field['.$field['id'].']';
		$class = 'webling-form__input';
		$placeholder = ($field['placeholder_text'] ? esc_attr(__($field['placeholder_text'], 'webling-dynamic')) : '');
		$select_options = json_decode($field['select_options']);
		if (!is_array($select_options)) {
			$select_options = array();
		}
		switch ($datatype){
			case 'multienum':
				$options = [];
				if (isset($definitions[$field['webling_field_id']]['values'])) {
					foreach ($definitions[$field['webling_field_id']]['values'] as $value) {
						if (count($select_options) == 0 || in_array($value['id'], $select_options)) {
							$options[] = '<div><label><input type="checkbox" class="webling-form__checkbox" name="'.$name.'['.base64_encode($value['value']).']"/> '.esc_html(__($value['value'], 'webling-dynamic')).'</label></div>';
						}
					}
				}
				return '<div class="webling-form__multiselect" >'.implode($options).'</div>';
			case 'enum':
				$options = [];
				if (isset($definitions[$field['webling_field_id']]['values'])) {

					if ($placeholder) {
						$options[] = '<option value="">'.esc_html(__($field['placeholder_text'], 'webling-dynamic')).'</option>';
					}
					foreach ($definitions[$field['webling_field_id']]['values'] as $value) {
						if (count($select_options) == 0 || in_array($value['id'], $select_options)) {
							$options[] = '<option value="'.esc_attr($value['value']).'">'.esc_html(__($value['value'], 'webling-dynamic')).'</option>';
						}
					}
				}
				return '<select name="' . $name . '" id="' . $id . '" class="webling-form__select" ' . $required . '>'.implode($options).'</select>';
			case 'bool':
				return '<span>'
					. '<label>'
					. '<input type="checkbox" class="webling-form__checkbox" name="' . $name . '" id="' . $id . '" ' . $required . '> '
					. esc_html(__($field['placeholder_text'], 'webling-dynamic')).' '
					.'</label></span>';
			case 'date':
				return '<input type="date" name="' . $name . '" id="' . $id . '" placeholder="TT.MM.JJJJ" class="'.$class.'" ' . $required . '>';
			case 'longtext':
				return '<textarea rows="6" name="' . $name . '" id="' . $id . '" placeholder="'.$placeholder.'" class="'.$class.'" '.$required.'></textarea>';
			case 'int':
				return '<input type="number" step="1" name="'.$name.'" id="'.$id.'" placeholder="'.$placeholder.'" class="'.$class.'" '.$required.'>';
			case 'numeric':
				return '<input type="number" step="any" name="'.$name.'" id="'.$id.'" placeholder="'.$placeholder.'" class="'.$class.'" '.$required.'>';
			case 'text':
				switch ($label) {
					case 'phone':
					case 'mobile':
						return '<input type="tel" name="' . $name . '" id="' . $id . '" placeholder="' . $placeholder . '" class="'.$class.'" ' . $required . '>';
					case 'url':
						return '<input type="url" name="' . $name . '" id="' . $id . '" placeholder="' . $placeholder . '" class="'.$class.'" ' . $required . '>';
					case 'email':
						return '<input type="email" name="' . $name . '" id="' . $id . '" placeholder="' . $placeholder . '" class="'.$class.'" ' . $required . '>';
					default:
						return '<input type="text" name="' . $name . '" id="' . $id . '" placeholder="' . $placeholder . '" class="'.$class.'" ' . $required . '>';
				}
			case 'image':
				return '<input type="file" name="webling-form-field-' . $field['id'] . '" id="' . $id . '" class="'.$class.'" ' . $required . ' accept="image/*">';
			case 'file':
				return '<input type="file" name="webling-form-field-' . $field['id'] . '" id="' . $id . '" class="'.$class.'" ' . $required . '>';
			case 'autoincrement':
			default:
				// autoincrement cannot be set
				return '';
		}
	}

	protected static function linkifyText($string) {
		$url = '@(http(s)?)?(://)?(([a-zA-Z])([-\w]+\.)+([^\s\.]+[^\s]*)+[^,.\s])@';
		return preg_replace($url, '<a href="http$2://$4" target="_blank" title="$0">$0</a>', $string);
	}
}
