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
    // Help tour about the email alert module (step 3)
    // ------------------------------------------------------------------
    $return_tours['tours']['email_alert_module_step_3'] = [];
    $return_tours['tours']['email_alert_module_step_3']['steps'] = [];
    $return_tours['tours']['email_alert_module_step_3']['steps'][] = [
        'element' => 'input[name="name"]',
        'intro'   => __('Fill the name of your action.'),
    ];
    $return_tours['tours']['email_alert_module_step_3']['steps'][] = [
        'element' => 'select[name="group"]',
        'intro'   => __('Select the group in the drop-down list and filter for ACL (the user in this group can use your action to create an alert).'),
    ];
    $return_tours['tours']['email_alert_module_step_3']['steps'][] = [
        'element' => 'select[name="id_command"]',
        'intro'   => __('In the command field select "email".'),
    ];
    $return_tours['tours']['email_alert_module_step_3']['steps'][] = [
        'element' => 'input[name="action_threshold"]',
        'intro'   => __('In the threshold field enter the seconds. The help icon show more information.').'<br />'.ui_print_help_icon('action_threshold', true, '', 'images/help.png'),
    ];
    $return_tours['tours']['email_alert_module_step_3']['steps'][] = [
        'element'  => '#table_macros',
        'position' => 'bottom',
        'intro'    => __('In the first field enter the email address/addresses where you want to receive the email alerts separated with comas ( , ) or white spaces.'),
    ];
    $return_tours['tours']['email_alert_module_step_3']['steps'][] = [
        'element'  => '#table_macros',
        'position' => 'bottom',
        'intro'    => __('In the "Subject"  field  you can use the macros _agent_ or _module_ for each name.'),
    ];
    $return_tours['tours']['email_alert_module_step_3']['steps'][] = [
        'element'  => '#table_macros',
        'position' => 'bottom',
        'intro'    => __('In the text field, you can also use macros. Get more information about the macros by clicking on the help icon.').'<br />'.ui_print_help_icon('alert_config', true, '', 'images/help.png'),
    ];
    $return_tours['tours']['email_alert_module_step_3']['steps'][] = [
        'element'  => 'input[name="create"]',
        'position' => 'left',
        'intro'    => __('Click on Create button to create the action.'),
    ];
    $return_tours['tours']['email_alert_module_step_3']['conf'] = [];
    $return_tours['tours']['email_alert_module_step_3']['conf']['show_bullets'] = 0;
    $return_tours['tours']['email_alert_module_step_3']['conf']['show_step_numbers'] = 0;
    $return_tours['tours']['email_alert_module_step_3']['conf']['next_help'] = 'email_alert_module_step_4';
    // ==================================================================
    return $return_tours;
}
