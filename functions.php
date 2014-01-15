<?php

function aci_register_routine($routine, $options = array(), $action = "ac_inspection", $priority = 10, $accepted_args = 1) {

	ACI_Routine_Handler::add( $routine, $options, $action, $priority, $accepted_args );

}

function aci_deregister_routine($routine, $action = "", $prority = 10) {

	ACI_Routine_Handler::remove( $routine, $action, $priority );

}