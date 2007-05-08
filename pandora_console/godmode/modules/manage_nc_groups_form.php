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
	"Trying to access SNMO Groups Management");
	require ("general/noaccess.php");
	exit;
}
  
if (isset($_GET["edit"])){ // Edit mode
	$id_sg = entrada_limpia ($_GET["id_sg"]);
	$sql1 = "SELECT * FROM tnetwork_component_group where id_sg = $id_sg";
	$result=mysql_query($sql1);
	$row=mysql_fetch_array($result);
	$name = $row["name"];
	$parent = $row["parent"];
} elseif (isset($_GET["create"])){
	$id_sg = -1;
	$name = "";
	$parent = "";
}

echo "<h2>".$lang_label["network_component_group_management"]."</h2>";
echo '<table width="500" cellspacing="4" cellpadding="4">';

// Different Form url if it's a create or if it's a update form
if ($id_sg != -1)
	echo "<form name='snmp_c' method='post' action='http://pandora.localhost/index.php?sec=gmodules&sec2=godmode/modules/manage_nc_groups&update=1&id_sg=$id_sg'>";
else
	echo "<form name='snmp_c' method='post' action='http://pandora.localhost/index.php?sec=gmodules&sec2=godmode/modules/manage_nc_groups&create=1'>";
	
echo "<tr><td class='lb' rowspan='3' width='3'>";
echo "<td class='datos'>".$lang_label["name"];
echo "<td class='datos'><input type='text' name='name' size=30 value='$name'>";

echo "<tr>";
echo "<td class='datos2'>".$lang_label["parent"];
echo "<td class='datos2'>";
echo "<select name='parent'>";
echo "<option value='$parent'>".give_network_component_group_name($parent);
$sql1 = "SELECT * FROM tnetwork_component_group where id_sg != '$parent'";
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result))
	echo "<option value='".$row["id_sg"]."'>".give_network_component_group_name($row["id_sg"]);
echo "</select>";


echo "	<tr><td colspan='3'><div class='raya'></div></td></tr>
	<tr><td colspan='3' align='right'>";

if ($id_sg == -1)
	echo "<input name='crtbutton' type='submit' class='sub wand' value='".$lang_label["create"]."'>";
else
	echo "<input name='uptbutton' type='submit' class='sub upd' value='".$lang_label["update"]."'>";

echo "</form></table>";

?>