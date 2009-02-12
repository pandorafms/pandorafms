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

check_login ();

if (! give_acl ($config['id_user'], 0, "LM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Alert Management");
	require ("general/noaccess.php");
	exit;
}

require_once ('include/functions_agents.php');
require_once ('include/functions_alerts.php');


$disable_alert = (bool) get_parameter ('disable_alert');
$enable_alert = (bool) get_parameter ('enable_alert');

if ($enable_alert) {
	$id_alert = (int) get_parameter ('id_alert');
	
	$result = set_alerts_agent_module_disable ($id_alert, false);
	print_error_message ($id, __('Successfully enabled'),
		__('Could not be enabled'));
	if (defined ('AJAX'))
		return;
}

if ($disable_alert) {
	$id_alert = (int) get_parameter ('id_alert');
	
	$result = set_alerts_agent_module_disable ($id_alert, true);
	print_error_message ($id, __('Successfully disabled'),
		__('Could not be disabled'));
	if (defined ('AJAX'))
		return;
}

echo '<h1>'.__('Alerts').'</h1>';

$groups = get_user_groups ();
$agents = get_group_agents (array_keys ($groups), false, "none");

$simple_alerts = array ();
$compound_alerts = array ();

foreach ($agents as $agent_id => $agent_name) {
	$agent_alerts = get_agent_alerts_simple ($agent_id);
	if (! empty ($agent_alerts))
		$simple_alerts[$agent_id] = $agent_alerts;
	
	$compound_alerts = get_agent_alerts_compound ($agent_id);
	if (! empty ($agent_alerts))
		$compound_alerts[$agent_id] = $compound_alerts;
}

foreach ($simple_alerts as $agent_id => $alerts) {
	if (empty ($alerts))
		continue;
	
	echo '<h3>'.get_agent_name ($agent_id).' - '.__('Alerts defined').'</h3>';
	
	$table->class = 'alert_list';
	$table->width = '90%';
	$table->data = array ();
	$table->head = array ();
	$table->head[0] = '';
	$table->head[1] = __('Module');
	$table->head[2] = __('Template');
	$table->head[3] = __('Actions');
	$table->size = array ();
	$table->size[0] = '20px';
	
	foreach ($alerts as $alert) {
		$data = array ();
		
		$data[0] = '<form class="disable_alert_form" method="post" style="display: inline;">';
		if ($alert['disabled']) {
			$data[0] .= print_input_image ('enable', 'images/lightbulb_off.png', 1, '', true);
			$data[0] .= print_input_hidden ('enable_alert', 1, true);
		} else {
			$data[0] .= print_input_image ('disable', 'images/lightbulb.png', 1, '', true);
			$data[0] .= print_input_hidden ('disable_alert', 1, true);
		}
		$data[0] .= print_input_hidden ('id_alert', $alert['id'], true);
		$data[0] .= '</form>';
		$data[1] = get_agentmodule_name ($alert['id_agent_module']);
		$data[2] = get_alert_template_name ($alert['id_alert_template']);
		$data[2] .= ' <a class="template_details"
			href="ajax.php?page=godmode/alerts/alert_templates&get_template_tooltip=1&id_template='.$alert['id_alert_template'].'">
			<img id="template-details-'.$alert['id_alert_template'].'" class="img_help" src="images/zoom.png"/></a>';
		
		$actions = get_alert_actions ($alert['id']);
		$data[3] = '<ul class="action_list">';
		foreach ($actions as $action) {
			$data[3] .= '<li>'.$action.'</li>';
		}
		$data[3] .= '</ul>';
		
		array_push ($table->data, $data);
	}
	
	print_table ($table);
}

$config['css'][] = "cluetip"; //link tags can't go in body
$config['jquery'][] = "cluetip"; //make sure it doesn't get overwritten
$config['jquery'][] = "form";

?>
<script type="text/javascript">
/* <![CDATA[ */
$(document).ready (function () {
	$("a.template_details").cluetip ({
		arrows: true,
		attribute: 'href',
		cluetipClass: 'default'
	}).click (function () {
		return false;
	});;
	$("input[name=disable]").attr ("title", "<?php echo __('Disable')?>")
		.hover (function () {
				$(this).attr ("src", "images/lightbulb_off.png");
			},
			function () {
				$(this).attr ("src", "images/lightbulb.png");
			}
		);
	$("input[name=enable]").attr ("title", "<?php echo __('Enable')?>")
		.hover (function () {
				$(this).attr ("src", "images/lightbulb.png");
			},
			function () {
				$(this).attr ("src", "images/lightbulb_off.png");
			}
		);
	$("form.disable_alert_form").submit (function () {
		jQuery.post ("ajax.php",
			{"page" : "godmode/alerts/alert_list",
			"enable-alert" : $("#hidden-enable_alert", this).attr ("value"),
			"disable-alert" : $("#hidden-disable_alert", this).attr ("value"),
			"id-alert" : $("#hidden-id_alert", this).attr ("value")},
			function (data, status) {
				$("#hidden-enable_alert", this).attr ("value", 0);
				$("#hidden-disable_alert", this).attr ("value", 1);
			},
			"html"
		);
		return false;
	});
});
/* ]]> */
</script>
