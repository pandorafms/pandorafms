<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2018 Artica Soluciones Tecnologicas
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
 * @subpackage Event Responses
 */

/**
 * Get all event responses with all values that user can access
 *
 * @return array With all table values
 */
function event_responses_get_responses() {
	global $config;
	$filter = array();

	// Apply a filter if user cannot see all groups
	if (!users_can_manage_group_all()) {
		$id_groups = array_keys(users_get_groups(false, "PM"));
		$filter = array('id_group' => $id_groups);
	}
	return db_get_all_rows_filter('tevent_response', $filter);
}

?>
