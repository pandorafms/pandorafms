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

if (! check_acl ($config['id_user'], 0, "IR")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Inventory Module Management");
	require ("general/noaccess.php");
	return;
}

$delete_graph = (bool) get_parameter ('delete_graph');
$view_graph = (bool) get_parameter ('view_graph');
$id = (int) get_parameter ('id');

// Header
ui_print_page_header (__('Reporting')." &raquo; ".__('Custom graphs'), "images/reporting.png", false, "");

// Delete module SQL code
if ($delete_graph) {
	if (check_acl ($config['id_user'], 0, "AW")) {
		$result = db_process_sql_delete("tgraph_source", array('id_graph' =>$id));
		
		if ($result)
			$result = "<h3 class=suc>".__('Successfully deleted')."</h3>";
		else
			$result = "<h3 class=error>".__('Not deleted. Error deleting data')."</h3>";
			
		$result = db_process_sql_delete("tgraph", array('id_graph' =>$id));
		
		if ($result)
			$result = "<h3 class=suc>".__('Successfully deleted')."</h3>";
		else
			$result = "<h3 class=error>".__('Not deleted. Error deleting data')."</h3>";
		
		echo $result;
	}
	else {
		db_pandora_audit("ACL Violation","Trying to delete a graph from access graph builder");
		include ("general/noaccess.php");
		exit;
	}
}


$graphs = custom_graphs_get_user ($config['id_user'], false, true, "IR");

if (! empty ($graphs)) {
	$table->width = '98%';
	$tale->class = 'databox_frame';
	$table->align = array ();
	$table->head = array ();
	$table->head[0] = __('Graph name');
	$table->head[1] = __('Description');
	$table->head[2] = __('Number of Graphs');
	$table->head[3] = __('Group');
	$table->size[2] = '125px';
	$table->size[3] = '50px';
	$table->align[2] = 'center';
	$table->align[3] = 'center';
	if (check_acl ($config['id_user'], 0, "AW")) {
		$table->align[4] = 'center';
		$table->head[4] = __('Op.');
		$table->size[4] = '50px';
	}
	$table->data = array ();
	
	foreach ($graphs as $graph) {
		$data = array ();
		
		$data[0] = '<a href="index.php?sec=reporting&sec2=operation/reporting/graph_viewer&view_graph=1&id='.
			$graph['id_graph'].'">' . $graph['name'] . '</a>';
		
		$data[1] = $graph["description"];
		
		$data[2] = $graph["graphs_count"];
		$data[3] = ui_print_group_icon($graph['id_group'],true);
		
		if (check_acl ($config['id_user'], 0, "AW")) {
			$data[4] = '<a href="index.php?sec=reporting&sec2=godmode/reporting/graph_builder&edit_graph=1&id='.
			$graph['id_graph'].'">'.html_print_image("images/config.png", true).'</a>';
			
			$data[4] .= '&nbsp;&nbsp;';
			
			$data[4] .= '<a href="index.php?sec=reporting&sec2=godmode/reporting/graphs&delete_graph=1&id='
				.$graph['id_graph'].'" onClick="if (!confirm(\''.__('Are you sure?').'\'))
					return false;">' . html_print_image("images/cross.png", true) . '</a>';
		}
		
		array_push ($table->data, $data);
	}
	html_print_table ($table);
}
else { 	 
	echo "<div class='nf'>".__('There are no defined reportings')."</div>"; 	 
}

if (check_acl ($config['id_user'], 0, "AW")) {
	echo '<form method="post" action="index.php?sec=reporting&sec2=godmode/reporting/graph_builder">';
	echo '<div class="action-buttons" style="width: 98%; margin-top: 5px;">';
	html_print_submit_button (__('Create graph'), 'create', false, 'class="sub next"');
	echo "</div>";
	echo "</form>";
}
?>
