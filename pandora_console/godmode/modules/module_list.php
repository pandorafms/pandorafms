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
	if (isset($_POST["update_module"])){ // if modified any parameter
		$name = entrada_limpia($_POST["name"]);
		$id_type = entrada_limpia($_POST["id_type"]);
		$description = entrada_limpia($_POST["description"]);
		$icon = entrada_limpia($_POST["icon"]);
		$category = entrada_limpia($_POST["category"]);
	    	$sql_update ="UPDATE ttipo_modulo
	    	SET descripcion = '".$description."', categoria = '".$category."',
	    	nombre = '".$name."', icon = '".$icon."'
		WHERE id_tipo = '".$id_type."'";
		$result=mysql_query($sql_update);
		if (! $result)
			echo "<h3 class='error'>".$lang_label["modify_module_no"]."</h3>";
		else
			echo "<h3 class='suc'>".$lang_label["modify_module_ok"]."</h3>";
	}
	
	/*if (isset($_GET["borrar_grupo"])){ // if delete
		$id_borrar_modulo = entrada_limpia($_GET["id_grupo"]);
		
		// First delete from tagente_modulo
		$sql_delete= "DELETE FROM tgrupo WHERE id_grupo = ".$id_borrar_modulo;
		$result=mysql_query($sql_delete);
		if (! $result)
			echo "<h3 class='error'>".$lang_label["delete_group_no"]."</h3>"; 
		else
			echo "<h3 class='suc'>".$lang_label["delete_group_ok"]."</h3>";
	}*/
	echo "<h2>".$lang_label["module_management"]." &gt; ";
	echo $lang_label["defined_modules"]."</h2>";

	echo "<table cellpadding='4' cellspacing='4' width='750' class='databox'>";
	echo "<th>".$lang_label["icon"]."</th>";
	echo "<th>".$lang_label["name"]."</th>";
	echo "<th>".$lang_label["description"]."</th>";
	$sql1='SELECT * FROM ttipo_modulo ORDER BY nombre';
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
		echo "
		<tr>
			<td class='$tdcolor' align='center'>
			<img src='images/".$row["icon"]."' 
			border='0'>
			</td>
			<td class='$tdcolor'>
			<b>".$row["nombre"]."
			</b></td>
			<td class='$tdcolor'>
			".$row["descripcion"]."
			</td>
		</tr>";
	}
	echo "</table>";
	
	/* not used yet 
	echo "<table>";
	echo "<tr><td align='right'><form method=post action='index.php?sec=gmodules&
	sec2=godmode/modules/manage_modules&create=1'>";

	echo "<input type='submit' class='sub next' name='crt' value='".$lang_label["create_module"]."'>";
	echo "</form></td></tr></table>
	*/

} // Fin pagina
else {
	audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access module management");
	require ("general/noaccess.php");
}
?>
