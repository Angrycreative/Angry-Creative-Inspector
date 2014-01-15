<?php
/*
Class name: ACI Routine Handler
Version: 0.1
Depends: AC Inspector 0.3.x
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

			if ( empty($routine) ) {
				return false;
			}

			if ( empty($action) ) {
				$action = "ac_inspection";
			}

			if ( !is_array( self::$routine_events[$routine] ) ) {
				self::$routine_events[$routine] = array();
			}

			self::$routine_events[$routine][] = $action;

			if ( $action == "ac_inspection" || has_action( $action ) ) {
				add_action( $action, $routine, $priority, $accepted_args );
			}

			if ( !empty( $options ) ) {

				$saved_options = self::get_options( $routine );

				if ( empty( $saved_options ) ) {
					self::set_options( $routine, $options );
				}

			}

			return true;

		}

		public static function remove($routine, $action = "", $priority = 10) {

			if (empty($routine)) {
				return false;
			}

			if ( is_array(self::$routine_events[$routine]) && count(self::$routine_events[$routine]) > 0 ) {

				if ( $action_key = array_search( $action, self::$routine_events[$routine] ) ) {

					remove_action( $routine_events[$routine][$action_key], $routine, $priority );

				}

				unset(self::$routine_events[$routine]);
				return true;

			}

			return false;

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
