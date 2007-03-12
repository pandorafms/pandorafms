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
	// Inic vars
	$id_grupo = "";
	$nombre = "";
	
	
	if (isset($_GET["creacion_grupo"])){ //
		$creacion_grupo = entrada_limpia($_GET["creacion_grupo"]);
	} else
		$creacion_grupo = 0;
		
	if (isset($_GET["id_grupo"])){
		// Conecto con la BBDD
		$id_grupo = entrada_limpia($_GET["id_grupo"]);
		$sql1='SELECT nombre, icon FROM tgrupo WHERE id_grupo = '.$id_grupo;
		$result=mysql_query($sql1);
		if ($row=mysql_fetch_array($result)){
			$nombre = $row["nombre"];
			$icono = $row["icon"];
		} else
			{
			echo "<h3 class='error'>".$lang_label["group_error"]."</h3>";
			echo "</table>";                                        
			include ("general/footer.php");                       
			exit;
			}
	}

	echo "<h2>".$lang_label["group_management"]."</h2>";
	if (isset($_GET["creacion_grupo"])) {echo "<h3>".$lang_label["create_group"]."<a href='help/".$help_code."/chap3.php#31' target='_help' class='help'>&nbsp;<span>".$lang_label["help"]."</span></a></h3>";}
	if (isset($_GET["id_grupo"])) {echo "<h3>".$lang_label["update_group"]."<a href='help/".$help_code."/chap3.php#31' target='_help' class='help'>&nbsp;<span>".$lang_label["help"]."</span></a></h3>";}
	
?>
<table width="450">

<form name="grupo" method="post" action="index.php?sec=gagente&
sec2=godmode/grupos/lista_grupos">

<?php
	if ($creacion_grupo == 1)
		echo "<input type='hidden' name='crear_grupo' value='1'>";
	else {
		echo "<input type='hidden' name='update_grupo' value='1'>";
		echo "<input type='hidden' name='id_grupo' value='".$id_grupo."'>";
	}
?>
<tr><td class='lb' rowspan='3' width='5'>
<tr><td class="datos"><?php echo $lang_label["group_name"] ?></td>
<td class="datos">
<input type="text" name="nombre" size="35" value="<?php echo $nombre ?>">
</td></tr>
<tr><td class='datos2'>
<?PHP
		echo $lang_label["icon"];
		echo '<td class="datos2">';
		
		echo '<select name="icon">';

		if ($icono != ""){
			echo '<option>' . $icono;
		}
		
		$ficheros = list_files ('images/groups_small/', "png", 1, 0);
		$size = count ($ficheros);
		for ($i = 0; $i < $size; $i++) {
			echo "<option>".substr($ficheros[$i],0,strlen($ficheros[$i])-4);
		}
		echo '</select>';
?>



<tr><td colspan='3'><div class='raya'></div></td></tr>
<tr><td colspan="3" align="right">
<?php 
if (isset($_GET["creacion_grupo"])){
	echo "<input name='crtbutton' type='submit' class='sub' 
	value='".$lang_label["create"]."'>";
	} else {
	echo "<input name='uptbutton' type='submit' class='sub' 
	value='".$lang_label["update"]."'>";
	} 
?>
</form>

</table>

<?php
   } // fin pagina
else {
	audit_db($id_user,$REMOTE_ADDR, "ACL Violation",
	"Trying to access Group Management2");
	require ("general/noaccess.php");
	}
?>