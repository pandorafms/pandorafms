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
	
echo "<h2>".$lang_label["alert_config"]."</h2>";
echo "<h3>";
	if (isset($_GET["creacion"])){
		echo $lang_label["create_alert"];
	}
	if (isset($_GET["id_alerta"])){
		echo $lang_label["mod_alert"];
	}
	echo '<a href="help/'.$help_code.'/chap3.php#3221" target="_help" class="help">
	<span>'.$lang_label["help"].'</span></a>';
echo "</h3>";
 ?>
<table width="500" cellspacing="3" cellpadding="3">

<form name="alerta" method="post" action="index.php?sec=galertas&sec2=godmode/alertas/modificar_alerta&id_alerta=<?php echo $id_alerta ?>">

<?php
	if ($creacion_alerta == 1)
		echo "<input type='hidden' name='crear_alerta' value='1'>";
	else {
		echo "<input type='hidden' name='update_alerta' value='1'>";
		echo "<input type='hidden' name='id_alerta' value='".$id_alerta."'>";
	}
?>
<tr><td class='lb' rowspan='3' width='5'>
<td class="datos"><?php echo $lang_label["alertname"] ?>
<td class="datos"><input type="text" name="nombre" size=30 value="<?php echo $nombre ?>">

<tr>
<td class="datos2"><?php echo $lang_label["command"] ?>
<td class="datos2"><input type="text" name="comando" size="50" value="<?php echo $comando ?>">
<a href='#' class='tip'>&nbsp;<span>
<b>Macros:</b><br>
_field1_<br>
_field2_<br>
_field3_<br>
_agent_<br>
_timestamp_<br>
_data_<br>
</span></a>

<tr><td class="datos"><?php echo $lang_label["description"] ?>
<td class="datos"><textarea name="descripcion" cols="50" rows="7">
<?php echo $descripcion ?>
</textarea>
<tr><td colspan='3'><div class='raya'></div></td></tr>
<tr><td colspan="3" align="right">
<?php 
if (isset($_GET["creacion"])){
	echo "<input name='crtbutton' type='submit' class='sub' value='".$lang_label["create"]."'>";
} else {
	echo "<input name='uptbutton' type='submit' class='sub' value='".$lang_label["update"]."'>";
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