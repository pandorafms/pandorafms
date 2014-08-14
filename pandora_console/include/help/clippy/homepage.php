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
		'intro' => __('Could I help you?<br/><br/>I am Pandorin, the annoying clippy for Pandora. You could follow my advices for to make common and basic tasks in Pandora.') .
			'<div style="text-align: left;">'.
			html_print_checkbox_extended
				('clippy_is_annoying', 1, $clippy_is_annoying, false,
				'set_clippy_annoying()', '', true) .
				__('Please the clippy is annoying, I don\'t want see.') .
			'</div>' .
			'<div style="position:relative;">
			<div id="pandorin" style="display: block; position: absolute; left: -100px; top: 20px;">' .
				html_print_image('images/pandorin.png', true) .
			'</div>
			</div>'
		);
	$return_tours['tours']['homepage']['steps'][] = array(
		'element'=> '#clippy',
		'intro' => __('What task do you want to do?') . '<br/><br/>' .
			'<ul style="text-align: left; margin-left: 3px; list-style-type: disc;">' .
				'<li>' .
					"<a href='javascript: clippy_go_link_show_help(\"index.php?sec=gagente&sec2=godmode/agentes/modificar_agente\", \"monitoring_server_step_1\");'>" . 
					//'<a href="index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&clippy=monitoring_server">' . 
						__('Ping to a server Linux/Windows with a pandora agent') .
					'</a>' .
				'</li>' .
				'<li>' . __('Monitoring a switch with remote SNMP') . '</li>' .
				'<li>' . __('Monitoring a Windows server with remote WMI') . '</li>' .
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