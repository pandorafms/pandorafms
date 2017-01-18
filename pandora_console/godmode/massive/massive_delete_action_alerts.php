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
require_once ('include/functions_users.php');

if (is_ajax ()) {
	$get_alerts = (bool) get_parameter ('get_alerts');
	
	if ($get_alerts) {
		$id_agents = get_parameter ('id_agents');
		if (empty($id_agents)) {
			echo json_encode ('');
			return;
		}
		$alert_templates = agents_get_alerts_simple ($id_agents);
		echo json_encode (index_array ($alert_templates, 'id_alert_template', 'template_name'));
		return;
	}
	return;
}

$id_group = (int) get_parameter ('id_group');
$id_agents = get_parameter ('id_agents');
$id_alert_templates = (array) get_parameter ('id_alert_templates');
$recursion = get_parameter ('recursion');

$delete = (bool) get_parameter_post ('delete');

if ($delete) {
	if (empty($id_agents) || $id_agents[0] == 0)
		ui_print_result_message (false, '',
			__('Could not be deleted. No agents selected'));
	else {
		$actions = get_parameter ('action');
		
		if (!empty($actions)) {
			$modules = (array) get_parameter ('module');
			$modules_id = array();
			if (!empty($modules)) {
				foreach ($modules as $module) {
					foreach ($id_agents as $id_agent) {
						$module_id = modules_get_agentmodule_id($module, $id_agent);
						$modules_id[] = $module_id['id_agente_modulo'];
					}
				}
							
				$agent_alerts = agents_get_alerts($id_agents);
				$alerts_agent_modules = array();
				foreach ($agent_alerts['simple'] as $agent_alert) {
					if ((in_array($agent_alert['id_alert_template'], $id_alert_templates)) && (in_array($agent_alert['id_agent_module'], $modules_id))) {
						$alerts_agent_modules = array_merge($alerts_agent_modules, alerts_get_alerts_agent_module ($agent_alert['id_agent_module'], true, false, 'id'));
					}
				}
				
				if (empty($alerts_agent_modules)) {
					ui_print_result_message (false, '',
						__('Could not be deleted. No alerts selected'));
				}
				else {
					$results = true;
					$agent_module_actions = array();
					
					foreach ($alerts_agent_modules as $alert_agent_module) {
						$agent_module_actions = alerts_get_alert_agent_module_actions ($alert_agent_module['id'], array('id','id_alert_action'));
						
						foreach ($agent_module_actions as $agent_module_action) {
							foreach ($actions as $action) {
								if ($agent_module_action['id_alert_action'] == $action) {
									$result = alerts_delete_alert_agent_module_action ($agent_module_action['id']);
									
									if ($result === false)
										$results = false;
								}
							}
						}
					}
					
					if ($results) {
						db_pandora_audit("Massive management", "Delete alert action", false, false,
							'Agent: ' . json_encode($id_agents) . ' Alert templates: ' . json_encode($id_alert_templates) . 
							' Actions: ' . implode(',',$actions));
					}
					else {
						db_pandora_audit("Massive management", "Fail try to delete alert action", false, false,
							'Agent: ' . json_encode($id_agents) . ' Alert templates: ' . json_encode($id_alert_templates) . 
							' Actions: ' . implode(',',$actions));
					}
					
					ui_print_result_message ($results,
						__('Successfully deleted'),
						__('Could not be deleted'));
				}
			}
			else {
				ui_print_result_message (false, '', __('Could not be added').". ".__('No modules selected'));
			}
		}
		else {
			ui_print_result_message (false, '',
				__('Could not be deleted. No action selected'));
		}
	}

}

$groups = users_get_groups ();
$own_info = get_user_info($config['id_user']);
if (!$own_info['is_admin'] && !check_acl ($config['id_user'], 0, "AW"))
	$return_all_group = false;
else   
	$return_all_group = true;

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
$table->data[0][1] = html_print_select_groups(false, "AW", $return_all_group, 'id_group', $id_group,
	false, '', '', true);
$table->data[0][2] = __('Group recursion');
$table->data[0][3] = html_print_checkbox ("recursion", 1, $recursion,
	true, false);

$table->data[1][0] = __('Agents with templates');
$table->data[1][1] = html_print_select (array(),'id_agents[]', 0, false, '', '', true, true);

if (empty($id_agents)) {
	$alert_templates = '';
}
else {
	$alert_templates = agents_get_alerts_simple ($id_agents);
}
$table->data[2][0] = __('Alert templates');
$table->data[2][1] = html_print_select (index_array ($alert_templates, 'id_alert_template', 'template_name'), 'id_alert_templates[]', '', '', '', '', true, true, true, '', $alert_templates == 0);
$table->data[2][2] = __('When select agents');
$table->data[2][2] .= '<br>';
$table->data[2][2] .= html_print_select (
	array('common' => __('Show common modules'),
		'all' => __('Show all modules'),'unknown' => __('Show unknown and not init modules')),
	'modules_selection_mode',
	'common', false, '', '', true);
$table->data[2][3] = html_print_select (array(), 'module[]',
	$modules_select, false, '', '', true, true, false);


$actions = alerts_get_alert_actions ();
$table->data[3][0] = __('Action');
$table->data[3][1] = html_print_select ($actions, 'action[]', '', '',
	'', '', true, true);

echo '<form method="post" id="form_alert" action="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&option=delete_action_alerts">';
html_print_table ($table);

$sql = 'SELECT DISTINCT(id_agente)
	FROM tagente_modulo
	WHERE id_agente_modulo IN (
		SELECT id_agent_module
		FROM talert_template_modules)';
$agents_with_templates = db_get_all_rows_sql($sql);
$agents_with_templates_json = array();
foreach($agents_with_templates as $ag) {
	$agents_with_templates_json[] = $ag['id_agente'];
}
$agents_with_templates_json = json_encode($agents_with_templates_json);

echo "<input type='hidden' id='hidden-agents_with_templates' value='".$agents_with_templates_json."'>";

echo '<div class="action-buttons" style="width: '.$table->width.'" onsubmit="if (!confirm(\' '.__('Are you sure?').'\')) return false;">';
html_print_input_hidden ('delete', 1);
html_print_submit_button (__('Delete'), 'go', false, 'class="sub delete"');
echo '</div>';
echo '</form>';

echo '<h3 class="error invisible" id="message"></h3>';

ui_require_jquery_file('form');
ui_require_jquery_file('pandora.controls');
?>

<script type="text/javascript">
$(document).ready (function () {
	update_alerts();
	
	var filter_agents_json = $("#hidden-agents_with_templates").val();
	var recursion = false;
	$("#checkbox-recursion").click(function () {
		recursion = this.checked;
		$("#id_group").trigger("change");
	});	
	
	$("#id_group").pandoraSelectGroupAgent ({
		agentSelect: "select#id_agents",
		privilege: "AW",
		filter_agents_json: filter_agents_json,
		add_alert_bulk_op: true,
		callbackPost: function () {
			var $select_template = $("#id_alert_templates").disable ();
			$("option", $select_template).remove ();
		},
		recursion: function() {return recursion}
	});
	
	$("#id_agents").change (function () {
		update_alerts();
	});
	
	$("#id_alert_templates").change(alert_templates_changed_by_multiple_agents_with_alerts);
	
	$("#modules_selection_mode").click(function () {
		$("#id_alert_templates").trigger("change");
	});
	
	function update_alerts() {
		var idAgents = Array();
		jQuery.each ($("#id_agents option:selected"), function (i, val) {
			idAgents.push($(val).val());
		});
		$("#template_loading").show();
		
		var $select_template = $("#id_alert_templates").disable ();
		
		jQuery.post ("ajax.php",
			{"page" : "godmode/massive/massive_delete_action_alerts",
			"get_alerts" : 1,
			"id_agents[]" : idAgents
			},
			function (data, status) {
				$("option", $select_template).remove ();
				
				options = "";
				jQuery.each (data, function (id, value) {
					options += "<option value=\""+id+"\">"+value+"</option>";
				});
				$("#id_alert_templates").append (options);
				$("#template_loading").hide ();
				$select_template.enable ();
			},
			"json"
		);
	}
	
	$('#id_group').trigger('change');

});
/* ]]> */
</script>
