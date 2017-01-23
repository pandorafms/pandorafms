<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

include_once($config['homedir'] . "/include/graphs/fgraph.php");
include_once($config['homedir'] . "/include/functions_reporting.php");
include_once($config['homedir'] . "/include/functions_agents.php");
include_once($config['homedir'] . "/include/functions_modules.php");
include_once($config['homedir'] . "/include/functions_users.php");

function get_graph_statistics ($chart_array) {
	
	/// IMPORTANT!
	///
	/// The calculus for AVG, MIN and MAX values are in this function
	/// because it must be done based on graph array data not using reporting 
	/// function to get coherent data between stats and graph visualization
	
	$stats = array ();
	
	$count = 0;
	
	$size = sizeof($chart_array);
	
	//Initialize stats array
	$stats = array ("avg" => 0, "min" => null, "max" => null, "last" => 0);
	
	foreach ($chart_array as $item) {
		
		//Sum all values later divide by the number of elements
		$stats['avg'] = $stats['avg'] + $item;
		
		//Get minimum
		if ($stats['min'] == null) {
			$stats['min'] = $item;
		}
		else if ($item < $stats['min']) {
			$stats['min'] = $item;
		}
		
		//Get maximum
		if ($stats['max'] == null) {
			$stats['max'] = $item;
		}
		else if ($item > $stats['max']) {
			$stats['max'] = $item;
		}
		
		$count++;
		
		//Get last data
		if ($count == $size) {
			$stats['last'] = $item;
		}
	}
	
	//End the calculus for average
	if ($count > 0) {
		
		$stats['avg'] = $stats['avg'] / $count;
	}
	
	//Format stat data to display properly
	$stats['last'] = round($stats['last'], 2);
	$stats['avg'] = round($stats['avg'], 2);
	$stats['min'] = round($stats['min'], 2);
	$stats['max'] = round($stats['max'], 2);
	
	return $stats;
}

function get_statwin_graph_statistics ($chart_array, $series_suffix = '') {
	
	/// IMPORTANT!
	///
	/// The calculus for AVG, MIN and MAX values are in this function
	/// because it must be done based on graph array data not using reporting 
	/// function to get coherent data between stats and graph visualization
	
	$stats = array ();
	
	$count = 0;
	
	$size = sizeof($chart_array);
	
	//Initialize stats array
	$stats['sum'] = array ("avg" => 0, "min" => null, "max" => null, "last" => 0);
	$stats['min'] = array ("avg" => 0, "min" => null, "max" => null, "last" => 0);
	$stats['max'] = array ("avg" => 0, "min" => null, "max" => null, "last" => 0);
	
	foreach ($chart_array as $item) {
		if ($series_suffix != '') {
			if (isset($item['sum' . $series_suffix]))
				$item['sum'] = $item['sum' . $series_suffix];
			if (isset($item['min' . $series_suffix]))
				$item['min'] = $item['min' . $series_suffix];
			if (isset($item['max' . $series_suffix]))
				$item['max'] = $item['max' . $series_suffix];
		}
		
		//Get stats for normal graph
		if (isset($item['sum']) && $item['sum']) {
			
			//Sum all values later divide by the number of elements
			$stats['sum']['avg'] = $stats['sum']['avg'] + $item['sum'];
			
			//Get minimum
			if ($stats['sum']['min'] == null) {
				$stats['sum']['min'] = $item['sum'];
			}
			else if ($item['sum'] < $stats['sum']['min']) {
				$stats['sum']['min'] = $item['sum'];
			}
			
			//Get maximum
			if ($stats['sum']['max'] == null) {
				$stats['sum']['max'] = $item['sum'];
			}
			else if ($item['sum'] > $stats['sum']['max']) {
				$stats['sum']['max'] = $item['sum'];
			}
			
		}
		
		//Get stats for min graph
		if (isset($item['min']) && $item['min']) {
			//Sum all values later divide by the number of elements
			$stats['min']['avg'] = $stats['min']['avg'] + $item['min'];
			
			//Get minimum
			if ($stats['min']['min'] == null) {
				$stats['min']['min'] = $item['min'];
			}
			else if ($item['min'] < $stats['min']['min']) {
				$stats['min']['min'] = $item['min'];
			}
			
			//Get maximum
			if ($stats['min']['max'] == null) {
				$stats['min']['max'] = $item['min'];
			}
			else if ($item['min'] > $stats['min']['max']) {
				$stats['min']['max'] = $item['min'];
			}
		
		}
		
		//Get stats for max graph
		if (isset($item['max']) && $item['max']) {
			//Sum all values later divide by the number of elements
			$stats['max']['avg'] = $stats['max']['avg'] + $item['max'];
			
			//Get minimum
			if ($stats['max']['min'] == null) {
				$stats['max']['min'] = $item['max'];
			}
			else if ($item['max'] < $stats['max']['min']) {
				$stats['max']['min'] = $item['max'];
			}
			
			//Get maximum
			if ($stats['max']['max'] == null) {
				$stats['max']['max'] = $item['max'];
			}
			else if ($item['max'] > $stats['max']['max']) {
				$stats['max']['max'] = $item['max'];
			}
		}
		
		
		//Count elements
		$count++;
		
		//Get last data
		if ($count == $size) {
			if (isset($item['sum']) && $item['sum']) {
				$stats['sum']['last'] = $item['sum'];
			}
			
			if (isset($item['min']) && $item['min']) {
				$stats['min']['last'] = $item['min'];
			}
			
			if (isset($item['max']) && $item['max']) {
				$stats['max']['last'] = $item['max'];
			}
		}
	}
	
	//End the calculus for average
	if ($count > 0) {
		
		$stats['sum']['avg'] = $stats['sum']['avg'] / $count;
		$stats['min']['avg'] = $stats['min']['avg'] / $count;
		$stats['max']['avg'] = $stats['max']['avg'] / $count;
	}
	
	//Format stat data to display properly
	$stats['sum']['last'] = round($stats['sum']['last'], 2);
	$stats['sum']['avg'] = round($stats['sum']['avg'], 2);
	$stats['sum']['min'] = round($stats['sum']['min'], 2);
	$stats['sum']['max'] = round($stats['sum']['max'], 2);
	
	$stats['min']['last'] = round($stats['min']['last'], 2);
	$stats['min']['avg'] = round($stats['min']['avg'], 2);
	$stats['min']['min'] = round($stats['min']['min'], 2);
	$stats['min']['max'] = round($stats['min']['max'], 2);
	
	$stats['max']['last'] = round($stats['max']['last'], 2);
	$stats['max']['avg'] = round($stats['max']['avg'], 2);
	$stats['max']['min'] = round($stats['max']['min'], 2);
	$stats['max']['max'] = round($stats['max']['max'], 2);
	
	return $stats;
}

function grafico_modulo_sparse_data_chart (&$chart, &$chart_data_extra, &$long_index, 
				$data, $data_i, $previous_data, $resolution, $interval, $period, $datelimit, 
				$projection, $avg_only = false, $uncompressed_module = false, 
				$show_events = false, $show_alerts = false, $show_unknown = false, $baseline = false, 
				$baseline_data = array(), $events = array(), $series_suffix = '', $start_unknown = false,
				$percentil = null) {
	
	global $config;
	global $chart_extra_data;
	global $series_type;
	global $max_value;
	global $min_value;
	
	$max_value = 0;
	$min_value = null;
	$flash_chart = $config['flash_charts'];
	
	// Event iterator
	$event_i = 0;
	
	// Is unknown flag
	$is_unknown = $start_unknown;
	
	// Calculate chart data
	$last_known = $previous_data;
	
	
	for ($i = 0; $i <= $resolution; $i++) {
		$timestamp = $datelimit + ($interval * $i);
		
		
		$total = 0;
		$count = 0;
		
		// Read data that falls in the current interval
		$interval_min = false;
		$interval_max = false;
		
		while (isset ($data[$data_i]) && $data[$data_i]['utimestamp'] >= $timestamp && $data[$data_i]['utimestamp'] < ($timestamp + $interval)) {
			if ($interval_min === false) {
				$interval_min = $data[$data_i]['datos'];
			}
			if ($interval_max === false) {
				$interval_max = $data[$data_i]['datos'];
			}
			
			if ($data[$data_i]['datos'] > $interval_max) {
				$interval_max = $data[$data_i]['datos'];
			}
			else if ($data[$data_i]['datos'] < $interval_min) {
				$interval_min = $data[$data_i]['datos'];
			}
			$total += $data[$data_i]['datos'];
			$last_known = $data[$data_i]['datos'];
			$count++;
			$data_i++;
		}
		
		if ($max_value < $interval_max) {
			$max_value = $interval_max;
		}
		
		if ($min_value > $interval_max || $min_value == null) {
			$min_value = $interval_max;
		}
		
		// Data in the interval
		if ($count > 0) {
			$total /= $count;
			// If detect data, unknown period finishes
			$is_unknown = false;
		}
		
		// Read events and alerts that fall in the current interval
		$event_value = 0;
		$alert_value = 0;
		$unknown_value = 0;
		// Is the first point of a unknown interval
		$first_unknown = false;
		
		$event_ids = array();
		$alert_ids = array();
		while (isset ($events[$event_i]) && $events[$event_i]['utimestamp'] >= $timestamp && $events[$event_i]['utimestamp'] <= ($timestamp + $interval)) {
			if ($show_events == 1) {
				$event_value++;
				$event_ids[] = $events[$event_i]['id_evento'];
			}
			if ($show_alerts == 1 && substr ($events[$event_i]['event_type'], 0, 5) == 'alert') {
				$alert_value++;
				$alert_ids[] = $events[$event_i]['id_evento'];
			}
			if ($show_unknown) {
				if ($events[$event_i]['event_type'] == 'going_unknown') {
					if ($is_unknown == false) {
						$first_unknown = true;
					}
					$is_unknown = true;
				}
				else if (substr ($events[$event_i]['event_type'], 0, 5) == 'going') {
					$is_unknown = false;
				}
			}
			$event_i++;
		}
		
		// In some cases, can be marked as known because a recovery event
		// was found in same interval. For this cases first_unknown is 
		// checked too
		if ($is_unknown || $first_unknown) {
			$unknown_value++;
		}
		
		if (!$flash_chart) {
			// Set the title and time format
			if ($period <= SECONDS_6HOURS) {
				$time_format = 'H:i:s';
			}
			elseif ($period < SECONDS_1DAY) {
				$time_format = 'H:i';
			}
			elseif ($period < SECONDS_15DAYS) {
				$time_format = "M \nd H:i";
			}
			elseif ($period < SECONDS_1MONTH) {
				$time_format = "M \nd H\h";
			} 
			else {
				$time_format = "M \nd H\h";
			}
		}
		else {
			// Set the title and time format
			if ($period <= SECONDS_6HOURS) {
				$time_format = 'H:i:s';
			}
			elseif ($period < SECONDS_1DAY) {
				$time_format = 'H:i';
			}
			elseif ($period < SECONDS_15DAYS) {
				$time_format = "M d H:i";
			}
			elseif ($period < SECONDS_1MONTH) {
				$time_format = "M d H\h";
			} 
			else {
				$time_format = "M d H\h";
			}
		}
		
		$timestamp_short = date($time_format, $timestamp);
		$long_index[$timestamp_short] = date(
			html_entity_decode($config['date_format'], ENT_QUOTES, "UTF-8"), $timestamp);
		if (!$projection) {
			$timestamp = $timestamp_short;
		}
		
		// Data
		if ($show_events) {
			if (!isset($chart[$timestamp]['event'.$series_suffix])) {
				$chart[$timestamp]['event'.$series_suffix] = 0;
			}
			
			$chart[$timestamp]['event'.$series_suffix] += $event_value;
			$series_type['event'.$series_suffix] = 'points';
		}
		if ($show_alerts) {
			if (!isset($chart[$timestamp]['alert'.$series_suffix])) {
				$chart[$timestamp]['alert'.$series_suffix] = 0;
			}
			
			$chart[$timestamp]['alert'.$series_suffix] += $alert_value;
			$series_type['alert'.$series_suffix] = 'points';
		}
		
		if ($count > 0) {
			if ($avg_only) {
				$chart[$timestamp]['sum'.$series_suffix] = $total;
			}
			else {
				$chart[$timestamp]['max'.$series_suffix] = $interval_max;
				$chart[$timestamp]['sum'.$series_suffix] = $total;
				$chart[$timestamp]['min'.$series_suffix] = $interval_min;
			}
		// Compressed data
		}
		else {
			if ($uncompressed_module || ($timestamp > time ())) {
				if ($avg_only) {
					$chart[$timestamp]['sum'.$series_suffix] = 0;
				}
				else {
					$chart[$timestamp]['max'.$series_suffix] = 0;
					$chart[$timestamp]['sum'.$series_suffix] = 0;
					$chart[$timestamp]['min'.$series_suffix] = 0;
				}
			}
			else {
				if ($avg_only) {
					$chart[$timestamp]['sum'.$series_suffix] = $last_known;
				}
				else {
					$chart[$timestamp]['max'.$series_suffix] = $last_known;
					$chart[$timestamp]['sum'.$series_suffix] = $last_known;
					$chart[$timestamp]['min'.$series_suffix] = $last_known;
				}
			}
		}
		
		if ($show_unknown) {
			if (!isset($chart[$timestamp]['unknown'.$series_suffix])) {
				$chart[$timestamp]['unknown'.$series_suffix] = 0;
			}
			
			$chart[$timestamp]['unknown'.$series_suffix] = $unknown_value;
			$series_type['unknown'.$series_suffix] = 'area';
		}
		
		//$chart[$timestamp]['count'] = 0;
		/////////
		//$chart[$timestamp]['timestamp_bottom'] = $timestamp;
		//$chart[$timestamp]['timestamp_top'] = $timestamp + $interval;
		/////////

		//Baseline was replaced by compare graphs feature
		/*if ($baseline) {
			$chart[$timestamp]['baseline'.$series_suffix] = array_shift ($baseline_data);
			if ($chart[$timestamp]['baseline'.$series_suffix] == NULL) {
				$chart[$timestamp]['baseline'.$series_suffix] = 0;
			}
		}*/
		
		if (!empty($event_ids)) {
			$chart_extra_data[count($chart)-1]['events'] = implode(',',$event_ids);
		}
		if (!empty($alert_ids)) {
			$chart_extra_data[count($chart)-1]['alerts'] = implode(',',$alert_ids);
		}
	}
	
	if (!is_null($percentil)) {
		$avg = array_map(function($item) { return $item['sum'];}, $chart);
		
		$percentil_result = get_percentile($percentil, $avg);
		
		//Fill the data of chart
		array_walk($chart, function(&$item) use ($percentil_result, $series_suffix) {
			$item['percentil' . $series_suffix] = $percentil_result; });
		$series_type['percentil' . $series_suffix] = 'line';
	}
}


function grafico_modulo_sparse_data ($agent_module_id, $period, $show_events,
	$width, $height , $title = '', $unit_name = null,
	$show_alerts = false, $avg_only = 0, $date = 0, $unit = '',
	$baseline = 0, $return_data = 0, $show_title = true, $projection = false, 
	$adapt_key = '', $compare = false, $series_suffix = '', $series_suffix_str = '', 
	$show_unknown = false, $percentil = null, $dashboard = false, $vconsole = false) {
	
	global $config;
	global $chart;
	global $color;
	global $legend;
	global $long_index;
	global $series_type;
	global $chart_extra_data;
	global $warning_min;
	global $critical_min;
	global $graphic_type;
	global $max_value;
	global $min_value;
	
	$chart = array();
	$color = array();
	$legend = array();
	$long_index = array();
	$warning_min = 0;
	$critical_min = 0;
	$start_unknown = false;
	
	// Set variables
	if ($date == 0) $date = get_system_time();
	$datelimit = $date - $period;
	$search_in_history_db = db_search_in_history_db($datelimit);
	$resolution = $config['graph_res'] * 50; //Number of points of the graph
	$interval = (int) ($period / $resolution);
	$agent_name = modules_get_agentmodule_agent_name ($agent_module_id);
	$agent_id = agents_get_agent_id ($agent_name);
	$module_name = modules_get_agentmodule_name ($agent_module_id);
	$id_module_type = modules_get_agentmodule_type ($agent_module_id);
	$module_type = modules_get_moduletype_name ($id_module_type);
	$uncompressed_module = is_module_uncompressed ($module_type);
	if ($uncompressed_module) {
		$avg_only = 1;
	}
	
	$flash_chart = $config['flash_charts'];
	
	
	// Get event data (contains alert data too)
	$events = array();
	if ($show_unknown == 1 || $show_events == 1 || $show_alerts == 1) {
		$events = db_get_all_rows_filter ('tevento',
			array ('id_agentmodule' => $agent_module_id,
				"utimestamp > $datelimit",
				"utimestamp < $date",
				'order' => 'utimestamp ASC'),
			array ('id_evento', 'evento', 'utimestamp', 'event_type'));
		
		// Get the last event after inverval to know if graph start on unknown
		$prev_event = db_get_row_filter ('tevento',
			array ('id_agentmodule' => $agent_module_id,
				"utimestamp <= $datelimit",
				'order' => 'utimestamp DESC'));
		if (isset($prev_event['event_type']) && $prev_event['event_type'] == 'going_unknown') {
			$start_unknown = true;
		}
		
		if ($events === false) {
			$events = array ();
		}
	}
	
	// Get module data
	$data = db_get_all_rows_filter ('tagente_datos',
		array ('id_agente_modulo' => (int)$agent_module_id,
			"utimestamp > $datelimit",
			"utimestamp < $date",
			'order' => 'utimestamp ASC'),
		array ('datos', 'utimestamp'), 'AND', $search_in_history_db);
	
	
	// Get module warning_min and critical_min
	$warning_min = db_get_value('min_warning','tagente_modulo','id_agente_modulo',$agent_module_id);
	$critical_min = db_get_value('min_critical','tagente_modulo','id_agente_modulo',$agent_module_id);
	
	if ($data === false) {
		$data = array ();
	}
	
	
	if ($uncompressed_module) {
		// Uncompressed module data
		
		$min_necessary = 1;
	}
	else {
		// Compressed module data
		
		// Get previous data
		$previous_data = modules_get_previous_data ($agent_module_id, $datelimit);
		if ($previous_data !== false) {
			$previous_data['utimestamp'] = $datelimit;
			array_unshift ($data, $previous_data);
		}
		
		// Get next data
		$nextData = modules_get_next_data ($agent_module_id, $date);
		if ($nextData !== false) {
			array_push ($data, $nextData);
		}
		else if (count ($data) > 0) {
			// Propagate the last known data to the end of the interval
			$nextData = array_pop ($data);
			array_push ($data, $nextData);
			$nextData['utimestamp'] = $date;
			array_push ($data, $nextData);
		}
		
		$min_necessary = 2;
	}
	
	// Check available data
	if (count ($data) < $min_necessary) {
		if (!$graphic_type) {
			if (!$projection) {
				return fs_error_image ();
			}
			else {
				return fs_error_image ();
			}
		}
		graphic_error ();
	}
	
	// Data iterator
	$data_i = 0;
	
	// Set initial conditions
	if ($data[0]['utimestamp'] == $datelimit) {
		$previous_data = $data[0]['datos'];
		$data_i++;
	}
	else {
		$previous_data = 0;
	}
	
	// Get baseline data
	$baseline_data = array();
	if ($baseline) {
		$baseline_data = array ();
		if ($baseline == 1) {
			$baseline_data = enterprise_hook(
				'reporting_enterprise_get_baseline',
				array ($agent_module_id, $period, $width, $height , $title, $unit_name, $date));
			if ($baseline_data === ENTERPRISE_NOT_HOOK) {
				$baseline_data = array ();
			}
		}
	}
	
	if (empty($unit)) {
		$unit = modules_get_unit($agent_module_id);
	}
	
	// Calculate chart data
	grafico_modulo_sparse_data_chart ($chart, $chart_data_extra, $long_index, 
		$data, $data_i, $previous_data, $resolution, $interval, $period, $datelimit, 
		$projection, $avg_only, $uncompressed_module, 
		$show_events, $show_alerts, $show_unknown, $baseline, 
		$baseline_data, $events, $series_suffix, $start_unknown,
		$percentil);
	
	
	
	// Return chart data and don't draw
	if ($return_data == 1) {
		return $chart;
	}
	
	$graph_stats = get_statwin_graph_statistics($chart, $series_suffix);
	
	// Fix event and alert scale
	if ($max_value > 0) {
		$event_max = 2 + (float)$max_value * 1.05;
	}
	else {
		$event_max = abs(($max_value+$min_value)/2);
		if ($event_max < 5) {
			$event_max = 5;
		}
	}
	
	foreach ($chart as $timestamp => $chart_data) {
		if ($show_events && $chart_data['event' . $series_suffix] > 0) {
			$chart[$timestamp]['event' . $series_suffix] = $event_max * 1.2;
		}
		if ($show_alerts && $chart_data['alert' . $series_suffix] > 0) {
			$chart[$timestamp]['alert' . $series_suffix] = $event_max * 1.10;
		}
		if ($show_unknown && $chart_data['unknown' . $series_suffix] > 0) {
			$chart[$timestamp]['unknown' . $series_suffix] = $event_max * 1.05;
		}
	}
	
	// Only show caption if graph is not small
	if ($width > MIN_WIDTH_CAPTION && $height > MIN_HEIGHT)
		//Flash chart
		$caption =
			__('Max. Value') . $series_suffix_str . ': ' . $graph_stats['sum']['max'] . '    ' .
			__('Avg. Value') . $series_suffix_str . ': ' .  $graph_stats['sum']['avg'] . '    ' .
			__('Min. Value') . $series_suffix_str . ': ' . $graph_stats['sum']['min'] . '    ' .
			__('Units. Value') . $series_suffix_str . ': ' . $unit;
	else
		$caption = array();
	
	///////
	// Color commented not to restrict serie colors
	if ($show_events) {
		$color['event' . $series_suffix] =
			array('border' => '#ff0000', 'color' => '#ff0000',
				'alpha' => CHART_DEFAULT_ALPHA);
	}
	if ($show_alerts) {
		$color['alert' . $series_suffix] =
			array('border' => '#ff7f00', 'color' => '#ff7f00',
				'alpha' => CHART_DEFAULT_ALPHA);
	}
	if ($show_unknown) {
		$color['unknown' . $series_suffix] =
			array('border' => '#999999', 'color' => '#999999',
				'alpha' => CHART_DEFAULT_ALPHA);
	}
	$color['max'.$series_suffix] = array(
		'border' => '#000000', 'color' => $config['graph_color3'],
		'alpha' => CHART_DEFAULT_ALPHA);
	$color['sum'.$series_suffix] = array(
		'border' => '#000000', 'color' => $config['graph_color2'],
		'alpha' => CHART_DEFAULT_ALPHA);
	$color['min'.$series_suffix] = array(
		'border' => '#000000', 'color' => $config['graph_color1'],
		'alpha' => CHART_DEFAULT_ALPHA);
	//Baseline was replaced by compare graph feature
	//$color['baseline'.$series_suffix] = array('border' => null, 'color' => '#0097BD', 'alpha' => 10);
	$color['unit'.$series_suffix] = array('border' => null, 'color' => '#0097BC', 'alpha' => 10);		
	
	if ($show_events) {
		$legend['event'.$series_suffix_str] = __('Events').$series_suffix_str;
		$chart_extra_data['legend_events'] = $legend['event'].$series_suffix_str;
	}
	if ($show_alerts) {
		$legend['alert'.$series_suffix] = __('Alerts').$series_suffix_str;
		$chart_extra_data['legend_alerts'] = $legend['alert'.$series_suffix_str];
	}
	
	if ($dashboard || $vconsole) {
		$legend['sum'.$series_suffix] =
			__('Last') . ': ' . rtrim(number_format($graph_stats['sum']['last'], $config['graph_precision']), '.0') . ($unit ? ' ' . $unit : '') . ' ; '
			. __('Avg') . ': ' . rtrim(number_format($graph_stats['sum']['avg'], $config['graph_precision']), '.0') . ($unit ? ' ' . $unit : '');
	}
	else if (!$avg_only) {
		$legend['max'.$series_suffix] = __('Max').$series_suffix_str.': '.__('Last').': '.rtrim(number_format($graph_stats['max']['last'], $config['graph_precision']), '.0').' '.$unit.' ; '.__('Avg').': '.rtrim(number_format($graph_stats['max']['avg'], $config['graph_precision']), '.0').' '.$unit.' ; '.__('Max').': '.rtrim(number_format($graph_stats['max']['max'], $config['graph_precision']), '.0').' '.$unit.' ; '.__('Min').': '.rtrim(number_format($graph_stats['max']['min'], $config['graph_precision']), '.0').' '.$unit;
		$legend['sum'.$series_suffix] = __('Avg').$series_suffix_str.': '.__('Last').': '.rtrim(number_format($graph_stats['sum']['last'], $config['graph_precision']), '.0').' '.$unit.' ; '.__('Avg').': '.rtrim(number_format($graph_stats['sum']['avg'], $config['graph_precision']), '.0').' '.$unit.' ; '.__('Max').': '.rtrim(number_format($graph_stats['sum']['max'], $config['graph_precision']), '.0').' '.$unit.' ; '.__('Min').': '.rtrim(number_format($graph_stats['sum']['min'], $config['graph_precision']), '.0').' '.$unit;
		$legend['min'.$series_suffix] = __('Min').$series_suffix_str.': '.__('Last').': '.rtrim(number_format($graph_stats['min']['last'], $config['graph_precision']), '.0').' '.$unit.' ; '.__('Avg').': '.rtrim(number_format($graph_stats['min']['avg'], $config['graph_precision']), '.0').' '.$unit.' ; '.__('Max').': '.rtrim(number_format($graph_stats['min']['max'], $config['graph_precision']), '.0').' '.$unit.' ; '.__('Min').': '.rtrim(number_format($graph_stats['min']['min'], $config['graph_precision']), '.0').' '.$unit;
	}
	else {
		$legend['sum'.$series_suffix] = __('Avg').$series_suffix_str.': '.__('Last').': '.rtrim(number_format($graph_stats['sum']['last'], $config['graph_precision']), '.0').' '.$unit.' ; '.__('Avg').': '.rtrim(number_format($graph_stats['sum']['avg'], $config['graph_precision']), '.0').' '.$unit.' ; '.__('Max').': '.rtrim(number_format($graph_stats['sum']['max'], $config['graph_precision']), '.0').' '.$unit.' ; '.__('Min').': '.rtrim(number_format($graph_stats['sum']['min'], $config['graph_precision']), '.0').' '.$unit;
	}
	//Baseline was replaced by compare graph feature
	/*if ($baseline) {
		$legend['baseline'.$series_suffix] = __('Baseline');
	}*/
	
	if ($show_unknown) {
		$legend['unknown'.$series_suffix] = __('Unknown').$series_suffix_str;
		$chart_extra_data['legend_unknown'] = $legend['unknown'.$series_suffix_str];
	}
	
	if (!is_null($percentil)) {
		$first_data = reset($chart);
		$percentil_value = format_for_graph($first_data['percentil'], 2);
		
		$legend['percentil'.$series_suffix] = __('Percentile %dº', $percentil)  .$series_suffix_str . " (" . $percentil_value . " " . $unit . ") ";
		$chart_extra_data['legend_percentil'] = $legend['percentil'.$series_suffix_str];
	}
}

function grafico_modulo_sparse ($agent_module_id, $period, $show_events,
	$width, $height , $title = '', $unit_name = null,
	$show_alerts = false, $avg_only = 0, $pure = false, $date = 0,
	$unit = '', $baseline = 0, $return_data = 0, $show_title = true,
	$only_image = false, $homeurl = '', $ttl = 1, $projection = false,
	$adapt_key = '', $compare = false, $show_unknown = false,
	$menu = true, $backgroundColor = 'white', $percentil = null,
	$dashboard = false, $vconsole = false) {
	
	global $config;
	global $graphic_type;
	
	
	$flash_chart = $config['flash_charts'];
	
	enterprise_include_once("include/functions_reporting.php");
	
	global $chart;
	global $color;
	global $color_prev;
	global $legend;
	global $long_index;
	global $series_type;
	global $chart_extra_data;
	global $warning_min;
	global $critical_min;
	
	$series_suffix_str = '';
	if ($compare !== false) {
		$series_suffix = '2';
		$series_suffix_str = ' (' . __('Previous') . ')';
		// Build the data of the previous period
		
		grafico_modulo_sparse_data ($agent_module_id, $period,
			$show_events, $width, $height, $title, $unit_name,
			$show_alerts, $avg_only, $date-$period, $unit, $baseline,
			$return_data, $show_title, $projection, $adapt_key,
			$compare, $series_suffix, $series_suffix_str,
			$show_unknown, $percentil, $dashboard, $vconsole);
		
		switch ($compare) {
			case 'separated':
				// Store the chart calculated
				$chart_prev = $chart;
				$legend_prev = $legend;
				$long_index_prev = $long_index;
				$series_type_prev = $series_type;
				$color_prev = $color;
				break;
			case 'overlapped':
				// Store the chart calculated deleting index,
				// because will be over the current period
				$chart_prev = array_values($chart);
				$legend_prev = $legend;
				$series_type_prev = $series_type;
				$color_prev = $color;
				foreach($color_prev as $k => $col) {
					$color_prev[$k]['color'] = '#' .
						get_complementary_rgb($color_prev[$k]['color']);
				}
				break;
		}
	}
	
	// Build the data of the current period
	$data_returned = grafico_modulo_sparse_data ($agent_module_id,
		$period, $show_events,
		$width, $height , $title, $unit_name,
		$show_alerts, $avg_only,
		$date, $unit, $baseline, $return_data, $show_title,
		$projection, $adapt_key, $compare, '', '', $show_unknown,
		$percentil, $dashboard, $vconsole);
	
	
	if ($return_data) {
		return $data_returned;
	}
	
	if ($compare === 'overlapped') {
		$i = 0;
		foreach ($chart as $k=>$v) {
			if (!isset($chart_prev[$i])) {
				continue;
			}
			$chart[$k] = array_merge($v,$chart_prev[$i]);
			$i++;
		}
		
		$legend = array_merge($legend, $legend_prev);
		$color = array_merge($color, $color_prev);
	}
	
	if ($only_image) {
		$flash_chart = false;
	}
	if($config["fixed_graph"] == false){
		$water_mark = array('file' =>
			$config['homedir'] . "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
	}
	
	if ($config['type_module_charts'] === 'area') {
		if ($compare === 'separated') {
			return
				area_graph($flash_chart, $chart, $width, $height/2, $color,
					$legend, $long_index,
					ui_get_full_url("images/image_problem.opaque.png", false, false, false),
					$title, $unit, $homeurl, $water_mark, $config['fontpath'],
					$config['font_size'], $unit, $ttl, $series_type,
					$chart_extra_data, $warning_min, $critical_min,
					$adapt_key, false, $series_suffix_str, $menu,
					$backgroundColor).
				'<br>'.
				area_graph($flash_chart, $chart_prev, $width, $height/2,
					$color_prev, $legend_prev, $long_index_prev,
					ui_get_full_url("images/image_problem.opaque.png", false, false, false),
					$title, $unit, $homeurl, $water_mark, $config['fontpath'],
					$config['font_size'], $unit, $ttl, $series_type_prev,
					$chart_extra_data, $warning_min, $critical_min,
					$adapt_key, false, $series_suffix_str, $menu,
					$backgroundColor);
		}
		else {
			// Color commented not to restrict serie colors
			return
				area_graph($flash_chart, $chart, $width, $height, $color,
					$legend, $long_index,
					ui_get_full_url("images/image_problem.opaque.png", false, false, false),
					$title, $unit, $homeurl, $water_mark, $config['fontpath'],
					$config['font_size'], $unit, $ttl, $series_type,
					$chart_extra_data, $warning_min, $critical_min,
					$adapt_key, false, $series_suffix_str, $menu,
					$backgroundColor, $dashboard, $vconsole, $agent_module_id);
		}
	}
	elseif ($config['type_module_charts'] === 'line') {
		if ($compare === 'separated') {
			return
				line_graph($flash_chart, $chart, $width, $height/2, $color,
					$legend, $long_index,
					ui_get_full_url("images/image_problem.opaque.png", false, false, false),
					"", $unit, $water_mark, $config['fontpath'],
					$config['font_size'], $unit, $ttl, $homeurl, $backgroundColor).
				'<br>'.
				line_graph($flash_chart, $chart_prev, $width, $height/2, $color,
					$legend, $long_index,
					ui_get_full_url("images/image_problem.opaque.png", false, false, false),
					"", $unit, $water_mark, $config['fontpath'],
					$config['font_size'], $unit, $ttl, $homeurl, $backgroundColor);
		}
		else {
			// Color commented not to restrict serie colors
			return
				line_graph($flash_chart, $chart, $width, $height, $color,
					$legend, $long_index,
					ui_get_full_url("images/image_problem.opaque.png", false, false, false),
					"", $unit, $water_mark, $config['fontpath'],
					$config['font_size'], $unit, $ttl, $homeurl, $backgroundColor);
		}
	}
}

function graph_get_formatted_date($timestamp, $format1, $format2) {
	global $config;
	
	if ($config['flash_charts']) {
		$date = date("$format1 $format2", $timestamp);
	}
	else {
		$date = date($format1, $timestamp);
		if ($format2 != '') {
			$date .= "\n".date($format2, $timestamp);
		}
	}
	
	return $date;
}

/**
 * Produces a combined/user defined graph
 *
 * @param array List of source modules
 * @param array List of weighs for each module
 * @param int Period (in seconds)
 * @param int Width, in pixels
 * @param int Height, in pixels
 * @param string Title for graph
 * @param string Unit name, for render in legend
 * @param int Show events in graph (set to 1)
 * @param int Show alerts in graph (set to 1)
 * @param int Pure mode (without titles) (set to 1)
 * @param int Date to start of getting info.
 * @param mixed If is a projection graph this parameter will be module data with prediction data (the projection) 
 * or false in other case.
 * @param array List of names for the items. Should have the same size as the module list.
 * @param array List of units for the items. Should have the same size as the module list.
 * @param bool Show the last value of the item on the list.
 * @param bool Show the max value of the item on the list.
 * @param bool Show the min value of the item on the list.
 * @param bool Show the average value of the item on the list.
 * 
 * @return Mixed 
 */
function graphic_combined_module ($module_list, $weight_list, $period,
	$width, $height, $title, $unit_name, $show_events = 0,
	$show_alerts = 0, $pure = 0, $stacked = 0, $date = 0,
	$only_image = false, $homeurl = '', $ttl = 1, $projection = false,
	$prediction_period = false, $background_color = 'white',
	$name_list = array(), $unit_list = array(), $show_last = true, $show_max = true,
	$show_min = true, $show_avg = true, $labels = array(), $dashboard = false,
	$vconsole = false, $percentil = null, $from_interface = false) {
	
	global $config;
	global $graphic_type;
	
	$time_format_2 = '';
	$temp_range = $period;
	
	if ($projection != false) {
		if ($period < $prediction_period)
			$temp_range = $prediction_period;
	}
	
	// Set the title and time format
	if ($temp_range <= SECONDS_6HOURS) {
		$time_format = 'H:i:s';
	}
	elseif ($temp_range < SECONDS_1DAY) {
		$time_format = 'H:i';
	}
	elseif ($temp_range < SECONDS_15DAYS) {
		$time_format = 'M d';
		$time_format_2 = 'H:i';
		if ($projection != false) {
			$time_format_2 = 'H\h';
		}
	}
	elseif ($temp_range <= SECONDS_1MONTH) {
		$time_format = 'M d';
		$time_format_2 = 'H\h';
	} 
	else {
		$time_format = 'M d';
		$time_format_2 = 'H\h';
	}
	
	// Set variables
	if ($date == 0)
		$date = get_system_time();
	$datelimit = $date - $period;
	$search_in_history_db = db_search_in_history_db($datelimit);
	$resolution = $config['graph_res'] * 50; //Number of points of the graph
	$interval = (int) ($period / $resolution);
	
	// If projection graph, fill with zero previous data to projection interval	
	if ($projection != false) {
		$j = $datelimit;
		$in_range = true;
		while ($in_range) {
			$timestamp_f = graph_get_formatted_date($j, $time_format, $time_format_2);
			
			//$timestamp_f = date('d M Y H:i:s', $j);
			$before_projection[$timestamp_f] = 0;
			
			if ($j > $date) {
				$in_range = false;
			}
			$j = $j + $interval;
		}
	}
	
	// Added support for projection graphs (normal_module + 1(prediction data))
	if ($projection !== false) { 
		$module_number = count ($module_list) + 1;
	}
	else {
		$module_number = count ($module_list);
	}
	
	$names_number = count($name_list);
	$units_number = count($unit_list);
	
	// interval - This is the number of "rows" we are divided the time to fill data.
	//    more interval, more resolution, and slower.
	// periodo - Gap of time, in seconds. This is now to (now-periodo) secs
	
	// Init weights
	for ($i = 0; $i < $module_number; $i++) {
		if (! isset ($weight_list[$i])) {
			$weight_list[$i] = 1;
		}
		else if ($weight_list[$i] == 0) {
			$weight_list[$i] = 1;
		}
	}
	
	// Set data containers
	for ($i = 0; $i < $resolution; $i++) {
		$timestamp = $datelimit + ($interval * $i);/*
		$timestamp_short = date($time_format, $timestamp);
		$long_index[$timestamp_short] = date(
		html_entity_decode($config['date_format'], ENT_QUOTES, "UTF-8"), $timestamp);
		$timestamp = $timestamp_short;*/
		
		$graph[$timestamp]['count'] = 0;
		$graph[$timestamp]['timestamp_bottom'] = $timestamp;
		$graph[$timestamp]['timestamp_top'] = $timestamp + $interval;
		$graph[$timestamp]['min'] = 0;
		$graph[$timestamp]['max'] = 0;
		$graph[$timestamp]['event'] = 0;
		$graph[$timestamp]['alert'] = 0;
	}
	
	$long_index = array();
	
	$graph_values = array();
	$module_name_list = array();
	$collector = 0;
	$user = users_get_user_by_id($config['id_user']);
	$user_flash_charts = $user['flash_chart'];
	
	if ($user_flash_charts == 1)
		$flash_charts = true;
	elseif($user_flash_charts == -1)
		$flash_charts = $config['flash_charts'];
	elseif($user_flash_charts == 0)
		$flash_charts = false;
	
	if ($only_image) {
		$flash_charts = false;
	}
	
	// Calculate data for each module
	for ($i = 0; $i < $module_number; $i++) {
		// If its a projection graph, first module will be data and second will be the projection
		if ($projection != false && $i != 0) {
			$agent_module_id = $module_list[0];
			
			$id_module_type = modules_get_agentmodule_type ($agent_module_id);
			$module_type = modules_get_moduletype_name ($id_module_type);
			$uncompressed_module = is_module_uncompressed ($module_type);
		}
		else {
			$agent_module_id = $module_list[$i];
			
			$id_module_type = modules_get_agentmodule_type ($agent_module_id);
			$module_type = modules_get_moduletype_name ($id_module_type);
			$uncompressed_module = is_module_uncompressed ($module_type);
		}
		
		if ($uncompressed_module) {
			$avg_only = 1;
		}
		
		// Get event data (contains alert data too)
		if ($show_events == 1 || $show_alerts == 1) {
			$events = db_get_all_rows_filter ('tevento',
				array ('id_agentmodule' => $agent_module_id,
					"utimestamp > $datelimit",
					"utimestamp < $date",
					'order' => 'utimestamp ASC'),
				array ('evento', 'utimestamp', 'event_type'));
			if ($events === false) {
				$events = array ();
			}
		}
		
		// Get module data
		$data = db_get_all_rows_filter ('tagente_datos',
			array ('id_agente_modulo' => $agent_module_id,
				"utimestamp > $datelimit",
				"utimestamp < $date",
				'order' => 'utimestamp ASC'),
			array ('datos', 'utimestamp'), 'AND', $search_in_history_db);
		if ($data === false) {
			$data = array ();
		}
		
		// Uncompressed module data
		if ($uncompressed_module) {
			$min_necessary = 1;
		
		// Compressed module data
		}
		else {
			// Get previous data
			$previous_data = modules_get_previous_data ($agent_module_id, $datelimit);
			if ($previous_data !== false) {
				$previous_data['utimestamp'] = $datelimit;
				array_unshift ($data, $previous_data);
			}
			
			// Get next data
			$nextData = modules_get_next_data ($agent_module_id, $date);
			if ($nextData !== false) {
				array_push ($data, $nextData);
			}
			else if (count ($data) > 0) {
				// Propagate the last known data to the end of the interval
				$nextData = array_pop ($data);
				array_push ($data, $nextData);
				$nextData['utimestamp'] = $date;
				array_push ($data, $nextData);
			}
			
			$min_necessary = 2;
		}
		
		// Set initial conditions
		$graph_values[$i] = array();
		
		// Check available data
		if (count ($data) < $min_necessary) {
			continue;
		}
		
		if (!empty($name_list) && $names_number == $module_number && isset($name_list[$i])) {
			if ($labels[$agent_module_id] != '')
				$module_name_list[$i] = $labels[$agent_module_id];
			else {
				$agent_name = io_safe_output(
					modules_get_agentmodule_agent_name ($agent_module_id));
				$module_name = io_safe_output(
					modules_get_agentmodule_name ($agent_module_id));
				
				if ($flash_charts)
					$module_name_list[$i] = '<span style=\"font-size:' . ($config['font_size']) . 'pt;font-family: smallfontFont;\" >' . $agent_name . " / " . $module_name. '</span>';
				else
					$module_name_list[$i] = $agent_name . " / " . $module_name;
			}
		}
		else {
			//Get and process agent name
			$agent_name = io_safe_output(
				modules_get_agentmodule_agent_name ($agent_module_id));
			$agent_name = ui_print_truncate_text($agent_name, 'agent_small', false, true, false, '...', false);
			
			$agent_id = agents_get_agent_id ($agent_name);
			
			//Get and process module name
			$module_name = io_safe_output(
				modules_get_agentmodule_name ($agent_module_id));
			$module_name = sprintf(__("%s"), $module_name);
			$module_name = ui_print_truncate_text($module_name, 'module_small', false, true, false, '...', false);
			
			if ($flash_charts) {
				if ($labels[$agent_module_id] != '')
					$module_name_list[$i] = '<span style=\"font-size:' . 
						($config['font_size']) . 'pt;font-family: smallfontFont;\" >' . 
						$labels[$agent_module_id] . '</span>';
				else
					$module_name_list[$i] = '<span style=\"font-size:' . 
						($config['font_size']) . 'pt;font-family: smallfontFont;\" >' . 
						$agent_name . ' / ' . $module_name . '</span>';
			}
			else {
				if ($labels[$agent_module_id] != '')
					$module_name_list[$i] = $labels[$agent_module_id];
				else
					$module_name_list[$i] = $agent_name . ' / ' . $module_name;
			}
		}
		
		// Data iterator
		$j = 0;
		
		// Event iterator
		$k = 0;
		
		// Set initial conditions
		
		//$graph_values[$i] = array();
		$temp_graph_values = array();
		
		if ($data[0]['utimestamp'] == $datelimit) {
			$previous_data = $data[0]['datos'];
			$j++;
		}
		else {
			$previous_data = 0;
		}
		
		$max = 0;
		$min = null;
		$avg = 0;
		$countAvg = 0;
		
		// Calculate chart data
		$last_known = $previous_data;
		for ($l = 0; $l < $resolution; $l++) {
			$countAvg ++;
			
			$timestamp = $datelimit + ($interval * $l);
			$timestamp_short = graph_get_formatted_date($timestamp, $time_format, $time_format_2);
			
			$long_index[$timestamp_short] = date(
			html_entity_decode($config['date_format'], ENT_QUOTES, "UTF-8"), $timestamp);
			//$timestamp = $timestamp_short;
			
			$total = 0;
			$count = 0;
			
			// Read data that falls in the current interval
			$interval_min = $last_known;
			$interval_max = $last_known;
			while (isset ($data[$j]) && $data[$j]['utimestamp'] >= $timestamp && $data[$j]['utimestamp'] < ($timestamp + $interval)) {
				if ($data[$j]['datos'] > $interval_max) {
					$interval_max = $data[$j]['datos'];
				}
				else if ($data[$j]['datos'] < $interval_max) {
					$interval_min = $data[$j]['datos'];
				}
				$total += $data[$j]['datos'];
				$last_known = $data[$j]['datos'];
				$count++;
				$j++;
			}
			
			// Average
			if ($count > 0) {
				$total /= $count;
			}
			
			// Read events and alerts that fall in the current interval
			$event_value = 0;
			$alert_value = 0;
			while (isset ($events[$k]) && $events[$k]['utimestamp'] >= $timestamp && $events[$k]['utimestamp'] <= ($timestamp + $interval)) {
				if ($show_events == 1) {
					$event_value++;
				}
				if ($show_alerts == 1 && substr ($events[$k]['event_type'], 0, 5) == 'alert') {
					$alert_value++;
				}
				$k++;
			}
			
			// Data
			if ($count > 0) {
				//$graph_values[$i][$timestamp] = $total * $weight_list[$i];
				$temp_graph_values[$timestamp_short] = $total * $weight_list[$i];
			}
			else {
				// Compressed data
				if ($uncompressed_module || ($timestamp > time ())) {
					$temp_graph_values[$timestamp_short] = 0;
				}
				else {
					$temp_graph_values[$timestamp_short] = $last_known * $weight_list[$i];
				}
			}
			
			//Extract max, min, avg
			if ($max < $temp_graph_values[$timestamp_short]) {
				$max = $temp_graph_values[$timestamp_short];
			}
			
			if (isset($min)) {
				if ($min > $temp_graph_values[$timestamp_short]) {
					$min = $temp_graph_values[$timestamp_short];
				}
			}
			else {
				$min = $temp_graph_values[$timestamp_short];
			}
			$avg += $temp_graph_values[$timestamp_short];
			
			// Added to support projection graphs
			if ($projection != false and $i != 0) {
				$projection_data = array();
				$projection_data = array_merge($before_projection, $projection); 
				$graph_values[$i] = $projection_data;
			}
			else {
				$graph_values[$i] = $temp_graph_values; 
			}
		}
		
		//Add the max, min and avg in the legend
		$avg = round($avg / $countAvg, 1);
		
		$graph_stats = get_graph_statistics($graph_values[$i]);
		
		if (!isset($config["short_module_graph_data"]))
			$config["short_module_graph_data"] = true;
		
		if ($config["short_module_graph_data"]) {
			$min = $graph_stats['min'];
			$max = $graph_stats['max'];
			$avg = $graph_stats['avg'];
			$last = $graph_stats['last'];
			
			if ($min > 1000000)
				$min = sprintf("%sM", remove_right_zeros(number_format($min / 1000000, $config['graph_precision'])));
			else if ($min > 1000)
				$min = sprintf("%sK", remove_right_zeros(number_format($min / 1000, $config['graph_precision'])));
			
			if ($max > 1000000)
				$max = sprintf("%sM", remove_right_zeros(number_format($max / 1000000, $config['graph_precision'])));
			else if ($max > 1000)
				$max = sprintf("%sK", remove_right_zeros(number_format($max / 1000, $config['graph_precision'])));
			
			if ($avg > 1000000)
				$avg = sprintf("%sM", remove_right_zeros(number_format($avg / 1000000, $config['graph_precision'])));
			else if ($avg > 1000)
				$avg = sprintf("%sK", remove_right_zeros(number_format($avg / 1000, $config['graph_precision'])));
			
			if ($last > 1000000)
				$last = sprintf("%sM", remove_right_zeros(number_format($last / 1000000, $config['graph_precision'])));
			else if ($last > 1000)
				$last = sprintf("%sK", remove_right_zeros(number_format($last / 1000, $config['graph_precision'])));
		}
		else {
			$min = remove_right_zeros(number_format($graph_stats['min'], $config['graph_precision']));
			$max = remove_right_zeros(number_format($graph_stats['max'], $config['graph_precision']));
			$avg = remove_right_zeros(number_format($graph_stats['avg'], $config['graph_precision']));
			$last = remove_right_zeros(number_format($graph_stats['last'], $config['graph_precision']));
		}
		
		
		if (!empty($unit_list) && $units_number == $module_number && isset($unit_list[$i])) {
			$unit = $unit_list[$i];
		}
		else {
			$unit = modules_get_unit($agent_module_id);
		}
		
		if ($projection == false or ($projection != false and $i == 0)) {
			$module_name_list[$i] .= ": ";
			if ($show_last)
				$module_name_list[$i] .= __('Last') . ": $last $unit; ";
			if ($show_max)
				$module_name_list[$i] .= __("Max") . ": $max $unit; ";
			if ($show_min)
				$module_name_list[$i] .= __("Min") . ": $min $unit; ";
			if ($show_avg)
				$module_name_list[$i] .= __("Avg") . ": $avg $unit";
		}
		
		if ($weight_list[$i] != 1) {
			//$module_name_list[$i] .= " (x". format_numeric ($weight_list[$i], 1).")";
			$module_name_list[$i] .= " (x". format_numeric ($weight_list[$i], 1).")";
		}
		
		//$graph_values[$module_name_list[$i]] = $graph_values[$i];
		//unset($graph_values[$i]);
		
		//$graph_values[$i] = $graph_values[$i];
	}
	
	$temp = array();
	
	if ($flash_charts === false && $stacked == CUSTOM_GRAPH_GAUGE)
		$stacked = CUSTOM_GRAPH_BULLET_CHART;
	
	switch ($stacked) {
		case CUSTOM_GRAPH_BULLET_CHART_THRESHOLD:
		case CUSTOM_GRAPH_BULLET_CHART:
			$datelimit = $date - $period;
			if($stacked == CUSTOM_GRAPH_BULLET_CHART_THRESHOLD){
				$acumulador = 0;
				foreach ($module_list as $module_item) {
					$module = $module_item;
					$query_last_value = sprintf('
						SELECT datos
						FROM tagente_datos
						WHERE id_agente_modulo = %d
							AND utimestamp < %d
							ORDER BY utimestamp DESC',
						$module, $date);
					$temp_data = db_get_value_sql($query_last_value);
					if ($acumulador < $temp_data){
						$acumulador = $temp_data;
					}
				}
			}
			foreach ($module_list as $module_item) {
				$automatic_custom_graph_meta = false;
				if ($config['metaconsole']) {
					// Automatic custom graph from the report template in metaconsole
					if (is_array($module_list[$i])) {
						$server = metaconsole_get_connection_by_id ($module_item['server']);
						metaconsole_connect($server);
						$automatic_custom_graph_meta = true;
					}
				}
				
				if ($automatic_custom_graph_meta)
					$module = $module_item['module'];
				else
					$module = $module_item;
				
				$search_in_history_db = db_search_in_history_db($datelimit);
				
				$temp[$module] = modules_get_agentmodule($module);
				$query_last_value = sprintf('
					SELECT datos
					FROM tagente_datos
					WHERE id_agente_modulo = %d
						AND utimestamp < %d
						ORDER BY utimestamp DESC',
					$module, $date);
				$temp_data = db_get_value_sql($query_last_value);
								
				if ($temp_data) {
					if (is_numeric($temp_data))
						$value = $temp_data;
					else
						$value = count($value);
				}
				else {
					if ($flash_charts === false)
						$value = 0;
					else
						$value = false;
				}
				
				
				if ( !empty($labels) && isset($labels[$module]) )
					$label = io_safe_input($labels[$module]);
				else
					$label = agents_get_name($temp[$module]['id_agente']) . ': ' . $temp[$module]['nombre'];
				
				$temp[$module]['label'] = $label;
				$temp[$module]['value'] = $value;
				$temp_max = reporting_get_agentmodule_data_max($module,$period,$date);
				if ($temp_max < 0)
					$temp_max = 0;
				if (isset($acumulador)){
					$temp[$module]['max'] = $acumulador;
				}else{
					$temp[$module]['max'] = ($temp_max === false) ? 0 : $temp_max;
				}

				$temp_min = reporting_get_agentmodule_data_min($module,$period,$date);
				if ($temp_min < 0)
					$temp_min = 0;
				$temp[$module]['min'] = ($temp_min === false) ? 0 : $temp_min;
			}
			break;
		case CUSTOM_GRAPH_HBARS:
		case CUSTOM_GRAPH_VBARS:
			$datelimit = $date - $period;
			
			$label = '';
			foreach ($module_list as $module) {
				$module_data = modules_get_agentmodule($module);
				$query_last_value = sprintf('
					SELECT datos
					FROM tagente_datos
					WHERE id_agente_modulo = %d
						AND utimestamp < %d
						ORDER BY utimestamp DESC',
					$module, $date);
				$temp_data = db_get_value_sql($query_last_value);
				
				$agent_name = io_safe_output(
					modules_get_agentmodule_agent_name ($module));
				
				if (!empty($labels) && isset($labels[$module]) )
					$label = $labels[$module];
				else
					$label = $agent_name . " - " .$module_data['nombre'];
				$temp[$label]['g'] = round($temp_data,4);
			}
			break;
		case CUSTOM_GRAPH_PIE:
			$datelimit = $date - $period;
			$total_modules = 0;
			
			foreach ($module_list as $module) {
				$data_module = modules_get_agentmodule($module);
				$query_last_value = sprintf('
					SELECT datos
					FROM tagente_datos
					WHERE id_agente_modulo = %d
						AND utimestamp > %d
						AND utimestamp < %d
						ORDER BY utimestamp DESC',
					$module, $datelimit, $date);
				$temp_data = db_get_value_sql($query_last_value);
				
				if ( $temp_data ){
					if (is_numeric($temp_data))
						$value = $temp_data;
					else
						$value = count($value);
				}
				else {
					$value = false;
				}
				$total_modules += $value;

				if ( !empty($labels) && isset($labels[$module]) ){
					$label = io_safe_output($labels[$module]);
				}else {
					$agent_name = agents_get_name($data_module['id_agente']);
					$label = io_safe_output($agent_name . ": " . $data_module['nombre']);
				}
				
				$temp[$label] = array('value'=>$value,
										'unit'=>$data_module['unit']);
			}
			$temp['total_modules'] = $total_modules;
			
			break;
		case CUSTOM_GRAPH_GAUGE:
			$datelimit = $date - $period;
			foreach ($module_list as $module) {
				$temp[$module] = modules_get_agentmodule($module);
				$query_last_value = sprintf('
					SELECT datos
					FROM tagente_datos
					WHERE id_agente_modulo = %d
						AND utimestamp < %d
						ORDER BY utimestamp DESC',
					$module, $date);
				$temp_data = db_get_value_sql($query_last_value);
				if ( $temp_data ) {
					if (is_numeric($temp_data))
						$value = $temp_data;
					else
						$value = count($value);
				}
				else {
					$value = false;
				}
				$temp[$module]['label'] = ($labels[$module] != '') ? $labels[$module] : $temp[$module]['nombre'];
				
				$temp[$module]['value'] = $value;
				$temp[$module]['label'] = ui_print_truncate_text($temp[$module]['label'],"module_small",false,true,false,"..");
				
				if ($temp[$module]['unit'] == '%') {
					$temp[$module]['min'] =	0;
					$temp[$module]['max'] = 100;
				}
				else {
					$min = $temp[$module]['min'];
					if ($temp[$module]['max'] == 0)
						$max = reporting_get_agentmodule_data_max($module,$period,$date);
					else
						$max = $temp[$module]['max'];
					$temp[$module]['min'] = ($min == 0 ) ? 0 : $min;
					$temp[$module]['max'] = ($max == 0 ) ? 100 : $max;
				}
				$temp[$module]['gauge'] = uniqid('gauge_');
			}
			break;
		default:
			if (!is_null($percentil)) {
				
				foreach ($graph_values as $graph_group => $point) {
					foreach ($point as $timestamp_point => $point_value) {
						$temp[$timestamp_point][$graph_group] = $point_value;
					}
					
					$percentile_value = get_percentile($percentil, $point);
					$percentil_result[$graph_group] = array_fill ( 0, count($point), $percentile_value);
					$series_type[$graph_group] = 'line';
					$agent_name = io_safe_output(
						modules_get_agentmodule_agent_name ($module_list[$graph_group]));
					$module_name = io_safe_output(
						modules_get_agentmodule_name ($module_list[$graph_group]));
					$module_name_list['percentil'.$graph_group] = __('Percentile %dº', $percentil) . __(' of module ') . $agent_name .' / ' . $module_name . ' (' . $percentile_value . ' ' . $unit . ') ';
				}
			}
			else {
				foreach ($graph_values as $graph_group => $point) {
					foreach ($point as $timestamp_point => $point_value) {
						$temp[$timestamp_point][$graph_group] = $point_value;
					}
				}
			}
			break;
	}
	
	$graph_values = $temp;
	
	/*
	for ($i = 0; $i < $module_number; $i++) {
		if ($weight_list[$i] != 1) {
			$module_name_list[$i] .= " (x". format_numeric ($weight_list[$i], 1).")";
		}
	}
	*/
	
	
	$water_mark = array(
		'file' => $config['homedir'] .  "/images/logo_vertical_water.png",
		'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
	
	
	//Work around for fixed the agents name with huge size chars.
	$fixed_font_size = $config['font_size'] - 2;
	
	//Set graph color
	
	$color = array();
	
	$color[0] = array('border' => '#000000',
		'color' => $config['graph_color1'],
		'alpha' => CHART_DEFAULT_ALPHA);
	$color[1] = array('border' => '#000000',
		'color' => $config['graph_color2'],
		'alpha' => CHART_DEFAULT_ALPHA);
	$color[2] = array('border' => '#000000',
		'color' => $config['graph_color3'],
		'alpha' => CHART_DEFAULT_ALPHA);
	$color[3] = array('border' => '#000000',
		'color' => $config['graph_color4'],
		'alpha' => CHART_DEFAULT_ALPHA);
	$color[4] = array('border' => '#000000',
		'color' => $config['graph_color5'],
		'alpha' => CHART_DEFAULT_ALPHA);
	$color[5] = array('border' => '#000000',
		'color' => $config['graph_color6'],
		'alpha' => CHART_DEFAULT_ALPHA);
	$color[6] = array('border' => '#000000',
		'color' => $config['graph_color7'],
		'alpha' => CHART_DEFAULT_ALPHA);
	$color[7] = array('border' => '#000000',
		'color' => $config['graph_color8'],
		'alpha' => CHART_DEFAULT_ALPHA);
	$color[8] = array('border' => '#000000',
		'color' => $config['graph_color9'],
		'alpha' => CHART_DEFAULT_ALPHA);
	$color[9] = array('border' => '#000000',
		'color' => $config['graph_color10'],
		'alpha' => CHART_DEFAULT_ALPHA);
	$color[11] = array('border' => '#000000',
		'color' => COL_GRAPH9,
		'alpha' => CHART_DEFAULT_ALPHA);
	$color[12] = array('border' => '#000000',
		'color' => COL_GRAPH10,
		'alpha' => CHART_DEFAULT_ALPHA);
	$color[13] = array('border' => '#000000',
		'color' => COL_GRAPH11,
		'alpha' => CHART_DEFAULT_ALPHA);
	$color[14] = array('border' => '#000000',
		'color' => COL_GRAPH12,
		'alpha' => CHART_DEFAULT_ALPHA);
	$color[15] = array('border' => '#000000',
		'color' => COL_GRAPH13,
		'alpha' => CHART_DEFAULT_ALPHA);
	
	$threshold_data = array();

	if ($from_interface) {
		$yellow_threshold = 0;
		$red_threshold = 0;

		$yellow_up = 0;
		$red_up = 0;

		$yellow_inverse = 0;
		$red_inverse = 0;

		$compare_warning = false;
		$compare_critical = false;

		$do_it_warning_min = true;
		$do_it_critical_min = true;

		$do_it_warning_max = true;
		$do_it_critical_max = true;

		$do_it_warning_inverse = true;
		$do_it_critical_inverse = true;
		foreach ($module_list as $index => $id_module) {
			// Get module warning_min and critical_min
			$warning_min = db_get_value('min_warning','tagente_modulo','id_agente_modulo',$id_module);
			$critical_min = db_get_value('min_critical','tagente_modulo','id_agente_modulo',$id_module);

			if ($index == 0) {
				$compare_warning = $warning_min;
			}
			else {
				if ($compare_warning != $warning_min) {
					$do_it_warning_min = false;
				}
			}

			if ($index == 0) {
				$compare_critical = $critical_min;
			}
			else {
				if ($compare_critical != $critical_min) {
					$do_it_critical_min = false;
				}
			}
		}

		if ($do_it_warning_min || $do_it_critical_min) {
			foreach ($module_list as $index => $id_module) {
				$warning_max = db_get_value('max_warning','tagente_modulo','id_agente_modulo',$id_module);
				$critical_max = db_get_value('max_critical','tagente_modulo','id_agente_modulo',$id_module);

				if ($index == 0) {
					$yellow_up = $warning_max;
				}
				else {
					if ($yellow_up != $warning_max) {
						$do_it_warning_max = false;
					}
				}

				if ($index == 0) {
					$red_up = $critical_max;
				}
				else {
					if ($red_up != $critical_max) {
						$do_it_critical_max = false;
					}
				}
			}
		}

		if ($do_it_warning_min || $do_it_critical_min) {
			foreach ($module_list as $index => $id_module) {
				$warning_inverse = db_get_value('warning_inverse','tagente_modulo','id_agente_modulo',$id_module);
				$critical_inverse = db_get_value('critical_inverse','tagente_modulo','id_agente_modulo',$id_module);

				if ($index == 0) {
					$yellow_inverse = $warning_inverse;
				}
				else {
					if ($yellow_inverse != $warning_inverse) {
						$do_it_warning_inverse = false;
					}
				}

				if ($index == 0) {
					$red_inverse = $critical_inverse;
				}
				else {
					if ($red_inverse != $critical_inverse) {
						$do_it_critical_inverse = false;
					}
				}
			}
		}
		
		if ($do_it_warning_min && $do_it_warning_max && $do_it_warning_inverse) {
			$yellow_threshold = $compare_warning;
			$threshold_data['yellow_up'] = $yellow_up;
			$threshold_data['yellow_inverse'] = (bool)$yellow_inverse;
		}

		if ($do_it_critical_min && $do_it_critical_max && $do_it_critical_inverse) {
			$red_threshold = $compare_critical;
			$threshold_data['red_up'] = $red_up;
			$threshold_data['red_inverse'] = (bool)$red_inverse;
		}
	}

	switch ($stacked) {
		case CUSTOM_GRAPH_AREA:
			return area_graph($flash_charts, $graph_values, $width,
				$height, $color, $module_name_list, $long_index,
				ui_get_full_url("images/image_problem.opaque.png", false, false, false),
				$title, "", $homeurl, $water_mark, $config['fontpath'],
				$fixed_font_size, $unit, $ttl, array(), array(), $yellow_threshold, $red_threshold,  '',
				false, '', true, $background_color,$dashboard, $vconsole, 0, $percentil_result, $threshold_data);
			break;
		default:
		case CUSTOM_GRAPH_STACKED_AREA: 
			return stacked_area_graph($flash_charts, $graph_values,
				$width, $height, $color, $module_name_list, $long_index,
				ui_get_full_url("images/image_problem.opaque.png", false, false, false),
				$title, "", $water_mark, $config['fontpath'], $fixed_font_size,
				"", $ttl, $homeurl, $background_color,$dashboard, $vconsole);
			break;
		case CUSTOM_GRAPH_LINE:  
			return line_graph($flash_charts, $graph_values, $width,
				$height, $color, $module_name_list, $long_index,
				ui_get_full_url("images/image_problem.opaque.png", false, false, false),
				$title, "", $water_mark, $config['fontpath'], $fixed_font_size,
				$unit, $ttl, $homeurl, $background_color, $dashboard, 
				$vconsole, $series_type, $percentil_result, $yellow_threshold, $red_threshold, $threshold_data); 
			break;
		case CUSTOM_GRAPH_STACKED_LINE:
			return stacked_line_graph($flash_charts, $graph_values,
				$width, $height, $color, $module_name_list, $long_index,
				ui_get_full_url("images/image_problem.opaque.png", false, false, false),
				"", "", $water_mark, $config['fontpath'], $fixed_font_size,
				"", $ttl, $homeurl, $background_color, $dashboard, $vconsole);
			break;
		case CUSTOM_GRAPH_BULLET_CHART_THRESHOLD:
		case CUSTOM_GRAPH_BULLET_CHART:
			return stacked_bullet_chart($flash_charts, $graph_values,
				$width, $height, $color, $module_name_list, $long_index,
				ui_get_full_url("images/image_problem.opaque.png", false, false, false),
				"", "", $water_mark, $config['fontpath'], ($config['font_size']+1),
				"", $ttl, $homeurl, $background_color);
			break;
		case CUSTOM_GRAPH_GAUGE:
			return stacked_gauge($flash_charts, $graph_values,
				$width, $height, $color, $module_name_list, $long_index,
				ui_get_full_url("images/image_problem.opaque.png", false, false, false),
				"", "", $water_mark, $config['fontpath'], $fixed_font_size,
				"", $ttl, $homeurl, $background_color);
			break;
		case CUSTOM_GRAPH_HBARS:
			return hbar_graph($flash_charts, $graph_values,
				$width, $height, $color, $module_name_list, $long_index,
				ui_get_full_url("images/image_problem.opaque.png", false, false, false),
				"", "", $water_mark, $config['fontpath'], $fixed_font_size,
				"", $ttl, $homeurl, $background_color);
			break;
		case CUSTOM_GRAPH_VBARS:
			return vbar_graph($flash_charts, $graph_values,
				$width, $height, $color, $module_name_list, $long_index,
				ui_get_full_url("images/image_problem.opaque.png", false, false, false),
				"", "", $water_mark, $config['fontpath'], $fixed_font_size,
				"", $ttl, $homeurl, $background_color);
			break;
		case CUSTOM_GRAPH_PIE:
			return ring_graph($flash_charts, $graph_values, $width, $height,
				$others_str, $homeurl, $water_mark, $config['fontpath'],
				($config['font_size']+1), $ttl, false, $color, false);
			break;
	}
}

/**
 * Print a graph with access data of agents
 * 
 * @param integer id_agent Agent ID
 * @param integer width pie graph width
 * @param integer height pie graph height
 * @param integer period time period
 * @param bool return or echo the result flag
 */
function graphic_agentaccess ($id_agent, $width, $height, $period = 0, $return = false) {
	global $config;
	global $graphic_type;
	
	
	$data = array ();
	
	$resolution = $config["graph_res"] * ($period * 2 / $width); // Number of "slices" we want in graph
	
	$interval = (int) ($period / $resolution);
	$date = get_system_time ();
	$datelimit = $date - $period;
	$periodtime = floor ($period / $interval);
	$time = array ();
	$data = array ();
	
	$empty_data = true;
	for ($i = 0; $i < $interval; $i++) {
		$bottom = $datelimit + ($periodtime * $i);
		if (! $graphic_type) {
			$name = date('G:i', $bottom);
		}
		else {
			$name = $bottom;
		}
		
		$top = $datelimit + ($periodtime * ($i + 1));
		switch ($config["dbtype"]) {
			case "mysql":
			case "postgresql":
				$data[$name]['data'] = (int) db_get_value_filter ('COUNT(*)',
					'tagent_access',
					array ('id_agent' => $id_agent,
						'utimestamp > '.$bottom,
						'utimestamp < '.$top));
				break;
			case "oracle":
				$data[$name]['data'] = (int) db_get_value_filter ('count(*)',
					'tagent_access',
					array ('id_agent' => $id_agent,
						'utimestamp > '.$bottom,
						'utimestamp < '.$top));
				break;
		}
		
		if ($data[$name]['data'] != 0) {
			$empty_data = false;
		}
	}
	
	if($config["fixed_graph"] == false){
		$water_mark = array('file' =>
			$config['homedir'] . "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
	}

	if ($empty_data) {
		$out = graph_nodata_image($width, $height);
	}
	else {
		$out = area_graph($config['flash_charts'], $data, $width, $height, null, null, null,
			ui_get_full_url("images/image_problem.opaque.png", false, false, false),
			"", "", ui_get_full_url(false, false, false, false), $water_mark,
			$config['fontpath'], $config['font_size'], "", 1, array(), array(), 0, 0, '', false, '', false);
	}
	
	if ($return) {
		return $out;
	}
	else {
		echo $out;
	}
}

/**
 * Print a pie graph with alerts defined/fired data
 * 
 * @param integer Number of defined alerts
 * @param integer Number of fired alerts
 * @param integer width pie graph width
 * @param integer height pie graph height
 * @param bool return or echo flag
 */
function graph_alert_status ($defined_alerts, $fired_alerts, $width = 300, $height = 200, $return = false) {
	global $config;
	
	$data = array(__('Not fired alerts') => $defined_alerts - $fired_alerts, __('Fired alerts') => $fired_alerts);
	$colors = array(COL_NORMAL, COL_ALERTFIRED);
	
	if($config["fixed_graph"] == false){
		$water_mark = array('file' =>
			$config['homedir'] . "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
	}
	
	$out = pie2d_graph($config['flash_charts'], $data, $width, $height, __("other"),
		'', '', $config['fontpath'], $config['font_size'], 1, "hidden", $colors);
	
	if ($return) {
		return $out;
	}
	else {
		echo $out;
	}
}

// If any value is negative, truncate it to 0
function truncate_negatives(&$element) {
	if ($element < 0) {
		$element = 0;
	}
}

/**
 * Print a pie graph with events data of agent or all agents (if id_agent = false)
 * 
 * @param integer id_agent Agent ID
 * @param integer width pie graph width
 * @param integer height pie graph height
 * @param bool return or echo flag
 * @param bool show_not_init flag
 */
function graph_agent_status ($id_agent = false, $width = 300, $height = 200, $return = false, $show_not_init = false, $data_agents=false) {
	global $config;
	
	
	$filter = array('disabled' => 0, 'id_grupo' => array_keys(users_get_groups(false, 'AR', false)));
	
	
	if (!empty($id_agent)) {
		$filter['id_agente'] = $id_agent; 
	}
	
	$fields = array('SUM(critical_count) AS Critical', 
		'SUM(warning_count) AS Warning', 
		'SUM(normal_count) AS Normal', 
		'SUM(unknown_count) AS Unknown');
	
	if ($show_not_init) {
		$fields[] = 'SUM(notinit_count) "Not init"';
	}

	if ($data_agents == false) {
		$data = db_get_row_filter('tagente', $filter, $fields);
	} else {
		$data = $data_agents;
	}
	
	if (empty($data)) {
		$data = array();
	}
	
	array_walk($data, 'truncate_negatives');
	
	if($config["fixed_graph"] == false){
		$water_mark = array('file' =>
			$config['homedir'] . "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
	}
	
	//$colors = array(COL_CRITICAL, COL_WARNING, COL_NORMAL, COL_UNKNOWN);
	$colors[__('Critical')] = COL_CRITICAL;
	$colors[__('Warning')] = COL_WARNING;
	$colors[__('Normal')] = COL_NORMAL;
	$colors[__('Unknown')] = COL_UNKNOWN;
	
	if ($show_not_init) {
		$colors[__('Not init')] = COL_NOTINIT;
	}
	
	if (array_sum($data) == 0) {
		$data = array();
	}
	
	$out = pie2d_graph($config['flash_charts'], $data, $width, $height,
		__("other"), ui_get_full_url(false, false, false, false), '',
		$config['fontpath'], $config['font_size'], 1, "hidden", $colors);
	
	if ($return) {
		return $out;
	}
	else {
		echo $out;
	}
}


/**
 * Print a pie graph with events data of agent
 * 
 * @param integer width pie graph width
 * @param integer height pie graph height
 * @param integer id_agent Agent ID
 */
function graph_event_module ($width = 300, $height = 200, $id_agent) {
	global $config;
	global $graphic_type;

	// Fix: tag filters implemented! for tag functionality groups have to be all user_groups (propagate ACL funct!)
	$groups = users_get_groups($config["id_user"]);
	$tags_condition = tags_get_acl_tags($config['id_user'], array_keys($groups), 'ER', 'event_condition', 'AND');
	
	$data = array ();
	$max_items = 6;
	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":
			$sql = sprintf ('SELECT COUNT(id_evento) AS count_number,
					id_agentmodule
				FROM tevento
				WHERE tevento.id_agente = %d %s
				GROUP BY id_agentmodule ORDER BY count_number DESC LIMIT %d', $id_agent, $tags_condition, $max_items);
			break;
		case "oracle":
			$sql = sprintf ('SELECT COUNT(id_evento) AS count_number,
					id_agentmodule
				FROM tevento
				WHERE tevento.id_agente = %d AND rownum <= %d
				GROUP BY id_agentmodule ORDER BY count_number DESC', $id_agent, $max_items);
			break;
	}
	
	$events = db_get_all_rows_sql ($sql);
	if ($events === false) {
		if (! $graphic_type) {
			return fs_error_image ();
		}
		graphic_error ();
		return;
	}
	
	foreach ($events as $event) {
		if ($event['id_agentmodule'] == 0) {
			$key = __('System') . ' ('.$event['count_number'].')';
		}
		else {
			$key = modules_get_agentmodule_name ($event['id_agentmodule']) .
				' ('.$event['count_number'].')';
		}
		
		$data[$key] = $event["count_number"];
	}
	
	if($config["fixed_graph"] == false){
		$water_mark = array('file' =>
			$config['homedir'] . "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
	}
	return pie3d_graph($config['flash_charts'], $data, $width, $height, __("other"),
		'', $water_mark, $config['fontpath'], $config['font_size'], 1, "bottom");
}

function progress_bar($progress, $width, $height, $title = '', $mode = 1, $value_text = false, $color = false, $options = false) {
	global $config;
	
	$out_of_lim_str = io_safe_output(__("Out of limits"));
	
	$title = "";
	
	if ($value_text === false) {
		$value_text = $progress . "%";
	}
	
	$colorRGB = '';
	if ($color !== false) {
		$colorRGB = html_html2rgb($color);
		$colorRGB = implode('|', $colorRGB);
	}
	
	$class_tag = '';
	$id_tag = '';
	if ($options !== false) {
		foreach ($options as $option_type => $option_value) {
			if ($option_type == 'class')
				$class_tag = ' class="' . $option_value . '" ';
			else if ($option_type == 'id')
				$id_tag = ' id="' . $option_value . '" ';
		}
	}
	
	require_once("include_graph_dependencies.php");
	include_graphs_dependencies($config['homedir'].'/');
	
	$src = ui_get_full_url(
		"/include/graphs/fgraph.php?homeurl=../../&graph_type=progressbar" .
		"&width=".$width."&height=".$height."&progress=".$progress.
		"&mode=" . $mode . "&out_of_lim_str=".$out_of_lim_str .
		"&title=".$title."&font=".$config['fontpath']."&value_text=". $value_text . 
		"&colorRGB=". $colorRGB, false, false, false
		);
	
	return "<img title='" . $title . "' alt='" . $title . "'" . $class_tag . $id_tag . 
		" src='" . $src . "' />";
}

function progress_bubble($progress, $width, $height, $title = '', $mode = 1, $value_text = false, $color = false) {
	global $config;
	
	$hack_metaconsole = '';
	if (defined('METACONSOLE'))
		$hack_metaconsole = '../../';
	
	$out_of_lim_str = io_safe_output(__("Out of limits"));
	$title = "";
	
	if ($value_text === false) {
		$value_text = $progress . "%";
	}
	
	$colorRGB = '';
	if ($color !== false) {
		$colorRGB = html_html2rgb($color);
		$colorRGB = implode('|', $colorRGB);
	}
	
	require_once("include_graph_dependencies.php");
	include_graphs_dependencies($config['homedir'].'/');
	
	return "<img title='" . $title . "' alt='" . $title . "'" .
		" src='" . $config['homeurl'] . $hack_metaconsole . "/include/graphs/fgraph.php?homeurl=../../&graph_type=progressbubble" .
		"&width=".$width."&height=".$height."&progress=".$progress.
		"&mode=" . $mode . "&out_of_lim_str=".$out_of_lim_str .
		"&title=".$title."&font=".$config['fontpath']."&value_text=". $value_text . 
		"&colorRGB=". $colorRGB . "' />";
}

function graph_sla_slicebar ($id, $period, $sla_min, $sla_max, $date, $daysWeek = null, $time_from = null, $time_to = null, $width, $height, $home_url, $ttl = 1, $data = false, $round_corner = null) {
	global $config;
	
	if ($round_corner === null) {
		$round_corner = $config['round_corner'];
	}
	
	// If the data is not provided, we got it
	if ($data === false) {
		$data = reporting_get_agentmodule_sla_array ($id, $period,
			$sla_min, $sla_max, $date, $daysWeek, null, null);
	}
	
	$col_planned_downtime = '#20973F';
	
	$colors = array(1 => COL_NORMAL,
		2 => COL_WARNING,
		3 => COL_CRITICAL,
		4 => COL_UNKNOWN,
		5 => "#ff8400",//COL_MINOR,
		6 => COL_NOTINIT,
		7 => "#ddd");//COL_MAJOR);
	
	return slicesbar_graph($data, $period, $width, $height, $colors,
		$config['fontpath'], $round_corner, $home_url, $ttl);
}

/**
 * Print a pie graph with purge data of agent
 * 
 * @param integer id_agent ID of agent to show
 * @param integer width pie graph width
 * @param integer height pie graph height
 */
function grafico_db_agentes_purge ($id_agent, $width = 380, $height = 300) {
	global $config;
	global $graphic_type;
	
	$filter = array();
	
	if ($id_agent < 1) {
		$query = "";
	}
	else {
		$modules = agents_get_modules($id_agent);
		$module_ids = array_keys($modules);
		
		if (!empty($module_ids))
			$filter['id_agente_modulo'] = $module_ids;
	}
	
	// All data (now)
	$time_now = time();
	
	// 1 day ago
	$time_1day = $time_now - SECONDS_1DAY;
	
	// 1 week ago
	$time_1week = $time_now - SECONDS_1WEEK;
	
	// 1 month ago
	$time_1month = $time_now - SECONDS_1MONTH;
	
	// Three months ago
	$time_3months = $time_now - SECONDS_3MONTHS;
	
	$query_error = false;
	
	// Data from 1 day ago
	$num_1day = 0;
	$num_1day += (int) db_get_sql('SELECT COUNT(*)
										FROM tagente_datos
										WHERE utimestamp > ' . $time_1day);
	$num_1day += (int) db_get_sql('SELECT COUNT(*)
										FROM tagente_datos_string
										WHERE utimestamp > ' . $time_1day);
	$num_1day += (int) db_get_sql('SELECT COUNT(*)
										FROM tagente_datos_log4x
										WHERE utimestamp > ' . $time_1day);
	if ($num_1day >= 0) {
		// Data from 1 week ago
		$num_1week = 0;
		$num_1week += (int) db_get_sql('SELECT COUNT(*)
											FROM tagente_datos
											WHERE utimestamp > ' . $time_1week . '
											AND utimestamp < ' . $time_1day);
		$num_1week += (int) db_get_sql('SELECT COUNT(*)
											FROM tagente_datos_string
											WHERE utimestamp > ' . $time_1week . '
											AND utimestamp < ' . $time_1day);
		$num_1week += (int) db_get_sql('SELECT COUNT(*)
											FROM tagente_datos_log4x
											WHERE utimestamp > ' . $time_1week . '
											AND utimestamp < ' . $time_1day);
		if ($num_1week >= 0) {
			if ($num_1week > 0) {
				$num_1week = 0;
				$num_1week += (int) db_get_sql('SELECT COUNT(*)
													FROM tagente_datos
													WHERE utimestamp > ' . $time_1week);
				$num_1week += (int) db_get_sql('SELECT COUNT(*)
													FROM tagente_datos_string
													WHERE utimestamp > ' . $time_1week);
				$num_1week += (int) db_get_sql('SELECT COUNT(*)
													FROM tagente_datos_log4x
													WHERE utimestamp > ' . $time_1week);
			}
			// Data from 1 month ago
			$num_1month = 0;
			$num_1month += (int) db_get_sql('SELECT COUNT(*)
												FROM tagente_datos
												WHERE utimestamp > ' . $time_1month . '
												AND utimestamp < ' . $time_1week);
			$num_1month += (int) db_get_sql('SELECT COUNT(*)
												FROM tagente_datos_string
												WHERE utimestamp > ' . $time_1month . '
												AND utimestamp < ' . $time_1week);
			$num_1month += (int) db_get_sql('SELECT COUNT(*)
												FROM tagente_datos_log4x
												WHERE utimestamp > ' . $time_1month . '
												AND utimestamp < ' . $time_1week);
			if ($num_1month >= 0) {
				if ($num_1month > 0) {
					$num_1month = 0;
					$num_1month += (int) db_get_sql('SELECT COUNT(*)
														FROM tagente_datos
														WHERE utimestamp > ' . $time_1month);
					$num_1month += (int) db_get_sql('SELECT COUNT(*)
														FROM tagente_datos_string
														WHERE utimestamp > ' . $time_1month);
					$num_1month += (int) db_get_sql('SELECT COUNT(*)
														FROM tagente_datos_log4x
														WHERE utimestamp > ' . $time_1month);
				}
				// Data from 3 months ago
				$num_3months = 0;
				$num_3months += (int) db_get_sql('SELECT COUNT(*)
													FROM tagente_datos
													WHERE utimestamp > ' . $time_3months . '
													AND utimestamp < ' . $time_1month);
				$num_3months += (int) db_get_sql('SELECT COUNT(*)
													FROM tagente_datos
													WHERE utimestamp > ' . $time_3months . '
													AND utimestamp < ' . $time_1month);
				$num_3months += (int) db_get_sql('SELECT COUNT(*)
													FROM tagente_datos
													WHERE utimestamp > ' . $time_3months . '
													AND utimestamp < ' . $time_1month);
				if ($num_3months >= 0) {
					if ($num_3months > 0) {
						$num_3months = 0;
						$num_3months += (int) db_get_sql('SELECT COUNT(*)
															FROM tagente_datos
															WHERE utimestamp > ' . $time_3months);
						$num_3months += (int) db_get_sql('SELECT COUNT(*)
															FROM tagente_datos
															WHERE utimestamp > ' . $time_3months);
						$num_3months += (int) db_get_sql('SELECT COUNT(*)
															FROM tagente_datos
															WHERE utimestamp > ' . $time_3months);
					}
					// All data
					$num_all = 0;
					$num_all += (int) db_get_sql('SELECT COUNT(*)
														FROM tagente_datos
														WHERE utimestamp < ' . $time_3months);
					$num_all += (int) db_get_sql('SELECT COUNT(*)
														FROM tagente_datos
														WHERE utimestamp < ' . $time_3months);
					$num_all += (int) db_get_sql('SELECT COUNT(*)
														FROM tagente_datos
														WHERE utimestamp < ' . $time_3months);
					if ($num_all >= 0) {
						$num_older = $num_all - $num_3months;
						if ($config['history_db_enabled'] == 1) {
							// All data in common and history database
							$num_all_w_history = 0;
							$num_all_w_history += (int) db_get_sql('SELECT COUNT(*)
																FROM tagente_datos
																WHERE utimestamp < ' . $time_3months);
							$num_all_w_history += (int) db_get_sql('SELECT COUNT(*)
																FROM tagente_datos
																WHERE utimestamp < ' . $time_3months);
							$num_all_w_history += (int) db_get_sql('SELECT COUNT(*)
																FROM tagente_datos
																WHERE utimestamp < ' . $time_3months);
							if ($num_all_w_history >= 0) {
								$num_history = $num_all_w_history - $num_all;
							}
						}
					}
				}
			}
		}
	}
	else if (($num_1day == 0) && ($num_1week == 0) && ($num_1month == 0) && ($num_3months == 0) && ($num_all == 0)) {
		//If no data, returns empty
		$query_error = true;
	}
	
	// Error
	if ($query_error || $num_older < 0 || ($config['history_db_enabled'] == 1 && $num_history < 0)
			|| (empty($num_1day) && empty($num_1week) && empty($num_1month)
				&& empty($num_3months) && empty($num_all) 
				&& ($config['history_db_enabled'] == 1 && empty($num_all_w_history)))) {
		return html_print_image('images/image_problem.png', true);
	}

	// Data indexes
	$str_1day = __("Today");
	$str_1week = "1 ".__("Week");
	$str_1month = "1 ".__("Month");
	$str_3months = "3 ".__("Months");
	$str_older = "> 3 ".__("Months");
	
	// Filling the data array
	$data = array();
	if (!empty($num_1day))
		$data[$str_1day] = $num_1day;
	if (!empty($num_1week))
		$data[$str_1week] = $num_1week;
	if (!empty($num_1month))
		$data[$str_1month] = $num_1month;
	if (!empty($num_3months))
		$data[$str_3months] = $num_3months;
	if (!empty($num_older))
		$data[$str_older] = $num_older;
	if ($config['history_db_enabled'] == 1 && !empty($num_history)) {
		// In this pie chart only 5 elements are shown, so we need to remove
		// an element. With a history db enabled the >3 months element are dispensable
		if (count($data) >= 5 && isset($data[$str_3months]))
			unset($data[$str_3months]);

		$time_historic_db = time() - ((int)$config['history_db_days'] * SECONDS_1DAY);
		$date_human = human_time_comparation($time_historic_db);
		$str_history = "> $date_human (".__("History db").")";
		$data[$str_history] = $num_history;
	}

	$water_mark = array(
			'file' => $config['homedir'] . "/images/logo_vertical_water.png", 
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false)
		);
	
	return pie3d_graph($config['flash_charts'], $data, $width, $height,
		__('Other'), '', $water_mark, $config['fontpath'], $config['font_size']);
}

/**
 * Print a horizontal bar graph with packets data of agents
 * 
 * @param integer width pie graph width
 * @param integer height pie graph height
 */
function grafico_db_agentes_paquetes($width = 380, $height = 300) {
	global $config;
	global $graphic_type;
	
	
	$data = array ();
	$legend = array ();
	
	$agents = agents_get_group_agents (array_keys (users_get_groups (false, 'RR')), false, "none");
	$count = agents_get_modules_data_count (array_keys ($agents));
	unset ($count["total"]);
	arsort ($count, SORT_NUMERIC);
	$count = array_slice ($count, 0, 8, true);
	
	foreach ($count as $agent_id => $value) {
		$data[$agents[$agent_id]]['g'] = $value;
	}
	
	if($config["fixed_graph"] == false){
		$water_mark = array('file' =>
			$config['homedir'] . "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
	}
	
	return hbar_graph($config['flash_charts'], $data, $width, $height, array(),
		$legend, "", "", true, "", $water_mark,
		$config['fontpath'], $config['font_size'], false);
}

/**
 * Print a horizontal bar graph with modules data of agents
 * 
 * @param integer height graph height
 * @param integer width graph width
 */
function graph_db_agentes_modulos($width, $height) {
	global $config;
	global $graphic_type;
	
	
	$data = array ();
	
	switch ($config['dbtype']) {
		case "mysql":
		case "postgresql":
			$modules = db_get_all_rows_sql ('
				SELECT COUNT(id_agente_modulo), id_agente
				FROM tagente_modulo
				WHERE delete_pending = 0
				GROUP BY id_agente
				ORDER BY 1 DESC LIMIT 10');
			break;
		case "oracle":
			$modules = db_get_all_rows_sql ('
				SELECT COUNT(id_agente_modulo), id_agente
				FROM tagente_modulo
				WHERE rownum <= 10
				AND delete_pending = 0
				GROUP BY id_agente
				ORDER BY 1 DESC');
			break;
	}
	if ($modules === false)
		$modules = array ();
	
	$data = array();
	foreach ($modules as $module) {
		$agent_name = agents_get_name ($module['id_agente'], "none");
		
		if (empty($agent_name)) {
			continue;
		}
		switch ($config['dbtype']) {
			case "mysql":
			case "postgresql":
				$data[$agent_name]['g'] = $module['COUNT(id_agente_modulo)'];
				break;
			case "oracle":
				$data[$agent_name]['g'] = $module['count(id_agente_modulo)'];
				break;
		}
	}
	
	if($config["fixed_graph"] == false){
		$water_mark = array('file' =>
			$config['homedir'] . "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
	}
	
	return hbar_graph($config['flash_charts'],
		$data, $width, $height, array(),
		array(), "", "", true, "",
		$water_mark,
		$config['fontpath'], $config['font_size'], false);
}

/**
 * Print a pie graph with users activity in a period of time
 * 
 * @param integer width pie graph width
 * @param integer height pie graph height
 * @param integer period time period
 */
function graphic_user_activity ($width = 350, $height = 230) {
	global $config;
	global $graphic_type;
	
	$data = array ();
	$max_items = 5;
	switch ($config['dbtype']) {
		case "mysql":
		case "postgresql":
			$sql = sprintf ('SELECT COUNT(id_usuario) n_incidents, id_usuario
				FROM tsesion
				GROUP BY id_usuario
				ORDER BY 1 DESC LIMIT %d', $max_items);
			break;
		case "oracle":
			$sql = sprintf ('SELECT COUNT(id_usuario) n_incidents, id_usuario
				FROM tsesion 
				WHERE rownum <= %d
				GROUP BY id_usuario
				ORDER BY 1 DESC', $max_items);
			break;
	}
	$logins = db_get_all_rows_sql ($sql);
	
	if ($logins == false) {
		$logins = array();
	}
	foreach ($logins as $login) {
		$data[$login['id_usuario']] = $login['n_incidents'];
	}
	
	if($config["fixed_graph"] == false){
		$water_mark = array('file' =>
			$config['homedir'] . "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
	}
	
	return pie3d_graph($config['flash_charts'], $data, $width, $height,
		__('Other'), '', $water_mark,
		$config['fontpath'], $config['font_size']);
}

/**
 * Print a pie graph with priodity incident
 */
function grafico_incidente_prioridad () {
	global $config;
	global $graphic_type;
	
	$data_tmp = array (0, 0, 0, 0, 0, 0);
	$sql = 'SELECT COUNT(id_incidencia) n_incidents, prioridad
		FROM tincidencia
		GROUP BY prioridad
		ORDER BY 2 DESC';
	$incidents = db_get_all_rows_sql ($sql);
	
	if ($incidents == false) {
		$incidents = array();
	}
	foreach ($incidents as $incident) {
		if ($incident['prioridad'] < 5)
			$data_tmp[$incident['prioridad']] = $incident['n_incidents'];
		else
			$data_tmp[5] += $incident['n_incidents'];
	}
	$data = array (__('Informative') => $data_tmp[0],
		__('Low') => $data_tmp[1],
		__('Medium') => $data_tmp[2],
		__('Serious') => $data_tmp[3],
		__('Very serious') => $data_tmp[4],
		__('Maintenance') => $data_tmp[5]);
	
		if($config["fixed_graph"] == false){
			$water_mark = array('file' =>
				$config['homedir'] . "/images/logo_vertical_water.png",
				'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
		}
	
	return pie3d_graph($config['flash_charts'], $data, 320, 200,
		__('Other'), '', $water_mark,
		$config['fontpath'], $config['font_size']);
}

/**
 * Print a pie graph with incidents data
 */
function graph_incidents_status () {
	global $config;
	global $graphic_type;
	$data = array (0, 0, 0, 0);
	
	$data = array ();
	$data[__('Open incident')] = 0;
	$data[__('Closed incident')] = 0;
	$data[__('Outdated')] = 0;
	$data[__('Invalid')] = 0;
	
	$incidents = db_get_all_rows_filter ('tincidencia',
		array ('estado' => array (0, 2, 3, 13)),
		array ('estado'));
	if ($incidents === false)
		$incidents = array ();
	foreach ($incidents as $incident) {
		if ($incident["estado"] == 0)
			$data[__("Open incident")]++;
		if ($incident["estado"] == 2)
			$data[__("Closed incident")]++;
		if ($incident["estado"] == 3)
			$data[__("Outdated")]++;
		if ($incident["estado"] == 13)
			$data[__("Invalid")]++;
	}
	
	if($config["fixed_graph"] == false){
		$water_mark = array('file' =>
			$config['homedir'] . "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
	}
	
	return pie3d_graph($config['flash_charts'], $data, 370, 180,
		__('Other'), '', $water_mark,
		$config['fontpath'], $config['font_size']);
}

/**
 * Print a pie graph with incident data by group
 */
function graphic_incident_group () {
	global $config;
	global $graphic_type;
	
	$data = array ();
	$max_items = 5;
	switch ($config["dbtype"]) {
		case 'mysql':
			$sql = sprintf ('SELECT COUNT(id_incidencia) n_incidents, nombre
				FROM tincidencia,tgrupo
				WHERE tgrupo.id_grupo = tincidencia.id_grupo
				GROUP BY tgrupo.id_grupo, nombre ORDER BY 1 DESC LIMIT %d',
				$max_items);
			break;
		case 'oracle':
			$sql = sprintf ('SELECT COUNT(id_incidencia) n_incidents, nombre
				FROM tincidencia,tgrupo
				WHERE tgrupo.id_grupo = tincidencia.id_grupo
				AND rownum <= %d
				GROUP BY tgrupo.id_grupo, nombre ORDER BY 1 DESC',
				$max_items);
			break;
	}
	$incidents = db_get_all_rows_sql ($sql);
	
	$sql = sprintf ('SELECT COUNT(id_incidencia) n_incidents
		FROM tincidencia
		WHERE tincidencia.id_grupo = 0');
	
	$incidents_all = db_get_value_sql($sql);
	
	if ($incidents == false) {
		$incidents = array();
	}
	foreach ($incidents as $incident) {
		$data[$incident['nombre']] = $incident['n_incidents'];
	}
	
	if ($incidents_all > 0) {
		$data[__('All')] = $incidents_all;
	}
	
	if($config["fixed_graph"] == false){
		$water_mark = array('file' =>
			$config['homedir'] . "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
	}
	
	return pie3d_graph($config['flash_charts'], $data, 320, 200,
		__('Other'), '', $water_mark,
		$config['fontpath'], $config['font_size']);
}

/**
 * Print a graph with access data of agents
 * 
 * @param integer id_agent Agent ID
 * @param integer width pie graph width
 * @param integer height pie graph height
 * @param integer period time period
 */
function graphic_incident_user () {
	global $config;
	global $graphic_type;
	
	$data = array ();
	$max_items = 5;
	switch ($config["dbtype"]) {
		case 'mysql':
			$sql = sprintf ('SELECT COUNT(id_incidencia) n_incidents, id_usuario
				FROM tincidencia
				GROUP BY id_usuario
				ORDER BY 1 DESC LIMIT %d', $max_items);
			break;
		case 'oracle':
			$sql = sprintf ('SELECT COUNT(id_incidencia) n_incidents, id_usuario
				FROM tincidencia
				WHERE rownum <= %d
				GROUP BY id_usuario
				ORDER BY 1 DESC', $max_items);
			break;
	}
	$incidents = db_get_all_rows_sql ($sql);
	
	if ($incidents == false) {
		$incidents = array();
	}
	foreach ($incidents as $incident) {
		if ($incident['id_usuario'] == false) {
			$name = __('System');
		}
		else {
			$name = $incident['id_usuario'];
		}
		
		$data[$name] = $incident['n_incidents'];
	}
	
	if($config["fixed_graph"] == false){
		$water_mark = array('file' =>
			$config['homedir'] . "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
	}
	
	return pie3d_graph($config['flash_charts'], $data, 320, 200,
		__('Other'), '', $water_mark,
		$config['fontpath'], $config['font_size']);
}

/**
 * Print a pie graph with access data of incidents source
 * 
 * @param integer width pie graph width
 * @param integer height pie graph height
 */
function graphic_incident_source($width = 320, $height = 200) {
	global $config;
	global $graphic_type;
	
	$data = array ();
	$max_items = 5;
	
	switch ($config["dbtype"]) {
		case "mysql":
			$sql = sprintf ('SELECT COUNT(id_incidencia) n_incident, origen 
				FROM tincidencia
				GROUP BY `origen`
				ORDER BY 1 DESC LIMIT %d', $max_items);
			break;
		case "postgresql":
			$sql = sprintf ('SELECT COUNT(id_incidencia) n_incident, origen 
				FROM tincidencia
				GROUP BY "origen"
				ORDER BY 1 DESC LIMIT %d', $max_items);
			break;
		case "oracle":
			$sql = sprintf ('SELECT COUNT(id_incidencia) n_incident, origen 
				FROM tincidencia
				WHERE rownum <= %d
				GROUP BY origen
				ORDER BY 1 DESC', $max_items);
			break;
	}
	$origins = db_get_all_rows_sql ($sql);
	
	if ($origins == false) {
		$origins = array();
	}
	foreach ($origins as $origin) {
		$data[$origin['origen']] = $origin['n_incident'];
	}
	
	if($config["fixed_graph"] == false){
		$water_mark = array('file' =>
			$config['homedir'] . "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
	}
	
	return pie3d_graph($config['flash_charts'], $data, $width, $height,
		__('Other'), '', $water_mark,
		$config['fontpath'], $config['font_size']);
}

function graph_events_validated($width = 300, $height = 200, $url = "", $meta = false, $history = false) {
	global $config;
	global $graphic_type;
	
	$data_graph = events_get_count_events_validated(
		array('id_group' => array_keys(users_get_groups())));
	
	$colors = array();
	foreach ($data_graph as $k => $v) {
		if ($k == __('Validated')) {
			$colors[$k] = COL_NORMAL;
		}
		else {
			$colors[$k] = COL_CRITICAL;
		}
	}
	
	if($config["fixed_graph"] == false){
		$water_mark = array('file' =>
			$config['homedir'] . "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
	}
	
	echo pie3d_graph(
		true, $data_graph, $width, $height, __("other"), "",
		$water_mark,
		$config['fontpath'], $config['font_size'], 1, false, $colors);
}

/**
 * Print a pie graph with events data of group
 * 
 * @param integer width pie graph width
 * @param integer height pie graph height
 * @param string url
 * @param bool if the graph required is or not for metaconsole
 * @param bool if the graph required is or not for history table
 */
function grafico_eventos_grupo ($width = 300, $height = 200, $url = "", $meta = false, $history = false, $noWaterMark = true) {
	global $config;
	global $graphic_type;
	
	//It was urlencoded, so we urldecode it
	$url = html_entity_decode (rawurldecode ($url), ENT_QUOTES);
	$data = array ();
	$loop = 0;
	define ('NUM_PIECES_PIE', 6);
	
	
	//Hotfix for the id_agente_modulo
	$url = str_replace(
		'SELECT id_agente_modulo', 'SELECT_id_agente_modulo', $url);
	
	
	$badstrings = array (";",
		"SELECT ",
		"DELETE ",
		"UPDATE ",
		"INSERT ",
		"EXEC");
	//remove bad strings from the query so queries like ; DELETE FROM  don't pass
	$url = str_ireplace ($badstrings, "", $url);
	
	
	//Hotfix for the id_agente_modulo
	$url = str_replace(
		'SELECT_id_agente_modulo', 'SELECT id_agente_modulo', $url);
	
	
	// Choose the table where search if metaconsole or not
	if ($meta) {
		if ($history) {
			$event_table = 'tmetaconsole_event_history';
		}
		else {
			$event_table = 'tmetaconsole_event';
		}
		$field_extra = ', agent_name';
		$groupby_extra = ', server_id';
	}
	else {
		$event_table = 'tevento';
		$field_extra = '';
		$groupby_extra = '';
	}
	
	// Add tags condition to filter
	$tags_condition = tags_get_acl_tags($config['id_user'], 0, 'ER', 'event_condition', 'AND');
	
	//This will give the distinct id_agente, give the id_grupo that goes
	//with it and then the number of times it occured. GROUP BY statement
	//is required if both DISTINCT() and COUNT() are in the statement 
	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":
			$sql = sprintf ('SELECT DISTINCT(id_agente) AS id_agente,
					COUNT(id_agente) AS count'.$field_extra.'
				FROM '.$event_table.'
				WHERE 1=1 %s %s
				GROUP BY id_agente'.$groupby_extra.'
				ORDER BY count DESC LIMIT 8', $url, $tags_condition);
			break;
		case "oracle":
			$sql = sprintf ('SELECT DISTINCT(id_agente) AS id_agente,
					id_grupo, COUNT(id_agente) AS count'.$field_extra.'
				FROM '.$event_table.'
				WHERE rownum <= 8 %s %s
				GROUP BY id_agente, id_grupo'.$groupby_extra.'
				ORDER BY count DESC', $url, $tags_condition); 
			break;
	}
	
	$result = db_get_all_rows_sql ($sql, false, false);
	if ($result === false) {
		$result = array();
	}
	
	$system_events = 0;
	$other_events = 0;
	
	foreach ($result as $row) {
		$row["id_grupo"] = agents_get_agent_group ($row["id_agente"]);
		if (!check_acl ($config["id_user"], $row["id_grupo"], "ER") == 1)
			continue;
		
		if ($loop >= NUM_PIECES_PIE) {
			$other_events += $row["count"];
		}
		else {
			if ($row["id_agente"] == 0) {
				$system_events += $row["count"];
			}
			else {
				if ($meta) {
					$name = mb_substr (io_safe_output($row['agent_name']), 0, 14)." (".$row["count"].")";
				}
				else {
					$name = mb_substr (agents_get_name ($row["id_agente"], "lower"), 0, 14)." (".$row["count"].")";
				}
				$data[$name] = $row["count"];
			}
		}
		$loop++;
	}
	
	if ($system_events > 0) {
		$name = __('SYSTEM')." (".$system_events.")";
		$data[$name] = $system_events;
	}
	
	/*
	if ($other_events > 0) {
		$name = __('Other')." (".$other_events.")";
		$data[$name] = $other_events;
	}
	*/
	
	// Sort the data
	arsort($data);
	if ($noWaterMark) {
		$water_mark = array('file' => $config['homedir'] .  "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
	}
	else
	{
		$water_mark = array();
	}
	
	return pie3d_graph($config['flash_charts'], $data, $width, $height,
		__('Other'), '', $water_mark,
		$config['fontpath'], $config['font_size'], 1, 'bottom');
}

function grafico_eventos_agente ($width = 300, $height = 200, $result = false, $meta = false, $history = false) {
	global $config;
	global $graphic_type;
	
	//It was urlencoded, so we urldecode it
	//$url = html_entity_decode (rawurldecode ($url), ENT_QUOTES);
	$data = array ();
	$loop = 0;
	
	if ($result === false) {
		$result = array();
	}
	
	$system_events = 0;
	$other_events = 0;
	$total = array();
	$i = 0;
	
	foreach ($result as $row) {
		if ($meta) {
			$count[] = $row["agent_name"];
		}
		else {
			if ($row["id_agente"] == 0) {
				$count[] = __('SYSTEM');
			}
			else
				$count[] = agents_get_name ($row["id_agente"]) ;
		}
		
	}
	
	$total = array_count_values($count);
	
	foreach ($total as $key => $total) {
		if ($meta) {
			$name = $key." (".$total.")";
		}
		else {
			$name = $key." (".$total.")";
		}
		$data[$name] = $total;
	}
	
	/*
	if ($other_events > 0) {
		$name = __('Other')." (".$other_events.")";
		$data[$name] = $other_events;
	}
	*/
	
	// Sort the data
	arsort($data);
	if($config["fixed_graph"] == false){
		$water_mark = array('file' =>
			$config['homedir'] . "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
	}
	
	return pie3d_graph($config['flash_charts'], $data, $width, $height,
		__('Others'), '', $water_mark,
		$config['fontpath'], $config['font_size'], 1, 'bottom');
}

/**
 * Print a pie graph with events data in 320x200 size
 * 
 * @param string filter Filter for query in DB
 */
function grafico_eventos_total($filter = "", $width = 320, $height = 200, $noWaterMark = true) {
	global $config;
	global $graphic_type;
	
	$filter = str_replace  ( "\\" , "", $filter);
	
	// Add tags condition to filter
	$tags_condition = tags_get_acl_tags($config['id_user'], 0, 'ER', 'event_condition', 'AND');
	$filter .= $tags_condition;
	
	$data = array ();
	$legend = array ();
	$total = 0;
	
	$sql = "SELECT criticity, COUNT(id_evento) events
		FROM tevento
		GROUP BY criticity ORDER BY events DESC";
	
	$criticities = db_get_all_rows_sql ($sql, false, false);
	
	if (empty($criticities)) {
		$criticities = array();
		$colors = array();
	}
	
	foreach ($criticities as $cr) {
		switch ($cr['criticity']) {
			case EVENT_CRIT_MAINTENANCE:
				$data[__('Maintenance')] = $cr['events'];
				$colors[__('Maintenance')] = COL_MAINTENANCE;
				break;
			case EVENT_CRIT_INFORMATIONAL:
				$data[__('Informational')] = $cr['events'];
				$colors[__('Informational')] = COL_INFORMATIONAL;
				break;
			case EVENT_CRIT_NORMAL:
				$data[__('Normal')] = $cr['events'];
				$colors[__('Normal')] = COL_NORMAL;
				break;
			case EVENT_CRIT_MINOR:
				$data[__('Minor')] = $cr['events'];
				$colors[__('Minor')] = COL_MINOR;
				break;
			case EVENT_CRIT_WARNING:
				$data[__('Warning')] = $cr['events'];
				$colors[__('Warning')] = COL_WARNING;
				break;
			case EVENT_CRIT_MAJOR:
				$data[__('Major')] = $cr['events'];
				$colors[__('Major')] = COL_MAJOR;
				break;
			case EVENT_CRIT_CRITICAL:
				$data[__('Critical')] = $cr['events'];
				$colors[__('Critical')] = COL_CRITICAL;
				break;
		}
	}
	if ($noWaterMark) {
		$water_mark = array(
			'file' => $config['homedir'] . "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("/images/logo_vertical_water.png", false, false, false));
	}
	else {
		$water_mark = array();
	}
	
	return pie3d_graph($config['flash_charts'], $data, $width, $height,
		__('Other'), '', $water_mark,
		$config['fontpath'], $config['font_size'], 1, 'bottom', $colors);
}

/**
 * Print a pie graph with events data of users
 * 
 * @param integer height pie graph height
 * @param integer period time period
 */
function grafico_eventos_usuario ($width, $height) {
	global $config;
	global $graphic_type;
	
	$data = array ();
	$max_items = 5;
	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":
			$sql = sprintf ('SELECT COUNT(id_evento) events, id_usuario
				FROM tevento
				GROUP BY id_usuario
				ORDER BY 1 DESC LIMIT %d', $max_items);
			break;
		case "oracle":
			$sql = sprintf ('SELECT *
				FROM (SELECT COUNT(id_evento) events, id_usuario
					FROM tevento
					GROUP BY id_usuario
					ORDER BY 1 DESC)
				WHERE rownum <= %d', $max_items);
			break;
	}
	$events = db_get_all_rows_sql ($sql);
	
	if ($events === false) {
		$events = array();
	}
	
	foreach($events as $event) {
		if ($event['id_usuario'] == '0') {
			$data[__('System')] = $event['events'];
		}
		elseif ($event['id_usuario'] == '') {
			$data[__('System')] = $event['events'];
		}
		else {
			$data[$event['id_usuario']] = $event['events'];
		}
	}
	
	$water_mark = array(
		'file' => $config['homedir'] .  "/images/logo_vertical_water.png",
		'url' => ui_get_full_url("/images/logo_vertical_water.png", false, false, false));
	
	return pie3d_graph($config['flash_charts'], $data, $width, $height,
		__('Other'), '', $water_mark,
		$config['fontpath'], $config['font_size']);
}

/**
 * Print a custom SQL-defined graph 
 * 
 * @param integer ID of report content, used to get SQL code to get information for graph
 * @param integer height graph height
 * @param integer width graph width
 * @param integer Graph type 1 vbar, 2 hbar, 3 pie
 */
function graph_custom_sql_graph ($id, $width, $height,
	$type = 'sql_graph_vbar', $only_image = false, $homeurl = '',
	$ttl = 1) {
	
	global $config;
	
	$report_content = db_get_row ('treport_content', 'id_rc', $id);
	if ($report_content["external_source"] != "") {
		$sql = io_safe_output ($report_content["external_source"]);
	}
	else {
		$sql = db_get_row('treport_custom_sql', 'id', $report_content["treport_custom_sql_id"]);
		$sql = io_safe_output($sql['sql']);
	}
	
	if (($config['metaconsole'] == 1) && defined('METACONSOLE')) {
		$metaconsole_connection = enterprise_hook('metaconsole_get_connection', array($report_content['server_name']));
		
		if ($metaconsole_connection === false) {
			return false;
		}
		
		if (enterprise_hook('metaconsole_load_external_db', array($metaconsole_connection)) != NOERR) {
			//ui_print_error_message ("Error connecting to ".$server_name);
			return false;
		}
	}
	
	
	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":
			break;
		case "oracle":
			$sql = str_replace(";", "", $sql);
			break;
	}
	
	
	$data_result = db_get_all_rows_sql ($sql);
	
	if (($config['metaconsole'] == 1) && defined('METACONSOLE'))
		enterprise_hook('metaconsole_restore_db');
	
	if ($data_result === false)
		$data_result = array ();
	
	$data = array ();
	
	$count = 0;
	foreach ($data_result as $data_item) {
		$count++;
		$value = 0;
		if (!empty($data_item["value"])) {
			$value = $data_item["value"];
		}
		$label = __('Data');
		if (!empty($data_item["label"])) {
			$label = $data_item["label"];
		}
		switch ($type) {
			case 'sql_graph_vbar': // vertical bar
			case 'sql_graph_hbar': // horizontal bar
				$data[$label]['g'] = $value;
				break;
			case 'sql_graph_pie': // Pie
				$data[$label] = $value;
				break;
		}
	}
	
	$flash_charts = $config['flash_charts'];
	
	
	if ($only_image) {
		$flash_charts = false;
	}
	
	if($config["fixed_graph"] == false){
		$water_mark = array('file' =>
			$config['homedir'] . "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
	}
	
	switch ($type) {
		case 'sql_graph_vbar': // vertical bar
			return vbar_graph($flash_charts, $data, $width, $height, array(),
				array(), "", "", $homeurl, $water_mark,
				$config['fontpath'], $config['font_size'], false, $ttl);
			break;
		case 'sql_graph_hbar': // horizontal bar
			return hbar_graph($flash_charts, $data, $width, $height, array(),
				array(), "", "", true, $homeurl, $water_mark,
				$config['fontpath'], $config['font_size'], false, $ttl);
			break;
		case 'sql_graph_pie': // Pie
			return pie3d_graph($flash_charts, $data, $width, $height, __("other"), $homeurl,
				$water_mark, $config['fontpath'], '', $ttl);
			break;
	}
}

/**
 * Print a static graph with event data of agents
 * 
 * @param integer id_agent Agent ID
 * @param integer width pie graph width
 * @param integer height pie graph height
 * @param integer period time period
 * @param string homeurl
 * @param bool return or echo the result
 */
function graph_graphic_agentevents ($id_agent, $width, $height, $period = 0, $homeurl, $return = false) {
	global $config;
	global $graphic_type;
	
	
	$data = array ();
	
	$resolution = $config['graph_res'] * ($period * 2 / $width); // Number of "slices" we want in graph
	
	$interval = (int) ($period / $resolution);
	$date = get_system_time ();
	$datelimit = $date - $period;
	$periodtime = floor ($period / $interval);
	$time = array ();
	$data = array ();
	$legend = array();
	$full_legend = array();
	
	$cont = 0;
	for ($i = 0; $i < $interval; $i++) {
		$bottom = $datelimit + ($periodtime * $i);
		if (! $graphic_type) {
			if ($config['flash_charts']) {
				$name = date('H:i', $bottom);
			}
			else {
				$name = date('H\h', $bottom);
			}
		}
		else {
			$name = $bottom;
		}
		
		// Show less values in legend
		if ($cont == 0 or $cont % 2)
			$legend[$cont] = $name;
		
		$full_legend[$cont] = $name;
		
		$top = $datelimit + ($periodtime * ($i + 1));
		$event = db_get_row_filter ('tevento',
			array ('id_agente' => $id_agent,
				'utimestamp > '.$bottom,
				'utimestamp < '.$top), 'criticity, utimestamp');
		
		if (!empty($event['utimestamp'])) {
			$data[$cont]['utimestamp'] = $periodtime;
			switch ($event['criticity']) {
				case EVENT_CRIT_WARNING:
					$data[$cont]['data'] = 2;
					break;
				case EVENT_CRIT_CRITICAL:
					$data[$cont]['data'] = 3;
					break;
				default:
					$data[$cont]['data'] = 1;
					break;
			}
		}
		else {
			$data[$cont]['utimestamp'] = $periodtime;
			$data[$cont]['data'] = 1;
		}
		$cont++;
	}
	
	$colors = array(1 => COL_NORMAL, 2 => COL_WARNING, 3 => COL_CRITICAL, 4 => COL_UNKNOWN);
	
	// Draw slicebar graph
	if ($config['flash_charts']) {
		$out = flot_slicesbar_graph($data, $period, $width, $height, $full_legend, $colors, $config['fontpath'], $config['round_corner'], $homeurl, '', '', false, $id_agent);
	}
	else {
		$out = slicesbar_graph($data, $period, $width, $height, $colors, $config['fontpath'], $config['round_corner'], $homeurl);
		
		// Draw legend
		$out .=  "<br>";
		$out .=  "&nbsp;";
		foreach ($legend as $hour) {
			$out .=  "<span style='font-size: 6pt'>" . $hour . "</span>";
			$out .=  "&nbsp;";
		}
	}
	
	if ($return) {
		return $out;
	}
	else {
		echo $out;
	}
}

// Prints an error image
function fs_error_image ($width = 300, $height = 110) {
	global $config;
	
	return graph_nodata_image($width, $height, 'area');
}

function grafico_modulo_boolean_data ($agent_module_id, $period, $show_events,
	$unit_name, $show_alerts, $avg_only = 0,
	$date = 0, $series_suffix = '', $series_suffix_str = '', $show_unknown = false) {
	
	global $config;
	global $chart;
	global $color;
	global $legend;
	global $long_index;
	global $series_type;
	global $chart_extra_data;
	
	
	$chart = array();
	$color = array();
	$legend = array();
	$long_index = array();
	$start_unknown = false;
	
	// Set variables
	if ($date == 0) $date = get_system_time();
	$datelimit = $date - $period;
	$search_in_history_db = db_search_in_history_db($datelimit);
	$resolution = $config['graph_res'] * 50; //Number of points of the graph
	$interval = (int) ($period / $resolution);
	$agent_name = modules_get_agentmodule_agent_name ($agent_module_id);
	$agent_id = agents_get_agent_id ($agent_name);
	$module_name = modules_get_agentmodule_name ($agent_module_id);
	$id_module_type = modules_get_agentmodule_type ($agent_module_id);
	$module_type = modules_get_moduletype_name ($id_module_type);
	$uncompressed_module = is_module_uncompressed ($module_type);
	if ($uncompressed_module) {
		$avg_only = 1;
	}
	$search_in_history_db = db_search_in_history_db($datelimit);
	
	// Get event data (contains alert data too)
	if ($show_unknown == 1 || $show_events == 1 || $show_alerts == 1) {
		$events = db_get_all_rows_filter('tevento',
			array ('id_agentmodule' => $agent_module_id,
				"utimestamp > $datelimit",
				"utimestamp < $date",
				'order' => 'utimestamp ASC'),
			array ('evento', 'utimestamp', 'event_type', 'id_evento'));
		
		// Get the last event after inverval to know if graph start on unknown
		$prev_event = db_get_row_filter ('tevento',
			array ('id_agentmodule' => $agent_module_id,
				"utimestamp <= $datelimit",
				'order' => 'utimestamp DESC'));
		if (isset($prev_event['event_type']) && $prev_event['event_type'] == 'going_unknown') {
			$start_unknown = true;
		}
		
		if ($events === false) {
			$events = array ();
		}
	}
	
	// Get module data
	$data = db_get_all_rows_filter ('tagente_datos',
		array ('id_agente_modulo' => $agent_module_id,
			"utimestamp > $datelimit",
			"utimestamp < $date",
			'order' => 'utimestamp ASC'),
		array ('datos', 'utimestamp'), 'AND', $search_in_history_db);
	if ($data === false) {
		$data = array ();
	}
	
	// Uncompressed module data
	if ($uncompressed_module) {
		$min_necessary = 1;
	
	// Compressed module data
	}
	else {
		// Get previous data
		$previous_data = modules_get_previous_data ($agent_module_id, $datelimit);
		if ($previous_data !== false) {
			$previous_data['utimestamp'] = $datelimit;
			array_unshift ($data, $previous_data);
		}
		
		// Get next data
		$nextData = modules_get_next_data ($agent_module_id, $date);
		if ($nextData !== false) {
			array_push ($data, $nextData);
		}
		else if (count ($data) > 0) {
			// Propagate the last known data to the end of the interval
			$nextData = array_pop ($data);
			array_push ($data, $nextData);
			$nextData['utimestamp'] = $date;
			array_push ($data, $nextData);
		}
		
		$min_necessary = 2;
	}
	
	// Check available data
	if (count ($data) < $min_necessary) {
		if (!$graphic_type) {
			return fs_error_image ();
		}
		graphic_error ();
	}
	
	if (empty($unit_name)) {
		$unit = modules_get_unit($agent_module_id);
	}
	else
		$unit = $unit_name;
	
	// Data iterator
	$j = 0;
	
	// Event iterator
	$k = 0;
	
	// Set initial conditions
	if ($data[0]['utimestamp'] == $datelimit) {
		$previous_data = $data[0]['datos'];
		$j++;
	}
	else {
		$previous_data = 0;
	}
	
	$max_value = 0;
	
	// Calculate chart data
	$last_known = $previous_data;
	for ($i = 0; $i <= $resolution; $i++) {
		$timestamp = $datelimit + ($interval * $i);
		
		$zero = 0;
		$total = 0;
		$count = 0;
		
		// Read data that falls in the current interval
		while (isset ($data[$j]) &&
			$data[$j]['utimestamp'] >= $timestamp &&
			$data[$j]['utimestamp'] <= ($timestamp + $interval)) {
			if ($data[$j]['datos'] == 0) {
				$zero = 1;
			}
			else {
				$total += $data[$j]['datos'];
				$count++;
			}
			
			$last_known = $data[$j]['datos'];
			$j++;
		}
		
		// Average
		if ($count > 0) {
			$total /= $count;
		}
		
		// Read events and alerts that fall in the current interval
		$event_value = 0;
		$alert_value = 0;
		$unknown_value = 0;
		$is_unknown = false;
		// Is the first point of a unknown interval
		$first_unknown = false;
		
		$event_ids = array();
		$alert_ids = array();
		while (isset ($events[$k]) &&
			$events[$k]['utimestamp'] >= $timestamp &&
			$events[$k]['utimestamp'] < ($timestamp + $interval)) {
			if ($show_events == 1) {
				$event_value++;
				$event_ids[] = $events[$k]['id_evento'];
			}
			if ($show_alerts == 1 && substr ($events[$k]['event_type'], 0, 5) == 'alert') {
				$alert_value++;
				$alert_ids[] = $events[$k]['id_evento'];
			}
			if ($show_unknown) {
				if ($events[$k]['event_type'] == 'going_unknown') {
					if ($is_unknown == false) {
						$first_unknown = true;
					}
					$is_unknown = true;
				}
				else if (substr ($events[$k]['event_type'], 0, 5) == 'going') {
					$is_unknown = false;
				}
			}
			$k++;
		}
		
		// In some cases, can be marked as known because a recovery event
		// was found in same interval. For this cases first_unknown is 
		// checked too
		if ($is_unknown || $first_unknown) {
			$unknown_value++;
		}
		
		// Set the title and time format
		if ($period <= SECONDS_6HOURS) {
			$time_format = 'H:i:s';
		}
		elseif ($period < SECONDS_1DAY) {
			$time_format = 'H:i';
		}
		elseif ($period < SECONDS_15DAYS) {
			$time_format = 'M d H:i';
		}
		elseif ($period < SECONDS_1MONTH) {
			$time_format = 'M d H\h';
		} 
		else {
			$time_format = 'M d H\h';
		}
		
		$timestamp_short = date($time_format, $timestamp);
		$long_index[$timestamp_short] = date(
			html_entity_decode($config['date_format'], ENT_QUOTES, "UTF-8"), $timestamp);
		$timestamp = $timestamp_short;
		/////////////////////////////////////////////////////////////////
		
		if ($total > $max_value) {
			$max_value = $total;
		}
		
		if ($show_events) {
			if (!isset($chart[$timestamp]['event'.$series_suffix])) {
				$chart[$timestamp]['event'.$series_suffix] = 0;
			}
			
			$chart[$timestamp]['event'.$series_suffix] += $event_value;
			$series_type['event'.$series_suffix] = 'points';
		}
		if ($show_alerts) {
			if (!isset($chart[$timestamp]['alert'.$series_suffix])) {
				$chart[$timestamp]['alert'.$series_suffix] = 0;
			}
			
			$chart[$timestamp]['alert'.$series_suffix] += $alert_value;
			$series_type['alert'.$series_suffix] = 'points';
		}
		
		//The order filling the array is very important to get the same colors
		//in legends and graphs!!!
		//Boolean graph doesn't have max!!!
		/*if (!$avg_only) {
			$chart[$timestamp]['max'.$series_suffix] = 0;
		}*/
		
		// Data and zeroes (draw a step)
		if ($zero == 1 && $count > 0) {
			
			//New code set 0 if there is a 0
			//Please check the incident #665
			//http://192.168.50.2/integria/index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id=665
			$chart[$timestamp]['sum'.$series_suffix] = 0;
		}
		else if ($zero == 1) { // Just zeros
			$chart[$timestamp]['sum'.$series_suffix] = 0;
		}
		else if ($count > 0) { // No zeros
			$chart[$timestamp]['sum'.$series_suffix] = $total;
		}
		else { // Compressed data
			if ($uncompressed_module || ($timestamp > time ()) || $is_unknown) {
				$chart[$timestamp]['sum'.$series_suffix] = 0;
			}
			else {
				$chart[$timestamp]['sum'.$series_suffix] = $last_known;
			}
		}
		
		if ($show_unknown) {
			if (!isset($chart[$timestamp]['unknown'.$series_suffix])) {
				$chart[$timestamp]['unknown'.$series_suffix] = 0;
			}
			
			$chart[$timestamp]['unknown'.$series_suffix] = $unknown_value;
			$series_type['unknown'.$series_suffix] = 'area';
		}
		
		$series_type['sum' . $series_suffix] = 'boolean';
		
		//Boolean graph doesn't have min!!!
		/*if (!$avg_only) {
			$chart[$timestamp]['min'.$series_suffix] = 0;
		}*/
		
		if (!empty($event_ids)) {
			$chart_extra_data[count($chart)-1]['events'] = implode(',',$event_ids);
		}
		if (!empty($alert_ids)) {
			$chart_extra_data[count($chart)-1]['alerts'] = implode(',',$alert_ids);
		}
		
	}
	
	// Get min, max and avg (less efficient but centralized for all modules and reports)
	$graph_stats = get_statwin_graph_statistics($chart, $series_suffix);
	
	// Fix event and alert scale
	foreach ($chart as $timestamp => $chart_data) {
		if ($show_events) {
			if ($chart_data['event'.$series_suffix] > 0) {
				$chart[$timestamp]['event'.$series_suffix] = $max_value * 1.2;
			}
		}
		if ($show_alerts) {
			if ($chart_data['alert'.$series_suffix] > 0) {
				$chart[$timestamp]['alert'.$series_suffix] = $max_value * 1.10;
			}
		}
		if ($show_unknown) {
			if ($chart_data['unknown'.$series_suffix] > 0) {
				$chart[$timestamp]['unknown'.$series_suffix] = $max_value * 1.05;
			}
		}
	}
	
	///////////////////////////////////////////////////
	// Set the title and time format
	if ($period <= SECONDS_6HOURS) {
		$time_format = 'H:i:s';
	}
	elseif ($period < SECONDS_1DAY) {
		$time_format = 'H:i';
	}
	elseif ($period < SECONDS_15DAYS) {
		$time_format = 'M d H:i';
	}
	elseif ($period < SECONDS_1MONTH) {
		$time_format = 'M d H\h';
	} 
	else {
		$time_format = 'M d H\h';
	}
	
	// Flash chart
	$caption = __('Max. Value').$series_suffix_str . ': ' . $graph_stats['sum']['max'] . '    ' . __('Avg. Value').$series_suffix_str . 
	': ' . $graph_stats['sum']['avg'] . '    ' . __('Min. Value').$series_suffix_str . ': ' . $graph_stats['sum']['min'] . '   ' . __('Units').$series_suffix_str . ': ' . $unit;
	
	/////////////////////////////////////////////////////////////////////////////////////////
	if ($show_events) {
		$legend['event'.$series_suffix] = __('Events').$series_suffix_str;
		$chart_extra_data['legend_events'] = $legend['event'.$series_suffix];
	}
	if ($show_alerts) {
		$legend['alert'.$series_suffix] = __('Alerts').$series_suffix_str;
		$chart_extra_data['legend_alerts'] = $legend['alert'.$series_suffix];
	}
	
	if (!$avg_only) {
		//Boolean graph doesn't have max!!!
		//$legend['max'.$series_suffix] = __('Max').$series_suffix_str .': '.__('Last').': '.$graph_stats['max']['last'].' '.$unit.' ; '.__('Avg').': '.$graph_stats['max']['avg'].' '.$unit.' ; '.__('Max').': '.$graph_stats['max']['max'].' '.$unit.' ; '.__('Min').': '.$graph_stats['max']['min'].' '.$unit;
		$legend['sum'.$series_suffix] = __('Avg').$series_suffix_str.': '.__('Last').': '.$graph_stats['sum']['last'].' '.$unit.' ; '.__('Avg').': '.$graph_stats['sum']['avg'].' '.$unit.' ; '.__('Max').': '.$graph_stats['sum']['max'].' '.$unit.' ; '.__('Min').': '.$graph_stats['sum']['min'].' '.$unit;
		// Boolean graph doesn't have min!!!
		// $legend['min'.$series_suffix] = __('Min').$series_suffix_str .': '.__('Last').': '.$graph_stats['min']['last'].' '.$unit.' ; '.__('Avg').': '.$graph_stats['min']['avg'].' '.$unit.' ; '.__('Max').': '.$graph_stats['min']['max'].' '.$unit.' ; '.__('Min').': '.$graph_stats['min']['min'].' '.$unit;
	}
	else {
		$legend['sum'.$series_suffix] = __('Avg').$series_suffix_str.': '.__('Last').': '.$graph_stats['sum']['last'].' '.$unit.' ; '.__('Avg').': '.$graph_stats['sum']['avg'].' '.$unit.' ; '.__('Max').': '.$graph_stats['sum']['max'].' '.$unit.' ; '.__('Min').': '.$graph_stats['sum']['min'].' '.$unit;
	
	}
	
	if ($show_unknown) {
		$legend['unknown'.$series_suffix] = __('Unknown').$series_suffix_str;
		$chart_extra_data['legend_unknown'] = $legend['unknown'.$series_suffix];
	}
	//$legend['baseline'.$series_suffix] = __('Baseline').$series_suffix_str;
	/////////////////////////////////////////////////////////////////////////////////////////
	if ($show_events) {
		$color['event'.$series_suffix] =
			array('border' => '#ff0000', 'color' => '#ff0000',
				'alpha' => CHART_DEFAULT_ALPHA);
	}
	if ($show_alerts) {
		$color['alert'.$series_suffix] =
			array('border' => '#ff7f00', 'color' => '#ff7f00',
				'alpha' => CHART_DEFAULT_ALPHA);
	}
	$color['max'.$series_suffix] =
		array('border' => '#000000', 'color' => $config['graph_color3'],
			'alpha' => CHART_DEFAULT_ALPHA);
	$color['sum'.$series_suffix] =
		array('border' => '#000000', 'color' => $config['graph_color2'],
			'alpha' => CHART_DEFAULT_ALPHA);
	$color['min'.$series_suffix] =
		array('border' => '#000000', 'color' => $config['graph_color1'],
			'alpha' => CHART_DEFAULT_ALPHA);
	if ($show_unknown) {
		$color['unknown'.$series_suffix] =
			array('border' => '#999999', 'color' => '#999999',
				'alpha' => CHART_DEFAULT_ALPHA);
	}
	//$color['baseline'.$series_suffix] = array('border' => null, 'color' => '#0097BD', 'alpha' => 10);
}

function grafico_modulo_boolean ($agent_module_id, $period, $show_events,
	$width, $height , $title, $unit_name, $show_alerts, $avg_only = 0, $pure=0,
	$date = 0, $only_image = false, $homeurl = '', $adapt_key = '', $compare = false, 
	$show_unknown = false, $menu = true) {
	
	global $config;
	global $graphic_type;
	
	$flash_chart = $config['flash_charts'];
	
	global $chart;
	global $color;
	global $color_prev;
	global $legend;
	global $long_index;
	global $series_type;
	global $chart_extra_data;
	
	if (empty($unit_name)) {
		$unit = modules_get_unit($agent_module_id);
	}
	else
		$unit = $unit_name;
	
	
	$series_suffix_str = '';
	if ($compare !== false) {
		$series_suffix = '2';
		$series_suffix_str = ' (' . __('Previous') . ')';
		// Build the data of the previous period
		grafico_modulo_boolean_data ($agent_module_id, $period, $show_events,
			$unit_name, $show_alerts, $avg_only, $date-$period, $series_suffix, 
			$series_suffix_str, $show_unknown);
		switch ($compare) {
			case 'separated':
				// Store the chart calculated
				$chart_prev = $chart;
				$legend_prev = $legend;
				$long_index_prev = $long_index;
				$series_type_prev = $series_type;
				$chart_extra_data_prev = $chart_extra_data;
				$chart_extra_data = array();
				$color_prev = $color;
				break;
			case 'overlapped':
				// Store the chart calculated deleting index, because will be over the current period
				$chart_prev = array_values($chart);
				$legend_prev = $legend;
				$series_type_prev = $series_type;
				$color_prev = $color;
				foreach ($color_prev as $k => $col) {
					$color_prev[$k]['color'] = '#' . get_complementary_rgb($color_prev[$k]['color']);
				}
				break;
		}
	}
	
	grafico_modulo_boolean_data ($agent_module_id, $period, $show_events,
		$unit_name, $show_alerts, $avg_only, $date, '', '', $show_unknown);
	
	if ($compare === 'overlapped') {
		$i = 0;
		foreach($chart as $k => $v) {
			$chart[$k] = array_merge($v, $chart_prev[$i]);
			$i++;
		}
		
		$legend = array_merge($legend, $legend_prev);
		$color = array_merge($color, $color_prev);
	}
	
	if ($only_image) {
		$flash_chart = false;
	}
	
	$water_mark = array(
		'file' => $config['homedir'] .  "/images/logo_vertical_water.png",
		'url' => ui_get_full_url("/images/logo_vertical_water.png",
		false, false, false));
	
	if ($config['type_module_charts'] === 'area') {
		if ($compare === 'separated') {
			return area_graph($flash_chart, $chart, $width, $height/2, $color, $legend,
				$long_index, ui_get_full_url("images/image_problem.opaque.png", false, false, false),
				"", $unit, $homeurl, $water_mark,
				$config['fontpath'], $config['font_size'], $unit, 1, $series_type, 
				$chart_extra_data, 0, 0, $adapt_key, false, $series_suffix_str, $menu).
				'<br>'.
				area_graph($flash_chart, $chart_prev, $width, $height/2, $color_prev, $legend_prev,
				$long_index_prev, ui_get_full_url("images/image_problem.opaque.png", false, false, false),
				"", $unit, $homeurl, $water_mark,
				$config['fontpath'], $config['font_size'], $unit, 1, $series_type_prev, 
				$chart_extra_data_prev, 0, 0, $adapt_key, false, $series_suffix_str, $menu);
		}
		else {
			return area_graph($flash_chart, $chart, $width, $height, $color, $legend,
				$long_index, ui_get_full_url("images/image_problem.opaque.png", false, false, false),
				"", $unit, $homeurl, $water_mark,
				$config['fontpath'], $config['font_size'], $unit, 1, $series_type, 
				$chart_extra_data, 0, 0, $adapt_key, false, $series_suffix_str, $menu);
		}
	}
	elseif ($config['type_module_charts'] === 'line') {
		if ($compare === 'separated') {
			return
				line_graph($flash_chart, $chart, $width, $height/2, $color,
					$legend, $long_index,
					ui_get_full_url("images/image_problem.opaque.png", false, false, false),
					"", $unit, $water_mark, $config['fontpath'],
					$config['font_size'], $unit, $ttl, $homeurl, $backgroundColor).
				'<br>'.
				line_graph($flash_chart, $chart_prev, $width, $height/2, $color,
					$legend, $long_index,
					ui_get_full_url("images/image_problem.opaque.png", false, false, false),
					"", $unit, $water_mark, $config['fontpath'],
					$config['font_size'], $unit, $ttl, $homeurl, $backgroundColor);
		}
		else {
			// Color commented not to restrict serie colors
			return
				line_graph($flash_chart, $chart, $width, $height, $color,
					$legend, $long_index,
					ui_get_full_url("images/image_problem.opaque.png", false, false, false),
					"", $unit, $water_mark, $config['fontpath'],
					$config['font_size'], $unit, $ttl, $homeurl, $backgroundColor);
		}
	}
}


/**
 * Print an area graph with netflow aggregated
 */

function graph_netflow_aggregate_area ($data, $period, $width, $height, $unit = '', $ttl = 1, $only_image = false) {
	global $config;
	global $graphic_type;
	
	if (empty ($data)) {
		echo fs_error_image ();
		return;
	}
	
	
	if ($period <= SECONDS_6HOURS) {
		$chart_time_format = 'H:i:s';
	}
	elseif ($period < SECONDS_1DAY) {
		$chart_time_format = 'H:i';
	}
	elseif ($period < SECONDS_15DAYS) {
		$chart_time_format = 'M d H:i';
	}
	elseif ($period < SECONDS_1MONTH) {
		$chart_time_format = 'M d H\h';
	}
	else {
		$chart_time_format = 'M d H\h';
	}
	
	// Calculate source indexes
	$i = 0;
	$sources = array ();
	foreach ($data['sources'] as $source => $value) {
		$source_indexes[$source] = $i;
		$sources[$i] = $source;
		$i++;
	}
	
	// Add sources to chart
	$chart = array ();
	foreach ($data['data'] as $timestamp => $data) {
		$chart_date = date ($chart_time_format, $timestamp);
		$chart[$chart_date] = array ();
		foreach ($source_indexes as $source => $index) {
			$chart[$chart_date][$index] = 0;
		}
		foreach ($data as $source => $value) {
			$chart[$chart_date][$source_indexes[$source]] = $value;
		}
	}
	
	
	$flash_chart = $config['flash_charts'];
	if ($only_image) {
		$flash_chart = false;
	}
	
	if ($config['homeurl'] != '') {
		$homeurl = $config['homeurl'];
	}
	else {
		$homeurl = '';
	}
	
	if($config["fixed_graph"] == false){
		$water_mark = array('file' =>
			$config['homedir'] . "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
	}
	
	
	return area_graph($flash_chart, $chart, $width, $height, array (), $sources,
		array (), ui_get_full_url("images/image_problem.opaque.png", false, false, false),
		"", $unit, $homeurl,
		$config['homedir'] .  "/images/logo_vertical_water.png",
		$config['fontpath'], $config['font_size'], $unit, 2);
}



/**
 * Print an area graph with netflow total
 */
function graph_netflow_total_area ($data, $period, $width, $height, $unit = '', $ttl = 1, $only_image = false) {
	global $config;
	global $graphic_type;
	
	if (empty ($data)) {
		echo fs_error_image ();
		return;
	}
	
	if ($period <= SECONDS_6HOURS) {
		$chart_time_format = 'H:i:s';
	}
	elseif ($period < SECONDS_1DAY) {
		$chart_time_format = 'H:i';
	}
	elseif ($period < SECONDS_15DAYS) {
		$chart_time_format = 'M d H:i';
	}
	elseif ($period < SECONDS_1MONTH) {
		$chart_time_format = 'M d H\h';
	}
	else { 
		$chart_time_format = 'M d H\h';
	}

	// Calculate min, max and avg values
	$avg = 0;
	foreach ($data as $timestamp => $value) {
		$max = $value['data'];
		$min = $value['data'];
		break;
	}
	
	// Populate chart
	$count = 0;
	$chart = array ();
	foreach ($data as $timestamp => $value) {
		$chart[date ($chart_time_format, $timestamp)] = $value;
		if ($value['data'] > $max) {
			$max = $value['data'];
		}
		if ($value['data'] < $min) {
			$min = $value['data'];
		}
		$avg += $value['data'];
		$count++;
	}
	if ($count > 0) {
		$avg /= $count;
	}

	$flash_chart = $config['flash_charts'];
	if ($only_image) {
		$flash_chart = false;
	}
	
	if ($config['homeurl'] != '') {
		$homeurl = $config['homeurl'];
	}
	else {
		$homeurl = '';
	}
	
	if($config["fixed_graph"] == false){
		$water_mark = array('file' =>
			$config['homedir'] . "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
	}
	
	$legend = array (__('Max.') . ' ' . format_numeric($max) . ' ' . __('Min.') . ' ' . format_numeric($min) . ' ' . __('Avg.') . ' ' . format_numeric ($avg));
	return area_graph($flash_chart, $chart, $width, $height, array (), $legend,
		array (), ui_get_full_url("images/image_problem.opaque.png", false, false, false),
		"", "", $homeurl, $water_mark,
		$config['fontpath'], $config['font_size'], $unit, $ttl);
}

/**
 * Print a pie graph with netflow aggregated
 */
function graph_netflow_aggregate_pie ($data, $aggregate, $ttl = 1, $only_image = false) {
	global $config;
	global $graphic_type;
	
	if (empty ($data)) {
		return fs_error_image ();
	}
	
	$i = 0;
	$values = array();
	$agg = '';
	while (isset ($data[$i])) {
		$agg = $data[$i]['agg'];
		if (!isset($values[$agg])) {
			$values[$agg] = $data[$i]['data'];
		}
		else {
			$values[$agg] += $data[$i]['data'];
		}
		$i++;
	}
	
	$flash_chart = $config['flash_charts'];
	if ($only_image) {
		$flash_chart = false;
	}
	
	if($config["fixed_graph"] == false){
		$water_mark = array('file' =>
			$config['homedir'] . "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
	}
	
	return pie3d_graph($flash_chart, $values, 370, 200,
		__('Other'), $config['homeurl'], $water_mark,
		$config['fontpath'], $config['font_size'], $ttl);
}

/**
 * Print a circular graph with the data transmitted between IPs
 */
function graph_netflow_circular_mesh ($data, $unit, $radius = 700) {
	global $config;

	if (empty($data) || empty($data['elements']) || empty($data['matrix'])) {
		return fs_error_image ();
	}

	include_once($config['homedir'] . "/include/graphs/functions_d3.php");

	return d3_relationship_graph ($data['elements'], $data['matrix'], $unit, $radius, true);
}

/**
 * Print a rectangular graph with the traffic of the ports for each IP
 */
function graph_netflow_host_traffic ($data, $unit, $width = 700, $height = 700) {
	global $config;

	if (empty ($data)) {
		return fs_error_image ();
	}

	include_once($config['homedir'] . "/include/graphs/functions_d3.php");

	return d3_tree_map_graph ($data, $width, $height, true);
}

/**
 * Draw a graph of Module string data of agent
 * 
 * @param integer id_agent_modulo Agent Module ID
 * @param integer show_event show event (1 or 0)
 * @param integer height graph height
 * @param integer width graph width
 * @param string title graph title
 * @param string unit_name String of unit name
 * @param integer show alerts (1 or 0)
 * @param integer avg_only calcules avg only (1 or 0)
 * @param integer pure Fullscreen (1 or 0)
 * @param integer date date
 */
function grafico_modulo_string ($agent_module_id, $period, $show_events,
	$width, $height, $title, $unit_name, $show_alerts, $avg_only = 0, $pure = 0,
	$date = 0, $only_image = false, $homeurl = '', $adapt_key = '', $ttl = 1, $menu = true) {
	global $config;
	global $graphic_type;
	global $max_value;
	
	
	// Set variables
	if ($date == 0)
		$date = get_system_time();
	$datelimit = $date - $period;
	$search_in_history_db = db_search_in_history_db($datelimit);
	$resolution = $config['graph_res'] * 50; //Number of points of the graph
	$interval = (int) ($period / $resolution);
	$agent_name = modules_get_agentmodule_agent_name ($agent_module_id);
	$agent_id = agents_get_agent_id ($agent_name);
	$module_name = modules_get_agentmodule_name ($agent_module_id);
	$id_module_type = modules_get_agentmodule_type ($agent_module_id);
	$module_type = modules_get_moduletype_name ($id_module_type);
	$uncompressed_module = is_module_uncompressed ($module_type);
	if ($uncompressed_module) {
		$avg_only = 1;
	}
	$search_in_history_db = db_search_in_history_db($datelimit);
	
	// Get event data (contains alert data too)
	if ($show_events == 1 || $show_alerts == 1) {
		$events = db_get_all_rows_filter ('tevento',
			array ('id_agentmodule' => $agent_module_id,
				"utimestamp > $datelimit",
				"utimestamp < $date",
				'order' => 'utimestamp ASC'),
			array ('evento', 'utimestamp', 'event_type'));
		if ($events === false) {
			$events = array ();
		}
	}
	
	// Get module data
	$data = db_get_all_rows_filter ('tagente_datos_string',
		array ('id_agente_modulo' => $agent_module_id,
			"utimestamp > $datelimit",
			"utimestamp < $date",
			'order' => 'utimestamp ASC'),
		array ('datos', 'utimestamp'), 'AND', $search_in_history_db);
	if ($data === false) {
		$data = array ();
	}
	
	// Uncompressed module data
	if ($uncompressed_module) {
		$min_necessary = 1;
	}
	else {
		// Compressed module data
		
		// Get previous data
		$previous_data = modules_get_previous_data ($agent_module_id, $datelimit, 1);
		if ($previous_data !== false) {
			$previous_data['utimestamp'] = $datelimit;
			array_unshift ($data, $previous_data);
		}
		
		// Get next data
		$nextData = modules_get_next_data ($agent_module_id, $date, 1);
		if ($nextData !== false) {
			array_push ($data, $nextData);
		}
		else if (count ($data) > 0) {
			// Propagate the last known data to the end of the interval
			$nextData = array_pop ($data);
			array_push ($data, $nextData);
			$nextData['utimestamp'] = $date;
			array_push ($data, $nextData);
		}
		
		$min_necessary = 2;
	}
	
	// Check available data
	if (count ($data) < $min_necessary) {
		if (!$graphic_type) {
			return fs_error_image ($width, $height);
		}
		graphic_error ();
	}
	
	// Data iterator
	$j = 0;
	
	// Event iterator
	$k = 0;
	
	// Set initial conditions
	$chart = array();
	if ($data[0]['utimestamp'] == $datelimit) {
		$previous_data = 1;
		$j++;
	}
	else {
		$previous_data = 0;
	}
	
	// Calculate chart data
	$last_known = $previous_data;
	for ($i = 0; $i < $resolution; $i++) {
		$timestamp = $datelimit + ($interval * $i);
		
		$count = 0;
		$total = 0;
		// Read data that falls in the current interval
		while (isset($data[$j]) &&
			isset ($data[$j]) !== null &&
			$data[$j]['utimestamp'] >= $timestamp &&
			$data[$j]['utimestamp'] <= ($timestamp + $interval)) {
			
			// ---------------------------------------------------------
			// FIX TICKET #1749
			$last_known = $count;
			// ---------------------------------------------------------
			$count++;
			$j++;
		}
		
		if ($max_value < $count) {
			$max_value = $count;
		}
		
		// Read events and alerts that fall in the current interval
		$event_value = 0;
		$alert_value = 0;
		while (isset ($events[$k]) && $events[$k]['utimestamp'] >= $timestamp && $events[$k]['utimestamp'] <= ($timestamp + $interval)) {
			if ($show_events == 1) {
				$event_value++;
			}
			if ($show_alerts == 1 && substr ($events[$k]['event_type'], 0, 5) == 'alert') {
				$alert_value++;
			}
			$k++;
		}
		
		/////////////////////////////////////////////////////////////////
		// Set the title and time format
		if ($period <= SECONDS_6HOURS) {
			$time_format = 'H:i:s';
		}
		elseif ($period < SECONDS_1DAY) {
			$time_format = 'H:i';
		}
		elseif ($period < SECONDS_15DAYS) {
			$time_format = 'M d H:i';
		}
		elseif ($period < SECONDS_1MONTH) {
			$time_format = 'M d H\h';
		}
		else {
			$time_format = 'M d H\h';
		}
		
		$timestamp_short = date($time_format, $timestamp);
		$long_index[$timestamp_short] = date(
			html_entity_decode($config['date_format'], ENT_QUOTES, "UTF-8"), $timestamp);
		$timestamp = $timestamp_short;
		/////////////////////////////////////////////////////////////////
		
		// Data in the interval
		//The order in chart array is very important!!!!
		if ($show_events) {
			$chart[$timestamp]['event'] = $event_value;
		}
		
		if ($show_alerts) {
			$chart[$timestamp]['alert'] = $alert_value;
		}
		
		if (!$avg_only) {
			$chart[$timestamp]['max'] = 0;
		}
		
		if ($count > 0) {
			$chart[$timestamp]['sum'] = $count;
		}
		else {
			// Compressed data
			$chart[$timestamp]['sum'] = $last_known;
		}
		
		if (!$avg_only) {
			$chart[$timestamp]['min'] = 0;
		}
	}
	
	$graph_stats = get_statwin_graph_statistics($chart);
	
	// Fix event and alert scale
	$event_max = 2 + (float)$max_value * 1.05;
	foreach ($chart as $timestamp => $chart_data) {
		if (!empty($chart_data['event']) && $chart_data['event'] > 0) {
			$chart[$timestamp]['event'] = $event_max;
		}
		if (!empty($chart_data['alert']) && $chart_data['alert'] > 0) {
			$chart[$timestamp]['alert'] = $event_max;
		}
	}
	
	if (empty($unit_name)) {
		$unit = modules_get_unit($agent_module_id);
	}
	else
		$unit = $unit_name;
	
	/////////////////////////////////////////////////////////////////////////////////////////
	$color = array();
	
	if ($show_events) {
		$color['event'] = array('border' => '#ff0000',
			'color' => '#ff0000', 'alpha' => CHART_DEFAULT_ALPHA);
	}
	if ($show_alerts) {
		$color['alert'] = array('border' => '#ff7f00',
			'color' => '#ff7f00', 'alpha' => CHART_DEFAULT_ALPHA);
	}
	
	if (!$avg_only) {
		$color['max'] = array('border' => '#000000',
			'color' => $config['graph_color3'],
			'alpha' => CHART_DEFAULT_ALPHA);
	}
	$color['sum'] = array('border' => '#000000',
		'color' => $config['graph_color2'],
		'alpha' => CHART_DEFAULT_ALPHA);
	
	if (!$avg_only) {
		$color['min'] = array('border' => '#000000',
			'color' => $config['graph_color1'],
			'alpha' => CHART_DEFAULT_ALPHA);
	}
	
	//$color['baseline'] = array('border' => null, 'color' => '#0097BD', 'alpha' => 10);
	/////////////////////////////////////////////////////////////////////////////////////////
	
	$flash_chart = $config['flash_charts'];
	if ($only_image) {
		$flash_chart = false;
	}
	
	$legend = array();
	
	if ($show_events) {
		$legend['event'] = __('Events');
	}
	
	if ($show_alerts) {
		$legend['alert'] = __('Alerts');
	}
	
	if (!$avg_only) {
		$legend['max'] = __('Max').': '.__('Last').': '.$graph_stats['max']['last'].' '.$unit.' ; '.__('Avg').': '.$graph_stats['max']['avg'].' '.$unit.' ; '.__('Max').': '.$graph_stats['max']['max'].' '.$unit.' ; '.__('Min').': '.$graph_stats['max']['min'].' '.$unit;
	}
	
	$legend['sum'] = __('Avg').': '.__('Last').': '.$graph_stats['sum']['last'].' '.$unit.' ; '.__('Avg').': '.$graph_stats['sum']['avg'].' '.$unit.' ; '.__('Max').': '.$graph_stats['sum']['max'].' '.$unit.' ; '.__('Min').': '.$graph_stats['sum']['min'].' '.$unit;
	
	if (!$avg_only) {
		$legend['min'] = __('Min').': '.__('Last').': '.$graph_stats['min']['last'].' '.$unit.' ; '.__('Avg').': '.$graph_stats['min']['avg'].' '.$unit.' ; '.__('Max').': '.$graph_stats['min']['max'].' '.$unit.' ; '.__('Min').': '.$graph_stats['min']['min'].' '.$unit;
	}
	
	if($config["fixed_graph"] == false){
		$water_mark = array('file' =>
			$config['homedir'] . "/images/logo_vertical_water.png",
			'url' => ui_get_full_url("images/logo_vertical_water.png", false, false, false));
	}
	
	if ($config['type_module_charts'] === 'area') {
		return area_graph($flash_chart, $chart, $width, $height, $color,
			$legend, array(), '', "", $unit, $homeurl,
			$water_mark, $config['fontpath'], $config['font_size'], $unit,
			1, array(),	array(), 0, 0, $adapt_key, true, '', $menu);
	}
	else {
		return
			line_graph($flash_chart, $chart, $width, $height, $color,
				$legend, $long_index,
				ui_get_full_url("images/image_problem.opaque.png", false, false, false),
				"", $unit, $water_mark, $config['fontpath'],
				$config['font_size'], $unit, $ttl, $homeurl, $backgroundColor);
	}
}

/**
 * Print a graph with event data of module
 * 
 * @param integer id_module Module ID
 * @param integer width graph width
 * @param integer height graph height
 * @param integer period time period
 * @param string homeurl Home url if the complete path is needed
 * @param int Zoom factor over the graph
 * @param string adaptation width and margin left key (could be adapter_[something] or adapted_[something])
 * @param int date limit of the period
 */
function graphic_module_events ($id_module, $width, $height, $period = 0, $homeurl = '', $zoom = 0, $adapt_key = '', $date = false, $stat_win = false) {
	global $config;
	global $graphic_type;
	
	$data = array ();
	
	$resolution = $config['graph_res'] * ($period * 2 / $width); // Number of "slices" we want in graph
	
	$interval = (int) ($period / $resolution);
	if ($date === false) {
		$date = get_system_time ();
	}
	$datelimit = $date - $period;
	$periodtime = floor ($period / $interval);
	$time = array ();
	$data = array ();
	
	// Set the title and time format
	if ($period <= SECONDS_6HOURS) {
		$time_format = 'H:i:s';
	}
	elseif ($period < SECONDS_1DAY) {
		$time_format = 'H:i';
	}
	elseif ($period < SECONDS_15DAYS) {
		$time_format = 'M d H:i';
	}
	elseif ($period < SECONDS_1MONTH) {
		$time_format = 'M d H\h';
	}
	else { 
		$time_format = 'M d H\h';
	}
	
	$legend = array();
	$cont = 0;
	for ($i = 0; $i < $interval; $i++) {
		$bottom = $datelimit + ($periodtime * $i);
		if (! $graphic_type) {
			$name = date($time_format, $bottom);
			//$name = date('H\h', $bottom);
		}
		else {
			$name = $bottom;
		}
		
		$top = $datelimit + ($periodtime * ($i + 1));
		
		$events = db_get_all_rows_filter ('tevento', 
			array ('id_agentmodule' => $id_module,
				'utimestamp > '.$bottom,
				'utimestamp < '.$top),
			'event_type, utimestamp');

		if (!empty($events)) {
			$status = 'normal';
			foreach($events as $event) {
				if (empty($event['utimestamp'])) {
					continue;
				}
			
				switch($event['event_type']) {
					case 'going_down_normal':
					case 'going_up_normal':
						// The default status is normal. Do nothing
						break;
					case 'going_unknown':
						if ($status == 'normal') {
							$status = 'unknown';
						}
						break;
					case 'going_up_warning':
					case 'going_down_warning':
						if ($status == 'normal' || $status == 'unknown') {
							$status = 'warning';
						}
						break;
					case 'going_up_critical':
					case 'going_down_critical':
						$status = 'critical';
						break;
				}
			}
		}
		
		$data[$cont]['utimestamp'] = $periodtime;
		
		if (!empty($events)) {
			switch ($status) {
				case 'warning':
					$data[$cont]['data'] = 2;
					break;
				case 'critical':
					$data[$cont]['data'] = 3;
					break;
				case 'unknown':
					$data[$cont]['data'] = 4;
					break;
				default:
					$data[$cont]['data'] = 1;
					break;
			}
		}
		else {
			$data[$cont]['data'] = 1;
		}
		$current_timestamp = $bottom;
		
		$legend[] = date($time_format, $current_timestamp);	
		$cont++;
	}
	
	$pixels_between_xdata = 25;
	$max_xdata_display = round($width / $pixels_between_xdata);
	$ndata = count($data);
	if ($max_xdata_display > $ndata) {
		$xdata_display = $ndata;
	}
	else {
		$xdata_display = $max_xdata_display;
	}
	
	$step = round($ndata/$xdata_display);
	
	$colors = array(1 => '#38B800', 2 => '#FFFF00', 3 => '#FF0000', 4 => '#C3C3C3');
	
	// Draw slicebar graph
	if ($config['flash_charts']) {
		echo flot_slicesbar_graph($data, $period, $width, 15, $legend, $colors, $config['fontpath'], $config['round_corner'], $homeurl, '', $adapt_key, $stat_win);
	}
	else {
		echo slicesbar_graph($data, $period, $width, 15, $colors, $config['fontpath'], $config['round_corner'], $homeurl);
	}
}

///Functions for the LOG4X graphs
function grafico_modulo_log4x ($id_agente_modulo, $periodo, $show_event,
	$width, $height , $title, $unit_name, $show_alert, $avg_only = 0, $pure=0,
	$date = 0) {
	
	grafico_modulo_log4x_trace("<pre style='text-align:left;'>");
	
	if ($date == "")
		$now = time ();
	else
		$now = $date;
	
	$fechatope = $now - $periodo; // limit date
	
	$nombre_agente = modules_get_agentmodule_agent_name ($id_agente_modulo);
	$nombre_modulo = modules_get_agentmodule_name ($id_agente_modulo);
	$id_agente = agents_get_agent_id ($nombre_agente);
	
	$adjust_time = SECONDS_1MINUTE;
	

	if ($periodo == SECONDS_1DAY)
		$adjust_time = SECONDS_1HOUR;
	elseif ($periodo == SECONDS_1WEEK)
		$adjust_time = SECONDS_1DAY;
	elseif ($periodo == SECONDS_1HOUR)
		$adjust_time = SECONDS_10MINUTES;
	elseif ($periodo == SECONDS_1MONTH)
		$adjust_time = SECONDS_1WEEK;
	else
		$adjust_time = $periodo / 12.0;
	
	$num_slices = $periodo / $adjust_time;
	
	$fechatope_index = grafico_modulo_log4x_index($fechatope, $adjust_time);
	
	$sql1="SELECT utimestamp, SEVERITY " .
			" FROM tagente_datos_log4x " .
			" WHERE id_agente_modulo = $id_agente_modulo AND utimestamp > $fechatope and utimestamp < $now";
	
	$valores = array();
	
	$max_count = -1;
	$min_count = 9999999;
	
	grafico_modulo_log4x_trace("$sql1");
	
	$rows = 0;
	
	$first = true;
	while ($row = get_db_all_row_by_steps_sql($first, $result, $sql1)) {
		$first = false;
		
		$rows++;
		$utimestamp = $row[0];
		$severity = $row[1];
		$severity_num = $row[2];
		
		if (!isset($valores[$severity]))
			$valores[$severity] = array();
		
		$dest = grafico_modulo_log4x_index($utimestamp, $adjust_time);
		
		$index = (($dest - $fechatope_index) / $adjust_time) - 1;
		
		if (!isset($valores[$severity][$index])) {
			$valores[$severity][$index] = array();
			$valores[$severity][$index]['pivot'] = $dest;
			$valores[$severity][$index]['count'] = 0;
			$valores[$severity][$index]['alerts'] = 0;
		}
		
		$valores[$severity][$index]['count']++;
		
		$max_count = max($max_count, $valores[$severity][$index]['count']);
		$min_count = min($min_count, $valores[$severity][$index]['count']);
	}
	
	grafico_modulo_log4x_trace("$rows rows");
	
	// Create graph
	// *************
	
	grafico_modulo_log4x_trace(__LINE__);
	
	//set_error_handler("myErrorHandler");
	
	grafico_modulo_log4x_trace(__LINE__);
	$ds = DIRECTORY_SEPARATOR;
	set_include_path(get_include_path() . PATH_SEPARATOR . getcwd() . $ds."..".$ds."..".$ds."include");
	
	require_once 'Image/Graph.php';
	
	grafico_modulo_log4x_trace(__LINE__);
	
	$Graph =& Image_Graph::factory('graph', array($width, $height));
	
	grafico_modulo_log4x_trace(__LINE__);
	
	// add a TrueType font
	$Font =& $Graph->addNew('font', $config['fontpath']); // C:\WINNT\Fonts\ARIAL.TTF
	$Font->setSize(7);
	
	$Graph->setFont($Font);
	
	if ($periodo == SECONDS_1DAY)
		$title_period = $lang_label["last_day"];
	elseif ($periodo == SECONDS_1WEEK)
		$title_period = $lang_label["last_week"];
	elseif ($periodo == SECONDS_1HOUR)
		$title_period = $lang_label["last_hour"];
	elseif ($periodo == SECONDS_1MONTH)
		$title_period = $lang_label["last_month"];
	else {
		$suffix = $lang_label["days"];
		$graph_extension = $periodo / SECONDS_1DAY;
		
		if ($graph_extension < 1) {
			$graph_extension = $periodo / SECONDS_1HOUR;
			$suffix = $lang_label["hours"];
		}
		//$title_period = "Last ";
		$title_period = format_numeric($graph_extension,2)." $suffix";
	}
	
	$title_period = html_entity_decode($title_period);
	
	grafico_modulo_log4x_trace(__LINE__);
	
	if ($pure == 0) {
		$Graph->add(
			Image_Graph::horizontal(
				Image_Graph::vertical(
					Image_Graph::vertical(
						$Title = Image_Graph::factory('title', array('   Pandora FMS Graph - '.strtoupper($nombre_agente)." - " .$title_period, 10)),
						$Subtitle = Image_Graph::factory('title', array('     '.$title, 7)),
						90
					),
					$Plotarea = Image_Graph::factory('plotarea', array('Image_Graph_Axis', 'Image_Graph_Axis')),
					15 // If you change this, change the 0.85 below
				),
				Image_Graph::vertical(
					$Legend = Image_Graph::factory('legend'),
					$PlotareaMinMax = Image_Graph::factory('plotarea'),
					65
				),
				85 // If you change this, change the 0.85 below
			)
		);
		
		$Legend->setPlotarea($Plotarea);
		$Title->setAlignment(IMAGE_GRAPH_ALIGN_LEFT);
		$Subtitle->setAlignment(IMAGE_GRAPH_ALIGN_LEFT);
	}
	else { // Pure, without title and legends
		$Graph->add($Plotarea = Image_Graph::factory('plotarea', array('Image_Graph_Axis', 'Image_Graph_Axis')));
	}
	
	grafico_modulo_log4x_trace(__LINE__);
	
	$dataset = array();
	
	$severities = array("FATAL", "ERROR", "WARN", "INFO", "DEBUG", "TRACE");
	$colors = array("black", "red", "orange", "yellow", "#3300ff", 'magenta');
	
	$max_bubble_radius = $height * 0.6 / (count($severities) + 1); // this is the size for the max_count
	$y = count($severities) - 1;
	$i = 0;
	
	foreach($severities as $severity) {
		$dataset[$i] = Image_Graph::factory('dataset');
		$dataset[$i]->setName($severity);
		
		if (isset($valores[$severity])) {
			$data =& $valores[$severity];
			while (list($index, $data2) = each($data)) {
				$count = $data2['count'];
				$pivot = $data2['pivot'];
				
				//$x = $scale * $index;
				$x = 100.0 * ($pivot - $fechatope) / ($now - $fechatope);
				if ($x > 100) $x = 100;
				
				$size = grafico_modulo_log4x_bubble_size($count, $max_count, $max_bubble_radius);
				
				// pivot is the value in the X axis
				// y is the number of steps (from the bottom of the graphics) (zero based)
				// x is the position of the bubble, in % from the left (0% = full left, 100% = full right)
				// size is the radius of the bubble
				// value is the value associated with the bubble (needed to calculate the leyend)
				//
				$dataset[$i]->addPoint($pivot, $y, array("x" => $x, "size" => $size, "value" => $count));
			}
		}
		else {
			// There's a problem when we have no data ...
			// This was the first try.. didnt work
			//$dataset[$i]->addPoint($now, -1, array("x" => 0, "size" => 0));
		}
		
		$y--;
		$i++;
	}
	
	grafico_modulo_log4x_trace(__LINE__);
	
	// create the 1st plot as smoothed area chart using the 1st dataset
	$Plot =& $Plotarea->addNew('bubble', array(&$dataset));
	$Plot->setFont($Font);
	
	$AxisX =& $Plotarea->getAxis(IMAGE_GRAPH_AXIS_X);
	$AxisX->setDataPreprocessor(Image_Graph::factory('Image_Graph_DataPreprocessor_Function', 'grafico_modulo_log4x_format_x_axis'));
	$AxisX->forceMinimum($fechatope);
	$AxisX->forceMaximum($now);
	
	$minIntervalWidth = $Plot->getTextWidth("88/88/8888");
	$interval_x = $adjust_time;
	
	while (true) {
		$intervalWidth = $width * 0.85 * $interval_x/ $periodo;
		if ($intervalWidth >= $minIntervalWidth)
			break;
		
		$interval_x *= 2;
	}
	
	$AxisX->setLabelInterval($interval_x);
	$AxisX->setLabelOption("showtext",true);
	
	//*
	$GridY2 =& $Plotarea->addNew('line_grid');
	$GridY2->setLineColor('gray');
	$GridY2->setFillColor('lightgray@0.05');
	$GridY2->_setPrimaryAxis($AxisX);
	//$GridY2->setLineStyle(Image_Graph::factory('Image_Graph_Line_Dotted', array("white", "gray", "gray", "gray")));
	$GridY2->setLineStyle(Image_Graph::factory('Image_Graph_Line_Formatted', array(array("transparent", "transparent", "transparent", "gray"))));
	//*/
	//grafico_modulo_log4x_trace(print_r($AxisX, true));
	
	$AxisY =& $Plotarea->getAxis(IMAGE_GRAPH_AXIS_Y);
	$AxisY->setDataPreprocessor(Image_Graph::factory('Image_Graph_DataPreprocessor_Function', 'grafico_modulo_log4x_format_y_axis'));
	$AxisY->setLabelOption("showtext",true);
	//$AxisY->setLabelInterval(0);
	//$AxisY->showLabel(IMAGE_GRAPH_LABEL_ZERO);
	
	//*
	$GridY2 =& $Plotarea->addNew('line_grid');
	$GridY2->setLineColor('gray');
	$GridY2->setFillColor('lightgray@0.05');
	$GridY2->_setPrimaryAxis($AxisY);
	$GridY2->setLineStyle(Image_Graph::factory('Image_Graph_Line_Formatted', array(array("transparent", "transparent", "transparent", "gray"))));
	//*/
	
	$AxisY->forceMinimum(0);
	$AxisY->forceMaximum(count($severities) + 1) ;
	
	// set line colors
	$FillArray =& Image_Graph::factory('Image_Graph_Fill_Array');
	
	$Plot->setFillStyle($FillArray);
	foreach($colors as $color)
		$FillArray->addColor($color);
	
	grafico_modulo_log4x_trace(__LINE__);
	
	$FillArray->addColor('green@0.6');
	//$AxisY_Weather =& $Plotarea->getAxis(IMAGE_GRAPH_AXIS_Y);
	
	// Show events !
	if ($show_event == 1) {
		$Plot =& $Plotarea->addNew('Plot_Impulse', array($dataset_event));
		$Plot->setLineColor( 'red' );
		$Marker_event =& Image_Graph::factory('Image_Graph_Marker_Cross');
		$Plot->setMarker($Marker_event);
		$Marker_event->setFillColor( 'red' );
		$Marker_event->setLineColor( 'red' );
		$Marker_event->setSize ( 5 );
	}
	
	$Axis =& $PlotareaMinMax->getAxis(IMAGE_GRAPH_AXIS_X);
	$Axis->Hide();
	$Axis =& $PlotareaMinMax->getAxis(IMAGE_GRAPH_AXIS_Y);
	$Axis->Hide();
	
	$plotMinMax =& $PlotareaMinMax->addNew('bubble', array(&$dataset, true));
	
	grafico_modulo_log4x_trace(__LINE__);
	
	$Graph->done();
	
	grafico_modulo_log4x_trace(__LINE__);
}

function grafico_modulo_log4x_index($x, $interval)
{
	return $x + $interval - (($x - 1) % $interval) - 1;
}

function grafico_modulo_log4x_trace($str)
{
	//echo "$str\n";
}

function grafico_modulo_log4x_bubble_size($count, $max_count, $max_bubble_radius)
{
	//Superformula de ROA
	$r0 = 1.5;
	$r1 = $max_bubble_radius;
	$v2 = pow($max_count,1/2.0);
	
	return $r1*pow($count,1/2.0)/($v2)+$r0;
}

function grafico_modulo_log4x_format_x_axis ( $number , $decimals=2, $dec_point=".", $thousands_sep=",")
{
	// $number is the unix time in the local timezone
	
	//$dtZone = new DateTimeZone(date_default_timezone_get());
	//$d = new DateTime("now", $dtZone);
	//$offset = $dtZone->getOffset($d);
	//$number -= $offset;
	
	return date("d/m", $number) . "\n" . date("H:i", $number);
}

function grafico_modulo_log4x_format_y_axis ( $number , $decimals=2, $dec_point=".", $thousands_sep=",")
{
	
	switch ($number) {
		case 6:
			return "FATAL";
			break;
		case 5:
			return "ERROR";
			break;
		case 4:
			return "WARN";
			break;
		case 3:
			return "INFO";
			break;
		case 2:
			return "DEBUG";
			break;
		case 1:
			return "TRACE";
			break;
		default:
			return "";
			break;
	}
	
}

function graph_nodata_image($width = 300, $height = 110, $type = 'area', $text = '') {
	$image = ui_get_full_url('images/image_problem_' . $type . '.png',
		false, false, false); 
	
	if ($text == '') {
		$text = __('No data to show');
	}
	
	$text_div = '<div class="nodata_text">' . $text . '</div>';
	
	$image_div = '<div class="nodata_container" style="background-image: url(\'' . $image . '\');">' .
		$text_div . '</div>';
	
	$div = '<div style="width:' . $width . 'px; height:' . $height . 'px; border: 1px dotted #ddd; background-color: white; margin: 0 auto;">' .
		$image_div . '</div>';
	
	return $div;
}

function get_criticity_pie_colors ($data_graph) {
	$colors = array();
	foreach (array_keys($data_graph) as $crit) {
		switch ($crit) {
			case __('Maintenance'): 
				$colors[$crit] = COL_MAINTENANCE;
				break;
			case __('Informational'): 
				$colors[$crit] = COL_INFORMATIONAL;
				break;
			case __('Normal'): 
				$colors[$crit] = COL_NORMAL;
				break;
			case __('Warning'): 
				$colors[$crit] = COL_WARNING;
				break;
			case __('Critical'): 
				$colors[$crit] = COL_CRITICAL;
				break;
			case __('Minor'): 
				$colors[$crit] = COL_MINOR;
				break;
			case __('Major'): 
				$colors[$crit] = COL_MAJOR;
				break;
		}
	}
	
	return $colors;
}


/**
 * Print a rectangular graph with the snmptraps received
 */
function graph_snmp_traps_treemap ($data, $width = 700, $height = 700) {
	global $config;

	if (empty ($data)) {
		return fs_error_image ();
	}

	include_once($config['homedir'] . "/include/graphs/functions_d3.php");

	return d3_tree_map_graph ($data, $width, $height, true);
}

/**
 * Print a solarburst graph with a representation of all the groups, agents, module groups and modules grouped
 */
function graph_monitor_wheel ($width = 550, $height = 600, $filter = false) {
	global $config;

	include_once ($config['homedir'] . "/include/functions_users.php");
	include_once ($config['homedir'] . "/include/functions_groups.php");
	include_once ($config['homedir'] . "/include/functions_agents.php");
	include_once ($config['homedir'] . "/include/functions_modules.php");

	$graph_data = array();

	$filter_module_group = (!empty($filter) && !empty($filter['module_group'])) ? $filter['module_group'] : false;

	$groups = users_get_groups(false, "AR", false, true, (!empty($filter) && isset($filter['group']) ? $filter['group'] : null));

	$data_groups = array();
	if (!empty($groups)) {
		$groups_aux = $groups;
		$data_groups = groups_get_tree($groups);
		$groups_aux = null;
	}

	if (!empty($data_groups)) {
		$filter = array('id_grupo' => array_keys($groups));
		$fields = array('id_agente', 'id_parent', 'id_grupo', 'nombre');
		$agents = agents_get_agents($filter, $fields);

		if (!empty($agents)) {
			$agents_id = array();
			$agents_aux = array();
			foreach ($agents as $key => $agent) {
				$agents_aux[$agent['id_agente']] = $agent;
			}
			$agents = $agents_aux;
			$agents_aux = null;
			$fields = array('id_agente_modulo', 'id_agente', 'id_module_group', 'nombre');

			$module_groups = modules_get_modulegroups();
			$module_groups[0] = __('Not assigned');
			$modules = agents_get_modules(array_keys($agents), '*');

			$data_agents = array();
			if (!empty($modules)) {
				foreach ($modules as $key => $module) {
					$module_id = (int) $module['id_agente_modulo'];
					$agent_id = (int) $module['id_agente'];
					$module_group_id = (int) $module['id_module_group'];
					$module_name = io_safe_output($module['nombre']);
					$module_status = modules_get_agentmodule_status($module_id);
					$module_value = modules_get_last_value($module_id);
					
					if ($filter_module_group && $filter_module_group != $module_group_id)
						continue;

					if (!isset($data_agents[$agent_id])) {
						$data_agents[$agent_id] = array();
						$data_agents[$agent_id]['id'] = $agent_id;
						$data_agents[$agent_id]['name'] = io_safe_output($agents[$agent_id]['nombre']);
						$data_agents[$agent_id]['group'] = (int) $agents[$agent_id]['id_grupo'];
						$data_agents[$agent_id]['type'] = 'agent';
						$data_agents[$agent_id]['size'] = 30;
						$data_agents[$agent_id]['children'] = array();

						$tooltip_content = __('Agent') . ": <b>" . $data_agents[$agent_id]['name'] . "</b>";
						$data_agents[$agent_id]['tooltip_content'] = io_safe_output($tooltip_content);

						$data_agents[$agent_id]['modules_critical'] = 0;
						$data_agents[$agent_id]['modules_warning'] = 0;
						$data_agents[$agent_id]['modules_normal'] = 0;
						$data_agents[$agent_id]['modules_not_init'] = 0;
						$data_agents[$agent_id]['modules_not_normal'] = 0;
						$data_agents[$agent_id]['modules_unknown'] = 0;

						$data_agents[$agent_id]['color'] = COL_UNKNOWN;

						unset($agents[$agent_id]);
					}
					if (!isset($data_agents[$agent_id]['children'][$module_group_id])) {
						$data_agents[$agent_id]['children'][$module_group_id] = array();
						$data_agents[$agent_id]['children'][$module_group_id]['id'] = $module_group_id;
						$data_agents[$agent_id]['children'][$module_group_id]['name'] = io_safe_output($module_groups[$module_group_id]);
						$data_agents[$agent_id]['children'][$module_group_id]['type'] = 'module_group';
						$data_agents[$agent_id]['children'][$module_group_id]['size'] = 10;
						$data_agents[$agent_id]['children'][$module_group_id]['children'] = array();

						$tooltip_content = __('Module group') . ": <b>" . $data_agents[$agent_id]['children'][$module_group_id]['name'] . "</b>";
						$data_agents[$agent_id]['children'][$module_group_id]['tooltip_content'] = $tooltip_content;

						$data_agents[$agent_id]['children'][$module_group_id]['modules_critical'] = 0;
						$data_agents[$agent_id]['children'][$module_group_id]['modules_warning'] = 0;
						$data_agents[$agent_id]['children'][$module_group_id]['modules_normal'] = 0;
						$data_agents[$agent_id]['children'][$module_group_id]['modules_not_init'] = 0;
						$data_agents[$agent_id]['children'][$module_group_id]['modules_not_normal'] = 0;
						$data_agents[$agent_id]['children'][$module_group_id]['modules_unknown'] = 0;

						$data_agents[$agent_id]['children'][$module_group_id]['color'] = COL_UNKNOWN;
					}
					
					switch ($module_status) {
						case AGENT_MODULE_STATUS_CRITICAL_BAD:
						case AGENT_MODULE_STATUS_CRITICAL_ALERT:
							$data_agents[$agent_id]['modules_critical']++;
							$data_agents[$agent_id]['children'][$module_group_id]['modules_critical']++;
							break;
						
						case AGENT_MODULE_STATUS_WARNING:
						case AGENT_MODULE_STATUS_WARNING_ALERT:
							$data_agents[$agent_id]['modules_warning']++;
							$data_agents[$agent_id]['children'][$module_group_id]['modules_warning']++;
							break;

						case AGENT_MODULE_STATUS_NORMAL:
						case AGENT_MODULE_STATUS_NORMAL_ALERT:
							$data_agents[$agent_id]['modules_normal']++;
							$data_agents[$agent_id]['children'][$module_group_id]['modules_normal']++;
							break;

						case AGENT_MODULE_STATUS_NOT_INIT:
							$data_agents[$agent_id]['modules_not_init']++;
							$data_agents[$agent_id]['children'][$module_group_id]['modules_not_init']++;
							break;

						case AGENT_MODULE_STATUS_NOT_NORMAL:
							$data_agents[$agent_id]['modules_not_normal']++;
							$data_agents[$agent_id]['children'][$module_group_id]['modules_not_normal']++;
							break;

						case AGENT_MODULE_STATUS_NO_DATA:
						case AGENT_MODULE_STATUS_UNKNOWN:
							$data_agents[$agent_id]['modules_unknown']++;
							$data_agents[$agent_id]['children'][$module_group_id]['modules_unknown']++;
							break;
					}

					if ($data_agents[$agent_id]['modules_critical'] > 0) {
						$data_agents[$agent_id]['color'] = COL_CRITICAL;
					}
					else if ($data_agents[$agent_id]['modules_warning'] > 0) {
						$data_agents[$agent_id]['color'] = COL_WARNING;
					}
					else if ($data_agents[$agent_id]['modules_not_normal'] > 0) {
						$data_agents[$agent_id]['color'] = COL_WARNING;
					}
					else if ($data_agents[$agent_id]['modules_unknown'] > 0) {
						$data_agents[$agent_id]['color'] = COL_UNKNOWN;
					}
					else if ($data_agents[$agent_id]['modules_normal'] > 0) {
						$data_agents[$agent_id]['color'] = COL_NORMAL;
					}
					else {
						$data_agents[$agent_id]['color'] = COL_NOTINIT;
					}

					if ($data_agents[$agent_id]['children'][$module_group_id]['modules_critical'] > 0) {
						$data_agents[$agent_id]['children'][$module_group_id]['color'] = COL_CRITICAL;
					}
					else if ($data_agents[$agent_id]['children'][$module_group_id]['modules_warning'] > 0) {
						$data_agents[$agent_id]['children'][$module_group_id]['color'] = COL_WARNING;
					}
					else if ($data_agents[$agent_id]['children'][$module_group_id]['modules_not_normal'] > 0) {
						$data_agents[$agent_id]['children'][$module_group_id]['color'] = COL_WARNING;
					}
					else if ($data_agents[$agent_id]['children'][$module_group_id]['modules_unknown'] > 0) {
						$data_agents[$agent_id]['children'][$module_group_id]['color'] = COL_UNKNOWN;
					}
					else if ($data_agents[$agent_id]['children'][$module_group_id]['modules_normal'] > 0) {
						$data_agents[$agent_id]['children'][$module_group_id]['color'] = COL_NORMAL;
					}
					else {
						$data_agents[$agent_id]['children'][$module_group_id]['color'] = COL_NOTINIT;
					}
					
					$data_module = array();
					$data_module['id'] = $module_id;
					$data_module['name'] = $module_name;
					$data_module['type'] = 'module';
					$data_module['size'] = 10;
					$data_module['link'] = ui_get_full_url("index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=$agent_id");

					$tooltip_content = __('Module') . ": <b>" . $module_name . "</b>";
					if (isset($module_value) && $module_value !== false) {
						$tooltip_content .= "<br>";
						$tooltip_content .= __('Value') . ": <b>" . io_safe_output($module_value) . "</b>";
					}
					$data_module['tooltip_content'] = $tooltip_content;

					switch ($module_status) {
						case AGENT_MODULE_STATUS_CRITICAL_BAD:
						case AGENT_MODULE_STATUS_CRITICAL_ALERT:
							$data_module['color'] = COL_CRITICAL;
							break;
						
						case AGENT_MODULE_STATUS_WARNING:
						case AGENT_MODULE_STATUS_WARNING_ALERT:
							$data_module['color'] = COL_WARNING;
							break;

						case AGENT_MODULE_STATUS_NORMAL:
						case AGENT_MODULE_STATUS_NORMAL_ALERT:
							$data_module['color'] = COL_NORMAL;
							break;

						case AGENT_MODULE_STATUS_NOT_INIT:
							$data_module['color'] = COL_NOTINIT;
							break;

						case AGENT_MODULE_STATUS_NOT_NORMAL:
							$data_module['color'] = COL_WARNING;
							break;

						case AGENT_MODULE_STATUS_NO_DATA:
						case AGENT_MODULE_STATUS_UNKNOWN:
						default:
							$data_module['color'] = COL_UNKNOWN;
							break;
					}

					$data_agents[$agent_id]['children'][$module_group_id]['children'][] = $data_module;
					unset($modules[$module_id]);
				}
				function order_module_group_keys ($value, $key) {
					$value['children'] = array_merge($value['children']);
					return $value;
				}
				$data_agents = array_map('order_module_group_keys', $data_agents);
			}
			foreach ($agents as $id => $agent) {
				if (!isset($data_agents[$id])) {
					$data_agents[$id] = array();
					$data_agents[$id]['id'] = (int) $id;
					$data_agents[$id]['name'] = io_safe_output($agent['nombre']);
					$data_agents[$id]['type'] = 'agent';
					$data_agents[$id]['color'] = COL_NOTINIT;
				}
			}
			$agents = null;
		}
	}

	function iterate_group_array ($groups, &$data_agents) {
		
		$data = array();

		foreach ($groups as $id => $group) {

			$group_aux = array();
			$group_aux['id'] = (int) $id;
			$group_aux['name'] = io_safe_output($group['nombre']);
			$group_aux['show_name'] = true;
			$group_aux['parent'] = (int) $group['parent'];
			$group_aux['type'] = 'group';
			$group_aux['size'] = 100;
			$group_aux['status'] = groups_get_status($id);

			switch ($group_aux['status']) {
				case AGENT_STATUS_CRITICAL:
					$group_aux['color'] = COL_CRITICAL;
					break;
				
				case AGENT_STATUS_WARNING:
				case AGENT_STATUS_ALERT_FIRED:
					$group_aux['color'] = COL_WARNING;
					break;

				case AGENT_STATUS_NORMAL:
					$group_aux['color'] = COL_NORMAL;
					break;

				case AGENT_STATUS_UNKNOWN:
				default:
					$group_aux['color'] = COL_UNKNOWN;
					break;
			}

			$tooltip_content = html_print_image("images/groups_small/" . $group['icon'] . ".png", true) . "&nbsp;" . __('Group') . ": <b>" . $group_aux['name'] . "</b>";
			$group_aux['tooltip_content'] = $tooltip_content;

			if (!isset($group['children']))
				$group_aux['children'] = array();
			if (!empty($group['children']))
				$group_aux['children'] = iterate_group_array($group['children'], $data_agents);

			$agents = extract_agents_with_group_id($data_agents, (int) $id);

			if (!empty($agents))
				$group_aux['children'] = array_merge($group_aux['children'], $agents);
			
			$data[] = $group_aux;
		}

		return $data;
	}

	function extract_agents_with_group_id (&$agents, $group_id) {
		$valid_agents = array();
		foreach ($agents as $id => $agent) {
			if (isset($agent['group']) && $agent['group'] == $group_id) {
				$valid_agents[$id] = $agent;
				unset($agents[$id]);
			}
		}
		if (!empty($valid_agents))
			return $valid_agents;
		else
			return false;
	}

	$graph_data = array('name' => __('Main node'), 'children' => iterate_group_array($data_groups, $data_agents), 'color' => '#3F3F3F');
	
	if (empty($graph_data['children']))
		return fs_error_image();

	include_once($config['homedir'] . "/include/graphs/functions_d3.php");

	return d3_sunburst_graph ($graph_data, $width, $height, true);
}

?>
