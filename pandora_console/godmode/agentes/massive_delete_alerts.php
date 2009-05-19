<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributepd in the hope that it will be useful,
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
		"Trying to access massive alert deletion");
	require ("general/noaccess.php");
	return;
}

require_once ('include/functions_agents.php');
require_once ('include/functions_alerts.php');

if (is_ajax ()) {
	$get_agents = (bool) get_parameter ('get_agents');
	
	if ($get_agents) {
		$id_group = (int) get_parameter ('id_group');
		$id_alert_template = (int) get_parameter ('id_alert_template');
		
		$agents_alerts = get_agents_with_alert_template ($id_alert_template, $id_group,
			false, array ('tagente.nombre', 'tagente.id_agente'));
		
		echo json_encode (index_array ($agents_alerts, 'id_agente', 'nombre'));
		return;
	}
	return;
}

echo '<h3>'.__('Massive alerts deletion').'</h3>';
function process_manage_delete ($id_alert_template, $id_agents) {
	if (empty ($id_alert_template)) {
		echo '<h3 class="error">'.__('No alert selected').'</h3>';
		return false;
	}
	
	if (empty ($id_agents)) {
		echo '<h3 class="error">'.__('No agents selected').'</h3>';
		return false;
	}
	
	$modules = get_agent_modules ($id_agents, 'id_agente_modulo', false, true);
	
	process_sql_begin ();
	$success = delete_alert_agent_module (false,
		array ('id_agent_module' => $modules,
			'id_alert_template' => $id_alert_template));
	if (! $success) {
		echo '<h3 class="error">'.__('There was an error deleting the alert, the operation has been cancelled').'</h3>';
		echo '<h4>'.__('Could not delete alert in agent %s', get_agent_name ($id_agent)).'</h4>';
		process_sql_rollback ();
	} else {
		echo '<h3 class="suc">'.__('Successfully deleted').'</h3>';
		process_sql_commit ();
	}
}

$id_group = (int) get_parameter ('id_group');
$id_agents = (array) get_parameter ('id_agents');
$id_alert_template = (int) get_parameter ('id_alert_template');

$delete = (bool) get_parameter_post ('delete');

if ($delete) {
	process_manage_delete ($id_alert_template, $id_agents);
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

$table->data[0][0] = __('Alert template');
$table->data[0][1] = print_select (get_alert_templates (),
	'id_alert_template', $id_alert_template, false, __('Select'), 0, true);
	
$table->data[1][0] = __('Group');
$table->data[1][1] = print_select ($groups, 'id_group', $id_group,
	'', '', '', true, false, true, '', $id_alert_template == 0);

$table->data[2][0] = __('Agent');
$table->data[2][0] .= '<span id="agent_loading" class="invisible">';
$table->data[2][0] .= '<img src="images/spinner.gif" />';
$table->data[2][0] .= '</span>';
$agents_alerts = get_agents_with_alert_template ($id_alert_template, $id_group,
	false, array ('tagente.nombre', 'tagente.id_agente'));
$table->data[2][1] = print_select (index_array ($agents_alerts, 'id_agente', 'nombre'),
	'id_agents', '', '', '', '', true, true, true, '', $id_alert_template == 0);

echo '<form method="post" onsubmit="if (! confirm(\''.__('Are you sure').'\')) return false;">';
print_table ($table);

echo '<div class="action-buttons" style="width: '.$table->width.'" onsubmit="if (!confirm(\' '.__('Are you sure?').'\')) return false;">';
print_input_hidden ('delete', 1);
print_submit_button (__('Delete'), 'go', false, 'class="sub delete"');
echo '</div>';
echo '</form>';

echo '<h3 class="error invisible" id="message"> </h3>';

require_jquery_file ('form');
require_jquery_file ('pandora.controls');
?>

<script type="text/javascript">
/* <![CDATA[ */
$(document).ready (function () {
	$("#id_alert_template").change (function () {
		if (this.value != 0) {
			$("#id_agents").enable ();
			$("#id_group").enable ().change ();
			
		} else {
			$("#id_group, #id_agents").disable ();
		}
	});
	$("#id_group").change (function () {
		var $select = $("#id_agents").disable ();
		$("#agent_loading").show ();
		$("option[value!=0]", $select).remove ();
		
		jQuery.post ("ajax.php",
			{"page" : "godmode/agentes/massive_delete_alerts",
			"get_agents" : 1,
			"id_group" : this.value,
			"id_alert_template" : $("#id_alert_template").attr ("value")
			},
			function (data, status) {
				options = "";
				jQuery.each (data, function (id, value) {
					options += "<option value=\""+id+"\">"+value+"</option>";
				});
				$("#id_agents").append (options);
				$("#agent_loading").hide ();
				$select.enable ();
			},
			"json"
		);
	});
});
/* ]]> */
</script>
