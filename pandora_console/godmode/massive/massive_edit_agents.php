<?php
/**
 * View for edit agents in Massive Operations
 *
 * @category   Configuration
 * @package    Pandora FMS
 * @subpackage Massive Operations
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2022 Artica Soluciones Tecnologicas
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

use PandoraFMS\Agent;
use PandoraFMS\Enterprise\Metaconsole\Node;

// Begin.
check_login();

if (! check_acl($config['id_user'], 0, 'AW')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access massive agent deletion section'
    );
    include 'general/noaccess.php';
    return;
}

require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_ui.php';
require_once $config['homedir'].'/include/functions_alerts.php';
require_once $config['homedir'].'/include/functions_modules.php';
require_once $config['homedir'].'/include/functions_servers.php';
require_once $config['homedir'].'/include/functions_gis.php';
require_once $config['homedir'].'/include/functions_users.php';
enterprise_include_once('include/functions_config_agents.php');

if (is_ajax() === true) {
    $get_n_conf_files = (bool) get_parameter(
        'get_n_conf_files',
        false
    );

    $groups_secondary_selected = (bool) get_parameter(
        'groups_secondary_selected',
        false
    );

    if ($get_n_conf_files === true) {
        $id_agents = get_parameter('id_agents');
        $cont = 0;
        foreach ($id_agents as $id_agent) {
            if (is_metaconsole() === true) {
                $array_id = explode('|', $id_agent);
                try {
                    $node = new Node((int) $array_id[0]);
                    $node->connect();

                    $agent = new Agent((int) $array_id[1]);
                    if ($agent->hasRemoteConf() === true) {
                        $cont++;
                    }

                    $node->disconnect();
                } catch (\Exception $e) {
                    // Unexistent agent.
                    $cont = 0;
                    $node->disconnect();
                }
            } else {
                try {
                    $agent = new Agent((int) $array_id[1]);
                    if ($agent->hasRemoteConf() === true) {
                        $cont++;
                    }
                } catch (\Exception $e) {
                    // Unexistent agent.
                    $cont = 0;
                }
            }
        }

        echo $cont;
        return;
    }

    if ($groups_secondary_selected === true) {
        $groups = get_parameter('groups', []);
        $groups_selected = get_parameter('groups_selected', []);

        $user_groups = users_get_groups($config['user'], 'AR', false);
        $ret = [];
        foreach ($user_groups as $id_gr => $name_group) {
            if (in_array($id_gr, $groups) === false) {
                $ret[$id_gr] = $name_group;
            }
        }

        echo json_encode($ret);
        return;
    }
}

$update_agents = get_parameter('update_agents', 0);
$recursion = get_parameter('recursion');

if ($update_agents) {
    $values = [];

    if ((int) get_parameter('group', '') !== -1) {
        $values['id_grupo'] = get_parameter('group');
    }

    if (!(get_parameter('interval_select') === -1
        && empty(get_parameter('interval_text')))
    ) {
        if (get_parameter('interval') != -2) {
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

    $secondary_groups_added = (array) get_parameter(
        'secondary_groups_added',
        []
    );
    $secondary_groups_removed = (array) get_parameter(
        'secondary_groups_removed',
        []
    );

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

    $id_module_safe = [];
    if (is_metaconsole() === false) {
        // Get the id_agente_modulo to update the 'safe_operation_mode' field.
        if (isset($values['safe_mode_module']) === true
            && ($values['safe_mode_module'] != '0')
        ) {
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
    }

    // CONF FILE DELETION.
    if (isset($values['delete_conf']) === true) {
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
            db_pandora_audit(
                AUDIT_LOG_MASSIVE_MANAGEMENT,
                'Delete conf file '.$id_agent
            );
        } else {
            db_pandora_audit(
                AUDIT_LOG_MASSIVE_MANAGEMENT,
                'Try to delete conf file '.$id_agent
            );
        }


        ui_print_result_message(
            $n_deleted > 0,
            __('Configuration files deleted successfully').'('.$n_deleted.')',
            __('Configuration files cannot be deleted')
        );
    }

    if (empty($values) === true
        && empty($fields) === true
    ) {
        $id_agents = [];
    }

    $result = [];
    foreach ($id_agents as $id_agent) {
        if (is_metaconsole() === true) {
            $array_id = explode('|', $id_agent);
            try {
                $node = new Node((int) $array_id[0]);
                $node->connect();

                $id_agent = (int) $array_id[1];

                // Get the id_agente_modulo to update the 'safe_operation_mode' field.
                if (isset($values['safe_mode_module']) === true
                    && ($values['safe_mode_module'] != '0')
                ) {
                    $id_module_safe[$id_agent] = db_get_value_filter(
                        'id_agente_modulo',
                        'tagente_modulo',
                        [
                            'id_agente' => $id_agent,
                            'nombre'    => $values['safe_mode_module'],
                        ]
                    );
                }

                $result[$id_agent] = edit_massive_agent(
                    (int) $array_id[1],
                    $values,
                    $id_module_safe,
                    $fields,
                    $secondary_groups_added,
                    $secondary_groups_removed
                );

                $agents_values = agents_get_agent((int) $array_id[1]);
                $node->disconnect();

                if (empty($values) === false) {
                    update_agents_in_metaconsole(
                        (int) $array_id[1],
                        $values,
                        $agents_values
                    );
                }
            } catch (\Exception $e) {
                // Unexistent agent.
                $result = [];
                $node->disconnect();
            }
        } else {
            try {
                $result[$id_agent] = edit_massive_agent(
                    $id_agent,
                    $values,
                    $id_module_safe,
                    $fields,
                    $secondary_groups_added,
                    $secondary_groups_removed
                );
            } catch (\Exception $e) {
                // Unexistent agent.
                $result = [];
            }
        }
    }

    $ret = [];
    foreach ($result as $id_agent => $item) {
        if ($item['db'] !== false) {
            $ret['db']['edited'] += 1;
            $ret['db']['edited_agent'][] = $id_agent;
        } else {
            $ret['db']['failed'] += 1;
            $ret['db']['failed_agent'][] = $id_agent;
        }

        if (isset($item['fields']) === true
            && empty($item['fields']) === false
        ) {
            foreach ($item['fields'] as $kfield => $vfield) {
                if ($vfield !== false) {
                    $ret['fields'][$id_agent]['edited'] += 1;
                    $ret['fields'][$id_agent]['edited_field'][] = $kfield;
                } else {
                    $ret['fields'][$id_agent]['failed'] += 1;
                    $ret['fields'][$id_agent]['failed_field'][] = $kfield;
                }
            }
        }

        if (isset($item['secondary']) === true
            && empty($item['secondary']) === false
        ) {
            foreach ($item['secondary'] as $type_action => $values_secondary) {
                foreach ($values_secondary as $kgr => $vgr) {
                    if ($vgr !== false) {
                        $ret['secondary'][$type_action][$id_agent]['edited'] += 1;
                        $ret['secondary'][$type_action][$id_agent]['edited_gr'][] = $kgr;
                    } else {
                        $ret['secondary'][$type_action][$id_agent]['failed'] += 1;
                        $ret['secondary'][$type_action][$id_agent]['failed_gr'][] = $kgr;
                    }
                }
            }
        }
    }

    foreach ($ret as $type => $ret_val) {
        switch ($type) {
            case 'db':
                if (isset($ret_val['edited']) === true
                    && $ret_val['edited'] > 0
                ) {
                    ui_print_success_message(
                        __(
                            'Agents updated successfully (%d)',
                            $ret_val['edited'],
                            implode(
                                ',',
                                $ret_val['edited_agent']
                            )
                        )
                    );
                }

                if (isset($ret_val['failed']) === true
                    && $ret_val['failed'] > 0
                ) {
                    ui_print_error_message(
                        __(
                            'Agents cannot be updated (%d), ids (%s)',
                            $ret_val['failed'],
                            implode(',', $ret_val['failed_agent'])
                        )
                    );
                }
            break;

            case 'fields':
                $str = '';
                foreach ($ret_val as $kag => $vag) {
                    if (isset($vag['failed']) === true
                        && $vag['failed'] > 0
                    ) {
                        $str .= __(
                            'Agent ID: %s cannot be updated custom fields (%s)',
                            $kag,
                            implode(',', $vag['failed_field'])
                        ).'<br>';
                    }
                }

                if (empty($str) === false) {
                    ui_print_error_message($str);
                }
            break;

            case 'secondary':
                $str = '';
                foreach ($ret_val as $type => $values_secondary) {
                    foreach ($values_secondary as $kag => $vag) {
                        if (isset($vag['failed']) === true
                            && $vag['failed'] > 0
                        ) {
                            $str .= __(
                                'Agent ID: %s cannot be updated %s secondary groups (%s)',
                                $kag,
                                $type,
                                implode(',', $vag['failed_gr'])
                            ).'<br>';
                        }
                    }
                }

                if (empty($str) === false) {
                    ui_print_error_message($str);
                }
            break;

            default:
                // Not posible.
            break;
        }
    }
}


/**
 * Edit massive agent.
 *
 * @param  integer $id_agent
 * @param  array   $values
 * @param  array   $id_module_safe
 * @param  array   $fields
 * @param  array   $secondary_groups_added
 * @param  array   $secondary_groups_removed
 * @return void
 */
function edit_massive_agent(
    int $id_agent,
    array $values,
    array $id_module_safe,
    array $fields,
    array $secondary_groups_added,
    array $secondary_groups_removed
) {
    global $config;
    $result = false;

    if (empty($values) === false) {
        $agent = new Agent($id_agent);
        $disabled_old = $agent->disabled();

        if (empty($id_module_safe) === false) {
            // Get the id_agent_module for this agent to update the 'safe_operation_mode' field.
            if (isset($values['safe_mode_module']) === true
                && ($values['safe_mode_module'] != '0')
            ) {
                $values['safe_mode_module'] = $id_module_safe[$id_agent];
            }
        }

        foreach ($values as $key => $value) {
            $agent->{$key}($value);
        }

        $result['db'] = $agent->save();

        if (is_metaconsole() === false) {
            if ($result['db'] !== false
                && (bool) $config['metaconsole_agent_cache'] === true
            ) {
                // Force an update of the agent cache.
                $agent->updateFromCache();
            }
        }

        if ($disabled_old !== $values['disabled']) {
            // Validate alerts for disabled agents.
            if ($values['disabled'] == 1) {
                alerts_validate_alert_agent($id_agent);
            }
        }

        if (empty($values['id_grupo']) === false) {
            // Check if group and secondary group match and remove.
            $remove_sg = (bool) db_process_sql_delete(
                'tagent_secondary_group',
                [
                    'id_agent' => (int) $id_agent,
                    'id_group' => (int) $values['id_grupo'],
                ]
            );
        }
    }

    $info = [];
    // Update Custom Fields.
    if (isset($fields) === true
        && empty($fields) === false
    ) {
        foreach ($fields as $field) {
            $info[$field['id_field']] = $field['name'];
            $value = get_parameter('customvalue_'.$field['id_field']);
            if (empty($value) === false) {
                $key = $field['id_field'];
                $old_value = db_get_all_rows_filter(
                    'tagent_custom_data',
                    [
                        'id_agent' => $id_agent,
                        'id_field' => $key,
                    ]
                );

                if ($old_value === false) {
                    // Create custom field if not exist.
                    $result['fields'][$field['id_field']] = db_process_sql_insert(
                        'tagent_custom_data',
                        [
                            'id_field'    => $key,
                            'id_agent'    => $id_agent,
                            'description' => $value,
                        ]
                    );
                } else {
                    if ($old_value[0]['description'] !== $value) {
                        $result['fields'][$field['id_field']] = db_process_sql_update(
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
        }
    }

    // Create or Remove the secondary groups.
    if (empty($secondary_groups_added) === false
        || empty($secondary_groups_removed) === false
    ) {
        $result['secondary'] = enterprise_hook(
            'agents_update_secondary_groups',
            [
                $id_agent,
                $secondary_groups_added,
                $secondary_groups_removed,
                true,
            ]
        );
    }

    if ($result['db'] !== false) {
        db_pandora_audit(
            AUDIT_LOG_MASSIVE_MANAGEMENT,
            'Update agent '.$id_agent,
            false,
            false,
            json_encode($info)
        );
    } else {
        if (isset($id_agent) === true) {
            db_pandora_audit(
                AUDIT_LOG_MASSIVE_MANAGEMENT,
                'Try to update agent '.$id_agent,
                false,
                false,
                json_encode($info)
            );
        }
    }

    return $result;
}


$url = 'index.php?sec=gmassive&sec2=godmode/massive/massive_operations&option=edit_agents';
if (is_metaconsole() === true) {
    $url = 'index.php?sec=advanced&sec2=advanced/massive_operations&tab=massive_agents&pure=0&option=edit_agents';
}

echo '<form method="post" autocomplete="off" id="form_agent" action="'.$url.'">';
echo html_print_avoid_autocomplete();
$params = [
    'id_group'  => $id_group,
    'recursion' => $recursion,
];
echo get_table_inputs_masive_agents($params);

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

echo '<div id="form_agents" style="display:none">';

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

if (is_metaconsole() === false) {
    $modules = db_get_all_rows_sql(
        sprintf(
            'SELECT id_agente_modulo as id_module,
                nombre as name
            FROM tagente_modulo
            WHERE id_agente = %d',
            $id_parent
        )
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

    $table->data[0][1] .= '<b>'.__('Cascade protection').'</b>';
    $table->data[0][1] .= html_print_select(
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

    $table->data[0][1] .= '&nbsp;&nbsp;'.__('Module').'&nbsp;';
    $table->data[0][1] .= html_print_select(
        $modules,
        'cascade_protection_module',
        $cascade_protection_module,
        '',
        '',
        0,
        true
    );
}

$table->data[1][0] = __('Group');
$table->data[1][1] = '<div class="w290px inline">';
$table->data[1][1] .= html_print_select_groups(
    false,
    'AR',
    false,
    'group',
    $group,
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
$table->data[1][1] .= '</div>';

$table->data[2][0] = __('Interval');

$table->data[2][1] = html_print_extended_select_for_time(
    'interval',
    -2,
    '',
    '',
    '0',
    10,
    true,
    'width: 150px',
    false,
    '',
    false,
    false,
    '',
    true
);

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

// Network server.
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

// Description.
$table->data[5][0] = __('Description');
$table->data[5][1] = html_print_input_text(
    'description',
    $description,
    '',
    45,
    255,
    true
);

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

// Custom ID.
$table->data[0][0] = __('Custom ID');
$table->data[0][1] = html_print_input_text(
    'custom_id',
    $custom_id,
    '',
    16,
    255,
    true
);

// Secondary Groups.
if (enterprise_installed() === true) {
    $groups = users_get_groups($config['id_user'], 'AW', false);
    $table->data['secondary_groups_added'][0] = __('Add secondary groups');
    $table->data['secondary_groups_added'][1] = html_print_select(
        $groups,
        'secondary_groups_added[]',
        0,
        false,
        '',
        '',
        true,
        true,
        true,
        '',
        false,
        'min-width: 500px; max-width: 500px; max-height: 100px',
        false,
        false,
        false,
        '',
        false,
        false,
        false,
        false,
        true,
        true
    );

    $table->data['secondary_groups_removed'][0] = __('Remove secondary groups');
    $table->data['secondary_groups_removed'][1] = html_print_select(
        $groups,
        'secondary_groups_removed[]',
        0,
        false,
        '',
        '',
        true,
        true,
        true,
        '',
        false,
        'min-width: 500px; max-width: 500px; max-height: 100px',
        false,
        false,
        false,
        '',
        false,
        false,
        false,
        false,
        true,
        true
    );
}

// Learn mode / Normal mode.
$table->data[1][0] = __('Module definition');
$table->data[1][1] = __('No change').' ';
$table->data[1][1] .= html_print_radio_button_extended(
    'mode',
    -1,
    '',
    $mode,
    false,
    '',
    'class="mrgn_right_40px"',
    true
);
$table->data[1][1] .= __('Learning mode').' ';
$table->data[1][1] .= html_print_radio_button_extended(
    'mode',
    1,
    '',
    $mode,
    false,
    '',
    'class="mrgn_right_40px"',
    true
);
$table->data[1][1] .= __('Normal mode').' ';
$table->data[1][1] .= html_print_radio_button_extended(
    'mode',
    0,
    '',
    $mode,
    false,
    '',
    'class="mrgn_right_40px"',
    true
);
$table->data[1][1] .= __('Autodisable mode').' ';
$table->data[1][1] .= html_print_radio_button_extended(
    'mode',
    2,
    '',
    $mode,
    false,
    '',
    'class="mrgn_right_40px"',
    true
);

// Status (Disabled / Enabled).
$table->data[2][0] = __('Status');
$table->data[2][1] = __('No change').' ';
$table->data[2][1] .= html_print_radio_button_extended(
    'disabled',
    -1,
    '',
    $disabled,
    false,
    '',
    'class="mrgn_right_40px"',
    true
);
$table->data[2][1] .= __('Disabled').' ';
$table->data[2][1] .= ui_print_help_tip(
    __('If the remote configuration is enabled, it will also go into standby mode when disabling it.'),
    true
).' ';
$table->data[2][1] .= html_print_radio_button_extended(
    'disabled',
    1,
    '',
    $disabled,
    false,
    '',
    'class="mrgn_right_40px"',
    true
);
$table->data[2][1] .= __('Active').' ';
$table->data[2][1] .= html_print_radio_button_extended(
    'disabled',
    0,
    '',
    $disabled,
    false,
    '',
    'class="mrgn_right_40px"',
    true
);

// Remote configuration.
$table->data[3][0] = __('Remote configuration');
// Delete remote configuration.
$table->data[3][1] = '<div id="delete_configurations" class="invisible">';
$table->data[3][1] .= __('Delete available remote configurations');
$table->data[3][1] .= ' (';
$table->data[3][1] .= '<span id="n_configurations"></span>';
$table->data[3][1] .= ') ';
$table->data[3][1] .= html_print_checkbox_extended(
    'delete_conf',
    1,
    0,
    false,
    '',
    'class="mrgn_right_40px"',
    true
);
$table->data[3][1] .= '</div>';

$table->data[3][1] .= '<div id="not_available_configurations" class="invisible"><em>';
$table->data[3][1] .= __('Not available');
$table->data[3][1] .= '</em></div>';

$listIcons = gis_get_array_list_icons();

$arraySelectIcon = [];
foreach ($listIcons as $index => $value) {
    $arraySelectIcon[$index] = $index;
}

$path = 'images/gis_map/icons/';
// TODO set better method the path.
if ($icon_path == '') {
    $display_icons = 'none';
    // Hack to show no icon. Use any given image to fix not found image errors.
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
$table->data[4][1] = html_print_select(
    $arraySelectIcon,
    'icon_path',
    $icon_path,
    'changeIcons();',
    __('No change'),
    '',
    true
);
$table->data[4][1] .= '&nbsp;';
$table->data[4][1] .= __('Without status').': ';
$table->data[4][1] .= html_print_image(
    $path_without,
    true,
    [
        'id'    => 'icon_without_status',
        'style' => 'display:'.$display_icons.';',
    ]
);
$table->data[4][1] .= '&nbsp;'.__('Default').': ';
$table->data[4][1] .= html_print_image(
    $path_default,
    true,
    [
        'id'    => 'icon_default',
        'style' => 'display:'.$display_icons.';',
    ]
);
$table->data[4][1] .= '&nbsp;'.__('Ok').': ';
$table->data[4][1] .= html_print_image(
    $path_ok,
    true,
    [
        'id'    => 'icon_ok',
        'style' => 'display:'.$display_icons.';',
    ]
);
$table->data[4][1] .= '&nbsp;'.__('Bad').': ';
$table->data[4][1] .= html_print_image(
    $path_bad,
    true,
    [
        'id'    => 'icon_bad',
        'style' => 'display:'.$display_icons.';',
    ]
);
$table->data[4][1] .= '&nbsp;'.__('Warning').': ';
$table->data[4][1] .= html_print_image(
    $path_warning,
    true,
    [
        'id'    => 'icon_warning',
        'style' => 'display:'.$display_icons.';',
    ]
);

if ($config['activate_gis']) {
    $table->data[5][0] = __('Ignore new GIS data:');
    $table->data[5][1] = __('No change').' ';
    $table->data[5][1] .= html_print_radio_button_extended(
        'update_gis_data',
        -1,
        '',
        $update_gis_data,
        false,
        '',
        'class="mrgn_right_40px"',
        true
    );
    $table->data[5][1] .= __('Yes').' ';
    $table->data[5][1] .= html_print_radio_button_extended(
        'update_gis_data',
        0,
        '',
        $update_gis_data,
        false,
        '',
        'class="mrgn_right_40px"',
        true
    );
    $table->data[5][1] .= __('No').' ';
    $table->data[5][1] .= html_print_radio_button_extended(
        'update_gis_data',
        1,
        '',
        $update_gis_data,
        false,
        '',
        'class="mrgn_right_40px"',
        true
    );
}

$table->data[6][0] = __('Quiet');
$table->data[6][0] .= ui_print_help_tip(
    __('The agent still runs but the alerts and events will be stop'),
    true
);
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
        'This mode allow %s to disable all modules of this agent while the selected module is on CRITICAL status',
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

$table->data[7][1] .= __('Module').'&nbsp;';
$table->data[7][1] .= html_print_select(
    '',
    'safe_mode_module',
    '',
    '',
    __('Any'),
    -1,
    true
);

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
        $data[1] = html_print_input_password_avoid_autocomplete();
        $data[1] .= html_print_input_text_extended(
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
        $data[1] = html_print_textarea(
            'customvalue_'.$field['id_field'],
            2,
            65,
            $custom_value,
            'class="mrgn_right_30px"',
            true
        );
    }

    if ($field['combo_values'] !== '') {
        $data[1] = html_print_input(
            [
                'type'          => 'select_search',
                'fields'        => $combo_values,
                'name'          => 'customvalue_'.$field['id_field'],
                'selected'      => $custom_value,
                'nothing'       => __('No change'),
                'nothing_value' => '',
                'return'        => true,
                'sort'          => false,
            ]
        );
    };

    array_push($table->data, $data);
}

if (empty($fields) === false) {
    ui_toggle(
        html_print_table($table, true),
        __('Custom fields')
    );
}


echo '<h3 class="error invisible" id="message"> </h3>';

html_print_input_hidden('id_agente', $id_agente);

echo '</div>';
attachActionButton('update_agents', 'update', $table->width, false, $SelectAction);
echo '</form>';

// Shown and hide div.
ui_require_jquery_file('form');
ui_require_jquery_file('pandora.controls');
ui_require_jquery_file('ajaxqueue');
ui_require_jquery_file('bgiframe');
?>
<script type="text/javascript">
/* <![CDATA[ */

var limit_parameters_massive = <?php echo $config['limit_parameters_massive']; ?>;

//Use this function for change 3 icons when change the selectbox
$(document).ready (function () {
    // Check Metaconsole.
    var metaconsole = '<?php echo (is_metaconsole() === true) ? 1 : 0; ?>';
    form_controls_massive_operations_agents(metaconsole);

    $("#id_group").change (function () {
        $("#form_agents").attr("style", "display: none");
    });

    $('#id_agents').on('change', function() {
        var idAgents = Array();
        jQuery.each ($("#id_agents option:selected"), function (i, val) {
            idAgents.push($(val).val());
        });
        jQuery.post (
            "ajax.php",
            {
                "page" : "godmode/massive/massive_edit_agents",
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

    $("select#id_os").pandoraSelectOS();

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


    /*
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
    */

    $("#secondary_groups_added").change(
        function() {
            var groups = $("#secondary_groups_added").val();
            var groups_selected = $("#secondary_groups_removed").val();
            jQuery.post (
                "ajax.php",
                {
                    "page" : "godmode/massive/massive_edit_agents",
                    "groups_secondary_selected" : 1,
                    "groups" : groups
                },
                function (data, status) {
                    $('#secondary_groups_removed').empty();
                    $('#secondary_groups_removed').val(null).trigger("change");
                    if($.type(data) === "object"){
                        jQuery.each (data, function (id, value) {
                            option = $("<option></option>").attr("value", id).html(value);
                            if (inArray(id, groups_selected) === true) {
                                option.attr("selected", true);
                            }
                            $("#secondary_groups_removed").append(option).trigger("change");
                        });
                    } else {
                        option = $("<option></option>").attr("value", '').html('None');
                        $("#secondary_groups_removed").append(option).trigger("change");
                    }
                },
                "json"
            );
        }
    );
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
