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
if ( (give_acl($id_user, 0, "LM")==1)){
	// Var init
	$descripcion = "";
	$nombre = "";
	$comando ="";
	
	if (isset($_GET["id_alerta"])){
		$id_alerta = entrada_limpia($_GET["id_alerta"]);
		$sql1='SELECT * FROM talerta WHERE id_alerta = '.$id_alerta;
		$result=mysql_query($sql1);
		if ($row=mysql_fetch_array($result)){
			$descripcion = $row["descripcion"];
			$nombre= $row["nombre"];
			$comando = $row["comando"];
		} else
			{
			echo "<h3 class='error'>".$lang_label["alert_error"]."</h3>";
			echo "</table>";
			include ("general/footer.php");
			exit;
		}
	}
	
	$creacion_alerta = 0;
	if (isset($_GET["creacion"])){
		$creacion_alerta = 1;
	}
	
echo "<h2>".$lang_label["alert_config"]." &gt; ";
	if (isset($_GET["creacion"])){
		echo $lang_label["create_alert"];
	}
	if (isset($_GET["id_alerta"])){
		echo $lang_label["mod_alert"];
	}
pandora_help ("manage_alerts");
echo "</h2>";
 ?>
 <form name="alerta" method="post" action="index.php?sec=galertas&sec2=godmode/alerts/modify_alert&id_alerta=<?php echo $id_alerta ?>">

<?php
	if ($creacion_alerta == 1)
		echo "<input type='hidden' name='crear_alerta' value='1'>";
	else {
		echo "<input type='hidden' name='update_alerta' value='1'>";
		echo "<input type='hidden' name='id_alerta' value='".$id_alerta."'>";
	}
?>
<table width="500" cellspacing="4" cellpadding="4" class="databox_color">
<tr><td class="datos"><?php echo $lang_label["alertname"] ?></td>
<td class="datos">
<input type="text" name="nombre" size=30 value="<?php echo $nombre ?>"></td>
</tr>
<tr>
<td class="datos2"><?php echo $lang_label["command"] ?></td>
<td class="datos2">
<input type="text" name="comando" size="50" value="<?php echo $comando ?>">
</td></tr>
<tr><td class="datos"><?php echo $lang_label["description"] ?></td>
<td class="datos"><textarea name="descripcion" cols="50" rows="7">
<?php echo $descripcion ?>
</textarea>
</td></tr>
</table>
<table width=500>
<tr><td align="right">
<?php 
if (isset($_GET["creacion"])){
	echo "<input name='crtbutton' type='submit' class='sub wand' value='".$lang_label["create"]."'>";
} else {
	echo "<input name='uptbutton' type='submit' class='sub upd' value='".$lang_label["update"]."'>";
} ?>
</form>

</table>

<?php
} // end page
else {
		audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access Alert Management");
		require ("general/noaccess.php");
	}	
?>
