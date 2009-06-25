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
		"Trying to access agent massive deletion");
	require ("general/noaccess.php");
	return;
}

require_once ('include/functions_agents.php');
require_once ('include/functions_modules.php');

if (is_ajax ()) {
	$get_agents = (bool) get_parameter ('get_agents');
	
	if ($get_agents) {
		$id_group = (int) get_parameter ('id_group');
		$module_name = (string) get_parameter ('module_name');
		
		$agents_modules = get_agents_with_module_name ($module_name, $id_group,
			array ('delete_pending' => 0,
				'`tagente_modulo`.disabled' => 0),
			array ('tagente.id_agente', 'tagente.nombre'));
		
		echo json_encode (index_array ($agents_modules, 'id_agente', 'nombre'));
		return;
	}
	return;
}

echo '<h3>'.__('Massive modules deletion').'</h3>';

function process_manage_delete ($module_name, $id_agents) {
	if (empty ($module_name)) {
		echo '<h3 class="error">'.__('No module selected').'</h3>';
		return false;
	}
	
	if (empty ($id_agents)) {
		echo '<h3 class="error">'.__('No agents selected').'</h3>';
		return false;
	}
	
	process_sql_begin ();
	$modules = get_agent_modules ($id_agents, 'id_agente_modulo',
		array ('nombre' => $module_name), true);
	$success = delete_agent_module ($modules);
	if (! $success) {
		echo '<h3 class="error">'.__('There was an error deleting the modules, the operation has been cancelled').'</h3>';
		echo '<h4>'.__('Could not delete modules').'</h4>';
		process_sql_rollback ();
	} else {
		echo '<h3 class="suc">'.__('Successfully deleted').'</h3>';
		process_sql_commit ();
	}
}

$id_group = (int) get_parameter ('id_group');
$id_agents = (array) get_parameter ('id_agents');
$module_name = (string) get_parameter ('module_name');

$delete = (bool) get_parameter_post ('delete');

if ($delete) {
	process_manage_delete ($module_name, $id_agents);
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

$table->data[0][0] = __('Modules');
$modules = array ();
$modules = get_db_all_rows_filter ('tagente_modulo', false, 'DISTINCT(nombre)');
$table->data[0][1] = print_select (index_array ($modules, 'nombre', 'nombre'),
	'module_name', $module_name, false, __('Select'), '', true);

$table->data[1][0] = __('Group');
$table->data[1][1] = print_select ($groups, 'id_group', $id_group,
	false, '', '', true, false, true, '', empty ($module_name));

$table->data[2][0] = __('Agent');
$table->data[2][0] .= '<span id="agent_loading" class="invisible">';
$table->data[2][0] .= '<img src="images/spinner.gif" />';
$table->data[2][0] .= '</span>';
$agents = get_agents_with_module_name ($module_name, $id_group,
	array ('delete_pending' => 0,
		'`tagente_modulo`.disabled' => 0),
	array ('tagente.id_agente', 'tagente.nombre'));
$table->data[2][1] = print_select (index_array ($agents, 'id_agente', 'nombre'),
	'id_agents[]', 0, false, __('None'), 0, true, true, true, '', empty ($module_name));

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
	
	$("#id_group").change (function () {
		var $select = $("#id_agents").disable ();
		$("#agent_loading").show ();
		$("option", $select).remove ();
		
		jQuery.post ("ajax.php",
			{"page" : "godmode/agentes/massive_delete_modules",
			"get_agents" : 1,
			"id_group" : this.value,
			"module_name" : $("#module_name").attr ("value")
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
