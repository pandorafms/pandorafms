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
$left_content .= '
    <div class="filters-div-header">
        <div></div>
        <img src="images/menu/contraer.svg">
    </div>
    <div class="filters-div-submain">
        <div class="filter-div filters-left-div">
            <input id="search-left" name="search-left" placeholder="Enter keywords to search" type="search" class="search-graph-analytics">
            <div class="draggable draggable1" data-id-module="1"></div>
            <div class="draggable draggable2" data-id-module="2"></div>
            <div class="draggable draggable3" data-id-module="3"></div>
            <br>
            '.ui_toggle('code', __('Agents'), 'agents-toggle', 'agents-toggle', true, true).'
            '.ui_toggle('code', __('Groups'), 'groups-toggle', 'groups-toggle', true, true).'
            '.ui_toggle('code', __('Modules'), 'modules-toggle', 'modules-toggle', true, true).'
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

                data.agents.forEach(agent => {
                    agentsToggle.append(`<div class="draggable draggable1" data-id-agent="${agent.id_agente}">${agent.alias}</div>`);
                });

                data.groups.forEach(group => {
                    groupsToggle.append(`<div class="draggable draggable2" data-id-group="${group.id_grupo}">${group.nombre}</div>`);
                });

                data.modules.forEach(module => {
                    modulesToggle.append(`<div class="draggable draggable3" data-id-module="${module.id_agente_modulo}">${module.nombre}</div>`);
                });

                // todo: position: static; div#tgl_div_...
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

    // Draggable & Droppable graphs.
    $('.draggable').draggable({
        revert: "invalid",
        stack: ".draggable",
        helper: "clone",
    });

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
            graphDiv.append($(`<div class="draggable draggable${module} ui-draggable ui-draggable-handle"></div>`));
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