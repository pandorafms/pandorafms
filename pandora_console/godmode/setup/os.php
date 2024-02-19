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

check_login();

if (! check_acl($config['id_user'], 0, 'PM') && ! is_user_admin($config['id_user'])) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Setup Management'
    );
    include 'general/noaccess.php';
    return;
}

$action = get_parameter('action', '');
$idOS = get_parameter('id_os', 0);
$id_message = get_parameter('message', 0);
if (is_metaconsole() === true) {
    $tab = get_parameter('tab2', 'list');
} else {
    $tab = get_parameter('tab', 'manage_os');
}

$is_management_allowed = true;
if (is_management_allowed() === false) {
    $is_management_allowed = false;
}

$buttons = [];

$buttons['manage_os'] = [
    'active' => false,
    'text'   => '<a href="index.php?sec=gsetup&sec2=godmode/setup/os&tab=manage_os">'.html_print_image(
        'images/os@svg.svg',
        true,
        [
            'title' => __('Manage OS types'),
            'class' => 'invert_filter main_menu_icon',
        ]
    ).'</a>',
];

$buttons['manage_version'] = [
    'active' => false,
    'text'   => '<a href="index.php?sec=gsetup&sec2=godmode/setup/os&tab=manage_version">'.html_print_image(
        'images/os_version@svg.svg',
        true,
        [
            'title' => __('Manage version expiration dates'),
            'class' => 'invert_filter main_menu_icon',
        ]
    ).'</a>',
];

$buttons[$tab]['active'] = true;

switch ($tab) {
    case 'builder':
        $headerTitle = __('Edit OS');
    break;

    case 'manage_os':
        $id_os = get_parameter('id_os', '');
        if ($id_os !== '') {
            $headerTitle = __('Edit OS');
        } else {
            $headerTitle = __('Create OS');
        }
    break;

    case 'list':
        if ($action === 'edit') {
            $headerTitle = __('Edit OS');
        } else {
            $headerTitle = __('List of Operating Systems');
        }
    break;

    case 'manage_version':
        if ($action === 'edit') {
            $headerTitle = __('Edit OS version expiration date');
        } else {
            $headerTitle = __('List of version expiration dates');
        }
    break;

    default:
        // Default.
    break;
}

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

        case 8:
            header('Location: index.php?sec=gagente&sec2=godmode/setup/os&tab=manage_os&action=edit&id_message=8');
        break;

        case 9:
            header('Location: index.php?sec=gagente&sec2=godmode/setup/os&tab=manage_os&action=edit&id_message=9');
        break;

        case 10:
            header('Location: index.php?sec=gagente&sec2=godmode/setup/os&tab=manage_os&action=edit&id_message=10');
        break;

        default:
            // Default.
        break;
    }
}

switch ($tab) {
    case 'manage_os':
    case 'list':
        if (in_array($action, ['edit', 'save', 'update']) && is_management_allowed() === true) {
            include_once $config['homedir'].'/godmode/setup/os.builder.php';
        } else {
            include_once $config['homedir'].'/godmode/setup/os.list.php';
        }
    break;

    case 'manage_version':
        if (in_array($action, ['edit', 'save', 'update']) && is_management_allowed() === true) {
            include_once $config['homedir'].'/godmode/setup/os_version.builder.php';
        } else {
            include_once $config['homedir'].'/godmode/setup/os_version.list.php';
        }
    break;

    default:
        // Default.
    break;
}
