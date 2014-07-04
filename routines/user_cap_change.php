<?php

/*
 *	Logs if a user's roles and/or capabilities was changed
 */

class ACI_Log_User_Capability_Change {

	const LOG_LEVEL = "notice";

	const DESCRIPTION = "Logs every time a user's role or capabilities has been changed.";

	private static $_current_filter = "";

	private static $_user_meta_action_args = array(
		"add_user_meta" => array('object_id', 'meta_key', 'meta_value'),
		"update_user_meta" => array('meta_id', 'object_id', 'meta_key', 'meta_value'),
		"delete_user_meta" => array('meta_ids', 'object_id', 'meta_key', 'meta_value')
	);

	private static $_super_user_action_args = array(
		"grant_super_admin" => array('user_id'),
		"revoke_super_admin" => array('user_id')
	);

	private static $_super_user_cap_changes = array();

	public static function register() {

		$options = array( 'log_level' => self::LOG_LEVEL,
						  'description' => self::DESCRIPTION,
						  'site_specific_settings' => 0 );

		foreach(array_keys(self::$_user_meta_action_args) as $action) {
			aci_register_routine( __CLASS__, $options, $action, 10, count(self::$_user_meta_action_args[$action]) );
		}
		
		if ( is_multisite() && is_plugin_active_for_network( ACI_PLUGIN_BASENAME ) ) {
			foreach(array_keys(self::$_super_user_action_args) as $action) {
				aci_register_routine( __CLASS__, $options, $action, 10, count(self::$_super_user_action_args[$action]) );
			}
		}

	}

	public static function inspect() {

		self::$_current_filter = current_filter();

		if ( in_array( self::$_current_filter, array_keys( self::$_user_meta_action_args ) ) ) {

			$arg_list = func_get_args();

			foreach(self::$_user_meta_action_args[self::$_current_filter] as $arg_key => $var_name) {
				$$var_name = $arg_list[$arg_key];
			}

			if ( strpos( $meta_key, '_capabilities' ) ) {

				if ( !is_super_admin( $object_id ) ) {

					$new_caps = array_filter( (array) $meta_value );

					if ( "delete_user_meta" == self::$_current_filter || !empty( $new_caps ) ) {

						self::_log_capability_change( $object_id, $new_caps );

					}

				} else {

					self::_log_super_user_cap_change( $object_id );

				}

			}

		}

		if ( in_array( self::$_current_filter, array_keys( self::$_super_user_action_args ) ) ) {

			$arg_list = func_get_args();

			foreach(self::$_super_user_action_args[self::$_current_filter] as $arg_key => $var_name) {
				$$var_name = $arg_list[$arg_key];
			}

			self::_log_super_user_priv_change( $user_id );

		}

	}

	private static function _log_capability_change( $user_id, $new_caps = array() ) {

		global $wpdb;
		$cap_key = $wpdb->get_blog_prefix() . 'capabilities';

		$current_user = wp_get_current_user();
		$changed_user = get_user_by( 'id', $user_id );

		$old_caps = array_filter( (array) maybe_unserialize( $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s AND user_id = %d", $cap_key, $user_id ) ) ) );

		$added_caps = array_filter(array_diff_assoc($new_caps, $old_caps));
		$removed_caps = array_filter(array_diff_assoc($old_caps, $new_caps));

		if ( count($added_caps) > 0 ) {

        	$message = $changed_user->display_name. ' (' . $changed_user->user_login . ') was ';

        	// If caps is an associative array...
        	if (array_keys($added_caps) !== range(0, count($added_caps) - 1)) {
        		$privs_str = implode(', ', array_keys($added_caps));
        	} else {
        		$privs_str = implode(', ', $added_caps);
        	}

        	if ( count($added_caps) > 1 ) {
        		$privs_str = substr_replace($privs_str, ' and ', strrpos($privs_str, ', '), 2);
        	}

			$message .= 'given ' . $privs_str . ' privileges';

			if ( is_multisite() && is_plugin_active_for_network( ACI_PLUGIN_BASENAME ) ) {
				$message .= ' on ' . get_blog_details(get_current_blog_id())->blogname;
			}

			$message .= ' by ' . $current_user->display_name. ' (' . $current_user->user_login . ')';

			AC_Inspector::log($message, __CLASS__);

		}

        if ( count($removed_caps) > 0 ) {

        	$message = $changed_user->display_name. ' (' . $changed_user->user_login . ') was ';

        	// If caps is an associative array...
    		if (array_keys($removed_caps) !== range(0, count($removed_caps) - 1)) {
        		$privs_str = implode(', ', array_keys($removed_caps));
        	} else {
        		$privs_str = implode(', ', $removed_caps);
        	}

        	if ( count($removed_caps) > 1 ) {
        		$privs_str = substr_replace($privs_str, ' and ', strrpos($privs_str, ', '), 2);
        	}

			$message .= 'stripped of his/her ' . $privs_str . ' privileges';

			if ( is_multisite() && is_plugin_active_for_network( ACI_PLUGIN_BASENAME ) ) {
				$message .= ' on ' . get_blog_details(get_current_blog_id())->blogname;
			}

			$message .= ' by ' . $current_user->display_name. ' (' . $current_user->user_login . ')';

			AC_Inspector::log( $message, __CLASS__ );

        }

	}

	private static function _log_super_user_priv_change( $user_id ) {

		$current_user = wp_get_current_user();
		$changed_user = get_user_by( 'id', $user_id );

		switch( self::$_current_filter ) {

			case "grant_super_admin":
				$message = $changed_user->display_name. ' (' . $changed_user->user_login . ') was granted super user privileges by ' . $current_user->display_name. ' (' . $current_user->user_login . ')';
				break;

			case "revoke_super_admin":
				$message = $changed_user->display_name. ' (' . $changed_user->user_login . ') was stripped of his/her super user privileges by ' . $current_user->display_name. ' (' . $current_user->user_login . ')';
				break;

		}

		AC_Inspector::log( $message, __CLASS__ );

	}

	private static function _log_super_user_cap_change( $user_id ) {

		$current_user = wp_get_current_user();
		$changed_user = get_user_by( 'id', $user_id );

		if ( !is_array( self::$_super_user_cap_changes[$current_user->ID] ) ) {
			self::$_super_user_cap_changes[$current_user->ID] = array();
		}

		if (!in_array($changed_user->ID, self::$_super_user_cap_changes[$current_user->ID])) {

			$message = "Meaningless change of capabilities on super user " . $changed_user->display_name. ' (' . $changed_user->user_login . ') by ' . $current_user->display_name. ' (' . $current_user->user_login . ')';
			AC_Inspector::log( $message, __CLASS__ );

			self::$_super_user_cap_changes[$current_user->ID][] = $changed_user->ID;

		}

	}

}

ACI_Log_User_Capability_Change::register();

