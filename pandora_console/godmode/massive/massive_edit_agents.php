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
check_login();

if (! check_acl($config['id_user'], 0, 'AW')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access massive agent deletion section'
    );
    include 'general/noaccess.php';
    return;
}

require_once 'include/functions_agents.php';
require_once 'include/functions_ui.php';
require_once 'include/functions_alerts.php';
require_once 'include/functions_modules.php';
require_once 'include/functions_servers.php';
require_once 'include/functions_gis.php';
require_once 'include/functions_users.php';
enterprise_include_once('include/functions_config_agents.php');

if (is_ajax()) {
    $get_n_conf_files = (bool) get_parameter('get_n_conf_files');
    if ($get_n_conf_files) {
        $id_agents = get_parameter('id_agents');
        $cont = 0;
        foreach ($id_agents as $id_agent) {
            $name = agents_get_name($id_agent);
            $agent_md5 = md5($name);
            if (file_exists($config['remote_config'].'/md5/'.$agent_md5.'.md5')) {
                $cont ++;
            }
        }

        echo $cont;
        return;
    }
}

$update_agents = get_parameter('update_agents', 0);
$recursion = get_parameter('recursion');

if ($update_agents) {
    $values = [];
    if (get_parameter('group', '') != -1) {
        $values['id_grupo'] = get_parameter('group');
    }

    if (!(get_parameter('interval_select') == -1 && empty(get_parameter('interval_text')))) {
        if (get_parameter('interval', 0) != 0) {
            $values['intervalo'] = get_parameter('interval');
        }
    }

    if (get_parameter('id_os', '') != -1) {
        $values['id_os'] = get_parameter('id_os');
    }

    if (get_parameter('id_parent', '') != '') {
        $values['id_parent'] = get_parameter('id_agent_parent', 0);
    }

    if (get_parameter('server_name', '') != -1) {
        $values['server_name'] = get_parameter('server_name');
    }

    if (get_parameter('description', '') != '') {
        $values['comentarios'] = get_parameter('description');
    }

    if (get_parameter('mode', '') != -1) {
        $values['modo'] = get_parameter('mode');
    }

    if (get_parameter('disabled', '') != -1) {
        $values['disabled'] = get_parameter('disabled');
    }

    if (get_parameter('icon_path', '') != '') {
        $values['icon_path'] = get_parameter('icon_path');
    }

    if (get_parameter('update_gis_data', -1) != -1) {
        $values['update_gis_data'] = get_parameter('update_gis_data');
    }

    if (get_parameter('custom_id', '') != '') {
        $values['custom_id'] = get_parameter('custom_id');
    }

    if (get_parameter('cascade_protection', -1) != -1) {
        $values['cascade_protection'] = get_parameter('cascade_protection');
    }

    if (get_parameter('cascade_protection_module', -1) != -1) {
        $values['cascade_protection_module'] = get_parameter('cascade_protection_module');
    }

    if (get_parameter('delete_conf', 0) != 0) {
        $values['delete_conf'] = get_parameter('delete_conf');
    }

    if (get_parameter('quiet_select', -1) != -1) {
        $values['quiet'] = get_parameter('quiet_select');
    }

    if (get_parameter('safe_mode_change', -1) == 1 && get_parameter('safe_mode_module', '') != '') {
        // Get the module name.
        $values['safe_mode_module'] = get_parameter('safe_mode_module');
    } else if (get_parameter('safe_mode_change', -1) == 0) {
        // Disabled Safe Operation Mode.
        $values['safe_mode_module'] = '0';
    }

    $fields = db_get_all_fields_in_table('tagent_custom_fields');

    if ($fields === false) {
        $fields = [];
    }

    $id_agents = get_parameter('id_agents', false);
    if (!$id_agents) {
        ui_print_error_message(__('No agents selected'));
        $id_agents = [];
    } else {
        if (empty($values) && empty($fields)) {
            ui_print_error_message(__('No values changed'));
            $id_agents = [];
        }
    }

    // Get the id_agente_modulo to update the 'safe_operation_mode' field.
    if (isset($values['safe_mode_module']) && ($values['safe_mode_module'] != '0')) {
        foreach ($id_agents as $id_agent) {
            $id_module_safe[$id_agent] = db_get_value_filter(
                'id_agente_modulo',
                'tagente_modulo',
                [
                    'id_agente' => $id_agent,
                    'nombre'    => $values['safe_mode_module'],
                ]
            );
        }
    }

    // CONF FILE DELETION
    if (isset($values['delete_conf'])) {
        unset($values['delete_conf']);
        $n_deleted = 0;
        foreach ($id_agents as $id_agent) {
            $agent_md5 = md5(agents_get_name($id_agent));
            @unlink(
                $config['remote_config'].'/md5/'.$agent_md5.'.md5'
            );
            $result = @unlink(
                $config['remote_config'].'/conf/'.$agent_md5.'.conf'
            );

            $n_deleted += (int) $result;
        }


        if ($n_deleted > 0) {
            db_pandora_audit('Massive management', 'Delete conf file '.$id_agent);
        } else {
            db_pandora_audit('Massive management', 'Try to delete conf file '.$id_agent);
        }


        ui_print_result_message(
            $n_deleted > 0,
            __('Configuration files deleted successfully').'('.$n_deleted.')',
            __('Configuration files cannot be deleted')
        );
    }

    if (empty($values) && empty($fields)) {
        $id_agents = [];
    }

    $n_edited = 0;
    $result = false;
    foreach ($id_agents as $id_agent) {
        $old_interval_value = db_get_value_filter('intervalo', 'tagente', ['id_agente' => $id_agent]);

        if (!empty($values)) {
            $group_old = false;
            $disabled_old = false;
            if ($values['id_grupo'] || isset($values['disabled'])) {
                $values_old = db_get_row_filter('tagente', ['id_agente' => $id_agent], ['id_grupo', 'disabled']);
                if ($values_old['id_grupo']) {
                    $group_old = $values_old['id_grupo'];
                }

                if (isset($values['disabled'])) {
                    $disabled_old = $values_old['disabled'];
                }
            }

            // Get the id_agent_module for this agent to update the 'safe_operation_mode' field.
            if (isset($values['safe_mode_module']) && ($values['safe_mode_module'] != '0')) {
                $values['safe_mode_module'] = $id_module_safe[$id_agent];
            }

            $result = db_process_sql_update(
                'tagente',
                $values,
                ['id_agente' => $id_agent]
            );

            if ($result && $config['metaconsole_agent_cache'] == 1) {
                $server_name['server_name'] = db_get_sql('SELECT server_name FROM tagente WHERE id_agente ='.$id_agent);
                // Force an update of the agent cache.
                $result_metaconsole = agent_update_from_cache($id_agent, $values, $server_name);
            }

            // Update the configuration files.
            if ($result && ($old_interval_value != $values['intervalo']) && !empty($values['intervalo'])) {
                enterprise_hook(
                    'config_agents_update_config_token',
                    [
                        $id_agent,
                        'interval',
                        $values['intervalo'],
                    ]
                );
            }

            if ($disabled_old !== false && $disabled_old != $values['disabled']) {
                enterprise_hook(
                    'config_agents_update_config_token',
                    [
                        $id_agent,
                        'standby',
                        $values['disabled'],
                    ]
                );
                // Validate alerts for disabled agents.
                if ($values['disabled'] == 1) {
                    alerts_validate_alert_agent($id_agent);
                }
            }

            if ($group_old || $result) {
                if ($group_old && $group_old != null) {
                    $tpolicy_group_old = db_get_all_rows_sql(
                        'SELECT id_policy FROM tpolicy_groups 
						WHERE id_group = '.$group_old
                    );
                } else {
                    $tpolicy_group_old = db_get_all_rows_sql('SELECT id_policy FROM tpolicy_groups');
                }

                if ($tpolicy_group_old) {
                    foreach ($tpolicy_group_old as $key => $value) {
                        $tpolicy_agents_old = db_get_sql(
                            'SELECT * FROM tpolicy_agents 
							WHERE id_policy = '.$value['id_policy'].' AND id_agent = '.$id_agent
                        );

                        if ($tpolicy_agents_old) {
                            $result2 = db_process_sql_update(
                                'tpolicy_agents',
                                ['pending_delete' => 1],
                                [
                                    'id_agent'  => $id_agent,
                                    'id_policy' => $value['id_policy'],
                                ]
                            );
                        }
                    }
                }

                if ($values['id_grupo'] && $values['id_grupo'] != null) {
                    $tpolicy_group_new = db_get_all_rows_sql(
                        'SELECT id_policy FROM tpolicy_groups 
						WHERE id_group = '.$values['id_grupo']
                    );
                } else {
                    $tpolicy_group_new = db_get_all_rows_sql('SELECT id_policy FROM tpolicy_groups');
                }

                if ($tpolicy_group_new) {
                    foreach ($tpolicy_group_new as $key => $value) {
                        $tpolicy_agents_new = db_get_sql(
                            'SELECT * FROM tpolicy_agents 
							WHERE id_policy = '.$value['id_policy'].' AND id_agent ='.$id_agent
                        );

                        if (!$tpolicy_agents_new) {
                            db_process_sql_insert(
                                'tpolicy_agents',
                                [
                                    'id_policy' => $value['id_policy'],
                                    'id_agent'  => $id_agent,
                                ]
                            );
                        } else {
                            $result3 = db_process_sql_update(
                                'tpolicy_agents',
                                ['pending_delete' => 0],
                                [
                                    'id_agent'  => $id_agent,
                                    'id_policy' => $value['id_policy'],
                                ]
                            );
                        }
                    }
                }
            }
        }

        $info = [];
        // Update Custom Fields
        foreach ($fields as $field) {
            $info[$field['id_field']] = $field['name'];
            if (get_parameter_post('customvalue_'.$field['id_field'], '') != '') {
                $key = $field['id_field'];
                $value = get_parameter_post('customvalue_'.$field['id_field'], '');

                $old_value = db_get_all_rows_filter('tagent_custom_data', ['id_agent' => $id_agent, 'id_field' => $key]);



                if ($old_value === false) {
                    // Create custom field if not exist
                    $result = db_process_sql_insert(
                        'tagent_custom_data',
                        [
                            'id_field'    => $key,
                            'id_agent'    => $id_agent,
                            'description' => $value,
                        ]
                    );
                } else {
                    $result = db_process_sql_update(
                        'tagent_custom_data',
                        ['description' => $value],
                        [
                            'id_field' => $key,
                            'id_agent' => $id_agent,
                        ]
                    );
                }
            }
        }

        $n_edited += (int) $result;
    }


    if ($result !== false) {
        db_pandora_audit('Massive management', 'Update agent '.$id_agent, false, false, json_encode($info));
    } else {
        if (isset($id_agent)) {
            db_pandora_audit('Massive management', 'Try to update agent '.$id_agent, false, false, json_encode($info));
        }
    }


    ui_print_result_message(
        $result !== false,
        __('Agents updated successfully').'('.$n_edited.')',
        __('Agents cannot be updated (maybe there was no field to update)')
    );
}

$id_group = 0;

$groups = users_get_groups();

$table = new StdClass();
$table->id = 'delete_table';
$table->class = 'databox filters';
$table->width = '100%';
$table->data = [];
$table->style = [];
$table->style[0] = 'font-weight: bold;';
$table->style[2] = 'font-weight: bold';
$table->size = [];
$table->size[0] = '15%';
$table->size[1] = '35%';
$table->size[2] = '15%';
$table->size[3] = '35%';

$table->data = [];
$table->data[0][0] = __('Group');
$table->data[0][1] = html_print_select_groups(
    false,
    'AW',
    true,
    'id_group',
    $id_group,
    false,
    '',
    '',
    true
);
$table->data[0][2] = __('Group recursion');
$table->data[0][3] = html_print_checkbox(
    'recursion2',
    1,
    $recursion,
    true,
    false
);


$status_list = [];
$status_list[AGENT_STATUS_NORMAL] = __('Normal');
$status_list[AGENT_STATUS_WARNING] = __('Warning');
$status_list[AGENT_STATUS_CRITICAL] = __('Critical');
$status_list[AGENT_STATUS_UNKNOWN] = __('Unknown');
$status_list[AGENT_STATUS_NOT_NORMAL] = __('Not normal');
$status_list[AGENT_STATUS_NOT_INIT] = __('Not init');
$table->data[1][0] = __('Status');
$table->data[1][1] = html_print_select(
    $status_list,
    'status_agents',
    'selected',
    '',
    __('All'),
    AGENT_STATUS_ALL,
    true
);
$table->data[1][2] = __('Show agents');
$table->data[1][3] = html_print_select(
    [
        0 => 'Only enabled',
        1 => 'Only disabled',
    ],
    'disabled',
    2,
    '',
    __('All'),
    2,
    true,
    '',
    '',
    '',
    '',
    'width:30%;'
);
$table->data[2][0] = __('Agents');
$table->data[2][0] .= '<span id="agent_loading" class="invisible">';
$table->data[2][0] .= html_print_image('images/spinner.png', true);
$table->data[2][0] .= '</span>';
$enabled_agents = agents_get_group_agents(array_keys(users_get_groups($config['id_user'], 'AW', false)), ['disabled' => 0], 'none');
$all_agents = (agents_get_group_agents(array_keys(users_get_groups($config['id_user'], 'AW', false)), ['disabled' => 1], 'none') + $enabled_agents);

$table->data[2][1] = html_print_select(
    $all_agents,
    'id_agents[]',
    0,
    false,
    '',
    '',
    true,
    true
);

echo '<form method="post" id="form_agent" action="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&option=edit_agents">';
html_print_table($table);

$nombre_agente = '';
$direccion_agente = '';
$id_agente = 0;
$id_parent = 0;
$cascade_protection = 0;
$group = 0;
$interval = '';
$id_os = 0;
$server_name = 0;
$description = '';

echo '<div id="form_agents" style="display: none;">';

$table = new StdClass();
$table->width = '100%';
$table->class = 'databox filters';

$table->head = [];
$table->style = [];
$table->style[0] = 'font-weight: bold; width: 150px;';
$table->size[0] = '15%';
$table->size[1] = '35%';
$table->size[2] = '15%';
$table->size[3] = '35%';

$table->data = [];

$groups = users_get_groups($config['id_user'], 'AW', false);
$agents = agents_get_group_agents(array_keys($groups));

$modules = db_get_all_rows_sql(
    'SELECT id_agente_modulo as id_module, nombre as name FROM tagente_modulo 
								WHERE id_agente = '.$id_parent
);
if ($modules === false) {
    $modules = [];
}

$modules_values = [];
$modules_values[0] = __('Any');
foreach ($modules as $m) {
    $modules_values[$m['id_module']] = $m['name'];
}

$table->data[0][0] = __('Parent');
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
$table->data[0][1] = ui_print_agent_autocomplete_input($params);

$table->data[0][1] .= '<b>'.__('Cascade protection').'</b>'.html_print_select(
    [
        1 => __('Yes'),
        0 => __('No'),
    ],
    'cascade_protection',
    -1,
    '',
    __('No change'),
    -1,
    true
);

$table->data[0][1] .= '&nbsp;&nbsp;'.__('Module').'&nbsp;'.html_print_select($modules, 'cascade_protection_module', $cascade_protection_module, '', '', 0, true);

$table->data[1][0] = __('Group');
$table->data[1][1] = html_print_select_groups(false, 'AR', false, 'group', $group, '', __('No change'), -1, true, false, true, '', false, 'width: 150px;');

$table->data[2][0] = __('Interval');

$table->data[2][1] = html_print_extended_select_for_time('interval', 0, '', __('No change'), '0', 10, true, 'width: 150px', false);

$table->data[3][0] = __('OS');
$table->data[3][1] = html_print_select_from_sql(
    'SELECT id_os, name FROM tconfig_os',
    'id_os',
    $id_os,
    '',
    __('No change'),
    -1,
    true,
    false,
    true,
    false,
    'width: 105px;'
);
$table->data[3][1] .= ' <span id="os_preview">';
$table->data[3][1] .= ui_print_os_icon($id_os, false, true);
$table->data[3][1] .= '</span>';

// Network server
$none = '';
if ($server_name == '' && $id_agente) {
    $none = __('None');
}

$table->data[4][0] = __('Server');
$table->data[4][1] = html_print_select(
    servers_get_names(),
    'server_name',
    $server_name,
    '',
    __('No change'),
    -1,
    true,
    false,
    true,
    '',
    false,
    'width: 150px;'
);

// Description
$table->data[5][0] = __('Description');
$table->data[5][1] = html_print_input_text('description', $description, '', 45, 255, true);

html_print_table($table);
unset($table);

$custom_id = '';
$mode = -1;
$disabled = -1;
$new_agent = true;
$icon_path = '';
$update_gis_data = -1;
$cascade_protection = -1;
$cascade_protection_module = -1;
$quiet_select = -1;

$table = new StdClass();
$table->width = '100%';
$table->class = 'databox filters';

$table->size[0] = '15%';
$table->size[1] = '35%';
$table->size[2] = '15%';
$table->size[3] = '35%';

$table->head = [];
$table->style = [];
$table->style[0] = 'font-weight: bold; width: 150px;';
$table->data = [];

// Custom ID
$table->data[0][0] = __('Custom ID');
$table->data[0][1] = html_print_input_text('custom_id', $custom_id, '', 16, 255, true);

// Learn mode / Normal mode
$table->data[1][0] = __('Module definition');
$table->data[1][1] = __('No change').' '.html_print_radio_button_extended('mode', -1, '', $mode, false, '', 'style="margin-right: 40px;"', true);
$table->data[1][1] .= __('Learning mode').' '.html_print_radio_button_extended('mode', 1, '', $mode, false, '', 'style="margin-right: 40px;"', true);
$table->data[1][1] .= __('Normal mode').' '.html_print_radio_button_extended('mode', 0, '', $mode, false, '', 'style="margin-right: 40px;"', true);
$table->data[1][1] .= __('Autodisable mode').' '.html_print_radio_button_extended('mode', 2, '', $mode, false, '', 'style="margin-right: 40px;"', true);

// Status (Disabled / Enabled)
$table->data[2][0] = __('Status');
$table->data[2][1] = __('No change').' '.html_print_radio_button_extended('disabled', -1, '', $disabled, false, '', 'style="margin-right: 40px;"', true);
$table->data[2][1] .= __('Disabled').' '.ui_print_help_tip(__('If the remote configuration is enabled, it will also go into standby mode when disabling it.'), true).' '.html_print_radio_button_extended('disabled', 1, '', $disabled, false, '', 'style="margin-right: 40px;"', true);
$table->data[2][1] .= __('Active').' '.html_print_radio_button_extended('disabled', 0, '', $disabled, false, '', 'style="margin-right: 40px;"', true);

// Remote configuration
$table->data[3][0] = __('Remote configuration');

// Delete remote configuration
$table->data[3][1] = '<div id="delete_configurations" style="display: none">'.__('Delete available remote configurations').' (';
$table->data[3][1] .= '<span id="n_configurations"></span>';
$table->data[3][1] .= ') '.html_print_checkbox_extended('delete_conf', 1, 0, false, '', 'style="margin-right: 40px;"', true).'</div>';

$table->data[3][1] .= '<div id="not_available_configurations" style="display: none"><em>'.__('Not available').'</em></div>';

$listIcons = gis_get_array_list_icons();

$arraySelectIcon = [];
foreach ($listIcons as $index => $value) {
    $arraySelectIcon[$index] = $index;
}

$path = 'images/gis_map/icons/';
// TODO set better method the path
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

$table->data[4][0] = __('Agent icon');
$table->data[4][1] = html_print_select($arraySelectIcon, 'icon_path', $icon_path, 'changeIcons();', __('No change'), '', true).'&nbsp;'.__('Without status').': '.html_print_image($path_without, true, ['id' => 'icon_without_status', 'style' => 'display:'.$display_icons.';']).'&nbsp;'.__('Default').': '.html_print_image($path_default, true, ['id' => 'icon_default', 'style' => 'display:'.$display_icons.';']).'&nbsp;'.__('Ok').': '.html_print_image($path_ok, true, ['id' => 'icon_ok', 'style' => 'display:'.$display_icons.';']).'&nbsp;'.__('Bad').': '.html_print_image($path_bad, true, ['id' => 'icon_bad', 'style' => 'display:'.$display_icons.';']).'&nbsp;'.__('Warning').': '.html_print_image($path_warning, true, ['id' => 'icon_warning', 'style' => 'display:'.$display_icons.';']);

if ($config['activate_gis']) {
    $table->data[5][0] = __('Ignore new GIS data:');
    $table->data[5][1] = __('No change').' '.html_print_radio_button_extended('update_gis_data', -1, '', $update_gis_data, false, '', 'style="margin-right: 40px;"', true);
    $table->data[5][1] .= __('Yes').' '.html_print_radio_button_extended('update_gis_data', 0, '', $update_gis_data, false, '', 'style="margin-right: 40px;"', true);
    $table->data[5][1] .= __('No').' '.html_print_radio_button_extended('update_gis_data', 1, '', $update_gis_data, false, '', 'style="margin-right: 40px;"', true);
}

$table->data[6][0] = __('Quiet');
$table->data[6][0] .= ui_print_help_tip(__('The agent still runs but the alerts and events will be stop'), true);
$table->data[6][1] = html_print_select(
    [
        -1 => __('No change'),
        1  => __('Yes'),
        0  => __('No'),
    ],
    'quiet_select',
    $quiet_select,
    '',
    '',
    0,
    true
);

$table->data[7][0] = __('Safe operation mode').': '.ui_print_help_tip(
    __(
        'This mode allow %s to disable all modules 
of this agent while the selected module is on CRITICAL status',
        get_product_name()
    ),
    true
);
$table->data[7][1] .= html_print_select(
    [
        1 => __('Enabled'),
        0 => __('Disabled'),
    ],
    'safe_mode_change',
    -1,
    '',
    __('No change'),
    -1,
    true
).'&nbsp;';
$table->data[7][1] .= __('Module').'&nbsp;'.html_print_select('', 'safe_mode_module', '', '', __('Any'), -1, true).'</div>';
ui_toggle(html_print_table($table, true), __('Advanced options'));
unset($table);

$table = new StdClass();
$table->width = '100%';
$table->class = 'databox filters';

$table->head = [];
$table->style = [];
$table->style[0] = 'font-weight: bold; width: 150px;';
$table->data = [];
$table->size[0] = '15%';
$table->size[1] = '35%';
$table->size[2] = '15%';
$table->size[3] = '35%';

$fields = db_get_all_fields_in_table('tagent_custom_fields');

if ($fields === false) {
    $fields = [];
}

foreach ($fields as $field) {
    $data[0] = '<b>'.$field['name'].'</b>';
    $data[0] .= ui_print_help_tip(
        __('This field allows url insertion using the BBCode\'s url tag').'.<br />'.__('The format is: [url=\'url to navigate\']\'text to show\'[/url] or [url]\'url to navigate\'[/url] ').'.<br /><br />'.__('e.g.: [url=google.com]Google web search[/url] or [url]www.goole.com[/url]'),
        true
    );
    $combo = [];
    $combo = $field['combo_values'];
    $combo = explode(',', $combo);
    $combo_values = [];
    foreach ($combo as $value) {
        $combo_values[$value] = $value;
    }

    $custom_value = db_get_value_filter('description', 'tagent_custom_data', ['id_field' => $field['id_field'], 'id_agent' => $id_agente]);

    if ($custom_value === false) {
        $custom_value = '';
    }

    if ($field['is_password_type']) {
        $data[1] = html_print_input_text_extended(
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
        $data[1] = html_print_textarea('customvalue_'.$field['id_field'], 2, 65, $custom_value, 'style="min-height: 30px;"', true);
    }

    if ($field['combo_values'] !== '') {
        $data[1] = html_print_select(
            $combo_values,
            'customvalue_'.$field['id_field'],
            $custom_value,
            '',
            __('No change'),
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

    array_push($table->data, $data);
}

if (!empty($fields)) {
    ui_toggle(html_print_table($table, true), __('Custom fields'));
}


echo '<h3 class="error invisible" id="message"> </h3>';

echo '<div class="action-buttons" style="width: '.$table->width.'">';

html_print_submit_button(__('Update'), 'updbutton', false, 'class="sub upd"');
html_print_input_hidden('update_agents', 1);
html_print_input_hidden('id_agente', $id_agente);

echo '</div>';
// Shown and hide div
echo '</div></form>';

ui_require_jquery_file('form');
ui_require_jquery_file('pandora.controls');


ui_require_jquery_file('pandora.controls');
ui_require_jquery_file('ajaxqueue');
ui_require_jquery_file('bgiframe');
?>
<script type="text/javascript">
/* <![CDATA[ */

var limit_parameters_massive = <?php echo $config['limit_parameters_massive']; ?>;

//Use this function for change 3 icons when change the selectbox
$(document).ready (function () {
    var checked = $("#cascade_protection").val();
    
    $("#cascade_protection_module").attr("disabled", 'disabled');

    $("#cascade_protection").change(function () {
        var checked = $("#cascade_protection").val();

        if (checked == 1) {
            $("#cascade_protection_module").removeAttr("disabled");
        }
        else {
            $("#cascade_protection_module").val(0);
            $("#cascade_protection_module").attr("disabled", 'disabled');
        }
    });


    // Enable Safe Operation Mode if 'Enabled' is selected.
    $("#safe_mode_module").attr("disabled", "disabled");
    $("#safe_mode_change").on('change', function() {
        if ($("#safe_mode_change").val() == 1) {
            $("#safe_mode_module").removeAttr("disabled");
            refreshSafeModules();
        }
        else {
            $("#safe_mode_module").attr("disabled", "disabled");
            $('#safe_mode_module').empty();
            $("#safe_mode_module").append($("<option></option>").attr("value", 'Any').html('Any'));
        } 
    });
 
    // Fill modules in Safe Operation Mode.
    function refreshSafeModules(){
        var idAgents = Array();
        jQuery.each ($("#id_agents option:selected"), function (i, val) {
            idAgents.push($(val).val());
        });

        var params = {
            "page" : "operation/agentes/ver_agente",
            "get_agent_modules_json_for_multiple_agents" : 1,
            "id_agent" : idAgents,
            "selection_mode": "common"
        };
        
        jQuery.post ("ajax.php",
            params,
            function (data, status) {
                $('#safe_mode_module').empty();
                if($.type(data) === "object"){
                    jQuery.each (data, function (id, value) {
                        option = $("<option></option>").attr("value", value).html(value);
                        $("#safe_mode_module").append(option);
                    });
                } else {
                    option = $("<option></option>").attr("value", 'None').html('None');
                    $("#safe_mode_module").append(option);
                }
            },
            "json"
        );
    }


    $("#form_agent").submit(function() {
        var get_parameters_count = window.location.href.slice(
            window.location.href.indexOf('?') + 1).split('&').length;
        var post_parameters_count = $("#form_agent").serializeArray().length;
        
        var count_parameters =
            get_parameters_count + post_parameters_count;
        
        if (count_parameters > limit_parameters_massive) {
            alert("<?php echo __('Unsucessful sending the data, please contact with your administrator or make with less elements.'); ?>");
            return false;
        }
    });
    
    var disabled;
    
    $("#disabled").click(function () {
    
            disabled = this.value;
    
         $("#id_group").trigger("change");
    });
    
    $('#id_agents').on('change', function() {
        var idAgents = Array();
        jQuery.each ($("#id_agents option:selected"), function (i, val) {
            idAgents.push($(val).val());
        });
        jQuery.post ("ajax.php",
                {"page" : "godmode/massive/massive_edit_agents",
                "get_n_conf_files" : 1,
                "id_agents[]" : idAgents
                },
                function (data, status) {
                    if (data == 0) { 
                        $("#delete_configurations").attr("style", "display: none");
                        $("#not_available_configurations").attr("style", "");
                    }
                    else {
                        $("#n_configurations").text(data);
                        $("#not_available_configurations").attr("style", "display: none");
                        $("#delete_configurations").attr("style", "");
                    }
                },
                "json"
            );
        
        $("#form_agents").attr("style", "");

        if($("#safe_mode_change").val() == 1) {
            refreshSafeModules();
        }
    });
    
    $("#id_group").change (function () {
        $("#form_agents").attr("style", "display: none");
    });
    
    $("select#id_os").pandoraSelectOS ();
    
    var recursion;
    $("#checkbox-recursion2").click(function () {
        recursion = this.checked ? 1 : 0;
        $("#id_group").trigger("change");
    });
    
    $("#id_group").pandoraSelectGroupAgent ({
        status_agents: function () {
            return $("#status_agents").val();
        },
        agentSelect: "select#id_agents",
        privilege: "AW",
        recursion: function() {
            return recursion;
        },
        disabled: function() {
            return disabled;
        }
    });
    
    $("#status_agents").change(function() {
        $("#id_group").trigger("change");
    });
    
    
    disabled = 2;

 $("#id_group").trigger("change");
    
});

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
</script>
