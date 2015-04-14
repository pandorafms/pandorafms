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
include_once($config['homedir'] . "/include/functions_os.php");

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
	
	$report["group"] = $report['id_group'];
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
			case 'availability':
				$report['contents'][] =
					reporting_availability(
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
			case 'MTTR':
				$report['contents'][] = reporting_value(
					$report,
					$content,
					'MTTR');
				break;
			case 'MTBF':
				$report['contents'][] = reporting_value(
					$report,
					$content,
					'MTBF');
				break;
			case 'TTO':
				$report['contents'][] = reporting_value(
					$report,
					$content,
					'TTO');
				break;
			case 'TTRT':
				$report['contents'][] = reporting_value(
					$report,
					$content,
					'TTRT');
				break;
			case 'agent_configuration':
				$report['contents'][] = reporting_agent_configuration(
					$report,
					$content);
				break;
			case 'projection_graph':
				$report['contents'][] = reporting_projection_graph(
					$report,
					$content,
					$type,
					$force_width_chart,
					$force_height_chart);
				break;
			case 'prediction_date':
				$report['contents'][] = reporting_prediction_date(
					$report,
					$content);
				break;
			case 'simple_baseline_graph':
				$report['contents'][] = reporting_simple_baseline_graph(
					$report,
					$content,
					$type,
					$force_width_chart,
					$force_height_chart);
				break;
			case 'netflow_area':
				$report['contents'][] = reporting_simple_baseline_graph(
					$report,
					$content,
					$type,
					$force_width_chart,
					$force_height_chart);
				break;
			case 'netflow_pie':
				$report['contents'][] = reporting_netflow_pie(
					$report,
					$content,
					$type,
					$force_width_chart,
					$force_height_chart);
				break;
			case 'netflow_data':
				$report['contents'][] = reporting_netflow_data(
					$report,
					$content,
					$type,
					$force_width_chart,
					$force_height_chart);
				break;
			case 'netflow_statistics':
				$report['contents'][] = reporting_netflow_statistics(
					$report,
					$content,
					$type,
					$force_width_chart,
					$force_height_chart);
				break;
			case 'netflow_summary':
				$report['contents'][] = reporting_netflow_summary(
					$report,
					$content,
					$type,
					$force_width_chart,
					$force_height_chart);
				break;
			case 'monitor_report':
				$report['contents'][] = reporting_monitor_report(
					$report,
					$content);
				break;
			case 'sql_graph_vbar':
				$report['contents'][] = reporting_sql_graph(
					$report,
					$content,
					$type,
					$force_width_chart,
					$force_height_chart,
					'sql_graph_vbar');
				break;
			case 'sql_graph_hbar':
				$report['contents'][] = reporting_sql_graph(
					$report,
					$content,
					$type,
					$force_width_chart,
					$force_height_chart,
					'sql_graph_hbar');
				break;
			case 'sql_graph_pie':
				$report['contents'][] = reporting_sql_graph(
					$report,
					$content,
					$type,
					$force_width_chart,
					$force_height_chart,
					'sql_graph_pie');
				break;
			case 'alert_report_module':
				$report['contents'][] = reporting_alert_report_module(
					$report,
					$content);
				break;
			case 'alert_report_agent':
				$report['contents'][] = reporting_alert_report_agent(
					$report,
					$content);
				break;
		}
	}
	
	return reporting_check_structure_report($report);
}

function reporting_alert_report_agent($report, $content) {
	
	global $config;
	
	$return['type'] = 'alert_report_agent';
	
	if (empty($content['name'])) {
		$content['name'] = __('Alert Report Agent');
	}
	
	$agent_name = agents_get_name($content['id_agent']);
	
	$return['title'] = $content['name'];
	$return['subtitle'] = $agent_name;
	$return["description"] = $content["description"];
	$return["date"] = reporting_get_date_text($report, $content);
	
	$alerts = agents_get_alerts($content['id_agent']);
	
	if (isset($alerts['simple'])) {
		$alerts = $alerts['simple'];
	}
	else {
		$alerts = array();
	}
	
	$data = array();
	
	foreach ($alerts as $alert) {
		$data_row = array();
		
		$data_row['disabled'] = $alert['disabled'];
		
		$data_row['module'] = db_get_value_filter('nombre', 'tagente_modulo',
			array('id_agente_modulo' => $alert['id_agent_module']));
		$data_row['template'] = db_get_value_filter('name', 'talert_templates',
			array('id' => $alert['id_alert_template']));
		
		
		$actions = db_get_all_rows_sql('SELECT name 
			FROM talert_actions 
			WHERE id IN (SELECT id_alert_action 
				FROM talert_template_module_actions 
				WHERE id_alert_template_module = ' . $alert['id_alert_template'] . ');');
		
		if (!empty($actions)) {
			$row = db_get_row_sql('SELECT id_alert_action
				FROM talert_templates
				WHERE id IN (SELECT id_alert_template
					FROM talert_template_modules
					WHERE id = ' . $alert['id_alert_template'] . ')');
			
			$id_action = 0;
			if (!empty($row))
				$id_action = $row['id_alert_action'];
			
			// Prevent from void action
			if (empty($id_action))
				$id_action = 0;
			
			$actions = db_get_all_rows_sql('SELECT name 
				FROM talert_actions 
				WHERE id = ' . $id_action);
			
			if (empty($actions)) {
				$actions = array();
			}
		}
		
		$data_row['action'] = array();
		foreach ($actions as $action) {
			$data_row['action'][] = $action['name'];
		}
		
		$data_row['fired'] = array();
		$firedTimes = get_module_alert_fired(
			$content['id_agent_module'],
			$alert['id_alert_template'],
			(int) $content['period'],
			(int) $report["datetime"]);
		if (empty($firedTimes)) {
			$firedTimes = array();
		}
		foreach ($firedTimes as $fireTime) {
			$data_row['fired'][] = $fireTime['timestamp'];
		}
		
		$data[] = $data_row;
	}
	
	$return['data'] = $data;
	
	return reporting_check_structure_content($return);
}

function reporting_alert_report_module($report, $content) {
	
	global $config;
	
	$return['type'] = 'alert_report_module';
	
	if (empty($content['name'])) {
		$content['name'] = __('Alert Report Module');
	}
	
	$module_name = io_safe_output(
		modules_get_agentmodule_name($content['id_agent_module']));
	$agent_name = io_safe_output(
		modules_get_agentmodule_agent_name ($content['id_agent_module']));
	
	$return['title'] = $content['name'];
	$return['subtitle'] = $agent_name . " - " . $module_name;
	$return["description"] = $content["description"];
	$return["date"] = reporting_get_date_text($report, $content);
	
	$alerts = db_get_all_rows_sql('SELECT *, t1.id as id_alert_template_module
		FROM talert_template_modules AS t1
			INNER JOIN talert_templates AS t2 ON t1.id_alert_template = t2.id
		WHERE id_agent_module = ' . $content['id_agent_module']);
	
	if ($alerts === false) {
		$alerts = array();
	}
	
	$data = array();
	foreach ($alerts as $alert) {
		$data_row = array();
		
		$data_row['disabled'] = $alert['disabled'];
		
		$data_row['template'] = db_get_value_filter('name',
			'talert_templates', array('id' => $alert['id_alert_template']));
		$actions = db_get_all_rows_sql('SELECT name 
			FROM talert_actions 
			WHERE id IN (SELECT id_alert_action 
				FROM talert_template_module_actions 
				WHERE id_alert_template_module = ' . $alert['id_alert_template_module'] . ');');
		
		if (!empty($actions)) {
			$row = db_get_row_sql('SELECT id_alert_action
				FROM talert_templates
				WHERE id IN (SELECT id_alert_template
					FROM talert_template_modules
					WHERE id = ' . $alert['id_alert_template_module'] . ')');
			
			$id_action = 0;
			if (!empty($row))
				$id_action = $row['id_alert_action'];
			
			// Prevent from void action
			if (empty($id_action))
				$id_action = 0;
			
			$actions = db_get_all_rows_sql('SELECT name 
				FROM talert_actions 
				WHERE id = ' . $id_action);
			
			if (empty($actions)) {
				$actions = array();
			}
		}
		
		$data_row['action'] = array();
		foreach ($actions as $action) {
			$data_row['action'][] = $action['name'];
		}
		
		$data_row['fired'] = array();
		$firedTimes = get_module_alert_fired(
			$content['id_agent_module'],
			$alert['id_alert_template_module'],
			(int) $content['period'],
			(int) $report["datetime"]);
		if (empty($firedTimes)) {
			$firedTimes = array();
		}
		foreach ($firedTimes as $fireTime) {
			$data_row['fired'][] = $fireTime['timestamp'];
		}
		
		$data[] = $data_row;
	}
	
	$return['data'] = $data;
	
	return reporting_check_structure_content($return);
}

function reporting_sql_graph($report, $content, $type,
	$force_width_chart, $force_height_chart, $type_sql_graph) {
	
	global $config;
	
	switch ($type_sql_graph) {
		case 'netflow_area':
			$return['type'] = 'sql_graph_vbar';
			break;
		case 'sql_graph_hbar':
			$return['type'] = 'sql_graph_hbar';
			break;
		case 'sql_graph_pie':
			$return['type'] = 'sql_graph_pie';
			break;
	}
	
	if (empty($content['name'])) {
		switch ($type_sql_graph) {
			case 'sql_graph_vbar':
				$return['name'] = __('SQL Graph Vertical Bars');
				break;
			case 'sql_graph_hbar':
				$return['name'] = __('SQL Graph Horizontal Bars');
				break;
			case 'sql_graph_pie':
				$return['name'] = __('SQL Graph Pie');
				break;
		}
	}
	
	// Get chart
	reporting_set_conf_charts($width, $height, $only_image, $type, $content);
	
	if (!empty($force_width_chart)) {
		$width = $force_width_chart;
	}
	
	if (!empty($force_height_chart)) {
		$height = $force_height_chart;
	}
	
	$return['title'] = $content['name'];
	$return["description"] = $content["description"];
	$return["date"] = reporting_get_date_text();
	
	switch ($type) {
		case 'dinamic':
		case 'static':
			$return['chart'] = graph_custom_sql_graph(
				$content["id_rc"],
				$width,
				$height,
				$content["type"],
				true,
				ui_get_full_url(false, false, false, false));
			break;
		case 'data':
			break;
	}
	
	return reporting_check_structure_content($return);
}

function reporting_monitor_report($report, $content) {
	global $config;
	
	
	$return['type'] = 'monitor_report';
	
	if (empty($content['name'])) {
		$content['name'] = __('Monitor Report');
	}
	
	$return['title'] = $content['name'];
	$return["description"] = $content["description"];
	$return["date"] = reporting_get_date_text($report, $content);
	
	$value = reporting_get_agentmodule_sla(
		$content['id_agent_module'],
		$content['period'],
		1,
		false,
		$report["datetime"]);
	
	if ($value === __('Unknown')) {
		$return['data']['unknown'] = 1;
	}
	else {
		$return['data']['unknown'] = 0;
		
		$return["data"]["ok"]["value"] = $value;
		$return["data"]["ok"]["formated_value"] = format_numeric($value, 2);
		
		$return["data"]["fail"]["value"] = 100 - $return["data"]["ok"]["value"];
		$return["data"]["fail"]["formated_value"] = (100 - $return["data"]["ok"]["formated_value"]);
	}
	
	return reporting_check_structure_content($return);
}

function reporting_netflow($report, $content, $type,
	$force_width_chart, $force_height_chart, $type_netflow = null) {
	
	global $config;
	
	switch ($type_netflow) {
		case 'netflow_area':
			$return['type'] = 'netflow_area';
			break;
		case 'netflow_pie':
			$return['type'] = 'netflow_pie';
			break;
		case 'netflow_data':
			$return['type'] = 'netflow_data';
			break;
		case 'netflow_statistics':
			$return['type'] = 'netflow_statistics';
			break;
		case 'netflow_summary':
			$return['type'] = 'netflow_summary';
			break;
	}
	
	if (empty($content['name'])) {
		switch ($type_netflow) {
			case 'netflow_area':
				$return['name'] = __('Netflow Area');
				break;
			case 'netflow_pie':
				$return['name'] = __('Netflow Pie');
				break;
			case 'netflow_data':
				$return['name'] = __('Netflow Data');
				break;
			case 'netflow_statistics':
				$return['name'] = __('Netflow Statistics');
				break;
			case 'netflow_summary':
				$return['name'] = __('Netflow Summary');
				break;
		}
	}
	
	$return['title'] = $content['name'];
	$return["description"] = $content["description"];
	$return["date"] = reporting_get_date_text($report, $content);
	
	// Get chart
	reporting_set_conf_charts($width, $height, $only_image, $type, $content);
	
	if (!empty($force_width_chart)) {
		$width = $force_width_chart;
	}
	
	if (!empty($force_height_chart)) {
		$height = $force_height_chart;
	}
	
	// Get item filters
	$filter = db_get_row_sql("SELECT *
		FROM tnetflow_filter
		WHERE id_sg = '" . (int)$content['text'] . "'", false, true);
	
	switch ($type) {
		case 'dinamic':
		case 'static':
			$return['chart'] = netflow_draw_item (
				$report['datetime'] - $content['period'],
				$report['datetime'],
				$content['top_n'],
				$type_netflow,
				$filter,
				$content['top_n_value'],
				$content ['server_name'],
				'HTML');
			break;
		case 'data':
			break;
	}
	
	return reporting_check_structure_content($return);
}

function reporting_simple_baseline_graph($report, $content,
	$type = 'dinamic', $force_width_chart = null,
	$force_height_chart = null) {
	
	global $config;
	
	$return['type'] = 'simple_baseline_graph';
	
	if (empty($content['name'])) {
		$content['name'] = __('Simple baseline graph');
	}
	
	$return['title'] = $content['name'];
	$return["description"] = $content["description"];
	$return["date"] = reporting_get_date_text($report, $content);
	
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
			$return['chart'] = grafico_modulo_sparse(
				$content['id_agent_module'],
				$content['period'],
				false,
				$width,
				$height,
				'',
				'',
				false,
				true,
				true,
				$report["datetime"],
				'',
				true,
				0,
				true,
				false,
				ui_get_full_url(false, false, false, false));
			break;
		case 'data':
			break;
	}
	
	return reporting_check_structure_content($return);
}

function reporting_prediction_date($report, $content) {
	
	global $config;
	
	$return['type'] = 'prediction_date';
	
	if (empty($content['name'])) {
		$content['name'] = __('Prediction Date');
	}
	
	$return['title'] = $content['name'];
	$return["description"] = $content["description"];
	$return["date"] = reporting_get_date_text($report, $content);
	
	set_time_limit(500);
	
	$intervals_text = $content['text'];
	$max_interval = substr($intervals_text, 0, strpos($intervals_text, ';'));
	$min_interval = substr($intervals_text, strpos($intervals_text, ';') + 1);			
	$value = forecast_prediction_date ($content['id_agent_module'], $content['period'],  $max_interval, $min_interval);
	
	if ($value === false) {
		$return["data"]['value'] = __('Unknown');
	}
	else {
		$return["data"]['value'] = date ('d M Y H:i:s', $value);
	}
	
	return reporting_check_structure_content($return);
}

function reporting_projection_graph($report, $content,
	$type = 'dinamic', $force_width_chart = null,
	$force_height_chart = null) {
	
	global $config;
	
	$return['type'] = 'projection_graph';
	
	if (empty($content['name'])) {
		$content['name'] = __('Projection Graph');
	}
	
	$return['title'] = $content['name'];
	$return["description"] = $content["description"];
	$return["date"] = reporting_get_date_text($report, $content);
	
	set_time_limit(500);
	
	$output_projection = forecast_projection_graph(
		$content['id_agent_module'], $content['period'], $content['top_n_value']);
	
	// If projection doesn't have data then don't draw graph
	if ($output_projection ==  NULL) {
		$output_projection = false;
	}
	
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
			$return['chart'] = graphic_combined_module(
				array($content['id_agent_module']),
				array(),
				$content['period'],
				$width,
				$height,
				'Projection%20Sample%20Graph',
				'',
				0,
				0,
				0,
				0,
				$report["datetime"],
				true,
				ui_get_full_url(false, false, false, false) . '/',
				1,
				// Important parameter, this tell to graphic_combined_module function that is a projection graph
				$output_projection,
				$content['top_n_value']
				);
			break;
		case 'data':
			break;
	}
	
	return reporting_check_structure_content($return);
}

function reporting_agent_configuration($report, $content) {
	global $config;
	
	$return['type'] = 'agent_configuration';
	
	if (empty($content['name'])) {
		$content['name'] = __('Agent configuration');
	}
	
	$return['title'] = $content['name'];
	$return["description"] = $content["description"];
	$return["date"] = reporting_get_date_text($report, $content);
	
	
	$sql = "
		SELECT *
		FROM tagente
		WHERE id_agente=" . $content['id_agent'];
	$agent_data = db_get_row_sql($sql);
	
	$agent_configuration = array();
	$agent_configuration['name'] = $agent_data['nombre'];
	$agent_configuration['group'] = groups_get_name($agent_data['id_grupo']);
	$agent_configuration['group_icon'] =
		ui_print_group_icon ($agent_data['id_grupo'], true, '', '', false);
	$agent_configuration['os'] = os_get_name($agent_data["id_os"]);
	$agent_configuration['os_icon'] = ui_print_os_icon($agent_data["id_os"], true, true);
	$agent_configuration['address'] = $agent_data['direccion'];
	$agent_configuration['description'] =
		strip_tags(ui_bbcode_to_html($agent_data['comentarios']));
	$agent_configuration['enabled'] = (int)!$agent_data['disabled'];
	$agent_configuration['group'] = $report["group"];
	
	$modules = agents_get_modules ($content['id_agent']);
	
	$agent_configuration['modules'] = array();
	//Agent's modules
	if (!empty($modules)) {
		foreach ($modules as $id_agent_module => $module) {
			$sql = "
				SELECT *
				FROM tagente_modulo
				WHERE id_agente_modulo = $id_agent_module";
			$module_db = db_get_row_sql($sql);
			
			
			$data_module = array();
			$data_module['name'] = $module_db['nombre'];
			if ($module_db['disabled']) {
				$data_module['name'] .= " (" . __('Disabled') . ")";
			}
			$data_module['type_icon'] =
				ui_print_moduletype_icon($module_db['id_tipo_modulo'], true);
			$data_module['type'] =
				modules_get_type_name($module_db['id_tipo_modulo']);
			$data_module['max_warning'] =
				$module_db['max_warning'];
			$data_module['min_warning'] =
				$module_db['min_warning'];
			$data_module['max_critical'] =
				$module_db['max_critical'];
			$data_module['min_critical'] =
				$module_db['min_critical'];
			$data_module['threshold'] = $module_db['module_ff_interval'];
			$data_module['description'] = $module_db['descripcion'];
			if (($module_db['module_interval'] == 0) ||
				($module_db['module_interval'] == '')) {
				
				$data_module['interval'] = db_get_value('intervalo',
					'tagente', 'id_agente', $content['id_agent']);
			}
			else {
				$data_module['interval'] = $module_db['module_interval'];
			}
			$data_module['unit'] = $module_db['unit'];
			$module_status = db_get_row(
				'tagente_estado', 'id_agente_modulo', $id_agent_module);
			modules_get_status($id_agent_module,
				$module_status['estado'],
				$module_status['datos'], $status, $title);
			$data_module['status_icon'] = 
				ui_print_status_image($status, $title, true);
			$data_module['status'] = $title;
			$sql_tag = "
				SELECT name
				FROM ttag
				WHERE id_tag IN (
					SELECT id_tag
					FROM ttag_module
					WHERE id_agente_modulo = $id_agent_module)";
			$tags = db_get_all_rows_sql($sql_tag);
			if ($tags === false)
				$data_module['tags'] = array();
			else
				$data_module['tags'] = $tags;
			
			$agent_configuration['modules'][] = $data_module;
		}
	}
	
	$return['data'] = $agent_configuration;
	
	return reporting_check_structure_content($return);
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
		case 'MTTR':
			$return['type'] = 'MTTR';
			break;
		case 'MTBF':
			$return['type'] = 'MTBF';
			break;
		case 'TTO':
			$return['type'] = 'TTO';
			break;
		case 'TTRT':
			$return['type'] = 'TTRT';
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
			case 'MTTR':
				$content['name'] = __('MTTR');
				break;
			case 'MTBF':
				$content['name'] = __('MTBF');
				break;
			case 'TTO':
				$content['name'] = __('TTO');
				break;
			case 'TTRT':
				$return['type'] = __('TTRT');
				break;
		}
	}
	
	$module_name = io_safe_output(
		modules_get_agentmodule_name($content['id_agent_module']));
	$agent_name = io_safe_output(
		modules_get_agentmodule_agent_name ($content['id_agent_module']));
	$unit = db_get_value('unit', 'tagente_modulo', 'id_agente_modulo',
		$content ['id_agent_module']);
	
	$return['title'] = $content['name'];
	$return['subtitle'] = $agent_name . " - " . $module_name;
	$return["description"] = $content["description"];
	$return["date"] = reporting_get_date_text($report, $content);
	
	switch ($type) {
		case 'max':
			$value = reporting_get_agentmodule_data_max(
				$content['id_agent_module'], $content['period'], $report["datetime"]);
			$formated_value = format_for_graph($value, 2) . " " . $unit;
			break;
		case 'min':
			$value = reporting_get_agentmodule_data_min(
				$content['id_agent_module'], $content['period'], $report["datetime"]);
			$formated_value = format_for_graph($value, 2) . " " . $unit;
			break;
		case 'avg':
			$value = reporting_get_agentmodule_data_average(
				$content['id_agent_module'], $content['period'], $report["datetime"]);
			$formated_value = format_for_graph($value, 2) . " " . $unit;
			break;
		case 'sum':
			$value = reporting_get_agentmodule_data_sum(
				$content['id_agent_module'], $content['period'], $report["datetime"]);
			$formated_value = format_for_graph($value, 2) . " " . $unit;
			break;
		case 'MTTR':
			$value = reporting_get_agentmodule_mttr(
				$content['id_agent_module'], $content['period'], $report["datetime"]);
			$formated_value = null;
			break;
		case 'MTBF':
			$value = reporting_get_agentmodule_mtbf(
				$content['id_agent_module'], $content['period'], $report["datetime"]);
			$formated_value = null;
			break;
		case 'TTO':
			$value = reporting_get_agentmodule_tto(
				$content['id_agent_module'], $content['period'], $report["datetime"]);
			if ($value == 0) {
				$formated_value = null;
			}
			else {
				$formated_value = human_time_description_raw ($value);
			}
			break;
		case 'TTRT':
			$value = reporting_get_agentmodule_ttr(
				$content['id_agent_module'], $content['period'], $report["datetime"]);
			if ($value == 0) {
				$formated_value = null;
			}
			else {
				$formated_value = human_time_description_raw ($value);
			}
			break;
	}
	
	$return['data'] = array(
		'value' => $value,
		'formated_value' => $formated_value);
	
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

function reporting_availability($report, $content) {
	
	global $config;
	
	$return = array();
	$return['type'] = 'availability';
	$return['subtype'] = $content['group_by_agent'];
	$return['resume'] = $content['show_resume'];
	
	if (empty($content['name'])) {
		$content['name'] = __('Availability');
	}
	
	$return['title'] = $content['name'];
	$return["description"] = $content["description"];
	$return["date"] = reporting_get_date_text(
		$report,
		$content);
	
	if ($content['show_graph']) {
		$return['kind_availability'] = "address";
	}
	else {
		$return['kind_availability'] = "module";
	}
	
	
	$sql = sprintf("
		SELECT id_agent_module,
			server_name, operation
		FROM treport_content_item
		WHERE id_report_content = %d",
		$content['id_rc']);
	
	$items = db_process_sql ($sql);
	
	
	$data = array();
	
	$avg = 0;
	$min = null;
	$min_text = "";
	$max = null;
	$max_text = "";
	$count = 0;
	foreach ($items as $item) {
		//aaMetaconsole connection
		$server_name = $item ['server_name'];
		if (($config ['metaconsole'] == 1) && $server_name != '' && defined('METACONSOLE')) {
			$connection = metaconsole_get_connection($server_name);
			if (metaconsole_load_external_db($connection) != NOERR) {
				//ui_print_error_message ("Error connecting to ".$server_name);
				continue;
			}
		}
		
		if (modules_is_disable_agent($item['id_agent_module'])) {
			continue;
		}
		
		$row = array();
		
		$text = "";
		
		// HACK it is saved in show_graph field.
		// Show interfaces instead the modules
		if ($content['show_graph']) {
			$text = $row['availability_item'] = agents_get_address(
				modules_get_agentmodule_agent($item['id_agent_module']));
			
			if (empty($text)) {
				$text = $row['availability_item'] = __('No Address');
			}
		}
		else {
			$text = $row['availability_item'] = modules_get_agentmodule_name(
				$item['id_agent_module']);
		}
		$row['agent'] = modules_get_agentmodule_agent_name(
			$item['id_agent_module']);
		
		$text = $row['agent'] . " (" . $text . ")";
		
		$count_checks = modules_get_count_datas(
			$item['id_agent_module'],
			$report["datetime"] - $content['period'],
			$report["datetime"]);
		
		
		if (empty($count_checks)) {
			$row['checks'] = __('Unknown');
			$row['failed'] = __('Unknown');
			$row['fail'] = __('Unknown');
			$row['poling_time'] = __('Unknown');
			$row['time_unavaliable'] = __('Unknown');
			$row['ok'] = __('Unknown');
			
			$percent_ok = 0;
		}
		else {
			$count_fails = count(
				modules_get_data_with_value(
					$item['id_agent_module'],
					$report["datetime"] - $content['period'],
					$report["datetime"],
					0, true));
			$percent_ok = (($count_checks - $count_fails) * 100) / $count_checks;
			$percent_fail = 100 - $percent_ok;
			
			$row['ok'] = format_numeric($percent_ok, 2) . " %";
			$row['fail'] = format_numeric($percent_fail, 2) . " %";
			$row['checks'] = format_numeric($count_checks, 2);
			$row['failed'] = format_numeric($count_fails ,2);
			$row['poling_time'] = human_time_description_raw(
				($count_checks - $count_fails) * modules_get_interval($item['id_agent_module']),
				true);
			$row['time_unavaliable'] = "-";
			if ($count_fails > 0) {
				$row['time_unavaliable'] = human_time_description_raw(
					$count_fails * modules_get_interval($item['id_agent_module']),
					true);
			}
		}
		
		$data[] = $row;
		
		
		$avg = (($avg * $count) + $percent_ok) / ($count + 1);
		if (is_null($min)) {
			$min = $percent_ok;
			$min_text = $text;
		}
		else {
			if ($min > $percent_ok) {
				$min = $percent_ok;
				$min_text = $text;
			}
		}
		if (is_null($max)) {
			$max = $percent_ok;
			$max_text = $text;
		}
		else {
			if ($max < $percent_ok) {
				$max = $percent_ok;
				$max_text = $text;
			}
		}
		
		//Restore dbconnection
		if (($config ['metaconsole'] == 1) && $server_name != '' && defined('METACONSOLE')) {
			metaconsole_restore_db();
		}
		
		$count++;
	}
	
	
	switch ($content['order_uptodown']) {
		case REPORT_ITEM_ORDER_BY_AGENT_NAME:
			$temp = array();
			foreach ($data as $row) {
				$i = 0;
				foreach ($temp as $t_row) {
					if (strcmp($row['agent'], $t_row['agent']) < 0) {
						break;
					}
					
					$i++;
				}
				
				array_splice($temp, $i, 0, array($row));
			}
			
			$data = $temp;
			break;
		case REPORT_ITEM_ORDER_BY_ASCENDING:
			$temp = array();
			foreach ($data as $row) {
				$i = 0;
				foreach ($temp as $t_row) {
					if (strcmp($row['availability_item'], $t_row['availability_item']) < 0) {
						break;
					}
					
					$i++;
				}
				
				array_splice($temp, $i, 0, array($row));
			}
			
			$data = $temp;
			break;
		case REPORT_ITEM_ORDER_BY_DESCENDING:
			$temp = array();
			foreach ($data as $row) {
				$i = 0;
				foreach ($temp as $t_row) {
					
					if (strcmp($row['availability_item'], $t_row['availability_item']) > 0) {
						break;
					}
					
					$i++;
				}
				
				array_splice($temp, $i, 0, array($row));
			}
			
			$data = $temp;
			break;
	}
	
	
	$return["data"] = $data;
	$return["resume"] = array();
	$return["resume"]['min_text'] = $min_text;
	$return["resume"]['min'] = $min;
	$return["resume"]['avg'] = $avg;
	$return["resume"]['max_text'] = $max_text;
	$return["resume"]['max'] = $max;
	
	
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
				// HACK it is saved in show_graph field.
				$time_compare_overlapped = false;
				if ($content['show_graph']) {
					$time_compare_overlapped = 'overlapped';
				}
				
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
					$time_compare_overlapped,
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


/** 
 * Get the MTTR value of an agent module in a period of time. See
 * http://en.wikipedia.org/wiki/Mean_time_to_recovery
 * 
 * @param int Agent module id
 * @param int Period of time to check (in seconds)
 * @param int Top date to check the values. Default current time.
 * 
 * @return float The MTTR value in the interval.
 */
function reporting_get_agentmodule_mttr ($id_agent_module, $period = 0, $date = 0) {
	
	// Initialize variables
	if (empty ($date)) $date = get_system_time ();
	
	// Read module configuration
	$datelimit = $date - $period;
	$search_in_history_db = db_search_in_history_db($datelimit);

	$module = db_get_row_sql ('SELECT max_critical, min_critical, id_tipo_modulo
		FROM tagente_modulo
		WHERE id_agente_modulo = ' . (int) $id_agent_module);
	if ($module === false) {
		return false;
	}
	
	$critical_min = $module['min_critical'];
	$critical_max = $module['max_critical'];
	$module_type = $module['id_tipo_modulo'];
	
	// Set critical_min and critical for proc modules
	$module_type_str = modules_get_type_name ($module_type);
	if (strstr ($module_type_str, 'proc') !== false &&
		($critical_min == 0 && $critical_max == 0)) {
		$critical_min = 1;
	}
	
	// Get module data
	$interval_data = db_get_all_rows_sql ('SELECT * FROM tagente_datos 
		WHERE id_agente_modulo = ' . (int) $id_agent_module .
		' AND utimestamp > ' . (int) $datelimit .
		' AND utimestamp < ' . (int) $date .
		' ORDER BY utimestamp ASC', $search_in_history_db);
	if ($interval_data === false) $interval_data = array ();
	
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
	
	if (count ($interval_data) < 2) {
		return false;
	}
	
	// Set initial conditions
	$critical_period = 0;
	$first_data = array_shift ($interval_data);
	$previous_utimestamp = $first_data['utimestamp'];
	if ((($critical_max > $critical_min AND ($first_data['datos'] > $critical_max OR $first_data['datos'] < $critical_min))) OR
		($critical_max <= $critical_min AND $first_data['datos'] < $critical_min)) {
		$previous_status = 1;
		$critical_count = 1;
	}
	else {
		$previous_status = 0;
		$critical_count = 0;
	}
	
	foreach ($interval_data as $data) {
		// Previous status was critical
		if ($previous_status == 1) {
			$critical_period += $data['utimestamp'] - $previous_utimestamp;
		}
		
		// Re-calculate previous status for the next data
		if ((($critical_max > $critical_min AND ($data['datos'] > $critical_max OR $data['datos'] < $critical_min))) OR
			($critical_max <= $critical_min AND $data['datos'] < $critical_min)) {
			if ($previous_status == 0) {
				$critical_count++;
			}
			$previous_status = 1;
		}
		else {
			$previous_status = 0;
		}
		
		$previous_utimestamp = $data['utimestamp'];
	}
	
	if ($critical_count == 0) {
		return 0;
	}
	
	return $critical_period / $critical_count;
}


/** 
 * Get the MTBF value of an agent module in a period of time. See
 * http://en.wikipedia.org/wiki/Mean_time_between_failures
 * 
 * @param int Agent module id
 * @param int Period of time to check (in seconds)
 * @param int Top date to check the values. Default current time.
 * 
 * @return float The MTBF value in the interval.
 */
function reporting_get_agentmodule_mtbf ($id_agent_module, $period = 0, $date = 0) {
	
	// Initialize variables
	if (empty ($date)) $date = get_system_time ();
	
	// Read module configuration
	$datelimit = $date - $period;
	$search_in_history_db = db_search_in_history_db($datelimit);

	$module = db_get_row_sql ('SELECT max_critical, min_critical, id_tipo_modulo
		FROM tagente_modulo
		WHERE id_agente_modulo = ' . (int) $id_agent_module);
	if ($module === false) {
		return false;
	}
	
	$critical_min = $module['min_critical'];
	$critical_max = $module['max_critical'];
	$module_type = $module['id_tipo_modulo'];
	
	// Set critical_min and critical for proc modules
	$module_type_str = modules_get_type_name ($module_type);
	if (strstr ($module_type_str, 'proc') !== false &&
		($critical_min == 0 && $critical_max == 0)) {
		$critical_min = 1;
	}
	
	// Get module data
	$interval_data = db_get_all_rows_sql ('SELECT * FROM tagente_datos 
		WHERE id_agente_modulo = ' . (int) $id_agent_module .
		' AND utimestamp > ' . (int) $datelimit .
		' AND utimestamp < ' . (int) $date .
		' ORDER BY utimestamp ASC', $search_in_history_db);
	if ($interval_data === false) $interval_data = array ();
	
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
	
	if (count ($interval_data) < 2) {
		return false;
	}
	
	// Set initial conditions
	$critical_period = 0;
	$first_data = array_shift ($interval_data);
	$previous_utimestamp = $first_data['utimestamp'];
	if ((($critical_max > $critical_min AND ($first_data['datos'] > $critical_max OR $first_data['datos'] < $critical_min))) OR
			($critical_max <= $critical_min AND $first_data['datos'] < $critical_min)) {
		$previous_status = 1;
		$critical_count = 1;
	}
	else {
		$previous_status = 0;
		$critical_count = 0;
	}
	
	foreach ($interval_data as $data) {
		// Previous status was critical
		if ($previous_status == 1) {
			$critical_period += $data['utimestamp'] - $previous_utimestamp;
		}
		
		// Re-calculate previous status for the next data
		if ((($critical_max > $critical_min AND ($data['datos'] > $critical_max OR $data['datos'] < $critical_min))) OR
			($critical_max <= $critical_min AND $data['datos'] < $critical_min)) {
			if ($previous_status == 0) {
				$critical_count++;
			}
			$previous_status = 1;
		}
		else {
			$previous_status = 0;
		}
		
		$previous_utimestamp = $data['utimestamp'];
	}
	
	if ($critical_count == 0) {
		return 0;
	}
	
	return ($period - $critical_period) / $critical_count;
}


/** 
 * Get the TTO value of an agent module in a period of time.
 * 
 * @param int Agent module id
 * @param int Period of time to check (in seconds)
 * @param int Top date to check the values. Default current time.
 * 
 * @return float The TTO value in the interval.
 */
function reporting_get_agentmodule_tto ($id_agent_module, $period = 0, $date = 0) {
	
	// Initialize variables
	if (empty ($date)) $date = get_system_time ();
	
	// Read module configuration
	$datelimit = $date - $period;
	$search_in_history_db = db_search_in_history_db($datelimit);

	$module = db_get_row_sql ('SELECT max_critical, min_critical, id_tipo_modulo
		FROM tagente_modulo
		WHERE id_agente_modulo = ' . (int) $id_agent_module);
	if ($module === false) {
		return false;
	}
	
	$critical_min = $module['min_critical'];
	$critical_max = $module['max_critical'];
	$module_type = $module['id_tipo_modulo'];
	
	// Set critical_min and critical for proc modules
	$module_type_str = modules_get_type_name ($module_type);
	if (strstr ($module_type_str, 'proc') !== false &&
		($critical_min == 0 && $critical_max == 0)) {
		$critical_min = 1;
	}
	
	// Get module data
	$interval_data = db_get_all_rows_sql ('SELECT * FROM tagente_datos 
		WHERE id_agente_modulo = ' . (int) $id_agent_module .
		' AND utimestamp > ' . (int) $datelimit .
		' AND utimestamp < ' . (int) $date .
		' ORDER BY utimestamp ASC', $search_in_history_db);
	if ($interval_data === false) $interval_data = array ();
	
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
	
	if (count ($interval_data) < 2) {
		return false;
	}
	
	// Set initial conditions
	$critical_period = 0;
	$first_data = array_shift ($interval_data);
	$previous_utimestamp = $first_data['utimestamp'];
	if ((($critical_max > $critical_min AND ($first_data['datos'] > $critical_max OR $first_data['datos'] < $critical_min))) OR
			($critical_max <= $critical_min AND $first_data['datos'] < $critical_min)) {
		$previous_status = 1;
	}
	else {
		$previous_status = 0;
	}
	
	foreach ($interval_data as $data) {
		// Previous status was critical
		if ($previous_status == 1) {
			$critical_period += $data['utimestamp'] - $previous_utimestamp;
		}
		
		// Re-calculate previous status for the next data
		if ((($critical_max > $critical_min AND ($data['datos'] > $critical_max OR $data['datos'] < $critical_min))) OR
			($critical_max <= $critical_min AND $data['datos'] < $critical_min)) {
			$previous_status = 1;
		}
		else {
			$previous_status = 0;
		}
		
		$previous_utimestamp = $data['utimestamp'];
	}
	
	return $period - $critical_period;
}

/** 
 * Get the TTR value of an agent module in a period of time.
 * 
 * @param int Agent module id
 * @param int Period of time to check (in seconds)
 * @param int Top date to check the values. Default current time.
 * 
 * @return float The TTR value in the interval.
 */
function reporting_get_agentmodule_ttr ($id_agent_module, $period = 0, $date = 0) {
	
	// Initialize variables
	if (empty ($date)) $date = get_system_time ();
	
	// Read module configuration
	$datelimit = $date - $period;
	$search_in_history_db = db_search_in_history_db($datelimit);

	$module = db_get_row_sql ('SELECT max_critical, min_critical, id_tipo_modulo
		FROM tagente_modulo
		WHERE id_agente_modulo = ' . (int) $id_agent_module);
	if ($module === false) {
		return false;
	}
	
	$critical_min = $module['min_critical'];
	$critical_max = $module['max_critical'];
	$module_type = $module['id_tipo_modulo'];
	
	// Set critical_min and critical for proc modules
	$module_type_str = modules_get_type_name ($module_type);
	if (strstr ($module_type_str, 'proc') !== false &&
		($critical_min == 0 && $critical_max == 0)) {
		$critical_min = 1;
	}
	
	// Get module data
	$interval_data = db_get_all_rows_sql ('SELECT * FROM tagente_datos 
		WHERE id_agente_modulo = ' . (int) $id_agent_module .
		' AND utimestamp > ' . (int) $datelimit .
		' AND utimestamp < ' . (int) $date .
		' ORDER BY utimestamp ASC', $search_in_history_db);
	if ($interval_data === false) $interval_data = array ();
	
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
	
	if (count ($interval_data) < 2) {
		return false;
	}
	
	// Set initial conditions
	$critical_period = 0;
	$first_data = array_shift ($interval_data);
	$previous_utimestamp = $first_data['utimestamp'];
	if ((($critical_max > $critical_min AND ($first_data['datos'] > $critical_max OR $first_data['datos'] < $critical_min))) OR
			($critical_max <= $critical_min AND $first_data['datos'] < $critical_min)) {
		$previous_status = 1;
	}
	else {
		$previous_status = 0;
	}
	
	foreach ($interval_data as $data) {
		// Previous status was critical
		if ($previous_status == 1) {
			$critical_period += $data['utimestamp'] - $previous_utimestamp;
		}
		
		// Re-calculate previous status for the next data
		if ((($critical_max > $critical_min AND ($data['datos'] > $critical_max OR $data['datos'] < $critical_min))) OR
			($critical_max <= $critical_min AND $data['datos'] < $critical_min)) {
			$previous_status = 1;
		}
		else {
			$previous_status = 0;
		}
		
		$previous_utimestamp = $data['utimestamp'];
	}
	
	return $critical_period;
}


/** 
 * Get a detailed report of the modules of the agent
 * 
 * @param int $id_agent Agent id to get the report for.
 * @param string $filter filter for get partial modules.
 * 
 * @return array An array
 */
function reporting_get_agent_module_info ($id_agent, $filter = false) {
	global $config;
	
	$return = array ();
	$return["last_contact"] = 0; //Last agent contact
	$return["status"] = STATUS_AGENT_NO_DATA;
	$return["status_img"] = ui_print_status_image (STATUS_AGENT_NO_DATA, __('Agent without data'), true);
	$return["alert_status"] = "notfired";
	$return["alert_value"] = STATUS_ALERT_NOT_FIRED;
	$return["alert_img"] = ui_print_status_image (STATUS_ALERT_NOT_FIRED, __('Alert not fired'), true);
	$return["agent_group"] = agents_get_agent_group ($id_agent);
	
	if (!check_acl ($config["id_user"], $return["agent_group"], "AR")) {
		return $return;
	}
	
	if ($filter != '') {
		$filter = 'AND ';
	}
	
	$filter = 'disabled = 0';
	
	$modules = agents_get_modules($id_agent, false, $filter, true, false);
	
	if ($modules === false) {
		return $return;
	}
	
	$now = get_system_time ();
	
	// Get modules status for this agent
	
	$agent = db_get_row ("tagente", "id_agente", $id_agent);

	$return["total_count"] = $agent["total_count"];
	$return["normal_count"] = $agent["normal_count"];
	$return["warning_count"] = $agent["warning_count"];
	$return["critical_count"] = $agent["critical_count"];
	$return["unknown_count"] = $agent["unknown_count"];
	$return["fired_count"] = $agent["fired_count"];
	$return["notinit_count"] = $agent["notinit_count"];
			
	if ($return["total_count"] > 0) {
		if ($return["critical_count"] > 0) {
			$return["status"] = STATUS_AGENT_CRITICAL;
			$return["status_img"] = ui_print_status_image (STATUS_AGENT_CRITICAL, __('At least one module in CRITICAL status'), true);
		}
		else if ($return["warning_count"] > 0) {
			$return["status"] = STATUS_AGENT_WARNING;
			$return["status_img"] = ui_print_status_image (STATUS_AGENT_WARNING, __('At least one module in WARNING status'), true);
		}
		else if ($return["unknown_count"] > 0) {
			$return["status"] = STATUS_AGENT_DOWN;
			$return["status_img"] = ui_print_status_image (STATUS_AGENT_DOWN, __('At least one module is in UKNOWN status'), true);	
		}
		else {
			$return["status"] = STATUS_AGENT_OK;
			$return["status_img"] = ui_print_status_image (STATUS_AGENT_OK, __('All Monitors OK'), true);
		}
	}
	
	//Alert not fired is by default
	if ($return["fired_count"] > 0) {
		$return["alert_status"] = "fired";
		$return["alert_img"] = ui_print_status_image (STATUS_ALERT_FIRED, __('Alert fired'), true);
		$return["alert_value"] = STATUS_ALERT_FIRED;
	}
	elseif (groups_give_disabled_group ($return["agent_group"])) {
		$return["alert_status"] = "disabled";
		$return["alert_value"] = STATUS_ALERT_DISABLED;
		$return["alert_img"] = ui_print_status_image (STATUS_ALERT_DISABLED, __('Alert disabled'), true);
	}
	
	return $return;
}


/**
 * Print tiny statistics of the status of one agent, group, etc.
 * 
 * @param mixed Array with the counts of the total modules, normal modules, critical modules, warning modules, unknown modules and fired alerts
 * @param bool return or echo flag
 * 
 * @return string html formatted tiny stats of modules/alerts of an agent
 */
function reporting_tiny_stats ($counts_info, $return = false, $type = 'agent', $separator = ':', $strict_user = false) {
	global $config;

	$out = '';
	
	// Depend the type of object, the stats will refer agents, modules...
	switch ($type) {
		case 'modules':
			$template_title['total_count'] = __('%d Total modules');
			$template_title['normal_count'] = __('%d Normal modules');
			$template_title['critical_count'] = __('%d Critical modules');
			$template_title['warning_count'] = __('%d Warning modules');
			$template_title['unknown_count'] = __('%d Unknown modules');
			break;
		case 'agent':
			$template_title['total_count'] = __('%d Total modules');
			$template_title['normal_count'] = __('%d Normal modules');
			$template_title['critical_count'] = __('%d Critical modules');
			$template_title['warning_count'] = __('%d Warning modules');
			$template_title['unknown_count'] = __('%d Unknown modules');
			$template_title['fired_count'] = __('%d Fired alerts');
			break;
		default:
			$template_title['total_count'] = __('%d Total agents');
			$template_title['normal_count'] = __('%d Normal agents');
			$template_title['critical_count'] = __('%d Critical agents');
			$template_title['warning_count'] = __('%d Warning agents');
			$template_title['unknown_count'] = __('%d Unknown agents');
			$template_title['not_init_count'] = __('%d not init agents');
			$template_title['fired_count'] = __('%d Fired alerts');
			break;
	}
	
	if ($strict_user && $type == 'agent') {
		
		$acltags = tags_get_user_module_and_tags ($config['id_user'],'AR', $strict_user);
		$filter['disabled'] = 0;
		$id_agent = $counts_info['id_agente'];
		
		$counts_info = array();
		$counts_info['normal_count'] = count(tags_get_agent_modules ($id_agent, false, $acltags, false, $filter, false, AGENT_MODULE_STATUS_NORMAL));
		$counts_info['warning_count'] = count(tags_get_agent_modules ($id_agent, false, $acltags, false, $filter, false, AGENT_MODULE_STATUS_WARNING));
		$counts_info['critical_count'] = count(tags_get_agent_modules ($id_agent, false, $acltags, false, $filter, false, AGENT_MODULE_STATUS_CRITICAL_BAD));
		$counts_info['notinit_count'] = count(tags_get_agent_modules ($id_agent, false, $acltags, false, $filter, false, AGENT_MODULE_STATUS_NOT_INIT));
		$counts_info['unknown_count'] = count(tags_get_agent_modules ($id_agent, false, $acltags, false, $filter, false, AGENT_MODULE_STATUS_UNKNOWN));
		$counts_info['total_count'] = $counts_info['normal_count'] + $counts_info['warning_count'] + $counts_info['critical_count'] + $counts_info['unknown_count'] + $counts_info['notinit_count'];
		
		$all_agent_modules = tags_get_agent_modules ($id_agent, false, $acltags, false, $filter);
		if (!empty($all_agent_modules)) {
			$mod_clause = "(".implode(',', array_keys($all_agent_modules)).")";

			$counts_info['fired_count'] = (int) db_get_sql ("SELECT COUNT(times_fired)
				FROM talert_template_modules
				WHERE times_fired != 0 AND id_agent_module IN ".$mod_clause);
		}
		else {
			$counts_info['fired_count'] = 0;
		}
	}
	
	// Store the counts in a data structure to print hidden divs with titles
	$stats = array();
	
	if (isset($counts_info['total_count'])) {
		$not_init = isset($counts_info['notinit_count']) ? $counts_info['notinit_count'] : 0;
		$total_count = $counts_info['total_count'] - $not_init;
		$stats[] = array('name' => 'total_count', 'count' => $total_count, 'title' => sprintf($template_title['total_count'], $total_count));
	}
	
	if (isset($counts_info['normal_count'])) {
		$normal_count = $counts_info['normal_count'];
		$stats[] = array('name' => 'normal_count', 'count' => $normal_count, 'title' => sprintf($template_title['normal_count'], $normal_count));
	}
	
	if (isset($counts_info['critical_count'])) {
		$critical_count = $counts_info['critical_count'];
		$stats[] = array('name' => 'critical_count', 'count' => $critical_count, 'title' => sprintf($template_title['critical_count'], $critical_count));
	}
	
	if (isset($counts_info['warning_count'])) {
		$warning_count = $counts_info['warning_count'];
		$stats[] = array('name' => 'warning_count', 'count' => $warning_count, 'title' => sprintf($template_title['warning_count'], $warning_count));
	}
	
	if (isset($counts_info['unknown_count'])) {
		$unknown_count = $counts_info['unknown_count'];
		$stats[] = array('name' => 'unknown_count', 'count' => $unknown_count, 'title' => sprintf($template_title['unknown_count'], $unknown_count));
	}
	
	if (isset($counts_info['not_init_count'])) {
		$not_init_count = $counts_info['not_init_count'];
		$stats[] = array('name' => 'not_init_count',
			'count' => $not_init_count,
			'title' => sprintf($template_title['not_init_count'], $not_init_count));
	}
	
	if (isset($template_title['fired_count'])) {
		if (isset($counts_info['fired_count'])) {
			$fired_count = $counts_info['fired_count'];
			$stats[] = array('name' => 'fired_count', 'count' => $fired_count, 'title' => sprintf($template_title['fired_count'], $fired_count));
		}
	}
	
	$uniq_id = uniqid();
	
	foreach ($stats as $stat) {
		$params = array('id' => 'forced_title_' . $stat['name'] . '_' . $uniq_id, 
						'class' => 'forced_title_layer', 
						'content' => $stat['title'],
						'hidden' => true);
		$out .= html_print_div($params, true);
	}
	
	// If total count is less than 0, is an error. Never show negative numbers
	if ($total_count < 0) {
		$total_count = 0;
	}
	
	$out .= '<b>' . '<span id="total_count_' . $uniq_id . '" class="forced_title" style="font-size: 7pt">' . $total_count . '</span>';
	if (isset($fired_count) && $fired_count > 0)
		$out .= ' ' . $separator . ' <span class="orange forced_title" id="fired_count_' . $uniq_id . '" style="font-size: 7pt">' . $fired_count . '</span>';
	if (isset($critical_count) && $critical_count > 0)
		$out .= ' ' . $separator . ' <span class="red forced_title" id="critical_count_' . $uniq_id . '" style="font-size: 7pt">' . $critical_count . '</span>';
	if (isset($warning_count) && $warning_count > 0)
		$out .= ' ' . $separator . ' <span class="yellow forced_title" id="warning_count_' . $uniq_id . '" style="font-size: 7pt">' . $warning_count . '</span>';
	if (isset($unknown_count) && $unknown_count > 0)
		$out .= ' ' . $separator . ' <span class="grey forced_title" id="unknown_count_' . $uniq_id . '" style="font-size: 7pt">' . $unknown_count . '</span>';
	if (isset($not_init_count) && $not_init_count > 0)
		$out .= ' ' . $separator . ' <span class="blue forced_title" id="not_init_count_' . $uniq_id . '" style="font-size: 7pt">' . $not_init_count . '</span>';
	if (isset($normal_count) && $normal_count > 0)
		$out .= ' ' . $separator . ' <span class="green forced_title" id="normal_count_' . $uniq_id . '" style="font-size: 7pt">' . $normal_count . '</span>';
	
	$out .= '</b>';
	
	if ($return) {
		return $out;
	}
	else {
		echo $out;
	}
}
?>