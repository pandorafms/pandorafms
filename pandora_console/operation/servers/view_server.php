<?php

// Pandora FMS - the Free Monitoring System
// ========================================
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

$modules_server = 0;
$total_modules_network = 0;
$total_modules_data = 0;

if (comprueba_login() != 0) {
    audit_db($config["id_user"],$REMOTE_ADDR, "ACL Violation","Trying to access Agent view");
    require ($config["homeurl"]."/general/noaccess.php");
}

if ((give_acl($id_user, 0, "AR")==0) AND (give_acl($id_user,0,"AW") == 0) AND (dame_admin($id_user) == 0) ){
    audit_db($config["id_user"],$REMOTE_ADDR, "ACL Violation","Trying to access Agent view");
    require ($config["homeurl"]."/general/noaccess.php");
}

echo "<h2>".$lang_label["view_servers"]." &gt; ";
echo $lang_label["server_detail"]."</h2>";

$sql='SELECT * FROM tserver';
$result=mysql_query($sql);
if (mysql_num_rows($result)){
	echo "<table cellpadding='4' cellspacing='4' witdh='720' class='databox'>";
	echo "<tr><th class='datos'>".$lang_label["name"]."</th>";
	echo "<th class='datos'>".$lang_label['status']."</th>";
	echo "<th class='datos'>".$lang_label['load']."</th>";
	echo "<th class='datos'>".$lang_label['modules']."</th>";
	echo "<th class='datos'>".$lang_label['lag']."</th>";
	echo "<th class='datos'>".$lang_label['description']."</th>";
	echo "<th class='datos' width=80>".$lang_label['type']."</th>";
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

        $wmi_server = $row["wmi_server"];
        $plugin_server = $row["plugin_server"];
        $prediction_server = $row["prediction_server"];
        $export_server = $row["export_server"];

		$master = $row["master"];
		$checksum = $row["checksum"];
		$description = $row["description"];
		$version = $row["version"];


		$serverinfo = server_status ($id_server);

			// Name of server
			echo "<tr><td class='$tdcolor'>";
			echo $name;

			// Status
			echo "<td class='$tdcolor' align='middle'>";
			if ($status ==0){
				echo "<img src='images/pixel_red.png' width=20 height=20>";
			} else {
				echo "<img src='images/pixel_green.png' width=20 height=20>";
			}
			
			// Load
			echo "<td class='$tdcolor' align='middle'>";
			if ($serverinfo["modules_total"] > 0)
				$percentil = $serverinfo["modules"] / ( $serverinfo["modules_total"]/ 100);
			else
				$percentil = 0;
			if ($percentil > 100)
				$percentil = 100;
			// Progress bar render

			echo '<img src="reporting/fgraph.php?tipo=progress&percent='.$percentil.'&height=18&width=80">';

			// Modules
			echo "<td class='$tdcolor' align='middle'>";
			echo $serverinfo["modules"] . " ".lang_string("of")." ". $serverinfo["modules_total"];

			// Lag
			echo "<td class='$tdcolor' align='middle'>";
			echo human_time_description_raw ($serverinfo["lag"]) . " / ". $serverinfo["module_lag"];


		echo "<td class='".$tdcolor."f9'>".substr($description,0,25)."</td>";
		echo "<td class='$tdcolor' align='middle'>";			
		if ($network_server == 1){
			echo '<img src="images/network.png" title="network">';
		}
		if ($data_server == 1){
			echo '&nbsp; <img src="images/data.png" title="data server">';
		}
		if ($snmp_server == 1){
			echo "&nbsp; <img src='images/snmp.png' title='snmp console'>";
		}
		if ($recon_server == 1){
			echo "&nbsp; <img src='images/recon.png' title='recon'>";
		}
        if ($export_server == 1){
            echo "&nbsp; <img src='images/database_refresh.png' title='export'>";
        }
        if ($wmi_server == 1){
            echo "&nbsp; <img src='images/wmi.png' title='WMI'>";
        }
        if ($prediction_server == 1){
            echo "&nbsp; <img src='images/chart_bar.png' title='prediction'>";
        }
        if ($plugin_server == 1){
            echo "&nbsp;  <img src='images/plugin.png' title='plugin'>";
        }
		if ($master == 1){
			echo "&nbsp; <img src='images/master.png' title='master'>";
		}
		if ($checksum == 1){
			echo "&nbsp; <img src='images/binary.png' title='checksum'>";
		}
		echo "</td><td class='".$tdcolor."f9' align='middle'>";
			echo $version;
		
		echo "</td><td class='".$tdcolor."f9' align='middle'>";
		// if ($status ==0)
        
			echo human_date_relative($keepalive)."</td>";
	}
	echo '</tr></table>';
	echo "<table cellpadding=2 cellspacing=0>";
	echo "
	<tr>
		<td>
		<span class='net'>".$lang_label["network_server"]."</span>
		</td>
		<td>
		<span class='data'>".$lang_label["data_server"]."</span>
		</td>
        <td>
        <span class='plugin'>".lang_string ("plugin_server")."</span>
        </td>
        <td>
        <span class='wmi'>".lang_string ("wmi_server")."</span>
        </td>
        <td>
        <span class='prediction'>".lang_string ("prediction_server")."</span>
        </td>
    </tr>
    <tr>
    <td>
        <span class='export'>".lang_string ("export_server"). "</span>
        </td>
		<td>
		<span class='snmp'>".lang_string ("snmp_console"). "</span>
		</td>
		<td>
		<span class='recon'>".lang_string ("recon_server"). "</span>
		</td>    
    <td>  
        <span class='binary'>".lang_string ("md5_checksum"). "</span>
        </td>
        <td>
        <span class='master'>".lang_string ("master"). "</span>
        </td>
    </tr>";
	echo "</table>";
}

?>
