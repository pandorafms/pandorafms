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
		
		$agents_alerts = get_group_agents ($id_group);
		
		echo json_encode ($agents_alerts);
		return;
	}
	return;
}

function process_manage_add ($id_alert_template, $id_agents) {
	if (empty ($id_agents)) {
		echo '<h3 class="error">'.__('No agents selected').'</h3>';
		return false;
	}
	
	if (empty ($id_alert_template)) {
		echo '<h3 class="error">'.__('No alert selected').'</h3>';
		return false;
	}
	
	process_sql_begin ();
	$modules = get_agent_modules ($id_agents, 'id_agente_modulo', false, true);
	$contfail = 0;
	$contsuccess = 0;
	foreach($modules as $module){
		$success = create_alert_agent_module ($module, $id_alert_template);
		if(!$success)
			$contfail = $contfail + 1;
		else
			$contsuccess = $contsuccess + 1;
	}
	
	if ($contfail > 0) {
		echo '<h3 class="error">'.__('There was an error adding the alerts, the operation has been cancelled').'</h3>';
		echo '<h3 class="error">'.__('Could not add alerts').'</h3>';
		process_sql_rollback ();
	}
	else {
		echo '<h3 class="suc">'.__('Successfully added').' '.$contsuccess.' '.__('Alerts').'</h3>';
		process_sql_commit ();
	}
}

$id_group = (int) get_parameter ('id_group', -1);
$id_agents = get_parameter ('id_agents');
$id_alert_template = (int) get_parameter ('id_alert_template');

$add = (bool) get_parameter_post ('add');

if ($add) {
	process_manage_add ($id_alert_template, $id_agents);
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
	'', 'Select', -1, true, false, true, '', false);

$table->data[1][0] = __('Agent');
$table->data[1][0] .= '<span id="agent_loading" class="invisible">';
$table->data[1][0] .= '<img src="images/spinner.png" />';
$table->data[1][0] .= '</span>';
$agents_alerts = get_agents_with_alert_template ($id_alert_template, $id_group,
	false, array ('tagente.nombre', 'tagente.id_agente'));
$table->data[1][1] = print_select (index_array ($agents_alerts, 'id_agente', 'nombre'),
	'id_agents[]', '', '', '', '', true, true, true, '', false);
	
$templates = get_alert_templates (false, array ('id', 'name'));
$table->data[2][0] = __('Alert template');
$table->data[2][1] = print_select (index_array ($templates, 'id', 'name'),
	'id_alert_template', $id_alert_template, false, __('Select'), 0, true);
	
echo '<form method="post" action="index.php?sec=gagente&sec2=godmode/massive/massive_operations&option=add_alerts" onsubmit="if (! confirm(\''.__('Are you sure?').'\')) return false;">';
print_table ($table);

echo '<div class="action-buttons" style="width: '.$table->width.'" onsubmit="if (!confirm(\' '.__('Are you sure?').'\')) return false;">';
print_input_hidden ('add', 1);
print_submit_button (__('Add'), 'go', false, 'class="sub add"');
echo '</div>';
echo '</form>';

echo '<h3 class="error invisible" id="message"> </h3>';

require_jquery_file ('form');
require_jquery_file ('pandora.controls');
?>

<script type="text/javascript">
/* <![CDATA[ */
$(document).ready (function () {
	$("#id_group").change (function () {
		var $select = $("#id_agents").enable ();
		$("#agent_loading").show ();
		$("option", $select).remove ();
		
		jQuery.post ("ajax.php",
			{"page" : "godmode/massive/massive_add_alerts",
			"get_agents" : 1,
			"id_group" : this.value
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
	
	$("#id_group").value = "0";
});
/* ]]> */
</script>
