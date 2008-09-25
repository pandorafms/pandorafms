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
?>


<script language="JavaScript" type="text/javascript">
<!--
	function CheckAll () {
		for (var i = 0; i < document.eventtable.elements.length; i++) {
			var e = document.eventtable.elements[i];
			if (e.type == 'checkbox' && e.name != 'allbox')
				e.checked = 1;
		}
	}

	function OpConfirm (text, conf) {
		for (var i = 0; i < document.pageform.elements.length; i++) {
			var e = document.pageform.elements[i];
			if (e.type == 'checkbox' && e.name != 'allbox' && e.checked == 1) {
				if (conf) {
					return confirm (text);
				} else {
					return 1;
				}
			}
		}
		return false;
	}

	/* Function to hide/unhide a specific Div id */
	function toggleDiv (divid){
		if (document.getElementById(divid).style.display == 'none'){
			document.getElementById(divid).style.display = 'block';
		} else {
			document.getElementById(divid).style.display = 'none';
		}
	}
//-->
</script>

<?php
// Load global vars
require("include/config.php");

check_login ();

if (! give_acl ($config["id_user"], 0, "AR")) {
	audit_db ($config["id_user"], $REMOTE_ADDR, "ACL Violation",
		"Trying to access event viewer");
	require ("general/noaccess.php");
	return;
}

$accion = "";
// OPERATIONS
// Delete Event (only incident management access).
if (isset ($_GET["delete"])) {
	//safe input
	$id_evento = get_parameter_get ("delete");
	
	// Look for event_id following parameters: id_group.
	$id_group = gime_idgroup_from_idevent ($id_evento);
	if (give_acl ($config['id_user'], $id_group, "IM")) {
		$descr = return_event_description ($id_evento); //Get description before it gets deleted
		$sql = "DELETE FROM tevento WHERE id_evento =".$id_evento;
		$result = process_sql ($sql);
		
		if ($result !== false) {
			echo '<h3 class="suc">'.__('Event successfully deleted').'</h3>';
			audit_db ($config['id_user'], $REMOTE_ADDR,
				"Event deleted","Deleted event: ".$descr);
		} else {
			echo '<h3 class="error">'.__('Error deleting event').'</h3>';
		}
	} else {
		audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
			"Trying to delete event ID".$id_evento);
	}
}
	
// Check Event (only incident write access).
if (isset ($_GET["check"])) {
	$id_evento = get_parameter_get ("check");
	// Look for event_id following parameters: id_group.
	$id_group = gime_idgroup_from_idevent ($id_evento);
	if (give_acl ($config["id_user"], $id_group, "IW") ==1){
		$sql = "UPDATE tevento SET estado = 1, id_usuario = '".$config["id_user"]."' WHERE id_evento = ".$id_evento;
		$result = process_sql ($sql);
		if ($result !== false) {
			echo '<h3 class="suc">'.__('Event successfully validated').'</h3>';
			audit_db($config["id_user"],$REMOTE_ADDR, "Event validated","Validate event: ".return_event_description ($id_evento));
		} else {
			echo '<h3 class="error">'.__('Error validating event').'</h3>';
		}
		
	} else {
		audit_db ($config['id_user'],$REMOTE_ADDR, "ACL Violation",
			"Trying to checkout event ".return_event_description ($id_evento));
	}
}
	
// Mass-process DELETE
if (isset ($_POST["deletebt"])){
	$count=0;
	while ($count <= $config["block_size"]) {
		if (isset ($_POST["eventid".$count])) {
			$event_id = get_parameter_post ("eventid".$count);
			$descr = return_event_description ($event_id); //Get description before it gets deleted
			// Look for event_id following parameters: id_group.
			$id_group = gime_idgroup_from_idevent ($event_id);
			if (give_acl ($config['id_user'], $id_group, "IM")) {
				process_sql ("DELETE FROM tevento WHERE id_evento = ".$event_id);
				audit_db ($config['id_user'], $REMOTE_ADDR,
					"Event deleted","Deleted event: ".$descr);
			} else {
				audit_db ($config['id_user'], $REMOTE_ADDR,
					"ACL Violation","Trying to delete event: ".$descr);
			}
		}
		$count++;
	}
}

// Mass-process UPDATE
if (isset ($_POST["updatebt"])) {
	$count = 0;
	while ($count <= $config["block_size"]) {
		if (isset ($_POST["eventid".$count])) {
			$id_evento = get_parameter_post ("eventid".$count);
			$id_group = gime_idgroup_from_idevent($id_evento);
			if (give_acl ($config['id_user'], $id_group, "IW")) {
				$sql = "UPDATE tevento SET estado=1, id_usuario = '".$config['id_user']."' WHERE estado = 0 AND id_evento = ".$id_evento;
				$result = process_sql ($sql);
				audit_db ($config['id_user'], $REMOTE_ADDR,
					"Event validated","Validate event: ".return_event_description ($id_evento));
			} else {
				audit_db ($config['id_user'], $REMOTE_ADDR,
					"ACL Violation","Trying to checkout event ID".$id_evento);
			}
		}
		$count++;
	}
}

// ***********************************************************************
// Main code form / page
// ***********************************************************************


// Get data

$offset = (int) get_parameter ( "offset",0);
$ev_group = (int) get_parameter ("ev_group", 0); // group
$search = get_parameter ("search", ""); // free search
$event_type = get_parameter ("event_type", ''); // 0 all
$severity = (int) get_parameter ("severity", -1); // -1 all
$status = (int) get_parameter ("status", 0); // -1 all, 0 only red, 1 only green
$id_agent = (int) get_parameter ("id_agent", -1);
$id_event = (int) get_parameter ("id_event", -1);
$pagination = (int) get_parameter ("pagination", $config["block_size"]);
$config["block_size"] = $pagination;

$sql_post = "";
if ($ev_group > 1)
	$sql_post .= " AND id_grupo = $ev_group";
if ($status == 1)
	$sql_post .= " AND estado = 1";
if ($status == 0)
	$sql_post .= " AND estado = 0";
if ($search != "")
	$sql_post .= " AND evento LIKE '%$search%'";
if ($event_type != "")
	$sql_post .= " AND event_type = '$event_type'";
if ($severity != -1)
	$sql_post .= " AND criticity >= ".$severity;
if ($id_agent != -1)
	$sql_post .= " AND id_agente = ".$id_agent;
if ($id_event != -1)
	$sql_post .= " AND id_evento = ".$id_event;

$url = "index.php?sec=eventos&sec2=operation/events/events&search=$search&event_type=$event_type&severity=$severity&status=$status&ev_group=$ev_group&refr=60&id_agent=$id_agent&id_event=$id_event";

echo "<h2>".__('Events')." &gt; ".__('Main event view'). "&nbsp";

if ($config["pure"] == 1)
	echo "<a target='_top' href='$url&pure=0'><img src='images/monitor.png' title='".__('Normal screen')."'></a>";
else {
	// Fullscreen
	echo "<a target='_top' href='$url&pure=1'><img src='images/monitor.png' title='".__('Full screen')."'></a>";
}
echo "</h2>";

echo "<a href=\"javascript:;\" onmousedown=\"toggleDiv('event_control');\">";
echo "<b>".__('Event control filter')." ".'<img src="images/wand.png"></A></b>';

if ($config["pure"] == 1) {
	echo "<div id='event_control' style='display:none'>";
} else {
	echo "<div id='event_control' style='display:block'>"; //There is no value all to property display
}
// Table who separate control and graph
echo "<table width=99% cellpadding=0 cellspacing=2 border=0>";
echo "<tr><td width=500>";

// Table for filter controls
echo "<table width=500 cellpadding=4 cellspacing=4 class=databox>";
echo "<tr>";
echo "<form method='post' action='index.php?sec=eventos&sec2=operation/events/events&refr=60&id_agent=$id_agent&pure=".$config["pure"]."'>";

// Group combo
echo "<td>".__('Group')."</td>";
echo "<td>";
echo "<select name='ev_group' onChange='javascript:this.form.submit();' class='w130'>";
if ( $ev_group > 1 ){
	echo "<option value='".$ev_group."' selected>".dame_nombre_grupo ($ev_group)."</option>";
}
list_group ($config["id_user"]);
echo "</select></td>";

// Event type
echo "<td>".__('Event type')."</td>";
echo "<td>";
print_select (get_event_types (), 'event_type', $event_type, '', 'All', '');
echo "</td></tr><tr>";

// Severity
echo "<td>".__('Severity')."</td>";
echo "<td>";
print_select (get_priorities (), "severity", $severity, '', 'All', '-1');

// Status
echo "</td><td>".__('Event status')."</td>";
echo "<td>";
$fields = array ( -1 => __('All event'), 
		  1 => __('Only validated'),
		  0 => __('Only pending') 
		);
print_select ($fields, 'status', $status, 'javascript:this.form.submit();', '', '');
echo "</td></tr><tr>";

// Free search
echo "<td>".__('Free search')."</td><td>";
print_input_text ('search', $search, '', 15);

//Agent search
echo "</td><td>".__('Agent search')."</td><td>";
$sql = "SELECT DISTINCT(id_agente) FROM tevento WHERE 1=1 ".$sql_post;
$result = get_db_all_rows_sql ($sql);
if ($result === false)
	$result = array();
$agents = array(-1 => "All");

foreach ($result as $id_row) {
	$name_for_combo = substr(dame_nombre_agente ($id_row[0]),0,20);
	if ($name_for_combo != "")
		$agents[$id_row[0]] = $name_for_combo;
}

print_select ($agents, 'id_agent', $id_agent, 'javascript:this.form.submit();', '', '');
echo "</td></tr>";

// User selectable block size
echo '<tr><td>';
echo __('Block size for pagination');
echo '</td>';
$lpagination[25]=25;
$lpagination[50]=50;
$lpagination[100]=100;
$lpagination[200]=200;
$lpagination[500]=500;

echo "<td>";
print_select ($lpagination, "pagination", $pagination, '', 'Default', $config["block_size"]);
echo "</td>";

//The buttons
echo '<td colspan="2">';
print_submit_button (__('Update'), '', false, $attributes = 'class="sub upd"');

// CSV
echo '&nbsp;&nbsp;&nbsp;
	<a href="operation/events/export_csv.php?ev_group='.$ev_group.'&event_type='.$event_type.'&search='.$search.'&severity='.$severity.'&status='.$status.'&id_agent='.$id_agent.'">
	<img src="images/disk.png" title="Export to CSV file"></a>';
// Marquee
echo "&nbsp;<a target='_top' href='operation/events/events_marquee.php'><img src='images/heart.png' title='".__('Marquee display')."'></a>";
// RSS
echo '&nbsp;<a target="_top" href="operation/events/events_rss.php?ev_group='.$ev_group.'&event_type='.$event_type.'&search='.$search.'&severity='.$severity.'&status='.$status.'&id_agent='.$id_agent.'"><img src="images/transmit.png" title="'.__('RSS Events').'"></a>';


echo "</td></tr></table>";
echo "</form>";
echo "<td>";
echo '<img src="reporting/fgraph.php?tipo=group_events&width=250&height=180&url='.rawurlencode($sql_post).'" border="0">'; //Don't rely on browsers to do this correctly
echo "</td></tr></table>";
echo "</div>";

$sql = "SELECT * FROM tevento WHERE 1=1 ".$sql_post." ORDER BY timestamp DESC LIMIT ".$offset.",".$config["block_size"];
$result = get_db_all_rows_sql ($sql);
$sql = "SELECT COUNT(id_evento) FROM tevento WHERE 1=1 ".$sql_post;
$total_events = get_db_sql ($sql);

// Show pagination header
if ($total_events > 0){

	$offset = get_parameter ("offset",0);
	pagination ($total_events, $url."&pure=".$config["pure"], $offset);		
	// Show data.
		
	echo "<br>";
	echo "<br>";
	if ($config["pure"] == 0) {
		echo "<table cellpadding='4' cellspacing='4' width='765' class='databox'>";
	} else {
		echo "<table cellpadding='4' cellspacing='4' class='databox'>";
	}
	echo "<tr>";
	echo "<th class=f9>".__('St')."</th>";
	echo "<th class=f9>".__('Type')."</th>";
	echo "<th class=f9>".__('Event name')."</th>";
	echo "<th class=f9>".__('Agent name')."</th>";
	echo "<th class=f9>".__('Source')."</th>";
	echo "<th class=f9>".__('Group')."</th>";
	echo "<th class=f9>".__('User ID')."</th>";
	echo "<th class=f9>".__('Timestamp')."</th>";
	echo "<th class=f9>".__('Action')."</th>";
	echo "<th class='p10'>";
	echo "<label for='checkbox' class='p21'>".__('All')." </label>";
	echo '<input type="checkbox" class="chk" name="allbox" onclick="CheckAll();"></th>';
	echo "<form name='eventtable' method='POST' action='$url&pure=".$config["pure"]."'>";
	$id_evento = 0;
	
	$offset_counter=0;
	// Make query for data (all data, not only distinct).
	foreach ($result as $row2) {
		$id_grupo = $row2["id_grupo"];
		if (give_acl($config["id_user"], $id_grupo, "AR") == 1){ // Only incident read access to view data !
			$id_group = $row2["id_grupo"];

		switch ($row2["criticity"]) {
		case 0: 
			$tdclass = "datos_blue";
			break;
		case 1: 
			$tdclass = "datos_grey";
			break;
		case 2: 
			$tdclass = "datos_green";
			break;
		case 3: 
			$tdclass = "datos_yellow";
			break;
		case 4: 
			$tdclass = "datos_red";
			break;
		default:
			$tdclass = "datos_grey";
		}
		$criticity_label = return_priority ($row2["criticity"]);
		// Colored box 
		echo "<tr><td class='$tdclass' title='$criticity_label' align='center'>";
		if ($row2["estado"] == 0)
			echo "<img src='images/pixel_red.png' width=20 height=35>";
		else
			echo "<img src='images/pixel_green.png' width=20 height=35>";

		// Event type
		echo "<td class='".$tdclass."' title='".$row2["event_type"]."'>";
		switch ($row2["event_type"]){
		case "unknown": 
			echo "<img src='images/err.png'>";
			break;
		case "alert_recovered": 
			echo "<img src='images/error.png'>";
			break;
		case "alert_manual_validation": 
			echo "<img src='images/eye.png'>";
			break;
		case "monitor_up":
			echo "<img src='images/lightbulb.png'>";
			break;
		case "monitor_down":
			echo "<img src='images/lightbulb_off.png'>";
			break;
		case "alert_fired":
			echo "<img src='images/bell.png'>";
			break;
		case "system";
			echo "<img src='images/cog.png'>";
			break;
		case "recon_host_detected";
			echo "<img src='images/network.png'>";
			break;
		case "new_agent";
			echo "<img src='images/wand.png'>";
			break;
		}
 
		// Event description
		$event_title = safe_input ($row2["evento"]);
		echo "<td class='".$tdclass."f9' title='$event_title'>";
		echo substr($row2["evento"],0,45);
		if (strlen($row2["evento"]) > 45)
			echo "..";
		if ($row2["id_agente"] > 0) {
			// Agent name
			$agent_name = dame_nombre_agente ($row2["id_agente"]);
			echo "<td class='".$tdclass."f9' title='$agent_name'><a href='$url&pure=".$config["pure"]."&id_agent=".$row2["id_agente"]."'><b>";
			echo substr($agent_name, 0, 14);
			if (strlen($agent_name) > 14)
				echo "..";
			echo "</b></a>";
			
			// Module name / Alert
			echo "<td class='$tdclass'>";
			if ($row2["id_agentmodule"] != 0)
				echo "<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=".$row2["id_agente"]."&tab=data'><img src='images/bricks.png' border=0></A>";
			echo "&nbsp;";
			if ($row2["id_alert_am"] != 0)
				echo "<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=".$row2["id_agente"]."&tab=alert'><img src='images/bell.png' border=0></a>";

			// Group icon
			$group_name = (string) get_db_value ('nombre', 'tgrupo', 'id_grupo', $id_group);
			echo "<td class='$tdclass' align='center'><img src='images/groups_small/".show_icon_group($id_group).".png' title='$group_name' class='bot'></td>";

			// for System or SNMP generated alerts
			} else { 
				if ($row2["event_type"] == "system") {
					echo "<td class='$tdclass' colspan=3>".__('System');
				} else {
					echo "<td class='$tdclass' colspan=3>".__('Alert')."SNMP";
				}
			}

			// User who validated event
			echo "<td class='$tdclass'>";
			if ($row2["estado"] <> 0)
				echo "<a href='index.php?sec=usuario&sec2=operation/users/user_edit&ver=".$row2["id_usuario"]."'>".substr($row2["id_usuario"],0,8)."<a href='#' class='tip'> <span>".dame_nombre_real($row2["id_usuario"])."</span></a></a>";
			
			// Timestamp
			echo "<td class='".$tdclass."f9' title='".$row2["timestamp"]."'>";
			echo human_time_comparation ($row2["timestamp"]);
			echo "</td>";
			// Several options grouped here
			echo "<td class='$tdclass' align='right'>";
			// Validate event
			if (($row2["estado"] == 0) and (give_acl ($config["id_user"], $id_group,"IW") ==1))
				echo "<a href='$url&check=".$row2["id_evento"]."&pure=".$config["pure"]."'>
				<img src='images/ok.png' border='0'></a> ";
			// Delete event
			if (give_acl ($config["id_user"], $id_group,"IM") ==1)
				echo "<a href='$url&delete=".$row2["id_evento"]."&pure=".$config["pure"]."'>
				<img src='images/cross.png' border=0></a> ";
			// Create incident from this event			
			if (give_acl ($config["id_user"], $id_group,"IW") == 1)
				echo "<a href='index.php?sec=incidencias&sec2=operation/incidents/incident_detail&insert_form&from_event=".$row2["id_evento"]."'><img src='images/page_lightning.png' border=0></a>";
			echo "</td>";
			// Checbox				
			echo "<td class='$tdclass' align='center'>";
			echo "<input type='checkbox' class='chk' name='eventid".$offset_counter."' 
			value='".$row2["id_evento"]."'>";
			echo "</td></tr>";
		}
		$offset_counter++;
	}
	echo "</table>";
	echo "<table width='750'><tr><td align='right'>";
	
	echo "<input class='sub ok' type='submit' name='updatebt' value='".__('Validate')."'> ";
	if (give_acl ($config["id_user"], 0,"IM") ==1){
		echo "<input class='sub delete' type='submit' name='deletebt' value='".__('Delete')."'>";
	}
	echo "</form></table>";
	echo "<table>";
	echo "<tr>";
	echo "<td rowspan='4' class='f9' style='padding-left: 30px; line-height: 17px; vertical-align: top;'>";
	echo "<h3>".__('Status')."</h3>";
	echo "<img src='images/dot_green.png'> - ".__('Validated event');
	echo "<br>";
	echo "<img src='images/dot_red.png'> - ".__('Not validated event');
	echo "</td>";
	echo "<td rowspan='4' class='f9' style='padding-left: 30px; line-height: 17px; vertical-align: top;'>";
	echo "<h3>".__('Action')."</h3>";
	echo "<img src='images/ok.png'> - ".__('Validate event');
	echo "<br>";
	echo "<img src='images/cross.png'> - ".__('Delete event');
	echo "<br>";
	echo "<img src='images/page_lightning.png'> - ".__('Create incident');
	echo "</td></tr></table>";
} // no events to show
?>
