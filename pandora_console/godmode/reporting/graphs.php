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

if (! check_acl ($config['id_user'], 0, "IW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Inventory Module Management");
	require ("general/noaccess.php");
	return;
}

$buttons['graph_list'] = array('active' => true,
		'text' => '<a href="index.php?sec=greporting&sec2=godmode/reporting/graphs">' .
		html_print_image("images/god6.png", true, array ("title" => __('Graph list'))) .'</a>');

$buttons['wizard'] = array('active' => false,
		'text' => '<a href="index.php?sec=greporting&sec2=godmode/reporting/graph_template_wizard">' .
		html_print_image("images/wand.png", true, array ("title" => __('Wizard'))) .'</a>');

$buttons['template'] = array('active' => false,
		'text' => '<a href="index.php?sec=greporting&sec2=godmode/reporting/graph_template_list">' .
		html_print_image("images/paste_plain.png", true, array ("title" => __('Templates'))) .'</a>');
	
$delete_graph = (bool) get_parameter ('delete_graph');
$view_graph = (bool) get_parameter ('view_graph');
$id = (int) get_parameter ('id');
$multiple_delete = (bool)get_parameter('multiple_delete', 0);

// Header
ui_print_page_header (__('Graphs management'), "", false, "", true, $buttons);

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

if ($multiple_delete) {
	$ids = (array)get_parameter('delete_multiple', array());
	
	db_process_sql_begin();
	
	foreach ($ids as $id) {
		$result = db_process_sql_delete ('tgraph',
			array ('id_graph' => $id));
	
		if ($result === false) {
			db_process_sql_rollback();
			break;
		}
	}
	
	if ($result !== false) {
		db_process_sql_commit();
	}
	
	if ($result !== false) $result = true;
	else $result = false;
		
	ui_print_result_message ($result,
		__('Successfully deleted'),
		__('Not deleted. Error deleting data'));
}
$own_info = get_user_info ($config['id_user']);
if ($own_info['is_admin'] || check_acl ($config['id_user'], 0, "PM"))
	$return_all_group = true;
else
	$return_all_group = false;
	
$graphs = custom_graphs_get_user ($config['id_user'], false, $return_all_group, "IW");

if (! empty ($graphs)) {
	$table->width = '98%';
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
	if (check_acl ($config['id_user'], 0, "AW")) {
		$table->align[5] = 'center';
		$table->head[5] = __('Delete'). html_print_checkbox('all_delete', 0, false, true, false, 'check_all_checkboxes();');
		$table->size[5] = '50px';
	}
	$table->data = array ();
	
	foreach ($graphs as $graph) {
		$data = array ();
		
		$data[0] = '<a href="index.php?sec=reporting&sec2=operation/reporting/graph_viewer&view_graph=1&id='.
			$graph['id_graph'].'">' . html_print_image('images/eye.png', true) . "</a>" . '</a>';
		$data[1] = '<a href="index.php?sec=greporting&sec2=godmode/reporting/graph_builder&edit_graph=1&id='.
			$graph['id_graph'].'">'.$graph['name'].'</a>';
		$data[2] = $graph["description"];
		
		$data[3] = $graph["graphs_count"];
		$data[4] = ui_print_group_icon($graph['id_group'],true);
		
		if (check_acl ($config['id_user'], 0, "AW")) {
			$data[5] = '<a href="index.php?sec=greporting&sec2=godmode/reporting/graphs&delete_graph=1&id='
				.$graph['id_graph'].'" onClick="if (!confirm(\''.__('Are you sure?').'\'))
					return false;">' . html_print_image("images/cross.png", true) . '</a>' .
					html_print_checkbox_extended ('delete_multiple[]', $graph['id_graph'], false, false, '', 'class="check_delete"', true);
		}
		
		array_push ($table->data, $data);
	}
	
	echo "<form method='post' action='index.php?sec=greporting&sec2=godmode/reporting/graphs'>";
	html_print_input_hidden('multiple_delete', 1);
	html_print_table ($table);
	echo "<div style='padding-bottom: 20px; text-align: right; width:" . $table->width . "'>";
	html_print_submit_button(__('Delete'), 'delete_btn', false, 'class="sub delete"');
	echo "</div>";
	echo "</form>";
}
else {
	echo "<div class='nf'>".__('There are no defined reportings')."</div>";
}
	
echo '<form method="post" action="index.php?sec=greporting&sec2=godmode/reporting/graph_builder">';
echo '<div class="action-buttons" style="width: 98%;">';
html_print_submit_button (__('Create graph'), 'create', false, 'class="sub next"');
echo "</div>";
echo "</form>";
?>

<script type="text/javascript">

function check_all_checkboxes() {
	if ($("input[name=all_delete]").attr('checked')) {
		$(".check_delete").attr('checked', true);
	}
	else {
		$(".check_delete").attr('checked', false);
	}
}

</script>
