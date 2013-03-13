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
		"Trying to access Agent Config Management Admin section");
	require ("general/noaccess.php");
	return;
}

require_once ('include/functions_agents.php');
require_once ('include/functions_alerts.php');
require_once ('include/functions_modules.php');
require_once ('include/functions_users.php');

$source_id_group = (int) get_parameter ('source_id_group');
$source_id_agent = (int) get_parameter ('source_id_agent');
$destiny_id_group = (int) get_parameter ('destiny_id_group');
$destiny_id_agents = (array) get_parameter ('destiny_id_agent', array ());
$source_recursion = get_parameter ('source_recursion');
$destiny_recursion = get_parameter ('destiny_recursion');

$do_operation = (bool) get_parameter ('do_operation');

if ($do_operation) {
	$result = agents_process_manage_config ($source_id_agent, $destiny_id_agents);
	
	if ($result) {
		db_pandora_audit("Massive management", "Copy modules", false, false,
			'Source agent: ' . json_encode($source_id_agent) . ' Destinity agent: ' . json_encode($destiny_id_agents));
	}
	else {
		db_pandora_audit("Massive management", "Fail to try copy modules", false, false,
			'Source agent: ' . json_encode($source_id_agent) . ' Destinity agent: ' . json_encode($destiny_id_agents));
	}
}

$groups = users_get_groups ();

$table->class = 'databox';
$table->width = '98%';
$table->data = array ();
$table->style = array ();
$table->style[0] = 'font-weight: bold; vertical-align:top';
$table->style[2] = 'font-weight: bold';
$table->size = array ();
$table->size[0] = '10%';
$table->size[1] = '30%';
$table->size[2] = '10%';
$table->size[3] = '10%';
$table->size[4] = '10%';
$table->size[5] = '30%';

/* Source selection */
$table->id = 'source_table';
$table->data[0][0] = __('Group');
$table->data[0][1] = html_print_select_groups(false, "AR", true, 'source_id_group', $source_id_group,
	false, '', '', true);
$table->data[0][2] = __('Group recursion');
$table->data[0][3] = html_print_checkbox ("source_recursion", 1, $source_recursion, true, false);
$table->data[0][4] = __('Agent');
$table->data[0][4] .= ' <span id="source_agent_loading" class="invisible">';
$table->data[0][4] .= html_print_image ("images/spinner.png", true);
$table->data[0][4] .= '</span>';
$table->data[0][5] = html_print_select (agents_get_group_agents ($source_id_group, false, "none"),
	'source_id_agent', $source_id_agent, false, __('Select'), 0, true);

//$table->data[0][5] = html_print_input_text_extended ('id_agent', __('Select'), 'text-id_agent', '', 25, 100, false, '',
//	array('style' => 'background: url(images/lightning.png) no-repeat right;'), true)
//	. '<a href="#" class="tip">&nbsp;<span>' . __("Type two chars at least for search") . '</span></a>';

echo '<form action="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&option=copy_modules" id="manage_config_form" method="post">';

echo '<fieldset id="fieldset_source">';
echo '<legend><span>'.__('Source');
ui_print_help_icon ('manageconfig');
echo '</span></legend>';
html_print_table ($table);
echo '</fieldset>';

/* Target selection */
$table->id = 'target_table';
$table->data = array ();

$modules = array ();
if ($source_id_agent)
	$modules = agents_get_modules ($source_id_agent, 'nombre');

$table->data['operations'][0] = __('Operations');
$table->data['operations'][1] = '<span class="with_modules'.(empty ($modules) ? ' invisible': '').'">';
$table->data['operations'][1] .= html_print_checkbox ('copy_modules', 1, true, true);
$table->data['operations'][1] .= html_print_label (__('Copy modules'), 'checkbox-copy_modules', true);
$table->data['operations'][1] .= '</span><br />';

$table->data['operations'][1] .= '<span class="with_alerts'.(empty ($alerts) ? ' invisible': '').'">';
$table->data['operations'][1] .= html_print_checkbox ('copy_alerts', 1, true, true);
$table->data['operations'][1] .= html_print_label (__('Copy alerts'), 'checkbox-copy_alerts', true);
$table->data['operations'][1] .= '</span>';

$table->data[1][0] = __('Modules');
$table->data[1][1] = '<span class="with_modules'.(empty ($modules) ? ' invisible': '').'">';
$table->data[1][1] .= html_print_select ($modules,
	'target_modules[]', 0, false, '', '', true, true);
$table->data[1][1] .= '</span>';
$table->data[1][1] .= '<span class="without_modules'.(! empty ($modules) ? ' invisible': '').'">';
$table->data[1][1] .= '<em>'.__('No modules for this agent').'</em>';
$table->data[1][1] .= '</span>';

$table->data[2][0] = __('Alerts');

$agent_alerts = array ();
if ($source_id_agent)
	$agent_alerts = agents_get_alerts_simple ($source_id_agent);
$alerts = array ();
foreach ($agent_alerts as $alert) {
	$name = alerts_get_alert_template_name ($alert['id_alert_template']);
	$name .= ' (<em>'.$modules[$alert['id_agent_module']].'</em>)';
	$alerts[$alert['id']] = $name;
}
$table->data[2][1] = '<span class="with_alerts'.(empty ($alerts) ? ' invisible': '').'">';
$table->data[2][1] .= html_print_select ($alerts,
	'target_alerts[]', 0, false, '', '', true, true);
$table->data[2][1] .= '</span>';
$table->data[2][1] .= '<span class="without_alerts'.(! empty ($modules) ? ' invisible': '').'">';
$table->data[2][1] .= '<em>'.__('No alerts for this agent').'</em>';
$table->data[2][1] .= '</span>';

echo '<div id="modules_loading" class="loading invisible">';
html_print_image ("images/spinner.png");
echo __('Loading').'&hellip;';
echo '</div>';

echo '<fieldset id="fieldset_targets"'.($source_id_agent ? '' : ' class="invisible"').'>';
echo '<legend><span>'.__('Targets').'</span></legend>';
html_print_table ($table);
echo '</fieldset>';

/* Destiny selection */
$table->id = 'destiny_table';
$table->data = array ();
$table->size[0] = '20%';
$table->size[1] = '30%';
$table->size[2] = '20%';
$table->size[3] = '30%';
$table->data[0][0] = __('Group');
$table->data[0][1] = html_print_select ($groups, 'destiny_id_group', $destiny_id_group,
	false, '', '', true);
$table->data[0][2] = __('Group recursion');
$table->data[0][3] = html_print_checkbox ("destiny_recursion", 1, $destiny_recursion, true, false);
$table->data[1][0] = __('Agent');
$table->data[1][0] .= '<span id="destiny_agent_loading" class="invisible">';
$table->data[1][0] .= html_print_image ("images/spinner.png", true);
$table->data[1][0] .= '</span>';
$table->data[1][1] = html_print_select (agents_get_group_agents ($destiny_id_group, false, "none"),
	'destiny_id_agent[]', 0, false, '', '', true, true);

echo '<fieldset id="fieldset_destiny"'.($source_id_agent ? '' : ' class="invisible"').'>';
echo '<legend><span>'.__('To agent(s)').'</span></legend>';
html_print_table ($table);
echo '</fieldset>';

echo '<div class="action-buttons" style="width: '.$table->width.'">';
html_print_input_hidden ('do_operation', 1);
html_print_submit_button (__('Copy'), 'go', true, 'class="sub wand"');
echo '</div>';
echo '</form>';

echo '<h3 class="error invisible" id="message">&nbsp;</h3>';

ui_require_jquery_file ('form');
ui_require_jquery_file ('pandora.controls');
?>
<script type="text/javascript">
/* <![CDATA[ */
var module_alerts;
$(document).ready (function () {
	var source_recursion;
	$("#checkbox-source_recursion").click(function () {
		source_recursion = this.checked ? 1 : 0;
		$("#source_id_group").trigger("change");
	});
	
	$("#source_id_group").pandoraSelectGroupAgent ({
		agentSelect: "select#source_id_agent",
		recursion: function() {return source_recursion},
		loading: "#source_agent_loading"
	});
	
	var destiny_recursion;
	$("#checkbox-destiny_recursion").click(function () {
		destiny_recursion = this.checked ? 1 : 0;
		$("#destiny_id_group").trigger("change");
	});
	
	$("#destiny_id_group").pandoraSelectGroupAgent ({
		agentSelect: "select#destiny_id_agent",
		recursion: function() {return destiny_recursion},
		loading: "#destiny_agent_loading",
		callbackPost: function (id, value, option) {
			if ($("#source_id_agent").fieldValue ().in_array (id)) {
				/* Hide source agent */
				$(option).hide ();
			}
		}
	});
	
	$("#source_id_agent").change (function () {
		var id_agent = this.value;
		
		if (id_agent == 0) {
			$("#submit-go").attr("disabled", "disabled");
			
			$("span.without_modules, span.without_alerts").hide();
			$("span.without_modules").hide();
			$("span.with_modules").hide();
			$("span.without_alerts").hide();
			$("span.with_alerts").hide();
			$("#fieldset_destiny, #target_table-operations").hide();
			$("#fieldset_targets").hide();
			
			return;
		}
		
		$("#submit-go").attr("disabled", false);
		
		$("#modules_loading").show ();
		$("#target_modules option, #target_alerts option").remove ();
		$("#target_modules, #target_alerts").disable ();
		$("#destiny_id_agent option").show ();
		$("#destiny_id_agent option[value="+id_agent+"]").hide ();
		var no_modules;
		var no_alerts;
		/* Get modules */
		jQuery.post ("ajax.php",
			{"page" : "operation/agentes/ver_agente",
			"get_agent_modules_json" : 1,
			"id_agent" : this.value,
			"filter" : "disabled = 0",
			"fields" : "id_agente_modulo,nombre"
			},
			function (data, status) {
				if (data.length == 0) {
					no_modules = true;
				}
				else {
					jQuery.each (data, function (i, val) {
						option = $("<option></option>")
							.attr ("value", val["id_agente_modulo"])
							.append (val["nombre"]);
						$("#target_modules").append (option);
					});
					
					no_modules = false;
				}
				
				/* Get alerts */
				jQuery.post ("ajax.php",
					{"page" : "include/ajax/alert_list.ajax",
					"get_agent_alerts_simple" : 1,
					"id_agent" : id_agent
					},
					function (data, status) {
						module_alerts = Array ();
						if (! data) {
							no_alerts = true;
						}
						else {
							jQuery.each (data, function (i, val) {
								module_name = $("<em></em>").append (val["module_name"]);
								option = $("<option></option>")
									.attr ("value", val["id"])
									.append (val["template"]["name"])
									.append (" (")
									.append (module_name)
									.append (")");
								$("#target_alerts").append (option);
								module_alerts[val["id"]] = val["id_agent_module"];
							});
							no_alerts = false;
						}
						$("#modules_loading").hide ();
						
						if (no_modules && no_alerts) {
							/* Nothing to export from selected agent */
							$("#fieldset_destiny").hide ();
							
							$("span.without_modules, span.without_alerts").show ();
							$("span.with_modules, span.with_alerts, #target_table-operations").hide ();
						}
						else {
							if (no_modules) {
								$("span.without_modules").show ();
								$("span.with_modules").hide ();
								$("#checkbox-copy_modules").uncheck ();
							}
							else {
								$("span.without_modules").hide ();
								$("span.with_modules").show ();
								$("#checkbox-copy_modules").check ();
							}
							
							if (no_alerts) {
								$("span.without_alerts").show ();
								$("span.with_alerts").hide ();
								$("#checkbox-copy_alerts").uncheck ();
							}
							else {
								$("span.without_alerts").hide ();
								$("span.with_alerts").show ();
								$("#checkbox-copy_alerts").check ();
							}
							$("#fieldset_destiny, #target_table-operations").show ();
						}
						$("#fieldset_targets").show ();
						$("#target_modules, #target_alerts").enable ();
					},
					"json"
				);
			},
			"json"
		);
	});
	
	$("#target_alerts").change (function () {
		jQuery.each ($(this).fieldValue (), function () {
			if (module_alerts[this] != undefined)
				$("#target_modules option[value="+module_alerts[this]+"]")
					.attr ("selected", "selected");
		});
	});
	
	$("#manage_config_form").submit (function () {
		$("h3:not([id=message])").remove ();
		if ($("#source_id_agent").attr ("value") == 0) {
			$("#message").showMessage ("<?php echo __('No source agent to copy') ?>");
			return false;
		}
		
		copy_modules = $("#checkbox-copy_modules");
		copy_alerts = $("#checkbox-copy_alerts");
		
		if (! $(copy_modules).attr ("checked") && ! $(copy_alerts).attr ("checked")) {
			$("#message").showMessage ("<?php echo __('No operation selected') ?>");
			return false;
		}
		
		if ($(copy_modules).attr ("checked") && $("#target_modules").fieldValue ().length == 0) {
			$("#message").showMessage ("<?php echo __('No modules have been selected') ?>");
			return false;
		}
		
		if ($("#destiny_id_agent").fieldValue ().length == 0) {
			$("#message").showMessage ("<?php echo __('No destiny agent(s) to copy') ?>");
			return false;
		}
		
		$("#message").hide ();
		return true;
	});
});
/* ]]> */
</script>
