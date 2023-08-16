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

// Requires.
ui_require_css_file('graph_analytics');
require_once 'include/functions_custom_graphs.php';

// Get parameters.
$x = get_parameter('x');

// Ajax.
if (is_ajax()) {
    $search_left = get_parameter('search_left');

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
        $search_sql = ' AND (nombre LIKE "%%'.$search.'%%" OR descripcion LIKE "%%'.$search.'%%")';

        $sql = sprintf(
            'SELECT id_agente_modulo, nombre, descripcion
            FROM tagente_modulo
            WHERE (id_agente IN (%s))
                %s
            ORDER BY nombre',
            $id_agents,
            $search_sql
        );

        $output['modules'] = db_get_all_rows_sql($sql);

        // Return.
        echo json_encode($output);
        return;
    }

    return;
}

// Header & Actions.
$title_tab = __('Start realtime');
$tab_start_realtime = [
    'text' => '<span>'.html_print_image(
        'images/change-active.svg',
        true,
        [
            'title' => $title_tab,
            'class' => 'invert_filter main_menu_icon',
        ]
    ).$title_tab.'</span>',
];

$title_tab = __('New');
$tab_new = [
    'text' => '<span>'.html_print_image(
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
    'text' => '<span>'.html_print_image(
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
    'text' => '<span>'.html_print_image(
        'images/logs@svg.svg',
        true,
        [
            'title' => $title_tab,
            'class' => 'invert_filter main_menu_icon',
        ]
    ).$title_tab.'</span>',
];

$title_tab = __('Share');
$tab_share = [
    'text' => '<span>'.html_print_image(
        'images/responses.svg',
        true,
        [
            'title' => $title_tab,
            'class' => 'invert_filter main_menu_icon',
        ]
    ).$title_tab.'</span>',
];

$title_tab = __('Export to custom graph');
$tab_export = [
    'text' => '<span>'.html_print_image(
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
        $tab_start_realtime,
    ],
    [
        [
            'link'  => '',
            'label' => __('Reporting'),
        ],
    ]
);

// Header options.
// $options_content = 'Sample text';
// html_print_div(
// [
// 'class'   => 'options-graph-analytics',
// 'content' => $options_content,
// ]
// );
// Content.
$left_content = '';
$right_content = '';

// $left_content .= '<input id="search" name="search" placeholder="Enter keywords to search" type="search" class="w100p">';
// <div class="draggable draggable1" data-id-module="1"></div>
// <div class="draggable draggable2" data-id-module="2"></div>
// <div class="draggable draggable3" data-id-module="3"></div>
$left_content .= '
    <div class="filters-div-header">
        <div></div>
        <img src="images/menu/contraer.svg">
    </div>
    <div class="filters-div-submain">
        <div class="filter-div filters-left-div">
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
).'
        </div>
        <div class="filter-div filters-right-div ">
            <input id="search-right" name="search-right" placeholder="Enter keywords to search" type="search" class="search-graph-analytics">
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
// <div class="droppable" data-id-zone="zone1"></div>
// <div class="droppable" data-id-zone="zone2"></div>
// <div class="droppable" data-id-zone="zone3"></div>
$filters_div = html_print_div(
    [
        'class'   => 'filters-div-main',
        'content' => $left_content,
    ],
    true
);

$graphs_div = html_print_div(
    [
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


// Realtime graph.
$table = new stdClass();
$table->width = '100%';
$table->id = 'table-form';
$table->class = 'filter-table-adv';
$table->style = [];
$table->data = [];

$graph_fields['cpu_load'] = __('%s Server CPU', get_product_name());
$graph_fields['pending_packets'] = __(
    'Pending packages from %s Server',
    get_product_name()
);
$graph_fields['disk_io_wait'] = __(
    '%s Server Disk IO Wait',
    get_product_name()
);
$graph_fields['apache_load'] = __(
    '%s Server Apache load',
    get_product_name()
);
$graph_fields['mysql_load'] = __(
    '%s Server MySQL load',
    get_product_name()
);
$graph_fields['server_load'] = __(
    '%s Server load',
    get_product_name()
);
$graph_fields['snmp_interface'] = __('SNMP Interface throughput');

$graph = get_parameter('graph', 'cpu_load');
$refresh = get_parameter('refresh', '1000');

if ($graph != 'snmp_module') {
    $data['graph'] = html_print_label_input_block(
        __('Graph'),
        html_print_select(
            $graph_fields,
            'graph',
            $graph,
            '',
            '',
            0,
            true,
            false,
            true,
            '',
            false,
            'width: 100%'
        )
    );
}

$refresh_fields[1000]  = human_time_description_raw(1, true, 'large');
$refresh_fields[5000]  = human_time_description_raw(5, true, 'large');
$refresh_fields[10000] = human_time_description_raw(10, true, 'large');
$refresh_fields[30000] = human_time_description_raw(30, true, 'large');

if ($graph == 'snmp_module') {
    $agent_alias = io_safe_output(get_parameter('agent_alias', ''));
    $module_name = io_safe_output(get_parameter('module_name', ''));
    $module_incremental = get_parameter('incremental', 0);
    $data['module_info'] = html_print_label_input_block(
        $agent_alias.': '.$module_name,
        html_print_input_hidden(
            'incremental',
            $module_incremental,
            true
        ).html_print_select(
            ['snmp_module' => '-'],
            'graph',
            'snmp_module',
            '',
            '',
            0,
            true,
            false,
            true,
            '',
            false,
            'width: 100%; display: none;'
        )
    );
}

$data['refresh'] = html_print_label_input_block(
    __('Refresh interval'),
    html_print_select(
        $refresh_fields,
        'refresh',
        $refresh,
        '',
        '',
        0,
        true,
        false,
        true,
        '',
        false,
        'width: 100%'
    )
);

if ($graph != 'snmp_module') {
    $data['incremental'] = html_print_label_input_block(
        __('Incremental'),
        html_print_checkbox_switch('incremental', 1, 0, true)
    );
}

$table->data[] = $data;

// Print the relative path to AJAX calls.
html_print_input_hidden('rel_path', get_parameter('rel_path', ''));

// Print the form.
$searchForm = '<form id="realgraph" method="post">';
$searchForm .= html_print_table($table, true);
$searchForm .= html_print_div(
    [
        'class'   => 'action-buttons',
        'content' => html_print_submit_button(
            __('Clear graph'),
            'srcbutton',
            false,
            [
                'icon'    => 'delete',
                'mode'    => 'mini',
                'onClick' => 'javascript:realtimeGraphs.clearGraph();',
            ],
            true
        ),
    ],
    true
);
$searchForm .= '</form>';

// echo $searchForm;
echo '
    <input type="hidden" id="refresh" value="1000">
    <input type="hidden" id="hidden-incremental" value="0">
';

// Canvas realtime graph.
$canvas = '<div id="graph_container" class="graph_container">';
$canvas .= '<div id="chartLegend" class="chartLegend"></div>';

$width = 800;
$height = 300;

$data_array['realtime']['data'][0][0] = (time() - 10);
$data_array['realtime']['data'][0][1] = 0;
$data_array['realtime']['data'][1][0] = time();
$data_array['realtime']['data'][1][1] = 0;
$data_array['realtime']['color'] = 'green';

$params = [
    'agent_module_id'   => false,
    'period'            => 300,
    'width'             => $width,
    'height'            => $height,
    'only_image'        => false,
    'type_graph'        => 'area',
    'font'              => $config['fontpath'],
    'font-size'         => $config['font_size'],
    'array_data_create' => $data_array,
    'show_legend'       => false,
    'show_menu'         => false,
    'backgroundColor'   => 'transparent',
];

$canvas .= grafico_modulo_sparse($params);
$canvas .= '</div>';
echo $canvas;

html_print_input_hidden(
    'custom_action',
    urlencode(
        base64_encode(
            '&nbsp;<a href="javascript:realtimeGraphs.setOID();"><img src="'.ui_get_full_url('images').'/input_filter.disabled.png" title="'.__('Use this OID').'" class="vertical_middle"></img></a>'
        )
    ),
    false
);
html_print_input_hidden('incremental_base', '0');

echo '<script type="text/javascript" src="'.ui_get_full_url('include/javascript/pandora_snmp_browser.js').'?v='.$config['current_package'].'"></script>';
echo '<script type="text/javascript" src="'.ui_get_full_url('extensions/realtime_graphs/realtime_graphs.js').'?v='.$config['current_package'].'"></script>';

if ($config['style'] !== 'pandora_black') {
    echo '<link rel="stylesheet" type="text/css" href="'.ui_get_full_url('extensions/realtime_graphs/realtime_graphs.css').'?v='.$config['current_package'].'"></style>';
}

// Store servers timezone offset to be retrieved from js.
set_js_value('timezone_offset', date('Z', time()));

extensions_add_operation_menu_option(
    __('Realtime graphs'),
    'estado',
    null,
    'v1r1',
    'view'
);
extensions_add_main_function('pandora_realtime_graphs');
?>

<script>
// Interval change.
$('#interval').change(function (e) { 
    console.log(parseInt($(this).val()));
});

// Collapse filters.
$('div.filters-div-main > .filters-div-header > img').click(function (e) {
    if ($('.filters-div-main').hasClass('filters-div-main-collapsed') === true) {
        $('.filters-div-header > img').attr('src', 'images/menu/contraer.svg');
        $('.filters-div-main').removeClass('filters-div-main-collapsed');
    } else {
        $('.filters-div-header > img').attr('src', 'images/menu/expandir.svg');
        $('.filters-div-main').addClass('filters-div-main-collapsed');
    }
});

// Search left.
$('#search-left').keyup(function (e) { 
    $.ajax({
        method: "POST",
        url: 'ajax.php',
        dataType: "json",
        data: {
            page: 'operation/reporting/graph_analytics',
            search_left: e.target.value,
        },
        success: function(data) {
            if(data.agents || data.groups || data.modules){
                console.log(data);
                var agentsToggle = $('#agents-toggle > div[id^=tgl_div_] > div.white-box-content');
                var groupsToggle = $('#groups-toggle > div[id^=tgl_div_] > div.white-box-content');
                var modulesToggle = $('#modules-toggle > div[id^=tgl_div_] > div.white-box-content');
                agentsToggle.empty();
                groupsToggle.empty();
                modulesToggle.empty();

                if (data.agents) {
                    $('#agents-toggle').show();
                    data.agents.forEach(agent => {
                        agentsToggle.append(`<div class="" data-id-agent="${agent.id_agente}" title="${agent.alias}">${agent.alias}</div>`);
                    });
                } else {
                    $('#agents-toggle').hide();
                }

                if (data.groups) {
                    $('#groups-toggle').show();
                    data.groups.forEach(group => {
                        groupsToggle.append(`<div class="" data-id-group="${group.id_grupo}" title="${group.nombre}">${group.nombre}</div>`);
                    });
                } else {
                    $('#groups-toggle').hide();
                }

                if (data.modules) {
                    $('#modules-toggle').show();
                    data.modules.forEach(module => {
                        modulesToggle.append(`<div class="draggable" data-id-module="${module.id_agente_modulo}" title="${module.nombre}">${module.nombre}</div>`);
                    });
                } else {
                    $('#modules-toggle').hide();
                }

                // Create draggable elements.
                $('.draggable').draggable({
                    revert: "invalid",
                    stack: ".draggable",
                    helper: "clone",
                });
            } else {
                console.error('NO DATA FOUND');
            }
        },
        error: function(data) {
            // console.error("Fatal error in AJAX call", data);
        }
    });
});

$(document).ready(function(){
    // Hide toggles.
    $('#agents-toggle').hide();
    $('#groups-toggle').hide();
    $('#modules-toggle').hide();


    // Create draggable elements.
    // $('.draggable').draggable({
    //     revert: "invalid",
    //     stack: ".draggable",
    //     helper: "clone",
    // });

    // Droppable options.
    var droppableOptions = {
        accept: ".draggable",
        hoverClass: "drops-hover",
        activeClass: "droppable-zone",
        drop: function(event, ui) {
            // Add new module.
            $(this).data('modules').push(ui.draggable.data('id-module'));

            var modulesByGraphs = [];
            $('#droppable-graphs > div').each(function (i, v) {
                var modulesTmp = $(v).data('modules');
                if (modulesTmp.length > 0) {
                    modulesByGraphs.push(modulesTmp)
                }
            });

            // Ajax.
            // $.ajax({
            //     method: "POST",
            //     url: 'ajax.php',
            //     data: {
            //         page: '',
            //         method: "",
            //     },
            //     dataType: "json",
            //     success: function(data) {
            //         if(data.result == 1){
            //             confirmDialog({
            //                 title: "<?php echo __('Update priority nodes'); ?>",
            //                 message: "<?php echo __('Successfully updated priority order nodes'); ?>",
            //                 hideCancelButton: true
            //             });
            //         } else {
            //             confirmDialog({
            //                 title: "<?php echo __('Update priority nodes'); ?>",
            //                 message: "<?php echo __('Could not be updated priority order nodes'); ?>",
            //                 hideCancelButton: true
            //             });
            //         }
            //     },
            //     error: function(data) {
            //         console.error("Fatal error in AJAX call", data);
            //     }
            // });

            // Create elements.
            createDroppableZones(droppableOptions, modulesByGraphs);
        },
    };

    // Set droppable zones.
    $('.droppable').droppable(droppableOptions);
});

function createDroppableZones(droppableOptions, modulesByGraphs) {
    // Translate.
    const dropHere = '<?php echo __('Drop here'); ?>';

    // Clear graph area.
    $('#droppable-graphs').empty();

    // example graph modules
    modulesByGraphs.slice().reverse().forEach(graph => {
        // Create graph div.
        var graphDiv = $(`<div class="droppable" data-modules="[${graph}]"></div>`);
        $("#droppable-graphs").prepend(graphDiv);

        // todo: Print graph
        graph.forEach(module => {
            graphDiv.append($(`<div class="draggable ui-draggable ui-draggable-handle">${module}</div>`));
        });

        // Create next droppable zone.
        graphDiv.after($(`<div class="droppable droppable-default-zone droppable-new" data-modules="[]"><span class="drop-here">${dropHere}<span></div>`));
    
    });

    // Create first droppable zones and graphs.
    $("#droppable-graphs").prepend($(`<div class="droppable droppable-default-zone droppable-new" data-modules="[]"><span class="drop-here">${dropHere}<span></div>`));

    // Set droppable zones.
    $('.droppable').droppable(droppableOptions);

    // todo: Create draggable graphs.
}

</script>