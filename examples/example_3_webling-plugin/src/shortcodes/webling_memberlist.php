<?php

use Webling\CacheAdapters\WordpressCacheAdapter;
use Webling\Cache\Cache;

class webling_memberlist_shortcode {

	/**
	 * Shortcode Handler for [webling_memberlist]
	 *
	 * @param $atts array shortcode attributes
	 * @return string HTML code for the memberlist
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

			// Load memberlist config
			$id = intval($attributes['id']);
			$config = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}webling_memberlists WHERE id = " . esc_sql($id), 'ARRAY_A');
			if (!$config) {
				throw new Exception('No memberlist with ID '.$id.' found');
			}

			// Load memberlist data
			try {
				return '<div class="webling_memberlist '.esc_attr($config['class']).'">'
					. self::get_html($config)
					. '</div>';

			} catch (\Webling\API\ClientException $exception) {
				throw new Exception('Connection Error: '.$exception->getMessage());
			}
		} catch (Exception $exception) {
			if ( WP_DEBUG ) {
				trigger_error($exception->getMessage());
			}
			return '<p class="webling_memberlist-error">[webling_memberlist] Fehler: '.$exception->getMessage().'</p>';
		}

	}

	/**
	 * @param $listconfig array - list configuration from database
	 * @return string HTML Markup
	 * @throws Exception
	 */
	protected static function get_html($listconfig) {

		$options = get_option('webling-options');
		$fields = json_decode($listconfig['fields']);

		if(count($fields) == 0){
			throw new Exception('Keine Felder angegeben');
		}

		if (!isset($options['host']) || !isset($options['apikey'])) {
			throw new Exception('Zugangsdaten überprüfen');
		}

		$client = new \Webling\API\Client($options['host'], $options['apikey'], array('useragent' => WeblingApiHelper::Instance()->getUserAgent()));
		$apiCache = new Cache($client, new WordpressCacheAdapter());

		// load field definitions
		$memberfields = WeblingApiHelper::Instance()->getMemberFieldDefinitionsByTitle();

		// collect memberIds
		$memberIds = WeblingMemberlistHelper::getMemberlistMemberIds($listconfig);

		// show empty state if there are no members
		if(count($memberIds) == 0){
			return '<p class="webling_memberlist-empty">'.__('Keine Mitglieder.', 'webling').'</p>';
		}

		// fetch member data
		$memberdata = $apiCache->getObjects("member", $memberIds);

		// sort member
		usort($memberdata, function ($a, $b) use ($listconfig) {
			$field = $listconfig['sortfield'];
			if (is_array($a["properties"][$field])) {
				$a_val = mb_strtolower(join(', ', array_values($a["properties"][$field])));
			} else {
				$a_val = mb_strtolower((string)$a["properties"][$field]);
			}
			if (is_array($b["properties"][$field])) {
				$b_val = mb_strtolower(join(', ', array_values($b["properties"][$field])));
			} else {
				$b_val = mb_strtolower((string)$b["properties"][$field]);
			}
			if ($a_val == $b_val) {
				return 0;
			}
			return ($a_val < $b_val) ? -1 : 1;
		});
		if ($listconfig['sortorder'] == 'DESC') {
			$memberdata = array_reverse($memberdata);
		}


		if ($listconfig['design'] == 'CUSTOM') {
			return self::render_html_custom($memberdata, $memberfields, $listconfig);
		} else {
			return self::render_html_list($memberdata, $fields, $memberfields, $listconfig);
		}
	}

	/**
	 * Returns a memberlist in default list style
	 * @param $memberdata - member data
	 * @param $fields - fields to show in current list
	 * @param $memberfields - member field definitions
	 * @return string html
	 */
	protected static function render_html_list($memberdata, $fields, $memberfields, $listconfig) {
		$output  = '<table class="webling_memberlist-table">';
		$output .= '<tr>';
		foreach ($fields as $field) {
			$output .= '<th>' . esc_html(__($field, 'webling-dynamic')) . '</th>';
		}
		$output .= '</tr>';

		// display member data
		foreach ($memberdata as $member) {
			$output .= '<tr>';
			foreach ($fields as $field) {
				$output .= "<td>";
				$value = $member['properties'][$field];
				$type = (isset($memberfields[$field]['type']) ? $memberfields[$field]['type'] : null);
				$datatype = $memberfields[$field]['datatype'];
				if ($datatype == 'image') {
					$url = self::getBinaryUrl($value, $member['id'], $field, $listconfig, []);
					if ($url) {
						$output .= '<a href="'.$url.'"><img src="'.$url.'&height=50" srcset="'.$url.'&height=50 1x, '.$url.'&height=100 2x" class="webling_memberlist-image" loading="lazy"></a>';
					}
				} else {
					$output .= self::field_formatter($value, $memberfields[$field]['datatype'], $type);
				}
				$output .= "</td>";
			}
			$output .= '</tr>';
		}
		$output .= '</table>';

		return $output;
	}

	/**
	 * Returns a memberlist rendered with a custom html template
	 * @param $memberdata array - member data
	 * @param $memberfields array - member field definitions
	 * @param $listconfig array
	 * @return string html
	 */
	protected static function render_html_custom($memberdata, $memberfields, $listconfig) {
		// display member data
		$output = '';
		foreach ($memberdata as $member) {
			$tpl = $listconfig['custom_template'];
			if (is_array($member['properties'])) {

				// get all placeholders
				$placeholders = [];
				preg_match_all('/\[\[(.*?)\]\]/', $listconfig['custom_template'], $matches);
				if ($matches && isset($matches[1]) && count($matches[1]) > 0) {
					$placeholders = $matches[1];
				}

				// replace all placeholders
				if ($placeholders) {
					foreach ($placeholders as $placeholder) {

						// parse fieldname and options
						$field = null;
						$options = [];
						if (array_key_exists($placeholder, $member['properties'])) {
							$field = $placeholder;
						} else {
							$placeholder_parts = explode('@', $placeholder);
							if (count($placeholder_parts) >= 2) {
								$field = $placeholder_parts[0];
								$options = self::parsePlaceholderOptions($placeholder_parts[1]);
							}
						}

						if ($field !== null) {
							$value = $member['properties'][$field];
							$type = (isset($memberfields[$field]['type']) ? $memberfields[$field]['type'] : null);
							$datatype = $memberfields[$field]['datatype'];
							if ($datatype == 'image') {
								$formatted_value = self::getBinaryUrl($value, $member['id'], $field, $listconfig, $options);
							} else {
								$formatted_value = self::field_formatter($value, $datatype, $type);
							}
							$tpl = str_replace('[['.$placeholder.']]', $formatted_value, $tpl);
						}
					}
				}
			}
			$output .= $tpl;
		}
		return $output;
	}

	/**
	 * @param $value mixed
	 * @param $memberId int
	 * @param $fieldname string
	 * @param $listconfig array
	 * @return string wordpress api url or empty string
	 */
	protected static function getBinaryUrl($value, $memberId, $fieldname, $listconfig, $options) {
		// images return an url
		if (is_array($value)) {
			$queryParams = [
				'id' => intval($memberId),
				'prop' => $fieldname,
				'list' => intval($listconfig['id']),
			];
			if (isset($options['height'])) {
				$queryParams['height'] = $options['height'];
			}
			if (isset($options['width'])) {
				$queryParams['width'] = $options['width'];
			}
			return get_rest_url(null, '/webling/v1/memberimage?'.http_build_query($queryParams));
		} else {
			return '';
		}
	}

	/**
	 * @param $value string options as a string
	 * @return array parsed options as an indexed array
	 */
	protected static function parsePlaceholderOptions($value) {
		// remove all whitespaces
		$value = $string = preg_replace('/\s+/', '', $value);
		$value = strtolower($value);

		// split multiple options
		$indexed = [];
		$options = explode(',', $value);
		if (is_array($options)) {
			foreach ($options as $option) {
				// extract key & value of option
				$parts = explode('=', $option);
				if (count($parts) == 2 && strlen($parts[0]) > 0 && strlen($parts[1]) > 0) {
					$indexed[$parts[0]] = $parts[1];
				}
			}
		}
		return $indexed;
	}

	/**
	 * Formats a value according to it's type
	 *
	 * @param $value mixed raw value
	 * @param $datatype string the datatype of the value
	 * @param $label string label of the value
	 * @return string formatted string
	 */
	protected static function field_formatter($value, $datatype, $label){
		switch ($datatype){
			case 'numeric':
				return number_format((float)$value, 2);
				break;
			case 'bool':
				return ($value ? 'Ja' : 'Nein');
				break;
			case 'date':
				if ($value) {
					$time = strtotime($value);
					if($time !== false){
						return date('d.m.Y', $time);
					}
				}
				return '';
				break;
			case 'multienum':
				if(is_array($value)){
					return implode(', ', $value);
				}
				return '';
				break;
			case 'image':
			case 'file':
				// files are currently not supported, images are handled differently (see getBinaryUrl())
				return '';
				break;
			case 'text':
				switch ($label){
					case 'url':
						if($value){
							$prefixed_url = $value;
							if ($ret = parse_url($prefixed_url)) {
								if (!isset($ret["scheme"])) {
									$prefixed_url = 'http://'.$prefixed_url;
								}
							}
							return '<a href="'.$prefixed_url.'" target="_blank">'.$value.'</a>';
						} else {
							return $value;
						}
						break;
					case 'email':
						if($value && strpos($value, '@') !== false) {
							return '<a href="mailto:'.trim($value).'" target="_blank">'.$value.'</a>';
						} else {
							return $value;
						}
						break;
					default:
						return $value;
				}
				break;
			case 'longtext':
			case 'int':
			case 'autoincrement':
			case 'enum':
			default:
				if(is_array($value)) {
					return "&nbsp;";
				}
				return $value;
		}
	}
}
