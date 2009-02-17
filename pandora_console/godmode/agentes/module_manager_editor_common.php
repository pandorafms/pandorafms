<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

if (! isset ($id_agente)) {
	die ("Not Authorized");
}

function prepend_table_simple ($row, $id = false) {
	global $table_simple;
	
	if ($id)
		$data = array ($id => $row);
	else
		$data = array ($row);
	
	$table_simple->data = array_merge ($data, $table_simple->data);
}

function push_table_simple ($row, $id = false) {
	global $table_simple;
	
	if ($id)
		$data = array ($id => $row);
	else
		$data = array ($row);
	
	$table_simple->data = array_merge ($table_simple->data, $data);
}

function prepend_table_advanced ($row, $id = false) {
	global $table_advanced;
	
	if ($id)
		$data = array ($id => $row);
	else
		$data = array ($row);
	
	$table_advanced->data = array_merge ($data, $table_advanced->data);
}

function push_table_advanced ($row, $id = false) {
	global $table_advanced;
	
	if ($id)
		$data = array ($id => $row);
	else
		$data = array ($row);
	
	$table_advanced->data = array_merge ($table_advanced->data, $data);
}

function add_component_selection ($id_network_component_type) {
	global $table_simple;
	
	$data = array ();
	$data[0] = __('Using module component').' ';
	$data[0] .= pandora_help ('network_component', true);
	
	$component_groups = get_network_component_groups ($id_network_component_type);
	$data[1] = '<span id="component_group" class="left">';
	$data[1] .= __('Group').'<br />';
	$data[1] .= print_select ($component_groups,
		'network_component_group', '', '', '--'.__('Manual setup').'--', 0,
		true, false, false);
	$data[1] .= '</span>';
	$data[1] .= print_input_hidden ('id_module_component_type', $id_network_component_type, true);
	$data[1] .= '<span id="no_component" class="invisible error">';
	$data[1] .= __('No component was found');
	$data[1] .= '</span>';
	$data[1] .= '<span id="component" class="invisible right">';
	$data[1] .= __('Component').'<br />';
	$data[1] .= print_select (array (), 'network_component', '', '',
		'---'.__('Manual setup').'---', 0, true);
	$data[1] .= '</span>';
	$data[1] .= ' <span id="component_loading" class="invisible">';
	$data[1] .= '<img src="images/spinner.gif" />';
	$data[1] .= '</span>';
	
	$table_simple->colspan['module_component'][1] = 3;
	$table_simple->rowstyle['module_component'] = 'background-color: #D4DDC6';
	
	prepend_table_simple ($data, 'module_component');
}

require_once ('include/functions_modules.php');

$update_module_id = (int) get_parameter_get ('update_module');

$table_simple->id = 'simple';
$table_simple->width = '90%';
$table_simple->class = 'databox_color';
$table_simple->data = array ();
$table_simple->colspan = array ();
$table_simple->style = array ();
$table_simple->style[0] = 'font-weight: bold';
$table_simple->style[2] = 'font-weight: bold';

$table_simple->data[0][0] = __('Name');
$table_simple->data[0][1] = print_input_text ('name', $name, '', 20, 100, true);
$table_simple->data[0][2] = __('Disabled');
$table_simple->data[0][3] = print_checkbox ("disabled", 1, $disabled, true);

$table_simple->data[1][0] = __('Type').' '.pandora_help ('module_type', true);
if ($id_agent_module) {
	$table_simple->data[1][1] = '<em>'.get_moduletype_description ($id_module_type).'</em>';
} else {
	switch ($moduletype) {
	case 1:
	case "dataserver":
		$categories = array (0, 1, 2, 6, 7, 8, 9, -1);
		break;
	case 2:
	case "networkserver":
		$categories = array (3, 4, 5);
		break;
	case 3:
	case "pluginserver":
		$categories = array (0, 1, 2, 9);
		break;
	case 4:
	case "predictionserver":
		$categories = array (1, 2);
		break;
	case 5:
	case "wmiserver":
		$categories = array (0, 1, 2);
		break;
	}
	
	$sql = sprintf ('SELECT id_tipo, descripcion
		FROM ttipo_modulo
		WHERE categoria IN (%s)
		ORDER BY descripcion',
		implode (',', $categories));
	$table_simple->data[1][1] = print_select_from_sql ($sql, 'id_module_type',
		'', '', '', '', true, false, false);
}

$table_simple->data[1][2] = __('Module group');
$table_simple->data[1][3] = print_select_from_sql ('SELECT id_mg, name FROM tmodule_group ORDER BY name',
	'id_module_group', $id_module_group, '', __('Not assigned'), '0',
	true);

$table_simple->data[2][0] = __('Warning status');
$table_simple->data[2][1] = '<em>'.__('Min.').'</em>';
$table_simple->data[2][1] .= print_input_text ('min_warning', $min_warning,
	'', 5, 15, true);
$table_simple->data[2][1] .= '<br /><em>'.__('Max.').'</em>';
$table_simple->data[2][1] .= print_input_text ('max_warning', $max_warning,
	'', 5, 15, true);
$table_simple->data[2][2] = __('Critical status');
$table_simple->data[2][3] = '<em>'.__('Min.').'</em>';
$table_simple->data[2][3] .= print_input_text ('min_critical', $min_critical,
	'', 5, 15, true);
$table_simple->data[2][3] .= '<br /><em>'.__('Max.').'</em>';
$table_simple->data[2][3] .= print_input_text ('max_critical', $max_critical,
	'', 5, 15, true);

/* FF stands for Flip-flop */
$table_simple->data[3][0] = __('FF threshold').' '.pandora_help ('ff_threshold', true);
$table_simple->data[3][1] = print_input_text ('ff_event', $ff_event,
	'', 5, 15, true);
$table_simple->data[3][2] = __('Historical data');
$table_simple->data[3][3] = print_checkbox ("history_data", 1, $history_data, true);

/* Advanced form part */
$table_advanced->id = 'advanced';
$table_advanced->width = '90%';
$table_advanced->class = 'databox_color';
$table_advanced->data = array ();
$table_advanced->style = array ();
$table_advanced->style[0] = 'font-weight: bold';
$table_advanced->style[2] = 'font-weight: bold';
$table_advanced->colspan = array ();

$table_advanced->data[0][0] = __('Description');
$table_advanced->colspan[0][1] = 3;
$table_advanced->data[0][1] = print_textarea ('description', 2, 65,
	$description, '', true);

$table_advanced->data[1][0] = __('Custom ID');
$table_advanced->data[1][1] = print_input_text ('custom_id', $custom_id,
	'', 20, 65, true);

$table_advanced->data[2][0] = __('Interval');
$table_advanced->data[2][1] = print_input_text ('module_interval', $interval,
	'', 5, 10, true);
	
$table_advanced->data[2][2] = __('Post process').' '.pandora_help ('postprocess', true);
$table_advanced->data[2][3] = print_input_text ('post_process',
	$post_process, '', 5, 5, true);

$table_advanced->data[3][0] = __('Min. Value');
$table_advanced->data[3][1] = print_input_text ('min', $min, '', 5, 15, true);
$table_advanced->data[3][2] = __('Max. Value');
$table_advanced->data[3][3] = print_input_text ('max', $max, '', 5, 15, true);

$table_advanced->data[4][0] = __('Export target');
$table_advanced->data[4][1] = print_select_from_sql ('SELECT id, name FROM tserver_export ORDER BY name',
	'id_export', $id_export, '',__('None'),'0', true, false, false);
$table_advanced->colspan[4][1] = 3;
?>
