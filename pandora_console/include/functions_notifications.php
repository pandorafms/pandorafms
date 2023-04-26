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

require_once $config['homedir'].'/include/functions_messages.php';

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
            array_push($targets['users'], get_user_fullname($row['id_user']));
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
 * Return subtypes.
 *
 * @param string|null $source Source filter or all.
 *
 * @return array
 */
function notifications_get_subtypes(?string $source=null)
{
    $subtypes = [
        'System status' => [
            'NOTIF.LICENSE.LIMITED',
            'NOTIF.FILES.ATTACHMENT',
            'NOTIF.FILES.DATAIN',
            'NOTIF.FILES.DATAIN.BADXML',
            'NOTIF.PHP.SAFE_MODE',
            'NOTIF.PHP.INPUT_TIME',
            'NOTIF.PHP.EXECUTION_TIME',
            'NOTIF.PHP.UPLOAD_MAX_FILESIZE',
            'NOTIF.PHP.MEMORY_LIMIT',
            'NOTIF.PHP.DISABLE_FUNCTIONS',
            'NOTIF.PHP.CHROMIUM',
            'NOTIF.PHP.VERSION',
            'NOTIF.HISTORYDB',
            'NOTIF.PANDORADB',
            'NOTIF.PANDORADB.HISTORICAL',
            'NOTIF.HISTORYDB.MR',
            'NOTIF.EXT.ELASTICSEARCH',
            'NOTIF.EXT.LOGSTASH',
            'NOTIF.METACONSOLE.DB_CONNECTION',
            'NOTIF.DOWNTIME',
            'NOTIF.UPDATEMANAGER.REGISTRATION',
            'NOTIF.MISC.EVENTSTORMPROTECTION',
            'NOTIF.MISC.DEVELOPBYPASS',
            'NOTIF.MISC.FONTPATH',
            'NOTIF.SECURITY.DEFAULT_PASSWORD',
            'NOTIF.UPDATEMANAGER.OPENSETUP',
            'NOTIF.UPDATEMANAGER.UPDATE',
            'NOTIF.UPDATEMANAGER.MINOR',
            'NOTIF.UPDATEMANAGER.MESSAGES',
            'NOTIF.CRON.CONFIGURED',
            'NOTIF.ALLOWOVERRIDE.MESSAGE',
            'NOTIF.HAMASTER.MESSAGE',
            'NOTIF.SERVER.STATUS',
            'NOTIF.SERVER.QUEUE',
            'NOTIF.SERVER.MASTER',
            'NOTIF.ACCESSSTASTICS.PERFORMANCE',
            'NOTIF.VARIABLES.PERFORMANCE',
        ],
    ];

    if ($source === null) {
        return $subtypes;
    }

    if (isset($subtypes[$source]) === true) {
        return $subtypes[$source];
    }

    return [];
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

    // Using distinct to avoid double response on group messages read by user.
    $sql = sprintf(
        'SELECT DISTINCT tm.*, utimestamp_read > 0 as "read"
            FROM tmensajes tm 
            LEFT JOIN tnotification_user nu
                ON tm.id_mensaje=nu.id_mensaje 
                AND nu.id_user="%s"
                AND tm.id_mensaje=%d
            LEFT JOIN (tnotification_group ng
                INNER JOIN tusuario_perfil up
                    ON ng.id_group=up.id_grupo
                    AND up.id_grupo=ng.id_group
            ) ON tm.id_mensaje=ng.id_mensaje 
            WHERE utimestamp_erased is null
                AND (nu.id_user="%s" OR up.id_usuario="%s" OR ng.id_group=0)',
        $config['id_user'],
        $id_message,
        $config['id_user'],
        $config['id_user']
    );

    return (bool) db_get_value_sql($sql);
}


/**
 * Returns the target users and groups assigned to be notified on
 * desired source.
 *
 * @param integer     $id_source Source identificator.
 * @param string|null $subtype   Subtype identification.
 *
 * @return array [users] and [groups] with the targets.
 */
function get_notification_source_targets(
    int $id_source,
    ?string $subtype=null
) {
    $ret = [];

    $filter = '';
    if ($subtype !== null) {
        $matches = [];
        if (preg_match('/(.*)\.\d+$/', $subtype, $matches) > 0) {
            $subtype = $matches[1];
        }

        $filter = sprintf(
            ' AND (ns.`subtype_blacklist` IS NULL OR ns.`subtype_blacklist` NOT LIKE "%%%s%%") ',
            $subtype
        );
    }

    $users = db_get_all_rows_sql(
        sprintf(
            'SELECT 
                id_user,
                IF(ns.user_editable = 1,nsu.also_mail,ns.also_mail) AS also_mail
            FROM tnotification_source_user nsu
            INNER JOIN tnotification_source ns
                ON ns.id=nsu.id_source
                %s
            WHERE ns.id = %d 
            AND ((ns.enabled is NULL OR ns.enabled != 0)
                OR (nsu.enabled is NULL OR nsu.enabled != 0))',
            $filter,
            $id_source
        )
    );

    if ($users !== false) {
        $i = 0;
        foreach ($users as $user) {
            $ret['users'][$i]['id_user'] = $user['id_user'];
            $ret['users'][$i++]['also_mail'] = $user['also_mail'];
        }
    }

    $groups = db_get_all_rows_sql(
        sprintf(
            'SELECT id_group,ns.also_mail
            FROM tnotification_source_group nsg
            INNER JOIN tnotification_source ns
                ON ns.id=nsg.id_source
                %s
            WHERE ns.id = %d 
            AND (ns.enabled is NULL OR ns.enabled != 0)',
            $filter,
            $id_source
        )
    );

    if ($groups !== false) {
        $i = 0;
        foreach ($groups as $group) {
            $ret['groups'][$i]['id_group'] = $group['id_group'];
            $ret['groups'][$i++]['also_mail'] = $group['also_mail'];
        }
    }

    return $ret;
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
                $field = 'tnsg.'.$field;
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

    // If fails or no one is selected, return empty array.
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
    // Source id is mandatory.
    if (!isset($source_id)) {
        return false;
    }

    // Delete from database.
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
    // Source id is mandatory.
    if (!isset($source_id)) {
        return false;
    }

    // Delete from database.
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
    // Source id is mandatory.
    if (!isset($source_id)) {
        return false;
    }

    // Insert into database all groups passed.
    $res = true;
    foreach ($groups as $group) {
        if (!isset($group)) {
            continue;
        }

        $res = $res &&
        db_process_sql_insert(
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
    // Source id is mandatory.
    if (!isset($source_id)) {
        return false;
    }

    // Insert into database all groups passed.
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

        $res = $res &&
        db_process_sql_insert(
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
    $all_groups = users_get_groups_for_select(
        false,
        'AR',
        false,
        true,
        $groups_selected
    );
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
    $users_selected = array_keys(
        notifications_get_user_sources_for_select($source_id)
    );
    $users = get_users('id_user', ['!id_user' => $users_selected], ['id_user']);
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
        'status'  => (bool) $status === true ? 1 : 0,
        'enabled' => (bool) $enabled === true ? 1 : 0,
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
    if (!$source['enabled']) {
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
        array_keys(notifications_get_group_sources_for_select($source['id']))
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
 * Get the counters of notification. Usefull to print the header notification
 * ball.
 *
 * @return array With the fields:
 *      'notifications' => Total new notifications,
 *      'last_id' => Id of last read value. Usefull to make comparisons.
 */
function notifications_get_counters()
{
    $num_notifications = 0;
    $last_id = 0;
    $last_message = messages_get_overview('timestamp', 'DESC', false, false, 1);
    if (!empty($last_message)) {
        $num_notifications = messages_get_count();
        $last_id = $last_message[0]['id_mensaje'];
    }

    return [
        'notifications' => $num_notifications,
        'last_id'       => $last_id,
    ];
}


// /////////////////////////////////////////////////////////////////////////////
// UI FUNCTIONS
// /////////////////////////////////////////////////////////////////////////////


/**
 * Print the notification ball to see unread messages.
 *
 * @param integer $num_notifications Number of messages shown
 *      in notification ball.
 * @param integer $last_id           Id of last message shown
 *           in the notification ball.
 *
 * @return string with HTML code of notification ball.
 */
function notifications_print_ball($num_notifications, $last_id)
{
    $no_notifications = (int) $num_notifications === 0;
    $class_status = $no_notifications
    ? 'notification-ball-no-messages'
    : 'notification-ball-new-messages';
    return sprintf(
        '<div
            %s
            class="notification-ball %s"
            id="notification-ball-header"
            last_id="%s"
        >
            %s
        </div>',
        $no_notifications ? '' : 'onclick="addNotifications(event)"',
        $class_status,
        $last_id,
        $num_notifications
    );
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
    // Get some values to generate the title.
    $switch_values = [
        'name'  => 'enable-'.$source['id'],
        'value' => $source['enabled'],
        'id'    => 'nt-'.$source['id'].'-enabled',
        'class' => 'elem-clickable',
    ];

    // Search if group all is set and handle that situation.
    $source_groups = notifications_get_group_sources_for_select($source['id']);
    $is_group_all = isset($source_groups['0']);
    if ($is_group_all) {
        unset($source_groups['0']);
    }

    // Generate the title.
    $html_title = "<div class='global-config-notification-title'>";
    $html_title .= html_print_switch($switch_values);
    $html_title .= '<h2>'.$source['description'].'</h2>';
    $html_title .= '</div>';

    // Generate the html for title.
    $html_selectors = "<div class='global-config-notification-selectors'>";
    $html_selectors .= notifications_print_source_select_box(
        notifications_get_user_sources_for_select($source['id']),
        'users',
        $source['id']
    );
    $html_selectors .= notifications_print_source_select_box(
        $source_groups,
        'groups',
        $source['id']
    );
    $html_selectors .= '</div>';

    $html_checkboxes = '';

    $blacklist = json_decode($source['subtype_blacklist'], 1);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $blacklist = [];
    }

    if ($source['description'] === io_safe_input('System status')) {
        $system_subtypes = notifications_get_subtypes('System status');

        foreach ($system_subtypes as $type) {
            $html_checkboxes .= html_print_input(
                [
                    'input_class' => 'flex flex-row w290px margin-soft',
                    'label'       => $type,
                    'name'        => 'check-'.$type,
                    'type'        => 'switch',
                    'id'          => 'nt-'.$source['id'].'.'.$type.'-subtype',
                    'class'       => 'elem-clickable',
                    'value'       => isset($blacklist[$type]) === false,
                    'return'      => true,
                ]
            );
        }

        $html_checkboxes = ui_print_toggle(
            [
                'content'         => $html_checkboxes,
                'name'            => __('Subtype customization'),
                'hidden_default'  => false,
                'return'          => true,
                'toggle_class'    => '',
                'container_class' => 'flex flex-row flex-start w100p',
                'main_class'      => '',
                'clean'           => true,
            ]
        );
    }

    // Return all html.
    return $html_title.$html_selectors.$html_checkboxes;
}


/**
 * Print select boxes of notified users or groups
 *
 * @param array  $info_selec All info required for build the selector.
 * @param string $id         One of users|groups.
 * @param string $source_id  Id of source.
 *
 * @return string HTML with the generated selector
 */
function notifications_print_source_select_box($info_selec, $id, $source_id)
{
    $title = $id === 'users' ? __('Notified users') : __('Notified groups');
    $add_title = $id === 'users' ? __('Add users') : __('Add groups');
    $delete_title = $id === 'users' ? __('Delete users') : __('Delete groups');

    // Generate the HTML.
    return sprintf(
        "
        <div class='global-config-notification-single-selector'>
            <div>
                <h5>%s</h5>
                %s
            </div>
            <div class='global-notifications-icons'>
                %s
                %s
            </div>
        </div>
        ",
        $title,
        // Put a true if empty sources to avoid to sow the 'None' value.
        html_print_select(
            empty($info_selec) ? true : $info_selec,
            'multi-'.$id.'-'.$source_id.'[]',
            0,
            false,
            '',
            '',
            true,
            true
        ),
        html_print_image(
            'images/fail@svg.svg',
            true,
            [
                'style'   => 'width: 14px;rotate: 45deg',
                'title'   => $add_title,
                'class'   => 'main_menu_icon invert_filter',
                'onclick' => sprintf("add_source_dialog('%s', '%s')", $id, $source_id),
            ]
        ),
        html_print_image(
            'images/delete.svg',
            true,
            [
                'style'   => 'width: 14px',
                'title'   => $delete_title,
                'class'   => 'main_menu_icon invert_filter',
                'onclick' => sprintf(
                    "remove_source_elements('%s', '%s')",
                    $id,
                    $source_id
                ),
            ]
        )
    );
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
    return sprintf(
        "
        <div class='global_config_notifications_dialog_add'>
            %s
            <div class='global_config_notifications_two_ways_form_arrows'>
                %s
                %s
            </div>
            %s
        </div>
        %s
        ",
        html_print_select(
            empty($info_selec) ? true : $info_selec,
            'all-multi-'.$users.'-'.$source_id.'[]',
            0,
            false,
            '',
            '',
            true,
            true,
            true,
            ''
        ),
        html_print_image(
            'images/arrow@svg.svg',
            true,
            [
                'title'   => __('Add elements'),
                'style'   => 'rotate: 90deg',
                'onclick' => sprintf(
                    "notifications_modify_two_ways_element('%s', '%s', 'add')",
                    $users,
                    $source_id
                ),
                'class'   => 'main_menu_icon invert_filter',
            ]
        ),
        html_print_image(
            'images/arrow@svg.svg',
            true,
            [
                'title'   => __('Remove elements'),
                'style'   => 'rotate: 0',
                'onclick' => sprintf(
                    "notifications_modify_two_ways_element('%s', '%s', 'remove')",
                    $users,
                    $source_id
                ),
                'class'   => 'main_menu_icon invert_filter',
            ]
        ),
        html_print_select(
            true,
            'selected-multi-'.$users.'-'.$source_id.'[]',
            0,
            false,
            '',
            '',
            true,
            true,
            true,
            ''
        ),
        html_print_button(
            __('Add'),
            'Add',
            false,
            sprintf(
                "notifications_add_source_element_to_database('%s', '%s')",
                $users,
                $source_id
            ),
            "class='sub add'",
            true
        )
    );
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
    $switch = html_print_switch(
        [
            'name'     => $label,
            'value'    => $status['status'],
            'disabled' => !$status['enabled'],
            'class'    => 'notifications-user-label_individual',
            'id'       => 'notifications-user-'.$source['id'].'-label-'.$label,
        ]
    );

    $data = [
        'disabled' => !$status['enabled'],
        'switch'   => $switch,
    ];

    return $data;
}


/**
 * Generates the dropdown notifications menu.
 *
 * @return string HTML with dropdown menu.
 */
function notifications_print_dropdown()
{
    $mess = messages_get_overview('status', 'DESC', false, true);
    if ($mess === false) {
        $mess = [];
    }

    return sprintf(
        "<div id='notification-wrapper'>
            <div id='notification-wrapper-inner'>
                %s
            </div>
        </div>
        <div
            id='notification-wrapper-shadow'
            onclick='notifications_hide()'
        >
        </div>
        ",
        array_reduce(
            $mess,
            function ($carry, $message) {
                return $carry.notifications_print_dropdown_element($message);
            },
            ''
        )
    );
}


/**
 * Print a single notification box
 *
 * @param array $message_info Info of printed message.
 *
 * @return string HTML code of single message
 */
function notifications_print_dropdown_element($message_info)
{
    $action = '';
    switch ($message_info['description']) {
        case 'Official&#x20;communication':
            $action = 'show_modal(this.id);';
            $target = '';
            $body_preview = __('Click here to get more information');
        break;

        default:
            $action = '';
            $target = '_blank';
            $body_preview = strip_tags(
                io_safe_output($message_info['mensaje']),
                '<br><pre>'
            );
        break;
    }

    $split_subject = explode(' ', io_safe_output($message_info['subject']));
    $is_image = false;
    $img = '';
    foreach ($split_subject as $item) {
        if ($is_image) {
            if (preg_match('/src/', $item)) {
                $img .= $item.' >';
                $is_image = false;
            }
        }

        if (preg_match('/img/', $item)) {
            $img = '<img ';
            $is_image = true;
        }
    }

    if ($img !== '') {
        $message_info['subject'] = io_safe_input($img);
    }

    return sprintf(
        "<a
            class='notification-item'
            onclick='%s'
            id='notification-item-id-%s'
            href='%s'
            target='%s'
        >
            %s
            <div class='notification-info'>
                <h4 class='notification-title'>
                    %s
                </h4>
                <p class='notification-subtitle'>
                    %s
                </p>
            </div>
        </a>",
        $action.';click_on_notification_toast(event)',
        $message_info['id_mensaje'],
        messages_get_url($message_info['id_mensaje']),
        $target,
        html_print_image('images/info.svg', true, ['style' => 'height: 40px;margin-left: -20px;margin-top: -40px;']),
        io_safe_output($message_info['subject']),
        $body_preview
    );
}
