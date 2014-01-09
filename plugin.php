<?php
/*
Plugin Name: Angry Creative Inspector
Plugin URI: http://angrycreative.se
Description: Inspects and logs possible issues with your Wordpress installation.
Version: 0.2.3
Author: Robin Björklund, Sammy Nordström, Angry Creative AB
*/

if ( ! function_exists( 'is_plugin_active_for_network' ) )
    require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

if(!class_exists('AC_Inspector')) { 

	class AC_Inspector { 

		public $log_path = "";
		public $errors = array();

		private $_default_log_path = "";
		private $_log_levels = array();

		private $_plugin_options_url = "";

		private $_log_count= 0;

		private $_plugin_dir = "";
		private $_plugin_file = "";
		
		public function __construct() {

			$this->_plugin_dir = basename( dirname( __FILE__ ) );
			$this->_plugin_file = basename( __FILE__ );

			$this->_default_log_path = WP_PLUGIN_DIR.'/'.basename(__DIR__).'/inspection.log';

			if ( is_multisite() && is_plugin_active_for_network( $this->_plugin_dir."/".$this->_plugin_file ) ) {
				$this->_plugin_options_url = network_admin_url('settings.php').'?page=ac-inspector-settings';
			} else {
				$this->_plugin_options_url = admin_url('options-general.php').'?page=ac-inspector-settings';
			}

			$this->get_log_path();

			$this->check_log_path();

			$this->_log_levels = array(
				'notice',
				'warning',
				'fatal'
			);

			// Log functions
			add_action( 'activate_plugin', 		array( $this, 'plugin_changed'), 'activated' );
			add_action( 'deactivate_plugin',	array( $this, 'plugin_changed'), 'deactivated' );

			add_action( 'deactivate_blog',	array( $this, 'site_changed'), 'deactivated' );
			add_action( 'activate_blog',	array( $this, 'site_changed'), 'activated' );

			add_action( 'wp-mail.php',	array( $this, 'mail_sent') );

			// Settings
			if ( is_admin() ) {

				add_action( 'admin_init', array( $this, 'plugin_page_init' ) );

				if ( is_multisite() && is_plugin_active_for_network( $this->_plugin_dir."/".$this->_plugin_file ) ) {

					add_action( 'network_admin_menu', array( $this, 'add_plugin_page' ) );
            		add_action( 'network_admin_notices', array( $this, 'admin_error_notice' ) );

				} else {

            		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
            		add_action( 'admin_notices', array( $this, 'admin_error_notice' ) );

            	}

        	}

        	add_action('ac_inspector_event', array($this, 'inspect') );

		}

		/* Add Cron Job on activation, that test permissions etc. */
		public static function activate() { 

			wp_schedule_event( time(), 'daily', 'ac_inspector_event');

		}

		/* Clear job on deactivation */
		public static function deactivate() { 

			wp_clear_scheduled_hook( 'ac_inspector_event');
		
		}

		private function _get_option( $name ) {

			if ( is_multisite() && is_plugin_active_for_network( $this->_plugin_dir."/".$this->_plugin_file ) ) {

				return get_site_option( $name );

			} else {

				return get_option( $name );

			}
			
		}

		private function _add_option( $name, $value ) {

			if ( is_multisite() && is_plugin_active_for_network( $this->_plugin_dir."/".$this->_plugin_file ) ) {

				return add_site_option( $name, $value );

			} else {

				return add_option( $name, $value );

			}

		}

		private function _update_option($name, $value) {

			if ( is_multisite() && is_plugin_active_for_network( $this->_plugin_dir."/".$this->_plugin_file ) ) {

				return update_site_option( $name, $value );

			} else {

				return update_option( $name, $value );

			}

		}

		public function get_log_path() {

			$this->log_path = $this->_get_option('ac_inspector_log_path');

			if( empty($this->log_path) ) {

				// For backwards compatibility with versions <= 0.1.1
				$this->log_path = $this->_get_option( 'log_path' );

				if( !empty($this->log_path) ) {

					// Set new option variable name
					$this->_update_option( 'ac_inspector_log_path', $this->log_path );

				} else {

					$this->log_path = $this->_default_log_path;
					$this->_update_option( 'ac_inspector_log_path', $this->_default_log_path );

				}
				
			}

		}

		public function check_log_path() {

			if ( file_exists( $this->log_path ) ) {

				if ( !is_writable( $this->log_path ) ) {

					$this->errors[] = 'Log file exists but is not writable.';

					return false;

				}

			} else {

				if ( !is_dir( dirname($this->log_path) ) ) {

					$this->errors[] = 'Invalid log file directory.';

				} else {

					$file_handle = @fopen( $this->log_path, 'w' );

				    if ( !$file_handle ) {

	        			$this->errors[] = 'Unable to create log file.';

	        			return false;

	    			}

	    		}

			}

			return true;

		}

		public function clear_log() {

			if ( file_exists( $this->log_path ) ) {

				if ( is_writable( $this->log_path ) ) {

					unlink( $this->log_path );
					$this->check_log_path();

					global $current_user;
					get_currentuserinfo();

					$this->log('Log cleared by ' . $current_user->display_name);

					return true;

				}

			}

			return false;

		}

		public function admin_error_notice() {

			if ( sizeof( $this->errors ) > 0 ) {
			
				echo '<div class="error"><p>';
				echo "<strong>AC Inspector Error:</strong> ";

				foreach ($this->errors as $error) {
					echo $error . " \n";
				}

				printf('Please check your <a href="%s">AC Inspector Settings</a>.', $this->_plugin_options_url);

				echo '</div>';

			}

		}

		public function inspect() {

			// This function runs daily
			$this->check_wp_upload_permissions();
			$this->check_wp_file_permissions();
			$this->check_site_visibility();

			$this->log("Inspection completed with " . $this->_log_count . " remarks.");

		}

		public function check_wp_file_permissions(){

			$folders2check = array(
				'',
				'wp-admin',
				'wp-content',
				'wp-content/plugins',
				'wp-content/themes',
				'wp-includes'
			);

			foreach($folders2check as $folder) {

				$file_created = $this->test_create_file(ABSPATH.$folder.'/.ac_inspector_testfile') ? true : false;
				
				if(defined('DISALLOW_FILE_MODS') && true == DISALLOW_FILE_MODS) {	

					if($file_created) {
						$this->log('Was able to create a file in `/' . $folder . '` despite DISALLOW_FILE_MODS being set to true. Check your file permissions.', 'warning');
					}

				} else {

					if(!$file_created){
						$this->log('Was not able to create a file in `/' . $folder . '`. Check your file permissions.', 'fatal');
					}

				}

			}

		}

		public function test_create_file($path, $output = true) {

			$file_handle = @fopen($path, 'w');

		    if ( !$file_handle ) {

		    	// Could not open file for writing
    			return false;

			} else {

				// Test was successful, let's cleanup before returning true...
				fclose($file_handle);
				unlink($path);

				return true;

			}

		}	

		public function test_create_dir($path, $output = true) {

			$dir_created = mkdir($path, 0, true);

			if ( !$dir_created ) {

				// Failed creating dir...
    			return false;

			} else {

				// Test was successful, let's cleanup before returning true...
				rmdir($path);

    			return true;

			}

		}

		public function check_wp_upload_permissions() {

			$upload_dir = wp_upload_dir();
			$file_path = $upload_dir['basedir'] . "/.ac_inspector_testfile";

			try {

				$file_handle = @fopen($file_path, 'w');

			    if ( ! $file_handle ) {

        			throw new Exception("Unable to write files in /uploads, check your permissions!");
    			
    			} else {

					fclose($file_handle);
					unlink($file_path);

    			}

			} catch ( Exception $e ) {

				$this->log($e->getMessage(), 'fatal');

			}

		}

		public function check_site_visibility() {

			if ( is_multisite() && is_plugin_active_for_network( $this->_plugin_dir."/".$this->_plugin_file ) ) {

				global $wpdb;
				$site_blog_ids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM ".$wpdb->prefix."blogs where blog_id > 1"));

				if (is_array($site_blog_ids)) {
					foreach( $site_blog_ids AS $site_blog_id ) {

						if (intval($site_blog_id) > 0) {

							$visible = get_blog_details( $site_blog_id )->public;

							if ( !$visible ) {

								$this->log( 'Site '.$site_blog_id.' is not visible to search engines.', 'warning' );

							}

						}

					}
				}

			} else {

				$visible = get_option('blog_public');

				if ( !$visible ) {

					$this->log( 'The site is not visible to search engines.', 'warning' );

				}

			}
			

		}

		/*
			Logs if a plugin has been activated/deactivated
		*/
		public function plugin_changed($plugin){

			$user = wp_get_current_user();
			$site = (is_multisite()) ? ' on "' . get_blog_details(get_current_blog_id())->blogname . '"' : '';
			$usermsg = ($user instanceof WP_User) ? ' (user: '.$user->user_login.')' : '';
			$status = (current_filter() == 'activate_plugin') ? 'activated' : 'deactivated';

			$plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' .$plugin);

			$message = 'Plugin "'.$plugin_data['Name']. '" was ' . $status . $usermsg . $site;

			$this->log($message, 'warning');

		}

		/*
			Logs if a site has been activated/deactivated
		*/
		public function site_changed($site){

			$user = wp_get_current_user();
			$usermsg = ($user instanceof WP_User) ? ' (user: '.$user->user_login.')' : '';
			$status = (current_filter() == 'activate_blog') ? 'activated' : 'deactivated';
			$message = 'Site ' . get_blog_details($site)->blogname . ' (id: '.$site . ')' . ' was ' . $status . $usermsg . '\n';

			$this->log($message, 'warning');

		}

		public function mail_sent($mail) {

			$this->log($mail);

		}

		/* 
			Main log function that does the actual output
		*/
		private function log($message, $status = 'notice'){

			if ( !in_array($status, $this->_log_levels) ) {

				$status = 'notice';
			}

			$output = '['.date("d M, Y H:i:s").'] ['.get_class($this).'] [ ' .strtoupper($status). ' ] - ';

			if (is_array($message) || is_object($message)) {

            	error_log( $output );
				error_log( print_r( $message, true ) . "\n", 3, $this->log_path); 

        	} else {

				$message = $output . $message;
            	error_log( $message . "\n", 3, $this->log_path ); 

        	}

        	$this->_log_count++;

		}

		public function add_plugin_page() {

      		// This page will be under "Settings"
      		if ( is_multisite() && is_plugin_active_for_network( $this->_plugin_dir."/".$this->_plugin_file ) ) {
      			add_submenu_page('settings.php', 'AC Inspector', 'AC Inspector', 'manage_options', 'ac-inspector-settings', array( $this, 'create_admin_page' ) );
      		} else {
       			add_options_page( 'Settings Admin', 'AC Inspector', 'manage_options', 'ac-inspector-settings', array( $this, 'create_admin_page' ) );
       		}
    	
    	}

	    public function create_admin_page() {
        	
        	?>

			<div class="wrap">

			    <?php screen_icon(); ?>

			    <h2><?php _e('Inställningar'); ?></h2>		

			    <form method="post" action="<?php echo $this->_plugin_options_url; ?>">
			        <?php
				    	settings_fields( 'log_path_options' );	
				    	do_settings_sections( 'ac-inspector-settings' );
			        	submit_button(); 
			        ?>
			    </form>

			    <form name="post" action="<?php echo $this->_plugin_options_url; ?>" method="post" id="post">
			    	<button class="button" name="clear_log" value="true" />Clear log</button>
			    	<button class="button button-primary" name="inspect" value="true" />Inspect now!</button>
			    </form>

			</div>

			<?php

			if ( file_exists( $this->log_path ) && is_writable( $this->log_path ) ) { ?>

				<div class="latest">

					<h3>Latest</h3>

					<?php 

						$lines = array();

						$fp = fopen( $this->log_path, "r" );

						while(!feof($fp)) {

						   $line = fgets($fp, 4096);
						   if (preg_match('['.get_class($this).']', $line)) {

							   	array_push($lines, $line);

							   	if (count($lines)>15) {
							       array_shift($lines);
							    }

						   }

						}

						fclose($fp);

						echo '<ul>';
						foreach ($lines as $line) {
							echo '<li>' . $line . '</li>'; 
						} 

						echo '</ul>'; 

					?>

				</div> 

			<?php 
			
			}

	    }
		
	    public function plugin_page_init() {

	    	if ( $_SERVER['REQUEST_METHOD'] == "POST" ) {
	    		if ( isset( $_POST['inspect'] ) ) {
	    			$this->inspect();
	    		}
	    		if ( isset( $_POST['clear_log'] ) ) {
	    			$this->clear_log();
	    		}
	    	}

	        register_setting( 'log_path_options', 'array_key', array( $this, 'check_new_log_path' ) );
	            
	        add_settings_section(
	            'setting_section_id',
	            'Setting',
	            array( $this, 'print_section_info' ),
	            'ac-inspector-settings'
	        );	
	            
	        add_settings_field(
	            'log_path', 
	            'Relative path to log file', 
	            array( $this, 'create_an_id_field' ), 
	            'ac-inspector-settings',
	            'setting_section_id'			
	        );

	    }
		
	    public function check_new_log_path( $input ) {

            $path = $input['log_path'];

            $saved_option = $this->_get_option( 'ac_inspector_log_path' );

            if ( $saved_option === FALSE ) {

            	$this->_add_option( 'ac_inspector_log_path', $path );

            } else {

                $this->_update_option( 'ac_inspector_log_path', $path );

            }

	        return $path;

	    }
		
	    public function print_section_info() {

	        print 'Enter your setting below:';

	    }
		
	    public function create_an_id_field() {

	    	$this->log_path = $this->_get_option( 'ac_inspector_log_path' );

	    	if ( !empty($_POST['array_key']['log_path']) && $_POST['array_key']['log_path'] != $this->log_path ) {

	    		$this->check_new_log_path( $_POST['array_key'] );

	    	}

	        ?>
	        <input type="text" size="80" id="log_path_id" name="array_key[log_path]" value="<?php echo $this->_get_option( 'ac_inspector_log_path' ); ?>" />
			<?php

	    }

	} 

} 

if ( class_exists( 'AC_Inspector' ) ) {

	// Installation and uninstallation hooks
	register_activation_hook(__FILE__, array('AC_Inspector', 'activate'));
	register_deactivation_hook(__FILE__, array('AC_Inspector', 'deactivate'));

	$ac_inspector = new AC_Inspector();

}
