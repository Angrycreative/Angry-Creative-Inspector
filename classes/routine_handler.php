<?php
/*
Class name: ACI Routine Handler
Version: 1.0
Depends: AC Inspector 1.0 or newer
Author: Sammy Nordström, Angry Creative AB
*/

if ( class_exists('AC_Inspector') && !class_exists('ACI_Routine_Handler') ) { 

	class ACI_Routine_Handler {

		private static $force_enabled = array();
		private static $routine_triggers = array();

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

			$inspection_method = '';

			if ( !is_array( $options ) || empty( $options ) ) {

				$options = self::get_options( $routine );

				if ( !is_array( $options ) ) {
					$options = array();
				}

			}

			if ( class_exists( $routine ) ) {

				if ( !empty( $options['inspection_method'] ) && method_exists( $routine, $options['inspection_method'] ) ) {

					$inspection_method = array( $routine, $options['inspection_method'] );

				} else if ( method_exists( $routine, 'inspect' ) ) {

					$inspection_method = array( $routine, 'inspect' );

				}

			}

			if ( !empty( $options['inspection_method'] ) && function_exists( $options['inspection_method'] ) ) {

				$inspection_method = $options['inspection_method'];

			} else if ( function_exists( $routine ) ) {

				$inspection_method = $routine;

			}

			return apply_filters( 'ac_inspector_'.$routine.'_inspection_method', $inspection_method );

		}

		public static function get_repair_method( $routine, $options = array() ) {

			if ( empty( $routine ) ) {
				return false;
			}

			$repair_method = '';

			if ( !is_array( $options ) || empty( $options ) ) {

				$options = self::get_options( $routine );

				if ( !is_array( $options ) ) {
					$options = array();
				}

			}

			if ( class_exists( $routine ) ) {

				if ( !empty( $options['repair_method'] ) && method_exists( $routine, $options['repair_method'] ) ) {

					$repair_method = array( $routine, $options['repair_method'] );

				} else if ( method_exists( $routine, 'repair' ) ) {

					$repair_method = array( $routine, 'repair' );

				}

			}

			if ( !empty( $options['repair_method'] ) && function_exists( $options['repair_method'] ) ) {

				$repair_method = $options['repair_method'];

			}

			return apply_filters( 'ac_inspector_'.$routine.'_repair_method', $repair_method );

		}

		public static function set_options($routine, $args = array()) {

			if ( empty( $routine ) ) {
				return false;
			}

			if ( self::is_scheduled( $routine ) ) {

				$schedules = array_keys( wp_get_schedules() );
				$inspection_method = self::get_inspection_method( $routine, self::get_options( $routine ) );

				$filters = $GLOBALS['wp_filter'][ $action ];

				foreach( $schedules as $schedule ) {
					$action = 'ac_inspection_'.$schedule;
					$filters = $GLOBALS['wp_filter'][$action];
					if ( !empty( $filters ) ) {
						foreach ( $filters as $priority => $filter ) {
							foreach ( $filter as $identifier => $function ) {
								if ( $function['function'] === $inspection_method ) {
									remove_filter(
										$action,
										$inspection_method,
										$priority
									);
								}
							}
						}
					}
				}

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

						if ( in_array( $routine, self::$force_enabled ) && $options[$site_blog_id]['log_level'] == 'ignore' ) {
							$options[$site_blog_id]['log_level'] = 'notice';
						}

					}

				}

			} else {

				if ( in_array( $routine, self::$force_enabled ) && $options['log_level'] == 'ignore' ) {
					$options['log_level'] = 'notice';
				}

			}

			return apply_filters( 'ac_inspector_'.$routine.'_options', $options );

		}

		public static function remove_options($routine) {

			if (empty($routine)) {
				return false;
			}

			$options_key = self::routine_options_key($routine);

		}

		public static function force_enable( $routine = "" ) {
			
			if ( !empty( $routine ) ) {
				self::$force_enabled = array_merge( self::$force_enabled, array( $routine ) );
			}

		}

		public static function release_tier_aware() {

			if ( defined( 'SITE_RELEASE_TIER' ) || apply_filters( 'ac_inspector_site_release_tier', '' ) ) {
				return true;
			}

			return false;

		}

		public static function get_release_tier() {

			$site_release_tier = '';

			if ( defined( 'SITE_RELEASE_TIER' ) && in_array( SITE_RELEASE_TIER, array( 'local', 'development', 'integration', 'test', 'stage', 'production' ) ) ) {
				$site_release_tier = SITE_RELEASE_TIER;
			}

			return apply_filters( 'ac_inspector_site_release_tier', SITE_RELEASE_TIER );

		}

		public static function is_release_tier( $tier ) {

			if ( empty( $tier ) ) {
				return false;
			}

			$site_release_tier = self::get_release_tier();

			if ( $tier == $site_release_tier ) {
				return true;
			}

			return false;

		}

		public static function add( $routine, $options = array(), $trigger = "daily", $priority = 10, $accepted_args = 1 ) {

			$inspection_method = self::get_inspection_method( $routine, $options );

			if ( empty( $inspection_method ) ) {
				return false;
			}

			if ( empty( $trigger ) || $trigger == 'ac_inspection' ) {
				$trigger = 'daily';
			}

			$schedules = wp_get_schedules();
			if ( in_array( $trigger, array_keys( $schedules ) ) ) {
				$action = "ac_inspection_".$trigger;
			} else {
				$action = $trigger;
			}

			if ( !array_key_exists( $routine, self::$routine_triggers ) || !is_array( self::$routine_triggers[$routine] ) ) {
				self::$routine_triggers[$routine] = array();
			}

			if ( in_array( $action, self::$routine_triggers[$routine] ) ) {
				return false;
			}

			self::$routine_triggers[$routine][] = $action;

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
				if ( !empty( $options[$current_site_id]['schedule'] ) && in_array( $options[$current_site_id]['schedule'], $schedules ) ) {
					$action = "ac_inspection_".$options[$current_site_id]['schedule'];
				}
			} else {
				if ( $options['log_level'] == 'ignore' ) {
					return true;
				}
				if ( !empty( $options['schedule'] ) && in_array( $options['schedule'], $schedules ) ) {
					$action = "ac_inspection_".$options['schedule'];
				}
			}

			add_action( $action, $inspection_method, $priority, $accepted_args );
			add_action( 'ac_inspection_now', $inspection_method, $priority, $accepted_args );

			return true;

		}

		public static function remove( $routine, $action = "", $priority = 10 ) {

			if ( empty( $routine ) ) {
				return false;
			}

			if ( !is_array(self::$routine_triggers[$routine]) || count(self::$routine_triggers[$routine]) == 0 ) {
				return false;
			}

			if ( $action_key = array_search( $action, self::$routine_triggers[$routine] ) ) {

				remove_action( $routine_triggers[$routine][$action_key], $routine, $priority );

			}

			unset( self::$routine_triggers[$routine] );

			return true;

		}

		public static function get_trigger_routines( $trigger ) {

			if ( empty( $trigger ) ) {
				return false;
			}

			$trigger_routines = array();

			foreach( array_keys( self::$routine_triggers ) as $routine ) {
				if ( in_array( $trigger, self::$routine_triggers[$routine] ) ) {
					$trigger_routines[] = $routine;
				}
			}

			return apply_filters( 'ac_inspector_'.$trigger.'_routines', $trigger_routines );

		}

		public static function get_routine_triggers( $routine ) {

			if (empty($routine)) {
				return false;
			}

			return apply_filters( 'ac_inspector_'.$routine.'_triggers', self::$routine_triggers[$routine] );

		}

		public static function get_triggers() {

			$triggers = array();

			foreach(array_keys(self::$routine_triggers) as $routine) {
				foreach(self::$routine_triggers[$routine] as $trigger) {
					if (!in_array($trigger, $triggers)) {
						$triggers[] = $trigger;
					}
				}
			}

			return apply_filters( 'ac_inspector_routine_triggers', $triggers );

		}

		public static function get_scheduled_routines() {

			$schedules = wp_get_schedules();
			$scheduled_routines = array();

			foreach( array_keys( $schedules ) as $schedule ) {
				$scheduled_routines = array_merge( $scheduled_routines, self::get_trigger_routines( 'ac_inspection_'.$schedule ) );
			}

			return apply_filters( 'ac_inspector_scheduled_routines', $scheduled_routines );

		}

		public static function get_hooked_routines() {

			$hooked_routines = array();

			foreach(array_keys(self::$routine_triggers) as $routine) {
				if ( !self::is_scheduled( $routine ) ) {
					$hooked_routines[] = $routine;
				}
			}

			return apply_filters( 'ac_inspector_hooked_routines', $hooked_routines );

		}

		public static function get_all() {

			return apply_filters( 'ac_inspector_all_routines', self::$routine_triggers );

		}

		public static function is_scheduled( $routine ) {

			if ( !is_array( self::$routine_triggers ) || !array_key_exists( $routine , self::$routine_triggers ) ) {
				return false;
			}

			$schedules = wp_get_schedules();

			$routine_schedules = array_map( function( $trigger ) {
				return str_replace( 'ac_inspection_', '', $trigger );
			}, self::$routine_triggers[$routine] );

			return ( count( array_intersect( array_keys( $schedules ), $routine_schedules ) ) >= 1 ) ? true : false;

		}

	}

}
