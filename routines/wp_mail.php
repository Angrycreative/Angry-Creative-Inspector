<?php

/*
 *	Logs when wp-mail is called
 */
function aci_log_wp_mail($mail) {

	// AC_Inspector::log($mail, __FUNCTION__);

	return "";

}

$options = array('log_level' => 'warning');
aci_register_routine("aci_log_wp_mail", $options, "wp_mail");
