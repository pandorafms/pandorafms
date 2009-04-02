<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// Load global vars
check_login ();

if (! give_acl ($config['id_user'], 0, "AW")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Agent Config Management Admin section");
	require ("general/noaccess.php");
	return;
}

require_once ('include/functions_agents.php');
require_once ('include/functions_alerts.php');
require_once ('include/functions_modules.php');

echo '<h3>'.__('Configuration Management').'</h3>';

$source_id_group = (int) get_parameter ('source_id_group');
$source_id_agent = (int) get_parameter ('source_id_agent');
$destiny_id_group = (int) get_parameter ('destiny_id_group');
$destiny_id_agents = (array) get_parameter ('destiny_id_agent', array ());

$do_operation = (bool) get_parameter ('do_operation');

if ($do_operation) {
	process_manage_config ($source_id_agent, $destiny_id_agents);
}

$groups = get_user_groups ();

$table->class = 'databox';
$table->width = '95%';
$table->data = array ();
$table->style = array ();
$table->style[0] = 'font-weight: bold; vertical-align:top';
$table->style[2] = 'font-weight: bold';
$table->size = array ();
$table->size[0] = '15%';
$table->size[1] = '35%';
$table->size[2] = '15%';
$table->size[3] = '35%';

/* Source selection */
$table->id = 'source_table';
$table->data[0][0] = __('Group');
$table->data[0][1] = print_select ($groups, 'source_id_group', $source_id_group,
	false, '', '', true);
$table->data[0][2] = __('Agent');
$table->data[0][2] .= ' <span id="source_agent_loading" class="invisible">';
$table->data[0][2] .= print_image ("images/spinner.gif", true);
$table->data[0][2] .= '</span>';
$table->data[0][3] = print_select (get_group_agents ($source_id_group, false, "none"),
	'source_id_agent', $source_id_agent, false, __('Select'), 0, true);

echo '<form id="manage_config_form" method="post">';

echo '<fieldset id="fieldset_source">';
echo '<legend><span>'.__('Source');
print_help_icon ('manageconfig');
echo '</span></legend>';
print_table ($table);
echo '</fieldset>';

/* Target selection */
$table->id = 'target_table';
$table->data = array ();

$modules = array ();
if ($source_id_agent)
	$modules = get_agent_modules ($source_id_agent, 'nombre');

$table->data['operations'][0] = __('Operations');
$table->data['operations'][1] = '<span class="with_modules'.(empty ($modules) ? ' invisible': '').'">';
$table->data['operations'][1] .= print_checkbox ('copy_modules', 1, true, true);
$table->data['operations'][1] .= print_label (__('Copy modules'), 'checkbox-copy_modules', true);
$table->data['operations'][1] .= '</span><br />';

$table->data['operations'][1] .= '<span class="with_alerts'.(empty ($alerts) ? ' invisible': '').'">';
$table->data['operations'][1] .= print_checkbox ('copy_alerts', 1, true, true);
$table->data['operations'][1] .= print_label (__('Copy alerts'), 'checkbox-copy_alerts', true);
$table->data['operations'][1] .= '</span>';

$table->data[1][0] = __('Modules');
$table->data[1][1] = '<span class="with_modules'.(empty ($modules) ? ' invisible': '').'">';
$table->data[1][1] .= print_select ($modules,
	'target_modules[]', 0, false, '', '', true, true);
$table->data[1][1] .= '</span>';
$table->data[1][1] .= '<span class="without_modules'.(! empty ($modules) ? ' invisible': '').'">';
$table->data[1][1] .= '<em>'.__('No modules for this agent').'</em>';
$table->data[1][1] .= '</span>';

$table->data[2][0] = __('Alerts');

$agent_alerts = array ();
if ($source_id_agent)
	$agent_alerts = get_agent_alerts_simple ($source_id_agent);
$alerts = array ();
foreach ($agent_alerts as $alert) {
	$name = get_alert_template_name ($alert['id_alert_template']);
	$name .= ' (<em>'.$modules[$alert['id_agent_module']].'</em>)';
	$alerts[$alert['id']] = $name;
}
$table->data[2][1] = '<span class="with_alerts'.(empty ($alerts) ? ' invisible': '').'">';
$table->data[2][1] .= print_select ($alerts,
	'target_alerts[]', 0, false, '', '', true, true);
$table->data[2][1] .= '</span>';
$table->data[2][1] .= '<span class="without_alerts'.(! empty ($modules) ? ' invisible': '').'">';
$table->data[2][1] .= '<em>'.__('No alerts for this agent').'</em>';
$table->data[2][1] .= '</span>';

echo '<div id="modules_loading" class="loading invisible">';
print_image ("images/spinner.gif");
echo __('Loading').'&hellip;';
echo '</div>';

echo '<fieldset id="fieldset_targets"'.($source_id_agent ? '' : ' class="invisible"').'>';
echo '<legend><span>'.__('Targets').'</span></legend>';
print_table ($table);
echo '</fieldset>';

/* Destiny selection */
$table->id = 'destiny_table';
$table->data = array ();
$table->data[0][0] = __('Group');
$table->data[0][1] = print_select ($groups, 'destiny_id_group', $destiny_id_group,
	false, '', '', true);

$table->data[1][0] = __('Agent');
$table->data[1][0] .= '<span id="destiny_agent_loading" class="invisible">';
$table->data[1][0] .= print_image ("images/spinner.gif", true);
$table->data[1][0] .= '</span>';
$table->data[1][1] = print_select (get_group_agents ($destiny_id_group, false, "none"),
	'destiny_id_agent[]', 0, false, '', '', true, true);

echo '<fieldset id="fieldset_destiny"'.($source_id_agent ? '' : ' class="invisible"').'>';
echo '<legend><span>'.__('To agent(s)').'</span></legend>';
print_table ($table);
echo '</fieldset>';

echo '<div class="action-buttons" style="width: '.$table->width.'">';
print_input_hidden ('do_operation', 1);
print_submit_button (__('Go'), 'go', false, 'class="sub next"');
echo '</div>';
echo '</form>';

echo '<h3 class="error invisible" id="message">&nbsp;</h3>';

require_jquery_file ('form');
require_jquery_file ('pandora.controls');
?>
<script type="text/javascript">
/* <![CDATA[ */
var module_alerts;
$(document).ready (function () {
	$("#source_id_group").pandoraSelectGroup ({
		agentSelect: "select#source_id_agent",
		loading: "#source_agent_loading"
	});
	
	$("#destiny_id_group").pandoraSelectGroup ({
		agentSelect: "select#destiny_id_agent",
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
			return;
		}
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
				} else {
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
					{"page" : "godmode/alerts/alert_list",
					"get_agent_alerts_simple" : 1,
					"id_agent" : id_agent
					},
					function (data, status) {
						module_alerts = Array ();
						if (! data) {
							no_alerts = true;
						} else {
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
						} else {
							if (no_modules) {
								$("span.without_modules").show ();
								$("span.with_modules").hide ();
								$("#checkbox-copy_modules").uncheck ();
							} else {
								$("span.without_modules").hide ();
								$("span.with_modules").show ();
								$("#checkbox-copy_modules").check ();
							}
							
							if (no_alerts) {
								$("span.without_alerts").show ();
								$("span.with_alerts").hide ();
								$("#checkbox-copy_alerts").uncheck ();
							} else {
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
