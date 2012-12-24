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
		"Trying to access massive alert deletion");
	require ("general/noaccess.php");
	return;
}

require_once ('include/functions_agents.php');
require_once ('include/functions_alerts.php');
require_once ($config['homedir'] . "/include/functions_modules.php");
require_once ($config['homedir'].'/include/functions_users.php');

if (is_ajax ()) {
	$get_agents = (bool) get_parameter ('get_agents');
	$recursion = (int) get_parameter ('recursion');
	
	if ($get_agents) {
		$id_group = (int) get_parameter ('id_group');
		$id_alert_template = (int) get_parameter ('id_alert_template');
		
		if ($recursion) {
			$groups = groups_get_id_recursive($id_group, true);
		}
		else {
			$groups = array($id_group);
		}

		$agents_alerts = array();
		foreach( $groups as $group ) {
			$agents_alerts_one_group = alerts_get_agents_with_alert_template ($id_alert_template, $group,
					false, array ('tagente.nombre', 'tagente.id_agente'));
			if (is_array($agents_alerts_one_group)) {
				$agents_alerts = array_merge($agents_alerts, $agents_alerts_one_group);
			}
		}
		
		echo json_encode (index_array ($agents_alerts, 'id_agente', 'nombre'));
		return;
	}
	return;
}

function process_manage_delete ($id_alert_template, $id_agents, $module_names) {
	if (empty ($id_alert_template)) {
		ui_print_error_message(__('No alert selected'));
		return false;
	}
	
	if (empty ($id_agents) || $id_agents[0] == 0) {
		ui_print_error_message(__('No agents selected'));
		return false;
	}
	
	$module_selection_mode =  get_parameter('modules_selection_mode');
		
	foreach($module_names as $module){
		foreach($id_agents as $id_agent) {
			 $module_id = modules_get_agentmodule_id($module, $id_agent);
			 $modules_id[] = $module_id['id_agente_modulo'];
		}
	}

	// If is selected "ANY" option then we need the module selection mode: common or all modules
	if (count($module_names) == 1 && $module_names[0] == '0') {

		if ($module_selection_mode == 'common')
				$modules_id = agents_common_modules_with_alerts ($id_agents, false, true);
		else {
			// For agents selected
			$modules_id = array();

			foreach ($id_agents as $id_agent) {
				$current_modules_agent = agents_get_modules($id_agent, 'id_agente_modulo', array ('disabled' => 0));

				if ($current_modules_agent != false) {
					// And their modules
					foreach ($current_modules_agent as $current_module) {
						$module_alerts = alerts_get_alerts_agent_module($current_module);
						if ($module_alerts !=  false) {
							// And for all alert in modules
							foreach ($module_alerts as $module_alert) {
								// Find the template in module
								if ($module_alert['id_alert_template'] == $id_alert_template)
									$modules_id[] = $module_alert['id_agent_module'];
							}
						}
					}
				}
			}
		}
	}

	$conttotal = 0;
	$contsuccess = 0;
	foreach($modules_id as $module){
		$success = alerts_delete_alert_agent_module (false,
		array ('id_agent_module' => $module,
			'id_alert_template' => $id_alert_template));		

		if($success)
			$contsuccess ++;
		$conttotal ++;
	}
	
	ui_print_result_message ($contsuccess > 0,
	__('Successfully deleted')."(".$contsuccess."/".$conttotal.")",
	__('Could not be deleted'));

	
	return (bool)($contsuccess > 0);
}

$id_group = (int) get_parameter ('id_group');
$id_agents = get_parameter ('id_agents');
$module_names = get_parameter ('module');
$id_alert_template = (int) get_parameter ('id_alert_template');

$delete = (bool) get_parameter_post ('delete');

if ($delete) {
	$result = process_manage_delete ($id_alert_template, $id_agents, $module_names);
	
	if ($result) {
		db_pandora_audit("Masive management", "Delete alert ", false, false,
			'Agent: ' . json_encode($id_agents) . ' Template: ' . $id_alert_template . ' Module: ' . $module_names);
	}
	else {
		db_pandora_audit("Masive management", "Fail try to delete alert", false, false,
			'Agent: ' . json_encode($id_agents) . ' Template: ' . $id_alert_template . ' Module: ' . $module_names);
	}
}

$groups = users_get_groups ();

$table->id = 'delete_table';
$table->width = '98%';
$table->data = array ();
$table->style = array ();
$table->style[0] = 'font-weight: bold; vertical-align:top';
$table->style[2] = 'font-weight: bold; vertical-align:top';
$table->size = array ();
$table->size[0] = '15%';
$table->size[1] = '40%';
$table->size[2] = '15%';
$table->size[3] = '40%';

$table->data = array ();

$templates = alerts_get_alert_templates (false, array ('id', 'name'));
$table->data[0][0] = __('Alert template');
$table->data[0][1] = html_print_select (index_array ($templates, 'id', 'name'),
	'id_alert_template', $id_alert_template, false, __('Select'), 0, true);
$table->data[0][2] = '';
$table->data[0][3] = '';

$table->data[1][0] = __('Group');
$table->data[1][1] = html_print_select_groups(false, "AR", true, 'id_group', $id_group,
	'', '', '', true, false, true, '', $id_alert_template == 0);
$table->data[1][2] = __('Group recursion');
$table->data[1][3] = html_print_checkbox ("recursion", 1, false, true, false);

$table->data[2][0] = __('Agents');
$table->data[2][0] .= '<span id="agent_loading" class="invisible">';
$table->data[2][0] .= html_print_image('images/spinner.png', true);
$table->data[2][0] .= '</span>';
$agents_alerts = alerts_get_agents_with_alert_template ($id_alert_template, $id_group,
	false, array ('tagente.nombre', 'tagente.id_agente'));
$table->data[2][1] = html_print_select (index_array ($agents_alerts, 'id_agente', 'nombre'),
	'id_agents[]', '', '', '', '', true, true, true, '', $id_alert_template == 0);
$table->data[2][2] = __('When select agents');
$table->data[2][2] .= '<br>';
$table->data[2][2] .= html_print_select (array('common' => __('Show common modules'), 'all' => __('Show all modules')), 'modules_selection_mode',
	'common', false, '', '', true);
$table->data[2][3] = html_print_select (array(), 'module[]',	'', false, '', '', true, true, false);

echo '<form method="post" id="form_alerts" action="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&option=delete_alerts" >';
html_print_table ($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
html_print_input_hidden ('delete', 1);
html_print_submit_button (__('Delete'), 'go', false, 'class="sub delete"');
echo '</div>';
echo '</form>';

//Hack to translate text "none" in PHP to javascript
echo '<span id ="none_text" style="display: none;">' . __('None') . '</span>';

echo '<h3 class="error invisible" id="message"> </h3>';

ui_require_jquery_file ('form');
ui_require_jquery_file ('pandora.controls');
	
?>

<script type="text/javascript">
/* <![CDATA[ */
$(document).ready (function () {
	$("#id_agents").change(agent_changed_by_multiple_agents_with_alerts);

	$("#id_alert_template").change (function () {
		if (this.value != 0) {
			$("#id_agents").enable ();
			$("#id_group").enable ().change ();
		} else {
			$("#id_group, #id_agents").disable ();
		}
	});
	
	$("#id_group").change (function () {
		var $select = $("#id_agents").disable ();
		$("#agent_loading").show ();
		$("option", $select).remove ();
		
		jQuery.post ("ajax.php",
			{"page" : "godmode/massive/massive_delete_alerts",
			"get_agents" : 1,
			"id_group" : this.value,
			"recursion" : $("#checkbox-recursion").attr ("checked") ? 1 : 0,
			"id_alert_template" : $("#id_alert_template").attr ("value")
			},
			function (data, status) {
				options = "";
				jQuery.each (data, function (id, value) {
					options += "<option value=\""+id+"\">"+value+"</option>";
				});
				$("#id_agents").append (options);
				$("#agent_loading").hide ();
				$select.enable ();
			},
			"json"
		);
	});

	$("#checkbox-recursion").click(function (){
		$("#id_group").trigger("change");
	});
	
	$("#modules_selection_mode").change (function() {
		$("#id_agents").trigger('change');
	});	
});
/* ]]> */
</script>
