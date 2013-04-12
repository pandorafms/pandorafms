<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

if (! check_acl ($config['id_user'], 0, "PM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Agent Management");
	require ("general/noaccess.php");
	return;
}

function push_table_row ($row, $id = false) {
	global $table;
	
	if ($id)
		$data = array ($id => $row);
	else
		$data = array ($row);
	
	$table->data = array_merge ($table->data, $data);
}


$table->id = 'network_component';
$table->width = '98%';
$table->class = 'databox_color';
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->style[2] = 'font-weight: bold';
$table->colspan = array ();
if(!enterprise_installed()) {
	$table->colspan[0][1] = 3;
}
$table_simple->colspan[7][1] = 4;
$table_simple->colspan[8][1] = 4;
$table_simple->colspan[9][1] = 4;
$table->data = array ();

$table->data[0][0] = __('Name');
$table->data[0][1] = html_print_input_text ('name', $name, '', 55, 255, true);
if (enterprise_installed()) {
	if(defined('METACONSOLE')) {
		$table->data[0][2] = __('Wizard level');
		$wizard_levels = array('basic' => __('Basic'),
			'advanced' => __('Advanced'));
		$table->data[0][3] = html_print_select($wizard_levels,'wizard_level',$wizard_level,'','',-1,true, false, false). ' ' .ui_print_help_icon ('meta_access', true);
	}
	else {
		$table->data[0][2] = '';
		$table->data[0][3] = html_print_input_hidden('wizard_level', $wizard_level, true);
	}
}

$table->data[1][0] = __('Type') . ' ' . ui_print_help_icon ('module_type', true);
$sql = sprintf ('SELECT id_tipo, descripcion
	FROM ttipo_modulo
	WHERE categoria IN (%s)
	ORDER BY descripcion',
	implode (',', $categories));
$table->data[1][1] = html_print_select_from_sql ($sql, 'type',
	$type, '', '', '', true,
	false, false, false, true, false, false, false, 0);

// Store the relation between id and name of the types on a hidden field
$sql = sprintf ('SELECT id_tipo, nombre
		FROM ttipo_modulo
		WHERE categoria IN (%s)
		ORDER BY descripcion',
		implode (',', $categories));
$type_names = db_get_all_rows_sql($sql);

$type_names_hash = array();
foreach($type_names as $tn) {
	$type_names_hash[$tn['id_tipo']] = $tn['nombre'];
}

$table->data[1][1] .= html_print_input_hidden('type_names',
	base64_encode(json_encode($type_names_hash)),true);

$table->data[1][2] = __('Module group');
$table->data[1][3] = html_print_select_from_sql ('SELECT id_mg, name
	FROM tmodule_group ORDER BY name',
	'id_module_group', $id_module_group, '', '', '', true, false, false,
	false, true, false, false, false, 0);

$table->data[2][0] = __('Group');
$table->data[2][1] = html_print_select (network_components_get_groups (),
	'id_group', $id_group, '', '', '', true, false, false);
$table->data[2][2] = __('Interval');
$table->data[2][3] = html_print_extended_select_for_time ('module_interval' , $module_interval, '', '', '0', false, true);


$table->data[3][0] = __('Warning status') . ' ' . ui_print_help_icon ('warning_status', true);
$table->data[3][1] = '<span id="minmax_warning"><em>'.__('Min.').'&nbsp;</em>&nbsp;';
$table->data[3][1] .= html_print_input_text ('min_warning', $min_warning,
	'', 5, 15, true);
$table->data[3][1] .= '<br /><em>'.__('Max.').'</em>&nbsp;';
$table->data[3][1] .= html_print_input_text ('max_warning', $max_warning,
	'', 5, 15, true) . '</span>';
$table->data[3][1] .= '<span id="string_warning"><em>'.__('Str.').' </em>&nbsp;';
$table->data[3][1] .= html_print_input_text ('str_warning', $str_warning,
	'', 5, 15, true) . '</span>';
$table->data[3][1] .= '<br /><em>'.__('Inverse interval').'</em>';
$table->data[3][1] .= html_print_checkbox ("warning_inverse", 1, $warning_inverse, true);

$table->data[3][2] = __('Critical status'). ' ' . ui_print_help_icon ('critical_status', true);
$table->data[3][3] = '<span id="minmax_critical"><em>'.__('Min.').'&nbsp;</em>&nbsp;';
$table->data[3][3] .= html_print_input_text ('min_critical', $min_critical,
	'', 5, 15, true);
$table->data[3][3] .= '<br /><em>'.__('Max.').'</em>&nbsp;';
$table->data[3][3] .= html_print_input_text ('max_critical', $max_critical,
	'', 5, 15, true) . '</span>';
$table->data[3][3] .= '<span id="string_critical"><em>'.__('Str.').' </em>&nbsp;';
$table->data[3][3] .= html_print_input_text ('str_critical', $str_critical,
	'', 5, 15, true) . '</span>';
$table->data[3][3] .= '<br /><em>'.__('Inverse interval').'</em>';
$table->data[3][3] .= html_print_checkbox ("critical_inverse", 1, $critical_inverse, true);

$table->data[4][0] = __('FF threshold') . ' ' . ui_print_help_icon ('ff_threshold', true);
$table->data[4][1] = html_print_input_text ('ff_event', $ff_event,
	'', 5, 15, true);
$table->data[4][2] = __('Historical data');
$table->data[4][3] = html_print_checkbox ("history_data", 1, $history_data, true);

$table->data[5][0] = __('Min. Value');
$table->data[5][1] = html_print_input_text ('min', $min, '', 5, 15, true). ' ' . ui_print_help_tip (__('Any value below this number is discarted'), true);
$table->data[5][2] = __('Max. Value');
$table->data[5][3] = html_print_input_text ('max', $max, '', 5, 15, true) . ' ' . ui_print_help_tip (__('Any value over this number is discarted'), true);
$table->data[6][0] = __('Unit');
$table->data[6][1] = html_print_input_text ('unit', $unit, '', 12, 25, true);
$table->data[6][2] = $table->data[6][3] = '';

$table->data[7][0] = __('Critical instructions'). ui_print_help_tip(__("Instructions when the status is critical"), true);
$table->data[7][1] = html_print_textarea ('critical_instructions', 2, 65, $critical_instructions, '', true);
$table->colspan[7][1] = 3;

$table->data[8][0] = __('Warning instructions'). ui_print_help_tip(__("Instructions when the status is warning"), true);
$table->data[8][1] = html_print_textarea ('warning_instructions', 2, 65, $warning_instructions, '', true);
$table->colspan[8][1] = 3;

$table->data[9][0] = __('Unknown instructions'). ui_print_help_tip(__("Instructions when the status is unknown"), true);
$table->data[9][1] = html_print_textarea ('unknown_instructions', 2, 65, $unknown_instructions, '', true);
$table->colspan[9][1] = 3;

$next_row = 10;

if (check_acl ($config['id_user'], 0, "PM")) {
	$table->data[$next_row][0] = __('Category');
	$table->data[$next_row][1] = html_print_select(categories_get_all_categories('forselect'), 'id_category', $id_category, '', __('None'), 0, true);
	$table->data[$next_row][2] = $table->data[$next_row][3] = $table->data[$next_row][4] = '';
	$next_row++;
}
else {
	// Store in a hidden field if is not visible to avoid delete the value
	$table->data[9][1] .= html_print_input_hidden ('id_category', $id_category, true);
}

$table->data[$next_row][0] =  __('Tags');

if($tags == '') {
	$tags_condition_not = '1 = 1';
	$tags_condition_in = '1 = 0';
}
else {
	$tags = str_replace(",", "','", $tags);
	$tags_condition_not = "name NOT IN ('".$tags."')";
	$tags_condition_in = "name IN ('".$tags."')";
}

$table->data[$next_row][1] = '<b>' . __('Tags available') . '</b><br>';
$table->data[$next_row][1] .= html_print_select_from_sql (
	"SELECT name, name
	FROM ttag 
	WHERE $tags_condition_not
		ORDER BY name", 'id_tag_available[]', '', '','','',
	true, true, false, false, 'width: 200px', '5');
$table->data[$next_row][2] =  html_print_image('images/darrowright.png', true, array('id' => 'right', 'title' => __('Add tags to module'))); //html_print_input_image ('add', 'images/darrowright.png', 1, '', true, array ('title' => __('Add tags to module')));
$table->data[$next_row][2] .= '<br><br><br><br>' . html_print_image('images/darrowleft.png', true, array('id' => 'left', 'title' => __('Delete tags to module'))); //html_print_input_image ('add', 'images/darrowleft.png', 1, '', true, array ('title' => __('Delete tags to module')));

$table->data[$next_row][3] = '<b>' . __('Tags selected') . '</b><br>';
$table->data[$next_row][3] .=  html_print_select_from_sql (
	"SELECT name, name
	FROM ttag 
	WHERE $tags_condition_in
		ORDER BY name",
	'id_tag_selected[]', '', '','','', true, true, false,
	false, 'width: 200px', '5');
	
$next_row++;
?>
<script type="text/javascript">
	$(document).ready (function () {
		$("#type").change(function () {
			var type_selected = $(this).val();
			var type_names = jQuery.parseJSON(Base64.decode($('#hidden-type_names').val()));
			
			var type_name_selected = type_names[type_selected];
			
			if (type_name_selected.match(/_string$/) == null) {
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
		
		$("#type").trigger('change');
	});
</script>
