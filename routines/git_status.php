<?php

if ( file_exists(ABSPATH.".git") && is_dir(ABSPATH.".git") ) {

	require_once( ACI_PLUGIN_DIR.'/git/Git.php' );

	class ACI_Routine_Check_Git_Status {

		const LOG_LEVEL = 'warning';

		const DESCRIPTION = "Detects uncommited file changes in the site's Git-repository.";

		private static $_default_ignore_files = array(
			'wp-content/uploads/*',
			'wp-content/blogs.dir/*',
			'wp-content/cache/*',
			'wp-content/avatars/*',
			'wp-content/*/LC_MESSAGES/*'
		);

		public static function register() {

			$options = array( 'log_level' => self::LOG_LEVEL, 
							  'description' => self::DESCRIPTION,
							  'ignore_files' => self::$_default_ignore_files );
			
			aci_register_routine( __CLASS__, $options );

			add_action( __CLASS__.'_settings_field', array( __CLASS__, 'settings_field' ), 10, 2 );
			add_filter( __CLASS__.'_settings',  array( __CLASS__, 'settings' ), 10, 1 );

		}

		public static function inspect() {

			$repo = Git::open(ABSPATH);

			if (is_object($repo) && $repo->test_git()) {

				$status_data = $repo->run('status --porcelain');

				$changed_files = array();
	        
		        if( preg_match_all( '/(^.+?)\\s(.*)$/m', $status_data, $changes, PREG_SET_ORDER ) ){
		            
		            foreach( $changes  as $changed_item ){
		            	$change = trim($changed_item[1]);
		            	$file = trim($changed_item[2]);
		                $changed_files[$change][] = $file;
		            }
		            
		        }

		        $routine_options = ACI_Routine_Handler::get_options( __CLASS__ );

		        if ( !is_array($routine_options) ) {
		        	$routine_options = array();
		        }

		        if ( !is_array($routine_options['changed_files']) ) {
		        	$routine_options['changed_files'] = array();
		        }

		        if ( empty( $routine_options['ignore_files'] ) ) {
		        	$routine_options['ignore_files'] = self::$_default_ignore_files;
		        } else if ( !is_array( $routine_options['ignore_files'] ) ) {
		        	$routine_options['ignore_files'] = (array) $routine_options['ignore_files'];
		        }

		        foreach( array_keys($changed_files) as $change ) {

		        	foreach( $routine_options['ignore_files'] as $file_path ) {
		        		if (!empty($file_path)) {
			        		$files_to_ignore = preg_grep('/^'.str_replace('\*', '*', preg_quote($file_path, '/').'/'), $changed_files[$change] );
			        		if ( is_array($files_to_ignore) && count($files_to_ignore) > 0 ) {
			        			foreach(array_keys($files_to_ignore) as $ignore_file_key) {
			        				unset( $changed_files[$change][$ignore_file_key] );
			        			}
			        		}
			        	}
		        	}

		        	if (count($changed_files[$change]) > 0) {

			        	switch($change) {

			        		case 'A':
			        			AC_Inspector::log( 'Git repository has '.count($changed_files[$change]).' NEW file(s).', __CLASS__ );
			        			break;
			        		case 'M':
			        			AC_Inspector::log( 'Git repository has '.count($changed_files[$change]).' MODIFIED file(s).', __CLASS__ );
			        			break;
			        		case 'D':
			        			AC_Inspector::log( 'Git repository has '.count($changed_files[$change]).' DELETED file(s).', __CLASS__ );
			        			break;
			        		case '??':
			        			AC_Inspector::log( 'Git repository has '.count($changed_files[$change]).' UNTRACKED file(s).', __CLASS__ );
			        			break;

			        	}

		        	}

		        }

		        $routine_options['changed_files'] = $changed_files;

		        ACI_Routine_Handler::set_options( __CLASS__, $routine_options );

			}

		}

		public static function settings_field( $options, $args = array() ) {

			$routine = $args['routine'];

	    	?>

			<tr valign="top">
			    <td scope="row" valign="top" style="vertical-align: top;">Ignore files</td>
			    <td>
	        		<textarea cols="45" rows="5" name="aci_options[<?php echo $routine; ?>][ignore_files]" type="checkbox" id="aci_options_<?php echo $routine; ?>_ignore_files"><?php echo implode("\n", (array) $options['ignore_files']); ?></textarea>
	        		<p class="description">Enter a list of files to ignore, seperated by line breaks.</p>
				</td>
			</tr>

			<?php

		}

		public static function settings( $options ) {

			if ( !empty( $options['ignore_files'] ) && false != strpos( $options['ignore_files'], "\n" ) ) {
				$options['ignore_files'] = array_map('trim', explode("\n", $options['ignore_files']));
			}

			return $options;

		}

	}

	ACI_Routine_Check_Git_Status::register();

}
