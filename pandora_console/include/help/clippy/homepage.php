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
	
	$helps = array();
	
	//==================================================================
	//Help tour with the some task for to help the user.
	//------------------------------------------------------------------
	$helps['homepage'] = array();
	$helps['homepage']['steps'] = array();
	$helps['homepage']['steps'][] = array(
		'element'=> '#clippy',
		'intro' => __('Could I help you?<br/><br/>I am Pandorin, the annoying clippy for Pandora. You could follow my advices for to make common and basic tasks in Pandora.') .
			'<div style="position:relative;">
			<div id="pandorin" style="display: block; position: absolute; left: -100px; top: 20px;">' .
				html_print_image('images/pandorin.png', true) .
			'</div>
			</div>'
		);
	$helps['homepage']['steps'][] = array(
		'element'=> '#clippy',
		'intro' => __('What task do you want to do?') . '<br/><br/>' .
			'<ul style="text-align: left; margin-left: 3px; list-style-type: disc;">' .
				'<li>' .
					"<a href='javascript: clippy_go_link_show_help(\"index.php?sec=gagente&sec2=godmode/agentes/modificar_agente\", \"monitoring_server_step_1\");'>" . 
					//'<a href="index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&clippy=monitoring_server">' . 
						__('Monitoring a server Linux/Windows with a pandora agent') .
					'</a>' .
				'</li>' .
				'<li>' . __('Monitoring a switch with remote SNMP') . '</li>' .
				'<li>' . __('Monitoring a Windows server with remote WMI ') . '</li>' .
			'</ul>' .
			'<div style="text-align: left;">'.
			html_print_checkbox_extended
				('clippy_is_annoying', 1, $clippy_is_annoying, false,
				'set_clippy_annoying()', '', true) .
				__('Please the clippy is annoying, I don\'t want see.') .
			'</div>'
		);
	$helps['homepage']['conf'] = array();
	$helps['homepage']['conf']['showBullets'] = 0;
	$helps['homepage']['conf']['showStepNumbers'] = 0;
	$helps['homepage']['conf']['name_obj_tour'] = 'intro_homepage';
	$helps['homepage']['conf']['other_js'] = "
		function show_clippy() {
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
		$helps['homepage']['conf']['autostart'] = true;
	}
	else {
		$helps['homepage']['conf']['autostart'] = false;
	}
	
	if ($config["tutorial_mode"] == 'on_demand') {
		$helps['homepage']['conf']['autostart'] = false;
	}
	
	if ($clippy_is_annoying === 1) {
		$helps['homepage']['conf']['autostart'] = false;
	}
	
	//==================================================================
	
	clippy_write_javascript_helps_steps($helps);
}
?>