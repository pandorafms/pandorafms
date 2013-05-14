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

// Load global vars
global $config;

check_login ();

if (! check_acl ($config["id_user"], 0, "EW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access event viewer");
	require ("general/noaccess.php");
	return;
}

$delete = (bool) get_parameter ('delete', 0);
$multiple_delete = (bool)get_parameter('multiple_delete', 0);

if ($delete){
	
	$id = (int) get_parameter('id');
	
	$id_filter = db_get_value('id_name', 'tevent_filter', 'id_filter', $id);
	
	if ($id_filter === false) {
		$result = false;
	}
	else {
		$result = db_process_sql_delete ('tevent_filter', array ('id_filter' => $id));
	}
	
	if ($result !== false) {
		$result = true;
	}
	else {
		$result = false;
	}
	
	ui_print_result_message ($result,
		__('Successfully deleted'),
		__('Not deleted. Error deleting data'));
	
}

if ($multiple_delete) {
	$ids = (array)get_parameter('delete_multiple', array());
	
	foreach ($ids as $id) {
		$result = db_process_sql_delete ('tevent_filter',
			array ('id_filter' => $id));
		
		if ($result === false) {
			break;
		}
	}
	
	if ($result !== false) $result = true;
	else $result = false;
	
	ui_print_result_message ($result,
		__('Successfully deleted'),
		__('Not deleted. Error deleting data'));
}

$own_info = get_user_info ($config['id_user']);
// Get group list that user has access
$groups_user = users_get_groups ($config['id_user'], "EW", users_can_manage_group_all(), true);

$sql = "SELECT * FROM tevent_filter WHERE id_group_filter IN (".implode(',', array_keys ($groups_user)).")";
$filters = db_get_all_rows_sql($sql);

if ($filters === false)
	$filters = array ();

$table->width = '98%';
$table->head = array ();
$table->head[0] = __('Name');
$table->head[1] = __('Filter group');
$table->head[2] = __('Event type');
$table->head[3] = __('Event status');
$table->head[4] = __('Severity');
$table->head[5] = __('Action') .
	html_print_checkbox('all_delete', 0, false, true, false, 'check_all_checkboxes();');
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->align = array ();
$table->align[1] = 'center';
$table->align[2] = 'center';
$table->align[3] = 'center';
$table->align[4] = 'center';
$table->align[5] = 'center';
$table->size = array ();
$table->size[0] = '50%';
$table->size[1] = '5px';
$table->size[2] = '80px';
$table->size[3] = '80px';
$table->size[4] = '80px';
$table->size[5] = '40px';
$table->data = array ();

$total_filters = db_get_all_rows_filter ('tevent_filter', false, 'COUNT(*) AS total');
$total_filters = $total_filters[0]['total'];

//ui_pagination ($total_filters, $url);

foreach ($filters as $filter) {
	$data = array ();
	
	$data[0] = '<a href="index.php?sec=geventos&sec2=godmode/events/events&section=edit_filter&id=' . $filter['id_filter'] . '&pure=' . $config['pure'] . '">'.$filter['id_name'].'</a>';
	$data[1] = ui_print_group_icon ($filter['id_group_filter'], true);
	$data[2] = events_get_event_types($filter['event_type']);
	$data[3] = events_get_status($filter['status']);
	$data[4] = events_get_severity_types($filter['severity']);
	$data[5] = "<a onclick='if(confirm(\"" . __('Are you sure?') . "\")) return true; else return false;' 
		href='index.php?sec=geventos&sec2=godmode/events/events&section=filter&delete=1&id=".$filter['id_filter']."&offset=0&pure=".$config['pure']."'>" . 
		html_print_image('images/cross.png', true, array('title' => __('Delete'))) . "</a>" .
		html_print_checkbox_extended ('delete_multiple[]', $filter['id_filter'], false, false, '', 'class="check_delete"', true);
	
	array_push ($table->data, $data);
}

if (isset($data)) {
	echo "<form method='post' action='index.php?sec=geventos&sec2=godmode/events/events&amp;pure=".$config['pure']."'>";
	html_print_input_hidden('multiple_delete', 1);
	html_print_table ($table);
	echo "<div style='padding-bottom: 20px; text-align: right; width:" . $table->width . "'>";
	html_print_submit_button(__('Delete'), 'delete_btn', false, 'class="sub delete"');
	echo "</div>";
	echo "</form>";
}
else {
	echo "<div class='nf'>" .
		__('There are no defined filters') . "</div>";
}

echo '<form method="post" action="index.php?sec=geventos&sec2=godmode/events/events&section=edit_filter&amp;pure='.$config['pure'].'">';
	echo "<div style='padding-bottom: 20px; text-align: right; width:" . $table->width . "'>";
	html_print_submit_button (__('Create filter'), 'crt', false, 'class="sub wand"');
	echo "</div>";
	echo '</form>';

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
