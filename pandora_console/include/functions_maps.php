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
	if ($result) {
		$map_items = db_get_all_rows_sql("SELECT * FROM titem WHERE id_map = " . $id);
		maps_duplicate_items_map($result, $map_items);
	}
	return (int)$result;
}

function maps_duplicate_items_map($id, $map_items) {
	if (empty($map_items)) {
		return;
	}
	foreach ($map_items as $item) {
		$copy_items = array('id_map' => $id, 'x' => $item['x'], 'y' => $item['y'], 'z' => $item['z'],
					'deleted' => $item['deleted'], 'type' => $item['type'], 'refresh' => $item['refresh'],
					'source' => $item['source'], 'source_data' => $item['source_data'],
					'options' => $item['options'], 'style' => $item['style']);
		$result_copy_item = db_process_sql_insert('titem', $copy_items);
		if ($result_copy_item) {
			$item_relations = db_get_all_rows_sql("SELECT * FROM trel_item WHERE id = " . $item['id']);
			if ($item['id'] == $item_relations['parent_id']) {
				$copy_item_relations = array(
					'id_parent' => $result_copy_item,
					'id_child' => $item_relations['id_child'],
					'parent_type' => $item_relations['parent_type'],
					'child_type' => $item_relations['child_type'],
					'id_item' => $item_relations['id_item'],
					'deleted' => $item_relations['deleted']);
			}
			else {
				$copy_item_relations = array(
					'id_parent' => $item_relations['id_parent'],
					'id_child' => $result_copy_item,
					'parent_type' => $item_relations['parent_type'],
					'child_type' => $item_relations['child_type'],
					'id_item' => $item_relations['id_item'],
					'deleted' => $item_relations['deleted']);
			}
			db_process_sql_insert('trel_item', $copy_item_relations);
		}
	}
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

function maps_add_node ($values) {
	$result_add_node =  db_process_sql_insert('titem', $values);
	return $result_add_node;
}

function maps_add_node_relationship ($values) {
	$result_add_node_rel =  db_process_sql_insert('trel_item', $values);
	return $result_add_node_rel;
}
?>
