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

check_login();

$module_id = get_parameter_get ("id", 0);
$period = get_parameter_get ("period", 86400);
$group = get_agentmodule_group ($module_id);

if (! give_acl ($config['id_user'], $group, "AR") || $module_id == 0) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Agent Data view");
	require ("general/noaccess.php");
	return;
}

if (isset ($_GET["delete"])) {
	$delete = get_parameter_get ("delete", 0);
	$sql = sprintf ("DELETE FROM tagente_datos WHERE id_agente_datos = %d", $delete);
	process_sql ($sql);
} elseif (isset($_GET["delete_text"])) {
	$delete = get_parameter_get ("delete_string", 0);
	$sql = sprintf ("DELETE FROM tagente_datos_string WHERE id_tagente_datos_string = %d", $delete);
	process_sql ($sql);
}

// Different query for string data type
if (preg_match ("/string/", get_moduletype_name (get_agentmodule_type ($module_id)))) {
	$sql = sprintf ("SELECT * FROM tagente_datos_string WHERE id_agente_modulo = %d AND utimestamp > %d ORDER BY utimestamp DESC", $module_id, get_system_time () - $period);
	$string_type = 1;
} else {
	$sql = sprintf ("SELECT * FROM tagente_datos WHERE id_agente_modulo = %d AND utimestamp > %d ORDER BY utimestamp DESC", $module_id, get_system_time () - $period);
	$string_type = 0;
}
	
$result = get_db_all_rows_sql ($sql);
if ($result === false) {
	$result = array ();
}

echo "<h2>".__('Received data from')." ".get_agentmodule_agent_name ($module_id)." / ".get_agentmodule_name ($module_id)." </h2>";
echo "<h3>".human_time_description ($period) ."</h3>";

$table->cellpadding = 3;
$table->cellspacing = 3;
$table->width = 600;
$table->class = "databox";
$table->head = array ();
$table->data = array ();
$table->align = array ();

$table->head[0] = __('Delete');
$table->align[0] = 'center';

$table->head[1] = __('Timestamp');
$table->align[1] = 'center';
$table->head[2] = __('Data');
$table->align[2] = 'center';

foreach ($result as $row) {
	$data = array ();
	if (give_acl ($config['id_user'], $group, "AW") ==1) {
		if ($string_type == 0) {
			$data[0] = '<a href="index.php?sec=estado&sec2=operation/agentes/datos_agente&period='.$period.'&id='.$module_id.'&delete='.$row["id_agente_datos"].'"><img src="images/cross.png" border="0" /></a>';
		} else {
			$data[0] = '<a href="index.php?sec=estado&sec2=operation/agentes/datos_agente&period='.$period.'&id='.$module_id.'&delete_string='.$row["id_tagente_datos_string"].'"><img src="images/cross.png" border="0" /></a>';
		}
	} else {
		$data[0] = '';
	}
	$data[1] = print_timestamp ($row["utimestamp"], true);
	if (is_numeric ($row["datos"])) {
		$data[2] = $row["datos"];
	} else {
		$data[2] = safe_input ($row["datos"]);
	}
	
	array_push ($table->data, $data);
}

if (empty ($table->data)) {
	echo '<h3 class="error">'.__('There was a problem locating the source of the graph').'</h3>';
} else {
	print_table ($table);
	unset ($table);
}

?>
