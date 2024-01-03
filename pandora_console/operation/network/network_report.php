<?php
/**
 * Netflow Report
 *
 * @category   Netflow
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
check_login();

// ACL Check.
if (! check_acl($config['id_user'], 0, 'AR')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Network report.'
    );
    include 'general/noaccess.php';
    exit;
}

// Ajax callbacks.
if (is_ajax() === true) {
    include_once $config['homedir'].'/include/functions_netflow.php';
    $get_filter_values = get_parameter('get_filter_values', 0);
    $whois = (bool) get_parameter('whois', 0);

    // Get values of the current network filter.
    if ($get_filter_values) {
        $id = get_parameter('id');
        $filter_values = db_get_row_filter('tnetwork_explorer_filter', ['id' => $id]);
        // Decode HTML entities.
        $filter_values['advanced_filter'] = io_safe_output($filter_values['advanced_filter']);
        echo json_encode($filter_values);
    }

    if ($whois) {
        $ip = get_parameter('ip');
        $info = command_whois($ip);
        $output = '';
        if (is_array($info) === true && count($info) > 0) {
            $table = new \stdClass();
            $table->class = 'details_table dataTable info_table';
            $table->data = [];
            $row = 0;
            foreach ($info as $key => $value) {
                $table->data[$row][0] = $key;
                $table->data[$row][1] = $value;
                $row++;
            }

            $output = html_print_table($table, true);
        } else {
            $output = ui_print_info_message(__('No data found'));
        }

        html_print_div(
            [
                'content' => $output,
                'style'   => 'max-height: 600px;',
            ],
        );
    }

    return;
}

// Include JS timepicker.
ui_include_time_picker();


// Calculate range dates.
$date_end = get_parameter('date_end', 0);
$time_end = get_parameter('time_end');
$datetime_end = strtotime($date_end.' '.$time_end);

$custom_date = get_parameter('custom_date', 0);
$range = get_parameter('date', SECONDS_1DAY);
$date_text = get_parameter('date_text', SECONDS_1DAY);
$date_init_less = (strtotime(date('Y/m/d')) - SECONDS_1DAY);
$date_init = get_parameter('date_init', date(DATE_FORMAT, $date_init_less));
$time_init = get_parameter('time_init', date(TIME_FORMAT, $date_init_less));
$datetime_init = strtotime($date_init.' '.$time_init);
if ($custom_date === '1') {
    if ($datetime_init >= $datetime_end) {
        $datetime_init = $date_init_less;
    }

    $date_init = date('Y/m/d H:i:s', $datetime_init);
    $date_end = date('Y/m/d H:i:s', $datetime_end);
    $period = ($datetime_end - $datetime_init);
} else if ($custom_date === '2') {
    $date_units = get_parameter('date_units');
    $date_end = date('Y/m/d H:i:s');
    $date_init = date('Y/m/d H:i:s', (strtotime($date_end) - ((int) $date_text * (int) $date_units)));
    $period = (strtotime($date_end) - strtotime($date_init));
} else if (in_array($range, ['this_week', 'this_month', 'past_week', 'past_month'])) {
    if ($range === 'this_week') {
        $monday = date('Y/m/d', strtotime('last monday'));

        $sunday = date('Y/m/d', strtotime($monday.' +6 days'));
        $period = (strtotime($sunday) - strtotime($monday));
        $date_init = $monday;
        $date_end = $sunday;
    } else if ($range === 'this_month') {
        $date_end = date('Y/m/d', strtotime('last day of this month'));
        $first_of_month = date('Y/m/d', strtotime('first day of this month'));
        $date_init = $first_of_month;
        $period = (strtotime($date_end) - strtotime($first_of_month));
    } else if ($range === 'past_month') {
        $date_end = date('Y/m/d', strtotime('last day of previous month'));
        $first_of_month = date('Y/m/d', strtotime('first day of previous month'));
        $date_init = $first_of_month;
        $period = (strtotime($date_end) - strtotime($first_of_month));
    } else if ($range === 'past_week') {
        $date_end = date('Y/m/d', strtotime('sunday', strtotime('last week')));
        $first_of_week = date('Y/m/d', strtotime('monday', strtotime('last week')));
        $date_init = $first_of_week;
        $period = (strtotime($date_end) - strtotime($first_of_week));
    }
} else {
    $date_end = date('Y/m/d H:i:s');
    $date_init = date('Y/m/d H:i:s', (strtotime($date_end) - $range));
    $period = (strtotime($date_end) - strtotime($date_init));
}

$date_from = strtotime($date_init);
$date_to = strtotime($date_end);

$filter_id = (int) get_parameter('filter_id', 0);

// Query params and other initializations.
$utimestamp_greater = $date_to;
$utimestamp_lower = $date_from;

$top = (int) get_parameter('top', 10);
$main_value = ((bool) get_parameter('remove_filter', 0)) ? '' : get_parameter('main_value', '');
if (is_numeric($main_value) && !in_array($action, ['udp', 'tcp'])) {
    $main_value = '';
} else {
    $filter['ip'] = $main_value;
}

$advanced_filter = get_parameter('advanced_filter', '');
if ($advanced_filter !== '') {
    $filter['advanced_filter'] = $advanced_filter;
}

$filter_name = get_parameter('filter_name');

$order_by = get_parameter('order_by', 'bytes');
if (!in_array($order_by, ['bytes', 'pkts', 'flows'])) {
    $order_by = 'bytes';
}


$save = get_parameter('save_button', '');
$update = get_parameter('update_button', '');

// Save user defined filter.
if ($save != '' && check_acl($config['id_user'], 0, 'AW')) {
    // Save filter args.
    $data['filter_name'] = $filter_name;
    $data['top'] = $top;
    $data['action'] = $action;
    $data['advanced_filter'] = $advanced_filter;


    $filter_id = db_process_sql_insert('tnetwork_explorer_filter', $data);
    if ($filter_id === false) {
        $filter_id = 0;
        ui_print_error_message(__('Error creating filter'));
    } else {
        ui_print_success_message(__('Filter created successfully'));
    }
} else if ($update != '' && check_acl($config['id_user'], 0, 'AW')) {
    // Update current filter.
    // Do not update the filter name and group.
    $data['top'] = $top;
    $data['action'] = $action;
    $data['advanced_filter'] = $advanced_filter;

    $result = db_process_sql_update(
        'tnetwork_explorer_filter',
        $data,
        ['id' => $filter_id]
    );
    ui_print_result_message(
        $result,
        __('Filter updated successfully'),
        __('Error updating filter')
    );
}


// Build the table.
$filterTable = new stdClass();
$filterTable->id = '';
$filterTable->class = 'filter-table-adv';
$filterTable->size = [];
$filterTable->size[0] = '33%';
$filterTable->size[1] = '33%';
$filterTable->size[2] = '33%';
$filterTable->data = [];
$filterTable->data[0][0] = html_print_label_input_block(
    __('Results to show'),
    html_print_select(
        [
            '5'   => 5,
            '10'  => 10,
            '15'  => 15,
            '20'  => 20,
            '25'  => 25,
            '50'  => 50,
            '100' => 100,
            '250' => 250,
        ],
        'top',
        $top,
        '',
        '',
        0,
        true
    )
);

$filterTable->data[0][1] = html_print_label_input_block(
    __('Start date'),
    html_print_select_date_range('date', true)
);

$filterTable->data[1][0] = html_print_label_input_block(
    __('Data to show'),
    html_print_select(
        network_get_report_actions(),
        'action',
        $action,
        '',
        '',
        0,
        true
    )
);

$advanced_toggle = new stdClass();
$advanced_toggle->class = 'filter-table-adv';
$advanced_toggle->size = [];
$advanced_toggle->size[0] = '50%';
$advanced_toggle->size[1] = '50%';
$advanced_toggle->width = '100%';
$user_groups = users_get_groups($config['id_user'], 'AR', $own_info['is_admin'], true);
$user_groups[0] = 0;
// Add all groups.
$sql = 'SELECT * FROM tnetwork_explorer_filter';
$advanced_toggle->data[0][0] = html_print_label_input_block(
    __('Load Filter'),
    html_print_select_from_sql($sql, 'filter_id', $filter_id, '', __('Select a filter'), 0, true, false, true, false, 'width:100%;')
);
$advanced_toggle->data[0][1] = html_print_label_input_block(
    __('Filter name'),
    html_print_input_text('filter_name', $filter_name, false, 40, 45, true, false, false, '', 'w100p')
);
$advanced_toggle->colspan[1][0] = 2;
$advanced_toggle->data[1][0] = html_print_label_input_block(
    __('Filter').ui_print_help_icon('pcap_filter', true),
    html_print_textarea('advanced_filter', 4, 10, $advanced_filter, 'style="width:100%"', true)
);
$filterTable->colspan[2][0] = 3;
$filterTable->data[2][0] = html_print_label_input_block(
    '',
    ui_toggle(
        html_print_table($advanced_toggle, true),
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


$filterInputTable = '<form method="POST">';
$filterInputTable .= html_print_input_hidden('order_by', $order_by);
$filterInputTable .= html_print_table($filterTable, true);
$filterInputTable .= html_print_div(
    [
        'class'   => 'action-buttons-right-forced',
        'content' => html_print_submit_button(
            __('Filter'),
            'update',
            false,
            [
                'icon' => 'search',
                'mode' => 'mini',
            ],
            true
        ).html_print_submit_button(
            __('Save as new filter'),
            'save_button',
            false,
            [
                'icon'    => 'load',
                'onClick' => 'return defineFilterName();',
                'mode'    => 'mini secondary',
                'class'   => 'mrgn_right_10px',
            ],
            true
        ).html_print_submit_button(
            __('Update current filter'),
            'update_button',
            false,
            [
                'icon'  => 'load',
                'mode'  => 'mini secondary',
                'class' => 'mrgn_right_10px',
            ],
            true
        ),
    ],
    true
);
$filterInputTable .= html_print_div(
    [
        'class'   => 'action-buttons',
        'content' => $netflow_button,
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
html_print_action_buttons(
    html_print_submit_button(
        __('Export to CSV'),
        'export_csv',
        false,
        [
            'icon'    => 'load',
            'onclick' => 'blockResumit($(this))',
        ],
        true
    )
);
echo '</form>';

// Print the data.
$data = [];
$data = netflow_get_top_summary(
    $top,
    $action,
    $utimestamp_lower,
    $utimestamp_greater,
    $filter,
    $order_by
);

// Get the params to return the builder.
$hidden_main_link = [
    'custom_date' => get_parameter('custom_date', '0'),
    'date'        => get_parameter('date', SECONDS_1DAY),
    'date_init'   => get_parameter('date_init'),
    'time_init'   => get_parameter('time_init'),
    'date_end'    => get_parameter('date_end'),
    'time_end'    => get_parameter('time_end'),
    'date_text'   => get_parameter('date_text'),
    'date_units'  => get_parameter('date_units'),
    'top'         => $top,
    'action'      => $action,
];

unset($table);
$table = new stdClass();
$table->id = '';
$table->width = '100%';
$table->class = 'info_table';
// Print the header.
$table->head = [];
$table->head['main'] = __('IP');
$table->head['flows'] = network_print_explorer_header(
    __('Flows'),
    'flows',
    $order_by,
    array_merge(
        $hidden_main_link,
        ['main_value' => $main_value]
    )
);


$table->head['pkts'] = network_print_explorer_header(
    __('Packets'),
    'pkts',
    $order_by,
    array_merge(
        $hidden_main_link,
        ['main_value' => $main_value]
    )
);
$table->head['bytes'] = network_print_explorer_header(
    __('Bytes'),
    'bytes',
    $order_by,
    array_merge(
        $hidden_main_link,
        ['main_value' => $main_value]
    )
);

// Add the order.
$hidden_main_link['order_by'] = $order_by;

if (get_parameter('export_csv')) {
    // Clean the buffer.
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Set cookie for download control.
    setDownloadCookieToken();
    // Write metadata.
    header('Content-type: text/csv;');
    header('Content-Disposition: attachment; filename="network_data.csv"');

    $div = $config['csv_divider'];
    $nl = "\n";

    // Print the header.
    echo reset($table->head).$div;
    echo __('Flows').$div;

    echo __('Packets').$div;
    echo __('Bytes').$div;
    echo $nl;

    // Print the data.
    foreach ($data as $row) {
        echo $row['host'].$div;
        if (isset($row['sum_flows'])) {
            echo $row['sum_flows'].$div;
        }

        echo $row['sum_pkts'].$div;
        echo $row['sum_bytes'].$nl;
    }

    exit;
}

// Print the data and build the chart.
$table->data = [];
$chart_data = [];
$labels = [];
$hide_filter = !empty($main_value) && ($action === 'udp' || $action === 'tcp');
foreach ($data as $item) {
    $row = [];
    $row['main'] = '<div class="flex_center">';
    $row['main'] .= $item['host'];
    if (!$hide_filter) {
        $row['main'] .= html_print_link_with_params(
            'images/filters@svg.svg',
            array_merge($hidden_main_link, ['main_value' => $item['host']]),
            'image'
        );
        $row['main'] .= html_print_input_image('whois', 'images/eye.png', 'whois', '', true, ['onclick' => 'whois(\''.$item['host'].'\')']);
    }

    $row['main'] .= '</div>';
    $row['flows'] = format_for_graph($item['sum_flows'], 2);
    $row['flows'] .= ' ('.$item['pct_flows'].'%)';

    $row['pkts'] = format_for_graph($item['sum_pkts'], 2);
    $row['pkts'] .= ' ('.$item['pct_pkts'].'%)';

    $row['bytes'] = network_format_bytes($item['sum_bytes']);
    $row['bytes'] .= ' ('.$item['pct_bytes'].'%)';

    $table->data[] = $row;

    $labels[] = io_safe_output($item['host']);
    // Build the pie graph data structure.
    switch ($order_by) {
        case 'pkts':
            $chart_data[] = $item['sum_bytes'];
        break;

        case 'flows':
            $chart_data[] = $item['sum_flows'];
        break;

        case 'bytes':
        default:
            $chart_data[] = $item['sum_bytes'];
        break;
    }
}

if (empty($data)) {
    ui_print_info_message(__('No data found'));
} else {
    // Pie graph options.
    $options = [
        'height' => 230,
        'legend' => [
            'display'  => true,
            'position' => 'top',
            'align'    => 'left',
        ],
        'labels' => $labels,
    ];
    // Results table.
    $resultsTable = html_print_div(
        [
            'class'   => '',
            'style'   => 'flex: 75;margin-right: 5px;',
            'content' => html_print_table($table, true),
        ],
        true
    );
    // Pie graph.
    $pieGraph = html_print_div(
        [
            'class'   => 'databox netflow-pie-graph-container padding-2 white_box',
            'style'   => 'flex: 25;margin-left: 5px;',
            'content' => pie_graph(
                $chart_data,
                $options
            ),
        ],
        true
    );
    // Print the filter remove link.
    if (empty($main_value) === false) {
        echo html_print_link_with_params(
            in_array($action, ['udp', 'tcp']) ? __('Filtered by port %s. Click here to remove the filter.', $main_value) : __('Filtered by IP %s. Click here to remove the filter.', $main_value),
            array_merge(
                $hidden_main_link,
                [
                    'main_value'    => $main_value,
                    'remove_filter' => 1,
                ]
            ),
            'text',
            '',
            'width: 100%; display: flex; justify-content: center;'
        );
    }

    // Print results.
    html_print_div(
        [
            'id'      => 'content-netflow',
            'style'   => 'max-width: -webkit-fill-available; display: flex',
            'class'   => '',
            'content' => $resultsTable.$pieGraph,
        ]
    );
}

$spinner = html_print_div(
    [
        'content' => '<span></span>',
        'class'   => 'spinner-fixed inherit',
        'style'   => 'position: initial;',
    ],
    true
);
html_print_div(
    [
        'id'      => 'spinner',
        'content' => '<p class="loading-text">'.__('Loading netflow data, please wait...').'</p>'.$spinner,
        'class'   => 'invisible',
        'style'   => 'position: initial;',
    ]
);

html_print_div(
    [
        'id'    => 'modal_whois',
        'class' => 'invisible',
    ]
);
?>
<script>
$(document).ready(function(){
    $('#filter_id').change(function(){
        jQuery.post (
        "ajax.php",
        {
            "page" : "operation/network/network_report",
            "get_filter_values" : 1,
            "id": $(this).val(),
        },
        function (data) {
            $('#action').val(data.action).trigger('change');
            $('#top').val(data.top).trigger('change');
            $('#textarea_advanced_filter').val(data.advanced_filter);
            $('#text-filter_name').val(data.filter_name);
            $('select#filter_id').select2('close');
        }, 'json');
    });

    $('#button-update').on('click', function(){
        if ($('.info_box_information').length > 0) {
            $('.info_box_information').remove();
        }
        if ($('#content-netflow').length > 0) {
            $('#content-netflow').remove();
        }
        if ($('#spinner').length > 0) {
            $('#spinner').removeClass("invisible");
        }
        if ($('.link-with-params').length > 0) {
            $('.link-with-params').remove();
        }
    });

    $('.link-with-params').on('submit', function(e){
        setTimeout(() => {
            if ($('.info_box_information').length > 0) {
                $('.info_box_information').remove();
            }
            if ($('#content-netflow').length > 0) {
                $('#content-netflow').remove();
            }
            if ($('#spinner').length > 0) {
                $('#spinner').removeClass("invisible");
            }
            if ($('.link-with-params').length > 0) {
                $('.link-with-params').remove();
            }
        }, 100); // Prevent fields from being deleted before being sent.
    })
});

// Configure jQuery timepickers.
$("#text-time_lower, #text-time_greater").timepicker({
    showSecond: true,
    timeFormat: '<?php echo TIME_FORMAT_JS; ?>',
    timeOnlyTitle: '<?php echo __('Choose time'); ?>',
    timeText: '<?php echo __('Time'); ?>',
    hourText: '<?php echo __('Hour'); ?>',
    minuteText: '<?php echo __('Minute'); ?>',
    secondText: '<?php echo __('Second'); ?>',
    currentText: '<?php echo __('Now'); ?>',
    closeText: '<?php echo __('Close'); ?>'
});

$("#text-date_lower, #text-date_greater").datepicker({dateFormat: "<?php echo DATE_FORMAT_JS; ?>"});
$.datepicker.setDefaults($.datepicker.regional[ "<?php echo get_user_language(); ?>"]);

function network_report_click_period(event) {
    var is_period = document.getElementById(event.target.id).checked;

    document.getElementById('period_container').style.display = !is_period ? 'none' : 'block';
    document.getElementById('end_date_container').style.display = is_period ? 'none' : 'block';
}

function nf_view_click_period() {
    var is_period = document.getElementById('checkbox-is_period').checked;

    document.getElementById('period_container').style.display = !is_period ? 'none' : 'flex';
    document.getElementById('end_date_container').style.display = is_period ? 'none' : 'flex';
}

function whois(ip) {
    load_modal({
        target: $('#modal_whois'),
        url: '<?php echo ui_get_full_url('ajax.php', false, false, false); ?>',
        modal: {
            title: '<?php echo __('Details'); ?>',
            ok: '<?php echo __('Ok'); ?>',
        },
        extradata: [
            {
                name: "ip",
                value: ip,
            },
            {
                name: "whois",
                value: 1,
            }
        ],
        onshow: {
            page: '<?php echo $config['homedir'].'/operation/network/network_report'; ?>',
            width: 800,
        }
    });
}
</script>
