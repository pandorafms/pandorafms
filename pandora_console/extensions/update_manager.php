<?php

//Pandora FMS- http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

function load_update_manager_lib () {
	set_time_limit (0);
	require_once ('update_manager/load_updatemanager.php');
}

function update_settings_database_connection () {
	global $config;
	global $db;
	
	$db =& um_db_connect ('mysql', $config['dbhost'], $config['dbuser'],
			$config['dbpass'], $config['dbname']);
	um_db_update_setting ('dbname', $config['dbname']);
	um_db_update_setting ('dbuser', $config['dbuser']);
	um_db_update_setting ('dbpass', $config['dbpass']);
	um_db_update_setting ('dbhost', $config['dbhost']);
}

function pandora_update_manager_install () {
	global $config;
	global $db;
	
	if (isset ($config['update_manager_installed']))
		/* Already installed */
		return;
	
	load_update_manager_lib ();
	
	/* SQL installation */
	$sentences = file (EXTENSIONS_DIR.'/update_manager/sql/update_manager.sql');
	foreach ($sentences as $sentence) {
		$success = process_sql ($sentence);
		if ($success === false)
			return;
	}
	$sql = 'INSERT INTO `tconfig` (`token`, `value`)
		VALUES ("update_manager_installed", 1)';
	process_sql ($sql);
	
	$db =& um_db_connect ('mysql', $config['dbhost'], $config['dbuser'],
			$config['dbpass'], $config['dbname']);
	um_db_update_setting ('updating_code_path',
			dirname ($_SERVER['SCRIPT_FILENAME']));
	update_settings_database_connection ();
}

function pandora_update_manager_uninstall () {
	process_sql ('DELETE FROM `tconfig` WHERE `token` = "update_manager_installed"');
	process_sql ('DROP TABLE `tupdate_settings`');
	process_sql ('DROP TABLE `tupdate_journal`');
	process_sql ('DROP TABLE `tupdate`');
	process_sql ('DROP TABLE `tupdate_package`');
}

function pandora_update_manager_main () {
	global $config;
	global $db;
	
	load_update_manager_lib ();
	update_settings_database_connection ();
	
	require_once ('update_manager/main.php');
}

function pandora_update_manager_login () {
	global $config;
	global $db;
	
	// If first time, make the first autoupdate and disable it in DB
	if (!isset($config["autoupdate"])){
		$config["autoupdate"] = 1;
		process_sql ("INSERT INTO tconfig (token,value) VALUES ('autoupdate', 0)");
	}

	if ($config["autoupdate"] == 0)
		return;

	load_update_manager_lib ();
	
	$db =& um_db_connect ('mysql', $config['dbhost'], $config['dbuser'],
			$config['dbpass'], $config['dbname']);
	$settings = um_db_load_settings ();
	
	$user_key = get_user_key ($settings);
	
	$package = um_client_check_latest_update ($settings, $user_key);
	
	if (is_object ($package)) {
		echo '<div class="notify">';
		echo '<img src="images/information.png" alt="info" /> ';
		echo __('There\'s a new update for Pandora');
		echo '. <a href="index.php?sec=extensions&amp;sec2=extensions/update_manager">';
		echo __('More info');
		echo '</a>';
		echo '</div>';
	}
}

function pandora_update_manager_godmode () {
	global $config;
	global $db;
	
	load_update_manager_lib ();
	
	require_once ('update_manager/settings.php');
}

add_operation_menu_option (__('Update manager'));
add_godmode_menu_option (__('Update manager settings'), 'PM','gagente');
add_extension_main_function ('pandora_update_manager_main');
add_extension_godmode_function ('pandora_update_manager_godmode');
add_extension_login_function ('pandora_update_manager_login');

$db = NULL;

pandora_update_manager_install ();
?>
