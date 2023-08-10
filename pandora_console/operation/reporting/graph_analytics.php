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
    <div class="sortable-graphs-connected">
        <div data-id-module="6">
            <span class="handle-graph">6 </span>
            <img width="25" src="images/application_double.png">
        </div>
    </div>

    <div class="sortable-graphs-connected">
        <div data-id-module="7">
            <span class="handle-graph">7 </span>
            <img width="25" src="images/building.png">
        </div>
    </div>
';

$intervals = [];
$intervals[SECONDS_1HOUR]  = human_time_description_raw(SECONDS_1HOUR, true, 'large');
$intervals[SECONDS_6HOURS]  = human_time_description_raw(SECONDS_6HOURS, true, 'large');
$intervals[SECONDS_12HOURS] = human_time_description_raw(SECONDS_12HOURS, true, 'large');
$intervals[SECONDS_1DAY] = human_time_description_raw(SECONDS_1DAY, true, 'large');
$intervals[SECONDS_2DAY] = human_time_description_raw(SECONDS_2DAY, true, 'large');
$intervals[SECONDS_1WEEK] = human_time_description_raw(SECONDS_1WEEK, true, 'large');

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
    <div id="sortable-graphs" class="sortable-graphs-connected">
        <div data-id-module="1">
            <span class="handle-graph">1 </span>
            <img width="25" src="images/add.png">
        </div>
        <div data-id-module="2">
            <span class="handle-graph">2 </span>
            <img width="25" src="images/advanced.png">
        </div>
        <div data-id-module="3">
            <span class="handle-graph">3 </span>
            <img width="25" src="images/agent_notinit.png">
        </div>
        <div data-id-module="4">
            <span class="handle-graph">4 </span>
            <img width="25" src="images/alerts_command.png">
        </div>
        <div data-id-module="5">
            <span class="handle-graph">5 </span>
            <img width="25" src="images/alarm-off.png">
        </div>
    </div>
';

$filters_div = html_print_div(
    [
        'class'   => 'padding-div filters-div',
        'content' => $left_content,
    ],
    true
);

$graphs_div = html_print_div(
    [
        'class'   => 'padding-div graphs-div',
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
$(document).ready(function(){
    $(".sortable-graphs-connected").sortable({
        handle: '.handle-graph',
        placeholder: 'drop-zone-sortable',
        connectWith: ['.sortable-graphs-connected'],
        containment: '#sortable-graphs',
        opacity: 0.7,
        cursor: 'move',
        scrollSpeed: 10,
        revert: 300,
        start: function(event, ui) {
            // console.log('start');
            const divs = $('#sortable-graphs div');
            const sort = $(this).sortable('instance');

            ui.placeholder.height(ui.helper.height());

            divs.each(function(i, v) {
                sort.containment[i +1] += ui.helper.height() * 1 - sort.offset.click.bottom;
            });

            sort.containment[1] -= sort.offset.click.bottom;
        },
        stop: function(e, ui) {
            // console.log('stop');
            let data = [];
            $('#sortable-graphs div').each(function(i, v) {
                data.push($(this).data('id-module'));
            });

            console.log(data);

            // $.ajax({
                // method: "POST",
                // url: '',
                // data: {
                //     page: '',
                //     method: "updatePriorityNodes",
                //     order: data
                // },
                // dataType: "json",
                // success: function(data) {
                //     if(data.result == 1){
                //         confirmDialog({
                //             title: "<?php echo __('Update priority nodes'); ?>",
                //             message: "<?php echo __('Successfully updated priority order nodes'); ?>",
                //             hideCancelButton: true
                //         });
                //     } else {
                //         confirmDialog({
                //             title: "<?php echo __('Update priority nodes'); ?>",
                //             message: "<?php echo __('Could not be updated priority order nodes'); ?>",
                //             hideCancelButton: true
                //         });
                //     }
                // },
                // error: function(data) {
                //     console.error("Fatal error in AJAX call", data);
                // }
            // });
        },
    });
});
</script>