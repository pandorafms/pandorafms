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

/**
 * @package Include
 * @subpackage DataBase
 */

include_once($config['homedir'] . "/include/functions_extensions.php");
include_once($config['homedir'] . "/include/functions_groups.php");
include_once($config['homedir'] . "/include/functions_agents.php");
include_once($config['homedir'] . "/include/functions_modules.php");
include_once($config['homedir'] . "/include/functions_alerts.php");
include_once($config['homedir'] . '/include/functions_users.php');

function db_select_engine() {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			require_once ($config['homedir'] . '/include/db/mysql.php');
			break;
		case "postgresql":
			require_once ($config['homedir'] . '/include/db/postgresql.php');
			break;
		case "oracle":
			require_once ($config['homedir'] . '/include/db/oracle.php');
			break;
	}
}

function db_connect($host = null, $db = null, $user = null, $pass = null, $history = null) {
	global $config;

	switch ($config["dbtype"]) {
		case "mysql": 
			return mysql_connect_db($host, $db, $user, $pass, $history);
			break;
		case "postgresql":
			return postgresql_connect_db($host, $db, $user, $pass, $history);
			break;
		case "oracle":
			return oracle_connect_db($host, $db, $user, $pass, $history);
			break;
	}
}

/**
 * When you delete (with the function "db_process_sql_delete" or other) any row in
 * any table, some times the cache save the data just deleted, because you
 * must use "db_clean_cache".
 */

/**
 *
 * Escape string to set it properly to use in sql queries
 *
 * @param string String to be cleaned.
 *
 * @return string String cleaned.
 */
function db_escape_string_sql($string) {
	global $config;

	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_escape_string_sql($string);
			break;
		case "postgresql":
			return postgresql_escape_string_sql($string);
			break;
		case "oracle":
			return oracle_escape_string_sql($string);
			break;
	}
}

/**
 * Adds an audit log entry (new function in 3.0)
 *
 * @param string $accion Action description
 * @param string $descripcion Long action description
 * @param string $id User id, by default is the user that login.
 * @param string $ip The ip to make the action, by default is $_SERVER['REMOTE_ADDR'] or $config["remote_addr"]
 * @param string $info The extended info for enterprise audit, by default is empty string.
 *
 * @return int Return the id of row in tsesion or false in case of fail.
 */
function db_pandora_audit($accion, $descripcion, $user_id = false, $ip = false, $info = '') {
	global $config;

	if ($ip !== false) {
		if (isset($config["remote_addr"])) {
			$ip = $config["remote_addr"];
				
		}
		else {
			if ($_SERVER['REMOTE_ADDR']) {
				$ip = $_SERVER['REMOTE_ADDR'];
			}
			else {
				$ip = null;
			}
		}
	}

	if ($user_id !== false) {
		$id = $user_id;
	}
	else {
		if (isset($config["id_user"])) {
			$id = $config["id_user"];
		}
		else $id = 0;
	}

	$accion = io_safe_input($accion);
	$descripcion = io_safe_input($descripcion);

	switch ($config['dbtype']){	
		case "mysql":
		case "postgresql":
			$values = array('id_usuario' => $id,
				'accion' => $accion,
				'ip_origen' => $ip,
				'descripcion' => $descripcion,
				'fecha' => date('Y-m-d H:i:s'),
				'utimestamp' => time());
			break;
		case "oracle":
			$values = array('id_usuario' => $id,
				'accion' => $accion,
				'ip_origen' => $ip,
				'descripcion' => $descripcion,
				'fecha' => '#to_date(\'' . date('Y-m-d H:i:s') . '\',\'YYYY-MM-DD HH24:MI:SS\')',
				'utimestamp' => time());
			break;
	}
	$id_audit = db_process_sql_insert('tsesion', $values);

	enterprise_include_once('include/functions_audit.php');
	enterprise_hook('audit_pandora_enterprise', array($id_audit, $info));

	return $id_audit;
}



/**
 * Log in a user into Pandora.
 *
 * @param string $id_user User id
 * @param string $ip Client user IP address.
 */
function db_logon ($id_user, $ip) {
	db_pandora_audit("Logon", "Logged in", $id_user, $ip);

	// Update last registry of user to set last logon. How do we audit when the user was created then?
	process_user_contact ($id_user);
}

/**
 * Log out a user into Pandora.
 *
 * @param string $id_user User id
 * @param string $ip Client user IP address.
 */
function db_logoff ($id_user, $ip) {
	db_pandora_audit("Logoff", "Logged out", $id_user, $ip);
}

$sql_cache = array ('saved' => 0);

/**
 * Get the first value of the first row of a table in the database.
 *
 * @param string Field name to get
 * @param string Table to retrieve the data
 * @param string Field to filter elements
 * @param string Condition the field must have
 *
 * @return mixed Value of first column of the first row. False if there were no row.
 */
function db_get_value($field, $table, $field_search = 1, $condition = 1, $search_history_db = false) {
	global $config;

	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_get_value($field, $table, $field_search, $condition, $search_history_db);
			break;
		case "postgresql":
			return postgresql_db_get_value($field, $table, $field_search, $condition, $search_history_db);
			break;
		case "oracle":
			return oracle_db_get_value($field, $table, $field_search, $condition, $search_history_db);
			break;
	}
}

/**
 * Get the first value of the first row of a table in the database from an
 * array with filter conditions.
 *
 * Example:
 * <code>
 * db_get_value_filter ('name', 'talert_templates',
 * array ('value' => 2, 'type' => 'equal'));
 * // Equivalent to:
 * // SELECT name FROM talert_templates WHERE value = 2 AND type = 'equal' LIMIT 1
 *
 * db_get_value_filter ('description', 'talert_templates',
 * array ('name' => 'My alert', 'type' => 'regex'), 'OR');
 * // Equivalent to:
 * // SELECT description FROM talert_templates WHERE name = 'My alert' OR type = 'equal' LIMIT 1
 * </code>
 *
 * @param string Field name to get
 * @param string Table to retrieve the data
 * @param array Conditions to filter the element. See db_format_array_where_clause_sql()
 * for the format
 * @param string Join operator for the elements in the filter.
 *
 * @return mixed Value of first column of the first row. False if there were no row.
 */
function db_get_value_filter ($field, $table, $filter, $where_join = 'AND') {
	global $config;

	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_get_value_filter($field, $table, $filter, $where_join);
			break;
		case "postgresql":
			return postgresql_db_get_value_filter($field, $table, $filter, $where_join);
			break;
		case "oracle":
			return oracle_db_get_value_filter($field, $table, $filter, $where_join);
			break;
	}
}

/**
 * Get the first value of the first row of a table result from query.
 *
 * @param string SQL select statement to execute.
 *
 * @return the first value of the first row of a table result from query.
 *
 */
function db_get_value_sql($sql) {
	global $config;

	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_get_value_sql($sql);
			break;
		case "postgresql":
			return postgresql_db_get_value_sql($sql);
			break;
		case "oracle":
			return oracle_db_get_value_sql($sql);
			break;
	}
}

/**
 * Get the first row of an SQL database query.
 *
 * @param string SQL select statement to execute.
 *
 * @return mixed The first row of the result or false
 */
function db_get_row_sql($sql, $search_history_db = false) {
	global $config;

	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_get_row_sql($sql, $search_history_db);
			break;
		case "postgresql":
			return postgresql_db_get_row_sql($sql, $search_history_db);
			break;
		case "oracle":
			return oracle_db_get_row_sql($sql, $search_history_db);
			break;
			
	}
}

/**
 * Get the first row of a database query into a table.
 *
 * The SQL statement executed would be something like:
 * "SELECT (*||$fields) FROM $table WHERE $field_search = $condition"
 *
 * @param string Table to get the row
 * @param string Field to filter elements
 * @param string Condition the field must have.
 * @param mixed Fields to select (array or string or false/empty for *)
 *
 * @return mixed The first row of a database query or false.
 */
function db_get_row ($table, $field_search, $condition, $fields = false) {
	global $config;

	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_get_row($table, $field_search, $condition, $fields);
			break;
		case "postgresql":
			return postgresql_db_get_row($table, $field_search, $condition, $fields);
			break;
		case "oracle":
			return oracle_db_get_row($table, $field_search, $condition, $fields);
			break;
	}
}

/**
 * Get the row of a table in the database using a complex filter.
 *
 * @param string Table to retrieve the data (warning: not cleaned)
 * @param mixed Filters elements. It can be an indexed array
 * (keys would be the field name and value the expected value, and would be
 * joined with an AND operator) or a string, including any SQL clause (without
 * the WHERE keyword). Example:
 <code>
 Both are similars:
 db_get_row_filter ('table', array ('disabled', 0));
 db_get_row_filter ('table', 'disabled = 0');

 Both are similars:
 db_get_row_filter ('table', array ('disabled' => 0, 'history_data' => 0), 'name, description', 'OR');
 db_get_row_filter ('table', 'disabled = 0 OR history_data = 0', 'name, description');
 db_get_row_filter ('table', array ('disabled' => 0, 'history_data' => 0), array ('name', 'description'), 'OR');
 </code>
 * @param mixed Fields of the table to retrieve. Can be an array or a coma
 * separated string. All fields are retrieved by default
 * @param string Condition to join the filters (AND, OR).
 *
 * @return mixed Array of the row or false in case of error.
 */
function db_get_row_filter($table, $filter, $fields = false, $where_join = 'AND') {
	global $config;

	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_get_row_filter($table, $filter, $fields, $where_join);
			break;
		case "postgresql":
			return postgresql_db_get_row_filter($table, $filter, $fields, $where_join);
			break;
		case "oracle":
			return oracle_db_get_row_filter($table, $filter, $fields, $where_join);
			break;
	}
}

/**
 * Get a single field in the databse from a SQL query.
 *
 * @param string SQL statement to execute
 * @param mixed Field number or row to get, beggining by 0. Default: 0
 *
 * @return mixed The selected field of the first row in a select statement.
 */

function db_get_sql ($sql, $field = 0, $search_history_db = false) {
	$result = db_get_all_rows_sql ($sql, $search_history_db);

	if($result === false)
	return false;

	$ax = 0;
	foreach ($result[0] as $f){
		if ($field == $ax)
		return $f;
		$ax++;
	}
}

/**
 * Get all the result rows using an SQL statement.
 *
 * @param string SQL statement to execute.
 * @param bool If want to search in history database also
 * @param bool If want to use cache (true by default)
 *
 * @return mixed A matrix with all the values returned from the SQL statement or
 * false in case of empty result
 */
function db_get_all_rows_sql($sql, $search_history_db = false, $cache = true) {
	global $config;

	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_get_all_rows_sql($sql, $search_history_db, $cache);
			break;
		case "postgresql":
			return postgresql_db_get_all_rows_sql($sql, $search_history_db, $cache);
			break;
		case "oracle":
			return oracle_db_get_all_rows_sql($sql, $search_history_db, $cache);
			break;
	}
}

/**
 * Get all the rows of a table in the database that matches a filter.
 *
 * @param string Table to retrieve the data (warning: not cleaned)
 * @param mixed Filters elements. It can be an indexed array
 * (keys would be the field name and value the expected value, and would be
 * joined with an AND operator) or a string, including any SQL clause (without
 * the WHERE keyword). Example:
 * <code>
 * Both are similars:
 * db_get_all_rows_filter ('table', array ('disabled', 0));
 * db_get_all_rows_filter ('table', 'disabled = 0');
 *
 * Both are similars:
 * db_get_all_rows_filter ('table', array ('disabled' => 0, 'history_data' => 0), 'name', 'OR');
 * db_get_all_rows_filter ('table', 'disabled = 0 OR history_data = 0', 'name');
 * </code>
 * @param mixed Fields of the table to retrieve. Can be an array or a coma
 * separated string. All fields are retrieved by default
 * @param string Condition of the filter (AND, OR).
 * @param bool $returnSQL Return a string with SQL instead the data, by default false.
 *
 * @return mixed Array of the row or false in case of error.
 */
function db_get_all_rows_filter($table, $filter = array(), $fields = false, $where_join = 'AND', $search_history_db = false, $returnSQL = false) {
	global $config;

	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_get_all_rows_filter($table, $filter, $fields, $where_join, $search_history_db, $returnSQL);
			break;
		case "postgresql":
			return postgresql_db_get_all_rows_filter($table, $filter, $fields, $where_join, $search_history_db, $returnSQL);
			break;
		case "oracle":
			return oracle_db_get_all_rows_filter($table, $filter, $fields, $where_join, $search_history_db, $returnSQL);
			break;
	}
}

/**
 * Get row by row the DB by SQL query. The first time pass the SQL query and
 * rest of times pass none for iterate in table and extract row by row, and
 * the end return false.
 *
 * @param bool $new Default true, if true start to query.
 * @param resource $result The resource of mysql for access to query.
 * @param string $sql
 * @return mixed The row or false in error.
 */
function db_get_all_row_by_steps_sql($new = true, &$result, $sql = null) {
	global $config;

	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_get_all_row_by_steps_sql($new, $result, $sql);
			break;
		case "postgresql":
			return postgresql_db_get_all_row_by_steps_sql($new, $result, $sql);
			break;
		case "oracle":
			return oracle_db_get_all_row_by_steps_sql($new, $result, $sql);
			break;
	}
}

/**
 * Return the count of rows of query.
 *
 * @param $sql
 * @return integer The count of rows of query.
 */
function db_get_num_rows($sql) {
	global $config;

	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_get_num_rows($sql);
			break;
		case "postgresql":
			return postgresql_db_get_num_rows($sql);
			break;
		case "oracle":
			return oracle_db_get_num_rows($sql);
			break;
	}
}

/**
 * Error handler function when an SQL error is triggered.
 *
 * @param int Level of the error raised (not used, but required by set_error_handler()).
 * @param string Contains the error message.
 *
 * @return bool True if error level is lower or equal than errno.
 */
function db_sql_error_handler ($errno, $errstr) {
	global $config;

	/* If debug is activated, this will also show the backtrace */
	if (ui_debug ($errstr))
		return false;

	if (error_reporting () <= $errno)
		return false;

	echo "<strong>SQL error</strong>: ".$errstr."<br />\n";

	return true;
}

/**
 * Add a database query to the debug trace.
 *
 * This functions does nothing if the config['debug'] flag is not set. If a
 * sentence was repeated, then the 'saved' counter is incremented.
 *
 * @param string SQL sentence.
 * @param mixed Query result. On error, error string should be given.
 * @param int Affected rows after running the query.
 * @param mixed Extra parameter for future values.
 */
function db_add_database_debug_trace ($sql, $result = false, $affected = false, $extra = false) {
	global $config;

	if (! isset ($config['debug']))
	return false;

	if (! isset ($config['db_debug']))
	$config['db_debug'] = array ();

	if (isset ($config['db_debug'][$sql])) {
		$config['db_debug'][$sql]['saved']++;
		return;
	}

	$var = array ();
	$var['sql'] = $sql;
	$var['result'] = $result;
	$var['affected'] = $affected;
	$var['saved'] = 0;
	$var['extra'] = $extra;

	$config['db_debug'][$sql] = $var;
}

/**
 * Clean the cache for to have errors and ghost rows when you do "select <table>",
 * "delete <table>" and "select <table>".
 *
 * @return None
 */
function db_clean_cache() {
	global $sql_cache;

	$sql_cache = array ('saved' => 0);
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
 * @param string $status The status and type of query (support only postgreSQL).
 *
 * @param bool $autocommit (Only oracle) Set autocommit transaction mode true/false 
 *
 * @return mixed An array with the rows, columns and values in a multidimensional array or false in error
 */
function db_process_sql($sql, $rettype = "affected_rows", $dbconnection = '', $cache = true, &$status = null, $autocommit = true) {
	global $config;

	switch ($config["dbtype"]) {
		case "mysql":
			return @mysql_db_process_sql($sql, $rettype, $dbconnection, $cache);
			break;
		case "postgresql":
			return @postgresql_db_process_sql($sql, $rettype, $dbconnection, $cache, $status);
			break;
		case "oracle":
			return @oracle_db_process_sql($sql, $rettype, $dbconnection, $cache, $status, $autocommit);
			break;			
	}
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
function db_get_all_rows_in_table ($table, $order_field = "", $order = 'ASC') {
	global $config;

	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_get_all_rows_in_table($table, $order_field, $order);
			break;
		case "postgresql":
			return postgresql_db_get_all_rows_in_table($table, $order_field, $order);
			break;
		case "oracle":
			return oracle_db_get_all_rows_in_table($table, $order_field, $order);
			break;
	}
}

/**
 * Get all the rows in a table of the databes filtering from a field.
 *
 * @param string Database table name.
 * @param string Field of the table.
 * @param string Condition the field must have to be selected.
 * @param string Field to order by.
 *
 * @return mixed A matrix with all the values in the table that matches the condition in the field or false
 */
function db_get_all_rows_field_filter($table, $field, $condition, $order_field = "") {
	global $config;

	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_get_all_rows_field_filter($table, $field, $condition, $order_field);
			break;
		case "postgresql":
			return postgresql_db_get_all_rows_field_filter($table, $field, $condition, $order_field);
			break;
		case "oracle":
			return oracle_db_get_all_rows_field_filter($table, $field, $condition, $order_field);
			break;
	}
}

/**
 * Get all the rows in a table of the databes filtering from a field.
 *
 * @param string Database table name.
 * @param string Field of the table.
 *
 * @return mixed A matrix with all the values in the table that matches the condition in the field
 */
function db_get_all_fields_in_table($table, $field = '', $condition = '', $order_field = '') {
	global $config;

	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_get_all_fields_in_table($table, $field, $condition, $order_field);
			break;
		case "postgresql":
			return postgresql_db_get_all_fields_in_table($table, $field, $condition, $order_field);
			break;
		case "oracle":
			return oracle_db_get_all_fields_in_table($table, $field, $condition, $order_field);
			break;
	}
}

/**
 * Formats an array of values into a SQL string.
 *
 * This function is useful to generate an UPDATE SQL sentence from a list of
 * values. Example code:
 *
 * <code>
 * $values = array ();
 * $values['name'] = "Name";
 * $values['description'] = "Long description";
 * $sql = 'UPDATE table SET '.db_format_array_to_update_sql ($values).' WHERE id=1';
 * echo $sql;
 * </code>
 * Will return:
 * <code>
 * UPDATE table SET `name` = "Name", `description` = "Long description" WHERE id=1
 * </code>
 *
 * @param array Values to be formatted in an array indexed by the field name.
 *
 * @return string Values joined into an SQL string that can fits into an UPDATE
 * sentence.
 */
function db_format_array_to_update_sql($values) {
	global $config;

	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_format_array_to_update_sql($values);
			break;
		case "postgresql":
			return postgresql_db_format_array_to_update_sql($values);
			break;
		case "oracle":
			return oracle_db_format_array_to_update_sql($values);
			break;
	}
}

/**
 * Formats an array of values into a SQL where clause string.
 *
 * This function is useful to generate a WHERE clause for a SQL sentence from
 * a list of values. Example code:
 <code>
 $values = array ();
 $values['name'] = "Name";
 $values['description'] = "Long description";
 $values['limit'] = $config['block_size']; // Assume it's 20
 $sql = 'SELECT * FROM table WHERE '.db_format_array_where_clause_sql ($values);
 echo $sql;
 </code>
 * Will return:
 * <code>
 * SELECT * FROM table WHERE `name` = "Name" AND `description` = "Long description" LIMIT 20
 * </code>
 *
 * @param array Values to be formatted in an array indexed by the field name.
 * There are special parameters such as 'limit' and 'offset' that will be used
 * as ORDER, LIMIT and OFFSET clauses respectively. Since LIMIT and OFFSET are
 * numerics, ORDER can receive a field name or a SQL function and a the ASC or
 * DESC clause. Examples:
 <code>
 $values = array ();
 $values['value'] = 10;
 $sql = 'SELECT * FROM table WHERE '.db_format_array_where_clause_sql ($values);
 // SELECT * FROM table WHERE VALUE = 10

 $values = array ();
 $values['value'] = 10;
 $values['order'] = 'name DESC';
 $sql = 'SELECT * FROM table WHERE '.db_format_array_where_clause_sql ($values);
 // SELECT * FROM table WHERE VALUE = 10 ORDER BY name DESC

 </code>
 * @param string Join operator. AND by default.
 * @param string A prefix to be added to the string. It's useful when limit and
 * offset could be given to avoid this cases:
 <code>
 $values = array ();
 $values['limit'] = 10;
 $values['offset'] = 20;
 $sql = 'SELECT * FROM table WHERE '.db_format_array_where_clause_sql ($values);
 // Wrong SQL: SELECT * FROM table WHERE LIMIT 10 OFFSET 20

 $values = array ();
 $values['limit'] = 10;
 $values['offset'] = 20;
 $sql = 'SELECT * FROM table WHERE '.db_format_array_where_clause_sql ($values, 'AND', 'WHERE');
 // Good SQL: SELECT * FROM table LIMIT 10 OFFSET 20

 $values = array ();
 $values['value'] = 5;
 $values['limit'] = 10;
 $values['offset'] = 20;
 $sql = 'SELECT * FROM table WHERE '.db_format_array_where_clause_sql ($values, 'AND', 'WHERE');
 // Good SQL: SELECT * FROM table WHERE value = 5 LIMIT 10 OFFSET 20
 </code>
 *
 * @return string Values joined into an SQL string that can fits into the WHERE
 * clause of an SQL sentence.

 // IMPORTANT!!! OFFSET parameter is not allowed for Oracle because Oracle needs to recode the complete query. 
 // use oracle_format_query() function instead.
 */
function db_format_array_where_clause_sql ($values, $join = 'AND', $prefix = false) {
	global $config;

	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_format_array_where_clause_sql($values, $join, $prefix);
			break;
		case "postgresql":
			return postgresql_db_format_array_where_clause_sql($values, $join, $prefix);
			break;
		case "oracle":
			return oracle_db_format_array_where_clause_sql($values, $join, $prefix);
			break;
	}
}

/**
 * Delete query without commit transaction
 *
 * @param string Table name
 * @param string Field of the filter condition
 * @param string Value of the filter
 *
 * @result Rows deleted or false if something goes wrong
 */
function db_process_delete_temp ($table, $row, $value) {
	global $error; //Globalize the errors variable
	global $config;

	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":
			$result = db_process_sql_delete ($table, $row.' = '.$value);
			break;
		case "oracle":
			if (is_int ($value) || is_bool ($value) || is_float ($value) || is_double ($value)) {
				$result = oracle_db_process_sql_delete_temp ($table, $row . ' = ' . $value);
			}	
			else {
				$result = oracle_db_process_sql_delete_temp ($table, $row . " = '" . $value . "'");
			}
			break;			
	}

	if ($result === false) {
		$error = true;
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
 * @param bool Whether to do autocommit or not (only Oracle)
 *
 * @return mixed False in case of error or invalid values passed. Affected rows otherwise
 */
function db_process_sql_insert($table, $values, $autocommit = true) {
	global $config;

	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_process_sql_insert($table, $values);
			break;
		case "postgresql":
			return postgresql_db_process_sql_insert($table, $values);
			break;
		case "oracle":
			return oracle_db_process_sql_insert($table, $values, $autocommit);
			break;
	}
}

/**
 * Updates a database record.
 *
 * All values should be cleaned before passing. Quoting isn't necessary.
 * Examples:
 *
 * <code>
 * db_process_sql_update ('table', array ('field' => 1), array ('id' => $id));
 * db_process_sql_update ('table', array ('field' => 1), array ('id' => $id, 'name' => $name));
 * db_process_sql_update ('table', array ('field' => 1), array ('id' => $id, 'name' => $name), 'OR');
 * db_process_sql_update ('table', array ('field' => 2), 'id in (1, 2, 3) OR id > 10');
 * </code>
 *
 * @param string Table to insert into
 * @param array An associative array of values to update
 * @param mixed An associative array of field and value matches. Will be joined
 * with operator specified by $where_join. A custom string can also be provided.
 * If nothing is provided, the update will affect all rows.
 * @param string When a $where parameter is given, this will work as the glue
 * between the fields. "AND" operator will be use by default. Other values might
 * be "OR", "AND NOT", "XOR"
 * @param bool Transaction automatically commited or not 
 *
 * @return mixed False in case of error or invalid values passed. Affected rows otherwise
 */
function db_process_sql_update($table, $values, $where = false, $where_join = 'AND', $autocommit = true) {
	global $config;

	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_process_sql_update($table, $values, $where, $where_join);
			break;
		case "postgresql":
			return postgresql_db_process_sql_update($table, $values, $where, $where_join);
			break;
		case "oracle":
			return oracle_db_process_sql_update($table, $values, $where, $where_join, $autocommit);
			break;
	}
}

/**
 * Delete database records.
 *
 * All values should be cleaned before passing. Quoting isn't necessary.
 * Examples:
 *
 * <code>
 * db_process_sql_delete ('table', array ('id' => 1));
 * // DELETE FROM table WHERE id = 1
 * db_process_sql_delete ('table', array ('id' => 1, 'name' => 'example'));
 * // DELETE FROM table WHERE id = 1 AND name = 'example'
 * db_process_sql_delete ('table', array ('id' => 1, 'name' => 'example'), 'OR');
 * // DELETE FROM table WHERE id = 1 OR name = 'example'
 * db_process_sql_delete ('table', 'id in (1, 2, 3) OR id > 10');
 * // DELETE FROM table WHERE id in (1, 2, 3) OR id > 10
 * </code>
 *
 * @param string Table to insert into
 * @param array An associative array of values to update
 * @param mixed An associative array of field and value matches. Will be joined
 * with operator specified by $where_join. A custom string can also be provided.
 * If nothing is provided, the update will affect all rows.
 * @param string When a $where parameter is given, this will work as the glue
 * between the fields. "AND" operator will be use by default. Other values might
 * be "OR", "AND NOT", "XOR"
 *
 * @return mixed False in case of error or invalid values passed. Affected rows otherwise
 */
function db_process_sql_delete($table, $where, $where_join = 'AND') {
	global $config;

	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_process_sql_delete($table, $where, $where_join);
			break;
		case "postgresql":
			return postgresql_db_process_sql_delete($table, $where, $where_join);
			break;
		case "oracle":
			return oracle_db_process_sql_delete($table, $where, $where_join);
			break;
	}
}

/**
 * Starts a database transaction.
 */
function db_process_sql_begin() {
	global $config;

	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_process_sql_begin();
			break;
		case "postgresql":
			return postgresql_db_process_sql_begin();
			break;
		case "oracle":
			return oracle_db_process_sql_begin();
			break;
	}
}

/**
 * Commits a database transaction.
 */
function db_process_sql_commit() {
	global $config;

	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_process_sql_commit();
			break;
		case "postgresql":
			return postgresql_db_process_sql_commit();
			break;
		case "oracle":
			return oracle_db_process_sql_commit();
			break;
	}
}

/**
 * Rollbacks a database transaction.
 */
function db_process_sql_rollback() {
	global $config;

	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_process_sql_rollback();
			break;
		case "postgresql":
			return postgresql_db_process_sql_rollback();
			break;
		case "oracle":
			return oracle_db_process_sql_rollback();
			break;
	}
}

/**
 * Prints a database debug table with all the queries done in the page loading.
 *
 * This functions does nothing if the config['debug'] flag is not set.
 */
function db_print_database_debug () {
	global $config;

	if (! isset ($config['debug']))
	return '';

	echo '<div class="database_debug_title">'.__('Database debug').'</div>';

	$table->id = 'database_debug';
	$table->cellpadding = '0';
	$table->width = '95%';
	$table->align = array ();
	$table->align[1] = 'left';
	$table->size = array ();
	$table->size[0] = '40px';
	$table->size[2] = '30%';
	$table->size[3] = '40px';
	$table->size[4] = '40px';
	$table->size[5] = '40px';
	$table->data = array ();
	$table->head = array ();
	$table->head[0] = '#';
	$table->head[1] = __('SQL sentence');
	$table->head[2] = __('Result');
	$table->head[3] = __('Rows');
	$table->head[4] = __('Saved');
	$table->head[5] = __('Time (ms)');

	if (! isset ($config['db_debug']))
	$config['db_debug'] = array ();
	$i = 1;
	foreach ($config['db_debug'] as $debug) {
		$data = array ();

		$data[0] = $i++;
		$data[1] = $debug['sql'];
		$data[2] = (empty ($debug['result']) ? __('OK') : $debug['result']);
		$data[3] = $debug['affected'];
		$data[4] = $debug['saved'];
		$data[5] = (isset ($debug['extra']['time']) ? format_numeric ($debug['extra']['time'] * 1000, 0) : '');

		array_push ($table->data, $data);

		if (($i % 100) == 0) {
			html_print_table ($table);
			$table->data = array ();
		}
	}

	html_print_table ($table);
}

/**
 * Get last error.
 * 
 * @return string Return the string error.
 */
function db_get_last_error() {
	global $config;

	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_get_last_error();
			break;
		case "postgresql":
			return postgresql_db_get_last_error();
			break;
		case "oracle":
			return oracle_db_get_last_error();
			break;
	}
}

/**
 * Get the type of field.
 * 
 * @param string $table The table to examine the type of field.
 * @param integer $field The field order in table.
 * 
 * @return mixed Return the type name or False in error case.
 */
function db_get_type_field_table($table, $field) {
	global $config;

	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_db_get_type_field_table($table, $field);
			break;
		case "postgresql":
			return postgresql_db_get_type_field_table($table, $field);
			break;
		case "oracle":
			return oracle_db_get_type_field_table($table, $field);
			break;
	}
}
?>
