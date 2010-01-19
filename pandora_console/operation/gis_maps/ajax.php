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

$opt = get_parameter('opt');

switch ($opt) {
	case 'point_info':
		$id = get_parameter('id');
		$row = get_db_row_sql('SELECT * FROM tgis_data WHERE id_tgis_data = ' . $id);
		
		$row = array('id_tgis_data' => 0, 'longitude' => -3.709,
			'latitude' => 40.422, 'altitude' => 0, 'end_timestamp' => '2010-01-14 17:19:45', 'start_timestamp' => '2010-01-12 17:19:45', 'manual_placemen' => 1, 'tagente_id_agente' => 1);
		
		$returnJSON = array();
		$returnJSON['correct'] = 1;
		$returnJSON['content'] = __('Agent') . ': <a style="font-weight: bolder;" href="?sec=estado&sec2=operation/agentes/ver_agente&id_agente=' . $row['tagente_id_agente'] . '">pepito</a><br />';
		$returnJSON['content'] .= __('Position') . ': (' . $row['longitude'] . ', ' . $row['latitude'] . ', ' . $row['altitude'] . ') <br />';		
		$returnJSON['content'] .= __('Start contact') . ': ' . $row['start_timestamp'] . '<br />';
		$returnJSON['content'] .= __('Last contact') . ': ' . $row['end_timestamp'] . '<br />';
		$returnJSON['content'] .= __('Num reports') . ': 666<br />'; //$row['num_packages']; //TODO
		if ($row['manual_placemen']) $returnJSON['content'] .= '<br />' . __('Manual placement') . '<br />'; 
		
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