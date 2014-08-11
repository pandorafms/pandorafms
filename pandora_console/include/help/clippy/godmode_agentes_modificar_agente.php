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
	
	$helps = array();
	
	//==================================================================
	//Help tour about the monitoring with a ping (step 1)
	//------------------------------------------------------------------
	$helps['monitoring_server_step_1'] = array();
	$helps['monitoring_server_step_1']['steps'] = array();
	$helps['monitoring_server_step_1']['steps'][] = array(
		'element'=> '#clippy',
		'intro' => __('I show how to monitoring a server.')
		);
	$helps['monitoring_server_step_1']['steps'][] = array(
		'element'=> 'input[name="search"]',
		'intro' => __('Please type a agent to save the modules for monitoring a server.')
		);
	$helps['monitoring_server_step_1']['steps'][] = array(
		'element'=> 'input[name="srcbutton"]',
		'intro' => __('Maybe if you typped correctly the name, you can see the agent.')
		);
	$helps['monitoring_server_step_1']['conf'] = array();
	$helps['monitoring_server_step_1']['conf']['showBullets'] = 0;
	$helps['monitoring_server_step_1']['conf']['showStepNumbers'] = 1;
	$helps['monitoring_server_step_1']['conf']['next_help'] = 'monitoring_server_step_2';
	//==================================================================
	
	
	//==================================================================
	//Help tour about the monitoring with a ping (step 2)
	//------------------------------------------------------------------
	$helps['monitoring_server_step_2'] = array();
	$helps['monitoring_server_step_2']['steps'] = array();
	$helps['monitoring_server_step_2']['steps'][] = array(
		'element'=> '#clippy',
		'intro' => __('Please choose the agent that you have searched.')
		);
	$helps['monitoring_server_step_2']['steps'][] = array(
		'element'=> '#agent_list',
		'intro' => __('Choose the agent, please click in the name.')
		);
	$helps['monitoring_server_step_2']['conf'] = array();
	$helps['monitoring_server_step_2']['conf']['showBullets'] = 0;
	$helps['monitoring_server_step_2']['conf']['showStepNumbers'] = 0;
	$helps['monitoring_server_step_2']['conf']['next_help'] = 'monitoring_server_step_3';
	//==================================================================
	
	clippy_write_javascript_helps_steps($helps);
}
?>