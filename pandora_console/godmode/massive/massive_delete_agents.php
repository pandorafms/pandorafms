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

if (! check_acl ($config['id_user'], 0, "AW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access massive agent deletion section");
	require ("general/noaccess.php");
	return;
}

require_once ('include/functions_agents.php');
require_once ('include/functions_alerts.php');
require_once ('include/functions_modules.php');
require_once ('include/functions_users.php');

function process_manage_delete ($id_agents) {
	if (empty ($id_agents)) {
		ui_print_error_message(__('No agents selected'));
		return false;
	}
	
	$id_agents = (array) $id_agents;
	
	$copy_modules = (bool) get_parameter ('copy_modules');
	$copy_alerts = (bool) get_parameter ('copy_alerts');
	
	$error = false;
	$count_deleted = 0;
	$agent_id_restore = 0;
	foreach ($id_agents as $id_agent) {
		$success = agents_delete_agent ($id_agent);
		if (! $success) {
			$agent_id_restore = $id_agent;
			break;
		}	
		$count_deleted++;
	}
	
	if (! $success) {
		ui_print_error_message(__('There was an error deleting the agent, the operation has been cancelled') . '.&nbsp;' . __('Could not delete agent').' '.agents_get_name ($agent_id_restore));
		
		return false;
	}
	else {
		ui_print_success_message(__('Successfully deleted') . '&nbsp;(' . $count_deleted . ')');
		
		return true;
	}
}

$id_group = (int) get_parameter ('id_group');
$id_agents = get_parameter ('id_agents');
$recursion = get_parameter('recursion');

$delete = (bool) get_parameter_post ('delete');

if ($delete) {
	$result = process_manage_delete ($id_agents);
	
	if ($result) {
		db_pandora_audit("Masive management", "Delete agent ", false, false,
			'Agent: ' . json_encode($id_agents));
	}
	else {
		db_pandora_audit("Masive management", "Fail try to delete agent", false, false,
			'Agent: ' . json_encode($id_agents));
	}
}

$groups = users_get_groups ();

$table->id = 'delete_table';
$table->width = '98%';
$table->data = array ();
$table->style = array ();
$table->style[0] = 'font-weight: bold; vertical-align:top';
$table->style[2] = 'font-weight: bold';
$table->size = array ();
$table->size[0] = '15%';
$table->size[1] = '35%';
$table->size[2] = '15%';
$table->size[3] = '35%';

$table->data = array ();
$table->data[0][0] = __('Group');
$table->data[0][1] = html_print_select_groups(false, "AR", true, 'id_group', $id_group,
	false, '', '', true);
$table->data[0][2] = __('Group recursion');
$table->data[0][3] = html_print_checkbox ("recursion", 1, $recursion, true, false);

$table->data[1][0] = __('Agents');
$table->data[1][0] .= '<span id="agent_loading" class="invisible">';
$table->data[1][0] .= html_print_image('images/spinner.png', true);
$table->data[1][0] .= '</span>';
$table->data[1][1] = html_print_select (agents_get_group_agents ($id_group, false, "none"),
	'id_agents[]', 0, false, '', '', true, true);

echo '<form method="post" id="form_agents" action="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&option=delete_agents">';
html_print_table ($table);

echo '<div class="action-buttons" style="width: '.$table->width.'" onsubmit="if (!confirm(\' '.__('Are you sure?').'\')) return false;">';
html_print_input_hidden ('delete', 1);
html_print_submit_button (__('Delete'), 'go', false, 'class="sub delete"');
echo '</div>';
echo '</form>';

echo '<h3 class="error invisible" id="message"> </h3>';

ui_require_jquery_file ('form');
ui_require_jquery_file ('pandora.controls');
?>

<script type="text/javascript">
$(document).ready (function () {
	var recursion;
	$("#checkbox-recursion").click(function (){
		recursion = this.checked ? 1 : 0;
		$("#id_group").trigger("change");
	});
	$("#id_group").pandoraSelectGroupAgent ({
		agentSelect: "select#id_agents",
		recursion: function() {return recursion}
	});
});
</script>
