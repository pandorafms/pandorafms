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
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Setup Management'
    );
    include 'general/noaccess.php';
    return;
}

$action = get_parameter('action', 'new');
$idOS = get_parameter('id_os', 0);
$id_message = get_parameter('message', 0);
if (is_metaconsole() === true) {
    $tab = get_parameter('tab2', 'list');
} else {
    $tab = get_parameter('tab', 'list');
}

if ($idOS) {
    $os = db_get_row_filter('tconfig_os', ['id_os' => $idOS]);
    $name = $os['name'];
    $description = $os['description'];
    $icon = $os['icon_name'];
} else {
    $name = io_safe_input(strip_tags(io_safe_output((string) get_parameter('name'))));
    $description = io_safe_input(strip_tags(io_safe_output((string) get_parameter('description'))));
    $icon = get_parameter('icon', 0);
}

$is_management_allowed = true;
if (is_management_allowed() === false) {
    $is_management_allowed = false;
}

$message = '';
if ($is_management_allowed === true) {
    switch ($action) {
        case 'edit':
            $actionHidden = 'update';
            $textButton = __('Update');
            $classButton = ['icon' => 'wand'];
        break;

        case 'save':
            $values = [];
            $values['name'] = $name;
            $values['description'] = $description;

            if (($icon !== 0) && ($icon != '')) {
                $values['icon_name'] = $icon;
            }

            $resultOrId = false;
            if ($name != '') {
                $resultOrId = db_process_sql_insert('tconfig_os', $values);
            }

            if ($resultOrId === false) {
                $message = 2;
                $tab = 'builder';
                $actionHidden = 'save';
                $textButton = __('Create');
                $classButton = ['icon' => 'wand'];
            } else {
                $tab = 'list';
                $message = 1;
            }

            if (is_metaconsole() === true) {
                header('Location:'.$config['homeurl'].'index.php?sec=advanced&sec2=advanced/component_management&tab=os_manage&tab2='.$tab.'&message='.$message);
            } else {
                header('Location:'.$config['homeurl'].'index.php?sec=gsetup&sec2=godmode/setup/os&tab='.$tab.'&message='.$message);
            }
        break;

        case 'update':
            $name = io_safe_input(strip_tags(io_safe_output((string) get_parameter('name'))));
            $description = io_safe_input(strip_tags(io_safe_output((string) get_parameter('description'))));
            $icon = get_parameter('icon', 0);

            $values = [];
            $values['name'] = $name;
            $values['description'] = $description;
            // Only for Metaconsole. Save the previous name for synchronizing.
            if (is_metaconsole() === true) {
                $values['previous_name'] = db_get_value('name', 'tconfig_os', 'id_os', $idOS);
            }

            if (($icon !== 0) && ($icon != '')) {
                $values['icon_name'] = $icon;
            }

            $result = false;
            if ($name != '') {
                $result = db_process_sql_update('tconfig_os', $values, ['id_os' => $idOS]);
            }

            if ($result !== false) {
                $message = 3;
                $tab = 'list';
            } else {
                $message = 4;
                $tab = 'builder';
                $os = db_get_row_filter('tconfig_os', ['id_os' => $idOS]);
                $name = $os['name'];
            }

            $actionHidden = 'update';
            $textButton = __('Update');
            $classButton = ['icon' => 'wand'];
            if (is_metaconsole() === true) {
                header('Location:'.$config['homeurl'].'index.php?sec=advanced&sec2=advanced/component_management&tab=os_manage&tab2='.$tab.'&message='.$message);
            } else {
                header('Location:'.$config['homeurl'].'index.php?sec=gsetup&sec2=godmode/setup/os&tab='.$tab.'&message='.$message);
            }
        break;

        case 'delete':
            $sql = 'SELECT COUNT(id_os) AS count FROM tagente WHERE id_os = '.$idOS;
            $count = db_get_all_rows_sql($sql);
            $count = $count[0]['count'];

            if ($count > 0) {
                $message = 5;
            } else {
                $result = (bool) db_process_sql_delete('tconfig_os', ['id_os' => $idOS]);
                if ($result) {
                    $message = 6;
                } else {
                    $message = 7;
                }
            }

            if (is_metaconsole() === true) {
                header('Location:'.$config['homeurl'].'index.php?sec=advanced&sec2=advanced/component_management&tab=os_manage&tab2='.$tab.'&message='.$message);
            } else {
                header('Location:'.$config['homeurl'].'index.php?sec=gsetup&sec2=godmode/setup/os&tab='.$tab.'&message='.$message);
            }
        break;

        default:
        case 'new':
            $actionHidden = 'save';
            $textButton = __('Create');
            $classButton = ['icon' => 'next'];
        break;
    }
}

$buttons = [];
$buttons['list'] = [
    'active' => false,
    'text'   => '<a href="index.php?sec=gsetup&sec2=godmode/setup/os&tab=list">'.html_print_image(
        'images/logs@svg.svg',
        true,
        [
            'title' => __('List OS'),
            'class' => 'invert_filter main_menu_icon',
        ]
    ).'</a>',
];
if ($is_management_allowed === true) {
    $buttons['builder'] = [
        'active' => false,
        'text'   => '<a href="index.php?sec=gsetup&sec2=godmode/setup/os&tab=builder">'.html_print_image(
            'images/edit.svg',
            true,
            [
                'title' => __('Builder OS'),
                'class' => 'invert_filter main_menu_icon',
            ]
        ).'</a>',
    ];
}

$buttons[$tab]['active'] = true;

$headerTitle = ($tab === 'builder') ? __('Edit OS') : __('List of Operating Systems');

if (is_metaconsole() === false) {
    // Header.
    ui_print_standard_header(
        $headerTitle,
        '',
        false,
        '',
        true,
        $buttons,
        [
            [
                'link'  => '',
                'label' => __('Servers'),
            ],
            [
                'link'  => '',
                'label' => __('Edit OS'),
            ],
        ]
    );
}

if (empty($id_message) === false) {
    switch ($id_message) {
        case 1:
            echo ui_print_success_message(__('Success creating OS'), '', true);
        break;

        case 2:
            echo ui_print_error_message(__('Fail creating OS'), '', true);
        break;

        case 3:
            echo ui_print_success_message(__('Success updating OS'), '', true);
        break;

        case 4:
            echo ui_print_error_message(__('Error updating OS'), '', true);
        break;

        case 5:
            echo ui_print_error_message(__('There are agents with this OS.'), '', true);
        break;

        case 6:
            echo ui_print_success_message(__('Success deleting'), '', true);
        break;

        case 7:
            echo ui_print_error_message(__('Error deleting'), '', true);
        break;

        default:
            // Default.
        break;
    }
}

switch ($tab) {
    case 'list':
        include_once $config['homedir'].'/godmode/setup/os.list.php';
    break;

    case 'builder':
        include_once $config['homedir'].'/godmode/setup/os.builder.php';
    break;

    default:
        // Default.
    break;
}
