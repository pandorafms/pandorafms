<?php
/**
 * View for Custom Fields.
 *
 * @category   Custom fields
 * @package    Pandora FMS
 * @subpackage Enterprise
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

if (!check_acl($config['id_user'], 0, 'AR')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Inventory'
    );
    include 'general/noaccess.php';
    return;
}

// Header.
ui_print_standard_header(
    __('Custom fields view'),
    '',
    false,
    '',
    false,
    [],
    [
        [
            'link'  => 'index.php?sec=estado&sec2=operation/custom_fields/custom_fields_view',
            'label' => __('Monitoring'),
        ],
    ]
);

// =====================================================================
// Includes
// =====================================================================
require_once 'include/functions_custom_fields.php';

// =====================================================================
// parameters
// =====================================================================
$info_user = get_user_info($config['id_user']);
$group = get_parameter('group', 0);
$id_custom_fields = get_parameter('id_custom_fields', 0);
$id_custom_fields_data = get_parameter('id_custom_fields_data', -1);
$id_status = get_parameter('id_status', -1);
$module_search = get_parameter('module_search', '');
$search = get_parameter('uptbutton', '');
$id_filter = get_parameter('id_name', 0);
$recursion = get_parameter('recursion', 0);
$module_status = get_parameter('module_status', -1);

// =====================================================================
// Custom filter search
// =====================================================================
if ($search != 'Show') {
    if ($id_filter || $info_user['default_custom_view']) {
        if ($id_filter) {
            $filter_array = array_shift(
                get_filters_custom_fields_view($id_filter)
            );
        } else {
            if ($info_user['default_custom_view']) {
                $filter_array = array_shift(
                    get_filters_custom_fields_view(
                        $info_user['default_custom_view']
                    )
                );
            }
        }

        $group = $filter_array['id_group'];
        $id_custom_fields = io_safe_input($filter_array['id_custom_field']);
        $id_custom_fields_data = json_decode(
            $filter_array['id_custom_fields_data']
        );
        $id_status = json_decode($filter_array['id_status']);
        $module_search = $filter_array['module_search'];
        $recursion = $filter_array['recursion'];
        $module_status = json_decode($filter_array['module_status']);
    }
}

// =====================================================================
// filters for search
// =====================================================================
$filters = [
    'group'                 => $group,
    'id_custom_fields'      => $id_custom_fields,
    'id_custom_fields_data' => $id_custom_fields_data,
    'id_status'             => $id_status,
    'module_search'         => $module_search,
    'module_status'         => $module_status,
    'block_size'            => $config['block_size'],
    'recursion'             => $recursion,
];

// =====================================================================
// Table filters custom field
// =====================================================================
$table = new StdClass();
$table->width = '100%';
$table->class = 'databox filters';
$table->data = [];
$table->rowspan = [];
$table->colspan = [];


$array_custom_fields = get_custom_fields(false, true, true);

if ($id_custom_fields) {
    $array_custom_fields_data = get_custom_fields_data($id_custom_fields);
} else {
    $array_custom_fields_data = [];
}

$table->data[0][0] = __('Group');
$table->data[0][1] = html_print_select_groups(
    $config['id_user'],
    'AR',
    true,
    'group',
    $group,
    '',
    '',
    '0',
    true,
    false,
    false,
    '',
    false,
    'width:180px;',
    false,
    false,
    'id_grupo',
    false
);

$table->data[0][2] = ' '.__('Recursion').' ';
$table->data[0][3] = html_print_checkbox(
    'recursion',
    1,
    $recursion,
    true,
    false,
    ''
);

$array_status = [];
$array_status[AGENT_MODULE_STATUS_NORMAL] = __('Normal');
$array_status[AGENT_MODULE_STATUS_WARNING] = __('Warning');
$array_status[AGENT_MODULE_STATUS_CRITICAL_BAD] = __('Critical');
$array_status[AGENT_MODULE_STATUS_UNKNOWN] = __('Unknown');
$array_status[AGENT_MODULE_STATUS_NOT_NORMAL] = __('Not normal');
// Default.
$array_status[AGENT_MODULE_STATUS_NOT_INIT] = __('Not init');

$table->data[1][0] = __('Status agents');
$table->data[1][1] = html_print_select(
    $array_status,
    'id_status[]',
    $id_status,
    '',
    __('All'),
    -1,
    true,
    true,
    true,
    '',
    false,
    'min-width:150px'
);

$table->data[1][2] = __('Status module');
$table->data[1][3] = html_print_select(
    $array_status,
    'module_status[]',
    $module_status,
    '',
    __('All'),
    -1,
    true,
    true,
    true,
    '',
    false,
    'min-width:150px'
);

$table->data[1][4] = '<div class="w100p left">';
if ($info_user['is_admin']) {
    $table->data[1][4] .= '<a href="javascript:"
		onclick="dialog_filter_cf(\''.__('Save filter').'\', \'save\');">';
    $table->data[1][4] .= html_print_image(
        'images/disk.png',
        true,
        [
            'border' => '0',
            'title'  => __('Save filter'),
            'alt'    => __('Save filter'),
        ]
    );
    $table->data[1][4] .= '</a> &nbsp;';
}

$table->data[1][4] .= '<a href="javascript:"
	onclick="dialog_filter_cf(\''.__('Load filter').'\', \'load\');">';
$table->data[1][4] .= html_print_image(
    'images/load.png',
    true,
    [
        'border' => '0',
        'title'  => __('Load filter'),
        'alt'    => __('Load filter'),
    ]
);
$table->data[1][4] .= '</a> &nbsp;';

$table->data[1][4] .= '</div>';

$table->data[2][0] = __('Custom Fields');
$table->data[2][1] = html_print_select(
    $array_custom_fields,
    'id_custom_fields',
    $id_custom_fields,
    '',
    __('None'),
    0,
    true,
    false,
    true,
    '',
    false,
    'width:10em'
);

$table->data[2][2] = __('Custom Fields Data');
$table->data[2][3] = html_print_select(
    $array_custom_fields_data,
    'id_custom_fields_data[]',
    io_safe_output($id_custom_fields_data),
    'set_custom_fields_data_title()',
    __('All'),
    -1,
    true,
    true,
    true,
    '',
    false,
    'min-width:150px;',
    false,
    false,
    false,
    '',
    false,
    true,
    false,
    false,
    true,
    true
);

$table->colspan[3][1] = 3;
$table->data[3][0] = __('Module search');
$table->data[3][1] = html_print_input_text(
    'module_search',
    $module_search,
    '',
    20,
    40,
    true
);

$table->data[2][5] = html_print_submit_button(
    __('Show'),
    'uptbutton',
    false,
    'class="sub search mgn_tp_0"',
    true
);

if (check_acl($config['id_user'], 0, 'PM')) {
    // Pass the parameters to the page that generates the csv file (arrays).
    $decode_id_status = base64_encode(json_encode($id_status));
    $decode_module_status = base64_encode(json_encode($module_status));
    $decode_filters = base64_encode(json_encode($filters));

    $table->data[3][5] = '<div style="display: inline;">';
    $table->data[3][5] .= '</div>';
}


$form = '<form method="post" action="">';
    $form .= html_print_table($table, true);
$form .= '</form>';

ui_toggle(
    $form,
    '<span class="subsection_header_title">'.__('Filters').'</span>',
    'filters',
    false,
    true,
    false,
    '',
    'white-box-content',
    'box-flat white_table_graph fixed_filter_bar'
);

// =====================================================================
// div for show success messages
// =====================================================================
echo "<div id='msg_success'> </div>";


// Image for gif datatables processing.
$processing = html_print_image(
    'images/spinner.gif',
    true,
    [
        'title' => __('Processing'),
    ]
).'<span>'.__('Processing').'...</span>';

if (isset($id_custom_fields_data) && is_array($id_custom_fields_data)) {
    $data = agent_counters_custom_fields($filters);

    if (!empty($data['indexed_descriptions'])) {
        echo "<div class='custom_fields_view_layout'>";
            echo "<div class='custom_fields_view'>";

        if (in_array(-1, $filters['id_custom_fields_data'])) {
            $id_custom_field_array = get_custom_fields_data($id_custom_fields);
        } else {
            $id_custom_field_array = $filters['id_custom_fields_data'];
        }

        $id_field = db_get_value_filter(
            'id_field',
            'tagent_custom_fields',
            ['name' => $id_custom_fields]
        );

        foreach ($id_custom_field_array as $value) {
            $table_agent = new StdClass();
            $table_agent->width = '100%';
            $table_agent->class = 'tactical_view';
            $table_agent->data = [];
            $table_agent->rowspan = [];
            $table_agent->colspan = [];

            $agent_data = [];

            // Critical.
            $agent_data[0] = html_print_image(
                'images/agent_critical.png',
                true,
                ['title' => __('Agents critical')]
            );
            $agent_data[1] = "<a style='color: ".COL_CRITICAL.";' href='index.php?sec=view&sec2=operation/agentes/estado_agente&status=".AGENT_STATUS_CRITICAL.'&ag_custom_fields['.$id_field.']='.$value."'>";
            $agent_data[1] .= "<b><span class='font_12pt bolder red_color '>";
            $agent_data[1] .= format_numeric(
                $data['counters_name'][$value]['a_critical']
            );
            $agent_data[1] .= '</span></b></a>';

            // Warning.
            $agent_data[2] = html_print_image(
                'images/agent_warning.png',
                true,
                ['title' => __('Agents warning')]
            );
            $agent_data[3] = "<a style='color: ".COL_WARNING.";' href='index.php?sec=view&sec2=operation/agentes/estado_agente&status=".AGENT_STATUS_WARNING.'&ag_custom_fields['.$id_field.']='.$value."'>";
            $agent_data[3] .= "<b><span class='font_12pt bolder yellow_color'>";
            $agent_data[3] .= format_numeric(
                $data['counters_name'][$value]['a_warning']
            );
            $agent_data[3] .= '</span></b></a>';

            // OK.
            $agent_data[4] = html_print_image(
                'images/agent_ok.png',
                true,
                ['title' => __('Agents ok')]
            );
            $agent_data[5] = "<a style='color: ".COL_NORMAL.";' href='index.php?sec=view&sec2=operation/agentes/estado_agente&status=".AGENT_STATUS_NORMAL.'&ag_custom_fields['.$id_field.']='.$value."'>";
            $agent_data[5] .= "<b><span class='font_12pt bolder pandora_green_text'>";
            $agent_data[5] .= format_numeric(
                $data['counters_name'][$value]['a_normal']
            );
            $agent_data[5] .= '</span></b></a>';

            // Unknown.
            $agent_data[6] = html_print_image(
                'images/agent_unknown.png',
                true,
                ['title' => __('Agents unknown')]
            );
            $agent_data[7] = "<a style='color: ".COL_UNKNOWN.";' href='index.php?sec=view&sec2=operation/agentes/estado_agente&status=".AGENT_STATUS_UNKNOWN.'&ag_custom_fields['.$id_field.']='.$value."'>";
            $agent_data[7] .= "<b><span class='font_12pt bolder grey_color'>";
            $agent_data[7] .= format_numeric(
                $data['counters_name'][$value]['a_unknown']
            );
            $agent_data[7] .= '</span></b></a>';

            // Not init.
            $agent_data[8] = html_print_image(
                'images/agent_notinit.png',
                true,
                ['title' => __('Agents not init')]
            );
            $agent_data[9] = "<a style='color: ".COL_NOTINIT.";' href='index.php?sec=view&sec2=operation/agentes/estado_agente&status=".AGENT_STATUS_NOT_INIT.'&ag_custom_fields['.$id_field.']='.$value."'>";
            $agent_data[9] .= "<b><span class='font_12pt bolder blue_color'>";
            $agent_data[9] .= format_numeric(
                $data['counters_name'][$value]['a_not_init']
            );
            $agent_data[9] .= '</span></b></a>';

            $table_agent->data[] = $agent_data;

            $m_critical = ($data['counters_name'][$value]['m_critical'] <= 0) ? '0' : $data['counters_name'][$value]['m_critical'];
            $m_warning = ($data['counters_name'][$value]['m_warning'] <= 0) ? '0' : $data['counters_name'][$value]['m_warning'];
            $m_normal = ($data['counters_name'][$value]['m_normal'] <= 0) ? '0' : $data['counters_name'][$value]['m_normal'];
            $m_unknown = ($data['counters_name'][$value]['m_unknown'] <= 0) ? '0' : $data['counters_name'][$value]['m_unknown'];
            $m_not_init = ($data['counters_name'][$value]['m_not_init'] <= 0) ? '0' : $data['counters_name'][$value]['m_not_init'];

            $table_mbs = new StdClass();
            $table_mbs->width = '100%';
            $table_mbs->class = 'tactical_view';
            $table_mbs->data = [];
            $table_mbs->rowspan = [];
            $table_mbs->colspan = [];

            $tdata = [];
            $tdata[0] = html_print_image(
                'images/module_critical.png',
                true,
                ['title' => __('Monitor critical')],
                false,
                false,
                false,
                true
            );
            $tdata[1] = '<a style="color: '.COL_CRITICAL.';" class="font_12pt bolder" href="index.php?sec=view&sec2=operation/agentes/status_monitor&status='.AGENT_STATUS_CRITICAL.'&ag_custom_fields['.$id_field.']='.$value.'">'.$m_critical.'</a>';

            $tdata[2] = html_print_image(
                'images/module_warning.png',
                true,
                ['title' => __('Monitor warning')],
                false,
                false,
                false,
                true
            );
            $tdata[3] = '<a style="color: '.COL_WARNING_DARK.';" class="font_12pt bolder" href="index.php?sec=view&sec2=operation/agentes/status_monitor&status='.AGENT_STATUS_WARNING.'&ag_custom_fields['.$id_field.']='.$value.'">'.$m_warning.'</a>';

            $tdata[4] = html_print_image(
                'images/module_ok.png',
                true,
                ['title' => __('Monitor normal')],
                false,
                false,
                false,
                true
            );
            $tdata[5] = '<a style="color: '.COL_NORMAL.';" class="font_12pt bolder" href="index.php?sec=view&sec2=operation/agentes/status_monitor&status='.AGENT_STATUS_NORMAL.'&ag_custom_fields['.$id_field.']='.$value.'">'.$m_normal.'</a>';

            $tdata[6] = html_print_image(
                'images/module_unknown.png',
                true,
                ['title' => __('Monitor unknown')],
                false,
                false,
                false,
                true
            );
            $tdata[7] = '<a style="color: '.COL_UNKNOWN.';" class="font_12pt bolder" href="index.php?sec=view&sec2=operation/agentes/status_monitor&status='.AGENT_STATUS_UNKNOWN.'&ag_custom_fields['.$id_field.']='.$value.'">'.$m_unknown.'</a>';

            $tdata[8] = html_print_image(
                'images/module_notinit.png',
                true,
                ['title' => __('Monitor not init')],
                false,
                false,
                false,
                true
            );

            $tdata[9] = '<a style="color: '.COL_NOTINIT.';" class="font_12pt bolder" href="index.php?sec=view&sec2=operation/agentes/status_monitor&status='.AGENT_STATUS_NOT_INIT.'&ag_custom_fields['.$id_field.']='.$value.'">'.$m_not_init.'</a>';

            $table_mbs->data[] = $tdata;

                echo "<div class='title_tactical'>".ui_bbcode_to_html($value).'</div>';
                // Agents data.
                echo '<div>';
                    echo "<fieldset class='tactical_set'>";
                        echo '<legend>'.__('Agents by status').': '.$data['counters_name'][$value]['a_agents'].'</legend>';
                        echo html_print_table($table_agent, true);
                    echo '</fieldset>';
                echo '</div>';

                // Modules data.
                echo "<div class='tactical_div_end'>";
                    echo "<fieldset class='tactical_set'>";
                        echo '<legend>'.__('Monitors by status').': '.$data['counters_name'][$value]['m_total'].'</legend>';
                        echo html_print_table($table_mbs, true);
                    echo '</fieldset>';
                echo '</div>';
        }

        echo '</div>';
        // Agent status.
        $status_agent_array = [
            1 => [
                'value'   => 'AGENT_STATUS_CRITICAL',
                'checked' => 1,
                'image'   => 'images/agent_mc.menu-2.png',
                'title'   => __('Critical agents'),
                'color'   => '#e63c52',
                'counter' => format_numeric(
                    $data['counters_total']['t_a_critical']
                ),
            ],
            2 => [
                'value'   => 'AGENT_STATUS_WARNING',
                'checked' => 1,
                'image'   => 'images/agent_mc.menu-2.png',
                'title'   => __('Warning agents'),
                'color'   => '#f3b200',
                'counter' => format_numeric(
                    $data['counters_total']['t_a_warning']
                ),
            ],
            0 => [
                'value'   => 'AGENT_STATUS_NORMAL',
                'checked' => 1,
                'image'   => 'images/agent_mc.menu-2.png',
                'title'   => __('Normal agents'),
                'color'   => '#82b92e',
                'counter' => format_numeric(
                    $data['counters_total']['t_a_normal']
                ),
            ],
            3 => [
                'value'   => 'AGENT_STATUS_UNKNOWN',
                'checked' => 1,
                'image'   => 'images/agent_mc.menu-2.png',
                'title'   => __('Unknown agents'),
                'color'   => '#B2B2B2',
                'counter' => format_numeric(
                    $data['counters_total']['t_a_unknown']
                ),
            ],
            5 => [
                'value'   => 'AGENT_STATUS_NOT_INIT',
                'checked' => 1,
                'image'   => 'images/agent_mc.menu-2.png',
                'title'   => __('Not init agents'),
                'color'   => '#60aae9',
                'counter' => format_numeric(
                    $data['counters_total']['t_a_not_init']
                ),
            ],
        ];

        if (isset($filters['id_status']) === true && is_array($filters['id_status']) === true) {
            if (in_array(-1, $filters['id_status']) === false) {
                if (in_array(AGENT_MODULE_STATUS_NOT_NORMAL, $filters['id_status']) === false) {
                    foreach ($status_agent_array as $key => $value) {
                        if (in_array($key, $filters['id_status']) === false) {
                            $status_agent_array[$key]['checked'] = 0;
                        }
                    }
                } else {
                    // Not normal statuses.
                    $status_agent_array[0]['checked'] = 0;
                }
            }
        }

        // Module status.
        $status_module_array = [
            1 => [
                'value'   => 'AGENT_STATUS_CRITICAL',
                'checked' => 1,
                'image'   => 'images/module_event_ok.png',
                'title'   => __('Critical modules'),
                'color'   => '#e63c52',
                'counter' => format_numeric(
                    $data['counters_total']['t_m_critical']
                ),
                'class'   => 'line_heigth_0pt',
            ],
            2 => [
                'value'   => 'AGENT_STATUS_WARNING',
                'checked' => 1,
                'image'   => 'images/module_event_ok.png',
                'title'   => __('Warning modules'),
                'color'   => '#f3b200',
                'counter' => format_numeric(
                    $data['counters_total']['t_m_warning']
                ),
                'class'   => 'line_heigth_0pt',
            ],
            0 => [
                'value'   => 'AGENT_STATUS_NORMAL',
                'checked' => 1,
                'image'   => 'images/module_event_ok.png',
                'title'   => __('Normal modules'),
                'color'   => '#82b92e',
                'counter' => format_numeric(
                    $data['counters_total']['t_m_normal']
                ),
                'class'   => 'line_heigth_0pt',
            ],
            3 => [
                'value'   => 'AGENT_STATUS_UNKNOWN',
                'checked' => 1,
                'image'   => 'images/module_event_ok.png',
                'title'   => __('Unknown modules'),
                'color'   => '#B2B2B2',
                'counter' => format_numeric(
                    $data['counters_total']['t_m_unknown']
                ),
                'class'   => 'line_heigth_0pt',
            ],
            5 => [
                'value'   => 'AGENT_STATUS_NOT_INIT',
                'checked' => 1,
                'image'   => 'images/module_event_ok.png',
                'title'   => __('Not init modules'),
                'color'   => '#60aae9',
                'counter' => format_numeric(
                    $data['counters_total']['t_m_not_init']
                ),
                'class'   => 'line_heigth_0pt',
            ],
        ];

        if (isset($filters['module_status']) === true && is_array($filters['module_status']) === true) {
            if (in_array(-1, $filters['module_status']) === false) {
                if (in_array(AGENT_MODULE_STATUS_NOT_NORMAL, $filters['module_status']) === false) {
                    foreach ($status_module_array as $key => $value) {
                        if (in_array($key, $filters['module_status']) === false) {
                            $status_module_array[$key]['checked'] = 0;
                        }
                    }
                } else {
                    // Not normal statuses.
                    $status_module_array[0]['checked'] = 0;
                }
            }
        }

        // Total status.
        echo "<div class='state_events'>";
            echo "<div class='title_tactical'>".__('Total counters').'</div>';

            echo "<fieldset class='tactical_set'>";
                echo '<legend>'.__('Total Agents').'</legend>';
                echo print_counters_cfv(
                    $status_agent_array,
                    'form-agent-counters',
                    'agents'
                );
            echo '</fieldset>';

            echo "<fieldset class='tactical_set'>";
                echo '<legend>'.__('Total Modules').'</legend>';
                echo print_counters_cfv(
                    $status_module_array,
                    'form-module-counters',
                    'modules'
                );
            echo '</fieldset>';

        echo '</div>';

        echo "<div class='agents_custom_fields '>";
            echo "<table id='datatables' class='info_table w100pi'>";
                echo '<thead>';
                    echo '<tr>';
                        echo '<th></th>';
                        echo '<th>'.$array_custom_fields[$id_custom_fields].'</th>';
                        echo '<th>'.__('Agent').'</th>';
                        echo '<th>'.__('I.P').'</th>';
                        echo '<th>'.__('Server').'</th>';
                        echo '<th>'.__('Status').'</th>';
                    echo '</tr>';
                echo '</thead>';
                echo '<tbody>';
                    // Content of the dynamically created load.
                echo '</tbody>';
            echo '</table>';
        echo '</div>';

        echo '</div>';

        $indexed_descriptions = $data['indexed_descriptions'];
    } else {
        ui_print_info_message(
            [
                'no_close' => true,
                'message'  => __('No data to show.'),
            ]
        );
    }
} else {
    ui_print_info_message(
        [
            'no_close' => true,
            'message'  => __('There are no custom search defined.'),
        ]
    );
}

// Div modal for display filters.
echo '<div id="filter_cf" class="invisible"></div>';

ui_require_css_file('datatables.min', 'include/styles/js/');
ui_require_css_file('custom_field', 'include/styles/');
ui_require_javascript_file_enterprise('functions_csv');
ui_require_javascript_file('datatables.min');
ui_require_javascript_file('buttons.dataTables.min');
ui_require_javascript_file('dataTables.buttons.min');
ui_require_javascript_file('buttons.html5.min');
ui_require_javascript_file('buttons.print.min');
enterprise_include_once('include/functions_reporting_csv.php');
?>

<script type='text/javascript'>
$(document).ready (function () {
    $("#id_custom_fields").on('change', function(e){
        var name_custom_fields = $("#id_custom_fields").val();
        $.ajax({
            type: "POST",
            url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
            data: "page=include/ajax/custom_fields&get_custom_fields_data=1&name_custom_fields="+name_custom_fields,
            dataType: "json",
            success: function(data) {
                $("#id_custom_fields_data")
                    .find('option')
                    .remove();

                $("#id_custom_fields_data")
                    .append('<option value="-1">' +'<?php echo __('All'); ?>' + '</option>');

                $("#id_custom_fields_data [value='-1']").attr("selected", true);

                $.each(data, function(index, element){
                    $("#id_custom_fields_data")
                        .append('<option value="'+ index +'">'+ element +'</option>');
                });
            }
        });
    });

    var filters = '<?php echo json_encode($filters); ?>';
    var indexed_descriptions = '<?php echo json_encode((isset($indexed_descriptions) === true) ? $indexed_descriptions : ''); ?>';
    var processing = '<?php echo $processing; ?>';

    table_datatables(filters, indexed_descriptions, processing);

    $('#form-agent-counters input').on( 'click', function () {
        cf_status_change(filters, indexed_descriptions, processing);
    });

    $('#form-module-counters input').on( 'click', function () {
        cf_status_change(filters, indexed_descriptions, processing); 
    });

    $('#datatables tbody').on( 'click', 'tr td.details-control', function () {
        var tr = $(this).closest('tr');
        var row = table.row( tr );

        if ( row.child.isShown() ) {
            // This row is already open - close it
            row.child.hide();
            tr.removeClass('shown');
        }
        else {
            // Open this row
            row.child( build_table_child(row.data(), filters, processing) ).show();
            tr.addClass('shown');
        }
    });  

    set_custom_fields_data_title();
});

/**
 * Create dialog
 */
function dialog_filter_cf(title, type_form){
    if (type_form == 'load') {
        $("#filter_cf").dialog ({
            title: title,
            resizable: true,
            draggable: true,
            modal: true,
            overlay: {
                opacity: 0.5,
                background: "black"
            },
            width: 688,
            height: 200
        })
        .show ();
    } else {
        $("#filter_cf").dialog ({
            title: title,
            resizable: true,
            draggable: true,
            modal: true,
            overlay: {
                opacity: 0.5,
                background: "black"
            },
            width: 688,
            height: 350
        })
        .show ();
    }

    $("#filter_cf").empty();

    $.ajax({
        type: "POST",
        url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
        data: "page=include/ajax/custom_fields&build_table_save_filter=1&type_form="+type_form,
        dataType: "html",
        success: function(data) {
            $("#filter_cf").append(data);
            var filters = {};
            filters.id = "extended_create_filter";
            filters.group = $("#group").val();
            filters.id_custom_fields_data = $("#id_custom_fields_data").val();
            filters.id_status = $("#id_status").val();
            filters.id_custom_fields = $("#id_custom_fields").val();
            filters.module_search = $("#text-module_search").val();
            filters.recursion = $("#checkbox-recursion").is(':checked') ? 1 : 0;
            filters.module_status = $("#module_status").val();

            if(type_form == 'save'){
                $("#tabs").tabs({});

                append_tab_filter(filters);

                $("#tabs ul li a").on('click', function(e){
                    var filters = {};
                    filters.id = "extended_create_filter";
                    filters.group = $("#group").val();
                    filters.id_custom_fields_data = $("#id_custom_fields_data").val();
                    filters.id_status = $("#id_status").val();
                    filters.id_custom_fields = $("#id_custom_fields").val();
                    filters.module_search = $("#text-module_search").val();
                    filters.recursion = $("#checkbox-recursion").is(':checked');
                    filters.module_status = $("#module_status").val();

                    if(this.id == 'link_update'){
                        filters['id'] = "extended_update_filter";
                    }
                    append_tab_filter(filters);
                });
            }
        }
    });
}

function table_datatables(filters, indexed_descriptions, processing){
    array_data = JSON.parse(filters);
    table = $('#datatables').DataTable({
        processing: true,
        serverSide: true,
        lengthChange: true,
        searching: true,
        pageLength: Number(array_data.block_size),
        lengthMenu: [ 5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 75, 100],
        responsive: false,
        ajax: {
            type: "POST",
            url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
            dataType: "json",
            data: {
                'page': 'include/ajax/custom_fields',
                'build_table_custom_fields': 1,
                'group': array_data.group,
                'id_custom_fields': array_data.id_custom_fields,
                'id_custom_fields_data': array_data.id_custom_fields_data,
                'id_status': array_data.id_status,
                'module_search': array_data.module_search,
                'recursion': array_data.recursion,
                'module_status': array_data.module_status,
                'indexed_descriptions': indexed_descriptions,
                'paging': true,
                'ordering': true,
                'scrollX': true,
                'scroller': true,
            },
        },
        language: {
            processing: processing,
            lengthMenu: "Show _MENU_ items per page",
            zeroRecords: "Nothing found. Please change your search term",
            infoEmpty: "No results",
            infoFiltered: "",
            search: "Search:",
        },
        sDom: '<"top"lfp>rt<"bottom"ip><"clear">',
        columns: [
            {
                className: 'details-control',
                orderable: false,
                data: null,
                defaultContent: '',
                width: '5%'
            },
            {
                orderable: true,
                data: "data_custom_field"
            },
            {
                orderable: true,
                data: "agent",
                render: function (data, type, row) {
                    if (type === "exportcsv") {
                        const title = data.match(/title="(.*)"/i);
                        if (title != null) {
                            return title[1];
                        }
                    }
                    return data;
                }
            },
            {
                orderable: true,
                data: "IP"
            },
            {
                orderable: true,
                data: "server"
            },
            {
                orderable: false,
                data: "status",
                render: function (data, type, row) {
                    // In the future, try to use the column "status_value".
                   if (type === "exportcsv") {
                        var status_string;
                        if (data.indexOf("agent_warning.png") > -1) {
                            status_string = 'Warning';
                        } else if (data.indexOf("agent_critical.png") > -1){
                            status_string = 'Critical';
                        } else if (data.indexOf("agent_ok.png") > -1){
                            status_string = 'Normal';
                        } else if (data.indexOf("agent_down.png") > -1){
                            status_string = 'Unknown';
                        } else if (data.indexOf("init") > -1){
                            status_string = 'Not init';
                        } else{
                            status_string = 'Normal';
                        }
                        return status_string;
                    }
                    return data;
                },
                width: '10%'
            }
        ],
        order: [
            [ 1, "desc" ]
        ]
    });
    
    $.ajax ({
        url : "ajax.php",
        data : {
            'page': 'include/ajax/custom_fields',
            'check_csv_button': 1
            },
        type : 'POST',
        dataType : 'json',
        success: function (data) { 
            // Create button to export csv when table exists.
            if($.fn.DataTable.isDataTable('#datatables')) {
                new $.fn.dataTable.Buttons( table, {
                    name: 'commands',
                    buttons: 
                    [
                        { 
                            extend: 'csvHtml5', 
                            text: 'Save as CSV', 
                            itle: 'custom_fields_current_view',
                            fieldSeparator: "<?php echo $config['csv_divider']; ?>",
                            fieldBoundary: '',
                            action: function ( e, dt, node, config ) {
                                blockResubmit(node);
                                // Call the default csvHtml5 action method to create the CSV file
                                $.fn.dataTable.ext.buttons.csvHtml5.action.call(this, e, dt, node, config);
                            },
                            exportOptions: {
                                columns: [1,2,3,4,5],
                                orthogonal: "exportcsv",
                                format: {
                                    body: function(data, row, column, node) {
                                        data = $('<p>' + data + '</p>').text();
                                            if($.isNumeric(data)) {
                                                var countDecimals = function(value) {
                                                    if (Math.floor(value) !== value) {
                                                        return value.toString().split(".")[1].length || 0;
                                                    }

                                                    return 0;
                                                }
                                                var dec_point = "<?php echo $config['csv_decimal_separator']; ?>";
                                                var thousands_separator = "<?php echo __(''); ?>";
                                                data = js_csv_format_numeric(data ,dec_point, thousands_separator, countDecimals(data));
                                            }
                                            return data;
                                    }
                                },
                            },
                            className: 'button_save_csv' 
                        }
                    ],               
                });

                table.buttons( 0, null ).containers().insertBefore( '#datatables_paginate' );
                // Enable Export All csv button, when the table has data (filters are charged).
                $("#button-csv_export").prop("disabled",false).css('cursor','pointer');
            } else {   
                // Disable Export All csv button, because it is empty.   
                $("#button-csv_export").prop("disabled",true).css('cursor','not-allowed');
            }
        }
    });
}

function build_table_child (d, filters, processing) {
    array_data = JSON.parse(filters);
    
    var filters_modules = $('#form-module-counters').serializeArray();
    var status_modules = filters_modules.map(
        name => name.name.replace(/lists_modules\[/g,'').replace(/\]/g,'')
    );

    var data = {
        'page': 'include/ajax/custom_fields',
        'build_table_child_custom_fields': true,
        'id_agent': d.id_agent,
        'id_server': d.id_server,
        'module_search': array_data.module_search,
        'module_status': status_modules
    }

    var div_table = $('<div>').append(processing);

    $.ajax({
        url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
        type: 'POST',
        dataType: 'json',
        data: data,
        success: function(data){
            div_table.empty();
            div_table.append(data.modules_table);
            $('#reload_status_agent_'+d.id_server+'_'+d.id_agent).empty();
            $('#reload_status_agent_'+d.id_server+'_'+d.id_agent).append(data.img_status_agent);
        }
    });

    return div_table;
}

/**
 * Action filters custom fileds view
 */
function append_tab_filter(filters){
    //convert to JSON
    filters_string = JSON.stringify(filters);
    //clean div
    $("#"+filters['id']).empty();

    //execute actions
    $.ajax({
        type: "POST",
        url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
        data: "page=include/ajax/custom_fields&append_tab_filter=1&filters="+filters_string,
        dataType: "html",
        success: function(data) {
            //clean divs errors
            $("#msg_success").empty();

            //filters
            $("#"+filters['id']).append(data);

            //action create
            $('#button-create_filter').on('click', function(){
                name = $('#text-id_name').val();
                group_search = $('#group_search_cr').val();
                $.ajax({
                    type: "POST",
                    url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
                    data: "page=include/ajax/custom_fields&create_filter_cf=1&filters="+filters_string+"&name_filter="+name+"&group_search="+group_search,
                    dataType: "json",
                    success: function(data) {
                        //clean divs errors
                        $("#msg_error_create").empty();
                        if(data.error){
                            $("#msg_error_create").append(data.msg);
                        }
                        else{
                            $("#msg_success").append(data.msg);
                            $("#filter_cf").dialog('close');
                        }
                    }
                });
            });

            //action update
            $('#button-update_filter').on('click', function(){
                id = $('#id_name').val();
                group_search = $('#group_search_up').val();
                $.ajax({
                    type: "POST",
                    url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
                    data: "page=include/ajax/custom_fields&update_filter_cf=1&filters="+filters_string+"&id_filter="+id+"&group_search="+group_search,
                    dataType: "json",
                    success: function(data) {
                        //clean divs errors
                        $("#msg_error_update").empty();

                        if(data.error){
                            $("#msg_error_update").append(data.msg);
                        }
                        else{
                            $("#msg_success").append(data.msg);
                            $("#filter_cf").dialog('close');
                        }
                    }
                });
            });

            //delete update
            $('#button-delete_filter').on('click', function(){

                //dialog confirm
                display_confirm_dialog(
                    "<?php echo __('Are you sure?'); ?>",
                    "<?php echo __('Confirm'); ?>",
                    "<?php echo __('Cancel'); ?>",
                    function () {
                        id = $('#id_name').val();
                        //delete if confirm
                        $.ajax({
                            type: "POST",
                            url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
                            data: "page=include/ajax/custom_fields&delete_filter_cf=1&filters="+filters_string+"&id_filter="+id,
                            dataType: "json",
                            success: function(data) {
                                //clean divs errors
                                $("#msg_error_delete").empty();

                                if(data.error){
                                    $("#msg_error_delete").append(data.msg);
                                }
                                else{
                                    $("#msg_success").append(data.msg);
                                    $("#filter_cf").dialog('close');
                                }
                            }
                        });
                        return false;
                    }
                );
            });
        }
    });
}

function filter_name_change_group(val){
    $.ajax({
        type: "POST",
        url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
        data: "page=include/ajax/custom_fields&change_name_filter=1&id_filter="+val,
        dataType: "json",
        success: function(data) {
            $('#group_search_up option[value="'+data.group_search+'"]').attr("selected",true);
        }
    });
}

function cf_status_change(filters, indexed_descriptions, processing){
    var filters_agents = $('#form-agent-counters').serializeArray();
    var status_agents = filters_agents.map(
        name => name.name.replace(/lists_agents\[/g,'').replace(/\]/g,'')
    );

    var filters_modules = $('#form-module-counters').serializeArray();
    var status_modules = filters_modules.map(
        name => name.name.replace(/lists_modules\[/g,'').replace(/\]/g,'')
    );

    // Convert json to object.
    var array_filters = JSON.parse(filters);

    // Add new filters.
    array_filters['id_status'] = status_agents;
    array_filters['module_status'] = status_modules;

    // Convert to string.
    var filters_string = JSON.stringify(array_filters);

    // Clear table.
    table.destroy();

    // Add new table with new filter.
    table_datatables(filters_string, indexed_descriptions, processing);
}

function set_custom_fields_data_title()
{
    $('#id_custom_fields_data > option').each(function() {
       var text = $(this).text();
       $(this).attr('title', text);
    });
}

</script>
