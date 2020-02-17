<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2018 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
global $config;

require_once $config['homedir'].'/include/functions_visual_map.php';

// ACL for the general permission
$vconsoles_read   = check_acl($config['id_user'], 0, 'VR');
$vconsoles_write  = check_acl($config['id_user'], 0, 'VW');
$vconsoles_manage = check_acl($config['id_user'], 0, 'VM');

$is_enterprise = enterprise_include_once('include/functions_policies.php');
$is_metaconsole = is_metaconsole();

if (!$vconsoles_read && !$vconsoles_write && !$vconsoles_manage) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access map builder'
    );
    include 'general/noaccess.php';
    exit;
}

if (!$is_metaconsole) {
    $url_visual_console                 = 'index.php?sec=network&sec2=godmode/reporting/map_builder';
    $url_visual_console_favorite        = 'index.php?sec=network&sec2=godmode/reporting/visual_console_favorite';
    $url_visual_console_template        = 'index.php?sec=network&sec2=enterprise/godmode/reporting/visual_console_template';
    $url_visual_console_template_wizard = 'index.php?sec=network&sec2=enterprise/godmode/reporting/visual_console_template_wizard';
} else {
    $url_visual_console                 = 'index.php?sec=screen&sec2=screens/screens&action=visualmap';
    $url_visual_console_favorite        = 'index.php?sec=screen&sec2=screens/screens&action=visualmap_favorite';
    $url_visual_console_template        = 'index.php?sec=screen&sec2=screens/screens&action=visualmap_template';
    $url_visual_console_template_wizard = 'index.php?sec=screen&sec2=screens/screens&action=visualmap_wizard';
    $url_visual_console_manager         = 'index.php?sec=screen&sec2=enterprise/extensions/visual_console_manager';
}

$buttons['visual_console'] = [
    'active' => false,
    'text'   => '<a href="'.$url_visual_console.'">'.html_print_image('images/visual_console.png', true, ['title' => __('Visual Console List')]).'</a>',
];

$buttons['visual_console_favorite'] = [
    'active' => true,
    'text'   => '<a href="'.$url_visual_console_favorite.'">'.html_print_image('images/list.png', true, ['title' => __('Visual Favourite Console')]).'</a>',
];

if ($is_enterprise !== ENTERPRISE_NOT_HOOK && $vconsoles_manage) {
    $buttons['visual_console_template'] = [
        'active' => false,
        'text'   => '<a href="'.$url_visual_console_template.'">'.html_print_image('images/templates.png', true, ['title' => __('Visual Console Template')]).'</a>',
    ];

    $buttons['visual_console_template_wizard'] = [
        'active' => false,
        'text'   => '<a href="'.$url_visual_console_template_wizard.'">'.html_print_image('images/wand.png', true, ['title' => __('Visual Console Template Wizard')]).'</a>',
    ];

    if ($is_metaconsole) {
        $buttons['visual_console_manager'] = [
            'active' => false,
            'text'   => '<a href="'.$url_visual_console_manager.'">'.html_print_image('images/builder.png', true, ['title' => __('Visual Console Manager')]).'</a>',
        ];
    }
}

if (!$is_metaconsole) {
    ui_print_page_header(
        __('Reporting').' &raquo; '.__('Visual Favourite Console'),
        'images/op_reporting.png',
        false,
        'map_builder_favorite',
        false,
        $buttons
    );
} else {
    ui_meta_print_header(
        __('Visual console').' &raquo; '.$visualConsoleName,
        '',
        $buttons
    );
}

$search    = (string) get_parameter('search', '');
$ag_group  = (int) get_parameter('ag_group', 0);
$recursion = (int) get_parameter('recursion', 0);


if (!is_metaconsole()) {
    echo "<form method='post'
		action='index.php?sec=network&amp;sec2=godmode/reporting/visual_console_favorite'>";
} else {
    echo "<form method='post'
		action='index.php?sec=screen&sec2=screens/screens&action=visualmap_favorite'>";
}

    echo "<ul class='form_flex'><li class='first_elements'>";
        echo '<ul><li>';
        echo __('Search').'&nbsp;';
        html_print_input_text('search', $search, '', 50);
        echo '</li><li>';
        echo __('Group').'&nbsp;';
        $own_info = get_user_info($config['id_user']);
if (!$own_info['is_admin'] && !check_acl($config['id_user'], 0, 'AW')) {
    $return_all_group = false;
} else {
    $return_all_group = true;
}

        html_print_select_groups(
            false,
            'AR',
            $return_all_group,
            'ag_group',
            $ag_group,
            'this.form.submit();',
            '',
            0,
            false,
            false,
            true,
            '',
            false
        );
        echo "</li></ul></li><li class='second_elements'><ul><li>";
        echo __('Group Recursion');
        html_print_checkbox('recursion', 1, $recursion, false, false, 'this.form.submit()');
        echo '</li><li>';
        echo "<input name='search_visual_console' type='submit' class='sub search' value='".__('Search')."'>";
        echo '</li></ul></li></ul>';
        echo '</form>';


        $returnAllGroups = 0;
        $filters = [];
        if (!empty($search)) {
            $filters['name'] = io_safe_input($search);
        }

        if ($ag_group > 0) {
            $ag_groups = [];
            $ag_groups = (array) $ag_group;
            if ($recursion) {
                $ag_groups = groups_get_id_recursive($ag_group, true);
            }
        } else if ($own_info['is_admin']) {
            $returnAllGroups = 1;
        }

        if ($ag_group) {
            $filters['group'] = array_flip($ag_groups);
        }

        $favorite_array = visual_map_get_user_layouts($config['id_user'], false, $filters, $returnAllGroups, true);

        echo "<div id='is_favourite'>";
        if ($favorite_array == false) {
            ui_print_info_message(__('No favourite consoles defined'));
        } else {
            echo "<ul class='container'>";
            foreach ($favorite_array as $favorite_k => $favourite_v) {
                if ($is_metaconsole) {
                    $url = 'index.php?sec=screen&sec2=screens/screens&action=visualmap&pure=0&id_visualmap='.$favourite_v['id'];
                } else {
                    $url = 'index.php?sec=network&sec2=operation/visual_console/render_view&id='.$favourite_v['id'];
                }

                echo "<a href='".$url."' title='Visual console".$favourite_v['name']."' alt='".$favourite_v['name']."'><li>";
                echo "<div class='icon_img'>";
                    echo html_print_image(
                        'images/groups_small/'.groups_get_icon($favourite_v['id_group']).'.png',
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
