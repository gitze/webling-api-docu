<?php

class webling_page_settings {

	public static function html () {

		add_settings_section(
			'webling_page_settings_section',
			__( 'Webling Plugin Einstellungen', 'webling' ),
			['webling_page_settings','section_callback'],
			'webling-options-group'
		);

		add_settings_field(
			'webling_field_host',
			__( 'Webling-URL', 'webling' ),
			['webling_page_settings','host_render'],
			'webling-options-group',
			'webling_page_settings_section'
		);

		add_settings_field(
			'webling_field_apikey',
			__( 'API Key', 'webling' ),
			['webling_page_settings','apikey_render'],
			'webling-options-group',
			'webling_page_settings_section'
		);

		add_settings_field(
			'webling_field_css',
			__( 'Eigenes CSS', 'webling' ),
			['webling_page_settings','css_render'],
			'webling-options-group',
			'webling_page_settings_section'
		);

// Disable CAPTCHA 4WP Option for future users, because this plugin it does not work with the latest version.
// Keep the options for existing users, can be removed completely in the future
//		add_settings_field(
//			'webling_field_anr_enabled',
//			__( 'Captcha Plugin Integration', 'webling' ),
//			['webling_page_settings','anr_enabled_render'],
//			'webling-options-group',
//			'webling_page_settings_section'
//		);

		add_settings_field(
			'webling_field_friendlycaptcha_enabled',
			__( 'Friendly Captcha Plugin Integration', 'webling' ),
			['webling_page_settings','friendlycaptcha_enabled_render'],
			'webling-options-group',
			'webling_page_settings_section'
		);

		echo '<div class="wrap">';

		// display the settings form
		echo '<form method="post" action="options.php" id="webling-form">';
		settings_fields('webling-options-group');
		do_settings_sections('webling-options-group');
		submit_button();
		echo '</form>';

		// only show clear cache when there is an apikey
		$options = get_option('webling-options');
		if (strlen($options['apikey']) > 0) {
			echo '<form method="post" action="'.admin_url( 'admin-post.php' ).'">';
			echo '<input type="hidden" name="action" value="webling-clear-cache">';
			echo '<p>';
			echo '<b>'.__('Webling Cache', 'webling').'</b>';
			echo '<div>'.__('Damit die Seiten schneller laden, werden die Daten von Webling in WordPress zwischengespeichert.', 'webling').'<br>';
			echo __('Es wird maximal einmal pro Minute auf Updates in Webling geprüft.', 'webling').'<br>';
			echo __('Du kannst ein Update forcieren, indem du den Cache löschst.', 'webling').' ';
			echo '</p>';
			submit_button(__('Cache löschen', 'webling'), 'secondary');
			echo '</form>';
		}

		// versions for debugging
		self::print_versions();

		echo '</div>';
	}

	public static function host_render() {

		$options = get_option('webling-options');
		echo '<input type="text" name="webling-options[host]" value="'.$options['host'].'" style="width: 400px;">';
		echo '<p class="description">'.__('Die Adresse deines Weblings (z.B. demo1.webling.ch)', 'webling').'</p>';
	}


	public static function apikey_render() {
		$options = get_option('webling-options');
		echo '<input type="text" name="webling-options[apikey]" value="'.$options['apikey'].'" style="width: 400px;">';
		echo '<p class="description">'.__('Einen API-Key kannst du dir in deinem Webling unter "Administration" &raquo; "API" erstellen.', 'webling').'</p>';
	}


	public static function css_render() {
		$options = get_option('webling-options');
		echo '<textarea rows="5" cols="60" name="webling-options[css]">'.$options['css'].'</textarea>';
		echo '<p class="description">'.__('Eigenes CSS für Designanpassungen.', 'webling').'</p>';
	}

	public static function anr_enabled_render() {
		$options = get_option('webling-options');
		$installed = function_exists('anr_captcha_form_field') && function_exists('anr_verify_captcha') || function_exists('c4wp_captcha_form_field') && function_exists('c4wp_verify_captcha');
		echo '<label>';
		echo '<input type="checkbox" name="webling-options[anr_enabled]" value="1" '.((isset($options['anr_enabled']) && $options['anr_enabled'] === '1') ? 'checked="checked"' : '').' '.(!$installed ? 'disabled' : '').'>';
		echo __('Support für das "CAPTCHA 4WP" Plugin aktivieren', 'webling');
		echo '</label>';
		echo '<p class="description">';
		echo __('Wenn du das Plugin <a href="https://de.wordpress.org/plugins/advanced-nocaptcha-recaptcha" target="_blank">CAPTCHA 4WP</a> installiert hast, dann kannst du den Spamschutz für die Formulare hier aktivieren (ab Version 7 von CAPTCHA 4WP nur noch mit der Premium-Version möglich)', 'webling');
		echo '</p>';
		if ($installed) {
			echo '<p><span class="dashicons dashicons-yes"></span> '.__('Das Plugin ist installiert.', 'webling').'</p>';
		} else {
			echo '<p><span class="dashicons dashicons-no"></span> '.__('Das Plugin ist zurzeit nicht installiert.', 'webling').'</p>';
		}
	}

	public static function friendlycaptcha_enabled_render() {
		$options = get_option('webling-options');
		$installed = class_exists('FriendlyCaptcha_Plugin') && function_exists('frcaptcha_generate_widget_tag_from_plugin') && function_exists('frcaptcha_verify_captcha_solution');
		echo '<label>';
		echo '<input type="checkbox" name="webling-options[friendlycaptcha_enabled]" value="1" '.((isset($options['friendlycaptcha_enabled']) && $options['friendlycaptcha_enabled'] === '1') ? 'checked="checked"' : '').' '.(!$installed ? 'disabled' : '').'>';
		echo __('Support für das "Friendly Captcha" Plugin aktivieren', 'webling');
		echo '</label>';
		echo '<p class="description">';
		echo __('Wenn du das Plugin <a href="https://wordpress.org/plugins/friendly-captcha/" target="_blank">Friendly Captcha</a> installiert hast, dann kannst du den Spamschutz für die Formulare hier aktivieren.', 'webling');
		echo '</p>';
		if ($installed) {
			echo '<p><span class="dashicons dashicons-yes"></span> '.__('Das Plugin ist installiert.', 'webling').'</p>';
		} else {
			echo '<p><span class="dashicons dashicons-no"></span> '.__('Das Plugin ist zurzeit nicht installiert.', 'webling').'</p>';
		}
	}


	public static function section_callback() {
		echo __( 'Mit diesem Plugin kannst du Mitgliederdaten aus deiner <a href="https://www.webling.eu" target="_blank">Webling Vereinsverwaltung</a> auf einer Seite anzeigen lassen oder via Anmeldeformular automatisch erfassen.', 'webling' );
		echo '<br>';
		echo __( 'Es wird mindestens ein "Webling Basic" Abo benötigt, damit dieses Plugin verwendet werden kann.', 'webling' );
		echo '<br><br>';
		echo '<b>'.__( 'Verbindungsstatus: ', 'webling' ) . '</b> '. self::connection_status();
	}

	public static function print_versions() {
		global $wpdb, $wp_version;
		echo '<div style="font-size: 90%; color: rgba(0,0,0,0.5);">';
		$plugin = get_plugin_data(WEBLING_PLUGIN_DIR . '/webling.php', false);
		echo 'Versionsinfo: PHP ' . phpversion() . '; MySQL ' . $wpdb->db_version() . '; WordPress ' . $wp_version . '; Webling Plugin ' . $plugin['Version']. '; Webling DB v' . WEBLING_DB_VERSION . ';';
		echo '</div>';
	}

	public static function connection_status() {
		$options = get_option('webling-options');

		if (!$options['host'] || !$options['apikey']) {
			return '<span style="color: grey;">Keine Zugangsdaten. Bitte Webling-URL und API Key angeben.</span>';
		}
		try {
			$connection = new \Webling\API\Client($options['host'], $options['apikey']);
			$response = $connection->get('config');
			if ($response->getStatusCode() == '200') {
				$config = $response->getData();
				if (isset($config['domain']) && $config['domain']) {
					return '<span style="color: #79c700;">Verbindung OK</span>';
				} else {
					return '<span style="color: red;">Ungültige Webling URL.</span>';
				}
			} else {
				if ($response->getStatusCode() == '401') {
					return '<span style="color: red;">Ungültige Zugangsdaten.</span>';
				} else {
					$data = $response->getData();
					$error = '';
					if (isset($data['error'])) {
						$error = ' - ' . $data['error'];
					}
					return '<span style="color: red;">Verbindungsfehler: '.$response->getStatusCode().' '.$error.'</span>';
				}
			}
		} catch (\Webling\API\ClientException $exception) {
			return '<span style="color: red;">Verbindungsfehler: '.$exception->getMessage().'</span>';
		}
	}
}
