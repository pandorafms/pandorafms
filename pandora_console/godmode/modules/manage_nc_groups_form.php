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
		"Trying to access SNMO Groups Management");
	require ("general/noaccess.php");
	exit;
}

$create = (bool) get_parameter ('create');
$edit = (bool) get_parameter ('edit');

if ($edit) { // Edit mode
	$id_sg = entrada_limpia ($_GET["id_sg"]);
	$sql1 = "SELECT * FROM tnetwork_component_group where id_sg = $id_sg";
	$result=mysql_query($sql1);
	$row=mysql_fetch_array($result);
	$name = $row["name"];
	$parent = $row["parent"];
} elseif ($create) {
	$id_sg = -1;
	$name = "";
	$parent = "";
}

echo "<h2>".__('Component group management')."</h2>";
echo '<table width="50%" cellspacing="4" cellpadding="4" class="databox">';

// Different Form url if it's a create or if it's a update form
if ($id_sg != -1)
	echo "<form name='snmp_c' method='post' action='index.php?sec=gmodules&sec2=godmode/modules/manage_nc_groups&update=1&id_sg=$id_sg'>";
else
	echo "<form name='snmp_c' method='post' action='index.php?sec=gmodules&sec2=godmode/modules/manage_nc_groups&create=1'>";
	
echo "<tr>";
echo "<td class='datos'>".__('Name')."</td>";
echo "<td class='datos'><input type='text' name='name' size=30 value='$name'></td>";

echo "<tr>";
echo "<td class='datos2'>".__('Parent')."</td>";
echo "<td class='datos2'>";
echo "<select name='parent'>";
echo "<option value='$parent'>".give_network_component_group_name($parent);
$sql1 = "SELECT * FROM tnetwork_component_group where id_sg != '$parent'";
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result))
	echo "<option value='".$row["id_sg"]."'>".give_network_component_group_name($row["id_sg"]);
echo "</select>";

echo "</td></tr><table>";
echo '<table width="500">';
echo '<tr><td align="right">';

if ($id_sg == -1)
	echo "<input name='crtbutton' type='submit' class='sub wand' value='".__('Create')."'>";
else
	echo "<input name='uptbutton' type='submit' class='sub upd' value='".__('Update')."'>";

echo "</form></td></tr></table>";

?>
