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

check_login ();

require_once ('include/functions_gis.php');

$idMap = (int) get_parameter ('map_id');
$show_history = get_parameter ('show_history', 'n');

$map = get_db_row ('tgis_map', 'id_tgis_map', $idMap);
$confMap = getMapConf($idMap);

$num_baselayer=0;

if ($confMap !== false) {
	foreach ($confMap as $mapC) {
		$baselayers[$num_baselayer]['typeBaseLayer'] = $mapC['connection_type'];
		$baselayers[$num_baselayer]['name'] = $mapC['conection_name'];
		$decodeJSON = json_decode($mapC['conection_data'], true);
		$baselayers[$num_baselayer]['url'] = $decodeJSON['url'];
		$num_baselayer++;
		if ($mapC['default_map_connection'] == 1) {
			$numZoomLevels = $mapC['num_zoom_levels'];
		}
	}
}

$controls = array('PanZoom', 'ScaleLine', 'Navigation', 'MousePosition', 'OverviewMap');

$layers = getLayers($idMap);

// Render map
echo "<h2>".__('Visual console')." &raquo; ".__('Map');
echo "&nbsp;" . $map['map_name'] . "&nbsp;&nbsp;";

if ($config["pure"] == 0) {
	echo '<a href="index.php?sec=visualc&amp;sec2=operation/gis_maps/render_view&amp;map_id='.$idMap.'&amp;refr='.$config["refr"].'&amp;pure=1">';
	print_image ("images/fullscreen.png", false, array ("title" => __('Full screen mode')));
	echo "</a>";
} else {
	echo '<a href="index.php?sec=visualc&amp;sec2=operation/gis_maps/render_view&amp;map_id='.$idMap.'&amp;refr='.$config["refr"].'">';
	print_image ("images/normalscreen.png", false, array ("title" => __('Back to normal mode')));
	echo "</a>";
}

echo "&nbsp;";

if (give_acl ($config["id_user"], $map['group_id'], "AW"))
	echo '<a href="index.php?sec=gmap&amp;sec2=godmode/reporting/map_builder&amp;map_id='.$idMap.'">'.print_image ("images/setup.png", true, array ("title" => __('Setup'))).'</a>';
echo "</h2>";

printMap('map', $map['zoom_level'], $numZoomLevels, $map['initial_latitude'],
	$map['initial_longitude'], $baselayers, $controls);
	
if ($layers != false) {
	foreach ($layers as $layer) {
		makeLayer($layer['layer_name'], $layer['view_layer']);
		
		// calling get_group_agents with none to obtain the names in the same case as they are in the DB.	
		$agentNames = get_group_agents($layer['tgrupo_id_grupo'],false,'none');
		foreach ($agentNames as $agentName) {
			$idAgent = get_agent_id($agentName);
			$coords = get_agent_last_coords($idAgent);
			
			if ($coords['last_latitude'] == null)
				continue;
			
			$icon = get_agent_icon_map($idAgent, true);
			
			if ($show_history == 'y') { 	
				addPath($layer['layer_name'], $idAgent);
			}
			addPoint($layer['layer_name'], $agentName, $coords['last_latitude'], $coords['last_longitude'], $icon, 20, 20, $idAgent, 'point_agent_info');
		}
	}
	
	$timestampLastOperation = get_db_value_sql("SELECT UNIX_TIMESTAMP()");
	
	activateSelectControl();
	activateAjaxRefresh($layers, $timestampLastOperation);
}

?>
<br /><br />
<?php 
if ($config["pure"] == 0) {
	echo "<div id='map' style='width: 99%; height: 400px; border: 1px solid black;' ></div>";
}
else {
	echo "<div id='map' style='position:absolute;top:40px; z-index:100; width: 98%; height:94%; border: 1px solid black;' ></div>";
}