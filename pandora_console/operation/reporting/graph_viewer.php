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



// Login check

check_login ();

require_once ('include/functions_custom_graphs.php');

$delete_graph = (bool) get_parameter ('delete_graph');
$view_graph = (bool) get_parameter ('view_graph');
$id_graph = (int) get_parameter ('id');

// Delete module SQL code
if ($delete_graph) {
	if (check_acl ($config['id_user'], 0, "AW")) {
		$res = db_process_sql_delete('tgraph_source', array('id_graph' => $id_graph));
		
		if ($res)
			$result = ui_print_success_message(__('Successfully deleted'), '', true);
		else
			$result = ui_print_error_message(__('Not deleted. Error deleting data'), '', true);
		
		$res = db_process_sql_delete('tgraph', array('id_graph' => $id_graph));
		
		if ($res)
			$result = ui_print_success_message(__('Successfully deleted'), '', true);
		else
			$result = ui_print_error_message(__('Not deleted. Error deleting data'), '', true);
		echo $result;
	}
	else {
		db_pandora_audit("ACL Violation","Trying to delete a graph from access graph builder");
		include ("general/noaccess.php");
		exit;
	}
}

if ($view_graph) {
	$sql="SELECT * FROM tgraph_source WHERE id_graph = $id_graph";
	$sources = db_get_all_rows_sql($sql);
	
	$sql="SELECT * FROM tgraph WHERE id_graph = $id_graph";
	$graph = db_get_row_sql($sql);
	
	$id_user = $graph["id_user"];
	$private = $graph["private"];
	$width = $graph["width"];
	$height = $graph["height"] + count($sources) * 10;
	
	$zoom = (int) get_parameter ('zoom', 0);
	//Increase the height to fix the leyend rise
	if ($zoom > 0) {
		switch ($zoom) {
			case 1:
				$width = 500;
				$height = 200 + count($sources) * 15;
				break;
			case 2:
				$width = 650;
				$height = 300 + count($sources) * 10;
				break;
			case 3:
				$width = 770;
				$height = 400 + count($sources) * 5;
				break;
		}
	}
	
	// Get different date to search the report.
	$date = (string) get_parameter ('date', date(DATE_FORMAT));
	$time = (string) get_parameter ('time', date(TIME_FORMAT));
	$unixdate = strtotime ($date . ' ' . $time);
	
	$period = (int) get_parameter ('period');
	if (! $period)
		$period = $graph["period"];
	else 
		$period = $period;
	$events = $graph["events"];
	$description = $graph["description"];
	$stacked = (int) get_parameter ('stacked', -1);
	if ($stacked == -1)
		$stacked = $graph["stacked"];
	
	if ($stacked == CUSTOM_GRAPH_BULLET_CHART )
		$height = 50;
	
	if ($stacked == CUSTOM_GRAPH_GAUGE ){
		if ( $graph["height"] < 150 )
			$height = 150;
		elseif( $graph["height"] >= 150 && $graph["height"] < 250 )
				$height = $graph["height"];
			elseif( $graph["height"] >= 250 )
				$height = 200;
	}
	
	$name = $graph["name"];
	if (($graph["private"]==1) && ($graph["id_user"] != $id_user)) {
		db_pandora_audit("ACL Violation",
			"Trying to access to a custom graph not allowed");
		include ("general/noaccess.php");
		exit;
	}
	html_print_input_hidden ('line_width_graph', $config['custom_graph_width']);
	html_print_input_hidden ('custom_graph', 1);
	$url = "index.php?" .
		"sec=reporting&" .
		"sec2=operation/reporting/graph_viewer&" .
		"id=$id_graph&" .
		"view_graph=1";
	
	$options = array();
	
	if (check_acl ($config['id_user'], 0, "RW")) {
		$options = array(
			'main' => array('active' => false,
				'text' => '<a href="index.php?sec=reporting&sec2=godmode/reporting/graph_builder&tab=main&edit_graph=1&id=' . $id_graph . '">' . 
					html_print_image("images/chart.png", true, array ("title" => __('Main data'))) .'</a>'),
			'graph_editor' => array('active' => false,
				'text' => '<a href="index.php?sec=reporting&sec2=godmode/reporting/graph_builder&tab=graph_editor&edit_graph=1&id=' . $id_graph . '">' . 
					html_print_image("images/builder.png", true, array ("title" => __('Graph editor'))) .'</a>')
			);
	}
	
	$options['view']['text'] = '<a href="index.php?sec=reporting&sec2=operation/reporting/graph_viewer&view_graph=1&id=' . $id_graph . '">' . 
		html_print_image("images/operation.png", true,
			array ("title" => __('View graph'))) .'</a>';
	$options['view']['active'] = true;
	
	if ($config["pure"] == 0) {
		$options['screen']['text'] = "<a href='$url&pure=1'>"
			. html_print_image ("images/full_screen.png", true, array ("title" => __('Full screen mode')))
			. "</a>";
	}
	else {
		$options['screen']['text'] = "<a href='$url&pure=0'>"
			. html_print_image ("images/normal_screen.png", true, array ("title" => __('Back to normal mode')))
			. "</a>";
		
		// In full screen, the manage options are not available
		$options = array('view' => $options['view'], 'screen' => $options['screen']);
	}
	
	// Header
	ui_print_page_header (__('Reporting') . " &raquo;  " .
		__('Custom graphs') . " - " . $graph['name'],
		"images/chart.png", false, "", false, $options);
	
	$graph_return = custom_graphs_print($id_graph, $height, $width, $period, $stacked,
			true, $unixdate);
	
	if ($graph_return){
		echo "<table class='databox filters' cellpadding='0' cellspacing='0' width='100%'>";
		echo "<tr><td>";
		echo $graph_return;
		// Add space to the legend transformation
		if ($stacked == CUSTOM_GRAPH_VBARS) {
			echo '<br /><br /><br /><br />';
		}
		echo "</td></tr></table>";
	}
	else {
		ui_print_info_message ( array ( 'no_close' => true, 'message' =>  __('No data.') ) );
	}
	
	$period_label = human_time_description_raw ($period);
	echo "<form method='POST' action='index.php?sec=reporting&sec2=operation/reporting/graph_viewer&view_graph=1&id=$id_graph'>";
	echo "<table class='databox filters' cellpadding='4' cellspacing='4' style='width: 100%'>";
	echo "<tr>";
	
	echo "<td>";
	echo "<b>" . __('Date') . "</b>" . " ";
	echo "</td>";
	
	echo "<td>";
	echo html_print_input_text ('date', $date, '', 12, 10, true). ' ';
	echo "</td>";
	
	echo "<td>";
	echo html_print_input_text ('time', $time, '', 7, 7, true). ' ';
	echo "</td>";
	
	echo "<td class='datos'>";
	echo "<b>" . __('Time range') . "</b>";
	echo "</td>";
	
	echo "<td class='datos'>";
	echo html_print_extended_select_for_time ('period', $period, '', '', '0', 10, true);
	echo "</td>";
	
	echo "<td class='datos'>";
	$stackeds = array ();
	$stackeds[0] = __('Graph defined');
	$stackeds[CUSTOM_GRAPH_AREA] = __('Area');
	$stackeds[CUSTOM_GRAPH_STACKED_AREA] = __('Stacked area');
	$stackeds[CUSTOM_GRAPH_LINE] = __('Line');
	$stackeds[CUSTOM_GRAPH_STACKED_LINE] = __('Stacked line');
	$stackeds[CUSTOM_GRAPH_BULLET_CHART] = __('Bullet chart');
	$stackeds[CUSTOM_GRAPH_GAUGE] = __('Gauge');
	$stackeds[CUSTOM_GRAPH_HBARS] = __('Horizontal Bars');
	$stackeds[CUSTOM_GRAPH_VBARS] = __('Vertical Bars');
	$stackeds[CUSTOM_GRAPH_PIE] = __('Pie');
	html_print_select ($stackeds, 'stacked', $stacked , '', '', -1, false, false);
	echo "</td>";
	
	echo "<td class='datos'>";
	$zooms = array();
	$zooms[0] = __('Graph defined');
	$zooms[1] = __('Zoom x1');
	$zooms[2] = __('Zoom x2');
	$zooms[3] = __('Zoom x3');
	html_print_select ($zooms, 'zoom', $zoom , '', '', 0);
	echo "</td>";
	
	echo "<td class='datos'>";
	echo "<input type=submit value='" . __('Refresh') . "' class='sub upd'>";
	echo "</td>";
	
	echo "</tr>";
	echo "</table>";
	echo "</form>";
	/* We must add javascript here. Otherwise, the date picker won't 
	work if the date is not correct because php is returning. */
	
	ui_include_time_picker();
	ui_require_jquery_file("ui.datepicker-" . get_user_language(), "include/javascript/i18n/");
	ui_require_jquery_file("");
	?>
	<script language="javascript" type="text/javascript">
	
	$(document).ready (function () {
		$("#loading").slideUp ();
		$("#text-time").timepicker({
			showSecond: true,
			timeFormat: '<?php echo TIME_FORMAT_JS; ?>',
			timeOnlyTitle: '<?php echo __('Choose time');?>',
			timeText: '<?php echo __('Time');?>',
			hourText: '<?php echo __('Hour');?>',
			minuteText: '<?php echo __('Minute');?>',
			secondText: '<?php echo __('Second');?>',
			currentText: '<?php echo __('Now');?>',
			closeText: '<?php echo __('Close');?>'});
		
		$.datepicker.setDefaults($.datepicker.regional[ "<?php echo get_user_language(); ?>"]);
		
		$("#text-date").datepicker({
			dateFormat: "<?php echo DATE_FORMAT_JS; ?>",
			changeMonth: true,
			changeYear: true,
			showAnim: "slideDown"});
	});
	</script>
	
	<?php
	$datetime = strtotime ($date.' '.$time);
	$report["datetime"] = $datetime;
	
	if ($datetime === false || $datetime == -1) {
		ui_print_error_message(__('Invalid date selected'));
		return;
	}
	return;
}

// Header
ui_print_page_header (__('Reporting'). " &raquo;  ".__('Custom graph viewer'), "images/reporting.png", false, "", false, "" );


$graphs = custom_graphs_get_user ();
if (! empty ($graphs)) {
	$table = new stdClass();
	$table->width = '100%';
	$tale->class = 'databox_frame';
	$table->align = array ();
	$table->align[2] = 'center';
	$table->head = array ();
	$table->head[0] = __('Graph name');
	$table->head[1] = __('Description');
	$table->data = array ();
	
	foreach ($graphs as $graph) {
		$data = array ();
		
		$data[0] = '<a href="index.php?sec=reporting&sec2=operation/reporting/graph_viewer&view_graph=1&id='.
			$graph['id_graph'].'">'.$graph['name'].'</a>';
		$data[1] = $graph["description"];
		
		array_push ($table->data, $data);
	}
	html_print_table ($table);
}
else {
	echo "<div class='nf'>".__('There are no defined reportings')."</div>";
}
?>
