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
	//Help tour about the monitoring with a ping (step 1)
	//------------------------------------------------------------------
	$return_tours['tours']['monitoring_server_step_1'] = array();
	$return_tours['tours']['monitoring_server_step_1']['steps'] = array();
	$return_tours['tours']['monitoring_server_step_1']['steps'][] = array(
		'element'=> '#clippy',
		'intro' => __('I show how to ping a server.')
		);
	$return_tours['tours']['monitoring_server_step_1']['steps'][] = array(
		'element'=> 'input[name="search"]',
		'intro' => __('Please type a agent to save the modules for monitoring a server.')
		);
	$return_tours['tours']['monitoring_server_step_1']['steps'][] = array(
		'element'=> 'input[name="srcbutton"]',
		'intro' => __('Maybe if you typped correctly the name, you can see the agent.')
		);
	$return_tours['tours']['monitoring_server_step_1']['conf'] = array();
	$return_tours['tours']['monitoring_server_step_1']['conf']['show_bullets'] = 0;
	$return_tours['tours']['monitoring_server_step_1']['conf']['show_step_numbers'] = 1;
	$return_tours['tours']['monitoring_server_step_1']['conf']['next_help'] = 'monitoring_server_step_2';
	//==================================================================
	
	
	//==================================================================
	//Help tour about the monitoring with a ping (step 2)
	//------------------------------------------------------------------
	$return_tours['tours']['monitoring_server_step_2'] = array();
	$return_tours['tours']['monitoring_server_step_2']['steps'] = array();
	$return_tours['tours']['monitoring_server_step_2']['steps'][] = array(
		'element'=> '#clippy',
		'intro' => __('Please choose the agent that you have searched.')
		);
	$return_tours['tours']['monitoring_server_step_2']['steps'][] = array(
		'element'=> '#agent_list',
		'intro' => __('Choose the agent, please click in the name.')
		);
	$return_tours['tours']['monitoring_server_step_2']['conf'] = array();
	$return_tours['tours']['monitoring_server_step_2']['conf']['show_bullets'] = 0;
	$return_tours['tours']['monitoring_server_step_2']['conf']['show_step_numbers'] = 0;
	$return_tours['tours']['monitoring_server_step_2']['conf']['next_help'] = 'monitoring_server_step_3';
	//==================================================================
	
	return $return_tours;
}
?>