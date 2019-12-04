<?php
/**
 * Session manager.
 *
 * @category   Session handler.
 * @package    Pandora FMS.
 * @subpackage OpenSource.
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
 * Please see http://pandorafms.org for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */

// Begin.


/**
 * Open session.
 *
 * @param string $save_path    Save path.
 * @param string $session_name Session name.
 *
 * @return boolean
 */
function pandora_session_open($save_path, $session_name)
{
    return true;
}


/**
 * Close session.
 *
 * @return boolean
 */
function pandora_session_close()
{
    return true;
}


/**
 * Read a session.
 *
 * @param string $session_id Session ID.
 *
 * @return string Session data.
 */
function pandora_session_read($session_id)
{
    $session_id = addslashes($session_id);
    $session_data = db_get_value(
        'data',
        'tsessions_php',
        'id_session',
        $session_id
    );

    if (!empty($session_data)) {
        return $session_data;
    } else {
        return '';
    }
}


/**
 * Write session data.
 *
 * @param string $session_id Session id.
 * @param string $data       Data.
 *
 * @return boolean
 */
function pandora_session_write($session_id, $data)
{
    $session_id = addslashes($session_id);

    if (is_ajax()) {
        // Avoid session upadte while processing ajax responses - notifications.
        if (get_parameter('check_new_notifications', false)) {
            return true;
        }
    }

    $values = [];
    $values['last_active'] = time();

    if (!empty($data)) {
        $values['data'] = addslashes($data);
    }

    $session_exists = (bool) db_get_value(
        'COUNT(id_session)',
        'tsessions_php',
        'id_session',
        $session_id
    );

    if (!$session_exists) {
        $values['id_session'] = $session_id;
        $retval_write = db_process_sql_insert('tsessions_php', $values);
    } else {
        $retval_write = db_process_sql_update(
            'tsessions_php',
            $values,
            ['id_session' => $session_id]
        );
    }

    return $retval_write !== false;
}


/**
 * Destroy a session.
 *
 * @param string $session_id Session Id.
 *
 * @return boolean
 */
function pandora_session_destroy($session_id)
{
    $session_id = addslashes($session_id);

    $retval = (bool) db_process_sql_delete(
        'tsessions_php',
        ['id_session' => $session_id]
    );

    return $retval;
}


/**
 * Session garbage collector.
 *
 * @param integer $max_lifetime Max lifetime.
 *
 * @return boolean.
 */
function pandora_session_gc($max_lifetime=300)
{
    global $config;

    if (isset($config['session_timeout'])) {
        $session_timeout = $config['session_timeout'];
    } else {
        // If $config doesn`t work ...
        $session_timeout = db_get_value(
            'value',
            'tconfig',
            'token',
            'session_timeout'
        );
    }

    if (empty($session_timeout) === false) {
        if ($session_timeout == -1) {
            // The session expires in 10 years.
            $session_timeout = 315576000;
        } else {
            $session_timeout *= 60;
        }

        $max_lifetime = $session_timeout;
    }

    $time_limit = (time() - $max_lifetime);

    $retval = (bool) db_process_sql_delete(
        'tsessions_php',
        [
            'last_active' => '<'.$time_limit,
        ]
    );

    // Deleting cron and empty sessions.
    $sql = 'DELETE FROM tsessions_php WHERE data IS NULL';
    db_process_sql($sql);

    return $retval;
}


// TODO: SAML should work with pandora session handlers.
if (db_get_value('value', 'tconfig', 'token', 'auth') != 'saml') {
    $result_handler = session_set_save_handler(
        'pandora_session_open',
        'pandora_session_close',
        'pandora_session_read',
        'pandora_session_write',
        'pandora_session_destroy',
        'pandora_session_gc'
    );
}
