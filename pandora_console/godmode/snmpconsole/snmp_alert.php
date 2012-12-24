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
	ui_print_page_header (__('SNMP Console')." &raquo; ".__('Create alert'), "images/computer_error.png", false, "snmp_alert", true);
} else if (isset ($_GET["update_alert"]) && $_GET["update_alert"] != "-1") {
	ui_print_page_header (__('SNMP Console')." &raquo; ".__('Update alert'), "images/computer_error.png", false, "snmp_alert", true);
} else if (isset ($_GET["submit"])) {
	ui_print_page_header (__('SNMP Console')." &raquo; ".__('Update alert'), "images/computer_error.png", false, "snmp_alert", true);
	$id_as = (int) get_parameter_get ("submit", -1);
	$source_ip = (string) get_parameter_post ("source_ip");
	$alert_type = (int) get_parameter_post ("alert_type"); //Event, e-mail
	$description = (string) get_parameter_post ("description");
	$oid = (string) get_parameter_post ("oid");
	$custom_value = (string) get_parameter_post ("custom_value");
	$time_threshold = (int) get_parameter_post ("time_threshold", 300);
	$time_other = (int) get_parameter_post ("time_other", -1);
	$al_field1 = (string) get_parameter_post ("al_field1");
	$al_field2 = (string) get_parameter_post ("al_field2");
	$al_field3 = (string) get_parameter_post ("al_field3");
	$max_alerts = (int) get_parameter_post ("max_alerts", 1);
	$min_alerts = (int) get_parameter_post ("min_alerts", 0);
	$priority = (int) get_parameter_post ("priority", 0);
	
	if ($time_threshold == -1) {
		$time_threshold = $time_other;
	}
	
	if ($id_as < 1) {
		$values = array(
			'id_alert' => $alert_type,
			'al_field1' => $al_field1,
			'al_field2' => $al_field2,
			'al_field3' => $al_field3,
			'description' => $description,
			'agent' => $source_ip,
			'custom_oid' => $custom_value,
			'oid' => $oid,
			'time_threshold' => $time_threshold,
			'max_alerts' => $max_alerts,
			'min_alerts' => $min_alerts,
			'priority' => $priority);

			$result = db_process_sql_insert('talert_snmp', $values);
		
		if (!$result) {
			echo '<h3 class="error">'.__('There was a problem creating the alert').'</h3>';
		}
		else {
			echo '<h3 class="suc">'.__('Successfully created').'</h3>';
		}
		
	} else {
		$sql = sprintf ("UPDATE talert_snmp SET
				priority = %d, id_alert = %d, al_field1 = '%s', al_field2 = '%s', al_field3 = '%s', description = '%s', agent = '%s', custom_oid = '%s',
				oid = '%s', time_threshold = %d, max_alerts = %d, min_alerts = %d WHERE id_as = %d",
				$priority, $alert_type, $al_field1, $al_field2, $al_field3, $description, $source_ip, $custom_value,
				$oid, $time_threshold, $max_alerts, $min_alerts, $id_as);
		
		$result = db_process_sql ($sql);

		if (!$result) {
			echo '<h3 class="error">'.__('There was a problem updating the alert').'</h3>';
		} else {
			echo '<h3 class="suc">'.__('Successfully updated').'</h3>';
		}
	}

} else {
	ui_print_page_header (__('SNMP Console')." &raquo; ".__('Alert overview'), "images/computer_error.png", false, "snmp_alert", true);
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
	$max_alerts = $alert["max_alerts"];
	$min_alerts = $alert["min_alerts"];
	$priority = $alert["priority"];	
} elseif (isset ($_GET["update_alert"])) {
	// Variable init
	$id_as = -1;
	$source_ip = "";
	$alert_type = 1; //Event, e-mail
	$description = "";
	$oid = "";
	$custom_value = "";
	$time_threshold = 300;
	$al_field1 = "";
	$al_field2 = "";
	$al_field3 = "";
	$max_alerts = 1;
	$min_alerts = 0;
	$priority = 0;
}

// Header

// Alert Delete
// =============
if (isset ($_GET["delete_alert"])) { // Delete alert
	$alert_delete = (int) get_parameter_get ("delete_alert", 0);
	
	$result = db_process_sql_delete('talert_snmp', array('id_as' => $alert_delete));
	if ($result === false) {
		echo '<h3 class="error">'.__('There was a problem deleting the alert').'</h3>';
	}
	else {
		echo '<h3 class="suc">'.__('Successfully deleted').'</h3>';
	}
}

// Alert form
if (isset ($_GET["update_alert"])) {
	//the update_alert means the form should be displayed. If update_alert > 1 then an existing alert is updated
	echo '<form name="agente" method="post" action="index.php?sec=estado&sec2=godmode/snmpconsole/snmp_alert&submit='.$id_as.'">';
	echo '<table cellpadding="4" cellspacing="4" width="98%" class="databox_color">';
	
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
	
	// Description
	echo '<tr><td class="datos">'.__('Description').'</td><td class="datos">';
	html_print_input_text ("description", $description, '', 60);
	echo '</td></tr>';
	
	// OID
	echo '<tr id="tr-oid"><td class="datos2">'.__('OID').'</td><td class="datos2">';
	html_print_input_text ("oid", $oid, '', 50);
	echo '</td></tr>';

	// Custom
	echo '<tr id="tr-custom_value"><td class="datos"  valign="top">'.__('Custom data');
    echo ui_print_help_icon ("snmp_alert_custom", true);

    echo '</td><td class="datos">';
    html_print_textarea ("custom_value", $custom_value, 2, $custom_value, 'style="width:400px;"');

	echo '</td></tr>';

	// SNMP Agent
	echo '<tr id="tr-source_ip"><td class="datos2">'.__('SNMP Agent').' (IP)</td><td class="datos2">';
	html_print_input_text ("source_ip", $source_ip, '', 20);
	echo '</td></tr>';
		
	// Alert fields
	echo '<tr><td class="datos">'.__('Field #1 (Alias, name)');
    echo ui_print_help_icon ("snmp_alert_field1", true);
    echo '</td><td class="datos">';
	html_print_input_text ("al_field1", $al_field1, '', 60);
	echo '</td></tr>';
	
	echo '<tr><td class="datos2">'.__('Field #2 (Single Line)').'</td><td class="datos2">';
	html_print_input_text ("al_field2", $al_field2, '', 60);
	echo '</td></tr>';
	
	echo '<tr><td class="datos" valign="top">'.__('Field #3 (Full Text)').'<td class="datos">';
	html_print_textarea ("al_field3", $al_field3, 4, $al_field3, 'style="width:400px"');
	echo '</td></tr>';
	
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
	$fields[300] = human_time_description_raw (300);
	$fields[600] = human_time_description_raw (600);
	$fields[900] = human_time_description_raw (900);
	$fields[1800] = human_time_description_raw (1800);
	$fields[3600] = human_time_description_raw (3600);
	$fields[7200] = human_time_description_raw (7200);
	$fields[18000] = human_time_description_raw (18000);
	$fields[43200] = human_time_description_raw (43200);
	$fields[86400] = human_time_description_raw (86400);
	$fields[604800] = human_time_description_raw (604800);
	$fields[-1] = __('Other value');
	
	html_print_select ($fields, "time_threshold", $time_threshold, '', '', '0', false, false, false, '" style="margin-right:60px');
	echo '<div id="div-time_other" style="display:none">';
	html_print_input_text ("time_other", 0, '', 6);
	echo ' '.__('seconds').'</div></td></tr>';
		
	// Priority
	echo '<tr><td class="datos">'.__('Priority').'</td><td class="datos">';
	echo html_print_select (get_priorities (), "priority", $priority, '', '', '0', false, false, false);
	echo '</td></tr>';

	//Button
	echo '<tr><td></td><td align="right">';
	if ($id_as > 0) {
		html_print_submit_button (__('Update'), "submit", false, 'class="sub upd"', false);
	} else {
		html_print_submit_button (__('Create'), "submit", false, 'class="sub wand"', false);
	}
	
	// End table
	echo "</td></tr></table>";
} else {
	
	require_once ('include/functions_alerts.php');
	
	//Overview
	$result = db_get_all_rows_in_table ("talert_snmp");
	if ($result === false) {
		$result = array ();
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
		$data[0] = '<a href="index.php?sec=estado&sec2=godmode/snmpconsole/snmp_alert&update_alert='.$row["id_as"].'">' . alerts_get_alert_action_name ($row["id_alert"]) . '</a>';

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
		} else {
			$data[6] = __('Never');
		}
		
		$data[7] = '<a href="index.php?sec=estado&sec2=godmode/snmpconsole/snmp_alert&update_alert='.$row["id_as"].'">' .
				html_print_image("images/config.png", true, array("border" => '0', "alt" => __('Update'))) . '</a>' .
				'&nbsp;&nbsp;<a href="index.php?sec=estado&sec2=godmode/snmpconsole/snmp_alert&delete_alert='.$row["id_as"].'">'  .
				html_print_image("images/cross.png", true, array("border" => '0', "alt" => __('Delete'))) . '</a>';
		$idx = count ($table->data); //The current index of the table is 1 less than the count of table data so we count before adding to table->data
		array_push ($table->data, $data);
		
		$table->rowclass[$idx] = get_priority_class ($row["priority"]);
	}

	if (!empty ($table->data)) {
		html_print_table ($table);
	}
	
	unset ($table);	
	
	echo '<div style="text-align:right; width:98%; margin-top: 5px;">';
	echo '<form name="agente" method="post" action="index.php?sec=estado&sec2=godmode/snmpconsole/snmp_alert&update_alert=-1">';
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
}); 
</script>
