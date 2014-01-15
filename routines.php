<?php

$options = array('log_level' => 'fatal');
aci_register_routine("aci_check_wp_upload_permissions", $options);

$options = array('log_level' => 'warning');
aci_register_routine("aci_check_wp_file_permissions", $options);

$options = array('log_level' => 'warning');
aci_register_routine("aci_check_site_visibility", $options);

$options = array('log_level' => 'warning');
aci_register_routine("aci_log_plugin_change", $options, "activate_plugin");
aci_register_routine("aci_log_plugin_change", $options, "deactivate_plugin");

$options = array('log_level' => 'warning');
aci_register_routine("aci_log_site_change", $options, "activate_blog");
aci_register_routine("aci_log_site_change", $options, "deactivate_blog");

$options = array('log_level' => 'warning');
aci_register_routine("aci_log_wp_mail", $options, "wp_mail");

/*
	Checks uploads path permissions
*/
function aci_check_wp_upload_permissions() {

	$upload_dir = wp_upload_dir();
	$file_path = $upload_dir['basedir'] . "/.ac_inspector_testfile";

	try {

		$file_handle = @fopen($file_path, 'w');

	    if ( ! $file_handle ) {

			throw new Exception("Unable to write files in /uploads, check your permissions!");
		
		} else {

			fclose($file_handle);
			unlink($file_path);

		}

	} catch ( Exception $e ) {

		return AC_Inspector::log($e->getMessage(), __FUNCTION__);

	}

	return "";

}

/*
	Checks wordpress file permissions
*/
function aci_check_wp_file_permissions(){

	$folders2check = array(
		'',
		'wp-admin',
		'wp-content',
		'wp-content/plugins',
		'wp-content/themes',
		'wp-includes'
	);

	foreach($folders2check as $folder) {

		$file_path = ABSPATH.$folder.'/.ac_inspector_testfile';

		$file_handle = @fopen($file_path, 'w');

	    if ( !$file_handle ) {

	    	// Could not open file for writing
			$file_created = false;

		} else {

			// Test was successful, let's cleanup before returning true...
			fclose($file_handle);
			unlink($file_path);

			$file_created = true;

		}
		
		if(defined('DISALLOW_FILE_MODS') && true == DISALLOW_FILE_MODS) {	

			if($file_created) {
				return AC_Inspector::log('Was able to create a file in `/' . $folder . '` despite DISALLOW_FILE_MODS being set to true. Check your file permissions.', __FUNCTION__);
			}

		} else {

			if(!$file_created){
				return AC_Inspector::log('Was not able to create a file in `/' . $folder . '`. Check your file permissions.', __FUNCTION__);
			}

		}

	}

	return "";

}


/*
	Checks site visibility
*/
function aci_check_site_visibility() {

	if ( is_multisite() && is_plugin_active_for_network( ACI_PLUGIN_BASENAME ) ) {

		global $wpdb;
		$site_blog_ids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM ".$wpdb->prefix."blogs where blog_id > %d", 1));

		if (is_array($site_blog_ids)) {
			foreach( $site_blog_ids AS $site_blog_id ) {

				if (intval($site_blog_id) > 0) {

					$visible = get_blog_details( $site_blog_id )->public;

					if ( !$visible ) {

						return AC_Inspector::log('Site '.$site_blog_id.' is not visible to search engines.', __FUNCTION__);

					}

				}

			}
		}

	} else {

		$visible = get_option('blog_public');

		if ( !$visible ) {

			return AC_Inspector::log('The site is not visible to search engines.', __FUNCTION__);

		}

	}

	return "";

}

/*
	Logs if a plugin has been activated/deactivated
*/
function aci_log_plugin_change($plugin){

	$user = wp_get_current_user();
	$site = (is_multisite()) ? ' on "' . get_blog_details(get_current_blog_id())->blogname . '"' : '';
	$usermsg = ($user instanceof WP_User) ? ' (user: '.$user->user_login.')' : '';
	$status = (current_filter() == 'activate_plugin') ? 'activated' : 'deactivated';

	$plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' .$plugin);

	$message = 'Plugin "'.$plugin_data['Name']. '" was ' . $status . $usermsg . $site;

	AC_Inspector::log($message, __FUNCTION__);

}

/*
	Logs if a site has been activated/deactivated
*/
function aci_log_site_change($site){

	$user = wp_get_current_user();
	$usermsg = ($user instanceof WP_User) ? ' (user: '.$user->user_login.')' : '';
	$status = (current_filter() == 'activate_blog') ? 'activated' : 'deactivated';
	$message = 'Site ' . get_blog_details($site)->blogname . ' (id: '.$site . ')' . ' was ' . $status . $usermsg . '\n';

	AC_Inspector::log($message, __FUNCTION__);

}

/*
	Logs when wp-mail is called
*/
function aci_log_wp_mail($mail) {

	// AC_Inspector::log($mail, __FUNCTION__);

}


