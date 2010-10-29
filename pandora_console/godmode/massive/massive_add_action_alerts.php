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

if (! give_acl ($config['id_user'], 0, "AW")) {
	pandora_audit("ACL Violation",
		"Trying to access massive agent deletion section");
	require ("general/noaccess.php");
	return;
}

require_once ('include/functions_agents.php');
require_once ('include/functions_alerts.php');

if (is_ajax ()) {
	$get_alerts = (bool) get_parameter ('get_alerts');

	if ($get_alerts) {
		$id_agents = get_parameter ('id_agents');
		if (empty($id_agents)) {
			echo json_encode ('');
			return;
		}
		$get_compounds = get_parameter ('get_compounds');
		if (!$get_compounds) {
			$alert_templates = get_agent_alerts_simple ($id_agents);
			echo json_encode (index_array ($alert_templates, 'id_alert_template', 'template_name'));
			return;
		}
		else {
        	$filter = '';
        	foreach ($id_agents as $id_agent) {
                	if ($filter != '') {
                       		$filter .= ' OR ';
                	}
                	$filter .= 'id_agent=' . $id_agent;
        	};
			$alert_compounds = get_alert_compounds ($filter, array('id', 'name'));
			echo json_encode (index_array ($alert_compounds, 'id', 'name'));
			return;
		}
	}
	return;
}

$id_group = (int) get_parameter ('id_group');
$id_agents = get_parameter ('id_agents');
$id_alert_templates = (array) get_parameter ('id_alert_templates');
$id_alert_compounds = (array) get_parameter ('id_alert_compounds');

$add = (bool) get_parameter_post ('add');

if ($add) {
	if(empty($id_agents))
		print_result_message (false, '', __('Could not be added').". ".__('No agents selected'));
	else {
		$action = (int) get_parameter ('action');
		$fires_min = get_parameter ('fires_min');
		$fires_max = get_parameter ('fires_max');
		
		if ($action > 0) {
			$agent_alerts = get_agent_alerts($id_agents);
			$cont = 0;
			$agent_alerts_id = array();
			foreach($agent_alerts['simple'] as $agent_alert){
				if (in_array($agent_alert['id_alert_template'], $id_alert_templates)) {
					$agent_alerts_id[$cont] = $agent_alert['id'];
					$cont = $cont + 1;
				}
			}
			
			$cont = 0;
			$agent_alerts_id_compound = array();
			foreach($agent_alerts['compounds'] as $agent_alert){
				if (in_array($agent_alert['id'], $id_alert_compounds)) {
					$agent_alerts_id_compound[$cont] = $agent_alert['id'];
					$cont = $cont + 1;
				}
			}
					
			$options = array();
			
			if($fires_min > 0)
				$options['fires_min'] = $fires_min;
			if($fires_max > 0)
				$options['fires_max'] = $fires_max;
				
			if (empty($agent_alerts_id) && empty($agent_alerts_id_compound)) {
				print_result_message (false, '', __('Could not be added').". ".__('No alerts selected'));
			}
			else {
				$results = true;
				foreach ($agent_alerts_id as $agent_alert_id) {
					$result = add_alert_agent_module_action($agent_alert_id, $action, $options);
					if($result === false)
						$results = false;
				}

				foreach ($agent_alerts_id_compound as $agent_alert_id_compound) {
					$result = add_alert_compound_action ($agent_alert_id_compound, $action, $options);
					if($result === false)
						$results = false;
				}
			
				pandora_audit("Masive management", "Add alert action " . $id_agent, false, false, 'Agents: ' . 
					json_encode($id_agents) . ' Alerts : ' . json_encode($agent_alerts) .
					' Fires Min: ' . $fires_min . ' Fires Max: ' . $fires_max . ' Action: ' . $action);
				
				print_result_message ($results, __('Successfully added'), __('Could not be added'));
			}
		}
		else {
			print_result_message (false, '', __('Could not be added').". ".__('No action selected'));
		}
	}

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
$table->data[0][0] = __('Group');
$table->data[0][1] = print_select_groups(false, "AR", true, 'id_group', $id_group,
	false, '', '', true);

$table->data[1][0] = __('Agents');
$table->data[1][0] .= '<span id="agent_loading" class="invisible">';
$table->data[1][0] .= '<img src="images/spinner.png" />';
$table->data[1][0] .= '</span>';
$table->data[1][1] = print_select (get_group_agents ($id_group, false, "none"),
	'id_agents[]', 0, false, '', '', true, true);

if (empty($id_agents)) {
	$alert_templates = '';
}
else {
	$alert_templates = get_agent_alerts_simple ($id_agents);
}
$table->data[2][0] = __('Alert templates');
$table->data[2][0] .= '<span id="template_loading" class="invisible">';
$table->data[2][0] .= '<img src="images/spinner.png" />';
$table->data[2][0] .= '</span>';
$table->data[2][1] = print_select (index_array ($alert_templates, 'id_alert_template', 'template_name'), 'id_alert_templates[]', '', '', '', '', true, true, true, '', $alert_templates == 0);

if (empty($id_agents)) {
	$alert_compounds = '';
}
else {
	$filter = '';
	foreach ($id_agents as $id_agent) {
                if ($filter != '') {
                        $filter .= ' OR ';
                }
                $filter .= 'id_agent=' . $id_agent;
        };
	$alert_compounds = get_alert_compounds ($filter, array('id', 'name'));
}
$table->data[3][0] = __('Alert compounds');
$table->data[3][0] .= '<span id="compound_loading" class="invisible">';
$table->data[3][0] .= '<img src="images/spinner.png" />';
$table->data[3][0] .= '</span>';
$table->data[3][1] = print_select (index_array ($alert_compounds, 'id', 'name'), 'id_alert_compounds[]', '', false, '', '', true, true, true, '', $alert_compounds == 0);
	
$actions = get_alert_actions ();
$table->data[4][0] = __('Action');
$table->data[4][1] = print_select ($actions, 'action', '', '', __('None'), 0, true);	
$table->data[4][1] .= '<span><a href="#" class="show_advanced_actions">'.__('Advanced options').' &raquo; </a></span>';
$table->data[4][1] .= '<span id="advanced_actions" class="advanced_actions invisible">';
$table->data[4][1] .= __('Number of alerts match from').' ';
$table->data[4][1] .= print_input_text ('fires_min', 0, '', 4, 10, true);
$table->data[4][1] .= ' '.__('to').' ';
$table->data[4][1] .= print_input_text ('fires_max', 0, '', 4, 10, true);
$table->data[4][1] .= print_help_icon ("alert-matches", true);
$table->data[4][1] .= '</span>';

echo '<form method="post" action="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&option=add_action_alerts" onsubmit="if (! confirm(\''.__('Are you sure?').'\')) return false;">';
print_table ($table);

echo '<div class="action-buttons" style="width: '.$table->width.'" onsubmit="if (!confirm(\' '.__('Are you sure?').'\')) return false;">';
print_input_hidden ('add', 1);
print_submit_button (__('Add'), 'go', false, 'class="sub add"');
echo '</div>';
echo '</form>';

echo '<h3 class="error invisible" id="message"> </h3>';

require_jquery_file ('form');
require_jquery_file ('pandora.controls');
?>

<script type="text/javascript">
$(document).ready (function () {
	update_alerts();

	$("#id_group").pandoraSelectGroupAgent ({
		agentSelect: "select#id_agents",
		callbackPost: function () {
			var $select_template = $("#id_alert_templates").disable ();
			var $select_compound = $("#id_alert_compounds").disable ();
			$("option", $select_template).remove ();
			$("option", $select_compound).remove ();
		}
	});

	$("#id_agents").change (function () {
		update_alerts();
	});

	function update_alerts() {
		var idAgents = Array();
		jQuery.each ($("#id_agents option:selected"), function (i, val) {
			idAgents.push($(val).val());
                });
		$("#template_loading").show();
		$("#compound_loading").show();

		var $select_template = $("#id_alert_templates").disable ();
		var $select_compound = $("#id_alert_compounds").disable ();
		$("option", $select_template).remove ();
		$("option", $select_compound).remove ();

		jQuery.post ("ajax.php",
				{"page" : "godmode/massive/massive_add_action_alerts",
				"get_alerts" : 1,
				"get_compounds" : 0,
                                "id_agents[]" : idAgents
                                },
				function (data, status) {
					options = "";
                                	jQuery.each (data, function (id, value) {
                                        	options += "<option value=\""+id+"\">"+value+"</option>";
                                	});
                                	$("#id_alert_templates").append (options);
                                	$("#template_loading").hide ();
                                	$select_template.enable ();
				},
				"json"
			);

		jQuery.post ("ajax.php",
				{"page" : "godmode/massive/massive_add_action_alerts",
				"get_alerts" : 1,
				"get_compounds" : 1,
				"id_agents[]" : idAgents
				},
				function (data, status) {
					options = "";
					jQuery.each (data, function (id, value) {
						options += "<option value=\""+id+"\">"+value+"</option>";
					});
					$("#id_alert_compounds").append (options);
					$("#compound_loading").hide ();
					$select_compound.enable ();
				},
				"json"
			);
	}

	$("a.show_advanced_actions").click (function () {
		/* It can be done in two different sites, so it must use two different selectors */
		actions = $(this).parents ("form").children ("span.advanced_actions");
		if (actions.length == 0)
			actions = $(this).parents ("div").children ("span.advanced_actions")
		$("#advanced_actions").removeClass("advanced_actions invisible");
		$(this).remove ();
		return false;
	});
});
/* ]]> */
</script>
