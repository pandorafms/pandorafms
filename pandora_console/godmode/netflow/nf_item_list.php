<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.


global $config;

include_once("include/functions_ui.php");
include_once("include/functions_db.php");
include_once("include/functions_netflow.php");
include_once("include/functions_html.php");

check_login ();

if (! check_acl ($config["id_user"], 0, "IW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access event viewer");
	require ("general/noaccess.php");
	return;
}
		
$buttons['list'] = '<a href="index.php?sec=netf&sec2=godmode/netflow/nf_report">'
		. html_print_image ("images/edit.png", true, array ("title" => __('Report list')))
		. '</a>';
		
//Header
ui_print_page_header (__('Item list'), "images/god6.png", false, "", true, $buttons);

$delete = (bool) get_parameter ('delete');
$multiple_delete = (bool)get_parameter('multiple_delete', 0);
//id report
$id = (int) get_parameter ('id');
//id item
$id_rc = (int) get_parameter ('id_rc');

if ($delete) {
	$result = db_process_sql_delete ('tnetflow_report_content',
		array ('id_rc' => $id_rc));
		
	if ($result !== false) $result = true;
	else $result = false;
		
	ui_print_result_message ($result,
		__('Successfully deleted'),
		__('Not deleted. Error deleting data'));
}


if ($multiple_delete) {
	$ids = (array)get_parameter('delete_multiple', array());

	db_process_sql_begin();
	
	foreach ($ids as $id_delete) {
		$result = db_process_sql_delete ('tnetflow_report_content',
			array ('id_rc' => $id_delete));
	
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

$filter = array ();

$filter['offset'] = (int) get_parameter ('offset');
$filter['limit'] = (int) $config['block_size'];

$reports_item = db_get_all_rows_filter ('tnetflow_report_content', $filter);

$reports_item = db_get_all_rows_sql('
		SELECT *
		FROM tnetflow_report_content
		WHERE id_report = ' . $id);

if ($reports_item === false)
	$reports_item = array ();
	
$table->width = '90%';
$table->head = array ();
$table->head[0] = __('Id item');
$table->head[1] = __('Filter');
$table->head[2] = __('Max values');
$table->head[3] = __('Graph');
$table->head[4] = __('Action') .
	html_print_checkbox('all_delete', 0, false, true, false, 'check_all_checkboxes();');
	
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->align = array ();
$table->align[0] = 'center';
$table->align[2] = 'center';
$table->align[4] = 'right';
$table->size = array ();
$table->size[0] = '10%';
$table->size[1] = '50%';
$table->size[2] = '10%';
$table->size[3] = '30%';
$table->size[4] = '20px';

$table->data = array ();

$total_reports_item = db_get_all_rows_filter ('tnetflow_report_content', false, 'COUNT(*) AS total');
$total_reports_item = $total_reports_item[0]['total'];

//ui_pagination ($total_reports_item, $url);

 foreach ($reports_item as $item) {

	$data = array ();

	$data[0] = '<a href="index.php?sec=netf&sec2=godmode/netflow/nf_report_item&id='.$item['id_report'].'&id_rc='.$item['id_rc'].'">'.$item['id_rc'].'</a>';
	
	$data[1] = $item['id_filter'];
	
	$data[2] = $item['max'];
	
	switch ($item['show_graph']) {
		case 0:
			$data[3] = 'Area graph';
			break;
		case 1:
			$data[3] = 'Pie graph';
			break;
		case 2:
			$data[3] = 'Table values';
			break;
		case 3:
			$data[3] = 'Table total period';
			break;
	}
	//$data[3] = $item['show_graph'];
	
	$data[4] = "<a onclick='if(confirm(\"" . __('Are you sure?') . "\")) return true; else return false;' 
		href='index.php?sec=netf&sec2=godmode/netflow/nf_item_list&delete=1&id_rc=".$item['id_rc']."&id=".$id."&offset=0'>" . 
		html_print_image('images/cross.png', true, array('title' => __('Delete'))) . "</a>" .
		html_print_checkbox_extended ('delete_multiple[]', $item['id_rc'], false, false, '', 'class="check_delete"', true);
	
	array_push ($table->data, $data);
}

if(isset($data)) {
	echo '<form method="post" action="index.php?sec=netf&sec2=godmode/netflow/nf_item_list&id='.$id.'">';
	html_print_input_hidden('multiple_delete', 1);
	html_print_table ($table);
	echo "<div style='padding-bottom: 20px; text-align: right; width:" . $table->width . "'>";
	html_print_submit_button(__('Delete'), 'delete_btn', false, 'class="sub delete"');
	echo "</div>";
	echo "</form>";
}else {
	echo "<div class='nf'>".__('There are no defined items')."</div>";
}

echo '<form method="post" action="index.php?sec=netf&sec2=godmode/netflow/nf_report_item&id='.$id.'">';
	echo "<div style='padding-bottom: 20px; text-align: right; width:" . $table->width . "'>";
	html_print_submit_button (__('Create item'), 'crt', false, 'class="sub wand"');
	echo "</div>";
	echo "</form>";

?>

<script type="text/javascript">

$(document).ready (function () {
	$("textarea").TextAreaResizer ();
});

function check_all_checkboxes() {
	if ($("input[name=all_delete]").attr('checked')) {
		$(".check_delete").attr('checked', true);
	}
	else {
		$(".check_delete").attr('checked', false);
	}
}

</script>
