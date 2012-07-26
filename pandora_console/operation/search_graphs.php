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

include_once('include/functions_custom_graphs.php');

$searchGraphs = check_acl($config["id_user"], 0, "IR");

$graphs = false;

if ($searchGraphs) {
	//Check ACL
	$usergraphs = custom_graphs_get_user($config['id_user'], true);
	
	$usergraphs_id = array_keys($usergraphs);
	
	if(!$usergraphs_id){
		$graphs_condition = " AND 1<>1";
	}
	else {
		$graphs_condition = " AND id_graph IN (".implode(',',$usergraphs_id).")";
	}
	
	$sql = "SELECT id_graph, name, description
		FROM tgraph
		WHERE (name LIKE '%" . $stringSearchSQL . "%' OR description LIKE '%" . $stringSearchSQL . "%')".$graphs_condition ."
		LIMIT " . $config['block_size'] . " OFFSET " . get_parameter ('offset',0);
	$graphs = db_process_sql($sql);
	
	if($graphs !== false) {
		$sql = "SELECT COUNT(id_graph) AS count
			FROM tgraph
			WHERE name LIKE '%" . $stringSearchSQL . "%' OR description LIKE '%" . $stringSearchSQL . "%'";
		$totalGraphs = db_get_row_sql($sql);
		$totalGraphs = $totalGraphs['count'];
	}
}

if ($graphs === false) {
	echo "<br><div class='nf'>" . __("Zero results found") . "</div>\n";
}
else {
	$table->cellpadding = 4;
	$table->cellspacing = 4;
	$table->width = "98%";
	$table->class = "databox";
	
	$table->head = array ();
	$table->head[0] = __('Graph name');
	$table->head[1] = __('Description');
	
	$table->data = array ();
	foreach ($graphs as $graph) {
		array_push($table->data, array(
			"<a href='?sec=reporting&sec2=operation/reporting/graph_viewer&view_graph=1&id=" .
				$graph['id_graph'] . "'>" . $graph['name'] . "</a>",
			$graph['description']
		));
	}
	
	echo "<br />";ui_pagination ($totalGraphs);
	html_print_table ($table); unset($table);
	ui_pagination ($totalGraphs);
}

switch ($searchTab) {
	case 'agents':
		require_once('search_agents.php');
		break;
	case 'users':
		require_once('search_users.php');
		break;
	case 'alerts':
		require_once('search_alerts.php');
		break;
}
?>
