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

/*
function extension_db_status_extension_tables() {
	return array(
		'tbackup',
		'tfiles_repo',
		'tfiles_repo_group',
		'tipam_ip',
		'tipam_network',
		'tuser_task',
		'tuser_task_scheduled',
		);
}
*/

function extension_db_status() {
	global $config;
	
	
	
	$db_user = get_parameter('db_user', '');
	$db_password = get_parameter('db_password', '');
	$db_host = get_parameter('db_host', '');
	$db_name = get_parameter('db_name', '');
	$db_status_execute = (bool)get_parameter('db_status_execute', false);
	
	
	ui_print_page_header (__("DB Status"),
		"images/extensions.png", false, "", true, "");
	
	
	if (! check_acl ($config['id_user'], 0, "DM")) {
		db_pandora_audit("ACL Violation",
			"Trying to access db status");
		require ("general/noaccess.php");
		return;
	}
	
	
	ui_print_info_message(
		__('This extension checks the DB is correct. Because sometimes the old DB from a migration has not some fields in the tables or the data is changed.'));
	ui_print_info_message(
		__('At the moment the checks is for MySQL/MariaDB.'));
	
	echo "<form method='post'>";
	
	echo "<fieldset>";
	echo "<legend>" . __('DB settings') . "</legend>";
	$table = null;
	$table->data = array();
	$row = array();
	$row[] = __("DB User with privileges");
	$row[] = html_print_input_text('db_user', $db_user, '', 50, 255, true);
	$row[] = __("DB Password for this user");
	$row[] = html_print_input_password('db_password', $db_password, '', 50, 255, true);
	$table->data[] = $row;
	$row = array();
	$row[] = __("DB Hostname");
	$row[] = html_print_input_text('db_host', $db_host, '', 50, 255, true);
	$row[] = __("DB Name (temporal for testing)");
	$row[] = html_print_input_text('db_name', $db_name, '', 50, 255, true);
	$table->data[] = $row;
	html_print_table($table);
	echo "</fieldset>";
	
	echo "<div style='text-align: right;'>";
	html_print_input_hidden('db_status_execute', 1);
	html_print_submit_button(__('Execute Test'), 'submit', false, 'class="sub"');
	echo "</div>";
	
	echo "</form>";
	
	if ($db_status_execute) {
		extension_db_status_execute_checks($db_user, $db_password,
			$db_host, $db_name);
	}
}


function extension_db_status_execute_checks($db_user, $db_password, $db_host, $db_name) {
	global $config;
	
	$connection_system = $config['dbconnection'];
	
	// Avoid SQL injection
	$db_name = io_safe_output($db_name);
	$db_name = str_replace(';', ' ', $db_name);
	$db_name = explode(" ", $db_name);
	$db_name = $db_name[0];
	
	$connection_test  = mysql_connect ($db_host, $db_user, $db_password);
	
	if (!$connection_test) {
		ui_print_error_message(
			__('Unsuccessful connected to the DB'));
	}
	else {
		$create_db = mysql_query ("CREATE DATABASE `$db_name`");
		
		if (!$create_db) {
			ui_print_error_message(
				__('Unsuccessful created the testing DB'));
		}
		else {
			mysql_select_db($db_name, $connection_test);
			
			$install_tables = extension_db_status_execute_sql_file(
				$config['homedir'] . "/pandoradb.sql",
				$connection_test);
			
			if (!$install_tables) {
				ui_print_error_message(
					__('Unsuccessful installed tables into the testing DB'));
			}
			else {
				if (enterprise_installed()) {
					$install_tables_enterprise =
						extension_db_status_execute_sql_file(
							$config['homedir'] . "/enterprise/pandoradb.sql",
							$connection_test);
					
					if (!$install_tables_enterprise) {
						ui_print_error_message(
							__('Unsuccessful installed enterprise tables into the testing DB'));
					}
				}
				
				extension_db_check_tables_differences(
					$connection_test,
					$connection_system,
					$db_name,
					$config['dbname']);
				//extension_db_check_data_differences();
			}
			
			mysql_select_db($db_name, $connection_test);
			mysql_query ("DROP DATABASE IF EXISTS `$db_name`");
		}
	}
}

function extension_db_check_tables_differences($connection_test,
	$connection_system, $db_name_test, $db_name_system) {
	
	global $config;
	
	// --------- Check the tables --------------------------------------
	mysql_select_db($db_name_test, $connection_test);
	$result = mysql_query("SHOW TABLES", $connection_test);
	$tables_test = array();
	while ($row = mysql_fetch_array ($result)) {
		$tables_test[] = $row[0];
	}
	mysql_free_result ($result);
	//~ $tables_test = array_merge($tables_test,
		//~ extension_db_status_extension_tables());
	
	
	mysql_select_db($db_name_system, $connection_system);
	$result = mysql_query("SHOW TABLES", $connection_system);
	$tables_system = array();
	while ($row = mysql_fetch_array ($result)) {
		$tables_system[] = $row[0];
	}
	mysql_free_result ($result);
	
	$diff_tables = array_diff($tables_test, $tables_system);
	
	//~ html_debug_print($tables_test);
	//~ html_debug_print($tables_system);
	//~ html_debug_print($diff_tables);
	
	ui_print_result_message(
		empty($diff_tables),
		__('Successful the DB Pandora has all tables'),
		__('Unsuccessful the DB Pandora has not all tables. The tables lost are (%s)',
			implode(", ", $diff_tables)));
	
	if (!empty($diff_tables)) {
		foreach ($diff_tables as $table) {
			mysql_select_db($db_name_test, $connection_test);
			$result = mysql_query("SHOW CREATE TABLE " . $table, $connection_test);
			$tables_test = array();
			while ($row = mysql_fetch_array ($result)) {
				ui_print_info_message(
					__('You can execute this SQL query for to fix.') . "<br />" .
					'<pre>' .
						$row[1] .
					'</pre>'
				);
			}
			mysql_free_result ($result);
		}
	}
	
	// --------------- Check the fields -------------------------------
	$correct_fields = true;
	
	foreach ($tables_system as $table) {
		
		mysql_select_db($db_name_test, $connection_test);
		$result = mysql_query("EXPLAIN " . $table, $connection_test);
		$fields_test = array();
		if (!empty($result)) {
			while ($row = mysql_fetch_array ($result)) {
				$fields_test[$row[0]] = array(
					'field ' => $row[0],
					'type' => $row[1],
					'null' => $row[2],
					'key' => $row[3],
					'default' => $row[4],
					'extra' => $row[5]);
			}
			mysql_free_result ($result);
		}
		
		
		
		mysql_select_db($db_name_system, $connection_system);
		$result = mysql_query("EXPLAIN " . $table, $connection_system);
		$fields_system = array();
		if (!empty($result)) {
			while ($row = mysql_fetch_array ($result)) {
				$fields_system[$row[0]] = array(
					'field ' => $row[0],
					'type' => $row[1],
					'null' => $row[2],
					'key' => $row[3],
					'default' => $row[4],
					'extra' => $row[5]);
			}
			mysql_free_result ($result);
		}
		
		foreach ($fields_test as $name_field => $field_test) {
			if (!isset($fields_system[$name_field])) {
				$correct_fields = false;
				
				ui_print_error_message(
					__('Unsuccessful the table %s has not the field %s',
					$table, $name_field));
				ui_print_info_message(
					__('You can execute this SQL query for to fix.') . "<br />" .
					'<pre>' .
						"ALTER TABLE " . $table . " ADD COLUMN " . $name_field . " text;" .
					'</pre>'
				);
			}
			else {
				$correct_fields = false;
				$field_system = $fields_system[$name_field];
				
				$diff = array_diff($field_test, $field_system);
				
				if (!empty($diff)) {
					foreach ($diff as $config_field => $value) {
						switch ($config_field) {
							case 'type':
								ui_print_error_message(
									__('Unsuccessful the field %s in the table %s must be setted the type with %s.',
									$name_field, $table, $value));
								ui_print_info_message(
									__('You can execute this SQL query for to fix.') . "<br />" .
									'<pre>' .
										"ALTER TABLE " . $table . " MODIFY COLUMN " . $name_field . " " . $value . ";" .
									'</pre>'
								);
								break;
							case 'null':
								ui_print_error_message(
									__('Unsuccessful the field %s in the table %s must be setted the null values with %s.',
									$name_field, $table, $value));
								if ($value == "no") {
									ui_print_info_message(
										__('You can execute this SQL query for to fix.') . "<br />" .
										'<pre>' .
											"ALTER TABLE " . $table . " MODIFY COLUMN " . $name_field . "INT NULL;" .
										'</pre>'
									);
								}
								else {
									ui_print_info_message(
										__('You can execute this SQL query for to fix.') . "<br />" .
										'<pre>' .
											"ALTER TABLE " . $table . " MODIFY COLUMN " . $name_field . "INT NOT NULL;" .
										'</pre>'
									);
								}
								
								break;
							case 'key':
								ui_print_error_message(
									__('Unsuccessful the field %s in the table %s must be setted the key as defined in the SQL file.',
									$name_field, $table));
								ui_print_info_message(
									__('Please check the SQL file for to know the kind of key needed.'));
								break;
							case 'default':
								ui_print_error_message(
									__('Unsuccessful the field %s in the table %s must be setted the default value as %s.',
									$name_field, $table, $value));
								ui_print_info_message(
									__('Please check the SQL file for to know the kind of default value needed.'));
								break;
							case 'extra':
								ui_print_error_message(
									__('Unsuccessful the field %s in the table %s must be setted as defined in the SQL file.',
									$name_field, $table));
								ui_print_info_message(
									__('Please check the SQL file for to know the kind of extra config needed.'));
								break;
						}
					}
				}
			}
		}
	}
	
	if ($correct_fields) {
		ui_print_success_message(
			__('Successful all the tables have the correct fields')
		);
	}
}

function extension_db_status_execute_sql_file($url, $connection) {
	if (file_exists($url)) {
		$file_content = file($url);
		$query = "";
		foreach ($file_content as $sql_line) {
			if (trim($sql_line) != "" && strpos($sql_line, "--") === false) {
				$query .= $sql_line;
				if (preg_match("/;[\040]*\$/", $sql_line)) {
					if (!$result = mysql_query($query, $connection)) {
						echo mysql_error(); //Uncomment for debug
						echo "<i><br>$query<br></i>";
						return 0;
					}
					$query = "";
				}
			}
		}
		return 1;
	}
	else
		return 0;
}

extensions_add_godmode_function('extension_db_status');
extensions_add_godmode_menu_option(__('DB Status'), 'DM', 'gextensions', null, "v1r1", 'gdbman');
?>