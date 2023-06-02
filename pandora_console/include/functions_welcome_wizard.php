<?php
/**
 * Agents Functions.
 *
 * @category   Agents functions.
 * @package    Pandora FMS
 * @subpackage User interface.
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 PandoraFMS Soluciones Tecnologicas
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


/**
 * Create_module_latency_goliat and return module id.
 *
 * @param mixed $id_agent      Id agent.
 * @param mixed $module_name   Module name.
 * @param mixed $id_group      Id group.
 * @param mixed $url_search    Url to search.
 * @param mixed $string_search Text to search.
 *
 * @return interger Module id.
 */
function create_module_latency_goliat($id_agent, $module_name, $id_group, $url_search, $string_search='')
{
    if ($string_search !== '') {
        $str_search = 'check_string '.$string_search.'';
    }

    include_once 'include/functions_modules.php';

    $array_values = [
        'id_tipo_modulo'        => '30',
        'descripcion'           => '',
        'max'                   => '0',
        'min'                   => '0',
        'snmp_oid'              => '',
        'snmp_community'        => 'public',
        'id_module_group'       => $id_group,
        'module_interval'       => '300',
        'module_ff_interval'    => '0',
        'ip_target'             => '',
        'tcp_port'              => '0',
        'tcp_rcv'               => '',
        'tcp_send'              => '',
        'id_export'             => '0',
        'plugin_user'           => '',
        'plugin_pass'           => '0',
        'plugin_parameter'      => 'task_begin
get '.$url_search.'
resource 1
'.$str_search.'
task_end',
        'id_plugin'             => '0',
        'post_process'          => '0',
        'prediction_module'     => '0',
        'max_timeout'           => '0',
        'max_retries'           => '0',
        'disabled'              => '',
        'id_modulo'             => '7',
        'custom_id'             => '',
        'history_data'          => '1',
        'dynamic_interval'      => '0',
        'dynamic_max'           => '0',
        'dynamic_min'           => '0',
        'dynamic_two_tailed'    => '0',
        'parent_module_id'      => '0',
        'min_warning'           => '0',
        'max_warning'           => '0',
        'str_warning'           => '',
        'min_critical'          => '0',
        'max_critical'          => '0',
        'str_critical'          => '',
        'custom_string_1'       => '',
        'custom_string_2'       => '',
        'custom_string_3'       => '',
        'custom_integer_1'      => '0',
        'custom_integer_2'      => '0',
        'min_ff_event'          => '0',
        'min_ff_event_normal'   => '0',
        'min_ff_event_warning'  => '0',
        'min_ff_event_critical' => '0',
        'ff_type'               => '0',
        'each_ff'               => '0',
        'ff_timeout'            => '0',
        'unit'                  => '',
        'macros'                => '',
        'quiet'                 => '0',
        'cps'                   => '0',
        'critical_instructions' => '',
        'warning_instructions'  => '',
        'unknown_instructions'  => '',
        'critical_inverse'      => '0',
        'warning_inverse'       => '0',
        'percentage_critical'   => '0',
        'percentage_warning'    => '0',
        'cron_interval'         => '* * * * *',
        'id_category'           => '0',
        'disabled_types_event'  => '{\"going_unknown\":0}',
        'module_macros'         => 'W10=',
        'warning_time'          => '0',
    ];
    return modules_create_agent_module($id_agent, $module_name.'_latency', $array_values);
}


/**
 * Create_module_status_goliat and return module id.
 *
 * @param mixed $id_agent      Id agent.
 * @param mixed $module_name   Module name.
 * @param mixed $id_group      Id group.
 * @param mixed $url_search    Url to search.
 * @param mixed $string_search Text to search.
 *
 * @return interger Module id.
 */
function create_module_status_goliat($id_agent, $module_name, $id_group, $url_search, $string_search='')
{
    if ($string_search !== '') {
        $str_search = 'check_string '.$string_search.' ';
    }

    include_once 'include/functions_modules.php';

    $array_values = [
        'id_tipo_modulo'        => '31',
        'descripcion'           => '',
        'max'                   => '0',
        'min'                   => '0',
        'snmp_oid'              => '',
        'snmp_community'        => 'public',
        'id_module_group'       => $id_group,
        'module_interval'       => '300',
        'module_ff_interval'    => '0',
        'ip_target'             => '',
        'tcp_port'              => '0',
        'tcp_rcv'               => '',
        'tcp_send'              => '',
        'id_export'             => '0',
        'plugin_user'           => '',
        'plugin_pass'           => '0',
        'plugin_parameter'      => 'task_begin
get '.$url_search.'
resource 1
'.$str_search.'
task_end',
        'id_plugin'             => '0',
        'post_process'          => '0',
        'prediction_module'     => '0',
        'max_timeout'           => '0',
        'max_retries'           => '0',
        'disabled'              => '',
        'id_modulo'             => '7',
        'custom_id'             => '',
        'history_data'          => '1',
        'dynamic_interval'      => '0',
        'dynamic_max'           => '0',
        'dynamic_min'           => '0',
        'dynamic_two_tailed'    => '0',
        'parent_module_id'      => '0',
        'min_warning'           => '0',
        'max_warning'           => '0',
        'str_warning'           => '',
        'min_critical'          => '0',
        'max_critical'          => '0',
        'str_critical'          => '',
        'custom_string_1'       => '',
        'custom_string_2'       => '',
        'custom_string_3'       => '',
        'custom_integer_1'      => '0',
        'custom_integer_2'      => '0',
        'min_ff_event'          => '0',
        'min_ff_event_normal'   => '0',
        'min_ff_event_warning'  => '0',
        'min_ff_event_critical' => '0',
        'ff_type'               => '0',
        'each_ff'               => '0',
        'ff_timeout'            => '0',
        'unit'                  => '',
        'macros'                => '',
        'quiet'                 => '0',
        'cps'                   => '0',
        'critical_instructions' => '',
        'warning_instructions'  => '',
        'unknown_instructions'  => '',
        'critical_inverse'      => '0',
        'warning_inverse'       => '0',
        'percentage_critical'   => '0',
        'percentage_warning'    => '0',
        'cron_interval'         => '* * * * *',
        'id_category'           => '0',
        'disabled_types_event'  => '{\"going_unknown\":0}',
        'module_macros'         => 'W10=',
        'warning_time'          => '0',
    ];
    return modules_create_agent_module($id_agent, $module_name.'_status', $array_values);
}


/**
 * Create module basic network and return module id.
 *
 * @param mixed $id_agent  Id agent.
 * @param mixed $id_group  Id group.
 * @param mixed $ip_target Ip target.
 *
 * @return interger Module id.
 */
function create_module_basic_network($id_agent, $id_group, $ip_target)
{
    include_once 'include/functions_modules.php';

    $array_values = [
        'id_tipo_modulo'        => '6',
        'descripcion'           => 'Basic network check (ping)',
        'max'                   => '0',
        'min'                   => '0',
        'snmp_oid'              => '',
        'snmp_community'        => 'public',
        'id_module_group'       => $id_group,
        'module_interval'       => '300',
        'module_ff_interval'    => '0',
        'ip_target'             => $ip_target,
        'tcp_port'              => '0',
        'tcp_rcv'               => '',
        'tcp_send'              => '',
        'id_export'             => '0',
        'plugin_user'           => '',
        'plugin_pass'           => '0',
        'plugin_parameter'      => '',
        'id_plugin'             => '0',
        'post_process'          => '0',
        'prediction_module'     => '0',
        'max_timeout'           => '0',
        'max_retries'           => '0',
        'disabled'              => '',
        'id_modulo'             => '2',
        'custom_id'             => '',
        'history_data'          => '1',
        'dynamic_interval'      => '0',
        'dynamic_max'           => '0',
        'dynamic_min'           => '0',
        'dynamic_two_tailed'    => '0',
        'parent_module_id'      => '0',
        'min_warning'           => '0',
        'max_warning'           => '0',
        'str_warning'           => '',
        'min_critical'          => '0',
        'max_critical'          => '0',
        'str_critical'          => '',
        'custom_string_1'       => '',
        'custom_string_2'       => '',
        'custom_string_3'       => '',
        'custom_integer_1'      => '0',
        'custom_integer_2'      => '0',
        'min_ff_event'          => '0',
        'min_ff_event_normal'   => '0',
        'min_ff_event_warning'  => '0',
        'min_ff_event_critical' => '0',
        'ff_type'               => '0',
        'each_ff'               => '0',
        'ff_timeout'            => '0',
        'unit'                  => '',
        'macros'                => '',
        'quiet'                 => '0',
        'cps'                   => '0',
        'critical_instructions' => '',
        'warning_instructions'  => '',
        'unknown_instructions'  => '',
        'critical_inverse'      => '0',
        'warning_inverse'       => '0',
        'percentage_critical'   => '0',
        'percentage_warning'    => '0',
        'cron_interval'         => '* * * * *',
        'id_category'           => '0',
        'disabled_types_event'  => '{\"going_unknown\":0}',
        'module_macros'         => 'W10=',
        'warning_time'          => '0',
    ];
    return modules_create_agent_module($id_agent, 'Basic_Network_Check', $array_values);
}


/**
 * Create module latency network and return module id.
 *
 * @param mixed $id_agent  Id agent.
 * @param mixed $id_group  Id group.
 * @param mixed $ip_target Ip target.
 *
 * @return interger Module id.
 */
function create_module_latency_network($id_agent, $id_group, $ip_target)
{
    include_once 'include/functions_modules.php';

    $array_values = [
        'id_tipo_modulo'        => '7',
        'descripcion'           => 'Basic network connectivity check to measure network latency in miliseconds',
        'max'                   => '0',
        'min'                   => '0',
        'snmp_oid'              => '',
        'snmp_community'        => 'public',
        'id_module_group'       => $id_group,
        'module_interval'       => '300',
        'module_ff_interval'    => '0',
        'ip_target'             => $ip_target,
        'tcp_port'              => '0',
        'tcp_rcv'               => '',
        'tcp_send'              => '',
        'id_export'             => '0',
        'plugin_user'           => '',
        'plugin_pass'           => '0',
        'plugin_parameter'      => '',
        'id_plugin'             => '0',
        'post_process'          => '0',
        'prediction_module'     => '0',
        'max_timeout'           => '0',
        'max_retries'           => '0',
        'disabled'              => '',
        'id_modulo'             => '2',
        'custom_id'             => '',
        'history_data'          => '1',
        'dynamic_interval'      => '0',
        'dynamic_max'           => '0',
        'dynamic_min'           => '0',
        'dynamic_two_tailed'    => '1',
        'parent_module_id'      => '0',
        'min_warning'           => '0',
        'max_warning'           => '0',
        'str_warning'           => '',
        'min_critical'          => '0',
        'max_critical'          => '0',
        'str_critical'          => '',
        'custom_string_1'       => '',
        'custom_string_2'       => '',
        'custom_string_3'       => '',
        'custom_integer_1'      => '0',
        'custom_integer_2'      => '0',
        'min_ff_event'          => '0',
        'min_ff_event_normal'   => '0',
        'min_ff_event_warning'  => '0',
        'min_ff_event_critical' => '0',
        'ff_type'               => '0',
        'each_ff'               => '0',
        'ff_timeout'            => '0',
        'unit'                  => '',
        'macros'                => '',
        'quiet'                 => '0',
        'cps'                   => '0',
        'critical_instructions' => '',
        'warning_instructions'  => '',
        'unknown_instructions'  => '',
        'critical_inverse'      => '0',
        'warning_inverse'       => '0',
        'percentage_critical'   => '0',
        'percentage_warning'    => '0',
        'cron_interval'         => '* * * * *',
        'id_category'           => '0',
        'disabled_types_event'  => '{\"going_unknown\":0}',
        'module_macros'         => 'W10=',
        'warning_time'          => '0',
    ];
    return modules_create_agent_module($id_agent, 'Basic_Network_Latency', $array_values);
}


/**
 * Create module packet lost and return module id.
 *
 * @param mixed $id_agent  Id agent.
 * @param mixed $id_group  Id group.
 * @param mixed $ip_target Ip target.
 *
 * @return interger Module id.
 */
function create_module_packet_lost($id_agent, $id_group, $ip_target)
{
    include_once 'include/functions_modules.php';

    $array_values = [
        'id_tipo_modulo'        => '1',
        'descripcion'           => 'Basic network connectivity check to measure packet loss in %',
        'max'                   => '0',
        'min'                   => '0',
        'snmp_oid'              => '',
        'snmp_community'        => 'public',
        'id_module_group'       => $id_group,
        'module_interval'       => '300',
        'module_ff_interval'    => '0',
        'ip_target'             => '',
        'tcp_port'              => '0',
        'tcp_rcv'               => '',
        'tcp_send'              => '',
        'id_export'             => '0',
        'plugin_user'           => '',
        'plugin_pass'           => '0',
        'plugin_parameter'      => '',
        'id_plugin'             => '9',
        'post_process'          => '0',
        'prediction_module'     => '0',
        'max_timeout'           => '0',
        'max_retries'           => '0',
        'disabled'              => '',
        'id_modulo'             => '4',
        'custom_id'             => '',
        'history_data'          => '1',
        'dynamic_interval'      => '0',
        'dynamic_max'           => '0',
        'dynamic_min'           => '0',
        'dynamic_two_tailed'    => '1',
        'parent_module_id'      => '0',
        'min_warning'           => '0',
        'max_warning'           => '0',
        'str_warning'           => '',
        'min_critical'          => '0',
        'max_critical'          => '0',
        'str_critical'          => '',
        'custom_string_1'       => '',
        'custom_string_2'       => '',
        'custom_string_3'       => '',
        'custom_integer_1'      => '0',
        'custom_integer_2'      => '0',
        'min_ff_event'          => '0',
        'min_ff_event_normal'   => '0',
        'min_ff_event_warning'  => '0',
        'min_ff_event_critical' => '0',
        'ff_type'               => '0',
        'each_ff'               => '0',
        'ff_timeout'            => '0',
        'unit'                  => '',
        'macros'                => '{"1":{"macro":"_field1_","desc":"Test time","help":"","value":"8","hide":""},"2":{"macro":"_field2_","desc":"Target IP","help":"","value":"'.$ip_target.'","hide":""}}',
        'quiet'                 => '0',
        'cps'                   => '0',
        'critical_instructions' => '',
        'warning_instructions'  => '',
        'unknown_instructions'  => '',
        'critical_inverse'      => '0',
        'warning_inverse'       => '0',
        'percentage_critical'   => '0',
        'percentage_warning'    => '0',
        'cron_interval'         => '* * * * *',
        'id_category'           => '0',
        'disabled_types_event'  => '{\"going_unknown\":0}',
        'module_macros'         => 'W10=',
        'warning_time'          => '0',
    ];
    return modules_create_agent_module($id_agent, 'Basic_Network_Packetloss', $array_values);
}


/**
 * Create module packet lost and return module id.
 *
 * @param string $ip_target Ip and red mask.
 *
 * @return interger Module id.
 */
function create_net_scan($ip_target)
{
    global $config;
    include_once 'HostDevices.class.php';
    $HostDevices = new HostDevices(1);
    $id_recon_server = db_get_row_filter('tserver', ['server_type' => SERVER_TYPE_DISCOVERY], 'id_server')['id_server'];

    $_POST = [
        'page'                    => '1',
        'interval_manual_defined' => '1',
        'interval_select'         => '-1',
        'interval_text'           => '0',
        'interval'                => '0',
        'interval_units'          => '1',
        'taskname'                => __('Basic network'),
        'id_recon_server'         => $id_recon_server,
        'network'                 => $ip_target,
        'id_group'                => '8',
        'comment'                 => __('Created on welcome'),
    ];
    $task_created = $HostDevices->parseNetScan();
    if ($task_created === true) {
        $HostDevicesFinal = new HostDevices(2);
        $_POST = [
            'task'                      => $HostDevices->task['id_rt'],
            'page'                      => '2',
            'recon_ports'               => '',
            'auto_monitor'              => 'on',
            'id_network_profile'        => ['0' => '2'],
            'review_results'            => 'on',
            'review_limited'            => '0',
            'snmp_enabled'              => 'on',
            'snmp_version'              => '1',
            'snmp_skip_non_enabled_ifs' => 'on',
            'community'                 => '',
            'snmp_context'              => '',
            'snmp_auth_user'            => '',
            'snmp_security_level'       => 'authNoPriv',
            'snmp_auth_method'          => 'MD5',
            'snmp_auth_pass'            => '',
            'snmp_privacy_method'       => 'AES',
            'snmp_privacy_pass'         => '',
            'os_detect'                 => 'on',
            'resolve_names'             => 'on',
            'parent_detection'          => 'on',
            'parent_recursion'          => 'on',
            'vlan_enabled'              => 'on',
        ];

        $task_final_created = $HostDevicesFinal->parseNetScan();
        if ($task_final_created === true) {
            $net_scan_id = $HostDevices->task['id_rt'];
            unset($HostDevices, $HostDevicesFinal);
            return $net_scan_id;
        }
    } else {
        return 0;
    }
}
