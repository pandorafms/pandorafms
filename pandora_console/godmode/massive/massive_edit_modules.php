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

if (! check_acl ($config['id_user'], 0, "PM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access massive module update");
	require ("general/noaccess.php");
	return;
}

require_once ('include/functions_modules.php');
require_once($config['homedir'] . "/include/functions_agents.php");
require_once($config['homedir'] . "/include/functions_groups.php");
require_once($config['homedir'] . '/include/functions_users.php');

function process_manage_edit ($module_name, $agents_select = null) {
	if (is_int ($module_name) && $module_name <= 0) {
		echo '<h3 class="error">'.__('No modules selected').'</h3>';
		return false;
	}
	
	/* List of fields which can be updated */
	$fields = array ('min_warning', 'max_warning', 'str_warning', 'min_critical', 'max_critical', 'str_critical', 'min_ff_event', 'module_interval',
		'disabled', 'post_process', 'unit', 'snmp_community', 'tcp_send', 'custom_string_1', 'plugin_parameter', 
		'custom_string_2', 'custom_string_3', 'min', 'max', 'id_module_group', 'plugin_user', 'plugin_pass', 'id_export', 'history_data');
	$values = array ();

	// Specific snmp reused fields
	if(get_parameter ('tcp_send', '') == 3) {
		$plugin_user_snmp = get_parameter ('plugin_user_snmp', '');
		if($plugin_user_snmp != '') {
			$values['plugin_user'] = $plugin_user_snmp;
		}
		$plugin_pass_snmp = get_parameter ('plugin_pass_snmp', '');
		if($plugin_pass_snmp != '') {
			$values['plugin_pass'] = $plugin_pass_snmp;
		}
	}
	
	foreach ($fields as $field) {
		$value = get_parameter ($field, '');
		if ($value != '') {
			$values[$field] = $value;
		}
	}
	
	if (strlen(get_parameter('history_data')) > 0) {
		$values['history_data'] = get_parameter('history_data');
	}

	// Whether to update module tag info
	$update_tags = get_parameter('id_tag', false);
	

	
if (array_search(0, $agents_select) !== false) {
	$modules = db_get_all_rows_filter ('tagente_modulo',
		array ('nombre' => $module_name),
		array ('id_agente_modulo'));
}
else {
	$modules = db_get_all_rows_filter ('tagente_modulo',
		array ('id_agente' => $agents_select,
			'nombre' => $module_name),
		array ('id_agente_modulo'));
}
	
	db_process_sql_begin ();
	
	if ($modules === false)
		return false;
	
	foreach ($modules as $module) {
		$result = modules_update_agent_module ($module['id_agente_modulo'], $values, true, $update_tags);
		
		if (is_error($result)) {
			db_process_sql_rollback ();
			
			return false;
		}
	}
	
	db_process_sql_commit ();
	
	return true;
}

$module_type = (int) get_parameter ('module_type');
$idGroupMassive = (int) get_parameter('id_group_massive');
$idAgentMassive = (int) get_parameter('id_agent_massive');
$group_select = get_parameter('groups_select');

$module_name = get_parameter ('module_name');
$agents_select = get_parameter('agents', array());
$agents_id = get_parameter('id_agents');
$modules_select = get_parameter('module');
$selection_mode = get_parameter('selection_mode', 'modules');
$recursion = get_parameter('recursion');

$update = (bool) get_parameter_post ('update');

if ($update) {
	$agents_ = '';
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
	
	$success = 0;
	$count = 0;
	
	if($agents_ == false) {
		$agents_ = array();
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

	foreach($agents_ as $agent_) {
		
		if($modules_ == false) {
			$modules_ = array();
		}
	
		foreach($modules_ as $module_) {
			$result = process_manage_edit ($module_, $agents_);
			$count ++;
			$success += (int)$result;
		}
	}
	
	ui_print_result_message ($success > 0,
		__('Successfully updated')."(".$success."/".$count.")",
		__('Could not be updated'));
	
	$info = 'Modules: ' . json_encode($modules_) . ' Agents: ' . json_encode($agents_);	
	if ($success > 0) {
		db_pandora_audit("Masive management", "Edit module", false, false, $info);
	}
	else {
		db_pandora_audit("Masive management", "Fail try to edit module", false, false, $info);
	}
}

$table->id = 'delete_table';
$table->width = '98%';
$table->data = array ();
$table->style = array ();
$table->style[0] = 'font-weight: bold; vertical-align:top';
$table->style[2] = 'font-weight: bold';
$table->rowstyle = array ();
$table->size = array ();
$table->size[0] = '15%';
$table->size[1] = '35%';
$table->size[2] = '15%';
$table->size[3] = '35%';
if (! $module_type) {
	$table->rowstyle['edit1'] = 'display: none';
	$table->rowstyle['edit2'] = 'display: none';
	$table->rowstyle['edit3'] = 'display: none';
	$table->rowstyle['edit35'] = 'display: none';
	$table->rowstyle['edit4'] = 'display: none';
	$table->rowstyle['edit5'] = 'display: none';
	$table->rowstyle['edit6'] = 'display: none';
	$table->rowstyle['edit7'] = 'display: none';
}
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

$snmp_versions['1'] = 'v. 1';
$snmp_versions['2'] = 'v. 2';
$snmp_versions['2c'] = 'v. 2c';
$snmp_versions['3'] = 'v. 3';

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
	'module_type', '', false, __('Select'), -1, true, false, true);

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
	'', true, __('Select'), -1, true, false, true).
        ' '.__('Group recursion').' '.html_print_checkbox ("recursion", 1, false, true, false);
$table->data[2][3] = __('Select all modules of this group').' '.html_print_checkbox_extended ("force_group", 'group', '', '', false, '', 'style="margin-right: 40px;"', true);

$table->rowstyle[3] = 'vertical-align: top;';
$table->rowclass[3] = 'select_modules_row select_modules_row_2';
$table->data[3][0] = __('Modules');
$table->data[3][1] = html_print_select ($modules, 'module_name[]',
	$module_name, false, __('Select'), -1, true, true, true);

$table->data[3][2] = __('When select modules');
$table->data[3][2] .= '<br>';
$table->data[3][2] .= html_print_select (array('common' => __('Show common agents'), 'all' => __('Show all agents')), 'agents_selection_mode',
	'common', false, '', '', true);
$table->data[3][3] = html_print_select (array(), 'agents[]',
	$agents_select, false, __('None'), 0, true, true, false);
	
$table->rowstyle[4] = 'vertical-align: top;';
$table->rowclass[4] = 'select_agents_row select_agents_row_2';
$table->data[4][0] = __('Agents');
$table->data[4][1] = html_print_select ($agents, 'id_agents[]',
	$agents_id, false, '', '', true, true, false);
	
$table->data[4][2] = __('When select agents');
$table->data[4][2] .= '<br>';
$table->data[4][2] .= html_print_select (array('common' => __('Show common modules'), 'all' => __('Show all modules')), 'modules_selection_mode',
	'common', false, '', '', true);
$table->data[4][3] = html_print_select (array(), 'module[]',
	$modules_select, false, '', '', true, true, false);


$table->data['edit1'][0] = __('Warning status');
$table->data['edit1'][1] = '<em>'.__('Min.').'</em>';
$table->data['edit1'][1] .= html_print_input_text ('min_warning', '', '', 5, 15, true);
$table->data['edit1'][1] .= '<br /><em>'.__('Max.').'</em>';
$table->data['edit1'][1] .= html_print_input_text ('max_warning', '', '', 5, 15, true);
$table->data['edit1'][1] .= '<br /><em>'.__('Str.').'</em>';
$table->data['edit1'][1] .= html_print_input_text ('str_warning', '', '', 5, 15, true);
$table->data['edit1'][2] = __('Critical status');
$table->data['edit1'][3] = '<em>'.__('Min.').'</em>';
$table->data['edit1'][3] .= html_print_input_text ('min_critical', '', '', 5, 15, true);
$table->data['edit1'][3] .= '<br /><em>'.__('Max.').'</em>';
$table->data['edit1'][3] .= html_print_input_text ('max_critical', '', '', 5, 15, true);
$table->data['edit1'][3] .= '<br /><em>'.__('Str.').'</em>';
$table->data['edit1'][3] .= html_print_input_text ('str_critical', '', '', 5, 15, true);

$table->data['edit2'][0] = __('Interval');
$table->data['edit2'][1] = html_print_extended_select_for_time ('module_interval', 0, '', __('No change'), '0', 10, true, 'width: 150px');
$table->data['edit2'][2] = __('Disabled');
$table->data['edit2'][3] = html_print_select(array('' => __('No change'), '1' => __('Yes'), '0' => __('No')),'disabled','','','', '', true);

$table->data['edit3'][0] = __('Post process').ui_print_help_icon ('postprocess', true);
$table->data['edit3'][1] = html_print_input_text ('post_process', '', '', 10, 15, true);
$table->data['edit3'][2] = __('SMNP community');
$table->data['edit3'][3] = html_print_input_text ('snmp_community', '', '', 10, 15, true);
$table->data['edit35'][0] = '';
$table->data['edit35'][1] = '';
$table->data['edit35'][2] = __('SNMP version');
$table->data['edit35'][3] = html_print_select ($snmp_versions, 'tcp_send', '', '', __('No change'), '', true, false, false, '');
$table->data['edit36'][0] = __('Auth user');
$table->data['edit36'][1] = html_print_input_text ('plugin_user_snmp', '', '', 15, 60, true);
$table->data['edit36'][2] = __('Auth password') . ui_print_help_tip(__("The pass length must be eight character minimum."), true);
$table->data['edit36'][3] = html_print_input_text ('plugin_pass_snmp', '', '', 15, 60, true);
$table->data['edit37'][0] = __('Privacy method');
$table->data['edit37'][1] = html_print_select(array('DES' => __('DES'), 'AES' => __('AES')), 'custom_string_1', '', '', __('No change'), '', true);
$table->data['edit37'][2] = __('Privacy pass') . ui_print_help_tip(__("The pass length must be eight character minimum."), true);
$table->data['edit37'][3] = html_print_input_text ('custom_string_2', '', '', 15, 60, true);
$table->data['edit38'][0] = __('Auth method');
$table->data['edit38'][1] = html_print_select(array('MD5' => __('MD5'), 'SHA' => __('SHA')), 'plugin_parameter', '', '', __('No change'), '', true);
$table->data['edit38'][2] = __('Security level');
$table->data['edit38'][3] = html_print_select(array('noAuthNoPriv' => __('Not auth and not privacy method'),
	'authNoPriv' => __('Auth and not privacy method'), 'authPriv' => __('Auth and privacy method')), 'custom_string_3', '', '', __('No change'), '', true);

$table->data['edit4'][0] = __('Valid values');
$table->data['edit4'][1] = '<em>'.__('Min.').'</em>';
$table->data['edit4'][1] .= html_print_input_text ('min', '', '', 5, 15, true);
$table->data['edit4'][1] .= '<br /><em>'.__('Max.').'</em>';
$table->data['edit4'][1] .= html_print_input_text ('max', '', '', 5, 15, true);
$table->data['edit4'][2] = __('Module group');
$module_groups = array_merge(array(0 => 'Not assigned'), modules_get_modulegroups());
$table->data['edit4'][3] = html_print_select ($module_groups,
	'id_module_group', '', '', __('No change'), '', true, false, false);

$table->data['edit5'][0] = __('Username');
$table->data['edit5'][1] = html_print_input_text ('plugin_user', '', '', 15, 60, true);
$table->data['edit5'][2] = __('Password');
$table->data['edit5'][3] = html_print_input_password ('plugin_pass', '', '', 15, 60, true);

// Export target
$table->data['edit6'][0] = __('Export target');
$targets2 = db_get_all_rows_sql ("SELECT id, name FROM tserver_export ORDER by name");
if ($targets2 == null)
	$targets2 = array();
$targets =  array_merge(array(0 => __('None')), $targets2 );
$table->data['edit6'][1] = html_print_select ($targets, 'id_export', '','', __('No change'), '', true, false, false);
$table->data['edit6'][2] = __('Unit');
$table->data['edit6'][3] = html_print_input_text ('unit', '', '', 15, 60, true);


/* FF stands for Flip-flop */
$table->data['edit7'][0] = __('FF threshold') . ' ' . ui_print_help_icon ('ff_threshold', true);
$table->data['edit7'][1] = html_print_input_text ('min_ff_event', '', '', 5, 15, true);
$table->data['edit7'][2] = __('Historical data');
$table->data['edit7'][3] = html_print_select(array('' => __('No change'), '1' => __('Yes'), '0' => __('No')),'history_data','','','', '', true);

/* Tags avalaible */
$id_tag = array();
$table->data['edit8'][2] = __('Tags');
$table->data['edit8'][3] = html_print_select_from_sql ('SELECT id_tag, name FROM ttag ORDER BY name',
	'id_tag[]', $id_tag, '',__('None'),'0', true, true, false, false);

echo '<form method="post" action="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&option=edit_modules" id="form_edit">';
html_print_table ($table);

echo '<div class="action-buttons" style="width: '.$table->width.'" onsubmit="if (!confirm(\' '.__('Are you sure?').'\')) return false;">';
html_print_input_hidden ('update', 1);
html_print_submit_button (__('Update'), 'go', false, 'class="sub upd"');
echo '</div>';
echo '</form>';

echo '<h3 class="error invisible" id="message"> </h3>';
//Hack to translate text "none" in PHP to javascript
echo '<span id ="none_text" style="display: none;">' . __('None') . '</span>';
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
		$('input[type=checkbox]').attr('checked', false);
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
	function show_form() {
		$("td#delete_table-0-1, td#delete_table-edit1-1, td#delete_table-edit2-1").css ("width", "300px");
		$("#form_edit input[type=text]").attr ("value", "");
		$("#form_edit input[type=checkbox]").not ("#checkbox-recursion").removeAttr ("checked");
		$("tr#delete_table-edit1, tr#delete_table-edit2, tr#delete_table-edit3, tr#delete_table-edit35, tr#delete_table-edit4, tr#delete_table-edit5, tr#delete_table-edit6, tr#delete_table-edit7, tr#delete_table-edit8").show ();
	}
	
	function clean_lists() {
		$("#id_agents").html('<?php echo __('None'); ?>');
		$("#module_name").html('<?php echo __('None'); ?>');
		$("#agents").html('<?php echo __('None'); ?>');
		$("#module").html('<?php echo __('None'); ?>');
		$("tr#delete_table-edit1, tr#delete_table-edit2, tr#delete_table-edit3, tr#delete_table-edit35, tr#delete_table-edit36, tr#delete_table-edit37, tr#delete_table-edit38, tr#delete_table-edit4, tr#delete_table-edit5, tr#delete_table-edit6, tr#delete_table-edit7, tr#delete_table-edit8").hide ();
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
				$("#checkbox-force_group").attr("checked", false);
				$("#groups_select").trigger("change");
			}
			else {
				if (this.id == "checkbox-force_group") {
					$("#checkbox-recursion").attr("checked", false);
				}
				
				if(this.checked) {
					$(".select_agents_row_2").css('display', 'none');
					$("tr#delete_table-edit1, tr#delete_table-edit2, tr#delete_table-edit3, tr#delete_table-edit35, tr#delete_table-edit4, tr#delete_table-edit5, tr#delete_table-edit6, tr#delete_table-edit7, tr#delete_table-edit8").show ();
				}
				else {
					$(".select_agents_row_2").css('display', '');
					if($('#id_agents option:selected').val() == undefined) {
						$("tr#delete_table-edit1, tr#delete_table-edit2, tr#delete_table-edit3, tr#delete_table-edit35, tr#delete_table-edit4, tr#delete_table-edit5, tr#delete_table-edit6, tr#delete_table-edit7, tr#delete_table-edit8").hide ();
					}
				}
			}
		}
	);
	
	$("#module_name").change (show_form);
	$("#id_agents").change (show_form);
	
	$("#form_edit input[name=selection_mode]").change (function () {
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
	
	$('#tcp_send').change(function() {
		if($(this).val() == 3) {
			$("tr#delete_table-edit36, tr#delete_table-edit37, tr#delete_table-edit38").show();
		}
		else {
			$("tr#delete_table-edit36, tr#delete_table-edit37, tr#delete_table-edit38").hide();
		}
	});
	
	$("#groups_select").change (
		function () {

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
			
			$("tr#delete_table-edit1, tr#delete_table-edit2, tr#delete_table-edit3, tr#delete_table-edit35, tr#delete_table-edit4, tr#delete_table-edit5, tr#delete_table-edit6, tr#delete_table-edit7, tr#delete_table-edit8").hide ();
			
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
