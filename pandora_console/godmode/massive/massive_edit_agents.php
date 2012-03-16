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

// Load global vars
check_login ();

if (! check_acl ($config['id_user'], 0, "AW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access massive agent deletion section");
	require ("general/noaccess.php");
	return;
}

require_once ('include/functions_agents.php');
require_once ('include/functions_ui.php');
require_once ('include/functions_alerts.php');
require_once ('include/functions_modules.php');
require_once ('include/functions_servers.php');
require_once ('include/functions_gis.php');
require_once ('include/functions_users.php');

if (is_ajax ()) {
	$get_n_conf_files = (bool) get_parameter ('get_n_conf_files');
	
	if ($get_n_conf_files) {
		$id_agents = get_parameter('id_agents');
		$cont = 0;
		foreach($id_agents as $id_agent) {
			$name = agents_get_name($id_agent);
			$agent_md5 = md5($name);
			if (file_exists ($config["remote_config"]."/md5/".$agent_md5.".md5"))
				$cont ++;
		}
		
		return;
	}
}

$update_agents = get_parameter ('update_agents', 0);
$recursion = get_parameter ('recursion');

if ($update_agents) {
	$values = array();
	if (get_parameter ('group', '') != -1)
		$values['id_grupo'] = get_parameter ('group');
	if (get_parameter ('interval', '') != '')
		$values['intervalo'] = get_parameter ('interval');
	if (get_parameter ('id_os', '') != -1)
		$values['id_os'] = get_parameter ('id_os');
	if (get_parameter ('id_parent', '') != '')
		$values['id_parent'] = agents_get_agent_id(get_parameter ('id_parent'));
	if (get_parameter ('server_name', '') != -1)
		$values['server_name'] = get_parameter ('server_name');
	if (get_parameter ('description', '') != '')
		$values['comentarios'] = get_parameter ('description');
	if (get_parameter ('mode', '') != -1)
		$values['modo'] = get_parameter ('mode');
	if (get_parameter ('disabled', '') != -1)
		$values['disabled'] = get_parameter ('disabled');
	if (get_parameter ('icon_path', '') != '')
		$values['icon_path'] = get_parameter('icon_path');
	if (get_parameter ('update_gis_data', -1) != -1)
		$values['update_gis_data'] = get_parameter('update_gis_data');
	if (get_parameter ('custom_id', '') != '')
		$values['custom_id'] = get_parameter('custom_id');
	if (get_parameter ('cascade_protection', -1) != -1)
		$values['cascade_protection'] = get_parameter('cascade_protection');
	if (get_parameter ('delete_conf', 0) != 0)
		$values['delete_conf'] = get_parameter('delete_conf');

	$fields = db_get_all_fields_in_table('tagent_custom_fields');
	
	if ($fields === false) $fields = array();
	
	$id_agents = get_parameter('id_agents', false);
	if (!$id_agents) {
		ui_print_error_message(__('No agents selected'));
		$id_agents = array();
	}
	else {
		if (empty($values) && empty($fields)) {
			ui_print_error_message(__('No values changed'));
			$id_agents = array();
		}
	}
	
	// CONF FILE DELETION
	if (isset($values['delete_conf'])) {
		unset($values['delete_conf']);
		$n_deleted = 0;
		foreach ($id_agents as $id_agent) {
			$agent_md5 = md5(agents_get_name($id_agent));
			@unlink ($config["remote_config"]."/md5/".$agent_md5.".md5");
			$result = @unlink ($config["remote_config"]."/conf/".$agent_md5.".conf");
			
			$n_deleted += (int)$result;
		}
		
		
		if ($n_deleted > 0) {
			db_pandora_audit("Masive management", "Delete conf file " . $id_agent);
		}
		else {
			db_pandora_audit("Masive management", "Try to delete conf file " . $id_agent);
		}
		
		
		ui_print_result_message ($n_deleted > 0,
			__('Configuration files deleted successfully').'('.$n_deleted.')',
			__('Configuration files cannot be deleted'));
	}
	
	if (empty($values) && empty($fields)) {
		$id_agents = array();
	}
	
	$n_edited = 0;
	$result = false;
	foreach ($id_agents as $id_agent) {		
		if (!empty($values)) {
			$result = db_process_sql_update ('tagente',
					 $values,
					 array ('id_agente' => $id_agent));
		}
				
		// Update Custom Fields
		foreach ($fields as $field) {
			if (get_parameter_post ('customvalue_'.$field['id_field'], '') != '') {
				$key = $field['id_field'];
				$value = get_parameter_post ('customvalue_'.$field['id_field'], '');
			
				$old_value = db_get_all_rows_filter('tagent_custom_data', array('id_agent' => $id_agent, 'id_field' => $key));
			
				if ($old_value === false) {
					// Create custom field if not exist
					$result = db_process_sql_insert ('tagent_custom_data',
						 array('id_field' => $key,'id_agent' => $id_agent, 'description' => $value));
				}
				else {		
					$result = db_process_sql_update ('tagent_custom_data',
						 array('description' => $value),
						 array('id_field' => $key,'id_agent' => $id_agent));
				}
			}
		}
		
		$n_edited += (int)$result;
	}
	
	
	if ($result !== false) {
		db_pandora_audit("Masive management", "Update agent " . $id_agent, false, false, json_encode($fields));
	}
	else {
		if (isset ($id_agent)) {
			db_pandora_audit("Masive management", "Try to update agent " . $id_agent, false, false, json_encode($fields));
		}
	}
	
	
	ui_print_result_message ($result !== false,
			__('Agents updated successfully').'('.$n_edited.')',
			__('Agents cannot be updated'));
	
}
$id_group = 0;

$groups = users_get_groups();

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
$table->data[0][0] = __('Group');
$table->data[0][1] = html_print_select_groups(false, "AR", true, 'id_group', $id_group,
	false, '', '', true);
$table->data[0][2] = __('Group recursion');
$table->data[0][3] = html_print_checkbox ("recursion", 1, $recursion, true, false);

$table->data[1][0] = __('Agents');
$table->data[1][0] .= '<span id="agent_loading" class="invisible">';
$table->data[1][0] .= html_print_image('images/spinner.png', true);
$table->data[1][0] .= '</span>';
$enabled_agents = agents_get_group_agents ($id_group, array('disabled' => 0), "none");
$all_agents = agents_get_group_agents ($id_group, array('disabled' => 1), "none") + $enabled_agents;

$table->data[1][1] = html_print_select ($all_agents,
	'id_agents[]', 0, false, '', '', true, true);

echo '<form method="post" id="form_agent" action="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&option=edit_agents">';
html_print_table ($table);

$nombre_agente = "";
$direccion_agente = "";
$id_agente = 0;
$id_parent = 0;
$cascade_protection = 0;
$group = 0;
$interval = '';
$id_os = 0;
$server_name = 0;
$description = "";

echo '<div id="form_agents" style="display: none;">';

$table->width = '95%';
$table->class = "databox_color";

$table->head = array ();
$table->style = array ();
$table->style[0] = 'font-weight: bold; width: 150px;';
$table->data = array ();

$groups = users_get_groups ($config["id_user"], "AR",false);
$agents = agents_get_group_agents (array_keys ($groups));

$table->data[0][0] = __('Parent');
$table->data[0][1] = html_print_input_text_extended ('id_parent', agents_get_name ($id_parent), 'text-id_parent', '', 30, 100, false, '',
	array('style' => 'background: url(images/lightning.png) no-repeat right;'), true)
	. '<a href="#" class="tip">&nbsp;<span>' . __("Type at least two characters to search") . '</span></a>';

$table->data[0][1] .= html_print_checkbox ("cascade_protection", 1, $cascade_protection, true).__('Cascade protection'). "&nbsp;" . ui_print_help_icon("cascade_protection", true);

$table->data[1][0] = __('Group');
$table->data[1][1] = html_print_select_groups(false, "AR", false, 'group', $group, '', __('No change'), -1, true, false, true, '', false, 'width: 150px;');

$table->data[2][0] = __('Interval');

$table->data[2][1] = html_print_extended_select_for_time ('interval', $interval, '', '', '0', 10, true, 'width: 150px');

$table->data[3][0] = __('OS');
$table->data[3][1] = html_print_select_from_sql ('SELECT id_os, name FROM tconfig_os',
	'id_os', $id_os, '', __('No change'), -1, true, false, true, false, 'width: 105px;');
$table->data[3][1] .= ' <span id="os_preview">';
$table->data[3][1] .= ui_print_os_icon ($id_os, false, true);
$table->data[3][1] .= '</span>';

// Network server
$none = '';
if ($server_name == '' && $id_agente)
	$none = __('None');
$table->data[4][0] = __('Server');
$table->data[4][1] = html_print_select (servers_get_names (),
	'server_name', $server_name, '', __('No change'), -1, true, false, true, '', false, 'width: 150px;');

// Description
$table->data[5][0] = __('Description');
$table->data[5][1] = html_print_input_text ('description', $description, '', 45, 255, true);

html_print_table ($table);
unset($table);

$custom_id = '';
$mode = -1;
$disabled = -1;
$new_agent = true;
$icon_path = '';
$update_gis_data = -1;
$cascade_protection = -1;

$table->width = '95%';
$table->class = "databox_color";

$table->head = array ();
$table->style = array ();
$table->style[0] = 'font-weight: bold; width: 150px;';
$table->data = array ();

// Custom ID
$table->data[0][0] = __('Custom ID');
$table->data[0][1] = html_print_input_text ('custom_id', $custom_id, '', 16, 255, true);

// Learn mode / Normal mode
$table->data[1][0] = __('Module definition') . ui_print_help_icon("module_definition", true);
$table->data[1][1] = __('No change').' '.html_print_radio_button_extended ("mode", -1, '', $mode, false, '', 'style="margin-right: 40px;"', true);
$table->data[1][1] .= __('Learning mode').' '.html_print_radio_button_extended ("mode", 1, '', $mode, false, '', 'style="margin-right: 40px;"', true);
$table->data[1][1] .= __('Normal mode').' '.html_print_radio_button_extended ("mode", 0, '', $mode, false, '', 'style="margin-right: 40px;"', true);

// Status (Disabled / Enabled)
$table->data[2][0] = __('Status');
$table->data[2][1] = __('No change').' '.html_print_radio_button_extended ("disabled", -1, '', $disabled, false, '', 'style="margin-right: 40px;"', true);
$table->data[2][1] .= __('Disabled').' '.html_print_radio_button_extended ("disabled", 1, '', $disabled, false, '', 'style="margin-right: 40px;"', true);
$table->data[2][1] .= __('Active').' '.html_print_radio_button_extended ("disabled", 0, '', $disabled, false, '', 'style="margin-right: 40px;"', true);

// Remote configuration
$table->data[3][0] = __('Remote configuration');

// Delete remote configuration
$table->data[3][1] = '<div id="delete_configurations" style="display: none">'. __('Delete available remote configurations').' (';
$table->data[3][1] .= '<span id="n_configurations"></span>';
$table->data[3][1] .= ') '.html_print_radio_button_extended ("delete_conf", 1, '', $disabled, false, '', 'style="margin-right: 40px;"', true).'</div>';

$table->data[3][1] .= '<div id="not_available_configurations" style="display: none"><em>'.__('Not available').'</em></div>';		
	
$listIcons = gis_get_array_list_icons();

$arraySelectIcon = array();
foreach ($listIcons as $index => $value) $arraySelectIcon[$index] = $index;

$path = 'images/gis_map/icons/'; //TODO set better method the path
if($icon_path == '') {
	$display_icons = 'none';
	// Hack to show no icon. Use any given image to fix not found image errors
	$path_without = "images/spinner.png";
	$path_default = "images/spinner.png";
	$path_ok = "images/spinner.png";
	$path_bad = "images/spinner.png";
	$path_warning = "images/spinner.png";
}
else {
	$display_icons = '';
	$path_without = $path . $icon_path . ".default.png";
	$path_default = $path . $icon_path . ".default.png";
	$path_ok = $path . $icon_path . ".ok.png";
	$path_bad = $path . $icon_path . ".bad.png";
	$path_warning = $path . $icon_path . ".warning.png";
}

$table->data[4][0] = __('Agent icon');
$table->data[4][1] = html_print_select($arraySelectIcon, "icon_path", $icon_path, "changeIcons();", __('None'), '', true) .
	'&nbsp;' . __('Without status') . ': ' . html_print_image($path_without, true, array("id" => 'icon_without_status',"style" => 'display:'.$display_icons.';')) .
	'&nbsp;' . __('Default') . ': ' . html_print_image($path_default, true, array("id" => 'icon_default',"style" => 'display:'.$display_icons.';')) .
	'&nbsp;' . __('Ok') . ': ' .  html_print_image($path_ok, true, array("id" => 'icon_ok',"style" => 'display:'.$display_icons.';')) .
	'&nbsp;' . __('Bad') . ': ' . html_print_image($path_bad, true, array("id" => 'icon_bad',"style" => 'display:'.$display_icons.';')) . 
	'&nbsp;' . __('Warning') . ': ' .  html_print_image($path_warning, true, array("id" => 'icon_warning',"style" => 'display:'.$display_icons.';'));

if ($config['activate_gis']) {
	$table->data[5][0] = __('Ignore new GIS data:');
	$table->data[5][1] = __('No change').' '.html_print_radio_button_extended ("update_gis_data", -1, '', $update_gis_data, false, '', 'style="margin-right: 40px;"', true);
	$table->data[5][1] .= __('Disabled').' '.html_print_radio_button_extended ("update_gis_data", 0, '', $update_gis_data, false, '', 'style="margin-right: 40px;"', true);
	$table->data[5][1] .= __('Active').' '.html_print_radio_button_extended ("update_gis_data", 1, '', $update_gis_data, false, '', 'style="margin-right: 40px;"', true);
}

ui_toggle(html_print_table ($table, true), __('Advanced options'));
unset($table);

$table->width = '95%';
$table->class = "databox_color";

$table->head = array ();
$table->style = array ();
$table->style[0] = 'font-weight: bold; width: 150px;';
$table->data = array ();

$fields = db_get_all_fields_in_table('tagent_custom_fields');

if ($fields === false) $fields = array();

foreach ($fields as $field) {
	
	$data[0] = '<b>'.$field['name'].'</b>';
		
	$custom_value = db_get_value_filter('description', 'tagent_custom_data', array('id_field' => $field['id_field'], 'id_agent' => $id_agente));
	
	if ($custom_value === false) {
		$custom_value = '';
	}
	
	$data[1] = html_print_textarea ('customvalue_'.$field['id_field'], 2, 65, $custom_value, 'style="min-height: 30px;"', true);
	
	array_push ($table->data, $data);
}

if (!empty($fields)) {
	ui_toggle(html_print_table ($table, true), __('Custom fields'));
}


echo '<h3 class="error invisible" id="message"> </h3>';

echo '<div class="action-buttons" style="width: '.$table->width.'">';

html_print_submit_button (__('Update'), 'updbutton', false, 'class="sub upd"');
html_print_input_hidden ('update_agents', 1);
html_print_input_hidden ('id_agente', $id_agente);

echo "</div>"; // Shown and hide div

echo '</div></form>';

ui_require_jquery_file ('form');
ui_require_jquery_file ('pandora.controls');


ui_require_jquery_file ('pandora.controls');
ui_require_jquery_file ('ajaxqueue');
ui_require_jquery_file ('bgiframe');
ui_require_jquery_file ('autocomplete');
?>
<script type="text/javascript">
/* <![CDATA[ */
	
//Use this function for change 3 icons when change the selectbox
$(document).ready (function () {
	function changeIcons() {
		icon = $("#icon_path :selected").val();

		$("#icon_without_status").attr("src", "images/spinner.png");
		$("#icon_default").attr("src", "images/spinner.png");
		$("#icon_ok").attr("src", "images/spinner.png");
		$("#icon_bad").attr("src", "images/spinner.png");
		$("#icon_warning").attr("src", "images/spinner.png");
		
		if (icon.length == 0) {
			$("#icon_without_status").attr("style", "display:none;");
			$("#icon_default").attr("style", "display:none;");
			$("#icon_ok").attr("style", "display:none;");
			$("#icon_bad").attr("style", "display:none;");
			$("#icon_warning").attr("style", "display:none;");
		}
		else {
			$("#icon_without_status").attr("src", "<?php echo $path; ?>" + icon + ".default.png");
			$("#icon_default").attr("src", "<?php echo $path; ?>" + icon + ".default.png");
			$("#icon_ok").attr("src", "<?php echo $path; ?>" + icon + ".ok.png");
			$("#icon_bad").attr("src", "<?php echo $path; ?>" + icon + ".bad.png");
			$("#icon_warning").attr("src", "<?php echo $path; ?>" + icon + ".warning.png");
			$("#icon_without_status").attr("style", "");
			$("#icon_default").attr("style", "");
			$("#icon_ok").attr("style", "");
			$("#icon_bad").attr("style", "");
			$("#icon_warning").attr("style", "");
		}
		
		//$("#icon_default").attr("src", "<?php echo $path; ?>" + icon +
	}
	
	function get_n_conf_files(idAgents) {

	}
	
	$("#id_agents").change (function () {
		var idAgents = Array();
		jQuery.each ($("#id_agents option:selected"), function (i, val) {
			idAgents.push($(val).val());
		});
				
		jQuery.post ("ajax.php",
				{"page" : "godmode/massive/massive_edit_agents",
				"get_n_conf_files" : 1,
				"id_agents[]" : idAgents
				},
				function (data, status) {
					if (data == 0) { 
						$("#delete_configurations").attr("style", "display: none");
						$("#not_available_configurations").attr("style", "");
					}
					else {
						$("#n_configurations").text(data);
						$("#not_available_configurations").attr("style", "display: none");
						$("#delete_configurations").attr("style", "");
					}
				},
				"json"
			);
	
		$("#form_agents").attr("style", "");
	});
	
	$("#id_group").change (function () {
		$("#form_agents").attr("style", "display: none");
	});
	
	$("select#id_os").pandoraSelectOS ();
	
	$("#text-id_parent").autocomplete ("ajax.php",
		{
			scroll: true,
			minChars: 2,
			extraParams: {
				page: "godmode/agentes/agent_manager",
				search_parents: 1,
				id_group: function() { return $("#group").val(); },
				id_agent: <?php echo $id_agente ?>
			},
			formatItem: function (data, i, total) {
				if (total == 0)
					$("#text-id_parent").css ('background-color', '#cc0000');
				else
					$("#text-id_parent").css ('background-color', '');
				if (data == "")
					return false;
				return data[0]+'<br><span class="ac_extra_field"><?php echo __("IP") ?>: '+data[1]+'</span>';
			},
			delay: 200
		}
	);

	var recursion;
	$("#checkbox-recursion").click(function (){
		recursion = this.checked ? 1 : 0;
		$("#id_group").trigger("change");
	});
	
	$("#id_group").pandoraSelectGroupAgent ({
		agentSelect: "select#id_agents",
		recursion: function() {return recursion}
	});
	
	$("#id_group").pandoraSelectGroupAgentDisabled ({
		agentSelect: "select#id_agents",
		recursion: function() {return recursion}
	});
});
</script>
