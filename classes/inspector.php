<?php 
/*
Class name: AC Inspector
Version: 0.6
Author: Sammy Nordström, Angry Creative AB
*/

if(!class_exists('AC_Inspector')) { 

	class AC_Inspector { 

		private static $_this;

		public static $log_path = "";
		public static $errors = array();
		public static $log_count = 0;
		public static $success_count = 0;
		public static $error_count = 0;

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

		public static function download_log() {
			if ( file_exists( self::$log_path ) ) {
				header( "Content-type: application/x-msdownload", true, 200 );
				header( "Content-Disposition: attachment; filename=ac_inspection.log" );
				header( "Pragma: no-cache" );
				header( "Expires: 0" );
				echo file_get_contents( self::$log_path );
				exit();
			} else {
				self::log( 'Failed to download log file: File does not exist.' );
			}
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

		public function increment_success_count() {

			self::$success_count++;

		}

		public function increment_error_count() {

			self::$error_count++;
			
		}

		public function get_success_count() {

			return self::$success_count;

		}

		public function get_error_count() {

			return self::$error_count;

		}

		/* 
			Main log function that does the actual output
		*/
		public static function log($message, $routine = '', $args = array() ) {

			if ( !is_array( $args ) && is_numeric( $args ) ) {
				// For backwards compatibility...
				$args = array( 'site_id' => $args );
			}

			$default_args = array(
				'site_id' => get_current_blog_id(),
				'site_specific_settings' => false,
				'log_level' => 'notice',
				'success' => false,
				'error' => false
			);

			if (!empty($routine)) {

				$routine_options = ACI_Routine_Handler::get_options($routine);

				$routine_args = wp_parse_args( $routine_options, $default_args );
				$args = wp_parse_args( $args, $routine_args );

				if ( is_array( $args ) ) {
					if ( $args['site_specific_settings'] && is_multisite() && is_plugin_active_for_network( ACI_PLUGIN_BASENAME ) ) {
						$site_id = ( is_numeric($site_id) ) ? $site_id : get_current_blog_id();
						if ( is_array($args[$site_id]) && isset($args[$site_id]['log_level']) ) {
							$log_level = $args[$site_id]['log_level'];
						} else if ( is_array($args[1]) && isset($args[1]['log_level']) ) {
							$log_level = $args[1]['log_level'];
						}
					}
					if ( empty($log_level) && isset($args['log_level']) ) {
						$log_level = $args['log_level'];
					}
				}

			} else {

				$args = wp_parse_args( $args, $default_args );

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

					if ( defined('WP_CLI') && WP_CLI ) {
						echo $output . "\n";
						print_r( $message );
						echo "\n";
					}

	        	} else {

					$message = $output . $message;
	            	error_log( $message . "\n", 3, self::$log_path ); 

	            	if ( defined('WP_CLI') && WP_CLI ) {
						echo $message . "\n";
					}

	        	}

	        	self::$log_count++;

	        	if ( $args['error'] ) {
	        		self::$error_count++;
	        	}

	        	if ( $args['success'] ) {
	        		self::$success_count++;
	        	}

	        }

		}

	} 

} 
