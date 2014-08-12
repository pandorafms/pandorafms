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
	//Help tour about the monitoring with a ping (step 3)
	//------------------------------------------------------------------
	$helps['monitoring_server_step_3'] = array();
	$helps['monitoring_server_step_3']['steps'] = array();
	$helps['monitoring_server_step_3']['steps'][] = array(
		'element'=> '#clippy',
		'intro' => __('Now you must go to modules, don\'t worry I teach you.')
		);
	$helps['monitoring_server_step_3']['steps'][] = array(
		'element'=> "img[alt='Modules']",
		'intro' => __('Please click in this tab.')
		);
	$helps['monitoring_server_step_3']['conf'] = array();
	$helps['monitoring_server_step_3']['conf']['showBullets'] = 0;
	$helps['monitoring_server_step_3']['conf']['showStepNumbers'] = 0;
	$helps['monitoring_server_step_3']['conf']['next_help'] = 'monitoring_server_step_4';
	//==================================================================
	
	
	//==================================================================
	//Help tour about the monitoring with a ping (step 4)
	//------------------------------------------------------------------
	$helps['monitoring_server_step_4'] = array();
	$helps['monitoring_server_step_4']['steps'] = array();
	$helps['monitoring_server_step_4']['steps'][] = array(
		'element'=> '#clippy',
		'intro' => __('Now you must create the module, don\'t worry I teach you.')
		);
	$helps['monitoring_server_step_4']['steps'][] = array(
		'element'=> "#moduletype",
		'intro' => __('Choose the network server module.')
		);
	$helps['monitoring_server_step_4']['steps'][] = array(
		'element'=> "input[name='updbutton']",
		'intro' => __('And click in this button.')
		);
	$helps['monitoring_server_step_4']['conf'] = array();
	$helps['monitoring_server_step_4']['conf']['showBullets'] = 0;
	$helps['monitoring_server_step_4']['conf']['showStepNumbers'] = 0;
	$helps['monitoring_server_step_4']['conf']['next_help'] = 'monitoring_server_step_5';
	//==================================================================
	
	
	//==================================================================
	//Help tour about the monitoring with a ping (step 5)
	//------------------------------------------------------------------
	$helps['monitoring_server_step_5'] = array();
	$helps['monitoring_server_step_5']['steps'] = array();
	$helps['monitoring_server_step_5']['steps'][] = array(
		'element'=> '#clippy',
		'intro' => __('Now you must create the module, don\'t worry I teach you.')
		);
	$helps['monitoring_server_step_5']['steps'][] = array(
		'element'=> '#clippy',
		'intro' => __('We are going to fill the form.')
		);
	$helps['monitoring_server_step_5']['steps'][] = array(
		'element'=> "#network_component_group",
		'intro' => __('Please choose the Network Management.')
		);
	$helps['monitoring_server_step_5']['steps'][] = array(
		'element'=> "#network_component",
		'intro' => __('And choose the component with the name "Host Alive".')
		);
	$helps['monitoring_server_step_5']['steps'][] = array(
		'element'=> "input[name='name']",
		'intro' => __('You can change the name.')
		);
	$helps['monitoring_server_step_5']['steps'][] = array(
		'element'=> "input[name='ip_target']",
		'intro' => __('Check if this IP is the address of your machine.')
		);
	$helps['monitoring_server_step_5']['steps'][] = array(
		'element'=> "input[name='crtbutton']",
		'intro' => __('And only to finish it is clicking this button.')
		);
	$helps['monitoring_server_step_5']['conf'] = array();
	$helps['monitoring_server_step_5']['conf']['showBullets'] = 0;
	$helps['monitoring_server_step_5']['conf']['showStepNumbers'] = 0;
	$helps['monitoring_server_step_5']['conf']['next_help'] = 'monitoring_server_step_6';
	//==================================================================
	
	
	//==================================================================
	//Help tour about the monitoring with a ping (step 6)
	//------------------------------------------------------------------
	$helps['monitoring_server_step_6'] = array();
	$helps['monitoring_server_step_6']['steps'] = array();
	$helps['monitoring_server_step_6']['steps'][] = array(
		'element'=> '#clippy',
		'intro' => __('Now, your module is just created.<br/> And the status color is <b>blue</b>.<br/>This meaning of blue status is the module is not executed for first time.<br/>In the next seconds if there is not a problem, the status color will change to red or green.')
		);
	$helps['monitoring_server_step_6']['conf'] = array();
	$helps['monitoring_server_step_6']['conf']['showBullets'] = 0;
	$helps['monitoring_server_step_6']['conf']['showStepNumbers'] = 0;
	//==================================================================
	
	
	clippy_write_javascript_helps_steps($helps, false);
}
?>