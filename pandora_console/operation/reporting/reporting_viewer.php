<?PHP

// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@gmail.com
// Main PHP/SQL code development and project architecture and management
// Copyright (c) 2005-2007 Artica Soluciones Tecnologicas, info@artica.es
//
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

// Login check
$id_user=$_SESSION["id_usuario"];
global $REMOTE_ADDR;

if (comprueba_login() != 0) {
	audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access graph builder");
	include ("general/noaccess.php");
	exit;
}

if ((give_acl($id_user,0,"AR") != 1 ) AND (dame_admin($id_user)!=1)) {
	audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access graph builder");
	include ("general/noaccess.php");
	exit;
}


$id_report = give_parameter_get ( 'id', $default = "");
if ($id_report == ""){
	audit_db($id_user,$REMOTE_ADDR, "HACK Attempt","Trying to access graph viewer withoud ID");
	include ("general/noaccess.php");
	exit;
}

require ("include/functions_reporting.php");

$report_name = give_db_value ("name", "treport", "id_report", $id_report);
$report_description = give_db_value ("description", "treport", "id_report", $id_report);
$report_private= give_db_value ("private", "treport", "id_report", $id_report);
$report_user = give_db_value ("id_user", "treport", "id_report", $id_report);
if (($report_user == $id_user) OR (dame_admin($id_user)==1) OR ($report_private == 0)) {
	
	echo "<h2>".$lang_label["reporting"]." &gt; ";
	echo $lang_label["custom_reporting"]." - ";
	echo $report_name."</h2>";

	echo "<table class='databox' cellpadding= 4 cellspacing=4 width=750>";
	echo "<tr>";
	echo "<td width=50 align='left'><img src='images/reporting.png' width=32 height=32>";
	echo "<td>".$report_description."</td>";
	echo "</table>";

	echo "<table width=750 cellpadding=4 cellspacing=4 class='databox'>";
	$sql = "SELECT * FROM treport_content WHERE id_report = $id_report ORDER by type, id_agent_module DESC";
	$res=mysql_query($sql);
	while ($row = mysql_fetch_array($res)){
		$type = $row["type"];
		$sla_max = $row["sla_max"];
		$sla_min = $row["sla_min"];
		$sla_limit = $row["sla_limit"];
		$id_agent_module = $row["id_agent_module"];
		$period = $row["period"];
		$id_gs = $row["id_gs"];
        unset ($modules);
        unset ($weights);
        $module_name = get_db_sql ("SELECT nombre FROM tagente_modulo WHERE id_agente_modulo = ". $id_agent_module);
        $agent_name = dame_nombre_agente_agentemodulo ($id_agent_module);
		switch($type){
			case 2: // SLA
					$sla_result = format_numeric(return_module_SLA ($id_agent_module, $period, $sla_min, $sla_max), 2);
					echo "<tr><td class='datos3'>";
					echo "<h4>".$lang_label["SLA"]."</h4>";
					echo "<td class='datos3' >";
					echo "<h4>$agent_name - $module_name</h4>";
					echo "<td class='datos3' >";
					echo "<h4>".human_time_description($period)."</h4>";
					echo "<tr>";
					echo "<td colspan=2 class=datos>";
					echo "<font size='0.6em'>";
					echo $lang_label["sla_max"]. " : ".$sla_max. "<br>";
					echo $lang_label["sla_min"]. " : ".$sla_min. "<br>";
					echo $lang_label["sla_limit"]. " : ".$sla_limit. "<br>";
					echo "</font>";
					echo "<td class=datos valign='middle' align='right' >";
					if ($sla_result >= $sla_limit)
						echo "<p style='font: bold 3em Arial, Sans-serif; color: #000000;'>";
					else
						echo "<p style='font: bold 3em Arial, Sans-serif; color: #ff0000;'>";
					echo $sla_result. " %";
					echo "</p>";
					echo "</td></tr>";
					break;
			case 0: // Simple graph
					echo "<tr><td class='datos3'>";
					echo "<h4>".$lang_label["simple_graph"]."</h4>";
					echo "<td class='datos3'>";
					echo "<h4>$agent_name - $module_name</h4>";
					echo "<td class='datos3' valign='top'>";
					echo "<h4>".human_time_description($period)."</h4>";
					echo "<tr><td colspan=3 class='datos' valign='top'>";
					echo "<img src='reporting/fgraph.php?tipo=sparse&id=$id_agent_module&height=230&width=720&period=$period&avg_only=1&pure=1' border=0 alt=''>";
					echo "</tr>";
					break;
			case 1: // Custom/Combined graph
        			$graph = get_db_row ("tgraph", "id_graph", $id_gs);
					$sql2="SELECT * FROM tgraph_source WHERE id_graph = $id_gs";
					$res2=mysql_query($sql2);
					while ( $row2 = mysql_fetch_array($res2)){
						$weight = $row2["weight"];
						$id_agent_module = $row2["id_agent_module"];
						if (!isset($modules)){
							$modules = $id_agent_module;
							$weights = $weight;
						} else {
							$modules = $modules.",".$id_agent_module;
							$weights = $weights.",".$weight;
						}
					}
					echo "<tr><td class=datos3 >";
					echo "<h4>".$lang_label["custom_graph"]."</h4>";
					echo "<td class=datos3>";
					echo "<h4>".$graph["name"]."</h4>";
                    $stacked = $graph["stacked"];
					echo "<td class=datos3>";
					echo "<h4>".human_time_description($period)."</h4>";
					echo "<tr><td colspan=3 class=datos valign='top' align='right'>";
					echo "<img src='reporting/fgraph.php?tipo=combined&id=$modules&weight_l=$weights&height=230&width=720&period=$period&stacked=$stacked&pure=1' border=1 alt=''>";
					echo "</tr>";
					break;
			case 6: // AVG value
					$avg_value = format_for_graph(return_moduledata_avg_value ($id_agent_module, $period),2);
					echo "<tr><td class='datos3'>";
					echo "<h4>".$lang_label["avg_value"]."</h4>";
					echo "<td class='datos3' >";
					echo "<h4>$agent_name - $module_name</h4>";
					echo "<td class='datos3' >";
					echo "<h4>".human_time_description($period)."</h4>";
					echo "<tr>";
					echo "<td colspan=2 class=datos>";
					echo "<td class=datos valign='middle' align='right' >";
					echo "<p style='font: bold 3em Arial, Sans-serif; color: #000000;'>";
					echo $avg_value;
					echo "</p>";
					echo "</td></tr>";
					break;
			case 7: // MAX value
					$max_value = format_for_graph(return_moduledata_max_value ($id_agent_module, $period),2);
					echo "<tr><td class='datos3'>";
					echo "<h4>".$lang_label["max_value"]."</h4>";
					echo "<td class='datos3' >";
					echo "<h4>$agent_name - $module_name</h4>";
					echo "<td class='datos3' >";
					echo "<h4>".human_time_description($period)."</h4>";
					echo "<tr>";
					echo "<td colspan=2 class=datos>";
					echo "<td class=datos valign='middle' align='right' >";
					echo "<p style='font: bold 3em Arial, Sans-serif; color: #000000;'>";
					echo $max_value;
					echo "</p>";
					echo "</td></tr>";
					break;
			case 8: // MIN value
					$min_value = format_for_graph(return_moduledata_min_value ($id_agent_module, $period),2);
					echo "<tr><td class='datos3'>";
					echo "<h4>".$lang_label["min_value"]."</h4>";
					echo "<td class='datos3' >";
					echo "<h4>$agent_name - $module_name</h4>";
					echo "<td class='datos3' >";
					echo "<h4>".human_time_description($period)."</h4>";
					echo "<tr>";
					echo "<td colspan=2 class=datos>";
					echo "<td class=datos valign='middle' align='right' >";
					echo "<p style='font: bold 3em Arial, Sans-serif; color: #000000;'>";
					echo $min_value;
					echo "</p>";
					echo "</td></tr>";
					break;
			case 5: // Monitor report
					$monitor_value = $sla_result = format_numeric(return_module_SLA ($id_agent_module, $period, 1, 1), 2);
					echo "<tr><td class='datos3'>";
					echo "<h4>".$lang_label["monitor_report"]."</h4>";
					echo "<td class='datos3' >";
					echo "<h4>$agent_name - $module_name</h4>";
					echo "<td class='datos3' >";
					echo "<h4>".human_time_description($period)."</h4>";
					echo "<tr>";
					echo "<td colspan=2 class=datos>";
					echo "<td class=datos valign='middle' align='right' >";
					echo "<p style='font: bold 3em Arial, Sans-serif; color: #000000;'>";
					echo $monitor_value." %"."<img src='images/b_green.png' height=32 width=32>";
					echo "</p>";
					$monitor_value2 = format_numeric(100 - $monitor_value,2) ;
					echo "<p style='font: bold 3em Arial, Sans-serif; color: #ff0000;'>";
					echo $monitor_value2." %"."<img src='images/b_red.png' height=32 width=32>";
					echo "</p>";
					echo "</td></tr>";
					break;
			case 3: // Event report
					$id_agent = dame_agente_id ($agent_name);
					echo "<tr><td class='datos3'>";
					echo "<h4>".$lang_label["event_report"]."</h4>";
					echo "<td class='datos3' >";
					echo "<h4>$agent_name - $module_name</h4>";
					echo "<td class='datos3' >";
					echo "<h4>".human_time_description($period)."</h4>";
					echo "<tr>";
					echo "<td colspan=3 class=datos>";
					event_reporting ($id_agent, $period);

					echo "</td></tr>";
					break;
			case 4: // Alert report					
					echo "<tr><td class='datos3'>";
					echo "<h4>".$lang_label["alert_report"]."</h4>";
					echo "<td class='datos3' >";
					echo "<h4>$agent_name - $module_name</h4>";
					echo "<td class='datos3' >";
					echo "<h4>".human_time_description($period)."</h4>";
					echo "<tr>";
					echo "<td colspan=3 class=datos>";
					alert_reporting ($id_agent_module);
					echo "</td></tr>";
					break;
		}
	}
	echo "</table>";
}

?>
