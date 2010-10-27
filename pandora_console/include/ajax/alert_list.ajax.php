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
	pandora_audit("ACL Violation",
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

if ($get_agent_alerts_simple) {
	$id_agent = (int) get_parameter ('id_agent');
	if ($id_agent <= 0) {
		echo json_encode (false);
		return;
	}
	$id_group = get_agent_group ($id_agent);
	
	if (! give_acl ($config['id_user'], $id_group, "AR")) {
		pandora_audit("ACL Violation",
			"Trying to access Alert Management");
		echo json_encode (false);
		return;
	}
	
	require_once ('include/functions_agents.php');
	require_once ('include/functions_alerts.php');
	
	$alerts = get_agent_alerts_simple ($id_agent);
	if (empty ($alerts)) {
		echo json_encode (false);
		return;
	}
	
	$retval = array ();
	foreach ($alerts as $alert) {
		$alert['template'] = get_alert_template ($alert['id_alert_template']);
		$alert['module_name'] = get_agentmodule_name ($alert['id_agent_module']);
		$alert['agent_name'] = get_agentmodule_agent_name ($alert['id_agent_module']);
		$retval[$alert['id']] = $alert;
	}
	
	echo json_encode ($retval);
	return;
}

if ($enable_alert) {
	$id_alert = (int) get_parameter ('id_alert');

	$result = set_alerts_agent_module_disable ($id_alert, false);
	if ($result)
		echo __('Successfully enabled');
	else
		echo __('Could not be enabled');
	return;
}

if ($disable_alert) {
	$id_alert = (int) get_parameter ('id_alert');

	$result = set_alerts_agent_module_disable ($id_alert, true);
	if ($result)
		echo __('Successfully disabled');
	else
		echo __('Could not be disabled');
	return;
}
return;
?>