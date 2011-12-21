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

/*
$buttons ['view']= '<a href="index.php?sec=netf&sec2=operation/netflow/nf&id_name='.$name.'">'
		. html_print_image ("images/lupa.png", true, array ("title" => __('View')))
		. '</a>';
		
$buttons['edit'] = '<a href="index.php?sec=netf&sec2=godmode/netflow/nf_edit">'
		. html_print_image ("images/edit.png", true, array ("title" => __('Filter list')))
		. '</a>';
		
$buttons['add'] = '<a href="index.php?sec=netf&sec2=godmode/netflow/nf_edit_form">'
		. html_print_image ("images/add.png", true, array ("title" => __('Add filter')))
		. '</a>';
		
*/
//Header
ui_print_page_header (__('Netflow Manager'), "images/networkmap/so_cisco_new.png", false, "", true, $buttons);

$delete = (bool) get_parameter ('delete');
$multiple_delete = (bool)get_parameter('multiple_delete', 0);
$id = (int) get_parameter ('id');

if ($delete) {
	$result = db_process_sql_delete ('tnetflow_options',
		array ('id_option' => $id));
		
	if ($result !== false) $result = true;
	else $result = false;
		
	ui_print_result_message ($result,
		__('Successfully deleted'),
		__('Not deleted. Error deleting data'));
}


if ($multiple_delete) {
	$ids = (array)get_parameter('delete_multiple', array());
	
	db_process_sql_begin();
	
	foreach ($ids as $id) {
		$result = db_process_sql_delete ('tnetflow_options',
			array ('id_option' => $id));
	
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

$options = db_get_all_rows_filter ('tnetflow_options', $filter);

if ($options === false)
	$filter = array ();
	
$table->width = '80%';
$table->head = array ();
$table->head[0] = __('Options name');
$table->head[1] = __('Description');
$table->head[2] = __('Action') .
	html_print_checkbox('all_delete', 0, false, true, false, 'check_all_checkboxes();');
	
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->align = array ();
$table->align[2] = 'center';
$table->size = array ();
$table->size[0] = '50%';
$table->size[1] = '40%';
$table->size[2] = '50px';
$table->data = array ();

$total_options = db_get_all_rows_filter ('tnetflow_options', false, 'COUNT(*) AS total');
$total_options = $total_options[0]['total'];

ui_pagination ($total_options, $url);

 foreach ($options as $option) {

	$data = array ();
	

	$data[0] = '<a href="index.php?sec=netf&sec2=godmode/netflow/nf_option_form&id='.$option['id_option'].'">'.$option['id_name'].'</a>';
	
	$data[1] = $option['description'];
	
	$data[2] = "<a onclick='if(confirm(\"" . __('Are you sure?') . "\")) return true; else return false;' 
		href='index.php?sec=netf&sec2=godmode/netflow/nf_manage&delete=1&id=".$option['id_option']."&offset=0'>" . 
		html_print_image('images/cross.png', true, array('title' => __('Delete'))) . "</a>" .
		html_print_checkbox_extended ('delete_multiple[]', $option['id_option'], false, false, '', 'class="check_delete"', true);
	
	array_push ($table->data, $data);
}

if(isset($data)) {
	echo "<form method='post' action='index.php?sec=netf&sec2=godmode/netflow/nf_manage'>";
	html_print_input_hidden('multiple_delete', 1);
	html_print_table ($table);
	echo "<div style='padding-bottom: 20px; text-align: right; width:" . $table->width . "'>";
	html_print_submit_button(__('Delete'), 'delete_btn', false, 'class="sub delete"');
	echo "</div>";
	echo "</form>";
}else {
	echo "<div class='nf'>".__('There are no defined filters')."</div>";
}

echo '<form method="post" action="index.php?sec=netf&sec2=godmode/netflow/nf_option_form">';
	echo "<div style='padding-bottom: 20px; text-align: right; width:" . $table->width . "'>";
	html_print_submit_button (__('Create option'), 'crt', false, 'class="sub wand"');
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
