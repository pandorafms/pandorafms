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

	global $config;

	if (empty ($module_name)) {
		ui_print_error_message(__('No module selected'));
		return false;
	}
	
	if (empty ($id_agents)) {
		ui_print_error_message(__('No agents selected'));
		return false;
	}
	
	$module_name = (array)$module_name;
	
	// We are selecting "any" agent for the selected modules
	if (($id_agents[0] == 0) and (is_array($id_agents)) and (count($id_agents) == 1))
		$id_agents = NULL;
	
	$selection_delete_mode = get_parameter('selection_mode', 'modules');
	
	// Selection mode by Agents
	if ($selection_delete_mode == 'agents') {
		// We are selecting "any" module for the selecteds agents
		if (($module_name[0] == "0") and (is_array($module_name)) and (count($module_name) == 1))
			$filter_for_module_deletion = false;
		else
			$filter_for_module_deletion = sprintf('nombre IN ("%s")', implode('","', $module_name));

		if ($config['dbtype'] == "oracle") {
			$all_agent_modules = false;
			if (($module_name[0] == "0") and (is_array($module_name)) and (count($module_name) == 1)) {
				$all_agent_modules = true;
			}
			$names_to_long = array();
			$i = 0;
			foreach ($module_name as $name) {
				if (strlen($name) > 30) {
					$original_names[] = $module_name[$i];
					unset($module_name[$i]);
					$names_to_long[] = substr($name, 0, 28) . "%";
				}
				$i++;
			}
			$modules = "SELECT id_agente_modulo, nombre FROM tagente_modulo WHERE";
			$modules .= sprintf(" id_agente IN (%s)", implode(",", $id_agents));
			if (!empty($module_name) && (!$all_agent_modules)) {
				$modules .= sprintf(" AND nombre IN ('%s')", implode("','", $module_name));
			}
			$modules = db_get_all_rows_sql($modules);
			$modules2 = "";
			if (!empty($names_to_long) && (!$all_agent_modules)) {
				$modules2 = "SELECT id_agente_modulo, nombre FROM tagente_modulo WHERE";
				$modules2 .= sprintf(" id_agente IN (%s) AND (", implode(",", $id_agents));
				$j = 0;
				foreach ($names_to_long as $name) {
					if ($j == 0) {
						$modules2 .= "nombre LIKE ('" . $name . "')";
					}
					else {
						$modules2 .= " OR nombre LIKE ('" . $name . "')";
					}
					$j++;
				}
				$modules2 .= ")";
				$modules2 = db_get_all_rows_sql($modules2);
				$modules = array_merge($modules, $modules2);
			}
			$all_names = array();
			foreach ($modules as $module) {
				$all_modules[] = $module['id_agente_modulo'];
				$all_names[] = $module['nombre'];
			}
			$modules = $all_modules;
			$modules = array_unique($modules);
			if (!empty($names_to_long) && (!$all_agent_modules)) {
				$j = 0;
				foreach ($all_names as $name) {
					if (strlen($name) > 30) {
						if (!in_array($name, $original_names)) {
							unset($modules[$j]);
						}
					}
					$j++;
				}
			}
		}
		else {
			$modules = agents_get_modules ($id_agents, 'id_agente_modulo',
				$filter_for_module_deletion, true);
		}
	}
	else {
		if ($config['dbtype'] == "oracle") {
			$all_agent_modules = false;
			$names_to_long = array();
			if (($module_name[0] == "0") and (is_array($module_name)) and (count($module_name) == 1)) {
				$all_agent_modules = true;
			}
			$i = 0;
			foreach ($module_name as $name) {
				if (strlen($name) > 30) {
					$original_names[] = $module_name[$i];
					unset($module_name[$i]);
					$names_to_long[] = substr($name, 0, 28) . "%";
				}
				$i++;
			}
			$modules = "SELECT id_agente_modulo, nombre FROM tagente_modulo WHERE";
			$any_agent = false;
			if ($id_agents == null) {
				$any_agent = true;
			}
			if (!empty($id_agents)) {
				$modules .= sprintf(" id_agente IN (%s)", implode(",", $id_agents));
				$agents_selected = true;
			}
			if (!empty($module_name) && (!$all_agent_modules)) {
				if ($any_agent) {
					$modules .= sprintf(" nombre IN ('%s')", implode("','", $module_name));
				}
				else {
					$modules .= sprintf(" AND nombre IN ('%s')", implode("','", $module_name));
				}
			}
			$modules = db_get_all_rows_sql($modules);
			if (!empty($names_to_long) && (!$all_agent_modules)) {
				$modules2 = "SELECT id_agente_modulo, nombre FROM tagente_modulo WHERE";
				$modules2 .= sprintf(" id_agente IN (%s) AND (", implode(",", $id_agents));
				$j = 0;
				foreach ($names_to_long as $name) {
					if ($j == 0) {
						$modules2 .= "nombre LIKE ('" . $name . "')";
					}
					else {
						$modules2 .= " OR nombre LIKE ('" . $name . "')";
					}
					$j++;
				}
				$modules2 .= ")";
				$modules2 = db_get_all_rows_sql($modules2);
				$modules = array_merge($modules, $modules2);
			}
			$all_names = array();
			foreach ($modules as $module) {
				$all_modules[] = $module['id_agente_modulo'];
				$all_names[] = $module['nombre'];
			}
			$modules = $all_modules;
			$modules = array_unique($modules);
			if (!empty($names_to_long) && (!$all_agent_modules)) {
				$j = 0;
				foreach ($all_names as $name) {
					if (strlen($name) > 30) {
						if (!in_array($name, $original_names)) {
							unset($modules[$j]);
						}
					}
					$j++;
				}
			}
		}
		else {
			$modules = agents_get_modules ($id_agents, 'id_agente_modulo',
				sprintf('nombre IN ("%s")', implode('","',$module_name)), true);
		}
	}
	
	$count_deleted_modules = count($modules);
	if ($config['dbtype'] == "oracle") {
		$success = db_process_sql(sprintf("DELETE FROM tagente_modulo WHERE id_agente_modulo IN (%s)", implode(",", $modules)));
	}
	else {
		$success = modules_delete_agent_module ($modules);
	}

	if (! $success) {
		ui_print_error_message(
			__('There was an error deleting the modules, the operation has been cancelled'));
		
		return false;
	}
	else {
		ui_print_success_message(
			__('Successfully deleted') . '&nbsp;(' . $count_deleted_modules . ')');
		
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
	switch ($selection_mode) {
		case 'modules':
			$force = get_parameter('force_type', false);
			
			if ($agents_select == false) {
				$agents_select = array();
				$agents_ = array();
			}
			
			foreach ($agents_select as $agent_name) {
				$agents_[] = agents_get_agent_id(io_safe_output($agent_name));
			}
			$modules_ = $module_name;
			break;
		case 'agents':
			$force = get_parameter('force_group', false);
			
			$agents_ = $agents_id;
			$modules_ = $modules_select;
			break;
	}
	
	$count = 0;
	$success = 0;
	
	// If the option to select all of one group or module type is checked
	if ($force) {
		if ($force == 'type') {
			$condition = '';
			if ($module_type != 0)
				$condition = ' AND t2.id_tipo_modulo = '.$module_type;

			$groups = users_get_groups ($config["id_user"], "AW", false);
			$group_id_list = ($groups ? join(",",array_keys($groups)):"0");
			$condition = ' AND t1.id_grupo IN (' . $group_id_list . ') ';

			$agents_ = db_get_all_rows_sql('SELECT DISTINCT(t1.id_agente)
				FROM tagente t1, tagente_modulo t2
				WHERE t1.id_agente = t2.id_agente AND t2.delete_pending = 0 ' . $condition);
			foreach($agents_ as $id_agent) {
				$module_name = db_get_all_rows_filter('tagente_modulo', array('id_agente' => $id_agent['id_agente'], 'id_tipo_modulo' =>  $module_type, 'delete_pending' => 0),'nombre');
				
				if ($module_name == false) {
					$module_name = array();
				}
				foreach ($module_name as $mod_name) {
					$result = process_manage_delete ($mod_name['nombre'], $id_agent['id_agente']);
					$count ++;
					$success += (int)$result;
				}
			}
		}
		else if ($force == 'group') {
			if( $group_select == 0 ) {
				$agents_ = array_keys (agents_get_group_agents (array_keys (users_get_groups ($config["id_user"], "AW", false)), false, "none"));
			}
			else {
				$agents_ = array_keys (agents_get_group_agents ($group_select, false, "none"));
			}

			foreach ($agents_ as $id_agent) {
				$module_name = db_get_all_rows_filter('tagente_modulo', array('id_agente' => $id_agent),'nombre');
				if ($module_name == false) {
					$module_name = array();
				}
				else {
					$result = process_manage_delete (array(0 => 0), $id_agent);
				}
				$success += (int)$result;
			}
		}
		
		// We empty the agents array to skip the standard procedure
		$agents_ = array();
	}
	
	if (!$force) {
		$result = process_manage_delete ($modules_, $agents_);
	}
	
	if ($result) {
		db_pandora_audit("Massive management", "Delete module ", false, false,
			'Agent: ' . json_encode($agents_) . ' Module: ' . $module_name);
	}
	else {
		db_pandora_audit("Massive management", "Fail try to delete module", false, false,
			'Agent: ' . json_encode($agents_) . ' Module: ' . $module_name);
	}
}

$groups = users_get_groups ();

$agents = agents_get_group_agents (array_keys (users_get_groups ()),
	false, "none");
switch ($config["dbtype"]) {
	case "mysql":
		$module_types = db_get_all_rows_filter ('tagente_modulo,ttipo_modulo',
			array ('tagente_modulo.id_tipo_modulo = ttipo_modulo.id_tipo',
				'id_agente' => array_keys ($agents),
				'disabled' => 0,
				'order' => 'ttipo_modulo.nombre'),
			array ('DISTINCT(id_tipo)',
				'CONCAT(ttipo_modulo.descripcion," (",ttipo_modulo.nombre,")") AS description'));
		break;
	case "oracle":
		$module_types = db_get_all_rows_filter ('tagente_modulo,ttipo_modulo',
			array ('tagente_modulo.id_tipo_modulo = ttipo_modulo.id_tipo',
				'id_agente' => array_keys ($agents),
				'disabled' => 0,
				'order' => 'ttipo_modulo.nombre'),
			array ('ttipo_modulo.id_tipo',
				'ttipo_modulo.descripcion || \' (\' || ttipo_modulo.nombre || \')\' AS description'));
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

$table->width = '100%';
$table->class = 'databox filters';
$table->data = array ();
$table->style[0] = 'font-weight: bold';
$table->style[2] = 'font-weight: bold';



$table->data['selection_mode'][0] = __('Selection mode');
$table->data['selection_mode'][1] = __('Select modules first') . ' ' .
	html_print_radio_button_extended ("selection_mode", 'modules', '', $selection_mode, false, '', 'style="margin-right: 40px;"', true).'<br>';
$table->data['selection_mode'][1] .= __('Select agents first') . ' ' .
	html_print_radio_button_extended ("selection_mode", 'agents', '', $selection_mode, false, '', 'style="margin-right: 40px;"', true);



$table->rowclass['form_modules_1'] = 'select_modules_row';
$table->data['form_modules_1'][0] = __('Module type');
$table->data['form_modules_1'][0] .= '<span id="module_loading" class="invisible">';
$table->data['form_modules_1'][0] .= html_print_image('images/spinner.png', true);
$table->data['form_modules_1'][0] .= '</span>';
$types[0] = __('All');
$table->colspan['form_modules_1'][1] = 2;
$table->data['form_modules_1'][1] = html_print_select ($types, 'module_type', '',
	false, __('Select'), -1, true, false, true, '', false,
	'width:100%');
$table->data['form_modules_1'][3] = __('Select all modules of this type') . ' ' .
	html_print_checkbox_extended("force_type", 'type', '', '', false,
		'', 'style="margin-right: 40px;"', true);



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

$table->rowclass['form_agents_1'] = 'select_agents_row';
$table->data['form_agents_1'][0] = __('Agent group');
$groups = users_get_groups ($config["id_user"], "AW", false);
$groups[0] = __('All');
$table->colspan['form_agents_1'][1] = 2;
$table->data['form_agents_1'][1] = html_print_select_groups (false, 'AW', true, 'groups_select',
	'', false, '', '', true) .
	' ' . __('Group recursion') . ' ' .
	html_print_checkbox ("recursion", 1, false, true, false);
$table->data['form_agents_1'][3] = __('Select all modules of this group') . ' ' .
	html_print_checkbox_extended ("force_group", 'group', '', '', false,
	'', 'style="margin-right: 40px;"', true);



$table->rowclass['form_agents_2'] = 'select_agents_row';
$table->data['form_agents_2'][0] = __('Status');
$table->colspan['form_agents_2'][1] = 2;
$status_list = array ();
$status_list[AGENT_STATUS_NORMAL] = __('Normal');
$status_list[AGENT_STATUS_WARNING] = __('Warning');
$status_list[AGENT_STATUS_CRITICAL] = __('Critical');
$status_list[AGENT_STATUS_UNKNOWN] = __('Unknown');
$status_list[AGENT_STATUS_NOT_NORMAL] = __('Not normal');
$status_list[AGENT_STATUS_NOT_INIT] = __('Not init');
$table->data['form_agents_2'][1] = html_print_select($status_list,
	'status_agents', 'selected', '', __('All'), AGENT_STATUS_ALL, true);
$table->data['form_agents_2'][3] = '';

$table->rowclass['form_modules_3'] = '';
$table->data['form_modules_3'][0] = __('Module Status');
$table->colspan['form_modules_3'][1] = 2;
$status_list = array ();
$status_list[AGENT_MODULE_STATUS_NORMAL] = __('Normal');
$status_list[AGENT_MODULE_STATUS_WARNING] = __('Warning');
$status_list[AGENT_MODULE_STATUS_CRITICAL_BAD] = __('Critical');
$status_list[AGENT_MODULE_STATUS_UNKNOWN] = __('Unknown');
$status_list[AGENT_MODULE_STATUS_NOT_NORMAL] = __('Not normal');
$status_list[AGENT_MODULE_STATUS_NOT_INIT] = __('Not init');
$table->data['form_modules_3'][1] = html_print_select($status_list,
	'status_module', 'selected', '', __('All'), AGENT_MODULE_STATUS_ALL, true);
$table->data['form_modules_3'][3] = '';

$table->rowstyle['form_modules_2'] = 'vertical-align: top;';
$table->rowclass['form_modules_2'] = 'select_modules_row select_modules_row_2';
$table->data['form_modules_2'][0] = __('Modules');
$table->data['form_modules_2'][1] = html_print_select ($modules, 'module_name[]',
	$module_name, false, __('Select'), -1, true, true, true, '', false, 'width:100%');

$table->data['form_modules_2'][2] = __('When select modules');
$table->data['form_modules_2'][2] .= '<br>';
$table->data['form_modules_2'][2] .= html_print_select(
	array('common' => __('Show common agents'),
		'all' => __('Show all agents')), 'agents_selection_mode',
	'common', false, '', '', true, false, true, '', false);
$table->data['form_modules_2'][3] = html_print_select (array(), 'agents[]',
	$agents_select, false, __('None'), 0, true, true, false, '', false, 'width:100%');



$table->rowstyle['form_agents_3'] = 'vertical-align: top;';
$table->rowclass['form_agents_3'] = 'select_agents_row select_agents_row_2';
$table->data['form_agents_3'][0] = __('Agents');
$table->data['form_agents_3'][1] = html_print_select ($agents, 'id_agents[]',
	$agents_id, false, '', '', true, true, false, '', false, 'width:100%');
$table->data['form_agents_3'][2] = __('When select agents');
$table->data['form_agents_3'][2] .= '<br>';
$table->data['form_agents_3'][2] .= html_print_select (array('common' => __('Show common modules'), 'all' => __('Show all modules'),'unknown' => __('Show unknown and not init modules')), 'modules_selection_mode',
	'common', false, '', '', true);
$table->data['form_agents_3'][3] = html_print_select (array(), 'module[]',
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

if ($selection_mode == 'modules') {
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

var limit_parameters_massive = <?php echo $config['limit_parameters_massive']; ?>;

$(document).ready (function () {
	$("#id_agents").change(agent_changed_by_multiple_agents);
	$("#module_name").change(module_changed_by_multiple_modules);
	
	clean_lists();
	
	$(".select_modules_row")
		.css('display', '<?php echo $modules_row?>');
	$(".select_agents_row")
		.css('display', '<?php echo $agents_row?>');
	
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
		
		$("tr#delete_table-edit1, tr#delete_table-edit2, tr#delete_table-edit3, tr#delete_table-edit35, tr#delete_table-edit4, tr#delete_table-edit5, tr#delete_table-edit6, tr#delete_table-edit7, tr#delete_table-edit8")
			.hide ();
		
		var params = {
			"page" : "operation/agentes/ver_agente",
			"get_agent_modules_json" : 1,
			"get_distinct_name" : 1,
			"indexed" : 0,
			"privilege" : "AW"
		};
		
		if (this.value != '0')
			params['id_tipo_modulo'] = this.value;
		
		var status_module = $('#status_module').val();
		if (status_module != '-1')
			params['status_module'] = status_module;
		
		$("#module_loading").show ();
		$("tr#delete_table-edit1, tr#delete_table-edit2").hide ();
		$("#module_name").attr ("disabled", "disabled")
		$("#module_name option[value!=0]").remove ();
		jQuery.post ("ajax.php",
			params,
			function (data, status) {
				jQuery.each (data, function (id, value) {
					option = $("<option></option>")
						.attr("value", value["nombre"])
						.html(value["nombre"]);
					$("#module_name").append (option);
				});
				$("#module_loading").hide();
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
			if (this.id == "checkbox-force_type") {
				if (this.checked) {
					$(".select_modules_row_2").css('display', 'none');
					$("tr#delete_table-edit1, tr#delete_table-edit2, tr#delete_table-edit3, tr#delete_table-edit35, tr#delete_table-edit4, tr#delete_table-edit5, tr#delete_table-edit6, tr#delete_table-edit7, tr#delete_table-edit8").show ();
				}
				else {
					$(".select_modules_row_2").css('display', '');
					if ($('#module_name option:selected').val() == undefined) {
						$("tr#delete_table-edit1, tr#delete_table-edit2, tr#delete_table-edit3, tr#delete_table-edit35, tr#delete_table-edit4, tr#delete_table-edit5, tr#delete_table-edit6, tr#delete_table-edit7, tr#delete_table-edit8").hide ();
					}
				}
			}
			else if (this.id == "checkbox-recursion") {
				$("#groups_select").trigger("change");
			}
			else {
				if (this.checked) {
					$(".select_agents_row_2").css('display', 'none');
				}
				else {
					$(".select_agents_row_2").css('display', '');
				}
			}
		}
	);
	
	$("#form_modules input[name=selection_mode]").change (function () {
		selector = this.value;
		clean_lists();
		
		if (selector == 'agents') {
			$(".select_modules_row").hide();
			$(".select_agents_row").show();
			$("#groups_select").trigger("change");
		}
		else if (selector == 'modules') {
			$(".select_agents_row").hide();
			$(".select_modules_row").show();
		}
	});

	var recursion;

	$("#checkbox-recursion").click(function () {
		recursion = this.checked ? 1 : 0;

		$("#groups_select").trigger("change");
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
					"recursion" : recursion,
					"id_group" : this.value,
					"privilege" : "AW",
					status_agents: function () {
						return $("#status_agents").val();
					},
					// Add a key prefix to avoid auto sorting in js object conversion
					"keys_prefix" : "_"
				},
				function (data, status) {
					$("#id_agents").html('');
					jQuery.each (data, function (id, value) {
						// Remove keys_prefix from the index
						id = id.substring(1);
						
						option = $("<option></option>")
							.attr ("value", value["id_agente"])
							.html (value["nombre"]);
						$("#id_agents").append (option);
					});
				},
				"json"
			);
		}
	);
	
	$("#status_agents").change(function() {
		$("#groups_select").trigger("change");
	});
	
	$("#form_modules").submit(function() {
		var get_parameters_count = window.location.href.slice(
			window.location.href.indexOf('?') + 1).split('&').length;
		var post_parameters_count = $("#form_modules").serializeArray().length;
		
		var count_parameters =
			get_parameters_count + post_parameters_count;
		
		if (count_parameters > limit_parameters_massive) {
			alert("<?php echo __('Unsucessful sending the data, please contact with your administrator or make with less elements.'); ?>");
			return false;
		}
	});
	
	$("#status_module").change(function() {
		selector = $("#form_modules input[name=selection_mode]:checked").val();
		if(selector == 'agents') {
			$("#id_agents").trigger("change");
		}
		else if(selector == 'modules') {
			$("#module_type").trigger("change");
		}
	});
});
/* ]]> */
</script>

