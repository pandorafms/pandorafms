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


function clippy_start_page()
{
    $return_tours = [];
    $return_tours['first_step_by_default'] = false;
    $return_tours['tours'] = [];

    // ==================================================================
    // Help tour about the monitoring with a ping (step 1)
    // ------------------------------------------------------------------
    $return_tours['tours']['monitoring_server_step_1'] = [];
    $return_tours['tours']['monitoring_server_step_1']['steps'] = [];
    $return_tours['tours']['monitoring_server_step_1']['steps'][] = [
        'element' => '#clippy',
        'intro'   => __('I\'m going to show you how to monitor a server.'),
    ];
    $return_tours['tours']['monitoring_server_step_1']['steps'][] = [
        'element' => 'input[name="search"]',
        'intro'   => __('Please, type an agent to save the modules for monitoring a server.'),
    ];
    $return_tours['tours']['monitoring_server_step_1']['steps'][] = [
        'element'  => 'input[name="srcbutton"]',
        'position' => 'left',
        'intro'    => __('If you have typed the name correctly you will see the agent.'),
    ];
    $return_tours['tours']['monitoring_server_step_1']['conf'] = [];
    $return_tours['tours']['monitoring_server_step_1']['conf']['show_bullets'] = 0;
    $return_tours['tours']['monitoring_server_step_1']['conf']['show_step_numbers'] = 0;
    $return_tours['tours']['monitoring_server_step_1']['conf']['next_help'] = 'monitoring_server_step_2';
    // ==================================================================
    // ==================================================================
    // Help tour about the monitoring with a ping (step 2)
    // ------------------------------------------------------------------
    $return_tours['tours']['monitoring_server_step_2'] = [];
    $return_tours['tours']['monitoring_server_step_2']['steps'] = [];
    $return_tours['tours']['monitoring_server_step_2']['steps'][] = [
        'element' => '#clippy',
        'intro'   => __('Now, please choose the agent you searched.'),
    ];
    $return_tours['tours']['monitoring_server_step_2']['steps'][] = [
        'element'  => '#agent_list',
        'position' => 'top',
        'intro'    => __('Choose the agent and click on the name.'),
    ];
    $return_tours['tours']['monitoring_server_step_2']['conf'] = [];
    $return_tours['tours']['monitoring_server_step_2']['conf']['show_bullets'] = 0;
    $return_tours['tours']['monitoring_server_step_2']['conf']['show_step_numbers'] = 0;
    $return_tours['tours']['monitoring_server_step_2']['conf']['next_help'] = 'monitoring_server_step_3';
    // ==================================================================
    return $return_tours;
}
