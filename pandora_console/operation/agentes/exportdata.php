<?php
/**
 * Export data.
 *
 * @category   Tools
 * @package    Pandora FMS
 * @subpackage Operation
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

// Load global vars
require_once 'include/config.php';
require_once 'include/functions_agents.php';
require_once 'include/functions_reporting.php';
require_once 'include/functions_modules.php';
require_once 'include/functions_users.php';

check_login();

if (!check_acl($config['id_user'], 0, 'RR')) {
    include 'general/noaccess.php';
    return;
}

ui_require_javascript_file('calendar');

// Header.
ui_print_standard_header(
    __('Export data'),
    'images/server_export_mc.png',
    false,
    '',
    false,
    [],
    [
        [
            'link'  => '',
            'label' => __('Tools'),
        ],
        [
            'link'  => '',
            'label' => __('Export data'),
        ],
    ]
);

$group = get_parameter_post('group', 0);
$agentName = get_parameter_post('agent', 0);
$agents = agents_get_agents(
    ['nombre LIKE "'.$agentName.'"'],
    ['id_agente']
);
$agent = $agents[0]['id_agente'];

$module = (array) get_parameter_post('module_arr', []);
$start_date = get_parameter_post('start_date', 0);
$end_date = get_parameter_post('end_date', 0);
$start_time = get_parameter_post('start_time', 0);
$end_time = get_parameter_post('end_time', 0);
$export_type = get_parameter_post('export_type', 'data');
$export_btn = get_parameter('export_btn', 0);

$show_form = false;

if (!empty($export_btn) && !empty($module)) {
    // Disable SQL cache
    global $sql_cache;
    $sql_cache = ['saved' => []];


    // Convert start time and end time to unix timestamps.
    // The date/time will have the user's timezone,
    // so we need to change it to the system's timezone.
    $fixed_offset = get_fixed_offset();
    $start = (strtotime($start_date.' '.$start_time) - $fixed_offset);
    $end = (strtotime($end_date.' '.$end_time) - $fixed_offset);
    $period = ($end - $start);
    $data = [];

    // If time is negative or zero, don't process - it's invalid
    if ($start < 1 || $end < 1) {
        ui_print_error_message(__('Invalid time specified'));
        return;
    }

    // ******************************************************************
    // Starts, ends and dividers
    // ******************************************************************
    switch ($export_type) {
        case 'data':
        case 'avg':
        default:
            // HTML output - don't style or use XHTML just in case somebody needs to copy/paste it. (Office doesn't handle <thead> and <tbody>)
            $datastart = '<table class="databox data w100p">'.'<tr>'.'<th>'.__('Agent').'</th>'.'<th>'.__('Module').'</th>'.'<th>'.__('Data').'</th>'.'<th>'.__('Timestamp').'</th>'.'</tr>';
            $rowstart = '<tr><td>';
            $divider = '</td><td>';
            $rowend = '</td></tr>';
            $dataend = '</table>';
        break;
    }

    // ******************************************************************
    // Data processing
    // ******************************************************************
    $data = [];
    switch ($export_type) {
        case 'data':
        case 'avg':
            // Show header
            echo $datastart;

            foreach ($module as $selected) {
                $output = '';
                $work_period = SECONDS_1DAY;
                if ($work_period > $period) {
                    $work_period = $period;
                }

                $work_end = ($end - $period + $work_period);
                $work_start = ($work_end - $work_period);
                // Buffer to get data, anyway this will report a memory exhaustin
                $flag_last_time_slice = false;
                while ($work_end <= $end) {
                    $data = [];
                    // Reinitialize array for each module chunk
                    if ($export_type == 'avg') {
                        $arr = [];
                        $arr['data'] = reporting_get_agentmodule_data_average(
                            $selected,
                            $work_period,
                            $work_end
                        );
                        if ($arr['data'] === false) {
                            $work_end = ($work_end + $work_period);
                            continue;
                        }

                        $arr['module_name'] = modules_get_agentmodule_name($selected);
                        $arr['agent_name'] = modules_get_agentmodule_agent_name($selected);
                        $arr['agent_id'] = modules_get_agentmodule_agent($selected);
                        $arr['utimestamp'] = $end;
                        array_push($data, $arr);
                    } else {
                        $data_single = modules_get_agentmodule_data(
                            $selected,
                            $work_period,
                            $work_end
                        );

                        if (!empty($data_single)) {
                            $data = array_merge($data, $data_single);
                        }
                    }



                    foreach ($data as $key => $module) {
                        $output .= $rowstart;
                        $alias = db_get_value('alias', 'tagente', 'id_agente', $module['agent_id']);
                        $output .= io_safe_output($alias);
                        $output .= $divider;
                        $output .= io_safe_output($module['module_name']);
                        $output .= $divider;
                        $output .= $module['data'];
                        $output .= $divider;
                        switch ($export_type) {
                            case 'data':
                                // Change from the system's timezone to the user's timezone
                                $output .= date('Y-m-d G:i:s', ($module['utimestamp'] + $fixed_offset));
                            break;

                            case 'avg':
                                // Change from the system's timezone to the user's timezone
                                $output .= date('Y-m-d G:i:s', ($work_start + $fixed_offset)).' - '.date('Y-m-d G:i:s', ($work_end + $fixed_offset));
                            break;
                        }

                        $output .= $rowend;
                    }

                    switch ($export_type) {
                        default:
                        case 'data':
                        case 'avg':
                            echo $output;
                        break;
                    }

                    unset($output);
                    $output = '';
                    unset($data);
                    unset($data_single);

                    // The last time slice is executed now exit of
                    // while loop
                    if ($flag_last_time_slice) {
                        break;
                    }

                    if (($work_end + $work_period) > $end || $work_period == 0) {
                        // Get the last timelapse
                        $work_period = ($end - $work_end);
                        $work_end = $end;
                        $flag_last_time_slice = true;
                    } else {
                        $work_end = ($work_end + $work_period);
                    }

                    $work_start = ($work_end - $work_period);
                }

                unset($output);
                $output = '';
            } //end foreach

            echo $dataend;
        break;
    }
} else if (!empty($export_btn) && empty($module)) {
    ui_print_error_message(__('No modules specified'));
    $show_form = true;
}

if (empty($export_btn) || $show_form) {
    $groups = users_get_groups($config['id_user'], 'RR', users_can_manage_group_all());

    $filter = [];
    $filter['id_grupo'] = ($group > 0) ? (array) $group : array_keys($groups);

    $agents = [];
    $rows = agents_get_agents($filter, false, 'RR');
    if ($rows == null) {
        $rows = [];
    }

    foreach ($rows as $row) {
        $agents[$row['id_agente']] = $row['nombre'];
    }

    // Src code of lightning image with skins.
    $src_code = html_print_image('images/lightning_go.png', true, false, true);

    $params = [];
    $params['return'] = true;
    $params['show_helptip'] = true;
    $params['input_name'] = 'agent';
    $params['selectbox_group'] = 'group';
    $params['value'] = agents_get_name($agent);
    $params['javascript_is_function_select'] = true;
    $params['add_none_module'] = false;
    $params['size'] = 38;
    $params['selectbox_id'] = 'module_arr';

    if ($agent > 0) {
        $modules = agents_get_modules($agent);
    } else {
        $modules = [];
    }

    if (empty($modules) === false) {
        // Remove modules of type string because you cant calculate their average.
        $i = 0;
        foreach ($modules as $key => $module) {
            $id_module_type = modules_get_agentmodule_type($key);
            switch ($id_module_type) {
                case 3:
                case 10:
                case 17:
                case 23:
                case 33:
                    unset($modules[$i]);
                break;

                default:
                continue;
            }

            $i++;
        }
    }

    $disabled_export_button = false;
    if (empty($modules) === true) {
        $disabled_export_button = true;
    }

    $export_types = [];
    $export_types['data'] = __('Data table');
    $export_types['csv'] = __('CSV');
    $export_types['excel'] = __('MS Excel');
    $export_types['avg'] = __('Average per hour/day');


    echo '<form method="post" action="index.php?sec=reporting&amp;sec2=operation/agentes/exportdata" name="export_form" id="export_form">';

    $table = new stdClass();
    $table->width = '100%';
    $table->border = 0;
    $table->cellspacing = 3;
    $table->cellpadding = 5;
    $table->class = 'databox filter-table-adv';
    $table->style[0] = 'vertical-align: top;';

    $table->data = [];

    // Group selector.
    $table->data[0][] = html_print_label_input_block(
        __('Group'),
        html_print_select_groups(
            $config['id_user'],
            'RR',
            true,
            'group',
            $group,
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

    // Agent selector.
    $table->data[0][] = html_print_label_input_block(
        __('Source agent'),
        ui_print_agent_autocomplete_input($params)
    );

    // Module selector.
    $table->data[1][] = html_print_label_input_block(
        __('Modules'),
        html_print_select(
            $modules,
            'module_arr[]',
            array_keys($modules),
            '',
            '',
            0,
            true,
            true,
            true,
            'w100p',
            false
        ).ui_print_input_placeholder(
            __('No modules of type string. You can not calculate their average'),
            true
        )
    );

    // Export type.
    $table->data[1][] = html_print_label_input_block(
        __('Export type'),
        html_print_select(
            $export_types,
            'export_type',
            $export_type,
            '',
            '',
            0,
            true,
            false,
            true,
            'w100p',
            false
        )
    );

    // Start date selector.
    $table->data[2][] = html_print_label_input_block(
        __('Begin date'),
        html_print_div(
            [
                'class'   => 'flex-content',
                'content' => html_print_input_text(
                    'start_date',
                    date('Y-m-d', (get_system_time() - SECONDS_1DAY)),
                    false,
                    13,
                    10,
                    true
                ).html_print_image(
                    'images/calendar_view_day.png',
                    true,
                    [
                        'alt'     => 'calendar',
                        'onclick' => "scwShow(scwID('text-start_date'),this);",
                        'class'   => 'main_menu_icon invert_filter',
                    ]
                ).html_print_input_text(
                    'start_time',
                    date('H:i:s', (get_system_time() - SECONDS_1DAY)),
                    false,
                    10,
                    9,
                    true
                ),
            ],
            true
        )
    );

    // End date selector.
    $table->data[2][] = html_print_label_input_block(
        __('End date'),
        html_print_div(
            [
                'class'   => 'flex-content',
                'content' => html_print_input_text(
                    'end_date',
                    date('Y-m-d', get_system_time()),
                    false,
                    13,
                    10,
                    true
                ).html_print_image(
                    'images/calendar_view_day.png',
                    true,
                    [
                        'alt'     => 'calendar',
                        'onclick' => "scwShow(scwID('text-end_date'),this);",
                        'class'   => 'main_menu_icon invert_filter',
                    ]
                ).html_print_input_text(
                    'end_time',
                    date('H:i:s', get_system_time()),
                    false,
                    10,
                    9,
                    true
                ),
            ],
            true
        ),
    );

    html_print_table($table);

    // Submit button.
    html_print_action_buttons(
        html_print_button(
            __('Export'),
            'export_btn',
            false,
            'change_action()',
            ['icon' => 'wand'],
            true
        )
    );

    echo '</form>';
}

ui_require_jquery_file('pandora.controls');
ui_require_jquery_file('ajaxqueue');
ui_require_jquery_file('bgiframe');
?>
<script type="text/javascript">
    /* <![CDATA[ */
    function change_action() {
        type = $("#export_type").val();
        var f = document.forms.export_form;
        
        switch (type) {
            case 'csv':
                blockResubmit($(this));
                f.action = "operation/agentes/exportdata.csv.php";
                break;
            case 'excel':
                blockResubmit($(this));
                f.action = "operation/agentes/exportdata.excel.php";
                break;
            case 'avg':
            case 'data':
                f.action = "index.php?sec=reporting&sec2=operation/agentes/exportdata&export_btn=1";
                break;
        }
        $("#export_form").submit();
    }
    /* ]]> */
</script>
