<?php
/*
Class name: ACI Settings
Version: 0.1.1
Depends: AC Inspector 0.3.x
Author: Sammy Nordström, Angry Creative AB
*/

if ( class_exists('AC_Inspector') && !class_exists('ACI_Settings') ) { 

	class ACI_Settings extends AC_Inspector {

		private static $_plugin_options_url = "";
		private static $_plugin_actions_url = "";

		public function __construct() {

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

			if ( 'ac-inspector' == $_GET['page'] && isset( $_GET['updated'] ) ) {

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

    		wp_enqueue_style('aci-tabs-style', plugins_url('css/tabs.css', ACI_PLUGIN_FILE), array(), '20140115');

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

			    <h2>AC Inspector</h2>	

				<div id="aci-tabs" class="ui-tabs ui-widget ui-widget-content ui-corner-all">

				    <ul class="hide-if-no-js ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all" role="tablist">

				    	<li id="tab-button-inspection-log" class="ui-state-default ui-corner-top ui-tabs-active ui-state-active" role="tab" tabindex="0" aria-controls="section-inspection-log" aria-labelledby="ui-id-1" aria-selected="true"><a href="#section-inspection-log" title="Inspection Log" class="ui-tabs-anchor" role="presentation" tabindex="-1" id="ui-id-1">Inspection Log</a></li>

				    	<li id="tab-button-settings" class="ui-state-default ui-corner-top" role="tab" tabindex="0" aria-controls="section-settings" aria-labelledby="ui-id-2" aria-selected="false"><a href="#section-settings" title="Settings" class="ui-tabs-anchor" role="presentation" tabindex="-2" id="ui-id-2">Settings</a></li>

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

								<form name="post" action="<?php echo self::$_plugin_options_url; ?>" method="post" id="post">
							    	<button class="button" name="clear_log" value="true" />Clear log</button>
							    	<button class="button button-primary" name="inspect" value="true" />Inspect now!</button>
							    </form>

							<?php } ?>

        				</div>
        			</div>

        			<div id="section-settings" class="aci-section ui-tabs-panel ui-widget-content ui-corner-bottom" aria-labelledby="ui-id-2" role="tabpanel" aria-expanded="false" aria-hidden="true" style="display: none;">
        				<div class="aci-section-content ui-tabs-panel-content">

		        			<form method="post" action="<?php echo self::$_plugin_actions_url; ?>">
						        <?php
							    	settings_fields( 'ac-inspector' );	
							    	do_settings_sections( 'ac-inspector' );
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
	    	}

	    	register_setting( 'ac-inspector', 'aci_options', array( $this, 'validate_options' ) );
   
	        add_settings_section(
	            'aci_general_options',
	            'General',
	            array( $this, 'print_log_path_info' ),
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
		            'aci_inspection_routine_options',
		            'Routines on General Inspection',
		            array( $this, 'print_inspection_routine_options_info' ),
		            'ac-inspector'
		        );	

				foreach( $inspection_routines as $routine ) { 

					$routine_label = ucwords(str_replace("_", " ", str_replace("wp_", "", str_replace("aci_", "", $routine))));
					$routine_options_key = ACI_Routine_Handler::routine_options_key($routine);

					add_settings_field(
			            $routine.'_log_level', 
			            $routine_label, 
			            array( $this, 'log_level_field' ), 
			            'ac-inspector',
			            'aci_inspection_routine_options',
			            array('routine' => $routine)		
			        );
							
				}

			}

			$wp_hook_routines = ACI_Routine_Handler::get_wp_hook_routines();

			if ( is_array($wp_hook_routines) && count($wp_hook_routines) > 0 ) { 
	            
		        add_settings_section(
		            'aci_wp_hook_routine_options',
		            'Routines on WP Hooks',
		            array( $this, 'print_wp_hook_routine_options_info' ),
		            'ac-inspector'
		        );	

				foreach( $wp_hook_routines as $routine ) { 

					$routine_label = ucwords(str_replace("_", " ", str_replace("wp_", "", str_replace("aci_", "", $routine))));
					$routine_options_key = ACI_Routine_Handler::routine_options_key($routine);

					add_settings_field(
			            $routine.'_log_level', 
			            $routine_label, 
			            array( $this, 'log_level_field' ), 
			            'ac-inspector',
			            'aci_wp_hook_routine_options',
			            array('routine' => $routine)		
			        );
							
				}

			}

	    }

	    public function validate_options( $input = array() ) {

	    	if (empty($input) && $_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['aci_options'])) {
	    		$input = $_POST['aci_options'];
	    	}

            $saved_option = parent::get_option( 'ac_inspector_log_path' );

            if ( $saved_option === FALSE ) {

            	parent::add_option( 'ac_inspector_log_path', $input['log_path'] );

            } else {

                parent::update_option( 'ac_inspector_log_path', $input['log_path'] );

            }

            parent::$log_path = $input['log_path'];

	        $routines = ACI_Routine_Handler::get_all();

			foreach( array_keys($routines) as $routine ) { 

				$routine_options = ACI_Routine_Handler::get_options($routine);
	            $new_routine_options = $input[$routine];

	            foreach($routine_options as $opt => $val) {

	            	if (isset($new_routine_options[$opt])) {
	            		$routine_options[$opt] = $new_routine_options[$opt];
	            	}

	            }

	            ACI_Routine_Handler::set_options( $routine, $routine_options );

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
		
	    public function print_log_path_info() {

	        print 'These are your general settings:';

	    }

	    public function print_inspection_routine_options_info() {

	        print 'Below you can set options for your inspection routines on scheduled and user activated inspections:';

	    }

	    public function print_wp_hook_routine_options_info() {

	        print 'Below you can set options for your inspection routines triggered by Wordpress hooks:';

	    }
		
	    public function log_path_field() {

	        ?>
	        <input type="text" size="80" id="log_path" name="aci_options[log_path]" value="<?php echo parent::get_option( 'ac_inspector_log_path' ); ?>" />
			<?php

	    }

	    public function log_level_field($args) {

	    	$routine = $args['routine'];
	    	$log_levels = AC_Inspector::get_log_levels();
	    	$routine_options = ACI_Routine_Handler::get_options($routine);

			?>

			<tr valign="top">
			    <td scope="row">Log level</td>
			    <td>
					<select id="<?php echo $routine; ?>_log_level" name="aci_options[<?php echo $routine; ?>][log_level]">
						<?php foreach( $log_levels as $level ) { ?>

							<option value="<?php echo $level; ?>"<?php echo ($level == $routine_options['log_level']) ? " selected" : ""; ?>><?php echo ucfirst($level); ?></option>

						<?php } ?>
					</select>
			    </td>
			</tr>

			<?php

	    }

	}

}
