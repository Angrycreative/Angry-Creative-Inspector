<?php
/*
Class name: ACI Routine Handler
Version: 0.3.1
Depends: AC Inspector 0.5.x
Author: Sammy Nordström, Angry Creative AB
*/

if ( class_exists('AC_Inspector') && !class_exists('ACI_Routine_Handler') ) { 

	class ACI_Routine_Handler {

		private static $routine_events = array();

		public static function routine_options_key($routine) {

			if (empty($routine)) {
				return false;
			}

			$routine_option_key = $routine . "_options";

			$prefix = substr($routine, 0, 4);
			if ($prefix != "aci_") {
				$routine_option_key = "aci_" . $routine_option_key;
			}

			return $routine_option_key;

		}

		public static function get_inspection_method( $routine, $options = array() ) {

			if ( empty( $routine ) ) {
				return false;
			}

			if ( !is_array( $options ) || empty( $options ) ) {

				$options = self::get_options( $routine );

				if ( !is_array( $options ) ) {
					$options = array();
				}

			}

			if ( class_exists( $routine ) ) {

				if ( !empty( $options['inspection_method'] ) && method_exists( $routine, $options['inspection_method'] ) ) {

					return array( $routine, $options['inspection_method'] );

				} else if ( method_exists( $routine, 'inspect' ) ) {

					return array( $routine, 'inspect' );

				}

			} 

			if ( !empty( $options['inspection_method'] ) && function_exists( $options['inspection_method'] ) ) {

				return $options['inspection_method'];

			} else if ( function_exists( $routine ) ) {

				return $routine;

			}

			return false;

		}

		public static function set_options($routine, $args = array()) {

			if (empty($routine)) {
				return false;
			}

			$options_key = self::routine_options_key($routine);

			return AC_Inspector::update_option($options_key, $args);
			
		}

		public static function get_options( $routine ) {

			if ( empty( $routine ) ) {
				return false;
			}

			$options_key = self::routine_options_key( $routine );

			$options = AC_Inspector::get_option( $options_key );

			if ( !empty( $options['site_specific_settings'] ) && is_multisite() && is_plugin_active_for_network( ACI_PLUGIN_BASENAME ) ) {

				global $wpdb;
				$site_blog_ids = $wpdb->get_col( "SELECT blog_id FROM ".$wpdb->prefix."blogs" );

				if ( is_array( $site_blog_ids ) ) {

					$global_opt_keys = array_keys( $options );

					foreach( $site_blog_ids AS $site_blog_id ) {

						if ( !is_array( $options[$site_blog_id] ) ) {
							$options[$site_blog_id] = array();
						}

						foreach( $global_opt_keys as $global_opt_key ) {

							if ( !is_numeric( $global_opt_key ) && $global_opt_key != 'site_specific_settings' && !isset( $options[$site_blog_id][$global_opt_key] ) ) {
								$options[$site_blog_id][$global_opt_key] = $options[$global_opt_key];
							}

						}
					}

				}

			}

			return $options;

		}

		public static function remove_options($routine) {

			if (empty($routine)) {
				return false;
			}

			$options_key = self::routine_options_key($routine);

		}

		public static function add( $routine, $options = array(), $action = "ac_inspection", $priority = 10, $accepted_args = 1 ) {

			$inspection_method = self::get_inspection_method( $routine, $options );

			if ( empty( $inspection_method ) ) {
				return false;
			}

			if ( empty( $action ) ) {
				$action = "ac_inspection";
			}

			if ( !array_key_exists( $routine, self::$routine_events ) || !is_array( self::$routine_events[$routine] ) ) {
				self::$routine_events[$routine] = array();
			}

			if ( in_array( $action, self::$routine_events[$routine] ) ) {
				return false;
			}

			self::$routine_events[$routine][] = $action;

			if ( has_action( $action, $inspection_method ) === $priority ) {
				return false;
			}

			$saved_options = self::get_options( $routine );

			if ( isset( $saved_options['description'] ) && ( !isset($options['description']) || $saved_options['description'] != $options['description'] ) ) {
				unset( $saved_options['description'] );
			}

			if ( !empty( $options ) && is_array( $options ) ) {
				if ( is_array( $saved_options ) ) {
					foreach( $saved_options as $opt_key => $saved_val ) {
						if ( !isset( $options[$opt_key] ) || $options[$opt_key] != $saved_val ) {
							$options[$opt_key] = $saved_val;
						}
					}
				}
				if ( !is_array( $saved_options ) || ( count( $options ) != count( $saved_options ) ) ) {
					self::set_options( $routine, $options );
				}
			}

			if ( !empty( $options['site_specific_settings'] ) && is_multisite() && is_plugin_active_for_network( ACI_PLUGIN_BASENAME ) ) {
				$current_site_id = get_current_blog_id();
				if ( $options[$current_site_id]['log_level'] == 'ignore' ) {
					return true;
				}
			} else if ( $options['log_level'] == 'ignore' ) {
				return true;
			}

			add_action( $action, $inspection_method, $priority, $accepted_args );

			return true;

		}

		public static function remove($routine, $action = "", $priority = 10) {

			if ( empty( $routine ) ) {
				return false;
			}

			if ( !is_array(self::$routine_events[$routine]) || count(self::$routine_events[$routine]) == 0 ) {
				return false;
			}

			if ( $action_key = array_search( $action, self::$routine_events[$routine] ) ) {

				remove_action( $routine_events[$routine][$action_key], $routine, $priority );

			}

			unset( self::$routine_events[$routine] );
			
			return true;

		}

		public static function get_event_routines($event) {

			if ( empty( $event) ) {
				return false;
			}

			$event_routines = array();

			foreach( array_keys( self::$routine_events ) as $routine ) {
				if ( in_array( $event, self::$routine_events[$routine] ) ) {
					$event_routines[] = $routine;
				}
			}

			return $event_routines;

		}

		public static function get_routine_events($routine) {

			if (empty($routine)) {
				return false;
			}

			return self::$routine_events[$routine];

		}	

		public static function get_events() {

			$events = array();

			foreach(array_keys(self::$routine_events) as $routine) {
				foreach(self::$routine_events[$routine] as $event) {
					if (!in_array($event, $events)) {
						$events[] = $event;
					}
				}
			}

			return $events;

		}

		public static function get_inspection_routines() {

			return self::get_event_routines('ac_inspection');

		}

		public static function get_wp_hook_routines() {

			$wp_hook_routines = array();

			foreach(array_keys(self::$routine_events) as $routine) {
				foreach(self::$routine_events[$routine] as $event) {
					if ($event != 'ac_inspection' && !in_array($routine, $wp_hook_routines)) {
						$wp_hook_routines[] = $routine;
						break;
					}
					
				}
			}

			return $wp_hook_routines;

		}

		public static function get_all() {

			return self::$routine_events;

		}

		

	}

}
