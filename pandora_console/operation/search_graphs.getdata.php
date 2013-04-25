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
	
	if (!$usergraphs_id) {
		$graphs_condition = " AND 1<>1";
	}
	else {
		$graphs_condition = " AND id_graph IN (".implode(',',$usergraphs_id).")";
	}
	
	$fromwhere = "FROM tgraph
		WHERE (name LIKE '%" . $stringSearchSQL . "%' OR description LIKE '%" . $stringSearchSQL . "%')".$graphs_condition;
	$limitoffset = "LIMIT " . $config['block_size'] . " OFFSET " . get_parameter ('offset',0);
	
	$sql_count = "SELECT COUNT(id_graph) AS count $fromwhere";
	
	if ($only_count) {
		$totalGraphs = db_get_value_sql($sql_count);
	}
	else {
		$sql = "SELECT id_graph, name, description $fromwhere $limitoffset";
		
		$graphs = db_process_sql($sql);
		
		if($graphs !== false) {
			$totalGraphs = db_get_value_sql($sql_count);
		}
	}
}
?>
