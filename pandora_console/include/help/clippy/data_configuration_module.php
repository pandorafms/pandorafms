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


function clippy_data_configuration_module()
{
    $return_tours = [];
    $return_tours['first_step_by_default'] = true;
    $return_tours['help_context'] = true;
    $return_tours['tours'] = [];

    // ==================================================================
    // Help tour about the monitoring with a ping (step 3)
    // ------------------------------------------------------------------
    $return_tours['tours']['data_configuration_module'] = [];
    $return_tours['tours']['data_configuration_module']['steps'] = [];
    $return_tours['tours']['data_configuration_module']['steps'][] = [
        'init_step_context' => true,
        'intro'             => '<table>'.'<tr>'.'<td class="context_help_title">'.__('Data Configuration Module.').'</td>'.'</tr>'.'<td class="context_help_body">'.__('Please note that information provided here affects how the agent collect information and generate the data XML. Any data/configuration reported by the agent, different from data or description is discarded, and the configuration shown in the console prevails over any configuration coming from the agent, this applies for example for crit/warn thresholds, interval, module group, min/max value, tags, etc.').'</td>'.'</tr>'.'<tr>'.'<td class="context_help_body">'.__('Information imported FIRST time from the XML will fill the information you can see in the console, but after the first import, system will ignore any update coming from the XML/Agent.').'</td>'.'</tr>'.'</table>',
    ];
    $return_tours['tours']['data_configuration_module']['conf'] = [];
    $return_tours['tours']['data_configuration_module']['conf']['autostart'] = false;
    $return_tours['tours']['data_configuration_module']['conf']['show_bullets'] = 0;
    $return_tours['tours']['data_configuration_module']['conf']['show_step_numbers'] = 0;
    // ==================================================================
    return $return_tours;
}
