<?php
/**
 * Extension to schedule tasks on Pandora FMS Console
 *
 * @category   Wizard
 * @package    Pandora FMS
 * @subpackage Host&Devices
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 PandoraFMS S.L.
 * Please see http://pandorafms.org for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */

// Begin.
require_once '../functions.php';
require_once '../functions_welcome_wizard.php';
global $config;

$check_web = get_parameter('check_web', 0);
$check_connectivity = get_parameter('check_connectivity', 0);
$create_net_scan = get_parameter('create_net_scan', 0);
$create_mail_alert = get_parameter('create_mail_alert', 0);
$create_unknown_template_alert = get_parameter('create_unknown_template_alert', 0);

// Begin.
global $config;

if ($check_web) {
    include_once '../functions_api.php';
    include_once '../functions_servers.php';

    $status_webserver = db_get_row_filter('tserver', ['server_type' => SERVER_TYPE_WEB], 'status')['status'];
    if ($status_webserver === '1') {
        $name = array_keys(servers_get_names())[0];
        $id_group = get_parameter('id_group', 4);

        $array_other['data'] = [
            'Web monitoring',
            '',
            2,
            $id_group,
            0,
            30,
            30,
            9,
            $name,
            0,
            0,
            0,
            __('Agent Web monitoring created on welcome'),
        ];

        $id_agent = api_set_new_agent(0, '', $array_other, '', true, true);
        if (is_integer($id_agent)) {
            $module_name = get_parameter('module_name', 'Web_monitoring_module');
            $text_to_search = get_parameter('text_to_search', '');
            $url_goliat = get_parameter('url_goliat', 'https://pandorafms.com/en/');
            $module_latency = create_module_latency_goliat($id_agent, $module_name, $id_group, $url_goliat, $text_to_search);
            $module_status = create_module_status_goliat($id_agent, $module_name, $id_group, $url_goliat, $text_to_search);
            if ($module_latency > 0 && $module_status > 0) {
                ui_print_success_message(__('Your check has been created, <a href='.ui_get_full_url('index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=module&id_agente='.$id_agent).'>click here to view the data</a>. Please note that it may take a few seconds to see data if your server is busy'));
            }
        } else {
            ui_print_error_message(__($id_agent));
        }
    } else {
        ui_print_error_message(__('Web server is not enabled.'));
    }
}

if ($check_connectivity) {
    include_once '../functions_api.php';
    include_once '../functions_servers.php';

    $status_newtwork = db_get_row_filter('tserver', ['server_type' => SERVER_TYPE_NETWORK], 'status')['status'];
    $status_pluggin = db_get_row_filter('tserver', ['server_type' => SERVER_TYPE_PLUGIN], 'status')['status'];
    if ($status_newtwork === '1' && $status_pluggin === '1') {
        $name = array_keys(servers_get_names())[0];
        $id_group = get_parameter('id_group', 4);
        $agent_name = get_parameter('agent_name', __('Agent check connectivity'));

        $array_other['data'] = [
            $agent_name,
            '',
            2,
            $id_group,
            0,
            30,
            30,
            9,
            $name,
            0,
            0,
            0,
            __('Basic connectivity'),
        ];

        $id_agent = api_set_new_agent(0, '', $array_other, '', true, true);
        if (is_integer($id_agent)) {
            $ip_target = get_parameter('ip_target', '127.0.0.1');
            $basic_network = create_module_basic_network($id_agent, $id_group, $ip_target);
            $latency_network = create_module_latency_network($id_agent, $id_group, $ip_target);
            $packet_lost = create_module_packet_lost($id_agent, $id_group, $ip_target);
            if ($basic_network > 0 && $latency_network > 0 && $packet_lost > 0) {
                ui_print_success_message(__('Your check has been created, <a href='.ui_get_full_url('index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=module&id_agente='.$id_agent).'>click here to view the data</a>. Please note that it may take a few seconds to see data if your server is busy'));
            }
        } else {
            ui_print_error_message(__($id_agent));
        }
    } else {
        ui_print_error_message(__('Web server is not enabled.'));
    }
}

if ($create_net_scan) {
    $ip_target = get_parameter('ip_target', '192.168.10.0/24');
    $snmp_version = get_parameter('snmp_version', '1');
    $snmp_communities = get_parameter('snmp_communities', 'public');
    $wmi_credentials = get_parameter('wmi_credentials', []);
    $rcmd_credentials = get_parameter('rcmd_credentials', []);

    $id_net_scan = create_net_scan($ip_target, $snmp_version, $snmp_communities, $wmi_credentials, $rcmd_credentials);
    if ($id_net_scan > 0) {
        $id_recon_server = db_get_row_filter('tserver', ['server_type' => SERVER_TYPE_DISCOVERY], 'id_server')['id_server'];
        ui_print_success_message(__('Basic net created and scan in progress. <a href='.ui_get_full_url('index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=tasklist&server_id='.$id_recon_server.'&force='.$id_net_scan).'>Click here to view the data</a>. Please note that it may take a few seconds to see data if your server is busy'));
    } else {
        ui_print_error_message(__('Basic net already exists. <a href='.ui_get_full_url('index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=tasklist').'>Click here to view the data</a>'));
    }
}

if ($create_mail_alert) {
    include_once '../functions_alerts.php';
    $id_action = db_get_row_filter('talert_actions', ['name' => 'Email to '.$config['id_user']], 'id')['id'];
    if (!$id_action) {
        $al_action = alerts_get_alert_action($id);
        $id_action = alerts_clone_alert_action(1, $al_action['id_group'], 'Email to '.$config['id_user']);
    }

    $id_alert_template = get_parameter('id_condition', 0);
    $id_agent_module = get_parameter('id_agent_module', 0);

    $exist = db_get_value_sql(
        sprintf(
            'SELECT COUNT(id)
            FROM talert_template_modules
            WHERE id_agent_module = %d
                AND id_alert_template = %d
                AND id_policy_alerts = 0
            ',
            $id_agent_module,
            $id_alert_template
        )
    );

    if ($exist > 0) {
        ui_print_error_message(__('Alert already exists. <a href='.ui_get_full_url('index.php?sec=galertas&sec2=godmode/alerts/alert_list&tab=list&pure=0').'>Click here to view the data</a>'));
    } else {
        $id = alerts_create_alert_agent_module($id_agent_module, $id_alert_template);
        if ($id !== false) {
            $values = [];
            $values['fires_min'] = (int) get_parameter('fires_min');
            $values['fires_max'] = (int) get_parameter('fires_max');
            $values['module_action_threshold'] = (int) 300;

            $alert_created = alerts_add_alert_agent_module_action($id, $id_action, $values);
        }
    }

    if ($alert_created === true) {
        ui_print_success_message(__('Congratulations, you have already created a simple alert. <a href='.ui_get_full_url('index.php?sec=galertas&sec2=godmode/alerts/alert_list&tab=list&pure=0').'>You can see it.</a> Pandora FMS alerts are very flexible, you can do many more things with them, we recommend you to read the <a href="https://pandorafms.com/manual/!current/en/documentation/pandorafms/management_and_operation/01_alerts">documentation</a> for more information. You can create advanced alerts from <a href='.ui_get_full_url('index.php?sec=galertas&sec2=godmode/alerts/alert_actions').'>here</a>.'));
    }
}

if ($create_unknown_template_alert) {
    if (is_array(alerts_get_alert_templates(['name' => io_safe_input('Unknown condition')]))) {
        echo 1;
    } else {
        echo create_template_alert_unknown();
    }
}
