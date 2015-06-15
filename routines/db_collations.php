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

		$proper_db_collation =  self::get_proper_db_collation();

		$default_db_collation = $wpdb->get_var( "SELECT DEFAULT_COLLATION_NAME
												 FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '".DB_NAME."'" );

		if ( $proper_db_collation != $default_db_collation ) {
			AC_Inspector::log( "Default table collation is $default_db_collation (should be $proper_db_collation).", __CLASS__ );
		}

		list( $proper_charset ) = explode( '_', $proper_db_collation );

        $tbl_collation_queries = self::get_table_collation_queries();

        if ( is_array( $tbl_collation_queries ) && count( $tbl_collation_queries ) > 0 ) {

        	foreach( $tbl_collation_queries as $tbl_collation_query ) {

        		$tbl_collation_data = $wpdb->get_row( $tbl_collation_query );
        		$tbl_name = $tbl_collation_data->TABLE_NAME;
        		$tbl_collation = $tbl_collation_data->TABLE_COLLATION;

        		if ( $proper_db_collation != $tbl_collation ) {
					AC_Inspector::log( "Table collation for $tbl_name is $tbl_collation (should be $proper_db_collation).", __CLASS__ );
				}

				$tbl_columns = $wpdb->get_results( "SHOW FULL COLUMNS FROM `$tbl_name`" );
				if ( ! $tbl_columns ) {
					AC_Inspector::log( "Unable to determine column collations for table $tbl_name.", __CLASS__ );
					continue;
				}

				foreach ( $tbl_columns as $column ) {
					if ( $column->Collation ) {
						if ( $proper_db_collation !== $column->Collation ) {
							AC_Inspector::log( "Column collation for {$column->Field} in $tbl_name is {$column->Collation} (should be $proper_db_collation).", __CLASS__ );
						}
					}
				}

        	}

        }

		return;

	}

	public static function repair() {

		global $wpdb;

        $proper_db_collation =  self::get_proper_db_collation();

		$default_db_collation = $wpdb->get_var( "SELECT DEFAULT_COLLATION_NAME
												 FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '".DB_NAME."'" );

		list( $proper_charset ) = explode( '_', $proper_db_collation );

		if ( $proper_db_collation != $default_db_collation ) {
			if ( $wpdb->query( $wpdb->prepare( "ALTER DATABASE `".DB_NAME."` CHARACTER SET %s COLLATE %s", $proper_charset, $proper_db_collation ) ) ) {
				AC_Inspector::log( "Converted default table collation from $default_db_collation to $proper_db_collation.", __CLASS__, array( 'success' => true ) );
			} else {
				AC_Inspector::log( "Failed to convert default table collation from $default_db_collation to $proper_db_collation!", __CLASS__ , array( 'error' => true ));
			}
		}

        $tbl_collation_queries = self::get_table_collation_queries();

        if ( is_array( $tbl_collation_queries ) && count( $tbl_collation_queries ) > 0 ) {

        	foreach( $tbl_collation_queries as $tbl_collation_query ) {

        		$tbl_collation_data = $wpdb->get_row( $tbl_collation_query );
        		$tbl_name = $tbl_collation_data->TABLE_NAME;
        		$tbl_collation = $tbl_collation_data->TABLE_COLLATION;

        		if ( $proper_db_collation != $tbl_collation ) {
        			if ( $wpdb->query( $wpdb->prepare( "ALTER TABLE `".$tbl_name."` CONVERT TO CHARACTER SET %s COLLATE %s", $proper_charset, $proper_db_collation ) ) ) {
						AC_Inspector::log( "Converted collation for $tbl_name from $tbl_collation to $proper_db_collation.", __CLASS__, array( 'success' => true ) );
        			} else {
        				AC_Inspector::log( "Failed to convert collation for $tbl_name from $tbl_collation to $proper_db_collation.", __CLASS__, array( 'error' => true ) );
        			}
        			continue;
				}

				$tbl_columns = $wpdb->get_results( "SHOW FULL COLUMNS FROM `$tbl_name`" );
				if ( ! $tbl_columns ) {
					AC_Inspector::log( "Unable to determine column collations for table $tbl_name.", __CLASS__, array( 'error' => true ) );
					continue;
				}

				foreach ( $tbl_columns as $column ) {
					if ( $column->Collation ) {
						if ( $proper_db_collation !== $column->Collation ) {
							if ( $wpdb->query( $wpdb->prepare( "ALTER TABLE `".$tbl_name."` CONVERT TO CHARACTER SET %s COLLATE %s", $proper_charset, $proper_db_collation ) ) ) {
								AC_Inspector::log( "Converted collation for $tbl_name from $tbl_collation to $proper_db_collation.", __CLASS__, array( 'success' => true ) );
		        			} else {
		        				AC_Inspector::log( "Failed to convert collation for $tbl_name from $tbl_collation to $proper_db_collation.", __CLASS__, array( 'error' => true ) );
		        			}
		        			break;
						}
					}
				}

        	}

        }

		return;

	} 

	private static function get_proper_db_collation() {

		global $wp_version;

		if ( $wp_version >= 4.2 ) {

			$blog_charset = 'utf8mb4';
	    	$language = 'unicode';

	    } else {

	    	$blog_charset = strtolower( str_replace( '-', '', get_option('blog_charset') ) );

		    $language = 'general';

		    require_once( ABSPATH . 'wp-admin/includes/translation-install.php' );
	    	$translations = wp_get_available_translations();
	    	$locale = get_locale();

		    if ( 'en_US' != $locale && isset( $translations[$locale] ) ) {
		    	$language = strtolower( $translations[$locale]['english_name'] );
		    }

		}

		return $blog_charset . '_' . $language . '_ci';

	}

	private static function get_table_collation_queries() {

		global $wpdb;

		$tbl_collation_queries_query = "SELECT  CONCAT('SELECT TABLE_NAME, TABLE_COLLATION FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = \'', table_name, '\';') AS sql_statements
		                                FROM    information_schema.tables AS tb
		                                WHERE   table_schema = '".DB_NAME."'
		                                AND     `TABLE_TYPE` = 'BASE TABLE'
		                                ORDER BY table_name DESC";

        return $wpdb->get_col( $tbl_collation_queries_query  );

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
