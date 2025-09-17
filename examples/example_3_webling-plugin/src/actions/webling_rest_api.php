<?php

use Webling\CacheAdapters\WordpressCacheAdapter;
use Webling\Cache\Cache;

function webling_register_rest_api() {
	register_rest_route( 'webling/v1', '/memberimage', array(
		'methods' => 'GET',
		'callback' => 'webling_rest_api_memberimage',
		'permission_callback' => '__return_true'
	) );
}


/**
 * A proxy to load and cache images from the webling api
 * The list id is required to check access to the file
 *
 * @param $args WP_REST_Request
 * @return string|void
 * @throws \Webling\API\ClientException
 * @throws \Webling\Cache\CacheException
 */
function webling_rest_api_memberimage($args) {
	global $wpdb;

	$params = $args->get_query_params();
	if (!isset($params['id']) || !isset($params['prop']) || !isset($params['list'])) {
		http_response_code(404);
		return '400 Missing Parameters';
	}

	$options = get_option('webling-options');

	if (!isset($options['host']) || !isset($options['apikey'])) {
		http_response_code(400);
		return '400 Invalid Webling API Credentials';
	}

	$client = new \Webling\API\Client($options['host'], $options['apikey'], array('useragent' => WeblingApiHelper::Instance()->getUserAgent()));
	$apiCache = new Cache($client, new WordpressCacheAdapter());

	// Load memberlist config
	$listId = intval($params['list']);
	$listconfig = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}webling_memberlists WHERE id = " . esc_sql($listId), 'ARRAY_A');
	if (!$listconfig) {
		http_response_code(404);
		return '404 Memberlist not found';
	}

	// validate field
	if ($listconfig['design'] == 'CUSTOM') {
		// only allow fields that are in the template
		$allowedFields = [];
		preg_match_all('/\[\[(.*?)\]\]/', $listconfig['custom_template'], $matches);
		if ($matches && isset($matches[1]) && count($matches[1]) > 0) {
			foreach ($matches[1] as $match) {
				// discard options
				$name = explode('@', $match);
				$allowedFields[] = trim($name[0]);
			}
		}
	} else {
		// only allow fields that are in the fieldslist
		$allowedFields = json_decode($listconfig['fields']);
	}
	if (!in_array($params['prop'], $allowedFields)) {
		http_response_code(403);
		return '403 Field not allowed';
	}

	// validate member id
	$memberId = intval($params['id']);
	$memberIds = WeblingMemberlistHelper::getMemberlistMemberIds($listconfig);
	if (!in_array($memberId, $memberIds)) {
		http_response_code(403);
		return '403 Member not allowed';
	}

	// get actual image from cache
	$member = $apiCache->getObject('member', $memberId);
	$image_data = null;
	if (isset($member['properties'][$params['prop']]) && $member['properties'][$params['prop']] !== null) {
		$options = [];
		if (isset($params['height'])) {
			$options['height'] = $params['height'];
		}
		if (isset($params['width'])) {
			$options['width'] = $params['width'];
		}
		$data = $apiCache->getObjectBinary('member', $memberId, $member['properties'][$params['prop']]['href'], $options);
		if (strlen($data)) {
			$image_data = $data;
		}
	}

	// display image
	if ($image_data !== null) {
		$finfo = new finfo(FILEINFO_MIME);
		$mime = $finfo->buffer($image_data);
		header("Content-type: " . $mime);
		echo $image_data;
		exit();
	} else {
		http_response_code(404);
		return '404 Not Found';
	}
}
