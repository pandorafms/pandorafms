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
require_once $config['homedir'].'/include/functions_users.php';

if ($only_count) {
    ob_start();
}

// TODO: CLEAN extra_sql
$extra_sql = '';

$searchAgents = check_acl($config['id_user'], 0, 'AR');

if (!$agents || !$searchAgents) {
    if (!$only_count) {
        echo "<br><div class='nf'>".__('Zero results found')."</div>\n";
    }
} else {
    $table = new stdClass();
    $table->cellpadding = 4;
    $table->cellspacing = 4;
    $table->width = '98%';
    $table->class = 'info_table';

    $table->head = [];

    if ($only_count) {
        $table->head[0] = __('Agent');
        $table->head[1] = __('Description');
        $table->head[2] = __('OS');
        $table->head[3] = __('Interval');
        $table->head[4] = __('Group');
    } else {
        $table->head[0] = __('Agent').' '.'<a href="index.php?search_category=agents&keywords='.$config['search_keywords'].'&head_search_keywords=abc&offset='.$offset.'&sort_field=name&sort=up">'.html_print_image('images/sort_up.png', true, ['style' => $selectNameUp]).'</a>'.'<a href="index.php?search_category=agents&keywords='.$config['search_keywords'].'&head_search_keywords=abc&offset='.$offset.'&sort_field=name&sort=down">'.html_print_image('images/sort_down.png', true, ['style' => $selectNameDown]).'</a>';
        $table->head[1] = __('Description').' '.'<a href="index.php?search_category=agents&keywords='.$config['search_keywords'].'&head_search_keywords=abc&offset='.$offset.'&sort_field=comentarios&sort=up">'.html_print_image('images/sort_up.png', true, ['style' => $selectDescriptionUp]).'</a>'.'<a href="index.php?search_category=agents&keywords='.$config['search_keywords'].'&head_search_keywords=abc&offset='.$offset.'&sort_field=comentarios&sort=down">'.html_print_image('images/sort_down.png', true, ['style' => $selectDescriptionDown]).'</a>';
        $table->head[2] = __('OS').' '.'<a href="index.php?search_category=agents&keywords='.$config['search_keywords'].'&head_search_keywords=abc&offset='.$offset.'&sort_field=os&sort=up">'.html_print_image('images/sort_up.png', true, ['style' => $selectOsUp]).'</a>'.'<a href="index.php?search_category=agents&keywords='.$config['search_keywords'].'&head_search_keywords=abc&offset='.$offset.'&sort_field=os&sort=down">'.html_print_image('images/sort_down.png', true, ['style' => $selectOsDown]).'</a>';
        $table->head[3] = __('Interval').' '.'<a href="index.php?search_category=agents&keywords='.$config['search_keywords'].'&head_search_keywords=abc&offset='.$offset.'&sort_field=interval&sort=up">'.html_print_image('images/sort_up.png', true, ['style' => $selectIntervalUp]).'</a>'.'<a href="index.php?search_category=agents&keywords='.$config['search_keywords'].'&head_search_keywords=abc&offset='.$offset.'&sort_field=interval&sort=down">'.html_print_image('images/sort_down.png', true, ['style' => $selectIntervalDown]).'</a>';
        $table->head[4] = __('Group').' '.'<a href="index.php?search_category=agents&keywords='.$config['search_keywords'].'&head_search_keywords=abc&offset='.$offset.'&sort_field=group&sort=up">'.html_print_image('images/sort_up.png', true, ['style' => $selectGroupUp]).'</a>'.'<a href="index.php?search_category=agents&keywords='.$config['search_keywords'].'&head_search_keywords=abc&offset='.$offset.'&sort_field=group&sort=down">'.html_print_image('images/sort_down.png', true, ['style' => $selectGroupDown]).'</a>';
    }

    $table->head[5] = __('Modules');
    $table->head[6] = __('Status');
    $table->head[7] = __('Alerts');
    $table->head[8] = __('Last contact').' '.'<a href="index.php?search_category=agents&keywords='.$config['search_keywords'].'&head_search_keywords=abc&offset='.$offset.'&sort_field=last_contact&sort=up">'.html_print_image('images/sort_up.png', true, ['style' => $selectLastContactUp]).'</a>'.'<a href="index.php?search_category=agents&keywords='.$config['search_keywords'].'&head_search_keywords=abc&offset='.$offset.'&sort_field=last_contact&sort=down">'.html_print_image('images/sort_down.png', true, ['style' => $selectLastContactDown]).'</a>';
    $table->head[9] = '';

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
    $table->headstyle[9] = 'text-align: center';

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
    $table->align[9] = 'center';

    $table->data = [];

    foreach ($agents as $agent) {
        $agent_info = reporting_get_agent_module_info($agent['id_agente']);

        $modulesCell = reporting_tiny_stats($agent_info, true);

        if ($agent['disabled']) {
            $cellName = '<em>'.'<a style href=index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$agent['id_agente'].' title='.$agent['nombre'].'><b>'.'<span style>'.$agent['alias'].'</span></b></a>'.ui_print_help_tip(__('Disabled'), true).'</em>';
        } else {
            $cellName = '<a style href=index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$agent['id_agente'].' title='.$agent['nombre'].'><b>'.'<span style>'.$agent['alias'].'</span></b></a>';
        }

        if ($agent['quiet']) {
            $cellName .= '&nbsp;';
            $cellName .= html_print_image('images/dot_blue.png', true, ['border' => '0', 'title' => __('Quiet'), 'alt' => '']);
        }

        $in_planned_downtime = db_get_sql(
            'SELECT executed FROM tplanned_downtime 
			INNER JOIN tplanned_downtime_agents 
			ON tplanned_downtime.id = tplanned_downtime_agents.id_downtime
			WHERE tplanned_downtime_agents.id_agent = '.$agent['id_agente'].' AND tplanned_downtime.executed = 1'
        );

        if ($in_planned_downtime) {
            $cellName .= '<em>'.ui_print_help_tip(__('Agent in scheduled downtime'), true, 'images/minireloj-16.png');
            $cellName .= '</em>';
        }

        $last_time = time_w_fixed_tz($agent['ultimo_contacto']);
        $now = get_system_time();
        $diferencia = ($now - $last_time);
        $time = ui_print_timestamp($last_time, true);
        $time_style = $time;
        if ($diferencia > ($agent['intervalo'] * 2)) {
            $time_style = '<b><span class="color_ff0">'.$time.'</span></b>';
        }

        $manage_agent = '';

        if (check_acl($config['id_user'], $agent['id_grupo'], 'AW')) {
            $url_manage = 'index.php?sec=estado&sec2=godmode/agentes/configurar_agente&id_agente='.$agent['id_agente'];
            $manage_agent = '<a href="'.$url_manage.'">'.html_print_image(
                'images/cog.png',
                true,
                [
                    'title' => __('Manage'),
                    'alt'   => __('Manage'),
                    'class' => 'invert_filter',
                ]
            ).'</a>';
        }

        $table->cellclass[][9] = 'table_action_buttons';

        array_push(
            $table->data,
            [
                $cellName,
                ui_print_truncate_text($agent['comentarios'], 'comentarios', false, true, true, '[&hellip;]'),
                ui_print_os_icon($agent['id_os'], false, true),
                human_time_description_raw($agent['intervalo'], false, 'tiny'),
                ui_print_group_icon($agent['id_grupo'], true),
                $modulesCell,
                $agent_info['status_img'],
                $agent_info['alert_img'],
                $time_style,
                $manage_agent,
            ]
        );
    }

    echo '<br />';

    html_print_table($table);
    unset($table);
    if (!$only_count) {
        $tablePagination = ui_pagination(
            $totalAgents,
            false,
            0,
            0,
            true,
            'offset',
            false
        );
    }

    html_print_action_buttons(
        '',
        [
            'type'          => 'data_table',
            'class'         => 'fixed_action_buttons',
            'right_content' => $tablePagination,
        ]
    );
}

if ($only_count) {
    $list_agents = ob_get_clean();
}
