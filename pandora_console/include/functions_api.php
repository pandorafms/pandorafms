<?php

/**
 * Functions for API.
 *
 * @category   Functions.
 * @package    Pandora FMS
 * @subpackage API.
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
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
global $config;

// Set character encoding to UTF-8 - fixes a lot of multibyte character headaches
require_once 'functions_agents.php';
require_once 'functions_modules.php';
require_once $config['homedir'].'/include/functions_profile.php';
require_once $config['homedir'].'/include/functions.php';
require_once $config['homedir'].'/include/functions_ui.php';
require_once $config['homedir'].'/include/functions_graph.php';
require_once $config['homedir'].'/include/functions_events.php';
require_once $config['homedir'].'/include/functions_groups.php';
require_once $config['homedir'].'/include/functions_network_components.php';
require_once $config['homedir'].'/include/functions_netflow.php';
require_once $config['homedir'].'/include/functions_servers.php';
require_once $config['homedir'].'/include/functions_planned_downtimes.php';
require_once $config['homedir'].'/include/functions_db.php';
require_once $config['homedir'].'/include/functions_event_responses.php';
require_once $config['homedir'].'/include/functions_tactical.php';
require_once $config['homedir'].'/include/functions_reporting.php';
require_once $config['homedir'].'/include/functions_reporting_xml.php';
require_once $config['homedir'].'/include/functions_reports.php';
enterprise_include_once('include/functions_local_components.php');
enterprise_include_once('include/functions_events.php');
enterprise_include_once('include/functions_agents.php');
enterprise_include_once('include/functions_modules.php');
enterprise_include_once('include/functions_clusters.php');
enterprise_include_once('include/functions_alerts.php');
enterprise_include_once('include/functions_reporting_pdf.php');
enterprise_include_once('include/functions_reporting_csv.php');
enterprise_include_once('include/functions_cron.php');

// Clases.
use PandoraFMS\Agent;
use PandoraFMS\Module;
use PandoraFMS\Cluster;
use PandoraFMS\Enterprise\Metaconsole\Node;
use PandoraFMS\Event;
use PandoraFMS\SpecialDay;


/**
 * Parse the "other" parameter.
 *
 * @param  string  $other
 * @param  mixed   $otherType
 * @param  boolean $rawDecode Decode string in which the sequences with percent (%) signs followed by two hex digits have been replaced with literal characters.
 * @return mixed
 */
function parseOtherParameter($other, $otherType, $rawDecode)
{
    switch ($otherType) {
        case 'url_encode':
            $returnVar = [
                'type' => 'string',
                'data' => urldecode($other),
            ];
        break;

        default:
            if (strpos($otherType, 'url_encode_separator_') !== false) {
                $separator = str_replace('url_encode_separator_', '', $otherType);
                $returnVar = [
                    'type' => 'array',
                    'data' => explode($separator, $other),
                ];
                foreach ($returnVar['data'] as $index => $element) {
                    $returnVar['data'][$index] = $rawDecode ? rawurldecode($element) : urldecode($element);
                }
            } else {
                $returnVar = [
                    'type' => 'string',
                    'data' => $rawDecode ? rawurldecode($other) : urldecode($other),
                ];
            }
        break;
    }

    return $returnVar;
}


/**
 *
 * @param  $typeError
 * @param  $returnType
 * @return unknown_type
 */
function returnError($typeError, $returnType='string')
{
    switch ($typeError) {
        case 'no_set_no_get_no_help':
            returnData(
                $returnType,
                [
                    'type' => 'string',
                    'data' => __('Not `set`, `get` or `help` operation selected.'),
                ]
            );
        break;

        case 'no_exist_operation':
            returnData(
                $returnType,
                [
                    'type' => 'string',
                    'data' => __('This operation does not exist.'),
                ]
            );
        break;

        case 'id_not_found':
            returnData(
                $returnType,
                [
                    'type' => 'string',
                    'data' => __('The Id does not exist in database.'),
                ]
            );
        break;

        case 'not_allowed_operation_cluster':
            returnData(
                $returnType,
                [
                    'type' => 'string',
                    'data' => __('This operation can not be used in cluster elements.'),
                ]
            );
        break;

        case 'forbidden':
            returnData(
                $returnType,
                [
                    'type' => 'string',
                    'data' => __('The user has not enough permissions for perform this action.'),
                ]
            );
        break;

        case 'no_data_to_show':
            returnData(
                $returnType,
                [
                    'type' => 'string',
                    'data' => __('No data to show.'),
                ]
            );
        break;

        case 'centralized':
            returnData(
                $returnType,
                [
                    'type' => 'string',
                    'data' => __('This console is not the environment administrator. Please, manage this feature from centralized manager console (Metaconsole).'),
                ]
            );
        break;

        case 'auth_error':
            returnData(
                $returnType,
                [
                    'type' => 'string',
                    'data' => __('Auth error'),
                ]
            );
        break;

        case 'license_error':
            returnData(
                $returnType,
                [
                    'type' => 'string',
                    'data' => __('License not allowed for this operation.'),
                ]
            );
        break;

        default:
            returnData(
                $returnType,
                [
                    'type' => 'string',
                    'data' => __($typeError),
                ]
            );
        break;
    }
}


/**
 * @param  $returnType
 * @param  $data
 * @param  $separator
 * @return
 */
function returnData($returnType, $data, $separator=';')
{
    switch ($returnType) {
        case 'string':
            if (is_array($data['data'])) {
                echo convert_array_multi($data['data'], $separator);
            } else {
                echo $data['data'];
            }
        break;

        case 'csv':
        case 'csv_head':
            if (is_array($data['data'])) {
                if (array_key_exists('list_index', $data)) {
                    if ($returnType == 'csv_head') {
                        header('Content-type: text/csv');
                        foreach ($data['list_index'] as $index) {
                            echo $index;
                            if (end($data['list_index']) == $index) {
                                echo "\n";
                            } else {
                                echo $separator;
                            }
                        }
                    }

                    foreach ($data['data'] as $dataContent) {
                        foreach ($data['list_index'] as $index) {
                            if (array_key_exists($index, $dataContent)) {
                                echo str_replace("\n", ' ', $dataContent[$index]);
                            }

                            if (end($data['list_index']) == $index) {
                                echo "\n";
                            } else {
                                echo $separator;
                            }
                        }
                    }
                } else {
                    if (!empty($data['data'])) {
                        foreach ($data['data'] as $dataContent) {
                            $clean = array_map('array_apply_io_safe_output', (array) $dataContent);
                            foreach ($clean as $k => $v) {
                                $clean[$k] = str_replace("\r", "\n", $clean[$k]);
                                $clean[$k] = str_replace("\n", ' ', $clean[$k]);
                                $clean[$k] = strip_tags($clean[$k]);
                                $clean[$k] = str_replace(';', ' ', $clean[$k]);
                            }

                            $row = implode($separator, $clean);
                            echo $row."\n";
                        }
                    }
                }
            } else {
                echo $data['data'];
            }
        break;

        case 'json':
            $data = array_apply_io_safe_output($data);
            header('Content-type: application/json');
            // Allows extra parameters to json_encode, like JSON_FORCE_OBJECT
            if ($separator == ';') {
                $separator = null;
            }

            if (empty($separator)) {
                echo json_encode($data);
            } else {
                echo json_encode($data, $separator);
            }
        break;
    }
}


function array_apply_io_safe_output($item)
{
    return io_safe_output($item);
}


/**
 *
 * @param  $ip
 * @return unknown_type
 */
function isInACL($ip)
{
    global $config;

    if (! is_array($config['list_ACL_IPs_for_API'])) {
        $config['list_ACL_IPs_for_API'] = explode(';', $config['list_ACL_IPs_for_API']);
    }

    if (in_array($ip, $config['list_ACL_IPs_for_API'])) {
        return true;
    }

    // If the IP is not in the list, we check one by one, all the wildcard registers
    foreach ($config['list_ACL_IPs_for_API'] as $acl_ip) {
        if (preg_match('/\*/', $acl_ip)) {
            if ($acl_ip[0] == '*' && strlen($acl_ip) > 1) {
                // example *.lab.artica.es == 151.80.15.*
                $acl_ip = str_replace('*.', '', $acl_ip);
                $name = [];
                $name = gethostbyname($acl_ip);
                $names = explode('.', $name);
                $names[3] = '';
                $names = implode('.', $names);
                if (preg_match('/'.$names.'/', $ip)) {
                    return true;
                }
            } else {
                // example 192.168.70.* or *
                $acl_ip = str_replace('.', '\.', $acl_ip);
                // Replace wilcard by .* to do efective in regular expression
                $acl_ip = str_replace('*', '.*', $acl_ip);
                // If the string match with the beginning of the IP give it access
                if (preg_match('/'.$acl_ip.'/', $ip)) {
                    return true;
                }
            }

            // Scape for protection
        } else {
            // example lab.artica.es without '*'
            $name = [];
            $name = gethostbyname($acl_ip);
            if (preg_match('/'.$name.'/', $ip, $matches)) {
                // This is for false matches, like '' or $.
                if (count($matches) == 1 && $matches[0] == '') {
                    continue;
                } else {
                    return true;
                }
            }
        }
    }

    return false;
}


// Return string OK,[version],[build]
function api_get_test()
{
    global $pandora_version;
    global $build_version;

    echo "OK,$pandora_version,$build_version";

    if (defined('METACONSOLE')) {
        echo ',meta';
    }
}


// Return OK if agent cache is activated
function api_get_test_agent_cache()
{
    if (defined('METACONSOLE')) {
        return;
    }

    $status = enterprise_hook('test_agent_cache', []);
    if ($status === ENTERPRISE_NOT_HOOK) {
        echo 'ERR';
        return;
    }

    echo $status;

}


// -------------------------DEFINED OPERATIONS FUNCTIONS-----------------


/**
 * Example: http://localhost/pandora_console/include/api.php?op=get&op2=license&user=admin&apipass=1234&pass=pandora&return_type=json
 * Retrieve license information.
 *
 * @param null   $trash1     Not used.
 * @param null   $trash1     Not used.
 * @param null   $trash1     Not used.
 * @param string $returnType Return type (string, json...).
 *
 * @return void
 */
function api_get_license($trash1, $trash2, $trash3, $returnType='json')
{
    global $config;
    check_login();

    if (! check_acl($config['id_user'], 0, 'PM')) {
        returnError('forbidden', $returnType);
        return;
    }

    enterprise_include_once('include/functions_license.php');
    $license = enterprise_hook('license_get_info');
    if ($license === ENTERPRISE_NOT_HOOK) {
        // Not an enterprise environment?
        if (license_free()) {
            $license = 'PANDORA_FREE';
        }

        returnData(
            $returnType,
            [
                'type' => 'array',
                'data' => ['license_mode' => $license],
            ]
        );
        return;
    }

    returnData(
        $returnType,
        [
            'type' => 'array',
            'data' => $license,
        ]
    );

}


/**
 * Example: http://localhost/pandora_console/include/api.php?op=get&op2=license_remaining&user=admin&apipass=1234&pass=pandora&return_type=json
 * Retrieve license status agents or modules left.
 *
 * @param null   $trash1     Not used.
 * @param null   $trash1     Not used.
 * @param null   $trash1     Not used.
 * @param string $returnType Return type (string, json...).
 *
 * @return void
 */
function api_get_license_remaining(
    $trash1,
    $trash2,
    $trash3,
    $returnType='json'
) {
    enterprise_include_once('include/functions_license.php');
    $license = enterprise_hook('license_get_info');
    if ($license === ENTERPRISE_NOT_HOOK) {
        if (license_free()) {
            returnData(
                $returnType,
                [
                    'type' => 'integer',
                    'data' => PHP_INT_MAX,
                ]
            );
        } else {
            returnError('get-license', 'Failed to verify license.');
        }

        return;
    }

    returnData(
        $returnType,
        [
            'type' => 'integer',
            'data' => ($license['limit'] - $license['count_enabled']),
        ]
    );
}


function api_get_groups($thrash1, $thrash2, $other, $returnType, $user_in_db)
{
    $returnAllGroup = true;
    $returnAllColumns = false;

    if (isset($other['data'][1])) {
        $returnAllGroup = ( $other['data'][1] == '1' ? true : false);
    }

    if (isset($other['data'][2])) {
        $returnAllColumns = ( $other['data'][2] == '1' ? true : false);
    }

    $groups = users_get_groups($user_in_db, 'AR', $returnAllGroup, $returnAllColumns);

    $data_groups = [];
    foreach ($groups as $id => $group) {
        $data_groups[] = [
            $id,
            $group,
        ];
    }

    if (!isset($other['data'][0])) {
        $separator = ';';
        // by default
    } else {
        $separator = $other['data'][0];
    }

    $data['type'] = 'array';
    $data['data'] = $data_groups;

    returnData($returnType, $data, $separator);
}


function api_get_agent_module_name_last_value($agentName, $moduleName, $other=';', $returnType)
{
    $idAgent = agents_get_agent_id($agentName);

    $sql = sprintf(
        'SELECT id_agente_modulo
        FROM tagente_modulo
        WHERE id_agente = %d AND nombre LIKE "%s"',
        $idAgent,
        $moduleName
    );
    $idModuleAgent = db_get_value_sql($sql);

    api_get_module_last_value($idModuleAgent, null, $other, $returnType);
}


function api_get_agent_module_name_last_value_alias($alias, $moduleName, $other=';', $returnType)
{
    $sql = sprintf(
        'SELECT tagente_modulo.id_agente_modulo FROM tagente_modulo
            INNER JOIN tagente ON tagente_modulo.id_agente = tagente.id_agente
            WHERE tagente.alias LIKE "%s" AND tagente_modulo.nombre LIKE "%s"',
        $alias,
        $moduleName
    );
    $idModuleAgent = db_get_value_sql($sql);

    api_get_module_last_value($idModuleAgent, null, $other, $returnType);
}


function api_get_module_last_value($idAgentModule, $trash1, $other=';', $returnType)
{
    global $config;
    if (defined('METACONSOLE')) {
        return;
    }

    $check_access = agents_check_access_agent(modules_get_agentmodule_agent($idAgentModule));
    if ($check_access === false || !check_acl($config['id_user'], 0, 'AR')) {
        returnError('forbidden', $returnType);
        return;
    }

    $sql = sprintf(
        'SELECT datos
        FROM tagente_estado
        WHERE id_agente_modulo = %d',
        $idAgentModule
    );
    $value = db_get_value_sql($sql);

    if ($value === false) {
        if (isset($other['data'][1]) && $other['data'][0] == 'error_value') {
            returnData($returnType, ['type' => 'string', 'data' => $other['data'][1]]);
        } else if ($check_access) {
            returnError('no_data_to_show', $returnType);
        } else {
            returnError('id_not_found', $returnType);
        }

        return;
    }

    $data = [
        'type' => 'string',
        'data' => $value,
    ];
    returnData($returnType, $data);
}


/***
 * end of DB column mapping table
 ***/


/**
 *
 * @param $trash1
 * @param $trahs2
 * @param mixed      $other If $other is string is only the separator,
 *       but if it's array, $other as param is <separator>;<replace_return>;(<field_1>,<field_2>...<field_n>) in this order
 *       and separator char (after text ; ) must be diferent that separator (and other) url (pass in param othermode as othermode=url_encode_separator_<separator>)
 *       example:
 *
 *       return csv with fields type_row,group_id and agent_name, separate with ";" and the return of the text replace for " "
 *       api.php?op=get&op2=tree_agents&return_type=csv&other=;| |type_row,group_id,agent_name&other_mode=url_encode_separator_|
 *
 * @param  $returnType
 * @return unknown_type
 */
function api_get_tree_agents($trash1, $trahs2, $other, $returnType)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if ($other['type'] == 'array') {
        $separator = $other['data'][0];
        $returnReplace = $other['data'][1];
        if (trim($other['data'][2]) == '') {
            $fields = false;
        } else {
            $fields = explode(',', $other['data'][2]);
            foreach ($fields as $index => $field) {
                $fields[$index] = trim($field);
            }
        }
    } else {
        if (strlen($other['data']) == 0) {
            $separator = ';';
            // by default
        } else {
            $separator = $other['data'];
        }

        $returnReplace = ' ';
        $fields = false;
    }

    /*
        NOTE: if you want to add an output field, you have to add it to;
        1. $master_fields (field name)
        2. one of following field_column_mapping array (a pair of field name and corresponding column designation)

        e.g. To add a new field named 'agent_NEWFIELD' that comes from tagente's COLUMN_X , you have to add;
        1. "agent_NEW_FIELD"  to $master_fields
        2. "'agent_NEW_FIELD' => 'agent_NEWFIELD as COLUMN_X'"  to $agent_field_column_mapping
    */

    // all of output field names
    $master_fields = [
        'type_row',

        'group_id',
        'group_name',
        'group_parent',
        'disabled',
        'custom_id',
        'group_description',
        'group_contact',
        'group_other',

        'agent_id',
        'alias',
        'agent_direction',
        'agent_comentary',
        'agent_id_group',
        'agent_last_contant',
        'agent_mode',
        'agent_interval',
        'agent_id_os',
        'agent_os_version',
        'agent_version',
        'agent_last_remote_contact',
        'agent_disabled',
        'agent_id_parent',
        'agent_custom_id',
        'agent_server_name',
        'agent_cascade_protection',
        'agent_cascade_protection_module',
        'agent_name',

        'module_id_agent_modulo',
        'module_id_agent',
        'module_id_module_type',
        'module_description',
        'module_name',
        'module_max',
        'module_min',
        'module_interval',
        'module_tcp_port',
        'module_tcp_send',
        'module_tcp_rcv',
        'module_snmp_community',
        'module_snmp_oid',
        'module_ip_target',
        'module_id_module_group',
        'module_flag',
        'module_id_module',
        'module_disabled',
        'module_id_export',
        'module_plugin_parameter',
        'module_plugin_pass',
        'module_plugin_user',
        'module_id_plugin',
        'module_post_process',
        'module_prediction_module',
        'module_max_timeout',
        'module_max_retries',
        'module_custom_id',
        'module_history_data',
        'module_min_warning',
        'module_max_warning',
        'module_str_warning',
        'module_min_critical',
        'module_max_critical',
        'module_str_critical',
        'module_min_ff_event',
        'module_delete_pending',
        'module_id_agent_state',
        'module_data',
        'module_timestamp',
        'module_state',
        'module_last_try',
        'module_utimestamp',
        'module_current_interval',
        'module_running_by',
        'module_last_execution_try',
        'module_status_changes',
        'module_last_status',
        'module_plugin_macros',
        'module_macros',
        'module_critical_inverse',
        'module_warning_inverse',

        'alert_id_agent_module',
        'alert_id_alert_template',
        'alert_internal_counter',
        'alert_last_fired',
        'alert_last_reference',
        'alert_times_fired',
        'alert_disabled',
        'alert_force_execution',
        'alert_id_alert_action',
        'alert_type',
        'alert_value',
        'alert_matches_value',
        'alert_max_value',
        'alert_min_value',
        'alert_time_threshold',
        'alert_max_alerts',
        'alert_min_alerts',
        'alert_time_from',
        'alert_time_to',
        'alert_monday',
        'alert_tuesday',
        'alert_wednesday',
        'alert_thursday',
        'alert_friday',
        'alert_saturday',
        'alert_sunday',
        'alert_recovery_notify',
        'alert_field2_recovery',
        'alert_field3_recovery',
        'alert_id_alert_template_module',
        'alert_fires_min',
        'alert_fires_max',
        'alert_id_alert_command',
        'alert_command',
        'alert_internal',
        'alert_template_modules_id',
        'alert_templates_id',
        'alert_template_module_actions_id',
        'alert_actions_id',
        'alert_commands_id',
        'alert_templates_name',
        'alert_actions_name',
        'alert_commands_name',
        'alert_templates_description',
        'alert_commands_description',
        'alert_template_modules_priority',
        'alert_templates_priority',
        'alert_templates_field1',
        'alert_actions_field1',
        'alert_templates_field2',
        'alert_actions_field2',
        'alert_templates_field3',
        'alert_actions_field3',
        'alert_templates_id_group',
        'alert_actions_id_group',
    ];

    /*
     * Agent related field mappings (output field => column designation for 'tagente').
     * agent_id is not in this list (because it is mandatory).
     * agent_id_group is not in this list.
     */
    $agent_field_column_mapping = [
        'agent_name'                      => 'nombre as agent_name',
        'agent_direction'                 => 'direccion as agent_direction',
        'agent_comentary'                 => 'comentarios as agent_comentary',
        'agent_last_contant'              => 'ultimo_contacto as agent_last_contant',
        'agent_mode'                      => 'modo as agent_mode',
        'agent_interval'                  => 'intervalo as agent_interval',
        'agent_id_os'                     => 'id_os as agent_id_os',
        'agent_os_version'                => 'os_version as agent_os_version',
        'agent_version'                   => 'agent_version as agent_version',
        'agent_last_remote_contact'       => 'ultimo_contacto_remoto as agent_last_remote_contact',
        'agent_disabled'                  => 'disabled as agent_disabled',
        'agent_id_parent'                 => 'id_parent as agent_id_parent',
        'agent_custom_id'                 => 'custom_id as agent_custom_id',
        'agent_server_name'               => 'server_name as agent_server_name',
        'agent_cascade_protection'        => 'cascade_protection as agent_cascade_protection',
        'agent_cascade_protection_module' => 'cascade_protection_module as agent_cascade_protection_module',
    ];

    // module related field mappings 1/2 (output field => column for 'tagente_modulo')
    // module_id_agent_modulo  is not in this list
    // module_plugin_user, module_plugin_pass, module_plugin_macros are not in this list due to security purposes.
    $module_field_column_mapping = [
        'module_id_agent'          => 'id_agente as module_id_agent',
        'module_id_module_type'    => 'id_tipo_modulo as module_id_module_type',
        'module_description'       => 'descripcion as module_description',
        'module_name'              => 'nombre as module_name',
        'module_max'               => 'max as module_max',
        'module_min'               => 'min as module_min',
        'module_interval'          => 'module_interval',
        'module_tcp_port'          => 'tcp_port as module_tcp_port',
        'module_tcp_send'          => 'tcp_send as module_tcp_send',
        'module_tcp_rcv'           => 'tcp_rcv as module_tcp_rcv',
        'module_snmp_community'    => 'snmp_community as module_snmp_community',
        'module_snmp_oid'          => 'snmp_oid as module_snmp_oid',
        'module_ip_target'         => 'ip_target as module_ip_target',
        'module_id_module_group'   => 'id_module_group as module_id_module_group',
        'module_flag'              => 'flag as module_flag',
        'module_id_module'         => 'id_modulo as module_id_module',
        'module_disabled'          => 'disabled as module_disabled',
        'module_id_export'         => 'id_export as module_id_export',
        'module_plugin_parameter'  => 'plugin_parameter as module_plugin_parameter',
        'module_id_plugin'         => 'id_plugin as module_id_plugin',
        'module_post_process'      => 'post_process as module_post_process',
        'module_prediction_module' => 'prediction_module as module_prediction_module',
        'module_max_timeout'       => 'max_timeout as module_max_timeout',
        'module_max_retries'       => 'max_retries as module_max_retries',
        'module_custom_id'         => 'custom_id as module_custom_id',
        'module_history_data'      => 'history_data as module_history_data',
        'module_min_warning'       => 'min_warning as module_min_warning',
        'module_max_warning'       => 'max_warning as module_max_warning',
        'module_str_warning'       => 'str_warning as module_str_warning',
        'module_min_critical'      => 'min_critical as module_min_critical',
        'module_max_critical'      => 'max_critical as module_max_critical',
        'module_str_critical'      => 'str_critical as module_str_critical',
        'module_min_ff_event'      => 'min_ff_event as module_min_ff_event',
        'module_delete_pending'    => 'delete_pending as module_delete_pending',
        'module_macros'            => 'module_macros as module_macros',
        'module_critical_inverse'  => 'critical_inverse as module_critical_inverse',
        'module_warning_inverse'   => 'warning_inverse as module_warning_inverse',
    ];

    // module related field mappings 2/2 (output field => column for 'tagente_estado')
    // module_id_agent_modulo  is not in this list
    $estado_fields_to_columns_mapping = [
        'module_id_agent_state'     => 'id_agente_estado as module_id_agent_state',
        'module_data'               => 'datos as module_data',
        'module_timestamp'          => 'timestamp as module_timestamp',
        'module_state'              => 'estado as module_state',
        'module_last_try'           => 'last_try as module_last_try',
        'module_utimestamp'         => 'utimestamp as module_utimestamp',
        'module_current_interval'   => 'current_interval as module_current_interval',
        'module_running_by'         => 'running_by as module_running_by',
        'module_last_execution_try' => 'last_execution_try as module_last_execution_try',
        'module_status_changes'     => 'status_changes as module_status_changes',
        'module_last_status'        => 'last_status as module_last_status',
    ];

    // alert related field mappings (output field => column for 'talert_template_modules', ... )
    $alert_fields_to_columns_mapping = [
        // 'alert_id_agent_module (id_agent_module) is not in this list
        'alert_template_modules_id'        => 't1.id as alert_template_modules_id',
        'alert_id_alert_template'          => 't1.id_alert_template as alert_id_alert_template',
        'alert_internal_counter'           => 't1.internal_counter as alert_internal_counter',
        'alert_last_fired'                 => 't1.last_fired as alert_last_fired',
        'alert_last_reference'             => 't1.last_reference as alert_last_reference',
        'alert_times_fired'                => 't1.times_fired as alert_times_fired',
        'alert_disabled'                   => 't1.disabled as alert_disabled',
        'alert_force_execution'            => 't1.force_execution as alert_force_execution',
        'alert_template_modules_priority'  => 't1.priority as alert_template_modules_priority',

        'alert_templates_id'               => 't2.id as alert_templates_id',
        'alert_type'                       => 't2.type as alert_type',
        'alert_value'                      => 't2.value as alert_value',
        'alert_matches_value'              => 't2.matches_value as alert_matches_value',
        'alert_max_value'                  => 't2.max_value as alert_max_value',
        'alert_min_value'                  => 't2.min_value as alert_min_value',
        'alert_time_threshold'             => 't2.time_threshold as alert_time_threshold',
        'alert_max_alerts'                 => 't2.max_alerts as alert_max_alerts',
        'alert_min_alerts'                 => 't2.min_alerts as alert_min_alerts',
        'alert_time_from'                  => 't2.time_from as alert_time_from',
        'alert_time_to'                    => 't2.time_to as alert_time_to',
        'alert_monday'                     => 't2.monday as alert_monday',
        'alert_tuesday'                    => 't2.tuesday as alert_tuesday',
        'alert_wednesday'                  => 't2.wednesday as alert_wednesday',
        'alert_thursday'                   => 't2.thursday as alert_thursday',
        'alert_friday'                     => 't2.friday as alert_friday',
        'alert_saturday'                   => 't2.saturday as alert_saturday',
        'alert_sunday'                     => 't2.sunday as alert_sunday',
        'alert_templates_name'             => 't2.name as alert_templates_name',
        'alert_templates_description'      => 't2.description as alert_templates_description',
        'alert_templates_priority'         => 't2.priority as alert_templates_priority',
        'alert_templates_id_group'         => 't2.id_group as alert_templates_id_group',
        'alert_recovery_notify'            => 't2.recovery_notify as alert_recovery_notify',
        'alert_field2_recovery'            => 't2.field2_recovery as alert_field2_recovery',
        'alert_field3_recovery'            => 't2.field3_recovery as alert_field3_recovery',
        'alert_templates_field1'           => 't2.field1 as alert_templates_field1',
        'alert_templates_field2'           => 't2.field2 as alert_templates_field2',
        'alert_templates_field3'           => 't2.field3 as alert_templates_field3',

        'alert_template_module_actions_id' => 't3.id as alert_template_module_actions_id',
        'alert_id_alert_action'            => 't3.id_alert_action as alert_id_alert_action',
        'alert_id_alert_template_module'   => 't3.id_alert_template_module as alert_id_alert_template_module',
        'alert_fires_min'                  => 't3.fires_min as alert_fires_min',
        'alert_fires_max'                  => 't3.fires_max as alert_fires_max',

        'alert_actions_id'                 => 't4.id as alert_actions_id',
        'alert_actions_name'               => 't4.name as alert_actions_name',
        'alert_id_alert_command'           => 't4.id_alert_command as alert_id_alert_command',
        'alert_actions_id_group'           => 't4.id_group as alert_actions_id_group',
        'alert_actions_field1'             => 't4.field1 as alert_actions_field1',
        'alert_actions_field2'             => 't4.field2 as alert_actions_field2',
        'alert_actions_field3'             => 't4.field3 as alert_actions_field3',

        'alert_command'                    => 't5.command as alert_command',
        'alert_internal'                   => 't5.internal as alert_internal',
        'alert_commands_id'                => 't5.id as alert_commands_id',
        'alert_commands_name'              => 't5.name as alert_commands_name',
        'alert_commands_description'       => 't5.description as alert_commands_description',
    ];

    if ($fields == false) {
        $fields = $master_fields;
    }

    // construct column list to query for tagente, tagente_modulo, tagente_estado and alert-related tables
    {
        $agent_additional_columns  = '';
        $module_additional_columns = '';
        $estado_additional_columns = '';
        $alert_additional_columns  = '';

    foreach ($fields as $fld) {
        if (array_key_exists($fld, $agent_field_column_mapping)) {
            $agent_additional_columns .= (', '.$agent_field_column_mapping[$fld] );
        }

        if (array_key_exists($fld, $module_field_column_mapping)) {
            $module_additional_columns .= (', '.$module_field_column_mapping[$fld]);
        }

        if (array_key_exists($fld, $estado_fields_to_columns_mapping)) {
            $estado_additional_columns .= (', '.$estado_fields_to_columns_mapping[$fld]);
        }

        if (array_key_exists($fld, $alert_fields_to_columns_mapping)) {
            $alert_additional_columns .= (', '.$alert_fields_to_columns_mapping[$fld]);
        }
    }

    }

    $returnVar = [];

    // Get only the user groups
    $filter_groups = '1 = 1';
    if (!users_is_admin($config['id_user'])) {
        $user_groups = implode(',', array_keys(users_get_groups()));
        $filter_groups = "id_grupo IN ($user_groups)";
    }

    $groups = db_get_all_rows_sql(
        'SELECT id_grupo as group_id, '.'nombre as group_name, parent as group_parent, disabled, custom_id, '.'description as group_description, contact as group_contact, '.'other as group_other FROM tgrupo WHERE '.$filter_groups
    );
    if ($groups === false) {
        $groups = [];
    }

    foreach ($groups as &$group) {
        if (check_acl($config['id_user'], $group['group_id'], 'AR') === false) {
            continue;
        }

        $group = str_replace('\n', $returnReplace, $group);

        $group['type_row'] = 'group';
        $returnVar[] = $group;

        // Get the agents for this group
        $id_group = $group['group_id'];
        $agents = db_get_all_rows_sql(
            "SELECT id_agente AS agent_id, id_grupo AS agent_id_group , alias $agent_additional_columns
            FROM tagente ta LEFT JOIN tagent_secondary_group tasg
                ON ta.id_agente = tasg.id_agent
            WHERE ta.id_grupo = $id_group OR tasg.id_group = $id_group"
        );
        if ($agents === false) {
            $agents = [];
        }

        if ((bool) check_acl($config['id_user'], $id_group, 'AW') === true) {
            if (array_search('module_plugin_user', $fields) !== false) {
                $module_additional_columns .= ' ,plugin_user as module_plugin_user';
            }

            if (array_search('module_plugin_pass', $fields) !== false) {
                $module_additional_columns .= ' ,plugin_pass as module_plugin_pass';
            }

            if (array_search('module_plugin_macros', $fields) !== false) {
                $module_additional_columns .= ' ,macros as module_plugin_macros';
            }
        }

        foreach ($agents as $index => &$agent) {
            $agent = str_replace('\n', $returnReplace, $agent);

            $agent['type_row']  = 'agent';
            $returnVar[] = $agent;

            if (strlen($module_additional_columns) <= 0
                && strlen($estado_additional_columns) <= 0
                && strlen($alert_additional_columns) <= 0
            ) {
                continue;
                // SKIP collecting MODULES and ALERTS
            }

            $sql = 'SELECT *
            FROM (SELECT id_agente_modulo as module_id_agent_modulo '.$module_additional_columns.'
                    FROM tagente_modulo t1
                    WHERE id_agente = '.$agent['agent_id'].') t1 
                INNER JOIN (SELECT id_agente_modulo as module_id_agent_modulo '.$estado_additional_columns.'
                    FROM tagente_estado
                    WHERE id_agente = '.$agent['agent_id'].') t2
                ON t1.module_id_agent_modulo = t2.module_id_agent_modulo';

            $modules = db_get_all_rows_sql(
                $sql
            );

            if ($modules === false) {
                $modules = [];
            }

            foreach ($modules as &$module) {
                $module = str_replace('\n', $returnReplace, $module);

                $module['type_row'] = 'module';

                if ($module['module_macros']) {
                    $module['module_macros'] = base64_decode($module['module_macros']);
                }

                $returnVar[] = $module;

                if (strlen($alert_additional_columns) <= 0) {
                    continue;
                    // SKIP collecting ALERTS info
                }

                $alerts = db_get_all_rows_sql(
                    'SELECT t1.id_agent_module as alert_id_agent_module '.$alert_additional_columns.'
                    FROM (SELECT * FROM talert_template_modules
                        WHERE id_agent_module = '.$module['module_id_agent_modulo'].') t1 
                    INNER JOIN talert_templates t2
                        ON t1.id_alert_template = t2.id
                    LEFT JOIN talert_template_module_actions t3
                        ON t1.id = t3.id_alert_template_module
                    LEFT JOIN talert_actions t4
                        ON t3.id_alert_action = t4.id
                    LEFT JOIN talert_commands t5
                        ON t4.id_alert_command = t5.id'
                );

                if ($alerts === false) {
                    $alerts = [];
                }

                foreach ($alerts as &$alert) {
                    $alert = str_replace('\n', $returnReplace, $alert);
                    $alert['type_row'] = 'alert';
                    $returnVar[] = $alert;
                }
            }
        }
    }

    $data = [
        'type' => 'array',
        'data' => $returnVar,
    ];

    $data['list_index'] = $fields;

    returnData($returnType, $data, $separator);
}


/**
 *
 * @param $id_module
 * @param $trahs2
 * @param mixed      $other If $other is string is only the separator,
 *       but if it's array, $other as param is <separator>;<replace_return>;(<field_1>,<field_2>...<field_n>) in this order
 *       and separator char (after text ; ) must be diferent that separator (and other) url (pass in param othermode as othermode=url_encode_separator_<separator>)
 *       example:
 *
 *       return csv with fields type_row,group_id and agent_name, separate with ";" and the return of the text replace for " "
 *       api.php?op=get&op2=module_properties&id=1116&return_type=csv&other=;| |module_id_agent,module_name,module_description,module_last_try,module_data&other_mode=url_encode_separator_|
 *
 * @param  $returnType
 * @return unknown_type
 */
function api_get_module_properties($id_module, $trahs2, $other, $returnType)
{
    if (!util_api_check_agent_and_print_error(modules_get_agentmodule_agent($id_module), $returnType)) {
        return;
    }

    if ($other['type'] == 'array') {
        $separator = $other['data'][0];
        $returnReplace = $other['data'][1];
        if (trim($other['data'][2]) == '') {
            $fields = false;
        } else {
            $fields = explode(',', $other['data'][2]);
            foreach ($fields as $index => $field) {
                $fields[$index] = trim($field);
            }
        }
    } else {
        if (strlen($other['data']) == 0) {
            $separator = ';';
            // by default
        } else {
            $separator = $other['data'];
        }

        $returnReplace = ' ';
        $fields = false;
    }

    get_module_properties($id_module, $fields, $separator, $returnType, $returnReplace);
}


/**
 *
 * @param $agent_name
 * @param $module_name
 * @param mixed       $other If $other is string is only the separator,
 *        but if it's array, $other as param is <separator>;<replace_return>;(<field_1>,<field_2>...<field_n>) in this order
 *        and separator char (after text ; ) must be diferent that separator (and other) url (pass in param othermode as othermode=url_encode_separator_<separator>)
 *        example:
 *
 *        return csv with fields type_row,group_id and agent_name, separate with ";" and the return of the text replace for " "
 *        api.php?op=get&op2=module_properties_by_name&id=sample_agent&id2=sample_module&return_type=csv&other=;| |module_id_agent,module_name,module_str_critical,module_str_warning&other_mode=url_encode_separator_|
 *
 * @param  $returnType
 * @return unknown_type
 */
function api_get_module_properties_by_name($agent_name, $module_name, $other, $returnType)
{
    if ($other['type'] == 'array') {
        $separator = $other['data'][0];
        $returnReplace = $other['data'][1];
        if (trim($other['data'][2]) == '') {
            $fields = false;
        } else {
            $fields = explode(',', $other['data'][2]);
            foreach ($fields as $index => $field) {
                $fields[$index] = trim($field);
            }
        }
    } else {
        if (strlen($other['data']) == 0) {
            $separator = ';';
            // by default
        } else {
            $separator = $other['data'];
        }

        $returnReplace = ' ';
        $fields = false;
    }

    $agent_id = agents_get_agent_id($agent_name);
    if ($agent_id == 0) {
        returnError('Does not exist agent with this name.');
        return;
    }

    if (!util_api_check_agent_and_print_error($agent_id, $returnType)) {
        return;
    }

    $tagente_modulo = modules_get_agentmodule_id($module_name, $agent_id);
    if ($tagente_modulo === false) {
        returnError('Does not exist module with this name.');
        return;
    }

    $module_id = $tagente_modulo['id_agente_modulo'];

    get_module_properties($module_id, $fields, $separator, $returnType, $returnReplace);
}


/*
 * subroutine for api_get_module_properties() and api_get_module_properties_by_name().
 */


 /**
  *
  * @param $alias
  * @param $module_name
  * @param mixed       $other If $other is string is only the separator,
  *        but if it's array, $other as param is <separator>;<replace_return>;(<field_1>,<field_2>...<field_n>) in this order
  *        and separator char (after text ; ) must be diferent that separator (and other) url (pass in param othermode as othermode=url_encode_separator_<separator>)
  *        example:
  *
  *        return csv with fields type_row,group_id and agent_name, separate with ";" and the return of the text replace for " "
  *        api.php?op=get&op2=module_properties_by_name&id=sample_agent&id2=sample_module&return_type=csv&other=;| |module_id_agent,module_name,module_str_critical,module_str_warning&other_mode=url_encode_separator_|
  *
  * @param  $returnType
  * @return unknown_type
  */
function api_get_module_properties_by_alias($alias, $module_name, $other, $returnType)
{
    if ($other['type'] == 'array') {
        $separator = $other['data'][0];
        $returnReplace = $other['data'][1];
        if (trim($other['data'][2]) == '') {
            $fields = false;
        } else {
            $fields = explode(',', $other['data'][2]);
            foreach ($fields as $index => $field) {
                $fields[$index] = trim($field);
            }
        }
    } else {
        if (strlen($other['data']) == 0) {
            $separator = ';';
            // by default
        } else {
            $separator = $other['data'];
        }

        $returnReplace = ' ';
        $fields = false;
    }

    $sql = sprintf(
        'SELECT tagente_modulo.id_agente_modulo, tagente.id_agente FROM tagente_modulo 
                    INNER JOIN tagente ON tagente_modulo.id_agente = tagente.id_agente 
                    WHERE tagente.alias LIKE "%s" AND tagente_modulo.nombre LIKE "%s"',
        $alias,
        $module_name
    );

    $data = db_get_row_sql($sql);
    if ($data === false) {
        returnError('Does not exist the pair alias/module required.');
    }

    if (!util_api_check_agent_and_print_error($data['id_agente'], $returnType)) {
        return;
    }

    $module_id = $data['id_agente_modulo'];

    get_module_properties($module_id, $fields, $separator, $returnType, $returnReplace);
}


/*
 * subroutine for api_get_module_properties() and api_get_module_properties_by_name().
 */

function get_module_properties($id_module, $fields, $separator, $returnType, $returnReplace)
{
    /*
        NOTE: if you want to add an output field, you have to add it to;
        1. $module_properties_master_fields (field name in order)
        2. Update field_column_mapping array (arraies are shared with get_tree_agents()).
            Each entry is  (DB coloum name => query fragment)
    */

    // all of output field names
    $module_properties_master_fields = [
        'module_id_agent_modulo',
        'module_id_agent',
        'module_id_module_type',
        'module_description',
        'module_name',
        'module_max',
        'module_min',
        'module_interval',
        'module_tcp_port',
        'module_tcp_send',
        'module_tcp_rcv',
        'module_snmp_community',
        'module_snmp_oid',
        'module_ip_target',
        'module_id_module_group',
        'module_flag',
        'module_id_module',
        'module_disabled',
        'module_id_export',
        'module_plugin_user',
        'module_plugin_pass',
        'module_plugin_parameter',
        'module_id_plugin',
        'module_post_process',
        'module_prediction_module',
        'module_max_timeout',
        'module_max_retries',
        'module_custom_id',
        'module_history_data',
        'module_min_warning',
        'module_max_warning',
        'module_str_warning',
        'module_min_critical',
        'module_max_critical',
        'module_str_critical',
        'module_min_ff_event',
        'module_delete_pending',
        'module_id_agent_state',
        'module_data',
        'module_timestamp',
        'module_state',
        'module_last_try',
        'module_utimestamp',
        'module_current_interval',
        'module_running_by',
        'module_last_execution_try',
        'module_status_changes',
        'module_last_status',
        'module_plugin_macros',
        'module_macros',
        'module_critical_inverse',
        'module_warning_inverse',
    ];

    // module related field mappings 1/2 (output field => column for 'tagente_modulo')
    // module_id_agent_modulo  is not in this list
    // module_plugin_user, module_plugin_pass, module_plugin_macros are not in this list due to security purposes.
    $module_field_column_mapping = [
        'module_id_agent'          => 'id_agente as module_id_agent',
        'module_id_module_type'    => 'id_tipo_modulo as module_id_module_type',
        'module_description'       => 'descripcion as module_description',
        'module_name'              => 'nombre as module_name',
        'module_max'               => 'max as module_max',
        'module_min'               => 'min as module_min',
        'module_interval'          => 'module_interval',
        'module_tcp_port'          => 'tcp_port as module_tcp_port',
        'module_tcp_send'          => 'tcp_send as module_tcp_send',
        'module_tcp_rcv'           => 'tcp_rcv as module_tcp_rcv',
        'module_snmp_community'    => 'snmp_community as module_snmp_community',
        'module_snmp_oid'          => 'snmp_oid as module_snmp_oid',
        'module_ip_target'         => 'ip_target as module_ip_target',
        'module_id_module_group'   => 'id_module_group as module_id_module_group',
        'module_flag'              => 'flag as module_flag',
        'module_id_module'         => 'id_modulo as module_id_module',
        'module_disabled'          => 'disabled as module_disabled',
        'module_id_export'         => 'id_export as module_id_export',
        'module_plugin_parameter'  => 'plugin_parameter as module_plugin_parameter',
        'module_plugin_user'       => 'plugin_user as module_plugin_user',
        'module_plugin_pass'       => 'plugin_pass as module_plugin_pass',
        'module_plugin_macros'     => 'macros as module_plugin_macros',
        'module_id_plugin'         => 'id_plugin as module_id_plugin',
        'module_post_process'      => 'post_process as module_post_process',
        'module_prediction_module' => 'prediction_module as module_prediction_module',
        'module_max_timeout'       => 'max_timeout as module_max_timeout',
        'module_max_retries'       => 'max_retries as module_max_retries',
        'module_custom_id'         => 'custom_id as module_custom_id',
        'module_history_data'      => 'history_data as module_history_data',
        'module_min_warning'       => 'min_warning as module_min_warning',
        'module_max_warning'       => 'max_warning as module_max_warning',
        'module_str_warning'       => 'str_warning as module_str_warning',
        'module_min_critical'      => 'min_critical as module_min_critical',
        'module_max_critical'      => 'max_critical as module_max_critical',
        'module_str_critical'      => 'str_critical as module_str_critical',
        'module_min_ff_event'      => 'min_ff_event as module_min_ff_event',
        'module_delete_pending'    => 'delete_pending as module_delete_pending',
        'module_macros'            => 'module_macros as module_macros',
        'module_critical_inverse'  => 'critical_inverse as module_critical_inverse',
        'module_warning_inverse'   => 'warning_inverse as module_warning_inverse',
    ];

    // module related field mappings 2/2 (output field => column for 'tagente_estado')
    // module_id_agent_modulo  is not in this list
    $estado_fields_to_columns_mapping = [
        'module_id_agent_state'     => 'id_agente_estado as module_id_agent_state',
        'module_data'               => 'datos as module_data',
        'module_timestamp'          => 'timestamp as module_timestamp',
        'module_state'              => 'estado as module_state',
        'module_last_try'           => 'last_try as module_last_try',
        'module_utimestamp'         => 'utimestamp as module_utimestamp',
        'module_current_interval'   => 'current_interval as module_current_interval',
        'module_running_by'         => 'running_by as module_running_by',
        'module_last_execution_try' => 'last_execution_try as module_last_execution_try',
        'module_status_changes'     => 'status_changes as module_status_changes',
        'module_last_status'        => 'last_status as module_last_status',
    ];

    if ($fields == false) {
        $fields = $module_properties_master_fields;
    }

    // construct column list to query for tagente, tagente_modulo, tagente_estado and alert-related tables
    $module_additional_columns = '';
    $estado_additional_columns = '';
    foreach ($fields as $fld) {
        if (array_key_exists($fld, $module_field_column_mapping)) {
            $module_additional_columns .= (', '.$module_field_column_mapping[$fld]);
        }

        if (array_key_exists($fld, $estado_fields_to_columns_mapping)) {
            $estado_additional_columns .= (', '.$estado_fields_to_columns_mapping[$fld]);
        }
    }

    // query to the DB
    $returnVar = [];
    $modules = db_get_all_rows_sql(
        'SELECT *
        FROM (SELECT id_agente_modulo as module_id_agent_modulo '.$module_additional_columns.'
                FROM tagente_modulo 
                WHERE id_agente_modulo = '.$id_module.') t1 
            INNER JOIN (SELECT id_agente_modulo as module_id_agent_modulo '.$estado_additional_columns.'
                FROM tagente_estado
                WHERE id_agente_modulo = '.$id_module.') t2
            ON t1.module_id_agent_modulo = t2.module_id_agent_modulo'
    );

    if ($modules === false) {
        $modules = [];
    }

    foreach ($modules as &$module) {
        $module = str_replace('\n', $returnReplace, $module);

        $module['type_row'] = 'module';

        if ($module['module_macros']) {
            $module['module_macros'] = base64_decode($module['module_macros']);
        }

        $returnVar[] = $module;
    }

    $data = [
        'type' => 'array',
        'data' => $returnVar,
    ];

    $data['list_index'] = $fields;

    returnData($returnType, $data, $separator);
}


function api_set_update_agent($id_agent, $thrash2, $other, $thrash3)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'AW')) {
        returnError('forbidden', 'string');
        return;
    }

    if (!util_api_check_agent_and_print_error($id_agent, 'string', 'AW')) {
        return;
    }

    $alias = $other['data'][0];
    $ip = $other['data'][1];
    $idParent = $other['data'][2];
    $idGroup = $other['data'][3];
    $cascadeProtection = $other['data'][4];
    $cascadeProtectionModule = $other['data'][5];
    $intervalSeconds = $other['data'][6];
    $idOS = $other['data'][7];
    $nameServer = $other['data'][8];
    $customId = $other['data'][9];
    $learningMode = $other['data'][10];
    $disabled = $other['data'][11];
    $description = $other['data'][12];

    // Check parameters.
    if ($idGroup == 0) {
        $agent_update_error = __('The agent could not be modified. For security reasons, use a group other than 0.');
        returnError($agent_update_error);
        return;
    }

    $server_name = db_get_value_sql('SELECT name FROM tserver WHERE BINARY name LIKE "'.$nameServer.'"');
    if ($alias == '' && $alias_as_name === 0) {
        returnError('No agent alias specified');
        return;
    } else if (db_get_value_sql('SELECT id_grupo FROM tgrupo WHERE id_grupo = '.$idGroup) === false) {
        returnError('The group doesn`t exist.');
        return;
    } else if (db_get_value_sql('SELECT id_os FROM tconfig_os WHERE id_os = '.$idOS) === false) {
        returnError('The OS doesn`t exist.');
        return;
    } else if ($server_name === false) {
        returnError('The '.get_product_name().' Server doesn`t exist.');
        return;
    }

    if ($cascadeProtection == 1) {
        if (($idParent != 0) && (db_get_value_sql(
            'SELECT id_agente_modulo
                                    FROM tagente_modulo
                                    WHERE id_agente = '.$idParent.' AND id_agente_modulo = '.$cascadeProtectionModule
        ) === false)
        ) {
                returnError('Cascade protection is not applied because it is not a parent module');
        }
    } else {
        $cascadeProtectionModule = 0;
    }

    // Check ACL group
    if (!check_acl($config['id_user'], $idGroup, 'AW')) {
        returnError('forbidden', 'string');
        return;
    }

    // Check if group allow more agents or have limit stablished.
    if (group_allow_more_agents($idGroup, true, 'update') === false) {
        returnError('Agent cannot be updated due to the maximum agent limit for this group');
        return;
    }

    // Check selected parent
    if ($idParent != 0) {
        $parentCheck = agents_check_access_agent($idParent);
        if ($parentCheck === null) {
            returnError('The parent agent does not exist.');
            return;
        }

        if ($parentCheck === false) {
            returnError('The user cannot access to parent agent.');
            return;
        }

        $agents_offspring = agents_get_offspring($id_agent);
        if (empty($agents_offspring) === false) {
            if (isset($agents_offspring[$idParent]) === true) {
                returnError('The parent cannot be a offspring');
                return;
            }
        }
    }

    $values_old = db_get_row_filter(
        'tagente',
        ['id_agente' => $id_agent],
        [
            'id_grupo',
            'disabled',
        ]
    );

    $return = db_process_sql_update(
        'tagente',
        [
            'alias'                     => $alias,
            'direccion'                 => $ip,
            'id_grupo'                  => $idGroup,
            'intervalo'                 => $intervalSeconds,
            'comentarios'               => $description,
            'modo'                      => $learningMode,
            'id_os'                     => $idOS,
            'disabled'                  => $disabled,
            'cascade_protection'        => $cascadeProtection,
            'cascade_protection_module' => $cascadeProtectionModule,
            'server_name'               => $nameServer,
            'id_parent'                 => $idParent,
            'custom_id'                 => $customId,
        ],
        ['id_agente' => $id_agent]
    );

    if ($return && !empty($ip)) {
        // register ip for this agent in 'taddress'
        agents_add_address($id_agent, $ip);
    }

    if ($return) {
        // Update config file
        if (isset($disabled) && $values_old['disabled'] != $disabled) {
            enterprise_hook(
                'config_agents_update_config_token',
                [
                    $id_agent,
                    'standby',
                    $disabled,
                ]
            );
        }
    }

    returnData(
        'string',
        [
            'type' => 'string',
            'data' => (int) ((bool) $return),
        ]
    );
}


/**
 * Update an agent by indicating a pair of field - value separated by comma.
 *
 * @param integer $id_agent        Id (or alias) agent to upadate.
 * @param boolean $use_agent_alias Use alias instead of id.
 * @param array   $params          Pair of parameter/value separated by comma. Available fields are:
 *  'alias',
 * 'direccion',
 * 'id_parent',
 * 'id_grupo',
 * 'cascade_protection',
 * 'cascade_protection_module',
 * 'intervalo',
 * 'id_os',
 * 'server_name',
 * 'custom_id',
 * 'modo',
 * 'disabled',
 * 'comentarios'
 *
 * eg . http://127.0.0.1/pandora_console/include/api.php?op=set&op2=update_agent_field&id=pandora&other=id_os,1|alias,pandora|direccion,192.168.10.16|id_parent,1cascade_protection,1|cascade_protection_module,1|intervalo,5||modo|3|&other_mode=url_encode_separator_|&apipass=1234&user=admin&pass=pandora
 */
function api_set_update_agent_field($id_agent, $use_agent_alias, $params)
{
    global $config;
    $return = false;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'AW')) {
        returnError('forbidden', 'string');
        return;
    }

     // Check the agent.
    if ((bool) $use_agent_alias === true) {
        $agents_by_alias = agents_get_agent_id_by_alias($id_agent);
        if (empty($agents_by_alias) === false) {
            foreach ($agents_by_alias as $agent) {
                if (agents_check_access_agent($agent['id_agente'], 'AW') === true) {
                    $agents[] = $agent['id_agente'];
                }
            }

            if (empty($agents) === true) {
                returnError('forbidden', 'string');
                return;
            }
        } else {
            returnError('Alias does not match any agent.');
            return;
        }
    } else {
        if (!util_api_check_agent_and_print_error($id_agent, 'string', 'AW')) {
            return;
        }

        $agents[] = $id_agent;
    }

     // Serialize the data for update.
    if ($params['type'] === 'array') {
        // Keys available to change.
        $available_fields = [
            'alias',
            'direccion',
            'id_parent',
            'id_grupo',
            'cascade_protection',
            'cascade_protection_module',
            'intervalo',
            'id_os',
            'server_name',
            'custom_id',
            'modo',
            'disabled',
            'comentarios',
        ];

        foreach ($params['data'] as $key_value) {
            list($key, $value) = explode(',', $key_value, 2);
            if (in_array($key, $available_fields) === true) {
                $fields[$key] = $value;
            }
        }
    }

    if (empty($fields) === true) {
        returnError('Selected field not available. Please, select one the fields avove');
        return;
    }

    // Check fields.
    foreach ($fields as $field => $data) {
        switch ($field) {
            case 'alias':
                if (empty($data)) {
                    returnError('No agent alias specified');
                    return;
                }
            break;

            case 'id_grupo':
                if (db_get_value_sql('SELECT  FROM tgrupo WHERE id_grupo = '.$data) === false) {
                    returnError('The group doesn`t exist.');
                    return;
                }
            break;

            case 'id_os':
                if (db_get_value_sql('SELECT id_os FROM tconfig_os WHERE id_os = '.$data) === false) {
                    returnError('The OS doesn`t exist.');
                    return;
                }
            break;

            case 'server_name':
                $server_name = db_get_value_sql('SELECT name FROM tserver WHERE BINARY name LIKE "'.$data.'"');
                if ($server_name === false) {
                        returnError('The server doesn`t exist.');
                     return;
                }
            break;

            case 'cascade_protection':
                if ($data == 1) {
                    if (($field['id_parent'] != 0) && (db_get_value_sql(
                        'SELECT id_agente_modulo
                            FROM tagente_modulo
                            WHERE id_agente = '.$fields['id_parent'].' AND id_agente_modulo = '.$fields['cascade_protection_module']
                    ) === false)
                    ) {
                            returnError('Cascade protection is not applied because it is not a parent module');
                    }
                } else {
                    unset($fields['cascade_protection']);
                }
            break;

            case 'id_grupo':
                // Check ACL group.
                if (!check_acl($config['id_user'], $data, 'AW')) {
                    returnError('forbidden', 'string');
                    return;
                }

                if ($data == 0) {
                    $agent_update_error = 'The agent could not be modified. For security reasons, use a group other than 0.';
                    returnError($agent_update_error);
                    return;
                }
            break;

            case 'id_parent':
                $parentCheck = agents_check_access_agent($data);
                if (is_null($parentCheck) === true) {
                    returnError('The parent agent does not exist.');
                    return;
                }

                if ($parentCheck === false) {
                    returnError('The user cannot access to parent agent.');
                    return;
                }

                $agents_offspring = agents_get_offspring($id_agent);
                if (empty($agents_offspring) === false) {
                    if (isset($agents_offspring[$data]) === true) {
                        returnError('The parent cannot be a offspring');
                        return;
                    }
                }
            break;

            default:
                // Default empty.
            break;
        }
    }

        // Var applied in case there is more than one agent.
        $return = false;
        $applied = 0;
    foreach ($agents as $agent) {
        $values_old = db_get_row_filter(
            'tagente',
            ['id_agente' => $agent],
            [
                'id_grupo',
                'disabled',
            ]
        );

        $return = db_process_sql_update(
            'tagente',
            $fields,
            ['id_agente' => $agent]
        );

        if ((count($agents) > 1) && $return !== 0) {
            $applied += 1;
        }

        if ($return && !isset($field['direccion'])) {
            // register ip for this agent in 'taddress'.
            agents_add_address($agent, $field['direccion']);
        }

        if ($return) {
            // Update config file
            if (isset($field['disabled']) && $values_old['disabled'] != $field['disabled']) {
                enterprise_hook(
                    'config_agents_update_config_token',
                    [
                        $agent,
                        'standby',
                        $field['disabled'],
                    ]
                );
            }
        }
    }

    if (count($agents) > 1) {
        returnData(
            'string',
            [
                'type' => 'string',
                'data' => __('Updated %d/%d agents', $applied, count($agents)),
            ]
        );
    } else {
        returnData(
            'string',
            [
                'type' => 'string',
                'data' => __('Agent updated.'),
            ]
        );
    }

}


/**
 * Create a new agent, and print the id for new agent.
 *
 * @param $id_node Id_node target (if metaconsole)
 * @param $thrash2 Don't use.
 * @param array                                   $other it's array, $other as param is <agent_name>;<ip>;<id_parent>;<id_group>;
 *                                    <cascade_protection>;<interval_sec>;<id_os>;<id_server>;<custom_id>;<learning_mode>;<disabled>;<description> in this order
 *                                    and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *                                    example:
 *
 *                                    api.php?op=set&op2=new_agent&other=pepito|1.1.1.1|0|4|0|30|8|10||0|0|nose%20nose&other_mode=url_encode_separator_|
 *
 * @param $thrash3 Don't use.
 */
function api_set_new_agent($id_node, $thrash2, $other, $trhash3)
{
    global $config;

    if (!check_acl($config['id_user'], 0, 'AW')) {
        returnError('forbidden', 'string');
        return;
    }

    if ((int) $other['data'][3] == 0) {
        returnError('For security reasons, the agent was not created. Use a group other than 0.');
        return;
    }

    try {
        $agent = new Agent();

        if (is_metaconsole() === true) {
            if ($id_node <= 0) {
                throw new Exception('No node id specified');
            }

            $node = new Node($id_node);
            $id_agente = $node->callApi(
                'new_agent',
                'set',
                null,
                null,
                $other['data'],
                null
            );
        } else {
            $alias                     = io_safe_input(
                trim(
                    preg_replace(
                        '/[\/\\\|%#&$]/',
                        '',
                        preg_replace(
                            '/x20;/',
                            ' ',
                            $other['data'][0]
                        )
                    )
                )
            );

            $direccion_agente          = io_safe_input($other['data'][1]);
            $id_parent                 = (int) $other['data'][2];
            $grupo                     = (int) $other['data'][3];
            $cascade_protection        = (int) $other['data'][4];
            $cascade_protection_module = (int) $other['data'][5];
            $intervalo                 = (string) $other['data'][6];
            $id_os                     = (int) $other['data'][7];
            $server_name               = (string) $other['data'][8];
            $custom_id                 = (string) $other['data'][9];
            $modo                      = (int) $other['data'][10];
            $disabled                  = (int) $other['data'][11];
            $comentarios               = (string) html_entity_decode($other['data'][12]);
            $alias_as_name             = (int) $other['data'][13];
            $update_module_count       = (int) $config['metaconsole_agent_cache'] == 1;

            $agent->nombre($alias);
            $agent->alias($alias);
            $agent->alias_as_name($alias_as_name);
            $agent->direccion($direccion_agente);
            $agent->id_grupo($grupo);
            $agent->intervalo($intervalo);
            $agent->comentarios($comentarios);
            $agent->modo($modo);
            $agent->id_os($id_os);
            $agent->disabled($disabled);
            $agent->cascade_protection($cascade_protection);
            $agent->cascade_protection_module($cascade_protection_module);
            $agent->server_name($server_name);
            $agent->id_parent($id_parent);
            $agent->custom_id($custom_id);
            $agent->timezone_offset(0);
            $agent->update_module_count($update_module_count);

            if ($cascade_protection == 1) {
                if ($id_parent != 0) {
                    try {
                        $parent = new Agent($id_parent);
                    } catch (\Exception $e) {
                        returnError('Cascade protection is not applied because it is not a parent module.');
                    }
                }
            }

            $server_name = db_get_value_sql('SELECT name FROM tserver WHERE BINARY name LIKE "'.$server_name.'"');

            // Check if agent exists (BUG WC-50518-2).
            if ($alias == '' && $alias_as_name === 0) {
                returnError('No agent alias specified');
            } else if (agents_get_agent_id($nombre_agente)) {
                returnError('The agent name already exists in DB.');
            } else if (db_get_value_sql('SELECT id_grupo FROM tgrupo WHERE id_grupo = '.$grupo) === false) {
                returnError('The group does not exist.');
            } else if (group_allow_more_agents($grupo, true, 'create') === false) {
                returnError('Agent cannot be created due to the maximum agent limit for this group');
            } else if (db_get_value_sql('SELECT id_os FROM tconfig_os WHERE id_os = '.$id_os) === false) {
                returnError('The OS does not exist.');
            } else if ($server_name === false) {
                returnError('The '.get_product_name().' Server does not exist.');
            } else {
                if ($alias_as_name === 1) {
                    $exists_alias  = db_get_row_sql('SELECT nombre FROM tagente WHERE nombre = "'.$alias.'"');
                    $nombre_agente = $alias;
                }

                $exists_ip = false;

                if ($config['unique_ip'] && $direccion_agente != '') {
                    $exists_ip = db_get_row_sql('SELECT direccion FROM tagente WHERE direccion = "'.$direccion_agente.'"');
                }

                $agent->save((bool) $alias_as_name);
                $id_agente = $agent->id_agente();
                $agent->updateFromCache();
            }
        }

        returnData(
            'string',
            [
                'type' => 'string',
                'data' => $id_agente,
            ]
        );
    } catch (\Exception $e) {
        returnError($e->getMessage());
        return;
    }
}


function api_set_create_os($thrash1, $thrash2, $other, $thrash3)
{
    global $config;

    if (!check_acl($config['id_user'], 0, 'AW')) {
        returnError('forbidden', 'string');
        return;
    }

    $idk = get_header('idk');
    if (is_management_allowed($idk) === false) {
        returnError('centralized');
        exit;
    }

    $values = [];

    $values['name'] = $other['data'][0];
    $values['description'] = $other['data'][1];

    if (($other['data'][2] !== 0) && ($other['data'][2] != '')) {
        $values['icon_name'] = $other['data'][2];
    }

    $resultOrId = false;
    if ($other['data'][0] != '') {
        $resultOrId = db_process_sql_insert('tconfig_os', $values);

        if ($resultOrId) {
            echo __('Success creating OS');
        } else {
            echo __('Could not create OS');
        }
    }

}


function api_set_update_os($id_os, $thrash2, $other, $thrash3)
{
    global $config;

    if (!check_acl($config['id_user'], 0, 'AW')) {
        returnError('forbidden', 'string');
        return;
    }

    $idk = get_header('idk');
    if (is_management_allowed($idk) === false) {
        returnError('centralized');
        exit;
    }

    $values = [];
    $values['name'] = $other['data'][0];
    $values['description'] = $other['data'][1];

    if (($other['data'][2] !== 0) && ($other['data'][2] != '')) {
        $values['icon_name'] = $other['data'][2];
        ;
    }

    $result = false;

    if ($other['data'][0] != '') {
        if (db_process_sql_update('tconfig_os', $values, ['id_os' => $id_os])) {
            echo __('Success updating OS');
        } else {
            echo __('Could not update OS');
        }
    }

}


/**
 * Creates a custom field
 *
 * @param string  $name          Custom field name
 * @param boolean $display_front Flag to display custom field in agent's operation view
 */
function api_set_create_custom_field($t1, $t2, $other, $returnType)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'PM')) {
        returnError('forbidden', $returnType);
        return;
    }

    if ($other['type'] == 'string') {
        returnError('Parameter error.');
        return;
    } else if ($other['type'] == 'array') {
        $name = '';

        if ($other['data'][0] != '') {
            $name = $other['data'][0];
        } else {
            returnError('Custom field name required');
            return;
        }

        $display_front = 0;

        if ($other['data'][1] != '') {
            $display_front = $other['data'][1];
        } else {
            returnError('Custom field display flag required');
            return;
        }

        $is_password_type = 0;

        if ($other['data'][2] != '') {
            $is_password_type = $other['data'][2];
        } else {
            returnError('Custom field is password type required');
            return;
        }

        $result = db_process_sql_insert(
            'tagent_custom_fields',
            [
                'name'             => $name,
                'display_on_front' => $display_front,
                'is_password_type' => $is_password_type,
            ]
        );

        $data['type'] = 'string';
        $data['data'] = $result;

        returnData('string', $data);
    }
}


/**
 * Returns ID of custom field zero if not exists
 *
 * @param string $name Custom field name
 */
function api_get_custom_field_id($t1, $t2, $other, $returnType)
{
    if (defined('METACONSOLE')) {
        return;
    }

    $name = $other['data'][0];
    $id = db_get_value('id_field', 'tagent_custom_fields', 'name', $name);

    if ($id === false) {
        returnError('id_not_found', $returnType);
        return;
    }

    $data['type'] = 'string';
    $data['data'] = $id;
    returnData('string', $data);
}


/**
 * Delete an agent with the name as parameter.
 *
 * @param string            $id Name of agent to delete.
 * @param $thrash1 Don't use.
 * @param $thrast2 Don't use.
 * @param $thrash3 Don't use.
 */
function api_set_delete_agent($id, $thrash1, $other, $returnType)
{
    global $config;

    if (empty($returnType)) {
        $returnType = 'string';
    }

    $agent_by_alias = false;

    if ($other['data'][0] === '1') {
        $agent_by_alias = true;
    }

    if (is_metaconsole()) {
        if (!check_acl($config['id_user'], 0, 'PM')
            && !check_acl($config['id_user'], 0, 'AW')
        ) {
            returnError('forbidden', 'string');
            return;
        }

        $servers = db_get_all_rows_sql(
            'SELECT *
            FROM tmetaconsole_setup
                WHERE disabled = 0'
        );

        if ($servers === false) {
            $servers = [];
        }

        foreach ($servers as $server) {
            if (metaconsole_connect($server) == NOERR) {
                if ($agent_by_alias) {
                    $idAgent = agents_get_agent_id_by_alias($id);
                } else {
                    $idAgent[0] = agents_get_agent_id($id, true);
                }

                if (empty($idAgent) === false) {
                    $result = agents_delete_agent($idAgent[0], true);
                }

                metaconsole_restore_db();
            }
        }
    } else {
        // Delete only if the centralised mode is disabled.
        $idk = get_header('idk');
        if (is_management_allowed($idk) === false) {
            returnError('centralized');
            exit;
        }

        // Support for Pandora Enterprise.
        if (license_free() === false) {
            define('PANDORA_ENTERPRISE', true);
        }

        if ($agent_by_alias) {
            $idsAgents = agents_get_agent_id_by_alias(io_safe_input($id));
            foreach ($idsAgents as $id) {
                if (!util_api_check_agent_and_print_error($id['id_agente'], 'string', 'AD')) {
                    continue;
                }

                $result = agents_delete_agent($id['id_agente'], true);

                if (!$result) {
                    break;
                }
            }
        } else {
            $idAgent = agents_get_agent_id($id, false);
            if (!util_api_check_agent_and_print_error($idAgent, 'string', 'AD')) {
                return;
            }

            $result = agents_delete_agent($idAgent, false);
        }
    }

    if ($result === false) {
        if ($returnType !== 'string') {
            return false;
        }

        returnError('The agent could not be deleted', $returnType);
    } else {
        if ($returnType !== 'string') {
            return true;
        }

        returnData($returnType, ['type' => 'string', 'data' => __('The agent was successfully deleted')]);
    }
}


/**
 * Get all agents, and print all the result like a csv or other type for example json.
 *
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array             $other it's array, $other as param are the filters available <filter_so>;<filter_group>;<filter_modules_states>;<filter_name>;<filter_policy>;<csv_separator><recursion> in this order
 *              and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *              example for CSV:
 *
 *              api.php?op=get&op2=all_agents&return_type=csv&other=1|2|warning|j|2|~&other_mode=url_encode_separator_|
 *
 *              example for JSON:
 *
 *                 api.php?op=get&op2=all_agents&return_type=json&other=1|2|warning|j|2|~&other_mode=url_encode_separator_|
 *
 * @param $returnType.
 */
function api_get_all_agents($thrash1, $thrash2, $other, $returnType)
{
    global $config;

    $where = '';

    // Error if user cannot read agents.
    if (!check_acl($config['id_user'], 0, 'AR')) {
        returnError('forbidden', $returnType);
        return;
    }

    $groups = '1 = 1';
    if (!is_user_admin($config['id_user'])) {
        $user_groups = implode(',', array_keys(users_get_groups()));
        $groups = "(id_grupo IN ($user_groups) OR id_group IN ($user_groups))";
    }

    if (isset($other['data'][0])) {
        // Filter by SO.
        if ($other['data'][0] != '') {
            $where .= ' AND tconfig_os.id_os = '.$other['data'][0];
        }
    }

    if (isset($other['data'][1])) {
        // Filter by group.
        if ($other['data'][1] != '') {
            $ag_groups = $other['data'][1];
            // Recursion.
            if ($other['data'][6] === '1') {
                $ag_groups = groups_get_children_ids($ag_groups, true);
            }

            $ag_groups = implode(',', (array) $ag_groups);

            $where .= ' AND (id_grupo IN ('.$ag_groups.') OR id_group IN ('.$ag_groups.'))';
        }
    }

    if (isset($other['data'][3])) {
        // Filter by alias
        if ($other['data'][3] != '') {
            $where .= " AND alias LIKE ('%".$other['data'][3]."%')";
        }
    }

    if (isset($other['data'][4])) {
        // Filter by policy
        if ($other['data'][4] != '') {
            $filter_by_policy = enterprise_hook('policies_get_filter_by_agent', [$other['data'][4]]);
            if ($filter_by_policy !== ENTERPRISE_NOT_HOOK) {
                $where .= $filter_by_policy;
            }
        }
    }

    if (!isset($other['data'][5])) {
        $separator = ';';
        // by default
    } else {
        $separator = $other['data'][5];
    }

    // Initialization of array
    $result_agents = [];
    // Filter by state
    if (is_metaconsole()) {
        $sql = 'SELECT id_agente, alias, direccion, comentarios,
			tconfig_os.name, url_address, nombre
		FROM tconfig_os, tmetaconsole_agent
		LEFT JOIN tmetaconsole_agent_secondary_group
			ON tmetaconsole_agent.id_agente = tmetaconsole_agent_secondary_group.id_agent
		WHERE tmetaconsole_agent.id_os = tconfig_os.id_os
			AND disabled = 0 '.$where.' AND '.$groups;
    } else {
        $sql = 'SELECT id_agente, alias, direccion, comentarios,
                tconfig_os.name, url_address, nombre
            FROM tconfig_os, tagente
            LEFT JOIN tagent_secondary_group
                ON tagente.id_agente = tagent_secondary_group.id_agent
            WHERE tagente.id_os = tconfig_os.id_os
                AND disabled = 0 '.$where.' AND '.$groups;
    }

    // Group by agent
    $sql .= ' GROUP BY id_agente';

    $all_agents = db_get_all_rows_sql($sql);

    // Filter by status: unknown, warning, critical, without modules
    if (isset($other['data'][2])) {
        if ($other['data'][2] != '') {
            foreach ($all_agents as $agent) {
                $filter_modules['id_agente'] = $agent['id_agente'];
                $filter_modules['disabled'] = 0;
                $filter_modules['delete_pending'] = 0;
                $modules = db_get_all_rows_filter(
                    'tagente_modulo',
                    $filter_modules,
                    'id_agente_modulo'
                );
                $result_modules = [];
                // Skip non init modules
                foreach ($modules as $module) {
                    if (modules_get_agentmodule_is_init($module['id_agente_modulo'])) {
                        $result_modules[] = $module;
                    }
                }

                // Without modules NO_MODULES
                if ($other['data'][2] == 'no_modules') {
                    if (empty($result_modules) and $other['data'][2] == 'no_modules') {
                        $result_agents[] = $agent;
                    }
                }
                // filter by NORMAL, WARNING, CRITICAL, UNKNOWN, ALERT_FIRED
                else {
                    $status = agents_get_status($agent['id_agente'], true);
                    // Filter by status
                    switch ($other['data'][2]) {
                        case 'warning':
                            if ($status == AGENT_MODULE_STATUS_WARNING || $status == AGENT_MODULE_STATUS_WARNING_ALERT) {
                                $result_agents[] = $agent;
                            }
                        break;

                        case 'critical':
                            if ($status == AGENT_MODULE_STATUS_CRITICAL_BAD || $status == AGENT_MODULE_STATUS_CRITICAL_ALERT) {
                                $result_agents[] = $agent;
                            }
                        break;

                        case 'unknown':
                            if ($status == AGENT_MODULE_STATUS_UNKNOWN) {
                                $result_agents[] = $agent;
                            }
                        break;

                        case 'normal':
                            if ($status == AGENT_MODULE_STATUS_NORMAL || $status == AGENT_MODULE_STATUS_NORMAL_ALERT) {
                                $result_agents[] = $agent;
                            }
                        break;

                        case 'alert_fired':
                            if ($status == AGENT_STATUS_ALERT_FIRED || $status == AGENT_MODULE_STATUS_WARNING_ALERT || $status == AGENT_MODULE_STATUS_CRITICAL_ALERT || $status == AGENT_MODULE_STATUS_NORMAL_ALERT) {
                                $result_agents[] = $agent;
                            }
                        break;
                    }
                }
            }
        } else {
            $result_agents = $all_agents;
        }
    } else {
        $result_agents = $all_agents;
    }

    if (empty($returnType)) {
        $returnType = 'string';
    }

    if (empty($separator)) {
        $separator = ';';
    }

    foreach ($result_agents as $key => $value) {
        $result_agents[$key]['status'] = agents_get_status($result_agents[$key]['id_agente'], true);
    }

    if (count($result_agents) > 0 and $result_agents !== false) {
        $data = [
            'type' => 'array',
            'data' => $result_agents,
        ];
        returnData($returnType, $data, $separator);
    } else {
        returnError('No agents retrieved.');
    }
}


/**
 * Get modules for an agent, and print all the result like a csv.
 *
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array             $other it's array, $other as param are the filters available <id_agent> in this order
 *              and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *              example:
 *
 *              api.php?op=get&op2=agents_modules&return_type=csv&other=14&other_mode=url_encode_separator_|
 *
 * @param $thrash3 Don't use.
 */
function api_get_agent_modules($thrash1, $thrash2, $other, $thrash3)
{
    if (defined('METACONSOLE')) {
        return;
    }

    if (!util_api_check_agent_and_print_error($other['data'][0], 'csv')) {
        return;
    }

    $sql = sprintf(
        'SELECT id_agente, id_agente_modulo, nombre 
        FROM tagente_modulo
        WHERE id_agente = %d AND disabled = 0
            AND delete_pending = 0',
        $other['data'][0]
    );

    $all_modules = db_get_all_rows_sql($sql);

    if (count($all_modules) > 0 and $all_modules !== false) {
        $data = [
            'type' => 'array',
            'data' => $all_modules,
        ];

        returnData('csv', $data, ';');
    } else {
        returnError('No modules retrieved.');
    }
}


function api_get_db_uncompress_module_data($id_agente_modulo, $tstart, $other)
{
    global $config;

    if (!isset($id_agente_modulo)) {
        return false;
    }

    if ((!isset($tstart)) || ($tstart === false)) {
        // Return data from the begining
        // $tstart = 0;
        $tstart = 0;
    }

    $tend = $other['data'];
    if ((!isset($tend)) || ($tend === false)) {
        // Return data until now
        $tend = time();
    }

    if ($tstart > $tend) {
        return false;
    }

    $search_historydb = false;
    $table = 'tagente_datos';

    $module = modules_get_agentmodule($id_agente_modulo);

    if ($module === false) {
        // module not exists
        return false;
    }

    $module_type = $module['id_tipo_modulo'];
    $module_type_str = modules_get_type_name($module_type);
    if (strstr($module_type_str, 'string') !== false) {
        $table = 'tagente_datos_string';
    }

    // Get first available utimestamp in active DB
    $query  = " SELECT utimestamp, datos FROM $table ";
    $query .= " WHERE id_agente_modulo=$id_agente_modulo AND utimestamp < $tstart";
    $query .= ' ORDER BY utimestamp DESC LIMIT 1';

    $ret = db_get_all_rows_sql($query, $search_historydb);

    if (( $ret === false ) || (( isset($ret[0]['utimestamp']) && ($ret[0]['utimestamp'] > $tstart )))) {
        // Value older than first retrieved from active DB
        $search_historydb = true;

        $ret = db_get_all_rows_sql($query, $search_historydb);
    } else {
        $first_data['utimestamp'] = $ret[0]['utimestamp'];
        $first_data['datos']      = $ret[0]['datos'];
    }

    if (( $ret === false ) || (( isset($ret[0]['utimestamp']) && ($ret[0]['utimestamp'] > $tstart )))) {
        // No previous data. -> not init
        // Avoid false unknown status
        $first_data['utimestamp'] = time();
        $first_data['datos']      = false;
    } else {
        $first_data['utimestamp'] = $ret[0]['utimestamp'];
        $first_data['datos']      = $ret[0]['datos'];
    }

    $query  = " SELECT utimestamp, datos FROM $table ";
    $query .= " WHERE id_agente_modulo=$id_agente_modulo AND utimestamp >= $tstart AND utimestamp <= $tend";
    $query .= ' ORDER BY utimestamp ASC';

    // Retrieve all data from module in given range
    $raw_data = db_get_all_rows_sql($query, $search_historydb);

    if (($raw_data === false) && ($ret === false)) {
        // No data
        return false;
    }

    // Retrieve going unknown events in range
    $unknown_events = db_get_module_ranges_unknown($id_agente_modulo, $tstart, $tend);

    // Retrieve module_interval to build the template
    $module_interval = modules_get_interval($id_agente_modulo);
    $slice_size = $module_interval;

    // We'll return a bidimensional array
    // Structure returned: schema:
    //
    // uncompressed_data =>
    // pool_id (int)
    // utimestamp (start of current slice)
    // data
    // array
    // utimestamp
    // datos
    $return = [];
    // Point current_timestamp to begin of the set and initialize flags
    $current_timestamp   = $tstart;
    $last_inserted_value = $first_data['datos'];
    $last_timestamp      = $first_data['utimestamp'];
    $data_found          = 0;

    // Build template
    $pool_id = 0;
    $now = time();

    $in_unknown_status = 0;
    if (is_array($unknown_events)) {
        $current_unknown = array_shift($unknown_events);
    }

    while ($current_timestamp < $tend) {
        $expected_data_generated = 0;

        $return[$pool_id]['data'] = [];
        $tmp_data   = [];
        $data_found = 0;

        if (is_array($unknown_events)) {
            $i = 0;
            while ($current_timestamp >= $unknown_events[$i]['time_to']) {
                // Skip unknown events in past
                array_splice($unknown_events, $i, 1);
                $i++;
                if (!isset($unknown_events[$i])) {
                    break;
                }
            }

            if (isset($current_unknown)) {
                // check if recovered from unknown status
                if (is_array($unknown_events) && isset($current_unknown)) {
                    if ((($current_timestamp + $slice_size) > $current_unknown['time_to'])
                        && ($current_timestamp < $current_unknown['time_to'])
                        && ($in_unknown_status == 1)
                    ) {
                        // Recovered from unknown
                        if (($current_unknown['time_to'] > $current_timestamp)
                            && ($expected_data_generated == 0)
                        ) {
                            // also add the "expected" data
                            $tmp_data['utimestamp'] = $current_timestamp;
                            if ($in_unknown_status == 1) {
                                $tmp_data['datos'] = null;
                            } else {
                                $tmp_data['datos'] = $last_inserted_value;
                            }

                            $return[$pool_id]['utimestamp'] = $current_timestamp;
                            array_push($return[$pool_id]['data'], $tmp_data);
                            $expected_data_generated = 1;
                        }

                        $tmp_data['utimestamp'] = $current_unknown['time_to'];
                        $tmp_data['datos']      = $last_inserted_value;
                        // debug purpose
                        $tmp_data['obs'] = 'event recovery data';

                        $return[$pool_id]['utimestamp'] = $current_timestamp;
                        array_push($return[$pool_id]['data'], $tmp_data);
                        $data_found = 1;
                        $in_unknown_status = 0;
                    }

                    if ((($current_timestamp + $slice_size) > $current_unknown['time_from'])
                        && (($current_timestamp + $slice_size) < $current_unknown['time_to'])
                        && ($in_unknown_status == 0)
                    ) {
                        // Add unknown state detected
                        if ($current_unknown['time_from'] < ($current_timestamp + $slice_size)) {
                            if (($current_unknown['time_from'] > $current_timestamp)
                                && ($expected_data_generated == 0)
                            ) {
                                // also add the "expected" data
                                $tmp_data['utimestamp'] = $current_timestamp;
                                if ($in_unknown_status == 1) {
                                    $tmp_data['datos'] = null;
                                } else {
                                    $tmp_data['datos'] = $last_inserted_value;
                                }

                                $return[$pool_id]['utimestamp'] = $current_timestamp;
                                array_push($return[$pool_id]['data'], $tmp_data);
                                $expected_data_generated = 1;
                            }

                            $tmp_data['utimestamp'] = $current_unknown['time_from'];
                            $tmp_data['datos']      = null;
                            // debug purpose
                            $tmp_data['obs'] = 'event data';
                            $return[$pool_id]['utimestamp'] = $current_timestamp;
                            array_push($return[$pool_id]['data'], $tmp_data);
                            $data_found = 1;
                        }

                        $in_unknown_status = 1;
                    }

                    if (($in_unknown_status == 0) && ($current_timestamp >= $current_unknown['time_to'])) {
                        $current_unknown = array_shift($unknown_events);
                    }
                }
            } //end if
        }

        // Search for data
        $i = 0;

        if (is_array($raw_data)) {
            foreach ($raw_data as $data) {
                if (($data['utimestamp'] >= $current_timestamp)
                    && ($data['utimestamp'] < ($current_timestamp + $slice_size))
                ) {
                    // Data in block, push in, and remove from $raw_data (processed)
                    if (($data['utimestamp'] > $current_timestamp)
                        && ($expected_data_generated == 0)
                    ) {
                        // also add the "expected" data
                        $tmp_data['utimestamp'] = $current_timestamp;
                        if ($in_unknown_status == 1) {
                            $tmp_data['datos'] = null;
                        } else {
                            $tmp_data['datos'] = $last_inserted_value;
                        }

                        $tmp_data['obs'] = 'expected data';
                        $return[$pool_id]['utimestamp'] = $current_timestamp;
                        array_push($return[$pool_id]['data'], $tmp_data);
                        $expected_data_generated = 1;
                    }

                    $tmp_data['utimestamp'] = intval($data['utimestamp']);
                    $tmp_data['datos']      = $data['datos'];
                    // debug purpose
                    $tmp_data['obs'] = 'real data';

                    $return[$pool_id]['utimestamp'] = $current_timestamp;
                    array_push($return[$pool_id]['data'], $tmp_data);

                    $last_inserted_value = $data['datos'];
                    $last_timestamp      = intval($data['utimestamp']);

                    unset($raw_data[$i]);
                    $data_found = 1;
                    $in_unknown_status = 0;
                } else if ($data['utimestamp'] > ($current_timestamp + $slice_size)) {
                    // Data in future, stop searching new ones
                    break;
                }
            }

            $i++;
        }

        if ($data_found == 0) {
            // No data found, lug the last_value until SECONDS_1DAY + 2*modules_get_interval
            // UNKNOWN!
            if (($current_timestamp > $now) || (($current_timestamp - $last_timestamp) > (SECONDS_1DAY + 2 * $module_interval))) {
                if (isset($last_inserted_value)) {
                    // unhandled unknown status control
                    $unhandled_time_unknown = ($current_timestamp - (SECONDS_1DAY + 2 * $module_interval) - $last_timestamp);
                    if ($unhandled_time_unknown > 0) {
                        // unhandled unknown status detected. Add to previous pool
                        $tmp_data['utimestamp'] = (intval($last_timestamp) + (SECONDS_1DAY + 2 * $module_interval));
                        $tmp_data['datos']      = null;
                        // debug purpose
                        $tmp_data['obs'] = 'unknown extra';
                        // add to previous pool if needed
                        if (isset($return[($pool_id - 1)])) {
                            array_push($return[($pool_id - 1)]['data'], $tmp_data);
                        }
                    }
                }

                $last_inserted_value = null;
            }

            $tmp_data['utimestamp'] = $current_timestamp;

            if ($in_unknown_status == 1) {
                $tmp_data['datos'] = null;
            } else {
                $tmp_data['datos'] = $last_inserted_value;
            }

            // debug purpose
            $tmp_data['obs'] = 'virtual data';

            $return[$pool_id]['utimestamp'] = $current_timestamp;
            array_push($return[$pool_id]['data'], $tmp_data);
        }

        $pool_id++;
        $current_timestamp += $slice_size;
    }

    $data = [
        'type' => 'array',
        'data' => $return,
    ];
    returnData('json', $return, ';');
}


/**
 * Get modules id for an agent, and print the result like a csv.
 *
 * @param $id Id of agent.
 * @param array             $name name of module.
 * @param $thrash1 Don't use.
 *
 *  pi.php?op=get&op2=module_id&id=5&other=Host%20Alive&apipass=1234&user=admin&pass=pandora
 *
 * @param $thrash3 Don't use.
 */
function api_get_module_id($id, $thrash1, $name, $thrash3)
{
    if (defined('METACONSOLE')) {
        return;
    }

    if (!util_api_check_agent_and_print_error($id, 'csv')) {
        return;
    }

    $sql = sprintf(
        'SELECT id_agente_modulo
        FROM tagente_modulo WHERE id_agente = %d
        AND nombre = "%s" AND disabled = 0
        AND delete_pending = 0',
        $id,
        $name['data']
    );

    $module_id = db_get_all_rows_sql($sql);

    if (count($module_id) > 0 and $module_id !== false) {
        $data = [
            'type' => 'array',
            'data' => $module_id,
        ];
        returnData('csv', $data, ';');
    } else {
        returnError('Does not exist module or agent');
    }
}


/**
 * Retrieves custom_id from given module_id.
 *
 * @param integer $id Module id.
 *
 * @return void
 */
function api_get_module_custom_id($id)
{
    if (is_metaconsole()) {
        return;
    }

    try {
        $module = new Module($id);
        if (!util_api_check_agent_and_print_error(
            $module->id_agente(),
            'json'
        )
        ) {
            return;
        }
    } catch (Exception $e) {
        returnError('id_not_found', 'json');
    }

    returnData('json', $module->custom_id());
}


/**
 * Retrieves custom_id from given module_id.
 *
 * @param integer $id Module id.
 *
 * @return void
 */
function api_set_module_custom_id($id, $value)
{
    if (is_metaconsole()) {
        return;
    }

    try {
        $module = new Module($id);
        if (!util_api_check_agent_and_print_error(
            $module->id_agente(),
            'json',
            'AW'
        )
        ) {
            return;
        }

        $module->custom_id($value);
        $module->save();
    } catch (Exception $e) {
        returnError('id_not_found', 'json');
    }

    returnData('json', ['type' => 'string', 'data' => $module->custom_id()]);
}


/**
 * Get modules for an agent, and print all the result like a csv.
 *
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array             $other it's array, $other as param are the filters available <id_agent> in this order
 *              and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *              example:
 *
 *              api.php?op=get&op2=group_agent&return_type=csv&other=14&other_mode=url_encode_separator_|
 *
 * @param $thrash3 Don't use.
 */
function api_get_group_agent($thrash1, $thrash2, $other, $thrash3)
{
    if (defined('METACONSOLE')) {
        return;
    }

    $sql = sprintf(
        'SELECT groups.nombre nombre 
        FROM tagente agents, tgrupo groups
        WHERE id_agente = %d AND agents.disabled = 0
            AND groups.disabled = 0
            AND agents.id_grupo = groups.id_grupo',
        $other['data'][0]
    );

    $group_names = db_get_all_rows_sql($sql);

    if (count($group_names) > 0 and $group_names !== false) {
        $data = [
            'type' => 'array',
            'data' => $group_names,
        ];

        returnData('csv', $data, ';');
    } else {
        returnError('error_group_agent', 'No groups retrieved.');
    }
}


/**
 * Get name group for an agent, and print all the result like a csv.
 *
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array             $other it's array, $other as param are the filters available <name_agent> in this order
 *              and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *              example:
 *
 *              api.php?op=get&op2=group_agent&return_type=csv&other=Pepito&other_mode=url_encode_separator_|
 *
 * @param $thrash3 Don't use.
 */
function api_get_group_agent_by_name($thrash1, $thrash2, $other, $thrash3)
{
    $group_names = [];

    if (is_metaconsole()) {
        $servers = db_get_all_rows_sql(
            'SELECT *
            FROM tmetaconsole_setup
                WHERE disabled = 0'
        );

        if ($servers === false) {
            $servers = [];
        }

        foreach ($servers as $server) {
            if (metaconsole_connect($server) == NOERR) {
                $agent_id = agents_get_agent_id($other['data'][0], true);

                if ($agent_id) {
                    $sql = sprintf(
                        'SELECT groups.nombre nombre 
                        FROM tagente agents, tgrupo groups
                        WHERE id_agente = %d 
                            AND agents.id_grupo = groups.id_grupo',
                        $agent_id
                    );
                    $group_server_names = db_get_all_rows_sql($sql);

                    if ($group_server_names) {
                        foreach ($group_server_names as $group_server_name) {
                            $group_names[] = $group_server_name;
                        }
                    }
                }
            }

            metaconsole_restore_db();
        }
    } else {
        $agent_id = agents_get_agent_id($other['data'][0], true);
        if (!util_api_check_agent_and_print_error($agent_id, 'csv')) {
            return;
        }

        $sql = sprintf(
            'SELECT groups.nombre nombre 
            FROM tagente agents, tgrupo groups
            WHERE id_agente = %d 
                AND agents.id_grupo = groups.id_grupo',
            $agent_id
        );
        $group_names = db_get_all_rows_sql($sql);
    }

    if (count($group_names) > 0 and $group_names !== false) {
        $data = [
            'type' => 'array',
            'data' => $group_names,
        ];

        returnData('csv', $data, ';');
    } else {
        returnError('No groups retrieved.');
    }
}


/**
 * Get name group for an agent, and print all the result like a csv.
 *
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array             $other it's array, $other as param are the filters available <alias> in this order
 *              and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *              example:
 *
 *              api.php?op=get&op2=group_agent_by_alias&return_type=csv&other=Pepito&other_mode=url_encode_separator_|
 *
 * @param $thrash3 Don't use.
 */
function api_get_group_agent_by_alias($thrash1, $thrash2, $other, $thrash3)
{
    global $config;

    if (is_metaconsole()) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'AR')) {
        returnError('forbidden', 'csv');
        return;
    }

    $group_names = [];

    if (is_metaconsole()) {
        $servers = db_get_all_rows_sql(
            'SELECT *
            FROM tmetaconsole_setup
                WHERE disabled = 0'
        );

        if ($servers === false) {
            $servers = [];
        }

        foreach ($servers as $server) {
            if (metaconsole_connect($server) == NOERR) {
                $sql = sprintf("SELECT tagente.id_agente FROM tagente WHERE alias LIKE  '%s' ", $other['data'][0]);
                $agent_id = db_get_all_rows_sql($sql);

                foreach ($agent_id as &$id) {
                    $sql = sprintf(
                        'SELECT groups.nombre nombre 
                        FROM tagente agents, tgrupo groups
                        WHERE id_agente = %d 
                            AND agents.id_grupo = groups.id_grupo',
                        $id['id_agente']
                    );
                    $group_server_names = db_get_all_rows_sql($sql);

                    if ($group_server_names) {
                        foreach ($group_server_names as $group_server_name) {
                            $group_names[] = $group_server_name;
                        }
                    }
                }
            }

            metaconsole_restore_db();
        }
    } else {
        $sql = sprintf("SELECT tagente.id_agente FROM tagente WHERE alias LIKE  '%s' ", $other['data'][0]);
        $agent_id = db_get_all_rows_sql($sql);

        foreach ($agent_id as &$id) {
            if (!users_access_to_agent($id['id_agente'])) {
                continue;
            }

            $sql = sprintf(
                'SELECT groups.nombre nombre 
            FROM tagente agents, tgrupo groups
            WHERE id_agente = %d 
                AND agents.id_grupo = groups.id_grupo',
                $id['id_agente']
            );
            $group_name = db_get_all_rows_sql($sql);
            $group_names[] = $group_name[0];
        }
    }

    if (count($group_names) > 0 and $group_names !== false) {
        $data = [
            'type' => 'array',
            'data' => $group_names,
        ];

        returnData('csv', $data, ';');
    } else {
        returnError('No groups retrieved.');
    }
}


/**
 * Get id server whare agent is located, and print all the result like a csv.
 *
 * @param $id name of agent.
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 *  example:
 *
 *  api.php?op=get&op2=locate_agent&return_type=csv&id=Pepito&other_mode=url_encode_separator_|
 *
 * @param $thrash3 Don't use.
 */


function api_get_locate_agent($id, $thrash1, $thrash2, $thrash3)
{
    if (!is_metaconsole()) {
        return;
    }

    $servers = db_get_all_rows_sql(
        'SELECT *
        FROM tmetaconsole_setup
            WHERE disabled = 0'
    );

    if ($servers === false) {
        $servers = [];
    }

    foreach ($servers as $server) {
        $id_server = $server['id'];
        if (metaconsole_connect($server) == NOERR) {
            $agent_id = agents_get_agent_id($id, true);

            if ($agent_id && agents_check_access_agent($agent_id)) {
                $group_servers[]['server'] = $id_server;
            }
        }

        metaconsole_restore_db();
    }

    if (count($group_servers) > 0 and $group_servers !== false) {
        $data = [
            'type' => 'array',
            'data' => $group_servers,
        ];

        returnData('csv', $data, ';');
    } else {
        returnError('No agents located.');
    }
}


/**
 * Get id group for an agent, and print all the result like a csv.
 *
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array             $other it's array, $other as param are the filters available <name_agent> in this order
 *              and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *              example:
 *
 *              api.php?op=get&op2=id_group_agent_by_name&return_type=csv&other=Pepito&other_mode=url_encode_separator_|
 *
 * @param $thrash3 Don't use.
 */
function api_get_id_group_agent_by_name($thrash1, $thrash2, $other, $thrash3)
{
    if (is_metaconsole()) {
        return;
    }

    $group_names = [];

    if (is_metaconsole()) {
        $servers = db_get_all_rows_sql(
            'SELECT *
            FROM tmetaconsole_setup
                WHERE disabled = 0'
        );

        if ($servers === false) {
            $servers = [];
        }

        foreach ($servers as $server) {
            if (metaconsole_connect($server) == NOERR) {
                $agent_id = agents_get_agent_id($other['data'][0], true);

                if ($agent_id) {
                    $sql = sprintf(
                        'SELECT groups.id_grupo id_group 
                        FROM tagente agents, tgrupo groups
                        WHERE id_agente = %d 
                            AND agents.id_grupo = groups.id_grupo',
                        $agent_id
                    );
                    $group_server_names = db_get_all_rows_sql($sql);

                    if ($group_server_names) {
                        foreach ($group_server_names as $group_server_name) {
                            $group_names[] = $group_server_name;
                        }
                    }
                }
            }

            metaconsole_restore_db();
        }
    } else {
        $agent_id = agents_get_agent_id($other['data'][0], true);

        if (!util_api_check_agent_and_print_error($agent_id, 'csv')) {
            return;
        }

        $sql = sprintf(
            'SELECT groups.id_grupo id_group
            FROM tagente agents, tgrupo groups
            WHERE id_agente = %d 
                AND agents.id_grupo = groups.id_grupo',
            $agent_id
        );
        $group_names = db_get_all_rows_sql($sql);
    }

    if (count($group_names) > 0 and $group_names !== false) {
        $data = [
            'type' => 'array',
            'data' => $group_names,
        ];

        returnData('csv', $data);
    } else {
        returnError('No groups retrieved.');
    }
}


/**
 * Get id group for an agent, and print all the result like a csv.
 *
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array             $other it's array, $other as param are the filters available <alias> in this order
 *              and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *              example:
 *
 *              api.php?op=get&op2=id_group_agent_by_alias&return_type=csv&other=Nova&other_mode=url_encode_separator_|
 *
 * @param $thrash3 Don't use.
 */
function api_get_id_group_agent_by_alias($thrash1, $thrash2, $other, $thrash3)
{
    global $config;

    if (is_metaconsole()) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'AR')) {
        returnError('forbidden', 'csv');
        return;
    }

    $group_names = [];

    if (is_metaconsole()) {
        $servers = db_get_all_rows_sql(
            'SELECT *
            FROM tmetaconsole_setup
                WHERE disabled = 0'
        );

        if ($servers === false) {
            $servers = [];
        }

        foreach ($servers as $server) {
            if (metaconsole_connect($server) == NOERR) {
                $sql = sprintf("SELECT tagente.id_agente FROM tagente WHERE alias LIKE  '%s' ", $other['data'][0]);
                $agent_id = db_get_all_rows_sql($sql);

                foreach ($agent_id as &$id) {
                    $sql = sprintf(
                        'SELECT groups.id_grupo id_group 
                        FROM tagente agents, tgrupo groups
                        WHERE id_agente = %d 
                            AND agents.id_grupo = groups.id_grupo',
                        $id['id_agente']
                    );
                    $group_server_names = db_get_all_rows_sql($sql);

                    if ($group_server_names) {
                        foreach ($group_server_names as $group_server_name) {
                            $group_names[] = $group_server_name;
                        }
                    }
                }
            }

            metaconsole_restore_db();
        }
    } else {
        $sql = sprintf("SELECT tagente.id_agente FROM tagente WHERE alias LIKE  '%s' ", $other['data'][0]);
        $agent_id = db_get_all_rows_sql($sql);

        foreach ($agent_id as &$id) {
            if (!users_access_to_agent($id['id_agente'])) {
                continue;
            }

            $sql = sprintf(
                'SELECT groups.id_grupo id_group
            FROM tagente agents, tgrupo groups
            WHERE id_agente = %d 
                AND agents.id_grupo = groups.id_grupo',
                $id['id_agente']
            );
            $group_name = db_get_all_rows_sql($sql);
            $group_names[] = $group_name[0];
        }
    }

    if (count($group_names) > 0 and $group_names !== false) {
        $data = [
            'type' => 'array',
            'data' => $group_names,
        ];
        returnData('csv', $data);
    } else {
        returnError('No groups retrieved.');
    }
}


/**
 * Get all policies, possible filtered by agent, and print all the result like a csv.
 *
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array             $other it's array, $other as param are the filters available <id_agent> in this order
 *              and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *              example:
 *
 *              api.php?op=get&op2=policies&return_type=csv&other=&other_mode=url_encode_separator_|
 *
 * @param $thrash3 Don't use.
 */
function api_get_policies($thrash1, $thrash2, $other, $thrash3)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'AW')) {
        returnError('forbidden', 'csv');
        return;
    }

    $user_groups = implode(',', array_keys(users_get_groups($config['id_user'], 'AW')));

    if ($other['data'][0] != '') {
        if (!users_access_to_agent($other['data'][0])) {
            returnError('forbidden', 'csv');
            return;
        }

        $where = ' AND pol_agents.id_agent = '.$other['data'][0];

        $sql = sprintf(
            'SELECT policy.id, name, id_agent
            FROM tpolicies AS policy, tpolicy_agents AS pol_agents 
            WHERE policy.id = pol_agents.id_policy %s AND id_group IN (%s)',
            $where,
            $user_groups
        );
    } else {
        $sql = "SELECT id, name FROM tpolicies AS policy WHERE id_group IN ($user_groups)";
    }

    $policies = db_get_all_rows_sql($sql);

    if (count($policies) > 0 and $policies !== false) {
        $data = [
            'type' => 'array',
            'data' => $policies,
        ];

        returnData('csv', $data, ';');
    } else {
        returnError('No policies retrieved.');
    }
}


/**
 * Get policy modules, possible filtered by agent, and print all the result like a csv.
 *
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array             $other it's array, $other as param are the filters available <id_agent> in this order
 *              and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *              example:
 *
 *              api.php?op=get&op2=policy_modules&return_type=csv&other=2&other_mode=url_encode_separator_|
 *
 * @param $thrash3 Don't use.
 */
function api_get_policy_modules($thrash1, $thrash2, $other, $thrash3)
{
    if (defined('METACONSOLE')) {
        return;
    }

    $where = '';

    if ($other['data'][0] == '') {
        returnError('Could not retrieve policy modules. Id_policy cannot be left blank.');
        return;
    }

    $policies = enterprise_hook(
        'policies_get_modules_api',
        [
            $other['data'][0],
            $other['data'][1],
        ]
    );

    if ($policies === ENTERPRISE_NOT_HOOK) {
        returnError('Could not retrieve ');
        return;
    }

    if (count($policies) > 0 and $policies !== false) {
        $data = [
            'type' => 'array',
            'data' => $policies,
        ];

        returnData('csv', $data, ';');
    } else {
        returnError('No policy modules retrieved.');
    }
}


/**
 * Create a network module in agent.
 * And return the id_agent_module of new module.
 *
 * @param    string $id      Name of agent to add the module.
 * @param    string $thrash1 Don't use.
 * @param    array  $other   It's array, $other as param is <name_module>;<disabled>;<id_module_type>;
 *    <id_module_group>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;<ff_threshold>;
 *    <history_data>;<ip_target>;<module_port>;<snmp_community>;<snmp_oid>;<module_interval>;<post_process>;
 *    <min>;<max>;<custom_id>;<description>;<disabled_types_event>;<module_macros>;
 *    <each_ff>;<ff_threshold_normal>;<ff_threshold_warning>;<ff_threshold_critical>; in this order
 *    and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>).
 * @param    string $thrash3 Don't use.
 * @example: api.php?op=set&op2=create_network_module&id=pepito&other=prueba|0|7|1|10|15|0|16|18|0|15|0|www.google.es|0||0|180|0|0|0|0|latency%20ping&other_mode=url_encode_separator_|*
 * @return   mixed Return.
 */
function api_set_create_network_module($id, $thrash1, $other, $thrash3)
{
    if (defined('METACONSOLE')) {
        return;
    }

    $agent_by_alias = false;

    if ($other['data'][31] === '1') {
        $agent_by_alias = true;
    }

    if ($agent_by_alias) {
        $idsAgents = agents_get_agent_id_by_alias($id);
    } else {
        $idAgent = agents_get_agent_id($id);
    }

    if (!$agent_by_alias) {
        if (!util_api_check_agent_and_print_error($idAgent, 'string', 'AW')) {
            return;
        }
    }

    if ($other['data'][2] < 6 or $other['data'][2] > 18) {
        returnError(
            'Could not be created the network module. Id_module_type is not correct for network modules.'
        );
        return;
    }

    $name = $other['data'][0];

    $disabled_types_event = [];
    $disabled_types_event[EVENTS_GOING_UNKNOWN] = (int) !$other['data'][22];
    $disabled_types_event = json_encode($disabled_types_event);

    $values = [
        'disabled'              => $other['data'][1],
        'id_tipo_modulo'        => $other['data'][2],
        'id_module_group'       => $other['data'][3],
        'min_warning'           => $other['data'][4],
        'max_warning'           => $other['data'][5],
        'str_warning'           => $other['data'][6],
        'min_critical'          => $other['data'][7],
        'max_critical'          => $other['data'][8],
        'str_critical'          => $other['data'][9],
        'min_ff_event'          => $other['data'][10],
        'history_data'          => $other['data'][11],
        'ip_target'             => $other['data'][12],
        'tcp_port'              => $other['data'][13],
        'snmp_community'        => $other['data'][14],
        'snmp_oid'              => $other['data'][15],
        'module_interval'       => $other['data'][16],
        'post_process'          => $other['data'][17],
        'min'                   => $other['data'][18],
        'max'                   => $other['data'][19],
        'custom_id'             => $other['data'][20],
        'descripcion'           => $other['data'][21],
        'id_modulo'             => 2,
        'disabled_types_event'  => $disabled_types_event,
        'module_macros'         => $other['data'][23],
        'each_ff'               => $other['data'][24],
        'min_ff_event_normal'   => $other['data'][25],
        'min_ff_event_warning'  => $other['data'][26],
        'min_ff_event_critical' => $other['data'][27],
        'critical_inverse'      => $other['data'][28],
        'warning_inverse'       => $other['data'][29],
        'ff_type'               => $other['data'][30],
    ];

    if (! $values['descripcion']) {
        $values['descripcion'] = '';
        // Column 'descripcion' cannot be null.
    }

    if (! $values['module_macros']) {
        $values['module_macros'] = '';
        // Column 'module_macros' cannot be null.
    }

    $type_exist = db_get_value_filter(
        'id_tipo',
        'ttipo_modulo',
        [
            'id_tipo' => $values['id_tipo_modulo'],
        ]
    );

    if ((bool) $type_exist === false) {
        returnError('Module type does not exist');
        return;
    }

    if ($agent_by_alias) {
        $agents_affected = 0;
        $idModule = false;

        foreach ($idsAgents as $id) {
            if (!util_api_check_agent_and_print_error($id['id_agente'], 'string', 'AW')) {
                continue;
            }

            $idModule = modules_create_agent_module($id['id_agente'], $name, $values, true);

            if (!is_error($idModule)) {
                $agents_affected++;
            }
        }

        returnData('string', ['type' => 'string', 'data' => $agents_affected.' agents affected']);

        return;
    } else {
        $idModule = modules_create_agent_module($idAgent, $name, $values, true);
    }

    if (is_error($idModule)) {
        // TODO: Improve the error returning more info.
        returnError('Could not be created the network module.');
    } else {
        returnData('string', ['type' => 'string', 'data' => $idModule]);
    }
}


/**
 * Update a network module in agent. And return a message with the result of the operation.
 *
 * @param string            $id    Id of the network module to update.
 * @param $thrash1 Don't use.
 * @param array             $other it's array, $other as param is <id_agent>;<disabled>
 *              <id_module_group>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;<ff_threshold>;
 *              <history_data>;<ip_target>;<module_port>;<snmp_community>;<snmp_oid>;<module_interval>;<post_process>;
 *              <min>;<max>;<custom_id>;<description>;<disabled_types_event>;<module_macros>;
 *              <each_ff>;<ff_threshold_normal>;<ff_threshold_warning>;<ff_threshold_critidcal>; in this order
 *              and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *              example:
 *
 *              api.php?op=set&op2=update_network_module&id=271&other=156|0|2|10|15||16|18||7|0|127.0.0.1|0||0|300|30.00|0|0|0|latency%20ping%20modified%20by%20the%20Api&other_mode=url_encode_separator_|
 *
 * @param $thrash3 Don't use
 */
function api_set_update_network_module($id_module, $thrash1, $other, $thrash3)
{
    if (defined('METACONSOLE')) {
        return;
    }

    if ($id_module == '') {
        returnError(
            'The network module could not be updated. Module name cannot be left blank.'
        );
        return;
    }

    if (!util_api_check_agent_and_print_error(
        modules_get_agentmodule_agent($id_module),
        'string',
        'AW'
    )
    ) {
        return;
    }

    $check_id_module = db_get_value('id_agente_modulo', 'tagente_modulo', 'id_agente_modulo', $id_module);

    if (!$check_id_module) {
        returnError(
            'The network module could not be updated. Id_module does not exist.'
        );
        return;
    }

    // If we want to change the module to a new agent
    if ($other['data'][0] != '') {
        if (!util_api_check_agent_and_print_error($other['data'][0], 'string', 'AW')) {
            return;
        }

        $id_agent_old = db_get_value('id_agente', 'tagente_modulo', 'id_agente_modulo', $id_module);

        if ($id_agent_old != $other['data'][0]) {
            $id_module_exists = db_get_value_filter(
                'id_agente_modulo',
                'tagente_modulo',
                [
                    'nombre'    => $module_name,
                    'id_agente' => $other['data'][0],
                ]
            );

            if ($id_module_exists) {
                returnError(
                    'The network module could not be updated. Id_module exists in the new agent.'
                );
                return;
            }
        }
    }

    $network_module_fields = [
        'id_agente',
        'disabled',
        'id_module_group',
        'min_warning',
        'max_warning',
        'str_warning',
        'min_critical',
        'max_critical',
        'str_critical',
        'min_ff_event',
        'history_data',
        'ip_target',
        'tcp_port',
        'snmp_community',
        'snmp_oid',
        'module_interval',
        'post_process',
        'min',
        'max',
        'custom_id',
        'descripcion',
        'disabled_types_event',
        'module_macros',
        'each_ff',
        'min_ff_event_normal',
        'min_ff_event_warning',
        'min_ff_event_critical',
        'critical_inverse',
        'warning_inverse',
        'policy_linked',
        'ff_type',
    ];

    $values = [];
    $cont = 0;
    foreach ($network_module_fields as $field) {
        if ($other['data'][$cont] != '') {
            $values[$field] = $other['data'][$cont];
        }

        $cont++;
    }

    $values['policy_linked'] = 0;

    $result_update = modules_update_agent_module($id_module, $values);

    if ($result_update < 0) {
        returnError('The network module could not be updated.');
    } else {
        returnData('string', ['type' => 'string', 'data' => __('Network module updated.')]);
    }
}


/**
 * Create a plugin module in agent. And return the id_agent_module of new module.
 *
 * @param string            $id    Name of agent to add the module.
 * @param $thrash1 Don't use.
 * @param array             $other it's array, $other as param is <name_module>;<disabled>;<id_module_type>;
 *              <id_module_group>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;<ff_threshold>;
 *              <history_data>;<ip_target>;<tcp_port>;<snmp_community>;<snmp_oid>;<module_interval>;<post_process>;
 *              <min>;<max>;<custom_id>;<description>;<id_plugin>;<plugin_user>;<plugin_pass>;<plugin_parameter>;
 *              <disabled_types_event>;<macros>;<module_macros>;<each_ff>;<ff_threshold_normal>;
 *              <ff_threshold_warning>;<ff_threshold_critical> in this order
 *              and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *              example:
 *
 *              api.php?op=set&op2=create_plugin_module&id=pepito&other=prueba|0|1|2|0|0||0|0||0|0|127.0.0.1|0||0|300|0|0|0|0|plugin%20module%20from%20api|2|admin|pass|-p%20max&other_mode=url_encode_separator_|
 *
 * @param $thrash3 Don't use
 */
function api_set_create_plugin_module($id, $thrash1, $other, $thrash3)
{
    if (defined('METACONSOLE')) {
        return;
    }

    if ($other['data'][22] == '') {
        returnError('The plugin module could not be created. Id_plugin cannot be left blank.');
        return;
    }

    $agent_by_alias = false;

    if ($other['data'][36] === '1') {
        $agent_by_alias = true;
    }

    if ($agent_by_alias) {
        $idsAgents = agents_get_agent_id_by_alias($id);
    } else {
        $idAgent = agents_get_agent_id($id);
    }

    if (!$agent_by_alias) {
        if (!util_api_check_agent_and_print_error($idAgent, 'string', 'AW')) {
            return;
        }
    }

    $disabled_types_event = [];
    $disabled_types_event[EVENTS_GOING_UNKNOWN] = (int) !$other['data'][26];
    $disabled_types_event = json_encode($disabled_types_event);

    $name = $other['data'][0];

    $values = [
        'disabled'              => $other['data'][1],
        'id_tipo_modulo'        => $other['data'][2],
        'id_module_group'       => $other['data'][3],
        'min_warning'           => $other['data'][4],
        'max_warning'           => $other['data'][5],
        'str_warning'           => $other['data'][6],
        'min_critical'          => $other['data'][7],
        'max_critical'          => $other['data'][8],
        'str_critical'          => $other['data'][9],
        'min_ff_event'          => $other['data'][10],
        'history_data'          => $other['data'][11],
        'ip_target'             => $other['data'][12],
        'tcp_port'              => $other['data'][13],
        'snmp_community'        => $other['data'][14],
        'snmp_oid'              => $other['data'][15],
        'module_interval'       => $other['data'][16],
        'post_process'          => $other['data'][17],
        'min'                   => $other['data'][18],
        'max'                   => $other['data'][19],
        'custom_id'             => $other['data'][20],
        'descripcion'           => $other['data'][21],
        'id_modulo'             => 4,
        'id_plugin'             => $other['data'][22],
        'plugin_user'           => $other['data'][23],
        'plugin_pass'           => $other['data'][24],
        'plugin_parameter'      => $other['data'][25],
        'disabled_types_event'  => $disabled_types_event,
        'macros'                => base64_decode(str_replace('&#x20', '+', $other['data'][27])),
        'module_macros'         => $other['data'][28],
        'each_ff'               => $other['data'][29],
        'min_ff_event_normal'   => $other['data'][30],
        'min_ff_event_warning'  => $other['data'][31],
        'min_ff_event_critical' => $other['data'][32],
        'critical_inverse'      => $other['data'][33],
        'warning_inverse'       => $other['data'][34],
        'ff_type'               => $other['data'][35],
    ];

    $plugin = db_get_row('tplugin', 'id', $values['id_plugin']);
    if (empty($plugin)) {
        returnError('id_not_found');
        return;
    }

    $plugin_command_macros = $plugin['macros'];

    if (!empty($values['macros'])) {
        $macros = io_safe_input_json($values['macros']);
        if (empty($macros)) {
            returnError('JSON string in macros is invalid.');
            exit;
        }

        $values['macros'] = io_merge_json_value($plugin_command_macros, $macros);
    }

    if (! $values['descripcion']) {
        $values['descripcion'] = '';
        // Column 'descripcion' cannot be null.
    }

    if (! $values['module_macros']) {
        $values['module_macros'] = '';
        // Column 'module_macros' cannot be null.
    }

    if ($agent_by_alias) {
        $agents_affected = 0;

        foreach ($idsAgents as $id) {
            if (!util_api_check_agent_and_print_error($id['id_agente'], 'string', 'AW')) {
                continue;
            }

            $idModule = modules_create_agent_module($id['id_agente'], $name, $values, true);

            if (!is_error($idModule)) {
                $agents_affected++;
            }
        }

        returnData('string', ['type' => 'string', 'data' => $agents_affected.' agents affected']);

        return;
    } else {
        $idModule = modules_create_agent_module($idAgent, $name, $values, true);
    }

    if (is_error($idModule)) {
        // TODO: Improve the error returning more info.
        returnError('The plugin module could not be created.');
    } else {
        returnData('string', ['type' => 'string', 'data' => $idModule]);
    }
}


/**
 * Update a plugin module in agent. And return the id_agent_module of new module.
 *
 * @param string            $id    Id of the plugin module to update.
 * @param $thrash1 Don't use.
 * @param array             $other it's array, $other as param is <id_agent>;<disabled>;
 *              <id_module_group>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;<ff_threshold>;
 *              <history_data>;<ip_target>;<module_port>;<snmp_community>;<snmp_oid>;<module_interval>;<post_process>;
 *              <min>;<max>;<custom_id>;<description>;<id_plugin>;<plugin_user>;<plugin_pass>;<plugin_parameter>;
 *              <disabled_types_event>;<macros>;<module_macros>;<each_ff>;<ff_threshold_normal>;
 *              <ff_threshold_warning>;<ff_threshold_critical> in this order
 *              and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *              example:
 *
 *              api.php?op=set&op2=update_plugin_module&id=293&other=156|0|2|0|0||0|0||0|0|127.0.0.1|0||0|300|0|0|0|0|plugin%20module%20from%20api|2|admin|pass|-p%20max&other_mode=url_encode_separator_|
 *
 * @param $thrash3 Don't use
 */
function api_set_update_plugin_module($id_module, $thrash1, $other, $thrash3)
{
    if (defined('METACONSOLE')) {
        return;
    }

    if ($id_module == '') {
        returnError('The plugin module could not be updated. Id_module cannot be left blank.');
        return;
    }

    if (!util_api_check_agent_and_print_error(
        modules_get_agentmodule_agent($id_module),
        'string',
        'AW'
    )
    ) {
        return;
    }

    // If we want to change the module to a new agent
    if ($other['data'][0] != '') {
        if (!util_api_check_agent_and_print_error($other['data'][0], 'string', 'AW')) {
            return;
        }

        $id_agent_old = db_get_value('id_agente', 'tagente_modulo', 'id_agente_modulo', $id_module);

        if ($id_agent_old != $other['data'][0]) {
            $id_module_exists = db_get_value_filter('id_agente_modulo', 'tagente_modulo', ['nombre' => $module_name, 'id_agente' => $other['data'][0]]);

            if ($id_module_exists) {
                returnError('The plugin module could not be updated. Id_module exists in the new agent.');
                return;
            }
        }

        // Check if agent exists
        $check_id_agent = db_get_value('id_agente', 'tagente', 'id_agente', $other['data'][0]);
        if (!$check_id_agent) {
            returnError('The plugin module could not be updated. Id_agent does not exist.');
            return;
        }
    }

    $plugin_module_fields = [
        'id_agente',
        'disabled',
        'id_module_group',
        'min_warning',
        'max_warning',
        'str_warning',
        'min_critical',
        'max_critical',
        'str_critical',
        'min_ff_event',
        'history_data',
        'ip_target',
        'tcp_port',
        'snmp_community',
        'snmp_oid',
        'module_interval',
        'post_process',
        'min',
        'max',
        'custom_id',
        'descripcion',
        'id_plugin',
        'plugin_user',
        'plugin_pass',
        'plugin_parameter',
        'disabled_types_event',
        'macros',
        'module_macros',
        'each_ff',
        'min_ff_event_normal',
        'min_ff_event_warning',
        'min_ff_event_critical',
        'critical_inverse',
        'warning_inverse',
        'policy_linked',
        'ff_type',
    ];

    $values = [];
    $cont = 0;
    foreach ($plugin_module_fields as $field) {
        if ($other['data'][$cont] != '') {
            $values[$field] = $other['data'][$cont];

            if ($field === 'macros') {
                $values[$field] = base64_decode(str_replace('&#x20', '+', $values[$field]));
            }
        }

        $cont++;
    }

    $plugin = db_get_row('tplugin', 'id', $values['id_plugin']);
    if (empty($plugin)) {
        returnError('id_not_found');
        return;
    }

    $plugin_command_macros = $plugin['macros'];

    if (!empty($values['macros'])) {
        $macros = io_safe_input_json($values['macros']);
        if (empty($macros)) {
            returnError('JSON string in macros is invalid.');
            exit;
        }

        $values['macros'] = io_merge_json_value($plugin_command_macros, $macros);
    }

    $values['policy_linked'] = 0;
    $result_update = modules_update_agent_module($id_module, $values);

    if ($result_update < 0) {
        returnError('The plugin module could not be updated');
    } else {
        returnData('string', ['type' => 'string', 'data' => __('Plugin module updated.')]);
    }
}


/**
 * Create a data module in agent. And return the id_agent_module of new module.
 * Note: Only adds database information, this function doesn't alter config file information.
 *
 * @param string            $id    Name of agent to add the module.
 * @param $thrash1 Don't use.
 * @param array             $other it's array, $other as param is <name_module>;<disabled>;<id_module_type>;
 *                 <description>;<id_module_group>;<min_value>;<max_value>;<post_process>;<module_interval>;<min_warning>;
 *                 <max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;<history_data>;
 *                 <disabled_types_event>;<module_macros>;<ff_threshold>;<each_ff>;
 *                <ff_threshold_normal>;<ff_threshold_warning>;<ff_threshold_critical>; in this order
 *              and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *              example:
 *
 *              api.php?op=set&op2=create_data_module&id=pepito&other=prueba|0|1|data%20module%20from%20api|1|10|20|10.50|180|10|15||16|20||0&other_mode=url_encode_separator_|
 *
 * @param $thrash3 Don't use
 */
function api_set_create_data_module($id, $thrash1, $other, $thrash3)
{
    if (defined('METACONSOLE')) {
        return;
    }

    if ($other['data'][0] == '') {
        returnError('The data module could not be created. Module_name cannot be left blank.');
        return;
    }

    $agent_by_alias = false;

    if ($other['data'][27] === '1') {
        $agent_by_alias = true;
    }

    if ($agent_by_alias) {
        $idsAgents = agents_get_agent_id_by_alias($id);
    } else {
        $idAgent = agents_get_agent_id($id);
    }

    if (!$agent_by_alias) {
        if (!util_api_check_agent_and_print_error($idAgent, 'string', 'AW')) {
            return;
        }
    }

    $name = $other['data'][0];

    $disabled_types_event = [];
    $disabled_types_event[EVENTS_GOING_UNKNOWN] = (int) !$other['data'][16];
    $disabled_types_event = json_encode($disabled_types_event);

    $values = [
        'disabled'              => $other['data'][1],
        'id_tipo_modulo'        => $other['data'][2],
        'descripcion'           => $other['data'][3],
        'id_module_group'       => $other['data'][4],
        'min'                   => $other['data'][5],
        'max'                   => $other['data'][6],
        'post_process'          => $other['data'][7],
        'module_interval'       => $other['data'][8],
        'min_warning'           => $other['data'][9],
        'max_warning'           => $other['data'][10],
        'str_warning'           => $other['data'][11],
        'min_critical'          => $other['data'][12],
        'max_critical'          => $other['data'][13],
        'str_critical'          => $other['data'][14],
        'history_data'          => $other['data'][15],
        'id_modulo'             => 1,
        'disabled_types_event'  => $disabled_types_event,
        'module_macros'         => $other['data'][17],
        'min_ff_event'          => $other['data'][18],
        'each_ff'               => $other['data'][19],
        'min_ff_event_normal'   => $other['data'][20],
        'min_ff_event_warning'  => $other['data'][21],
        'min_ff_event_critical' => $other['data'][22],
        'ff_timeout'            => $other['data'][23],
        'critical_inverse'      => $other['data'][24],
        'warning_inverse'       => $other['data'][25],
        'ff_type'               => $other['data'][26],
    ];

    if (! $values['descripcion']) {
        $values['descripcion'] = '';
        // Column 'descripcion' cannot be null.
    }

    if (! $values['module_macros']) {
        $values['module_macros'] = '';
        // Column 'module_macros' cannot be null.
    }

    if ($agent_by_alias) {
        $agents_affected = 0;

        foreach ($idsAgents as $id) {
            if (!util_api_check_agent_and_print_error($id['id_agente'], 'string', 'AW')) {
                continue;
            }

            $idModule = modules_create_agent_module($id['id_agente'], $name, $values, true);

            if (!is_error($idModule)) {
                $agents_affected++;
            }
        }

        returnData('string', ['type' => 'string', 'data' => $agents_affected.' agents affected']);

        return;
    } else {
        $idModule = modules_create_agent_module($idAgent, $name, $values, true);
    }

    if (is_error($idModule)) {
        // TODO: Improve the error returning more info.
        returnError('The data module could not be created.');
    } else {
        returnData('string', ['type' => 'string', 'data' => $idModule]);
    }
}


/**
 * Create a synthetic module in agent. And return the id_agent_module of new module.
 * Note: Only adds database information, this function doesn't alter config file information.
 *
 * @param string            $id    Name of agent to add the module.
 * @param $thrash1 Don't use.
 * @param array             $other it's array, $other as param is <name_module><synthetic_type><AgentName;Operation;NameModule> OR <AgentName;NameModule> OR <Operation;Value>in this order
 *              and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *              example:
 *
 *              api.php?op=set&op2=create_synthetic_module&id=pepito&other=prueba|average|Agent%20Name;AVG;Name%20Module|Agent%20Name2;AVG;Name%20Module2&other_mode=url_encode_separator_|
 *
 * @param $thrash3 Don't use
 */
function api_set_create_synthetic_module($id, $agent_by_alias, $other, $thrash3)
{
    if (defined('METACONSOLE')) {
        return;
    }

    global $config;

    io_safe_input_array($other);

    if ($other['data'][0] == '') {
        returnError('The synthetic module could not be created. Module_name cannot be left blank.');
        return;
    }

    if ($agent_by_alias == '1') {
        $ids_agents = agents_get_agent_id_by_alias(io_safe_output($id));

        foreach ($ids_agents as $id) {
            if (!util_api_check_agent_and_print_error($id['id_agente'], 'string', 'AW')) {
                return;
            }
        }
    } else {
        $idAgent = agents_get_agent_id(io_safe_output($id), true);

        if (!util_api_check_agent_and_print_error($idAgent, 'string', 'AW')) {
            return;
        }
    }

    if ($agent_by_alias) {
        foreach ($ids_agents as $id) {
            if (!$id['id_agente']) {
                returnError('The synthetic module could not be created. Agent name does not exist.');
                return;
            }
        }
    } else {
        if (!$idAgent) {
            returnError('The synthetic module could not be created. Agent name does not exist.');
            return;
        }
    }

    $name = io_safe_output($other['data'][0]);
    $name = io_safe_input($name);
    $id_tipo_modulo = db_get_row_sql("SELECT id_tipo FROM ttipo_modulo WHERE nombre = 'generic_data'");

    $values = [
        'id_modulo'         => 5,
        'custom_integer_1'  => 0,
        'custom_integer_2'  => 0,
        'prediction_module' => 3,
        'id_tipo_modulo'    => $id_tipo_modulo['id_tipo'],
    ];

    if (! $values['descripcion']) {
        $values['descripcion'] = '';
        // Column 'descripcion' cannot be null.
    }

    if ($agent_by_alias) {
        foreach ($ids_agents as $id) {
            $idAgent = $id['id_agente'];

            $idModule = modules_create_agent_module($idAgent, $name, $values, true);

            if (is_error($idModule)) {
                // TODO: Improve the error returning more info.
                returnError('The synthetic module could not be created.');
            } else {
                $synthetic_type = $other['data'][1];
                unset($other['data'][0]);
                unset($other['data'][1]);

                $filterdata = [];
                foreach ($other['data'] as $data) {
                    $data = str_replace(['ADD', 'SUB', 'MUL', 'DIV'], ['+', '-', '*', '/'], $data);
                    $data = io_safe_output($data);
                    // Double safe output is necessary.
                    $split_data = explode(';', io_safe_output($data));

                    if (preg_match('/[x\/+*-]/', $split_data[0]) && strlen($split_data[0]) == 1) {
                        if (preg_match('/[\/|+|*|-]/', $split_data[0]) && $synthetic_type === 'average') {
                            returnError(sprintf("[ERROR] With this type: %s only be allow use this operator: 'x' \n\n", $synthetic_type));
                        }

                        $operator = strtolower($split_data[0]);
                        $data_module = [
                            '',
                            $operator,
                            $split_data[1],
                        ];

                        $text_data = implode('_', $data_module);
                        array_push($filterdata, $text_data);
                    } else {
                        if (count($split_data) == 2) {
                            $idAgent = agents_get_agent_id($split_data[0], true);
                            $data_module = [
                                $idAgent,
                                '',
                                $split_data[1],
                            ];
                            $text_data = implode('_', $data_module);
                            array_push($filterdata, $text_data);
                        } else {
                            if (strlen($split_data[1]) > 1 && $synthetic_type != 'average') {
                                returnError(sprintf("[ERROR] You can only use +, -, *, / or x, and you use this: %s \n\n", $split_data[1]));
                                return;
                            }

                            if (preg_match('/[\/|+|*|-]/', $split_data[1]) && $synthetic_type === 'average') {
                                returnError(sprintf("[ERROR] With this type: %s only be allow use this operator: 'x' \n\n", $synthetic_type));
                                return;
                            }

                            $idAgent = agents_get_agent_id(io_safe_output($split_data[0]), true);
                            $operator = strtolower($split_data[1]);
                            $data_module = [
                                $idAgent,
                                $operator,
                                $split_data[2],
                            ];
                            $text_data = implode('_', $data_module);
                            array_push($filterdata, $text_data);
                        }
                    }
                }

                $serialize_ops = implode(',', $filterdata);

                // modules_create_synthetic_operations
                $synthetic = enterprise_hook(
                    'modules_create_synthetic_operations',
                    [
                        $idModule,
                        $serialize_ops,
                    ]
                );

                if ($synthetic === ENTERPRISE_NOT_HOOK) {
                    returnError('The synthetic module could not be created.');
                    db_process_sql_delete(
                        'tagente_modulo',
                        ['id_agente_modulo' => $idModule]
                    );

                    return;
                } else {
                    $status = AGENT_MODULE_STATUS_NO_DATA;
                    switch ($config['dbtype']) {
                        case 'mysql':
                            $result = db_process_sql_insert(
                                'tagente_estado',
                                [
                                    'id_agente_modulo'  => $idModule,
                                    'datos'             => 0,
                                    'timestamp'         => '01-01-1970 00:00:00',
                                    'estado'            => $status,
                                    'id_agente'         => (int) $idAgent,
                                    'utimestamp'        => 0,
                                    'status_changes'    => 0,
                                    'last_status'       => $status,
                                    'last_known_status' => $status,
                                ]
                            );
                        break;

                        case 'postgresql':
                            $result = db_process_sql_insert(
                                'tagente_estado',
                                [
                                    'id_agente_modulo'  => $idModule,
                                    'datos'             => 0,
                                    'timestamp'         => null,
                                    'estado'            => $status,
                                    'id_agente'         => (int) $idAgent,
                                    'utimestamp'        => 0,
                                    'status_changes'    => 0,
                                    'last_status'       => $status,
                                    'last_known_status' => $status,
                                ]
                            );
                        break;

                        case 'oracle':
                            $result = db_process_sql_insert(
                                'tagente_estado',
                                [
                                    'id_agente_modulo'  => $idModule,
                                    'datos'             => 0,
                                    'timestamp'         => '#to_date(\'1970-01-01 00:00:00\', \'YYYY-MM-DD HH24:MI:SS\')',
                                    'estado'            => $status,
                                    'id_agente'         => (int) $idAgent,
                                    'utimestamp'        => 0,
                                    'status_changes'    => 0,
                                    'last_status'       => $status,
                                    'last_known_status' => $status,
                                ]
                            );
                        break;
                    }

                    if ($result === false) {
                        db_process_sql_delete(
                            'tagente_modulo',
                            ['id_agente_modulo' => $idModule]
                        );
                        returnError('The synthetic module could not be created.');
                    } else {
                        db_process_sql('UPDATE tagente SET total_count=total_count+1, notinit_count=notinit_count+1 WHERE id_agente='.(int) $idAgent);
                        returnData('string', ['type' => 'string', 'data' => __('Synthetic module created ID: %s', $idModule)]);
                    }
                }
            }
        }
    } else {
        $idModule = modules_create_agent_module($idAgent, $name, $values, true);

        if (is_error($idModule)) {
            // TODO: Improve the error returning more info
            returnError('The synthetic module could not be created.');
        } else {
            $synthetic_type = $other['data'][1];
            unset($other['data'][0]);
            unset($other['data'][1]);

            $filterdata = [];
            foreach ($other['data'] as $data) {
                $data = str_replace(['ADD', 'SUB', 'MUL', 'DIV'], ['+', '-', '*', '/'], $data);
                $data = io_safe_output($data);
                // Double safe output is necessary.
                $split_data = explode(';', io_safe_output($data));

                if (preg_match('/[x\/+*-]/', $split_data[0]) && strlen($split_data[0]) == 1) {
                    if (preg_match('/[\/|+|*|-]/', $split_data[0]) && $synthetic_type === 'average') {
                        returnError(sprintf("[ERROR] With this type: %s only be allow use this operator: 'x' \n\n", $synthetic_type));
                    }

                    $operator = strtolower($split_data[0]);
                    $data_module = [
                        '',
                        $operator,
                        $split_data[1],
                    ];

                    $text_data = implode('_', $data_module);
                    array_push($filterdata, $text_data);
                } else {
                    if (count($split_data) == 2) {
                        $idAgent = agents_get_agent_id($split_data[0], true);
                        $data_module = [
                            $idAgent,
                            '',
                            $split_data[1],
                        ];
                        $text_data = implode('_', $data_module);
                        array_push($filterdata, $text_data);
                    } else {
                        if (strlen($split_data[1]) > 1 && $synthetic_type != 'average') {
                            returnError(sprintf("[ERROR] You can only use +, -, *, / or x, and you use this: %s \n\n", $split_data[1]));
                            return;
                        }

                        if (preg_match('/[\/|+|*|-]/', $split_data[1]) && $synthetic_type === 'average') {
                            returnError(sprintf("[ERROR] With this type: %s only be allow use this operator: 'x' \n\n", $synthetic_type));
                            return;
                        }

                        $idAgent = agents_get_agent_id(io_safe_output($split_data[0]), true);
                        $operator = strtolower($split_data[1]);
                        $data_module = [
                            $idAgent,
                            $operator,
                            $split_data[2],
                        ];
                        $text_data = implode('_', $data_module);
                        array_push($filterdata, $text_data);
                    }
                }
            }

            $serialize_ops = implode(',', $filterdata);

            // modules_create_synthetic_operations
            $synthetic = enterprise_hook(
                'modules_create_synthetic_operations',
                [
                    $idModule,
                    $serialize_ops,
                ]
            );

            if ($synthetic === ENTERPRISE_NOT_HOOK) {
                returnError('The synthetic module could not be created.');
                db_process_sql_delete(
                    'tagente_modulo',
                    ['id_agente_modulo' => $idModule]
                );

                return;
            } else {
                $status = AGENT_MODULE_STATUS_NO_DATA;
                switch ($config['dbtype']) {
                    case 'mysql':
                        $result = db_process_sql_insert(
                            'tagente_estado',
                            [
                                'id_agente_modulo'  => $idModule,
                                'datos'             => 0,
                                'timestamp'         => '01-01-1970 00:00:00',
                                'estado'            => $status,
                                'id_agente'         => (int) $idAgent,
                                'utimestamp'        => 0,
                                'status_changes'    => 0,
                                'last_status'       => $status,
                                'last_known_status' => $status,
                            ]
                        );
                    break;

                    case 'postgresql':
                        $result = db_process_sql_insert(
                            'tagente_estado',
                            [
                                'id_agente_modulo'  => $idModule,
                                'datos'             => 0,
                                'timestamp'         => null,
                                'estado'            => $status,
                                'id_agente'         => (int) $idAgent,
                                'utimestamp'        => 0,
                                'status_changes'    => 0,
                                'last_status'       => $status,
                                'last_known_status' => $status,
                            ]
                        );
                    break;

                    case 'oracle':
                        $result = db_process_sql_insert(
                            'tagente_estado',
                            [
                                'id_agente_modulo'  => $idModule,
                                'datos'             => 0,
                                'timestamp'         => '#to_date(\'1970-01-01 00:00:00\', \'YYYY-MM-DD HH24:MI:SS\')',
                                'estado'            => $status,
                                'id_agente'         => (int) $idAgent,
                                'utimestamp'        => 0,
                                'status_changes'    => 0,
                                'last_status'       => $status,
                                'last_known_status' => $status,
                            ]
                        );
                    break;
                }

                if ($result === false) {
                    db_process_sql_delete(
                        'tagente_modulo',
                        ['id_agente_modulo' => $idModule]
                    );
                    returnError('The synthetic module could not be created.');
                } else {
                    db_process_sql('UPDATE tagente SET total_count=total_count+1, notinit_count=notinit_count+1 WHERE id_agente='.(int) $idAgent);
                    returnData('string', ['type' => 'string', 'data' => __('Synthetic module created ID: '.$idModule)]);
                }
            }
        }
    }
}


/**
 * Update a data module in agent. And return a message with the result of the operation.
 *
 * @param string            $id    Id of the data module to update.
 * @param $thrash1 Don't use.
 * @param array             $other it's array, $other as param is <id_agent>;<disabled>;<description>;
 *              <id_module_group>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;<ff_threshold>;
 *              <history_data>;<ip_target>;<module_port>;<snmp_community>;<snmp_oid>;<module_interval>;<post_process>;
 *              <min>;<max>;<custom_id>;<disabled_types_event>;<module_macros>;<ff_threshold>;
 *              <each_ff>;<ff_threshold_normal>;<ff_threshold_warning>;<ff_threshold_critical>;
 *              <ff_timeout> in this order
 *              and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *              example:
 *
 *              api.php?op=set&op2=update_data_module&id=170&other=44|0|data%20module%20modified%20from%20API|6|0|0|50.00|300|10|15||16|18||0&other_mode=url_encode_separator_|
 *
 * @param $thrash3 Don't use
 */
function api_set_update_data_module($id_module, $thrash1, $other, $thrash3)
{
    if (defined('METACONSOLE')) {
        return;
    }

    if ($id_module == '') {
        returnError('The data module could not be updated. Id_module cannot be left blank.');
        return;
    }

    if (!util_api_check_agent_and_print_error(
        modules_get_agentmodule_agent($id_module),
        'string',
        'AW'
    )
    ) {
        return;
    }

    // If we want to change the module to a new agent
    if ($other['data'][0] != '') {
        if (!util_api_check_agent_and_print_error($other['data'][0], 'string', 'AW')) {
            return;
        }

        $id_agent_old = db_get_value('id_agente', 'tagente_modulo', 'id_agente_modulo', $id_module);

        if ($id_agent_old != $other['data'][0]) {
            $id_module_exists = db_get_value_filter('id_agente_modulo', 'tagente_modulo', ['nombre' => $module_name, 'id_agente' => $other['data'][0]]);

            if ($id_module_exists) {
                returnError('The data module could not be updated. Id_module exists in the new agent.');
                return;
            }
        }

        // Check if agent exists
        $check_id_agent = db_get_value('id_agente', 'tagente', 'id_agente', $other['data'][0]);
        if (!$check_id_agent) {
            returnError('The data module could not be updated. Id_agent does not exist.');
            return;
        }
    }

    $data_module_fields = [
        'id_agente',
        'disabled',
        'descripcion',
        'id_module_group',
        'min',
        'max',
        'post_process',
        'module_interval',
        'min_warning',
        'max_warning',
        'str_warning',
        'min_critical',
        'max_critical',
        'str_critical',
        'history_data',
        'disabled_types_event',
        'module_macros',
        'min_ff_event',
        'each_ff',
        'min_ff_event_normal',
        'min_ff_event_warning',
        'min_ff_event_critical',
        'ff_timeout',
        'critical_inverse',
        'warning_inverse',
        'policy_linked',
        'ff_type',
    ];

    $values = [];
    $cont = 0;
    foreach ($data_module_fields as $field) {
        if ($other['data'][$cont] != '') {
            $values[$field] = $other['data'][$cont];
        }

        $cont++;
    }

    $values['policy_linked'] = 0;
    $result_update = modules_update_agent_module($id_module, $values);

    if ($result_update < 0) {
        returnError('The data module could not be updated.');
    } else {
        returnData('string', ['type' => 'string', 'data' => __('Data module updated.')]);
    }
}


/**
 * Create a SNMP module in agent. And return the id_agent_module of new module.
 *
 * @param string            $id    Name of agent to add the module.
 * @param $thrash1 Don't use.
 * @param array             $other it's array, $other as param is <name_module>;<disabled>;<id_module_type>;
 *              <id_module_group>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;<ff_threshold>;
 *              <history_data>;<ip_target>;<module_port>;<snmp_version>;<snmp_community>;<snmp_oid>;<module_interval>;<post_process>;
 *              <min>;<max>;<custom_id>;<description>;<snmp3_priv_method>;<snmp3_priv_pass>;<snmp3_sec_level>;<snmp3_auth_method>;
 *              <snmp3_auth_user>;<snmp3_auth_pass>;<disabled_types_event>;<each_ff>;<ff_threshold_normal>;
 *              <ff_threshold_warning>;<ff_threshold_critical> in this order
 *              and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *              example:
 *
 *                 example 1 (snmp v: 3, snmp3_priv_method: AES, passw|authNoPriv|MD5|pepito_user|example_priv_passw)
 *
 *              api.php?op=set&op2=create_snmp_module&id=pepito&other=prueba|0|15|1|10|15||16|18||15|0|127.0.0.1|60|3|public|.1.3.6.1.2.1.1.1.0|180|0|0|0|0|SNMP%20module%20from%20API|AES|example_priv_passw|authNoPriv|MD5|pepito_user|example_auth_passw&other_mode=url_encode_separator_|
 *
 *              example 2 (snmp v: 1)
 *
 *              api.php?op=set&op2=create_snmp_module&id=pepito1&other=prueba2|0|15|1|10|15||16|18||15|0|127.0.0.1|60|1|public|.1.3.6.1.2.1.1.1.0|180|0|0|0|0|SNMP module from API&other_mode=url_encode_separator_|
 *
 * @param $thrash3 Don't use
 */
function api_set_create_snmp_module($id, $thrash1, $other, $thrash3)
{
    if (defined('METACONSOLE')) {
        return;
    }

    if ($other['data'][0] == '') {
        returnError('The SNMP module could not be created. Module_name cannot be left blank.');
        return;
    }

    if ($other['data'][2] < 15 or $other['data'][2] > 18) {
        returnError('The SNMP module could not be created. Invalid id_module_type for a SNMP module.');
        return;
    }

    $agent_by_alias = false;

    if ($other['data'][35] === '1') {
        $agent_by_alias = true;
    }

    if ($agent_by_alias) {
        $idsAgents = agents_get_agent_id_by_alias($id);
    } else {
        $idAgent = agents_get_agent_id($id);
    }

    if (!$agent_by_alias) {
        if (!util_api_check_agent_and_print_error($idAgent, 'string', 'AW')) {
            return;
        }
    }

    $name = $other['data'][0];

    $disabled_types_event = [];
    $disabled_types_event[EVENTS_GOING_UNKNOWN] = (int) !$other['data'][27];
    $disabled_types_event = json_encode($disabled_types_event);

    // SNMP version 3.
    if ($other['data'][14] == '3') {
        if ($other['data'][23] != 'AES' and $other['data'][23] != 'DES') {
            returnError('The SNMP module could not be created.. snmp3_priv_method does not exist. Set it to \'AES\' or \'DES\'. ');
            return;
        }

        if ($other['data'][25] != 'authNoPriv' and $other['data'][25] != 'authPriv' and $other['data'][25] != 'noAuthNoPriv') {
            returnError('The SNMP module could not be created. snmp3_sec_level does not exist. Set it to \'authNoPriv\' or \'authPriv\' or \'noAuthNoPriv\'. ');
            return;
        }

        if ($other['data'][26] != 'MD5' and $other['data'][26] != 'SHA') {
            returnError('The SNMP module could not be created. snmp3_auth_method does not exist. Set it to \'MD5\' or \'SHA\'. ');
            return;
        }

        $values = [
            'disabled'              => $other['data'][1],
            'id_tipo_modulo'        => $other['data'][2],
            'id_module_group'       => $other['data'][3],
            'min_warning'           => $other['data'][4],
            'max_warning'           => $other['data'][5],
            'str_warning'           => $other['data'][6],
            'min_critical'          => $other['data'][7],
            'max_critical'          => $other['data'][8],
            'str_critical'          => $other['data'][9],
            'min_ff_event'          => $other['data'][10],
            'history_data'          => $other['data'][11],
            'ip_target'             => $other['data'][12],
            'tcp_port'              => $other['data'][13],
            'tcp_send'              => $other['data'][14],
            'snmp_community'        => $other['data'][15],
            'snmp_oid'              => $other['data'][16],
            'module_interval'       => $other['data'][17],
            'post_process'          => $other['data'][18],
            'min'                   => $other['data'][19],
            'max'                   => $other['data'][20],
            'custom_id'             => $other['data'][21],
            'descripcion'           => $other['data'][22],
            'id_modulo'             => 2,
            'custom_string_1'       => $other['data'][23],
            'custom_string_2'       => $other['data'][24],
            'custom_string_3'       => $other['data'][25],
            'plugin_parameter'      => $other['data'][26],
            'plugin_user'           => $other['data'][27],
            'plugin_pass'           => $other['data'][28],
            'disabled_types_event'  => $disabled_types_event,
            'each_ff'               => $other['data'][30],
            'min_ff_event_normal'   => $other['data'][31],
            'min_ff_event_warning'  => $other['data'][32],
            'min_ff_event_critical' => $other['data'][33],
            'ff_type'               => $other['data'][34],
        ];
    } else {
        $values = [
            'disabled'              => $other['data'][1],
            'id_tipo_modulo'        => $other['data'][2],
            'id_module_group'       => $other['data'][3],
            'min_warning'           => $other['data'][4],
            'max_warning'           => $other['data'][5],
            'str_warning'           => $other['data'][6],
            'min_critical'          => $other['data'][7],
            'max_critical'          => $other['data'][8],
            'str_critical'          => $other['data'][9],
            'min_ff_event'          => $other['data'][10],
            'history_data'          => $other['data'][11],
            'ip_target'             => $other['data'][12],
            'tcp_port'              => $other['data'][13],
            'tcp_send'              => $other['data'][14],
            'snmp_community'        => $other['data'][15],
            'snmp_oid'              => $other['data'][16],
            'module_interval'       => $other['data'][17],
            'post_process'          => $other['data'][18],
            'min'                   => $other['data'][19],
            'max'                   => $other['data'][20],
            'custom_id'             => $other['data'][21],
            'descripcion'           => $other['data'][22],
            'id_modulo'             => 2,
            'disabled_types_event'  => $disabled_types_event,
            'each_ff'               => $other['data'][24],
            'min_ff_event_normal'   => $other['data'][25],
            'min_ff_event_warning'  => $other['data'][26],
            'min_ff_event_critical' => $other['data'][27],
            'ff_type'               => $other['data'][28],
        ];
    }

    if (! $values['descripcion']) {
        $values['descripcion'] = '';
        // Column 'descripcion' cannot be null.
    }

    if ($agent_by_alias) {
        $agents_affected = 0;

        foreach ($idsAgents as $id) {
            if (!util_api_check_agent_and_print_error($id['id_agente'], 'string', 'AW')) {
                continue;
            }

            $idModule = modules_create_agent_module($id['id_agente'], $name, $values, true);

            if (!is_error($idModule)) {
                $agents_affected++;
            }
        }

        returnData('string', ['type' => 'string', 'data' => $agents_affected.' agents affected']);

        return;
    } else {
        $idModule = modules_create_agent_module($idAgent, $name, $values, true);
    }

    if (is_error($idModule)) {
        // TODO: Improve the error returning more info
        returnError('The SNMP module could not be created.');
    } else {
        returnData('string', ['type' => 'string', 'data' => $idModule]);
    }
}


/**
 * Update a SNMP module in agent. And return a message with the result of the operation.
 *
 * @param string            $id    Id of module to update.
 * @param $thrash1 Don't use.
 * @param array             $other it's array, $other as param is <id_agent>;<disabled>;
 *              <id_module_group>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;<ff_threshold>;
 *              <history_data>;<ip_target>;<module_port>;<snmp_version>;<snmp_community>;<snmp_oid>;<module_interval>;<post_process>;
 *              <min>;<max>;<custom_id>;<description>;<snmp3_priv_method>;<snmp3_priv_pass>;<snmp3_sec_level>;<snmp3_auth_method>;
 *              <snmp3_auth_user>;<snmp3_auth_pass>;<each_ff>;<ff_threshold_normal>;
 *              <ff_threshold_warning>;<ff_threshold_critical> in this order
 *              and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *              example:
 *
 *                 example (update snmp v: 3, snmp3_priv_method: AES, passw|authNoPriv|MD5|pepito_user|example_priv_passw)
 *
 *              api.php?op=set&op2=update_snmp_module&id=example_snmp_module_name&other=44|0|6|20|25||26|30||15|1|127.0.0.1|60|3|public|.1.3.6.1.2.1.1.1.0|180|50.00|10|60|0|SNMP%20module%20modified%20by%20API|AES|example_priv_passw|authNoPriv|MD5|pepito_user|example_auth_passw&other_mode=url_encode_separator_|
 *
 * @param $thrash3 Don't use
 */
function api_set_update_snmp_module($id_module, $thrash1, $other, $thrash3)
{
    if (defined('METACONSOLE')) {
        return;
    }

    if ($id_module == '') {
        returnError('The SNMP module could not be updated. Id_module cannot be left blank.');
        return;
    }

    if (!util_api_check_agent_and_print_error(
        modules_get_agentmodule_agent($id_module),
        'string',
        'AW'
    )
    ) {
        return;
    }

    // If we want to change the module to a new agent
    if ($other['data'][0] != '') {
        if (!util_api_check_agent_and_print_error($other['data'][0], 'string', 'AW')) {
            return;
        }

        $id_agent_old = db_get_value('id_agente', 'tagente_modulo', 'id_agente_modulo', $id_module);

        if ($id_agent_old != $other['data'][0]) {
            $id_module_exists = db_get_value_filter('id_agente_modulo', 'tagente_modulo', ['nombre' => $module_name, 'id_agente' => $other['data'][0]]);

            if ($id_module_exists) {
                returnError('The SNMP module could not be updated. Id_module exists in the new agent.');
                return;
            }
        }

        // Check if agent exists
        $check_id_agent = db_get_value('id_agente', 'tagente', 'id_agente', $other['data'][0]);
        if (!$check_id_agent) {
            returnError(
                'The SNMP module could not be updated. Id_agent does not exist.'
            );
            return;
        }
    }

    // SNMP version 3
    if ($other['data'][13] == '3') {
        if ($other['data'][22] != 'AES' and $other['data'][22] != 'DES') {
            returnError(
                'The SNMP module could not be updated. snmp3_priv_method does not exist. Set it to \'AES\' or \'DES\'. '
            );
            return;
        }

        if ($other['data'][24] != 'authNoPriv'
            and $other['data'][24] != 'authPriv'
            and $other['data'][24] != 'noAuthNoPriv'
        ) {
            returnError(
                'The SNMP module could not be updated. snmp3_sec_level does not exist. Set it to \'authNoPriv\' or \'authPriv\' or \'noAuthNoPriv\'. '
            );
            return;
        }

        if ($other['data'][25] != 'MD5' and $other['data'][25] != 'SHA') {
            returnError(
                'The SNMP module could not be updated. snmp3_auth_method does not exist. Set it to \'MD5\' or \'SHA\'. '
            );
            return;
        }

        $snmp_module_fields = [
            'id_agente',
            'disabled',
            'id_module_group',
            'min_warning',
            'max_warning',
            'str_warning',
            'min_critical',
            'max_critical',
            'str_critical',
            'min_ff_event',
            'history_data',
            'ip_target',
            'tcp_port',
            'tcp_send',
            'snmp_community',
            'snmp_oid',
            'module_interval',
            'post_process',
            'min',
            'max',
            'custom_id',
            'descripcion',
            'custom_string_1',
            'custom_string_2',
            'custom_string_3',
            'plugin_parameter',
            'plugin_user',
            'plugin_pass',
            'disabled_types_event',
            'each_ff',
            'min_ff_event_normal',
            'min_ff_event_warning',
            'min_ff_event_critical',
            'policy_linked',
            'ff_type',
        ];
    } else {
        $snmp_module_fields = [
            'id_agente',
            'disabled',
            'id_module_group',
            'min_warning',
            'max_warning',
            'str_warning',
            'min_critical',
            'max_critical',
            'str_critical',
            'min_ff_event',
            'history_data',
            'ip_target',
            'tcp_port',
            'tcp_send',
            'snmp_community',
            'snmp_oid',
            'module_interval',
            'post_process',
            'min',
            'max',
            'custom_id',
            'descripcion',
            'disabled_types_event',
            'each_ff',
            'min_ff_event_normal',
            'min_ff_event_warning',
            'min_ff_event_critical',
            'policy_linked',
            'ff_type',
        ];
    }

    $values = [];
    $cont = 0;
    foreach ($snmp_module_fields as $field) {
        if ($other['data'][$cont] != '') {
            $values[$field] = $other['data'][$cont];
        }

        $cont++;
    }

    $values['policy_linked'] = 0;
    $result_update = modules_update_agent_module($id_module, $values);

    if ($result_update < 0) {
        returnError('The SNMP module could not be updated.');
    } else {
        returnData('string', ['type' => 'string', 'data' => __('SNMP module updated.')]);
    }
}


/**
 * Create new network component.
 *
 * @param $id string Name of the network component.
 * @param $thrash1 Don't use.
 * @param array                                   $other it's array, $other as param is <network_component_type>;<description>;
 *                                    <module_interval>;<max_value>;<min_value>;<snmp_community>;<id_module_group>;<max_timeout>;
 *                                    <history_data>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;
 *                                    <ff_threshold>;<post_process>;<network_component_group>;<enable_unknown_events>;<each_ff>;
 *                                    <ff_threshold_normal>;<ff_threshold_warning>;<ff_threshold_critical>  in this
 *                                    order and separator char (after text ; ) and separator (pass in param
 *                                    othermode as othermode=url_encode_separator_<separator>)
 *                                    example:
 *
 *                                    api.php?op=set&op2=new_network_component&id=example_network_component_name&other=7|network%20component%20created%20by%20Api|300|30|10|public|3||1|10|20|str|21|30|str1|10|50.00|12&other_mode=url_encode_separator_|
 *
 * @param $thrash2 Don't use.
 */
function api_set_new_network_component($id, $thrash1, $other, $thrash2)
{
    global $config;
    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'PM')) {
        returnError('forbidden', 'string');
        return;
    }

    $idk = get_header('idk');
    if (is_management_allowed($idk) === false) {
        returnError('centralized');
        exit;
    }

    if ($id == '') {
        returnError('The network component could not be created. Network component name cannot be left blank.');
        return;
    }

    if ($other['data'][0] < 6 or $other['data'][0] > 18) {
        returnError('The network component could not be created. Incorrect value for Network component type field.');
        return;
    }

    if ($other['data'][17] == '') {
        returnError('The network component could not be created. Network component group cannot be left blank.');
        return;
    }

    $disabled_types_event = [];
    $disabled_types_event[EVENTS_GOING_UNKNOWN] = (int) !$other['data'][18];
    $disabled_types_event = json_encode($disabled_types_event);

    $values = [
        'description'           => $other['data'][1],
        'module_interval'       => $other['data'][2],
        'max'                   => $other['data'][3],
        'min'                   => $other['data'][4],
        'snmp_community'        => $other['data'][5],
        'id_module_group'       => $other['data'][6],
        'id_modulo'             => 2,
        'max_timeout'           => $other['data'][7],
        'history_data'          => $other['data'][8],
        'min_warning'           => $other['data'][9],
        'max_warning'           => $other['data'][10],
        'str_warning'           => $other['data'][11],
        'min_critical'          => $other['data'][12],
        'max_critical'          => $other['data'][13],
        'str_critical'          => $other['data'][14],
        'min_ff_event'          => $other['data'][15],
        'post_process'          => $other['data'][16],
        'id_group'              => $other['data'][17],
        'disabled_types_event'  => $disabled_types_event,
        'each_ff'               => $other['data'][19],
        'min_ff_event_normal'   => $other['data'][20],
        'min_ff_event_warning'  => $other['data'][21],
        'min_ff_event_critical' => $other['data'][22],
        'ff_type'               => $other['data'][23],
    ];

    $name_check = db_get_value('name', 'tnetwork_component', 'name', $id);

    if ($name_check !== false) {
        returnError('The network component could not be created. This network component already exist.');
        return;
    }

    $id = network_components_create_network_component($id, $other['data'][0], $other['data'][17], $values);

    if (!$id) {
        returnError('The network component could not be created.');
    } else {
        returnData('string', ['type' => 'string', 'data' => $id]);
    }
}


/**
 * Create new plugin component.
 *
 * @param $id string Name of the plugin component.
 * @param $thrash1 Don't use.
 * @param array                                  $other it's array, $other as param is <plugin_component_type>;<description>;
 *                                   <module_interval>;<max_value>;<min_value>;<module_port>;<id_module_group>;<id_plugin>;<max_timeout>;
 *                                   <history_data>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;
 *                                   <ff_threshold>;<post_process>;<plugin_component_group>;<enable_unknown_events>;
 *                                   <each_ff>;<ff_threshold_normal>;<ff_threshold_warning>;<ff_threshold_critical> in this
 *                                   order and separator char (after text ; ) and separator (pass in param
 *                                   othermode as othermode=url_encode_separator_<separator>)
 *                                   example:
 *
 *                                   api.php?op=set&op2=new_plugin_component&id=example_plugin_component_name&other=2|plugin%20component%20created%20by%20Api|300|30|10|66|3|2|example_user|example_pass|-p%20max||1|10|20|str|21|30|str1|10|50.00|12&other_mode=url_encode_separator_|
 *
 * @param $thrash2 Don't use.
 */
function api_set_new_plugin_component($id, $thrash1, $other, $thrash2)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'PM')) {
        returnError('forbidden', 'string');
        return;
    }

    $idk = get_header('idk');
    if (is_management_allowed($idk) === false) {
        returnError('centralized');
        exit;
    }

    if ($id == '') {
        returnError(
            'The plugin component could not be created. Plugin component name cannot be left blank.'
        );
        return;
    }

    if ($other['data'][7] == '') {
        returnError(
            'The plugin component could not be created. Incorrect value for Id plugin.'
        );
        return;
    }

    if ($other['data'][21] == '') {
        returnError(
            'The  plugin component could not be created. Plugin component group cannot be left blank.'
        );
        return;
    }

    $disabled_types_event = [];
    $disabled_types_event[EVENTS_GOING_UNKNOWN] = (int) !$other['data'][12];
    $disabled_types_event = json_encode($disabled_types_event);

    $values = [
        'description'           => $other['data'][1],
        'module_interval'       => $other['data'][2],
        'max'                   => $other['data'][3],
        'min'                   => $other['data'][4],
        'tcp_port'              => $other['data'][5],
        'id_module_group'       => $other['data'][6],
        'id_modulo'             => 4,
        'id_plugin'             => $other['data'][7],
        'plugin_user'           => $other['data'][8],
        'plugin_pass'           => $other['data'][9],
        'plugin_parameter'      => $other['data'][10],
        'max_timeout'           => $other['data'][11],
        'history_data'          => $other['data'][12],
        'min_warning'           => $other['data'][13],
        'max_warning'           => $other['data'][14],
        'str_warning'           => $other['data'][15],
        'min_critical'          => $other['data'][16],
        'max_critical'          => $other['data'][17],
        'str_critical'          => $other['data'][18],
        'min_ff_event'          => $other['data'][19],
        'post_process'          => $other['data'][20],
        'id_group'              => $other['data'][21],
        'disabled_types_event'  => $disabled_types_event,
        'each_ff'               => $other['data'][23],
        'min_ff_event_normal'   => $other['data'][24],
        'min_ff_event_warning'  => $other['data'][25],
        'min_ff_event_critical' => $other['data'][26],
        'ff_type'               => $other['data'][27],
    ];

    $name_check = db_get_value('name', 'tnetwork_component', 'name', $id);

    if ($name_check !== false) {
        returnError('The plugin component could not be created. This plugin component already exist.');
        return;
    }

    $id = network_components_create_network_component($id, $other['data'][0], $other['data'][21], $values);

    if (!$id) {
        returnError('The plugin component could not be created.');
    } else {
        returnData('string', ['type' => 'string', 'data' => $id]);
    }
}


/**
 * Create new SNMP component.
 *
 * @param $id string Name of the SNMP component.
 * @param $thrash1 Don't use.
 * @param array                                $other it's array, $other as param is <snmp_component_type>;<description>;
 *                                 <module_interval>;<max_value>;<min_value>;<id_module_group>;<max_timeout>;
 *                                 <history_data>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;
 *                                 <ff_threshold>;<post_process>;<snmp_version>;<snmp_oid>;<snmp_community>;
 *                                 <snmp3_auth_user>;<snmp3_auth_pass>;<module_port>;<snmp3_privacy_method>;<snmp3_privacy_pass>;<snmp3_auth_method>;<snmp3_security_level>;<snmp_component_group>;<enable_unknown_events>;
 *                                 <each_ff>;<ff_threshold_normal>;<ff_threshold_warning>;<ff_threshold_critical> in this
 *                                 order and separator char (after text ; ) and separator (pass in param
 *                                 othermode as othermode=url_encode_separator_<separator>)
 *                                 example:
 *
 *                                 api.php?op=set&op2=new_snmp_component&id=example_snmp_component_name&other=16|SNMP%20component%20created%20by%20Api|300|30|10|3||1|10|20|str|21|30|str1|15|50.00|3|.1.3.6.1.2.1.2.2.1.8.2|public|example_auth_user|example_auth_pass|66|AES|example_priv_pass|MD5|authNoPriv|12&other_mode=url_encode_separator_|
 *
 * @param $thrash2 Don't use.
 */
function api_set_new_snmp_component($id, $thrash1, $other, $thrash2)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if ($id == '') {
        returnError('The SNMP component could not be created. SNMP component name cannot be left blank.');
        return;
    }

    if (!check_acl($config['id_user'], 0, 'PM')) {
        returnError('forbidden', 'string');
        return;
    }

    if ($other['data'][0] < 15 or $other['data'][0] > 17) {
        returnError('The SNMP component could not be created. Incorrect value for Snmp component type field.');
        return;
    }

    if ($other['data'][25] == '') {
        returnError('The SNMP component could not be created. Snmp component group cannot be left blank.');
        return;
    }

    $disabled_types_event = [];
    $disabled_types_event[EVENTS_GOING_UNKNOWN] = (int) !$other['data'][27];
    $disabled_types_event = json_encode($disabled_types_event);

    // SNMP version 3
    if ($other['data'][16] == '3') {
        if ($other['data'][22] != 'AES' and $other['data'][22] != 'DES') {
            returnError(
                'The SNMP component could not be created. snmp3_priv_method does not exist. Set it to \'AES\' or \'DES\'. '
            );
            return;
        }

        if ($other['data'][25] != 'authNoPriv'
            and $other['data'][25] != 'authPriv'
            and $other['data'][25] != 'noAuthNoPriv'
        ) {
            returnError(
                'The SNMP component could not be created. snmp3_sec_level does not exist. Set it to \'authNoPriv\' or \'authPriv\' or \'noAuthNoPriv\'. '
            );
            return;
        }

        if ($other['data'][24] != 'MD5' and $other['data'][24] != 'SHA') {
            returnError(
                'The SNMP component could not be created. snmp3_auth_method does not exist. Set it to \'MD5\' or \'SHA\'. '
            );
            return;
        }

        $values = [
            'description'           => $other['data'][1],
            'module_interval'       => $other['data'][2],
            'max'                   => $other['data'][3],
            'min'                   => $other['data'][4],
            'id_module_group'       => $other['data'][5],
            'max_timeout'           => $other['data'][6],
            'history_data'          => $other['data'][7],
            'min_warning'           => $other['data'][8],
            'max_warning'           => $other['data'][9],
            'str_warning'           => $other['data'][10],
            'min_critical'          => $other['data'][11],
            'max_critical'          => $other['data'][12],
            'str_critical'          => $other['data'][13],
            'min_ff_event'          => $other['data'][14],
            'post_process'          => $other['data'][15],
            'tcp_send'              => $other['data'][16],
            'snmp_oid'              => $other['data'][17],
            'snmp_community'        => $other['data'][18],
            'plugin_user'           => $other['data'][19],
        // snmp3_auth_user
            'plugin_pass'           => $other['data'][20],
        // snmp3_auth_pass
            'tcp_port'              => $other['data'][21],
            'id_modulo'             => 2,
            'custom_string_1'       => $other['data'][22],
        // snmp3_privacy_method
            'custom_string_2'       => $other['data'][23],
        // snmp3_privacy_pass
            'plugin_parameter'      => $other['data'][24],
        // snmp3_auth_method
            'custom_string_3'       => $other['data'][25],
        // snmp3_security_level
            'id_group'              => $other['data'][26],
            'disabled_types_event'  => $disabled_types_event,
            'each_ff'               => $other['data'][28],
            'min_ff_event_normal'   => $other['data'][29],
            'min_ff_event_warning'  => $other['data'][30],
            'min_ff_event_critical' => $other['data'][31],
            'ff_type'               => $other['data'][32],
        ];
    } else {
        $values = [
            'description'           => $other['data'][1],
            'module_interval'       => $other['data'][2],
            'max'                   => $other['data'][3],
            'min'                   => $other['data'][4],
            'id_module_group'       => $other['data'][5],
            'max_timeout'           => $other['data'][6],
            'history_data'          => $other['data'][7],
            'min_warning'           => $other['data'][8],
            'max_warning'           => $other['data'][9],
            'str_warning'           => $other['data'][10],
            'min_critical'          => $other['data'][11],
            'max_critical'          => $other['data'][12],
            'str_critical'          => $other['data'][13],
            'min_ff_event'          => $other['data'][14],
            'post_process'          => $other['data'][15],
            'tcp_send'              => $other['data'][16],
            'snmp_oid'              => $other['data'][17],
            'snmp_community'        => $other['data'][18],
            'plugin_user'           => '',
            'plugin_pass'           => '',
            'tcp_port'              => $other['data'][21],
            'id_modulo'             => 2,
            'id_group'              => $other['data'][22],
            'disabled_types_event'  => $disabled_types_event,
            'each_ff'               => $other['data'][24],
            'min_ff_event_normal'   => $other['data'][25],
            'min_ff_event_warning'  => $other['data'][26],
            'min_ff_event_critical' => $other['data'][27],
            'ff_type'               => $other['data'][28],
        ];
    }

    $name_check = db_get_value('name', 'tnetwork_component', 'name', $id);

    if ($name_check !== false) {
        returnError('The SNMP component could not be created. This SNMP component already exist.');
        return;
    }

    $id = network_components_create_network_component($id, $other['data'][0], $other['data'][26], $values);

    if (!$id) {
        returnError('The SNMP component could not be created.');
    } else {
        returnData('string', ['type' => 'string', 'data' => $id]);
    }
}


/**
 * Create new local (data) component.
 *
 * @param $id string Name of the local component.
 * @param $thrash1 Don't use.
 * @param array                                 $other it's array, $other as param is <description>;<id_os>;
 *                                  <local_component_group>;<configuration_data>;<enable_unknown_events>;
 *                                  <ff_threshold>;<each_ff>;<ff_threshold_normal>;<ff_threshold_warning>;
 *                                  <ff_threshold_critical>;<ff_timeout>  in this order and separator char
 *                                  (after text ; ) and separator (pass in param othermode as
 *                                  othermode=url_encode_separator_<separator>)
 *                                  example:
 *
 *                                  api.php?op=set&op2=new_local_component&id=example_local_component_name&other=local%20component%20created%20by%20Api~5~12~module_begin%0dmodule_name%20example_local_component_name%0dmodule_type%20generic_data%0dmodule_exec%20ps%20|%20grep%20pid%20|%20wc%20-l%0dmodule_interval%202%0dmodule_end&other_mode=url_encode_separator_~
 *
 * @param $thrash2 Don't use.
 */
function api_set_new_local_component($id, $thrash1, $other, $thrash2)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    $idk = get_header('idk');
    if (is_management_allowed($idk) === false) {
        returnError('centralized');
        exit;
    }

    if ($id == '') {
        returnError(
            'The local component could not be created. Local component name cannot be left blank.'
        );
        return;
    }

    if (!check_acl($config['id_user'], 0, 'PM')) {
        returnError('forbidden', 'string');
        return;
    }

    if ($other['data'][1] == '') {
        returnError(
            'The local component could not be created. Local component group cannot be left blank.'
        );
        return;
    }

    $disabled_types_event = [];
    $disabled_types_event[EVENTS_GOING_UNKNOWN] = (int) !$other['data'][4];
    $disabled_types_event = json_encode($disabled_types_event);

    $values = [
        'description'                => $other['data'][0],
        'id_network_component_group' => $other['data'][2],
        'disabled_types_event'       => $disabled_types_event,
        'min_ff_event'               => $other['data'][5],
        'each_ff'                    => $other['data'][6],
        'min_ff_event_normal'        => $other['data'][7],
        'min_ff_event_warning'       => $other['data'][8],
        'min_ff_event_critical'      => $other['data'][9],
        'ff_timeout'                 => $other['data'][10],
        'ff_type'                    => $other['data'][11],
    ];

    $name_check = enterprise_hook(
        'local_components_get_local_components',
        [
            ['name' => $id],
            'name',
        ]
    );

    if ($name_check === ENTERPRISE_NOT_HOOK) {
        returnError(
            'The local component could not be created.'
        );
        return;
    }

    if ($name_check !== false) {
        returnError(
            'The local component could not be created. This local component already exist.'
        );
        return;
    }

    $id = enterprise_hook(
        'local_components_create_local_component',
        [
            $id,
            $other['data'][3],
            $other['data'][1],
            $values,
        ]
    );

    if (!$id) {
        returnError('The local component could not be created.');
    } else {
        returnData('string', ['type' => 'string', 'data' => $id]);
    }
}


/**
 * Get module data value from all agents filter by module name. And return id_agents, agent_name and module value.
 *
 * @param $id string Name of the module.
 * @param $thrash1 Don't use.
 * @param array                        $other Don't use.
 *                         example:
 *
 *                         api.php?op=get&op2=module_value_all_agents&id=example_module_name
 *
 * @param $thrash2 Don't use.
 */
function api_get_module_value_all_agents($id, $thrash1, $other, $thrash2)
{
    global $config;
    if (defined('METACONSOLE')) {
        return;
    }

    if ($id == '') {
        returnError(
            'Could not get the module value from all agents. Module name cannot be left blank.'
        );
        return;
    }

    $id_module = db_get_value('id_agente_modulo', 'tagente_modulo', 'nombre', $id);

    if ($id_module === false) {
        returnError(
            'Could not get the module value from all agents. Module name does not exist.'
        );
        return;
    }

    $groups = '1 = 1';
    if (!is_user_admin($config['id_user'])) {
        $user_groups = implode(',', array_keys(users_get_groups()));
        $groups = "(id_grupo IN ($user_groups) OR id_group IN ($user_groups))";
    }

    $sql = sprintf(
        "SELECT agent.id_agente, agent.alias, module_state.datos, agent.nombre
        FROM tagente agent LEFT JOIN tagent_secondary_group tasg ON agent.id_agente = tasg.id_agent, tagente_modulo module, tagente_estado module_state
        WHERE agent.id_agente = module.id_agente AND module.id_agente_modulo=module_state.id_agente_modulo AND module.nombre = '%s'
        AND %s",
        $id,
        $groups
    );

    $module_values = db_get_all_rows_sql($sql);

    if (!$module_values) {
        returnError('Could not get module values from all agents.');
    } else {
        $data = [
            'type' => 'array',
            'data' => $module_values,
        ];
        returnData('csv', $data, ';');
    }
}


/**
 * Create an alert template. And return the id of new template.
 *
 * @param string            $id    Name of alert template to add.
 * @param $thrash1 Don't use.
 * @param array             $other it's array, $other as param is <type>;<description>;<id_alert_action>;
 *              <field1>;<field2>;<field3>;<value>;<matches_value>;<max_value>;<min_value>;<time_threshold>;
 *              <max_alerts>;<min_alerts>;<time_from>;<time_to>;<monday>;<tuesday>;<wednesday>;
 *              <thursday>;<friday>;<saturday>;<sunday>;<recovery_notify>;<field2_recovery>;<field3_recovery>;<priority>;<id_group> in this order
 *              and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *              example:
 *
 *              example 1 (condition: regexp =~ /pp/, action: Mail to XXX, max_alert: 10, min_alert: 0, priority: WARNING, group: databases):
 *              api.php?op=set&op2=create_alert_template&id=pepito&other=regex|template%20based%20in%20regexp|1||||pp|1||||10|0|||||||||||||3&other_mode=url_encode_separator_|
 *
 *                 example 2 (condition: value is not between 5 and 10, max_value: 10.00, min_value: 5.00, time_from: 00:00:00, time_to: 15:00:00, priority: CRITICAL, group: Servers):
 *              api.php?op=set&op2=create_alert_template&id=template_min_max&other=max_min|template%20based%20in%20range|NULL||||||10|5||||00:00:00|15:00:00|||||||||||4|2&other_mode=url_encode_separator_|
 *
 * @param $thrash3 Don't use
 */
function api_set_create_alert_template($name, $thrash1, $other, $thrash3)
{
    global $config;

    $idk = get_header('idk');
    if (is_management_allowed($idk) === false) {
        returnError('centralized');
        exit;
    }

    if ($name == '') {
        returnError(
            'The alert template could not be created. Template name cannot be left blank.'
        );
        return;
    }

    $template_name = $name;

    $type = $other['data'][0];
    $id_group = $other['data'][26];

    if ($id_group == '') {
        returnError(
            'error_create_alert_template',
            __('Error creating alert template. Id_group cannot be left blank.')
        );
        return;
    }

    if (users_can_manage_group_all('LM')) {
        $groups = users_get_groups($config['id_user'], 'LM');
    } else {
        $groups = users_get_groups($config['id_user'], 'LM', false);
    }

    if ($groups[$id_group] === null) {
        returnError(
            'error_create_alert_template',
            __('Error creating alert template. Invalid id_group or the user has not enough permission to make this action.')
        );
        return;
    }

    for ($i = 29; $i < 54; $i++) {
        if ($other['data'][$i] === null) {
            $other['data'][$i] = '';
        }
    }

    $values = [
        'description'              => $other['data'][1],
        'field1'                   => $other['data'][3],
        'field2'                   => $other['data'][4],
        'field3'                   => $other['data'][5],
        'value'                    => $other['data'][6],
        'matches_value'            => $other['data'][7],
        'max_value'                => $other['data'][8],
        'min_value'                => $other['data'][9],
        'time_threshold'           => $other['data'][10],
        'max_alerts'               => $other['data'][11],
        'min_alerts'               => $other['data'][12],
        'time_from'                => $other['data'][13],
        'time_to'                  => $other['data'][14],
        'monday'                   => $other['data'][15],
        'tuesday'                  => $other['data'][16],
        'wednesday'                => $other['data'][17],
        'thursday'                 => $other['data'][18],
        'friday'                   => $other['data'][19],
        'saturday'                 => $other['data'][20],
        'sunday'                   => $other['data'][21],
        'recovery_notify'          => $other['data'][22],
        'field2_recovery'          => $other['data'][23],
        'field3_recovery'          => $other['data'][24],
        'priority'                 => $other['data'][25],
        'id_group'                 => $other['data'][26],
        'special_day'              => $other['data'][27],
        'min_alerts_reset_counter' => $other['data'][28],
        'field1_recovery'          => $other['data'][29],
        'field4'                   => $other['data'][30],
        'field5'                   => $other['data'][31],
        'field6'                   => $other['data'][32],
        'field7'                   => $other['data'][33],
        'field8'                   => $other['data'][34],
        'field9'                   => $other['data'][35],
        'field10'                  => $other['data'][36],
        'field11'                  => $other['data'][37],
        'field12'                  => $other['data'][38],
        'field13'                  => $other['data'][39],
        'field14'                  => $other['data'][40],
        'field15'                  => $other['data'][41],
        'field4_recovery'          => $other['data'][42],
        'field5_recovery'          => $other['data'][43],
        'field6_recovery'          => $other['data'][44],
        'field7_recovery'          => $other['data'][45],
        'field8_recovery'          => $other['data'][46],
        'field9_recovery'          => $other['data'][47],
        'field10_recovery'         => $other['data'][48],
        'field11_recovery'         => $other['data'][49],
        'field12_recovery'         => $other['data'][50],
        'field13_recovery'         => $other['data'][51],
        'field14_recovery'         => $other['data'][52],
        'field15_recovery'         => $other['data'][53],
    ];

    if ($other['data'][2] != '') {
        $values['id_alert_action'] = $other['data'][2];
    }

    $id_template = alerts_create_alert_template($template_name, $type, $values);

    if (is_error($id_template)) {
        // TODO: Improve the error returning more info
        returnError('The alert template could not be created.');
    } else {
        returnData('string', ['type' => 'string', 'data' => $id_template]);
    }
}


/**
 * Update an alert template. And return a message with the result of the operation.
 *
 * @param string            $id_template Id of the template to update.
 * @param $thrash1 Don't use.
 * @param array             $other       it's array, $other as param is <template_name>;<type>;<description>;<id_alert_action>;
 *                    <field1>;<field2>;<field3>;<value>;<matches_value>;<max_value>;<min_value>;<time_threshold>;
 *                    <max_alerts>;<min_alerts>;<time_from>;<time_to>;<monday>;<tuesday>;<wednesday>;
 *                    <thursday>;<friday>;<saturday>;<sunday>;<recovery_notify>;<field2_recovery>;<field3_recovery>;<priority>;<id_group> in this order
 *                    and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *
 *                    example:
 *
 *                   api.php?op=set&op2=update_alert_template&id=38&other=example_template_with_changed_name|onchange|changing%20from%20min_max%20to%20onchange||||||1||||5|1|||1|1|0|1|1|0|0|1|field%20recovery%20example%201|field%20recovery%20example%202|1|8&other_mode=url_encode_separator_|
 *
 * @param $thrash3 Don't use
 */
function api_set_update_alert_template($id_template, $thrash1, $other, $thrash3)
{
    global $config;

    if (is_metaconsole() === true) {
        return;
    }

    $idk = get_header('idk');
    if (is_management_allowed($idk) === false) {
        returnError('centralized');
        exit;
    }

    if ($id_template == '') {
        returnError(
            'The alert template could not be updated. Id_template cannot be left blank.'
        );
        return;
    }

    $result_template = alerts_get_alert_template($id_template);

    if (!$result_template) {
        returnError(
            'The alert template could not be updated. Id_template does not exist.'
        );
        return;
    }

    if (users_can_manage_group_all('LM')) {
        $groups = users_get_groups($config['id_user'], 'LM');
    } else {
        $groups = users_get_groups($config['id_user'], 'LM', false);
    }

    $id_group_org = $result_template['id_group'];
    if ($other['data'][27] === null) {
        $id_group_new = $id_group_org;
    } else {
        $id_group_new = $other['data'][27];
    }

    if ($groups[$id_group_org] === null || $groups[$id_group_new] === null) {
        returnError(
            'error_create_alert_template',
            __('Error updating alert template. Invalid id_group or the user has not enough permission to make this action.')
        );
        return;
    }

    $fields_template = [
        'name',
        'type',
        'description',
        'id_alert_action',
        'field1',
        'field2',
        'field3',
        'value',
        'matches_value',
        'max_value',
        'min_value',
        'time_threshold',
        'max_alerts',
        'min_alerts',
        'time_from',
        'time_to',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
        'recovery_notify',
        'field2_recovery',
        'field3_recovery',
        'priority',
        'id_group',
        'special_day',
        'min_alerts_reset_counter',
        'field1_recovery',
        'field4',
        'field5',
        'field6',
        'field7',
        'field8',
        'field9',
        'field10',
        'field11',
        'field12',
        'field13',
        'field14',
        'field15',
        'field4_recovery',
        'field5_recovery',
        'field6_recovery',
        'field7_recovery',
        'field8_recovery',
        'field9_recovery',
        'field10_recovery',
        'field11_recovery',
        'field12_recovery',
        'field13_recovery',
        'field14_recovery',
        'field15_recovery',
    ];

    $cont = 0;
    foreach ($fields_template as $field) {
        if ($other['data'][$cont] != '') {
            $values[$field] = $other['data'][$cont];
        }

        $cont++;
    }

    $id_template = alerts_update_alert_template($id_template, $values);

    if (is_error($id_template)) {
        // TODO: Improve the error returning more info
        returnError(
            'The alert template could not be updated.'
        );
    } else {
        returnData(
            'string',
            [
                'type' => 'string',
                'data' => __('Successful update of the alert template'),
            ]
        );
    }
}


/**
 * Delete an alert template. And return a message with the result of the operation.
 *
 * @param string            $id_template Id of the template to delete.
 * @param $thrash1 Don't use.
 * @param array             $other       Don't use
 *
 *                    example:
 *
 *                   api.php?op=set&op2=delete_alert_template&id=38
 *
 * @param $thrash3 Don't use
 */
function api_set_delete_alert_template($id_template, $thrash1, $other, $thrash3)
{
    global $config;

    if (is_metaconsole() === true) {
        return;
    }

    $idk = get_header('idk');
    if (is_management_allowed($idk) === false) {
        returnError('centralized');
        exit;
    }

    if ($id_template == '') {
        returnError(
            'The alert template could not be deleted. Id_template cannot be left blank.'
        );
        return;
    }

    $result_template = alerts_get_alert_template($id_template);

    if (!$result_template) {
        returnError(
            'error_update_alert_template',
            __('Error deleting alert template. Id_template doesn\'t exist.')
        );
        return;
    }

    if (users_can_manage_group_all('LM')) {
        $groups = users_get_groups($config['id_user'], 'LM');
    } else {
        $groups = users_get_groups($config['id_user'], 'LM', false);
    }

    $id_group = $result_template['id_group'];
    if ($groups[$id_group] === null) {
        returnError('forbidden', 'string');
        return;
    }

    $result = alerts_delete_alert_template($id_template);

    if ($result == 0) {
        // TODO: Improve the error returning more info
        returnError(
            'The alert template could not be deleted.'
        );
    } else {
        returnData(
            'string',
            [
                'type' => 'string',
                'data' => __('Successful delete of alert template.'),
            ]
        );
    }
}


/**
 * Get all alert tamplates, and print all the result like a csv.
 *
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array             $other it's array, but only <csv_separator> is available.
 *              example:
 *
 *              api.php?op=get&op2=all_alert_templates&return_type=csv&other=;
 *
 * @param $thrash3 Don't use.
 */
function api_get_all_alert_templates($thrash1, $thrash2, $other, $thrash3)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!isset($other['data'][0])) {
        $separator = ';';
        // by default
    } else {
        $separator = $other['data'][0];
    }

    if (!check_acl($config['id_user'], 0, 'LM')) {
        returnError('forbidden', 'csv');
        return;
    }

    $filter_templates = false;

    $template = alerts_get_alert_templates();

    if ($template !== false) {
        $data['type'] = 'array';
        $data['data'] = $template;
    }

    if (!$template) {
        returnError(
            'Could not get all alert templates'
        );
    } else {
        returnData('csv', $data, $separator);
    }
}


function api_get_all_alert_commands($thrash1, $thrash2, $other, $thrash3)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!isset($other['data'][0])) {
        $separator = ';';
        // by default
    } else {
        $separator = $other['data'][0];
    }

    if (!check_acl($config['id_user'], 0, 'LM')) {
        returnError('forbidden', 'csv');
        return;
    }

    $commands = db_get_all_rows_filter(
        'talert_commands',
        ['id_group' => array_keys(users_get_groups(false, 'LM'))]
    );

    if ($commands === false) {
        $commands = [];
    }

    if ($commands !== false) {
        $data['type'] = 'array';
        $data['data'] = $commands;
    }

    if (!$commands) {
        returnError(
            'Could not get all alert commands.'
        );
    } else {
        returnData('csv', $data, $separator);
    }
}


/**
 * Get an alert tamplate, and print the result like a csv.
 *
 * @param string            $id_template Id of the template to get.
 * @param $thrash1 Don't use.
 * @param array             $other       Don't use
 *
 *                    example:
 *
 *                   api.php?op=get&op2=alert_template&id=25
 *
 * @param $thrash3 Don't use
 */
function api_get_alert_template($id_template, $thrash1, $other, $thrash3)
{
    if (defined('METACONSOLE')) {
        return;
    }

    $filter_templates = false;

    if ($id_template != '') {
        $result_template = alerts_get_alert_template_name($id_template);

        if (!$result_template) {
            returnError(
                'Could not get alert template. Id_template does not exist.'
            );
            return;
        }

        $filter_templates = ['id' => $id_template];
    }

    $template = alerts_get_alert_templates(
        $filter_templates,
        [
            'id',
            'name',
            'description',
            'id_alert_action',
            'type',
            'id_group',
        ]
    );

    if ($template !== false) {
        $data['type'] = 'array';
        $data['data'] = $template;
    }

    if (!$template) {
        returnError(
            'Could not get alert template'
        );
    } else {
        returnData('csv', $data, ';');
    }
}


/**
 * List of alert actions.
 *
 * @param array                            $other it's array, $other as param is <action_name>;<separator_data> and separator (pass in param
 *                             othermode as othermode=url_encode_separator_<separator>)
 * @param $returnType (csv, string or json).
 *
 *  example:
 *
 *  api.php?op=get&op2=alert_actions&apipass=1234&user=admin&pass=pandora&other=Create|;&other_mode=url_encode_separator_|&return_type=json
 */
function api_get_alert_actions($thrash1, $thrash2, $other, $returnType)
{
    global $config;
    if (!check_acl($config['id_user'], 0, 'LM')) {
        returnError('forbidden', 'string');
        return;
    }

    if (!isset($other['data'][0])) {
        $other['data'][1] = '';
    }

    if (!isset($other['data'][1])) {
        $separator = ';';
        // by default
    } else {
        $separator = $other['data'][1];
    }

    $action_name = $other['data'][0];

    $filter = [];
    if (!is_user_admin($config['id_user'])) {
        $filter['talert_actions.id_group'] = array_keys(users_get_groups(false, 'LM'));
    }

    $filter['talert_actions.name'] = "%$action_name%";

    $actions = db_get_all_rows_filter(
        'talert_actions INNER JOIN talert_commands ON talert_actions.id_alert_command = talert_commands.id',
        $filter,
        'talert_actions.id, talert_actions.name'
    );
    if ($actions === false) {
        $actions = [];
    }

    if ($actions !== false) {
        $data['type'] = 'array';
        $data['data'] = $actions;
    }

    if (!$actions) {
        returnError(
            'Could not get alert actions.'
        );
    } else {
        returnData($returnType, $data, $separator);
    }
}


/**
 * Get module groups, and print all the result like a csv.
 *
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array             $other it's array, but only <csv_separator> is available.
 *              example:
 *
 *              api.php?op=get&op2=module_groups&return_type=csv&other=;
 *
 * @param $thrash3 Don't use.
 */
function api_get_module_groups($thrash1, $thrash2, $other, $thrash3)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'PM')) {
        returnError('forbidden', 'csv');
        return;
    }

    if (!isset($other['data'][0])) {
        $separator = ';';
        // by default
    } else {
        $separator = $other['data'][0];
    }

    $filter = false;

    $module_groups = @db_get_all_rows_filter('tmodule_group', $filter);

    if ($module_groups !== false) {
        $data['type'] = 'array';
        $data['data'] = $module_groups;
    }

    if (!$module_groups) {
        returnError('Could not get module groups.');
    } else {
        returnData('csv', $data, $separator);
    }
}


/**
 * Get plugins, and print all the result like a csv.
 *
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array             $other it's array, but only <csv_separator> is available.
 *              example:
 *
 *              api.php?op=get&op2=plugins&return_type=csv&other=;
 *
 * @param $thrash3 Don't use.
 */
function api_get_plugins($thrash1, $thrash2, $other, $thrash3)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'PM')) {
        returnError('forbidden', 'csv');
        return;
    }

    if (!isset($other['data'][0])) {
        $separator = ';';
        // by default
    } else {
        $separator = $other['data'][0];
    }

    $filter = false;
    $field_list = [
        'id',
        'name',
        'description',
        'max_timeout',
        'max_retries',
        'execute',
        'net_dst_opt',
        'net_port_opt',
        'user_opt',
        'pass_opt',
        'plugin_type',
        'macros',
        'parameters',
    ];

    $plugins = @db_get_all_rows_filter('tplugin', $filter, $field_list);

    if ($plugins !== false) {
        $data['type'] = 'array';
        $data['data'] = $plugins;
    }

    if (!$plugins) {
        returnError('Could not get plugins.');
    } else {
        returnData('csv', $data, $separator);
    }
}


/**
 * Create a network module from a network component. And return the id of new module.
 *
 * @param string            $agent_name     The name of the agent where the module will be created
 * @param string            $component_name The name of the network component
 * @param $thrash1 Don't use
 * @param $thrash2 Don't use
 */
function api_set_create_network_module_from_component($agent_name, $component_name, $other, $thrash2)
{
    if (defined('METACONSOLE')) {
        return;
    }

    $agent_by_alias = false;

    if ($other['data'][0] === '1') {
        $agent_by_alias = true;
    }

    if ($agent_by_alias) {
        $ids_agents = agents_get_agent_id_by_alias($agent_name);
    } else {
        $agent_id = agents_get_agent_id($agent_name);
    }

    if ($agent_by_alias) {
        foreach ($ids_agents as $id) {
            if (!util_api_check_agent_and_print_error($id['id_agente'], 'string', 'AW')) {
                return;
            }
        }
    } else {
        if (!util_api_check_agent_and_print_error($agent_id, 'string', 'AW')) {
            return;
        }
    }

    if ($agent_by_alias) {
        foreach ($ids_agents as $id) {
            if (!$id['id_agente']) {
                returnError('The network component could not be created. Agent does not exist.');
                return;
            }
        }
    } else {
        if (!$agent_id) {
            returnError('The network component could not be created. Agent does not exist.');
            return;
        }
    }

    $component = db_get_row('tnetwork_component', 'name', $component_name);

    if (!$component) {
        returnError('The network component could not be created. Network component does not exist.');
        return;
    }

    // Adapt fields to module structure
    unset($component['id_nc']);
    unset($component['id_group']);
    $component['id_tipo_modulo'] = $component['type'];
    unset($component['type']);
    $component['descripcion'] = $component['description'];
    unset($component['description']);
    unset($component['name']);
    $component['ip_target'] = agents_get_address($agent_id);

    // Create module
    if ($agent_by_alias) {
        $agents_affected = 0;

        foreach ($ids_agents as $id) {
            $module_id = modules_create_agent_module($id['id_agente'], $component_name, $component, true);

            if ($module_id) {
                $agents_affected++;
            }
        }

        returnData('string', ['type' => 'string', 'data' => __('%d agents affected', $agents_affected)]);
        return;
    } else {
        $module_id = modules_create_agent_module($agent_id, $component_name, $component, true);

        if (!$module_id) {
            returnError('The network component could not be created.');
            return;
        }

        return $module_id;
    }
}


/**
 * Assign a module to an alert template. And return the id of new relationship.
 *
 * @param string            $id_template Name of alert template to add.
 * @param $thrash1 Don't use.
 * @param array             $other       it's array, $other as param is <id_module>;<id_agent> in this order
 *                    and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *                    example:
 *
 *                    api.php?op=set&op2=create_module_template&id=1&other=1|10&other_mode=url_encode_separator_|
 *
 * @param $thrash3 Don't use
 */
function api_set_create_module_template($id, $thrash1, $other, $thrash3)
{
    if (defined('METACONSOLE')) {
        return;
    }

    if ($id == '') {
        returnError(
            'The module template could not be assigned. Id_template cannot be left blank.'
        );
        return;
    }

    if ($other['data'][0] == '') {
        returnError(
            'The module template could not be assigned. Id_module cannot be left blank.'
        );
        return;
    }

    if ($other['data'][1] == '') {
        returnError(
            'The module template could not be assigned. Id_agent cannot be left blank.'
        );
        return;
    }

    $id_module = $other['data'][0];
    $id_agent = $other['data'][1];

    if (!util_api_check_agent_and_print_error($id_agent, 'string', 'AW')) {
        return;
    }

    $result_template = alerts_get_alert_template($id);

    if (!$result_template) {
        returnError(
            'The module template could not be assigned. Id_template does not exist.'
        );
        return;
    }

    $result_agent = agents_get_name($id_agent);

    if (!$result_agent) {
        returnError(
            'The module template could not be assigned. Id_agent does not exist.'
        );
        return;
    }

    $result_module = db_get_value('nombre', 'tagente_modulo', 'id_agente_modulo', (int) $id_module);

    if (!$result_module) {
        returnError(
            'The module template could not be assigned. Id_module does not exist.'
        );
        return;
    }

    $id_template_module = alerts_create_alert_agent_module($id_module, $id);

    if (is_error($id_template_module)) {
        // TODO: Improve the error returning more info
        returnError(
            'The module template could not be assigned.'
        );
    } else {
        returnData('string', ['type' => 'string', 'data' => $id_template_module]);
    }
}


/**
 * Delete an module assigned to a template. And return a message with the result of the operation.
 *
 * @param string            $id    Id of the relationship between module and template (talert_template_modules) to delete.
 * @param $thrash1 Don't use.
 * @param array             $other Don't use
 *
 *              example:
 *
 *             api.php?op=set&op2=delete_module_template&id=38
 *
 * @param $thrash3 Don't use
 */
function api_set_delete_module_template($id, $thrash1, $other, $thrash3)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'AD')) {
        returnError('forbidden', 'string');
        return;
    }

    if ($id == '') {
        returnError('The module template could not deleted. Id_module_template cannot be left blank.');
        return;
    }

    $result_module_template = alerts_get_alert_agent_module($id);

    if (!$result_module_template) {
        returnError('The module template could not deleted. Id_module_template does not exist.');
        return;
    }

    $result = alerts_delete_alert_agent_module($id);

    if ($result == 0) {
        // TODO: Improve the error returning more info
        returnError('The module template could not deleted.');
    } else {
        returnData('string', ['type' => 'string', 'data' => __('Correct deleting of module template.')]);
    }
}


/**
 * Delete a module assigned to a template and return a message with the result of the operation.
 *
 * @param $id        Agent Name
 * @param $id2        Alert Template Name
 * @param $other    [0] : Module Name
 * @param $trash1    Don't use
 *
 *  example:
 *
 * api.php?op=set&op2=delete_module_template_by_names&id=my_latest_agent&id2=test_template&other=memfree
 */
function api_set_delete_module_template_by_names($id, $id2, $other, $trash1)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    $result = 0;

    if (! check_acl($config['id_user'], 0, 'AD')
        && ! check_acl($config['id_user'], 0, 'LM')
    ) {
        returnError('forbidden', 'string');
        return;
    }

    $agent_by_alias = false;

    if ($other['data'][1] === '1') {
        $agent_by_alias = true;
    }

    if ($agent_by_alias) {
        $idsAgents = agents_get_agent_id_by_alias($id);

        foreach ($idsAgents as $id) {
            if (!util_api_check_agent_and_print_error($id['id_agente'], 'string', 'AD')) {
                return;
            }
        }
    } else {
        $idAgent = agents_get_agent_id($id);

        if (!util_api_check_agent_and_print_error($idAgent, 'string', 'AD')) {
            return;
        }
    }

    $row = db_get_row_filter('talert_templates', ['name' => $id2]);

    if ($row === false) {
        returnError('Parameter error.');
        return;
    }

    $idTemplate = $row['id'];
    $idActionTemplate = $row['id_alert_action'];

    $delete_count = 0;

    if ($agent_by_alias) {
        foreach ($idsAgents as $id) {
            $idAgentModule = db_get_value_filter('id_agente_modulo', 'tagente_modulo', ['id_agente' => $id['id_agente'], 'nombre' => $other['data'][0]]);

            if ($idAgentModule === false) {
                continue;
            }

            $values = [
                'id_agent_module'   => $idAgentModule,
                'id_alert_template' => $idTemplate,
            ];

            $result = db_process_sql_delete('talert_template_modules', $values);

            if ($result != 0) {
                $delete_count++;
            }
        }

        returnError(sprintf('Module template has been deleted in %d agents.', $delete_count));
    } else {
        $idAgentModule = db_get_value_filter('id_agente_modulo', 'tagente_modulo', ['id_agente' => $idAgent, 'nombre' => $other['data'][0]]);

        if ($idAgentModule === false) {
            returnError('Parameter error.');
            return;
        }

        $values = [
            'id_agent_module'   => $idAgentModule,
            'id_alert_template' => $idTemplate,
        ];

        $result = db_process_sql_delete('talert_template_modules', $values);

        if ($result == 0) {
            // TODO: Improve the error returning more info
            returnError('The module template could not deleted');
        } else {
            returnData('string', ['type' => 'string', 'data' => __('Successful delete of module template.')]);
        }
    }
}


/**
 * Validate an alert
 *
 * @param  string $id1    Alert template name (eg. 'Warning condition')
 * @param  string $trash1 Do nnot use.
 * @param  array  $other  [1] id/name agent.
 *                       [2] id/name module
 *                       [3] Use agent/module alias.
 * @param  string $trash2 Do not use
 * @return void
 */
function api_set_validate_alert($id1, $trash1, $other, $trash2)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'LW')) {
        returnError('forbidden');
        return;
    }

    if ($id1 === '') {
        returnError(
            'error_validate_alert',
            __('Error validating alert. Id_template cannot be left blank.')
        );
        return;
    }

    if ($other['data'][0] == '') {
        returnError(
            'error_validate_alert',
            __('Error validating alert. Id_agent cannot be left blank.')
        );
        return;
    }

    if ($other['data'][1] == '') {
        returnError(
            'error_validate_alert',
            __('Error validating alert. Id_module cannot be left blank.')
        );
        return;
    }

    if ($other['data'][2] == 1) {
        $use_alias = true;
    }

    $values = [
        'alert_name'      => $id1,
        'id_agent'        => $other['data'][0],
        'id_agent_module' => $other['data'][1],
    ];

    if ($use_alias === true) {
        $id_agents = agents_get_agent_id_by_alias($values['id_agent']);

        foreach ($id_agents as $id) {
            $values['id_agent'] = $id['id_agente'];
            $values['id_agent_module'] = db_get_value_filter(
                'id_agente_modulo as id_module',
                'tagente_modulo',
                [
                    'id_agente' => $values['id_agent'],
                    'nombre'    => $values['id_agent_module'],
                ]
            );

            $id_template = db_get_value_filter(
                'id as id_template',
                'talert_templates',
                [
                    'name' => $values['alert_name'],
                ]
            );

             // Get alert id.
            $id_alert = db_get_value_filter(
                'id as id_alert',
                'talert_template_modules',
                [
                    'id_agent_module'   => $values['id_agent_module'],
                    'id_alert_template' => $id_template,
                ]
            );
        }

        $result = alerts_validate_alert_agent_module($id_alert);
    } else {
        $id_template = db_get_value_filter(
            'id as id_template',
            'talert_templates',
            [
                'name' => $values['alert_name'],
            ]
        );

        // Get alert id.
        $id_alert = db_get_value_filter(
            'id as id_alert',
            'talert_template_modules',
            [
                'id_agent_module'   => $values['id_agent_module'],
                'id_alert_template' => $id_template,
            ]
        );

        if ($id_alert === false) {
            returnError(
                'error_validate_alert',
                __('Error validating alert. Specified alert does not exist.')
            );
            return;
        }

        $result = alerts_validate_alert_agent_module($id_alert);
    }

    if ($result) {
        returnData('string', ['type' => 'string', 'data' => 'Alert succesfully validated']);
    } else {
        returnData('string', ['type' => 'string', 'data' => __('Error validating alert')]);
    }
}


/**
 * Validate all alerts. And return a message with the result of the operation.
 *
 * @param string Don't use.
 * @param $thrash1 Don't use.
 * @param array             $other Don't use
 *
 *              example:
 *
 *             api.php?op=set&op2=validate_all_alerts
 *
 * @param $thrash3 Don't use
 */
function api_set_validate_all_alerts($id, $thrash1, $other, $thrash3)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'LW')) {
        returnError('forbidden', 'string');
        return;
    }

    $agents = [];
    $raw_agents = agents_get_agents(false, 'id_agente');
    if ($raw_agents !== false) {
        foreach ($raw_agents as $agent) {
            $agents[] = $agent['id_agente'];
        }
    }

    $agents_string = implode(',', $agents);

    $sql = sprintf(
        '
        SELECT talert_template_modules.id
        FROM talert_template_modules
            INNER JOIN tagente_modulo t2
                ON talert_template_modules.id_agent_module = t2.id_agente_modulo
            INNER JOIN tagente t3
                ON t2.id_agente = t3.id_agente
            INNER JOIN talert_templates t4
                ON talert_template_modules.id_alert_template = t4.id
        WHERE t3.id_agente in (%s)',
        $agents_string
    );

    $alerts = db_get_all_rows_sql($sql);
    if ($alerts === false) {
        $alerts = [];
    }

    $total_alerts = count($alerts);
    $count_results = 0;
    foreach ($alerts as $alert) {
        $result = alerts_validate_alert_agent_module($alert['id'], false);

        if ($result) {
            $count_results++;
        }
    }

    if ($total_alerts > $count_results) {
        $errors = ($total_alerts - $count_results);
        returnError(sprintf('Could not validate all alerts. Failed %d', $errors));
    } else {
        returnData('string', ['type' => 'string', 'data' => __('Correct validation of all alerts (total %d).', $count_results)]);
    }
}


/**
 * Validate all policy alerts. And return a message with the result of the operation.
 *
 * @param string Don't use.
 * @param $thrash1 Don't use.
 * @param array             $other Don't use
 *
 *              example:
 *
 *             api.php?op=set&op2=validate_all_policy_alerts
 *
 * @param $thrash3 Don't use
 */
function api_set_validate_all_policy_alerts($id, $thrash1, $other, $thrash3)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'AW')) {
        returnError('forbidden', 'string');
        return;
    }

    // Get all policies
    $policies = enterprise_hook('policies_get_policies', [false, false, false]);

    if ($duplicated === ENTERPRISE_NOT_HOOK) {
        returnError('Could not validate all of alert policies.');
        return;
    }

    // Count of valid results
    $total_alerts = 0;
    $count_results = 0;
    // Check all policies
    foreach ($policies as $policy) {
        $policy_alerts = [];
        $policy_alerts = enterprise_hook('policies_get_alerts', [$policy['id'], false, false]);

        // Number of alerts in this policy
        if ($policy_alerts != false) {
            $partial_alerts = count($policy_alerts);
            // Added alerts of this policy to the total
            $total_alerts = ($total_alerts + $partial_alerts);
        }

        $result_pol_alerts = [];
        foreach ($policy_alerts as $policy_alert) {
            $result_pol_alerts[] = $policy_alert['id'];
        }

        $id_pol_alerts = implode(',', $result_pol_alerts);

        // If the policy has alerts
        if (count($result_pol_alerts) != 0) {
            $sql = sprintf(
                '
                SELECT id
                FROM talert_template_modules 
                WHERE id_policy_alerts IN (%s)',
                $id_pol_alerts
            );

            $id_alerts = db_get_all_rows_sql($sql);

            $result_alerts = [];
            foreach ($id_alerts as $id_alert) {
                $result_alerts[] = $id_alert['id'];
            }

            // Validate alerts of these modules
            foreach ($result_alerts as $result_alert) {
                $result = alerts_validate_alert_agent_module($result_alert, true);

                if ($result) {
                    $count_results++;
                }
            }
        }
    }

    // Check results
    if ($total_alerts > $count_results) {
        $errors = ($total_alerts - $count_results);
        returnError(sprintf('Could not validate all policy alerts. Failed %s', $errors));
    } else {
        returnData('string', ['type' => 'string', 'data' => __('Correct validation of all policy alerts.')]);
    }
}


/**
 * Stop a schedule downtime. And return a message with the result of the operation.
 *
 * @param string            $id    Id of the downtime to stop.
 * @param $thrash1 Don't use.
 * @param array             $other Don't use
 *
 *              example:
 *
 *             api.php?op=set&op2=stop_downtime&id=38
 *
 * @param $thrash3 Don't use
 */
function api_set_stop_downtime($id, $thrash1, $other, $thrash3)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'AD')) {
        returnError('forbidden', 'string');
        return;
    }

    if ($id == '') {
        returnError('Could not stop scheduled downtime. Id_downtime cannot be left blank.');
        return;
    }

    $date_time_stop = get_system_time();

    $sql = sprintf('SELECT  date_to, type_execution, executed FROM tplanned_downtime WHERE id=%d', $id);
    $data = db_get_row_sql($sql);

    if ($data['type_execution'] == 'periodically' && $data['executed'] == 1) {
        returnError('error_stop_downtime', __('Error stopping downtime. Periodical and running scheduled downtime cannot be stopped.'));
        return;
    }

    $values = [];
    $values['date_to'] = $date_time_stop;

    $result_update = db_process_sql_update('tplanned_downtime', $values, ['id' => $id]);
    if ($result_update == 0) {
        returnError('No action has been taken.');
    } else if ($result_update < 0) {
        returnError('Could not stop downtime.');
    } else {
        returnData('string', ['type' => 'string', 'data' => __('Downtime stopped.')]);
    }
}


function api_set_add_tag_module($id, $id2, $thrash1, $thrash2)
{
    if (defined('METACONSOLE')) {
        return;
    }

    $id_module = $id;
    $id_tag = $id2;

    if (!util_api_check_agent_and_print_error(modules_get_agentmodule_agent($id_module), 'string', 'AW')) {
        return;
    }

    $exists = db_get_row_filter(
        'ttag_module',
        [
            'id_agente_modulo' => $id_module,
            'id_tag'           => $id_tag,
        ]
    );

    if (empty($exists)) {
        db_process_sql_insert(
            'ttag_module',
            [
                'id_agente_modulo' => $id_module,
                'id_tag'           => $id_tag,
            ]
        );

        $exists = db_get_row_filter(
            'ttag_module',
            [
                'id_agente_modulo' => $id_module,
                'id_tag'           => $id_tag,
            ]
        );
    }

    if (empty($exists)) {
        returnError('The tag module could not be setted.');
    } else {
        returnData(
            'string',
            [
                'type' => 'string',
                'data' => 1,
            ]
        );
    }
}


function api_set_remove_tag_module($id, $id2, $thrash1, $thrash2)
{
    if (defined('METACONSOLE')) {
        return;
    }

    $id_module = $id;
    $id_tag = $id2;

    if (!util_api_check_agent_and_print_error(modules_get_agentmodule_agent($id_module), 'string', 'AW')) {
        return;
    }

    $row = db_get_row_filter(
        'ttag_module',
        [
            'id_agente_modulo' => $id_module,
            'id_tag'           => $id_tag,
        ]
    );

    $correct = 0;

    if (!empty($row)) {
        // Avoid to delete from policies
        if ($row['id_policy_module'] == 0) {
            $correct = db_process_sql_delete(
                'ttag_module',
                [
                    'id_agente_modulo' => $id_module,
                    'id_tag'           => $id_tag,
                ]
            );
        }
    }

    returnData(
        'string',
        [
            'type' => 'string',
            'data' => $correct,
        ]
    );
}


function api_set_tag($id, $thrash1, $other, $thrash3)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'PM')) {
        returnError('forbidden', 'string');
        return;
    }

    $idk = get_header('idk');
    if (is_management_allowed($idk) === false) {
        returnError('centralized');
        exit;
    }

    $values = [];
    $values['name'] = $id;
    $values['description'] = $other['data'][0];
    $values['url'] = $other['data'][1];
    $values['email'] = $other['data'][2];
    $values['phone'] = $other['data'][3];

    $id_tag = tags_create_tag($values);

    if (empty($id_tag)) {
        returnError('The tag could not be setted.');
    } else {
        returnData(
            'string',
            [
                'type' => 'string',
                'data' => $id_tag,
            ]
        );
    }
}


/**
 * Return all planned downtime.
 *
 * @param $thrash1 Don't use.
 * @param array                      $other it's array, $other as param is <name>;<id_group>;<type_downtime>;<type_execution>;<type_periodicity>; in this order
 *                       and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *                       example:
 *
 *                       api.php?op=get&op2=all_planned_downtimes&other=test|0|quiet|periodically|weekly&other_mode=url_encode_separator_|&return_type=json
 *
 * @param type of return json or csv.
 */


function api_get_all_planned_downtimes($thrash1, $thrash2, $other, $returnType='json')
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'AR')) {
        returnError('forbidden', $returnType);
        return;
    }

    $values = [];
    $values = ["name LIKE '%".$other['data'][0]."%'"];

    if (isset($other['data'][1]) && ($other['data'][1] != false )) {
        $values['id_group'] = $other['data'][1];
    }

    if (isset($other['data'][2]) && ($other['data'][2] != false)) {
        $values['type_downtime'] = $other['data'][2];
    }

    if (isset($other['data'][3]) && ($other['data'][3] != false)) {
        $values['type_execution'] = $other['data'][3];
    }

    if (isset($other['data'][4]) && ($other['data'][4] != false)) {
        $values['type_periodicity'] = $other['data'][4];
    }

    $returned = all_planned_downtimes($values);

    if ($returned === false) {
        returnError('Planned downtime was not retrieved');
        return;
    }

    returnData(
        $returnType,
        [
            'type' => 'array',
            'data' => $returned,
        ]
    );
}


/**
 * Return all items of planned downtime.
 *
 * @param $id id of planned downtime.
 * @param array                      $other it's array, $other as param is <name>;<id_group>;<type_downtime>;<type_execution>;<type_periodicity>; in this order
 *                       and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *
 *                       example:
 *
 *                        api.php?op=get&op2=planned_downtimes_items&other=test|0|quiet|periodically|weekly&other_mode=url_encode_separator_|&return_type=json
 *
 * @param type of return json or csv.
 */


function api_get_planned_downtimes_items($thrash1, $thrash2, $other, $returnType='json')
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'AR')) {
        returnError('forbidden', $returnType);
        return;
    }

    $values = [];
    $values = ["name LIKE '%".$other['data'][0]."%'"];

    if (isset($other['data'][1]) && ($other['data'][1] != false )) {
        $values['id_group'] = $other['data'][1];
    }

    if (isset($other['data'][2]) && ($other['data'][2] != false)) {
        $values['type_downtime'] = $other['data'][2];
    }

    if (isset($other['data'][3]) && ($other['data'][3] != false)) {
        $values['type_execution'] = $other['data'][3];
    }

    if (isset($other['data'][4]) && ($other['data'][4] != false)) {
        $values['type_periodicity'] = $other['data'][4];
    }

    $returned = all_planned_downtimes($values);

    $is_quiet = false;
    $return = [
        'list_index' => [
            'id_agents',
            'id_downtime',
            'all_modules',
        ],
    ];

    foreach ($returned as $downtime) {
        if ($downtime['type_downtime'] === 'quiet') {
            $is_quiet = true;
        }

        $filter['id_downtime'] = $downtime['id'];

        $items = planned_downtimes_items($filter);
        if ($items !== false) {
            $return[] = $items;
        }
    }

    // If the header is the unique element in the array, return an error
    if (count($return) == 1) {
        returnError('no_data_to_show', $returnType);
        return;
    }

    if ($is_quiet) {
        $return['list_index'][] = 'modules';
    }

    if ($returnType == 'json') {
        unset($return['list_index']);
    }

    returnData(
        $returnType,
        [
            'type' => 'array',
            'data' => $return,
        ]
    );
}


/**
 * Delete planned downtime.
 *
 * @param $id id of planned downtime.
 * @param $thrash1 not use.
 * @param $thrash2 not use.
 *
 *  api.php?op=set&op2=planned_downtimes_deleted &id=10
 *
 * @param type of return json or csv.
 */


function api_set_planned_downtimes_deleted($id, $thrash1, $thrash2, $returnType)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'AD')) {
        returnError('forbidden', $returnType);
        return;
    }

    $values = [];
    $values = ['id_downtime' => $id];

    $returned = delete_planned_downtimes($values);

    returnData(
        $returnType,
        [
            'type' => 'string',
            'data' => $returned,
        ]
    );
}


/**
 * Create a new planned downtime.
 * e.g.: api.php?op=set&op2=planned_downtimes_created&id=pepito&other=testing|08-22-2015|08-31-2015|0|1|1|1|1|1|1|1|17:06:00|19:06:00|1|31|quiet|periodically|weekly&other_mode=url_encode_separator_|
 *
 * @param $id name of planned downtime.
 * @param $thrash1 Don't use.
 * @param array                       $other Contains the following elements (in order):
 *                       <description>
 *                       <date_from>
 *                       <date_to>
 *                       <id_group>
 *                       <monday>
 *                       <tuesday>
 *                       <wednesday>
 *                       <thursday>
 *                       <friday>
 *                       <saturday>
 *                       <sunday>
 *                       <periodically_time_from>
 *                       <periodically_time_to>
 *                       <periodically_day_from>
 *                       <periodically_day_to>
 *                       <type_downtime>
 *                       <type_execution>
 *                       <type_periodicity>
 * @param $thrash3 Don't use.
 */
function api_set_planned_downtimes_created($id, $thrash1, $other, $thrash3)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'AD')) {
        returnError('forbidden', 'string');
        return;
    }

    $date_from = strtotime(html_entity_decode($other['data'][1].' '.$other['data'][11]));
    $date_to = strtotime(html_entity_decode($other['data'][2].' '.$other['data'][12]));
    $values = [];
    $values['name'] = $id;
    $values = [
        'name'                   => $id,
        'description'            => $other['data'][0],
        'date_from'              => $date_from,
        'date_to'                => $date_to,
        'id_group'               => $other['data'][3],
        'monday'                 => $other['data'][4],
        'tuesday'                => $other['data'][5],
        'wednesday'              => $other['data'][6],
        'thursday'               => $other['data'][7],
        'friday'                 => $other['data'][8],
        'saturday'               => $other['data'][9],
        'sunday'                 => $other['data'][10],
        'periodically_time_from' => $other['data'][11],
        'periodically_time_to'   => $other['data'][12],
        'periodically_day_from'  => $other['data'][13],
        'periodically_day_to'    => $other['data'][14],
        'type_downtime'          => $other['data'][15],
        'type_execution'         => $other['data'][16],
        'type_periodicity'       => $other['data'][17],
        'id_user'                => $other['data'][18],
    ];

    $returned = planned_downtimes_created($values);

    if (!$returned['return']) {
        returnError($returned['message']);
    } else {
        returnData(
            'string',
            [
                'type' => 'string',
                'data' => $returned['return'],
            ]
        );
    }
}


/**
 * Add new items to planned Downtime.
 * e.g.: api.php?op=set&op2=planned_downtimes_additem&id=123&other=1;2;3;4|Status;Unkown_modules&other_mode=url_encode_separator_|
 *
 * @param $id id of planned downtime.
 * @param $thrash1 Don't use.
 * @param array                     $other
 * The first index contains a list of agent Ids.
 * The second index contains a list of module names.
 * The list separator is the character ';'.
 * @param $thrash3 Don't use.
 */
function api_set_planned_downtimes_additem($id, $thrash1, $other, $thrash3)
{
    if (defined('METACONSOLE')) {
        return;
    }

    $total_agents = explode(';', $other['data'][0]);
    $agents = $total_agents;
    $bad_agents = [];
    $i = 0;
    foreach ($total_agents as $agent_id) {
        $result_agent = agents_check_access_agent($agent_id, 'AR');
        if (!$result_agent) {
            $bad_agents[] = $agent_id;
            unset($agents[$i]);
        }

        $i++;
    }

    if (isset($other['data'][1])) {
        $name_modules = explode(';', io_safe_output($other['data'][1]));
    } else {
        $name_modules = false;
    }

    if ($name_modules) {
        $all_modules = false;
    } else {
        $all_modules = true;
    }

    if (!empty($agents)) {
        $returned = planned_downtimes_add_items($id, $agents, $all_modules, $name_modules);
    }

    if (empty($agents)) {
        returnError('No agents to create scheduled downtime items');
    } else {
        if (!empty($returned['bad_modules'])) {
            $bad_modules = __("and this modules are doesn't exists or not applicable a this agents: ").implode(', ', $returned['bad_modules']);
        }

        if (!empty($returned['bad_agents'])) {
            $bad_agent = __('and this agents are generate problems: ').implode(', ', $returned['bad_agents']);
        }

        if (!empty($bad_agents)) {
            $agents_no_exists = __("and this agents with ids are doesn't exists: ").implode(', ', $bad_agents);
        }

        returnData(
            'string',
            [
                'type' => 'string',
                'data' => 'Successfully created items '.$bad_agent.' '.$bad_modules.' '.$agents_no_exists,
            ]
        );
    }
}


/**
 * Edit planned Downtime.
 * e.g.: api.php?op=set&op2=planned_downtimes_edit&apipass=1234&user=admin&pass=pandora&id=2&other=testing2|test2|2021/05/10|2021/06/12|19:03:03|19:55:00|0|0|0|0|0|0|0|0|1|31|quiet|once|weekly&other_mode=url_encode_separator_|
 *
 * @param $id id of planned downtime.
 * @param $thrash1 Don't use.
 * @param array                     $other
 * The first index contains a list of agent Ids.
 * The second index contains a list of module names.
 * The list separator is the character ';'.
 * @param $thrash3 Don't use.
 */
function api_set_planned_downtimes_edit($id, $thrash1, $other, $thrash3)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'PM')) {
        returnError('forbidden', 'string');
        return;
    }

    if ($id == '') {
        returnError(
            'id cannot be left blank.'
        );
        return;
    }

    if (db_get_value('id', 'tplanned_downtime', 'id', $id) === false) {
        returnError(
            'id does not exist'
        );
        return;
    }

    if ($other['data'] == '') {
        returnError(
            'data cannot be left blank.'
        );
        return;
    }

    $values = [];
    if (!empty($other['data'][0])) {
        $values['name'] = io_safe_input($other['data'][0]);
    }

    if (!empty($other['data'][1])) {
        $values['description'] = io_safe_input($other['data'][1]);
    }

    if (!empty($other['data'][2]) && !empty($other['data'][4])) {
        $date_from = strtotime(html_entity_decode($other['data'][2].' '.$other['data'][4]));
        $values['date_from'] = io_safe_input($date_from);
    }

    if (!empty($other['data'][4])) {
        $values['periodically_time_from'] = io_safe_input($other['data'][4]);
    }

    if (!empty($other['data'][3]) && !empty($other['data'][5])) {
        $date_to = strtotime(html_entity_decode($other['data'][3].' '.$other['data'][5]));
        $values['date_to'] = io_safe_input($date_to);
    }

    if (!empty($other['data'][5])) {
        $values['periodically_time_to'] = io_safe_input($other['data'][5]);
    }

    if ($other['data'][6] != '') {
        $values['id_group'] = io_safe_input($other['data'][6]);
    }

    if ($other['data'][7] != '') {
        $values['monday'] = io_safe_input($other['data'][7]);
    }

    if ($other['data'][8] != '') {
        $values['tuesday'] = io_safe_input($other['data'][8]);
    }

    if ($other['data'][9] != '') {
        $values['wednesday'] = io_safe_input($other['data'][9]);
    }

    if ($other['data'][10] != '') {
        $values['thursday'] = io_safe_input($other['data'][10]);
    }

    if ($other['data'][11] != '') {
        $values['friday'] = io_safe_input($other['data'][11]);
    }

    if ($other['data'][12] != '') {
        $values['saturday'] = io_safe_input($other['data'][12]);
    }

    if ($other['data'][13] != '') {
        $values['sunday'] = io_safe_input($other['data'][13]);
    }

    if (!empty($other['data'][14])) {
        $values['periodically_day_from'] = io_safe_input($other['data'][14]);
    }

    if (!empty($other['data'][15])) {
        $values['periodically_day_to'] = io_safe_input($other['data'][15]);
    }

    if (!empty($other['data'][16])) {
        $values['type_downtime'] = io_safe_input($other['data'][16]);
    }

    if (!empty($other['data'][17])) {
        $values['type_execution'] = io_safe_input($other['data'][17]);
    }

    if (!empty($other['data'][18])) {
        $values['type_periodicity'] = io_safe_input($other['data'][18]);
    }

    $res = db_process_sql_update('tplanned_downtime', $values, ['id' => $id]);

    if ($res === false) {
        returnError('Planned downtime could not be updated');
    } else {
        returnData(
            'string',
            [
                'type' => 'string',
                'data' => __('Planned downtime updated'),
            ]
        );
    }
}


/**
 * Delete agents in planned Downtime.
 * e.g.: pi.php?op=set&op2=planned_downtimes_delete_agents&apipass=1234&user=admin&pass=pandora&id=4&other=1;2;3&other_mode=url_encode_separator_|
 *
 * @param $id id of planned downtime.
 * @param $thrash1 Don't use.
 * @param array                     $other
 * The first index contains a list of agent Ids.
 * The list separator is the character ';'.
 * @param $thrash3 Don't use.
 */
function api_set_planned_downtimes_delete_agents($id, $thrash1, $other, $thrash3)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'PM')) {
        returnError('forbidden', 'string');
        return;
    }

    if ($id == '') {
        returnError(
            'id cannot be left blank.'
        );
        return;
    }

    if (db_get_value('id', 'tplanned_downtime', 'id', $id) === false) {
        returnError(
            'id does not exist'
        );
        return;
    }

    if ($other['data'] == '') {
        returnError(
            'data cannot be left blank.'
        );
        return;
    }

    if (!empty($other['data'][0])) {
        $agents = $other['data'];
        $results = false;
        foreach ($agents as $agent) {
            if (db_get_value_sql(sprintf('SELECT id from tplanned_downtime_agents WHERE id_agent = %d AND id_downtime = %d', $agent, $id)) !== false) {
                $result = db_process_sql_delete('tplanned_downtime_agents', ['id_agent' => $agent]);
                db_process_sql_delete('tplanned_downtime_modules', ['id_agent' => $agent]);

                if ($result == false) {
                    returnError(" Agent $agent could not be deleted.");
                } else {
                    $results = true;
                }
            } else {
                returnError(" Agent $agent is not in planned downtime.");
            }
        }

        if ($results) {
            returnData(
                'string',
                [
                    'type' => 'string',
                    'data' => __(' Agents deleted'),
                ]
            );
        }
    }
}


/**
 * Add agents planned Downtime.
 * e.g.: api.php?op=set&op2=planned_downtimes_add_agents&apipass=1234&user=admin&pass=pandora&id=4&other=1;2;3&other_mode=url_encode_separator_|
 *
 * @param $id id of planned downtime.
 * @param $thrash1 Don't use.
 * @param array                     $other
 * The first index contains a list of agent Ids.
 * The list separator is the character ';'.
 * @param $thrash3 Don't use.
 */
function api_set_planned_downtimes_add_agents($id, $thrash1, $other, $thrash3)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'PM')) {
        returnError('forbidden', 'string');
        return;
    }

    if ($id == '') {
        returnError(
            'id cannot be left blank.'
        );
        return;
    }

    if (db_get_value('id', 'tplanned_downtime', 'id', $id) === false) {
        returnError(
            'id does not exist'
        );
        return;
    }

    if ($other['data'] == '') {
        returnError(
            'data cannot be left blank.'
        );
        return;
    }

    if (!empty($other['data'][0])) {
        $agents = $other['data'];
        $results = false;
        foreach ($agents as $agent) {
            if (db_get_value_sql(sprintf('SELECT id from tplanned_downtime_agents tpd WHERE tpd.id_agent = %d AND id_downtime = %d', $agent, $id)) === false) {
                $res = db_process_sql_insert(
                    'tplanned_downtime_agents',
                    [
                        'id_agent'          => $agent,
                        'id_downtime'       => $id,
                        'all_modules'       => 0,
                        'manually_disabled' => 0,
                    ]
                );
                if ($res) {
                    $results = true;
                }
            } else {
                returnError(" Agent $agent is already at the planned downtime.");
            }
        }

        if ($results) {
            returnData(
                'string',
                [
                    'type' => 'string',
                    'data' => __(' Agents added'),
                ]
            );
        }
    }
}


/**
 * Update data module in policy. And return id from new module.
 *
 * @param string            $id    Id of the target policy module.
 * @param $thrash1 Don't use.
 * @param array             $other it's array, $other as param is <id_policy_module>;<description>;
 *             <id_module_group>;<min>;<max>;<post_process>;<module_interval>;<min_warning>;<max_warning>;<str_warning>;
 *             <min_critical>;<max_critical>;<str_critical>;<history_data>;<configuration_data>;
 *             <disabled_types_event>;<module_macros> in this order
 *              and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *              example:
 *
 *              example:
 *
 *              api.php?op=set&op2=update_data_module_policy&id=1&other=10~data%20module%20updated%20by%20Api~2~0~0~50.00~10~20~180~~21~35~~1~module_begin%0dmodule_name%20pandora_process%0dmodule_type%20generic_data%0dmodule_exec%20ps%20aux%20|%20grep%20pandora%20|%20wc%20-l%0dmodule_end&other_mode=url_encode_separator_~
 *
 * @param $thrash3 Don't use
 */
function api_set_update_data_module_policy($id, $thrash1, $other, $thrash3)
{
    if (defined('METACONSOLE')) {
        return;
    }

    $idk = get_header('idk');
    if (is_management_allowed($idk) === false) {
        returnError('centralized');
        exit;
    }

    if ($id == '') {
        returnError(
            'The data module could not be updated in policy. Id_policy cannot be left blank.'
        );
        return;
    }

    if (!util_api_check_agent_and_print_error($id, 'string', 'AW')) {
        return;
    }

    if ($other['data'][0] == '') {
        returnError(
            'The data module could not be updated in policy. Id_policy_module cannot be left blank.'
        );
        return;
    }

    // Check if the module exists
    $module_policy = enterprise_hook('policies_get_modules', [$id, ['id' => $other['data'][0]], 'id_module']);

    if ($module_policy === false) {
        returnError(
            'The data module could not be updated in policy. Module does not exist.'
        );
        return;
    }

    if ($module_policy[0]['id_module'] != 1) {
        returnError(
            'The data module could not be updated in policy. Module type is not network type.'
        );
        return;
    }

    $fields_data_module = [
        'id',
        'description',
        'id_module_group',
        'min',
        'max',
        'post_process',
        'module_interval',
        'min_warning',
        'max_warning',
        'str_warning',
        'min_critical',
        'max_critical',
        'str_critical',
        'history_data',
        'configuration_data',
        'disabled_types_event',
        'module_macros',
    ];

    $cont = 0;
    foreach ($fields_data_module as $field) {
        if ($other['data'][$cont] != '' and $field != 'id') {
            $values[$field] = $other['data'][$cont];
        }

        $cont++;
    }

    $result_update = enterprise_hook(
        'policies_update_module',
        [
            $other['data'][0],
            $values,
            false,
        ]
    );

    if ($result_update < 0) {
        returnError('The data module could not be updated in policy.');
    } else {
        returnData(
            'string',
            [
                'type' => 'string',
                'data' => __('Data policy module updated.'),
            ]
        );
    }
}


/**
 * Add network module to policy. And return a result message.
 *
 * @param string            $id    Id of the target policy.
 * @param $thrash1 Don't use.
 * @param array             $other it's array, $other as param is <module_name>;<id_module_type>;<description>;
 *             <id_module_group>;<min>;<max>;<post_process>;<module_interval>;<min_warning>;<max_warning>;<str_warning>;
 *             <min_critical>;<max_critical>;<str_critical>;<history_data>;<time_threshold>;<disabled>;<module_port>;
 *             <snmp_community>;<snmp_oid>;<custom_id>;<disabled_types_event>;<module_macros>;
 *             <each_ff>;<ff_threshold_normal>;<ff_threshold_warning>;<ff_threshold_critical> in this order
 *              and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *              example:
 *
 *              example:
 *
 *              api.php?op=set&op2=add_network_module_policy&id=1&other=network_module_policy_example_name|6|network%20module%20created%20by%20Api|2|0|0|50.00|180|10|20||21|35||1|15|0|66|||0&other_mode=url_encode_separator_|
 *
 * @param $thrash3 Don't use
 */
function api_set_add_network_module_policy($id, $thrash1, $other, $thrash3)
{
    if (defined('METACONSOLE')) {
        return;
    }

    $idk = get_header('idk');
    if (is_management_allowed($idk) === false) {
        returnError('centralized');
        exit;
    }

    if ($id == '') {
        returnError(
            'The network module could not be added to policy. Id_policy cannot be left blank.'
        );
        return;
    }

    if (enterprise_hook('policies_check_user_policy', [$id]) === false) {
        returnError('forbidden', 'string');
        return;
    }

    if ($other['data'][0] == '') {
        returnError(
            'The network module could not be added to policy. Module_name cannot be left blank.'
        );
        return;
    }

    if ($other['data'][1] < 6 or $other['data'][1] > 18) {
        returnError(
            'The network module could not be added to policy. Id_module_type is not correct for network modules.'
        );
        return;
    }

    // Check if the module is already in the policy
    $name_module_policy = enterprise_hook(
        'policies_get_modules',
        [
            $id,
            ['name' => $other['data'][0]],
            'name',
        ]
    );

    if ($name_module_policy === ENTERPRISE_NOT_HOOK) {
        returnError('The network module could not be added to policy.');
        return;
    }

    $disabled_types_event = [];
    $disabled_types_event[EVENTS_GOING_UNKNOWN] = (int) !$other['data'][21];
    $disabled_types_event = json_encode($disabled_types_event);

    $values = [];
    $values['id_tipo_modulo'] = $other['data'][1];
    $values['description'] = $other['data'][2];
    $values['id_module_group'] = $other['data'][3];
    $values['min'] = $other['data'][4];
    $values['max'] = $other['data'][5];
    $values['post_process'] = $other['data'][6];
    $values['module_interval'] = $other['data'][7];
    $values['min_warning'] = $other['data'][8];
    $values['max_warning'] = $other['data'][9];
    $values['str_warning'] = $other['data'][10];
    $values['min_critical'] = $other['data'][11];
    $values['max_critical'] = $other['data'][12];
    $values['str_critical'] = $other['data'][13];
    $values['history_data'] = $other['data'][14];
    $values['min_ff_event'] = $other['data'][15];
    $values['disabled'] = $other['data'][16];
    $values['tcp_port'] = $other['data'][17];
    $values['snmp_community'] = $other['data'][18];
    $values['snmp_oid'] = $other['data'][19];
    $values['custom_id'] = $other['data'][20];
    $values['disabled_types_event'] = $disabled_types_event;
    $values['module_macros'] = $other['data'][22];
    $values['each_ff'] = $other['data'][23];
    $values['min_ff_event_normal'] = $other['data'][24];
    $values['min_ff_event_warning'] = $other['data'][25];
    $values['min_ff_event_critical'] = $other['data'][26];
    $values['ff_type'] = $other['data'][27];

    if ($name_module_policy !== false) {
        if ($name_module_policy[0]['name'] == $other['data'][0]) {
            returnError('The network module could not be added to policy. The module is already in the policy.');
            return;
        }
    }

    $success = enterprise_hook('policies_create_module', [$other['data'][0], $id, 2, $values, false]);

    if ($success) {
        returnData('string', ['type' => 'string', 'data' => $success]);
    } else {
        returnError('The network module could not be added to policy.');
    }
}


/**
 * Update network module in policy. And return a result message.
 *
 * @param string            $id    Id of the target policy module.
 * @param $thrash1 Don't use.
 * @param array             $other it's array, $other as param is <id_policy_module>;<description>;
 *             <id_module_group>;<min>;<max>;<post_process>;<module_interval>;<min_warning>;<max_warning>;<str_warning>;
 *             <min_critical>;<max_critical>;<str_critical>;<history_data>;<time_threshold>;<disabled>;<module_port>;
 *             <snmp_community>;<snmp_oid>;<custom_id>;<disabled_types_event>;<module_macros> in this order
 *              and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *              example:
 *
 *              example:
 *
 *              api.php?op=set&op2=update_network_module_policy&id=1&other=14|network%20module%20updated%20by%20Api|2|0|0|150.00|300|10|20||21|35||1|15|0|66|||0&other_mode=url_encode_separator_|
 *
 * @param $thrash3 Don't use
 */
function api_set_update_network_module_policy($id, $thrash1, $other, $thrash3)
{
    if (defined('METACONSOLE')) {
        return;
    }

    $idk = get_header('idk');
    if (is_management_allowed($idk) === false) {
        returnError('centralized');
        exit;
    }

    if ($id == '') {
        returnError(
            'The network module could not be updated in policy. Id_policy cannot be left blank.'
        );
        return;
    }

    if ($other['data'][0] == '') {
        returnError(
            'The network module could not be updated in policy. Id_policy_module cannot be left blank.'
        );
        return;
    }

    // Check if the module exist.
    $module_policy = enterprise_hook('policies_get_modules', [$id, ['id' => $other['data'][0]], 'id_module']);

    if ($module_policy === false) {
        returnError(
            'The network module could not be updated in policy. Module does not exist.'
        );
        return;
    }

    if ($module_policy[0]['id_module'] != 2) {
        returnError(
            'The network module could not be updated in policy. Module type is not network type.'
        );
        return;
    }

    $fields_network_module = [
        'id',
        'description',
        'id_module_group',
        'min',
        'max',
        'post_process',
        'module_interval',
        'min_warning',
        'max_warning',
        'str_warning',
        'min_critical',
        'max_critical',
        'str_critical',
        'history_data',
        'min_ff_event',
        'disabled',
        'tcp_port',
        'snmp_community',
        'snmp_oid',
        'custom_id',
        'disabled_types_event',
        'module_macros',
    ];

    $cont = 0;
    foreach ($fields_network_module as $field) {
        if ($other['data'][$cont] != '' and $field != 'id') {
            $values[$field] = $other['data'][$cont];
        }

        $cont++;
    }

    $result_update = enterprise_hook('policies_update_module', [$other['data'][0], $values, false]);

    if ($result_update < 0) {
        returnError('The network module could not be updated in policy.');
    } else {
        returnData('string', ['type' => 'string', 'data' => __('Network policy module updated.')]);
    }
}


/**
 * Add plugin module to policy. And return id from new module.
 *
 * @param string            $id    Id of the target policy.
 * @param $thrash1 Don't use.
 * @param array             $other it's array, $other as param is <name_module>;<disabled>;<id_module_type>;
 *              <id_module_group>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;<ff_threshold>;
 *              <history_data>;<module_port>;<snmp_community>;<snmp_oid>;<module_interval>;<post_process>;
 *              <min>;<max>;<custom_id>;<description>;<id_plugin>;<plugin_user>;<plugin_pass>;<plugin_parameter>;
 *              <disabled_types_event>;<macros>;<module_macros>;<each_ff>;<ff_threshold_normal>;
 *              <ff_threshold_warning>;<ff_threshold_critical> in this order
 *              and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *              example:
 *
 *              api.php?op=set&op2=add_plugin_module_policy&id=1&other=example%20plugin%20module%20name|0|1|2|0|0||0|0||15|0|66|||300|50.00|0|0|0|plugin%20module%20from%20api|2|admin|pass|-p%20max&other_mode=url_encode_separator_|
 *
 * @param $thrash3 Don't use
 */
function api_set_add_plugin_module_policy($id, $thrash1, $other, $thrash3)
{
    if (defined('METACONSOLE')) {
        return;
    }

    $idk = get_header('idk');
    if (is_management_allowed($idk) === false) {
        returnError('centralized');
        exit;
    }

    if ($id == '') {
        returnError('The plugin module could not be added. Id_policy cannot be left blank.');
        return;
    }

    if (enterprise_hook('policies_check_user_policy', [$id]) === false) {
        returnError('forbidden', 'string');
        return;
    }

    if ($other['data'][0] == '') {
        returnError('The plugin module could not be added. Module_name cannot be left blank.');
        return;
    }

    if ($other['data'][21] == '') {
        returnError('The plugin module could not be added. Id_plugin cannot be left blank.');
        return;
    }

    // Check if the module is already in the policy.
    $name_module_policy = enterprise_hook('policies_get_modules', [$id, ['name' => $other['data'][0]], 'name']);

    if ($name_module_policy === ENTERPRISE_NOT_HOOK) {
        returnError('The plugin module could not be added.');
        return;
    }

    $disabled_types_event = [];
    $disabled_types_event[EVENTS_GOING_UNKNOWN] = (int) !$other['data'][25];
    $disabled_types_event = json_encode($disabled_types_event);

    $values = [];
    $values['disabled'] = $other['data'][1];
    $values['id_tipo_modulo'] = $other['data'][2];
    $values['id_module_group'] = $other['data'][3];
    $values['min_warning'] = $other['data'][4];
    $values['max_warning'] = $other['data'][5];
    $values['str_warning'] = $other['data'][6];
    $values['min_critical'] = $other['data'][7];
    $values['max_critical'] = $other['data'][8];
    $values['str_critical'] = $other['data'][9];
    $values['min_ff_event'] = $other['data'][10];
    $values['history_data'] = $other['data'][11];
    $values['tcp_port'] = $other['data'][12];
    $values['snmp_community'] = $other['data'][13];
    $values['snmp_oid'] = $other['data'][14];
    $values['module_interval'] = $other['data'][15];
    $values['post_process'] = $other['data'][16];
    $values['min'] = $other['data'][17];
    $values['max'] = $other['data'][18];
    $values['custom_id'] = $other['data'][19];
    $values['description'] = $other['data'][20];
    $values['id_plugin'] = $other['data'][21];
    $values['plugin_user'] = $other['data'][22];
    $values['plugin_pass'] = $other['data'][23];
    $values['plugin_parameter'] = $other['data'][24];
    $values['disabled_types_event'] = $disabled_types_event;
    $values['macros'] = base64_decode(str_replace('&#x20', '+', $other['data'][26]));
    $values['module_macros'] = $other['data'][27];
    $values['each_ff'] = $other['data'][28];
    $values['min_ff_event_normal'] = $other['data'][29];
    $values['min_ff_event_warning'] = $other['data'][30];
    $values['min_ff_event_critical'] = $other['data'][31];
    $values['ff_type'] = $other['data'][32];

    if ($name_module_policy !== false) {
        if ($name_module_policy[0]['name'] == $other['data'][0]) {
            returnError('The plugin module could not be added. The module is already in the policy.');
            return;
        }
    }

    $plugin = db_get_row('tplugin', 'id', $values['id_plugin']);
    if (empty($plugin)) {
        returnError('id_not_found');
        return;
    }

    $plugin_command_macros = $plugin['macros'];

    if (!empty($values['macros'])) {
        $macros = io_safe_input_json($values['macros']);
        if (empty($macros)) {
            returnError('JSON string in macros is invalid.');
            exit;
        }

        $values['macros'] = io_merge_json_value($plugin_command_macros, $macros);
    }

    $success = enterprise_hook('policies_create_module', [$other['data'][0], $id, 4, $values, false]);

    if ($success) {
        returnData('string', ['type' => 'string', 'data' => $success]);
    } else {
        returnError('The plugin module could not be added.');
    }
}


/**
 * Update plugin module in policy. And return a result message.
 *
 * @param string            $id    Id of the target policy module.
 * @param $thrash1 Don't use.
 * @param array             $other it's array, $other as param is <id_policy_module>;<disabled>;
 *              <id_module_group>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;<ff_threshold>;
 *              <history_data>;<module_port>;<snmp_community>;<snmp_oid>;<module_interval>;<post_process>;
 *              <min>;<max>;<custom_id>;<description>;<id_plugin>;<plugin_user>;<plugin_pass>;<plugin_parameter>;
 *              <disabled_types_event>;<macros>;<module_macros> in this order
 *              and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *              example:
 *
 *              example:
 *
 *              api.php?op=set&op2=update_plugin_module_policy&id=1&other=23|0|1|0|0||0|0||15|0|166|||180|150.00|0|0|0|plugin%20module%20updated%20from%20api|2|example_user|pass|-p%20min&other_mode=url_encode_separator_|
 *
 * @param $thrash3 Don't use
 */
function api_set_update_plugin_module_policy($id, $thrash1, $other, $thrash3)
{
    if (defined('METACONSOLE')) {
        return;
    }

    $idk = get_header('idk');
    if (is_management_allowed($idk) === false) {
        returnError('centralized');
        exit;
    }

    if ($id == '') {
        returnError(
            'The plugin module could not be updated in policy. Id_policy cannot be left blank.'
        );
        return;
    }

    if ($other['data'][0] == '') {
        returnError(
            'The plugin module could not be updated in policy. Id_policy_module cannot be left blank.'
        );
        return;
    }

    // Check if the module exist.
    $module_policy = enterprise_hook('policies_get_modules', [$id, ['id' => $other['data'][0]], 'id_module']);

    if ($module_policy === false) {
        returnError(
            'The plugin module could not be updated in policy. Module does not exist.'
        );
        return;
    }

    if ($module_policy[0]['id_module'] != 4) {
        returnError(
            'The plugin module could not be updated in policy. Module type is not network type.'
        );
        return;
    }

    $fields_plugin_module = [
        'id',
        'disabled',
        'id_module_group',
        'min_warning',
        'max_warning',
        'str_warning',
        'min_critical',
        'max_critical',
        'str_critical',
        'min_ff_event',
        'history_data',
        'tcp_port',
        'snmp_community',
        'snmp_oid',
        'module_interval',
        'post_process',
        'min',
        'max',
        'custom_id',
        'description',
        'id_plugin',
        'plugin_user',
        'plugin_pass',
        'plugin_parameter',
        'disabled_types_event',
        'macros',
        'module_macros',
    ];

    $cont = 0;
    foreach ($fields_plugin_module as $field) {
        if ($other['data'][$cont] != '' and $field != 'id') {
            $values[$field] = $other['data'][$cont];

            if ($field === 'macros') {
                $values[$field] = base64_decode($values[$field]);
            }
        }

        $cont++;
    }

    $result_update = enterprise_hook(
        'policies_update_module',
        [
            $other['data'][0],
            $values,
            false,
        ]
    );

    if ($result_update < 0) {
        returnError('The plugin module could not be updated in policy.');
    } else {
        returnData('string', ['type' => 'string', 'data' => __('Plugin policy module updated.')]);
    }
}


/**
 * Add module data configuration into agent configuration file
 *
 * @param string            $id_agent           Id of the agent
 * @param string            $module_name
 * @param array             $configuration_data is an array. The data in it is the new configuration data of the module
 * @param $thrash3 Don't use
 *
 * Call example:
 *
 *  api.php?op=set&op2=add_module_in_conf&user=admin&pass=pandora&id=9043&id2=example_name&other=bW9kdWxlX2JlZ2luCm1vZHVsZV9uYW1lIGV4YW1wbGVfbmFtZQptb2R1bGVfdHlwZSBnZW5lcmljX2RhdGEKbW9kdWxlX2V4ZWMgZWNobyAxOwptb2R1bGVfZW5k
 *
 * @return string 0 when success, -1 when error, -2 if already exist
 */
function api_set_add_module_in_conf($id_agent, $module_name, $configuration_data, $thrash3)
{
    if (defined('METACONSOLE')) {
        return;
    }

    if (!util_api_check_agent_and_print_error($id_agent, 'string', 'AW')) {
        return;
    }

    $new_configuration_data = io_safe_output(urldecode($configuration_data['data']));

    // Check if exist a current module with the same name in the conf file.
    $old_configuration_data = config_agents_get_module_from_conf($id_agent, io_safe_output($module_name));

    // If exists a module with same name, abort.
    if (!empty($old_configuration_data)) {
        returnError('-2');
        exit;
    }

    $result = enterprise_hook('config_agents_add_module_in_conf', [$id_agent, $new_configuration_data]);

    if ($result && $result !== ENTERPRISE_NOT_HOOK) {
        returnData('string', ['type' => 'string', 'data' => '0']);
    } else {
        returnError('-1');
    }
}


/**
 * Get module data configuration from agent configuration file
 *
 * @param string            $id_agent    Id of the agent
 * @param string            $module_name
 * @param $thrash2 Don't use
 * @param $thrash3 Don't use
 *
 * Call example:
 *
 *  api.php?op=get&op2=module_from_conf&user=admin&pass=pandora&id=9043&id2=example_name
 *
 * @return string Module data when success, empty when error
 */
function api_get_module_from_conf($id_agent, $module_name, $thrash2, $thrash3)
{
    if (defined('METACONSOLE')) {
        return;
    }

    if (!util_api_check_agent_and_print_error($id_agent, 'string')) {
        return;
    }

    $module_name = io_safe_output($module_name);
    $result = enterprise_hook(
        'config_agents_get_module_from_conf',
        [
            $id_agent,
            $module_name,
        ]
    );

    if ($result !== ENTERPRISE_NOT_HOOK && !empty($result)) {
        returnData('string', ['type' => 'string', 'data' => $result]);
    } else {
        returnError(sprintf('Remote config of module %s is not available', $module_name));
    }
}


/**
 * Delete module data configuration from agent configuration file
 *
 * @param string            $id_agent    Id of the agent
 * @param string            $module_name
 * @param $thrash2 Don't use
 * @param $thrash3 Don't use
 *
 * Call example:
 *
 *  api.php?op=set&op2=delete_module_in_conf&user=admin&pass=pandora&id=9043&id2=example_name
 *
 * @return string 0 when success, -1 when error
 */
function api_set_delete_module_in_conf($id_agent, $module_name, $thrash2, $thrash3)
{
    if (defined('METACONSOLE')) {
        return;
    }

    if (!util_api_check_agent_and_print_error($id_agent, 'string')) {
        return;
    }

    $result = config_agents_delete_module_in_conf($id_agent, $module_name);

    $result = enterprise_hook('config_agents_delete_module_in_conf', [$id_agent, $module_name]);

    if ($result && $result !== ENTERPRISE_NOT_HOOK) {
        returnData('string', ['type' => 'string', 'data' => '0']);
    } else {
        returnError('-1');
    }
}


/**
 * Update module data configuration from agent configuration file
 *
 * @param string            $id_agent           Id of the agent
 * @param string            $module_name
 * @param array             $configuration_data is an array. The data in it is the new configuration data of the module
 * @param $thrash3 Don't use
 *
 * Call example:
 *
 *  api.php?op=set&op2=update_module_in_conf&user=admin&pass=pandora&id=9043&id2=example_name&other=bW9kdWxlX2JlZ2luCm1vZHVsZV9uYW1lIGV4YW1wbGVfbmFtZQptb2R1bGVfdHlwZSBnZW5lcmljX2RhdGEKbW9kdWxlX2V4ZWMgZWNobyAxOwptb2R1bGVfZW5k
 *
 * @return string 0 when success, 1 when no changes, -1 when error, -2 if doesnt exist
 */
function api_set_update_module_in_conf($id_agent, $module_name, $configuration_data_serialized, $thrash3)
{
    if (defined('METACONSOLE')) {
        return;
    }

    if (!util_api_check_agent_and_print_error($id_agent, 'string')) {
        return;
    }

    $new_configuration_data = io_safe_output(urldecode($configuration_data_serialized['data']));

    // Get current configuration.
    $old_configuration_data = config_agents_get_module_from_conf($id_agent, io_safe_output($module_name));

    // If not exists
    if (empty($old_configuration_data)) {
        returnError('-2');
        exit;
    }

    // If current configuration and new configuration are equal, abort.
    if ($new_configuration_data == $old_configuration_data) {
        returnData('string', ['type' => 'string', 'data' => '1']);
        exit;
    }

    $result = enterprise_hook('config_agents_update_module_in_conf', [$id_agent, $old_configuration_data, $new_configuration_data]);

    if ($result && $result !== ENTERPRISE_NOT_HOOK) {
        returnData('string', ['type' => 'string', 'data' => '0']);
    } else {
        returnError('-1');
    }
}


/**
 * Add SNMP module to policy. And return id from new module.
 *
 * @param string            $id    Id of the target policy.
 * @param $thrash1 Don't use.
 * @param array             $other it's array, $other as param is <name_module>;<disabled>;<id_module_type>;
 *              <id_module_group>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;<ff_threshold>;
 *              <history_data>;<module_port>;<snmp_version>;<snmp_community>;<snmp_oid>;<module_interval>;<post_process>;
 *              <min>;<max>;<custom_id>;<description>;<snmp3_priv_method>;<snmp3_priv_pass>;<snmp3_sec_level>;<snmp3_auth_method>;
 *              <snmp3_auth_user>;<snmp3_auth_pass>;<disabled_types_event>;;<each_ff>;<ff_threshold_normal>;
 *              <ff_threshold_warning>;<ff_threshold_critical> in this order
 *              and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *              example:
 *
 *              api.php?op=set&op2=add_snmp_module_policy&id=1&other=example%20SNMP%20module%20name|0|15|2|0|0||0|0||15|1|66|3|public|.1.3.6.1.2.1.1.1.0|180|50.00|10|60|0|SNMP%20module%20modified%20by%20API|AES|example_priv_passw|authNoPriv|MD5|pepito_user|example_auth_passw&other_mode=url_encode_separator_|
 *
 * @param $thrash3 Don't use
 */
function api_set_add_snmp_module_policy($id, $thrash1, $other, $thrash3)
{
    if (defined('METACONSOLE')) {
        return;
    }

    $idk = get_header('idk');
    if (is_management_allowed($idk) === false) {
        returnError('centralized');
        exit;
    }

    if ($id == '') {
        returnError('The SNMP module could not be added to policy. Id_policy cannot be left blank.');
        return;
    }

    if (enterprise_hook('policies_check_user_policy', [$id]) === false) {
        returnError('forbidden', 'string');
        return;
    }

    if ($other['data'][0] == '') {
        returnError('The SNMP module could not be added to policy. Module_name cannot be left blank.');
        return;
    }

    // Check if the module is already in the policy
    $name_module_policy = enterprise_hook('policies_get_modules', [$id, ['name' => $other['data'][0]], 'name']);

    if ($name_module_policy === ENTERPRISE_NOT_HOOK) {
        returnError('The SNMP module could not be added to policy.');
        return;
    }

    if ($other['data'][2] < 15 or $other['data'][2] > 18) {
        returnError('The SNMP module could not be added to policy. Id_module_type is not correct for SNMP modules.');
        return;
    }

    $disabled_types_event = [];
    $disabled_types_event[EVENTS_GOING_UNKNOWN] = (int) !$other['data'][28];
    $disabled_types_event = json_encode($disabled_types_event);

    // SNMP version 3
    if ($other['data'][13] == '3') {
        if ($other['data'][22] != 'AES' and $other['data'][22] != 'DES') {
            returnError('The SNMP module could not be added to policy. snmp3_priv_method does not exist. Set it to \'AES\' or \'DES\'. ');
            return;
        }

        if ($other['data'][24] != 'authNoPriv' and $other['data'][24] != 'authPriv' and $other['data'][24] != 'noAuthNoPriv') {
            returnError('The SNMP module could not be added to policy. snmp3_sec_level does not exist. Set it to \'authNoPriv\' or \'authPriv\' or \'noAuthNoPriv\'. ');
            return;
        }

        if ($other['data'][25] != 'MD5' and $other['data'][25] != 'SHA') {
            returnError('The SNMP module could not be added to policy. snmp3_auth_method does not exist. Set it to \'MD5\' or \'SHA\'. ');
            return;
        }

        $values = [
            'disabled'              => $other['data'][1],
            'id_tipo_modulo'        => $other['data'][2],
            'id_module_group'       => $other['data'][3],
            'min_warning'           => $other['data'][4],
            'max_warning'           => $other['data'][5],
            'str_warning'           => $other['data'][6],
            'min_critical'          => $other['data'][7],
            'max_critical'          => $other['data'][8],
            'str_critical'          => $other['data'][9],
            'min_ff_event'          => $other['data'][10],
            'history_data'          => $other['data'][11],
            'tcp_port'              => $other['data'][12],
            'tcp_send'              => $other['data'][13],
            'snmp_community'        => $other['data'][14],
            'snmp_oid'              => $other['data'][15],
            'module_interval'       => $other['data'][16],
            'post_process'          => $other['data'][17],
            'min'                   => $other['data'][18],
            'max'                   => $other['data'][19],
            'custom_id'             => $other['data'][20],
            'description'           => $other['data'][21],
            'custom_string_1'       => $other['data'][22],
            'custom_string_2'       => $other['data'][23],
            'custom_string_3'       => $other['data'][24],
            'plugin_parameter'      => $other['data'][25],
            'plugin_user'           => $other['data'][26],
            'plugin_pass'           => $other['data'][27],
            'disabled_types_event'  => $disabled_types_event,
            'each_ff'               => $other['data'][29],
            'min_ff_event_normal'   => $other['data'][30],
            'min_ff_event_warning'  => $other['data'][31],
            'min_ff_event_critical' => $other['data'][32],
            'ff_type'               => $other['data'][33],
        ];
    } else {
        $values = [
            'disabled'              => $other['data'][1],
            'id_tipo_modulo'        => $other['data'][2],
            'id_module_group'       => $other['data'][3],
            'min_warning'           => $other['data'][4],
            'max_warning'           => $other['data'][5],
            'str_warning'           => $other['data'][6],
            'min_critical'          => $other['data'][7],
            'max_critical'          => $other['data'][8],
            'str_critical'          => $other['data'][9],
            'min_ff_event'          => $other['data'][10],
            'history_data'          => $other['data'][11],
            'tcp_port'              => $other['data'][12],
            'tcp_send'              => $other['data'][13],
            'snmp_community'        => $other['data'][14],
            'snmp_oid'              => $other['data'][15],
            'module_interval'       => $other['data'][16],
            'post_process'          => $other['data'][17],
            'min'                   => $other['data'][18],
            'max'                   => $other['data'][19],
            'custom_id'             => $other['data'][20],
            'description'           => $other['data'][21],
            'disabled_types_event'  => $disabled_types_event,
            'each_ff'               => $other['data'][23],
            'min_ff_event_normal'   => $other['data'][24],
            'min_ff_event_warning'  => $other['data'][25],
            'min_ff_event_critical' => $other['data'][26],
            'ff_type'               => $other['data'][27],
        ];
    }

    if ($name_module_policy !== false) {
        if ($name_module_policy[0]['name'] == $other['data'][0]) {
            returnError('The SNMP module could not be added to policy. The module is already in the policy.');
            return;
        }
    }

    $success = enterprise_hook('policies_create_module', [$other['data'][0], $id, 2, $values, false]);

    if ($success) {
        returnData('string', ['type' => 'string', 'data' => $success]);
    } else {
        returnError('The SNMP module could not be added to policy');
    }

}


/**
 * Update SNMP module in policy. And return a result message.
 *
 * @param string            $id    Id of the target policy module.
 * @param $thrash1 Don't use.
 * @param array             $other it's array, $other as param is <id_policy_module>;<disabled>;
 *              <id_module_group>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;<ff_threshold>;
 *              <history_data>;<module_port>;<snmp_version>;<snmp_community>;<snmp_oid>;<module_interval>;<post_process>;
 *              <min>;<max>;<custom_id>;<description>;<snmp3_priv_method>;<snmp3_priv_pass>;<snmp3_sec_level>;<snmp3_auth_method>;
 *              <snmp3_auth_user>;<snmp3_auth_pass> in this order
 *              and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *              example:
 *
 *              example:
 *
 *              api.php?op=set&op2=update_snmp_module_policy&id=1&other=14|0|2|0|0||0|0||30|1|66|3|nonpublic|.1.3.6.1.2.1.1.1.0|300|150.00|10|60|0|SNMP%20module%20updated%20by%20API|DES|example_priv_passw|authPriv|MD5|pepito_user|example_auth_passw&other_mode=url_encode_separator_|
 *
 * @param $thrash3 Don't use
 */
function api_set_update_snmp_module_policy($id, $thrash1, $other, $thrash3)
{
    if (defined('METACONSOLE')) {
        return;
    }

    $idk = get_header('idk');
    if (is_management_allowed($idk) === false) {
        returnError('centralized');
        exit;
    }

    if ($id == '') {
        returnError('The SNMP module could not be updated in policy. Id_policy cannot be left blank.');
        return;
    }

    if ($other['data'][0] == '') {
        returnError('The SNMP module could not be updated in policy. Id_policy_module cannot be left blank.');
        return;
    }

    // Check if the module exists
    $module_policy = enterprise_hook('policies_get_modules', [$id, ['id' => $other['data'][0]], 'id_module']);

    if ($module_policy === false) {
        returnError('The SNMP module could not be updated in policy. Module does not exist.');
        return;
    }

    if ($module_policy[0]['id_module'] != 2) {
        returnError('The SNMP module could not be updated in policy. Module type is not SNMP type.');
        return;
    }

    // SNMP version 3
    if ($other['data'][12] == '3') {
        if ($other['data'][21] != 'AES' and $other['data'][21] != 'DES') {
            returnError(
                'The SNMP module could not be updated in policy. snmp3_priv_method does not exist. Set it to \'AES\' or \'DES\'. '
            );

            return;
        }

        if ($other['data'][23] != 'authNoPriv'
            && $other['data'][23] != 'authPriv'
            && $other['data'][23] != 'noAuthNoPriv'
        ) {
            returnError(
                'The SNMP module could not be updated in policy. snmp3_sec_level does not exist. Set it to \'authNoPriv\' or \'authPriv\' or \'noAuthNoPriv\'. '
            );

            return;
        }

        if ($other['data'][24] != 'MD5' and $other['data'][24] != 'SHA') {
            returnError(
                'The SNMP module could not be updated in policy. snmp3_auth_method does not exist. Set it to \'MD5\' or \'SHA\'. '
            );

            return;
        }

        $fields_snmp_module = [
            'id',
            'disabled',
            'id_module_group',
            'min_warning',
            'max_warning',
            'str_warning',
            'min_critical',
            'max_critical',
            'str_critical',
            'min_ff_event',
            'history_data',
            'tcp_port',
            'tcp_send',
            'snmp_community',
            'snmp_oid',
            'module_interval',
            'post_process',
            'min',
            'max',
            'custom_id',
            'description',
            'custom_string_1',
            'custom_string_2',
            'custom_string_3',
            'plugin_parameter',
            'plugin_user',
            'plugin_pass',
        ];
    } else {
        $fields_snmp_module = [
            'id',
            'disabled',
            'id_module_group',
            'min_warning',
            'max_warning',
            'str_warning',
            'min_critical',
            'max_critical',
            'str_critical',
            'min_ff_event',
            'history_data',
            'tcp_port',
            'tcp_send',
            'snmp_community',
            'snmp_oid',
            'module_interval',
            'post_process',
            'min',
            'max',
            'custom_id',
            'description',
        ];
    }

    $cont = 0;
    foreach ($fields_snmp_module as $field) {
        if ($other['data'][$cont] != '' and $field != 'id') {
            $values[$field] = $other['data'][$cont];
        }

        $cont++;
    }

    $result_update = enterprise_hook(
        'policies_update_module',
        [
            $other['data'][0],
            $values,
            false,
        ]
    );

    if ($result_update < 0) {
        returnError('The SNMP module could not be updated in policy');
    } else {
        returnData(
            'string',
            [
                'type' => 'string',
                'data' => __('SNMP policy module updated.'),
            ]
        );
    }
}


/**
 * Remove an agent from a policy by agent id.
 *
 * @param $id Id of the policy
 * @param $thrash1 Don't use.
 * @param $other
 * @param $thrash2 Don't use.
 *
 * Example:
 * api.php?op=set&op2=remove_agent_from_policy&apipass=1234&user=admin&pass=pandora&id=11&id2=2
 */
function api_set_remove_agent_from_policy_by_id($id, $thrash1, $other, $thrash2)
{
    if ($id == '' || !$id) {
        returnError('The agent could not be deleted from policy. ID Policy cannot be left blank.');
        return;
    }

    $idk = get_header('idk');
    if (is_management_allowed($idk) === false) {
        returnError('centralized');
        exit;
    }

    if ($other['data'][0] == '' || !$other['data'][0]) {
        returnError('The agent could not be deleted from policy. Agent cannot be left blank.');
        return;
    }

    // Require node id if is metaconsole
    if (is_metaconsole() && $other['data'][1] == '') {
        returnError('The agent could not be deleted from policy. Node ID cannot be left blank.');
        return;
    }

    return remove_agent_from_policy($id, false, [$other['data'][0], $other['data'][1]]);
}


/**
 * Remove an agent from a policy by agent name.
 *
 * @param $id Id of the policy
 * @param $thrash1 Don't use.
 * @param $other
 * @param $thrash2 Don't use.
 *
 * Example:
 * api.php?op=set&op2=remove_agent_from_policy&apipass=1234&user=admin&pass=pandora&id=11&id2=2
 */
function api_set_remove_agent_from_policy_by_name($id, $thrash1, $other, $thrash2)
{
    if ($id == '' || !$id) {
        returnError('The agent could not be deleted from policy. Policy cannot be left blank.');
        return;
    }

    $idk = get_header('idk');
    if (is_management_allowed($idk) === false) {
        returnError('centralized');
        exit;
    }

    if ($other['data'][0] == '' || !$other['data'][0]) {
        returnError('The agent could not be deleted from policy. Agent name cannot be left blank.');
        return;
    }

    return remove_agent_from_policy($id, true, [$other['data'][0]]);
}


/**
 * Create a new group. And return the id_group of the new group.
 *
 * @param string            $id    Name of the new group.
 * @param $thrash1 Don't use.
 * @param array             $other it's array, $other as param is <icon_name>;<id_group_parent>;<description> in this order
 *              and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *              example:
 *
 *                 example 1 (with parent group: Servers)
 *
 *              api.php?op=set&op2=create_group&id=example_group_name&other=applications|1&other_mode=url_encode_separator_|
 *
 *                 example 2 (without parent group)
 *
 *                 api.php?op=set&op2=create_group&id=example_group_name2&other=computer|&other_mode=url_encode_separator_|
 *
 * @param $thrash3 Don't use
 */
function api_set_create_group($id, $thrash1, $other, $thrash3)
{
    global $config;

    if (is_metaconsole()) {
        return;
    }

    $idk = get_header('idk');
    if (is_management_allowed($idk) === false) {
        returnError('centralized');
        exit;
    }

    $group_name = $id;

    if (!check_acl($config['id_user'], 0, 'PM')) {
        returnError('forbidden', 'string');
        return;
    }

    if ($id == '') {
        returnError(
            'The group could not be created. Group_name cannot be left blank.'
        );
        return;
    }

    if ($other['data'][0] == '') {
        returnError(
            'The group could not be created. Icon_name cannot be left blank.'
        );
        return;
    }

    $safe_other_data = io_safe_input($other['data']);

    if ($safe_other_data[1] != '') {
            $group = groups_get_group_by_id($safe_other_data[1]);

        if ($group == false) {
            returnError(
                'The group could not be created. Id_parent_group does not exist.'
            );
            return;
        }
    }

    if ($safe_other_data[1] != '') {
        $values = [
            'icon'        => $safe_other_data[0],
            'parent'      => $safe_other_data[1],
            'description' => $safe_other_data[2],
        ];
    } else {
        $values = [
            'icon'        => $safe_other_data[0],
            'description' => $safe_other_data[2],
        ];
    }

    $values['propagate'] = $safe_other_data[3];
    $values['disabled'] = $safe_other_data[4];
    $values['custom_id'] = $safe_other_data[5];
    $values['contact'] = $safe_other_data[6];
    $values['other'] = $safe_other_data[7];
    $values['max_agents'] = $safe_other_data[8];
    $values['password'] = $safe_other_data[9];

    $id_group = groups_create_group($group_name, $values);

    if (is_error($id_group)) {
        // TODO: Improve the error returning more info
        returnError('The group could not be created');
    } else {
        if (defined('METACONSOLE')) {
            $servers = db_get_all_rows_sql(
                'SELECT *
                FROM tmetaconsole_setup
                WHERE disabled = 0'
            );

            if ($servers === false) {
                $servers = [];
            }

            $result = [];
            foreach ($servers as $server) {
                // If connection was good then retrieve all data server
                if (metaconsole_connect($server) == NOERR) {
                    $values['id_grupo'] = $id_group;
                    $id_group_node = groups_create_group($group_name, $values);
                }

                metaconsole_restore_db();
            }
        }

        returnData('string', ['type' => 'string', 'data' => $id_group]);
    }
}


/**
 * Update a group.
 *
 * @param integer           $id    Group ID
 * @param $thrash2 Don't use.
 * @param array             $other it's array, $other as param is <group_name>;<icon_name>;<parent_group_id>;<propagete>;<disabled>;<custom_id>;<description>;<contact>;<other> in this order
 *              and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *              example:
 *
 *                 api.php?op=set&op2=update_group&id=example_group_id&other=New%20Name|application|2|new%20description|1|0|custom%20id||&other_mode=url_encode_separator_|
 *
 * @param $thrash3 Don't use
 */
function api_set_update_group($id_group, $thrash2, $other, $thrash3)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'PM')) {
        returnError('forbidden', 'string');
        return;
    }

    $idk = get_header('idk');
    if (is_management_allowed($idk) === false) {
        returnError('centralized');
        exit;
    }

    if (db_get_value('id_grupo', 'tgrupo', 'id_grupo', $id_group) === false) {
        returnError('There is no group with the ID provided');
        return;
    }

    if (!check_acl($config['id_user'], $id_group, 'PM')) {
        returnError('forbidden', 'string');
        return;
    }

    $name = $other['data'][0];
    $icon = $other['data'][1];
    $parent = $other['data'][2];
    $description = $other['data'][3];
    $propagate = $other['data'][4];
    $disabled = $other['data'][5];
    $custom_id = $other['data'][6];
    $contact = $other['data'][7];
    $otherData = $other['data'][8];
    $maxAgents = $other['data'][9];

    $return = db_process_sql_update(
        'tgrupo',
        [
            'nombre'      => $name,
            'icon'        => $icon,
            'parent'      => $parent,
            'description' => $description,
            'propagate'   => $propagate,
            'disabled'    => $disabled,
            'custom_id'   => $custom_id,
            'contact'     => $contact,
            'other'       => $otherData,
            'max_agents'  => $maxAgents,
        ],
        ['id_grupo' => $id_group]
    );

    returnData(
        'string',
        [
            'type' => 'string',
            'data' => (int) ((bool) $return),
        ]
    );
}


/**
 * Delete a group
 *
 * @param integer           $id Group ID
 * @param $thrash1 Don't use.
 * @param $thrast2 Don't use.
 * @param $thrash3 Don't use.
 */
function api_set_delete_group($id_group, $thrash2, $other, $thrash3)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'PM')) {
        returnError('forbidden', 'string');
        return;
    }

    $idk = get_header('idk');
    if (is_management_allowed($idk) === false) {
        returnError('centralized');
        exit;
    }

    $group = db_get_row_filter('tgrupo', ['id_grupo' => $id_group]);
    if (!$group) {
        returnError('The group could not be deleted. ID does not exist.');
        return;
    }

    if (!check_acl($config['id_user'], $id_group, 'PM')) {
        returnError('forbidden', 'string');
        return;
    }

    $usedGroup = groups_check_used($id_group);
    if ($usedGroup['return']) {
        returnError(
            'The delete action could not be performed. The group is not empty (used in '.implode(', ', $usedGroup['tables']).').'
        );
        return;
    }

    db_process_sql_update('tgrupo', ['parent' => $group['parent']], ['parent' => $id_group]);
    db_process_sql_delete('tgroup_stat', ['id_group' => $id_group]);

    $result = db_process_sql_delete('tgrupo', ['id_grupo' => $id_group]);

    if (!$result) {
        returnError('The delete action could not be performed.');
    } else {
        returnData('string', ['type' => 'string', 'data' => __('Successful deletion')]);
    }
}


/**
 * Create a new netflow filter. And return the id_group of the new group.
 *
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array             $other it's array, $other as param is <filter_name>;<group_id>;<filter>;<aggregate_by>;<output_format> in this order
 *              and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *
 *             Possible values of 'aggregate_by' field: dstip,dstport,none,proto,srcip,srcport
 *             Possible values of 'output_format' field: kilobytes,kilobytespersecond,megabytes,megabytespersecond
 *
 *              example:
 *
 *                 api.php?op=set&op2=create_netflow_filter&id=Filter name&other=9|host 192.168.50.3 OR host 192.168.50.4 or HOST 192.168.50.6|dstport|kilobytes&other_mode=url_encode_separator_|
 *
 * @param $thrash3 Don't use
 */
function api_set_create_netflow_filter($thrash1, $thrash2, $other, $thrash3)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'AW')) {
        returnError('forbidden', 'string');
        return;
    }

    if ($other['data'][0] == '') {
        returnError('The netflow filter could not be created. Filter name cannot be left blank.');
        return;
    }

    if ($other['data'][1] == '') {
        returnError('The netflow filter could not be created. Group id cannot be left blank.');
        return;
    } else {
        $group = groups_get_group_by_id($other['data'][1]);

        if ($group == false) {
            returnError('The netflow filter could not be created. Id_group does not exist.');
            return;
        }

        if (!check_acl($config['id_user'], $other['data'][1], 'AW')) {
            returnError('forbidden', 'string');
            return;
        }
    }

    if ($other['data'][2] == '') {
        returnError('The netflow filter could not be created. Filter cannot be left blank.');
        return;
    }

    if ($other['data'][3] == '') {
        returnError('The netflow filter could not be created. Aggregate_by cannot be left blank.');
        return;
    }

    if ($other['data'][4] == '') {
        returnError('The netflow filter could not be created. Output_format cannot be left blank.');
        return;
    }

    $values = [
        'id_name'         => $other['data'][0],
        'id_group'        => $other['data'][1],
        'advanced_filter' => $other['data'][2],
        'aggregate'       => $other['data'][3],
        'output'          => $other['data'][4],
    ];

    // Save filter args
    $values['filter_args'] = netflow_get_filter_arguments($values);

    $id = db_process_sql_insert('tnetflow_filter', $values);

    if ($id === false) {
        returnError('The netflow filter could not be created');
    } else {
        returnData('string', ['type' => 'string', 'data' => $id]);
    }
}


/**
 * Get module data in CSV format.
 *
 * @param integer              $id    The ID of module in DB.
 * @param $thrash1 Don't use.
 * @param array                $other it's array, $other as param is <separator>;<period>;<tstart>;<tend> in this order
 *                 and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *                 example:
 *
 *                 api.php?op=get&op2=module_data&id=17&other=;|604800|20161201T13:40|20161215T13:40&other_mode=url_encode_separator_|
 *
 * @param $returnType Don't use.
 */
function api_get_module_data($id, $thrash1, $other, $returnType)
{
    if (defined('METACONSOLE')) {
        return;
    }

    if (!util_api_check_agent_and_print_error(modules_get_agentmodule_agent($id), $returnType)) {
        return;
    }

    $separator = ';';
    $tstart = null;
    $tend = null;
    $periodSeconds = null;
    if (is_array($other) === true && is_array($other['data']) === true) {
        $separator = $other['data'][0];
        $periodSeconds = $other['data'][1];
        $tstart = $other['data'][2];
        $tend = $other['data'][3];
    }

    if (($tstart != '') && ($tend != '')) {
        try {
            $dateStart = explode('T', $tstart);
            $dateYearStart = substr($dateStart[0], 0, 4);
            $dateMonthStart = substr($dateStart[0], 4, 2);
            $dateDayStart = substr($dateStart[0], 6, 2);
            $date_start = $dateYearStart.'-'.$dateMonthStart.'-'.$dateDayStart.' '.$dateStart[1];
            $date_start = new DateTime($date_start);
            $date_start = $date_start->format('U');

            $dateEnd = explode('T', $tend);
            $dateYearEnd = substr($dateEnd[0], 0, 4);
            $dateMonthEnd = substr($dateEnd[0], 4, 2);
            $dateDayEnd = substr($dateEnd[0], 6, 2);
            $date_end = $dateYearEnd.'-'.$dateMonthEnd.'-'.$dateDayEnd.' '.$dateEnd[1];
            $date_end = new DateTime($date_end);
            $date_end = $date_end->format('U');
        } catch (Exception $e) {
            returnError('Wrong date format.');
            return;
        }
    } else {
        if ($periodSeconds !== null) {
            $date_end = get_system_time();
            $date_start = (get_system_time() - $periodSeconds);
        } else {
            $date_end = get_system_time();
            $result = modules_get_first_date($id, $tstart);
            if ($result !== false) {
                $date_start = $result['first_utimestamp'];
            }
        }
    }

    $data['type'] = 'array';
    $data['list_index'] = [
        'utimestamp',
        'datos',
    ];

    $data['data'] = array_reduce(
        db_uncompress_module_data($id, $date_start, $date_end),
        function ($carry, $item) {
            if (is_array($item['data']) === true) {
                foreach ($item['data'] as $i => $v) {
                    $carry[] = [
                        'utimestamp' => $v['utimestamp'],
                        'datos'      => $v['datos'],
                    ];
                }
            }

            return $carry;
        },
        []
    );

    if ($data === false) {
        returnError('Module data query error.', $returnType);
    } else if ($data['data'] == '') {
        returnError('no_data_to_show', $returnType);
    } else {
        // returnData('csv_head', $data, $separator);
        returnData('csv', $data, $separator);
    }
}


/**
 * Return a image file of sparse graph of module data in a period time.
 *
 * @param integer           $id    id of a module data.
 * @param $thrash1 Don't use.
 * @param array             $other it's array, $other as param is <period>;<width>;<height>;<label>;<start_date>; in this order
 *              and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *              example:
 *
 *              api.php?op=get&op2=graph_module_data&id=17&other=604800|555|245|pepito|2009-12-07&other_mode=url_encode_separator_|
 *
 * @param $thrash2 Don't use.
 */
function api_get_graph_module_data($id, $thrash1, $other, $thrash2)
{
    if (defined('METACONSOLE')) {
        return;
    }

    if (!util_api_check_agent_and_print_error(modules_get_agentmodule_agent($id), 'string')) {
        return;
    }

    $period = $other['data'][0];
    $width = $other['data'][1];
    $height = $other['data'][2];
    $graph_type = 'sparse';
    $draw_alerts = 0;
    $draw_events = 0;
    $zoom = 1;
    $label = $other['data'][3];
    $start_date = $other['data'][4];
    $date = strtotime($start_date);

    $homeurl = '../';
    $ttl = 1;

    global $config;

    $params = [
        'agent_module_id'    => $id,
        'period'             => $period,
        'show_events'        => $draw_events,
        'width'              => $width,
        'height'             => $height,
        'show_alerts'        => $draw_alerts,
        'date'               => $date,
        'unit'               => '',
        'baseline'           => 0,
        'return_data'        => 0,
        'show_title'         => true,
        'only_image'         => true,
        'homeurl'            => $homeurl,
        'compare'            => false,
        'show_unknown'       => true,
        'backgroundColor'    => 'white',
        'percentil'          => null,
        'type_graph'         => $config['type_module_charts'],
        'fullscale'          => false,
        'return_img_base_64' => true,
    ];

    $image = grafico_modulo_sparse($params);

    header('Content-type: text/html');
    returnData('string', ['type' => 'string', 'data' => '<img src="data:image/jpeg;base64,'.$image.'">']);
}


/**
 * Create new user.
 *
 * @param string            $id    String username for user login in Pandora
 * @param $thrash2 Don't use.
 * @param array             $other it's array, $other as param is <fullname>;<firstname>;<lastname>;<middlename>;
 *              <email>;<phone>;<languages>;<comments> in this order and separator char
 *              (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *              example:
 *
 *              api.php?op=set&op2=new_user&id=md&other=miguel|de%20dios|matias|kkk|pandora|md@md.com|666|es|descripcion%20y%20esas%20cosas&other_mode=url_encode_separator_|
 *
 * @param $thrash3 Don't use.
 */
function api_set_new_user($id, $thrash2, $other, $thrash3)
{
    global $config;

    if (!check_acl($config['id_user'], 0, 'UM')) {
        returnError('forbidden', 'string');
        return;
    }

    if (empty($id) === true) {
        returnError('Id cannot be empty.');
        return;
    }

    $idk = get_header('idk');
    if (is_management_allowed($idk) === false) {
        returnError('centralized');
        exit;
    }

    $values = [];
    $values['fullname'] = $other['data'][0];
    $values['firstname'] = $other['data'][1];
    $values['lastname'] = $other['data'][2];
    $values['middlename'] = $other['data'][3];
    $password = $other['data'][4];
    $values['email'] = $other['data'][5];
    $values['phone'] = $other['data'][6];
    $values['language'] = $other['data'][7];
    $values['comments'] = $other['data'][8];
    $values['time_autorefresh'] = $other['data'][9];
    $values['default_event_filter'] = $other['data'][10];
    $values['section'] = $other['data'][11];
    $values['session_time'] = $other['data'][12];
    $values['metaconsole_access_node'] = $other['data'][13];
    $values['api_token'] = api_token_generate();

    if (empty($password) === true) {
        returnError('Password cannot be empty.');
        return;
    }

    if (!create_user($id, $password, $values)) {
        returnError('The user could not created');
    } else {
        returnData('string', ['type' => 'string', 'data' => __('User created.')]);
    }
}


/**
 * Update new user.
 *
 * @param string            $id    String username for user login in Pandora
 * @param $thrash2 Don't use.
 * @param array             $other it's array, $other as param is <fullname>;<firstname>;<lastname>;<middlename>;<password>;
 *              <email>;<phone>;<language>;<comments>;<is_admin>;<block_size>;in this order and separator char
 *              (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *              example:
 *
 *              api.php?op=set&op2=update_user&id=example_user_name&other=example_fullname||example_lastname||example_new_passwd|example_email||example_language|example%20comment|1|30|&other_mode=url_encode_separator_|
 *
 * @param $thrash3 Don't use.
 */
function api_set_update_user($id, $thrash2, $other, $thrash3)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'UM')) {
        returnError('forbidden', 'string');
        return;
    }

    $idk = get_header('idk');
    if (is_management_allowed($idk) === false) {
        returnError('centralized');
        exit;
    }

    $fields_user = [
        'fullname',
        'firstname',
        'lastname',
        'middlename',
        'password',
        'email',
        'phone',
        'language',
        'comments',
        'is_admin',
        'block_size',
        'flash_chart',
        'time_autorefresh',
        'default_event_filter',
        'section',
        'session_time',
    ];

    if ($id == '') {
        returnError(
            'The user could not be updated. Id_user cannot be left blank.'
        );
        return;
    }

    $result_user = users_get_user_by_id($id);

    if (!$result_user) {
        returnError(
            'The user could not be updated. Id_user does not exist.'
        );
        return;
    }

    $cont = 0;
    foreach ($fields_user as $field) {
        if ($other['data'][$cont] != '' and $field != 'password') {
            $values[$field] = $other['data'][$cont];
        }

        $cont++;
    }

    // If password field has data
    if ($other['data'][4] != '') {
        if (!update_user_password($id, $other['data'][4])) {
            returnError('The user could not be updated. Password info incorrect.');
            return;
        } else {
            $values['api_token'] = api_token_generate();
        }
    }

    if (!update_user($id, $values)) {
        returnError('The user could not be updated');
    } else {
        returnData('string', ['type' => 'string', 'data' => __('User updated.')]);
    }
}


/**
 * Enable/disable user given an id
 *
 * @param string            $id    String username for user login in Pandora
 * @param $thrash2 Don't use.
 * @param array             $other it's array, $other as param is <enable/disable value> in this order and separator char
 *              (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *              example:
 *
 *                 example 1 (Disable user 'example_name')
 *
 *              api.php?op=set&op2=enable_disable_user&id=example_name&other=0&other_mode=url_encode_separator_|
 *
 *                 example 2 (Enable user 'example_name')
 *
 *              api.php?op=set&op2=enable_disable_user&id=example_name&other=1&other_mode=url_encode_separator_|
 *
 * @param $thrash3 Don't use.
 */
function api_set_enable_disable_user($id, $thrash2, $other, $thrash3)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'UM')) {
        returnError('forbidden', 'string');
        return;
    }

    $idk = get_header('idk');
    if (is_management_allowed($idk) === false) {
        returnError('centralized');
        exit;
    }

    if ($id == '') {
        returnError(
            'Failed switching enable/disable user. Id_user cannot be left blank.'
        );
        return;
    }

    if ($other['data'][0] != '0' and $other['data'][0] != '1') {
        returnError(
            'Failed switching enable/disable user. Enable/disable value cannot be left blank.'
        );
        return;
    }

    if (users_get_user_by_id($id) == false) {
        returnError(
            'Failed switching enable/disable user. The user does not exist.'
        );
        return;
    }

    $result = users_disable($id, $other['data'][0]);

    if (is_error($result)) {
        // TODO: Improve the error returning more info
        returnError(
            'The user could not be enabled/disabled.'
        );
    } else {
        if ($other['data'][0] == '0') {
            returnData(
                'string',
                [
                    'type' => 'string',
                    'data' => __('Enabled user.'),
                ]
            );
        } else {
            returnData(
                'string',
                [
                    'type' => 'string',
                    'data' => __('Disabled user.'),
                ]
            );
        }
    }
}


function otherParameter2Filter($other, $return_as_array=false, $use_agent_name=false)
{
    $filter = [];

    if (isset($other['data'][1]) && ($other['data'][1] != -1) && ($other['data'][1] != '')) {
        $filter['criticity'] = $other['data'][1];
    }

    if (isset($other['data'][2]) && $other['data'][2] != '') {
        if ($use_agent_name === false) {
            $idAgents = agents_get_agent_id_by_alias($other['data'][2], is_metaconsole());

            if (!empty($idAgents)) {
                $idAgent = [];

                $id_agent_field = 'id_agente';

                if (is_metaconsole() === true) {
                    $id_agent_field = 'id_tagente';
                }

                foreach ($idAgents as $key => $value) {
                    $idAgent[] .= $value[$id_agent_field];
                }

                $filter[] = 'id_agente IN ('.implode(',', $idAgent).')';
            } else {
                $filter['sql'] = '1=0';
            }
        } else {
            $idAgent = agents_get_agent_id($other['data'][2]);

            if (!empty($idAgent)) {
                $filter[] = 'id_agente = '.$idAgent;
            } else {
                $filter['sql'] = '1=0';
            }
        }
    }

    $idAgentModulo = null;
    if (isset($other['data'][3]) && $other['data'][3] != '') {
        $filterModule = ['nombre' => $other['data'][3]];
        if ($idAgent != null) {
            $filterModule['id_agente'] = $idAgent;
        }

        $idAgentModulo = db_get_all_rows_filter('tagente_modulo', $filterModule, 'id_agente_modulo');

        if (!empty($idAgentModulo)) {
            $id_agentmodule = [];
            foreach ($idAgentModulo as $key => $value) {
                $id_agentmodule[] .= $value['id_agente_modulo'];
            }

            $idAgentModulo = $id_agentmodule;
            if ($idAgentModulo !== false) {
                $filter['id_agentmodule'] = $idAgentModulo;
            }
        } else {
            // If the module doesn't exist or doesn't exist in that agent.
            $filter['sql'] = '1=0';
        }
    }

    if (isset($other['data'][4]) && $other['data'][4] != '') {
        $idTemplate = db_get_value_filter('id', 'talert_templates', ['name' => $other['data'][4]]);
        if ($idTemplate !== false) {
            if ($idAgentModulo != null) {
                $idAlert = db_get_value_filter('id', 'talert_template_modules', ['id_agent_module' => $idAgentModulo, 'id_alert_template' => $idTemplate]);
                if ($idAlert !== false) {
                    $filter['id_alert_am'] = $idAlert;
                }
            }
        }
    }

    if (isset($idTemplate) && $idTemplate != '') {
        $filter['id_alert_template'] = $idTemplate;
    }

    if (isset($other['data'][5]) && $other['data'][5] != '') {
        $filter['id_usuario'] = $other['data'][5];
    }

    $filterString = db_format_array_where_clause_sql($filter);
    if ($filterString == '') {
        $filterString = '1 = 1';
    }

    if (isset($other['data'][6]) && ($other['data'][6] != '') && ($other['data'][6] != -1)) {
        if ($return_as_array) {
            $filter['utimestamp']['>'] = $other['data'][6];
        } else {
            $filterString .= ' AND utimestamp >= '.$other['data'][6];
        }
    }

    if (isset($other['data'][7]) && ($other['data'][7] != '') && ($other['data'][7] != -1)) {
        if ($return_as_array) {
            $filter['utimestamp']['<'] = $other['data'][7];
        } else {
            $filterString .= ' AND utimestamp <= '.$other['data'][7];
        }
    }

    if (isset($other['data'][8]) && ($other['data'][8] != '')) {
        if ($return_as_array) {
            $filter['estado'] = $other['data'][8];
        } else {
            $estado = (int) $other['data'][8];

            if ($estado >= 0) {
                $filterString .= ' AND estado = '.$estado;
            }
        }
    }

    if (isset($other['data'][9]) && ($other['data'][9] != '')) {
        if ($return_as_array) {
            $filter['evento'] = $other['data'][9];
        } else {
            $filterString .= ' AND evento like "%'.$other['data'][9].'%"';
        }
    }

    if (isset($other['data'][10]) && ($other['data'][10] != '')) {
        if ($return_as_array) {
            $filter['limit'] = $other['data'][10];
        } else {
            $filterString .= ' LIMIT '.$other['data'][10];
        }
    }

    if (isset($other['data'][11]) && ($other['data'][11] != '')) {
        if ($return_as_array) {
            $filter['offset'] = $other['data'][11];
        } else {
            $filterString .= ' OFFSET '.$other['data'][11];
        }
    }

    if (isset($other['data'][12]) && ($other['data'][12] != '')) {
        if ($return_as_array) {
            $filter['total'] = false;
            $filter['more_criticity'] = false;

            if ($other['data'][12] == 'total') {
                $filter['total'] = true;
            }

            if ($other['data'][12] == 'more_criticity') {
                $filter['more_criticity'] = true;
            }
        }
    } else {
        if ($return_as_array) {
            $filter['total'] = false;
            $filter['more_criticity'] = false;
        }
    }

    if (isset($other['data'][13]) && ($other['data'][13] != '')) {
        if ($return_as_array) {
            $filter['id_group'] = $other['data'][13];
        } else {
            $filterString .= ' AND id_grupo = '.$other['data'][13];
        }
    }

    if (isset($other['data'][14]) && ($other['data'][14] != '')) {
        if ($return_as_array) {
            $filter['tag'] = $other['data'][14];
        } else {
            $filterString .= " AND tags LIKE '".$other['data'][14]."'";
        }
    }

    if (isset($other['data'][15]) && ($other['data'][15] != '')) {
        if ($return_as_array) {
            $filter['event_type'] = $other['data'][15];
        } else {
            $event_type = $other['data'][15];

            if ($event_type == 'not_normal') {
                $filterString .= " AND ( event_type LIKE '%warning%'
                    OR event_type LIKE '%critical%' OR event_type LIKE '%unknown%' ) ";
            } else {
                $filterString .= ' AND event_type LIKE "%'.$event_type.'%"';
            }
        }
    }

    if ($return_as_array) {
        return $filter;
    } else {
        return $filterString;
    }
}


/**
 *
 * @param $id
 * @param $id2
 * @param $other
 * @param $trash1
 */
function api_set_new_alert_template($id, $id2, $other, $trash1)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'LM')) {
        returnError('forbidden', 'string');
        return;
    }

    $idk = get_header('idk');
    if (is_management_allowed($idk) === false) {
        returnError('centralized');
        exit;
    }

    if ($other['type'] == 'string') {
        returnError('Parameter error.');
        return;
    } else if ($other['type'] == 'array') {
        $agent_by_alias = false;

        if ($other['data'][1] === '1') {
            $agent_by_alias = true;
        }

        if ($agent_by_alias) {
            $idsAgents = agents_get_agent_id_by_alias($id);
        } else {
            $idAgent = agents_get_agent_id($id);
        }

        if ($agent_by_alias) {
            foreach ($idsAgents as $id) {
                if (!util_api_check_agent_and_print_error($id['id_agente'], 'string', 'AW')) {
                    return;
                }
            }
        } else {
            if (!util_api_check_agent_and_print_error($idAgent, 'string', 'AW')) {
                return;
            }
        }

        $row = db_get_row_filter('talert_templates', ['name' => $id2]);

        if ($row === false) {
            returnError('Parameter error.');
            return;
        }

        $idTemplate = $row['id'];
        $idActionTemplate = $row['id_alert_action'];

        $inserted_count = 0;

        if ($agent_by_alias) {
            foreach ($idsAgents as $id) {
                $idAgentModule = db_get_value_filter('id_agente_modulo', 'tagente_modulo', ['id_agente' => $id['id_agente'], 'nombre' => $other['data'][0]]);

                if ($idAgentModule === false) {
                    continue;
                }

                $values = [
                    'id_agent_module'   => $idAgentModule,
                    'id_alert_template' => $idTemplate,
                ];

                $return = db_process_sql_insert('talert_template_modules', $values);

                if ($return != false) {
                    $inserted_count++;
                }
            }

            returnData('string', ['type' => 'string', 'data' => __('Template have been inserted in %d agents.', $inserted_count)]);
        } else {
            $idAgentModule = db_get_value_filter('id_agente_modulo', 'tagente_modulo', ['id_agente' => $idAgent, 'nombre' => $other['data'][0]]);

            if ($idAgentModule === false) {
                returnError('Parameter error.');
                return;
            }

            $values = [
                'id_agent_module'   => $idAgentModule,
                'id_alert_template' => $idTemplate,
            ];

            $return = db_process_sql_insert('talert_template_modules', $values);

            $data['type'] = 'string';
            if ($return === false) {
                $data['data'] = 0;
            } else {
                $data['data'] = $return;
            }

            returnData('string', $data);
            return;
        }
    }
}


function api_set_delete_module($id, $id2, $other, $trash1)
{
    if (defined('METACONSOLE')) {
        return;
    }

    $simulate = false;
    if ($other['data'][0] == 'simulate') {
        $simulate = true;
    }

    $agent_by_alias = false;

    if ($other['data'][1] === '1') {
        $agent_by_alias = true;
    }

    if ($agent_by_alias) {
        $idsAgents = agents_get_agent_id_by_alias($id);
    } else {
        $idAgent = agents_get_agent_id($id);
    }

    if ($agent_by_alias) {
        foreach ($idsAgents as $id) {
            if (!util_api_check_agent_and_print_error($id['id_agente'], 'string', 'AD')) {
                return;
            }
        }
    } else {
        if (!util_api_check_agent_and_print_error($idAgent, 'string', 'AD')) {
            return;
        }
    }

    if ($agent_by_alias) {
        foreach ($idsAgents as $id) {
            $idAgentModule = db_get_value_filter('id_agente_modulo', 'tagente_modulo', ['id_agente' => $id['id_agente'], 'nombre' => $id2]);

            if ($idAgentModule === false) {
                continue;
            }

            if (!$simulate) {
                // Before delete the main module, check and delete the childrens from the original module.
                module_check_childrens_and_delete($idAgentModule);
                $return = modules_delete_agent_module($idAgentModule);
            } else {
                $return = true;
            }

            $data['type'] = 'string';
            if ($return === false) {
                $data['data'] = 0;
            } else {
                $data['data'] = $return;
            }

            returnData('string', $data);
        }

        return;
    } else {
        $idAgentModule = db_get_value_filter('id_agente_modulo', 'tagente_modulo', ['id_agente' => $idAgent, 'nombre' => $id2]);

        if ($idAgentModule === false) {
            returnError('Parameter error.');
            return;
        }

        if (!$simulate) {
            // Before delete the main module, check and delete the childrens from the original module.
            module_check_childrens_and_delete($idAgentModule);
            $return = modules_delete_agent_module($idAgentModule);
        } else {
            $return = true;
        }

        $data['type'] = 'string';
        if ($return === false) {
            $data['data'] = 0;
        } else {
            $data['data'] = $return;
        }

        returnData('string', $data);
        return;
    }
}


function api_set_module_data($id, $thrash2, $other, $trash1)
{
    global $config;

    if (is_metaconsole()) {
        return;
    }

    if ($other['type'] == 'array') {
        if (!util_api_check_agent_and_print_error(modules_get_agentmodule_agent($id), 'string')) {
            return;
        }

        $idAgentModule = $id;
        $data = $other['data'][0];
        $time = $other['data'][1];

        if ($time == 'now') {
            $time = time();
        }

        $agentModule = db_get_row_filter('tagente_modulo', ['id_agente_modulo' => $idAgentModule]);
        if ($agentModule === false) {
            returnError('Module agent not found.');
        } else {
            $agent = db_get_row_filter('tagente', ['id_agente' => $agentModule['id_agente']]);

            $xmlTemplate = "<?xml version='1.0' encoding='ISO-8859-1'?>
                <agent_data description='' group='' os_name='%s' "." os_version='%s' interval='%d' version='%s' timestamp='%s' agent_name='%s' timezone_offset='%d'>
                    <module>
                        <name><![CDATA[%s]]></name>
                        <description><![CDATA[%s]]></description>
                        <type><![CDATA[%s]]></type>
                        <data><![CDATA[%s]]></data>
                    </module>
                </agent_data>";

            $xml = sprintf(
                $xmlTemplate,
                io_safe_output(get_os_name($agent['id_os'])),
                io_safe_output($agent['os_version']),
                $agentModule['module_interval'],
                io_safe_output($agent['agent_version']),
                date('Y/m/d H:i:s', $time),
                io_safe_output($agent['nombre']),
                $agent['timezone_offset'],
                io_safe_output($agentModule['nombre']),
                io_safe_output($agentModule['descripcion']),
                modules_get_type_name($agentModule['id_tipo_modulo']),
                $data
            );

            if (false === @file_put_contents($config['remote_config'].'/'.io_safe_output($agent['nombre']).'.'.$time.'.data', $xml)) {
                returnError(sprintf('XML file could not be generated in path: %s', $config['remote_config']));
            } else {
                echo __('XML file was generated successfully in path: ').$config['remote_config'];
                returnData('string', ['type' => 'string', 'data' => $xml]);
                return;
            }
        }
    } else {
        returnError('Parameter error.');
        return;
    }
}


function api_set_new_module($id, $id2, $other, $trash1)
{
    if (defined('METACONSOLE')) {
        return;
    }

    if ($other['type'] == 'string') {
        returnError('Parameter error.');
        return;
    } else if ($other['type'] == 'array') {
        $values = [];

        $agent_by_alias = false;

        if ($other['data'][15] === '1') {
            $agent_by_alias = true;
        }

        if ($agent_by_alias) {
            $idsAgents = agents_get_agent_id_by_alias($id);
        } else {
            $values['id_agente'] = agents_get_agent_id($id);
        }

        if ($agent_by_alias) {
            foreach ($idsAgents as $id) {
                if (!util_api_check_agent_and_print_error($id['id_agente'], 'string', 'AW')) {
                    return;
                }
            }
        } else {
            if (!util_api_check_agent_and_print_error($values['id_agente'], 'string', 'AW')) {
                return;
            }
        }

        $values['nombre'] = $id2;

        $values['id_tipo_modulo'] = db_get_value_filter('id_tipo', 'ttipo_modulo', ['nombre' => $other['data'][0]]);
        if ($values['id_tipo_modulo'] === false) {
            returnError('Parameter error.');
            return;
        }

        if ($other['data'][1] == '') {
            returnError('Parameter error.');
            return;
        }

        $values['ip_target'] = $other['data'][1];

        if (strstr($other['data'][0], 'icmp') === false) {
            if (($other['data'][2] == '') || ($other['data'][2] <= 0 || $other['data'][2] > 65535)) {
                returnError('Parameter error.');
                return;
            }

            $values['tcp_port'] = $other['data'][2];
        }

        $values['descripcion'] = $other['data'][3];

        if ($other['data'][4] != '') {
            $values['min'] = $other['data'][4];
        }

        if ($other['data'][5] != '') {
            $values['max'] = $other['data'][5];
        }

        if ($other['data'][6] != '') {
            $values['post_process'] = $other['data'][6];
        }

        if ($other['data'][7] != '') {
            $values['module_interval'] = $other['data'][7];
        }

        if ($other['data'][8] != '') {
            $values['min_warning'] = $other['data'][8];
        }

        if ($other['data'][9] != '') {
            $values['max_warning'] = $other['data'][9];
        }

        if ($other['data'][10] != '') {
            $values['str_warning'] = $other['data'][10];
        }

        if ($other['data'][11] != '') {
            $values['min_critical'] = $other['data'][11];
        }

        if ($other['data'][12] != '') {
            $values['max_critical'] = $other['data'][12];
        }

        if ($other['data'][13] != '') {
            $values['str_critical'] = $other['data'][13];
        }

        if ($other['data'][14] != '') {
            $values['history_data'] = $other['data'][14];
        }

        $disabled_types_event = [];
        $disabled_types_event[EVENTS_GOING_UNKNOWN] = (int) !$other['data'][15];
        $disabled_types_event = json_encode($disabled_types_event);
        $values['disabled_types_event'] = $disabled_types_event;

        $values['id_modulo'] = 2;

        if ($agent_by_alias) {
            $agents_module_created = 0;

            foreach ($idsAgents as $id) {
                $return = modules_create_agent_module(
                    $id['id_agente'],
                    $values['nombre'],
                    $values
                );

                if ($return != false) {
                    $agents_module_created++;
                }
            }

            returnData('string', ['type' => 'string', 'data' => __('Module has been created in %d agents.', $agents_module_created)]);
            return;
        } else {
                $return = modules_create_agent_module(
                    $values['id_agente'],
                    $values['nombre'],
                    $values
                );
        }

        $data['type'] = 'string';
        if ($return === false) {
            $data['data'] = 0;
        } else {
            $data['data'] = $return;
        }

        returnData('string', $data);
        return;
    }
}


/**
 *
 * @param unknown_type $id
 * @param unknown_type $id2
 * @param unknown_type $other
 * @param unknown_type $trash1
 */
function api_set_alert_actions($id, $id2, $other, $trash1)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'LW')) {
        returnError('forbidden', 'string');
        return;
    }

    $idk = get_header('idk');
    if (is_management_allowed($idk) === false) {
        returnError('centralized');
        exit;
    }

    if ($other['type'] == 'string') {
        returnError('Parameter error.');
        return;
    } else if ($other['type'] == 'array') {
        $agent_by_alias = false;

        if ($other['data'][4] === '1') {
            $agent_by_alias = true;
        }

        if ($agent_by_alias) {
            $idsAgents = agents_get_agent_id_by_alias($id);
        } else {
            $idAgent = agents_get_agent_id($id);
        }

        if ($agent_by_alias) {
            foreach ($idsAgents as $id) {
                if (!util_api_check_agent_and_print_error($id['id_agente'], 'string', 'AW')) {
                    return;
                }
            }
        } else {
            if (!util_api_check_agent_and_print_error($idAgent, 'string', 'AW')) {
                return;
            }
        }

        $row = db_get_row_filter('talert_templates', ['name' => $id2]);
        if ($row === false) {
            returnError('Parameter error.');
            return;
        }

        $idTemplate = $row['id'];

        if ($agent_by_alias) {
            $actions_set = 0;

            foreach ($idsAgents as $id) {
                $idAgentModule = db_get_value_filter('id_agente_modulo', 'tagente_modulo', ['id_agente' => $id['id_agente'], 'nombre' => $other['data'][0]]);
                if ($idAgentModule === false) {
                    continue;
                }

                $idAlertTemplateModule = db_get_value_filter('id', 'talert_template_modules', ['id_alert_template' => $idTemplate, 'id_agent_module' => $idAgentModule]);
                if ($idAlertTemplateModule === false) {
                    returnError('Parameter error.');
                    return;
                }

                if ($other['data'][1] != '') {
                    $idAction = db_get_value_filter('id', 'talert_actions', ['name' => $other['data'][1]]);
                    if ($idAction === false) {
                        returnError('Parameter error.');
                        return;
                    }
                } else {
                    returnError('Parameter error.');
                    return;
                }

                $firesMin = $other['data'][2];
                $firesMax = $other['data'][3];

                $values = [
                    'id_alert_template_module' => $idAlertTemplateModule,
                    'id_alert_action'          => $idAction,
                    'fires_min'                => $firesMin,
                    'fires_max'                => $firesMax,
                ];

                $return = db_process_sql_insert('talert_template_module_actions', $values);

                if ($return != false) {
                    $actions_set++;
                }
            }

            returnData('string', ['type' => 'string', 'data' => __('Action has been set for %d agents.', $actions_set)]);

            return;
        } else {
            $idAgentModule = db_get_value_filter('id_agente_modulo', 'tagente_modulo', ['id_agente' => $idAgent, 'nombre' => $other['data'][0]]);
            if ($idAgentModule === false) {
                returnError('Parameter error.');
                return;
            }

            $idAlertTemplateModule = db_get_value_filter('id', 'talert_template_modules', ['id_alert_template' => $idTemplate, 'id_agent_module' => $idAgentModule]);
            if ($idAlertTemplateModule === false) {
                returnError('Parameter error.');
                return;
            }

            if ($other['data'][1] != '') {
                $idAction = db_get_value_filter('id', 'talert_actions', ['name' => $other['data'][1]]);
                if ($idAction === false) {
                    returnError('Parameter error.');
                    return;
                }
            } else {
                returnError('Parameter error.');
                return;
            }

            $firesMin = $other['data'][2];
            $firesMax = $other['data'][3];

            $values = [
                'id_alert_template_module' => $idAlertTemplateModule,
                'id_alert_action'          => $idAction,
                'fires_min'                => $firesMin,
                'fires_max'                => $firesMax,
            ];

            $return = db_process_sql_insert('talert_template_module_actions', $values);

            $data['type'] = 'string';
            if ($return === false) {
                $data['data'] = 0;
            } else {
                $data['data'] = $return;
            }

            returnData('string', $data);
            return;
        }
    }
}


/**
 * Create a new module group
 *
 * @param $id as module group name (mandatory)
 example:

 * http://localhost/pandora_console/include/api.php?op=set&op2=new_module_group&id=Module_group_name&apipass=1234&user=admin&pass=pandora
 */
function api_set_new_module_group($id, $thrash2, $other, $trash1)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    $idk = get_header('idk');
    if (is_management_allowed($idk) === false) {
        returnError('centralized');
        exit;
    }

    if (!check_acl($config['id_user'], 0, 'PM')) {
        returnError('forbidden', 'string');
        return;
    }

    if ($id == '' || !$id) {
        returnError('Module group must have a name.');
        return;
    }

    $name = db_get_value('name', 'tmodule_group', 'name', $id);

    if ($name) {
        returnError('Each Module Group must have a different name.');
        return;
    }

    $return = db_process_sql_insert('tmodule_group', ['name' => $id]);

    if ($return === false) {
        returnError('There was a problem creating group.');
    } else {
        returnData('string', ['type' => 'string', 'data' => $return]);
    }

}


/**
 * Create a new alert command
 *
 * @param $id as command name  (optional)
 *  other=<serialized_parameters> (mandatory). Are the following in this order:
 *    <name>
 *    <command> (mandatory)
 *    <id_group> (optional)
 *    <description> (optional)
 *    <internal> (optional)
 *    <field_description_1><field_value_1><field_description_2><field_value_2>...<field_description_n><field_value_n> (optional)

 example:

 * http://localhost/pandora_console/include/api.php?op=set&op2=alert_commands&id=PRUEBA1&other=command|0|Desc|1|des1|val1|des2|val2|des3|val3||val4|des5&other_mode=url_encode_separator_|&apipass=1234&user=admin&pass=pandora
 */
function api_set_alert_commands($id, $thrash2, $other, $trash1)
{
    global $config;

    $command = $other['data'][0];
    $id_group = 0;
    if ($other['data'][1] != '') {
        $id_group = $other['data'][1];
    }

    $description = $other['data'][2];
    $internal = $other['data'][3];

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'LW')) {
        returnError('forbidden', 'string');
        return;
    }

    $idk = get_header('idk');
    if (is_management_allowed($idk) === false) {
        returnError('centralized');
        exit;
    }

    $name = db_get_value('id', 'talert_commands', 'name', $id);
    $group = db_get_value('id_grupo', 'tgrupo', 'id_grupo', $id_group);

    if ($id == '' || !$id) {
        returnError('Name cannot be empty.');
        return;
    }

    if ($command == '' || !$command) {
        returnError('Command cannot be empty.');
        return;
    }

    if ($name) {
        returnError('Name already exist.');
        return;
    }

    if (!$group && $id_group != 0) {
        returnError('Group does not exist.');
        return;
    }

    if ($other['type'] == 'string') {
        returnError('Parameter error.');
        return;
    } else if ($other['type'] == 'array') {
        $fields_descriptions = [];
        $fields_values = [];
        $max_macro_fields = ($config['max_macro_fields'] * 2);

        $values = [];
        for ($i = 0; $i < $max_macro_fields; $i++) {
            $n = ($i + 4);

            if (!$other['data'][$n]) {
                $other['data'][$n] = '';
            }

            if (($n % 2) == 0) {
                $fields_descriptions[] = $other['data'][$n];
            } else {
                $fields_values[] = $other['data'][$n];
            }
        }

        $fields_descriptions_encode = io_json_mb_encode($fields_descriptions);
        $fields_values_encode = io_json_mb_encode($fields_values);

        $values = [
            'id_group'            => $id_group,
            'description'         => $description,
            'internal'            => $internal,
            'fields_descriptions' => $fields_descriptions_encode,
            'fields_values'       => $fields_values_encode,
        ];

        $return = alerts_create_alert_command($id, $command, $values);

        $data['type'] = 'string';
        if ($return === false) {
            $data['data'] = 0;
        } else {
            $data['data'] = $return;
        }

        returnData('string', $data);
        return;
    }
}


function api_set_new_event($trash1, $trash2, $other, $trash3)
{
    $simulate = false;
    $time = get_system_time();

    if ($other['type'] == 'string') {
        if ($other['data'] != '') {
            returnError('Parameter error.');
            return;
        }
    } else if ($other['type'] == 'array') {
        $values = [];

        if (($other['data'][0] == null) && ($other['data'][0] == '')) {
            returnError('Parameter error.');
            return;
        } else {
            $values['evento'] = $other['data'][0];
        }

        if (($other['data'][1] == null) && ($other['data'][1] == '')) {
            returnError('Parameter error.');
            return;
        } else {
            $valuesAvaliable = [
                'unknown',
                'alert_fired',
                'alert_recovered',
                'alert_ceased',
                'alert_manual_validation',
                'recon_host_detected',
                'system',
                'error',
                'new_agent',
                'going_up_warning',
                'going_up_critical',
                'going_down_warning',
                'going_down_normal',
                'going_down_critical',
                'going_up_normal',
                'configuration_change',
            ];

            if (in_array($other['data'][1], $valuesAvaliable)) {
                $values['event_type'] = $other['data'][1];
            } else {
                returnError('Parameter error.');
                return;
            }
        }

        if (($other['data'][2] == null) && ($other['data'][2] == '')) {
            returnError('Parameter error.');
            return;
        } else {
            $values['estado'] = $other['data'][2];
        }

        if (($other['data'][3] == null) && ($other['data'][3] == '')) {
            returnError('Parameter error.');
            return;
        } else {
            $values['id_agente'] = agents_get_agent_id($other['data'][3]);
        }

        if (($other['data'][4] == null) && ($other['data'][4] == '')) {
            returnError('Parameter error.');
            return;
        } else {
            $idAgentModule = db_get_value_filter(
                'id_agente_modulo',
                'tagente_modulo',
                [
                    'nombre'    => $other['data'][4],
                    'id_agente' => $values['id_agente'],
                ]
            );
        }

        if ($idAgentModule === false) {
            returnError('Parameter error.');
            return;
        } else {
            $values['id_agentmodule'] = $idAgentModule;
        }

        if (($other['data'][5] == null) && ($other['data'][5] == '')) {
            returnError('Parameter error.');
            return;
        } else {
            if ($other['data'][5] != 'all') {
                $idGroup = db_get_value_filter('id_grupo', 'tgrupo', ['nombre' => $other['data'][5]]);
            } else {
                $idGroup = 0;
            }

            if ($idGroup === false) {
                returnError('Parameter error.');
                return;
            } else {
                $values['id_grupo'] = $idGroup;
            }
        }

        if (($other['data'][6] == null) && ($other['data'][6] == '')) {
            returnError('Parameter error.');
            return;
        } else {
            if (($other['data'][6] >= 0) && ($other['data'][6] <= 4)) {
                $values['criticity'] = $other['data'][6];
            } else {
                returnError('Parameter error.');
                return;
            }
        }

        if (($other['data'][7] == null) && ($other['data'][7] == '')) {
            // its optional parameter
        } else {
            $idAlert = db_get_value_sql(
                "SELECT t1.id 
                FROM talert_template_modules t1 
                    INNER JOIN talert_templates t2 
                        ON t1.id_alert_template = t2.id 
                WHERE t1.id_agent_module = 1
                    AND t2.name LIKE '".$other['data'][7]."'"
            );

            if ($idAlert === false) {
                returnError('Parameter error.');
                return;
            } else {
                $values['id_alert_am'] = $idAlert;
            }
        }
    }

    $values['timestamp'] = date('Y-m-d H:i:s', $time);
    $values['utimestamp'] = $time;

    $return = db_process_sql_insert('tevento', $values);

    $data['type'] = 'string';
    if ($return === false) {
        $data['data'] = 0;
    } else {
        $data['data'] = $return;
    }

    returnData('string', $data);
    return;
}


function api_set_event_validate_filter_pro($trash1, $trash2, $other, $trash3)
{
    global $config;

    if (!check_acl($config['id_user'], 0, 'EW')) {
        returnError('forbidden', 'string');
        return;
    }

    if ($other['type'] == 'string') {
        if ($other['data'] != '') {
            returnError('Parameter error.');
            return;
        }
    } else if ($other['type'] == 'array') {
        $filter = [];

        if (($other['data'][1] != null) && ($other['data'][1] != -1)
            && ($other['data'][1] != '')
        ) {
            $filter['criticity'] = $other['data'][1];
        }

        if (($other['data'][2] != null) && ($other['data'][2] != -1)
            && ($other['data'][2] != '')
        ) {
            $filter['id_agente'] = $other['data'][2];
        }

        if (($other['data'][3] != null) && ($other['data'][3] != -1)
            && ($other['data'][3] != '')
        ) {
            $filter['id_agentmodule'] = $other['data'][3];
        }

        if (($other['data'][4] != null) && ($other['data'][4] != -1)
            && ($other['data'][4] != '')
        ) {
            $filter['id_alert_am'] = $other['data'][4];
        }

        if (($other['data'][5] != null) && ($other['data'][5] != '')) {
            $filter['id_usuario'] = $other['data'][5];
        }

        $filterString = db_format_array_where_clause_sql($filter);
        if ($filterString == '') {
            $filterString = '1 = 1';
        }

        if (($other['data'][6] != null) && ($other['data'][6] != -1)) {
            $filterString .= ' AND utimestamp > '.$other['data'][6];
        }

        if (($other['data'][7] != null) && ($other['data'][7] != -1)) {
            $filterString .= 'AND utimestamp < '.$other['data'][7];
        }

        if (!users_can_manage_group_all('EW')) {
            $user_groups = implode(
                ',',
                array_keys(
                    users_get_groups(
                        $config['id_user'],
                        'EW',
                        false
                    )
                )
            );
            $filterString .= " AND id_grupo IN ($user_groups) ";
        }
    }

    $count = db_process_sql_update(
        'tevento',
        ['estado' => 1],
        $filterString
    );

    returnData(
        'string',
        [
            'type' => 'string',
            'data' => $count,
        ]
    );
    return;
}


function api_set_event_validate_filter($trash1, $trash2, $other, $trash3)
{
    global $config;

    if (!check_acl($config['id_user'], 0, 'EW')) {
        returnError('forbidden', 'string');
        return;
    }

    $simulate = false;
    if ($other['type'] == 'string') {
        if ($other['data'] != '') {
            returnError('Parameter error.');
            return;
        }
    } else if ($other['type'] == 'array') {
        $separator = $other['data'][0];

        if (($other['data'][8] != null) && ($other['data'][8] != '')) {
            if ($other['data'][8] == 'simulate') {
                $simulate = true;
            }
        }

        $use_agent_name = ($other['data'][8] === '1') ? true : false;

        $filterString = otherParameter2Filter($other, false, $use_agent_name);

        if (!users_can_manage_group_all('EW')) {
            $user_groups = implode(
                ',',
                array_keys(
                    users_get_groups(
                        $config['id_user'],
                        'EW',
                        false
                    )
                )
            );
            $filterString .= " AND id_grupo IN ($user_groups) ";
        }
    }

    if ($simulate) {
        $rows = db_get_all_rows_filter('tevento', $filterString);
        if ($rows !== false) {
            returnData('string', count($rows));
            return;
        }
    } else {
        $count = db_process_sql_update(
            'tevento',
            ['estado' => 1],
            $filterString
        );

        returnData(
            'string',
            [
                'type' => 'string',
                'data' => $count,
            ]
        );
        return;
    }
}


function api_set_validate_events($id_event, $trash1, $other, $return_type, $user_in_db)
{
    $node_int = 0;
    if ($other['type'] == 'string') {
        returnError('Parameter error.');
        return;
    } else if ($other['type'] == 'array') {
        $text = $other['data'][0];
        if (is_metaconsole() === true) {
            if (isset($other['data'][1]) === true
                && empty($other['data'][1]) === false
            ) {
                $node_int = $other['data'][1];
            }
        }
    }

    try {
        if (is_metaconsole() === true
            && (int) $node_int > 0
        ) {
            $node = new Node($node_int);
            $node->connect();
        }

        // Set off the standby mode when close an event
        $event = events_get_event($id_event);
        alerts_agent_module_standby($event['id_alert_am'], 0);
        $result = events_change_status($id_event, EVENT_VALIDATE);

        if (!empty($text)) {
            // Set the comment for the validation
            events_comment($id_event, $text);
        }
    } catch (\Exception $e) {
        if (is_metaconsole() === true
            && $node_int > 0
        ) {
            $node->disconnect();
        }

        $result = false;
    } finally {
        if (is_metaconsole() === true
            && $node_int > 0
        ) {
            $node->disconnect();
        }
    }

    if ($result) {
        returnData(
            'string',
            [
                'type' => 'string',
                'data' => 'Correct validation',
            ]
        );
    } else {
        returnError('The event could not be validated.');
    }
}


function api_get_gis_agent($id_agent, $trash1, $tresh2, $return_type, $user_in_db)
{
    if (defined('METACONSOLE')) {
        return;
    }

    if (!util_api_check_agent_and_print_error($id_agent, $return_type)) {
        return;
    }

    $agent_gis_data = db_get_row_sql(
        '
        SELECT *
        FROM tgis_data_status
        WHERE tagente_id_agente = '.$id_agent
    );

    if ($agent_gis_data) {
        returnData(
            $return_type,
            [
                'type' => 'array',
                'data' => [$agent_gis_data],
            ]
        );
    } else {
        returnError('There is not GIS data for the agent.');
    }
}


function api_set_gis_agent_only_position($id_agent, $trash1, $other, $return_type, $user_in_db)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!util_api_check_agent_and_print_error($id_agent, 'string', 'AW')) {
        return;
    }

    $new_gis_data = $other['data'];

    $correct = true;

    if (isset($new_gis_data[0])) {
        $latitude = $new_gis_data[0];
    } else {
        $correct = false;
    }

    if (isset($new_gis_data[1])) {
        $longitude = $new_gis_data[1];
    } else {
        $correct = false;
    }

    if (isset($new_gis_data[2])) {
        $altitude = $new_gis_data[2];
    } else {
        $correct = false;
    }

    if (!$config['activate_gis']) {
        $correct = false;
        returnError('GIS not activated.');
        return;
    } else {
        if ($correct) {
            $correct = agents_update_gis(
                $id_agent,
                $latitude,
                $longitude,
                $altitude,
                0,
                1,
                date('Y-m-d H:i:s'),
                null,
                1,
                __('Save by %s Console', get_product_name()),
                __('Update by %s Console', get_product_name()),
                __('Insert by %s Console', get_product_name())
            );
        } else {
            returnError('Missing parameters.');
            return;
        }
    }

    $data = [
        'type' => 'string',
        'data' => (int) $correct,
    ];

    $returnType = 'string';
    returnData($returnType, $data);
}


function api_set_gis_agent($id_agent, $trash1, $other, $return_type, $user_in_db)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!util_api_check_agent_and_print_error($id_agent, 'string', 'AW')) {
        return;
    }

    $new_gis_data = $other['data'];

    $correct = true;

    if (isset($new_gis_data[0])) {
        $latitude = $new_gis_data[0];
    } else {
        $correct = false;
    }

    if (isset($new_gis_data[1])) {
        $longitude = $new_gis_data[1];
    } else {
        $correct = false;
    }

    if (isset($new_gis_data[2])) {
        $altitude = $new_gis_data[2];
    } else {
        $correct = false;
    }

    if (isset($new_gis_data[3])) {
        $ignore_new_gis_data = $new_gis_data[3];
    } else {
        $correct = false;
    }

    if (isset($new_gis_data[4])) {
        $manual_placement = $new_gis_data[4];
    } else {
        $correct = false;
    }

    if (isset($new_gis_data[5])) {
        $start_timestamp = $new_gis_data[5];
    } else {
        $correct = false;
    }

    if (isset($new_gis_data[6])) {
        $end_timestamp = $new_gis_data[6];
    } else {
        $correct = false;
    }

    if (isset($new_gis_data[7])) {
        $number_of_packages = $new_gis_data[7];
    } else {
        $correct = false;
    }

    if (isset($new_gis_data[8])) {
        $description_save_history = $new_gis_data[8];
    } else {
        $correct = false;
    }

    if (isset($new_gis_data[9])) {
        $description_update_gis = $new_gis_data[9];
    } else {
        $correct = false;
    }

    if (isset($new_gis_data[10])) {
        $description_first_insert = $new_gis_data[10];
    } else {
        $correct = false;
    }

    if (!$config['activate_gis']) {
        $correct = false;
        returnError('GIS not activated.');
        return;
    } else {
        if ($correct) {
            $correct = agents_update_gis(
                $id_agent,
                $latitude,
                $longitude,
                $altitude,
                $ignore_new_gis_data,
                $manual_placement,
                $start_timestamp,
                $end_timestamp,
                $number_of_packages,
                $description_save_history,
                $description_update_gis,
                $description_first_insert
            );
        } else {
            returnError('Missing parameters.');
            return;
        }
    }

    $data = [
        'type' => 'string',
        'data' => (int) $correct,
    ];

    $returnType = 'string';
    returnData($returnType, $data);
}


/**
 * Update an event
 *
 * @param string $id_event Id of the event for change.
 * @param string $unused1  Without use.
 * @param array  $params   Dictionary with field,value format with the data for update.
 * @param string $unused2  Without use.
 * @param string $unused3  Without use.
 *
 * @return void
 */
function api_set_event($id_event, $unused1, $params, $unused2, $unused3)
{
    // Get the event
    $event = events_get_event($id_event, false, is_metaconsole());
    // If event not exists, end the execution.
    if ($event === false) {
        returnError(
            'event_not_exists',
            'Event not exists'
        );
        return false;
    }

    $paramsSerialize = [];
    // Serialize the data for update
    if ($params['type'] === 'array') {
        // Keys that is not available to change
        $invalidKeys = [
            'id_evento',
            'id_agente',
            'id_grupo',
            'timestamp',
            'utimestamp',
            'id_agentmodule',
            'ack_utimestamp',
            'data',
        ];

        foreach ($params['data'] as $key_value) {
            list($key, $value) = explode(',', $key_value, 2);
            if (in_array($key, $invalidKeys) == false) {
                $paramsSerialize[$key] = $value;
            }
        }
    }

    // Update the row
    $result = db_process_sql_update(
        'tevento',
        $paramsSerialize,
        [ 'id_evento' => $id_event ]
    );

    // If update results failed
    if (empty($result) === true || $result === false) {
        returnError(
            'The event could not be updated'
        );
        return false;
    } else {
        returnData('string', ['data' => 'Event updated.']);
    }

    return;
}


/**
 * Get events.
 *
 * @param $trash1
 * @param $trah2
 * @param $other
 * @param $returnType
 */
function api_get_events($node_id, $trash2, $other, $returnType)
{
    $separator = (isset($other['data'][0]) === true && empty($other['data'][0]) === false) ? $other['data'][0] : ';';

    if (is_metaconsole() === true) {
        if (empty($node_id) === true && $node_id != 0) {
            $node_id = array_keys(metaconsole_get_names(['disabled' => 0]));
            $node_id[] = 0;
        } else {
            $node_id = [(int) $node_id];
        }
    } else {
        $node_id = 0;
    }

    $filters = [
        'group_rep'         => EVENT_GROUP_REP_ALL,
        'severity'          => (isset($other['data'][1]) === true) ? $other['data'][1] : null,
        'agent_alias'       => (isset($other['data'][2]) === true) ? $other['data'][2] : null,
        'module_search'     => (isset($other['data'][3]) === true) ? $other['data'][3] : null,
        'filter_only_alert' => (isset($other['data'][4]) === true) ? $other['data'][4] : null,
        'id_user_ack'       => (isset($other['data'][5]) === true) ? $other['data'][5] : null,
        'date_from'         => (isset($other['data'][6]) === true && empty($other['data'][6]) === false) ? date('y-m-d', $other['data'][6]) : null,
        'date_to'           => (isset($other['data'][7]) === true && empty($other['data'][7]) === false) ? date('y-m-d', $other['data'][7]) : null,
        'time_from'         => (isset($other['data'][6]) === true && empty($other['data'][6]) === false) ? date('h:i:s', $other['data'][6]) : null,
        'time_to'           => (isset($other['data'][7]) === true && empty($other['data'][7]) === false) ? date('h:i:s', $other['data'][7]) : null,
        'status'            => (isset($other['data'][8]) === true) ? $other['data'][8] : null,
        'search'            => (isset($other['data'][9]) === true) ? $other['data'][9] : null,
        'id_group_filter'   => (isset($other['data'][13]) === true) ? $other['data'][13] : null,
        'tag_with'          => (isset($other['data'][14]) === true) ? base64_encode(io_safe_output($other['data'][14])) : null,
        'event_type'        => (isset($other['data'][15]) === true) ? $other['data'][15] : null,
        'id_server'         => $node_id,
    ];

    $limit = null;
    if (isset($other['data'][10]) === true) {
        if (empty($other['data'][10]) === true) {
            $limit = 0;
        } else {
            $limit = $other['data'][10];
        }
    }

    $offset = null;
    if (isset($other['data'][11]) === true) {
        if (empty($other['data'][11]) === true) {
            $offset = 0;
        } else {
            $offset = $other['data'][11];
        }
    } else {
        if (isset($other['data'][10]) === true) {
            $offset = 0;
        }
    }

    $fields = ['te.*'];
    $order_direction = 'desc';
    $order_field = 'te.utimestamp';
    $filter_total = false;
    if (isset($other['data'][12]) === true
        && empty($other['data'][12]) === false
    ) {
        $filter_total = true;
        if ($other['data'][12] === 'total') {
            $fields = ['count'];
            $limit = null;
            $offset = null;
        } else if ($other['data'][12] === 'more_criticity') {
            $fields = ['te.criticity'];
            $order_direction = 'desc';
            $order_field = 'te.criticity';
            $limit = 1;
            $offset = 0;
        }
    }

    $events = Event::search(
        $fields,
        $filters,
        $offset,
        $limit,
        $order_direction,
        $order_field
    );

    $result = $events;
    if (is_metaconsole() === true && empty($limit) === false) {
        $result = $events['data'];
    }

    if (is_array($result) === true && $filter_total === false) {
        $urlImage = ui_get_full_url(false);

        // Add the description and image.
        foreach ($result as $key => $row) {
            if (is_metaconsole() === true) {
                if (empty($row['id_agente']) === false) {
                    $row['agent_name'] = agents_meta_get_name(
                        $row['id_agente'],
                        'none',
                        $row['server_id']
                    );
                }

                if (empty($row['id_agentmodule']) === false) {
                    $row['module_name'] = meta_modules_get_name(
                        $row['id_agentmodule'],
                        $row['server_id']
                    );
                }
            }

            // FOR THE TEST THE API IN THE ANDROID.
            $row['description_event'] = events_print_type_description($row['event_type'], true);
            $row['img_description'] = events_print_type_img($row['event_type'], true, true);
            $row['criticity_name'] = get_priority_name($row['criticity']);

            switch ($row['criticity']) {
                default:
                case EVENT_CRIT_MAINTENANCE:
                    $img_sev = $urlImage.'/images/status_sets/default/severity_maintenance.png';
                break;

                case EVENT_CRIT_INFORMATIONAL:
                    $img_sev = $urlImage.'/images/status_sets/default/severity_informational.png';
                break;

                case EVENT_CRIT_NORMAL:
                    $img_sev = $urlImage.'/images/status_sets/default/severity_normal.png';
                break;

                case EVENT_CRIT_WARNING:
                    $img_sev = $urlImage.'/images/status_sets/default/severity_warning.png';
                break;

                case EVENT_CRIT_CRITICAL:
                    $img_sev = $urlImage.'/images/status_sets/default/severity_critical.png';
                break;
            }

            $row['img_criticy'] = $img_sev;

            $result[$key] = $row;
        }
    }

    $data['type'] = $returnType;
    $data['data'] = $result;
    returnData($returnType, $data, $separator);
    return;
}


/**
 * Delete user.
 *
 * @param $id string Username to delete.
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param $thrash3 Don't use.
 */
function api_set_delete_user($id, $thrash1, $thrash2, $thrash3)
{
    global $config;

    if (!check_acl($config['id_user'], 0, 'UM')) {
        returnError('forbidden', 'string');
        return;
    }

    if (empty($id) === true) {
        returnError('Id cannot be empty.');
        return;
    }

    $idk = get_header('idk');
    if (is_management_allowed($idk) === false) {
        returnError('centralized');
        exit;
    }

    if (!delete_user($id)) {
        returnError('The user could not be deleted');
    } else {
        returnData('string', ['type' => 'string', 'data' => __('User deleted.')]);
    }
}


/**
 * Add user to profile and group.
 *
 * @param $id string Username to delete.
 * @param $thrash1 Don't use.
 * @param array                        $other it's array, $other as param is <group>;<profile> in this
 *                         order and separator char (after text ; ) and separator (pass in param
 *                         othermode as othermode=url_encode_separator_<separator>)
 *                         example:
 *
 *                         api.php?op=set&op2=add_user_profile&id=example_user_name&other=12|4&other_mode=url_encode_separator_|
 *
 * @param $thrash2 Don't use.
 */
function api_set_add_user_profile($id, $thrash1, $other, $thrash2)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'PM')) {
        returnError('forbidden', 'string');
        return;
    }

    $idk = get_header('idk');
    if (is_management_allowed($idk) === false) {
        returnError('centralized');
        exit;
    }

    $group = (int) $other['data'][0];
    $profile = $other['data'][1];

    if ($group !== 0 && db_get_value('id_grupo', 'tgrupo', 'id_grupo', $group) === false) {
        returnError('There is not any group with the ID provided.');
        return;
    }

    if (db_get_value('id_perfil', 'tperfil', 'id_perfil', $profile) === false) {
        returnError('There is not any profile with the ID provided.');
        return;
    }

    if (!check_acl($config['id_user'], $group, 'PM')) {
        returnError('forbidden', 'string');
        return;
    }

    if (!profile_create_user_profile($id, $profile, $group, 'API')) {
        returnError('The user profile could not be added.');
    } else {
        returnData('string', ['type' => 'string', 'data' => __('User profile added.')]);
    }
}


/**
 * Deattach user from group and profile.
 *
 * @param $id string Username to delete.
 * @param $thrash1 Don't use.
 * @param array                        $other it's array, $other as param is <group>;<profile> in this
 *                         order and separator char (after text ; ) and separator (pass in param
 *                         othermode as othermode=url_encode_separator_<separator>)
 *                         example:
 *
 *                         api.php?op=set&op2=delete_user_profile&id=md&other=12|4&other_mode=url_encode_separator_|
 *
 * @param $thrash2 Don't use.
 */
function api_set_delete_user_profile($id, $thrash1, $other, $thrash2)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'PM')) {
        returnError('forbidden', 'string');
        return;
    }

    $idk = get_header('idk');
    if (is_management_allowed($idk) === false) {
        returnError('centralized');
        exit;
    }

    $group = $other['data'][0];
    $profile = $other['data'][1];

    if (db_get_value('id_grupo', 'tgrupo', 'id_grupo', $group) === false) {
        returnError('There is not any group with the ID provided.');
        return;
    }

    if (db_get_value('id_perfil', 'tperfil', 'id_perfil', $profile) === false) {
        returnError('There is not any profile with the ID provided.');
        return;
    }

    if (!check_acl($config['id_user'], $group, 'PM')) {
        returnError('forbidden', 'string');
        return;
    }

    $where = [
        'id_usuario' => $id,
        'id_perfil'  => $profile,
        'id_grupo'   => $group,
    ];
    $result = db_process_sql_delete('tusuario_perfil', $where);
    if ($result === false) {
        returnError('The user profile could not be deleted.');
    } else {
        returnData('string', ['type' => 'string', 'data' => __('User profile deleted.')]);
    }
}


/**
 * List all user profiles.
 *
 * @param Reserved                              $thrash1
 * @param Reserved                              $thrash2
 * @param Reserved                              $thrash3
 * @param string Return type (csv, json, string...)
 *
 *  api.php?op=get&op2=user_profiles_info&return_type=json&apipass=1234&user=admin&pass=pandora
 */
function api_get_user_profiles_info($thrash1, $thrash2, $thrash3, $returnType)
{
    global $config;

    if (!check_acl($config['id_user'], 0, 'PM')) {
        returnError('forbidden', 'string');
        return;
    }

    $profiles = db_get_all_rows_filter(
        'tperfil',
        [],
        [
            'id_perfil',
            'name',
            'agent_view as AR',
            'agent_edit as AW',
            'agent_disable as AD',
            'alert_edit as LW',
            'alert_management as LM',
            'user_management as UM',
            'db_management as DM',
            'event_view as ER',
            'event_edit as EW',
            'event_management as EM',
            'report_view as RR',
            'report_edit as RW',
            'report_management as RM',
            'map_view as MR',
            'map_edit as MW',
            'map_management as MM',
            'vconsole_view as VR',
            'vconsole_edit as VW',
            'vconsole_management as VM',
            'pandora_management as PM',
        ]
    );

    if ($profiles === false) {
        returnError('Profiles could not be retrieved.');
    } else {
        returnData($returnType, ['type' => 'array', 'data' => $profiles]);
    }
}


/**
 * Create an user profile.
 *
 * @param Reserved                                                                                             $thrash1
 * @param Reserved                                                                                             $thrash2
 * @param array parameters in array: name|IR|IW|IM|AR|AW|AD|LW|LM|UM|DM|ER|EW|EM|RR|RW|RM|MR|MW|MM|VR|VW|VM|PM
 * @param string Return type (csv, json, string...)
 *
 *  api.php?op=set&op2=create_user_profile_info&return_type=json&other=API_profile%7C1%7C0%7C0%7C1%7C0%7C0%7C0%7C0%7C0%7C0%7C1%7C0%7C0%7C1%7C0%7C0%7C1%7C0%7C0%7C1%7C0%7C0%7C0&other_mode=url_encode_separator_%7C&apipass=1234&user=admin&pass=pandora
 */
function api_set_create_user_profile_info($thrash1, $thrash2, $other, $returnType)
{
    global $config;

    if (!check_acl($config['id_user'], 0, 'PM')) {
        returnError('forbidden', 'string');
        return;
    }

    $idk = get_header('idk');
    if (is_management_allowed($idk) === false) {
        returnError('centralized');
        exit;
    }

    $values = [
        'name'                => (string) $other['data'][0],
        'agent_view'          => (bool) $other['data'][1] ? 1 : 0,
        'agent_edit'          => (bool) $other['data'][2] ? 1 : 0,
        'agent_disable'       => (bool) $other['data'][3] ? 1 : 0,
        'alert_edit'          => (bool) $other['data'][4] ? 1 : 0,
        'alert_management'    => (bool) $other['data'][5] ? 1 : 0,
        'user_management'     => (bool) $other['data'][6] ? 1 : 0,
        'db_management'       => (bool) $other['data'][7] ? 1 : 0,
        'event_view'          => (bool) $other['data'][8] ? 1 : 0,
        'event_edit'          => (bool) $other['data'][9] ? 1 : 0,
        'event_management'    => (bool) $other['data'][10] ? 1 : 0,
        'report_view'         => (bool) $other['data'][11] ? 1 : 0,
        'report_edit'         => (bool) $other['data'][12] ? 1 : 0,
        'report_management'   => (bool) $other['data'][13] ? 1 : 0,
        'map_view'            => (bool) $other['data'][14] ? 1 : 0,
        'map_edit'            => (bool) $other['data'][15] ? 1 : 0,
        'map_management'      => (bool) $other['data'][16] ? 1 : 0,
        'vconsole_view'       => (bool) $other['data'][17] ? 1 : 0,
        'vconsole_edit'       => (bool) $other['data'][18] ? 1 : 0,
        'vconsole_management' => (bool) $other['data'][19] ? 1 : 0,
        'pandora_management'  => (bool) $other['data'][20] ? 1 : 0,
    ];

    $return = db_process_sql_insert('tperfil', $values);

    if ($return === false) {
        returnError('The user profile could not be created.');
    } else {
        returnData($returnType, ['type' => 'array', 'data' => 1]);
    }
}


/**
 * Update an user profile.
 *
 * @param int Profile id
 * @param Reserved                                                                                             $thrash1
 * @param array parameters in array: name|IR|IW|IM|AR|AW|AD|LW|LM|UM|DM|ER|EW|EM|RR|RW|RM|MR|MW|MM|VR|VW|VM|PM
 * @param string Return type (csv, json, string...)
 *
 *  api.php?op=set&op2=update_user_profile_info&return_type=json&id=6&other=API_profile_updated%7C%7C%7C%7C1%7C1%7C1%7C%7C%7C%7C%7C%7C%7C%7C%7C%7C%7C%7C%7C%7C%7C%7C%7C&other_mode=url_encode_separator_%7C&apipass=1234&user=admin&pass=pandora
 */
function api_set_update_user_profile_info($id_profile, $thrash1, $other, $returnType)
{
    global $config;

    if (!check_acl($config['id_user'], 0, 'PM')) {
        returnError('forbidden', 'string');
        return;
    }

    $idk = get_header('idk');
    if (is_management_allowed($idk) === false) {
        returnError('centralized');
        exit;
    }

    $profile = db_get_row('tperfil', 'id_perfil', $id_profile);
    if ($profile === false) {
        returnError('id_not_found', 'string');
        return;
    }

    $values = [
        'name'                => ($other['data'][0] == '') ? $profile['name'] : (string) $other['data'][0],
        'agent_view'          => ($other['data'][1] == '') ? $profile['agent_view'] : ((bool) $other['data'][1] ? 1 : 0),
        'agent_edit'          => ($other['data'][2] == '') ? $profile['agent_edit'] : ((bool) $other['data'][2] ? 1 : 0),
        'agent_disable'       => ($other['data'][3] == '') ? $profile['agent_disable'] : ((bool) $other['data'][3] ? 1 : 0),
        'alert_edit'          => ($other['data'][4] == '') ? $profile['alert_edit'] : ((bool) $other['data'][4] ? 1 : 0),
        'alert_management'    => ($other['data'][5] == '') ? $profile['alert_management'] : ((bool) $other['data'][5] ? 1 : 0),
        'user_management'     => ($other['data'][6] == '') ? $profile['user_management'] : ((bool) $other['data'][6] ? 1 : 0),
        'db_management'       => ($other['data'][7] == '') ? $profile['db_management'] : ((bool) $other['data'][7] ? 1 : 0),
        'event_view'          => ($other['data'][8] == '') ? $profile['event_view'] : ((bool) $other['data'][8] ? 1 : 0),
        'event_edit'          => ($other['data'][9] == '') ? $profile['event_edit'] : ((bool) $other['data'][9] ? 1 : 0),
        'event_management'    => ($other['data'][10] == '') ? $profile['event_management'] : ((bool) $other['data'][10] ? 1 : 0),
        'report_view'         => ($other['data'][11] == '') ? $profile['report_view'] : ((bool) $other['data'][11] ? 1 : 0),
        'report_edit'         => ($other['data'][12] == '') ? $profile['report_edit'] : ((bool) $other['data'][12] ? 1 : 0),
        'report_management'   => ($other['data'][13] == '') ? $profile['report_management'] : ((bool) $other['data'][13] ? 1 : 0),
        'map_view'            => ($other['data'][14] == '') ? $profile['map_view'] : ((bool) $other['data'][14] ? 1 : 0),
        'map_edit'            => ($other['data'][15] == '') ? $profile['map_edit'] : ((bool) $other['data'][15] ? 1 : 0),
        'map_management'      => ($other['data'][16] == '') ? $profile['map_management'] : ((bool) $other['data'][16] ? 1 : 0),
        'vconsole_view'       => ($other['data'][17] == '') ? $profile['vconsole_view'] : ((bool) $other['data'][17] ? 1 : 0),
        'vconsole_edit'       => ($other['data'][18] == '') ? $profile['vconsole_edit'] : ((bool) $other['data'][18] ? 1 : 0),
        'vconsole_management' => ($other['data'][19] == '') ? $profile['vconsole_management'] : ((bool) $other['data'][19] ? 1 : 0),
        'pandora_management'  => ($other['data'][20] == '') ? $profile['pandora_management'] : ((bool) $other['data'][20] ? 1 : 0),
    ];

    $return = db_process_sql_update('tperfil', $values, ['id_perfil' => $id_profile]);

    if ($return === false) {
        returnError('The user profile could not be updated');
    } else {
        returnData($returnType, ['type' => 'array', 'data' => 1]);
    }
}


/**
 * Delete an user profile.
 *
 * @param int Profile id
 * @param Reserved                              $thrash1
 * @param Reserved                              $thrash2
 * @param string Return type (csv, json, string...)
 *
 *  api.php?op=set&op2=delete_user_profile_info&return_type=json&id=7&other_mode=url_encode_separator_%7C&apipass=1234&user=admin&pass=pandora
 */
function api_set_delete_user_profile_info($id_profile, $thrash1, $thrash2, $returnType)
{
    global $config;

    if (!check_acl($config['id_user'], 0, 'PM')) {
        returnError('forbidden', 'string');
        return;
    }

    $idk = get_header('idk');
    if (is_management_allowed($idk) === false) {
        returnError('centralized');
        exit;
    }

    $profile = db_get_value('id_perfil', 'tperfil', 'id_perfil', $id_profile);
    if ($profile === false) {
        returnError('id_not_found', 'string');
        return;
    }

    $return = profile_delete_profile_and_clean_users($id_profile);

    if ($return === false) {
        returnError('The user profile could not be deleted');
    } else {
        returnData($returnType, ['type' => 'array', 'data' => 1]);
    }
}


/**
 * Disable a module, given agent and module name.
 *
 * @param string            $agent_name  Name of agent.
 * @param string            $module_name Name of the module
 * @param $thrash3 Don't use.
 * @param $thrash4 Don't use.
 * // http://localhost/pandora_console/include/api.php?op=set&op2=enable_module&id=garfio&id2=Status
 */
function api_set_disable_module($agent_name, $module_name, $other, $thrash4)
{
    if (defined('METACONSOLE')) {
        return;
    }

    $agent_by_alias = false;

    if ($other['data'][0] === '1') {
        $agent_by_alias = true;
    }

    if ($agent_by_alias) {
        $ids_agents = agents_get_agent_id_by_alias($agent_name);

        foreach ($ids_agents as $id) {
            if (!util_api_check_agent_and_print_error($id['id_agente'], 'string', 'AD')) {
                return;
            }
        }
    } else {
        $id_agent = agents_get_agent_id($agent_name);

        if (!util_api_check_agent_and_print_error($id_agent, 'string', 'AD')) {
            return;
        }
    }

    if ($agent_by_alias) {
        $agents_affected = 0;

        foreach ($ids_agents as $id) {
            $id_agent_module = db_get_value_filter('id_agente_modulo', 'tagente_modulo', ['id_agente' => $id['id_agente'], 'nombre' => $module_name]);

            $result = modules_change_disabled($id_agent_module, 1);

            if ($result === NOERR) {
                $agents_affected++;
            }
        }

        returnData('string', ['type' => 'string', 'data' => __('%d agents affected', $agents_affected)]);
    } else {
        $id_agent_module = db_get_value_filter('id_agente_modulo', 'tagente_modulo', ['id_agente' => $id_agent, 'nombre' => $module_name]);

        $result = modules_change_disabled($id_agent_module, 1);

        if ($result === NOERR) {
            returnData('string', ['type' => 'string', 'data' => __('Module disabled successfully.')]);
        } else {
            returnData('string', ['type' => 'string', 'data' => __('The module could not be disabled.')]);
        }
    }
}


/**
 * Enable a module, given agent and module name.
 *
 * @param string            $agent_name  Name of agent.
 * @param string            $module_name Name of the module
 * @param $thrash3 Don't use.
 * @param $thrash4 Don't use.
 */


function api_set_enable_module($agent_name, $module_name, $other, $thrash4)
{
    if (defined('METACONSOLE')) {
        return;
    }

    $agent_by_alias = false;

    if ($other['data'][0] === '1') {
        $agent_by_alias = true;
    }

    if ($agent_by_alias) {
        $ids_agents = agents_get_agent_id_by_alias($agent_name);

        foreach ($ids_agents as $id) {
            if (!util_api_check_agent_and_print_error($id['id_agente'], 'string', 'AD')) {
                return;
            }
        }
    } else {
        $id_agent = agents_get_agent_id($agent_name);

        if (!util_api_check_agent_and_print_error($id_agent, 'string', 'AD')) {
            return;
        }
    }

    if ($agent_by_alias) {
        $agents_affected = 0;

        foreach ($ids_agents as $id) {
            $id_agent_module = db_get_value_filter('id_agente_modulo', 'tagente_modulo', ['id_agente' => $id['id_agente'], 'nombre' => $module_name]);

            $result = modules_change_disabled($id_agent_module, 0);

            if ($result === NOERR) {
                $agents_affected++;
            }
        }

        returnData('string', ['type' => 'string', 'data' => __('%d agents affected', $agents_affected)]);
    } else {
        $id_agent_module = db_get_value_filter('id_agente_modulo', 'tagente_modulo', ['id_agente' => $id_agent, 'nombre' => $module_name]);

        $result = modules_change_disabled($id_agent_module, 0);

        if ($result === NOERR) {
            returnData('string', ['type' => 'string', 'data' => __('Module enabled successfully.')]);
        } else {
            returnData('string', ['type' => 'string', 'data' => __('The module could not be enabled.')]);
        }
    }
}


/**
 * Disable an alert
 *
 * @param string            $agent_name    Name of agent (for example "myagent")
 * @param string            $module_name   Name of the module (for example "Host alive")
 * @param string            $template_name Name of the alert template (for example, "Warning event")
 * @param $thrash4 Don't use.

// http://localhost/pandora_console/include/api.php?op=set&op2=disable_alert&id=c2cea5860613e363e25f4ba185b54fe28f869ff8a5e8bb46343288337c903531&id2=Status&other=Warning%20condition
 */


function api_set_disable_alert($agent_name, $module_name, $template_name, $thrash4)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'LM')) {
        returnError('forbidden', 'string');
        return;
    }

    $id_agent = agents_get_agent_id($agent_name);
    if (!util_api_check_agent_and_print_error($id_agent, 'string', 'AD')) {
        return;
    }

    $id_agent_module = db_get_value_filter('id_agente_modulo', 'tagente_modulo', ['id_agente' => $id_agent, 'nombre' => $module_name]);
    $id_template = db_get_value_filter('id', 'talert_templates', ['name' => $template_name['data']]);

    $result = db_process_sql(
        "UPDATE talert_template_modules
        SET disabled = 1
        WHERE id_agent_module = $id_agent_module AND id_alert_template = $id_template"
    );

    if ($result) {
        returnData('string', ['type' => 'string', 'data' => 'Alert disabled successfully.']);
    } else {
        returnData('string', ['type' => 'string', 'data' => __('The alert could not be disabled.')]);
    }
}


/**
 * Disable an alert with alias
 *
 * @param string            $agent_alias   Alias of agent (for example "myagent")
 * @param string            $module_name   Name of the module (for example "Host alive")
 * @param string            $template_name Name of the alert template (for example, "Warning event")
 * @param $thrash4 Don't use.

// http://localhost/pandora_console/include/api.php?op=set&op2=disable_alert_alias&id=garfio&id2=Status&other=Warning%20condition
 */


function api_set_disable_alert_alias($agent_alias, $module_name, $template_name, $thrash4)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'LM')) {
        returnError('forbidden', 'string');
        return;
    }

    $agent_id = agents_get_agent_id_by_alias($agent_alias);
    $result = false;
    foreach ($agent_id as $key => $id_agent) {
        if (!util_api_check_agent_and_print_error($id_agent['id_agente'], 'string', 'AD')) {
            continue;
        }

        $id_agent_module = db_get_value_filter('id_agente_modulo', 'tagente_modulo', ['id_agente' => $id_agent['id_agente'], 'nombre' => $module_name]);
        $id_template = db_get_value_filter('id', 'talert_templates', ['name' => $template_name['data']]);

        $result = db_process_sql(
            "UPDATE talert_template_modules
            SET disabled = 1
            WHERE id_agent_module = $id_agent_module AND id_alert_template = $id_template"
        );

        if ($result) {
            returnData('string', ['type' => 'string', 'data' => 'Alert disabled successfully.']);
            return;
        }
    }

    if (!$result) {
        returnData('string', ['type' => 'string', 'data' => __('The alert could not be disabled.')]);
    }
}


/**
 * Enable an alert
 *
 * @param string            $agent_name    Name of agent (for example "myagent")
 * @param string            $module_name   Name of the module (for example "Host alive")
 * @param string            $template_name Name of the alert template (for example, "Warning event")
 * @param $thrash4 Don't use.

// http://localhost/pandora_console/include/api.php?op=set&op2=enable_alert&id=garfio&id2=Status&other=Warning%20condition
 */


function api_set_enable_alert($agent_name, $module_name, $template_name, $thrash4)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'LW')) {
        returnError('forbidden');
        return;
    }

    $id_agent = agents_get_agent_id($agent_name);
    if (!util_api_check_agent_and_print_error($id_agent, 'string', 'AW')) {
        return;
    }

    $id_agent_module = db_get_value_filter('id_agente_modulo', 'tagente_modulo', ['id_agente' => $id_agent, 'nombre' => $module_name]);
    $id_template = db_get_value_filter('id', 'talert_templates', ['name' => $template_name['data']]);

    $result = db_process_sql(
        "UPDATE talert_template_modules
        SET disabled = 0
        WHERE id_agent_module = $id_agent_module AND id_alert_template = $id_template"
    );

    if ($result) {
        returnData('string', ['type' => 'string', 'data' => 'Alert enabled successfully.']);
    } else {
        returnData('string', ['type' => 'string', 'data' => __('The alert could not be enabled.')]);
    }
}


/**
 * Enable an alert with alias
 *
 * @param string            $agent_alias   Alias of agent (for example "myagent")
 * @param string            $module_name   Name of the module (for example "Host alive")
 * @param string            $template_name Name of the alert template (for example, "Warning event")
 * @param $thrash4 Don't use.

// http://localhost/pandora_console/include/api.php?op=set&op2=enable_alert_alias&id=garfio&id2=Status&other=Warning%20condition
 */


function api_set_enable_alert_alias($agent_alias, $module_name, $template_name, $thrash4)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'LW')) {
        returnError('forbidden');
        return;
    }

    $agent_id = agents_get_agent_id_by_alias($agent_alias);
    $result = false;
    foreach ($agent_id as $key => $id_agent) {
        if (!util_api_check_agent_and_print_error($id_agent['id_agente'], 'string', 'AW')) {
            continue;
        }

        $id_agent_module = db_get_value_filter('id_agente_modulo', 'tagente_modulo', ['id_agente' => $id_agent['id_agente'], 'nombre' => $module_name]);
        $id_template = db_get_value_filter('id', 'talert_templates', ['name' => $template_name['data']]);

        $result = db_process_sql(
            "UPDATE talert_template_modules
            SET disabled = 0
            WHERE id_agent_module = $id_agent_module AND id_alert_template = $id_template"
        );

        if ($result) {
            returnData('string', ['type' => 'string', 'data' => 'Alert enabled successfully.']);
            return;
        }
    }

    if (!$result) {
        returnData('string', ['type' => 'string', 'data' => __('The alert could not be enabled.')]);
    }
}


/**
 * Disable all the alerts of one module
 *
 * @param string            $agent_name  Name of agent (for example "myagent")
 * @param string            $module_name Name of the module (for example "Host alive")
 * @param $thrash3 Don't use.
 * @param $thrash4 Don't use.

// http://localhost/pandora_console/include/api.php?op=set&op2=disable_module_alerts&id=garfio&id2=Status
 */


function api_set_disable_module_alerts($agent_name, $module_name, $other, $thrash4)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'LW')) {
        returnError('forbidden');
        return;
    }

    $agent_by_alias = false;

    if ($other['data'][0] === '1') {
        $agent_by_alias = true;
    }

    if ($agent_by_alias) {
        $ids_agents = agents_get_agent_id_by_alias($agent_name);
    } else {
        $id_agent = agents_get_agent_id($agent_name);
    }

    if ($agent_by_alias) {
        foreach ($ids_agents as $id) {
            if (!util_api_check_agent_and_print_error($id['id_agente'], 'string', 'AW')) {
                return;
            }
        }
    } else {
        if (!util_api_check_agent_and_print_error($id_agent, 'string', 'AW')) {
            return;
        }
    }

    if ($agent_by_alias) {
        $agents_affected = 0;

        foreach ($ids_agents as $id) {
            $id_agent_module = db_get_value_filter('id_agente_modulo', 'tagente_modulo', ['id_agente' => $id['id_agente'], 'nombre' => $module_name]);

            $return_value = db_process_sql(
                "UPDATE talert_template_modules
                SET disabled = 1
                WHERE id_agent_module = $id_agent_module"
            );

            if ($return_value != false) {
                $agents_affected++;
            }
        }

        returnData('string', ['type' => 'string', 'data' => __('%d agents affected', $agents_affected)]);
    } else {
        $id_agent_module = db_get_value_filter('id_agente_modulo', 'tagente_modulo', ['id_agente' => $id_agent, 'nombre' => $module_name]);

        db_process_sql(
            "UPDATE talert_template_modules
            SET disabled = 1
            WHERE id_agent_module = $id_agent_module"
        );

        returnData('string', ['type' => 'string', 'data' => 'Alerts disabled successfully.']);
    }
}


/**
 * Enable all the alerts of one module
 *
 * @param string            $agent_name  Name of agent (for example "myagent")
 * @param string            $module_name Name of the module (for example "Host alive")
 * @param $thrash3 Don't use.
 * @param $thrash4 Don't use.
 * // http://localhost/pandora_console/include/api.php?op=set&op2=enable_module_alerts&id=garfio&id2=Status
 */


function api_set_enable_module_alerts($agent_name, $module_name, $other, $thrash4)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'LW')) {
        returnError('forbidden');
        return;
    }

    $agent_by_alias = false;

    if ($other['data'][0] === '1') {
        $agent_by_alias = true;
    }

    if ($agent_by_alias) {
        $ids_agents = agents_get_agent_id_by_alias($agent_name);
    } else {
        $id_agent = agents_get_agent_id($agent_name);
    }

    if ($agent_by_alias) {
        foreach ($ids_agents as $id) {
            if (!util_api_check_agent_and_print_error($id['id_agente'], 'string', 'AW')) {
                return;
            }
        }
    } else {
        if (!util_api_check_agent_and_print_error($id_agent, 'string', 'AW')) {
            return;
        }
    }

    if ($agent_by_alias) {
        $agents_affected = 0;

        foreach ($ids_agents as $id) {
            $id_agent_module = db_get_value_filter('id_agente_modulo', 'tagente_modulo', ['id_agente' => $id['id_agente'], 'nombre' => $module_name]);

            $return_value = db_process_sql(
                "UPDATE talert_template_modules
                SET disabled = 0
                WHERE id_agent_module = $id_agent_module"
            );

            if ($return_value != false) {
                $agents_affected++;
            }
        }

        returnData('string', ['type' => 'string', 'data' => __('%d agents affected', $agents_affected)]);
    } else {
        $id_agent_module = db_get_value_filter('id_agente_modulo', 'tagente_modulo', ['id_agente' => $id_agent, 'nombre' => $module_name]);

        db_process_sql(
            "UPDATE talert_template_modules
            SET disabled = 0
            WHERE id_agent_module = $id_agent_module"
        );

        returnData('string', ['type' => 'string', 'data' => 'Alerts enabled successfully.']);
    }
}


function api_get_tags($thrash1, $thrash2, $other, $returnType, $user_in_db)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'AR')) {
        returnError('forbidden', $returnType);
        return;
    }

    if ($other['type'] == 'string') {
        if ($other['data'] != '') {
            returnError('Parameter error.');
            return;
        } else {
            // Default values
            $separator = ';';
        }
    } else if ($other['type'] == 'array') {
        $separator = $other['data'][0];
    }

    $tags = tags_get_all_tags();

    $data_tags = [];
    foreach ($tags as $id => $tag) {
        $data_tags[] = [
            $id,
            $tag,
        ];
    }

    $data['type'] = 'array';
    $data['data'] = $data_tags;

    returnData($returnType, $data, $separator);
}


/**
 * Total modules for a group given
 *
 * @param int $id_group
 **/
// http://localhost/pandora_console/include/api.php?op=get&op2=total_modules&id=1&apipass=1234&user=admin&pass=pandora
function api_get_total_modules($id_group, $trash1, $trash2, $returnType)
{
    global $config;

    if (is_metaconsole() === true) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'AR')) {
        returnError('forbidden', $returnType);
        return;
    }

    if ($id_group) {
        $groups_clause = '1 = 1';
        if (!users_is_admin($config['id_user'])) {
            $user_groups = implode(',', array_keys(users_get_groups()));
            $groups_clause = "(ta.id_grupo IN ($user_groups) OR tasg.id_group IN ($user_groups))";
        }

        $sql = "SELECT COUNT(DISTINCT(id_agente_modulo))
        FROM tagente_modulo tam, tagente ta
        LEFT JOIN tagent_secondary_group tasg
            ON ta.id_agente = tasg.id_agent
        WHERE tam.id_agente = ta.id_agente AND id_module_group = $id_group
            AND delete_pending = 0 AND $groups_clause";

        $total = db_get_value_sql($sql);
    } else {
        $partial = tactical_status_modules_agents($config['id_user'], false, 'AR');

        $total = (int) $partial['_monitor_total_'];
    }

    $data = [
        'type' => 'string',
        'data' => $total,
    ];

    returnData($returnType, $data);
}


/**
 * Total modules for a given group
 *
 * @param int $id_group
 **/
// http://localhost/pandora_console/include/api.php?op=get&op2=total_agents&id=2&apipass=1234&user=admin&pass=pandora
function api_get_total_agents($id_group, $trash1, $trash2, $returnType)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    // Only for agent reader of specified group
    if (!check_acl($config['id_user'], $id_group, 'AR')) {
        returnError('forbidden', $returnType);
        return;
    }

    $total_agents = agents_count_agents_filter(['id_grupo' => $id_group]);

    $data = [
        'type' => 'string',
        'data' => $total_agents,
    ];
    returnData($returnType, $data);
}


/**
 * Agent name for a given id
 *
 * @param int $id_agent
 **/
// http://localhost/pandora_console/include/api.php?op=get&op2=agent_name&id=1&apipass=1234&user=admin&pass=pandora
function api_get_agent_name($id_agent, $trash1, $trash2, $returnType)
{
    if (defined('METACONSOLE')) {
        return;
    }

    if (!util_api_check_agent_and_print_error($id_agent, $returnType)) {
        return;
    }

    $sql = sprintf(
        'SELECT nombre
        FROM tagente
        WHERE id_agente = %d',
        $id_agent
    );
    $value = db_get_value_sql($sql);

    $data = [
        'type' => 'string',
        'data' => $value,
    ];

    returnData($returnType, $data);
}


/**
 *  Return the ID or an hash of IDs of the detected given agents
 *
 * @param array or value $data
 **/
function api_get_agent_id($trash1, $trash2, $data, $returnType)
{
    if (is_metaconsole()) {
        return;
    }

    if (empty($returnType)) {
        $returnType = 'json';
    }

    $response = [];

    if ($data['type'] == 'array') {
        $response['type'] = 'array';
        $response['data'] = [];

        foreach ($data['data'] as $name) {
            $response['data'][$name] = agents_get_agent_id($name, 1);
        }
    } else {
        $response['type'] = 'string';
        $response['data'] = agents_get_agent_id($data['data'], 1);
    }

    returnData($returnType, $response);
}


/**
 * Agent alias for a given id
 *
 * @param int $id_agent
 * @param int $id_node Only for metaconsole
 * @param $thrash1 Don't use.
 * @param $returnType
 **/
// http://localhost/pandora_console/include/api.php?op=get&op2=agent_alias&id=1&apipass=1234&user=admin&pass=pandora
// http://localhost/pandora_console/enterprise/meta/include/api.php?op=get&op2=agent_alias&id=1&id2=1&apipass=1234&user=admin&pass=pandora
function api_get_agent_alias($id_agent, $id_node, $trash1, $returnType)
{
    $table_agent_alias = 'tagente';
    $force_meta = false;

    if (is_metaconsole()) {
        $table_agent_alias = 'tmetaconsole_agent';
        $force_meta = true;
        $id_agent = db_get_value_sql("SELECT id_agente FROM tmetaconsole_agent WHERE id_tagente = $id_agent AND id_tmetaconsole_setup = $id_node");
    }

    if (!util_api_check_agent_and_print_error($id_agent, $returnType, 'AR', $force_meta)) {
        return;
    }

    $sql = sprintf(
        'SELECT alias
        FROM '.$table_agent_alias.'
        WHERE id_agente = %d',
        $id_agent
    );
    $value = db_get_value_sql($sql);

    $data = [
        'type' => 'string',
        'data' => $value,
    ];

    returnData($returnType, $data);
}


/**
 * Module name for a given id
 *
 * @param int $id_group
 **/
// http://localhost/pandora_console/include/api.php?op=get&op2=module_name&id=20&apipass=1234&user=admin&pass=pandora
function api_get_module_name($id_module, $trash1, $trash2, $returnType)
{
    if (defined('METACONSOLE')) {
        return;
    }

    if (!util_api_check_agent_and_print_error(modules_get_agentmodule_agent($id_module), $returnType)) {
        return;
    }

    $sql = sprintf(
        'SELECT nombre
        FROM tagente_modulo
        WHERE id_agente_modulo = %d',
        $id_module
    );

    $value = db_get_value_sql($sql);

    if ($value === false) {
        returnError('id_not_found', $returnType);
    }

    $data = [
        'type' => 'string',
        'data' => $value,
    ];

    returnData($returnType, $data);
}


// http://localhost/pandora_console/include/api.php?op=get&op2=alert_action_by_group&id=3&id2=1&apipass=1234&user=admin&pass=pandora
function api_get_alert_action_by_group($id_group, $id_action, $trash2, $returnType)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], $id_group, 'LW')) {
        returnError('forbidden', $returnType);
        return;
    }

    // Get only the user groups
    $filter_groups = '1 = 1';
    if (!users_is_admin($config['id_user'])) {
        $user_groups = implode(',', array_keys(users_get_groups()));
        $filter_groups = "(ta.id_grupo IN ($user_groups) OR tasg.id_group IN ($user_groups))";
    }

    $sql = "SELECT SUM(internal_counter)
        FROM
            talert_template_modules tatm,
            tagente ta LEFT JOIN tagent_secondary_group tasg
                ON ta.id_agente = tasg.id_agent,
            tagente_modulo tam
        WHERE tam.id_agente = ta.id_agente
            AND tatm.id_agent_module = tam.id_agente_modulo
            AND ta.disabled = 0
            AND $filter_groups";

    $value = db_get_value_sql($sql);

    if ($value === false) {
        returnError('No alert found');
        return;
    } else if ($value == '') {
        $value = 0;
    }

    $data = [
        'type' => 'string',
        'data' => $value,
    ];
    returnData($returnType, $data);
}


// http://localhost/pandora_console/include/api.php?op=get&op2=event_info&id=58&apipass=1234&user=admin&pass=pandora
function api_get_event_info($id_event, $trash1, $trash, $returnType)
{
    global $config;

    $sql = sprintf(
        'SELECT *
        FROM tevento
        WHERE id_evento= %d',
        $id_event
    );

    $event_data = db_get_row_sql($sql);

    // Check the access to group
    if (!empty($event_data['id_grupo'])
        && $event_data['id_grupo'] > 0
        && !$event_data['id_agente']
    ) {
        if (!check_acl($config['id_user'], $event_data['id_grupo'], 'ER')) {
            returnError('forbidden', $returnType);
            return;
        }
    }

    // Check the access to agent
    if (!empty($event_data['id_agente'])
        && $event_data['id_agente'] > 0
    ) {
        if (!util_api_check_agent_and_print_error(
            $event_data['id_agente'],
            $returnType
        )
        ) {
            return;
        }
    }

    $result = '';
    $i = 0;
    foreach ($event_data as $key => $data) {
        $data = strip_tags($data);
        $data = str_replace("\n", ' ', $data);
        $data = str_replace(';', ' ', $data);
        if ($i == 0) {
            $result = $key.': '.$data.'<br>';
        } else {
            $result .= $key.': '.$data.'<br>';
        }

        $i++;
    }

    $data = [
        'type' => 'string',
        'data' => $result,
    ];

    returnData($returnType, $data);
    return;
}


// http://127.0.0.1/pandora_console/include/api.php?op=set&op2=create_tag&other=tag_name|tag_description|tag_url|tag_email&other_mode=url_encode_separator_|&apipass=1234&user=admin&pass=pandora
function api_set_create_tag($id, $trash1, $other, $returnType)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'PM')) {
        returnError('forbidden', 'string');
        return;
    }

    $idk = get_header('idk');
    if (is_management_allowed($idk) === false) {
        returnError('centralized');
        exit;
    }

    $data = [];

    if ($other['type'] == 'string') {
        $data['name'] = $other['data'];
    } else if ($other['type'] == 'array') {
        $data['name'] = $other['data'][0];

        if ($other['data'][1] != '') {
            $data['description'] = $other['data'][1];
        } else {
            $data['description'] = '';
        }

        if ($other['data'][1] != '') {
            $data['url'] = $other['data'][2];
        } else {
            $data['url'] = '';
        }

        if ($other['data'][1] != '') {
            $data['email'] = $other['data'][3];
        } else {
            $data['email'] = '';
        }
    }

    if (tags_create_tag($data)) {
        returnData(
            'string',
            [
                'type' => 'string',
                'data' => '1',
            ]
        );
    } else {
        returnError('error_set_tag_user_profile', 'Could not create tag for user profile.');
    }
}


// http://127.0.0.1/pandora_console/include/api.php?op=set&op2=create_event&id=name_event&other=2|system|3|admin|2|1|10|0|comments||Pandora||critical_inst|warning_inst|unknown_inst|other||&other_mode=url_encode_separator_|&apipass=1234&user=admin&pass=pandora
// http://127.0.0.1/pandora_console/include/api.php?op=set&op2=create_event&id=name_event&other=textodelevento|10|2|0|admin|going_down_critical|4|&other_mode=url_encode_separator_|&apipass=1234&user=admin&pass=pandora
function api_set_create_event($id, $trash1, $other, $returnType)
{
    global $config;

    if (!check_acl($config['id_user'], 0, 'EW')) {
        returnError('forbidden', 'string');
        return;
    }

    if ($other['type'] == 'string') {
        returnError('Parameter error.');
        return;
    } else if ($other['type'] == 'array') {
        $values = [];

        if ($other['data'][0] != '') {
            $values['event'] = $other['data'][0];
        } else {
            returnError('Event text required.');
            return;
        }

        if ($other['data'][1] != '') {
            if (!check_acl($config['id_user'], $other['data'][1], 'AR')) {
                returnError('forbidden', 'string');
                return;
            }

            $values['id_grupo'] = $other['data'][1];

            if (groups_get_name($values['id_grupo']) === false) {
                returnError('Group ID does not exist');
                return;
            }
        } else {
            returnError('Group ID required.');
            return;
        }

        if (!empty($other['data'][17]) && is_metaconsole()) {
            $id_server = db_get_row_filter('tmetaconsole_setup', ['id' => $other['data'][17]]);
            if ($id_server === false) {
                returnError('Server ID does not exist in database.');
                return;
            }

            $values['server_id'] = $other['data'][17];
        } else {
            $values['server_id'] = 0;
        }

        $error_msg = '';
        if ($other['data'][2] != '') {
            // Id agent assignment. If come from pandora_revent, id_agent can be 0.
            $id_agent = $other['data'][2];
            // To the next if is metaconsole and id_agent is not none.
            if (is_metaconsole() === true && $id_agent > 0) {
                // On metaconsole, connect with the node to check the permissions
                if (empty($values['server_id'])) {
                    $agent_cache = db_get_row('tmetaconsole_agent', 'id_tagente', $id_agent);
                } else {
                    $agent_cache = db_get_row_filter('tmetaconsole_agent', ['id_tagente' => $id_agent, 'id_tmetaconsole_setup' => $values['server_id']]);
                }

                if ($agent_cache === false) {
                    returnError('id_not_found', 'string');
                    return;
                }

                if (!metaconsole_connect(null, $agent_cache['id_tmetaconsole_setup'])) {
                    returnError('Cannot connect with the agent node.');
                    return;
                }

                $id_agent = $agent_cache['id_tagente'];
            }

            $values['id_agente'] = $id_agent;

            if ((int) $id_agent > 0 && util_api_check_agent_and_print_error($id_agent, 'string', 'AR') === false) {
                if (is_metaconsole()) {
                    metaconsole_restore_db();
                }

                return;
            }

            if (is_metaconsole()) {
                metaconsole_restore_db();
            }
        } else {
            if ($other['data'][19] != '') {
                $values['id_agente'] = db_get_value('id_agente', 'tagente', 'nombre', $other['data'][19]);
                if (!$values['id_agente']) {
                    if ($other['data'][20] == 1) {
                        $values['id_agente'] = db_process_sql_insert(
                            'tagente',
                            [
                                'nombre'   => $other['data'][19],
                                'id_grupo' => $other['data'][1],
                                'alias'    => $other['data'][19],
                            ]
                        );
                    } else {
                        $error_msg = 'name_not_exist';
                    }
                }
            } else {
                $error_msg = 'none';
            }
        }

        if ($error_msg != '') {
            if ($error_msg == 'id_not_exist') {
                returnError('Agent ID does not exist.');
            } else if ($error_msg == 'name_not_exist') {
                returnError('Agent Name does not exist.');
            } else if ($error_msg == 'none') {
                returnError('Agent ID or name required.');
            }

            return;
        }

        if ($other['data'][3] != '') {
            $values['status'] = $other['data'][3];
        } else {
            $values['status'] = 0;
        }

        $values['id_usuario'] = $other['data'][4];

        if ($other['data'][5] != '') {
            $values['event_type'] = $other['data'][5];
        } else {
            $values['event_type'] = 'unknown';
        }

        if ($other['data'][6] != '') {
            $values['priority'] = $other['data'][6];
        } else {
            $values['priority'] = 0;
        }

        if ($other['data'][7] != '') {
            $values['id_agentmodule'] = $other['data'][7];
        } else {
            $values['id_agentmodule'] = 0;
        }

        if ($other['data'][8] != '') {
            $values['id_alert_am'] = $other['data'][8];
        } else {
            $values['id_alert_am'] = 0;
        }

        if ($other['data'][9] != '') {
            $values['critical_instructions'] = $other['data'][9];
        } else {
            $values['critical_instructions'] = '';
        }

        if ($other['data'][10] != '') {
            $values['warning_instructions'] = $other['data'][10];
        } else {
            $values['warning_instructions'] = '';
        }

        if ($other['data'][11] != '') {
            $values['unknown_instructions'] = $other['data'][11];
        } else {
            $values['unknown_instructions'] = '';
        }

        if ($other['data'][14] != '') {
            $values['source'] = $other['data'][14];
        } else {
            $values['source'] = get_product_name();
        }

        if ($other['data'][15] != '') {
            $values['tags'] = $other['data'][15];
        } else {
            $values['tags'] = '';
        }

        if ($other['data'][16] != '') {
            $values['custom_data'] = $other['data'][16];
        } else {
            $values['custom_data'] = '';
        }

        if ($other['data'][18] != '') {
            $values['id_extra'] = $other['data'][18];
            $sql_validation = 'SELECT id_evento,estado FROM tevento where estado IN (0,2) and id_extra ="'.$other['data'][18].'";';
            $validation = db_get_all_rows_sql($sql_validation);

            if ($validation) {
                foreach ($validation as $val) {
                    if ((bool) $config['keep_in_process_status_extra_id'] === true
                        && (int) $val['estado'] === EVENT_STATUS_INPROCESS
                        && (int) $values['status'] === 0
                    ) {
                        $values['status'] = 2;
                    }

                    api_set_validate_event_by_id($val['id_evento']);
                }
            }
        } else {
            $values['id_extra'] = '';
        }

        $custom_data = base64_decode($values['custom_data']);
        $custom_data = mysql_escape_string_sql($custom_data);

        $return = events_create_event(
            $values['event'],
            $values['id_grupo'],
            $values['id_agente'],
            $values['status'],
            $values['id_usuario'],
            $values['event_type'],
            $values['priority'],
            $values['id_agentmodule'],
            $values['id_alert_am'],
            $values['critical_instructions'],
            $values['warning_instructions'],
            $values['unknown_instructions'],
            $values['source'],
            $values['tags'],
            $custom_data,
            $values['server_id'],
            $values['id_extra']
        );

        if ($other['data'][12] != '') {
            // user comments
            if ($return !== false) {
                // event successfully created
                $user_comment = $other['data'][12];
                $res = events_comment(
                    $return,
                    $user_comment,
                    'Added comment'
                );
                if ($other['data'][13] != '') {
                    // owner user
                    if ($res !== false) {
                        // comment added
                        $owner_user = $other['data'][13];
                        events_change_owner(
                            $return,
                            $owner_user,
                            true
                        );
                    }
                }
            }
        }

        $data['type'] = 'string';
        if ($return === false) {
            $data['data'] = 0;
        } else {
            $data['data'] = $return;
        }

        returnData($returnType, $data);
        return;
    }
}


/**
 * Add event commet.
 *
 * @param $id event id.
 * @param $thrash2 Don't use.
 * @param array             $other it's array, but only <csv_separator> is available.
 * @param $thrash3 Don't use.
 *
 * example:
 *   http://127.0.0.1/pandora_console/include/api.php?op=set&op2=add_event_comment&id=event_id&other=string|&other_mode=url_encode_separator_|&apipass=1234&user=admin&pass=pandora
 */
function api_set_add_event_comment($id, $thrash2, $other, $thrash3)
{
    global $config;

    if (!check_acl($config['id_user'], 0, 'EW')) {
        returnError('forbidden', 'string');
        return;
    }

    if ($other['type'] == 'string') {
        returnError('Parameter error.');
        return;
    } else if ($other['type'] == 'array') {
        $comment = $other['data'][0];

        $node_int = 0;
        if (is_metaconsole() === true) {
            if (isset($other['data'][1]) === true
                && empty($other['data'][1]) === false
            ) {
                $node_int = $other['data'][1];
            }
        }

        try {
            if (is_metaconsole() === true
                && (int) $node_int > 0
            ) {
                $node = new Node($node_int);
                $node->connect();
            }

            $status = events_comment(
                $id,
                $comment,
                'Added comment'
            );
        } catch (\Exception $e) {
            // Unexistent agent.
            if (is_metaconsole() === true
                && $node_int > 0
            ) {
                $node->disconnect();
            }

            $status = false;
        } finally {
            if (is_metaconsole() === true
                && $node_int > 0
            ) {
                $node->disconnect();
            }
        }

        if (is_error($status)) {
            returnError(
                'The event comment could not be added.'
            );
            return;
        }
    }

    returnData('string', ['type' => 'string', 'data' => $status]);
    return;
}


// http://localhost/pandora_console/include/api.php?op=get&op2=tactical_view&apipass=1234&user=admin&pass=pandora
function api_get_tactical_view($trash1, $trash2, $trash3, $returnType)
{
    if (defined('METACONSOLE')) {
        return;
    }

    $tactical_info = reporting_get_group_stats();

    switch ($returnType) {
        case 'string':
            $i = 0;
            foreach ($tactical_info as $key => $data) {
                if ($i == 0) {
                    $result = $key.': '.$data.'<br>';
                } else {
                    $result .= $key.': '.$data.'<br>';
                }

                $i++;
            }

            $data = [
                'type' => 'string',
                'data' => $result,
            ];
        break;

        case 'csv':
            $data = [
                'type' => 'array',
                'data' => [$tactical_info],
            ];
        break;
    }

    returnData($returnType, $data);
    return;

}


// http://localhost/pandora_console/include/api.php?op=get&op2=netflow_get_data&other=1348562410|1348648810|0|base64_encode(json_encode($filter))|none|50|bytes&other_mode=url_encode_separator_|&apipass=pandora&user=pandora&pass=pandora'
function api_get_netflow_get_data($discard_1, $discard_2, $params)
{
    if (defined('METACONSOLE')) {
        return;
    }

    // Parse function parameters
    $start_date = $params['data'][0];
    $end_date = $params['data'][1];
    $interval_length = $params['data'][2];
    $filter = json_decode(base64_decode($params['data'][3]), true);
    $aggregate = $params['data'][4];
    $max = $params['data'][5];
    $unit = $params['data'][6];
    $address_resolution = $params['data'][7];

    // Get netflow data
    $data = netflow_get_data($start_date, $end_date, $interval_length, $filter, $aggregate, $max, $unit, '', $address_resolution);

    returnData('json', $data);
    return;
}


// http://localhost/pandora_console/include/api.php?op=get&op2=netflow_get_stats&other=1348562410|1348648810|base64_encode(json_encode($filter))|none|50|bytes&other_mode=url_encode_separator_|&apipass=pandora&user=pandora&pass=pandora'
function api_get_netflow_get_stats($discard_1, $discard_2, $params)
{
    if (defined('METACONSOLE')) {
        return;
    }

    // Parse function parameters.
    $start_date = $params['data'][0];
    $end_date = $params['data'][1];
    $filter = json_decode(base64_decode($params['data'][2]), true);
    $aggregate = $params['data'][3];
    $max = $params['data'][4];
    $unit = $params['data'][5];
    $address_resolution = $params['data'][6];

    // Get netflow data
    $data = netflow_get_stats($start_date, $end_date, $filter, $aggregate, $max, $unit, '', $address_resolution);

    returnData('json', $data);
    return;
}


/**
 *
 * @param  $trash1       Don't use.
 * @param  $trash2       Don't use.
 * @param  array                  $params Call parameters.
 * @return void
 */
function api_get_netflow_get_top_N($trash1, $trash2, $params)
{
    if (is_metaconsole() === true) {
        return;
    }

    // Parse function parameters.
    $start_date = $params['data'][0];
    $end_date = $params['data'][1];
    $filter = json_decode(base64_decode($params['data'][2]), true);
    $max = $params['data'][3];

    // Get netflow data.
    $data = netflow_get_top_N($start_date, $end_date, $filter, $max, '');

    returnData('json', $data);

    return;
}


// http://localhost/pandora_console/include/api.php?op=get&op2=netflow_get_summary&other=1348562410|1348648810|_base64_encode(json_encode($filter))&other_mode=url_encode_separator_|&apipass=pandora&user=pandora&pass=pandora'
function api_get_netflow_get_summary($discard_1, $discard_2, $params)
{
    if (is_metaconsole() === true) {
        return;
    }

    // Parse function parameters
    $start_date = $params['data'][0];
    $end_date = $params['data'][1];
    $filter = json_decode(base64_decode($params['data'][2]), true);

    // Get netflow data
    $data = netflow_get_summary($start_date, $end_date, $filter);
    returnData('json', $data);
    return;
}


// http://localhost/pandora_console/include/api.php?op=set&op2=validate_event_by_id&id=23&apipass=1234&user=admin&pass=pandora
function api_set_validate_event_by_id($id, $trash1=null, $trash2=null, $returnType='string')
{
    global $config;

    if (!check_acl($config['id_user'], 0, 'EW')) {
        returnError('forbidden', 'string');
        return;
    }

    $data['type'] = 'string';
    $check_id = db_get_value('id_evento', 'tevento', 'id_evento', $id);

    if ($check_id) {
        // event exists
        $status = db_get_value('estado', 'tevento', 'id_evento', $id);
        if ($status == 1) {
            // event already validated
            $data['data'] = 'Event already validated.';
        } else {
            $ack_utimestamp = time();

            events_comment($id, '', 'Changed the status to validated.');

            $values = [
                'ack_utimestamp' => $ack_utimestamp,
                'estado'         => 1,
            ];

            $result = db_process_sql_update('tevento', $values, ['id_evento' => $id]);

            if ($result === false) {
                $data['data'] = 'The event could not be validated.';
            } else {
                $data['data'] = 'Validated event.';
            }
        }
    } else {
        $data['data'] = 'Event does not exist.';
    }

    returnData($returnType, $data);
    return;
}


/**
 *
 * @param $trash1
 * @param $trash2
 * @param array $other it's array, but only <csv_separator> is available.
 * @param $returnType
 */
// http://localhost/pandora_console/include/api.php?op=get&op2=pandora_servers&return_type=csv&apipass=1234&user=admin&pass=pandora
function api_get_pandora_servers($trash1, $trash2, $other, $returnType)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'AW')) {
        returnError('forbidden', $returnType);
        return;
    }

    if (!isset($other['data'][0])) {
        $separator = ';';
        // by default
    } else {
        $separator = $other['data'][0];
    }

    $servers = servers_get_info();

    foreach ($servers as $server) {
        $dd = [
            'name'           => $server['name'],
            'status'         => $server['status'],
            'type'           => $server['type'],
            'master'         => $server['master'],
            'modules'        => $server['modules'],
            'modules_total'  => $server['modules_total'],
            'lag'            => $server['lag'],
            'module_lag'     => $server['module_lag'],
            'threads'        => $server['threads'],
            'queued_modules' => $server['queued_modules'],
            'keepalive'      => $server['keepalive'],
            'id_server'      => $server['id_server'],
        ];

        // servers_get_info() returns "<a http:....>servername</a>" for recon server's name.
        // i don't know why and the following line is a temprary workaround...
        $dd['name'] = preg_replace('/<[^>]*>/', '', $dd['name']);

        switch ($dd['type']) {
            case 'snmp':
            case 'event':
                $dd['modules'] = '';
                $dd['modules_total'] = '';
                $dd['lag'] = '';
                $dd['module_lag'] = '';
            break;

            case 'export':
                $dd['lag'] = '';
                $dd['module_lag'] = '';
            break;

            default:
            break;
        }

        $returnVar[] = $dd;
    }

    $data = [
        'type' => 'array',
        'data' => $returnVar,
    ];

    returnData($returnType, $data, $separator);
    return;
}


/**
 * Enable/Disable agent given an id
 *
 * @param string            $id    String Agent ID
 * @param $thrash2 not used.
 * @param array             $other it's array, $other as param is <enable/disable value> in this order and separator char
 *              (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *              example:
 *
 *                 example 1 (Enable agent 'example_id')
 *
 *              api.php?op=set&op2=enable_disable_agent&id=example_id&other=0&other_mode=url_encode_separator_|
 *
 *                 example 2 (Disable agent 'example_id')
 *
 *              api.php?op=set&op2=enable_disable_agent&id=example_id16&other=1&other_mode=url_encode_separator_|
 *
 * @param $thrash3 Don't use.
 */


function api_set_enable_disable_agent($id, $thrash2, $other, $thrash3)
{
    if (defined('METACONSOLE')) {
        return;
    }

    if ($id == '') {
        returnError(
            'Failed switching enable/disable agent. Id_agent cannot be left blank.'
        );
        return;
    }

    if (!util_api_check_agent_and_print_error($id, 'string', 'AD')) {
        return;
    }

    if ($other['data'][0] != '0' and $other['data'][0] != '1') {
        returnError(
            'Failed switching enable/disable agent. Enable/disable value cannot be left blank.'
        );
        return;
    }

    if (agents_get_name($id) == false) {
        returnError(
            'Failed switching enable/disable agent. The agent does not exist.'
        );
        return;
    }

    $disabled = ( $other['data'][0] ? 0 : 1 );

    enterprise_hook(
        'config_agents_update_config_token',
        [
            $id,
            'standby',
            $disabled ? '1' : '0',
        ]
    );

    $result = db_process_sql_update(
        'tagente',
        ['disabled' => $disabled],
        ['id_agente' => $id]
    );

    if (is_error($result)) {
        // TODO: Improve the error returning more info
        returnError('Failed switching enable/disable agent.');
    } else {
        if ($disabled == 0) {
            returnData(
                'string',
                [
                    'type' => 'string',
                    'data' => __('Enabled agent.'),
                ]
            );
        } else {
            returnData(
                'string',
                [
                    'type' => 'string',
                    'data' => __('Disabled agent.'),
                ]
            );
        }
    }
}


/**
 * Validate alert from Pager Duty service. This call will be setted in PagerDuty's service as a Webhook to
 * validate the alerts of Pandora FMS previously linked to PagertDuty when its were validated from PagerDuty.
 *
 * This call only have a parameter: id=alert
 *
 * Call example:
 *     http://127.0.0.1/pandora_console/include/api.php?op=set&op2=pagerduty_webhook&apipass=1234&user=admin&pass=pandora&id=alert
 *
 * TODO: Add support to events.
 */


function api_set_pagerduty_webhook($type, $matchup_path, $tresh2, $return_type)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'PM')) {
        returnError('forbidden', 'string');
        return;
    }

    $pagerduty_data = json_decode(file_get_contents('php://input'), true);

    foreach ($pagerduty_data['messages'] as $pm) {
        $incident = $pm['data']['incident'];
        $incident_type = $pm['type'];
        // incident.acknowledge
        // incident.resolve
        // incident.trigger
        switch ($type) {
            case 'alert':
                // Get all the alerts that the user can see
                $id_groups = array_keys(users_get_groups($config['id_user'], 'AR', false));
                $alerts = get_group_alerts($id_groups);

                // When an alert is resolved, the Pandoras alert will be validated
                if ($incident_type != 'incident.resolve') {
                    break;
                }

                $alert_id = 0;
                foreach ($alerts as $al) {
                        $key = file_get_contents($matchup_path.'/.pandora_pagerduty_id_'.$al['id']);
                    if ($key == $incident['incident_key']) {
                        $alert_id = $al['id'];
                        break;
                    }
                }

                if ($alert_id != 0) {
                    alerts_validate_alert_agent_module($alert_id);
                }
            break;

            case 'event':
            break;
        }
    }
}


/**
 * Get special days, and print all the result like a csv.
 *
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array             $other it's array, but only <csv_separator> is available.
 * @param $thrash3 Don't use.
 *
 * example:
 *  api.php?op=get&op2=special_days&other=,;
 */
function api_get_special_days($thrash1, $thrash2, $other, $thrash3)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'LM')) {
        returnError('forbidden', 'csv');
        return;
    }

    if (!isset($other['data'][0])) {
        $separator = ';';
        // by default
    } else {
        $separator = $other['data'][0];
    }

    $user_groups = implode(',', array_keys(users_get_groups($config['id_user'], 'LM')));
    $filter = "id_group IN ($user_groups)";

    $special_days = @db_get_all_rows_filter('talert_special_days', $filter);

    if ($special_days !== false) {
        $data['type'] = 'array';
        $data['data'] = $special_days;
    }

    if (!$special_days) {
        returnError('Could not get special_days.');
    } else {
        returnData('csv', $data, $separator);
    }
}


/**
 * Create a special day. And return the id if new special day.
 *
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array             $other it's array, $other as param is <special_day>;<day_code>;<description>;<id_group>; in this order
 *              and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 * @param $thrash3 Don't use
 *
 * example:
 *  api.php?op=set&op2=create_special_day&other=2014-05-03|sunday|text|0&other_mode=url_encode_separator_|
 */
function api_set_create_special_day($thrash1, $thrash2, $other, $thrash3)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    $special_day = $other['data'][0];
    $same_day = $other['data'][1];
    $description = $other['data'][2];
    $idGroup = $other['data'][3];
    $calendar_name = (isset($other['data'][4]) === true) ? $other['data'][4] : 'Default';

    if (!check_acl($config['id_user'], $idGroup, 'LM', true)) {
        returnError('forbidden', 'string');
        return;
    }

    $check_id_special_day = db_get_value_filter('id', 'talert_special_days', ['date' => $special_day, 'id_group' => $idGroup]);

    if ($check_id_special_day) {
        returnError('Special Day could not be created. Specified day already exist.');
        return;
    }

    if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $special_day)) {
        returnError('Special Day could not be created. Invalid date format.');
        return;
    }

    if (!isset($idGroup) || $idGroup == '') {
        returnError('Special Day could not be created. Group id cannot be left blank.');
        return;
    } else {
        $group = groups_get_group_by_id($idGroup);

        if ($idGroup != 0 && $group == false) {
            returnError('Special Day could not be created. Id_group does not exist.');

            return;
        }

        if (!check_acl($config['id_user'], $idGroup, 'LM')) {
            returnError('forbidden', 'string');
            return;
        }
    }

    $weekdays = [
        'monday'    => 1,
        'tuesday'   => 2,
        'wednesday' => 3,
        'thursday'  => 4,
        'friday'    => 5,
        'saturday'  => 6,
        'sunday'    => 7,
        'holiday'   => 8,
    ];

    $day_code = (isset($weekdays[$same_day]) === true) ? $weekdays[$same_day] : 0;

    if ($day_code === 0) {
        returnError('Special Day could not be created. Same day doesn\'t exists.');
        return;
    }

    $id_calendar = db_get_value_sql(
        sprintf(
            'SELECT id FROM talert_calendar WHERE name="%s"',
            $calendar_name
        )
    );

    if ($id_calendar === false) {
        returnError('Special Day could not be created. Calendar doesn\'t exists.');
        return;
    }

    try {
        $sd = new SpecialDay();
        $sd->date($special_day);
        $sd->id_group($idGroup);
        $sd->day_code($day_code);
        $sd->description($description);
        $sd->id_calendar($id_calendar);
        $sd->save();
        if ($sd->save() === true) {
            returnData('string', ['type' => 'string', 'data' => $sd->id()]);
        } else {
            returnError('Special Day could not be created');
        }
    } catch (Exception $e) {
        returnData('string', ['type' => 'string', 'data' => $e]);
    }
}


/**
 * Create a service and return service id.
 *
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array             $other it's array, $other as param is <description>;<id_group>;<critical>;
 *             <warning>;<id_agent>;<sla_interval>;<sla_limit>;<id_warning_module_template_alert>;
 *             <id_critical_module_template_alert>;<id_critical_module_sla_template_alert>;<quiet>;
 *             <cascade_protection>;<evaluate_sla>;
 *             in this order and separator char (after text ; ) and separator
 *             (pass in param othermode as othermode=url_encode_separator_<separator>)
 * @param $thrash3 Don't use
 *
 * example:
 * http://127.0.0.1/pandora_console/include/api.php?op=set&op2=create_service&return_type=json
 * &other=test1%7CDescripcion de prueba%7C12%7C1%7C0.5%7C1&other_mode=url_encode_separator_%7C&apipass=1234&user=admin&pass=pandora
 */
function api_set_create_service($thrash1, $thrash2, $other, $thrash3)
{
    global $config;

    if (is_metaconsole()) {
        return;
    }

    $name = $other['data'][0];
    $description = $other['data'][1];
    $id_group = $other['data'][2];
    $critical = $other['data'][3];
    $warning = $other['data'][4];
    $mode = 0;
    $id_agent = $other['data'][5];
    $sla_interval = $other['data'][6];
    $sla_limit = $other['data'][7];
    $id_warning_module_template = $other['data'][8];
    $id_critical_module_template = $other['data'][9];
    $id_unknown_module_template = 0;
    $id_critical_module_sla = $other['data'][10];
    $quiet = $other['data'][11];
    $cascade_protection = $other['data'][12];
    $evaluate_sla = $other['data'][13];
    $is_favourite = $other['data'][14];
    $unknown_as_critical = $other['data'][15];
    $server_name = $other['data'][16];

    if (empty($name)) {
        returnError('The service could not be created. No name provided.');
        return;
    }

    if (empty($id_group)) {
        // By default applications
        $id_group = 12;
    }

    if (!check_acl($config['id_user'], $id_group, 'AR')) {
        returnError('forbidden', 'string');
        return;
    }

    if (empty($critical)) {
        $critical = 1;
    }

    if (empty($warning)) {
        $warning = 0.5;
    }

    if (empty($id_agent)) {
        returnError('The service could not be created. No agent ID provided.');
        return;
    } else {
        if (!util_api_check_agent_and_print_error($id_agent, 'string', 'AR')) {
            return;
        }
    }

    if (empty($sla_interval)) {
        // By default one month
        $sla_interval = 2592000;
    }

    if (empty($sla_limit)) {
        $sla_limit = 95;
    }

    if (empty($id_warning_module_template)) {
        $id_warning_module_template = 0;
    }

    if (empty($id_critical_module_template)) {
        $id_critical_module_template = 0;
    }

    if (empty($id_critical_module_sla)) {
        $id_critical_module_sla = 0;
    }

    if (empty($quiet)) {
        $quiet = 0;
    }

    if (empty($cascade_protection)) {
        $cascade_protection = 0;
    }

    if (empty($evaluate_sla)) {
        $evaluate_sla = 0;
    }

    if (empty($is_favourite)) {
        $is_favourite = false;
    }

    if (empty($unknown_as_critical)) {
        $unknown_as_critical = false;
    }

    if (empty($server_name)) {
        $server_name = null;
    }

    $result = enterprise_hook(
        'services_create_service',
        [
            $name,
            $description,
            $id_group,
            $critical,
            $warning,
            false,
            SECONDS_5MINUTES,
            $mode,
            $id_agent,
            $sla_interval,
            $sla_limit,
            $id_warning_module_template,
            $id_critical_module_template,
            $id_unknown_module_template,
            $id_critical_module_sla,
            $quiet,
            $cascade_protection,
            $evaluate_sla,
        ]
    );

    if ($result) {
        returnData('string', ['type' => 'string', 'data' => $result]);
    } else {
        returnError('The service could not be created.');
    }
}


/**
 * Update a service.
 *
 * @param $thrash1 service id.
 * @param $thrash2 Don't use.
 * @param array              $other it's array, $other as param is <name>;<description>;<id_group>;<critical>;
 *              <warning>;<id_agent>;<sla_interval>;<sla_limit>;<id_warning_module_template_alert>;
 *              <id_critical_module_template_alert>;<id_critical_module_sla_template_alert>;<quiet>;
 *              <cascade_protection>;<evaluate_sla>;<is_favourite>;<unknown_as_critical>;<server_name>;
 *              in this order and separator char (after text ; ) and separator
 *              (pass in param othermode as othermode=url_encode_separator_<separator>)
 * @param $thrash3 Don't use
 *
 * example:
 * http://172.17.0.1/pandora_console/include/api.php?op=set&op2=update_service&return_type=json
 * &id=4&other=test2%7CDescripcion%7C%7C%7C0.6%7C&other_mode=url_encode_separator_%7C&apipass=1234&user=admin&pass=pandora
 */
function api_set_update_service($thrash1, $thrash2, $other, $thrash3)
{
    global $config;

    if (is_metaconsole()) {
        return;
    }

    $id_service = $thrash1;
    if (empty($id_service)) {
        returnError('The service could not be updated. No service ID provided.');
        return;
    }

    $service = db_get_row(
        'tservice',
        'id',
        $id_service
    );

    if (!check_acl($config['id_user'], $service['id_group'], 'AD')) {
        returnError('forbidden', 'string');
        return;
    }

    $name = $other['data'][0];
    if (empty($name)) {
        $name = $service['name'];
    }

    $description = $other['data'][1];
    if (empty($description)) {
        $description = $service['description'];
    }

    $id_group = $other['data'][2];
    if (empty($id_group)) {
        $id_group = $service['id_group'];
    } else {
        if (!check_acl($config['id_user'], $id_group, 'AD')) {
            returnError('forbidden', 'string');
            return;
        }
    }

    $critical = $other['data'][3];
    if (empty($critical)) {
        $critical = $service['critical'];
    }

    $warning = $other['data'][4];
    if (empty($warning)) {
        $warning = $service['warning'];
    }

    $mode = 0;

    $id_agent = $other['data'][5];
    if (empty($id_agent)) {
        $id_agent = $service['id_agent_module'];
    } else {
        if (!util_api_check_agent_and_print_error($id_agent, 'string', 'AR')) {
            return;
        }
    }

    $sla_interval = $other['data'][6];
    if (empty($sla_interval)) {
        $sla_interval = $service['sla_interval'];
    }

    $sla_limit = $other['data'][7];
    if (empty($sla_limit)) {
        $sla_limit = $service['sla_limit'];
    }

    $id_warning_module_template = $other['data'][8];
    if (empty($id_warning_module_template)) {
        $id_warning_module_template = $service['id_template_alert_warning'];
    }

    $id_critical_module_template = $other['data'][9];
    if (empty($id_critical_module_template)) {
        $id_critical_module_template = $service['id_template_alert_critical'];
    }

    $id_unknown_module_template = 0;

    $id_critical_module_sla = $other['data'][10];
    if (empty($id_critical_module_sla)) {
        $id_critical_module_sla = $service['id_template_alert_critical_sla'];
    }

    $quiet = $other['data'][11];
    if (empty($quiet)) {
        $quiet = $service['quiet'];
    }

    $cascade_protection = $other['data'][12];
    if (empty($cascade_protection)) {
        $cascade_protection = $service['cascade_protection'];
    }

    $evaluate_sla = $other['data'][13];
    if (empty($evaluate_sla)) {
        $evaluate_sla = $service['evaluate_sla'];
    }

    $is_favourite = $other['data'][14];
    if (empty($is_favourite)) {
        $is_favourite = $service['is_favourite'];
    }

    $unknown_as_critical = $other['data'][15];
    if (empty($unknown_as_critical)) {
        $unknown_as_critical = $service['unknown_as_critical'];
    }

    $server_name = $other['data'][16];
    if (empty($server_name)) {
        $server_name = $service['server_name'];
    }

    $result = enterprise_hook(
        'services_update_service',
        [
            $id_service,
            $name,
            $description,
            $id_group,
            $critical,
            $warning,
            SECONDS_5MINUTES,
            $mode,
            $id_agent,
            $sla_interval,
            $sla_limit,
            $id_warning_module_template,
            $id_critical_module_template,
            $id_unknown_module_template,
            $id_critical_module_sla,
            $quiet,
            $cascade_protection,
            $evaluate_sla,
            $is_favourite,
            $unknown_as_critical,
            $server_name,
        ]
    );

    if ($result) {
        returnData('string', ['type' => 'string', 'data' => $result]);
    } else {
        returnError('The service could not be updated.');
    }
}


/**
 * Add elements to service.
 *
 * @param $thrash1 service id.
 * @param $thrash2 Don't use.
 * @param array              $other it's a json, $other as param is <description>;<id_group>;<critical>;
 *              <warning>;<id_agent>;<sla_interval>;<sla_limit>;<id_warning_module_template_alert>;
 *              <id_critical_module_template_alert>;<id_critical_module_sla_template_alert>;
 *              in this order and separator char (after text ; ) and separator
 *              (pass in param othermode as othermode=url_encode_separator_<separator>)
 * @param $thrash3 Don't use
 *
 * example:
 * http://172.17.0.1/pandora_console/include/api.php?op=set&op2=add_element_service&return_type=json&id=1
 * &other=W3sidHlwZSI6ImFnZW50IiwiaWQiOjIsImRlc2NyaXB0aW9uIjoiamlqaWppIiwid2VpZ2h0X2NyaXRpY2FsIjowLCJ3ZWlnaHRfd2FybmluZyI6MCwid2VpZ2h0X3Vua25vd24iOjAsIndlaWdodF9vayI6MH0seyJ0eXBlIjoibW9kdWxlIiwiaWQiOjEsImRlc2NyaXB0aW9uIjoiSG9sYSBxdWUgdGFsIiwid2VpZ2h0X2NyaXRpY2FsIjowLCJ3ZWlnaHRfd2FybmluZyI6MCwid2VpZ2h0X3Vua25vd24iOjAsIndlaWdodF9vayI6MH0seyJ0eXBlIjoic2VydmljZSIsImlkIjozLCJkZXNjcmlwdGlvbiI6ImplamVqZWplIiwid2VpZ2h0X2NyaXRpY2FsIjowLCJ3ZWlnaHRfd2FybmluZyI6MCwid2VpZ2h0X3Vua25vd24iOjAsIndlaWdodF9vayI6MH1d
 * &other_mode=url_encode_separator_%7C&apipass=1234&user=admin&pass=pandora
 */
function api_set_add_element_service($thrash1, $thrash2, $other, $thrash3)
{
    global $config;

    if (is_metaconsole()) {
        return;
    }

    $id = $thrash1;

    if (empty($id)) {
        returnError('Could not add elements to service. No service ID provided.');
        return;
    }

    $service_group = db_get_value('id_group', 'tservice', 'id', $id);

    if (!check_acl($config['id_user'], $service_group, 'AD')) {
        returnError('forbidden', 'string');
        return;
    }

    $array_json = json_decode(base64_decode(io_safe_output($other['data'][0])), true);
    if (!empty($array_json)) {
        $results = false;
        foreach ($array_json as $key => $element) {
            if ($element['id'] == 0) {
                continue;
            }

            switch ($element['type']) {
                case 'agent':
                    $id_agente_modulo = 0;
                    $id_service_child = 0;
                    $agent_id = $element['id'];
                    if (!agents_check_access_agent($agent_id, 'AR')) {
                        continue 2;
                    }
                break;

                case 'module':
                    $agent_id = 0;
                    $id_service_child = 0;
                    $id_agente_modulo = $element['id'];
                    if (!agents_check_access_agent(modules_get_agentmodule_agent($id_agente_modulo), 'AR')) {
                        continue 2;
                    }
                break;

                case 'service':
                    $agent_id = 0;
                    $id_agente_modulo = 0;
                    $id_service_child = $element['id'];
                    $service_group = db_get_value(
                        'id_group',
                        'tservice',
                        'id',
                        $id_service_child
                    );
                    if ($service_group === false || !check_acl($config['id_user'], $service_group, 'AD')) {
                        continue 2;
                    }
                break;
            }

            $values = [
                'id_agente_modulo' => $id_agente_modulo,
                'description'      => $element['description'],
                'id_service'       => $id,
                'weight_critical'  => $element['weight_critical'],
                'weight_warning'   => $element['weight_warning'],
                'weight_unknown'   => $element['weight_unknown'],
                'weight_ok'        => $element['weight_ok'],
                'id_agent'         => $agent_id,
                'id_service_child' => $id_service_child,
                'id_server_meta'   => 0,
            ];

            $result = db_process_sql_insert('tservice_element', $values);
            if ($result && !$results) {
                $results = $result;
            }
        }
    }

    if ($results) {
        returnData('string', ['type' => 'string', 'data' => 1]);
    } else {
        returnError('Could not add elements to service');
    }

}


/**
 * Example: http://127.0.0.1/pandora_console/include/api.php?op=set&op2=recreate_service_modules&id=1&user=admin&apipass=1234&pass=pandora
 * Recreates modules for given service id, if needed.
 *
 * @param  integer      $id       Id service.
 * @param  integer|null $id_agent Agent id to allocate service if node env.
 * @return void
 */
function api_set_recreate_service_modules($id, $id_agent)
{
    global $config;
    if (!check_acl($config['id_user'], $service['id_group'], 'AW')) {
        returnError('forbidden', 'string');
        return;
    }

    $id_agent = (int) $id_agent;
    if (empty($id_agent) === true) {
        $id_agent = null;
    }

    if (is_metaconsole() === true) {
        // Force agent recreation in MC if modules are missing.
        $id_agent = null;
    }

    $result = false;
    try {
        $service = new PandoraFMS\Enterprise\Service($id);
        if ($service->migrateModules() !== true) {
            $service->addModules($id_agent);
        }

        $result = true;
    } catch (Exception $e) {
        $result = false;
        $error = $e->getMessage();
    }

    if ($result === true) {
        returnData('string', ['type' => 'string', 'data' => 'OK']);
    } else {
        returnError('The service modules could not be recreated: '.$error);
    }
}


/**
 * Update a special day. And return a message with the result of the operation.
 *
 * @param string            $id    Id of the special day to update.
 * @param $thrash2 Don't use.
 * @param array             $other it's array, $other as param is <special_day>;<day_code>;<description>;<id_group>; in this order
 *              and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 * @param $thrash3 Don't use
 *
 * example:
 *  api.php?op=set&op2=update_special_day&id=1&other=2014-05-03|sunday|text|0&other_mode=url_encode_separator_|
 */
function api_set_update_special_day($id_special_day, $thrash2, $other, $thrash3)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    $special_day = $other['data'][0];
    $day_code = $other['data'][1];
    $description = $other['data'][2];
    $idGroup = $other['data'][3];
    $id_calendar = $other['data'][4];

    if (!check_acl($config['id_user'], $idGroup, 'LM', true)) {
        returnError('forbidden', 'string');
        return;
    }

    if ($id_special_day == '') {
        returnError('The Special Day could not be updated. ID cannot be left blank.');
        return;
    }

    $check_id_special_day = db_get_value('id', 'talert_special_days', 'id', $id_special_day);

    if (!$check_id_special_day) {
        returnError('The Special Day could not be updated. ID does not exist.');
        return;
    }

    $id_group_org = db_get_value('id_group', 'talert_special_days', 'id', $id_special_day);

    if (!check_acl($config['id_user'], $id_group_org, 'LM', true)) {
        returnError('forbidden', 'string');
        return;
    }

    if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $special_day)) {
        returnError('The Special Day could not be updated. Invalid date format.');
        return;
    }

    try {
        $sd = new SpecialDay();
        $sd->date($special_day);
        $sd->id_group($idGroup);
        $sd->day_code($day_code);
        $sd->description($description);
        $sd->id_calendar($id_calendar);
        $sd->save();
        if ($sd->save() === true) {
            returnError('Special Day could not be updated');
        } else {
            returnData('string', ['type' => 'string', 'data' => $sd->id()]);
        }
    } catch (Exception $e) {
        returnData('string', ['type' => 'string', 'data' => $e]);
    }
}


/**
 * Delete a special day. And return a message with the result of the operation.
 *
 * @param string            $id Id of the special day to delete.
 * @param $thrash2 Don't use.
 * @param $thrash3 Don't use.
 * @param $thrash4 Don't use.
 *
 * example:
 *  api.php?op=set&op2=delete_special_day&id=1
 */
function api_set_delete_special_day($id_special_day, $thrash2, $thrash3, $thrash4)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'LM')) {
        returnError('forbidden', 'string');
        return;
    }

    if ($id_special_day == '') {
        returnError('The Special Day could not be deleted. ID cannot be left blank.');
        return;
    }

    $check_id_special_day = db_get_value('id', 'talert_special_days', 'id', $id_special_day);

    if (!$check_id_special_day) {
        returnError('The Special Day could not be deleted. ID does not exist.');
        return;
    }

    $id_group = db_get_value('id_group', 'talert_special_days', 'id', $id_special_day);
    if (!check_acl($config['id_user'], $id_group, 'LM', true)) {
        returnError('forbidden', 'string');
        return;
    }

    try {
        $specialDay = new SpecialDay($id_special_day);
    } catch (\Exception $e) {
        if ($id > 0) {
            returnError('The Special Day could not be deleted.');
        }

        return;
    }

    // Remove.
    $specialDay->delete();
    $return = 'success';
    returnData('string', ['type' => 'string', 'data' => $return]);
}


/**
 * Get a module graph image encoded with base64.
 * The value returned by this function will be always a string.
 *
 * @param integer           $id    Id of the module.
 * @param $thrash2 Don't use.
 * @param array             $other Array array('type' => 'string', 'data' => '<Graph seconds>').
 * @param $thrash4 Don't use.
 *
 * example:
 *  http://localhost/pandora_console/include/
 *        api.php?op=get&op2=module_graph&id=5&other=40000%7C1&other_mode=url_encode_separator_%7C&apipass=1234
 *        &api=1&user=admin&pass=pandora
 */
function api_get_module_graph($id_module, $thrash2, $other, $thrash4)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (is_nan($id_module) || $id_module <= 0) {
        returnError('Parameter error.');
        return;
    }

    if (!util_api_check_agent_and_print_error(modules_get_agentmodule_agent($id_module), 'string')) {
        return;
    }

    $user_defined = false;
    if (is_array($other['data']) === true) {
        $user_defined = true;
        // Parameters received by user call.
        $graph_seconds = (!empty($other) && isset($other['data'][0])) ? $other['data'][0] : SECONDS_1HOUR;

        // Base64.
        $base64 = $other['data'][1];

        // 1 hour by default.
        $graph_threshold = (!empty($other) && isset($other['data'][2]) && $other['data'][2]) ? $other['data'][2] : 0;
        // TODO. For complete
        $width = '';
        // Graph height when send email by alert
        $height = (!empty($other) && isset($other['data'][3]) && $other['data'][3]) ? $other['data'][3] : 225;

        // Graph width (optional).
        $width = (!empty($other) && isset($other['data'][4]) && $other['data'][4]) ? $other['data'][4] : 600;

        // If recive value its from mail call.
        $graph_font_size = $other['data'][5];
    } else {
        // Fixed parameters for _modulegraph_nh_.
        $graph_seconds = $other['data'];
        $graph_threshold = 0;
        $base64 = 0;
        $height = 225;
        $width = '90%';
    }

    if (is_nan($graph_seconds) || $graph_seconds <= 0) {
        // returnError('error_module_graph', __(''));
        return;
    }

    $params = [
        'agent_module_id'    => $id_module,
        'period'             => $graph_seconds,
        'show_events'        => false,
        'width'              => $width,
        'height'             => $height,
        'show_alerts'        => false,
        'date'               => time(),
        'unit'               => '',
        'baseline'           => 0,
        'return_data'        => 0,
        'show_title'         => true,
        'only_image'         => true,
        'homeurl'            => ui_get_full_url(false).'/',
        'compare'            => false,
        'show_unknown'       => true,
        'backgroundColor'    => 'white',
        'percentil'          => null,
        'type_graph'         => $config['type_module_charts'],
        'fullscale'          => false,
        'return_img_base_64' => true,
        'image_threshold'    => $graph_threshold,
        'graph_font_size'    => $graph_font_size,
    ];

    // Format MIME RFC 2045 (line break 76 chars).
    $graph_html = chunk_split(grafico_modulo_sparse($params));

    if ((bool) $base64 === false) {
        header('Content-type: text/html');
        returnData('string', ['type' => 'string', 'data' => '<img src="data:image/jpeg;base64,'.$graph_html.'">']);
    } else {
        returnData('string', ['type' => 'string', 'data' => $graph_html]);
    }
}


function api_set_metaconsole_synch($keys)
{
    global $config;

    if (defined('METACONSOLE')) {
        if (!check_acl($config['id_user'], 0, 'PM')) {
            returnError('forbidden', 'string');
            return;
        }

        $data['keys'] = ['customer_key' => $keys];
        foreach ($data['keys'] as $key => $value) {
            db_process_sql_update(
                'tupdate_settings',
                [db_escape_key_identifier('value') => $value],
                [db_escape_key_identifier('key') => $key]
            );
        }

        // Validate update the license in nodes:
        enterprise_include_once('include/functions_metaconsole.php');
        $array_metaconsole_update = metaconsole_update_all_nodes_license();
        if ($array_metaconsole_update[0] === 0) {
            ui_print_success_message(__('Metaconsole and the licenses of all nodes were updated.'));
        } else {
            ui_print_error_message(__('Metaconsole license updated but %d of %d node failed to sync.', $array_metaconsole_update[0], $array_metaconsole_update[1]));
        }
    } else {
        echo __('This function is for metaconsole only.');
    }
}


function api_set_metaconsole_license_file($key)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (empty($key) === true) {
        returnError('Key cannot be empty.');
        return;
    }

    $license_encryption_key = db_get_value('value', 'tupdate_settings', '`key`', 'license_encryption_key');
    if (empty($license_encryption_key) === false) {
        $key = openssl_blowfish_encrypt_hex($key, io_safe_output($license_encryption_key));
    }

    // Update the license file.
    $result = file_put_contents($config['remote_config'].'/'.LICENSE_FILE, $key);
    if ($result === false) {
        returnError('update-license', 'Failed to Update license file.');
    } else {
        returnData('string', ['type' => 'string', 'data' => true]);
    }

    return;
}


function api_set_new_cluster($thrash1, $thrash2, $other, $thrash3)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    $name = $other['data'][0];
    $cluster_type = $other['data'][1];
    $description = $other['data'][2];
    $idGroup = $other['data'][3];

    if (!check_acl($config['id_user'], $idGroup, 'AW')) {
        returnError('forbidden', 'string');
        return;
    }

    $name_exist = db_process_sql('select count(name) as already_exist from tcluster as already_exist where name = "'.$name.'"');

    if ($name_exist[0]['already_exist'] > 0) {
        returnError('A cluster with this name already exist.');
        return false;
    }

    $server_name = db_process_sql('select name from tserver where server_type=5 limit 1');

    $server_name_agent = $server_name[0]['name'];

    $values_agent = [
        'nombre'      => $name,
        'alias'       => $name,
        'comentarios' => $description,
        'id_grupo'    => $idGroup,
        'id_os'       => 100,
        'server_name' => $server_name_agent,
        'modo'        => 1,
    ];

    if (!isset($name)) {
        // avoid warnings.
        $name = '';
    }

    if (trim($name) != '') {
        $id_agent = agents_create_agent($values_agent['nombre'], $values_agent['id_grupo'], 300, '', $values_agent);

        if ($id_agent !== false) {
            // Create cluster.
            $values_cluster = [
                'name'         => $name,
                'cluster_type' => $cluster_type,
                'description'  => $description,
                'group'        => $idGroup,
                'id_agent'     => $id_agent,
            ];

            $id_cluster = db_process_sql_insert('tcluster', $values_cluster);

            if ($id_cluster === false) {
                // failed to create cluster, rollback previously created agent.
                agents_delete_agent($id_agent, true);
            }

            $values_module = [
                'nombre'            => io_safe_input('Cluster status'),
                'id_modulo'         => 5,
                'prediction_module' => 5,
                'id_agente'         => $id_agent,
                'custom_integer_1'  => $id_cluster,
                'id_tipo_modulo'    => 1,
                'descripcion'       => io_safe_input('Cluster status information module'),
                'min_warning'       => 1,
                'min_critical'      => 2,
            ];

            $id_module = modules_create_agent_module($id_agent, $values_module['nombre'], $values_module, true);
            if ($id_module === false) {
                db_pandora_audit(
                    AUDIT_LOG_REPORT_MANAGEMENT,
                    "Failed to create cluster status module in cluster $name (#$id_agent)"
                );
            }
        }

        $auditMessageCluster = ((bool) $id_cluster === true)
                                                            ? sprintf('Created new cluster %s (#%s)', $name, $id_cluster)
                                                            : sprintf('Failed to create cluster %s ', $name);

        db_pandora_audit(
            AUDIT_LOG_REPORT_MANAGEMENT,
            $auditMessageCluster
        );

        $auditMessageAgent = ((bool) $id_agent === true)
                                                        ? sprintf('Created new cluster agent %s (#%s)', $name, $id_agent)
                                                        : sprintf('Failed to create cluster agent %s ', $name);

        db_pandora_audit(
            AUDIT_LOG_REPORT_MANAGEMENT,
            $auditMessageAgent
        );

        if ($id_cluster !== false) {
            returnData(
                'string',
                [
                    'type' => 'string',
                    'data' => (int) $id_cluster,
                ]
            );
        } else {
            returnError('A cluster could not be created.');
        }
    } else {
        returnError('Agent name cannot be empty.');
        return;
    }

    return;
}


function api_set_add_cluster_agent($thrash1, $thrash2, $other, $thrash3)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    $array_json = json_decode(base64_decode(io_safe_output($other['data'][0])), true);
    if (!empty($array_json)) {
        foreach ($array_json as $key => $element) {
            $clusters = Cluster::search(['id' => $element['id']]);
            $cluster = $clusters[0];

            if ((!check_acl($config['id_user'], $cluster['group'], 'AW'))
                || (!agents_check_access_agent($element['id_agent'], 'AW'))
            ) {
                continue;
            }

            $tcluster_agent = db_process_sql('insert into tcluster_agent values ('.$element['id'].','.$element['id_agent'].')');
        }
    }

    if ($tcluster_agent !== false) {
        returnData('string', ['type' => 'string', 'data' => 1]);
    } else {
        returnError('The elements could not be added to cluster');
    }

}


function api_set_add_cluster_item($thrash1, $thrash2, $other, $thrash3)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    $array_json = json_decode(base64_decode(io_safe_output($other['data'][0])), true);
    if (is_array($array_json)) {
        foreach ($array_json as $key => $element) {
            $clusters = Cluster::search(['id' => $element['id_cluster']]);
            $cluster = $clusters[0];

            if (!check_acl($config['id_user'], $cluster['group'], 'AW')) {
                continue;
            }

            if ($element['type'] == 'AA') {
                $id_agent = db_get_value_sql('SELECT id_agent FROM tcluster WHERE id = '.$element['id_cluster']);

                $module_exists_sql = sprintf(
                    'SELECT id_agente_modulo FROM tagente_modulo tam INNER JOIN tcluster_agent tca WHERE tam.id_agente=tca.id_agent AND tca.id_cluster=%d AND tam.nombre="%s"',
                    $element['id_cluster'],
                    io_safe_input($element['name'])
                );

                $module_exists = db_process_sql($module_exists_sql);

                if ($module_exists === false) {
                    continue;
                }

                $tcluster_module = db_process_sql_insert('tcluster_item', ['name' => io_safe_input($element['name']), 'id_cluster' => $element['id_cluster'], 'critical_limit' => $element['critical_limit'], 'warning_limit' => $element['warning_limit']]);

                $id_parent_modulo = db_process_sql(
                    'select id_agente_modulo from tagente_modulo where id_agente = '.$id_agent.' and nombre = "'.io_safe_input('Cluster status').'"'
                );

                $get_module_type = db_process_sql('select id_tipo_modulo,descripcion,min_warning,min_critical,module_interval from tagente_modulo where nombre = "'.io_safe_input($element['name']).'" limit 1');

                $get_module_type_value = $get_module_type[0]['id_tipo_modulo'];
                $get_module_description_value = $get_module_type[0]['descripcion'];
                $get_module_warning_value = $get_module_type[0]['min_warning'];
                $get_module_critical_value = $get_module_type[0]['min_critical'];
                $get_module_interval_value = $get_module_type[0]['module_interval'];

                $values_module = [
                    'nombre'            => io_safe_input($element['name']),
                    'id_modulo'         => 0,
                    'prediction_module' => 6,
                    'id_agente'         => $id_agent,
                    'parent_module_id'  => $id_parent_modulo[0]['id_agente_modulo'],
                    'custom_integer_1'  => $element['id_cluster'],
                    'custom_integer_2'  => $tcluster_module,
                    'id_tipo_modulo'    => 1,
                    'descripcion'       => $get_module_description_value,
                    'min_warning'       => $element['warning_limit'],
                    'min_critical'      => $element['critical_limit'],
                    'tcp_port'          => 1,
                    'module_interval'   => $get_module_interval_value,
                ];

                $id_module = modules_create_agent_module($values_module['id_agente'], $values_module['nombre'], $values_module, true);

                $launch_cluster = db_process_sql(
                    'update tagente_modulo set flag = 1 where custom_integer_1 = '.$element['id_cluster'].' and nombre = "'.io_safe_input('Cluster status').'"'
                );

                if ($tcluster_module !== false) {
                    db_pandora_audit(
                        AUDIT_LOG_REPORT_MANAGEMENT,
                        'Module #'.$element['name'].' assigned to cluster #'.$element['id_cluster']
                    );
                } else {
                    db_pandora_audit(
                        AUDIT_LOG_REPORT_MANAGEMENT,
                        'Failed to assign AA item module to cluster '.$element['name']
                    );
                }
            } else if ($element['type'] == 'AP') {
                $id_agent = db_get_value_sql('SELECT id_agent FROM tcluster WHERE id = '.$element['id_cluster']);

                $module_exists_sql = sprintf(
                    'SELECT id_agente_modulo FROM tagente_modulo tam INNER JOIN tcluster_agent tca WHERE tam.id_agente=tca.id_agent AND tca.id_cluster=%d AND tam.nombre="%s"',
                    $element['id_cluster'],
                    io_safe_input($element['name'])
                );

                $module_exists = db_process_sql($module_exists_sql);

                if ($module_exists === false) {
                    continue;
                }

                $id_parent_modulo = db_process_sql(
                    'select id_agente_modulo from tagente_modulo where id_agente = '.$id_agent.' and nombre = "'.io_safe_input('Cluster status').'"'
                );

                $tcluster_balanced_module = db_process_sql_insert('tcluster_item', ['name' => io_safe_input($element['name']), 'id_cluster' => $element['id_cluster'], 'item_type' => 'AP', 'is_critical' => $element['is_critical']]);

                $get_module_type = db_process_sql('select id_tipo_modulo,descripcion,min_warning,min_critical,module_interval,ip_target,id_module_group from tagente_modulo where nombre = "'.io_safe_input($element['name']).'" limit 1');

                $get_module_type_value = $get_module_type[0]['id_tipo_modulo'];
                $get_module_description_value = $get_module_type[0]['descripcion'];
                $get_module_warning_value = $get_module_type[0]['min_warning'];
                $get_module_critical_value = $get_module_type[0]['min_critical'];
                $get_module_interval_value = $get_module_type[0]['module_interval'];
                $get_module_type_nombre = db_process_sql('select nombre from ttipo_modulo where id_tipo = '.$get_module_type_value);
                $get_module_type_nombre_value = $get_module_type_nombre[0]['nombre'];
                $get_module_ip_target = $get_module_type[0]['ip_target'];
                $get_module_id_module_group = $get_module_type[0]['id_module_group'];
                $get_module_id_flag = $get_module_type[0]['flag'];
                $get_module_dynamic_two_tailed = $get_module_type[0]['dynamic_two_tailed'];

                $values_module = [
                    'nombre'            => $element['name'],
                    'id_modulo'         => 0,
                    'prediction_module' => 7,
                    'id_agente'         => $id_agent,
                    'parent_module_id'  => $id_parent_modulo[0]['id_agente_modulo'],
                    'custom_integer_1'  => $element['id_cluster'],
                    'custom_integer_2'  => $tcluster_balanced_module,
                    'id_tipo_modulo'    => 1,
                    'descripcion'       => $get_module_description_value,
                    'min_warning'       => $get_module_warning_value,
                    'min_critical'      => $get_module_critical_value,
                    'tcp_port'          => $element['is_critical'],
                    'module_interval'   => $get_module_interval_value,
                    'ip_target'         => $get_module_ip_target,
                    'tcp_port'          => 1,
                    'id_module_group'   => $get_module_id_module_group,
                ];

                $id_module = modules_create_agent_module($values_module['id_agente'], $values_module['nombre'], $values_module, true);

                $launch_cluster = db_process_sql(
                    'update tagente_modulo set flag = 1 where custom_integer_1 = '.$element['id_cluster'].' and nombre = "'.io_safe_input('Cluster status').'"'
                );

                if ($tcluster_balanced_module !== false) {
                    db_pandora_audit(
                        AUDIT_LOG_REPORT_MANAGEMENT,
                        'Module #'.$element['name'].' assigned to cluster #'.$element['id_cluster']
                    );
                } else {
                    db_pandora_audit(
                        AUDIT_LOG_REPORT_MANAGEMENT,
                        'The module could not be assigned to the cluster'
                    );
                }
            }
        }
    } else {
        $id_module = false;
    }

    if ($id_module !== false) {
        returnData('string', ['type' => 'string', 'data' => 1]);
    } else {
        returnError('The elements could not be added to cluster');
    }

}


function api_set_delete_cluster($id, $thrash1, $thrast2, $thrash3)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    $clusters = Cluster::search(['id' => $id]);
    $cluster = $clusters[0];

    if (!check_acl($config['id_user'], $cluster['group'], 'AD')) {
        returnError('The user cannot access to the cluster');
        return;
    }

    $temp_id_cluster = db_process_sql('select id_agent from tcluster where id ='.$id);

    $tcluster_modules_delete_get = db_process_sql('select id_agente_modulo from tagente_modulo where custom_integer_1 = '.$id);

    foreach ($tcluster_modules_delete_get as $key => $value) {
        $tcluster_modules_delete_get_values[] = $value['id_agente_modulo'];
    }

    $tcluster_modules_delete = modules_delete_agent_module($tcluster_modules_delete_get_values);
    $tcluster_items_delete = db_process_sql('delete from tcluster_item where id_cluster = '.$id);
    $tcluster_agents_delete = db_process_sql('delete from tcluster_agent where id_cluster = '.$id);
    $tcluster_delete = db_process_sql('delete from tcluster where id = '.$id);
    $tcluster_agent_delete = $temp_id_cluster[0]['id_agent'] !== null ? agents_delete_agent($temp_id_cluster[0]['id_agent']) : 0;

    if (($tcluster_modules_delete + $tcluster_items_delete + $tcluster_agents_delete + $tcluster_delete + $tcluster_agent_delete) == 0) {
        returnError('Cluster could not be deleted.');
    } else {
        returnData('string', ['type' => 'string', 'data' => __('Successfully deleted.')]);
    }
}


function api_set_delete_cluster_agents($thrash1, $thrast2, $other, $thrash3)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    $id_agent = $other['data'][0];
    $id_cluster = $other['data'][1];

    $target_agents = json_decode(base64_decode(io_safe_output($other['data'][0])), true);

    $deleted_counter = 0;

    foreach ($target_agents as $ta) {
        $clusters = Cluster::search(['id' => $ta['id']]);
        $cluster = $clusters[0];

        // $cluster_group = clusters_get_group($id_agent);
        if (!users_is_admin($config['id_user'])) {
            if (!$cluster['group']
                || (!check_acl($config['id_user'], $cluster['group'], 'AW'))
                || (!agents_check_access_agent($ta['id_agent'], 'AW'))
            ) {
                returnError('error_set_delete_cluster_agent', __('The user cannot access the cluster.'));
                return;
            }
        }

        $cluster = new Cluster($ta['id']);
        $deleted = $cluster->removeMember($ta['id_agent']);

        if ($deleted === true) {
            $deleted_counter++;
        }
    }

    returnData('string', ['type' => 'string', 'data' => $deleted_counter]);

}


function api_set_delete_cluster_item($id, $thrash1, $thrast2, $thrast3)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    $clusters = Cluster::search(['id' => $id]);
    $cluster = $clusters[0];

    if (!check_acl($config['id_user'], $cluster['group'], 'AD')) {
        returnError('The user cannot access the cluster');
        return;
    }

    $delete_module_aa_get = db_process_sql('select id_agente_modulo from tagente_modulo where custom_integer_2 = '.$id);
    $delete_module_aa_get_result = modules_delete_agent_module($delete_module_aa_get[0]['id_agente_modulo']);
    $delete_item = db_process_sql('delete from tcluster_item where id = '.$id);

    if (!$delete_item) {
        returnError('Error in delete operation.');
    } else {
        returnData('string', ['type' => 'string', 'data' => __('Successfully deleted.')]);
    }

}


function api_set_apply_module_template($id_template, $id_agent, $thrash3, $thrash4)
{
    global $config;

    if (isset($id_template)) {
        if (!util_api_check_agent_and_print_error($id_agent, 'string', 'AW')) {
            return;
        }

        // Take agent data.
        $row = db_get_row('tagente', 'id_agente', $id_agent);

        $intervalo = $row['intervalo'];
        $nombre_agente = $row['nombre'];
        $direccion_agente = $row['direccion'];
        $ultima_act = $row['ultimo_contacto'];
        $ultima_act_remota = $row['ultimo_contacto_remoto'];
        $comentarios = $row['comentarios'];
        $id_grupo = $row['id_grupo'];
        $id_os = $row['id_os'];
        $os_version = $row['os_version'];
        $agent_version = $row['agent_version'];
        $disabled = $row['disabled'];

        $id_np = $id_template;
        $name_template = db_get_value('name', 'tnetwork_profile', 'id_np', $id_np);
        $npc = db_get_all_rows_field_filter('tnetwork_profile_component', 'id_np', $id_np);

        if ($npc === false) {
            $npc = [];
        }

        $success_count = $error_count = 0;
        $modules_already_added = [];

        foreach ($npc as $row) {
            $nc = db_get_all_rows_field_filter('tnetwork_component', 'id_nc', $row['id_nc']);

            if ($nc === false) {
                $nc = [];
            }

            foreach ($nc as $row2) {
                // Insert each module from tnetwork_component into agent.
                $values = [
                    'id_agente'             => $id_agent,
                    'id_tipo_modulo'        => $row2['type'],
                    'descripcion'           => __('Created by template ').$name_template.' . '.$row2['description'],
                    'max'                   => $row2['max'],
                    'min'                   => $row2['min'],
                    'module_interval'       => $row2['module_interval'],
                    'tcp_port'              => $row2['tcp_port'],
                    'tcp_send'              => $row2['tcp_send'],
                    'tcp_rcv'               => $row2['tcp_rcv'],
                    'snmp_community'        => $row2['snmp_community'],
                    'snmp_oid'              => $row2['snmp_oid'],
                    'ip_target'             => $direccion_agente,
                    'id_module_group'       => $row2['id_module_group'],
                    'id_modulo'             => $row2['id_modulo'],
                    'plugin_user'           => $row2['plugin_user'],
                    'plugin_pass'           => $row2['plugin_pass'],
                    'plugin_parameter'      => $row2['plugin_parameter'],
                    'unit'                  => $row2['unit'],
                    'max_timeout'           => $row2['max_timeout'],
                    'max_retries'           => $row2['max_retries'],
                    'id_plugin'             => $row2['id_plugin'],
                    'post_process'          => $row2['post_process'],
                    'dynamic_interval'      => $row2['dynamic_interval'],
                    'dynamic_max'           => $row2['dynamic_max'],
                    'dynamic_min'           => $row2['dynamic_min'],
                    'dynamic_two_tailed'    => $row2['dynamic_two_tailed'],
                    'min_warning'           => $row2['min_warning'],
                    'max_warning'           => $row2['max_warning'],
                    'str_warning'           => $row2['str_warning'],
                    'min_critical'          => $row2['min_critical'],
                    'max_critical'          => $row2['max_critical'],
                    'str_critical'          => $row2['str_critical'],
                    'critical_inverse'      => $row2['critical_inverse'],
                    'warning_inverse'       => $row2['warning_inverse'],
                    'critical_instructions' => $row2['critical_instructions'],
                    'warning_instructions'  => $row2['warning_instructions'],
                    'unknown_instructions'  => $row2['unknown_instructions'],
                    'id_category'           => $row2['id_category'],
                    'macros'                => $row2['macros'],
                    'each_ff'               => $row2['each_ff'],
                    'min_ff_event'          => $row2['min_ff_event'],
                    'min_ff_event_normal'   => $row2['min_ff_event_normal'],
                    'min_ff_event_warning'  => $row2['min_ff_event_warning'],
                    'min_ff_event_critical' => $row2['min_ff_event_critical'],
                    'ff_type'               => $row2['ff_type'],
                ];

                $name = $row2['name'];

                // Put tags in array if the component has to add them later.
                if (!empty($row2['tags'])) {
                    $tags = explode(',', $row2['tags']);
                } else {
                    $tags = [];
                }

                // Check if this module exists in the agent.
                $module_name_check = db_get_value_filter('id_agente_modulo', 'tagente_modulo', ['delete_pending' => 0, 'nombre' => $name, 'id_agente' => $id_agent]);

                if ($module_name_check !== false) {
                    $modules_already_added[] = $row2['name'];
                    $error_count++;
                } else {
                    $id_agente_modulo = modules_create_agent_module($id_agent, $name, $values);

                    if ($id_agente_modulo === false) {
                        $error_count++;
                    } else {
                        if (!empty($tags)) {
                            // Creating tags
                            $tag_ids = [];
                            foreach ($tags as $tag_name) {
                                $tag_id = tags_get_id($tag_name);

                                // If tag exists in the system we store to create it
                                $tag_ids[] = $tag_id;
                            }

                            tags_insert_module_tag($id_agente_modulo, $tag_ids);
                        }

                        $success_count++;
                    }
                }
            }
        }

        if ($error_count > 0) {
            if (empty($modules_already_added)) {
                returnError(sprintf('Could not add modules (%s)', $error_count));
            } else {
                returnError('Could not add modules. The following errors already exists: ').implode(', ', $modules_already_added);
            }
        }

        if ($success_count > 0) {
            returnData('string', ['type' => 'string', 'data' => __('Modules successfully added')]);
        }
    }

}


function api_get_cluster_status($id_cluster, $trash1, $trash2, $returnType)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    $clusters = Cluster::search(['id' => $id_cluster]);
    $cluster = $clusters[0];

    if (!check_acl($config['id_user'], $cluster['group'], 'AR')) {
        returnError('The user cannot access to the cluster');
        return;
    }

    $sql = 'select ae.estado from tagente_estado ae, tagente_modulo tam, tcluster tc'.' where tam.id_agente=tc.id_agent and ae.id_agente_modulo=tam.id_agente_modulo '.' and tc.id='.$id_cluster.' and tam.nombre = "'.io_safe_input('Cluster status').'" ';

    $value = db_get_value_sql($sql);

    if ($value === false) {
        returnError('id_not_found', $returnType);
        return;
    }

    $data = [
        'type' => 'string',
        'data' => $value,
    ];

    returnData($returnType, $data);
}


function api_get_cluster_id_by_name($cluster_name, $trash1, $trash2, $returnType)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    $clusters = Cluster::search(['name' => $cluster_name]);

    if ($clusters === false) {
        returnError('id_not_found', $returnType);
        return;
    }

    $cluster = $clusters[0];

    if (!check_acl($config['id_user'], $cluster['group'], 'AR')) {
        returnError('The user cannot access to the cluster');
        return;
    }

    $data = [
        'type' => 'string',
        'data' => $cluster['id'],
    ];

    returnData($returnType, $data);
}


function api_get_agents_id_name_by_cluster_id($cluster_id, $trash1, $trash2, $returnType)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    $clusters = Cluster::search(['id' => $cluster_id]);

    if ($clusters === false) {
        returnError('id_not_found', $returnType);
    }

    $cluster = $clusters[0];

    if (!check_acl($config['id_user'], $cluster['group'], 'AR')) {
        returnError('The user cannot access to the cluster');
        return;
    }

    $cluster = new Cluster($cluster_id);
    $cluster_agents = $cluster->getMembers();

    $all_agents = [];

    foreach ($cluster_agents as $ca) {
        $all_agents[$ca->id_agente()] = $ca->name();
    }

    if ($all_agents !== false) {
        $data = [
            'type' => 'json',
            'data' => $all_agents,
        ];

        returnData('json', $data, JSON_FORCE_OBJECT);
    } else {
        returnError('No agents retrieved.');
    }
}


function api_get_agents_id_name_by_cluster_name($cluster_name, $trash1, $trash2, $returnType)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    $clusters = Cluster::search(['name' => $cluster_name]);

    $cluster = $clusters[0];

    if (!check_acl($config['id_user'], $cluster['group'], 'AR')) {
        returnError('The user cannot access to the cluster');
        return;
    }

    $cluster = new Cluster($cluster['id']);
    $cluster_agents = $cluster->getMembers();

    $all_agents = [];

    foreach ($cluster_agents as $ca) {
        $all_agents[$ca->id_agente()] = $ca->name();
    }

    if (count($all_agents) > 0 and $all_agents !== false) {
        $data = [
            'type' => 'json',
            'data' => $all_agents,
        ];

        returnData('json', $data, JSON_FORCE_OBJECT);
    } else {
        returnError('No agents retrieved.');
    }
}


/**
 * Get agents alias, id and server id (if Metaconsole) given agent alias
 * matching part of it.
 *
 * @param string $alias
 * @param $trash1
 * @param $trash2
 * @param string $returnType
 *  Example:
 *    api.php?op=get&op2=agents_id_name_by_alias&return_type=json&apipass=1234&user=admin&pass=pandora&id=pandorafms&id2=strict
 */
function api_get_agents_id_name_by_alias($alias, $strict, $trash2, $returnType)
{
    global $config;

    if ($strict == 'strict') {
        $where_clause = " alias = '$alias'";
    } else {
        $where_clause = " upper(alias) LIKE upper('%$alias%')";
    }

    if (is_metaconsole()) {
        $all_agents = db_get_all_rows_sql("SELECT alias, nombre, id_agente, id_tagente,id_tmetaconsole_setup as 'id_server', server_name FROM tmetaconsole_agent WHERE $where_clause");
    } else {
        $all_agents = db_get_all_rows_sql("SELECT alias, nombre, id_agente from tagente WHERE $where_clause");
    }

    if ($all_agents !== false) {
        $data = [
            'type' => 'json',
            'data' => $all_agents,
        ];

        returnData('json', $data, JSON_FORCE_OBJECT);
    } else {
        returnError('Alias does not match any agent.');
    }
}


function api_get_modules_id_name_by_cluster_id($cluster_id)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    $clusters = Cluster::search(['id' => $cluster_id]);

    if ($clusters === false) {
        returnError('ID does not exist in database.');
    }

    $cluster = $clusters[0];

    if (!check_acl($config['id_user'], $cluster['group'], 'AR')) {
        returnError('The user cannot access to the cluster');
        return;
    }

    $cluster_type = $cluster['cluster_type'] == 'AA' ? MODULE_PREDICTION_CLUSTER_AA : MODULE_PREDICTION_CLUSTER_AP;

    $cluster = new Cluster($cluster_id);
    $cluster_modules = $cluster->getItems($cluster_type);

    $all_modules = [];

    foreach ($cluster_modules as $cm) {
        $module_obj = $cm->getModule();
        $module_values = $module_obj->toArray();

        $id_agent_module = $module_values['id_agente_modulo'];
        $module_name = $module_values['nombre'];

        $all_modules[$id_agent_module] = $module_name;
    }

    if (count($all_modules) > 0 and $all_modules !== false) {
        $data = [
            'type' => 'json',
            'data' => $all_modules,
        ];

        returnData('json', $data);
    } else {
        returnError('No modules were retrieved.');
    }

}


function api_get_modules_id_name_by_cluster_name($cluster_name)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    $clusters = Cluster::search(['name' => $cluster_name]);
    $cluster = $clusters[0];

    if (!check_acl($config['id_user'], $cluster['group'], 'AR')) {
        returnError('The user cannot access to the cluster');
        return;
    }

    $cluster_type = $cluster['cluster_type'] == 'AA' ? MODULE_PREDICTION_CLUSTER_AA : MODULE_PREDICTION_CLUSTER_AP;

    $cluster = new Cluster($cluster['id']);
    $cluster_modules = $cluster->getItems($cluster_type);

    $all_modules = [];

    foreach ($cluster_modules as $cm) {
        $module_obj = $cm->getModule();
        $module_values = $module_obj->toArray();

        $id_agent_module = $module_values['id_agente_modulo'];
        $module_name = $module_values['nombre'];

        $all_modules[$id_agent_module] = $module_name;
    }

    if (count($all_modules) > 0 and $all_modules !== false) {
        $data = [
            'type' => 'json',
            'data' => $all_modules,
        ];

        returnData('json', $data);
    } else {
        returnError('No modules were retrieved.');
    }

}


 /**
  * @param $trash1
  * @param $trash2
  * @param mixed      $trash3
  * @param $returnType
  *    Example:
  *    api.php?op=get&op2=event_responses&return_type=csv&apipass=1234&user=admin&pass=pandora
  */
function api_get_event_responses($trash1, $trash2, $trash3, $returnType)
{
    global $config;

    // Error if user cannot read event responses.
    if (!check_acl($config['id_user'], 0, 'PM')) {
        returnError('forbidden', $returnType);
        return;
    }

    $responses = event_responses_get_responses();
    if (empty($responses)) {
        returnError('no_data_to_show', $returnType);
        return;
    }

    returnData($returnType, ['type' => 'array', 'data' => $responses]);
}


 /**
  * @param $id_response
  * @param $trash2
  * @param mixed       $trash3
  * @param $returnType
  *    Example:
  *    api.php?op=set&op2=delete_event_response&id=7&apipass=1234&user=admin&pass=pandora
  */
function api_set_delete_event_response($id_response, $trash1, $trash2, $returnType)
{
    global $config;

    // Error if user cannot read event responses.
    if (!check_acl($config['id_user'], 0, 'PM')) {
        returnError('forbidden', $returnType);
        return;
    }

    // Check if id exists
    $event_group = db_get_value('id_group', 'tevent_response', 'id', $id_response);
    if ($event_group === false) {
        returnError('id_not_found', $returnType);
        return;
    }

    // Check user if can edit the module
    if (!check_acl($config['id_user'], $event_group, 'PM')) {
        returnError('forbidden', $returnType);
        return;
    }

    $result = db_process_sql_delete('tevent_response', ['id' => $id_response]);
    returnData($returnType, ['type' => 'string', 'data' => $result]);
}


/**
 * @param $trash1
 * @param $trash2
 * @param mixed      $other. Serialized params
 * @param $returnType
 *    Example:
 *    api.php?op=set&op2=create_event_response&other=response%7Cdescription%20response%7Ctouch%7Ccommand%7C0%7C650%7C400%7C0%7Cresponse%7C0%7C90&other_mode=url_encode_separator_%7C&apipass=1234&user=admin&pass=pandora
 */
function api_set_create_event_response($trash1, $trash2, $other, $returnType)
{
    global $config;

    // Error if user cannot read event responses.
    if (!check_acl($config['id_user'], 0, 'PM')) {
        returnError('forbidden', $returnType);
        return;
    }

    $values = [];
    $values['name'] = $other['data'][0];
    $values['description'] = $other['data'][1];
    $values['target'] = $other['data'][2];
    $values['type'] = $other['data'][3];
    $values['id_group'] = $other['data'][4];
    $values['modal_width'] = $other['data'][5];
    $values['modal_height'] = $other['data'][6];
    $values['new_window'] = $other['data'][7];
    $values['params'] = $other['data'][8];
    $values['server_to_exec'] = $other['data'][9];
    $values['command_timeout'] = $other['data'][10];

    // Error if user has not permission for the group.
    if (!check_acl($config['id_user'], $values['id_group'], 'PM')) {
        returnError('forbidden', $returnType);
        return;
    }

    $return = event_responses_create_response($values) ? 1 : 0;

    returnData($returnType, ['type' => 'string', 'data' => $return]);
}


/**
 * @param $id_response
 * @param $trash2
 * @param mixed       $other. Serialized params
 * @param $returnType
 *    Example:
 *    api.php?op=set&op2=update_event_response&id=7&other=response%7Cdescription%20response%7Ctouch%7Ccommand%7C0%7C650%7C400%7C0%7Cresponse%7C0%7C90&other_mode=url_encode_separator_%7C&apipass=1234&user=admin&pass=pandora
 */
function api_set_update_event_response($id_response, $trash1, $other, $returnType)
{
    global $config;

    // Error if user cannot read event responses.
    if (!check_acl($config['id_user'], 0, 'PM')) {
        returnError('forbidden', $returnType);
        return;
    }

    // Check if id exists
    $event_response = db_get_row('tevent_response', 'id', $id_response);
    if ($event_response === false) {
        returnError('id_not_found', $returnType);
        return;
    }

    // Check user if can edit the module
    if (!check_acl($config['id_user'], $event_response['id_group'], 'PM')) {
        returnError('forbidden', $returnType);
        return;
    }

    $values = [];
    $values['name'] = $other['data'][0] == '' ? $event_response['name'] : $other['data'][0];
    $values['description'] = $other['data'][1] == '' ? $event_response['description'] : $other['data'][1];
    $values['target'] = $other['data'][2] == '' ? $event_response['target'] : $other['data'][2];
    $values['type'] = $other['data'][3] == '' ? $event_response['type'] : $other['data'][3];
    $values['id_group'] = $other['data'][4] == '' ? $event_response['id_group'] : $other['data'][4];
    $values['modal_width'] = $other['data'][5] == '' ? $event_response['modal_width'] : $other['data'][5];
    $values['modal_height'] = $other['data'][6] == '' ? $event_response['modal_height'] : $other['data'][6];
    $values['new_window'] = $other['data'][7] == '' ? $event_response['new_window'] : $other['data'][7];
    $values['params'] = $other['data'][8] == '' ? $event_response['params'] : $other['data'][8];
    $values['server_to_exec'] = $other['data'][9] == '' ? $event_response['server_to_exec'] : $other['data'][9];
    $values['command_timeout'] = $other['data'][10] == '' ? $event_response['command_timeout'] : $other['data'][10];

    // Error if user has not permission for the group.
    if (!check_acl($config['id_user'], $values['id_group'], 'PM')) {
        returnError('forbidden', $returnType);
        return;
    }

    $return = event_responses_update_response($id_response, $values) ? 1 : 0;

    returnData($returnType, ['type' => 'string', 'data' => $return]);
}


function api_get_cluster_items($cluster_id)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    $clusters = Cluster::search(['id' => $cluster_id]);
    $cluster = $clusters[0];

    if (!check_acl($config['id_user'], $cluster['group'], 'AR')) {
        returnError('The user cannot access to the cluster');
        return;
    }

    $cluster = new Cluster($cluster_id);
    $cluster_modules_AA = $cluster->getItems(MODULE_PREDICTION_CLUSTER_AA);
    $cluster_modules_AP = $cluster->getItems(MODULE_PREDICTION_CLUSTER_AP);
    $cluster_modules = array_merge($cluster_modules_AA, $cluster_modules_AP);

    $cluster_items = [];

    foreach ($cluster_modules as $cm) {
        $cluster_module_values = $cm->toArray();

        $module_obj = $cm->getModule();
        $module_values = $module_obj->toArray();

        $cluster_items[] = [
            'id'               => $cluster_module_values['id'],
            'name'             => $cluster_module_values['name'],
            'id_agente_modulo' => $module_values['id_agente_modulo'],
            'item_type'        => $cluster_module_values['item_type'],
            'critical_limit'   => $cluster_module_values['critical_limit'],
            'warning_limit'    => $cluster_module_values['warning_limit'],
            'is_critical'      => $cluster_module_values['is_critical'],
        ];
    }

    $cluster_items_2 = [];

    foreach ($cluster_items as $key => $value) {
        $cluster_items_2[$cluster_items[$key]['id']]['name'] = $cluster_items[$key]['name'];
        $cluster_items_2[$cluster_items[$key]['id']]['id_agente_modulo'] = $cluster_items[$key]['id_agente_modulo'];
        $cluster_items_2[$cluster_items[$key]['id']]['type'] = $cluster_items[$key]['item_type'];

        if ($cluster_items_2[$cluster_items[$key]['id']]['type'] == 'AA') {
            $cluster_items_2[$cluster_items[$key]['id']]['pcrit'] = $cluster_items[$key]['critical_limit'];
        } else if ($cluster_items_2[$cluster_items[$key]['id']]['type'] == 'AP') {
            $cluster_items_2[$cluster_items[$key]['id']]['pcrit'] = $cluster_items[$key]['is_critical'];
        }

        if ($cluster_items_2[$cluster_items[$key]['id']]['type'] == 'AA') {
            $cluster_items_2[$cluster_items[$key]['id']]['pwarn'] = $cluster_items[$key]['warning_limit'];
        } else if ($cluster_items_2[$cluster_items[$key]['id']]['type'] == 'AP') {
            $cluster_items_2[$cluster_items[$key]['id']]['pwarn'] = null;
        }

        unset($cluster_items_2[$key]['id']);
    }

    $all_items = $cluster_items_2;

    if (count($all_items) > 0 and $all_items !== false) {
        $data = [
            'type' => 'json',
            'data' => $all_items,
        ];

        returnData('json', $data);
    } else {
        returnError('No items were retrieved.');
    }
}


/**
 * Create an event filter.
 *
 * @param string            $id    Name of event filter to add.
 * @param $thrash1 Don't use.
 * @param array             $other it's array, $other as param is<id_group_filter>;<id_group>;<event_type>;
 *              <severity>;<event_status>;<free_search>;<agent_search_id>;<pagination_size>;<max_hours_old>;<id_user_ack>;<duplicate>;
 *              <date_from>;<date_to>;<events_with_tags>;<events_without_tags>;<alert_events>;<module_search_id>;<source>;
 *              <id_extra>;<user_comment> in this order
 *              and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *
 *                 example: api.php?op=set&op2=create_event_filter&id=test&other=||error|4|||1||12|||2018-12-09|2018-12-13|[%226%22]|[%2210%22,%226%22,%223%22]|1|10|||&other_mode=url_encode_separator_|
 *
 * @param $thrash3 Don't use
 */
function api_set_create_event_filter($name, $thrash1, $other, $thrash3)
{
    if ($name == '') {
        returnError(
            'The event filter could not be created. Event filter name cannot be left blank.'
        );
        return;
    }

    $event_w = check_acl($config['id_user'], 0, 'EW');
    $event_m = check_acl($config['id_user'], 0, 'EM');
    $access = ($event_w == true) ? 'EW' : (($event_m == true) ? 'EM' : 'EW');

    $event_filter_name = $name;

    $user_groups = users_get_groups($config['id_user'], 'AR', true);

    $id_group_filter = (array_key_exists($other['data'][0], $user_groups)) ? $other['data'][0] : 0;

    $id_group = (array_key_exists($other['data'][1], $user_groups)) ? $other['data'][1] : 0;

    $event_type = (array_key_exists($other['data'][2], get_event_types()) || $other['data'][2] == '') ? $other['data'][2] : '';

    $severity = (array_key_exists($other['data'][3], get_priorities()) || $other['data'][3] == -1) ? $other['data'][3] : -1;

    $status = (array_key_exists($other['data'][4], events_get_all_status()) || $other['data'][4] == -1) ? $other['data'][4] : -1;

    if (!is_numeric($other['data'][6]) || empty($other['data'][6])) {
        $text_agent = '';
        $id_agent = 0;
    } else {
        $filter = [];

        if ($id_group == 0) {
            $filter['id_grupo'] = array_keys($user_groups);
        } else {
            $filter['id_grupo'] = $id_group;
        }

        $filter[] = '(id_agente = '.$other['data'][6].')';
        $agent = agents_get_agents($filter, ['id_agente']);

        if ($agent === false) {
            $text_agent = '';
        } else {
            $sql = sprintf(
                'SELECT alias
                FROM tagente
                WHERE id_agente = %d',
                $agent[0]['id_agente']
            );

            $id_agent = $other['data'][6];
            $text_agent = db_get_value_sql($sql);
        }
    }

    $pagination = (in_array($other['data'][7], [20, 25, 50, 100, 200, 500])) ? $other['data'][7] : 20;

    $users = users_get_user_users($config['id_user'], $access, users_can_manage_group_all());

    $id_user_ack = (in_array($other['data'][9], $users)) ? $other['data'][9] : 0;

    $group_rep = ($other['data'][10] == EVENT_GROUP_REP_ALL || $other['data'][10] == EVENT_GROUP_REP_EVENTS) ? $other['data'][10] : EVENT_GROUP_REP_ALL;

    $date_from = (preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/', $other['data'][11])) ? $other['data'][11] : '0000-00-00';

    $date_to = (preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/', $other['data'][12])) ? $other['data'][12] : '0000-00-00';

    $tag_with = (preg_match('/^\[(("\d+"((,|\])("\d+"))+)|"\d+")\]$/', io_safe_output($other['data'][13]))) ? $other['data'][13] : '[]';

    $tag_without = (preg_match('/^\[(("\d+"((,|\])("\d+"))+)|"\d+")\]$/', io_safe_output($other['data'][14]))) ? $other['data'][14] : '[]';

    $filter_only_alert = (in_array($other['data'][15], [-1, 0, 1])) ? $other['data'][15] : -1;

    if (!is_numeric($other['data'][16]) || empty($other['data'][16])) {
        $id_agent_module = 0;
    } else {
        $groups = [];

        $groups = users_get_groups($config['id_user'], 'AW', false);
        $groups = array_keys($groups);

        if (empty($groups)) {
            $id_groups = 0;
        } else {
            $id_groups = implode(',', $groups);
        }

        $agents = db_get_all_rows_sql(
            'SELECT id_agente
            FROM tagente
            WHERE id_grupo IN ('.$id_groups.')'
        );

        if ($agents === false) {
            $agents = [];
        }

        $id_agents = [];
        foreach ($agents as $agent) {
            $id_agents[] = $agent['id_agente'];
        }

        $filter = '('.$other['data'][16].')';

        $modules = agents_get_modules(
            $id_agents,
            false,
            (['tagente_modulo.id_agente_modulo in' => $filter])
        );

        $id_agent_module = (array_key_exists($other['data'][16], $modules)) ? $other['data'][16] : 0;
    }

    $values = [
        'id_group_filter'   => $id_group_filter,
        'id_group'          => $id_group,
        'event_type'        => $event_type,
        'severity'          => $severity,
        'status'            => $status,
        'search'            => $other['data'][5],
        'text_agent'        => $text_agent,
        'id_agent'          => $id_agent,
        'pagination'        => $pagination,
        'event_view_hr'     => $other['data'][8],
        'id_user_ack'       => $id_user_ack,
        'group_rep'         => $group_rep,
        'date_from'         => $date_from,
        'date_to'           => $date_to,
        'tag_with'          => $tag_with,
        'tag_without'       => $tag_without,
        'filter_only_alert' => $filter_only_alert,
        'id_agent_module'   => $id_agent_module,
        'source'            => $other['data'][17],
        'id_extra'          => $other['data'][18],
        'user_comment'      => $other['data'][19],
    ];

    $values['id_name'] = $event_filter_name;

    $id_filter = db_process_sql_insert('tevent_filter', $values);

    if ($id_filter === false) {
        returnError('The event filter could not be created.');
    } else {
        returnData(
            'string',
            [
                'type' => 'string',
                'data' => __('Event filter successfully created.'),
            ]
        );
    }

}


/**
 * Update an event filter. And return a message with the result of the operation.
 *
 * @param string            $id_event_filter Id of the event filter to update.
 * @param $thrash1 Don't use.
 * @param array             $other           it's array, $other as param is <filter_name>;<id_group>;<event_type>;
 *                        <severity>;<event_status>;<free_search>;<agent_search_id>;<pagination_size>;<max_hours_old>;<id_user_ack>;<duplicate>;
 *                        <date_from>;<date_to>;<events_with_tags>;<events_without_tags>;<alert_events>;<module_search_id>;<source>;
 *                        <id_extra>;<user_comment> in this order
 *                        and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *
 *                        example:
 *
 *                       api.php?op=set&op2=update_event_filter&id=198&other=new_name|||alert_recovered|||||||||||||||||&other_mode=url_encode_separator_%7C
 *
 * @param $thrash3 Don't use
 */
function api_set_update_event_filter($id_event_filter, $thrash1, $other, $thrash3)
{
    global $config;

    if (!check_acl($config['id_user'], 0, 'LM')) {
        returnError('forbidden', 'string');
        return;
    }

    if ($id_event_filter == '') {
        returnError(
            'The event filter could not be updated. Event filter ID cannot be left blank.'
        );
        return;
    }

    $sql = "SELECT * FROM tevent_filter WHERE id_filter=$id_event_filter";
    $result_event_filter = db_get_row_sql($sql);

    if (!$result_event_filter) {
        returnError(
            'The event filter could not be updated. Event filter ID does not exist.'
        );
        return;
    }

    $values = [];

    for ($i = 0; $i < 21; $i++) {
        if ($other['data'][$i] != '') {
            switch ($i) {
                case 0:
                    $values['id_name'] = $other['data'][0];
                break;

                case 1:
                    $user_groups = users_get_groups($config['id_user'], 'AR', true);
                    $values['id_group_filter'] = (array_key_exists($other['data'][1], $user_groups)) ? $other['data'][1] : 0;
                break;

                case 2:
                    $user_groups = users_get_groups($config['id_user'], 'AR', true);
                    $values['id_group'] = (array_key_exists($other['data'][2], $user_groups)) ? $other['data'][2] : 0;
                break;

                case 3:
                    $values['event_type'] = (array_key_exists($other['data'][3], get_event_types()) || $other['data'][3] == '') ? $other['data'][3] : '';
                break;

                case 4:
                    $values['severity'] = (array_key_exists($other['data'][4], get_priorities()) || $other['data'][4] == -1) ? $other['data'][4] : -1;
                break;

                case 5:
                    $values['status'] = (array_key_exists($other['data'][5], events_get_all_status()) || $other['data'][5] == -1) ? $other['data'][5] : -1;
                break;

                case 6:
                    $values['search'] = $other['data'][6];
                break;

                case 7:
                    $user_groups = users_get_groups($config['id_user'], 'AR', true);

                    if (!is_numeric($other['data'][7]) || empty($other['data'][7])) {
                        $values['text_agent'] = '';
                        $values['id_agent'] = 0;
                    } else {
                        $filter = [];

                        if ($id_group == 0) {
                            $filter['id_grupo'] = array_keys($user_groups);
                        } else {
                            $filter['id_grupo'] = $id_group;
                        }

                        $filter[] = '(id_agente = '.$other['data'][7].')';
                        $agent = agents_get_agents($filter, ['id_agente']);

                        if ($agent === false) {
                            $values['text_agent'] = '';
                        } else {
                            $sql = sprintf(
                                'SELECT alias
                                FROM tagente
                                WHERE id_agente = %d',
                                $agent[0]['id_agente']
                            );

                            $values['id_agent'] = $other['data'][7];
                            $values['text_agent'] = db_get_value_sql($sql);
                        }
                    }
                break;

                case 8:
                    $values['pagination'] = (in_array($other['data'][8], [20, 25, 50, 100, 200, 500])) ? $other['data'][8] : 20;
                break;

                case 9:
                    $values['event_view_hr'] = $other['data'][9];
                break;

                case 10:

                    $event_w = check_acl($config['id_user'], 0, 'EW');
                    $event_m = check_acl($config['id_user'], 0, 'EM');
                    $access = ($event_w == true) ? 'EW' : (($event_m == true) ? 'EM' : 'EW');

                    $users = users_get_user_users($config['id_user'], $access, users_can_manage_group_all());

                    $values['id_user_ack'] = (in_array($other['data'][10], $users)) ? $other['data'][10] : 0;
                break;

                case 11:
                    $values['group_rep'] = ($other['data'][11] == EVENT_GROUP_REP_ALL || $other['data'][11] == EVENT_GROUP_REP_EVENTS) ? $other['data'][11] : EVENT_GROUP_REP_ALL;
                break;

                case 12:
                    $values['date_from'] = (preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/', $other['data'][12])) ? $other['data'][12] : '0000-00-00';
                break;

                case 13:
                    $values['date_to'] = (preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/', $other['data'][13])) ? $other['data'][13] : '0000-00-00';
                break;

                case 14:
                    $values['tag_with'] = (preg_match('/^\[(("\d+"((,|\])("\d+"))+)|"\d+")\]$/', io_safe_output($other['data'][14]))) ? $other['data'][14] : '[]';
                break;

                case 15:
                    $values['tag_without'] = (preg_match('/^\[(("\d+"((,|\])("\d+"))+)|"\d+")\]$/', io_safe_output($other['data'][15]))) ? $other['data'][15] : '[]';
                break;

                case 16:
                    $values['filter_only_alert'] = (in_array($other['data'][16], [-1, 0, 1])) ? $other['data'][16] : -1;
                break;

                case 17:
                    if (!is_numeric($other['data'][17]) || empty($other['data'][17])) {
                        $values['id_agent_module'] = 0;
                    } else {
                        $groups = [];

                        $groups = users_get_groups($config['id_user'], 'AW', false);
                        $groups = array_keys($groups);

                        if (empty($groups)) {
                            $id_groups = 0;
                        } else {
                            $id_groups = implode(',', $groups);
                        }

                        $agents = db_get_all_rows_sql(
                            'SELECT id_agente
                            FROM tagente
                            WHERE id_grupo IN ('.$id_groups.')'
                        );

                        if ($agents === false) {
                            $agents = [];
                        }

                        $id_agents = [];
                        foreach ($agents as $agent) {
                            $id_agents[] = $agent['id_agente'];
                        }

                        $filter = '('.$other['data'][17].')';

                        $modules = agents_get_modules(
                            $id_agents,
                            false,
                            (['tagente_modulo.id_agente_modulo in' => $filter])
                        );

                        $values['id_agent_module'] = (array_key_exists($other['data'][17], $modules)) ? $other['data'][17] : 0;
                    }
                break;

                case 18:
                    $values['source'] = $other['data'][18];
                break;

                case 19:
                    $values['id_extra'] = $other['data'][19];
                break;

                case 20:
                    $values['user_comment'] = $other['data'][20];
                break;
            }
        }
    }

    $result = db_process_sql_update(
        'tevent_filter',
        $values,
        ['id_filter' => $id_event_filter]
    );

    if ($result === false) {
        returnError('The event filter could not be updated');
    } else {
        returnData(
            'string',
            [
                'type' => 'string',
                'data' => __('Event filter successfully updated.'),
            ]
        );
    }

}


/**
 * Delete an event filter. And return a message with the result of the operation.
 *
 * @param string            $id_template Id of the event filter to delete.
 * @param $thrash1 Don't use.
 * @param array             $other       Don't use
 *
 *                    example:
 *
 *                   api.php?op=set&op2=delete_event_filter&id=38
 *
 * @param $thrash3 Don't use
 */
function api_set_delete_event_filter($id_event_filter, $thrash1, $other, $thrash3)
{
    if ($id_event_filter == '') {
        returnError(
            'The event filter could not be deleted. Event filter ID cannot be left blank.'
        );
        return;
    }

    $result = db_process_sql_delete('tevent_filter', ['id_filter' => $id_event_filter]);

    if ($result == 0) {
        returnError(
            'The event filter could not be deleted.'
        );
    } else {
        returnData(
            'string',
            [
                'type' => 'string',
                'data' => __('Event filter successfully deleted.'),
            ]
        );
    }
}


/**
 * Get all event filters, and print all the result like a csv.
 *
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array             $other it's array, but only <csv_separator> is available.
 *              example:
 *
 *              api.php?op=get&op2=all_event_filters&return_type=csv&other=;
 *
 * @param $thrash3 Don't use.
 */
function api_get_all_event_filters($thrash1, $thrash2, $other, $thrash3)
{
    global $config;

    if (!isset($other['data'][0])) {
        $separator = ';';
        // by default
    } else {
        $separator = $other['data'][0];
    }

    if (!check_acl($config['id_user'], 0, 'LM')) {
        returnError('forbidden', 'csv');
        return;
    }

    $filter = false;

    $sql = 'SELECT * FROM tevent_filter';
      $event_filters = db_get_all_rows_sql($sql);

    if ($event_filters !== false) {
        $data['type'] = 'array';
        $data['data'] = $event_filters;
    }

    if (!$event_filters) {
        returnError(
            'Could not get all event filters.'
        );
    } else {
        returnData('csv', $data, $separator);
    }
}


function api_get_user_info($thrash1, $thrash2, $other, $returnType)
{
    $separator = ';';

    $other = json_decode(base64_decode($other['data']), true);

    $sql = sprintf(
        'SELECT * FROM tusuario WHERE id_user = "%s" and password = "%s"',
        mysql_escape_string_sql($other[0]['id_user']),
        mysql_escape_string_sql($other[0]['password'])
    );

    $user_info = db_get_all_rows_sql($sql);

    if (count($user_info) > 0 and $user_info !== false) {
        $data = [
            'type' => 'array',
            'data' => $user_info,
        ];
        returnData($returnType, $data, $separator);
    } else {
        return 0;
    }
}


/*
    This function receives different parameters to process one of these actions the logging process in our application from the records in the audit of pandora fms, to avoid concurrent access of administrator users, and optionally to prohibit access to non-administrator users:

    Parameter 0

    The User ID that attempts the action is used to check the status of the application for access.

    Parameter 1

    Login, logout, exclude, browse.

    These requests receive a response that we can treat as we consider, this function only sends answers, does not perform any action in your application, you must customize them.

    Login action: free (register our access), taken, denied (if you are not an administrator user and parameter four is set to 1, register the expulsion).

    Browse action: It has the same answers as login, but does not register anything in the audit.

    Logout action: It records the deslogeo but does not send a response.

    All other actions do not return a response,

    Parameter 2

    IP address of the application is also used to check the status of the application for access.

    Parameter 3

    Name of the application, it is also used to check the status of the application for access.

    Parameter 4

    If you mark 1 you will avoid the access to the non-administrators users, returning the response `denied' and registering that expulsion in the audit of pandora fms.

*/
function api_set_access_process($thrash1, $thrash2, $other, $returnType)
{
    if (defined('METACONSOLE')) {
        return;
    }

    $other['data'] = explode('|', $other['data']);

    $sql = 'select id_usuario,utimestamp from tsesion where descripcion like "%'.$other['data'][2].'%" and accion like "%'.$other['data'][3].'&#x20;Logon%" and id_usuario IN (select id_user from tusuario where is_admin = 1) and id_usuario != "'.$other['data'][0].'" order by utimestamp DESC limit 1';
    $audit_concurrence = db_get_all_rows_sql($sql);
    $sql_user = 'select id_usuario,utimestamp from tsesion where descripcion like "%'.$other['data'][2].'%" and accion like "%'.$other['data'][3].'&#x20;Logon%" and id_usuario IN (select id_user from tusuario where is_admin = 1) and id_usuario = "'.$other['data'][0].'" order by utimestamp DESC limit 1';
    $audit_concurrence_user = db_get_all_rows_sql($sql_user);
    $sql2 = 'select id_usuario,utimestamp,accion from tsesion where descripcion like "%'.$other['data'][2].'%" and accion like "%'.$other['data'][3].'&#x20;Logoff%" and id_usuario = "'.$audit_concurrence[0]['id_usuario'].'" order by utimestamp DESC limit 1';
    $audit_concurrence_2 = db_get_all_rows_sql($sql2);

    // The user trying to log in is an administrator
    if (users_is_admin($other['data'][0])) {
        // The admin user is trying to login
        if ($other['data'][1] == 'login') {
            // Check if there is an administrator user logged in prior to our last login
            if ($audit_concurrence[0]['utimestamp'] > $audit_concurrence_user[0]['utimestamp']) {
                // Check if the administrator user logged in later to us has unlogged and left the node free
                if ($audit_concurrence[0]['utimestamp'] > $audit_concurrence_2[0]['utimestamp']) {
                    // The administrator user logged in later has not yet unlogged
                    returnData('string', ['type' => 'string', 'data' => 'taken']);
                } else {
                    // The administrator user logged in later has already unlogged
                    returnData('string', ['type' => 'string', 'data' => 'free']);
                }
            } else {
                // There is no administrator user who has logged in since then to log us in.
                db_pandora_audit(
                    AUDIT_LOG_USER_REGISTRATION,
                    'Logged in '.$other['data'][3].' node '.$other['data'][2],
                    $other['data'][0]
                );
                returnData('string', ['type' => 'string', 'data' => 'free']);
            }
        } else if ($other['data'][1] == 'logout') {
            // The administrator user wants to log out
            db_pandora_audit(
                AUDIT_LOG_USER_REGISTRATION,
                'Logout from '.$other['data'][3].' node '.$other['data'][2],
                $other['data'][0]
            );
        } else if ($other['data'][1] == 'exclude') {
            // The administrator user has ejected another administrator user who was logged in
            db_pandora_audit(
                AUDIT_LOG_USER_REGISTRATION,
                'Logged in '.$other['data'][3].' node '.$other['data'][2],
                $other['data'][0]
            );
            db_pandora_audit(
                AUDIT_LOG_USER_REGISTRATION,
                'Logout from '.$other['data'][3].' node '.$other['data'][2],
                $audit_concurrence[0]['id_usuario']
            );
        }
        // The admin user is trying to browse
        else if ($other['data'][1] == 'browse') {
            // Check if there is an administrator user logged in prior to our last login
            if ($audit_concurrence[0]['utimestamp'] > $audit_concurrence_user[0]['utimestamp']) {
                // Check if the administrator user logged in later to us has unlogged and left the node free
                if ($audit_concurrence[0]['utimestamp'] > $audit_concurrence_2[0]['utimestamp']) {
                    // The administrator user logged in later has not yet unlogged
                    returnData('string', ['type' => 'string', 'data' => $audit_concurrence[0]['id_usuario']]);
                } else {
                    // The administrator user logged in later has already unlogged
                    returnData('string', ['type' => 'string', 'data' => 'free']);
                }
            } else {
                // There is no administrator user who has logged in since then to log us in.
                returnData('string', ['type' => 'string', 'data' => 'free']);
            }
        } else if ($other['data'][1] == 'cancelled') {
            // The administrator user tries to log in having another administrator logged in, but instead of expelling him he cancels his log in.
            db_pandora_audit(
                AUDIT_LOG_USER_REGISTRATION,
                'Cancelled access in '.$other['data'][3].' node '.$other['data'][2],
                $other['data'][0]
            );
            returnData('string', ['type' => 'string', 'data' => 'cancelled']);
        }
    } else {
        if ($other['data'][4] == 1) {
            // The user trying to log in is not an administrator and is not allowed no admin access
            db_pandora_audit(
                AUDIT_LOG_USER_REGISTRATION,
                'Denied access to non-admin user '.$other['data'][3].' node '.$other['data'][2],
                $other['data'][0]
            );
            returnData('string', ['type' => 'string', 'data' => 'denied']);
        } else {
            // The user trying to log in is not an administrator and is allowed no admin access
            if ($other['data'][1] == 'login') {
                // The user trying to login is not admin, can enter without concurrent use filter
                db_pandora_audit(
                    AUDIT_LOG_USER_REGISTRATION,
                    'Logged in '.$other['data'][3].' node '.$other['data'][2],
                    $other['data'][0]
                );
                returnData('string', ['type' => 'string', 'data' => 'free']);
            } else if ($other['data'][1] == 'logout') {
                // The user trying to logoff is not admin
                db_pandora_audit(
                    AUDIT_LOG_USER_REGISTRATION,
                    'Logout from '.$other['data'][3].' node '.$other['data'][2],
                    $other['data'][0]
                );
            } else if ($other['data'][1] == 'browse') {
                // The user trying to browse in an app page is not admin, can enter without concurrent use filter
                returnData('string', ['type' => 'string', 'data' => 'free']);
            }
        }
    }
}


function api_get_traps($thrash1, $thrash2, $other, $returnType)
{
    if (defined('METACONSOLE')) {
        return;
    }

    $other['data'] = explode('|', $other['data']);

    $other['data'][1] = date('Y-m-d H:i:s', $other['data'][1]);

    $sql = 'SELECT * from ttrap where timestamp >= "'.$other['data'][1].'"';

    // $sql = 'SELECT * from ttrap where source = "'.$other['data'][0].'" and timestamp >= "'.$other['data'][1].'"';
    if ($other['data'][4]) {
        $other['data'][4] = date('Y-m-d H:i:s', $other['data'][4]);
        $sql .= ' and timestamp <= "'.$other['data'][4].'"';
    }

    if ($other['data'][2]) {
        $sql .= ' limit '.$other['data'][2];
    }

    if ($other['data'][3]) {
        $sql .= ' offset '.$other['data'][3];
    }

    if ($other['data'][5]) {
        $sql .= ' and status = 0';
    }

    if (count($other['data']) == 0) {
        $sql = 'SELECT * from ttrap';
    }

    $traps = db_get_all_rows_sql($sql);

    if ($other['data'][6]) {
        foreach ($traps as $key => $value) {
            if (!strpos($value['oid_custom'], $other['data'][6]) && $other['data'][7] == 'false') {
                unset($traps[$key]);
            }

            if (strpos($value['oid_custom'], $other['data'][6]) && $other['data'][7] == 'true') {
                unset($traps[$key]);
            }
        }
    }

    $traps_json = json_encode($traps);

    if (count($traps) > 0 and $traps !== false) {
        returnData('string', ['type' => 'string', 'data' => $traps_json]);
    } else {
        return 0;
    }

}


function api_set_validate_traps($id, $thrash2, $other, $thrash3)
{
    if (defined('METACONSOLE')) {
        return;
    }

    if ($id == 'all') {
        $result = db_process_sql_update('ttrap', ['status' => 1]);
    } else {
        $result = db_process_sql_update(
            'ttrap',
            ['status' => 1],
            ['id_trap' => $id]
        );
    }

    if (is_error($result)) {
        // TODO: Improve the error returning more info
        returnError('Trap could not be updated.');
    } else {
            returnData(
                'string',
                [
                    'type' => 'string',
                    'data' => __('Validated traps.'),
                ]
            );
    }
}


function api_set_delete_traps($id, $thrash2, $other, $thrash3)
{
    if (defined('METACONSOLE')) {
        return;
    }

    if ($id == 'all') {
        $result = db_process_sql('delete from ttrap');
    } else {
        $result = db_process_sql_delete('ttrap', ['id_trap' => $id]);
    }

    if (is_error($result)) {
        // TODO: Improve the error returning more info
        returnError('Trap could not be deleted.');
    } else {
            returnData(
                'string',
                [
                    'type' => 'string',
                    'data' => __('Deleted traps.'),
                ]
            );
    }
}


function api_get_group_id_by_name($thrash1, $thrash2, $other, $thrash3)
{
    if (defined('METACONSOLE')) {
        return;
    }

    if (is_array($other['data']) === true) {
        $group_id = $other['data'][0];
    } else {
        $group_id = $other['data'];
    }

    $sql = sprintf(
        'SELECT id_grupo
        FROM tgrupo WHERE nombre = "'.$group_id.'"'
    );

    $group_id = db_get_all_rows_sql($sql);

    if (count($group_id) > 0 and $group_id !== false) {
        $data = [
            'type' => 'array',
            'data' => $group_id,
        ];

        returnData('csv', $data, ';');
    } else {
        returnError('No groups were retrieved.');
    }
}


function api_get_timezone($thrash1, $thrash2, $other, $thrash3)
{
    if (defined('METACONSOLE')) {
        return;
    }

    $sql = sprintf(
        'SELECT value
        FROM tconfig WHERE token = "timezone"'
    );

    $timezone = db_get_all_rows_sql($sql);

    if (count($timezone) > 0 and $timezone !== false) {
        $data = [
            'type' => 'string',
            'data' => $timezone,
        ];

        returnData('string', ['type' => 'string', 'data' => $data['data'][0]['value']]);
    } else {
        returnError('No timezone were retrieved.');
    }
}


function api_get_language($thrash1, $thrash2, $other, $thrash3)
{
    if (defined('METACONSOLE')) {
        return;
    }

    $sql = sprintf(
        'SELECT value
        FROM tconfig WHERE token = "language"'
    );

    $language = db_get_all_rows_sql($sql);

    if (count($language) > 0 and $language !== false) {
        $data = [
            'type' => 'string',
            'data' => $language,
        ];

        returnData('string', ['type' => 'string', 'data' => $data['data'][0]['value']]);
    } else {
        returnError('No language were retrieved.');
    }
}


function api_get_session_timeout($thrash1, $thrash2, $other, $thrash3)
{
    if (defined('METACONSOLE')) {
        return;
    }

    $sql = sprintf(
        'SELECT value
        FROM tconfig WHERE token = "session_timeout"'
    );

    $language = db_get_all_rows_sql($sql);

    if (count($language) > 0 and $language !== false) {
        $data = [
            'type' => 'string',
            'data' => $language,
        ];

        returnData('string', ['type' => 'string', 'data' => $data['data'][0]['value']]);
    } else {
        returnError('No session timeout were retrieved.');
    }
}


function api_get_users($thrash1, $thrash2, $other, $returnType)
{
            global $config;

            $user_info = get_users();

    if (!isset($returnType) || empty($returnType) || $returnType == '') {
        $returnType = 'json';
        $data['data'] = 'json';
    }

    if (!isset($separator) || empty($separator) || $separator == '') {
        $separator = ';';
    }

            $data['data'] = $user_info;

    if (count($data) > 0 and $data !== false) {
        returnData($returnType, $data, $separator);
    } else {
        returnError('No users were retrieved.');
    }

}


/**
 * Resets module counts and alert counts in the agents
 *
 * @param $id id of the agent you want to synchronize. Add "All" to synchronize all agents
 * @param $trash1
 * @param $trash2
 * @param $trash3
 *
 * Example:
 * api.php?op=set&op2=reset_agent_counts&apipass=1234&user=admin&pass=pandora&id=All
 */
function api_set_reset_agent_counts($id, $thrash1, $thrash2, $thrash3)
{
    global $config;

    if (!check_acl($config['id_user'], 0, 'AW')) {
        returnError('forbidden', 'string');
        return;
    }

    if ($id == '' || !$id) {
        returnError('Error. Agent cannot be left blank.');
        return;
    }

    if ($id != 'All') {
        $agent = db_get_row_filter('tagente', ['id_agente' => $id]);
        if (empty($agent)) {
            returnError('This agent does not exist.');
            return;
        } else {
            $return = db_process_sql_update(
                'tagente',
                [
                    'update_module_count' => 1,
                    'update_alert_count'  => 1,
                ],
                ['id_agente' => $id]
            );
        }
    } else {
        $return = db_process_sql_update(
            'tagente',
            [
                'update_module_count' => 1,
                'update_alert_count'  => 1,
            ]
        );
    }

    $data = __('Successfully updated module/alert count in id agent %d.', $id);
    if ($id == 'All') {
        $data = __('Successfully updated module/alert count in all agents');
    }

    if ($return === false) {
        returnError('Could not be updated module/alert counts in id agent %d.', $id);
    } else {
        returnData('string', ['type' => 'string', 'data' => $data]);
    }

}


/**
 * Functions por get all  user to new feature for Carrefour
 * It depends of returnType, the method will return csv or json data
 *
 * @param  string $thrash1 don't use
 * @param  string $thrash2 don't use
 * @param  array  $other   don't use
 * *@param  string           $returnType
 * Example:
 * api.php?op=get&op2=list_all_user&return_type=json&apipass=1234&user=admin&pass=pandora
 * @return
 */


function api_get_list_all_user($thrash1, $thrash2, $other, $returnType)
{
    global $config;

    if (!check_acl($config['id_user'], 0, 'AR')) {
        returnError('forbidden', 'string');
        return;
    }

    $sql = 'SELECT
                tup.id_usuario AS user_id,
                tu.fullname AS fullname,
                tp.id_perfil AS profile_id,
                tup.id_up AS id_up,
                tp.name AS profile_name,
                tup.id_grupo AS group_id,
                tgp.nombre AS group_name
            FROM tperfil tp
            INNER JOIN tusuario_perfil tup
                ON tp.id_perfil = tup.id_perfil
            LEFT OUTER JOIN tgrupo tgp
                ON tup.id_grupo = tgp.id_grupo
                LEFT OUTER JOIN tusuario tu
            ON tu.id_user = tup.id_usuario';

    $users = db_get_all_rows_sql($sql);

    $i = 0;

    foreach ($users as $up) {
        $group_name = $up['group_name'];
        if ($up['group_name'] === null) {
            $group_name = 'All';
        }

        $values[$i] = [
            'id_usuario'  => $up['user_id'],
            'fullname'    => $up['fullname'],
            'id_up'       => $up['id_up'],
            'id_perfil'   => $up['profile_id'],
            'perfil_name' => $up['profile_name'],
            'id_grupo'    => $up['group_id'],
            'group_name'  => $group_name,
        ];
        $i += 1;
    }

    if ($values === false) {
        returnError('Users could not be found.');
        return;
    }

    $data = [
        'type' => 'array',
        'data' => $values,
    ];

    returnData($returnType, $data, ';');
}


/**
 * Funtion for get all info user to  new feature for Carrefour
 * It depends of returnType, the method will return csv or json data
 *
 * @param string $thrash1    don't use
 * @param string $thrash2    don't use
 * @param array  $other      other[0] = user database
 * @param string $returnType
 *      Example
 *      api.php?op=get&op2=info_user_name&return_type=json&other=admin&other_mode=url_encode_separator_|&apipass=1234&user=admin&pass=pandora
 *
 * @return
 */


function api_get_info_user_name($thrash1, $thrash2, $other, $returnType)
{
    global $config;

    if (!check_acl($config['id_user'], 0, 'AR')) {
        returnError('forbidden', 'string');
        return;
    }

    $sql = sprintf(
        'SELECT tup.id_usuario AS user_id,
                tu.fullname AS fullname,
                tup.id_up AS id_up,
                tp.id_perfil AS profile_id,
                tp.name AS profile_name,
                tup.id_grupo AS group_id,
                tg.nombre AS group_name
        FROM tperfil tp
        INNER JOIN tusuario_perfil tup
            ON tp.id_perfil = tup.id_perfil
        LEFT OUTER JOIN tgrupo tg
            ON tup.id_grupo = tg.id_grupo
        LEFT OUTER JOIN tusuario tu
            ON tu.id_user = tup.id_usuario
        WHERE tup.id_usuario = "%s"',
        io_safe_output($other['data'][0])
    );

    $user_profile = db_get_all_rows_sql($sql);

    $i = 0;

    foreach ($user_profile as $up) {
        $group_name = $up['group_name'];
        if ($up['group_name'] === null) {
            $group_name = 'All';
        }

        $values[$i] = [
            'id_usuario'  => $up['user_id'],
            'fullname'    => $up['fullname'],
            'id_up'       => $up['id_up'],
            'id_perfil'   => $up['profile_id'],
            'perfil_name' => $up['profile_name'],
            'id_grupo'    => $up['group_id'],
            'group_name'  => $group_name,
        ];
        $i += 1;
    }

        $data = [
            'type' => 'array',
            'data' => $values,
        ];

        returnData($returnType, $data, ';');
}


/**
 * Function for get  user from a group  to  new feature for Carrefour.
 * It depends of returnType, the method will return csv or json data.
 *
 * @param string $thrash1    don't use
 * @param string $thrash2    don't use
 * @param array  $other
 *                  $other[0] = id group
 *                  $other[1] = is disabled or not
 * @param string $returnType
 * Example
 * api.php?op=get&op2=filter_user_group&return_type=json&other=0|0&other_mode=url_encode_separator_|&apipass=1234&user=admin&pass=pandora
 *
 * @return
 */


function api_get_filter_user_group($thrash1, $thrash2, $other, $returnType)
{
    global $config;

    if (!check_acl($config['id_user'], 0, 'AR')) {
        returnError('forbidden', 'string');
        return;
    }

    $filter = '';

    if ($other['data'][0] !== '' && $other['data'][1] !== '') {
        $filter = 'WHERE tup.id_grupo = '.$other['data'][0].' AND tu.disabled = '.$other['data'][1].'';
    } else if ($other['data'][0] !== '') {
        $filter = 'WHERE tup.id_grupo = '.$other['data'][0].'';
    } else if ($other['data'][1] !== '') {
        $filter = 'WHERE tu.disabled = '.$other['data'][1].'';
    }

    $sql = sprintf(
        'SELECT DISTINCT
            tup.id_usuario AS user_id,
            tu.fullname AS fullname,
            tup.id_up AS id_up,
            tp.id_perfil AS profile_id,
            tp.name AS profile_name,
            tup.id_grupo AS group_id,
            tg.nombre AS group_name
        FROM tperfil tp
        INNER JOIN tusuario_perfil tup
            ON tp.id_perfil = tup.id_perfil
        LEFT OUTER JOIN tgrupo tg
            ON tup.id_grupo = tg.id_grupo
        LEFT OUTER JOIN tusuario tu
            ON tu.id_user = tup.id_usuario
       '.$filter.''
    );

    $filter_user = db_get_all_rows_sql($sql);

    $i = 0;

    foreach ($filter_user as $up) {
        $group_name = $up['group_name'];
        if ($up['group_name'] === null) {
            $group_name = 'All';
        }

        $values[$i] = [
            'id_usuario'  => $up['user_id'],
            'fullname'    => $up['fullname'],
            'id_up'       => $up['id_up'],
            'id_perfil'   => $up['profile_id'],
            'perfil_name' => $up['profile_name'],
            'id_grupo'    => $up['group_id'],
            'group_name'  => $group_name,
        ];
        $i += 1;
    }

    $data = [
        'type' => 'array',
        'data' => $values,
    ];

    returnData($returnType, $data, ';');

}


/**
 * Function for delete an user permission for Carrefour  new feature
 * The return of this function its only a message
 *
 * @param string $thrash1    don't use
 * @param string $thrash2    don't use
 * @param array  $other
 *                  $other[0] = id up
 * @param string $returnType
 * Example
 * api.php?op=set&op2=delete_user_permission&return_type=json&other=user|2&other_mode=url_encode_separator_|&apipass=1234&user=admin&pass=pandora
 *
 * @return void
 */


function api_set_delete_user_permission($thrash1, $thrash2, $other, $returnType)
{
    global $config;

    if (!check_acl($config['id_user'], 0, 'AW')) {
        returnError('forbidden', 'string');
        return;
    }

    $idk = get_header('idk');
    if (is_management_allowed($idk) === false) {
        returnError('centralized');
        exit;
    }

    if ($other['data'][0] != '') {
        $values = [
            'id_up' => io_safe_output($other['data'][0]),
        ];
    } else {
        returnError('User profile could not be deleted.');
        return;
    }

    $deleted_permission = db_process_sql_delete('tusuario_perfil', $values);

    if ($deleted_permission == false) {
        returnError('User profile could not be deleted.');
        return;
    }

    $data = [
        'type' => 'string',
        'data' => $deleted_permission,
    ];

        returnData('string', ['type' => 'string', 'data' => $data]);
}


/**
 * Function for add permission a user to a group for Carrefour new feature
 * It depends of returnType, the method will return csv or json data
 *
 * @param string $thrash1 don't use
 * @param string $thrash2 don't use
 * @param array  $other   other[0] = user database
 *                        other[1] = id group
 *                        other[2] = id profile
 *                        other[3] = no_hierarchy ( 0 or 1, if empty = 0)
 *                        other[4] = id from tusuario_perfil table (optional)
 * * @param string $returnType
 * Example
 * api.php?op=set&op2=add_permission_user_to_group&return_type=json&other=admin|0|1|1|20&other_mode=url_encode_separator_|&apipass=1234&user=admin&pass=pandora
 *
 * @return void
 */


function api_set_add_permission_user_to_group($thrash1, $thrash2, $other, $returnType)
{
    global $config;

    if (!check_acl($config['id_user'], 0, 'AW')) {
        returnError('forbidden', 'string');
        return;
    }

    $idk = get_header('idk');
    if (is_management_allowed($idk) === false) {
        returnError('centralized');
        exit;
    }

    $sql = 'SELECT id_up 
            FROM tusuario_perfil
            WHERE  id_up = '.$other['data'][4].'';

    $exist_profile = db_get_value_sql($sql);

    if ($other['data'][3] < 0 || $other['data'][3] > 1) {
        returnError('User profile could not be available.');
        return;
    }

    if ($other['data'][3] == null) {
        $other['data'][3] = 0;
    }

    $values = [
        'id_usuario'   => $other['data'][0],
        'id_perfil'    => $other['data'][2],
        'id_grupo'     => $other['data'][1],
        'no_hierarchy' => $other['data'][3],
        'assigned_by'  => $config['id_user'],
        'id_policy'    => 0,
        'tags'         => '',

    ];

    $group_exist = db_get_value_filter(
        'id_grupo',
        'tgrupo',
        [
            'id_grupo' => $values['id_grupo'],
        ]
    );

    if ((bool) $group_exist === false) {
        returnError('Selected group does not exist');
        return;
    }

    $profile_exist = db_get_value_filter(
        'id_perfil',
        'tperfil',
        [
            'id_perfil' => $values['id_perfil'],
        ]
    );

    if ((bool) $profile_exist === false) {
        returnError('Selected profile does not exist');
        return;
    }

    $where_id_up = ['id_up' => $other['data'][4]];
    if ($exist_profile === $other['data'][4] && $where_id_up !== null) {
        $sucessfull_insert = db_process_sql_update('tusuario_perfil', $values, $where_id_up);
    } else {
        $sucessfull_insert = db_process_sql_insert('tusuario_perfil', $values);
    }

    if ($sucessfull_insert == false) {
        returnError('User profile could not be available.');
        return;
    }

     $data = [
         'type' => 'array',
         'data' => $values,
     ];

     returnData($returnType, $data, ';');

}


// AUXILIARY FUNCTIONS


/**
 * Auxiliary function to remove an agent from a policy. Used from API methods api_set_remove_agent_from_policy_by_id and api_set_remove_agent_from_policy_by_name.
 *
 * @param int ID of targeted policy.
 * @param boolean If true it will look for the agent we are targeting at based on its agent name, otherwise by agent id.
 * @param array Array containing agent's name or agent's id (and node id in case we are on metaconsole).
 */
function remove_agent_from_policy($id_policy, $use_agent_name, $params)
{
    global $config;

    if (!check_acl($config['id_user'], 0, 'AW')) {
        returnError('forbidden', 'string');
        return;
    }

    $id_node = 0;
    $agent_table = 'tagente';

    if ($use_agent_name === false) {
        $id_agent = $params[0];
    } else {
        $id_agent = db_get_value_filter('id_agente', 'tagente', ['nombre' => $params[0]]);
    }

    $agent = db_get_row_filter('tagente', ['id_agente' => $id_agent]);

    if (is_metaconsole()) {
        if ($use_agent_name === false) {
            $id_node = $params[1];
            $id_agent = $params[0];
        } else {
            $id_node = db_get_value_filter('id_tmetaconsole_setup', 'tmetaconsole_agent', ['nombre' => $params[0]]);
            $id_agent = db_get_value_filter('id_tagente', 'tmetaconsole_agent', ['nombre' => $params[0]]);
        }

        $agent = db_get_row_filter('tmetaconsole_agent', ['id_tagente' => $id_agent, 'id_tmetaconsole_setup' => $id_node]);
    }

    $policy = policies_get_policy($id_policy);

    $policy_agent = (is_metaconsole()) ? db_get_row_filter('tpolicy_agents', ['id_policy' => $id_policy, 'id_agent' => $id_agent, 'id_node' => $id_node]) : db_get_row_filter('tpolicy_agents', ['id_policy' => $id_policy, 'id_agent' => $id_agent]);

    if (empty($policy)) {
        returnError('This policy does not exist.');
        return;
    }

    if (empty($agent)) {
        returnError('This agent does not exist.');
        return;
    }

    if (empty($policy_agent)) {
        returnError('This agent does not exist in this policy.');
        return;
    }

    $return = policies_change_delete_pending_agent($policy_agent['id']);
    $data = __('Successfully added to delete pending id agent %d to id policy %d.', $id_agent, $id_policy);

    if ($return === false) {
        returnError('Could not be deleted id agent %d from id policy %d', $id_agent, $id_policy);
    } else {
        returnData('string', ['type' => 'string', 'data' => $data]);
    }

}


function util_api_check_agent_and_print_error($id_agent, $returnType, $access='AR', $force_meta=false)
{
    global $config;

    $check_agent = agents_check_access_agent($id_agent, $access, $force_meta);
    if ($check_agent === true) {
        return true;
    }

    if ($check_agent === false || !check_acl($config['id_user'], 0, $access)) {
        returnError('forbidden', $returnType);
    } else if ($check_agent === null) {
        returnError('id_not_found', $returnType);
    }

    return false;
}


/**
 * Function for get event id and node id, then we get in return the Metaconsole event ID.
 *
 * @param [string] $server_id        id server (Node)
 * @param [string] $console_event_id console Id node event in tevent
 * @param [string] $trash2           don't use
 * @param [string] $returnType
 *
 * Example
 * api.php?op=get&op2=event_mcid&return_type=json&id=0&id2=0&apipass=1234&user=admin&pass=pandora
 *
 * @return void
 */
function api_get_event_mcid($server_id, $console_event_id, $trash2, $returnType)
{
    try {
        if (is_metaconsole() === true
            && $server_id > 0
        ) {
            $node = new Node($server_id);
            $node->connect();
        }

        // Get grouped comments.
        $mc_event_id = db_get_all_rows_sql(
            sprintf(
                'SELECT id_evento
                FROM tevento
                WHERE id_evento = %d
                ',
                $console_event_id
            )
        );

        if ($mc_event_id !== false) {
            returnData(
                $returnType,
                [
                    'type' => 'string',
                    'data' => $mc_event_id,
                ]
            );
        } else {
            returnError('id_not_found', 'string');
        }
    } catch (\Exception $e) {
        // Unexistent agent.
        if (is_metaconsole() === true
            && $server_id > 0
        ) {
            $node->disconnect();
        }

        returnError('forbidden', 'string');
    } finally {
        if (is_metaconsole() === true
            && $server_id > 0
        ) {
            $node->disconnect();
        }
    }

    return;
}


/**
 * Return whether or not a node is in centralized mode.
 *
 * @param [string] $server_id  server id (node)
 * @param [string] $thrash1    don't use
 * @param [string] $thrash2    don't use
 * @param [string] $returnType
 *
 * Example
 * api.php?op=get&op2=is_centralized&id=3&apipass=1234&user=admin&pass=pandora
 *
 * @return void
 */
function api_get_is_centralized($server_id, $thrash1, $thrash2, $returnType)
{
    if (is_metaconsole() === true) {
        $unified = db_get_value_filter('unified', 'tmetaconsole_setup', ['id' => $server_id]);

        if ($unified !== false) {
            returnData($returnType, ['type' => 'string', 'data' => $unified]);
        } else {
            returnData($returnType, ['type' => 'string', 'data' => 'Node with ID '.$server_id.' does not exist']);
        }
    } else {
        returnData($returnType, ['type' => 'string', 'data' => (int) is_centralized()]);
    }
}


/**
 * Function to set events in progress status.
 *
 * @param [int]    $event_id   Id event (Node or Meta).
 * @param [string] $trash2     don't use.
 * @param [string] $returnType
 *
 * Example
 * http://127.0.0.1/pandora_console/include/api.php?op=set&op2=event_in_progress&return_type=json&id=0&apipass=1234&user=admin&pass=pandora
 *
 * @return void
 */
function api_set_event_in_progress($event_id, $trash2, $returnType)
{
    global $config;
    $event = db_process_sql_update(
        'tevento',
        ['estado' => 2],
        ['id_evento' => $event_id]
    );

    if ($event !== false) {
        returnData('string', ['data' => $event]);
    } else {
        returnError('id_not_found', 'string');
    }
}


/**
 * Enable/Disable discovery task.
 *
 * @param string           $id_task Integer discovery task ID
 * @param $thrash2 not used.
 * @param array            $other   it's array, $other as param is <enable/disable value> in this order and separator char
 *                           (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *                           example:
 *
 *                              example 1 (Enable)
 *
 *                           api.php?op=set&op2=enable_disable_discovery_task&id=1&other=0&other_mode=url_encode_separator_|
 *
 *                              example 2 (Disable)
 *
 *                           api.php?op=set&op2=enable_disable_discovery_task&id=1&other=1&other_mode=url_encode_separator_|
 *
 *              http://localhost/pandora_console/include/api.php?op=set&op2=enable_disable_discovery_task&id=1&other=1&other_mode=url_encode_separator_|&apipass=1234&user=admin&pass=pandora
 */
function api_set_enable_disable_discovery_task($id_task, $thrash2, $other)
{
    global $config;

    if (defined('METACONSOLE')) {
        return;
    }

    if (!check_acl($config['id_user'], 0, 'AW')) {
        returnError('forbidden', 'string');
        return;
    }

    if ($id_task == '') {
        returnError(
            'error_enable_disable_discovery_task',
            __('Error enable/disable discovery task. Id_user cannot be left blank.')
        );
        return;
    }

    if ($other['data'][0] != '0' and $other['data'][0] != '1') {
        returnError(
            'error_enable_disable_discovery_task',
            __('Error enable/disable discovery task. Enable/disable value cannot be left blank.')
        );
        return;
    }

    if ($other['data'][0] == '0') {
        $result = db_process_sql_update(
            'trecon_task',
            [
                'disabled' => $other['data'][0],
                'status'   => 0,
            ],
            ['id_rt' => $id_task]
        );
    } else {
        $result = db_process_sql_update(
            'trecon_task',
            ['disabled' => $other['data'][0]],
            ['id_rt' => $id_task]
        );
    }

    if (is_error($result)) {
        returnError(
            'error_enable_disable_discovery_task',
            __('Error in discovery task enabling/disabling.')
        );
    } else {
        if ($other['data'][0] == '0') {
            returnData(
                'string',
                [
                    'type' => 'string',
                    'data' => __('Enabled discovery task.'),
                ]
            );
        } else {
            returnData(
                'string',
                [
                    'type' => 'string',
                    'data' => __('Disabled discovery task.'),
                ]
            );
        }
    }
}


/**
 * Make report (PDF, CSV or XML) and send it via e-mail (this method is intended to be used by server's execution
 * of alert actions that involve sending reports by e-mail).
 *
 * @param [string] $server_id        id server (Node)
 * @param [string] $console_event_id console Id node event in tevent
 * @param [string] $trash2           don't use
 * @param [string] $returnType
 *
 * --Internal use--
 *
 * @return void
 */
function api_set_send_report($thrash1, $thrash2, $other, $returnType)
{
    global $config;

    $id_item = (int) $other['data'][0];
    $report_type = $other['data'][1];
    $email = $other['data'][2];
    $subject_email = $other['data'][3];
    $body_email = $other['data'][4];
    $make_report_from_template = (bool) $other['data'][5];
    $template_regex_agents = $other['data'][6];

    // Filter normal and metaconsole reports.
    if (is_metaconsole() === true) {
        $filter['metaconsole'] = 1;
    } else {
        $filter['metaconsole'] = 0;
    }

    $own_info = get_user_info($config['id_user']);
    if ($own_info['is_admin'] || check_acl($config['id_user'], 0, 'RM') || check_acl($config['id_user'], 0, 'RR')) {
        $return_all_group = true;
    } else {
        $return_all_group = false;
    }

    if (is_user_admin($config['id_user']) === false) {
        $filter[] = sprintf(
            'private = 0 OR (private = 1 AND id_user = "%s")',
            $config['id_user']
        );
    }

    $date_today = date($config['date_format']);
    $date_today = preg_split('/[\s,]+/', io_safe_output($date_today));
    $date_today = __($date_today[0]).' '.$date_today[1].' '.$date_today[2].' '.$date_today[3].' '.$date_today[4];

    if ($make_report_from_template === true) {
        $filter['id_report'] = $id_item;

        $template = reports_get_report_templates(
            $filter,
            ['description'],
            $return_all_group,
            'RR'
        )[0];

        $description = $template['description'];

        // Report macros post-process.
        $body_email = str_replace(
            [
                '_report_description_',
                '_report_generated_date_',
                '_report_date_',
            ],
            [
                $description,
                $date_today,
                $date_today,
            ],
            $body_email
        );

        $report_type = strtoupper($report_type);
        $body_email = io_safe_output(io_safe_output($body_email));

        cron_task_generate_report_by_template(
            $id_item,
            '',
            $template_regex_agents,
            false,
            '',
            $email,
            $subject_email,
            $body_email,
            $report_type,
            ''
        );
    } else {
        $report = reports_get_report($id_item);

        if ($report === false) {
            // User has no grant to access this report.
            return;
        }

        // Report macros post-process.
        $body_email = str_replace(
            [
                '_report_description_',
                '_report_generated_date_',
                '_report_date_',
            ],
            [
                $report['description'],
                $date_today,
                $date_today,
            ],
            $body_email
        );

        $body_email = io_safe_output(io_safe_output($body_email));

        // Set the languaje of user.
        global $l10n;

        if (isset($l10n) === false) {
            $l10n = null;
            $user_language = get_user_language($config['id_user']);
            if (file_exists(
                $config['homedir'].'/include/languages/'.$user_language.'.mo'
            ) === true
            ) {
                $obj = new CachedFileReader(
                    $config['homedir'].'/include/languages/'.$user_language.'.mo'
                );
                $l10n = new gettext_reader($obj);
                $l10n->load_tables();
            }
        }

        // Attachments.
        $attachments = [];
        // Set the datetime for the report.
        $report['datetime'] = time();

        $date = date('Y-m-j');
        $time = date('h:iA');

        $tmpfile = false;

        switch ($report_type) {
            case 'pdf':
                $tmpfile = $config['homedir'].'/attachment/'.date('Ymd-His').'.pdf';

                $report = reporting_make_reporting_data(
                    null,
                    $id_item,
                    $date,
                    $time,
                    null,
                    'static',
                    null,
                    null,
                    true
                );
                pdf_get_report($report, $tmpfile);

                $attachments[0] = [
                    'file'         => $tmpfile,
                    'content_type' => 'application/pdf',
                ];
            break;

            case 'csv':
                $report = reporting_make_reporting_data(
                    null,
                    $id_item,
                    $date,
                    $time,
                    null,
                    'data'
                );

                $name = explode(' - ', $report['name']);
                $tmpfile = $config['homedir'].'/attachment/'.$name[0].'.csv';

                // Remove unused fields.
                unset($report['header']);
                unset($report['first_page']);
                unset($report['footer']);
                unset($report['custom_font']);
                unset($report['id_template']);
                unset($report['id_group_edit']);
                unset($report['metaconsole']);
                unset($report['private']);
                unset($report['custom_logo']);

                ob_start();
                csv_get_report($report, true);
                $output = ob_get_clean();

                file_put_contents($tmpfile, $output);
                ob_end_clean();

                $attachments[0] = [
                    'file'         => $tmpfile,
                    'content_type' => 'text/csv',
                ];
            break;

            case 'json':
                $report = reporting_make_reporting_data(
                    null,
                    $id_item,
                    $date,
                    $time,
                    null,
                    'data'
                );

                // Remove unused fields.
                unset($report['header']);
                unset($report['first_page']);
                unset($report['footer']);
                unset($report['custom_font']);
                unset($report['id_template']);
                unset($report['id_group_edit']);
                unset($report['metaconsole']);
                unset($report['private']);
                unset($report['custom_logo']);

                $name = explode(' - ', $report['name']);
                $tmpfile = $config['homedir'].'/attachment/'.$name[0].'.json';

                file_put_contents($tmpfile, json_encode($report, JSON_PRETTY_PRINT));

                $attachments[0] = [
                    'file'         => $tmpfile,
                    'content_type' => 'text/json',
                ];
            break;

            case 'xml':
                $report = reporting_make_reporting_data(
                    null,
                    $id_item,
                    $date,
                    $time,
                    null,
                    'data'
                );

                $name = explode(' - ', $report['name']);
                $tmpfile = $config['homedir'].'/attachment/'.$name[0].'.xml';

                // Remove unused fields.
                unset($report['header']);
                unset($report['first_page']);
                unset($report['footer']);
                unset($report['custom_font']);
                unset($report['id_template']);
                unset($report['id_group_edit']);
                unset($report['metaconsole']);
                unset($report['private']);
                unset($report['custom_logo']);

                ob_start();
                reporting_xml_get_report($report, true);
                $output = ob_get_clean();

                file_put_contents($tmpfile, $output);
                ob_end_clean();

                $attachments[0] = [
                    'file'         => $tmpfile,
                    'content_type' => 'text/xml',
                ];
            break;

            default:
            break;
        }

        reporting_email_template(
            $subject_email,
            $body_email,
            '',
            $report['name'],
            $email,
            $attachments
        );

        unlink($other['data'][0]);

        $data = [
            'type' => 'string',
            'data' => '1',
        ];

        returnData($returnType, $data, ';');
    }
}


/**
 * Check if token is correct.
 *
 * @param string $token Token for check.
 *
 * @return mixed Id of user. If returns 0 there is not valid token.
 */
function api_token_check(string $token)
{
    if (empty($token) === true) {
        return 0;
    } else {
        return db_get_value('id_user', 'tusuario', 'api_token', $token);
    }
}
