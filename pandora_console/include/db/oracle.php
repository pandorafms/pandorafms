<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
function oracle_connect_db($host=null, $db=null, $user=null, $pass=null, $port=null, $new_connection=true)
{
    global $config;

    if ($host === null) {
        $host = $config['dbhost'];
    }

    if ($db === null) {
        $db = $config['dbname'];
    }

    if ($user === null) {
        $user = $config['dbuser'];
    }

    if ($pass === null) {
        $pass = $config['dbpass'];
    }

    if ($port === null) {
        $port = $config['dbport'];
    }

    // Non-persistent connection: This will help to avoid mysql errors like "has gone away" or locking problems
    // If you want persistent connections change it to oci_pconnect().
    if ($new_connection) {
        $connect_id = oci_new_connect($user, $pass, '//'.$host.':'.$port.'/'.$db);
    } else {
        $connect_id = oci_connect($user, $pass, '//'.$host.':'.$port.'/'.$db);
    }

    if (! $connect_id) {
        return false;
    }

    // Set date and timestamp formats for this session
    $datetime_tz_format = oci_parse($connect_id, 'alter session set NLS_TIMESTAMP_TZ_FORMAT =\'YYYY-MM-DD HH24:MI:SS\'');
    $datetime_format = oci_parse($connect_id, 'alter session set NLS_TIMESTAMP_FORMAT =\'YYYY-MM-DD HH24:MI:SS\'');
    $date_format = oci_parse($connect_id, 'alter session set NLS_DATE_FORMAT =\'YYYY-MM-DD HH24:MI:SS\'');
    $decimal_separator = oci_parse($connect_id, 'alter session set NLS_NUMERIC_CHARACTERS =\'.,\'');

    db_change_cache_id($host, $db);

    oci_execute($datetime_tz_format);
    oci_execute($datetime_format);
    oci_execute($date_format);
    oci_execute($decimal_separator);

    oci_free_statement($datetime_tz_format);
    oci_free_statement($datetime_format);
    oci_free_statement($date_format);
    oci_free_statement($decimal_separator);

    return $connect_id;
}


/**
 * Get the first value of the first row of a table in the database.
 *
 * @param string  $field             Field name to get.
 * @param string  $table             Table to retrieve the data.
 * @param string  $field_search      Field to filter elements.
 * @param string  $condition         Condition the field must have.
 * @param boolean $search_history_db Search in historical db.
 * @param boolean $cache             Enable cache or not.
 *
 * @return mixed Value of first column of the first row. False if there were no row.
 */
function oracle_db_get_value(
    $field,
    $table,
    $field_search=1,
    $condition=1,
    $search_history_db=false,
    $cache=false
) {
    if (is_int($condition)) {
        $sql = sprintf(
            'SELECT *
			FROM (SELECT %s FROM %s WHERE %s = %d)
			WHERE rownum < 2',
            $field,
            $table,
            $field_search,
            $condition
        );
    } else if (is_float($condition) || is_double($condition)) {
        $sql = sprintf(
            'SELECT *
			FROM (SELECT %s FROM %s WHERE %s = %f)
			WHERE rownum < 2',
            $field,
            $table,
            $field_search,
            $condition
        );
    } else {
        $sql = sprintf(
            "SELECT *
			FROM (SELECT %s FROM %s WHERE %s = '%s')
			WHERE rownum < 2",
            $field,
            $table,
            $field_search,
            $condition
        );
    }

    $result = db_get_all_rows_sql($sql, $search_history_db, $cache);

    if ($result === false) {
        return false;
    }

    $row = array_shift($result);
    $value = array_shift($row);

    if ($value === null) {
        return false;
    }

    return $value;
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
function oracle_db_get_row($table, $field_search, $condition, $fields=false)
{
    if (empty($fields)) {
        $fields = '*';
    } else {
        if (is_array($fields)) {
            $fields = implode(',', $fields);
        } else if (! is_string($fields)) {
            return false;
        }
    }

    if (is_int($condition)) {
        $sql = sprintf(
            'SELECT * FROM (SELECT %s FROM %s WHERE %s = %d) WHERE rownum < 2',
            $fields,
            $table,
            $field_search,
            $condition
        );
    } else if (is_float($condition) || is_double($condition)) {
        $sql = sprintf(
            'SELECT * FROM (SELECT %s FROM %s WHERE "%s" = %f) WHERE rownum < 2',
            $fields,
            $table,
            $field_search,
            $condition
        );
    } else {
        $sql = sprintf(
            "SELECT * FROM (SELECT %s FROM %s WHERE %s = '%s') WHERE rownum < 2",
            $fields,
            $table,
            $field_search,
            $condition
        );
    }

    $result = db_get_all_rows_sql($sql);

    if ($result === false) {
        return false;
    }

    return $result[0];
}


function oracle_db_get_all_rows_sql($sql, $search_history_db=false, $cache=true, $dbconnection=false)
{
    global $config;

    $history = [];

    if ($dbconnection === false) {
        $dbconnection = $config['dbconnection'];
    }

    // To disable globally SQL cache depending on global variable.
    // Used in several critical places like Metaconsole trans-server queries
    if (isset($config['dbcache'])) {
        $cache = $config['dbcache'];
    }

    // Read from the history DB if necessary
    if ($search_history_db && $config['history_db_enabled'] == 1) {
        $cache = false;
        $history = false;

        // Connect to the history DB
        if (! isset($config['history_db_connection']) || $config['history_db_connection'] === false) {
            $config['history_db_connection'] = db_connect($config['history_db_host'], $config['history_db_name'], $config['history_db_user'], io_output_password($config['history_db_pass']), $config['history_db_port'], false);
        }

        if ($config['history_db_connection'] !== false) {
            $history = oracle_db_process_sql($sql, 'affected_rows', $config['history_db_connection'], false);
        }

        if ($history === false) {
            $history = [];
        }
    }

    $return = oracle_db_process_sql($sql, 'affected_rows', $dbconnection, $cache);
    if ($return === false) {
        $return = [];
    }

    // Append result to the history DB data
    if (! empty($return)) {
        foreach ($return as $row) {
            array_push($history, $row);
        }
    }

    if (! empty($history)) {
        return $history;
    }

    // Return false, check with === or !==
    return false;
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
 *        'affected_rows' will return mysql_affected_rows (default value)
 *        'insert_id' will return the ID of an autoincrement value
 *        'info' will return the full (debug) information of a query
 *
 * @param bool Set autocommit transaction mode true/false
 *
 * @return mixed An array with the rows, columns and values in a multidimensional array or false in error
 */
// TODO: Return debug info of the query
function oracle_db_process_sql($sql, $rettype='affected_rows', $dbconnection='', $cache=true, &$status=null, $autocommit=true)
{
    global $config;
    global $sql_cache;

    $retval = [];

    if ($sql == '') {
        return false;
    }

    if ($cache && ! empty($sql_cache[$sql_cache['id']][$sql])) {
        $retval = $sql_cache[$sql_cache['id']][$sql];
        $sql_cache['saved'][$sql_cache['id']]++;
        db_add_database_debug_trace($sql);
    } else {
        $id = 0;
        $parse_query = explode(' ', trim(preg_replace('/\s\s+/', ' ', $sql)));
        $table_name = preg_replace('/\((\w*|,\w*)*\)|\(\w*|,\w*/', '', preg_replace('/\s/', '', $parse_query[2]));
        $type = explode(' ', strtoupper(trim($sql)));

        $start = microtime(true);

        if (empty($dbconnection)) {
            $dbconnection = $config['dbconnection'];
        }

        if ($type[0] == 'INSERT') {
            $query = oci_parse($dbconnection, 'BEGIN insert_id(:table_name, :sql, :out); END;');
        }
        // Prevent execution of insert_id stored procedure
        else if ($type[0] == '/INSERT') {
            $query = oci_parse($dbconnection, substr($sql, 1));
        } else {
            $query = oci_parse($dbconnection, $sql);
        }

        // If query is an insert retrieve Id field
        if ($type[0] == 'INSERT') {
            oci_bind_by_name($query, ':table_name', $table_name, 32);
            oci_bind_by_name($query, ':sql', $sql, -1);
            oci_bind_by_name($query, ':out', $id, 40);
        }

        if (!$autocommit) {
            $result = oci_execute($query, OCI_NO_AUTO_COMMIT);
        } else {
            $result = oci_execute($query);
        }

        $time = (microtime(true) - $start);

        $config['oracle_error_query'] = null;
        if ($result === false) {
            $backtrace = debug_backtrace();
            $e = oci_error($query);

            $config['oracle_error_query'] = $query;

            $error = sprintf(
                '%s (\'%s\') in <strong>%s</strong> on line %d',
                htmlentities($e['message'], ENT_QUOTES),
                $sql,
                $backtrace[0]['file'],
                $backtrace[0]['line']
            );
            db_add_database_debug_trace($sql, htmlentities($e['message'], ENT_QUOTES));

            set_error_handler('db_sql_error_handler');
            trigger_error($error);
            restore_error_handler();

            return false;
        } else {
            $status = oci_statement_type($query);
            $rows = oci_num_rows($query);

            if ($status !== 'SELECT') {
                // The query NOT IS a select
                if ($rettype == 'insert_id') {
                    $result = $id;
                } else if ($rettype == 'info') {
                    // TODO: return debug information of the query $result = pg_result_status($result, PGSQL_STATUS_STRING);
                    $result = '';
                } else {
                    $result = $rows;
                }

                db_add_database_debug_trace(
                    $sql,
                    $result,
                    $rows,
                    ['time' => $time]
                );

                return $result;
            } else {
                // The query IS a select.
                db_add_database_debug_trace($sql, 0, $rows, ['time' => $time]);
                while ($row = oci_fetch_assoc($query)) {
                    $i = 1;
                    $result_temp = [];
                    foreach ($row as $key => $value) {
                        $column_type = oci_field_type($query, $key);
                        // Support for Clob fields larger than 4000bytes
                        // if ($sql == 'SELECT * FROM tgrupo ORDER BY dbms_lob.substr(nombre,4000,1) ASC') echo $i .' '.$column_type.' '.$key.'<br>';
                        if ($column_type == 'CLOB') {
                            $column_name = oci_field_name($query, $i);
                            // Protect against a NULL CLOB
                            if (is_object($row[$column_name])) {
                                $clob_data = $row[$column_name]->load();
                                $row[$column_name]->free();
                                $value = $clob_data;
                            } else {
                                $value = '';
                            }
                        }

                        $result_temp[strtolower($key)] = $value;
                        $i++;
                    }

                    array_push($retval, $result_temp);
                    // array_push($retval, $row);
                }

                if ($cache === true) {
                    $sql_cache[$sql_cache['id']][$sql] = $retval;
                }

                oci_free_statement($query);
            }
        }
    }

    if (! empty($retval)) {
        return $retval;
    }

    // Return false, check with === or !==
    return false;
}


/**
 * Get all the rows in a table of the database.
 *
 * @param string Database table name.
 * @param string Field to order by.
 * @param string                     $order The type of order, by default 'ASC'.
 *
 * @return mixed A matrix with all the values in the table
 */
function oracle_db_get_all_rows_in_table($table, $order_field='', $order='ASC')
{
    if ($order_field != '') {
        // Clob fields are not allowed in ORDER BY statements, they need cast to varchar2 datatype
        $type = db_get_value_filter(
            'data_type',
            'user_tab_columns',
            [
                'table_name'  => strtoupper($table),
                'column_name' => strtoupper($order_field),
            ],
            'AND'
        );
        if ($type == 'CLOB') {
            return db_get_all_rows_sql(
                'SELECT *
				FROM '.$table.'
				ORDER BY dbms_lob.substr('.$order_field.',4000,1) '.$order
            );
        } else {
            return db_get_all_rows_sql(
                'SELECT *
				FROM '.$table.'
				ORDER BY '.$order_field.' '.$order
            );
        }
    } else {
        return db_get_all_rows_sql('SELECT * FROM '.$table);
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
 * @param bool Whether to do autocommit or not
 *
 * @return mixed False in case of error or invalid values passed. Affected rows otherwise
 */
function oracle_db_process_sql_insert($table, $values, $autocommit=true)
{
    // Empty rows or values not processed
    if (empty($values)) {
        return false;
    }

    $values = (array) $values;

    $query = sprintf('INSERT INTO %s ', $table);
    $fields = [];
    $values_str = '';
    $i = 1;
    $max = count($values);
    foreach ($values as $field => $value) {
        array_push($fields, $field);

        if (is_null($value)) {
            $values_str .= 'NULL';
        } else if (is_int($value) || is_bool($value)) {
            $values_str .= sprintf('%d', $value);
        } else if (is_float($value) || is_double($value)) {
            $values_str .= sprintf('%f', $value);
        } else if (substr($value, 0, 1) == '#') {
            $values_str .= sprintf('%s', substr($value, 1));
        } else {
            $values_str .= sprintf("'%s'", $value);
        }

        if ($i < $max) {
            $values_str .= ',';
        }

        $i++;
    }

    $query .= '('.implode(', ', $fields).')';

    $query .= ' VALUES ('.$values_str.')';
    $status = '';

    return db_process_sql($query, 'insert_id', '', true, $status, $autocommit);
}


/**
 * Escape string to set it properly to use in sql queries
 *
 * @param string String to be cleaned.
 *
 * @return string String cleaned.
 */
function oracle_escape_string_sql($string)
{
    return str_replace(['"', "'", '\\'], ['\\"', '\\\'', '\\\\'], $string);
}


function oracle_encapsule_fields_with_same_name_to_instructions($field)
{
    $return = $field;

    if (is_string($return)) {
        if ($return[0] !== '"') {
            // The columns declared without quotes are converted to uppercase in oracle.
            // A column named asd is equal to asd, ASD or "ASD", but no to "asd".
            $return = '"'.$return.'"';
        }
    }

    return $return;
}


/**
 * Get the first value of the first row of a table in the database from an
 * array with filter conditions.
 *
 * Example:
 <code>
 db_get_value_filter ('name', 'talert_templates',
 array ('value' => 2, 'type' => 'equal'));
 // Equivalent to:
 // SELECT name FROM talert_templates WHERE value = 2 AND type = 'equal' LIMIT 1
 // In oracle sintax:
 // SELECT name FROM talert_templates WHERE value = 2 AND type = 'equal' AND rownum < 2

 db_get_value_filter ('description', 'talert_templates',
 array ('name' => 'My alert', 'type' => 'regex'), 'OR');
 // Equivalent to:
 // SELECT description FROM talert_templates WHERE name = 'My alert' OR type = 'equal' LIMIT 1
 // In oracle sintax:
 // SELECT description FROM talert_templates WHERE name = 'My alert' OR type = 'equal' AND rownum < 2
 </code>
 *
 * @param string Field name to get
 * @param string Table to retrieve the data
 * @param array Conditions to filter the element. See db_format_array_where_clause_sql()
 * for the format
 * @param string Join operator for the elements in the filter.
 *
 * @return mixed Value of first column of the first row. False if there were no row.
 */
function oracle_db_get_value_filter($field, $table, $filter, $where_join='AND', $search_history_db=false)
{
    if (! is_array($filter) || empty($filter)) {
        return false;
    }

    // Avoid limit and offset if given
    unset($filter['limit']);
    unset($filter['offset']);

    $sql = sprintf(
        'SELECT * FROM (SELECT %s FROM %s WHERE %s) WHERE rownum < 2',
        $field,
        $table,
        db_format_array_where_clause_sql($filter, $where_join)
    );
    $result = db_get_all_rows_sql($sql, $search_history_db);

    if ($result === false) {
        return false;
    }

    $row = array_shift($result);
    $value = array_shift($row);

    if ($value === null) {
        return false;
    }

    return $value;
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
 * This in Oracle Sql sintaxis is translate to:
 * SELECT * FROM table WHERE name = "Name" AND description = "Long description" AND rownum <= 20
 * </code>
 *
 * @param array Values to be formatted in an array indexed by the field name.
 * There are special parameters such as 'order' and 'limit' that will be used
 * as ORDER and LIMIT clauses respectively. Since LIMIT is
 * numeric, ORDER can receive a field name or a SQL function and a the ASC or
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


 IMPORTANT!!! OFFSET is not allowed in this function because Oracle needs to recode the complete query.
 use oracle_recode_query() function instead
 *
 * @return string Values joined into an SQL string that can fits into the WHERE
 * clause of an SQL sentence.
 */
function oracle_db_format_array_where_clause_sql($values, $join='AND', $prefix=false)
{
    $fields = [];

    if (! is_array($values)) {
        return '';
    }

    $query = '';
    $limit = '';
    $order = '';
    $group = '';
    if (isset($values['offset'])) {
        return '';
    }

    if (isset($values['limit'])) {
        $limit = sprintf(' AND rownum <= %d', $values['limit']);
        unset($values['limit']);
    }

    if (isset($values['order'])) {
        if (is_array($values['order'])) {
            if (!isset($values['order']['order'])) {
                $orderTexts = [];
                foreach ($values['order'] as $orderItem) {
                    $orderTexts[] = $orderItem['field'].' '.$orderItem['order'];
                }

                $order = ' ORDER BY '.implode(', ', $orderTexts);
            } else {
                $order = sprintf(' ORDER BY %s %s', $values['order']['field'], $values['order']['order']);
            }
        } else {
            $order = sprintf(' ORDER BY %s', $values['order']);
        }

        unset($values['order']);
    }

    if (isset($values['group'])) {
        $group = sprintf(' GROUP BY %s', $values['group']);
        unset($values['group']);
    }

    $i = 1;
    $max = count($values);
    foreach ($values as $field => $value) {
        if ($i == 1) {
            $query .= ' ( ';
        }

        if ($field == '1' and $value == '1') {
            $query .= sprintf("'%s' = '%s'", $field, $value);

            if ($i < $max) {
                $query .= ' '.$join.' ';
            }

            if ($i == $max) {
                $query .= ' ) ';
            }

            $i++;
            continue;
        } else if (is_numeric($field)) {
            // User provide the exact operation to do
            $query .= $value;

            if ($i < $max) {
                $query .= ' '.$join.' ';
            }

            if ($i == $max) {
                $query .= ' ) ';
            }

            $i++;
            continue;
        }

        if (is_null($value)) {
            $query .= sprintf('%s IS NULL', $field);
        } else if (is_int($value) || is_bool($value)) {
            $query .= sprintf('%s = %d', $field, $value);
        } else if (is_float($value) || is_double($value)) {
            $query .= sprintf('%s = %f', $field, $value);
        } else if (is_array($value)) {
            $query .= sprintf("%s IN ('%s')", $field, implode("', '", $value));
        } else {
            if ($value[0] == '>') {
                $value = substr($value, 1, (strlen($value) - 1));

                if (is_nan($value)) {
                    $query .= sprintf("%s > '%s'", $field, $value);
                } else {
                    $query .= sprintf('%s > %s', $field, $value);
                }
            } else if ($value[0] == '<') {
                if ($value[1] == '>') {
                    $value = substr($value, 2, (strlen($value) - 2));

                    if (is_nan($value)) {
                        $query .= sprintf("%s <> '%s'", $field, $value);
                    } else {
                        $query .= sprintf('%s <> %s', $field, $value);
                    }
                } else {
                    $value = substr($value, 1, (strlen($value) - 1));

                    if (is_nan($value)) {
                        $query .= sprintf("%s < '%s'", $field, $value);
                    } else {
                        $query .= sprintf('%s < %s', $field, $value);
                    }
                }
            } else if ($value[0] == '%') {
                $query .= sprintf("%s LIKE '%s'", $field, $value);
            } else {
                $query .= sprintf("%s = '%s'", $field, $value);
            }
        }

        if ($i < $max) {
            $query .= ' '.$join.' ';
        }

        if ($i == $max) {
            $query .= ' ) ';
        }

        $i++;
    }

    return (! empty($query) ? $prefix : '').$query.$limit.$group.$order;
}


/**
 * Formats an SQL query to use LIMIT and OFFSET Mysql like statements in Oracle.
 *
 * This function is useful to generate an SQL sentence from
 * a list of values. Example code:
 <code>
 *
 * @param string Join operator. AND by default.
 * @param string A prefix to be added to the string. It's useful when
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
 // This in Oracle Sql sintaxis is translate to:
 // SELECT * FROM (SELECT ROWNUM AS rnum, a.* FROM (SELECT * FROM table) a) WHERE rnum > 20 AND rnum <= 30

 $values = array ();
 $values['value'] = 5;
 $values['limit'] = 10;
 $values['offset'] = 20;
 $sql = 'SELECT * FROM table WHERE '.db_format_array_where_clause_sql ($values, 'AND', 'WHERE');
 // Good SQL: SELECT * FROM table WHERE value = 5 LIMIT 10 OFFSET 20
 // This in Oracle Sql sintaxis is translate to:
 // SELECT * FROM (SELECT ROWNUM AS rnum, a.* FROM (SELECT * FROM table WHERE value = 5) a) WHERE rnum > 20 AND rnum <= 30;
 </code>

 * @param string Sql from SELECT to WHERE reserved words: SELECT * FROM mytable WHERE
 * @param array Conditions to filter the element. See db_format_array_where_clause_sql()
 * for the format. LIMIT + OFFSET are allowed in this function:

 <code>
 $values = array();
 $values['limit'] = x;
 $values['offset'] = y;
 </code>

 * @param string Join operator for the elements in the filter.
 * @param bool Whether to return Sql or execute. Note that if you return data in a string format then after execute the query you have
 * to discard RNUM column.
 *
 * @return string Values joined into an SQL string that fits Oracle SQL sintax
 * clause of an SQL sentence.
 **/
function oracle_recode_query($sql, $values, $join='AND', $return=true)
{
    $fields = [];

    if (! is_array($values) || empty($sql)) {
        return '';
    }

    $query = '';
    $limit = '';
    $offset = '';
    $order = '';
    $group = '';
    $pre_query = '';
    $post_query = '';
    // LIMIT + OFFSET options have to be recoded into a subquery
    if (isset($values['limit']) && isset($values['offset'])) {
        $down = $values['offset'];
        $top = ($values['offset'] + $values['limit']);
        $pre_query = 'SELECT * FROM (SELECT ROWNUM AS rnum, a.* FROM (';
        $post_query = sprintf(') a) WHERE rnum > %d AND rnum <= %d', $down, $top);
        unset($values['limit']);
        unset($values['offset']);
    } else if (isset($values['limit'])) {
        $limit = sprintf(' AND rownum <= %d', $values['limit']);
        unset($values['limit']);
    }
    // OFFSET without LIMIT option is not supported
    else if (isset($values['offset'])) {
        unset($values['offset']);
    }

    if (isset($values['order'])) {
        if (is_array($values['order'])) {
            if (!isset($values['order']['order'])) {
                $orderTexts = [];
                foreach ($values['order'] as $orderItem) {
                    $orderTexts[] = $orderItem['field'].' '.$orderItem['order'];
                }

                $order = ' ORDER BY '.implode(', ', $orderTexts);
            } else {
                $order = sprintf(' ORDER BY %s %s', $values['order']['field'], $values['order']['order']);
            }
        } else {
            $order = sprintf(' ORDER BY %s', $values['order']);
        }

        unset($values['order']);
    }

    if (isset($values['group'])) {
        $group = sprintf(' GROUP BY %s', $values['group']);
        unset($values['group']);
    }

    $i = 1;
    $max = count($values);
    foreach ($values as $field => $value) {
        if ($i == 1) {
            $query .= ' ( ';
        }

        if (is_numeric($field)) {
            // User provide the exact operation to do
            $query .= $value;

            if ($i < $max) {
                $query .= ' '.$join.' ';
            }

            $i++;
            continue;
        }

        if (is_null($value)) {
            $query .= sprintf('%s IS NULL', $field);
        } else if (is_int($value) || is_bool($value)) {
            $query .= sprintf('%s = %d', $field, $value);
        } else if (is_float($value) || is_double($value)) {
            $query .= sprintf('%s = %f', $field, $value);
        } else if (is_array($value)) {
            $query .= sprintf("%s IN ('%s')", $field, implode("', '", $value));
        } else {
            if ($value[0] == '>') {
                $value = substr($value, 1, (strlen($value) - 1));
                $query .= sprintf("%s > '%s'", $field, $value);
            } else if ($value[0] == '<') {
                if ($value[1] == '>') {
                    $value = substr($value, 2, (strlen($value) - 2));
                    $query .= sprintf("%s <> '%s'", $field, $value);
                } else {
                    $value = substr($value, 1, (strlen($value) - 1));
                    $query .= sprintf("%s < '%s'", $field, $value);
                }
            } else if ($value[0] == '%') {
                $query .= sprintf("%s LIKE '%s'", $field, $value);
            } else {
                $query .= sprintf("%s = '%s'", $field, $value);
            }
        }

        if ($i < $max) {
            $query .= ' '.$join.' ';
        }

        if ($i == $max) {
            $query .= ' ) ';
        }

        $i++;
    }

    $result = $pre_query.$sql.$query.$limit.$group.$order.$post_query;
    if ($return) {
        return $result;
    } else {
        $result = oracle_db_process_sql($result);
        if ($result !== false) {
            for ($i = 0; $i < count($result); $i++) {
                unset($result[$i]['RNUM']);
            }
        }

        return $result;
    }
}


/**
 * Get the first value of the first row of a table result from query.
 *
 * @param string SQL select statement to execute.
 *
 * @return the first value of the first row of a table result from query.
 */
function oracle_db_get_value_sql($sql, $dbconnection=false, $search_history_db=false)
{
    $sql = 'SELECT * FROM ('.$sql.') WHERE rownum < 2';
    $result = oracle_db_get_all_rows_sql($sql, $search_history_db, true, $dbconnection);

    if ($result === false) {
        return false;
    }

    $row = array_shift($result);
    $value = array_shift($row);

    if ($value === null) {
        return false;
    }

    return $value;
}


/**
 * Get the first row of an SQL database query.
 *
 * @param string SQL select statement to execute.
 *
 * @return mixed The first row of the result or false
 */
function oracle_db_get_row_sql($sql, $search_history_db=false, $cache=true)
{
    $sql = 'SELECT * FROM ('.$sql.') WHERE rownum < 2';
    $result = oracle_db_get_all_rows_sql($sql, $search_history_db, $cache);

    if ($result === false) {
        return false;
    }

    return $result[0];
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
function oracle_db_get_row_filter($table, $filter, $fields=false, $where_join='AND', $history_db=false, $cache=true)
{
    if (empty($fields)) {
        $fields = '*';
    } else {
        if (is_array($fields)) {
            $fields = implode(',', $fields);
        } else if (! is_string($fields)) {
            return false;
        }
    }

    if (is_array($filter)) {
        $filter = db_format_array_where_clause_sql($filter, $where_join, ' WHERE ');
    } else if (is_string($filter)) {
        $filter = 'WHERE '.$filter;
    } else {
        $filter = '';
    }

    $sql = sprintf('SELECT %s FROM %s %s', $fields, $table, $filter);

    return db_get_row_sql($sql, $history_db, $cache);
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
 * @param boolean                                                  $returnSQL Return a string with SQL instead the data, by default false.
 *
 * @return mixed Array of the row or false in case of error.
 */
function oracle_db_get_all_rows_filter($table, $filter=[], $fields=false, $where_join='AND', $search_history_db=false, $returnSQL=false)
{
    // TODO: Validate and clean fields
    if (empty($fields)) {
        $fields = '*';
    } else if (is_array($fields)) {
        $fields = implode(' , ', $fields);
    } else if (!is_string($fields)) {
        return false;
    }

    // TODO: Validate and clean filter options
    if (is_array($filter)) {
        $filter = db_format_array_where_clause_sql($filter, $where_join, ' WHERE ');
    } else if (is_string($filter)) {
        $filter = 'WHERE '.$filter;
    } else {
        $filter = '';
    }

    $sql = sprintf('SELECT %s FROM %s %s', $fields, $table, $filter);

    if ($returnSQL) {
        return $sql;
    } else {
        return db_get_all_rows_sql($sql, $search_history_db);
    }
}


/**
 * Return the count of rows of query.
 *
 * @param  $sql
 * @return integer The count of rows of query.
 */
function oracle_db_get_num_rows($sql)
{
    global $config;

    $type = explode(' ', strtoupper(trim($sql)));
    if ($type[0] == 'SELECT') {
        $sql = 'SELECT count(*) as NUM FROM ('.$sql.')';
    }

    $query = oci_parse($config['dbconnection'], $sql);
    oci_execute($query);
    if ($type[0] == 'SELECT') {
        $row = oci_fetch_assoc($query);
        $rows = $row['NUM'];
    } else {
        $rows = oci_num_rows($query);
    }

    oci_free_statement($query);

    return $rows;
}


/**
 * Get all the rows in a table of the database filtering from a field.
 *
 * @param string Database table name.
 * @param string Field of the table.
 * @param string Condition the field must have to be selected.
 * @param string Field to order by.
 *
 * @return mixed A matrix with all the values in the table that matches the condition in the field or false
 */
function oracle_db_get_all_rows_field_filter($table, $field, $condition, $order_field='')
{
    if (is_int($condition) || is_bool($condition)) {
        $sql = sprintf(
            'SELECT *
			FROM %s
			WHERE %s = %d',
            $table,
            $field,
            $condition
        );
    } else if (is_float($condition) || is_double($condition)) {
        $sql = sprintf(
            'SELECT *
			FROM %s
			WHERE %s = %f',
            $table,
            $field,
            $condition
        );
    } else {
        $sql = sprintf(
            "SELECT *
			FROM %s
			WHERE %s = '%s'",
            $table,
            $field,
            $condition
        );
    }

    if ($order_field != '') {
        $sql .= sprintf(' ORDER BY %s', $order_field);
    }

    return db_get_all_rows_sql($sql);
}


/**
 * Get all the rows in a table of the database filtering from a field.
 *
 * @param string Database table name.
 * @param string Field of the table.
 *
 * @return mixed A matrix with all the values in the table that matches the condition in the field
 */
function oracle_db_get_all_fields_in_table($table, $field='', $condition='', $order_field='')
{
    $sql = sprintf('SELECT * FROM %s', $table);

    if ($condition != '') {
        $sql .= sprintf(" WHERE %s = '%s'", $field, $condition);
    }

    if ($order_field != '') {
        $sql .= sprintf(' ORDER BY %s', $order_field);
    }

    return db_get_all_rows_sql($sql);
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
 * UPDATE table SET name = "Name", description = "Long description" WHERE id=1
 * </code>
 *
 * @param array Values to be formatted in an array indexed by the field name.
 *
 * @return string Values joined into an SQL string that can fits into an UPDATE
 * sentence.
 */
function oracle_db_format_array_to_update_sql($values)
{
    $fields = [];

    foreach ($values as $field => $value) {
        if (is_numeric($field)) {
            array_push($fields, $value);
            continue;
        } else if ($field[0] == '`') {
            $field = str_replace('`', '', $field);
        }

        if ($value === null) {
            $sql = sprintf('%s = NULL', $field);
        } else if (is_int($value) || is_bool($value)) {
            $sql = sprintf('%s = %d', $field, $value);
        } else if (is_float($value) || is_double($value)) {
            $sql = sprintf('%s = %f', $field, $value);
        } else {
            // String
            if (isset($value[0]) && $value[0] == '`') {
                // Don't round with quotes if it references a field
                $sql = sprintf('%s = %s', $field, str_replace('`', '', $value));
            } else if (substr($value, 0, 1) == '#') {
                $sql = sprintf('%s = %s', $field, substr($value, 1));
            } else {
                $sql = sprintf("%s = '%s'", $field, $value);
            }
        }

        array_push($fields, $sql);
    }

    return implode(', ', $fields);
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
 * @param string When a                                         $where parameter is given, this will work as the glue
 *                                         between the fields. "AND" operator will be use by default. Other values might
 *                                         be "OR", "AND NOT", "XOR"
 *
 * @return mixed False in case of error or invalid values passed. Affected rows otherwise
 */
function oracle_db_process_sql_update($table, $values, $where=false, $where_join='AND', $autocommit=true)
{
    $query = sprintf(
        'UPDATE %s SET %s',
        $table,
        db_format_array_to_update_sql($values)
    );

    if ($where) {
        if (is_string($where)) {
            // No clean, the caller should make sure all input is clean, this is a raw function
            $query .= ' WHERE '.$where;
        } else if (is_array($where)) {
            $query .= db_format_array_where_clause_sql($where, $where_join, ' WHERE ');
        }
    }

    $status = '';

    return db_process_sql($query, 'affected_rows', '', true, $status, $autocommit);
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
 * @param string When a                                         $where parameter is given, this will work as the glue
 *                                         between the fields. "AND" operator will be use by default. Other values might
 *                                         be "OR", "AND NOT", "XOR"
 *
 * @return mixed False in case of error or invalid values passed. Affected rows otherwise
 */
function oracle_db_process_sql_delete($table, $where, $where_join='AND')
{
    if (empty($where)) {
        // Should avoid any mistake that lead to deleting all data
        return false;
    }

    $query = sprintf('DELETE FROM %s WHERE ', $table);

    if ($where) {
        if (is_string($where)) {
            /*
                FIXME: Should we clean the string for sanity?
             Who cares if this is deleting data... */
            $query .= $where;
        } else if (is_array($where)) {
            $query .= db_format_array_where_clause_sql($where, $where_join);
        }
    }

    return db_process_sql($query);
}


function oracle_db_process_sql_delete_temp($table, $where, $where_join='AND')
{
    if (empty($where)) {
        // Should avoid any mistake that lead to deleting all data
        return false;
    }

    $query = sprintf('DELETE FROM %s WHERE ', $table);

    if ($where) {
        if (is_string($where)) {
            /*
                FIXME: Should we clean the string for sanity?
             Who cares if this is deleting data... */
            $query .= $where;
        } else if (is_array($where)) {
            $query .= db_format_array_where_clause_sql($where, $where_join);
        }
    }

    $result = '';

    return db_process_sql($query, 'affected_rows', '', true, $result, false);
}


/**
 * Get row by row the DB by SQL query. The first time pass the SQL query and
 * rest of times pass none for iterate in table and extract row by row, and
 * the end return false.
 *
 * @param  boolean  $new    Default true, if true start to query.
 * @param  resource $result The resource of oracle for access to query.
 * @param  string   $sql
 * @return mixed The row or false in error.
 */
function oracle_db_get_all_row_by_steps_sql($new=true, &$result, $sql=null)
{
    global $config;

    if ($new == true) {
        $result = oci_parse($config['dbconnection'], $sql);
        oci_execute($result);
    }

    $row = oci_fetch_assoc($result);

    $result_temp = [];
    if ($row) {
        foreach ($row as $key => $value) {
            $column_type = oci_field_type($result, $key);
            // Support for Clob field larger than 4000bytes
            if ($column_type == 'CLOB') {
                $column_name = oci_field_name($result, $key);
                $column_name = oci_field_name($result, $key);
                // protect against a NULL CLOB
                if (is_object($row[$column_name])) {
                    $clob_data = $row[$column_name]->load();
                    $row[$column_name]->free();
                    $value = $clob_data;
                } else {
                    $value = '';
                }
            }

            $result_temp[strtolower($key)] = $value;
        }
    }

    if (!$row) {
        oci_free_statement($result);
    }

    // return $row;
    return $result_temp;
}


/**
 * Starts a database transaction.
 */
function oracle_db_process_sql_begin()
{
    global $config;

    $query = oci_parse($config['dbconnection'], 'SET TRANSACTION READ WRITE');
    oci_execute($query);
    oci_free_statement($query);
}


/**
 * Commits a database transaction.
 */
function oracle_db_process_sql_commit()
{
    global $config;

    oci_commit($config['dbconnection']);
}


/**
 * Rollbacks a database transaction.
 */
function oracle_db_process_sql_rollback()
{
    global $config;

    oci_rollback($config['dbconnection']);
}


/**
 * Get last error.
 *
 * @return string Return the string error.
 */
function oracle_db_get_last_error()
{
    global $config;

    if (empty($config['oracle_error_query'])) {
        return null;
    }

    $ora_erno = oci_error($config['oracle_error_query']);

    return $ora_erno['message'];
}


/**
 * This function gets the time from either system or sql based on preference and returns it
 *
 * @return integer Unix timestamp
 */
function oracle_get_system_time()
{
    global $config;

    static $time = 0;

    if ($time != 0) {
        return $time;
    }

    if ($config['timesource'] == 'sql') {
        $time = db_get_sql("SELECT ceil((sysdate - to_date('19700101000000','YYYYMMDDHH24MISS')) * (".SECONDS_1DAY.')) as dt FROM dual');
        if (empty($time)) {
            return time();
        }

        return $time;
    } else {
        return time();
    }
}


/**
 * Get the type of field.
 *
 * @param string  $table The table to examine the type of field.
 * @param integer $field The field order in table.
 *
 * @return mixed Return the type name or False in error case.
 */
function oracle_db_get_type_field_table($table, $field)
{
    global $config;

    $query = oci_parse(
        $config['dbconnection'],
        'SELECT * FROM '.$table.' WHERE rownum < 2'
    );
    oci_execute($query);

    $type = oci_field_type($query, ($field + 1));
    oci_free_statement($query);

    return $type;
}


/**
 * Get all field names of a table and recode fields
 * for clob datatype as "dbms_lob.substr(<field>, 4000 ,1) as <field>".
 *
 * @param string  $table       The table to retrieve all column names.
 * @param integer $return_mode Whether to return as array (by default) or as comma separated string.
 *
 * @return mixed Return an array/string of table fields or false if something goes wrong.
 */
function oracle_list_all_field_table($table_name, $return_mode='array')
{
    if (empty($table_name)) {
        return false;
    }

    $fields_info = db_get_all_rows_field_filter('user_tab_columns', 'table_name', strtoupper($table_name));
    if (empty($fields_info)) {
        return false;
    }

    $field_list = [];
    foreach ($fields_info as $field) {
        if ($field['data_type'] == 'CLOB') {
            $new_field = 'dbms_lob.substr('.$field['table_name'].'.'.$field['column_name'].', 4000, 1) as '.strtolower($field['column_name']);
            $field_list[] = $new_field;
        } else {
            $field_list[] = strtolower($field['table_name'].'.'.$field['column_name']);
        }
    }

    // Return as comma separated string
    if ($return_mode == 'string') {
        return implode(',', $field_list);
    }
    // Return as array
    else {
        return $field_list;
    }
}


/**
 * Process a file with an oracle schema sentences.
 * Based on the function which installs the pandoradb.sql schema.
 *
 * @param string  $path         File path.
 * @param boolean $handle_error Whether to handle the oci_execute errors or throw an exception.
 *
 * @return boolean Return the final status of the operation.
 */
function oracle_db_process_file($path, $handle_error=true)
{
    global $config;

    if (file_exists($path)) {
        $file_content = file($path);

        $query = '';
        $plsql_block = false;

        // Begin the transaction
        oracle_db_process_sql_begin();

        $datetime_tz_format = oci_parse($connection, 'alter session set NLS_TIMESTAMP_TZ_FORMAT =\'YYYY-MM-DD HH24:MI:SS\'');
        $datetime_format = oci_parse($connection, 'alter session set NLS_TIMESTAMP_FORMAT =\'YYYY-MM-DD HH24:MI:SS\'');
        $date_format = oci_parse($connection, 'alter session set NLS_DATE_FORMAT =\'YYYY-MM-DD HH24:MI:SS\'');
        $decimal_separator = oci_parse($connection, 'alter session set NLS_NUMERIC_CHARACTERS =\',.\'');

        oci_execute($datetime_tz_format);
        oci_execute($datetime_format);
        oci_execute($date_format);
        oci_execute($decimal_separator);

        oci_free_statement($datetime_tz_format);
        oci_free_statement($datetime_format);
        oci_free_statement($date_format);
        oci_free_statement($decimal_separator);

        foreach ($file_content as $sql_line) {
            $clean_line = trim($sql_line);
            $comment = preg_match("/^(\s|\t)*--.*$/", $clean_line);
            if ($comment) {
                continue;
            }

            if (empty($clean_line)) {
                continue;
            }

            // Support for PL/SQL blocks
            if (preg_match('/^BEGIN$/', $clean_line)) {
                $query .= $clean_line.' ';
                $plsql_block = true;
            } else {
                $query .= $clean_line;
            }

            // Check query's end with a back slash and any returns in the end of line or if it's a PL/SQL block 'END;;' string
            if ((preg_match("/;[\040]*\$/", $clean_line) && !$plsql_block)
                || (preg_match("/^END;;[\040]*\$/", $clean_line) && $plsql_block)
            ) {
                $plsql_block = false;
                // Execute and clean buffer
                // Delete the last semicolon from current query
                $query = substr($query, 0, (strlen($query) - 1));
                $sql = oci_parse($config['dbconnection'], $query);
                $result = oci_execute($sql, OCI_NO_AUTO_COMMIT);

                if (!$result) {
                    // Error. Rollback the transaction
                    oracle_db_process_sql_rollback();

                    $e = oci_error($sql);

                    // Handle the error
                    if ($handle_error) {
                        $backtrace = debug_backtrace();
                        $error = sprintf(
                            '%s (\'%s\') in <strong>%s</strong> on line %d',
                            htmlentities($e['message'], ENT_QUOTES),
                            $query,
                            $backtrace[0]['file'],
                            $backtrace[0]['line']
                        );
                        db_add_database_debug_trace($query, htmlentities($e['message'], ENT_QUOTES));
                        set_error_handler('db_sql_error_handler');
                        trigger_error($error);
                        restore_error_handler();

                        return false;
                    }
                    // Throw an exception with the error message
                    else {
                        throw new Exception($e['message']);
                    }
                }

                $query = '';
                oci_free_statement($sql);
            }
        }

        // No errors. Commit the transaction
        oracle_db_process_sql_commit();

        return true;
    } else {
        return false;
    }
}


function oracle_format_float_to_php($val)
{
    return floatval(str_replace(',', '.', $val));
}
