<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
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
 * @subpackage Servers
 */

/**
 * Get a server.
 *
 * @param int Server id to get.
 * @param array Extra filter.
 * @param array Fields to get.
 *
 * @return Server with the given id. False if not available.
 */
function get_server ($id_server, $filter = false, $fields = false) {
	if (empty ($id_server))
		return false;
	if (! is_array ($filter))
		$filter = array ();
	$filter['id_server'] = $id_server;
	
	return @get_db_row_filter ('tserver', $filter, $fields);
}

/**
 * Get all the server availables.
 *
 * @return All the servers available.
 */
function get_server_names () {
	$all_servers = @get_db_all_rows_filter ('tserver', false, array ('DISTINCT(`name`) as name'));
	if ($all_servers === false)
		return array ();
	
	$servers = array ();
	foreach ($all_servers as $server) {
		$servers[$server['name']] = $server['name'];
	}
	return $servers;
}

?>
