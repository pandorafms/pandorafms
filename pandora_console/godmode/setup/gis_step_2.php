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

$action = get_parameter('action', 'create_connection_map');

if (is_ajax ()) {
}

echo '<form action="index.php?sec=gsetup&sec2=godmode/setup/gis_step_2" method="post">';

switch ($action) {
	case 'create_connection_map':
		echo "<h2>".__('Pandora Setup')." &raquo; ";
		echo __('Create new map connection')."</h2>";
		$mapConnection_name = '';
		$mapConnection_group = '';
		$mapConnection_numLevelsZoom = '16';
		$mapConnection_defaultZoom = '19';
		$mapConnection_type = 0;
		$mapConnection_defaultLatitude = '40.42056';
		$mapConnection_defaultLongitude = '-3.708187';
		$mapConnection_defaultAltitude = '0';
		$mapConnection_centerLatitude = '40.42056';
		$mapConnection_centerLongitude = '-3.708187';
		$mapConnection_centerAltitude = '0';
		$mapConnectionData = null;
		
		print_input_hidden('action', 'save_map_connection');
		break;
	case 'edit_connection_map':
		echo "<h2>".__('Pandora Setup')." &raquo; ";
		echo __('Edit map connection')."</h2>";
		
		$idConnectionMap = get_parameter('id_connection_map');
		$mapConnection = get_db_row_sql('SELECT * FROM tgis_map_connection WHERE id_tmap_connection = ' . $idConnectionMap);
		
		$mapConnection_name = $mapConnection['conection_name'];
		$mapConnection_group = $mapConnection['group_id'];
		$mapConnection_numLevelsZoom = $mapConnection['num_zoom_levels'];
		$mapConnection_defaultZoom = $mapConnection['default_zoom_level'];
		$mapConnection_type = $mapConnection['connection_type'];
		$mapConnection_defaultLatitude = $mapConnection['default_latitude'];
		$mapConnection_defaultLongitude = $mapConnection['default_longitude'];
		$mapConnection_defaultAltitude = $mapConnection['default_altitude'];
		$mapConnection_centerLatitude = $mapConnection['initial_latitude'];
		$mapConnection_centerLongitude = $mapConnection['initial_longitude'];
		$mapConnection_centerAltitude = $mapConnection['initial_altitude'];
		$mapConnectionData = json_decode($mapConnection['conection_data'], true);
		
		print_input_hidden('id_connection_map', $idConnectionMap);
		print_input_hidden('action', 'save_edit_map_connection');
		break;
	case 'save_map_connection':
	case 'save_edit_map_connection':
		$mapConnection_name = get_parameter('name');
		$mapConnection_group = get_parameter('group');
		$mapConnection_numLevelsZoom = get_parameter('num_levels_zoom');
		$mapConnection_defaultZoom = get_parameter('initial_zoom');
		$mapConnection_type = get_parameter('type');
		$mapConnection_defaultLatitude = get_parameter('default_latitude');
		$mapConnection_defaultLongitude = get_parameter('default_longitude');
		$mapConnection_defaultAltitude = get_parameter('default_altitude');
		$mapConnection_centerLatitude = get_parameter('center_latitude');
		$mapConnection_centerLongitude = get_parameter('center_longitude');
		$mapConnection_centerAltitude = get_parameter('center_altitude');
		
		$idConnectionMap = get_parameter('id_connection_map', null);
		
		switch ($mapConnection_type) {
			case 'OSM':
				$mapConnection_OSM_url = get_parameter('url');
				$mapConnectionData = array('type' => 'OSM',
					'url' => $mapConnection_OSM_url);
				break;
		}
		
		//TODO VALIDATE PARAMETERS
		
		saveMapConnection($mapConnection_name, $mapConnection_group,
			$mapConnection_numLevelsZoom, $mapConnection_defaultZoom,
			$mapConnection_defaultLatitude, $mapConnection_defaultLongitude,
			$mapConnection_defaultAltitude, $mapConnection_centerLatitude,
			$mapConnection_centerLongitude, $mapConnection_centerAltitude,
			$mapConnectionData, $idConnectionMap);
			
		require_once('gis.php');
		return;
		break;
}

$table->width = '90%';

//$table->colspan[0][1] = 3;
//$table->colspan[1][2] = 2;

$table->data = array();
$table->data[0][0] = __('Name') . ":";
$table->data[0][1] = print_input_text ('name', $mapConnection_name, '', 30, 60, true);
$table->data[0][2] = __("Default position for not GIS data element");

$table->data[1][0] = __("Group") . ":";
$table->data[1][1] = print_select_from_sql('SELECT id_grupo, nombre FROM tgrupo', 'group', $mapConnection_group, '', '', '0', true);
$table->data[1][2] = __('Latitude') . ":";
$table->data[1][3] = print_input_text ('default_latitude', $mapConnection_defaultLatitude, '', 4, 10, true);

$table->data[2][0] = __('Num levels zoom') . ":";
$table->data[2][1] = print_input_text ('num_levels_zoom', $mapConnection_numLevelsZoom, '', 4, 10, true);
$table->data[2][2] = __('Longitude') . ":";
$table->data[2][3] = print_input_text ('default_longitude', $mapConnection_defaultLongitude, '', 4, 10, true);


$table->data[3][0] = __('Default zoom level') . ":";
$table->data[3][1] = print_input_text ('initial_zoom', $mapConnection_defaultZoom, '', 4, 10, true);
$table->data[3][2] = __('Altitude') . ":";
$table->data[3][3] = print_input_text ('default_altitude', $mapConnection_defaultAltitude, '', 4, 10, true);

echo "<h3>" . __('Basic configuration') . "</h3>";
print_table($table);

$table->width = '60%';
$table->data = array();
$types[0] = __('Please select the type');
$types["OSM"] = __('Open Street Maps');
$table->data[0][0] = __('Type') . ":";
$table->data[0][1] = print_select($types, 'sel_type', $mapConnection_type, "selMapConnectionType();", '', 0, true);

echo "<h3>" . __('Maps connection type') . "</h3>";
print_table ($table);

$optionsConnectionTypeTable = '';
$mapConnectionDataUrl = '';

if ($mapConnectionData != null) {
	switch ($mapConnection_type) {
		case 'OSM':
			$mapConnectionDataUrl = $mapConnectionData['url'];
			break;
	}
}

$optionsConnectionOSMTable = '<table class="databox" border="0" cellpadding="4" cellspacing="4" width="50%">' .
		'<tr class="row_0">' .
			'<td>'  . __("URL") . ':</td>' .
			'<td><input id="type" type="hidden" name="type" value="OSM" />' . print_input_text ('url', $mapConnectionDataUrl, '', 45, 90, true) . '</td>' .
		'</tr>' . 
	'</table>';

if ($mapConnectionData != null) {
	switch ($mapConnection_type) {
		case 'OSM':
			$optionsConnectionTypeTable = $optionsConnectionOSMTable;
			break;
	}
}

echo "<div id='form_map_connection_type'>" . $optionsConnectionTypeTable . "</div>";

echo "<h3>" . __('Preview and Select the center') . "</h3>";
echo "<p>" . __("For test the conf and set the center of map.") . "</p>";
echo "<a href='javascript: refreshMapView();'>" . __("Refresh the map view") . "</a>";
echo "<div id='map' style='width: 300px; height: 300px; border: 1px solid black;'></div>";

$table->width = '30%';
$table->data = array();

$table->colspan[0][0] = 2;
$table->data[0][0] = __('Center map connection');

$table->data[1][2] = __('Latitude') . ":";
$table->data[1][3] = print_input_text ('center_latitude', $mapConnection_centerLatitude, '', 4, 10, true);

$table->data[2][2] = __('Longitude') . ":";
$table->data[2][3] = print_input_text ('center_longitude', $mapConnection_centerLongitude, '', 4, 10, true);

$table->data[3][2] = __('Altitude') . ":";
$table->data[3][3] = print_input_text ('center_altitude', $mapConnection_centerAltitude, '', 4, 10, true);
print_table($table);

echo '<div class="action-buttons" style="width: '.$table->width.'; float: left;">';
print_submit_button (__('Save'), '', false, 'class="sub save"');
echo '</div>';
echo "</form>";
?>
<script type="text/javascript">
var mapConfig = false;

function refreshMapView() {
	if (mapConfig) {
	}
	else
		alert('<?php echo __('Please conf '); ?>');
}

function selMapConnectionType() {
	switch ($('#sel_type :selected').val()) {
		case 'OSM':
			$('#form_map_connection_type').html('<?php echo $optionsConnectionOSMTable; ?>');
			break; 
		default:
			$('#form_map_connection_type').html('');
			break;
	}
}
</script>