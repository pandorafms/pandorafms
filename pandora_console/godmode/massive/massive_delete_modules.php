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
		"Trying to access agent massive deletion");
	require ("general/noaccess.php");
	return;
}

require_once ('include/functions_agents.php');
require_once ('include/functions_modules.php');
require_once ('include/functions_users.php');

if (is_ajax ()) {
	$get_agents = (bool) get_parameter ('get_agents');
	
	if ($get_agents) {
		$id_group = (int) get_parameter ('id_group');
		$module_name = (string) get_parameter ('module_name');
		$recursion = (int) get_parameter ('recursion');
		
		$agents_modules = modules_get_agents_with_module_name ($module_name, $id_group,
			array ('delete_pending' => 0,
				'tagente_modulo.disabled' => 0),
			array ('tagente.id_agente', 'tagente.nombre'),
			$recursion);
		
		echo json_encode (index_array ($agents_modules, 'id_agente', 'nombre'));
		return;
	}
	return;
}

function process_manage_delete ($module_name, $id_agents) {
	if (empty ($module_name)) {
		echo '<h3 class="error">'.__('No module selected').'</h3>';
		return false;
	}
	
	if (empty ($id_agents)) {
		echo '<h3 class="error">'.__('No agents selected').'</h3>';
		return false;
	}
	
	db_process_sql_begin ();
	$modules = agents_get_modules ($id_agents, 'id_agente_modulo',
		array ('nombre' => $module_name), true);
	$success = modules_delete_agent_module ($modules);
	if (! $success) {
		echo '<h3 class="error">'.__('There was an error deleting the modules, the operation has been cancelled').'</h3>';
		echo '<h4>'.__('Could not delete modules').'</h4>';
		db_process_sql_rollback ();
		
		return false;
	}
	else {
		echo '<h3 class="suc">'.__('Successfully deleted').'</h3>';
		db_process_sql_commit ();
		
		return true;
	}
}

$id_group = (int) get_parameter ('id_group');
$id_agents = (array) get_parameter ('id_agents');
$module_name = (string) get_parameter ('module_name');
$recursion = get_parameter ('recursion');

$delete = (bool) get_parameter_post ('delete');

if ($delete) {
	$result = process_manage_delete ($module_name, $id_agents);
	if ($result) {
		db_pandora_audit("Massive management", "Delete module ", false, false,
			'Agent: ' . json_encode($id_agents) . ' Module: ' . $module_name);
	}
	else {
		db_pandora_audit("Massive management", "Fail try to delete module", false, false,
			'Agent: ' . json_encode($id_agents) . ' Module: ' . $module_name);
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

$table->data[0][0] = __('Modules');

$modules = agents_get_modules();
$modulesSelect = array();
foreach ($modules as $module) {
	$modulesSelect[$module] = io_safe_output($module);
}
$table->data[0][1] = html_print_select($modulesSelect,
	'module_name', $module_name, false, __('Select'), '', true);

$table->data[1][0] = __('Group');
$table->data[1][1] = html_print_select_groups(false, "AR", true, 'id_group', $id_group,
	false, '', '', true, false, true, '', empty ($module_name));
$table->data[1][2] = __('Group recursion');
$table->data[1][3] = html_print_checkbox ("recursion", 1, $recursion, true, false);

$table->data[2][0] = __('Agent');
$table->data[2][0] .= '<span id="agent_loading" class="invisible">';
$table->data[2][0] .= html_print_image('images/spinner.png', true);
$table->data[2][0] .= '</span>';
$agents = modules_get_agents_with_module_name ($module_name, $id_group,
	array ('delete_pending' => 0,
		'tagente_modulo.disabled' => 0),
	array ('tagente.id_agente', 'tagente.nombre'));
$table->data[2][1] = html_print_select (index_array ($agents, 'id_agente', 'nombre'),
	'id_agents[]', 0, false, __('None'), 0, true, true, true, '', empty ($module_name));

echo '<form method="post" id="form_modules" action="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&option=delete_modules" >';
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
/* <![CDATA[ */
$(document).ready (function () {
	$("#module_name").change (function () {
		if (this.value != "") {
			$("#id_agents").enable ();
			$("#id_group").enable ().change ();
		} else {
			$("#id_group, #id_agents").disable ();
		}
	});

	$("#checkbox-recursion").click(function () {
		if ($("#module_name").attr ("value") != "") {
			$("#id_group").trigger("change");
		}
	});
	
	$("#id_group").change (function () {
		var $select = $("#id_agents").disable ();
		$("#agent_loading").show ();
		$("option", $select).remove ();
		
		jQuery.post ("ajax.php",
			{"page" : "godmode/massive/massive_delete_modules",
			"get_agents" : 1,
			"id_group" : this.value,
			"module_name" : $("#module_name").attr ("value"),
			"recursion" : $("#checkbox-recursion").attr ("checked") ? 1 : 0
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
