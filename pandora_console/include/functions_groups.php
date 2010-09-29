<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


/**
 * Check if the group is in use in the Pandora DB. 
 * 
 * @param integer $idGroup The id of group.
 * 
 * @return bool Return false if the group is unused in the Pandora, else true.
 */
function checkUsedGroup($idGroup) {
	$return = array();
	$return['return'] = false;
	$return['tables'] = array();
	
	$numRows = get_db_num_rows('SELECT * FROM tagente WHERE id_grupo = ' . $idGroup . ';');
	if ($numRows > 0) {
		$return['return'] = true;
		$return['tables'][] = __('Agents'); 
	}
	
	$numRows = get_db_num_rows('SELECT * FROM talert_actions WHERE id_group = ' . $idGroup . ';');
	if ($numRows > 0) {
		$return['return'] = true;
		$return['tables'][] = __('Alert Actions'); 
	}
	
	$numRows = get_db_num_rows('SELECT * FROM talert_templates WHERE id_group = ' . $idGroup . ';');
	if ($numRows > 0) {
		$return['return'] = true;
		$return['tables'][] = __('Alert Templates'); 
	}
	
	$numRows = get_db_num_rows('SELECT * FROM trecon_task WHERE id_group = ' . $idGroup . ';');
	if ($numRows > 0) {
		$return['return'] = true;
		$return['tables'][] = __('Recon task'); 
	}
	
	$numRows = get_db_num_rows('SELECT * FROM tgraph WHERE id_group = ' . $idGroup . ';');
	if ($numRows > 0) {
		$return['return'] = true;
		$return['tables'][] = __('Graphs'); 
	}
	
	$numRows = get_db_num_rows('SELECT * FROM treport WHERE id_group = ' . $idGroup . ';');
	if ($numRows > 0) {
		$return['return'] = true;
		$return['tables'][] = __('Reports'); 
	}
	
	$numRows = get_db_num_rows('SELECT * FROM tlayout WHERE id_group = ' . $idGroup . ';');
	if ($numRows > 0) {
		$return['return'] = true;
		$return['tables'][] = __('Layout visual console'); 
	}
	
	$numRows = get_db_num_rows('SELECT * FROM tplanned_downtime WHERE id_group = ' . $idGroup . ';');
	if ($numRows > 0) {
		$return['return'] = true;
		$return['tables'][] = __('Plannet down time'); 
	}
	
	$numRows = get_db_num_rows('SELECT * FROM tgraph WHERE id_group = ' . $idGroup . ';');
	if ($numRows > 0) {
		$return['return'] = true;
		$return['tables'][] = __('Graphs'); 
	}
	
	$numRows = get_db_num_rows('SELECT * FROM tgis_map WHERE group_id = ' . $idGroup . ';');
	if ($numRows > 0) {
		$return['return'] = true;
		$return['tables'][] = __('GIS maps'); 
	}
	
	$numRows = get_db_num_rows('SELECT * FROM tgis_map_connection WHERE group_id = ' . $idGroup . ';');
	if ($numRows > 0) {
		$return['return'] = true;
		$return['tables'][] = __('GIS connections'); 
	}
	
	$numRows = get_db_num_rows('SELECT * FROM tgis_map_layer WHERE tgrupo_id_grupo = ' . $idGroup . ';');
	if ($numRows > 0) {
		$return['return'] = true;
		$return['tables'][] = __('GIS map layers'); 
	}
	
	$numRows = get_db_num_rows('SELECT * FROM tnetwork_map WHERE id_group = ' . $idGroup . ';');
	if ($numRows > 0) {
		$return['return'] = true;
		$return['tables'][] = __('Network maps'); 
	}
	
	$hookEnterprise = enterprise_include_once('include/functions_groups.php');
	if ($hookEnterprise !== ENTERPRISE_NOT_HOOK) {
		$returnEnterprise = enterprise_hook('checkUsedGroupEnterprise', array($idGroup));
		
		if ($returnEnterprise['return']) {
			$return['return'] = true;
			$return['tables'] = array_merge($return['tables'], $returnEnterprise['tables']);
		}
	}
	
	return $return;
}

?>