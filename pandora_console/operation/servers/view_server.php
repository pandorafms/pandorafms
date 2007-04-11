<?php

// Pandora - the Free monitoring system
// ====================================
// Copyright (c) 2004-2006 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2005-2006 Artica Soluciones Tecnologicas S.L, info@artica.es
// Copyright (c) 2004-2006 Raul Mateos Martin, raulofpandora@gmail.com
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// Load global vars
require("include/config.php");

$modules_server = 0;
$total_modules = 0;
$total_modules_data = 0;

if (comprueba_login() == 0) {
 	if ((give_acl($id_user, 0, "AR")==1) or (give_acl($id_user,0,"AW")) or (dame_admin($id_user)==1)) {

	$sql='SELECT * FROM tserver';
	
	echo "<h2>".$lang_label["view_servers"]." -&gt; ";
	echo $lang_label["server_detail"]." <a href='help/".$help_code."/chap7.php#7' target='_help' class='help'>&nbsp;<span>".$lang_label["help"]."</span></a></h2>";

	// Get total modules defined (network)
	$sql1='SELECT COUNT(id_agente_modulo) FROM tagente_modulo WHERE id_tipo_modulo > 4';
	$result1=mysql_query($sql1);
	$row1=mysql_fetch_array($result1);
	$total_modules = $row1[0];

	// Get total modules defined (data)
	$sql1='SELECT COUNT(processed_by_server) FROM tagente_estado WHERE processed_by_server LIKE "%_Data" ';
	if ($result1=mysql_query($sql1)){
		$row1=mysql_fetch_array($result1);
		$total_modules_data = $row1[0];
	} else	
		$total_modules_data = 0;

	// Connect DataBase
	$result=mysql_query($sql);
	if (mysql_num_rows($result)){
		echo "<table cellpadding='4' cellspacing='4' witdh='750'>";
		echo "<tr><th class='datos'>".$lang_label["name"]."</th>";
		echo "<th class='datos'>".$lang_label['status']."</th>";
		echo "<th class='datos'>".$lang_label['load']."</th>";
		echo "<th class='datos'>".$lang_label['modules']."</th>";
		echo "<th class='datos'>".$lang_label['lag']."</th>";
		echo "<th class='datos'>".$lang_label['description']."</th>";
		echo "<th class='datos' width=80>".$lang_label['type']."</th>";
		// echo "<th class='datos'>".$lang_label['master']."</th>";
		//echo "<th class='datos'>".$lang_label['checksum']."</th>";
		//echo "<th class='datos'>".$lang_label['laststart']."</th>";
		echo "<th class='datos'>".$lang_label['version']."</th>";
		echo "<th class='datos'>".$lang_label['lastupdate']."</th>";
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
			$id_server = $row["id_server"];
			$name = $row["name"];
			$address = $row["ip_address"];
			$status = $row["status"];
			$laststart = $row["laststart"];
			$keepalive = $row["keepalive"];
			$network_server = $row["network_server"];
			$data_server = $row["data_server"];
			$snmp_server = $row["snmp_server"];
			$recon_server = $row["recon_server"];
			$master = $row["master"];
			$checksum = $row["checksum"];
			$description = $row["description"];
			$version = $row["version"];

			$modules_server = 0;
			if (($network_server == 1) OR ($data_server == 1))
				if ($network_server == 1){
					// Get total modules defined for this server (network modules)
					$sql1='SELECT * FROM tagente where id_server = '.$row["id_server"];
					$result1=mysql_query($sql1);
					while ($row1=mysql_fetch_array($result1)){
						$sql2='SELECT COUNT(id_agente_modulo) FROM tagente_modulo WHERE id_tipo_modulo > 4 AND id_agente = '.$row1["id_agente"];
						$result2=mysql_query($sql2);
						$row2=mysql_fetch_array($result2);
						$modules_server = $modules_server + $row2[0];
					}
				} else {
					// Get total modules defined for this server (data modules)
					$sql2 = "SELECT COUNT(processed_by_server) FROM tagente_estado WHERE processed_by_server = '$name'";
					$result2=mysql_query($sql2);
					$row2=mysql_fetch_array($result2);
					$modules_server = $row2[0];
				}
			
			echo "<tr><td class='$tdcolor'>";
			if ($recon_server == 1)
				echo "<b><a href='index.php?sec=estado_server&sec2=operation/servers/view_server_detail&server_id=$id_server'>$name</a></b> ";
			else
				echo "<b>$name</b>";
			echo "<td class='$tdcolor' align='middle'>";
			if ($status ==0){
				echo "<img src='images/dot_red.gif'>";
			} else {
				echo "<img src='images/dot_green.gif'>";
			}
			echo "<td class='$tdcolor' align='middle'>";
			if (($network_server == 1) OR ($data_server == 1)){
				// Progress bar calculations
				if ($network_server == 1){
					if ($total_modules == 0)
						$percentil = 0;
					if ($total_modules > 0)
						$percentil = $modules_server / ($total_modules / 100);
					else	
						$percentil = 0;
					$total_modules_temp = $total_modules;
				} else {
					if ($total_modules_data == 0)
						$percentil = 0;
					else
						$percentil = $modules_server / ($total_modules_data / 100);
					$total_modules_temp = $total_modules_data;
				}
			} elseif ($recon_server == 1){
			
				$sql2 = "SELECT COUNT(id_rt) FROM trecon_task WHERE id_network_server = $id_server";
				$result2=mysql_query($sql2);
				$row2=mysql_fetch_array($result2);
				$modules_server = $row2[0];

				$sql2 = "SELECT COUNT(id_rt) FROM trecon_task";
				$result2=mysql_query($sql2);
				$row2=mysql_fetch_array($result2);
				$total_modules = $row2[0];
				if ($total_modules == 0)
					$percentil = 0;
				$percentil = $modules_server / ($total_modules / 100);
				$total_modules_temp = $total_modules;
			}
			else 
				echo "-";

			if (($network_server == 1) OR ($data_server == 1) OR ($recon_server == 1))
				// Progress bar render
				echo '<img src="reporting/fgraph.php?tipo=progress&percent='.$percentil.'&height=18&width=80">';
				
			// Number of modules
			echo "<td class='$tdcolor'>";
			if (($recon_server ==1) OR ($network_server == 1) OR ($data_server == 1))
				echo $modules_server . " / ". $total_modules_temp;
			else
				echo "-";

			// LAG CHECK
			echo "<td class='$tdcolor'>"; 
			// Calculate lag: get oldest module of any proc_type, for this server,
			// and calculate difference in seconds 
			// Get total modules defined for this server
			if (($network_server == 1) OR ($data_server == 1)){
				$sql1 = "SELECT utimestamp, current_interval FROM tagente_estado WHERE  processed_by_server = '$name' AND estado < 100";
	
				$nowtime = time();
				$maxlag=0;
				if ($result1=mysql_query($sql1))
					while ($row1=mysql_fetch_array($result1)){
						if (($row1["utimestamp"] + $row1["current_interval"]) < $nowtime)
							$maxlag2 =  $nowtime - ($row1["utimestamp"] + $row1["current_interval"]);
							if ($maxlag2 > $maxlag)
								$maxlag = $maxlag2;
					}
				if ($maxlag < 60)
					echo $maxlag." sec";
				elseif ($maxlag < 86400)
					echo format_numeric($maxlag/60) . " min";
				elseif ($maxlag > 86400)
					echo "+1 ".$lang_label["day"];
			} elseif ($recon_server == 1) {
				$sql1 = "SELECT * FROM trecon_task WHERE id_network_server = $id_server";
				$result1=mysql_query($sql1);
				$nowtime = time();
				$maxlag=0;
				while ($row1=mysql_fetch_array($result1)){
					if (($row1["utimestamp"] + $row1["interval_sweep"]) < $nowtime)
						$maxlag2 =  $nowtime - ($row1["utimestamp"] + $row1["interval"]);
						if ($maxlag2 > $maxlag)
							$maxlag = $maxlag2;
				}
				if ($maxlag < 60)
					echo $maxlag." sec";
				elseif ($maxlag < 86400)
					echo format_numeric($maxlag/60) . " min";
				elseif ($maxlag > 86400)
					echo "+1 ".$lang_label["day"];
			} else
				echo "--";
			echo "<td class='".$tdcolor."f9'>".substr($description,0,25);
			echo "<td class='$tdcolor' align='middle'>";			
			if ($network_server == 1){
				echo " <img src='images/network.gif'>";
			}
			if ($data_server == 1){
				echo "&nbsp; <img src='images/data.gif'>";
			}
			if ($snmp_server == 1){
				echo "&nbsp; <img src='images/snmp.gif'>";
			}
			if ($recon_server == 1){
				echo "&nbsp; <img src='images/chart_organisation.png'>";
			}
			if ($master == 1){
				echo "&nbsp; <img src='images/master.gif'>";
			}
			if ($checksum == 1){
				echo "&nbsp; <img src='images/binary.gif'>";
			}
			//echo "<td class='".$tdcolor."f9' align='middle'>"
			//.substr($laststart,0,25)."</td>";
			echo "<td class='".$tdcolor."f9' align='middle'>";
				echo $version;
			
			echo "<td class='".$tdcolor."f9' align='middle'>";
			// if ($status ==0)
				echo substr($keepalive,0,25)."</td>";
		}
		echo '<tr><td colspan="11"><div class="raya"></div></td></tr></table>';	
	} else {
		echo "<div class='nf'>".$lang_label["no_server"]."</div>";
	}

	} else {
	audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access Agent view");
		require ("general/noaccess.php");
	}
	echo "<table cellpadding=4 cellspacing=4>";
	echo "<tr><td>";
	echo "<img src='images/network.gif'><td>".$lang_label["network_server"];
	echo "<td>";
	echo "<img src='images/master.gif'><td>".$lang_label["master"];
	echo "<td>";
	echo "<img src='images/data.gif'><td>".$lang_label["data_server"];
	echo "<td>";
	echo "<img src='images/binary.gif'><td>".$lang_label["md5_checksum"];
	echo "<td>";
	echo "<img src='images/snmp.gif'><td>".$lang_label["snmp_console"];
	echo "<td>";
	echo "<img src='images/chart_organisation.png'><td>".$lang_label["recon_server"];
	echo "</table>";
}
?>