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
    // Help tour about the email alert module (step 2)
    // ------------------------------------------------------------------
    $return_tours['tours']['email_alert_module_step_2'] = [];
    $return_tours['tours']['email_alert_module_step_2']['steps'] = [];
    $return_tours['tours']['email_alert_module_step_2']['steps'][] = [
        'element'  => 'input[name="create"]',
        'position' => 'left',
        'intro'    => __('Let me show you how to create an email action: Click on Create button and fill the form showed in the following screen.'),
    ];
    $return_tours['tours']['email_alert_module_step_2']['conf'] = [];
    $return_tours['tours']['email_alert_module_step_2']['conf']['show_bullets'] = 0;
    $return_tours['tours']['email_alert_module_step_2']['conf']['show_step_numbers'] = 0;
    $return_tours['tours']['email_alert_module_step_2']['conf']['next_help'] = 'email_alert_module_step_3';
    // ==================================================================
    // ==================================================================
    // Help tour about the email alert module (step 4)
    // ------------------------------------------------------------------
    $return_tours['tours']['email_alert_module_step_4'] = [];
    $return_tours['tours']['email_alert_module_step_4']['steps'] = [];
    $return_tours['tours']['email_alert_module_step_4']['steps'][] = [
        'element' => '#clippy',
        'intro'   => __('Now, you have to go to the monitors list and look for a critical module to apply the alert.'),
    ];
    $return_tours['tours']['email_alert_module_step_4']['steps'][] = [
        'element'  => '#icon_oper-agents',
        'position' => 'right',
        'intro'    => __('Click on the arrow to drop down the Monitoring submenu and select Monitor Detail.'),
    ];
    $return_tours['tours']['email_alert_module_step_4']['conf'] = [];
    $return_tours['tours']['email_alert_module_step_4']['conf']['show_bullets'] = 0;
    $return_tours['tours']['email_alert_module_step_4']['conf']['show_step_numbers'] = 0;
    $return_tours['tours']['email_alert_module_step_4']['conf']['next_help'] = 'email_alert_module_step_5';
    // ==================================================================
    return $return_tours;
}
