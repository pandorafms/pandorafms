<?php

//Pandora FMS- http://pandorafms.com
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

global $config;

// Login check
check_login ();

if (! check_acl ($config['id_user'], 0, "IW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access report builder");
	require ("general/noaccess.php");
	exit;
}

$delete_sla_item = get_parameter('delete_sla_item', 0);
$delete_general_item = get_parameter('delete_general_item', 0);
$get_custom_sql = get_parameter('get_custom_sql', 0);
$add_sla = get_parameter('add_sla', 0);
$add_general = get_parameter('add_general', 0);
$id = get_parameter('id', 0);
$truncate_text = get_parameter ('truncate_text', 0);
$get_metaconsole_hash_data = get_parameter('get_metaconsole_hash_data', 0);
$get_metaconsole_server_url = get_parameter('get_metaconsole_server_url', 0);

if ($delete_sla_item) {
	$result = db_process_sql_delete('treport_content_sla_combined', array('id' => (int)$id));
	
	$data['correct'] = 1;
	if ($result === false) {
		$data['correct'] = 0;
	}
	
	echo json_encode($data);
	return;
}

if ($delete_general_item) {
	$result = db_process_sql_delete('treport_content_item', array('id' => (int)$id));
	
	$data['correct'] = 1;
	if ($result === false) {
		$data['correct'] = 0;
	}
	
	echo json_encode($data);
	return;
}

if ($add_sla) {
	$id_module = get_parameter('id_module', 0);
	$sla_limit = get_parameter('sla_limit', 0);
	$sla_max = get_parameter('sla_max', 0);
	$sla_min = get_parameter('sla_min', 0);
	$server_name = get_parameter('server_name', '');
	
	$result = db_process_sql_insert('treport_content_sla_combined', array(
		'id_report_content' => $id,
		'id_agent_module' => $id_module,
		'sla_max' => $sla_max,
		'sla_min' => $sla_min,
		'sla_limit' => $sla_limit,
		'server_name' => $server_name));
	
	if ($result === false) {
		$data['correct'] = 0;
	}
	else {
		$data['correct'] = 1;
		$data['id'] = $result;
	}
	
	echo json_encode($data);
	return;
}

if ($add_general) {
	$id_module = get_parameter('id_module', 0);
	$server_name = get_parameter('server_name_general', '');
	
	$result = db_process_sql_insert('treport_content_item', array(
		'id_report_content' => $id,
		'id_agent_module' => $id_module,
		'server_name' => $server_name));
	
	if ($result === false) {
		$data['correct'] = 0;
	}
	else {
		$data['correct'] = 1;
		$data['id'] = $result;
	}
	
	echo json_encode($data);
	return;
}

if ($get_custom_sql) {
	switch ($config["dbtype"]) {
		case "mysql":
			$sql = db_get_value_filter('`sql`', 'treport_custom_sql', array('id' => $id));
			break;
		case "postgresql":
			$sql = db_get_value_filter('"sql"', 'treport_custom_sql', array('id' => $id));
			break;
		case "oracle":
			$sql = db_get_value_filter('sql', 'treport_custom_sql', array('id' => $id));
			break;
	}
	
	if ($sql === false) {
		$data['correct'] = 0;
	}
	else {
		$data['correct'] = 1;
		$data['sql'] = $sql;
	}
	
	echo json_encode($data);
	return;
}

if ($truncate_text) {
	$text = get_parameter ('text', '');
	return ui_print_truncate_text ($text, GENERIC_SIZE_TEXT, true, false);
}

if ($get_metaconsole_hash_data) {
	$server_name = get_parameter('server_name');
	
	enterprise_include_once('include/functions_metaconsole.php');
	
	$server = enterprise_hook('metaconsole_get_connection', array($server_name));
	
	$pwd = $server["auth_token"]; // Create HASH login info
	$user = $config["id_user"];
	
	// Extract auth token from serialized field
	$pwd_deserialiced = json_decode($pwd, true);
	$hashdata = $user.$pwd_deserialiced['auth_token'];
	
	$hashdata = md5($hashdata);
	$url_hash = "&loginhash=auto&loginhash_data=$hashdata&loginhash_user=$user";
	
	echo $url_hash;
	return;
}

if ($get_metaconsole_server_url) {
	$server_name = get_parameter('server_name');
	
	enterprise_include_once('include/functions_metaconsole.php');
	
	$server = enterprise_hook('metaconsole_get_connection', array($server_name));
	
	echo $server["server_url"];
	return;
}


?>
