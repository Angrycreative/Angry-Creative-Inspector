<?php

/*
 *	Logs if a plugin has been activated/deactivated
 */

class ACI_Routine_Log_Plugin_Change {

	const LOG_LEVEL = "warning";

	const DESCRIPTION = "Logs if a plugin has been activated or deactivated.";

	public static function register() {

		$default_options = array( 'log_level' => self::LOG_LEVEL, 
								  'description' => self::DESCRIPTION,
								  'site_specific_settings' => 0 );
		
		aci_register_routine( __CLASS__, $default_options, "activate_plugin", 10, 1 );
		aci_register_routine( __CLASS__, $default_options, "deactivate_plugin", 10, 1 );

	}

	public static function inspect( $plugin ) {

		$user = wp_get_current_user();
		$site = (is_multisite()) ? ' on "' . get_blog_details(get_current_blog_id())->blogname . '"' : '';
		$usermsg = ($user instanceof WP_User) ? ' (user: '.$user->user_login.')' : '';

		switch( current_filter() ) {
			case 'activate_plugin':
				$status = 'activated';
				break;
			case 'deactivate_plugin':
				$status = 'deactivated';
				break;
		}

		if ( !empty($status) ) {
			
			$plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' .$plugin);
			$message = 'Plugin "'.$plugin_data['Name']. '" was ' . $status . $usermsg . $site;

			AC_Inspector::log( $message, __CLASS__ );

		}

		return "";

	}

}

ACI_Routine_Log_Plugin_Change::register();
