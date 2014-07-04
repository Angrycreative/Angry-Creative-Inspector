<?php 
/*
Class name: AC Inspector
Version: 0.5.0
Author: Sammy Nordström, Angry Creative AB
*/

if(!class_exists('AC_Inspector')) { 

	class AC_Inspector { 

		private static $_this;

		public static $log_path = "";
		public static $errors = array();
		public static $log_count = 0;

		private static $_default_log_path = "";
		private static $_log_levels = array();
		
		public function __construct() {

			if ( !isset( self::$_this ) ) {

				self::$_this = $this;

				self::$_default_log_path = ACI_PLUGIN_DIR . '/inspection.log';

				$this->_get_log_path();

				$this->_check_log_path();

				// Define log levels
				self::$_log_levels = array(
					'notice',
					'warning',
					'fatal',
					'ignore'
				);

				$this->_on_update();

				add_action( 'ac_inspection', function() {
					AC_Inspector::log("Inspection completed with " . AC_Inspector::$log_count . " remarks.");
				}, 999, 0 );

			}

		}

		static function this() {
		    return self::$_this;
		}

		/* Add Cron Job on activation, that test permissions etc. */
		public static function activate() { 

			if ( !wp_next_scheduled( 'ac_inspection' ) ) {

				wp_schedule_event( time(), 'daily', 'ac_inspection');

			}

		}

		/* Clear job on deactivation */
		public static function deactivate() { 

			wp_clear_scheduled_hook( 'ac_inspection');
		
		}

		/* Plugin update actions */
		private function _on_update() {

			$saved_version = self::get_option( __CLASS__.'_Version', ACI_PLUGIN_VERSION );

			if ( empty($saved_version) || version_compare($saved_version, ACI_PLUGIN_VERSION, '<') ) {

				self::deactivate();
				self::activate();

				self::update_option( __CLASS__.'_Version', ACI_PLUGIN_VERSION );

			}

		}

		public static function get_log_levels() {

			return self::$_log_levels;

		}

		public static function get_option( $name ) {

			if ( is_multisite() && is_plugin_active_for_network( ACI_PLUGIN_BASENAME ) ) {

				return get_site_option( $name );

			} else {

				return get_option( $name );

			}
			
		}

		public static function add_option( $name, $value ) {

			if ( is_multisite() && is_plugin_active_for_network( ACI_PLUGIN_BASENAME ) ) {

				return add_site_option( $name, $value );

			} else {

				return add_option( $name, $value );

			}

		}

		public static function update_option($name, $value) {

			if ( is_multisite() && is_plugin_active_for_network( ACI_PLUGIN_BASENAME ) ) {

				return update_site_option( $name, $value );

			} else {

				return update_option( $name, $value );

			}

		}

		private function _get_log_path() {

			self::$log_path = self::get_option('ac_inspector_log_path');

			if( empty(self::$log_path) ) {

				// For backwards compatibility with versions <= 0.1.1
				self::$log_path = self::get_option( 'log_path' );

				if( !empty(self::$log_path) ) {

					// Set new option variable name
					self::update_option( 'ac_inspector_log_path', self::$log_path );

				} else {

					self::$log_path = self::$_default_log_path;
					self::update_option( 'ac_inspector_log_path', self::$_default_log_path );

				}
				
			}

		}

		private function _check_log_path() {

			if ( file_exists( self::$log_path ) ) {

				if ( !is_writable( self::$log_path ) ) {

					self::$errors[] = 'Log file exists but is not writable.';

					return false;

				}

			} else {

				if ( !is_dir( dirname(self::$log_path) ) ) {

					self::$errors[] = 'Invalid log file directory.';

				} else {

					$file_handle = @fopen( self::$log_path, 'w' );

				    if ( !$file_handle ) {

	        			self::$errors[] = 'Unable to create log file.';

	        			return false;

	    			}

	    		}

			}

			return true;

		}

		public static function clear_log() {

			if ( file_exists( self::$log_path ) ) {

				if ( is_writable( self::$log_path ) ) {

					unlink( self::$log_path );
					self::_check_log_path();

					global $current_user;
					get_currentuserinfo();

					self::log('Log cleared by ' . $current_user->display_name);

					return true;

				}

			}

			return false;

		}

		public function inspect() {

			if ( !did_action( 'ac_inspection' ) ) {

				do_action( 'ac_inspection' );

			}

		}

		/* 
			Main log function that does the actual output
		*/
		public static function log($message, $routine = '', $site_id = '') {

			$log_level = '';

			if (!empty($routine)) {

				$routine_options = ACI_Routine_Handler::get_options($routine);

				if (is_array($routine_options)) {
					if ( $routine_options['site_specific_settings'] && is_multisite() && is_plugin_active_for_network( ACI_PLUGIN_BASENAME ) ) {
						$site_id = ( is_numeric($site_id) ) ? $site_id : get_current_blog_id();
						if ( is_array($routine_options[$site_id]) && isset($routine_options[$site_id]['log_level']) ) {
							$log_level = $routine_options[$site_id]['log_level'];
						} else if ( is_array($routine_options[1]) && isset($routine_options[1]['log_level']) ) {
							$log_level = $routine_options[1]['log_level'];
						}
					}
					if ( empty($log_level) && isset($routine_options['log_level']) ) {
						$log_level = $routine_options['log_level'];
					}
				}

			}

			if (!empty($message) && $log_level != 'ignore') {

				// Fallback to notice if no default or user supplied log level
				if (empty($log_level) || !in_array($log_level, self::$_log_levels)) {
					$log_level = "notice";
				}

				$output = '['.date("d M, Y H:i:s").'] ['.__CLASS__.'] [ ' .strtoupper($log_level). ' ] - ';

				if (is_array($message) || is_object($message)) {

	            	error_log( $output, 3, self::$log_path );
					error_log( print_r( $message, true ) . "\n", 3, self::$log_path ); 

	        	} else {

					$message = $output . $message;
	            	error_log( $message . "\n", 3, self::$log_path ); 

	        	}

	        	self::$log_count++;

	        }

		}

	} 

} 
