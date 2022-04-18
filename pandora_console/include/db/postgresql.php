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
function postgresql_connect_db($host=null, $db=null, $user=null, $pass=null, $port=null)
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

    $connect_id = pg_connect(
        "host='".$host."'".' port='.$port." dbname='".$db."'"." user='".$user."'"." password='".$pass."'",
        PGSQL_CONNECT_FORCE_NEW
    );

    if (! $connect_id) {
        return false;
    }

    db_change_cache_id($host, $db);

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
function postgresql_db_get_value(
    $field,
    $table,
    $field_search=1,
    $condition=1,
    $search_history_db=false,
    $cache=true
) {
    if ($field_search[0] == '`') {
        $field_search = str_replace('`', '', $field_search);
    }

    if (is_int($condition)) {
        $sql = sprintf(
            'SELECT %s FROM %s WHERE %s = %d LIMIT 1',
            $field,
            $table,
            $field_search,
            $condition
        );
    } else if (is_float($condition) || is_double($condition)) {
        $sql = sprintf(
            'SELECT %s FROM %s WHERE %s = %f LIMIT 1',
            $field,
            $table,
            $field_search,
            $condition
        );
    } else {
        $sql = sprintf(
            "SELECT %s FROM %s WHERE %s = '%s' LIMIT 1",
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
function postgresql_db_get_row($table, $field_search, $condition, $fields=false)
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
            'SELECT %s FROM "%s" WHERE "%s" = %d LIMIT 1',
            $fields,
            $table,
            $field_search,
            $condition
        );
    } else if (is_float($condition) || is_double($condition)) {
        $sql = sprintf(
            'SELECT %s FROM "%s" WHERE "%s" = %f LIMIT 1',
            $fields,
            $table,
            $field_search,
            $condition
        );
    } else {
        $sql = sprintf(
            "SELECT %s FROM \"%s\" WHERE \"%s\" = '%s' LIMIT 1",
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


function postgresql_db_get_all_rows_sql($sql, $search_history_db=false, $cache=true, $dbconnection=false)
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
        $history_db_connection = db_connect($config['history_db_host'], $config['history_db_name'], $config['history_db_user'], $config['history_db_pass'], io_output_password($config['history_db_port'], false));
        if ($history_db_connection !== false) {
            $history = postgresql_db_process_sql($sql, 'affected_rows', $config['history_db_connection'], false);
        }

        if ($history === false) {
            $history = [];
        }
    }

    $return = postgresql_db_process_sql($sql, 'affected_rows', $dbconnection, $cache);
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


function postgresql_insert_id($dbconnection='')
{
    global $config;

    if ($dbconnection !== '') {
        $insert_query = pg_query($dbconnection, 'SELECT lastval();');
        $insert_id = pg_fetch_row($insert_query);
        $result = $insert_id[0];
    } else {
        $insert_query = pg_query($config['dbconnection'], 'SELECT lastval();');
        $insert_id = pg_fetch_row($insert_query);
        $result = $insert_id[0];
    }

    return $result;
}


function postgresql_db_process_sql($sql, $rettype='affected_rows', $dbconnection='', $cache=true, &$status=null)
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
        $start = microtime(true);
        if ($dbconnection !== '') {
            pg_send_query($dbconnection, $sql);
            $result = pg_get_result($dbconnection);
        } else {
            pg_send_query($config['dbconnection'], $sql);
            $result = pg_get_result($config['dbconnection']);
        }

        $time = (microtime(true) - $start);

        $resultError = pg_result_error($result);

        if (($result === false) || (!empty($resultError))) {
            $backtrace = debug_backtrace();
            $error = sprintf(
                '%s (\'%s\') in <strong>%s</strong> on line %d',
                pg_result_error($result),
                $sql,
                $backtrace[0]['file'],
                $backtrace[0]['line']
            );
            db_add_database_debug_trace($sql, pg_result_error($result));
            set_error_handler('db_sql_error_handler');
            trigger_error($error);
            restore_error_handler();

            return false;
        } else {
            $status = pg_result_status($result);
            $rows = pg_affected_rows($result);

            if ($status !== 2) {
                // The query NOT IS a select
                if ($rettype == 'insert_id') {
                    $result = postgresql_insert_id($dbconnection);
                } else if ($rettype == 'info') {
                    $result = pg_result_status($result, PGSQL_STATUS_STRING);
                } else {
                    $rows = pg_affected_rows($result);
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
                while ($row = pg_fetch_assoc($result)) {
                    array_push($retval, $row);
                }

                if ($cache === true) {
                    $sql_cache[$sql_cache['id']][$sql] = $retval;
                }

                pg_free_result($result);
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
function postgresql_db_get_all_rows_in_table($table, $order_field='', $order='ASC')
{
    if ($order_field != '') {
        return db_get_all_rows_sql('SELECT * FROM "'.$table.'" ORDER BY '.$order_field.' '.$order);
    } else {
        return db_get_all_rows_sql('SELECT * FROM "'.$table.'"');
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
function postgresql_db_process_sql_insert($table, $values)
{
     // Empty rows or values not processed
    if (empty($values)) {
        return false;
    }

    $values = (array) $values;

    $query = sprintf('INSERT INTO "%s" ', $table);
    $fields = [];
    $values_str = '';
    $i = 1;
    $max = count($values);
    foreach ($values as $field => $value) {
        // Add the correct escaping to values
        if ($field[0] != '"') {
            $field = '"'.$field.'"';
        }

        array_push($fields, $field);

        if (is_null($value)) {
            $values_str .= 'NULL';
        } else if (is_int($value) || is_bool($value)) {
            $values_str .= sprintf('%d', $value);
        } else if (is_float($value) || is_double($value)) {
            $values_str .= sprintf('%f', $value);
        } else if (is_string($value) && (strtoupper($value) === 'NULL')) {
            $values_str .= 'NULL';
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

    return db_process_sql($query, 'insert_id');
}


/**
 * Escape string to set it properly to use in sql queries
 *
 * @param string String to be cleaned.
 *
 * @return string String cleaned.
 */
function postgresql_escape_string_sql($string)
{
    $str = pg_escape_string($string);

    return $str;
}


function postgresql_encapsule_fields_with_same_name_to_instructions($field)
{
    $return = $field;

    if (is_string($return)) {
        if ($return[0] !== '"') {
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

 db_get_value_filter ('description', 'talert_templates',
 array ('name' => 'My alert', 'type' => 'regex'), 'OR');
 // Equivalent to:
 // SELECT description FROM talert_templates WHERE name = 'My alert' OR type = 'equal' LIMIT 1
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
function postgresql_db_get_value_filter($field, $table, $filter, $where_join='AND', $search_history_db=false)
{
    if (! is_array($filter) || empty($filter)) {
        return false;
    }

    // Avoid limit and offset if given
    unset($filter['limit']);
    unset($filter['offset']);

    if (strstr($field, '(') === false) {
        // It is a field.
        $field = '"'.$field.'"';
    }

    $sql = sprintf(
        'SELECT %s FROM "%s" WHERE %s LIMIT 1',
        $field,
        $table,
        db_format_array_where_clause_sql($filter, $where_join)
    );

    $result = db_get_all_rows_sql($sql, $search_history_db);

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
 */
function postgresql_db_format_array_where_clause_sql($values, $join='AND', $prefix=false)
{
    $fields = [];

    if (! is_array($values)) {
        return '';
    }

    $query = '';
    $limit = '';
    $offset = '';
    $order = '';
    $group = '';
    if (isset($values['limit'])) {
        $limit = sprintf(' LIMIT %d', $values['limit']);
        unset($values['limit']);
    }

    if (isset($values['offset'])) {
        $offset = sprintf(' OFFSET %d', $values['offset']);
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
        if (is_numeric($field)) {
            // User provide the exact operation to do
            $query .= $value;

            if ($i < $max) {
                $query .= ' '.$join.' ';
            }

            $i++;
            continue;
        }

        if ($field[0] != '"') {
            // If the field is as <table>.<field>, don't scape.
            if (strstr($field, '.') === false) {
                $field = '"'.$field.'"';
            }
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

        $i++;
    }

    return (! empty($query) ? $prefix : '').$query.$group.$order.$limit.$offset;
}


/**
 * Get the first value of the first row of a table result from query.
 *
 * @param string SQL select statement to execute.
 *
 * @return the first value of the first row of a table result from query.
 */
function postgresql_db_get_value_sql($sql, $dbconnection=false, $search_history_db=false)
{
    $sql .= ' LIMIT 1';
    $result = postgresql_db_get_all_rows_sql($sql, $search_history_db, true, $dbconnection);

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
function postgresql_db_get_row_sql($sql, $search_history_db=false, $cache=true)
{
    $sql .= ' LIMIT 1';
    $result = postgresql_db_get_all_rows_sql($sql, $search_history_db, $cache);

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
function postgresql_db_get_row_filter($table, $filter, $fields=false, $where_join='AND', $historydb=false, $cache=true)
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

    return db_get_row_sql($sql, $historydb, $cache);
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
function postgresql_db_get_all_rows_filter($table, $filter=[], $fields=false, $where_join='AND', $search_history_db=false, $returnSQL=false)
{
    // TODO: Validate and clean fields
    if (empty($fields)) {
        $fields = '*';
    } else if (is_array($fields)) {
        $fields = implode(',', $fields);
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
function postgresql_db_get_num_rows($sql)
{
    $result = pg_query($sql);

    return pg_num_rows($result);
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
function postgresql_db_get_all_rows_field_filter($table, $field, $condition, $order_field='')
{
    if (is_int($condition) || is_bool($condition)) {
        $sql = sprintf('SELECT * FROM "%s" WHERE "%s" = %d', $table, $field, $condition);
    } else if (is_float($condition) || is_double($condition)) {
        $sql = sprintf('SELECT * FROM "%s" WHERE "%s" = %f', $table, $field, $condition);
    } else {
        $sql = sprintf("SELECT * FROM \"%s\" WHERE \"%s\" = '%s'", $table, $field, $condition);
    }

    if ($order_field != '') {
        $sql .= sprintf(' ORDER BY %s', $order_field);
    }

    return db_get_all_rows_sql($sql);
}


/**
 * Get all the rows in a table of the databes filtering from a field.
 *
 * @param string Database table name.
 * @param string Field of the table.
 *
 * @return mixed A matrix with all the values in the table that matches the condition in the field
 */
function postgresql_db_get_all_fields_in_table($table, $field='', $condition='', $order_field='')
{
    $sql = sprintf('SELECT * FROM "%s"', $table);

    if ($condition != '') {
        $sql .= sprintf(" WHERE \"%s\" = '%s'", $field, $condition);
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
 * UPDATE table SET `name` = "Name", `description` = "Long description" WHERE id=1
 * </code>
 *
 * @param array Values to be formatted in an array indexed by the field name.
 *
 * @return string Values joined into an SQL string that can fits into an UPDATE
 * sentence.
 */
function postgresql_db_format_array_to_update_sql($values)
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
            $sql = sprintf('"%s" = NULL', $field);
        } else if (is_int($value) || is_bool($value)) {
            $sql = sprintf('"%s" = %d', $field, $value);
        } else if (is_float($value) || is_double($value)) {
            $sql = sprintf('"%s" = %f', $field, $value);
        } else {
            // String
            if (isset($value[0]) && $value[0] == '`') {
                // Don't round with quotes if it references a field
                $sql = sprintf('"%s" = %s', $field, $value);
            } else {
                $sql = sprintf("\"%s\" = '%s'", $field, $value);
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
function postgresql_db_process_sql_update($table, $values, $where=false, $where_join='AND')
{
    $query = sprintf(
        'UPDATE "%s" SET %s',
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

    return db_process_sql($query);
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
function postgresql_db_process_sql_delete($table, $where, $where_join='AND')
{
    if (empty($where)) {
        // Should avoid any mistake that lead to deleting all data
        return false;
    }

    $query = sprintf('DELETE FROM "%s" WHERE ', $table);

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


/**
 * Get row by row the DB by SQL query. The first time pass the SQL query and
 * rest of times pass none for iterate in table and extract row by row, and
 * the end return false.
 *
 * @param  boolean  $new    Default true, if true start to query.
 * @param  resource $result The resource of mysql for access to query.
 * @param  string   $sql
 * @return mixed The row or false in error.
 */
function postgresql_db_get_all_row_by_steps_sql($new=true, &$result, $sql=null)
{
    if ($new == true) {
        $result = pg_query($sql);
    }

    return pg_fetch_assoc($result);
}


/**
 * Starts a database transaction.
 */
function postgresql_db_process_sql_begin()
{
    pg_query('BEGIN TRANSACTION');
}


/**
 * Commits a database transaction.
 */
function postgresql_db_process_sql_commit()
{
    pg_query('COMMIT TRANSACTION');
}


/**
 * Rollbacks a database transaction.
 */
function postgresql_db_process_sql_rollback()
{
    pg_query('ROLLBACK TRANSACTION');
}


/**
 * Get last error.
 *
 * @return string Return the string error.
 */
function postgresql_db_get_last_error()
{
    return pg_last_error();
}


/**
 * This function gets the time from either system or sql based on preference and returns it
 *
 * @return integer Unix timestamp
 */
function postgresql_get_system_time()
{
    global $config;

    static $time = 0;

    if ($time != 0) {
        return $time;
    }

    if ($config['timesource'] == 'sql') {
        $time = db_get_sql("SELECT ceil(date_part('epoch', CURRENT_TIMESTAMP));");
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
function postgresql_db_get_type_field_table($table, $field)
{
    $result = pg_query('SELECT parameters FROM '.$table);

    return pg_field_type($result, $field);
}
