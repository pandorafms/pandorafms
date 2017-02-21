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

require_once('include/functions_custom_graphs.php');
require_once('include/functions_graph.php');

$save_custom_graph = (bool) get_parameter('save_custom_graph');
$print_custom_graph = (bool) get_parameter('print_custom_graph');
$print_sparse_graph = (bool) get_parameter('print_sparse_graph');

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

if ($print_custom_graph) {
	ob_clean();
	
	$id_graph = (int) get_parameter('id_graph');
	$height = (int) get_parameter('height', CHART_DEFAULT_HEIGHT);
	$width = (int) get_parameter('width', CHART_DEFAULT_WIDTH);
	$period = (int) get_parameter('period', SECONDS_5MINUTES);
	$stacked = (int) get_parameter('stacked', CUSTOM_GRAPH_LINE);
	$date = (int) get_parameter('date', time());
	$only_image = (bool) get_parameter('only_image');
	$background_color = (string) get_parameter('background_color', 'white');
	$modules_param = get_parameter('modules_param', array());
	$homeurl = (string) get_parameter('homeurl');
	$name_list = get_parameter('name_list', array());
	$unit_list = get_parameter('unit_list', array());
	$show_last = (bool) get_parameter('show_last', true);
	$show_max = (bool) get_parameter('show_max', true);
	$show_min = (bool) get_parameter('show_min', true);
	$show_avg = (bool) get_parameter('show_avg', true);
	$ttl = (int) get_parameter('ttl', 1);
	$dashboard = (bool) get_parameter('dashboard');
	$vconsole = (bool) get_parameter('vconsole');
	
	echo custom_graphs_print($id_graph, $height, $width, $period, $stacked,
		true, $date, $only_image, $background_color, $modules_param,
		$homeurl, $name_list, $unit_list, $show_last, $show_max,
		$show_min, $show_avg, $ttl, $dashboard, $vconsole);
	return;
}

if ($print_sparse_graph) {
	ob_clean();
	
	$agent_module_id = (int) get_parameter('agent_module_id');
	$period = (int) get_parameter('period', SECONDS_5MINUTES);
	$show_events = (bool) get_parameter('show_events');
	$width = (int) get_parameter('width', CHART_DEFAULT_WIDTH);
	$height = (int) get_parameter('height', CHART_DEFAULT_HEIGHT);
	$title = (string) get_parameter('title');
	$unit_name = (string) get_parameter('unit_name');
	$show_alerts = (bool) get_parameter('show_alerts');
	$avg_only = (int) get_parameter('avg_only');
	$pure = (bool) get_parameter('pure');
	$date = (int) get_parameter('date', time());
	$unit = (string) get_parameter('unit');
	$baseline = (int) get_parameter('baseline');
	$return_data = (int) get_parameter('return_data');
	$show_title = (bool) get_parameter('show_title', true);
	$only_image = (bool) get_parameter('only_image');
	$homeurl = (string) get_parameter('homeurl');
	$ttl = (int) get_parameter('ttl', 1);
	$projection = (bool) get_parameter('projection');
	$adapt_key = (string) get_parameter('adapt_key');
	$compare = (bool) get_parameter('compare');
	$show_unknown = (bool) get_parameter('show_unknown');
	$menu = (bool) get_parameter('menu', true);
	$background_color = (string) get_parameter('background_color', 'white');
	$percentil = get_parameter('percentil', null);
	$dashboard = (bool) get_parameter('dashboard');
	$vconsole = (bool) get_parameter('vconsole');
	
	echo grafico_modulo_sparse($agent_module_id, $period, $show_events,
		$width, $height , $title, $unit_name, $show_alerts, $avg_only,
		$pure, $date, $unit, $baseline, $return_data, $show_title,
		$only_image, $homeurl, $ttl, $projection, $adapt_key, $compare,
		$show_unknown, $menu, $backgroundColor, $percentil,
		$dashboard, $vconsole, $config['type_module_charts']);
	return;
}

?>
