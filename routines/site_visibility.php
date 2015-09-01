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

	public static function is_visible( $site_id = 1 ) {

		if ( is_multisite() && is_plugin_active_for_network( ACI_PLUGIN_BASENAME ) ) {

			if ( intval( $site_id ) == 0 || !get_blog_details( $site_id ) ) {
				$site_id = 1;
			}

			$visible = get_blog_details( $site_id )->public;

		} else {

			$visible = get_option('blog_public');

		}

		return $visible ? true : false;

	}

	public static function should_be_visible( $site_id = 1 ) {

		if ( !aci_release_tier_aware() ) {
			return null;
		}

		$release_tier = aci_get_release_tier();

		if ( $release_tier != 'production' ) {
			return false;
		}

		if ( defined( 'HIDDEN_SITES' ) ) {

			$hidden_sites = explode( ',', HIDDEN_SITES );

			foreach ($hidden_sites as $hidden_site_id) {

				$hidden_site_id = intval( trim( $hidden_site_id ) );

				if ( $hidden_site_id > 0 && $hidden_site_id == $site_id ) {
					return false;
				}
			}
		}

		return true;

	}

	public static function inspect() {

		if ( is_multisite() && is_plugin_active_for_network( ACI_PLUGIN_BASENAME ) ) {

			global $wpdb;
			$site_ids = $wpdb->get_col("SELECT blog_id FROM ".$wpdb->prefix."blogs");

			if (is_array($site_ids)) {
				foreach( $site_ids AS $site_id ) {

					if ( null === self::should_be_visible( $site_id ) ) {
						if ( self::is_visible( $site_id ) ) {
							AC_Inspector::log( 'Site '.$site_id.' is visible to search enginges, which may be correct as well as incorrect. Please define SITE_RELEASE_TIER in your wp-config.php to help me determine this.', __CLASS__ );
						} else {
							AC_Inspector::log( 'Site '.$site_id.' is not visible to search enginges, which may be correct as well as incorrect. Please define SITE_RELEASE_TIER in your wp-config.php to help me determine this.', __CLASS__ );
						}
					}

					if ( !self::is_visible( $site_id ) && true === self::should_be_visible( $site_id ) ) {
						AC_Inspector::log( 'Site '.$site_id.' should be visible to search engines, please check your site visibility settings.', __CLASS__, array( 'site_id' => $site_id ) );
					} else if ( self::is_visible( $site_id ) && false === self::should_be_visible( $site_id ) ) {
						AC_Inspector::log( 'Site '.$site_id.' should not be visible to search engines, please check your site visibility settings.', __CLASS__, array( 'site_id' => $site_id ) );
					}

				}
			}

		} else {

			if ( null === self::should_be_visible() ) {
				if ( self::is_visible() ) {
					AC_Inspector::log( 'The site is visible to search enginges, which may be correct as well as incorrect. Please define SITE_RELEASE_TIER in your wp-config.php to help me determine this.', __CLASS__ );
				} else {
					AC_Inspector::log( 'The site is not visible to search enginges, which may be correct as well as incorrect. Please define SITE_RELEASE_TIER in your wp-config.php to help me determine this.', __CLASS__ );
				}
			}

			if ( !self::is_visible() && true === self::should_be_visible() ) {
				AC_Inspector::log( 'The site should be visible to search engines, please check your site visibility settings.', __CLASS__ );
			} else if ( self::is_visible() && false === self::should_be_visible() ) {
				AC_Inspector::log( 'The site should not be visible to search engines, please check your site visibility settings.', __CLASS__ );
			}

		}

	}

	public static function repair() {

		if ( !aci_release_tier_aware() ) {
			AC_Inspector::log( 'Unable to determine the appropriate site visibility setting, please define SITE_RELEASE_TIER in your wp-config.php.', __CLASS__, array( 'error' => true ) );
			return;
		}

		if ( is_multisite() && is_plugin_active_for_network( ACI_PLUGIN_BASENAME ) ) {

			global $wpdb;
			$site_ids = $wpdb->get_col("SELECT blog_id FROM ".$wpdb->prefix."blogs");

			if (is_array($site_ids)) {
				foreach( $site_ids AS $site_id ) {

					if (intval($site_id) > 0) {

						$visible = self::is_visible( $site_id );

						if ( ( !$visible && true === self::should_be_visible( $site_id ) ) || ( $visible && false === self::should_be_visible( $site_id ) ) ) {

							$visible = $visible ? 0 : 1;

							switch_to_blog( $site_id );
	                		update_option( 'blog_public', $visible );
	                		restore_current_blog();

	                		AC_Inspector::log( 'The site visibility setting for ' . get_blog_details( $site_id, true )->blogname .' is now ' . ( ( $visible ) ? ' public' : ' private' )  . '.', __CLASS__, array( 'success' => true ) );

						} else {

							AC_Inspector::log( 'The site visibility setting for ' . get_blog_details( $site_id, true )->blogname .' seems correct, no action taken.', __CLASS__, array( 'log_level' => 'notice' ) );

						}
					}

				}
			}

		} else {

			$visible = self::is_visible();

			if ( ( !$visible && true === self::should_be_visible() ) || ( $visible && false === self::should_be_visible() ) ) {

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
