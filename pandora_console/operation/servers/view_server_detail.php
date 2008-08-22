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
require("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "AR")) {
	audit_db ($config["id_user"], $REMOTE_ADDR, "ACL Violation",
		"Trying to access recon task viewer");
	require ("/general/noaccess.php");
	return;
}


$modules_server = 0;
$total_modules = 0;
$total_modules_data = 0;


// --------------------------------
// FORCE A RECON TASK
// --------------------------------
if (give_acl ($config['id_user'], 0, "PM")) {
	if (isset ($_GET["force"])) {
		$id = entrada_limpia($_GET["force"]);
		$sql = "UPDATE trecon_task set utimestamp = 0, status = -1 WHERE id_rt = $id ";
		$result = mysql_query($sql);
	}
}

$id_server = get_parameter ("server_id", -1);
$sql = "SELECT * FROM tserver WHERE id_server = $id_server";
$result=mysql_query($sql);
$row=mysql_fetch_array($result);
$server_name = $row["name"];
$id_server = $row[0];

echo "<h2>". __('Configuration detail') . " - $server_name ";
echo "&nbsp;";
echo "<a href='index.php?sec=estado_server&sec2=operation/servers/view_server_detail&server_id=$id_server'>";
echo "<img src='images/refresh.png'>";
echo "</A>";
echo "</h2>";
// Show network tasks for Recon Server
if ($row["recon_server"]) {
	$sql = "SELECT * FROM trecon_task WHERE id_recon_server = $id_server";
	// Connect DataBase
	$result=mysql_query($sql);
	if (mysql_num_rows($result)){
		echo "<table cellpadding='4' cellspacing='4' width='760' class='databox'>";
		echo "<tr><th class='datos'>".__('Force')."</th>";
		echo "<th class='datos'>".__('Task name')."</th>";
		echo "<th class='datos'>".__('Interval')."</th>";
		echo "<th class='datos'>".__('Network')."</th>";
		echo "<th class='datos'>".__('Status')."</th>";
		echo "<th class='datos'>".__('Network profile')."</th>";
		echo "<th class='datos'>".__('Group')."</th>";
		echo "<th class='datos'>".__('OS') ."</th>";
		echo "<th class='datos'>".__('Progress')."</th>";
		echo "<th class='datos'>".__('Updated at')."</th>";
		echo "<th class='datos'>".__('Op')."</th>";
		$color=1;
		while ($row=mysql_fetch_array($result)){
			if ($color == 1){
				$tdcolor = "datos";
				$color = 0;
				}
			else {
				$tdcolor = "datos2";
				$color = 1;
			}
			$id_rt = $row["id_rt"];
			$name = $row["name"];
			$status = $row["status"];
			$utimestamp = $row["utimestamp"];
			$interval = $row["interval_sweep"];
			$create_incident = $row["create_incident"];
			$subnet = $row["subnet"];
			$id_os = $row["id_os"];
			$id_group = $row["id_group"];
			$id_network_profile = $row["id_network_profile"];

			echo "<tr>";
			// Name
			echo "<td class='$tdcolor'>";
			echo "<a href='index.php?sec=estado_server&sec2=operation/servers/view_server_detail&server_id=$id_server&force=$id_rt'><img src='images/target.png' border='0'></a>";
			
			echo "<td class='$tdcolor'>";
			echo "<b>$name</b>";
			// Interval
			echo "<td class='$tdcolor'>";
			if ($interval != 0){
				if ($interval < 43200)
					echo "~ ".floor ($interval / 3600)." ".__('hours');
				else
					echo "~ ".floor ($interval / 86400)." ".__('days');
			} else
				echo $interval;
			
			// Subnet
			echo "<td class='$tdcolor'>";
			echo $subnet;
			
			// status
			echo "<td class='$tdcolor' align='center'>";
			if ($status == -1)
				echo __('Done');
			else
				echo __('Pending');
			// Network profile
			echo "<td class='$tdcolor'>";
			echo give_network_profile_name($id_network_profile);
			
			// Group
			echo "<td class='$tdcolor' align='center'>";
			echo "<img class='bot' src='images/groups_small/".show_icon_group($id_group).".png'>";
			
			// OS
			echo "<td class='$tdcolor' align='center'>";
			if ($id_os > 0){
				$icon = get_db_sql ("SELECT icon_name FROM tconfig_os WHERE id_os = $id_os");
				echo "<img src='images/$icon'>";
			}

			// Progress
			echo "<td class='$tdcolor' align='center'>";
			if ($status < 0)
				echo "-";
			else
				echo '<img src="reporting/fgraph.php?tipo=progress&percent='.$status.'&height=20&width=100">';
			
			// Last execution
			echo "<td class='".$tdcolor."f9'>";
			$keepalive = format_datetime ( $utimestamp );
			echo substr($keepalive,0,25)."</td>";

			echo "<td class='$tdcolor'>";
			if (give_acl ($config['id_user'], 0, "PM")) {
				echo "<a  href='index.php?sec=gservers&sec2=godmode/servers/manage_recontask_form&update=$id_rt'>";
				echo "<img src='images/wrench_orange.png'></a>";
			}	
		}
		echo "</table>";
	} else {
		echo "This server has no recon tasks assigned";
	}
}

?>
