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

global $config;

require_once ('include/functions_custom_graphs.php');

if (is_ajax ()) {
	$search_agents = (bool) get_parameter ('search_agents');
	
	if ($search_agents) {
		
		require_once ('include/functions_agents.php');
		
		$id_agent = (int) get_parameter ('id_agent');
		$string = (string) get_parameter ('q'); /* q is what autocomplete plugin gives */
		$id_group = (int) get_parameter('id_group');
		
		$filter = array ();
		$filter[] = '(nombre COLLATE utf8_general_ci LIKE "%'.$string.'%" OR direccion LIKE "%'.$string.'%" OR comentarios LIKE "%'.$string.'%")';
		$filter['id_grupo'] = $id_group; 
		
		$agents = agents_get_agents ($filter, array ('nombre', 'direccion'));
		if ($agents === false)
			return;
		
		foreach ($agents as $agent) {
			echo $agent['nombre']."|".$agent['direccion']."\n";
		}
		
		return;
 	}
 	
 	return;
}

check_login ();

if (! check_acl ($config['id_user'], 0, "IW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access graph builder");
	include ("general/noaccess.php");
	exit;
}

$id = (integer) get_parameter('id');

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

$graphRows = db_get_all_rows_sql("SELECT t1.*,
	(SELECT t3.nombre 
		FROM tagente AS t3 
		WHERE t3.id_agente = 
			(SELECT t2.id_agente 
				FROM tagente_modulo AS t2
				WHERE t2.id_agente_modulo = t1.id_agent_module)) 
	AS agent_name
	FROM tgraph_source AS t1
	WHERE t1.id_graph = " . $id);
$module_array = array();
$weight_array = array();
$agent_array = array();

if($graphRows === false) {
		$graphRows = array();
}
	
foreach ($graphRows as $graphRow) {
	$module_array[] = $graphRow['id_agent_module'];
	$weight_array[] = $graphRow['weight'];
	$agent_array[] = $graphRow['agent_name'];
}

$modules = implode(',', $module_array);
$weights = implode(',', $weight_array);

echo "<table class='databox_frame' cellpadding='0' cellspacing='0' style='width:98%'>";
echo "<tr><td>";

if(!empty($modules)) {
	require_once ($config["homedir"] . '/include/functions_graph.php');
	
	echo graphic_combined_module(explode (',', $modules), explode (',', $weights), $period, $width, $height,
				'Combined%20Sample%20Graph', '', $events, 0, 0, $stacked, $unixdate);
}
else {
	echo "<div class='nf' style='width: 98%'>".__('Empty graph')."</div>";
}

echo "</td></tr></table>";

echo "<form method = 'POST' action='index.php?sec=greporting&sec2=godmode/reporting/graph_builder&tab=preview&edit_graph=1&id=$id_graph'>";
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
?>
