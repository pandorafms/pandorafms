<?php
/**
 * Favorite visual console.
 *
 * @category   Topology maps
 * @package    Pandora FMS
 * @subpackage Visual consoles
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2007-2023 Artica Soluciones Tecnologicas
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

// Begin.
global $config;

require_once $config['homedir'].'/include/functions_visual_map.php';
// Breadcrumb.
require_once $config['homedir'].'/include/class/HTML.class.php';
ui_require_css_file('discovery');
// ACL for the general permission.
$vconsoles_read   = (bool) check_acl($config['id_user'], 0, 'VR');
$vconsoles_write  = (bool) check_acl($config['id_user'], 0, 'VW');
$vconsoles_manage = (bool) check_acl($config['id_user'], 0, 'VM');

$is_enterprise = enterprise_include_once('include/functions_policies.php');

if ($vconsoles_read === false && $vconsoles_write === false && $vconsoles_manage === false) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access map builder'
    );
    include 'general/noaccess.php';
    exit;
}

if (is_metaconsole() === false) {
    $url_visual_console                 = 'index.php?sec=network&sec2=godmode/reporting/map_builder';
    $url_visual_console_favorite        = 'index.php?sec=network&sec2=godmode/reporting/visual_console_favorite';
    $url_visual_console_template        = 'index.php?sec=network&sec2=enterprise/godmode/reporting/visual_console_template';
    $url_visual_console_template_wizard = 'index.php?sec=network&sec2=enterprise/godmode/reporting/visual_console_template_wizard';
} else {
    $url_visual_console                 = 'index.php?sec=screen&sec2=screens/screens&action=visualmap';
    $url_visual_console_favorite        = 'index.php?sec=screen&sec2=screens/screens&action=visualmap_favorite';
    $url_visual_console_template        = 'index.php?sec=screen&sec2=screens/screens&action=visualmap_template';
    $url_visual_console_template_wizard = 'index.php?sec=screen&sec2=screens/screens&action=visualmap_wizard';
}

$buttons = [];

$buttons['visual_console'] = [
    'active' => false,
    'text'   => '<a href="'.$url_visual_console.'">'.html_print_image(
        'images/logs@svg.svg',
        true,
        [
            'title' => __('Visual Console List'),
            'class' => 'main_menu_icon invert_filter',
        ]
    ).'</a>',
];

$buttons['visual_console_favorite'] = [
    'active' => true,
    'text'   => '<a href="'.$url_visual_console_favorite.'">'.html_print_image(
        'images/star@svg.svg',
        true,
        [
            'title' => __('Visual Favourite Console'),
            'class' => 'main_menu_icon invert_filter',
        ]
    ).'</a>',
];

if ($is_enterprise !== ENTERPRISE_NOT_HOOK && $vconsoles_manage) {
    $buttons['visual_console_template'] = [
        'active' => false,
        'text'   => '<a href="'.$url_visual_console_template.'">'.html_print_image(
            'images/groups@svg.svg',
            true,
            [
                'title' => __('Visual Console Template'),
                'class' => 'main_menu_icon invert_filter',
            ]
        ).'</a>',
    ];

    $buttons['visual_console_template_wizard'] = [
        'active' => false,
        'text'   => '<a href="'.$url_visual_console_template_wizard.'">'.html_print_image(
            'images/wizard@svg.svg',
            true,
            [
                'title' => __('Visual Console Template Wizard'),
                'class' => 'main_menu_icon invert_filter',
            ]
        ).'</a>',
    ];
}

ui_print_standard_header(
    __('Favourite Visual Console'),
    'images/op_reporting.png',
    false,
    '',
    true,
    $buttons,
    [
        [
            'link'  => '',
            'label' => __('Topology maps'),
        ],
        [
            'link'  => '',
            'label' => __('Visual console'),
        ],
    ]
);

$search    = (string) get_parameter('search', '');
$ag_group  = (int) get_parameter('ag_group', 0);
$recursion = (int) get_parameter('recursion', 0);

$returnAllGroups = 0;
$filters = [];
if (empty($search) === false) {
    $filters['name'] = io_safe_input($search);
}

if ($ag_group > 0) {
    $ag_groups = [];
    $ag_groups = (array) $ag_group;
    if ($recursion) {
        $ag_groups = groups_get_children_ids($ag_group, true);
    }
} else if ($own_info['is_admin']) {
    $returnAllGroups = 1;
}

if ($ag_group) {
    $filters['group'] = array_flip($ag_groups);
}

$own_info = get_user_info($config['id_user']);
if (!$own_info['is_admin'] && !check_acl($config['id_user'], 0, 'AW')) {
    $return_all_group = false;
} else {
    $return_all_group = true;
}

$filterTable = new stdClass();
$filterTable->id = 'visual_console_favorite_filter';
$filterTable->class = 'filter-table-adv';
$filterTable->width = '100%';
$filterTable->size = [];
$filterTable->size[0] = '33%';
$filterTable->size[1] = '33%';

$filterTable->data = [];

$filterTable->data[0][] = html_print_label_input_block(
    __('Search'),
    html_print_input_text('search', $search, '', 50, 255, true)
);

$filterTable->data[0][] = html_print_label_input_block(
    __('Group'),
    html_print_select_groups(false, 'AR', $return_all_group, 'ag_group', $ag_group, '', '', 0, true, false, true, '', false)
);

$filterTable->data[0][] = html_print_label_input_block(
    __('Group Recursion'),
    html_print_checkbox_switch('recursion', 1, $recursion, true, false, '')
);

if (is_metaconsole() === false) {
    $actionUrl = 'index.php?sec=network&amp;sec2=godmode/reporting/visual_console_favorite';
} else {
    $actionUrl = 'index.php?sec=screen&sec2=screens/screens&action=visualmap_favorite';
}

// exit;
$searchForm = '<form method="POST" action="'.$actionUrl.'">';
$searchForm .= html_print_table($filterTable, true);
$searchForm .= html_print_div(
    [
        'class'   => 'action-buttons',
        'content' => html_print_submit_button(
            __('Filter'),
            'search_visual_console',
            false,
            [
                'icon' => 'search',
                'mode' => 'mini',
            ],
            true
        ),
    ],
    true
);
$searchForm .= '</form>';

ui_toggle(
    $searchForm,
    '<span class="subsection_header_title">'.__('Filters').'</span>',
    'filter_form',
    '',
    true,
    false,
    '',
    'white-box-content',
    'box-flat white_table_graph fixed_filter_bar'
);

$favorite_array = visual_map_get_user_layouts(
    $config['id_user'],
    false,
    $filters,
    $returnAllGroups,
    true
);

echo "<div id='is_favourite'>";
if ($favorite_array == false) {
    ui_print_info_message(__('No favourite consoles defined'));
} else {
    echo "<ul class='container'>";
    foreach ($favorite_array as $favorite_k => $favourite_v) {
        if (is_metaconsole() === true) {
            $url = 'index.php?sec=screen&sec2=screens/screens&action=visualmap&pure=0&id='.$favourite_v['id'];
        } else {
            $url = 'index.php?sec=network&sec2=operation/visual_console/render_view&id='.$favourite_v['id'];
        }

        echo "<a href='".$url."' title='Visual console".$favourite_v['name']."' alt='".$favourite_v['name']."'><li>";
        echo "<div class='icon_img'>";
            echo html_print_image(
                'images/'.groups_get_icon($favourite_v['id_group']),
                true,
                ['style' => '']
            );
            echo '</div>';
            echo "<div class='text'>";
            echo $favourite_v['name'];
            echo '</div>';
        echo '</li></a>';
    }

    echo '</ul>';
}

echo '</div>';
