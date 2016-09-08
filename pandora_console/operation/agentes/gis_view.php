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

if (! check_acl ($config['id_user'], 0, "AR") && ! check_acl ($config['id_user'], 0, "AW") && ! is_user_admin ($config['id_user'])) {
	db_pandora_audit("ACL Violation", "Trying to access GIS Agent view");
	require ("general/noaccess.php");
	return;
}

require_once ('include/functions_gis.php');
require_once ('include/functions_html.php');
require_once ($config['homedir'].'/include/functions_agents.php');

ui_require_javascript_file('openlayers.pandora');

/* Get the parameters */
$period = (int)get_parameter ("period", SECONDS_1DAY);
$agentId = (int)get_parameter('id_agente');
$agent_name = agents_get_name($id_agente);
$agentData = gis_get_data_last_position_agent($id_agente);

//Avoid the agents with characters that fails the div.
$agent_name_original = $agent_name;
$agent_name = md5($agent_name);

$url = '';
//These variables come from index.php
foreach ($_GET as $key => $value) {
	$url .= '&amp;' . safe_url_extraclean($key) . '=' . safe_url_extraclean($value);
}

echo "<div style='margin-bottom: 30px;'></div>";

/* Map with the current position */
echo "<div id=\"" . $agent_name . "_agent_map\" style=\"border:1px solid black; width:100%; height: 30em;\"></div>";

if (!gis_get_agent_map($id_agente, "500px", "100%", true, true, $period)) {
	ui_print_error_message( __("There is no default map. Please go to the setup for to set a default map.") );
	echo "<script type='text/javascript'>
		$(document).ready(function() {
			$('#" . $agent_name . "_agent_map').hide();
		});
		</script>";
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
			"SELECT ceil((sysdate - to_date('19700101000000','YYYYMMDDHH24MISS')) * (" . SECONDS_1DAY . ")) from dual");
		break;
}

gis_activate_ajax_refresh(null, $timestampLastOperation);
gis_activate_select_control();

if ($agentData === false) {
	ui_print_info_message (
		__("There is no GIS data for this agent, so it's positioned in default position of map.") );
}

$dataLastPosition = gis_get_data_last_position_agent($agentId);
if ($dataLastPosition !== false) {
	echo "<b>" . __("Last position in ") .
		$dataLastPosition['start_timestamp'] . ":</b> " .
		$dataLastPosition['stored_longitude'] . ", " . $dataLastPosition['stored_latitude'] . ", " . $dataLastPosition['stored_altitude'];
}

echo "<form class='' action='index.php?" . $url . "' method='POST'>";
echo "<table width=100% class='databox filters'>";
echo "<tr><td>" . __("Period to show data as path") . ": ";
echo "<td>";
html_print_extended_select_for_time ('period', $period, '', '', '0', 10);
echo "<td>";
html_print_submit_button(__('Refresh path'), 'refresh', false, 'class = "sub upd" style="margin-top:0px"');
echo "</table></form>";

echo "<h4>" . __("Positional data from the last") . " " . human_time_description_raw ($period) ."</h4>";
/* Get the total number of Elements for the pagination */
$sqlCount = sprintf ("SELECT COUNT(*)
	FROM tgis_data_history
	WHERE tagente_id_agente = %d AND end_timestamp > FROM_UNIXTIME(%d)
	ORDER BY end_timestamp DESC", $agentId, get_system_time () - $period);
$countData = db_get_value_sql($sqlCount);


/* Get the elements to present in this page */
switch ($config["dbtype"]) {
	case "mysql":
		$sql = sprintf ("
			SELECT longitude, latitude, altitude, start_timestamp,
				end_timestamp, description, number_of_packages, manual_placement
			FROM tgis_data_history
			WHERE tagente_id_agente = %d AND end_timestamp > FROM_UNIXTIME(%d)
			ORDER BY end_timestamp DESC
			LIMIT %d OFFSET %d", $agentId, get_system_time () - $period, $config['block_size'], (int)get_parameter ('offset'));
		break;
	case "postgresql":
	case "oracle":
		$set = array ();
		$set['limit'] = $config['block_size'];
		$set['offset'] = (int)get_parameter ('offset');
		$sql = sprintf ("
			SELECT longitude, latitude, altitude, start_timestamp,
				end_timestamp, description, number_of_packages, manual_placement
			FROM tgis_data_history
			WHERE tagente_id_agente = %d AND end_timestamp > FROM_UNIXTIME(%d)
			ORDER BY end_timestamp DESC", $agentId, get_system_time () - $period);
		$sql = oracle_recode_query ($sql, $set);
		break;
}

$result = db_get_all_rows_sql ($sql, true);


if ($result === false) {
	ui_print_empty_data( __('This agent doesn\'t have any GIS data.') );
}
else {
	ui_pagination ($countData, false) ;
	$table->data = array();
	foreach ($result as $key => $row) {
		$distance = 0;
		if (isset($result[$key - 1])) {
			$distance = gis_calculate_distance($row['latitude'],
				$row['longitude'], $result[$key - 1]['latitude'],
				$result[$key - 1]['longitude']);
		}
		else {
			$dataLastPosition = gis_get_data_last_position_agent($agentId);
			if ($dataLastPosition !== false) {
				$distance = gis_calculate_distance($row['latitude'],
					$row['longitude'], $dataLastPosition['stored_latitude'],
					$dataLastPosition['stored_longitude']);
			}
		}

		$rowdata = array(
			$row['longitude'],
			$row['latitude'],
			$row['altitude'],
			$row['start_timestamp'],
			$row['end_timestamp'],
			$row['description'],
			sprintf(__('%s Km'), $distance),
			$row['number_of_packages'],
			$row['manual_placement']);
	array_push($table->data, $rowdata);
	}
	$table->head = array(
		__("Longitude"),
		__("Latitude"),
		__("Altitude"),
		__("From"),
		__("To"),
		__("Description"),
		__('Distance'),
		__("# of Packages"),
		__("Manual placement"));
	$table->class = 'position_data_table';
	$table->id = $agent_name.'_position_data_table';
	$table->title = $agent_name_original . " " . __("positional data");
	$table->titlestyle = "background-color:#799E48;";
	html_print_table($table); unset($table);

	ui_pagination ($countData, false) ;
	echo "<h3>" . __('Total') . ' ' . $countData . ' ' . __('Data') . "</h3>";
}
?>
