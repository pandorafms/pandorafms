<?php
// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2009 Evi Vanoost, <vanooste@rcbi.rochester.edu>
// Please see http://pandora.sourceforge.net for full contribution list
//
// This file is part of Pandora FMS.
//
// Pandora FMS is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 2 of the License, or
// (at your option) any later version.
//
// Pandora FMS is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Pandora FMS. If not, see <http://www.gnu.org/licenses/>.

/**
 * Gets all export servers out of the database
 *   
 * @param (bool) $active Whether or not to exclude inactive servers (defaults to 1 => no inactive servers)
 *
 * @return (array) An array of server information (similar to server_info) but without the other servers
**/
function get_exportservers ($active = 1) {
	$query = "SELECT * FROM tserver WHERE export_server = 1";
	$return = array ();
		
	if ($active == 1) {
		$servers = get_db_all_rows_sql ($query.' AND status = 1');
	} else {
		$servers = get_db_all_rows_sql ($query);
	}
	
	if (empty ($servers)) {
		return $return;
	}
	
	foreach ($servers as $server) {
		$return[$server["id_server"]] = $server;
	}
	
	return $return;
}

/**
 * Gets a specific piece of info on the export servers table (defaults to name)
 *
 * @param (bool) $active (bool) Whether or not to exclude inactive servers (defaults to 1 => no inactive servers)
 * @param (string) $row What row to select from the server info table
 *
 * @return (array) An array of server information (similar to get_exportservers) but without the extra data
 **/
function get_exportservers_info ($active = 1, $row = "name") {
	$exportservers = get_exportservers ();
	$return = array ();
	
	foreach ($exportservers as $server_id => $server_info) {
		$return[$server_id] = $server_info[$row];
	}
	
	return $return;
}
?>