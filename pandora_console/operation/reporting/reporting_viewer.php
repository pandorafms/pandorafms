<?php

// Pandora FMS - https://pandorafms.com
// ==================================================
// Copyright (c) 2005-2023 Pandora FMS
// Please see https://pandorafms.com/community/ for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// Login check
global $config;

check_login();

$id_report = (int) get_parameter('id');

if (! $id_report) {
    db_pandora_audit(
        AUDIT_LOG_HACK_ATTEMPT,
        'Trying to access report viewer withoud ID'
    );
    include 'general/noaccess.php';
    return;
}

// Include with the functions to calculate each kind of report.
require_once $config['homedir'].'/include/functions_reporting.php';
require_once $config['homedir'].'/include/functions_reporting_html.php';
require_once $config['homedir'].'/include/functions_groups.php';
enterprise_include_once('include/functions_reporting.php');


if (!reporting_user_can_see_report($id_report)) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access report viewer'
    );
    include 'general/noaccess.php';
    exit;
}

$date_params = get_parameter_date('date', '', 'U');
$date_end = date('Y/m/d', $date_params['date_end']);
$time_end = date('H:i:s', $date_params['date_end']);

$date_start = date('Y/m/d', $date_params['date_init']);
$time_start = date('H:i:s', $date_params['date_init']);

$date_init = date('Y/m/d', $date_params['date_init']);
$time_init = date('H:i:s', $date_params['date_init']);

$custom_date_end = date('Y/m/d H:i:s', $date_params['date_end']);

$period = $date_params['period'];
$custom_period = $date_params['period'];

// Shchedule report email.
$schedule_report = get_parameter('schbutton', '');

if (empty($schedule_report) === false) {
    $id_user_task = 1;
    $scheduled = 'no';
    $date = date(DATE_FORMAT);
    $time = date(TIME_FORMAT);
    $parameters[0] = get_parameter('id_schedule_report');
    $parameters[1] = get_parameter('schedule_email_address');
    $parameters[2] = get_parameter('schedule_subject', '');
    $parameters[3] = get_parameter('schedule_email', '');
    $parameters[4] = get_parameter('report_type', '');
    $parameters['first_execution'] = strtotime($date.' '.$time);


    $values = [
        'id_usuario'   => $config['id_user'],
        'id_user_task' => $id_user_task,
        'args'         => serialize($parameters),
        'scheduled'    => $scheduled,
        'flag_delete'  => 1,
    ];

    $result = db_process_sql_insert('tuser_task_scheduled', $values);

    $report_type = $parameters[4];

    ui_print_result_message(
        $result,
        __('Your report has been planned, and the system will email you a '.$report_type.' file with the report as soon as its finished'),
        __('An error has ocurred')
    );
    echo '<br>';
}


// ------------------- INIT HEADER --------------------------------------
$url = "index.php?sec=reporting&sec2=operation/reporting/reporting_viewer&id=$id_report&date=$date&time=$time&pure=$pure";

$options = [];

$options['list_reports'] = [
    'active' => false,
    'text'   => '<a href="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&pure='.$pure.'&action=list">'.html_print_image(
        'images/report_list.png',
        true,
        [
            'title' => __('Reports'),
            'class' => 'main_menu_icon invert_filter',
        ]
    ).'</a>',
];

if ($id_report > 0) {
    $report_group = db_get_value(
        'id_group',
        'treport',
        'id_report',
        $id_report
    );
}

if (check_acl_restricted_all($config['id_user'], $report_group, 'RW')) {
    $options['main']['text'] = '<a href="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&tab=main&action=edit&id_report='.$id_report.'&pure='.$pure.'">'.html_print_image(
        'images/op_reporting.png',
        true,
        [
            'title' => __('Main data'),
            'class' => 'main_menu_icon invert_filter',
        ]
    ).'</a>';

    $options['list_items']['text'] = '<a href="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&tab=list_items&action=edit&id_report='.$id_report.'&pure='.$pure.'">'.html_print_image(
        'images/logs@svg.svg',
        true,
        [
            'title' => __('List items'),
            'class' => 'main_menu_icon invert_filter',
        ]
    ).'</a>';

    $options['item_editor']['text'] = '<a href="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&tab=item_editor&action=new&id_report='.$id_report.'&pure='.$pure.'">'.html_print_image(
        'images/edit.svg',
        true,
        [
            'title' => __('Item editor'),
            'class' => 'main_menu_icon invert_filter',
        ]
    ).'</a>';

    if (enterprise_installed()) {
        $options = reporting_enterprise_add_Tabs($options, $id_report);
    }
}

$options['view'] = [
    'active' => true,
    'text'   => '<a href="index.php?sec=reporting&sec2=operation/reporting/reporting_viewer&id='.$id_report.'&pure='.$pure.'">'.html_print_image(
        'images/see-details@svg.svg',
        true,
        [
            'title' => __('View report'),
            'class' => 'main_menu_icon invert_filter',

        ]
    ).'</a>',
];

if (!defined('METACONSOLE')) {
    if ($config['pure'] == 0) {
        $options['screen']['text'] = "<a href='$url&pure=1&enable_init_date=$enable_init_date&date_init=$date_init&time_init=$time_init'>".html_print_image(
            'images/full_screen.png',
            true,
            [
                'title' => __('Full screen mode'),
                'class' => 'main_menu_icon invert_filter',
            ]
        ).'</a>';
    } else {
        $options['screen']['text'] = "<a href='$url&pure=0&enable_init_date=$enable_init_date&date_init=$date_init&time_init=$time_init'>".html_print_image(
            'images/normal_screen.png',
            true,
            [
                'title' => __('Back to normal mode'),
                'class' => 'main_menu_icon invert_filter',
            ]
        ).'</a>';

        // In full screen, the manage options are not available
        $options = [
            'view'   => $options['view'],
            'screen' => $options['screen'],
        ];
    }
}

// Header.
ui_print_standard_header(
    reporting_get_name($id_report),
    'images/op_reporting.png',
    false,
    '',
    false,
    $options,
    [
        [
            'link'  => '',
            'label' => __('Reporting'),
        ],
        [
            'link'  => '',
            'label' => __('Custom reports'),
        ],
    ],
    [
        'id_element' => $id_report,
        'url'        => 'operation/reporting/reporting_viewer&id='.$id_report,
        'label'      => reporting_get_name($id_report),
        'section'    => 'Reporting',
    ]
);

// ------------------- END HEADER ---------------------------------------
// ------------------------ INIT FORM -----------------------------------
$table2 = new stdClass();
$table2->id = 'controls_table';
$table2->size[2] = '20%';
$table2->style[3] = 'position:absolute !important; left: auto !important;';
// $table2->style[3] = 'position:absolute !important; right: 1em !important;';
$table2->styleTable = 'border:none';

if (defined('METACONSOLE')) {
    $table2->width = '100%';
    $table2->class = 'databox filters filter-table-adv';

    $table2->head[0] = __('View Report');
    $table2->head_colspan[0] = 5;
    $table2->headstyle[0] = 'text-align: center';
}

// Set initial conditions for these controls, later will be modified by javascript
if (!$enable_init_date) {
    $display_to = 'none';
    $display_item = '';
} else {
    $display_to = '';
    $display_item = 'none';
}

$html_menu_export = enterprise_hook('reporting_print_button_export');
if ($html_menu_export === ENTERPRISE_NOT_HOOK) {
    $html_menu_export = '';
}

if ((bool) is_metaconsole() === true) {
    $table2->data[0][2] = html_print_label_input_block(
        __('Date').' ',
        html_print_select_date_range('date', true, get_parameter('date', 'none'), $date_init, $time_init, $date_end, $time_end, $date_text),
    );
} else {
    $table2->data[0][2] = html_print_label_input_block(
        __('Date').' ',
        html_print_select_date_range('date', true, get_parameter('date', 'none'), $date_init, $time_init, $date_end, $time_end, $date_text),
        ['label_class' => 'filter_label_position_before']
    );
}

$table2->data[0][3] = $html_menu_export;



$searchForm = '<form method="post" action="'.$url.'&pure='.$config['pure'].'" class="mrgn_right_0px">';
$searchForm .= html_print_table($table2, true);
$searchForm .= html_print_input_hidden('id_report', $id_report, true);
$Actionbuttons = '';

if ((bool) is_metaconsole() === true) {
    $Actionbuttons .= html_print_submit_button(
        __('Update'),
        'date_submit',
        false,
        [
            'mode'  => 'mini',
            'icon'  => 'next',
            'style' => 'position: absolute; top: 60px;',
        ],
        true
    );
} else {
    $Actionbuttons .= html_print_submit_button(
        __('Update'),
        'date_submit',
        false,
        [
            'mode'  => 'mini',
            'icon'  => 'next',
            'style' => 'position: absolute; top: 20px;',
        ],
        true
    );
}


$searchForm .= html_print_div(
    [
        'class'   => 'action-buttons',
        'content' => $Actionbuttons,
    ],
    true
);
$searchForm .= '</form>';

ui_toggle(
    $searchForm,
    '<span class="subsection_header_title">'.__('Filters').'</span>',
    'filter_form',
    '',
    true,
    false,
    '',
    'white-box-content',
    'box-flat white_table_graph fixed_filter_bar'
);
// ------------------------ END FORM ------------------------------------
if ($enable_init_date) {
    if ($datetime_init > $datetime_end) {
        ui_print_error_message(
            __('Invalid date selected. Initial date must be before end date.')
        );
    }
}

$report = reporting_make_reporting_data(
    null,
    $id_report,
    $date_end,
    $time,
    $period,
    'dinamic',
    null,
    null,
    false,
    false,
    $filter_type,
    $custom_date_end,
    $custom_period
);
for ($i = 0; $i < count($report['contents']); $i++) {
    $report['contents'][$i]['description'] = str_replace('&#x0d;&#x0a;', '<br/>', $report['contents'][$i]['description']);
}

reporting_html_print_report($report, false, $config['custom_report_info'], $custom_date_end, $custom_period);


echo '<div id="loading" class="center">';
echo html_print_image('images/wait.gif', true, ['border' => '0']);
echo '<strong>'.__('Loading').'...</strong>';
echo '</div>';

/*
 * We must add javascript here. Otherwise, the date picker won't
 * work if the date is not correct because php is returning.
 */

ui_include_time_picker();
ui_require_jquery_file('ui.datepicker-'.get_user_language(), 'include/javascript/i18n/');

db_pandora_audit(
    AUDIT_LOG_REPORT_MANAGEMENT,
    sprintf('Report visualized %s #%s.', $report['name'], $report['id_report']),
    false,
    false
);

?>
<script language="javascript" type="text/javascript">

$(document).ready (function () {
    
    $("#loading").slideUp ();
    $("#text-time").timepicker({
            showSecond: true,
            timeFormat: '<?php echo TIME_FORMAT_JS; ?>',
            timeOnlyTitle: '<?php echo __('Choose time'); ?>',
            timeText: '<?php echo __('Time'); ?>',
            hourText: '<?php echo __('Hour'); ?>',
            minuteText: '<?php echo __('Minute'); ?>',
            secondText: '<?php echo __('Second'); ?>',
            currentText: '<?php echo __('Now'); ?>',
            closeText: '<?php echo __('Close'); ?>'});

    $.datepicker.setDefaults($.datepicker.regional[ "<?php echo get_user_language(); ?>"]);


    /* Show/hide begin date reports controls */
    $("#checkbox-enable_init_date").click(function() {
        flag = $("#checkbox-enable_init_date").is(':checked');
        if (flag == true) {
            $("#string_to").show();
            $('#string_from').show();
            $("#string_items").hide();
        } else {
            $("#string_to").hide();
            $('#string_from').hide();
            $("#string_items").show();
        }
    });
    $('#div-report_export').addClass('div-report_export_filter');
    $('#button-export').addClass('button-export_filter ');
    $('#report_export_menu').removeClass('right');
});
</script>

<?php

