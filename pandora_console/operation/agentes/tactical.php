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
ui_print_page_header (__("Tactical view"), "images/bricks.png", false, "", false, $updated_time );
$data = reporting_get_group_stats();

if(tags_has_user_acl_tags()) {
	ui_print_tags_warning();
}

echo '<table border=0 style="width:100%;"><tr>';
echo '<td style="vertical-align: top; min-width: 265px; width:30%; padding-right: 5%;" id="leftcolumn">';
// ---------------------------------------------------------------------
// The status horizontal bars (Global health, Monitor sanity...
// ---------------------------------------------------------------------
$table->width = "100%";
$table->class = "";
$table->cellpadding = 4;
$table->cellspacing = 4;
$table->border = 0;
$table->head = array ();
$table->data = array ();
$table->style = array ();

$table->data[0][0] = reporting_get_stats_indicators($data, 140, 20);


html_print_table ($table);
unset ($table);

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
$table->data[0][0] .= reporting_get_stats_modules_status($data, 200, 100) . '<br>';
$table->data[0][0] .= reporting_get_stats_agents_monitors($data) . '<br>';

html_print_table($table);
// ---------------------------------------------------------------------
// Server performance 
// ---------------------------------------------------------------------
if ($is_admin) {
	$table->width = "100%";
	$table->class = "";
	$table->cellpadding = 4;
	$table->cellspacing = 4;
	$table->border = 0;
	$table->head = array ();
	$table->data = array ();
	$table->style = array ();

	$table->data[0][0] = reporting_get_stats_servers(false);

	html_print_table($table);
}

echo '</td>'; //Left column

echo '<td style="vertical-align: top; width: 70%;" id="rightcolumn">';


// ---------------------------------------------------------------------
// Last events information
// ---------------------------------------------------------------------
$tags_condition = tags_get_acl_tags($config['id_user'], 0, 'ER', 'event_condition', 'AND');

events_print_event_table ("estado<>1 $tags_condition", 10, "100%");

// ---------------------------------------------------------------------
// Server information
// ---------------------------------------------------------------------
if ($is_admin) {
	$serverinfo = servers_get_info ();
	$cells = array ();
	
	if ($serverinfo === false) {
		$serverinfo = array ();
	}
	
	$table->class = "databox";
	$table->cellpadding = 4;
	$table->cellspacing = 4;
	$table->width = "100%";
	
	$table->title = __('Tactical server information');
	$table->titlestyle = "background-color:#799E48;";
	
	$table->head = array ();
	$table->head[0] = __('Name');
	$table->head[1] = __('Type');
	$table->head[2] = __('Status');
	$table->head[3] = __('Load');
	$table->head[4] = __('Lag').' ' . ui_print_help_icon ("serverlag", true);
	$table->align[2] = 'center';
	$table->align[3] = 'center';
	$table->align[4] = 'right';
	
	$table->data = array ();
	
	foreach ($serverinfo as $server) {
		$data = array ();
		$data[0] = $server["name"];
		$data[1] = '<span style="white-space:nowrap;">'.$server["img"].'</span> ('.ucfirst($server["type"]).")";
		if ($server["master"] == 1)
			$data[1] .= ui_print_help_tip (__("This is a master server"), true);
		
		if ($server["status"] == 0){
			$data[2] = ui_print_status_image (STATUS_SERVER_DOWN, '', true);
		}
		else {
			$data[2] = ui_print_status_image (STATUS_SERVER_OK, '', true);
		}
		
		if ($server["type"] != "snmp") {
			$data[3] = progress_bar($server["load"], 80, 20);
			
			if ($server["type"] != "recon"){
				$data[4] = $server["lag_txt"];
			}
			else {
				$data[4] = __("N/A");
			}
		}
		else {
			$data[3] = "";
			$data[4] = __("N/A");
		}
		
		array_push ($table->data, $data);
	}
	
	if (!empty ($table->data)) {
		html_print_table ($table);
	}
	else {
		echo '<div class="nf">' .
			__('There are no servers configured in the database') .
			'</div>';
	}
	unset ($table);
}


echo '</td>';
echo '</tr></table>';
?>
