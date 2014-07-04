<?php

/*
 *	Still on the to-do list, but works as a routine template class for now :)
 */

class ACI_Routine_Log_Mail {

	const LOG_LEVEL = "notice";

	const DESCRIPTION = "Logs whenever Wordpress dispatches outgoing mails.";

	public static function register() {

		$default_options = array( 'log_level' => self::LOG_LEVEL, 
								  'description' => self::DESCRIPTION );
		
		aci_register_routine( __CLASS__, $default_options );

		// Optional extra settings fields
		add_action( __CLASS__.'_settings_field', array( __CLASS__, 'settings_field' ), 10, 2 );
		add_filter( __CLASS__.'_settings',  array( __CLASS__, 'settings' ), 10, 1 );

	}

	public static function inspect() {

		// Do the inspection and log a message like this:
		AC_Inspector::log( "Enter appropriate log text here.", __CLASS__ );

		// Return whatever is expected of the hook this routine is attached to, 
		// nothing if the standard "ac_inspection" cron job hook
		return "";

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

// ACI_Routine_Log_Mail::register();
