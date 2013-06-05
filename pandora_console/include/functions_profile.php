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
 * @package Include
 * @subpackage Profile_Functions
 */

/**
 * Get profile name from id.
 *
 * @param int $id_profile Id profile in tperfil
 *
 * @return string Profile name of the given id
 */
function profile_get_name ($id_profile) {
	return (string) db_get_value ('name', 'tperfil', 'id_perfil', (int) $id_profile);
}

/**
 * Selects all profiles (array (id => name)) or profiles filtered
 *
 * @param mixed Array with filter conditions to retrieve profiles or false.  
 *
 * @return array List of all profiles
 */
function profile_get_profiles ($filter = false) {
	if ($filter === false) { 
		$profiles = db_get_all_rows_in_table ("tperfil", "name");
	}
	else {
		$profiles = db_get_all_rows_filter ("tperfil", $filter);
	}
	$return = array ();
	if ($profiles === false) {
		return $return;
	}
	foreach ($profiles as $profile) {
		$return[$profile["id_perfil"]] = $profile["name"];
	}
	return $return;
}



/**
 * Create Profile for User
 *
 * @param string User ID
 * @param int Profile ID (default 1 => AR)
 * @param int Group ID (default 1 => All)
 * @param string Assign User who assign the profile to user.
 *
 * @return mixed Number id if succesful, false if not
 */
function profile_create_user_profile ($id_user, $id_profile = 1, $id_group = 0, $assignUser = false) {
	global $config;
	
	if (empty ($id_profile) || $id_group < 0)
	return false;
	
	// Checks if the user exists
	$result_user = users_get_user_by_id($id_user);
	
	if (!$result_user) {
		return false;
	}
	
	if (isset ($config["id_user"])) {
		//Usually this is set unless we call it while logging in (user known by auth scheme but not by pandora)
		$assign = $config["id_user"];
	}
	else {
		$assign = $id_user;
	}
	
	if ($assignUser !== false)
	$assign = $assignUser;
	
	$insert = array (
		"id_usuario" => $id_user,
		"id_perfil" => $id_profile,
		"id_grupo" => $id_group,
		"assigned_by" => $assign
	);
	
	return db_process_sql_insert ("tusuario_perfil", $insert);
}

/**
 * Delete user profile from database
 *
 * @param string User ID
 * @param int Profile ID
 *
 * @return bool Whether or not it's deleted
 */
function profile_delete_user_profile ($id_user, $id_profile) {
	$where = array(
		'id_usuario' => $id_user,
		'id_up' => $id_profile);
	
	return (bool)db_process_sql_delete('tusuario_perfil', $where);
}

/**
 * Delete profile from database (not user-profile link (tusuario_perfil), but the actual profile (tperfil))
 *
 * @param int Profile ID
 *
 * @return bool Whether or not it's deleted
 */
function profile_delete_profile ($id_profile) {
	return (bool)db_process_sql_delete('tperfil', array('id_perfil' => $id_profile));
}

?>
