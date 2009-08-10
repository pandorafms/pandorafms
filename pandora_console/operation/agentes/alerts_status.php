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


check_login ();

require_once ("include/functions_agents.php");

$filter = get_parameter ("filter", "all");
$offset = (int) get_parameter_get ("offset", 0);
$id_group = (int) get_parameter ("ag_group", 1); //1 is the All group (selects all groups)

$sec2 = get_parameter_get ('sec2');
$sec2 = safe_url_extraclean ($sec2);
	
$sec = get_parameter_get ('sec');
$sec = safe_url_extraclean ($sec);

$url = 'index.php?sec='.$sec.'&sec2='.$sec2.'&refr='.$config["refr"].'&filter='.$filter.'&ag_group='.$id_group;

// Force alert execution
$flag_alert = (bool) get_parameter ('force_execution');
$alert_validate = (bool) get_parameter ('alert_validate');

if ($flag_alert  == 1 && give_acl ($config['id_user'], $id_group, "AW")) {
	require_once ("include/functions_alerts.php");
	$id_alert = (int) get_parameter ('id_alert');
	set_alerts_agent_module_force_execution ($id_alert);
}

if ($alert_validate) {
	$ids = (array) get_parameter_post ("validate", array ());
	$compound_ids = (array) get_parameter_post ("validate_compound", array ());
	
	if (! empty ($ids) || ! empty ($compound_ids)) {
		require_once ("include/functions_alerts.php");
		$result1 = validate_alert_agent_module ($ids);
		$result2 = validate_alert_compound ($compound_ids);
		$result == $result1 || $result2;
		
		print_result_message ($result,
			__('Alert(s) validated'),
			__('Error processing alert(s)'));
	}
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
	$alerts_combined = get_agent_alerts_compound ($id_agent, $filter);
	$print_agent = false;
} else {
	if (!give_acl ($config["id_user"], 0, "AR")) {
		audit_db ($config["id_user"], $config["remote_addr"], "ACL Violation","Trying to access alert view");
		require ("general/noaccess.php");
		return;
	}
	
	$alerts_simple = array ();
	$alerts_combined = array ();
	
	$agents = array_keys (get_group_agents ($id_group));
	
	foreach ($agents as $id_agent) {
		$simple = get_agent_alerts_simple ($id_agent, $filter);
		$combined = get_agent_alerts_compound ($id_agent, $filter);
		
		$alerts_simple = array_merge ($alerts_simple, $simple);
		$alerts_combined = array_merge ($alerts_combined, $combined);
	}
	
	$print_agent = true;
}

$tab = get_parameter_get ("tab");
if ($tab != '') {
	$url = $url.'&tab='.$tab;
}

echo "<h2>".__('Pandora agents')." &raquo; ".__('Alerts').'</h2>';

echo '<form method="post" action="'.$url.'">';

if ($print_agent) {
	$table->width = '90%';
	$table->data = array ();
	$table->style = array ();
	
	$table->data[0][0] = __('Group');
	$table->data[0][1] = print_select (get_user_groups (), "ag_group", $id_group,
		'javascript:this.form.submit();', '', '', true);
		
	$alert_status_filter = array();
	$alert_status_filter['all'] = __('All');
	$alert_status_filter['fired'] = __('Fired');
	$alert_status_filter['notfired'] = __('Not fired');
	$alert_status_filter['disabled'] = __('Disabled');		
		
	$table->data[0][2] = __('Status');
	$table->data[0][3] = print_select ($alert_status_filter, "filter", $filter, 'javascript:this.form.submit();', '', '', true);
	print_table ($table);
}
echo '</form>';

$table->width = '90%';
$table->class = "databox";
$table->size = array ();
$table->size[0] = '20px';
$table->size[1] = '25%';
$table->size[2] = '50%';
$table->size[3] = '25%';
$table->size[4] = '20px';
$table->size[5] = '20px';
$table->head = array ();
$table->head[0] = '';
$table->head[1] = ''; //Placeholder for name
$table->head[2] = __('Template');
$table->head[3] = __('Last fired');
$table->head[4] = __('Status');
$table->head[5] = __('Validate').print_help_icon ('alert_validation', true);
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

$rowPair = true;
$iterator = 0;
foreach ($alerts_simple as $alert) {
	if ($rowPair)
		$table->rowclass[$iterator] = 'rowPair';
	else
		$table->rowclass[$iterator] = 'rowOdd';
	$rowPair = !$rowPair;
	$iterator++;
	
	$total++;
	if (empty ($alert) || $printed >= $config["block_size"] || $total <= $offset) {
		continue;
	}
	$printed++;
	array_push ($table->data, format_alert_row ($alert, false, $print_agent, $url));
}

echo '<form method="post" action="'.$url.'">';

if (!empty ($table->data)) {
	pagination ($total, $url);
	print_table ($table);
} else {
	echo '<div class="nf">'.__('No simple alerts found').'</div>';
}

$table->title = __('Compound alerts');
$table->head[1] = __('Agent');
$table->head[2] = __('Name');
$table->data = array ();

$combined_total = 0;
$combined_printed = 0;
foreach ($alerts_combined as $alert) {
	$combined_total++;
	if (empty ($alert) || $combined_printed >= $config["block_size"] || $combined_total <= $offset) {
		continue;
	}
	$combined_printed++;
	array_push ($table->data, format_alert_row ($alert, true, $print_agent));
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
		cluetipClass: 'default'
	}).click (function () {
		return false;
	});
});
</script>
