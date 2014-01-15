<?php
/*
Plugin Name: Angry Creative Inspector
Plugin URI: http://angrycreative.se
Description: Inspects and logs possible issues with your Wordpress installation.
Version: 0.3
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
require( dirname( __FILE__ ) . "/classes/inspector.php" );
require( dirname( __FILE__ ) . "/classes/routine_handler.php" );

// Load required functions
require( dirname( __FILE__ ) . "/functions.php" );

// Load inspection routines
require( dirname( __FILE__ ) . "/routines.php" );

// If in admin, also load settings class
if (is_admin()) {
	require( dirname( __FILE__ ) . "/classes/settings.php" );
}

if ( class_exists( 'AC_Inspector' ) ) {

	// Installation and uninstallation hooks
	register_activation_hook(__FILE__, array('AC_Inspector', 'activate'));
	register_deactivation_hook(__FILE__, array('AC_Inspector', 'deactivate'));

	$ac_inspector = new AC_Inspector();

	if (is_admin()) {
		$ac_inspector_settings = new ACI_Settings();
	}

}
