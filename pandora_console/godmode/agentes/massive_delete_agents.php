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
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access massive agent deletion section");
	require ("general/noaccess.php");
	return;
}

require_once ('include/functions_agents.php');
require_once ('include/functions_alerts.php');
require_once ('include/functions_modules.php');

function process_manage_delete ($id_agents) {
	if (empty ($id_agents)) {
		echo '<h3 class="error">'.__('No agents selected').'</h3>';
		return false;
	}
	
	$id_agents = (array) $id_agents;
	
	$copy_modules = (bool) get_parameter ('copy_modules');
	$copy_alerts = (bool) get_parameter ('copy_alerts');
	
	process_sql_begin ();
	
	$error = false;
	foreach ($id_agents as $id_agent) {
		$success = delete_agent ($id_agent);
		if (! $success)
			break;
	}
	
	if (! $success) {
		echo '<h3 class="error">'.__('There was an error deleting the agent, the operation has been cancelled').'</h3>';
		echo '<h4>'.__('Could not delete agent').' '.get_agent_name ($id_agent).'</h4>';
		process_sql_rollback ();
	} else {
		echo '<h3 class="suc">'.__('Successfully deleted').'</h3>';
		process_sql_commit ();
	}
}

$id_group = (int) get_parameter ('id_group');
$id_agents = get_parameter ('id_agents');

$delete = (bool) get_parameter_post ('delete');

echo '<h3>'.__('Massive agent deletion').'</h3>';

if ($delete) {
	process_manage_delete ($id_agents);
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
$table->data[0][1] = print_select ($groups, 'id_group', $id_group,
	false, '', '', true);

$table->data[1][0] = __('Agents');
$table->data[1][0] .= '<span id="agent_loading" class="invisible">';
$table->data[1][0] .= '<img src="images/spinner.gif" />';
$table->data[1][0] .= '</span>';
$table->data[1][1] = print_select (get_group_agents ($id_group, false, "none"),
	'id_agents[]', 0, false, '', '', true, true);

echo '<form method="post" onsubmit="if (! confirm(\''.__('Are you sure?').'\')) return false;">';
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
</script>
