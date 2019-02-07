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
function notifications_desc_to_id(string $desc)
{
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
 * @param array $filter Filter to table tnotification_source.
 *
 * @return array with sources info
 */
function notifications_get_all_sources($filter=[])
{
    return db_get_all_rows_filter('tnotification_source', $filter);
}


/**
 * Return the user sources to be inserted into a select
 *
 * @param integer $source_id Source database identificator.
 *
 * @return array with the user id in keys and user id in value too
 */
function notifications_get_user_sources_for_select($source_id)
{
    $users = notifications_get_user_sources(
        ['id_source' => $source_id],
        ['id_user']
    );

    return index_array($users, 'id_user', 'id_user');
}


/**
 * Get the user sources
 *
 * @param array $filter Filter of sql query.
 * @param array $fields Fields to get of query.
 *
 * @return array Array with user sources data.
 */
function notifications_get_user_sources($filter=[], $fields=[])
{
    $users = db_get_all_rows_filter(
        'tnotification_source_user',
        $filter,
        $fields
    );
    // If fails or no one is selected, return empty array.
    if ($users === false) {
        return [];
    }

    return $users;
}


/**
 * Return the groups sources to be inserted into a select
 *
 * @param integer $source_id Source database identificator.
 *
 * @return array with the group id in keys and group name in value
 */
function notifications_get_group_sources_for_select($source_id)
{
    $groups = notifications_get_group_sources(
        ['id_source' => $source_id],
        ['id_group']
    );
    return index_array($groups, 'id_group', 'name');
}


/**
 * Get the group sources
 *
 * @param array $filter Filter of sql query.
 * @param array $fields Fields retrieved.
 *
 * @return array With the group info
 */
function notifications_get_group_sources($filter=[], $fields=[])
{
    // Get only the tnotifications_source_group fields in addition to group name.
    if (empty($fields)) {
        $fields[] = 'tnsg.*';
    }

    $fields = array_map(
        function ($field) {
            if (!preg_match('/^tnsg./', $field)) {
                $field = "tnsg.{$field}";
            }

            return $field;
        },
        $fields
    );

    // Get groups.
    $groups = db_get_all_rows_filter(
        'tnotification_source_group tnsg
            LEFT JOIN tgrupo tg ON tnsg.id_group = tg.id_grupo',
        $filter,
        array_merge($fields, ['IFNULL(tg.nombre, "All") AS name'])
    );

    // If fails or no one is selected, return empty array
    if ($groups === false) {
        return [];
    }

    return $groups;
}


/**
 * Delete a set of groups from notification source
 *
 * @param integer $source_id Source id.
 * @param array   $groups    Id of groups to be deleted.
 *
 * @return boolean True if success. False otherwise.
 */
function notifications_remove_group_from_source($source_id, $groups)
{
    // Source id is mandatory
    if (!isset($source_id)) {
        return false;
    }

    // Delete from database
    return db_process_sql_delete(
        'tnotification_source_group',
        [
            'id_group'  => $groups,
            'id_source' => $source_id,
        ]
    ) !== false;
}


/**
 * Delete a set of users from notification source
 *
 * @param integer $source_id Source id.
 * @param array   $users     Id of users to be deleted.
 *
 * @return boolean True if success. False otherwise.
 */
function notifications_remove_users_from_source($source_id, $users)
{
    // Source id is mandatory
    if (!isset($source_id)) {
        return false;
    }

    // Delete from database
    return db_process_sql_delete(
        'tnotification_source_user',
        [
            'id_user'   => $users,
            'id_source' => $source_id,
        ]
    ) !== false;
}


/**
 * Insert a set of groups to notification source
 *
 * @param integer $source_id Source id.
 * @param array   $groups    Id of groups to be deleted.
 *
 * @return boolean True if success. False otherwise.
 */
function notifications_add_group_to_source($source_id, $groups)
{
    // Source id is mandatory
    if (!isset($source_id)) {
        return false;
    }

    // Insert into database all groups passed
    $res = true;
    foreach ($groups as $group) {
        if (empty($group)) {
            continue;
        }

        $res = $res && db_process_sql_insert(
            'tnotification_source_group',
            [
                'id_group'  => $group,
                'id_source' => $source_id,
            ]
        ) !== false;
    }

    return $res;
}


/**
 * Insert a set of users to notification source
 *
 * @param integer $source_id Source id.
 * @param array   $users     Id of users to be deleted.
 *
 * @return boolean True if success. False otherwise.
 */
function notifications_add_users_to_source($source_id, $users)
{
    // Source id is mandatory
    if (!isset($source_id)) {
        return false;
    }

    // Insert into database all groups passed
    $res = true;
    $also_mail = db_get_value(
        'also_mail',
        'tnotification_source',
        'id',
        $source_id
    );
    foreach ($users as $user) {
        if (empty($user)) {
            continue;
        }

        $res = $res && db_process_sql_insert(
            'tnotification_source_user',
            [
                'id_user'   => $user,
                'id_source' => $source_id,
                'enabled'   => 1,
                'also_mail' => (int) $also_mail,
            ]
        ) !== false;
    }

    return $res;
}


/**
 * Get the groups that not own to a source and, for that reason, they can be
 * added to the source.
 *
 * @param integer $source_id Source id.
 *
 * @return array Indexed by id group all selectable groups.
 */
function notifications_get_group_source_not_configured($source_id)
{
    $groups_selected = notifications_get_group_sources_for_select($source_id);
    $all_groups = users_get_groups_for_select(false, 'AR', false, true, $groups_selected);
    return array_diff($all_groups, $groups_selected);
}


/**
 * Get the users that not own to a source and, for that reason, they can be
 * added to the source.
 *
 * @param integer $source_id Source id.
 *
 * @return array Indexed by id user, all selectable users.
 */
function notifications_get_user_source_not_configured($source_id)
{
    $users_selected = array_keys(notifications_get_user_sources_for_select($source_id));
    $users = get_users(
        'id_user',
        ['!id_user' => $users_selected],
        ['id_user']
    );
    return index_array($users, 'id_user', 'id_user');
}


/**
 * Build a data struct to handle the value of a label
 *
 * @param mixed $status  Status value.
 * @param mixed $enabled Enabled value.
 *
 * @return array with status (1|0) and enabled (1|0)
 */
function notifications_build_user_enable_return($status, $enabled)
{
    return [
        'status'  => ((bool) $status === true) ? 1 : 0,
        'enabled' => ((bool) $enabled === true) ? 1 : 0,
    ];
}


/**
 * Get user label (enabled, also_mail...) status.
 *
 * @param integer $source Id of notification source.
 * @param string  $user   User id.
 * @param string  $label  Label id (enabled, also_email...).
 *
 * @return array Return of notifications_build_user_enable_return.
 */
function notifications_get_user_label_status($source, $user, $label)
{
    // If not enabled, it cannot be modificable.
    if (!$source['enabled'] || !$source[$label]) {
        return notifications_build_user_enable_return(false, false);
    }

    // See at first for direct reference.
    $user_source = notifications_get_user_sources(
        [
            'id_source' => $source['id'],
            'id_user'   => $user,
        ]
    );
    if (!empty($user_source)) {
        return notifications_build_user_enable_return(
            isset($user_source[0][$label]) ? $user_source[0][$label] : false,
            $source['user_editable']
        );
    }

    $common_groups = array_intersect(
        array_keys(users_get_groups($user)),
        array_keys(
            notifications_get_group_sources_for_select($source['id'])
        )
    );
    // No group found, return no permissions.
    $value = empty($common_groups) ? false : $source[$label];
    return notifications_build_user_enable_return($value, false);
}


/**
 * Set the status to a single label on config of users notifications.
 *
 * @param integer $source Id of notification source.
 * @param string  $user   User id.
 * @param string  $label  Label id (enabled, also_email...).
 * @param mixed   $value  Numeric value: 1 or 0.
 *
 * @return boolean True if success.
 */
function notifications_set_user_label_status($source, $user, $label, $value)
{
    $source_info = notifications_get_all_sources(['id' => $source]);
    if (!isset($source_info[0])
        || !$source_info[0]['enabled']
        || !$source_info[0][$label]
        || !$source_info[0]['user_editable']
    ) {
        return false;
    }

    return (bool) db_process_sql_update(
        'tnotification_source_user',
        [$label => $value],
        [
            'id_user'   => $user,
            'id_source' => $source,
        ]
    );

}


/**
 * Print the notification ball to see unread messages.
 *
 * @return string with HTML code of notification ball.
 */
function notifications_print_ball()
{
    $num_notifications = messages_get_count();
    $class_status = $num_notifications == 0 ? 'notification-ball-no-messages' : 'notification-ball-new-messages';
    return "<div class='notification-ball $class_status' id='notification-ball-header'>
            $num_notifications
        </div>";
}


/**
 * Print notification configuration global
 *
 * @param array $source Notification source data.
 *
 * @return string with HTML of source configuration
 */
function notifications_print_global_source_configuration($source)
{
    // Get some values to generate the title
    $id = notifications_desc_to_id($source['description']);
    $switch_values = [
        'name'  => 'enable-'.$id,
        'value' => $source['enabled'],
    ];

    // Search if group all is set and handle that situation
    $source_groups = notifications_get_group_sources_for_select($source['id']);
    $is_group_all = isset($source_groups['0']);
    if ($is_group_all) {
        unset($source_groups['0']);
    }

    // Generate the title
    $html_title = "<div class='global-config-notification-title'>";
    $html_title .= html_print_switch($switch_values);
    $html_title .= "<h2>{$source['description']}</h2>";
    $html_title .= '</div>';

    // Generate the html for title
    $html_selectors = "<div class='global-config-notification-selectors'>";
    $html_selectors .= notifications_print_source_select_box(notifications_get_user_sources_for_select($source['id']), 'users', $id, $is_group_all);
    $html_selectors .= notifications_print_source_select_box($source_groups, 'groups', $id, $is_group_all);
    $html_selectors .= '</div>';

    // Generate the checkboxes and time select
    $html_checkboxes = "<div class='global-config-notification-checkboxes'>";
    $html_checkboxes .= '   <span>';
    $html_checkboxes .= html_print_checkbox("all-$id", 1, $is_group_all, true, false, 'notifications_disable_source(event)');
    $html_checkboxes .= __('Notify all users');
    $html_checkboxes .= '   </span><br><span>';
    $html_checkboxes .= html_print_checkbox("mail-$id", 1, $source['also_mail'], true);
    $html_checkboxes .= __('Also email users with notification content');
    $html_checkboxes .= '   </span><br><span>';
    $html_checkboxes .= html_print_checkbox("user-$id", 1, $source['user_editable'], true);
    $html_checkboxes .= __('Users can modify notification preferences');
    $html_checkboxes .= '   </span>';
    $html_checkboxes .= '</div>';

    // Generate the select with the time
    $html_select_pospone = __('Users can postpone notifications up to');
    $html_select_pospone .= html_print_select(
        [
            SECONDS_5MINUTES               => __('5 minutes'),
            SECONDS_15MINUTES              => __('15 minutes'),
            SECONDS_12HOURS                => __('12 hours'),
            SECONDS_1DAY                   => __('1 day'),
            SECONDS_1WEEK                  => __('1 week'),
            SECONDS_15DAYS                 => __('15 days'),
            SECONDS_1MONTH                 => __('1 month'),
            NOTIFICATIONS_POSTPONE_FOREVER => __('forever'),
        ],
        "postpone-{$id}",
        $source['max_postpone_time'],
        '',
        '',
        0,
        true
    );

    // Return all html
    return $html_title.$html_selectors.$html_checkboxes.$html_select_pospone;
}


/**
 * Print select boxes of notified users or groups
 *
 * @param array   $info_selec All info required for build the selector.
 * @param string  $id         One of users|groups.
 * @param string  $source_id  Id of source.
 * @param boolean $disabled   Disable the selectors.
 *
 * @return string HTML with the generated selector
 */
function notifications_print_source_select_box(
    $info_selec,
    $id,
    $source_id,
    $disabled
) {
    $title = $id == 'users' ? __('Notified users') : __('Notified groups');
    $add_title = $id == 'users' ? __('Add users') : __('Add groups');
    $delete_title = $id == 'users' ? __('Delete users') : __('Delete groups');

    // Generate the HTML
    $html_select = "<div class='global-config-notification-single-selector'>";
    $html_select .= '   <div>';
    $html_select .= "       <h4>$title</h4>";
    // Put a true if empty sources to avoid to sow the 'None' value
    $html_select .= html_print_select(empty($info_selec) ? true : $info_selec, "multi-{$id}-{$source_id}[]", 0, false, '', '', true, true, true, '', $disabled);
    $html_select .= '   </div>';
    $html_select .= "   <div class='global-notifications-icons'>";
    $html_select .= html_print_image('images/input_add.png', true, ['title' => $add_title, 'onclick' => "add_source_dialog('$id', '$source_id')"]);
    $html_select .= html_print_image('images/input_delete.png', true, ['title' => $delete_title, 'onclick' => "remove_source_elements('$id', '$source_id')"]);
    $html_select .= '   </div>';
    $html_select .= '</div>';
    return $html_select;
}


/**
 * Print the select with right and left arrows to select new sources
 * (groups or users).
 *
 * @param array  $info_selec Array with source info.
 * @param string $users      One of users|groups.
 * @param source $source_id  Source id.
 *
 * @return string HTML with the select code.
 */
function notifications_print_two_ways_select($info_selec, $users, $source_id)
{
    $html_select = "<div class='global_config_notifications_dialog_add'>";
    $html_select .= html_print_select(empty($info_selec) ? true : $info_selec, "all-multi-{$users}-{$source_id}[]", 0, false, '', '', true, true, true, '');
    $html_select .= "<div class='global_config_notifications_two_ways_form_arrows'>";
    $html_select .= html_print_image('images/darrowright.png', true, ['title' => $add_title, 'onclick' => "notifications_modify_two_ways_element('$users', '$source_id', 'add')"]);
    $html_select .= html_print_image('images/darrowleft.png', true, ['title' => $add_title, 'onclick' => "notifications_modify_two_ways_element('$users', '$source_id', 'remove')"]);
    $html_select .= '</div>';
    $html_select .= html_print_select(true, "selected-multi-{$users}-{$source_id}[]", 0, false, '', '', true, true, true, '');
    $html_select .= '</div>';
    $html_select .= html_print_button(__('Add'), 'Add', false, "notifications_add_source_element_to_database('$users', '$source_id')", "class='sub add'", true);

    return $html_select;
}


/**
 * Print a label status represented by a switch
 *
 * @param integer $source Source id.
 * @param string  $user   User id.
 * @param string  $label  Label (enabled, also_mail...).
 *
 * @return string With HTML code
 */
function notifications_print_user_switch($source, $user, $label)
{
    $status = notifications_get_user_label_status($source, $user, $label);
    return html_print_switch(
        [
            'name'     => $label,
            'value'    => $status['status'],
            'disabled' => !$status['enabled'],
            'class'    => 'notifications-user-label_individual',
            'id'       => 'notifications-user-'.$source['id'].'-label-'.$label,
        ]
    );
}
