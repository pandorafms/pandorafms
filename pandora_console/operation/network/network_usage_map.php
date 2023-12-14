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

// Ajax callbacks.
if (is_ajax() === true) {
    $get_filter_values = get_parameter('get_filter_values', 0);
    // Get values of the current network filter.
    if ($get_filter_values) {
        $id = get_parameter('id');
        $filter_values = db_get_row_filter('tnetwork_usage_filter', ['id' => $id]);
        // Decode HTML entities.
        $filter_values['advanced_filter'] = io_safe_output($filter_values['advanced_filter']);
        echo json_encode($filter_values);
    }

    return;
}

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
$default_date_netflow = false;
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
    $top = 10;
    $default_date_netflow = true;
}

$date_from = strtotime($date_init);
$date_to = strtotime($date_end);

$advanced_filter = get_parameter('advanced_filter', '');

$top = (int) get_parameter('top', 10);

$order_by = get_parameter('order_by', 'bytes');
if (in_array($order_by, ['bytes', 'pkts', 'flows']) === false) {
    $order_by = 'bytes';
}

$save = get_parameter('save_button', '');
$update = get_parameter('update_button', '');

// Save user defined filter.
if ($save != '' && check_acl($config['id_user'], 0, 'AW')) {
    // Save filter args.
    $data['filter_name'] = get_parameter('filter_name');
    $data['top'] = $top;
    $data['action'] = $action;
    $data['advanced_filter'] = $advanced_filter;


    $filter_id = db_process_sql_insert('tnetwork_usage_filter', $data);
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
        'tnetwork_usage_filter',
        $data,
        ['id' => $filter_id]
    );
    ui_print_result_message(
        $result,
        __('Filter updated successfully'),
        __('Error updating filter')
    );
}

if ((bool) $config['activate_netflow'] === true) {
    $netflow_button = html_print_submit_button(
        __('Show netflow map'),
        'update_netflow',
        false,
        ['icon' => 'update'],
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

$advanced_toggle = new stdClass();
$advanced_toggle->class = 'filter-table-adv';
$advanced_toggle->size = [];
$advanced_toggle->size[0] = '50%';
$advanced_toggle->size[1] = '50%';
$advanced_toggle->width = '100%';
$user_groups = users_get_groups($config['id_user'], 'AR', $own_info['is_admin'], true);
$user_groups[0] = 0;
$sql = 'SELECT * FROM tnetwork_usage_filter';
$advanced_toggle->data[0][0] = html_print_label_input_block(
    __('Load Filter'),
    html_print_select_from_sql($sql, 'filter_id', $filter_id, '', __('Select a filter'), 0, true, false, true, false, 'width:100%;')
);
$advanced_toggle->data[0][1] = html_print_label_input_block(
    __('Filter name'),
    html_print_input_text('filter_name', '', false, 40, 45, true, false, false, '', 'w100p')
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

if ((bool) get_parameter('update_netflow', 1) === true || $default_date_netflow === true) {
    $map_data = netflow_build_map_data(
        $date_from,
        $date_to,
        $top,
        ($action === 'talkers') ? 'srcip' : 'dstip',
        $advanced_filter
    );
    $has_data = !empty($map_data['nodes']);
}

if ($has_data === true) {
    $map_manager = new NetworkMap($map_data);
    $map_manager->printMap();
} else {
    ui_print_info_message(__('No data to show'));
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

?>
<style>
    .networkconsole {
        min-height: calc(100vh - 280px) !important;
    }
</style>
<script>

$(document).ready(function(){

    $('#filter_id').change(function(){
        jQuery.post (
        "ajax.php",
        {
            "page" : "operation/network/network_usage_map",
            "get_filter_values" : 1,
            "id": $(this).val(),
        },
        function (data) {
            $('#action').val(data.action).trigger('change');
            $('#top').val(data.top).trigger('change');
            $('#textarea_advanced_filter').val(data.advanced_filter);
            $('select#filter_id').select2('close');
        }, 'json');
    });

    $('#button-update_netflow').on('click', function(){
        $('.info_box_information').remove();
        $('.networkconsole').remove();
        $('#spinner').removeClass("invisible");
    });
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

