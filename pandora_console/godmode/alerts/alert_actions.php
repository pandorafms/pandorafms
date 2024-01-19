<?php
/**
 * Alert actions
 *
 * @category   Alerts
 * @package    Pandora FMS
 * @subpackage Community
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 * |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 * |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 Pandora FMS
 * Please see https://pandorafms.com/community/ for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */

// Load global vars.
global $config;

require_once $config['homedir'].'/include/functions_alerts.php';
require_once $config['homedir'].'/include/functions_users.php';
require_once $config['homedir'].'/include/functions_groups.php';
enterprise_include_once('meta/include/functions_alerts_meta.php');

check_login();

if (! check_acl($config['id_user'], 0, 'LM')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Alert actions'
    );
    include 'general/noaccess.php';
    exit;
}

$update_action = (bool) get_parameter('update_action');
$create_action = (bool) get_parameter('create_action');
$delete_action = (bool) get_parameter('delete_action');
$copy_action = (bool) get_parameter('copy_action');
$pure = get_parameter('pure', 0);

if (defined('METACONSOLE')) {
    $sec = 'advanced';
} else {
    $sec = 'galertas';
}

$can_edit_all = false;
if (check_acl_restricted_all($config['id_user'], 0, 'LM')) {
    $can_edit_all = true;
}

// Header.
if (defined('METACONSOLE')) {
    alerts_meta_print_header();
} else {
    ui_print_standard_header(
        __('Alerts'),
        'images/gm_alerts.png',
        false,
        '',
        true,
        [],
        [
            [
                'link'  => '',
                'label' => __('Alert actions'),
            ],
        ]
    );
}

if ($copy_action) {
    $id = get_parameter('id');

    $al_action = alerts_get_alert_action($id);

    if ($al_action !== false) {
        // If user who doesn't have permission to modify group all tries to copy an action with group=ALL.
        if ($can_edit_all == false && $al_action['id_group'] == 0) {
            $al_action['id_group'] = users_get_first_group(false, 'LM', false);
        } else {
            $own_info = get_user_info($config['id_user']);
            if ($can_edit_all == true || check_acl($config['id_user'], 0, 'PM')) {
                $own_groups = array_keys(
                    users_get_groups($config['id_user'], 'LM')
                );
            } else {
                $own_groups = array_keys(
                    users_get_groups($config['id_user'], 'LM', false)
                );
            }

            $is_in_group = in_array($al_action['id_group'], $own_groups);
            // Then action group have to be in his own groups.
            if (!$is_in_group) {
                db_pandora_audit(
                    AUDIT_LOG_ACL_VIOLATION,
                    'Trying to access Alert Management'
                );
                include 'general/noaccess.php';
                exit;
            }
        }
    }

    $result = alerts_clone_alert_action($id, $al_action['id_group']);

    $auditMessage = ((bool) $result === true)
            ? sprintf('Duplicate alert action %s clone to %s', $id, $result)
            : sprintf('Fail try to duplicate alert action %s', $id);

    db_pandora_audit(
        AUDIT_LOG_ALERT_MANAGEMENT,
        $auditMessage
    );

    ui_print_result_message(
        $result,
        __('Successfully copied'),
        __('Could not be copied')
    );
}

if ($update_action || $create_action) {
    alerts_ui_update_or_create_actions($update_action);
}

if ($delete_action) {
    $id = get_parameter('id');

    $al_action = alerts_get_alert_action($id);

    if (!check_acl_restricted_all($config['id_user'], $al_action['id_group'], 'LM')) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access Alert Management'
        );
        include 'general/noaccess.php';
        exit;
    }

    if ($al_action !== false) {
        // If user tries to delete an action with group=ALL.
        if ($al_action['id_group'] == 0) {
            // Then must have "PM" access privileges.
            if (! check_acl($config['id_user'], 0, 'PM')) {
                db_pandora_audit(
                    AUDIT_LOG_ACL_VIOLATION,
                    'Trying to access Alert Management'
                );
                include 'general/noaccess.php';
                exit;
            }

            // If user tries to delete an action of others groups.
        } else {
            $own_info = get_user_info($config['id_user']);
            if ($own_info['is_admin'] || check_acl($config['id_user'], 0, 'PM')) {
                $own_groups = array_keys(
                    users_get_groups($config['id_user'], 'LM')
                );
            } else {
                $own_groups = array_keys(
                    users_get_groups($config['id_user'], 'LM', false)
                );
            }

            $is_in_group = in_array($al_action['id_group'], $own_groups);
            // Then action group have to be in his own groups.
            if (!$is_in_group) {
                db_pandora_audit(
                    AUDIT_LOG_ACL_VIOLATION,
                    'Trying to access Alert Management'
                );
                include 'general/noaccess.php';
                exit;
            }
        }
    }


    $result = alerts_delete_alert_action($id);

    $auditMessage = ((bool) $result === true)
    ? sprintf('Delete alert action #%s', $id)
    : sprintf('Fail try to delete alert action #%s', $id);

    db_pandora_audit(
        AUDIT_LOG_ALERT_MANAGEMENT,
        $auditMessage
    );

    ui_print_result_message(
        $result,
        __('Successfully deleted'),
        __('Could not be deleted')
    );
}

$is_management_allowed = is_management_allowed();
if ($is_management_allowed === false) {
    if (is_metaconsole() === false) {
        $url = '<a target="_blank" href="'.ui_get_meta_url(
            'index.php?sec=advanced&sec2=godmode/alerts/alert_actions&tab=action&pure=0'
        ).'">'.__('metaconsole').'</a>';
    } else {
        $url = __('any node');
    }

    ui_print_warning_message(
        __(
            'This node is configured with centralized mode. All alert actions information is read only. Go to %s to manage it.',
            $url
        )
    );
}

$search_string = (string) get_parameter('search_string', '');
$group = (int) get_parameter('group', 0);
$group_search = (int) get_parameter('group_search', 0);
$id_command_search = (int) get_parameter('id_command_search', 0);
$url = 'index.php?sec='.$sec.'&sec2=godmode/alerts/alert_actions&search_string='.$search_string.'&group_search='.$group_search.'&id_command_search='.$id_command_search;

// Filter table.
$table_filter = new stdClass();
$table_filter->width = '100%';
$table_filter->class = 'databox filters no_border filter-table-adv';
$table_filter->style = [];
$table_filter->style[0] = 'width: 33%';
$table_filter->style[1] = 'width: 33%';
$table_filter->style[2] = 'width: 33%';
$table_filter->data = [];
$table_filter->colspan = [];
$table_filter->colspan[1][0] = 3;

$table_filter->data[0][0] = html_print_label_input_block(
    __('Search'),
    html_print_input_text(
        'search_string',
        $search_string,
        '',
        25,
        255,
        true
    )
);

$return_all_group = false;

if (users_can_manage_group_all('LM') === true) {
    $return_all_group = true;
}


$table_filter->data[0][1] = html_print_label_input_block(
    __('Group'),
    html_print_select_groups(
        $config['id_user'],
        'LM',
        $return_all_group,
        'group_search',
        $group_search,
        '',
        '',
        0,
        true
    )
);

$commands_sql = db_get_all_rows_filter(
    'talert_commands',
    ['id_group' => array_keys(users_get_groups(false, 'LW'))],
    [
        'id',
        'name',
    ],
    'AND',
    false,
    true
);

$commands = db_get_all_rows_sql($commands_sql);

$table_filter->data[0][2] = html_print_label_input_block(
    __('Command'),
    html_print_select(
        index_array($commands, 'id', 'name'),
        'id_command_search',
        $id_command_search,
        '',
        __('None'),
        0,
        true,
        false,
        true,
        '',
        false,
        'width:100%'
    )
);

$table_filter->data[1][0] = '<div class="float-right">';
$table_filter->data[1][0] .= html_print_submit_button(
    __('Search'),
    '',
    false,
    [
        'icon'  => 'search',
        'class' => 'mini',
    ],
    true
);
$table_filter->data[1][0] .= '</div>';


$show_table_filter = '<form method="post" action="'.$url.'">';
$show_table_filter .= ui_toggle(
    html_print_table($table_filter, true),
    '<span class="subsection_header_title">'.__('Search').'</span>',
    __('Search'),
    'search',
    true,
    true,
    '',
    'white-box-content no_border',
    'filter-datatable-main box-flat white_table_graph fixed_filter_bar  '
);
$show_table_filter .= '</form>';

echo $show_table_filter;


$table = new stdClass();
$table->width = '100%';
$table->class = 'info_table';
$table->data = [];
$table->head = [];
$table->head[0] = __('Name');
$table->head[1] = __('Command');
$table->head[2] = __('Group');
if (is_management_allowed() === true) {
    $table->head[3] = __('Copy');
    $table->head[4] = __('Delete');
}

$table->style = [];
$table->style[0] = 'font-weight: bold';
$table->size = [];
$table->size[3] = '40px';
$table->size[4] = '40px';
$table->align = [];
$table->align[1] = 'left';
$table->align[2] = 'left';
$table->align[3] = 'left';
$table->align[4] = 'left';

$filter = [];
if (!is_user_admin($config['id_user'])) {
    $filter['talert_actions.id_group'] = array_keys(
        users_get_groups(false, 'LM')
    );
}

if ($group_search !== 0) {
    $filter['talert_actions.id_group'] = $group_search;
}

if ($search_string !== '') {
    $filter['talert_actions.name'] = '%'.$search_string.'%';
}

if ($id_command_search !== 0) {
    $filter['talert_commands.id'] = $id_command_search;
}

$actions = db_get_all_rows_filter(
    'talert_actions INNER JOIN talert_commands ON talert_actions.id_alert_command = talert_commands.id',
    $filter,
    'talert_actions.* , talert_commands.id_group AS command_group, talert_commands.name AS command_name'
);

if ($actions === false) {
    $actions = [];
}

// Pagination.
$total_actions = count($actions);
$offset = (int) get_parameter('offset');
$limit = (int) $config['block_size'];
$actions = array_slice($actions, $offset, $limit);

$rowPair = true;
$iterator = 0;
foreach ($actions as $action) {
    if ((isset($config['ITSM_enabled']) === false || (bool) $config['ITSM_enabled'] === false)
        && $action['name'] === 'Create&#x20;Pandora&#x20;ITSM&#x20;ticket'
    ) {
        continue;
    }

    if ($rowPair) {
        $table->rowclass[$iterator] = 'rowPair';
    } else {
        $table->rowclass[$iterator] = 'rowOdd';
    }

    $rowPair = !$rowPair;
    $iterator++;

    $data = [];

    $data[0] = '<a href="index.php?sec='.$sec.'&sec2=godmode/alerts/configure_alert_action&id='.$action['id'].'&pure='.$pure.'&offset='.$offset.'">'.$action['name'].'</a>';
    if ($action['id_group'] == 0 && $can_edit_all == false) {
        $data[0] .= ui_print_help_tip(__('You cannot edit this action, You don\'t have the permission to edit All group.'), true);
    }

    $data[1] = $action['command_name'];
    $data[2] = ui_print_group_icon($action['id_group'], true).'&nbsp;';
    if (!alerts_validate_command_to_action($action['id_group'], $action['command_group'])) {
        $data[2] .= html_print_image(
            'images/error.png',
            true,
            // FIXME: Translation.
            [
                'title' => __('The action and the command associated with it do not have the same group. Please contact an administrator to fix it.'),
            ]
        );
    }

    $data[3] = '';
    $data[4] = '';

    if (is_management_allowed() === true
        && check_acl($config['id_user'], $action['id_group'], 'LM')
    ) {
        $table->cellclass[] = [
            3 => 'table_action_buttons',
            4 => 'table_action_buttons',
        ];

        $id_action = $action['id'];
        $text_confirm = __('Are you sure?');

        $data[3] = '<form method="post" style="display: inline; float: right" onsubmit="if (!confirm(\''.$text_confirm.'\')) return false;">';
        $data[3] .= html_print_input_hidden('copy_action', 1, true);
        $data[3] .= html_print_input_hidden('id', $id_action, true);
        $data[3] .= html_print_input_image(
            'dup',
            'images/copy.svg',
            1,
            '',
            true,
            [
                'title' => __('Duplicate'),
                'class' => 'main_menu_icon invert_filter',
            ]
        );
        $data[3] .= '</form> ';

        if ($action['id_group'] != 0 || $can_edit_all == true) {
            $data[4] = '<form method="post" style="display: inline; float: right" onsubmit="if (!confirm(\''.$text_confirm.'\')) return false;">';
            $data[4] .= html_print_input_hidden('delete_action', 1, true);
            $data[4] .= html_print_input_hidden('id', $id_action, true);
            $data[4] .= html_print_input_image(
                'del',
                'images/delete.svg',
                1,
                '',
                true,
                [
                    'title' => __('Delete'),
                    'class' => 'main_menu_icon invert_filter',
                ]
            );
            $data[4] .= '</form> ';
        } else {
            $data[4] = '';
        }
    }

    array_push($table->data, $data);
}

$pagination = '';
if (isset($data)) {
    html_print_table($table);
    $show_count = false;
    if (is_metaconsole() === true) {
        $show_count = true;
    }

    $pagination = ui_pagination($total_actions, $url, 0, 0, true, 'offset', $show_count, '');
} else {
    ui_print_info_message(['no_close' => true, 'message' => __('No alert actions configured') ]);
}

if (is_management_allowed() === true) {
    echo '<div class="action-buttons" style="width: '.$table->width.'">';
    echo '<form method="post" action="index.php?sec='.$sec.'&sec2=godmode/alerts/configure_alert_action&pure='.$pure.'&offset='.$offset.'">';
    $button = html_print_submit_button(__('Create'), 'create', false, ['icon' => 'wand'], true);
    html_print_input_hidden('create_alert', 1);
    html_print_action_buttons($button, ['right_content' => $pagination]);
    echo '</form>';
    echo '</div>';
}
