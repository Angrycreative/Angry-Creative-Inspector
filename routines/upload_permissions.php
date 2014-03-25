<?php

/*
 * Checks uploads path permissions
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

		AC_Inspector::log($e->getMessage(), __FUNCTION__);

	}

	return "";

}

$options = array('log_level' => 'fatal');
aci_register_routine("aci_check_wp_upload_permissions", $options);