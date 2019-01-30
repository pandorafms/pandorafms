<?php

/**
 * Extension to manage a list of gateways and the node address where they should
 * point to.
 *
 * @category   Extensions
 * @package    Pandora FMS
 * @subpackage Community
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

require_once $config['homedir'].'/include/functions_users.php';
require_once $config['homedir'].'/include/functions_groups.php';
require_once $config['homedir'].'/include/functions_notifications.php';


/**
 * Creates a private message to be forwarded to other people
 *
 * @param string $usuario_origen  The sender of the message.
 * @param string $usuario_destino The receiver of the message.
 * @param string $subject         Subject of the message (much like E-Mail).
 * @param string $mensaje         The actual message. This message will be
 *                                cleaned by io_safe_input (html is allowed but
 *                                loose html chars will be translated).
 *
 * @return boolean true when delivered, false in case of error
 */
function messages_create_message(
    string $usuario_origen,
    string $usuario_destino,
    string $subject,
    string $mensaje
) {
    $users = users_get_info();

    if (!array_key_exists($usuario_origen, $users)
        || !array_key_exists($usuario_destino, $users)
    ) {
        return false;
        // Users don't exist so don't send to them.
    }

    // Create message.
    $message_id = db_process_sql_insert(
        'tmensajes',
        [
            'id_usuario_origen' => $usuario_origen,
            'subject'           => $subject,
            'mensaje'           => $mensaje,
            'id_source'         => get_notification_source_id('message'),
            'timestamp'         => get_system_time(),
        ]
    );

    // Update URL
    // Update targets.
    if ($message_id !== false) {
        $return = db_process_sql_insert(
            'tnotification_user',
            [
                'id_mensaje' => $message_id,
                'id_user'    => $usuario_destino,
            ]
        );
    }

    if ($return === false) {
        return false;
    } else {
        return true;
    }
}


/**
 * Creates private messages to be forwarded to groups
 *
 * @param string $usuario_origen The sender of the message.
 * @param string $dest_group     The receivers (group) of the message.
 * @param string $subject        Subject of the message (much like E-Mail).
 * @param string $mensaje        The actual message. This message will be
 *                               cleaned by io_safe_input (html is allowed but
 *                               loose html chars will be translated).
 *
 * @return boolean true when delivered, false in case of error
 */
function messages_create_group(
    string $usuario_origen,
    string $dest_group,
    string $subject,
    string $mensaje
) {
    $users = users_get_info();
    $group_users = groups_get_users($dest_group);

    if (! array_key_exists($usuario_origen, $users)) {
        // Users don't exist in the system.
        return false;
    } else if (empty($group_users)) {
        /*
            There are no users in the group, so it hasn't failed
            although it hasn't done anything.
        */

        return true;
    }

    // Array unique.
    foreach ($group_users as $user) {
        foreach ($user as $key => $us) {
            if ($key == 'id_user') {
                $group_user[$us] = $us;
            }
        }
    }

    foreach ($group_user as $user) {
        $return = messages_create_message(
            $usuario_origen,
            get_user_id($user),
            $subject,
            $mensaje
        );
        if ($return === false) {
            // Error sending message.
            return false;
        }
    }

    return true;
}


/**
 * Deletes a private message
 *
 * @param integer $id_message Message to be deleted.
 *
 * @return boolean true when deleted, false in case of error
 */
function messages_delete_message(int $id_message)
{
    global $config;
    // 'id_usuario_destino' => $config["id_user"],
    $where = ['id_mensaje' => $id_message];
    return (bool) db_process_sql_delete('tmensajes', $where);
}


/**
 * Marks a private message as read/unread
 *
 * @param integer $message_id The message to modify.
 * @param boolean $read       To set unread pass 0, false or empty value.
 *
 * @return boolean true when marked, false in case of error
 */
function messages_process_read(
    int $message_id,
    bool $read=true
) {
    if (empty($read)) {
        $read = 0;
    } else {
        $read = 1;
    }

    return (bool) db_process_sql_update(
        'tmensajes',
        ['estado' => $read],
        ['id_mensaje' => $message_id]
    );
}


/**
 * Gets a private message
 *
 * This function abstracts the database backend so it can simply be
 * replaced with another system
 *
 * @param integer $message_id Message to be retrieved.
 *
 * @return mixed False if it doesn't exist or a filled array otherwise
 */
function messages_get_message(int $message_id)
{
    global $config;

    $sql = sprintf(
        "SELECT id_usuario_origen, id_usuario_destino, subject, mensaje, timestamp
        FROM tmensajes
        WHERE id_usuario_destino='%s' AND id_mensaje=%d",
        $config['id_user'],
        $message_id
    );
    $row = db_get_row_sql($sql);

    if (empty($row)) {
        return false;
    }

    return $row;
}


/**
 * Gets a sent message
 *
 * This function abstracts the database backend so it can simply be
 * replaced with another system
 *
 * @param integer $message_id Message to be retrieved.
 *
 * @return mixed False if it doesn't exist or a filled array otherwise
 */
function messages_get_message_sent(int $message_id)
{
    global $config;

    $sql = sprintf(
        "SELECT id_usuario_origen, id_usuario_destino, subject, mensaje, timestamp
        FROM tmensajes
        WHERE id_usuario_origen='%s' AND id_mensaje=%d",
        $config['id_user'],
        $message_id
    );
    $row = db_get_row_sql($sql);

    if (empty($row)) {
        return false;
    }

    return $row;
}


/**
 * Counts private messages
 *
 * @param string  $user      Target user.
 * @param boolean $incl_read Whether or not to include read messages.
 *
 * @return integer The number of messages this user has
 */
function messages_get_count(
    string $user='',
    bool $incl_read=false
) {
    if (empty($user)) {
        global $config;
        $user = $config['id_user'];
    }

    if (!empty($incl_read)) {
        // Retrieve only unread messages.
        $filter = 'AND nu.uptimestap_read == NULL';
    } else {
        // Do not filter.
        $filter = '';
    }

    $sql = sprintf(
        "SELECT count(*) FROM tmensajes tm 
        left join tnotification_user nu
            ON tm.id_mensaje=nu.id_mensaje 
        left join tnotification_group ng
            ON tm.id_mensaje=ng.id_mensaje 
        left join tusuario_perfil up
            ON tm.id_mensaje=ng.id_mensaje
            AND ng.id_group=up.id_grupo
        WHERE (nu.id_user='%s' OR ng.id_group=0 OR up.id_grupo=ng.id_group)
            %s",
        $config['id_user'],
        $filter
    );

    return (int) db_get_sql($sql);
}


/**
 * Counts messages sent.
 *
 * @param string $user Target user.
 *
 * @return integer The number of messages this user has sent
 */
function messages_get_count_sent(string $user='')
{
    if (empty($user)) {
        global $config;
        $user = $config['id_user'];
    }

    $sql = sprintf(
        "SELECT COUNT(*)
        FROM tmensajes WHERE id_usuario_origen='%s'",
        $user
    );

    return (int) db_get_sql($sql);
}


/**
 * Get message overview in array
 *
 * @param string $order     How to order them valid:
 *                          (status (default), subject, timestamp, sender).
 * @param string $order_dir Direction of order
 *                          (ASC = Ascending, DESC = Descending).
 *
 * @return integer The number of messages this user has
 */
function messages_get_overview(
    string $order='status',
    string $order_dir='ASC'
) {
    global $config;

    switch ($order) {
        case 'timestamp':{
        }
        case 'sender':{
        }
        case 'subject':{
        }
        break;

        case 'status':
        default:
            $order = 'estado, timestamp';
        break;
    }

    if ($order_dir != 'ASC') {
        $order .= ' DESC';
    }

    $sql = sprintf(
        "SELECT * FROM tmensajes tm 
        left join tnotification_user nu
            ON tm.id_mensaje=nu.id_mensaje 
        left join tnotification_group ng
            ON tm.id_mensaje=ng.id_mensaje 
        left join tusuario_perfil up
            ON tm.id_mensaje=ng.id_mensaje
            AND ng.id_group=up.id_grupo
        WHERE (nu.id_user='%s' OR ng.id_group=0 OR up.id_grupo=ng.id_group)
        ORDER BY %s",
        $config['id_user'],
        $order
    );

    $result = [];
    $return = db_get_all_rows_sql($sql);

    if ($return === false) {
        return $result;
    }

    foreach ($return as $message) {
        $id_message = $message['id_mensaje'];
        $result[$id_message]['sender'] = $message['id_usuario_origen'];
        $result[$id_message]['subject'] = $message['subject'];
        $result[$id_message]['timestamp'] = $message['timestamp'];
        $result[$id_message]['status'] = $message['estado'];
    }

    return $result;
}


/**
 * Get sent message overview in array
 *
 * @param string $order     How to order them valid:
 *                          (status (default), subject, timestamp, sender).
 * @param string $order_dir Direction of order
 *                          (ASC = Ascending, DESC = Descending).
 *
 * @return integer The number of messages this user has
 */
function messages_get_overview_sent(
    string $order='timestamp',
    string $order_dir='ASC'
) {
    global $config;

    switch ($order) {
        case 'timestamp':{
        }
        case 'sender':{
        }
        case 'subject':{
        }
        break;

        case 'status':
        default:
            $order = 'estado, timestamp';
        break;
    }

    if ($order_dir != 'ASC') {
        $order .= ' DESC';
    }

    $result = [];
    $return = db_get_all_rows_field_filter(
        'tmensajes',
        'id_usuario_origen',
        $config['id_user'],
        $order
    );

    if ($return === false) {
        return $result;
    }

    foreach ($return as $message) {
        $id_message = $message['id_mensaje'];
        $result[$id_message]['dest'] = $message['id_usuario_destino'];
        $result[$id_message]['subject'] = $message['subject'];
        $result[$id_message]['timestamp'] = $message['timestamp'];
        $result[$id_message]['status'] = $message['estado'];
    }

    return $result;
}
