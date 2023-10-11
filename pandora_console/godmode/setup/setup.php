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
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Setup Management'
    );
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
    'text'   => '<a href="'.ui_get_full_url('index.php?sec=gsetup&sec2=godmode/setup/setup&amp;section=general').'">'.html_print_image(
        'images/setup.png',
        true,
        [
            'title' => __('General'),
            'class' => 'invert_filter',

        ]
    ).'</a>',
];

if (enterprise_installed()) {
    $buttons = setup_enterprise_add_Tabs($buttons);
}

$buttons['auth'] = [
    'active' => false,
    'text'   => '<a href="'.ui_get_full_url('index.php?sec=gsetup&sec2=godmode/setup/setup&amp;section=auth').'">'.html_print_image(
        'images/key.png',
        true,
        [
            'title' => __('Authentication'),
            'class' => 'invert_filter',
        ]
    ).'</a>',
];

$buttons['perf'] = [
    'active' => false,
    'text'   => '<a href="'.ui_get_full_url('index.php?sec=gsetup&sec2=godmode/setup/setup&amp;section=perf').'">'.html_print_image(
        'images/performance.png',
        true,
        [
            'title' => __('Performance'),
            'class' => 'invert_filter',
        ]
    ).'</a>',
];

$buttons['vis'] = [
    'active' => false,
    'text'   => '<a href="'.ui_get_full_url('index.php?sec=gsetup&sec2=godmode/setup/setup&amp;section=vis').'">'.html_print_image(
        'images/chart.png',
        true,
        [
            'title' => __('Visual styles'),
            'class' => 'invert_filter',
        ]
    ).'</a>',
];

if (check_acl($config['id_user'], 0, 'AW')) {
    if ($config['activate_netflow']) {
        $buttons['net'] = [
            'active' => false,
            'text'   => '<a href="'.ui_get_full_url('index.php?sec=gsetup&sec2=godmode/setup/setup&amp;section=net').'">'.html_print_image(
                'images/op_netflow.png',
                true,
                [
                    'title' => __('Netflow'),
                    'class' => 'invert_filter',
                ]
            ).'</a>',
        ];
    }

    if ($config['activate_sflow']) {
        $buttons['sflow'] = [
            'active' => false,
            'text'   => '<a href="'.ui_get_full_url('index.php?sec=gsetup&sec2=godmode/setup/setup&amp;section=sflow').'">'.html_print_image(
                'images/op_recon.png',
                true,
                [
                    'title' => __('Sflow'),
                    'class' => 'invert_filter',
                ]
            ).'</a>',
        ];
    }
}

$buttons['ITSM'] = [
    'active' => false,
    'text'   => '<a href="'.ui_get_full_url('index.php?sec=gsetup&sec2=godmode/setup/setup&section=ITSM').'">'.html_print_image(
        'images/itsm.png',
        true,
        [
            'title' => __('ITSM'),
            'class' => 'invert_filter',
        ]
    ).'</a>',
];

$buttons['ehorus'] = [
    'active' => false,
    'text'   => '<a href="'.ui_get_full_url('index.php?sec=gsetup&sec2=godmode/setup/setup&section=ehorus').'">'.html_print_image(
        'images/RC.png',
        true,
        [
            'title' => __('Pandora RC'),
            'class' => 'invert_filter',
        ]
    ).'</a>',
];

if (check_acl($config['id_user'], 0, 'PM') && enterprise_installed()) {
    $buttons['module_library'] = [
        'active' => false,
        'text'   => '<a href="'.ui_get_full_url('index.php?sec=gsetup&sec2=godmode/setup/setup&section=module_library').'">'.html_print_image(
            'images/library.png',
            true,
            [
                'title' => __('Module Library'),
                'class' => 'invert_filter',
            ]
        ).'</a>',
    ];
}

// FIXME: Not definitive icon
$buttons['notifications'] = [
    'active' => false,
    'text'   => '<a href="'.ui_get_full_url('index.php?sec=gsetup&sec2=godmode/setup/setup&section=notifications').'">'.html_print_image(
        'images/alerts_template.png',
        true,
        [
            'title' => __('Notifications'),
            'class' => 'invert_filter',
        ]
    ).'</a>',
];

$buttons['quickshell'] = [
    'active' => false,
    'text'   => '<a href="'.ui_get_full_url('index.php?sec=gsetup&sec2=godmode/setup/setup&section=quickshell').'">'.html_print_image(
        'images/websocket_small.png',
        true,
        [
            'title' => __('QuickShell'),
            'class' => 'invert_filter',
        ]
    ).'</a>',
];

$buttons['external_tools'] = [
    'active' => false,
    'text'   => '<a href="'.ui_get_full_url('index.php?sec=gsetup&sec2=godmode/setup/setup&section=external_tools').'">'.html_print_image('images/nettool.png', true, ['title' => __('External Tools'), 'class' => 'invert_filter']).'</a>',
];

$buttons['welcome_tips'] = [
    'active' => false,
    'text'   => '<a href="'.ui_get_full_url('index.php?sec=gsetup&sec2=godmode/setup/setup&section=welcome_tips').'">'.html_print_image('images/inventory.png', true, ['title' => __('Welcome tips'), 'class' => 'invert_filter']).'</a>',
];

if ($config['activate_gis']) {
    $buttons['gis'] = [
        'active' => false,
        'text'   => '<a href="'.ui_get_full_url('index.php?sec=general&sec2=godmode/setup/setup&section=gis').'">'.html_print_image(
            'images/gis_tab.png',
            true,
            [
                'title' => __('GIS Map connection'),
                'class' => 'invert_filter',
            ]
        ).'</a>',
    ];
}

$help_header = '';
if (enterprise_installed()) {
    $subpage = setup_enterprise_add_subsection_main($section, $buttons, $help_header);
}

switch ($section) {
    case 'general':
        $buttons['general']['active'] = true;
        $subpage = __('General');
        $help_header = 'setup_general_tab';
    break;

    case 'auth':
        $buttons['auth']['active'] = true;
        $subpage = __('Authentication');
    break;

    case 'perf':
        $buttons['perf']['active'] = true;
        $subpage = __('Performance');
        $help_header = '';
    break;

    case 'vis':
        $buttons['vis']['active'] = true;
        $subpage = __('Visual styles');
    break;

    case 'net':
        $buttons['net']['active'] = true;
        $subpage = __('Netflow');
        $help_header = 'setup_netflow_tab';
    break;

    case 'sflow':
        $buttons['sflow']['active'] = true;
        $subpage = __('Sflow');
        $help_header = 'setup_flow_tab';
    break;

    case 'ehorus':
        $buttons['ehorus']['active'] = true;
        $subpage = __('Pandora RC');
        $help_header = 'setup_ehorus_tab';
    break;

    case 'ITSM':
        $buttons['ITSM']['active'] = true;
        $subpage = __('Pandora ITSM');
        $help_header = 'setup_ITSM_tab';
    break;

    case 'module_library':
        $buttons['module_library']['active'] = true;
        $subpage = __('Module Library');
        $help_header = 'setup_module_library_tab';
    break;

    case 'gis':
        $buttons['gis']['active'] = true;
        $subpage = __('Map conections GIS');
    break;

    case 'notifications':
        $buttons['notifications']['active'] = true;
        $subpage = __('Notifications');
    break;

    case 'quickshell':
        $buttons['quickshell']['active'] = true;
        $subpage = __('QuickShell');
        $help_header = 'quickshell_settings';
    break;

    case 'external_tools':
        $buttons['external_tools']['active'] = true;
        $subpage = __('External Tools');
        $help_header = '';
    break;

    case 'welcome_tips':
        $view = get_parameter('view', '');
        $title = __('Welcome tips');
        if ($view === 'create') {
            $title = __('Create tip');
        } else if ($view === 'edit') {
            $title = __('Edit tip');
        }

        $buttons['welcome_tips']['active'] = true;
        $subpage = $title;
        $help_header = '';
    break;

    case 'enterprise':
        $buttons['enterprise']['active'] = true;
        $subpage = __('Enterprise');
        $help_header = 'setup_enterprise_tab';
    break;

    case 'hist_db':
        $buttons['hist_db']['active'] = true;
        $subpage = __('Historical database');
        $help_header = '';
    break;

    case 'pass':
        $buttons['pass']['active'] = true;
        $subpage = __('Password policies');
        $help_header = '';
    break;

    default:
        $subpage = 'seccion: '.$section;
        // Default.
    break;
}

// Header.
ui_print_standard_header(
    $subpage,
    '',
    false,
    $help_header,
    true,
    $buttons,
    [
        [
            'link'  => '',
            'label' => __('Setup'),
        ],
    ]
);

if (isset($config['error_config_update_config'])) {
    if ($config['error_config_update_config']['correct'] == false) {
        ui_print_error_message($config['error_config_update_config']['message']);
    } else {
        ui_print_success_message(__('Correct update the setup options'));
    }

    if (is_array($config['error_config_update_config']['errors']) === true) {
        foreach ($config['error_config_update_config']['errors'] as $msg) {
            ui_print_error_message($msg);
        }
    }

    if (is_array($config['error_config_update_config']['warnings']) === true) {
        foreach ($config['error_config_update_config']['warnings'] as $msg) {
            ui_print_warning_message($msg);
        }
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

    case 'sflow':
        include_once $config['homedir'].'/godmode/setup/setup_sflow.php';
    break;

    case 'vis':
        include_once $config['homedir'].'/godmode/setup/setup_visuals.php';
    break;

    case 'ehorus':
        include_once $config['homedir'].'/godmode/setup/setup_ehorus.php';
    break;

    case 'ITSM':
        include_once $config['homedir'].'/godmode/setup/setup_ITSM.php';
    break;

    case 'gis':
        include_once $config['homedir'].'/godmode/setup/gis.php';
    break;

    case 'notifications':
        include_once $config['homedir'].'/godmode/setup/setup_notifications.php';
    break;

    case 'quickshell':
        include_once $config['homedir'].'/godmode/setup/setup_quickshell.php';
    break;

    case 'external_tools':
        include_once $config['homedir'].'/godmode/setup/setup_external_tools.php';
    break;

    case 'welcome_tips':
        include_once $config['homedir'].'/godmode/setup/welcome_tips.php';
    break;

    default:
        enterprise_hook('setup_enterprise_select_tab', [$section]);
    break;
}
