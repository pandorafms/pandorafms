<?php

/**
 * Library. Notification system auxiliary functions.
 *
 * @category   Library
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


/**
 * Retrieves source ID for given source.
 *
 * @param string $source Source.
 *
 * @return integer source's id.
 */
function get_notification_source_id(string $source)
{
    if (empty($source) === true) {
        return false;
    }

    return db_get_value_sql(
        sprintf(
            'SELECT id
                FROM `tnotification_source`
                WHERE lower(`description`) = lower("%s")',
            $source
        )
    );
}


/**
 * Retrieve all targets for given message.
 *
 * @param integer $id_message Message id.
 *
 * @return array of users and groups target of this message.
 */
function get_notification_targets(int $id_message)
{
    $targets = [
        'users'  => [],
        'groups' => [],
    ];

    if (empty($id_message)) {
        return $targets;
    }

    $ret = db_get_all_rows_sql(
        sprintf(
            'SELECT id_user
                FROM tnotification_user nu
                WHERE nu.id_mensaje = %d',
            $id_message
        )
    );

    if (is_array($ret)) {
        foreach ($ret as $row) {
            array_push(
                $targets['users'],
                get_user_fullname($row['id_user'])
            );
        }
    }

    $ret = db_get_all_rows_sql(
        sprintf(
            'SELECT COALESCE(tg.nombre,ng.id_group) as "id_group"
                FROM tnotification_group ng
                LEFT JOIN tgrupo tg
                    ON tg.id_grupo=ng.id_group
                WHERE ng.id_mensaje = %d',
            $id_message
        )
    );

    if (is_array($ret)) {
        foreach ($ret as $row) {
            if ($row['id_group'] == '0') {
                $row['id_group'] = '<b>'.__('All').'</b>';
            }

            array_push($targets['groups'], $row['id_group']);
        }
    }

    return $targets;
}


/**
 * Check if current user has grants to read this notification
 *
 * @param integer $id_message Target message.
 *
 * @return boolean true, read available. False if not.
 */
function check_notification_readable(int $id_message)
{
    global $config;

    if (empty($id_message)) {
        return false;
    }

    $sql = sprintf(
        'SELECT tm.*, utimestamp_read > 0 as "read" FROM tmensajes tm 
            LEFT JOIN tnotification_user nu
                ON tm.id_mensaje=nu.id_mensaje 
                AND tm.id_mensaje=%d
            LEFT JOIN (tnotification_group ng
                INNER JOIN tusuario_perfil up
                    ON ng.id_group=up.id_grupo
                    AND up.id_grupo=ng.id_group
            ) ON tm.id_mensaje=ng.id_mensaje 
            WHERE utimestamp_erased is null
                AND (up.id_usuario="%s" OR nu.id_user="%s" OR ng.id_group=0)',
        $id_message,
        $config['id_user'],
        $config['id_user']
    );

    return (bool) db_get_value_sql($sql);
}

/**
 * Print the notification ball to see unread messages
 *
 * @return string with HTML code of notification ball
 */
function notifications_print_ball() {
    $num_notifications = messages_get_count();
    $class_status = $num_notifications == 0
        ? 'notification-ball-no-messages'
        : 'notification-ball-new-messages';
    return
        "<div class='notification-ball $class_status' id='notification-ball-header'>
            $num_notifications
        </div>";
}
