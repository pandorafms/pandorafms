<?php
/**
 * Graph viewer.
 *
 * @category   Reporting - Graph analytics
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

// Begin.
global $config;

check_login();

use PandoraFMS\User;

// Requires.
ui_require_css_file('graph_analytics');
require_once 'include/functions_custom_graphs.php';

// Ajax.
if (is_ajax()) {
    $search_left = get_parameter('search_left');
    $search_right = get_parameter('search_right');
    $get_graphs = get_parameter('get_graphs');
    $save_filter = get_parameter('save_filter');
    $load_filter = get_parameter('load_filter');
    $update_filter = get_parameter('update_filter');
    $delete_filter = get_parameter('delete_filter');
    $get_new_values = get_parameter('get_new_values');
    $export_filter = get_parameter('export_filter');
    $load_list_filters = get_parameter('load_list_filters');

    if (empty($search_left) === false) {
        $output = [];
        $search = io_safe_input($search_left);

        // Agents.
        // Concatenate AW and AD permisions to get all the possible groups where the user can manage.
        $user_groupsAW = users_get_groups($config['id_user'], 'AW');
        $user_groupsAD = users_get_groups($config['id_user'], 'AD');

        $user_groups = ($user_groupsAW + $user_groupsAD);
        $user_groups_to_sql = implode(',', array_keys($user_groups));

        $search_sql = ' AND (nombre LIKE "%%'.$search.'%%" OR alias LIKE "%%'.$search.'%%")';

        $sql = sprintf(
            'SELECT *
            FROM tagente
            LEFT JOIN tagent_secondary_group tasg
                ON tagente.id_agente = tasg.id_agent
            WHERE (tagente.id_grupo IN (%s) OR tasg.id_group IN (%s))
                %s
            GROUP BY tagente.id_agente
            ORDER BY tagente.nombre',
            $user_groups_to_sql,
            $user_groups_to_sql,
            $search_sql
        );

        $output['agents'] = db_get_all_rows_sql($sql);

        // Groups.
        $search_sql = ' AND (nombre LIKE "%%'.$search.'%%" OR description LIKE "%%'.$search.'%%")';

        $sql = sprintf(
            'SELECT id_grupo, nombre, icon, description
            FROM tgrupo
            WHERE (id_grupo IN (%s))
                %s
            ORDER BY nombre',
            $user_groups_to_sql,
            $search_sql
        );

        $output['groups'] = db_get_all_rows_sql($sql);

        // Modules.
        $result_agents = [];
        $sql_result = db_get_all_rows_sql('SELECT id_agente FROM tagente WHERE id_grupo IN ('.$user_groups_to_sql.')');

        foreach ($sql_result as $result) {
            array_push($result_agents, $result['id_agente']);
        }

        $id_agents = implode(',', $result_agents);
        $search_sql = ' AND (tam.nombre LIKE "%%'.$search.'%%" OR tam.descripcion LIKE "%%'.$search.'%%")';

        $sql = sprintf(
            'SELECT tam.id_agente_modulo, tam.nombre, tam.descripcion, ta.alias
            FROM tagente_modulo tam
            INNER JOIN tagente ta ON ta.id_agente = tam.id_agente
            WHERE (tam.id_agente IN (%s))
                %s
            ORDER BY tam.nombre',
            $id_agents,
            $search_sql
        );

        $output['modules'] = db_get_all_rows_sql($sql);

        // Return.
        echo json_encode($output);
        return;
    }

    if (empty($search_right) === false) {
        $output = [];
        $search = io_safe_input(get_parameter('free_search'));
        $agent = get_parameter('search_agent');
        $group = get_parameter('search_group');

        $search_sql = ' AND (tam.nombre LIKE "%%'.$search.'%%" OR tam.descripcion LIKE "%%'.$search.'%%")';

        // Agent.
        if (empty($agent) === false) {
            $sql = sprintf(
                'SELECT tam.id_agente_modulo, tam.nombre, tam.descripcion, ta.alias
                FROM tagente_modulo tam
                INNER JOIN tagente ta ON ta.id_agente = tam.id_agente
                WHERE (tam.id_agente = %s)
                    %s
                ORDER BY tam.nombre',
                $agent,
                $search_sql
            );

            $output['modules'] = db_get_all_rows_sql($sql);
        }

        // Group.
        if (empty($group) === false) {
            $sql = sprintf(
                'SELECT tam.id_agente_modulo, tam.nombre, tam.descripcion, ta.alias
                FROM tagente_modulo tam
                INNER JOIN tagente ta ON ta.id_agente = tam.id_agente
                WHERE (ta.id_grupo = %s)
                    %s
                ORDER BY tam.nombre',
                $group,
                $search_sql
            );

            $output['modules'] = db_get_all_rows_sql($sql);
        }

        // Return.
        echo json_encode($output);
        return;
    }

    // Graph.
    if (empty($get_graphs) === false) {
        $interval = (int) get_parameter('interval');
        $modules = $get_graphs;

        $params = [
            'period'              => $interval,
            'width'               => '100%',
            'graph_width'         => '85%',
            'height'              => 100,
            'date'                => time(),
            'percentil'           => null,
            'fullscale'           => 1,
            'type_graph'          => 'line',
            'timestamp_top_fixed' => 'timestamp-top-fixed',
            'graph_analytics'     => true,
            'realtime'            => true,
        ];

        $params_combined = [
            'stacked'         => 2,
            'labels'          => [],
            'modules_series'  => $modules,
            'id_graph'        => null,
            'return'          => 1,
            'graph_analytics' => true,
        ];

        $graph_return = graphic_combined_module(
            $modules,
            $params,
            $params_combined
        );

        $graph_return .= "
            <script>
                $('div.parent_graph > .legend_background').each(function (index, element) {
                    $(element).next().html('');
                    $(element).next().append(element);
                });
            </script>
        ";

        echo $graph_return;
        return;
    }

    // Save filter.
    if (empty($save_filter) === false) {
        $graphs = get_parameter('graphs');
        $interval = (int) get_parameter('interval');

        if (empty($save_filter) === true) {
            echo __('Empty name');
            return;
        }

        if (empty($graphs) === true) {
            echo __('It is not possible to create the filter if you have not made any change');
            return;
        }

        $id_filter = db_process_sql_insert(
            'tgraph_analytics_filter',
            [
                'filter_name'   => $save_filter,
                'user_id'       => $config['id_user'],
                'graph_modules' => json_encode($graphs),
                'interval'      => $interval,
            ]
        );

        if ($id_filter > 0) {
            echo 'saved';
            return;
        } else {
            echo __('It is not possible to create the filter if you have not made any change');
            return;
        }
    }

    // Update filter.
    if (empty($update_filter) === false) {
        $graphs = get_parameter('graphs');
        $interval = (int) get_parameter('interval');

        if (empty($graphs) === true) {
            echo __('It is not possible to update the filter if you have not made any change');
            return;
        }

        $update_filter = db_process_sql_update(
            'tgraph_analytics_filter',
            [
                'graph_modules' => json_encode($graphs),
                'interval'      => $interval,
            ],
            ['id' => $update_filter]
        );

        if ($update_filter > 0) {
            echo 'updated';
            return;
        } else {
            echo __('No updated');
            return;
        }

        echo $update_filter;
        return;
    }

    // Load filter.
    if (empty($load_filter) === false) {
        $data = [];
        $data['graphs'] = json_decode(db_get_value('graph_modules', 'tgraph_analytics_filter', 'id', $load_filter));
        $data['interval'] = db_get_value('tgraph_analytics_filter.interval', 'tgraph_analytics_filter', 'id', $load_filter);

        echo json_encode($data);
        return;
    }

    if (empty($delete_filter) === false) {
        $result = db_process_sql_delete('tgraph_analytics_filter', ['id' => $delete_filter]);
        echo ((bool) $result === true) ? 'deleted' : '';
    }

    // Get new values.
    if (empty($get_new_values) === false) {
        $data = [];

        $agent_module_id = $get_new_values;
        $date_array = get_parameter('date_array');
        $data_module_graph = get_parameter('data_module_graph');
        $params = get_parameter('params');
        $suffix = get_parameter('suffix');

        // Stract data.
        $array_data_module = grafico_modulo_sparse_data(
            $agent_module_id,
            $date_array,
            $data_module_graph,
            $params,
            $suffix
        );

        echo json_encode($array_data_module);
        return;
    }

    // Export graphs.
    if (empty($export_filter) === false) {
        $counter = 0;
        $filter = get_parameter('export_filter');
        $group = get_parameter('group');

        $filter_name = db_get_value('filter_name', 'tgraph_analytics_filter', 'id', $filter);
        $graphs = json_decode(db_get_value('graph_modules', 'tgraph_analytics_filter', 'id', $filter));
        $interval = db_get_value('tgraph_analytics_filter.interval', 'tgraph_analytics_filter', 'id', $filter);

        $id_graph = db_process_sql_insert(
            'tgraph',
            [
                'id_user'     => $config['id_user'],
                'id_group'    => $group,
                'name'        => $filter_name.' ('.__('Graph').') ',
                'description' => __('Created from Graph analytics. Filter:').' '.$filter_name.'. '.__('Graph'),
                'period'      => $interval,
                'stacked'     => 2,
            ]
        );

        foreach ($graphs as $graph) {
            if ($id_graph > 0) {
                $counter++;
                $field_order = 1;

                foreach ($graph as $module) {
                    $id_graph_source = db_process_sql_insert(
                        'tgraph_source',
                        [
                            'id_graph'        => $id_graph,
                            'id_server'       => 0,
                            'id_agent_module' => $module,
                            'weight'          => 1,
                            'label'           => '',
                            'field_order'     => $field_order,
                        ]
                    );

                    $field_order++;
                }
            }
        }

        if ($id_graph > 0) {
            echo 'created';
        } else {
            echo '';
        }
    }

    if (empty($load_list_filters) === false) {
        $filters = graph_analytics_filter_select();
        echo json_encode($filters);
    }

    return;
}

// Save filter modal.
echo '<div id="save-filter-select" style="width:350px;" class="invisible">';
if (check_acl($config['id_user'], 0, 'RW') === 1 || check_acl($config['id_user'], 0, 'RM') === 1) {
    echo '<div id="info_box"></div>';
    $table = new StdClass;
    $table->id = 'save_filter_form';
    $table->width = '100%';
    $table->cellspacing = 4;
    $table->cellpadding = 4;
    $table->class = 'databox no_border';

    $table->styleTable = 'font-weight: bold; text-align:left;';

    $data = [];
    $table->rowid[0] = 'update_save_selector';
    $data[0] = html_print_div(
        [
            'style'   => 'display: flex;',
            'content' => html_print_radio_button(
                'filter_mode',
                'new',
                __('Create'),
                true,
                true
            ),
        ],
        true
    );

    $data[1] = html_print_div(
        [
            'style'   => 'display: flex;',
            'content' => html_print_radio_button(
                'filter_mode',
                'update',
                __('Update'),
                false,
                true
            ),
        ],
        true
    );

    $data[2] = html_print_div(
        [
            'style'   => 'display: flex;',
            'content' => html_print_radio_button(
                'filter_mode',
                'delete',
                __('Delete'),
                false,
                true
            ),
        ],
        true
    );

    $table->data[] = $data;
    $table->rowclass[] = '';

    $data = [];
    $table->rowid[1] = 'save_filter_row1';
    $data[0] = __('Filter name');
    $data[0] .= html_print_input_text('id_name', '', '', 15, 255, true);

    $data[1] = html_print_submit_button(
        __('Save filter'),
        'save_filter',
        false,
        [
            'class'   => 'mini ',
            'icon'    => 'save',
            'style'   => 'margin-left: 175px; width: 125px;',
            'onclick' => 'save_new_filter();',
        ],
        true
    );

    $table->data[] = $data;
    $table->rowclass[] = '';

    $data = [];
    $table->rowid[2] = 'save_filter_row2';

    $table->data[] = $data;
    $table->rowclass[] = '';

    $data = [];
    $table->rowid[3] = 'update_filter_row1';
    $data[0] = __('Overwrite filter');

    $select_filters_update = graph_analytics_filter_select();

    $data[0] .= html_print_select(
        $select_filters_update,
        'overwrite_filter',
        '',
        '',
        '',
        0,
        true
    );
    $table->rowclass[] = 'display-grid';
    $data[1] = html_print_submit_button(
        __('Update filter'),
        'update_filter',
        false,
        [
            'class'   => 'mini ',
            'icon'    => 'save',
            'style'   => 'margin-left: 155px; width: 145px;',
            'onclick' => 'save_update_filter();',
        ],
        true
    );

    $table->data[] = $data;
    $table->rowclass[] = 'display-grid';

    $data = [];
    $table->rowid[4] = 'delete_filter_row2';
    $data[0] = __('Delete filter');

    $select_filters_delete = graph_analytics_filter_select();

    $data[0] .= html_print_select(
        $select_filters_delete,
        'delete_filter',
        '',
        '',
        '',
        0,
        true
    );
    $data[1] = html_print_submit_button(
        __('Delete filter'),
        'delete_filter',
        false,
        [
            'class'   => 'mini ',
            'icon'    => 'delete',
            'style'   => 'margin-left: 155px; width: 145px;',
            'onclick' => 'delete_filter();',
        ],
        true
    );

    $table->data[] = $data;

    html_print_table($table);
} else {
    include 'general/noaccess.php';
}

echo '</div>';

// Load filter modal.
$filters = graph_analytics_filter_select();

echo '<div id="load-filter-select" class="load-filter-modal invisible">';

$table = new StdClass;
$table->id = 'load_filter_form';
$table->width = '100%';
$table->cellspacing = 4;
$table->cellpadding = 4;
$table->class = 'databox no_border';

$table->styleTable = 'font-weight: bold; color: #555; text-align:left;';
$filter_id_width = 'w100p';

$data = [];
$table->rowid[3] = 'update_filter_row1';
$data[0] = __('Load filter');
$data[0] .= html_print_select(
    $filters,
    'filter_id',
    '',
    '',
    __('None'),
    0,
    true,
    false,
    true,
    '',
    false,
    'width:'.$filter_id_width.';'
);

$table->rowclass[] = 'display-grid';
$data[1] = html_print_submit_button(
    __('Load filter'),
    'load_filter',
    false,
    [
        'class'   => 'mini w30p',
        'icon'    => 'load',
        'style'   => 'margin-left: 208px; width: 130px;',
        'onclick' => 'load_filter_values();',
    ],
    true
);
$data[1] .= html_print_input_hidden('load_filter', 1, true);
$table->data[] = $data;
$table->rowclass[] = '';

html_print_table($table);
echo '</div>';

// Share modal.
echo '<div id="share-select" class="load-filter-modal invisible">';

$table = new StdClass;
$table->id = 'share_form';
$table->width = '100%';
$table->cellspacing = 4;
$table->cellpadding = 4;
$table->class = 'databox no_border';

$table->styleTable = 'font-weight: bold; color: #555; text-align:left;';
$filter_id_width = 'w100p';

$data = [];
$table->rowid[3] = 'share_row1';
$data[0] = __('Share');
$data[0] .= html_print_select(
    $filters,
    'share-id',
    '',
    '',
    '',
    0,
    true,
    false,
    true,
    '',
    false,
    'width:'.$filter_id_width.';'
);

$table->rowclass[] = 'display-grid';
$data[1] = html_print_submit_button(
    __('Share'),
    'share-modal',
    false,
    [
        'class'   => 'mini w30p',
        'icon'    => 'next',
        'style'   => 'margin-left: 208px; width: 130px;',
        'onclick' => '',
    ],
    true
);
$data[1] .= html_print_input_hidden('share-hidden', 1, true);
$table->data[] = $data;
$table->rowclass[] = '';

html_print_table($table);
echo '</div>';

// Export graphs.
echo '<div id="export-select" class="load-filter-modal invisible">';

$table = new StdClass;
$table->id = 'export_form';
$table->width = '100%';
$table->cellspacing = 4;
$table->cellpadding = 4;
$table->class = 'databox no_border';

$table->styleTable = 'font-weight: bold; color: #555; text-align:left;';
$filter_id_width = 'w100p';

$data = [];
$table->rowid[3] = 'export_row1';
$data[0] = __('Export filter');
$data[0] .= html_print_select(
    $filters,
    'export-filter-id',
    '',
    '',
    '',
    0,
    true,
    false,
    true,
    '',
    false,
    'width:'.$filter_id_width.';'
);

if (isset($config['user']) === false) {
    $config['user'] = false;
}

$user_groups = users_get_groups($config['user'], 'RW');
$data[1] = __('Group');
$data[1] .= html_print_select(
    $user_groups,
    'export-group-id',
    '',
    '',
    '',
    0,
    true,
    false,
    true,
    '',
    false,
    'width:'.$filter_id_width.';'
);

$table->rowclass[] = 'display-grid';
$data[2] = html_print_submit_button(
    __('Export'),
    'export-modal',
    false,
    [
        'class'   => 'mini w30p',
        'icon'    => 'next',
        'style'   => 'margin-left: 208px; width: 130px;',
        'onclick' => 'exportCustomGraph()',
    ],
    true
);
$data[1] .= html_print_input_hidden('export-hidden', 1, true);
$table->data[] = $data;
$table->rowclass[] = '';

html_print_table($table);
echo '</div>';


// Header & Actions.
$title_tab = __('Start realtime');
$tab_start_realtime = [
    'text' => '<span data-button="start-realtime">'.html_print_image(
        'images/change-active.svg',
        true,
        [
            'title' => $title_tab,
            'class' => 'invert_filter main_menu_icon',
        ]
    ).$title_tab.'</span>',
];

$title_tab = __('Pause realtime');
$tab_pause_realtime = [
    'text' => '<span data-button="pause-realtime">'.html_print_image(
        'images/change-pause.svg',
        true,
        [
            'title' => $title_tab,
            'class' => 'invert_filter main_menu_icon',
        ]
    ).$title_tab.'</span>',
];

$title_tab = __('New');
$tab_new = [
    'text' => '<span data-button="new">'.html_print_image(
        'images/plus-black.svg',
        true,
        [
            'title' => $title_tab,
            'class' => 'invert_filter main_menu_icon',
        ]
    ).$title_tab.'</span>',
];

$title_tab = __('Save');
$tab_save = [
    'text' => '<span data-button="save">'.html_print_image(
        'images/save_mc.png',
        true,
        [
            'title' => $title_tab,
            'class' => 'invert_filter main_menu_icon',
        ]
    ).$title_tab.'</span>',
];

$title_tab = __('Load');
$tab_load = [
    'text' => '<span data-button="load">'.html_print_image(
        'images/logs@svg.svg',
        true,
        [
            'title' => $title_tab,
            'class' => 'invert_filter main_menu_icon',
        ]
    ).$title_tab.'</span>',
];

// Hash for auto-auth in public link.
$hash = User::generatePublicHash();

$title_tab = __('Share');
$tab_share = [
    'text' => '<span data-button="share">'.html_print_image(
        'images/responses.svg',
        true,
        [
            'title' => $title_tab,
            'class' => 'invert_filter main_menu_icon',
        ]
    ).$title_tab.'</span><input id="hash_share" type="hidden" value="'.$hash.'">
    <input id="id_user" type="hidden" value="'.$config['id_user'].'">',
];

$title_tab = __('Export to custom graph');
$tab_export = [
    'text' => '<span data-button="export">'.html_print_image(
        'images/module-graph.svg',
        true,
        [
            'title' => $title_tab,
            'class' => 'invert_filter main_menu_icon',
        ]
    ).$title_tab.'</span>',
];

ui_print_standard_header(
    __('Graph analytics'),
    'images/menu/reporting.svg',
    false,
    '',
    false,
    [
        $tab_export,
        $tab_share,
        $tab_load,
        $tab_save,
        $tab_new,
        $tab_pause_realtime,
        $tab_start_realtime,
        html_print_input_hidden('section', get_parameter('sec2'), true),
    ],
    [
        [
            'link'  => '',
            'label' => __('Reporting'),
        ],
    ]
);

// Content.
$left_content = '';
$right_content = '';

$left_content .= '
    <div class="filters-div-header">
        <div></div>
        <img src="images/menu/contraer.svg">
    </div>
    <div class="filters-div-submain">
        <div class="filter-div filters-left-div">
            <span><b>'.__('Agents').'</b></span>
            <input id="search-left" name="search-left" placeholder="Enter keywords to search" type="search" class="search-graph-analytics">
            <br>
'.ui_toggle(
                '',
                __('Agents'),
                'agents-toggle',
                'agents-toggle',
                true,
                true,
                '',
                'white-box-content',
                'box-flat white_table_graph',
                'images/arrow@svg.svg',
                'images/arrow@svg.svg',
                false,
                false,
                false,
                '',
                '',
                null,
                null,
                false,
                false,
                'static'
).ui_toggle(
    '',
    __('Groups'),
    'groups-toggle',
    'groups-toggle',
    true,
    true,
    '',
    'white-box-content',
    'box-flat white_table_graph',
    'images/arrow@svg.svg',
    'images/arrow@svg.svg',
    false,
    false,
    false,
    '',
    '',
    null,
    null,
    false,
    false,
    'static'
).ui_toggle(
    '',
    __('Modules'),
    'modules-toggle',
    'modules-toggle',
    true,
    true,
    'modules',
    'white-box-content',
    'box-flat white_table_graph',
    'images/arrow@svg.svg',
    'images/arrow@svg.svg',
    false,
    false,
    false,
    '',
    '',
    null,
    null,
    false,
    false,
    'static'
).'
        </div>
        <div class="filter-div filters-right-div ">
            <span><b>'.__('Modules').'</b></span>
            <input id="search-right" placeholder="Enter keywords to search" type="search" class="search-graph-analytics">
            <input id="search-agent" type="hidden" value="">
            <input id="search-group" type="hidden" value="">
            <div id="modules-right"></div>
        </div>
    </div>
';

$intervals = [];
$intervals[SECONDS_1HOUR]  = _('Last ').human_time_description_raw(SECONDS_1HOUR, true, 'large');
$intervals[SECONDS_6HOURS]  = _('Last ').human_time_description_raw(SECONDS_6HOURS, true, 'large');
$intervals[SECONDS_12HOURS] = _('Last ').human_time_description_raw(SECONDS_12HOURS, true, 'large');
$intervals[SECONDS_1DAY] = _('Last ').human_time_description_raw(SECONDS_1DAY, true, 'large');
$intervals[SECONDS_2DAY] = _('Last ').human_time_description_raw(SECONDS_2DAY, true, 'large');
$intervals[SECONDS_1WEEK] = _('Last ').human_time_description_raw(SECONDS_1WEEK, true, 'large');

$right_content .= '<div class="interval-div">'.html_print_select(
    $intervals,
    'interval',
    SECONDS_12HOURS,
    '',
    '',
    0,
    true,
    false,
    false,
    ''
).'</div>';

$right_content .= '
    <div id="droppable-graphs">
        <div class="droppable droppable-default-zone" data-modules="[]"><span class="drop-here">'.__('Drop here').'<span></div>
    </div>
';

$filters_div = html_print_div(
    [
        'class'   => 'filters-div-main',
        'content' => $left_content,
    ],
    true
);

$graphs_div = html_print_div(
    [
        'id'      => 'graph_analytics',
        'class'   => 'padding-div graphs-div-main',
        'content' => $right_content,
    ],
    true
);

html_print_div(
    [
        'class'   => 'white_box main-div',
        'content' => $filters_div.$graphs_div,
    ]
);

ui_require_javascript_file('graph_analytics', 'include/javascript/', true);
?>

<script>
const dropHere = "<?php echo __('Drop here'); ?>";

const titleNew = "<?php echo __('New graph'); ?>";
const messageNew = "<?php echo __('If you create a new graph, the current settings will be deleted. Please save the graph if you want to keep it.'); ?>";

const titleSave = "<?php echo __('Saved successfully'); ?>";
const messageSave = "<?php echo __('The filter has been saved successfully'); ?>";

const messageSaveEmpty = "<?php echo __('It is not possible to create the filter if you have not made any change'); ?>";
const messageSaveEmptyName = "<?php echo __('Empty name'); ?>";

const titleError = "<?php echo __('Error'); ?>";

const titleUpdate = "<?php echo __('Override filter?'); ?>";
const messageUpdate = "<?php echo __('Do you want to overwrite the filter?'); ?>";

const titleDelete = "<?php echo __('Delete filter?'); ?>";
const messageDelete = "<?php echo __('Do you want to delete the filter?'); ?>";

const titleDeleteConfirm = "<?php echo __('Deleted successfully'); ?>";
const messageDeleteConfirm = "<?php echo __('The filter has been deleted successfully'); ?>";

const titleDeleteError = "<?php echo __('Error'); ?>";
const messageDeleteError = "<?php echo __('It is not possible delete the filter'); ?>";

const titleUpdateConfirm = "<?php echo __('Updated successfully'); ?>";
const messageUpdateConfirm = "<?php echo __('The filter has been updated successfully'); ?>";

const titleUpdateError = "<?php echo __('Error'); ?>";
const messageUpdateError = "<?php echo __('It is not possible to update the filter if you have not made any change'); ?>";

const titleLoad = "<?php echo __('Overwrite current graph?'); ?>";
const messageLoad = "<?php echo __('If you load a filter, it will clear the current graph'); ?>";

const titleLoadConfirm = "<?php echo __('Error'); ?>";
const messageLoadConfirm = "<?php echo __('Error loading filter'); ?>";

const titleExport = "<?php echo __('Export to custom graph'); ?>";

const titleExportConfirm = "<?php echo __('Exported successfully'); ?>";
const messageExportConfirm = "<?php echo __('Graph have been created in Custom graphs'); ?>";

const titleExportError = "<?php echo __('Error to export'); ?>";
const messageExportError = "<?php echo __('Filter cannot be None'); ?>";

const titleRemoveConfirm = "<?php echo __('Delete graph'); ?>";
const messageRemoveConfirm = "<?php echo __('Do you want to delete the graph? Remember to save the changes.'); ?>";

const titleModalActions = "<?php echo __('Filter actions'); ?>"

</script>