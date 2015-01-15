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
require_once ($config["homedir"] . '/include/functions_graph.php');

check_login ();

if (! check_acl ($config['id_user'], 0, "AR")) {
	db_pandora_audit("ACL Violation", 
	"Trying to access Agent view (Grouped)");
	require ("general/noaccess.php");
	return;
}
 
$is_admin = check_acl ($config['id_user'], 0, "PM");
$user_strict = db_get_value('strict_acl', 'tusuario', 'id_user', $config['id_user']);

$force_refresh = get_parameter ("force_refresh", "");
if ($force_refresh == 1) {
	db_process_sql ("UPDATE tgroup_stat SET utimestamp = 0");
}

if ($config["realtimestats"] == 0) {
	$updated_time ="<a href='index.php?sec=estado&sec2=operation/agentes/tactical&force_refresh=1'>";
	$updated_time .= __('Last update'). " : ". ui_print_timestamp (db_get_sql ("SELECT min(utimestamp) FROM tgroup_stat"), true);
	$updated_time .= "</a>"; 
}
else {
	$updated_time = __("Updated at realtime");
}

// Header
ui_print_page_header (__("Tactical view"), "images/op_monitoring.png", false, "", false, $updated_time);

$all_data = group_get_groups_list($config['id_user'], $user_strict, 'AR', true, false, 'tactical');

$data = array();
foreach ($all_data as $item) {
	$data['monitor_checks'] += $item['_monitor_checks_'];
	$data['monitor_not_init'] += $item['_monitors_not_init_'];
	$data['monitor_unknown'] += $item['_monitors_unknown_'];
	$data['monitor_ok'] += $item['_monitors_ok_'];
	$data['monitor_bad'] += $item['_monitor_bad_'];
	$data['monitor_warning'] += $item['_monitors_warning_'];
	$data['monitor_critical'] += $item['_monitors_critical_'];
	$data['monitor_not_normal'] += $item['_monitor_not_normal_'];
	$data['monitor_alerts'] += $item['_monitors_alerts_'];
	$data['monitor_alerts_fired'] += $item['_monitors_alerts_fired_'];
	$data['monitor_alerts_fire_count'] += $item['_monitor_alerts_fire_count_'];
	$data['total_agents'] += $item['_total_agents_'];
	$data['total_alerts'] += $item['_total_alerts_'];
	$data['total_checks'] += $item['_total_checks_'];
	$data['alerts'] += $item['_alerts_'];
	$data['agents_unknown'] += $item['_agents_unknown_'];
	$data['monitor_health'] += $item['_monitor_health_'];
	$data['alert_level'] += $item['_alert_level_'];
	$data['module_sanity'] += $item['_module_sanity_'];
	$data['server_sanity'] += $item['_server_sanity_'];
	$data['agent_ok'] += $item['_agents_ok_'];
	$data['agent_warning'] += $item['_agents_warning_'];
	$data['agent_critical'] += $item['_agents_critical_'];
	$data['agent_unknown'] += $item['_agents_unknown_'];
	$data['agent_not_init'] += $item['_agents_not_init_'];
	$data['global_health'] += $item['_global_health_'];
	
}

echo '<table border=0 style="width:100%;"><tr>';
echo '<td style="vertical-align: top; min-width: 180px; width:25%; padding-right: 10px; vertical-align: top; padding-top: 0px;" id="leftcolumn">';
// ---------------------------------------------------------------------
// The status horizontal bars (Global health, Monitor sanity...
// ---------------------------------------------------------------------
$table->width = "100%";
$table->class = "";
$table->cellpadding = 2;
$table->cellspacing = 2;
$table->border = 0;
$table->head = array ();
$table->data = array ();
$table->style = array ();

$table->data[0][0] = reporting_get_stats_indicators($data, 120, 20);
$table->rowclass[] = '';


html_print_table ($table);
unset($table);

// ---------------------------------------------------------------------
// Monitor checks
// ---------------------------------------------------------------------
$table->width = "100%";
$table->class = "";
$table->cellpadding = 4;
$table->cellspacing = 4;
$table->border = 0;
$table->head = array ();
$table->data = array ();
$table->style = array ();

$table->data[0][0] = reporting_get_stats_alerts($data);
$table->data[0][0] .= reporting_get_stats_modules_status($data, 180, 100);
$table->data[0][0] .= reporting_get_stats_agents_monitors($data);
$table->rowclass[] = '';

html_print_table($table);


// ---------------------------------------------------------------------
// Server performance 
// ---------------------------------------------------------------------
if ($is_admin) {
	$table->width = "99%";
	$table->class = "";
	$table->cellpadding = 4;
	$table->cellspacing = 4;
	$table->border = 0;
	$table->head = array ();
	$table->data = array ();
	$table->style = array ();
	
	$table->data[0][0] = reporting_get_stats_servers(false);
	$table->rowclass[] = '';
	
	html_print_table($table);
}

echo '</td>'; //Left column

echo '<td style="vertical-align: top; width: 75%; padding-top: 0px;" id="rightcolumn">';


// ---------------------------------------------------------------------
// Last events information
// ---------------------------------------------------------------------

$acltags = tags_get_user_module_and_tags ($config['id_user'], $access = 'ER', $user_strict);
$tags_condition = tags_get_acl_tags_event_condition($acltags, false, $user_strict);

events_print_event_table ("estado<>1 AND ($tags_condition)", 10, "100%");

// ---------------------------------------------------------------------
// Server information
// ---------------------------------------------------------------------
if ($is_admin) {
	$tiny = true;
	require($config['homedir'] . '/godmode/servers/servers.build_table.php');
}


echo '</td>';
echo '</tr></table>';
?>
