<?php

// Pandora - the Free monitoring system
// ====================================
// Copyright (c) 2004-2006 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2005-2006 Artica Soluciones Tecnologicas S.L, info@artica.es
// Copyright (c) 2004-2006 Raul Mateos Martin, raulofpandora@gmail.com
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
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

if (comprueba_login() == 0) {
	$accion = "";
	$id_usuario =$_SESSION["id_usuario"];
 	if (give_acl($id_usuario, 0, "AR")==1) {
	// OPERATIONS
	// Delete Event (only incident management access).
	if (isset($_GET["delete"])){
		$id_evento = $_GET["delete"];
		// Look for event_id following parameters: id_group.
		$id_group = gime_idgroup_from_idevent($id_evento);
		if (give_acl($id_usuario, $id_group, "IM") ==1){
			$sql2="DELETE FROM tevento WHERE id_evento =".$id_evento;
			$result2=mysql_query($sql2);
			if ($result) {echo "<h3 class='suc'>".$lang_label["delete_event_ok"]."</h3>";}
		} else {
			audit_db($id_usuario,$REMOTE_ADDR, "ACL Violation","Trying to delete event ID".$id_evento);
		}
	}
	
	// Check Event (only incident write access).
	if (isset($_GET["check"])){
		$id_evento = $_GET["check"];
		// Look for event_id following parameters: id_group.
		$id_group = gime_idgroup_from_idevent($id_evento);
		if (give_acl($id_usuario, $id_group, "IW") ==1){
			$sql2="UPDATE tevento SET estado=1, id_usuario = '".$id_usuario."' WHERE id_evento = ".$id_evento;
			$result2=mysql_query($sql2);
			if ($result2) { echo "<h3 class='suc'>".$lang_label["validate_event_ok"]."</h3>";}

		} else {
			audit_db($id_usuario,$REMOTE_ADDR, "ACL Violation","Trying to checkout event ID".$id_evento);
		}
	}
	
	// Mass-process DELETE
	if (isset($_POST["deletebt"])){
		$count=0;
		while ($count <= $block_size){
			if (isset($_POST["eventid".$count])){
				$event_id = $_POST["eventid".$count];
				// Look for event_id following parameters: id_group.
				$id_group = gime_idgroup_from_idevent($event_id);
				if (give_acl($id_usuario, $id_group, "IM") ==1){
					mysql_query("DELETE FROM tevento WHERE id_evento =".$event_id);
				} else {
					audit_db($id_usuario,$REMOTE_ADDR, "ACL Violation","Trying to delete event ID".$id_evento);
				}
			}
			$count++;
		}
	}
	
	// Mass-process UPDATE
	if (isset($_POST["updatebt"])){
		$count=0;
		while ($count <= $block_size){
			if (isset($_POST["eventid".$count])){
				$id_evento = $_POST["eventid".$count];
				$id_group = gime_idgroup_from_idevent($id_evento);
				if (give_acl($id_usuario, $id_group, "IW") ==1){
					$sql2="UPDATE tevento SET estado=1, id_usuario = '".$id_usuario."' WHERE estado = 0 AND id_evento = ".$id_evento;
					$result2=mysql_query($sql2);
				} else {
					audit_db($id_usuario,$REMOTE_ADDR, "ACL Violation","Trying to checkout event ID".$id_evento);
				}
			}
			$count++;
		}
	}
	
	
	echo "<h2>".$lang_label["events"]."</h2>";
	echo "<h3>".$lang_label["event_main_view"]."<a href='help/".$help_code."/chap5.php#5' target='_help' class='help'>&nbsp;<span>".$lang_label["help"]."</span></a></h3>";
	echo "<table cellpadding='3' cellspacing='3'><tr>";
	
	if (isset($_POST["ev_group"])) {
		$ev_group = $_POST["ev_group"];
	} else {
		$ev_group = -1;
	}
	echo "<form method='post' action='index.php?sec=eventos&sec2=operation/events/events&refr=60'>";

	echo "<td>".$lang_label["group"]."</td>";
	echo "<td>";
	echo "<select name='ev_group' onChange='javascript:this.form.submit();' class='w130'>";

	if ( $ev_group > 1 ){
		echo "<option value='".$ev_group."'>".dame_nombre_grupo($ev_group);
	} 
	echo "<option value=1>".dame_nombre_grupo(1)."</option>";
	$mis_grupos[]=""; // Define array mis_grupos to put here all groups with Agent Read permission
	$iconindex_g[]="";
	$sql='SELECT id_grupo, icon FROM tgrupo';
	$result=mysql_query($sql);
	while ($row=mysql_fetch_array($result)){
		$iconindex_g[$row["id_grupo"]] = $row["icon"];
		if ($row["id_grupo"] != 1){
			if (give_acl($id_usuario,$row["id_grupo"], "AR") == 1){
				echo "<option value='".$row["id_grupo"]."'>".dame_nombre_grupo($row["id_grupo"]);
				$mis_grupos[]=$row["id_grupo"]; //Put in  an array all the groups the user belongs
			}
		}
	}
	echo "</select>";
	echo "<td class='f9l30w17t'>";
	echo "<img src='images/dot_green.gif'> - ".$lang_label["validated_event"];
	echo "<br>";
	echo "<img src='images/dot_red.gif'> - ".$lang_label["not_validated_event"];
	echo "</td>";
	echo "<td class='f9l30w17t'>";  
	echo "<img src='images/ok.gif'> - ".$lang_label["validate_event"];
	echo "<br>"; 
	echo "<img src='images/cancel.gif'> - ".$lang_label["delete_event"];
	echo "</td>"; 
	echo "<tr><td valign='middle'>".$lang_label["events"]."</td>";
	echo "<td><form method='post' action='index.php?sec=eventos&sec2=operation/events/events&refr=60'>";
	echo "<select name='event' onChange='javascript:this.form.submit();' class='w155'>";


	// Prepare index for pagination
	$event_list[]="";
	if (isset($_POST["event"])){
		$event = entrada_limpia($_POST["event"]);
		if ($event=="All")
		{
			if (isset($ev_group) && ($ev_group > 1)) {
				$sql2="SELECT * FROM tevento WHERE id_grupo = '$ev_group' ORDER BY timestamp DESC";
			} else {
				$sql2="SELECT * FROM tevento ORDER BY timestamp DESC";
			}
		} else {
			if (isset($ev_group) && ($ev_group > 1)) {
				$sql2="SELECT * FROM tevento WHERE evento = '$event' AND id_grupo = '$ev_group' ORDER BY timestamp DESC";
			} else {
				$sql2="SELECT * FROM tevento WHERE evento = '$event' ORDER BY timestamp DESC";
				}
			echo "<option value='".$event."'>".$event."</option>";
		}
	} else {
		$sql2="SELECT * FROM tevento ORDER BY timestamp DESC";
	}
	echo "<option value='All'>".$lang_label["all"]."</option>";
	$result2=mysql_query($sql2);
	if (mysql_num_rows($result2)){
		while ($row2=mysql_fetch_array($result2)){ // Jump offset records
		
			$id_grupo = $row2["id_grupo"];
				if (give_acl($id_usuario, $id_grupo, "IR") == 1) // Only incident read access to view data !
					$event_list[]=$row2["id_evento"];
		}
		if (isset($_GET["offset"]))
			$offset=$_GET["offset"];
		else
			$offset=0;
	
		$offset_counter=0;
	if (isset($ev_group) && ($ev_group > 1)) {
		$sql="SELECT DISTINCT evento FROM tevento WHERE id_grupo = '$ev_group'";
	} else {
		$sql="SELECT DISTINCT evento FROM tevento";
	}
	$result=mysql_query($sql);
	while ($row=mysql_fetch_array($result)){
			echo "<option value='".$row["evento"]."'>".$row["evento"]."</option>";
		}
	echo "</select>";
	echo "</form>";
	echo "<td valign='middle'>";
	echo "<noscript><input type='submit' class='sub' value='".$lang_label["show"]."'></noscript>";
	echo "</td></tr>";
	echo "</table>";
	echo "<br>";
	
	//pagination
	$total_eventos = count($event_list);
	pagination($total_eventos, "index.php?sec=eventos&sec2=operation/events/events", $offset);
	
	if (isset($_GET["offset"])){
		$offset=entrada_limpia($_GET["offset"]);
	} else {
		$offset=0;
	}
	
	echo "<br>";
	echo "<table cellpadding='3' cellspacing='3' width='775'>";
	echo "<tr>";
	echo "<th>".$lang_label["status"]."</th>";
	echo "<th>".$lang_label["event_name"]."</th>";
	echo "<th>".$lang_label["agent_name"]."</th>";
	echo "<th>".$lang_label["group"]."</th>";
	echo "<th>".$lang_label["id_user"]."</th>";
	echo "<th class='w130'>".$lang_label["timestamp"]."</th>";
	echo "<th>".$lang_label["action"]."</th>";
	echo "<th class='p10'>";
	echo "<label for='checkbox' class='p21'>".$lang_label["all"]." </label>";
	echo '<input type="checkbox" class="chk" name="allbox" onclick="CheckAll();"></th>';	
	echo "<form name='eventtable' method='POST' action='index.php?sec=eventos&sec2=operation/events/events&refr=60&offset=".$offset."'>";
	$color = 1;
	$id_evento = 0;
	if ($offset !=0)
		$offset_limit = $offset +1;
	else
		$offset_limit = $offset;
	// Skip offset records
	for ($a=$offset_limit;$a < ($block_size + $offset + 1);$a++){
		if (isset($event_list[$a])) {
			$id_evento = $event_list[$a]; 
			if ($id_evento != ""){
				if (isset($_POST["event"])) {
					$event = entrada_limpia($_POST["event"]);
					if ($event=="All") {
						if (isset($ev_group) && ($ev_group > 1)) {
							$sql="SELECT * FROM tevento WHERE id_evento = '$id_evento' AND id_grupo = '$ev_group'";
						} else {
							$sql="SELECT * FROM tevento WHERE id_evento = '$id_evento'";
						}
						
					} else {
						if (isset($ev_group) && ($ev_group > 1)) {
							$sql="SELECT * FROM tevento WHERE evento= '$event' AND id_evento = '$id_evento' AND id_grupo = '$ev_group'";
						} else {
							$sql="SELECT * FROM tevento WHERE evento= '$event' AND id_evento = '$id_evento'";
						}
					}
					
					
				} else {
					$sql="SELECT * FROM tevento WHERE id_evento = $id_evento";
				}
				$result=mysql_query($sql);
				$row=mysql_fetch_array($result);
				$id_group = $row["id_grupo"];
				if ($color == 1){
					$tdcolor = "datos";
					$color = 0;
				}
				else {
					$tdcolor = "datos2";
					$color = 1;
				}
				//if (give_acl($id_usuario, $id_group, "IR") == 1){ // Only incident read access to view data
				$offset_counter++;
				echo "<tr><td class='$tdcolor' align='center'>";
				if ($row["estado"] == 0)
					echo "<img src='images/dot_red.gif'>";
				else 
					echo "<img src='images/dot_green.gif'>";
				echo "<td class='$tdcolor'>".$row["evento"];
				if ($row["id_agente"] > 0){
						echo "<td class='$tdcolor'><a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=".$row["id_agente"]."'><b>".dame_nombre_agente($row["id_agente"])."</b></a>";
						echo "<td class='$tdcolor'><img src='images/g_".$iconindex_g[$id_group].".gif'> ( ".dame_grupo($id_group)." )</td>";
						echo "<td class='$tdcolor'>";
					} else { // for SNMP generated alerts
						echo "<td class='$tdcolor' colspan='2'>".$lang_label["alert"]." /  SNMP";
						echo "<td class='$tdcolor'>";
					}
					if ($row["estado"] <> 0)
						echo "<a href='index.php?sec=usuario&sec2=operation/users/user_edit&ver=".$row["id_usuario"]."'><a href='#' class='tip'>&nbsp;<span>".dame_nombre_real($row["id_usuario"])."</span></a>".substr($row["id_usuario"],0,8)."</a>";
					echo "<td class='$tdcolor'>".$row["timestamp"];
					echo "<td class='$tdcolor' align='center'>";
					
					if (($row["estado"] == 0) and (give_acl($id_usuario,$id_group,"IW") ==1))
						echo "<a href='index.php?sec=eventos&sec2=operation/events/events&check=".$row["id_evento"]."'><img src='images/ok.gif' border='0'></a>";
					if (give_acl($id_usuario,$id_group,"IM") ==1)
						echo "<a href='index.php?sec=eventos&sec2=operation/events/events&delete=".$row["id_evento"]."&refr=60&offset=".$offset."'><img src='images/cancel.gif' border=0></a>";
					echo "<td class='$tdcolor' align='center'>";
					echo "<input type='checkbox' class='chk' name='eventid".$offset_counter."' value='".$row["id_evento"]."'>";
					echo "</td></tr>";
				//}
			}
		}
	}
	echo "<tr><td colspan='8'><div class='raya'></div></td></tr>";
	echo "<tr><td colspan='8' align='right'>";
	
	echo "<input class='sub' type='submit' name='updatebt' value='".$lang_label["validate"]."'> ";
	if (give_acl($id_usuario, 0,"IM") ==1){
		echo "<input class='sub' type='submit' name='deletebt' value='".$lang_label["delete"]."'>";
	}
	echo "</form></table>";
	}
	else {echo "</select></form></td></tr></table><br><div class='nf'>".$lang_label["no_event"]."</div>";}
	}
	else {
		audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access event viewer");
		require ("general/noaccess.php");
	}
}

?>