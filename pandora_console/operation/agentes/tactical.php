<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


// Load global vars
global $config;

require_once ("include/functions_events.php");
require_once ("include/functions_servers.php");
require_once ("include/functions_reporting.php");
require_once ("include/functions_tactical.php");
require_once ($config["homedir"] . '/include/functions_graph.php');

check_login ();

if (! check_acl ($config['id_user'], 0, "AR") && ! check_acl ($config['id_user'], 0, "AW")) {
	db_pandora_audit("ACL Violation", 
	"Trying to access Agent view (Grouped)");
	require ("general/noaccess.php");
	return;
}

$is_admin = check_acl ($config['id_user'], 0, "PM");
$user_strict = (bool) db_get_value('strict_acl', 'tusuario', 'id_user', $config['id_user']);

$force_refresh = get_parameter ("force_refresh", "");
$refresh = get_parameter ("refr", 0);
if ($force_refresh == 1) {
	db_process_sql ("UPDATE tgroup_stat SET utimestamp = 0");
}

if ($config["realtimestats"] == 0) {
	$updated_time ="<a href='index.php?sec=estado&sec2=operation/agentes/tactical&force_refresh=1'>";
	$updated_time .= __('Last update'). " : ". ui_print_timestamp (db_get_sql ("SELECT min(utimestamp) FROM tgroup_stat"), true);
	$updated_time .= "</a>";
}
else {
//	$updated_info = __("Updated at realtime");
		$updated_info = "";
}

// Header
ui_print_page_header (__("Tactical view"), "", false, "", false, $updated_time);

//Currently this function makes loading this page is impossible. Change
//and create new function.
//$all_data = group_get_groups_list($config['id_user'], $user_strict, 'AR', true, false, 'tactical');

$all_data = tactical_status_modules_agents($config['id_user'], $user_strict, 'AR', $user_strict);

$data = array();

$data['monitor_not_init'] = (int) $all_data['_monitors_not_init_'];
$data['monitor_unknown'] = (int) $all_data['_monitors_unknown_'];
$data['monitor_ok'] = (int) $all_data['_monitors_ok_'];
$data['monitor_warning'] = (int) $all_data['_monitors_warning_'];
$data['monitor_critical'] = (int) $all_data['_monitors_critical_'];
$data['monitor_not_normal'] = (int) $all_data['_monitor_not_normal_'];
$data['monitor_alerts'] = (int) $all_data['_monitors_alerts_'];
$data['monitor_alerts_fired'] = (int) $all_data['_monitors_alerts_fired_'];

$data['total_agents'] = (int) $all_data['_total_agents_'];

$data["monitor_checks"] = (int) $all_data['_monitor_checks_'];


// Percentages
if (!empty($all_data)) {
	if ($data["monitor_not_normal"] > 0 && $data["monitor_checks"] > 0) {
		$data['monitor_health'] = format_numeric (100 - ($data["monitor_not_normal"] / ($data["monitor_checks"] / 100)), 1);
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
			$data["alert_level"] = format_numeric (100 - ($data["monitor_alerts_fired"] / ($data["alerts"] / 100)), 1);
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
}

echo '<table border=0 style="width:100%;"><tr>';
echo '<td style="vertical-align: top; min-width: 180px; width:25%; padding-right: 20px; vertical-align: top; padding-top: 0px;" id="leftcolumn">';
// ---------------------------------------------------------------------
// The status horizontal bars (Global health, Monitor sanity...
// ---------------------------------------------------------------------
$table = new stdClass();
$table->width = "100%";
$table->class = "";
$table->cellpadding = 2;
$table->cellspacing = 2;
$table->border = 0;
$table->head = array ();
$table->data = array ();
$table->style = array ();

$table->head[0] = '<span>' . __('Report of State') . '</span>';
$stats = reporting_get_stats_indicators($data, 120, 10,false);
$status = '<table class="status_tactical">';
foreach ( $stats as $stat ) {
	$status .= '<tr><td><b>' . $stat['title'] . '</b>' . '</td><td>' . $stat['graph'] . "</td></tr>" ;
}
$status .= '</table>';
$table->data[0][0] = $status;
$table->rowclass[] = '';

// ---------------------------------------------------------------------
// Monitor checks
// ---------------------------------------------------------------------

$data_agents = array(
		__('Critical') => $data['monitor_critical'],
		__('Warning') => $data['monitor_warning'],
		__('Normal') => $data['monitor_ok'],
		__('Unknown') => $data['monitor_unknown'],
		__('Not init') => $data['monitor_not_init']
	);

$table->data[1][0] = reporting_get_stats_alerts($data);
$table->data[2][0] = reporting_get_stats_modules_status($data, 180, 100, false, $data_agents);
$table->data[3][0] = reporting_get_stats_agents_monitors($data);

$table->rowclass[] = '';

// ---------------------------------------------------------------------
// Server performance 
// ---------------------------------------------------------------------
if ($is_admin) {
	$table->data[4][0] = reporting_get_stats_servers(false);
	$table->rowclass[] = '';
}

html_print_table($table);

echo '</td>'; //Left column

echo '<td style="vertical-align: top; width: 75%; padding-top: 0px;" id="rightcolumn">';

// ---------------------------------------------------------------------
// Last events information
// ---------------------------------------------------------------------

$acltags = tags_get_user_module_and_tags ($config['id_user'], 'ER', $user_strict);

if (!empty($acltags)) {
	$tags_condition = tags_get_acl_tags_event_condition($acltags, false, $user_strict);
	
	if (!empty($tags_condition)) {
		$events = events_print_event_table ("estado<>1 AND ($tags_condition)", 10, "100%",true,false,true);
		ui_toggle($events, __('Latest events'),false,false);
	}
}

// ---------------------------------------------------------------------
// Server information
// ---------------------------------------------------------------------
if ($is_admin) {
	$tiny = true;
	require($config['homedir'] . '/godmode/servers/servers.build_table.php');
	
}
$out = '<table cellpadding=0 cellspacing=0 class="databox pies"  style="margin-top:15px;" width=100%><tr><td>';
	$out .= '<fieldset class="databox tactical_set">
			<legend>' . 
				__('Event graph') . 
			'</legend>' . 
			grafico_eventos_total("", 280, 150, false) . '</fieldset>';
	$out .="</td><td>";
	$out .= '<fieldset class="databox tactical_set">
			<legend>' .
				__('Event graph by agent') .
			'</legend>' .
			grafico_eventos_grupo(280, 150, "", false, false, false) . '</fieldset>';
	$out .= '</td></tr></table>';
echo $out;

echo '</td>';
echo '</tr></table>';
?>