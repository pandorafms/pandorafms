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

//id report
$id = (int)get_parameter('id');

$buttons['report_list']['active'] = false;
$buttons['report_list'] = '<a href="index.php?sec=netf&sec2=godmode/netflow/nf_report">'
		. html_print_image ("images/edit.png", true, array ("title" => __('Report list')))
		. '</a>';
		
$buttons['report_items']['active'] = true;
$buttons['report_items']['text'] = '<a href="index.php?sec=netf&sec2=godmode/netflow/nf_item_list&id='.$id.'">'
		. html_print_image ("images/god6.png", true, array ("title" => __('Report items')))
		. '</a>';
		
$buttons['edit_report']['active'] = false;
$buttons['edit_report']['text'] = '<a href="index.php?sec=netf&sec2=godmode/netflow/nf_report_form&id='.$id.'">'
		. html_print_image ("images/config.png", true, array ("title" => __('Edit report')))
		. '</a>';
		
//Header
ui_print_page_header (__('Report items'), "images/networkmap/so_cisco_new.png", false, "", true, $buttons);
		
$delete = (bool) get_parameter ('delete');
$multiple_delete = (bool)get_parameter('multiple_delete', 0);
$order = get_parameter('order');

//id item
$id_rc = (int) get_parameter ('id_rc');

if ($order) {
	$dir = get_parameter ('dir');
	$old_order = db_get_value_sql('SELECT `order` FROM tnetflow_report_content WHERE id_rc = ' . $id_rc);
	switch ($dir) {
		case 'up':
			$new_order = $old_order-1;
			break;
		case 'down':
			$new_order = $old_order + 1;
			break;
	}
	$sql = "select id_rc from tnetflow_report_content where id_report=$id and `order`=$new_order";
	$item_cont = db_get_row_sql($sql);
	$id_item_mod = $item_cont['id_rc'];
	$result = db_process_sql_update('tnetflow_report_content', array('`order`' => $new_order), array('id_rc' => $id_rc));
	$result2 = db_process_sql_update('tnetflow_report_content', array('`order`' => $old_order), array('id_rc' => $id_item_mod));
}

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

$reports_item = db_get_all_rows_sql("
		SELECT *
		FROM tnetflow_report_content
		WHERE id_report=$id ORDER BY `order`");

if ($reports_item === false)
	$reports_item = array ();
	
$table->width = '98%';
$table->head = array ();
$table->head[0] = __('Order');
$table->head[1] = __('Filter');
$table->head[2] = __('Max. values');
$table->head[3] = __('Chart type');
$table->head[3] = __('Chart type');
$table->head[4] = __('Action') . html_print_checkbox('all_delete', 0, false, true, false, 'check_all_checkboxes();');
	
$table->style = array ();
$table->style[1] = 'font-weight: bold';
$table->align = array ();
$table->align[1] = 'left';
$table->align[2] = 'center';
$table->align[3] = 'center';
$table->align[4] = 'right';
$table->size = array ();
$table->size[0] = '20px';
$table->size[2] = '5%';
$table->size[3] = '15%';
$table->size[4] = '60px';

$table->data = array ();

$total_reports_item = db_get_all_rows_filter ('tnetflow_report_content', false, 'COUNT(*) AS total');
$total_reports_item = $total_reports_item[0]['total'];

//ui_pagination ($total_reports_item, $url);

$sql = "SELECT id_rc FROM tnetflow_report_content where `order`= (select min(`order`) 
		from tnetflow_report_content 
		where id_report=$id) and id_report=$id";
$item_min = db_get_row_sql($sql);
$first_item = $item_min['id_rc'];

$sql = "SELECT id_rc FROM tnetflow_report_content where `order`= (select max(`order`) 
		from tnetflow_report_content 
		where id_report=$id) and id_report=$id";
$item_max = db_get_row_sql($sql);
$last_item = $item_max['id_rc'];

foreach ($reports_item as $item) {
	
	$data = array ();
	if (($item['id_rc'] == $first_item) && ($item['id_rc'] == $last_item)){
		$data[0] = '<span style="display: block; float: left; width: 16px;">&nbsp;</span>';
	}
	else if (($item['id_rc'] == $first_item) && ($item['id_rc'] != $last_item)){
		$data[0] = '<span style="display: block; float: left; width: 16px;">&nbsp;</span>';
		$data[0] .= '<a href="index.php?sec=netf&sec2=godmode/netflow/nf_item_list&id='.$item['id_report'].'&order=1&dir=down&id_rc='.$item['id_rc'].'">' . html_print_image("images/down.png", true, array("title" => __('Move to down'))) . '</a>';
	}
	else if (($item['id_rc'] == $last_item) && ($item['id_rc'] != $first_item)){
		$data[0] = '<a href="index.php?sec=netf&sec2=godmode/netflow/nf_item_list&id='.$item['id_report'].'&order=1&dir=up&id_rc='.$item['id_rc'].'">' . html_print_image("images/up.png", true, array("title" => __('Move to up'))) . '</a>';
	}
	else {
		$data[0] = '<a href="index.php?sec=netf&sec2=godmode/netflow/nf_item_list&id='.$item['id_report'].'&order=1&dir=up&id_rc='.$item['id_rc'].'">' . html_print_image("images/up.png", true, array("title" => __('Move to up'))) . '</a>';
		$data[0] .= '<a href="index.php?sec=netf&sec2=godmode/netflow/nf_item_list&id='.$item['id_report'].'&order=1&dir=down&id_rc='.$item['id_rc'].'">' . html_print_image("images/down.png", true, array("title" => __('Move to down'))) . '</a>';
	}
	
	$name_filter = db_get_value('id_name', 'tnetflow_filter', 'id_sg', $item['id_filter']);
	$data[1] = '<a href="index.php?sec=netf&sec2=godmode/netflow/nf_report_item&id='.$item['id_report'].'&id_rc='.$item['id_rc'].'">'.$name_filter.'</a>';
	
	$data[2] = $item['max'];
	
	switch ($item['show_graph']) {
		case 0:
			$data[3] = 'Area graph';
			break;
		case 1:
			$data[3] = 'Pie graph';
			break;
		case 2:
			$data[3] = 'Data table';
			break;
		case 3:
			$data[3] = 'Statistics table';
			break;
	}
	
	$data[4] = "<a onclick='if(confirm(\"" . __('Are you sure?') . "\")) return true; else return false;' 
		href='index.php?sec=netf&sec2=godmode/netflow/nf_item_list&delete=1&id_rc=".$item['id_rc']."&id=".$id."&offset=0'>" . 
		html_print_image('images/cross.png', true, array('title' => __('Delete'))) . "</a>" .
		html_print_checkbox_extended ('delete_multiple[]', $item['id_rc'], false, false, '', 'class="check_delete"', true);
	
	array_push ($table->data, $data);
}

if (isset($data)) {
	echo '<form method="post" action="index.php?sec=netf&sec2=godmode/netflow/nf_item_list&id='.$id.'">';
	html_print_input_hidden('multiple_delete', 1);
	html_print_table ($table);
	echo "<div style='padding-bottom: 20px; text-align: right; width:" . $table->width . "'>";
	html_print_submit_button(__('Delete'), 'delete_btn', false, 'class="sub delete"');
	echo "</div>";
	echo "</form>";
}
else {
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
