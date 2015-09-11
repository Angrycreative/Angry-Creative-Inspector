<?php
/*
Plugin Name: Angry Creative Inspector
Plugin URI: http://angrycreative.se
Description: Inspects and logs possible issues with your Wordpress installation.
Version: 1.0
Author: Robin Björklund, Sammy Nordström, Angry Creative AB
*/

define( 'ACI_PLUGIN_VERSION', '1.0' );

define( 'ACI_PLUGIN_NAME', 'Angry Creative Inspector' );
define( 'ACI_PLUGIN_SHORTNAME', 'AC Inspector' );
define( 'ACI_PLUGIN_AUTHOR_CO', 'Angry Creative AB' );
define( 'ACI_PLUGIN_AUTHOR_CO_URI', 'http://angrycreative.se' );

define( 'ACI_PLUGIN_SLUG', 'ac-inspector' );
define( 'ACI_PLUGIN_TEXTDOMAIN', 'ac_inspector' );

define( 'ACI_PLUGIN_DIR', dirname( __FILE__ ) );
define( 'ACI_PLUGIN_FILE',  __FILE__ );
define( 'ACI_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

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
foreach ( glob( ACI_PLUGIN_DIR."/routines/*.php" ) as $routine_file ) {
    include $routine_file;
}

// If in admin, also load settings class
if (is_admin()) {
	require( ACI_PLUGIN_DIR . "/classes/settings.php" );
}

if ( class_exists( 'AC_Inspector' ) ) {

	// Installation and uninstallation hooks
	register_activation_hook( ACI_PLUGIN_FILE, array('AC_Inspector', 'schedule_inspections') );
	register_deactivation_hook( ACI_PLUGIN_FILE, array('AC_Inspector', 'unschedule_inspections') );

	if (is_admin()) {
		$ac_inspector = new ACI_Settings();
	} else {
		$ac_inspector = new AC_Inspector();
		if ( defined('WP_CLI') && WP_CLI ) {
		    require_once( dirname( __FILE__ ) . '/classes/wp_cli.php' );
		}
	}

}
