<?php

function webling_admin_save_memberlist()
{
	global $wpdb;

	$_POST = stripslashes_deep($_POST);

	// sanitize id
	$id = intval($_POST['list_id']);

	// sanitize design
	$available_designs = array('LIST', 'CUSTOM');
	if (!in_array($_POST['design'], $available_designs)) {
		$_POST['design'] = 'LIST';
	}

	// serialize groups value
	if (isset($_POST['groups'])) {
		$groups = serialize(array_keys($_POST['groups']));
	} else {
		$groups = serialize(array());
	}

	// sanitize type
	$available_types = array('ALL','GROUPS','SAVEDSEARCH');
	if (!in_array($_POST['type'], $available_types)) {
		$_POST['type'] = 'ALL';
	}

	// serialize groups value
	if (isset($_POST['savedsearch'])) {
		$savedsearch = intval($_POST['savedsearch']);
	} else {
		$savedsearch = 0;
	}

	if ($id) {
		// update list
		$wpdb->query(
			$wpdb->prepare("
				UPDATE {$wpdb->prefix}webling_memberlists
				SET 
				`title` = %s,
				`groups` = %s,
				`fields` = %s,
				`class` = %s,
				`sortfield` = %s,
				`sortorder` = %s,
				`design` = %s,
				`custom_template` = %s,
				`type` = %s,
				`savedsearch` = %d,
				`savedsearch_cache` = NULL,
				`savedsearch_cache_revision` = 0
				WHERE id = %d",
				$_POST['title'],
				$groups,
				$_POST['fields'],
				$_POST['class'],
				$_POST['sortfield'],
				$_POST['sortorder'],
				$_POST['design'],
				$_POST['custom_template'],
				$_POST['type'],
				$savedsearch,
				$id
			)
		);
	} else {
		// create list
		$wpdb->query(
			$wpdb->prepare("
				INSERT INTO {$wpdb->prefix}webling_memberlists
				(
					`title`,
					`groups`,
					`fields`,
					`class`,
					`sortfield`,
					`sortorder`,
					`design`,
					`custom_template`,
					`type`,
					`savedsearch`
				) VALUES (
					%s,
					%s,
					%s,
					%s,
					%s,
					%s,
					%s,
					%s,
					%s,
					%d
				)",
				$_POST['title'],
				$groups,
				$_POST['fields'],
				$_POST['class'],
				$_POST['sortfield'],
				$_POST['sortorder'],
				$_POST['design'],
				$_POST['custom_template'],
				$_POST['type'],
				$savedsearch
			)
		);

		// update created_at field
		$wpdb->query(
			$wpdb->prepare("
				UPDATE {$wpdb->prefix}webling_memberlists set `created_at` = `updated_at` WHERE id = %d",
				$wpdb->insert_id
			)
		);

	}

	wp_redirect(admin_url('admin.php?page=webling_page_memberlist_list'));
	exit;
}
