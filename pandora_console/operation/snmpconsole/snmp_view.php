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
 	// Var init
	$accion = "";
	$offset_counter =0;

	$id_usuario =$_SESSION["id_usuario"];
 	if (give_acl($id_usuario, 0, "AR")==1) {	
	// OPERATIONS
	
	// Delete SNMP Trap entryEvent (only incident management access).
	if (isset($_GET["delete"])){
		$id_trap = $_GET["delete"];
		if (give_acl($id_usuario, 0, "IM") ==1){
			$sql2="DELETE FROM ttrap WHERE id_trap =".$id_trap;
			$result2=mysql_query($sql2);
			if ($result) { echo "<h3 class='suc'>".$lang_label["delete_event_ok"]."</h3>";}
		} else {
			audit_db($id_usuario,$REMOTE_ADDR, "ACL Violation","Trying to delete event ID".$id_trap);
		}
	}
	
	// Check Event (only incident write access).
	if (isset($_GET["check"])){
		$id_trap = $_GET["check"];
		if (give_acl($id_usuario, 0, "IW") ==1){
			$sql2="UPDATE ttrap set status=1, id_usuario = '".$id_usuario."' WHERE id_trap = ".$id_trap;
			$result2=mysql_query($sql2);
			if ($result2) { echo "<h3 class='suc'>".$lang_label["validate_event_ok"]."</h3>";}

		} else {
			audit_db($id_usuario,$REMOTE_ADDR, "ACL Violation","Trying to checkout SNMP Trap ID".$id_trap);
		}
	}

	// Mass-process DELETE
	if (isset($_POST["deletebt"])){
		$count=0;
		if (give_acl($id_usuario, 0, "IW") ==1){
			while ($count <= $config["block_size"]){
				if (isset($_POST["snmptrapid".$count])){
					$trap_id = $_POST["snmptrapid".$count];
					mysql_query("DELETE FROM ttrap WHERE id_trap =".$trap_id);
				}
				$count++;
			}
		} else {
			audit_db($id_usuario,$REMOTE_ADDR, "ACL Violation","Trying to mass-delete SNMP Trap ID");
		}
	}
	
	// Mass-process UPDATE
	if (isset($_POST["updatebt"])){
		$count=0;
		if (give_acl($id_usuario, 0, "IW") ==1){
			while ($count <= $config["block_size"]){
				if (isset($_POST["snmptrapid".$count])){
					$id_trap = $_POST["snmptrapid".$count];
					$sql2="UPDATE ttrap SET status=1, id_usuario = '".$id_usuario."' WHERE status = 0 and id_trap = ".$id_trap;
					$result2=mysql_query($sql2);
				}
				$count++;
			}
		} else {
			audit_db($id_usuario,$REMOTE_ADDR, "ACL Violation","Trying to mass-validate SNMP Trap ID");
		}
	}
	echo "<h2>Pandora SNMP &gt; ";
	echo $lang_label["SNMP_console"]."</h2>";

	if (isset($_GET["offset"]))
		$offset=$_GET["offset"];
	else
		$offset=0;
	
	$sql2="SELECT * FROM ttrap ORDER BY timestamp DESC";
	$result2=mysql_query($sql2);
	
	if (mysql_num_rows($result2)){
	
		echo "<table><tr>";
		echo "<td class='f9' style='padding-left: 30px;'>";
		echo "<img src='images/dot_green.png'> - ".$lang_label["validated_event"];
		echo "<br>";
		echo "<img src='images/dot_red.png'> - ".$lang_label["not_validated_event"];
		echo "<br>";
		echo "<img src='images/dot_yellow.png'> - ".$lang_label["alert"];
		echo "</td>";
		echo "<td class='f9' style='padding-left: 20px;'>";  
		echo "<img src='images/ok.png'> - ".$lang_label["validate_event"];
		echo "<br>"; 
		echo "<img src='images/cross.png '> - ".$lang_label["delete_event"];
		echo "</td>";
		echo "</tr></table>";
		echo "<br>";
		
		// Prepare index for pagination
		$trap_list[]="";
	
		while ($row2=mysql_fetch_array($result2)){ // Jump offset records
				$trap_list[]=$row2["id_trap"];
		}

	$total_traps = count($trap_list);
	pagination($total_traps, "index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_view", $offset);
	/*
	if ($total_eventos > $config["block_size"]){ 
		// If existes more registers tha$row["id_usuario"]n i can put in a page, calculate index markers
		$index_counter = ceil($total_eventos/$config["block_size"]);
		for ($i = 1; $i <= $index_counter; $i++) {
			$inicio_bloque = ($i * $config["block_size"] - $config["block_size"]);
			$final_bloque = $i * $config["block_size"];
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
		// if exists more registers than i can put in a page (defined by $config["block_size"] config parameter)
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
	echo "<table cellpadding='4' cellspacing='4' width='750' class='databox'>";
	echo "<tr>";
	echo "<th>".$lang_label["status"]."</th>";
	echo "<th>".$lang_label["OID"]."</th>";
	echo "<th>".$lang_label["SNMP_agent"]."</th>";
	echo "<th>".$lang_label["customvalue"]."</th>";
	echo "<th>".$lang_label["id_user"]."</th>";
	echo "<th width ='130px'>".$lang_label["timestamp"]."</th>";
	echo "<th>".$lang_label["alert"]."</th>";
	echo "<th>".$lang_label["action"]."</th>";
	echo "<th class='p10'>";
	echo "<label for='checkbox' class='p21'>".$lang_label["all"]." </label>";
	echo '<input type="checkbox" class="chk" name="allbox" onclick="CheckAll();">
	</th>';
	echo "<form name='eventtable' method='POST' action='index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_view&refr=60&offset=".$offset."'>";	
	$id_trap = 0;
	if ($offset !=0)
		$offset_limit = $offset +1;
	else
		$offset_limit = $offset;
	// Skip offset records
	for ($a=$offset_limit;$a < ($config["block_size"] + $offset + 1);$a++){
		if (isset($trap_list[$a])){
			$id_trap = $trap_list[$a];
			$sql="SELECT * FROM ttrap WHERE id_trap = $id_trap";
			if ($result=mysql_query($sql)){
				$row=mysql_fetch_array($result);
				$offset_counter++;
				echo "<tr>";
				echo "<td class='datos' align='center'>";
				if ($row["status"] == 0){
					echo "<img src='images/dot_red.png'>";
				}
				else {
					echo "<img src='images/dot_green.png'>";
				}
				echo "<td class='datos'>".$row["oid"];
				$sql="SELECT * FROM tagente WHERE direccion = '".$row["source"]."'";
				$result2=mysql_query($sql); // If there's any agent with this IP we show name and link to agent
				if ($row2=mysql_fetch_array($result2)){
					echo "<td class='datos'>
					<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=".$row2["id_agente"]."'>
					<b>".dame_nombre_agente($row2["id_agente"])."</b></a></td>";
				}
				else {
					echo "<td class='datos'>".$row["source"]."</td>";
				}
				echo "<td class='datos'>".$row["value_custom"]."</td>";
	
				echo "<td class='datos'>";
				if ($row["status"] <> 0)
					echo "<a href='index.php?sec=usuario&sec2=operation/users/user_edit&ver=".$row["id_usuario"]."'><a href='#' class='tip'>&nbsp;<span>".dame_nombre_real($row["id_usuario"])."</span></a>".substr($row["id_usuario"],0,8)."</a>";
				echo "<td class='datos'>".$row["timestamp"]."</td>";
				echo "<td class='datos' align='center'>";
				if ($row["alerted"] != 0 )
					echo "<img src='images/dot_yellow.png' border=0>";
				echo "<td class='datos' align='center'>";
				
				if (($row["status"] == 0) and (give_acl($id_usuario,"0","IW") ==1))
					echo "<a href='index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_view&check=".$row["id_trap"]."'><img src='images/ok.png' border='0'></a>";
				if (give_acl($id_usuario,"0","IM") ==1)
					echo "<a href='index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_view&delete=".$row["id_trap"]."&refr=60&offset=".$offset."'><img src='images/cross.png' border=0></a>";
				echo "<td class='datos' align='center'>";
				echo "<input type='checkbox' class='chk' name='snmptrapid".$offset_counter."' value='".$row["id_trap"]."'>";
				echo "</td></tr>";
			}
		}
	}
	echo "</table>";
	$offset_counter = 0;
	echo "<table width='750px'><tr><td align='right'>";
	
	echo "<input class='sub' type='submit' name='updatebt' value='".$lang_label["validate"]."'> ";
	if (give_acl($id_usuario, 0,"IM") ==1){
		echo "<input class='sub' type='submit' name='deletebt' value='".$lang_label["delete"]."'>";
	}
	echo "</form></td></tr></table>";

	} else { 
		echo "<div class='nf'>".$lang_label["no_snmp_agent"]."</div>";
		}
	
	} 
	else {
		audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access SNMP Console");
		require ("general/noaccess.php");
	}
}

?>