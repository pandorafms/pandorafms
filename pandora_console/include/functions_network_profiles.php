<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License (LGPL)
// as published by the Free Software Foundation for version 2.
// 
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

/**
 * Get a custom user report.
 *
 * @param int Report id to get.
 * @param array Extra filter.
 * @param array Fields to get.
 *
 * @return Report with the given id. False if not available or readable.
 */
function get_network_profile ($id_network_profile, $filter = false, $fields = false) {
	global $config;
	
	$id_network_profile = safe_int ($id_network_profile);
	if (empty ($id_network_profile))
		return false;
	if (! is_array ($filter))
		$filter = array ();
	$filter['id_np'] = $id_network_profile;
	
	return @get_db_row_filter ('tnetwork_profile', $filter, $fields);
}

/**
 * Deletes a network_profile.
 * 
 * @param int Network profile id to be deleted.
 *
 * @return bool True if deleted, false otherwise.
 */
function delete_network_profile ($id_network_profile) {
	$id_network_profile = safe_int ($id_network_profile);
	if (empty ($id_network_profile))
		return false;
	$profile = get_network_profile ($id_network_profile);
	if ($profile === false)
		return false;
	return @process_sql_delete ('tnetwork_profile',
		array ('id_np' => $id_network_profile));
}

?>
