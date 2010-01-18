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

$idMap = (int) get_parameter ('id');

$map = get_db_row ('tgis_map', 'id_tgis_map', $idMap);
$confMap = getMapConf($idMap);
$baseLayers = $confMap['baselayer'];
$controls = $confMap['controls'][0];
$numZoomLevels = $confMap['numLevelsZoom'][0];

$layers = getLayers($idMap);

// Render map
echo "<h2>".__('Visual console')." &raquo; ".__('Map');
echo "&nbsp;" . $map['map_name'] . "&nbsp;&nbsp;";

if ($config["pure"] == 0) {
	echo '<a href="index.php?sec=visualc&amp;sec2=operation/gis_maps/render_view&amp;id='.$idMap.'&amp;refr='.$config["refr"].'&amp;pure=1">';
	print_image ("images/fullscreen.png", false, array ("title" => __('Full screen mode')));
	echo "</a>";
} else {
	echo '<a href="index.php?sec=visualc&amp;sec2=operation/gis_maps/render_view&amp;id='.$idMap.'&amp;refr='.$config["refr"].'">';
	print_image ("images/normalscreen.png", false, array ("title" => __('Back to normal mode')));
	echo "</a>";
}

echo "&nbsp;";

if (give_acl ($config["id_user"], $map['group_id'], "AW"))
	echo '<a href="index.php?sec=gmap&amp;sec2=godmode/reporting/map_builder&amp;id_map='.$idMap.'">'.print_image ("images/setup.png", true, array ("title" => __('Setup'))).'</a>';
echo "</h2>";

printMap('map', $map['zoom_level'], $numZoomLevels, $map['initial_latitude'],
	$map['initial_longitude'], array($baseLayers[0]['typeBaseLayer'] => $baseLayers[0]['url']), $controls);
	
if ($layers != false) {
	foreach ($layers as $layer) {
		makeLayer($layer['layer_name'], $layer['view_layer']);
		
		$agentNames = get_group_agents($layer['tgrupo_id_grupo']);
		foreach ($agentNames as $agentName) {
			$idAgent = get_agent_id($agentName);
			$coords = get_agent_last_coords($idAgent);
			
			switch (get_agent_status($idAgent)) {
				case 1:
				case 4:
					//Critical (BAD or ALERT)
					$status = "bad";
					break;
				case 0:
					//Normal (OK)
					$status = "ok";
					break;
				case 2:
					//Warning
					$status = "warning";
					break;
				default:
					// Default is Grey (Other)
					$status = false;
			}
			$icon = get_agent_icon_map($idAgent, $status);
			
			addPath($layer['layer_name'], $idAgent);
			addPoint($layer['layer_name'], $agentName, $coords['last_latitude'], $coords['last_longitude'], $icon);
		}
	}
}

?>
<br /><br />
<div id='map' style='width: 99%; height: 400px; border: 1px solid black;' ></div>