=== Plugin Name ===
Contributors: ac-robin, samface, angrycreative 
Tags: inspect, inspection, monitor, monitoring, log, logging, check, checking, validate, validation, permissions, install, installation
Requires at least: 3.0.1
Tested up to: 3.8.1
Stable tag: 0.4.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Inspects and logs possible issues with your Wordpress installation.

== Description ==

Inspects and logs possible issues with your Wordpress installation.

== Installation ==

1. Download, unzip and upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress (activate for network if multisite)
3. Make sure the right log file path is set under the 'Settings/AC Inspector' menu in Wordpress

== Changelog ==

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
