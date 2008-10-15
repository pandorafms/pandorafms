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
require ("include/config.php");

if (! give_acl ($config['id_user'], 0, "LW")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access SNMP Alert Management");
	require ("general/noaccess.php");
	return;
}

// Alert Delete
// =============
if (isset ($_GET["delete_alert"])) { // Delete alert
	$alert_delete = (int) get_parameter_get ("delete_alert", 0);
	$sql = sprintf ("DELETE FROM talert_snmp WHERE id_as = %d", $alert_delete);
	$result = process_sql ($sql);
	if ($result === false) {
		echo '<h3 class="error">'.__('There was a problem deleting the alert').'</h3>';
	} else {
		echo '<h3 class="suc">'.__('Alert successfully deleted').'</h3>';
	}
}

// Form submitted
// =============
if (isset ($_GET["submit"])) {
	$id_as = (int) get_parameter_get ("submit", -1);
	$source_ip = (string) get_parameter_post ("source_ip");
	$alert_type = (int) get_parameter_post ("alert_type"); //Event, e-mail
	$alert_trigger = (int) get_parameter_post ("alert_trigger"); //OID, Custom Value
	$description = (string) get_parameter_post ("description");
	$oid = (string) get_parameter_post ("oid");
	$custom_value = (string) get_parameter_post ("custom_value");
	$time_threshold = (int) get_parameter_post ("time_threshold", 300);
	$time_other = (int) get_parameter_post ("time_other", -1);
	$al_field1 = (string) get_parameter_post ("al_field1");
	$al_field2 = (string) get_parameter_post ("al_field2");
	$al_field3 = (string) get_parameter_post ("al_field3");
	$max_alerts = (int) get_parameter_post ("max_alerts", 1);
	$min_alerts = (int) get_parameter_post ("min_alerts", 1);
	$priority = (int) get_parameter_post ("priority", 0);
	
	if ($time_threshold == -1) {
		$time_threshold = $time_other;
	}
	
	if ($id_as < 1) {
		$sql = sprintf ("INSERT INTO talert_snmp 
			(id_alert, al_field1, al_field2, al_field3, description, alert_type, agent, custom_oid, oid, time_threshold, max_alerts, min_alerts, priority)
			VALUES
			(%d, '%s', '%s', '%s', '%s', %d, '%s', '%s', '%s', %d, %d, %d, %d)",
			$alert_type, $al_field1, $al_field2, $al_field3, $description, $alert_trigger, $source_ip, $custom_value, $oid, $time_threshold, $max_alerts, $min_alerts, $priority);
		
		//$result = process_sql ($sql);

		if ($result === false) {
			echo '<h3 class="error">'.__('There was a problem creating the alert').'</h3>';
		} else {
			echo '<h3 class="suc">'.__('Alert successfully created').'</h3>';
		}
		
	} else {
		$sql = sprintf ("UPDATE talert_snmp SET
			priority = %d, id_alert = %d, al_field1 = '%s', al_field2 = '%s', al_field3 = '%s', description = '%s', alert_type = %d, agent = '%s', custom_oid = '%s',
			oid = '%s', time_threshold = %d, max_alerts = %d, min_alerts = %d WHERE id_as = %d",
			$priority, $alert_type, $al_field1, $al_field2, $al_field3, $description, $alert_trigger, $source_ip, $custom_value,
			$oid, $time_threshold, $max_alerts, $min_alerts, $id_as);
		
		$result = process_sql ($sql);

		if ($result === false) {
			echo '<h3 class="error">'.__('There was a problem updating the alert').'</h3>';
		} else {
			echo '<h3 class="suc">'.__('Alert successfully updated').'</h3>';
		}
	}
}

// From variable init
// ==================
if (isset ($_GET["update_alert"]) && $_GET["update_alert"]) {
	$id_as = (int) get_parameter_get ("update_alert", -1);
	$alert = get_db_row ("talert_snmp", "id_as", $id_as);
	$id_as = $alert["id_as"];
	$source_ip = $alert["agent"];
	$alert_type = $alert["id_alert"];
	$alert_trigger = $alert["alert_type"];
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
	$alert_trigger = 0; //OID, Custom Value
	$description = "";
	$oid = "";
	$custom_value = "";
	$time_threshold = 300;
	$al_field1 = "";
	$al_field2 = "";
	$al_field3 = "";
	$max_alerts = 1;
	$min_alerts = 1;
	$priority = 0;
}

// Alert form
if (isset ($_GET["update_alert"])) { //the update_alert means the form should be displayed. If update_alert > 1 then an existing alert is updated
	if ($id_as > 1) {
		echo "<h2>Pandora SNMP &gt; ".__('Update alert')."</h2>";
	} else {
		echo "<h2>Pandora SNMP &gt; ".__('Create alert')."</h2>";
	}
	echo '<form name="agente" method="post" action="index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_alert&submit='.$id_as.'">';
	echo '<table cellpadding="4" cellspacing="4" width="650" class="databox_color">';
	
	// Alert type (e-mail, event etc.)
	echo '<tr><td class="datos">'.__('Alert type').'</td><td class="datos">';
	
	$fields = array ();
	$result = get_db_all_rows_in_table ("talerta", "nombre");
	if ($result === false) {
		$result = array ();
	}

	foreach ($result as $row) {
		$fields[$row["id_alerta"]] = $row["nombre"];
	}
	
	print_select ($fields, "alert_type", $alert_type, '', '', '0', false, false, false);
	echo '</td></tr>';
	
	// Alert trigger (OID, custom_value)
	echo '<tr><td class="datos2">'.__('Alert trigger').'</td><td class="datos2">';
	
	$fields = array ();
	$fields[0] = "OID";
	$fields[1] = "Custom Value/OID";
	$fields[2] = "SNMP Agent";
	
	print_select ($fields, "alert_trigger", $alert_trigger);
	echo '</td></tr>';
	
	// Description
	echo '<tr><td class="datos">'.__('Description').'</td><td class="datos">';
	print_input_text ("description", $description, '', 60);
	echo '</td></tr>';
	
	// OID
	echo '<tr id="tr-oid"><td class="datos2">'.__('OID').'</td><td class="datos2">';
	print_input_text ("oid", $oid, '', 30);
	echo '</td></tr>';

	// OID Custom
	echo '<tr id="tr-custom_value" style="display:none"><td class="datos">'.__('Custom Value')."/".__("OID").'</td><td class="datos">';
	print_input_text ("custom_value", $custom_value, '', 30);
	echo '</td></tr>';

	// SNMP Agent
	echo '<tr id="tr-source_ip" style="display:none"><td class="datos2">'.__('SNMP Agent').' (IP)</td><td class="datos2">';
	print_input_text ("source_ip", $source_ip, '', 30);
	echo '</td></tr>';
		
	// Alert fields
	echo '<tr><td class="datos">'.__('Field #1 (Alias, name)').'</td><td class="datos">';
	print_input_text ("al_field1", $al_field1, '', 30);
	echo '</td></tr>';
	
	echo '<tr><td class="datos2">'.__('Field #2 (Single Line)').'</td><td class="datos2">';
	print_input_text ("al_field2", $al_field2, '', 30);
	echo '</td></tr>';
	
	echo '<tr><td class="datos" valign="top">'.__('Field #3 (Full Text)').'<td class="datos">';
	print_textarea ("al_field3", $al_field3, 4, $al_field3, 'style="width:400px"');
	echo '</td></tr>';
	
	// Max / Min alerts
	echo '<tr><td class="datos2">'.__('Min. number of alerts').'</td><td class="datos2">';
	print_input_text ("min_alerts", $min_alerts, '', 3);
	
	echo '</td></tr><tr><td class="datos">'.__('Max. number of alerts').'</td><td class="datos">';
	print_input_text ("max_alerts", $max_alerts, '', 3);
	echo '</td></tr>';

	// Time Threshold
	echo '<tr><td class="datos2">'.__('Time threshold').'</td><td class="datos2">';
	
	$fields = array ();
	$fields[$time_threshold] = human_time_description ($time_threshold);
	$fields[300] = human_time_description (300);
	$fields[600] = human_time_description (600);
	$fields[900] = human_time_description (900);
	$fields[1800] = human_time_description (1800);
	$fields[3600] = human_time_description (3600);
	$fields[7200] = human_time_description (7200);
	$fields[18000] = human_time_description (18000);
	$fields[43200] = human_time_description (43200);
	$fields[86400] = human_time_description (86400);
	$fields[604800] = human_time_description (604800);
	$fields[-1] = __('Other value');
	
	print_select ($fields, "time_threshold", $time_threshold, '', '', '0', false, false, false, '" style="margin-right:60px');
	echo '<div id="div-time_other" style="display:none">';
	print_input_text ("time_other", 0, '', 6);
	echo ' '.__('seconds').'</div></td></tr>';
		
	// Priority
	echo '<tr><td class="datos">'.__('Priority').'</td><td class="datos">';
	echo print_select (get_priorities (), "priority", $priority, '', '', '0', false, false, false);
	echo '</td></tr>';

	//Button
	echo '<tr><td></td><td align="right">';
	if ($id_as > 0) {
		print_submit_button (__('Update'), "submit", false, 'class="sub upd"', false);
	} else {
		print_submit_button (__('Create'), "submit", false, 'class="sub next"', false);
	}
	
	// End table
	echo "</td></tr></table>";
} else {
	echo "<h2>Pandora SNMP &gt; ".__('Alert Overview')."</h2>";
	//Overview
	$result = get_db_all_rows_in_table ("talert_snmp");
	if ($result === false) {
		$result = array ();
		echo "<div class='nf'>".__('There are no SNMP alerts')."</div>";
	}
	
	$table->data = array ();
	$table->head = array ();
	$table->size = array ();
	$table->cellpadding = 4;
	$table->cellspacing = 4;
	$table->width = 750;
	$table->class= "databox";
	$table->align = array ();

	$table->head[0] = __('Alert type');
	
	$table->head[1] = __('Alert trigger');
	$table->align[1] = 'center';
	
	$table->head[2] = __('SNMP Agent');
	$table->size[2] = 75;
	$table->align[2] = 'center';

	$table->head[3] = __('OID');
	$table->align[3] = 'center';
	
	$table->head[4] = __('Custom Value/OID');
	$table->align[4] = 'center';
	
	$table->head[5] = __('Description');
	
	$table->head[6] = __('Times fired');
	$table->align[6] = 'center';
	
	$table->head[7] = __('Last fired');
	$table->align[7] = 'center';

	$table->head[8] = __('Action');
	$table->size[8] = 50;
	$table->align[8] = 'right';

	foreach ($result as $row) {
		$data = array ();
		$data[0] = dame_nombre_alerta ($row["id_alert"]);
		$data[1] = __('N/A');
		$data[2] = __('N/A');
		$data[3] = __('N/A');
		$data[4] = __('N/A');
									
		switch ($row["alert_type"]) {
		case 0:
			$data[1] = __('OID');
			$data[3] = $row["oid"];
			break;
		case 1:
			$data[1] = __('Custom Value/OID');
			$data[4] = $row["custom_oid"];
			break;
		case 2:
			$data[1] = __('SNMP Agent');
			$data[2] = $row["agent"];
			break;
		}
			
		$data[5] = $row["description"];
		$data[6] = $row["times_fired"];
		
		if ($row["last_fired"] != "0000-00-00 00:00:00") {
			$data[7] = $row["last_fired"];
		} else {
			$data[7] = __('Never');
		}
		
		$data[8] = '<a href="index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_alert&delete_alert='.$row["id_as"].'">
				<img src="images/cross.png" border="0" alt="'.__('Delete').'"></a>&nbsp;
				<a href="index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_alert&update_alert='.$row["id_as"].'">
				<img src="images/config.png" border="0" alt="'.__('Update').'"></a>';
		array_push ($table->data, $data);			
	}

	if (!empty ($table->data)) {
		print_table ($table);
	}
	
	unset ($table);	
	
	echo '<div style="text-align:right; width:740px">';
	echo '<form name="agente" method="post" action="index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_alert&update_alert=-1">';
	print_submit_button (__('Create'), "add_alert", false, 'class="sub next"');
	echo "</form></div>";
		
}
?>
<script type="text/javascript" src="include/javascript/jquery.js"></script>

<script language="javascript" type="text/javascript">
function time_changed () {
	var time = this.value;
	if (time == -1) {
		$('#time_threshold').fadeOut ('normal', function () {
			$('#div-time_other').fadeIn ('normal');
		});
	}
}

function trigger_changed () {
	var trigger = this.value;
	if (trigger == 0) {
		$('#tr-custom_value').fadeOut ('fast');
		$('#tr-source_ip').fadeOut ('fast');
		$('#tr-oid').fadeIn ('slow');
		return;
	} 
	if (trigger == 1) {
		$('#tr-oid').fadeOut ('fast');
		$('#tr-source_ip').fadeOut ('fast');
		$('#tr-custom_value').fadeIn ('slow');
		return;
	} 
	if (trigger == 2) {
		$('#tr-oid').fadeOut ('fast');
		$('#tr-custom_value').fadeOut ('fast');
		$('#tr-source_ip').fadeIn ('slow');
		return;
	}
}

$(document).ready (function () {
	$('#time_threshold').change (time_changed);
	$('#alert_trigger').change (trigger_changed);
}); 
</script>
