<?php

class ACI_Routine_Log_JS_Errors {

	const LOG_LEVEL = 'warning';

	public static function register() {

		$options = array( 'log_level' => self::LOG_LEVEL,
						  'inspection_method' => 'setup' );
		
		aci_register_routine( __CLASS__, $options, 'init' );
		aci_register_routine( __CLASS__, $options, 'admin-init' );

	}

	public static function setup() {

		// Add our js to the head of the page, i.e. as early as possible
		add_action("wp_head", array( __CLASS__, "add_js_to_header" ), 1 );
		add_action("admin_head", array( __CLASS__, "add_js_to_header" ), 1 );

		// Add ajax action
		add_action('wp_ajax_log_js_error', array( __CLASS__, "log_error"), 1 );
		add_action('wp_ajax_nopriv_log_js_error', array( __CLASS__, "log_error"), 1 );

	}

	public static function add_js_to_header() {
		?>
		<script type="text/javascript">
		window.onerror = function(m, u, l) {
			// console.log('Error message: '+m+'\nURL: '+u+'\nLine Number: '+l);
			if (encodeURIComponent) {
				var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>',
					img = new Image(1,1);
				img.src = ajaxurl + "?action=log_js_error&m=" + encodeURIComponent(m) + "&u=" + encodeURIComponent(u) + "&l=" + encodeURIComponent(l);
			}
			return true;
		}
		</script>
		<?php
	}

	public static function log_error() {

		$msg = isset( $_GET["m"] ) ? $_GET["m"] : "";
		$url = isset( $_GET["u"] ) ? $_GET["u"] : "";
		$line = isset( $_GET["l"] ) ? $_GET["l"] : "";

		$full_msg = $msg . " on " . $url . ":" . $line;

		AC_Inspector::log( $full_msg, __CLASS__ );

	}

}

ACI_Routine_Log_JS_Errors::register();

