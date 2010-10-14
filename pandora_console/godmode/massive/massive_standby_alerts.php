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
	audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "ACL Violation",
		"Trying to access massive alert deletion");
	require ("general/noaccess.php");
	return;
}

require_once ('include/functions_agents.php');
require_once ('include/functions_alerts.php');

if (is_ajax ()) {
	$get_alerts = (bool) get_parameter ('get_alerts');
	
	if ($get_alerts) {
		$id_group = (int) get_parameter ('id_group');
		$id_alert_template = (int) get_parameter ('id_alert_template');
		$standby = (int) get_parameter ('standby');

		$agents_alerts = get_agents_with_alert_template ($id_alert_template, $id_group,
			array('order' => 'tagente.nombre, talert_template_modules.standby', '`talert_template_modules`.standby' => $standby), 
			array ('LEFT(CONCAT(LEFT(tagente.nombre,40), " - ", tagente_modulo.nombre), 85) as agent_agentmodule_name', 
			'talert_template_modules.id as template_module_id'));

		echo json_encode (index_array ($agents_alerts, 'template_module_id', 'agent_agentmodule_name'));
		return;
	}
	return;
}

$id_group = (int) get_parameter ('id_group');
$action = (string) get_parameter ('action', '');

$result = false;

switch($action) {
	case 'set_off_standby_alerts':
		$id_alert_template = (int) get_parameter ('id_alert_template_standby', 0);
		$id_standby_alerts = get_parameter_post ('id_standby_alerts', array());
		foreach($id_standby_alerts as $id_alert) {
			$result = set_alerts_agent_module_standby ($id_alert, false);
		}
			print_result_message ($result, __('Successfully set off standby'), __('Could not be set off standby'));
		break;
	case 'set_standby_alerts':
		$id_alert_template = (int) get_parameter ('id_alert_template_standby', 0);
		$id_not_standby_alerts = get_parameter_post ('id_not_standby_alerts', array());
		
		foreach($id_not_standby_alerts as $id_alert) {
			$result = set_alerts_agent_module_standby ($id_alert, true);
		}
			print_result_message ($result, __('Successfully set standby'), __('Could not be set standby'));
		break;
	default:
		$id_alert_template = (int) get_parameter ('id_alert_template', 0);
		break;
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

$templates = get_alert_templates (false, array ('id', 'name'));
$table->data[0][0] = '<form method="post" action="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&tab=massive_alerts&option=standby_alerts&action=set_standby_alerts" onsubmit="if (! confirm(\''.__('Are you sure?').'\')) return false;">';
$table->data[0][0] .= print_input_hidden('id_alert_template_not_standby', $id_alert_template, true);
$table->data[0][0] .= __('Alert template');
$table->data[0][1] = print_select (index_array ($templates, 'id', 'name'),
	'id_alert_template', $id_alert_template, false, __('All'), 0, true);
	
$table->data[1][0] = __('Group');
$table->data[1][1] = print_select_groups(false, "AR", true, 'id_group', $id_group,
	'', '', '', true, false, true, '');

$table->data[2][0] = __('Not standby alerts').print_help_tip(__('Format').":<br> ".__('Agent')." - ".__('Module'), true);
$table->data[2][0] .= '<span id="alerts_loading" class="invisible">';
$table->data[2][0] .= '<img src="images/spinner.png" />';
$table->data[2][0] .= '</span>';
$agents_alerts = get_agents_with_alert_template ($id_alert_template, $id_group,
	false, array ('tagente.nombre', 'tagente.id_agente'));
$table->data[2][1] = print_select (index_array ($agents_alerts, 'id_agente', 'nombre'),
	'id_not_standby_alerts[]', '', '', '', '', true, true, true, '', $id_alert_template == 0);

$table->data[3][0] = __('Action');

$table->data[3][1] = "<table border='0' width='100%'><tr><td>".print_input_image ('standby_alerts', 'images/darrowdown.png', 1, 'margin-left: 150px;', true, array ('title' => __('Set standby selected alerts')))."</td><td>";
$table->data[3][1] .= '</form>';
$table->data[3][1] .= '<form method="post" action="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&tab=massive_alerts&option=standby_alerts&action=set_off_standby_alerts" onsubmit="if (! confirm(\''.__('Are you sure?').'\')) return false;">';
$table->data[3][1] .= print_input_hidden('id_alert_template_standby', $id_alert_template, true);
$table->data[3][1] .= print_input_image ('set_off_standby_alerts', 'images/darrowup.png', 1, 'margin-left: 200px;', true, array ('title' => __('Set standby selected alerts')))."</td></tr></table>";

$table->data[4][0] = __('Standby alerts').print_help_tip(__('Format').":<br> ".__('Agent')." - ".__('Module'), true);
$table->data[4][0] .= '<span id="alerts_loading2" class="invisible">';
$table->data[4][0] .= '<img src="images/spinner.png" />';
$table->data[4][0] .= '</span>';
$table->data[4][1] = print_select (index_array ($agents_alerts, 'id_agente2', 'nombre'),
	'id_standby_alerts[]', '', '', '', '', true, true, true, '', $id_alert_template == 0);
$table->data[4][1] .= '</form>';

print_table ($table);

echo '<h3 class="error invisible" id="message"> </h3>';

require_jquery_file ('form');
require_jquery_file ('pandora.controls');
?>

<script type="text/javascript">
/* <![CDATA[ */
$(document).ready (function () {
	update_alerts();
	
	$("#id_alert_template").change (function () {
		if (this.value != 0) {
			$("#id_not_standby_alerts").enable ();
			$("#id_standby_alerts").enable ();
			$("#id_group").enable ().change ();
		} else {
			$("#id_group, #id_not_standby_alerts").disable ();
			$("#id_group, #id_standby_alerts").disable ();
		}
		$("#hidden-id_alert_template_not_standby").attr("value",$("#id_alert_template").attr("value"));
		$("#hidden-id_alert_template_standby").val($("#id_alert_template").attr("value"));
	});
	
	
	function update_alerts() {
		var $select = $("#id_not_standby_alerts").disable ();
		var $select2 = $("#id_standby_alerts").disable ();
		$("#alerts_loading").show ();
		$("#alerts_loading2").show ();
		$("option", $select).remove ();
		$("option", $select2).remove ();
		
		jQuery.post ("ajax.php",
			{"page" : "godmode/massive/massive_standby_alerts",
			"get_alerts" : 1,
			"id_group" : $("#id_group").attr("value"),
			"id_alert_template" : $("#id_alert_template").attr("value"),
			"standby" : 0
			},
			function (data, status) {
				options = "";
				jQuery.each (data, function (id, value) {
					options += "<option value=\""+id+"\">"+value+"</option>";
				});
				$("#id_not_standby_alerts").append (options);
				$("#alerts_loading").hide ();
				$select.enable ();
			},
			"json"
		);
		
		jQuery.post ("ajax.php",
			{"page" : "godmode/massive/massive_standby_alerts",
			"get_alerts" : 1,
			"id_group" : $("#id_group").attr("value"),
			"id_alert_template" : $("#id_alert_template").attr("value"),
			"standby" : 1
			},
			function (data, status) {
				options = "";
				jQuery.each (data, function (id, value) {
					options += "<option value=\""+id+"\">"+value+"</option>";
				});
				$("#id_standby_alerts").append (options);
				$("#alerts_loading2").hide ();
				$select2.enable ();
			},
			"json"
		);
	}
	
	$("#id_group").change (function () {
		update_alerts();
	});
});
/* ]]> */
</script>
