<?php

/*
 *	Checks and repairs site visibility
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

							AC_Inspector::log( 'Site '.$site_blog_id.' is not visible to search engines.', __CLASS__, array( 'site_id' => $site_blog_id ) );

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

	public static function repair() {

		if ( !aci_release_tier_aware() ) {
			AC_Inspector::log( 'Unable to determine the appropriate site visibility setting, please define SITE_RELEASE_TIER in your wp-config.php.', __CLASS__, array( 'error' => true ) );
			return;
		}

		$release_tier = aci_get_release_tier();

		if ( is_multisite() && is_plugin_active_for_network( ACI_PLUGIN_BASENAME ) ) {

			global $wpdb;
			$site_blog_ids = $wpdb->get_col("SELECT blog_id FROM ".$wpdb->prefix."blogs");

			if (is_array($site_blog_ids)) {
				foreach( $site_blog_ids AS $site_blog_id ) {

					if (intval($site_blog_id) > 0) {

						$visible = get_blog_details( $site_blog_id )->public;

						if ( ( !$visible && $release_tier == 'production' ) || ( $visible && $release_tier != 'production' ) ) {

							$visible = $visible ? 0 : 1;

							switch_to_blog( $site_blog_id );
	                		update_option( 'blog_public', $visible );
	                		restore_current_blog();

	                		AC_Inspector::log( 'The site visibility setting for ' . get_blog_details( $site_blog_id, true )->blogname .' is now ' . ( ( $visible ) ? ' public' : ' private' )  . '.', __CLASS__, array( 'success' => true ) );

						} else {

							AC_Inspector::log( 'The site visibility setting for ' . get_blog_details( $site_blog_id, true )->blogname .' seems correct, no action taken.', __CLASS__, array( 'log_level' => 'notice' ) );

						}
					}

				}
			}

		} else {

			$visible = get_option('blog_public');

			if ( ( !$visible && $release_tier == 'production' ) || ( $visible && $release_tier != 'production' ) ) {

				$visible = $visible ? 0 : 1;
        		update_option( 'blog_public', $visible );

        		AC_Inspector::log( 'The site visibility setting is now ' . ( ( $visible ) ? ' public' : ' private' )  . '.', __CLASS__, array( 'success' => true ) );

			} else {

				AC_Inspector::log( 'The site visibility setting seems correct, no action taken.', __CLASS__, array( 'log_level' => 'notice' ) );

			}

		}

	}

}

ACI_Routine_Check_Site_Visibility::register();
