<?php

function webling_update_field_webling_options( $new_value, $old_value ) {
	if (isset($new_value['apikey'])) {
		$new_value['apikey'] = trim($new_value['apikey']);
	}
	if (isset($new_value['host'])) {
		$new_value['host'] = trim($new_value['host']);
		$url = parse_url($new_value['host']);
		if (!isset($url['scheme'])) {
			$new_value['host'] = 'https://' . $new_value['host'];
		} else {
			$new_value['host'] = $url['scheme'] . '://' . $url['host'];
		}
	}
	return $new_value;
}
