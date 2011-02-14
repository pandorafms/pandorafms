<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

function postgresql_connect_db($host = null, $db = null, $user = null, $pass = null) {
	global $config;
	
	if ($host === null)
		$host = $config["dbhost"];
	if ($db === null)
		$db = $config["dbname"];
	if ($user === null)
		$user = $config["dbuser"];
	if ($pass === null)
		$pass = $config["dbpass"];
	
	$config['dbconnection'] = pg_connect("host=" . $host . 
		" dbname=" . $db . 
		" user=" . $user . 
		" password=" . $pass);
	
	if (! $config['dbconnection']) {
		include ($config["homedir"]."/general/error_authconfig.php");
		exit;
	}
	
	return $config['dbconnection'];
}

function postgresql_get_db_all_rows_sql ($sql, $search_history_db = false, $cache = true) {
	global $config;
	
	$history = array ();
	
	// To disable globally SQL cache depending on global variable.
	// Used in several critical places like Metaconsole trans-server queries
	if (isset($config["dbcache"]))
		$cache = $config["dbcache"];
	
	// Read from the history DB if necessary
	if ($search_history_db) {
		$cache = false;
		$history = false;
		
		if (isset($config['history_db_connection']))
			$history = postgresql_process_sql ($sql, 'affected_rows', $config['history_db_connection'], false);
			
		if ($history === false) {
			$history = array ();
		}
	}
	
	$return = postgresql_process_sql ($sql, 'affected_rows', $config['dbconnection'], $cache);
	if ($return === false) {
		return false;
	}

	// Append result to the history DB data
	if (! empty ($return)) {
		foreach ($return as $row) {
			array_push ($history, $row);
		}
	}

	if (! empty ($history))
		return $history;
	//Return false, check with === or !==
	return false;
}

function postgresql_insert_id($dbconnection = '') {
	global $config;
	
	if ($dbconnection !== '') {
		$insert_query = pg_query($dbconnection, "SELECT lastval();");
		$insert_id = pg_fetch_row($insert_query);
		$result = $insert_row[0];
	}
	else {
		$insert_query = pg_query($config['dbconnection'], "SELECT lastval();");
		$insert_id = pg_fetch_row($insert_query);
		$result = $insert_row[0];
	}
	
	return $result;
}

function postgresql_process_sql($sql, $rettype = "affected_rows", $dbconnection = '', $cache = true) {
	global $config;
	global $sql_cache;
	
	$retval = array();
	
	if ($sql == '')
		return false;
		
	if ($cache && ! empty ($sql_cache[$sql])) {
		$retval = $sql_cache[$sql];
		$sql_cache['saved']++;
		add_database_debug_trace ($sql);
	}
	else {
		$start = microtime (true);
		if ($dbconnection !== '') {
			pg_send_query($dbconnection, $sql);
			$result = pg_get_result($dbconnection);
		}
		else {
			pg_send_query($config['dbconnection'], $sql);
			$result = pg_get_result($config['dbconnection']);
			
			debugPrint($sql);
			$insert_query = pg_query($config['dbconnection'], "SELECT LASTVAL();");
			$insert_id = pg_fetch_row($insert_query);
			debugPrint($insert_row[0]);
		}
		$time = microtime (true) - $start;
		if ($result === false) {
			$backtrace = debug_backtrace ();
			$error = sprintf ('%s (\'%s\') in <strong>%s</strong> on line %d',
				mysql_error (), $sql, $backtrace[0]['file'], $backtrace[0]['line']);
			add_database_debug_trace ($sql, mysql_error ());
			set_error_handler ('sql_error_handler');
			trigger_error ($error);
			restore_error_handler ();
			
			return false;
		}
		else {
			$status = pg_result_status($result);
			$rows = pg_affected_rows($result);
			
			if ($status !== 2) { //The query NOT IS a select
				if ($rettype == "insert_id") {
					$result = postgresql_insert_id($dbconnection);
				}
				elseif ($rettype == "info") {
					$result = pg_result_status($result, PGSQL_STATUS_STRING);
				}
				else {
					$rows = pg_affected_rows($result);
					$result = $rows;
				}
				add_database_debug_trace ($sql, $result, $rows,
						array ('time' => $time));
						
				return $result;	
			}
			else { //The query IS a select.
				add_database_debug_trace ($sql, 0, $rows, array ('time' => $time));
				while ($row = pg_fetch_assoc($result)) {
					array_push ($retval, $row);
				}
	
				if ($cache === true)
					$sql_cache[$sql] = $retval;
				pg_free_result ($result);
			}
		}
	}
	
	if (! empty ($retval)) {
		return $retval;
	}
	
	//Return false, check with === or !==
	return false;
}

/**
 * Get all the rows in a table of the database.
 * 
 * @param string Database table name.
 * @param string Field to order by.
 * @param string $order The type of order, by default 'ASC'.
 *
 * @return mixed A matrix with all the values in the table
 */
function postgresql_get_db_all_rows_in_table($table, $order_field = "", $order = 'ASC') {
	if ($order_field != "") {
		return get_db_all_rows_sql ('SELECT * FROM "'.$table.'" ORDER BY "'.$order_field . ' ' . $order);
	}
	else {	
		return get_db_all_rows_sql ('SELECT * FROM "'.$table.'"');
	}
}

/**
 * Inserts strings into database
 *
 * The number of values should be the same or a positive integer multiple as the number of rows
 * If you have an associate array (eg. array ("row1" => "value1")) you can use this function with ($table, array_keys ($array), $array) in it's options
 * All arrays and values should have been cleaned before passing. It's not neccessary to add quotes.
 *
 * @param string Table to insert into
 * @param mixed A single value or array of values to insert (can be a multiple amount of rows)
 *
 * @return mixed False in case of error or invalid values passed. Affected rows otherwise
 */
function postgresql_process_sql_insert($table, $values) {
	 //Empty rows or values not processed
	if (empty ($values))
		return false;
	
	$values = (array) $values;
		
	$query = sprintf ('INSERT INTO "%s" ', $table);
	$fields = array ();
	$values_str = '';
	$i = 1;
	$max = count ($values);
	foreach ($values as $field => $value) {
		//Add the correct escaping to values
		if ($field[0] != '"') {
			$field = '"' . $field . '"';
		}
		
		array_push ($fields, $field);
		
		if (is_null ($value)) {
			$values_str .= "NULL";
		}
		elseif (is_int ($value) || is_bool ($value)) {
			$values_str .= sprintf("%d", $value);
		}
		else if (is_float ($value) || is_double ($value)) {
			$values_str .= sprintf("%f", $value);
		}
		else {
			$values_str .= sprintf("'%s'", $value);
		}
		
		if ($i < $max) {
			$values_str .= ",";
		}
		$i++;
	}
	
	$query .= '(' . implode(', ', $fields) . ')';
	
	$query .= ' VALUES (' . $values_str . ')';
	
	return process_sql($query, 'insert_id');
}
?>