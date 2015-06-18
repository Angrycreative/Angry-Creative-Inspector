<?php
/*
Class name: ACI Settings
Version: 0.3.2
Depends: AC Inspector 0.5.x or newer
Author: Sammy Nordström, Angry Creative AB
*/

if ( class_exists('AC_Inspector') && !class_exists('ACI_Settings') ) { 

	class ACI_Settings extends AC_Inspector {

		private static $_plugin_options_url = "";
		private static $_plugin_actions_url = "";

		public function __construct() {

			parent::__construct();

			add_action( 'admin_init', array( $this, 'plugin_page_init' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			if ( is_multisite() && is_plugin_active_for_network( ACI_PLUGIN_BASENAME ) ) {

				self::$_plugin_options_url = network_admin_url('settings.php').'?page=ac-inspector';
				self::$_plugin_actions_url = network_admin_url('edit.php').'?action=aci_options';

				add_action( 'network_admin_menu', array( $this, 'add_plugin_page' ) );
        		add_action( 'network_admin_notices', array( $this, 'admin_notices' ) );

        		add_action( 
				    'network_admin_edit_aci_options', 
				    array($this, 'validate_options')
				);

			} else {

				self::$_plugin_options_url = admin_url('options-general.php').'?page=ac-inspector';
				self::$_plugin_actions_url = admin_url('options.php');

        		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        		add_action( 'admin_notices', array( $this, 'admin_notices' ) );

        	}

		}

		public function admin_notices() {

			if ( isset( $_GET['updated'] ) && isset( $_GET['page'] ) && 'ac-inspector' == $_GET['page'] ) {

				echo '<div class="updated"><p>';
				echo 'Your AC Inspector settings was updated successfully.';
				echo '</div>';

			}

			if ( sizeof( parent::$errors ) > 0 ) {
			
				echo '<div class="error"><p>';
				echo "<strong>AC Inspector Error:</strong> ";

				foreach (parent::$errors as $error) {
					echo $error . " \n";
				}

				printf('Please check your <a href="%s">AC Inspector Settings</a>.', self::$_plugin_options_url);

				echo '</div>';

			}

		}

		public function enqueue_styles() {

    		

    	}

    	public function enqueue_scripts($hook) {

    		if ("settings_page_ac-inspector" != $hook) {
    			return;
    		}

    		wp_enqueue_style('aci-main-style', plugins_url('css/main.css', ACI_PLUGIN_FILE), array(), '20140701');

    		wp_enqueue_style('aci-tabs-style', plugins_url('css/tabs.css', ACI_PLUGIN_FILE), array(), '20140115');

    		wp_enqueue_script('aci-main-script', plugins_url('js/main.js', ACI_PLUGIN_FILE), array('jquery'), '20140115', true);

	        wp_enqueue_script('aci-tabs-script', plugins_url('js/tabs.js', ACI_PLUGIN_FILE), array('jquery-ui-tabs'), '20140115', true);
	        
		}

		public function add_plugin_page() {

			add_action( 'admin_print_styles-ac-inspector', array(&$this, 'enqueue_styles') );
			add_action( 'admin_print_scripts-ac-inspector', array(&$this, 'enqueue_scripts') );

      		// This page will be under "Settings"
      		if ( is_multisite() && is_plugin_active_for_network( ACI_PLUGIN_BASENAME ) ) {
      			add_submenu_page('settings.php', 'AC Inspector', 'AC Inspector', 'manage_options', 'ac-inspector', array( $this, 'create_admin_page' ) );
      		} else {
       			add_options_page( 'Settings Admin', 'AC Inspector', 'manage_options', 'ac-inspector', array( $this, 'create_admin_page' ) );
       		}
    	
    	}

	    public function create_admin_page() { ?>

	    	<div class="wrap" id="aci-settings-wrap">

	    		<?php screen_icon(); ?>

			    <h2>Angry Creative Inspector <small>Version <?php echo ACI_PLUGIN_VERSION; ?> by <a href="http://angrycreative.se">Angry Creative AB</a></small></h2>	

				<div id="aci-tabs" class="ui-tabs ui-widget ui-widget-content ui-corner-all">

				    <ul class="hide-if-no-js ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all" role="tablist">

				    	<li id="tab-button-inspection-log" class="ui-state-default ui-corner-top ui-tabs-active ui-state-active" role="tab" tabindex="0" aria-controls="section-inspection-log" aria-labelledby="ui-id-1" aria-selected="true"><a href="#section-inspection-log" title="Inspection Log" class="ui-tabs-anchor" role="presentation" tabindex="-1" id="ui-id-1">Inspection Log</a></li>

				    	<li id="tab-button-general" class="ui-state-default ui-corner-top" role="tab" tabindex="1" aria-controls="section-general" aria-labelledby="ui-id-2" aria-selected="false"><a href="#section-general" title="General Settings" class="ui-tabs-anchor" role="presentation" tabindex="-2" id="ui-id-2">General Settings</a></li>

				    	<li id="tab-button-inspections" class="ui-state-default ui-corner-top" role="tab" tabindex="2" aria-controls="section-inspections" aria-labelledby="ui-id-3" aria-selected="false"><a href="#section-inspections" title="Inspection Routines" class="ui-tabs-anchor" role="presentation" tabindex="-3" id="ui-id-3">Inspection Routines</a></li>

				    	<li id="tab-button-actions" class="ui-state-default ui-corner-top" role="tab" tabindex="3" aria-controls="section-actions" aria-labelledby="ui-id-4" aria-selected="false"><a href="#section-actions" title="Action Routines" class="ui-tabs-anchor" role="presentation" tabindex="-4" id="ui-id-4">Action Routines</a></li>

				    </ul>

				    <div id="section-inspection-log" class="aci-section ui-tabs-panel ui-widget-content ui-corner-bottom" aria-labelledby="ui-id-1" role="tabpanel" aria-expanded="true" aria-hidden="false" style="display: block;">
				    	<div class="aci-section-content ui-tabs-panel-content">

					    	<?php

							if ( file_exists( parent::$log_path ) && is_writable( parent::$log_path ) ) { ?>

								<div class="latest">

									<h3>Latest log entries</h3>

									<?php 

										$lines = array();

										$fp = fopen( parent::$log_path, "r" );

										while(!feof($fp)) {

										   $line = fgets($fp, 4096);
										   if (preg_match('['.get_parent_class($this).']', $line)) {

											   	array_push($lines, $line);

											   	if (count($lines)>999) {
											       array_shift($lines);
											    }

										   }

										}

										fclose($fp);

										echo '<ul>';
										foreach ($lines as $line) {
											echo '<li>';
											if (strpos($line, '|') !== false) {
												$sublines = array_map('trim', explode('|', $line));
												$firstline = array_splice($sublines, 0, 1);
												echo $firstline[0];
												echo '<ul>';
												foreach ($sublines as $subline) {
													echo '<li>' . $subline . '</li>';
												}
												echo '</ul>';
											} else {
												echo $line; 
											}
											echo '</li>';
										} 

										echo '</ul>'; 

									?>

								</div> 

								<form name="post" action="<?php echo self::$_plugin_options_url; ?>" method="post" id="post">
									<button class="button" name="clear_log" value="true" />Clear log</button>
							    	<button class="button" name="download_log" value="true" />Download log</button>
							    	<button class="button button-primary" name="inspect" value="true" />Inspect now!</button>
							    </form>

							<?php } ?>

        				</div>
        			</div>

        			<div id="section-general" class="aci-section ui-tabs-panel ui-widget-content ui-corner-bottom" aria-labelledby="ui-id-2" role="tabpanel" aria-expanded="false" aria-hidden="true" style="display: none;">
        				<div class="aci-section-content ui-tabs-panel-content">

		        			<form method="post" action="<?php echo self::$_plugin_actions_url; ?>">
						        <?php
							    	settings_fields( 'ac-inspector' );	
							    	$this->do_settings_section( 'ac-inspector', 'aci_general_options' );
						        	submit_button(); 
						        ?>
						    </form>
	        
	        			</div>
        			</div>

        			<div id="section-inspections" class="aci-section ui-tabs-panel ui-widget-content ui-corner-bottom" aria-labelledby="ui-id-3" role="tabpanel" aria-expanded="false" aria-hidden="true" style="display: none;">
        				<div class="aci-section-content ui-tabs-panel-content">

		        			<form method="post" action="<?php echo self::$_plugin_actions_url; ?>">
						        <?php
							    	settings_fields( 'ac-inspector' );	
							    	$this->do_settings_section( 'ac-inspector', 'aci_inspection_routine_settings' );
						        	submit_button(); 
						        ?>
						    </form>
	        
	        			</div>
        			</div>

        			<div id="section-actions" class="aci-section ui-tabs-panel ui-widget-content ui-corner-bottom" aria-labelledby="ui-id-4" role="tabpanel" aria-expanded="false" aria-hidden="true" style="display: none;">
        				<div class="aci-section-content ui-tabs-panel-content">

		        			<form method="post" action="<?php echo self::$_plugin_actions_url; ?>">
						        <?php
							    	settings_fields( 'ac-inspector' );	
							    	$this->do_settings_section( 'ac-inspector', 'aci_wp_hook_routine_settings' );
						        	submit_button(); 
						        ?>
						    </form>
	        
	        			</div>
        			</div>

        		</div>

			</div>

			<?php

	    }
		
	    public function plugin_page_init() {

	    	if ( $_SERVER['REQUEST_METHOD'] == "POST" ) {
	    		if ( isset( $_POST['inspect'] ) ) {
	    			parent::inspect();
	    		}
	    		if ( isset( $_POST['clear_log'] ) ) {
	    			parent::clear_log();
	    		}
	    		if ( isset( $_POST['download_log'] ) ) {
	    			parent::download_log();
	    		}
	    	}

	    	register_setting( 'ac-inspector', 'aci_options', array( $this, 'validate_options' ) );
   
	        add_settings_section(
	            'aci_general_options',
	            'General Settings',
	            array( $this, 'print_general_settings_info' ),
	            'ac-inspector'
	        );	
	            
	        add_settings_field(
	            'log_path', 
	            'Path to log file', 
	            array( $this, 'log_path_field' ), 
	            'ac-inspector',
	            'aci_general_options'			
	        );

	        $inspection_routines = ACI_Routine_Handler::get_inspection_routines();

	        if ( is_array($inspection_routines) && count($inspection_routines) > 0 ) { 
	            
		        add_settings_section(
		            'aci_inspection_routine_settings',
		            'Routines on General Inspection',
		            array( $this, 'print_inspection_routine_settings_info' ),
		            'ac-inspector'
		        );

		        $routine_fields = array();	

				foreach( $inspection_routines as $routine ) { 

					$routine_settings_fields[$routine] = array();

					$routine_settings_fields[$routine]['id'] = $routine.'_settings_field';
					$routine_settings_fields[$routine]['title'] = ucwords(str_replace("_", " ", str_replace("wp_", "", str_replace("aci_", "", str_replace("routine_", "", strtolower($routine))))));
					$routine_settings_fields[$routine]['callback'] = array( &$this, 'routine_settings_field' );
					$routine_settings_fields[$routine]['page'] = 'ac-inspector';
					$routine_settings_fields[$routine]['section'] = 'aci_inspection_routine_settings';
					$routine_settings_fields[$routine]['args'] = array('routine' => $routine);

					$routine_settings_fields[$routine] = apply_filters( $routine.'_settings_field_args', $routine_settings_fields[$routine] );
							
				}

				foreach( $routine_settings_fields as $routine_field ) {

					add_settings_field(
			            $routine_field['id'], 
			            $routine_field['title'], 
			            $routine_field['callback'], 
			            $routine_field['page'],
			            $routine_field['section'],
			            $routine_field['args']		
			        );

				}

			}

			$wp_hook_routines = ACI_Routine_Handler::get_wp_hook_routines();

			if ( is_array($wp_hook_routines) && count($wp_hook_routines) > 0 ) { 
	            
		        add_settings_section(
		            'aci_wp_hook_routine_settings',
		            'Routines on WP Action/Filter Hooks',
		            array( $this, 'print_wp_hook_routine_settings_info' ),
		            'ac-inspector'
		        );	

				foreach( $wp_hook_routines as $routine ) { 

					$routine_settings_fields[$routine] = array();

					$routine_settings_fields[$routine]['id'] = $routine.'_settings_field';
					$routine_settings_fields[$routine]['title'] = ucwords(str_replace("_", " ", str_replace("wp_", "", str_replace("aci_", "", str_replace("routine_", "", strtolower($routine))))));
					$routine_settings_fields[$routine]['callback'] = array( &$this, 'routine_settings_field' );
					$routine_settings_fields[$routine]['page'] = 'ac-inspector';
					$routine_settings_fields[$routine]['section'] = 'aci_wp_hook_routine_settings';
					$routine_settings_fields[$routine]['args'] = array('routine' => $routine);

					$routine_settings_fields[$routine] = apply_filters( $routine.'_settings_field_args', $routine_settings_fields[$routine] );
							
				}

				foreach( $routine_settings_fields as $routine_field ) {

					add_settings_field(
			            $routine_field['id'], 
			            $routine_field['title'], 
			            $routine_field['callback'], 
			            $routine_field['page'],
			            $routine_field['section'],
			            $routine_field['args']		
			        );

				}

			}

	    }

	    public function validate_options( $input = array() ) {

	    	if ( $_REQUEST['download_log_file'] ) {
	    		$this->download_log_file();
	    		exit;
	    	}

	    	if ( empty( $input ) && $_SERVER['REQUEST_METHOD'] == "POST" && isset( $_POST['aci_options'] ) ) {
	    		$input = $_POST['aci_options'];
	    	}

            $saved_option = parent::get_option( 'ac_inspector_log_path' );

            if ( !empty( $input['log_path'] ) ) {

	            if ( $saved_option === FALSE ) {

	            	parent::add_option( 'ac_inspector_log_path', $input['log_path'] );

	            } else {

	                parent::update_option( 'ac_inspector_log_path', $input['log_path'] );

	            }

	        	parent::$log_path = $input['log_path'];

	        }

	        $routines = (array) ACI_Routine_Handler::get_all();

			foreach( array_keys($routines) as $routine ) { 

				$routine_settings = ACI_Routine_Handler::get_options($routine);

				if ( !empty( $input[$routine] ) && is_array( $input[$routine] ) ) {
	            	$new_routine_settings = $input[$routine];
	            } else {
	            	$new_routine_settings = array();
	            }

	            foreach($routine_settings as $opt => $val) {

	            	if ( isset( $new_routine_settings[$opt] ) ) {
	            		$routine_settings[$opt] = $new_routine_settings[$opt];
	            	}

	            }

	            $routine_settings = apply_filters( $routine.'_settings', $routine_settings );

	            ACI_Routine_Handler::set_options( $routine, $routine_settings );

	        }

	        if ( is_multisite() && is_plugin_active_for_network( ACI_PLUGIN_BASENAME ) ) {

	        	// redirect to settings page in network
				wp_redirect(
				    add_query_arg(
				        array( 'page' => 'ac-inspector', 'updated' => 'true' ),
				        network_admin_url( 'settings.php' )
				    )
				);

				exit;

			}

	        return $input;

	    }
		
	    public function print_general_settings_info() {

	        print 'These are your general settings:';

	    }

	    public function print_inspection_routine_settings_info() {

	        print 'Below you can set options for your inspection routines on scheduled and user activated inspections:';

	    }

	    public function print_wp_hook_routine_settings_info() {

	        print 'Below you can set options for your inspection routines triggered by Wordpress hooks:';

	    }
		
	    public function log_path_field() {

	        ?>
	        <input type="text" size="80" id="log_path" name="aci_options[log_path]" value="<?php echo parent::get_option( 'ac_inspector_log_path' ); ?>" />
			<?php

	    }

	    public function routine_settings_field($args) {

	    	$routine = $args['routine'];
	    	$log_levels = AC_Inspector::get_log_levels();
	    	$routine_settings = ACI_Routine_Handler::get_options($routine);

	    	if ( !empty( $routine_settings['description'] ) ) { ?>

	    		<tr valign="top">
				    <td colspan="2" class="description-row" scope="row" valign="top"><div class="howto"><?php echo $routine_settings['description']; ?></div></td>
				</tr>

	    	<?php }

	    	if ( isset( $routine_settings['site_specific_settings'] ) && is_multisite() && is_plugin_active_for_network( ACI_PLUGIN_BASENAME ) ) { ?>

	    		<tr valign="top">
				    <td scope="row" valign="top">Site specific settings</td>
				    <td>
		        		<select id="<?php echo $routine; ?>_site_specific_settings" name="aci_options[<?php echo $routine; ?>][site_specific_settings]">
							<option value="1"<?php echo ($routine_settings['site_specific_settings']) ? " selected" : ""; ?>>Yes</option>
							<option value="0"<?php echo ($routine_settings['site_specific_settings']) ? "" : " selected"; ?>>No</option>
						</select>
						<div class="howto">Submit your settings to enable/disable site-specific settings</div>
					</td>
				</tr>

	    	<?php }

		    if ( $routine_settings['site_specific_settings'] && is_multisite() && is_plugin_active_for_network( ACI_PLUGIN_BASENAME ) ) {

				global $wpdb;
				$site_blog_ids = $wpdb->get_col("SELECT blog_id FROM ".$wpdb->prefix."blogs");

				if ( is_array( $site_blog_ids ) ) {

					foreach( $site_blog_ids AS $site_blog_id ) {

						$sitename = get_blog_details( $site_blog_id )->blogname;

				    	?>

						<tr valign="top">
						    <td scope="row" valign="top">Log level on <?php echo $sitename; ?></td>
						    <td>
				        		<select id="<?php echo $routine; ?>_<?php echo $site_blog_id; ?>_log_level" name="aci_options[<?php echo $routine; ?>][<?php echo $site_blog_id; ?>][log_level]">
									<?php foreach( $log_levels as $level ) { ?>

										<option value="<?php echo $level; ?>"<?php echo ($level == $routine_settings[$site_blog_id]['log_level']) ? " selected" : ""; ?>><?php echo ucfirst($level); ?></option>

									<?php } ?>
								</select>
							</td>
						</tr>

						<?php

					}
				}

			} else {

				?>

				<tr valign="top">
				    <td scope="row">Log level</td>
				    <td>
						<select id="<?php echo $routine; ?>_log_level" name="aci_options[<?php echo $routine; ?>][log_level]">
							<?php foreach( $log_levels as $level ) { ?>

								<option value="<?php echo $level; ?>"<?php echo ($level == $routine_settings['log_level']) ? " selected" : ""; ?>><?php echo ucfirst($level); ?></option>

							<?php } ?>
						</select>
				    </td>
				</tr>

				<?php

			}

			do_action($routine.'_settings_field', $routine_settings, $args);

	    }

	    public function do_settings_section( $page, $section ) {

	        global $wp_settings_sections, $wp_settings_fields;
	
	        if ( ! isset( $wp_settings_sections[$page] ) )
	                return;

	        if ( ! isset( $wp_settings_sections[$page][$section] ) )
	                return;
	
	        $section = $wp_settings_sections[$page][$section];

            if ( $section['title'] )
                    echo "<h3>{$section['title']}</h3>\n";

            if ( $section['callback'] )
                    call_user_func( $section['callback'], $section );

            if ( ! isset( $wp_settings_fields ) || !isset( $wp_settings_fields[$page] ) || !isset( $wp_settings_fields[$page][$section['id']] ) )
                    continue;

            echo '<table class="form-table">';
            do_settings_fields( $page, $section['id'] );
            echo '</table>';

		}

	}

}
