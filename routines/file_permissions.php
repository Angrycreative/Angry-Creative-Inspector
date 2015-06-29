<?php

/*
 * Checks file permissions on the web server
 */
class ACI_Routine_Check_File_Permissions {

	const LOG_LEVEL = 'warning';

	const DESCRIPTION = 'Checks wether your web server has the appropriate file permissions.';

	private static $_default_allowed_dirs = array(
		'wp-content/uploads/*',
		'wp-content/blogs.dir/*',
		'wp-content/cache/*',
		'wp-content/plugins/*',
		'wp-content/themes/*',
		'wp-content/languages/*',
	);

	private static $_force_default_allowed_dirs = false;

	private static $_options = array();

	private static $_instantiated = false;

	private static $_real_abspath = ABSPATH;

	public static function preload() {

		if ( defined('DISALLOW_FILE_MODS') && true == DISALLOW_FILE_MODS ) {
			self::$_default_allowed_dirs = array( 'wp-content/uploads/*', 
												  'wp-content/blogs.dir/*', 
												  'wp-content/cache/*', 
												  'wp-content/avatars/*', 
												  'wp-content/*/LC_MESSAGES/*' );
			self::$_force_default_allowed_dirs = false;
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
			} else {
				self::$_options['allowed_dirs'] = wp_parse_args( self::$_options['allowed_dirs'], self::$_default_allowed_dirs );
			}

		}

		if ( is_link( rtrim( ABSPATH, '/' ) ) ) {
			self::$_real_abspath = readlink( rtrim( ABSPATH, '/' ) );
		} else {
			self::$_real_abspath = rtrim( ABSPATH, '/' );
		}

		$wildcard_dir_paths = preg_grep( "/^(.*\/[^\*]*)?([\*]+\/)(.*)$/", self::$_options['allowed_dirs'] );

		if ( is_array( $wildcard_dir_paths ) && count( $wildcard_dir_paths ) > 0 ) {

			foreach( $wildcard_dir_paths as $wc_path ) {

				$wc_path = trim( $wc_path, '/' );

				if ( "/*" == substr( $wc_path, -2 ) ) {
					$resolved_paths = glob( self::$_real_abspath.'/'.substr( $wc_path, 0, -2 ), GLOB_ONLYDIR );
					foreach ( $resolved_paths as &$res_path ) {
						if ( !empty( $res_path ) ) {
							$res_path = $res_path . "/*";
						}
					}
				} else {
					$resolved_paths = glob( self::$_real_abspath.'/'.$wc_path, GLOB_ONLYDIR );
				}

				$allowed_path_key = array_search( $wc_path, self::$_options['allowed_dirs'] );
				unset( self::$_options['allowed_dirs'][$allowed_path_key] );

				if ( is_array( $resolved_paths ) && count( $resolved_paths ) > 0 ) {
					self::$_options['allowed_dirs'] = array_merge( self::$_options['allowed_dirs'], $resolved_paths );
				}

			}
		}

	}

	public static function inspect( $folders2check = array(), $halt_on_error = true ) {

		if ( defined( 'WP_CLI' ) && WP_CLI ) {

			if ( !defined( 'HTTPD_USER' ) ) {
				AC_Inspector::log( 'Unable to determine the user your web server is running as, please define HTTPD_USER in your wp-config.php.', __CLASS__, array( 'error' => true ) );
				return;
			}

			$httpd_usr = posix_getpwnam( HTTPD_USER );

			if ( !$httpd_usr ) {
				AC_Inspector::log( 'Unable to get retrieve information about a user named ' . HTTPD_USER . ', please check your HTTPD_USER setting in the wp-config.php file.', __CLASS__, array( 'error' => true ) );
				return;
			}

			$original_uid = posix_geteuid();

			if ( !posix_seteuid( $httpd_usr['uid'] ) ) {
				AC_Inspector::log( 'Unable change the owner of the current process to ' . HTTPD_USER . ' (uid: ' . $httpd_usr['uid'] . '), do you have the appropriate sudo privileges?', __CLASS__, array( 'error' => true ) );
				return;
			}

		}

		if ( !is_array($folders2check) || empty($folders2check) ) {

			$folders2check = array( '/*' );

		}

		foreach($folders2check as $folder) {

			$folder_base = trim( str_replace( '/*', '', str_replace('//', '/', str_replace( trim( ABSPATH, '/' ) , '', $folder ) ) ), '/' );
			$recursive = substr($folder, -2) == "/*" ? true : false;

			if ( is_link( self::$_real_abspath.'/'.$folder_base ) ) {
				$resolved_folder_path = readlink( self::$_real_abspath.'/'.$folder_base );
			} else {
				$resolved_folder_path = self::$_real_abspath.'/'.$folder_base;
			}

			if ( !self::$_force_default_allowed_dirs && !file_exists( $resolved_folder_path ) ) {
				continue;
			}

			$file_path = $resolved_folder_path.'/.ac_inspector_testfile';
			$allowed_dir = false;

			if ($recursive) {
				if ( "/*" == $folder && in_array( "/*", self::$_options['allowed_dirs'] ) ) {
					$allowed_dir = true;
				} else if ( !empty( $folder_base ) && false !== strpos( $file_path, $folder_base ) ) {
					$allowed_dir = true;
				}
			} else if ( in_array( $folder, self::$_options['allowed_dirs'] ) ) {
				$allowed_dir = true;
			}

			$bad_folder_perm = false;
			$bad_file_perm = false;

			try {

				$file_handle = @fopen($file_path, 'w');

			    if ( !$file_handle ) {

			    	if ( $allowed_dir ) {
			    		throw new Exception('Was not able to create a file in allowed folder `' . $folder_base . '`. Check your file permissions.');
			    	}

				} else {

					// Test was successful, let's cleanup before returning true...
					fclose($file_handle);
					unlink($file_path);

					if ( !$allowed_dir ) {
			    		throw new Exception('Was able to create a file in disallowed folder `' . $folder_base . '`. Check your file permissions.');
			    	}

				}

			} catch ( Exception $e ) {

				AC_Inspector::log( $e->getMessage(), __CLASS__ );

				if ( defined( 'WP_CLI' ) && WP_CLI && $halt_on_error ) {
					$response = cli\choose( "Bad file permissions detected, continue the inspection", $choices = 'yn', $default = 'n' );
					if ( $response !== 'y' ) {
						if ( !posix_seteuid( $original_uid ) ) {
							AC_Inspector::log( 'Unable restore the owner of the current process (uid: ' . $original_uid . '). File permissions will have to be repaired manually.', __CLASS__, array( 'error' => true ) );
						}
						return;
					}
					$halt_on_error = false;
				}

				$bad_folder_perm = true;

			}

			$files = array_filter( glob( $resolved_folder_path."/*" ), 'is_file' );

			foreach( $files as $file ) {

				$file = str_replace('//', '/', $file);

				if ( !$allowed_dir && is_writable( $file ) ) {
					$bad_file_perm = true;
					AC_Inspector::log( "Writable file `$file` is in a file directory that should not be writeable. Check your file permissions.", __CLASS__ );
				} else if ( $allowed_dir && !is_writable( $file ) ) {
					$bad_file_perm = true;
					AC_Inspector::log( "Unwritable file `$file` is in a file directory that should be writeable. Check your file permissions.", __CLASS__ );
				}

				if ( defined( 'WP_CLI' ) && WP_CLI && $bad_file_perm && $halt_on_error ) {
					$response = cli\choose( "Bad file permissions detected, continue the inspection", $choices = 'yn', $default = 'n' );
					if ( $response !== 'y' ) {
						if ( !posix_seteuid( $original_uid ) ) {
							AC_Inspector::log( 'Unable restore the owner of the current process (uid: ' . $original_uid . '). File permissions will have to be repaired manually.', __CLASS__, array( 'error' => true ) );
						}
						return;
					}
					$halt_on_error = false;
				}

			}

			if ( substr($folder, -2) == "/*" && ( !$halt_on_error || ( !$bad_folder_perm && !$bad_file_perm ) ) ) {

				$subfolders = glob($resolved_folder_path."/*", GLOB_ONLYDIR);

				if ( is_array($subfolders) && !empty($subfolders) ) {

					foreach(array_keys($subfolders) as $sf_key) {
						$subfolders[$sf_key] = trim($subfolders[$sf_key], '/') . '/*';
						if ( $f2c_key = array_search( $subfolders[$sf_key], $folders2check ) ) {
							unset($subfolders[$f2c_key]);
						}
					}

					if ( is_array($subfolders) && count($subfolders) > 0 && !empty($subfolders[0]) ) {
						self::inspect( $subfolders, $halt_on_error );
					}

				}

			}

		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {

			if ( !posix_seteuid( $original_uid ) ) {
				AC_Inspector::log( 'Unable restore the owner of the current process (uid: ' . $original_uid . '). File permissions will have to be repaired manually.', __CLASS__, array( 'error' => true ) );
			}

		}

		return;

	}

	private static function chown( $path, $owner = '', $group = '', $recursive = false, $verbose = false ) {

		if ( empty( $owner ) && empty( $group ) ) {
			return false;
		}

		$path = rtrim( $path, '/' );

		if ( is_link( $path ) ) {
			$path = readlink( $path );
		}

	    if ( is_dir( $path ) ) {

	    	if ( !empty( $owner ) ) {
	    		try {
	    			$chowned = @chown( $path, $owner );
			        if ( !$chowned ) {
			            throw new Exception( "Failed changing user ownership of directory '$path' to '$owner'" );
			        } else if ( $verbose ) {
			        	AC_Inspector::log( "Changed user ownership of directory '$path' to '$owner'", __CLASS__, array( 'success' => true ) );
			        }
			    } catch ( Exception $e ) {
					AC_Inspector::log( $e->getMessage(), __CLASS__, array( 'error' => true ) );
					$owner = '';
				}
		    }

		    if ( !empty( $group ) ) {
		    	try {
			        $chowned = @chown( $path, $group );
			        if ( !$chowned ) {
			            throw new Exception( "Failed changing group ownership of directory '$path' to '$group'" );
			        } else if ( $verbose ) {
			        	AC_Inspector::log( "Changed group ownership of directory '$path' to '$group'", __CLASS__, array( 'success' => true ) );
			        }
			    } catch ( Exception $e ) {
					AC_Inspector::log( $e->getMessage(), __CLASS__, array( 'error' => true ) );
					$group = '';
				}
		    }

		    if ( empty( $owner ) && empty( $group ) ) {
		    	return false;
		    }

		    $ownership_str = ( $owner ) ? 'user ' . $owner : '';
		    if ( !empty( $group ) ) {
			    if ( empty( $ownership_str ) ) {
			    	$ownership_str = 'group ' . $group;
			    } else {
			    	$ownership_str = ' and group ' . $group;
			    }
			}

	        $dh = opendir( $path );
	        while ( ( $file = readdir( $dh ) ) !== false ) {
	            if ( $file != '.' && $file != '..' && $file[0] != '.' ) { // skip self and parent pointing directories as well as hidden files/dirs
	                $fullpath = $path . '/' . $file;
	                if ( is_link( $fullpath ) ) {
						$fullpath = readlink( $fullpath );
					}
	                if ( $recursive || !is_dir( $fullpath ) ) {
	                	if ( self::chown( $fullpath, $owner, $group, $recursive ) ) {
	                		if ( is_dir( $fullpath ) && $verbose ) {
	                			AC_Inspector::log( "Changed ownership of files in '$fullpath' to $ownership_str", __CLASS__, array( 'success' => true ) );
	                		}
	                	} else {
	                		return false;
	                	}
	                }
	            }
	        }

	        if ( $verbose ) {
	        	AC_Inspector::log( "Changed ownership of files in '$path' to $ownership_str", __CLASS__, array( 'success' => true ) );
	        }

	        closedir( $dh );

	    } else {

	        if ( !empty( $owner ) ) {
	        	try {
			        $chowned = @chown( $path, $owner );
			        if ( !$chowned ) {
			            throw new Exception( "Failed changing user ownership of file '$path' to '$owner'" );
			        }
			    } catch ( Exception $e ) {
					AC_Inspector::log( $e->getMessage(), __CLASS__, array( 'error' => true ) );
					$owner = '';
				}
		    }

		    if ( !empty( $group ) ) {
		        try {
			        $chowned = @chown( $path, $group );
			        if ( !$chowned ) {
			            throw new Exception( "Failed changing group ownership of file '$path' to '$group'" );
			        }
			    } catch ( Exception $e ) {
					AC_Inspector::log( $e->getMessage(), __CLASS__, array( 'error' => true ) );
					$group = '';
				}
		    }

		    if ( empty( $owner ) && empty( $group ) ) {
		    	return false;
		    }

	    }

	    return true;

	}

	private static function chmod( $path, $filemode = 0644, $dirmode = 0755, $recursive = false, $verbose = false ) {

		$path = rtrim( $path, '/' );

		if ( is_link( $path ) ) {
			$path = readlink( $path );
		}

	    if ( is_dir( $path ) ) {

	    	$dirmode_str = decoct( $dirmode );
	    	$filemode_str = decoct( $filemode );

	    	try {
	    		$chmodded = @chmod( $path, $dirmode );
		        if ( !$chmodded ) {
		            throw new Exception( "Failed applying filemode '$dirmode_str' on directory '$path'" );
		        } else if ( $verbose ) {
		        	AC_Inspector::log( "Applied filemode '$dirmode_str' on directory '$path'", __CLASS__, array( 'success' => true ) );
		        }
		    } catch ( Exception $e ) {
				AC_Inspector::log( $e->getMessage(), __CLASS__, array( 'error' => true ) );
				return false;
			}

	        $dh = opendir( $path );
	        while ( ( $file = readdir( $dh ) ) !== false ) {
	            if ( $file != '.' && $file != '..' && $file[0] != '.' ) { // skip self and parent pointing directories as well as hidden files/dirs
	                $fullpath = $path . '/' . $file;
	                if ( is_link( $fullpath ) ) {
						$fullpath = readlink( $fullpath );
					}
	                if ( $recursive || !is_dir( $fullpath ) ) {
	                	if ( self::chmod( $fullpath, $filemode, $dirmode, $recursive ) ) {
	                		if ( is_dir( $fullpath ) && $verbose ) {
	                			AC_Inspector::log( "Applied filemode '$filemode_str' on files in '$fullpath'", __CLASS__, array( 'success' => true ) );
	                		}
	                	} else {
	                		return false;
	                	}
	                }
	            }
	        }

	        if ( $verbose ) {
	        	AC_Inspector::log( "Applied filemode '$filemode_str' on files in '$path'", __CLASS__, array( 'success' => true ) );
	        }

	        closedir( $dh );

	    } else {

	        $filemode_str = decoct( $filemode );

	        try {
	    		$chmodded = @chmod( $path, $filemode );
		        if ( !$chmodded ) {
		            throw new Exception( "Failed applying filemode '$filemode_str' on file '$path'" );
		        }
		    } catch ( Exception $e ) {
				AC_Inspector::log( $e->getMessage(), __CLASS__, array( 'error' => true ) );
				return false;
			}

	    }

	    return true;

	}

	public static function repair() {

		if ( !function_exists( 'posix_getuid' ) ) {
			AC_Inspector::log( 'Repairing file permissions requires a POSIX-enabled PHP server.', __CLASS__, array( 'error' => true ) );
			return;
		}

		if ( posix_getuid() !== 0 ) {
			AC_Inspector::log( 'Repairing file permissions must be performed as root.', __CLASS__, array( 'error' => true ) );
			return;
		}

		$group = '';
		$owner = '';

		if ( defined( 'HTTPD_USER' ) ) {
			$group = HTTPD_USER;
		} else {
			AC_Inspector::log( 'Unable to determine the user your web server is running as, define HTTPD_USER in your wp-config.php to correct this.', __CLASS__, array( 'log_level' => 'warning' ) );
		}

		if ( defined( 'FS_USER' ) ) {
			$owner = FS_USER;
		} else if ( defined( 'FTP_USER' ) ) {
			$owner = FTP_USER;
		} else {
			AC_Inspector::log( 'Unable to determine the appropriate file system owner, define FS_USER in your wp-config.php to correct this.', __CLASS__, array( 'log_level' => 'warning' ) );
		}

		if ( empty( $group ) && empty( $owner ) ) {
			WP_CLI::confirm( "Skip setting ownerships (chown) and attempt to repair file permissions (chmod) anyway?" );
		} else if ( empty( $group ) ) {
			WP_CLI::confirm( "Skip setting group permissions and attempt to set just user permissions instead?" );
		} else if ( empty( $owner ) ) {
			WP_CLI::confirm( "Skip setting user permissions and attempt to set just group permissions instead?" );
		}

		if ( !self::chown( self::$_real_abspath, $owner, $group, true, true ) ) {
			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				WP_CLI::confirm( "There where errors while trying to set file ownerships (chown), proceed with setting file permissions (chmod) anyway?" );
			} else {
				return false;
			}
		}

		self::chmod( self::$_real_abspath, 0644, 0755, true, true );

		foreach(self::$_options['allowed_dirs'] as $folder) {

			$folder_base = trim( str_replace( '/*', '', str_replace('//', '/', str_replace( self::$_real_abspath , '', $folder ) ) ), '/' );

			if ( is_link( self::$_real_abspath.'/'.$folder_base ) ) {
				$resolved_folder_path = readlink( self::$_real_abspath.'/'.$folder_base );
			} else {
				$resolved_folder_path = self::$_real_abspath.'/'.$folder_base;
			}

			if ( !self::$_force_default_allowed_dirs && !file_exists( $resolved_folder_path ) ) {
				continue;
			}

			$recursive = substr($folder, -2) == "/*" ? true : false;

			self::chmod( $resolved_folder_path, 0664, 0775, $recursive, true );

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

ACI_Routine_Check_File_Permissions::register();
