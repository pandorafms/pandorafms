<?php 

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
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
if ($config['flash_charts']) {
	require_once ('include/fgraph.php');
}

/**
 * Get all the custom graphs a user can see.
 *
 * @param $id_user User id to check.
 * @param $only_names Wheter to return only graphs names in an associative array
 * or all the values.
 *
 * @return Custom graphs of a an user. Empty array if none.
 */
function get_user_custom_graphs ($id_user = 0, $only_names = false) {
	global $config;
	
	if (!$id_user) {
		$id_user = $config['id_user'];
	}
	
	$all_graphs = get_db_all_rows_in_table ('tgraph', 'name');
	if ($all_graphs === false)
		return array ();
	
	$graphs = array ();
	foreach ($all_graphs as $graph) {
		if ($graph["id_user"] != $id_user && $graph['private'])
			continue;
		
		if ($only_names) {
			$graphs[$graph['id_graph']] = $graph['name'];
		} else {
			$graphs[$graph['id_graph']] = $graph;
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
 * @param $stacked Wheter the graph is stacked or not.
 * @param $return Whether to return an output string or echo now (optional, echo by default).
 */
function print_custom_graph ($id_graph, $height, $width, $period, $stacked, $return = false) {
	global $config;
	
	$sources = get_db_all_rows_field_filter ('tgraph_source', 'id_graph', $id_graph);
	$modules = array ();
	$weights = array ();
	foreach ($sources as $source) {
		$sql = sprintf ("SELECT id_grupo
			FROM tagente, tagente_modulo
			WHERE tagente_modulo.id_agente_modulo = %d
			AND tagente.id_agente = tagente_modulo.id_agente",
			$source['id_agent_module']);
		$id_group = get_db_sql ($sql);
		if (! give_acl ($config["id_user"], $id_group, 'AR'))
			continue;
		array_push ($modules, $source['id_agent_module']);
		array_push ($weights, $source['weight']);
	}	

	if ($config['flash_charts']) {
		$output = graphic_combined_module ($modules, $weights, $period, $width, $height,
				'', '', 0, 0, 0, $stacked);
	} else {
		$modules = implode (',', $modules);
		$weights = implode (',', $weights);
		$output = '<img src="include/fgraph.php?tipo=combined&height='.$height.'&width='.$width.'&id='.$modules.'&period='.$period.'&weight_l='.$weights.'&stacked='.$stacked.'">';
	}
	
	if ($return)
		return $output;
	echo $output;
}

/**
 * Get all the possible periods in a custom graph.
 *
 * @return The possible periods in a custom graph in an associative array.
 */
function get_custom_graph_periods () {
	$periods = array ();
	
	$periods[1] = __('1 hour');
	$periods[2] = '2 '.__('hours');
	$periods[3] = '3 '.__('hours');
	$periods[6] = '6 '.__('hours');
	$periods[12] = '12 '.__('hours');
	$periods[24] = __('1 day');
	$periods[48] = __('2 days');
	$periods[360] = __('1 week');
	$periods[720] = __('1 month');
	$periods[4320] = __('6 months');
	
	return $periods;
}

?>
