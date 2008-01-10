<?php

// Pandora FMS - the Free Monitoring System
// ========================================
// Copyright (c) 2004-2008 Sancho Lerena, slerena@gmail.com
// Main PHP/SQL code development, project architecture and management.
// Copyright (c) 2004-2007 Raul Mateos Martin, raulofpandora@gmail.com
// CSS and some PHP code additions
// Please see http://pandora.sourceforge.net for full contribution list



	// Load global vars
	require("include/config.php");
	require("include/functions_reporting.php");
	$id_user = $_SESSION["id_usuario"];
	 	
	if (give_acl ($id_user, 0, "AR") != 1) {
		audit_db ($id_user, $REMOTE_ADDR, "ACL Violation", 
		"Trying to access Agent view (Grouped)");
		require ("general/noaccess.php");
		exit;
	}
	echo "<h2>".$lang_label["ag_title"]." &gt; ";
	echo $lang_label["tactical_view"]."</h2>";

	$data = general_stats($id_user,0);
	$monitor_checks = $data[0];
	$monitor_ok = $data[1];
	$monitor_bad = $data[2];
	$monitor_unknown = $data[3];
	$monitor_alert = $data[4];
	$total_agents = $data[5];
	$data_checks = $data[6];
	$data_unknown = $data[7];
	$data_alert = $data[8];
	$data_alert_total = $data[9];
	$monitor_alert_total = $data[10];
	$data_not_init = $data[11];
	$monitor_not_init = $data[12];	
	$total_checks = $data_checks + $monitor_checks;

	// Monitor checks
	// ~~~~~~~~~~~~~~~
	echo "<table width=700 border=0>";
	echo "<tr><td>";
	echo "<table class='databox' celldpadding=4 cellspacing=4 width=250>";
	echo "<th colspan=2>".$lang_label["monitor_checks"]."</th>";
	echo "<tr><td class=datos2><b>"."Monitor checks"."</b></td>";
	echo "<td class=datos2 style='font: bold 2em Arial, Sans-serif; color: #000;'>".$monitor_checks."</td>";
	echo "<tr><td class=datos><b>"."Monitor OK"."</b></td>";
	echo "<td class=datos style='font: bold 2em Arial, Sans-serif; color: #000;'>".$monitor_ok."</td>";
	echo "<tr><td class=datos2><b>"."Monitor BAD"."</b></td>";
	echo "<td class=datos2 style='font: bold 2em Arial, Sans-serif; color: #f00;'>";
	if ($monitor_bad > 0)
		echo $monitor_bad;
	else
		echo "-";
	echo "</td></tr><tr><td class=datos><b>"."Monitor Unknown"."</b></td>";
	echo "<td class=datos style='font: bold 2em Arial, Sans-serif; color: #888;'>";
	if ($monitor_unknown > 0)
		echo $monitor_unknown;
	else
		echo "-";

	echo "</td></tr><tr><td class=datos><b>"."Monitor Not Init"."</b></td>";
        echo "<td class=datos style='font: bold 2em Arial, Sans-serif; color: #888;'>";
        if ($monitor_not_init> 0)
                echo $monitor_not_init;
        else
                echo "-";

	echo "<tr><td class=datos2><b>"."Alerts Fired"."</b></td>";
	echo "<td class=datos2 style='font: bold 2em Arial, Sans-serif; color: #ff0000;'>";
	if ($monitor_alert > 0)
		echo $monitor_alert;
	else
		echo "-";
	echo "<tr><td class=datos><b>"."Alerts Total"."</b></td>";
	echo "<td class=datos style='font: bold 2em Arial, Sans-serif; color: #000000;'>".$monitor_alert_total;
	echo "</table>";

	// Data checks
	// ~~~~~~~~~~~~~~~
	echo "<table class='databox' celldpadding=4 cellspacing=4 width=250>";
	echo "<th colspan=2>".$lang_label["data_checks"]."</th>";
	echo "<tr><td class=datos2><b>"."Data checks"."</b></td>";
	echo "<td class=datos2 style='font: bold 2em Arial, Sans-serif; color: #000000;'>".$data_checks;
	echo "<tr><td class=datos><b>"."Data Unknown"."</b></td>";
	echo "<td class=datos style='font: bold 2em Arial, Sans-serif; color: #888;'>";
	if ($data_unknown > 0)
		echo $data_unknown;
	else
		echo "-";
	echo "<tr><td class=datos2><b>"."Data not init"."</b></td>";
	echo "<td class=datos2 style='font: bold 2em Arial, Sans-serif; color: #f00;'>";
	if ($data_not_init > 0)
		echo $data_not_init;
	else
		echo "-";
	echo "<tr><td class=datos2><b>"."Alerts Fired"."</b></td>";
        echo "<td class=datos2 style='font: bold 2em Arial, Sans-serif; color: #f00;'>";
        if ($data_alert > 0)
                echo $data_alert;
        else
                echo "-";
	echo "<tr><td class=datos><b>"."Alerts Total";
	echo "<td class=datos style='font: bold 2em Arial, Sans-serif; color: #000;'>".$data_alert_total;
	echo "</table>";

	// Summary
	// ~~~~~~~~~~~~~~~
	echo "<table class='databox' celldpadding=4 cellspacing=4 width=250>";
	echo "<th colspan='2'>".$lang_label["summary"]."</th>";
	echo "<tr><td class='datos2'><b>"."Total agents"."</b></td>";
	echo "<td class='datos2' style='font: bold 2em Arial, Sans-serif; color: #000;'>".$total_agents;
	echo "<tr><td class='datos'><b>"."Total checks"."</b></td>";
	echo "<td class='datos' style='font: bold 2em Arial, Sans-serif; color: #000;'>".$total_checks;
	echo "</table>";

	echo "<td valign='top'>";

	// Odometer Graph
	// ~~~~~~~~~~~~~~~
	if ($monitor_checks > 0){
		$monitor_health = format_numeric ((($monitor_ok - $monitor_alert - $monitor_unknown)/ $monitor_checks) * 100,1);
	} else 
		$monitor_health = 100;
	if ($data_checks > 0){
		$data_health = format_numeric ( (($data_checks -($data_unknown + $data_alert)) / $data_checks ) * 100,1);;
	} else
		$data_health = 100;
	if (($data_checks != 0) OR ($data_checks != 0)){
		$global_health = format_numeric( ((($monitor_ok -$monitor_alert - $monitor_unknown )+($data_checks -($data_unknown + $data_alert))) / ($data_checks + $monitor_checks)  ) * 100, 1);
	} else
		$global_health = 100;
		
	echo "<h2>".$lang_label["tactical_indicator"]."</h2>";
	echo "<img src='reporting/fgraph.php?tipo=odo_tactic&value1=$global_health&value2=$data_health&value3=$monitor_health'>";

	// Server information
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        // Get total modules defined (network)
        $total_modules_network = get_db_sql  ("SELECT COUNT(id_agente_modulo) FROM tagente_modulo WHERE id_tipo_modulo > 4 AND id_tipo_modulo != 100");
        // Get total modules defined (data)
        $total_modules_data = get_db_sql  ("SELECT COUNT(id_agente_modulo) FROM tagente_modulo WHERE id_tipo_modulo < 5 OR id_tipo_modulo = 100");
	echo "<h2>".$lang_label["tactical_server_information"]."</h2>";
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
			if (($network_server == 1) OR ($data_server == 1)){
				// Get total modules defined for this server (data modules)
                                $sql2 = "SELECT COUNT(running_by) FROM tagente_estado WHERE running_by = $id_server";
                                $result2=mysql_query($sql2);
                                $row2=mysql_fetch_array($result2);
                                $modules_server = $row2[0];
				echo "<tr><td class='$tdcolor'>";
				echo "<b>$name</b>";
				echo "<td class='$tdcolor' align='middle'>";
				if ($status ==0){
					echo "<img src='images/dot_red.png'>";
				} else {
					echo "<img src='images/dot_green.png'>";
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
					else	
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
					if ($network_server == 1)
	                                        $sql1 = "SELECT MIN(last_execution_try),current_interval FROM tagente_estado WHERE last_execution_try > 0 AND running_by=$id_server GROUP BY current_interval ORDER BY 1";
	                                if ($data_server == 1)
						// This only checks for agent with a last_execution_try of at
		                                // maximun: ten times it's interval.... if is bigger, it probably
			                        // will be because an agent down
	                                        $sql1 = "SELECT MAX(last_execution_try), current_interval, id_agente FROM tagente_estado WHERE last_execution_try > 0 AND (tagente_estado.last_execution_try + (tagente_estado.current_interval *10) > UNIX_TIMESTAMP()) AND running_by=$id_server GROUP BY id_agente ORDER BY 1 ASC LIMIT 1";
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
	                        } else
	                                echo "--";

			}
		}
		echo '</table>';
	}
	echo "</table>";


?>
