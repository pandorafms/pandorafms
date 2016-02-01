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
	return db_process_sql_insert('tmap', $values);
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

function maps_get_count_nodes($id) {
	$result = db_get_sql(
		"SELECT COUNT(*) FROM titem WHERE id_map = " . $id);
	
	return (int)$result;
}
?>
