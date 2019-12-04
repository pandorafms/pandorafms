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
// You can redefine $url and unset $id_agente to reuse the form. Dirty (hope temporal) hack
if (isset($id_agente)) {
    $url = 'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=module&id_agente='.$id_agente;
} else {
    $url = 'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=module';
}

enterprise_include('godmode/agentes/module_manager.php');
$isFunctionPolicies = enterprise_include_once('include/functions_policies.php');
require_once $config['homedir'].'/include/functions_modules.php';
require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_servers.php';

$search_string = io_safe_output(urldecode(trim(get_parameter('search_string', ''))));

global $policy_page;

if (!isset($policy_page)) {
    $policy_page = false;
}

// Search string filter form
if (($policy_page) || (isset($agent))) {
    echo '<form id="" method="post" action="">';
} else {
    echo '<form id="create_module_type" method="post" action="'.$url.'">';
}

echo '<table width="100%" cellpadding="2" cellspacing="2" class="databox filters" >';
echo "<tr><td class='datos' style='width:20%; font-weight: bold;'>";
echo __('Search').' '.html_print_input_text('search_string', $search_string, '', 15, 255, true);
    html_print_input_hidden('search', 1);
echo '</td>';
echo "<td class='datos' style='width:10%'>";
html_print_submit_button(__('Filter'), 'filter', false, 'class="sub search"');
echo '</td>';
echo "<td class='datos' style='width:10%'></td>";
echo '</form>';
// Check if there is at least one server of each type available to assign that
// kind of modules. If not, do not show server type in combo
$network_available = db_get_sql(
    'SELECT count(*)
	FROM tserver
	WHERE server_type = 1'
);
// POSTGRESQL AND ORACLE COMPATIBLE
$wmi_available = db_get_sql(
    'SELECT count(*)
	FROM tserver
	WHERE server_type = 6'
);
// POSTGRESQL AND ORACLE COMPATIBLE
$plugin_available = db_get_sql(
    'SELECT count(*)
	FROM tserver
	WHERE server_type = 4'
);
// POSTGRESQL AND ORACLE COMPATIBLE
$prediction_available = db_get_sql(
    'SELECT count(*)
	FROM tserver
	WHERE server_type = 5'
);
// POSTGRESQL AND ORACLE COMPATIBLE
// Development mode to use all servers
if ($develop_bypass || is_metaconsole()) {
    $network_available = 1;
    $wmi_available = 1;
    $plugin_available = 1;
    // FIXME when prediction predictions server modules can be configured
    // on metaconsole
    $prediction_available = is_metaconsole() ? 0 : 1;
}

$modules = [];
$modules['dataserver'] = __('Create a new data server module');
if ($network_available) {
    $modules['networkserver'] = __('Create a new network server module');
}

if ($plugin_available) {
    $modules['pluginserver'] = __('Create a new plugin server module');
}

if ($wmi_available) {
    $modules['wmiserver'] = __('Create a new WMI server module');
}

if ($prediction_available) {
    $modules['predictionserver'] = __('Create a new prediction server module');
}

if (enterprise_installed()) {
    set_enterprise_module_types($modules);
}

$sec2 = get_parameter('sec2', '');
if (strstr($sec2, 'enterprise/godmode/policies/policies') !== false) {
    // It is unset because the policies haven't a table tmodule_synth and the
    // some part of code to apply this kind of modules in policy agents.
    // But in the future maybe will be good to make this feature, but remember
    // the modules to show in syntetic module policy form must be the policy
    // modules from the same policy.
    unset($modules['predictionserver']);
    if (enterprise_installed()) {
        unset($modules['webux']);
    }
}

$show_creation = false;
$checked = get_parameter('checked');

if (($policy_page) || (isset($agent))) {
    if ($policy_page) {
        $show_creation = !is_central_policies_on_node();
    } else {
        if (!isset($all_groups)) {
            $all_groups = agents_get_all_groups_agent(
                $agent['id_agente'],
                $agent['id_grupo']
            );
        }

        if (check_acl_one_of_groups($config['id_user'], $all_groups, 'AW')) {
            $show_creation = true;
        }
    }

    if ($show_creation) {
        // Create module/type combo
        echo '<form id="create_module_type" method="post" action="'.$url.'">';
        if (!$policy_page) {
            echo '<td class="datos" style="font-weight: bold; width:20%;">';
            echo __('Show in hierachy mode');
            if ($checked == 'true') {
                $checked = true;
            } else {
                $checked = false;
            }

            html_print_checkbox('status_hierachy_mode', '', $checked, false, false, 'onChange=change_mod_filter();');
            echo '</td>';
        }

        echo '<td class="datos" style="font-weight: bold; width:20%;">';
        echo __('<p>Type</p>');
        html_print_select($modules, 'moduletype', '', '', '', '', false, false, false, '', false, 'max-width:300px;');
        html_print_input_hidden('edit_module', 1);
        echo '</td>';
        echo '<td class="datos" style="width:10%;">';
        echo '<input align="right" name="updbutton" type="submit" class="sub next" value="'.__('Create').'">';
        echo '</td>';
        echo '</tr>';
        echo '</form>';
    }
}

echo '</table>';

if (!$config['disable_help']) {
    echo '<div style="text-align: right; width: 100%;padding-top:10px;padding-bottom:10px">';
    echo '<strong>';
    echo "<a style='color: #373737;' target='_blank' href='http://pandorafms.com/Library/Library/'>".__('Get more modules on Monitoring Library').'</a>';
    echo '</strong>';
    echo '</div>';
}

if (! isset($id_agente)) {
    return;
}


$multiple_delete = (bool) get_parameter('multiple_delete');

if ($multiple_delete) {
    $id_agent_modules_delete = (array) get_parameter('id_delete');

    $count_correct_delete_modules = 0;
    foreach ($id_agent_modules_delete as $id_agent_module_del) {
        $id_grupo = (int) agents_get_agent_group($id_agente);
        $all_groups = agents_get_all_groups_agent($id_agente, $id_grupo);

        if (! check_acl_one_of_groups($config['id_user'], $all_groups, 'AW')) {
            db_pandora_audit(
                'ACL Violation',
                'Trying to delete a module without admin rights'
            );
            include 'general/noaccess.php';
            exit;
        }

        if ($id_agent_module_del < 1) {
            db_pandora_audit(
                'HACK Attempt',
                'Expected variable from form is not correct'
            );
            die(__('Nice try buddy'));
            exit;
        }

        enterprise_include_once('include/functions_config_agents.php');
        enterprise_hook('config_agents_delete_module_in_conf', [modules_get_agentmodule_agent($id_agent_module_del), modules_get_agentmodule_name($id_agent_module_del)]);

        $error = 0;

        // First delete from tagente_modulo -> if not successful, increment
        // error. NOTICE that we don't delete all data here, just marking for deletion
        // and delete some simple data.
        $status = '';
        $agent_id_of_module = db_get_value('id_agente', 'tagente_modulo', 'id_agente_modulo', (int) $id_agent_module_del);

        if (db_process_sql(
            "UPDATE tagente_modulo
			SET nombre = 'pendingdelete', disabled = 1, delete_pending = 1
			WHERE id_agente_modulo = ".$id_agent_module_del,
            'affected_rows',
            '',
            true,
            $status,
            false
        ) === false
        ) {
            $error++;
        } else {
            // Set flag to update module status count
            if ($agent_id_of_module !== false) {
                db_process_sql(
                    'UPDATE tagente
					SET update_module_count = 1, update_alert_count = 1
					WHERE id_agente = '.$agent_id_of_module
                );
            }
        }

        switch ($config['dbtype']) {
            case 'mysql':
            case 'postgresql':
                $result = db_process_sql_delete(
                    'tagente_estado',
                    ['id_agente_modulo' => $id_agent_module_del]
                );
                if ($result === false) {
                    $error++;
                }

                $result = db_process_sql_delete(
                    'tagente_datos_inc',
                    ['id_agente_modulo' => $id_agent_module_del]
                );
                if ($result === false) {
                    $error++;
                }
            break;

            case 'oracle':
                $result = db_process_delete_temp(
                    'tagente_estado',
                    'id_agente_modulo',
                    $id_agent_module_del
                );
                if ($result === false) {
                    $error++;
                }

                $result = db_process_delete_temp(
                    'tagente_datos_inc',
                    'id_agente_modulo',
                    $id_agent_module_del
                );
                if ($result === false) {
                    $error++;
                }
            break;
        }

        // Trick to detect if we are deleting a synthetic module (avg or arithmetic)
        // If result is empty then module doesn't have this type of submodules
        $ops_json = enterprise_hook('modules_get_synthetic_operations', [$id_agent_module_del]);
        $result_ops_synthetic = json_decode($ops_json);
        if (!empty($result_ops_synthetic)) {
            $result = enterprise_hook('modules_delete_synthetic_operations', [$id_agent_module_del]);
            if ($result === false) {
                $error++;
            }
        } //end if
        else {
            $result_components = enterprise_hook('modules_get_synthetic_components', [$id_agent_module_del]);
            $count_components = 1;
            if (!empty($result_components)) {
                // Get number of components pending to delete to know when it's needed to update orders
                $num_components = count($result_components);
                $last_target_module = 0;
                foreach ($result_components as $id_target_module) {
                    // Detects change of component or last component to update orders
                    if (($count_components == $num_components) or ($last_target_module != $id_target_module)) {
                        $update_orders = true;
                    } else {
                        $update_orders = false;
                    }

                    $result = enterprise_hook('modules_delete_synthetic_operations', [$id_target_module, $id_agent_module_del, $update_orders]);
                    if ($result === false) {
                        $error++;
                    }

                    $count_components++;
                    $last_target_module = $id_target_module;
                }
            }
        }


        // Check for errors
        if ($error != 0) {
        } else {
            $count_correct_delete_modules++;
        }
    }

    $count_modules_to_delete = count($id_agent_modules_delete);
    if ($count_correct_delete_modules == 0) {
        ui_print_error_message(
            sprintf(
                __('There was a problem deleting %s modules, none deleted.'),
                $count_modules_to_delete
            )
        );
    } else {
        if ($count_correct_delete_modules == $count_modules_to_delete) {
            ui_print_success_message(__('All Modules deleted succesfully'));
        } else {
            ui_print_error_message(
                sprintf(
                    __('There was a problem only deleted %s modules of %s total.'),
                    count_correct_delete_modules,
                    $count_modules_to_delete
                )
            );
        }
    }
}


// ==================
// TABLE LIST MODULES
// ==================
$url = 'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=module&id_agente='.$id_agente;
$selectNameUp = false;
$selectNameDown = false;
$selectServerUp = false;
$selectServerDown = false;
$selectTypeUp = false;
$selectTypeDown = false;
$selectIntervalUp = false;
$selectIntervalDown = false;
$sortField = get_parameter('sort_field');
$sort = get_parameter('sort', 'none');
$selected = true;

$order[] = [
    'field' => 'tmodule_group.name',
    'order' => 'ASC',
];

switch ($sortField) {
    case 'name':
        switch ($sort) {
            case 'up':
                $selectNameUp = $selected;
                switch ($config['dbtype']) {
                    case 'mysql':
                    case 'postgresql':
                        $order[] = [
                            'field' => 'tagente_modulo.nombre',
                            'order' => 'ASC',
                        ];
                    break;

                    case 'oracle':
                        $order[] = [
                            'field' => 'dbms_lob.substr(tagente_modulo.nombre,4000,1)',
                            'order' => 'ASC',
                        ];
                    break;
                }
            break;

            case 'down':
                $selectNameDown = $selected;
                switch ($config['dbtype']) {
                    case 'mysql':
                    case 'postgresql':
                        $order[] = [
                            'field' => 'tagente_modulo.nombre',
                            'order' => 'DESC',
                        ];
                    break;

                    case 'oracle':
                        $order[] = [
                            'field' => 'dbms_lob.substr(tagente_modulo.nombre,4000,1)',
                            'order' => 'DESC',
                        ];
                    break;
                }
            break;
        }
    break;

    case 'server':
        switch ($sort) {
            case 'up':
                $selectServerUp = $selected;
                $order[] = [
                    'field' => 'id_modulo',
                    'order' => 'ASC',
                ];
            break;

            case 'down':
                $selectServerDown = $selected;
                $order[] = [
                    'field' => 'id_modulo',
                    'order' => 'DESC',
                ];
            break;
        }
    break;

    case 'type':
        switch ($sort) {
            case 'up':
                $selectTypeUp = $selected;
                $order[] = [
                    'field' => 'id_tipo_modulo',
                    'order' => 'ASC',
                ];
            break;

            case 'down':
                $selectTypeDown = $selected;
                $order[] = [
                    'field' => 'id_tipo_modulo',
                    'order' => 'DESC',
                ];
            break;
        }
    break;

    case 'interval':
        switch ($sort) {
            case 'up':
                $selectIntervalUp = $selected;
                $order[] = [
                    'field' => 'module_interval',
                    'order' => 'ASC',
                ];
            break;

            case 'down':
                $selectIntervalDown = $selected;
                $order[] = [
                    'field' => 'module_interval',
                    'order' => 'DESC',
                ];
            break;
        }
    break;

    default:
        $selectNameUp = $selected;
        $selectNameDown = false;
        $selectServerUp = false;
        $selectServerDown = false;
        $selectTypeUp = false;
        $selectTypeDown = false;
        $selectIntervalUp = false;
        $selectIntervalDown = false;
        switch ($config['dbtype']) {
            case 'mysql':
            case 'postgresql':
                $order[] = [
                    'field' => 'nombre',
                    'order' => 'ASC',
                ];
            break;

            case 'oracle':
                $order[] = [
                    'field' => 'dbms_lob.substr(nombre,4000,1)',
                    'order' => 'ASC',
                ];
            break;
        }
    break;
}


// Build the order sql
if (!empty($order)) {
    $order_sql = ' ORDER BY ';
}

$first = true;
foreach ($order as $ord) {
    if ($first) {
        $first = false;
    } else {
        $order_sql .= ',';
    }

    $order_sql .= $ord['field'].' '.$ord['order'];
}

// Get limit and offset parameters
$limit = (int) $config['block_size'];
$offset = (int) get_parameter('offset');

$params = ($checked) ? 'tagente_modulo.*, tmodule_group.*' : implode(
    ',',
    [
        'tagente_modulo.id_agente_modulo',
        'id_tipo_modulo',
        'descripcion',
        'nombre',
        'max',
        'min',
        'module_interval',
        'id_modulo',
        'id_module_group',
        'disabled',
        'max_warning',
        'min_warning',
        'str_warning',
        'max_critical',
        'min_critical',
        'str_critical',
        'quiet',
        'critical_inverse',
        'warning_inverse',
        'id_policy_module',
    ]
);

$where = sprintf('delete_pending = 0 AND id_agente = %s', $id_agente);

$search_string_entities = io_safe_input($search_string);

$basic_where = sprintf("(nombre LIKE '%%%s%%' OR nombre LIKE '%%%s%%' OR descripcion LIKE '%%%s%%' OR descripcion LIKE '%%%s%%') AND", $search_string, $search_string_entities, $search_string, $search_string_entities);

// Tags acl
$agent_tags = tags_get_user_applied_agent_tags($id_agente);
if ($agent_tags !== true) {
    $where_tags = ' AND ttag_module.id_tag IN ('.implode(',', $agent_tags).')';
}

$paginate_module = false;
if (isset($config['paginate_module'])) {
    $paginate_module = $config['paginate_module'];
}

if ($paginate_module) {
    if (!isset($limit_sql)) {
        $limit_sql = " LIMIT $offset, $limit ";
    }
} else {
    $limit_sql = '';
}

$sql = sprintf(
    'SELECT tagente_modulo.*, tmodule_group.*
		FROM tagente_modulo
		LEFT JOIN tmodule_group
			ON tagente_modulo.id_module_group = tmodule_group.id_mg
		LEFT JOIN ttag_module
			ON ttag_module.id_agente_modulo = tagente_modulo.id_agente_modulo
		WHERE %s %s %s
		GROUP BY tagente_modulo.id_agente_modulo
		%s %s',
    $basic_where,
    $where,
    $where_tags,
    $order_sql,
    $limit_sql
);

$modules = db_get_all_rows_sql($sql);

$sql_total_modules = sprintf(
    'SELECT count(DISTINCT(tagente_modulo.id_agente_modulo))
	FROM tagente_modulo
	LEFT JOIN ttag_module
		ON ttag_module.id_agente_modulo = tagente_modulo.id_agente_modulo
	WHERE %s %s %s',
    $basic_where,
    $where,
    $where_tags
);

$total_modules = db_get_value_sql($sql_total_modules);

$total_modules = isset($total_modules) ? $total_modules : 0;

if ($modules === false) {
    ui_print_empty_data(__('No available data to show'));
    return;
}

// Prepare pagination
$url = '?'.'sec=gagente&'.'tab=module&'.'sec2=godmode/agentes/configurar_agente&'.'id_agente='.$id_agente.'&'.'sort_field='.$sortField.'&'.'&sort='.$sort.'&'.'search_string='.urlencode($search_string);

if ($paginate_module) {
    ui_pagination($total_modules, $url);
}

$url_name = $url.'&sort_field=name&sort=';
$url_server = $url.'&sort_field=server&sort=';
$url_type = $url.'&sort_field=type&sort=';
$url_interval = $url.'&sort_field=interval&sort=';

$table = new stdClass();
$table->width = '100%';
$table->class = 'info_table';
$table->head = [];
$table->head['checkbox'] = html_print_checkbox('all_delete', 0, false, true, false);
$table->head[0] = __('Name').ui_get_sorting_arrows($url_name.'up', $url_name.'down', $selectNameUp, $selectNameDown);

// The access to the policy is granted only with AW permission
if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK && check_acl($config['id_user'], $agent['id_grupo'], 'AW')) {
    $table->head[1] = "<span title='".__('Policy')."'>".__('P.').'</span>';
}

$table->head[2] = "<span title='".__('Server')."'>".__('S.').'</span>'.ui_get_sorting_arrows($url_server.'up', $url_server.'down', $selectServerUp, $selectServerDown);
$table->head[3] = __('Type').ui_get_sorting_arrows($url_type.'up', $url_type.'down', $selectTypeUp, $selectTypeDown);
$table->head[4] = __('Interval').ui_get_sorting_arrows($url_interval.'up', $url_interval.'down', $selectIntervalUp, $selectIntervalDown);
$table->head[5] = __('Description');
$table->head[6] = __('Status');
$table->head[7] = __('Warn');


$table->head[8] = __('Action');
$table->head[9] = '<span title="'.__('Delete').'">'.__('Del.').'</span>';

$table->rowstyle = [];
$table->style = [];
$table->style[0] = 'font-weight: bold';
$table->size = [];
$table->size['checkbox'] = '20px';
$table->size[2] = '70px';
$table->align = [];
$table->align[2] = 'left';
$table->align[8] = 'left';
$table->align[9] = 'left';
$table->data = [];

$agent_interval = agents_get_interval($id_agente);
$last_modulegroup = '0';

// Extract the ids only numeric modules for after show the normalize link.
$tempRows = db_get_all_rows_sql(
    "SELECT *
	FROM ttipo_modulo
	WHERE nombre NOT LIKE '%string%' AND nombre NOT LIKE '%proc%'"
);
$numericModules = [];
foreach ($tempRows as $row) {
    $numericModules[$row['id_tipo']] = true;
}

if ($checked) {
    $modules_hierachy = [];
    $modules_hierachy = get_hierachy_modules_tree($modules);

    $modules_dt = get_dt_from_modules_tree($modules_hierachy);

    $modules = $modules_dt;
}

foreach ($modules as $module) {
    if (! check_acl_one_of_groups($config['id_user'], $all_groups, 'AW') && ! check_acl_one_of_groups($config['id_user'], $all_groups, 'AD')) {
        continue;
    }

    $type = $module['id_tipo_modulo'];
    $id_module = $module['id_modulo'];
    $nombre_modulo = $module['nombre'];
    $descripcion = $module['descripcion'];
    $module_max = $module['max'];
    $module_min = $module['min'];
    $module_interval2 = $module['module_interval'];
    $module_group2 = $module['id_module_group'];

    $data = [];

    if (!$checked) {
        if ($module['id_module_group'] != $last_modulegroup) {
            $last_modulegroup = $module['id_module_group'];
            $data[0] = '<strong>'.modules_get_modulegroup_name($last_modulegroup).'</strong>';
            $i = array_push($table->data, $data);
            $table->rowstyle[($i - 1)] = 'text-align: center';
            $table->rowclass[($i - 1)] = 'datos3';
            if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) {
                    $table->colspan[($i - 1)][0] = 11;
            } else {
                $table->colspan[($i - 1)][0] = 10;
            }

            $data = [];
        }
    }

    if (check_acl_one_of_groups($config['id_user'], $all_groups, 'AW')) {
        $data['checkbox'] = html_print_checkbox('id_delete[]', $module['id_agente_modulo'], false, true);
    }

    $data[0] = '';

    if (isset($module['deep']) && ($module['deep'] != 0)) {
        $data[0] .= str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $module['deep']);
        $data[0] .= html_print_image('images/icono_escuadra.png', true, ['style' => 'padding-bottom: inherit;']).'&nbsp;&nbsp;';
    }

    if ($module['quiet']) {
        $data[0] .= html_print_image(
            'images/dot_blue.png',
            true,
            [
                'border' => '0',
                'title'  => __('Quiet'),
                'alt'    => '',
            ]
        ).'&nbsp;';
    }

    if (check_acl_one_of_groups($config['id_user'], $all_groups, 'AW')) {
        $data[0] .= '<a href="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente='.$id_agente.'&tab=module&edit_module=1&id_agent_module='.$module['id_agente_modulo'].'">';
    }

    if ($module['disabled']) {
        $data[0] .= '<em class="disabled_module">'.ui_print_truncate_text($module['nombre'], 'module_medium', false, true, true, '[&hellip;]', 'font-size: 7.2pt').'</em>';
    } else {
        $data[0] .= ui_print_truncate_text($module['nombre'], 'module_medium', false, true, true, '[&hellip;]', 'font-size: 7.2pt');
    }

    if (check_acl_one_of_groups($config['id_user'], $all_groups, 'AW')) {
        $data[0] .= '</a>';
    }

    // The access to the policy is granted only with AW permission
    if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK && check_acl($config['id_user'], $agent['id_grupo'], 'AW')) {
        $policyInfo = policies_info_module_policy($module['id_agente_modulo']);
        if ($policyInfo === false) {
            $data[1] = '';
        } else {
            $linked = policies_is_module_linked($module['id_agente_modulo']);

            $adopt = false;
            if (policies_is_module_adopt($module['id_agente_modulo'])) {
                $adopt = true;
            }

            if ($linked) {
                if ($adopt) {
                    $img = 'images/policies_brick.png';
                    $title = '('.__('Adopted').') '.$policyInfo['name_policy'];
                } else {
                    $img = 'images/policies.png';
                    $title = $policyInfo['name_policy'];
                }
            } else {
                if ($adopt) {
                    $img = 'images/policies_not_brick.png';
                    $title = '('.__('Unlinked').') ('.__('Adopted').') '.$policyInfo['name_policy'];
                } else {
                    $img = 'images/unlinkpolicy.png';
                    $title = '('.__('Unlinked').') '.$policyInfo['name_policy'];
                }
            }

            $data[1] = '<a href="?sec=gmodules&sec2=enterprise/godmode/policies/policies&id='.$policyInfo['id_policy'].'">'.html_print_image($img, true, ['title' => $title]).'</a>';
        }
    }

    // Module type (by server type )
    $data[2] = '';
    if ($module['id_modulo'] > 0) {
        $data[2] = servers_show_type($module['id_modulo']);
    }

    $module_status = db_get_row('tagente_estado', 'id_agente_modulo', $module['id_agente_modulo']);

    modules_get_status($module['id_agente_modulo'], $module_status['estado'], $module_status['datos'], $status, $title);

    // This module is initialized ? (has real data)
    if ($status == STATUS_MODULE_NO_DATA) {
        $data[2] .= html_print_image(
            'images/error.png',
            true,
            ['title' => __('Non initialized module')]
        );
    }

    // Module type (by data type)
    $data[3] = '';
    if ($type) {
        $data[3] = ui_print_moduletype_icon($type, true);
    }

    // Module interval
    if ($module['module_interval']) {
        $data[4] = human_time_description_raw($module['module_interval']);
    } else {
        $data[4] = human_time_description_raw($agent_interval);
    }

    if ($module['id_modulo'] == MODULE_DATA && $module['id_policy_module'] != 0) {
        $data[4] .= ui_print_help_tip(__('The policy modules of data type will only update their intervals when policy is applied.'), true);
    }

    $data[5] = ui_print_truncate_text($module['descripcion'], 'description', false);

    $data[6] = ui_print_status_image($status, $title, true);

    // MAX / MIN values
    if ($module['id_tipo_modulo'] != 25) {
        $data[7] = ui_print_module_warn_value(
            $module['max_warning'],
            $module['min_warning'],
            $module['str_warning'],
            $module['max_critical'],
            $module['min_critical'],
            $module['str_critical'],
            $module['warning_inverse'],
            $module['critical_inverse']
        );
    } else {
        $data[7] = '';
    }

    if ($module['disabled']) {
        $data[8] = "<a href='index.php?sec=gagente&tab=module&sec2=godmode/agentes/configurar_agente&id_agente=".$id_agente.'&enable_module='.$module['id_agente_modulo']."'>".html_print_image(
            'images/lightbulb_off.png',
            true,
            [
                'alt'   => __('Enable module'),
                'title' => __('Enable module'),
            ]
        ).'</a>';
    } else {
        $data[8] = "<a href='index.php?sec=gagente&tab=module&sec2=godmode/agentes/configurar_agente&id_agente=".$id_agente.'&disable_module='.$module['id_agente_modulo']."'>".html_print_image(
            'images/lightbulb.png',
            true,
            [
                'alt'   => __('Disable module'),
                'title' => __('Disable module'),
            ]
        ).'</a>';
    }

    if (check_acl_one_of_groups($config['id_user'], $all_groups, 'AW') && $module['id_tipo_modulo'] != 25) {
        $data[8] .= '<a href="index.php?sec=gagente&tab=module&sec2=godmode/agentes/configurar_agente&id_agente='.$id_agente.'&duplicate_module='.$module['id_agente_modulo'].'"
			onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">';
        $data[8] .= html_print_image(
            'images/copy.png',
            true,
            ['title' => __('Duplicate')]
        );
        $data[8] .= '</a> ';

        // Make a data normalization
        if (isset($numericModules[$type])) {
            if ($numericModules[$type] === true) {
                $data[8] .= '<a href="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente='.$id_agente.'&tab=module&fix_module='.$module['id_agente_modulo'].'" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">';
                $data[8] .= html_print_image(
                    'images/chart_curve.png',
                    true,
                    ['title' => __('Normalize')]
                );
                $data[8] .= '</a>';
            }
        } else {
            $data[8] .= html_print_image(
                'images/chart_curve.disabled.png',
                true,
                ['title' => __('Normalize (Disabled)')]
            );
            $data[8] .= '&nbsp;&nbsp;';
        }

        // create network component action
        if ((is_user_admin($config['id_user']))
            && ($module['id_modulo'] == MODULE_NETWORK)
        ) {
            $data[8] .= '<a href="index.php?sec=gmodules&sec2=godmode/modules/manage_network_components&create_network_from_module=1&id_agente='.$id_agente.'&create_module_from='.$module['id_agente_modulo'].'"
				onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">';
            $data[8] .= html_print_image(
                'images/network.png',
                true,
                ['title' => __('Create network component')]
            );
            $data[8] .= '</a> ';
        } else {
            $data[8] .= html_print_image(
                'images/network.disabled.png',
                true,
                ['title' => __('Create network component (Disabled)')]
            );
            $data[8] .= '&nbsp;&nbsp;';
        }
    }

    if (check_acl_one_of_groups($config['id_user'], $all_groups, 'AW')) {
        // Delete module
        $data[9] = '<a href="index.php?sec=gagente&tab=module&sec2=godmode/agentes/configurar_agente&id_agente='.$id_agente.'&delete_module='.$module['id_agente_modulo'].'"
			onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">';
        $data[9] .= html_print_image(
            'images/cross.png',
            true,
            ['title' => __('Delete')]
        );
        $data[9] .= '</a> ';
    }

    $table->cellclass[] = [
        8 => 'action_buttons',
        9 => 'action_buttons',
    ];
    array_push($table->data, $data);
    $table->cellclass[] = [
        8 => 'action_buttons',
        9 => 'action_buttons',
    ];
}

if (check_acl_one_of_groups($config['id_user'], $all_groups, 'AW')) {
    echo '<form method="post" action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente='.$id_agente.'&tab=module"
		onsubmit="if (! confirm (\''.__('Are you sure?').'\')) return false">';
}

html_print_table($table);

if (check_acl_one_of_groups($config['id_user'], $all_groups, 'AW')) {
    echo '<div class="action-buttons" style="width: '.$table->width.'">';
    html_print_input_hidden('multiple_delete', 1);
    html_print_submit_button(__('Delete'), 'multiple_delete', false, 'class="sub delete"');
    echo '</div>';
    echo '</form>';
}
?>

<script type="text/javascript">

    $(document).ready (function () {
        
        $('[id^=checkbox-id_delete]').change(function(){
            if($(this).parent().parent().hasClass('checkselected')){
                $(this).parent().parent().removeClass('checkselected');
            }
            else{
                $(this).parent().parent().addClass('checkselected');                            
            }
        });        


        $('[id^=checkbox-all_delete]').change(function(){    
            if ($("#checkbox-all_delete").prop("checked")) {
                $('[id^=checkbox-id_delete]').parent().parent().addClass('checkselected');
                $("[name^=id_delete").prop("checked", true);
            }
            else{
                $('[id^=checkbox-id_delete]').parent().parent().removeClass('checkselected');
                $("[name^=id_delete").prop("checked", false);
            }    
        });


    });


    function change_mod_filter() {
        var checked = $("#checkbox-status_hierachy_mode").is(":checked");

        if (/checked/.test(window.location)) {
            var url = window.location.toString();
            if (checked) {
                window.location = url.replace("checked=false", "checked=true");
            }
            else {
                window.location = url.replace("checked=true", "checked=false");
            }
        }
        else {
            window.location = window.location + "&checked=true";
        }
    }
</script>
