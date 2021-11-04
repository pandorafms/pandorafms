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
    // Help tour about the monitoring with a ping (step 3)
    // ------------------------------------------------------------------
    $return_tours['tours']['monitoring_server_step_3'] = [];
    $return_tours['tours']['monitoring_server_step_3']['steps'] = [];
    $return_tours['tours']['monitoring_server_step_3']['steps'][] = [
        'element' => '#clippy',
        'intro'   => __('Now you must go to Modules. Don\'t worry I\'ll lead you.'),
    ];
    $return_tours['tours']['monitoring_server_step_3']['steps'][] = [
        'element'  => "img[alt='Modules']",
        'position' => 'left',
        'intro'    => __('Click in this tab..'),
    ];
    $return_tours['tours']['monitoring_server_step_3']['conf'] = [];
    $return_tours['tours']['monitoring_server_step_3']['conf']['show_bullets'] = 0;
    $return_tours['tours']['monitoring_server_step_3']['conf']['show_step_numbers'] = 0;
    $return_tours['tours']['monitoring_server_step_3']['conf']['next_help'] = 'monitoring_server_step_4';
    // ==================================================================
    // ==================================================================
    // Help tour about the monitoring with a ping (step 4)
    // ------------------------------------------------------------------
    $return_tours['tours']['monitoring_server_step_4'] = [];
    $return_tours['tours']['monitoring_server_step_4']['steps'] = [];
    $return_tours['tours']['monitoring_server_step_4']['steps'][] = [
        'element' => '#clippy',
        'intro'   => __('Now you must create the module. Don\'t worry, i\'ll teach you.'),
    ];
    $return_tours['tours']['monitoring_server_step_4']['steps'][] = [
        'element' => '#moduletype',
        'intro'   => __('Choose the network server module.'),
    ];
    $return_tours['tours']['monitoring_server_step_4']['steps'][] = [
        'element' => "input[name='updbutton']",
        'intro'   => __('And click the button.'),
    ];
    $return_tours['tours']['monitoring_server_step_4']['conf'] = [];
    $return_tours['tours']['monitoring_server_step_4']['conf']['show_bullets'] = 0;
    $return_tours['tours']['monitoring_server_step_4']['conf']['show_step_numbers'] = 0;
    $return_tours['tours']['monitoring_server_step_4']['conf']['next_help'] = 'monitoring_server_step_5';
    // ==================================================================
    // ==================================================================
    // Help tour about the monitoring with a ping (step 5)
    // ------------------------------------------------------------------
    $return_tours['tours']['monitoring_server_step_5'] = [];
    $return_tours['tours']['monitoring_server_step_5']['steps'] = [];
    $return_tours['tours']['monitoring_server_step_5']['steps'][] = [
        'element' => '#clippy',
        'intro'   => __('Now you must create the module. Don\'t worry, i\'ll teach you .'),
    ];
    $return_tours['tours']['monitoring_server_step_5']['steps'][] = [
        'element' => '#clippy',
        'intro'   => __('Now we are going to fill the form.'),
    ];
    $return_tours['tours']['monitoring_server_step_5']['steps'][] = [
        'element' => '#network_component_group',
        'intro'   => __('Please choose Network Management.'),
    ];
    $return_tours['tours']['monitoring_server_step_5']['steps'][] = [
        'element' => '#network_component',
        'intro'   => __('Choose the component named "Host alive".'),
    ];
    $return_tours['tours']['monitoring_server_step_5']['steps'][] = [
        'element' => "input[name='name']",
        'intro'   => __('You can change the name if you want.'),
    ];
    $return_tours['tours']['monitoring_server_step_5']['steps'][] = [
        'element' => "input[name='ip_target']",
        'intro'   => __('Check if the IP showed is the IP of your machine.'),
    ];
    $return_tours['tours']['monitoring_server_step_5']['steps'][] = [
        'element'  => "input[name='crtbutton']",
        'intro'    => __('And only to finish it is clicking this button.'),
        'position' => 'left',
    ];
    $return_tours['tours']['monitoring_server_step_5']['conf'] = [];
    $return_tours['tours']['monitoring_server_step_5']['conf']['show_bullets'] = 0;
    $return_tours['tours']['monitoring_server_step_5']['conf']['show_step_numbers'] = 0;
    $return_tours['tours']['monitoring_server_step_5']['conf']['next_help'] = 'monitoring_server_step_6';
    // ==================================================================
    // ==================================================================
    // Help tour about the monitoring with a ping (step 6)
    // ------------------------------------------------------------------
    $return_tours['tours']['monitoring_server_step_6'] = [];
    $return_tours['tours']['monitoring_server_step_6']['steps'] = [];
    $return_tours['tours']['monitoring_server_step_6']['steps'][] = [
        'element' => '#clippy',
        'intro'   => __('Congrats! Your module has been created. <br /> and the status color is <b>blue.</b><br /> That color means that the module hasn\'t been executed for the first time. In the next seconds, if there is no problem, the status color will turn into <b>red</b> or <b>green</b>.'),
    ];
    $return_tours['tours']['monitoring_server_step_6']['conf'] = [];
    $return_tours['tours']['monitoring_server_step_6']['conf']['show_bullets'] = 0;
    $return_tours['tours']['monitoring_server_step_6']['conf']['show_step_numbers'] = 0;
    $return_tours['tours']['monitoring_server_step_6']['conf']['done_label'] = __('Done');
    // ==================================================================
    // ==================================================================
    // Help tour about the email alert module (step 7)
    // ------------------------------------------------------------------
    $return_tours['tours']['email_alert_module_step_7'] = [];
    $return_tours['tours']['email_alert_module_step_7']['steps'] = [];
    $return_tours['tours']['email_alert_module_step_7']['steps'][] = [
        'element'  => "img[alt='Alerts']",
        'position' => 'left',
        'intro'    => __('Click on alerts tab and then fill the form to add an alert.'),
    ];
    $return_tours['tours']['email_alert_module_step_7']['conf'] = [];
    $return_tours['tours']['email_alert_module_step_7']['conf']['show_bullets'] = 0;
    $return_tours['tours']['email_alert_module_step_7']['conf']['show_step_numbers'] = 0;
    $return_tours['tours']['email_alert_module_step_7']['conf']['next_help'] = 'email_alert_module_step_8';
    // ==================================================================
    // ==================================================================
    // Help tour about the email alert module (step 8)
    // ------------------------------------------------------------------
    $return_tours['tours']['email_alert_module_step_8'] = [];
    $return_tours['tours']['email_alert_module_step_8']['steps'] = [];
    $return_tours['tours']['email_alert_module_step_8']['steps'][] = [
        'element' => "select[name='id_agent_module']",
        'intro'   => __('Select the critical module.'),
    ];
    $return_tours['tours']['email_alert_module_step_8']['steps'][] = [
        'element' => "select[name='template']",
        'intro'   => __('In template select "Critical Condition".'),
    ];
    $return_tours['tours']['email_alert_module_step_8']['steps'][] = [
        'element' => "select[name='action_select']",
        'intro'   => __('Now, select the action created before.'),
    ];
    $return_tours['tours']['email_alert_module_step_8']['steps'][] = [
        'element'  => "input[name='add']",
        'position' => 'left',
        'intro'    => __('Click on Add Alert button to create the alert.'),
    ];
    $return_tours['tours']['email_alert_module_step_8']['conf'] = [];
    $return_tours['tours']['email_alert_module_step_8']['conf']['show_bullets'] = 0;
    $return_tours['tours']['email_alert_module_step_8']['conf']['show_step_numbers'] = 0;
    $return_tours['tours']['email_alert_module_step_8']['conf']['next_help'] = 'email_alert_module_step_9';
    // ==================================================================
    // ==================================================================
    // Help tour about the email alert module (step 9)
    // ------------------------------------------------------------------
    $return_tours['tours']['email_alert_module_step_9'] = [];
    $return_tours['tours']['email_alert_module_step_9']['steps'] = [];
    $return_tours['tours']['email_alert_module_step_9']['steps'][] = [
        'element'  => "img[alt='View']",
        'position' => 'left',
        'intro'    => __('To test the alert you\'ve just created go to the main view by clicking on the eye tab.'),
    ];
    $return_tours['tours']['email_alert_module_step_9']['conf'] = [];
    $return_tours['tours']['email_alert_module_step_9']['conf']['show_bullets'] = 0;
    $return_tours['tours']['email_alert_module_step_9']['conf']['show_step_numbers'] = 0;
    $return_tours['tours']['email_alert_module_step_9']['conf']['next_help'] = 'email_alert_module_step_10';
    // ==================================================================
    return $return_tours;
}
