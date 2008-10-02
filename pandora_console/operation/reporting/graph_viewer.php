<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
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


// Login check

check_login ();

$delete_graph = (bool) get_parameter ('delete_graph');
$view_graph = (bool) get_parameter ('view_graph');
$id = (int) get_parameter ('id');

// Delete module SQL code
if ($delete_graph) {
	if (give_acl ($config['id_user'], 0, "AW")) {
		$sql = "DELETE FROM tgraph_source WHERE id_graph = $id";
		if ($res=mysql_query($sql))
			$result = "<h3 class=suc>".__('Deleted successfully')."</h3>";
		else
			$result = "<h3 class=error>".__('Not deleted. Error deleting data')."</h3>";
		$sql = "DELETE FROM tgraph WHERE id_graph = $id";
		if ($res=mysql_query($sql))
			$result = "<h3 class=suc>".__('Deleted successfully')."</h3>";
		else
			$result = "<h3 class=error>".__('Not deleted. Error deleting data')."</h3>";
		echo $result;
	} else {
		audit_db ($config['id_user'],$REMOTE_ADDR, "ACL Violation","Trying to delete a graph from access graph builder");
		include ("general/noaccess.php");
		exit;
	}
}

if ($view_graph) {
	$sql="SELECT * FROM tgraph WHERE id_graph = $id";
	$res=mysql_query($sql);
	if ($graph = mysql_fetch_array($res)){
		$id_user = $graph["id_user"];
		$private = $graph["private"];
		$width = $graph["width"];
		$height = $graph["height"];
		$zoom = (int) get_parameter ('zoom', 0);
		if ($zoom > 0) {
			switch ($zoom) {
			case 1: 
				$width = 500;
				$height = 210;
				break;
			case 2:  
				$width = 650;
				$height = 310;
				break;
			case 3:
				$width = 770;
				$height = 400;
				break;
			}
		}
		$period = (int) get_parameter ('period');
		if (! $period)
			$period = $graph["period"];
		else 
			$period = 3600 * $period;
		$events = $graph["events"];
		$description = $graph["description"];
		$stacked = (int) get_parameter ('stacked', -1);
		if ($stacked == -1)
			$stacked = $graph["stacked"];
		

		$name = $graph["name"];
		if (($graph["private"]==1) && ($graph["id_user"] != $id_user)){
			audit_db($config['id_user'],$REMOTE_ADDR, "ACL Violation","Trying to access to a custom graph not allowed");
			include ("general/noaccess.php");
			exit;
		}
		
		$sql2="SELECT * FROM tgraph_source WHERE id_graph = $id";
		$res2=mysql_query($sql2);
		while ($graph_source = mysql_fetch_array($res2)) {
			$weight = $graph_source["weight"];
			$id_agent_module = $graph_source["id_agent_module"];
			$id_grupo = get_db_sql ("SELECT id_grupo FROM tagente, tagente_modulo WHERE tagente_modulo.id_agente_modulo = $id_agent_module AND tagente.id_agente = tagente_modulo.id_agente");
			if (give_acl($config["id_user"], $id_grupo, "AR")==1){
				if (!isset($modules)){
					$modules = $id_agent_module;
					$weights = $weight;
				} else {
					$modules = $modules.",".$id_agent_module;
					$weights = $weights.",".$weight;
				}
			}
		}
		echo "<h2>".__('Reporting')." &gt; ";
		echo __('Combined image render')."</h2>";
		echo "<table class='databox_frame' cellpadding=0 cellspacing=0>";
		echo "<tr><td>";
		echo "<img 
src='reporting/fgraph.php?tipo=combined&height=$height&width=$width&id=$modules&period=$period&weight_l=$weights&stacked=$stacked' 
border=1 alt=''>";
		echo "</td></tr></table>";
		$period_label = human_time_description ($period);
		echo "<form method='POST' action='index.php?sec=reporting&sec2=operation/reporting/graph_viewer&view_graph=1&id=$id'>";
		echo "<table class='databox_frame' cellpadding=4 cellspacing=4>";
		echo "<tr><td class='datos'>";
		echo "<b>".__('Period')."</b>";
		echo "<td class='datos'>";
		$periods = array ();
		$periods[1] = __('1 hour');
		$periods[2] = '2 '.__('hours');
		$periods[3] = '3 '.__('hours');
		$periods[6] = '6 '.__('hours');
		$periods[12] = '12 '.__('hours');
		$periods[24] = __('1 day');
		$periods[48] = __('2 days');
		$periods[360] = __('1 week');
		$periods[720] = __('1 month');
		$periods[4320] = __('6 months');
		print_select ($periods, 'period', intval ($period / 3600), '', '', 0);

		echo "<td class='datos'>";
		$stackeds = array ();
		$stackeds[0] = __('Graph defined');
		$stackeds[0] = __('Area');
		$stackeds[1] = __('Stacked area');
		$stackeds[2] = __('Line');
		print_select ($stackeds, 'stacked', $stacked , '', '', -1, false, false);

		echo "<td class='datos'>";
		$zooms = array();
		$zooms[0] = __('Graph defined');
	 	$zooms[1] = __('Zoom x1');
		$zooms[2] = __('Zoom x2');
		$zooms[3] = __('Zoom x3');
		print_select ($zooms, 'zoom', $zoom , '', '', 0);

		echo "<td class='datos'>";
		echo "<input type=submit value='".__('Update')."' class='sub upd'>";
		echo "</table>";
		echo "</form>";		
	}
}
echo "<h2>" . __('Reporting') . " &gt; ";
echo __('Custom graph viewer') . "</h2>";

$color=1;
$sql="SELECT * FROM tgraph ORDER by name";
$res=mysql_query($sql);
if (mysql_num_rows($res)) {
	echo "<table width='500' cellpadding=4 cellpadding=4 class='databox_frame'>";
	echo "<tr>
		<th>".__('Graph name')."</th>
		<th>".__('Description')."</th>
		<th>".__('View')."</th>";
	if (give_acl ($config['id_user'], 0, "AW"))
		echo "<th>".__('Delete')."</th>";
	echo "</tr>";

	while ($graph = mysql_fetch_array($res)){
		if (($graph["private"] == 0) || ($graph["id_user"] == $id_user)) {
			// Calculate table line color
			if ($color == 1){
				$tdcolor = "datos";
				$color = 0;
			}
			else {
				$tdcolor = "datos2";
				$color = 1;
			}
			echo "<tr>";
			echo "<td valign='top' class='$tdcolor'>".$graph["name"]."</td>";
			echo "<td class='$tdcolor'>".$graph["description"]."</td>";
			$id =  $graph["id_graph"];
			echo "<td valign='middle' class='$tdcolor' align='center'><a href='index.php?sec=reporting&sec2=operation/reporting/graph_viewer&view_graph=1&id=$id'><img src='images/images.png'></a>";
			
			if (give_acl ($config['id_user'], 0, "AW")) {
				echo "<td class='$tdcolor' align='center'><a href='index.php?sec=reporting&sec2=operation/reporting/graph_viewer&delete_graph=1&id=$id' ".'onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">';
				echo "<img src='images/cross.png'></a></td>";
			}
		}
	}
	echo "</table>";
} else {
	echo "<div class='nf'>".__('There are no defined reportings')."</div>";
}

?>
