<?php

//Pandora FMS- http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;


include_once($config['homedir'] . "/include/functions_agents.php");
include_once($config['homedir'] . "/include/functions_modules.php");
include_once($config['homedir'] . "/include/functions_ui.php");
enterprise_include_once ('include/functions_metaconsole.php');

$get_plugin_macros = get_parameter('get_plugin_macros');
$search_modules = get_parameter('search_modules');
$get_module_detail = get_parameter ('get_module_detail', 0);
$get_module_autocomplete_input = (bool) get_parameter('get_module_autocomplete_input');
$add_module_relation = (bool) get_parameter('add_module_relation');
$remove_module_relation = (bool) get_parameter('remove_module_relation');
$change_module_relation_updates = (bool) get_parameter('change_module_relation_updates');
$get_id_tag = (bool) get_parameter('get_id_tag', 0);

if ($get_plugin_macros) {
	$id_plugin = get_parameter('id_plugin', 0);
	
	$plugin_macros = db_get_value('macros', 'tplugin', 'id',
		$id_plugin);
	
	$macros = array();
	$macros['base64'] = base64_encode($plugin_macros);
	$macros['array'] = json_decode($plugin_macros,true);
	
	echo json_encode($macros);
	return;
}


if ($search_modules) {
	$id_agents = json_decode(io_safe_output(get_parameter('id_agents')));
	$filter =  '%' . get_parameter('q', '') . '%';
	$other_filter = json_decode(io_safe_output(get_parameter('other_filter')), true);
	
	$modules = agents_get_modules($id_agents, false,
		(array('nombre' => $filter) + $other_filter));
	
	if ($modules === false) $modules = array();
	
	$modules = array_unique($modules);
	
	$modules = io_safe_output($modules);
	
	echo json_encode($modules);
	return;
}


if ($get_module_detail) {
	
	ui_include_time_picker();
	
	$module_id = (int)get_parameter('id_module');
	$period = get_parameter("period", SECONDS_1DAY);
	if ($period === 'undefined') {
		$period = SECONDS_1DAY;
	}
	else {
		$period = (int)$period;
	}
	
	
	$group = agents_get_agentmodule_group ($module_id);
	$agentId = get_parameter("id_agent");
	$server_name = get_parameter('server_name');
	
	if (defined ('METACONSOLE')) {
		$server = metaconsole_get_connection ($server_name);
		
		if (metaconsole_connect($server) != NOERR)
			return;
		$conexion = false;
	}
	else {
		$conexion = false;
	}
	
	$selection_mode = get_parameter('selection_mode', 'fromnow');
	$date_from = (string) get_parameter ('date_from', date ('Y-m-j'));
	$time_from = (string) get_parameter ('time_from', date ('h:iA'));
	$date_to = (string) get_parameter ('date_to', date ('Y-m-j'));
	$time_to = (string) get_parameter ('time_to', date ('h:iA'));
	
	$formtable->width = '98%';
	$formtable->class = "databox";
	$formtable->data = array ();
	$formtable->size = array ();
	
	$periods = array(SECONDS_5MINUTES =>__('5 minutes'),
		SECONDS_30MINUTES =>__('30 minutes'),
		SECONDS_1HOUR =>__('1 hour'),
		SECONDS_6HOURS =>__('6 hours'),
		SECONDS_12HOURS =>__('12 hours'),
		SECONDS_1DAY =>__('1 day'),
		SECONDS_1WEEK =>__('1 week'),
		SECONDS_15DAYS =>__('15 days'),
		SECONDS_1MONTH =>__('1 month'),
		SECONDS_3MONTHS =>__('3 months'),
		SECONDS_6MONTHS =>__('6 months'),
		SECONDS_1YEAR =>__('1 year'),
		SECONDS_2YEARS =>__('2 years'),
		SECONDS_3YEARS =>__('3 years'));
	
	$formtable->data[0][0] = html_print_radio_button_extended (
		"selection_mode", 'fromnow', '', $selection_mode, false, '',
		'style="margin-right: 15px;"', true) . __("Choose a time from now");
	$formtable->data[0][1] = html_print_select ($periods, 'period', $period, '', '', 0, true, false, false);
	$formtable->data[0][2] = '';
	$formtable->data[0][3] = "<a href='javascript: show_module_detail_dialog(" . $module_id .", ".  $agentId.", \"" . $server_name . "\", 0, -1)'>". html_print_image ("images/refresh.png", true, array ("style" => 'vertical-align: middle;', "border" => "0" )) . "</a>";
	$formtable->rowspan[0][3] = 2;
	$formtable->cellstyle[0][3] = 'vertical-align: middle;';

	$formtable->data[1][0] = html_print_radio_button_extended(
		"selection_mode", 'range','', $selection_mode, false, '',
		'style="margin-right: 15px;"', true) . __("Specify time range");
	$formtable->data[1][1] = __('Timestamp from:');
	
	$formtable->data[1][2] = html_print_input_text('date_from',
		$date_from, '', 10, 10, true);
	$formtable->data[1][2] .= html_print_input_text('time_from',
		$time_from, '', 9, 7, true);
	
	$formtable->data[1][1] .= '<br />';
	$formtable->data[1][1] .= __('Timestamp to:');
	
	$formtable->data[1][2] .= '<br />';
	$formtable->data[1][2] .= html_print_input_text('date_to', $date_to,
		'', 10, 10, true);
	$formtable->data[1][2] .= html_print_input_text('time_to', $time_to,
		'', 9, 7, true);
	
	html_print_table($formtable);
	
	$moduletype_name = modules_get_moduletype_name(
		modules_get_agentmodule_type($module_id));
	
	$offset = (int) get_parameter("offset");
	$block_size = (int) $config["block_size"];
	
	$columns = array ();
	
	$datetime_from = strtotime ($date_from . ' ' . $time_from);
	$datetime_to = strtotime ($date_to . ' ' . $time_to);
	
	
	$columns = array(
		"Data" => array(
			"data",
			"modules_format_data",
			"align" => "left",
			"width" => "230px"),
		"Time" => array(
			"utimestamp",
			"modules_format_time",
			"align" => "left",
			"width" => "50px")
	);
	
	if ($selection_mode == "fromnow") {
		$date = get_system_time();
		$period = $period;
	}
	else {
		$period = $datetime_to - $datetime_from;
		$date = $datetime_from + $period;
	}
	
	$count = modules_get_agentmodule_data ($module_id, $period,
		$date, true, $conexion);
	
	$module_data = modules_get_agentmodule_data ($module_id, $period,
		$date, false, $conexion, 'DESC');
	
	if (empty($module_data)) {
		$result = array();
	}
	else {
		// Paginate the result
		$result = array_slice($module_data, $offset, $block_size);
	}
	
	$table->width = '98%';
	$table->data = array();
	
	$index = 0;
	foreach($columns as $col => $attr) {
		$table->head[$index] = $col;
		
		if (isset($attr["align"]))
			$table->align[$index] = $attr["align"];
		
		if (isset($attr["width"]))
			$table->size[$index] = $attr["width"];
		
		$index++;
	}
	
	$id_type_web_content_string = db_get_value('id_tipo',
		'ttipo_modulo', 'nombre', 'web_content_string');
	
	foreach ($result as $row) {
		$data = array ();
		
		$is_web_content_string = (bool)db_get_value_filter('id_agente_modulo',
			'tagente_modulo',
			array('id_agente_modulo' => $row['id_agente_modulo'],
				'id_tipo_modulo' => $id_type_web_content_string));
		
		foreach ($columns as $col => $attr) {
			if ($attr[1] != "modules_format_data") {
				$data[] = $attr[1] ($row[$attr[0]]);
			
			}
			elseif (($config['command_snapshot']) && (preg_match ("/[\n]+/i", $row[$attr[0]]))) {
				// Its a single-data, multiline data (data snapshot) ?
				
				
				// Detect string data with \n and convert to <br>'s
				$datos = $row[$attr[0]];
				//$datos = preg_replace ('/\n/i','<br>',$row[$attr[0]]);
				//$datos = preg_replace ('/\s/i','&nbsp;',$datos);
				
				// Because this *SHIT* of print_table monster, I cannot format properly this cells
				// so, eat this, motherfucker :))
				
				$datos =  io_safe_input($datos);
				
				// I dont why, but using index (value) method, data is automatically converted to html entities ¿?
				$data[] = $datos;
			}
			elseif ($is_web_content_string) {
				//Fixed the goliat sends the strings from web
				//without HTML entities
				
				$data[] = io_safe_input($row[$attr[0]]);
			}
			else {
				// Just a string of alphanumerical data... just do print
				//Fixed the data from Selenium Plugin
				if ($row[$attr[0]] != strip_tags($row[$attr[0]]))
					$data[] = io_safe_input($row[$attr[0]]);
				else
					$data[] = $row[$attr[0]];
			}
		}
		
		array_push ($table->data, $data);
		if (count($table->data) > 200)
			break;
	}
	
	if (empty ($table->data)) {
		ui_print_error_message(__('No available data to show'));
	}
	else {
		ui_pagination (count($count), false, $offset, 0, false, 'offset', true, 'binary_dialog');
		html_print_table($table);
	}
	
	if (defined ('METACONSOLE')) {
		metaconsole_restore_db();
	}
	
	return;
}


if ($get_module_autocomplete_input) {
	$id_agent = (int) get_parameter("id_agent");
	
	ob_clean();
	if ($id_agent > 0) {
		html_print_autocomplete_modules(
			'autocomplete_module_name', '', array($id_agent));
		return;
	}
	return;
}


if ($add_module_relation) {
	$result = false;
	$id_module_a = (int) get_parameter("id_module_a");
	$id_module_b = (int) get_parameter("id_module_b");
	
	if ($id_module_a < 1) {
		$name_module_a = get_parameter("name_module_a", "");
		if ($name_module_a) {
			$id_module_a = (int) db_get_value('id_agente_modulo', 'tagente_modulo', 'nombre', $name_module_a);
		}
		else {
			echo json_encode($result);
			return;
		}
	}
	if ($id_module_b < 1) {
		$name_module_b = get_parameter("name_module_b", "");
		if ($name_module_b) {
			$id_module_b = (int) db_get_value('id_agente_modulo', 'tagente_modulo', 'nombre', $name_module_b);
		}
		else {
			echo json_encode($result);
			return;
		}
	}
	if ($id_module_a > 0 && $id_module_b > 0) {
		$result = modules_add_relation($id_module_a, $id_module_b);
	}
	
	echo json_encode($result);
	return;
}


if ($remove_module_relation) {
	$id_relation = (int) get_parameter("id_relation");
	if ($id_relation > 0) {
		$result = (bool) modules_delete_relation($id_relation);
	}
	
	echo json_encode($result);
	return;
}


if ($change_module_relation_updates) {
	$id_relation = (int) get_parameter("id_relation");
	if ($id_relation > 0) {
		$result = (bool) modules_change_relation_lock($id_relation);
	}
	
	echo json_encode($result);
	return;
}


if ($get_id_tag) {
	$tag_name = get_parameter('tag_name');
	
	if ($tag_name) {
		$tag_id = db_get_value('id_tag', 'ttag', 'name', $tag_name);
	}
	else {
		$tag_id = 0;
	}
	
	echo $tag_id;
	return;
}
?>
