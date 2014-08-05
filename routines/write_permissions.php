<?php

/*
 * Checks write permissions on web server
 */
class ACI_Routine_Check_Write_Permissions {

	const LOG_LEVEL = 'warning';

	const DESCRIPTION = 'Checks wether your web server has the appropriate write permissions.';

	private static $_default_allowed_dirs = array(
		'wp-content/uploads/*',
		'wp-content/plugins/*',
		'wp-content/themes/*',
		'wp-content/languages/*',
	);

	private static $_force_default_allowed_dirs = false;

	private static $_options = array();

	private static $_instantiated = false;

	public static function preload() {

		if ( defined('DISALLOW_FILE_MODS') && true == DISALLOW_FILE_MODS ) {
			self::$_default_allowed_dirs = array( 'wp-content/uploads/*' );
			self::$_force_default_allowed_dirs = true;
		} 

		if ( defined('FS_METHOD') && 'direct' == FS_METHOD ) {
			self::$_default_allowed_dirs = array( '/*' );
			self::$_force_default_allowed_dirs = true;
		}

	}

	public static function register() {

		self::preload();

		$reg_options = array( 'log_level' => self::LOG_LEVEL, 
					  		  'allowed_dirs' => self::$_default_allowed_dirs,
					  		  'description' => self::DESCRIPTION );
		
		aci_register_routine( __CLASS__, $reg_options );

		self::setup();

		if ( !self::$_force_default_allowed_dirs ) {

			add_action( __CLASS__.'_settings_field', array( __CLASS__, 'settings_field' ), 10, 2 );
			add_filter( __CLASS__.'_settings',  array( __CLASS__, 'settings' ), 10, 1 );

		}

	}

	public static function setup() {

		self::$_options = ACI_Routine_Handler::get_options( __CLASS__ );

		if ( !is_array( self::$_options ) ) {
        	self::$_options = array();
        }

		if ( self::$_force_default_allowed_dirs ) {

			self::$_options['allowed_dirs'] = self::$_default_allowed_dirs;

		} else {

			if ( !is_array( self::$_options['allowed_dirs'] ) || empty( self::$_options['allowed_dirs'] ) ) {
				self::$_options['allowed_dirs'] = self::$_default_allowed_dirs;
			}

		}

	}

	public static function inspect( $folders2check = array() ) {

		if ( !is_array($folders2check) || empty($folders2check) ) {

			$folders2check = array( '/*' );

		}

		foreach($folders2check as $folder) {

			$folder_base = trim( str_replace( '/*', '', str_replace('//', '/', str_replace( trim( ABSPATH, '/' ) , '', $folder ) ) ), '/' );

			$file_path = ABSPATH.$folder_base.'/.ac_inspector_testfile';

			$recurse = substr($folder, -2) == "/*" ? true : false;

			$allowed_dir = false;

			if ($recurse) {
				if ( "/*" == $folder && in_array( "/*", self::$_options['allowed_dirs'] ) ) {
					$allowed_dir = true;
				} else if ( !empty( $folder_base ) && false !== strpos( $file_path, $folder_base ) ) {
					$allowed_dir = true;
				}
			} else if ( in_array( $folder, self::$_options['allowed_dirs'] ) ) {
				$allowed_dir = true;
			}

			$file_created = false;

			try {

				$file_handle = @fopen($file_path, 'w');

			    if ( !$file_handle ) {

			    	if ( $allowed_dir ) {
			    		throw new Exception('Was not able to create a file in allowed folder `' . $folder_base . '`. Check your file permissions.');
			    	}

			    	$file_created = false;

				} else {

					// Test was successful, let's cleanup before returning true...
					fclose($file_handle);
					unlink($file_path);

					if ( !$allowed_dir ) {
			    		throw new Exception('Was able to create a file in disallowed folder `' . $folder_base . '`. Check your file permissions.');
			    	}

					$file_created = true;

				}

			} catch ( Exception $e ) {

				AC_Inspector::log( $e->getMessage(), __CLASS__ );

			}

			if ( $file_created && substr($folder, -2) == "/*" ) {

				$subfolders = glob(ABSPATH.$folder_base."/*", GLOB_ONLYDIR);

				if ( is_array($subfolders) && !empty($subfolders) ) {

					foreach(array_keys($subfolders) as $sf_key) {
						$subfolders[$sf_key] = trim($subfolders[$sf_key], '/') . '/*';
						if ( $f2c_key = array_search( $subfolders[$sf_key], $folders2check ) ) {
							unset($subfolders[$f2c_key]);
						}
					}

					if ( is_array($subfolders) && count($subfolders) > 0 && !empty($subfolders[0]) ) {
						self::inspect( $subfolders );
					}

				}

			}

		}

		return "";

	}

	public static function settings_field( $options, $args = array() ) {

		$routine = $args['routine'];

		if ( empty( $options['allowed_dirs'] ) || self::$_force_default_allowed_dirs ) {
			$options['allowed_dirs'] = self::$_default_allowed_dirs;
		}

    	?>

		<tr valign="top">
		    <td scope="row" valign="top" style="vertical-align: top;">Allowed directories</td>
		    <td>
        		<textarea cols="45" rows="5" name="aci_options[<?php echo $routine; ?>][allowed_dirs]" type="checkbox" id="aci_options_<?php echo $routine; ?>_allowed_dirs"><?php echo implode("\n", (array) $options['allowed_dirs']); ?></textarea>
        		<p class="description">Enter a list of directories where the web server should be allowed to write files, seperated by line breaks.</p>
			</td>
		</tr>

		<?php

	}

	public static function settings( $options ) {

		if ( empty( $options['allowed_dirs'] ) || self::$_force_default_allowed_dirs ) {
			$options['allowed_dirs'] = self::$_default_allowed_dirs;
		}

		if ( false != strpos( $options['allowed_dirs'], "\n" ) ) {
			$options['allowed_dirs'] = array_map('trim', explode("\n", $options['allowed_dirs']));
		}

		return $options;

	}

}

ACI_Routine_Check_Write_Permissions::register();
