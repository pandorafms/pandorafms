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
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
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
 * Set targets for given messaje
 *
 * @param integer $message_id Message id.
 * @param array   $users      An array with all target users.
 * @param array   $groups     An array with all target groups.
 *
 * @return boolean Task status.
 */
function message_set_targets(
    int $message_id,
    array $users=null,
    array $groups=null
) {
    if (empty($message_id)) {
        return false;
    }

    if (is_array($users)) {
        $values = [];
        foreach ($users as $user) {
            if (empty($user)) {
                continue;
            }

            $values['id_mensaje'] = $message_id;
            $values['id_user'] = $user;
        }

        if (!empty($values)) {
            $ret = db_process_sql_insert('tnotification_user', $values);
            if ($ret === false) {
                return false;
            }
        }
    }

    if (is_array($groups)) {
        $values = [];
        foreach ($groups as $group) {
            if ($group != 0 && empty($group)) {
                continue;
            }

            $values['id_mensaje'] = $message_id;
            $values['id_group'] = $group;
        }

        if (!empty($values)) {
            $ret = db_process_sql_insert('tnotification_group', $values);
            if ($ret === false) {
                return false;
            }
        }
    }

    return true;
}


/**
 * Creates a private message to be forwarded to other people
 *
 * @param string $usuario_origen The sender of the message.
 * @param array  $target_users   The receiver of the message.
 * @param array  $target_groups  Target groups to be delivered.
 * @param string $subject        Subject of the message (much like E-Mail).
 * @param string $mensaje        The actual message. This message will be
 *                               cleaned by io_safe_input (html is allowed but
 *                               loose html chars will be translated).
 *
 * @return boolean true when delivered, false in case of error
 */
function messages_create_message(
    string $usuario_origen,
    array $target_users,
    array $target_groups,
    string $subject,
    string $mensaje
) {
    $users = users_get_info();

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
        $ret = message_set_targets(
            $message_id,
            $target_users,
            $target_groups
        );
        if ($ret === false) {
            // Failed to deliver messages. Erase message and show error.
            db_process_sql_delete(
                'tmensajes',
                ['id_mensaje' => $message_id]
            );
            return false;
        }
    }

    if ($return === false) {
        return false;
    } else {
        return true;
    }
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

    // Check if user has grants to access the message.
    if (check_notification_readable($id_message) === false) {
        return false;
    }

    $utimestamp = time();

    $ret = db_process_sql_update(
        'tnotification_user',
        ['utimestamp_erased' => $utimestamp],
        [
            'id_mensaje' => $id_message,
            'id_user'    => $config['id_user'],
        ]
    );

    if ($ret === 0) {
        // No previous updates.
        // Message available to user due group assignment.
        $ret = db_process_sql_insert(
            'tnotification_user',
            [
                'id_mensaje'        => $id_message,
                'id_user'           => $config['id_user'],
                'utimestamp_erased' => $utimestamp,
            ]
        );

        // Quick fix. Insertions returns 0.
        if ($ret !== false) {
            $ret = 1;
        }
    }

    return (bool) $ret;
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
    global $config;
    // Check if user has grants to read the message.
    if (check_notification_readable($message_id) === false) {
        return false;
    }

    if (empty($read)) {
        // Mark as unread.
        $utimestamp = null;
    } else {
        // Mark as read.
        $utimestamp = time();
    }

    $already_read = db_get_value_filter(
        'utimestamp_read',
        'tnotification_user',
        [
            'id_mensaje' => $message_id,
            'id_user'    => $config['id_user'],
        ]
    );

    if (empty($already_read) === false) {
        // Already read.
        return true;
    }

    $ret = db_process_sql_update(
        'tnotification_user',
        ['utimestamp_read' => $utimestamp],
        [
            'id_mensaje' => $message_id,
            'id_user'    => $config['id_user'],
        ]
    );

    if ($ret === 0) {
        // No previous updates.
        // Message available to user due group assignment.
        $ret = db_process_sql_insert(
            'tnotification_user',
            [
                'id_mensaje'      => $message_id,
                'id_user'         => $config['id_user'],
                'utimestamp_read' => $utimestamp,
            ]
        );
    }

    return (bool) $ret;
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

    // Check if user has grants to read the message.
    if (check_notification_readable($message_id) === false) {
        return false;
    }

    $sql = sprintf(
        'SELECT *, nu.utimestamp_read > 0 as "read"
        FROM tmensajes tm
        LEFT JOIN tnotification_user nu
            ON nu.id_mensaje = tm.id_mensaje
        WHERE tm.id_mensaje=%d',
        $message_id
    );
    $row = db_get_row_sql($sql);

    if (empty($row)) {
        return false;
    }

    $row['id_usuario_destino'] = $config['id_user'];

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
        "SELECT id_usuario_origen, subject, mensaje, timestamp
        FROM tmensajes
        WHERE id_usuario_origen='%s' AND id_mensaje=%d AND hidden_sent = 0",
        $config['id_user'],
        $message_id
    );
    $row = db_get_row_sql($sql);

    if (empty($row)) {
        return false;
    }

    $targets = get_notification_targets($message_id);

    $row['id_usuario_destino'] = implode(
        ',',
        $targets['users']
    ).','.implode(
        ',',
        $targets['groups']
    );

    return $row;
}


/**
 * Counts private messages
 *
 * @param string  $user          Target user.
 * @param boolean $incl_read     Whether or not to include read messages.
 * @param boolean $ignore_source Ignore source.
 *
 * @return integer The number of messages this user has
 */
function messages_get_count(
    string $user='',
    bool $incl_read=false,
    bool $ignore_source=false
) {
    if (empty($user)) {
        global $config;
        $user = $config['id_user'];
    }

    if (!empty($incl_read)) {
        // Do not filter.
        $read = ' 1=1 ';
    } else {
        // Retrieve only unread messages.
        $read = ' t.read is null';
    }

    if ($ignore_source === true) {
        $source_select = '';
        $source_sql = '';
        $source_extra = '';
    } else {
        $source_select = ',IF(ns.user_editable,nsu.enabled,ns.enabled) as enabled';

        // Row in tnotification_source_user could exist or not.
        $source_sql = sprintf(
            'INNER JOIN (
                tnotification_source ns
                LEFT JOIN tnotification_source_user nsu
                    ON ns.id=nsu.id_source
                    AND nsu.id_user="%s")
                ON tm.id_source=ns.id',
            $user
        );
        $source_extra = 'AND (t.enabled=1 OR t.enabled is null)';
    }

    $sql = sprintf(
        'SELECT count(distinct id_mensaje) as "n" FROM (
            SELECT
                tm.*,
                utimestamp_read > 0 as "read"
                %s
            FROM tmensajes tm
                %s
                LEFT JOIN tnotification_user nu
                    ON tm.id_mensaje=nu.id_mensaje
                    AND nu.id_user="%s"
                LEFT JOIN (tnotification_group ng
                    INNER JOIN tusuario_perfil up
                        ON ng.id_group=up.id_grupo
                        AND up.id_grupo=ng.id_group)
                    ON tm.id_mensaje=ng.id_mensaje
            WHERE utimestamp_erased is null
                AND (nu.id_user="%s" OR up.id_usuario="%s" OR ng.id_group=0)
        ) t
        WHERE %s %s',
        $source_select,
        $source_sql,
        $user,
        $user,
        $user,
        $read,
        $source_extra
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
        FROM tmensajes WHERE id_usuario_origen='%s' AND hidden_sent = 0",
        $user
    );

    return (int) db_get_sql($sql);
}


/**
 * Get message overview in array
 *
 * @param string  $order             How to order them valid:
 *                                   (status (default), subject, timestamp, sender).
 * @param string  $order_dir         Direction of order
 *                                   (ASC = Ascending, DESC = Descending).
 * @param boolean $incl_read         Include read messages in return.
 * @param boolean $incl_source_info  Include source info.
 * @param integer $limit             Maximum number of result in the query.
 * @param array   $other_filter      Add a filter on main query.
 * @param string  $join_other_filter How to join filter on main query.
 *
 * @return integer The number of messages this user has
 */
function messages_get_overview(
    string $order='status',
    string $order_dir='ASC',
    bool $incl_read=true,
    bool $incl_source_info=false,
    int $limit=0,
    array $other_filter=[],
    string $join_other_filter='AND'
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

    if (!empty($incl_read)) {
        // Do not filter.
        $read = '';
    } else {
        // Retrieve only unread messages.
        $read = 'where t.read is null';
    }

    $source_fields = '';
    $source_join = '';
    if ($incl_source_info) {
        $source_fields = ', tns.*';
        $source_join = 'INNER JOIN tnotification_source tns
            ON tns.id=tm.id_source';
    }

    // Using distinct because could be double assignment due group/user.
    $sql = sprintf(
        'SELECT * FROM (
            SELECT DISTINCT tm.*, utimestamp_read > 0 as "read" %s
            FROM tmensajes tm 
            LEFT JOIN tnotification_user nu
                ON tm.id_mensaje=nu.id_mensaje 
                AND nu.id_user="%s" 
            LEFT JOIN (tnotification_group ng
                INNER JOIN tusuario_perfil up
                    ON ng.id_group=up.id_grupo
                    AND up.id_grupo=ng.id_group
            ) ON tm.id_mensaje=ng.id_mensaje
            %s
            WHERE utimestamp_erased is null
                AND (nu.id_user="%s" OR up.id_usuario="%s" OR ng.id_group=0)
        ) t 
        %s
        %s
        ORDER BY %s
        %s',
        $source_fields,
        $config['id_user'],
        $source_join,
        $config['id_user'],
        $config['id_user'],
        $read,
        db_format_array_where_clause_sql(
            $other_filter,
            $join_other_filter,
            ' AND '
        ),
        $order,
        ($limit !== 0) ? ' LIMIT '.$limit : ''
    );

    return db_get_all_rows_sql($sql);
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

    $filter = [
        'id_usuario_origen' => $config['id_user'],
        'hidden_sent'       => 0,
        'order'             => $order,
    ];

    return db_get_all_rows_filter(
        'tmensajes',
        $filter
    );
}


/**
 * Get a message interpreted as a conversation.
 *
 * @param mixed $data Complete message or message id.
 *
 * @return mixed False if fails. A string array with the conversation.
 */
function messages_get_conversation($data)
{
    if (is_array($data)) {
        $message = $data;
    } else {
        $message = messages_get_message($data);
    }

    if (!isset($message) || !is_array($message)) {
        return [];
    }

    $conversation = [];
    $target_str = $message['mensaje'];

    while (preg_match_all(
        '/(.*)On(.*)wrote:(.*)/',
        $target_str,
        $decoded,
        PREG_PATTERN_ORDER
    ) !== false && empty($target_str) !== true) {
        if (empty($decoded[2]) !== true) {
            array_push(
                $conversation,
                [
                    'message' => array_pop($decoded)[0],
                    'date'    => array_pop($decoded)[0],
                ]
            );
        } else {
            array_push(
                $conversation,
                ['message' => $target_str]
            );
        }

        $target_str = $decoded[1][0];
    }

    return $conversation;
}


/**
 * Get the URL of a message. If field in db is null, it returs a link to
 *      messages view.
 *
 * @param integer $message_id Message id to get URL.
 *
 * @return mixed False if fails. A string with URL otherwise.
 */
function messages_get_url($message_id)
{
    $messages = messages_get_message($message_id);
    if ($messages === false) {
        return false;
    }

    // Return URL stored if is set in database.
    if (isset($messages['url'])) {
        return str_replace('__url__', ui_get_full_url('/'), $messages['url']);
    }

    // Return the message direction.
    return ui_get_full_url('index.php?sec=message_list&sec2=operation/messages/message_edit&read_message=1&id_message='.$message_id);
}


/**
 * Deletes sent message
 *
 * @param integer $message_id Message id to get URL.
 *
 * @return boolean true when deleted, false in case of error
 */
function messages_delete_message_sent($id_message)
{
    global $config;

    $ret = db_process_sql_update(
        'tmensajes',
        ['hidden_sent' => 1],
        [
            'id_mensaje'        => $id_message,
            'id_usuario_origen' => $config['id_user'],
        ]
    );

    return $ret;
}
