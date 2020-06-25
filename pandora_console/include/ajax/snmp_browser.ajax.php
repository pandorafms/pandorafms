<?php

// Pandora FMS- http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2017 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
require_once $config['homedir'].'/include/functions_config.php';
require_once $config['homedir'].'/include/functions_snmp_browser.php';
require_once $config['homedir'].'/include/functions_snmp.php';
require_once $config['homedir'].'/include/functions_network_components.php';


global $config;

if (is_ajax()) {
    ob_clean();

    $method = (string) get_parameter('method', '');
    $action = (string) get_parameter('action', '');
    $target_ip = (string) get_parameter('target_ip', '');
    $community = (string) get_parameter('community', '');
    $snmp_version = (string) get_parameter('snmp_browser_version', '');
    $snmp3_auth_user = io_safe_output(get_parameter('snmp3_browser_auth_user'));
    $snmp3_security_level = get_parameter('snmp3_browser_security_level');
    $snmp3_auth_method = get_parameter('snmp3_browser_auth_method');
    $snmp3_auth_pass = io_safe_output(get_parameter('snmp3_browser_auth_pass'));
    $snmp3_privacy_method = get_parameter('snmp3_browser_privacy_method');
    $snmp3_privacy_pass = io_safe_output(get_parameter('snmp3_browser_privacy_pass'));
    $module_target = get_parameter('module_target', '');
    $targets_oids = get_parameter('oids', '');
    $return_id = get_parameter('return_id', false);
    $custom_action = get_parameter('custom_action', '');

    if (!is_array($targets_oids)) {
        $targets_oids = explode(',', $targets_oids);
    }

    if ($custom_action != '') {
        $custom_action = urldecode(base64_decode($custom_action));
    }

    // SNMP browser.
    if ($action == 'snmptree') {
        $starting_oid = (string) get_parameter('starting_oid', '.');


            $snmp_tree = snmp_browser_get_tree(
                $target_ip,
                $community,
                $starting_oid,
                $snmp_version,
                $snmp3_auth_user,
                $snmp3_security_level,
                $snmp3_auth_method,
                $snmp3_auth_pass,
                $snmp3_privacy_method,
                $snmp3_privacy_pass,
                $server_to_exec
            );
        if (! is_array($snmp_tree)) {
            echo $snmp_tree;
        } else {
            snmp_browser_print_tree(
                $snmp_tree,
                // Id.
                0,
                // Depth.
                0,
                // Last.
                0,
                // Last_array.
                [],
                // Sufix.
                false,
                // Checked.
                [],
                // Return.
                false,
                // Descriptive_ids.
                false,
                // Previous_id.
                ''
            );

            // Div for error/succes dialog.
            $output .= '<div id="snmp_result_msg" style="display:none"></div>';

            // Dialog error.
            $output .= '<div id="dialog_error" style="display:none" title="'.__('SNMP modules').'">';
            $output .= '<div>';
            $output .= "<div style='width:25%; float:left'><img style='padding-left:20px; padding-top:20px;' src='images/icono_error_mr.png'></div>";
            $output .= "<div style='width:75%; float:left;'><h3><strong style='font-family:Verdana; font-size:13pt;'>ERROR</strong></h3>";
            $output .= "<p style='font-family:Verdana; font-size:12pt;margin-bottom: 0px'>".__('Error creating the following modules:').'</p>';
            $output .= "<p id='error_text' style='font-family:Verdana; font-size:12pt;'></p>";
            $output .= '</div>';
            $output .= '</div>';
            $output .= '</div>';

            // Dialog success.
            $output .= '<div id="dialog_success" style="display: none" title="'.__('SNMP modules').'">';
            $output .= '<div>';
            $output .= "<div style='width:25%; float:left'><img style='padding-left:20px; padding-top:20px;' src='images/icono_exito_mr.png'></div>";
            $output .= "<div style='width:75%; float:left;'><h3><strong style='font-family:Verdana; font-size:13pt;'>SUCCESS</strong></h3>";
            $output .= "<p style='font-family:Verdana; font-size:12pt;'>".__('Modules successfully created').'</p>';
            $output .= '</div>';
            $output .= '</div>';
            $output .= '</div>';

            // Dialog no agent selected.
            $output .= '<div id="dialog_no_agents_selected" style="display:none" title="'.__('SNMP modules').'">';
            $output .= '<div>';
            $output .= "<div style='width:25%; float:left'><img style='padding-left:20px; padding-top:20px;' src='images/icono_error_mr.png'></div>";
            $output .= "<div style='width:75%; float:left;'><h3><strong style='font-family:Verdana; font-size:13pt;'>ERROR</strong></h3>";
            $output .= "<p style='font-family:Verdana; font-size:12pt;margin-bottom: 0px'>".__('Module must be applied to an agent or a policy').'</p>';
            $output .= "<p id='error_text' style='font-family:Verdana; font-size:12pt;'></p>";
            $output .= '</div>';
            $output .= '</div>';
            $output .= '</div>';

            echo $output;
        }


        return;
    }

    if ($action == 'snmpget') {
        // SNMP get.
        $target_oid = htmlspecialchars_decode(get_parameter('oid', ''));
        $custom_action = get_parameter('custom_action', '');
        if ($custom_action != '') {
            $custom_action = urldecode(base64_decode($custom_action));
        }

        $oid = snmp_browser_get_oid(
            $target_ip,
            $community,
            $target_oid,
            $snmp_version,
            $snmp3_auth_user,
            $snmp3_security_level,
            $snmp3_auth_method,
            $snmp3_auth_pass,
            $snmp3_privacy_method,
            $snmp3_privacy_pass,
            $server_to_exec
        );

        snmp_browser_print_oid(
            $oid,
            $custom_action,
            false,
            $community,
            $snmp_version
        );
        return;
    }

    if ($method == 'snmp_browser_create_modules') {
        // Get target ids from form.
        $id_items = get_parameter('id_item2', null);
        if (!is_null($id_items)) {
            $id_target = explode(',', $id_items[0]);
        }

        if (empty($id_items[0])) {
            echo json_encode([0 => -1]);
            exit;
        }

        $snmp_extradata = get_parameter('snmp_extradata', '');

        if (!is_array($snmp_extradata)) {
            // Decode SNMP values.
            $snmp_extradata = json_decode(io_safe_output($snmp_extradata), true);
        }


        foreach ($snmp_extradata as $snmp_conf) {
            $snmp_conf_values[$snmp_conf['name']] = $snmp_conf['value'];
        }

        $fail_modules = snmp_browser_create_modules_snmp($module_target, $snmp_conf_values, $id_target);

        // Return fail modules for error/success message.
        echo json_encode($fail_modules);
        exit;
    }

    if ($method == 'snmp_browser_print_create_module_massive') {
        // Get SNMP conf vaues from modal onshow extradata.
        $snmp_extradata = get_parameter('extradata', '');

        $return = snmp_browser_print_create_module_massive($module_target, $snmp_extradata, true);
        echo $return;
        exit;
    }

    if ($method == 'snmp_browser_print_create_policy') {
        $return = snmp_browser_print_create_policy();
        echo $return;
        exit;
    }

    if ($method == 'snmp_browser_create_policy') {
        enterprise_include_once('include/functions_policies.php');

        $policy_name = get_parameter('name', '');
        $policy_id_group = get_parameter('id_group', 0);
        $policy_description = get_parameter('description', '');
        $values = [
            'id_group'    => $policy_id_group,
            'description' => $policy_description,

        ];

        // Check if policy exist.
        $policy_exists = policies_get_id($policy_name);
        if ($policy_exists != false) {
             $id_policy = 0;
        } else {
            $id_policy = (boolean) policies_create_policy($policy_name, $values);
        }


        $return = [
            'error' => (int) $id_policy,
            'title' => [
                __('Failed'),
                __('Success'),
            ],
            'text'  => [
                ui_print_error_message(__('Failed to create policy'), '', true),
                ui_print_success_message(__('Policy created succesfully'), '', true),
            ],
        ];

        echo json_encode($return);
        exit;
    }
}
