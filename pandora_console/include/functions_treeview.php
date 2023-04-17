<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
function treeview_printModuleTable($id_module, $server_data=false, $no_head=false)
{
    global $config;

    if (empty($server_data)) {
        $server_name = '';
        $server_id = '';
        $url_hash = '';
        $console_url = ui_get_full_url('/');
    } else {
        $server_name = $server_data['server_name'];
        $server_id = $server_data['id'];
        $console_url = $server_data['server_url'].'/';
        $url_hash = metaconsole_get_servers_url_hash($server_data);
    }

    include_once $config['homedir'].'/include/functions_agents.php';
    include_once $config['homedir'].'/include/functions_graph.php';
    include_graphs_dependencies($config['homedir'].'/');
    include_once $config['homedir'].'/include/functions_groups.php';
    include_once $config['homedir'].'/include/functions_servers.php';
    enterprise_include_once('meta/include/functions_modules_meta.php');
    enterprise_include_once('meta/include/functions_ui_meta.php');
    enterprise_include_once('meta/include/functions_metaconsole.php');

    $user_access_node = can_user_access_node();

    if (is_metaconsole()) {
        if (metaconsole_connect($server_data) != NOERR) {
            return;
        }
    }

    $filter['id_agente_modulo'] = $id_module;

    $module = db_get_row_filter('tagente_modulo', $filter);

    if ($module === false) {
        ui_print_error_message(__('There was a problem loading module'));
        return;
    }

    $table = new StdClass();
    $table->width = '100%';
    $table->class = 'floating_form';
    $table->id = 'tree_view_module_data';
    $table->style = [];
    $table->style['title'] = 'height: 32px; width: 30%; padding-right: 5px; text-align: end;';
    $table->style['data'] = 'height: 32px; width: 70%; padding-left: 5px;font-weight: lighter;';
    $table->data = [];

    // Module name.
    $cellName = ((bool) $module['disabled'] === true) ? '<em>'.$module['nombre'].'</em>'.ui_print_help_tip(__('Disabled'), true) : $module['nombre'];

    $row = [];
    $row['title'] = __('Name');
    $row['data'] = html_print_anchor(
        [
            'href'    => $console_url.'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente='.$module['id_agente'].'&tab=module&edit_module=1&id_agent_module='.$module['id_agente_modulo'].$url_hash,
            'title'   => __('Click here for view this module'),
            'class'   => 'font_11',
            'content' => $cellName,
        ],
        true
    );
    $table->data['name'] = $row;

    // Interval.
    $row = [];
    $row['title'] = __('Interval');
    $row['data'] = human_time_description_raw(modules_get_interval($module['id_agente_modulo']), true);
    $table->data['interval'] = $row;

    // Warning Min/Max.
    if (modules_is_string_type($module['id_tipo_modulo'])) {
        $warning_status_str = __('Str.').': '.$module['str_warning'];
    } else {
        $warning_status_str = __('Min.').': '.(float) $module['min_warning'].'<br>'.__('Max.').': '.(float) $module['max_warning'];
    }

    $row = [];
    $row['title'] = __('Warning status');
    $row['data'] = $warning_status_str;
    $table->data['warning_status'] = $row;

    // Critical Min/Max.
    if (modules_is_string_type($module['id_tipo_modulo'])) {
        $critical_status_str = __('Str.').': '.$module['str_warning'];
    } else {
        $critical_status_str = __('Min.').': '.(float) $module['min_critical'].'<br>'.__('Max.').': '.(float) $module['max_critical'];
    }

    $row = [];
    $row['title'] = __('Critical status');
    $row['data'] = $critical_status_str;
    $table->data['critical_status'] = $row;

    // Module group.
    $module_group = modules_get_modulegroup_name($module['id_module_group']);

    if ($module_group === false) {
        $module_group = __('Not assigned');
    } else {
        $module_group = __("$module_group");
    }

    $row = [];
    $row['title'] = __('Module group');
    $row['data'] = $module_group;
    $table->data['module_group'] = $row;

    $row = [];
    $row['title'] = __('Description');
    $row['data'] = $module['descripcion'];
    $table->data['description'] = $row;

    // Tags.
    $tags = tags_get_module_tags($module['id_agente_modulo']);

    if (empty($tags) === false) {
        $user_tags = tags_get_user_tags($config['id_user']);

        foreach ($tags as $k => $v) {
            if (!array_key_exists($v, $user_tags)) {
                // Only show user's tags.
                unset($tags[$k]);
            } else {
                $tag_name = tags_get_name($v);
                if (empty($tag_name) === true) {
                    unset($tags[$k]);
                } else {
                    $tags[$k] = $tag_name;
                }
            }
        }
    }

    $row = [];
    $row['title'] = __('Tags');
    $row['data'] = (empty($tags) === true) ? '<i>'.__('N/A').'</i>' : implode(', ', $tags);
    $table->data['tags'] = $row;

    // Data.
    $last_data = db_get_row_filter('tagente_estado', ['id_agente_modulo' => $module['id_agente_modulo'], 'order' => ['field' => 'id_agente_estado', 'order' => 'DESC']]);
    if ($config['render_proc']) {
        switch ($module['id_tipo_modulo']) {
            case 2:
            case 6:
            case 9:
            case 18:
            case 21:
            case 31:
                if (is_numeric($last_data['datos']) && $last_data['datos'] >= 1) {
                    $data = "<span class='span_treeview'>".$config['render_proc_ok'].'</span>';
                } else {
                    $data = "<span class='span_treeview'>".$config['render_proc_fail'].'</span>';
                }
            break;

            default:
                switch ($module['id_tipo_modulo']) {
                    case 15:
                        $value = db_get_value('snmp_oid', 'tagente_modulo', 'id_agente_modulo', $module['id_agente_modulo']);
                        if ($value == '.1.3.6.1.2.1.1.3.0' || $value == '.1.3.6.1.2.1.25.1.1.0') {
                            $data = "<span title='".$last_data['datos']."' class='nowrap'>".human_milliseconds_to_string($last_data['datos']).'</span>';
                        } else if (is_numeric($last_data['datos'])) {
                            $data = "<span class='span_treeview'>".remove_right_zeros(number_format($last_data['datos'], $config['graph_precision'], $config['decimal_separator'], $config['thousand_separator'])).'</span>';
                        } else {
                            $data = ui_print_truncate_text(
                                io_safe_output($last_data['datos']),
                                GENERIC_SIZE_TEXT,
                                true,
                                true,
                                true,
                                '...',
                                'white-space: nowrap;'
                            );
                        }
                    break;

                    default:
                        if (is_numeric($last_data['datos'])) {
                            $data = "<span class='span_treeview'>".remove_right_zeros(number_format($last_data['datos'], $config['graph_precision'], $config['decimal_separator'], $config['thousand_separator'])).'</span>';
                        } else {
                            $data = ui_print_truncate_text(
                                io_safe_output($last_data['datos']),
                                GENERIC_SIZE_TEXT,
                                true,
                                true,
                                true,
                                '...',
                                'white-space: nowrap;'
                            );
                        }
                    break;
                }
            break;
        }
    } else {
        switch ($module['id_tipo_modulo']) {
            case 15:
                $value = db_get_value('snmp_oid', 'tagente_modulo', 'id_agente_modulo', $module['id_agente_modulo']);
                if ($value === '.1.3.6.1.2.1.1.3.0' || $value === '.1.3.6.1.2.1.25.1.1.0') {
                    $data = "<span title='".human_milliseconds_to_string($last_data['datos'])."' class='nowrap'>".human_milliseconds_to_string($last_data['datos']).'</span>';
                } else if (is_numeric($last_data['datos']) === true) {
                    $data = "<span class='span_treeview'>".remove_right_zeros(number_format($last_data['datos'], $config['graph_precision'], $config['decimal_separator'], $config['thousand_separator'])).'</span>';
                } else {
                    $data = ui_print_truncate_text(
                        io_safe_output($last_data['datos']),
                        GENERIC_SIZE_TEXT,
                        true,
                        true,
                        true,
                        '...',
                        'white-space: nowrap;'
                    );
                }
            break;

            default:
                if (is_numeric($last_data['datos'])) {
                    $data = "<span class='span_treeview'>".remove_right_zeros(number_format($last_data['datos'], $config['graph_precision'], $config['decimal_separator'], $config['thousand_separator'])).'</span>';
                } else {
                    $data = ui_print_truncate_text(
                        io_safe_output($last_data['datos']),
                        GENERIC_SIZE_TEXT,
                        true,
                        true,
                        true,
                        '...',
                        'white-space: nowrap;'
                    );
                }
            break;
        }
    }

    if (empty($last_data['utimestamp']) === false) {
        $last_data_str = $data;

        if (empty($module['unit']) === false) {
            $data_macro = modules_get_unit_macro($last_data['datos'], $module['unit']);
            if ($data_macro !== false) {
                if (is_numeric($data_macro) === true) {
                    $last_data_str = "<span class='span_treeview'>".remove_right_zeros(number_format($data_macro, $config['graph_precision'], $config['decimal_separator'], $config['thousand_separator'])).'</span>';
                } else {
                    $last_data_str = ui_print_truncate_text(
                        io_safe_output($data_macro),
                        GENERIC_SIZE_TEXT,
                        true,
                        true,
                        true,
                        '...',
                        'white-space: nowrap;'
                    );
                }
            } else {
                $last_data_str .= '&nbsp;';
                $last_data_str .= '<span class="span_treeview">('.$module['unit'].')</span>';
            }
        }

        $last_data_str .= '&nbsp;';
        $last_data_str .= html_print_image('images/clock.svg', true, ['title' => $last_data['timestamp'], 'style' => 'margin-top: 3px; width: 20px', 'class' => 'invert_filter']);

        $is_snapshot = is_snapshot_data($last_data['datos']);
        $is_large_image = is_text_to_black_string($last_data['datos']);
        if (($config['command_snapshot']) && ($is_snapshot || $is_large_image)) {
            $link = ui_get_snapshot_link(
                [
                    'id_module'   => $module['id_agente_modulo'],
                    'interval'    => $module['current_interval'],
                    'module_name' => $module['module_name'],
                    'id_node'     => empty($server_id) ? 0 : $server_id,
                ]
            );
            $salida = ui_get_snapshot_image($link, $is_snapshot).'&nbsp;&nbsp;';
        }

        if ($salida !== null) {
            $last_data_str = html_print_image('images/clock2.png', true, ['title' => $last_data['timestamp'], 'width' => '18px', 'class' => 'invert_filter']);
        }

        $last_data_str .= $salida;
    } else {
        $last_data_str = '<i>'.__('No data').'</i>';
    }

    $row = [];
    $row['title'] = __('Last data');
    $row['data'] = $last_data_str;
    $table->data['last_data'] = $row;

    // Last status change.
    $last_status_change = db_get_value('last_status_change', 'tagente_estado', 'id_agente_modulo', $module['id_agente_modulo']);
    $row = [];
    $row['title'] = __('Last status change');
    $time_elapsed = ($last_status_change > 0) ? human_time_comparation($last_status_change) : __('N/A');
    $row['data'] = $time_elapsed;
    $table->data['tags'] = $row;

    // Title.
    echo '<span id="fixedBottomHeadTitle" style="display:none">'.__('Module information').'</span>';
    // End of table.
    html_print_table($table);

    $id_group = agents_get_agent_group($module['id_agente']);
    $group_name = db_get_value('nombre', 'tgrupo', 'id_grupo', $id_group);
    $agent_name = db_get_value('nombre', 'tagente', 'id_agente', $module['id_agente']);

    if ($user_access_node && check_acl($config['id_user'], $id_group, 'AW')) {
        // Actions table
        echo '<div class="actions_treeview flex flex-evenly">';
        echo '<a target=_blank href="'.$console_url.'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente='.$module['id_agente'].'&tab=module&edit_module=1&id_agent_module='.$module['id_agente_modulo'].$url_hash.'">';
        html_print_submit_button(
            __('Go to module edition'),
            'upd_button',
            false,
            ['class' => 'secondary mini']
        );
        echo '</a>';

        echo '</div>';
    }

    // id_module and id_agent hidden
    echo '<div id="ids" class="invisible">';
        html_print_input_text('id_module', $id_module);
        html_print_input_text('id_agent', $module['id_agente']);
        html_print_input_text('server_name', $server_name);
    echo '</div>';

    if (!empty($server_data) && is_metaconsole()) {
        metaconsole_restore_db();
    }

    return;
}


function treeview_printAlertsTable($id_module, $server_data=[], $no_head=false)
{
    global $config;

    if (empty($server_data)) {
        $server_name = '';
        $server_id = '';
        $url_hash = '';
        $console_url = '';
    } else {
        $server_name = $server_data['server_name'];
        $server_id = $server_data['id'];
        $console_url = $server_data['server_url'].'/';
        $url_hash = metaconsole_get_servers_url_hash($server_data);
    }

    $user_access_node = can_user_access_node();

    if (is_metaconsole()) {
        if (metaconsole_connect($server_data) != NOERR) {
            return;
        }
    }

    $module_alerts = alerts_get_alerts_agent_module($id_module);
    $module_name = db_get_value('nombre', 'tagente_modulo', 'id_agente_modulo', $id_module);
    $agent_id = db_get_value('id_agente', 'tagente_modulo', 'id_agente_modulo', $id_module);
    $id_group = agents_get_agent_group($agent_id);

    if ($module_alerts === false) {
        ui_print_error_message(__('There was a problem loading alerts'));
        return;
    }

    $table = new StdClass();
    $table->width = '100%';
    $table->class = 'databox data';
    $table->rowstyle = [];
    $table->rowstyle['titles'] = 'font-weight: bold;';

    if (!$no_head) {
        $table->head = [];
        $table->head[] = __('Alerts').': '.$module_name;
    }

    $table->head_colspan[] = 2;
    $table->data = [];

    $row = [];
    $row['template'] = __('Template');
    $row['actions'] = __('Actions');
    $table->data['titles'] = $row;

    foreach ($module_alerts as $module_alert) {
        // Template name
        $template_name = db_get_value('name', 'talert_templates', 'id', $module_alert['id_alert_template']);

        $actions = alerts_get_alert_agent_module_actions($module_alert['id']);

        if (empty($actions)) {
            $actions_list = '<i>'.__('N/A').'</i>';
        } else {
            $actions_list = '<ul>';
            foreach ($actions as $act) {
                $actions_list .= '<li>';
                $actions_list .= $act['name'];
                $actions_list .= '</li>';
            }

            $actions_list .= '</ul>';
        }

        $row = [];
        $row['template'] = $template_name;
        $row['actions'] = $actions_list;
        $table->data['last_data'] = $row;
    }

    html_print_table($table);

    $table2 = new StdClass();
    $table2->width = '100%';
    $table2->class = 'databox data';
    $table2->rowstyle = [];
    $table2->rowstyle['titles'] = 'font-weight: bold;';

    $table2->head_colspan[] = 3;
    $table2->data = [];

    $row = [];
    $row['template'] = __('Template');
    $row['times_fired'] = __('Times fired');
    $row['last_fired'] = __('Last fired');
    $table2->data['titles'] = $row;

    foreach ($module_alerts as $module_alert) {
        $template_name = db_get_value('name', 'talert_templates', 'id', $module_alert['id_alert_template']);

        $times_fired = $module_alert['times_fired'];

        $last_fired = date($config['date_format'], $module_alert['last_fired']);

        $row = [];
        $row['template'] = $template_name;
        $row['times_fired'] = $times_fired;
        $row['last_fired'] = $last_fired;
        $table2->data['last_data'] = $row;
    }

    html_print_table($table2);

    if ($user_access_node && check_acl($config['id_user'], $id_group, 'LW')) {
        // Actions button.
        html_print_div(
            [
                'class'   => 'action-buttons',
                'content' => html_print_button(
                    __('Go to alerts edition'),
                    'upd_button',
                    false,
                    'window.location.assign("'.$console_url.'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=alert&search=1&module_name='.$module_name.'&id_agente='.$agent_id.$url_hash.'")',
                    ['icon' => 'alert'],
                    true
                ),
            ]
        );
    }

    if (empty($server_data) === false && is_metaconsole() === true) {
        metaconsole_restore_db();
    }

    return;
}


function treeview_printTable($id_agente, $server_data=[], $no_head=false)
{
    global $config;

    if (empty($server_data)) {
        $server_name = '';
        $server_id = '';
        $url_hash = '';
        $console_url = ui_get_full_url('/');
    } else {
        $server_name = $server_data['server_name'];
        $server_id = $server_data['id'];
        $console_url = $server_data['server_url'].'/';
        $url_hash = metaconsole_get_servers_url_hash($server_data);
    }

    include_once $config['homedir'].'/include/functions_agents.php';
    include_once $config['homedir'].'/include/functions_graph.php';
    include_once $config['homedir'].'/include/functions_groups.php';
    include_once $config['homedir'].'/include/functions_gis.php';
    enterprise_include_once('meta/include/functions_ui_meta.php');
    include_graphs_dependencies();

    $user_access_node = can_user_access_node();

    if (is_metaconsole()) {
        if (metaconsole_connect($server_data) != NOERR) {
            return;
        }
    }

    // Get the agent info.
    $agent = db_get_row('tagente', 'id_agente', $id_agente);
    if ($agent == false) {
        return;
    }

    // Check all groups.
    $groups = agents_get_all_groups_agent($id_agente, $agent['id_grupo']);

    if (is_metaconsole() === true) {
        if (! check_acl_one_of_groups($config['id_user'], $groups, 'AR', false)
            && ! check_acl_one_of_groups($config['id_user'], $groups, 'AW', false)
        ) {
            $grants_on_node = false;
        } else {
            $grants_on_node = true;
        }
    }

    if (is_metaconsole() === true) {
        metaconsole_restore_db();
    }

    if (! check_acl_one_of_groups($config['id_user'], $groups, 'AR', false)
        && ! check_acl_one_of_groups($config['id_user'], $groups, 'AW', false)
    ) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access Agent General Information'
        );
        include_once 'general/noaccess.php';

        return;
    }

    if (is_metaconsole()) {
        if (metaconsole_connect($server_data) != NOERR) {
            return;
        }
    }

    if ($agent === false) {
        ui_print_error_message(__('There was a problem loading agent'));
        if (!empty($server_data) && is_metaconsole()) {
            metaconsole_restore_db();
        }

        return;
    }

    $table = new StdClass();
    $table->width = '100%';
    $table->class = 'floating_form border-bottom-gray';
    $table->id = 'tree_view_agent_detail';
    $table->style = [];
    $table->style['title'] = 'height: 32px; width: 30%; padding-right: 5px; text-align: end;';
    $table->style['data'] = 'height: 32px; width: 70%; padding-left: 5px;font-weight: lighter;';
    $table->head = [];
    $table->data = [];

    // Agent name.
    $cellName = ((bool) $agent['disabled'] === true) ? '<em>' : '';

    if (is_metaconsole() === true) {
        $pwd = $server_data['auth_token'];
        // Create HASH login info.
        $user = $config['id_user'];

        // Extract auth token from serialized field.
        $pwd_deserialiced = json_decode($pwd, true);
        $hashdata = $user.$pwd_deserialiced['auth_token'];

        $hashdata = md5($hashdata);
        if ((bool) $grants_on_node === true && (bool) $user_access_node !== false) {
            $urlAgent = $server_data['server_url'].'/index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$agent['id_agente'];
        } else {
            $urlAgent = '';
        }
    } else {
        $urlAgent = 'index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$agent['id_agente'];
    }

    $cellName = $agent['alias'];

    if ((bool) $agent['disabled'] === true) {
        $cellName .= ui_print_help_tip(__('Disabled'), true).'</em>';
    }

    // Agent name.
    $row = [];
    $row['title'] = __('Agent name');
    $row['data'] = html_print_anchor(
        [
            'href'    => $urlAgent,
            'title'   => __('Click here for view this agent'),
            'class'   => 'font_11',
            'content' => $cellName,
        ],
        true
    );

    $table->data['name'] = $row;

    // Addresses.
    $ips = [];
    $addresses = agents_get_addresses($id_agente);
    $address = agents_get_address($id_agente);

    foreach ($addresses as $k => $add) {
        if ($add == $address) {
            unset($addresses[$k]);
        }
    }

    if (empty($addresses) === false) {
        $address .= ui_print_help_tip(
            __('Other IP addresses').': <br>'.implode('<br>', $addresses),
            true
        );
    }

    if (empty($address) === true) {
        $address = __('N/A');
    }

    $row = [];
    $row['title'] = __('IP Address');
    $row['data'] = $address;
    $table->data['address'] = $row;

    // Agent Interval.
    $row = [];
    $row['title'] = __('Interval');
    $row['data'] = human_time_description_raw($agent['intervalo']);
    $table->data['interval'] = $row;

    // Comments.
    $row = [];
    $row['title'] = __('Description');
    $row['data'] = $agent['comentarios'];
    $table->data['description'] = $row;

    // Last contact.
    $last_contact = ui_print_timestamp($agent['ultimo_contacto'], true, ['style' => 'font-size: 13px;']);

    if ($agent['ultimo_contacto_remoto'] === '01-01-1970 00:00:00') {
        $last_remote_contact = __('Never');
    } else {
        $last_remote_contact = date_w_fixed_tz(
            $agent['ultimo_contacto_remoto']
        );
    }

    $row = [];
    $row['title'] = __('Last contact');
    $row['data'] = $last_contact;
    $table->data['last_contact'] = $row;

    $row = [];
    $row['title'] = __('Remote contact');
    $row['data'] = $last_remote_contact;
    $table->data['remote_contact'] = $row;

    // Next contact (agent).
    $progress = agents_get_next_contact($id_agente);

    $row = [];
    $row['title'] = __('Next agent contact');
    $row['data'] = ui_progress(
        $progress,
        '80%',
        '1.2',
        '#ececec',
        true,
        '',
        false
    );
    $table->data['next_contact'] = $row;

    // Title.
    echo '<span id="fixedBottomHeadTitle" style="display:none">'.__('Agent information').'</span>';
    // Print agent data toggle.
    html_print_table($table);

    // Events graph toggle.
    $eventsGraph = html_print_div(
        [
            'style'   => 'height: 150px;',
            'class'   => 'max-graph-tree-view',
            'content' => graph_graphic_agentevents(
                $id_agente,
                '500px;',
                '100px',
                SECONDS_1DAY,
                '',
                true,
                false,
                550,
                1,
                $server_id
            ),
        ],
        true
    );

    ui_toggle(
        $eventsGraph,
        '<span class="subsection_header_title secondary">'.__('Events (24h)').'</span>',
        '',
        '',
        false,
        false,
        '',
        'white-box-content mrgn_top_0 mrgn_btn_0px',
        'white_table_flex'
    );

    if ($config['agentaccess']) {
        $access_graph = '<div style="height: 150px;" class="w100p center">';
        $access_graph .= graphic_agentaccess(
            $id_agente,
            SECONDS_1DAY,
            false
        );
        $access_graph .= '</div>';
        ui_toggle(
            $access_graph,
            '<span class="subsection_header_title secondary">'.__('Agent access rate (24h)').'</span>',
            '',
            '',
            true,
            false,
            '',
            'white-box-content mrgn_top_0 mrgn_btn_0px border-bottom-gray',
            'white_table_flex'
        );
    }

    // Table network interfaces.
    $network_interfaces_by_agents = agents_get_network_interfaces([$agent]);

    $network_interfaces = [];
    if (empty($network_interfaces_by_agents) === false && empty($network_interfaces_by_agents[$id_agente]) === false) {
        $network_interfaces = $network_interfaces_by_agents[$id_agente]['interfaces'];
    }

    if (empty($network_interfaces) === false) {
        $table = new stdClass();
        $table->id = 'agent_interface_info';
        $table->class = 'databox';
        $table->width = '100%';
        $table->style = [];
        $table->style['interface_status'] = 'width: 30px;';
        $table->style['interface_graph'] = 'width: 20px;';
        $table->head = [];
        $table->data = [];

        foreach ($network_interfaces as $interface_name => $interface) {
            if (empty($interface['traffic']) === false) {
                $permission = (bool) check_acl($config['id_user'], $agent['id_grupo'], 'RR');

                if ($permission === true) {
                    $params = [
                        'interface_name'     => $interface_name,
                        'agent_id'           => $id_agente,
                        'traffic_module_in'  => $interface['traffic']['in'],
                        'traffic_module_out' => $interface['traffic']['out'],
                    ];

                    if (is_metaconsole() === true && empty($server_id) === false) {
                        $params['server'] = $server_id;
                    }

                    $params_json = json_encode($params);
                    $params_encoded = base64_encode($params_json);
                    $url = ui_get_full_url('operation/agentes/interface_traffic_graph_win.php', false, false, false);
                    $graph_url = $url.'?params='.$params_encoded;
                    $win_handle = dechex(crc32($interface['status_module_id'].$interface_name));

                    $graph_link = "<a href=\"javascript:winopeng_var('".$graph_url."','".$win_handle."', 800, 480)\">";
                    $graph_link .= html_print_image(
                        'images/chart_curve.png',
                        true,
                        ['title' => __('Interface traffic')]
                    );
                    $graph_link .= '</a>';
                } else {
                    $graph_link = '';
                }
            } else {
                $graph_link = '';
            }

            $data = [];
            $data['interface_name'] = '<strong>'.$interface_name.'</strong>';
            $data['interface_status'] = $interface['status_image'];
            $data['interface_graph'] = $graph_link;
            $data['interface_ip'] = $interface['ip'];
            $data['interface_mac'] = $interface['mac'];
            $table->data[] = $data;
        }

        // End of table network interfaces.
        $table_interfaces = html_print_table($table, true);
        $table_interfaces .= '<br>';

        ui_toggle($table_interfaces, '<span class="subsection_header_title secondary">'.__('Interface information').' (SNMP)</span>');
    }

        // Advanced data.
        $table_advanced = new StdClass();
        $table_advanced->width = '100%';
        $table_advanced->class = 'floating_form';
        $table_advanced->id = 'tree_view_agent_advanced';
        $table_advanced->style = [];
        $table_advanced->style['title'] = 'height: 32px; width: 30%; padding-right: 5px; text-align: end;';
        $table_advanced->style['data'] = 'height: 32px; width: 70%; padding-left: 5px;font-weight: lighter;';
        $table_advanced->head = [];
        $table_advanced->data = [];

        // Agent version.
        $row = [];
        $row['title'] = __('Agent Version');
        $row['data'] = $agent['agent_version'];
        $table_advanced->data['agent_version'] = $row;

        // Position Information.
    if ($config['activate_gis']) {
        $dataPositionAgent = gis_get_data_last_position_agent($agent['id_agente']);

        if ($dataPositionAgent !== false) {
            if (empty($dataPositionAgent['description']) === false) {
                $positionContent .= $dataPositionAgent['description'];
            } else {
                $positionContent .= $dataPositionAgent['stored_longitude'].', '.$dataPositionAgent['stored_latitude'];
            }

            $position = html_print_anchor(
                [
                    'href'    => $console_url.'index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;tab=gis&amp;id_agente='.$id_agente,
                    'content' => $positionContent,
                ],
                true
            );

            $row = [];
            $row['title'] = __('Position (Long, Lat)');
            $row['data'] = $position;
            $table_advanced->data['agent_position'] = $row;
        }
    }

        // If the url description is setted.
    if (empty($agent['url_address']) === false) {
        $row = [];
        $row['title'] = __('Url address');
        $row['data'] = html_print_anchor(
            [
                'href'    => $agent['url_address'],
                'content' => $agent['url_address'],
            ],
            true
        );
        $table_advanced->data['agent_address'] = $row;
    }

        // Timezone Offset.
    if ($agent['timezone_offset'] != 0) {
        $row = [];
        $row['title'] = __('Timezone Offset');
        $row['data'] = $agent['timezone_offset'];
        $table_advanced->data['agent_timezone_offset'] = $row;
    }

    // Custom fields.
    $fields = db_get_all_rows_filter('tagent_custom_fields', ['display_on_front' => 1]);
    if ($fields === false) {
        $fields = [];
    }

    if ($fields) {
        foreach ($fields as $field) {
            $custom_value = db_get_value_filter('description', 'tagent_custom_data', ['id_field' => $field['id_field'], 'id_agent' => $id_agente]);
            if (empty($custom_value) === false) {
                $row = [];
                $row['title'] = $field['name'].ui_print_help_tip(__('Custom field'), true);
                if ($field['is_password_type']) {
                    $row['data'] = '&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;';
                } else {
                    $row['data'] = ui_bbcode_to_html($custom_value);
                }

                $table_advanced->data['custom_field_'.$field['id_field']] = $row;
            }
        }
    }

    // End of table advanced.
    ui_toggle(
        html_print_table($table_advanced, true),
        '<span class="subsection_header_title secondary">'.__('Advanced information').'</span>',
        '',
        '',
        true,
        false,
        '',
        'white-box-content mrgn_top_0 mrgn_btn_0px border-bottom-gray',
        'white_table_flex'
    );

    if ($user_access_node && check_acl($config['id_user'], $agent['id_grupo'], 'AW')) {
        $buttons_act = '<div style="text-align: right" class="margin-bottom-20 margin-top-20  flex flex-evenly">';

        if ($agent['id_os'] == CLUSTER_OS_ID) {
            $cluster = PandoraFMS\Cluster::loadFromAgentId(
                $agent['id_agente']
            );
            $buttons_act .= '<a target=_blank href="'.$console_url.'index.php?sec=reporting&sec2=operation/cluster/cluster&op=update&id='.$cluster->id().'">';
            $buttons_act .= html_print_submit_button(
                __('Go to cluster edition'),
                'upd_button',
                false,
                ['class' => 'secondary mini'],
                true
            );
        } else {
            $buttons_act .= '<a target=_blank href="'.$console_url.'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente='.$id_agente.$ent.'&tab=module&show_dialog_create=1">';
            $buttons_act .= html_print_submit_button(
                __('Go to module creation'),
                'upd_button',
                false,
                ['class' => 'secondary mini no_decoration'],
                true
            );

            $buttons_act .= '<a target=_blank href="'.$console_url.'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente='.$id_agente.$ent.'">';
            $buttons_act .= html_print_submit_button(
                __('Go to agent edition'),
                'upd_button',
                false,
                ['class' => 'secondary mini no_decoration'],
                true
            );
        }

        $buttons_act .= '</a>';
        $buttons_act .= '</div>';
    }

    echo $buttons_act;

    if (empty($server_data) === false && is_metaconsole() === true) {
        metaconsole_restore_db();
    }

    echo "
        <script>
            function sendHash(url) {
                window.location = url+'&loginhash=auto&loginhash_data=".$hashdata.'&loginhash_user='.str_rot13($user)."';
 
            }

            $('.max-graph-tree-view').ready(function() {
                widthGraph();
            });

            $(window).resize(function() {
                widthGraph();
            });

            function widthGraph () {
                var parentWidth = $('.max-graph-tree-view').parent().width();
                $('.max-graph-tree-view').children().width(parentWidth + 5);
                
                $('<input>').attr({
                    type: 'hidden',
                    id: 'graph-counter',
                    value: 1
                }).appendTo('body');

                
                if ($('#graph-counter').val() == 1) {
                    $('.max-graph-tree-view div.flot-x-axis').css('inset', '-25px').css('margin-top', '10px').css('margin-left', '10px');
                    $('.max-graph-tree-view canvas.flot-base').css('height', '85px');
                    $('#graph-counter').val(2);
                }

            }

        </script>";
}
