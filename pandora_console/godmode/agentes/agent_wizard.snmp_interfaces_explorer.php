<?php
/**
 * Extension to manage a list of gateways and the node address where they should
 * point to.
 *
 * @category   SNMP interfaces.
 * @package    Pandora FMS
 * @subpackage Community
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
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

global $config;
require_once $config['homedir'].'/include/functions_agents.php';
require_once 'include/functions_modules.php';
require_once 'include/functions_alerts.php';
require_once 'include/functions_reporting.php';
require_once 'include/graphs/functions_utils.php';


$idAgent = (int) get_parameter('id_agente', 0);
$ipAgent = db_get_value('direccion', 'tagente', 'id_agente', $idAgent);

check_login();
$ip_target = (string) get_parameter('ip_target', $ipAgent);
$use_agent = get_parameter('use_agent');
$snmp_community = (string) get_parameter('snmp_community', 'public');
$server_to_exec = get_parameter('server_to_exec', 0);
$snmp_version = get_parameter('snmp_version', '1');
$snmp3_auth_user = io_safe_output(get_parameter('snmp3_auth_user'));
$snmp3_security_level = get_parameter('snmp3_security_level');
$snmp3_auth_method = get_parameter('snmp3_auth_method');
$snmp3_auth_pass = io_safe_output(get_parameter('snmp3_auth_pass'));
$snmp3_privacy_method = get_parameter('snmp3_privacy_method');
$snmp3_privacy_pass = io_safe_output(get_parameter('snmp3_privacy_pass'));
$tcp_port = (string) get_parameter('tcp_port');

// See if id_agente is set (either POST or GET, otherwise -1.
$id_agent = $idAgent;

// Get passed variables.
$snmpwalk = (int) get_parameter('snmpwalk', 0);
$create_modules = (int) get_parameter('create_modules', 0);

$interfaces = [];
$interfaces_ip = [];

if ($snmpwalk) {
    // OID Used is for SNMP MIB-2 Interfaces.
    $snmpis = get_snmpwalk(
        $ip_target,
        $snmp_version,
        $snmp_community,
        $snmp3_auth_user,
        $snmp3_security_level,
        $snmp3_auth_method,
        $snmp3_auth_pass,
        $snmp3_privacy_method,
        $snmp3_privacy_pass,
        0,
        '.1.3.6.1.2.1.2',
        $tcp_port,
        $server_to_exec
    );
    // IfXTable is also used.
    $ifxitems = get_snmpwalk(
        $ip_target,
        $snmp_version,
        $snmp_community,
        $snmp3_auth_user,
        $snmp3_security_level,
        $snmp3_auth_method,
        $snmp3_auth_pass,
        $snmp3_privacy_method,
        $snmp3_privacy_pass,
        0,
        '.1.3.6.1.2.1.31.1.1',
        $tcp_port,
        $server_to_exec
    );

    // Get the interfaces IPV4/IPV6.
    $snmp_int_ip = get_snmpwalk(
        $ip_target,
        $snmp_version,
        $snmp_community,
        $snmp3_auth_user,
        $snmp3_security_level,
        $snmp3_auth_method,
        $snmp3_auth_pass,
        $snmp3_privacy_method,
        $snmp3_privacy_pass,
        0,
        '.1.3.6.1.2.1.4.34.1.3',
        $tcp_port,
        $server_to_exec
    );

    // Build a [<interface id>] => [<interface ip>] array.
    if (!empty($snmp_int_ip)) {
        foreach ($snmp_int_ip as $key => $value) {
            // The key is something like IP-MIB::ipAddressIfIndex.ipv4."<ip>".
            // or IP-MIB::ipAddressIfIndex.ipv6."<ip>".
            // The value is something like INTEGER: <interface id>.
            $data = explode(': ', $value);
            $interface_id = !empty($data) && isset($data[1]) ? $data[1] : false;

            if (preg_match('/^.+"(.+)"$/', $key, $matches) && isset($matches[1])) {
                $interface_ip = $matches[1];
            }

            // Get the first ip.
            if ($interface_id !== false && !empty($interface_ip) && !isset($interfaces_ip[$interface_id])) {
                $interfaces_ip[$interface_id] = $interface_ip;
            }
        }

        unset($snmp_int_ip);
    }

    $snmpis = array_merge((($snmpis === false) ? [] : $snmpis), (($ifxitems === false) ? [] : $ifxitems));

    $interfaces = [];

    // We get here only the interface part of the MIB, not full mib.
    foreach ($snmpis as $key => $snmp) {
        $data = explode(': ', $snmp, 2);
        $keydata = explode('::', $key);
        $keydata2 = explode('.', $keydata[1]);

        // Avoid results without index and interfaces without name.
        if (!isset($keydata2[1]) || !isset($data[1])) {
            continue;
        }

        if (array_key_exists(1, $data)) {
            $interfaces[$keydata2[1]][$keydata2[0]]['type'] = $data[0];
            $interfaces[$keydata2[1]][$keydata2[0]]['value'] = $data[1];
        } else {
            $interfaces[$keydata2[1]][$keydata2[0]]['type'] = '';
            $interfaces[$keydata2[1]][$keydata2[0]]['value'] = $data[0];
        }

        $interfaces[$keydata2[1]][$keydata2[0]]['oid'] = $key;
        $interfaces[$keydata2[1]][$keydata2[0]]['checked'] = 0;
    }

    unset($interfaces[0]);
}

if ($create_modules) {
    $id_snmp_serialize = get_parameter_post('id_snmp_serialize');
    $interfaces = unserialize_in_temp($id_snmp_serialize);

    $id_snmp_int_ip_serialize = get_parameter_post('id_snmp_int_ip_serialize');
    $interfaces_ip = unserialize_in_temp($id_snmp_int_ip_serialize);

    if (!$interfaces) {
        $interfaces = [];
    }

    if (!$interfaces_ip) {
        $interfaces_ip = [];
    }

    $values = [];

    if ($tcp_port != '') {
        $values['tcp_port'] = $tcp_port;
    }

    $values['snmp_community'] = $snmp_community;
    if ($use_agent) {
        $values['ip_target'] = 'auto';
    } else {
        $values['ip_target'] = $ip_target;
    }

    $values['tcp_send'] = $snmp_version;

    if ($snmp_version == '3') {
        $values['plugin_user'] = $snmp3_auth_user;
        $values['plugin_pass'] = $snmp3_auth_pass;
        $values['plugin_parameter'] = $snmp3_auth_method;
        $values['custom_string_1'] = $snmp3_privacy_method;
        $values['custom_string_2'] = $snmp3_privacy_pass;
        $values['custom_string_3'] = $snmp3_security_level;
    }

    $oids = [];
    foreach ($interfaces as $key => $interface) {
        foreach ($interface as $key2 => $module) {
            $oid = get_parameter($key.'-'.$key2, '');
            if ($oid != '') {
                $interfaces[$key][$key2]['checked'] = 1;
                $oids[$key][] = $interfaces[$key][$key2]['oid'];
            } else {
                $interfaces[$key][$key2]['checked'] = 0;
            }
        }
    }

    $modules = get_parameter('module', []);
    $id_snmp = get_parameter('id_snmp');

    if ($id_snmp == false) {
        ui_print_error_message(__('No modules selected'));
        $id_snmp = [];
    }

    if (agents_get_name($id_agent) == false) {
        ui_print_error_message(__('No agent selected or the agent does not exist'));
        $id_snmp = [];
    }

    $result = false;

    $errors = [];
    $done = 0;

    foreach ($id_snmp as $id) {
        $ifname = '';
        $ifPhysAddress = '';

        if (isset($interfaces[$id]['ifName']) && $interfaces[$id]['ifName']['value'] != '') {
            $ifname = $interfaces[$id]['ifName']['value'];
        } else if (isset($interfaces[$id]['ifDescr']) && $interfaces[$id]['ifDescr']['value'] != '') {
            $ifname = $interfaces[$id]['ifDescr']['value'];
        }

        if (isset($interfaces[$id]['ifPhysAddress']) && $interfaces[$id]['ifPhysAddress']['value'] != '') {
            $ifPhysAddress = $interfaces[$id]['ifPhysAddress']['value'];
            $ifPhysAddress = strtoupper($ifPhysAddress);
        }

        foreach ($modules as $module) {
            $oid_array = explode('.', $module);
            $oid_array[(count($oid_array) - 1)] = $id;
            $oid = implode('.', $oid_array);

            // Get the name.
            $name_array = explode('::', $oid_array[0]);
            $name = $ifname.'_'.$name_array[1];

            // Clean the name.
            $name = str_replace('"', '', $name);

            // Proc moduletypes.
            if (preg_match('/Status/', $name_array[1])) {
                $module_type = 18;
            } else if (preg_match('/Present/', $name_array[1])) {
                $module_type = 18;
            } else if (preg_match('/PromiscuousMode/', $name_array[1])) {
                $module_type = 18;
            } else if (preg_match('/Alias/', $name_array[1])) {
                // String moduletypes.
                $module_type = 17;
            } else if (preg_match('/Address/', $name_array[1])) {
                $module_type = 17;
            } else if (preg_match('/Name/', $name_array[1])) {
                $module_type = 17;
            } else if (preg_match('/Specific/', $name_array[1])) {
                $module_type = 17;
            } else if (preg_match('/Descr/', $name_array[1])) {
                $module_type = 17;
            } else if (preg_match('/s$/', $name_array[1])) {
                // Specific counters (ends in s).
                $module_type = 16;
            } else {
                // Otherwise, numeric.
                $module_type = 15;
            }

            $values['unit'] = '';
            if (preg_match('/Octets/', $name_array[1])) {
                $values['unit'] = 'Bytes';
            }

            $module_server = 2;

            if ($server_to_exec != 0) {
                $sql = sprintf('SELECT server_type, ip_address FROM tserver WHERE id_server = %d', $server_to_exec);
                $row = db_get_row_sql($sql);

                if ($row['server_type'] == 13) {
                    if (preg_match('/ifPhysAddress/', $name_array[1])) {
                        $module_type = 3;
                    } else if (preg_match('/ifSpecific/', $name_array[1])) {
                        $module_type = 3;
                    } else if (preg_match('/ifType/', $name_array[1])) {
                        $module_type = 1;
                    } else if (preg_match('/ifSpeed/', $name_array[1])) {
                        $module_type = 1;
                    } else if (preg_match('/ifPromiscuousMode/', $name_array[1])) {
                        $module_type = 2;
                    } else if (preg_match('/ifOutQLen/', $name_array[1])) {
                        $module_type = 1;
                    } else if (preg_match('/ifName/', $name_array[1])) {
                        $module_type = 3;
                    } else if (preg_match('/ifMtu/', $name_array[1])) {
                        $module_type = 1;
                    } else if (preg_match('/ifLinkUpDownTrapEnable/', $name_array[1])) {
                        $module_type = 1;
                    } else if (preg_match('/ifLastChange/', $name_array[1])) {
                        $module_type = 1;
                    } else if (preg_match('/ifIndex/', $name_array[1])) {
                        $module_type = 1;
                    } else if (preg_match('/ifDescr/', $name_array[1])) {
                        $module_type = 3;
                    } else if (preg_match('/ifCounterDiscontinuityTime/', $name_array[1])) {
                        $module_type = 1;
                    } else if (preg_match('/ifConnectorPresent/', $name_array[1])) {
                        $module_type = 2;
                    } else if (preg_match('/ifAdminStatus/', $name_array[1])) {
                        $module_type = 2;
                    } else if (preg_match('/ifOperStatus/', $name_array[1])) {
                        $module_type = 2;
                    } else {
                        $module_type = 4;
                    }

                    $module_server = 1;

                    $output_oid = '';

                    exec('snmptranslate -On '.$oid, $output_oid, $rc);

                    $conf_oid = $output_oid[0];
                    $oid = $conf_oid;
                }
            }

            $values['id_tipo_modulo'] = $module_type;

            if (!empty($ifPhysAddress) && isset($interfaces_ip[$id])) {
                $values['descripcion'] = io_safe_input('(IP: '.$interfaces_ip[$id].' - MAC: '.$ifPhysAddress.' - '.$name.') '.$interfaces[$id]['ifDescr']['value']);
            } else if (!empty($ifPhysAddress)) {
                $values['descripcion'] = io_safe_input('(MAC: '.$ifPhysAddress.' - '.$name.') '.$interfaces[$id]['ifDescr']['value']);
            } else if (isset($interfaces_ip[$id])) {
                $values['descripcion'] = io_safe_input('(IP: '.$interfaces_ip[$id].' - '.$name.') '.$interfaces[$id]['ifDescr']['value']);
            } else {
                $values['descripcion'] = io_safe_input('('.$name.') '.$interfaces[$id]['ifDescr']['value']);
            }

            $values['snmp_oid'] = $oid;
            $values['id_modulo'] = $module_server;

            $result = modules_create_agent_module($id_agent, io_safe_input($name), $values);

            if (is_error($result)) {
                if (!isset($errors[$result])) {
                    $errors[$result] = 0;
                }

                $errors[$result]++;
            } else {
                if ($server_to_exec != 0) {
                    $sql = sprintf('SELECT server_type FROM tserver WHERE id_server = %d', $server_to_exec);
                    $row = db_get_row_sql($sql);

                    if ($row['server_type'] == 13) {
                        $module_type_name = db_get_value_filter('nombre', 'ttipo_modulo', ['id_tipo' => $values['id_tipo_modulo']]);

                        $new_module_configuration_data = "module_begin\nmodule_name ".io_safe_input($name)."\nmodule_description ".io_safe_output($values['descripcion'])."\nmodule_type ".$module_type_name."\nmodule_snmp\nmodule_version ".$snmp_version."\nmodule_oid ".$conf_oid."\nmodule_community ".$values['snmp_community'];

                        if ($snmp_version == '3') {
                            $new_module_configuration_data .= "\nmodule_secname ".$snmp3_auth_user;
                            $new_module_configuration_data .= "\nmodule_seclevel ".$snmp3_security_level;

                            if ($snmp3_security_level == 'authNoPriv' || $snmp3_security_level == 'authPriv') {
                                $new_module_configuration_data .= "\nmodule_authpass ".$snmp3_auth_pass;
                                $new_module_configuration_data .= "\nmodule_authproto ".$snmp3_auth_method;
                            }

                            if ($snmp3_security_level == 'authPriv') {
                                $new_module_configuration_data .= "\nmodule_privproto ".$snmp3_privacy_method;
                                $new_module_configuration_data .= "\nmodule_privpass ".$snmp3_privacy_pass;
                            }
                        }

                        $new_module_configuration_data .= "\nmodule_end";

                        config_agents_add_module_in_conf($id_agent, $new_module_configuration_data);
                    }
                }

                $done++;
            }
        }
    }

    if ($done > 0) {
        ui_print_success_message(
            __('Successfully modules created').' ('.$done.')'
        );
    }

    if (!empty($errors)) {
        $msg = __('Could not be created').':';


        foreach ($errors as $code => $number) {
            switch ($code) {
                case ERR_EXIST:
                    $msg .= '<br>'.__('Another module already exists with the same name').' ('.$number.')';
                break;

                case ERR_INCOMPLETE:
                    $msg .= '<br>'.__('Some required fields are missed').': ('.__('name').') ('.$number.')';
                break;

                case ERR_DB:
                case ERR_GENERIC:
                default:
                    $msg .= '<br>'.__('Processing error').' ('.$number.')';
                break;
            }
        }

        ui_print_error_message($msg);
    }
}

// Create the interface list for the interface.
$interfaces_list = [];
foreach ($interfaces as $interface) {
    // Get the interface name, removing " " characters and avoid "blank" interfaces.
    if (isset($interface['ifDescr']) && $interface['ifDescr']['value'] != '') {
        $ifname = $interface['ifDescr']['value'];
    } else if (isset($interface['ifName']) && $interface['ifName']['value'] != '') {
        $ifname = $interface['ifName']['value'];
    } else {
        continue;
    }

    $interfaces_list[$interface['ifIndex']['value']] = str_replace('"', '', $ifname);
}

echo '<span id ="none_text" style="display: none;">'.__('None').'</span>';
echo "<form method='post' id='walk_form' action='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=agent_wizard&wizard_section=snmp_interfaces_explorer&id_agente=".$id_agent."'>";

$table->width = '100%';
$table->cellpadding = 0;
$table->cellspacing = 0;
$table->class = 'databox filters';

$table->data[0][0] = '<b>'.__('Target IP').'</b>';
$table->data[0][1] = html_print_input_text('ip_target', $ip_target, '', 15, 60, true);

$table->data[0][2] = '<b>'.__('Port').'</b>';
$table->data[0][3] = html_print_input_text('tcp_port', $tcp_port, '', 5, 20, true);

$table->data[1][0] = '<b>'.__('Use agent ip').'</b>';
$table->data[1][1] = html_print_checkbox('use_agent', 1, $use_agent, true);

$servers_to_exec = [];
$servers_to_exec[0] = __('Local console');
if (enterprise_installed()) {
    enterprise_include_once('include/functions_satellite.php');

    $rows = get_proxy_servers();

    // Check if satellite server has remote configuration enabled.
    $satellite_remote = config_agents_has_remote_configuration($id_agent);

    foreach ($rows as $row) {
        if ($row['server_type'] != 13) {
            $s_type = ' (Standard)';
        } else {
            $id_satellite = $row['id_server'];
            $s_type = ' (Satellite)';
        }

        $servers_to_exec[$row['id_server']] = $row['name'].$s_type;
    }
}

$table->data[1][2] = '<b>'.__('Server to execute command').'</b>';
$table->data[1][2] .= '<span id=satellite_remote_tip>'.ui_print_help_tip(__('In order to use remote executions you need to enable remote execution in satellite server'), true, 'images/tip_help.png', false, 'display:').'</span>';
$table->data[1][4] = html_print_select(
    $servers_to_exec,
    'server_to_exec',
    $server_to_exec,
    'satellite_remote_warn('.$id_satellite.','.$satellite_remote.')',
    '',
    '',
    true
);

$snmp_versions['1'] = 'v. 1';
$snmp_versions['2'] = 'v. 2';
$snmp_versions['2c'] = 'v. 2c';
$snmp_versions['3'] = 'v. 3';

$table->data[2][0] = '<b>'.__('SNMP community').'</b>';
$table->data[2][1] = html_print_input_text('snmp_community', $snmp_community, '', 15, 60, true);

$table->data[2][2] = '<b>'.__('SNMP version').'</b>';
$table->data[2][3] = html_print_select($snmp_versions, 'snmp_version', $snmp_version, '', '', '', true, false, false, '');

$table->data[2][3] .= '<div id="spinner_modules" style="float: left; display: none;">'.html_print_image('images/spinner.gif', true).'</div>';
html_print_input_hidden('snmpwalk', 1);

html_print_table($table);

unset($table);

// SNMP3 OPTIONS.
$table->width = '100%';

$table->data[2][1] = '<b>'.__('Auth user').'</b>';
$table->data[2][2] = html_print_input_text('snmp3_auth_user', $snmp3_auth_user, '', 15, 60, true);
$table->data[2][3] = '<b>'.__('Auth password').'</b>';
$table->data[2][4] = html_print_input_password('snmp3_auth_pass', $snmp3_auth_pass, '', 15, 60, true);
$table->data[2][4] .= html_print_input_hidden_extended('active_snmp_v3', 0, 'active_snmp_v3_awsie', true);

$table->data[5][0] = '<b>'.__('Privacy method').'</b>';
$table->data[5][1] = html_print_select(['DES' => __('DES'), 'AES' => __('AES')], 'snmp3_privacy_method', $snmp3_privacy_method, '', '', '', true);
$table->data[5][2] = '<b>'.__('privacy pass').'</b>';
$table->data[5][3] = html_print_input_password('snmp3_privacy_pass', $snmp3_privacy_pass, '', 15, 60, true);

$table->data[6][0] = '<b>'.__('Auth method').'</b>';
$table->data[6][1] = html_print_select(['MD5' => __('MD5'), 'SHA' => __('SHA')], 'snmp3_auth_method', $snmp3_auth_method, '', '', '', true);
$table->data[6][2] = '<b>'.__('Security level').'</b>';
$table->data[6][3] = html_print_select(
    [
        'noAuthNoPriv' => __('Not auth and not privacy method'),
        'authNoPriv'   => __('Auth and not privacy method'),
        'authPriv'     => __('Auth and privacy method'),
    ],
    'snmp3_security_level',
    $snmp3_security_level,
    '',
    '',
    '',
    true
);

if ($snmp_version == 3) {
    echo '<div id="snmp3_options">';
} else {
    echo '<div id="snmp3_options" style="display: none;">';
}

html_print_table($table);
echo '</div>';

echo "<div style='text-align:right; width:".$table->width."'>";
echo '<span id="oid_loading" class="invisible">'.html_print_image('images/spinner.gif', true).'</span>';
html_print_submit_button(__('SNMP Walk'), 'snmp_walk', false, ['class' => 'sub next']);
echo '</div>';

if ($snmpwalk && !$snmpis) {
    ui_print_error_message(__('Unable to do SNMP walk'));
}

unset($table);

echo '</form>';

if (!empty($interfaces_list)) {
    echo '<span id ="none_text" style="display: none;">'.__('None').'</span>';
    echo "<form method='post' action='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=agent_wizard&wizard_section=snmp_interfaces_explorer&id_agente=".$id_agent."'>";
    echo '<span id="form_interfaces">';

    $id_snmp_serialize = serialize_in_temp($interfaces, $config['id_user'].'_snmp');
    html_print_input_hidden('id_snmp_serialize', $id_snmp_serialize);

    $id_snmp_int_ip_serialize = serialize_in_temp($interfaces_ip, $config['id_user'].'_snmp_int_ip');
    html_print_input_hidden('id_snmp_int_ip_serialize', $id_snmp_int_ip_serialize);

    html_print_input_hidden('create_modules', 1);
    html_print_input_hidden('ip_target', $ip_target);
    html_print_input_hidden('use_agent', $use_agent);
    html_print_input_hidden('tcp_port', $tcp_port);
    html_print_input_hidden('snmp_community', $snmp_community);
    html_print_input_hidden('snmp_version', $snmp_version);
    html_print_input_hidden('snmp3_auth_user', $snmp3_auth_user);
    html_print_input_hidden('snmp3_auth_pass', $snmp3_auth_pass);
    html_print_input_hidden('snmp3_auth_method', $snmp3_auth_method);
    html_print_input_hidden('snmp3_privacy_method', $snmp3_privacy_method);
    html_print_input_hidden('snmp3_privacy_pass', $snmp3_privacy_pass);
    html_print_input_hidden('snmp3_security_level', $snmp3_security_level);
    html_print_input_hidden('server_to_exec', $server_to_exec);

    $table->width = '100%';

    // Agent selector.
    $table->data[0][0] = '<b>'.__('Interfaces').'</b>';
    $table->data[0][1] = '';
    $table->data[0][2] = '<b>'.__('Modules').'</b>';

    $table->data[1][0] = html_print_select($interfaces_list, 'id_snmp[]', 0, false, '', '', true, true, true, '', false, 'width:500px; overflow: auto;');

    $table->data[1][1] = __('When selecting interfaces');
    $table->data[1][1] .= '<br>';
    $table->data[1][1] .= html_print_select(
        [
            1 => __('Show common modules'),
            0 => __('Show all modules'),
        ],
        'modules_selection_mode',
        1,
        false,
        '',
        '',
        true,
        false,
        false
    );

    $table->data[1][2] = html_print_select([], 'module[]', 0, false, '', 0, true, true, true, '', false, 'width:200px;');
    $table->data[1][2] .= html_print_input_hidden('agent', $id_agent, true);

    html_print_table($table);

    echo "<div style='text-align:right; width:".$table->width."'>";
    html_print_submit_button(__('Create modules'), '', false, ['class' => 'sub add']);
    echo '</div>';
    unset($table);

    echo '</span>';
    echo '</form>';
    echo '</div>';
}

ui_require_jquery_file('pandora.controls');
ui_require_jquery_file('ajaxqueue');
ui_require_jquery_file('bgiframe');
?>
<script language="javascript" type="text/javascript">
/* <![CDATA[ */

$(document).ready (function () {
    var inputActive = true;

    $('#server_to_exec option').trigger('change');

    $(document).data('text_for_module', $("#none_text").html());

    $("#id_snmp").change(snmp_changed_by_multiple_snmp);

    $("#snmp_version").change(function () {
        if (this.value == "3") {
            $("#snmp3_options").css("display", "");
        }
        else {
            $("#snmp3_options").css("display", "none");
        }
    });

    $("#walk_form").submit(function() {
        $("#submit-snmp_walk").disable ();
        $("#oid_loading").show ();
        $("#no_snmp").hide ();
        $("#form_interfaces").hide ();
    });

    // When select interfaces changes
    $("#modules_selection_mode").change (function() {
        $("#id_snmp").trigger('change');
    });

});

function snmp_changed_by_multiple_snmp (event, id_snmp, selected) {
    var idSNMP = Array();
    var get_common_modules = $("#modules_selection_mode option:selected").val();

    jQuery.each ($("#id_snmp option:selected"), function (i, val) {
        idSNMP.push($(val).val());
    });
    $('#module').attr ('disabled', 1);
    $('#module').empty ();
    $('#module').append ($('<option></option>').html ("Loading...").attr ("value", 0));

    jQuery.post ('ajax.php',
        {"page" : "godmode/agentes/agent_manager",
            "get_modules_json_for_multiple_snmp": 1,
            "get_common_modules" : get_common_modules,
            "id_snmp[]": idSNMP,
            "id_snmp_serialize": $("#hidden-id_snmp_serialize").val()
        },
        function (data) {
            $('#module').empty ();
            c = 0;
            jQuery.each (data, function (i, val) {
                s = js_html_entity_decode(val);
                $('#module').append ($('<option></option>').html (s).attr ("value", i));
                $('#module').fadeIn ('normal');
                c++;
                });

            if (c == 0) {
                if (typeof($(document).data('text_for_module')) != 'undefined') {
                    $('#module').append ($('<option></option>').html ($(document).data('text_for_module')).attr("value", 0).prop('selected', true));
                }
                else {
                    if (typeof(data['any_text']) != 'undefined') {
                        $('#module').append ($('<option></option>').html (data['any_text']).attr ("value", 0).prop('selected', true));
                    }
                    else {
                        var anyText = $("#any_text").html(); //Trick for catch the translate text.

                        if (anyText == null) {
                            anyText = 'Any';
                        }

                        $('#module').append ($('<option></option>').html (anyText).attr ("value", 0).prop('selected', true));
                    }
                }
            }
            if (selected != undefined)
                $('#module').attr ('value', selected);
            $('#module').removeAttr('disabled');
        },
        "json");
}


function satellite_remote_warn(id_satellite, remote)
{
    if(!remote)
    {
        $('#server_to_exec option[value='+id_satellite+']').prop('disabled', true);
        $('#satellite_remote_tip').removeAttr("style").show();
    }
    else
    {
        $('#satellite_remote_tip').removeAttr("style").hide();
    }

}

/* ]]> */
</script>
