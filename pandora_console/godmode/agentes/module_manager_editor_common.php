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

include_once("include/functions_modules.php");
include_once("include/functions_categories.php");

function prepend_table_simple ($row, $id = false) {
	global $table_simple;
	
	if ($id)
		$data = array ($id => $row);
	else
		$data = array ($row);
	
	$table_simple->data = array_merge ($data, $table_simple->data);
}

function push_table_simple ($row, $id = false) {
	global $table_simple;
	
	if ($id)
		$data = array ($id => $row);
	else
		$data = array ($row);
	
	$table_simple->data = array_merge ($table_simple->data, $data);
}

function prepend_table_advanced ($row, $id = false) {
	global $table_advanced;
	
	if ($id)
		$data = array ($id => $row);
	else
		$data = array ($row);
	
	$table_advanced->data = array_merge ($data, $table_advanced->data);
}

function push_table_advanced ($row, $id = false) {
	global $table_advanced;
	
	if ($id)
		$data = array ($id => $row);
	else
		$data = array ($row);
	
	$table_advanced->data = array_merge ($table_advanced->data, $data);
}

function add_component_selection ($id_network_component_type) {
	global $table_simple;
	
	$data = array ();
	$data[0] = __('Using module component').' ';
	$data[0] .= ui_print_help_icon ('network_component', true);
	
	$component_groups = network_components_get_groups ($id_network_component_type);
	$data[1] = '<span id="component_group" class="left">';
	$data[1] .= html_print_select ($component_groups,
		'network_component_group', '', '', '--'.__('Manual setup').'--', 0,
		true, false, false);
	$data[1] .= '</span>';
	$data[1] .= html_print_input_hidden ('id_module_component_type', $id_network_component_type, true);
	$data[1] .= '<span id="no_component" class="invisible error">';
	$data[1] .= __('No component was found');
	$data[1] .= '</span>';
	$data[1] .= '<span id="component" class="invisible right">';
	$data[1] .= html_print_select (array (), 'network_component', '', '',
		'---'.__('Manual setup').'---', 0, true);
	$data[1] .= '</span>';
	$data[1] .= ' <span id="component_loading" class="invisible">';
	$data[1] .= html_print_image('images/spinner.png', true);
	$data[1] .= '</span>';
	
	$table_simple->colspan['module_component'][1] = 3;
	$table_simple->rowstyle['module_component'] = 'background-color: #D4DDC6';
	
	prepend_table_simple ($data, 'module_component');
}

require_once ('include/functions_network_components.php');
enterprise_include_once('include/functions_policies.php');


$disabledBecauseInPolicy = false;
$disabledTextBecauseInPolicy = '';

$page = get_parameter('page', '');
if (strstr($page, "policy_modules") === false && $id_agent_module) {
	if ($config['enterprise_installed'])
		$disabledBecauseInPolicy = policies_is_module_in_policy($id_agent_module) && policies_is_module_linked($id_agent_module);
	else
		$disabledBecauseInPolicy = false;
	if ($disabledBecauseInPolicy)
		$disabledTextBecauseInPolicy = 'disabled = "disabled"';
}

$update_module_id = (int) get_parameter_get ('update_module');

html_print_input_hidden ('moduletype', $moduletype);

$table_simple->id = 'simple';
$table_simple->width = '98%';
$table_simple->class = 'databox_color';
$table_simple->data = array ();
$table_simple->colspan = array ();
$table_simple->style = array ();
$table_simple->style[0] = 'font-weight: bold; vertical-align: top; width: 26%';
$table_simple->style[1] = 'width: 40%';
$table_simple->style[2] = 'font-weight: bold; vertical-align: top';

$table_simple->colspan[4][1] = 3;
$table_simple->colspan[5][1] = 3;
$table_simple->colspan[6][1] = 3;

$table_simple->data[0][0] = __('Name');
$table_simple->data[0][1] = html_print_input_text ('name', io_safe_output($name), '', 45, 100, true, $disabledBecauseInPolicy);

if (!empty($id_agent_module) && isset($id_agente)) {
	$table_simple->data[0][1] .= '&nbsp;<a href="index.php?sec=gagente&tab=module&sec2=godmode/agentes/configurar_agente&id_agente='.$id_agente.'&delete_module='.$id_agent_module.'"
		onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">';
	$table_simple->data[0][1] .= html_print_image ('images/cross.png', true,
		array ('title' => __('Delete module')));
	$table_simple->data[0][1] .= '</a> ';
}

$disabled_enable = 0;
$policy_link = db_get_value('policy_linked', 'tagente_modulo', 'id_agente_modulo', $id_agent_module);
if ($policy_link != 0) {
	$disabled_enable = 1;
}
$table_simple->data[0][2] = __('Disabled');
$table_simple->data[0][3] = html_print_checkbox ("disabled", 1, $disabled, true, $disabled_enable);

$table_simple->data[1][0] = __('Type').' ' . ui_print_help_icon ('module_type', true);
$table_simple->data[1][0] .= html_print_input_hidden ('id_module_type_hidden', $id_module_type, true);

if (isset($id_agent_module)) {
	if ($id_agent_module) {
		$edit = false;
	}
	else {
		$edit = true;
	}
}
else {
	//Run into a policy
	$edit = true;
}

if (!$edit) {
	$sql = sprintf ('SELECT id_tipo, nombre
			FROM ttipo_modulo
			WHERE id_tipo = %s
			ORDER BY descripcion',
			$id_module_type);
		
	$type_names = db_get_all_rows_sql($sql);
	
	$type_names_hash = array();
	foreach ($type_names as $tn) {
		$type_names_hash[$tn['id_tipo']] = $tn['nombre'];
	}
	
	$table_simple->data[1][1] = '<em>'.modules_get_moduletype_description ($id_module_type).' ('.$type_names_hash[$id_module_type].')</em>';
	$table_simple->data[1][1] .= html_print_input_hidden('type_names',base64_encode(json_encode($type_names_hash)),true);
}
else {
	if (isset($id_module_type)) {
		$idModuleType = $id_module_type;
	}
	else {
		$idModuleType = '';
	}
	
	$sql = sprintf ('SELECT id_tipo, descripcion
		FROM ttipo_modulo
		WHERE categoria IN (%s)
		ORDER BY descripcion',
		implode (',', $categories));
		
	$table_simple->data[1][1] = html_print_select_from_sql ($sql, 'id_module_type',
		$idModuleType, '', '', '', true, false, false, $disabledBecauseInPolicy, false, false, 100);
	
	// Store the relation between id and name of the types on a hidden field
	$sql = sprintf ('SELECT id_tipo, nombre
			FROM ttipo_modulo
			WHERE categoria IN (%s)
			ORDER BY descripcion',
			implode (',', $categories));
	
	$type_names = db_get_all_rows_sql($sql);
	
	$type_names_hash = array();
	foreach ($type_names as $tn) {
		$type_names_hash[$tn['id_tipo']] = $tn['nombre'];
	}
	
	$table_simple->data[1][1] .= html_print_input_hidden('type_names',base64_encode(json_encode($type_names_hash)),true);
}

$table_simple->data[1][2] = __('Module group');
$table_simple->data[1][3] = html_print_select_from_sql ('SELECT id_mg, name FROM tmodule_group ORDER BY name',
	'id_module_group', $id_module_group, '', __('Not assigned'), '0',
	true, false, true, $disabledBecauseInPolicy);

$table_simple->data[2][0] = __('Warning status').' ' . ui_print_help_icon ('warning_status', true);

$table_simple->data[2][1] = '';

if (!modules_is_string_type($id_module_type) || $edit) {
	$table_simple->data[2][1] .= '<span id="minmax_warning"><em>'.__('Min. ').'</em>';
	$table_simple->data[2][1] .= html_print_input_text ('min_warning', $min_warning,
		'', 10, 255, true, $disabledBecauseInPolicy);
	$table_simple->data[2][1] .= '<br /><em>'.__('Max.').'</em>';
	$table_simple->data[2][1] .= html_print_input_text ('max_warning', $max_warning,
		'', 10, 255, true, $disabledBecauseInPolicy).'</span>';
}
if (modules_is_string_type($id_module_type) || $edit) {
	$table_simple->data[2][1] .= '<span id="string_warning"><em>'.__('Str.').'</em>';
	$table_simple->data[2][1] .= html_print_input_text ('str_warning', $str_warning, 
		'', 10, 255, true, $disabledBecauseInPolicy).'</span>';
}

$table_simple->data[2][1] .= '<br /><em>'.__('Inverse interval').'</em>';
$table_simple->data[2][1] .= html_print_checkbox ("warning_inverse", 1, $warning_inverse, true);
$table_simple->data[2][2] = __('Critical status').' ' . ui_print_help_icon ('critical_status', true);
$table_simple->data[2][3] = '';

if (!modules_is_string_type($id_module_type) || $edit) {
	$table_simple->data[2][3] .= '<span id="minmax_critical"><em>'.__('Min. ').'</em>';
	$table_simple->data[2][3] .= html_print_input_text ('min_critical', $min_critical,
		'', 10, 255, true, $disabledBecauseInPolicy);
	$table_simple->data[2][3] .= '<br /><em>'.__('Max.').'</em>';
	$table_simple->data[2][3] .= html_print_input_text ('max_critical', $max_critical,
		'', 10, 255, true, $disabledBecauseInPolicy).'</span>';
}
if (modules_is_string_type($id_module_type) || $edit) {
	$table_simple->data[2][3] .= '<span id="string_critical"><em>'.__('Str.').'</em>';
	$table_simple->data[2][3] .= html_print_input_text ('str_critical', $str_critical, 
		'', 10, 255, true, $disabledBecauseInPolicy).'</span>';
}

$table_simple->data[2][3] .= '<br /><em>'.__('Inverse interval').'</em>';
$table_simple->data[2][3] .= html_print_checkbox ("critical_inverse", 1, $critical_inverse, true);

/* FF stands for Flip-flop */
$table_simple->data[3][0] = __('FF threshold').' ' . ui_print_help_icon ('ff_threshold', true);
$table_simple->data[3][1] = html_print_input_text ('ff_event', $ff_event,
	'', 5, 15, true, $disabledBecauseInPolicy);
$table_simple->data[3][2] = __('Historical data');
if($disabledBecauseInPolicy) {
	// If is disabled, we send a hidden in his place and print a false checkbox because HTML dont send disabled fields and could be disabled by error
	$table_simple->data[3][3] = html_print_checkbox ("history_data_fake", 1, $history_data, true, $disabledBecauseInPolicy);
	$table_simple->data[3][3] .= '<input type="hidden" name="history_data" value="'.(int)$history_data.'">';
}
else {
	$table_simple->data[3][3] = html_print_checkbox ("history_data", 1, $history_data, true, $disabledBecauseInPolicy);
}

/* Advanced form part */
$table_advanced->id = 'advanced';
$table_advanced->width = '98%';
$table_advanced->class = 'databox_color';
$table_advanced->data = array ();
$table_advanced->style = array ();
$table_advanced->style[0] = 'font-weight: bold; vertical-align: top';
$table_advanced->style[3] = 'font-weight: bold; vertical-align: top';
$table_advanced->colspan = array ();

$table_advanced->data[0][0] = __('Description');
$table_advanced->colspan[0][1] = 4;
$table_advanced->data[0][1] = html_print_textarea ('description', 2, 65,
	$description, $disabledTextBecauseInPolicy, true);

$table_advanced->data[1][0] = __('Custom ID');
$table_advanced->colspan[1][1] = 2;
$table_advanced->data[1][1] = html_print_input_text ('custom_id', $custom_id,
	'', 20, 65, true);

$table_advanced->data[1][3] = __('FF interval');
$table_advanced->data[1][4] = html_print_input_text ('module_ff_interval', $ff_interval,
	'', 5, 10, true, $disabledBecauseInPolicy).ui_print_help_tip (__('Module execution flip flop time interval (in secs).'), true);

$table_advanced->data[2][0] = __('Interval').ui_print_help_icon ('module_interval', true);

$table_advanced->colspan[2][1] = 2;
$table_advanced->data[2][1] = html_print_extended_select_for_time ('module_interval' , $interval, '', '', '0', false, true, false, false);
	
$table_advanced->data[2][3] = __('Post process').' ' . ui_print_help_icon ('postprocess', true);
$table_advanced->data[2][4] = html_print_input_text ('post_process',
	$post_process, '', 15, 25, true, $disabledBecauseInPolicy);

$table_advanced->data[3][0] = __('Min. Value');
$table_advanced->colspan[3][1] = 2;

$table_advanced->data[3][1] = html_print_input_text ('min', $min, '', 5, 15, true, $disabledBecauseInPolicy). ' ' . ui_print_help_tip (__('Any value below this number is discarted.'), true);
$table_advanced->data[3][3] = __('Max. Value');
$table_advanced->data[3][4] = html_print_input_text ('max', $max, '', 5, 15, true, $disabledBecauseInPolicy). ' ' . ui_print_help_tip (__('Any value over this number is discarted.'), true);

$table_advanced->data[4][0] = __('Export target'); 
// Default text message for export target select and disabled option
$none_text = __('None');
$disabled_export = false;
// If code comes from policies disable export select
global $__code_from;
if ($__code_from == 'policies') {
	$none_text = __('Not needed');
	$disabled_export = true;
} 
$table_advanced->data[4][1] = html_print_select_from_sql ('SELECT id, name FROM tserver_export ORDER BY name',
	'id_export', $id_export, '', $none_text, '0', true, false, false, $disabled_export).ui_print_help_tip (__('In case you use an Export server you can link this module and export data to one these.'), true);
$table_advanced->colspan[4][1] = 4;
$table_advanced->data[5][0] = __('Unit');
$table_advanced->data[5][1] = html_print_input_text ('unit', $unit,
	'', 20, 65, true);
$table_advanced->colspan[5][1] = 4;

/* Tags */
// This var comes from module_manager_editor.php or policy_modules.php
global $__code_from;
$table_advanced->data[6][0] =  __('Tags available');
// Code comes from module_editor
if ($__code_from == 'modules') {
	$__table_modules = 'ttag_module';
	$__id_where = 'b.id_agente_modulo';
	$__id = (int)$id_agent_module;
// Code comes from policy module editor
}
else {
	global $__id_pol_mod;
	$__table_modules= 'ttag_policy_module';
	$__id_where = 'b.id_policy_module';
	$__id = $__id_pol_mod;
}

$table_advanced->data[6][1] = html_print_select_from_sql (
	"SELECT id_tag, name
	FROM ttag 
	WHERE id_tag NOT IN (
		SELECT a.id_tag
		FROM ttag a, $__table_modules b 
		WHERE a.id_tag = b.id_tag AND $__id_where = $__id )
		ORDER BY name", 'id_tag_available[]', '', '','','',
	true, true, false, false, 'width: 200px', '5');
$table_advanced->data[6][2] =  html_print_image('images/darrowright.png', true, array('id' => 'right', 'title' => __('Add tags to module'))); //html_print_input_image ('add', 'images/darrowright.png', 1, '', true, array ('title' => __('Add tags to module')));
$table_advanced->data[6][2] .= '<br><br><br><br>' . html_print_image('images/darrowleft.png', true, array('id' => 'left', 'title' => __('Delete tags to module'))); //html_print_input_image ('add', 'images/darrowleft.png', 1, '', true, array ('title' => __('Delete tags to module')));

$table_advanced->data[6][3] = '<b>' . __('Tags selected') . '</b>';
$table_advanced->data[6][4] =  html_print_select_from_sql (
	"SELECT a.id_tag, name 
	FROM ttag a, $__table_modules b
	WHERE a.id_tag = b.id_tag AND $__id_where = $__id
	ORDER BY name",
	'id_tag_selected[]', '', '','','', true, true, false,
	false, 'width: 200px', '5');
$table_advanced->data[7][0] = __('Quiet');
$table_advanced->data[7][0] .= ui_print_help_tip(__('The module still stores data but the alerts and events will be stop'), true);
$table_advanced->colspan[7][1] = 4;
$table_advanced->data[7][1] = html_print_checkbox('quiet_module', 1,
	$quiet_module, true);

$table_advanced->data[8][0] = __('Critical instructions'). ui_print_help_tip(__("Instructions when the status is critical"), true);
$table_advanced->data[8][1] = html_print_textarea ('critical_instructions', 2, 65, $critical_instructions, '', true);
$table_advanced->colspan[8][1] = 4;

$table_advanced->data[9][0] = __('Warning instructions'). ui_print_help_tip(__("Instructions when the status is warning"), true);
$table_advanced->data[9][1] = html_print_textarea ('warning_instructions', 2, 65, $warning_instructions, '', true);
$table_advanced->colspan[9][1] = 4;

$table_advanced->data[10][0] = __('Unknown instructions'). ui_print_help_tip(__("Instructions when the status is unknown"), true);
$table_advanced->data[10][1] = html_print_textarea ('unknown_instructions', 2, 65, $unknown_instructions, '', true);
$table_advanced->colspan[10][1] = 4;

$table_advanced->data[11][0] = __('Cron') . ui_print_help_tip (__('If cron is set the module interval is ignored and the module runs on the specified date and time'), true);
$table_advanced->data[11][1] = html_print_extended_select_for_cron ($hour, $minute, $mday, $month, $wday, true);
$table_advanced->colspan[11][1] = 4;

$table_advanced->data[12][0] = __('Timeout');
$table_advanced->data[12][1] = html_print_input_text ('max_timeout', $max_timeout, '', 5, 10, true). ' ' . ui_print_help_tip (__('Seconds that agent will wait for the execution of the module.'), true);
$table_advanced->data[12][2] = '';
$table_advanced->data[12][3] = __('Retries');
$table_advanced->data[12][4] = html_print_input_text ('max_retries', $max_retries, '', 5, 10, true). ' ' . ui_print_help_tip (__('Number of retries that the module will attempt to run.'), true);

if (check_acl ($config['id_user'], 0, "PM")) {
	$table_advanced->data[13][0] = __('Category');
	$table_advanced->data[13][1] = html_print_select(categories_get_all_categories('forselect'), 'id_category', $id_category, '', __('None'), 0, true); 
	$table_advanced->colspan[13][1] = 4;
}
else {
	// Store in a hidden field if is not visible to avoid delete the value
	$table_advanced->data[12][4] .= html_print_input_hidden ('id_category', $id_category, true);
}

ui_require_jquery_file('json');

?>

<script type="text/javascript">
/* <![CDATA[ */
$(document).ready (function () {
	$("#right").click (function () {
		jQuery.each($("select[name='id_tag_available[]'] option:selected"), function (key, value) {
			tag_name = $(value).html();
			if (tag_name != <?php echo "'".__('None')."'"; ?>) {
				id_tag = $(value).attr('value');
				$("select[name='id_tag_selected[]']").append($("<option></option>").val(id_tag).html('<i>' + tag_name + '</i>'));
				$("#id_tag_available").find("option[value='" + id_tag + "']").remove();
				$("#id_tag_selected").find("option[value='']").remove();
				if($("#id_tag_available option").length == 0) {
					$("select[name='id_tag_available[]']").append($("<option></option>").val('').html('<i><?php echo __('None'); ?></i>'));
				}
			}
		});
	});
	$("#left").click (function () {
		jQuery.each($("select[name='id_tag_selected[]'] option:selected"), function (key, value) {
				tag_name = $(value).html();
				if (tag_name != <?php echo "'".__('None')."'"; ?>) {
					id_tag = $(value).attr('value');
					$("select[name='id_tag_available[]']").append($("<option>").val(id_tag).html('<i>' + tag_name + '</i>'));
					$("#id_tag_selected").find("option[value='" + id_tag + "']").remove();
					$("#id_tag_available").find("option[value='']").remove();
					if($("#id_tag_selected option").length == 0) {
						$("select[name='id_tag_selected[]']").append($("<option></option>").val('').html('<i><?php echo __('None'); ?></i>'));
					}
				}
		});
	});
	$("#submit-updbutton").click(function () {
		$('#id_tag_selected option').map(function(){
			$(this).attr('selected','selected');
		});
	});
	
	$("#submit-crtbutton").click(function () {
		$('#id_tag_selected option').map(function(){
			$(this).attr('selected','selected');
		});
	});
	
	$("#id_module_type").change(function () {
		var type_selected = $(this).val();
		var type_names = jQuery.parseJSON(Base64.decode($('#hidden-type_names').val()));
		
		var type_name_selected = type_names[type_selected];
		
		if(type_name_selected.match(/_string$/) == null) {
			// Numeric types
			$('#string_critical').hide();
			$('#string_warning').hide();
			$('#minmax_critical').show();
			$('#minmax_warning').show();
		}
		else {
			// String types
			$('#string_critical').show();
			$('#string_warning').show();
			$('#minmax_critical').hide();
			$('#minmax_warning').hide();
		}
	});
	
	$("#id_module_type").trigger('change');
});
/* ]]> */
</script>
