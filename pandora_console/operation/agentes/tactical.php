<?php

// Pandora FMS
// ====================================
// Copyright (c) 2004-2008 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2005-2008 Artica Soluciones Tecnologicas, info@artica.es
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

	$data = general_stats ($id_user,-1);
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

    // Calculate global indicators

	$total_checks = $data_checks + $monitor_checks;
	if($total_checks != 0){
		$notinit_percentage = (($data_not_init + $monitor_not_init) / ($total_checks / 100));
	} else {
		$notinit_percentage = 0;
	}
	$module_sanity = format_numeric (100 - $notinit_percentage);
	$total_alerts = $data_alert + $monitor_alert;
	$total_fired_alerts = $monitor_alert_total+$data_alert_total;
	if ($total_fired_alerts > 0)
    	$alert_level = format_numeric (100 - ($total_alerts / ($total_fired_alerts / 100)));
	else
		$alert_level  = 100;
    
    if ($monitor_checks > 0){
        $monitor_health = format_numeric (  100- (($monitor_bad + $monitor_unknown) / ($monitor_checks/100)) , 1);
    } else 
        $monitor_health = 100;
    if ($data_checks > 0){
        $data_health = format_numeric ( (($data_checks -($data_unknown + $data_alert)) / $data_checks ) * 100,1);;
    } else
        $data_health = 100;
    if ($data_health < 0)
	$data_health =0;
    if (($data_checks != 0) OR ($data_checks != 0)){
        $global_health = format_numeric ((($data_health * $data_checks) + ($monitor_health * $monitor_checks)) / $total_checks);
    } else
        $global_health = 100;
   
	if ($global_health < 0)
		$global_health;
 
	// Monitor checks
	// ~~~~~~~~~~~~~~~
	echo "<table width=770 border=0>";
	echo "<tr><td valign=top>";
	echo "<table class='databox' celldpadding=4 cellspacing=4 width=250>";

// Summary
    
    echo "<tr><td colspan='2'><b>".lang_string("Monitor health")."</th>";
    echo "<tr><td colspan='2'><img src='reporting/fgraph.php?tipo=progress&height=20&width=260&mode=0&percent=$monitor_health' title='$monitor_health % ".lang_string("of monitors UP")."'>";
    echo "<tr><td colspan='2'><b>".lang_string("Data health")."</th>";
    echo "<tr><td colspan='2'><img src='reporting/fgraph.php?tipo=progress&height=20&width=260&mode=0&percent=$data_health' title='$data_health % ".lang_string("of modules with updated data")."'>";
    echo "<tr><td colspan='2'><b>".lang_string("Global health")."</th>";
    echo "<tr><td colspan='2'><img src='reporting/fgraph.php?tipo=progress&height=20&width=260&mode=0&percent=$global_health' title='$global_health % ".lang_string("of modules with good data")."'>";
    echo "<tr><td colspan='2'><b>".lang_string("Module sanity")."</th>";
    echo "<tr><td colspan='2'><img src='reporting/fgraph.php?tipo=progress&height=20&width=260&mode=0&percent=$module_sanity ' title='$module_sanity % ".lang_string("of well initialized modules")."'>";
    echo "<tr><td colspan='2'><b>".lang_string("Alert level")."</th>";
    echo "<tr><td colspan='2'><img src='reporting/fgraph.php?tipo=progress&height=20&width=260&mode=0&percent=$alert_level' title='$alert_level % ".lang_string("of non-fired alerts")."'>";
	echo "<br><br>";
    

    echo "<tr>";
	echo "<th colspan=2>".$lang_label["monitor_checks"]."</th>";
	echo "<tr><td class=datos2><b>"."Monitor checks"."</b></td>";
	echo "<td class=datos2 style='font: bold 2em Arial, Sans-serif; color: #000;'>".$monitor_checks."</td>";
	echo "<tr><td class=datos><b>"."Monitor OK"."</b></td>";
	echo "<td class=datos style='font: bold 2em Arial, Sans-serif; color: #000;'>".$monitor_ok."</td>";
	echo "<tr><td class=datos2><b>"."Monitor BAD"."</b></td>";
	echo "<td class=datos2 style='font: bold 2em Arial, Sans-serif; color: #f00;'>";
	echo "<a style='text-decoration: none; font: bold 1em Arial, Sans-serif; color: #f00;' href='index.php?sec=estado&sec2=operation/agentes/status_monitor&refr=60'>";
	if ($monitor_bad > 0)
		echo $monitor_bad;
	else
		echo "-";
	echo "</A>";

	echo "</td></tr><tr><td class=datos><b>"."Monitor Unknown"."</b></td>";
	echo "<td class=datos style='font: bold 2em Arial, Sans-serif; color: #888;'>";
	if ($monitor_unknown > 0)
		echo $monitor_unknown;
	else
		echo "-";

	echo "</td></tr><tr><td class=datos2><b>"."Monitor Not Init"."</b></td>";
        echo "<td class=datos2 style='font: bold 2em Arial, Sans-serif; color: #FF8C00;'>";
        if ($monitor_not_init> 0)
                echo $monitor_not_init;
        else
                echo "-";

	echo "<tr><td class=datos><b>"."Alerts Fired"."</b></td>";
	echo "<td class=datos style='font: bold 2em Arial, Sans-serif; color: #ff0000;'>";
	echo "<a style='text-decoration: none; font: bold 1em Arial, Sans-serif; color: #ff0000;' href='index.php?sec=eventos&sec2=operation/events/events&search=&event_type=alert_fired'>";
	if ($monitor_alert > 0)
		echo $monitor_alert;
	else
		echo "-";
	echo "</A>";
	echo "<tr><td class=datos2><b>"."Alerts Total"."</b></td>";
	echo "<td class=datos2 style='font: bold 2em Arial, Sans-serif; color: #000000;'>".$monitor_alert_total;
	

	// Data checks
	// ~~~~~~~~~~~~~~~
    
	echo "<tr><th colspan=2>".$lang_label["data_checks"]."</th>";
	echo "<tr><td class=datos2><b>"."Data checks"."</b></td>";
	echo "<td class=datos2 style='font: bold 2em Arial, Sans-serif; color: #000000;'>".$data_checks;
	echo "<tr><td class=datos><b>"."Data Unknown"."</b></td>";
	echo "<td class=datos style='font: bold 2em Arial, Sans-serif; color: #888;'>";
	if ($data_unknown > 0)
		echo $data_unknown;
	else
		echo "-";
	echo "<tr><td class=datos2><b>"."Data not init"."</b></td>";
	echo "<td class=datos2 style='font: bold 2em Arial, Sans-serif; color: #FF8C00;'>";
	if ($data_not_init > 0)
		echo $data_not_init;
	else
		echo "-";
	echo "<tr><td class=datos><b>"."Alerts Fired"."</b></td>";
        echo "<td class=datos style='font: bold 2em Arial, Sans-serif; color: #f00;'>";
        if ($data_alert > 0)
                echo $data_alert;
        else
                echo "-";
	echo "<tr><td class=datos2><b>"."Alerts Total";
	echo "<td class=datos2 style='font: bold 2em Arial, Sans-serif; color: #000;'>".$data_alert_total;


	// Summary
	// ~~~~~~~~~~~~~~~

	echo "<tr><th colspan='2'>".$lang_label["summary"]."</th>";
	echo "<tr><td class='datos2'><b>"."Total agents"."</b></td>";
	echo "<td class='datos2' style='font: bold 2em Arial, Sans-serif; color: #000;'>".$total_agents;
	echo "<tr><td class='datos'><b>"."Total checks"."</b></td>";
	echo "<td class='datos' style='font: bold 2em Arial, Sans-serif; color: #000;'>".$total_checks;

    echo "<tr><td class='datos2'><b>"."Server sanity"."</b></td>";
    echo "<td class='datos2' style='font: bold 1em Arial, Sans-serif; color: #000;'>";
    echo format_numeric($notinit_percentage);
    echo "% ".lang_string("Uninitialized modules");

	echo "</table>";

	echo "<td valign='top'>";

	// Server information
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    
    $sql='SELECT * FROM tserver';
	$result=mysql_query($sql);
	if (mysql_num_rows($result)){
		echo "<table cellpadding='4' cellspacing='4' witdh='440' class='databox'>";
        echo "<tr><th colspan=5>";
        echo lang_string("tactical_server_information");
		echo "<tr><td class='datos3'>".$lang_label["name"]."</th>";
		echo "<td class='datos3'>".$lang_label['status']."</th>";
		echo "<td class='datos3'>".$lang_label['load']."</th>";
		echo "<td class='datos3'>".$lang_label['modules']."</th>";
		echo "<td class='datos3'>".$lang_label['lag']."</th>";
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

		}
		echo '</table>';

    // Event information
    smal_event_table ("", 10, 440);

	}
	echo "</table>";


?>
