<?php

if(check_login()){

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
$get_type = (bool) get_parameter('get_type', 0);
$list_modules = (bool) get_parameter('list_modules', 0);


if ($get_plugin_macros) {
	if ( https_is_running() ) {
		header('Content-type: application/json');
	}
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
	if ( https_is_running() ) {
		header('Content-type: application/json');
	}
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
	// This script is included manually to be included after jquery and avoid error
	ui_include_time_picker();
	ui_require_jquery_file("ui.datepicker-" . get_user_language(), "include/javascript/i18n/");

	$module_id = (int)get_parameter('id_module');
	$period = get_parameter("period", SECONDS_1DAY);
	if ($period === 'undefined') {
		$period = SECONDS_1DAY;
	}
	else {
		$period = (int)$period;
	}


	$group = agents_get_agentmodule_group ($module_id);
	$agentId = (int) get_parameter("id_agent");
	$server_name = (string) get_parameter('server_name');

	if (is_metaconsole()) {
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
	$formtable->data[0][3] = "<a href='javascript: show_module_detail_dialog(" . $module_id .", ".  $agentId.", \"" . $server_name .
					"\", 0, -1,\"" . modules_get_agentmodule_name( $module_id ) . "\")'>" .
					html_print_image ("images/refresh.png", true, array ("style" => 'vertical-align: middle;', "border" => "0" )) .
					"</a>";
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
			"align" => "center",
			"width" => "230px"),
	);

	if($config['prominent_time']=='comparation') {
		$columns["Time"] = array(
			"utimestamp",
			"modules_format_time",
			"align" => "center",
			"width" => "50px");
	}
	else {
		$columns["Timestamp"] = array(
			"utimestamp",
			"modules_format_timestamp",
			"align" => "center",
			"width" => "50px");
	}

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

	$table->width = '100%';
	$table->class = 'databox data';
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
				$data[] = date('d F Y h:i:s A', $row['utimestamp']);

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
				if ($row[$attr[0]] != strip_tags($row[$attr[0]])) {

					$data[] = io_safe_input($row[$attr[0]]);
				}
				else if (is_numeric($row[$attr[0]]) && !modules_is_string_type($row['module_type']) ) {
					
					$data[] = remove_right_zeros(number_format($row[$attr[0]], $config['graph_precision']));
					//~ $data[] = (double) $row[$attr[0]];
				}
				else {
					if ($row[$attr[0]] == '') {
						$data[] = 'No data';
					}
					else {
					
					if(is_snapshot_data($row[$attr[0]])){	
						$data[] = "<a target='_blank' href='".io_safe_input($row[$attr[0]])."'><img style='width:300px' src='".io_safe_input($row[$attr[0]])."'></a>";
					}
					else{
						$data[] = $row[$attr[0]];
					}
						
						
					
					
					}
				}
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

	if (is_metaconsole())
		metaconsole_restore_db();

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

if ($list_modules) {
	include_once($config['homedir'] . "/include/functions_modules.php");
	include_once($config['homedir'] . "/include/functions_servers.php");
	include_once($config['homedir'] . "/include/functions_tags.php");
	include_once($config['homedir'] . "/include/functions_clippy.php");

	$agent_a = check_acl ($config['id_user'], 0, "AR");
	$agent_w = check_acl ($config['id_user'], 0, "AW");
	$access = ($agent_a == true) ? 'AR' : (($agent_w == true) ? 'AW' : 'AR');

	$id_agente = $id_agent = (int)get_parameter('id_agente', 0);
	$url = 'index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente=' . $id_agent;
	$selectTypeUp = '';
	$selectTypeDown = '';
	$selectNameUp = '';
	$selectNameDown = '';
	$selectStatusUp = '';
	$selectStatusDown = '';
	$selectDataUp = '';
	$selectDataDown = '';
	$selectLastContactUp = '';
	$selectLastContactDown = '';
	$sortField = get_parameter('sort_field');
	$sort = get_parameter('sort', 'none');
	$selected = 'border: 1px solid black;';

	switch ($sortField) {
		case 'type':
			switch ($sort) {
				case 'up':
					$selectTypeUp = $selected;
					$order = array('field' => 'tagente_modulo.id_tipo_modulo', 'order' => 'ASC');
					break;
				case 'down':
					$selectTypeDown = $selected;
					$order = array('field' => 'tagente_modulo.id_tipo_modulo', 'order' => 'DESC');
					break;
			}
			break;
		case 'name':
			switch ($sort) {
				case 'up':
					$selectNameUp = $selected;
					$order = array('field' => 'tagente_modulo.nombre', 'order' => 'ASC');
					break;
				case 'down':
					$selectNameDown = $selected;
					$order = array('field' => 'tagente_modulo.nombre', 'order' => 'DESC');
					break;
			}
			break;
		case 'status':
			switch ($sort) {
				case 'up':
					$selectStatusUp = $selected;
					$order = array('field' => 'tagente_estado.estado', 'order' => 'ASC');
					break;
				case 'down':
					$selectStatusDown = $selected;
					$order = array('field' => 'tagente_estado.estado', 'order' => 'DESC');
					break;
			}
			break;
		case 'last_contact':
			switch ($sort) {
				case 'up':
					$selectLastContactUp = $selected;
					$order = array('field' => 'tagente_estado.utimestamp', 'order' => 'ASC');
					break;
				case 'down':
					$selectLastContactDown = $selected;
					$order = array('field' => 'tagente_estado.utimestamp', 'order' => 'DESC');
					break;
			}
			break;
		default:
			$selectTypeUp = '';
			$selectTypeDown = '';
			$selectNameUp = $selected;
			$selectNameDown = '';
			$selectStatusUp = '';
			$selectStatusDown = '';
			$selectDataUp = '';
			$selectDataDown = '';
			$selectLastContactUp = '';
			$selectLastContactDown = '';

			$order = array('field' => 'tagente_modulo.nombre', 'order' => 'ASC');
			break;
	}

	switch ($config["dbtype"]) {
		case "oracle":
			if (isset($order['field']) && $order['field'] == 'tagente_modulo.nombre') {
				$order['field'] = 'dbms_lob.substr(tagente_modulo.nombre,4000,1)';
			}
			break;
	}

	// Fix: for tag functionality groups have to be all user_groups (propagate ACL funct!)
	$groups = users_get_groups($config["id_user"], $access);

	$tags_sql = tags_get_acl_tags($config['id_user'],
		array_keys($groups), $access, 'module_condition', 'AND',
		'tagente_modulo', false, array(), true);

	$status_filter_monitor = (int)get_parameter('status_filter_monitor', -1);
	$status_text_monitor = get_parameter('status_text_monitor', '');
	$filter_monitors = (bool)get_parameter('filter_monitors', false);
	$status_module_group = get_parameter('status_module_group', -1);
	$monitors_change_filter = (bool)get_parameter('monitors_change_filter', false);

	$status_filter_sql = '1 = 1';
	if ($status_filter_monitor == AGENT_MODULE_STATUS_NOT_NORMAL) { //Not normal
		$status_filter_sql = " tagente_estado.estado <> 0";
	}
	elseif ($status_filter_monitor != -1) {
		$status_filter_sql = 'tagente_estado.estado = ' . $status_filter_monitor;
	}

	if ($status_module_group != -1) {
	$status_module_group_filter = 'id_module_group = ' . $status_module_group;
	}
	else {
		$status_module_group_filter = 'id_module_group >= 0';
	}

	$status_text_monitor_sql = '%';
	if (!empty($status_text_monitor)) {
		$status_text_monitor_sql .= $status_text_monitor . '%';
	}


	//Count monitors/modules
	switch ($config["dbtype"]) {
		case "mysql":
			$sql = sprintf("
					SELECT COUNT(*)
						FROM tagente_estado,
							(SELECT *
							FROM tagente_modulo
							WHERE id_agente = %d AND nombre LIKE \"%s\" AND delete_pending = 0
								AND disabled = 0 AND %s) tagente_modulo
						LEFT JOIN tmodule_group
							ON tagente_modulo.id_module_group = tmodule_group.id_mg
						WHERE tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
							AND %s %s
							AND tagente_estado.estado != %d
							AND tagente_modulo.%s
						ORDER BY tagente_modulo.id_module_group , %s  %s",
					$id_agente, $status_text_monitor_sql,$status_module_group_filter,$status_filter_sql, $tags_sql, AGENT_MODULE_STATUS_NO_DATA,
					$status_module_group_filter, $order['field'], $order['order']);
			break;
		case "postgresql":
			$sql = sprintf("
				SELECT COUNT(DISTINCT tagente_modulo.id_module_group)
				FROM tagente_estado,
					(SELECT *
					FROM tagente_modulo
					WHERE id_agente = %d AND nombre LIKE '%s'
						AND delete_pending = 0
						AND disabled = 0 AND %s) tagente_modulo
				LEFT JOIN tmodule_group
					ON tagente_modulo.id_module_group = tmodule_group.id_mg
				WHERE tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
					AND %s %s
					AND tagente_estado.estado != %d
					AND tagente_modulo.%s
				GROUP BY tagente_modulo.id_module_group,
					tagente_modulo.nombre
				ORDER BY tagente_modulo.id_module_group , %s  %s",
				$id_agente, $status_text_monitor_sql,$status_module_group_filter,$status_filter_sql,
				$tags_sql, AGENT_MODULE_STATUS_NO_DATA,$status_module_group_filter,$order['field'],
				$order['order']);
			break;
		case "oracle":
			$sql = sprintf ("
					SELECT COUNT(*)" .
				" FROM tagente_estado, tagente_modulo
					LEFT JOIN tmodule_group
					ON tmodule_group.id_mg = tagente_modulo.id_module_group
				WHERE tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
					AND tagente_modulo.id_agente = %d
					AND tagente_modulo.nombre LIKE '%s'
					AND %s %s
					AND tagente_modulo.delete_pending = 0
					AND tagente_modulo.disabled = 0
					AND tagente_estado.estado != %d
					AND tagente_modulo.%s
				ORDER BY tagente_modulo.id_module_group , %s %s
				", $id_agente, $status_text_monitor_sql, $status_filter_sql, $tags_sql, AGENT_MODULE_STATUS_NO_DATA,
				$status_module_group_filter,$order['field'], $order['order']);
			break;
	}

	$count_modules = db_get_all_rows_sql($sql);
	if (isset($count_modules[0]))
		$count_modules = reset($count_modules[0]);
	else
		$count_modules = 0;


	//Get monitors/modules
	// Get all module from agent
	switch ($config["dbtype"]) {
		case "mysql":
			$sql = sprintf("
				SELECT *
				FROM tagente_estado,
					(SELECT *
					FROM tagente_modulo
					WHERE id_agente = %d AND nombre LIKE \"%s\" AND delete_pending = 0
						AND disabled = 0 AND %s) tagente_modulo
				LEFT JOIN tmodule_group
					ON tagente_modulo.id_module_group = tmodule_group.id_mg
				WHERE tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
					AND %s %s
					AND tagente_estado.estado != %d
					AND tagente_modulo.%s
				ORDER BY tmodule_group.name , %s  %s",
				$id_agente, $status_text_monitor_sql,$status_module_group_filter,$status_filter_sql, $tags_sql, AGENT_MODULE_STATUS_NO_DATA,
				$status_module_group_filter, $order['field'], $order['order']);

			break;
		case "postgresql":
			$sql = sprintf("
				SELECT *
				FROM tagente_estado,
					(SELECT *
					FROM tagente_modulo
					WHERE id_agente = %d AND nombre LIKE '%s' AND delete_pending = 0
						AND disabled = 0 AND %s) tagente_modulo
				LEFT JOIN tmodule_group
					ON tagente_modulo.id_module_group = tmodule_group.id_mg
				WHERE tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
					AND %s %s
					AND tagente_estado.estado != %d
					AND tagente_modulo.%s
				ORDER BY tmodule_group.name , %s  %s",
				$id_agente, $status_text_monitor_sql,$status_module_group_filter,$status_filter_sql, $tags_sql, AGENT_MODULE_STATUS_NO_DATA,
				$status_module_group_filter, $order['field'], $order['order']);
			break;
		// If Dbms is Oracle then field_list in sql statement has to be recoded. See oracle_list_all_field_table()
		case "oracle":
			$fields_tagente_estado = oracle_list_all_field_table('tagente_estado', 'string');
			$fields_tagente_modulo = oracle_list_all_field_table('tagente_modulo', 'string');
			$fields_tmodule_group = oracle_list_all_field_table('tmodule_group', 'string');

			$sql = sprintf ("
					SELECT " . $fields_tagente_estado . ', ' . $fields_tagente_modulo . ', ' . $fields_tmodule_group .
				" FROM tagente_estado, tagente_modulo
					LEFT JOIN tmodule_group
					ON tmodule_group.id_mg = tagente_modulo.id_module_group
				WHERE tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
					AND tagente_modulo.id_agente = %d
					AND tagente_modulo.nombre LIKE '%s'
					AND %s %s
					AND tagente_modulo.delete_pending = 0
					AND tagente_modulo.disabled = 0
					AND tagente_estado.estado != %d
					AND tagente_modulo.%s
				ORDER BY tmodule_group.name , %s %s
				", $id_agente, $status_text_monitor_sql, $tags_sql, $status_filter_sql, AGENT_MODULE_STATUS_NO_DATA,
				 $status_module_group_filter, $order['field'], $order['order']);
			break;
	}

	if ($monitors_change_filter) {
		$limit = " LIMIT " . $config['block_size'] . " OFFSET 0";
	}
	else {
		$limit = " LIMIT " . $config['block_size'] . " OFFSET " . get_parameter ('offset',0);
	}
	$paginate_module = false;
	if (isset($config['paginate_module']))
		$paginate_module = $config['paginate_module'];

	if ($paginate_module) {
		$modules = db_get_all_rows_sql ($sql . $limit);
	}
	else {
		$modules = db_get_all_rows_sql ($sql);
	}
	if (empty ($modules)) {
		$modules = array ();
	}
	$table->width = "100%";
	$table->cellpadding = 4;
	$table->cellspacing = 4;
	$table->class = "databox data";
	$table->head = array ();
	$table->data = array ();

	$isFunctionPolicies = enterprise_include_once ('include/functions_policies.php');
	if ($agent_w)
		$table->head[0] = "<span title='" . __('Force execution') . "'>" . __('F.') . "</span>";

	if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) {
		$table->head[1] = "<span title='" . __('Policy') . "'>" . __('P.') . "</span>";
	}

	$table->head[2] = __('Type') . ' ' .
		'<a href="' . $url . '&sort_field=type&amp;sort=up&refr=&filter_monitors=1&status_filter_monitor=' .$status_filter_monitor.' &status_text_monitor='. $status_text_monitor.'&status_module_group= '.$status_module_group.'">' . html_print_image("images/sort_up.png", true, array("style" => $selectTypeUp, "alt" => "up")) . '</a>' .
		'<a href="' . $url . '&sort_field=type&amp;sort=down&refr=&filter_monitors=1&status_filter_monitor=' .$status_filter_monitor.' &status_text_monitor='. $status_text_monitor.'&status_module_group= '.$status_module_group.'">' . html_print_image("images/sort_down.png", true, array("style" => $selectTypeDown, "alt" => "down")) . '</a>';
	$table->head[3] = __('Module name') . ' ' .
		'<a href="' . $url . '&sort_field=name&amp;sort=up&refr=&filter_monitors=1&status_filter_monitor=' .$status_filter_monitor.' &status_text_monitor='. $status_text_monitor.'&status_module_group= '.$status_module_group.'">' . html_print_image("images/sort_up.png", true, array("style" => $selectNameUp, "alt" => "up")) . '</a>' .
		'<a href="' . $url . '&sort_field=name&amp;sort=down&refr=&filter_monitors=1&status_filter_monitor=' .$status_filter_monitor.' &status_text_monitor='. $status_text_monitor.'&status_module_group= '.$status_module_group.'">' . html_print_image("images/sort_down.png", true, array("style" => $selectNameDown, "alt" => "down")) . '</a>';
	$table->head[4] = __('Description');
	$table->head[5] = __('Status') . ' ' .
		'<a href="' . $url . '&sort_field=status&amp;sort=up&refr=&filter_monitors=1&status_filter_monitor=' .$status_filter_monitor.' &status_text_monitor='. $status_text_monitor.'&status_module_group= '.$status_module_group.'">' . html_print_image("images/sort_up.png", true, array("style" => $selectStatusUp, "alt" => "up")) . '</a>' .
		'<a href="' . $url . '&sort_field=status&amp;sort=down&refr=&filter_monitors=1&status_filter_monitor=' .$status_filter_monitor.' &status_text_monitor='. $status_text_monitor.'&status_module_group= '.$status_module_group.'">' . html_print_image("images/sort_down.png", true, array("style" => $selectStatusDown, "alt" => "down")) . '</a>';
	$table->head[6] = __('Warn');
	$table->head[7] = __('Data');
	$table->head[8] = __('Graph');
	$table->head[9] = __('Last contact') . ' ' .
		'<a href="' . $url . '&sort_field=last_contact&amp;sort=up&refr=&filter_monitors=1&status_filter_monitor=' .$status_filter_monitor.' &status_text_monitor='. $status_text_monitor.'&status_module_group= '.$status_module_group.'">' . html_print_image("images/sort_up.png", true, array("style" => $selectLastContactUp, "alt" => "up")) . '</a>' .
		'<a href="' . $url . '&sort_field=last_contact&amp;sort=down&refr=&filter_monitors=1&status_filter_monitor=' .$status_filter_monitor.' &status_text_monitor='. $status_text_monitor.'&status_module_group= '.$status_module_group.'">' . html_print_image("images/sort_down.png", true, array("style" => $selectLastContactDown, "alt" => "down")) . '</a>';


	$table->align = array("left", "left", "left", "left", "left", "left","left","left","left");

	$last_modulegroup = 0;
	$rowIndex = 0;


	$id_type_web_content_string = db_get_value('id_tipo', 'ttipo_modulo',
		'nombre', 'web_content_string');

	$show_context_help_first_time = false;

	foreach ($modules as $module) {
		//The code add the row of 1 cell with title of group for to be more organice the list.

		if ($module["id_module_group"] != $last_modulegroup)
		{
			$table->colspan[$rowIndex][0] = count($table->head);
			$table->rowclass[$rowIndex] = 'datos4';

			array_push ($table->data, array ('<b>'.$module['name'].'</b>'));

			$rowIndex++;
			$last_modulegroup = $module["id_module_group"];
		}
		//End of title of group
		
		
		$data = array ();
		if (($module["id_modulo"] != 1) && ($module["id_tipo_modulo"] != 100)) {
			if ($agent_w) {
				if ($module["flag"] == 0) {
					$data[0] = '<a href="index.php?' .
						'sec=estado&amp;' .
						'sec2=operation/agentes/ver_agente&amp;' .
						'id_agente=' . $id_agente . '&amp;' .
						'id_agente_modulo=' . $module["id_agente_modulo"] . '&amp;' .
						'flag=1&amp;' .
						'refr=60">' . html_print_image("images/target.png", true, array("border" => '0', "title" => __('Force'))) . '</a>';
				}
				else {
					$data[0] = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$id_agente.'&amp;id_agente_modulo='.$module["id_agente_modulo"].'&amp;refr=60">' . html_print_image("images/refresh.png", true, array("border" => "0", "title" => __("Refresh"))) . '</a>';
				}
			}
		}
		else {
			$data[0] = '';
		}
		
		if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) {
			if ($module["id_policy_module"] != 0) {
				$linked = policies_is_module_linked($module['id_agente_modulo']);
				$id_policy = db_get_value_sql('SELECT id_policy FROM tpolicy_modules WHERE id = '.$module["id_policy_module"]);

				if ($id_policy != "")
					$name_policy = db_get_value_sql('SELECT name FROM tpolicies WHERE id = '.$id_policy);
				else
					$name_policy = __("Unknown");

				$policyInfo = policies_info_module_policy($module["id_policy_module"]);

				$adopt = false;
				if (policies_is_module_adopt($module['id_agente_modulo'])) {
					$adopt = true;
				}

				if ($linked) {
					if ($adopt) {
						$img = 'images/policies_brick.png';
						$title = '(' . __('Adopted') . ') ' . $name_policy;
					}
					else {
						$img = 'images/policies.png';
						$title = $name_policy;
					}
				}
				else {
					if ($adopt) {
						$img = 'images/policies_not_brick.png';
						$title = '(' . __('Unlinked') . ') (' . __('Adopted') . ') ' . $name_policy;
					}
					else {
						$img = 'images/unlinkpolicy.png';
						$title = '(' . __('Unlinked') . ') ' . $name_policy;
					}
				}

				$data[1] = '<a href="?sec=gpolicies&amp;sec2=enterprise/godmode/policies/policies&amp;id=' . $id_policy . '">' .
					html_print_image($img,true, array('title' => $title)) .
					'</a>';
			}
			else {
				$data[1] = "";
			}
		}

		$data[2] = servers_show_type ($module['id_modulo']) . '&nbsp;';

		if (check_acl ($config['id_user'], $id_grupo, "AW"))
			$data[2] .= '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;id_agente='.$id_agente.'&amp;tab=module&amp;id_agent_module='.$module["id_agente_modulo"].'&amp;edit_module='.$module["id_modulo"].'">' . html_print_image("images/config.png", true, array("alt" => '0', "border" => "", "title" => __('Edit'))) . '</a>';




		$data[3] = "";
		if ($module['quiet']) {
			$data[3] .= html_print_image("images/dot_green.disabled.png", true,
				array("border" => '0', "title" => __('Quiet'), "alt" => ""))
				. "&nbsp;";
		}
		$data[3] .= ui_print_truncate_text($module["nombre"], 'module_medium');
		if (!empty($module["extended_info"])) {
			if ($module["extended_info"] != "") {
				$data[3] .= ui_print_help_tip ($module["extended_info"], true, '/images/default_list.png');
			}
		}

		//Adds tag context information
		if (tags_get_modules_tag_count($module['id_agente_modulo']) > 0) {
			$data[3] .= ' <a class="tag_details" href="ajax.php?page=operation/agentes/estado_monitores&get_tag_tooltip=1&id_agente_modulo='.$module['id_agente_modulo'].'">' .
			html_print_image("images/tag_red.png", true, array("id" => 'tag-details-'.$module['id_agente_modulo'], "class" => "img_help")) . '</a> ';
		}

		//Adds relations context information
		if (modules_relation_exists($module['id_agente_modulo'])) {
			$data[3] .= ' <a class="relations_details" href="ajax.php?page=operation/agentes/estado_monitores&get_relations_tooltip=1&id_agente_modulo='.$module['id_agente_modulo'].'">' .
			html_print_image("images/link2.png", true, array("id" => 'relations-details-'.$module['id_agente_modulo'], "class" => "img_help")) . '</a> ';
		}


		$data[4] = ui_print_string_substr ($module["descripcion"], 60, true, 8);


		if ($module["datos"] != strip_tags($module["datos"])) {
			$module_value = io_safe_input($module["datos"]);
		}
		else {
			$module_value = io_safe_output($module["datos"]);
		}

		modules_get_status($module['id_agente_modulo'], $module['estado'],
			$module_value, $status, $title);

		$data[5] = ui_print_status_image($status, $title, true);
		if (!$show_context_help_first_time) {
			$show_context_help_first_time = true;

			if ($module['estado'] == AGENT_MODULE_STATUS_UNKNOWN) {
				$data[5] .= clippy_context_help("module_unknow");
			}
		}
		
		if ($module["id_tipo_modulo"] == 24) {
			// log4x
			switch($module["datos"]) {
				case 10:
					$salida = "TRACE";
					$style="font-weight:bold; color:darkgreen;";
					break;
				case 20:
					$salida = "DEBUG";
					$style="font-weight:bold; color:darkgreen;";
					break;
				case 30:
					$salida = "INFO";
					$style="font-weight:bold; color:darkgreen;";
					break;
				case 40:
					$salida = "WARN";
					$style="font-weight:bold; color:darkorange;";
					break;
				case 50:
					$salida = "ERROR";
					$style="font-weight:bold; color:red;";
					break;
				case 60:
					$salida = "FATAL";
					$style="font-weight:bold; color:red;";
					break;
			}
			$salida = "<span style='$style'>$salida</span>";
		}
		else {
			if (is_numeric($module["datos"]) && !modules_is_string_type($module['id_tipo_modulo'])) {
				if ( $config["render_proc"] ) {
					switch($module["id_tipo_modulo"]) {
						case 2:
						case 6:
						case 9:
						case 18:
						case 21:
						case 31:
							if ($module["datos"]>=1)
								$salida = $config["render_proc_ok"];
							else
								$salida = $config["render_proc_fail"];
							break;
						default:
							$salida = remove_right_zeros(number_format($module["datos"], $config['graph_precision']));
						break;
					}
				}
				else {
					$salida = remove_right_zeros(number_format($module["datos"], $config['graph_precision']));
				}
				// Show units ONLY in numeric data types
				if (isset($module["unit"])) {
					$salida .= "&nbsp;" . '<i>'. io_safe_output($module["unit"]) . '</i>';
				}
			}
			else {
				$salida = ui_print_module_string_value(
					$module["datos"], $module["id_agente_modulo"],
					$module["current_interval"], $module["module_name"]);
			}
		}
		
		$data[6] = ui_print_module_warn_value ($module["max_warning"], $module["min_warning"], $module["str_warning"], $module["max_critical"], $module["min_critical"], $module["str_critical"]);
		
		$data[7] = $salida;
		$graph_type = return_graphtype ($module["id_tipo_modulo"]);
		
		$data[8] = " ";
		if ($module['history_data'] == 1) {
			$nombre_tipo_modulo = modules_get_moduletype_name ($module["id_tipo_modulo"]);
			$handle = "stat".$nombre_tipo_modulo."_".$module["id_agente_modulo"];
			$url = 'include/procesos.php?agente='.$module["id_agente_modulo"];
			$win_handle=dechex(crc32($module["id_agente_modulo"].$module["nombre"]));
			
			# Show events for boolean modules by default.
			if ($graph_type == 'boolean') {
				$draw_events = 1;
			} else {
				$draw_events = 0;
			}
			$link ="winopeng('" .
				"operation/agentes/stat_win.php?" .
				"type=$graph_type&amp;" .
				"period=" . SECONDS_1DAY . "&amp;" .
				"id=" . $module["id_agente_modulo"] . "&amp;" .
				"label=" . rawurlencode(
					urlencode(
						base64_encode($module["nombre"]))) . "&amp;" .
				"refresh=" . SECONDS_10MINUTES . "&amp;" .
				"draw_events=$draw_events', 'day_".$win_handle."')";

if(!is_snapshot_data($module['datos'])){
			$data[8] .= '<a href="javascript:'.$link.'">' . html_print_image("images/chart_curve.png", true, array("border" => '0', "alt" => "")) . '</a> &nbsp;&nbsp;';
			}
			$server_name = '';
			$data[8] .= "<a href='javascript: " .
				"show_module_detail_dialog(" .
					$module["id_agente_modulo"] . ", ".
					$id_agente . ", " .
					"\"" . $server_name . "\", " .
					0 . ", " .
					SECONDS_1DAY . ", \" " . modules_get_agentmodule_name( $module["id_agente_modulo"] ) . "\")'>". html_print_image ("images/binary.png", true, array ("border" => "0", "alt" => "")) . "</a>";
		}
		
		if ($module['estado'] == 3) {
			$data[9] = '<span class="redb">';
		}
		else {
			$data[9] = '<span>';
		}
		$data[9] .= ui_print_timestamp ($module["utimestamp"], true, array('style' => 'font-size: 7pt'));
		$data[9] .= '</span>';
		
		array_push ($table->data, $data);
		$rowIndex++;
	}
	
	?>
	<script type="text/javascript">
		/* <![CDATA[ */
		$("a.tag_details").cluetip ({
			arrows: true,
			attribute: 'href',
			cluetipClass: 'default'
		})
		.click (function () {
			return false;
		});
		$("a.relations_details").cluetip ({
			width: 500,
			arrows: true,
			attribute: 'href',
			cluetipClass: 'default'
		})
		.click (function () {
			return false;
		});
		function toggle_full_value(id) {
			text = $('#hidden_value_module_' + id).html();
			old_text = $("#value_module_text_" + id).html();
			
			$("#hidden_value_module_" + id).html(old_text);
			
			$("#value_module_text_" + id).html(text);
		}
		/* ]]> */
	</script>
	<?php
	if (empty ($table->data)) {
		if ($filter_monitors) {
			ui_print_info_message(array( 'no_close'=>true, "message" => __('Any monitors aren\'t with this filter.') ) );
		}
		else {
			ui_print_info_message( array( 'no_close'=>true, "message" => __('This agent doesn\'t have any active monitors.') ) );
		}
	}
	else {
		$url = "index.php?" .
			"sec=estado&" .
			"sec2=operation/agentes/ver_agente&" .
			"id_agente=" . $id_agente . "&" .
			"refr=&filter_monitors=1&" .
			"status_filter_monitor=" . $status_filter_monitor . "&" .
			"status_text_monitor=" . $status_text_monitor . "&".
			"status_module_group=" . $status_module_group;

		if ($paginate_module) {
			ui_pagination ($count_modules, false, 0, 0, false, 'offset',
				true, '',
				"pagination_list_modules(offset_param)",
				array('count' => '', 'offset' => 'offset_param'));
		}

		html_print_table ($table);

		if ($paginate_module) {
			ui_pagination ($count_modules, false, 0, 0, false, 'offset',
				true, '',
				"pagination_list_modules(offset_param)",
				array('count' => '', 'offset' => 'offset_param'));
		}
	}

	unset ($table);
	unset ($table_data);
}

if ($get_type) {
	$id_module = (int) get_parameter("id_module");
	$module = modules_get_agentmodule($id_module);
	$graph_type = return_graphtype ($module["id_tipo_modulo"]);
	echo $graph_type;
	return;
}
}

?>
