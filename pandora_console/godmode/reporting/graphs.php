<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Load global variables
global $config;

require_once ('include/functions_custom_graphs.php');

// Check user credentials
check_login ();

if (! give_acl ($config['id_user'], 0, "IW")) {
	pandora_audit("ACL Violation",
		"Trying to access Inventory Module Management");
	require ("general/noaccess.php");
	return;
}

$delete_graph = (bool) get_parameter ('delete_graph');
$view_graph = (bool) get_parameter ('view_graph');
$id = (int) get_parameter ('id');

// Header
print_page_header (__('Graphs management'), "", false, "", true);

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
	}
	else {
		pandora_audit("ACL Violation","Trying to delete a graph from access graph builder");
		include ("general/noaccess.php");
		exit;
	}
}

$graphs = get_user_custom_graphs ();

if (! empty ($graphs)) {
	$table->width = '720px';
	$tale->class = 'databox_frame';
	$table->align = array ();
	$table->align[0] = 'center';
	$table->align[3] = 'right';
	$table->align[4] = 'center';
	$table->head = array ();
	$table->head[0] = __('View');
	$table->head[1] = __('Graph name');
	$table->head[2] = __('Description');
	$table->head[3] = __('Number of Graphs');
	$table->head[4] = __('Group');
	$table->size[0] = '20px';
	$table->size[3] = '125px';
	$table->size[4] = '50px';
	if (give_acl ($config['id_user'], 0, "AW")) {
		$table->align[5] = 'center';
		$table->head[5] = __('Delete');
		$table->size[5] = '50px';
	}
	$table->data = array ();
	
	foreach ($graphs as $graph) {
		$data = array ();
		
		$data[0] = '<a href="index.php?sec=reporting&sec2=operation/reporting/graph_viewer&view_graph=1&id='.
			$graph['id_graph'].'">' . "<img src='images/eye.png'></a>" . '</a>';
		$data[1] = '<a href="index.php?sec=greporting&sec2=godmode/reporting/graph_builder&edit_graph=1&id='.
			$graph['id_graph'].'">'.$graph['name'].'</a>';
		$data[2] = $graph["description"];
		
		$data[3] = $graph["graphs_count"];
		$data[4] = print_group_icon($graph['id_group'],true);
		
		if (give_acl ($config['id_user'], 0, "AW")) {
			$data[5] = '<a href="index.php?sec=greporting&sec2=godmode/reporting/graphs&delete_graph=1&id='
				.$graph['id_graph'].'" onClick="if (!confirm(\''.__('Are you sure?').'\'))
					return false;"><img src="images/cross.png" /></a>';
		}
		
		array_push ($table->data, $data);
	}
	print_table ($table);
}
else {
	echo "<div class='nf'>".__('There are no defined reportings')."</div>";
}

echo '<form method="post" action="index.php?sec=greporting&sec2=godmode/reporting/graph_builder">';
echo '<div class="action-buttons" style="width: 720px;">';
print_submit_button (__('Create graph'), 'create', false, 'class="sub next"');
echo "</div>";
echo "</form>";
?>
