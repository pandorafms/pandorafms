<?php
/**
 * List view for Alerts.
 *
 * @category   Alerts
 * @package    Community
 * @subpackage Software agents repository
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ==========================================================
 * Copyright (c) 2005-2022 Artica Soluciones Tecnológicas S.L
 * This code is NOT free software. This code is NOT licenced under GPL2 licence
 * You cannot redistribute it without written permission of copyright holder.
 * ============================================================================
 */

// Begin.
global $config;

// Login check.
check_login();

// Check if this page is included from a agent edition.
if ((bool) check_acl($config['id_user'], 0, 'LW') === false
    && (bool) check_acl($config['id_user'], 0, 'AD') === false
    && (bool) check_acl($config['id_user'], 0, 'LM') === false
) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Alert Management'
    );
    include 'general/noaccess.php';
    exit;
}

require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_modules.php';
require_once $config['homedir'].'/include/functions_users.php';

$pure = get_parameter('pure', 0);
$agent_id = get_parameter('agent_id', 0);

if (is_metaconsole() === true) {
    $sec = 'advanced';
} else {
    $sec = 'galertas';
}

if ($id_agente) {
    $sec2 = 'godmode/agentes/configurar_agente&tab=alert&id_agente='.$id_agente;
} else {
    $sec2 = 'godmode/alerts/alert_list';
}

// Table for filter controls.
$form_filter = '<form method="post" action="index.php?sec='.$sec.'&amp;sec2='.$sec2.'&amp;refr='.((int) get_parameter('refr', 0)).'&amp;pure='.$config['pure'].'">';
$form_filter .= "<input type='hidden' name='search' value='1' />";
$form_filter .= '<table  cellpadding="0" cellspacing="0" class="databox filters w100p filter-table-adv">';
$form_filter .= '<tr>';
$form_filter .= '<td class="w33p">'.html_print_label_input_block(
    __('Template name'),
    html_print_input_text('template_name', $templateName, '', 12, 255, true)
).'</td>';

$temp = agents_get_agents();
$arrayAgents = [];

// Avoid empty arrays, warning messages are UGLY !
if ($temp !== false) {
    foreach ($temp as $agentElement) {
        $arrayAgents[$agentElement['id_agente']] = $agentElement['nombre'];
    }
}

$params = [];
$params['return'] = true;
$params['show_helptip'] = true;
$params['input_name'] = 'agent_name';
$params['value'] = $agentName;
$params['size'] = 24;
$params['metaconsole_enabled'] = false;
$params['use_hidden_input_idagent'] = true;
$params['print_hidden_input_idagent'] = true;
$params['hidden_input_idagent_id'] = 'hidden-autocomplete_id_agent';
$params['hidden_input_idagent_name'] = 'agent_id';
$params['hidden_input_idagent_value'] = $agent_id;




$form_filter .= '</td>';
$form_filter .= '<td class="w33p">'.html_print_label_input_block(
    __('Agents'),
    ui_print_agent_autocomplete_input($params)
).'</td>';

$form_filter .= '<td class="w33p">'.html_print_label_input_block(
    __('Module name'),
    html_print_input_text('module_name', $moduleName, '', 12, 255, true)
).'</td>';
$form_filter .= '</tr>';

$all_groups = db_get_value('is_admin', 'tusuario', 'id_user', $config['id_user']);

if ((bool) check_acl($config['id_user'], 0, 'AD') === true) {
    $groups_user = users_get_groups($config['id_user'], 'AD', $all_groups);
} else if ((bool) check_acl($config['id_user'], 0, 'LW') === true) {
    $groups_user = users_get_groups($config['id_user'], 'LW', $all_groups);
} else if ((bool) check_acl($config['id_user'], 0, 'LM') === true) {
    $groups_user = users_get_groups($config['id_user'], 'LM', $all_groups);
}

if ($groups_user === false) {
    $groups_user = [];
}

$groups_id = implode(',', array_keys($groups_user));

$form_filter .= '<tr>';

$temp = db_get_all_rows_sql('SELECT id, name FROM talert_actions WHERE id_group IN ('.$groups_id.');');
$arrayActions = [];
if (is_array($temp) === true) {
    foreach ($temp as $actionElement) {
        $arrayActions[$actionElement['id']] = $actionElement['name'];
    }
}

$form_filter .= '<td class="w33p">'.html_print_label_input_block(
    __('Actions'),
    html_print_select($arrayActions, 'action_id', $actionID, '', __('All'), -1, true, false, true, '', false, 'width:95%')
).'</td>';

$form_filter .= '<td class="w33p">'.html_print_label_input_block(
    __('Field content'),
    html_print_input_text('field_content', $fieldContent, '', 12, 255, true)
).'</td>';

$form_filter .= '<td class="w33p">'.html_print_label_input_block(
    __('Priority'),
    html_print_select(
        get_priorities(),
        'priority',
        $priority,
        '',
        __('All'),
        -1,
        true,
        false,
        true,
        'w100p',
        false,
        'width: 100%;'
    )
).'</td>';

$form_filter .= '</tr>';

$form_filter .= '<tr>';
$ed_list = [];
$alert_status_filter = [];
$alert_status_filter['all_enabled'] = __('All (Enabled)');
$alert_status_filter['all'] = __('All');
$alert_status_filter['fired'] = __('Fired');
$alert_status_filter['notfired'] = __('Not fired');
$alert_status_filter['disabled'] = __('Disabled');
$form_filter .= '<td class="w33p">'.html_print_label_input_block(
    __('Status'),
    html_print_select(
        $alert_status_filter,
        'status_alert',
        $status_alert,
        '',
        '',
        '',
        true,
        false,
        true,
        'w100p',
        false,
        'width: 100%;'
    )
).'</td>';

$sb_list = [];
$sb_list[1] = __('Standby on');
$sb_list[0] = __('Standby off');
$form_filter .= '<td class="w33p">'.html_print_label_input_block(
    __('Standby'),
    html_print_select(
        $sb_list,
        'standby',
        $standby,
        '',
        __('All'),
        -1,
        true,
        false,
        true,
        'w100p',
        false,
        'width: 100%;'
    )
).'</td>';

$own_info = get_user_info($config['id_user']);
if (!$own_info['is_admin'] && !check_acl($config['id_user'], 0, 'AR') && !check_acl($config['id_user'], 0, 'AW')) {
    $return_all_group = false;
} else {
    $return_all_group = true;
}

$form_filter .= '<td class="w33p">'.html_print_label_input_block(
    __('Group'),
    html_print_select_groups(false, 'AR', $return_all_group, 'ag_group', $ag_group, '', '', 0, true, false, true, '', false)
).'</td>';

$form_filter .= '</tr>';

$updateButton = html_print_submit_button(
    __('Update'),
    '',
    false,
    [
        'icon' => 'update',
        'mode' => 'mini',
    ],
    true
);

if (is_metaconsole() === true) {
    $form_filter .= '<tr>';
    $form_filter .= "<td colspan='6' align='right'>";
    $form_filter .= $updateButton;
    $form_filter .= '</td>';
    $form_filter .= '</tr>';
    $form_filter .= '</table>';
} else {
    $form_filter .= '</table>';
    $form_filter .= "<div class='right height_100p'>";
    $form_filter .= $updateButton;
    $form_filter .= '</div>';
}

$form_filter .= '</form>';
if (is_metaconsole() === true) {
    echo '<br>';
}

if (!$id_cluster) {
    ui_toggle(
        $form_filter,
        '<span class="subsection_header_title">'.__('Alert control filter').'</span>',
        __('Toggle filter(s)'),
        'update',
        true,
        false,
        '',
        'white-box-content no_border',
        'filter-datatable-main box-flat white_table_graph fixed_filter_bar  '
    );
} else {
    unset($form_filter);
}


$simple_alerts = [];

$total = 0;
$where = '';

if ($status_alert === 'fired') {
    $where .= ' AND talert_template_modules.times_fired > 0';
}

if ($status_alert === 'notfired') {
    $where .= ' AND talert_template_modules.times_fired = 0';
}

if ($priority != -1 && $priority != '') {
    $where .= ' AND id_alert_template IN (SELECT id FROM talert_templates WHERE priority = '.$priority.')';
}

if (strlen(trim($templateName)) > 0) {
    $where .= " AND id_alert_template IN (SELECT id FROM talert_templates WHERE name LIKE '%".trim($templateName)."%')";
}

if (strlen(trim($fieldContent)) > 0) {
    $where .= " AND id_alert_template IN (SELECT id FROM talert_templates
			WHERE field1 LIKE '%".trim($fieldContent)."%' OR field2 LIKE '%".trim($fieldContent)."%' OR
				field3 LIKE '%".trim($fieldContent)."%' OR
				field2_recovery LIKE '%".trim($fieldContent)."%' OR
				field3_recovery LIKE '%".trim($fieldContent)."%')";
}

if (strlen(trim($moduleName)) > 0) {
    $where .= " AND id_agent_module IN (SELECT id_agente_modulo FROM tagente_modulo WHERE nombre LIKE '%".trim($moduleName)."%')";
}

if (strlen(trim($agentName)) > 0) {
    $where .= " AND id_agent_module IN (SELECT t2.id_agente_modulo
			FROM tagente t1 INNER JOIN tagente_modulo t2 ON t1.id_agente = t2.id_agente
			WHERE t1.alias LIKE '".trim($agentName)."')";
}

if ($actionID != -1 && $actionID != '') {
    $where .= ' AND (talert_template_modules.id IN (SELECT id_alert_template_module FROM talert_template_module_actions WHERE id_alert_action = '.$actionID.') OR talert_template_modules.id IN (SELECT id FROM talert_template_modules ttm WHERE ttm.id_alert_template IN (SELECT tat.id FROM talert_templates tat WHERE tat.id_alert_action = '.$actionID.')))';
}

if ($status_alert === 'disabled') {
    $where .= ' AND talert_template_modules.disabled = 1';
}

if ($status_alert === 'all_enabled') {
    $where .= ' AND talert_template_modules.disabled = 0';
}

if ($standby != -1 && $standby != '') {
    $where .= ' AND talert_template_modules.standby = '.$standby;
}

$id_agents = array_keys($agents);
if (empty($id_agents) === true) {
    $id_agents[0] = 0;
}

$total = agents_get_alerts_simple(
    (empty($agent_id) === false) ? ['0' => $agent_id] : $id_agents,
    false,
    false,
    $where,
    false,
    false,
    $ag_group,
    true
);

if (empty($total) === true) {
    $total = 0;
}

$order = null;

$sortField = get_parameter('sort_field');
$sort = get_parameter('sort', 'none');
$selected = true;
// 'border: 1px solid black;';
$selectDisabledUp = '';
$selectDisabledDown = '';
$selectStandbyUp = '';
$selectStandbyDown = '';
$selectAgentUp = false;
$selectAgentDown = false;
$selectModuleUp = false;
$selectModuleDown = false;
$selectTemplateUp = false;
$selectTemplateDown = false;

switch ($sortField) {
    case 'disabled':
        switch ($sort) {
            case 'down':
                $selectDisabledDown = $selected;
                $order = [
                    'field' => 'disabled',
                    'order' => 'DESC',
                ];
            break;

            default:
            case 'up':
                $selectDisabledUp = $selected;
                $order = [
                    'field' => 'disabled',
                    'order' => 'ASC',
                ];
            break;
        }
    break;

    case 'standby':
        switch ($sort) {
            case 'down':
                $selectStandbyDown = $selected;
                $order = [
                    'field' => 'standby',
                    'order' => 'DESC',
                ];
            break;

            default:
            case 'up':
                $selectStandbyUp = $selected;
                $order = [
                    'field' => 'standby',
                    'order' => 'ASC',
                ];
            break;
        }
    break;

    case 'agent':
        switch ($sort) {
            case 'down':
                $selectAgentDown = $selected;
                $order = [
                    'field' => 'agent_name',
                    'order' => 'DESC',
                ];
            break;

            default:
            case 'up':
                $selectAgentUp = $selected;
                $order = [
                    'field' => 'agent_name',
                    'order' => 'ASC',
                ];
            break;
        }
    break;

    case 'module':
        switch ($sort) {
            case 'down':
                $selectModuleDown = $selected;
                $order = [
                    'field' => 'agent_module_name',
                    'order' => 'DESC',
                ];
            break;

            default:
            case 'up':
                $selectModuleUp = $selected;
                $order = [
                    'field' => 'agent_module_name',
                    'order' => 'ASC',
                ];
            break;
        }
    break;

    case 'template':
        switch ($sort) {
            case 'down':
                $selectTemplateDown = $selected;
                $order = [
                    'field' => 'template_name',
                    'order' => 'DESC',
                ];
            break;

            default:
            case 'up':
                $selectTemplateUp = $selected;
                $order = [
                    'field' => 'template_name',
                    'order' => 'ASC',
                ];
            break;
        }
    break;

    default:
        if (!$id_agente) {
            $selectDisabledUp = '';
            $selectDisabledDown = '';
            $selectStandbyUp = '';
            $selectStandbyDown = '';
            $selectAgentUp = $selected;
            $selectAgentDown = false;
            $selectModuleUp = false;
            $selectModuleDown = false;
            $selectTemplateUp = false;
            $selectTemplateDown = false;
            $order = [
                'field' => 'agent_name',
                'order' => 'ASC',
            ];
        } else {
            $selectDisabledUp = '';
            $selectDisabledDown = '';
            $selectStandbyUp = '';
            $selectStandbyDown = '';
            $selectAgentUp = false;
            $selectAgentDown = false;
            $selectModuleUp = $selected;
            $selectModuleDown = false;
            $selectTemplateUp = false;
            $selectTemplateDown = false;
            $order = [
                'field' => 'agent_module_name',
                'order' => 'ASC',
            ];
        }
    break;
}

$form_params = '&template_name='.$templateName.'&agent_name='.$agentName.'&module_name='.$moduleName.'&action_id='.$actionID.'&field_content='.$fieldContent.'&priority='.$priority.'&enabledisable='.$enabledisable.'&standby='.$standby.'&ag_group='.$ag_group.'&status_alert='.$status_alert;
$sort_params = '&sort_field='.$sortField.'&sort='.$sort;

$offset = (int) get_parameter('offset');
$simple_alerts = agents_get_alerts_simple(
    (empty($agent_id) === false) ? ['0' => $agent_id] : $id_agents,
    false,
    [
        'offset' => $offset,
        'limit'  => $config['block_size'],
        'order'  => $order,
    ],
    $where,
    false,
    false,
    $ag_group
);

if (!$id_agente) {
    $url = 'index.php?sec='.$sec.'&sec2=godmode/alerts/alert_list&tab=list&pure='.$pure.'&offset='.$offset.$form_params;
} else {
    $url = 'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&pure='.$pure.'&tab=alert&id_agente='.$id_agente.'&offset='.$offset.$form_params;
}

// Urls to sort the table.
$url_up_agente = $url.'&sort_field=agent&sort=up&pure='.$pure;
$url_down_agente = $url.'&sort_field=agent&sort=down&pure='.$pure;
$url_up_module = $url.'&sort_field=module&sort=up&pure='.$pure;
$url_down_module = $url.'&sort_field=module&sort=down&pure='.$pure;
$url_up_template = $url.'&sort_field=template&sort=up&pure='.$pure;
$url_down_template = $url.'&sort_field=template&sort=down&pure='.$pure;


$table_alert_list = new stdClass();

if (is_metaconsole()) {
    $table_alert_list->class = 'alert_list databox';
} else {
    $table_alert_list->class = 'info_table';
}

$table_alert_list->width = '100%';
$table_alert_list->cellpadding = 0;
$table_alert_list->cellspacing = 0;
$table_alert_list->size = [];

$table_alert_list->align = [];
$table_alert_list->align[0] = 'left';
$table_alert_list->align[1] = 'left';
$table_alert_list->align[2] = 'left';
$table_alert_list->align[3] = 'left';
$table_alert_list->align[4] = 'left';

$table_alert_list->head = [];

if (! $id_agente) {
    $table_alert_list->style = [];
    $table_alert_list->style[0] = 'font-weight: bold;';
    $table_alert_list->head[0] = __('Agent').ui_get_sorting_arrows($url_up_agente, $url_down_agente, $selectAgentUp, $selectAgentDown);
} else {
    $table_alert_list->head[0] = __('Module').ui_get_sorting_arrows($url_up_module, $url_down_module, $selectModuleUp, $selectModuleDown);
}

$table_alert_list->head[1] = __('Status');
$table_alert_list->head[2] = __('Template').ui_get_sorting_arrows($url_up_template, $url_down_template, $selectTemplateUp, $selectTemplateDown);
$table_alert_list->head[3] = __('Actions');
$table_alert_list->head[4] = "<span title='".__('Operations')."'>".__('Op.').'</span>';

$table_alert_list->headstyle[0] = 'min-width: 200px; width:30%;';
$table_alert_list->headstyle[1] = 'min-width: 50px; width:8%';
$table_alert_list->headstyle[2] = 'min-width: 150px; width:22%;';
$table_alert_list->headstyle[3] = 'min-width: 200px; width:30%;';
$table_alert_list->headstyle[4] = 'min-width: 150px; width:10%;';

$table_alert_list->valign[0] = 'middle';
$table_alert_list->valign[1] = 'middle';
$table_alert_list->valign[2] = 'middle';
$table_alert_list->valign[3] = 'middle';
$table_alert_list->valign[4] = 'middle';

$table_alert_list->cellstyle = [];

$table_alert_list->data = [];

// $url .= $sort_params;
$rowPair = true;
$iterator = 0;

foreach ($simple_alerts as $alert) {
    if ($alert['disabled']) {
        $table_alert_list->rowstyle[$iterator] = 'font-style: italic; color: #aaaaaa;';
        $table_alert_list->style[$iterator][2] = 'font-style: italic; color: #aaaaaa;';
    }

    if ($rowPair) {
        $table_alert_list->rowclass[$iterator] = 'rowPair';
    } else {
        $table_alert_list->rowclass[$iterator] = 'rowOdd';
    }

    $rowPair = !$rowPair;
    $iterator++;

    $data = [];

    if (! $id_agente) {
        $id_agent = modules_get_agentmodule_agent($alert['id_agent_module']);
        $all_groups = agents_get_all_groups_agent($id_agent);

        $data[0] = '';

        if (check_acl_one_of_groups($config['id_user'], $all_groups, 'AW')) {
            $main_tab = 'main';
        } else {
            $main_tab = 'module';
        }

        $data[0] = '<a href="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab='.$main_tab.'&id_agente='.$id_agent.'">';

        if ($alert['disabled']) {
            $data[0] .= '<span class="italic_a">';
        }

        $alias = db_get_value('alias', 'tagente', 'id_agente', $id_agent);
        $data[0] .= $alias;
        if ($alert['disabled']) {
            $data[0] .= '</span>';
        }

        $data[0] .= '</a>';
    } else {
        $all_groups = agents_get_all_groups_agent($id_agente);
    }

    $status = STATUS_ALERT_NOT_FIRED;
    $title = '';

    if ($alert['times_fired'] > 0) {
        $status = STATUS_ALERT_FIRED;
        $title = __('Alert fired').' '.$alert['times_fired'].' '.__('time(s)');
    } else if ($alert['disabled'] > 0) {
        $status = STATUS_ALERT_DISABLED;
        $title = __('Alert disabled');
    } else {
        $status = STATUS_ALERT_NOT_FIRED;
        $title = __('Alert not fired');
    }

    $module_name = modules_get_agentmodule_name($alert['id_agent_module']);
    $data[0] .= ui_print_truncate_text($module_name, 'module_medium', false, true, true, '[&hellip;]', 'display:block;font-weight:normal;');

    $data[1] = ui_print_status_image($status, $title, true);

    $template_group = db_get_value('id_group', 'talert_templates', 'id', $alert['id_alert_template']);

    // The access to the template manage page is necessary have LW permissions on template group
    if (check_acl($config['id_user'], $template_group, 'LW')) {
        $data[2] .= "<a href='index.php?sec=".$sec.'&sec2=godmode/alerts/configure_alert_template&id='.$alert['id_alert_template']."'>";
    }

    $data[2] .= ui_print_truncate_text(
        alerts_get_alert_template_name($alert['id_alert_template']),
        'module_medium',
        false,
        true,
        true,
        '[&hellip;]',
        ''
    );
    $data[2] .= ' <a class="template_details"
		href="'.ui_get_full_url(false, false, false, false).'ajax.php?page=godmode/alerts/alert_templates&get_template_tooltip=1&id_template='.$alert['id_alert_template'].'">';
        $data[2] .= html_print_image(
            'images/details.svg',
            true,
            [
                'id'    => 'template-details-'.$alert['id_alert_template'],
                'class' => 'img_help main_menu_icon invert_filter',
            ]
        );
    $data[2] .= '</a> ';

    if (check_acl($config['id_user'], $template_group, 'LW') || check_acl($config['id_user'], $template_group, 'LM')) {
        $data[2] .= '</a>';
    }

    $actions = alerts_get_alert_agent_module_actions($alert['id']);

    $data[3] = "<table class='w100p'>";
    // Get and show default actions for this alert
    $default_action = db_get_sql(
        'SELECT id_alert_action
		FROM talert_templates
		WHERE id = '.$alert['id_alert_template']
    );
    if ($default_action != '') {
        $data[3] .= "<tr><td colspan='2'><ul class='action_list'><li>";
        $data[3] .= db_get_sql("SELECT name FROM talert_actions WHERE id = $default_action").' <em>('.__('Default').')</em>';
        $data[3] .= '</li></ul></td>';
        $data[3] .= '</tr>';
    }

    foreach ($actions as $action_id => $action) {
        $data[3] .= '<tr class="alert_action_list">';
            $data[3] .= '<td>';
                $data[3] .= '<ul class="action_list inline_line">';
                $data[3] .= '<li class="inline_line">';
        if ($alert['disabled']) {
            $data[3] .= '<font class="action_name italic_a">';
        } else {
            $data[3] .= '<font class="action_name">';
        }

                $data[3] .= ui_print_truncate_text($action['name'], (GENERIC_SIZE_TEXT + 20), false);
                $data[3] .= ' <em>(';
        if ($action['fires_min'] == $action['fires_max']) {
            if ($action['fires_min'] == 0) {
                $data[3] .= __('Always');
            } else {
                $data[3] .= __('On').' '.$action['fires_min'];
            }
        } else if ($action['fires_min'] < $action['fires_max']) {
            if ($action['fires_min'] == 0) {
                $data[3] .= __('Until').' '.$action['fires_max'];
            } else {
                $data[3] .= __('From').' '.$action['fires_min'].' '.__('to').' '.$action['fires_max'];
            }
        } else {
            $data[3] .= __('From').' '.$action['fires_min'];
        }

        if ($action['module_action_threshold'] != 0) {
            $data[3] .= ' '.__('Threshold').' '.human_time_description_alerts($action['module_action_threshold'], true, 'tiny');
        }

                $data[3] .= ')</em>';
                $data[3] .= '</font>';
                $data[3] .= '</li>';
                $data[3] .= '</ul>';

        $data[3] .= '</td>';

        $data[3] .= '<td class="flex_center">';
        $data[3] .= ui_print_help_tip(__('The default actions will be executed every time that the alert is fired and no other action is executed'), true);
        // Is possible manage actions if have LW permissions in the agent group of the alert module
        if (check_acl_one_of_groups($config['id_user'], $all_groups, 'LW')) {
            $data[3] .= '<form method="post" action="'.$url.'" class="delete_link display_in">';
            $data[3] .= html_print_input_image(
                'delete',
                'images/delete.svg',
                1,
                'padding:0px; margin-left:5px; margin-right:5px; width: 22px;',
                true,
                [
                    'title' => __('Delete action'),
                    'class' => 'main_menu_icon invert_filter',
                ]
            );
            $data[3] .= html_print_input_hidden('delete_action', 1, true);
            $data[3] .= html_print_input_hidden('id_alert', $alert['id'], true);
            $data[3] .= html_print_input_hidden('id_action', $action_id, true);
            $data[3] .= '</form>';
            $data[3] .= html_print_input_image(
                'update_action',
                'images/edit.svg',
                1,
                'padding:0px;',
                true,
                [
                    'title'   => __('Update action'),
                    'class'   => 'main_menu_icon invert_filter',
                    'onclick' => 'show_display_update_action(\''.$action['id'].'\',\''.$alert['id'].'\',\''.$alert['id_agent_module'].'\',\''.$action_id.'\',\''.$alert['id_agent_module'].'\')',
                ]
            );
            $data[3] .= html_print_input_hidden('id_agent_module', $alert['id_agent_module'], true);
        }

            $data[3] .= '</td>';
        $data[3] .= '</tr>';
    }

    $data[3] .= '<div id="update_action-div-'.$alert['id'].'" class="invisible">';
    $data[3] .= '</div>';
    $data[3] .= '</table>';
    // Is possible manage actions if have LW permissions in the agent group of the alert module
    if (check_acl_one_of_groups($config['id_user'], $all_groups, 'LW') || check_acl($config['id_user'], $template_group, 'LM')) {
        $own_info = get_user_info($config['id_user']);
        if (check_acl($config['id_user'], $template_group, 'LW')) {
            $own_groups = users_get_groups($config['id_user'], 'LW', true);
        } else if (check_acl($config['id_user'], $template_group, 'LM')) {
            $own_groups = users_get_groups($config['id_user'], 'LM', true);
        }

        $filter_groups = '';
        $filter_groups = implode(',', array_keys($own_groups));
        if ($filter_groups != null) {
            $actions = alerts_get_alert_actions_filter(true, 'id_group IN ('.$filter_groups.')');
        }

        $data[3] .= '<div id="add_action-div-'.$alert['id'].'" class="invisible">';
            $data[3] .= '<form id="add_action_form-'.$alert['id'].'" method="post" style="height:85%;">';
                $data[3] .= '<table class="w100p bg_color222 filter-table-adv">';
                    $data[3] .= html_print_input_hidden('add_action', 1, true);
                    $data[3] .= html_print_input_hidden('id_alert_module', $alert['id'], true);

        if (! $id_agente) {
            $data[3] .= '<tr class="datos2">';
                $data[3] .= '<td class="w50p">'.html_print_label_input_block(
                    __('Agent'),
                    ui_print_truncate_text($alias, 'agent_small', false, true, true, '[&hellip;]')
                ).'</td>';
                $data[3] .= '<td class="w50p">'.html_print_label_input_block(
                    __('Module'),
                    ui_print_truncate_text($module_name, 'module_small', false, true, true, '[&hellip;]')
                ).'</td>';
            $data[3] .= '</tr>';
        }

                    $data[3] .= '<tr class="datos2">';
                        $data[3] .= '<td class="w50p">'.html_print_label_input_block(
                            __('Action'),
                            html_print_select(
                                $actions,
                                'action_select',
                                '',
                                '',
                                __('None'),
                                0,
                                true,
                                false,
                                true,
                                '',
                                false,
                                'width:100%'
                            )
                        ).'</td>';

                        $data[3] .= '<td class="w50p">'.html_print_label_input_block(
                            __('Number of alerts match from'),
                            '<div class="inline">'.html_print_input_text(
                                'fires_min',
                                0,
                                '',
                                4,
                                10,
                                true,
                                false,
                                false,
                                '',
                                'w40p'
                            ).' '.__('to').' '.html_print_input_text(
                                'fires_max',
                                0,
                                '',
                                4,
                                10,
                                true,
                                false,
                                false,
                                '',
                                'w40p'
                            ).'</div>'
                        ).'</td>';
                    $data[3] .= '</tr>';
                    $data[3] .= '<tr class="datos2">';
                        $data[3] .= '<td class="w50p">'.html_print_label_input_block(
                            __('Threshold'),
                            html_print_extended_select_for_time(
                                'module_action_threshold',
                                0,
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
                            )
                        ).'</td>';
                    $data[3] .= '</tr>';
                $data[3] .= '</table>';
                $data[3] .= html_print_submit_button(
                    __('Add'),
                    'addbutton',
                    false,
                    [
                        'icon'  => 'next',
                        'class' => 'mini float-right',
                    ],
                    true
                );
            $data[3] .= '</form>';
        $data[3] .= '</div>';
    }

    $table_alert_list->cellclass[] = [4 => 'table_action_buttons'];
    $data[4] = '<form class="disable_alert_form display_in" action="'.$url.'" method="post" >';
    if ($alert['disabled']) {
        $data[4] .= html_print_input_image(
            'enable',
            'images/lightbulb_off.png',
            1,
            'padding:0px; width: 22px; height: 22px;',
            true,
            ['class' => 'filter_none main_menu_icon']
        );
        $data[4] .= html_print_input_hidden('enable_alert', 1, true);
    } else {
        $data[4] .= html_print_input_image(
            'disable',
            'images/lightbulb.png',
            1,
            'padding:0px; width: 22px; height: 22px;',
            true,
            ['class' => 'main_menu_icon']
        );
        $data[4] .= html_print_input_hidden('disable_alert', 1, true);
    }

    $data[4] .= html_print_input_hidden('id_alert', $alert['id'], true);
    $data[4] .= '</form>';

    // To manage alert is necessary LW permissions in the agent group
    if (check_acl_one_of_groups($config['id_user'], $all_groups, 'LW')) {
        $data[4] .= '<form class="standby_alert_form display_in" action="'.$url.'" method="post">';
        if (!$alert['standby']) {
            $data[4] .= html_print_input_image(
                'standby_off',
                'images/bell.png',
                1,
                'padding:0px; width: 22px; height: 22px;',
                true,
                ['class' => 'invert_filter main_menu_icon']
            );
            $data[4] .= html_print_input_hidden('standbyon_alert', 1, true);
        } else {
            $data[4] .= html_print_input_image(
                'standby_on',
                'images/bell_pause.png',
                1,
                'padding:0px; width: 22px; height: 22px;',
                true,
                ['class' => 'invert_filter main_menu_icon']
            );
            $data[4] .= html_print_input_hidden('standbyoff_alert', 1, true);
        }

        $data[4] .= html_print_input_hidden('id_alert', $alert['id'], true);
        $data[4] .= '</form>';
    }

    // To access to policy page is necessary have AW permissions in the agent
    if (check_acl_one_of_groups($config['id_user'], $all_groups, 'AW')) {
        if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) {
            $policyInfo = policies_is_alert_in_policy2($alert['id'], false);
            $module_linked = policies_is_module_linked($alert['id_agent_module']);
            if ((is_array($policyInfo) === false && $module_linked === false)
                || (is_array($policyInfo) === false && $module_linked === '1')
            ) {
                $data[$index['policy']] = '';
            } else {
                $module_linked = policies_is_module_linked($alert['id_agent_module']);
                if ($module_linked === '0') {
                    $img = 'images/unlinkpolicy.png';
                } else {
                    $img = 'images/policy@svg.svg';
                }

                $data[1] .= '&nbsp;&nbsp;<a href="?sec=gmodules&sec2=enterprise/godmode/policies/policies&pure='.$pure.'&id='.$policyInfo['id'].'">'.html_print_image($img, true, ['title' => $policyInfo['name'], 'class' => 'invert_filter main_menu_icon']).'</a>';
            }
        }
    }

    // To manage alert is necessary LW permissions in the agent group
    if (check_acl_one_of_groups($config['id_user'], $all_groups, 'LW')) {
        $data[4] .= '<form class="delete_alert_form display_in" action="'.$url.'" method="post" >';
        $is_cluster = (bool) get_parameter('id_cluster');
        if (!$is_cluster) {
            if ($alert['disabled']) {
                $data[4] .= html_print_image(
                    'images/add.disabled.png',
                    true,
                    [
                        'title' => __('Add action'),
                        'class' => 'invert_filter main_menu_icon',
                    ]
                );
            } else {
                if ((int) $alert['id_policy_alerts'] === 0 || $module_linked === '0') {
                    $data[4] .= '<a href="javascript:show_add_action(\''.$alert['id'].'\');">';
                    $data[4] .= html_print_image(
                        'images/plus-black.svg',
                        true,
                        [
                            'title' => __('Add action'),
                            'class' => 'invert_filter main_menu_icon',
                            'style' => 'margin-bottom: 12px;',
                        ]
                    );
                    $data[4] .= '</a>';
                }
            }
        }

        $data[4] .= html_print_input_image(
            'delete',
            'images/delete.svg',
            1,
            '',
            true,
            [
                'title' => __('Delete'),
                'class' => 'invert_filter main_menu_icon',
            ]
        );
        $data[4] .= html_print_input_hidden('delete_alert', 1, true);
        $data[4] .= html_print_input_hidden('id_alert', $alert['id'], true);
        $data[4] .= '</form>';

        if ($is_cluster) {
            $data[4] .= '<form class="view_alert_form display_in" method="post">';

            $data[4] .= html_print_input_image(
                'update',
                'images/builder@svg.svg',
                1,
                '',
                true,
                [
                    'title' => __('Update'),
                    'class' => 'invert_filter main_menu_icon',
                ]
            );
            $data[4] .= html_print_input_hidden('upd_alert', 1, true);
            $data[4] .= html_print_input_hidden('id_alert', $alert['id'], true);

            $data[4] .= '</form>';
        }
    }

    if (check_acl_one_of_groups($config['id_user'], $all_groups, 'LM')) {
        $data[4] .= '<form class="view_alert_form display_in" method="post" action="index.php?sec=galertas&sec2=godmode/alerts/alert_view">';
        $data[4] .= html_print_input_image(
            'view_alert',
            'images/details.svg',
            1,
            '',
            true,
            [
                'title' => __('View alert advanced details'),
                'class' => 'invert_filter main_menu_icon',
            ]
        );
        $data[4] .= html_print_input_hidden('id_alert', $alert['id'], true);
        $data[4] .= '</form>';
    }

    array_push($table_alert_list->data, $data);
}

$pagination = '';
if (isset($data)) {
    html_print_table($table_alert_list);
    if ($id_agente) {
        $pagination .= ui_pagination($total, 'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=alert&id_agente='.$id_agente.$form_params.$sort_params, 0, 0, true, 'offset', false, '');
    } else {
        $pagination .= ui_pagination($total, 'index.php?sec='.$sec.'&sec2=godmode/alerts/alert_list'.$form_params.$sort_params, 0, 0, true, 'offset', false, '');
    }
} else {
    ui_print_info_message(['no_close' => true, 'message' => __('No alerts defined') ]);
}

// Create alert button
// $dont_display_alert_create_bttn is setted in configurar_agente.php in order to not display create button
$display_create = true;
if (isset($dont_display_alert_create_bttn)) {
    if ($dont_display_alert_create_bttn) {
        $display_create = false;
    }
}

if ($display_create && (check_acl($config['id_user'], 0, 'LW') || check_acl($config['id_user'], $template_group, 'LM')) && !$id_cluster) {
    echo '<form method="post" action="index.php?sec='.$sec.'&sec2=godmode/alerts/alert_list&tab=builder&pure='.$pure.'">';
    $actionButtons = html_print_submit_button(
        __('Create'),
        'crtbtn',
        false,
        ['icon' => 'next'],
        true
    );
    html_print_action_buttons($actionButtons, ['right_content' => $pagination]);
    echo '</form>';
}

ui_require_css_file('cluetip', 'include/styles/js/');
ui_require_jquery_file('cluetip');
ui_require_jquery_file('pandora.controls');
ui_require_jquery_file('bgiframe');
?>
<script type="text/javascript">
/* <![CDATA[ */
$(document).ready (function () {
<?php
if (! $id_agente) {
    ?>
    $("#id_group").pandoraSelectGroupAgent ({
        callbackBefore: function () {
            $select = $("#id_agent_module").disable ();
            $select.siblings ("span#latest_value").hide ();
            $("option[value!=0]", $select).remove ();
            return true;
        }
    });
    
    //$("#id_agent").pandoraSelectAgentModule ();
    <?php
}
?>
    $("a.template_details").cluetip ({
            arrows: true,
            attribute: 'href',
            cluetipClass: 'default'
        }).click (function () {
            return false;
        });
    
    $("#tgl_alert_control").click (function () {
        $("#alert_control").toggle ();
        return false;
    });
    
    $("input[name=disable]").attr ("title", "<?php echo __('Disable'); ?>")
        .hover (function () {
                $(this).attr ("src", 
                <?php
                echo '"'.html_print_image(
                    'images/lightbulb_off.png',
                    true,
                    ['class' => 'filter_none'],
                    true
                ).'"';
                ?>
                     );
            },
            function () {
                $(this).attr ("src", 
                <?php
                echo '"'.html_print_image(
                    'images/lightbulb.png',
                    true,
                    ['class' => 'invert_filter'],
                    true
                ).'"';
                ?>
                     );
            }
        );
    
    $("input[name=enable]").attr ("title", "<?php echo __('Enable'); ?>")
        .hover (function () {
                $(this).attr ("src", 
                <?php
                echo '"'.html_print_image(
                    'images/lightbulb.png',
                    true,
                    ['class' => 'invert_filter'],
                    true
                ).'"';
                ?>
                     );
            },
            function () {
                $(this).attr ("src", 
                <?php
                echo '"'.html_print_image(
                    'images/lightbulb_off.png',
                    true,
                    ['class' => 'filter_none'],
                    true
                ).'"';
                ?>
                     );
            }
        );
    
    $("input[name=standby_on]").attr ("title", "<?php echo __('Set off standby'); ?>")
        .hover (function () {
                $(this).attr ("src", 
                <?php
                echo '"'.html_print_image(
                    'images/bell.png',
                    true,
                    ['class' => 'invert_filter'],
                    true
                ).'"';
                ?>
                     );
            },
            function () {
                $(this).attr ("src", 
                <?php
                echo '"'.html_print_image(
                    'images/bell_pause.png',
                    true,
                    ['class' => 'invert_filter'],
                    true
                ).'"';
                ?>
                     );
            }
        );
    
    $("input[name=standby_off]").attr ("title", "<?php echo __('Set standby'); ?>")
        .hover (function () {
                $(this).attr ("src", 
                <?php
                echo '"'.html_print_image(
                    'images/bell_pause.png',
                    true,
                    ['class' => 'invert_filter'],
                    true
                ).'"';
                ?>
                     );
            },
            function () {
                $(this).attr ("src", 
                <?php
                echo '"'.html_print_image(
                    'images/bell.png',
                    true,
                    ['class' => 'invert_filter'],
                    true
                ).'"';
                ?>
                     );
            }
        );
    
    $("form.disable_alert_form").submit (function () {
        return true;
    });
    
    $("form.delete_link, form.delete_alert_form").submit (function () {
        if (! confirm ("<?php echo __('Are you sure?'); ?>"))
            return false;
        return true;
    });
});

function show_advance_options_action(id_alert) {
    $(".link_show_advance_options_" + id_alert).hide();
    $(".advance_options_" + id_alert).show();
}

function show_add_action(id_alert) {
    $("#add_action-div-" + id_alert).hide ()
        .dialog ({
            title: '<?php echo __('Add action'); ?>',
            modal: true,
            overlay: {
                opacity: 0.5,
                background: "black"
            },
            open: function() {
                $(`#add_action-div-${id_alert}`).css('overflow', 'initial');
                $("select[id^='action_select'], select[id^='action_select']").select2({
                    tags: true,
                    dropdownParent: $("#add_action-div-" + id_alert)
                });
            },
            width: 665,
            height: 300
        })
        .show ();
}

function show_display_update_action(id_module_action, alert_id, alert_id_agent_module, action_id) {
    var params = [];
    params.push("show_update_action_menu=1");
    params.push("id_agent_module=" + alert_id_agent_module);
    params.push("id_module_action=" + id_module_action);
    params.push("id_alert=" + alert_id);
    params.push("id_action=" + action_id);
    params.push("page=include/ajax/alert_list.ajax");
    jQuery.ajax ({
        data: params.join ("&"),
        type: 'POST',
        url: action="<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
        success: function (data) {
            $(`#update_action-div-${alert_id}`).html (data);
            $(`#update_action-div-${alert_id}`).hide ()
                .dialog ({
                    resizable: true,
                    draggable: true,
                    title: '<?php echo __('Update action'); ?>',
                    modal: true,
                    overlay: {
                        opacity: 0.5,
                        background: "black"
                    },
                    open: function() {
                        $(`#update_action-div-${alert_id}`).css('overflow', 'hidden');
                        $(`#action_select_ajax-${alert_id}`).select2({
                            tags: true,
                            dropdownParent: $(`#update_action-div-${alert_id}`)
                        });
                    },
                    width: 600,
                    height: 350
                })
                .show ();
        }
    });
}

/* ]]> */
</script>
