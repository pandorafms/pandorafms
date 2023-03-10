<?php
/**
 * Extension to manage a list of gateways and the node address where they should
 * point to.
 *
 * @category   Users
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

global $config;

check_login();

require_once $config['homedir'].'/include/functions_profile.php';
require_once $config['homedir'].'/include/functions_users.php';
require_once $config['homedir'].'/include/functions_groups.php';
require_once $config['homedir'].'/include/functions_visual_map.php';

$meta = false;
if (enterprise_installed() && defined('METACONSOLE')) {
    $meta = true;
}

$id = get_parameter_get('id', $config['id_user']);
// ID given as parameter.
$status = get_parameter('status', -1);
// Flag to print action status message.
$user_info = get_user_info($id);
$id = $user_info['id_user'];
// This is done in case there are problems
// with uppercase/lowercase (MySQL auth has that problem).
if ((!check_acl($config['id_user'], users_get_groups($id), 'UM'))
    && ($id != $config['id_user'])
) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to view a user without privileges'
    );
    include 'general/noaccess.php';
    exit;
}

// If current user is editing himself or if the user has UM (User Management)
// rights on any groups the user is part of AND the authorization scheme
// allows for users/admins to update info.
if (($config['id_user'] == $id
    || check_acl($config['id_user'], users_get_groups($id), 'UM'))
    && $config['user_can_update_info']
) {
    $view_mode = false;
} else {
    $view_mode = true;
}

$urls = [];
if (is_metaconsole()) {
    user_meta_print_header();
    $urls['main'] = 'index.php?sec=advanced&amp;sec2=advanced/users_setup&amp;tab=user_edit';
} else {
    $urls['main'] = 'index.php?sec=gusuarios&sec2=godmode/users/user_list';
    $urls['notifications'] = 'index.php?sec=workspace&amp;sec2=operation/users/user_edit_notifications';
    $buttons = [
        'main'          => [
            'active' => $_GET['sec2'] === 'godmode/users/user_list&tab=user&pure=0',
            'text'   => "<a href='{$urls['main']}'>".html_print_image(
                'images/user.svg',
                true,
                [
                    'title' => __('User management'),
                    'class' => 'main_menu_icon invert_filter',
                ]
            ).'</a>',
        ],
        'notifications' => [
            'active' => $_GET['sec2'] === 'operation/users/user_edit_notifications',
            'text'   => "<a href='{$urls['notifications']}'>".html_print_image(
                'images/alert@svg.svg',
                true,
                [
                    'title' => __('User notifications'),
                    'class' => 'main_menu_icon invert_filter',
                ]
            ).'</a>',
        ],
    ];
    $tab_name = 'User Management';

    $helpers = '';
    if ($_GET['sec2'] === 'operation/users/user_edit_notifications') {
        $helpers = 'user_edit_notifications';
        $tab_name = 'User Notifications';
    }

    // Header.
    ui_print_standard_header(
        $headerTitle,
        'images/user.png',
        false,
        $helpers,
        false,
        $buttons,
        [
            [
                'link'  => '',
                'label' => __('Workspace'),
            ],
            [
                'link'  => '',
                'label' => __('Edit user'),
            ],
        ]
    );
}
