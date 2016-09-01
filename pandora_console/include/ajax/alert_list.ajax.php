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

require_once ('include/functions_agents.php');
require_once ('include/functions_alerts.php');
$isFunctionPolicies = enterprise_include ('include/functions_policies.php');

$get_agent_alerts_simple = (bool) get_parameter ('get_agent_alerts_simple');
$disable_alert = (bool) get_parameter ('disable_alert');
$enable_alert = (bool) get_parameter ('enable_alert');
$get_actions_module = (bool) get_parameter ('get_actions_module');
$show_update_action_menu = (bool) get_parameter ('show_update_action_menu');

if ($get_agent_alerts_simple) {
	$id_agent = (int) get_parameter ('id_agent');
	if ($id_agent <= 0) {
		echo json_encode (false);
		return;
	}
	$id_group = agents_get_agent_group ($id_agent);
	
	if (! check_acl ($config['id_user'], $id_group, "AR")) {
		db_pandora_audit("ACL Violation",
			"Trying to access Alert Management");
		echo json_encode (false);
		return;
	}
	
	require_once ('include/functions_agents.php');
	require_once ('include/functions_alerts.php');
	require_once ('include/functions_modules.php');

	
	$alerts = agents_get_alerts_simple ($id_agent);
	if (empty ($alerts)) {
		echo json_encode (false);
		return;
	}
	
	$retval = array ();
	foreach ($alerts as $alert) {
		$alert['template'] = alerts_get_alert_template ($alert['id_alert_template']);
		$alert['module_name'] = modules_get_agentmodule_name ($alert['id_agent_module']);
		$alert['agent_name'] = modules_get_agentmodule_agent_name ($alert['id_agent_module']);
		$retval[$alert['id']] = $alert;
	}
	
	echo json_encode ($retval);
	return;
}

if ($enable_alert) {
	$id_alert = (int) get_parameter ('id_alert');

	$result = alerts_agent_module_disable ($id_alert, false);
	if ($result)
		echo __('Successfully enabled');
	else
		echo __('Could not be enabled');
	return;
}

if ($disable_alert) {
	$id_alert = (int) get_parameter ('id_alert');

	$result = alerts_agent_module_disable ($id_alert, true);
	if ($result)
		echo __('Successfully disabled');
	else
		echo __('Could not be disabled');
	return;
}

if ($get_actions_module) {
	$id_module = get_parameter ('id_module');
	
	if (empty($id_module))
		return false;
		
	$alerts_modules = alerts_get_alerts_module_name ($id_module);	
	
	echo json_encode ($alerts_modules);
	return;
}

if ($show_update_action_menu) {
	$id_agent_module = (int) get_parameter ('id_agent_module');
	$id_module_action = (int) get_parameter ('id_module_action');
	$id_agent = (int) get_parameter ('id_agent');
	$id_alert = (int) get_parameter ('id_alert');
	
	$module_name = modules_get_agentmodule_name ($id_agent_module);
	$id_agent = modules_get_agentmodule_agent ($id_agent_module);
	$agent_name = agents_get_name ($id_agent);
	
	$id_action = (int) get_parameter ('id_action');
	
	$actions = alerts_get_alert_agent_module_actions ($id_alert);
	$action_opction = db_get_row ('talert_template_module_actions', 'id', $id_module_action);
	
	$data .= '<form id="update_action-'.$alert['id'] . '" method="post">';
	$data .= '<table class="databox_color" style="width:100%">';
		$data .= html_print_input_hidden ('update_action', 1, true);
		$data .= html_print_input_hidden ('id_module_action_ajax', $id_module_action, true);
		if (! $id_agente) {
			$data .= '<tr class="datos2">';
				$data .= '<td class="datos2" style="font-weight:bold;padding:6px;">';
				$data .= __('Agent');
				$data .= '</td>';
				$data .= '<td class="datos">';
				$data .= ui_print_truncate_text($agent_name, 'agent_small', false, true, true, '[&hellip;]');
				$data .= '</td>';
			$data .= '</tr>';
		}
		$data .= '<tr class="datos">';
			$data .= '<td class="datos" style="font-weight:bold;padding:6px;">';
			$data .= __('Module');
			$data .= '</td>';
			$data .= '<td class="datos">';
			$data .= ui_print_truncate_text($module_name, 'module_small', false, true, true, '[&hellip;]');
			$data .= '</td>';
		$data .= '</tr>';
		$data .= '<tr class="datos2">';
			$data .= '<td class="datos2" style="font-weight:bold;padding:6px;">';
				$data .= __('Action');
			$data .= '</td>';
			$data .= '<td class="datos2">';
				$data .= html_print_select ($actions, 'action_select_ajax', $id_action, '', __('None'), 0, true, false, true, '', false, 'width:150px');
			$data .= '</td>';
		$data .= '</tr>';
		$data .= '<tr class="datos">';
			$data .= '<td class="datos" style="font-weight:bold;padding:6px;">';
				$data .= __('Number of alerts match from') . '&nbsp;' . ui_print_help_icon ("alert-matches", true, ui_get_full_url(false, false, false, false));
			$data .= '</td>';
			$data .= '<td class="datos">';
				$data .= html_print_input_text ('fires_min_ajax', $action_opction['fires_min'], '', 4, 10, true);
				$data .= ' '.__('to').' ';
				$data .= html_print_input_text ('fires_max_ajax', $action_opction['fires_max'], '', 4, 10, true);
			$data .= '</td>';
		$data .= '</tr>';
		$data .= '<tr class="datos2">';
			$data .= '<td class="datos2" style="font-weight:bold;padding:6px;">';
				$data .= __('Threshold') . "&nbsp;" . ui_print_help_icon ('action_threshold', true, ui_get_full_url(false, false, false, false));
			$data .= '</td>';
			$data .= '<td class="datos2">';
				$data .= html_print_input_text ('module_action_threshold_ajax', $action_opction['module_action_threshold'], '', 4, 10, true);
			$data .= '</td>';
		$data .= '</tr>';
	$data .= '</table>';
	$data .= html_print_submit_button (__('Update'), 'updbutton', false, array('class' => "sub next", 'style' => "float:right"), true);
	$data .= '</form>';
	echo $data;
	return;
}

return;
?>