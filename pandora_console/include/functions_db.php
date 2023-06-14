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

/**
 * @package    Include
 * @subpackage DataBase
 */

use PandoraFMS\Enterprise\Metaconsole\Synchronizer;

require_once $config['homedir'].'/include/functions_extensions.php';
require_once $config['homedir'].'/include/functions_groups.php';
require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_modules.php';
require_once $config['homedir'].'/include/functions_alerts.php';
require_once $config['homedir'].'/include/functions_users.php';
require_once $config['homedir'].'/include/functions_ui.php';


function db_select_engine()
{
    global $config;

    switch ($config['dbtype']) {
        case 'mysql':
            include_once $config['homedir'].'/include/db/mysql.php';
        break;

        case 'postgresql':
            include_once $config['homedir'].'/include/db/postgresql.php';
        break;

        case 'oracle':
            include_once $config['homedir'].'/include/db/oracle.php';
        break;
    }
}


/**
 * Connects to target DB.
 *
 * @param array $setup Database definition.
 *
 * @return mixed Dbconnection or null.
 */
function get_dbconnection(array $setup)
{
    return mysqli_connect(
        $setup['dbhost'],
        $setup['dbuser'],
        $setup['dbpass'],
        $setup['dbname'],
        $setup['dbport']
    );
}


/**
 * Connect bbdd.
 *
 * @param string  $host     Host.
 * @param string  $db       Db.
 * @param string  $user     User.
 * @param string  $pass     Pass.
 * @param string  $port     Port.
 * @param boolean $critical Critical.
 * @param string  $charset  Charset.
 *
 * @return mysqli|false
 */
function db_connect(
    $host=null,
    $db=null,
    $user=null,
    $pass=null,
    $port=null,
    $critical=true,
    $charset=null
) {
    global $config;
    static $error = 0;

    switch ($config['dbtype']) {
        case 'mysql':
            $return = mysql_connect_db(
                $host,
                $db,
                $user,
                $pass,
                $port,
                $charset
            );
        break;

        case 'postgresql':
            $return = postgresql_connect_db($host, $db, $user, $pass, $port);
        break;

        case 'oracle':
            $return = oracle_connect_db($host, $db, $user, $pass, $port);
        break;

        default:
            $return = false;
        break;
    }

    // Something went wrong.
    if ($return === false) {
        if ($critical) {
            $url = explode('/', $_SERVER['REQUEST_URI']);
            $flag_url = 0;
            foreach ($url as $key => $value) {
                if (strpos($value, 'index.php') !== false || $flag_url) {
                    $flag_url = 1;
                    unset($url[$key]);
                } else if (strpos($value, 'enterprise') !== false || $flag_url) {
                    $flag_url = 1;
                    unset($url[$key]);
                }
            }

            $config['homeurl'] = rtrim(join('/', $url), '/');
            $config['homeurl_static'] = $config['homeurl'];
            $ownDir = dirname(__FILE__).DIRECTORY_SEPARATOR;
            $config['homedir'] = $ownDir;
            $login_screen = 'error_authconfig';
            include $config['homedir'].'../general/error_screen.php';
            exit;
        } else if ($error == 0) {
            // Display the error once even if multiple
            // connection attempts are made.
            $error = 1;
            ui_print_error_message(
                __('Error connecting to database %s at %s.', $db, $host)
            );
        }
    }

    return $return;
}


/**
 * When you delete (with the function "db_process_sql_delete" or other) any row in
 * any table, some times the cache save the data just deleted, because you
 * must use "db_clean_cache".
 */


/**
 * Escape string to set it properly to use in sql queries
 *
 * @param string String to be cleaned.
 *
 * @return string String cleaned.
 */
function db_escape_string_sql($string)
{
    global $config;

    switch ($config['dbtype']) {
        case 'mysql':
        return mysql_escape_string_sql($string);

            break;
        case 'postgresql':
        return postgresql_escape_string_sql($string);

            break;
        case 'oracle':
        return oracle_escape_string_sql($string);

            break;
    }
}


function db_encapsule_fields_with_same_name_to_instructions($field)
{
    global $config;

    switch ($config['dbtype']) {
        case 'mysql':
        return mysql_encapsule_fields_with_same_name_to_instructions($field);

            break;
        case 'postgresql':
        return postgresql_encapsule_fields_with_same_name_to_instructions($field);

            break;
        case 'oracle':
        return oracle_encapsule_fields_with_same_name_to_instructions($field);

            break;
    }
}


// Alias for 'db_encapsule_fields_with_same_name_to_instructions'
function db_escape_key_identifier($field)
{
    return db_encapsule_fields_with_same_name_to_instructions($field);
}


/**
 * Adds an audit log entry (new function in 3.0)
 *
 * @param string $accion      Action description
 * @param string $descripcion Long action description
 * @param string $id          User id, by default is the user that login.
 * @param string $ip          The ip to make the action, by default is $_SERVER['REMOTE_ADDR'] or $config["remote_addr"]
 * @param string $info        The extended info for enterprise audit, by default is empty string.
 *
 * @return integer Return the id of row in tsesion or false in case of fail.
 */
function db_pandora_audit($accion, $descripcion, $user_id=false, $ip=true, $info='')
{
    global $config;

    // Ignore $ip and always set the ip address.
    if (isset($config['remote_addr']) === true) {
        $ip = $config['remote_addr'];
    } else {
        if (isset($_SERVER['REMOTE_ADDR']) === true) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = __('N/A');
        }
    }

    if ($user_id !== false) {
        $id = $user_id;
    } else {
        $id = (isset($config['id_user']) === true) ? $config['id_user'] : 0;
    }

    $accion = io_safe_input($accion);
    $descripcion = io_safe_input($descripcion);

    $values = [
        'id_usuario'  => $id,
        'accion'      => $accion,
        'ip_origen'   => $ip,
        'descripcion' => $descripcion,
        'fecha'       => date('Y-m-d H:i:s'),
        'utimestamp'  => time(),
    ];

    $id_audit = db_process_sql_insert('tsesion', $values);

    $valor = ''.$values['fecha'].' - '.io_safe_output($id).' - '.io_safe_output($accion).' - '.$ip.' - '.io_safe_output($descripcion)."\n";

    if ((bool) $config['audit_log_enabled'] === true) {
        file_put_contents($config['homedir'].'/log/audit.log', $valor, FILE_APPEND);
    }

    enterprise_include_once('include/functions_audit.php');
    enterprise_hook('audit_pandora_enterprise', [$id_audit, $info]);

    return $id_audit;
}


/**
 * Log in a user into Pandora.
 *
 * @param string $id_user User id.
 * @param string $ip      Client user IP address.
 *
 * @return void
 */
function db_logon($id_user, $ip)
{
    db_pandora_audit(
        AUDIT_LOG_USER_REGISTRATION,
        'Logged in',
        $id_user,
        $ip
    );

    // Update last registry of user to set last logon. How do we audit when the user was created then?
    process_user_contact($id_user);
}


/**
 * Log out a user into Pandora.
 *
 * @param string $id_user User id.
 * @param string $ip      Client user IP address.
 *
 * @return void
 */
function db_logoff($id_user, $ip)
{
    db_pandora_audit(
        AUDIT_LOG_USER_REGISTRATION,
        'Logged out',
        $id_user,
        $ip
    );
}


$sql_cache = ['saved' => []];


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
function db_get_value(
    $field,
    $table,
    $field_search=1,
    $condition=1,
    $search_history_db=false,
    $cache=true
) {
    global $config;

    switch ($config['dbtype']) {
        case 'mysql':
        default:
        return mysql_db_get_value($field, $table, $field_search, $condition, $search_history_db, $cache);

        case 'postgresql':
        return postgresql_db_get_value($field, $table, $field_search, $condition, $search_history_db, $cache);

        case 'oracle':
        return oracle_db_get_value($field, $table, $field_search, $condition, $search_history_db, $cache);
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
function db_get_value_filter($field, $table, $filter, $where_join='AND', $search_history_db=false)
{
    global $config;

    switch ($config['dbtype']) {
        case 'mysql':
        return mysql_db_get_value_filter($field, $table, $filter, $where_join, $search_history_db);

            break;
        case 'postgresql':
        return postgresql_db_get_value_filter($field, $table, $filter, $where_join, $search_history_db);

            break;
        case 'oracle':
        return oracle_db_get_value_filter($field, $table, $filter, $where_join, $search_history_db);

            break;
    }
}


/**
 * Get the first value of the first row of a table result from query.
 *
 * @param string SQL select statement to execute.
 *
 * @return mixed the first value of the first row of a table result from query.
 */
function db_get_value_sql($sql, $dbconnection=false, $search_history_db=false)
{
    global $config;

    switch ($config['dbtype']) {
        case 'mysql':
        return mysql_db_get_value_sql($sql, $dbconnection, $search_history_db);

            break;
        case 'postgresql':
        return postgresql_db_get_value_sql($sql, $dbconnection, $search_history_db);

            break;
        case 'oracle':
        return oracle_db_get_value_sql($sql, $dbconnection, $search_history_db);

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
function db_get_row_sql($sql, $search_history_db=false, $cache=true)
{
    global $config;

    switch ($config['dbtype']) {
        case 'mysql':
        return mysql_db_get_row_sql($sql, $search_history_db, $cache);

            break;
        case 'postgresql':
        return postgresql_db_get_row_sql($sql, $search_history_db, $cache);

            break;
        case 'oracle':
        return oracle_db_get_row_sql($sql, $search_history_db, $cache);

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
function db_get_row($table, $field_search, $condition, $fields=false, $cache=true)
{
    global $config;

    switch ($config['dbtype']) {
        case 'mysql':
        return mysql_db_get_row($table, $field_search, $condition, $fields, $cache);

            break;
        case 'postgresql':
        return postgresql_db_get_row($table, $field_search, $condition, $fields);

            break;
        case 'oracle':
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
 * @param boolean                                                  $cache Use cache or not.
 *
 * @return mixed Array of the row or false in case of error.
 */
function db_get_row_filter($table, $filter, $fields=false, $where_join='AND', $historydb=false, $cache=true)
{
    global $config;

    switch ($config['dbtype']) {
        case 'mysql':
        return mysql_db_get_row_filter($table, $filter, $fields, $where_join, $historydb, $cache);

            break;
        case 'postgresql':
        return postgresql_db_get_row_filter($table, $filter, $fields, $where_join, $historydb, $cache);

            break;
        case 'oracle':
        return oracle_db_get_row_filter($table, $filter, $fields, $where_join, $historydb, $cache);

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
function db_get_sql($sql, $field=0, $search_history_db=false)
{
    $result = db_get_all_rows_sql($sql, $search_history_db);

    if ($result === false) {
        return false;
    }

    $ax = 0;
    foreach ($result[0] as $f) {
        if ($field == $ax) {
            return $f;
        }

        $ax++;
    }
}


/**
 * Get all the result rows using an SQL statement.
 *
 * @param string  $sql               SQL statement to execute.
 * @param boolean $search_history_db If want to search in history database also.
 * @param boolean $cache             If want to use cache (true by default).
 * @param mixed   $dbconnection      Use custom database connection (false default).
 *
 * @return mixed A matrix with all the values returned from the SQL statement or
 * false in case of empty result
 */
function db_get_all_rows_sql(
    $sql,
    $search_history_db=false,
    $cache=true,
    $dbconnection=false
) {
    global $config;

    switch ($config['dbtype']) {
        case 'mysql':
        default:
        return mysql_db_get_all_rows_sql($sql, $search_history_db, $cache, $dbconnection);

        case 'postgresql':
        return postgresql_db_get_all_rows_sql($sql, $search_history_db, $cache, $dbconnection);

        case 'oracle':
        return oracle_db_get_all_rows_sql($sql, $search_history_db, $cache, $dbconnection);
    }
}


/**
 * Returns the time the module is in unknown status (by events)
 *
 * @param integer $id_agente_modulo Module to check.
 * @param boolean $tstart           Begin of search.
 * @param boolean $tend             End of search.
 * @param boolean $historydb        HistoryDb.
 * @param integer $fix_to_range     Range.
 *
 * @return array Return array or false.
 */
function db_get_module_ranges_unknown(
    $id_agente_modulo,
    $tstart=false,
    $tend=false,
    $historydb=false,
    $fix_to_range=0
) {
    global $config;

    if (isset($id_agente_modulo) === false) {
        return false;
    }

    if ((isset($tstart) === false) || ($tstart === false)) {
        // Return data from the begining.
        $tstart = 0;
    }

    if ((isset($tend) === false) || ($tend === false)) {
        // Return data until now.
        $tend = time();
    }

    if ($tstart > $tend) {
        return false;
    }

    // Retrieve going unknown events in range.
    $query = sprintf(
        'SELECT *
        FROM tevento
        WHERE id_agentmodule = %d
            AND event_type like "going_%%"
            AND utimestamp >= %d
            AND utimestamp <= %d
            ORDER BY utimestamp ASC
        ',
        $id_agente_modulo,
        $tstart,
        $tend
    );
    $events = db_get_all_rows_sql($query, $historydb);

    $query  = sprintf(
        'SELECT *
        FROM tevento
        WHERE id_agentmodule = %d
            AND event_type like "going_%%"
            AND utimestamp < %d
            ORDER BY utimestamp DESC
            LIMIT 1
        ',
        $id_agente_modulo,
        $tstart
    );
    $previous_event = db_get_all_rows_sql($query, $historydb);

    if ($previous_event !== false) {
        $last_status = ($previous_event[0]['event_type'] == 'going_unknown') ? 1 : 0;
    } else {
        $last_status = 0;
    }

    if ((is_array($events) === false)
        && (is_array($previous_event) === false)
    ) {
        return false;
    }

    if (is_array($events) === false) {
        if ($previous_event[0]['event_type'] == 'going_unknown') {
            return [
                [
                    'time_from' => (($fix_to_range == 1) ? $tstart : $previous_event[0]['utimestamp']),
                ],
            ];
        }
    }

    $return = [];
    $i = 0;
    if (is_array($events) === true) {
        foreach ($events as $event) {
            switch ($event['event_type']) {
                case 'going_up_critical':
                case 'going_up_warning':
                case 'going_up_normal':
                case 'going_down_critical':
                case 'going_down_warning':
                case 'going_down_normal':{
                    if ($last_status == 1) {
                        $return[$i]['time_to'] = $event['utimestamp'];
                        $i++;
                        $last_status = 0;
                    }
                    break;
                }
                case 'going_unknown':{
                    if ($last_status == 0) {
                        $return[$i] = [];
                        $return[$i]['time_from'] = $event['utimestamp'];
                        $last_status = 1;
                    }

                    break;
                }
                default:
                    // Nothing.
                break;
            }
        }
    }

    if (isset($return[0]) === false) {
        return false;
    }

    return $return;
}


/**
 * Uncompresses and returns the data of a given id_agent_module
 *
 * @param integer    $id_agente_modulo Id_agente_modulo.
 * @param utimestamp $tstart           Begin of the catch.
 * @param utimestamp $tend             End of the catch.
 * @param integer    $slice_size       Size of slice(default-> module_interval).
 *
 * @return array with the data uncompressed in blocks of module_interval
 * false in case of empty result
 *
 * Note: All "unknown" data are marked as NULL
 * Warning: Be careful with the amount of data, check your RAM size available
 * We'll return a bidimensional array
 * Structure returned: schema:
 *
 * uncompressed_data =>
 *      pool_id (int)
 *          utimestamp (start of current slice)
 *          data
 *              array
 *                  datos
 *                  utimestamp
 */
function db_uncompress_module_data(
    $id_agente_modulo,
    $tstart=false,
    $tend=false,
    $slice_size=false,
    $force_slice_not_data=false
) {
    global $config;

    if (isset($id_agente_modulo) === false) {
        return false;
    }

    if ((isset($tend) === false) || ($tend === false)) {
        // Return data until now.
        $tend = time();
    }

    if ($tstart > $tend) {
        return false;
    }

    $search_historydb = false;
    $table = 'tagente_datos';

    $module = modules_get_agentmodule($id_agente_modulo);
    if ($module === false) {
        // Module not exists.
        return false;
    }

    $module_type = $module['id_tipo_modulo'];
    $module_type_str = modules_get_type_name($module_type);

    if (strstr($module_type_str, 'string') !== false) {
        $table = 'tagente_datos_string';
    }

    $flag_async = false;
    if (strstr($module_type_str, 'async_data') !== false) {
        $flag_async = true;
    }

    if (strstr($module_type_str, 'async_string') !== false) {
        $flag_async = true;
    }

    if (strstr($module_type_str, 'async_proc') !== false) {
        $flag_async = true;
    }

    $result = modules_get_first_date($id_agente_modulo, $tstart);
    $first_utimestamp = $result['first_utimestamp'];
    $search_historydb = (isset($result['search_historydb']) === true) ? $result['search_historydb'] : false;

    if ($first_utimestamp === false) {
        $first_data['utimestamp'] = $tstart;
        $first_data['datos']      = false;
    } else {
        $query = sprintf(
            'SELECT datos,utimestamp
            FROM %s
            WHERE id_agente_modulo = %d
                AND utimestamp = %d
            ',
            $table,
            $id_agente_modulo,
            $first_utimestamp
        );

        $data = db_get_all_rows_sql($query, $search_historydb);

        if ($data === false) {
            // First utimestamp not found in active database
            // SEARCH HISTORY DB.
            $search_historydb = true;
            $data = db_get_all_rows_sql($query, $search_historydb);
        }

        if ($data === false) {
            // Not init.
            $first_data['utimestamp'] = $tstart;
            $first_data['datos']      = false;
        } else {
            $first_data['utimestamp'] = $data[0]['utimestamp'];
            $first_data['datos']      = $data[0]['datos'];
        }
    }

    $query = sprintf(
        'SELECT utimestamp, datos
        FROM %s
        WHERE id_agente_modulo = %d
            AND utimestamp >= %d
            AND utimestamp <= %d
        ORDER BY utimestamp ASC
        ',
        $table,
        $id_agente_modulo,
        $tstart,
        $tend
    );

    // Retrieve all data from module in given range.
    $raw_data = db_get_all_rows_sql($query, $search_historydb);

    $module_interval = modules_get_interval($id_agente_modulo);

    if (($force_slice_not_data === false)
        && ($raw_data === false)
        && ( $first_utimestamp === false )
    ) {
        // No data.
        return false;
    }

    // Retrieve going unknown events in range.
    $unknown_events = db_get_module_ranges_unknown(
        $id_agente_modulo,
        $tstart,
        $tend,
        $search_historydb,
        1
    );

    // If time to is missing in last event force time to outside range time.
    if ($unknown_events
        && isset($unknown_events[(count($unknown_events) - 1)]['time_to']) === false
    ) {
        $unknown_events[(count($unknown_events) - 1)]['time_to'] = $tend;
    }

    // If time to is missing in first event force time to outside range time.
    if ($first_data['datos'] === false && !$flag_async) {
        $last_inserted_value = false;
    } else if (($unknown_events
        && isset($unknown_events[0]['time_from']) === false
        && $flag_async === false)
        || ($first_utimestamp < $tstart - (SECONDS_1DAY + 2 * $module_interval)
        && $flag_async === false)
    ) {
        $last_inserted_value = $first_data['datos'];
        $unknown_events[0]['time_from'] = $tstart;
    } else {
        $last_inserted_value = $first_data['datos'];
    }

    // Retrieve module_interval to build the template.
    if ($slice_size === false) {
        $slice_size = $module_interval;
    }

    $return = [];

    // Point current_timestamp to begin of the set and initialize flags.
    $current_timestamp   = $tstart;
    $last_timestamp      = $first_data['utimestamp'];
    $last_value          = $first_data['datos'];

    // Reverse array data optimization.
    if (is_array($raw_data) === true) {
        $raw_data = array_reverse($raw_data);
    }

    // Build template.
    $pool_id = 0;
    $now = time();

    if ($unknown_events) {
        $current_unknown = array_shift($unknown_events);
    } else {
        $current_unknown = null;
    }

    if (is_array($raw_data) === true) {
        $current_raw_data = array_pop($raw_data);
    } else {
        $current_raw_data = null;
    }

    while ($current_timestamp < $tend) {
        $return[$pool_id]['data'] = [];
        $tmp_data   = [];
        $current_timestamp_end = ($current_timestamp + $slice_size);

        if (($current_timestamp > $now)
            || (($current_timestamp - $last_timestamp) > (SECONDS_1DAY + 2 * $module_interval))
        ) {
            $tmp_data['utimestamp'] = $current_timestamp;

            // Check not init.
            $tmp_data['datos'] = $last_value === false ? false : null;

            // Async not unknown.
            if ($flag_async && $tmp_data['datos'] === null) {
                $tmp_data['datos'] = $last_inserted_value;
            }

            // Debug purpose.
            // $tmp_data["obs"] = "unknown extra";.
            array_push($return[$pool_id]['data'], $tmp_data);
        }

        // Insert raw data.
        while (($current_raw_data != null) &&
                (   ($current_timestamp_end > $current_raw_data['utimestamp']) &&
                    ($current_timestamp <= $current_raw_data['utimestamp']) ) ) {
            // Add real data detected.
            if (count($return[$pool_id]['data']) == 0) {
                // Insert first slice data.
                $tmp_data['utimestamp'] = $current_timestamp;
                $tmp_data['datos']  = $last_inserted_value;
                // Debug purpose
                // $tmp_data["obs"] = "virtual data (raw)";.
                $tmp_data['type'] = ($current_timestamp == $tstart || ($current_timestamp == $tend) ? 0 : 1);
                // Virtual data.
                // Add order to avoid usort missorder
                // in same utimestamp data cells.
                $tmp_data['order'] = 1;
                array_push($return[$pool_id]['data'], $tmp_data);
            }

            $tmp_data['utimestamp'] = $current_raw_data['utimestamp'];
            $tmp_data['datos']      = $current_raw_data['datos'];
            $tmp_data['type'] = 0;
            // Real data.
            // Debug purpose
            // $tmp_data["obs"] = "real data";
            // Add order to avoid usort missorder in same utimestamp data cells.
            $tmp_data['order'] = 2;
            array_push($return[$pool_id]['data'], $tmp_data);

            $last_value = $current_raw_data['datos'];
            $last_timestamp = $current_raw_data['utimestamp'];
            if ($raw_data) {
                $current_raw_data = array_pop($raw_data);
            } else {
                $current_raw_data = null;
            }
        }

        // Unknown.
        $data_slices = $return[$pool_id]['data'];
        if (!$flag_async) {
            while (($current_unknown != null) &&
                    ( ( ($current_unknown['time_from'] != null) &&
                        ($current_timestamp_end >= $current_unknown['time_from']) ) ||
                    ($current_timestamp_end >= $current_unknown['time_to']) ) ) {
                if (( $current_timestamp <= $current_unknown['time_from'])
                    && ( $current_timestamp_end >= $current_unknown['time_from'] )
                ) {
                    if (count($return[$pool_id]['data']) == 0) {
                        // Insert first slice data.
                        $tmp_data['utimestamp'] = $current_timestamp;
                        $tmp_data['datos']  = $last_inserted_value;
                        // Debug purpose
                        // $tmp_data["obs"] = "virtual data (e)";
                        // Add order to avoid usort missorder
                        // in same utimestamp data cells.
                        $tmp_data['order'] = 1;

                        array_push($return[$pool_id]['data'], $tmp_data);
                    }

                    // Add unknown state detected.
                    $tmp_data['utimestamp'] = $current_unknown['time_from'];
                    $tmp_data['datos']      = null;
                    // Debug purpose
                    // $tmp_data["obs"] = "event data unknown from";
                    // Add order to avoid usort missorder
                    // in same utimestamp data cells.
                    $tmp_data['order'] = 2;
                    array_push($return[$pool_id]['data'], $tmp_data);
                    $current_unknown['time_from'] = null;
                } else if (($current_timestamp <= $current_unknown['time_to'])
                    && ($current_timestamp_end > $current_unknown['time_to'] )
                ) {
                    if (count($return[$pool_id]['data']) == 0) {
                        // Add first slice data always
                        // Insert first slice data.
                        $tmp_data['utimestamp'] = $current_timestamp;
                        $tmp_data['datos']  = $last_inserted_value;
                        // Debug purpose
                        // $tmp_data["obs"] = "virtual data (event_to)";
                        // Add order to avoid usort missorder
                        // in same utimestamp data cells.
                        $tmp_data['order'] = 1;
                        array_push($return[$pool_id]['data'], $tmp_data);
                    }

                    $tmp_data['utimestamp'] = $current_unknown['time_to'];
                    // Add order to avoid usort missorder
                    // in same utimestamp data cells.
                    $tmp_data['order'] = 2;
                    $i = count($data_slices);
                    while ($i >= 0) {
                        if ($data_slices[$i]['utimestamp'] <= $current_unknown['time_to']) {
                            $tmp_data['datos'] = $data_slices[$i]['datos'] == null ? $last_value : $data_slices[$i]['datos'];
                            break;
                        }

                        $i--;
                    }

                    // Debug purpose
                    // $tmp_data["obs"] = "event data unknown to";.
                    array_push($return[$pool_id]['data'], $tmp_data);
                    if ($unknown_events) {
                        $current_unknown = array_shift($unknown_events);
                    } else {
                        $current_unknown = null;
                    }
                } else {
                    break;
                }
            }
        }

        $return[$pool_id]['utimestamp'] = $current_timestamp;
        if (count($return[$pool_id]['data']) == 0) {
            // Insert first slice data.
            $tmp_data['utimestamp'] = $current_timestamp;
            $tmp_data['datos'] = $last_inserted_value;
            // Debug purpose
            // $tmp_data["obs"] = "virtual data (empty)";.
            array_push($return[$pool_id]['data'], $tmp_data);
        }

        // Sort current slice.
        if (count($return[$pool_id]['data']) > 1) {
            usort(
                $return[$pool_id]['data'],
                function ($a, $b) {
                    if ($a['utimestamp'] == $b['utimestamp']) {
                        return (($a['order'] < $b['order']) ? -1 : 1);
                    }

                    return ($a['utimestamp'] < $b['utimestamp']) ? -1 : 1;
                }
            );
        }

        // Put the last slice data like first element of next slice.
        $last_inserted_value = end($return[$pool_id]['data']);
        $last_inserted_value = $last_inserted_value['datos'];

        // Increment.
        $pool_id++;
        $current_timestamp = $current_timestamp_end;
    }

    // Slice to the end.
    if ($pool_id == 1) {
        $end_array = [];
        $end_array['data'][0]['utimestamp'] = $tend;
        $end_array['data'][0]['datos']      = $last_inserted_value;
        // $end_array['data'][0]['obs']        = 'virtual data END';
        array_push($return, $end_array);
    }

    return $return;
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
function db_get_all_rows_filter($table, $filter=[], $fields=false, $where_join='AND', $search_history_db=false, $returnSQL=false)
{
    global $config;

    switch ($config['dbtype']) {
        case 'mysql':
        return mysql_db_get_all_rows_filter($table, $filter, $fields, $where_join, $search_history_db, $returnSQL);

            break;
        case 'postgresql':
        return postgresql_db_get_all_rows_filter($table, $filter, $fields, $where_join, $search_history_db, $returnSQL);

            break;
        case 'oracle':
        return oracle_db_get_all_rows_filter($table, $filter, $fields, $where_join, $search_history_db, $returnSQL);

            break;
    }
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
function db_get_all_row_by_steps_sql($new, &$result, $sql=null)
{
    global $config;

    $new = ($new ?? true);

    switch ($config['dbtype']) {
        case 'mysql':
        return mysql_db_get_all_row_by_steps_sql($new, $result, $sql);

            break;
        case 'postgresql':
        return postgresql_db_get_all_row_by_steps_sql($new, $result, $sql);

            break;
        case 'oracle':
        return oracle_db_get_all_row_by_steps_sql($new, $result, $sql);

            break;
    }
}


/**
 * Return the count of rows of query.
 *
 * @param  $sql
 * @return integer The count of rows of query.
 */
function db_get_num_rows($sql)
{
    global $config;

    switch ($config['dbtype']) {
        case 'mysql':
        return mysql_db_get_num_rows($sql);

            break;
        case 'postgresql':
        return postgresql_db_get_num_rows($sql);

            break;
        case 'oracle':
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
 * @return boolean True if error level is lower or equal than errno.
 */
function db_sql_error_handler($errno, $errstr)
{
    global $config;

    // If debug is activated, this will also show the backtrace
    if (ui_debug($errstr)) {
        return false;
    }

    if (error_reporting() <= $errno) {
        return false;
    }

    echo '<strong>SQL error</strong>: '.$errstr."<br />\n";

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
function db_add_database_debug_trace($sql, $result=false, $affected=false, $extra=false)
{
    global $config;

    if (! isset($config['debug'])) {
        return false;
    }

    if (! isset($config['db_debug'])) {
        $config['db_debug'] = [];
    }

    if (isset($config['db_debug'][$sql])) {
        $config['db_debug'][$sql]['saved']++;
        return;
    }

    $var = [];
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
function db_clean_cache()
{
    global $sql_cache;

    $sql_cache = ['saved' => []];
}


/**
 * Change the sql cache id to another value
 *
 * @return None
 */
function db_change_cache_id($name, $host)
{
    global $sql_cache;

    // Update the sql cache identification
    $sql_cache['id'] = $name.'_'.$host;
    if (!isset($sql_cache['saved'][$sql_cache['id']])) {
        $sql_cache['saved'][$sql_cache['id']] = 0;
    }
}


/**
 * Get the total cached queries and the databases checked
 *
 * @return (total_queries, total_dbs)
 */
function db_get_cached_queries()
{
    global $sql_cache;

    $total_saved = 0;
    $total_dbs = 0;
    foreach ($sql_cache['saved'] as $saver) {
        $total_saved += format_numeric($saver);
        $total_dbs++;
    }

    return [
        $total_saved,
        $total_dbs,
    ];
}


/**
 * This function comes back with an array in case of SELECT
 * in case of UPDATE, DELETE etc. with affected rows
 * an empty array in case of SELECT without results
 * Queries that return data will be cached so queries don't get repeated.
 *
 * @param string  $sql          SQL statement to execute.
 * @param string  $rettype      What type of info to return in case of INSERT/UPDATE.
 *              'affected_rows' will return mysql_affected_rows (default value)
 *              'insert_id' will return the ID of an autoincrement value
 *              'info' will return the full (debug) information of a query.
 * @param string  $dbconnection Info conecction.
 * @param boolean $cache        Cache.
 * @param string  $status       The status and type of query (support only postgreSQL).
 * @param boolean $autocommit   Set autocommit transaction mode true/false (Only oracle).
 * @param array   $values       Values (Only type insert).
 *
 * @return mixed An array with the rows, columns and values in a multidimensional array or false in error.
 */
function db_process_sql(
    $sql,
    $rettype='affected_rows',
    $dbconnection='',
    $cache=true,
    &$status=null,
    $autocommit=true,
    $values_insert=[]
) {
    global $config;

    $rc = false;
    switch ($config['dbtype']) {
        case 'mysql':
        default:
            $rc = @mysql_db_process_sql(
                $sql,
                $rettype,
                $dbconnection,
                $cache
            );
        break;

        case 'postgresql':
            $rc = @postgresql_db_process_sql($sql, $rettype, $dbconnection, $cache, $status);
        break;

        case 'oracle':
            $rc = oracle_db_process_sql($sql, $rettype, $dbconnection, $cache, $status, $autocommit);
        break;
    }

    db_sync(
        $dbconnection,
        $sql,
        $rc,
        $rettype,
        $values_insert
    );

    return $rc;
}


/**
 * Propagate to nodes.
 *
 * @param mixed $dbconnection Dbconnection.
 * @param mixed $sql          Sql.
 * @param mixed $rc           Rc.
 *
 * @return void
 */
function db_sync(
    $dbconnection,
    $sql,
    $rc,
    $rettype='affected_rows',
    $values_insert=[]
) {
    global $config;
    if (enterprise_hook('is_metaconsole') === true
        && isset($config['centralized_management']) === true
        && (bool) $config['centralized_management'] === true
        && $dbconnection === ''
    ) {
        $errors = null;
        try {
            // Synchronize changes to nodes if needed.
            $sync = new Synchronizer();
            if ($sync !== null) {
                if ($rettype === 'insert_id') {
                    $forceSql = $sync->updateInsertQueryAddPrimaryKey(
                        $values_insert,
                        $rc
                    );
                    if (empty($forceSql) === false) {
                        $sql = $forceSql;
                    }
                }

                if ($sync->queue($sql, $rc) === false) {
                    // Launch events per failed query.
                    $errors = $sync->getLatestErrors();
                    if ($errors !== null) {
                        $errors = join(', ', $errors);
                    } else {
                        $errors = '';
                    }
                }
            }
        } catch (\Exception $e) {
            $errors = $e->getMessage();
        }

        if ($errors !== null) {
            // TODO: Generate pandora event.
            error_log($errors);
        }
    }
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
function db_get_all_rows_in_table($table, $order_field='', $order='ASC')
{
    global $config;

    switch ($config['dbtype']) {
        case 'mysql':
        return mysql_db_get_all_rows_in_table($table, $order_field, $order);

            break;
        case 'postgresql':
        return postgresql_db_get_all_rows_in_table($table, $order_field, $order);

            break;
        case 'oracle':
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
function db_get_all_rows_field_filter($table, $field, $condition, $order_field='')
{
    global $config;

    switch ($config['dbtype']) {
        case 'mysql':
        return mysql_db_get_all_rows_field_filter($table, $field, $condition, $order_field);

            break;
        case 'postgresql':
        return postgresql_db_get_all_rows_field_filter($table, $field, $condition, $order_field);

            break;
        case 'oracle':
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
function db_get_all_fields_in_table($table, $field='', $condition='', $order_field='')
{
    global $config;

    switch ($config['dbtype']) {
        case 'mysql':
        return mysql_db_get_all_fields_in_table($table, $field, $condition, $order_field);

            break;
        case 'postgresql':
        return postgresql_db_get_all_fields_in_table($table, $field, $condition, $order_field);

            break;
        case 'oracle':
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
function db_format_array_to_update_sql($values)
{
    global $config;

    switch ($config['dbtype']) {
        case 'mysql':
        return mysql_db_format_array_to_update_sql($values);

            break;
        case 'postgresql':
        return postgresql_db_format_array_to_update_sql($values);

            break;
        case 'oracle':
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
function db_format_array_where_clause_sql($values, $join='AND', $prefix=false)
{
    global $config;

    switch ($config['dbtype']) {
        case 'mysql':
        return mysql_db_format_array_where_clause_sql($values, $join, $prefix);

            break;
        case 'postgresql':
        return postgresql_db_format_array_where_clause_sql($values, $join, $prefix);

            break;
        case 'oracle':
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
 * @param bool The value will be appended without quotes
 *
 * @result Rows deleted or false if something goes wrong
 */
function db_process_delete_temp($table, $row, $value, $custom_value=false)
{
    global $error;
    // Globalize the errors variable
    global $config;

    switch ($config['dbtype']) {
        case 'mysql':
        case 'postgresql':
            $result = db_process_sql_delete($table, $row.' = '.$value);
        break;

        case 'oracle':
            if ($custom_value || is_int($value) || is_bool($value)
                || is_float($value) || is_double($value)
            ) {
                $result = oracle_db_process_sql_delete_temp($table, $row.' = '.$value);
            } else {
                $result = oracle_db_process_sql_delete_temp($table, $row." = '".$value."'");
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
function db_process_sql_insert($table, $values, $autocommit=true, $sqltostring=false)
{
    global $config;

    switch ($config['dbtype']) {
        case 'mysql':
        return mysql_db_process_sql_insert($table, $values, $sqltostring);

            break;
        case 'postgresql':
        return postgresql_db_process_sql_insert($table, $values);

            break;
        case 'oracle':
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
 * @param string When a                                         $where parameter is given, this will work as the glue
 *                                         between the fields. "AND" operator will be use by default. Other values might
 *                                         be "OR", "AND NOT", "XOR"
 * @param bool Transaction automatically commited or not
 *
 * @return mixed False in case of error or invalid values passed. Affected rows otherwise
 */
function db_process_sql_update($table, $values, $where=false, $where_join='AND', $autocommit=true)
{
    global $config;

    switch ($config['dbtype']) {
        case 'mysql':
        return mysql_db_process_sql_update($table, $values, $where, $where_join);

            break;
        case 'postgresql':
        return postgresql_db_process_sql_update($table, $values, $where, $where_join);

            break;
        case 'oracle':
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
 * @param string When a                                         $where parameter is given, this will work as the glue
 *                                         between the fields. "AND" operator will be use by default. Other values might
 *                                         be "OR", "AND NOT", "XOR"
 *
 * @return mixed False in case of error or invalid values passed. Affected rows otherwise
 */
function db_process_sql_delete($table, $where, $where_join='AND')
{
    global $config;

    switch ($config['dbtype']) {
        case 'mysql':
        return mysql_db_process_sql_delete($table, $where, $where_join);

            break;
        case 'postgresql':
        return postgresql_db_process_sql_delete($table, $where, $where_join);

            break;
        case 'oracle':
        return oracle_db_process_sql_delete($table, $where, $where_join);

            break;
    }
}


/**
 * Starts a database transaction.
 */
function db_process_sql_begin()
{
    global $config;
    $null = null;

    switch ($config['dbtype']) {
        case 'postgresql':
        return postgresql_db_process_sql_begin();

        case 'oracle':
        return oracle_db_process_sql_begin();

        default:
        case 'mysql':
            db_process_sql('SET AUTOCOMMIT = 0', 'affected_rows', '', false, $null, false);
            db_process_sql('START TRANSACTION', 'affected_rows', '', false, $null, false);
        break;
    }
}


/**
 * Commits a database transaction.
 */
function db_process_sql_commit()
{
    global $config;
    $null = null;

    switch ($config['dbtype']) {
        case 'postgresql':
        return postgresql_db_process_sql_commit();

        case 'oracle':
        return oracle_db_process_sql_commit();

        default:
        case 'mysql':
            db_process_sql('COMMIT', 'affected_rows', '', false, $null, false);
            db_process_sql('SET AUTOCOMMIT = 1', 'affected_rows', '', false, $null, false);
        break;
    }
}


/**
 * Rollbacks a database transaction.
 */
function db_process_sql_rollback()
{
    global $config;
    $null = null;

    switch ($config['dbtype']) {
        case 'postgresql':
        return postgresql_db_process_sql_rollback();

        case 'oracle':
        return oracle_db_process_sql_rollback();

        default:
        case 'mysql':
            db_process_sql('ROLLBACK', 'affected_rows', '', false, $null, false);
            db_process_sql('SET AUTOCOMMIT = 1', 'affected_rows', '', false, $null, false);
        break;
    }
}


/**
 * Prints a database debug table with all the queries done in the page loading.
 *
 * This functions does nothing if the config['debug'] flag is not set.
 */
function db_print_database_debug()
{
    global $config;

    if (! isset($config['debug'])) {
        return '';
    }

    echo '<div class="database_debug_title">'.__('Database debug').'</div>';

    $table = new stdClass();
    $table->id = 'database_debug';
    $table->cellpadding = '0';
    $table->width = '95%';
    $table->align = [];
    $table->align[1] = 'left';
    $table->size = [];
    $table->size[0] = '40px';
    $table->size[2] = '30%';
    $table->size[3] = '40px';
    $table->size[4] = '40px';
    $table->size[5] = '40px';
    $table->data = [];
    $table->head = [];
    $table->head[0] = '#';
    $table->head[1] = __('SQL sentence');
    $table->head[2] = __('Result');
    $table->head[3] = __('Rows');
    $table->head[4] = __('Saved');
    $table->head[5] = __('Time (ms)');

    if (! isset($config['db_debug'])) {
        $config['db_debug'] = [];
    }

    $i = 1;
    foreach ($config['db_debug'] as $debug) {
        $data = [];

        $data[0] = $i++;
        $data[1] = $debug['sql'];
        $data[2] = (empty($debug['result']) ? __('OK') : $debug['result']);
        $data[3] = $debug['affected'];
        $data[4] = $debug['saved'];
        $data[5] = (isset($debug['extra']['time']) ? format_numeric(($debug['extra']['time'] * 1000), 0) : '');

        array_push($table->data, $data);

        if (($i % 100) == 0) {
            html_print_table($table);
            $table->data = [];
        }
    }

    html_print_table($table);
}


/**
 * Get last error.
 *
 * @return string Return the string error.
 */
function db_get_last_error()
{
    global $config;

    switch ($config['dbtype']) {
        case 'postgresql':
        return postgresql_db_get_last_error();

        case 'oracle':
        return oracle_db_get_last_error();

        case 'mysql':
        default:
        return mysql_db_get_last_error();
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
function db_get_type_field_table($table, $field)
{
    global $config;

    switch ($config['dbtype']) {
        case 'postgresql':
        return postgresql_db_get_type_field_table($table, $field);

        case 'oracle':
        return oracle_db_get_type_field_table($table, $field);

        case 'mysql':
        default:
        return mysql_db_get_type_field_table($table, $field);
    }
}


/**
 * Get the columns of a table.
 *
 * @param string $table table to retrieve columns.
 *
 * @return array with column names.
 */
function db_get_fields($table)
{
    global $config;

    switch ($config['dbtype']) {
        case 'mysql':
        return mysql_get_fields($table);

            break;
        case 'postgresql':
            // return postgresql_get_fields($table);
        break;

        case 'oracle':
            // return oracle_get_fields($table);
        break;
    }
}


/**
 * @param int Unix timestamp with the date.
 *
 * @return boolean Returns true if the history db has data after the date provided or false otherwise.
 */
function db_search_in_history_db($utimestamp)
{
    global $config;

    $search_in_history_db = false;
    if ($config['history_db_enabled'] == 1) {
        $history_db_start_period = ($config['history_db_days'] * SECONDS_1DAY);

        // If the date is newer than the newest history db data
        if ((time() - $history_db_start_period) >= $utimestamp) {
            $search_in_history_db = true;
        }
    }

    return $search_in_history_db;
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
function db_process_file($path, $handle_error=true)
{
    global $config;

    switch ($config['dbtype']) {
        case 'mysql':
        return mysql_db_process_file($path, $handle_error);

            break;
        case 'postgresql':
            // Not supported
            // return postgresql_db_process_file($path, $handle_error);
        break;

        case 'oracle':
        return oracle_db_process_file($path, $handle_error);

            break;
    }
}


/**
 * Search for minor release files.
 *
 * @return boolean Return if minor release is available or not
 */
function db_check_minor_relase_available()
{
    global $config;

    if (!$config['enable_update_manager']) {
        return false;
    }

    $dir = $config['homedir'].'/extras/mr';

    $have_minor_release = false;

    if (file_exists($dir) && is_dir($dir)) {
        if (is_readable($dir)) {
            $files = scandir($dir);
            // Get all the files from the directory ordered by asc
            if ($files !== false) {
                $pattern = '/^\d+\.sql$/';
                $sqlfiles = preg_grep($pattern, $files);
                // Get the name of the correct files
                $files = null;
                $pattern = '/\.sql$/';
                $replacement = '';
                $sqlfiles_num = preg_replace($pattern, $replacement, $sqlfiles);
                // Get the number of the file
                if ($sqlfiles_num) {
                    foreach ($sqlfiles_num as $sqlfile_num) {
                        if ($config['MR'] < $sqlfile_num) {
                            $have_minor_release = true;
                        }
                    }
                }
            }
        }
    }

    if ($have_minor_release) {
        return true;
    } else {
        return false;
    }
}


/**
 * Search for minor release files.
 *
 * @return boolean Return if minor release is available or not
 */
function db_check_minor_relase_available_to_um($package, $ent, $offline)
{
    global $config;

    if (!$config['enable_update_manager']) {
        return false;
    }

    if (!$ent) {
        $dir = $config['attachment_store'].'/downloads/pandora_console/extras/mr';
    } else {
        if ($offline) {
            $dir = $package.'/extras/mr';
        } else {
            $dir = sys_get_temp_dir().'/pandora_oum/'.$package.'/extras/mr';
        }
    }

    $have_minor_release = false;
    if (file_exists($dir) && is_dir($dir)) {
        if (is_readable($dir)) {
            $files = scandir($dir);
            // Get all the files from the directory ordered by asc
            if ($files !== false) {
                $pattern = '/^\d+\.sql$/';
                $sqlfiles = preg_grep($pattern, $files);
                // Get the name of the correct files
                $files = null;
                $pattern = '/\.sql$/';
                $replacement = '';
                $sqlfiles_num = preg_replace($pattern, $replacement, $sqlfiles);
                // Get the number of the file
                $exists = false;
                foreach ($sqlfiles_num as $num) {
                    $file_dest = $config['homedir']."/extras/mr/updated/$num.sql";
                    if (file_exists($file_dest)) {
                        $exists = true;
                        update_config_token('MR', $num);
                        if (file_exists($config['homedir']."/extras/mr/$num.sql")) {
                            unlink($config['homedir']."/extras/mr/$num.sql");
                        }
                    }
                }

                if ($sqlfiles_num && !$exists) {
                    $have_minor_release = true;
                }
            }
        }
    }

    if ($have_minor_release) {
        return true;
    } else {
        return false;
    }
}


/**
 * Checks if a lock is free or not.
 *
 * @param string $lockname Name.
 *
 * @return boolean Free or not.
 */
function db_is_free_lock($lockname)
{
    global $config;

    $restore = false;
    // Temporary disable to get a valid lock if any...
    if (isset($config['dbcache']) === false) {
        // Set.
        $config['dbcache'] = false;
    } else {
        // Set and keep.
        $cache = $config['dbcache'];
        $config['dbcache'] = false;
        $restore = true;
    }

    $lock_status = db_get_value_sql(
        sprintf(
            'SELECT IS_FREE_LOCK("%s")',
            $lockname
        )
    );

    if ($restore === true) {
        // Restore.
        $config['dbcache'] = $cache;
    } else {
        // Remove.
        unset($config['dbcache']);
    }

    return (bool) $lock_status;
}


/**
 * Tries to get a lock with current name.
 *
 * @param string  $lockname        Lock name.
 * @param integer $expiration_time Expiration time.
 *
 * @return integer 1 - lock OK, able to continue executing
 *                 0 - already locked by another process.
 *                 NULL: something really bad happened
 */
function db_get_lock(string $lockname, int $expiration_time=86400) :?int
{
    global $config;

    // Temporary disable to get a valid lock if any...
    if (isset($config['dbcache']) === true) {
        $cache = $config['dbcache'];
    }

    $config['dbcache'] = false;

    $lock_status = db_get_value_sql(
        sprintf(
            'SELECT IS_FREE_LOCK("%s")',
            $lockname
        )
    );

    if ($lock_status == 1) {
        $lock_status = db_get_value_sql(
            sprintf(
                'SELECT GET_LOCK("%s", %d)',
                $lockname,
                $expiration_time
            )
        );

        if (isset($cache) === true) {
            $config['dbcache'] = $cache;
        } else {
            unset($config['dbcache']);
        }

        if ($lock_status === false) {
            db_pandora_audit(
                AUDIT_LOG_SYSTEM,
                'Issue in Database Lock',
                'system'
            );

            return (int) null;
        }

        return (int) $lock_status;
    }

    if (isset($cache) === true) {
        $config['dbcache'] = $cache;
    } else {
        unset($config['dbcache']);
    }

    return 0;
}


/**
 * Release a previously defined lock.
 *
 * @param string $lockname Lock name.
 *
 * @return integer 1 Lock released.
 *                 0 cannot release (not owned).
 *                 NULL lock does not exist.
 */
function db_release_lock($lockname)
{
    global $config;
    // Temporary disable to get a valid lock if any...
    if (isset($config['dbcache']) === true) {
        $cache = $config['dbcache'];
    }

    $config['dbcache'] = false;

    $return = db_get_value_sql(
        sprintf(
            'SELECT RELEASE_LOCK("%s")',
            $lockname
        )
    );

    if (isset($cache) === true) {
        $config['dbcache'] = $cache;
    } else {
        unset($config['dbcache']);
    }

    return $return;

}


/**
 * Inserts multiples strings into database
 *
 * @param string $table  Table to insert into
 * @param mixed  $values A single value or array of values to insert
 *  (can be a multiple amount of rows).
 *
 * @return mixed False in case of error or invalid values passed.
 * Affected rows otherwise.
 */
function db_process_sql_insert_multiple($table, $values, $only_query=false)
{
    global $config;
    return mysql_db_process_sql_insert_multiple($table, $values, $only_query);
}


/**
 * Update multiples strings into database
 *
 * @param string $table  Table to update into
 * @param mixed  $values A single value or array of values to update
 *  (can be a multiple amount of rows).
 *
 * @return mixed False in case of error or invalid values passed.
 * Affected rows otherwise.
 */
function db_process_sql_update_multiple($table, $values, $only_query=false)
{
    global $config;
    return mysql_db_process_sql_update_multiple($table, $values, $only_query);
}


/**
 * Is lock table.
 *
 * @param string $table Name table is Lock.
 *
 * @return boolean
 */
function db_get_lock_table($table)
{
    global $config;

    if (empty($table) === true) {
        return false;
    }

    $sql = sprintf(
        'SHOW OPEN TABLES
        WHERE `Table` = %s
            AND `Database` = %s
            AND `In_use` > 0',
        $table,
        $config['dbname']
    );

    $result = db_process_sql($sql);

    return (bool) $result['In_use'];
}


/**
 * Lock table.
 *
 * @param string  $table Table Name.
 * @param integer $mode  READ or WRITE.
 *
 * @return boolean
 */
function db_lock_table($table, $mode='WRITE')
{
    global $config;

    if (empty($table) === true) {
        return false;
    }

    $sql = sprintf(
        'LOCK TABLE %s %s',
        $table,
        $mode
    );

    $result = db_process_sql($sql);

    if ($result !== false) {
        $result = true;
    }

    return $result;
}


/**
 * Lock tables.
 *
 * @param array $tables ['name_table','mode'];
 *
 * @return boolean
 */
function db_lock_tables($tables)
{
    global $config;

    if (empty($tables) === true) {
        return false;
    }

    $sql = 'LOCK TABLES ';

    $count_tables = count($tables);
    foreach ($tables as $v) {
        if ($count_tables === 1) {
            $sql .= sprintf(
                '%s %s',
                $v['table'],
                $v['mode']
            );
        } else {
            $sql .= sprintf(
                '%s %s, ',
                $v['table'],
                $v['mode']
            );
        }

        $count_tables--;
    }

    $result = db_process_sql($sql);

    if ($result !== false) {
        $result = true;
    }

    return $result;
}


/**
 * Unlock tables.
 */
function db_unlock_tables()
{
    $result = db_process_sql('UNLOCK TABLES');
    if ($result !== false) {
        $result = true;
    }

    return $result;
}


/**
 * Get column type. Example: 'varchar(60)'.
 *
 * @param string $table  Table name.
 * @param string $column Column name.
 *
 * @return array|boolean
 */
function db_get_column_type(string $table, string $column='')
{
    $sql = sprintf(
        'SELECT column_type FROM information_schema.columns WHERE table_name = "%s"',
        $table
    );

    if (empty($column) === false) {
        $sql .= sprintf(' AND column_name="%s"', $column);
    }

    $result = db_process_sql($sql);

    return $result;
}


/**
 * Validate sql query.
 *
 * @param string $sql    Query for validate.
 * @param mixed  $server Server name where sql must connect.
 *
 * @return boolean True if query is valid.
 */
function db_validate_sql(string $sql, $server=false)
{
    if ($server !== false && is_metaconsole() === true) {
        $setup = metaconsole_get_connection($server);
        if (metaconsole_connect($setup) !== NOERR) {
            return false;
        }
    }

    try {
        error_reporting(0);
        db_process_sql_begin();
        $result = db_process_sql(io_safe_output($sql));
    } catch (Exception $e) {
        // Catch all posible errors.
        $result = false;
    } finally {
        db_process_sql_rollback();
        error_reporting(E_ALL);
    }

    if ($server !== false && is_metaconsole() === true) {
        metaconsole_restore_db();
    }

    return ($result !== false) ? true : false;
}
