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

global $config;

// Login check
check_login ();

/* Check if this page is included from a agent edition */

if (! check_acl ($config['id_user'], 0, "LW") && 
	! check_acl ($config['id_user'], 0, "AD") && 
		! check_acl ($config['id_user'], 0, "LM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Alert Management");
	require ("general/noaccess.php");
	exit;
}

require_once ($config['homedir'].'/include/functions_agents.php');
require_once ($config['homedir'].'/include/functions_modules.php');
require_once ($config['homedir'].'/include/functions_users.php');

$pure = get_parameter('pure', 0);

if (defined('METACONSOLE')) {
	$sec = 'advanced';
}
else {
	$sec = 'galertas';
}

if ($id_agente) {
	$sec2 = 'godmode/agentes/configurar_agente&tab=alert&id_agente=' . $id_agente;
}
else {
	$sec2 = 'godmode/alerts/alert_list';
}

// Table for filter controls
$form_filter = '<form method="post" action="index.php?sec=' . $sec . '&amp;sec2=' . $sec2 . '&amp;refr=' . ((int)get_parameter('refr', 0)) .
					'&amp;pure='.$config["pure"].'">';
$form_filter .= "<input type='hidden' name='search' value='1' />";
$form_filter .= '<table style="width: 100%;" cellpadding="0" cellspacing="0" class="databox filters">';
$form_filter .= "<tr>";
$form_filter .= "<td style='font-weight: bold;'>" . __('Template name') . "</td><td>";
$form_filter .= html_print_input_text ('template_name', $templateName, '', 12, 255, true);
$form_filter .= "</td>";
$temp = agents_get_agents();
$arrayAgents = array();

# Avoid empty arrays, warning messages are UGLY !
if ($temp) {
	foreach ($temp as $agentElement) {
		$arrayAgents[$agentElement['id_agente']] = $agentElement['nombre'];
	}
}

$form_filter .= "<td style='font-weight: bold;'>".__('Agents')."</td><td>";

$params = array();
$params['return'] = true;
$params['show_helptip'] = true;
$params['input_name'] = 'agent_name';
$params['value'] = $agentName;
$params['size'] = 24;
$params['metaconsole_enabled'] = false;

$form_filter .=  ui_print_agent_autocomplete_input($params);


$form_filter .= "</td>";

$form_filter .= "<td style='font-weight: bold;'>".__('Module name')."</td><td>";
$form_filter .= html_print_input_text ('module_name', $moduleName, '', 12, 255, true);
$form_filter .= "</td>";
$form_filter .= "</tr>";

$all_groups = db_get_value('is_admin', 'tusuario', 'id_user', $config['id_user']);

if (check_acl ($config['id_user'], 0, "AD"))
	$groups_user = users_get_groups($config['id_user'], 'AD', $all_groups);
elseif (check_acl ($config['id_user'], 0, "LW"))
	$groups_user = users_get_groups($config['id_user'], 'LW', $all_groups);
elseif (check_acl ($config['id_user'], 0, "LM"))
	$groups_user = users_get_groups($config['id_user'], 'LM', $all_groups);
if ($groups_user === false) {
	$groups_user = array();
}
$groups_id = implode(',', array_keys($groups_user));

$form_filter .= "<tr>";
switch ($config["dbtype"]) {
	case "mysql":
	case "postgresql":
		$temp = db_get_all_rows_sql("SELECT id, name FROM talert_actions WHERE id_group IN ($groups_id);");
		break;
	case "oracle":
		$temp = db_get_all_rows_sql("SELECT id, name FROM talert_actions WHERE id_group IN ($groups_id)");
		break;
}

$arrayActions = array();
if (is_array($temp)) {
	foreach ($temp as $actionElement) {
		$arrayActions[$actionElement['id']] = $actionElement['name'];
	}
}
$form_filter .= "<td style='font-weight: bold;'>".__('Actions')."</td><td>";
$form_filter .= html_print_select ($arrayActions, "action_id", $actionID,  '', __('All'), -1, true);
$form_filter .= "</td>";
$form_filter .= "<td style='font-weight: bold;'>".__('Field content')."</td><td>";
$form_filter .= html_print_input_text ('field_content', $fieldContent, '', 12, 255, true);
$form_filter .= "</td>";
$form_filter .= "<td style='font-weight: bold;'>".__('Priority')."</td><td>";
$form_filter .= html_print_select (get_priorities (), 'priority',$priority, '', __('All'), -1, true);
$form_filter .= "</td style='font-weight: bold;'>";
$form_filter .= "</tr>";

$form_filter .= "<tr>";
$form_filter .= "<td style='font-weight: bold;'>".__('Enabled / Disabled')."</td><td>";
$ed_list = array ();
$ed_list[0] = __('Enable');
$ed_list[1] = __('Disable');
$form_filter .= html_print_select ($ed_list, 'enabledisable', $enabledisable, '', __('All'), -1, true);
$form_filter .= "</td><td style='font-weight: bold;'>".__('Standby')."</td><td>";
$sb_list = array ();
$sb_list[1] = __('Standby on');
$sb_list[0] = __('Standby off');
$form_filter .= html_print_select ($sb_list, 'standby', $standby, '', __('All'), -1, true);
$form_filter .= "</td></tr>";
if ( defined("METACONSOLE") ) {
	$form_filter .= "<tr>";
	$form_filter .= "<td colspan='6' align='right'>";
	$form_filter .= html_print_submit_button (__('Update'), '', false, 'class="sub upd"', true);
	$form_filter .= "</td>";
	$form_filter .= "</tr>";
	$form_filter .= "</table>";
}
else {
	$form_filter .= "</table>";
	$form_filter .= "<div  style='text-align:right; height:100%;'>";
	$form_filter .= html_print_submit_button (__('Update'), '', false, 'class="sub upd"', true);
	$form_filter .= "</div>";
}

$form_filter .= "</form>";
if ( defined("METACONSOLE"))
	echo "<br>";

ui_toggle($form_filter, __('Alert control filter'), __('Toggle filter(s)'));

$simple_alerts = array();

$total = 0;
$where = '';

if ($searchFlag) {
	if ($priority != -1 && $priority != '')
		$where .= " AND id_alert_template IN (SELECT id FROM talert_templates WHERE priority = " . $priority . ")";
	if (strlen(trim($templateName)) > 0)
		$where .= " AND id_alert_template IN (SELECT id FROM talert_templates WHERE name LIKE '%" . trim($templateName) . "%')";
	if (strlen(trim($fieldContent)) > 0)
		$where .= " AND id_alert_template IN (SELECT id FROM talert_templates
			WHERE field1 LIKE '%" . trim($fieldContent) . "%' OR field2 LIKE '%" . trim($fieldContent) . "%' OR
				field3 LIKE '%" . trim($fieldContent) . "%' OR
				field2_recovery LIKE '%" . trim($fieldContent) . "%' OR
				field3_recovery LIKE '%" . trim($fieldContent) . "%')";
	if (strlen(trim($moduleName)) > 0)
		$where .= " AND id_agent_module IN (SELECT id_agente_modulo FROM tagente_modulo WHERE nombre LIKE '%" . trim($moduleName) . "%')";
	//if ($agentID != -1)
		//$where .= " AND id_agent_module IN (SELECT id_agente_modulo FROM tagente_modulo WHERE id_agente = " . $agentID . ")";
	if (strlen(trim($agentName)) > 0) {
		
		switch ($config["dbtype"]) {
			case "mysql":
			case "postgresql":
				$where .= " AND id_agent_module IN (SELECT t2.id_agente_modulo
					FROM tagente t1 INNER JOIN tagente_modulo t2 ON t1.id_agente = t2.id_agente
					WHERE t1.alias LIKE '" . trim($agentName) . "')";
				break;
			case "oracle":
				$where .= " AND id_agent_module IN (SELECT t2.id_agente_modulo
					FROM tagente t1 INNER JOIN tagente_modulo t2 ON t1.id_agente = t2.id_agente
					WHERE t1.alias LIKE '" . trim($agentName) . "')";
				break;
		}
	}
	if ($actionID != -1 && $actionID != '')
		$where .= " AND talert_template_modules.id IN (SELECT id_alert_template_module FROM talert_template_module_actions WHERE id_alert_action = " . $actionID . ")";
	if ($enabledisable != -1 && $enabledisable != '')
		$where .= " AND talert_template_modules.disabled =" . $enabledisable;
	if ($standby != -1 && $standby != '')
		$where .= " AND talert_template_modules.standby = " . $standby;
}

switch ($config["dbtype"]) {
	case "mysql":
	case "postgresql":
		$id_agents = array_keys ($agents);
		break;
	case "oracle":
		$id_agents = false;
		break;
}



$total = agents_get_alerts_simple ($id_agents, false,
	false, $where, false, false, false, true);

if (empty($total)) $total = 0;

$order = null;

$sortField = get_parameter('sort_field');
$sort = get_parameter('sort', 'none');
$selected = 'border: 1px solid black;';
$selectDisabledUp = '';
$selectDisabledDown = '';
$selectStandbyUp = '';
$selectStandbyDown = '';
$selectAgentUp = '';
$selectAgentDown = '';
$selectModuleUp = '';
$selectModuleDown = '';
$selectTemplateUp = '';
$selectTemplateDown = '';

switch ($sortField) {
	case 'disabled':
		switch ($sort) {
			case 'up':
				$selectDisabledUp = $selected;
				$order = array('field' => 'disabled', 'order' => 'ASC');
				break;
			case 'down':
				$selectDisabledDown = $selected;
				$order = array('field' => 'disabled', 'order' => 'DESC');
				break;
		}
		break;
	case 'standby':
		switch ($sort) {
			case 'up':
				$selectStandbyUp = $selected;
				$order = array('field' => 'standby', 'order' => 'ASC');
				break;
			case 'down':
				$selectStandbyDown = $selected;
				$order = array('field' => 'standby', 'order' => 'DESC');
				break;
		}
		break;
	case 'agent':
		switch ($sort) {
			case 'up':
				$selectAgentUp = $selected;
				$order = array('field' => 'agent_name', 'order' => 'ASC');
				break;
			case 'down':
				$selectAgentDown = $selected;
				$order = array('field' => 'agent_name', 'order' => 'DESC');
				break;
		}
		break;
	case 'module':
		switch ($sort) {
			case 'up':
				$selectModuleUp = $selected;
				$order = array('field' => 'agent_module_name', 'order' => 'ASC');
				break;
			case 'down':
				$selectModuleDown = $selected;
				$order = array('field' => 'agent_module_name', 'order' => 'DESC');
				break;
		}
		break;
	case 'template':
		switch ($sort) {
			case 'up':
				$selectTemplateUp = $selected;
				$order = array('field' => 'template_name', 'order' => 'ASC');
				break;
			case 'down':
				$selectTemplateDown = $selected;
				$order = array('field' => 'template_name', 'order' => 'DESC');
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
			$selectAgentDown = '';
			$selectModuleUp = '';
			$selectModuleDown = '';
			$selectTemplateUp = '';
			$selectTemplateDown = '';
			$order = array('field' => 'agent_name', 'order' => 'ASC');
		}
		else {
			$selectDisabledUp = '';
			$selectDisabledDown = '';
			$selectStandbyUp = '';
			$selectStandbyDown = '';
			$selectAgentUp = '';
			$selectAgentDown = '';
			$selectModuleUp = $selected;
			$selectModuleDown = '';
			$selectTemplateUp = '';
			$selectTemplateDown = '';
			$order = array('field' => 'agent_module_name', 'order' => 'ASC');
		}
		break;
}

$form_params = '&template_name=' . $templateName . '&agent_name=' . $agentName . '&module_name=' . $moduleName . '&action_id=' . $actionID . '&field_content=' . $fieldContent. '&priority=' . $priority . '&enabledisable=' . $enabledisable . '&standby=' . $standby;
$sort_params = '&sort_field=' . $sortField . '&sort=' . $sort;

if ($id_agente) {
	ui_pagination ($total, 'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=alert&id_agente=' . $id_agente . $form_params . $sort_params);
}
else {
	ui_pagination ($total, 'index.php?sec='.$sec.'&sec2=godmode/alerts/alert_list' . $form_params . $sort_params);
}

$offset = (int) get_parameter('offset');
$simple_alerts = agents_get_alerts_simple ($id_agents, false,
	array ('offset' => $offset,
		'limit' => $config['block_size'], 'order' => $order), $where, false);

if (!$id_agente) {
	$url = 'index.php?sec='.$sec.'&sec2=godmode/alerts/alert_list&tab=list&pure='.$pure.'&offset=' . $offset . $form_params;
}
else {
	$url = 'index.php?sec='.$sec.'&sec2=godmode/agentes/configurar_agente&pure='.$pure.'&tab=alert&id_agente=' . $id_agente . '&offset=' . $offset . $form_params;
}

$table = new stdClass();

if ( is_metaconsole() )
	$table->class = 'alert_list databox';
else
	$table->class = 'databox data';

$table->width = '100%';
$table->cellpadding = 0;
$table->cellspacing = 0;
$table->size = array ();

$table->align = array ();
$table->align[0] = 'left';
$table->align[1] = 'left';
$table->align[2] = 'left';
$table->align[3] = 'left';
$table->align[4] = 'left';

$table->head = array ();

if (! $id_agente) {
	$table->style = array ();
	$table->style[0] = 'font-weight: bold;';
	$table->head[0] = __('Agent') . '&nbsp;' .
		'<a href="' . $url . '&sort_field=agent&sort=up&pure='.$pure.'">' . html_print_image("images/sort_up.png", true, array("style" => $selectAgentUp)) . '</a>' .
		'<a href="' . $url . '&sort_field=agent&sort=down&pure='.$pure.'">' . html_print_image("images/sort_down.png", true, array("style" => $selectAgentDown)) . '</a>';
	$table->size[0] = '4%';
	$table->size[1] = '8%';
	$table->size[2] = '8%';
	$table->size[3] = '4%';
	$table->size[4] = '4%';

/*	if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) {
		$table->size[4] = '8%';
	}*/
}
else {
	$table->head[0] = __('Module') . '&nbsp;' .
		'<a href="' . $url . '&sort_field=module&sort=up&pure='.$pure.'">' . html_print_image("images/sort_up.png", true, array("style" => $selectModuleUp)) . '</a>' .
		'<a href="' . $url . '&sort_field=module&sort=down&pure='.$pure.'">' . html_print_image("images/sort_down.png", true, array("style" => $selectModuleDown)) . '</a>';
	/* Different sizes or the layout screws up */
	$table->size[0] = '0%';
	$table->size[1] = '10%';
	$table->size[2] = '30%';
/*	if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) {
		$table->size[4] = '25%';
	}  */
	$table->size[3] = '1%';
	$table->size[4] = '1%';
}

$table->head[1] = __('Template') . '&nbsp;' .
	'<a href="' . $url . '&sort_field=template&sort=up&pure='.$pure.'">' . html_print_image("images/sort_up.png", true, array("style" => $selectTemplateUp)) . '</a>' .
	'<a href="' . $url . '&sort_field=template&sort=down&pure='.$pure.'">' . html_print_image("images/sort_down.png", true, array("style" => $selectTemplateDown)) . '</a>';
$table->head[2] = __('Actions');
$table->head[3] = __('Status');
$table->head[4] = "<span title='" . __('Operations') . "'>" . __('Op.') . "</span>";

$table->valign[0] = 'middle';
$table->valign[1] = 'middle';
$table->valign[2] = 'middle';
$table->valign[3] = 'middle';
$table->valign[4] = 'middle';

$table->style[4] = "min-width:80px";

$table->data = array ();

$url .= $sort_params;

$rowPair = true;
$iterator = 0;

foreach ($simple_alerts as $alert) {
	if ($alert['disabled']) {
		$table->rowstyle[$iterator] = 'font-style: italic; color: #aaaaaa;';
		$table->style[$iterator][1] = 'font-style: italic; color: #aaaaaa;';
	}
	
	if ($rowPair)
		$table->rowclass[$iterator] = 'rowPair';
	else
		$table->rowclass[$iterator] = 'rowOdd';
	$rowPair = !$rowPair;
	$iterator++;
	
	$data = array ();
	
	if (! $id_agente) {
		$id_agent = modules_get_agentmodule_agent ($alert['id_agent_module']);
		
		$agent_group = db_get_value('id_grupo', 'tagente', 'id_agente', $id_agent);
		
		$data[0] = '';
		
		if (check_acl ($config['id_user'], $agent_group, "AW")) {
			$main_tab = 'main';
		}
		else {
			$main_tab = 'module';
		}
		
		$data[0] = '<a href="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab='.$main_tab.'&id_agente='.$id_agent.'">';
		
		if ($alert['disabled'])
			$data[0] .= '<span style="font-style: italic; color: #aaaaaa;">';
		$alias = db_get_value ("alias","tagente","id_agente",$id_agent);
		$data[0] .= $alias;
		if ($alert['disabled'])
			$data[0] .= '</span>';
		
		$data[0] .= '</a>';
	}
	else {
		$agent_group = db_get_value('id_grupo', 'tagente', 'id_agente', $id_agente);
	}
	
	$module_name = modules_get_agentmodule_name ($alert['id_agent_module']);
	$data[0] .= ui_print_truncate_text($module_name, 'module_medium', false, true, true, '[&hellip;]', 'display:block;font-size: 7.2pt') . '<br>';


	$template_group = db_get_value('id_group', 'talert_templates', 'id', $alert['id_alert_template']);
	
	// The access to the template manage page is necessary have LW permissions on template group
	if(check_acl ($config['id_user'], $template_group, "LW")) {
		$data[1] .= "<a href='index.php?sec=".$sec."&sec2=godmode/alerts/configure_alert_template&id=".$alert['id_alert_template']."'>";
	}
	
	$data[1] .= ui_print_truncate_text(
		alerts_get_alert_template_name ($alert['id_alert_template']), 'module_medium', false, true, true, '[&hellip;]', 'font-size: 7.1pt');
	$data[1] .= ' <a class="template_details"
		href="'.ui_get_full_url(false,false,false,false).'ajax.php?page=godmode/alerts/alert_templates&get_template_tooltip=1&id_template='.$alert['id_alert_template'].'">';
		$data[1] .= html_print_image("images/zoom.png", true, array("id" => 'template-details-'.$alert['id_alert_template'], "class" => "img_help"));
	$data[1] .= '</a> ';
	
	if(check_acl ($config['id_user'], $template_group, "LW") || check_acl ($config['id_user'], $template_group, "LM")) {
		$data[1] .= "</a>";
	}
	
	$actions = alerts_get_alert_agent_module_actions ($alert['id']);

	$data[2] = "<table width='70%'>";
	// Get and show default actions for this alert
	$default_action = db_get_sql ("SELECT id_alert_action
		FROM talert_templates
		WHERE id = ".$alert["id_alert_template"]);
	if ($default_action != "") {
		$data[2] .= "<tr><td><ul class='action_list'><li>";
		$data[2] .= db_get_sql ("SELECT name FROM talert_actions WHERE id = $default_action") . ' <em>(' . __('Default') . ')</em>';
		$data[2] .= ui_print_help_tip(__('The default actions will be executed every time that the alert is fired and no other action is executed'), true);
		$data[2] .= "</li></ul></td>";
		$data[2] .= "<td></td>";
		$data[2] .= "</tr>";
	}
	
	foreach ($actions as $action_id => $action) {
		$data[2] .= "<tr>";
			$data[2] .= "<td>";
				$data[2] .= '<ul class="action_list" style="display:inline;">';
				$data[2] .= '<li style="display:inline;">';
				if ($alert['disabled'])
					$data[2] .= '<font class="action_name" style="font-style: italic; color: #aaaaaa;">';
				else
					$data[2] .= '<font class="action_name">';
				$data[2] .= ui_print_truncate_text($action['name'], (GENERIC_SIZE_TEXT+20), false);
				$data[2] .= ' <em>(';
				if ($action['fires_min'] == $action['fires_max']) {
					if ($action['fires_min'] == 0)
						$data[2] .= __('Always');
					else
						$data[2] .= __('On').' '.$action['fires_min'];
				}
				else if ($action['fires_min'] < $action['fires_max']) {
					if ($action['fires_min'] == 0)
						$data[2] .= __('Until').' '.$action['fires_max'];
					else
						$data[2] .= __('From').' '.$action['fires_min'].
							' '.__('to').' '.$action['fires_max'];
				}
				else {
					$data[2] .= __('From').' '.$action['fires_min'];
				}
				if ($action['module_action_threshold'] != 0)
					$data[2] .= ' '.__('Threshold').' '.human_time_description_alerts ($action['module_action_threshold'], true, 'tiny');
				
				$data[2] .= ')</em>';
				$data[2] .= '</font>';
				$data[2] .= '</li>';
				$data[2] .= '</ul>';

				// Is possible manage actions if have LW permissions in the agent group of the alert module
				if (check_acl ($config['id_user'], $agent_group, "LW")) {
					//~ $data[2] .= '<form method="post" action="' . $url . '" class="delete_link" style="display: inline; vertical-align: -50%;">';
					$data[2] .= '<form method="post" action="' . $url . '" class="delete_link" style="display: inline;">';
					$data[2] .= html_print_input_image ('delete',
						'images/cross.png', 1, 'padding:0px;', true,
						array('title' => __('Delete action')));
					$data[2] .= html_print_input_hidden ('delete_action', 1, true);
					$data[2] .= html_print_input_hidden ('id_alert', $alert['id'], true);
					$data[2] .= html_print_input_hidden ('id_action', $action_id, true);
					$data[2] .= '</form>';
					$data[2] .= html_print_input_image ('update_action',
						'images/config.png', 1, 'padding:0px;', true,
						array('title' => __('Update action'),
								'onclick' => 'show_display_update_action(\''.$action['id'].'\',\''.$alert['id'].'\',\''.$alert['id_agent_module'].'\',\''.$action_id.'\',\''.$alert['id_agent_module'].'\')'));
					$data[2] .= html_print_input_hidden ('id_agent_module', $alert['id_agent_module'], true);
				}

			$data[2] .= "</td>";
		$data[2] .= "</tr>";
	}
	$data[2] .= '<div id="update_action-div" style="display:none;text-align:left">';
	$data[2] .= '</div>';
	$data[2] .= '</table>';
	// Is possible manage actions if have LW permissions in the agent group of the alert module
	if (check_acl ($config['id_user'], $agent_group, "LW") || check_acl ($config['id_user'], $template_group, "LM")) {
		$own_info = get_user_info($config['id_user']);
		if (check_acl ($config['id_user'], $template_group, "LW"))
			$own_groups = users_get_groups($config['id_user'], 'LW', true);
		elseif (check_acl ($config['id_user'], $template_group, "LM"))
			$own_groups = users_get_groups($config['id_user'], 'LM', true);
		$filter_groups = '';
		$filter_groups = implode(',', array_keys($own_groups));
		$actions = alerts_get_alert_actions_filter(true, 'id_group IN (' . $filter_groups . ')');
		
		$data[2] .= '<div id="add_action-div-'.$alert['id'].'" style="display:none;text-align:left">';
			$data[2] .= '<form id="add_action_form-'.$alert['id'] . '" method="post">';
				$data[2] .= '<table class="databox_color" style="width:100%">';
					$data[2] .= html_print_input_hidden ('add_action', 1, true);
					$data[2] .= html_print_input_hidden ('id_alert_module', $alert['id'], true);
					
					if (! $id_agente) {
						$data[2] .= '<tr class="datos2">';
							$data[2] .= '<td class="datos2" style="font-weight:bold;padding:6px;">';
							$data[2] .= __('Agent');
							$data[2] .= '</td>';
							$data[2] .= '<td class="datos">';
							$data[2] .= ui_print_truncate_text($agent_name, 'agent_small', false, true, true, '[&hellip;]');
							$data[2] .= '</td>';
						$data[2] .= '</tr>';
					}
					$data[2] .= '<tr class="datos">';
						$data[2] .= '<td class="datos" style="font-weight:bold;padding:6px;">';
						$data[2] .= __('Module');
						$data[2] .= '</td>';
						$data[2] .= '<td class="datos">';
						$data[2] .= ui_print_truncate_text($module_name, 'module_small', false, true, true, '[&hellip;]');
						$data[2] .= '</td>';
					$data[2] .= '</tr>';
					$data[2] .= '<tr class="datos2">';
						$data[2] .= '<td class="datos2" style="font-weight:bold;padding:6px;">';
							$data[2] .= __('Action');
						$data[2] .= '</td>';
						$data[2] .= '<td class="datos2">';
							$data[2] .= html_print_select ($actions, 'action_select', '', '', __('None'), 0, true, false, true, '', false, 'width:150px');
						$data[2] .= '</td>';
					$data[2] .= '</tr>';
					$data[2] .= '<tr class="datos">';
						$data[2] .= '<td class="datos" style="font-weight:bold;padding:6px;">';
							$data[2] .= __('Number of alerts match from') . '&nbsp;' . ui_print_help_icon ("alert-matches", true, ui_get_full_url(false, false, false, false));
						$data[2] .= '</td>';
						$data[2] .= '<td class="datos">';
							$data[2] .= html_print_input_text ('fires_min', 0, '', 4, 10, true);
							$data[2] .= ' '.__('to').' ';
							$data[2] .= html_print_input_text ('fires_max', 0, '', 4, 10, true);
						$data[2] .= '</td>';
					$data[2] .= '</tr>';
					$data[2] .= '<tr class="datos2">';
						$data[2] .= '<td class="datos2" style="font-weight:bold;padding:6px;">';
							$data[2] .= __('Threshold') . "&nbsp;" . ui_print_help_icon ('action_threshold', true, ui_get_full_url(false, false, false, false));
						$data[2] .= '</td>';
						$data[2] .= '<td class="datos2">';
							$data[2] .= html_print_input_text ('module_action_threshold', '', '', 4, 10, true);
						$data[2] .= '</td>';
					$data[2] .= '</tr>';
				$data[2] .= '</table>';
				$data[2] .= html_print_submit_button (__('Add'), 'addbutton', false, array('class' => "sub next", 'style' => "float:right"), true);
			$data[2] .= '</form>';
		$data[2] .= '</div>';
	}
	
	$status = STATUS_ALERT_NOT_FIRED;
	$title = "";
	
	if ($alert["times_fired"] > 0) {
		$status = STATUS_ALERT_FIRED;
		$title = __('Alert fired').' '.$alert["times_fired"].' '.__('times');
	}
	elseif ($alert["disabled"] > 0) {
		$status = STATUS_ALERT_DISABLED;
		$title = __('Alert disabled');
	}
	else {
		$status = STATUS_ALERT_NOT_FIRED;
		$title = __('Alert not fired');
	}

	$data[3] = ui_print_status_image($status, $title, true);

	$data[4] = '<form class="disable_alert_form" action="' . $url . '" method="post" style="display: inline;">';
	if ($alert['disabled']) {
		$data[4] .= html_print_input_image ('enable', 'images/lightbulb_off.png', 1, 'padding:0px', true);
		$data[4] .= html_print_input_hidden ('enable_alert', 1, true);
	}
	else {
		$data[4] .= html_print_input_image ('disable', 'images/lightbulb.png', 1, 'padding:0px;', true);
		$data[4] .= html_print_input_hidden ('disable_alert', 1, true);
	}
	$data[4] .= html_print_input_hidden ('id_alert', $alert['id'], true);
	$data[4] .= '</form>';

	// To manage alert is necessary LW permissions in the agent group
	if(check_acl ($config['id_user'], $agent_group, "LW")) {
		$data[4] .= '&nbsp;&nbsp;<form class="standby_alert_form" action="' . $url . '" method="post" style="display: inline;">';
		if (!$alert['standby']) {
			$data[4] .= html_print_input_image ('standby_off', 'images/bell.png', 1, 'padding:0px;', true);
			$data[4] .= html_print_input_hidden ('standbyon_alert', 1, true);
		}
		else {
			$data[4] .= html_print_input_image ('standby_on', 'images/bell_pause.png', 1, 'padding:0px;', true);
			$data[4] .= html_print_input_hidden ('standbyoff_alert', 1, true);
		}
		$data[4] .= html_print_input_hidden ('id_alert', $alert['id'], true);
		$data[4] .= '</form>';
	}
	
	// To access to policy page is necessary have AW permissions in the agent
	if(check_acl ($config['id_user'], $agent_group, "AW")) {
		if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) {
			$policyInfo = policies_is_alert_in_policy2($alert['id'], false);
			if ($policyInfo === false)
				$data[3] .= '';
			else {
				$img = 'images/policies.png';

				$data[3] .= '&nbsp;&nbsp;<a href="?sec=gpolicies&sec2=enterprise/godmode/policies/policies&pure='.$pure.'&id=' . $policyInfo['id'] . '">' .
					html_print_image($img, true, array('title' => $policyInfo['name'])) .
					'</a>';
			}
		}
	}
	
	// To manage alert is necessary LW permissions in the agent group 
	if(check_acl ($config['id_user'], $agent_group, "LW")) {
		$data[4] .= '&nbsp;&nbsp;<form class="delete_alert_form" action="' . $url . '" method="post" style="display: inline;">';
		if ($alert['disabled']) {
			$data[4] .= html_print_image('images/add.disabled.png',
			true, array('title' => __("Add action")));
		}
		else {
			$data[4] .= '<a href="javascript:show_add_action(\'' . $alert['id'] . '\');">';
			$data[4] .= html_print_image('images/add.png', true, array('title' => __("Add action")));
			$data[4] .= '</a>';
		}
		$data[4] .= html_print_input_image ('delete', 'images/cross.png', 1, '', true, array('title' => __('Delete')));
		$data[4] .= html_print_input_hidden ('delete_alert', 1, true);
		$data[4] .= html_print_input_hidden ('id_alert', $alert['id'], true);
		$data[4] .= '</form>';
	}
	
	if(check_acl ($config['id_user'], $agent_group, "LM")) {
		$data[4] .= '<form class="view_alert_form" method="post" style="display: inline;" action="index.php?sec=galertas&sec2=godmode/alerts/alert_view">';
		$data[4] .= html_print_input_image ('view_alert', 'images/eye.png', 1, '', true, array('title' => __('View alert advanced details')));
		$data[4] .= html_print_input_hidden ('id_alert', $alert['id'], true);
		$data[4] .= '</form>';
	}
	array_push ($table->data, $data);
}

if (isset($data)) {
	html_print_table ($table);
}
else {
	ui_print_info_message ( array('no_close' => true, 'message' =>  __('No alerts defined') ) );
}

// Create alert button
// $dont_display_alert_create_bttn is setted in configurar_agente.php in order to not display create button
$display_create = true;
if (isset($dont_display_alert_create_bttn))
	if ($dont_display_alert_create_bttn)
		$display_create = false;

if ($display_create && (check_acl ($config['id_user'], 0, "LW") || check_acl ($config['id_user'], $template_group, "LM"))) {
	echo '<div class="action-buttons" style="width: ' . $table->width . '">';
	echo '<form method="post" action="index.php?sec='.$sec.'&sec2=godmode/alerts/alert_list&tab=builder&pure='.$pure.'">';
	html_print_submit_button (__('Create'), 'crtbtn', false, 'class="sub next"');
	echo '</form>';
	echo '</div>';
}

ui_require_css_file ('cluetip');
ui_require_jquery_file ('cluetip');
ui_require_jquery_file ('pandora.controls');
ui_require_jquery_file ('bgiframe');
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
	
	$("input[name=disable]").attr ("title", "<?php echo __('Disable')?>")
		.hover (function () {
				$(this).attr ("src", <?php echo '"' . html_print_image("images/lightbulb_off.png", true, false, true) . '"'; ?> );
			},
			function () {
				$(this).attr ("src", <?php echo '"' . html_print_image("images/lightbulb.png", true, false, true) . '"'; ?> );
			}
		);
	
	$("input[name=enable]").attr ("title", "<?php echo __('Enable')?>")
		.hover (function () {
				$(this).attr ("src", <?php echo '"' . html_print_image("images/lightbulb.png", true, false, true) . '"'; ?> );
			},
			function () {
				$(this).attr ("src", <?php echo '"' . html_print_image("images/lightbulb_off.png", true, false, true) . '"'; ?> );
			}
		);
	
	$("input[name=standby_on]").attr ("title", "<?php echo __('Set off standby')?>")
		.hover (function () {
				$(this).attr ("src", <?php echo '"' . html_print_image("images/bell.png", true, false, true) . '"'; ?> );
			},
			function () {
				$(this).attr ("src", <?php echo '"' . html_print_image("images/bell_pause.png", true, false, true) . '"'; ?> );
			}
		);
	
	$("input[name=standby_off]").attr ("title", "<?php echo __('Set standby')?>")
		.hover (function () {
				$(this).attr ("src", <?php echo '"' . html_print_image("images/bell_pause.png", true, false, true) . '"'; ?> );
			},
			function () {
				$(this).attr ("src", <?php echo '"' . html_print_image("images/bell.png", true, false, true) . '"'; ?> );
			}
		);
	
	$("form.disable_alert_form").submit (function () {
		return true;
	});
	
	$("form.delete_link, form.delete_alert_form").submit (function () {
		if (! confirm ("<?php echo __('Are you sure?')?>"))
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
			resizable: true,
			draggable: true,
			title: '<?php echo __('Add action'); ?>',
			modal: true,
			overlay: {
				opacity: 0.5,
				background: "black"
			},
			width: 500,
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
		url: action="<?php echo ui_get_full_url("ajax.php", false, false, false); ?>",
		success: function (data) {
			$("#update_action-div").html (data);
			$("#update_action-div").hide ()
				.dialog ({
					resizable: true,
					draggable: true,
					title: '<?php echo __('Update action'); ?>',
					modal: true,
					overlay: {
						opacity: 0.5,
						background: "black"
					},
					width: 500,
					height: 300
				})
				.show ();
		}
	});
	
}

/* ]]> */
</script>
