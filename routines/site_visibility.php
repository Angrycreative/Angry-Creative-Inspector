<?php

/*
 *	Checks site visibility
 */

class ACI_Routine_Check_Site_Visibility {

	const LOG_LEVEL = "warning";

	const DESCRIPTION = "Checks if your site(s) is blocking search engines.";

	public static function register() {

		$options = array( 'log_level' => self::LOG_LEVEL,
						  'description' => self::DESCRIPTION,
						  'site_specific_settings' => 0 );
		
		aci_register_routine( __CLASS__, $options );

	}

	public static function inspect() {

		if ( is_multisite() && is_plugin_active_for_network( ACI_PLUGIN_BASENAME ) ) {

			global $wpdb;
			$site_blog_ids = $wpdb->get_col("SELECT blog_id FROM ".$wpdb->prefix."blogs");

			if (is_array($site_blog_ids)) {
				foreach( $site_blog_ids AS $site_blog_id ) {

					if (intval($site_blog_id) > 0) {

						$visible = get_blog_details( $site_blog_id )->public;

						if ( !$visible ) {

							AC_Inspector::log( 'Site '.$site_blog_id.' is not visible to search engines.', __CLASS__, $site_blog_id );

						}

					}

				}
			}

		} else {

			$visible = get_option('blog_public');

			if ( !$visible ) {

				AC_Inspector::log( 'The site is not visible to search engines.', __CLASS__ );

			}

		}

	}

}

ACI_Routine_Check_Site_Visibility::register();
