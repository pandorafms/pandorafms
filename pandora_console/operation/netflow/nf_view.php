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

include_once("include/functions_graph.php");
include_once("include/functions_ui.php");
include_once("include/functions_netflow.php");
ui_require_javascript_file ('calendar');

check_login ();

if (! check_acl ($config["id_user"], 0, "AR")) {
	db_pandora_audit("ACL Violation",
		"Trying to access event viewer");
	require ("general/noaccess.php");
	return;
}

$id = io_safe_input (get_parameter('id'));

if ($id) {
	$permission = netflow_check_report_group ($id, true);
	if (!$permission) { //no tiene permisos para acceder a un informe
		require ("general/noaccess.php");
		return;
	}
}

$period = get_parameter('period', '86400');
$update_date = get_parameter('update_date', 0);
if($update_date){
	$date = get_parameter_post ('date');
	$time = get_parameter_post ('time');
	$interval = get_parameter('period','86400');
} else {
	$date = date ("Y/m/d", get_system_time ());
	$time = date ("H:i:s", get_system_time ());
	$interval ='86400';
}
$end_date = strtotime ($date . " " . $time);
$start_date = $end_date - $interval;

$buttons['report_list'] = '<a href="index.php?sec=netf&sec2=operation/netflow/nf_reporting">'
		. html_print_image ("images/edit.png", true, array ("title" => __('Report list')))
		. '</a>';
		
//Header
ui_print_page_header (__('Netflow'), "images/networkmap/so_cisco_new.png", false, "", false, $buttons);

echo"<h4>".__('Filter graph')."</h4>";

echo '<form method="post" action="index.php?sec=netf&sec2=operation/netflow/nf_view&amp;id='.$id.'">';

	$table->width = '60%';
	$table->border = 0;
	$table->cellspacing = 3;
	$table->cellpadding = 5;
	$table->class = "databox_color";
	$table->style[0] = 'vertical-align: top;';

	$table->data = array ();

	$table->data[0][0] = '<b>'.__('Date').'</b>';

	$table->data[0][1] = html_print_input_text ('date', $date, false, 10, 10, true);
	$table->data[0][1] .= html_print_image ("images/calendar_view_day.png", true, array ("alt" => "calendar", "onclick" => "scwShow(scwID('text-date'),this);"));
	$table->data[0][1] .= html_print_input_text ('time', $time, false, 10, 5, true);

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
	html_print_table ($table);

	echo '<div class="action-buttons" style="width:60%;">';
	html_print_submit_button (__('Update'), 'updbutton', false, 'class="sub upd"');
	html_print_input_hidden ('update_date', 1);
	echo '</div>';
echo'</form>';

if (empty ($id)){
	echo fs_error_image();
	return;
}

$report_name = db_get_value('id_name', 'tnetflow_report', 'id_report', $id);
echo"<h3>$report_name</h3>";

$all_rcs = db_get_all_rows_sql("SELECT id_rc FROM tnetflow_report_content WHERE id_report='$id'");
if (empty ($all_rcs)) {
	echo fs_error_image();
	return;
}

// Process report items
for ($x = 0; isset($all_rcs[$x]['id_rc']); $x++) {

	// Get report item
	$report_id = $all_rcs[$x]['id_rc'];
	$content_report = db_get_row_sql("SELECT * FROM tnetflow_report_content WHERE id_rc='$report_id'");
	$content_id = $content_report['id_rc'];
	$max_aggregates= $content_report['max'];
	$type = $content_report['show_graph'];
	
	// Get item filters
	$filter = db_get_row_sql("SELECT * FROM tnetflow_filter WHERE id_sg = '" . io_safe_input ($content_report['id_filter']) . "'", false, true);
	$command = netflow_get_command ($filter);
	$title = $filter['id_name'];
	$aggregate = $filter['aggregate'];
	$unit = $filter['output'];
	
	// Process item
	switch ($type){
		case '0':
			$unique_id = $report_id . '_' . $content_id . '_' . ($end_date - $start_date);
			$data = netflow_get_data ($start_date, $end_date, $command, $unique_id, $aggregate, $max_aggregates, $unit);
			if ($aggregate != 'none') {
				echo '<h4>' . $title . ' (' . __($aggregate) . '/' . __($unit) . ')</h4>';
				echo graph_netflow_aggregate_area($data, $interval, 660, 320, 0);
			} else {
				echo '<h4>' . $title . ' (' . __($unit) . ')</h4>';
				echo graph_netflow_total_area($data, $interval, 660, 320, 0);
			}
			break;
		case '1':
			$data = netflow_get_stats ($start_date, $end_date, $command, $aggregate, $max_aggregates, $unit);
			echo '<h4>' . $title . ' (' . __($aggregate) . '/' . __($unit) . ')</h4>';
			echo graph_netflow_aggregate_pie($data, $aggregate, $unit, $title);
			break;
		case '2':
			$unique_id = $report_id . '_' . $content_id . '_' . ($end_date - $start_date);
			$data = netflow_get_data ($start_date, $end_date, $command, $unique_id, $aggregate, $max_aggregates, $unit);
			echo '<h4>' . $title . ' (' . __($aggregate) . '/' . __($unit) . ')</h4>';
			echo netflow_data_table ($data, $start_date, $end_date, $aggregate);
			break;
		case '3':
			$data = netflow_get_stats ($start_date, $end_date, $command, $aggregate, $max_aggregates, $unit);
			echo '<h4>' . $title . '</h4>';
			echo netflow_stat_table ($data, $start_date, $end_date, $aggregate, $unit);
			break;
		default:
			echo fs_error_image();
			break;
	}
}

?>


