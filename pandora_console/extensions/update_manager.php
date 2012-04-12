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

		$Alphabet = array_merge (range ('A','Z'), range('0', '9'));
		$AlphabetSize = sizeof ($Alphabet);

		// Return the order of the given char in ('A'..'Z', 0-9)
		function char_ord ($char) {

				$ascii_ord = ord ($char);
				if ($ascii_ord >= ord ('A') && $ascii_ord <= ord ('Z')) {
						return $ascii_ord - ord ('A');
				}
				if ($ascii_ord >= ord ('0') && $ascii_ord <= ord ('9')) {
						return sizeof (range ('A','Z')) + $ascii_ord - ord ('0');
				}

				return -1;
		}

		// Generate a random string of the given length
		function random_string ($length) {
				global $Alphabet, $AlphabetSize;

				$random_string = '';
				for ($i = 0; $i < $length; $i++) {
						$random_string .= $Alphabet[rand (0, $AlphabetSize - 1)];
				}

				return $random_string;
		}
		
		// Shift a string given a key: shifted_string[i] = string[i] + key[i] mod |Alphabet|
		function shift_string ($string, $key) {
				global $Alphabet, $AlphabetSize;

				// Get the minimum length
				$string_length = strlen ($string);
				$key_length = strlen ($key);
				$min_length = $string_length < $key_length ? $string_length : $key_length;

				// Shift the string
				$shifted_string = '';
				for ($i = 0; $i < $min_length; $i++) {
						$shifted_string .= $Alphabet[(char_ord ($string[$i]) + char_ord ($key[$i])) % $AlphabetSize];
				}

				return $shifted_string;
		}
		// Un-shift a string given a key: string[i] = shifted_string[i] - key[i] mod |Alphabet|
		function unshift_string ($string, $key) {
				global $Alphabet, $AlphabetSize;

				// Get the minimum length
				$string_length = strlen ($string);
				$key_length = strlen ($key);
				$min_length = $string_length < $key_length ? $string_length : $key_length;

				// Shift the string
				$unshifted_string = '';
				for ($i = 0; $i < $min_length; $i++) {
						$unshifted_string .= $Alphabet[($AlphabetSize + char_ord ($string[$i]) - char_ord ($key[$i])) % $AlphabetSize];
				}

				return $unshifted_string;
		}

		function check_pandora_license ($license) {

				if (strlen ($license) != 32) {
						return array ("Invalid license!", '', '', '', '', '', '');
				}

				$company_name = trim (substr ($license, 0, 4), "0");
				$random_string = substr ($license, 4, 8);
				$max_agents = (int) unshift_string (substr ($license, 12, 6), $random_string);
				$license_mode_string = unshift_string (substr ($license, 18, 6), $random_string);
				$license_mode = (int) substr ($license_mode_string, 0, 1);
				$expiry_date_string = unshift_string (substr ($license, 24, 8), $random_string);
				$expiry_year = substr ($expiry_date_string, 0, 4);
				$expiry_month = substr ($expiry_date_string, 4, 2);
				$expiry_day = substr ($expiry_date_string, 6, 2);
				return array ("Valid license.", $company_name, $max_agents, $expiry_day, $expiry_month, $expiry_year, $license_mode);
		}

		$license = db_get_value_sql ('SELECT value FROM tupdate_settings WHERE `key`="customer_key"');
		
		if ($license === false) {
			echo "<p>License not available</p>";
			return;
		}

		$license_info = array();	
		$license_info = check_pandora_license($license);
		
		$table->width = '98%';
		$table->data = array ();

		$table->data[0][0] = '<strong>'.__('Company').'</strong>';
		$table->data[0][1] = $license_info[1];
		$table->data[1][0] = '<strong>'.__('Expires').'</strong>';
		$table->data[1][1] = $license_info[3] . ' / ' . $license_info[4] . ' / ' . $license_info[5];
		$table->data[2][0] = '<strong>'.__('Platform Limit').'</strong>';
		$table->data[2][1] = $license_info[2];
		$table->data[3][0] = '<strong>'.__('Current Platform Count').'</strong>';
		$count_agents = db_get_value_sql ('SELECT count(*) FROM tagente');
		$table->data[3][1] = $count_agents;
		$table->data[4][0] = '<strong>'.__('License Mode').'</strong>';
		if ($license_info[6] == 1)
			$license_mode_string = 'Client';
		else
			$license_mode_string = 'Trial';
		$table->data[4][1] = $license_mode_string;	
		
		html_print_table ($table);
		
		return;	
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
			if ($package->id != 'ERROR_NON_NUMERIC_FOUND')
				$_SESSION['new_update'] = 'new';
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

if(isset($config['id_user'])) {
	if (check_acl($config['id_user'], 0, "PM")) {
		extensions_add_operation_menu_option (__('Update manager'));
		extensions_add_godmode_menu_option (__('Update manager settings'), 'PM');
		extensions_add_main_function ('pandora_update_manager_main');
		extensions_add_godmode_function ('pandora_update_manager_godmode');
		extensions_add_login_function ('pandora_update_manager_login');
	}
}

pandora_update_manager_install ();

$db = NULL;
?>
