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
    // Help tour about the email alert module (step 5)
    // ------------------------------------------------------------------
    $return_tours['tours']['email_alert_module_step_5'] = [];
    $return_tours['tours']['email_alert_module_step_5']['steps'] = [];
    $return_tours['tours']['email_alert_module_step_5']['steps'][] = [
        'element' => 'select[name="status"]',
        'intro'   => __('Now, you have to go to the monitors list and look for a "critical" module to apply the alert.'),
    ];
    $return_tours['tours']['email_alert_module_step_5']['steps'][] = [
        'element' => 'input[name="ag_freestring"]',
        'intro'   => __('If you know the name of the agent or the name of the module in critical status, type it in this field to make the module list shorter. You can write the entire name or just a part of it.'),
    ];
    $return_tours['tours']['email_alert_module_step_5']['steps'][] = [
        'element'  => 'input[name="uptbutton"]',
        'position' => 'left',
        'intro'    => __('Click on Show button to get the modules list filtered.'),
    ];
    $return_tours['tours']['email_alert_module_step_5']['conf'] = [];
    $return_tours['tours']['email_alert_module_step_5']['conf']['show_bullets'] = 0;
    $return_tours['tours']['email_alert_module_step_5']['conf']['show_step_numbers'] = 0;
    $return_tours['tours']['email_alert_module_step_5']['conf']['next_help'] = 'email_alert_module_step_6';
    // ==================================================================
    // ==================================================================
    // Help tour about the email alert module (step 6)
    // ------------------------------------------------------------------
    $return_tours['tours']['email_alert_module_step_6'] = [];
    $return_tours['tours']['email_alert_module_step_6']['steps'] = [];
    $return_tours['tours']['email_alert_module_step_6']['steps'][] = [
        'element'  => '#table3',
        'position' => 'top',
        'intro'    => __('Now, to edit the module, click on the wrench that appears in the type column.'),
    ];
    $return_tours['tours']['email_alert_module_step_6']['conf'] = [];
    $return_tours['tours']['email_alert_module_step_6']['conf']['show_bullets'] = 0;
    $return_tours['tours']['email_alert_module_step_6']['conf']['show_step_numbers'] = 0;
    $return_tours['tours']['email_alert_module_step_6']['conf']['next_help'] = 'email_alert_module_step_7';
    // ==================================================================
    return $return_tours;
}
