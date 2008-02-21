<?php

// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@gmail.com
// Main PHP/SQL code development and project architecture and management
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
if ((comprueba_login() != 0) || (give_acl($id_user, 0, "PM")!=1)) {
	audit_db($id_user,$REMOTE_ADDR, "ACL Violation",
	"Trying to access Agent Management");
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
		echo "<h3 class='suc'>".$lang_label["delete_ok"]."</h3>";
	else
		echo "<h3 class='suc'>".$lang_label["delete_no"]."</h3>";
}


// --------------------------------
// UPDATE A RECON TASK
// --------------------------------
if (isset($_GET["update"])) {
	$id = entrada_limpia($_GET["update"]);
	$name = entrada_limpia($_POST["name"]);
	$network = entrada_limpia($_POST["network"]);
	$description = entrada_limpia($_POST["description"]);
	$id_server = entrada_limpia($_POST["id_server"]);
	$type = entrada_limpia($_POST["type"]);
	$server_assigned = entrada_limpia($_POST["server_assigned"]);
	$interval = entrada_limpia($_POST["interval"]);
	$id_group = entrada_limpia($_POST["id_group"]);
	$create_incident = entrada_limpia($_POST["create_incident"]);
	$id_network_profile = entrada_limpia($_POST["id_network_profile"]);
	$sql = "UPDATE trecon_task SET name = '".$name."', subnet = '".$network."', description='".$description."', type = $type, id_network_server = $id_server,  create_incident = $create_incident, id_group = $id_group, interval_sweep = $interval, id_network_server_assigned = $server_assigned, id_network_profile = $id_network_profile WHERE id_rt = $id";
	$result=mysql_query($sql);
	if ($result)
		echo "<h3 class='suc'>".$lang_label["modify_ok"]."</h3>";
	else
		echo "<h3 class='suc'>".$lang_label["modify_no"]."</h3>";
}

// --------------------------------
// CREATE A RECON TASK
// --------------------------------
if (isset($_GET["create"])) {
	$name = entrada_limpia($_POST["name"]);
	$network = entrada_limpia($_POST["network"]);
	$description = entrada_limpia($_POST["description"]);
	$id_server = entrada_limpia($_POST["id_server"]);
	$type = entrada_limpia($_POST["type"]);
	$server_assigned = entrada_limpia($_POST["server_assigned"]);
	$interval = entrada_limpia($_POST["interval"]);
	$id_group = entrada_limpia($_POST["id_group"]);
	$create_incident = entrada_limpia($_POST["create_incident"]);
	$id_network_profile = entrada_limpia($_POST["id_network_profile"]);
	$sql = "INSERT INTO trecon_task (name, subnet, description, type, id_network_server, create_incident, id_group, id_network_server_assigned, id_network_profile, interval_sweep) VALUES ( '$name', '$network', '$description', $type, $id_server, $create_incident, $id_group, $server_assigned, $id_network_profile, $interval)";
	$result=mysql_query($sql);
	if ($result)
		echo "<h3 class='suc'>".$lang_label["create_ok"]."</h3>";
	else
		echo "<h3 class='suc'>".$lang_label["create_no"]."</h3>";
}

// --------------------------------
// SHOW TABLE WITH ALL RECON TASKs
// --------------------------------
echo "<h2>".$lang_label["view_servers"]." &gt; ";
echo $lang_label["manage_recontask"]."</h2>";
$query="SELECT * FROM trecon_task";
$result=mysql_query($query);
$color=1;
if (mysql_num_rows($result)){
	echo "<table cellpadding='4' cellspacing='4' witdh='720' class='databox'>";
	echo "<tr><th class='datos'>".$lang_label["name"];
	echo "<th class='datos'>".$lang_label['type'];
	echo "<th class='datos'>".$lang_label['network'];
	echo "<th class='datos'>".$lang_label['network_profile'];
	echo "<th class='datos'>".$lang_label['group'];
	echo "<th class='datos'>".$lang_label['incident'];
	echo "<th class='datos'>".$lang_label['interval'];
	echo "<th class='datos'>".$lang_label['network_server'];
	echo "<th class='datos'>X";
}
while ($row=mysql_fetch_array($result)){
	$id_rt = $row["id_rt"];
	$name = $row["name"];
	$network = $row["subnet"];
	$description = $row["description"];
//	$id_server = $row["server"];
	$type = $row["type"];
	$id_network_server = $row["id_network_server"];
	$interval = $row["interval_sweep"];
	$id_group = $row["id_group"];
	$create_incident = $row["create_incident"];
	$id_network_profile = $row["id_network_profile"];
	$id_network_server_assigned = $row["id_network_server_assigned"];
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
		echo $lang_label["yes"];
	else
		echo $lang_label["no"];
	// INTERVAL
	echo "</td><td class='$tdcolor' align='center'>";
	echo human_time_description_raw($interval);

	// Network server assigned
	echo "</td><td class='$tdcolor'f9 >";
	echo "<a href='index.php?sec=gserver&sec2=godmode/servers/modificar_server&server=$id_network_server_assigned'>".give_server_name($id_network_server_assigned)."</a>";;

	// ACTION
	echo "</td><td class='".$tdcolor."' align='center'><a href='index.php?sec=gservers&sec2=godmode/servers/manage_recontask&delete=$id_rt'><img src='images/cross.png' border='0'></td></tr>";
}
echo "</table>";

if (!mysql_num_rows($result)){
	echo "<div class='nf'>".$lang_label["no_rtask"]."</div>";
}	

echo "<table width='680'>";
echo "<tr><td align='right'>";
echo "<form method='post' action='index.php?sec=gservers&sec2=godmode/servers/manage_recontask_form&create'>";
echo "<input type='submit' class='sub next' name='crt' value='".$lang_label["create"]."'>";
echo "</form></table>";

?>
