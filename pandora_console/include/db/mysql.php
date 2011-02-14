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

function mysql_connect_db($host = null, $db = null, $user = null, $pass = null) {
	global $config;
	
	if ($host === null)
		$host = $config["dbhost"];
	if ($db === null)
		$db = $config["dbname"];
	if ($user === null)
		$user = $config["dbuser"];
	if ($pass === null)
		$pass = $config["dbpass"];
	
	// Non-persistent connection: This will help to avoid mysql errors like "has gone away" or locking problems
	// If you want persistent connections change it to mysql_pconnect(). 
	$config['dbconnection'] = mysql_connect($host, $user, $pass);
	mysql_select_db($db, $config['dbconnection']);
	
	if (! $config['dbconnection']) {
		include ($config["homedir"]."/general/error_authconfig.php");
		exit;
	}
	
	return $config['dbconnection'];
}

function mysql_get_db_all_rows_sql ($sql, $search_history_db = false, $cache = true) {
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
			$history = mysql_process_sql ($sql, 'affected_rows', $config['history_db_connection'], false);
			
		if ($history === false) {
			$history = array ();
		}
	}

	$return = mysql_process_sql ($sql, 'affected_rows', $config['dbconnection'], $cache);
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

function mysql_process_sql ($sql, $rettype = "affected_rows", $dbconnection = '', $cache = true) {
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
		if ($dbconnection == '') {
			$result = mysql_query ($sql);
		}
		else {
			$result = mysql_query ($sql, $dbconnection);
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
		elseif ($result === true) {
			if ($rettype == "insert_id") {
				$result = mysql_insert_id ();
			}
			elseif ($rettype == "info") {
				$result = mysql_info ();
			}
			else {
				$result = mysql_affected_rows ();
			}
			
			add_database_debug_trace ($sql, $result, mysql_affected_rows (),
				array ('time' => $time));
			return $result;
		}
		else {
			add_database_debug_trace ($sql, 0, mysql_affected_rows (), 
				array ('time' => $time));
			while ($row = mysql_fetch_assoc ($result)) {
				array_push ($retval, $row);
			}

			if ($cache === true)
				$sql_cache[$sql] = $retval;
			mysql_free_result ($result);
		}
	}
	
	if (! empty ($retval))
		return $retval;
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
function mysql_get_db_all_rows_in_table($table, $order_field = "", $order = 'ASC') {
	if ($order_field != "") {
		return get_db_all_rows_sql ("SELECT * FROM `".$table."` ORDER BY ".$order_field . " " . $order);
	}
	else {	
		return get_db_all_rows_sql ("SELECT * FROM `".$table."`");
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
function mysql_process_sql_insert($table, $values) {
	 //Empty rows or values not processed
	if (empty ($values))
		return false;
	
	$values = (array) $values;
		
	$query = sprintf ("INSERT INTO `%s` ", $table);
	$fields = array ();
	$values_str = '';
	$i = 1;
	$max = count ($values);
	foreach ($values as $field => $value) { //Add the correct escaping to values
		if ($field[0] != "`") {
			$field = "`".$field."`";
		}
		
		array_push ($fields, $field);
		
		if (is_null ($value)) {
			$values_str .= "NULL";
		}
		elseif (is_int ($value) || is_bool ($value)) {
			$values_str .= sprintf ("%d", $value);
		}
		else if (is_float ($value) || is_double ($value)) {
			$values_str .= sprintf ("%f", $value);
		}
		else {
			$values_str .= sprintf ("'%s'", $value);
		}
		
		if ($i < $max) {
			$values_str .= ",";
		}
		$i++;
	}
	
	$query .= '('.implode (', ', $fields).')';
	
	$query .= ' VALUES ('.$values_str.')';
	
	return process_sql ($query, 'insert_id');
}

/**
 * This function comes back with an array in case of SELECT
 * in case of UPDATE, DELETE etc. with affected rows
 * an empty array in case of SELECT without results
 * Queries that return data will be cached so queries don't get repeated
 *
 * @param string SQL statement to execute
 *
 * @param string What type of info to return in case of INSERT/UPDATE.
 *		'affected_rows' will return mysql_affected_rows (default value)
 *		'insert_id' will return the ID of an autoincrement value
 *		'info' will return the full (debug) information of a query
 *
 * @return mixed An array with the rows, columns and values in a multidimensional array or false in error
 */
function mysql_process_sql($sql, $rettype = "affected_rows", $dbconnection = '', $cache = true) {
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
		if ($dbconnection == '') {
			$result = mysql_query ($sql);
		}
		else {
			$result = mysql_query ($sql, $dbconnection);
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
		elseif ($result === true) {
			if ($rettype == "insert_id") {
				$result = mysql_insert_id ();
			}
			elseif ($rettype == "info") {
				$result = mysql_info ();
			}
			else {
				$result = mysql_affected_rows ();
			}
			
			add_database_debug_trace ($sql, $result, mysql_affected_rows (),
				array ('time' => $time));
			return $result;
		}
		else {
			add_database_debug_trace ($sql, 0, mysql_affected_rows (), 
				array ('time' => $time));
			while ($row = mysql_fetch_assoc ($result)) {
				array_push ($retval, $row);
			}

			if ($cache === true)
				$sql_cache[$sql] = $retval;
			mysql_free_result ($result);
		}
	}
	
	if (! empty ($retval))
		return $retval;
	//Return false, check with === or !==
	return false;
}
?>