<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2012 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

require_once ("include/functions_custom_graphs.php");

$save_custom_graph = (bool)get_parameter('save_custom_graph', 0);

if ($save_custom_graph) {
	$return = array();
	
	$id_modules = (array)get_parameter('id_modules', array());
	$name = get_parameter('name', '');
	$description = get_parameter('description', '');
	$stacked = get_parameter('stacked', CUSTOM_GRAPH_LINE);
	$width = get_parameter('width', 0);
	$height = get_parameter('height', 0);
	$events = get_parameter('events', 0);
	$period = get_parameter('period', 0);
	
	$result = (bool)custom_graphs_create($id_modules, $name,
		$description, $stacked, $width, $height, $events, $period);
	
	
	$return['correct'] = $result;
	
	echo json_encode($return);
	return;
}

?>
