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
include_once($config['homedir'] . "/include/functions_ui.php");
require_once ($config['homedir'] . '/enterprise/include/functions_metaconsole.php');
ui_require_jquery_file ("ui-timepicker-addon");
	
$search_modules = get_parameter('search_modules');

if ($search_modules) {
	$id_agents = json_decode(io_safe_output(get_parameter('id_agents')));
	$filter = get_parameter('q', '') . '%';
	$other_filter = json_decode(io_safe_output(get_parameter('other_filter')), true);
	
	$modules = agents_get_modules($id_agents, false,
		(array('nombre' => $filter) + $other_filter));
	
	if ($modules === false) $modules = array();
	
	$modules = array_unique($modules);
	
	$modules = io_safe_output($modules);
	
	echo json_encode($modules);
}

$get_plugin_macros = get_parameter('get_plugin_macros');
if ($get_plugin_macros) {
	$plugin_macros = db_get_value('macros','tplugin','id',get_parameter('id_plugin',0));
	
	$macros = array();
	
	$macros['base64'] = base64_encode($plugin_macros);
	$macros['array'] = json_decode($plugin_macros,true);
	
	echo json_encode($macros);
}

$get_module_detail = get_parameter ('get_module_detail', 0);

if ($get_module_detail) {

	$module_id = get_parameter ('id_module');
	$period = get_parameter ("period", 86400);
	$group = agents_get_agentmodule_group ($module_id);
	$agentId = get_parameter("id_agent");
	$server_name = get_parameter('server_name');
	
	if (defined ('METACONSOLE')) {
		$server = metaconsole_get_connection ($server_name);
		$conexion = mysql_connect ($server['dbhost'], $server['dbuser'], $server['dbpass']);
		$select_db = mysql_select_db ($server['dbname'], $conexion);
	}

	$formtable->width = '98%';
	$formtable->class = "databox";
	$formtable->data = array ();
	$formtable->size = array ();

	$periods = array(300=>__('5 minutes'), 1800=>__('30 minutes'), 3600=>__('1 hour'), 21600=>__('6 hours'), 43200=>__('12 hours'),
					86400=>__('1 day'), 604800=>__('1 week'), 1296000=>__('15 days'), 2592000=>__('1 month'), 7776000=>__('3 months'),
					15552000=>__('6 months'), 31104000=>__('1 year'), 62208000=>__('2 years'), 93312000=>__('3 years')
				);

	$formtable->data[0][0] = __('Select period:');
	$formtable->data[0][1] = html_print_select ($periods, 'period', $period, '', '', 0, true, false, false);
	$formtable->data[0][2] = "<a href='javascript: show_module_detail_dialog(" . $module_id .", ".  $agentId.", \"" . $server_name . "\", 0, -1)'>". html_print_image ("images/refresh.png", true, array ("style" => 'vertical-align: middle;', "border" => "0" )) . "</a>";

	html_print_table($formtable);

	$moduletype_name = modules_get_moduletype_name (modules_get_agentmodule_type ($module_id));

	$offset = (int) get_parameter("offset");
	$block_size = (int) $config["block_size"];

	$columns = array ();

	$datetime_from = strtotime ($date_from.' '.$time_from);
	$datetime_to = strtotime ($date_to.' '.$time_to);

	if ($moduletype_name == "log4x") {
		$table->width = "100%";

		$sql_body = sprintf ("FROM tagente_datos_log4x WHERE id_agente_modulo = %d AND utimestamp > %d ORDER BY utimestamp DESC", $module_id, get_system_time () - $period);
		
		$columns = array(
			"Timestamp" => array("utimestamp", "modules_format_timestamp", "align" => "center" ),
			"Sev" => array("severity", "modules_format_data", "align" => "center", "width" => "70px"),
			"Message"=> array("message", "modules_format_verbatim", "align" => "left", "width" => "45%"),
			"StackTrace" => array("stacktrace", "modules_format_verbatim", "align" => "left", "width" => "50%")
		);
	}
	else if (preg_match ("/string/", $moduletype_name)) {

		$sql_body = sprintf (" FROM tagente_datos_string WHERE id_agente_modulo = %d AND utimestamp > %d ORDER BY utimestamp DESC", $module_id, get_system_time () - $period);
		$columns = array(
			"Timestamp" => array("utimestamp", 			"modules_format_timestamp", 		"align" => "left"),
			"Data" => array("datos", 				"modules_format_data", 				"align" => "left"),
			"Time" => array("utimestamp", 			"modules_format_time", 				"align" => "center")
		);
	}
	else {

		$sql_body = sprintf (" FROM tagente_datos WHERE id_agente_modulo = %d AND utimestamp > %d ORDER BY utimestamp DESC", $module_id, get_system_time () - $period);
		
		$columns = array(
			"Timestamp" => array("utimestamp", 			"modules_format_timestamp", 	"align" => "left"),
			"Data" => array("datos", 				"modules_format_data", 			"align" => "left"),
			"Time" => array("utimestamp", 			"modules_format_time", 			"align" => "center")
		);
	}

	$sql_body = io_safe_output($sql_body);
	// Clean all codification characters

	$sql = "SELECT * " . $sql_body;
	$sql_count = "SELECT count(*) " . $sql_body;

	$count = db_get_value_sql ($sql_count, $conexion);

	switch ($config["dbtype"]) {
		case "mysql":
			$sql .= " LIMIT " . $offset . "," . $block_size;
			break;
		case "postgresql":
			$sql .= " LIMIT " . $block_size . " OFFSET " . $offset;
			break;
		case "oracle":
			$set = array();
			$set['limit'] = $block_size;
			$set['offset'] = $offset;
			$sql = oracle_recode_query ($sql, $set);
			break;
	}

	$result = db_get_all_rows_sql ($sql, false, true, $conexion);

	if ($result === false) {
		$result = array ();
	}

	if (($config['dbtype'] == 'oracle') && ($result !== false)) {
		for ($i=0; $i < count($result); $i++) {
			unset($result[$i]['rnum']);
		}
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

	$id_type_web_content_string = db_get_value('id_tipo', 'ttipo_modulo',
		'nombre', 'web_content_string');

	foreach ($result as $row) {
		$data = array ();
		
		$is_web_content_string = (bool)db_get_value_filter('id_agente_modulo',
			'tagente_modulo',
			array('id_agente_modulo' => $row['id_agente_modulo'],
				'id_tipo_modulo' => $id_type_web_content_string));
		
		foreach($columns as $col => $attr) {
			if ($attr[1] != "modules_format_data") {
				$data[] = $attr[1] ($row[$attr[0]]);		
			
			}
			elseif (($config['command_snapshot']) && (preg_match ("/[\n]+/i", $row[$attr[0]]))) {
				// Its a single-data, multiline data (data snapshot) ?
				
				
				// Detect string data with \n and convert to <br>'s
				$datos = preg_replace ('/\n/i','<br>',$row[$attr[0]]);
				$datos = preg_replace ('/\s/i','&nbsp;',$datos);
				
				// Because this *SHIT* of print_table monster, I cannot format properly this cells
				// so, eat this, motherfucker :))
				
				$datos = "<span style='font-family: mono,monospace;'>".$datos."</span>";
				
				// I dont why, but using index (value) method, data is automatically converted to html entities ¿?
				$data[$attr[1]] = $datos;						
			}
			elseif ($is_web_content_string) {
				//Fixed the goliat sends the strings from web
				//without HTML entities
				
				$data[$attr[1]] = io_safe_input($row[$attr[0]]);		
			}
			else {
				// Just a string of alphanumerical data... just do print							
				//Fixed the data from Selenium Plugin
				if ($row[$attr[0]] != strip_tags($row[$attr[0]]))
					$data[$attr[1]] = io_safe_input($row[$attr[0]]);
				else
					$data[$attr[1]] = $row[$attr[0]];
			}
		}
					
		array_push ($table->data, $data);
		if (count($table->data) > 200) break;
	}

	if (empty ($table->data)) {
		echo '<h3 class="error">'.__('No available data to show').'</h3>';
	}
	else {
		ui_pagination($count, false, $offset);
		html_print_table($table);
	}
		return;
	}


?>
