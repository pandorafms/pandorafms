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
		"Trying to access massive tag deletion");
	require ("general/noaccess.php");
	return;
}

require_once ('include/functions_agents.php');
require_once ('include/functions_tags.php');

if (is_ajax ()) {
	$get_agents = (bool)get_parameter('get_agents', false);
	$get_modules = (bool)get_parameter('get_modules', false);
	
	if ($get_agents) {
		$id_tag = (int)get_parameter('id_tag', 0);
		
		$agents = tags_get_agents($id_tag);
		
		if (!empty($agents)) {
			$temp = array();
			foreach ($agents as $id_agent) {
				$temp[$id_agent] = agents_get_name($id_agent);
			}
			$agents = $temp;
		}
		
		echo json_encode($agents);
		return;
	}
	
	if ($get_modules) {
		$id_tag = (int)get_parameter('id_tag', 0);
		$id_agents = (array)get_parameter('id_agents', array());
		
		if (!empty($id_agents)) {
			
			foreach ($id_agents as $key => $id) {
				$id_agents[$key] = (int)$id;
			}
			
			$data = db_get_all_rows_sql("
				SELECT nombre
				FROM tagente_modulo
				WHERE id_agente IN (" . implode(',', $id_agents) . ")
					AND id_agente_modulo IN (
						SELECT t1.id_agente_modulo
						FROM ttag_module AS t1
						WHERE id_tag = " . $id_tag . "
							AND id_policy_module = 0)
				GROUP BY nombre;");
			
			if (empty($data)) {
				echo json_encode(array());
			}
			else {
				$modules = array();
				foreach ($data as $row) {
					$modules[] = $row['nombre'];
				}
				
				echo json_encode($modules);
			}
		}
		else {
			echo json_encode(array());
		}
		
		return;
	}
	
	return;
}

function process_manage_delete ($id_agents, $modules, $id_tag) {
	
	if (empty ($id_agents) || $id_agents[0] == 0) {
		ui_print_error_message(__('No agents selected'));
		return false;
	}
	
	if (empty ($modules) || $modules[0] == "0") {
		ui_print_error_message(__('No modules selected'));
		return false;
	}
	
	if (empty ($id_tag)) {
		ui_print_error_message(__('No tag selected'));
		return false;
	}
	
	$modules_id = array();
	foreach ($modules as $module) {
		$data = db_get_all_rows_sql("
			SELECT id_agente_modulo
			FROM tagente_modulo
			WHERE nombre = '" . $module . "'
				AND id_agente IN (" . implode(",", $id_agents) . ")
			");
		
		if (empty($data)) {
			$data = array();
		}
		
		foreach ($data as $row) {
			$modules_id[$row['id_agente_modulo']] = $row['id_agente_modulo'];
		}
	}
	
	$conttotal = 0;
	$contsuccess = 0;
	foreach ($modules_id as $id_module) {
		$success = tags_remove_tag($id_tag, $id_module);
		
		if ($success) {
			$contsuccess ++;
		}
		
		$conttotal ++;
	}
	
	if ($contsuccess > 0) {
		db_pandora_audit("Massive management", "Delete tags", false, false,
			"");
	}
	else {
		db_pandora_audit("Massive management", "Fail try to delete tags",
			false, false, "");
	}
	
	ui_print_result_message ($contsuccess > 0,
		__('Successfully deleted') . "(" . $contsuccess . "/" . $conttotal . ")",
		__('Could not be deleted'));
	
}

$id_agents = get_parameter ('id_agents');
$id_tag = (int)get_parameter ('id_tag');
$modules = get_parameter ('modules');

$delete = (bool) get_parameter_post ('delete');

if ($delete) {
	process_manage_delete ($id_agents, $modules, $id_tag);
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

$table->data[0][0] = __('Tags');
$tags = tags_get_all_tags();
$table->data[0][1] = html_print_select ($tags,
	'id_tag', '', '', '', '', true, false, true, '', false, 'min-width:180px;');

$table->data[1][0] = __('Agents');
$table->data[1][0] .= '<span id="agent_loading" class="invisible">';
$table->data[1][0] .= html_print_image('images/spinner.png', true);
$table->data[1][0] .= '</span>';
$table->data[1][1] = html_print_select (array(),
	'id_agents[]', '', '', '', '', true, true, true, '', false, 'min-width:180px;');

$table->data[2][0] = __('Modules');
$table->data[2][0]  .= '<span id="modules_loading" class="invisible">';
$table->data[2][0] .= html_print_image('images/spinner.png', true);
$table->data[2][0] .= '</span>';
$table->data[2][1] = '<input type="hidden" id="modules_selection_mode" value="all" />' .
	html_print_select (array(), 'modules[]',	'', false, '', '', true, true, false, '', false, 'min-width:180px;');





echo '<form method="post"
	id="form_tags"
	action="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&option=delete_tags">';
html_print_table ($table);

echo '<div class="action-buttons" style="width: '.$table->width.'" onsubmit="if (!confirm(\' '.__('Are you sure?').'\')) return false;">';
html_print_input_hidden ('delete', 1);

html_print_submit_button (__('Delete'), 'go', false, 'class="sub delete"');
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
	$("#id_tag").change(function() {
		
		$("#agent_loading").show();
		
		jQuery.post ("ajax.php",
			{
				"page" : "godmode/massive/massive_delete_tags",
				"get_agents" : 1,
				"id_tag" : this.value,
			},
			function (data, status) {
				$("#id_agents").empty();
				options = "";
				jQuery.each (data, function (id, value) {
					options += "<option value=\""+id+"\">"+value+"</option>";
				});
				if (options == "") {
					$("#id_agents").append(
						"<option value=\"0\"><?php echo __('None');?></option>");
				}
				else {
					$("#id_agents").append (options);
				}
				$("#agent_loading").hide ();
				
				$("#id_agents").trigger('change');
			},
			"json"
		);
	});
	
	//~ $("#id_agents").change(agent_changed_by_multiple_agents);
	$("#id_agents").change(function() {
		//Fill modules
		
		
		$("#modules_loading").show();
		
		jQuery.post ("ajax.php",
			{
				"page" : "godmode/massive/massive_delete_tags",
				"get_modules" : 1,
				"id_tag": $("#id_tag").val(),
				"id_agents[]" : $("#id_agents").val(),
			},
			function (data, status) {
				$("#modules").empty();
				
				options = "";
				jQuery.each (data, function (id, value) {
					options += "<option value=\""+value+"\">"+value+"</option>";
				});
				if (options == "") {
					$("#modules").append(
						"<option value=\"0\"><?php echo __('None');?></option>");
				}
				else {
					$("#modules").append (options);
				}
				$("#modules_loading").hide ();
			},
			"json"
		);
	});
	
	
	$("#id_tag").trigger('change');
	
});
/* ]]> */
</script>
