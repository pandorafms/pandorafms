<?php
/**
 * Agent Creation / Edition view
 *
 * @category   Agent editor/ builder.
 * @package    Pandora FMS
 * @subpackage Classic agent management view.
 * @version    2.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 * |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 * |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 Pandora FMS
 * Please see https://pandorafms.com/community/ for full contribution list
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
enterprise_include('godmode/agentes/agent_manager.php');

require_once 'include/functions_clippy.php';
require_once 'include/functions_servers.php';
require_once 'include/functions_gis.php';
require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_users.php';

if (is_ajax() === true) {
    global $config;

    $search_parents_2 = (bool) get_parameter('search_parents_2');

    if ($search_parents_2) {
        include_once 'include/functions_agents.php';

        $id_agent = (int) get_parameter('id_agent');
        $string = (string) get_parameter('q');
        // Field q is what autocomplete plugin gives.
        $filter = [];
        $filter[] = '(nombre LIKE "%'.$string.'%" OR direccion LIKE "%'.$string.'%" OR comentarios LIKE "%'.$string.'%" OR alias LIKE "%'.$string.'%")';
        $filter[] = 'id_agente != '.$id_agent;

        $agents = agents_get_agents(
            $filter,
            [
                'id_agente',
                'nombre',
                'direccion',
            ]
        );
        if ($agents === false) {
            $agents = [];
        }

        $data = [];
        foreach ($agents as $agent) {
            $data[] = [
                'id'   => $agent['id_agente'],
                'name' => io_safe_output($agent['nombre']),
                'ip'   => io_safe_output($agent['direccion']),
            ];
        }

        echo io_json_mb_encode($data);

        return;
    }

    $get_modules_json_for_multiple_snmp = (bool) get_parameter('get_modules_json_for_multiple_snmp', 0);
    $get_common_modules = (bool) get_parameter('get_common_modules', 1);
    if ($get_modules_json_for_multiple_snmp) {
        include_once 'include/graphs/functions_utils.php';

        $idSNMP = get_parameter('id_snmp');

        $id_snmp_serialize = get_parameter('id_snmp_serialize');
        $snmp = unserialize_in_temp($id_snmp_serialize, false);

        $oid_snmp = [];
        $out = false;
        foreach ($idSNMP as $id) {
            foreach ($snmp[$id] as $key => $value) {
                // Check if it has "ifXXXX" syntax and skip it.
                if (! preg_match('/if/', $key)) {
                    continue;
                }

                $oid_snmp[$value['oid']] = $key;
            }

            if ($out === false) {
                $out = $oid_snmp;
            } else {
                $commons = array_intersect($out, $oid_snmp);
                if ($get_common_modules) {
                    // Common modules is selected (default).
                    $out = $commons;
                } else {
                    // All modules is selected.
                    $array1 = array_diff($out, $oid_snmp);
                    $array2 = array_diff($oid_snmp, $out);
                    $out = array_merge($commons, $array1, $array2);
                }
            }

            $oid_snmp = [];
        }

        echo io_json_mb_encode($out);
    }

    // And and remove groups use the same function.
    $add_secondary_groups = get_parameter('add_secondary_groups');
    $remove_secondary_groups = get_parameter('remove_secondary_groups');
    if ($add_secondary_groups || $remove_secondary_groups) {
        $id_agent = get_parameter('id_agent');
        $groups_to_add = get_parameter('groups');
        if (enterprise_installed()) {
            if (empty($groups_to_add)) {
                return 0;
            }

            enterprise_include('include/functions_agents.php');
            $ret = enterprise_hook(
                'agents_update_secondary_groups',
                [
                    $id_agent,
                    (($add_secondary_groups) ? $groups_to_add : []),
                    (($remove_secondary_groups) ? $groups_to_add : []),
                ]
            );
            // Echo 0 in case of error. 0 Otherwise.
            echo ((bool) $ret === true) ? 1 : 0;
        }
    }

    return;
}

ui_require_javascript_file('openlayers.pandora');

if (isset($id_agente) === false) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access agent manager witout an agent'
    );
    include 'general/noaccess.php';
    return;
}

$new_agent = (empty($id_agente) === true) ? true : false;

if ($new_agent === true) {
    if (empty($direccion_agente) === false && empty($nombre_agente) === true) {
        $nombre_agente = $direccion_agente;
    }

    $servers = servers_get_names();
    if (empty($servers) === false) {
        $array_keys_servers = array_keys($servers);
        $server_name = reset($array_keys_servers);
    }
} else {
    // Agent remote configuration editor.
    enterprise_include_once('include/functions_config_agents.php');
    if (enterprise_installed() === true) {
        $filename = config_agents_get_agent_config_filenames($id_agente);
    }
}

$disk_conf_delete = (bool) get_parameter('disk_conf_delete');
// Agent remote configuration DELETE.
if ($disk_conf_delete === true) {
    // TODO: Get this working on computers where the Pandora server(s) are not on the webserver
    // TODO: Get a remote_config editor working in the open version.
    @unlink($filename['md5']);
    @unlink($filename['conf']);
}

echo '<div class="max_floating_element_size">';
echo '<form autocomplete="new-password" name="conf_agent" id="form_agent" method="post" action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente">';

// Custom ID.
$custom_id_div = '<div class="label_select">';
$custom_id_div .= '<p class="font_10pt">'.__('Custom ID').': </p>';
$custom_id_div .= html_print_input_text(
    'custom_id',
    $custom_id,
    '',
    16,
    255,
    true,
    false,
    false,
    '',
    'agent_custom_id'
).'</div>';

// Get groups.
$groups = users_get_groups($config['id_user'], 'AR', false);

// Get modules.
$modules = db_get_all_rows_sql(
    'SELECT id_agente_modulo as id_module, nombre as name FROM tagente_modulo
								WHERE id_agente = '.$id_agente
);
$modules_values = [];
$modules_values[0] = __('Any');
if (is_array($modules) === true) {
    foreach ($modules as $m) {
        $modules_values[$m['id_module']] = $m['name'];
    }
}

// Remote configuration available.
if (isset($filename) === true && file_exists($filename['md5']) === true) {
    $remote_agent = true;
    $agent_md5 = md5(io_safe_output(agents_get_name($id_agente)), false);
} else {
    $remote_agent = false;
}

// Get Servers.
$servers = servers_get_names();
// Set the agent have not server.
if (array_key_exists($server_name, $servers) === false) {
    $server_name = 0;
}

if ($new_agent === true) {
    // Set first server by default.
    $servers_get_names = $servers;
    $array_keys_servers_get_names = array_keys($servers_get_names);
    $server_name = reset($array_keys_servers_get_names);
}


// QR Code table.
$CodeQRContent = '';
if ($new_agent === false) {
    $CodeQRContent .= html_print_div(['id' => 'qr_container_image'], true);
    $CodeQRContent .= html_print_anchor(
        [
            'id'   => 'qr_code_agent_view',
            'href' => ui_get_full_url('mobile/index.php?page=agent&id='.$id_agente),
        ],
        true
    );
    $CodeQRContent .= '<br/>'.$custom_id_div;

    // QR code div.
    $CodeQRTable = html_print_div(
        [
            'class'   => 'agent_qr',
            'content' => $CodeQRContent,
        ],
        true
    );
} else {
    $CodeQRTable = '';
}

// Advanced mode.
if (enterprise_installed() === true) {
    // Safe operation mode.
    if ($new_agent === false) {
        $sql_modules = db_get_all_rows_sql(
            'SELECT id_agente_modulo as id_module, nombre as name FROM tagente_modulo 
									WHERE id_agente = '.$id_agente
        );
        $safe_mode_modules = [];
        $safe_mode_modules[0] = __('Any');
        if (is_array($sql_modules) === true) {
            foreach ($sql_modules as $m) {
                $safe_mode_modules[$m['id_module']] = $m['name'];
            }
        }
    }

    // Calculate cps value - agents.
    if ($new_agent === false) {
        $cps_val = service_agents_cps($id_agente);
    } else {
        // No agent defined, use received cps as base value.
        if ($cps >= 0) {
            $cps_val = $cps;
        }
    }
}

// Parent agents.
$paramsParentAgent = [];
$paramsParentAgent['return'] = true;
$paramsParentAgent['show_helptip'] = true;
$paramsParentAgent['input_name'] = 'id_parent';
$paramsParentAgent['print_hidden_input_idagent'] = true;
$paramsParentAgent['hidden_input_idagent_name'] = 'id_agent_parent';
$paramsParentAgent['hidden_input_idagent_value'] = $id_parent;
$paramsParentAgent['value'] = db_get_value('alias', 'tagente', 'id_agente', $id_parent);
$paramsParentAgent['selectbox_id'] = 'cascade_protection_module';
$paramsParentAgent['javascript_is_function_select'] = true;
$paramsParentAgent['cascade_protection'] = true;
$paramsParentAgent['input_style'] = 'width: 100%;';

if ($id_agente !== 0) {
    // Deletes the agent's offspring.
    $paramsParentAgent['delete_offspring_agents'] = $id_agente;
}

$listIcons = gis_get_array_list_icons();

$arraySelectIcon = [];
foreach ($listIcons as $index => $value) {
    $arraySelectIcon[$index] = $index;
}

// Agent icons.
$path = 'images/gis_map/icons/';
// TODO set better method the path.
$table_adv_agent_icon = '<div class="label_select"><p class="input_label">'.__('Agent icon').'</p>';
if ($icon_path == '') {
    $display_icons = 'none';
    // Hack to show no icon. Use any given image to fix not found image errors.
    $path_without = 'images/spinner.gif';
    $path_default = 'images/spinner.gif';
    $path_ok = 'images/spinner.gif';
    $path_bad = 'images/spinner.gif';
    $path_warning = 'images/spinner.gif';
} else {
    $display_icons = '';
    $path_without = $path.$icon_path.'.default.png';
    $path_default = $path.$icon_path.'.default.png';
    $path_ok = $path.$icon_path.'.ok.png';
    $path_bad = $path.$icon_path.'.bad.png';
    $path_warning = $path.$icon_path.'.warning.png';
}

$tableAgent = new stdClass();
$tableAgent->class = 'floating_form primary_form';
$tableAgent->data = [];
$tableAgent->style = [];
$tableAgent->cellclass = [];
$tableAgent->colspan = [];
$tableAgent->rowspan = [];

// Agent name.
if ($new_agent === false) {
    $tableAgent->data['caption_name'][0] = __('Agent name').ui_print_help_tip(__('The agent name is a unique identifier (which is automatically generated in software agents). If you set it manually, make sure it is not duplicated. Although it accepts spaces, we do not recommend you to use them in the agent name, you may use them in the agent alias. The agent name is not displayed in the interface, it is an internal name.'), true);
    $tableAgent->rowclass['name'] = 'w540px';
    $tableAgent->cellstyle['name'][0] = 'width: 100%;';
    $tableAgent->data['name'][0] = html_print_input_text(
        'agente',
        $nombre_agente,
        '',
        76,
        100,
        true,
        true,
        false,
        '',
        'w100p'
    );
    $tableAgent->data['name'][0] .= html_print_div(
        [
            'class'   => 'moduleIdBox',
            'content' => __('ID').'&nbsp;<span class="font_14pt">'.$id_agente.'</span>',
        ],
        true
    );

    // Other than Linux, Solaris, AIX, BSD, HPUX, MacOs, and Windows.
    if ($id_os !== LINUX && $id_os !== SOLARIS && $id_os !== AIX
        && $id_os !== BSD && $id_os !== HPUX && $id_os !== MACOS
        && $id_os !== WINDOWS
    ) {
        $tableAgent->data['name'][0] .= html_print_anchor(
            [
                'content' => html_print_image(
                    'images/edit.svg',
                    true,
                    [
                        'border'  => 0,
                        'title'   => __('Edit agent name'),
                        'class'   => 'main_menu_icon invert_filter after_input_icon forced_title clickable',
                        'onclick' => 'editAgent()',
                    ]
                ),
            ],
            true
        );
    }

    // Agent options for QR code.
    $agent_options_update = 'agent_options_update';
}

// Alias.
$tableAgent->data['caption_alias'][0] = __('Alias').ui_print_help_tip(__('This will be the text label that will represent the agent on all types of screens and reports. It may be duplicated, contain spaces and non-ASCII characters.'), true);
$tableAgent->rowclass['alias'] = 'w540px';
$tableAgent->data['alias'][0] = html_print_input_text('alias', $alias, '', 50, 100, true, false, true, '', 'w540px');
if ($new_agent === true) {
    $tableAgent->rowclass['additional_alias'] = 'subinput';
    $tableAgent->data['additional_alias'][0] = html_print_checkbox_switch('alias_as_name', 1, $config['alias_as_name'], true);
    $tableAgent->data['additional_alias'][1] = __('Use alias as name');
} else {
    if ($remote_agent === true) {
        $tableAgent->data['alias'][0] .= html_print_anchor(
            [
                'href'    => 'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=remote_configuration&id_agente='.$id_agente.'&disk_conf='.$agent_md5,
                'content' => html_print_image(
                    'images/remote-configuration@svg.svg',
                    true,
                    [
                        'border' => 0,
                        'title'  => __('This agent can be remotely configured'),
                        'class'  => 'invert_filter after_input_icon',
                    ]
                ),
            ],
            true
        );
    }
}

// Ip adress.
$tableAgent->data['caption_ip_address'] = __('IP Address');
$tableAgent->rowclass['ip_address'] = 'w400px';
$tableAgent->data['ip_address'][0] = html_print_input_text('direccion', $direccion_agente, '', 16, 100, true, false, false, '', 'w540px');
$tableAgent->data['ip_address'][1] = html_print_button(__('Check unique IP'), 'check_unique_ip', false, '', ['class' => 'secondary w130px'], true);
$tableAgent->data['message_check_ip'][0] = html_print_div(['id' => 'message_check_ip'], true);

$tableAgent->rowclass['additional_ip_address'] = 'subinput';
$tableAgent->cellclass['additional_ip_address'][1] = 'w120px';
$tableAgent->data['additional_ip_address'][2] = html_print_input(
    [
        'type'  => 'switch',
        'id'    => 'fixed_ip',
        'name'  => 'fixed_ip',
        'value' => $fixed_ip,
    ]
);

$table_ip .= '</div></div>';

if ($id_agente) {
    $ip_all = agents_get_addresses($id_agente);

    $table_ip .= '<div class="label_select">';
    $table_ip .= '<div class="label_select_parent">';
    $table_ip .= '<div class="label_select_child_left">'.html_print_select($ip_all, 'address_list', $direccion_agente, '', '', 0, true).'</div>';
    $table_ip .= '<div class="label_select_child_right">'.html_print_checkbox_switch('delete_ip', 1, false, true).__('Delete selected IPs').'</div>';
    $table_ip .= '</div></div>';
}

?>
<style type="text/css">
    #qr_code_agent_view img {
        display: inline !important;
    }
</style>
<?php
$groups = users_get_groups($config['id_user'], 'AR', false);

$modules = db_get_all_rows_sql(
    'SELECT id_agente_modulo as id_module, nombre as name FROM tagente_modulo 
								WHERE id_agente = '.$id_parent
);
$tableAgent->data['additional_ip_address'][3] = __('Fix IP address');
$tableAgent->data['additional_ip_address'][3] .= ui_print_help_tip(__('Avoid automatic IP address update when agent IP changes.'), true);

// IP Address List.
if ($new_agent === false) {
    $tableAgent->data['caption_ip_address_list'] = __('IP Address list');
    $tableAgent->data['ip_address_list'][0] = html_print_select(agents_get_addresses($id_agente), 'address_list', $direccion_agente, '', '', 0, true, false, true, 'w540px');
    $tableAgent->rowclass['additional_ip_address_list'] = 'subinput';
    $tableAgent->data['additional_ip_address_list'][0] = html_print_checkbox_switch('delete_ip', 1, false, true);
    $tableAgent->data['additional_ip_address_list'][1] = __('Delete selected IPs');
}

// Select primary group.
$tableAgent->data['caption_primary_group'][0] = __('Primary group').ui_print_help_tip(__('Although an agent can belong to multiple groups, it can only have a parent group.'), true);
if (isset($groups[$grupo]) === true || $new_agent === true) {
    $tableAgent->rowclass['primary_group'] = 'w540px';
    // Cannot change primary group if user have not permission for that group.
    $tableAgent->data['primary_group'][0] = html_print_select_groups(
        false,
        'AW',
        false,
        'grupo',
        $grupo,
        '',
        '',
        0,
        true,
        false,
        true,
        '',
        false,
        '',
        '',
        false,
        'id_grupo',
        false,
        false,
        false,
        '540px',
        false,
        true,
    );
} else {
    $tableAgent->data['primary_group'][0] .= groups_get_name($grupo);
    $tableAgent->data['primary_group'][0] .= html_print_input_hidden('grupo', $grupo, true);
}

$tableAgent->data['primary_group'][0] .= '<span id="group_preview">';
$tableAgent->data['primary_group'][0] .= ui_print_group_icon(
    $grupo,
    true,
    '',
    ($id_agente === 0) ? 'display: none;' : '',
    true,
    false,
    false,
    'after_input_icon'
);
$tableAgent->data['primary_group'][0] .= '</span>';

$broker = false;
if (enterprise_installed()) {
    // CHECK BROKER FOR SHOW INTERVAL.
    enterprise_include('include/functions_config_agents.php');
    // Read configuration file.
    $files = config_agents_get_agent_config_filenames($id_agente);
    $file_name = $files['conf'];
    if (empty($file_name) === false) {
        $agent_config = file_get_contents($file_name);
        $encoding = 'UTF-8';
        $agent_config_utf8 = mb_convert_encoding($agent_config, 'UTF-8', $encoding);
        if ($agent_config_utf8 !== false) {
            $agent_config = $agent_config_utf8;
        }

        $broker = str_contains($agent_config, '#broker active');
    }
}

if ($broker === false) {
    $tableAgent->data['caption_interval'][0] = __('Interval').ui_print_help_tip(__('Time that elapses when updating data in the agent. Remote modules have their own interval, but this time is used to find out if an agent stopped responding (unknown state). When twice the time interval defined in an agent goes by, it is considered to be in unknown state (or also if all its remote modules are in unknown state). An agent may be in unknown state if all of its local (software agent-based) modules have a last contact time longer than twice the agent interval, even if it has updated remote modules.'), true);
    // $tableAgent->rowstyle['interval'] = 'width: 260px';
    $tableAgent->rowclass['interval'] = 'w540px';
    $tableAgent->data['interval'][0] = html_print_extended_select_for_time(
        'intervalo',
        $intervalo,
        '',
        '',
        '0',
        10,
        true,
        false,
        true,
        'w33p'
    );
}

if ($intervalo < SECONDS_5MINUTES) {
    $tableAgent->data['interval'][0] .= clippy_context_help('interval_agent_min');
}

$tableAgent->data['caption_os'][0] = __('OS');
$tableAgent->rowclass['os'] = 'w540px';
$tableAgent->data['os'][0] = html_print_select_from_sql(
    'SELECT id_os, name FROM tconfig_os',
    'id_os',
    $id_os,
    '',
    '',
    '0',
    true,
    false,
    true,
    false,
    'width: 540px;'
);
$tableAgent->data['os'][0] .= html_print_div(
    [
        'class'   => 'after_input_icon',
        'id'      => 'os_preview',
        'content' => ui_print_os_icon(
            $id_os,
            false,
            true
        ),
    ],
    true
);

$tableAgent->data['caption_os_version'][0] = __('OS version');
$tableAgent->rowclass['os_version'] = 'w540px';
$tableAgent->data['os_version'][0] = html_print_input_text(
    'os_version',
    $os_version,
    '',
    16,
    100,
    true,
    false,
    false,
    '',
    'w540px'
);

$tableAgent->data['caption_server'][0] = __('Server').ui_print_help_tip(__('Server that will preferentially execute remote tasks.'), true);
$tableAgent->rowclass['server'] = 'w540px';
$tableAgent->data['server'][0] = html_print_select(
    $servers,
    'server_name',
    $server_name,
    '',
    __('None'),
    0,
    true,
    false,
    true,
    'w540px',
    false,
    'width: 540px;'
);

// Description.
$tableAgent->data['caption_description'][0] = __('Description');
$tableAgent->rowclass['description'] = 'w540px';
$tableAgent->data['description'][0] = html_print_textarea(
    'comentarios',
    3,
    80,
    $comentarios,
    '',
    true,
    'agent_description w540px'
);

html_print_div(
    [
        'class'   => 'box-flat white_table_flex white_box agent_details_col',
        'style'   => 'display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px',
        'content' => html_print_table($tableAgent, true).$CodeQRTable,
    ]
);

// Basic Options.
$tableBasicAgent = new stdClass();
$tableBasicAgent->class = 'filter-table-adv';
$tableBasicAgent->data = [];
$disabledBasic = false;
$tableClassDisabled = '';
if ($new_agent === true || $remote_agent === false || $has_remote_conf === false) {
    $disabledBasic = true;
    $tableClassDisabled = ' basic-options-disabled';
}

if (enterprise_installed()) {
    // Get all plugins (BASIC OPTIONS).
    $agent_plugin = new PandoraFMS\Agent($id_agente);
    $plugins = $agent_plugin->getPlugins();
    // Check if some plugin was enabled/disabled in conf.
    foreach ($plugins as $key => $row) {
        if (preg_match('/pandora_hardening/', $row['raw']) === 1) {
            if ($row['disabled'] === 1) {
                $security_hardening = 0;
            } else {
                $security_hardening = 1;
            }
        }

        if ($id_os === '1' || $id_os === '8') {
            if (preg_match('/(module_plugin grep_log_module ).*/', $row['raw']) === 1) {
                if ($row['disabled'] === 1) {
                    $enable_log_collector = 0;
                } else {
                    $enable_log_collector = 1;
                }
            }

            if (preg_match('/(module_plugin inventory).*/', $row['raw']) === 1) {
                if ($row['disabled'] === 1) {
                    $enable_inventory = 0;
                } else {
                    $enable_inventory = 1;
                }
            }
        } else {
            if (preg_match('/.vbs/', $row['raw']) === 1 && preg_match('/nettraffic.vbs/', $row['raw']) === 0 && preg_match('/software_installed.vbs/', $row['raw']) === 0 && preg_match('/df.vbs/', $row['raw']) === 0 && preg_match('/win_cf.vbs/', $row['raw']) === 0) {
                if ($row['disabled'] === 1) {
                    $enable_inventory = 0;
                } else {
                    $enable_inventory = 1;
                }
            }
        }
    }

    if ($id_os === '1') {
        $modules = $agent_plugin->getModules();
        foreach ($modules as $key => $row) {
            if (preg_match('/Syslog/', $row['raw']) === 1) {
                if ($row['disabled'] === 1) {
                    $enable_log_collector = 0;
                } else {
                    $enable_log_collector = 1;
                }
            }
        }
    }

    if ($id_os === '9') {
        $modules = $agent_plugin->getModules();
        foreach ($modules as $key => $row) {
            if (preg_match('/PandoraAgent_log/', $row['raw']) === 1) {
                if ($row['disabled'] === 1) {
                    $enable_log_collector = 0;
                } else {
                    $enable_log_collector = 1;
                }
            }
        }
    }

    unset($agent_plugin, $plugins);
    if (($new_agent === true && $config['current_package'] >= 774) || ($agent_version >= 774 && $new_agent === false)) {
        if ($disabledBasic === true || $has_remote_conf === false) {
            $message = __('Remote config disabled, please activate to enable agent basic options');
            $tableBasicAgent->data[] = '<span>'.$message.'</span>';
        }

        $tableBasicAgent->data[] = html_print_label_input_block(
            __('Enable security hardening monitoring'),
            html_print_input(
                [
                    'type'     => 'switch',
                    'id'       => 'security_hardening',
                    'name'     => 'security_hardening',
                    'value'    => $security_hardening,
                    'disabled' => $disabledBasic,
                ]
            ).html_print_input_hidden('options_package', '1', true)
        );

        $tableBasicAgent->data[] = html_print_label_input_block(
            __('Enable log collection'),
            html_print_input(
                [
                    'type'     => 'switch',
                    'id'       => 'enable_log_collector',
                    'name'     => 'enable_log_collector',
                    'value'    => $enable_log_collector,
                    'disabled' => $disabledBasic,
                ]
            )
        );
    }

    $tableBasicAgent->data[] = html_print_label_input_block(
        __('Enable inventory'),
        html_print_input(
            [
                'type'     => 'switch',
                'id'       => 'enable_inventory',
                'name'     => 'enable_inventory',
                'value'    => $enable_inventory,
                'disabled' => $disabledBasic,
            ]
        ).html_print_input_hidden('enable_basic_options', '1', true)
    );

    if ($config['ehorus_enabled'] === '1') {
        $pandoraRC_Id = html_print_image(
            'images/alert_recovered@svg.svg',
            true,
            [
                'class' => 'invert_filter main_menu_icon',
                'title' => __('Pandora RC connected with id ').$config['ehorus_custom_field'],
            ]
        );
    } else {
        $pandoraRC_Id = html_print_image(
            'images/alerts.svg',
            true,
            [
                'class' => 'invert_filter main_menu_icon',
                'title' => __('This agent do not have a Pandora RC agent installed, install one.'),
            ]
        );
    }

    $tableBasicAgent->data[] = html_print_label_input_block(
        __('Enable remote control'),
        $pandoraRC_Id
    );

    $WarningPackage = '';
    if (($new_agent === true && $config['current_package'] < 774) || ($agent_version < 774 && $new_agent === false)) {
        $WarningPackage = html_print_image(
            'images/alert-yellow@svg.svg',
            true,
            [
                'title' => __('Only available for agents 774 or higher'),
                'alt'   => __('Only available for agents 774 or higher'),
                'class' => 'main_menu_icon mrgn_lft_5px',
            ]
        );
    }
} else {
    $tableBasicAgent->data[] = '<span>'.__('Remote config is enabled only in the Enteprise version').'</span>';
    $tableBasicAgent->data[] .= html_print_input_hidden(
        'enable_basic_options',
        '0',
        true
    );
}

ui_toggle(
    html_print_table($tableBasicAgent, true),
    '<span class="subsection_header_title">'.__('Basic options').$WarningPackage.'</span>',
    '',
    'basic_options',
    true,
    false,
    'white_box_content',
    'no-border white_table_graph'.$tableClassDisabled,
    'box-flat white_table_graph invisible'
);

// Advanced options.
$tableAdvancedAgent = new stdClass();
$tableAdvancedAgent->class = 'filter-table-adv floating_form primary_form';
$tableAdvancedAgent->data = [];
$tableAdvancedAgent->style = [];
$tableAdvancedAgent->cellclass = [];
$tableAdvancedAgent->colspan = [];
$tableAdvancedAgent->rowspan = [];

if (enterprise_installed() === true) {
    // Secondary groups.
    $tableAdvancedAgent->data['secondary_groups'][] = html_print_label_input_block(
        __('Secondary groups'),
        html_print_select_agent_secondary(
            $agent,
            $id_agente,
            ['selected_post' => $secondary_groups]
        )
    );
}

// Parent agent.
$tableAdvancedAgent->data['parent_agent'][] = html_print_label_input_block(
    __('Agent parent'),
    ui_print_agent_autocomplete_input($paramsParentAgent)
);


if (enterprise_installed() === true) {
    $cascadeProtectionContents = [];
    $cascadeProtectionContents[] = html_print_checkbox_switch(
        'cascade_protection',
        1,
        $cascade_protection,
        true
    );

    $cascadeProtectionContents[] = html_print_select(
        $modules_values,
        'cascade_protection_module',
        $cascade_protection_module,
        '',
        '',
        0,
        true,
        false,
        true,
        'w220p'
    );

    $tableAdvancedAgent->data['caption_cascade_protection'][] = html_print_label_input_block(
        __('Cascade protection modules'),
        html_print_div(
            [
                'class'   => 'flex-row-center',
                'content' => implode('', $cascadeProtectionContents),
            ],
            true
        )
    );
}

// Module Definition (Learn mode).
$switchButtons = [];
$switchButtons[] = html_print_radio_button_extended(
    'modo',
    1,
    __('Learning mode'),
    $modo,
    false,
    'show_modules_not_learning_mode_context_help();',
    '',
    true
);
$switchButtons[] = html_print_radio_button_extended(
    'modo',
    0,
    __('Normal mode'),
    $modo,
    false,
    'show_modules_not_learning_mode_context_help();',
    '',
    true
);
$switchButtons[] = html_print_radio_button_extended(
    'modo',
    2,
    __('Autodisable mode'),
    $modo,
    false,
    'show_modules_not_learning_mode_context_help();',
    '',
    true
);

$tableAdvancedAgent->data['module_definition'][] = html_print_label_input_block(
    __('Module definition').ui_print_help_tip(__('Three working modes can be selected for module definition. Learning mode: Default mode, if an XML arrives with new modules, they will be created automatically; it is a learning behavior. Normal mode: If an XML arrives with new modules, they will only be created if they are previously declared in the Console. Autodisable mode: It is the same as learning mode, but if all modules go into unknown, the agent will be disabled until information arrives again.'), true),
    html_print_div(
        [
            'class'   => 'switch_radio_button',
            'content' => implode('', $switchButtons),
        ],
        true
    )
);

// CPS - Cascade Protection Services.
$tableAdvancedAgent->data['cps_value'][] = html_print_label_input_block(
    __('Cascade protection services').ui_print_help_tip(__('To avoid an avalanche of cascading alerts. You may choose any agent module (any) or a specific module. In the first case, when there is at least one module in critical, that event/alert will be launched, but no other of another module of the same agent. In the second case, when the specified module is in critical, the agent will not generate alerts/events.'), true),
    html_print_checkbox_switch('cps', $cps_val, ($cps >= 0), true)
);

// Update GIS data.
if ((bool) $config['activate_gis'] === true) {
    $tableAdvancedAgent->data['gis'][] = html_print_label_input_block(
        __('Update new GIS data'),
        html_print_checkbox_switch('update_gis_data', 1, ($new_agent === true), true)
    );
}

// Agent Icons.
$tableAdvancedAgent->data['agent_icon'][] = html_print_label_input_block(
    __('Agent icon'),
    html_print_select(
        $arraySelectIcon,
        'icon_path',
        $icon_path,
        'changeIcons();',
        __('None'),
        '',
        true,
        false,
        true,
        'w540px'
    ).'<div class="flex mrgn_top_6px mrgn_btn_5px">'.html_print_image(
        $path_ok,
        true,
        [
            'id'    => 'icon_ok',
            'style' => 'display:'.$display_icons.';',
            'width' => '30',
            'class' => 'mrgn_right_5px',
        ]
    ).html_print_image(
        $path_bad,
        true,
        [
            'id'    => 'icon_bad',
            'style' => 'display:'.$display_icons.';',
            'width' => '30',
            'class' => 'mrgn_right_5px',
        ]
    ).html_print_image(
        $path_warning,
        true,
        [
            'id'    => 'icon_warning',
            'style' => 'display:'.$display_icons.';',
            'width' => '30',
            'class' => 'mrgn_right_5px',
        ]
    ).'</div>'
);

// Url address.
if (enterprise_installed() === true) {
    $urlAddressInput = html_print_input_text(
        'url_description',
        $url_description,
        '',
        45,
        255,
        true,
        false,
        false,
        '',
        'w540px',
        '',
    );
} else {
    $urlAddressInput = html_print_input_text(
        'url_description',
        $url_description,
        '',
        45,
        255,
        true
    );
}

$tableAdvancedAgent->data['url_description'][] = html_print_label_input_block(
    __('URL Address'),
    $urlAddressInput
);

// Agent status.
$tableAdvancedAgent->data['agent_status'][] = html_print_label_input_block(
    __('Disabled mode').ui_print_help_tip(__('A deactivated agent does not generate activity (or issue events/alerts) or process data or actively generate monitoring. In many listings it does not even appear.'), true),
    html_print_checkbox_switch(
        'disabled',
        1,
        $disabled,
        true
    )
);

// Quiet mode.
$tableAdvancedAgent->data['agent_quiet'][] = html_print_label_input_block(
    __('Quiet').ui_print_help_tip(__('A \'silent\' agent continues to process monitoring data, but does not generate events or alerts.'), true),
    html_print_checkbox_switch('quiet', 1, $quiet, true)
);

// Remote configuration.
if ($new_agent === false && isset($filename) === true && file_exists($filename['md5']) === true) {
    $remoteConfigurationElements = [];
    $remoteConfigurationElements[] = html_print_input_text(
        'remote_file_timestamp',
        date('F d Y H:i:s', fileatime($filename['md5'])),
        '',
        0,
        100,
        true,
        true,
        false,
        '',
        'w540px'
    );
    $remoteConfigurationElements[] = html_print_anchor(
        [
            'href'    => 'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=main&disk_conf_delete=1&id_agente='.$id_agente,
            'content' => html_print_image(
                'images/delete.svg',
                true,
                [
                    'border' => 0,
                    'title'  => __('Delete remote configuration file'),
                    'class'  => 'invert_filter after_input_icon',
                ]
            ),
        ],
        true
    );

    $tableAdvancedAgent->data['remote_configuration'][] = html_print_label_input_block(
        __('Remote configuration'),
        html_print_div(
            [
                'class'   => 'flex-row-center',
                'content' => implode('', $remoteConfigurationElements),
            ],
            true
        )
    );
}

// Safe operation mode.
$safeOperationElements = [];
$safeOperationElements[] = html_print_checkbox_switch(
    'safe_mode',
    1,
    $safe_mode,
    true
);
$safeOperationElements[] = html_print_select(
    $safe_mode_modules,
    'safe_mode_module',
    $safe_mode_module,
    '',
    '',
    0,
    true
);

$tableAdvancedAgent->data['safe_operation'][] = html_print_label_input_block(
    __('Safe operation mode').ui_print_help_tip(__('When the module set in this option goes into critical state, the other modules of the agent are automatically deactivated. This option can be very useful to avoid wasting resources, especially in remote checks. For example, if a Host alive module is chosen, SNMP checks will not be performed on the device when there is no connectivity with it.'), true),
    html_print_div(
        [
            'class'   => 'flex-row-center',
            'content' => implode('', $safeOperationElements),
        ],
        true
    )
);

if (enterprise_installed() === true) {
    $tableAdvancedAgent->data['vul_scan_enabled'][] = html_print_label_input_block(
        __('Vulnerability scanning'),
        html_print_select(
            [
                0 => __('Disabled'),
                1 => __('Enabled'),
                2 => __('Use global settings'),
            ],
            'vul_scan_enabled',
            $vul_scan_enabled,
            '',
            '',
            0,
            true
        )
    );
}

$tableAdvancedAgent->data['ignore_unknown'][] = html_print_label_input_block(
    __('Ignore unknown').ui_print_help_tip(__('This disables the calculation of the unknown state in the agent and any of its modules, so it will never transition to unknown. The state it reflects is the last known status.'), true),
    html_print_checkbox_switch(
        'ignore_unknown',
        1,
        $ignore_unknown,
        true,
        false
    )
);


ui_toggle(
    html_print_table($tableAdvancedAgent, true),
    '<span class="subsection_header_title">'.__('Advanced options').'</span>',
    '',
    '',
    true,
    false,
    'white_box_content',
    'no-border white_table_graph'
);

// Custom fields.
$customOutputData = '';

$fields = db_get_all_fields_in_table('tagent_custom_fields');

if ($fields === false) {
    $fields = [];
}

foreach ($fields as $field) {
    // Filling the data.
    $combo = [];
    $combo = $field['combo_values'];
    $combo = explode(',', (empty($combo) === true) ? '' : $combo);
    $combo_values = [];
    foreach ($combo as $value) {
        $combo_values[$value] = $value;
    }

    $custom_value = db_get_value_filter(
        'description',
        'tagent_custom_data',
        [
            'id_field' => $field['id_field'],
            'id_agent' => $id_agente,
        ]
    );

    if ($custom_value === false) {
        $custom_value = '';
    }

    if ((bool) $field['is_password_type'] === true) {
        $customContent = html_print_input_password(
            'customvalue_'.$field['id_field'],
            $custom_value,
            '',
            45,
            255,
            true
        );
    } else if ($field['is_link_enabled']) {
        list($link_text, $link_url) = json_decode(io_safe_output($custom_value), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $link_text = '';
            $link_url = '';
        }

        $customContent = '<span style="line-height: 3.5;">'.__('Link text:').'</span>';
        $customContent .= '<br>';
        $customContent .= html_print_textarea(
            'customvalue_'.$field['id_field'].'[]',
            2,
            1000,
            $link_text,
            'class="min-height-30px w100p"',
            true
        );
        $customContent .= '<br>';
        $customContent .= '<span style="line-height: 3.5;">'.__('Link URL:').'</span>';
        $customContent .= '<br>';
        $customContent .= html_print_textarea(
            'customvalue_'.$field['id_field'].'[]',
            2,
            1000,
            $link_url,
            'class="min-height-30px w100p"',
            true
        );
    } else {
        $customContent = html_print_textarea(
            'customvalue_'.$field['id_field'],
            2,
            1000,
            $custom_value,
            'class="min-height-30px w100p"',
            true
        );
    }

    if (empty($field['combo_values']) === false) {
        $customContent = html_print_input(
            [
                'type'              => 'select_search',
                'fields'            => $combo_values,
                'name'              => 'customvalue_'.$field['id_field'],
                'selected'          => $custom_value,
                'nothing'           => __('None'),
                'nothing_value'     => '',
                'return'            => true,
                'sort'              => false,
                'size'              => '400px',
                'dropdownAutoWidth' => true,
            ]
        );
    };

    $customOutputData .= ui_toggle(
        html_print_div(
            [ 'content' => $customContent ],
            true
        ),
        $field['name'],
        $field['name'],
        'custom_field_toggle_'.$field['id_field'],
        true,
        true,
    );
}

if (empty($fields) === false) {
    ui_toggle(
        $customOutputData,
        '<span class="subsection_header_title">'.__('Custom fields').'</span>',
        '',
        '',
        true,
        false,
        'white_box white_box_opened white_table_graph_fixed no_border',
        'no-border custom_fields_elements'
    );
}

// The context help about the learning mode.
if ($modo == 0) {
    echo "<span id='modules_not_learning_mode_context_help' class='pdd_r_10px'>";
} else {
    echo "<span id='modules_not_learning_mode_context_help' class='invisible'>";
}

echo clippy_context_help('modules_not_learning_mode');
echo '</span>';

if ($new_agent === false) {
    $actionButtons = html_print_submit_button(
        __('Update'),
        'updbutton',
        false,
        [ 'icon' => 'update'],
        true
    );
    $actionButtons .= html_print_input_hidden('update_agent', 1);
    $actionButtons .= html_print_input_hidden('id_agente', $id_agente);

    if (is_management_allowed() === true) {
        $clusters = agents_get_agent_belongs_cluster($id_agente);
        $cluster_belongs = '';
        if (empty($clusters) === false) {
            $clusters = array_reduce(
                $clusters,
                function ($carry, $item) {
                    $carry[] = $item['name'];
                    return $carry;
                }
            );
            $cluster_belongs = implode(', ', $clusters);
        }

        $actionButtons .= html_print_button(
            __('Delete agent'),
            'deleteAgent',
            false,
            'deleteAgentDialog('.$id_agente.', "'.$cluster_belongs.'")',
            [
                'icon' => 'delete',
                'mode' => 'secondary dialog_opener',
            ],
            true
        );
    }
} else {
    $actionButtons = html_print_input_hidden('create_agent', 1);
    $actionButtons .= html_print_submit_button(
        __('Create'),
        'crtbutton',
        false,
        [ 'icon' => 'wand'],
        true
    );
}

$actionButtons .= html_print_go_back_button(
    'index.php?sec=gagente&sec2=godmode/agentes/modificar_agente',
    ['button_class' => ''],
    true
);

html_print_action_buttons($actionButtons, ['type' => 'form_action']);

echo '</div></div>';
echo '</form>';

ui_require_jquery_file('pandora.controls');
ui_require_jquery_file('ajaxqueue');
ui_require_jquery_file('bgiframe');
?>

<script type="text/javascript">
    let unique_ip_trigger = false;
    // Show/Hide custom field row.
    function show_custom_field_row(id){
        if( $('#field-'+id).css('display') == 'none'){
            $('#field-'+id).css('display','table-row');
            $('#name_field-'+id).addClass('custom_field_row_opened');
        }
        else{
            $('#field-'+id).css('display','none');
            $('#name_field-'+id).removeClass('custom_field_row_opened');
        }
    }

    function deleteAgentDialog($idAgente, cluster) {
        var msg_cluster = '';
        if(cluster) {
            msg_cluster = "<?php echo __('This agent belongs to the clusters'); ?>";
            msg_cluster += ': ';
            msg_cluster += cluster;
            msg_cluster += '. ';
        }

        confirmDialog({
            title: "<?php echo __('Delete agent'); ?>",
            message: msg_cluster + "<?php echo __('This action is not reversible. Are you sure'); ?>",
            onAccept: function() {
                window.location.assign('index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&borrar_agente='+$idAgente);
            }
        });
    }

    //Use this function for change 3 icons when change the selectbox
    function changeIcons() {
        var icon = $("#icon_path :selected").val();
        
        $("#icon_without_status").attr("src", "images/spinner.png");
        $("#icon_default").attr("src", "images/spinner.png");
        $("#icon_ok").attr("src", "images/spinner.png");
        $("#icon_bad").attr("src", "images/spinner.png");
        $("#icon_warning").attr("src", "images/spinner.png");
        
        if (icon.length == 0) {
            $("#icon_without_status").attr("style", "display:none;");
            $("#icon_default").attr("style", "display:none;");
            $("#icon_ok").attr("style", "display:none;");
            $("#icon_bad").attr("style", "display:none;");
            $("#icon_warning").attr("style", "display:none;");
        }
        else {
            $("#icon_without_status").attr("src",
                "<?php echo $path; ?>" + icon + ".default.png");
            $("#icon_default").attr("src",
                "<?php echo $path; ?>" + icon + ".default.png");
            $("#icon_ok").attr("src",
                "<?php echo $path; ?>" + icon + ".ok.png");
            $("#icon_bad").attr("src",
                "<?php echo $path; ?>" + icon + ".bad.png");
            $("#icon_warning").attr("src",
                "<?php echo $path; ?>" + icon + ".warning.png");
            $("#icon_without_status").attr("style", "");
            $("#icon_default").attr("style", "");
            $("#icon_ok").attr("style", "");
            $("#icon_bad").attr("style", "");
            $("#icon_warning").attr("style", "");
        }
    }
    
    function show_modules_not_learning_mode_context_help() {
        if ($("input[name='modo'][value=0]").is(':checked')) {
            $("#modules_not_learning_mode_context_help").show().css('padding-right','8px');
        }
        else {
            $("#modules_not_learning_mode_context_help").hide();
        }
    }


    $(document).ready (function() {

        var $id_agent = '<?php echo $id_agente; ?>';
        var previous_primary_group_select;
        $("#grupo").on('focus', function () {
            previous_primary_group_select = this.value;
        }).change(function() {
            if ($("#secondary_groups_selected option[value="+$("#grupo").val()+"]").length) {
                alert("<?php echo __('Secondary group cannot be primary too.'); ?>");
                $("#grupo").val(previous_primary_group_select);
            } else {
                previous_primary_group_select = this.value;
            }
        });

        $("select#id_os").pandoraSelectOS ();
        $('select#grupo').pandoraSelectGroupIcon ();

        

        var checked = $("#checkbox-cascade_protection").is(":checked");
        if (checked) {
            $("#cascade_protection_module").removeAttr("disabled");
        }
        else {
            $("#cascade_protection_module").attr("disabled", 'disabled');
        }

        $("#text-id_parent").change(function(){
            const parent = $("#text-id_parent").val();
            if (parent != '') {
                $("#checkbox-cascade_protection").prop('checked', true);
                $("#cascade_protection_module").removeAttr("disabled");
            }
            else {
                $("#cascade_protection_module").val(0);
                $("#cascade_protection_module").attr("disabled", 'disabled');
                $("#text-id_parent").removeAttr("required");
                $("#cascade_protection_module").empty();
                $("#checkbox-cascade_protection").prop('checked', false);
            }
        });

        $("#checkbox-cascade_protection").change(function () {
            var checked = $("#checkbox-cascade_protection").is(":checked");
            if (checked) {
                $("#cascade_protection_module").removeAttr("disabled");
                $("#text-id_parent").attr("required", "required");
            }
            else {
                $("#cascade_protection_module").val(0);
                $("#cascade_protection_module").attr("disabled", 'disabled');
                $("#text-id_parent").removeAttr("required");
            }
        });

        var safe_mode_checked = $("#checkbox-safe_mode").is(":checked");
        if (safe_mode_checked) {
            $("#safe_mode_module").removeAttr("disabled");
        }
        else {
            $("#safe_mode_module").attr("disabled", 'disabled');
        }

        $("#checkbox-safe_mode").change(function () {
            var safe_mode_checked = $("#checkbox-safe_mode").is(":checked");

            if (safe_mode_checked) {
                $("#safe_mode_module").removeAttr("disabled");
            }
            else {
                $("#safe_mode_module").val(0);
                $("#safe_mode_module").attr("disabled", 'disabled');
            }
        });

        if (typeof $id_agent !== 'undefined' && $id_agent !== '0') {
            paint_qrcode(
                "<?php echo ui_get_full_url('mobile/index.php?page=agent&id='.$id_agente); ?>",
                "#qr_code_agent_view",
                128,
                128
            );
        }


        $("#text-direccion").on('change',function(e){
            const unique_ip_token = '<?php echo $config['unique_ip']; ?>';
            unique_ip_trigger = false;
            if (unique_ip_token == 1) {
                check_unique_ip();
            }
        });

        check_basic_options();
        $('#id_os').on('change', function(){
            check_basic_options();
        });

        $('#button-check_unique_ip').on('click', function() {
            check_unique_ip();
        });

        $('#form_agent').on('submit', function(e) {
            if (unique_ip_trigger) {
                e.preventDefault();
                const form = this;
                confirmDialog(
                    {
                        title: '<?php echo __('Are you sure?'); ?>',
                        message: '<?php echo __('This IP address is in use. Are you sure you want to save it?'); ?>',
                        ok: '<?php echo __('Yes'); ?>',
                        cancel: '<?php echo __('Cancel'); ?>',
                        onAccept: function() {
                            form.submit();
                        }
                    }
                );
            }
        });
    });

    function check_basic_options(){
        if ($('#id_os').val() == 1 || $('#id_os').val() == 8 || $('#id_os').val() == 9) {
            $('#basic_options').removeClass('invisible');
        } else {
            $('#basic_options').addClass('invisible');
        }
    }

    function editAgent() {
        $(`#text-agente`).attr(`readonly`, false);
        const title = '<?php echo __('Warning'); ?>';
        const text = '<?php echo __('Change the internal name of the agent may cause duplicity and malfunction'); ?>';
        const id = uniqId();
        $("body").append('<div title="' + title + '" id="' + id + '"></div>');
        $("#" + id).empty();
        $("#" + id).append(text);
        $("#" + id).dialog({
            height: 150,
            width: 528,
            opacity: 1,
            modal: true,
            position: {
                my: "center",
                at: "center",
                of: window,
                collision: "fit"
            },
            title: title,
            closeOnEscape: true,
            buttons: [{
                text: "OK",
                click: function() {
                    $(this).dialog("close");
                }
            }],
            open: function(event, ui) {
                $(".ui-dialog-titlebar-close").hide();
            },
        }).show();
    }


    function check_unique_ip() {
        const direccion = $('#text-direccion').val();
        let ip_all = <?php echo json_encode($ip_all); ?>;
        if (ip_all) {
            ip_all = Object.keys(ip_all);
        }
        $.ajax({
                method: "POST",
                url: "<?php echo ui_get_full_url('ajax.php'); ?>",
                dataType: 'json',
                data: {
                    page: "include/ajax/agent",
                    check_unique_ip: 1,
                    direccion,
                    ip_all
                },
                success: function(data) {
                    if (data.success) {
                        $('#message_check_ip').attr('class', 'success');
                    } else {
                        $('#message_check_ip').attr('class', 'error');
                    }

                    if(data.exist_ip) {
                        unique_ip_trigger = true;
                    } else {
                        unique_ip_trigger = false;
                    }

                    $('#message_check_ip').html(data.message);
                }
        });

    }
</script>
