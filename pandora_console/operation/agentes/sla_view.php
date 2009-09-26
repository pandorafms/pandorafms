<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


// Load global vars
require_once ("include/config.php");
require_once ("include/functions_reporting.php");

check_login();

if (! give_acl ($config['id_user'], 0, "AR") && ! give_acl ($config['id_user'], 0, "AW")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access SLA View");
	require ("general/noaccess.php");
	exit;
}

echo "<h2>".__('Pandora agents'). " &raquo; ".__('SLA view')."</h2>";
$id_agent = get_parameter ("id_agente", 0);
$interval = get_agent_interval ($id_agent);
$modules = get_agent_modules ($id_agent, '*',
	array ('disabled' => 0, 'history_data' => 1, 'delete_pending' => 0));
if (empty ($modules)) {
	print_error_message (__("There are no modules to evaluate the S.L.A. from"));
	return;
}

$offset = get_parameter ("offset", 0);

// Get all module from agent
echo "<h3>".__('Automatic SLA for monitors')."</h3>";
pagination (count ($modules), "index.php?sec=estado&sec2=operation/agentes/ver_agente&tab=sla&id_agente=".$id_agent, $offset);

$table->width = '95%';
$table->cellpadding = 4;
$table->cellspacing = 4;
$table->class = "databox";

$table->head = array ();
$table->head[0] = __('Type');
$table->head[1] = __('Module name');
$table->head[2] = __('S.L.A.');
$table->head[3] = __('Status');
$table->head[4] = __('Interval');

$table->align = array ();
$table->align[0] = "center";
$table->align[1] = "center";
$table->align[2] = "center";
$table->align[3] = "center";
$table->align[4] = "center";

$table->data = array ();
$loc = 0;

foreach ($modules as $module_id => $module) {
	if ($loc < $offset) {
		$loc++;
		continue; //Skip offset
	} elseif ($loc >= $offset + $config["block_size"]) {
		continue;
	}
	$data = array ();
	$data[0] = print_moduletype_icon ($module["id_tipo_modulo"], true);
	$data[1] = print_string_substr ($module["nombre"], 25, true);
	$data[2] = format_numeric (get_agentmodule_sla ($module_id, $config["sla_period"], 1)).'%';

	//TODO: Make this work for all new status
	$status = get_agentmodule_status ($module_id);
	if ($status == 1){
		$data[3] = print_image ("images/pixel_red.png", true, array ("width" => 40, "height" => 18, "title" => __('Module Down')));
	} else {
		$data[3] = print_image ("images/pixel_green.png", true, array ("width" => 40, "height" => 18, "title" => __('Module Up')));
	}
			
	if ($module["module_interval"] > 0) {
		$data[4] = $module["module_interval"];
	} else {
		$data[4] = $interval;
	}
	array_push ($table->data, $data);
	$loc++;
}

print_table ($table);
unset ($table);

// Get all SLA report components
$sql = "SELECT id_agent_module, sla_max, sla_min, sla_limit FROM treport_content_sla_combined WHERE id_agent_module IN (".implode (",",array_keys ($modules)).")";
$result = get_db_all_rows_sql ($sql);
if ($result !== false) {
	echo "<h3>".__('User-defined SLA items')." - ".human_time_description_raw ($config["sla_period"])."</h3>";
	$table->width = '95%';
	$table->cellpadding = 4;
	$table->cellspacing = 4;
	$table->class = "databox";
	$table->head = array ();
	$table->head[0] = __('Type');
	$table->head[1] = __('Module name');
	$table->head[2] = __('S.L.A.');
	$table->head[3] = __('Status');
	$table->data = array ();
	
	foreach ($result as $sla_data) {
		$data = array ();
		$data[0] = print_moduletype_icon ($modules[$sla_data["id_agent_module"]]["id_tipo_modulo"], true);
		$data[1] = print_string_substr ($modules[$sla_data["id_agent_module"]]["nombre"], 25, true);
		$data[1] .= "(".$sla_data["sla_min"]." / ".$sla_data["sla_max"]." / ".$sla_data["sla_limit"].")";
		$data[2] = format_numeric (get_agentmodule_sla ($sla_data["id_agent_module"], $config["sla_period"], 1)).'%';
		$status = get_agentmodule_status ($sla_data["id_agent_module"]);
		if ($status == 1){
			$data[3] = print_image ("images/pixel_red.png", true, array ("width" => 40, "height" => 18, "title" => __('Module Down')));
		} else {
			$data[3] = print_image ("images/pixel_green.png", true, array ("width" => 40, "height" => 18, "title" => __('Module Up')));
		}
		array_push ($table->data, $data);
	}
	print_table ($table);
	unset ($table);
}
?>
