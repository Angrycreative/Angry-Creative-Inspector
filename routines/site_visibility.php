<?php

/*
 *	Checks site visibility
 */
function aci_check_site_visibility() {

	if ( is_multisite() && is_plugin_active_for_network( ACI_PLUGIN_BASENAME ) ) {

		global $wpdb;
		$site_blog_ids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM ".$wpdb->prefix."blogs where blog_id > %d", 1));

		if (is_array($site_blog_ids)) {
			foreach( $site_blog_ids AS $site_blog_id ) {

				if (intval($site_blog_id) > 0) {

					$visible = get_blog_details( $site_blog_id )->public;

					if ( !$visible ) {

						AC_Inspector::log('Site '.$site_blog_id.' is not visible to search engines.', __FUNCTION__);

					}

				}

			}
		}

	} else {

		$visible = get_option('blog_public');

		if ( !$visible ) {

			AC_Inspector::log('The site is not visible to search engines.', __FUNCTION__);

		}

	}

	return "";

}

$options = array('log_level' => 'warning');
aci_register_routine("aci_check_site_visibility", $options);
