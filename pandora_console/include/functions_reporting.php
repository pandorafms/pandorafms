<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

/**
 * @package Include
 * @subpackage Reporting
 */

/**
 * Include the usual functions
 */
require_once($config["homedir"] . "/include/functions.php");
require_once($config["homedir"] . "/include/functions_db.php");
require_once($config["homedir"] . "/include/functions_agents.php");
include_once($config["homedir"] . "/include/functions_groups.php");
require_once($config["homedir"] . '/include/functions_graph.php');
include_once($config['homedir'] . "/include/functions_modules.php");
include_once($config['homedir'] . "/include/functions_events.php");
include_once($config['homedir'] . "/include/functions_alerts.php");
include_once($config['homedir'] . '/include/functions_users.php');
enterprise_include_once('include/functions_metaconsole.php');
enterprise_include_once('include/functions_inventory.php');
include_once($config['homedir'] . "/include/functions_forecast.php");
include_once($config['homedir'] . "/include/functions_ui.php");
include_once($config['homedir'] . "/include/functions_netflow.php");

function reporting_user_can_see_report($id_report, $id_user = null) {
	global $config;
	
	if (empty($id_user)) {
		$id_user = $config['id_user'];
	}
	
	// Get Report record (to get id_group)
	$report = db_get_row ('treport', 'id_report', $id_report);
	
	// Check ACL on the report to see if user has access to the report.
	if (empty($report) || !check_acl ($config['id_user'], $report['id_group'], "RR")) {
		return false;
	}
	
	return true;
}

function reporting_get_type($content) {
	switch ($content["type"]) {
		case REPORT_OLD_TYPE_SIMPLE_GRAPH:
			$content["type"] = 'simple_graph';
			break;
		case REPORT_OLD_TYPE_CUSTOM_GRAPH:
			$content["type"] = 'custom_graph';
			break;
		case REPORT_OLD_TYPE_MONITOR_REPORT:
			$content["type"] = 'monitor_report';
			break;
		case REPORT_OLD_TYPE_SLA:
			$content["type"] = 'SLA';
			break;
		case REPORT_OLD_TYPE_AVG_VALUE:
			$content["type"] = 'avg_value';
			break;
		case REPORT_OLD_TYPE_MAX_VALUE:
			$content["type"] = 'max_value';
			break;
		case REPORT_OLD_TYPE_MIN_VALUE:
			$content["type"] = 'min_value';
			break;
		case REPORT_OLD_TYPE_SUMATORY:
			$content["type"] = 'sumatory';
			break;
	}
	
	return $content["type"];
}

function reporting_get_description($id_report) {
	return db_get_value('description', 'treport', 'id_report', $id_report);
}

function reporting_get_name($id_report) {
	return db_get_value('name', 'treport', 'id_report', $id_report);
}

function reporting_make_reporting_data($id_report, $date, $time,
	$period = null, $type = 'dinamic', $force_width_chart = null,
	$force_height_chart = null) {
	
	global $config;
	
	$return = array();
	
	$report = db_get_row ('treport', 'id_report', $id_report);
	
	switch ($config["dbtype"]) {
		case "mysql":
			$contents = db_get_all_rows_field_filter ("treport_content",
				"id_report", $id_report, "`order`");
			break;
		case "postgresql":
			$contents = db_get_all_rows_field_filter ("treport_content",
				"id_report", $id_report, '"order"');
			break;
		case "oracle":
			$contents = db_get_all_rows_field_filter ("treport_content",
				"id_report", $id_report, '"order"');
			break;
	}
	if ($contents === false) {
		return $return;
	}
	
	$report["group_name"] = groups_get_name ($report['id_group']);
	
	$datetime = strtotime($date . ' ' . $time);
	$report["datetime"] = $datetime;
	
	$report['contents'] = array();
	
	foreach ($contents as $content) {
		if (!empty($period)) {
			$content['period'] = $period;
		}
		
		$content['style'] = json_decode(
			io_safe_output($content['style']), true);
		
		switch (reporting_get_type($content)) {
			case 'simple_graph':
				$report['contents'][] =
					reporting_simple_graph(
						$report,
						$content,
						$type,
						$force_width_chart,
						$force_height_chart);
				break;
			case 'general':
				$report['contents'][] =
					reporting_general(
						$report,
						$content);
				break;
			case 'sql':
				$report['contents'][] = reporting_sql(
					$report,
					$content);
				break;
			case 'custom_graph':
			case 'automatic_custom_graph':
				$report['contents'][] =
					reporting_custom_graph(
						$report,
						$content,
						$type,
						$force_width_chart,
						$force_height_chart);
				break;
			case 'text':
				$report['contents'][] = reporting_text(
					$report,
					$content);
				break;
			case 'url':
				$report['contents'][] = reporting_url(
					$report,
					$content,
					$type);
				break;
			case 'max_value':
				$report['contents'][] = reporting_value(
					$report,
					$content,
					'max');
				break;
			case 'avg_value':
				$report['contents'][] = reporting_value(
					$report,
					$content,
					'avg');
				break;
			case 'min_value':
				$report['contents'][] = reporting_value(
					$report,
					$content,
					'min');
				break;
			case 'sumatory':
				$report['contents'][] = reporting_value(
					$report,
					$content,
					'sum');
				break;
		}
	}
	
	return reporting_check_structure_report($report);
}

function reporting_value($report, $content, $type) {
	global $config;
	
	$return = array();
	switch ($type) {
		case 'max':
			$return['type'] = 'max_value';
			break;
		case 'min':
			$return['type'] = 'min_value';
			break;
		case 'avg':
			$return['type'] = 'avg_value';
			break;
		case 'sum':
			$return['type'] = 'sumatory';
			break;
	}
	
	
	if (empty($content['name'])) {
		switch ($type) {
			case 'max':
				$content['name'] = __('Max. Value');
				break;
			case 'min':
				$content['name'] = __('Min. Value');
				break;
			case 'avg':
				$content['name'] = __('AVG. Value');
				break;
			case 'sum':
				$content['name'] = __('Summatory');
				break;
		}
	}
	
	$module_name = io_safe_output(
		modules_get_agentmodule_name($content['id_agent_module']));
	$agent_name = io_safe_output(
		modules_get_agentmodule_agent_name ($content['id_agent_module']));
	
	$return['title'] = $content['name'];
	$return['subtitle'] = $agent_name . " - " . $module_name;
	$return["description"] = $content["description"];
	$return["date"] = reporting_get_date_text($report, $content);
	
	switch ($type) {
		case 'max':
			$value = reporting_get_agentmodule_data_max(
				$content['id_agent_module'], $content['period'], $report["datetime"]);
			break;
		case 'min':
			$value = reporting_get_agentmodule_data_min(
				$content['id_agent_module'], $content['period'], $report["datetime"]);
			break;
		case 'avg':
			$value = reporting_get_agentmodule_data_average(
				$content['id_agent_module'], $content['period'], $report["datetime"]);
			break;
		case 'sum':
			$value = reporting_get_agentmodule_data_sum(
				$content['id_agent_module'], $content['period'], $report["datetime"]);
			break;
	}
	
	$unit = db_get_value('unit', 'tagente_modulo', 'id_agente_modulo', $content ['id_agent_module']);
	
	$return['data'] = array(
		'value' => $value,
		'formated_value' => format_for_graph($value, 2) . " " . $unit);
	
	return reporting_check_structure_content($return);
}

function reporting_url($report, $content, $type = 'dinamic') {
	global $config;
	
	$return = array();
	$return['type'] = 'url';
	
	if (empty($content['name'])) {
		$content['name'] = __('Url');
	}
	
	$return['title'] = $content['name'];
	$return["description"] = $content["description"];
	$return["date"] = reporting_get_date_text();
	
	$return["url"] = $content["external_source"];
	
	switch ($type) {
		case 'dinamic':
			$return["data"] = null;
			break;
		case 'data':
		case 'static':
			$curlObj = curl_init();
			curl_setopt($curlObj, CURLOPT_URL, $content['external_source']);
			curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
			$output = curl_exec($curlObj);
			curl_close($curlObj);
			$return["data"] = $output;
			break;
	}
	
	return reporting_check_structure_content($return);
}

function reporting_text($report, $content) {
	
	global $config;
	
	$return = array();
	$return['type'] = 'text';
	
	if (empty($content['name'])) {
		$content['name'] = __('Text');
	}
	
	$return['title'] = $content['name'];
	$return["description"] = $content["description"];
	$return["date"] = reporting_get_date_text();
	
	$return["data"] = html_entity_decode($content['text']);
	
	return reporting_check_structure_content($return);
}

function reporting_sql($report, $content) {
	
	global $config;
	
	$return = array();
	$return['type'] = 'sql';
	
	if (empty($content['name'])) {
		$content['name'] = __('SQL');
	}
	
	$return['title'] = $content['name'];
	$return["description"] = $content["description"];
	$return["date"] = reporting_get_date_text();
	
	if ($content['treport_custom_sql_id'] != 0) {
		switch ($config["dbtype"]) {
			case "mysql":
				$sql = io_safe_output (db_get_value_filter('`sql`', 'treport_custom_sql', array('id' => $content['treport_custom_sql_id'])));
				break;
			case "postgresql":
				$sql = io_safe_output (db_get_value_filter('"sql"', 'treport_custom_sql', array('id' => $content['treport_custom_sql_id'])));
				break;
			case "oracle":
				$sql = io_safe_output (db_get_value_filter('sql', 'treport_custom_sql', array('id' => $content['treport_custom_sql_id'])));
				break;
		}
	}
	else {
		$sql = io_safe_output ($content['external_source']);
	}
	
	// Do a security check on SQL coming from the user
	$sql = check_sql ($sql);
	
	$return['sql'] = $sql;
	$return['correct'] = 1;
	$return['error'] = "";
	$return['data'] = array();
	if ($sql != '') {
		$header = array();
		if ($content['header_definition'] != '') {
			$header = explode('|', $content['header_definition']);
		}
		
		$result = db_get_all_rows_sql($sql);
		if ($result !== false) {
			
			foreach ($result as $row) {
				$data_row = array();
				
				$i = 0;
				foreach ($row as $dbkey => $field) {
					if (isset($header[$i])) {
						$key = $header[$i];
					}
					else {
						$key = $dbkey;
					}
					$data_row[$key] = $field;
					
					$i++;
				}
				
				$return['data'][] = $data_row;
			}
		}
	}
	else {
		$return['correct'] = 0;
		$return['error'] = __('Illegal query: Due security restrictions, there are some tokens or words you cannot use: *, delete, drop, alter, modify, union, password, pass, insert or update.');
	}
	
	return reporting_check_structure_content($return);
}

function reporting_general($report, $content) {
	
	global $config;
	
	$return = array();
	$return['type'] = 'general';
	$return['subtype'] = $content['group_by_agent'];
	$return['resume'] = $content['show_resume'];
	
	if (empty($content['name'])) {
		$content['name'] = __('General');
	}
	
	$return['title'] = $content['name'];
	$return["description"] = $content["description"];
	$return["date"] = reporting_get_date_text(
		$report,
		$content);
	
	$return["data"] = array();
	$return["avg_value"] = 0;
	$return["min"] = array();
	$return["min"]["value"] = null;
	$return["min"]["formated_value"] = null;
	$return["min"]["agent"] = null;
	$return["min"]["module"] = null;
	$return["max"] = array();
	$return["max"]["value"] = null;
	$return["max"]["formated_value"] = null;
	$return["max"]["agent"] = null;
	$return["max"]["module"] = null;
	
	$generals = db_get_all_rows_filter(
		'treport_content_item',
		array('id_report_content' => $content['id_rc']));
	if (empty($generals)) {
		$generals = array();
	}
	
	$i = 0;
	foreach ($generals as $key => $row) {
		//Metaconsole connection
		$server_name = $row ['server_name'];
		if (($config ['metaconsole'] == 1) && $server_name != '' && defined('METACONSOLE')) {
			$connection = metaconsole_get_connection($server_name);
			if (metaconsole_load_external_db($connection) != NOERR) {
				//ui_print_error_message ("Error connecting to ".$server_name);
				continue;
			}
		}
		
		if (modules_is_disable_agent($row['id_agent_module'])) {
			continue;
		}
		
		$mod_name = modules_get_agentmodule_name ($row['id_agent_module']);
		$ag_name = modules_get_agentmodule_agent_name ($row['id_agent_module']);
		$unit = db_get_value('unit', 'tagente_modulo',
			'id_agente_modulo',
			$row['id_agent_module']);
		
		if ($content['period'] == 0) {
			$data_res[$key] =
				modules_get_last_value($row['id_agent_module']);
		}
		else {
			switch ($row['operation']) {
				case 'sum':
					$data_res[$key] =
						reporting_get_agentmodule_data_sum(
							$row['id_agent_module'], $content['period'], $report["datetime"]);
					break;
				case 'max':
					$data_res[$key] =
						reporting_get_agentmodule_data_max(
							$row['id_agent_module'], $content['period']);
					break;
				case 'min':
					$data_res[$key] =
						reporting_get_agentmodule_data_min(
							$row['id_agent_module'], $content['period']);
					break;
				case 'avg':
				default:
					$data_res[$key] =
						reporting_get_agentmodule_data_average(
							$row['id_agent_module'], $content['period']);
					break;
			}
		}
		
		switch ($content['group_by_agent']) {
			case REPORT_GENERAL_NOT_GROUP_BY_AGENT:
				$id_agent_module[$key] = $row['id_agent_module'];
				$agent_name[$key] = $ag_name;
				$module_name[$key] = $mod_name;
				$units[$key] = $unit;
				$operations[$key] = $row['operation'];
				break;
			case REPORT_GENERAL_GROUP_BY_AGENT:
				if ($data_res[$key] === false) {
					$return["data"][$ag_name][$mod_name] = null;
				}
				else {
					if (!is_numeric($data_res[$key])) {
						$return["data"][$ag_name][$mod_name] = $data_res[$key];
					}
					else {
						$return["data"][$ag_name][$mod_name] =
							format_for_graph($data_res[$key], 2) . " " . $unit;
					}
				}
				break;
		}
		
		// Calculate the avg, min and max
		if (is_numeric($data_res[$key])) {
			$change_min = false;
			if (is_null($return["min"]["value"])) {
				$change_min = true;
			}
			else {
				if ($return["min"]["value"] > $data_res[$key]) {
					$change_min = true;
				}
			}
			if ($change_min) {
				$return["min"]["value"] = $data_res[$key];
				$return["min"]["formated_value"] =
					format_for_graph($data_res[$key], 2) . " " . $unit;
				$return["min"]["agent"] = $ag_name;
				$return["min"]["module"] = $mod_name;
			}
			
			$change_max = false;
			if (is_null($return["max"]["value"])) {
				$change_max = true;
			}
			else {
				if ($return["max"]["value"] < $data_res[$key]) {
					$change_max = true;
				}
			}
			
			if ($change_max) {
				$return["max"]["value"] = $data_res[$key];
				$return["max"]["formated_value"] =
					format_for_graph($data_res[$key], 2) . " " . $unit;
				$return["max"]["agent"] = $ag_name;
				$return["max"]["module"] = $mod_name;
			}
			
			if ($i == 0) {
				$return["avg_value"] = $data_res[$key];
			}
			else {
				$return["avg_value"] =
					(($return["avg_value"] * $i) / ($i + 1))
					+
					($data_res[$key] / ($i + 1));
			}
		}
		
		$i++;
		
		//Restore dbconnection
		if (($config ['metaconsole'] == 1) && $server_name != '' && defined('METACONSOLE')) {
			metaconsole_restore_db();
		}
	}
	
	switch ($content['group_by_agent']) {
		case REPORT_GENERAL_NOT_GROUP_BY_AGENT:
			switch ($content['order_uptodown']) {
				case REPORT_ITEM_ORDER_BY_AGENT_NAME:
					array_multisort($agent_name, SORT_ASC,
						$data_res, SORT_ASC, $module_name, SORT_ASC,
						$id_agent_module, SORT_ASC, $operations,
						SORT_ASC);
					break;
				case REPORT_ITEM_ORDER_BY_ASCENDING:
					array_multisort($data_res, SORT_ASC,
						$agent_name, SORT_ASC, $module_name,
						SORT_ASC, $id_agent_module,
						SORT_ASC, $operations, SORT_ASC);
					break;
				case REPORT_ITEM_ORDER_BY_DESCENDING:
					array_multisort($data_res, SORT_DESC,
						$agent_name, SORT_ASC, $module_name,
						SORT_ASC, $id_agent_module,
						SORT_ASC, $operations, SORT_ASC);
					break;
				case REPORT_ITEM_ORDER_BY_UNSORT:
					break;
			}
			
			
			
			$i = 0;
			foreach ($data_res as $d) {
				$data = array();
				$data['agent'] = $agent_name[$i];
				$data['module'] = $module_name[$i];
				
				
				$data['operator'] = "";
				if ($content['period'] != 0) {
					switch ($operations[$i]) {
						case 'sum':
							$data['operator'] = __('Summatory');
							break;
						case 'min':
							$data['operator'] = __('Minimal');
							break;
						case 'max':
							$data['operator'] = __('Maximun');
							break;
						case 'avg':
						default:
							$data['operator'] = __('Rate');
							break;
					}
				}
				
				if ($d === false) {
					$data['value'] = null;
				}
				else {
					if (!is_numeric($d)) {
						$data['value'] = $d;
					}
					else {
						$data['value'] = format_for_graph($d, 2) . " " .
							$units[$i];
					}
				}
				$return["data"][] = $data;
				
				$i++;
			}
			break;
	}
	
	return reporting_check_structure_content($return);
}

function reporting_custom_graph($report, $content, $type = 'dinamic',
	$force_width_chart = null, $force_height_chart = null) {
	
	global $config;
	
	require_once ($config["homedir"] . '/include/functions_graph.php');
	
	$graph = db_get_row ("tgraph", "id_graph", $content['id_gs']);
	
	$return = array();
	$return['type'] = 'custom_graph';
	
	if (empty($content['name'])) {
		$content['name'] = __('Simple graph');
	}
	
	$return['title'] = $content['name'];
	$return['subtitle'] = $graph['name'];
	$return["description"] = $content["description"];
	$return["date"] = reporting_get_date_text(
		$report,
		$content);
	
	$graphs = db_get_all_rows_field_filter ("tgraph_source",
		"id_graph", $content['id_gs']);
	$modules = array ();
	$weights = array ();
	if ($graphs === false)
		$graphs = array();
	
	foreach ($graphs as $graph_item) {
		array_push ($modules, $graph_item['id_agent_module']);
		array_push ($weights, $graph_item["weight"]);
	}
	
	$return['chart'] = '';
	// Get chart
	reporting_set_conf_charts($width, $height, $only_image, $type, $content);
	
	$height += count($modules) * REPORTING_CUSTOM_GRAPH_LEGEND_EACH_MODULE_VERTICAL_SIZE;
	
	switch ($type) {
		case 'dinamic':
		case 'static':
			$return['chart'] = graphic_combined_module(
				$modules,
				$weights,
				$content['period'],
				$width, $height,
				'Combined%20Sample%20Graph',
				'',
				0,
				0,
				0,
				$graph["stacked"],
				$report["datetime"],
				$only_image,
				ui_get_full_url(false, false, false, false));
			break;
		case 'data':
			break;
	}
	
	return reporting_check_structure_content($return);
}

function reporting_simple_graph($report, $content, $type = 'dinamic',
	$force_width_chart = null, $force_height_chart = null) {
	
	global $config;
	
	$return = array();
	$return['type'] = 'simple_graph';
	
	if (empty($content['name'])) {
		$content['name'] = __('Simple graph');
	}
	
	$module_name = io_safe_output(
		modules_get_agentmodule_name($content['id_agent_module']));
	$agent_name = io_safe_output(
		modules_get_agentmodule_agent_name ($content['id_agent_module']));
	
	
	$return['title'] = $content['name'];
	$return['subtitle'] = $agent_name . " - " . $module_name;
	$return["description"] = $content["description"];
	$return["date"] = reporting_get_date_text(
		$report,
		$content);
	
	$only_avg = true;
	// Due to database compatibility problems, the 'only_avg' value
	// is stored into the json contained into the 'style' column.
	if (isset($style['only_avg'])) {
		$only_avg = (bool) $style['only_avg'];
	}
	
	$moduletype_name = modules_get_moduletype_name(
		modules_get_agentmodule_type(
			$content['id_agent_module']));
	
	
	
	$return['chart'] = '';
	// Get chart
	reporting_set_conf_charts($width, $height, $only_image, $type, $content);
	
	if (!empty($force_width_chart)) {
		$width = $force_width_chart;
	}
	
	if (!empty($force_height_chart)) {
		$height = $force_height_chart;
	}
	
	switch ($type) {
		case 'dinamic':
		case 'static':
			if (preg_match ("/string/", $moduletype_name)) {
				
				$urlImage = ui_get_full_url(false, false, false, false);
				
				$return['chart'] = grafico_modulo_string(
					$content['id_agent_module'],
					$content['period'],
					false,
					$width,
					$height,
					'',
					'',
					false,
					$only_avg,
					false,
					$report["datetime"], $only_image, $urlImage);
				
			}
			else {
				$return['chart'] = grafico_modulo_sparse(
					$content['id_agent_module'],
					$content['period'],
					false,
					$width,
					$height,
					'',
					'',
					false,
					$only_avg,
					true,
					$report["datetime"],
					'',
					0,
					0,
					true,
					$only_image,
					ui_get_full_url(false, false, false, false),
					1,
					false,
					'',
					false,
					true);
			}
			break;
		case 'data':
			break;
	}
	
	return reporting_check_structure_content($return);
}

function reporting_get_date_text($report = null, $content = null) {
	global $config;
	
	$return = array();
	$return['date'] = null;
	$return['period'] = null;
	$return['from'] = null;
	$return['to'] = null;
	
	if (!empty($report) && !empty($content)) {
		
		if ($content['period'] == 0) {
			$es = json_decode($content['external_source'], true);
			if ($es['date'] == 0) {
				$return['period'] = 0;
			}
			else {
				$return['date'] = $es['date'];
			}
		}
		else {
			$return['period'] = $content['period'];
			$return['from'] = $report["datetime"] - $content['period'];
			$return['to'] = $report["datetime"];
		}
	}
	
	return $return;
}

/**
 * Check the common items exits
 */
function reporting_check_structure_report($return) {
	if (!isset($return['group_name']))
		$return['group_name'] = "";
	if (!isset($return['title']))
		$return['title'] = "";
	if (!isset($return['datetime']))
		$return['datetime'] = "";
	if (!isset($return['period']))
		$return['period'] = "";
	
	return $return;
}

/**
 * Check the common items exits
 */
function reporting_check_structure_content($report) {
	if (!isset($report['title']))
		$report['title'] = "";
	if (!isset($report['subtitle']))
		$report['subtitle'] = "";
	if (!isset($report['description']))
		$report['description'] = "";
	if (!isset($report["date"])) {
		$report["date"]['date'] = "";
		$report["date"]['period'] = "";
		$report["date"]['from'] = "";
		$report["date"]['to'] = "";
	}
	
	return $report;
}

function reporting_set_conf_charts(&$width, &$height, &$only_image, $type, $content) {
	switch ($type) {
		case 'dinamic':
			$only_image = false;
			$width = 900;
			$height = 230;
			break;
		case 'static':
			$only_image = true;
			if ($content['style']['show_in_landscape']) {
				$height = 1100;
				$width = 1700;
			}
			else {
				$height = 360;
				$width = 780;
			}
			break;
		case 'data':
			break;
	}
}

////////////////////////////////////////////////////////////////////////
// MAYBE MOVE THE NEXT FUNCTIONS TO A FILE NAMED AS FUNCTION_REPORTING.UTILS.PHP //
////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////

/** 
 * Get general statistical info on a group
 * 
 * @param int Group Id to get info from. 0 = all
 * 
 * @return array Group statistics
 */
function reporting_get_group_stats ($id_group = 0, $access = 'AR') {
	global $config;
	
	$data = array ();
	$data["monitor_checks"] = 0;
	$data["monitor_not_init"] = 0;
	$data["monitor_unknown"] = 0;
	$data["monitor_ok"] = 0;
	$data["monitor_bad"] = 0; // Critical + Unknown + Warning
	$data["monitor_warning"] = 0;
	$data["monitor_critical"] = 0;
	$data["monitor_not_normal"] = 0;
	$data["monitor_alerts"] = 0;
	$data["monitor_alerts_fired"] = 0;
	$data["monitor_alerts_fire_count"] = 0;
	$data["total_agents"] = 0;
	$data["total_alerts"] = 0;
	$data["total_checks"] = 0;
	$data["alerts"] = 0;
	$data["agents_unknown"] = 0;
	$data["monitor_health"] = 100;
	$data["alert_level"] = 100;
	$data["module_sanity"] = 100;
	$data["server_sanity"] = 100;
	$data["total_not_init"] = 0;
	$data["monitor_non_init"] = 0;
	$data["agent_ok"] = 0;
	$data["agent_warning"] = 0;
	$data["agent_critical"] = 0;
	$data["agent_unknown"] = 0;
	$data["agent_not_init"] = 0;
	
	$cur_time = get_system_time ();
	
	//Check for access credentials using check_acl. More overhead, much safer
	if (!check_acl ($config["id_user"], $id_group, $access)) {
		return $data;
	}
	
	if ($id_group == 0) {
		$id_group = array_keys(
			users_get_groups($config['id_user'], $access, false));
	}
	
	// -----------------------------------------------------------------
	// Server processed stats. NOT realtime (taken from tgroup_stat)
	// -----------------------------------------------------------------
	if ($config["realtimestats"] == 0) {
		
		if (!is_array($id_group)){
			$my_group = $id_group;
			$id_group = array();
			$id_group[0] = $my_group;
		}
		
		foreach ($id_group as $group) {
			$group_stat = db_get_all_rows_sql ("SELECT *
				FROM tgroup_stat, tgrupo
				WHERE tgrupo.id_grupo = tgroup_stat.id_group
					AND tgroup_stat.id_group = $group
				ORDER BY nombre");
			
			$data["monitor_checks"] += $group_stat[0]["modules"];
			$data["agent_not_init"] += $group_stat[0]["non-init"];
			$data["agent_unknown"] += $group_stat[0]["unknown"];
			$data["agent_ok"] += $group_stat[0]["normal"];
			$data["agent_warning"] += $group_stat[0]["warning"];
			$data["agent_critical"] += $group_stat[0]["critical"];
			$data["monitor_alerts"] += $group_stat[0]["alerts"];
			$data["monitor_alerts_fired"] += $group_stat[0]["alerts_fired"];
			$data["monitor_alerts_fire_count"] += $group_stat[0]["alerts_fired"];
			$data["total_checks"] += $group_stat[0]["modules"];
			$data["total_alerts"] += $group_stat[0]["alerts"];
			$data["total_agents"] += $group_stat[0]["agents"];
			$data["agents_unknown"] += $group_stat[0]["agents_unknown"];
			$data["utimestamp"] = $group_stat[0]["utimestamp"];
			
			// This fields are not in database
			$data["monitor_ok"] += (int) groups_get_normal_monitors($group);
			$data["monitor_warning"] += (int) groups_get_warning_monitors($group);
			$data["monitor_critical"] += (int) groups_get_critical_monitors($group);
			$data["monitor_unknown"] += (int) groups_get_unknown_monitors($group);
			$data["monitor_not_init"] += (int) groups_get_not_init_monitors($group);
		}
		
	// -------------------------------------------------------------------
	// Realtime stats, done by PHP Console
	// -------------------------------------------------------------------
	}
	else {
		
		if (!is_array($id_group)) {
			$my_group = $id_group;
			$id_group = array();
			$id_group[0] = $my_group;
		}
		
		// Store the groups where we are quering
		$covered_groups = array();
		$group_array = array();
		foreach ($id_group as $group) {
			$children = groups_get_childrens($group);
			
			//Show empty groups only if they have children with agents
			//$group_array = array();
			
			foreach ($children as $sub) {
				// If the group is quering previously, we ingore it
				if (!in_array($sub['id_grupo'],$covered_groups)) {
					array_push($covered_groups, $sub['id_grupo']);
					array_push($group_array, $sub['id_grupo']);
				}
				
			}
			
			// Add id of this group to create the clause
			// If the group is quering previously, we ingore it
			if (!in_array($group,$covered_groups)) {
				array_push($covered_groups, $group);
				array_push($group_array, $group);
			}
			
			// If there are not groups to query, we jump to nextone
			
			if (empty($group_array)) {
				continue;
			}
		}
		
		if (!empty($group_array)) {
			// FOR THE FUTURE: Split the groups into groups with tags restrictions and groups without it
			// To calculate in the light way the non tag restricted and in the heavy way the others
			/*
			$group_restricted_data = tags_get_acl_tags($config['id_user'], $group_array, $access, 'data');
			$tags_restricted_groups = array_keys($group_restricted_data);
			
			$no_tags_restricted_groups = $group_array;
			foreach ($no_tags_restricted_groups as $k => $v) {
				if (in_array($v, $tags_restricted_groups)) {
					unset($no_tags_restricted_groups[$k]);
				}
			}
			*/
			
			if (!empty($group_array)) {
				// Get monitor NOT INIT, except disabled AND async modules
				$data["monitor_not_init"] += (int) groups_get_not_init_monitors ($group_array, array(), array(), false, false, true);
				
				// Get monitor OK, except disabled and non-init
				$data["monitor_ok"] += (int) groups_get_normal_monitors ($group_array, array(), array(), false, false, true);
				
				// Get monitor CRITICAL, except disabled and non-init
				$data["monitor_critical"] += (int) groups_get_critical_monitors ($group_array, array(), array(), false, false, true);
				
				// Get monitor WARNING, except disabled and non-init
				$data["monitor_warning"] += (int) groups_get_warning_monitors ($group_array, array(), array(), false, false, true);
				
				// Get monitor UNKNOWN, except disabled and non-init
				$data["monitor_unknown"] += (int) groups_get_unknown_monitors ($group_array, array(), array(), false, false, true);
				
				// Get alerts configured, except disabled 
				$data["monitor_alerts"] += groups_monitor_alerts ($group_array) ;
				
				// Get alert configured currently FIRED, except disabled 
				$data["monitor_alerts_fired"] += groups_monitor_fired_alerts ($group_array);
				
				// Calculate totals using partial counts from above
				
				// Get TOTAL non-init modules, except disabled ones and async modules
				$data["total_not_init"] += $data["monitor_not_init"];
			
				// Get TOTAL agents in a group
				$data["total_agents"] += (int) groups_get_total_agents ($group_array, array(), array(), false, false, true);
				
				// Get Agents OK
				$data["agent_ok"] += (int) groups_get_normal_agents ($group_array, array(), array(), false, false, true);
				
				// Get Agents Warning 
				$data["agent_warning"] += (int) groups_get_warning_agents ($group_array, array(), array(), false, false, true);
				
				// Get Agents Critical
				$data["agent_critical"] += (int) groups_get_critical_agents ($group_array, array(), array(), false, false, true);
				
				// Get Agents Unknown
				$data["agent_unknown"] += (int) groups_get_unknown_agents ($group_array, array(), array(), false, false, true);
				
				// Get Agents Not init
				$data["agent_not_init"] += (int) groups_get_not_init_agents ($group_array, array(), array(), false, false, true);
			}
			
			// Get total count of monitors for this group, except disabled.
			$data["monitor_checks"] = $data["monitor_not_init"] + $data["monitor_unknown"] + $data["monitor_warning"] + $data["monitor_critical"] + $data["monitor_ok"];
			
			// Calculate not_normal monitors
			$data["monitor_not_normal"] += $data["monitor_checks"] - $data["monitor_ok"];
		}
		
		// Get total count of monitors for this group, except disabled.
		
		$data["monitor_checks"] = $data["monitor_not_init"] + $data["monitor_unknown"] + $data["monitor_warning"] + $data["monitor_critical"] + $data["monitor_ok"];
		
		/*
		 Monitor health (percentage)
		 Data health (percentage)
		 Global health (percentage)
		 Module sanity (percentage)
		 Alert level (percentage)
		 
		 Server Sanity	0% Uninitialized modules
		 
		 */
	}
	
	if ($data["monitor_unknown"] > 0 && $data["monitor_checks"] > 0) {
		$data["monitor_health"] = format_numeric (100 - ($data["monitor_not_normal"] / ($data["monitor_checks"] / 100)), 1);
	}
	else {
		$data["monitor_health"] = 100;
	}
	
	if ($data["monitor_not_init"] > 0 && $data["monitor_checks"] > 0) {
		$data["module_sanity"] = format_numeric (100 - ($data["monitor_not_init"] / ($data["monitor_checks"] / 100)), 1);
	}
	else {
		$data["module_sanity"] = 100;
	}
	
	if (isset($data["alerts"])) {
		if ($data["monitor_alerts_fired"] > 0 && $data["alerts"] > 0) {
			$data["alert_level"] = format_numeric (100 - ($data	["monitor_alerts_fired"] / ($data["alerts"] / 100)), 1);
		}
		else {
			$data["alert_level"] = 100;
		}
	} 
	else {
		$data["alert_level"] = 100;
		$data["alerts"] = 0;
	}
	
	$data["monitor_bad"] = $data["monitor_critical"] + $data["monitor_warning"];
	
	if ($data["monitor_bad"] > 0 && $data["monitor_checks"] > 0) {
		$data["global_health"] = format_numeric (100 - ($data["monitor_bad"] / ($data["monitor_checks"] / 100)), 1);
	}
	else {
		$data["global_health"] = 100;
	}
	
	$data["server_sanity"] = format_numeric (100 - $data["module_sanity"], 1);
	
	
	$data['alert_fired'] = 0;
	if ($data["monitor_alerts_fired"] > 0) {
		$data['alert_fired'] = 1;
	}
	
	
	if ($data["monitor_critical"] > 0) {
		$data['status'] = 'critical';
	}
	elseif ($data["monitor_warning"] > 0) {
		$data['status'] = 'warning';
	}
	elseif (($data["monitor_unknown"] > 0) ||  ($data["agents_unknown"] > 0)) {
		$data['status'] = 'unknown';
	}
	elseif ($data["monitor_ok"] > 0)  {
		$data['status'] = 'ok';
	}
	elseif ($data["agent_not_init"] > 0)  {
		$data['status'] = 'not_init';
	}
	else {
		$data['status'] = 'none';
	}
	
	return ($data);
}

function reporting_get_stats_indicators($data, $width = 280, $height = 20, $html = true) {
	$table_ind = html_get_predefined_table();
	
	$servers = array();
	$servers["all"] = (int) db_get_value ('COUNT(id_server)','tserver');
	$servers["up"] = (int) servers_check_status ();
	$servers["down"] = $servers["all"] - $servers["up"];
	if ($servers["all"] == 0) {
		$servers["health"] = 0;
	}
	else {
		$servers["health"] = $servers["up"] / ($servers["all"] / 100);
	}
	
	if ($html) {
		$tdata[0] = '<fieldset class="databox tactical_set">
						<legend>' . 
							__('Server health') . ui_print_help_tip (sprintf(__('%d Downed servers'), $servers["down"]), true) . 
						'</legend>' . 
						progress_bar($servers["health"], $width, $height, '', 0) . '</fieldset>';
		$table_ind->rowclass[] = '';
		$table_ind->data[] = $tdata;
		
		$tdata[0] = '<fieldset class="databox tactical_set">
						<legend>' . 
							__('Monitor health') . ui_print_help_tip (sprintf(__('%d Not Normal monitors'), $data["monitor_not_normal"]), true) . 
						'</legend>' . 
						progress_bar($data["monitor_health"], $width, $height, $data["monitor_health"].'% '.__('of monitors up'), 0) . '</fieldset>';
		$table_ind->rowclass[] = '';
		$table_ind->data[] = $tdata;
		
		$tdata[0] = '<fieldset class="databox tactical_set">
						<legend>' . 
							__('Module sanity') . ui_print_help_tip (sprintf(__('%d Not inited monitors'), $data["monitor_not_init"]), true) .
						'</legend>' . 
						progress_bar($data["module_sanity"], $width, $height, $data["module_sanity"].'% '.__('of total modules inited'), 0) . '</fieldset>';
		$table_ind->rowclass[] = '';
		$table_ind->data[] = $tdata;
		
		$tdata[0] = '<fieldset class="databox tactical_set">
						<legend>' . 
							__('Alert level') . ui_print_help_tip (sprintf(__('%d Fired alerts'), $data["monitor_alerts_fired"]), true) . 
						'</legend>' . 
						progress_bar($data["alert_level"], $width, $height, $data["alert_level"].'% '.__('of defined alerts not fired'), 0) . '</fieldset>';
		$table_ind->rowclass[] = '';
		$table_ind->data[] = $tdata;
		
		
		return html_print_table($table_ind, true);
	}
	else {
		$return = array();
		
		$return['server_health'] = array(
			'title' => __('Server health'),
			'graph' => progress_bar($servers["health"], $width, $height, '', 0));
		$return['monitor_health'] = array(
			'title' => __('Monitor health'),
			'graph' => progress_bar($data["monitor_health"], $width, $height, $data["monitor_health"].'% '.__('of monitors up'), 0));
		$return['module_sanity'] = array(
			'title' => __('Module sanity'),
			'graph' => progress_bar($data["module_sanity"], $width, $height, $data["module_sanity"].'% '.__('of total modules inited'), 0));
		$return['alert_level'] = array(
			'title' => __('Alert level'),
			'graph' => progress_bar($data["alert_level"], $width, $height, $data["alert_level"].'% '.__('of defined alerts not fired'), 0));
		
		return $return;
	}
}

function reporting_get_stats_alerts($data, $links = false) {
	global $config;
	
	// Link URLS
	$mobile = false;
	if (isset($data['mobile'])) {
		if ($data['mobile']) {
			$mobile = true;
		}
	}
	
	if ($mobile) {
		$urls = array();
		$urls['monitor_alerts'] = "index.php?page=alerts&status=all_enabled";
		$urls['monitor_alerts_fired'] = "index.php?page=alerts&status=fired";
	}
	else {
		$urls = array();
		if ($links) {
			$urls['monitor_alerts'] = "index.php?sec=estado&sec2=operation/agentes/alerts_status&pure=" . $config['pure'];
			$urls['monitor_alerts_fired'] = "index.php?sec=estado&sec2=operation/agentes/alerts_status&filter=fired&pure=" . $config['pure'];
		} else {
			$urls['monitor_alerts'] = "index.php?sec=estado&amp;sec2=operation/agentes/alerts_status&amp;refr=60";
			$urls['monitor_alerts_fired'] = "index.php?sec=estado&amp;sec2=operation/agentes/alerts_status&amp;refr=60&filter=fired";
		}
	}
	
	// Alerts table
	$table_al = html_get_predefined_table();
	
	$tdata = array();
	$tdata[0] = html_print_image('images/bell.png', true, array('title' => __('Defined alerts')));
	$tdata[1] = $data["monitor_alerts"] <= 0 ? '-' : $data["monitor_alerts"];
	$tdata[1] = '<a class="big_data" href="' . $urls["monitor_alerts"] . '">' . $tdata[1] . '</a>';
	
	$tdata[2] = html_print_image('images/bell_error.png', true, array('title' => __('Fired alerts')));
	$tdata[3] = $data["monitor_alerts_fired"] <= 0 ? '-' : $data["monitor_alerts_fired"];
	$tdata[3] = '<a style="color: ' . COL_ALERTFIRED . ';" class="big_data" href="' . $urls["monitor_alerts_fired"] . '">' . $tdata[3] . '</a>';
	$table_al->rowclass[] = '';
	$table_al->data[] = $tdata;
	
	if(!defined('METACONSOLE')){
		$output = '<fieldset class="databox tactical_set">
					<legend>' . 
						__('Defined and fired alerts') . 
					'</legend>' . 
					html_print_table($table_al, true) . '</fieldset>';
	}else{
		$table_al->class = "tactical_view";
		$table_al->style = array();
		$output = '<fieldset class="tactical_set">
					<legend>' . 
						__('Defined and fired alerts') . 
					'</legend>' . 
					html_print_table($table_al, true) . '</fieldset>';
	}
	return $output;
}


function reporting_get_stats_modules_status($data, $graph_width = 250, $graph_height = 150, $links = false, $data_agents=false) {
	global $config;
	
	// Link URLS
	if ($links === false) {
		$urls = array();
		$urls['monitor_critical'] = "index.php?" .
			"sec=estado&amp;sec2=operation/agentes/status_monitor&amp;" .
			"refr=60&amp;status=" . AGENT_MODULE_STATUS_CRITICAL_BAD . "&pure=" . $config['pure'];
		$urls['monitor_warning'] = "index.php?" .
			"sec=estado&amp;sec2=operation/agentes/status_monitor&amp;" .
			"refr=60&amp;status=" . AGENT_MODULE_STATUS_WARNING . "&pure=" . $config['pure'];
		$urls['monitor_ok'] = "index.php?" .
			"sec=estado&amp;sec2=operation/agentes/status_monitor&amp;" .
			"refr=60&amp;status=" . AGENT_MODULE_STATUS_NORMAL . "&pure=" . $config['pure'];
		$urls['monitor_unknown'] = "index.php?" .
			"sec=estado&amp;sec2=operation/agentes/status_monitor&amp;" .
			"refr=60&amp;status=" . AGENT_MODULE_STATUS_UNKNOWN . "&pure=" . $config['pure'];
		$urls['monitor_not_init'] = "index.php?" .
			"sec=estado&amp;sec2=operation/agentes/status_monitor&amp;" .
			"refr=60&amp;status=" . AGENT_MODULE_STATUS_NOT_INIT . "&pure=" . $config['pure'];
	}
	else {
		$urls = array();
		$urls['monitor_critical'] = $links['monitor_critical'];
		$urls['monitor_warning'] = $links['monitor_warning'];
		$urls['monitor_ok'] = $links['monitor_ok'];
		$urls['monitor_unknown'] = $links['monitor_unknown'];
		$urls['monitor_not_init'] = $links['monitor_not_init'];
	}
	
	// Modules by status table
	$table_mbs = html_get_predefined_table();
	
	$tdata = array();
	$tdata[0] = html_print_image('images/module_critical.png', true, array('title' => __('Monitor critical')));
	$tdata[1] = $data["monitor_critical"] <= 0 ? '-' : $data["monitor_critical"];
	$tdata[1] = '<a style="color: ' . COL_CRITICAL . ';" class="big_data" href="' . $urls['monitor_critical'] . '">' . $tdata[1] . '</a>';
	
	$tdata[2] = html_print_image('images/module_warning.png', true, array('title' => __('Monitor warning')));
	$tdata[3] = $data["monitor_warning"] <= 0 ? '-' : $data["monitor_warning"];
	$tdata[3] = '<a style="color: ' . COL_WARNING_DARK . ';" class="big_data" href="' . $urls['monitor_warning'] . '">' . $tdata[3] . '</a>';
	$table_mbs->rowclass[] = '';
	$table_mbs->data[] = $tdata;
	
	$tdata = array();
	$tdata[0] = html_print_image('images/module_ok.png', true, array('title' => __('Monitor normal')));
	$tdata[1] = $data["monitor_ok"] <= 0 ? '-' : $data["monitor_ok"];
	$tdata[1] = '<a style="color: ' . COL_NORMAL . ';" class="big_data" href="' . $urls["monitor_ok"] . '">' . $tdata[1] . '</a>';
	
	$tdata[2] = html_print_image('images/module_unknown.png', true, array('title' => __('Monitor unknown')));
	$tdata[3] = $data["monitor_unknown"] <= 0 ? '-' : $data["monitor_unknown"];
	$tdata[3] = '<a style="color: ' . COL_UNKNOWN . ';" class="big_data" href="' . $urls["monitor_unknown"] . '">' . $tdata[3] . '</a>';
	$table_mbs->rowclass[] = '';
	$table_mbs->data[] = $tdata;
	
	$tdata = array();
	$tdata[0] = html_print_image('images/module_notinit.png', true, array('title' => __('Monitor not init')));
	$tdata[1] = $data["monitor_not_init"] <= 0 ? '-' : $data["monitor_not_init"];
	$tdata[1] = '<a style="color: ' . COL_NOTINIT . ';" class="big_data" href="' . $urls["monitor_not_init"] . '">' . $tdata[1] . '</a>';
	
	$tdata[2] = $tdata[3] = '';
	$table_mbs->rowclass[] = '';
	$table_mbs->data[] = $tdata;

	if ($data["monitor_checks"] > 0) {
		$tdata = array();
		$table_mbs->colspan[count($table_mbs->data)][0] = 4;
		$table_mbs->cellstyle[count($table_mbs->data)][0] = 'text-align: center;';
		$tdata[0] = '<div id="outter_status_pie" style="height: ' . $graph_height . 'px">' .
			'<div id="status_pie" style="margin: auto; width: ' . $graph_width . 'px;">' .
				graph_agent_status(false, $graph_width, $graph_height, true, true, $data_agents) .
			'</div></div>';
		$table_mbs->rowclass[] = '';
		$table_mbs->data[] = $tdata;
	}
	
	if(!defined("METACONSOLE")){
		$output = '
			<fieldset class="databox tactical_set">
				<legend>' . 
					__('Monitors by status') . 
				'</legend>' . 
				html_print_table($table_mbs, true) .
			'</fieldset>';
	}
	else{
		$table_mbs->class = "tactical_view";
		$table_mbs->style=array();
		$output = '
			<fieldset class="tactical_set">
				<legend>' . 
					__('Monitors by status') . 
				'</legend>' . 
				html_print_table($table_mbs, true) .
			'</fieldset>';
	}
	return $output;
}

function reporting_get_stats_agents_monitors($data) {
	global $config;
	
	// Link URLS
	$mobile = false;
	if (isset($data['mobile'])) {
		if ($data['mobile']) {
			$mobile = true;
		}
	}
	
	if ($mobile) {
		$urls = array();
		$urls['total_agents'] = "index.php?page=agents";
		$urls['monitor_checks'] = "index.php?page=modules";
	}
	else {
		$urls = array();
		$urls['total_agents'] = "index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=60";
		$urls['monitor_checks'] = "index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=60&amp;status=-1";
	}
	
	// Agents and modules table
	$table_am = html_get_predefined_table();
	
	$tdata = array();
	$tdata[0] = html_print_image('images/agent.png', true, array('title' => __('Total agents')));
	$tdata[1] = $data["total_agents"] <= 0 ? '-' : $data["total_agents"];
	$tdata[1] = '<a class="big_data" href="' . $urls['total_agents'] . '">' . $tdata[1] . '</a>';
	
	$tdata[2] = html_print_image('images/module.png', true, array('title' => __('Monitor checks')));
	$tdata[3] = $data["monitor_checks"] <= 0 ? '-' : $data["monitor_checks"];
	$tdata[3] = '<a class="big_data" href="' . $urls['monitor_checks'] . '">' . $tdata[3] . '</a>';
	$table_am->rowclass[] = '';
	$table_am->data[] = $tdata;
	
	$output = '<fieldset class="databox tactical_set">
				<legend>' . 
					__('Total agents and monitors') . 
				'</legend>' . 
				html_print_table($table_am, true) . '</fieldset>';
	
	return $output;
}

function reporting_get_stats_users($data) {
	global $config;
	
	// Link URLS
	$urls = array();
	if (check_acl ($config['id_user'], 0, "UM")) {
		$urls['defined_users'] = "index.php?sec=gusuarios&amp;sec2=godmode/users/user_list";
	}
	else {
		$urls['defined_users'] = 'javascript:';
	}
	
	// Users table
	$table_us = html_get_predefined_table();
	
	$tdata = array();
	$tdata[0] = html_print_image('images/user_green.png', true, array('title' => __('Defined users')));
	$tdata[1] = count (get_users ());
	$tdata[1] = '<a class="big_data" href="' . $urls["defined_users"] . '">' . $tdata[1] . '</a>';
	
	$tdata[2] = $tdata[3] = '&nbsp;';
	$table_us->rowclass[] = '';
	$table_us->data[] = $tdata;
	
	$output = '<fieldset class="databox tactical_set">
				<legend>' . 
					__('Users') . 
				'</legend>' . 
				html_print_table($table_us, true) . '</fieldset>';
	
	return $output;
}

/** 
 * Get the average value of an agent module in a period of time.
 * 
 * @param int Agent module id
 * @param int Period of time to check (in seconds)
 * @param int Top date to check the values. Default current time.
 * 
 * @return float The average module value in the interval.
 */
function reporting_get_agentmodule_data_average ($id_agent_module, $period=0, $date = 0) {
	global $config;
	
	// Initialize variables
	if (empty ($date)) $date = get_system_time ();
	$datelimit = $date - $period;
	
	$search_in_history_db = db_search_in_history_db($datelimit);
	
	$id_module_type = modules_get_agentmodule_type ($id_agent_module);
	$module_type = modules_get_moduletype_name ($id_module_type);
	$uncompressed_module = is_module_uncompressed ($module_type);
	
	// Get module data
	$interval_data = db_get_all_rows_sql ('SELECT *
		FROM tagente_datos 
		WHERE id_agente_modulo = ' . (int) $id_agent_module .
			' AND utimestamp > ' . (int) $datelimit .
			' AND utimestamp < ' . (int) $date .
		' ORDER BY utimestamp ASC', $search_in_history_db);
	if ($interval_data === false) $interval_data = array ();
	
	// Uncompressed module data
	if ($uncompressed_module) {
		$min_necessary = 1;
	
	// Compressed module data
	}
	else {
		// Get previous data
		$previous_data = modules_get_previous_data ($id_agent_module, $datelimit);
		if ($previous_data !== false) {
			$previous_data['utimestamp'] = $datelimit;
			array_unshift ($interval_data, $previous_data);
		}
		
		// Get next data
		$next_data = modules_get_next_data ($id_agent_module, $date);
		if ($next_data !== false) {
			$next_data['utimestamp'] = $date;
			array_push ($interval_data, $next_data);
		}
		else if (count ($interval_data) > 0) {
			// Propagate the last known data to the end of the interval
			$next_data = array_pop ($interval_data);
			array_push ($interval_data, $next_data);
			$next_data['utimestamp'] = $date;
			array_push ($interval_data, $next_data);
		}
		
		$min_necessary = 2;
	}
	
	if (count ($interval_data) < $min_necessary) {
		return false;
	}
	
	// Set initial conditions
	$total = 0;
	$count = 0;
	if (! $uncompressed_module) {
		$previous_data = array_shift ($interval_data);
		
		// Do not count the empty start of an interval as 0
		if ($previous_data['utimestamp'] != $datelimit) {
			$period = $date - $previous_data['utimestamp'];
		}
	}
	foreach ($interval_data as $data) {
		if (! $uncompressed_module) {
			$total += $previous_data['datos'] * ($data['utimestamp'] - $previous_data['utimestamp']);
			$previous_data = $data;
		}
		else {
			$total += $data['datos'];
			$count++;
		}
	}
	
	// Compressed module data
	if (! $uncompressed_module) {
		if ($period == 0) {
			return 0;
		}
		
		return $total / $period;
	}
	
	// Uncompressed module data
	if ($count == 0) {
		return 0;
	}
	
	return $total / $count;	
}
?>