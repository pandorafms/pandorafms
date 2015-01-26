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


// Load global vars
global $config;

enterprise_include ("operation/snmpconsole/snmp_view.php");
include_once($config['homedir'] . '/include/functions_graph.php');
require_once($config['homedir'] . "/include/functions_agents.php");
require_once($config['homedir'] . "/include/functions_snmp.php");

check_login ();

// ACL
if (! check_acl ($config['id_user'], 0, "AR")) {
	db_pandora_audit("ACL Violation",
		"Trying to access SNMP Console");
	require ("general/noaccess.php");
	exit;
}

// Read parameters
$refr = (int)get_parameter("refr", 0);


// Page header and tabs

// Fullscreen
$fullscreen = array();
if ($config["pure"]) {
	$fullscreen['text'] = '<a target="_top" href="index.php?sec=estado&sec2=operation/snmpconsole/snmp_statistics&pure=0&refr=' . $refr . '">' .
		html_print_image("images/normal_screen.png", true, array("title" => __('Normal screen')))  . '</a>';
}
else {
	$fullscreen['text'] = '<a target="_top" href="index.php?sec=estado&sec2=operation/snmpconsole/snmp_statistics&pure=1&refr=' . $refr . '">' .
		html_print_image("images/full_screen.png", true, array("title" => __('Full screen'))) . '</a>';
}

// List
$list = array();
$list['text'] = '<a href="index.php?sec=estado&sec2=operation/snmpconsole/snmp_view&pure=' . $config["pure"] . '&refr=' . $refr . '">' . 
	html_print_image("images/op_snmp.png", true, array ("title" => __('List'))) .'</a>';

// Statistics (This file)
$statistics = array();
$statistics['active'] = true;
$statistics['text'] = '<a href="index.php?sec=estado&sec2=operation/snmpconsole/snmp_statistics&pure=' . $config["pure"] . '&refr=' . $refr . '">' . 
	html_print_image("images/op_reporting.png", true, array ("title" => __('Statistics'))) .'</a>';

// Header
ui_print_page_header(__("SNMP Console"), "images/op_snmp.png", false,
	"", false, array($fullscreen, $list, $statistics));

// Retrieving the data

$user_groups = users_get_groups($config['id_user'], "AR", false);
$user_groups_str = '0';
if (!empty($user_groups)) {
	$user_groups_str = implode(",", array_keys($user_groups));
}

$last_month_timestamp = date("Y-m-d H:i:s", time() - SECONDS_1MONTH);

$sql_traps_generated = "SELECT %s, COUNT(id_trap) AS num
						FROM ttrap
						WHERE timestamp >= '%s'
							AND (source = ''
								OR source NOT IN (SELECT direccion FROM tagente)
								OR source IN (SELECT direccion
											  FROM tagente
											  WHERE id_grupo IN (%s)))
						GROUP BY %s
						ORDER BY num DESC, timestamp DESC
						LIMIT 25";

$sql_traps_generated_by_source = sprintf($sql_traps_generated, "source", $last_month_timestamp, $user_groups_str, "source");
$sql_traps_generated_by_oid = sprintf($sql_traps_generated, "oid", $last_month_timestamp, $user_groups_str, "oid");

$traps_generated_by_source = db_get_all_rows_sql($sql_traps_generated_by_source);
$traps_generated_by_oid = db_get_all_rows_sql($sql_traps_generated_by_oid);

// No traps 
if (empty($traps_generated_by_source) || empty($traps_generated_by_oid)) {
	echo '<div class="nf">'.__('There are no SNMP traps in database').'</div>';
	return;
}

$water_mark = array('file' => $config['homedir'] .  "/images/logo_vertical_water.png",
		'url' => ui_get_full_url("/images/logo_vertical_water.png"));

// By SOURCE
$table_source = new StdClass();
$table_source->width = '100%';
$table_source->head[] = __("Traps received by source") . " - " . sprintf(__('Top %d'), 25);
$table_source->head_colspan[] = 2;
$table_source->headstyle[] = "background-color: #82b92e";
$table_source->size = array();
$table_source->size['table'] = '50%';
$table_source->size['graph'] = '50%';
$table_source->data = array();

$table_source_row = array();

$table_source_data = new StdClass();
$table_source_data->width = '100%';
$table_source_data->head = array();
$table_source_data->head['source'] = __('Source IP');
$table_source_data->head['num'] = __('Number');
$table_source_data->data = array();

$table_source_graph_data = array();

foreach ($traps_generated_by_source as $trap) {
	$row = array();

	$agent = agents_get_agent_with_ip($trap['source']);
	if ($agent === false) {
		$row['source'] = '<a href="index.php?sec=estado&sec2=godmode/agentes/configurar_agente&new_agent=1&direccion='.$trap["source"].'" title="'.__('Create agent').'">'.$trap["source"].'</a>';
	}
	else {
		$agent_id = $agent["id_agente"];
		$agent_name = ui_print_truncate_text($agent["nombre"], "agent_medium", true, true, true, '[&hellip;]', '');
		$row['source'] = "<a href=\"index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=$agent_id\" title=\"".__('View agent details')."\">";
		$row['source'] .= "<strong>$agent_name</strong></a>";
	}
	$row['num'] = (int) $trap['num'];

	$table_source_data->data[] = $row;

	$table_source_graph_data[$trap["source"]] = (int) $trap["num"];
}

$table_source_row['table'] = html_print_table($table_source_data, true);
unset($table_source_data);

if (empty($table_source_graph_data))
	$table_source_graph = graph_nodata_image();
else
	$table_source_graph = pie3d_graph($config['flash_charts'], $table_source_graph_data, 400, 550, __("Other"),
		'', $water_mark, $config['fontpath'], $config['font_size'], 1);

$table_source_row['graph'] = $table_source_graph;

$table_source->data[] = $table_source_row;

html_print_table($table_source);
unset($table_source);

// By OID
$table_oid = new StdClass();
$table_oid->width = '100%';
$table_oid->head[] = __("Traps received by Enterprise String") . " - " . sprintf(__('Top %d'), 25);
$table_oid->head_colspan[] = 2;
$table_oid->headstyle[] = "background-color: #82b92e";
$table_oid->size = array();
$table_oid->size['table'] = '50%';
$table_oid->size['graph'] = '50%';
$table_oid->data = array();

$table_oid_row = array();

$table_oid_data = new StdClass();
$table_oid_data->width = '100%';
$table_oid_data->head = array();
$table_oid_data->head['oid'] = __('Trap Enterprise String');
$table_oid_data->head['num'] = __('Number');
$table_oid_data->data = array();

$table_oid_graph_data = array();

foreach ($traps_generated_by_oid as $trap) {
	$table_oid_data->data[] = array(
			'oid' => $trap['oid'],
			'num' => (int) $trap['num']
		);

	$table_oid_graph_data[$trap["oid"]] = (int) $trap["num"];
}

$table_oid_row['table'] = html_print_table($table_oid_data, true);
unset($table_oid_data);

if (empty($table_oid_graph_data))
	$table_oid_graph = graph_nodata_image();
else
	$table_oid_graph = pie3d_graph($config['flash_charts'], $table_oid_graph_data, 400, 550, __("Other"),
		'', $water_mark, $config['fontpath'], $config['font_size'], 1);

$table_oid_row['graph'] = $table_oid_graph;

$table_oid->data[] = $table_oid_row;

html_print_table($table_oid);
unset($table_oid);

?>