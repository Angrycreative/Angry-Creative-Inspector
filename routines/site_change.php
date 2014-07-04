<?php

/*
 *	Logs if a site has been activated/deactivated
 */

class ACI_Routine_Log_Site_Change {

	const LOG_LEVEL = "warning";

	const DESCRIPTION = "Logs if a site in the network has been activated or deactivated.";

	public static function register() {

		$default_options = array( 'log_level' => self::LOG_LEVEL, 
								  'description' => self::DESCRIPTION );
		
		aci_register_routine( __CLASS__, $default_options, "activate_blog" );
		aci_register_routine( __CLASS__, $default_options, "deactivate_blog" );

	}

	public static function inspect() {

		$user = wp_get_current_user();
		$usermsg = ($user instanceof WP_User) ? ' (user: '.$user->user_login.')' : '';
		$status = (current_filter() == 'activate_blog') ? 'activated' : 'deactivated';
		$message = 'Site ' . get_blog_details($site)->blogname . ' (id: '.$site . ')' . ' was ' . $status . $usermsg . '\n';

		AC_Inspector::log( $message, __CLASS__ );

		return "";

	}

}

ACI_Routine_Log_Site_Change::register();
