<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// Load global vars
enterprise_include('godmode/agentes/agent_manager.php');

require_once 'include/functions_clippy.php';
require_once 'include/functions_servers.php';
require_once 'include/functions_gis.php';
require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_users.php';

if (is_ajax()) {
    global $config;

    $search_parents_2 = (bool) get_parameter('search_parents_2');

    if ($search_parents_2) {
        include_once 'include/functions_agents.php';

        $id_agent = (int) get_parameter('id_agent');
        $string = (string) get_parameter('q');
        // q is what autocomplete plugin gives
        $filter = [];

        switch ($config['dbtype']) {
            case 'mysql':
            case 'postgresql':
                $filter[] = '(nombre COLLATE utf8_general_ci LIKE "%'.$string.'%" OR direccion LIKE "%'.$string.'%" OR comentarios LIKE "%'.$string.'%" OR alias LIKE "%'.$string.'%")';
            break;

            case 'oracle':
                $filter[] = '(upper(nombre) LIKE upper(\'%'.$string.'%\') OR upper(direccion) LIKE upper(\'%'.$string.'%\') OR upper(comentarios) LIKE upper(\'%'.$string.'%\') OR upper(alias) LIKE upper(\'%'.$string.'%\'))';
            break;
        }

        $filter[] = 'id_agente != '.$id_agent;

        $agents = agents_get_agents($filter, ['id_agente', 'nombre', 'direccion']);
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
    if ($get_modules_json_for_multiple_snmp) {
        include_once 'include/graphs/functions_utils.php';

        $idSNMP = get_parameter('id_snmp');

        $id_snmp_serialize = get_parameter('id_snmp_serialize');
        $snmp = unserialize_in_temp($id_snmp_serialize, false);

        $oid_snmp = [];
        $out = false;
        foreach ($idSNMP as $id) {
            foreach ($snmp[$id] as $key => $value) {
                // Check if it has "ifXXXX" syntax and skip it
                if (! preg_match('/if/', $key)) {
                    continue;
                }

                $oid_snmp[$value['oid']] = $key;
            }

            if ($out === false) {
                $out = $oid_snmp;
            } else {
                $out = array_intersect($out, $oid_snmp);
            }

            $oid_snmp = [];
        }

        echo io_json_mb_encode($out);
    }

    // And and remove groups use the same function
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
                    $add_secondary_groups ? $groups_to_add : [],
                    $remove_secondary_groups ? $groups_to_add : [],
                ]
            );
            // Echo 0 in case of error. 0 Otherwise.
            echo $ret ? 1 : 0;
        }
    }

    return;
}

ui_require_javascript_file('openlayers.pandora');

$new_agent = (bool) get_parameter('new_agent');

if (! isset($id_agente) && ! $new_agent) {
    db_pandora_audit('ACL Violation', 'Trying to access agent manager witout an agent');
    include 'general/noaccess.php';
    return;
}

if ($new_agent) {
    if (! empty($direccion_agente) && empty($nombre_agente)) {
        $nombre_agente = $direccion_agente;
    }

    $servers = servers_get_names();
    if (!empty($servers)) {
        $array_keys_servers = array_keys($servers);
        $server_name = reset($array_keys_servers);
    }
}

if (!$new_agent) {
    // Agent remote configuration editor
    enterprise_include_once('include/functions_config_agents.php');
    if (enterprise_installed()) {
        $filename = config_agents_get_agent_config_filenames($id_agente);
    }
}

$disk_conf_delete = (bool) get_parameter('disk_conf_delete');
// Agent remote configuration DELETE
if ($disk_conf_delete) {
    // TODO: Get this working on computers where the Pandora server(s) are not on the webserver
    // TODO: Get a remote_config editor working in the open version
    @unlink($filename['md5']);
    @unlink($filename['conf']);
}

echo '<form name="conf_agent" method="post" action="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente">';

if (!$new_agent && $alias != '') {
    $table_agent_name = '<div class="label_select"><p class="input_label">'.__('Agent name').': '.ui_print_help_tip(__("The agent's name must be the same as the one defined at the console"), true).'</p>';
    $table_agent_name .= '<div class="label_select_parent">';
    $table_agent_name .= '<div class="label_select_child_left">'.html_print_input_text('agente', $nombre_agente, '', 50, 100, true).'</div>';
    $table_agent_name .= '<div class="label_select_child_right agent_info_agent_name">';

    $table_qr_code = '<div class="agent_qr white_box"><p class="input_label">'.__('QR Code Agent view').': </p>';

    if ($id_agente) {
        $table_agent_name .= '<span>'.__('ID').' '.$id_agente.'</span>';
        $table_agent_name .= '<a href="index.php?sec=gagente&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$id_agente.'">';
        $table_agent_name .= html_print_image(
            'images/zoom.png',
            true,
            [
                'border' => 0,
                'title'  => __('Agent detail'),
            ]
        );
        $table_agent_name .= '</a>';
    }
} else {
    $agent_info_new = 'agent_info_new';
}

// Delete link from here.
if (!$new_agent) {
    $table_agent_name .= "<a onClick=\"if (!confirm('".__('Are you sure?')."')) return false;\" href='index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&borrar_agente=$id_agente&search=&offset=0&sort_field=&sort=none'>".html_print_image('images/cross.png', true, ['title' => __('Delete agent')]).'</a>';
}


// Remote configuration available.
if (!$new_agent) {
    if (isset($filename)) {
        if (file_exists($filename['md5'])) {
            $agent_name = agents_get_name($id_agente);
            $agent_name = io_safe_output($agent_name);
            $agent_md5 = md5($agent_name, false);

            $table_agent_name .= '<a href="index.php?'.'sec=gagente&amp;'.'sec2=godmode/agentes/configurar_agente&amp;'.'tab=remote_configuration&amp;'.'id_agente='.$id_agente.'&amp;'.'disk_conf='.$agent_md5.'">';
            $table_agent_name .= html_print_image(
                'images/application_edit.png',
                true,
                [
                    'border' => 0,
                    'title'  => __('This agent can be remotely configured'),
                ]
            );
            $table_agent_name .= '</a>'.ui_print_help_tip(
                __('You can remotely edit this agent configuration'),
                true
            );
        }
    }
}

if (!$new_agent) {
    $table_agent_name .= '</div></div></div>';
}

if ($new_agent) {
    $label_select_child_left = 'label_select_child_left';
    $label_select_parent = 'label_select_parent';
}

$table_alias = '<div class="label_select"><p class="input_label">'.__('Alias').': '.ui_print_help_tip(__('Characters /,\,|,%,#,&,$ will be ignored'), true).'</p>';
$table_alias .= '<div class='.$label_select_parent.'>';
$table_alias .= '<div class='.$label_select_child_left.'>'.html_print_input_text('alias', $alias, '', 50, 100, true).'</div>';
if ($new_agent) {
    $table_alias .= '<div class="label_select_child_right">'.html_print_checkbox_switch('alias_as_name', 1, $config['alias_as_name'], true).__('Use alias as name').'</div>';
}

$table_alias .= '</div></div>';

$table_ip = '<div class="label_select"><p class="input_label">'.__('IP Address').': </p>';
$table_ip .= '<div class="label_select_parent">';
$table_ip .= '<div class="label_select_child_left">'.html_print_input_text('direccion', $direccion_agente, '', 16, 100, true).'</div>';
$table_ip .= '<div class="label_select_child_right">'.html_print_checkbox_switch('unique_ip', 1, $config['unique_ip'], true).__('Unique IP').ui_print_help_tip(__('Set the primary IP address as the unique IP, preventing the same primary IP address from being used in more than one agent'), true).'</div>';
$table_ip .= '</div></div>';

if ($id_agente) {
    $ip_all = agents_get_addresses($id_agente);

    $table_ip .= '<div class="label_select">';
    $table_ip .= '<div class="label_select_parent">';
    $table_ip .= '<div class="label_select_child_left">'.html_print_select($ip_all, 'address_list', $direccion_agente, '', '', 0, true).'</div>';
    $table_ip .= '<div class="label_select_child_right">'.html_print_checkbox_switch('delete_ip', 1, false, true).__('Delete selected').'</div>';
    $table_ip .= '</div></div>';
}

?>
<style type="text/css">
    #qr_code_agent_view img {
        display: inline !important;
    }
</style>
<?php
if (!$new_agent) {
    if ($id_agente) {
        $table_qr_code .= "<a id='qr_code_agent_view' href='javascript: show_dialog_qrcode(null, \"".ui_get_full_url('mobile/index.php?page=agent&id='.$id_agente)."\" );'></a>";
    } else {
        $table_qr_code .= __('Only it is show when<br />the agent is saved.');
    }

    $table_qr_code .= '</div>';
}

$groups = users_get_groups($config['id_user'], 'AR', false);

$modules = db_get_all_rows_sql(
    'SELECT id_agente_modulo as id_module, nombre as name FROM tagente_modulo 
								WHERE id_agente = '.$id_parent
);
$modules_values = [];
$modules_values[0] = __('Any');
if (is_array($modules)) {
    foreach ($modules as $m) {
        $modules_values[$m['id_module']] = $m['name'];
    }
}

$table_primary_group = '<div class="label_select"><p class="input_label">'.__('Primary group').': </p>';
$table_primary_group .= '<div class="label_select_parent">';
// Cannot change primary group if user have not permission for that group.
if (isset($groups[$grupo]) || $new_agent) {
    $table_primary_group .= html_print_select_groups(false, 'AR', false, 'grupo', $grupo, '', '', 0, true);
} else {
    $table_primary_group .= groups_get_name($grupo);
    $table_primary_group .= html_print_input_hidden('grupo', $grupo, true);
}

$table_primary_group .= '<div class="label_select_child_icons"><span id="group_preview">';
$table_primary_group .= ui_print_group_icon($grupo, true);
$table_primary_group .= '</span></div></div></div>';

$table_interval = '<div class="label_select"><p class="input_label">'.__('Interval').': </p>';
$table_interval .= '<div class="label_select_parent">';
$table_interval .= html_print_extended_select_for_time('intervalo', $intervalo, '', '', '0', 10, true);
if ($intervalo < SECONDS_5MINUTES) {
    $table_interval .= clippy_context_help('interval_agent_min');
}

$table_interval .= '</div></div>';

$table_os = '<div class="label_select"><p class="input_label">'.__('OS').': </p>';
$table_os .= '<div class="label_select_parent">';
$table_os .= html_print_select_from_sql(
    'SELECT id_os, name FROM tconfig_os',
    'id_os',
    $id_os,
    '',
    '',
    '0',
    true
);
$table_os .= '<div class="label_select_child_icons"> <span id="os_preview">';
$table_os .= ui_print_os_icon($id_os, false, true);
$table_os .= '</span></div></div></div>';

// Network server.
$servers = servers_get_names();
if (!array_key_exists($server_name, $servers)) {
    $server_Name = 0;
    // Set the agent have not server.
}

$table_server = '<div class="label_select"><p class="input_label">'.__('Server').': </p>';
$table_server .= '<div class="label_select_parent">';
if ($new_agent) {
    // Set first server by default.
    $servers_get_names = servers_get_names();
    $array_keys_servers_get_names = array_keys($servers_get_names);
    $server_name = reset($array_keys_servers_get_names);
}

$table_server .= html_print_select(
    servers_get_names(),
    'server_name',
    $server_name,
    '',
    __('None'),
    0,
    true
).'<div class="label_select_child_icons">'.ui_print_help_icon('agent_server', true).'</div></div></div>';

// Description.
$table_description = '<div class="label_select"><p class="input_label">'.__('Description').': </p>';
$table_description .= html_print_input_text(
    'comentarios',
    $comentarios,
    '',
    45,
    200,
    true
).'</div>';


echo '<div class="first_row">
    <div class="agent_info '.$agent_info_new.' white_box">
        <div class="agent_info_left">'.$table_agent_name.$table_alias.$table_ip.$table_primary_group.'</div>
        <div class="agent_info_right">'.$table_interval.$table_os.$table_server.$table_description.'</div>
        </div>';
if (!$new_agent) {
    echo $table_qr_code;
}

echo '</div>';

$table = new stdClass();
$table->width = '100%';
$table->class = 'databox filters';

$table->head = [];
$table->style = [];
$table->style[0] = 'font-weight: bold; ';
$table->style[4] = 'font-weight: bold;';
$table->data = [];

if (enterprise_installed()) {
    $secondary_groups_selected = enterprise_hook('agents_get_secondary_groups', [$id_agente]);
    $table->data['secondary_groups'][0] = __('Secondary groups').ui_print_help_icon('secondary_groups', true);
    $table->data['secondary_groups'][1] = html_print_select_groups(
        false,
        // Use the current user to select the groups
        'AR',
        // ACL permission
        false,
        // Not all group
        'secondary_groups',
        // HTML id
        '',
        // No select any by default
        '',
        // Javascript onChange code
        '',
        // Do not user no selected value
        0,
        // Do not use no selected value
        true,
        // Return HTML (not echo)
        true,
        // Multiple selection
        true,
        // Sorting by default
        '',
        // CSS classnames (default)
        false,
        // Not disabled (default)
        false,
        // Inline styles (default)
        false,
        // Option style select (default)
        false,
        // Do not truncate the users tree (default)
        'id_grupo',
        // Key to get as value (default)
        false,
        // Not strict user (default)
        $secondary_groups_selected['plain']
        // Do not show the primary group in this selection
    );

    $table->data['secondary_groups'][2] = html_print_input_image(
        'add_secondary',
        'images/darrowright.png',
        1,
        '',
        true,
        [
            'title'   => __('Add secondary groups'),
            'onclick' => 'agent_manager_add_secondary_groups(event, '.$id_agente.');',
        ]
    ).'<br /><br /><br /><br />'.html_print_input_image(
        'remove_secondary',
        'images/darrowleft.png',
        1,
        '',
        true,
        [
            'title'   => __('Remove secondary groups'),
            'onclick' => 'agent_manager_remove_secondary_groups(event, '.$id_agente.');',
        ]
    );

    $table->data['secondary_groups'][3] = html_print_select(
        $secondary_groups_selected['for_select'],
        // Values
        'secondary_groups_selected',
        // HTML id
        '',
        // Selected
        '',
        // Javascript onChange code
        '',
        // Nothing selected
        0,
        // Nothing selected
        true,
        // Return HTML (not echo)
        true
        // Multiple selection
    );

    // safe operation mode
    if ($id_agente) {
        $sql_modules = db_get_all_rows_sql(
            'SELECT id_agente_modulo as id_module, nombre as name FROM tagente_modulo 
									WHERE id_agente = '.$id_agente
        );
        $safe_mode_modules = [];
        $safe_mode_modules[0] = __('Any');
        foreach ($sql_modules as $m) {
            $safe_mode_modules[$m['id_module']] = $m['name'];
        }

        $table->data[2][0] = __('Safe operation mode').ui_print_help_tip(
            __(
                'This mode allow %s to disable all modules 
		of this agent while the selected module is on CRITICAL status',
                get_product_name()
            ),
            true
        );
        $table->data[2][1] = html_print_checkbox('safe_mode', 1, $safe_mode, true);
        $table->data[2][1] .= '&nbsp;&nbsp;'.__('Module').'&nbsp;'.html_print_select($safe_mode_modules, 'safe_mode_module', $safe_mode_module, '', '', 0, true);
    }

    // Remote configuration
    $table->data[5][0] = __('Remote configuration');

    if (!$new_agent) {
        $table->data[5][1] = '<em>'.__('Not available').'</em>';
        if (isset($filename)) {
            if (file_exists($filename['md5'])) {
                $table->data[5][1] = date('F d Y H:i:s', fileatime($filename['md5']));
                // Delete remote configuration
                $table->data[5][1] .= '<a href="index.php?'.'sec=gagente&amp;'.'sec2=godmode/agentes/configurar_agente&amp;'.'tab=main&amp;'.'disk_conf_delete=1&amp;'.'id_agente='.$id_agente.'">';
                $table->data[5][1] .= html_print_image(
                    'images/cross.png',
                    true,
                    [
                        'title' => __('Delete remote configuration file'),
                        'style' => 'vertical-align: middle;',
                    ]
                ).'</a>';
                $table->data[5][1] .= '</a>'.ui_print_help_tip(
                    __('Delete this conf file implies that for restore you must reactive remote config in the local agent.'),
                    true
                );
            }
        }
    } else {
        $table->data[5][1] = '<em>'.__('Not available').'</em>';
    }



    $cps_array[-1] = __('Disabled');
    if ($cps > 0) {
        $cps_array[$cps] = __('Enabled');
    } else {
        $cps_inc = 0;
        if ($id_agente) {
            $cps_inc = service_agents_cps($id_agente);
        }

        $cps_array[$cps_inc] = __('Enabled');
    }

    $table->data[6][0] = __('Cascade protection services');
    $table->data[6][0] .= ui_print_help_tip(__('Disable the alerts and events of the elements that belong to this service'), true);
    $table->data[6][1] = html_print_select($cps_array, 'cps', $cps, '', '', 0, true);
}

// Custom ID
$table->data[0][0] = __('Custom ID');
$table->data[0][1] = html_print_input_text('custom_id', $custom_id, '', 16, 255, true);

$table->data[1][0] = __('Parent');
$params = [];
$params['return'] = true;
$params['show_helptip'] = true;
$params['input_name'] = 'id_parent';
$params['print_hidden_input_idagent'] = true;
$params['hidden_input_idagent_name'] = 'id_agent_parent';
$params['hidden_input_idagent_value'] = $id_parent;
$params['value'] = db_get_value('alias', 'tagente', 'id_agente', $id_parent);
$params['selectbox_id'] = 'cascade_protection_module';
$params['javascript_is_function_select'] = true;
$params['cascade_protection'] = true;

$table->data[1][1] = ui_print_agent_autocomplete_input($params);
if (enterprise_installed()) {
    $table->data[1][1] .= html_print_checkbox('cascade_protection', 1, $cascade_protection, true).__('Cascade protection').'&nbsp;'.ui_print_help_icon('cascade_protection', true);
}

$table->data[1][1] .= '&nbsp;&nbsp;'.__('Module').'&nbsp;'.html_print_select($modules_values, 'cascade_protection_module', $cascade_protection_module, '', '', 0, true);
// Learn mode / Normal mode
$table->data[3][0] = __('Module definition').ui_print_help_icon('module_definition', true);
$table->data[3][1] = __('Learning mode').' '.html_print_radio_button_extended(
    'modo',
    1,
    '',
    $modo,
    false,
    'show_modules_not_learning_mode_context_help();',
    'style="margin-right: 40px;"',
    true
);
$table->data[3][1] .= __('Normal mode').' '.html_print_radio_button_extended(
    'modo',
    0,
    '',
    $modo,
    false,
    'show_modules_not_learning_mode_context_help();',
    'style="margin-right: 40px;"',
    true
);
$table->data[3][1] .= __('Autodisable mode').' '.html_print_radio_button_extended(
    'modo',
    2,
    '',
    $modo,
    false,
    'show_modules_not_learning_mode_context_help();',
    'style="margin-right: 40px;"',
    true
);

// Status (Disabled / Enabled)
$table->data[4][0] = __('Status');
$table->data[4][1] = __('Disabled').' '.ui_print_help_tip(__('If the remote configuration is enabled, it will also go into standby mode when disabling it.'), true).' '.html_print_radio_button_extended('disabled', 1, '', $disabled, false, '', 'style="margin-right: 40px;"', true);
$table->data[4][1] .= __('Enabled').' '.html_print_radio_button_extended('disabled', 0, '', $disabled, false, '', 'style="margin-right: 40px;"', true);
if (enterprise_installed()) {
    $table->data[4][2] = __('Url address').ui_print_help_tip(__('URL address must be complete, for example: https://pandorafms.com/'), true);
    $table->data[4][3] = html_print_input_text(
        'url_description',
        $url_description,
        '',
        45,
        255,
        true
    );
} else {
    $table->data[5][0] = __('Url address');
    $table->data[5][1] = html_print_input_text(
        'url_description',
        $url_description,
        '',
        45,
        255,
        true
    );
}

$table->data[5][2] = __('Quiet');
$table->data[5][3] .= ui_print_help_tip(__('The agent still runs but the alerts and events will be stop'), true);
$table->data[5][3] = html_print_checkbox('quiet', 1, $quiet, true);

$listIcons = gis_get_array_list_icons();

$arraySelectIcon = [];
foreach ($listIcons as $index => $value) {
    $arraySelectIcon[$index] = $index;
}

$path = 'images/gis_map/icons/';
// TODO set better method the path
$table->data[0][2] = __('Agent icon').ui_print_help_tip(__('Agent icon for GIS Maps.'), true);
if ($icon_path == '') {
    $display_icons = 'none';
    // Hack to show no icon. Use any given image to fix not found image errors
    $path_without = 'images/spinner.png';
    $path_default = 'images/spinner.png';
    $path_ok = 'images/spinner.png';
    $path_bad = 'images/spinner.png';
    $path_warning = 'images/spinner.png';
} else {
    $display_icons = '';
    $path_without = $path.$icon_path.'.default.png';
    $path_default = $path.$icon_path.'.default.png';
    $path_ok = $path.$icon_path.'.ok.png';
    $path_bad = $path.$icon_path.'.bad.png';
    $path_warning = $path.$icon_path.'.warning.png';
}

$table->data[0][3] = html_print_select(
    $arraySelectIcon,
    'icon_path',
    $icon_path,
    'changeIcons();',
    __('None'),
    '',
    true
).'&nbsp;'.html_print_image(
    $path_ok,
    true,
    [
        'id'    => 'icon_ok',
        'style' => 'display:'.$display_icons.';',
    ]
).'&nbsp;'.html_print_image(
    $path_bad,
    true,
    [
        'id'    => 'icon_bad',
        'style' => 'display:'.$display_icons.';',
    ]
).'&nbsp;'.html_print_image(
    $path_warning,
    true,
    [
        'id'    => 'icon_warning',
        'style' => 'display:'.$display_icons.';',
    ]
);

if ($config['activate_gis']) {
    $table->data[3][2] = __('Ignore new GIS data:');
    $table->data[3][3] = __('Yes').' '.html_print_radio_button_extended(
        'update_gis_data',
        0,
        '',
        $update_gis_data,
        false,
        '',
        'style="margin-right: 40px;"',
        true
    );
    $table->data[3][3] .= __('No').' '.html_print_radio_button_extended(
        'update_gis_data',
        1,
        '',
        $update_gis_data,
        false,
        '',
        'style="margin-right: 40px;"',
        true
    );
}

ui_toggle(html_print_table($table, true), __('Advanced options'));
unset($table);

$table = new stdClass();
$table->width = '100%';
$table->class = 'custom_fields_table';

$table->head = [];
$table->style = [];
$table->style[0] = 'font-weight: bold;';
$table->data = [];

$fields = db_get_all_fields_in_table('tagent_custom_fields');

if ($fields === false) {
    $fields = [];
}

foreach ($fields as $field) {
    $id_custom_field = $field['id_field'];

    $data[0] = '<div onclick="show_custom_field_row('.$id_custom_field.')">';
    $data[0] .= '<b>'.$field['name'].'</b>';
    $data[0] .= ui_print_help_tip(
        __('This field allows url insertion using the BBCode\'s url tag').'.<br />'.__('The format is: [url=\'url to navigate\']\'text to show\'[/url]').'.<br /><br />'.__('e.g.: [url=google.com]Google web search[/url]'),
        true
    );
    $data[0] .= '</div>';
    $combo = [];
    $combo = $field['combo_values'];
    $combo = explode(',', $combo);
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

    if ($field['is_password_type']) {
        $data_field[1] = html_print_input_text_extended(
            'customvalue_'.$field['id_field'],
            $custom_value,
            'customvalue_'.$field['id_field'],
            '',
            30,
            100,
            $view_mode,
            '',
            '',
            true,
            true
        );
    } else {
        $data_field[1] = html_print_textarea(
            'customvalue_'.$field['id_field'],
            2,
            65,
            $custom_value,
            'style="min-height: 30px;"',
            true
        );
    }

    if ($field['combo_values'] !== '') {
        $data_field[1] = html_print_select(
            $combo_values,
            'customvalue_'.$field['id_field'],
            $custom_value,
            '',
            __('None'),
            '',
            true,
            false,
            false,
            '',
            false,
            false,
            false,
            false,
            false,
            '',
            false
        );
    };

    $table->rowid[] = 'name_field-'.$id_custom_field;
    array_push($table->data, $data);

    $table->rowid[] = 'field-'.$id_custom_field;
    array_push($table->data, $data_field);
}

if (!empty($fields)) {
    ui_toggle(html_print_table($table, true), __('Custom fields'), '', true, false, 'white_box');
}

echo '<div class="action-buttons" style="width: '.$table->width.'">';


// The context help about the learning mode.
if ($modo == 0) {
    echo "<span id='modules_not_learning_mode_context_help' style=''>";
} else {
    echo "<span id='modules_not_learning_mode_context_help' style='display: none;'>";
}

echo clippy_context_help('modules_not_learning_mode');
echo '</span>';


if ($id_agente) {
    echo '<div class="action-buttons" style="width: '.$table->width.'">';
    html_print_submit_button(
        __('Update'),
        'updbutton',
        false,
        'class="sub upd"'
    );
    html_print_input_hidden('update_agent', 1);
    html_print_input_hidden('id_agente', $id_agente);
} else {
    html_print_submit_button(
        __('Create'),
        'crtbutton',
        false,
        'class="sub wand"'
    );
    html_print_input_hidden('create_agent', 1);
}

echo '</div></form>';

ui_require_jquery_file('pandora.controls');
ui_require_jquery_file('ajaxqueue');
ui_require_jquery_file('bgiframe');
?>

<script type="text/javascript">
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
            $("#modules_not_learning_mode_context_help").show();
        }
        else {
            $("#modules_not_learning_mode_context_help").hide();
        }
    }

    function agent_manager_add_secondary_groups (event, id_agent) {
        event.preventDefault();
        var primary_value = $("#grupo").val()
        // The selected primary value cannot be selected like secondary
        if ($("#secondary_groups option:selected[value=" + primary_value + "]").length > 0) {
            alert("<?php echo __('Primary group cannot be secondary too.'); ?>")
            return
        }

        // On agent creation PHP will update the secondary groups table (not via AJAX)
        if (id_agent == 0) {
            agent_manager_add_secondary_groups_ui();
            agent_manager_update_hidden_input_secondary();
            return;
        }

        var selected_items = new Array();
        $("#secondary_groups option:selected").each(function(){
            selected_items.push($(this).val())
        })

        var data = {
            page: "godmode/agentes/agent_manager",
            id_agent: id_agent,
            groups: selected_items,
            add_secondary_groups: 1,
        }

        // Make the AJAX call to update the secondary groups
        $.ajax({
            type: "POST",
            url: "ajax.php",
            dataType: "html",
            data: data,
            success: function (data) {
                if (data == 1) {
                    agent_manager_add_secondary_groups_ui();
                } else {
                    console.error("Error in AJAX call to add secondary groups")
                }
            },
            error: function (data) {
                console.error("Fatal error in AJAX call to add secondary groups")
            }
        });
    }

    function agent_manager_remove_secondary_groups (event, id_agent) {
        event.preventDefault();

        // On agent creation PHP will update the secondary groups table (not via AJAX)
        if (id_agent == 0) {
            agent_manager_remove_secondary_groups_ui();
            agent_manager_update_hidden_input_secondary();
            return;
        }

        var selected_items = new Array();
        $("#secondary_groups_selected option:selected").each(function(){
            selected_items.push($(this).val())
        })

        var data = {
            page: "godmode/agentes/agent_manager",
            id_agent: id_agent,
            groups: selected_items,
            remove_secondary_groups: 1,
        }

        // Make the AJAX call to update the secondary groups
        $.ajax({
            type: "POST",
            url: "ajax.php",
            dataType: "html",
            data: data,
            success: function (data) {
                if (data == 1) {
                    agent_manager_remove_secondary_groups_ui();
                } else {
                    console.error("Error in AJAX call to add secondary groups")
                }
            },
            error: function (data) {
                console.error("Fatal error in AJAX call to add secondary groups")
            }
        });
    }

    // Move from left input to right input
    function agent_manager_add_secondary_groups_ui () {
        $("#secondary_groups_selected option[value=0]").remove()
        $("#secondary_groups option:selected").each(function() {
            $(this).remove().appendTo("#secondary_groups_selected")
        })
    }

    // Move from right input to left input
    function agent_manager_remove_secondary_groups_ui () {
        // Remove the groups selected if success
        $("#secondary_groups_selected option:selected").each(function(){
            $(this).remove().appendTo("#secondary_groups")
        })

        // Add none if empty select
        if ($("#secondary_groups_selected option").length == 0) {
            $("#secondary_groups_selected").append($('<option>',{
                value: 0,
                text: "<?php echo __('None'); ?>"
            }))
        }
    }

    function agent_manager_update_hidden_input_secondary () {
        var groups = [];
        if(!$('form[name="conf_agent"] #secondary_hidden').length) {
            $('form[name="conf_agent"]').append(
                '<input name="secondary_hidden" type="hidden" id="secondary_hidden">'
            );
        }

        var groups = new Array();
        $("#secondary_groups_selected option").each(function() {
            groups.push($(this).val())
        })

        $("#secondary_hidden").val(groups.join(','));
    }

    $(document).ready (function() {
        $("select#id_os").pandoraSelectOS ();

        var checked = $("#checkbox-cascade_protection").is(":checked");
        if (checked) {
            $("#cascade_protection_module").removeAttr("disabled");
        }
        else {
            $("#cascade_protection_module").attr("disabled", 'disabled');
        }

        $("#checkbox-cascade_protection").change(function () {
            var checked = $("#checkbox-cascade_protection").is(":checked");
    
            if (checked) {
                $("#cascade_protection_module").removeAttr("disabled");
            }
            else {
                $("#cascade_protection_module").val(0);
                $("#cascade_protection_module").attr("disabled", 'disabled');
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

        paint_qrcode(
            "<?php echo ui_get_full_url('mobile/index.php?page=agent&id='.$id_agente); ?>",
            "#qr_code_agent_view",
            128,
            128
        );
        $("#text-agente").prop('disabled', true);

    });
</script>
