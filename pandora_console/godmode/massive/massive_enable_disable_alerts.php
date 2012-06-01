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
		"Trying to access massive alert deletion");
	require ("general/noaccess.php");
	return;
}

require_once ('include/functions_agents.php');
require_once ('include/functions_alerts.php');
require_once ('include/functions_users.php');

if (is_ajax ()) {
	$get_alerts = (bool) get_parameter ('get_alerts');
	
	if ($get_alerts) {
		$id_agents = get_parameter ('id_agents');
		$get_templates = (bool) get_parameter ('get_templates');

		if ($get_templates) {
			if (!is_array($id_agents)) {
				echo json_encode ('');
				return;
			}
			$alert_templates = agents_get_alerts_simple ($id_agents);
			echo json_encode (index_array ($alert_templates, 'id_alert_template', 'template_name'));
			return;
		} else {
			$id_alert_templates = (array) get_parameter ('id_alert_templates');
			$disabled = (int) get_parameter ('disabled');

			$agents_alerts = alerts_get_agents_with_alert_template ($id_alert_templates, false,
				array('order' => 'tagente.nombre, talert_template_modules.disabled', 'talert_template_modules.disabled' => $disabled), 
				array ('LEFT(CONCAT(LEFT(tagente.nombre,40), " - ", tagente_modulo.nombre), 85) as agent_agentmodule_name', 
				'talert_template_modules.id as template_module_id'), $id_agents);

			echo json_encode (index_array ($agents_alerts, 'template_module_id', 'agent_agentmodule_name'));
			return;
		}
	}
	return;
}

$id_group = (int) get_parameter ('id_group');
$id_agents = (array) get_parameter ('id_agents');
$action = (string) get_parameter ('action', '');
$recursion = get_parameter ('recursion');

$result = false;

switch($action) {
	case 'enable_alerts':
		$id_alert_templates = (int) get_parameter ('id_alert_template_disabled', 0);
		$id_disabled_alerts = get_parameter_post ('id_disabled_alerts', array());
		foreach($id_disabled_alerts as $id_alert) {
			$result = alerts_agent_module_disable ($id_alert, false);
		}
		
		ui_print_result_message ($result, __('Successfully enabled'), __('Could not be enabled'));
		
		$info = 'Alert: ' . json_encode($id_disabled_alerts);	
		if ($result) {
			db_pandora_audit("Masive management", "Enable alert", false, false, $info);
		}
		else {
			db_pandora_audit("Masive management", "Fail try to enable alert", false, false, $info);
		}
		break;
	case 'disable_alerts':
		$id_alert_templates = (int) get_parameter ('id_alert_template_enabled', 0);
		$id_enabled_alerts = get_parameter_post ('id_enabled_alerts', array());
		
		foreach($id_enabled_alerts as $id_alert) {
			$result = alerts_agent_module_disable ($id_alert, true);
		}
		
		ui_print_result_message ($result, __('Successfully disabled'), __('Could not be disabled'));
		
		$info = 'Alert: ' . json_encode($id_enabled_alerts);	
		if ($result) {
			db_pandora_audit("Masive management", "Disable alert", false, false, $info);
		}
		else {
			db_pandora_audit("Masive management", "Fail try to Disable alert", false, false, $info);
		}
		break;
	default:
		$id_alert_templates = (int) get_parameter ('id_alert_template', 0);
		break;
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
$table->size[1] = '55%';
$table->size[2] = '15%';
$table->size[3] = '15%';

$table->data = array ();

$table->data[0][0] = '<form method="post" id="form_alerts" action="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&tab=massive_alerts&option=enable_disable_alerts&action=disable_alerts">';
$table->data[0][0] .= html_print_input_hidden('id_alert_template_enabled', $id_alert_templates, true);

$table->data[0][0] .= __('Group');
$table->data[0][1] = html_print_select_groups(false, "AR", true, 'id_group', $id_group, '', '', '', true);
$table->data[0][2] = __('Group recursion');
$table->data[0][3] = html_print_checkbox ("recursion", 1, $recursion, true, false);

$table->data[1][0] = __('Agents');
$table->data[1][0] .= '<span id="agent_loading" class="invisible">';
$table->data[1][0] .= html_print_image("images/spinner.png", true);
$table->data[1][0] .= '</span>';
$table->data[1][1] = html_print_select (agents_get_group_agents ($id_group, false, "none"),
        'id_agents[]', 0, false, '', '', true, true);

$table->data[2][0] = __('Alert template');
$table->data[2][0] .= '<span id="template_loading" class="invisible">';
$table->data[2][0] .= html_print_image("images/spinner.png", true);
$table->data[2][0] .= '</span>';
$table->data[2][1] = html_print_select ('',  'id_alert_templates[]', '', '', '', '', true, true, true, '', true);
	
$table->data[3][0] = __('Enabled alerts').ui_print_help_tip(__('Format').":<br> ".__('Agent')." - ".__('Module'), true);
$table->data[3][0] .= '<span id="alerts_loading" class="invisible">';
$table->data[3][0] .= html_print_image("images/spinner.png", true);
$table->data[3][0] .= '</span>';
$agents_alerts = alerts_get_agents_with_alert_template ($id_alert_templates, $id_group,
	false, array ('tagente.nombre', 'tagente.id_agente'));
$table->data[3][1] = html_print_select (index_array ($agents_alerts, 'id_agente', 'nombre'),
	'id_enabled_alerts[]', '', '', '', '', true, true, true, '', $id_alert_templates == 0);

$table->data[4][0] = __('Action');

$table->data[4][1] = "<table border='0' width='100%'><tr><td>".html_print_input_image ('disable_alerts', 'images/darrowdown.png', 1, 'margin-left: 150px;', true, array ('title' => __('Disable selected alerts')))."</td><td>";
$table->data[4][1] .= '</form>';
$table->data[4][1] .= '<form method="post" action="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&tab=massive_alerts&option=enable_disable_alerts&action=enable_alerts" onsubmit="if (! confirm(\''.__('Are you sure?').'\')) return false;">';
$table->data[4][1] .= html_print_input_hidden('id_alert_template_disabled', $id_alert_templates, true);
$table->data[4][1] .= html_print_input_image ('enable_alerts', 'images/darrowup.png', 1, 'margin-left: 200px;', true, array ('title' => __('Enable selected alerts')))."</td></tr></table>";

$table->data[5][0] = __('Disabled alerts').ui_print_help_tip(__('Format').":<br> ".__('Agent')." - ".__('Module'), true);
$table->data[5][0] .= '<span id="alerts_loading2" class="invisible">';
$table->data[5][0] .= html_print_image("images/spinner.png", true);
$table->data[5][0] .= '</span>';
$table->data[5][1] = html_print_select (index_array ($agents_alerts, 'id_agente2', 'nombre'),
	'id_disabled_alerts[]', '', '', '', '', true, true, true, '', $id_alert_templates == 0);
$table->data[5][1] .= '</form>';

html_print_table ($table);

echo '<h3 class="error invisible" id="message"> </h3>';

ui_require_jquery_file ('form');
ui_require_jquery_file ('pandora.controls');
?>

<script type="text/javascript">
/* <![CDATA[ */
$(document).ready (function () {
	clear_alert_fields();

	var recursion;
	$("#checkbox-recursion").click(function (){
		recursion = this.checked ? 1 : 0;
		$("#id_group").trigger("change");
	});

	$("#id_group").pandoraSelectGroupAgent ({
                agentSelect: "select#id_agents",
		recursion: function() {return recursion},
		callbackPost: function () {
			clear_alert_fields();
                }
	});

	$("#id_agents").change (function () {
		clear_alert_fields();
		update_alert_templates();
	});
	
	$("#id_alert_templates").change (function () {
		if (this.value != 0) {
			$("#id_enabled_alerts").enable ();
			$("#id_disabled_alerts").enable ();
		} else {
			$("#id_enabled_alerts").disable ();
			$("#id_disabled_alerts").disable ();
		}
		update_alerts();
	});
	
	function update_alert_templates() {
		var idAgents = Array();
		jQuery.each ($("#id_agents option:selected"), function (i, val) {
			idAgents.push($(val).val());
		});
                $("#template_loading").show();

		var $select_template = $("#id_alert_templates").disable ();
		$("option", $select_template).remove ();

		jQuery.post ("ajax.php",
				{"page" : "godmode/massive/massive_enable_disable_alerts",
				"get_alerts" : 1,
				"get_templates" : 1,
				"id_agents[]" : idAgents
				},
				function (data, status) {
					
					options = "";
					var arr=[];
					
					jQuery.each (data, function (id, value) {
						
						/* Get all values in id_alert_templates select	*/
						$("#id_alert_templates option").each(function() {
							  arr.push($(this).val());
						});
					
						/* If the value is not in the select, then add it	*/
						if ($.inArray(id,arr) <= -1)						
							options += "<option value=\""+id+"\">"+value+"</option>";
							
					});
					$("#id_alert_templates").append (options);
					$("#template_loading").hide ();
					$select_template.enable ();
				},
				"json"
			);
	}
	
	function update_alerts() {
		var idAgents = Array();
		jQuery.each ($("#id_agents option:selected"), function (i, val) {
			idAgents.push($(val).val());
		});
		var idAlertTemplates = Array();
		jQuery.each ($("#id_alert_templates option:selected"), function (i, val) {
			idAlertTemplates.push($(val).val());
                });

		var $select = $("#id_enabled_alerts").disable ();
		var $select2 = $("#id_disabled_alerts").disable ();
		$("#alerts_loading").show ();
		$("#alerts_loading2").show ();
		$("option", $select).remove ();
		$("option", $select2).remove ();
		
		jQuery.post ("ajax.php",
			{"page" : "godmode/massive/massive_enable_disable_alerts",
			"get_alerts" : 1,
			"get_templates" : 0,
			"id_agents[]" : idAgents,
			"id_alert_templates[]" : idAlertTemplates,
			"disabled" : 0
			},
			function (data, status) {
				options = "";
				var arr=[];

				jQuery.each (data, function (id, value) {
					
					/* Get all values in id_alert_templates select	*/
					$("#id_enabled_alerts option").each(function() {
						  arr.push($(this).val());
					});
				
					/* If the value is not in the select, then add it	*/
					if ($.inArray(id,arr) <= -1)						
						options += "<option value=\""+id+"\">"+value+"</option>";
				
				});
				$("#id_enabled_alerts").append (options);
				$("#alerts_loading").hide ();
				$select.enable ();
			},
			"json"
		);
		
		jQuery.post ("ajax.php",
			{"page" : "godmode/massive/massive_enable_disable_alerts",
			"get_alerts" : 1,
			"get_templates" : 0,
			"id_agents[]" : idAgents,
			"id_alert_templates[]" : idAlertTemplates,
			"disabled" : 1
			},
			function (data, status) {
				options = "";
				jQuery.each (data, function (id, value) {
					options += "<option value=\""+id+"\">"+value+"</option>";
				});
				$("#id_disabled_alerts").append (options);
				$("#alerts_loading2").hide ();
				$select2.enable ();
			},
			"json"
		);
	}

	function clear_alert_fields() {
                var $select_template = $("#id_alert_templates").disable ();
                var $select_enabled = $("#id_enabled_alerts").disable ();
                var $select_disabled = $("#id_disabled_alerts").disable ();
                $("option", $select_template).remove ();
                $("option", $select_enabled).remove ();
                $("option", $select_disabled).remove ();
        }
	
});
/* ]]> */
</script>
