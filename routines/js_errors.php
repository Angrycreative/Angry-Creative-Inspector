<?php

class ACI_Routine_Log_JS_Errors {

	const LOG_LEVEL = "warning";

	const DESCRIPTION = "Tries to catch and log javascript errors. Please note that it may not always be able to.";

	public static function register() {

		$options = array( 'log_level' => self::LOG_LEVEL,
						  'description' => self::DESCRIPTION,
						  'inspection_method' => 'setup',
						  'site_specific_settings' => 0 );
		
		if (is_admin()) {
			aci_register_routine( __CLASS__, $options, 'admin_init' );
		} else {
			aci_register_routine( __CLASS__, $options, 'init' );
		}

	}

	public static function setup() {

		// Add our js to the head of the page, i.e. as early as possible
		if (is_admin()) {
			add_action("admin_head", array( __CLASS__, "add_js_to_header" ), 1 );
		} else {
			add_action("wp_head", array( __CLASS__, "add_js_to_header" ), 1 );
		}

		// Add ajax action
		add_action('wp_ajax_log_js_error', array( __CLASS__, "log_error"), 1 );
		add_action('wp_ajax_nopriv_log_js_error', array( __CLASS__, "log_error"), 1 );

	}

	public static function add_js_to_header() {
		?>
		<script type="text/javascript">
		window.onerror = function(errorMsg, url, lineNumber, column, errorObj) {
			// console.log('Error message: '+m+'\nURL: '+u+'\nLine Number: '+l);
			if (encodeURIComponent) {
				var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>',
				requri = '<?php echo $_SERVER['REQUEST_URI']; ?>',
				img = new Image(1,1);
				img.src = ajaxurl + 
						  "?action=log_js_error&m=" + 
						  encodeURIComponent(errorMsg) + 
						  "&u=" + encodeURIComponent(url) + 
						  "&l=" + encodeURIComponent(lineNumber) + 
						  "&c=" + encodeURIComponent(column) + 
						  "&r=" + encodeURIComponent(requri) + 
						  "&ua=" + encodeURIComponent(navigator.userAgent) +
						  "&e=" + encodeURIComponent(errorObj);
			}
			return false;
		}
		</script>
		<?php
	}

	public static function log_error() {

		$msg = isset( $_GET["m"] ) ? $_GET["m"] : "";
		$url = isset( $_GET["u"] ) ? $_GET["u"] : "";
		$line = isset( $_GET["l"] ) ? $_GET["l"] : "";
		$col = isset( $_GET["c"] ) ? $_GET["c"] : "";
		$r = isset( $_GET["r"] ) ? $_GET["r"] : "";
		$ua = isset( $_GET["ua"] ) ? $_GET["ua"] : "";
		$err = isset( $_GET["e"] ) ? $_GET["e"] : "";

		if ( strpos( $msg, ':' ) !== false ) {
			$msg = trim( substr( $msg, strpos( $msg, ':' ) + 1 ) );
		}

		$url = str_replace( home_url(), '', $url );

		$full_msg = "JS Error: " . $msg . " on " . $url;

		if ( !empty($line) ) {
		 	$full_msg .= ", line " . $line;
		}

		if ( !empty($col) ) {
			$full_msg .= ", column " . $col . ".";
		} 

		if ( !empty($r) ) {
			$full_msg .= " | Requested URI: " . $r;
		} 

		if ( !empty($ua) ) {
			$full_msg .= " | User Agent: " . $ua;
		} 

		if ( !empty($err) ) {
			$full_msg .= " | Stacktrace: " . $err;
		} 

		AC_Inspector::log( $full_msg, __CLASS__ );

	}

}

ACI_Routine_Log_JS_Errors::register();

