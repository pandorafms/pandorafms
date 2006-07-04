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
// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2006
// Raul Mateos <raulofpandora@gmail.com>, 2005-2006

// Load globar var
require("include/config.php");
//require("include/functions.php");
//require("include/functions_db.php");
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
					$sql2="UPDATE tevento SET estado=1, id_usuario = '".$id_usuario."' WHERE estado = 0 and id_evento = ".$id_evento;
					$result2=mysql_query($sql2);
				} else {
					audit_db($id_usuario,$REMOTE_ADDR, "ACL Violation","Trying to checkout event ID".$id_evento);
				}
			}
			$count++;
		}
	}

	echo "<h2>".$lang_label["events"]."</h2>";
	echo "<h3>".$lang_label["event_main_view"]."<a href='help/".substr($language_code,0,2)."/chap5.php#5' target='_help' class='help'>&nbsp;<span>".$lang_label["help"]."</span></a></h3>";

	// Prepare index for pagination
	$event_list[]="";
	$sql2="SELECT * FROM tevento ORDER BY timestamp DESC";
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

	echo "<table><tr>";
	echo "<td class='f9l30'>";
	echo "<img src='images/dot_green.gif'> - ".$lang_label["validated_event"];
	echo "<br>";
	echo "<img src='images/dot_red.gif'> - ".$lang_label["not_validated_event"];
	echo "</td>";
	echo "<td class='f9l20'>";  
	echo "<img src='images/ok.gif'> - ".$lang_label["validate_event"];
	echo "<br>"; 
	echo "<img src='images/cancel.gif '> - ".$lang_label["delete_event"];
	echo "</td>"; 
	echo "</tr></table>";
	echo "<br>";
	
	//pagination
	$total_eventos = count($event_list);
	pagination($total_eventos, "index.php?sec=eventos&sec2=operation/events/events", $offset);
	/*
	if ($total_eventos > $block_size){ 
		// If existes more registers tha$row["id_usuario"]n i can put in a page, calculate index markers
		$index_counter = ceil($total_eventos/$block_size);
		for ($i = 1; $i <= $index_counter; $i++) {
			$inicio_bloque = ($i * $block_size - $block_size);
			$final_bloque = $i * $block_size;
			if ($total_eventos < $final_bloque)
				$final_bloque = $total_eventos;
			echo '<a href="index.php?sec=eventos&sec2=eventos/eventos&offset='.$inicio_bloque.'">';
			$inicio_bloque_fake = $inicio_bloque + 1;
			if ($inicio_bloque == $offset)
				echo '<b>[ '.$inicio_bloque_fake.' - '.$final_bloque.' ]</b>';
			else 
				echo '[ '.$inicio_bloque_fake.' - '.$final_bloque.' ]';
			echo '</a> ';
		}
		echo "<br><br>";
		// if exists more registers than i can put in a page (defined by $block_size config parameter)
		// get offset for index calculation
	}

	echo "</div>";
	*/
	
	if (isset($_GET["offset"])){
		$offset=entrada_limpia($_GET["offset"]);
	} else {
		$offset=0;
	}
	
	echo "<br>";
	echo "<table border='0' cellpadding='3' cellspacing='3' width='775'>";
	echo "<tr>";
	echo "<th>".$lang_label["status"];
	echo "<th>".$lang_label["event_name"];
	echo "<th>".$lang_label["agent_name"];
	echo "<th>".$lang_label["group_name"];
	echo "<th>".$lang_label["id_user"];
	echo "<th class='w130'>".$lang_label["timestamp"];
	echo "<th>".$lang_label["action"];
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
				$sql="SELECT * FROM tevento WHERE id_evento = $id_evento";
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
						echo "<td class='$tdcolor'>".dame_nombre_grupo($row["id_grupo"]);
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
	else {echo "<font class='red'>".$lang_label["no_event"]."</font>";}
	}
	else {
		audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access event viewer");
		require ("general/noaccess.php");
	}
}

?>