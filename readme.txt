=== Angry Creative Inspector ===
Contributors: ac-robin, samface, angrycreative
Tags: inspect, inspection, monitor, monitoring, log, logging, check, checking, validate, validation, permissions, install, installation, wp-cli
Requires at least: 4.0
Tested up to: 4.2.4
Stable tag: 0.8.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Inspects, logs and with the aid of WP-CLI, it may even repair possible issues with your WordPress installation.

== Description ==

The *Angry Creative Inspector*, or Angry Inspector for short, will weigh, measure and find possible issues with your WordPress installation. This plugin is especially recommended to maintainers of WordPress installations that needs to continously monitor the health of their sites. Got WP-CLI? Then you may even be able to repair what's wrong with your WordPress. Read on to learn more.

The inspector has two basic types of inspection routines; regularly repeating inspections run once every 24 hours and inspections triggered by a WordPress hook or user action. Currently, the following regularly running inspection routines are supported out of the box:

* File permissions check - You didn't do a chmod 777 on all files, right?
* Git modifications check - Is your WordPress revisioned by Git? Then we log uncomitted file changes.
* Database collation check - Does your tables have the proper character set and collations?
* Site visibility check - Is your website blocking search engines?

Then there's the following WordPress hook or otherwise user action triggered routines:

* Plugin enabling/disabling - Who disabled or enabled that plugin?
* User registrations - Logs all user registrations. No more, no less.
* User profile updates - Logs all user profile updates, even password changes.
* User capabilitiy changes - Who promoted or demoted that user?
* Site registrations - Who created that site in your site network?
* Site enabling/disabling - Who disabled or enabled that site in your site network?
* Javascript error logging - Keep track of javascript errors, even the ones that appears in your visitor's browsers.

A little configuration is needed to take full advantage of the file permissions and site visibility routines, see installation instructions.

= Doing inspections and viewing inspection logs =

Login to your WordPress site with an administrator account and navigate to the Angry Inspector page under the 'Settings/AC Inspector' menu in the WP admin panel (in network admin if multisite). In here you should see the inspection log and may trigger the scheduled inspection routines manually by clicking the "Inspect now" button. Also take note of the "Inspection routines" and "Action routines" tabs where you among other things may change the log level for each routine. Set log level to 'ignore' to completely disable a routine.

= Inspect and repair using WP-CLI =

First make sure you have WP-CLI installed, see http://wp-cli.org for instructions. Then navigate your commandline shell to your WordPress install directory and issue the following command:

	wp angry-inspector inspect

You may also call a specific inspection routine like this (use all lowercase name of class or function without "ACI_Routine_"-prefix and replace underscores with dashes):

	wp angry-inspector inspect file-permissions

Any inspection remarks while inspecting with WP-CLI will cause the inspector to look for a possible repair method and prompt you with the question wether you would like to make an attempt at repairing if one is found. Want to skip inspection and go directly for repairing? Here's how:

	wp angry-inspector repair

To call a repair method for a specific inspection routine:

	wp angry-inspector repair site-visibility

Please note that for utilizing a repair method like the one for file permissions, you need to run the command as super-user (root) and add --allow-root to your WP-CLI commands, like this:

	sudo wp angry-inspector repair file-permissions --allow-root

That's all there is to it, really. Note that the given log_level option is just a default and may be overridden in the 'Settings/AC Inspector' menu in the WP admin panel (in network admin if multisite) like all the other registered inspection routines.

= Creating your own inspection routines =

Are you skilled in the arts of WordPress development and have a need for a check not covered by the above? Then you should extend the inspector with your own inspection routine! It's really simple. Here's an example you can put in your theme's functions.php or in a plugin (after plugins loaded):

	if ( function_exists( 'aci_register_routine' ) ) {

		$options = array( 'description' => 'My awesome inspection routine!',
						  'log_level' => 'warning' ); // Possible values: notice, warning, fatal, ignore

		$action = 'ac_inspection'; // Default inspection action run once every 24 hrs

		aci_register_routine( 'my_inspection_routine', $options, $action );

		function my_inspection_routine() {

			$inspection_passed = false;

			// Put your inspection code here,
			// then log your results like so:

			if ( !$inspection_passed ) {
				AC_Inspector::log( 'Your WordPress-install did not pass your inspection routine.', __FUNCTION__ );
			}

			// No need to return anything...
			return;

		}

	}

For a bit more advanced developers, you may also register a class as an inspection routine. The syntax and function used is the same, it's just a matter of passing the class name instead of a function name. The inspector will by default look for a class function called 'inspect' to register as inspection method, otherwise you may add 'inspection_method' => 'your_inspection_function' to the options array to use any class function you like as your inspection method. 

The reason why you would want to register a class instead of just a function is that it gives you the ability to extend the settings for your routine beyond just the log level and maybe even add a repair method to call if the inspection fails (WP-CLI only).

First off, here's how you extend the settings for your routine (inside your inspection routine class):

	public static function settings_field( $options, $args = array() ) {

		$routine = $args["routine"];

		/**
		*
		*	Input field name convention: aci_options[<?php echo $routine; ?>][field_name]
		*	Input field id convention: aci_options_<?php echo $routine; ?>_field_name 
		*
		**/

		?>

		<tr valign="top">
		    <td scope="row" valign="top" style="vertical-align: top;">Example setting field</td>
		    <td>
	    		<p class="description">Here be extra settings field :)</p>
			</td>
		</tr>

		<?php

	}

	public static function settings( $options ) {

		// Here be code that parses and validates submitted values

		return $options;

	}

Then when you register your routine:

	$default_options = array( 'log_level' => 'warning',
							  'description' => 'My awesome inspection routine!' );

	aci_register_routine( 'my_inspection_routine_class', $default_options );

	// Optional extra settings fields
	add_action( 'my_inspection_routine_class_settings_field', array( 'my_inspection_routine_class', 'settings_field' ), 10, 2 );
	add_filter( 'my_inspection_routine_class_settings',  array( 'my_inspection_routine_class', 'settings' ), 10, 1 );

Your extra settings fields should now be visible in either the Inspection or the Action routines tab under the 'Settings/AC Inspector' menu in the WP admin panel (in network admin if multisite).

For a complete example routine class, checkout the routines/wp_mail.php file in the plugin directory.

As for registering a repair function, it works the same as when registering a class with an inspection method. The inspector looks for a class function called 'repair' by default. Override with any class function you like by defining 'repair_method' => 'your_repair_function' in the options array. However, a repair method will never be called automatically. You need WP-CLI ( http://wp-cli.org/ ) installed and issue the appropriate commands, see "Inspect and repair using WP-CLI" above.

Happy... no wait, angry inspecting! :)

== Installation ==

1. Download, unzip and upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress (activate for network if multisite)
3. Make sure the right log file path is set under the 'Settings/AC Inspector' menu in the WP admin panel (in network admin if multisite)

To get the file permissions and site visibility routines working properly, edit wp-config.php and add the following (before "That's all, stop editing!"):

	//define("FS_METHOD", "direct"); // Recommended: Enable this for dev/stage-environments
	define("DISALLOW_FILE_MODS", true); // Recommended: Enable this for production-evironments

	define("HTTPD_USER", "www-data"); // The user of your HTTP-daemon (Apache/Nginx/Lighttpd/etc)
	define("FS_USER", "my-user"); // The username/accountname you login and maintain your WordPress install with
	define("SITE_RELEASE_TIER", "production"); // Possible values: 'local', 'development', 'integration', 'test', 'stage', 'production'

Setting FS_METHOD to 'direct' will ensure write permissions in your entire WordPress-directory for FS_USER as well as HTTPD_USER, while enabling DISALLOW_FILE_MODS will restrict the HTTPD_USER to only having write permissions as configured under the 'Settings/AC Inspector' menu in the WP admin panel (in network admin if multisite). Default allowed dirs:

	wp-content/uploads/*
	wp-content/blogs.dir/*
	wp-content/cache/*
	wp-content/avatars/*
	wp-content/*/LC_MESSAGES/*

Setting SITE_RELEASE_TIER helps the inspector determine wether the site should allow search engine indexing or not. Forgetting to block search engines in a development or staging site can have terrible results on your search engine ranking.

== Changelog ==

= 0.8.x =
* WP CLI interactivity for running possible repair method when inspection yields errors
* Completely rewritten and much improved file permissions inspection routine
* Added the abilitiy to define HTTPD_USER (http daemon user name) in wp-config.php
* Added the abilitiy to define FS_USER (file owner user name) in wp-config.php
* Added repair method to file permissions routine using HTTPD_USER and FS_USER for setting ownerships
* Added the abilitiy to define SITE_RELEASE_TIER (local/development/test/integration/stage/production) in wp-config.php
* Added repair method to site visibility routine using SITE_RELEASE_TIER for making the site public or private
* Added the abiltity to define HIDDEN_SITES to prevent specific sites from being made public by the site visibility repair method
* Finally added a bit of useful documentation and user instructions to this readme, please start asking questions if you want an FAQ :)

= 0.7.x =
* Added repair method support for inspection routines
* Added WP CLI support for operating the inspector via command line
* Added inspect command for doing inspections via command line
* Added repair command for doing repairs via command line
* Added repair method to mysql database collations routine

= 0.6.x =
* New routine for checking mysql database collations
* New routine for logging user profile updates
* New routine for logging new user registrations
* New routine for logging new site registrations
* Fixed a bug in the routine for javascript errors that prevented browser debugging
* Added function for downloading log file
* Better log file display in wp-admin

= 0.5.x =
* New routine for logging javascript errors
* New routine for logging user capability changes
* Revised and much improved routine for checking file write permissions
* Added the abilitity to make site specific settings on multisite installations
* Improved administration interface
* Various bugfixes and code refactoring

= 0.4.x =
* New routine with git support for checking file system changes
* Improved pluggability and custom routines support
* Added version-awareness and re-activation on update

= 0.3.x =
* Completely rewritten sources abstracting settings and inspection routines
* Possible to override default log levels on inspection routines
* Settings page in network admin if multisite

= 0.2.x =
* Improved multisite support and various bugfixes
* Possible to manually trigger inspection
* Admin notice on settings errors
* Revised log format

= 0.1.x =
* Basic logging functionality
* Basic inspection routines
* Possible to change log file path
