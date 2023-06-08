<?php
/**
 * Monitors status - general overview.
 *
 * @category   Agent view monitor statuses.
 * @package    Pandora FMS
 * @subpackage Classic agent management view.
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

// Begin.
global $config;

// Ajax tooltip to deploy modules's tag info.
if (is_ajax() === true) {
    $get_tag_tooltip = (bool) get_parameter('get_tag_tooltip', 0);
    $get_relations_tooltip = (bool) get_parameter('get_relations_tooltip', 0);


    if ($get_tag_tooltip === true) {
        $id_agente_modulo = (int) get_parameter('id_agente_modulo');
        if ($id_agente_modulo === 0) {
            return;
        }

        $tags = tags_get_module_tags($id_agente_modulo);


        if ($tags === false) {
            $tags = [];
        }

        echo '<h3>'.__("Tag's information").'</h3>';
        echo "<table border='0'>";
        foreach ($tags as $tag) {
            echo '<tr>';

            echo '<td>';
            if (tags_get_module_policy_tags($tag, $id_agente_modulo)) {
                html_print_image(
                    'images/policies_mc.png',
                    false,
                    [
                        'style' => 'vertical-align: middle;',
                        'class' => 'invert_filter',
                    ]
                );
            }

            echo '</td>';

            echo '<td>';
            echo tags_get_name($tag);
            echo '</td>';

            echo '</tr>';
        }

        echo '</table>';

        return;
    }


    if ($get_relations_tooltip === true) {
        $id_agente_modulo = (int) get_parameter('id_agente_modulo');
        if ($id_agente_modulo === 0) {
            return;
        }

        $id_agente = modules_get_agentmodule_agent($id_agente_modulo);

        $params = [
            'id_agent'  => $id_agente,
            'id_module' => $id_agente_modulo,
        ];
        $relations = modules_get_relations($params);

        if (empty($relations) === true) {
            return;
        }

        $table_relations = new stdClass();
        $table_relations->id = 'module_'.$id_agente_modulo.'_relations';
        $table_relations->width = '100%';
        $table_relations->class = 'databox filters';
        $table_relations->style = [];
        $table_relations->style[0] = 'font-weight: bold;';
        $table_relations->style[2] = 'font-weight: bold;';
        $table_relations->head = [];
        $table_relations->head[0] = __('Relationship information');
        $table_relations->head_colspan[0] = 4;
        $table_relations->data = [];

        foreach ($relations as $relation) {
            if ($relation['module_a'] == $id_agente_modulo) {
                $id_module = $relation['module_b'];
            } else {
                $id_module = $relation['module_a'];
            }

            $id_agent = modules_get_agentmodule_agent($id_module);

            $data = [];
            $data[0] = __('Agent');
            $data[1] = ui_print_agent_name($id_agent, true);
            $data[2] = __('Module');
            $data[3] = "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente
				&id_agente=".$id_agent.'&tab=module&edit_module=1&id_agent_module='.$id_module."'>".ui_print_truncate_text(modules_get_agentmodule_name($id_module), 'module_medium', true, true, true, '[&hellip;]').'</a>';
            $table_relations->data[] = $data;
        }

        html_print_table($table_relations);

        return;
    }

    return;
}

if (isset($id_agente) === false) {
    // This page is included, $id_agente should be passed to it.
    db_pandora_audit(
        AUDIT_LOG_HACK_ATTEMPT,
        'Trying to get the monitor list without id_agent passed'
    );
    include 'general/noaccess.php';
    exit;
}

$id_agent = (int) get_parameter('id_agente');
$status_filter_monitor = (int) get_parameter('status_filter_monitor', -1);
$status_text_monitor = get_parameter('status_text_monitor', '');
$status_hierachy_mode = get_parameter('status_hierachy_mode', -1);
$sort_field = get_parameter('sort_field', 'name');
$sort = get_parameter('sort', 'up');


$modules_not_init = agents_monitor_notinit($id_agente);
if (empty($modules_not_init) === false) {
    $help_not_init = ui_print_warning_message(
        __('Non-initialized modules found.')
    );
} else {
    $help_not_init = '';
}

ob_start();

print_form_filter_monitors(
    $id_agente,
    $status_filter_monitor,
    $status_text_monitor,
    $status_hierachy_mode
);

echo html_print_div(
    [
        'id'      => 'module_list',
        'content' => '',
    ],
    true
);

$html_toggle = ob_get_clean();

html_print_div(
    [
        'class'   => 'agent_details_line',
        'content' => ui_toggle(
            $html_toggle,
            '<span class="subsection_header_title">'.__('List of modules').' '.$help_not_init.reporting_tiny_stats(
                $agent,
                true,
                'modules',
                ':',
                true,
            ).'</span>',
            'status_monitor_agent',
            false,
            false,
            true,
            '',
            'white-box-content',
            'box-flat white_table_graph w100p'
        ),
    ],
);

?>
<script type="text/javascript">
    var sort_field = '<?php echo $sort_field; ?>';
    var sort_rows = '<?php echo $sort; ?>';
    var filter_status = -1;
    var filter_text = "";
    reset_filter_modules ();
    
    $(document).ready(function() {
        /*filter_modules();
        var parameters = {};
        
        parameters["list_modules"] = 1;
        parameters["id_agente"] = <?php echo $id_agente; ?>;
        parameters["page"] = "include/ajax/module";
        
        jQuery.ajax ({
            data: parameters,
            type: 'POST',
            url: "ajax.php",
            dataType: 'html',
            success: function (data) {
                $("#module_list_loading").hide();
                
                $("#module_list").empty();
                $("#module_list").html(data);
            }
        });
        */
    });
    
    function change_module_filter () {
        hierachy_mode = $("#checkbox-status_hierachy_mode").is(":checked");
        if (hierachy_mode) {
            $("#status_module_group").disable();
            $("#status_filter_monitor").disable();
            $("#status_module_group").val(-1);
            $("#status_filter_monitor").val(-1);
        }
        else {
            $("#status_module_group").enable();
            $("#status_filter_monitor").enable();
        }
        filter_modules();
    }

    function order_module_list(sort_field_param, sort_rows_param) {
        sort_field = sort_field_param;
        sort_rows = sort_rows_param;
        
        var parameters = {};
        
        parameters["list_modules"] = 1;
        parameters["id_agente"] = <?php echo $id_agente; ?>;
        parameters["sort_field"] = sort_field;
        parameters["sort"] = sort_rows;
        parameters["status_filter_monitor"] = filter_status;
        parameters["status_text_monitor"] = filter_text;
        parameters["status_module_group"] = filter_group;
        parameters["page"] = "include/ajax/module";

        $("#module_list").empty();
        $("#module_list_loading").show();

        jQuery.ajax ({
            data: parameters,
            type: 'POST',
            url: "ajax.php",
            dataType: 'html',
            success: function (data) {
                $("#module_list_loading").hide();
                $("#module_list").empty();
                $("#module_list").html(data);
            }
        });
    }

    function filter_modules() {
        filter_status = $("#status_filter_monitor").val();
        filter_group = $("#status_module_group").val();
        filter_text = $("input[name='status_text_monitor']").val();
        hierachy_mode = $("#checkbox-status_hierachy_mode").is(":checked");

        var parameters = {};
        
        parameters["list_modules"] = 1;
        parameters["id_agente"] = <?php echo $id_agente; ?>;
        parameters["sort_field"] = sort_field;
        parameters["sort"] = sort_rows;
        parameters["status_filter_monitor"] = filter_status;
        parameters["status_text_monitor"] = filter_text;
        parameters["status_module_group"] = filter_group;
        parameters["hierachy_mode"] = hierachy_mode;
        parameters["filter_monitors"] = 1;
        parameters["monitors_change_filter"] = 1;
        parameters["page"] = "include/ajax/module";
        
        
        $("#module_list").empty();
        $("#module_list_loading").show();
        
        
        jQuery.ajax ({
            data: parameters,
            type: 'POST',
            url: "ajax.php",
            dataType: 'html',
            success: function (data) {
                $("#module_list_loading").hide();
                
                $("#module_list").empty();
                $("#module_list").html(data);
            }
        });
    }
    
    function reset_filter_modules() {
        $("#status_filter_monitor").val(-1);
        $("#status_module_group").val(-1);
        $("input[name='status_text_monitor']").val("");
        $("#checkbox-status_hierachy_mode").prop("checked", false);
        $("#status_module_group").enable();
        $("#status_filter_monitor").enable();
        
        filter_modules();
    }
    
    function pagination_list_modules(offset) {
        var parameters = {};
        
        parameters["list_modules"] = 1;
        parameters["id_agente"] = <?php echo $id_agente; ?>;
        parameters["offset"] = offset;
        parameters["sort_field"] = sort_field;
        parameters["sort"] = sort_rows;
        parameters["status_filter_monitor"] = filter_status;
        parameters["status_text_monitor"] = filter_text;
        parameters["status_module_group"] = filter_group;
        parameters["filter_monitors"] = 0;
        parameters["monitors_change_filter"] = 0;
        parameters["page"] = "include/ajax/module";
        
        
        $("#module_list").empty();
        $("#module_list_loading").show();
        
        
        jQuery.ajax ({
            data: parameters,
            type: 'POST',
            url: "ajax.php",
            dataType: 'html',
            success: function (data) {
                $("#module_list_loading").hide();
                
                $("#module_list").empty();
                $("#module_list").html(data);
            }
        });
    }
</script>
<?php
ui_require_css_file('cluetip', 'include/styles/js/');
ui_require_jquery_file('cluetip');

echo "<div id='module_details_dialog' class='display:none'></div>";

ui_include_time_picker();
ui_require_jquery_file('ui.datepicker-'.get_user_language(), 'include/javascript/i18n/');
?>

<script type="text/javascript">
/* <![CDATA[ */
    $("a.tag_details").cluetip ({
            arrows: true,
            clickThrough: false,
            attribute: 'href',
            cluetipClass: 'default'
        });
    $("a.relations_details").cluetip ({
            width: 500,
            arrows: true,
            clickThrough: false,
            attribute: 'href',
            cluetipClass: 'default',
            sticky: true,
            mouseOutClose: 'both',
            closeText: '<?php html_print_image('images/cancel.png', false, ['class' => 'invert_filter']); ?>'
        });
        
    // Show the modal window of an module
    function show_module_detail_dialog(module_id, id_agent, server_name, offset, period,module_name) {
        var server_name = '';
        var extra_parameters = '';
        if ($('input[name=selection_mode]:checked').val()) {
            
            period = $('#period').val();
            
            var selection_mode = $('input[name=selection_mode]:checked').val();
            var date_from = $('#text-date_from').val();
            var time_from = $('#text-time_from').val();
            var date_to = $('#text-date_to').val();
            var time_to = $('#text-time_to').val();
            
            extra_parameters = '&selection_mode=' + selection_mode + '&date_from=' + date_from + '&date_to=' + date_to + '&time_from=' + time_from + '&time_to=' + time_to;
        }

        // Get the free text in both options
        var freesearch = $('#text-freesearch').val();
        if (freesearch != null && freesearch !== '') {
            var free_checkbox = $('input[name=free_checkbox]:checked').val();
            extra_parameters += '&freesearch=' + freesearch;
            if (free_checkbox == 1) {
                extra_parameters += '&free_checkbox=1';
            } else {
                extra_parameters += '&free_checkbox=0';
            }
        }
        
        title = <?php echo '"'.__('Module: ').'"'; ?>;
        $.ajax({
            type: "POST",
            url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
            data: "page=include/ajax/module&get_module_detail=1&server_name="+server_name+"&id_agent="+id_agent+"&id_module=" + module_id+"&offset="+offset+"&period="+period + extra_parameters,
            dataType: "html",
            success: function(data) {
                $("#module_details_dialog").hide ()
                    .empty ()
                    .append (data)
                    .dialog ({
                        resizable: true,
                        draggable: true,
                        modal: true,
                        title: title + module_name,
                        overlay: {
                            opacity: 0.5,
                            background: "black"
                        },
                        width: 650,
                        height: 500
                    })
                    .show ();
                    refresh_pagination_callback (module_id, id_agent, "",module_name);
                    datetime_picker_callback();
                    forced_title_callback();
            }
        });
    }
    function datetime_picker_callback() {
        
        $("#text-time_from, #text-time_to").timepicker({
            showSecond: true,
            timeFormat: '<?php echo TIME_FORMAT_JS; ?>',
            timeOnlyTitle: '<?php echo __('Choose time'); ?>',
            timeText: '<?php echo __('Time'); ?>',
            hourText: '<?php echo __('Hour'); ?>',
            minuteText: '<?php echo __('Minute'); ?>',
            secondText: '<?php echo __('Second'); ?>',
            currentText: '<?php echo __('Now'); ?>',
            closeText: '<?php echo __('Close'); ?>'});
            
        $("#text-date_from, #text-date_to").datepicker({dateFormat: "<?php echo DATE_FORMAT_JS; ?>"});
        
        $.datepicker.setDefaults($.datepicker.regional[ "<?php echo get_user_language(); ?>"]);
    }
    datetime_picker_callback();
    
    function refresh_pagination_callback (module_id, id_agent, server_name,module_name) {
        $(".binary_dialog").click( function() {
            
            var classes = $(this).attr('class');
            classes = classes.split(' ');
            var offset_class = classes[2];
            offset_class = offset_class.split('_');
            var offset = offset_class[1];
            
            var period = $('#period').val();
            
            show_module_detail_dialog(module_id, id_agent, server_name, offset, period,module_name);
            return false;
        });
    }
/* ]]> */
</script>
<?php
/**
 * Print form filter monitors.
 *
 * @param integer $id_agent              Id_agent.
 * @param integer $status_filter_monitor Status_filter_monitor.
 * @param string  $status_text_monitor   Status_text_monitor.
 * @param integer $status_module_group   Status_module_group.
 * @param integer $status_hierachy_mode  Status_hierachy_mode.
 *
 * @return void
 */
function print_form_filter_monitors(
    $id_agent,
    $status_filter_monitor=-1,
    $status_text_monitor='',
    $status_module_group=-1,
    $status_hierachy_mode=-1
) {
    $status_list = [
        -1                                 => __('All'),
        AGENT_MODULE_STATUS_CRITICAL_BAD   => __('Critical'),
        AGENT_MODULE_STATUS_CRITICAL_ALERT => __('Alert'),
        AGENT_MODULE_STATUS_NORMAL         => __('Normal'),
        AGENT_MODULE_STATUS_NOT_NORMAL     => __('Not Normal'),
        AGENT_MODULE_STATUS_WARNING        => __('Warning'),
        AGENT_MODULE_STATUS_UNKNOWN        => __('Unknown'),
    ];

    $rows = db_get_all_rows_sql(
        sprintf(
            'SELECT * 
         FROM tmodule_group 
         WHERE id_mg IN (SELECT id_module_group
                         FROM tagente_modulo 
                        WHERE id_agente = %d )
         ORDER BY name',
            $id_agent
        )
    );

    $rows_select[-1] = __('All');
    if (empty($rows) === false) {
        foreach ($rows as $module_group) {
            $rows_select[$module_group['id_mg']] = __($module_group['name']);
        }
    }

    $form_text = '';
    $table = new stdClass();
    $table->class = 'filter-table-adv';
    // $table->id = 'module_filter_agent_view';
    // $table->styleTable = 'border-radius: 0;padding: 0;margin: 0 0 10px;';
    $table->width = '100%';
    $table->size[0] = '25%';
    $table->size[1] = '25%';
    $table->size[2] = '25%';
    $table->size[3] = '15%';
    $table->size[4] = '10%';
    // Captions.
    $table->data[0][0] = html_print_label_input_block(
        html_print_input_hidden('filter_monitors', 1, true).html_print_input_hidden(
            'monitors_change_filter',
            1,
            true
        ).__('Status:'),
        html_print_select(
            $status_list,
            'status_filter_monitor',
            $status_filter_monitor,
            '',
            '',
            0,
            true,
            false,
            true,
            'w100p',
            false,
            'width:100%'
        )
    );

    $table->data[0][1] = html_print_label_input_block(
        __('Free text for search (*):').ui_print_help_tip(
            __('Search by module name, list matches.'),
            true
        ),
        html_print_input_text(
            'status_text_monitor',
            $status_text_monitor,
            '',
            '',
            100,
            true
        )
    );

    $table->data[0][2] = html_print_label_input_block(
        __('Module group'),
        html_print_select(
            $rows_select,
            'status_module_group',
            $status_module_group,
            '',
            '',
            0,
            true,
            false,
            true,
            'w100p',
            false,
            'width:100%'
        )
    );

    $table->data[0][3] = html_print_label_input_block(
        __('Show in hierachy mode'),
        html_print_switch(
            [
                'name'     => 'status_hierachy_mode',
                'value'    => $all_events_24h,
                'onchange' => 'change_module_filter()',
                'id'       => 'checkbox-status_hierachy_mode',
            ]
        )
    );

    $table->data[0][4] = html_print_button(
        __('Filter'),
        'filter',
        false,
        'filter_modules();',
        [
            'icon'  => 'search',
            'mode'  => 'secondary mini',
            'style' => 'margin-left: 15px',
        ],
        true
    ).html_print_button(
        __('Reset'),
        'filter',
        false,
        'reset_filter_modules();',
        [
            'icon' => 'fail',
            'mode' => 'secondary mini',
        ],
        true
    );
    $table->cellstyle[0][4] = 'width:20%;display: flex;flex-direction: row-reverse;justify-content: flex-end;height: 60px;align-items: flex-end;width:10%;';

    $form_text = html_print_table($table, true);

    // TODO. Unused code.
    if ($status_filter_monitor === -1 && empty($status_text_monitor) === true && $status_module_group === -1) {
        $filter_hidden = true;
    } else {
        $filter_hidden = false;
    }

    echo $form_text;
}
