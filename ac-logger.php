<?php
/*
Plugin Name: Angry Creative Inspector
Plugin URI: http://angrycreative.se
Description: Logs different events.
Version: 0.1.1
Author: Angry Creative AB
*/

if(!class_exists('AC_Inspector')) { 

	class AC_Inspector { 

		public $log_path;
		public $errors = array();
		public $log_levels = array();

		public function __construct() { 

			$this->checkPath();

			$this->log_levels = array(
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
			if ( is_admin() ){
            	add_action( 'admin_menu', array( $this, 'ac_add_plugin_page' ) );
            	add_action( 'admin_init', array( $this, 'ac_page_init' ) );
        	}
        	add_action('ac_inspector_event', array($this, 'ac_inspector_tests') );
		}
		/* Add Cron Job on activation, that test permissions etc. */
		public static function activate() { 
			wp_schedule_event( time(), 'daily', 'ac_inspector_event');

			$file = WP_PLUGIN_DIR . '/'. basename(__DIR__) .'/ac_debug.log';
			update_option( 'log_path' , WP_PLUGIN_DIR . '/'. basename(__DIR__) .'/ac_debug.log');
			if(!file_exists($file)){
				fopen($file , 'x') or exit("Unable to open file!");
			}
		}

		/* Clear job on deactivation */
		public static function deactivate() { 
			wp_clear_scheduled_hook( 'ac_inspector_event');
		}

		public function checkPath(){
			if('' == get_option('log_path')){
				$path = WP_PLUGIN_DIR . '/'. basename(__DIR__) .'/ac_debug.log';
				update_option( 'log_path' , WP_PLUGIN_DIR . '/'. basename(__DIR__) .'/ac_debug.log');
				if(!file_exists($path)){
					fopen($path , 'x') or exit("Unable to open file!");
				}
				if(file_exists($path)){
					if(is_writable($path)){
						update_option( 'log_path', $path );
					} else{
						$this->errors[] = 'File exists but is not writable.';;
						// update_option( 'log_path', $this->log_path );
					}
				} else {
					$this->errors[] = 'File does not exist.';
				}
				return false;
			}
			else if(get_option('log_path') && file_exists(get_option( 'log_path'))){
				if(is_writable(get_option( 'log_path'))){
					update_option( 'log_path', get_option('log_path') );
				} else{
					update_option( 'log_path', get_option('log_path'));
					$this->errors[] = 'File exists but is not writable.';
				}
				return false;

			} else {
				if(get_option('log_path') && !file_exists(get_option('log_path'))){
					$this->errors[] = 'File does not exist.';
				}
				return false;
			}
			return true;
		}
		public function ac_inspector_tests(){
			// This function runs daily
			$this->ac_checkUploadPermissions();
			$this->ac_checkPermissions();
		}

		public function ac_checkPermissions(){

			$folders2check = array(
				'',
				'wp-admin',
				'wp-content',
				'wp-content/plugins',
				'wp-content/themes',
				'wp-includes'
			);

			foreach($folders2check as $folder) {
				$file_created = $this->ac_createFile(ABSPATH.$folder.'/ac_testfile.txt') ? true : false;
				
				if(defined('DISALLOW_FILE_MODS') && true == DISALLOW_FILE_MODS){	
					if($file_created) {
						$this->ac_log('Was able to create file `' . $folder . '/ac_testfile.txt` despite constant DISALLOW_FILE_MODS is set to true, check permissions?', 'warning');
					}
				} else {
					if(!$file_created){
						$this->ac_log('Was not able to create file `' . $folder . '/ac_testfile.txt`. Check permissions.', 'fatal');
					}
				}
			}
		}
		public function ac_createFile($path, $output = true){
			try {
				$ourFileHandle = @fopen($path, 'w');
			    if (! $ourFileHandle) {
        			throw new Exception("Could not create file " . $path);
    			} else {
					fclose($ourFileHandle);
					unlink($path);
					return true;
    			}
			} catch(Exception $e){
				if($output){
					$this->ac_log($e->getMessage());
				}
				return false;
			}
		}		
		public function ac_createDir($path, $output = true){
			try {
				$dir_created = mkdir($path, 0, true);
				if ($dir_created) {
    				rmdir($path);
    				return true;
				}

			} catch(Exception $e){
				if($output){
					$this->ac_log($e->getMessage());
				}
				return false;
			}
		}
		public function ac_checkUploadPermissions(){
			$uploadDir = wp_upload_dir();
			$ourFileName = $uploadDir['basedir'] . "/ac_testfile.txt";
			try {
				$ourFileHandle = @fopen($ourFileName, 'w');
			    if (! $ourFileHandle) {
        			throw new Exception("Could not create file in /uploads, check your permissions!");
    			} else {
					fclose($ourFileHandle);
					unlink($ourFileName);
    			}
			} catch(Exception $e){
				$this->ac_log($e->getMessage(), 'fatal');
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

			$this->ac_log($message, 'warning');
			$this->ac_checkUploadPermissions();

		}

		/*
			Logs if a site has been activated/deactivated
		*/
		public function site_changed($site){

			$user = wp_get_current_user();
			$usermsg = ($user instanceof WP_User) ? ' (user: '.$user->user_login.')' : '';
			$status = (current_filter() == 'activate_blog') ? 'activated' : 'deactivated';
			$message = 'Site ' . get_blog_details($site)->blogname . ' (id: '.$site . ')' . ' was ' . $status . $usermsg . '\n';

			$this->ac_log($message, 'warning');
		}
		public function mail_sent($mail){
			$this->ac_log($mail);
		}

		/* 
			Main log function that does the actual output
		*/
		private function ac_log($message, $status = 'notice'){

			if(!in_array($status, $this->log_levels)){
				$status = 'notice';
			}
			$output = '['.date("d M, Y H:i:s").'] ['.get_class($this).'] [ ' .strtoupper($status). ' ] - ';

			if (is_array($message) || is_object($message)) {
            	error_log($output);
				error_log(print_r($message) . "\n", 3, get_option( 'log_path')); 
        	} else {
				$message = $output . $message;
            	error_log($message . "\n", 3, get_option( 'log_path')); 
        	}
		}
		public function ac_add_plugin_page(){
      		// This page will be under "Settings"
       		add_options_page( 'Settings Admin', 'AC Logger', 'manage_options', 'logger-settings-admin', array( $this, 'ac_create_admin_page' ) );
    	}

	    public function ac_create_admin_page() {
        ?>
			<div class="wrap">
			    <?php screen_icon(); ?>
			    <h2><?php _e('Inställningar'); ?></h2>			
			    <form method="post" action="options.php">
			        <?php
				    settings_fields( 'log_path_options' );	
				    do_settings_sections( 'logger-settings-admin' );
					?>
			        <?php submit_button(); ?>
			    </form>
			</div>
			<?php
			$file = get_option( 'log_path');
			if(file_exists($file) && is_writable($file)){ ?>
				<div class="latest">
					<h3>Latest</h3>
					<?php 
						$lines=array();
						$fp = fopen(get_option('log_path'), "r");
						while(!feof($fp))
						{
						   $line = fgets($fp, 4096);
						   if(preg_match('['.get_class($this).']', $line)){

						   array_push($lines, $line);
						   if (count($lines)>15)
						       array_shift($lines);
						   }
						}
						fclose($fp);

						echo '<ul>';
						foreach ($lines as $line) {

							echo '<li>' . $line . '</li>'; 
						} 
						echo '</ul>'; ?>
				</div> 
				<?php 
			} ?>
		<?php
	    }
		
	    public function ac_page_init() {		
	        register_setting( 'log_path_options', 'array_key', array( $this, 'ac_check_path' ) );
	            
	            add_settings_section(
	            'setting_section_id',
	            'Setting',
	            array( $this, 'ac_print_section_info' ),
	            'logger-settings-admin'
	        );	
	            
	        add_settings_field(
	            'log_path', 
	            'Relative path to log file', 
	            array( $this, 'ac_create_an_id_field' ), 
	            'logger-settings-admin',
	            'setting_section_id'			
	        );		
	    }
		
	    public function ac_check_path( $input ) {
            $path = $input['log_path'];			
            if ( get_option( 'log_path' ) === FALSE ) {
                add_option( 'log_path', $path );
            } else {
                update_option( 'log_path', $path );
            }
	        return $path;
	    }
		
	    public function ac_print_section_info(){
	        print 'Enter your setting below:';
	    }
		
	    public function ac_create_an_id_field(){
	        ?><input type="text" size="80" id="log_path_id" name="array_key[log_path]" value="<?php echo get_option( 'log_path' ); ?>" />
			
			<?php
			if(sizeof($this->errors) > 0){?>
				<p class="error"><strong>Error:</strong> <?php
			} 
			foreach ($this->errors as $error) {
				echo $error . "\n";
			}
			?></p>
	        <?php
	    }
	} 
} 
if(class_exists('AC_Inspector'))
{
	// Installation and uninstallation hooks
	register_activation_hook(__FILE__, array('AC_Inspector', 'activate'));
	register_deactivation_hook(__FILE__, array('AC_Inspector', 'deactivate'));

	$ac_inspector = new AC_Inspector();
}