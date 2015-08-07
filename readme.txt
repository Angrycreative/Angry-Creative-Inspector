=== Angry Creative Inspector ===
Contributors: ac-robin, samface, angrycreative
Tags: inspect, inspection, monitor, monitoring, log, logging, check, checking, validate, validation, permissions, install, installation, wp-cli
Requires at least: 4.0
Tested up to: 4.2.4
Stable tag: 0.8.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Inspects, logs and with the aid of WP-CLI it may even repair possible issues with your Wordpress installation.

== Description ==

Inspects, logs and with the aid of WP-CLI it may even repair possible issues with your Wordpress installation.

TO-DO: Documentation :)

== Installation ==

1. Download, unzip and upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress (activate for network if multisite)
3. Make sure the right log file path is set under the 'Settings/AC Inspector' menu in Wordpress

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
