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

// Load global vars
check_login ();

if (! give_acl ($config['id_user'], 0, "AW")) {
	audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "ACL Violation",
		"Trying to access massive agent deletion section");
	require ("general/noaccess.php");
	return;
}

require_once ('include/functions_agents.php');
require_once ('include/functions_alerts.php');

$id_group = (int) get_parameter ('id_group');
$id_agents = get_parameter ('id_agents');

$delete = (bool) get_parameter_post ('delete');

if ($delete) {
	if(empty($id_agents))
		print_result_message (false, '', __('Could not be deleted').". ".__('No agents selected'));
	else {
		$action = (int) get_parameter ('action');
		
		if($action > 0){
			$agent_alerts = get_agent_alerts($id_agents);
			
			$alerts_agent_modules = array();
			foreach($agent_alerts['simple'] as $agent_alert){
				$alerts_agent_modules = array_merge($alerts_agent_modules, get_alerts_agent_module ($agent_alert['id_agent_module'], true, false, 'id'));
			}
			
			foreach($agent_alerts['compounds'] as $agent_alert){
				$alerts_agent_modules = array_merge($alerts_agent_modules, get_alerts_agent_module ($agent_alert['id'], false, false, 'id'));
			}

				
			$results = true;
			$agent_module_actions = array();
			
			foreach($alerts_agent_modules as $alert_agent_module){
				$agent_module_actions = get_alert_agent_module_actions ($alert_agent_module['id'], array('id','id_alert_action'));
				
				foreach ($agent_module_actions as $agent_module_action){
					if($agent_module_action['id_alert_action'] == $action) {
						echo $agent_module_action['id']." . ". $alert_agent_module['id'] ." ; ";
						$result = delete_alert_agent_module_action ($agent_module_action['id']);
						
						if($result === false)
							$results = false;
					}
				}
			}
			
			print_result_message ($results, __('Successfully deleted'), __('Could not be deleted')/*.": ". $agent_alerts['simple'][0]['id']*/);
		}
		else {
			print_result_message (false, '', __('Could not be deleted').". ".__('No action selected'));
		}
	}

}

$groups = get_user_groups ();

$table->id = 'delete_table';
$table->width = '95%';
$table->data = array ();
$table->style = array ();
$table->style[0] = 'font-weight: bold; vertical-align:top';
$table->style[2] = 'font-weight: bold';
$table->size = array ();
$table->size[0] = '15%';
$table->size[1] = '85%';

$table->data = array ();
$table->data[0][0] = __('Group');
$table->data[0][1] = print_select_groups(false, "AR", true, 'id_group', $id_group,
	false, '', '', true);

$table->data[1][0] = __('Agents');
$table->data[1][0] .= '<span id="agent_loading" class="invisible">';
$table->data[1][0] .= '<img src="images/spinner.png" />';
$table->data[1][0] .= '</span>';
$table->data[1][1] = print_select (get_group_agents ($id_group, false, "none"),
	'id_agents[]', 0, false, '', '', true, true);
	
$actions = get_alert_actions ();
$table->data[2][0] = __('Action');
$table->data[2][1] = print_select ($actions, 'action', '', '', __('None'), 0, true);	

echo '<form method="post" action="index.php?sec=gagente&sec2=godmode/massive/massive_operations&option=delete_action_alerts" onsubmit="if (! confirm(\''.__('Are you sure?').'\')) return false;">';
print_table ($table);

echo '<div class="action-buttons" style="width: '.$table->width.'" onsubmit="if (!confirm(\' '.__('Are you sure?').'\')) return false;">';
print_input_hidden ('delete', 1);
print_submit_button (__('Delete'), 'go', false, 'class="sub delete"');
echo '</div>';
echo '</form>';

echo '<h3 class="error invisible" id="message"> </h3>';

require_jquery_file ('form');
require_jquery_file ('pandora.controls');
?>

<script type="text/javascript">
$(document).ready (function () {
	$("#id_group").pandoraSelectGroupAgent ({
		agentSelect: "select#id_agents"
	});
});
/* ]]> */
</script>
