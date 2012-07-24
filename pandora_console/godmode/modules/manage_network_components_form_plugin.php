<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Load global variables
global $config;

check_login ();

echo "<h3>".__('Plugin component').'</h3>';

$data = array ();
$data[0] = __('Port');
$data[1] = html_print_input_text ('tcp_port', $tcp_port, '', 5, 20, true);
$table->colspan['plugin_0'][1] = 3;

push_table_row ($data, 'plugin_0');

$data = array ();
$data[0] = __('Plugin');
$data[1] = html_print_select_from_sql ('SELECT id, name FROM tplugin ORDER BY name',
	'id_plugin', $id_plugin, '', __('None'), 0, true, false, false);
$table->colspan['plugin_1'][1] = 3;

push_table_row ($data, 'plugin_1');

$data = array ();
$data[0] = __('Username');
$data[1] = html_print_input_text ('plugin_user', $plugin_user, '', 15, 60, true);
$data[2] = _('Password');
$data[3] = html_print_input_password ('plugin_pass', $plugin_pass, '', 15, 60, true);

push_table_row ($data, 'plugin_2');

$data = array ();
$data[0] = __('Plugin parameters');
$data[0] .= ui_print_help_icon ('plugin_parameters', true);
$data[1] = html_print_input_text ('plugin_parameter', $plugin_parameter, '', 30, 255, true);
$data[2] = __('Post process') . ' ' . ui_print_help_icon ('postprocess', true);
$data[3] = html_print_input_text ('post_process', $post_process, '', 12, 25, true);

push_table_row ($data, 'plugin_3');

// Dynamic macros 
$data = array ();
$data[0] = __('Plugin macros');
$data[0] .= ui_print_help_icon ('plugin', true);
$data[1] = $data[2] = $data[3] = '';

push_table_row ($data, 'plugin_4');

$macros = json_decode($macros,true);
// The next row number is plugin_5
$next_name_number = 5;
$i = 1;
while(1) {
	// Always print at least one macro
	if((!isset($macros[$i]) || $macros[$i]['desc'] == '') && $i > 1) {
		break;
	}
	$macro_desc_name = 'field'.$i.'_desc';
	$macro_desc_value = '';
	$macro_help_name = 'field'.$i.'_help';
	$macro_help_value = '';
	$macro_value_name = 'field'.$i.'_value';
	$macro_value_value = '';
	$macro_name_name = 'field'.$i.'_macro';
	$macro_name = '_field'.$i.'_';
	
	if(isset($macros[$i]['desc'])) {
		$macro_desc_value = $macros[$i]['desc'];
	}
	
	if(isset($macros[$i]['help'])) {
		$macro_help_value = $macros[$i]['help'];
	}
	
	if(isset($macros[$i]['value'])) {
		$macro_value_value = $macros[$i]['value'];
	}
	
	$data = array ();
	$data[0] = sprintf(__('Macro %s description'),$macro_name);
	$data[0] .= html_print_input_hidden($macro_name_name, $macro_name, true);
	$data[1] = html_print_input_text ($macro_desc_name, $macro_desc_value, '', 30, 255, true);
	$data[2] = sprintf(__('Macro %s default value'),$macro_name);
	$data[3] = html_print_input_text ($macro_value_name, $macro_value_value, '', 30, 255, true);

	push_table_row ($data, 'plugin_'.$next_name_number);
	$next_name_number++;
	
	$table->colspan['plugin_'.$next_name_number][1] = 2;

	$data = array ();
	$data[0] = sprintf(__('Macro %s help'),$macro_name);
	$data[1] = html_print_input_text ($macro_help_name, $macro_help_value, '', 100, 255, true);

	push_table_row ($data, 'plugin_'.$next_name_number);
	$next_name_number++;
	$i++;

	$table->colspan['plugin_n'][2] = 2;

	$data = array ();
	$data[0] = '';
	$data[1] = __('Add macro').' <a href="javascript:new_macro(\'network_component-plugin_\')">'.html_print_image('images/add.png',true).'</a>';
	$data[1] .= '<div id="next_macro" style="display:none">'.$i.'</div>';
	$data[1] .= '<div id="next_row" style="display:none">'.$next_name_number.'</div>';
	$data[2] = '<div id="delete_macro_button" style="display:none;">'.__('Delete macro').' <a href="javascript:delete_macro(\'network_component-plugin_\')">'.html_print_image('images/cancel.png',true).'</a></div>';

	push_table_row ($data, 'plugin_n');
}

?>

