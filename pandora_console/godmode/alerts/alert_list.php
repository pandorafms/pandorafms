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

if (! give_acl ($config['id_user'], 0, "LW")) {
	audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "ACL Violation",
		"Trying to access Alert Management");
	require ("general/noaccess.php");
	exit;
}

require_once ('include/functions_agents.php');
require_once ('include/functions_alerts.php');
$isFunctionPolicies = enterprise_include ('include/functions_policies.php');

$id_group = 0;
/* Check if this page is included from a agent edition */
if (isset ($id_agente)) {
	$id_group = get_agent_group ($id_agente);
}
else {
	$id_agente = 0;
}

$create_alert = (bool) get_parameter ('create_alert');
$add_action = (bool) get_parameter ('add_action');
$delete_action = (bool) get_parameter ('delete_action');
$delete_alert = (bool) get_parameter ('delete_alert');
$disable_alert = (bool) get_parameter ('disable_alert');
$enable_alert = (bool) get_parameter ('enable_alert');
$tab = get_parameter('tab', 'list');
$group = get_parameter('group', 1); //1 is All group
$templateName = get_parameter('template_name','');
$moduleName = get_parameter('module_name','');
$agentID = get_parameter('agent_id','');
$agentName = get_parameter('agent_name','');
$actionID = get_parameter('action_id','');
$fieldContent = get_parameter('field_content','');
$searchType = get_parameter('search_type','');
$priority = get_parameter('priority','');
$searchFlag = get_parameter('search',0);

$messageAction = '';

if ($create_alert) {
	$id_alert_template = (int) get_parameter ('template');
	$id_agent_module = (int) get_parameter ('id_agent_module');
	
	if (get_db_value_sql("SELECT COUNT(id)
		FROM talert_template_modules
		WHERE id_agent_module = " . $id_agent_module . "
			AND id_alert_template = " . $id_alert_template) > 0) {
		$messageAction = print_result_message (false, '', __('Already added'), '', true);
	}
	else {
		$id = create_alert_agent_module ($id_agent_module, $id_alert_template);

		$alert_template_name = get_db_value ("name", "talert_templates","id", $id_alert_template);
		$module_name = get_db_value ("nombre", "tagente_modulo","id_agente_modulo", $id_agent_module);
		$agent_name = get_agent_name (get_db_value ("id_agente", "tagente_modulo","id_agente_modulo", $id_agent_module)); 

		audit_db ($config["id_user"],$_SERVER['REMOTE_ADDR'], "Alert management",
		"Added alert '$alert_template_name' for module '$module_name' in agent '$agent_name'");

		$messageAction =  print_result_message ($id, __('Successfully created'), __('Could not be created'), '', true);
		if ($id !== false) {
			$action_select = get_parameter('action_select');
			
			if ($action_select != 0) {
				$values = array();
				$values['fires_min'] = get_parameter ('fires_min');
				$values['fires_max'] = get_parameter ('fires_max');
				
				add_alert_agent_module_action ($id, $action_select, $values);
			}
		}
	}
}

if ($delete_alert) {
	$id_alert_agent_module = (int) get_parameter ('id_alert');
	
	$temp =  get_db_row ("talert_template_modules","id", $id_alert_agent_module);
	$id_alert_template = $temp["id_alert_template"];
	$id_agent_module = $temp["id_agent_module"];
	$alert_template_name = get_db_value ("name", "talert_templates","id", $id_alert_template);
	$module_name = get_db_value ("nombre", "tagente_modulo","id_agente_modulo", $id_agent_module);
	$agent_name = get_agent_name (get_db_value ("id_agente", "tagente_modulo","id_agente_modulo", $id_agent_module)); 

	audit_db ($config["id_user"],$_SERVER['REMOTE_ADDR'], "Alert management",
	"Deleted alert '$alert_template_name' for module '$module_name' in agent '$agent_name'");

	$result = delete_alert_agent_module ($id_alert_agent_module);
	$messageAction = print_result_message ($id, __('Successfully deleted'), __('Could not be deleted'), '', true);
}

if ($add_action) {
	$id_action = (int) get_parameter ('action');
	$id_alert_module = (int) get_parameter ('id_alert_module');
	$fires_min = (int) get_parameter ('fires_min');
	$fires_max = (int) get_parameter ('fires_max');
	$values = array ();
	if ($fires_min != -1)
		$values['fires_min'] = $fires_min;
	if ($fires_max != -1)
		$values['fires_max'] = $fires_max;
	
	$result = add_alert_agent_module_action ($id_alert_module, $id_action, $values);
	$messageAction = print_result_message ($id, __('Successfully added'), __('Could not be added'), '', true);
}

if ($delete_action) {
	$id_action = (int) get_parameter ('id_action');
	$id_alert = (int) get_parameter ('id_alert');
	
	$result = delete_alert_agent_module_action ($id_action);
	$messageAction = print_result_message ($id, __('Successfully deleted'), __('Could not be deleted'), '', true);
}

if ($enable_alert) {
	$id_alert = (int) get_parameter ('id_alert');
	
	$result = set_alerts_agent_module_disable ($id_alert, false);
	$messageAction = print_result_message ($result, __('Successfully enabled'), __('Could not be enabled'), '', true);
}

if ($disable_alert) {
	$id_alert = (int) get_parameter ('id_alert');
	
	$result = set_alerts_agent_module_disable ($id_alert, true);
	$messageAction = print_result_message ($result, __('Successfully disabled'), __('Could not be disabled'), '', true);
}

// Header
if ($id_agente) {
	$agents = array ($id_agente => get_agent_name ($id_agente));
	
	if ($group == 1) {
		$groups = get_user_groups ();
	}
	else {
		$groups = array(1 => __('All'));
	}
	
	echo $messageAction;
	
	require_once('godmode/alerts/alert_list.list.php');
	require_once('godmode/alerts/alert_list.builder.php');
	return;
}
else {
	$buttons = array(
		'list' => array(
			'active' => false,
			'text' => '<a href="index.php?sec=galertas&sec2=godmode/alerts/alert_list&tab=list">' . 
				print_image ("images/god6.png", true, array ("title" => __('List alerts'))) .'</a>'),
		'builder' => array(
			'active' => false,
			'text' => '<a href="index.php?sec=galertas&sec2=godmode/alerts/alert_list&tab=builder">' . 
				print_image ("images/config.png", true, array ("title" => __('Builder alert'))) .'</a>'));
	
	$buttons[$tab]['active'] = true;
				
	print_page_header(__('Alerts') . ' &raquo; ' . __('Manage alerts') . ' &raquo; ' . __('List'), "images/god2.png", false, "manage_alert_list", true, $buttons);	
	
	echo $messageAction;
	
	switch ($tab) {
		case 'list':
			if ($group == 1) {
				$groups = get_user_groups ();
			}
			else {
				$groups = array(1 => __('All'));
			}
			$agents = get_group_agents (array_keys ($groups), false, "none");
					
			require_once('godmode/alerts/alert_list.list.php');
			return;
			break;
		case 'builder':
			if ($group == 1) {
				$groups = get_user_groups ();
			}
			else {
				$groups = array(1 => __('All'));
			}
			
			require_once('godmode/alerts/alert_list.builder.php');
			return;
			break;
	}
}
?>
