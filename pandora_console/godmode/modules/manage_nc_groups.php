<?PHP
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
else
	$id_user = "";

if (give_acl($id_user, 0, "PM")!=1) {
	audit_db($id_user,$REMOTE_ADDR, "ACL Violation",
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
		echo "<h3 class='error'>".$lang_label["create_no"]."</h3>";
	else {
		echo "<h3 class='suc'>".$lang_label["create_ok"]."</h3>";
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
		echo "<h3 class='error'>".$lang_label["modify_no"]."</h3>";
	else
		echo "<h3 class='suc'>".$lang_label["modify_ok"]."</h3>";
}

if (isset($_GET["delete"])){ // if delete
	$id_sg = entrada_limpia ($_GET["id_sg"]);
	$sql_delete= "DELETE FROM tnetwork_component_group WHERE id_sg = ".$id_sg;
	$result=mysql_query($sql_delete);
	if (! $result)
		echo "<h3 class='error'>".$lang_label["delete_no"]."</h3>";
	else
		echo "<h3 class='suc'>".$lang_label["delete_ok"]."</h3>";
	
	$result=mysql_query($sql_delete);
}
echo "<h2>".$lang_label["module_management"]." &gt; ";
echo $lang_label["network_component_group_management"]."</h2>";

echo "<table cellpadding=4 cellspacing=4 width=550>";
echo "<th>".$lang_label["name"]."</th>";
echo "<th>".$lang_label["parent"]."</th>";
echo "<th>".$lang_label["delete"]."</th>";
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
			<b><a href='index.php?sec=gmodules&sec2=godmode/modules/manage_nc_groups_form&edit=1&id_sg=".$row["id_sg"]."'>".$row["name"]."</A></B>
			</td>
			<td class='$tdcolor'>
			".give_network_component_group_name ($row["parent"])."
			</td>
			<td class='$tdcolor' align='center'>
			<a href='index.php?sec=gmodules&sec2=godmode/modules/manage_nc_groups&delete=1&id_sg=".$row["id_sg"]."'
				onClick='if (!confirm(\' ".$lang_label["are_you_sure"]."\'))
			return false;'>
			<img border='0' src='images/cross.png'></a>
			</td>
		</tr>";

}
echo "<tr><td colspan='3'><div class='raya'></div></td></tr>";
echo "<tr><td colspan='3' align='right'>";
echo "<form method=post action='index.php?sec=gmodules&sec2=godmode/modules/manage_nc_groups_form&create=1'>";
echo "<input type='submit' class='sub next' name='crt' value='".$lang_label["create"]."'>";
echo "</form></td></tr></table>";

?>