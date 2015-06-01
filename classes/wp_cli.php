<?php

/*
Class name: Angry Inspector Command
Version: 0.1
Depends: AC Inspector 0.6
Author: Sammy Nordström, Angry Creative AB
*/

class Angry_Inspector_Command extends WP_CLI_Command {

    /**
     * Performs a full inspection and outputs the inspection log.
     *
     * ## OPTIONS
     *
     * [<routine>]
     * : The inspection routine to perform.
     *
     * ## EXAMPLES
     *
     *     wp angry-inspector inspect write-permissions
     *
     * @synopsis [--force] [<routine>] [<other-routine>]
     */
    function inspect( $routines = array(), $assoc_args ) {

    	$all_routines = ACI_Routine_Handler::get_inspection_routines();
    	$all_routine_slugs = array();
        $force_inspect = true;

    	foreach( $all_routines as $key => $routine ) {
    		$all_routine_slugs[$key] = str_replace( '_', '-', str_replace( 'aci_routine_', '', str_replace('aci_routine_check_', '', strtolower( $routine ) ) ) );
    	}

    	if ( empty( $routines ) || !is_array( $routines ) || 0 == count( $routines ) ) {
            $force_inspect = false;
    		$routines = $all_routine_slugs;
    	}

        if ( $assoc_args['force'] ) {
            $force_inspect = true;
        }

        foreach( $routines as $routine ) {

        	if ( in_array( $routine, $all_routine_slugs ) ) {

                $total_log_count = AC_Inspector::$log_count;

        		$routine_key = array_search( $routine, $all_routine_slugs );
        		$routine_options = ACI_Routine_Handler::get_options( $all_routines[$routine_key] );
        		$inspection_method = ACI_Routine_Handler::get_inspection_method( $all_routines[$routine_key], $routine_options );

                $enabled_routine = false;

                if ( $force_inspect ) {
                    $enabled_routine = true;
                    ACI_Routine_Handler::force_enable( $all_routines[$routine_key] );
                } else {
                    if ( !empty( $routine_options['site_specific_settings'] ) && is_multisite() && is_plugin_active_for_network( ACI_PLUGIN_BASENAME ) ) {
                        $current_site_id = get_current_blog_id();
                        if ( $routine_options[$current_site_id]['log_level'] != 'ignore' ) {
                            $enabled_routine = true;
                        }
                    } else if ( $routine_options['log_level'] != 'ignore' ) {
                        $enabled_routine = true;
                    }
                }

                if ( !$enabled_routine ) {
                    echo "Skipping disabled routine $routine...\n\n";
                    continue;
                }

        		if ( empty( $inspection_method ) ) {
        			WP_CLI::error( "Failed to determine the inspection method for $routine." );
        		}

        		if ( is_array( $inspection_method ) ) {
        			if ( class_exists( $inspection_method[0] ) && method_exists( $inspection_method[0], $inspection_method[1] ) ) {
		        		echo "Calling inspection method $routine...\n";
		        		call_user_func( $inspection_method );
        			} else {
        				WP_CLI::error( "Failed to load the inspection method for $routine." );
        			}
        		} else if ( function_exists( $inspection_method ) ) {
        			echo "Calling inspection method $routine...\n";
        			call_user_func( $inspection_method );
				} else {
					WP_CLI::error( "Failed to load the inspection method for $routine." );
					break;
				}

                $routine_log_count = AC_Inspector::$log_count - $total_log_count;

        		WP_CLI::success( "Inspected $routine with $routine_log_count remark(s).\n" );

        	} else {

        		WP_CLI::error( "Unrecognized inspection routine '$routine'." );

        	}

        }

    }

    /**
     * If applicable, attempt to repair what is broken. ** WARNING! Do not attempt without doing a backup first! **
     *
     * ## OPTIONS
     *
     * [<routine>]
     * : Try to repair what is broken according to specific inspection routine
     *
     * ## EXAMPLES
     *
     *     wp angry-inspector repair write-permissions
     *
     * @synopsis [--force] [<routine>] [<other-routine>]
     */
    function repair( $routines = array(), $assoc_args ) {

        $all_routines = ACI_Routine_Handler::get_inspection_routines();
        $all_routine_slugs = array();
        $force_repair = true;

        foreach( $all_routines as $key => $routine ) {
            $all_routine_slugs[$key] = str_replace( '_', '-', str_replace( 'aci_routine_', '', str_replace('aci_routine_check_', '', strtolower( $routine ) ) ) );
        }

        if ( empty( $routines ) || !is_array( $routines ) || 0 == count( $routines ) ) {
            $force_repair = false;
            $routines = $all_routine_slugs;
        }

        if ( $assoc_args['force'] ) {
            $force_repair = true;
        }

        foreach( $routines as $routine ) {

            if ( in_array( $routine, $all_routine_slugs ) ) {

                $total_log_count = AC_Inspector::$log_count;
                $total_error_count = AC_Inspector::$error_count;
                $total_success_count = AC_Inspector::$success_count;

                $routine_key = array_search( $routine, $all_routine_slugs );
                $routine_options = ACI_Routine_Handler::get_options( $all_routines[$routine_key] );
                $repair_method = ACI_Routine_Handler::get_repair_method( $all_routines[$routine_key], $routine_options );

                $enabled_routine = false;

                if ( $force_repair ) {
                    $enabled_routine = true;
                    ACI_Routine_Handler::force_enable( $all_routines[$routine_key] );
                } else {
                    if ( !empty( $routine_options['site_specific_settings'] ) && is_multisite() && is_plugin_active_for_network( ACI_PLUGIN_BASENAME ) ) {
                        $current_site_id = get_current_blog_id();
                        if ( $routine_options[$current_site_id]['log_level'] != 'ignore' ) {
                            $enabled_routine = true;
                        }
                    } else if ( $routine_options['log_level'] != 'ignore' ) {
                        $enabled_routine = true;
                    }
                }

                if ( !$enabled_routine ) {
                    echo "Skipping disabled routine $routine...\n\n";
                    continue;
                }

                if ( empty( $repair_method ) ) {
                    WP_CLI::error( "No repair method for $routine, skipping..." );
                }

                if ( is_array( $repair_method ) ) {
                    if ( class_exists( $repair_method[0] ) && method_exists( $repair_method[0], $repair_method[1] ) ) {
                        echo "Calling repair method for $routine...\n";
                        call_user_func( $repair_method );
                    } else {
                        WP_CLI::error( "Failed to load the repair method for $routine." );
                    }
                } else if ( function_exists( $repair_method ) ) {
                    echo "Calling repair method for $routine...\n";
                    call_user_func( $repair_method );
                } else {
                    WP_CLI::error( "Failed to load the repair method for $routine." );
                    break;
                }

                $routine_log_count = AC_Inspector::$log_count - $total_log_count;
                $routine_error_count = AC_Inspector::$error_count - $total_error_count;
                $routine_success_count = AC_Inspector::$success_count - $total_success_count;

                if ( $routine_error_count > 0 ) {
                    WP_CLI::error( "Repair method for routine '$routine' yielded $routine_error_count error(s).\n" );
                } else if ( $routine_success_count > 0 || $routine_log_count > 0 ) {
                    WP_CLI::success( "Successfully performed repair method for routine '$routine' with no errors.\n" );
                } else {
                    WP_CLI::success( "Nothing seems broken. If it ain't broke, don't fix it.\n" );
                }

            } else {

                WP_CLI::error( "Unrecognized repair method for routine '$routine'." );

            }

        }

    }

}

WP_CLI::add_command( 'angry-inspector', 'Angry_Inspector_Command' );
