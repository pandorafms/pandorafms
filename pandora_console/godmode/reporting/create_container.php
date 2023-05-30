<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// Load global variables
global $config;

// Check user credentials
check_login();

if (! check_acl($config['id_user'], 0, 'RW')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Inventory Module Management'
    );
    include 'general/noaccess.php';
    return;
}

require_once 'include/functions_container.php';
require_once $config['homedir'].'/include/functions_custom_graphs.php';

$id_container = get_parameter('id', 0);
$offset = (int) get_parameter('offset', 0);

if (is_ajax()) {
    $add_single = (bool) get_parameter('add_single', 0);
    $add_custom = (bool) get_parameter('add_custom', 0);
    $add_dynamic = (bool) get_parameter('add_dynamic', 0);
    $id_container2 = get_parameter('id_container', 0);

    if ($add_single) {
        $id_agent = get_parameter('id_agent');
        $id_agent_module = get_parameter('id_agent_module');
        $time_lapse = get_parameter('time_lapse');
        $simple_type_graph = get_parameter('simple_type_graph');
        $fullscale = get_parameter('fullscale');

        if ($fullscale != 'false') {
                $fullscale = 1;
        } else {
                $fullscale = 0;
        }

        $values = [
            'id_container'    => $id_container2,
            'type'            => 'simple_graph',
            'id_agent'        => $id_agent,
            'id_agent_module' => $id_agent_module,
            'time_lapse'      => $time_lapse,
            'type_graph'      => $simple_type_graph,
            'fullscale'       => $fullscale,
        ];

        $id_item = db_process_sql_insert('tcontainer_item', $values);
        return;
    }

    if ($add_custom) {
        $time_lapse = get_parameter('time_lapse');
        $id_custom = get_parameter('id_custom');
        $fullscale = get_parameter('fullscale');
        if ($fullscale != 'false') {
                $fullscale = 1;
        } else {
                $fullscale = 0;
        }

        $values = [
            'id_container' => $id_container2,
            'type'         => 'custom_graph',
            'time_lapse'   => $time_lapse,
            'id_graph'     => $id_custom,
            'fullscale'    => $fullscale,
        ];

        $id_item = db_process_sql_insert('tcontainer_item', $values);
        return;
    }

    if ($add_dynamic) {
        $time_lapse = get_parameter('time_lapse');
        $group = get_parameter('group', 0);
        $module_group = get_parameter('module_group', 0);
        $agent_alias = get_parameter('agent_alias', '');
        $module_name = get_parameter('module_name', '');
        $tag = get_parameter('tag', 0);

        $simple_type_graph2 = get_parameter('simple_type_graph2');
        $fullscale = get_parameter('fullscale');

        if ($fullscale != 'false') {
                $fullscale = 1;
        } else {
                $fullscale = 0;
        }

        $values = [
            'id_container'    => $id_container2,
            'type'            => 'dynamic_graph',
            'time_lapse'      => $time_lapse,
            'id_group'        => $group,
            'id_module_group' => $module_group,
            'agent'           => $agent_alias,
            'module'          => $module_name,
            'id_tag'          => $tag,
            'type_graph'      => $simple_type_graph2,
            'fullscale'       => $fullscale,
        ];

        $id_item = db_process_sql_insert('tcontainer_item', $values);
        return;
    }
}

$add_container = (bool) get_parameter('add_container', 0);
$edit_container = (bool) get_parameter('edit_container', 0);
$update_container = (bool) get_parameter('update_container', 0);
$delete_item = (bool) get_parameter('delete_item', 0);

if ($edit_container) {
    $name = io_safe_input(get_parameter('name', ''));
    if (!empty($name)) {
        $id_parent = get_parameter('id_parent', 0);
        $description = io_safe_input(get_parameter('description', ''));
        $id_group = get_parameter('container_id_group', 0);
    } else if ((bool) $id_container !== false) {
        $tcontainer = db_get_row_sql('SELECT * FROM tcontainer WHERE id_container = '.$id_container);
        $name = $tcontainer['name'];
        $id_parent = $tcontainer['parent'];
        $description = $tcontainer['description'];
        $id_group = $tcontainer['id_group'];
    }
}

if ($add_container) {
    if ((bool) $name !== false) {
        $values = [
            'name'        => $name,
            'description' => $description,
            'parent'      => $id_parent,
            'id_group'    => $id_group,
        ];
        $id_container = db_process_sql_insert('tcontainer', $values);
    } else {
        $error = ui_print_error_message(
            __('Container name is missing.'),
            '',
            true
        );
    }
}

if ($update_container) {
    if ($id_container === $id_parent) {
        $success = false;
    } else {
        $values = [
            'name'        => $name,
            'description' => $description,
            'parent'      => $id_parent,
            'id_group'    => $id_group,
        ];
        $success = db_process_sql_update('tcontainer', $values, ['id_container' => $id_container]);
    }
}


if ($delete_item) {
    $id_item = get_parameter('id_item', 0);
    $success = db_process_sql_delete('tcontainer_item', ['id_ci' => $id_item]);
}

$buttons['graph_container'] = [
    'active' => false,
    'text'   => '<a href="index.php?sec=reporting&sec2=godmode/reporting/graph_container">'.html_print_image(
        'images/graph-container@svg.svg',
        true,
        [
            'title' => __('Graph container'),
            'class' => 'invert_filter',
        ]
    ).'</a>',
];

// Header.
ui_print_standard_header(
    __('Create container'),
    'images/chart.png',
    false,
    'create_container',
    false,
    $buttons,
    [
        [
            'link'  => '',
            'label' => __('Reporting'),
        ],
        [
            'link'  => '',
            'label' => __('Custom graphs'),
        ],
    ]
);

if ($add_container) {
    ui_print_result_message($id_container, __('Container stored successfully'), __('There was a problem storing container'));
    if (empty($error) === false) {
        echo $error;
    }
}

if ($update_container) {
    ui_print_result_message($success, __('Update the container'), __('Bad update the container'));
}

$table = '';
if ($edit_container) {
    $table .= "<form method='post' action='index.php?sec=reporting&sec2=godmode/reporting/create_container&edit_container=1&update_container=1&id=".$id_container."'>";
} else {
    $table .= "<form method='post' action='index.php?sec=reporting&sec2=godmode/reporting/create_container&edit_container=1&add_container=1'>";
}

$table .= "<table width='100%' class='filter-table-adv' cellpadding=4 cellspacing=4 >";
$table .= '<tr class="datos2"><td class="datos2" width="30%">';

$input_value = '';
if ($edit_container) {
    $input_value = io_safe_output($name);
}

if ($id_container === '1') {
    $input_name = html_print_input_text('name', $input_value, '', false, 255, true, true);
} else {
    $input_name = html_print_input_text('name', $input_value, '', false, 255, true, false, true);
}

$table .= html_print_label_input_block(
    __('Name'),
    $input_name
);

$table .= '</td>';
$own_info = get_user_info($config['id_user']);
if ($own_info['is_admin'] || check_acl($config['id_user'], 0, 'PM')) {
    $return_all_groups = true;
} else {
    $return_all_groups = false;
}

$table .= "<td class='datos2' width='30%'>";
$table .= html_print_label_input_block(
    __('Group'),
    html_print_input(
        [
            'type'           => 'select_groups',
            'id_user'        => $config['id_user'],
            'privilege'      => 'RW',
            'returnAllGroup' => $return_all_groups,
            'name'           => 'container_id_group',
            'selected'       => $id_group,
            'script'         => '',
            'nothing'        => '',
            'nothing_value'  => '',
            'return'         => true,
            'required'       => true,
            'disabled'       => ($id_container === '1'),
            'style'          => 'width:100% !important',
        ]
    )
);
$table .= '</td>';

$table .= "<td class='datos2' width='30%'>";
if ($id_container === '1') {
    $table .= html_print_label_input_block(
        __('Parent container'),
        html_print_select(
            $containers_tree,
            'id_parent',
            $id_parent,
            '',
            __('none'),
            0,
            true,
            '',
            false,
            'w130',
            true,
            'width: 100%',
            ''
        )
    );
} else {
    $table .= html_print_label_input_block(
        __('Parent container'),
        html_print_select(
            $containers_tree,
            'id_parent',
            $id_parent,
            '',
            __('none'),
            0,
            true,
            '',
            false,
            'w130',
            '',
            'width: 100%',
            ''
        )
    );
}

$table .= '</td></tr><tr class="datos2">';
$table .= '<td class="datos2" colspan="3">';
$textarea_disabled = false;
if ($id_container === '1') {
    $textarea_disabled = true;
}

$texarea_value = '';
if ($edit_container) {
    $texarea_value = io_safe_output($description);
}

$table .= html_print_label_input_block(
    __('Description'),
    html_print_textarea(
        'description',
        2,
        95,
        $texarea_value,
        '',
        true,
        '',
        $textarea_disabled
    )
);
$table .= '</td></tr>';
$container = folder_get_folders();
$tree = folder_get_folders_tree_recursive($container);
$containers_tree = folder_flatten_tree_folders($tree, 0);
$containers_tree = folder_get_select($containers_tree);

unset($containers_tree[$id_container]);


$table .= '</table>';

if ($edit_container) {
    if ($id_container !== '1') {
        $table .= html_print_div(
            [
                'class'   => 'action-buttons',
                'content' => html_print_submit_button(
                    __('Update'),
                    'store',
                    false,
                    [
                        'mode' => 'mini',
                        'icon' => 'next',
                    ],
                    true
                ),
            ],
            true
        );
    }
} else {
    $table .= html_print_div(
        [
            'class'   => 'action-buttons',
            'content' => html_print_submit_button(
                __('Create'),
                'store',
                false,
                [
                    'mode' => 'mini',
                    'icon' => 'next',
                ],
                true
            ),
        ],
        true
    );
}

$table .= '</form>';
ui_toggle(
    $table,
    '<span class="subsection_header_title">'.__('Container').'</span>',
    'container',
    '',
    false,
    false,
    '',
    'white-box-content',
    'box-flat white_table_graph'
);

echo '</br>';
echo '</br>';
echo '</br>';

if ($edit_container) {
    $period = SECONDS_15DAYS;
    $periods = [];
    $periods[-1] = __('custom');
    $periods[SECONDS_1HOUR] = __('1 hour');
    $periods[SECONDS_2HOUR] = sprintf(__('%s hours'), '2 ');
    $periods[SECONDS_6HOURS] = sprintf(__('%s hours'), '6 ');
    $periods[SECONDS_12HOURS] = sprintf(__('%s hours'), '12 ');
    $periods[SECONDS_1DAY] = __('1 day');
    $periods[SECONDS_2DAY] = sprintf(__('%s days'), '2 ');
    $periods[SECONDS_5DAY] = sprintf(__('%s days'), '5 ');
    $periods[SECONDS_1WEEK] = __('1 week');
    $periods[SECONDS_15DAYS] = __('15 days');
    $periods[SECONDS_1MONTH] = __('1 month');

    $type_graphs = [];
    $type_graphs[0] = __('Area');
    $type_graphs[1] = __('Line');

    $single_table = "<table width='100%' class='filter-table-adv' cellpadding=4 cellspacing=4>";
        $single_table .= "<tr class='datos2'>";
            $single_table .= "<td id='row_time_lapse' class='datos2' width='30%'>";
                $single_table .= html_print_label_input_block(
                    __('Time lapse').ui_print_help_tip(
                        __('This is the interval or period of time with which the graph data will be obtained. For example, a week means data from a week ago from now. '),
                        true
                    ),
                    html_print_extended_select_for_time(
                        'period_single',
                        $period,
                        '',
                        '',
                        '0',
                        10,
                        true,
                        'width:100%',
                        true,
                        '',
                        false,
                        $periods
                    )
                );
            $single_table .= '</td>';
            $single_table .= "<td id='row_agent' class='datos2' width='30%'>";
                $params = [];

                $params['show_helptip'] = false;
                $params['input_name'] = 'agent';
                $params['value'] = '';
                $params['return'] = true;

                $params['javascript_is_function_select'] = true;
                $params['selectbox_id'] = 'id_agent_module';
                $params['add_none_module'] = true;
                $params['use_hidden_input_idagent'] = true;
                $params['hidden_input_idagent_id'] = 'hidden-id_agent';


                $single_table .= html_print_label_input_block(
                    __('Agent'),
                    ui_print_agent_autocomplete_input($params)
                );
            $single_table .= '</td>';
            $single_table .= "<td id='row_module' class='datos2' width='30%'>";

    if ($idAgent) {
        $select_module .= html_print_select_from_sql($sql_modules, 'id_agent_module', $idAgentModule, '', '', '0', true);
    } else {
        $select_module .= "<select id='id_agent_module' name='id_agent_module' disabled='disabled' style='width:100%'>";
            $select_module .= "<option value='0'>";
                $select_module .= __('Select an Agent first');
            $select_module .= '</option>';
        $select_module .= '</select>';
    }

                $single_table .= html_print_label_input_block(
                    __('Module'),
                    $select_module
                );
            $single_table .= '</td>';
        $single_table .= '</tr>';
        $single_table .= "<tr class='datos2'>";
            $single_table .= "<td id='row_type_graphs' width='30%'>";
            $single_table .= html_print_label_input_block(
                __('Type of graph'),
                html_print_select($type_graphs, 'simple_type_graph2', '', '', '', 0, true, false, true, '', false, 'width:100%')
            );
            $single_table .= '</td>';

            $single_table .= "<td id='row_fullscale' width='30%' colspan='2'>";
            $single_table .= html_print_label_input_block(
                __('Show full scale graph (TIP)').ui_print_help_tip('This option may cause performance issues', true),
                html_print_checkbox('fullscale', 1, false, true)
            );
            $single_table .= '</td>';
        $single_table .= '</tr>';
    $single_table .= '</table>';

    $single_table .= html_print_div(
        [
            'class'   => 'action-buttons-right-forced mrgn_right_10px',
            'content' => html_print_submit_button(
                __('Add item'),
                'add_single',
                false,
                [
                    'mode' => 'mini',
                    'icon' => 'next',
                ],
                true
            ),
        ],
        true
    );

    ui_toggle(
        $single_table,
        '<span class="subsection_header_title">'.__('Simple module graph').'</span>',
        'container',
        '',
        true,
        false,
        '',
        'white-box-content',
        'box-flat white_table_graph'
    );

    $table = new stdClass();
    $table->id = 'custom_graph_table';
    $table->width = '100%';
    $table->cellspacing = 4;
    $table->cellpadding = 4;
    $table->class = 'filter-table-adv';

    $table->styleTable = 'font-weight: bold;';
    $table->style[0] = 'width: 13%';
    $table->data = [];
    $table->size[0] = '30%';
    $table->size[1] = '30%';
    $table->size[2] = '30%';

    $table->data[0][0] = html_print_label_input_block(
        __('Time lapse').ui_print_help_tip(
            __('This is the interval or period of time with which the graph data will be obtained. For example, a week means data from a week ago from now. '),
            true
        ),
        html_print_extended_select_for_time('period_custom', $period, '', '', '0', 10, true, 'width:100%', true, '', false, $periods)
    );

    $list_custom_graphs = custom_graphs_get_user($config['id_user'], false, true, 'RR');

    $graphs = [];
    foreach ($list_custom_graphs as $custom_graph) {
        $graphs[$custom_graph['id_graph']] = $custom_graph['name'];
    }

    $table->data[0][1] = html_print_label_input_block(
        __('Custom graph'),
        html_print_select($graphs, 'id_custom_graph', $idCustomGraph, '', __('None'), 0, true, '', true, '', false, 'width:100%')
    );

    $table->data[0][2] = html_print_label_input_block(
        __('Show full scale graph (TIP)').ui_print_help_tip('This option may cause performance issues', true),
        html_print_checkbox('fullscale_2', 1, false, true)
    );

    $data_toggle = html_print_table($table, true);
    $data_toggle .= html_print_div(
        [
            'class'   => 'action-buttons-right-forced mrgn_right_10px',
            'content' => html_print_submit_button(
                __('Add item'),
                'add_custom',
                false,
                [
                    'mode' => 'mini',
                    'icon' => 'next',
                ],
                true
            ),
        ],
        true
    );

    ui_toggle(
        $data_toggle,
        '<span class="subsection_header_title">'.__('Custom graph').'</span>',
        'container',
        '',
        true,
        false,
        '',
        'white-box-content',
        'box-flat white_table_graph'
    );

    unset($table);

    $table = new stdClass();
    $table->id = 'dynamic_rules_table';
    $table->width = '100%';
    $table->cellspacing = 4;
    $table->cellpadding = 4;
    $table->class = 'filter-table-adv';

    $table->styleTable = 'font-weight: bold;';
    $table->data = [];
    $table->size[0] = '30%';
    $table->size[1] = '30%';
    $table->size[2] = '30%';


    $table->data[0][0] = html_print_label_input_block(
        __('Time lapse').ui_print_help_tip(
            __('This is the interval or period of time with which the graph data will be obtained. For example, a week means data from a week ago from now. '),
            true
        ),
        html_print_extended_select_for_time('period_custom', $period, '', '', '0', 10, true, 'width:100%', true, '', false, $periods)
    );

    $table->data[0][1] = html_print_label_input_block(
        __('Group'),
        html_print_select_groups($config['id_user'], 'RW', $return_all_groups, 'container_id_group', $id_group, '', '', '', true)
    );

    $table->data[0][2] = html_print_label_input_block(
        __('Module group'),
        html_print_select_from_sql(
            'SELECT * FROM tmodule_group ORDER BY name',
            'combo_modulegroup',
            $modulegroup,
            '',
            __('All'),
            false,
            true,
            false,
            true,
            false,
            'width:100%'
        )
    );

    $table->data[1][0] = html_print_label_input_block(
        __('Agent'),
        html_print_input_text('text_agent', $textAgent, '', 30, 100, true)
    );

    $table->data[1][1] = html_print_label_input_block(
        __('Module'),
        html_print_input_text('text_agent_module', $textModule, '', 30, 100, true)
    );

    $select_tags = tags_search_tag(false, false, true);
    $table->data[1][2] = html_print_label_input_block(
        __('Tag'),
        html_print_select(
            $select_tags,
            'tag',
            $tag,
            '',
            __('Any'),
            0,
            true,
            false,
            false,
            '',
            false,
            'width:100%'
        )
    );

    $table->data[2][0] = html_print_label_input_block(
        __('Type of graph'),
        html_print_select($type_graphs, 'simple_type_graph2', '', '', '', 0, true, false, true, '', false, 'width:100%')
    );

    $table->data[2][1] = html_print_label_input_block(
        __('Show full scale graph (TIP)').ui_print_help_tip('This option may cause performance issues', true),
        html_print_checkbox('fullscale_3', 1, false, true)
    );

    $data_toggle = html_print_table($table, true);
    $data_toggle .= html_print_div(
        [
            'class'   => 'action-buttons-right-forced mrgn_right_10px',
            'content' => html_print_submit_button(
                __('Add item'),
                'add_dynamic',
                false,
                [
                    'mode' => 'mini',
                    'icon' => 'next',
                ],
                true
            ),
        ],
        true
    );

    ui_toggle(
        $data_toggle,
        '<span class="subsection_header_title">'.__('Dynamic rules for simple module graph').'</span>',
        'container',
        '',
        true,
        false,
        '',
        'white-box-content',
        'box-flat white_table_graph'
    );

    if ((bool) $id_container !== false) {
        $total_item = db_get_all_rows_sql('SELECT count(*) FROM tcontainer_item WHERE id_container = '.$id_container);
        $result_item = db_get_all_rows_sql('SELECT * FROM tcontainer_item WHERE id_container = '.$id_container.' LIMIT 10 OFFSET '.$offset);
    }

    if (!$result_item) {
        echo "<div class='nf'>".__('There are no items in this container.').'</div>';
    } else {
        ui_pagination($total_item[0]['count(*)'], false, $offset, 10);
        $table = new stdClass();
        $table->width = '100%';
        $table->class = 'info_table';
        $table->id = 'item_table';
        $table->align = [];
        $table->head = [];
        $table->head[0] = __('Agent/Module');
        $table->head[1] = __('Custom graph');
        $table->head[2] = __('Group');
        $table->head[3] = __('M.Group');
        $table->head[4] = __('Agent');
        $table->head[5] = __('Module');
        $table->head[6] = __('Tag');
        $table->head[7] = __('Delete');

        $table->data = [];
        $i = 0;

        foreach ($result_item as $item) {
            $data = [];
            switch ($item['type']) {
                case 'simple_graph':
                    $agent_alias = ui_print_truncate_text(agents_get_alias($item['id_agent'], 20, false));
                    $module_name = ui_print_truncate_text(modules_get_agentmodule_name($item['id_agent_module']), 20, false);
                    $module_name = $data[0] = $agent_alias.' / '.$module_name;
                    $data[1] = '';
                    $data[2] = '';
                    $data[3] = '';
                    $data[4] = '';
                    $data[5] = '';
                    $data[6] = '';
                break;

                case 'custom_graph':
                    $data[0] = '';
                    $name = db_get_value_filter('name', 'tgraph', ['id_graph' => $item['id_graph']]);
                    $data[1] = ui_print_truncate_text(io_safe_output($name), 35, false);
                    $data[2] = '';
                    $data[3] = '';
                    $data[4] = '';
                    $data[5] = '';
                    $data[6] = '';
                break;

                case 'dynamic_graph':
                    $data[0] = '';
                    $data[1] = '';

                    $data[2] = ui_print_group_icon($item['id_group'], true);
                    if ($item['id_module_group'] === '0') {
                        $data[3] = 'All';
                    } else {
                        $data[3] = io_safe_output(db_get_value_filter('name', 'tmodule_group', ['id_mg' => $item['id_module_group']]));
                    }

                    $data[4] = io_safe_output($item['agent']);
                    $data[5] = io_safe_output($item['module']);
                    if ($item['id_tag'] === '0') {
                        $data[6] = 'Any';
                    } else {
                        $data[6] = io_safe_output(db_get_value_filter('name', 'ttag', ['id_tag' => $item['id_tag']]));
                    }
                break;
            }

            $table->cellclass[$i][7] = 'table_action_buttons';
            $i++;
            $data[7] = '<a href="index.php?sec=reporting&sec2=godmode/reporting/create_container&edit_container=1&delete_item=1&id_item='.$item['id_ci'].'&id='.$id_container.'" onClick="if (!confirm(\''.__('Are you sure?').'\'))
                return false;">'.html_print_image('images/delete.svg', true, ['alt' => __('Delete'), 'title' => __('Delete'), 'class' => 'invert_filter main_menu_icon']).'</a>';

            array_push($table->data, $data);
        }

        html_print_table($table);
    }
}

echo html_print_input_hidden('id_agent', 0);
?>

<script type="text/javascript">
    $(document).ready (function () {
        $("#button-add_single").click (function () {
            var id_agent_module = $("#id_agent_module").val();
            if(id_agent_module !== '0'){
                var id_agent = $("#hidden-id_agent").attr('value');
                var time_lapse = $("#hidden-period_single").attr('value');
                var simple_type_graph = $("#simple_type_graph option:selected").attr('value');
                var fullscale = $("#checkbox-fullscale").prop("checked");
                var id_container = <?php echo $id_container; ?>;
                jQuery.post (
                    "ajax.php",
                    {"page" : "godmode/reporting/create_container",
                    "add_single" : 1,
                    "id_agent" : id_agent,
                    "id_agent_module" : id_agent_module,
                    "time_lapse" : time_lapse,
                    "simple_type_graph": simple_type_graph,
                    "fullscale" : fullscale,
                    "id_container" : id_container},
                    function (data, status) {
                        var url = location.href.replace('&update_container=1', "");
                        url = url.replace('&delete_item=1', "");
                        location.href = url.replace('&add_container=1', "&id="+id_container);
                    }
                );
            }
        });

        $("#button-add_custom").click (function () {
            var id_custom = $("#id_custom_graph").val();
            var fullscale = $("#checkbox-fullscale_2").prop("checked");
            if (id_custom !== '0'){
                var time_lapse = $("#hidden-period_custom").attr('value');
                var id_container = <?php echo $id_container; ?>;
                jQuery.post ("ajax.php",
                    {"page" : "godmode/reporting/create_container",
                    "add_custom" : 1,
                    "time_lapse" : time_lapse,
                    "id_custom" : id_custom,
                    "id_container" : id_container,
                   "fullscale" : fullscale,
                    },
                    function (data, status) {
                        var url = location.href.replace('&update_container=1', "");
                        url = url.replace('&delete_item=1', "");
                        location.href = url.replace('&add_container=1', "&id="+id_container);
                    }
                );
            }
        });
        
        $("#button-add_dynamic").click (function () {
            var agent_alias = $("#text-text_agent").val();
            var module_name = $("#text-text_agent_module").val();
            var time_lapse = $("#hidden-period_dynamic").attr('value');
            var group = $("#container_id_group1").val();
            var module_group = $("#combo_modulegroup").val();
            var simple_type_graph2 = $("#simple_type_graph2 option:selected").attr('value');
            var tag = $("#tag").val();

        var id_container = <?php echo $id_container; ?>;
            var fullscale = $("#checkbox-fullscale_3").prop("checked");
            jQuery.post ("ajax.php",
                {"page" : "godmode/reporting/create_container",
                "add_dynamic" : 1,
                "time_lapse" : time_lapse,
                "group" : group,
                "module_group" : module_group,
                "agent_alias" : agent_alias,
                "module_name" : module_name,
                "simple_type_graph2": simple_type_graph2,
                "tag" : tag,
                "id_container" : id_container,
                "fullscale" : fullscale
                },
                function (data, status) {
                    var url = location.href.replace('&update_container=1', "");
                    url = url.replace('&delete_item=1', "");
                    location.href = url.replace('&add_container=1', "&id="+id_container);
                }
            );

        });
    });

</script>
