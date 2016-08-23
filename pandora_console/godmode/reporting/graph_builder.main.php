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

require_once('include/functions_custom_graphs.php');

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

if (! check_acl ($config['id_user'], 0, "RW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access graph builder");
	include ("general/noaccess.php");
	exit;
}

if ($edit_graph) {
	$graphInTgraph = db_get_row_sql("SELECT * FROM tgraph WHERE id_graph = " . $id_graph);
	$stacked = $graphInTgraph['stacked'];
	$period = $graphInTgraph['period'];
	$name = $graphInTgraph['name'];
	$description = $graphInTgraph['description'];
	$id_group = $graphInTgraph['id_group'];
	$width = $graphInTgraph['width'];
	$height = $graphInTgraph['height'];
	$check = false;

	if ($stacked == CUSTOM_GRAPH_BULLET_CHART_THRESHOLD){
		$stacked = CUSTOM_GRAPH_BULLET_CHART;
		$check = true;
	}
}
else {
	$id_agent = 0;
	$id_module = 0;
	$id_group = 0;
	$name = "Pandora FMS combined graph";
	$width = 550;
	$height = 210;
	$period = SECONDS_1DAY;
	$factor = 1;
	$stacked = 4;
	$check = false;
}



// -----------------------
// CREATE/EDIT GRAPH FORM
// -----------------------

echo "<table width='100%' cellpadding=4 cellspacing=4 class='databox filters'>";

if ($edit_graph)
	echo "<form method='post' action='index.php?sec=reporting&sec2=godmode/reporting/graph_builder&edit_graph=1&update_graph=1&id=" . $id_graph . "'>";
else
	echo "<form method='post' action='index.php?sec=reporting&sec2=godmode/reporting/graph_builder&edit_graph=1&add_graph=1'>";

echo "<tr>";
echo "<td class='datos'><b>".__('Name')."</b></td>";
echo "<td class='datos'><input type='text' name='name' size='25' ";
if ($edit_graph) {
	echo "value='" . $graphInTgraph['name'] . "' ";
}
echo ">";

$own_info = get_user_info ($config['id_user']);
if ($own_info['is_admin'] || check_acl ($config['id_user'], 0, "PM"))
	$return_all_groups = true;
else	
	$return_all_groups = false;
	
echo "<td><b>".__('Group')."</b></td><td>" .
	html_print_select_groups($config['id_user'], "AR", $return_all_groups, 'graph_id_group', $id_group, '', '', '', true) .
	"</td></tr>";
echo "<tr>";
echo "<td class='datos2'><b>".__('Description')."</b></td>";
echo "<td class='datos2' colspan=3><textarea name='description' style='height:45px;' cols=55 rows=2>";
if ($edit_graph) {
	echo $graphInTgraph['description'];
}

echo "</textarea>";
echo "</td></tr>";
if ($stacked == CUSTOM_GRAPH_GAUGE)
	$hidden = ' style="display:none;" ';
else
	$hidden = '';
echo "<tr>";
echo "<td class='datos stacked' $hidden>";
echo "<b>".__('Width')."</b></td>";
echo "<td class='datos'>";
echo "<input type='text' name='width' value='$width' $hidden size=6></td>";
echo "<td class='datos2'>";
echo "<b>".__('Height')."</b></td>";
echo "<td class='datos2'>";
echo "<input type='text' name='height' value='$height' size=6></td></tr>";

echo "<tr>";
echo "<td class='datos'>";
echo "<b>".__('Period')."</b></td>";
echo "<td class='datos'>";
html_print_extended_select_for_time ('period', $period, '', '', '0', 10);
echo "</td><td class='datos2'>";
echo "<b>".__('Type of graph')."</b></td>";
echo "<td class='datos2'> <div style='float:left;display:inline-block'>";

include_once($config["homedir"] . "/include/functions_graph.php");

$stackeds = array(
	CUSTOM_GRAPH_AREA => __('Area'),
	CUSTOM_GRAPH_STACKED_AREA => __('Stacked area'),
	CUSTOM_GRAPH_LINE => __('Line'),
	CUSTOM_GRAPH_STACKED_LINE => __('Stacked line'),
	CUSTOM_GRAPH_BULLET_CHART => __('Bullet chart'),
	CUSTOM_GRAPH_GAUGE => __('Gauge'),
	CUSTOM_GRAPH_HBARS => __('Horizontal bars'),
	CUSTOM_GRAPH_VBARS => __('Vertical bars'),
	CUSTOM_GRAPH_PIE => __('Pie')
	);
html_print_select ($stackeds, 'stacked', $stacked);

echo "<div style='float:right' id='thresholdDiv' name='thresholdDiv'>&nbsp;&nbsp;<b>".__('Equalize maximum thresholds')."</b>" .
	ui_print_help_tip (__("If an option is selected, all graphs will have the highest value from all modules included in the graph as a maximum threshold"), true);

html_print_checkbox('threshold', CUSTOM_GRAPH_BULLET_CHART_THRESHOLD, $check, false, false, '', false);
echo "</div>";



echo "</div></td>";

echo "</table>";

if ($edit_graph) {
	echo "<div style='width:100%'><input style='float:right;' type=submit name='store' class='sub upd' value='".__('Update')."'></div>";
}
else {
	echo "<div style='width:100%'><input style='float:right;' type=submit name='store' class='sub next' value='".__('Create')."'></div>";
}
echo "</form>";


echo '<script type="text/javascript">
	$(document).ready(function() {
		if ($("#stacked").val() == '. CUSTOM_GRAPH_BULLET_CHART .') {
			$("#thresholdDiv").show();
		}else{
			$("#thresholdDiv").hide();
		}
	});

	$("#stacked").change(function(){
		if ( $(this).val() == '. CUSTOM_GRAPH_GAUGE .') {
			$("[name=threshold]").prop("checked", false);
			$(".stacked").hide();
			$("input[name=\'width\']").hide();
			$("#thresholdDiv").hide();
		} else if ($(this).val() == '. CUSTOM_GRAPH_BULLET_CHART .') {
			$("#thresholdDiv").show();
			$(".stacked").show();
			$("input[name=\'width\']").show();
		} else {
			$("[name=threshold]").prop("checked", false);
			$(".stacked").show();
			$("input[name=\'width\']").show();
			$("#thresholdDiv").hide();
		}
	});

</script>';
?>
