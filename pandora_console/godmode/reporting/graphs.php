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

$report_r = check_acl ($config['id_user'], 0, "RR");
$report_w = check_acl ($config['id_user'], 0, "RW");
$report_m = check_acl ($config['id_user'], 0, "RM");
$access = ($report_r == true) ? 'RR' : ($report_w == true) ? 'RW' : ($report_m == true) ? 'RM' : 'RR';
if (!$report_r && !$report_w && !$report_m) {
	db_pandora_audit("ACL Violation",
		"Trying to access Inventory Module Management");
	require ("general/noaccess.php");
	return;
}

$activeTab = get_parameter('tab', 'main');

$enterpriseEnable = false;
if (enterprise_include_once('include/functions_reporting.php') !== ENTERPRISE_NOT_HOOK) {
	$enterpriseEnable = true;
}

$buttons['graph_list'] = array('active' => true,
	'text' => '<a href="index.php?sec=reporting&sec2=godmode/reporting/graphs">' .
	html_print_image("images/list.png", true, array ("title" => __('Graph list'))) .'</a>');

if ($enterpriseEnable) {
	$buttons = reporting_enterprise_add_template_graph_tabs($buttons);
}

$subsection = '';
switch ($activeTab) {
	case 'main':
		$buttons['graph_list']['active'] = true;
		$subsection = ' &raquo; '.__('Graph list');
		break;
	default:
		$subsection = reporting_enterprise_add_graph_template_subsection($activeTab, $buttons);
		break;
}

switch ($activeTab) {
	case 'main':
		require_once('godmode/reporting/graphs.php');
		break;
	default:
		reporting_enterprise_select_graph_template_tab($activeTab);
		break;
}

$delete_graph = (bool) get_parameter ('delete_graph');
$view_graph = (bool) get_parameter ('view_graph');
$id = (int) get_parameter ('id');
$multiple_delete = (bool)get_parameter('multiple_delete', 0);

// Header
ui_print_page_header (__('Reporting')." &raquo; ".__('Custom graphs'), "images/chart.png", false, "", false, $buttons);

// Delete module SQL code
if ($delete_graph) {
	if ( $report_w || $report_m ) {
		
		$exist = db_get_value("id_graph", "tgraph_source", "id_graph", $id);
		if ($exist) {
			$result = db_process_sql_delete("tgraph_source", array('id_graph' =>$id));
			
			if ($result)
				$result = ui_print_success_message(__('Successfully deleted'));
			else
				$result = ui_print_error_message(__('Not deleted. Error deleting data'));
		}
		$result = db_process_sql_delete("tgraph", array('id_graph' =>$id));
		
		if ($result) {
			db_pandora_audit("Report management", "Delete graph #$id");
			$result = ui_print_success_message(__('Successfully deleted'));
		}
		else {
			db_pandora_audit("Report management", "Fail try to delete graph #$id");
			$result = ui_print_error_message(__('Not deleted. Error deleting data'));
		}
		
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
	
	foreach ($ids as $id) {
		$result = db_process_sql_delete ('tgraph',
			array('id_graph' => $id));
		
		if ($result === false) {
			break;
		}
	}
	
	if ($result !== false)
		$result = true;
	else
		$result = false;
	
	$str_ids = implode (',', $ids);
	if ($result) {
		db_pandora_audit("Report management", "Multiple delete graph: $str_ids");
	}
	else {
		db_pandora_audit("Report management", "Fail try to delete graphs: $str_ids");
	}
	
	ui_print_result_message ($result,
		__('Successfully deleted'),
		__('Not deleted. Error deleting data'));
}


$graphs = custom_graphs_get_user ($config['id_user'], false, true, $access);
$offset = (int) get_parameter ("offset");

ui_pagination (count($graphs));

if (!empty ($graphs)) {
	$table = new stdClass();
	$table->width = '100%';
	$table->class = 'databox data';
	$table->align = array ();
	$table->head = array ();
	$table->head[0] = __('Graph name');
	$table->head[1] = __('Description');
	$table->head[2] = __('Number of Graphs');
	$table->head[3] = __('Group');
	$table->size[0] = '30%';
	$table->size[2] = '200px';
	$table->size[3] = '200px';
	$table->align[2] = 'left';
	$table->align[3] = 'left';
	if ($report_w || $report_m) {
		$table->align[4] = 'left';
		$table->head[4] = __('Op.') .
			html_print_checkbox('all_delete', 0, false, true, false,
		'check_all_checkboxes();');
		$table->size[4] = '90px';
	}
	$table->data = array ();

	$result_graphs = array_slice($graphs, $offset, $config['block_size']);

	foreach ($result_graphs as $graph) {
		$data = array ();
		
		$data[0] = '<a href="index.php?sec=reporting&sec2=operation/reporting/graph_viewer&view_graph=1&id='.
			$graph['id_graph'].'">' . $graph['name'] . '</a>';
		
		$data[1] = $graph["description"];
		
		$data[2] = $graph["graphs_count"];
		$data[3] = ui_print_group_icon($graph['id_group'],true);
		
		if (($report_w || $report_m) && users_can_manage_group_all($access)) {
			$data[4] = '<a href="index.php?sec=reporting&sec2=godmode/reporting/graph_builder&edit_graph=1&id='.
			$graph['id_graph'].'">'.html_print_image("images/config.png", true).'</a>';
			
			$data[4] .= '&nbsp;';
			
			$data[4] .= '<a href="index.php?sec=reporting&sec2=godmode/reporting/graphs&delete_graph=1&id='
				.$graph['id_graph'].'" onClick="if (!confirm(\''.__('Are you sure?').'\'))
					return false;">' . html_print_image("images/cross.png", true, array('alt' => __('Delete'), 'title' => __('Delete'))) . '</a>' .
					html_print_checkbox_extended ('delete_multiple[]', $graph['id_graph'], false, false, '', 'class="check_delete" style="margin-left:2px;"', true);
		}
		
		array_push ($table->data, $data);
	}


	if (!empty($result_graphs)){
		echo "<form method='post' style='' action='index.php?sec=reporting&sec2=godmode/reporting/graphs'>";
			html_print_input_hidden('multiple_delete', 1);
			html_print_table ($table);
			echo "<div style='float: right;'>";
				html_print_submit_button(__('Delete'), 'delete_btn', false, 'class="sub delete"');
			echo "</div>";
		echo "</form>";
	}


	echo "<div style='float: right;'>";
		if ($report_w || $report_m) {
			echo '<form method="post" style="float:right;" action="index.php?sec=reporting&sec2=godmode/reporting/graph_builder">';
				html_print_submit_button (__('Create graph'), 'create', false, 'class="sub next" style="margin-right:5px;"');
			echo "</form>";
		}
	echo "</div>";
	ui_pagination (count($graphs));
}
else {
	require_once ($config['homedir'] . "/general/firts_task/custom_graphs.php");
}

?>

<script type="text/javascript">

$("input[name=all_delete]").css("margin-left", "32px");

	function check_all_checkboxes() {
		if ($("input[name=all_delete]").prop("checked")) {
			$(".check_delete").prop("checked", true);
		}
		else {
			$(".check_delete").prop("checked", false);
		}
	}

</script>
