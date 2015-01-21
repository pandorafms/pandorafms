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
		"Trying to access massive tag addition");
	require ("general/noaccess.php");
	return;
}

require_once ('include/functions_agents.php');
require_once ('include/functions_tags.php');

function process_manage_add ($id_agents, $modules, $id_tags) {
	
	if (empty ($id_agents) || $id_agents[0] == 0) {
		ui_print_error_message(__('No agents selected'));
		return false;
	}
	
	if (empty ($modules)) {
		ui_print_error_message(__('No modules selected'));
		return false;
	}
	
	if (empty ($id_tags)) {
		ui_print_error_message(__('No tags selected'));
		return false;
	}
	
	$modules_id = array();
	
	foreach($modules as $module) {
		foreach($id_agents as $id_agent) {
			 $module_id = modules_get_agentmodule_id($module, $id_agent);
			 $modules_id[] = $module_id['id_agente_modulo'];
		}
	}
	
	if (count($modules) == 1 && $modules[0] == '0') {
		foreach($id_agents as $id_agent) {
			$modules_temp = agents_get_modules($id_agent);
			foreach ($modules_temp as $id_module => $name_module) {
				$modules_id[] = $id_module;
			}
		}
	}
	
	
	$conttotal = 0;
	$contsuccess = 0;
	foreach($modules_id as $id_module) {
		$err_count = tags_insert_module_tag($id_module, $id_tags);
		
		if ($err_count == 0) {
			$contsuccess ++;
		}
		
		$conttotal ++;
	}
	
	if ($contsuccess > 0) {
		db_pandora_audit("Massive management", "Add tags", false, false,
			"");
	}
	else {
		db_pandora_audit("Massive management", "Fail try to add tags",
			false, false, "");
	}
	
	ui_print_result_message ($contsuccess > 0,
		__('Successfully added') . "(" . $contsuccess . "/" . $conttotal . ")",
		__('Could not be added'));
	
}

$id_agents = get_parameter ('id_agents');
$id_tags = get_parameter ('id_tags');
$modules = get_parameter ('module');

$add = (bool) get_parameter_post ('add');

if ($add) {
	process_manage_add ($id_agents, $modules, $id_tags);
}

$groups = users_get_groups ();
$own_info = get_user_info($config['id_user']);
if (!$own_info['is_admin'] && !check_acl ($config['id_user'], 0, "AW"))
	$return_all_group = false;
else   
	$return_all_group = true;

$table->id = 'add_table';
$table->width = '98%';
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
	
$table->data[0][0] = __('Group');
$table->data[0][1] = html_print_select_groups(false, "AW",
	$return_all_group, 'id_group', 0, '', 'Select', -1, true, false,
	true, '', false, 'width:180px;');

$table->data[1][0] = __('Agents');
$table->data[1][0] .= '<span id="agent_loading" class="invisible">';
$table->data[1][0] .= html_print_image('images/spinner.png', true);
$table->data[1][0] .= '</span>';

$agents = agents_get_group_agents(
	array_keys(users_get_groups ($config["id_user"], "AW", false)));
$table->data[1][1] = html_print_select ($agents,
	'id_agents[]', '', '', '', '', true, true, true, '', false, 'width:180px;');

$table->data[1][2] = __('Modules');
$table->data[1][2]  .= '<span id="module_loading" class="invisible">';
$table->data[1][2] .= html_print_image('images/spinner.png', true);
$table->data[1][2] .= '</span>';
$table->data[1][3] = '<input type="hidden" id="modules_selection_mode" value="all" />' .
	html_print_select (array(), 'module[]',	'', false, '', '', true, true, false, '', false, 'width:180px;');


$table->data[2][0] = __('Tags');
$tags = tags_get_all_tags();
$table->data[2][1] = html_print_select ($tags,
	'id_tags[]', '', '', '', '', true, true, true, '', false, 'width:180px;');


echo '<form method="post"
	id="form_tags"
	action="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&option=add_tags">';
html_print_table ($table);

echo '<div class="action-buttons" style="width: '.$table->width.'" onsubmit="if (!confirm(\' '.__('Are you sure?').'\')) return false;">';
html_print_input_hidden ('add', 1);

html_print_submit_button (__('Add'), 'go', false, 'class="sub add"');
echo '</div>';
echo '</form>';

// TODO: Change to iu_print_error system
echo '<h3 class="error invisible" id="message"> </h3>';

//Hack to translate text "none" in PHP to javascript
echo '<span id ="none_text" style="display: none;">' . __('None') . '</span>';

ui_require_jquery_file ('form');
ui_require_jquery_file ('pandora.controls');
?>

<script type="text/javascript">
/* <![CDATA[ */
$(document).ready (function () {
	$("#checkbox-recursion").click(function () {
		$("#id_group").trigger("change");
	});
	
	$("#id_agents").change(agent_changed_by_multiple_agents);
	
	$("#id_group").change (function () {
		var $select = $("#id_agents").enable ();
		$("#agent_loading").show ();
		$("option", $select).remove ();
		
		jQuery.post ("ajax.php",
			{
				"page" : "godmode/massive/massive_add_alerts",
				"get_agents" : 1,
				"id_group" : this.value,
				"recursion" : $("#checkbox-recursion").is(":checked") ? 1 : 0,
				// Add a key prefix to avoid auto sorting in js object conversion
				"keys_prefix" : "_"
			},
			function (data, status) {
				options = "";
				jQuery.each (data, function (id, value) {
					// Remove keys_prefix from the index
					id = id.substring(1);
					
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
	
	$("#id_group").click (
	function () {
		$(this).css ("width", "auto"); 
	});
	
	$("#id_group").blur (function () {
		$(this).css ("width", "180px"); 
	});
	
	$("#id_agents").click (
	function () {
		$(this).css ("width", "auto");
	});
	
	$("#id_agents").blur (function () {
		$(this).css ("width", "180px"); 
	});
	
	$("#module").click (
	function () {
		$(this).css ("width", "auto"); 
	});
	
	$("#module").blur (function () {
		$(this).css ("width", "180px"); 
	});
	
	$("#modules_selection_mode").change (function() {
		$("#id_agents").trigger('change');
	});
	
});
/* ]]> */
</script>
