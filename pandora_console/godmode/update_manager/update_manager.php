<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
global $config;

check_login();
// The ajax is in
// include/ajax/update_manager.ajax.php
if (! check_acl($config['id_user'], 0, 'PM') && ! is_user_admin($config['id_user'])) {
    db_pandora_audit('ACL Violation', 'Trying to access Setup Management');
    include 'general/noaccess.php';
    return;
}

$php_version = phpversion();
$php_version_array = explode('.', $php_version);
if ($php_version_array[0] < 7) {
    include_once 'general/php7_message.php';
}

$tab = get_parameter('tab', 'online');

$buttons['setup'] = [
    'active' => ($tab == 'setup') ? true : false,
    'text'   => '<a href="index.php?sec=gsetup&sec2=godmode/update_manager/update_manager&tab=setup">'.html_print_image('images/gm_setup.png', true, ['title' => __('Options')]).'</a>',
];

if (enterprise_installed()) {
    $buttons['offline'] = [
        'active' => ($tab == 'offline') ? true : false,
        'text'   => '<a href="index.php?sec=gsetup&sec2=godmode/update_manager/update_manager&tab=offline">'.html_print_image('images/box.png', true, ['title' => __('Offline update manager')]).'</a>',
    ];
}

$buttons['online'] = [
    'active' => ($tab == 'online') ? true : false,
    'text'   => '<a href="index.php?sec=gsetup&sec2=godmode/update_manager/update_manager&tab=online">'.html_print_image('images/op_gis.png', true, ['title' => __('Online update manager')]).'</a>',
];


switch ($tab) {
    case 'setup':
        $title = __('Update manager » Setup');
    break;

    case 'offline':
        $title = __('Update manager » Offline');
        $help_header = 'update_manager_offline_tab';
    break;

    case 'online':
        $title = __('Update manager » Online');
        $help_header = 'update_manager_online_tab';
    break;
}

ui_print_page_header(
    $title,
    'images/gm_setup.png',
    false,
    $help_header,
    true,
    $buttons
);

switch ($tab) {
    case 'setup':
        include $config['homedir'].'/godmode/update_manager/update_manager.setup.php';
    break;

    case 'offline':
        include $config['homedir'].'/godmode/update_manager/update_manager.offline.php';
    break;

    case 'online':
    default:
        include $config['homedir'].'/godmode/update_manager/update_manager.online.php';
    break;
}
