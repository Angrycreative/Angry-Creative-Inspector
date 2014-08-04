<?php

if ( is_multisite() && is_plugin_active_for_network( ACI_PLUGIN_BASENAME ) ) {

	class ACI_Routine_Log_User_Site_Registration {

		const LOG_LEVEL = "notice";

		const DESCRIPTION = "Logs whenever a user is added to a site in the network.";

		public static function register() {

			$default_options = array( 'log_level' => self::LOG_LEVEL, 
									  'description' => self::DESCRIPTION,
									  'site_specific_settings' => 0 );
			
			aci_register_routine( __CLASS__, $default_options, "add_user_to_blog", 10, 3 );

		}

		public static function inspect( $user_id, $role, $site_id ) {

			$current_user = wp_get_current_user();
			$added_user = get_user_by( 'id', $user_id );
			$site_name = get_blog_details($site_id)->blogname;

			if ( !( $added_user instanceof WP_User ) ) {
				return "";
			}

			if ( $current_user instanceof WP_User ) {
				$log_message = $added_user->display_name . " (" . $added_user->user_login . ") " .
							   "was added as " . $role . " to " . $site_name . " by " . $current_user->display_name . " (" . $current_user->user_login . ").";
			} else {
				$log_message = $added_user->display_name . " (" . $added_user->user_login . ") " .
							   "was added as " . $role . " to " . $site_name . ".";
			}

			AC_Inspector::log( $log_message, __CLASS__ );

			return "";

		}

	}

	ACI_Routine_Log_User_Site_Registration::register();

}