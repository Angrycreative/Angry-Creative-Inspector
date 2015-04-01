<?php

/*
 *	Check MySQL Database Table Collations 
 */

class ACI_Routine_Check_DB_Collations {

	const LOG_LEVEL = "notice";

	const DESCRIPTION = "Checks wether the MySQL database table collations adheres to the site language settings.";

	public static function register() {

		$default_options = array( 'log_level' => self::LOG_LEVEL, 
								  'description' => self::DESCRIPTION );
		
		aci_register_routine( __CLASS__, $default_options );

		// Optional extra settings fields
		// add_action( __CLASS__.'_settings_field', array( __CLASS__, 'settings_field' ), 10, 2 );
		// add_filter( __CLASS__.'_settings',  array( __CLASS__, 'settings' ), 10, 1 );

	}

	public static function inspect() {

		global $wpdb;

		$blog_charset = strtolower( str_replace( '-', '', get_option('blog_charset') ) );
		$locale = get_locale();
		
		require_once( ABSPATH . 'wp-admin/includes/translation-install.php' );
	    $translations = wp_get_available_translations();

	    $language = 'general';

	    if ( 'en_US' != $language && isset( $translations[$locale] ) ) {
	    	$language = strtolower( $translations[$locale]['english_name'] );
	    }

		$proper_tbl_collation =  $blog_charset . '_' . $language . '_ci';

		$default_tbl_collation = $wpdb->get_var( "SELECT DEFAULT_COLLATION_NAME
												  FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '".DB_NAME."'" );

		if ( $proper_tbl_collation != $default_tbl_collation ) {
			AC_Inspector::log( "Default table collation is $default_tbl_collation (should be $proper_tbl_collation).", __CLASS__ );
		}

		$tbl_collation_queries_query = "SELECT  CONCAT('SELECT TABLE_NAME, TABLE_COLLATION FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = \'', table_name, '\';') AS sql_statements
		                                FROM    information_schema.tables AS tb
		                                WHERE   table_schema = '".DB_NAME."'
		                                AND     `TABLE_TYPE` = 'BASE TABLE'
		                                ORDER BY table_name DESC";

        $tbl_collation_queries = $wpdb->get_col( $tbl_collation_queries_query  );

        if ( is_array( $tbl_collation_queries ) && count( $tbl_collation_queries ) > 0 ) {

        	foreach( $tbl_collation_queries as $tbl_collation_query ) {

        		$tbl_collation_data = $wpdb->get_row( $tbl_collation_query );
        		$tbl_name = $tbl_collation_data->TABLE_NAME;
        		$tbl_collation = $tbl_collation_data->TABLE_COLLATION;

        		if ( $proper_tbl_collation != $tbl_collation ) {
					AC_Inspector::log( "Table collation for $tbl_name is $tbl_collation (should be $proper_tbl_collation).", __CLASS__ );
				}

        	}

        }

		return;

	}

	public static function settings_field( $options, $args = array() ) {

		$routine = $args['routine'];

		/**
		* 
		*	Input field name convention: aci_options[<?php echo $routine; ?>][field_name]
		*	Input field id convention: aci_options_<?php echo $routine; ?>_field_name 
		*
		**/

    	?>

		<tr valign="top">
		    <td scope="row" valign="top" style="vertical-align: top;">Example setting field</td>
		    <td>
        		<p class="description">Here be extra settings field :)</p>
			</td>
		</tr>

		<?php

	}

	public static function settings( $options ) {

		// Here be code that parses and validates submitted values

		return $options;

	}

}

ACI_Routine_Check_DB_Collations::register();
