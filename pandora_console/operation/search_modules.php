<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

global $config;

enterprise_include_once('include/functions_policies.php');
include_once($config['homedir'] . "/include/functions_modules.php");
include_once($config['homedir'] . '/include/functions_users.php');

$searchModules = check_acl($config['id_user'], 0, "AR");

if (!$modules || !$searchModules) {
	echo "<br><div class='nf'>" . __("Zero results found") . "</div>\n";
}
else {
	$table->cellpadding = 4;
	$table->cellspacing = 4;
	$table->width = "98%";
	$table->class = "databox";
	
	$table->head = array ();
	$table->head[0] = __('Module') . ' ' .
		'<a href="index.php?search_category=modules&keywords=' . 
			$config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . 
			'&sort_field=module_name&sort=up">'. html_print_image("images/sort_up.png", true, array("style" => $selectModuleNameUp)) . '</a>' .
		'<a href="index.php?search_category=modules&keywords=' .
			$config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . 
			'&sort_field=module_name&sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectModuleNameDown)) . '</a>';
	$table->head[1] = __('Agent') . ' ' .
		'<a href="index.php?search_category=modules&keywords=' . 
			$config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . 
			'&sort_field=agent_name&sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectAgentNameUp)) . '</a>' .
		'<a href="index.php?search_category=modules&keywords=' .
			$config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . 
			'&sort_field=agent_name&sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectAgentNameDown)) .'</a>';
	$table->head[2] = __('Type');
	$table->head[3] = __('Interval');
	$table->head[4] = __('Status');
	$table->head[5] = __('Graph');
	$table->head[6] = __('Data');
	$table->head[7] = __('Timestamp');
	$table->head[8] = "";
	
	
	
	$table->align = array ();
	$table->align[0] = "left";
	$table->align[1] = "left";
	$table->align[2] = "left";
	$table->align[3] = "left";
	$table->align[4] = "left";
	$table->align[5] = "left";
	$table->align[6] = "left";
	$table->align[7] = "left";
	$table->align[8] = "left";
	
	$table->data = array ();
	
	$id_type_web_content_string = db_get_value('id_tipo', 'ttipo_modulo',
		'nombre', 'web_content_string');
	
	foreach ($modules as $module) {
		$module["datos"] =
			modules_get_last_value($module['id_agente_modulo']);
		$module["module_name"] = $module['nombre'];
		
		//To search the monitor status
		$status_sql = sprintf('SELECT estado from tagente_estado where id_agente_modulo =' . $module['id_agente_modulo']);
		$status_sql = db_process_sql($status_sql);
		$status_sql = $status_sql[0];
		//To search the monitor utimestamp
		$utimestamp_sql = sprintf('SELECT utimestamp from tagente_estado where id_agente_modulo =' . $module['id_agente_modulo']);
		$utimestamp_sql = db_process_sql($utimestamp_sql);
		$utimestamp_sql = $utimestamp_sql[0];
		
		
		$agentCell = '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=' . $module['id_agente'] . '">' .
			$module['agent_name'] . '</a>';
		
		$typeCell = ui_print_moduletype_icon($module["id_tipo_modulo"], true);
		
		$intervalCell = modules_get_interval ($module['id_agente_modulo']);
		
		if ($utimestamp_sql['utimestamp'] == 0 &&
			(
				($module['id_tipo_modulo'] < 21 || $module['id_tipo_modulo'] > 23) &&
				$module['id_tipo_modulo'] != 100)
			) {
			$statusCell = ui_print_status_image(STATUS_MODULE_NO_DATA,
				__('NOT INIT'), true);
		}
		elseif ($status_sql['estado'] == 0) {
			$statusCell = ui_print_status_image(STATUS_MODULE_OK,
				__('NORMAL') . ": " . modules_get_last_value($module["id_agente_modulo"]), true);
		}
		elseif ($status_sql['estado'] == 1) {
			$statusCell = ui_print_status_image(STATUS_MODULE_CRITICAL,
				__('CRITICAL') . ": " . modules_get_last_value($module["id_agente_modulo"]), true);
		}
		elseif ($status_sql['estado'] == 2) {
			$statusCell = ui_print_status_image(STATUS_MODULE_WARNING,
				__('WARNING') . ": " . modules_get_last_value($module["id_agente_modulo"]), true);
		}
		else {
			$last_status = modules_get_agentmodule_last_status($module['id_agente_modulo']);
			switch($last_status) {
				case 0:
					$statusCell = ui_print_status_image(
						STATUS_MODULE_OK,
						__('UNKNOWN') . " - " . __('Last status') .
						" " . __('NORMAL') .": " . modules_get_last_value($module["id_agente_modulo"]),
						true);
					break;
				case 1:
					$statusCell = ui_print_status_image(
						STATUS_MODULE_CRITICAL,
						__('UNKNOWN') . " - " . __('Last status') .
						" " . __('CRITICAL') . ": " . modules_get_last_value($module["id_agente_modulo"]),
						true);
					break;
				case 2:
					$statusCell = ui_print_status_image(
						STATUS_MODULE_WARNING,
						__('UNKNOWN') . " - " . __('Last status') .
						" " . __('WARNING') . ": " . modules_get_last_value($module["id_agente_modulo"]),
						true);
					break;
			}
		}
		
		$graphCell = "";
		if ($module['history_data'] == 1) {
			
			$graph_type = return_graphtype ($module["id_tipo_modulo"]);
			
			$name_module_type = modules_get_moduletype_name ($module["id_tipo_modulo"]);
			$handle = "stat" . $name_module_type . "_" . $module["id_agente_modulo"];
			$url = 'include/procesos.php?agente=' . $module["id_agente_modulo"];
			$win_handle = dechex(crc32($module["id_agente_modulo"] . $module["module_name"]));
			
			$link ="winopeng('" .
				"operation/agentes/stat_win.php?" .
				"type=$graph_type&" .
				"period=" . SECONDS_1DAY . "&" .
				"id=" . $module["id_agente_modulo"] . "&" .
				"label=" . rawurlencode(
					urlencode(
						base64_encode($module["module_name"]))) . "&" .
				"refresh=" . SECONDS_10MINUTES . "', " .
				"'day_" . $win_handle . "')";
			
			$graphCell = '<a href="javascript:'.$link.'">' . html_print_image("images/chart_curve.png", true, array("border" => 0, "alt" => "")) . '</a>';
			$graphCell .= "&nbsp;" .
				"<a href='index.php?" .
				"sec=estado&amp;" .
				"sec2=operation/agentes/ver_agente&amp;" .
				"id_agente=" . $module["id_agente"] . "&amp;" .
				"tab=data_view&" .
				"period=" . SECONDS_1DAY . "&amp;" .
				"id=" . $module["id_agente_modulo"] . "'>" .
				html_print_image('images/binary.png', true,
					array("border" => "0", "alt" => "")) . "</a>";
		}
		
		if (is_numeric(modules_get_last_value($module["id_agente_modulo"]))) {
			$dataCell = format_numeric(modules_get_last_value($module["id_agente_modulo"]));
		}
		else {
			$dataCell = ui_print_module_string_value(
				$module["datos"], $module["id_agente_modulo"],
				$module["current_interval"]);
		}
		
		if ($module['estado'] == 3) {
			$option = array ("html_attr" => 'class="redb"');
		}
		else {
			$option = array ();
		}
		$timestampCell = ui_print_timestamp ($utimestamp_sql["utimestamp"], true, $option);
		
		
		$group_agent = agents_get_agent_group($module['id_agente']);
		
		if (check_acl ($config['id_user'], $group_agent, "AW")) {
			$edit_module = 'aaa';
			
			$url_edit = "index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente="
				. $module['id_agente'] . "&tab=module&id_agent_module=" . 
				$module["id_agente_modulo"] . "&edit_module=1";
			
			$edit_module = '<a href="' . $url_edit . '">' .
				html_print_image("images/config.png", true) . '</a>';
		}
		else {
			$edit_module = '';
		}
		
		
		array_push($table->data, array(
			$module['module_name'],
			$agentCell,
			$typeCell,
			$intervalCell,
			$statusCell,
			$graphCell,
			$dataCell,
			$timestampCell,
			$edit_module));
	}
	
	echo "<br />";
	ui_pagination ($totalModules);
	html_print_table ($table);
	unset($table);
	ui_pagination ($totalModules);
}
?>
