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
    // Help tour about the email alert module (step 10)
    // ------------------------------------------------------------------
    $return_tours['tours']['email_alert_module_step_10'] = [];
    $return_tours['tours']['email_alert_module_step_10']['steps'] = [];
    $return_tours['tours']['email_alert_module_step_10']['steps'][] = [
        'element'  => '#table8',
        'position' => 'top',
        'intro'    => __('The last step is to check the alert created. Click on the round icon to force the action execution and after a few minutes you will receive the alert in your email.').'<br />'.__('And restart your pandora server to read again general configuration tokens.'),
    ];
    $return_tours['tours']['email_alert_module_step_10']['conf'] = [];
    $return_tours['tours']['email_alert_module_step_10']['conf']['show_bullets'] = 0;
    $return_tours['tours']['email_alert_module_step_10']['conf']['show_step_numbers'] = 0;
    $return_tours['tours']['email_alert_module_step_10']['conf']['done_label'] = __('Done');
    // ==================================================================
    return $return_tours;
}
