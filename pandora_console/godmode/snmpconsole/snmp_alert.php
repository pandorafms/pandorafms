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

// Form submitted
// =============

if (isset ($_GET["update_alert"]) && $_GET["update_alert"] == "-1") {
	ui_print_page_header (__('SNMP Console')." &raquo; ".__('Create alert'),
		"images/op_snmp.png", false, "snmp_alert", false);
}
else if (isset ($_GET["update_alert"]) && $_GET["update_alert"] != "-1") {
	ui_print_page_header (__('SNMP Console')." &raquo; ".__('Update alert'),
		"images/op_snmp.png", false, "snmp_alert", false);
}
else if (isset ($_GET["submit"])) {
	ui_print_page_header (__('SNMP Console')." &raquo; ".__('Update alert'),
		"images/op_snmp.png", false, "snmp_alert", false);
	$id_as = (int) get_parameter_get ("submit", -1);
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
	$trap_type = (int) get_parameter ("trap_type", -1);
	$single_value = (string) get_parameter ("single_value"); 
	
	if ($time_threshold == -1) {
		$time_threshold = $time_other;
	}
	
	if ($id_as < 1) {
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
			'trap_type' => $trap_type,
			'single_value' => $single_value);
			
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
			trap_type = %d, single_value = '%s' 
			WHERE id_as = %d",
			$priority, $alert_type, $al_field1, $al_field2, $al_field3,
			$al_field4, $al_field5, $al_field6, $al_field7, $al_field8,
			$al_field9, $al_field10,
			$description, $source_ip, $custom_value, $oid, $time_threshold,
			$max_alerts, $min_alerts, $custom_oid_data_1, $custom_oid_data_2,
			$custom_oid_data_3, $custom_oid_data_4, $custom_oid_data_5,
			$custom_oid_data_6, $trap_type, $single_value, $id_as);
		
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
else {
	ui_print_page_header (__('SNMP Console')." &raquo; ".__('Alert overview'), "images/op_snmp.png", false, "snmp_alert", false);
}

// From variable init
// ==================
if ((isset ($_GET["update_alert"])) && ($_GET["update_alert"] != -1)) {
	$id_as = (int) get_parameter_get ("update_alert", -1);
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
	$trap_type = $alert["trap_type"];
	$single_value = $alert["single_value"]; 
}
elseif (isset ($_GET["update_alert"])) {
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
	$trap_type = -1;
	$single_value = '';
}

// Header

// Alert Delete
// =============
if (isset ($_GET["delete_alert"])) { // Delete alert
	$alert_delete = (int) get_parameter_get ("delete_alert", 0);
	
	$result = db_process_sql_delete('talert_snmp', array('id_as' => $alert_delete));
	if ($result === false) {
		db_pandora_audit("SNMP management", "Fail try to delete snmp alert #$alert_delete");
		ui_print_error_message(__('There was a problem deleting the alert'));
	}
	else {
		db_pandora_audit("SNMP management", "Delete snmp alert #$alert_delete");
		ui_print_success_message(__('Successfully deleted'));
	}
}

// Alert form
if (isset ($_GET["update_alert"])) {
	//the update_alert means the form should be displayed. If update_alert > 1 then an existing alert is updated
	echo '<form name="agente" method="post" action="index.php?sec=snmpconsole&sec2=godmode/snmpconsole/snmp_alert&submit='.$id_as.'">';
	
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
	$trap_types = array(-1 => __('None'), 0 => __('Cold start (0)'), 1 => __('Warm start (1)'), 2 => __('Link down (2)'), 3 => __('Link up (3)'), 4 => __('Authentication failure (4)'), 5 => __('Other'));
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
	
	//Button
	//echo '<tr><td></td><td align="right">';
	
	// End table
	//echo "</td></tr></table>";
	
	// Alert configuration
	
	//echo '<table cellpadding="4" cellspacing="4" width="98%" class="databox_color" style="border:1px solid #A9A9A9;">';
	
	//echo '<tr><td class="datos"><b>' . __('Alert configuration') . ui_print_help_icon("snmp_alert_configuration", true) . '</b></td></tr>';
	
	// Alert fields
	
	$al = array('al_field1' => $al_field1, 'al_field2' => $al_field2, 'al_field3' => $al_field3,
			'al_field4' => $al_field4, 'al_field5' => $al_field5, 'al_field6' => $al_field6,
			'al_field7' => $al_field7, 'al_field8' => $al_field8, 'al_field9' => $al_field9,
			'al_field10' => $al_field10);
	
	// Hidden div with help hint to fill with javascript
	html_print_div(array('id' => 'help_snmp_alert_hint', 'content' => ui_print_help_icon ("snmp_alert_field1", true), 'hidden' => true));
	
	for ($i=1; $i<=10; $i++) {
		echo '<tr id="table1-field'.$i.'"><td class="datos" valign="top">'.html_print_image('images/spinner.gif',true);
		echo '<td class="datos">'.html_print_image('images/spinner.gif',true);
		html_print_input_hidden('field'.$i.'_value', isset($al['al_field'.$i]) ? $al['al_field'.$i] : '');
		echo '</td></tr>';
	}
	
	// Max / Min alerts
	echo '<tr><td class="datos2">'.__('Min. number of alerts').'</td><td class="datos2">';
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
	echo ' '.__('seconds').'</div></td></tr>';
	
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
	
	switch ($config['dbtype']){
		case "mysql":
		case "postgresql":
			html_print_select_from_sql ('SELECT id, name FROM talert_actions ORDER BY name',
			"alert_type", $alert_type, '', '', 0, false, false, false);
			break;
		case "oracle":
			html_print_select_from_sql ('SELECT id, dbms_lob.substr(name,4000,1) as name FROM talert_actions ORDER BY dbms_lob.substr(name,4000,1)',
			"alert_type", $alert_type, '', '', 0, false, false, false);
			break;
	}
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
	
	//Overview
	$result = db_get_all_rows_in_table ("talert_snmp");
	if ($result === false) {
		$result = array ();
		echo "<div class='nf'>".__('There are no SNMP alerts')."</div>";
	}
	
	$table->data = array ();
	$table->head = array ();
	$table->size = array ();
	$table->cellpadding = 4;
	$table->cellspacing = 4;
	$table->width = "98%";
	$table->class= "databox";
	$table->align = array ();
	
	$table->head[0] = __('Alert action');
	
	$table->head[1] = __('SNMP Agent');
	$table->size[1] = "90px";
	$table->align[1] = 'center';
	
	$table->head[2] = __('OID');
	$table->align[2] = 'center';
	
	$table->head[3] = __('Custom Value/OID');
	$table->align[3] = 'center';
	
	$table->head[4] = __('Description');
	
	$table->head[5] = __('Times fired');
	$table->align[5] = 'center';
	
	$table->head[6] = __('Last fired');
	$table->align[6] = 'center';
	
	$table->head[7] = __('Action');
	$table->size[7] = "50px";
	$table->align[7] = 'center';
	
	foreach ($result as $row) {
		$data = array ();
		$data[0] = '<a href="index.php?sec=snmpconsole&sec2=godmode/snmpconsole/snmp_alert&update_alert='.$row["id_as"].'">' . alerts_get_alert_action_name ($row["id_alert"]) . '</a>';
		
		$data[1] = __('SNMP Agent');
		$data[1] = $row["agent"];
		$data[2] = __('OID');
		$data[2] = $row["oid"];
		$data[3] = __('Custom Value/OID');
		$data[3] = $row["custom_oid"];
		
		$data[4] = $row["description"];
		$data[5] = $row["times_fired"];
		
		if (($row["last_fired"] != "1970-01-01 00:00:00") and ($row["last_fired"] != "01-01-1970 00:00:00")) {
			$data[6] = ui_print_timestamp($row["last_fired"], true);
		}
		else {
			$data[6] = __('Never');
		}
		
		$data[7] = '<a href="index.php?sec=snmpconsole&sec2=godmode/snmpconsole/snmp_alert&update_alert='.$row["id_as"].'">' .
			html_print_image("images/config.png", true, array("border" => '0', "alt" => __('Update'))) . '</a>' .
			'&nbsp;&nbsp;<a href="index.php?sec=snmpconsole&sec2=godmode/snmpconsole/snmp_alert&delete_alert='.$row["id_as"].'">'  .
			html_print_image("images/cross.png", true, array("border" => '0', "alt" => __('Delete'))) . '</a>';
		$idx = count ($table->data); //The current index of the table is 1 less than the count of table data so we count before adding to table->data
		array_push ($table->data, $data);
		
		$table->rowclass[$idx] = get_priority_class ($row["priority"]);
	}
	
	if (!empty ($table->data)) {
		html_print_table ($table);
	}
	
	unset ($table);
	
	echo '<div style="text-align:right; width:98%">';
	echo '<form name="agente" method="post" action="index.php?sec=snmpconsole&sec2=godmode/snmpconsole/snmp_alert&update_alert=-1">';
	html_print_submit_button (__('Create'), "add_alert", false, 'class="sub next"');
	echo "</form></div>";
	
	echo '<div style="margin-left: 30px; line-height: 17px; vertical-align: top; width:120px;">';
	echo '<h3>'.__('Legend').'</h3>';
	foreach (get_priorities () as $num => $name) {
		echo '<span class="'.get_priority_class ($num).'">'.$name.'</span>';
		echo '<br />';
	}
	echo '</div>';
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
	
	$("#alert_type").change (function () {
		values = Array ();
		values.push ({name: "page",
			value: "godmode/alerts/alert_commands"});
		values.push ({name: "get_alert_command",
			value: "1"});
		values.push ({name: "id_action",
			value: this.value});
		jQuery.get (<?php echo "'" . ui_get_full_url("ajax.php", false, false, false) . "'"; ?>,
			values,
			function (data, status) {
				original_command = js_html_entity_decode (data["command"]);
				command_description = js_html_entity_decode (data["description"]);
				for (i=1; i<=10; i++) {
					var old_value = '';
					// Only keep the value if is provided from hidden (first time)
					if ($("[name=field"+i+"_value]").attr('id') == "hidden-field" + i + "_value") {
						old_value = $("[name=field"+i+"_value]").val();
					}
					
					// If the row is empty, hide de row
					if(data["fields_rows"][i] == '') {
						$('#table1-field'+i).hide();
					}
					else {
						$('#table1-field'+i).replaceWith(data["fields_rows"][i]);
						
						// The row provided has a predefined class. We delete it
						$('#table1-field'+i).removeAttr('class');
						
						// Add help hint only in first field
						if(i == 1) {
							var td_content = $('#table1-field'+i).find('td').eq(0);
							td_content.html(td_content.html() + $('#help_snmp_alert_hint').html());
						}
						
						$("[name=field"+i+"_value]").val(old_value);
						$('#table1-field').show();
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
