<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnológicas, http://www.artica.es
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
		"Trying to access Recon Task Management");
	require ("general/noaccess.php");
	exit;
}

// --------------------------------
// DELETE A RECON TASKs
// --------------------------------
if (isset($_GET["delete"])) {
	$id = entrada_limpia($_GET["delete"]);
	$sql = "DELETE FROM trecon_task WHERE id_rt = $id ";
	$result = mysql_query($sql);
	if ($result)
		echo "<h3 class='suc'>".__('Deleted successfully')."</h3>";
	else
		echo "<h3 class='suc'>".__('Not deleted. Error deleting data')."</h3>";
}


if ((isset($_GET["update"])) OR ((isset($_GET["create"])))){
	$name = entrada_limpia($_POST["name"]);
	$network = entrada_limpia($_POST["network"]);
	$description = entrada_limpia($_POST["description"]);
	$id_recon_server = entrada_limpia($_POST["id_recon_server"]);
	$interval = entrada_limpia($_POST["interval"]);
	$id_group = entrada_limpia($_POST["id_group"]);
	$create_incident = entrada_limpia($_POST["create_incident"]);
	$id_network_profile = entrada_limpia($_POST["id_network_profile"]);
	$id_os = get_parameter ("id_os", 10);

}

// --------------------------------
// UPDATE A RECON TASK
// --------------------------------
if (isset($_GET["update"])) {
	$id = entrada_limpia($_GET["update"]);
	$sql = "UPDATE trecon_task SET id_os = $id_os, name = '$name', subnet = '$network', 
			description='$description', id_recon_server = $id_recon_server,
			create_incident = $create_incident, id_group = $id_group, interval_sweep = $interval,
			id_network_profile = $id_network_profile WHERE id_rt = $id";
	$result=mysql_query($sql);
	if ($result)
		echo "<h3 class='suc'>".__('Updated successfully')."</h3>";
	else
		echo "<h3 class='suc'>".__('Not updated. Error updating data')."</h3>";
}

// --------------------------------
// CREATE A RECON TASK
// --------------------------------
if (isset($_GET["create"])) {
	$sql = "INSERT INTO trecon_task (name, subnet, description, id_recon_server, create_incident, id_group, id_network_profile, interval_sweep, id_os) VALUES ( '$name', '$network', '$description', $id_recon_server, $create_incident, $id_group,  $id_network_profile, $interval, $id_os)";
	$result=mysql_query($sql);
	if ($result)
		echo "<h3 class='suc'>".__('Created successfully')."</h3>";
	else
		echo "<h3 class='suc'>".__('Not created. Error inserting data')."</h3>";
}

// --------------------------------
// SHOW TABLE WITH ALL RECON TASKs
// --------------------------------
echo "<h2>".__('Pandora servers')." &gt; ";
echo __('Manage recontask')."</h2>";
$query="SELECT * FROM trecon_task";
$result=mysql_query($query);
$color=1;
if (mysql_num_rows($result)){
	echo "<table cellpadding='4' cellspacing='4' width='700' class='databox'>";
	echo "<tr><th class='datos'>".__('Name');
	echo "<th class='datos'>".__('Type');
	echo "<th class='datos'>".__('Network');
	echo "<th class='datos'>".__('Network profile');
	echo "<th class='datos'>".__('Group');
	echo "<th class='datos'>".__('Incident');
	echo "<th class='datos'>".__('OS');
	echo "<th class='datos'>".__('Interval');
	echo "<th class='datos'>".__('Action');
}
while ($row=mysql_fetch_array($result)){
	$id_rt = $row["id_rt"];
	$name = $row["name"];
	$network = $row["subnet"];
	$description = $row["description"];
//	$id_server = $row["server"];
	$type = $row["type"];
	$id_recon_server = $row["id_recon_server"];
	$interval = $row["interval_sweep"];
	$id_group = $row["id_group"];
	$create_incident = $row["create_incident"];
	$id_network_profile = $row["id_network_profile"];
	$id_os = $row["id_os"];

	if ($color == 1){
		$tdcolor = "datos";
		$color = 0;
	}
	else {
		$tdcolor = "datos2";
		$color = 1;
	}
	echo "<tr>";
	echo "<td class='$tdcolor'>";
	echo "<a href='index.php?sec=gservers&sec2=godmode/servers/manage_recontask_form&update=$id_rt'><b>$name</b></A>";
	
	echo "</td><td class='$tdcolor'>";
	if ($type ==1)
		echo "ICMP";

	// Network
	echo "</td><td class='$tdcolor'>";
	echo $network;

	// Network profile name
	echo "</td><td class='$tdcolor'>";
	echo "<a href='index.php?sec=gmodules&sec2=godmode/modules/manage_network_templates&id=$id_network_profile'>".give_network_profile_name($id_network_profile)."</a>";

	// GROUP
	echo "</td><td class='$tdcolor' align='center'>";
	echo "<img class='bot' src='images/groups_small/".show_icon_group($id_group).".png' alt=''>";

	// INCIDENT
	echo "</td><td class='$tdcolor'>";
	if ($create_incident == 1)
		echo __('Yes');
	else
		echo __('No');

	// OS
	echo "</td><td class='$tdcolor'>";
	if ($id_os > 0){
		$icon = get_db_sql ("SELECT icon_name FROM tconfig_os WHERE id_os = $id_os");
		echo "<img src='images/$icon'>";
	}

	// INTERVAL
	echo "</td><td class='$tdcolor' align='center'>";
	echo human_time_description_raw($interval);

	// ACTION
	echo "</td><td class='".$tdcolor."' align='center'><a href='index.php?sec=gservers&sec2=godmode/servers/manage_recontask&delete=$id_rt'><img src='images/cross.png' border='0'>";
	echo "&nbsp;&nbsp;";
	echo "<a href='index.php?sec=gservers&sec2=godmode/servers/manage_recontask_form&update=$id_rt'><img src='images/config.png'></A>";
	echo "</td></tr>";
}
echo "</table>";

if (!mysql_num_rows($result)){
	echo "<div class='nf'>".__('There are no recon task configured')."</div>";
}	

echo "<table width='700'>";
echo "<tr><td align='right'>";
echo "<form method='post' action='index.php?sec=gservers&sec2=godmode/servers/manage_recontask_form&create'>";
echo "<input type='submit' class='sub next' name='crt' value='".__('Create')."'>";
echo "</form></table>";

?>
