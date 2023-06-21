<?php
/**
 * Map builder console.
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

// ACL for the general permission.
$vconsoles_read = check_acl($config['id_user'], 0, 'VR');
$vconsoles_write = check_acl($config['id_user'], 0, 'VW');
$vconsoles_manage = check_acl($config['id_user'], 0, 'VM');

$is_enterprise = enterprise_include_once('include/functions_policies.php');
$is_metaconsole = is_metaconsole();

if (!$vconsoles_read && !$vconsoles_write && !$vconsoles_manage) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access map builder'
    );
    include 'general/noaccess.php';
    exit;
}

if ($is_metaconsole === false) {
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

$pure = (int) get_parameter('pure', 0);
$hack_metaconsole = '';
if (is_metaconsole() === true) {
    $hack_metaconsole = '../../';
}

$buttons['visual_console'] = [
    'active' => true,
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
    'active' => false,
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
    __('Visual Console List'),
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

$id_layout = (int) get_parameter('id_layout');
$copy_layout = (bool) get_parameter('copy_layout');
$delete_layout = (bool) get_parameter('delete_layout');
$refr = (int) get_parameter('refr', $config['vc_refr']);
$offset = (int) get_parameter('offset', 0);
$pagination = (int) get_parameter('pagination', $config['block_size']);
$search = (string) get_parameter('search', '');
$ag_group = (int) get_parameter('ag_group', 0);
$recursion = get_parameter('recursion', 0);

if ($delete_layout || $copy_layout) {
    // Visual console required
    if (empty($id_layout)) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access map builder'
        );
        include 'general/noaccess.php';
        exit;
    }

    $group_id = db_get_value('id_group', 'tlayout', 'id', $id_layout);
    if ($group_id === false) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access map builder'
        );
        include 'general/noaccess.php';
        exit;
    }

    // ACL for the visual console
    // $vconsole_read = check_acl ($config['id_user'], $group_id, "VR");
    $vconsole_write = check_acl_restricted_all($config['id_user'], $group_id, 'VW', true);
    $vconsole_manage = check_acl_restricted_all($config['id_user'], $group_id, 'VM', true);

    if (!$vconsole_write && !$vconsole_manage) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access map builder'
        );
        include 'general/noaccess.php';
        exit;
    }

    if ($delete_layout) {
        db_process_sql_delete(
            'tlayout_data',
            ['id_layout' => $id_layout]
        );
        $result = db_process_sql_delete(
            'tlayout',
            ['id' => $id_layout]
        );
        db_process_sql_delete(
            'tfavmenu_user',
            [
                'id_element' => $id_layout,
                'section'    => 'Visual_Console',
                'id_user'    => $config['id_user'],
            ]
        );

        $auditMessage = ((bool) $result === true) ? 'Delete visual console' : 'Fail try to delete visual console';
        db_pandora_audit(
            AUDIT_LOG_VISUAL_CONSOLE_MANAGEMENT,
            sprintf('%s #%s', $auditMessage, $id_layout)
        );

        ui_print_result_message(
            (bool) $result,
            __('Successfully deleted'),
            __('Not deleted. Error deleting data')
        );

        db_clean_cache();

        $id_layout = 0;
    }

    if ($copy_layout) {
        // Number of inserts.
        $ninsert = (int) 0;

        // Return from DB the source layout.
        $layout_src = db_get_all_rows_filter(
            'tlayout',
            ['id' => $id_layout]
        );

        // Name of dst.
        $name_dst = get_parameter(
            'name_dst',
            $layout_src[0]['name'].' copy'
        );

        // Create the new Console.
        $idGroup = $layout_src[0]['id_group'];
        $background = $layout_src[0]['background'];
        $height = $layout_src[0]['height'];
        $width = $layout_src[0]['width'];
        $visualConsoleName = $name_dst;

        $values = [
            'name'             => $visualConsoleName,
            'id_group'         => $idGroup,
            'background'       => $background,
            'height'           => $height,
            'width'            => $width,
            'background_color' => $layout_src[0]['background_color'],
            'is_favourite'     => $layout_src[0]['is_favourite'],
        ];

        $result = db_process_sql_insert('tlayout', $values);

        $idNewVisualConsole = $result;

        if ($result) {
            $ninsert = 1;

            // Return from DB the items of the source layout
            $data_layout_src = db_get_all_rows_filter(
                'tlayout_data',
                ['id_layout' => $id_layout]
            );

            if (!empty($data_layout_src)) {
                // By default the id parent 0 is always 0.
                $id_relations = [0 => 0];

                for ($a = 0; $a < count($data_layout_src); $a++) {
                    // Changing the source id by the new visual console id
                    $data_layout_src[$a]['id_layout'] = $idNewVisualConsole;

                    $old_id = $data_layout_src[$a]['id'];

                    // Unsetting the source's id
                    unset($data_layout_src[$a]['id']);

                    // Configure the cloned Console
                    $result = db_process_sql_insert(
                        'tlayout_data',
                        $data_layout_src[$a]
                    );

                    $id_relations[$old_id] = 0;

                    if ($result !== false) {
                        $id_relations[$old_id] = $result;
                    }

                    if ($result) {
                        $ninsert++;
                    }
                }//end for

                $inserts = (count($data_layout_src) + 1);

                // If the number of inserts is correct, the copy is completed
                if ($ninsert == $inserts) {
                    // Update the ids of parents
                    $items = db_get_all_rows_filter(
                        'tlayout_data',
                        ['id_layout' => $idNewVisualConsole]
                    );

                    foreach ($items as $item) {
                        $new_parent = $id_relations[$item['parent_item']];

                        db_process_sql_update(
                            'tlayout_data',
                            ['parent_item' => $new_parent],
                            ['id' => $item['id']]
                        );
                    }


                    ui_print_success_message(__('Successfully copied'));
                    db_clean_cache();
                } else {
                    ui_print_error_message(__('Not copied. Error copying data'));
                }
            } else {
                // If the array is empty the copy is completed
                ui_print_success_message(__('Successfully copied'));
                db_clean_cache();
            }
        } else {
            ui_print_error_message(__('Not copied. Error copying data'));
        }
    }
}

if ($ag_group > 0) {
    $ag_groups = [];
    $ag_groups = (array) $ag_group;
    if ($recursion) {
        $ag_groups = groups_get_children_ids($ag_group, true);
    }
}

$own_info = get_user_info($config['id_user']);
if (!$own_info['is_admin'] && !check_acl($config['id_user'], 0, 'VR')) {
    $return_all_group = false;
} else {
    $return_all_group = true;
}

$filterTable = new stdClass();
$filterTable->id = 'map_buider_filter';
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
    html_print_select_groups(false, 'AR', $return_all_group, 'ag_group', $ag_group, 'this.form.submit();', '', 0, true, false, true, '', false)
);

$filterTable->data[0][] = html_print_label_input_block(
    __('Group Recursion'),
    html_print_checkbox_switch('recursion', 1, $recursion, true, false, 'this.form.submit()')
);

if (is_metaconsole() === false) {
    $actionUrl = 'index.php?sec=network&amp;sec2=godmode/reporting/map_builder';
} else {
    $actionUrl = 'index.php?sec=screen&sec2=screens/screens&action=visualmap';
}

$searchForm = [];
$searchForm[] = '<form method="POST" action="'.$actionUrl.'">';
$searchForm[] = html_print_table($filterTable, true);
$searchForm[] = html_print_div(
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
$searchForm[] = '</form>';

ui_toggle(
    implode('', $searchForm),
    '<span class="subsection_header_title">'.__('Filters').'</span>',
    'filter_form',
    '',
    true,
    false,
    '',
    'white-box-content',
    'box-flat white_table_graph fixed_filter_bar'
);

$table = new stdClass();
$table->width = '100%';
$table->class = 'info_table';
$table->cellpadding = 0;
$table->cellspacing = 0;
$table->data = [];
$table->head = [];
$table->head[0] = __('Map name');
$table->head[1] = __('Group');
$table->head[2] = __('Items');
$table->head[3] = __('Copy');
$table->head[4] = __('Delete');
$table->size[3] = '6%';
$table->size[4] = '6%';


$table->align = [];
$table->align[0] = 'left';
$table->align[1] = 'left';
$table->align[2] = 'left';
$table->align[3] = 'left';
$table->align[4] = 'left';

// Only display maps of "All" group if user is administrator
// or has "VR" privileges, otherwise show only maps of user group
$filters['offset'] = $offset;
$filters['limit'] = $pagination;
if (empty($search) === false) {
    $filters['name'] = io_safe_input($search);
}

if ($ag_group) {
    $filters['group'] = array_flip($ag_groups);
}

$own_info = get_user_info($config['id_user']);
if (is_metaconsole() === false) {
    $url = 'index.php?sec=network&amp;sec2=godmode/reporting/map_builder&recursion='.$recursion.'&ag_group='.$ag_group.'&search='.$search.'&pagination='.$pagination;
} else {
    $url = 'index.php?sec=screen&sec2=screens/screens&action=visualmap&recursion='.$recursion.'&ag_group='.$ag_group.'&search='.$search.'&pagination='.$pagination;
}




if ($own_info['is_admin'] || $vconsoles_read) {
    if ($ag_group) {
        $maps = visual_map_get_user_layouts($config['id_user'], false, $filters, false);
        unset($filters['offset']);
        unset($filters['limit']);
        $count_maps = visual_map_get_user_layouts($config['id_user'], false, $filters, false);
        $total_maps = count($count_maps);
    } else {
        $maps = visual_map_get_user_layouts($config['id_user'], false, $filters, false);
        unset($filters['offset']);
        unset($filters['limit']);
        $count_maps = visual_map_get_user_layouts($config['id_user'], false, $filters, false);
        $total_maps = count($count_maps);
    }
} else {
    $maps = visual_map_get_user_layouts($config['id_user'], false, $filters, false);
    unset($filters['offset']);
    unset($filters['limit']);
    $count_maps = visual_map_get_user_layouts($config['id_user'], false, $filters, false);
    $total_maps = count($count_maps);
}

if (!$maps && is_metaconsole() === false) {
    $total = count(visual_map_get_user_layouts($config['id_user'], false, false, false));
    if (!$total) {
        include_once $config['homedir'].'/general/first_task/map_builder.php';
    } else {
        ui_print_info_message(
            [
                'no_close' => false,
                'message'  => __('No available data to show'),
            ]
        );
    }
} else if (!$maps && is_metaconsole() === true) {
    $total = count(visual_map_get_user_layouts($config['id_user'], false, false, false));
    if (!$total) {
        ui_print_info_message(
            [
                'no_close' => true,
                'message'  => __('There are no visual console defined yet.'),
            ]
        );
    } else {
        ui_print_info_message(
            [
                'no_close' => false,
                'message'  => __('No available data to show'),
            ]
        );
    }
} else {
    foreach ($maps as $map) {
        // ACL for the visual console permission.
        $vconsole_write = false;
        $vconsole_manage = false;
        if (isset($map['vw']) === true) {
            $vconsole_write = true;
        }

        if (isset($map['vm']) === true) {
            $vconsole_manage = true;
        }

        $data = [];

        if (is_metaconsole() === false) {
            $data[0] = '<a href="index.php?sec=network&amp;sec2=operation/visual_console/render_view&amp;id='.$map['id'].'&amp;refr='.$refr.'">'.$map['name'].'</a>';
        } else {
            $data[0] = '<a href="index.php?sec=screen&sec2=screens/screens&action=visualmap&pure='.$pure.'&id='.$map['id'].'&amp;refr='.$refr.'">'.$map['name'].'</a>';
        }

        $data[1] = ui_print_group_icon($map['id_group'], true);
        $data[2] = db_get_sql('SELECT COUNT(*) FROM tlayout_data WHERE id_layout = '.$map['id']);

        $vconsoles_write_action_btn = check_acl_restricted_all($config['id_user'], $map['id_group'], 'VW');
        $vconsoles_manage_action_btn = check_acl_restricted_all($config['id_user'], $map['id_group'], 'VM');

        if ($vconsoles_write_action_btn || $vconsoles_manage_action_btn) {
            if (is_metaconsole() === false) {
                $table->cellclass[] = [
                    3 => 'table_action_buttons',
                    4 => 'table_action_buttons',
                ];
                $data[3] = '<a class="copy_visualmap" href="index.php?sec=network&amp;sec2=godmode/reporting/map_builder&amp;id_layout='.$map['id'].'&amp;copy_layout=1">'.html_print_image(
                    'images/copy.svg',
                    true,
                    ['class' => 'main_menu_icon invert_filter']
                ).'</a>';
                $data[4] = '<a class="delete_visualmap" href="index.php?sec=network&amp;sec2=godmode/reporting/map_builder&amp;id_layout='.$map['id'].'&amp;delete_layout=1" onclick="javascript: if (!confirm(\''.__('Are you sure?').'\n'.__('Delete').': '.$map['name'].'\')) return false;">'.html_print_image(
                    'images/delete.svg',
                    true,
                    ['class' => 'main_menu_icon invert_filter']
                ).'</a>';
            } else {
                $data[3] = '<a class="copy_visualmap" href="index.php?sec=screen&sec2=screens/screens&action=visualmap&pure='.$pure.'&id_layout='.$map['id'].'&amp;copy_layout=1">'.html_print_image(
                    'images/copy.svg',
                    true,
                    ['class' => 'main_menu_icon invert_filter']
                ).'</a>';
                $data[4] = '<a class="delete_visualmap" href="index.php?sec=screen&sec2=screens/screens&action=visualmap&pure='.$pure.'&id_layout='.$map['id'].'&amp;delete_layout=1" onclick="javascript: if (!confirm(\''.__('Are you sure?').'\n'.__('Delete').': '.$map['name'].'\')) return false;">'.html_print_image(
                    'images/delete.svg',
                    true,
                    ['class' => 'main_menu_icon invert_filter']
                ).'</a>';
            }
        } else {
            $data[3] = '';
            $data[4] = '';
        }

        array_push($table->data, $data);
    }

    html_print_table($table);
    $tablePagination = ui_pagination($total_maps, $url, $offset, $pagination, true, 'offset', false);
}

if ($maps || is_metaconsole() === true) {
    if ($vconsoles_write || $vconsoles_manage) {
        if (is_metaconsole() === false) {
            $actionUrl = 'index.php?sec=network&amp;sec2=godmode/reporting/visual_console_builder';
        } else {
            $actionUrl = 'index.php?sec=screen&sec2=screens/screens&action=visualmap&action2=new&operation=new_visualmap&tab=data&pure='.$pure;
        }

        echo '<form action="'.$actionUrl.'" method="post">';
        html_print_input_hidden('edit_layout', 1);

        html_print_action_buttons(
            html_print_submit_button(
                __('Create'),
                '',
                false,
                [ 'icon' => 'next'],
                true
            ),
            [ 'right_content' => $tablePagination ]
        );

        echo '</form>';
    }
}
