<?php
/**
 * Extension to manage a list of gateways and the node address where they should
 * point to.
 *
 * @category   Extensions
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

global $config;

check_login();

if (is_ajax()) {
    $get_os_icon = (bool) get_parameter('get_os_icon');
    $select_timezone = get_parameter('select_timezone', 0);

    if ($get_os_icon) {
        $id_os = (int) get_parameter('id_os');
        ui_print_os_icon($id_os, false);
        return;
    }

    if ($select_timezone) {
        $zone = get_parameter('zone');

        $timezones = timezone_identifiers_list();
        foreach ($timezones as $timezone_key => $timezone) {
            if (strpos($timezone, $zone) === false) {
                unset($timezones[$timezone_key]);
            }
        }

        echo json_encode($timezones);
    }

    return;
}


if (! check_acl($config['id_user'], 0, 'PM') && ! is_user_admin($config['id_user'])) {
    db_pandora_audit('ACL Violation', 'Trying to access Setup Management');
    include 'general/noaccess.php';
    return;
}

// Load enterprise extensions.
enterprise_include_once('include/functions_setup.php');
enterprise_include_once('include/functions_io.php');
enterprise_include_once('godmode/setup/setup.php');

/*
    NOTICE FOR DEVELOPERS:

    Update operation is done in config_process.php
    This is done in that way so the user can see the changes inmediatly.
    If you added a new token, please check config_update_config() in functions_config.php
    to add it there.
*/

// Gets section to jump to another section.
$section = (string) get_parameter('section', 'general');

$buttons = [];

// Draws header.
$buttons['general'] = [
    'active' => false,
    'text'   => '<a href="'.ui_get_full_url('index.php?sec=gsetup&sec2=godmode/setup/setup&amp;section=general').'">'.html_print_image('images/gm_setup.png', true, ['title' => __('General')]).'</a>',
];

if (enterprise_installed()) {
    $buttons = setup_enterprise_add_Tabs($buttons);
}

$buttons['auth'] = [
    'active' => false,
    'text'   => '<a href="'.ui_get_full_url('index.php?sec=gsetup&sec2=godmode/setup/setup&amp;section=auth').'">'.html_print_image('images/key.png', true, ['title' => __('Authentication')]).'</a>',
];

$buttons['perf'] = [
    'active' => false,
    'text'   => '<a href="'.ui_get_full_url('index.php?sec=gsetup&sec2=godmode/setup/setup&amp;section=perf').'">'.html_print_image('images/performance.png', true, ['title' => __('Performance')]).'</a>',
];

$buttons['vis'] = [
    'active' => false,
    'text'   => '<a href="'.ui_get_full_url('index.php?sec=gsetup&sec2=godmode/setup/setup&amp;section=vis').'">'.html_print_image('images/chart.png', true, ['title' => __('Visual styles')]).'</a>',
];

if (check_acl($config['id_user'], 0, 'AW')) {
    if ($config['activate_netflow']) {
        $buttons['net'] = [
            'active' => false,
            'text'   => '<a href="'.ui_get_full_url('index.php?sec=gsetup&sec2=godmode/setup/setup&amp;section=net').'">'.html_print_image('images/op_netflow.png', true, ['title' => __('Netflow')]).'</a>',
        ];
    }
}

$buttons['integria'] = [
    'active' => false,
    'text'   => '<a href="'.ui_get_full_url('index.php?sec=gsetup&sec2=godmode/setup/setup&section=integria').'">'.html_print_image('images/integria.png', true, ['title' => __('Integria IMS')]).'</a>',
];

$buttons['ehorus'] = [
    'active' => false,
    'text'   => '<a href="'.ui_get_full_url('index.php?sec=gsetup&sec2=godmode/setup/setup&section=ehorus').'">'.html_print_image('images/ehorus/ehorus.png', true, ['title' => __('eHorus')]).'</a>',
];

// FIXME: Not definitive icon
$buttons['notifications'] = [
    'active' => false,
    'text'   => '<a href="'.ui_get_full_url('index.php?sec=gsetup&sec2=godmode/setup/setup&section=notifications').'">'.html_print_image('images/alerts_template.png', true, ['title' => __('Notifications')]).'</a>',
];

$buttons['websocket_engine'] = [
    'active' => false,
    'text'   => '<a href="'.ui_get_full_url('index.php?sec=gsetup&sec2=godmode/setup/setup&section=websocket_engine').'">'.html_print_image('images/websocket_small.png', true, ['title' => __('Websocket engine')]).'</a>',
];

$help_header = '';
if (enterprise_installed()) {
    $subpage = setup_enterprise_add_subsection_main($section, $buttons, $help_header);
}

switch ($section) {
    case 'general':
        $buttons['general']['active'] = true;
        $subpage = ' &raquo '.__('General');
        $help_header = 'setup_general_tab';
    break;

    case 'auth':
        $buttons['auth']['active'] = true;
        $subpage = ' &raquo '.__('Authentication');
    break;

    case 'perf':
        $buttons['perf']['active'] = true;
        $subpage = ' &raquo '.__('Performance');
        $help_header = '';
    break;

    case 'vis':
        $buttons['vis']['active'] = true;
        $subpage = ' &raquo '.__('Visual styles');
    break;

    case 'net':
        $buttons['net']['active'] = true;
        $subpage = ' &raquo '.__('Netflow');
    break;

    case 'ehorus':
        $buttons['ehorus']['active'] = true;
        $subpage = ' &raquo '.__('eHorus');
        $help_header = 'setup_ehorus_tab';
    break;

    case 'integria':
        $buttons['integria']['active'] = true;
        $subpage = ' &raquo '.__('Integria IMS');
        $help_header = 'setup_integria_tab';
    break;

    case 'notifications':
        $buttons['notifications']['active'] = true;
        $subpage = ' &raquo '.__('Notifications');
    break;

    case 'websocket_engine':
        $buttons['websocket_engine']['active'] = true;
        $subpage = ' &raquo '.__('Pandora Websocket Engine');
        $help_header = 'quickshell_settings';
    break;

    case 'enterprise':
        $buttons['enterprise']['active'] = true;
        $subpage = ' &raquo '.__('Enterprise');
        $help_header = 'setup_enterprise_tab';
    break;

    default:
        // Default.
    break;
}

// Header.
ui_print_page_header(
    __('Configuration').$subpage,
    '',
    false,
    $help_header,
    true,
    $buttons
);

if (isset($config['error_config_update_config'])) {
    if ($config['error_config_update_config']['correct'] == false) {
        ui_print_error_message($config['error_config_update_config']['message']);
    } else {
        ui_print_success_message(__('Correct update the setup options'));
    }

    unset($config['error_config_update_config']);
}

switch ($section) {
    case 'general':
        include_once $config['homedir'].'/godmode/setup/setup_general.php';
    break;

    case 'auth':
        include_once $config['homedir'].'/godmode/setup/setup_auth.php';
    break;

    case 'perf':
        include_once $config['homedir'].'/godmode/setup/performance.php';
    break;

    case 'net':
        include_once $config['homedir'].'/godmode/setup/setup_netflow.php';
    break;

    case 'vis':
        include_once $config['homedir'].'/godmode/setup/setup_visuals.php';
    break;

    case 'ehorus':
        include_once $config['homedir'].'/godmode/setup/setup_ehorus.php';
    break;

    case 'integria':
        include_once $config['homedir'].'/godmode/setup/setup_integria.php';
    break;

    case 'notifications':
        include_once $config['homedir'].'/godmode/setup/setup_notifications.php';
    break;

    case 'websocket_engine':
        include_once $config['homedir'].'/godmode/setup/setup_websocket_engine.php';
    break;

    default:
        enterprise_hook('setup_enterprise_select_tab', [$section]);
    break;
}
