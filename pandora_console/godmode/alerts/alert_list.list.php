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

if (! check_acl ($config['id_user'], 0, "LW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Alert Management");
	require ("general/noaccess.php");
	exit;
}

/* Check if this page is included from a agent edition */

if (! check_acl ($config['id_user'], 0, "LW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Alert Management");
	require ("general/noaccess.php");
	exit;
}

require_once ($config['homedir'].'/include/functions_agents.php');
require_once ($config['homedir'].'/include/functions_modules.php');
require_once ($config['homedir'].'/include/functions_users.php');

// Table for filter controls
$form_filter = '<form method="post" action="index.php?sec=galertas&amp;sec2=godmode/alerts/alert_list&amp;refr='.$config["refr"].'&amp;pure='.$config["pure"].'">';
$form_filter .= "<input type='hidden' name='search' value='1' />\n";
$form_filter .= '<table style="width: 98%;" cellpadding="4" cellspacing="4" class="databox">'."\n";
$form_filter .= "<tr>\n";
$form_filter .= "<td>".__('Template name')."</td><td>";
$form_filter .= html_print_input_text ('template_name', $templateName, '', 12, 255, true);
$form_filter .= "</td>\n";
$temp = agents_get_agents();
$arrayAgents = array();

# Avoid empty arrays, warning messages are UGLY !
if ($temp){
    foreach ($temp as $agentElement) {
    	$arrayAgents[$agentElement['id_agente']] = $agentElement['nombre'];
    }
}

$form_filter .= "<td>".__('Agents')."</td><td>";
//Image src with skins
$src_code = html_print_image('images/lightning.png', true, false, true);
$form_filter .= html_print_input_text_extended ('agent_name', $agentName, 'text-agent_name', '', 12, 100, false, '',
array('style' => 'background: url(' . $src_code . ') no-repeat right;'), true);
$form_filter .=  ui_print_help_tip(__('Type at least two characters to search'), true); //'<a href="#" class="tip">&nbsp;<span>' . __("Type at least two characters to search") . '</span></a>';
$form_filter .= "</td>\n";


$form_filter .= "<td>".__('Module name')."</td><td>";
$form_filter .= html_print_input_text ('module_name', $moduleName, '', 12, 255, true);
$form_filter .= "</td>\n";
$form_filter .= "</tr>\n";

$all_groups = db_get_value('is_admin', 'tusuario', 'id_user', $config['id_user']);

$groups_user = users_get_groups($config['id_user'], 'AR', $all_groups);
if ($groups_user === false) {
	$groups_user = array();
}
$groups_id = implode(',', array_keys($groups_user));

$form_filter .= "<tr>\n";
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
$form_filter .= "<td>".__('Actions')."</td><td>";
$form_filter .= html_print_select ($arrayActions, "action_id", $actionID,  '', __('All'), -1, true);
$form_filter .= "</td>\n";
$form_filter .= "<td>".__('Field content')."</td><td>";
$form_filter .= html_print_input_text ('field_content', $fieldContent, '', 12, 255, true);
$form_filter .= "</td>\n";
$form_filter .= "<td>".__('Priority')."</td><td>";
$form_filter .= html_print_select (get_priorities (), 'priority',$priority, '', __('All'), -1, true);
$form_filter .= "</td>";
$form_filter .= "</tr>\n";

$form_filter .= "<tr>\n";
$form_filter .= "<td>".__('Enabled / Disabled')."</td><td>";
$ed_list = array ();
$ed_list[0] = __('Enable');
$ed_list[1] = __('Disable');
$form_filter .= html_print_select ($ed_list, 'enabledisable', $enabledisable, '', __('All'), -1, true);
$form_filter .= "</td><td>".__('Standby')."</td><td>";
$sb_list = array ();
$sb_list[1] = __('Standby on');
$sb_list[0] = __('Standby off');
$form_filter .= html_print_select ($sb_list, 'standby', $standby, '', __('All'), -1, true);
$form_filter .= "</td></tr>\n";

$form_filter .= "<tr>\n";
$form_filter .= "<td colspan='6' align='right'>";
$form_filter .= html_print_submit_button (__('Update'), '', false, 'class="sub upd"', true);
$form_filter .= "</td>";
$form_filter .= "</tr>\n";
$form_filter .= "</table>\n";
$form_filter .= "</form>\n";

echo "<br>";
ui_toggle($form_filter,__('Alert control filter'), __('Toggle filter(s)'));

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
					FROM tagente AS t1 INNER JOIN tagente_modulo AS t2 ON t1.id_agente = t2.id_agente
					WHERE t1.nombre LIKE '" . trim($agentName) . "')";
				break;
			case "oracle":
				$where .= " AND id_agent_module IN (SELECT t2.id_agente_modulo
					FROM tagente t1 INNER JOIN tagente_modulo t2 ON t1.id_agente = t2.id_agente
					WHERE t1.nombre LIKE '" . trim($agentName) . "')";
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

$total = agents_get_alerts_simple (array_keys ($agents), false,
	false, $where, false, false, false, true);

if(empty($total)) $total = 0;

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

if ($id_agente) {
	ui_pagination ($total, 'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=alert&id_agente=' . $id_agente);
}
else {
	ui_pagination ($total, 'index.php?sec=galertas&sec2=godmode/alerts/alert_list&search=1' . '&template_name=' . $templateName . '&agent_name=' . $agentName . '&module_name=' . $moduleName . '&action_id=' . $actionID . '&field_content=' . $fieldContent. '&priority=' . $priority . '&enabledisable=' . $enabledisable . '&standby=' . $standby);
}
$simple_alerts = agents_get_alerts_simple (array_keys ($agents), false,
	array ('offset' => (int) get_parameter ('offset'),
		'limit' => $config['block_size'], 'order' => $order), $where, false);

$offset = get_parameter('offset');
if (!$id_agente) {
	$url = 'index.php?sec=galertas&sec2=godmode/alerts/alert_list&tab=list&offset=' . $offset;
}
else {
	$url = 'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=alert&id_agente=' . $id_agente;
}
	
$table->class = 'alert_list';
$table->width = '98%';
$table->size = array ();

$table->align[2] = 'left';
$table->align[3] = 'left';
$table->align[4] = 'center';
$table->align[5] = 'center';

$table->head = array ();

if (! $id_agente) {
	$table->style = array ();
	$table->style[0] = 'font-weight: bold';
	$table->head[0] = __('Agent') . '<br>' .
		'<a href="' . $url . '&sort_field=agent&sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectAgentUp)) . '</a>' .
		'<a href="' . $url . '&sort_field=agent&sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectAgentDown)) . '</a>';
	$table->size[0] = '20%';
	$table->size[1] = '15%';
	$table->size[2] = '15%';
	$table->size[3] = '15%';
	$table->size[4] = '2%';
	$table->size[5] = '9%';
	
/*	if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) {
		$table->size[4] = '8%';
	}*/
}
else {
	/* Different sizes or the layout screws up */
	$table->size[0] = '0%';
	$table->size[1] = '25%';
	$table->size[3] = '25%';
/*	if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) {
		$table->size[4] = '25%';
	}  */
	$table->size[4] = '3%';	
	$table->size[5] = '10%';
}

$table->head[1] = __('Module') . '<br>' .
	'<a href="' . $url . '&sort_field=module&sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectModuleUp)) . '</a>' .
	'<a href="' . $url . '&sort_field=module&sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectModuleDown)) . '</a>';
$table->head[2] = __('Template') . '<br>' .
	'<a href="' . $url . '&sort_field=template&sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectTemplateUp)) . '</a>' .
	'<a href="' . $url . '&sort_field=template&sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectTemplateDown)) . '</a>';
/*if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) {
	$table->head[5] = "<span title='" . __('Policy') . "'>" . __('P.') . "</span>";
}*/
$table->head[3] = __('Actions');
$table->head[4] = __('Status');
$table->head[5] = "<span title='" . __('Operations') . "'>" . __('Op.') . "</span>";

$table->valign[0] = 'middle';
$table->valign[1] = 'middle';
$table->valign[2] = 'middle';
$table->valign[3] = 'middle';
$table->valign[4] = 'middle';
$table->valign[5] = 'middle';

/*if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) {
	$table->align[5] = 'center';
}*/

$table->data = array ();

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
		$data[0] = '<a href="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=main&id_agente='.$id_agent.'">';
		if ($alert['disabled'])
			$data[0] .= '<span style="font-style: italic; color: #aaaaaa;">';
		$data[0] .= '<span style="font-size: 7.2pt">' . agents_get_name ($id_agent) . '</span>';
		if ($alert['disabled'])
			$data[0] .= '</span>';
		$data[0] .= '</a>';
	}
	$data[1] = ui_print_truncate_text(modules_get_agentmodule_name ($alert['id_agent_module']), 35, false, true, true, '[&hellip;]', 'font-size: 7.2pt');

	$data[2] = ' <a class="template_details"
		href="ajax.php?page=godmode/alerts/alert_templates&get_template_tooltip=1&id_template='.$alert['id_alert_template'].'">' .
		html_print_image("images/zoom.png", true, array("id" => 'template-details-'.$alert['id_alert_template'], "class" => "img_help")) . '</a> ';
	$data[2] .= "<a href='index.php?sec=galertas&sec2=godmode/alerts/configure_alert_template&id=".$alert['id_alert_template']."'>";
	$data[2] .= ui_print_truncate_text(alerts_get_alert_template_name ($alert['id_alert_template']), 55, false, true, true, '[&hellip;]', 'font-size: 7.1pt');
	$data[2] .= "</a>";
		
	$actions = alerts_get_alert_agent_module_actions ($alert['id']);

	$data[3] = '';
	if (empty($actions)){
		// Get and show default actions for this alert
		$default_action = db_get_sql ("SELECT id_alert_action FROM talert_templates WHERE id = ".$alert["id_alert_template"]);
		if ($default_action != ""){
			$data[3] = __("Default"). " : ".db_get_sql ("SELECT name FROM talert_actions WHERE id = $default_action");
		}

	}
	else {
		$data[3] = '<ul class="action_list">';
		foreach ($actions as $action_id => $action) {
			$data[3] .= '<li>';
			if ($alert['disabled'])
				$data[3] .= '<font class="action_name" style="font-style: italic; color: #aaaaaa;">';
			else
				$data[3] .= '<font class="action_name">';
			$data[3] .= ui_print_truncate_text($action['name'], 15, false);
			$data[3] .= ' <em>(';
			if ($action['fires_min'] == $action['fires_max']) {
				if ($action['fires_min'] == 0)
					$data[3] .= __('Always');
				else
					$data[3] .= __('On').' '.$action['fires_min'];
			}
			else {
				if ($action['fires_min'] == 0)
					$data[3] .= __('Until').' '.$action['fires_max'];
				else
					$data[3] .= __('From').' '.$action['fires_min'].
						' '.__('to').' '.$action['fires_max'];
			}
			if ($action['module_action_threshold'] != 0)
				$data[3] .= ' '.__('Threshold').' '.$action['module_action_threshold'];

			$data[3] .= ')</em>';
			$data[3] .= '</font>';
//			$data[6] .= ' <span class="delete" style="clear:right">';
			$data[3] .= '<form method="post" class="delete_link" style="display: inline; vertical-align: -50%;">';
			$data[3] .= html_print_input_image ('delete', 'images/cross.png', 1, '', true, array('title' => __('Delete')));
			$data[3] .= html_print_input_hidden ('delete_action', 1, true);
			$data[3] .= html_print_input_hidden ('id_alert', $alert['id'], true);
			$data[3] .= html_print_input_hidden ('id_action', $action_id, true);
			$data[3] .= '</form>';
//			$data[3] .= '</span>';
			$data[3] .= '</li>';
		}
		$data[3] .= '</ul>';
	}

	
	$data[3] .= '<a class="add_action" id="add-action-'.$alert['id'].'" href="#">';
	$data[3] .= html_print_image ('images/add.png', true);
	if ($alert['disabled'])
		$data[3] .= ' '. '<span style="font-style: italic; color: #aaaaaa;">' .__('Add action') . '</span>';
	else
		$data[3] .= ' ' . __('Add action');
	$data[3] .= '</a>';
	
	$data[3] .= '<form id="add_action_form-'.$alert['id'].'" method="post" class="invisible">';
	$data[3] .= html_print_input_hidden ('add_action', 1, true);
	$data[3] .= html_print_input_hidden ('id_alert_module', $alert['id'], true);
	$own_info = get_user_info($config['id_user']);
	$own_groups = users_get_groups($config['id_user'], 'LW', true);
	$filter_groups = '';
	$filter_groups = implode(',', array_keys($own_groups));
	$actions = alerts_get_alert_actions_filter(true, 'id_group IN (' . $filter_groups . ')');
	$data[3] .= html_print_select ($actions, 'action', '', '', __('None'), 0, true);
	$data[3] .= '<br />';
	$data[3] .= '<span><a href="#" class="show_advanced_actions">'.__('Advanced options').' &raquo; </a></span>';
	$data[3] .= '<span class="advanced_actions invisible">';
	$data[3] .= __('Number of alerts match from').' ';
	$data[3] .= html_print_input_text ('fires_min', -1, '', 4, 10, true);
	$data[3] .= ' '.__('to').' ';
	$data[3] .= html_print_input_text ('fires_max', -1, '', 4, 10, true);
	$data[3] .= ui_print_help_icon ("alert-matches", true);
	$data[3] .= '<br />' . __('Threshold');
	$data[3] .= html_print_input_text ('module_action_threshold', '', '', 4, 10, true) . ui_print_help_icon ('action_threshold', true);
	$data[3] .= '</span>';
	$data[3] .= '<div class="right">';
	$data[3] .= html_print_submit_button (__('Add'), 'add_action', false, 'class="sub next"', true);
	$data[3] .= '</div>';
	$data[3] .= '</form>';
	
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
	
	$data[4] = ui_print_status_image($status, $title, true);
	
	$data[5] = '<form class="disable_alert_form" method="post" style="display: inline;">';
	if ($alert['disabled']) {
		$data[5] .= html_print_input_image ('enable', 'images/lightbulb_off.png', 1, '', true);
		$data[5] .= html_print_input_hidden ('enable_alert', 1, true);
	}
	else {
		$data[5] .= html_print_input_image ('disable', 'images/lightbulb.png', 1, '', true);
		$data[5] .= html_print_input_hidden ('disable_alert', 1, true);
	}
	$data[5] .= html_print_input_hidden ('id_alert', $alert['id'], true);
	$data[5] .= '</form>';	
	
	$data[5] .= '&nbsp;&nbsp;<form class="standby_alert_form" method="post" style="display: inline;">';
	if (!$alert['standby']) {
		$data[5] .= html_print_input_image ('standby_off', 'images/bell.png', 1, '', true);
		$data[5] .= html_print_input_hidden ('standbyon_alert', 1, true);
	}
	else {
		$data[5] .= html_print_input_image ('standby_on', 'images/bell_pause.png', 1, '', true);
		$data[5] .= html_print_input_hidden ('standbyoff_alert', 1, true);
	}
	$data[5] .= html_print_input_hidden ('id_alert', $alert['id'], true);
	$data[5] .= '</form>';	

	if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) {
		$policyInfo = policies_is_alert_in_policy2($alert['id'], false);
		if ($policyInfo === false)
			$data[5] .= '';
		else {
			$img = 'images/policies.png';
				
			$data[5] .= '&nbsp;&nbsp;<a href="?sec=gpolicies&sec2=enterprise/godmode/policies/policies&id=' . $policyInfo['id'] . '">' . 
				html_print_image($img,true, array('title' => $policyInfo['name'])) .
				'</a>';
		}
	}

	$data[5] .= '&nbsp;&nbsp;<form class="delete_alert_form" method="post" style="display: inline;">';	
	$data[5] .= html_print_input_image ('delete', 'images/cross.png', 1, '', true, array('title' => __('Delete')));
	$data[5] .= html_print_input_hidden ('delete_alert', 1, true);
	$data[5] .= html_print_input_hidden ('id_alert', $alert['id'], true);
	$data[5] .= '</form>';
	array_push ($table->data, $data);
}

if (isset($data)){
	html_print_table ($table);
}
else {
	echo "<div class='nf'>".__('No alerts defined')."</div>";
}

// Create alert button
// $dont_display_alert_create_bttn is setted in configurar_agente.php in order to not display create button
$display_create = true;
if (isset($dont_display_alert_create_bttn))
	if ($dont_display_alert_create_bttn)
		$display_create = false;

if ($display_create){
	echo '<div class="action-buttons" style="width: '.$table->width.'">';
	echo '<form method="post" action="index.php?sec=galertas&sec2=godmode/alerts/alert_list&tab=builder">';
	html_print_submit_button (__('Create'), 'crtbtn', false, 'class="sub next"');
	echo '</form>';
	echo '</div>';
}
	
ui_require_css_file ('cluetip');
ui_require_jquery_file ('cluetip');
ui_require_jquery_file ('pandora.controls');
ui_require_jquery_file ('bgiframe');
ui_require_jquery_file ('autocomplete');
?>
<script type="text/javascript">
/* <![CDATA[ */
$(document).ready (function () {
	$("#text-agent_name").autocomplete ("ajax.php",
		{
			scroll: true,
			minChars: 2,
			extraParams: {
				page: "godmode/agentes/agent_manager",
				search_parents: 1,
				id_group: function() { return $("#grupo").val(); },
				id_agent: <?php echo $id_agente ?>
			},
			formatItem: function (data, i, total) {
				if (total == 0)
					$("#text-id_parent").css ('background-color', '#cc0000');
				else
					$("#text-id_parent").css ('background-color', 'none');
				if (data == "")
					return false;

				return data[0]+'<br><span class="ac_extra_field"><?php echo __("IP") ?>: '+data[1]+'</span>';
			},
			delay: 200
		}
	);
//----------------------------


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
	
	
	$("a.add_action").click (function () {
		id = this.id.split ("-").pop ();
		
		$('#add_action_form-' + id).attr("class", '');
		$(this).attr("class", 'invisible');

		return false;
	});
	
	$("form.delete_link, form.delete_alert_form").submit (function () {
		if (! confirm ("<?php echo __('Are you sure?')?>"))
			return false;
		return true;
	});
	
	$("a.show_advanced_actions").click (function () {
		/* It can be done in two different sites, so it must use two different selectors */
		actions = $(this).parents ("form").children ("span.advanced_actions");
		if (actions.length == 0)
			actions = $(this).parents ("div").children ("span.advanced_actions")
		$("#text-fires_min", actions).attr ("value", 0);
		$("#text-fires_max", actions).attr ("value", 0);
		$(actions).show ();
		$(this).remove ();
		return false;
	});
	

});
/* ]]> */
</script>
