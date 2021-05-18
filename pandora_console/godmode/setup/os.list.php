<?php
/**
 * Os.
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

// Load global vars.
global $config;

check_login();

if (! check_acl($config['id_user'], 0, 'PM') && ! is_user_admin($config['id_user'])) {
    db_pandora_audit('ACL Violation', 'Trying to access Setup Management');
    include 'general/noaccess.php';
    return;
}

$is_management_allowed = true;
if (is_management_allowed() === false) {
    $is_management_allowed = false;
    ui_print_warning_message(
        __('This node is configured with centralized mode. All Os information is read only. Go to metaconsole to manage it.')
    );
}

$table = new stdClass();

$table->width = '100%';
$table->class = 'info_table';

$table->head[0] = '';
$table->head[1] = __('ID');
$table->head[2] = __('Name');
$table->head[3] = __('Description');
if ($is_management_allowed === true) {
    $table->head[4] = '';
}

$table->align[0] = 'center';
if ($is_management_allowed === true) {
    $table->align[4] = 'center';
}

$table->size[0] = '20px';
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
    $data[] = ui_print_os_icon($os['id_os'], false, true);
    $data[] = $os['id_os'];
    if ($is_management_allowed === true) {
        if (is_metaconsole() === true) {
            $data[] = '<a href="index.php?sec=advanced&sec2=advanced/component_management&tab=os_manage&action=edit&tab2=builder&id_os='.$os['id_os'].'">'.io_safe_output($os['name']).'</a>';
        } else {
            $data[] = '<a href="index.php?sec=gsetup&sec2=godmode/setup/os&action=edit&tab=builder&id_os='.$os['id_os'].'">'.io_safe_output($os['name']).'</a>';
        }
    } else {
        $data[] = io_safe_output($os['name']);
    }

    $data[] = ui_print_truncate_text(io_safe_output($os['description']), 'description', true, true);

    if ($is_management_allowed === true) {
        $table->cellclass[][4] = 'action_buttons';
        if ($os['id_os'] > 16) {
            if (is_metaconsole()) {
                $data[] = '<a href="index.php?sec=advanced&sec2=advanced/component_management&tab=os_manage&action=delete&tab2=list&id_os='.$os['id_os'].'">'.html_print_image('images/cross.png', true).'</a>';
            } else {
                $data[] = '<a href="index.php?sec=gsetup&sec2=godmode/setup/os&action=delete&tab=list&id_os='.$os['id_os'].'">'.html_print_image('images/cross.png', true, ['class' => 'invert_filter']).'</a>';
            }
        } else {
            // The original icons of pandora don't delete.
            $data[] = '';
        }
    }

    $table->data[] = $data;
}

if (isset($data) === true) {
    ui_pagination($count_osList, ui_get_url_refresh(['message' => false]), $offset);
    html_print_table($table);
    ui_pagination($count_osList, ui_get_url_refresh(['message' => false]), $offset, 0, false, 'offset', true, 'pagination-bottom');
} else {
    ui_print_info_message(['no_close' => true, 'message' => __('There are no defined operating systems') ]);
}

if (is_metaconsole() === true) {
    echo '<form method="post" action="index.php?sec=advanced&sec2=advanced/component_management&tab=os_manage&tab2=builder">';
        echo "<div style='text-align:right;width:".$table->width."'>";
            html_print_submit_button(__('Create OS'), '', false, 'class="sub next"');
        echo '</div>';
    echo '</form>';
}
