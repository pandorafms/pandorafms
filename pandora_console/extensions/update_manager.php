<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

if (is_ajax ()) {
	global $config;
	
	check_login ();
	
	if (! check_acl ($config["id_user"], 0, "PM")) {
		db_pandora_audit("ACL Violation",
			"Trying to access event viewer");
		require ("general/noaccess.php");
		return;
	}
	
	require_once('update_manager/lib/functions.ajax.php');
	
	$checking_online_enterprise_package =
		(bool)get_parameter('checking_online_enterprise_package', false);
	
	$get_license_info = get_parameter('get_license_info', 0);	
	
	if ($checking_online_enterprise_package) {
		checking_online_enterprise_package();
		
		return;
	}
		
	if ($get_license_info) {
	
		include_once("include/functions_db.php");
		enterprise_include_once('include/functions_license.php');

		enterprise_hook('license_show_info');
	}
	
	return;
}

function load_update_manager_lib () {
	set_time_limit (0);
	require_once ('update_manager/load_updatemanager.php');
}

function update_settings_database_connection () {
	global $config;
	
	um_db_connect ('mysql', $config['dbhost'], $config['dbuser'],
		$config['dbpass'], $config['dbname']);
	um_db_update_setting ('dbname', $config['dbname']);
	um_db_update_setting ('dbuser', $config['dbuser']);
	um_db_update_setting ('dbpass', $config['dbpass']);
	um_db_update_setting ('dbhost', $config['dbhost']);
}

function pandora_update_manager_install () {
	global $config;
	
	load_update_manager_lib ();
	
	/* SQL installation */
	switch ($config['dbtype']) {
		case 'mysql':
			$sentences = file (EXTENSIONS_DIR.'/update_manager/sql/update_manager.sql');
			break;
		case 'postgresql':
			$sentences = file (EXTENSIONS_DIR.'/update_manager/sql/update_manager.postgreSQL.sql');
			break;
		case 'oracle':
			$sentences = file (EXTENSIONS_DIR.'/update_manager/sql/update_manager.oracle.sql');
			break;
	}
	foreach ($sentences as $sentence) {
		$success = db_process_sql ($sentence);
		if ($success === false)
			return;
	}
	
	$values = array("token" => "update_manager_installed",
		"value" => 1);
	db_process_sql_insert('tconfig', $values);
	
	um_db_connect ('mysql', $config['dbhost'], $config['dbuser'],
		$config['dbpass'], $config['dbname']);
	um_db_update_setting ('updating_code_path',
		dirname ($_SERVER['SCRIPT_FILENAME']));
	update_settings_database_connection ();
}

function pandora_update_manager_uninstall () {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			db_process_sql ('DELETE FROM `tconfig`
				WHERE `token` = "update_manager_installed"');
			db_process_sql ('DROP TABLE `tupdate_settings`');
			db_process_sql ('DROP TABLE `tupdate_journal`');
			db_process_sql ('DROP TABLE `tupdate`');
			db_process_sql ('DROP TABLE `tupdate_package`');
			break;
		case "postgresql":
			db_process_sql ('DELETE FROM "tconfig"
				WHERE "token" = \'update_manager_installed\'');
			db_process_sql ('DROP TABLE "tupdate_settings"');
			db_process_sql ('DROP TABLE "tupdate_journal"');
			db_process_sql ('DROP TABLE "tupdate"');
			db_process_sql ('DROP TABLE "tupdate_package"');
			break;
		case "oracle":
			db_process_sql ('DELETE FROM tconfig
				WHERE token = \'update_manager_installed\'');
			db_process_sql ('DROP TABLE tupdate_settings');
			db_process_sql ('DROP TABLE tupdate_journal');
			db_process_sql ('DROP TABLE tupdate');
			db_process_sql ('DROP TABLE tupdate_package');
			break;
	}
}

function pandora_update_manager_main () {
	global $config;
	
	if (! check_acl($config['id_user'], 0, "PM")) {
		require ("general/noaccess.php");
		return;
	}
	
	load_update_manager_lib ();
	update_settings_database_connection ();
	
	require_once ('update_manager/main.php');
	
	main_view();
}

function pandora_update_manager_login () {
	global $config;
	
	if ($config["autoupdate"] == 0)
		return;
	
	unset($_SESSION['new_update']);
	
	if (enterprise_installed()) {
		um_db_connect ('mysql', $config['dbhost'], $config['dbuser'],
			$config['dbpass'], $config['dbname']);
		$settings = um_db_load_settings ();
		
		$user_key = get_user_key ($settings);
		
		$package = um_client_check_latest_update ($settings, $user_key);
		
		if (is_object ($package)) {
			if ($package->id != 'ERROR_NON_NUMERIC_FOUND') {
				$_SESSION['new_update'] = 'new';
			}
		}
	}
	else {
		require(
			"extensions/update_manager/lib/functions.ajax.php");
		
		$result = update_pandora_get_packages_online_ajax(false);
		
		if ($result['correct']) {
			$_SESSION['new_update'] = 'new';
		}
	}
}

function pandora_update_manager_godmode () {
	global $config;
	
	load_update_manager_lib ();
	
	require_once ('update_manager/settings.php');
}

extensions_add_operation_menu_option (__('Update manager'));
extensions_add_godmode_menu_option (__('Update manager settings'), 'PM');
extensions_add_main_function ('pandora_update_manager_main');
extensions_add_godmode_function ('pandora_update_manager_godmode');
extensions_add_login_function ('pandora_update_manager_login');

pandora_update_manager_install ();

$db = NULL;
?>
