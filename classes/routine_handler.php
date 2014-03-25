<?php
/*
Class name: ACI Routine Handler
Version: 0.2
Depends: AC Inspector 0.4.x
Author: Sammy Nordström, Angry Creative AB
*/

if ( class_exists('AC_Inspector') && !class_exists('ACI_Routine_Handler') ) { 

	class ACI_Routine_Handler extends AC_Inspector {

		private static $routine_events = array();

		public static function routine_options_key($routine) {

			if (empty($routine)) {
				return false;
			}

			$routine_option_key = $routine . "_options";

			// Make sure the key is prefixed
			$prefix = substr($routine, 0, 4);
			if ($prefix != "aci_") {
				$routine_option_key = "aci_" . $routine_option_key;
			}

			return $routine_option_key;

		}

		public static function get_inspection_method($routine, $options = array()) {

			if (empty($routine)) {
				return false;
			}

			if (!is_array($options) || empty($options)) {

				$options = self::get_options($routine);

				if (!is_array($options)) {
					$options = array();
				}

			}

			if (class_exists($routine)) {

				if ($options['inspection_method'] && method_exists($routine, $options['inspection_method'])) {

					return array($routine, $options['inspection_method']);

				} else if (method_exists($routine, 'inspect')) {

					return array($routine, 'inspect');

				}

			} 

			if (!empty($options['inspection_method']) && function_exists($options['inspection_method'])) {

				return $options['inspection_method'];

			} else if (function_exists($routine)) {

				return $routine;

			}

			return false;

		}

		public static function set_options($routine, $args = array()) {

			if (empty($routine)) {
				return false;
			}

			$options_key = self::routine_options_key($routine);

			return parent::update_option($options_key, $args);
			
		}

		public static function get_options($routine) {

			if (empty($routine)) {
				return false;
			}

			$options_key = self::routine_options_key($routine);

			return parent::get_option($options_key);

		}

		public static function remove_options($routine) {

			if (empty($routine)) {
				return false;
			}

			$options_key = self::routine_options_key($routine);

		}

		public static function add($routine, $options = array(), $action = "ac_inspection", $priority = 10, $accepted_args = 1) {

			$inspection_method = self::get_inspection_method($routine);

			if ( !$inspection_method ) {
				return false;
			}

			if ( empty($action) ) {
				$action = "ac_inspection";
			}

			if ( !is_array( self::$routine_events[$routine] ) ) {
				self::$routine_events[$routine] = array();
			}

			if ( in_array($action, self::$routine_events[$routine]) ) {
				return false;
			}

			self::$routine_events[$routine][] = $action;

			if ( !empty( $options ) ) {

				$saved_options = self::get_options( $routine );

				if ( empty( $saved_options ) ) {
					self::set_options( $routine, $options );
				}

			}

			add_action( $action, $inspection_method, $priority, $accepted_args );

			return true;

		}

		public static function remove($routine, $action = "", $priority = 10) {

			if (empty($routine)) {
				return false;
			}

			if ( !is_array(self::$routine_events[$routine]) || count(self::$routine_events[$routine]) == 0 ) {
				return false;
			}

			if ( $action_key = array_search( $action, self::$routine_events[$routine] ) ) {

				remove_action( $routine_events[$routine][$action_key], $routine, $priority );

			}

			unset(self::$routine_events[$routine]);
			
			return true;

		}

		public static function get_event_routines($event) {

			if (empty($event)) {
				return false;
			}

			$event_routines = array();

			foreach(array_keys(self::$routine_events) as $routine) {
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
