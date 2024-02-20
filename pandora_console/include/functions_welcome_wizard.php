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
 * @param string $ip_target        Ip and red mask.
 * @param string $snmp_communities SNMP Communities to use in recon task.
 * @param array  $wmi_credentials  WMI Credentials to use in recon task.
 * @param array  $rcmd_credentials RCMD Credentials to use in recon task.
 *
 * @return interger Module id.
 */
function create_net_scan($ip_target, $snmp_version, $snmp_communities, $wmi_credentials, $rcmd_credentials)
{
    global $config;
    include_once $config['homedir'].'/godmode/wizards/HostDevices.class.php';
    include_once $config['homedir'].'/include/functions_groups.php';

    $group_name = 'AutoDiscovery';
    $id_group = db_get_value('id_grupo', 'tgrupo', 'nombre', io_safe_input($group_name));
    if (!($id_group > 0)) {
        $id_group = groups_create_group(
            io_safe_input($group_name),
            [
                'icon'        => 'applications.png',
                'description' => '',
                'contact'     => '',
                'other'       => '',
            ]
        );

        if (!($id_group > 0)) {
            $id_group = 10;
        }
    }

    $auth_strings = [];

    $default_templates = [
        io_safe_input('Linux System'),
        io_safe_input('Windows System'),
        io_safe_input('Windows Hardware'),
        io_safe_input('Network Management'),
    ];

    $default_templates_ids = db_get_all_rows_sql(
        'SELECT id_np
                                              FROM tnetwork_profile
                                              WHERE name IN ('.implode(
            ',',
            array_map(
                function ($template) {
                                                                        return "'".$template."'";
                },
                $default_templates
            )
        ).')
                                              ORDER BY name'
    );

    $id_base = 'autoDiscovery-WMI-';
    $id = 0;
    foreach ($wmi_credentials as $wmi) {
        $id++;
        $identifier = $id_base.$id;
        while (db_get_value_sql(
            sprintf(
                'SELECT COUNT(*) AS count FROM tcredential_store WHERE identifier = "%s"',
                $identifier
            )
        ) > 0) {
            $id++;
            $identifier = $id_base.$id;
        }

        $storeKey = db_process_sql_insert(
            'tcredential_store',
            [
                'identifier' => $identifier,
                'id_group'   => $id_group,
                'product'    => 'WMI',
                'username'   => $wmi['credential']['user'],
                'password'   => $wmi['credential']['pass'],
                'extra_1'    => $wmi['credential']['namespace'],
            ]
        );

        if ($storeKey !== false) {
            $auth_strings[] = $identifier;
        }
    }

    $id_base = 'autoDiscovery-RCMD-';
    $id = 0;
    foreach ($rcmd_credentials as $rcmd) {
        $id++;
        $identifier = $id_base.$id;
        while (db_get_value_sql(
            sprintf(
                'SELECT COUNT(*) AS count FROM tcredential_store WHERE identifier = "%s"',
                $identifier
            )
        ) > 0) {
            $id++;
            $identifier = $id_base.$id;
        }

        $storeKey = db_process_sql_insert(
            'tcredential_store',
            [
                'identifier' => $identifier,
                'id_group'   => $id_group,
                'product'    => 'CUSTOM',
                'username'   => $rcmd['credential']['user'],
                'password'   => $rcmd['credential']['pass'],
            ]
        );

        if ($storeKey !== false) {
            $auth_strings[] = $identifier;
        }
    }

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
        'id_group'                => $id_group,
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
            'id_network_profile'        => array_column($default_templates_ids, 'id_np'),
            'review_results'            => 'on',
            'review_limited'            => '0',
            'snmp_enabled'              => 'on',
            'snmp_version'              => $snmp_version,
            'snmp_skip_non_enabled_ifs' => 'on',
            'community'                 => $snmp_communities,
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
            'wmi_enabled'               => 'on',
            'rcmd_enabled'              => 'on',
            'auth_strings'              => $auth_strings,
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


/**
 * Create new template unknown.
 *
 * @return boolean 1 correct create 0 bad create.
 */
function create_template_alert_unknown()
{
    $name = io_safe_input(__('Unknown condition'));
    $type = 'critical';
    $values = [
        'description'              => __('This is a generic alert template to fire on UNKNOWN condition'),
        'max_value'                => 0,
        'min_value'                => 0,
        'id_group'                 => 0,
        'priority'                 => 4,
        'wizard_level'             => 'nowizard',
        'time_threshold'           => '300',
        'min_alerts_reset_counter' => 1,
        'schedule'                 => '{"monday":[{"start":"00:00:00","end":"00:00:00"}],"tuesday":[{"start":"00:00:00","end":"00:00:00"}],"wednesday":[{"start":"00:00:00","end":"00:00:00"}],"thursday":[{"start":"00:00:00","end":"00:00:00"}],"friday":[{"start":"00:00:00","end":"00:00:00"}],"saturday":[{"start":"00:00:00","end":"00:00:00"}],"sunday":[{"start":"00:00:00","end":"00:00:00"}]}',
        'recovery_notify'          => true,
        'field2'                   => '[PANDORA] Alert for UNKNOWN status on _agent_ / _module_',
        'field2_recovery'          => '[PANDORA] Alert RECOVERED for UNKNOWN status on _agent_ / _module_',
        'field3'                   => '<div style="background-color: #eaf0f6; font-family: Arial, Helvetica, sans-serif; padding: 30px; margin: 0;"><table style="max-width: 560px; background-color: white; border-radius: 10px; padding: 10px 20px 40px;" cellspacing="0" cellpadding="0" align="center"><thead><tr><td style="padding: 0px 0px 5px;"><a href="https://pandorafms.com/en/" target="_blank" rel="noopener"><img src="https://pandorafms.com/wp-content/uploads/2022/03/System-email-Pandora-FMS.png" width="206px"></a></td><td style="padding: 0px 0px 5px;"><p style="text-align: right; color: #223549; font-weight: bold; line-height: 36px; padding: 0px; font-size: 12px;">Automatic alert system</p></td></tr><tr><td style="padding: 0px 0px 5px;" colspan="2"><hr style="border: 1px solid #f5f5f5; width: 100%; margin: 0px;"></td></tr></thead><tbody><tr><td colspan="2"><img style="display: block; margin-left: auto; margin-right: auto; width: 105px; margin-top: 20px; padding: 0px;" src="https://pandorafms.com/wp-content/uploads/2022/03/Warning-news.png" width="105px"></td></tr><tr><td colspan="2"><p style="font-size: 24px; text-align: center; color: #223549; padding: 0px 10%; line-height: 34px; margin: 20px 0px;">We have bad news for you, something is on <span style="text-transform: uppercase; font-weight: 800;">UNKNOWN</span>&nbsp;status!</p><div><!-- [if mso]><v:rect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="#" style="height:33px;v-text-anchor:middle;width:100px;" stroke="f" fillcolor="#D84A38"><w:anchorlock/><center><![endif]--><a style="background-color: #223549; border: none; color: white; padding: 15px 30px; text-align: center; text-decoration: none; display: block; font-size: 16px; margin-left: auto; margin-right: auto; border-radius: 100px; max-width: 50%; margin-top: 0px; font-weight: bold;" href="_homeurl_">Go to Pandora FMS Console</a><!-- [if mso]></center></v:rect><![endif]--></div></td></tr><tr><td colspan="2"><div style="background-color: #f6f6f6; border-radius: 10px; padding: 10px 20px; margin-top: 40px;"><p style="font-size: 18px; line-height: 30px; color: #223549;">Monitoring details</p><p style="font-size: 15px; color: #333333; font-weight: 800; line-height: 15px;">Data: <span style="font-weight: 400!important;">_data_ <em>(warning)</em></span></p><p style="font-size: 15px; color: #333333; font-weight: 800; line-height: 15px;">Agent: <span style="font-weight: 400!important;">_agent_ <em>_address_</em></span></p><p style="font-size: 15px; color: #333333; font-weight: 800; line-height: 15px;">Module: <span style="font-weight: 400!important;">_module_ <em>_moduledescription_</em></span></p><p style="font-size: 15px; color: #333333; font-weight: 800; line-height: 15px;">Timestamp: <span style="font-weight: 400!important;">_timestamp_</span></p></div></td></tr><tr><td style="padding: 20px 0px;" colspan="2"><p style="font-size: 18px; line-height: 30px; color: #223549;">This is a graph of latest 24hr data for this module</p><p style="font-weight: 400!important;">_modulegraph_24h_</p></td></tr></tbody></table><div style="text-align: center; margin-top: 10px;"><p style="font-size: 12px; text-decoration: none; font-weight: 400; color: #777;"><a style="font-size: 12px; text-decoration: none; font-weight: 400; color: #777;" href="https://pandorafms.com/en/contact/">Contact Us</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a style="font-size: 12px; text-decoration: none; font-weight: 400; color: #777;" href="https://pandorafms.com/community/forums/forum/english/">Support</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a style="font-size: 12px; text-decoration: none; font-weight: 400; color: #777;" href="https://pandorafms.com/manual/en/start">Docs</a></p></div></div>',
        'field3_recovery'          => '<div style="background-color:#EAF0F6; font-family: Arial, Helvetica, sans-serif; padding:30px; margin:0;"><table style="max-width:560px; background-color:white; border-radius:10px; padding:10px 20px 40px;" cellspacing="0" cellpadding="0" align="center"><thead><tr><td style="padding:0px 0px 5px;"><a href="https://pandorafms.com/en/" target="_blank"><img src="https://pandorafms.com/wp-content/uploads/2022/03/System-email-Pandora-FMS.png" width="206px"></a></td><td style="padding:0px 0px 5px;"><p style="text-align:right; color:#223549; font-weight:700; line-height:36px; padding:0px; font-size:12px;">Automatic alert system</p></td></tr><tr><td colspan="2" style="padding:0px 0px 5px;"><hr style="border: 1px solid #f5f5f5; width:100%; margin:0px;"></td></tr></thead><tbody><tr><td colspan="2"><img src="https://pandorafms.com/wp-content/uploads/2022/03/System-email-Good-news.png" style="display: block; margin-left: auto; margin-right: auto; width:105px; margin-top:20px; padding:0px;" width="105px"></td></tr><tr><td colspan="2"><p style="font-size:24px; text-align:center; color:#223549; padding:0px 10%; line-height:34px; margin:20px 0px;">We have good news for you, alert has been <span style="text-transform:uppercase; font-weight:800;">recovered</span></p><div><!--[if mso]><v:rect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="#" style="height:33px;v-text-anchor:middle;width:100px;" stroke="f" fillcolor="#D84A38"><w:anchorlock/><center><![endif]--><a style="background-color: #223549; border: none; color: white; padding: 15px 30px; text-align: center; text-decoration: none; display: block; font-size: 16px; margin-left: auto; margin-right: auto; border-radius:100px; max-width:50%; margin-top:0px; font-weight:700;" href="_homeurl_">Go to Pandora FMS Console</a><!--[if mso]></center></v:rect><![endif]--></div></td></tr><tr><td colspan="2"><div style="background-color:#F6F6F6; border-radius:10px; padding:10px 20px; margin-top:40px;"><p style="font-size:18px; line-height:30px; color:#223549;">Monitoring details</p><p style="font-size:15px; color:#333333; font-weight:800; line-height:15px;">Data: <span style="font-weight:400!important;">_data_ <em>(normal)</em></span></p><p style="font-size:15px; color:#333333; font-weight:800; line-height:15px;">Agent: <span style="font-weight:400!important;">_agent_ <em>_address_</em></span></p><p style="font-size:15px; color:#333333; font-weight:800; line-height:15px;">Module: <span style="font-weight:400!important;">_module_ <em>_moduledescription_</em></span></p><p style="font-size:15px; color:#333333; font-weight:800; line-height:15px;">Timestamp: <span style="font-weight:400!important;">_timestamp_</span></p></div></td></tr><tr><td style="padding:20px 0px;" colspan="2"><p style="font-size:18px; line-height:30px; color:#223549;">This is a graph of latest 24hr data for this module</p><p style="font-weight:400!important;">_modulegraph_24h_</p></td></tr></tbody></table><div style="text-align:center; margin-top:10px;"><p style="font-size:12px; text-decoration: none; font-weight:400; color:#777;"><a href="https://pandorafms.com/en/contact/" style="font-size:12px; text-decoration: none; font-weight:400; color:#777;">Contact Us</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="https://pandorafms.com/community/forums/forum/english/" style="font-size:12px; text-decoration: none; font-weight:400; color:#777;">Support</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="https://pandorafms.com/manual/en/start" style="font-size:12px; text-decoration: none; font-weight:400; color:#777;">Docs</a></p></div></div>',
    ];

    $result = alerts_create_alert_template($name, $type, $values);
    return $result;
}
