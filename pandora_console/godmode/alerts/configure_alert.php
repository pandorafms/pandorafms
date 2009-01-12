<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
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


// Load global vars
require_once ("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "LM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Alert Management");
	require ("general/noaccess.php");
	exit;
}
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
		echo "<h3 class='error'>".__('There was a problem loading alert')."</h3>";
		echo "</table>";
		include ("general/footer.php");
		exit;
	}
}

$creacion_alerta = 0;
if (isset($_GET["creacion"])){
	$creacion_alerta = 1;
}
	
echo "<h2>".__('Alert configuration')." &gt; ";
if (isset($_GET["creacion"])){
	echo __('Create alert');
}
if (isset($_GET["id_alerta"])){
	echo __('Modify alert');
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
<tr><td class="datos"><?php echo __('Alert name') ?></td>
<td class="datos">
<input type="text" name="nombre" size=30 value="<?php echo $nombre ?>"></td>
</tr>
<tr>
<td class="datos2"><?php echo __('Command') ?></td>
<td class="datos2">
<input type="text" name="comando" size="50" value="<?php echo $comando ?>">
</td></tr>
<tr><td class="datos"><?php echo __('Description') ?></td>
<td class="datos"><textarea name="descripcion" cols="50" rows="7">
<?php echo $descripcion ?>
</textarea>
</td></tr>
</table>
<table width=500>
<tr><td align="right">
<?php 
if (isset($_GET["creacion"])){
	echo "<input name='crtbutton' type='submit' class='sub wand' value='".__('Create')."'>";
} else {
	echo "<input name='uptbutton' type='submit' class='sub upd' value='".__('Update')."'>";
} ?>
</form>

</table>
