<?php
if (!isset ($config)) {
	die ('You cannot access this file directly!');
}

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2009 Evi Vanoost, vanooste@rcbi.rochester.edu
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
// Database configuration (default ones)

$config["user_can_update_password"] = true;
$config["admin_can_add_user"] = true;
$config["admin_can_delete_user"] = true;
$config["admin_can_disable_user"] = true;

/**
 * process_user_login accepts $login and $pass and handles it according to current authentication scheme
 *
 * @param string $login 
 * @param string $pass
 *
 * @return mixed False in case of error or invalid credentials, the username in case it's correct.
 */
function process_user_login ($login, $pass) {
	global $mysql_cache;
	
	// Connect to Database
	$sql = sprintf ("SELECT `id_usuario`, `password` FROM `tusuario` WHERE `id_usuario` = '%s'", $login);
	$row = get_db_row_sql ($sql);
	
	//Check that row exists, that password is not empty and that password is the same hash
	if ($row !== false && $row["password"] !== md5 ("") && $row["password"] == md5 ($pass)) {
		// Login OK
		// Nick could be uppercase or lowercase (select in MySQL
		// is not case sensitive)
		// We get DB nick to put in PHP Session variable,
		// to avoid problems with case-sensitive usernames.
		// Thanks to David Muñiz for Bug discovery :)
		return $row["id_usuario"];
	} else {
		$mysql_cache["auth_error"] = "User not found in database or incorrect password";
	}
	return false;
}

/** 
 * Checks if a user is administrator.
 * 
 * @param string User id.
 * 
 * @return bool True is the user is admin
 */
function is_user_admin ($id_user) {
	$level = get_db_value ('nivel', 'tusuario', 'id_usuario', $id_user);
	if ($level == 1) {
		return true;
	} else {
		return false;
	}
}

/** 
 * Check is a user exists in the system
 * 
 * @param string User id.
 * 
 * @return bool True if the user exists.
 */
function is_user ($id_user) {
	$user = get_db_row ('tusuario', 'id_usuario', $id_user);
	if (! $user)
		return false;
	return true;
}

/** 
 * Gets the users real name
 * 
 * @param string User id.
 * 
 * @return string The users full name
 */
function get_user_realname ($id_user) {
	return (string) get_db_value ('nombre_real', 'tusuario', 'id_usuario', $id_user);
}

/** 
 * Gets the users email
 * 
 * @param string User id.
 * 
 * @return string The users email address
 */
function get_user_email ($id_user) {
	return (string) get_db_value ('direccion', 'tusuario', 'id_usuario', $id_user);
}

/**
 * Gets a Users info
 * 
 * @param string User id
 *
 * @return mixed An array of users
 */
function get_user_info ($id_user) {
	return get_db_row ("tusuario", "id_usuario", $id_user);
}

/**
 * Get a list of all users in an array [username] => array (userinfo)
 * We can't simplify this because some auth schemes (like LDAP) automatically (or it's at least cheaper to) return all the information
 * Functions like get_user_info allow selection of specifics (in functions_db)
 *
 * @param string Field to order by (id_usuario, nombre_real or fecha_registro)
 *
 * @return array An array of user information
 */
function get_users ($order = "nombre_real") {
	switch ($order) {
		case "id_usuario":
		case "fecha_registro":
		case "nombre_real":
			break;
		default:
			$order = "nombre_real";
	}
	
	$output = array();
	
	$result = get_db_all_rows_in_table ("tusuario", $order);
	if ($result !== false) {
		foreach ($result as $row) {
			$output[$row["id_usuario"]] = $row;
		}
	}
	
	return $output;
}
	
/**
 * Sets the last login for a user
 *
 * @param string User id
 */
function update_user_contact ($id_user) {
	$sql = sprintf ("UPDATE tusuario SET fecha_registro = NOW() WHERE id_usuario = '%s'", $id_user);
	process_sql ($sql);
}

/**
 * Deletes the user
 *
 * @param string User id
 */
function delete_user ($id_user) {
	$sql = "DELETE FROM tgrupo_usuario WHERE usuario = '".$id_user."'";
	$result = process_sql ($sql);
	if ($result === false) {
		return false;
	}
	$sql = "DELETE FROM tusuario WHERE id_usuario = '".$id_user."'";
	$result = process_sql ($sql);
	if ($result === false) {
		return false;
	}
	return true;
}

//Reference the global use authorization error to last ldap error.
$config["auth_error"] = &$mysql_cache["auth_error"];
?>