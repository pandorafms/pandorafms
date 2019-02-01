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

define('NOTIFICATIONS_POSTPONE_FOREVER', -1);

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
        "SELECT id
            FROM `tnotification_source`
            WHERE `description` LIKE '{$source}%'"
    );
}

/**
 * Converts description into a handable identifier
 *
 * @param string $desc Full description
 *
 * @return string First word in lowercase. Empty string if no word detected.
 */
function notifications_desc_to_id(string $desc) {
    preg_match('/^[a-zA-Z]*/', $desc, $matches);
    $match = $matches[0];
    return isset($match) ? $match : '';
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
 * Return all info from tnotification_source
 *
 * @return array with sources info
 */
function notifications_get_all_sources() {
    return mysql_db_get_all_rows_in_table('tnotification_source');
}

/**
 * Return the user sources to be inserted into a select
 *
 * @param int $source_id Source database identificator
 *
 * @return array with the user id in keys and user id in value too
 */
function notifications_get_user_sources_for_select($source_id) {
    $users = db_get_all_rows_filter(
        'tnotification_source_user',
        array('id_source' => $source_id),
        'id_user'
    );
    // If fails or no one is selected, return empty array
    if ($users === false) return array();

    return index_array($users, 'id_user', 'id_user');
}


/**
 * Return the groups sources to be inserted into a select
 *
 * @param int $source_id Source database identificator
 *
 * @return array with the group id in keys and group name in value
 */
function notifications_get_group_sources_for_select($source_id) {
    $users = db_get_all_rows_filter(
        'tnotification_source_group tnsg
            INNER JOIN tgrupo tg ON tnsg.id_group = tg.id_grupo',
        array('id_source' => $source_id),
        array ('tnsg.id_group', 'tg.nombre')
    );
    // If fails or no one is selected, return empty array
    if ($users === false) return array();

    return index_array($users, 'id_group', 'nombre');
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

/**
 * Print notification configuration global
 *
 * @param array notification source data
 *
 * @return string with HTML of source configuration
 */
function notifications_print_global_source_configuration($source) {

    // Get some values to generate the title
    $id = notifications_desc_to_id($source['description']);
    $switch_values = array (
        'name' => "enable-" . $id,
        'value' => $source['enabled']
    );
    // Generate the title
    $html_title = "<div class='global-config-notification-title'>";
    $html_title .=     html_print_switch($switch_values);
    $html_title .=    "<h2>{$source['description']}</h2>";
    $html_title .= "</div>";

    // Generate the html for title
    $html_selectors = "<div class='global-config-notification-selectors'>";
    $html_selectors .=       notifications_print_source_select_box(notifications_get_user_sources_for_select($source['id']), 'users');
    $html_selectors .=       notifications_print_source_select_box(notifications_get_group_sources_for_select($source['id']), 'groups');
    $html_selectors .= "</div>";

    // Generate the checkboxes and time select
    $html_checkboxes = "<div class='global-config-notification-checkboxes'>";
    $html_checkboxes .= "   <span>";
    $html_checkboxes .=         html_print_checkbox("mail-$id", 1, $source['also_mail'], true);
    $html_checkboxes .=         __('Also email users with notification content');
    $html_checkboxes .= "   </span><br><span>";
    $html_checkboxes .=         html_print_checkbox("user-$id", 1, $source['user_editable'], true);
    $html_checkboxes .=         __('Users cannot modify notification preferences');
    $html_checkboxes .= "   </span>";
    $html_checkboxes .= "</div>";

    // Generate the select with the time
    $html_select_pospone = __('Users can postpone notifications up to');
    $html_select_pospone .= html_print_select (
		array(
            SECONDS_5MINUTES => __('5 minutes'),
            SECONDS_15MINUTES => __('15 minutes'),
            SECONDS_12HOURS => __('12 hours'),
            SECONDS_1DAY => __('1 day'),
            SECONDS_1WEEK => __('1 week'),
            SECONDS_15DAYS => __('15 days'),
            SECONDS_1MONTH => __('1 month'),
            NOTIFICATIONS_POSTPONE_FOREVER => __('forever')),
        "postpone-{$id}",
        $source['max_postpone_time'],
        '',
        '',
        0,
        true
    );

    // Return all html
    return $html_title . $html_selectors . $html_checkboxes . $html_select_pospone;
}

/**
 * Print select boxes of notified users or groups
 *
 * @param array $info_selec All info required for build the selector
 * @param string $id users|groups
 *
 * @return string HTML with the generated selector
 */
function notifications_print_source_select_box($info_selec, $id) {

    $title = $id == "users" ? __('Notified users') : __('Notified groups');
    $add_title = $id == "users" ? __('Add users') : __('Add groups');
    $delete_title = $id == "users" ? __('Delete users') : __('Delete groups');

    // Generate the HTML
    $html_select = "<div class='global-config-notification-single-selector'>";
    $html_select .= "   <div>";
    $html_select .= "       <h4>$title</h4>";
    // Put a true if empty sources to avoid to sow the 'None' value
    $html_select .=         html_print_select(empty($info_selec) ? true : $info_selec, "multi-{$id}[]", 0, false, '', '', true, true);
    $html_select .= "   </div>";
    $html_select .= "   <div class='global-notifications-icons'>";
    $html_select .=         html_print_image('images/input_add.png', true, array('title' => $add_title));
    $html_select .=         html_print_image('images/input_delete.png', true, array('title' => $delete_title));
    $html_select .= "   </div>";
    $html_select .= "</div>";
    return $html_select;
}
