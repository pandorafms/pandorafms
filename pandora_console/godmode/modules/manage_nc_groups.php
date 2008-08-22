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
require("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "PM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access SNMP Group Management");
	require ("general/noaccess.php");
	exit;
}
   
if (isset($_GET["create"])){ // Create module
	$name = entrada_limpia ($_POST["name"]);
	$parent = entrada_limpia ($_POST["parent"]);
	$sql_insert="INSERT INTO tnetwork_component_group (name,parent)
	VALUES ('$name', '$parent')";
	$result=mysql_query($sql_insert);
	if (! $result)
		echo "<h3 class='error'>".__('Not created. Error inserting data')."</h3>";
	else {
		echo "<h3 class='suc'>".__('Created successfully')."</h3>";
		$id_sg = mysql_insert_id();
	}
}

if (isset($_GET["update"])){ // if modified any parameter
	$id_sg = entrada_limpia ($_GET["id_sg"]);
	$name = entrada_limpia ($_POST["name"]);
	$parent = entrada_limpia ($_POST["parent"]);
	$sql_update ="UPDATE tnetwork_component_group
	SET name = '$name', parent = '$parent'
	WHERE id_sg = '$id_sg'";
	$result=mysql_query($sql_update);
	if (! $result)
		echo "<h3 class='error'>".__('Not updated. Error updating data')."</h3>";
	else
		echo "<h3 class='suc'>".__('Updated successfully')."</h3>";
}

if (isset($_GET["delete"])){ // if delete
	$id_sg = entrada_limpia ($_GET["id_sg"]);
	$sql_delete= "DELETE FROM tnetwork_component_group WHERE id_sg = ".$id_sg;
	$result=mysql_query($sql_delete);
	if (! $result)
		echo "<h3 class='error'>".__('Not deleted. Error deleting data')."</h3>";
	else
		echo "<h3 class='suc'>".__('Deleted successfully')."</h3>";
	
	$result=mysql_query($sql_delete);
}
echo "<h2>".__('Module management')." &gt; ";
echo __('Component group management')."</h2>";

echo "<table cellpadding='4' cellspacing='4' width='550' class='databox'>";
echo "<th>".__('Name')."</th>";
echo "<th>".__('Parent')."</th>";
echo "<th>".__('Delete')."</th>";
$sql1='SELECT * FROM tnetwork_component_group ORDER BY parent';
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
	echo "<tr>
			<td class='$tdcolor'>
			<b><a href='index.php?sec=gmodules&sec2=godmode/modules/manage_nc_groups_form&edit=1&id_sg=".$row["id_sg"]."'>".$row["name"]."</a></b>
			</td>
			<td class='$tdcolor'>
			".give_network_component_group_name ($row["parent"])."
			</td>
			<td class='$tdcolor' align='center'>
			<a href='index.php?sec=gmodules&sec2=godmode/modules/manage_nc_groups&delete=1&id_sg=".$row["id_sg"]."'
				onClick='if (!confirm(\' ".__('Are you sure?')."\'))
			return false;'>
			<img border='0' src='images/cross.png'></a>
			</td>
		</tr>";

}
echo "</table>";
echo '<table width="550">';
echo '<tr><td align="right">';
echo "<form method=post action='index.php?sec=gmodules&sec2=godmode/modules/manage_nc_groups_form&create=1'>";
echo "<input type='submit' class='sub next' name='crt' value='".__('Create')."'>";
echo "</form></td></tr></table>";

?>
