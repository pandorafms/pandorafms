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
require_once ("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user'])) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation", "Trying to access Visual Setup Management");
	require ("general/noaccess.php");
	return;
}

require_once ('include/functions_gis.php');
require_once ('include/functions_html.php');

/* Get the parameters */
$period = get_parameter ("period", 86400);
$agentId = get_parameter('id_agente');
$agent_name = get_agent_name($agentId); 

echo "<h2>".__('Received data from')." ". $agent_name . " </h2>";
echo "<h3>" . __("Map with the last position/s") . " " . human_time_description ($period) ."</h3>";

/* Map with the current position */
echo "<div id=\"".$agent_name."_agent_map\"  style=\"border:1px solid black; width:98%; height: 30em;\"></div>";
echo getAgentMap($agentId, "500px", "98%", true);

echo "<h3>" . __("Positional data from the last") . " " . human_time_description ($period) ."</h3>";
/* Get the total number of Elements for the pagination */ 
$sqlCount = sprintf ("SELECT COUNT(*) FROM tgis_data WHERE tagente_id_agente = %d AND end_timestamp > %d ORDER BY end_timestamp DESC", $agentId, get_system_time () - $period);
$countData = get_db_value_sql($sqlCount);
/* Get the elements to present in this page */
$sql = sprintf ("SELECT longitude, latitude, altitude, start_timestamp, end_timestamp, description, number_of_packages, manual_placement
        FROM tgis_data
        WHERE tagente_id_agente = %d AND end_timestamp > %d 
        ORDER BY end_timestamp DESC
        LIMIT %d OFFSET %d", $agentId, get_system_time () - $period, $config['block_size'], get_parameter ('offset'));
$result = get_db_all_rows_sql ($sql, true);

if ($result === false) {

    echo '<h3 class="error">'.__('There was a problem locating the positional data').'</h3>';
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
	print_table(&$table); unset($table);

	pagination ($countData, false) ;
	echo "<h3>" . __('Total') . ' ' . $countData . ' ' . __('Data') . "</h3>";
}


?>
