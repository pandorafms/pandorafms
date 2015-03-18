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

function reporting_make_reporting_data($id_report, $datetime) {
	$return = array();
	
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
	
	return $return;
}

function reporting_simple_graph($id_report, $id_report_item, $type = 'dinamic') {
	global $config;
	
	$return = array();
	
	$content = db_get_row('treport_content_item', 'id', $id_report_item);
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
	$return["date"] = reporting_get_date_text($id_report, $id_report_item);
	
	// Get chart
	switch ($type) {
		case 'dinamic':
			break;
		case 'static':
			break;
		case 'data':
			break;
	}
	
	return reporting_check_structure_content($return);
}

function reporting_get_date_text($id_report, $id_report_item) {
	global $config;
	
	$return = array();
	$return['date'] = "";
	$return['period'] = "";
	$return['from'] = "";
	$return['to'] = "";
	
	$report = db_get_row('treport_content_item', 'id', $id_report_item);
	$content = db_get_row('treport', 'id_report', $id_report);
	
	if ($content['period'] == 0) {
		$es = json_decode($content['external_source'], true);
		if ($es['date'] == 0) {
			$return['date'] = __('Last data');
		}
		else {
			$return['date'] = date($config["date_format"], $es['date']);
		}
	}
	else {
		$return['period'] = human_time_description_raw ($content['period']);
		$return['from'] = date($config["date_format"], $report["datetime"] - $content['period']);
		$return['from'] = date($config["date_format"], $report["datetime"]);
	}
	
	return $return;
}

/**
 * Check the common items exits
 */
function reporting_check_structure_content($return) {
	if (!isset($return['title']))
		$return['title'] = "";
	if (!isset($return['subtitle']))
		$return['subtitle'] = "";
	if (!isset($return['description']))
		$return['description'] = "";
	if (!isset($return["date"])) {
		$return["date"]['date'] = "";
		$return["date"]['period'] = "";
		$return["date"]['from'] = "";
		$return["date"]['to'] = "";
	}
	
	
	return $return;
}
?>
