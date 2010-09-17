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

check_login();

require_once('include/functions_modules.php');

$module_id = get_parameter_get ("id", 0);
$period = get_parameter ("period", 86400);
$group = get_agentmodule_group ($module_id);
$agentId = get_parameter("id_agente"); 


if (! give_acl ($config['id_user'], $group, "AR") || $module_id == 0) {
	audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "ACL Violation",
		"Trying to access Agent Data view");
	require ("general/noaccess.php");
	return;
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
	$table->width = "100%";

	$sql_body = sprintf ("FROM tagente_datos_log4x WHERE id_agente_modulo = %d AND utimestamp > %d ORDER BY utimestamp DESC", $module_id, get_system_time () - $period);
	
	$columns = array(
		
		"Timestamp" => array("utimestamp",				"format_timestamp", 	"align" => "center" ),
		"Sev" 		=> array("severity", 				"format_data", 			"align" => "center", "width" => "70px"),
		"Message"	=> array("message", 				"format_verbatim",		"align" => "left", "width" => "45%"),
		"StackTrace" 		=> array("stacktrace",				"format_verbatim", 			"align" => "left", "width" => "50%")
	);

} else if (preg_match ("/string/", $moduletype_name)) {
	$sql_body = sprintf (" FROM tagente_datos_string WHERE id_agente_modulo = %d AND utimestamp > %d ORDER BY utimestamp DESC", $module_id, get_system_time () - $period);
	
	$columns = array(
		"Timestamp"	=> array("utimestamp", 			"format_timestamp", 		"align" => "center"),
		"Data" 		=> array("datos", 				"format_data", 				"align" => "center"),
		"Time" 		=> array("utimestamp", 			"format_time", 				"align" => "center")
	);
} else {
	$sql_body = sprintf (" FROM tagente_datos WHERE id_agente_modulo = %d AND utimestamp > %d ORDER BY utimestamp DESC", $module_id, get_system_time () - $period);
	
	$columns = array(
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

$header_title = __('Received data from')." ".get_agentmodule_agent_name ($module_id)." / ".get_agentmodule_name ($module_id); 
$header_title .= "<br><br>" . __("From the last") . " " . human_time_description ($period);

echo "<h3>".$header_title. "</h3>";

echo "<form method='post' action='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=" . $agentId . "&tab=data_view&id=" . $module_id . "'>";
echo __("Choose a time from now") . ": ";
$intervals = array ();
$intervals[3600] = human_time_description_raw (3600); // 1 hour
$intervals[86400] = human_time_description_raw (86400); // 1 day 
$intervals[604800] = human_time_description_raw (604800); // 1 week
$intervals[2592000] = human_time_description_raw (2592000); // 1 month
echo print_extended_select_for_time ($intervals, 'period', $period, 'this.form.submit();', '', '0', 10) . __(" seconds.");
echo "</form><br />";

//
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
}
else {
	pagination($count);
	print_table ($table);
	unset ($table);
}

?>
