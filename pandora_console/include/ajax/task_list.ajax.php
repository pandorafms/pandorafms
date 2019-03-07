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

if ($progress_task_discovery) {
    $id_task = get_parameter('id', 0);

    if ($id_task !== 0) {
        $result = '';
        $result .= '<ul class="progress_task_discovery">';
        $result .= '<li><h1>'._('Overall Progress').'</h1></li>';
        $result .= '<li>';
        $result .= d3_progress_bar(
            $id_task,
            90,
            460,
            50,
            '#EA5434'
        );
        $result .= '</li>';
        $result .= '<li><h1>'.__('Searching devices in').' red a scanear</h1></li>';
        $result .= '<li>';
        $result .= d3_progress_bar(
            $id_task.'_2',
            30,
            460,
            50,
            '#2751E1'
        );
        $result .= '</li>';
        $result .= '<li><h1>'.__('Summary').'</h1></li>';
        $result .= '<li><span><b>'.__('Estimated').'</b>: total de host</span></li>';
        $result .= '<li><span><b>'.__('Discovered').'</b>: total de agentes</span></li>';
        $result .= '<li><span><b>'.__('Not alive/Not found').'</b>: total de agentes 1-2</span></li>';
        $result .= '</ul>';

        echo $result;
    } else {
        // Error.
        ui_print_error_message(
            __('Please, select task')
        );
    }

    return;
}
