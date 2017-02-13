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
		ui_print_error_message(
			sprintf(
				__('There was an error deleting the agent, the operation has been cancelled Could not delete agent %s'),
				agents_get_name ($agent_id_restore)));
		
		return false;
	}
	else {
		ui_print_success_message(sprintf(__('Successfully deleted (%s)',
			$count_deleted)));
		
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
		db_pandora_audit("Massive management", "Delete agent ", false, false,
			'Agent: ' . json_encode($id_agents));
	}
	else {
		db_pandora_audit("Massive management", "Fail try to delete agent", false, false,
			'Agent: ' . json_encode($id_agents));
	}
}

$groups = users_get_groups ();

$table->id = 'delete_table';
$table->class = 'databox filters';
$table->width = '100%';
$table->data = array ();
$table->style = array ();
$table->style[0] = 'font-weight: bold;';
$table->style[2] = 'font-weight: bold';
$table->size = array ();
$table->size[0] = '15%';
$table->size[1] = '35%';
$table->size[2] = '15%';
$table->size[3] = '35%';

$table->data = array ();
$table->data[0][0] = __('Group');
$table->data[0][1] = html_print_select_groups(false, "AW", true,
	'id_group', $id_group, false, '', '', true);
$table->data[0][2] = __('Group recursion');
$table->data[0][3] = html_print_checkbox ("recursion", 1, $recursion,
	true, false);

$status_list = array ();
$status_list[AGENT_STATUS_NORMAL] = __('Normal');
$status_list[AGENT_STATUS_WARNING] = __('Warning');
$status_list[AGENT_STATUS_CRITICAL] = __('Critical');
$status_list[AGENT_STATUS_UNKNOWN] = __('Unknown');
$status_list[AGENT_STATUS_NOT_NORMAL] = __('Not normal');
$status_list[AGENT_STATUS_NOT_INIT] = __('Not init');
$table->data[1][0] = __('Status');
$table->data[1][1] = html_print_select($status_list, 'status_agents', 'selected',
	'', __('All'), AGENT_STATUS_ALL, true);

$table->data[1][2] = __('Show agents');
$table->data[1][3] = html_print_select (array(0 => 'Only enabled',1 => 'Only disabled'), 'disabled',2,'',__('All'),
			2,true,'','','','','width:30%;');

$table->data[2][0] = __('Agents');
$table->data[2][0] .= '<span id="agent_loading" class="invisible">';
$table->data[2][0] .= html_print_image('images/spinner.png', true);
$table->data[2][0] .= '</span>';
$table->data[2][1] = html_print_select(
	agents_get_group_agents(array_keys (users_get_groups ($config["id_user"], "AW", false)), false, "none"),
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
	var limit_parameters_massive = <?php echo $config['limit_parameters_massive']; ?>;
	
	$(document).ready (function () {
		$("#form_agents").submit(function() {
			var get_parameters_count = window.location.href.slice(
				window.location.href.indexOf('?') + 1).split('&').length;
			var post_parameters_count = $("#form_agents").serializeArray().length;
			
			var count_parameters =
				get_parameters_count + post_parameters_count;
			
			if (count_parameters > limit_parameters_massive) {
				alert("<?php echo __('Unsucessful sending the data, please contact with your administrator or make with less elements.'); ?>");
				return false;
			}
		});
		
		
		var recursion;
		
		$("#checkbox-recursion").click(function () {
			recursion = this.checked ? 1 : 0;
			
			$("#id_group").trigger("change");
		});
		
		var disabled;
		
		$("#disabled").click(function () {
		
				disabled = this.value;
		
			 $("#id_group").trigger("change");
		});
		
		$("#id_group").pandoraSelectGroupAgent ({
			status_agents: function () {
				return $("#status_agents").val();
			},
			agentSelect: "select#id_agents",
			privilege: "AW",
			recursion: function() {
				return recursion;
			},
			disabled: function() {
				return disabled;
			}
		});
		
		$("#status_agents").change(function() {
			$("#id_group").trigger("change");
		});
		
		disabled = 2;

	 $("#id_group").trigger("change");
	 
	});
</script>
