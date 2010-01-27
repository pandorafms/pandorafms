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
//		$returnJSON = array();
//		
//		$returnJSON['correct'] = 1;
//		$returnJSON['content'] = '';
//		
//		//echo json_encode($returnJSON);
//		break;
//}
//
//$listPoints = get_db_all_rows_sql('SELECT * FROM tgis_data WHERE tagente_id_agente = ' . $idAgent . ' ORDER BY end_timestamp ASC');
//
//$listPoints = array(
//	array('id_tgis_data' => 0, 'longitude' => -3.709, 'latitude' => 40.422, 'altitude' => 0, 'manual_placemen' => 1),
//	array('id_tgis_data' => 1, 'longitude' => -3.710, 'latitude' => 40.420, 'altitude' => 0, 'manual_placemen' => 0),
//	array('id_tgis_data' => 2, 'longitude' => -3.711, 'latitude' => 40.420, 'altitude' => 0, 'manual_placemen' => 1),
//	array('id_tgis_data' => 3, 'longitude' => -3.712, 'latitude' => 40.422, 'altitude' => 0, 'manual_placemen' => 0),
//	array('id_tgis_data' => 4, 'longitude' => -3.708187, 'latitude' => 40.42056, 'altitude' => 0, 'manual_placemen' => 0)
//);

?>