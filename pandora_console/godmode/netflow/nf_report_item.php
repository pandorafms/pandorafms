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

$buttons['list_items'] = '<a href="index.php?sec=netf&sec2=godmode/netflow/nf_item_list&id='.$id.'">'
		. html_print_image ("images/god6.png", true, array ("title" => __('Item list')))
		. '</a>';
		
$buttons['list'] = '<a href="index.php?sec=netf&sec2=godmode/netflow/nf_report">'
		. html_print_image ("images/edit.png", true, array ("title" => __('Report list')))
		. '</a>';
//Header
ui_print_page_header (__('Netflow Report'), "images/networkmap/so_cisco_new.png", false, "", true, $buttons);


if ($id_rc) {
	$item = netflow_reports_get_content ($id_rc);
	$date = $item['date'];
	$period = $item['period'];
	$name_filter = $item['id_filter'];
	$max_val = $item['max'];
	$show_graph = $item['show_graph'];
} else {
	$date = '';
	$period ='';
	$name_filter = '';
	$max_val = '';
	$show_graph = '';
}

if ($update) {
	$date = get_parameter_post ('date');
	$time = get_parameter_post ('time');
	$period = get_parameter ('period');
	$name_filter = get_parameter('id_filter');
	$max_val = get_parameter('max','2');
	$show_graph = get_parameter('show_graph','');

	$date = str_replace('-','/',$date);
	$timedate = $date .".".$time;
	$date_time = strtotime ($date." ".$time);
	
	$result = db_process_sql_update ('tnetflow_report_content',
			array (
				'id_report' => $id,
				'id_filter' => $name_filter,
				'date' => $date_time,
				'period' => $period,
				'max' => $max_val,
				'show_graph' => $show_graph
				),
			array ('id_rc' => $id_rc));
			
		ui_print_result_message ($result,
			__('Successfully updated'),
			__('Not updated. Error updating data'));
}

if ($create){
	$date = get_parameter_post ('date');
	$time = get_parameter_post ('time');
	$period = get_parameter ('period');
	$name_filter = get_parameter('id_filter');
	$max_val = get_parameter('max','2');
	$show_graph = get_parameter('show_graph','');

	$date = str_replace('-','/',$date);
	$timedate = $date .".".$time;
	$date_time = strtotime ($date." ".$time);

	$sql1 = "select id_filter from tnetflow_report_content where id_report='".$id."'";
	$filters_aux = db_get_all_rows_sql($sql1);
	$exist = false;
	foreach($filters_aux as $filter_aux){
		if ($name_filter == $filter_aux['id_filter']){
			$exist = true;
			echo '<h3 class="error">'.__ ('Error creating item. Filter already exists.').'</h3>';
			break;
		}
	}
	if (!$exist){
		$values = array (
			'id_report' => $id,
			'id_filter' => $name_filter,
			'date' => $date_time,
			'period' => $period,
			'max' => $max_val,
			'show_graph' => $show_graph
			);
		$result = db_process_sql_insert('tnetflow_report_content', $values);

		if ($result === false)
				echo '<h3 class="error">'.__ ('Error creating item').'</h3>';
			else
				echo '<h3 class="suc">'.__ ('Item created successfully').'</h3>';
	}
}
$table->width = '80%';
$table->border = 0;
$table->cellspacing = 3;
$table->cellpadding = 5;
$table->class = "databox_color";
$table->style[0] = 'vertical-align: top;';

$table->data = array ();

$table->data[0][0] = '<b>'.__('Date').'</b>';

$table->data[0][1] = html_print_input_text ('date', date ("Y/m/d", get_system_time () - 86400), false, 10, 10, true);
$table->data[0][1] .= html_print_image ("images/calendar_view_day.png", true, array ("alt" => "calendar", "onclick" => "scwShow(scwID('text-date'),this);"));
$table->data[0][1] .= html_print_input_text ('time', date ("H:i:s", get_system_time () - 86400), false, 10, 5, true);

$table->data[1][0] = '<b>'.__('Interval').'</b>';
	$values_period = array ('600' => __('10 mins'),
		'900' => __('15 mins'),
		'1800' => __('30 mins'),
		'3600' => __('1 hour'),
		'7200' => __('2 hours'),
		'18000' => __('5 hours'),
		'43200' => __('12 hours'),
		'86400' => __('1 day'),
		'172800' => __('2 days'),
		'432000' => __('5 days'),
		'1296000' => __('15 days'),
		'604800' => __('Last week'),
		'2592000' => __('Last month'),
		'5184000' => __('2 months'),
		'7776000' => __('3 months'),
		'15552000' => __('6 months'),
		'31104000' => __('Last year'),
		'62208000' => __('2 years')
					);
$table->data[1][1] = html_print_select ($values_period, 'period', $period, '', '', 0, true, false, false);

$filters = netflow_get_filters ();
if ($filters === false) {
	$filters = array ();
}	
$table->data[2][0] = '<b>'.__('Filters').'</b>';
$table->data[2][1] = html_print_select($filters, 'id_filter', $name_filter, '', '', 0, true);

$table->data[3][0] = '<b>'.__('Max values aggregated').'</b>';
		$max_values = array ('2' => '2',
			'5' => '5',
			'10' => '10',
			'15' => '15',
			'20' => '20',
			'25' => '25',
			'50' => '50'
		);
$table->data[3][1] = html_print_select ($max_values, 'max', $max_val, '', '', 0, true);

$table->data[4][0] = '<b>'.__('Elements').'</b>';

$show_graph_options = Array();
$show_graph_options[0] = __('Area graph');
$show_graph_options[1] = __('Pie graph');
$show_graph_options[2] = __('Table values');
$show_graph_options[3] = __('Total period');

$table->data[4][1] = html_print_select ($show_graph_options, 'show_graph', $show_graph,'','',0,true);

echo '<form method="post" action="index.php?sec=netf&sec2=godmode/netflow/nf_report_item&id='.$id.'">';
html_print_table ($table);
echo '<div class="action-buttons" style="width: '.$table->width.'">';

if ($id_rc) {
	html_print_input_hidden ('update', 1);
	html_print_input_hidden ('id_rc', $id_rc);
	html_print_submit_button (__('Update'), 'crt', false, 'class="sub upd"');
} else {
	html_print_input_hidden ('create', 1);
	html_print_submit_button (__('Create item'), 'crt', false, 'class="sub wand"');
}
echo '</div>';
echo '</form>';

?>
