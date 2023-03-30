<?php
/**
 * Extension to manage a list of gateways and the node address where they should
 * point to.
 *
 * @category   Modules
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

require_once 'include/functions_network_components.php';
enterprise_include_once('include/functions_local_components.php');

if (is_ajax()) {
    $get_network_component = (bool) get_parameter('get_network_component');
    $snmp_walk = (bool) get_parameter('snmp_walk');
    $get_module_component = (bool) get_parameter('get_module_component');
    $get_module_components = (bool) get_parameter('get_module_components');
    $get_module_local_components = (bool) get_parameter(
        'get_module_local_components'
    );
    $get_module_local_component = (bool) get_parameter(
        'get_module_local_component'
    );

    if ($get_module_component) {
        $id_component = (int) get_parameter('id_module_component');

        $component = db_get_row('tnetwork_component', 'id_nc', $id_component);

        $component['throw_unknown_events'] = network_components_is_disable_type_event(
            $id_component,
            EVENTS_GOING_UNKNOWN
        );

        // Decrypt passwords in the component.
        $component['plugin_pass'] = io_output_password(
            $component['plugin_pass']
        );

        if ($component['type'] >= 15
            && $component['type'] <= 18
        ) {
            // New support for snmp v3.
            $component['snmp_version'] = $component['tcp_send'];
            $component['snmp3_auth_user'] = io_safe_output(
                $component['plugin_user']
            );
            // Must use io_output_password.
            $component['snmp3_auth_pass'] = io_safe_output(
                $component['plugin_pass']
            );
            $component['snmp3_auth_method'] = io_safe_output(
                $component['plugin_parameter']
            );
            $component['snmp3_privacy_method'] = io_safe_output(
                $component['custom_string_1']
            );
            $component['snmp3_privacy_pass'] = io_safe_output(
                $component['custom_string_2']
            );
            $component['snmp3_security_level'] = io_safe_output(
                $component['custom_string_3']
            );
        } else if ($component['type'] >= 34
            && $component['type'] <= 37
        ) {
            $component['command_text'] = io_safe_output(
                $component['tcp_send']
            );
            $component['command_credential_identifier'] = io_safe_output(
                $component['custom_string_1']
            );
            $component['command_os'] = io_safe_output(
                $component['custom_string_2']
            );
        }

        $component['str_warning'] = io_safe_output($component['str_warning']);
        $component['str_critical'] = io_safe_output($component['str_critical']);
        $component['warning_inverse'] = (bool) $component['warning_inverse'];
        $component['critical_inverse'] = (bool) $component['critical_inverse'];
        $component['percentage_warning'] = (bool) $component['percentage_warning'];
        $component['percentage_critical'] = (bool) $component['percentage_critical'];


        echo io_json_mb_encode($component);
        return;
    }

    if ($get_module_components) {
        include_once 'include/functions_modules.php';
        $id_module_group = (int) get_parameter('id_module_component_group');
        $id_module_component = (int) get_parameter('id_module_component_type');

        $components = network_components_get_network_components(
            $id_module_component,
            [
                'id_group' => $id_module_group,
                'order'    => 'name ASC',
            ],
            [
                'id_nc',
                'name',
            ]
        );

        echo io_json_mb_encode($components);
        return;
    }

    if ($get_module_local_components) {
        include_once $config['homedir'].'/'.ENTERPRISE_DIR.'/include/functions_local_components.php';

        $id_module_group = (int) get_parameter('id_module_component_group');
        $localComponents = local_components_get_local_components(
            ['id_network_component_group' => $id_module_group],
            [
                'id',
                'name',
            ]
        );

        echo io_json_mb_encode($localComponents);
        return;
    }

    if ($get_module_local_component) {
        $id_component = (int) get_parameter('id_module_component');

        $component = db_get_row('tlocal_component', 'id', $id_component);
        foreach ($component as $index => $element) {
            $component[$index] = html_entity_decode(
                $element,
                ENT_QUOTES,
                'UTF-8'
            );
        }

        $typeName = local_components_parse_module_extract_value(
            'module_type',
            $component['data']
        );

        $component['type'] = db_get_value_sql(
            '
            SELECT id_tipo
            FROM ttipo_modulo
            WHERE nombre LIKE "'.$typeName.'"'
        );

        $component['throw_unknown_events'] = !local_components_is_disable_type_event(
            $id_component,
            EVENTS_GOING_UNKNOWN
        );

        echo io_json_mb_encode($component);
        return;
    }

    if ($snmp_walk) {
        $test_ip_type = get_parameter('ip_target');
        if (is_array($test_ip_type)) {
            $ip_target = (string) array_shift($test_ip_type);
        } else {
            $ip_target = (string) get_parameter('ip_target');
        }

        $test_snmp_community = get_parameter('snmp_community');
        if (is_array($test_snmp_community)) {
            $snmp_community = (string) array_shift($test_snmp_community);
        } else {
            $snmp_community = (string) get_parameter('snmp_community');
        }

        $snmp_version = get_parameter('snmp_version');
        $snmp3_auth_user = io_safe_output(get_parameter('snmp3_auth_user'));
        $snmp3_security_level = get_parameter('snmp3_security_level');
        $snmp3_auth_method = get_parameter('snmp3_auth_method');
        $snmp3_auth_pass = io_safe_output(get_parameter('snmp3_auth_pass'));
        $snmp3_privacy_method = get_parameter('snmp3_privacy_method');
        $snmp3_privacy_pass = io_safe_output(
            get_parameter('snmp3_privacy_pass')
        );
        $snmp_port = get_parameter('snmp_port');

        $snmpwalk = get_snmpwalk(
            $ip_target,
            $snmp_version,
            $snmp_community,
            $snmp3_auth_user,
            $snmp3_security_level,
            $snmp3_auth_method,
            $snmp3_auth_pass,
            $snmp3_privacy_method,
            $snmp3_privacy_pass,
            1,
            '',
            $snmp_port
        );

        if ($snmpwalk === false) {
            echo io_json_mb_encode($snmpwalk);
            return;
        }

        $result = [];
        foreach ($snmpwalk as $id => $value) {
            $value = substr($id, 0, 35).' - '.substr($value, 0, 20);
            $result[$id] = substr($value, 0, 55);
        }

        asort($result);
        echo io_json_mb_encode($result);
        return;
    }

    return;
}

require_once 'include/functions_exportserver.php';
require_once $config['homedir'].'/include/functions_modules.php';
require_once $config['homedir'].'/include/functions_agents.php';

// Reading a module.
if ($id_agent_module) {
    $module = modules_get_agentmodule($id_agent_module);
    $moduletype = $module['id_modulo'];
    $name = $module['nombre'];
    $description = $module['descripcion'];
    $id_module_group = $module['id_module_group'];
    $id_module_type = $module['id_tipo_modulo'];
    $max = $module['max'];
    $min = $module['min'];
    $interval = $module['module_interval'];
    if ($interval == 0) {
        $interval = agents_get_interval($id_agente);
    }

    $ff_interval = $module['module_ff_interval'];
    $quiet_module = $module['quiet'];
    $cps_module = $module['cps'];
    $unit = $module['unit'];
    $tcp_port = $module['tcp_port'];
    $tcp_send = $module['tcp_send'];
    $tcp_rcv = $module['tcp_rcv'];
    $snmp_community = $module['snmp_community'];
    $snmp_oid = $module['snmp_oid'];

    // New support for snmp v3.
    $snmp_version = $module['tcp_send'];
    $snmp3_auth_user = $module['plugin_user'];
    $snmp3_auth_pass = io_output_password($module['plugin_pass']);

    // Auth method could be MD5 or SHA.
    $snmp3_auth_method = $module['plugin_parameter'];

    // Privacy method could be DES or AES.
    $snmp3_privacy_method = $module['custom_string_1'];
    $snmp3_privacy_pass = io_output_password($module['custom_string_2']);

    // For Remote cmd fields are reused:
    // tcp_send, custom_string_1, custom_string_2.
    $command_text = $module['tcp_send'];
    $command_credential_identifier = $module['custom_string_1'];
    $command_os = $module['custom_string_2'];

    // Security level Could be noAuthNoPriv | authNoPriv | authPriv.
    $snmp3_security_level = $module['custom_string_3'];
    $ip_target = $module['ip_target'];
    $disabled = $module['disabled'];
    $id_export = $module['id_export'];
    $plugin_user = $module['plugin_user'];
    $plugin_pass = io_output_password($module['plugin_pass']);
    $plugin_parameter = $module['plugin_parameter'];
    $id_plugin = $module['id_plugin'];
    $post_process = $module['post_process'];
    $prediction_module = $module['prediction_module'];
    $custom_integer_1 = $module['custom_integer_1'];
    $custom_integer_2 = $module['custom_integer_2'];
    $custom_string_1 = $module['custom_string_1'];
    $custom_string_2 = $module['custom_string_2'];
    $custom_string_3 = $module['custom_string_3'];
    $max_timeout = $module['max_timeout'];
    $max_retries = $module['max_retries'];
    $custom_id = $module['custom_id'];
    $history_data = $module['history_data'];
    $dynamic_interval = $module['dynamic_interval'];
    $dynamic_max = $module['dynamic_max'];
    $dynamic_min = $module['dynamic_min'];
    $parent_module_id = $module['parent_module_id'];
    $dynamic_two_tailed = $module['dynamic_two_tailed'];
    $min_warning = $module['min_warning'];
    $max_warning = $module['max_warning'];
    $str_warning = $module['str_warning'];
    $min_critical = $module['min_critical'];
    $max_critical = $module['max_critical'];
    $str_critical = $module['str_critical'];
    $ff_event = $module['min_ff_event'];
    $ff_event_normal = $module['min_ff_event_normal'];
    $ff_event_warning = $module['min_ff_event_warning'];
    $ff_event_critical = $module['min_ff_event_critical'];
    $ff_type = $module['ff_type'];
    $each_ff = $module['each_ff'];
    $ff_timeout = $module['ff_timeout'];
    $warning_time = $module['warning_time'];
    // Select tag info.
    $id_tag = tags_get_module_tags($id_agent_module);

    $critical_instructions = $module['critical_instructions'];
    $warning_instructions = $module['warning_instructions'];
    $unknown_instructions = $module['unknown_instructions'];

    $critical_inverse = $module['critical_inverse'];
    $warning_inverse = $module['warning_inverse'];
    $percentage_critical = $module['percentage_critical'];
    $percentage_warning = $module['percentage_warning'];


    $id_category = $module['id_category'];

    $cron_interval = explode(' ', $module['cron_interval']);
    if (isset($cron_interval[4]) === true) {
        $minute_from = $cron_interval[0];
        $minute = explode('-', $minute_from);
        $minute_from = $minute[0];
        if (isset($minute[1]) === true) {
            $minute_to = $minute[1];
        }

        $hour_from = $cron_interval[1];
        $h = explode('-', $hour_from);
        $hour_from = $h[0];
        if (isset($h[1]) === true) {
            $hour_to = $h[1];
        }

        $mday_from = $cron_interval[2];
        $md = explode('-', $mday_from);
        $mday_from = $md[0];
        if (isset($md[1]) === true) {
            $mday_to = $md[1];
        }

        $month_from = $cron_interval[3];
        $m = explode('-', $month_from);
        $month_from = $m[0];
        if (isset($m[1]) === true) {
            $month_to = $m[1];
        }

        $wday_from = $cron_interval[4];
        $wd = explode('-', $wday_from);
        $wday_from = $wd[0];
        if (isset($wd[1]) === true) {
            $wday_to = $wd[1];
        }
    } else {
        $minute_from = '*';
        $hour_from = '*';
        $mday_from = '*';
        $month_from = '*';
        $wday_from = '*';

        $minute_to = '*';
        $hour_to = '*';
        $mday_to = '*';
        $month_to = '*';
        $wday_to = '*';
    }

    $module_macros = null;
    if (isset($module['module_macros']) === true) {
        $module_macros = json_decode(
            base64_decode($module['module_macros']),
            true
        );
    }
} else {
    if (isset($moduletype) === false || $moduletype === 0) {
        $moduletype = (string) get_parameter('moduletype');
        if ((bool) $_SESSION['create_module'] === true && (bool) $config['welcome_state'] === true) {
            $moduletype = 'networkserver';
        }

        // Clean up specific network modules fields.
        $name = '';
        $description = '';
        $id_module_group = 1;
        $id_module_type = 1;
        $post_process = '';
        $max_timeout = 0;
        $max_retries = 0;
        $min = '';
        $max = '';
        $interval = '';
        $quiet_module = 0;
        $cps_module = 0;
        $unit = '';
        $prediction_module = '';
        $custom_integer_1 = 0;
        $custom_integer_2 = 0;
        $custom_string_1 = '';
        $custom_string_2 = '';
        $id_plugin = '';
        $id_export = '';
        $disabled = '0';
        $tcp_send = '';
        $tcp_rcv = '';
        $tcp_port = '';

        if ($moduletype == 'wmiserver') {
            $snmp_community = '';
        } else {
            $snmp_community = 'public';
        }

        $snmp_oid = '';
        $ip_target = agents_get_address($id_agente);
        $plugin_user = '';
        $plugin_pass = '';
        $plugin_parameter = '';
        $custom_id = '';
        $history_data = 1;
        $dynamic_interval = 0;
        $dynamic_min = 0;
        $dynamic_max = 0;
        $parent_module_id = 0;
        $dynamic_two_tailed = 0;
        $min_warning = 0;
        $max_warning = 0;
        $str_warning = '';
        $min_critical = 0;
        $max_critical = 0;
        $str_critical = '';
        $ff_event = 0;
        $warning_time = 0;

        // New support for snmp v3.
        $snmp_version = 1;
        $snmp3_auth_user = '';
        $snmp3_auth_pass = '';
        $snmp3_auth_method = '';
        $snmp3_privacy_method = '';
        $snmp3_privacy_pass = '';
        $snmp3_security_level = '';

        // For Remote CMD.
        $command_text = '';
        $command_credential_identifier = '';
        $command_os = '';

        $critical_instructions = '';
        $warning_instructions = '';
        $unknown_instructions = '';

        $critical_inverse = '';
        $warning_inverse = '';
        $percentage_critical = '';
        $percentage_warning = '';

        $each_ff = 0;
        $ff_event_normal = '';
        $ff_event_warning = '';
        $ff_event_critical = '';
        $ff_type = 0;

        $id_category = 0;

        $cron_interval = '* * * * *';
        $hour_from = '*';
        $minute_from = '*';
        $mday_from = '*';
        $month_from = '*';
        $wday_from = '*';
        $hour_to = '*';
        $minute_to = '*';
        $mday_to = '*';
        $month_to = '*';
        $wday_to = '*';

        $ff_interval = 0;

        $ff_timeout = 0;

        $module_macros = [];
    }

    $create_network_from_snmp_browser = get_parameter('create_network_from_snmp_browser', 0);

    if ($create_network_from_snmp_browser) {
        $moduletype = get_parameter('id_component_type', 2);
        $id_module_type = get_parameter('type', 1);
        $name = get_parameter('name', '');
        $description = get_parameter('description');
        $ip_target = get_parameter('target_ip');
        $snmp_community = get_parameter('community');
        $snmp_version = get_parameter('snmp_version');
        $snmp3_auth_user = get_parameter('snmp3_auth_user');
        $snmp3_auth_pass = get_parameter('snmp3_auth_pass');
        $snmp3_auth_method = get_parameter('snmp3_auth_method');
        $snmp3_privacy_method = get_parameter('snmp3_privacy_method');
        $snmp3_privacy_pass = get_parameter('snmp3_privacy_pass');
        $snmp3_security_level = get_parameter('snmp3_security_level');
        $snmp_oid = get_parameter('snmp_oid');
    }
}

$is_function_policies = enterprise_include_once(
    'include/functions_policies.php'
);

if ($is_function_policies !== ENTERPRISE_NOT_HOOK) {
    $relink_policy = get_parameter('relink_policy', 0);
    $unlink_policy = get_parameter('unlink_policy', 0);

    if ($relink_policy) {
        $policy_info = policies_info_module_policy($id_agent_module);
        $policy_id = $policy_info['id_policy'];

        if ($relink_policy
            && policies_get_policy_queue_status($policy_id) == STATUS_IN_QUEUE_APPLYING
        ) {
            ui_print_error_message(
                __('This policy is applying and cannot be modified')
            );
        } else {
            $result = policies_relink_module($id_agent_module);
            ui_print_result_message(
                $result,
                __('Module will be linked in the next application')
            );

            db_pandora_audit(
                AUDIT_LOG_AGENT_MANAGEMENT,
                'Re-link module '.$id_agent_module
            );
        }
    }

    if ($unlink_policy) {
        $result = policies_unlink_module($id_agent_module);
        ui_print_result_message(
            $result,
            __('Module will be unlinked in the next application')
        );

        db_pandora_audit(
            AUDIT_LOG_AGENT_MANAGEMENT,
            'Unlink module '.$id_agent_module
        );
    }
}

global $__code_from;
$__code_from = 'modules';
$remote_conf = false;

if ($__code_from !== 'policies') {
    // Only check in the module editor.
    // Check ACL tags.
    $tag_acl = true;

    // If edit a existing module.
    if (empty($id_agent_module) === false) {
        $tag_acl = tags_check_acl_by_module($id_agent_module);
    }

    if ($tag_acl !== true) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access agent manager'
        );
        include 'general/noaccess.php';
        return;
    }
}

switch ($moduletype) {
    case 'dataserver':
    case MODULE_DATA:
        $moduletype = MODULE_DATA;
        // Has remote configuration ?
        $remote_conf = false;
        if (enterprise_installed() === true) {
            enterprise_include_once('include/functions_config_agents.php');
            $remote_conf = (bool) enterprise_hook(
                'config_agents_has_remote_configuration',
                [$id_agente]
            );
        }

        $categories = [
            0,
            1,
            2,
            6,
            7,
            8,
            -1,
        ];
        include 'module_manager_editor_common.php';
        include 'module_manager_editor_data.php';
        if ((bool) $config['enterprise_installed'] === true && $remote_conf === true) {
            if ($id_agent_module) {
                enterprise_include_once('include/functions_config_agents.php');
                $configuration_data = enterprise_hook(
                    'config_agents_get_module_from_conf',
                    [
                        $id_agente,
                        io_safe_output(
                            modules_get_agentmodule_name($id_agent_module)
                        ),
                    ]
                );
            }

            enterprise_include(
                'godmode/agentes/module_manager_editor_data.php'
            );
        }
    break;

    case 'networkserver':
    case MODULE_NETWORK:
        $moduletype = MODULE_NETWORK;
        $categories = [
            3,
            4,
            5,
        ];
        if (enterprise_installed() === true) {
            $categories[] = 10;
        }

        include 'module_manager_editor_common.php';
        include 'module_manager_editor_network.php';
    break;

    case 'pluginserver':
    case MODULE_PLUGIN:
        $moduletype = MODULE_PLUGIN;

        $categories = [
            0,
            1,
            2,
        ];
        include 'module_manager_editor_common.php';
        include 'module_manager_editor_plugin.php';
    break;

    case 'predictionserver':
    case MODULE_PREDICTION:
        $moduletype = MODULE_PREDICTION;

        $categories = [
            0,
            1,
        ];
        include 'module_manager_editor_common.php';
        include 'module_manager_editor_prediction.php';
    break;

    case 'wmiserver':
    case MODULE_WMI:
        $moduletype = MODULE_WMI;

        $categories = [
            0,
            1,
            2,
        ];
        include 'module_manager_editor_common.php';
        include 'module_manager_editor_wmi.php';
    break;

    case 'webserver':
    case MODULE_WEB:
        $moduletype = MODULE_WEB;
        // Remove content of $ip_target when it is ip_agent because
        // it is used as HTTP auth (server) ....ONLY IN NEW MODULE!!!
        if (empty($id_agent_module) === true
            && ($ip_target === agents_get_address($id_agente))
        ) {
            $ip_target = '';
        }

        $categories = [9];
        include 'module_manager_editor_common.php';
        include 'module_manager_editor_web.php';
    break;

    // WARNING: type 7 is reserved on enterprise.
    default:
        if (enterprise_include(
            'godmode/agentes/module_manager_editor.php'
        ) === ENTERPRISE_NOT_HOOK
        ) {
            ui_print_error_message(sprintf(__('Invalid module type')));
            return;
        }
    break;
}


if ((bool) $config['enterprise_installed'] === true && $id_agent_module) {
    if (policies_is_module_in_policy($id_agent_module) === true) {
        policies_add_policy_linkation($id_agent_module);
    }
}

echo '<h3 id="message" class="error invisible"></h3>';

// TODO: Change to the ui_print_error system.
$outputForm = '<form method="post" id="module_form">';
$outputForm .= ui_toggle(
    html_print_table($table_simple, true),
    '<span class="subsection_header_title">'.__('Base options').'</span>',
    '',
    '',
    false,
    true,
    '',
    'white-box-content no_border',
    'filter-datatable-main box-flat white_table_graph'
);

$outputForm .= ui_toggle(
    html_print_table($table_advanced, true),
    '<span class="subsection_header_title">'.__('Advanced options').'</span>',
    '',
    '',
    true,
    true,
    '',
    'white-box-content no_border',
    'filter-datatable-main box-flat white_table_graph'
);

$outputForm .= ui_toggle(
    html_print_table($table_macros, true),
    '<span class="subsection_header_title">'.__('Custom macros').'</span>',
    '',
    '',
    true,
    true,
    '',
    'white-box-content no_border',
    'filter-datatable-main box-flat white_table_graph'
);

if ((int) $moduletype !== 13) {
    $outputForm .= ui_toggle(
        html_print_table(
            $table_new_relations,
            true
        ).html_print_table(
            $table_relations,
            true
        ),
        '<span class="subsection_header_title">'.__('Module relations').'<span>',
        '',
        '',
        true,
        true,
        '',
        'white-box-content no_border',
        'filter-datatable-main box-flat white_table_graph'
    );
}

// Submit.
if ($id_agent_module) {
    $actionButtons = html_print_submit_button(
        __('Update'),
        'updbutton',
        false,
        [ 'icon' => 'update' ],
        true
    );
    $actionButtons .= html_print_button(
        __('Delete'),
        'deleteModule',
        false,
        'window.location.assign("index.php?sec=gagente&tab=module&sec2=godmode/agentes/configurar_agente&id_agente='.$id_agente.'&delete_module='.$id_agent_module.'")',
        [
            'icon' => 'delete',
            'mode' => 'secondary',
        ],
        true
    );
    $actionButtons .= html_print_input_hidden('update_module', 1, true);
    $actionButtons .= html_print_input_hidden('id_agent_module', $id_agent_module, true);
    $actionButtons .= html_print_input_hidden('id_module_type', $id_module_type, true);
} else {
    $actionButtons = html_print_submit_button(
        __('Create'),
        'crtbutton',
        false,
        [ 'icon' => 'wand' ],
        true
    );

    $actionButtons .= html_print_input_hidden('id_module', $moduletype, true);
    $actionButtons .= html_print_input_hidden('create_module', 1, true);
}

$actionButtons .= html_print_go_back_button(
    'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=module&id_agente='.$id_agente,
    ['button_class' => ''],
    true
);

$outputForm .= html_print_action_buttons(
    $actionButtons,
    ['type' => 'form_action'],
    true
);

if ((bool) $config['enterprise_installed'] === true && $remote_conf === true) {
    $outputForm .= '<script type="text/javascript">var check_remote_conf = true;</script>';
}

$outputForm .= '</form>';

html_print_div(
    [
        'class'   => 'max_floating_element_size',
        'content' => $outputForm,
    ],
    false
);

ui_require_jquery_file('ui');
ui_require_jquery_file('form');
ui_require_jquery_file('pandora');
ui_require_jquery_file('pandora.controls');

ui_require_javascript_file('pandora_modules');
?>
<script language="javascript">
/* <![CDATA[ */
var no_name_lang =`
<?php
echo ui_print_info_message(
    [
        'no_close' => true,
        'message'  => __('No module name provided'),
    ]
);
?>
`;
var no_target_lang =`
<?php
echo ui_print_info_message(
    [
        'no_close' => true,
        'message'  => __('No target IP provided'),
    ]
);
?>
`;
var no_oid_lang =`
<?php
echo ui_print_info_message(
    [
        'no_close' => true,
        'message'  => __('No SNMP OID provided'),
    ]
);
?>
`;
var no_prediction_module_lang =`
<?php
echo ui_print_info_message(
    [
        'no_close' => true,
        'message'  => __('No module to predict'),
    ]
);
?>
`;
var no_plugin_lang =`
<?php
echo ui_print_info_message(
    [
        'no_close' => true,
        'message'  => __('No plug-in provided'),
    ]
);
?>
`;
var no_execute_test_from =`
<?php
echo ui_print_info_message(
    [
        'no_close' => true,
        'message'  => __('No server provided'),
    ]
);
?>
`;

$(document).ready (function () {
    configure_modules_form ();

    $("#module_form").submit(function() {
        if (typeof(check_remote_conf) != 'undefined') {
            if (check_remote_conf) {
                //Check the name.
                name = $("#text-name").val();
                remote_config = $("#textarea_configuration_data").val();

                regexp_name = new RegExp(
                    'module_name\\s*' + name.replace(/([^0-9A-Za-z_])/g,
                    "\\$1"
                    ) +"\n"
                );

                regexp_plugin = new RegExp('^module_plugin\\s*');

                if (remote_config == '' || remote_config.match(regexp_name) ||
                    remote_config.match(regexp_plugin) ||
                    $("#id_module_type").val()==100 ||
                    $("#hidden-id_module_type_hidden").val()==100) {
                    return true;
                }
                else {
                    alert ("<?php echo __('Error, The field name and name in module_name in data configuration are different.'); ?>");
                    return false;
                }
            }
        }

        return true;
    });

    function checkKeepaliveModule() {
        // keepalive modules have id = 100
        if ($("#id_module_type").val()==100 ||
            $("#hidden-id_module_type_hidden").val()==100) {
            $("#simple-configuration_data").hide();
        }
        else {
            // If exists macros dont show configuration data because
            // this visibility is controled by a form button
            if($('#hidden-macros').val() == '') {
                $("#simple-configuration_data").show();
            }
        }
    }

    checkKeepaliveModule();

    $("#id_module_type").change (function () {
        checkKeepaliveModule();
    });

});

function handleFileSelect() {
    //clear texarea
    $('#textarea_custom_string_1').empty();
    $('#mssg_error_div').empty();

    //messages error
    err_msg_1 = "<?php echo __('The File APIs are not fully supported in this browser.'); ?>";
    err_msg_2 = "<?php echo __('Couldn`t find the fileinput element.'); ?>";
    err_msg_3 = "<?php echo __('This browser doesn`t seem to support the files property of file inputs.'); ?>";
    err_msg_4 = "<?php echo __('Please select a file before clicking Load'); ?>";

    if (!window.File ||
        !window.FileReader ||
        !window.FileList ||
        !window.Blob
    ) {
        $('#mssg_error_div').append(err_msg_1);
        return;
    }

    input = document.getElementById('file-file_html_text');

    if (!input) {
        $('#mssg_error_div').append(err_msg_2);
    }
    else if (!input.files) {
        $('#mssg_error_div').append(err_msg_3);
    }
    else if (!input.files[0]) {
        $('#mssg_error_div').append(err_msg_4);
    }
    else {
        file = input.files[0];
        fr = new FileReader();
        fr.onload = receivedText;
        fr.readAsText(file);
    }
}

function receivedText() {
    document
        .getElementById('textarea_custom_string_1')
        .appendChild(document.createTextNode(fr.result));
}
/* ]]> */
</script>
