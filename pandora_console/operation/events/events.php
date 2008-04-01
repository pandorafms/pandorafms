<?php
// Pandora FMS - the Free Monitoring System
// ========================================
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
	function CheckAll()
	{
	  for (var i=0;i<document.eventtable.elements.length;i++)
	  {
		 var e = document.eventtable.elements[i];
		 if (e.type == 'checkbox' && e.name != 'allbox')
		e.checked = 1;
	  }
	}
	function OpConfirm(text, conf)
	{
	  for (var i=0;i<document.pageform.elements.length;i++)
	  {
		 var e = document.pageform.elements[i];
		 if (e.type == 'checkbox' && e.name != 'allbox' && e.checked == 1 ) {
			if (conf) {
				return confirm(text);
			} else {
				return 1;
			}
		 }
	  }
	  return false;
	}
//-->
</script>

<?php
// Load global vars
require("include/config.php");

if (comprueba_login() != 0) {
 	audit_db("Noauth",$REMOTE_ADDR, "No authenticated acces","Trying to access event viewer");
	no_permission();
}

$accion = "";
if (give_acl($id_user, 0, "AR")!=1) {
	audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access event viewer");
	no_permission();
}
	
// OPERATIONS
// Delete Event (only incident management access).
if (isset($_GET["delete"])){
	$id_evento = $_GET["delete"];
	// Look for event_id following parameters: id_group.
	$id_group = gime_idgroup_from_idevent($id_evento);
	if (give_acl($id_user, $id_group, "IM") ==1){
		$sql2="DELETE FROM tevento WHERE id_evento =".$id_evento;
		$result2=mysql_query($sql2);
		if ($result) {
			echo "<h3 class='suc'>".$lang_label["delete_event_ok"]."</h3>";
			audit_db($id_user,$REMOTE_ADDR, "Event deleted","Deleted event: ".return_event_description ($id_evento));
		}
	} else {
		audit_db($id_user,$REMOTE_ADDR, "ACL Violation",
		"Trying to delete event ID".$id_evento);
	}
}
	
// Check Event (only incident write access).
if (isset($_GET["check"])){
	$id_evento = $_GET["check"];
	// Look for event_id following parameters: id_group.
	$id_group = gime_idgroup_from_idevent($id_evento);
	if (give_acl($id_user, $id_group, "IW") ==1){
		$sql2="UPDATE tevento SET estado = 1, id_usuario = '".$id_user."' WHERE id_evento = ".$id_evento;
		$result2=mysql_query($sql2);
		if ($result2) {
			echo "<h3 class='suc'>".$lang_label["validate_event_ok"]."</h3>";
			audit_db($id_user,$REMOTE_ADDR, "Event validated","Validate event: ".return_event_description ($id_evento));
		} else {
			echo "<h3 class='error'>".$lang_label["validate_event_failed"]."</h3>";
		}
		
	} else {
		audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to checkout event ".return_event_description ($id_evento));
	}
}
	
// Mass-process DELETE
if (isset($_POST["deletebt"])){
	$count=0;
	while ($count <= $config["block_size"]){
		if (isset($_POST["eventid".$count])){
			$event_id = $_POST["eventid".$count];
			// Look for event_id following parameters: id_group.
			$id_group = gime_idgroup_from_idevent($event_id);
			if (give_acl($id_user, $id_group, "IM") ==1){
				mysql_query("DELETE FROM tevento WHERE id_evento = ".$event_id);
				audit_db($id_user,$REMOTE_ADDR, "Event deleted","Deleted event: ".return_event_description ($event_id));
			} else {
				audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to delete event ".return_event_description ($event_id));
			}
		}
		$count++;
	}
}

// Mass-process UPDATE
if (isset($_POST["updatebt"])){
	$count=0;
	while ($count <= $config["block_size"]){
		if (isset($_POST["eventid".$count])){
			$id_evento = $_POST["eventid".$count];
			$id_group = gime_idgroup_from_idevent($id_evento);
			if (give_acl($id_user, $id_group, "IW") ==1){
				$sql2="UPDATE tevento SET estado=1, id_usuario = '".$id_user."' WHERE estado = 0 AND id_evento = ".$id_evento;
				$result2=mysql_query($sql2);
				audit_db($id_user,$REMOTE_ADDR, "Event validated","Validate event: ".return_event_description ($id_evento));
			} else {
				audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to checkout event ID".$id_evento);
			}
		}
		$count++;
	}
}

// ***********************************************************************
// Main code form / page
// ***********************************************************************


// Get data

$offset=0;
if (isset($_GET["offset"]))
	$offset=$_GET["offset"];

if (isset($_GET["group_id"]))
                $group_id = entrada_limpia($_GET["group_id"]);
        else
                $group_id = 0;

if (isset($_POST["ev_group"]))
                        $ev_group = $_POST["ev_group"];
                elseif (isset($_GET["group_id"]))
                $ev_group = $_GET["group_id"];
        else
                $ev_group = -1;


$event="All";
if (isset($_POST["event"]))
	$event = entrada_limpia($_POST["event"]);

echo "<h2>".$lang_label["events"]." &gt; ".$lang_label["event_main_view"]."</h2>";
echo "<table width=100%>";
echo "<tr>";
echo "<form method='post' action='index.php?sec=eventos&sec2=operation/events/events&refr=60'>";
echo "<td>".$lang_label["group"]."</td>";
echo "<td>";
echo "<select name='ev_group' onChange='javascript:this.form.submit();' class='w130'>";
if ( $ev_group > 1 ){
	echo "<option value='".$ev_group."'>".dame_nombre_grupo($ev_group)."</option>";
}
echo "<option value=1>".dame_nombre_grupo(1)."</option>";
list_group ($id_user);
echo "</select></td></tr>";

echo "<tr><td valign='middle'>".$lang_label["events"]."</td>";
echo "<td><form method='post' action='index.php?sec=eventos&sec2=operation/events/events&refr=60'>";
echo "<select name='event' onChange='javascript:this.form.submit();' class='w155'>";
echo "<option value='All'>".$lang_label["all"]."</option>";

// Fill event type combo (DISTINCT!)
if (isset($ev_group) && ($ev_group > 1))
	$sql="SELECT DISTINCT evento FROM tevento WHERE id_grupo = '$ev_group'";
else
	$sql="SELECT DISTINCT evento FROM tevento";
$result=mysql_query($sql);
// Make query for distinct (to fill combo)
while ($row=mysql_fetch_array($result))
	echo "<option value='".$row["evento"]."'>".$row["evento"]."</option>";
echo "</select>";
echo "</form>";
echo "<td valign='middle'>";
echo "<noscript><input type='submit' class='sub' value='".$lang_label["show"]."'></noscript>";

echo "</table>";

echo "<br>";
	
// How many events do I have in total ?
if ($event=="All"){
	if (isset($ev_group) && ($ev_group > 1)) {
		$sql3="SELECT COUNT(id_evento) FROM tevento WHERE id_grupo = '$ev_group' ";
	} else {
		$sql3="SELECT COUNT(id_evento) FROM tevento";
	}
} else {
	if (isset($ev_group) && ($ev_group > 1)) {
		$sql3="SELECT COUNT(id_evento) FROM tevento WHERE evento = '$event' AND id_grupo = '$ev_group'";
	} else {
		$sql3="SELECT COUNT(id_evento) FROM tevento WHERE evento = '$event' ";
	}
}
$result3=mysql_query($sql3);
$row3=mysql_fetch_array($result3);
$total_events = $row3[0];
// Show pagination header

if ($total_events > 0){
	pagination ($total_events, "index.php?sec=eventos&sec2=operation/events/events&group_id=$ev_group&refr=60", $offset);		
	// Show data.
		
	echo "<br>";
	echo "<br>";
	echo "<table cellpadding='4' cellspacing='4' width='750' class='databox'>";
	echo "<tr>";
	echo "<th>".$lang_label["status"]."</th>";
	echo "<th>".$lang_label["event_name"]."</th>";
	echo "<th>".$lang_label["agent_name"]."</th>";
	echo "<th>".$lang_label["group"]."</th>";
	echo "<th>".$lang_label["id_user"]."</th>";
	echo "<th width='85'>".$lang_label["timestamp"]."</th>";
	echo "<th width='80'>".$lang_label["action"]."</th>";
	echo "<th class='p10'>";
	echo "<label for='checkbox' class='p21'>".$lang_label["all"]." </label>";
	echo '<input type="checkbox" class="chk" name="allbox" onclick="CheckAll();"></th>';
	echo "<form name='eventtable' method='POST' action='index.php?sec=eventos&sec2=operation/events/events&refr=60&offset=".$offset."'>";
	$color = 1;
	$id_evento = 0;
	
	// Prepare index for pagination. Prepare queries
	if ($event=="All"){
		if (isset($ev_group) && ($ev_group > 1)) {
			$sql2="SELECT * FROM tevento WHERE id_grupo = '$ev_group' ORDER BY timestamp DESC LIMIT $offset, ".$config["block_size"];
		} else {
			$sql2="SELECT * FROM tevento ORDER BY timestamp DESC LIMIT $offset, ".$config["block_size"];
		}
	} else {
		if (isset($ev_group) && ($ev_group > 1)) {
			$sql2="SELECT * FROM tevento WHERE evento = '$event' AND id_grupo = '$ev_group' ORDER BY timestamp DESC LIMIT $offset, ".$config["block_size"];
		} else {
			$sql2="SELECT * FROM tevento WHERE evento = '$event' ORDER BY timestamp DESC LIMIT $offset, ".$config["block_size"];
		}
	}

	$offset_counter=0;
	// Make query for data (all data, not only distinct).
	$result2=mysql_query($sql2);
	while ($row2=mysql_fetch_array($result2)){
		$id_grupo = $row2["id_grupo"];
		if (give_acl($id_user, $id_grupo, "IR") == 1){ // Only incident read access to view data !
			$id_group = $row2["id_grupo"];
			if ($color == 1){
				$tdcolor = "datos";
				$color = 0;
			}
			else {
				$tdcolor = "datos2";
				$color = 1;
			}
			echo "<tr><td class='$tdcolor' align='center'>";
			if ($row2["estado"] == 0)
				echo "<img src='images/dot_red.png'>";
			else
				echo "<img src='images/dot_green.png'>";
			echo "<td class='$tdcolor'>".$row2["evento"];
			if ($row2["id_agente"] > 0){
					echo "<td class='$tdcolor'><a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=".$row2["id_agente"]."'><b>".dame_nombre_agente($row2["id_agente"])."</b></a>";

					echo "<td class='$tdcolor' align='center'><img src='images/groups_small/".show_icon_group($id_group).".png' class='bot'></td>";
					echo "<td class='$tdcolor'>";
			} else { // for SNMP generated alerts
				echo "<td class='$tdcolor'>".$lang_label["alert"]." /  SNMP";
				echo "<td class='$tdcolor' align='center'><img src='images/dot_white.png' class='bot'>";
				echo "<td class='$tdcolor'>";
			}
			if ($row2["estado"] <> 0)
				echo "<a href='index.php?sec=usuario&sec2=operation/users/user_edit&ver=".$row2["id_usuario"]."'>".substr($row2["id_usuario"],0,8)."<a href='#' class='tip'> <span>".dame_nombre_real($row2["id_usuario"])."</span></a></a>";
			echo "<td class='".$tdcolor."f9'>".$row2["timestamp"];
			echo "<td class='$tdcolor' align='right'>";
	
			if (($row2["estado"] == 0) and (give_acl($id_user,$id_group,"IW") ==1))
				echo "<a href='index.php?sec=eventos&sec2=operation/events/events&offset=".$offset."&check=".$row2["id_evento"]."'><img src='images/ok.png' border='0'></a> ";
			if (give_acl($id_user,$id_group,"IM") ==1)
				echo "<a href='index.php?sec=eventos&sec2=operation/events/events&delete=".$row2["id_evento"]."&refr=60&offset=".$offset."'><img src='images/cross.png' border=0></a> ";
					
			if (give_acl($id_user,$id_group,"IW") == 1)
				echo "<a href='index.php?sec=incidencias&sec2=operation/incidents/incident_detail&insert_form&from_event=".$row2["id_evento"]."'><img src='images/page_lightning.png' border=0></a>";
					
			echo "<td class='$tdcolor' align='center'>";
			echo "<input type='checkbox' class='chk' name='eventid".$offset_counter."' value='".$row2["id_evento"]."'>";
			echo "</td></tr>";
		}
		$offset_counter++;
	}
	echo "</table>";
	echo "<table width='750'><tr><td align='right'>";
	
	echo "<input class='sub ok' type='submit' name='updatebt' value='".$lang_label["validate"]."'> ";
	if (give_acl($id_user, 0,"IM") ==1){
		echo "<input class='sub delete' type='submit' name='deletebt' value='".$lang_label["delete"]."'>";
	}
	echo "</form></table>";
echo "<table>";
echo "<tr>";
echo "<td rowspan='4' class='f9' style='padding-left: 30px; line-height: 17px; vertical-align: top;'>";
echo "<h3>".$lang_label["status"]."</h3>";
echo "<img src='images/dot_green.png'> - ".$lang_label["validated_event"];
echo "<br>";
echo "<img src='images/dot_red.png'> - ".$lang_label["not_validated_event"];
echo "</td>";
echo "<td rowspan='4' class='f9' style='padding-left: 30px; line-height: 17px; vertical-align: top;'>";
echo "<h3>".$lang_label["action"]."</h3>";
echo "<img src='images/ok.png'> - ".$lang_label["validate_event"];
echo "<br>";
echo "<img src='images/cross.png'> - ".$lang_label["delete_event"];
echo "<br>";
echo "<img src='images/page_lightning.png'> - ".$lang_label["create_incident"];
echo "</td></tr></table>";
} // no events to show
?>
