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
global $config;

check_login();

$module_id = get_parameter_get ("id", 0);
$period = get_parameter_get ("period", 86400);
$group = get_agentmodule_group ($module_id);

if (! give_acl ($config['id_user'], $group, "AR") || $module_id == 0) {
	audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "ACL Violation",
		"Trying to access Agent Data view");
	require ("general/noaccess.php");
	return;
}

if (isset ($_GET["delete"])) {
	$delete = get_parameter_get ("delete", 0);
	$sql = sprintf ("DELETE FROM tagente_datos WHERE id_agente_datos = %d", $delete);
	process_sql ($sql);
} elseif (isset($_GET["delete_log4x"])) {
	$delete = get_parameter_get ("delete_log4x", 0);
	$sql = sprintf ("DELETE FROM tagente_datos_log4x WHERE id_tagente_datos_log4x = %d", $delete);
	process_sql ($sql);
} elseif (isset($_GET["delete_string"])) {
	$delete = get_parameter_get ("delete_string", 0);
	$sql = sprintf ("DELETE FROM tagente_datos_string WHERE id_tagente_datos_string = %d", $delete);
	process_sql ($sql);
}

$table->cellpadding = 3;
$table->cellspacing = 3;
$table->width = 600;
$table->class = "databox";
$table->head = array ();
$table->data = array ();
$table->align = array ();
$table->size = array ();


$moduletype_name = get_moduletype_name (get_agentmodule_type ($module_id));

$offset = (int) get_parameter("offset");
$block_size = (int) $config["block_size"];

// The "columns" array is the number(and definition) of columns in the report:
// $columns = array(
//		"COLUMN1" => array(ROW_FROM_DB_TABLE, FUNCTION_NAME_TO_FORMAT_THE_DATA, "align"=>COLUMN_ALIGNMENT, "width"=>COLUMN_WIDTH)
//		"COLUMN2" => array(ROW_FROM_DB_TABLE, FUNCTION_NAME_TO_FORMAT_THE_DATA, "align"=>COLUMN_ALIGNMENT, "width"=>COLUMN_WIDTH)
//		....
// )
//
// For each row from the query, and for each column, we'll call the FUNCTION passing as argument
// the value of the ROW.
//
$columns = array ();

if ($moduletype_name == "log4x") {
	$table->width = 1100;

	$sql_body = sprintf ("FROM tagente_datos_log4x WHERE id_agente_modulo = %d AND utimestamp > %d ORDER BY utimestamp DESC", $module_id, get_system_time () - $period);
	
	$columns = array(
		"Delete" 	=> array("id_tagente_datos_log4x", 	"format_delete_log4x", 	"align" => "center", "width" => "10px"),
		"Timestamp" => array("utimestamp",				"format_timestamp", 	"align" => "center", "width" => "200px"),
		"Sev" 		=> array("severity", 				"format_data", 			"align" => "center", "width" => "70px"),
		"Message"	=> array("message", 				"format_verbatim",		"align" => "left", "width" => "400px"),
		"ST" 		=> array("stacktrace",				"format_data", 			"align" => "left", "width" => "400px")
	);

} else if (preg_match ("/string/", $moduletype_name)) {
	$sql_body = sprintf (" FROM tagente_datos_string WHERE id_agente_modulo = %d AND utimestamp > %d ORDER BY utimestamp DESC", $module_id, get_system_time () - $period);
	
	$columns = array(
		"Delete" 	=> array("id_agente_datos", 	"format_delete_string", 	"align" => "center"),
		"Timestamp"	=> array("utimestamp", 			"format_timestamp", 		"align" => "center"),
		"Data" 		=> array("datos", 				"format_data", 				"align" => "center"),
		"Time" 		=> array("utimestamp", 			"format_time", 				"align" => "center")
	);
} else {
	$sql_body = sprintf (" FROM tagente_datos WHERE id_agente_modulo = %d AND utimestamp > %d ORDER BY utimestamp DESC", $module_id, get_system_time () - $period);
	
	$columns = array(
		"Delete" 	=> array("id_agente_datos", 	"format_delete", 		"align" => "center"),
		"Timestamp"	=> array("utimestamp", 			"format_timestamp", 	"align" => "center"),
		"Data" 		=> array("datos", 				"format_data", 			"align" => "center"),
		"Time" 		=> array("utimestamp", 			"format_time", 			"align" => "center")
	);
}

$sql = "SELECT * " . $sql_body;
$sql_count = "SELECT count(*) " . $sql_body;

$count = get_db_value_sql($sql_count);

$sql .= " LIMIT " . $offset . "," . $block_size;

$result = get_db_all_rows_sql ($sql);
if ($result === false) {
	$result = array ();
}

echo "<h2>".__('Received data from')." ".get_agentmodule_agent_name ($module_id)." / ".get_agentmodule_name ($module_id)." </h2>";
echo "<h3>".human_time_description ($period) ."</h3>";

$index = 0;
foreach($columns as $col => $attr){
	$table->head[$index] = $col;
	
	if (isset($attr["align"]))
		$table->align[$index] = $attr["align"];
	
	if (isset($attr["width"]))
		$table->size[$index] = $attr["width"];

	$index++;
}

foreach ($result as $row) {
	$data = array ();

	foreach($columns as $col => $attr){
		$data[] = $attr[1] ($row[$attr[0]]);
	}

	array_push ($table->data, $data);
	if (count($table->data) > 200) break;
}

if (empty ($table->data)) {
	echo '<h3 class="error">'.__('There was a problem locating the source of the graph').'</h3>';
} else {
	pagination($count);
	print_table ($table);
	unset ($table);
}

//
// This are functions to format the data
//

function format_time($ts)
{
	return print_timestamp ($ts, true, array("prominent" => "comparation"));
}

function format_data($data)
{
	if (is_numeric ($data)) {
		$data = format_numeric($data, 2);
	} else {
		$data = safe_input ($data);
	}
	return $data;
}

function format_verbatim($data)
{
	return "<pre style='font-size:8px;'>" . $data . "</pre>";
}

function format_timestamp($ts)
{
	global $config;

	// This returns data with absolute user-defined timestamp format
	// and numeric by data managed with 2 decimals, and not using Graph format 
	// (replacing 1000 by K and 1000000 by G, like version 2.x
	return date ($config["date_format"], $ts);
}

function format_delete($id)
{
	global $period, $module_id, $config, $group;

	$txt = "";

	if (give_acl ($config['id_user'], $group, "AW") ==1) {
		$txt = '<a href="index.php?sec=estado&sec2=operation/agentes/datos_agente&period='.$period.'&id='.$module_id.'&delete='.$id.'"><img src="images/cross.png" border="0" /></a>';
	}
	return $txt;
}

function format_delete_string($id)
{
	global $period, $module_id, $config, $group;

	$txt = "";

	if (give_acl ($config['id_user'], $group, "AW") ==1) {
		$txt = '<a href="index.php?sec=estado&sec2=operation/agentes/datos_agente&period='.$period.'&id='.$module_id.'&delete_string='.$id.'"><img src="images/cross.png" border="0" /></a>';
	}
	return $txt;
}

function format_delete_log4x($id)
{
	global $period, $module_id, $config, $group;

	$txt = "";

	if (give_acl ($config['id_user'], $group, "AW") ==1) {
		$txt = '<a href="index.php?sec=estado&sec2=operation/agentes/datos_agente&period='.$period.'&id='.$module_id.'&delete_log4x='.$id.'"><img src="images/cross.png" border="0" /></a>';
	}
	return $txt;
}

?>
