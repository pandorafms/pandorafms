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

check_login ();

require_once ('include/functions_gis.php');
require_once($config['homedir'] . "/include/functions_agents.php");

ui_require_javascript_file('openlayers.pandora');

$idMap = (int) get_parameter ('map_id');
$show_history = get_parameter ('show_history', 'n');

$map = db_get_row ('tgis_map', 'id_tgis_map', $idMap);
$confMap = gis_get_map_conf($idMap);

if (! check_acl ($config['id_user'], $map['group_id'], "IR")) {
	db_pandora_audit("ACL Violation", "Trying to access map builder");
	require ("general/noaccess.php");
	return;
}

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

// Render map

$buttons = array();

if ($config["pure"] == 0) {
	$buttons[] = '<a href="index.php?sec=visualc&amp;sec2=operation/gis_maps/render_view&amp;map_id='.$idMap.'&amp;refr='.$config["refr"].'&amp;pure=1">' .
		html_print_image ("images/fullscreen.png", true, array ("title" => __('Full screen mode'))) . "</a>";
}
else {
	$buttons[] = '<a href="index.php?sec=visualc&amp;sec2=operation/gis_maps/render_view&amp;map_id='.$idMap.'&amp;refr='.$config["refr"].'">' . 
		html_print_image ("images/normalscreen.png", true, array ("title" => __('Back to normal mode'))) . "</a>";
}

if (check_acl ($config["id_user"], $map['group_id'], "IW"))
	$buttons [] = '<a href="index.php?sec=godgismaps&sec2=godmode/gis_maps/configure_gis_map&action=edit_map&map_id='. $idMap.'">'.html_print_image ("images/setup.png", true, array ("title" => __('Setup'))).'</a>';

$buttonsString = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente=3">' .
	html_print_image("images/bricks.png", true, array("class" => "top", "border" => '0')) . '&nbsp; Agent&nbsp;-&nbsp;test_gis1</a></li></ul></div><div id="menu_tab"><ul class="mn"><li class="nomn"><a href="index.php?sec=estado&amp;sec2=godmode/agentes/configurar_agente&amp;id_agente=3">' . html_print_image("images/setup.png", true, array("class" => "top", "title" => "Manage", "border" => "0", "width" => "16", "title" => "Manage")) . '&nbsp;</a></li><li class="nomn_high"><a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente=3">' . html_print_image("images/monitor.png", true, array("class" => "top", "title" => "Main", "border" => "0")) . '&nbsp;</a></li><li class="nomn"><a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente=3&amp;tab=data">' . html_print_image("images/lightbulb.png", true, array("class" => "top", "title" => "Data", "border" => "0")) . '&nbsp;</a></li><li class="nomn"><a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente=3&amp;tab=alert">' . html_print_image("images/bell.png", true, array("class" => "top", "title" => "Alerts", "border" => "0")) . '&nbsp;</a></li><li class="nomn"><a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;tab=sla&amp;id_agente=3">' . html_print_image("images/images.png", true, array("class" => "top", "title" => "S.L.A.", "border" => "0")) . '&nbsp;</a></li><li class="nomn"><a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;group_id=2">' . html_print_image("images/agents_group.png", true, array("class" => "top", "title" => "Group", "border" => "0")) . '&nbsp;</a></li><li class="nomn"><a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;tab=inventory&amp;id_agente=3">' . html_print_image("images/page_white_text.png", true, array("class" => "top", "title" => "Inventory", "border" => "0", "width" => "16")) . '&nbsp;</a></li><li class="nomn"><a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;tab=gis&amp;id_agente=3">' . html_print_image("images/world.png", array("class" => "top", "title" => "GIS data", "border" => "0")) . '&nbsp;</a>';

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

$buttons[] = '&nbsp;' . __('Refresh: ') . html_print_select($times, 'refresh_time', 60, 'changeRefreshTime(this.value);', '', 0, true, false, false) . "&nbsp;";

$buttons[] = '<a id="button_status_all" href="javascript: changeShowStatus(\'all\');" style="border: 1px black solid;">' .
	__('All') . '</a>';
$buttons[] = '<a id="button_status_bad" href="javascript: changeShowStatus(\'bad\');"><div style="height: 18px;">' . 
	html_print_image("images/status_sets/default/agent_critical_ball.png", true, array("title" => __('Critical'))) . '</div></a>';
$buttons[] = '<a id="button_status_warning" href="javascript: changeShowStatus(\'warning\');"><div style="height: 18px;">' .
	html_print_image("images/status_sets/default/agent_warning_ball.png", true, array("title" => __('Warning'))) . '</div></a>';
$buttons[] = '<a id="button_status_ok" href="javascript: changeShowStatus(\'ok\');"><div style="height: 18px;">' .
	html_print_image("images/status_sets/default/agent_ok_ball.png", true, array("title" => __('Ok'))) . '</div></a>';
$buttons[] = '<a id="button_status_default" href="javascript: changeShowStatus(\'default\');"><div style="height: 18px;">' .
	html_print_image("images/status_sets/default/agent_no_monitors_ball.png", true, array("title" => __('Other'))) . '</div></a>';
$buttons[] = __('Show agents by state: ');


ui_print_page_header(__('Map') . " &raquo; " . __('Map') . "&nbsp;" . $map['map_name'], "", false, "", false, $buttons);

if ($config["pure"] == 0) {
	echo "<div id='map' style='width: 99%; height: 500px; border: 1px solid black;' ></div>";
}
else {
	echo "<div id='map' style='position:absolute;top:40px; z-index:100; width: 100%; height:94%; min-height:500px; border: 1px solid black;' ></div>";
}

gis_print_map('map', $map['zoom_level'], $map['initial_latitude'],
	$map['initial_longitude'], $baselayers, $controls);

if ($layers != false) {
	foreach ($layers as $layer) {
		gis_make_layer($layer['layer_name'], $layer['view_layer'], null, $layer['id_tmap_layer']);
		
		// calling agents_get_group_agents with none to obtain the names in the same case as they are in the DB.	
		$agentNamesByGroup = agents_get_group_agents($layer['tgrupo_id_grupo'],false,'none', true, true, false);
		$agentNamesByLayer = gis_get_agents_layer($layer['id_tmap_layer'], array('nombre'));
		
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
			$status = agents_get_status($idAgent);
			$parent = db_get_value('id_parent', 'tagente', 'id_agente', $idAgent);
			
			gis_add_agent_point($layer['layer_name'], io_safe_output($agentName), $coords['stored_latitude'],
				$coords['stored_longitude'], $icon, 20, 20, $idAgent, $status, 'point_agent_info', $parent);
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
	gis_activate_ajax_refresh($layers, $timestampLastOperation);
}

// Resize GIS map on fullscreen
if ($config["pure"] != 0) {
?>
	<script type="text/javascript">
		$().ready(function(){
			
			var new_height = $(document).height();
			$("#map").css("height", new_height - 60);
			
		});
	</script>
<?php
}
else {
	// Resize GIS map on normalview
?>
	<script type="text/javascript">
		$().ready(function(){
			
			var new_height = $("#menu").height();
			$("#map").css("height", new_height - 20);
			
		});
		
	</script>
<?php
}
?>
