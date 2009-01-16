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

require_once ("include/functions_agents.php");

$filter = get_parameter_get ("filter", "all");
$offset = (int) get_parameter_get ("offset", 0);
$id_group = (int) get_parameter ("ag_group", 1); //1 is the All group (selects all groups)

$sec2 = get_parameter_get ('sec2');
$sec2 = safe_url_extraclean ($sec2);
	
$sec = get_parameter_get ('sec');
$sec = safe_url_extraclean ($sec);

$url = 'index.php?sec='.$sec.'&sec2='.$sec2.'&refr='.$config["refr"].'&filter='.$filter.'&ag_group='.$id_group;

// Force alert execution
$flag_alert = (bool) get_parameter ('force_execution');
if ($flag_alert  == 1 && give_acl ($config['id_user'], $id_grupo, "AW")) {
	require_once ("include/functions_alerts.php");
	$id_alert = (int) get_parameter ('id_alert');
	set_alerts_agent_module_force_execution ($id_alert);
}

// Show alerts for specific agent
if (isset ($_GET["id_agente"])) {
	$id_agent = (int) get_parameter_get ("id_agente", 0);
	$url = $url.'&id_agente='.$id_agent;
	
	$id_group = get_group_agents ($id_agent);
	
	if (give_acl ($config["id_user"], $id_group, "AR") == 0) {
		audit_db ($config["id_user"], $config["remote_addr"], "ACL Violation","Trying to access alert view");
		require ("general/noaccess.php");
		exit;
	}
	
	$alerts_simple = get_agent_alerts_simple ($id_agent, $filter);
	$alerts_combined = get_agent_alerts_combined ($id_agent, $filter);
	$print_agent = false;
} else {
	if (give_acl ($config["id_user"], $id_group, "AR") == 0) {
		audit_db ($config["id_user"], $config["remote_addr"], "ACL Violation","Trying to access alert view");
		require ("general/noaccess.php");
		exit;
	}
	
	$alerts_simple = array ();
	$alerts_combined = array ();
	
	$agents = array_keys (get_group_agents ($id_group));
	
	foreach ($agents as $id_agent) {
		$simple = get_agent_alerts_simple ($id_agent, $filter);
		$combined = get_agent_alerts_combined ($id_agent, $filter);
		
		$alerts_simple = array_merge ($alerts_simple, $simple);
		$alerts_combined = array_merge ($alerts_combined, $combined);
	}
	
	$print_agent = true;
}

$tab = get_parameter_get ("tab");
if ($tab != '') {
	$url = $url.'&tab='.$tab;
}

echo "<h2>".__('Pandora Agents')." &gt; ".__('Alerts').'</h2>';

if (get_parameter ('alert_validate')) {
	$ids = (array) get_parameter_post ("validate", array ());
	if (! empty ($ids)) {
		require_once ("include/functions_alerts.php");
		$result = validate_alert_agent_module ($ids);
		
		print_error_message ($result, __('Alert(s) validated'),
			__('Error processing alert(s)'));
	}
}

echo '<form method="post" action="'.$url.'">';

if ($print_agent) {
	$table->width = '90%';
	$table->data = array ();
	$table->style = array ();
	
	$table->data[0][0] = __('Group');
	$table->data[0][1] = print_select (get_user_groups (), "ag_group", $id_group,
		'javascript:this.form.submit();', '', '', true);
	
	$table->data[0][2] = '<a href="'.$url.'&filter=fired"><img src="images/pixel_red.png" width="18" height="18" title="'.__('Click to filter').'">'.__('Alert fired').'</a>';
	$table->data[0][3] = '<a href="'.$url.'&filter=notfired"><img src="images/pixel_green.png" width="18" height="18" title="'.__('Click to filter').'">'.__('Alert not fired').'</a>';
	$table->data[0][4] = '<a href="'.$url.'&filter=disabled"><img src="images/pixel_gray.png" width="18" height="18" title="'.__('Click to filter').'">'.__('Alert disabled').'</a>';
	
	switch ($filter) {
	case 'fired':
		$table->style[2] = 'font-weight: bold';
		
		break;
	case 'notfired':
		$table->style[3] = 'font-weight: bold';
		
		break;
	case 'disabled':
		$table->style[4] = 'font-weight: bold';
		
		break;
	}
	
	print_table ($table);
}

$table->width = '90%';
$table->class = "databox";
$table->head = array ();
$table->head[0] = '';
$table->head[1] = ''; //Placeholder for name
$table->head[2] = __('Template');
$table->head[3] = __('Last fired');
$table->head[4] = __('Status');
$table->head[5] = __('Validate').pandora_help ('alert_validation', true);
$table->title = __('Single alerts');

if ($print_agent == 0) {
	$table->head[1] = __('Module');
} else {
	$table->head[1] = __('Agent');
}
$table->align = array ();
$table->align[4] = 'center';
$table->align[5] = 'center';
$table->data = array ();

$total = 0;
$printed = 0;
foreach ($alerts_simple as $alert) {
	$total++;
	if (empty ($alert) || $printed >= $config["block_size"] || $total <= $offset) {
		continue;
	}
	$printed++;
	array_push ($table->data, format_alert_row ($alert, 0, $print_agent, $url));
}

if (!empty ($table->data)) {
	pagination ($total, $url, $offset);
	print_table ($table);
} else {
	echo '<div class="nf">'.__('No simple alerts found').'</div>';
}

$table->title = __('Combined alerts');
$table->head[1] = __('Agent');
$table->data = array ();

$combined_total = 0;
$combined_printed = 0;
foreach ($alerts_combined as $alert) {
	$combined_total++;
	if (empty ($alert) || $combined_printed >= $config["block_size"] || $combined_total <= $offset) {
		continue;
	}
	$combined_printed++;
	array_push ($table->data, format_alert_row ($alert, 1, $print_agent));
}	

if (!empty ($table->data)) {
	pagination ($total, $url, $offset);
	print_table ($table);
}

if ($printed > 0 || $combined_total > 0) {
	echo '<div class="action-buttons" style="width: '.$table->width.';">';
	print_submit_button (__('Validate'), 'alert_validate', false, 'class="sub upd"', false);
	echo '</div>';
}

echo '</form>';
?>
<link rel="stylesheet" href="include/styles/cluetip.css" type="text/css" />
<script type="text/javascript" src="include/javascript/jquery.cluetip.js"></script>

<script type="text/javascript">
$(document).ready (function () {
	$("a.template_details").cluetip ({
		arrows: true,
		attribute: 'href',
		cluetipClass: 'default',
		fx: { open: 'fadeIn', openSpeed: 'slow' },
	}).click (function () {
		return false;
	});
});
</script>
