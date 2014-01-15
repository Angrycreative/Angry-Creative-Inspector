<?php
/*
Plugin Name: Angry Creative Inspector
Plugin URI: http://angrycreative.se
Description: Inspects and logs possible issues with your Wordpress installation.
Version: 0.3.2
Author: Robin Björklund, Sammy Nordström, Angry Creative AB
*/

define('ACI_PLUGIN_DIR', dirname( __FILE__ ) );
define('ACI_PLUGIN_FILE',  __FILE__ );
define('ACI_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Needed for multisite support
if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
    require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
}

// Load required classes
require( ACI_PLUGIN_DIR . "/classes/inspector.php" );
require( ACI_PLUGIN_DIR . "/classes/routine_handler.php" );

// Load required functions
require( ACI_PLUGIN_DIR . "/functions.php" );

// Load inspection routines
require( ACI_PLUGIN_DIR . "/routines.php" );

// If in admin, also load settings class
if (is_admin()) {
	require( ACI_PLUGIN_DIR . "/classes/settings.php" );
}

if ( class_exists( 'AC_Inspector' ) ) {

	// Installation and uninstallation hooks
	register_activation_hook( ACI_PLUGIN_FILE, array('AC_Inspector', 'activate') );
	register_deactivation_hook( ACI_PLUGIN_FILE, array('AC_Inspector', 'deactivate') );

	$ac_inspector = new AC_Inspector();

	if (is_admin()) {
		$ac_inspector_settings = new ACI_Settings();
	}

}
