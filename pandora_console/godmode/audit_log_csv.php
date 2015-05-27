<?php
// ______                 __                     _______ _______ _______
//|   __ \.---.-.-----.--|  |.-----.----.---.-. |    ___|   |   |     __|
//|    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
//|___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
//
// ============================================================================
// Copyright (c) 2007-2010 Artica Soluciones Tecnologicas, http://www.artica.es
// This code is NOT free software. This code is NOT licenced under GPL2 licence
// You cannnot redistribute it without written permission of copyright holder.
// ============================================================================

$ownDir = dirname(__FILE__) . '/';
$ownDir = str_replace("\\", "/", $ownDir);
require_once ($ownDir.'../include/config.php');

global $config;
require_once ($config["homedir"]."/include/functions.php");
require_once ($config["homedir"]."/include/functions_db.php");
require_once ($config["homedir"]."/include/auth/mysql.php");

error_reporting(E_ALL);
ini_set("display_errors", 1);

if (! isset ($_SESSION["id_usuario"])) {
	session_start ();
	session_write_close ();
}


// Login check
if (!isset($_SESSION["id_usuario"])) {
	$config['id_user'] = null;
}
else {
	$config['id_user'] = $_SESSION["id_usuario"];
}

if (!check_login()) {
	db_pandora_audit("ACL Violation", "Trying to access graph builder");
	include ($config["homedir"]."/general/noaccess.php");
	return;
}

if (! check_acl ($config['id_user'], 0, "PM")) {
	db_pandora_audit( "ACL Violation",
		"Trying to access event viewer");
	require ("general/noaccess.php");
	exit;
}

$tipo_log = get_parameter ("tipo_log", 'all');
$user_filter = get_parameter('user_filter', 'all');
$filter_text = get_parameter('filter_text', '');
$filter_hours_old = get_parameter('filter_hours_old', 24);
$filter_ip = get_parameter('filter_ip', '');

$filter = 'WHERE 1 = 1';

if ($tipo_log != 'all') {
	$filter .= " AND accion = '$tipo_log'";
}
switch ($config['dbtype']) {
	case "mysql":
		if ($user_filter != 'all') {
			$filter .= sprintf(' AND id_usuario = "%s"', $user_filter);
		}
		
		$filter .= ' AND (accion LIKE "%' . $filter_text . '%" OR descripcion LIKE "%' . $filter_text . '%")';
		
		if ($filter_ip != '') {
			$filter .= sprintf(' AND ip_origen LIKE "%s"', $filter_ip);
		}
		break;
	case "postgresql":
	case "oracle":
		if ($user_filter != 'all') {
			$filter .= sprintf(' AND id_usuario = \'%s\'', $user_filter);
		}
		
		$filter .= ' AND (accion LIKE \'%' . $filter_text . '%\' OR descripcion LIKE \'%' . $filter_text . '%\')';
		
		if ($filter_ip != '') {
			$filter .= sprintf(' AND ip_origen LIKE \'%s\'', $filter_ip);
		}
		break;
}

if ($filter_hours_old != 0) {
	switch ($config["dbtype"]) {
		case "mysql":
			$filter .= ' AND fecha >= DATE_ADD(NOW(), INTERVAL -' . $filter_hours_old . ' HOUR)';
			break;
		case "postgresql":
			$filter .= ' AND fecha >= NOW() - INTERVAL \'' . $filter_hours_old . ' HOUR \'';
			break;
		case "oracle":
			$filter .= ' AND fecha >= (SYSTIMESTAMP - INTERVAL \'' . $filter_hours_old . '\' HOUR)';
			break;
	}
}

switch ($config["dbtype"]) {
	case "mysql":
		$sql = sprintf ("SELECT *
			FROM tsesion
			%s
			ORDER BY fecha DESC", $filter);
		break;
	case "postgresql":
		$sql = sprintf ("SELECT *
			FROM tsesion
			%s
			ORDER BY fecha DESC", $filter);
		break;
	case "oracle":
		$sql = sprintf ("SELECT *
			FROM tsesion
			%s
			ORDER BY fecha DESC", $filter);
		$result = oracle_recode_query ($sql, $set);
		break;
}

$result = db_get_all_rows_sql ($sql);

print_audit_csv ($result);

?>
