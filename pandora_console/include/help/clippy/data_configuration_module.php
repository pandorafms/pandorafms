<?php

// Pandora FMS - https://pandorafms.com
// ==================================================
// Copyright (c) 2005-2023 Pandora FMS
// Please see https://pandorafms.com/community/ for full contribution list
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
        'intro'             => __('Please note that information provided here affects how the agent collect information and generate the data XML. Any data/configuration reported by the agent, different from data or description is discarded, and the configuration shown in the console prevails over any configuration coming from the agent, this applies for example for crit/warn thresholds, interval, module group, min/max value, tags, etc.').__('Information imported FIRST time from the XML will fill the information you can see in the console, but after the first import, system will ignore any update coming from the XML/Agent.'),
        'title'             => __('Data Configuration Module.'),
        'img'               => html_print_image(
            'images/info-warning.svg',
            true,
            [
                'class' => 'main_menu_icon invert_filter',
                'style' => 'margin-left: 5px;',
            ]
        ),
    ];
    $return_tours['tours']['data_configuration_module']['conf'] = [];
    $return_tours['tours']['data_configuration_module']['conf']['autostart'] = false;
    $return_tours['tours']['data_configuration_module']['conf']['show_bullets'] = 0;
    $return_tours['tours']['data_configuration_module']['conf']['show_step_numbers'] = 0;
    // ==================================================================
    return $return_tours;
}
