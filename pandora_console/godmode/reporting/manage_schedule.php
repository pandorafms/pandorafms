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
// Load global vars.
global $config;

// Check ACL.
if (!check_acl($config['id_user'], 0, 'RW')
    && !check_acl($config['id_user'], 0, 'RM')
) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access SNMP Filter Management'
    );
    include 'general/noaccess.php';
    return;
}

require 'include/functions_cron.php';

// Header.
ui_print_standard_header(
    __('Schedule'),
    'images/op_snmp.png',
    false,
    '',
    false,
    [],
    [
        [
            'link'  => '',
            'label' => __('Reporting'),
        ],
        [
            'link'  => '',
            'label' => __('Custom Reports'),
        ],
    ]
);

$id_task = get_parameter('id_task', null);
$name = '';
$id_report = '';
$task = '';
$group = 0;
$schedule = '';
$console = '';
$date = '';
$time = '';
$args = '';

if (isset($id_task) === true) {
    $row = db_get_row('tuser_task_scheduled', 'id', $id_task);
    $name = $row['name'];
    $id_report = $row['id_report'];
    $task = $row['id_user_task'];
    $group = $row['id_grupo'];
    $schedule = $row['scheduled'];
    $console = $row['id_console'];
    $args = unserialize($row['args']);
    $date = date('Y/m/d', $args['first_execution']);
    $time = date('H:i:s', $args['first_execution']);
}

$table = new stdClass();
$traps_generator = '<form id="form_manage" class="max_floating_element_size" method="POST" action="index.php?sec=custom_report&sec2=godmode/reporting/schedule">';
$table->id = 'table_manage';
$table->width = '100%';
$table->class = 'filter-table-adv databox';
$table->size = [];
$table->data = [];
$table->size[0] = '50%';
$table->size[1] = '50%';

$table->data[0][0] = html_print_label_input_block(
    __('Name'),
    html_print_input_text(
        'name',
        $name,
        '',
        50,
        255,
        true
    )
);

$reports = db_get_all_rows_sql('SELECT id_report, name FROM treport');
if ($reports !== false) {
    $array_reports = [];
    foreach ($reports as $row) {
        $array_reports[$row['id_report']] = $row['name'];
    }
}

$table->data[0][1] = html_print_label_input_block(
    __('Report'),
    html_print_select(
        $array_reports,
        'id_report',
        $id_report,
        '',
        __('Select'),
        -1,
        true,
        false,
        true,
        'w100p'
    )
);

// Remove Send csv log in list new console task.
$tasks = get_tasks();
// Unset to take just Reports ones.
unset($tasks[4], $tasks[5], $tasks[6], $tasks[7]);
if (($key = array_search('Send csv log', $tasks)) !== false) {
    unset($tasks[$key]);
}

$table->data[1][0] = html_print_label_input_block(
    __('Task'),
    html_print_select(
        $tasks,
        'id_user_task',
        $task,
        '',
        __('Select'),
        -1,
        true,
        false,
        false,
        '',
        false,
        'width: 100%'
    )
);

$table->data[1][1] = html_print_label_input_block(
    __('Group'),
    html_print_select_groups(
        false,
        'AR',
        true,
        'group',
        $group,
        '',
        '',
        '',
        true,
        false,
        true,
        '',
        false,
        'width: 100%',
        false,
        false,
        false,
        false,
        false,
        false,
        false,
        false,
        true
    )
);

$table->data[2][0] = html_print_label_input_block(
    __('Scheduled'),
    html_print_select(
        cron_get_scheduled_options(),
        'scheduled',
        $schedule,
        '',
        '',
        0,
        true,
        false,
        false,
        '',
        false,
        'width: 100%'
    )
);

$table->data[2][1] = html_print_label_input_block(
    __('Console'),
    html_print_select(
        $registered_consoles_opts,
        'console',
        $console,
        '',
        __('Any'),
        0,
        true,
        false,
        false,
        '',
        false,
        'width: 100%'
    )
);

$table->data[3][0] = html_print_label_input_block(
    __('Next execution'),
    html_print_input_text(
        'date',
        $date,
        '',
        50,
        255,
        true
    )
);

$table->data[3][1] = html_print_label_input_block(
    __('Hour'),
    html_print_input_text(
        'time',
        $time,
        '',
        50,
        255,
        true
    )
);
$table->colspan[4][0] = 2;
$table->data[4][0] = html_print_label_input_block(__('Parameters'), cron_render_parameters($task, $args, $id_report, true));
$traps_generator .= html_print_table($table, true);
if (isset($id_task) === true) {
    $buttons[] = html_print_submit_button(
        __('Update schedule'),
        'btn_generate_trap',
        false,
        [
            'class' => 'sub ok submitButton',
            'icon'  => 'next',
        ],
        true
    ).html_print_input_hidden('update_schedule', 1, true).html_print_input_hidden('id', $id_task, true);
} else {
    $buttons[] = html_print_submit_button(
        __('Create schedule'),
        'btn_generate_trap',
        false,
        [
            'class' => 'sub ok submitButton',
            'icon'  => 'next',
        ],
        true
    ).html_print_input_hidden('new_schedule', 1, true);
}

$buttons[] = html_print_button(
    __('Go back'),
    'button_back',
    false,
    '',
    [
        'icon' => 'back',
        'mode' => 'secondary',
    ],
    true
);
$traps_generator .= '<div class="action-buttons">'.html_print_action_buttons(implode('', $buttons), ['type' => 'form_action'], true).'</div>';

unset($table);
$traps_generator .= '</form>';

echo $traps_generator;

ui_require_css_file('datepicker');
ui_include_time_picker();
ui_require_jquery_file('ui.datepicker-'.get_user_language(), 'include/javascript/i18n/');

?>
<script type="text/javascript">
    $(document).ready(function() {
        $('#text-time').timepicker({
            showSecond: true,
            timeFormat: '<?php echo TIME_FORMAT_JS; ?>',
            timeOnlyTitle: '<?php echo __('Choose time'); ?>',
            timeText: '<?php echo __('Time'); ?>',
            hourText: '<?php echo __('Hour'); ?>',
            minuteText: '<?php echo __('Minute'); ?>',
            secondText: '<?php echo __('Second'); ?>',
            currentText: '<?php echo __('Now'); ?>',
            closeText: '<?php echo __('Close'); ?>'});

        $('#text-date').datepicker ({
            dateFormat: '<?php echo DATE_FORMAT_JS; ?>',
            changeMonth: true,
            changeYear: true,
            showAnim: 'slideDown',
            firstDay: "<?php echo $config['datepicker_first_day']; ?>",
        });

        $('#id_user_task, #id_report').on('change', function() {
            $.ajax({
                url: 'ajax.php',
                data: {
                    "page" : "enterprise/include/ajax/ConsoleTasks.ajax",
                    "get_task_parameters": 1,
                    "id_user_task": $('#id_user_task :selected').val(),
                    "id_report": $('#id_report :selected').val(),
                },
                type: 'POST',
                success: function (data) {
                    $('#table_manage-4-0').find('table').html("");
                    $('#table_manage-4-0').append(data);
                }
            });
        });

        $('#button-btn_generate_trap').on('click', function() {
            event.preventDefault();
            var name = $('#text-name').val();
            var report = $('#id_report :selected').val();
            var task = $('#id_user_task :selected').val();
            var group = $('#group :selected').val();
            if (name !== '' && report !== '-1' && task !== '-1' && group !== '') {
                $('#form_manage').submit();
            } else {
                confirmDialog({
                    title: "<?php echo __('Error'); ?>",
                    message: "<?php echo __('Name, Report, Task and Group are required.'); ?>",
                    hideCancelButton: true,
                });
            }
        })
    });

    $('#button-button_back').on('click', function(){
        window.location = '<?php echo ui_get_full_url('index.php?sec=custom_report&sec2=godmode/reporting/schedule'); ?>';
    });
</script>