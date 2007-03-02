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

	// Load global vars
	require("include/config.php");

	if (give_acl($id_user, 0, "AW") != 1) {
		audit_db($id_user,$REMOTE_ADDR, "ACL Violation",
		"Trying to access Agent Management");
		require ("general/noaccess.php");
		exit;
	}
	
	if (isset($_GET["offset"]))
		$offset = entrada_limpia($_GET["offset"]);
	else
		$offset = 0;
	
	if (isset($_GET["borrar_agente"])){ // if delete agent
		$id_agente = entrada_limpia($_GET["borrar_agente"]);
		$agent_name = dame_nombre_agente($id_agente);
		$id_grupo = dame_id_grupo($id_agente);
		if (give_acl($id_user, $id_grupo, "AW")==1){
			// Firts delete from agents table
			$sql_delete= "DELETE FROM tagente 
			WHERE id_agente = ".$id_agente;
			$result=mysql_query($sql_delete);		
			if (! $result)
				echo "<h3 class='error'>".$lang_label["delete_agent_no"]."</h3>"; 
			else
				echo "<h3 class='suc'>".$lang_label["delete_agent_ok"]."</h3>";
			// Delete agent access table
			$sql_delete = "DELETE FROM tagent_access 
			WHERE id_agent = ".$id_agente;
			// Delete tagente_datos data
			$result=mysql_query($sql_delete);
			$sql_delete4="DELETE FROM tagente_datos 
			WHERE id_agente=".$id_agente;
			$result=mysql_query($sql_delete4);
			// Delete tagente_datos_string data
			$result=mysql_query($sql_delete);
			$sql_delete4="DELETE FROM tagente_datos_string 
			WHERE id_agente=".$id_agente;
			$result=mysql_query($sql_delete4);
			// Delete from tagente_datos
			$sql1='SELECT * FROM tagente_modulo 
			WHERE id_agente = '.$id_agente;
			$result1=mysql_query($sql1);
			while ($row=mysql_fetch_array($result1)){
				$sql_delete4="DELETE FROM tagente_datos_inc 
				WHERE id_agente_modulo=".$row["id_agente_modulo"];
				$result=mysql_query($sql_delete4);
			}
			$sql_delete2 ="DELETE FROM tagente_modulo 
			WHERE id_agente = ".$id_agente;
			$sql_delete3 ="DELETE FROM tagente_estado 
			WHERE id_agente = ".$id_agente; 
			$result=mysql_query($sql_delete2);
			$result=mysql_query($sql_delete3);
			audit_db($id_user,$REMOTE_ADDR, "Agent '$agent_name' deleted",
				"Agent Management");
		} else { // NO permissions.
			audit_db($id_user,$REMOTE_ADDR, "ACL Violation",
			"Trying to delete agent '$agent_name'");
			require ("general/noaccess.php");
			exit;
        	}
	}
	echo "<h2>".$lang_label["agent_conf"]." &gt; ".$lang_label["agent_defined2"]."
	<a href='help/".$help_code."/chap3.php#3' target='_help' class='help'>
	<span>".$lang_label["help"]."</span></a></h2>";
	
	$sql1="SELECT id_agente, nombre, id_grupo, comentarios, id_os 
	FROM tagente ORDER BY nombre LIMIT $offset, $block_size";
	$result=mysql_query($sql1);

	$sql2="SELECT COUNT(id_agente) FROM tagente";
	$result2=mysql_query($sql2);
	$row2=mysql_fetch_array($result2);
	$total_events = $row2[0];

	// Prepare pagination
	echo "<div style='height: 20px'> </div>";
	pagination ($total_events, "index.php?sec=gagente&sec2=godmode/agentes/modificar_agente", $offset);
	echo "<div style='height: 20px'> </div>";
	
	if (mysql_num_rows($result)){
		echo "<table cellpadding='4' cellspacing='4' width='700'>";
		echo "<th>".$lang_label["agent_name"];
		echo "<th>".$lang_label["os"];
		echo "<th>".$lang_label["group"];
		echo "<th>".$lang_label["description"];
		echo "<th>".$lang_label["delete"];
		$color=1;
		while ($row=mysql_fetch_array($result)){
			$id_grupo = $row["id_grupo"];
			if ($color == 1){
				$tdcolor = "datos";
				$color = 0;
				}
			else {
				$tdcolor = "datos2";
				$color = 1;
			}
			if (give_acl($id_user, $id_grupo, "AW")==1){
				// Agent name
				echo "<tr><td class='$tdcolor'>
				<b><a href='index.php?sec=gagente&
				sec2=godmode/agentes/configurar_agente&
				id_agente=".$row["id_agente"]."'>".$row["nombre"]."</a></b></td>";
				// Operating System icon
				echo "<td class='$tdcolor' align='center'>
				<img src='images/".dame_so_icon($row["id_os"])."'></td>";
				// Group icon and name
				echo "<td class='$tdcolor'>
				<img src='images/groups_small/".show_icon_group($id_grupo).".png' class='bot' border='0'>
				&nbsp; ".dame_grupo($id_grupo)."</td>";
				// Description
				echo "<td class='$tdcolor'>".$row["comentarios"]."</td>";
				// Action			
				echo "<td class='$tdcolor' align='center'>
				<a href='index.php?sec=gagente&
				sec2=godmode/agentes/modificar_agente&
				borrar_agente=".$row["id_agente"]."' 
				onClick='if (!confirm(\' ".$lang_label["are_you_sure"]."\')) 
				return false;'>
				<img border='0' src='images/cancel.gif'></a></td>";
			}
		}
		echo "<tr><td colspan='4'><div class='raya'></div></td></tr>";
		echo "<tr><td align='right' colspan='4'>";
		echo "<form method='post' action='index.php?sec=gagente&
		sec2=godmode/agentes/configurar_agente&creacion=1'>";
		echo "<input type='submit' class='sub' name='crt'
		value='".$lang_label["create_agent"]."'>";
		echo "</form></td></tr>";
		echo "</table>";
	} else {
		// If no data... let's show a beautiful button to create agent
		// This is a piece of crap because we're duplicanting code above
		// of this, don't do again, Raul, please.
		echo "<div class='nf'>".$lang_label["no_agent_def"]."</div>";
		echo "<form method='post' action='index.php?sec=gagente&
		sec2=godmode/agentes/configurar_agente&creacion=1'>";
		echo "<input type='submit' class='sub' name='crt'
		value='".$lang_label["create_agent"]."'>";
		echo "</form>";
	}
?>
