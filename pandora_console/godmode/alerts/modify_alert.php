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
require ("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "LM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Alert Management");
	require ("general/noaccess.php");
	return;
}
if (isset($_POST["update_alerta"])){ // if modified any parameter
	$id_alerta = entrada_limpia($_POST["id_alerta"]);
	if ($id_alerta < 4){
		audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation", "Trying to access Alert Management");
		require ("general/noaccess.php");
	}
	$nombre =  entrada_limpia($_POST["nombre"]);
	$comando =  entrada_limpia($_POST["comando"]);
	$descripcion=  entrada_limpia($_POST["descripcion"]);
	$sql_update ="UPDATE talerta SET nombre = '".$nombre."', comando = '".$comando."', descripcion = '".$descripcion."' WHERE id_alerta= '".$id_alerta."'";
	$result=mysql_query($sql_update);	
	if (! $result) {
		echo "<h3 class='error'>".__('There was a problem updating alert')."</h3>";
	} else {
		echo "<h3 class='suc'>".__('Alert successfully updated')."</h3>";
	}
}

if (isset($_POST["crear_alerta"])){ // if create alert
	$nombre =  entrada_limpia($_POST["nombre"]);
	$comando =  entrada_limpia($_POST["comando"]);
	$descripcion=  entrada_limpia($_POST["descripcion"]);
	$sql_update ="INSERT talerta (nombre, comando, descripcion) VALUES ('".$nombre."', '".$comando."', '".$descripcion."')";
	$result=mysql_query($sql_update);	
	if (! $result)
		echo "<h3 class='error'>".__('There was a problem creating alert')."</h3>";
	else
		echo "<h3 class='suc'>".__('Alert successfully created')."</h3>";  
}

if (isset($_GET["borrar_alerta"])){ // if delete alert
	$id_alerta = entrada_limpia($_GET["borrar_alerta"]);
	if ($id_alerta < 4) {
		audit_db ($config['id_user'],$REMOTE_ADDR, "ACL Violation","Trying to access Alert Management");
		require ("general/noaccess.php");
	}
	$sql_delete= "DELETE FROM talerta WHERE id_alerta = ".$id_alerta;
	$result=mysql_query($sql_delete);		
	if (! $result)
		echo "<h3 class='error'>".__('There was a problem deleting alert')."</h3>"; 
	else
		echo "<h3 class='suc'>".__('Alert successfully deleted')."</h3>"; 

	$sql_delete2 ="DELETE FROM talerta_agente_modulo WHERE id_alerta = ".$id_alerta; 
	$result=mysql_query($sql_delete2);
}

echo "<h2>".__('Alert configuration')." &gt; ";
echo __('Alerts defined in Pandora')."</h2>";
echo "<table width='500' cellpadding='4' cellspacing='4' class='databox'>";
echo "<th width='100px'>".__('Alert name')."</th>";
echo "<th>".__('Description')."</th>";
echo "<th>".__('Delete')."</th>";
$color=1;
$sql1='SELECT * FROM talerta';
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result)){
	if ($color == 1){
		$tdcolor = "datos";
		$color = 0;
		}
	else {
		$tdcolor = "datos2";
		$color = 1;
	}
	if ($row[0] > 4){
		echo "<tr><td class='$tdcolor'><b><a href='index.php?sec=galertas&sec2=godmode/alerts/configure_alert&id_alerta=".$row["id_alerta"]."'>".$row["nombre"]."</a></b></td>";
		echo "<td class='$tdcolor'>".$row["descripcion"]."</td>";
		echo "<td class='$tdcolor' align='center'><a href='index.php?sec=gagente&sec2=godmode/alerts/modify_alert&borrar_alerta=".$row["id_alerta"]."' onClick='if (!confirm(\' ".__('Are you sure?')."\')) return false;'><img border='0' src='images/cross.png'></a></td>";
	} else {
		echo "<tr><td class='$tdcolor'><b>".$row["nombre"]."</b></td>";
		echo "<td class='$tdcolor'>".$row["descripcion"]."</td>";
	}
}

echo "</tr></table>";
echo "<table width=500>";
echo "<tr><td align='right'>";
echo "<form method=post action='index.php?sec=galertas&sec2=godmode/alerts/configure_alert&creacion=1'>";
echo "<input type='submit' class='sub next' name='crt' value='".__('Create alert')."'>";
echo "</form>";
echo "</td></tr></table>";
?>
