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
 * @package Include/auth
 */


if (!isset ($config)) {
	die ('
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>Pandora FMS - The Flexible Monitoring System - Console error</title>
<meta http-equiv="expires" content="0">
<meta http-equiv="content-type" content="text/html; charset=utf8">
<meta name="resource-type" content="document">
<meta name="distribution" content="global">
<meta name="author" content="Sancho Lerena">
<meta name="copyright" content="This is GPL software. Created by Sancho Lerena and others">
<meta name="keywords" content="pandora, monitoring, system, GPL, software">
<meta name="robots" content="index, follow">
<link rel="icon" href="../../images/pandora.ico" type="image/ico">
<link rel="stylesheet" href="../styles/pandora.css" type="text/css">
</head>
<body>
<div id="main" style="float:left; margin-left: 100px">
<div align="center">
<div id="login_f">
	<h1 id="log_f" class="error">You cannot access this file</h1>
	<div>
		<img src="../../images/pandora_logo.png" border="0"></a>
	</div>
	<div class="msg">
		<span class="error"><b>ERROR:</b>
		You can\'t access this file directly!</span>
	</div>
</div>
</div>
</body>
</html>
');
}

$config["user_can_update_password"] = false;
$config["admin_can_add_user"] = false;
$config["admin_can_delete_user"] = false;
$config["admin_can_disable_user"] = false;

global $dev_cache; //This variable needs to be globalized because this file is called from within a function and thus local

//DON'T USE THIS IF YOU DON'T KNOW WHAT YOU'RE DOING
die ("This is a very dangerous authentication scheme. Only use for programming in case you should uncomment this line");

/**
 * process_user_login accepts $login and $pass and handles it according to current authentication scheme
 *
 * @param string $login 
 * @param string $pass
 *
 * @return mixed False in case of error or invalid credentials, the username in case it's correct.
 */
function process_user_login ($login, $pass) {
	return false; //Error
	return $login; //Good
}

/** 
 * Checks if a user is administrator.
 * 
 * @param string User id.
 * 
 * @return bool True is the user is admin
 */
function is_user_admin ($user) {
	return true; //User is admin
	return false; //User isn't
}

/** 
 * Check is a user exists in the system
 * 
 * @param string User id.
 * 
 * @return bool True if the user exists.
 */
function is_user ($id_user) {
	return true;
	return false;
}

/** 
 * Gets the users real name
 * 
 * @param string User id.
 * 
 * @return string The users full name
 */
function get_user_fullname ($id_user) {
	return "admin";
	return "";
	return false;
}

/** 
 * Gets the users email
 * 
 * @param string User id.
 * 
 * @return string The users email address
 */
function get_user_email ($id_user) {
	return "test@example.com";
	return "";
	return false;
}

/**
 * Get a list of all users in an array [username] => real name
 * 
 * @param string Field to order by (id_usuario, nombre_real or fecha_registro)
 *
 * @return array An array of users
 */
function get_users ($order = "nombre_real") {
	return array ("admin" => "Admini Strator");
}

/**
 * Sets the last login for a user
 *
 * @param string User id
 */
function process_user_contact ($id_user) {
	//void
}

/**
 * Deletes the user
 *
 * @param string User id
 */
function delete_user ($id_user) {
	return true;
	return false;
}

//Reference the global use authorization error to last ldap error.
$config["auth_error"] = &$dev_cache["auth_error"];
?>
