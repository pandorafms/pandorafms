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
	pandora_audit("ACL Violation",
		"Trying to access massive alert deletion");
	require ("general/noaccess.php");
	return;
}

require_once ('include/functions_agents.php');
require_once ('include/functions_alerts.php');

if (is_ajax ()) {
	$get_agents = (bool) get_parameter ('get_agents');
	
	if ($get_agents) {
		$id_group = (int) get_parameter ('id_group');
		$id_alert_template = (int) get_parameter ('id_alert_template');
		
		$agents_alerts = get_agents_with_alert_template ($id_alert_template, $id_group,
			false, array ('tagente.nombre', 'tagente.id_agente'));
		
		echo json_encode (index_array ($agents_alerts, 'id_agente', 'nombre'));
		return;
	}
	return;
}

function process_manage_delete ($id_alert_template, $id_agents, $module_names) {
	if (empty ($id_alert_template)) {
		echo '<h3 class="error">'.__('No alert selected').'</h3>';
		return false;
	}
	
	if (empty ($id_agents)) {
		echo '<h3 class="error">'.__('No agents selected').'</h3>';
		return false;
	}
	
	foreach($module_names as $module){
		foreach($id_agents as $id_agent) {
			 $module_id = get_agentmodule_id($module, $id_agent);
			 $modules_id[] = $module_id['id_agente_modulo'];
		}
	}
	
	if(count($module_names) == 1 && $module_names[0] == '0'){
		$modules_id = get_agents_common_modules_with_alerts ($id_agents, false, true);
	}

	$conttotal = 0;
	$contsuccess = 0;
	foreach($modules_id as $module){
		$success = delete_alert_agent_module (false,
		array ('id_agent_module' => $module,
			'id_alert_template' => $id_alert_template));		

		if($success)
			$contsuccess ++;
		$conttotal ++;
	}
	
	print_result_message ($contsuccess > 0,
	__('Successfully deleted')."(".$contsuccess."/".$conttotal.")",
	__('Could not be deleted'));	

}

$id_group = (int) get_parameter ('id_group');
$id_agents = get_parameter ('id_agents');
$module_names = get_parameter ('module');
$id_alert_template = (int) get_parameter ('id_alert_template');

$delete = (bool) get_parameter_post ('delete');

if ($delete) {
	process_manage_delete ($id_alert_template, $id_agents, $module_names);
}

$groups = get_user_groups ();

$table->id = 'delete_table';
$table->width = '95%';
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

$templates = get_alert_templates (false, array ('id', 'name'));
$table->data[0][0] = __('Alert template');
$table->data[0][1] = print_select (index_array ($templates, 'id', 'name'),
	'id_alert_template', $id_alert_template, false, __('Select'), 0, true);
$table->data[0][2] = '';
$table->data[0][3] = '';

$table->data[1][0] = __('Group');
$table->data[1][1] = print_select_groups(false, "AR", true, 'id_group', $id_group,
	'', '', '', true, false, true, '', $id_alert_template == 0);
$table->data[1][2] = '';
$table->data[1][3] = '';

$table->data[2][0] = __('Agents');
$table->data[2][0] .= '<span id="agent_loading" class="invisible">';
$table->data[2][0] .= '<img src="images/spinner.png" />';
$table->data[2][0] .= '</span>';
$agents_alerts = get_agents_with_alert_template ($id_alert_template, $id_group,
	false, array ('tagente.nombre', 'tagente.id_agente'));
$table->data[2][1] = print_select (index_array ($agents_alerts, 'id_agente', 'nombre'),
	'id_agents[]', '', '', '', '', true, true, true, '', $id_alert_template == 0);
$table->data[2][2] = __('Modules');
$table->data[2][3] = print_select (array(), 'module[]',	'', false, '', '', true, true, false);

echo '<form method="post" action="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&option=delete_alerts" onsubmit="if (! confirm(\''.__('Are you sure?').'\')) return false;">';
print_table ($table);

echo '<div class="action-buttons" style="width: '.$table->width.'" onsubmit="if (!confirm(\' '.__('Are you sure?').'\')) return false;">';
print_input_hidden ('delete', 1);
print_submit_button (__('Delete'), 'go', false, 'class="sub delete"');
echo '</div>';
echo '</form>';

//Hack to translate text "none" in PHP to javascript
echo '<span id ="none_text" style="display: none;">' . __('None') . '</span>';

echo '<h3 class="error invisible" id="message"> </h3>';

require_jquery_file ('form');
require_jquery_file ('pandora.controls');
	
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
});
/* ]]> */
</script>
