<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package Include
 * @subpackage Clippy
 */

function clippy_start_page() {
	
	$return_tours = array();
	$return_tours['first_step_by_default'] = false;
	$return_tours['tours'] = array();
	
	//==================================================================
	//Help tour about the monitoring with a ping (step 3)
	//------------------------------------------------------------------
	$return_tours['tours']['monitoring_server_step_3'] = array();
	$return_tours['tours']['monitoring_server_step_3']['steps'] = array();
	$return_tours['tours']['monitoring_server_step_3']['steps'][] = array(
		'element'=> '#clippy',
		'intro' => __('Now you must go to modules, don\'t worry I teach you.')
		);
	$return_tours['tours']['monitoring_server_step_3']['steps'][] = array(
		'element'=> "img[alt='Modules']",
		'intro' => __('Please click in this tab.')
		);
	$return_tours['tours']['monitoring_server_step_3']['conf'] = array();
	$return_tours['tours']['monitoring_server_step_3']['conf']['show_bullets'] = 0;
	$return_tours['tours']['monitoring_server_step_3']['conf']['show_step_numbers'] = 0;
	$return_tours['tours']['monitoring_server_step_3']['conf']['next_help'] = 'monitoring_server_step_4';
	//==================================================================
	
	
	//==================================================================
	//Help tour about the monitoring with a ping (step 4)
	//------------------------------------------------------------------
	$return_tours['tours']['monitoring_server_step_4'] = array();
	$return_tours['tours']['monitoring_server_step_4']['steps'] = array();
	$return_tours['tours']['monitoring_server_step_4']['steps'][] = array(
		'element'=> '#clippy',
		'intro' => __('Now you must create the module, don\'t worry I teach you.')
		);
	$return_tours['tours']['monitoring_server_step_4']['steps'][] = array(
		'element'=> "#moduletype",
		'intro' => __('Choose the network server module.')
		);
	$return_tours['tours']['monitoring_server_step_4']['steps'][] = array(
		'element'=> "input[name='updbutton']",
		'intro' => __('And click in this button.')
		);
	$return_tours['tours']['monitoring_server_step_4']['conf'] = array();
	$return_tours['tours']['monitoring_server_step_4']['conf']['show_bullets'] = 0;
	$return_tours['tours']['monitoring_server_step_4']['conf']['show_step_numbers'] = 0;
	$return_tours['tours']['monitoring_server_step_4']['conf']['next_help'] = 'monitoring_server_step_5';
	//==================================================================
	
	
	//==================================================================
	//Help tour about the monitoring with a ping (step 5)
	//------------------------------------------------------------------
	$return_tours['tours']['monitoring_server_step_5'] = array();
	$return_tours['tours']['monitoring_server_step_5']['steps'] = array();
	$return_tours['tours']['monitoring_server_step_5']['steps'][] = array(
		'element'=> '#clippy',
		'intro' => __('Now you must create the module, don\'t worry I teach you.')
		);
	$return_tours['tours']['monitoring_server_step_5']['steps'][] = array(
		'element'=> '#clippy',
		'intro' => __('We are going to fill the form.')
		);
	$return_tours['tours']['monitoring_server_step_5']['steps'][] = array(
		'element'=> "#network_component_group",
		'intro' => __('Please choose the Network Management.')
		);
	$return_tours['tours']['monitoring_server_step_5']['steps'][] = array(
		'element'=> "#network_component",
		'intro' => __('And choose the component with the name "Host Alive".')
		);
	$return_tours['tours']['monitoring_server_step_5']['steps'][] = array(
		'element'=> "input[name='name']",
		'intro' => __('You can change the name.')
		);
	$return_tours['tours']['monitoring_server_step_5']['steps'][] = array(
		'element'=> "input[name='ip_target']",
		'intro' => __('Check if this IP is the address of your machine.')
		);
	$return_tours['tours']['monitoring_server_step_5']['steps'][] = array(
		'element'=> "input[name='crtbutton']",
		'intro' => __('And only to finish it is clicking this button.')
		);
	$return_tours['tours']['monitoring_server_step_5']['conf'] = array();
	$return_tours['tours']['monitoring_server_step_5']['conf']['show_bullets'] = 0;
	$return_tours['tours']['monitoring_server_step_5']['conf']['show_step_numbers'] = 0;
	$return_tours['tours']['monitoring_server_step_5']['conf']['next_help'] = 'monitoring_server_step_6';
	//==================================================================
	
	
	//==================================================================
	//Help tour about the monitoring with a ping (step 6)
	//------------------------------------------------------------------
	$return_tours['tours']['monitoring_server_step_6'] = array();
	$return_tours['tours']['monitoring_server_step_6']['steps'] = array();
	$return_tours['tours']['monitoring_server_step_6']['steps'][] = array(
		'element'=> '#clippy',
		'intro' => __('Now, your module is just created.<br/> And the status color is <b>blue</b>.<br/>This meaning of blue status is the module is not executed for first time.<br/>In the next seconds if there is not a problem, the status color will change to red or green.')
		);
	$return_tours['tours']['monitoring_server_step_6']['conf'] = array();
	$return_tours['tours']['monitoring_server_step_6']['conf']['show_bullets'] = 0;
	$return_tours['tours']['monitoring_server_step_6']['conf']['show_step_numbers'] = 0;
	//==================================================================
	
	return $return_tours;
}
?>