<?php

/**
 * Netflow functions
 *
 * @package    Netflow usage map.
 * @subpackage UI.
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

require_once $config['homedir'].'/include/functions_network.php';
require_once $config['homedir'].'/include/class/NetworkMap.class.php';

global $config;

check_login();

ui_print_page_header(__('Network usage map'));

// ACL Check.
if (! check_acl($config['id_user'], 0, 'AR')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access Network usage map.'
    );
    include 'general/noaccess.php';
    exit;
}

// Include JS timepicker.
ui_include_time_picker();

// Query params and other initializations.
$action = get_parameter('action', 'talkers');
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

$table->data['0']['0'] = '<div style="display: flex;">';
$table->data['0']['0'] .= '<div id="end_date_container" style="'.$style_end.'">';
$table->data['0']['0'] .= __('Start date').'&nbsp;&nbsp;';
$table->data['0']['0'] .= html_print_input_text('date_lower', $date_lower, '', 10, 7, true);
$table->data['0']['0'] .= '&nbsp;&nbsp;';
$table->data['0']['0'] .= html_print_input_text('time_lower', $time_lower, '', 7, 8, true);
$table->data['0']['0'] .= '</div>';

$table->data['0']['0'] .= '<div id="period_container" style="'.$style_period.'">';
$table->data['0']['0'] .= __('Time Period').'&nbsp;&nbsp;';
$table->data['0']['0'] .= html_print_extended_select_for_time('period', $period, '', '', 0, false, true);
$table->data['0']['0'] .= '</div>';
$table->data['0']['0'] .= html_print_checkbox(
    'is_period',
    1,
    ($is_period === true) ? 1 : 0,
    true,
    false,
    'network_report_click_period(event)'
);
$table->data['0']['0'] .= ui_print_help_tip(
    __('Select this checkbox to write interval instead a date.'),
    true
);
$table->data['0']['0'] .= '</div>';

$table->data['0']['1'] = __('End date').'&nbsp;&nbsp;';
$table->data['0']['1'] .= html_print_input_text('date_greater', $date_greater, '', 10, 7, true);
$table->data['0']['1'] .= '&nbsp;&nbsp;';
$table->data['0']['1'] .= html_print_input_text('time_greater', $time_greater, '', 7, 8, true);

$table->data['0']['2'] = __('Number of result to show').'&nbsp;&nbsp;';
$table->data['0']['2'] .= html_print_select(
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


$table->data['1']['0'] = __('Data to show').'&nbsp;&nbsp;';
$table->data['1']['0'] .= html_print_select(
    network_get_report_actions(),
    'action',
    $action,
    '',
    '',
    0,
    true
);
$table->data['1']['1'] = '';

$netflow_button = '';
if ((bool) $config['activate_netflow'] === true) {
    $netflow_button = html_print_submit_button(
        __('Show netflow map'),
        'update_netflow',
        false,
        'class="sub upd"',
        true
    );
}

$nta_button = '';
if ((bool) $config['activate_nta'] === true) {
    $nta_button = html_print_submit_button(
        __('Show NTA map'),
        'update_nta',
        false,
        'class="sub upd"',
        true
    );
}

$table->data['1']['2'] .= implode(
    '&nbsp;&nbsp;',
    [
        $netflow_button,
        $nta_button,
    ]
);

echo '<form method="post">';
html_print_input_hidden('order_by', $order_by);

html_print_table($table);
echo '</form>';

$has_data = false;
$first_load = true;
if ((bool) get_parameter('update_netflow') === true) {
    $map_data = netflow_build_map_data(
        $utimestamp_lower,
        $utimestamp_greater,
        $top,
        ($action === 'talkers') ? 'srcip' : 'dstip'
    );
    $has_data = !empty($map_data['nodes']);
    $first_load = false;
} else if ((bool) get_parameter('update_nta') === true) {
    $map_data = network_build_map_data(
        $utimestamp_lower,
        $utimestamp_greater,
        $top,
        $action === 'talkers'
    );
    $has_data = !empty($map_data['nodes']);
    $first_load = false;
}

if ($has_data === true) {
    $map_manager = new NetworkMap($map_data);
    $map_manager->printMap();
} else if (!$first_load) {
    ui_print_info_message(__('No data retrieved'));
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

    document.getElementById('period_container').style.display = !is_period
        ? 'none'
        : 'block';
    document.getElementById('end_date_container').style.display = is_period
        ? 'none'
        : 'block';
}
</script>
<style type="text/css">
    tspan {
        font-size: 14px !important;
    }
</style>

