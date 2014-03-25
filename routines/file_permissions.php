<?php

/*
 * Checks wordpress file permissions
 */
function aci_check_wp_file_permissions() {

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
				AC_Inspector::log('Was able to create a file in `/' . $folder . '` despite DISALLOW_FILE_MODS being set to true. Check your file permissions.', __FUNCTION__);
			}

		} else {

			if(!$file_created){
				AC_Inspector::log('Was not able to create a file in `/' . $folder . '`. Check your file permissions.', __FUNCTION__);
			}

		}

	}

	return "";

}

$options = array('log_level' => 'warning');
aci_register_routine("aci_check_wp_file_permissions", $options);
