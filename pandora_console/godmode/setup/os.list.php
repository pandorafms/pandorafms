<?php
/**
 * Os List.
 *
 * @category   Os
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
 * Copyright (c) 2005-2023 Artica Soluciones Tecnologicas
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

// Load global vars.
global $config;

check_login();

if (! check_acl($config['id_user'], 0, 'PM') && ! is_user_admin($config['id_user'])) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Setup Management'
    );
    include 'general/noaccess.php';
    return;
}

$is_management_allowed = true;
if (is_management_allowed() === false) {
    $is_management_allowed = false;
    if (is_metaconsole() === false) {
        $url = '<a target="_blank" href="'.ui_get_meta_url(
            'index.php?sec=advanced&sec2=advanced/component_management&tab=os_manage&tab2=list&pure='.(int) $config['pure']
        ).'">'.__('metaconsole').'</a>';
    } else {
        $url = __('any node');
    }

    ui_print_warning_message(
        __(
            'This node is configured with centralized mode. All OS definitions are read only. Go to %s to manage them.',
            $url
        )
    );
}

$table = new stdClass();
$table->class = 'info_table';
$table->head[0] = __('ID');
$table->head[1] = __('Icon');
$table->head[2] = __('Name');
$table->head[3] = __('Description');
if ($is_management_allowed === true) {
    $table->head[4] = __('Actions');
}

if ($is_management_allowed === true) {
    $table->align[4] = 'center';
}

$table->size[0] = '5%';
if ($is_management_allowed === true) {
    $table->size[4] = '20px';
}

// Prepare pagination.
$offset = (int) get_parameter('offset');
$limit = $config['block_size'];
$count_osList = db_get_value('count(*)', 'tconfig_os');

$osList = db_get_all_rows_filter(
    'tconfig_os',
    [
        'offset' => $offset,
        'limit'  => $limit,
    ]
);

if ($osList === false) {
    $osList = [];
}

$table->data = [];
foreach ($osList as $os) {
    $data = [];
    $data[] = $os['id_os'];
    $data[] = ui_print_os_icon($os['id_os'], false, true);
    if ($is_management_allowed === true) {
        if (is_metaconsole() === true) {
            $osNameUrl = 'index.php?sec=advanced&sec2=advanced/component_management&tab=os_manage&action=edit&tab2=builder&id_os='.$os['id_os'];
        } else {
            $osNameUrl = 'index.php?sec=gsetup&sec2=godmode/setup/os&action=edit&tab=builder&id_os='.$os['id_os'];
        }

        $data[] = html_print_anchor(
            [
                'href'    => $osNameUrl,
                'content' => io_safe_output($os['name']),
            ],
            true
        );
    } else {
        $data[] = io_safe_output($os['name']);
    }

    $data[] = ui_print_truncate_text(io_safe_output($os['description']), 'description', true, true);

    if ($is_management_allowed === true) {
        $table->cellclass[][4] = 'table_action_buttons';
        if ($os['id_os'] > 16) {
            if (is_metaconsole() === true) {
                $hrefDelete = 'index.php?sec=advanced&sec2=advanced/component_management&tab=os_manage&action=delete&tab2=list&id_os='.$os['id_os'];
            } else {
                $hrefDelete = 'index.php?sec=gsetup&sec2=godmode/setup/os&action=delete&tab=list&id_os='.$os['id_os'];
            }

            $data[] = html_print_anchor(
                [
                    'href'    => $hrefDelete,
                    'content' => html_print_image('images/delete.svg', true, ['class' => 'main_menu_icon invert_filter']),
                ],
                true
            );
        } else {
            // The original icons of pandora don't delete.
            $data[] = '';
        }
    }

    $table->data[] = $data;
}

$tablePagination = '';
if (isset($data) === true) {
    html_print_table($table);
    $tablePagination = ui_pagination(
        $count_osList,
        ui_get_url_refresh(['message' => false]),
        $offset,
        0,
        true,
        'offset',
        false,
        ''
    );
} else {
    ui_print_info_message(['no_close' => true, 'message' => __('There are no defined operating systems') ]);
}

$buttons = '';
if (is_metaconsole() === true) {
    $buttons .= '<form method="post" action="index.php?sec=advanced&sec2=advanced/component_management&tab=os_manage&tab2=builder">';
    $buttons .= html_print_submit_button(
        __('Create OS'),
        '',
        false,
        ['icon' => 'next'],
        true
    );
    $buttons .= '</form>';
}

html_print_action_buttons(
    $buttons,
    [
        'type'          => 'data_table',
        'class'         => 'fixed_action_buttons',
        'right_content' => $tablePagination,
    ]
);
