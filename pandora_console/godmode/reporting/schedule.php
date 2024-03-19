<?php

/**
 * Report item list.
 *
 * @category   Reporting
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

global $config;

// Login check.
check_login();
if (!check_acl($config['id_user'], 0, 'RW')
    && !check_acl($config['id_user'], 0, 'RM')
) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access report builder'
    );
    include 'general/noaccess.php';
    exit;
}

require_once $config['homedir'].'/include/functions_agents.php';
enterprise_include_once('include/functions_metaconsole.php');

// Header.
ui_print_standard_header(
    __('Schedule'),
    'images/op_reporting.png',
    false,
    '',
    false,
    [],
    [
        [
            'link'  => '',
            'label' => __('Reporting'),
        ],
        [
            'link'  => '',
            'label' => __('Custom reports'),
        ],
    ]
);

$delete_task = get_parameter('delete_task', false);
if ($delete_task !== false) {
    db_process_sql(sprintf('DELETE FROM tuser_task_scheduled WHERE id = %s', $delete_task));
    ui_print_result_message(
        true,
        __('Successfully deleted')
    );
}

$update_schedule = get_parameter('update_schedule', false);
if ($update_schedule === '1') {
    enterprise_include_once('/godmode/wizards/ConsoleTasks.class.php');
    $task = new ConsoleTasks(0, 'Default message. Not set.', '/images/wizard/consoletasks.png', 'Report Tasks', true);
    $task->updateTask();
    ui_print_result_message(
        true,
        __('Successfully updated')
    );
}

$new_schedule = get_parameter('new_schedule', false);
if ($new_schedule === '1') {
    $name = get_parameter('name', null);
    $sql = sprintf('SELECT * FROM tuser_task_scheduled WHERE name = "%s"', $name);
    if (db_get_all_rows_sql($sql) === false) {
        enterprise_include_once('/godmode/wizards/ConsoleTasks.class.php');
        $task = new ConsoleTasks(0, 'Default message. Not set.', '/images/wizard/consoletasks.png', 'Report Tasks', true);
        $result = $task->createTask();
    } else {
        $result = false;
        $_SESSION['report_task_msg'] = __('The schedule name is already in use.');
    }

    ui_print_result_message(
        $result,
        __('Successfully created'),
        $_SESSION['report_task_msg']
    );
}

$id_group = get_parameter('id_group', 0);
$search = get_parameter('search', '');

$table_aux = new stdClass();
$table_aux->width = '100%';
$table_aux->class = 'filter-table-adv';
$table_aux->size[0] = '50%';
$table_aux->size[1] = '50%';

$table_aux->data[0][0] = html_print_label_input_block(
    __('Group'),
    html_print_select_groups(
        false,
        $access,
        true,
        'id_group',
        $id_group,
        '',
        '',
        '',
        true,
        false,
        true,
        '',
        false,
        '',
        false,
        false,
        'id_grupo'
    )
);

$table_aux->data[0][1] = html_print_label_input_block(
    __('Free text for search: ').ui_print_help_tip(
        __('Search by report name or description, list matches.'),
        true
    ),
    html_print_input_text(
        __('search'),
        $search,
        '',
        30,
        '',
        true
    )
);

$where = '';
if ((bool) users_is_admin() === false) {
    $where = sprintf(' AND id_usuario = "%s"', $config['id_user']);
}

$sql = 'SELECT * FROM tuser_task_scheduled WHERE id_user_task IN (1,2,3,4) '.$where;
$reports = db_get_all_rows_sql($sql);
if ($reports !== false) {
    $table = new stdClass();
    $table->class = 'info_table';
    $table->width = '100%';
    $table->data = [];

    $table->head[0] = __('Name');
    $table->head[1] = __('Report');
    $table->head[2] = __('Type');
    $table->head[3] = __('Schedule / Day');
    $table->head[4] = __('Action');
    $table->head[5] = __('Operations');

    foreach ($reports as $row) {
        $table->cellclass[][5] = 'table_action_buttons';
        $function_name = db_get_value(
            'name',
            'tuser_task',
            'id',
            $row['id_user_task']
        );
        $params = unserialize($row['args']);
        $id_report = ($row['id_report'] ?? $params[0]);
        $report_name = db_get_value(
            'name',
            'treport',
            'id_report',
            $id_report
        );
        $data = [];
        $data[0] = ($row['name'] ?? __('No name'));
        $data[1] = $report_name;
        $data[2] = $function_name;
        $data[3] = date('Y/m/d H:i:s', $params['first_execution']);
        $data[4] = cron_get_scheduled_string($row['scheduled']);
        $data[5] = '';
        if (check_acl($config['id_user'], 0, 'RW')) {
            $data[5] .= html_print_anchor(
                [
                    'href'    => ui_get_full_url(
                        sprintf(
                            'index.php?sec=reporting&sec2=godmode/reporting/manage_schedule&id_task=%s',
                            $row['id']
                        )
                    ),
                    'content' => html_print_image(
                        'images/edit.svg',
                        true,
                        [
                            'title' => __('Edit'),
                            'class' => 'main_menu_icon invert_filter',
                        ]
                    ),
                ],
                true
            );

            $data[5] .= html_print_anchor(
                [
                    'href'    => sprintf(
                        'index.php?sec=custom_report&sec2=godmode/reporting/schedule&delete_task=%s',
                        $row['id']
                    ),
                    'onClick' => 'if (!confirm(\''.__('Are you sure?').'\')) return false;',
                    'content' => html_print_image(
                        'images/delete.svg',
                        true,
                        [
                            'title' => __('Delete'),
                            'class' => 'main_menu_icon invert_filter',
                        ]
                    ),
                ],
                true
            );
        }

        array_push($table->data, $data);
    }

    html_print_table($table);
} else {
    ui_print_info_message(
        __('No data to show')
    );
}

if (check_acl($config['id_user'], 0, 'RW') || check_acl($config['id_user'], 0, 'RM')
) {
    $buttonsOutput = [];
    // Create form.
    $buttonsOutput[] = '<form method="post" action="index.php?sec=reporting&sec2=godmode/reporting/manage_schedule">';
    $buttonsOutput[] = html_print_submit_button(
        __('Create report'),
        'create',
        false,
        [ 'icon' => 'next' ],
        true
    );
    $buttonsOutput[] = '</form>';

    echo html_print_action_buttons(
        implode('', $buttonsOutput),
        [
            'type'          => 'form_action',
            'right_content' => $tablePagination,
        ],
        true
    );
}
