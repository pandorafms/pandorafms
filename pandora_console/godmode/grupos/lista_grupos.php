<?php
// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@gmail.com
// Main PHP/SQL code development and project architecture and management
// Copyright (c) 2004-2007 Raul Mateos Martin, raulofpandora@gmail.com
// CSS and some PHP additions
// Copyright (c) 2006-2007 Jonathan Barajas, jonathan.barajas[AT]gmail[DOT]com
// Javascript Active Console code.
// Copyright (c) 2006 Jose Navarro <contacto@indiseg.net>
// Additions to Pandora FMS 1.2 graph code and new XML reporting template management
// Copyright (c) 2005-2007 Artica Soluciones Tecnologicas, info@artica.es
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
// Load global vars
require("include/config.php");

if (comprueba_login() == 0)
   $id_user = $_SESSION["id_usuario"];
   if (give_acl($id_user, 0, "PM")==1) {
   
	if (isset($_POST["crear_grupo"])){ // Create group
		$nombre = entrada_limpia($_POST["nombre"]);
		$icon = entrada_limpia($_POST["icon"]);
		$sql_insert="INSERT INTO tgrupo (nombre, icon) 
		VALUES ('".$nombre."', '".$icon."') ";
		$result=mysql_query($sql_insert);	
		if (! $result)
			echo "<h3 class='error'>".$lang_label["create_group_no"]."</h3>";
		else {
			echo "<h3 class='suc'>".$lang_label["create_group_ok"]."</h3>"; 
			$id_grupo = mysql_insert_id();
		}
	}

	if (isset($_POST["update_grupo"])){ // if modified any parameter
		$nombre = entrada_limpia($_POST["nombre"]);
		$id_grupo = entrada_limpia($_POST["id_grupo"]);
		$icon = entrada_limpia($_POST["icon"]);
	    $sql_update ="UPDATE tgrupo 
		SET nombre = '".$nombre."', icon = '".$icon."' 
		WHERE id_grupo = '".$id_grupo."'";
		$result=mysql_query($sql_update);
		if (! $result)
			echo "<h3 class='error'>".$lang_label["modify_group_no"]."</h3>";
		else
			echo "<h3 class='suc'>".$lang_label["modify_group_ok"]."</h3>";
	}
	
	if (isset($_GET["borrar_grupo"])){ // if delete
		$id_borrar_modulo = entrada_limpia($_GET["id_grupo"]);
		
		// First delete from tagente_modulo
		$sql_delete= "DELETE FROM tgrupo WHERE id_grupo = ".$id_borrar_modulo;
		$result=mysql_query($sql_delete);
		if (! $result)
			echo "<h3 class='error'>".$lang_label["delete_group_no"]."</h3>"; 
		else
			echo "<h3 class='suc'>".$lang_label["delete_group_ok"]."</h3>";
	}
	echo "<h2>".$lang_label["group_management"]."</h2>";	
	echo "
		<h3>".$lang_label["definedgroups"]."
		<a href='help/".$help_code."/chap3.php#31' target='_help' class='help'>
		<span>".$lang_label["help"]."</span></a>
		</h3>";

	echo "<table cellpadding=3 cellspacing=3 width=400>";
	echo "<th>".$lang_label["icon"]."</th>";
	echo "<th>".$lang_label["group_name"]."</th>";
	echo "<th>".$lang_label["parent"]."</th>";
	echo "<th>".$lang_label["delete"]."</th>";
	$sql1='SELECT * FROM tgrupo ORDER BY nombre';
	$result=mysql_query($sql1);
	$color=0;
	while ($row=mysql_fetch_array($result)){
		if ($color == 1){
			$tdcolor = "datos";
			$color = 0;
			}
		else {
			$tdcolor = "datos2";
			$color = 1;
		}
		if ($row["id_grupo"] != 1){
			echo "
			<tr>
				<td class='$tdcolor' align='center'>
				<img src='images/groups_small/".$row["icon"].".png'
				border='0'>
				</td>
				<td class='$tdcolor'>
				<b><a href='index.php?sec=gagente&
				sec2=godmode/grupos/configurar_grupo&
				id_grupo=".$row["id_grupo"]."'>".$row["nombre"]."</a>
				</b></td>
				<td class='$tdcolor'>
				".dame_nombre_grupo ($row["parent"])."
				</td>
				<td class='$tdcolor' align='center'>
				<a href='index.php?sec=gagente&
				sec2=godmode/grupos/lista_grupos&
				id_grupo=".$row["id_grupo"]."&
				borrar_grupo=".$row["id_grupo"]."' 
				onClick='if (!confirm(\' ".$lang_label["are_you_sure"]."\')) 
				return false;'>
				<img border='0' src='images/cross.png'></a>
				</td>
			</tr>";
		}
	}
	echo "<tr><td colspan='4'><div class='raya'></div></td></tr>";
	echo "<tr><td colspan='4' align='right'>";
	echo "<form method=post action='index.php?sec=gagente&
	sec2=godmode/grupos/configurar_grupo&creacion_grupo=1'>";
	echo "<input type='submit' class='sub next' name='crt' value='".$lang_label["create_group"]."'>";
	echo "</form></td></tr></table>";

   } // Fin pagina
   else {
			audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access Group Management");
			require ("general/noaccess.php");
        }

?>