<?php
/**
 * Netflow live view
 *
 * @category   Netflow
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

global $config;

require_once $config['homedir'].'/include/functions_graph.php';
require_once $config['homedir'].'/include/functions_ui.php';
require_once $config['homedir'].'/include/functions_netflow.php';

ui_require_javascript_file('calendar');

// ACL.
check_login();
if (! check_acl($config['id_user'], 0, 'AR') && ! check_acl($config['id_user'], 0, 'AW')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access event viewer'
    );
    include 'general/noaccess.php';
    return;
}

$pure = get_parameter('pure', 0);

// Ajax callbacks.
if (is_ajax() === true) {
    $get_filter_type = get_parameter('get_filter_type', 0);
    $get_filter_values = get_parameter('get_filter_values', 0);

    // Get filter of the current netflow filter.
    if ($get_filter_type) {
        $id = get_parameter('id');

        $advanced_filter = db_get_value_filter('advanced_filter', 'tnetflow_filter', ['id_sg' => $id]);

        if (empty($advanced_filter)) {
            $type = 0;
        } else {
            $type = 1;
        }

        echo $type;
    }

    // Get values of the current netflow filter.
    if ($get_filter_values) {
        $id = get_parameter('id');

        $filter_values = db_get_row_filter('tnetflow_filter', ['id_sg' => $id]);

        // Decode HTML entities.
        $filter_values['advanced_filter'] = io_safe_output($filter_values['advanced_filter']);


        echo json_encode($filter_values);
    }

    return;
}

// Read filter configuration.
$filter_id = (int) get_parameter('filter_id', 0);
$filter['id_name'] = get_parameter('new_filter_name', '');
$filter['id_group'] = (int) get_parameter('assign_group', 0);
$filter['aggregate'] = get_parameter('aggregate', '');
$filter['ip_dst'] = get_parameter('ip_dst', '');
$filter['ip_src'] = get_parameter('ip_src', '');
$filter['dst_port'] = get_parameter('dst_port', '');
$filter['src_port'] = get_parameter('src_port', '');
$filter['advanced_filter'] = get_parameter('advanced_filter', '');
$filter['netflow_monitoring'] = (bool) get_parameter('netflow_monitoring');
$filter['netflow_monitoring_interval'] = (int) get_parameter('netflow_monitoring_interval', 300);
$filter['traffic_max'] = get_parameter('traffic_max', 0);
$filter['traffic_critical'] = get_parameter('traffic_critical', 0);
$filter['traffic_warning'] = get_parameter('traffic_warning', 0);


// Read chart configuration.
$chart_type = get_parameter('chart_type', 'netflow_area');
$max_aggregates = (int) get_parameter('max_aggregates', 10);
$update_date = (int) get_parameter('update_date', 0);
$connection_name = get_parameter('connection_name', '');
$interval_length = get_parameter('interval_length', NETFLOW_RES_MEDD);
$address_resolution = (int) get_parameter('address_resolution', ($config['netflow_get_ip_hostname'] ?? ''));
$filter_selected = (int) get_parameter('filter_selected', 0);

// Read time values.
$date = get_parameter_post('date', date(DATE_FORMAT, get_system_time()));
$time = get_parameter_post('time', date(TIME_FORMAT, get_system_time()));
$end_date = strtotime($date.' '.$time);
$is_period = (bool) get_parameter('is_period', false);
$period = (int) get_parameter('period', SECONDS_1DAY);
$time_lower = get_parameter('time_lower', date(TIME_FORMAT, ($end_date - $period)));
$date_lower = get_parameter('date_lower', date(DATE_FORMAT, ($end_date - $period)));
$start_date = ($is_period) ? ($end_date - $period) : strtotime($date_lower.' '.$time_lower);
if (!$is_period) {
    $period = ($end_date - $start_date);
} else {
    $time_lower = date(TIME_FORMAT, $start_date);
    $date_lower = date(DATE_FORMAT, $start_date);
}

// Read buttons.
$draw = get_parameter('draw_button', '');
$save = get_parameter('save_button', '');
$update = get_parameter('update_button', '');

// Header.
ui_print_standard_header(
    __('Netflow live view'),
    'images/op_netflow.png',
    false,
    '',
    false,
    [],
    [
        [
            'link'  => '',
            'label' => __('Monitoring'),
        ],
        [
            'link'  => '',
            'label' => __('Network'),
        ],
    ]
);

$is_windows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
if ($is_windows === true) {
    ui_print_error_message(__('Not supported in Windows systems'));
} else {
    netflow_print_check_version_error();
}

// Save user defined filter.
if ($save != '' && check_acl($config['id_user'], 0, 'AW')) {
    // Save filter args.
    $filter['filter_args'] = netflow_get_filter_arguments($filter, true);

    if ($filter['id_name'] === '') {
        $filter['id_name'] = 'Netflow_Filter_'.time();
    }

    $filter_id = db_process_sql_insert('tnetflow_filter', $filter);
    if ($filter_id === false) {
        $filter_id = 0;
        ui_print_error_message(__('Error creating filter'));
    } else {
        ui_print_success_message(__('Filter created successfully'));
    }
} else if ($update != '' && check_acl($config['id_user'], 0, 'AW')) {
    // Update current filter.
    // Do not update the filter name and group.
    $filter_copy = $filter;
    unset($filter_copy['id_name']);
    unset($filter_copy['id_group']);

    // Save filter args.
    $filter_copy['filter_args'] = netflow_get_filter_arguments($filter_copy, true);

    $result = db_process_sql_update(
        'tnetflow_filter',
        $filter_copy,
        ['id_sg' => $filter_id]
    );
    ui_print_result_message(
        $result,
        __('Filter updated successfully'),
        __('Error updating filter')
    );
}


// The filter name will not be needed anymore.
$filter['id_name'] = '';

$netflow_disable_custom_lvfilters = false;
if (isset($config['netflow_disable_custom_lvfilters'])) {
    $netflow_disable_custom_lvfilters = $config['netflow_disable_custom_lvfilters'];
}

// Add nodes list.
if (is_metaconsole() === true) {
    $list_servers = [];
    $servers = db_get_all_rows_sql(
        'SELECT *
			FROM tmetaconsole_setup'
    );
    if ($servers === false) {
        $servers = [];
    }

    foreach ($servers as $server) {
        // If connection was good then retrieve all data server.
        if (metaconsole_load_external_db($server)) {
            $connection = true;
        } else {
            $connection = false;
        }

        $row = db_get_row('tconfig', 'token', 'activate_netflow');


        if ($row['value']) {
            $list_servers[$server['server_name']] = $server['server_name'];
        }

        metaconsole_restore_db();
    }

    $nodeListInput = html_print_label_input_block(
        __('Connection'),
        html_print_select(
            $list_servers,
            'connection_name',
            $connection_name,
            '',
            '',
            0,
            true,
            false,
            false
        )
    );
} else {
    $nodeListInput = '';
}

$class_not_period = ($is_period === true) ? 'nf_hidden' : 'nf_display';
$class_period = ($is_period === true) ? 'nf_display' : 'nf_hidden';

$max_values = [
    '2'             => '2',
    '5'             => '5',
    '10'            => '10',
    '15'            => '15',
    '20'            => '20',
    '25'            => '25',
    '50'            => '50',
    $max_aggregates => $max_aggregates,
];

$aggregate_list = [];
$aggregate_list = [
    'srcip'   => __('Src Ip Address'),
    'dstip'   => __('Dst Ip Address'),
    'srcport' => __('Src Port'),
    'dstport' => __('Dst Port'),
];

$advanced_toggle = '<table style="width:100%">';
$advanced_toggle .= '<tr>';
if ($netflow_disable_custom_lvfilters) {
    $advanced_toggle .= '<td></td>';
    $advanced_toggle .= '<td></td>';
} else {
    $advanced_toggle .= '<td><b>'.__('Filter').'</b></td>';
    $advanced_toggle .= '<td colspan="2">'.__('Normal').' '.html_print_radio_button_extended('filter_type', 0, '', $filter_type, false, 'displayNormalFilter();', 'style="margin-right: 40px;"', true).__('Custom').' '.html_print_radio_button_extended('filter_type', 1, '', $filter_type, false, 'displayAdvancedFilter();', 'style="margin-right: 40px;"', true).'</td>';
}

$advanced_toggle .= '<td><b>'.__('Load filter').'</b></td>';
$user_groups = users_get_groups($config['id_user'], 'AR', $own_info['is_admin'], true);
$user_groups[0] = 0;
// Add all groups.
$sql = 'SELECT *
    FROM tnetflow_filter
    WHERE id_group IN ('.implode(',', array_keys($user_groups)).')';
$advanced_toggle .= "<td colspan='3'>".html_print_select_from_sql($sql, 'filter_id', $filter_id, '', __('Select a filter'), 0, true);
$advanced_toggle .= html_print_input_hidden('filter_selected', $filter_selected, false);
$advanced_toggle .= '</td>';
$advanced_toggle .= '</tr>';

$advanced_toggle .= "<tr class='filter_normal'>";
if ($netflow_disable_custom_lvfilters) {
    $advanced_toggle .= '<td></td>';
    $advanced_toggle .= '<td></td>';
} else {
    $advanced_toggle .= "<td style='font-weight:bold;'>".__('Dst Ip').ui_print_help_tip(__('Destination IP. A comma separated list of destination ip. If we leave the field blank, will show all ip. Example filter by ip:<br>25.46.157.214,160.253.135.249'), true).'</td>';
    $advanced_toggle .= '<td colspan="2">'.html_print_input_text('ip_dst', $filter['ip_dst'], false, 40, 80, true).'</td>';
}

if ($netflow_disable_custom_lvfilters) {
    $advanced_toggle .= '<td></td>';
    $advanced_toggle .= '<td></td>';
} else {
    $advanced_toggle .= "<td style='font-weight:bold;'>".__('Src Ip').ui_print_help_tip(__('Source IP. A comma separated list of source ip. If we leave the field blank, will show all ip. Example filter by ip:<br>25.46.157.214,160.253.135.249'), true).'</td>';
    $advanced_toggle .= '<td colspan="2">'.html_print_input_text('ip_src', $filter['ip_src'], false, 40, 80, true).'</td>';
}

$advanced_toggle .= '</tr>';

$advanced_toggle .= "<tr class='filter_normal'>";
if ($netflow_disable_custom_lvfilters) {
    $advanced_toggle .= '<td></td>';
    $advanced_toggle .= '<td></td>';
} else {
    $advanced_toggle .= "<td style='font-weight:bold;'>".__('Dst Port').ui_print_help_tip(__('Destination port. A comma separated list of destination ports. If we leave the field blank, will show all ports. Example filter by ports 80 and 22:<br>80,22'), true).'</td>';
    $advanced_toggle .= '<td colspan="2">'.html_print_input_text('dst_port', $filter['dst_port'], false, 40, 80, true).'</td>';
}

if ($netflow_disable_custom_lvfilters) {
    $advanced_toggle .= '<td></td>';
    $advanced_toggle .= '<td></td>';
} else {
    $advanced_toggle .= "<td style='font-weight:bold;'>".__('Src Port').ui_print_help_tip(__('Source port. A comma separated list of source ports. If we leave the field blank, will show all ports. Example filter by ports 80 and 22:<br>80,22'), true).'</td>';
    $advanced_toggle .= '<td colspan="2">'.html_print_input_text('src_port', $filter['src_port'], false, 40, 80, true).'</td>';
}

$advanced_toggle .= '</tr>';

$advanced_toggle .= "<tr class='filter_advance' style='display: none;'>";
if ($netflow_disable_custom_lvfilters) {
    $advanced_toggle .= '<td></td>';
    $advanced_toggle .= '<td></td>';
} else {
    $advanced_toggle .= '<td>'.ui_print_help_icon('pcap_filter', true).'</td>';
    $advanced_toggle .= "<td colspan='5'>".html_print_textarea('advanced_filter', 4, 40, $filter['advanced_filter'], "style='min-height: 0px; width: 90%;'", true).'</td>';
}

$advanced_toggle .= '</tr>';
$advanced_toggle .= '<tr>';

$onclick = "if (!confirm('".__('Warning').'. '.__('IP address resolution can take a lot of time')."')) return false;";
$radio_buttons = __('Yes').'&nbsp;&nbsp;'.html_print_radio_button_extended(
    'address_resolution',
    1,
    '',
    $address_resolution,
    false,
    $onclick,
    '',
    true
).'&nbsp;&nbsp;&nbsp;';
$radio_buttons .= __('No').'&nbsp;&nbsp;'.html_print_radio_button(
    'address_resolution',
    0,
    '',
    $address_resolution,
    true
);
$advanced_toggle .= '<td><b>'.__('IP address resolution').'</b>'.ui_print_help_tip(__('Resolve the IP addresses to get their hostnames.'), true).'</td>';
$advanced_toggle .= '<td colspan="2">'.$radio_buttons.'</td>';

$advanced_toggle .= '<td><b>'.__('Source ip').'</b></td>';
$advanced_toggle .= '<td colspan="2">'.html_print_input_text('router_ip', $filter['router_ip'], false, 40, 80, true).'</td>';

$advanced_toggle .= '</tr>';

// Netflow server options.
$advanced_toggle .= '<tr>';

$advanced_toggle .= "<td style='font-weight:bold;'>".__('Enable Netflow monitoring').ui_print_help_tip(__('Allows you to create an agent that monitors the traffic volume of this filter. It also creates a module that measures if the traffic of any IP of this filter exceeds a certain threshold. A text type module will be created with the traffic rate for each IP within this filter every five minutes (the 10 IP\'s with the most traffic). Only available for Enterprise version.'), true).'</td>';
$advanced_toggle .= '<td colspan="2">'.html_print_checkbox_switch(
    'netflow_monitoring',
    1,
    (bool) $filter['netflow_monitoring'],
    true,
    false,
    'displayMonitoringFilter()',
).'</td>';

$advanced_toggle .= '<td><b>'.__('New filter name').'</b></td>';
$advanced_toggle .= '<td>'.html_print_input_text('new_filter_name', '', false, 40, 80, true).'</td>';

$advanced_toggle .= '<tr id="netlofw_monitoring_filters">';
$advanced_toggle .= "<td style='font-weight:bold;'>".__('Netflow monitoring interval').ui_print_help_tip(__('Netflow monitoring interval in secs.'), true).'</td>';
$advanced_toggle .= '<td colspan="2">'.html_print_input_number(
    [
        'step'  => 1,
        'name'  => 'netflow_monitoring_interval',
        'id'    => 'netflow_monitoring_interval',
        'value' => $filter['netflow_monitoring_interval'],
    ]
).'</td>';

$advanced_toggle .= "<td style='font-weight:bold;'>".__('Maximum traffic value of the filter').ui_print_help_tip(__('Specifies the maximum rate (in bytes/sec) of traffic in the filter. It is then used to calculate the % of maximum traffic per IP.'), true).'</td>';
$advanced_toggle .= '<td colspan="2">'.html_print_input_number(
    [
        'step'  => 1,
        'name'  => 'traffic_max',
        'id'    => 'traffic_max',
        'value' => $filter['traffic_max'],
    ]
).'</td>';


$advanced_toggle .= '</tr>';
$advanced_toggle .= '<tr id="netlofw_monitoring_thresholds">';

$advanced_toggle .= "<td style='font-weight:bold;'>".__('CRITICAL threshold for the maximum % of traffic for an IP.').ui_print_help_tip(__('If this % is exceeded by any IP within the filter, a CRITICAL status will be generated.'), true).'</td>';
$advanced_toggle .= '<td colspan="2">'.html_print_input_number(
    [
        'step'  => 0.01,
        'name'  => 'traffic_critical',
        'id'    => 'traffic_critical',
        'value' => $filter['traffic_critical'],
    ]
).'</td>';

$advanced_toggle .= "<td style='font-weight:bold;'>".__('WARNING threshold for the maximum % of traffic of an IP.').ui_print_help_tip(__('If this % is exceeded by any IP within the filter, a WARNING status will be generated.'), true).'</td>';
$advanced_toggle .= '<td colspan="2">'.html_print_input_number(
    [
        'step'  => 0.01,
        'name'  => 'traffic_warning',
        'id'    => 'traffic_warning',
        'value' => $filter['traffic_warning'],
    ]
).'</td>';


$advanced_toggle .= '</tr>';

$advanced_toggle .= '</table>';

// Read filter type.
if (empty($filter['advanced_filter']) === false) {
    $filter_type = 1;
} else {
    $filter_type = 0;
}

$filterTable = new stdClass();
$filterTable->id = '';
$filterTable->width = '100%';
$filterTable->class = 'filter-table-adv';
$filterTable->size = [];
$filterTable->size[0] = '33%';
$filterTable->size[1] = '33%';
$filterTable->size[2] = '33%';
$filterTable->data = [];

if (empty($nodeListInput) === false) {
    $filterTable->data[-1][] = $nodeListInput;
}

$filterTable->data[0][0] = html_print_label_input_block(
    __('Interval'),
    html_print_extended_select_for_time(
        'period',
        $period,
        '',
        '',
        0,
        false,
        true
    ),
    [ 'div_id' => 'period_container' ]
);

$filterTable->data[0][0] .= html_print_label_input_block(
    __('Start date'),
    html_print_div(
        [
            'class'   => '',
            'content' => html_print_input_text(
                'date_lower',
                $date_lower,
                false,
                13,
                10,
                true
            ).html_print_image(
                'images/calendar_view_day.png',
                true,
                [
                    'alt'   => 'calendar',
                    'class' => 'main_menu_icon invert_filter',
                ]
            ).html_print_input_text(
                'time_lower',
                $time_lower,
                false,
                10,
                8,
                true
            ),
        ],
        true
    ),
    [ 'div_id' => 'end_date_container' ]
);

$filterTable->data[0][1] = html_print_label_input_block(
    __('End date'),
    html_print_div(
        [
            'class'   => '',
            'content' => html_print_input_text(
                'date',
                $date,
                false,
                13,
                10,
                true
            ).html_print_image(
                'images/calendar_view_day.png',
                true,
                ['alt' => 'calendar']
            ).html_print_input_text(
                'time',
                $time,
                false,
                10,
                8,
                true
            ),
        ],
        true
    )
);

$filterTable->data[0][2] = html_print_label_input_block(
    __('Resolution'),
    html_print_select(
        netflow_resolution_select_params(),
        'interval_length',
        $interval_length,
        '',
        '',
        0,
        true,
        false,
        false
    ).ui_print_input_placeholder(
        __('The interval will be divided in chunks the length of the resolution.'),
        true
    )
);

$filterTable->data[1][] = html_print_label_input_block(
    __('Defined period'),
    html_print_checkbox_switch(
        'is_period',
        1,
        ($is_period === true) ? 1 : 0,
        true,
        false,
        'nf_view_click_period(event)'
    )
);

$filterTable->data[1][] = html_print_label_input_block(
    __('Type'),
    html_print_select(
        netflow_get_chart_types(),
        'chart_type',
        $chart_type,
        '',
        '',
        0,
        true
    )
);

$filterTable->data[1][] = html_print_label_input_block(
    __('Aggregated by'),
    html_print_select(
        $aggregate_list,
        'aggregate',
        $filter['aggregate'],
        '',
        '',
        0,
        true,
        false,
        true,
        '',
        false
    )
);

$filterTable->data[2][] = html_print_label_input_block(
    __('Max values'),
    html_print_div(
        [
            'class'   => '',
            'content' => html_print_select(
                $max_values,
                'max_aggregates',
                $max_aggregates,
                '',
                '',
                0,
                true
            ).html_print_anchor(
                [
                    'id'      => 'max_values',
                    'href'    => '#',
                    'onClick' => 'edit_max_value()',
                    'content' => html_print_image(
                        'images/edit.svg',
                        true,
                        [
                            'id'    => 'pencil',
                            'class' => 'main_menu_icon invert_filter',
                        ]
                    ),
                ],
                true
            ),
        ],
        true
    )
);

$filterTable->colspan[3][0] = 3;
$filterTable->data[3][0] = html_print_label_input_block(
    '',
    ui_toggle(
        $advanced_toggle,
        __('Advanced'),
        '',
        '',
        true,
        true,
        '',
        'white-box-content',
        'box-flat white_table_graph'
    )
);

$buttons = html_print_submit_button(
    __('Draw'),
    'draw_button',
    false,
    [
        'icon' => 'cog',
        'mode' => 'mini',
    ],
    true
);

if (!$netflow_disable_custom_lvfilters) {
    if ((bool) check_acl($config['id_user'], 0, 'AW') === true) {
        $buttons .= html_print_submit_button(__('Save as new filter'), 'save_button', false, ['icon' => 'load', 'onClick' => 'return defineFilterName();', 'mode' => 'mini secondary'], true);
        $buttons .= html_print_submit_button(__('Update current filter'), 'update_button', false, ['icon' => 'load', 'mode' => 'mini secondary'], true);
    }
}

$filterInputTable = '<form method="post" action="'.$config['homeurl'].'index.php?sec=netf&sec2=operation/netflow/nf_live_view&pure='.$pure.'">';
$filterInputTable .= html_print_table($filterTable, true);
$filterInputTable .= html_print_div(
    [
        'class'   => 'action-buttons',
        'content' => $buttons,
    ],
    true
);
$filterInputTable .= '</form>';

ui_toggle(
    $filterInputTable,
    '<span class="subsection_header_title">'.__('Filter').'</span>',
    __('Filter'),
    'search',
    true,
    false,
    '',
    'white-box-content no_border',
    'box-flat white_table_graph fixed_filter_bar'
);

if (empty($draw) === false) {
    // No filter selected.
    if ($netflow_disable_custom_lvfilters && $filter_selected == 0) {
        ui_print_error_message(__('No filter selected'));
    } else {
        // Hidden input for handle properly the text colors.
        html_print_input_hidden(
            'selected_style_theme',
            $config['style']
        );

        $netflowContainerClass = ($chart_type === 'netflow_data' || $chart_type === 'netflow_summary' || $chart_type === 'netflow_top_N') ? '' : 'white_box';

        // Draw the netflow chart.
        html_print_div(
            [
                'class'   => $netflowContainerClass,
                'content' => netflow_draw_item(
                    $start_date,
                    $end_date,
                    $interval_length,
                    $chart_type,
                    $filter,
                    $max_aggregates,
                    $connection_name,
                    'HTML',
                    $address_resolution
                ),
            ]
        );
    }
} else {
    ui_print_info_message(__('No data to show'));
}

ui_include_time_picker();
?>

<style>
    .parent_graph {
        margin: 0 auto !important;
    }
</style>

<script type="text/javascript">
    function edit_max_value () {
        if ($("#max_values img").attr("id") == "pencil") {
            $("#max_values img").attr("src", "images/logs@svg.svg");
            $("#max_values img").attr("id", "select");
            var value = $("#max_aggregates").val();
            $("#max_aggregates").replaceWith("<input id='max_aggregates' name='max_aggregates' type='text'>");
            $("#max_aggregates").val(value);
        }
        else {
            $("#max_values img").attr("src", "images/edit.svg");
            $("#max_values img").attr("id", "pencil");
            $("#max_aggregates").replaceWith("<select id='max_aggregates' name='max_aggregates'>");
            var o = new Option("2", 2);
            var o1 = new Option("5", 5);
            var o2 = new Option("10", 10);
            var o3 = new Option("15", 15);
            var o4 = new Option("20", 20);
            var o5 = new Option("25", 25);
            var o6 = new Option("50", 50);
            $("#max_aggregates").append(o);
            $("#max_aggregates").append(o1);
            $("#max_aggregates").append(o2);
            $("#max_aggregates").append(o3);
            $("#max_aggregates").append(o4);
            $("#max_aggregates").append(o5);
            $("#max_aggregates").append(o6);
        }
        
    }

    // Hide the normal filter and display the advanced filter
    function displayAdvancedFilter () {
        // Erase the normal filter
        $("#text-ip_dst").val('');
        $("#text-ip_src").val('');
        $("#text-dst_port").val('');
        $("#text-src_port").val('');
        
        // Hide the normal filter
        $(".filter_normal").hide();
        
        // Show the advanced filter
        $(".filter_advance").show();
    };
    
    // Hide the advanced filter and display the normal filter
    function displayNormalFilter () {
        // Erase the advanced filter
        $("#textarea_advanced_filter").val('');
        
        // Hide the advanced filter
        $(".filter_advance").hide();
        
        // Show the normal filter
        $(".filter_normal").show();
    };

    function displayMonitoringFilter () {
        var checked = $('#checkbox-netflow_monitoring').prop('checked');

        if(checked == false) {
            // Reset values.
            $("#netflow_monitoring_interval").val(300);
            $("#traffic_max").val(0);
            $("#traffic_critical").val(0);
            $("#traffic_warning").val(0);

            // Hide filters.
            $("#netlofw_monitoring_filters").hide();
            $("#netlofw_monitoring_thresholds").hide();        
        } else {
            // Show filters.
            $("#netlofw_monitoring_filters").show();
            $("#netlofw_monitoring_thresholds").show();
        }
    }
    
    // Ask the user to define a name for the filter in order to save it
    function defineFilterName () {
        if ($("#text-name").val() == '') {
            $(".filter_save").show();
            
            return false;
        }
        
        return true;
    };

    // Display the appropriate filter
    var filter_type = <?php echo $filter_type; ?>;
    if (filter_type == 0) {
        displayNormalFilter ();
    }
    else {
        displayAdvancedFilter ();
    }
    
    $("#filter_id").change(function () {
        var filter_type;
        // Hide information and name/group row
        $(".filter_save").hide();
        
        // Clean fields
        if ($("#filter_id").val() == 0) {
            displayNormalFilter();
            
            // Check right filter type
            $("#radiobtn0001").attr("checked", "checked");
            
            $("#hidden-filter_selected").val(0);
            $("#text-ip_dst").val('');
            $("#text-ip_src").val('');
            $("#text-dst_port").val('');
            $("#text-src_port").val('');
            $("#text-router_ip").val('');
            $("#textarea_advanced_filter").val('');
            $("#aggregate").val('');
            $("#traffic_max").val('');
            $("#traffic_critical").val('');
            $("#traffic_warning").val('');
            $("#netflow_monitoring_interval").val(300);
            $('#checkbox-netflow_monitoring').prop('checked', false);
            
            
            // Hide update filter button
            $("#submit-update_button").hide();
            
        }
        else {
            // Load fields from DB
            $("#hidden-filter_selected").val(1);
            
            // Get filter type
            <?php
            if (! defined('METACONSOLE')) {
                echo 'jQuery.post ("ajax.php",';
            } else {
                echo 'jQuery.post ("'.$config['homeurl'].'../../ajax.php",';
            }
            ?>
                {"page" : "operation/netflow/nf_live_view",
                "get_filter_type" : 1,
                "id" : $("#filter_id").val()
                },
                function (data) {
                    filter_type = data;
                    // Display the appropriate filter
                    if (filter_type == 0) {
                        $(".filter_normal").show();
                        $(".filter_advance").hide();
                        
                        // Check right filter type
                        $("#radiobtn0001").attr("checked", "checked");
                    }
                    else {
                        $(".filter_normal").hide();
                        $(".filter_advance").show();
                        
                        // Check right filter type
                        $("#radiobtn0002").attr("checked", "checked");
                    }
                }
            // Get filter values from DB
            <?php
            echo ');';

            if (is_metaconsole() === false) {
                echo 'jQuery.post ("ajax.php",';
            } else {
                echo 'jQuery.post ("'.$config['homeurl'].'../../ajax.php",';
            }
            ?>
                {"page" : "operation/netflow/nf_live_view",
                "get_filter_values" : 1,
                "id" : $("#filter_id").val()
                },
                function (data) {
                    jQuery.each (data, function (i, val) {
                        if (i == 'ip_dst')
                            $("#text-ip_dst").val(val);
                        if (i == 'ip_src')
                            $("#text-ip_src").val(val);
                        if (i == 'dst_port')
                            $("#text-dst_port").val(val);
                        if (i == 'src_port')
                            $("#text-src_port").val(val);
                        if (i == 'router_ip')
                            $("#text-router_ip").val(val);
                        if (i == 'advanced_filter')
                            $("#textarea_advanced_filter").val(val);
                        if (i == 'aggregate')
                            $("#aggregate").val(val);
                        if (i == 'netflow_monitoring')
                            $("#checkbox-netflow_monitoring").prop('checked', val == "0" ? false : true);
                            // Hide or show monitoring filters.
                             displayMonitoringFilter();
                        if (i == 'netflow_monitoring_interval')
                            $("#netflow_monitoring_interval").val(val);
                        if (i == 'traffic_max')
                            $("#traffic_max").val(val);
                        if (i == 'traffic_critical')
                            $("#traffic_critical").val(val);
                        if (i == 'traffic_warning')
                            $("#traffic_warning").val(val);
                    });
                }
<?php echo ', "json");'; ?>

            // Shows update filter button
            $("#submit-update_button").show();
        
        }
        
    });
    
    $(document).ready( function() {
        displayMonitoringFilter();
        // Update visibility of controls.
        nf_view_click_period();
        // Hide update filter button
        if ($("#filter_id").val() == 0) {
            $("#submit-update_button").hide();
        }
        else {
            $("#submit-update_button").show();
        }
        
        // Change color of name and group if save button has been pushed
        $("#submit-save_button").click(function () {
            if ($("#text-name").val() == "") {
                $('#filter_name_color').css('color', '#CC0000');
                $('#filter_group_color').css('color', '#CC0000');
            }
            else {
                $('#filter_name_color').css('color', '#000000');
                $('#filter_group_color').css('color', '#000000');
            }
        });
    });
    
    $("#text-time, #text-time_lower").timepicker({
        showSecond: true,
        timeFormat: '<?php echo TIME_FORMAT_JS; ?>',
        timeOnlyTitle: '<?php echo __('Choose time'); ?>',
        timeText: '<?php echo __('Time'); ?>',
        hourText: '<?php echo __('Hour'); ?>',
        minuteText: '<?php echo __('Minute'); ?>',
        secondText: '<?php echo __('Second'); ?>',
        currentText: '<?php echo __('Now'); ?>',
        closeText: '<?php echo __('Close'); ?>'});
        
    $("#text-date, #text-date_lower").datepicker({dateFormat: "<?php echo DATE_FORMAT_JS; ?>"});
    
    $.datepicker.regional["<?php echo get_user_language(); ?>"];

    function nf_view_click_period() {
        var is_period = document.getElementById('checkbox-is_period').checked;

        document.getElementById('period_container').style.display = !is_period ? 'none' : 'flex';
        document.getElementById('end_date_container').style.display = is_period ? 'none' : 'flex';
    }
</script>
