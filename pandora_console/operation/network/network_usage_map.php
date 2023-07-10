<?php
/**
 * Netflow usage map.
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
require_once $config['homedir'].'/include/functions_network.php';
require_once $config['homedir'].'/include/class/NetworkMap.class.php';

global $config;

check_login();

// Header.
ui_print_standard_header(
    __('Network usage map'),
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

// ACL Check.
if (! check_acl($config['id_user'], 0, 'AR')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Network usage map.'
    );
    include 'general/noaccess.php';
    exit;
}

// Include JS timepicker.
ui_include_time_picker();

// Query params and other initializations.
$action = get_parameter('action', 'talkers');

// Calculate range dates.
$custom_date = get_parameter('custom_date', '0');
$date = get_parameter('date', SECONDS_1DAY);
if ($custom_date === '1') {
    $date_init = get_parameter('date_init');
    $time_init = get_parameter('time_init');
    $date_end = get_parameter('date_end');
    $time_end = get_parameter('time_end');
    $date_from = strtotime($date_init.' '.$time_init);
    $date_to = strtotime($date_end.' '.$time_end);
} else if ($custom_date === '2') {
    $date_text = get_parameter('date_text');
    $date_units = get_parameter('date_units');
    $period = ($date_text * $date_units);
    $date_to = strtotime(date('Y-m-d H:i:s'));
    $date_from = (strtotime($date_to) - $period);
} else if (in_array($date, ['this_week', 'this_month', 'past_week', 'past_month'])) {
    if ($date === 'this_week') {
        $date_from = strtotime('last monday');
        $date_to = strtotime($date_from.' +6 days');
    } else if ($date === 'this_month') {
        $date_from = strtotime('first day of this month');
        $date_to = strtotime('last day of this month');
    } else if ($date === 'past_month') {
        $date_from = strtotime('first day of previous month');
        $date_to = strtotime('last day of previous month');
    } else if ($date === 'past_week') {
        $date_from = strtotime('monday', strtotime('last week'));
        $date_to = strtotime('sunday', strtotime('last week'));
    }
} else {
    $date_to = strtotime(date('Y-m-d H:i:s'));
    $date_from = ($date_to - $date);
}

$top = (int) get_parameter('top', 10);

$order_by = get_parameter('order_by', 'bytes');
if (in_array($order_by, ['bytes', 'pkts', 'flows']) === false) {
    $order_by = 'bytes';
}

if ((bool) $config['activate_netflow'] === true) {
    $netflow_button = html_print_submit_button(
        __('Show netflow map'),
        'update_netflow',
        false,
        ['icon' => 'update'],
        true
    );
} else {
    $netflow_button = '';
}


$filterTable = new stdClass();
$filterTable->id = '';
$filterTable->class = 'filter-table-adv';
$filterTable->size = [];
$filterTable->size[0] = '33%';
$filterTable->size[1] = '33%';
$filterTable->size[2] = '33%';
$filterTable->data = [];

$filterTable->data[0][0] = html_print_label_input_block(
    __('Date'),
    html_print_select_date_range('date', true)
);

$filterTable->data[0][1] = html_print_label_input_block(
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

$filterTable->data[0][2] = html_print_label_input_block(
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

$filterInputTable = '<form method="POST">';
$filterInputTable .= html_print_input_hidden('order_by', $order_by);
$filterInputTable .= html_print_table($filterTable, true);
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

$has_data = false;

if ((bool) get_parameter('update_netflow') === true) {
    $map_data = netflow_build_map_data(
        $date_from,
        $date_to,
        $top,
        ($action === 'talkers') ? 'srcip' : 'dstip'
    );
    $has_data = !empty($map_data['nodes']);
}

if ($has_data === true) {
    $map_manager = new NetworkMap($map_data);
    $map_manager->printMap();
} else {
    ui_print_info_message(__('No data to show'));
}

?>
<style>
    .networkconsole {
        min-height: calc(100vh - 280px) !important;
    }
</style>
<script>

    $(document).ready(function(){
        nf_view_click_period();
    }
    );
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

function nf_view_click_period() {
    var is_period = document.getElementById('checkbox-is_period').checked;

    document.getElementById('period_container').style.display = !is_period ? 'none' : 'flex';
    document.getElementById('end_date_container').style.display = is_period ? 'none' : 'flex';
}

</script>
<style type="text/css">
    tspan {
        font-size: 14px !important;
    }
</style>

