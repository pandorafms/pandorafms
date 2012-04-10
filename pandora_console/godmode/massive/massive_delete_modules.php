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
		sprintf('nombre IN ("%s")', implode('","',$module_name)), true);
		
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

$module_type = (int) get_parameter ('module_type');
$idGroupMassive = (int) get_parameter('id_group_massive');
$idAgentMassive = (int) get_parameter('id_agent_massive');
$group_select = get_parameter('groups_select');

$delete = (bool) get_parameter_post ('delete');
$module_name = get_parameter ('module_name');
$agents_select = get_parameter('agents');
$agents_id = get_parameter('id_agents');
$modules_select = get_parameter('module');
$selection_mode = get_parameter('selection_mode', 'modules');
$recursion = get_parameter('recursion');

if ($delete) {
	if($selection_mode == 'modules') {	
		$force = get_parameter('force_type', false);
		
		if($agents_select == false) {
			$agents_select = array();
			$agents_ = array();
		}

		foreach($agents_select as $agent_name) {
			$agents_[] = agents_get_agent_id($agent_name);
		}
		$modules_ = $module_name;
	}
	else if($selection_mode == 'agents') {
		$force = get_parameter('force_group', false);

		$agents_ = $agents_id;
		$modules_ = $modules_select;
	}
	
	// If the option to select all of one group or module type is checked
	if($force) {
		if($force == 'type') {
			$condition = '';
			if($module_type != 0)
				$condition = ' AND t2.id_tipo_modulo = '.$module_type;
				
			$agents_ = db_get_all_rows_sql('SELECT DISTINCT(t1.id_agente)
				FROM tagente t1, tagente_modulo t2
				WHERE t1.id_agente = t2.id_agente');
			foreach($agents_ as $id_agent) {
				$module_name = db_get_all_rows_filter('tagente_modulo', array('id_agente' => $id_agent, 'id_tipo_modulo' =>  $module_type),'nombre');

				if($module_name == false) {
					$module_name = array();
				}
				foreach($module_name as $mod_name) {
					$result = process_manage_edit ($mod_name['nombre'], $id_agent['id_agente']);
					$count ++;
					$success += (int)$result;
				}
			}
		}
		else if($force == 'group') {
			$agents_ = array_keys (agents_get_group_agents ($group_select, false, "none"));
			foreach($agents_ as $id_agent) {
				$module_name = db_get_all_rows_filter('tagente_modulo', array('id_agente' => $id_agent),'nombre');
				if($module_name == false) {
					$module_name = array();
				}
				foreach($module_name as $mod_name) {
					$result = process_manage_edit ($mod_name['nombre'], $id_agent);
					$count ++;
					$success += (int)$result;
				}
			}
		}
		
		// We empty the agents array to skip the standard procedure
		$agents_ = array();
	}
	
	$result = process_manage_delete ($modules_, $agents_);
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

$agents = agents_get_group_agents (array_keys (users_get_groups ()), false, "none");
switch ($config["dbtype"]) {
	case "mysql":
	case "oracle":
		$module_types = db_get_all_rows_filter ('tagente_modulo,ttipo_modulo',
			array ('tagente_modulo.id_tipo_modulo = ttipo_modulo.id_tipo',
				'id_agente' => array_keys ($agents),
				'disabled' => 0,
				'order' => 'ttipo_modulo.nombre'),
			array ('DISTINCT(id_tipo)',
				'CONCAT(ttipo_modulo.descripcion," (",ttipo_modulo.nombre,")") AS description'));
		break;
	case "postgresql":
		$module_types = db_get_all_rows_filter ('tagente_modulo,ttipo_modulo',
			array ('tagente_modulo.id_tipo_modulo = ttipo_modulo.id_tipo',
			'id_agente' => array_keys ($agents),
			'disabled' => 0,
			'order' => 'description'),
			array ('DISTINCT(id_tipo)',
			'ttipo_modulo.descripcion || \' (\' || ttipo_modulo.nombre || \')\' AS description'));
		break;
}

if ($module_types === false)
	$module_types = array ();

$types = '';
foreach ($module_types as $type) {
	$types[$type['id_tipo']] = $type['description'];
}

$table->width = '99%';
$table->data = array ();
	
$table->data[0][0] = __('Selection mode');
$table->data[0][1] = __('Select modules first').' '.html_print_radio_button_extended ("selection_mode", 'modules', '', $selection_mode, false, '', 'style="margin-right: 40px;"', true);
$table->data[0][2] = '';
$table->data[0][3] = __('Select agents first').' '.html_print_radio_button_extended ("selection_mode", 'agents', '', $selection_mode, false, '', 'style="margin-right: 40px;"', true);

$table->rowclass[1] = 'select_modules_row';
$table->data[1][0] = __('Module type');
$table->data[1][0] .= '<span id="module_loading" class="invisible">';
$table->data[1][0] .= html_print_image('images/spinner.png', true);
$table->data[1][0] .= '</span>';
$types[0] = __('All');
$table->colspan[1][1] = 2;
$table->data[1][1] = html_print_select ($types,
	'module_type', '', false, __('Select'), -1, true, false, true, '', false, 'width:100%');

$table->data[1][3] = __('Select all modules of this type').' '.html_print_checkbox_extended ("force_type", 'type', '', '', false, '', 'style="margin-right: 40px;"', true);

$modules = array ();
if ($module_type != '') {
	$filter = array ('id_tipo_modulo' => $module_type);
}
else {
	$filter = false;
}

$names = agents_get_modules (array_keys ($agents),
	'DISTINCT(nombre)', $filter, false);
foreach ($names as $name) {
	$modules[$name['nombre']] = $name['nombre'];
}

$table->rowclass[2] = 'select_agents_row';
$table->data[2][0] = __('Agent group');
$groups = groups_get_all(true);
$groups[0] = __('All');
$table->colspan[2][1] = 2;
$table->data[2][1] = html_print_select ($groups, 'groups_select',
	'', true, __('Select'), -1, true, false, true, '', false, 'width:100%').
        ' '.__('Group recursion').' '.html_print_checkbox ("recursion", 1, false, true, false);
$table->data[2][3] = __('Select all modules of this group').' '.html_print_checkbox_extended ("force_group", 'group', '', '', false, '', 'style="margin-right: 40px;"', true);

$table->rowstyle[3] = 'vertical-align: top;';
$table->rowclass[3] = 'select_modules_row select_modules_row_2';
$table->data[3][0] = __('Modules');
$table->data[3][1] = html_print_select ($modules, 'module_name[]',
	$module_name, false, __('Select'), -1, true, true, true, '', false, 'width:100%');

$table->data[3][2] = __('When select modules');
$table->data[3][2] .= '<br>';
$table->data[3][2] .= html_print_select (array('common' => __('Show common agents'), 'all' => __('Show all agents')), 'agents_selection_mode',
	'common', false, '', '', true, false, true, '', false);
$table->data[3][3] = html_print_select (array(), 'agents[]',
	$agents_select, false, __('None'), 0, true, true, false, '', false, 'width:100%');
	
$table->rowstyle[4] = 'vertical-align: top;';
$table->rowclass[4] = 'select_agents_row select_agents_row_2';
$table->data[4][0] = __('Agents');

$table->data[4][1] = html_print_select ($agents, 'id_agents[]',
	$agents_id, false, '', '', true, true, false, '', false, 'width:100%');
	
$table->data[4][2] = __('When select agents');
$table->data[4][2] .= '<br>';
$table->data[4][2] .= html_print_select (array('common' => __('Show common modules'), 'all' => __('Show all modules')), 'modules_selection_mode',
	'common', false, '', '', true);
$table->data[4][3] = html_print_select (array(), 'module[]',
	$modules_select, false, '', '', true, true, false, '', false, 'width:100%');

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

if($selection_mode == 'modules'){
	$modules_row = '';
	$agents_row = 'none';
}
else {
	$modules_row = 'none';
	$agents_row = '';
}
?>

<script type="text/javascript">
/* <![CDATA[ */
$(document).ready (function () {
	$("#id_agents").change(agent_changed_by_multiple_agents);
	$("#module_name").change(module_changed_by_multiple_modules);
	
	clean_lists();

	$(".select_modules_row").css('display', '<?php echo $modules_row?>');
	$(".select_agents_row").css('display', '<?php echo $agents_row?>');

	// Trigger change to refresh selection when change selection mode
	$("#agents_selection_mode").change (function() {
		$("#module_name").trigger('change');
	});
	$("#modules_selection_mode").change (function() {
		$("#id_agents").trigger('change');
	});
	
	$("#module_type").change (function () {
		$('#checkbox-force_type').attr('checked', false);
		if (this.value < 0) {
			clean_lists();
			$(".select_modules_row_2").css('display', 'none');
			return;
		}
		else {
			$("#module").html('<?php echo __('None'); ?>');
			$("#module_name").html('');
			$('input[type=checkbox]').removeAttr('disabled');
			$(".select_modules_row_2").css('display', '');
		}
		
		$("tr#delete_table-edit1, tr#delete_table-edit2, tr#delete_table-edit3, tr#delete_table-edit35, tr#delete_table-edit4, tr#delete_table-edit5, tr#delete_table-edit6, tr#delete_table-edit7, tr#delete_table-edit8").hide ();
		
		if (this.value == '0') {
			filter = '';
		}
		else {
			filter = "id_tipo_modulo="+this.value;
		}

		$("#module_loading").show ();
		$("tr#delete_table-edit1, tr#delete_table-edit2").hide ();
		$("#module_name").attr ("disabled", "disabled")
		$("#module_name option[value!=0]").remove ();
		jQuery.post ("ajax.php",
			{"page" : "operation/agentes/ver_agente",
			"get_agent_modules_json" : 1,
			"filter" : filter,
			"fields" : "DISTINCT(nombre)",
			"indexed" : 0
			},
			function (data, status) {
				jQuery.each (data, function (id, value) {
					option = $("<option></option>").attr ("value", value["nombre"]).html (value["nombre"]);
					$("#module_name").append (option);
				});
				$("#module_loading").hide ();
				$("#module_name").removeAttr ("disabled");
			},
			"json"
		);
	});
	
	function clean_lists() {
		$("#id_agents").html('<?php echo __('None'); ?>');
		$("#module_name").html('<?php echo __('None'); ?>');
		$("#agents").html('<?php echo __('None'); ?>');
		$("#module").html('<?php echo __('None'); ?>');
		$('input[type=checkbox]').attr('checked', false);
		$('input[type=checkbox]').attr('disabled', true);
		$('#module_type').val(-1);
		$('#groups_select').val(-1);
	}
	
	$('input[type=checkbox]').change (
		function () {			
			if(this.id == "checkbox-force_type"){
				if(this.checked) {
					$(".select_modules_row_2").css('display', 'none');
					$("tr#delete_table-edit1, tr#delete_table-edit2, tr#delete_table-edit3, tr#delete_table-edit35, tr#delete_table-edit4, tr#delete_table-edit5, tr#delete_table-edit6, tr#delete_table-edit7, tr#delete_table-edit8").show ();
				}
				else {
					$(".select_modules_row_2").css('display', '');
					if($('#module_name option:selected').val() == undefined) {
						$("tr#delete_table-edit1, tr#delete_table-edit2, tr#delete_table-edit3, tr#delete_table-edit35, tr#delete_table-edit4, tr#delete_table-edit5, tr#delete_table-edit6, tr#delete_table-edit7, tr#delete_table-edit8").hide ();
					}
				}
			}
			else if(this.id == "checkbox-recursion"){
				$("#groups_select").trigger("change");
			}
			else {
				if(this.checked) {
					$(".select_agents_row_2").css('display', 'none');
				}
				else {
					$(".select_agents_row_2").css('display', '');

				}
			}
		}
	);
	
	$("#form_modules input[name=selection_mode]").change (function () {
		console.log(this.value);
		selector = this.value;
		clean_lists();

		if(selector == 'agents') {
			$(".select_modules_row").css('display', 'none');
			$(".select_agents_row").css('display', '');
		}
		else if(selector == 'modules') {
			$(".select_agents_row").css('display', 'none');
			$(".select_modules_row").css('display', '');
		}
	});

	$("#groups_select").change (
		function () {
			$('#checkbox-force_group').attr('checked', false);
			if (this.value < 0) {
				clean_lists();
				$(".select_agents_row_2").css('display', 'none');
				return;
			}
			else {
				$("#module").html('<?php echo __('None'); ?>');
				$("#id_agents").html('');
				$('input[type=checkbox]').removeAttr('disabled');
				$(".select_agents_row_2").css('display', '');
			}
						
			jQuery.post ("ajax.php",
				{"page" : "operation/agentes/ver_agente",
				"get_agents_group_json" : 1,
				"recursion" : $("#checkbox-recursion").attr ("checked") ? 1 : 0,
				"id_group" : this.value
				},
				function (data, status) {
					$("#id_agents").html('');
				
					jQuery.each (data, function (id, value) {
						option = $("<option></option>").attr ("value", value["id_agente"]).html (value["nombre"]);
						$("#id_agents").append (option);
					});
				},
				"json"
			);
		}
	);
});
/* ]]> */
</script>

