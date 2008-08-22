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

// Load globar vars
require("include/config.php");

check_login ();
if (! give_acl ($config['id_user'], 0, "UM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access User Management");
	require ("general/noaccess.php");
}
if (isset($_GET["borrar_usuario"])) { // if delete user
	$nombre= entrada_limpia($_GET["borrar_usuario"]);
	// Delete user
	// Delete cols from table tgrupo_usuario
	
	$sql = "DELETE FROM tgrupo_usuario WHERE usuario = '".$nombre."'";
	$result = mysql_query ($sql);
	$sql = "DELETE FROM tusuario WHERE id_usuario = '".$nombre."'";
	$result = mysql_query ($sql);
	if (! $result)
		echo "<h3 class='error'>".__('There was a problem deleting user')."</h3>";
	else
		echo "<h3 class='suc'>".__('User successfully deleted')."</h3>";
}
?>

<h2><?php echo __('User management') ?> &gt; 
<?php echo __('Users defined in Pandora') ?></h2>
 
<table width="700" cellpadding="4" cellspacing="4" class="databox">
<th width="80px"><?php echo __('UserID')?></th>
<th width="155px"><?php echo __('Last contact')?></th>
<th width="45px"><?php echo __('Profile')?></th>
<th width="120px"><?php echo __('Name')?></th>
<th><?php echo __('Description')?></th>
<th width="30px"><?php echo __('Delete')?></th>

<?php
$sql = "SELECT * FROM tusuario";
$resq1 = mysql_query ($sql);
// Init vars
$nombre = "";
$nivel = "";
$comentarios = "";
$fecha_registro = "";
$color=1;

while ($rowdup = mysql_fetch_array ($resq1)) {
	$name = $rowdup["id_usuario"];
	$nivel = $rowdup["nivel"];
	$real_name = $rowdup["nombre_real"];
	$comments = $rowdup["comentarios"];
	$fecha_registro = $rowdup["fecha_registro"];
	if ($color == 1){
		$tdcolor = "datos";
		$tip= "tip";
		$color = 0;
	}
	else {
		$tdcolor = "datos2";
		$tip= "tip2";
		$color = 1;
	}
	echo "<tr><td class='$tdcolor'>";
	echo "<a href='index.php?sec=gusuarios&sec2=godmode/users/configure_user&id_usuario_mio=".$name."'><b>".$name."</b></a>";
	echo "<td class='$tdcolor'>".$fecha_registro;
	echo "<td class='$tdcolor'>";
	if ($nivel == 1) 
		echo "<img src='images/user_suit.png'>";
	else
		echo "<img src='images/user_green.png'>";
	
	$sql = 'SELECT * FROM tusuario_perfil WHERE id_usuario = "'.$name.'"';
	$result = mysql_query ($sql);
	echo "<a href='#' class='$tip'>&nbsp;<span>";
	if (mysql_num_rows ($result)) {
		while ($row = mysql_fetch_array ($result)) {
			echo dame_perfil ($row["id_perfil"])."/ ";
			echo dame_grupo ($row["id_grupo"])."<br>";
		}
	} else {
		echo __('This user doesn\'t have any assigned profile/group');
	}
	echo "</span></a>";
	
	echo "<td class='$tdcolor' width='100'>".substr ($real_name, 0, 16)."</td>";
	echo "<td class='$tdcolor'>".$comments."</td>";
	echo "<td class='$tdcolor' align='center'><a href='index.php?sec=gagente&sec2=godmode/users/user_list&borrar_usuario=".$name."' onClick='if (!confirm(\' ".__('Are you sure?')."\')) return false;'><img border='0' src='images/cross.png'></a></td>";
}
echo "</tr></table>";
echo "<table width=700>";
echo "<tr><td align='right'>";
echo "<form method=post action='index.php?sec=gusuarios&sec2=godmode/users/configure_user&alta=1'>";
echo "<input type='submit' class='sub next' name='crt' value='".__('Create user')."'>";
echo "</form></td></tr></table>";

echo "</table>";
?>
