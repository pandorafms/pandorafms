<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.



/**
 * @package Include
 * @subpackage Maps
 */

function maps_save_map($values) {
	$result_add =  db_process_sql_insert('tmap', $values);
	return $result_add;
}

function maps_get_maps($filter) {
	return db_get_all_rows_filter('tmap', $filter);
}

function maps_get_subtype_string($subtype) {
	switch ($subtype) {
		case MAP_SUBTYPE_TOPOLOGY:
			return __('Topology');
			break;
		case MAP_SUBTYPE_POLICIES:
			return __('Policies');
			break;
		case MAP_SUBTYPE_GROUPS:
			return __('Groups');
			break;
		case MAP_SUBTYPE_RADIAL_DYNAMIC:
			return __('Dynamic');
			break;
		default:
			return __('Unknown');
			break;
	}
}

function maps_duplicate_map($id) {
	global $config;
	$map = db_get_all_rows_sql("SELECT * FROM tmap WHERE id = " . $id);
	$result = false;
	$map = $map[0];
	if (!empty($map)) {
		$map_names = db_get_row_sql("SELECT name FROM tmap WHERE name LIKE '" . $map['name'] . "%'");
		$index = 0;
		foreach ($map_names as $map_name) {
			$index++;
		}
		$new_name = __('Copy of ') . $map['name'];
		$result = db_process_sql_insert('tmap', array('id_group' => $map['id_group'],
				'id_user' => $config['id_user'], 'type' => $map['type'], 'subtype' => $map['subtype'],
				'name' => $new_name, 'description' => $map['description'], 'width' => $map['width'],
				'height' => $map['height'], 'center_x' => $map['center_x'], 'center_y' => $map['center_y'],
				'background' => $map['background'], 'background_options' => $map['background_options'],
				'source_period' => $map['source_period'], 'source' => $map['source'],
				'source_data' => $map['source_data'], 'generation_method' => $map['generation_method'],
				'filter' => $map['filter']));
	}
	return (int)$result;
}

function maps_delete_map($id) {
	$where = 'id=' . $id;
	$result = db_process_sql_delete('tmap', $where);
	return (int)$result;
}

function maps_get_count_nodes($id) {
	$result = db_get_sql("SELECT COUNT(*) FROM titem WHERE id_map = " . $id);
	return (int)$result;
}

function maps_update_map ($id, $values) {
	$where = 'id=' . $id;
	$result = db_process_sql_update('tmap', $values, $where);
	return (int)$result;
}

function maps_is_networkmap($id) {
	$return = db_get_value('type', 'tmap', 'id', $id);
	
	if ($return === false)
		return false;
	
	if ($return == MAP_TYPE_NETWORKMAP)
		return true;
	else
		return false;
}

function maps_show($id) {
	if (maps_is_networkmap($id)) {
		require_once("include/functions_networkmaps.php");
		
		networkmaps_show($id);
	}
	else {
		//TODO VISUAL
	}
}
?>
