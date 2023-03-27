<?php
/**
 * Agents Graphs.
 *
 * @category   Graphs.
 * @package    Pandora FMS
 * @subpackage Agent Configuration
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2022 Artica Soluciones Tecnologicas
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

// Load global vars.
global $config;

require_once 'include/functions_agents.php';
require_once 'include/functions_custom_graphs.php';
ui_require_javascript_file('calendar');

if ((bool) check_acl($config['id_user'], $id_grupo, 'AR') === false && (bool) check_acl($config['id_user'], 0, 'AW') === false) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access (read) to agent '.agents_get_name($id_agente)
    );
    include 'general/noaccess.php';
    return;
}

require_once $config['homedir'].'/include/functions_graph.php';

$draw_alerts = get_parameter('draw_alerts', 0);
$period = get_parameter('period', SECONDS_1HOUR);
$width = get_parameter('width', 555);
$height = get_parameter('height', 245);
$label = get_parameter('label', '');
$start_date = get_parameter('start_date', date('Y-m-d'));
$draw_events = get_parameter('draw_events', 0);
$modules = get_parameter('modules', []);
$filter = get_parameter('filter', 0);
$combined = get_parameter('combined', 1);
$option_type = get_parameter('option_type', 0);

// ----------------------------------------------------------------------
// Get modules of agent sorted as:
// - modules network no proc
// - modules network proc
// - others
// ----------------------------------------------------------------------
$list_modules = [];
$modules_networkmap_no_proc = agents_get_modules(
    $id_agente,
    false,
    [
        'id_modulo'      => 2,
        // Networkmap type.
        'id_tipo_modulo' => [
            '<>2',
            // != generic_proc
            '<>6',
            // != remote_icmp_proc
            '<>9',
            // != remote_tcp_proc
            '<>6',
            // != remote_tcp_proc
            '<>18',
            // != remote_snmp_proc
            '<>21',
            // != async_proc
            '<>31',
        ],
        // != web_proc
    ]
);
if (empty($modules_networkmap_no_proc)) {
    $modules_networkmap_no_proc = [];
}

$modules_others = agents_get_modules(
    $id_agente,
    false,
    [
        'id_tipo_modulo' => [
            '<>2',
            // != generic_proc
            '<>6',
            // != remote_icmp_proc
            '<>9',
            // != remote_tcp_proc
            '<>6',
            // != remote_tcp_proc
            '<>18',
            // != remote_snmp_proc
            '<>21',
            // != async_proc
            '<>31',
        ],
        // != web_proc
    ]
);

if (empty($modules_others)) {
    $modules_others = [];
}

$modules_boolean = agents_get_modules(
    $id_agente,
    false,
    [
        'id_tipo_modulo' => [
            '<>1',
            '<>3',
            '<>4',
            '<>5',
            '<>7',
            '<>8',
            '<>10',
            '<>11',
            '<>15',
            '<>16',
            '<>17',
            '<>22',
            '<>23',
            '<>24',
            '<>30',
            '<>32',
            '<>33',
            '<>100',
        ],
    ]
);
if (empty($modules_boolean)) {
    $modules_boolean = [];
}

// Cleaned the duplicate $modules and other things.
$modules_others = array_diff_key(
    $modules_others,
    $modules_networkmap_no_proc
);
foreach ($modules_others as $i => $m) {
    $modules_others[$i] = [
        'optgroup' => __('Other modules'),
        'name'     => $m,
    ];
}

foreach ($modules_networkmap_no_proc as $i => $m) {
    $modules_networkmap_no_proc[$i] = [
        'optgroup' => __('Modules network no proc'),
        'name'     => $m,
    ];
}

foreach ($modules_boolean as $i => $m) {
    $modules_boolean[$i] = [
        'optgroup' => __('Modules boolean'),
        'name'     => $m,
    ];
}


$list_modules = ($modules_networkmap_no_proc + $modules_others + $modules_boolean);
// ----------------------------------------------------------------------
if (empty($modules)) {
    // Selected the first 6 modules.
    $module_ids = array_keys($list_modules);
    $module_ids = array_slice($module_ids, 0, 6);
    $modules = $module_ids;
}

$table = new stdClass();
$table->width = '100%';
$table->class = 'databox filters';

$table->style[0] = 'font-weight: bolder; text-align: left;';
$table->size[0] = '10%';
$table->style[1] = 'font-weight: bolder; text-align: left;';
$table->size[1] = '15%';
$table->style[2] = 'font-weight: bolder; text-align: left;';
$table->size[2] = '10%';
$table->style[3] = 'font-weight: bolder; text-align: left;';
$table->size[3] = '20%';

$table->rowspan[0][0] = 7;
$table->rowspan[0][1] = 7;

$table->data[0][0] = __('Modules');
$table->data[0][1] = html_print_select(
    $list_modules,
    'modules[]',
    $modules,
    '',
    '',
    0,
    true,
    true,
    true,
    '',
    false,
    'min-width:200px;max-width:460px;height: 200px;'
);

$table->rowspan[2][0] = 7;
$table->data[2][0] = '';

$table->data[2][1] = __('Begin date');
$table->data[2][2] = html_print_input_text('start_date', substr($start_date, 0, 10), '', 10, 40, true);
$table->data[2][2] .= html_print_image(
    'images/calendar_view_day.png',
    true,
    [
        'class'   => 'invert_filter',
        'onclick' => "scwShow(scwID('text-start_date'),this);",
    ]
);

$table->data[3][1] = __('Time range');

$table->data[3][2] = html_print_extended_select_for_time('period', $period, '', '', 0, 7, true);

$table->data[4][2] = __('Show events');
$table->data[4][3] = html_print_checkbox('draw_events', 1, (bool) $draw_events, true);
$table->data[5][2] = __('Show alerts').ui_print_help_tip(__('the combined graph does not show the alerts into this graph'), true);
$table->data[5][3] = html_print_checkbox('draw_alerts', 1, (bool) $draw_alerts, true);
$table->data[6][2] = __('Show as one combined graph');
$graph_option_one_or_several = [
    0 => __('several graphs for each module'),
    1 => __('One combined graph'),
];
$table->data[6][3] = html_print_select($graph_option_one_or_several, 'combined', $combined, '', '', 1, true);

$table->data[7][2] = __('Chart type');
if ($combined == 1) {
    $graph_option_type = [
        0 => __('Area'),
        1 => __('Area stack'),
        2 => __('Line'),
        3 => __('Line stack'),
    ];
} else {
    $graph_option_type = [
        0 => __('Area'),
        2 => __('Line'),
    ];
}

$table->data[7][3] = html_print_select($graph_option_type, 'option_type', $option_type, '', '', 1, true);

$htmlForm = '<form method="post" action="index.php?sec=estado&sec2=operation/agentes/ver_agente&tab=graphs&id_agente='.$id_agente.'" >';
$htmlForm .= html_print_table($table, true);
$htmlForm .= html_print_input_hidden('filter', 1, true);

$outputButtons = html_print_submit_button(
    __('Filter'),
    'filter_button',
    false,
    [
        'icon' => 'update',
        'mode' => 'secondary mini',
    ],
    true
);

if ((bool) check_acl($config['id_user'], 0, 'RW') === true || (bool) check_acl($config['id_user'], 0, 'RM') === true) {
    $outputButtons .= html_print_button(
        __('Save as custom graph'),
        'save_custom_graph',
        false,
        '',
        [
            'icon' => 'add',
            'mode' => 'secondary mini',
        ],
        true
    );
}

$htmlForm .= html_print_div(
    [
        'class'   => 'action-buttons',
        'content' => $outputButtons,
    ],
    true
);

$htmlForm .= '</form>';

ui_toggle($htmlForm, __('Filter graphs'), __('Toggle filter(s)'), '', false);

$utime = get_system_time();
$current = date('Y-m-d', $utime);

if ($start_date != $current) {
    $date = strtotime($start_date);
} else {
    $date = $utime;
}

if ($combined) {
    // Pass the $modules before the ajax call.
    echo '<div class="combined-graph-container center w100p white_box"'.'data-period="'.$period.'"'.'data-stacked="'.CUSTOM_GRAPH_LINE.'"'.'data-date="'.$date.'"'.'data-height="'.$height.'"'.'>'.html_print_image('images/spinner.gif', true).'</div>';
} else {
    foreach ($modules as $id_module) {
        $title = modules_get_agentmodule_name($id_module);
        $unit = modules_get_unit($id_module);

        echo '<h4>'.$title.'</h4>';
        echo '<div class="sparse-graph-container center w100p"'.'data-id_module="'.$id_module.'"'.'data-period="'.$period.'"'.'data-draw_events="'.(int) $draw_events.'"'.'data-title="'.$title.'"'.'data-draw_alerts="'.(int) $draw_alerts.'"'.'data-date="'.$date.'"'.'data-unit="'.$unit.'"'.'data-date="'.$date.'"'.'data-height="'.$height.'"'.'>'.html_print_image('images/spinner.gif', true).'</div>';
    }
}

echo "<div class='both'></div>";

echo '<div id="graph-error-message" class="invisible">';
ui_print_error_message(__('There was an error loading the graph'));
echo '</div>';

// Dialog to save the custom graph.
echo "<div id='dialog_save_custom_graph' class='invisible'>";
$table = new stdClass();
$table->width = '100%';
$table->style[0] = 'font-weight: bolder; text-align: right;';
$table->data[0][0] = __('Name custom graph');
$table->data[0][1] = html_print_input_text(
    'name_custom_graph',
    '',
    __('Name custom graph'),
    30,
    50,
    true
);

html_print_table($table);

echo "<div style='width: ".$table->width."; text-align: right;'>";
html_print_image(
    'images/spinner.gif',
    false,
    [
        'style' => 'display: none',
        'class' => 'loading_save',
    ]
);
html_print_image(
    'images/ok.png',
    false,
    [
        'style' => 'display: none',
        'class' => 'ok_save',
    ]
);
html_print_image(
    'images/error_red.png',
    false,
    [
        'style' => 'display: none',
        'class' => 'error_save',
    ]
);
html_print_button(
    __('Save'),
    'save_custom_graph',
    false,
    'save_custom_graph_second_step();',
    'class="button_save sub save"'
);
echo '</div>';
echo '</div>';
?>
<script type="text/javascript">
    $(document).ready(function() {
        $("#dialog_save_custom_graph").dialog({
            title: "<?php echo __('Save custom graph'); ?>",
            height: 200,
            width: 500,
            modal: true,
            autoOpen: false
        });
    });

    $('#button-save_custom_graph').click(function(event) {
        $("#dialog_save_custom_graph").dialog("open");
    });

    function save_custom_graph_second_step() {
        $(".button_save").disable();
        $(".ok_save").hide();
        $(".error_save").hide();
        $(".loading_save").show();

        var params = {};
        params["id_modules"] = <?php echo json_encode($modules); ?>;
        params["name"] = $("input[name='name_custom_graph']").val();
        params["description"] = "<?php echo __('Custom graph create from the tab graphs in the agent.'); ?>";
        params["stacked"] = parseInt($("#option_type").val());
        params["width"] = <?php echo $width; ?>;
        params["height"] = <?php echo $height; ?>;
        params["events"] = <?php echo $draw_events; ?>;
        params["period"] = <?php echo $period; ?>;

        params["save_custom_graph"] = 1;
        params["page"] = "include/ajax/graph.ajax";
        jQuery.ajax({
            data: params,
            dataType: "json",
            type: "POST",
            url: "ajax.php",
            success: function(data) {
                $(".loading_save").hide();
                if (data.correct) {
                    $(".ok_save").show();
                } else {
                    $(".error_save").show();
                    $(".button_save").enable();
                }
            }
        });
    }

    // Load graphs
    $(document).ready(function() {
        $('#combined').change(function() {
            if ($('#combined').val() == 1) {
                $('#option_type').empty();
                $('#option_type').append($('<option>', {
                    value: 0,
                    text: "<?php echo __('Area'); ?>"
                }));
                $('#option_type').append($('<option>', {
                    value: 1,
                    text: "<?php echo __('Area stack'); ?>"
                }));
                $('#option_type').append($('<option>', {
                    value: 2,
                    text: "<?php echo __('Line'); ?>"
                }));
                $('#option_type').append($('<option>', {
                    value: 3,
                    text: "<?php echo __('Line stack'); ?>"
                }));
            } else {
                $('#option_type').empty();
                $('#option_type').append($('<option>', {
                    value: 0,
                    text: "<?php echo __('Area'); ?>"
                }));
                $('#option_type').append($('<option>', {
                    value: 2,
                    text: "<?php echo __('Line'); ?>"
                }));
            }
        });

        var getModulesPHP = function() {
            return <?php echo json_encode($modules); ?>;
        }

        var requestGraph = function(type, data) {
            data = data || {};
            type = type || 'custom';
            data.page = 'include/ajax/graph.ajax';
            data['print_' + type + '_graph'] = 1;

            return $.ajax({
                url: 'ajax.php',
                type: 'POST',
                dataType: 'html',
                data: data
            })
        }

        var requestCustomGraph = function(graphId, width, height, period, stacked, date, modules) {
            return requestGraph('custom', {
                page: 'include/ajax/graph.ajax',
                print_custom_graph: 1,
                id_graph: graphId,
                height: height,
                width: width,
                period: period,
                stacked: stacked,
                date: date,
                modules_param: modules
            });
        }

        var requestSparseGraph = function(moduleId, period, showEvents, width, height, title, showAlerts, date, unit, type_g) {
            return requestGraph('sparse', {
                page: 'include/ajax/graph.ajax',
                print_sparse_graph: 1,
                agent_module_id: moduleId,
                period: period,
                show_events: showEvents,
                width: width,
                height: height,
                title: title,
                show_alerts: showAlerts,
                date: date,
                unit: unit,
                type_g: type_g
            });
        }

        var loadCustomGraphs = function() {
            $('div.combined-graph-container').each(function(index, el) {
                loadCustomGraph(el);
            });
        }

        var loadCustomGraph = function(element) {
            var $container = $(element);
            var $errorMessage = $('div#graph-error-message');
            var period = $container.data('period');
            var conf_stacked = parseInt($("#option_type").val());

            switch (conf_stacked) {
                case 0:
                    var stacked = 0;
                    break;
                case 1:
                    var stacked = 1;
                    break;
                case 2:
                    var stacked = 2;
                    break;
                case 3:
                    var stacked = 3;
                    break;
            }

            var date = $container.data('date');
            var height = $container.data('height');

            var modules = getModulesPHP();
            var width = $container.width() - 20;

            var handleSuccess = function(data) {
                $container.html(data);
            }
            var handleError = function(xhr, textStatus, errorThrown) {
                $container.html($errorMessage.html());
            }

            requestCustomGraph(0, -1, height, period, stacked, date, modules)
                .done(handleSuccess)
                .fail(handleError);
        }

        var loadSparseGraphs = function() {
            $('div.sparse-graph-container').each(function(index, el) {
                loadSparseGraph(el);
            });
        }

        var loadSparseGraph = function(element) {
            var $container = $(element);
            var $errorMessage = $('div#graph-error-message');
            var moduleId = $container.data('id_module');
            var period = $container.data('period');
            var showEvents = $container.data('draw_events');
            var title = $container.data('title');
            var showAlerts = $container.data('draw_alerts');
            var date = $container.data('date');
            var unit = $container.data('unit');
            var date = $container.data('date');
            var height = $container.data('height');
            var conf_stacked = parseInt($("#option_type").val());

            switch (conf_stacked) {
                case 0:
                    var type_g = 'area';
                    break;
                case 2:
                    var type_g = 'line';
                    break;
            }

            var width = $container.width() - 20;

            var handleSuccess = function(data) {
                $container.html(data);
            }
            var handleError = function(xhr, textStatus, errorThrown) {
                $container.html($errorMessage.html());
            }

            requestSparseGraph(moduleId, period, showEvents, width, height, title, showAlerts, date, unit, type_g)
                .done(handleSuccess)
                .fail(handleError);
        }

        // Run
        loadCustomGraphs();
        loadSparseGraphs();
    });
</script>