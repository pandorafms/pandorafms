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
$x = (bool) get_parameter('x');

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

$left_content .= '
    <div class="filters-div-header">
        <div></div>
        <img src="images/menu/contraer.svg">
    </div>
    <div class="filters-div">
        <div class="draggable draggable1" data-id-module="1"></div>
        <div class="draggable draggable2" data-id-module="2"></div>
        <div class="draggable draggable3" data-id-module="3"></div>
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
        'class'   => 'padding-div filters-div-main',
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

$(document).ready(function(){
    // Draggable & Droppable graphs.
    $('.draggable').draggable({
        revert: "invalid",
        stack: ".draggable",
        helper: "clone",
    });

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
            //     url: '',
            //     data: {
            //         page: '',
            //         method: "updatePriorityNodes",
            //         order: data
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