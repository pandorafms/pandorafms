<?php
/**
 * Configure agents.
 *
 * @category   Agents view - management.
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

enterprise_include('godmode/agentes/configurar_agente.php');
enterprise_include_once('include/functions_modules.php');
enterprise_include_once('include/functions_config_agents.php');
enterprise_include('include/functions_policies.php');
require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_cron.php';
ui_require_javascript_file('encode_decode_base64');
ui_require_css_file('agent_manager');

use PandoraFMS\Event;

check_login();

$tab                       = (string) get_parameter('tab', 'main');
$id_agente                 = (int) get_parameter('id_agente');
$agentAddress              = (string) get_parameter('direccion');
$alias                     = (string) get_parameter('alias');
$alias_as_name             = (int) get_parameter_post('alias_as_name');
$direccion_agente          = (string) get_parameter_post('direccion');
$unique_ip                 = (int) get_parameter_post('unique_ip');
// $alerttype                 = get_parameter('alerttype'); UNUSED.
$id_module                 = (int) get_parameter('id_module');
$id_agent_module           = (int) get_parameter('id_agent_module');
$intervalo                 = (string) get_parameter_post('intervalo', SECONDS_5MINUTES);
$comentarios               = (string) get_parameter_post('comentarios');
$modo                      = (int) get_parameter_post('modo');
$id_parent                 = (int) get_parameter_post('id_agent_parent');
$server_name               = (string) get_parameter_post('server_name');
$id_os                     = (int) get_parameter_post('id_os');
$disabled                  = (int) get_parameter_post('disabled');
$custom_id                 = (string) get_parameter_post('custom_id');
$cascade_protection        = (int) get_parameter_post('cascade_protection');
$cascade_protection_module = (int) get_parameter_post('cascade_protection_module');
$safe_mode                 = (int) get_parameter_post('safe_mode');
$safe_mode_module          = (int) get_parameter_post('safe_mode_module');
$icon_path                 = (string) get_parameter_post('icon_path');
$update_gis_data           = (int) get_parameter_post('update_gis_data');
$url_description           = (string) get_parameter('url_description');
$quiet                     = (int) get_parameter('quiet');
$cps                       = (int) get_parameter_switch('cps', -1);
$fixed_ip                  = (int) get_parameter_switch('fixed_ip');
$secondary_groups          = (string) get_parameter('secondary_hidden');
$grupo                     = (int) get_parameter_post('grupo');
$id_extension              = get_parameter('id_extension');
$type_module_t             = get_parameter('moduletype');
$wizardSection             = (string) get_parameter('wizard_section');
$fix_module                = get_parameter_get('fix_module', 0);
$address_list              = (string) get_parameter_post('address_list');
$agentName                 = (string) get_parameter_post('agente');
$disabled                  = (bool) get_parameter_post('disabled');
$satellite_server          = (int) get_parameter('satellite_server', 0);
$max_timeout               = (int) get_parameter('max_timeout');
$max_retries               = (int) get_parameter('max_retries');
$min                       = (int) get_parameter('min');
$max                       = (int) get_parameter('max');
$interval                  = (int) get_parameter('module_interval', $intervalo);
$ff_interval               = (int) get_parameter('module_ff_interval');
$quiet_module              = (int) get_parameter('quiet_module');
$cps_module                = (int) get_parameter('cps_module');
$id_plugin                 = (int) get_parameter('id_plugin');
$id_export                 = (int) get_parameter('id_export');
$disabled                  = (bool) get_parameter('disabled');
$tcp_send                  = (string) get_parameter('tcp_send');
$tcp_rcv                   = (string) get_parameter('tcp_rcv');
$tcp_port                  = (int) get_parameter('tcp_port');
$delete_ip                 = (string) get_parameter_post('address_list');
// Correction in order to not insert 0 as port.
$is_port_empty             = (string) get_parameter('tcp_port');
$prediction_module         = (int) get_parameter('prediction_module');

$id_module_type            = (int) get_parameter('id_module_type');
$name                      = (string) get_parameter('name');
$description               = (string) get_parameter('description');
$id_module_group           = (int) get_parameter('id_module_group');
$flag                      = (bool) get_parameter('flag');
$post_process              = (string) get_parameter('post_process', 0.0);
$configuration_data        = (string) get_parameter('configuration_data');
$old_configuration_data    = (string) get_parameter('old_configuration_data');
$custom_string_1           = (string) get_parameter('custom_string_1');
$custom_string_2           = (string) get_parameter('custom_string_2');
$custom_string_3           = (string) get_parameter('custom_string_3');
$custom_integer_1          = (int) get_parameter('custom_integer_1');
$custom_integer_2          = (int) get_parameter('custom_integer_2');
$custom_id                     = (string) get_parameter('custom_id');
$history_data                  = (int) get_parameter('history_data');
$dynamic_interval              = (int) get_parameter('dynamic_interval');
$dynamic_max                   = (int) get_parameter('dynamic_max');
$dynamic_min                   = (int) get_parameter('dynamic_min');
$dynamic_two_tailed            = (int) get_parameter('dynamic_two_tailed');
$min_warning                   = (float) get_parameter('min_warning');
$max_warning                   = (float) get_parameter('max_warning');
$str_warning                   = (string) get_parameter('str_warning');
$min_critical                  = (float) get_parameter('min_critical');
$max_critical                  = (float) get_parameter('max_critical');
$str_critical                  = (string) get_parameter('str_critical');
$ff_event                      = (int) get_parameter('ff_event');
$ff_event_normal               = (int) get_parameter('ff_event_normal');
$ff_event_warning              = (int) get_parameter('ff_event_warning');
$ff_event_critical             = (int) get_parameter('ff_event_critical');
$ff_type                       = (int) get_parameter('ff_type');
$each_ff                       = (int) get_parameter('each_ff', $module['each_ff']);
$ff_timeout                    = (int) get_parameter('ff_timeout');
$unit                          = (string) get_parameter('unit');
$id_tag                        = (array) get_parameter('id_tag_selected');
$serialize_ops                 = (string) get_parameter('serialize_ops');
$critical_instructions         = (string) get_parameter('critical_instructions');
$warning_instructions          = (string) get_parameter('warning_instructions');
$unknown_instructions          = (string) get_parameter('unknown_instructions');
$critical_inverse              = (int) get_parameter('critical_inverse');
$warning_inverse               = (int) get_parameter('warning_inverse');
$percentage_critical           = (int) get_parameter('percentage_critical');
$percentage_warning            = (int) get_parameter('percentage_warning');
$combo_snmp_oid                = (string) get_parameter_post('combo_snmp_oid');
$id_category                   = (int) get_parameter('id_category');
$hour_from                     = get_parameter('hour_from');
$minute_from                   = get_parameter('minute_from');
$mday_from                     = get_parameter('mday_from');
$month_from                    = get_parameter('month_from');
$wday_from                     = get_parameter('wday_from');
$hour_to                       = get_parameter('hour_to');
$minute_to                     = get_parameter('minute_to');
$mday_to                       = get_parameter('mday_to');
$month_to                      = get_parameter('month_to');
$wday_to                       = get_parameter('wday_to');
$http_user                     = get_parameter('http_user');
$http_pass                     = get_parameter('http_pass');
$snmp_community                = (string) get_parameter('snmp_community');
$snmp_oid                      = (string) get_parameter('snmp_oid');
$select_snmp_oid               = (string) get_parameter('select_snmp_oid');
$tcp_send                      = (string) get_parameter('snmp_version');
$plugin_user                   = (string) get_parameter('snmp3_auth_user');
$snmp3_auth_pass               = (string) get_parameter('snmp3_auth_pass');
$snmp3_privacy_pass            = (string) get_parameter('snmp3_privacy_pass');
$snmp3_security_level          = (string) get_parameter('snmp3_security_level');
$snmp3_auth_method             = (string) get_parameter('snmp3_auth_method');
$custom_string_1               = (string) get_parameter('snmp3_privacy_method');
$command_os                    = (string) get_parameter('command_os');
$command_text                  = (string) get_parameter('command_text');
$command_credential_identifier = (string) get_parameter('command_credential_identifier');
$plugin_user                   = (string) get_parameter('plugin_user');
$id_module_component_type      = (int) get_parameter('id_module_component_type');
$plugin_parameter              = (string) get_parameter('plugin_parameter');
$parent_module_id              = (int) get_parameter('parent_module_id');
$ip_target                     = (string) get_parameter('ip_target');
$estimation_type               = (string) get_parameter('estimation_type', 'estimation_calculation');
$estimation_days               = (int) get_parameter('estimation_days', -1);
$estimation_interval           = (string) get_parameter('estimation_interval', '300');
$active_snmp_v3                = get_parameter('active_snmp_v3');
$throw_unknown_events          = (bool) get_parameter('throw_unknown_events');
$module_macro_names            = (array) get_parameter('module_macro_names');
$module_macro_values           = (array) get_parameter('module_macro_values');
$moduletype                    = (int) get_parameter('moduletype');
$id_extension                  = (int) get_parameter('id_extension');
$updateGisData                 = get_parameter('update_gis_data');
$lastLatitude                  = get_parameter('latitude');
$lastLongitude                 = get_parameter('longitude');
$lastAltitude                  = get_parameter('altitude');
$idAgente                      = get_parameter('id_agente');
// Get macros.
$macros       = (string) get_parameter('macros');
$macros_names = (array) get_parameter('macro_name', []);
// Actions.
$pure             = (bool) get_parameter('pure');
$create_agent     = (bool) get_parameter('create_agent');
$update_agent     = (bool) get_parameter('update_agent');
$delete_conf_file = (bool) get_parameter('delete_conf_file');
$action_delete_ip = (bool) get_parameter('delete_ip');
$updateGIS        = (bool) get_parameter('update_gis');
$update_module    = (bool) get_parameter('update_module');
$create_module    = (bool) get_parameter('create_module');
$edit_module      = (bool) get_parameter('edit_module');
$delete_module    = (bool) get_parameter('delete_module');
$enable_module    = (int) get_parameter('enable_module');
$disable_module   = (int) get_parameter('disable_module');
// It is the id_agent_module to duplicate.
$duplicate_module          = (int) get_parameter('duplicate_module');

// Variable definitions.
$group = 0;
$all_groups = [$group];
if ($id_agente > 0) {
    $group = agents_get_agent_group($id_agente);
    $all_groups = agents_get_all_groups_agent($id_agente, $group);
}

if (check_acl_one_of_groups($config['id_user'], $all_groups, 'AW') === false) {
    $access_granted = false;
    switch ($tab) {
        case 'alert':
        case 'module':
            if (check_acl_one_of_groups($config['id_user'], $all_groups, 'AD') === true) {
                $access_granted = true;
            }
        break;

        default:
            // Default.
        break;
    }

    if ($access_granted === false) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access agent manager'
        );
        include 'general/noaccess.php';
        return;
    }
}

require_once 'include/functions_modules.php';
require_once 'include/functions_alerts.php';
require_once 'include/functions_reporting.php';

// Init vars.
$nombre_agente = '';
$alias_as_name = 0;
$direccion_agente = $agentAddress;
$direccion_agente = trim(io_safe_output($direccion_agente));
$direccion_agente = io_safe_input($direccion_agente);
$unique_ip = 0;
$intervalo = SECONDS_5MINUTES;
$ff_interval = 0;
$quiet_module = 0;
$cps_module = 0;
$id_server = '';
$max_alerts = 0;
$modo = 1;
$update_module = 0;
$disabled = '';
$id_parent = 0;
$modulo_max = '';
$modulo_min = '';
$module_interval = '';
$module_ff_interval = '';
$tcp_port = '';
$tcp_send = '';
$tcp_rcv = '';
$snmp_oid = '';
$ip_target = '';
$snmp_community = '';
$combo_snmp_oid = '';
$agent_created_ok = 0;
$create_agent = 0;
$alert_text = '';
$time_from = '';
$time_to = '';
$alerta_campo2_rec = '';
$alerta_campo3_rec = '';
$alert_id_agent = '';
$alert_d1 = 1;
$alert_d2 = 1;
$alert_d3 = 1;
$alert_d4 = 1;
$alert_d5 = 1;
$alert_d6 = 1;
$alert_d7 = 1;
$alert_recovery = 0;
$alert_priority = 0;
$server_name = '';
$satellite_server = 0;
$grupo = 0;
$id_os = 9;
// Windows.
$custom_id = '';
$cascade_protection = 0;
$cascade_protection_modules = 0;
$safe_mode = 0;
$safe_mode_module = 0;
$icon_path = '';
$update_gis_data = 0;
$unit = '';
$id_tag = [];
$tab_description = '';
$url_description = '';
$quiet = 0;
$macros = '';
$cps = 0;
$fixed_ip = 0;
$module_macros = [];

// Create agent.
if ($create_agent === true) {
    $mssg_warning = false;
    $alias_safe_output = strip_tags(io_safe_output($alias));
    $alias = io_safe_input(trim(preg_replace('/[\/\\\|%#&$]/', '', $alias_safe_output)));
    // Safe_output only validate ip.
    $direccion_agente = trim(io_safe_output($direccion_agente));

    if (validate_address($direccion_agente) === false) {
        $mssg_warning = true;
    }

    // Safe-input before validate ip.
    $direccion_agente = io_safe_input($direccion_agente);
    $nombre_agente = hash_agent_name($alias, $nombre_agente);

    if ((bool) check_acl($config['id_user'], $grupo, 'AW') === false) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access agent manager'
        );
        include $config['homedir'].'/general/noaccess.php';
        return;
    }

    $fields = db_get_all_fields_in_table('tagent_custom_fields');

    if ($fields === false) {
        $fields = [];
    }

    $field_values = [];

    foreach ($fields as $field) {
        $field_values[$field['id_field']] = (string) get_parameter_post('customvalue_'.$field['id_field'], '');
    }

    // Check if agent exists (BUG WC-50518-2).
    if (empty($alias) === true) {
        $agent_creation_error = __('No agent alias specified');
        $agent_created_ok = 0;
    } else if (group_allow_more_agents($grupo, true, 'create') === false) {
        $agent_creation_error = __('Agent cannot be created due to the maximum agent limit for this group');
        $agent_created_ok = 0;
    } else {
        if ((bool) $alias_as_name === true) {
            $sql = 'SELECT nombre FROM tagente WHERE nombre = "'.$alias.'"';
            $exists_alias  = db_get_row_sql($sql);
            $nombre_agente = $alias;
        }

        if ($unique_ip && $direccion_agente != '') {
            $sql = 'SELECT direccion FROM tagente WHERE direccion = "'.$direccion_agente.'"';
            $exists_ip  = db_get_row_sql($sql);
        }

        if (!$exists_alias && !$exists_ip) {
            $id_agente = db_process_sql_insert(
                'tagente',
                [
                    'nombre'                    => $nombre_agente,
                    'alias'                     => $alias,
                    'alias_as_name'             => $alias_as_name,
                    'direccion'                 => $direccion_agente,
                    'id_grupo'                  => $grupo,
                    'intervalo'                 => $intervalo,
                    'comentarios'               => $comentarios,
                    'modo'                      => $modo,
                    'id_os'                     => $id_os,
                    'disabled'                  => $disabled,
                    'cascade_protection'        => $cascade_protection,
                    'cascade_protection_module' => $cascade_protection_module,
                    'server_name'               => $server_name,
                    'id_parent'                 => $id_parent,
                    'custom_id'                 => $custom_id,
                    'icon_path'                 => $icon_path,
                    'update_gis_data'           => $update_gis_data,
                    'url_address'               => $url_description,
                    'quiet'                     => $quiet,
                    'cps'                       => $cps,
                    'fixed_ip'                  => $fixed_ip,
                ]
            );
            enterprise_hook('update_agent', [$id_agente]);
        } else {
            $id_agente = false;
        }

        if ($id_agente !== false) {
            // Create custom fields for this agent.
            foreach ($field_values as $key => $value) {
                $update_custom = db_process_sql_insert(
                    'tagent_custom_data',
                    [
                        'id_field'    => $key,
                        'id_agent'    => $id_agente,
                        'description' => $value,
                    ]
                );
            }

            // Create address for this agent in taddress.
            if ($direccion_agente != '') {
                agents_add_address($id_agente, $direccion_agente);
            }

            $agent_created_ok = true;

            $info = '{"Name":"'.$nombre_agente.'",
				"IP":"'.$direccion_agente.'",
				"Group":"'.$grupo.'",
				"Interval":"'.$intervalo.'",
				"Comments":"'.$comentarios.'",
				"Mode":"'.$modo.'",
				"ID_parent:":"'.$id_parent.'",
				"Server":"'.$server_name.'",
				"ID os":"'.$id_os.'",
				"Disabled":"'.$disabled.'",
				"Custom ID":"'.$custom_id.'",
				"Cascade protection":"'.$cascade_protection.'",
				"Cascade protection module":"'.$cascade_protection_module.'",
				"Icon path":"'.$icon_path.'",
				"Update GIS data":"'.$update_gis_data.'",
				"Url description":"'.$url_description.'",
				"Quiet":"'.(int) $quiet.'",
				"Cps":"'.(int) $cps.'",}';

            // Create the secondary groups.
            enterprise_hook(
                'agents_update_secondary_groups',
                [
                    $id_agente,
                    explode(',', $secondary_groups),
                    [],
                ]
            );

            $unsafe_alias = io_safe_output($alias);
            db_pandora_audit(
                AUDIT_LOG_AGENT_MANAGEMENT,
                'Created agent '.$unsafe_alias,
                false,
                true,
                $info
            );
        } else {
            $id_agente = 0;
            $agent_creation_error = __('Could not be created');
            if ($exists_alias) {
                $agent_creation_error = __('Could not be created, because name already exists');
            } else if ($exists_ip) {
                $agent_creation_error = __('Could not be created, because IP already exists');
            }
        }
    }
}

// Show tabs.
$img_style = [
    'class' => 'top',
    'width' => 16,
];

if ($id_agente) {
    // View tab.
    $viewtab['text'] = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$id_agente.'">'.html_print_image(
        'images/eye.png',
        true,
        [
            'title' => __('View'),
            'class' => 'invert_filter',
        ]
    ).'</a>';

    if ($tab == 'view') {
        $viewtab['active'] = true;
    } else {
        $viewtab['active'] = false;
    }

    $viewtab['operation'] = 1;

    // Main tab.
    $maintab['text'] = '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;tab=main&amp;id_agente='.$id_agente.'">'.html_print_image(
        'images/gm_setup.png',
        true,
        [
            'title' => __('Setup'),
            'class' => 'invert_filter',
        ]
    ).'</a>';
    if ($tab === 'main') {
        $maintab['active'] = true;
    } else {
        $maintab['active'] = false;
    }

    // Module tab.
    $moduletab['text'] = '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;tab=module&amp;id_agente='.$id_agente.'">'.html_print_image(
        'images/gm_modules.png',
        true,
        [
            'title' => __('Modules'),
            'class' => 'invert_filter',
        ]
    ).'</a>';

    if ($tab == 'module') {
        $moduletab['active'] = true;
    } else {
        $moduletab['active'] = false;
    }

    // Alert tab.
    $alerttab['text'] = '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;tab=alert&amp;id_agente='.$id_agente.'">'.html_print_image(
        'images/gm_alerts.png',
        true,
        [
            'title' => __('Alerts'),
            'class' => 'invert_filter',
        ]
    ).'</a>';

    if ($tab == 'alert') {
        $alerttab['active'] = true;
    } else {
        $alerttab['active'] = false;
    }

    // Template tab.
    $templatetab['text'] = '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;tab=template&amp;id_agente='.$id_agente.'">'.html_print_image(
        'images/templates.png',
        true,
        [
            'title' => __('Module templates'),
            'class' => 'invert_filter',
        ]
    ).'</a>';

    $templatetab['active'] = ($tab === 'template');

    // Inventory.
    $inventorytab = enterprise_hook('inventory_tab');

    if ($inventorytab == -1) {
        $inventorytab = '';
    }


    $has_remote_conf = enterprise_hook(
        'config_agents_has_remote_configuration',
        [$id_agente]
    );
    if ($has_remote_conf === ENTERPRISE_NOT_HOOK) {
        $has_remote_conf = false;
    }

    if ($has_remote_conf === true) {
        // Plugins.
        $pluginstab = enterprise_hook('plugins_tab');
        if ($pluginstab == -1) {
            $pluginstab = '';
        }
    } else {
        $pluginstab = '';
    }

    // Collection.
    $collectiontab = enterprise_hook('collection_tab');

    if ($collectiontab == -1) {
        $collectiontab = '';
    }

    // NetworkConfigManager tab.
    $ncm_tab = enterprise_hook('networkconfigmanager_tab');

    if ($ncm_tab === ENTERPRISE_NOT_HOOK) {
        $ncm_tab = '';
    }

    // Group tab.
    $grouptab['text'] = html_print_anchor(
        [
            'href'    => 'index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&ag_group='.$group,
            'content' => html_print_image(
                'images/group.png',
                true,
                [
                    'title' => __('Group'),
                    'class' => 'invert_filter',
                ]
            ),
        ],
        true
    );

    $grouptab['active'] = false;

    $gistab = [];
    // GIS tab.
    if ((bool) $config['activate_gis'] === true) {
        $gistab['text'] = html_print_anchor(
            [
                'href'    => 'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=gis&id_agente='.$id_agente,
                'content' => html_print_image(
                    'images/gm_gis.png',
                    true,
                    [
                        'title' => __('GIS data'),
                        'class' => 'invert_filter',
                    ]
                ),
            ],
            true
        );

        $gistab['active'] = ($tab === 'gis');
    }

    // Agent wizard tab.
    $agent_wizard['text'] = '<a href="javascript:" class="agent_wizard_tab">'.html_print_image(
        'images/wand_agent.png',
        true,
        [
            'title' => __('Agent wizard'),
            'class' => 'invert_filter',
        ]
    ).'</a>';

    // Hidden subtab layer.
    $agent_wizard['sub_menu'] = '<ul class="mn subsubmenu invisible float-none">';
    $agent_wizard['sub_menu'] .= '<li class="nomn tab_godmode center">';
    $agent_wizard['sub_menu'] .= '<a href="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=agent_wizard&wizard_section=snmp_explorer&id_agente='.$id_agente.'">'.html_print_image(
        'images/wand_snmp.png',
        true,
        [
            'title' => __('SNMP Wizard'),
            'class' => 'invert_filter',
        ]
    ).'</a>';
    $agent_wizard['sub_menu'] .= '</li>';
    $agent_wizard['sub_menu'] .= '<li class="nomn tab_godmode center">';
    $agent_wizard['sub_menu'] .= '<a href="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=agent_wizard&wizard_section=snmp_interfaces_explorer&id_agente='.$id_agente.'">'.html_print_image(
        'images/wand_interfaces.png',
        true,
        [
            'title' => __('SNMP Interfaces wizard'),
            'class' => 'invert_filter',
        ]
    ).'</a>';
    $agent_wizard['sub_menu'] .= '</li>';
    $agent_wizard['sub_menu'] .= '<li class="nomn tab_godmode center" >';
    $agent_wizard['sub_menu'] .= '<a href="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=agent_wizard&wizard_section=wmi_explorer&id_agente='.$id_agente.'">'.html_print_image(
        'images/wand_wmi.png',
        true,
        [
            'title' => __('WMI Wizard'),
            'class' => 'invert_filter',
        ]
    ).'</a>';
    $agent_wizard['sub_menu'] .= '</li>';
    $agent_wizard['sub_menu'] .= '</ul>';
    $agent_wizard['active'] = ($tab === 'agent_wizard');

    $total_incidents = agents_get_count_incidents($id_agente);

    // Incident tab.
    if ($total_incidents > 0) {
        $incidenttab['text'] = html_print_anchor(
            [
                'href'    => 'index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;tab=incident&amp;id_agente='.$id_agente,
                'content' => html_print_image(
                    'images/book_edit.png',
                    true,
                    [
                        'title' => __('Incidents'),
                        'class' => 'invert_filter',
                    ]
                ),
            ],
            true
        );

        $incidenttab['active'] = ($tab === 'incident');
    }

    if ((bool) check_acl_one_of_groups($config['id_user'], $all_groups, 'AW') === true) {
        $onheader = [
            'view'         => $viewtab,
            'separator'    => '',
            'main'         => $maintab,
            'module'       => $moduletab,
            'ncm'          => $ncm_tab,
            'alert'        => $alerttab,
            'template'     => $templatetab,
            'inventory'    => $inventorytab,
            'pluginstab'   => $pluginstab,
            'collection'   => $collectiontab,
            'group'        => $grouptab,
            'gis'          => $gistab,
            'agent_wizard' => $agent_wizard,

        ];

        if ((bool) $has_remote_conf === true) {
            $agent_name = agents_get_name($id_agente);
            $agent_name = io_safe_output($agent_name);
            $agent_md5 = md5($agent_name, false);

            $remote_configuration_tab['text'] = html_print_anchor(
                [
                    'href'    => 'index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;tab=remote_configuration&amp;id_agente='.$id_agente.'&amp;disk_conf='.$agent_md5,
                    'content' => html_print_image(
                        'images/remote_configuration.png',
                        true,
                        [
                            'title' => __('Remote configuration'),
                            'class' => 'invert_filter',
                        ]
                    ),
                ],
                true
            );

            $remote_configuration_tab['active'] = ($tab === 'remote_configuration');

            $onheader[] = $remote_configuration_tab;
        }

        // Only if the agent has incidents associated show incidents tab.
        if ($total_incidents) {
            $onheader['incident'] = $incidenttab;
        }
    } else {
        $onheader = [
            'view'      => $viewtab,
            'separator' => '',
            'module'    => $moduletab,
            'alert'     => $alerttab,
        ];
    }

    // Extensions tabs.
    foreach ($config['extensions'] as $extension) {
        if (isset($extension['extension_god_tab']) === true) {
            if (check_acl($config['id_user'], $group, $extension['extension_god_tab']['acl'])) {
                $image = $extension['extension_god_tab']['icon'];
                $name = $extension['extension_god_tab']['name'];
                $id = $extension['extension_god_tab']['id'];

                if ($id_extension == $id) {
                    $active = true;
                } else {
                    $active = false;
                }

                $url = 'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=extension&id_agente='.$id_agente.'&id_extension='.$id;

                $extension_tab = [
                    'text'   => '<a href="'.$url.'">'.html_print_image($image, true, [ 'title' => $name]).'</a>',
                    'active' => $active,
                ];

                $onheader = ($onheader + [$id => $extension_tab]);
            }
        }
    }

    $help_header = '';
    $tab_name = '';
    // This add information to the header.
    switch ($tab) {
        case 'main':
            $help_header = 'main_tab';
            $tab_name = __('Setup');
        break;

        case 'collection':
            $tab_name = __('Collection');
        break;

        case 'ncm':
            $tab_name = __('Network config manager');
        break;

        case 'inventory':
            $help_header = 'inventory_tab';
            $tab_name = __('Inventory');
        break;

        case 'plugins':
            $help_header = 'plugins_tab';
            $tab_name = __('Agent plugins');
        break;

        case 'module':
            $tab_name = __('Modules');
            if ($type_module_t === 'webux') {
                $help_header = 'wux_console';
            } else {
                $help_header = 'local_module_tab';
            }
        break;

        case 'alert':
            $help_header = 'manage_alert_list';
            $tab_name = __('Alerts');
        break;

        case 'template':
            $tab_name = __('Module templates');
        break;

        case 'gis':
            $tab_name = __('Gis');
            $help_header = 'gis_tab';
        break;

        case 'incident':
            $tab_name = __('Incidents');
        break;

        case 'remote_configuration':
            $tab_name = __('Remote configuration');
        break;

        case 'agent_wizard':
            switch ($wizardSection) {
                case 'snmp_explorer':
                    $help_header = 'agent_snmp_explorer_tab';
                    $tab_name = __('SNMP Wizard');
                break;

                case 'snmp_interfaces_explorer':
                    $tab_name = __('SNMP Interfaces Wizard');
                    $help_header = 'agent_snmp_interfaces_explorer_tab';
                break;

                case 'wmi_explorer':
                    $tab_name = __('WMI Wizard');
                    $help_header = 'agent_snmp_wmi_explorer_tab';
                break;

                default:
                    // Default.
                break;
            }
        break;

        case 'extension':
            switch ($id_extension) {
                case 'snmp_explorer':
                    $tab_description = '- '.__('SNMP explorer');
                    $help_header = 'snmp_explorer';
                break;

                default:
                    // Default.
                break;
            }
        break;

        default:
            // Default.
        break;
    }

    if ($pure === false) {
        ui_print_standard_header(
            agents_get_alias($id_agente),
            'images/agent.png',
            false,
            ($help_header === 'main_tab') ? $help_header : '',
            false,
            $onheader,
            [
                [
                    'link'  => '',
                    'label' => __('Resources'),
                ],
                [
                    'link'  => 'index.php?sec=gagente&sec2=godmode/agentes/modificar_agente',
                    'label' => __('Manage agents'),
                ],
                [
                    'link'  => '',
                    'label' => $tab_name,
                ],
            ]
        );
    }
} else {
    ui_print_standard_header(
        __('Create agent'),
        'images/agent.png',
        false,
        '',
        false,
        [],
        [
            [
                'link'  => '',
                'label' => __('Resources'),
            ],
            [
                'link'  => 'index.php?sec=gagente&sec2=godmode/agentes/modificar_agente',
                'label' => __('Manage agents'),
            ],
        ]
    );
}

if ($delete_conf_file === true) {
    $correct = false;
    // Delete remote configuration.
    if (isset($config['remote_config'])) {
        $agent_md5 = md5(io_safe_output(agents_get_name($id_agente, 'none')), false);

        if (file_exists($config['remote_config'].'/md5/'.$agent_md5.'.md5')) {
            // Agent remote configuration editor.
            $file_name = $config['remote_config'].'/conf/'.$agent_md5.'.conf';
            $correct = @unlink($file_name);

            $file_name = $config['remote_config'].'/md5/'.$agent_md5.'.md5';
            $correct = @unlink($file_name);
        }
    }

    ui_print_result_message(
        $correct,
        __('Conf file deleted successfully'),
        __('Could not delete conf file')
    );
}

// Show agent creation results.
if ($create_agent === true) {
    if (isset($agent_creation_error) === false) {
        $agent_creation_error = __('Could not be created');
    }

    ui_print_result_message(
        $agent_created_ok,
        __('Successfully created'),
        $agent_creation_error
    );

    if ($mssg_warning === true) {
        ui_print_warning_message(__('The ip or dns name entered cannot be resolved'));
    }
}

// Fix / Normalize module data.
if (isset($_GET['fix_module']) === true) {
    // Get info about this module.
    $media = reporting_get_agentmodule_data_average($id_module, 30758400);
    // Get average over the year.
    $media *= 1.3;
    $error = '';
    $result = true;

    // If the value of media is 0 or something went wrong, don't delete.
    if (empty($media) === false) {
        $where = [
            'datos'            => '>'.$media,
            'id_agente_modulo' => $id_module,
        ];
        $res = db_process_sql_delete('tagente_datos', $where);

        if ($res === false) {
            $result = false;
            $error = modules_get_agentmodule_name($id_module);
        } else if ($res <= 0) {
            $result = false;
            $error = ' - '.__('No data to normalize');
        }
    } else {
        $result = false;
        $error = ' - '.__('No data to normalize');
    }

    ui_print_result_message(
        $result,
        __('Deleted data above %f', $media),
        __('Error normalizing module %s', $error)
    );
}

// Update AGENT.
if ($update_agent === true) {
    // If modified some agent paramenter.
    $mssg_warning = false;
    $nombre_agente = str_replace('`', '&lsquo;', $agentName);
    $alias_safe_output = strip_tags(io_safe_output($alias));
    $alias = io_safe_input(trim(preg_replace('/[\/\\\|%#&$]/', '', $alias_safe_output)));
    // Safe_output only validate ip.
    $direccion_agente = trim(io_safe_output($direccion_agente));

    if (validate_address($direccion_agente) === false) {
        $mssg_warning = true;
    }

    // Safe-input before validate ip.
    $direccion_agente = io_safe_input($direccion_agente);

    if ($address_list != $direccion_agente
        && $direccion_agente == agents_get_address($id_agente)
        && $address_list != agents_get_address($id_agente)
    ) {
        /*
         *  If we selected another IP in the drop down list to be 'primary':
         * a) field is not the same as selectbox
         * b) field has not changed from current IP
         * c) selectbox is not the current IP.
         */

        if (empty($address_list) === false) {
            $direccion_agente = $address_list;
        }
    }

    $comentarios = str_replace('`', '&lsquo;', $comentarios);
    $old_values = db_get_row('tagente', 'id_agente', $id_agente);
    $fields = db_get_all_fields_in_table('tagent_custom_fields');

    if ($fields === false) {
        $fields = [];
    }

    $field_values = [];

    foreach ($fields as $field) {
        $field_values[$field['id_field']] = (string) get_parameter_post('customvalue_'.$field['id_field'], '');
    }

    foreach ($field_values as $key => $value) {
        $old_value = db_get_all_rows_filter(
            'tagent_custom_data',
            [
                'id_agent' => $id_agente,
                'id_field' => $key,
            ]
        );

        if ($old_value === false) {
            // Create custom field if not exist.
            $update_custom = db_process_sql_insert(
                'tagent_custom_data',
                [
                    'id_field'    => $key,
                    'id_agent'    => $id_agente,
                    'description' => $value,
                ]
            );
        } else {
            $update_custom = db_process_sql_update(
                'tagent_custom_data',
                ['description' => $value],
                [
                    'id_field' => $key,
                    'id_agent' => $id_agente,
                ]
            );

            if ($update_custom == 1) {
                    $update_custom_result = 1;
            }
        }
    }

    if ($mssg_warning === true) {
        ui_print_warning_message(__('The ip or dns name entered cannot be resolved'));
    }

    // Verify if there is another agent with the same name but different ID.
    if (empty($alias) === true) {
        ui_print_error_message(__('No agent alias specified'));
        // If there is an agent with the same name, but a different ID.
    }

    if ($direccion_agente !== $address_list && (bool) $unique_ip === true && $direccion_agente != '') {
        $sql = 'SELECT direccion FROM tagente WHERE direccion = "'.$direccion_agente.'"';
        $exists_ip  = db_get_row_sql($sql);
    }

    $old_group = agents_get_agent_group($id_agente);
    if ($grupo <= 0) {
        ui_print_error_message(__('The group id %d is incorrect.', $grupo));
    } else if ($old_group !== $grupo && group_allow_more_agents($grupo, true, 'update') === false) {
        ui_print_error_message(__('Agent cannot be updated due to the maximum agent limit for this group'));
    } else if ($exists_ip) {
        ui_print_error_message(__('Duplicate main IP address'));
    } else {
        // If different IP is specified than previous, add the IP.
        if (empty($direccion_agente) === false
            && $direccion_agente != agents_get_address($id_agente)
        ) {
            agents_add_address($id_agente, $direccion_agente);
        }

        // If IP is set for deletion, delete first.
        if ($action_delete_ip === true) {
            if (empty($direccion_agente) === true) {
                $direccion_agente = agents_delete_address($id_agente, $delete_ip, true);
            } else {
                $direccion_agente = agents_delete_address($id_agente, $delete_ip, true);
            }
        }

        $values = [
            'disabled'                  => $disabled,
            'id_parent'                 => $id_parent,
            'id_os'                     => $id_os,
            'modo'                      => $modo,
            'alias'                     => $alias,
            'alias_as_name'             => $alias_as_name,
            'direccion'                 => $direccion_agente,
            'id_grupo'                  => $grupo,
            'intervalo'                 => $intervalo,
            'comentarios'               => $comentarios,
            'cascade_protection'        => $cascade_protection,
            'cascade_protection_module' => $cascade_protection_module,
            'server_name'               => $server_name,
            'custom_id'                 => $custom_id,
            'icon_path'                 => $icon_path,
            'update_gis_data'           => $update_gis_data,
            'url_address'               => $url_description,
            'quiet'                     => $quiet,
            'cps'                       => $cps,
            'safe_mode_module'          => $safe_mode_module,
            'satellite_server'          => $satellite_server,
            'fixed_ip'                  => $fixed_ip,
        ];

        if ($config['metaconsole_agent_cache'] == 1) {
            $values['update_module_count'] = 1;
            // Force an update of the agent cache.
        }

        $result = db_process_sql_update('tagente', $values, ['id_agente' => $id_agente]);

        if ($result === false && $update_custom_result == false) {
            ui_print_error_message(
                __('There was a problem updating the agent')
            );
        } else {
            // Update the agent from the metaconsole cache.
            enterprise_include_once('include/functions_agents.php');
            enterprise_hook('agent_update_from_cache', [$id_agente, $values, $server_name]);

            // Update the configuration files.
            if ($old_values['intervalo'] != $intervalo) {
                enterprise_hook(
                    'config_agents_update_config_token',
                    [
                        $id_agente,
                        'interval',
                        $intervalo,
                    ]
                );
            }

            if ($old_values['disabled'] != $disabled) {
                enterprise_hook(
                    'config_agents_update_config_token',
                    [
                        $id_agente,
                        'standby',
                        ($disabled) ? '1' : '0',
                    ]
                );
                // Validate alerts for disabled agents.
                if ($disabled) {
                    alerts_validate_alert_agent($id_agente);
                }
            }

            $info = '{
				"id_agente":"'.$id_agente.'",
				"alias":"'.$alias.'",
				"Group":"'.$grupo.'",
				"Interval" : "'.$intervalo.'",
				"Comments":"'.$comentarios.'",
				"Mode":"'.$modo.'",
				"ID OS":"'.$id_os.'",
				"Disabled":"'.$disabled.'",
				"Server Name":"'.$server_name.'",
				"ID parent":"'.$id_parent.'",
				"Custom ID":"'.$custom_id.'",
				"Cascade Protection":"'.$cascade_protection.'",
				"Cascade protection module":"'.$cascade_protection_module.'",
				"Icon Path":"'.$icon_path.'",
				"Update GIS data":"'.$update_gis_data.'",
				"Url description":"'.$url_description.'",
				"Quiet":"'.(int) $quiet.'",
				"Cps":"'.(int) $cps.'"}';

            // Create the secondary groups.
            enterprise_hook(
                'agents_update_secondary_groups',
                [
                    $id_agente,
                    explode(',', $secondary_groups),
                    [],
                ]
            );

            ui_print_success_message(__('Successfully updated'));
            db_pandora_audit(
                AUDIT_LOG_AGENT_MANAGEMENT,
                'Updated agent '.io_safe_output($alias),
                false,
                false,
                $info
            );
        }
    }
}

// Read agent data
// This should be at the end of all operation checks, to read the changes - $id_agente doesn't have to be retrieved.
if ($id_agente) {
    // This has been done in the beginning of the page, but if an agent was created, this id might change.
    $id_grupo = agents_get_agent_group($id_agente);
    if (!check_acl_one_of_groups($config['id_user'], $all_groups, 'AW') && !check_acl_one_of_groups($config['id_user'], $all_groups, 'AD')) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to admin an agent without access'
        );
        include 'general/noaccess.php';
        exit;
    }

    $agent = db_get_row('tagente', 'id_agente', $id_agente, false, false);
    if (empty($agent) === true) {
        // Close out the page.
        ui_print_error_message(__('There was a problem loading the agent'));
        return;
    }

    $intervalo = $agent['intervalo'];
    // Define interval in seconds.
    $nombre_agente = $agent['nombre'];
    if (empty($alias) === true) {
        $alias = $agent['alias'];
        if (empty($alias) === true) {
            $alias = $nombre_agente;
        }
    }

    $alias_as_name = $agent['alias_as_name'];
    $direccion_agente = $agent['direccion'];
    $grupo = $agent['id_grupo'];
    $ultima_act = $agent['ultimo_contacto'];
    $comentarios = $agent['comentarios'];
    $server_name = $agent['server_name'];
    $modo = $agent['modo'];
    $id_os = $agent['id_os'];
    $disabled = $agent['disabled'];
    $id_parent = $agent['id_parent'];
    $custom_id = $agent['custom_id'];
    $cascade_protection = $agent['cascade_protection'];
    $cascade_protection_module = $agent['cascade_protection_module'];
    $icon_path = $agent['icon_path'];
    $update_gis_data = $agent['update_gis_data'];
    $url_description = $agent['url_address'];
    $quiet = $agent['quiet'];
    $cps = $agent['cps'];
    $safe_mode_module = $agent['safe_mode_module'];
    $safe_mode = ($safe_mode_module) ? 1 : 0;
    $satellite_server = (int) $agent['satellite_server'];
    $fixed_ip = (int) $agent['fixed_ip'];
}

// GET DATA for MODULE UPDATE OR MODULE INSERT.
if ($update_module || $create_module) {
    $id_grupo = agents_get_agent_group($id_agente);
    $all_groups = agents_get_all_groups_agent($id_agente, $id_grupo);

    if (! check_acl_one_of_groups($config['id_user'], $all_groups, 'AW')) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to create a module without admin rights'
        );
        include 'general/noaccess.php';
        exit;
    }

    /*
     * Don't read as (float) because it lost it's decimals when put into MySQL
     * where are very big and PHP uses scientific notation, p.e:
     * 1.23E-10 is 0.000000000123.
     */

    if (empty($is_port_empty) === true) {
        $tcp_port = null;
    }

    $new_configuration_data = '';
    $custom_string_1_default = '';
    $custom_string_2_default = '';
    $custom_string_3_default = '';
    $custom_integer_1_default = 0;
    $custom_integer_2_default = 0;
    if ($update_module === true) {
        $module = modules_get_agentmodule($id_agent_module);

        $custom_string_1_default = $module['custom_string_1'];
        $custom_string_2_default = $module['custom_string_2'];
        $custom_string_3_default = $module['custom_string_3'];
        $custom_integer_1_default = $module['custom_integer_1'];
        $custom_integer_2_default = $module['custom_integer_2'];
    }

    if ($id_module_type === 25) {
        // Web analysis, from MODULE_WUX.
        $custom_string_1 = base64_encode($custom_string_1);
        // If the custom_string_1 parameter come empty, set the content
        // of the module (it is base64_encoded).
        if (empty($custom_string_1) === true) {
            $custom_string_1 = $custom_string_1_default;
        }
    }

    $custom_string_1 = (empty($custom_string_1) === true) ? $custom_string_1_default : $custom_string_1;
    $custom_string_2 = (empty($custom_string_2) === true) ? $custom_string_2_default : $custom_string_2;
    $custom_string_3 = (empty($custom_string_3) === true) ? $custom_string_3_default : $custom_string_3;
    $custom_integer_1 = ($custom_integer_1 === 0) ? $custom_integer_1_default : $custom_integer_1;
    $custom_integer_2 = ($custom_integer_2 === 0) ? $custom_integer_2_default : $custom_integer_2;

    if (empty($macros) === false) {
        $macros = json_decode(base64_decode($macros), true);

        foreach ($macros as $k => $m) {
            $m_hide = '0';
            if (isset($m['hide']) === true) {
                $m_hide = $m['hide'];
            }

            if ($m_hide == '1') {
                $macros[$k]['value'] = io_input_password(get_parameter($m['macro'], ''));
            } else {
                $macros[$k]['value'] = get_parameter($m['macro'], '');
            }
        }

        $macros = io_json_mb_encode($macros);

        $conf_array = explode("\n", io_safe_output($configuration_data));

        foreach ($conf_array as $line) {
            if (preg_match('/^module_name\s*(.*)/', $line, $match)) {
                $new_configuration_data .= 'module_name ';
                $new_configuration_data .= io_safe_output($name)."\n";
            } else if (!preg_match('/^module_macro_field.*/', $line, $match)) {
                // We delete from conf all the module macros starting with _field.
                $new_configuration_data .= $line."\n";
            }
        }

        $values_macros = [];
        $values_macros['macros'] = base64_encode($macros);

        $macros_for_data = enterprise_hook(
            'config_agents_get_macros_data_conf',
            [$values_macros]
        );

        if ($macros_for_data != '') {
            $new_configuration_data = str_replace('module_end', $macros_for_data.'module_end', $new_configuration_data);
        }

        $configuration_data = str_replace(
            '\\',
            '&#92;',
            io_safe_input($new_configuration_data)
        );
    }

    // Services are an enterprise feature,
    // so we got the parameters using this function.
    enterprise_hook('get_service_synthetic_parameters');

    $agent_name = (string) get_parameter(
        'agent_name',
        agents_get_name($id_agente)
    );

    // Change double quotes by single.
    $snmp_oid = preg_replace('/&quot;/', '&#039;', $snmp_oid);

    if (empty($snmp_oid) === true) {
        // The user did not set any OID manually but did a SNMP walk.
        $snmp_oid = $select_snmp_oid;
    }

    if ($id_module_type >= 15 && $id_module_type <= 18) {
        // New support for snmp v3.
        $plugin_pass = io_input_password($snmp3_auth_pass);
        $custom_string_1 = $snmp3_privacy_method;
        $custom_string_2 = io_input_password($snmp3_privacy_pass);
        $custom_string_3 = $snmp3_security_level;
        $plugin_parameter = $snmp3_auth_method;
    } else if ($id_module_type >= 34 && $id_module_type <= 37) {
        $tcp_send = $command_text;
        $custom_string_1 = $command_credential_identifier;
        $custom_string_2 = $command_os;
    } else {
        if ($id_module_component_type !== 7) {
            $plugin_pass = io_input_password($plugin_pass);
        }
    }

    // No autofill if the module is a webserver module.
    if (empty($ip_target) === true
        && $id_module_type < MODULE_TYPE_WEB_DATA
        && $id_module_type > MODULE_TYPE_WEB_CONTENT_STRING
    ) {
        $ip_target = 'auto';
    }

    if ($unit === '0') {
        $unit = '';
    }

    if ($hour_to !== '*') {
        $hour_to = '-'.$hour_to;
    } else {
        $hour_to = '';
    }

    if ($minute_to !== '*') {
        $minute_to = '-'.$minute_to;
    } else {
        $minute_to = '';
    }

    if ($mday_to !== '*') {
        $mday_to = '-'.$mday_to;
    } else {
        $mday_to = '';
    }

    if ($month_to !== '*') {
        $month_to = '-'.$month_to;
    } else {
        $month_to = '';
    }

    if ($wday_to !== '*') {
        $wday_to = '-'.$wday_to;
    } else {
        $wday_to = '';
    }

    $cron_interval = $minute_from.$minute_to.' '.$hour_from.$hour_to.' '.$mday_from.$mday_to.' '.$month_from.$month_to.' '.$wday_from.$wday_to;
    if (cron_check_syntax($cron_interval) === false) {
        $cron_interval = '';
    }

    if ((int) $prediction_module !== MODULE_PREDICTION_SYNTHETIC) {
        unset($serialize_ops);
        enterprise_hook(
            'modules_delete_synthetic_operations',
            [$id_agent_module]
        );
    }

    if ($prediction_module === MODULE_PREDICTION_PLANNING) {
        $custom_string_2 = $estimation_type;
        if ($custom_string_2 === 'estimation_calculation') {
            $custom_string_1 = $estimation_days;
        } else {
            $custom_string_1 = $estimation_interval;
        }
    }

    // Set the event type that can show.
    $disabled_types_event = [EVENTS_GOING_UNKNOWN => (int) $throw_unknown_events];
    $disabled_types_event = io_json_mb_encode($disabled_types_event);
    $module_macros = modules_get_module_macros_json($module_macro_names, $module_macro_values);

    // Make changes in the conf file if necessary.
    enterprise_include_once('include/functions_config_agents.php');

    $module_in_policy = enterprise_hook('policies_is_module_in_policy', [$id_agent_module]);
    $module_linked = enterprise_hook('policies_is_module_linked', [$id_agent_module]);
}

// Initialize result of the action (insert or update).
$success_action = NOERR;

// MODULE UPDATE.
if ($update_module === true) {
    $values = [
        'id_agente_modulo'      => $id_agent_module,
        'descripcion'           => $description,
        'id_module_group'       => $id_module_group,
        'nombre'                => $name,
        'max'                   => $max,
        'min'                   => $min,
        'module_interval'       => $interval,
        'module_ff_interval'    => $ff_interval,
        'tcp_port'              => $tcp_port,
        'tcp_send'              => $tcp_send,
        'tcp_rcv'               => $tcp_rcv,
        'snmp_community'        => $snmp_community,
        'snmp_oid'              => $snmp_oid,
        'ip_target'             => $ip_target,
        'flag'                  => $flag,
        'disabled'              => $disabled,
        'id_export'             => $id_export,
        'plugin_user'           => $plugin_user,
        'plugin_pass'           => $plugin_pass,
        'plugin_parameter'      => $plugin_parameter,
        'id_plugin'             => $id_plugin,
        'post_process'          => $post_process,
        'prediction_module'     => $prediction_module,
        'max_timeout'           => $max_timeout,
        'max_retries'           => $max_retries,
        'custom_id'             => $custom_id,
        'history_data'          => $history_data,
        'dynamic_interval'      => $dynamic_interval,
        'dynamic_max'           => $dynamic_max,
        'dynamic_min'           => $dynamic_min,
        'dynamic_two_tailed'    => $dynamic_two_tailed,
        'parent_module_id'      => $parent_module_id,
        'min_warning'           => $min_warning,
        'max_warning'           => $max_warning,
        'str_warning'           => $str_warning,
        'min_critical'          => $min_critical,
        'max_critical'          => $max_critical,
        'str_critical'          => $str_critical,
        'custom_string_1'       => $custom_string_1,
        'custom_string_2'       => $custom_string_2,
        'custom_string_3'       => $custom_string_3,
        'custom_integer_1'      => $custom_integer_1,
        'custom_integer_2'      => $custom_integer_2,
        'min_ff_event'          => $ff_event,
        'min_ff_event_normal'   => $ff_event_normal,
        'min_ff_event_warning'  => $ff_event_warning,
        'min_ff_event_critical' => $ff_event_critical,
        'ff_type'               => $ff_type,
        'each_ff'               => $each_ff,
        'ff_timeout'            => $ff_timeout,
        'unit'                  => io_safe_output($unit),
        'macros'                => $macros,
        'quiet'                 => $quiet_module,
        'cps'                   => $cps_module,
        'critical_instructions' => $critical_instructions,
        'warning_instructions'  => $warning_instructions,
        'unknown_instructions'  => $unknown_instructions,
        'critical_inverse'      => $critical_inverse,
        'warning_inverse'       => $warning_inverse,
        'percentage_critical'   => $percentage_critical,
        'percentage_warning'    => $percentage_warning,
        'cron_interval'         => $cron_interval,
        'id_category'           => $id_category,
        'disabled_types_event'  => addslashes($disabled_types_event),
        'module_macros'         => $module_macros,
    ];


    if ($id_module_type === 30 || $id_module_type === 31 || $id_module_type === 32 || $id_module_type === 33) {
        $plugin_parameter_split = explode('&#x0a;', $values['plugin_parameter']);

        $values['plugin_parameter'] = '';

        foreach ($plugin_parameter_split as $key => $value) {
            if ((int) $key === 1) {
                if ($http_user) {
                    $values['plugin_parameter'] .= 'http_auth_user&#x20;'.$http_user.'&#x0a;';
                }

                if ($http_pass) {
                    $values['plugin_parameter'] .= 'http_auth_pass&#x20;'.$http_pass.'&#x0a;';
                }
            }

            $values['plugin_parameter'] .= $value.'&#x0a;';
        }
    }


    // In local modules, the interval is updated by agent.
    $module_kind = $moduletype;
    if ($module_kind === MODULE_DATA) {
        unset($values['module_interval']);
    }

    if ($prediction_module === MODULE_PREDICTION_SYNTHETIC
        && empty($serialize_ops) === true
    ) {
        $result = false;
    } else {
        $check_dynamic = db_get_row_sql(
            'SELECT dynamic_interval, dynamic_max, dynamic_min, dynamic_two_tailed
							FROM tagente_modulo WHERE id_agente_modulo ='.$id_agent_module
        );

        if (((int) $check_dynamic['dynamic_interval'] === $dynamic_interval)
            && ((int) $check_dynamic['dynamic_max'] === $dynamic_max)
            && ((int) $check_dynamic['dynamic_min'] === $dynamic_min)
            && ((int) $check_dynamic['dynamic_two_tailed'] === $dynamic_two_tailed)
        ) {
            $result = modules_update_agent_module($id_agent_module, $values, false, $id_tag);
        } else {
            $values['dynamic_next'] = 0;
            $result = modules_update_agent_module($id_agent_module, $values, false, $id_tag);
        }
    }

    if (is_error($result) === true) {
        switch ($result) {
            case ERR_EXIST:
                $msg = __('There was a problem updating module. Another module already exists with the same name.');
            break;

            case ERR_INCOMPLETE:
                $msg = __('There was a problem updating module. Some required fields are missed: (name)');
            break;

            case ERR_NOCHANGES:
                $msg = __('There was a problem updating module. "No change"');
            break;

            case ERR_DB:
            case ERR_GENERIC:
            default:
                $msg = __('There was a problem updating module. Processing error');
            break;
        }

        // I save the result of the action (insert or update).
        $success_action = $result;
        $result = false;
        ui_print_error_message($msg);

        $edit_module = true;

        db_pandora_audit(
            AUDIT_LOG_AGENT_MANAGEMENT,
            "Fail to try update module '".io_safe_output($name)."' for agent ".io_safe_output($agent['alias'])
        );
    } else {
        if ($prediction_module == MODULE_PREDICTION_SYNTHETIC) {
            enterprise_hook(
                'modules_create_synthetic_operations',
                [
                    $id_agent_module,
                    $serialize_ops,
                ]
            );
        }

        // Update the module interval.
        cron_update_module_interval($id_agent_module, $cron_interval);

        ui_print_success_message(__('Module successfully updated'));
        $id_agent_module = false;
        $edit_module = false;

        $agent = db_get_row('tagente', 'id_agente', $id_agente);

        db_pandora_audit(
            AUDIT_LOG_AGENT_MANAGEMENT,
            "Updated module '".io_safe_output($name)."' for agent ".io_safe_output($agent['alias']),
            false,
            false,
            io_json_mb_encode($values)
        );
    }
}

// MODULE INSERT.
if ($create_module === true) {
    // Old configuration data must always be empty in case of creation.
    $old_configuration_data = '';

    if (empty($snmp_oid) === true) {
        $snmp_oid = $combo_snmp_oid;
    }

    $values = [
        'id_tipo_modulo'        => $id_module_type,
        'descripcion'           => $description,
        'max'                   => $max,
        'min'                   => $min,
        'snmp_oid'              => $snmp_oid,
        'snmp_community'        => $snmp_community,
        'id_module_group'       => $id_module_group,
        'module_interval'       => $interval,
        'module_ff_interval'    => $ff_interval,
        'ip_target'             => $ip_target,
        'tcp_port'              => $tcp_port,
        'tcp_rcv'               => $tcp_rcv,
        'tcp_send'              => $tcp_send,
        'id_export'             => $id_export,
        'plugin_user'           => $plugin_user,
        'plugin_pass'           => $plugin_pass,
        'plugin_parameter'      => $plugin_parameter,
        'id_plugin'             => $id_plugin,
        'post_process'          => $post_process,
        'prediction_module'     => $prediction_module,
        'max_timeout'           => $max_timeout,
        'max_retries'           => $max_retries,
        'disabled'              => $disabled,
        'id_modulo'             => $id_module,
        'custom_id'             => $custom_id,
        'history_data'          => $history_data,
        'dynamic_interval'      => $dynamic_interval,
        'dynamic_max'           => $dynamic_max,
        'dynamic_min'           => $dynamic_min,
        'dynamic_two_tailed'    => $dynamic_two_tailed,
        'parent_module_id'      => $parent_module_id,
        'min_warning'           => $min_warning,
        'max_warning'           => $max_warning,
        'str_warning'           => $str_warning,
        'min_critical'          => $min_critical,
        'max_critical'          => $max_critical,
        'str_critical'          => $str_critical,
        'custom_string_1'       => $custom_string_1,
        'custom_string_2'       => $custom_string_2,
        'custom_string_3'       => $custom_string_3,
        'custom_integer_1'      => $custom_integer_1,
        'custom_integer_2'      => $custom_integer_2,
        'min_ff_event'          => $ff_event,
        'min_ff_event_normal'   => $ff_event_normal,
        'min_ff_event_warning'  => $ff_event_warning,
        'min_ff_event_critical' => $ff_event_critical,
        'ff_type'               => $ff_type,
        'each_ff'               => $each_ff,
        'ff_timeout'            => $ff_timeout,
        'unit'                  => io_safe_output($unit),
        'macros'                => $macros,
        'quiet'                 => $quiet_module,
        'cps'                   => $cps_module,
        'critical_instructions' => $critical_instructions,
        'warning_instructions'  => $warning_instructions,
        'unknown_instructions'  => $unknown_instructions,
        'critical_inverse'      => $critical_inverse,
        'warning_inverse'       => $warning_inverse,
        'percentage_critical'   => $percentage_critical,
        'percentage_warning'    => $percentage_warning,
        'cron_interval'         => $cron_interval,
        'id_category'           => $id_category,
        'disabled_types_event'  => addslashes($disabled_types_event),
        'module_macros'         => $module_macros,
    ];

    if ($id_module_type == 30 || $id_module_type == 31 || $id_module_type == 32 || $id_module_type == 33) {
        $plugin_parameter_split = explode('&#x0a;', $values['plugin_parameter']);

        $values['plugin_parameter'] = '';

        foreach ($plugin_parameter_split as $key => $value) {
            if ($key == 1) {
                if ($http_user) {
                    $values['plugin_parameter'] .= 'http_auth_user&#x20;'.$http_user.'&#x0a;';
                }

                if ($http_pass) {
                    $values['plugin_parameter'] .= 'http_auth_pass&#x20;'.$http_pass.'&#x0a;';
                }

                $values['plugin_parameter'] .= $value.'&#x0a;';
            } else {
                $values['plugin_parameter'] .= $value.'&#x0a;';
            }
        }
    }

    if ($prediction_module === MODULE_PREDICTION_SYNTHETIC && empty($serialize_ops) === true) {
        $id_agent_module = false;
    } else {
        $id_agent_module = modules_create_agent_module(
            $id_agente,
            $name,
            $values,
            false,
            $id_tag
        );
    }

    if (is_error($id_agent_module) === true) {
        switch ($id_agent_module) {
            case ERR_EXIST:
                $msg = __('There was a problem adding module. Another module already exists with the same name.');
            break;

            case ERR_INCOMPLETE:
                $msg = __('There was a problem adding module. Some required fields are missed : (name)');
            break;

            case ERR_DB:
            case ERR_GENERIC:
            default:
                $msg = __('There was a problem adding module. Processing error');
            break;
        }

        // I save the result of the action (insert or update).
        $success_action = $id_agent_module;

        $id_agent_module = false;
        ui_print_error_message($msg);
        $edit_module = true;
        $moduletype = $id_module;
        db_pandora_audit(
            AUDIT_LOG_AGENT_MANAGEMENT,
            "Fail to try added module '".io_safe_output($name)."' for agent ".io_safe_output($agent['alias'])
        );
    } else {
        if ($prediction_module == MODULE_PREDICTION_SYNTHETIC) {
            enterprise_hook(
                'modules_create_synthetic_operations',
                [
                    $id_agent_module,
                    $serialize_ops,
                ]
            );
        }

        // Update the module interval.
        cron_update_module_interval($id_agent_module, $cron_interval);

        ui_print_success_message(__('Module added successfully'));
        $id_agent_module = false;
        $edit_module = false;

        $info = '';

        $agent = db_get_row('tagente', 'id_agente', $id_agente);
        db_pandora_audit(
            AUDIT_LOG_AGENT_MANAGEMENT,
            "Added module '".io_safe_output($name)."' for agent ".io_safe_output($agent['alias']),
            false,
            true,
            io_json_mb_encode($values)
        );
    }
}

// MODULE ENABLE/DISABLE
// =====================.
if ($enable_module === true) {
    $result = modules_change_disabled($enable_module, 0);
    $module_name = modules_get_agentmodule_name($enable_module);

    // Write for conf disable if remote_config.
    $configuration_data = enterprise_hook(
        'config_agents_get_module_from_conf',
        [
            $id_agente,
            io_safe_output($module_name),
        ]
    );
    // Force disable.
    $disabled = 0;

    // Force Update when disabled for save disabled in conf.
    $old_configuration_data = $configuration_data;

    // Successfull action.
    $success_action = $result;

    $success_action = $result;
    if ($result === NOERR) {
        db_pandora_audit(
            AUDIT_LOG_MODULE_MANAGEMENT,
            'Enable #'.$enable_module.' | '.$module_name.' | '.io_safe_output($agent['alias'])
        );
    } else {
        db_pandora_audit(
            AUDIT_LOG_MODULE_MANAGEMENT,
            'Fail to enable #'.$enable_module.' | '.$module_name.' | '.io_safe_output($agent['alias'])
        );
    }
}

if ($disable_module === true) {
    $result = modules_change_disabled($disable_module, 1);
    $module_name = modules_get_agentmodule_name($disable_module);

    // Write for conf disable if remote_config.
    $configuration_data = enterprise_hook(
        'config_agents_get_module_from_conf',
        [
            $id_agente,
            io_safe_output($module_name),
        ]
    );

    // Force disable.
    $disabled = 1;

    // Force Update when disabled for save disabled in conf.
    $old_configuration_data = $configuration_data;

    // Successfull action.
    $success_action = $result;

    if ($result === NOERR) {
        db_pandora_audit(
            AUDIT_LOG_MODULE_MANAGEMENT,
            'Disable #'.$disable_module.' | '.$module_name.' | '.io_safe_output($agent['alias'])
        );
    } else {
        db_pandora_audit(
            AUDIT_LOG_MODULE_MANAGEMENT,
            'Fail to disable #'.$disable_module.' | '.$module_name.' | '.io_safe_output($agent['alias'])
        );
    }
}

// Fix to stop the module from being added to the agent's conf
// when an error occurred while updating or inserting. or enable disable module.
if ($update_module || $create_module
    || $enable_module || $disable_module
) {
    if ((!$module_in_policy && !$module_linked)
        || ($module_in_policy && !$module_linked)
    ) {
        if ($success_action > 0) {
            enterprise_hook(
                'config_agents_write_module_in_conf',
                [
                    $id_agente,
                    io_safe_output($old_configuration_data),
                    io_safe_output($configuration_data),
                    $disabled,
                ]
            );
        }
    }
}

// MODULE DELETION
// =================.
if ($delete_module === true) {
    // DELETE agent module !
    $module_data = db_get_row_sql(
        'SELECT tam.id_agente, tam.nombre
		FROM tagente_modulo tam, tagente_estado tae
		WHERE tam.id_agente_modulo = tae.id_agente_modulo
			AND tam.id_agente_modulo = '.$id_agent_module
    );
    $id_grupo = (int) agents_get_agent_group($id_agente);
    $all_groups = agents_get_all_groups_agent($id_agente, $id_grupo);

    if ((bool) check_acl_one_of_groups($config['id_user'], $all_groups, 'AW') === false) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to delete a module without admin rights'
        );
        include 'general/noaccess.php';

        exit;
    }

    if (empty($module_data) === true || $id_agent_module < 1) {
        db_pandora_audit(
            AUDIT_LOG_HACK_ATTEMPT,
            'Expected variable from form is not correct'
        );
        include 'general/noaccess.php';

        exit;
    }

    // Also call base function to delete modules.
    modules_delete_agent_module($id_agent_module);

    // Check for errors.
    if (isset($error) === true && (empty($error) === false || $error !== 0)) {
        ui_print_error_message(__('There was a problem deleting the module'));
    } else {
        echo '<script type="text/javascript">
		location="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=module&id_agente='.$id_agente.'";
		alert("'.__('Module deleted succesfully').'");
		</script>';

        $agent = db_get_row('tagente', 'id_agente', $id_agente);
        db_pandora_audit(
            AUDIT_LOG_AGENT_MANAGEMENT,
            "Deleted module '".io_safe_output($module_data['nombre'])."' for agent ".io_safe_output($agent['alias'])
        );
    }
}

// MODULE DUPLICATION.
if (empty($duplicate_module) === false) {
    // DUPLICATE agent module !
    $id_duplicate_module = $duplicate_module;

    $original_name = modules_get_agentmodule_name($id_duplicate_module);
    $copy_name = io_safe_input(sprintf(__('copy of %s'), io_safe_output($original_name)));

    $cont = 0;
    $exists = true;
    while ($exists === true) {
        $exists = (bool) db_get_value(
            'id_agente_modulo',
            'tagente_modulo',
            'nombre',
            $copy_name
        );
        if ($exists === true) {
            $cont++;
            $copy_name = io_safe_input(
                sprintf(__('copy of %s (%d)'), io_safe_output($original_name), $cont)
            );
        }
    }

    $result = modules_copy_agent_module_to_agent(
        $id_duplicate_module,
        modules_get_agentmodule_agent($id_duplicate_module),
        $copy_name
    );

    $agent = db_get_row('tagente', 'id_agente', $id_agente);

    if ((bool) $result === true) {
        db_pandora_audit(
            AUDIT_LOG_AGENT_MANAGEMENT,
            "Duplicate module '".$id_duplicate_module."' for agent ".$agent['alias'].' with the new id for clon '.$result
        );
    } else {
        db_pandora_audit(
            AUDIT_LOG_AGENT_MANAGEMENT,
            "Fail to try duplicate module '".$id_duplicate_module."' for agent ".$agent['alias']
        );
    }
}

// MODULE ENABLE/DISABLE.
if ($enable_module === true) {
    $result = modules_change_disabled($enable_module, 0);
    $modulo_nombre = db_get_row_sql('SELECT nombre FROM tagente_modulo WHERE id_agente_modulo = '.$enable_module);
    $modulo_nombre = io_safe_output($modulo_nombre['nombre']);

    if ($result === NOERR) {
        enterprise_hook('config_agents_enable_module_conf', [$id_agente, $enable_module]);
        db_pandora_audit(
            AUDIT_LOG_MODULE_MANAGEMENT,
            'Enable #'.$enable_module.' | '.$modulo_nombre.' | '.$agent['alias']
        );
    } else {
        db_pandora_audit(
            AUDIT_LOG_MODULE_MANAGEMENT,
            'Fail to enable #'.$enable_module.' | '.$modulo_nombre.' | '.$agent['alias']
        );
    }

    ui_print_result_message(
        $result,
        __('Successfully enabled'),
        __('Could not be enabled')
    );
}

if ($disable_module === true) {
    $result = modules_change_disabled($disable_module, 1);
    $modulo_nombre = db_get_row_sql('SELECT nombre FROM tagente_modulo WHERE id_agente_modulo = '.$disable_module);
    $modulo_nombre = io_safe_output($modulo_nombre['nombre']);

    if ($result === NOERR) {
        enterprise_hook('config_agents_disable_module_conf', [$id_agente, $disable_module]);
        db_pandora_audit(
            AUDIT_LOG_MODULE_MANAGEMENT,
            'Disable #'.$disable_module.' | '.$modulo_nombre.' | '.$agent['alias']
        );
    } else {
        db_pandora_audit(
            AUDIT_LOG_MODULE_MANAGEMENT,
            'Fail to disable #'.$disable_module.' | '.$modulo_nombre.' | '.$agent['alias']
        );
    }

    ui_print_result_message(
        $result,
        __('Successfully disabled'),
        __('Could not be disabled')
    );
}

// UPDATE GIS.
if ($updateGIS === true) {
    $previusAgentGISData = db_get_row_sql(
        'SELECT *
		FROM tgis_data_status
		WHERE tagente_id_agente = '.$idAgente
    );

    db_process_sql_update(
        'tagente',
        ['update_gis_data' => $updateGisData],
        ['id_agente' => $idAgente]
    );

    if ($previusAgentGISData !== false) {
        db_process_sql_insert(
            'tgis_data_history',
            [
                'longitude'          => $previusAgentGISData['stored_longitude'],
                'latitude'           => $previusAgentGISData['stored_latitude'],
                'altitude'           => $previusAgentGISData['stored_altitude'],
                'start_timestamp'    => $previusAgentGISData['start_timestamp'],
                'end_timestamp'      => date('Y-m-d H:i:s'),
                'description'        => __('Save by %s Console', get_product_name()),
                'manual_placement'   => $previusAgentGISData['manual_placement'],
                'number_of_packages' => $previusAgentGISData['number_of_packages'],
                'tagente_id_agente'  => $previusAgentGISData['tagente_id_agente'],
            ]
        );
        db_process_sql_update(
            'tgis_data_status',
            [
                'tagente_id_agente' => $idAgente,
                'current_longitude' => $lastLongitude,
                'current_latitude'  => $lastLatitude,
                'current_altitude'  => $lastAltitude,
                'stored_longitude'  => $lastLongitude,
                'stored_latitude'   => $lastLatitude,
                'stored_altitude'   => $lastAltitude,
                'start_timestamp'   => date('Y-m-d H:i:s'),
                'manual_placement'  => 1,
                'description'       => __('Update by %s Console', get_product_name()),
            ],
            ['tagente_id_agente' => $idAgente]
        );
    } else {
        db_process_sql_insert(
            'tgis_data_status',
            [
                'tagente_id_agente' => $idAgente,
                'current_longitude' => $lastLongitude,
                'current_latitude'  => $lastLatitude,
                'current_altitude'  => $lastAltitude,
                'stored_longitude'  => $lastLongitude,
                'stored_latitude'   => $lastLatitude,
                'stored_altitude'   => $lastAltitude,
                'manual_placement'  => 1,
                'description'       => __('Insert by %s Console', get_product_name()),
            ]
        );
    }
}

// -----------------------------------
// Load page depending on tab selected
// -----------------------------------
if ($_SESSION['create_module'] && $config['welcome_state'] == 1) {
    $edit_module = true;
}

switch ($tab) {
    case 'main':
        include 'agent_manager.php';
    break;

    case 'module':
        if ($id_agent_module || $edit_module) {
            include 'module_manager_editor.php';
        } else {
            include 'module_manager.php';
        }
    break;

    case 'alert':
        /*
         * Because $id_agente is set, it will show only agent alerts
         * This var is for not display create button on alert list
         */

        $dont_display_alert_create_bttn = true;
        include 'godmode/alerts/alert_list.php';
    break;

    case 'template':
        include 'agent_template.php';
    break;

    case 'gis':
        include 'agent_conf_gis.php';
    break;

    case 'incident':
        include 'agent_incidents.php';
    break;

    case 'ncm':
        enterprise_hook('ncm_agent_tab', [$id_agente]);
    break;

    case 'remote_configuration':
        enterprise_include('godmode/agentes/agent_disk_conf_editor.php');
    break;

    case 'extension':
        $found = false;
        foreach ($config['extensions'] as $extension) {
            if (isset($extension['extension_god_tab']) === true) {
                $id = $extension['extension_god_tab']['id'];
                $function = $extension['extension_god_tab']['function'];

                if ($id_extension === (int) $id) {
                    call_user_func_array($function, []);
                    $found = true;
                }
            }
        }

        if ($found === false) {
            ui_print_error_message(__('Invalid tab specified'));
        }
    break;

    case 'agent_wizard':
        include 'agent_wizard.php';
    break;

    default:
        if ((bool) enterprise_hook('switch_agent_tab', [$tab]) === true) {
            // This will make sure that blank pages will have at least some
            // debug info in them - do not translate debug.
            ui_print_error_message(__('Invalid tab specified'));
        }
    break;
}

?>

<script type="text/javascript">
    /* <![CDATA[ */
    var wizard_tab_showed = 0;
    $(document).ready (function () {
        $('body').append('<div id="dialog"></div>');
        // Control the tab and subtab hover. When mouse leave one, 
        // check if is hover the other before hide the subtab
        $('.agent_wizard_tab').hover(agent_wizard_tab_show, agent_wizard_tab_hide);

        $('#module_form').submit(function() {

            var aget_id_os = '<?php echo agents_get_os(modules_get_agentmodule_agent($id_agent_module)); ?>';

            if('<?php echo io_safe_output(modules_get_agentmodule_name($id_agent_module)); ?>' != $('#text-name').val() &&
             '<?php echo agents_get_os(modules_get_agentmodule_agent($id_agent_module)); ?>' == 19){

                event.preventDefault();

                $("#dialog").dialog({
                    resizable: true,
                    draggable: true,
                    modal: true,
                    height: 240,
                    width: 600,
                    title: 'Changing the module name of a satellite agent',
                    open: function(){
                            $('#dialog').html('<br><table><tr><td><img src="images/icono-warning-triangulo.png" class="float-left mrgn_lft_25px"></td><td><p id="p_configurar_agente" ><span class="font_bold font_12pt">Warning</span> <br>The names of the modules of a satellite should not be altered manually. Unless you are absolutely certain of the process, do not alter these names.</p></td></tr></table>');
                    },
                    buttons: [{
                            text: "Ok",
                            click: function() {
                                $('#module_form').submit();
                            }
                        },
                        {
                        text: "Cancel",
                        click: function() {
                            $( this ).dialog( "close" );
                            return false;
                        }
                    }]
                });
            }

            var module_type_snmp =  '<?php echo modules_get_agentmodule_type($id_agent_module); ?>';

            if('<?php echo io_safe_output(modules_get_agentmodule_name($id_agent_module)); ?>' != $('#text-name').val() && (
                module_type_snmp == 15 || module_type_snmp == 16 || module_type_snmp == 17 || module_type_snmp == 18)){
                    event.preventDefault();
                    $("#dialog").dialog({
                        resizable: true,
                        draggable: true,
                        modal: true,
                        height: 240,
                        width: 650,
                        title: 'Changing snmp module name',
                        open: function(){
                                $('#dialog').html('<br><table><tr><td><img src="images/icono-warning-triangulo.png" class="warning_triangulo"></td><td><p id="p_configurar_agente"><span class="font_bold font_12pt">Warning</span> <br>                     If you change the name of this module, various features associated with this module, such as network maps, interface graphs or other network modules, may  no longer work. If you are not completely sure of the process, please do not change the name of the module.                    </p></td></tr></table>');
                        },
                        buttons: [{
                          text: "Ok",
                          click: function() {
                            $('#module_form').submit();
                          }
                        },
                            {
                            text: "Cancel",
                            click: function() {
                                $( this ).dialog( "close" );
                                return false;
                            }
                        }]
                    });
            }
    });
    });

    function agent_wizard_tab_show() {
        wizard_tab_showed = wizard_tab_showed + 1;

        if(wizard_tab_showed == 1) {
            $('.subsubmenu').show("fast");
        }
    }

    function agent_wizard_tab_hide() {
        wizard_tab_showed = wizard_tab_showed - 1;

        setTimeout(function() {
            if(wizard_tab_showed <= 0) {
                $('.subsubmenu').hide("fast");
            }
        },15000);
    }

    /* ]]> */
</script>
