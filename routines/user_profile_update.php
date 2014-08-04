<?php

class ACI_Routine_Log_User_Profile_Update {

	const LOG_LEVEL = "notice";

	const DESCRIPTION = "Logs whenever a user's profile is updated, including changes to their e-mail and/or password.";

	public static function register() {

		$default_options = array( 'log_level' => self::LOG_LEVEL, 
								  'description' => self::DESCRIPTION,
								  'site_specific_settings' => 0 );
		
		aci_register_routine( __CLASS__, $default_options, "profile_update", 10, 2 );

	}

	public static function inspect( $user_id, $old_user_data = array() ) {

		$current_user = wp_get_current_user();
		$updated_user = get_user_by( 'id', $user_id );

		if ( !( $updated_user instanceof WP_User ) ) {
			return "";
		}

		$changed_user_properties = array();

		foreach( get_object_vars( $updated_user->data ) as $property_key => $property_val ) {

			if ( in_array( gettype( $property_val ), array("integer", "string", "boolean") ) && $old_user_data->{$property_key} != $property_val ) {

				switch( $property_key ) {

					case "ID":
					case "user_login":
					case "user_nicename":
					case "user_registered":
					case "user_activation_key":
					case "user_status":
						// Because changes in any of the above is beyond the scope of this logging routine...
						break;
					case "user_pass":
						$changed_user_properties[$property_key] = "password";
						break;
					case "user_email":
						$changed_user_properties[$property_key] = "e-mail";
						break;
					case "user_url":
						$changed_user_properties[$property_key] = "homepage";
						break;
					case "display_name":
						$changed_user_properties[$property_key] = "display name";
						break;

				}

			}

		}

		foreach( $changed_user_properties as $property_key => $property_label ) {

			if ( $property_key != 'user_pass' ) {
				$log_message = "The " . $property_label . " setting for " . $updated_user->display_name . " (" . $updated_user->user_login . ") " .
							   "was changed from '" . $old_user_data->{$property_key} . "'' to '" . $updated_user->data->{$property_key} . "'";
			} else {
				$log_message = "The " . $property_label . " setting for " . $updated_user->display_name . " (" . $updated_user->user_login . ") " .
							   "was changed";
			}

			if ( $current_user instanceof WP_User ) {
				$log_message .= " by " . $current_user->display_name . " (" . $current_user->user_login . ")";
			}

			if ( is_multisite() && is_plugin_active_for_network( ACI_PLUGIN_BASENAME ) ) {
				$site_name = get_blog_details($site_id)->blogname;
				$log_message .= " on " . $site_name;
			}

			$log_message .= ".";

			AC_Inspector::log( $log_message, __CLASS__ );

		}

		return "";

	}

}

ACI_Routine_Log_User_Profile_Update::register();
