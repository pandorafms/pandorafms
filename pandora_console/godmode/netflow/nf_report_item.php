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
include_once("include/functions_html.php");
include_once("include/functions_db.php");
include_once("include/functions_netflow.php");
ui_require_javascript_file ('calendar');

check_login ();

if (! check_acl ($config["id_user"], 0, "IW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access event viewer");
	require ("general/noaccess.php");
	return;
}

$id = (int)get_parameter('id');
$id_rc = (int)get_parameter('id_rc');
$update = (string)get_parameter('update', 0);
$create = (string)get_parameter('create', 0);

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
ui_print_page_header (__('Report item editor'), "images/networkmap/so_cisco_new.png", false, "", true, $buttons);

if ($id_rc) {
	$item = netflow_reports_get_content ($id_rc);
	$id_filter = $item['id_filter'];
	$name_filter = db_get_value('id_name', 'tnetflow_filter', 'id_sg', $id_filter);
	$max_val = $item['max'];
	$show_graph = $item['show_graph'];
	
}
else {
	$name_filter = '';
	$max_val = '';
	$show_graph = '';
}

if ($update) {
	$id_filter = get_parameter('id_filter');
	$name_filter = db_get_value('id_name', 'tnetflow_filter', 'id_sg', $id_filter);
	$max_val = get_parameter('max','2');
	$show_graph = get_parameter('show_graph','');

	$result = db_process_sql_update ('tnetflow_report_content',
			array (
				'id_report' => $id,
				'id_filter' => $id_filter,
				'max' => $max_val,
				'show_graph' => $show_graph
				),
			array ('id_rc' => $id_rc));
			
		ui_print_result_message ($result,
			__('Successfully updated'),
			__('Not updated. Error updating data'));
}

if ($create){
	$id_filter = (int)get_parameter('id_filter', 0);
	$name_filter = db_get_value('id_name', 'tnetflow_filter', 'id_sg', $id_filter);
	$max_val = (int)get_parameter('max',2);
	$show_graph = (string)get_parameter('show_graph','');
	
	//insertion order
	$sql = "SELECT max(`order`) as max_order FROM tnetflow_report_content where id_report=$id";
	$result = db_get_row_sql($sql);
	$order = $result['max_order'];
	if ($order == '') {
		$order = 0;
	}
	else {
		$order++;
	}
	
	$values = array (
		'id_report' => $id,
		'id_filter' => $id_filter,
		'max' => $max_val,
		'show_graph' => $show_graph,
		'`order`' => $order
	);
	$id_rc = db_process_sql_insert('tnetflow_report_content', $values);
	if ($id_rc === false) {
		if ($id_filter == 0)
			echo '<h3 class="error">'.__ ('Error creating item. No filter.').'</h3>';
		else
			echo '<h3 class="error">'.__ ('Error creating item').'</h3>';
	}
	else {
		echo '<h3 class="suc">'.__ ('Item created successfully').'</h3>';
	}
}

$table->width = '70%';
$table->border = 0;
$table->cellspacing = 3;
$table->cellpadding = 5;
$table->class = "databox_color";
$table->style[0] = 'vertical-align: top;';

$table->data = array ();

$filters = netflow_get_filters ();
if ($filters === false) {
	$filters = array ();
}	

$own_info = get_user_info ($config['id_user']);
// Get group list that user has access
$groups_user = users_get_groups ($config['id_user'], "IW", $own_info['is_admin'], true);

$groups_id = array();
foreach($groups_user as $key => $groups){
	$groups_id[] = $groups['id_grupo'];
}

$sql = "SELECT * FROM tnetflow_filter WHERE id_group IN (".implode(',',$groups_id).")";
$table->data[0][0] = '<b>'.__('Filter').'</b>';
$table->data[0][1] = html_print_select_from_sql($sql, 'id_filter', $name_filter, '', '', 0, true);

$table->data[1][0] = '<b>'.__('Max. values').'</b>';
		$max_values = array ('2' => '2',
			'5' => '5',
			'10' => '10',
			'15' => '15',
			'20' => '20',
			'25' => '25',
			'50' => '50'
		);
$table->data[1][1] = html_print_select ($max_values, 'max', $max_val, '', '', 0, true);

$table->data[2][0] = '<b>'.__('Chart type').'</b>';
$table->data[2][1] = html_print_select (netflow_get_chart_types (), 'show_graph', $show_graph,'','',0,true);
$table->data[2][1] = html_print_select (netflow_get_chart_types (), 'show_graph', $show_graph,'','',0,true);

echo '<form method="post" action="index.php?sec=netf&sec2=godmode/netflow/nf_report_item&id='.$id.'">';
html_print_table ($table);
echo '<div class="action-buttons" style="width: '.$table->width.'">';

if ($id_rc) {
	html_print_input_hidden ('update', 1);
	html_print_input_hidden ('id_rc', $id_rc);
	html_print_submit_button (__('Update'), 'crt', false, 'class="sub upd"');
}
else {
	html_print_input_hidden ('create', 1);
	html_print_submit_button (__('Create item'), 'crt', false, 'class="sub wand"');
}
echo '</div>';
echo '</form>';
?>