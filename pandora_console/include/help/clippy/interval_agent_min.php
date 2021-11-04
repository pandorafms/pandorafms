<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package    Include
 * @subpackage Clippy
 */


function clippy_interval_agent_min()
{
    $return_tours = [];
    $return_tours['first_step_by_default'] = true;
    $return_tours['help_context'] = true;
    $return_tours['tours'] = [];

    // ==================================================================
    // Help tour about the monitoring with a ping (step 3)
    // ------------------------------------------------------------------
    $return_tours['tours']['interval_agent_min'] = [];
    $return_tours['tours']['interval_agent_min']['steps'] = [];
    $return_tours['tours']['interval_agent_min']['steps'][] = [
        'init_step_context' => true,
        'intro'             => '<table>'.'<tr>'.'<td class="context_help_title">'.__('Interval Agent.').'</td>'.'</tr>'.'<tr>'.'<td class="context_help_body">'.__('Please note that having agents with a monitoring interval below 300 seconds is not recommended. This will impact seriously in the performance of the server. For example, having 200 agents with one minute interval, is the same than having 1000 agents with a 5 minute interval. The probability of getting unknown modules is higher, and the impact on the server is higher because it requires a shorter response time.').'</td>'.'</tr>'.'</table>',
    ];
    $return_tours['tours']['interval_agent_min']['conf'] = [];
    $return_tours['tours']['interval_agent_min']['conf']['autostart'] = false;
    $return_tours['tours']['interval_agent_min']['conf']['show_bullets'] = 0;
    $return_tours['tours']['interval_agent_min']['conf']['show_step_numbers'] = 0;
    // ==================================================================
    return $return_tours;
}
