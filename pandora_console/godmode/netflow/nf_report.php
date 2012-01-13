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

//Header
ui_print_page_header (__('Report Manager'), "images/networkmap/so_cisco_new.png", false, "", true);

$delete = (bool) get_parameter ('delete');
$multiple_delete = (bool)get_parameter('multiple_delete', 0);
$id = (int) get_parameter ('id');

if ($delete) {
	$result = db_process_sql_delete ('tnetflow_report',
		array ('id_report' => $id));
		
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
		$result = db_process_sql_delete ('tnetflow_report',
			array ('id_report' => $id));
	
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
// Get group list that user has access
$groups_user = users_get_groups ($config['id_user'], "IW", $own_info['is_admin'], true);

$groups_id = array();
foreach($groups_user as $key => $groups){
	$groups_id[] = $groups['id_grupo'];
}

$sql = "SELECT * FROM tnetflow_report WHERE id_group IN (".implode(',',$groups_id).")";

$reports = db_get_all_rows_sql($sql);
if ($reports === false)
	$reports = array();
	
$table->width = '98%';
$table->head = array ();
$table->head[0] = __('Report name');
$table->head[1] = __('Description');
$table->head[2] = __('Group');
$table->head[3] = __('Action') .
	html_print_checkbox('all_delete', 0, false, true, false, 'check_all_checkboxes();');
	
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->align = array ();
$table->align[2] = 'center';
$table->align[3] = 'center';
$table->size = array ();
$table->size[0] = '40%';
$table->size[1] = '30%';
$table->size[2] = '20%';
$table->size[3] = '50px';
$table->data = array ();

$total_reports = db_get_all_rows_filter ('tnetflow_report', false, 'COUNT(*) AS total');
$total_reports = $total_reports[0]['total'];

//ui_pagination ($total_reports, $url);

 foreach ($reports as $report) {

	$data = array ();
	$data[0] = '<a href="index.php?sec=netf&sec2=godmode/netflow/nf_report_form&id='.$report['id_report'].'">'.$report['id_name'].'</a>';
	
	$data[1] = $report['description'];
	
	$group = db_get_value('nombre','tgrupo', 'id_grupo', $report['id_group']);
	if ($group == '')
		$group = 'All';
	$data[2] = $group;
	
	$data[3] = "<a onclick='if(confirm(\"" . __('Are you sure?') . "\")) return true; else return false;' 
		href='index.php?sec=netf&sec2=godmode/netflow/nf_report&delete=1&id=".$report['id_report']."&offset=0'>" . 
		html_print_image('images/cross.png', true, array('title' => __('Delete'))) . "</a>" .
		html_print_checkbox_extended ('delete_multiple[]', $report['id_report'], false, false, '', 'class="check_delete"', true);
	
	array_push ($table->data, $data);
}

if(isset($data)) {
	echo "<form method='post' action='index.php?sec=netf&sec2=godmode/netflow/nf_report'>";
	html_print_input_hidden('multiple_delete', 1);
	html_print_table ($table);
	echo "<div style='padding-bottom: 20px; text-align: right; width:" . $table->width . "'>";
	html_print_submit_button(__('Delete'), 'delete_btn', false, 'class="sub delete"');
	echo "</div>";
	echo "</form>";
}else {
	echo "<div class='nf'>".__('There are no defined reports')."</div>";
}

echo '<form method="post" action="index.php?sec=netf&sec2=godmode/netflow/nf_report_form">';
	echo "<div style='padding-bottom: 20px; text-align: right; width:" . $table->width . "'>";
	html_print_submit_button (__('Create report'), 'crt', false, 'class="sub wand"');
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
