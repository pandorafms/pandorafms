<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 20012 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Real start

require_once ("../../include/config.php");

// Set root on homedir, as defined in setup
chdir ($config["homedir"]);

session_start ();
ob_start ();
echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n";
echo '<html xmlns="http://www.w3.org/1999/xhtml">'."\n";
echo '<head>';

global $vc_public_view;
$vc_public_view = true;
// This starts the page head. In the call back function,
// things from $page['head'] array will be processed into the head
ob_start ('ui_process_page_head');


require_once('include/functions_gis.php');
require_once($config['homedir'] . "/include/functions_agents.php");

ui_require_javascript_file('openlayers.pandora');

$config["remote_addr"] = $_SERVER['REMOTE_ADDR'];

$hash = get_parameter ('hash');
$idMap = (int) get_parameter ('map_id');
$config["id_user"] = get_parameter ('id_user');

$myhash = md5($config["dbpass"] . $idMap . $config["id_user"]);

// Check input hash
if ( $myhash != $hash) {
	exit;
}


$show_history = get_parameter ('show_history', 'n');

$map = db_get_row ('tgis_map', 'id_tgis_map', $idMap);
$confMap = gis_get_map_conf($idMap);

$num_baselayer=0;
// Initialy there is no Gmap base layer.
$gmap_layer = false;
if ($confMap !== false) {
	foreach ($confMap as $mapC) {
		$baselayers[$num_baselayer]['typeBaseLayer'] = $mapC['connection_type'];
		$baselayers[$num_baselayer]['name'] = $mapC['conection_name'];
		$baselayers[$num_baselayer]['num_zoom_levels'] = $mapC['num_zoom_levels'];
		$decodeJSON = json_decode($mapC['conection_data'], true);
		
		switch ($mapC['connection_type']) {
			case 'OSM':
				$baselayers[$num_baselayer]['url'] = $decodeJSON['url'];
				break;
			case 'Gmap':
				$baselayers[$num_baselayer]['gmap_type'] = $decodeJSON['gmap_type'];
				$baselayers[$num_baselayer]['gmap_key'] = $decodeJSON['gmap_key'];
				$gmap_key = $decodeJSON['gmap_key'];
				// Onece a Gmap base layer is found we mark it to import the API
				$gmap_layer = true;
				break;
			case 'Static_Image':
				$baselayers[$num_baselayer]['url'] = $decodeJSON['url'];
				$baselayers[$num_baselayer]['bb_left'] = $decodeJSON['bb_left'];
				$baselayers[$num_baselayer]['bb_right'] = $decodeJSON['bb_right'];
				$baselayers[$num_baselayer]['bb_bottom'] = $decodeJSON['bb_bottom'];
				$baselayers[$num_baselayer]['bb_top'] = $decodeJSON['bb_top'];
				$baselayers[$num_baselayer]['image_width'] = $decodeJSON['image_width'];
				$baselayers[$num_baselayer]['image_height'] = $decodeJSON['image_height'];
				break;
		}
		$num_baselayer++;
		if ($mapC['default_map_connection'] == 1) {
			$numZoomLevels = $mapC['num_zoom_levels'];
		}
	}
}
if ($gmap_layer === true) {
?>
	<script type="text/javascript" src="http://maps.google.com/maps?file=api&v=2&sensor=false&key=<?php echo $gmap_key ?>" ></script>
<?php
}

$controls = array('PanZoomBar', 'ScaleLine', 'Navigation', 'MousePosition', 'layerSwitcher');

$layers = gis_get_layers($idMap);

echo '<div style="width: 95%; background: white; margin: 20px auto 20px auto; box-shadow: 10px 10px 5px #000;">';
echo "<h1>" . $map['map_name'] . "</h1>";
echo "<br />";

echo "<div id='map' style='z-index:100; width: 99%; height: 500px; min-height:500px; border: 1px solid black;' ></div>";

echo "</div>";

gis_print_map('map', $map['zoom_level'], $map['initial_latitude'],
	$map['initial_longitude'], $baselayers, $controls);

if ($layers != false) {
	foreach ($layers as $layer) {
		gis_make_layer($layer['layer_name'],
			$layer['view_layer'], null, $layer['id_tmap_layer'], 1, $idMap);
		
		// calling agents_get_group_agents with none to obtain the names in the same case as they are in the DB.
		$agentNamesByGroup = array();
		if ($layer['tgrupo_id_grupo'] >= 0) {
			$agentNamesByGroup = agents_get_group_agents($layer['tgrupo_id_grupo'],
				false, 'none', true, true, false);
		}
		$agentNamesByLayer = gis_get_agents_layer($layer['id_tmap_layer'],
			array('nombre'));
		
		
		
		$agentNames = array_unique($agentNamesByGroup + $agentNamesByLayer);
		
		foreach ($agentNames as $agentName) {
			$idAgent = agents_get_agent_id($agentName);
			$coords = gis_get_data_last_position_agent($idAgent);
			
			if ($coords === false) {
				$coords['stored_latitude'] = $map['default_latitude'];
				$coords['stored_longitude'] = $map['default_longitude'];
			}
			else {
				if ($show_history == 'y') {
					$lastPosition = array('longitude' => $coords['stored_longitude'], 'latitude' => $coords['stored_latitude']);
					gis_add_path($layer['layer_name'], $idAgent, $lastPosition);
				}
			}
			
			
			$icon = gis_get_agent_icon_map($idAgent, true);
			$icon_size = getimagesize($icon);
			$icon_width = $icon_size[0];
			$icon_height = $icon_size[1];
			$icon = ui_get_full_url($icon);
			$status = agents_get_status($idAgent);
			$parent = db_get_value('id_parent', 'tagente', 'id_agente', $idAgent);
			
			gis_add_agent_point($layer['layer_name'],
				io_safe_output($agentName), $coords['stored_latitude'],
				$coords['stored_longitude'], $icon, $icon_width,
				$icon_height, $idAgent, $status, 'point_agent_info',
				$parent);
		}
	}
	gis_add_parent_lines();
	
	switch ($config["dbtype"]) {
		case "mysql":
			$timestampLastOperation = db_get_value_sql("SELECT UNIX_TIMESTAMP()");
			break;
		case "postgresql":
			$timestampLastOperation = db_get_value_sql(
				"SELECT ceil(date_part('epoch', CURRENT_TIMESTAMP))");
			break;
		case "oracle":
			$timestampLastOperation = db_get_value_sql(
				"SELECT ceil((sysdate - to_date('19700101000000','YYYYMMDDHH24MISS')) * (86400)) FROM dual");
			break;
	}
	
	gis_activate_select_control();
	gis_activate_ajax_refresh($layers, $timestampLastOperation, 1, $idMap);
}

// Resize GIS map on fullscreen
?>
<script type="text/javascript">
	$().ready(function(){
		
		var new_height = $(document).height();
		$("#map").css("height", new_height - 60);
		
	});
</script>