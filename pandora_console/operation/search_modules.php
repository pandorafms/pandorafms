<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
global $config;

enterprise_include_once('include/functions_policies.php');
require_once $config['homedir'].'/include/functions_modules.php';
require_once $config['homedir'].'/include/functions_users.php';

$searchModules = check_acl($config['id_user'], 0, 'AR');

if (!$modules || !$searchModules) {
    echo "<br><div class='nf'>".__('Zero results found')."</div>\n";
} else {
    // Show the modal window of an module.
    echo '<div id="module_details_dialog" class=""></div>';

    $table = new stdClass();
    $table->cellpadding = 4;
    $table->cellspacing = 4;
    $table->width = '98%';
    $table->class = 'info_table';

    $table->head = [];
    $table->head[0] = __('Module').' <a href="index.php?search_category=modules&keywords='.$config['search_keywords'].'&head_search_keywords=abc&offset='.$offset.'&sort_field=module_name&sort=up">'.html_print_image('images/sort_up.png', true, ['style' => $selectModuleNameUp]).'</a><a href="index.php?search_category=modules&keywords='.$config['search_keywords'].'&head_search_keywords=abc&offset='.$offset.'&sort_field=module_name&sort=down">'.html_print_image('images/sort_down.png', true, ['style' => $selectModuleNameDown]).'</a>';
    $table->head[1] = __('Agent').' <a href="index.php?search_category=modules&keywords='.$config['search_keywords'].'&head_search_keywords=abc&offset='.$offset.'&sort_field=agent_name&sort=up">'.html_print_image('images/sort_up.png', true, ['style' => $selectAgentNameUp]).'</a><a href="index.php?search_category=modules&keywords='.$config['search_keywords'].'&head_search_keywords=abc&offset='.$offset.'&sort_field=agent_name&sort=down">'.html_print_image('images/sort_down.png', true, ['style' => $selectAgentNameDown]).'</a>';
    $table->head[2] = __('Type');
    $table->head[3] = __('Interval');
    $table->head[4] = __('Status');
    $table->head[5] = __('Graph');
    $table->head[6] = __('Data');
    $table->head[7] = __('Timestamp');
    $table->head[8] = '';



    $table->align = [];
    $table->align[0] = 'left';
    $table->align[1] = 'left';
    $table->align[2] = 'left';
    $table->align[3] = 'left';
    $table->align[4] = 'left';
    $table->align[5] = 'left';
    $table->align[6] = 'left';
    $table->align[7] = 'left';
    $table->align[8] = 'left';

    $table->headstyle = [];
    $table->headstyle[0] = 'text-align: left';
    $table->headstyle[1] = 'text-align: left';
    $table->headstyle[2] = 'text-align: left';
    $table->headstyle[3] = 'text-align: left';
    $table->headstyle[4] = 'text-align: left';
    $table->headstyle[5] = 'text-align: left';
    $table->headstyle[6] = 'text-align: left';
    $table->headstyle[7] = 'text-align: left';
    $table->headstyle[8] = 'text-align: left';

    $table->data = [];

    $id_type_web_content_string = db_get_value(
        'id_tipo',
        'ttipo_modulo',
        'nombre',
        'web_content_string'
    );

    $i = 0;
    foreach ($modules as $module) {
        $module['datos'] = modules_get_last_value($module['id_agente_modulo']);
        $module['module_name'] = $module['nombre'];

        // To search the monitor status
        $status_sql = sprintf('SELECT estado from tagente_estado where id_agente_modulo ='.$module['id_agente_modulo']);
        $status_sql = db_process_sql($status_sql);
        $status_sql = $status_sql[0];
        // To search the monitor utimestamp
        $utimestamp_sql = sprintf('SELECT utimestamp from tagente_estado where id_agente_modulo ='.$module['id_agente_modulo']);
        $utimestamp_sql = db_process_sql($utimestamp_sql);
        $utimestamp_sql = $utimestamp_sql[0];


        $agent = db_get_row('tagente', 'id_agente', $module['id_agente']);
        $agentCell = '<a title='.$module['agent_name'].' href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$module['id_agente'].'">'.$agent['alias'].'</a>';

        $typeCell = ui_print_moduletype_icon($module['id_tipo_modulo'], true);

        $intervalCell = modules_get_interval($module['id_agente_modulo']);


        $module_last_value = modules_get_last_value($module['id_agente_modulo']);
        if (!is_numeric($module_last_value)) {
            $module_last_value = htmlspecialchars($module_last_value);
        }

        if ($utimestamp_sql['utimestamp'] == 0
            && (                ($module['id_tipo_modulo'] < 21 || $module['id_tipo_modulo'] > 23)
            && $module['id_tipo_modulo'] != 100)
        ) {
            $statusCell = ui_print_status_image(
                STATUS_MODULE_NO_DATA,
                __('NOT INIT'),
                true
            );
        } else if ($status_sql['estado'] == 0) {
            $statusCell = ui_print_status_image(
                STATUS_MODULE_OK,
                __('NORMAL').': '.$module_last_value,
                true
            );
        } else if ($status_sql['estado'] == 1) {
            $statusCell = ui_print_status_image(
                STATUS_MODULE_CRITICAL,
                __('CRITICAL').': '.$module_last_value,
                true
            );
        } else if ($status_sql['estado'] == 2) {
            $statusCell = ui_print_status_image(
                STATUS_MODULE_WARNING,
                __('WARNING').': '.$module_last_value,
                true
            );
        } else if ($status_sql['estado'] == 3) {
            $statusCell = ui_print_status_image(
                STATUS_MODULE_UNKNOWN,
                __('UNKNOWN').': '.$module_last_value,
                true
            );
        } else {
            $last_status = modules_get_agentmodule_last_status($module['id_agente_modulo']);
            switch ($last_status) {
                case 0:
                    $statusCell = ui_print_status_image(
                        STATUS_MODULE_OK,
                        __('UNKNOWN').' - '.__('Last status').' '.__('NORMAL').': '.$module_last_value,
                        true
                    );
                break;

                case 1:
                    $statusCell = ui_print_status_image(
                        STATUS_MODULE_CRITICAL,
                        __('UNKNOWN').' - '.__('Last status').' '.__('CRITICAL').': '.$module_last_value,
                        true
                    );
                break;

                case 2:
                    $statusCell = ui_print_status_image(
                        STATUS_MODULE_WARNING,
                        __('UNKNOWN').' - '.__('Last status').' '.__('WARNING').': '.$module_last_value,
                        true
                    );
                break;
            }
        }

        $graphCell = '';
        $table->cellclass[$i][5] = 'table_action_buttons';
        if ($module['history_data'] == 1) {
            $graph_type = return_graphtype($module['id_tipo_modulo']);

            $name_module_type = modules_get_moduletype_name($module['id_tipo_modulo']);
            $handle = 'stat'.$name_module_type.'_'.$module['id_agente_modulo'];
            $url = 'include/procesos.php?agente='.$module['id_agente_modulo'];
            $win_handle = dechex(crc32($module['id_agente_modulo'].$module['module_name']));

            $link = "winopeng('".'operation/agentes/stat_win.php?'."type=$graph_type&".'period='.SECONDS_1DAY.'&id='.$module['id_agente_modulo'].'&refresh='.SECONDS_10MINUTES."', "."'day_".$win_handle."')";
            $link_module_detail = 'show_module_detail_dialog('.$module['id_agente_modulo'].', '.$module['id_agente'].', '."'', 0, ".SECONDS_1DAY.", '".$module['module_name']."')";

            $graphCell = '<a href="javascript:'.$link.'">'.html_print_image('images/module-graph.svg', true, ['border' => 0, 'alt' => '', 'class' => 'main_menu_icon invert_filter' ]).'</a>';
            $graphCell .= '&nbsp;<a href="javascript:'.$link_module_detail.'">'.html_print_image(
                'images/simple-value.svg',
                true,
                [
                    'border' => '0',
                    'alt'    => '',
                    'class'  => 'main_menu_icon invert_filter',
                ]
            ).'</a>';
        }

        if (is_numeric(modules_get_last_value($module['id_agente_modulo']))) {
            $dataCell = format_numeric(modules_get_last_value($module['id_agente_modulo']));
        } else {
            $dataCell = ui_print_module_string_value(
                $module['datos'],
                $module['id_agente_modulo'],
                $module['current_interval']
            );
        }

        if ($module['estado'] == 3) {
            $option = ['html_attr' => 'class="redb"'];
        } else {
            $option = [];
        }

        $timestampCell = ui_print_timestamp($utimestamp_sql['utimestamp'], true, $option);


        $group_agent = agents_get_agent_group($module['id_agente']);

        $table->cellclass[$i][8] = 'table_action_buttons';
        if (check_acl($config['id_user'], $group_agent, 'AW')) {
            $edit_module = 'aaa';

            $url_edit = 'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente='.$module['id_agente'].'&tab=module&id_agent_module='.$module['id_agente_modulo'].'&edit_module=1';

            $edit_module = '<a href="'.$url_edit.'">'.html_print_image('images/edit.svg', true).'</a>';
        } else {
            $edit_module = '';
        }


        array_push(
            $table->data,
            [
                $module['module_name'],
                $agentCell,
                $typeCell,
                $intervalCell,
                $statusCell,
                $graphCell,
                $dataCell,
                $timestampCell,
                $edit_module,
            ]
        );

        $i++;
    }

    echo '<br />';
    html_print_table($table);
    unset($table);
    $tablePagination = ui_pagination(
        $totalModules,
        false,
        0,
        0,
        true,
        'offset',
        false
    );

    html_print_action_buttons(
        '',
        [
            'type'          => 'data_table',
            'class'         => 'fixed_action_buttons',
            'right_content' => $tablePagination,
        ]
    );
}

ui_include_time_picker();
ui_require_jquery_file('ui.datepicker-'.get_user_language(), 'include/javascript/i18n/');

?>

<script type="text/javascript">
    function show_module_detail_dialog(module_id, id_agent, server_name, offset, period, module_name) {

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
            data: "page=include/ajax/module&get_module_detail=1&server_name=" + server_name + "&id_agent=" + id_agent + "&id_module=" + module_id + "&offset=" + offset + "&period=" + period + extra_parameters,
            dataType: "html",
            success: function(data) {
                $("#module_details_dialog").hide()
                    .empty()
                    .append(data)
                    .dialog({
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
                    .show();
                refresh_pagination_callback(module_id, id_agent, "", module_name);
                datetime_picker_callback();
                forced_title_callback();
            }
        });
    }

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

</script>