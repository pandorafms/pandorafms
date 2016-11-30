<?php 

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package Include
 * @subpackage Graphs
 */


/**
 * @global array Contents all var configs for the local instalation. 
 */ 
global $config;

require_once ($config['homedir'] . '/include/functions_graph.php');
require_once ($config['homedir'] . '/include/functions_users.php');

function custom_graphs_create($id_modules = array(), $name = "",
	$description = "", $stacked = CUSTOM_GRAPH_AREA, $width = 0,
	$height = 0, $events = 0 , $period = 0, $private = 0, $id_group = 0,
	$user = false) {
	
	global $config;
	
	if ($user === false) {
		$user = $config['id_user'];
	}
	
	$id_graph = db_process_sql_insert('tgraph',
		array(
			'id_user' => $user,
			'name' => $name,
			'description' => $description,
			'period' => $period,
			'width' => $width,
			'height' => $height,
			'private' => $private,
			'events' => $events,
			'stacked' => $stacked,
			'id_group' => $id_group,
			'id_graph_template' => 0
			));
	
	if (empty($id_graph)) {
		return false;
	}
	else {
		$result = true;
		foreach ($id_modules as $id_module) {
			$result = db_process_sql_insert('tgraph_source',
				array(
					'id_graph' => $id_graph,
					'id_agent_module' => $id_module,
					'weight' => 1
					));
			
			if (empty($result))
				break;
		}
		
		if (empty($result)) {
			//Not it is a complete insert the modules. Delete all
			db_process_sql_delete('tgraph_source',
				array('id_graph' => $id_graph));
			
			db_process_sql_delete('tgraph',
				array('id_graph' => $id_graph));
			
			return false;
		}
		
		return $id_graph;
	}
}

/**
 * Get all the custom graphs a user can see.
 *
 * @param $id_user User id to check.
 * @param $only_names Wheter to return only graphs names in an associative array
 * or all the values.
 * @param $returnAllGroup Wheter to return graphs of group All or not.
 * @param $privileges Privileges to check in user group
 *
 * @return Custom graphs of a an user. Empty array if none.
 */
function custom_graphs_get_user ($id_user = 0, $only_names = false, $returnAllGroup = true, $privileges = 'RR') {
	global $config;
	
	if (!$id_user) {
		$id_user = $config['id_user'];
	}
	
	$groups = users_get_groups ($id_user, $privileges, $returnAllGroup);
	
	$all_graphs = db_get_all_rows_in_table ('tgraph', 'name');
	if ($all_graphs === false)
		return array ();
	
	$graphs = array ();
	foreach ($all_graphs as $graph) {
		if (!in_array($graph['id_group'], array_keys($groups)))
			continue;
		
		if ($graph["id_user"] != $id_user && $graph['private'])
			continue;
		
		if ($graph["id_group"] > 0)
			if (!isset($groups[$graph["id_group"]])) {
				continue;
			}
		
		if ($only_names) {
			$graphs[$graph['id_graph']] = $graph['name'];
		}
		else {
			$graphs[$graph['id_graph']] = $graph;
			$graphsCount = db_get_value_sql("SELECT COUNT(id_gs)
				FROM tgraph_source
				WHERE id_graph = " . $graph['id_graph']);
			$graphs[$graph['id_graph']]['graphs_count'] = $graphsCount;
		}
	}
	
	return $graphs;
}

/**
 * Print a custom graph image.
 *
 * @param $id_graph Graph id to print.
 * @param $height Height of the returning image.
 * @param $width Width of the returning image.
 * @param $period Period of time to get data in seconds.
 * @param $stacked Whether the graph is stacked or not.
 * @param $return Whether to return an output string or echo now (optional, echo by default).
 * @param $date Date to start printing the graph
 * @param bool Wether to show an image instead a interactive chart or not
 * @param string Background color
 * @param array List of names for the items. Should have the same size as the module list.
 * @param bool Show the last value of the item on the list.
 * @param bool Show the max value of the item on the list.
 * @param bool Show the min value of the item on the list.
 * @param bool Show the average value of the item on the list.
 *
 * @return Mixed 
 */

function custom_graphs_print($id_graph, $height, $width, $period,
	$stacked = null, $return = false, $date = 0, $only_image = false,
	$background_color = 'white', $modules_param = array(), $homeurl = '',
	$name_list = array(), $unit_list = array(), $show_last = true,
	$show_max = true, $show_min = true, $show_avg = true, $ttl = 1,
	$dashboard = false, $vconsole = false, $percentil = null) {
	
	global $config;
	
	if ($from_interface) {
		if ($config["type_interface_charts"] == 'line') {
			$graph_conf['stacked'] = CUSTOM_GRAPH_LINE;
		}
		else {
			$graph_conf['stacked'] = CUSTOM_GRAPH_AREA;
		}
	}
	else {
		if ($id_graph == 0) {
			$graph_conf['stacked'] = CUSTOM_GRAPH_LINE;
		}
		else {
			$graph_conf = db_get_row('tgraph', 'id_graph', $id_graph);
		}
	}
	
	if ($stacked === null) {
		$stacked = $graph_conf['stacked'];
	}
	
	$sources = false;
	if ($id_graph == 0) {
		$modules = $modules_param;
		$count_modules = count($modules);
		$weights = array_fill(0, $count_modules, 1);
		
		if ($count_modules > 0)
			$sources = true;
	}
	else {
		$sources = db_get_all_rows_field_filter('tgraph_source', 'id_graph',
			$id_graph);
		
		$modules = array ();
		$weights = array ();
		$labels = array ();
		foreach ($sources as $source) {
			array_push ($modules, $source['id_agent_module']);
			array_push ($weights, $source['weight']);
			if ($source['label'] != '')
				$labels[$source['id_agent_module']] = $source['label'];
		}
	}
		
	
	if ($sources === false) {
		if ($return){
			return false;
		}
		else{	
			ui_print_info_message ( array ( 'no_close' => true, 'message' =>  __('No items.') ) );
			return;
		}
	}
	
	if (empty($homeurl)) {
		$homeurl = ui_get_full_url(false, false, false, false);
	}
	
	$output = graphic_combined_module($modules,
		$weights,
		$period,
		$width,
		$height,
		'',
		'',
		0,
		0,
		0,
		$stacked,
		$date,
		$only_image,
		$homeurl,
		$ttl,
		false,
		false,
		$background_color,
		$name_list,
		$unit_list,
		$show_last,
		$show_max,
		$show_min,
		$show_avg,
		$labels,
		$dashboard,
		$vconsole,
		$percentil);
	
	if ($return)
		return $output;
	echo $output;
}

?>
