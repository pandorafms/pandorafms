<?php
// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnológicas, http://www.artica.es
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
	$id_evento = $_GET["delete"];
	// Look for event_id following parameters: id_group.
	$id_group = gime_idgroup_from_idevent($id_evento);
	if (give_acl ($config['id_user'], $id_group, "IM")) {
		$sql2="DELETE FROM tevento WHERE id_evento =".$id_evento;
		$result2=mysql_query($sql2);
		if ($result) {
			echo "<h3 class='suc'>".__('delete_event_ok')."</h3>";
			audit_db ($config['id_user'], $REMOTE_ADDR,
				"Event deleted","Deleted event: ".return_event_description ($id_evento));
		}
	} else {
		audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
			"Trying to delete event ID".$id_evento);
	}
}
	
// Check Event (only incident write access).
if (isset ($_GET["check"])) {
	$id_evento = $_GET["check"];
	// Look for event_id following parameters: id_group.
	$id_group = gime_idgroup_from_idevent($id_evento);
	if (give_acl($config["id_user"], $id_group, "IW") ==1){
		$sql2="UPDATE tevento SET estado = 1, id_usuario = '".$config["id_user"]."' WHERE id_evento = ".$id_evento;
		$result2=mysql_query($sql2);
		if ($result2) {
			echo "<h3 class='suc'>".__('validate_event_ok')."</h3>";
			audit_db($config["id_user"],$REMOTE_ADDR, "Event validated","Validate event: ".return_event_description ($id_evento));
		} else {
			echo "<h3 class='error'>".__('validate_event_failed')."</h3>";
		}
		
	} else {
		audit_db ($config['id_user'],$REMOTE_ADDR, "ACL Violation",
			"Trying to checkout event ".return_event_description ($id_evento));
	}
}
	
// Mass-process DELETE
if (isset ($_POST["deletebt"])){
	$count=0;
	while ($count <= $config["block_size"]){
		if (isset($_POST["eventid".$count])){
			$event_id = $_POST["eventid".$count];
			// Look for event_id following parameters: id_group.
			$id_group = gime_idgroup_from_idevent($event_id);
			if (give_acl ($config['id_user'], $id_group, "IM")) {
				process_sql ("DELETE FROM tevento WHERE id_evento = ".$event_id);
				audit_db ($config['id_user'], $REMOTE_ADDR,
					"Event deleted","Deleted event: ".return_event_description ($event_id));
			} else {
				audit_db ($config['id_user'], $REMOTE_ADDR,
					"ACL Violation","Trying to delete event ".return_event_description ($event_id));
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
			$id_evento = $_POST["eventid".$count];
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

$offset = get_parameter ( "offset",0);
$ev_group = get_parameter ("ev_group", 0); // group
$search = get_parameter ("search", ""); // free search
$event_type = get_parameter ("event_type", ''); // 0 all
$severity = get_parameter ("severity", -1); // -1 all
$status = get_parameter ("status", 0); // -1 all, 0 only red, 1 only green
$id_agent = get_parameter ("id_agent", -1);

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
	$sql_post .= " AND criticity >= $severity";
if ($id_agent != -1)
	$sql_post .= " AND id_agente = $id_agent";
$url = "index.php?sec=eventos&sec2=operation/events/events&search=$search&event_type=$event_type&severity=$severity&status=$status&ev_group=$ev_group&refr=60&id_agent=$id_agent";

echo "<h2>".__('events')." &gt; ".__('event_main_view'). "&nbsp";

if ($config["pure"] == 1)
	echo "<a target='_top' href='$url&pure=0'><img src='images/monitor.png' title='".__('Normal screen')."'></a>";
else {
	// Fullscreen
	echo "<a target='_top' href='$url&pure=1'><img src='images/monitor.png' title='".__('Full screen')."'></a>";
}
echo "</h2>";

echo "<a href=\"javascript:;\" onmousedown=\"toggleDiv('event_control');\">";
echo "<b>".__('Event control filter')." ".'<img src="images/wand.png"></A></b>';

if ($config["pure"] == 1)
	echo "<div id='event_control' style='display:none'>";
else
	echo "<div id='event_control' style='display:all'>";

// Table who separate control and graph
echo "<table width=99% cellpadding=0 cellspacing=2 border=0>";
echo "<tr><td width=500>";

// Table for filter controls
echo "<table width=500 cellpadding=4 cellspacing=4 class=databox>";
echo "<tr>";
echo "<form method='post' action='index.php?sec=eventos&sec2=operation/events/events&refr=60&id_agent=$id_agent&pure=".$config["pure"]."'>";

// Group combo
echo "<td>".__('group')."</td>";
echo "<td>";
echo "<select name='ev_group' onChange='javascript:this.form.submit();' class='w130'>";
if ( $ev_group > 1 ){
	echo "<option value='".$ev_group."'>".dame_nombre_grupo($ev_group)."</option>";
}
echo "<option value=1>".dame_nombre_grupo(1)."</option>";
list_group ($config["id_user"]);
echo "</select></td>";

// Event type
echo "<td>".__('Event type')."</td>";
echo "<td>";
echo print_select (get_event_types (), 'event_type', $event_type, '', 'all', "");
echo "<tr>";

// Severity
echo "<td>".__('Severity')."</td>";
echo "<td>";

print_select (get_priorities (), "severity", $severity, '', 'all', '-1');

// Status
echo "<td>".__('Event status')."</td>";
echo "<td>";
echo "<select name='status' onChange='javascript:this.form.submit();'>";
if ($status == 1){
	echo "<option value=1>". __('Only validated');
	echo "<option value=-1>". __('All event');
	echo "<option value=0>". __('Only pending');
} elseif ($status == 0) {
	echo "<option value=0>". __('Only pending');
	echo "<option value=1>". __('Only validated');
	echo "<option value=-1>". __('All event');
} elseif ($status == -1) {
	echo "<option value=-1>". __('All event');
	echo "<option value=0>". __('Only pending');
	echo "<option value=1>". __('Only validated');
}
echo "</select></td>";
echo "<tr>";

// Free search
echo "<td>".__('Free search')."</td>";
echo "<td>";
echo "<input type='text' size=15 value='".$search."' name='search'>";
echo "<td colspan=2>";
echo "<input type=submit value='".__('Update')."' class='sub upd'>";
echo "&nbsp;&nbsp;&nbsp;";

// CSV
echo "<a href='operation/events/export_csv.php?ev_group=$ev_group&event_type=$event_type&search=$search&severity=$severity&status=$status&id_agent=$id_agent'>";
echo "<img src='images/disk.png' title='Export to CSV file'></A>";
// Marquee
echo "&nbsp;<a target='_top' href='operation/events/events_marquee.php'><img src='images/heart.png' title='".__('Marquee display')."'></a>";
// RSS
echo "&nbsp;<a target='_top' href='operation/events/events_rss.php'><img src='images/transmit.png' title='".__('RSS Events')."'></a>";


echo "</table>";
echo "</form>";
echo "<td>";
echo '<img src="reporting/fgraph.php?tipo=group_events&width=250&height=180&url='.$sql_post.'" border=0>';
echo "</table>";
echo "</div>";

$sql2 = "SELECT * FROM tevento WHERE 1=1 ";
$sql2 .= $sql_post . " ORDER BY timestamp DESC LIMIT $offset, ".$config["block_size"];
$sql3 = "SELECT COUNT(id_evento) FROM tevento WHERE 1=1 ";
$sql3 .= $sql_post;

$result3=mysql_query($sql3);
$row3=mysql_fetch_array($result3);
$total_events = $row3[0];

// Show pagination header
if ($total_events > 0){

	$offset = get_parameter ( "offset",0);
	pagination ($total_events, $url."&pure=".$config["pure"], $offset);		
	// Show data.
		
	echo "<br>";
	echo "<br>";
	if ($config["pure"] == 0)
		echo "<table cellpadding='4' cellspacing='4' width='765' class='databox'>";
	else
		echo "<table cellpadding='4' cellspacing='4' class='databox'>";
	echo "<tr>";
	echo "<th class=f9>".__('St')."</th>";
	echo "<th class=f9>".__('Type')."</th>";
	echo "<th class=f9>".__('event_name')."</th>";
	echo "<th class=f9>".__('agent_name')."</th>";
	echo "<th class=f9>".__('source')."</th>";
	echo "<th class=f9>".__('group')."</th>";
	echo "<th class=f9>".__('id_user')."</th>";
	echo "<th class=f9>".__('timestamp')."</th>";
	echo "<th class=f9>".__('action')."</th>";
	echo "<th class='p10'>";
	echo "<label for='checkbox' class='p21'>".__('all')." </label>";
	echo '<input type="checkbox" class="chk" name="allbox" onclick="CheckAll();"></th>';
	echo "<form name='eventtable' method='POST' action='$url&pure=".$config["pure"]."'>";
	$id_evento = 0;
	
	$offset_counter=0;
	// Make query for data (all data, not only distinct).
	$result2=mysql_query($sql2);
	while ($row2=mysql_fetch_array($result2)){
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
			$agent_name = dame_nombre_agente($row2["id_agente"]);
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
					echo "<td class='$tdclass' colspan=3>".__('alert')."SNMP";
				}
			}

			// User who validated event
			echo "<td class='$tdclass'>";
			if ($row2["estado"] <> 0)
				echo "<a href='index.php?sec=usuario&sec2=operation/users/user_edit&ver=".$row2["id_usuario"]."'>".substr($row2["id_usuario"],0,8)."<a href='#' class='tip'> <span>".dame_nombre_real($row2["id_usuario"])."</span></a></a>";
			
			// Timestamp
			echo "<td class='".$tdclass."f9' title='".$row2["timestamp"]."'>";
			echo human_time_comparation($row2["timestamp"]);
			
			// Several options grouped here
			echo "<td class='$tdclass' align='right'>";
			// Validate event
			if (($row2["estado"] == 0) and (give_acl($config["id_user"], $id_group,"IW") ==1))
				echo "<a href='$url&check=".$row2["id_evento"]."&pure=".$config["pure"]."'><img src='images/ok.png' border='0'></a> ";
			// Delete event
			if (give_acl($config["id_user"], $id_group,"IM") ==1)
				echo "<a href='$url&delete=".$row2["id_evento"]."&pure=".$config["pure"]."'><img src='images/cross.png' border=0></a> ";
			// Create incident from this event			
			if (give_acl($config["id_user"], $id_group,"IW") == 1)
				echo "<a href='index.php?sec=incidencias&sec2=operation/incidents/incident_detail&insert_form&from_event=".$row2["id_evento"]."'><img src='images/page_lightning.png' border=0></a>";
			// Checbox					
			echo "<td class='$tdclass' align='center'>";
			echo "<input type='checkbox' class='chk' name='eventid".$offset_counter."' value='".$row2["id_evento"]."'>";
			echo "</td></tr>";
		}
		$offset_counter++;
	}
	echo "</table>";
	echo "<table width='750'><tr><td align='right'>";
	
	echo "<input class='sub ok' type='submit' name='updatebt' value='".__('validate')."'> ";
	if (give_acl($config["id_user"], 0,"IM") ==1){
		echo "<input class='sub delete' type='submit' name='deletebt' value='".__('delete')."'>";
	}
	echo "</form></table>";
	echo "<table>";
	echo "<tr>";
	echo "<td rowspan='4' class='f9' style='padding-left: 30px; line-height: 17px; vertical-align: top;'>";
	echo "<h3>".__('status')."</h3>";
	echo "<img src='images/dot_green.png'> - ".__('validated_event');
	echo "<br>";
	echo "<img src='images/dot_red.png'> - ".__('not_validated_event');
	echo "</td>";
	echo "<td rowspan='4' class='f9' style='padding-left: 30px; line-height: 17px; vertical-align: top;'>";
	echo "<h3>".__('action')."</h3>";
	echo "<img src='images/ok.png'> - ".__('validate_event');
	echo "<br>";
	echo "<img src='images/cross.png'> - ".__('delete_event');
	echo "<br>";
	echo "<img src='images/page_lightning.png'> - ".__('create_incident');
	echo "</td></tr></table>";
} // no events to show
?>
