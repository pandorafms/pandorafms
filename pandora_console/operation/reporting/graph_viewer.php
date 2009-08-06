<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.



// Login check

check_login ();

require_once ('include/functions_custom_graphs.php');

$delete_graph = (bool) get_parameter ('delete_graph');
$view_graph = (bool) get_parameter ('view_graph');
$id = (int) get_parameter ('id');

// Delete module SQL code
if ($delete_graph) {
	if (give_acl ($config['id_user'], 0, "AW")) {
		$sql = "DELETE FROM tgraph_source WHERE id_graph = $id";
		if ($res=mysql_query($sql))
			$result = "<h3 class=suc>".__('Successfully deleted')."</h3>";
		else
			$result = "<h3 class=error>".__('Not deleted. Error deleting data')."</h3>";
		$sql = "DELETE FROM tgraph WHERE id_graph = $id";
		if ($res=mysql_query($sql))
			$result = "<h3 class=suc>".__('Successfully deleted')."</h3>";
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
		
		echo "<h2>".__('Reporting')." &raquo; ";
		echo __('Combined image render')."</h2>";
		echo "<table class='databox_frame' cellpadding=0 cellspacing=0>";
		echo "<tr><td>";
		print_custom_graph ($id, $height, $width, $period, $stacked);
		echo "</td></tr></table>";
		$period_label = human_time_description ($period);
		echo "<form method='POST' action='index.php?sec=reporting&sec2=operation/reporting/graph_viewer&view_graph=1&id=$id'>";
		echo "<table class='databox_frame' cellpadding=4 cellspacing=4>";
		echo "<tr><td class='datos'>";
		echo "<b>".__('Period')."</b>";
		echo "<td class='datos'>";
		
		print_select (get_custom_graph_periods (), 'period', intval ($period / 3600),
			'', '', 0, false, false, false);

		echo "<td class='datos'>";
		$stackeds = array ();
		$stackeds[0] = __('Graph defined');
		$stackeds[0] = __('Area');
		$stackeds[1] = __('Stacked area');
		$stackeds[2] = __('Line');
		$stackeds[3] = __('Stacked line');
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
echo "<h2>" . __('Reporting') . " &raquo; ";
echo __('Custom graph viewer') . "</h2>";

$graphs = get_user_custom_graphs ();
if (! empty ($graphs)) {
	$table->width = '500px';
	$tale->class = 'databox_frame';
	$table->align = array ();
	$table->align[2] = 'center';
	$table->head = array ();
	$table->head[0] = __('Graph name');
	$table->head[1] = __('Description');
	if (give_acl ($config['id_user'], 0, "AW"))
		$table->head[2] = __('Delete');
	$table->data = array ();
	
	foreach ($graphs as $graph) {
		$data = array ();
		
		$data[0] = '<a href="index.php?sec=reporting&sec2=operation/reporting/graph_viewer&view_graph=1&id='.
			$graph['id_graph'].'">'.$graph['name'].'</a>';
		$data[1] = $graph["description"];
		
		if (give_acl ($config['id_user'], 0, "AW")) {
			$data[2] = '<a href="index.php?sec=reporting&sec2=operation/reporting/graph_viewer&delete_graph=1&id='
				.$graph['id_graph'].'" onClick="if (!confirm(\''.__('Are you sure?').'\'))
					return false;"><img src="images/cross.png" /></a>';
		}
		
		array_push ($table->data, $data);
	}
	print_table ($table);
} else {
	echo "<div class='nf'>".__('There are no defined reportings')."</div>";
}

?>
