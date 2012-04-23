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

session_start ();

require_once ("../../include/config.php");
require_once ("../../include/functions_agents.php");
require_once ("../../include/functions_reporting.php");
require_once ("../../include/functions_modules.php");
require_once ("../../include/functions_users.php");

global $config;

if (!check_acl ($config['id_user'], 0, "AR")) {
	require ("../../general/noaccess.php");
	return;
}

$group = get_parameter_post ('group', 0);
$agentName = get_parameter_post ('agent', 0);
switch ($config["dbtype"]) {
	case "mysql":
	case "postgresql":
		$agents = agents_get_agents (array('nombre LIKE "' . $agentName . '"'), array ('id_agente'));
		break;
	case "oracle":
		$agents = agents_get_agents (array('nombre LIKE \'%' . $agentName . '%\''), array ('id_agente'));
		break;
}

$agent = $agents[0]['id_agente'];

$module = (array) get_parameter_post ('module_arr', array ());
$start_date = get_parameter_post ('start_date', 0);
$end_date = get_parameter_post ('end_date', 0);
$start_time = get_parameter_post ('start_time', 0);
$end_time = get_parameter_post ('end_time', 0);
$export_type = get_parameter_post ('export_type', 'data');
$export_btn = get_parameter_post ('export_btn', 0);

if (!empty ($export_btn) && !empty ($module)) {

	// Disable SQL cache
	global $sql_cache;
	$sql_cache = array ('saved' => 0);


	//Convert start time and end time to unix timestamps
	$start = strtotime ($start_date." ".$start_time);
	$end = strtotime ($end_date." ".$end_time);
	$period = $end - $start;
	$data = array ();	
	
	//If time is negative or zero, don't process - it's invalid
	if ($start < 1 || $end < 1) {
		ui_print_error_message (__('Invalid time specified'));
		return;
	}

	// ***************************************************
	// Starts, ends and dividers
	// ***************************************************

	//Pure CSV is comma delimited
	$datastart = __('Agent').','.__('Module').','.__('Data').','.__('Timestamp')."\n";
	$rowstart = '"';
	$divider = '","';
	$rowend = '"'."\n";
	$dataend = "\n";
	$extension = "csv";


	// ***************************************************
	// Header output
	// ***************************************************
	$config['ignore_callback'] = true;
	while (@ob_end_clean ());
	
	header("Content-type: application/octet-stream");
	header("Content-Disposition: attachment; filename=export_".date("Ymd", $start)."_".date("Ymd", $end).".".$extension);
	header("Pragma: no-cache");
	header("Expires: 0");

	// ***************************************************
	// Data processing
	// ***************************************************

	$data = array ();

	// Show header
	echo $datastart;

	foreach ($module as $selected) {

		$output = "";
		$work_period = 120000;
		if ($work_period > $period) {
			$work_period = $period;
		}

		$work_end = $end - $period + $work_period;
		//Buffer to get data, anyway this will report a memory exhaustin

		while ($work_end <= $end) {

			$data = array (); // Reinitialize array for each module chunk
			if ($export_type == "avg") {
				$arr = array ();
				$arr["data"] = reporting_get_agentmodule_data_average ($selected, $work_period, $work_end);
				if ($arr["data"] === false) {
					continue;
				}	
				$arr["module_name"] = modules_get_agentmodule_name ($selected);
				$arr["agent_name"] = modules_get_agentmodule_agent_name ($selected);
				$arr["agent_id"] = modules_get_agentmodule_agent ($selected);
				$arr["utimestamp"] = $end;				
				array_push ($data, $arr);
			}
			else {
				$data_single = modules_get_agentmodule_data ($selected, $work_period, $work_end);
				if (!empty ($data_single)) {
					$data = array_merge ($data, $data_single);
				}
			}
			
			foreach ($data as $key => $module) {
				$output .= $rowstart;
				$output .= io_safe_output($module['agent_name']);
				$output .= $divider;
				$output .= io_safe_output($module['module_name']);
				$output .= $divider;
				$output .= $module['data'];
				$output .= $divider;
				$output .= date ("Y-m-d G:i:s", $module['utimestamp']);
				$output .= $rowend;
			}
			
			
			echo $output;
			
			unset($output);
			$output = "";
			unset($data);
			unset($data_single);
			$work_end = $work_end + $work_period;
		}
		unset ($output);
		$output = "";
	} // main foreach
	echo $dataend;

	exit; // Necesary for CSV export, if not give problems
		
}

elseif (!empty ($export_btn) && empty ($module)) {
	ui_print_error_message (__('No modules specified'));
}

?>
