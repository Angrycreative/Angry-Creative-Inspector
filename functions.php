<?php

function aci_register_routine($routine, $options = array(), $action = "ac_inspection", $priority = 10, $accepted_args = 1) {

	ACI_Routine_Handler::add( $routine, $options, $action, $priority, $accepted_args );

}

function aci_deregister_routine($routine, $action = "", $prority = 10) {

	ACI_Routine_Handler::remove( $routine, $action, $priority );

}

function aci_release_tier_aware() {

	return ACI_Routine_Handler::release_tier_aware();

}

function aci_get_release_tier() {

	return ACI_Routine_Handler::get_release_tier();

}

function aci_is_release_tier( $tier ) {

	return ACI_Routine_Handler::is_release_tier( $tier );

}