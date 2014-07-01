<?php

/*
 * Checks wordpress file permissions
 */
function aci_check_wp_file_permissions( $folders2check = array() ) {

	if ( !is_array($folders2check) || empty($folders2check) ) {

		$folders2check = array(
			'/',
			'wp-admin/*',
			'wp-content/',
			'wp-content/plugins/*',
			'wp-content/themes/*',
			'wp-content/languages/*',
			'wp-includes/*'
		);

	}

	foreach($folders2check as $folder) {

		$folder_base = trim( str_replace( '/*', '', str_replace('//', '/', str_replace( trim( ABSPATH, '/' ) , '', $folder ) ) ), '/' );

		$file_path = ABSPATH.$folder_base.'/.ac_inspector_testfile';

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
				AC_Inspector::log('Was able to create a file in `' . $folder_base . '` despite DISALLOW_FILE_MODS being set to true. Check your file permissions.', __FUNCTION__);
			}

		} else {

			if(!$file_created){
				AC_Inspector::log('Was not able to create a file in `' . $folder_base . '`. Check your file permissions.', __FUNCTION__);
			}

		}

		if ( $file_created && !empty($folder_base) && substr($folder, -2) == "/*" ) {

			$subfolders = glob(ABSPATH.$folder_base."/*", GLOB_ONLYDIR);

			if ( is_array($subfolders) && !empty($subfolders) ) {

				foreach(array_keys($subfolders) as $sf_key) {
					$subfolders[$sf_key] = trim($subfolders[$sf_key], '/') . '/*';
					if ( $f2c_key = array_search( $subfolders[$sf_key], $folders2check ) ) {
						unset($subfolders[$f2c_key]);
					}
				}

				aci_check_wp_file_permissions( $subfolders );

			}

		}

	}

	return "";

}

$options = array('log_level' => 'warning');
aci_register_routine("aci_check_wp_file_permissions", $options);
