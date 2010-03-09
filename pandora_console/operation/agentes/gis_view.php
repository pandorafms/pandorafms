<?php
/**
 * Pandora FMS- http://pandorafms.com
 * ==================================================
 * Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
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

if (! give_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user'])) {
	audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "ACL Violation", "Trying to access Visual Setup Management");
	require ("general/noaccess.php");
	return;
}

require_once ('include/functions_gis.php');
require_once ('include/functions_html.php');

require_javascript_file('openlayers.pandora');

/* Get the parameters */
$period = get_parameter ("period", 86400);
$agentId = get_parameter('id_agente');
$agent_name = get_agent_name($agentId); 
$agentData = getDataLastPositionAgent($id_agente);

echo "<div style='margin-bottom: 30px;'></div>";

/* Map with the current position */
echo "<div id=\"".$agent_name."_agent_map\"  style=\"border:1px solid black; width:98%; height: 39em;\"></div>";
if (!getAgentMap($agentId, "500px", "98%", true, true, $period)) {
	echo "<br /><div class='nf'>" . __("There is no default map.") . "</div>";
} 

$timestampLastOperation = get_db_value_sql("SELECT UNIX_TIMESTAMP()");

activateAjaxRefresh(null, $timestampLastOperation);
activateSelectControl();

if ($agentData === false) {
	echo "<p>" . __("There is no GIS data for this agent, so it's positioned in default position of map.") . "</p>";
}

$intervals = array ();
$intervals[30] = human_time_description_raw (30);
$intervals[60] = human_time_description_raw (60);
$intervals[300] = human_time_description_raw (300);
$intervals[600] = human_time_description_raw (600);
$intervals[1200] = human_time_description_raw (1200);
$intervals[1800] = human_time_description_raw (1800);
$intervals[3600] = human_time_description_raw (3600);
$intervals[7200] = human_time_description_raw (7200);
$intervals[86400] = human_time_description_raw (86400);
$intervals[172800] = human_time_description_raw (172800);
$intervals[604800] = human_time_description_raw (604800);

echo "<br />";
$dataLastPosition = getDataLastPositionAgent($agentId);
if ($dataLastPosition !== false) {
	echo "<b>" . __("Last position in ") . $dataLastPosition['start_timestamp'] . ":</b> " .
		$dataLastPosition['stored_longitude'] . ", " . $dataLastPosition['stored_latitude'] . ", " . $dataLastPosition['stored_altitude'];
}
echo "<br />";
echo "<form action='index.php?" . $_SERVER['QUERY_STRING'] . "' method='POST'>";
echo __("Period to show data as path") . ": ";
print_extended_select_for_time ($intervals, 'period', $period, '', '', '0', 10);
echo  __(" seconds.") . "&nbsp;";
print_submit_button(__('Refresh path'), 'refresh', false, 'class = "sub upd"');
echo "</form>";

echo "<h3>" . __("Positional data from the last") . " " . human_time_description ($period) ."</h3>";
/* Get the total number of Elements for the pagination */ 
$sqlCount = sprintf ("SELECT COUNT(*) FROM tgis_data_history WHERE tagente_id_agente = %d AND end_timestamp > %d ORDER BY end_timestamp DESC", $agentId, get_system_time () - $period);
$countData = get_db_value_sql($sqlCount);
/* Get the elements to present in this page */
$sql = sprintf ("SELECT longitude, latitude, altitude, start_timestamp, end_timestamp, description, number_of_packages, manual_placement
        FROM tgis_data_history
        WHERE tagente_id_agente = %d AND end_timestamp > %d 
        ORDER BY end_timestamp DESC
        LIMIT %d OFFSET %d", $agentId, get_system_time () - $period, $config['block_size'], get_parameter ('offset'));

$result = get_db_all_rows_sql ($sql, true);

if ($result === false) {
	echo "<div class='nf'>".__('This agent doesn\'t have any GIS data')."</div>";
}
else {
    pagination ($countData, false) ;
	$table->data = array();
	foreach ($result as $row) {
		$rowdata = array($row['longitude'], $row['latitude'], $row['altitude'], $row['start_timestamp'], $row['end_timestamp'], $row['description'], $row['number_of_packages'], $row['manual_placement']);
    	array_push($table->data, $rowdata); 
	}
	$table->head = array(__("Longitude"), __("Latitude"), __("Altitude"), __("From"), __("To"), __("Description"), '# '.__("of Packages"), __("Manual placement"));
	$table->class = 'position_data_table';
	$table->id = $agent_name.'_position_data_table';
	$table->title = $agent_name." ". __("positional data");
	print_table($table); unset($table);

	pagination ($countData, false) ;
	echo "<h3>" . __('Total') . ' ' . $countData . ' ' . __('Data') . "</h3>";
}


?>
