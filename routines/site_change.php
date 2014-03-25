<?php

/*
 *	Logs if a site has been activated/deactivated
 */
function aci_log_site_change($site){

	$user = wp_get_current_user();
	$usermsg = ($user instanceof WP_User) ? ' (user: '.$user->user_login.')' : '';
	$status = (current_filter() == 'activate_blog') ? 'activated' : 'deactivated';
	$message = 'Site ' . get_blog_details($site)->blogname . ' (id: '.$site . ')' . ' was ' . $status . $usermsg . '\n';

	AC_Inspector::log($message, __FUNCTION__);

	return "";

}

$options = array('log_level' => 'warning');
aci_register_routine("aci_log_site_change", $options, "activate_blog");
aci_register_routine("aci_log_site_change", $options, "deactivate_blog");
