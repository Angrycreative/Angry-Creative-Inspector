<?php

class ACI_Routine_Log_User_Registrations {

	const LOG_LEVEL = "notice";

	const DESCRIPTION = "Logs whenever a new user account is created.";

	public static function register() {

		$default_options = array( 'log_level' => self::LOG_LEVEL, 
								  'description' => self::DESCRIPTION );
		
		aci_register_routine( __CLASS__, $default_options, "user_register", 10, 1 );

	}

	public static function inspect( $user_id ) {

		$current_user = wp_get_current_user();
		$new_user = get_user_by( 'id', $user_id );

		if ( !( $current_user instanceof WP_User ) ) {
			return "";
		}

		if ( $current_user instanceof WP_User ) {
			$log_message = $new_user->display_name . " (" . $new_user->user_login . ") " .
						   "was registered as a new user by " . $current_user->display_name . " (" . $current_user->user_login . ").";
		} else {
			$log_message = $new_user->display_name . " (" . $new_user->user_login . ") " .
						   "registered as a new user.";
		}

		AC_Inspector::log( $log_message, __CLASS__ );

		return "";

	}

}

ACI_Routine_Log_User_Registrations::register();
