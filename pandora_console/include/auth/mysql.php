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

$config["user_can_update_info"] = true;
$config["user_can_update_password"] = true;
$config["admin_can_add_user"] = true;
$config["admin_can_delete_user"] = true;
$config["admin_can_disable_user"] = false; //currently not implemented
$config["admin_can_make_admin"] = true;

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
	$sql = sprintf ("SELECT `id_user`, `password` FROM `tusuario` WHERE `id_user` = '%s'", $login);
	$row = get_db_row_sql ($sql);
	
	//Check that row exists, that password is not empty and that password is the same hash
	if ($row !== false && $row["password"] !== md5 ("") && $row["password"] == md5 ($pass)) {
		// Login OK
		// Nick could be uppercase or lowercase (select in MySQL
		// is not case sensitive)
		// We get DB nick to put in PHP Session variable,
		// to avoid problems with case-sensitive usernames.
		// Thanks to David Muñiz for Bug discovery :)
		return $row["id_user"];
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
	static $is_admin = -1;
	
	if ($is_admin !== -1)
		return $is_admin;
	
	$is_admin = (bool) get_db_value ('is_admin', 'tusuario', 'id_user', $id_user);
	return $is_admin;
}


/**
 * Get the user id field on a mixed structure.
 *
 * This function is needed to make auth system more compatible and independant.
 *
 * @param mixed User structure to get id. It might be a row returned from
 * tusuario or tusuario_perfil. If it's not a row, the int value is returned.
 *
 * @return int User id of the mixed parameter.
 */
function get_user_id ($user) {
	if (is_array ($user)){
		if (isset ($user['id_user']))
			return $user['id_user'];
		elseif (isset ($user['id_usuario']))
			return $user['id_usuario'];
		else
			return false;
	} else {
		return $user;
	}
}

/** 
 * Check is a user exists in the system
 * 
 * @param mixed User id.
 * 
 * @return bool True if the user exists.
 */
function is_user ($user) {
	$user = get_db_row ('tusuario', 'id_user', get_user_id ($user));
	if (! $user)
		return false;
	return true;
}

/** 
 * Gets the users real name
 * 
 * @param mixed User id.
 * 
 * @return string The users full name
 */
function get_user_fullname ($user) {
	return (string) get_db_value ('fullname', 'tusuario', 'id_user', get_user_id ($user));
}

/** 
 * Gets the users email
 * 
 * @param mixed User id.
 * 
 * @return string The users email address
 */
function get_user_email ($user) {
	return (string) get_db_value ('email', 'tusuario', 'id_user', get_user_id ($user));
}

/**
 * Gets a Users info
 * 
 * @param mixed User id
 *
 * @return mixed An array of users
 */
function get_user_info ($user) {
	return get_db_row ("tusuario", "id_user", get_user_id ($user));
}

/**
 * Get a list of all users in an array [username] => array (userinfo)
 * We can't simplify this because some auth schemes (like LDAP) automatically (or it's at least cheaper to) return all the information
 * Functions like get_user_info allow selection of specifics (in functions_db)
 *
 * @param string Field to order by (id_user, fullname or registered)
 *
 * @return array An array of user information
 */
function get_users ($order = "fullname") {
	switch ($order) {
	case "id_user":
	case "registered":
	case "last_connect":
	case "fullname":
		break;
	default:
		$order = "fullname";
	}
	
	$output = array();
	
	$result = get_db_all_rows_in_table ("tusuario", $order);
	if ($result !== false) {
		foreach ($result as $row) {
			$output[$row["id_user"]] = $row;
		}
	}
	
	return $output;
}
	
/**
 * Sets the last login for a user
 *
 * @param string User id
 */
function process_user_contact ($id_user) {
	return process_sql_update ("tusuario",
		array ("last_connect" => get_system_time ()),
			array ("id_user" => $id_user));
}

/**
 * Create a new user
 *
 * @return bool false
 */
function create_user ($id_user, $password, $user_info) {
	$values = $user_info;
	$values["id_user"] = $id_user;
	$values["password"] = md5 ($password);
	$values["last_connect"] = 0;
	$values["registered"] = get_system_time ();

	return (@process_sql_insert ("tusuario", $values)) !== false;
}

/**
 * Deletes the user
 *
 * @param string User id
 */
function delete_user ($id_user) {
	$sql = "DELETE FROM tusuario_perfil WHERE id_usuario = '".$id_user."'";
	$result = process_sql ($sql);
	if ($result === false) {
		return false;
	}
	$sql = "DELETE FROM tusuario WHERE id_user = '".$id_user."'";
	$result = process_sql ($sql);
	if ($result === false) {
		return false;
	}
	return true;
}

function update_user_password ($user, $password_new) {
	return process_sql_update ('tusuario',
		array ('password' => md5 ($password_new)),
		array ('id_user' => $user));
}

function update_user ($id_user, $values) {
	if (! is_array ($values))
		return false;
	
	return process_sql_update ("tusuario", $values, array ("id_user" => $id_user));
}

//Reference the global use authorization error to last auth error.
$config["auth_error"] = &$mysql_cache["auth_error"];
?>
