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

require_javascript_file('openlayers.pandora');

$idMap = (int) get_parameter ('map_id');
$show_history = get_parameter ('show_history', 'n');

$map = get_db_row ('tgis_map', 'id_tgis_map', $idMap);
$confMap = getMapConf($idMap);

$num_baselayer=0;
// Initialy there is no Gmap base layer.
$gmap_layer = false;
if ($confMap !== false) {
	foreach ($confMap as $mapC) {
		$baselayers[$num_baselayer]['typeBaseLayer'] = $mapC['connection_type'];
		$baselayers[$num_baselayer]['name'] = $mapC['conection_name'];
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
//debugPrint($gmap_layer);
//debugPrint($gmap_key);
if ($gmap_layer === true) {
?>
	<script type="text/javascript" src="http://maps.google.com/maps?file=api&v=2&sensor=falsei&key=<?php echo $gmap_key ?>" ></script>
<?php
}

$controls = array('PanZoomBar', 'ScaleLine', 'Navigation', 'MousePosition', 'OverviewMap');

$layers = getLayers($idMap);

// Render map

$buttons = array();

if ($config["pure"] == 0) {
	$buttons[] = '<a href="index.php?sec=visualc&amp;sec2=operation/gis_maps/render_view&amp;map_id='.$idMap.'&amp;refr='.$config["refr"].'&amp;pure=1">' .
		print_image ("images/fullscreen.png", true, array ("title" => __('Full screen mode'))) . "</a>";
}
else {
	$buttons[] = '<a href="index.php?sec=visualc&amp;sec2=operation/gis_maps/render_view&amp;map_id='.$idMap.'&amp;refr='.$config["refr"].'">' . 
		print_image ("images/normalscreen.png", true, array ("title" => __('Back to normal mode'))) . "</a>";
}

if (give_acl ($config["id_user"], $map['group_id'], "AW"))
	$buttons [] = '<a href="index.php?sec=godgismaps&sec2=godmode/gis_maps/configure_gis_map&action=edit_map&map_id='. $idMap.'">'.print_image ("images/setup.png", true, array ("title" => __('Setup'))).'</a>';
	
$buttonsString = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente=3"><img src="images/bricks.png" class="top" border="0">&nbsp; Agent&nbsp;-&nbsp;test_gis1</a></li></ul></div><div id="menu_tab"><ul class="mn"><li class="nomn"><a href="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;id_agente=3"><img src="images/setup.png" class="top" title="Manage" border="0" width="16">&nbsp;</a></li><li class="nomn_high"><a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente=3"><img src="images/monitor.png" class="top" title="Main" border="0">&nbsp;</a></li><li class="nomn"><a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente=3&amp;tab=data"><img src="images/lightbulb.png" class="top" title="Data" border="0">&nbsp;</a></li><li class="nomn"><a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente=3&amp;tab=alert"><img src="images/bell.png" class="top" title="Alerts" border="0">&nbsp;</a></li><li class="nomn"><a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;tab=sla&amp;id_agente=3"><img src="images/images.png" class="top" title="S.L.A." border="0">&nbsp;</a></li><li class="nomn"><a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;group_id=2"><img src="images/agents_group.png" class="top" title="Group" border="0">&nbsp;</a></li><li class="nomn"><a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;tab=inventory&amp;id_agente=3"><img src="images/page_white_text.png" class="top" title="Inventory" border="0" width="16">&nbsp;</a></li><li class="nomn"><a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;tab=gis&amp;id_agente=3"><img src="images/world.png" class="top" title="GIS data" border="0">&nbsp;</a>';

$times = array(
	5 => 5 . ' ' . __('seconds'),
	10 => 10 . ' ' . __('seconds'),
	30 => 30 . ' ' . __('seconds'),
	60 => 1 . ' ' . __('minute'),
	120 => 2 . ' ' . __('minutes'),
	300 => 5 . ' ' . __('minutes'),
	600 => 10 . ' ' . __('minutes'),
	3600 => 1 . ' ' . __('hour'),
	7200 => 2 . ' ' . __('hours')
	);

$buttons[] = '&nbsp;' . __('Refresh: ') . print_select($times, 'refresh_time', 60, 'changeRefreshTime(this.value);', '', 0, true, false, false) . "&nbsp;";

$buttons[] = '<a id="button_status_all" href="javascript: changeShowStatus(\'all\');" style="border: 1px black solid;">' . __('All') . '</a>';
$buttons[] = '<a id="button_status_bad" href="javascript: changeShowStatus(\'bad\');"><img src="images/status_sets/default/agent_critical_ball.png" /> ' . __('Critical') . '</a>';
$buttons[] = '<a id="button_status_warning" href="javascript: changeShowStatus(\'warning\');"><img src="images/status_sets/default/agent_warning_ball.png" /> ' . __('Warning') . '</a>';
$buttons[] = '<a id="button_status_ok" href="javascript: changeShowStatus(\'ok\');"><img src="images/status_sets/default/agent_ok_ball.png" /> ' . __('Ok') . '</a>';
$buttons[] = '<a id="button_status_default" href="javascript: changeShowStatus(\'default\');"><img src="images/status_sets/default/agent_no_monitors_ball.png" /> ' . __('Other') . '</a>';
$buttons[] = __('Show agents in state: ');


print_page_header(__('Map') . " &raquo; " . __('Map') . "&nbsp;" . $map['map_name'], "", false, "", false, $buttons);

printMap('map', $map['zoom_level'], $numZoomLevels, $map['initial_latitude'],
	$map['initial_longitude'], $baselayers, $controls);
	
if ($layers != false) {
	foreach ($layers as $layer) {
		makeLayer($layer['layer_name'], $layer['view_layer'], null, $layer['id_tmap_layer']);
		
		// calling get_group_agents with none to obtain the names in the same case as they are in the DB.	
		$agentNamesByGroup = get_group_agents($layer['tgrupo_id_grupo'],false,'none', true);
		$agentNamesByLayer = getAgentsLayer($layer['id_tmap_layer'], array('nombre'));
		
		$agentNames = array_unique($agentNamesByGroup + $agentNamesByLayer);
		
		foreach ($agentNames as $agentName) {
			$idAgent = get_agent_id($agentName);
			$coords = getDataLastPositionAgent($idAgent);
			
			if ($coords === false) {
				$coords['stored_latitude'] = $map['default_latitude'];
				$coords['stored_longitude'] = $map['default_longitude'];
			}
			else {
				if ($show_history == 'y') {
					addPath($layer['layer_name'], $idAgent);
				}
			}
			$icon = get_agent_icon_map($idAgent, true);
			$status = get_agent_status($idAgent);
			$parent = get_db_value('id_parent', 'tagente', 'id_agente', $idAgent);
			
			addAgentPoint($layer['layer_name'], $agentName, $coords['stored_latitude'],
				$coords['stored_longitude'], $icon, 20, 20, $idAgent, $status, 'point_agent_info', $parent);
		}
	}
	addParentLines();
	
	$timestampLastOperation = get_db_value_sql("SELECT UNIX_TIMESTAMP()");
	
	activateSelectControl();
	activateAjaxRefresh($layers, $timestampLastOperation);
}

?>
<?php 
if ($config["pure"] == 0) {
	echo "<div id='map' style='width: 99%; height: 500px; border: 1px solid black;' ></div>";
}
else {
	echo "<div id='map' style='position:absolute;top:40px; z-index:100; width: 98%; height:94%; border: 1px solid black;' ></div>";
}