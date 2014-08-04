<?php

/*
 *	Logs if a site has been activated/deactivated
 */

if ( is_multisite() && is_plugin_active_for_network( ACI_PLUGIN_BASENAME ) ) {

	class ACI_Routine_Log_Site_Change {

		const LOG_LEVEL = "warning";

		const DESCRIPTION = "Logs if a site in the network has been activated or deactivated.";

		public static function register() {

			$default_options = array( 'log_level' => self::LOG_LEVEL, 
									  'description' => self::DESCRIPTION );
			
			aci_register_routine( __CLASS__, $default_options, "activate_blog", 10, 1 );
			aci_register_routine( __CLASS__, $default_options, "deactivate_blog", 10, 1 );

		}

		public static function inspect( $site ) {

			$user = wp_get_current_user();
			$usermsg = ($user instanceof WP_User) ? ' (user: '.$user->user_login.')' : '';
			
			switch( current_filter() ) {
				case 'activate_blog':
					$status = 'activated';
					break;
				case 'deactivate_blog':
					$status = 'deactivated';
					break;
			}

			$message = 'Site ' . get_blog_details($site)->blogname . ' (id: '.$site . ')' . ' was ' . $status . $usermsg;

			AC_Inspector::log( $message, __CLASS__ );

			return "";

		}

	}

	ACI_Routine_Log_Site_Change::register();

}