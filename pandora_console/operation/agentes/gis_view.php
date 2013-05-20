<?php
/**
 * Pandora FMS- http://pandorafms.com
 * ==================================================
 * Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

// Load global vars
global $config;

check_login ();

if (! check_acl ($config['id_user'], 0, "AR") && ! is_user_admin ($config['id_user'])) {
	db_pandora_audit("ACL Violation", "Trying to access GIS Agent view");
	require ("general/noaccess.php");
	return;
}

require_once ('include/functions_gis.php');
require_once ('include/functions_html.php');
require_once ($config['homedir'].'/include/functions_agents.php');

ui_require_javascript_file('openlayers.pandora');

/* Get the parameters */
$period = get_parameter ("period", 86400);
$agentId = get_parameter('id_agente');
$agent_name = agents_get_name($agentId); 
$agentData = gis_get_data_last_position_agent($id_agente);

//Avoid the agents with characters that fails the div.
$agent_name = md5($agent_name);

$url = '';
//These variables come from index.php
foreach ($_GET as $key => $value) {
	$url .= '&amp;' . safe_url_extraclean($key) . '=' . safe_url_extraclean($value);
}

echo "<div style='margin-bottom: 30px;'></div>";

/* Map with the current position */
echo "<div id=\"" . $agent_name . "_agent_map\" style=\"border:1px solid black; width:98%; height: 39em;\"></div>";
if (!gis_get_agent_map($agentId, "500px", "98%", true, true, $period)) {
	echo "<br /><div class='nf'>" . __("There is no default map.") . "</div>";
}

switch ($config["dbtype"]) {
	case "mysql":
		$timestampLastOperation = db_get_value_sql(
			"SELECT UNIX_TIMESTAMP()");
		break;
	case "postgresql":
		$timestampLastOperation = db_get_value_sql(
			"SELECT ceil(date_part('epoch', CURRENT_TIMESTAMP))");
		break;
	case "oracle":
		$timestampLastOperation = db_get_value_sql(
			"SELECT ceil((sysdate - to_date('19700101000000','YYYYMMDDHH24MISS')) * (86400)) from dual");
		break;
}

gis_activate_ajax_refresh(null, $timestampLastOperation);
gis_activate_select_control();

if ($agentData === false) {
	echo "<p>" .
		__("There is no GIS data for this agent, so it's positioned in default position of map.") .
		"</p>";
}

echo "<br />";
$dataLastPosition = gis_get_data_last_position_agent($agentId);
if ($dataLastPosition !== false) {
	echo "<b>" . __("Last position in ") . $dataLastPosition['start_timestamp'] . ":</b> " .
		$dataLastPosition['stored_longitude'] . ", " . $dataLastPosition['stored_latitude'] . ", " . $dataLastPosition['stored_altitude'];
}
echo "<br />";
echo "<form action='index.php?" . $url . "' method='POST'>";
echo __("Period to show data as path") . ": ";
html_print_extended_select_for_time ('period', $period, '', '', '0', 10);
html_print_submit_button(__('Refresh path'), 'refresh', false, 'class = "sub upd"');
echo "</form>";

echo "<h4>" . __("Positional data from the last") . " " . human_time_description_raw ($period) ."</h4>";
/* Get the total number of Elements for the pagination */ 
$sqlCount = sprintf ("SELECT COUNT(*)
	FROM tgis_data_history
	WHERE tagente_id_agente = %d AND end_timestamp > FROM_UNIXTIME(%d)
	ORDER BY end_timestamp DESC", $agentId, get_system_time () - $period);
$countData = db_get_value_sql($sqlCount);


/* Get the elements to present in this page */
$sql = sprintf ("
	SELECT longitude, latitude, altitude, start_timestamp,
		end_timestamp, description, number_of_packages, manual_placement
	FROM tgis_data_history
	WHERE tagente_id_agente = %d AND end_timestamp > FROM_UNIXTIME(%d)  
	ORDER BY end_timestamp DESC
	LIMIT %d OFFSET %d", $agentId, get_system_time () - $period, $config['block_size'], get_parameter ('offset'));
$result = db_get_all_rows_sql ($sql, true);


if ($result === false) {
	echo "<div class='nf'>" .
		__('This agent doesn\'t have any GIS data.') . "</div>";
}
else {
	ui_pagination ($countData, false) ;
	$table->data = array();
	foreach ($result as $row) {
		$rowdata = array($row['longitude'], $row['latitude'], $row['altitude'], $row['start_timestamp'], $row['end_timestamp'], $row['description'], $row['number_of_packages'], $row['manual_placement']);
	array_push($table->data, $rowdata); 
	}
	$table->head = array(__("Longitude"), __("Latitude"), __("Altitude"), __("From"), __("To"), __("Description"), '# '.__("of Packages"), __("Manual placement"));
	$table->class = 'position_data_table';
	$table->id = $agent_name.'_position_data_table';
	$table->title = $agent_name." ". __("positional data");
	$table->titlestyle = "background-color:#799E48;";
	html_print_table($table); unset($table);
	
	ui_pagination ($countData, false) ;
	echo "<h3>" . __('Total') . ' ' . $countData . ' ' . __('Data') . "</h3>";
}
?>