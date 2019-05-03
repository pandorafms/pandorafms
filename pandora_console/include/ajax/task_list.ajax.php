<?php
/**
 * Extension to schedule tasks on Pandora FMS Console
 *
 * @category   Ajax
 * @package    Pandora FMS
 * @subpackage Host&Devices
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
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

require_once $config['homedir'].'/include/graphs/functions_d3.php';

$progress_task_discovery = (bool) get_parameter('progress_task_discovery', 0);
$showmap = (bool) get_parameter('showmap', 0);

if ($progress_task_discovery) {
    $id_task = get_parameter('id', 0);

    if ($id_task <= 0) {
        echo json_encode(['error' => true]);
        return;
    }

    $task = db_get_row('trecon_task', 'id_rt', $id_task);
    $global_progress = $task['status'];
    $summary = json_decode($task['summary'], true);

    $result = '<div id = progress_task_'.$id_task.'>';
    if ($task['utimestamp']) {
        $result .= '<div style="display: flex;">';
        $result .= '<div style="width: 100%; text-align: center; margin-top: 40px;">';
        $result .= '<span style="font-size: 1.9em; font-family: "lato-bolder", "Open Sans", sans-serif !important;">'._('Overall Progress').'</span>';

        $result .= '<div style="margin-top: 25px;">';
        $result .= progress_circular_bar(
            $id_task,
            ($global_progress < 0) ? 100 : $global_progress,
            200,
            200,
            '#7eb641',
            '%',
            '',
            '#3A3A3A',
            0
        );

        $result .= '</div>';

        if ($global_progress > 0) {
            switch ($summary['step']) {
                case STEP_SCANNING:
                    $str = __('Scanning network');
                break;

                case STEP_AFT:
                    $str = __('Finding AFT connectivity');
                break;

                case STEP_TRACEROUTE:
                    $str = __('Finding traceroute connectivity');
                break;

                case STEP_GATEWAY:
                    $str = __('Finding gateway connectivity');
                break;

                default:
                    $str = __('Searching for devices...');
                break;
            }

            $result .= '</div>';
            $result .= '<div style="width: 100%; text-align: center; margin-top: 40px;">';
            $result .= '<span style="font-size: 1.9em; font-family: "lato-bolder", "Open Sans", sans-serif !important;">'.$str.' ';
            $result .= $summary['c_network_name'];
            $result .= '</span>';

            $result .= '<div style="margin-top: 25px;">';
            $result .= progress_circular_bar(
                $id_task.'_detail',
                $summary['c_network_percent'],
                200,
                200,
                '#7eb641',
                '%',
                '',
                '#3A3A3A',
                0
            );
            $result .= '</div></div>';
        }

        $result .= '</div></div>';

        $i = 0;
        $table = new StdClasS();
        $table->class = 'databox data';
        $table->width = '75%';
        $table->styleTable = 'margin: 2em auto 0;border: 1px solid #ddd;background: white;';
        $table->rowid = [];
        $table->data = [];

        // Content.
        $table->data[$i][0] = '<b>'.__('Hosts discovered').'</b>';
        $table->data[$i][1] = '<span id="discovered">';
        $table->data[$i][1] .= $summary['summary']['discovered'];
        $table->data[$i++][1] .= '</span>';

        $table->data[$i][0] = '<b>'.__('Alive').'</b>';
        $table->data[$i][1] = '<span id="alive">';
        $table->data[$i][1] .= $summary['summary']['alive'];
        $table->data[$i++][1] .= '</span>';

        $table->data[$i][0] = '<b>'.__('Not alive').'</b>';
        $table->data[$i][1] = '<span id="not_alive">';
        $table->data[$i][1] .= $summary['summary']['not_alive'];
        $table->data[$i++][1] .= '</span>';

        $table->data[$i][0] = '<b>'.__('Responding SNMP').'</b>';
        $table->data[$i][1] = '<span id="SNMP">';
        $table->data[$i][1] .= $summary['summary']['SNMP'];
        $table->data[$i++][1] .= '</span>';

        $table->data[$i][0] = '<b>'.__('Responding WMI').'</b>';
        $table->data[$i][1] = '<span id="WMI">';
        $table->data[$i][1] .= $summary['summary']['WMI'];
        $table->data[$i++][1] .= '</span>';

        $result .= '<div style="margin-top: 40px; text-align: center;"><span style="font-size: 1.9em; font-family: "lato-bolder", "Open Sans", sans-serif !important;">'.__('Summary').'</span></div>';
        $result .= html_print_table($table, true).'</div>';
    } else {
        $global_progress = -1;
        $result .= ui_print_error_message(
            __('No data to show'),
            '',
            true
        ).'</div>';
    }

    $result_array['status'] = $global_progress;
    $result_array['html'] = $result;

    echo json_encode($result_array);
    return;
}

if ($showmap) {
    include_once $config['homedir'].'/include/class/NetworkMap.class.php';
    $id_task = get_parameter('id', 0);

    $map = new NetworkMap(
        [
            'id_task' => $id_task,
            'pure'    => 1,
            'widget'  => true,
        ]
    );
    $map->printMap();
}
