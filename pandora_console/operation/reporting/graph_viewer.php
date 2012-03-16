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
			$result = "<h3 class=suc>".__('Successfully deleted')."</h3>";
		else
			$result = "<h3 class=error>".__('Not deleted. Error deleting data')."</h3>";
		
		$res = db_process_sql_delete('tgraph', array('id_graph' => $id_graph));
		
		if ($res)
			$result = "<h3 class=suc>".__('Successfully deleted')."</h3>";
		else
			$result = "<h3 class=error>".__('Not deleted. Error deleting data')."</h3>";
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
	$date = (string) get_parameter ('date', date ('Y-m-j'));
	$time = (string) get_parameter ('time', date ('h:iA'));
	$unixdate = strtotime ($date.' '.$time);

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
	
	$name = $graph["name"];
	if (($graph["private"]==1) && ($graph["id_user"] != $id_user)){
		db_pandora_audit("ACL Violation","Trying to access to a custom graph not allowed");
		include ("general/noaccess.php");
		exit;
	}

	$url = "index.php?sec=reporting&sec2=operation/reporting/graph_viewer&id=$id_graph&view_graph=1";

	if ($config["pure"] == 0) {
		$options['screen'] = "<a href='$url&pure=1'>"
			. html_print_image ("images/fullscreen.png", true, array ("title" => __('Full screen mode')))
			. "</a>";
	} else {
		$options['screen'] = "<a href='$url&pure=0'>"
			. html_print_image ("images/normalscreen.png", true, array ("title" => __('Back to normal mode')))
			. "</a>";
	}

	// Header
	ui_print_page_header (__('Reporting'). " &raquo;  ". __('Combined image render'), "images/reporting.png", false, "", false, $options);

	echo "<table class='databox_frame' cellpadding='0' cellspacing='0' width='98%'>";
	echo "<tr><td>";
	custom_graphs_print ($id_graph, $height, $width, $period, $stacked, false, $unixdate);
	echo "</td></tr></table>";
	$period_label = human_time_description_raw ($period);
	echo "<form method='POST' action='index.php?sec=reporting&sec2=operation/reporting/graph_viewer&view_graph=1&id=$id_graph'>";
	echo "<table class='databox_frame' cellpadding='4' cellspacing='4' style='width: 98%'>";
	echo "<tr>";
	echo "<td>";
	echo "<b>".__('Date')."</b>"." ";
	echo "</td>";
	echo "<td>";
	echo html_print_input_text ('date', $date, '', 12, 10, true). ' ';
	echo "</td>";
	echo "<td>";
	echo html_print_input_text ('time', $time, '', 7, 7, true). ' ';
	echo "</td>";
	echo "<td class='datos'>";
	echo "<b>".__('Period')."</b>";
	echo "</td>";
	echo "<td class='datos'>";
		
	echo html_print_extended_select_for_time ('period', $period, '', '', '0', 10, true);

	echo "</td>";
	echo "<td class='datos'>";
	$stackeds = array ();
	$stackeds[0] = __('Graph defined');
	$stackeds[0] = __('Area');
	$stackeds[1] = __('Stacked area');
	$stackeds[2] = __('Line');
	$stackeds[3] = __('Stacked line');
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
	echo "<input type=submit value='".__('Update')."' class='sub upd'>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "</form>";	
	/* We must add javascript here. Otherwise, the date picker won't 
   work if the date is not correct because php is returning. */

	ui_require_css_file ('datepicker');
	ui_require_jquery_file ('ui.core');
	ui_require_jquery_file ('ui.datepicker');
	ui_require_jquery_file ('timeentry');
	?>
	<script language="javascript" type="text/javascript">

	$(document).ready (function () {
		$("#loading").slideUp ();
		$("#text-time").timeEntry ({spinnerImage: 'images/time-entry.png', spinnerSize: [20, 20, 0]});
		$("#text-date").datepicker ();
		$.datepicker.regional["<?php echo $config['language']; ?>"];
	});
	</script>

	<?php
	$datetime = strtotime ($date.' '.$time);
	$report["datetime"] = $datetime;

	if ($datetime === false || $datetime == -1) {
		echo '<h3 class="error">'.__('Invalid date selected').'</h3>';
		return;
	}
	return;
}

// Header
ui_print_page_header (__('Reporting'). " &raquo;  ".__('Custom graph viewer'), "images/reporting.png", false, "", false, "" );


$graphs = custom_graphs_get_user ();
if (! empty ($graphs)) {
	$table->width = '98%';
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
} else {
	echo "<div class='nf'>".__('There are no defined reportings')."</div>";
}

?>
