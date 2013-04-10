<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

global $config;

include_once('include/functions_alerts.php');
enterprise_include_once('include/functions_policies.php');
include_once($config['homedir'] . "/include/functions_agents.php");
include_once($config['homedir'] . "/include/functions_modules.php");


// TODO: CLEAN extra_sql
$extra_sql = '';

$searchAlerts = check_acl($config['id_user'], 0, "AR");

$selectDisabledUp = '';
$selectDisabledDown = '';
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
				$selectAgentUp = $selected;
				$order = array('field' => 'disabled', 'order' => 'ASC');
				break;
			case 'down':
				$selectAgentDown = $selected;
				$order = array('field' => 'disabled', 'order' => 'DESC');
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
				$order = array('field' => 'module_name', 'order' => 'ASC');
				break;
			case 'down':
				$selectModuleDown = $selected;
				$order = array('field' => 'module_name', 'order' => 'DESC');
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
		$selectDisabledUp = '';
		$selectDisabledDown = '';
		$selectAgentUp = $selected;
		$selectAgentDown = '';
		$selectModuleUp = '';
		$selectModuleDown = '';
		$selectTemplateUp = '';
		$selectTemplateDown = '';
		
		$order = array('field' => 'agent_name', 'order' => 'ASC');
		break;
}

$alerts = false;

if($searchAlerts) {
	$agents = array_keys(agents_get_group_agents(array_keys(users_get_groups($config["id_user"], 'AR', false))));

	switch ($config["dbtype"]) {
		case "mysql":
			$whereAlerts = 'AND (
				id_alert_template IN (SELECT id FROM talert_templates WHERE name LIKE "%' . $stringSearchSQL . '%") OR
				id_alert_template IN (
					SELECT id
					FROM talert_templates
					WHERE id_alert_action IN (
						SELECT id
						FROM talert_actions
						WHERE name LIKE "%' . $stringSearchSQL . '%")) OR
				talert_template_modules.id IN (
					SELECT id_alert_template_module
					FROM talert_template_module_actions
					WHERE id_alert_action IN (
						SELECT id
						FROM talert_actions
						WHERE name LIKE "%' . $stringSearchSQL . '%")) OR
				id_agent_module IN (
					SELECT id_agente_modulo
					FROM tagente_modulo
					WHERE nombre LIKE "%' . $stringSearchSQL . '%") OR
				id_agent_module IN (
					SELECT id_agente_modulo
					FROM tagente_modulo
					WHERE id_agente IN (
						SELECT id_agente
						FROM tagente
						WHERE nombre LIKE "%' . $stringSearchSQL . '%" ' . $extra_sql . '))
			)';
			break;
		case "postgresql":
		case "oracle":
			$whereAlerts = 'AND (
				id_alert_template IN (SELECT id FROM talert_templates WHERE name LIKE \'%' . $stringSearchSQL . '%\') OR
				id_alert_template IN (
					SELECT id
					FROM talert_templates
					WHERE id_alert_action IN (
						SELECT id
						FROM talert_actions
						WHERE name LIKE \'%' . $stringSearchSQL . '%\')) OR
				talert_template_modules.id IN (
					SELECT id_alert_template_module
					FROM talert_template_module_actions
					WHERE id_alert_action IN (
						SELECT id
						FROM talert_actions
						WHERE name LIKE \'%' . $stringSearchSQL . '%\')) OR
				id_agent_module IN (
					SELECT id_agente_modulo
					FROM tagente_modulo
					WHERE nombre LIKE \'%' . $stringSearchSQL . '%\') OR
				id_agent_module IN (
					SELECT id_agente_modulo
					FROM tagente_modulo
					WHERE id_agente IN (
						SELECT id_agente
						FROM tagente
						WHERE nombre LIKE \'%' . $stringSearchSQL . '%\'  ' . $extra_sql . '))
			)';
			break;
	}
		
	$alertsraw = agents_get_alerts_simple ($agents, "all_enabled", array('offset' => get_parameter ('offset',0), 'limit' => $config['block_size'], 'order' => $order['field'] . " " . $order['order']), $whereAlerts);

	$stringSearchPHP = substr($stringSearchSQL,1,strlen($stringSearchSQL)-2);

	$alerts = array();
	foreach($alertsraw as $key => $alert){
		$finded = false;
		$alerts[$key]['disabled'] = $alert['disabled'];
		$alerts[$key]['id_agente'] = modules_get_agentmodule_agent($alert['id_agent_module']);
		$alerts[$key]['agent_name'] = $alert['agent_name'];
		$alerts[$key]['module_name'] = $alert['agent_module_name'];
		$alerts[$key]['template_name'] = $alert['template_name'];
		$actions = alerts_get_alert_agent_module_actions($alert['id']);
		
		$actions_name = array();
		foreach($actions as $action) {
			$actions_name[] = $action['name'];
		}
		
		$alerts[$key]['actions'] = implode(',',$actions_name);
	}

	$totalAlerts = count($alerts);
	
	if ($only_count) {
		unset($alerts);
	}
}
?>
