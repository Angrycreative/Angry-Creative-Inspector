<?php

/*
 *	Logs if a plugin has been activated/deactivated
 */
function aci_log_plugin_change($plugin){

	$user = wp_get_current_user();
	$site = (is_multisite()) ? ' on "' . get_blog_details(get_current_blog_id())->blogname . '"' : '';
	$usermsg = ($user instanceof WP_User) ? ' (user: '.$user->user_login.')' : '';
	$status = (current_filter() == 'activate_plugin') ? 'activated' : 'deactivated';

	$plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' .$plugin);

	$message = 'Plugin "'.$plugin_data['Name']. '" was ' . $status . $usermsg . $site;

	AC_Inspector::log($message, __FUNCTION__);

	return "";

}

$options = array('log_level' => 'warning');
aci_register_routine("aci_log_plugin_change", $options, "activate_plugin");
aci_register_routine("aci_log_plugin_change", $options, "deactivate_plugin");
