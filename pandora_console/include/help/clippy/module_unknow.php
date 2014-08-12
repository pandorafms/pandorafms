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

function clippy_module_unknow() {
	$helps = array();
	
	//==================================================================
	//Help tour about the monitoring with a ping (step 3)
	//------------------------------------------------------------------
	$helps['module_unknow'] = array();
	$helps['module_unknow']['steps'] = array();
	$helps['module_unknow']['steps'][] = array(
		'element'=> '{clippy}', //The template to replace with the autogenerate id
		'intro' => '<table>' .
			'<tr>' .
			'<td class="context_help_title">' .
			__('You have unknown modules in this agent.') .
			'</td>' .
			'</tr>' .
			'<tr>' .
			'<td class="context_help_body">' .
			__('Unknown modules are modules which receive data normally at least in one occassion, but at this time are not receving data. Please check our troubleshoot help page to help you determine why you have unknown modules.') .
			ui_print_help_icon ('context_module_unknow', true, '', 'images/help_w.png') .
			'</td>' .
			'</tr>' .
			'</table>'
		);
	$helps['module_unknow']['conf'] = array();
	$helps['module_unknow']['conf']['autostart'] = false;
	$helps['module_unknow']['conf']['showBullets'] = 0;
	$helps['module_unknow']['conf']['showStepNumbers'] = 0;
	$helps['module_unknow']['conf']['name_obj_tour'] = '{clippy_obj}';
	//==================================================================
	
	clippy_write_javascript_helps_steps($helps, true);
}
?>