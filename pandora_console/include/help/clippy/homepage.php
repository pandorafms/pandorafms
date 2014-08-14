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

function clippy_start_page_homepage() {
	global $config;
	
	$clippy_is_annoying = (int)get_cookie('clippy_is_annoying', 0);
	
	clippy_clean_help();
	
	$return_tours = array();
	$return_tours['first_step_by_default'] = true;
	$return_tours['tours'] = array();
	
	
	//==================================================================
	//Help tour with the some task for to help the user.
	//------------------------------------------------------------------
	$return_tours['tours']['homepage'] = array();
	$return_tours['tours']['homepage']['steps'] = array();
	$return_tours['tours']['homepage']['steps'][] = array(
		'element'=> '#clippy',
		'intro' => __('Hi, can I help you?') . '<br/><br/>' .
			__('Let me introduce my self: I am Pandorin, the annoying clippy of Pandora FMS. You can follow my steps to do basic tasks in Pandora FMS or you can close me and never see me again.') .
			'<div style="text-align: left;">'.
			html_print_checkbox_extended
				('clippy_is_annoying', 1, $clippy_is_annoying, false,
				'set_clippy_annoying()', '', true) .
				__('Close this annoying clippy right now.') .
			'</div>' .
			'<div style="position:relative;">
			<div id="pandorin" style="display: block; position: absolute; left: -100px; top: 20px;">' .
				html_print_image('images/pandorin.png', true) .
			'</div>
			</div>'
		);
	$return_tours['tours']['homepage']['steps'][] = array(
		'element'=> '#clippy',
		'intro' => __('Which task would you like to do first?') . '<br/><br/>' .
			'<ul style="text-align: left; margin-left: 3px; list-style-type: disc;">' .
				'<li>' .
					"<a href='javascript: clippy_go_link_show_help(\"index.php?sec=gagente&sec2=godmode/agentes/modificar_agente\", \"monitoring_server_step_1\");'>" . 
					//'<a href="index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&clippy=monitoring_server">' . 
						__('Ping to a Linux or Windows server with a Pandora FMS agent') .
					'</a>' .
				'</li>' .
				'<li>' . __('Monitor a switch with remote SNMP') . '</li>' .
				'<li>' . __('Monitor a Windows server with remote WMI') . '</li>' .
			'</ul>'
		);
	$return_tours['tours']['homepage']['conf'] = array();
	$return_tours['tours']['homepage']['conf']['show_bullets'] = 0;
	$return_tours['tours']['homepage']['conf']['show_step_numbers'] = 0;
	$return_tours['tours']['homepage']['conf']['name_obj_js_tour'] = 'intro_homepage';
	$return_tours['tours']['homepage']['conf']['other_js'] = "
		var started = 0;
		
		function show_clippy() {
			if (intro_homepage.started()) {
				started = 1;
			}
			else {
				started = 0;
			}
			
			if (started == 0)
				intro_homepage.start();
		}
		
		function set_clippy_annoying() {
			checked = $('input[name=\'clippy_is_annoying\']').is(':checked');
			intro_homepage.exit();
			
			if (checked) {
				document.cookie = 'clippy_is_annoying=1';
			}
			else {
				document.cookie = 'clippy_is_annoying=0';
			}
		}
		";
	if ($config['logged']) {
		$return_tours['tours']['homepage']['conf']['autostart'] = true;
	}
	else {
		$return_tours['tours']['homepage']['conf']['autostart'] = false;
	}
	
	if ($config["tutorial_mode"] == 'on_demand') {
		$return_tours['tours']['homepage']['conf']['autostart'] = false;
	}
	
	if ($clippy_is_annoying === 1) {
		$return_tours['tours']['homepage']['conf']['autostart'] = false;
	}
	
	//==================================================================
	
	return $return_tours;
}
?>