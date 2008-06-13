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

// Get total modules defined (network)
$total_modules_network = get_db_sql  ("SELECT COUNT(id_agente_modulo) FROM tagente_modulo WHERE id_tipo_modulo > 4 AND id_tipo_modulo != 100");

// Get total modules defined (data)
$total_modules_data = get_db_sql  ("SELECT COUNT(id_agente_modulo) FROM tagente_modulo WHERE id_tipo_modulo < 5 OR id_tipo_modulo = 100");

// Connect DataBase
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

		$modules_server = 0;
		// Get total modules defined for this server (data modules)	
		$modules_server = get_db_sql  ("SELECT COUNT(running_by) FROM tagente_estado WHERE running_by = $id_server");
		
		echo "<tr><td class='$tdcolor'>";
        
        // Recon server detail
		if ($recon_server == 1)
            if (give_acl($id_user, 0, "PM")==1)
			    echo "<b><a href='index.php?sec=estado_server&sec2=operation/servers/view_server_detail&server_id=$id_server'>$name</a></b> ";
            else
                echo "<b>$name</b>";
		else
			echo "<b>$name</b>";
        
        // Status (bad or good)
		echo "<td class='$tdcolor' align='middle'>";
		if ($status ==0){
			echo "<img src='images/pixel_red.png' width=20 height=20>";
		} else {
			echo "<img src='images/pixel_green.png' width=20 height=20>";
		}

		echo "<td class='$tdcolor' align='middle'>";
		if (($snmp_server == 0) OR ($recon_server == 0)){
			// Progress bar calculations
			if ($network_server == 1){
				if ($total_modules_network == 0)
					$percentil = 0;
				if ($total_modules_network > 0)
					$percentil = $modules_server / ($total_modules_network / 100);
				else	
					$percentil = 0;
				$total_modules_temp = $total_modules_network;
			} else {
				if ($total_modules_data == 0)
					$percentil = 0;
				else
					$percentil = $modules_server / ($total_modules_data / 100);
				$total_modules_temp = $total_modules_data;
			}
		} elseif ($recon_server == 1){
			$modules_server = get_db_sql  ("SELECT COUNT(id_rt) FROM trecon_task WHERE id_network_server = $id_server");
			$total_modules = get_db_sql ("SELECT COUNT(id_rt) FROM trecon_task");
			if ($total_modules == 0)
				$percentil = 0;
			else	
				$percentil = $modules_server / ($total_modules / 100);
			$total_modules_temp = $total_modules;
		}
		else 
			echo "-";

        // Progress bar render
        if ($snmp_server == 0) {
            // Check bad values for percentile
            if ($percentil > 100){
                    $percentil = 100;
            }
            if ($percentil < 0){
                    $percentil = 0;
            }
            echo '<img src="reporting/fgraph.php?tipo=progress&percent='.$percentil.'&height=18&width=80">';
        }

		// Number of modules
		echo "<td class='$tdcolor'>";
		if (($recon_server ==1) OR ($network_server == 1) OR ($data_server == 1)){
			echo $modules_server . ' / '. $total_modules_temp;
        } else {
			echo "-";
        }

		// LAG CHECK 
		echo "<td class='$tdcolor'>"; 
		// Calculate lag: get oldest module of any proc_type, for this server,
		// and calculate difference in seconds 
		// Get total modules defined for this server
		if (($network_server == 1) OR ($data_server == 1) OR ($wmi_server == 1) OR ($plugin_server == 1)) {
			if (($network_server == 1) OR ($wmi_server == 1) OR ($plugin_server == 1)) {
				$sql1 = "SELECT MIN(last_execution_try),current_interval FROM tagente_estado WHERE last_execution_try > 0 AND running_by=$id_server GROUP BY current_interval ORDER BY 1";
            } elseif ($data_server == 1){
				// This only checks for agent with a last_execution_try of at 
				// maximun: ten times it's interval.... if is bigger, it probably 
				// will be because an agent down
				$sql1 = "SELECT MAX(last_execution_try), current_interval, id_agente FROM tagente_estado WHERE last_execution_try > 0 AND (tagente_estado.last_execution_try + (tagente_estado.current_interval * 10) > UNIX_TIMESTAMP()) AND running_by=$id_server GROUP BY id_agente ORDER BY 1 ASC LIMIT 1";
            }
			$nowtime = time();
			$maxlag=0;
			if ($result1=mysql_query($sql1))
			while ($row1=mysql_fetch_array($result1)){
				if (($row1[0] + $row1[1]) < $nowtime){
					$maxlag2 =  $nowtime - ($row1[0] + $row1[1]);
					// More than 5 times module interval is not lag, is a big
					// problem in agent, network or servers..
					if ($maxlag2 < ($row1[1]*5))
						if ($maxlag2 > $maxlag)
							$maxlag = $maxlag2;
				}
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
			$maxlag=0;$maxlag2=0;
			while ($row1=mysql_fetch_array($result1)){
				if (($row1["utimestamp"] + $row1["interval_sweep"]) < $nowtime){
					$maxlag2 =  $nowtime - ($row1["utimestamp"] + $row1["interval_sweep"]);
					if ($maxlag2 > $maxlag)
						$maxlag = $maxlag2;
				}
			}
			if ($maxlag < 60)
				echo $maxlag." sec";
			elseif ($maxlag < 86400)
				echo format_numeric($maxlag/60) . " min";
			elseif ($maxlag > 86400)
				echo "+1 ".$lang_label["day"];
		} else {
			echo "--";
        }
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
