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
require_once ('include/functions_ui.php');

$opt = get_parameter('opt');

switch ($opt) {
	case 'get_data_conexion':
		$returnJSON['correct'] = 1;
		$idConection = get_parameter('id_conection');
		
		$row = get_db_row_filter('tgis_map_connection', array('id_tmap_connection' => $idConection));
		
		$returnJSON['content'] = $row;
		
		echo json_encode($returnJSON);
		break;
	case 'get_new_positions':
		$id_features = get_parameter('id_features', '');
		$last_time_of_data = get_parameter('last_time_of_data');
		$layerId = get_parameter('layer_id');
		
		$returnJSON = array();
		$returnJSON['correct'] = 1;
		
		$rows = get_db_all_rows_sql('SELECT *
			FROM tgis_data 
			WHERE tagente_id_agente IN (' . $id_features . ') AND start_timestamp > from_unixtime(' . $last_time_of_data . ') ORDER BY start_timestamp DESC');
		
		$listCoords = null;
		foreach ($rows as $row) {
			if (empty($listCoords[$row['tagente_id_agente']])) {
				$coords['latitude'] = $row['latitude'];
				$coords['longitude'] = $row['longitude'];
				$coords['start_timestamp'] = $row['start_timestamp'];
				$coords['id_tgis_data'] = $row['id_tgis_data'];
				
				$listCoords[$row['tagente_id_agente']][] = $coords;
			}
		}
		
		//Extract the data of tgis_data the new agents that it aren't in list
		//of features.
		$idGroup = get_db_value('tgrupo_id_grupo', 'tgis_map_layer', 'id_tmap_layer', $layerId);
		//If id group = 1 is the all groups.
		if ($idGroup != 1) {
			$whereGroup = 'id_grupo = ' . $idGroup;
		}
		else {
			$whereGroup = '1 = 1';
		}
			
		$idAgents = get_db_all_rows_sql('SELECT id_agente 
			FROM tagente 
			WHERE id_agente IN (SELECT tagente_id_agente
					FROM tgis_map_layer_has_tagente
					WHERE tgis_map_layer_id_tmap_layer = ' . $layerId .') OR ' . $whereGroup);
		
		$temp = array();
		foreach($idAgents as $idAgent) {
			$temp[] = $idAgent['id_agente'];
		}
		
		$rows = get_db_all_rows_sql('SELECT * FROM tgis_data
			WHERE tagente_id_agente NOT IN (' . $id_features . ') AND tagente_id_agente IN (' . implode(',',$temp) . ')
				AND start_timestamp > from_unixtime(' . $last_time_of_data . ') ORDER BY start_timestamp DESC;');
		
		$agents = get_db_all_rows_sql('SELECT id_agente, nombre FROM tagente WHERE 
				id_agente NOT IN (' . $id_features . ') AND id_agente IN (' . implode(',',$temp) . ')');
		
		$listNewCoords = null;
		foreach ($rows as $row) {
			if (empty($listNewCoords[$row['tagente_id_agente']])) {
				foreach ($agents as $agent) {
					if ($agent['id_agente'] == $row['tagente_id_agente']) {
						$name = $agent['nombre'];
						$icon_path = get_agent_icon_map($row['tagente_id_agente'], true);
					}
				}
				
				$listNewCoords[$row['tagente_id_agente']] = array (
					'latitude' => $row['latitude'],
					'longitude' => $row['longitude'],
					'start_timestamp' => $row['start_timestamp'],
					'id_tgis_data' => $row['id_tgis_data'],
					'name' => $name,
					'icon_path' => $icon_path,
					'icon_width' => 20, //TODO SET CORRECT WIDTH
					'icon_height' => 20, //TODO SET CORRECT HEIGHT
					);
			}
		}
		
		$content = array('coords' => $listCoords, 'new_coords' => $listNewCoords);
		
		$returnJSON['content'] = json_encode($content); json_encode($listcoords);
		
		echo json_encode($returnJSON);
		break;
	case 'point_path_info':
		$id = get_parameter('id');
		$row = get_db_row_sql('SELECT * FROM tgis_data WHERE id_tgis_data = ' . $id);
		
		$returnJSON = array();
		$returnJSON['correct'] = 1;
		$returnJSON['content'] = __('Agent') . ': <a style="font-weight: bolder;" href="?sec=estado&sec2=operation/agentes/ver_agente&id_agente=' . $row['tagente_id_agente'] . '">'.get_agent_name($row['tagente_id_agente']).'</a><br />';
		$returnJSON['content'] .= __('Position (Long, Lat, Alt)') . ': (' . $row['longitude'] . ', ' . $row['latitude'] . ', ' . $row['altitude'] . ') <br />';		
		$returnJSON['content'] .= __('Start contact') . ': ' . $row['start_timestamp'] . '<br />';
		$returnJSON['content'] .= __('Last contact') . ': ' . $row['end_timestamp'] . '<br />';
		$returnJSON['content'] .= __('Num reports') . ': '.$row['number_of_packages'].'<br />'; 
		if ($row['manual_placemen']) $returnJSON['content'] .= '<br />' . __('Manual placement') . '<br />'; 
		
		echo json_encode($returnJSON);
		
		break;
	case 'point_agent_info':
		$id = get_parameter('id');
		$row = get_db_row_sql('SELECT * FROM tagente WHERE id_agente = ' . $id);
		
		$returnJSON = array();
		$returnJSON['correct'] = 1;
		$returnJSON['content'] = __('Agent') . ': <a style="font-weight: bolder;" href="?sec=estado&sec2=operation/agentes/ver_agente&id_agente=' . $row['id_agente'] . '">'.$row['nombre'].'</a><br />';
		$returnJSON['content'] .= __('Position (Long, Lat, Alt)') . ': (' . $row['last_longitude'] . ', ' . $row['last_latitude'] . ', ' . $row['last_altitude'] . ') <br />';		
		$agent_ip_address = get_agent_address ($id_agente);
		if ($agent_ip_address || $agent_ip_address != '') {
			$returnJSON['content'] .= __('IP Address').': '.get_agent_address ($id_agente).'<br />';
		}
		$returnJSON['content'] .= __('OS').': '.print_os_icon($row['id_os'], true, true);

		$osversion_offset = strlen($row["os_version"]);
		if ($osversion_offset > 15) {
    		$osversion_offset = $osversion_offset - 15;
		}
		else {
		    $osversion_offset = 0;
		}
		$returnJSON['content'] .= '&nbsp;( <i><span title="'.$row["os_version"].'">'.substr($row["os_version"],$osversion_offset,15).'</span></i>)<br />';
		$agent_description = $row['comentarios'];
		if ($agent_description || $agent_description != '') {
			$returnJSON['content'] .= __('Description').': '.$agent_description.'<br />';
		}
		$returnJSON['content'] .= __('Group').': '.print_group_icon ($row["id_grupo"], true).'&nbsp;(<strong>'.get_group_name ($row["id_grupo"]).'</strong>)<br />';
		$returnJSON['content'] .= __('Agent Version').': '.$row["agent_version"].'<br />';
		$returnJSON['content'] .= __('Last contact')." / ".__('Remote').': '. $row["ultimo_contacto"]. " / ";
		if ($row["ultimo_contacto_remoto"] == "0000-00-00 00:00:00") {
    		$returnJSON['content'] .=__('Never');
		} else {
 			$returnJSON['content'] .= $row["ultimo_contacto_remoto"];
		}


		
		echo json_encode($returnJSON);
		
		break;
}
?>