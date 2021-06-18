<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
global $config;

// Login check
check_login();

require_once 'include/functions_agents.php';
require_once 'include/functions_alerts.php';
$isFunctionPolicies = enterprise_include('include/functions_policies.php');

$get_agent_alerts_simple = (bool) get_parameter('get_agent_alerts_simple');
$disable_alert = (bool) get_parameter('disable_alert');
$enable_alert = (bool) get_parameter('enable_alert');
$get_actions_module = (bool) get_parameter('get_actions_module');
$show_update_action_menu = (bool) get_parameter('show_update_action_menu');
$get_agent_alerts_agent_view = (bool) get_parameter('get_agent_alerts_agent_view');

if ($get_agent_alerts_simple) {
    $id_agent = (int) get_parameter('id_agent');
    if ($id_agent <= 0) {
        echo json_encode(false);
        return;
    }

    $id_group = agents_get_agent_group($id_agent);

    if (! check_acl($config['id_user'], $id_group, 'AR')) {
        db_pandora_audit(
            'ACL Violation',
            'Trying to access Alert Management'
        );
        echo json_encode(false);
        return;
    }

    if (! check_acl($config['id_user'], 0, 'LW')) {
        db_pandora_audit(
            'ACL Violation',
            'Trying to access Alert Management'
        );
        echo json_encode(false);
        return;
    }

    include_once 'include/functions_agents.php';
    include_once 'include/functions_alerts.php';
    include_once 'include/functions_modules.php';


    $alerts = agents_get_alerts_simple($id_agent);
    if (empty($alerts)) {
        echo json_encode(false);
        return;
    }

    $retval = [];
    foreach ($alerts as $alert) {
        $alert['template'] = alerts_get_alert_template($alert['id_alert_template']);
        $alert['module_name'] = modules_get_agentmodule_name($alert['id_agent_module']);
        $alert['agent_name'] = modules_get_agentmodule_agent_name($alert['id_agent_module']);
        $retval[$alert['id']] = $alert;
    }

    echo json_encode($retval);
    return;
}


if ($get_agent_alerts_agent_view) {
    include_once $config['homedir'].'/include/functions_agents.php';
    include_once $config['homedir'].'/operation/agentes/alerts_status.functions.php';
    include_once $config['homedir'].'/include/functions_users.php';

    $agent_a = check_acl($config['id_user'], 0, 'AR');
    $agent_w = check_acl($config['id_user'], 0, 'AW');
    $access = ($agent_a == true) ? 'AR' : (($agent_w == true) ? 'AW' : 'AR');

    $free_search_alert = get_parameter('free_search_alert', '');
    $all_groups = json_decode(io_safe_output(get_parameter('all_groups')));
    $idAgent = (int) get_parameter('id_agent');
    $filter = get_parameter('filter', 'all_enabled');
    $url = 'index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$idAgent;

    $sortField = get_parameter('sort_field');
    $sort = get_parameter('sort', 'none');
    $selected = true;
    $selectModuleUp = false;
    $selectModuleDown = false;
    $selectTemplateUp = false;
    $selectTemplateDown = false;
    $selectLastFiredUp = false;
    $selectLastFiredDown = false;
    switch ($sortField) {
        case 'module':
            switch ($sort) {
                case 'up':
                    $selectModuleUp = $selected;
                    $order = [
                        'field' => 'agent_module_name',
                        'order' => 'ASC',
                    ];
                break;

                case 'down':
                    $selectModuleDown = $selected;
                    $order = [
                        'field' => 'agent_module_name',
                        'order' => 'DESC',
                    ];
                break;
            }
        break;

        case 'template':
            switch ($sort) {
                case 'up':
                    $selectTemplateUp = $selected;
                    $order = [
                        'field' => 'template_name',
                        'order' => 'ASC',
                    ];
                break;

                case 'down':
                    $selectTemplateDown = $selected;
                    $order = [
                        'field' => 'template_name',
                        'order' => 'DESC',
                    ];
                break;
            }
        break;

        case 'last_fired':
            switch ($sort) {
                case 'up':
                    $selectLastFiredUp = $selected;
                    $order = [
                        'field' => 'last_fired',
                        'order' => 'ASC',
                    ];
                break;

                case 'down':
                    $selectLastFiredDown = $selected;
                    $order = [
                        'field' => 'last_fired',
                        'order' => 'DESC',
                    ];
                break;
            }
        break;

        default:
            $selectDisabledUp = '';
            $selectDisabledDown = '';
            $selectModuleUp = $selected;
            $selectModuleDown = false;
            $selectTemplateUp = false;
            $selectTemplateDown = false;
            $selectLastFiredUp = false;
            $selectLastFiredDown = false;
            $order = [
                'field' => 'agent_module_name',
                'order' => 'ASC',
            ];
        break;
    }

    if ($free_search_alert != '') {
        $whereAlertSimple = 'AND ('.'id_alert_template IN (
                SELECT id
                FROM talert_templates
                WHERE name LIKE "%'.$free_search_alert.'%") OR '.'id_alert_template IN (
                SELECT id
                FROM talert_templates
                WHERE id_alert_action IN (
                    SELECT id
                    FROM talert_actions
                    WHERE name LIKE "%'.$free_search_alert.'%")) OR '.'talert_template_modules.id IN (
                SELECT id_alert_template_module
                FROM talert_template_module_actions
                WHERE id_alert_action IN (
                    SELECT id
                    FROM talert_actions
                    WHERE name LIKE "%'.$free_search_alert.'%")) OR '.'id_agent_module IN (
                SELECT id_agente_modulo
                FROM tagente_modulo
                WHERE nombre LIKE "%'.$free_search_alert.'%") OR '.'id_agent_module IN (
                SELECT id_agente_modulo
                FROM tagente_modulo
                WHERE alias LIKE "%'.$free_search_alert.'%")'.')';
    } else {
        $whereAlertSimple = '';
    }

    // Add checks for user ACL.
    $groups = users_get_groups($config['id_user'], $access);
    $id_groups = array_keys($groups);

    if (empty($id_groups)) {
        $whereAlertSimple .= ' AND (1 = 0) ';
    } else {
        $whereAlertSimple .= sprintf(
            ' AND id_agent_module IN (
            SELECT tam.id_agente_modulo
            FROM tagente_modulo tam
            WHERE tam.id_agente IN (SELECT ta.id_agente
                FROM tagente ta LEFT JOIN tagent_secondary_group tasg ON
                    ta.id_agente = tasg.id_agent
                    WHERE (ta.id_grupo IN (%s) OR tasg.id_group IN (%s)))) ',
            implode(',', $id_groups),
            implode(',', $id_groups)
        );
    }

    $alerts = [];

    $filter_alert = [];
    if ($filter_standby == 'standby_on') {
        $filter_alert['disabled'] = $filter;
        $filter_alert['standby'] = '1';
    } else if ($filter_standby == 'standby_off') {
        $filter_alert['disabled'] = $filter;
        $filter_alert['standby'] = '0';
    } else {
        $filter_alert['disabled'] = $filter;
    }

    $options_simple = ['order' => $order];

    $alerts['alerts_simple'] = agents_get_alerts_simple($idAgent, $filter_alert, $options_simple, $whereAlertSimple, false, false, false, false, $strict_user, $tag_filter);
    $countAlertsSimple = agents_get_alerts_simple($idAgent, $filter_alert, false, $whereAlertSimple, false, false, false, true, $strict_user, $tag_filter);

    // Urls to sort the table.
    $url_up_module = $url.'&sort_field=module&sort=up';
    $url_down_module = $url.'&sort_field=module&sort=down';
    $url_up_template = $url.'&sort_field=template&sort=up';
    $url_down_template = $url.'&sort_field=template&sort=down';
    $url_up_lastfired = $url.'&sort_field=last_fired&sort=up';
    $url_down_lastfired = $url.'&sort_field=last_fired&sort=down';

    $table = new stdClass();
    $table->width = '100%';
    $table->class = 'info_table';
    $table->cellpadding = '0';
    $table->cellspacing = '0';
    $table->size = [];
    $table->head = [];
    $table->align = [];

    if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) {
        $table->size[7] = '5%';
        if (check_acl_one_of_groups($config['id_user'], $all_groups, 'LW') || check_acl_one_of_groups($config['id_user'], $all_groups, 'LM')) {
            $table->head[8] = __('Validate');
            $table->align[8] = 'left';
            $table->size[8] = '5%';
        }

        $table->head[0] = "<span title='".__('Policy')."'>".__('P.').'</span>';
        $table->head[1] = "<span title='".__('Standby')."'>".__('S.').'</span>';

        if (check_acl_one_of_groups($config['id_user'], $all_groups, 'AW') || check_acl_one_of_groups($config['id_user'], $all_groups, 'LM')) {
            $table->head[2] = "<span title='".__('Force execution')."'>".__('F.').'</span>';
        }

        $table->head[3] = __('Module');
        $table->head[4] = __('Template');
        $table->head[5] = __('Action');
        $table->head[6] = __('Last fired');
        $table->head[7] = __('Status');

        $table->align[7] = 'center';

        $table->head[3] .= ui_get_sorting_arrows($url_up_module, $url_down_module, $selectModuleUp, $selectModuleDown);
        $table->head[4] .= ui_get_sorting_arrows($url_up_template, $url_down_template, $selectTemplateUp, $selectTemplateDown);
        $table->head[6] .= ui_get_sorting_arrows($url_up_lastfired, $url_down_lastfired, $selectLastFiredUp, $selectLastFiredDown);
    } else {
        $table->size[6] = '5%';
        if (check_acl($config['id_user'], $id_group, 'LW') || check_acl($config['id_user'], $id_group, 'LM')) {
            $table->head[7] = __('Validate');
            $table->align[7] = 'left';
            $table->size[7] = '5%';
        }

        $table->head[0] = "<span title='".__('Standby')."'>".__('S.').'</span>';

        if (check_acl($config['id_user'], $id_group, 'AW') || check_acl($config['id_user'], $id_group, 'LM')) {
            $table->head[1] = "<span title='".__('Force execution')."'>".__('F.').'</span>';
        }

        $table->head[2] = __('Module');
        $table->head[3] = __('Template');
        $table->head[4] = __('Action');
        $table->head[5] = __('Last fired');
        $table->head[6] = __('Status');

        $table->align[6] = 'center';

        $table->head[2] .= ui_get_sorting_arrows($url_up_module, $url_down_module, $selectModuleUp, $selectModuleDown);
        $table->head[3] .= ui_get_sorting_arrows($url_up_template, $url_down_template, $selectTemplateUp, $selectTemplateDown);
        $table->head[5] .= ui_get_sorting_arrows($url_up_lastfired, $url_down_lastfired, $selectLastFiredUp, $selectLastFiredDown);
    }

    $table->data = [];
    $rowPair = true;
    $iterator = 0;
    foreach ($alerts['alerts_simple'] as $alert) {
        $row = ui_format_alert_row($alert, false, $url, 'font-size: 7pt;');
        $table->data[] = $row;
    }

    if (!empty($table->data)) {
        html_print_table($table);
    } else {
        ui_print_info_message(['no_close' => true, 'message' => __('No alerts found') ]);
    }
}

if ($enable_alert) {
    if (! check_acl($config['id_user'], 0, 'LW')) {
        db_pandora_audit(
            'ACL Violation',
            'Trying to access Alert Management'
        );
        return false;
    }

    $id_alert = (int) get_parameter('id_alert');

    $result = alerts_agent_module_disable($id_alert, false);
    if ($result) {
        echo __('Successfully enabled');
    } else {
        echo __('Could not be enabled');
    }

    return;
}

if ($disable_alert) {
    if (! check_acl($config['id_user'], 0, 'LW')) {
        db_pandora_audit(
            'ACL Violation',
            'Trying to access Alert Management'
        );
        return false;
    }

    $id_alert = (int) get_parameter('id_alert');

    $result = alerts_agent_module_disable($id_alert, true);
    if ($result) {
        echo __('Successfully disabled');
    } else {
        echo __('Could not be disabled');
    }

    return;
}

if ($get_actions_module) {
    if (! check_acl($config['id_user'], 0, 'LW')) {
        db_pandora_audit(
            'ACL Violation',
            'Trying to access Alert Management'
        );
        return false;
    }

    $id_module = get_parameter('id_module');

    if (empty($id_module)) {
        return false;
    }

    $alerts_modules = alerts_get_alerts_module_name($id_module);

    echo json_encode($alerts_modules);
    return;
}

if ($show_update_action_menu) {
    if (! check_acl($config['id_user'], 0, 'LW')) {
        db_pandora_audit(
            'ACL Violation',
            'Trying to access Alert Management'
        );
        return false;
    }

    $id_agent_module = (int) get_parameter('id_agent_module');
    $id_module_action = (int) get_parameter('id_module_action');
    $id_agent = (int) get_parameter('id_agent');
    $id_alert = (int) get_parameter('id_alert');

    $module_name = modules_get_agentmodule_name($id_agent_module);

    $agent_alias = modules_get_agentmodule_agent_alias($id_agent_module);

    $id_action = (int) get_parameter('id_action');

    $own_groups = users_get_groups($config['id_user'], 'LW', true);
    $filter_groups = '';
    $filter_groups = implode(',', array_keys($own_groups));
    $actions = alerts_get_alert_actions_filter(true, 'id_group IN ('.$filter_groups.')');

    $action_option = db_get_row(
        'talert_template_module_actions',
        'id',
        $id_action
    );

    $data .= '<form id="update_action-'.$alert['id'].'" method="post">';
    $data .= '<table class="databox_color w100p">';
        $data .= html_print_input_hidden(
            'update_action',
            1,
            true
        );
        $data .= html_print_input_hidden(
            'id_module_action_ajax',
            $id_action,
            true
        );
    if (! $id_agente) {
        $data .= '<tr class="datos2">';
            $data .= '<td class="datos2 bolder_6px">';
            $data .= __('Agent').'&nbsp;'.ui_print_help_icon(
                'alert_scalate',
                true,
                ui_get_full_url(false, false, false, false)
            );
            $data .= '</td>';
            $data .= '<td class="datos">';
            $data .= ui_print_truncate_text(
                $agent_alias,
                'agent_small',
                false,
                true,
                true,
                '[&hellip;]'
            );
            $data .= '</td>';
        $data .= '</tr>';
    }

        $data .= '<tr class="datos">';
            $data .= '<td class="datos bolder_6px">';
            $data .= __('Module');
            $data .= '</td>';
            $data .= '<td class="datos">';
            $data .= ui_print_truncate_text(
                $module_name,
                'module_small',
                false,
                true,
                true,
                '[&hellip;]'
            );
            $data .= '</td>';
        $data .= '</tr>';
        $data .= '<tr class="datos2">';
            $data .= '<td class="datos2 bolder_6px">';
                $data .= __('Action');
            $data .= '</td>';
            $data .= '<td class="datos2">';
                $data .= html_print_select(
                    $actions,
                    'action_select_ajax',
                    $action_option['id_alert_action'],
                    '',
                    false,
                    0,
                    true,
                    false,
                    true,
                    '',
                    false,
                    'width:150px'
                );
            $data .= '</td>';
        $data .= '</tr>';
        $data .= '<tr class="datos">';
            $data .= '<td class="datos bolder_6px">';
                $data .= __('Number of alerts match from');
            $data .= '</td>';
            $data .= '<td class="datos">';
                $data .= html_print_input_text(
                    'fires_min_ajax',
                    $action_option['fires_min'],
                    '',
                    4,
                    10,
                    true
                );
                $data .= ' '.__('to').' ';
                $data .= html_print_input_text(
                    'fires_max_ajax',
                    $action_option['fires_max'],
                    '',
                    4,
                    10,
                    true
                );
            $data .= '</td>';
        $data .= '</tr>';
        $data .= '<tr class="datos2">';
            $data .= '<td class="datos2 bolder_6px">';
                $data .= __('Threshold');
            $data .= '</td>';
            $data .= '<td class="datos2">';
                $data .= html_print_extended_select_for_time(
                    'module_action_threshold_ajax',
                    $action_option['module_action_threshold'],
                    '',
                    '',
                    '',
                    false,
                    true,
                    false,
                    true,
                    '',
                    false,
                    false,
                    '',
                    false,
                    true
                );
            $data .= '</td>';
        $data .= '</tr>';
    $data .= '</table>';
    $data .= html_print_submit_button(
        __('Update'),
        'updbutton',
        false,
        [
            'class' => 'sub next',
            'style' => 'float:right',
        ],
        true
    );
    $data .= '</form>';
    echo $data;
    return;
}

return;
