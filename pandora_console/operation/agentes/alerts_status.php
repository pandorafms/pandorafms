<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
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

if (is_ajax()) {
    include_once 'include/functions_reporting.php';

    $get_alert_fired = get_parameter('get_alert_fired', 0);

    if ($get_alert_fired) {
        // Calculate alerts fired
        $data_reporting = reporting_get_group_stats();
        echo $data_reporting['monitor_alerts_fired'];
    }

    return;
}

require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/operation/agentes/alerts_status.functions.php';
require_once $config['homedir'].'/include/functions_users.php';

$isFunctionPolicies = enterprise_include_once('include/functions_policies.php');

$strict_user = db_get_value('strict_acl', 'tusuario', 'id_user', $config['id_user']);

$filter = get_parameter('filter', 'all_enabled');
$filter_standby = get_parameter('filter_standby', 'all');
$offset_simple = (int) get_parameter_get('offset_simple', 0);
$id_group = (int) get_parameter('ag_group', 0);
// 0 is the All group (selects all groups)
$free_search = get_parameter('free_search', '');

$user_tag_array = tags_get_user_tags($config['id_user'], 'AR', true);

if ($user_tag_array) {
    $user_tag_array = array_values(array_keys($user_tag_array));

    $user_tag = '';

    foreach ($user_tag_array as $key => $value) {
        if ($value === end($user_tag_array)) {
            $user_tag .= $value;
        } else {
            $user_tag .= $value.',';
        }
    }

    $tag_filter = get_parameter('tag_filter', $user_tag);

    $tag_param_validate = explode(',', $tag_filter);

    foreach ($tag_param_validate as $key => $value) {
        if (!in_array($value, $user_tag_array)) {
            db_pandora_audit(
                'ACL Violation',
                'Trying to access Alert view'
            );
            include 'general/noaccess.php';
            exit;
        }
    }
} else {
    $tag_filter = get_parameter('tag_filter', 0);
}

if ($tag_filter) {
    if ($id_group && $strict_user) {
        $tag_filter = 0;
    }
}

$action_filter = get_parameter('action_filter', 0);

$sec2 = get_parameter_get('sec2');
$sec2 = safe_url_extraclean($sec2);

$sec = get_parameter_get('sec');
$sec = safe_url_extraclean($sec);

$flag_alert = (bool) get_parameter('force_execution', 0);
$alert_validate = (bool) get_parameter('alert_validate', 0);
$tab = get_parameter_get('tab', null);

$refr = (int) get_parameter('refr', 0);
$pure = get_parameter('pure', 0);

$url = 'index.php?sec='.$sec.'&sec2='.$sec2.'&refr='.$refr.'&filter='.$filter.'&filter_standby='.$filter_standby.'&ag_group='.$id_group.'&tag_filter='.$tag_filter.'&action_filter='.$action_filter;

if ($flag_alert == 1 && check_acl($config['id_user'], $id_group, 'AW')) {
    forceExecution($id_group);
}


$idAgent = get_parameter_get('id_agente', 0);

// Show alerts for specific agent
if ($idAgent != 0) {
    $url = $url.'&id_agente='.$idAgent;

    $id_group = agents_get_agent_group($idAgent);

    $is_extra = enterprise_hook(
        'policies_is_agent_extra_policy',
        [$id_agente]
    );

    if ($is_extra === ENTERPRISE_NOT_HOOK) {
        $is_extra = false;
    }

    // All groups is calculated in ver_agente.php. Avoid to calculate it again
    if (!isset($all_groups)) {
        $all_groups = agents_get_all_groups_agent($idAgent, $id_group);
    }

    if (!check_acl_one_of_groups($config['id_user'], $all_groups, 'AR') && !check_acl_one_of_groups($config['id_user'], $id_group, 'AW') && !$is_extra) {
        db_pandora_audit('ACL Violation', 'Trying to access alert view');
        include 'general/noaccess.php';
        exit;
    }

    $idGroup = false;

    $print_agent = false;

    $tab = get_parameter('tab', 'main');

    ob_start();

    if ($tab == 'main') {
        $agent_view_page = true;
    }
} else {
    $agent_a = check_acl($config['id_user'], 0, 'AR');
    $agent_w = check_acl($config['id_user'], 0, 'AW');
    $access = ($agent_a == true) ? 'AR' : (($agent_w == true) ? 'AW' : 'AR');

    if (!$agent_a && !$agent_w) {
        db_pandora_audit('ACL Violation', 'Trying to access alert view');
        include 'general/noaccess.php';
        return;
    }

    $agents = array_keys(
        agents_get_group_agents(
            array_keys(
                users_get_groups($config['id_user'], $access, false)
            ),
            false,
            'lower',
            true
        )
    );

    $idGroup = $id_group;
    // If there is no agent defined, it means that it cannot search for the secondary groups
    $all_groups = [$id_group];

    $print_agent = true;

    if (!is_metaconsole()) {
        ui_print_page_header(__('Alert detail'), 'images/op_alerts.png', false, 'alert_validation');
    } else {
        ui_meta_print_header(__('Alerts view'));
    }
}

if ($alert_validate) {
    if (check_acl_one_of_groups($config['id_user'], $all_groups, 'AW') || check_acl_one_of_groups($config['id_user'], $all_groups, 'LM')) {
        validateAlert();
    } else {
        ui_print_error_message(__('Insufficient permissions to validate alerts'));
    }
}

enterprise_hook('open_meta_frame');

if ($free_search != '') {
    $whereAlertSimple = 'AND ('.'id_alert_template IN (
			SELECT id
			FROM talert_templates
			WHERE name LIKE "%'.$free_search.'%") OR '.'id_alert_template IN (
			SELECT id
			FROM talert_templates
			WHERE id_alert_action IN (
				SELECT id
				FROM talert_actions
				WHERE name LIKE "%'.$free_search.'%")) OR '.'talert_template_modules.id IN (
			SELECT id_alert_template_module
			FROM talert_template_module_actions
			WHERE id_alert_action IN (
				SELECT id
				FROM talert_actions
				WHERE name LIKE "%'.$free_search.'%")) OR '.'id_agent_module IN (
			SELECT id_agente_modulo
			FROM tagente_modulo
			WHERE nombre LIKE "%'.$free_search.'%") OR '.'id_agent_module IN (
			SELECT id_agente_modulo
			FROM tagente_modulo
			WHERE id_agente IN (
				SELECT id_agente
				FROM tagente
				WHERE nombre LIKE "%'.$free_search.'%") OR alias LIKE "%'.$free_search.'%")'.')';
} else {
    $whereAlertSimple = '';
}

$sortField = get_parameter('sort_field');
$sort = get_parameter('sort', 'none');
$selected = true;
$selectAgentUp = false;
$selectAgentDown = false;
$selectModuleUp = false;
$selectModuleDown = false;
$selectTemplateUp = false;
$selectTemplateDown = false;
$selectLastFiredUp = false;
$selectLastFiredDown = false;

switch ($sortField) {
    case 'agent':
        switch ($sort) {
            case 'up':
                $selectAgentUp = $selected;
                $order = [
                    'field' => 'agent_name',
                    'order' => 'ASC',
                ];
            break;

            case 'down':
                $selectAgentDown = $selected;
                $order = [
                    'field' => 'agent_name',
                    'order' => 'DESC',
                ];
            break;
        }
    break;

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
        if ($print_agent) {
            $selectDisabledUp = '';
            $selectDisabledDown = '';
            $selectAgentUp = false;
            $selectAgentDown = false;
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
        } else {
            $selectDisabledUp = '';
            $selectDisabledDown = '';
            $selectAgentUp = false;
            $selectAgentDown = false;
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
        }
    break;
}


// Add checks for user ACL
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
$options_simple = [
    'offset' => $offset_simple,
    'limit'  => $config['block_size'],
    'order'  => $order,
];

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

if (is_metaconsole()) {
    include_once $config['homedir'].'/enterprise/meta/include/functions_alerts_meta.php';
    if ($idAgent != 0) {
        $alerts['alerts_simple'] = alerts_meta_get_alerts($agents, $filter_alert, $options_simple, $whereAlertSimple, false, false, $idGroup, false, $strict_user);

        $countAlertsSimple = alerts_meta_get_alerts($agents, $filter_alert, false, $whereAlertSimple, false, false, $idGroup, true, $strict_user);
    } else {
        $id_groups = array_keys(
            users_get_groups($config['id_user'], 'AR', false)
        );

        $alerts['alerts_simple'] = alerts_meta_get_group_alerts($id_groups, $filter_alert, $options_simple, $whereAlertSimple, false, false, $idGroup, false, $strict_user, $tag_filter);

        $countAlertsSimple = alerts_meta_get_group_alerts($id_groups, $filter_alert, false, $whereAlertSimple, false, false, $idGroup, true, $strict_user, $tag_filter);
    }
} else {
    if ($idAgent != 0) {
        $alerts['alerts_simple'] = agents_get_alerts_simple($idAgent, $filter_alert, $options_simple, $whereAlertSimple, false, false, $idGroup, false, $strict_user, $tag_filter);

        $countAlertsSimple = agents_get_alerts_simple($idAgent, $filter_alert, false, $whereAlertSimple, false, false, $idGroup, true, $strict_user, $tag_filter);
    } else {
        $id_groups = array_keys(
            users_get_groups($config['id_user'], $access, false)
        );

        $alerts['alerts_simple'] = get_group_alerts($id_groups, $filter_alert, $options_simple, $whereAlertSimple, false, false, $idGroup, false, $strict_user, $tag_filter, $action_filter);

        $countAlertsSimple = get_group_alerts($id_groups, $filter_alert, false, $whereAlertSimple, false, false, $idGroup, true, $strict_user, $tag_filter, $action_filter);
    }
}

if ($tab != null) {
    $url = $url.'&tab='.$tab;
}

if ($pure) {
    $url .= '&pure='.$pure;
}

if ($free_search != '') {
    $url .= '&free_search='.$free_search;
}

// Filter form
if ($print_agent) {
    if (is_metaconsole()) {
        ui_toggle(
            printFormFilterAlert(
                $id_group,
                $filter,
                $free_search,
                $url,
                $filter_standby,
                $tag_filter,
                false,
                true,
                $strict_user
            ),
            __('Show Options')
        );
        ;
    } else {
        ui_toggle(
            printFormFilterAlert(
                $id_group,
                $filter,
                $free_search,
                $url,
                $filter_standby,
                $tag_filter,
                $action_filter,
                true,
                $strict_user
            ),
            __('Alert control filter'),
            __('Toggle filter(s)'),
            $access
        );
    }
}

// Urls to sort the table.
$url_up_agente = $url.'&sort_field=agent&sort=up';
$url_down_agente = $url.'&sort_field=agent&sort=down';
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
    if ($print_agent) {
        if (!is_metaconsole()) {
            $table->size[8] = '4%';
            if (check_acl($config['id_user'], $id_group, 'LW') || check_acl($config['id_user'], $id_group, 'LM')) {
                $table->head[9] = html_print_checkbox('all_validate', 0, false, true, false);
                $table->align[9] = 'left';
                $table->size[9] = '5%';
            }
        }

        $table->head[0] = "<span title='".__('Policy')."'>".__('P.').'</span>';

        $table->head[1] = "<span title='".__('Standby')."'>".__('S.').'</span>';

        if (!is_metaconsole()) {
            if (check_acl($config['id_user'], $id_group, 'AW') || check_acl($config['id_user'], $id_group, 'LM')) {
                $table->head[2] = "<span title='".__('Force execution')."'>".__('F.').'</span>';
            }
        }

        $table->head[3] = __('Agent');
        $table->head[4] = __('Module');
        $table->head[5] = __('Template');
        $table->head[6] = __('Action');
        $table->head[7] = __('Last fired');
        $table->head[8] = __('Status');

        $table->align[8] = 'center';

        // Sort buttons are only for normal console
        if (!is_metaconsole()) {
            $table->head[3] .= ui_get_sorting_arrows($url_up_agente, $url_down_agente, $selectAgentUp, $selectAgentDown);
            $table->head[4] .= ui_get_sorting_arrows($url_up_module, $url_down_module, $selectModuleUp, $selectModuleDown);
            $table->head[5] .= ui_get_sorting_arrows($url_up_template, $url_down_template, $selectTemplateUp, $selectTemplateDown);
            $table->head[7] .= ui_get_sorting_arrows($url_up_lastfired, $url_down_lastfired, $selectLastFiredUp, $selectLastFiredDown);
        }
    } else {
        if (!is_metaconsole()) {
            $table->size[7] = '5%';
            if (check_acl_one_of_groups($config['id_user'], $all_groups, 'LW') || check_acl_one_of_groups($config['id_user'], $all_groups, 'LM')) {
                $table->head[8] = __('Validate');
                $table->align[8] = 'left';
                $table->size[8] = '5%';
            }
        }

        $table->head[0] = "<span title='".__('Policy')."'>".__('P.').'</span>';

        $table->head[1] = "<span title='".__('Standby')."'>".__('S.').'</span>';

        if (!is_metaconsole()) {
            if (check_acl_one_of_groups($config['id_user'], $all_groups, 'AW') || check_acl_one_of_groups($config['id_user'], $all_groups, 'LM')) {
                $table->head[2] = "<span title='".__('Force execution')."'>".__('F.').'</span>';
            }
        }

        $table->head[3] = __('Module');
        $table->head[4] = __('Template');
        $table->head[5] = __('Action');
        $table->head[6] = __('Last fired');
        $table->head[7] = __('Status');


        $table->align[7] = 'center';

        // Sort buttons are only for normal console
        if (!is_metaconsole()) {
            $table->head[3] .= ui_get_sorting_arrows($url_up_module, $url_down_module, $selectModuleUp, $selectModuleDown);
            $table->head[4] .= ui_get_sorting_arrows($url_up_template, $url_down_template, $selectTemplateUp, $selectTemplateDown);
            $table->head[6] .= ui_get_sorting_arrows($url_up_lastfired, $url_down_lastfired, $selectLastFiredUp, $selectLastFiredDown);
        }
    }
} else {
    if ($print_agent) {
        if (!is_metaconsole()) {
            $table->size[7] = '5%';
            if (check_acl($config['id_user'], $id_group, 'LW') || check_acl($config['id_user'], $id_group, 'LM')) {
                $table->head[8] = __('Validate');
                $table->align[8] = 'left';
                $table->size[8] = '5%';
            }
        }

        $table->head[0] = "<span title='".__('Standby')."'>".__('S.').'</span>';
        if (!is_metaconsole()) {
            if (check_acl($config['id_user'], $id_group, 'AW') || check_acl($config['id_user'], $id_group, 'LM')) {
                $table->head[1] = "<span title='".__('Force execution')."'>".__('F.').'</span>';
            }
        }

        $table->head[2] = __('Agent');
        $table->head[3] = __('Module');
        $table->head[4] = __('Template');
        $table->head[5] = __('Action');
        $table->head[6] = __('Last fired');
        $table->head[7] = __('Status');

        $table->align[7] = 'center';

        // Sort buttons are only for normal console
        if (!is_metaconsole()) {
            $table->head[3] .= ui_get_sorting_arrows($url_up_agente, $url_down_agente, $selectAgentUp, $selectAgentDown);
            $table->head[4] .= ui_get_sorting_arrows($url_up_module, $url_down_module, $selectModuleUp, $selectModuleDown);
            $table->head[5] .= ui_get_sorting_arrows($url_up_template, $url_down_template, $selectTemplateUp, $selectTemplateDown);
            $table->head[6] .= ui_get_sorting_arrows($url_up_lastfired, $url_down_lastfired, $selectLastFiredUp, $selectLastFiredDown);
        }
    } else {
        if (!is_metaconsole()) {
            $table->size[6] = '5%';
            if (check_acl($config['id_user'], $id_group, 'LW') || check_acl($config['id_user'], $id_group, 'LM')) {
                $table->head[7] = __('Validate');
                $table->align[7] = 'left';
                $table->size[7] = '5%';
            }
        }

        $table->head[0] = "<span title='".__('Standby')."'>".__('S.').'</span>';
        if (!is_metaconsole()) {
            if (check_acl($config['id_user'], $id_group, 'AW') || check_acl($config['id_user'], $id_group, 'LM')) {
                $table->head[1] = "<span title='".__('Force execution')."'>".__('F.').'</span>';
            }
        }

        $table->head[2] = __('Module');
        $table->head[3] = __('Template');
        $table->head[4] = __('Action');
        $table->head[5] = __('Last fired');
        $table->head[6] = __('Status');

        $table->align[6] = 'center';

        // Sort buttons are only for normal console
        if (!is_metaconsole()) {
            $table->head[2] .= ui_get_sorting_arrows($url_up_module, $url_down_module, $selectModuleUp, $selectModuleDown);
            $table->head[3] .= ui_get_sorting_arrows($url_up_template, $url_down_template, $selectTemplateUp, $selectTemplateDown);
            $table->head[5] .= ui_get_sorting_arrows($url_up_lastfired, $url_down_lastfired, $selectLastFiredUp, $selectLastFiredDown);
        }
    }
}


if ($sortField) {
    $url .= '&sort_field='.$sortField;
    $url .= '&sort='.$sort;
}

$table->data = [];

$rowPair = true;
$iterator = 0;
foreach ($alerts['alerts_simple'] as $alert) {
    $row = ui_format_alert_row($alert, $print_agent, $url, 'font-size: 7pt;');
    $table->data[] = $row;
}

if (!empty($table->data)) {
    $class = '';
    if ($agent_view_page === true) {
        $class = 'w100p no-padding-imp';
    }

    echo '<form class="'.$class.'" method="post" action="'.$url.'">';

    ui_pagination(
        $countAlertsSimple,
        $url,
        $offset_simple,
        0,
        false,
        'offset_simple'
    );
    html_print_table($table);
    ui_pagination(
        $countAlertsSimple,
        $url,
        $offset_simple,
        0,
        false,
        'offset_simple',
        true,
        'pagination-bottom'
    );

    if (!is_metaconsole()) {
        if (check_acl($config['id_user'], $id_group, 'AW') || check_acl($config['id_user'], $id_group, 'LM')) {
            if (count($alerts['alerts_simple']) > 0) {
                echo '<div class="action-buttons" style="width: '.$table->width.';">';
                html_print_submit_button(__('Validate'), 'alert_validate', false, 'class="sub ok"', false);
                echo '</div>';
            }
        }
    }

    echo '</form>';
    $alerts_defined = true;
} else {
    ui_print_info_message(['no_close' => true, 'message' => __('No alerts found') ]);
    $alerts_defined = false;
}

$html_content = ob_get_clean();

if ($agent_view_page === true) {
    // Create controlled toggle content.
    ui_toggle(
        $html_content,
        __('Full list of alerts'),
        'status_monitor_agent',
        !$alerts_defined,
        false,
        '',
        'white_table_graph_content no-padding-imp'
    );
} else {
    // Dump entire content.
    echo $html_content;
}


// strict user hidden
echo '<div id="strict_hidden" style="display:none;">';
html_print_input_text('strict_user_hidden', $strict_user);

html_print_input_text('is_meta_hidden', (int) is_metaconsole());
echo '</div>';

enterprise_hook('close_meta_frame');


ui_require_css_file('cluetip', 'include/styles/js/');
ui_require_jquery_file('cluetip');
?>

<script type="text/javascript">
    
    $(document).ready (function () {
        $("a.template_details").cluetip ({
            arrows: true,
            attribute: 'href',
            cluetipClass: 'default'
        }).click (function () {
            return false;
        });


        $('[id^=checkbox-validate]').change(function(){
            if($(this).parent().parent().hasClass('checkselected')){
                $(this).parent().parent().removeClass('checkselected');
            }
            else{
                $(this).parent().parent().addClass('checkselected');                            
            }
        });

        $('[id^=checkbox-all_validate]').change(function(){    
            if ($("#checkbox-all_validate").prop("checked")) {
                $('[id^=checkbox-validate]').parent().parent().addClass('checkselected');                
                $('[name^=validate]').prop("checked", true);
            }
            else{
                $('[id^=checkbox-validate]').parent().parent().removeClass('checkselected');
                $('[name^=validate]').prop("checked", false);                
            }    
        });

    });

    $('table.alert-status-filter #ag_group').change (function () {
        var strict_user = $("#text-strict_user_hidden").val();
        var is_meta = $("#text-is_meta_hidden").val();

        if (($(this).val() != 0) && (strict_user != 0)) {
            $("table.alert-status-filter #tag_filter").hide();
            if (is_meta) {
                $("table.alert-status-filter #table1-0-4").hide();
            } else {
                $("table.alert-status-filter #table2-0-4").hide();
            }
        } else {
            $("#tag_filter").show();
            if (is_meta) {
                $("table.alert-status-filter #table1-0-4").show();
            } else {
                $("table.alert-status-filter #table2-0-4").show();
            }
        }
    }).change();
    
</script>
