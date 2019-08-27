<?php
/**
 * Network explorer
 *
 * @package    Operations.
 * @subpackage Network explorer view.
 *
 * Pandora FMS - http://pandorafms.com
 * ==================================================
 * Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
 * Please see http://pandorafms.org for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */

check_login();

// ACL Check.
if (! check_acl($config['id_user'], 0, 'AR')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access Network report.'
    );
    include 'general/noaccess.php';
    exit;
}

// Include JS timepicker.
ui_include_time_picker();

// Query params and other initializations.
$time_greater = get_parameter('time_greater', date(TIME_FORMAT));
$date_greater = get_parameter('date_greater', date(DATE_FORMAT));
$utimestamp_greater = strtotime($date_greater.' '.$time_greater);
$is_period = (bool) get_parameter('is_period', false);
$period = (int) get_parameter('period', SECONDS_1HOUR);
$time_lower = get_parameter('time_lower', date(TIME_FORMAT, ($utimestamp_greater - $period)));
$date_lower = get_parameter('date_lower', date(DATE_FORMAT, ($utimestamp_greater - $period)));
$utimestamp_lower = ($is_period) ? ($utimestamp_greater - $period) : strtotime($date_lower.' '.$time_lower);
if (!$is_period) {
    $period = ($utimestamp_greater - $utimestamp_lower);
}

$top = (int) get_parameter('top', 10);
$main_value = ((bool) get_parameter('remove_filter', 0)) ? '' : get_parameter('main_value', '');
if (is_numeric($main_value) && !in_array($action, ['udp', 'tcp'])) {
    $main_value = '';
}

$order_by = get_parameter('order_by', 'bytes');
if (!in_array($order_by, ['bytes', 'pkts', 'flows'])) {
    $order_by = 'bytes';
}

$style_end = ($is_period) ? 'display: none;' : '';
$style_period = ($is_period) ? '' : 'display: none;';

// Build the table.
$table = new stdClass();
$table->class = 'databox filters';
$table->styleTable = 'width: 100%';
$table->data['0']['0'] = __('Data to show').'&nbsp;&nbsp;';
$table->data['0']['0'] .= html_print_select(
    network_get_report_actions($is_network),
    'action',
    $action,
    '',
    '',
    0,
    true
);

$table->data['0']['1'] = __('Number of result to show').'&nbsp;&nbsp;';
$table->data['0']['1'] .= html_print_select(
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
);

$table->data['0']['2'] = '';

$table->data['1']['0'] = '<div style="display: flex;">';
$table->data['1']['0'] .= '<div id="end_date_container" style="'.$style_end.'">';
$table->data['1']['0'] .= __('Start date').'&nbsp;&nbsp;';
$table->data['1']['0'] .= html_print_input_text('date_lower', $date_lower, '', 10, 7, true);
$table->data['1']['0'] .= '&nbsp;&nbsp;';
$table->data['1']['0'] .= html_print_input_text('time_lower', $time_lower, '', 7, 8, true);
$table->data['1']['0'] .= '</div>';

$table->data['1']['0'] .= '<div id="period_container" style="'.$style_period.'">';
$table->data['1']['0'] .= __('Time Period').'&nbsp;&nbsp;';
$table->data['1']['0'] .= html_print_extended_select_for_time('period', $period, '', '', 0, false, true);
$table->data['1']['0'] .= '</div>';
$table->data['1']['0'] .= html_print_checkbox(
    'is_period',
    1,
    ($is_period === true) ? 1 : 0,
    true,
    false,
    'network_report_click_period(event)'
);
$table->data['1']['0'] .= ui_print_help_tip(
    __('Select this checkbox to write interval instead a date.'),
    true
);
$table->data['1']['0'] .= '</div>';

$table->data['1']['1'] = __('End date').'&nbsp;&nbsp;';
$table->data['1']['1'] .= html_print_input_text('date_greater', $date_greater, '', 10, 7, true);
$table->data['1']['1'] .= '&nbsp;&nbsp;';
$table->data['1']['1'] .= html_print_input_text('time_greater', $time_greater, '', 7, 8, true);

$table->data['1']['2'] = html_print_submit_button(
    __('Update'),
    'update',
    false,
    'class="sub upd"',
    true
);
$table->data['1']['2'] .= '&nbsp;&nbsp;';
$table->data['1']['2'] .= html_print_submit_button(
    __('Export to CSV'),
    'export_csv',
    false,
    'class="sub next"',
    true
);

echo '<form method="post">';
html_print_input_hidden('order_by', $order_by);
if (!empty($main_value)) {
    html_print_input_hidden('main_value', $main_value);
}

html_print_table($table);
echo '</form>';

// Print the data.
$data = [];
if ($is_network) {
    $data = network_matrix_get_top(
        $top,
        $action === 'talkers',
        $utimestamp_lower,
        $utimestamp_greater,
        $main_value,
        $order_by !== 'pkts'
    );
} else {
    $data = netflow_get_top_summary(
        $top,
        $action,
        $utimestamp_lower,
        $utimestamp_greater,
        $main_value,
        $order_by
    );
}

// Get the params to return the builder.
$hidden_main_link = [
    'time_greater' => $time_greater,
    'date_greater' => $date_greater,
    'is_period'    => $is_period,
    'period'       => $period,
    'time_lower'   => $time_lower,
    'date_lower'   => $date_lower,
    'top'          => $top,
    'action'       => $action,
];

unset($table);
$table = new stdClass();
$table->styleTable = 'width: 60%';
// Print the header.
$table->head = [];
$table->head['main'] = __('IP');
if (!$is_network) {
    $table->head['flows'] = network_print_explorer_header(
        __('Flows'),
        'flows',
        $order_by,
        array_merge(
            $hidden_main_link,
            ['main_value' => $main_value]
        )
    );
}

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

    // Write metadata.
    header('Content-type: text/csv;');
    header('Content-Disposition: attachment; filename="network_data.csv"');

    $div = $config['csv_divider'];
    $nl = "\n";

    // Print the header.
    echo reset($table->head).$div;
    if (!$is_network) {
        echo __('Flows').$div;
    }

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

// Print the filter remove link.
if (!empty($main_value)) {
    echo html_print_link_with_params(
        in_array($action, ['udp', 'tcp']) ? __('Filtered by port %s. Click here to remove the filter.', $main_value) : __('Filtered by IP %s. Click here to remove the filter.', $main_value),
        array_merge(
            $hidden_main_link,
            [
                'main_value'    => $main_value,
                'remove_filter' => 1,
            ]
        )
    );
}

// Print the data and build the chart.
$table->data = [];
$chart_data = [];
$hide_filter = !empty($main_value) && ($action === 'udp' || $action === 'tcp');
foreach ($data as $item) {
    $row = [];
    $row['main'] = '<div class="div-v-centered">';
    $row['main'] .= $item['host'];
    if (!$hide_filter) {
        $row['main'] .= html_print_link_with_params(
            'images/filter.png',
            array_merge($hidden_main_link, ['main_value' => $item['host']]),
            'image'
        );
    }

    $row['main'] .= '</div>';
    if (!$is_network) {
        $row['flows'] = format_for_graph($item['sum_flows'], 2);
        $row['flows'] .= ' ('.$item['pct_flows'].'%)';
    }

    $row['pkts'] = format_for_graph($item['sum_pkts'], 2);
    if (!$is_network) {
        $row['pkts'] .= ' ('.$item['pct_pkts'].'%)';
    }

    $row['bytes'] = network_format_bytes($item['sum_bytes']);
    if (!$is_network) {
        $row['bytes'] .= ' ('.$item['pct_bytes'].'%)';
    }

    $table->data[] = $row;

    // Build the pie graph data structure.
    switch ($order_by) {
        case 'pkts':
            $chart_data[$item['host']] = $item['sum_bytes'];
        break;

        case 'flows':
            $chart_data[$item['host']] = $item['sum_flows'];
        break;

        case 'bytes':
        default:
            $chart_data[$item['host']] = $item['sum_bytes'];
        break;
    }
}

if (empty($data)) {
    ui_print_info_message(__('No data found'));
} else {
    echo '<div style="display: flex; margin-top: 10px;">';
    html_print_table($table);

    // Print the graph.
    echo '<div style="margin-top: 50px; width: 40%;">';
    echo pie_graph(
        $chart_data,
        320,
        200,
        __('Others')
    );
    echo '</div>';
    echo '</div>';
}

?>
<script>
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
</script>
