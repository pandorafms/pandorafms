<?php
/**
 * Event configuration.
 *
 * @category   Events
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



if (!check_acl($config['id_user'], 0, 'EW') && !check_acl($config['id_user'], 0, 'EM') && ! check_acl($config['id_user'], 0, 'PM')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access event manage'
    );
    include 'general/noaccess.php';
    return;
}

// Gets section to jump to another section
$section = (string) get_parameter('section', 'filter');

// Draws header
if (check_acl($config['id_user'], 0, 'EW') || check_acl($config['id_user'], 0, 'EM')) {
    $buttons['view'] = [
        'active'    => false,
        'text'      => '<a href="index.php?sec=eventos&sec2=operation/events/events&amp;pure='.$config['pure'].'">'.html_print_image(
            'images/event.svg',
            true,
            [
                'title' => __('Event list'),
                'class' => 'invert_filter main_menu_icon',
            ]
        ).'</a>',
        'operation' => true,
    ];

    $buttons['filter'] = [
        'active' => false,
        'text'   => '<a href="index.php?sec=eventos&sec2=godmode/events/events&amp;section=filter&amp;pure='.$config['pure'].'">'.html_print_image(
            'images/filters@svg.svg',
            true,
            [
                'title' => __('Filter list'),
                'class' => 'invert_filter main_menu_icon',
            ]
        ).'</a>',
    ];
}

if (check_acl($config['id_user'], 0, 'PM')) {
    $buttons['responses'] = [
        'active' => false,
        'text'   => '<a href="index.php?sec=eventos&sec2=godmode/events/events&amp;section=responses&amp;pure='.$config['pure'].'">'.html_print_image(
            'images/responses.svg',
            true,
            [
                'title' => __('Event responses'),
                'class' => 'invert_filter main_menu_icon',
            ]
        ).'</a>',
    ];

    $buttons['fields'] = [
        'active' => false,
        'text'   => '<a href="index.php?sec=eventos&sec2=godmode/events/events&amp;section=fields&amp;pure='.$config['pure'].'">'.html_print_image(
            'images/edit_columns@svg.svg',
            true,
            [
                'title' => __('Custom columns'),
                'class' => 'invert_filter main_menu_icon',
            ]
        ).'</a>',
    ];
}

switch ($section) {
    case 'filter':
        $buttons['filter']['active'] = true;
        $subpage = __('Filters');
    break;

    case 'fields':
        $buttons['fields']['active'] = true;
        $subpage = __('Custom columns');
    break;

    case 'responses':
        $buttons['responses']['active'] = true;
        $subpage = __('Responses');
    break;

    case 'view':
        $buttons['view']['active'] = true;
    break;

    default:
        $buttons['filter']['active'] = true;
        $subpage = __('Filters');
    break;
}

ui_print_standard_header(
    $subpage,
    'images/gm_events.png',
    false,
    '',
    true,
    (array) $buttons,
    [
        [
            'link'  => '',
            'label' => __('Configuration'),
        ],
        [
            'link'  => '',
            'label' => __('Events'),
        ],
    ]
);


require_once $config['homedir'].'/include/functions_events.php';


switch ($section) {
    case 'edit_filter':
        include_once $config['homedir'].'/godmode/events/event_edit_filter.php';
    break;

    case 'filter':
        include_once $config['homedir'].'/godmode/events/event_filter.php';
    break;

    case 'fields':
        include_once $config['homedir'].'/godmode/events/custom_events.php';
    break;

    case 'responses':
        include_once $config['homedir'].'/godmode/events/event_responses.php';
    break;
}
