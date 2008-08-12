<?php
// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@openideas.info
// Copyright (c) 2005-2007 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation version 2
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
		"Trying to access Network Profile Management");
	require ("general/noaccess.php");
	exit;
}


if (isset($_GET["delete_module"])){ // Delete module from profile
	$id_npc = $_GET["delete_module"];
	$sql1="DELETE FROM tnetwork_profile_component WHERE id_npc = $id_npc";
	$result=mysql_query($sql1);
	if (! $result)
		echo "<h3 class='error'>".__('Not deleted. Error deleting data')."</h3>";
	else {
		echo "<h3 class='suc'>".__('Deleted successfully')."</h3>";
	}
}

if (isset($_GET["add_module"])){ // Add module to profile
	$id_nc = $_POST["component"];
	$id_np = $_GET["id_np"];
	$sql1="INSERT INTO tnetwork_profile_component (id_np,id_nc) VALUES ($id_np, $id_nc)";
	$result=mysql_query($sql1);
	if (! $result)
		echo "<h3 class='error'>".__('Not created. Error inserting data')."</h3>";
	else {
		echo "<h3 class='suc'>".__('Created successfully')."</h3>";
	}
}

$ncgroup = -1;

if (isset($_GET["refresh_module"])){  //Refresh module info from group combo
	$ncgroup = $_POST["ncgroup"];
}


if (isset($_GET["id_np"])){ // Read module data
	$id_np = $_GET["id_np"];
	if ($id_np != -1){
		$sql1="SELECT * FROM tnetwork_profile WHERE id_np = $id_np ORDER BY
        name";
		$result=mysql_query($sql1);
		$row=mysql_fetch_array($result);
		$description = $row["description"];
		$name = $row["name"];
	} else  {
		$comentarios = "";
		$name = "";
	}
}

if (isset($_GET["create"])){ // Create module
	$name = entrada_limpia ($_POST["name"]);
	$description = entrada_limpia ($_POST["description"]);
	$sql_insert="INSERT INTO tnetwork_profile (name,description)
	VALUES ('$name', '$description')";
	$result=mysql_query($sql_insert);
	if (! $result)
		echo "<h3 class='error'>".__('Not created. Error inserting data')."</h3>";
	else {
		echo "<h3 class='suc'>".__('Created successfully')."</h3>";
		$id_np = mysql_insert_id();
	}
}

if (isset($_GET["update"])){ // Update profile
	$id_np = $_GET["update"];
	$name = entrada_limpia ($_POST["name"]);
	$description = entrada_limpia ($_POST["description"]);
	$sql_insert="UPDATE tnetwork_profile set name = '$name', description = '$description' WHERE id_np = $id_np";
	$result=mysql_query($sql_insert);
	if (! $result)
		echo "<h3 class='error'>".__('Not updated. Error updating data')."</h3>";
	else {
		echo "<h3 class='suc'>".__('Updated successfully')."</h3>";
	}
}

echo "<h2>".__('Module management')." &gt; ";
echo __('Module template management')."</h2>";
echo "<table width='550' cellpadding='4' cellspacing='4' class='databox_color'>";

if ($id_np == -1)
	echo '<form name="new_user" method="post" action="index.php?sec=gmodules&sec2=godmode/modules/manage_network_templates_form&create=1">';
else
	echo '<form name="user_mod" method="post" action="index.php?sec=gmodules&sec2=godmode/modules/manage_network_templates_form&update='.$id_np.'">';

echo "<tr><td class='datos'>".__('Name')."</td>";
echo "<td class='datos'>";
echo "<input type='text' size=25 name='name' value='$name'>";
echo "</td>";
echo "<tr><td class='datos2'>".__('Description')."</td>";
echo "<td class='datos2'>";
echo "<textarea cols=50 rows=2 name='description'>";
if (isset($description)) {
	echo $description;
}
echo "</textarea>";
echo "</td></tr></table>";

if ($id_np != -1){
	// Show associated modules, allow to delete, and to add

	$sql1 = "SELECT * FROM tnetwork_profile_component where id_np = $id_np";
	$result = mysql_query ($sql1);
	if ($row=mysql_num_rows($result)) {
	
	echo '<table width="550" cellpadding="4" cellspacing="4" class="databox">';
	echo '<tr>';
	echo "<th>".__('Module name')."</th>";
	echo "<th>".__('Type')."</th>";
	echo "<th>".__('Description')."</th>";
	echo "<th>".__('NC.Group')."</th>";
 	echo "<th>X</th>";
	$color =0;

	while ( $row = mysql_fetch_array($result)) {
		$id_nc = $row["id_nc"];
		$id_npc = $row["id_npc"];
		$sql2 = "SELECT * FROM tnetwork_component where id_nc = $id_nc ORDER BY name";
		$result2 = mysql_query ($sql2);
		if ($row2=mysql_fetch_array($result2)){
			if ($color == 1){
				$tdcolor="datos";
				$color =0;
			} else {
				$tdcolor="datos2";
				$color =1;
			}
			$id_tipo = $row2["type"];
			$id_group = $row2["id_group"];
			$nombre_modulo =$row2["name"];
			$description = $row2["description"];
			$module_group2 = $row2["id_module_group"];

			echo "<tr><td class='".$tdcolor."_id'>";
			echo $nombre_modulo;
			echo "<td class='".$tdcolor."f9'>";
			if ($id_tipo > 0) {
				echo "<img src='images/".show_icon_type($id_tipo)."' border=0>";
			}
			echo "<td class='$tdcolor'>".substr($description,0,30)."</td>";
			echo "<td class='$tdcolor'>".give_network_component_group_name($id_group)."</td>";
			echo "<td class='$tdcolor'><a href='index.php?sec=gmodules&sec2=godmode/modules/manage_network_templates_form&id_np=$id_np&delete_module=$id_npc'><img src='images/cross.png'></a></td>";
		}
	}
	} else {
		echo "<div class='nf'>No modules</div>";
	}
	echo "</table>";
}

echo "<table width='550'>";
echo '<tr><td align="right">';
if ($id_np == -1)
	echo '<input name="crtbutton" type="submit" class="sub wand" value="'.__('Create').'">';
else
	echo '<input name="updbutton" type="submit" class="sub upd" value="'.__('Update').'">';
echo "</td></tr></table>";
echo "</form>";


if ($id_np != -1){ 
	echo "<h3>".__('Add')." ".__('Module')."</h3>";
	echo "<table class='databox'>";
	echo '<tr><td>';
	echo '<form name="add_module" method="post" action="index.php?sec=gmodules&sec2=godmode/modules/manage_network_templates_form&id_np='.$id_np.'&add_module=1">';
	$sql1 = "SELECT * FROM tnetwork_component ORDER BY id_group, name";
	$result = mysql_query ($sql1);
	echo "<select name='component'>";
	while ($row = mysql_fetch_array($result)) {
		echo "<option value='" . $row["id_nc"] . "'>". $row["name"]." / ".give_network_component_group_name ($row["id_group"]);
	}
	echo "</select>";

	echo '<td valign="top">';
	echo '<input name="crtbutton" type="submit" class="sub wand" value="'.__('Add').'">';
	echo "</td></tr></table>";
	echo "</form>";
}

?>
