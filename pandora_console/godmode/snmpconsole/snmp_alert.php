<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


// Load global vars

if (! check_acl ($config['id_user'], 0, "LW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access SNMP Alert Management");
	require ("general/noaccess.php");
	return;
}

$trap_types = array(
	SNMP_TRAP_TYPE_NONE => __('None'),
	SNMP_TRAP_TYPE_COLD_START => __('Cold start (0)'),
	SNMP_TRAP_TYPE_WARM_START => __('Warm start (1)'),
	SNMP_TRAP_TYPE_LINK_DOWN => __('Link down (2)'),
	SNMP_TRAP_TYPE_LINK_UP => __('Link up (3)'),
	SNMP_TRAP_TYPE_AUTHENTICATION_FAILURE => __('Authentication failure (4)'),
	SNMP_TRAP_TYPE_OTHER => __('Other'));

// Form submitted
// =============

$update_alert = (bool)get_parameter('update_alert', false);
$create_alert = (bool)get_parameter('create_alert', false);
$save_alert = (bool)get_parameter('save_alert', false);
$modify_alert = (bool)get_parameter('modify_alert', false);
$delete_alert = (bool)get_parameter('delete_alert', false);
$multiple_delete = (bool)get_parameter('multiple_delete', false);

if ($update_alert || $modify_alert) {
	ui_print_page_header(__('SNMP Console')." &raquo; ".__('Update alert'),
		"images/op_snmp.png", false, "snmp_alert", false);
}
else if ($create_alert || $save_alert) {
	ui_print_page_header(__('SNMP Console')." &raquo; ".__('Create alert'),
		"images/op_snmp.png", false, "snmp_alert", false);
}
else {
	ui_print_page_header(__('SNMP Console')." &raquo; ".__('Alert overview'),
		"images/op_snmp.png", false, "snmp_alert", false);
}

if ($save_alert || $modify_alert) {
	$id_as = (int) get_parameter("id_alert_snmp", -1);
	
	$source_ip = (string) get_parameter_post ("source_ip");
	$alert_type = (int) get_parameter_post ("alert_type"); //Event, e-mail
	$description = (string) get_parameter_post ("description");
	$oid = (string) get_parameter_post ("oid");
	$custom_value = (string) get_parameter_post ("custom_value");
	$time_threshold = (int) get_parameter_post ("time_threshold", SECONDS_5MINUTES);
	$time_other = (int) get_parameter_post ("time_other", -1);
	$al_field1 = (string) get_parameter_post ("field1_value");
	$al_field2 = (string) get_parameter_post ("field2_value");
	$al_field3 = (string) get_parameter_post ("field3_value");
	$al_field4 = (string) get_parameter_post ("field4_value");
	$al_field5 = (string) get_parameter_post ("field5_value");
	$al_field6 = (string) get_parameter_post ("field6_value");
	$al_field7 = (string) get_parameter_post ("field7_value");
	$al_field8 = (string) get_parameter_post ("field8_value");
	$al_field9 = (string) get_parameter_post ("field9_value");
	$al_field10 = (string) get_parameter_post ("al_field10");
	$max_alerts = (int) get_parameter_post ("max_alerts", 1);
	$min_alerts = (int) get_parameter_post ("min_alerts", 0);
	$priority = (int) get_parameter_post ("priority", 0);
	$custom_oid_data_1 = (string) get_parameter ("custom_oid_data_1"); 
	$custom_oid_data_2 = (string) get_parameter ("custom_oid_data_2"); 
	$custom_oid_data_3 = (string) get_parameter ("custom_oid_data_3"); 
	$custom_oid_data_4 = (string) get_parameter ("custom_oid_data_4"); 
	$custom_oid_data_5 = (string) get_parameter ("custom_oid_data_5"); 
	$custom_oid_data_6 = (string) get_parameter ("custom_oid_data_6");
	$custom_oid_data_7 = (string) get_parameter ("custom_oid_data_7");
	$custom_oid_data_8 = (string) get_parameter ("custom_oid_data_8");
	$custom_oid_data_9 = (string) get_parameter ("custom_oid_data_9");
	$custom_oid_data_10 = (string) get_parameter ("custom_oid_data_10");
	$trap_type = (int) get_parameter ("trap_type", -1);
	$single_value = (string) get_parameter ("single_value"); 
	$position = (int) get_parameter ("position"); 
	
	if ($time_threshold == -1) {
		$time_threshold = $time_other;
	}
	
	if ($save_alert) {
		$values = array(
			'id_alert' => $alert_type,
			'al_field1' => $al_field1,
			'al_field2' => $al_field2,
			'al_field3' => $al_field3,
			'al_field4' => $al_field4,
			'al_field5' => $al_field5,
			'al_field6' => $al_field6,
			'al_field7' => $al_field7,
			'al_field8' => $al_field8,
			'al_field9' => $al_field9,
			'al_field10' => $al_field10,
			'description' => $description,
			'agent' => $source_ip,
			'custom_oid' => $custom_value,
			'oid' => $oid,
			'time_threshold' => $time_threshold,
			'max_alerts' => $max_alerts,
			'min_alerts' => $min_alerts,
			'priority' => $priority,
			'_snmp_f1_' => $custom_oid_data_1,
			'_snmp_f2_' => $custom_oid_data_2,
			'_snmp_f3_' => $custom_oid_data_3,
			'_snmp_f4_' => $custom_oid_data_4,
			'_snmp_f5_' => $custom_oid_data_5,
			'_snmp_f6_' => $custom_oid_data_6,
			'_snmp_f7_' => $custom_oid_data_7,
			'_snmp_f8_' => $custom_oid_data_8,
			'_snmp_f9_' => $custom_oid_data_9,
			'_snmp_f10_' => $custom_oid_data_10,
			'trap_type' => $trap_type,
			'single_value' => $single_value,
			'position' => $position);
		
		$result = db_process_sql_insert('talert_snmp', $values);
		
		if (!$result) {
			db_pandora_audit("SNMP management", "Fail try to create snmp alert");
			ui_print_error_message(__('There was a problem creating the alert'));
		}
		else {
			db_pandora_audit("SNMP management", "Create snmp alert #$result");
			ui_print_success_message(__('Successfully created'));
		}
		
	}
	else {
		$sql = sprintf ("UPDATE talert_snmp SET
			priority = %d, id_alert = %d, al_field1 = '%s',
			al_field2 = '%s', al_field3 = '%s', al_field4 = '%s',
			al_field5 = '%s', al_field6 = '%s',al_field7 = '%s',
			al_field8 = '%s', al_field9 = '%s',al_field10 = '%s',
			description = '%s',
			agent = '%s', custom_oid = '%s', oid = '%s',
			time_threshold = %d, max_alerts = %d, min_alerts = %d,
			_snmp_f1_ = '%s', _snmp_f2_ = '%s', _snmp_f3_ = '%s',
			_snmp_f4_ = '%s', _snmp_f5_ = '%s', _snmp_f6_ = '%s',
			_snmp_f7_ = '%s', _snmp_f8_ = '%s', _snmp_f9_ = '%s',
			_snmp_f10_ = '%s', trap_type = %d, single_value = '%s',
			position = '%s' 
			WHERE id_as = %d",
			$priority, $alert_type, $al_field1, $al_field2, $al_field3,
			$al_field4, $al_field5, $al_field6, $al_field7, $al_field8,
			$al_field9, $al_field10,
			$description, $source_ip, $custom_value, $oid, $time_threshold,
			$max_alerts, $min_alerts, $custom_oid_data_1, $custom_oid_data_2,
			$custom_oid_data_3, $custom_oid_data_4, $custom_oid_data_5,
			$custom_oid_data_6, $custom_oid_data_7, $custom_oid_data_8,
			$custom_oid_data_9, $custom_oid_data_10, $trap_type, $single_value,
			$position, $id_as);
		
		$result = db_process_sql ($sql);
		
		if (!$result) {
			db_pandora_audit("SNMP management", "Fail try to update snmp alert #$id_as");
			ui_print_error_message(__('There was a problem updating the alert'));
		}
		else {
			db_pandora_audit("SNMP management", "Update snmp alert #$id_as");
			ui_print_success_message(__('Successfully updated'));
		}
	}
}

// From variable init
// ==================
if ($update_alert) {
	$id_as = (int) get_parameter("id_alert_snmp", -1);
	
	$alert = db_get_row ("talert_snmp", "id_as", $id_as);
	$id_as = $alert["id_as"];
	$source_ip = $alert["agent"];
	$alert_type = $alert["id_alert"];
	$description = $alert["description"];
	$oid = $alert["oid"];
	$custom_value = $alert["custom_oid"];
	$time_threshold = $alert["time_threshold"];
	$al_field1 = $alert["al_field1"];
	$al_field2 = $alert["al_field2"];
	$al_field3 = $alert["al_field3"];
	$al_field4 = $alert["al_field4"];
	$al_field5 = $alert["al_field5"];
	$al_field6 = $alert["al_field6"];
	$al_field7 = $alert["al_field7"];
	$al_field8 = $alert["al_field8"];
	$al_field9 = $alert["al_field9"];
	$al_field10 = $alert["al_field10"];
	$max_alerts = $alert["max_alerts"];
	$min_alerts = $alert["min_alerts"];
	$priority = $alert["priority"];
	$custom_oid_data_1 = $alert["_snmp_f1_"];
	$custom_oid_data_2 = $alert["_snmp_f2_"];
	$custom_oid_data_3 = $alert["_snmp_f3_"];
	$custom_oid_data_4 = $alert["_snmp_f4_"];
	$custom_oid_data_5 = $alert["_snmp_f5_"];
	$custom_oid_data_6 = $alert["_snmp_f6_"];
	$custom_oid_data_7 = $alert["_snmp_f7_"];
	$custom_oid_data_8 = $alert["_snmp_f8_"];
	$custom_oid_data_9 = $alert["_snmp_f9_"];
	$custom_oid_data_10 = $alert["_snmp_f10_"];
	$trap_type = $alert["trap_type"];
	$single_value = $alert["single_value"]; 
	$position = $alert["position"];
}
elseif ($create_alert) {
	// Variable init
	$id_as = -1;
	$source_ip = "";
	$alert_type = 1; //Event, e-mail
	$description = "";
	$oid = "";
	$custom_value = "";
	$time_threshold = SECONDS_5MINUTES;
	$al_field1 = "";
	$al_field2 = "";
	$al_field3 = "";
	$al_field4 = "";
	$al_field5 = "";
	$al_field6 = "";
	$al_field7 = "";
	$al_field8 = "";
	$al_field9 = "";
	$al_field10 = "";
	$max_alerts = 1;
	$min_alerts = 0;
	$priority = 0;
	$custom_oid_data_1 = '';
	$custom_oid_data_2 = '';
	$custom_oid_data_3 = '';
	$custom_oid_data_4 = '';
	$custom_oid_data_5 = '';
	$custom_oid_data_6 = '';
	$custom_oid_data_7 = '';
	$custom_oid_data_8 = '';
	$custom_oid_data_9 = '';
	$custom_oid_data_10 = '';
	$trap_type = -1;
	$single_value = '';
	$position = 0;
}

// Header

// Alert Delete
// =============
if ($delete_alert) { // Delete alert
	$alert_delete = (int) get_parameter_get ("delete_alert", 0);
	
	$result = db_process_sql_delete('talert_snmp',
		array('id_as' => $alert_delete));
	
	if ($result === false) {
		db_pandora_audit("SNMP management", "Fail try to delete snmp alert #$alert_delete");
		ui_print_error_message(__('There was a problem deleting the alert'));
	}
	else {
		db_pandora_audit("SNMP management", "Delete snmp alert #$alert_delete");
		ui_print_success_message(__('Successfully deleted'));
	}
}

if ($multiple_delete) {
	$delete_ids = get_parameter('delete_ids', array());
	
	$total = count($delete_ids);
	
	$count = 0;
	foreach ($delete_ids as $alert_delete) {
		$result = db_process_sql_delete('talert_snmp',
			array('id_as' => $alert_delete));
		
		if ($result !== false) {
			db_pandora_audit("SNMP management", "Delete snmp alert #$alert_delete");
			$count++;
		}
		else {
			db_pandora_audit("SNMP management", "Fail try to delete snmp alert #$alert_delete");
		}
	}
	
	if ($count == $total) {
		ui_print_success_message(__('Successfully deleted alerts (%s / %s)', $count, $total));
	}
	else {
		ui_print_error_message(__('Unsuccessfully deleted alerts (%s / %s)', $count, $total));
	}
}

// Alert form
if ($create_alert || $update_alert) {
//if (isset ($_GET["update_alert"])) {
	//the update_alert means the form should be displayed. If update_alert > 1 then an existing alert is updated
	echo '<form name="agente" method="post" action="index.php?sec=snmpconsole&sec2=godmode/snmpconsole/snmp_alert">';
	
	html_print_input_hidden('id_alert_snmp', $id_as);
	
	if ($create_alert) {
		html_print_input_hidden('save_alert', 1);
	}
	if ($update_alert) {
		html_print_input_hidden('modify_alert', 1);
	}
	
	/* SNMP alert filters */
	
	echo '<table cellpadding="4" cellspacing="4" width="98%" class="databox" style="font-weight: bold">';
	
	// Description
	echo '<tr><td class="datos">'.__('Description').'</td><td class="datos">';
	html_print_input_text ("description", $description, '', 60);
	echo '</td></tr>';
	
	//echo '<tr><td class="datos"><b>' . __('Alert filters') . ui_print_help_icon("snmp_alert_filters", true) . '</b></td></tr>';
	
	// OID
	echo '<tr id="tr-oid"><td class="datos2">'.__('OID').'</td><td class="datos2">';
	html_print_input_text ("oid", $oid, '', 50, 255);
	echo '</td></tr>';
	
	// Custom
	echo '<tr id="tr-custom_value"><td class="datos"  valign="top">'.__('Custom Value/OID');
	echo ui_print_help_icon ("snmp_alert_custom", true);
	
	echo '</td><td class="datos">';
	html_print_textarea ("custom_value", $custom_value, 2, $custom_value, 'style="width:400px;"');
	
	echo '</td></tr>';
	
	// SNMP Agent
	echo '<tr id="tr-source_ip"><td class="datos2">'.__('SNMP Agent').' (IP)</td><td class="datos2">';
	html_print_input_text ("source_ip", $source_ip, '', 20);
	echo '</td></tr>';
	
	// Trap type
	echo '<tr><td class="datos">'.__('Trap type').'</td><td class="datos">';
	echo html_print_select ($trap_types, 'trap_type', $trap_type, '', '', '', false, false, false);
	echo '</td></tr>';
	
	// Single value
	echo '<tr><td class="datos">'.__('Single value').'</td><td class="datos">';
	html_print_input_text ("single_value", $single_value, '', 20);
	echo '</td></tr>';
	
	//  Custom OID/Data #1
	echo '<tr id="tr-custom_value"><td class="datos"  valign="top">'.__('Custom OID/Data #1');
	echo ui_print_help_icon ("field_match_snmp", true);
	
	echo '</td><td class="datos">';
	html_print_input_text ("custom_oid_data_1", $custom_oid_data_1, '', 60);
	echo '</td></tr>';
	
	//  Custom OID/Data #2
	echo '<tr id="tr-custom_value"><td class="datos"  valign="top">'.__('Custom OID/Data #2');
	//echo ui_print_help_icon ("snmp_alert_custom", true);
	
	echo '</td><td class="datos">';
	html_print_input_text ("custom_oid_data_2", $custom_oid_data_2, '', 60);
	echo '</td></tr>';
	
	//  Custom OID/Data #3
	echo '<tr id="tr-custom_value"><td class="datos"  valign="top">'.__('Custom OID/Data #3');
	//echo ui_print_help_icon ("snmp_alert_custom", true);
	
	echo '</td><td class="datos">';
	html_print_input_text ("custom_oid_data_3", $custom_oid_data_3, '', 60);
	echo '</td></tr>';
	
	//  Custom OID/Data #4
	echo '<tr id="tr-custom_value"><td class="datos"  valign="top">'.__('Custom OID/Data #4');
	//echo ui_print_help_icon ("snmp_alert_custom", true);
	
	echo '</td><td class="datos">';
	html_print_input_text ("custom_oid_data_4", $custom_oid_data_4, '', 60);
	echo '</td></tr>';
	
	//  Custom OID/Data #5
	echo '<tr id="tr-custom_value"><td class="datos"  valign="top">'.__('Custom OID/Data #5');
	//echo ui_print_help_icon ("snmp_alert_custom", true);
	
	echo '</td><td class="datos">';
	html_print_input_text ("custom_oid_data_5", $custom_oid_data_5, '', 60);
	echo '</td></tr>';
	
	//  Custom OID/Data #6
	echo '<tr id="tr-custom_value"><td class="datos"  valign="top">'.__('Custom OID/Data #6');
	//echo ui_print_help_icon ("snmp_alert_custom", true);
	
	echo '</td><td class="datos">';
	html_print_input_text ("custom_oid_data_6", $custom_oid_data_6, '', 60);
	echo '</td></tr>';
	
	//  Custom OID/Data #7
	echo '<tr id="tr-custom_value"><td class="datos"  valign="top">'.__('Custom OID/Data #7');
	echo '</td><td class="datos">';
	html_print_input_text ("custom_oid_data_7", $custom_oid_data_7, '', 60);
	echo '</td></tr>';
	
	//  Custom OID/Data #8
	echo '<tr id="tr-custom_value"><td class="datos"  valign="top">'.__('Custom OID/Data #8');
	echo '</td><td class="datos">';
	html_print_input_text ("custom_oid_data_8", $custom_oid_data_8, '', 60);
	echo '</td></tr>';
	
	//  Custom OID/Data #9
	echo '<tr id="tr-custom_value"><td class="datos"  valign="top">'.__('Custom OID/Data #9');
	echo '</td><td class="datos">';
	html_print_input_text ("custom_oid_data_9", $custom_oid_data_9, '', 60);
	echo '</td></tr>';
	
	//  Custom OID/Data #10
	echo '<tr id="tr-custom_value"><td class="datos"  valign="top">'.__('Custom OID/Data #10');
	echo '</td><td class="datos">';
	html_print_input_text ("custom_oid_data_10", $custom_oid_data_10, '', 60);
	echo '</td></tr>';
	
	//Button
	//echo '<tr><td></td><td align="right">';
	
	// End table
	//echo "</td></tr></table>";
	
	// Alert configuration
	
	//echo '<table cellpadding="4" cellspacing="4" width="98%" class="databox_color" style="border:1px solid #A9A9A9;">';
	
	//echo '<tr><td class="datos"><b>' . __('Alert configuration') . ui_print_help_icon("snmp_alert_configuration", true) . '</b></td></tr>';
	
	// Alert fields
	
	$al = array(
		'al_field1' => $al_field1,
		'al_field2' => $al_field2,
		'al_field3' => $al_field3,
		'al_field4' => $al_field4,
		'al_field5' => $al_field5,
		'al_field6' => $al_field6,
		'al_field7' => $al_field7,
		'al_field8' => $al_field8,
		'al_field9' => $al_field9,
		'al_field10' => $al_field10);
	
	// Hidden div with help hint to fill with javascript
	html_print_div(array('id' => 'help_snmp_alert_hint', 'content' => ui_print_help_icon ("snmp_alert_field1", true), 'hidden' => true));
	
	for ($i = 1; $i <= 10; $i++) {
		echo '<tr id="table_macros-field'.$i.'"><td class="datos" valign="top">'.html_print_image('images/spinner.gif',true);
		echo '<td class="datos">' . html_print_image('images/spinner.gif',true);
		html_print_input_hidden('field'.$i.'_value', isset($al['al_field'.$i]) ? $al['al_field'.$i] : '');
		echo '</td></tr>';
	}
	
	// Max / Min alerts
	echo '<tr><td class="datos2">' . __('Min. number of alerts').'</td><td class="datos2">';
	html_print_input_text ("min_alerts", $min_alerts, '', 3);
	
	echo '</td></tr><tr><td class="datos">'.__('Max. number of alerts').'</td><td class="datos">';
	html_print_input_text ("max_alerts", $max_alerts, '', 3);
	echo '</td></tr>';
	
	// Time Threshold
	echo '<tr><td class="datos2">'.__('Time threshold').'</td><td class="datos2">';
	
	$fields = array ();
	$fields[$time_threshold] = human_time_description_raw ($time_threshold);
	$fields[SECONDS_5MINUTES] = human_time_description_raw (SECONDS_5MINUTES);
	$fields[SECONDS_10MINUTES] = human_time_description_raw (SECONDS_10MINUTES);
	$fields[SECONDS_15MINUTES] = human_time_description_raw (SECONDS_15MINUTES);
	$fields[SECONDS_30MINUTES] = human_time_description_raw (SECONDS_30MINUTES);
	$fields[SECONDS_1HOUR] = human_time_description_raw (SECONDS_1HOUR);
	$fields[SECONDS_2HOUR] = human_time_description_raw (SECONDS_2HOUR);
	$fields[SECONDS_5HOUR] = human_time_description_raw (SECONDS_5HOUR);
	$fields[SECONDS_12HOURS] = human_time_description_raw (SECONDS_12HOURS);
	$fields[SECONDS_1DAY] = human_time_description_raw (SECONDS_1DAY);
	$fields[SECONDS_1WEEK] = human_time_description_raw (SECONDS_1WEEK);
	$fields[-1] = __('Other value');
	
	html_print_select ($fields, "time_threshold", $time_threshold, '', '', '0', false, false, false, '" style="margin-right:60px');
	echo '<div id="div-time_other" style="display:none">';
	html_print_input_text ("time_other", 0, '', 6);
	echo ' ' . __('seconds') . '</div></td></tr>';
	
	// Priority
	echo '<tr><td class="datos">'.__('Priority').'</td><td class="datos">';
	echo html_print_select (get_priorities (), "priority", $priority, '', '', '0', false, false, false);
	echo '</td></tr>';
	
	// Alert type (e-mail, event etc.)
	echo '<tr><td class="datos">'.__('Alert action').'</td><td class="datos">';
	
	$fields = array ();
	$result = db_get_all_rows_in_table ('talert_actions', "name");
	if ($result === false) {
		$result = array ();
	}
	
	foreach ($result as $row) {
		$fields[$row["id"]] = $row["name"];
	}
	
	switch ($config['dbtype']) {
		case "mysql":
		case "postgresql":
			html_print_select_from_sql(
				'SELECT id, name
				FROM talert_actions
				ORDER BY name',
				"alert_type", $alert_type, '', '', 0, false, false, false);
			break;
		case "oracle":
			html_print_select_from_sql(
				'SELECT id, dbms_lob.substr(name,4000,1) as name
				FROM talert_actions
				ORDER BY dbms_lob.substr(name,4000,1)',
				"alert_type", $alert_type, '', '', 0, false, false, false);
			break;
	}
	echo '</td></tr>';
	echo '<tr><td class="datos">' . __('Position') . '</td><td class="datos">';
	html_print_input_text ("position", $position, '', 3);
	echo '</td></tr>';
	echo '</table>';
	
	echo "<table style='width:98%'>";
	echo '<tr><td></td><td align="right">';
	if ($id_as > 0) {
		html_print_submit_button (__('Update'), "submit", false, 'class="sub upd"', false);
	}
	else {
		html_print_submit_button (__('Create'), "submit", false, 'class="sub wand"', false);
	}
	echo '</td></tr></table>';
	echo "</table>";
	echo "</form>";
}
else {
	require_once ('include/functions_alerts.php');
	
	$free_search = (string)get_parameter('free_search', '');
	$trap_type_filter = (int)get_parameter('trap_type_filter', SNMP_TRAP_TYPE_NONE);
	$priority_filter = (int)get_parameter('priority_filter', -1);
	$filter_param = (bool)get_parameter('filter', false);
	$offset = (int) get_parameter ('offset');
	
	$table_filter = null;
	$table_filter->width = "98%";
	$table_filter->data = array();
	$table_filter->data[0][0] = __('Free search') . ui_print_help_tip(
		__('Search by these fields description, OID, Custom Value, SNMP Agent (IP), Single value, each Custom OIDs/Datas.'), true);
	$table_filter->data[0][1] =
		html_print_input_text('free_search', $free_search, '', 30, 100, true);
	$table_filter->data[0][2] = __('Trap type');
	$table_filter->data[0][3] =
		html_print_select ($trap_types, 'trap_type_filter', $trap_type_filter, '', '', '', true, false, false);
	$table_filter->data[0][4] = __('Priority');
	$table_filter->data[0][5] =
		html_print_select (get_priorities(), "priority_filter", $priority_filter, '', __('None'), '-1', true, false, false);;
	
	$form_filter = '<form name="agente" method="post" action="index.php?sec=snmpconsole&sec2=godmode/snmpconsole/snmp_alert">';
	$form_filter .= html_print_input_hidden('filter', 1, true);
	$form_filter .= html_print_table($table_filter, true);
	$form_filter .= '<div style="text-align: right; width: ' . $table_filter->width . '">';
	$form_filter .= html_print_submit_button(__('Filter'), 'filter_button', false, 'class="sub filter"', true);
	$form_filter .= '</div>';
	$form_filter .= '</form>';
	
	echo "<br>";
	ui_toggle($form_filter,__('Alert SNMP control filter'), __('Toggle filter(s)'));
	
	$filter = array();
	$filter['offset'] = (int) get_parameter ('offset');
	$filter['limit'] = (int) $config['block_size'];
	if ($filter_param) {
		//Move the first page
		$offset = 0;
		
		$url_pagination = "index.php?" .
			"sec=snmpconsole&" .
			"sec2=godmode/snmpconsole/snmp_alert&" .
			"free_seach=" . $free_search . "&" .
			"trap_type_filter=" . $trap_type_filter . "&" .
			"priority_filter=" . $priority_filter;
	}
	else {
		$url_pagination = "index.php?" .
			"sec=snmpconsole&" .
			"sec2=godmode/snmpconsole/snmp_alert&" .
			"free_seach=" . $free_search . "&" .
			"trap_type_filter=" . $trap_type_filter . "&" .
			"priority_filter=" . $priority_filter . "&" .
			"offset=" . $offset;
	}
	
	$where_sql = ' 1 = 1';
	if ($trap_type_filter != SNMP_TRAP_TYPE_NONE) {
		$where_sql .= ' AND `trap_type` = ' . $trap_type_filter;
	}
	
	if ($priority_filter != -1) {
		$where_sql .= ' AND `priority` = ' . $priority_filter;
	}
	
	if (!empty($free_search)) {
		$where_sql .= " AND (`single_value` = '" . $free_search . "'
			OR `_snmp_f10_` = '" . $free_search . "'
			OR `_snmp_f9_` = '" . $free_search . "'
			OR `_snmp_f8_` = '" . $free_search . "'
			OR `_snmp_f7_` = '" . $free_search . "'
			OR `_snmp_f6_` = '" . $free_search . "'
			OR `_snmp_f5_` = '" . $free_search . "'
			OR `_snmp_f4_` = '" . $free_search . "'
			OR `_snmp_f3_` = '" . $free_search . "'
			OR `_snmp_f2_` = '" . $free_search . "'
			OR `_snmp_f1_` = '" . $free_search . "'
			OR `oid` = '" . $free_search . "'
			OR `custom_oid` = '" . $free_search . "'
			OR `agent` = '" . $free_search . "'
			OR `description` = '" . $free_search . "')";
	}
	
	$count = db_get_value_sql('SELECT COUNT(*)
		FROM talert_snmp WHERE' . $where_sql);
	
	
	
	
	$result = array();
	
	//Overview
	if ($count == 0) {
		$result = array ();
		echo "<div class='nf'>" . __('There are no SNMP alerts') . "</div>";
	}
	else {
		ui_pagination ($count, $url_pagination);
		
		$where_sql .= ' LIMIT ' . $config['block_size'] . ' OFFSET ' . $offset;
		$result = db_get_all_rows_sql('SELECT *
			FROM talert_snmp WHERE' . $where_sql);
	}
	
	$table->data = array ();
	$table->head = array ();
	$table->size = array ();
	$table->cellpadding = 4;
	$table->cellspacing = 4;
	$table->width = "98%";
	$table->class= "databox";
	$table->align = array ();
	
	$table->head[0] = '<span title="' . __('Position') . '">' . __('P.') . '</span>';
	$table->align[0] = 'center';
		
	$table->head[1] = __('Alert action');
	
	$table->head[2] = __('SNMP Agent');
	$table->size[2] = "90px";
	$table->align[2] = 'center';
	
	$table->head[3] = __('OID');
	$table->align[3] = 'center';
	
	$table->head[4] = __('Custom Value/OID');
	$table->align[4] = 'center';
	
	$table->head[5] = __('Description');
	
	$table->head[6] = '<span title="' . __('Times fired') . '">' . __('TF.') . '</span>';
	$table->size[6] = "50px";
	$table->align[6] = 'center';
	
	$table->head[7] = __('Last fired');
	$table->align[7] = 'center';
	
	$table->head[8] = __('Action');
	$table->size[8] = "80px";
	$table->align[8] = 'center';
	
	$table->head[9] = html_print_checkbox ("all_delete_box", "1", false, true);
	$table->size[9] = "10px";
	$table->align[9] = 'center';
	
	foreach ($result as $row) {
		$data = array ();
		$data[0] = $row["position"];
		
		$url = "index.php?" .
			"sec=snmpconsole&" .
			"sec2=godmode/snmpconsole/snmp_alert&" .
			"id_alert_snmp=" . $row["id_as"] ."&" .
			"update_alert=1";
		
		$data[1] = '<a href="' . $url . '">' .
			alerts_get_alert_action_name ($row["id_alert"]) . '</a>';
		$data[2] = $row["agent"];
		$data[3] = $row["oid"];
		$data[4] = $row["custom_oid"];
		$data[5] = $row["description"];
		$data[6] = $row["times_fired"];
		
		if (($row["last_fired"] != "1970-01-01 00:00:00") and ($row["last_fired"] != "01-01-1970 00:00:00")) {
			$data[7] = ui_print_timestamp($row["last_fired"], true);
		}
		else {
			$data[7] = __('Never');
		}
		
		$data[8] = '<a href="index.php?sec=snmpconsole&sec2=godmode/snmpconsole/snmp_alert&update_alert='.$row["id_as"].'">' .
			html_print_image("images/config.png", true, array("border" => '0', "alt" => __('Update'))) . '</a>' .
			'&nbsp;&nbsp;<a href="index.php?sec=snmpconsole&sec2=godmode/snmpconsole/snmp_alert&delete_alert='.$row["id_as"].'">'  .
			html_print_image("images/cross.png", true, array("border" => '0', "alt" => __('Delete'))) . '</a>';
		
		$data[9] = html_print_checkbox_extended("delete_ids[]",
			$row['id_as'], false, false, false, 'class="chk_delete"', true);
		
		$idx = count ($table->data); //The current index of the table is 1 less than the count of table data so we count before adding to table->data
		array_push ($table->data, $data);
		
		$table->rowclass[$idx] = get_priority_class ($row["priority"]);
	}
	
	if (!empty ($table->data)) {
		echo '<form name="agente" method="post" action="index.php?sec=snmpconsole&sec2=godmode/snmpconsole/snmp_alert">';
		html_print_table ($table);
		
		ui_pagination ($count, $url_pagination);
		
		echo '<div style="text-align:right; width: ' . $table->width . '; margin-bottom: 30px;">';
		html_print_input_hidden('multiple_delete', 1);
		html_print_submit_button(__('Delete selected'), 'delete_button', false, 'class="sub delete"');
		echo '</div>';
		echo '</form>';
	}
	
	echo '<div style="text-align:right; width:' . $table->width . ';">';
	echo '<form name="agente" method="post" action="index.php?sec=snmpconsole&sec2=godmode/snmpconsole/snmp_alert">';
	html_print_input_hidden('create_alert', 1);
	html_print_submit_button (__('Create'), "add_alert", false, 'class="sub next"');
	echo "</form></div>";
	
	echo '<div style="margin-left: 30px; line-height: 17px; vertical-align: top; width:120px;">';
	echo '<h3>'.__('Legend').'</h3>';
	foreach (get_priorities() as $num => $name) {
		echo '<span class="' . get_priority_class ($num).'">' . $name . '</span>';
		echo '<br />';
	}
	echo '</div>';
	
	unset ($table);
}
?>
<script language="javascript" type="text/javascript">

function time_changed () {
	var time = this.value;
	if (time == -1) {
		$('#time_threshold').fadeOut ('normal', function () {
			$('#div-time_other').fadeIn ('normal');
		});
	}
}

$(document).ready (function () {
	$('#time_threshold').change (time_changed);
	
	$("input[name=all_delete_box]").change (function() {
		if ($(this).is(":checked")) {
			$("input[name='delete_ids[]']").check();
		}
		else {
			$("input[name='delete_ids[]']").uncheck();
		}
	});
	
	$("#alert_type").change (function () {
		values = Array ();
		
		values.push ({
			name: "page",
			value: "godmode/alerts/alert_commands"
		});
		values.push ({
			name: "get_alert_command",
			value: "1"
		});
		values.push ({
			name: "id_action",
			value: this.value
		});
		
		jQuery.get (
			<?php
			echo "'" . ui_get_full_url("ajax.php", false, false, false) . "'";
			?>,
			values,
			function (data, status) {
				original_command = js_html_entity_decode (data["command"]);
				command_description = js_html_entity_decode (data["description"]);
				for (i = 1; i <= 10; i++) {
					var old_value = '';
					// Only keep the value if is provided from hidden (first time)
					
					var id_field = $("[name=field" + i + "_value]").attr('id');
					
					if (id_field == "hidden-field" + i + "_value") {
						old_value = $("[name=field" + i + "_value]").val();
					}
					
					// If the row is empty, hide de row
					if (data["fields_rows"][i] == '') {
						$('#table_macros-field' + i).hide();
					}
					else {
						$('#table_macros-field' + i)
							.replaceWith(data["fields_rows"][i]);
						
						// The row provided has a predefined class. We delete it
						$('#table_macros-field' + i)
							.removeAttr('class');
						
						// Add help hint only in first field
						if (i == 1) {
							var td_content =
								$('#table_macros-field' + i)
									.find('td').eq(0);
							
							td_content
								.html(
									td_content.html() +
									$('#help_snmp_alert_hint').html()
								);
						}
						
						$("[name=field" + i + "_value]").val(old_value);
						$('#table_macros-field').show();
					}
				}
			},
			"json"
		);
	});
	
	// Charge the fields of the action 
	$("#alert_type").trigger('change');
}); 
</script>
