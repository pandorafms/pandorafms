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

include_once($config["homedir"] . "/include/graphs/fgraph.php");
include_once($config["homedir"] . "/include/functions_reporting.php");
include_once($config['homedir'] . "/include/functions_agents.php");
include_once($config['homedir'] . "/include/functions_modules.php");
include_once($config['homedir'] . '/include/functions_users.php');

define("GRAPH_AREA", 0);
define("GRAPH_STACKED_AREA", 1);
define("GRAPH_LINE", 2);
define("GRAPH_STACKED_LINE", 3);

function grafico_modulo_sparse ($agent_module_id, $period, $show_events,
				$width, $height , $title = '', $unit_name = null,
				$show_alerts = false, $avg_only = 0, $pure = false,
				$date = 0, $unit = '', $baseline = 0, $return_data = 0, $show_title = true,
				$only_image = false, $homeurl = '', $ttl = 1, $projection = false) {

	global $config;
	global $graphic_type;

	enterprise_include_once("include/functions_reporting.php");
	
	// Set variables
	if ($date == 0) $date = get_system_time();
	$datelimit = $date - $period;
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
		array ('datos', 'utimestamp'), 'AND', true);
		
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
			if (!$projection){
				return false;
			}
			else{
				return fs_error_image ();
			}
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
		$previous_data = $data[0]['datos'];
		$j++;
	}
	else {
		$previous_data = 0;
	}

	// Get baseline data
	if ($baseline) {
		$baseline_data = array ();
		if ($baseline == 1) {
			$baseline_data = enterprise_hook ('reporting_enterprise_get_baseline', array ($agent_module_id, $period, $width, $height , $title, $unit_name, $date));
			if ($baseline_data === ENTERPRISE_NOT_HOOK) {
				$baseline_data = array ();
			}
		}
	}

	if (empty($unit)){
		$unit = modules_get_unit($agent_module_id);
	}

	// Calculate chart data
	for ($i = 0; $i < $resolution; $i++) {
		$timestamp = $datelimit + ($interval * $i);

		$total = 0;
		$count = 0;
		
		// Read data that falls in the current interval
		$interval_min = false;
		$interval_max = false;
		while (isset ($data[$j]) && $data[$j]['utimestamp'] >= $timestamp && $data[$j]['utimestamp'] < ($timestamp + $interval)) {
			if ($interval_min === false) {
				$interval_min = $data[$j]['datos'];
			}
			if ($interval_max === false) {
				$interval_max = $data[$j]['datos'];
			}

			if ($data[$j]['datos'] > $interval_max) {
				$interval_max = $data[$j]['datos'];
			} else if ($data[$j]['datos'] < $interval_max) {
				$interval_min = $data[$j]['datos'];
			}
			$total += $data[$j]['datos'];
			$count++;
			$j++;
		}

		// Data in the interval
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
		
		if (!$flash_chart) {
			// Set the title and time format
			if ($period <= 21600) {
				$time_format = 'H:i:s';
			}
			elseif ($period < 86400) {
				$time_format = 'H:i';
			}
			elseif ($period < 1296000) {
				$time_format = "M \nd H:i";
			}
			elseif ($period < 2592000) {
				$time_format = "M \nd H\h";
			} 
			else {
				$time_format = "M \nd H\h";
			}
		}
		else {
			// Set the title and time format
			if ($period <= 21600) {
				$time_format = 'H:i:s';
			}
			elseif ($period < 86400) {
				$time_format = 'H:i';
			}
			elseif ($period < 1296000) {
				$time_format = "M d H:i";
			}
			elseif ($period < 2592000) {
				$time_format = "M d H\h";
			} 
			else {
				$time_format = "M d H\h";
			}		
		}

		$timestamp_short = date($time_format, $timestamp);
		$long_index[$timestamp_short] = date(
			html_entity_decode($config['date_format'], ENT_QUOTES, "UTF-8"), $timestamp);
		if (!$projection){
			$timestamp = $timestamp_short;
		}
		// Data
		if ($count > 0) {
			if ($avg_only) {
				$chart[$timestamp]['sum'] = $total;
			}
			else {
				//$chart[$timestamp]['utimestamp'] = $timestamp;
				//$chart[$timestamp]['datos'] = $total;
				$chart[$timestamp]['sum'] = $total;
				$chart[$timestamp]['min'] = $interval_min;
				$chart[$timestamp]['max'] = $interval_max;
			}
			$previous_data = $total;
		// Compressed data
		}
		else {
			if ($uncompressed_module || ($timestamp > time ())) {
				if ($avg_only) {
					$chart[$timestamp]['sum'] = 0;
				}
				else {
					$chart[$timestamp]['sum'] = 0;
					$chart[$timestamp]['min'] = 0;
					$chart[$timestamp]['max'] = 0;
				}
			}
			else {
				if ($avg_only) {
					$chart[$timestamp]['sum'] = $previous_data;
				}
				else {
					$chart[$timestamp]['sum'] = $previous_data;
					$chart[$timestamp]['min'] = $previous_data;
					$chart[$timestamp]['max'] = $previous_data;
				}
			}
		}
		
		//$chart[$timestamp]['count'] = 0;
		/////////
		//$chart[$timestamp]['timestamp_bottom'] = $timestamp;
		//$chart[$timestamp]['timestamp_top'] = $timestamp + $interval;
		/////////
		if($show_events) {
			$chart[$timestamp]['event'] = $event_value;
		}
		if($show_alerts) {
			$chart[$timestamp]['alert'] = $alert_value;
		}
		if ($baseline) {
			$chart[$timestamp]['baseline'] = array_shift ($baseline_data);
			if ($chart[$timestamp]['baseline'] == NULL) {
				$chart[$timestamp]['baseline'] = 0;
			}
		}
	}
	
	// Return chart data and don't draw
	if ($return_data == 1) {
		return $chart;
	}	
	
	// Get min, max and avg (less efficient but centralized for all modules and reports)
	$min_value = round(reporting_get_agentmodule_data_min ($agent_module_id, $period, $date), 2);
	$max_value = round(reporting_get_agentmodule_data_max ($agent_module_id, $period, $date), 2);
	$avg_value = round(reporting_get_agentmodule_data_average ($agent_module_id, $period, $date), 2);

	// Fix event and alert scale
	$event_max = $max_value * 1.25;
	foreach ($chart as $timestamp => $chart_data) {
		if ($show_events && $chart_data['event'] > 0) {
			$chart[$timestamp]['event'] = $event_max;
		}
		if ($show_alerts && $chart_data['alert'] > 0) {
			$chart[$timestamp]['alert'] = $event_max;
		}
	}

    // Only show caption if graph is not small
    if ($width > MIN_WIDTH_CAPTION && $height > MIN_HEIGHT)
    	//Flash chart
		$caption = __('Max. Value') . ': ' . $max_value . '    ' . __('Avg. Value') . ': ' . $avg_value . '    ' . __('Min. Value') . ': ' . $min_value . '    ' . __('Units. Value') . ': ' . $unit;
    else
		$caption = array();
	
	///////
	// Color commented not to restrict serie colors
	/*$color = array();
	$color['sum'] = array('border' => '#000000', 'color' => $config['graph_color2'], 'alpha' => 50);
	if($show_events) {
		$color['event'] = array('border' => '#ff7f00', 'color' => '#ff7f00', 'alpha' => 50);
	}
	if($show_alerts) {
		$color['alert'] = array('border' => '#ff0000', 'color' => '#ff0000', 'alpha' => 50);
	}
	$color['max'] = array('border' => '#000000', 'color' => $config['graph_color3'], 'alpha' => 50);
	$color['min'] = array('border' => '#000000', 'color' => $config['graph_color1'], 'alpha' => 50);
	$color['baseline'] = array('border' => null, 'color' => '#0097BD', 'alpha' => 10);
	$color['unit'] = array('border' => null, 'color' => '#0097BC', 'alpha' => 10);		*/
	
	$legend = array();
	$legend['sum'] = __('Avg') . ' (' . $avg_value . ')';
	if($show_events) {
		$legend['event'] = __('Events');
	}
	if($show_alerts) {
		$legend['alert'] = __('Alerts');
	}
	$legend['max'] = __('Max') . ' (' .format_for_graph($max_value) . ')';
	$legend['min'] = __('Min') . ' (' . format_for_graph($min_value) . ')';
	$legend['baseline'] = __('Baseline');
	
	$flash_chart = $config['flash_charts'];
	if ($only_image) {
		$flash_chart = false;
	}

	if ($flash_chart) {
		include_flash_chart_script($homeurl);
	}
	
	// Color commented not to restrict serie colors
	return area_graph($flash_chart, $chart, $width, $height, '' /*$color*/ ,$legend,
		$long_index, "images/image_problem.opaque.png", "", "", $homeurl,
		 $config['homedir'] .  "/images/logo_vertical_water.png",
		 $config['fontpath'], $config['font_size'], $unit, $ttl);
}

function graph_get_formatted_date($timestamp, $format1, $format2) {
	global $config;
	
	if($config['flash_charts']) {
		$date = date("$format1 $format2", $timestamp);
	}
	else {
		$date = date($format1, $timestamp);
		if($format2 != '') {
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
 * 
 * @return Mixed 
 */
function graphic_combined_module ($module_list, $weight_list, $period, $width, $height,
		$title, $unit_name, $show_events = 0, $show_alerts = 0, $pure = 0,
		$stacked = 0, $date = 0, $only_image = false, $homeurl = '', $ttl = 1, $projection = false, $prediction_period = false) {
	global $config;
	global $graphic_type;

	$time_format_2 = '';
	$temp_range = $period;
	
	if ($projection != false){
		if ($period < $prediction_period)
			$temp_range = $prediction_period;
	}
		
	// Set the title and time format
	if ($temp_range <= 21600) {
		$time_format = 'H:i:s';
	}
	elseif ($temp_range < 86400) {
		$time_format = 'H:i';
	}
	elseif ($temp_range < 1296000) {
		$time_format = 'M d';
		$time_format_2 = 'H:i';
		if ($projection != false){
			$time_format_2 = 'H\h';
		}
	}
	elseif ($temp_range <= 2592000) {
		$time_format = 'M d';
		$time_format_2 = 'H\h';
	} 
	else {
		$time_format = 'M d';
	}
	
	// Set variables
	if ($date == 0) $date = get_system_time();
	$datelimit = $date - $period;
	$resolution = $config['graph_res'] * 50; //Number of points of the graph
	$interval = (int) ($period / $resolution);	
	
	// If projection graph, fill with zero previous data to projection interval	
	if ($projection != false){
		$j = $datelimit;
		$in_range = true;
		while ($in_range){
			$timestamp_f = graph_get_formatted_date($j, $time_format, $time_format_2);
			
			//$timestamp_f = date('d M Y H:i:s', $j);
			$before_projection[$timestamp_f] = 0;
						
			if ($j > $date){
				$in_range = false;
			}	
			$j = $j + $interval;					
		}	
	}	

	// Added support for projection graphs (normal_module + 1(prediction data))
	if ($projection !== false){ 
		$module_number = count ($module_list) + 1;
	}else{
		$module_number = count ($module_list);		
	}

	// interval - This is the number of "rows" we are divided the time to fill data.
	//	     more interval, more resolution, and slower.
	// periodo - Gap of time, in seconds. This is now to (now-periodo) secs

	// Init weights
	for ($i = 0; $i < $module_number; $i++) {
		if (! isset ($weight_list[$i])) {
			$weight_list[$i] = 1;
		} else if ($weight_list[$i] == 0) {
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
	
	// Calculate data for each module
	for ($i = 0; $i < $module_number; $i++) {
		// If its a projection graph, first module will be data and second will be the projection
		if ($projection != false and $i != 0){
			$agent_module_id = $module_list[0];
			$agent_name = modules_get_agentmodule_agent_name ($agent_module_id);
			$agent_id = agents_get_agent_id ($agent_name);
			$module_name = "projection for " . io_safe_output(modules_get_agentmodule_name ($agent_module_id));
			$module_name_list[$i] = substr($agent_name, 0,80) ." / ".substr ($module_name, 0, 40);
			$id_module_type = modules_get_agentmodule_type ($agent_module_id);
			$module_type = modules_get_moduletype_name ($id_module_type);
			$uncompressed_module = is_module_uncompressed ($module_type);			
		}else{
			$agent_module_id = $module_list[$i];
			$agent_name = modules_get_agentmodule_agent_name ($agent_module_id);
			$agent_id = agents_get_agent_id ($agent_name);
			$module_name = io_safe_output(modules_get_agentmodule_name ($agent_module_id));
			$module_name_list[$i] = substr($agent_name, 0,80) ." / ".substr ($module_name, 0, 40);
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
			array ('datos', 'utimestamp'));
		if ($data === false) {
			$data = array ();
		}
	
		// Uncompressed module data
		if ($uncompressed_module) {
			$min_necessary = 1;

		// Compressed module data
		} else {
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
			} else if (count ($data) > 0) {
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
			$interval_min = $previous_data;
			$interval_max = $previous_data;
			while (isset ($data[$j]) && $data[$j]['utimestamp'] >= $timestamp && $data[$j]['utimestamp'] < ($timestamp + $interval)) {
				if ($data[$j]['datos'] > $interval_max) {
					$interval_max = $data[$j]['datos'];
				} else if ($data[$j]['datos'] < $interval_max) {
					$interval_min = $data[$j]['datos'];
				}
				$total += $data[$j]['datos'];
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
				
				$previous_data = $total;
			// Compressed data
			} else {
				if ($uncompressed_module || ($timestamp > time ())) {
					//$graph_values[$i][$timestamp] = 0;
					$temp_graph_values[$timestamp_short] = 0;
				}
				else {
					//$graph_values[$i][$timestamp] = $previous_data * $weight_list[$i];
					$temp_graph_values[$timestamp_short] = $previous_data * $weight_list[$i];
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
			if ($projection != false and $i != 0){
					$projection_data = array();
					$projection_data = array_merge($before_projection, $projection); 
					$graph_values[$i] = $projection_data;
			}else{
					$graph_values[$i] = $temp_graph_values; 
			}
		}

		//Add the max, min and avg in the legend
		$avg = round($avg / $countAvg, 1);
		
		$min = format_for_graph($min);
		$max = format_for_graph($max);
		$avg = format_for_graph($avg);
		$units = modules_get_unit($agent_module_id);
		
		if ($projection == false or ($projection != false and $i == 0)){
			$module_name_list[$i] .= " (".__("Max"). ":$max, ".__("Min"). ":$min, ". __("Avg"). ": $avg, ". __("Units"). ": $units)";
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

	foreach ($graph_values as $graph_group => $point) {
		foreach ($point as $timestamp_point => $point_value) {
			$temp[$timestamp_point][$graph_group] = $point_value;
		}
	}
	$graph_values = $temp;

	/*
	for ($i = 0; $i < $module_number; $i++) {
		if ($weight_list[$i] != 1) {
			$module_name_list[$i] .= " (x". format_numeric ($weight_list[$i], 1).")";
		}
	}
	*/

	$flash_charts = $config['flash_charts'];

	if ($only_image) {
		$flash_charts = false;
	}

			
	if ($flash_charts){
		include_flash_chart_script();
	}	

	switch ($stacked) {
		case GRAPH_AREA:
			$color = null; 
			return area_graph($flash_charts, $graph_values, $width, $height,
				$color, $module_name_list, $long_index, $homeurl."images/image_problem.opaque.png",
				"", "", $homeurl, $config['homedir'] .  "/images/logo_vertical_water.png",
				$config['fontpath'], $config['font_size'], "", $ttl); 
			break;
		default:
		case GRAPH_STACKED_AREA: 
			$color = null;
			return stacked_area_graph($flash_charts, $graph_values, $width, $height,
				$color, $module_name_list, $long_index, $homeurl."images/image_problem.opaque.png",
				"", "", $config['homedir'] .  "/images/logo_vertical_water.png",
				$config['fontpath'], $config['font_size'], "", $ttl, $homeurl);
			break;
		case GRAPH_LINE:  
			$color = null;
			return line_graph($flash_charts, $graph_values, $width, $height,
				$color, $module_name_list, $long_index, $homeurl."images/image_problem.opaque.png",
				"", "", $config['homedir'] .  "/images/logo_vertical_water.png",
				$config['fontpath'], $config['font_size'], "", $ttl, $homeurl); 
			break;
		case GRAPH_STACKED_LINE:
			$color = null;
			return stacked_line_graph($flash_charts, $graph_values, $width, $height,
				$color, $module_name_list, $long_index, $homeurl."images/image_problem.opaque.png",
				"", "", $config['homedir'] .  "/images/logo_vertical_water.png",
				$config['fontpath'], $config['font_size'], "", $ttl, $homeurl);
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
 */
function graphic_agentaccess ($id_agent, $width, $height, $period = 0) {
	global $config;
	global $graphic_type;
	
	include_flash_chart_script();

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
		} else {
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
	
	if ($empty_data)
		echo fs_error_image();
	else {
		echo area_graph($config['flash_charts'], $data, $width, $height,
			null, null, null, "images/image_problem.opaque.png", "", "", "",
			 $config['homedir'] .  "/images/logo_vertical_water.png",
			 $config['fontpath'], $config['font_size'], "");
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

	$data = array ();
	$max_items = 6;
	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":
			$sql = sprintf ('SELECT COUNT(id_evento) as count_number, nombre
				FROM tevento, tagente_modulo
				WHERE id_agentmodule = id_agente_modulo
					AND disabled = 0 AND tevento.id_agente = %d
				GROUP BY id_agentmodule, nombre LIMIT %d', $id_agent, $max_items);
			break;
		case "oracle":
			$sql = sprintf ('SELECT COUNT(id_evento) as count_number, dbms_lob.substr(nombre,4000,1) as nombre
				FROM tevento, tagente_modulo
				WHERE (id_agentmodule = id_agente_modulo
					AND disabled = 0 AND tevento.id_agente = %d) AND rownum <= %d
				GROUP BY id_agentmodule, dbms_lob.substr(nombre,4000,1)', $id_agent, $max_items);
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
		$data[$event['nombre'].' ('.$event['count_number'].')'] = $event["count_number"];
	}
	
	/* System events */
	$sql = "SELECT COUNT(*) FROM tevento WHERE id_agentmodule = 0 AND id_agente = $id_agent";
	$value = db_get_sql ($sql);
	if ($value > 0) {
		$data[__('System').' ('.$value.')'] = $value;
	}
	asort ($data);
	
	return pie3d_graph($config['flash_charts'], $data, $width, $height, __("other"),
		'', $config['homedir'] .  "/images/logo_vertical_water.png",
		$config['fontpath'], $config['font_size']);
}

function progress_bar($progress, $width, $height, $title = '', $mode = 1) {
	global $config;
	
	$out_of_lim_str = __("Out of limits");
	$title = "";
	
	require_once("include_graph_dependencies.php");
	include_graphs_dependencies($config['homedir'].'/');

	return "<img title='" . $title . "' alt='" . $title . "' src='include/graphs/fgraph.php?homeurl=../../&graph_type=progressbar&width=".$width."&height=".$height."&progress=".$progress.
		"&mode=" . $mode . "&out_of_lim_str=".$out_of_lim_str."&title=".$title."&font=".$config['fontpath']."' />";
}

function graph_sla_slicebar ($id, $period, $sla_min, $sla_max, $date, $daysWeek = null, $time_from = null, $time_to = null, $width, $height, $home_url) {
	global $config;

	$data = reporting_get_agentmodule_sla_array ($id, $period, $sla_min, $sla_max, $date, $daysWeek, $time_from, $time_to);
	$colors = 	array(1 => '#38B800', 2 => '#FFFF00', 3 => '#FF0000', 4 => '#C3C3C3');

	return slicesbar_graph($data, $period, $width, $height, $colors, $config['fontpath'],
		$config['round_corner'], $home_url);
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
	
	include_flash_chart_script();

	if ($id_agent < 1) {
		$id_agent = -1;
		$query = "";
	} else {
		$modules = agents_get_modules ($id_agent);
		$query = sprintf (" AND id_agente_modulo IN (%s)", implode (",", array_keys ($modules)));
	}
	
	// All data (now)
	$time["all"] = get_system_time ();

	// 1 day ago
	$time["1day"] = $time["all"] - 86400;

	// 1 week ago
	$time["1week"] = $time["all"] - 604800;

	// 1 month ago
	$time["1month"] = $time["all"] - 2592000;

	// Three months ago
	$time["3month"] = $time["all"] - 7776000;
	
	$data[__("Today")]        = db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos WHERE utimestamp > %d %s", $time["1day"], $query), 0, true);
	$data["1 ".__("Week")]    = db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos WHERE utimestamp > %d %s", $time["1week"], $query), 0, true);
	$data["1 ".__("Month")]   = db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos WHERE utimestamp > %d %s", $time["1month"], $query), 0, true);
	$data["3 ".__("Months")]  = db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos WHERE utimestamp > %d %s", $time["3month"], $query), 0, true);
	$data[__("Older")]        = db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos WHERE 1=1 %s", $query));
	
	$data[__("Today")]       += db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_string WHERE utimestamp > %d %s", $time["1day"], $query), 0, true);
	$data["1 ".__("Week")]   += db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_string WHERE utimestamp > %d %s", $time["1week"], $query), 0, true);
	$data["1 ".__("Month")]  += db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_string WHERE utimestamp > %d %s", $time["1month"], $query), 0, true);
	$data["3 ".__("Months")] += db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_string WHERE utimestamp > %d %s", $time["3month"], $query), 0, true);
	$data[__("Older")]       += db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_string WHERE 1=1 %s", $query), 0, true);

	$data[__("Today")]       += db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_log4x WHERE utimestamp > %d %s", $time["1day"], $query), 0, true);
	$data["1 ".__("Week")]   += db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_log4x WHERE utimestamp > %d %s", $time["1week"], $query), 0, true);
	$data["1 ".__("Month")]  += db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_log4x WHERE utimestamp > %d %s", $time["1month"], $query), 0, true);
	$data["3 ".__("Months")] += db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_log4x WHERE utimestamp > %d %s", $time["3month"], $query), 0, true);
	$data[__("Older")]       += db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_log4x WHERE 1=1 %s", $query), 0, true);

	$data[__("Older")] = $data[__("Older")] - $data["3 ".__("Months")];
	
	if ($data[__("Today")] == 0 && $data["1 ".__("Week")] == 0 && 
		$data["1 ".__("Month")] == 0 && $data["3 ".__("Months")] == 0 && $data[__("Older")] == 0) {
		return html_print_image('images/image_problem.png', true);
	}
	
	return pie3d_graph($config['flash_charts'], $data, $width, $height,
		__('Other'), '', $config['homedir'] .  "/images/logo_vertical_water.png",
		$config['fontpath'], $config['font_size']);
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
	
	include_flash_chart_script();

	$data = array ();
	$legend = array ();
	
	$agents = agents_get_group_agents (array_keys (users_get_groups ()), false, "none");
	$count = agents_get_modules_data_count (array_keys ($agents));
	unset ($count["total"]);
	arsort ($count, SORT_NUMERIC);
	$count = array_slice ($count, 0, 8, true);
	
	foreach ($count as $agent_id => $value) {
		$data[$agents[$agent_id]]['g'] = $value;
	}
	
	return hbar_graph($config['flash_charts'], $data, $width, $height, array(),
		$legend, "", "", true, "",
		$config['homedir'] .  "/images/logo_vertical_water.png",
		$config['fontpath'], $config['font_size'], false);
}

/**
 * Print a horizontal bar graph with modules data of agents
 * 
 * @param integer height graph height
 * @param integer width graph width
 */
function graph_db_agentes_modulos2($width, $height) {
	global $config;
	global $graphic_type;
	
	include_flash_chart_script();

	$data = array ();
	
	switch ($config['dbtype']){
		case "mysql":
		case "postgresql":
			$modules = db_get_all_rows_sql ('SELECT COUNT(id_agente_modulo), id_agente
				FROM tagente_modulo
				GROUP BY id_agente
				ORDER BY 1 DESC LIMIT 10');
			break;
		case "oracle":
			$modules = db_get_all_rows_sql ('SELECT COUNT(id_agente_modulo), id_agente
				FROM tagente_modulo
				WHERE rownum <= 10
				GROUP BY id_agente
				ORDER BY 1 DESC');
			break;
	}
	if ($modules === false)
		$modules = array ();
	
	foreach ($modules as $module) {
		$agent_name = agents_get_name ($module['id_agente'], "none");
		switch ($config['dbtype']){
			case "mysql":
			case "postgresql":
				$data[$agent_name]['g'] = $module['COUNT(id_agente_modulo)'];
				break;
			case "oracle":
				$data[$agent_name]['g'] = $module['count(id_agente_modulo)'];
				break;
		}
	}
	
	return hbar_graph($config['flash_charts'], $data, $width, $height, array(),
		array(), "", "", true, "",
		$config['homedir'] .  "/images/logo_vertical_water.png",
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
	
	if($logins == false) {
		$logins = array();
	}
	foreach ($logins as $login) {
		$data[$login['id_usuario']] = $login['n_incidents'];
	}
	
	return pie3d_graph($config['flash_charts'], $data, $width, $height,
		__('Other'), '', $config['homedir'] .  "/images/logo_vertical_water.png",
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
	
	if($incidents == false) {
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
	
	return pie3d_graph($config['flash_charts'], $data, 320, 200,
		__('Other'), '', $config['homedir'] .  "/images/logo_vertical_water.png",
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
	
	return pie3d_graph($config['flash_charts'], $data, 370, 180,
		__('Other'), '', $config['homedir'] .  "/images/logo_vertical_water.png",
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
	$sql = sprintf ('SELECT COUNT(id_incidencia) n_incidents, nombre
		FROM tincidencia,tgrupo
		WHERE tgrupo.id_grupo = tincidencia.id_grupo
		GROUP BY tgrupo.id_grupo ORDER BY 1 DESC LIMIT %d',
		$max_items);
	$incidents = db_get_all_rows_sql ($sql);
	
	$sql = sprintf ('SELECT COUNT(id_incidencia) n_incidents
		FROM tincidencia
		WHERE tincidencia.id_grupo = 0');
		
	$incidents_all = db_get_value_sql($sql);
		
	if($incidents == false) {
		$incidents = array();
	}
	foreach ($incidents as $incident) {		
		$data[$incident['nombre']] = $incident['n_incidents'];
	}
	
	if($incidents_all > 0) {
		$data[__('All')] = $incidents_all;
	}
	
	return pie3d_graph($config['flash_charts'], $data, 320, 200,
		__('Other'), '', $config['homedir'] .  "/images/logo_vertical_water.png",
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
	$sql = sprintf ('SELECT COUNT(id_incidencia) n_incidents, id_usuario
		FROM tincidencia
		GROUP BY id_usuario
		ORDER BY 1 DESC LIMIT %d', $max_items);
	$incidents = db_get_all_rows_sql ($sql);
	
	if($incidents == false) {
		$incidents = array();
	}
	foreach ($incidents as $incident) {
		if($incident['id_usuario'] == false) {
			$name = __('System');
		}
		else {
			$name = $incident['id_usuario'];
		}

		$data[$name] = $incident['n_incidents'];
	}
	
	return pie3d_graph($config['flash_charts'], $data, 320, 200,
		__('Other'), '', $config['homedir'] .  "/images/logo_vertical_water.png",
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
				FROM tincidencia GROUP BY `origen`
				ORDER BY 1 DESC LIMIT %d', $max_items);
			break;
		case "postgresql":
			$sql = sprintf ('SELECT COUNT(id_incidencia) n_incident, origen 
				FROM tincidencia GROUP BY "origen"
				ORDER BY 1 DESC LIMIT %d', $max_items);
			break;
		case "oracle":
			$sql = sprintf ('SELECT COUNT(id_incidencia) n_incident, origen 
				FROM tincidencia WHERE rownum <= %d GROUP BY origen
				ORDER BY 1 DESC', $max_items);
			break;
	}
	$origins = db_get_all_rows_sql ($sql);
	
	if($origins == false) {
		$origins = array();
	}
	foreach ($origins as $origin) {
		$data[$origin['origen']] = $origin['n_incident'];
	}
	
	return pie3d_graph($config['flash_charts'], $data, $width, $height,
		__('Other'), '', $config['homedir'] .  "/images/logo_vertical_water.png",
		$config['fontpath'], $config['font_size']);
}

/**
 * Print a pie graph with events data of group
 * 
 * @param integer width pie graph width
 * @param integer height pie graph height
 * @param string url
 */
function grafico_eventos_grupo ($width = 300, $height = 200, $url = "") {
	global $config;
	global $graphic_type;

	$url = html_entity_decode (rawurldecode ($url), ENT_QUOTES); //It was urlencoded, so we urldecode it
	$data = array ();
	$loop = 0;
	define ('NUM_PIECES_PIE', 6);	

	$badstrings = array (";", "SELECT ", "DELETE ", "UPDATE ", "INSERT ", "EXEC");	
	//remove bad strings from the query so queries like ; DELETE FROM  don't pass
	$url = str_ireplace ($badstrings, "", $url);
		
	//This will give the distinct id_agente, give the id_grupo that goes
	//with it and then the number of times it occured. GROUP BY statement
	//is required if both DISTINCT() and COUNT() are in the statement 
	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":
			$sql = sprintf ('SELECT DISTINCT(id_agente) AS id_agente, id_grupo, COUNT(id_agente) AS count
				FROM tevento WHERE 1=1 %s
				GROUP BY id_agente ORDER BY count DESC', $url); 
			break;
		case "oracle":
			$sql = sprintf ('SELECT DISTINCT(id_agente) AS id_agente, id_grupo, COUNT(id_agente) AS count
				FROM tevento WHERE 1=1 %s
				GROUP BY id_agente, id_grupo ORDER BY count DESC', $url); 
			break;
	}
	
	$result = db_get_all_rows_sql ($sql);
	if ($result === false) {
		$result = array();
	}
 
	foreach ($result as $row) {
		if (!check_acl ($config["id_user"], $row["id_grupo"], "AR") == 1)
			continue;
		
		if ($loop >= NUM_PIECES_PIE) {
			if (!isset ($data[__('Other')]))
				$data[__('Other')] = 0;
			$data[__('Other')] += $row["count"];
		} else {
			if ($row["id_agente"] == 0) {
				$name = __('SYSTEM')." (".$row["count"].")";
			} else {
				$name = mb_substr (agents_get_name ($row["id_agente"], "lower"), 0, 14)." (".$row["count"].")";
			}
			$data[$name] = $row["count"];
		}
		$loop++;
	}
	if ($config['flash_charts']){
		include_flash_chart_script();
	}
	return pie3d_graph($config['flash_charts'], $data, $width, $height,
		__('Other'), '', $config['homedir'] .  "/images/logo_vertical_water.png",
		$config['fontpath'], $config['font_size']);
}

/**
 * Print a pie graph with events data in 320x200 size
 * 
 * @param string filter Filter for query in DB
 */
function grafico_eventos_total($filter = "") {
	global $config;
	global $graphic_type;

	$filter = str_replace  ( "\\" , "", $filter);
	$data = array ();
	$legend = array ();
	$total = 0;
	
	$sql = "SELECT COUNT(id_evento) FROM tevento WHERE criticity = 0 $filter";
	$data[__('Maintenance')] = db_get_sql ($sql);
	if ($data[__('Maintenance')] == 0) {
		unset($data[__('Maintenance')]);
	}
	
	$sql = "SELECT COUNT(id_evento) FROM tevento WHERE criticity = 1 $filter";
	$data[__('Informational')] = db_get_sql ($sql);
	if ($data[__('Informational')] == 0) {
		unset($data[__('Informational')]);
	}

	$sql = "SELECT COUNT(id_evento) FROM tevento WHERE criticity = 2 $filter";
	$data[__('Normal')] = db_get_sql ($sql);
	if ($data[__('Normal')] == 0) {
		unset($data[__('Normal')]);
	}

	$sql = "SELECT COUNT(id_evento) FROM tevento WHERE criticity = 3 $filter";
	$data[__('Warning')] = db_get_sql ($sql);
	if ($data[__('Warning')] == 0) {
		unset($data[__('Warning')]);
	}

	$sql = "SELECT COUNT(id_evento) FROM tevento WHERE criticity = 4 $filter";
	$data[__('Critical')] = db_get_sql ($sql);
	if ($data[__('Critical')] == 0) {
		unset($data[__('Critical')]);
	}
	
	asort ($data);
	
	return pie3d_graph($config['flash_charts'], $data, 320, 200,
		__('Other'), '', $config['homedir'] .  "/images/logo_vertical_water.png",
		$config['fontpath'], $config['font_size']);
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
			$sql = sprintf ('SELECT * FROM (SELECT COUNT(id_evento) events, id_usuario
				FROM tevento
				GROUP BY id_usuario
				ORDER BY 1 DESC) WHERE rownum <= %d', $max_items);
			break;
	}
	$events = db_get_all_rows_sql ($sql);

	if ($events === false) {
		$events = array();
	}
	
	foreach($events as $event) {
		if($event['id_usuario'] == '0') {
			$data[__('System')] = $event['events'];
		}
		else {
			$data[$event['id_usuario']] = $event['events'];
		}
	}

	return pie3d_graph($config['flash_charts'], $data, $width, $height,
		__('Other'), '', $config['homedir'] .  "/images/logo_vertical_water.png",
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
function graph_custom_sql_graph ($id, $width, $height, $type = 'sql_graph_vbar', $only_image = false, $homeurl = '', $ttl = 1) {
	global $config;

    $report_content = db_get_row ('treport_content', 'id_rc', $id);
    if ($report_content["external_source"] != ""){
        $sql = io_safe_output ($report_content["external_source"]);
    }
    else {
    	$sql = db_get_row('treport_custom_sql', 'id', $report_content["treport_custom_sql_id"]);
    	$sql = io_safe_output($sql['sql']);
    }
    
	$data_result = db_get_all_rows_sql ($sql);

	if ($data_result === false)
		$data_result = array ();

	$data = array ();
	
	$count = 0;
	foreach ($data_result as $data_item) {
		$count++;
	    switch ($type) {
	        case 'sql_graph_vbar': // vertical bar
	            $data[$data_item["label"]]['g'] = $data_item["value"];
	            break;
	        case 'sql_graph_hbar': // horizontal bar
	        	$data[$data_item["label"]]['g'] = $data_item["value"];
	            break;
	        case 'sql_graph_pie': // Pie
	            $data[$data_item["label"]] = $data_item["value"];
	            break;
	    }
	}
	
	$flash_charts = $config['flash_charts'];

	if ($flash_charts){ 
		include_flash_chart_script();
	}
	
	if ($only_image) {
		$flash_charts = false;
	}
	
    switch ($type) {
        case 'sql_graph_vbar': // vertical bar
        	return vbar_graph($flash_charts, $data, $width, $height, array(),
        		array(), "", "", $homeurl,
        		$config['homedir'] .  "/images/logo_vertical_water.png",
        		$config['fontpath'], $config['font_size'], false, $ttl);
            break;
        case 'sql_graph_hbar': // horizontal bar
        	return hbar_graph($flash_charts, $data, $width, $height, array(),
        		array(), "", "", $homeurl,
        		$config['homedir'] .  "/images/logo_vertical_water.png",
        		$config['fontpath'], $config['font_size'], false, $ttl);
            break;
        case 'sql_graph_pie': // Pie
            return pie3d_graph($flash_charts, $data, $width, $height, __("other"), $homeurl,
            	$config['homedir'] .  "/images/logo_vertical_water.png", $config['fontpath'], '', $ttl);
            break;
    }
}

/**
 * Print a graph with event data of agents
 * 
 * @param integer id_agent Agent ID
 * @param integer width pie graph width
 * @param integer height pie graph height
 * @param integer period time period
 */
function graphic_agentevents ($id_agent, $width, $height, $period = 0) {
	global $config;
	global $graphic_type;
	
	if ($config['flash_charts']) {
		include_flash_chart_script();
	}

	$data = array ();

	$resolution = $config['graph_res'] * ($period * 2 / $width); // Number of "slices" we want in graph

	$interval = (int) ($period / $resolution);
	$date = get_system_time ();
	$datelimit = $date - $period;
	$periodtime = floor ($period / $interval);
	$time = array ();
	$data = array ();
	
	for ($i = 0; $i < $interval; $i++) {
		$bottom = $datelimit + ($periodtime * $i);
		if (! $graphic_type) {
			$name = date('H\h', $bottom);
		} else {
			$name = $bottom;
		}

		$top = $datelimit + ($periodtime * ($i + 1));
		$criticity = (int) db_get_value_filter ('criticity',
			'tevento',
			array ('id_agente' => $id_agent,
				'utimestamp > '.$bottom,
				'utimestamp < '.$top));

		switch ($criticity) {
			case 3: $data[$name] = 'E5DF63';
					break;
			case 4: $data[$name] = 'FF3C4B';
					break;
			default: $data[$name] = '9ABD18';
		}
		
	}

	if (! $graphic_type) {
		return fs_agent_event_chart ($data, $width, $height, $resolution / 750);
	}
}

// Clean FLASH string strips non-valid characters for flashchart
function clean_flash_string ($string) {
	$string = html_entity_decode ($string, ENT_QUOTES, "UTF-8");
	$string = str_replace('&', '', $string);
	$string = str_replace(' ', '', $string);
	$string = str_replace ('"', '', $string);
	return substr ($string, 0, 20);
}

// Returns a Pandora FMS agent event chart
function fs_agent_event_chart ($data, $width, $height, $step = 1) {
	global $config;

	if (sizeof ($data) == 0) {
		return fs_error_image ();
	}

	// Generate the XML
	$chart = new FusionCharts('Area2D', $width, $height);
	
	$count = 0;
	$num_vlines = 0;
	foreach ($data as $name => $value) {
			
		if (($step >= 1) && ($count++ % $step == 0)) {
			$show_name = '1';
			$num_vlines++;
		} else {
			$show_name = '0';
		}
		$chart->addChartData(1, 'name=' . clean_flash_string($name) . ';showName=' . $show_name . ';color=' . $value);
	}

	$chart->setChartParams('numDivLines=0;numVDivLines=0;showNames=1;rotateNames=0;showValues=0;baseFontSize=9;showLimits=0;showAreaBorder=0;areaBorderThickness=1;canvasBgColor=9ABD18');

	// Return the code
	return get_chart_code ($chart, $width, $height, 'include/FusionCharts/FCF_Area2D.swf');
}

// Prints an error image
function fs_error_image () {
	global $config;

	return html_print_image("images/image_problem.png", true, array("border" => '0'));
}


function grafico_modulo_boolean ($agent_module_id, $period, $show_events,
	 $width, $height , $title, $unit_name, $show_alerts, $avg_only = 0, $pure=0,
	 $date = 0, $only_image = false, $homeurl = '') {
	global $config;
	global $graphic_type;
	
	include_flash_chart_script($homeurl);

	// Set variables
	if ($date == 0) $date = get_system_time();
	$datelimit = $date - $period;
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

	// Get event data (contains alert data too)
	if ($show_events == 1 || $show_alerts == 1) {
		$events = db_get_all_rows_filter('tevento',
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
		array ('datos', 'utimestamp'));
	if ($data === false) {
		$data = array ();
	}

	// Uncompressed module data
	if ($uncompressed_module) {
		$min_necessary = 1;

	// Compressed module data
	} else {
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
		} else if (count ($data) > 0) {
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

	// Data iterator
	$j = 0;
	
	// Event iterator
	$k = 0;

	// Set initial conditions
	$chart = array();
	if ($data[0]['utimestamp'] == $datelimit) {
		$previous_data = $data[0]['datos'];
		$j++;
	} else {
		$previous_data = 0;
	}

	// Calculate chart data
	for ($i = 0; $i < $resolution; $i++) {
		$timestamp = $datelimit + ($interval * $i);

		$zero = 0;
		$total = 0;
		$count = 0;
		
		// Read data that falls in the current interval
		while (isset ($data[$j]) && $data[$j]['utimestamp'] >= $timestamp && $data[$j]['utimestamp'] <= ($timestamp + $interval)) {
			if ($data[$j]['datos'] == 0) {
				$zero = 1;
			} else {
				$total += $data[$j]['datos'];
				$count++;
			}

			$j++;
		}

		// Average
		if ($count > 0) {
			$total /= $count;
		}

		// Read events and alerts that fall in the current interval
		$event_value = 0;
		$alert_value = 0;
		while (isset ($events[$k]) && $events[$k]['utimestamp'] >= $timestamp && $events[$k]['utimestamp'] < ($timestamp + $interval)) {
			if ($show_events == 1) {
				$event_value++;
			}
			if ($show_alerts == 1 && substr ($events[$k]['event_type'], 0, 5) == 'alert') {
				$alert_value++;
			}
			$k++;
		}
		
		// Set the title and time format
		if ($period <= 21600) {
			$time_format = 'H:i:s';
		}
		elseif ($period < 86400) {
			$time_format = 'H:i';
		}
		elseif ($period < 1296000) {
			$time_format = 'M d H:i';
		}
		elseif ($period < 2592000) {
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

		// Data and zeroes (draw a step)
		if ($zero == 1 && $count > 0) {
			$chart[$timestamp]['sum'] = $total;
			$chart[$timestamp + 1] = array ('sum' => 0,
			                                //'count' => 0,
			                                //'timestamp_bottom' => $timestamp,
			                                //'timestamp_top' => $timestamp + $interval,
			                                'min' => 0,
			                                'max' => 0,
			                                'event' => $event_value,
			                                'alert' => $alert_value);
			$previous_data = 0;
		// Just zeros
		} else if ($zero == 1) {
			$chart[$timestamp]['sum'] = 0;
			$previous_data = 0;
		// No zeros
		} else if ($count > 0) {
			$chart[$timestamp]['sum'] = $total;
			$previous_data = $total;
		// Compressed data
		} else {
			if ($uncompressed_module || ($timestamp > time ())) {
				$chart[$timestamp]['sum'] = 0;
			} else {
				$chart[$timestamp]['sum'] = $previous_data;
			}
		}

		//$chart[$timestamp]['count'] = 0;
		//$chart[$timestamp]['timestamp_bottom'] = $timestamp;
		//$chart[$timestamp]['timestamp_top'] = $timestamp + $interval;
		$chart[$timestamp]['min'] = 0;
		$chart[$timestamp]['max'] = 0;
		if($show_events) {
			$chart[$timestamp]['event'] = $event_value;
		}
		else {
			unset($chart[$timestamp]['event']);
		}
		if ($show_alerts) {
			$chart[$timestamp]['alert'] = $alert_value;
		}
		else {
			unset($chart[$timestamp]['alert']);
		}
		
		
		
	}

	// Get min, max and avg (less efficient but centralized for all modules and reports)
	$min_value = round(reporting_get_agentmodule_data_min ($agent_module_id, $period, $date), 2);
	$max_value = round(reporting_get_agentmodule_data_max ($agent_module_id, $period, $date), 2);
	$avg_value = round(reporting_get_agentmodule_data_average ($agent_module_id, $period, $date), 2);

	// Fix event and alert scale
	$event_max = $max_value * 1.25;
	foreach ($chart as $timestamp => $chart_data) {
		if($show_events) {
			if ($chart_data['event'] > 0) {
				$chart[$timestamp]['event'] = $event_max;
			}
		}
		if ($show_alerts) {
			if ($chart_data['alert'] > 0) {
				$chart[$timestamp]['alert'] = $event_max;
			}
		}
	}
	///////////////////////////////////////////////////
	// Set the title and time format
	if ($period <= 21600) {
		$time_format = 'H:i:s';
	}
	elseif ($period < 86400) {
		$time_format = 'H:i';
	}
	elseif ($period < 1296000) {
		$time_format = 'M d H:i';
	}
	elseif ($period < 2592000) {
		$time_format = 'M d H\h';
	} 
	else {
		$time_format = 'M d H\h';
	}
	
    // Flash chart
	$caption = __('Max. Value') . ': ' . $max_value . '    ' . __('Avg. Value') . 
	': ' . $avg_value . '    ' . __('Min. Value') . ': ' . $min_value;
	
	/////////////////////////////////////////////////////////////////////////////////////////
	$legend = array();
	$legend['sum'] = __('Avg') . ' (' . $avg_value . ')';
	if($show_events) {
		$legend['event'] = __('Events');
	}
	if($show_alerts) {
		$legend['alert'] = __('Alerts');
	}
	$legend['max'] = __('Max') . ' (' .format_for_graph($max_value) . ')';
	$legend['min'] = __('Min') . ' (' . format_for_graph($min_value) . ')';
	$legend['baseline'] = __('Baseline');
	/////////////////////////////////////////////////////////////////////////////////////////
	$color = array();
	$color['sum'] = array('border' => '#000000', 'color' => $config['graph_color2'], 'alpha' => 50);
	if($show_events) {
		$color['event'] = array('border' => '#ff7f00', 'color' => '#ff7f00', 'alpha' => 50);
	}
	if($show_alerts) {
		$color['alert'] = array('border' => '#ff0000', 'color' => '#ff0000', 'alpha' => 50);
	}
	$color['max'] = array('border' => '#000000', 'color' => $config['graph_color3'], 'alpha' => 50);
	$color['min'] = array('border' => '#000000', 'color' => $config['graph_color1'], 'alpha' => 50);
	$color['baseline'] = array('border' => null, 'color' => '#0097BD', 'alpha' => 10);
	/////////////////////////////////////////////////////////////////////////////////////////
	
	
	$flash_chart = $config['flash_charts'];
	if ($only_image) {
		$flash_chart = false;
	}
	
	return area_graph($flash_chart, $chart, $width, $height, $color, $legend,
		$long_index, "images/image_problem.opaque.png", "", "", $homeurl,
		 $config['homedir'] .  "/images/logo_vertical_water.png",
		 $config['fontpath'], $config['font_size'], "");
}


/**
 * Print an area graph with netflow aggregated
 */

function grafico_netflow_aggregate_area ($data, $period,$width, $height , $title, $unit_name, $avg_only = 0, $pure=0,$date = 0, $only_image = false, $homeurl = '') {
	global $config;
	global $graphic_type;
echo"<h4>Gráfica de área</h4>";
	include_flash_chart_script($homeurl);

	// Set variables
	if ($date == 0) $date = get_system_time();
	$datelimit = $date - $period;
	$resolution = $config['graph_res'] * 50; //Number of points of the graph
	$interval = (int) ($period / $resolution);
	
		/////////////////////////////////////////////////////////////////
		// Set the title and time format
		if ($period <= 3600) {
			$time_format = 'G:i:s';
		}
		elseif ($period <= 86400) {
			$time_format = 'G:i:s';
		}
		elseif ($period <= 604800) {
			$time_format = 'M d H:i:s';
		}
		elseif ($period <= 2419200) {
			$time_format = 'M d H\h';
		} 
		else {
			$time_format = 'M d H\h';
		}
		$timestamp_short = date($time_format, $date);
		/////////////////////////////////////////////////////////////////

	
///////////////COMBINED
	$aggs = array();
	$ag ='';
	// Calculate data for each agg
	$j = 0;
	for ($i = 0; $i < $resolution; $i++) {
		$count = 0;
		$timestamp = $datelimit + ($interval * $i);
		$timestamp_short = date($time_format, $timestamp);
		$long_index[$timestamp_short] = date(
		html_entity_decode($config['date_format'], ENT_QUOTES, "UTF-8"), $timestamp);
		
		
		if (isset ($data[$i])){
			$aggs[$data[$i]['agg']] = $data[$i]['agg'];
		}
		// Read data that falls in the current interval
		while(isset ($data[$j])) {
			$ag = $data[$j]['agg'];
			
				$date = $data[$j]['date'];
				$time = $data[$j]['time'];

				$datetime = strtotime ($date." ".$time);
				
				if ($datetime >= $timestamp && $datetime <= ($timestamp + $interval)){	
					if(!isset($chart[$timestamp_short][$ag])) {
						$chart[$timestamp_short][$ag] = $data[$j]['data'];
						$count++;
					} else {
						$chart[$timestamp_short][$ag] += $data[$j]['data'];
						$count++;
					}
				} else { 
					break;
				}
				
				$j++;
			}	
		
		// Average
		if ($count > 0) {
			if (isset($chart[$timestamp_short][$ag])){
				$chart[$timestamp_short][$ag] = $chart[$timestamp_short][$ag]/$count;
			}
		} else {
			$chart[$timestamp_short][$ag] = 0;
		}
	}
	
	foreach($chart as $key => $value) {
		foreach($aggs as $agg) {
			if(!isset($chart[$key][$agg])) {
				$chart[$key][$agg] = 0;
			}
		}
	}

	$color = array();
	
	$flash_chart = $config['flash_charts'];
	if ($only_image) {
		$flash_chart = false;
	}
	
	return area_graph($flash_chart, $chart, $width, $height, $color, $aggs,
		$long_index, "images/image_problem.opaque.png", "", "", $homeurl,
		 $config['homedir'] .  "/images/logo_vertical_water.png",
		 $config['fontpath'], $config['font_size'], "");
}



/**
 * Print an area graph with netflow total
 */
function grafico_netflow_total_area ($data, $period,$width, $height , $title, $unit_name, $avg_only = 0, $pure=0,$date = 0, $only_image = false, $homeurl = '') {
	global $config;
	global $graphic_type;

	echo"<h4>Gráfica de área</h4>";
	include_flash_chart_script($homeurl);

	// Set variables
	if ($date == 0) $date = get_system_time();
	$datelimit = $date - $period;
	$resolution = $config['graph_res'] * 50; //Number of points of the graph
	$interval = (int) ($period / $resolution);
	
		/////////////////////////////////////////////////////////////////
		// Set the title and time format
		if ($period <= 3600) {
			$time_format = 'G:i:s';
		}
		elseif ($period <= 86400) {
			$time_format = 'G:i:s';
		}
		elseif ($period <= 604800) {
			$time_format = 'M d H:i:s';
		}
		elseif ($period <= 2419200) {
			$time_format = 'M d H\h';
		} 
		else {
			$time_format = 'M d H\h';
		}
		$timestamp_short = date($time_format, $date);

		/////////////////////////////////////////////////////////////////

	$aggs = array();
	// Calculate data for each agg
	$j = 0;
	$chart = array();
	$long_index = array();
	
	while (isset ($data[$j])) {
		$date = $data[$j]['date'];
		$time = $data[$j]['time'];
		$datetime = strtotime ($date." ".$time);
		$timestamp_short = date($time_format, $datetime);
		$chart[$timestamp_short]['data'] = $data[$j]['data'];
		$j++;
	}
	$flash_chart = $config['flash_charts'];
	if ($only_image) {
		$flash_chart = false;
	}
	$leyend = array();
	$color = array();

	return area_graph($flash_chart, $chart, $width, $height, $color, $leyend,
		$long_index, "images/image_problem.opaque.png", "", "", $homeurl,
		 $config['homedir'] .  "/images/logo_vertical_water.png",
		 $config['fontpath'], $config['font_size'], "");
}

/**
 * Print a pie graph with netflow aggregated
 */
function grafico_netflow_aggregate_pie ($data) {
	global $config;
	global $graphic_type;
	
	echo"<h4>Gráfica totalizada</h4>";
	
	$i = 0;
	$values = array();
	$agg = '';
	while (isset ($data[$i])) {
		$agg = $data[$i]['agg'];
		if (!isset($values[$agg])){
			$values[$agg] = $data[$i]['data'];
		} else {
			$values[$agg] += $data[$i]['data'];
		}
		$i++;
	}
	return pie3d_graph($config['flash_charts'], $values, 320, 200,
		__('Other'), '', $config['homedir'] .  "/images/logo_vertical_water.png",
		$config['fontpath'], $config['font_size']);
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
	 $width, $height , $title, $unit_name, $show_alerts, $avg_only = 0, $pure=0,
	 $date = 0, $only_image = false, $homeurl = '') {
	global $config;
	global $graphic_type;
	
	include_flash_chart_script($homeurl);

	// Set variables
	if ($date == 0) $date = get_system_time();
	$datelimit = $date - $period;
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
		array ('datos', 'utimestamp'));
	if ($data === false) {
		$data = array ();
	}

	// Uncompressed module data
	if ($uncompressed_module) {
		$min_necessary = 1;

	// Compressed module data
	} else {
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
		} else if (count ($data) > 0) {
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

	// Data iterator
	$j = 0;
	
	// Event iterator
	$k = 0;

	// Set initial conditions
	$chart = array();
	if ($data[0]['utimestamp'] == $datelimit) {
		$previous_data = 1;
		$j++;
	} else {
		$previous_data = 0;
	}

	// Calculate chart data
	for ($i = 0; $i < $resolution; $i++) {
		$timestamp = $datelimit + ($interval * $i);

		$count = 0;	
		$total = 0;	
		// Read data that falls in the current interval
		while (isset ($data[$j]) !== null && $data[$j]['utimestamp'] >= $timestamp && $data[$j]['utimestamp'] <= ($timestamp + $interval)) {
			$count++;
			$j++;
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
		if ($period <= 21600) {
			$time_format = 'H:i:s';
		}
		elseif ($period < 86400) {
			$time_format = 'H:i';
		}
		elseif ($period < 1296000) {
			$time_format = 'M d H:i';
		}
		elseif ($period < 2592000) {
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
		if ($count > 0) {
			$chart[$timestamp]['sum'] = $count;
			$previous_data = $total;
		// Compressed data
		} else {
			$chart[$timestamp]['sum'] = $previous_data;
		}

		//$chart[$timestamp]['count'] = 0;
		//$chart[$timestamp]['timestamp_bottom'] = $timestamp;
		//$chart[$timestamp]['timestamp_top'] = $timestamp + $interval;
		$chart[$timestamp]['min'] = 0;
		$chart[$timestamp]['max'] = 0;
		$chart[$timestamp]['event'] = $event_value;
		$chart[$timestamp]['alert'] = $alert_value;
	}

	// Get min, max and avg (less efficient but centralized for all modules and reports)
	$min_value = round(reporting_get_agentmodule_data_min ($agent_module_id, $period, $date), 2);
	$max_value = round(reporting_get_agentmodule_data_max ($agent_module_id, $period, $date), 2);
	$avg_value = round(reporting_get_agentmodule_data_average ($agent_module_id, $period, $date), 2);
	$unit = modules_get_unit($agent_module_id);

	// Fix event and alert scale
	$event_max = $max_value * 1.25;
	foreach ($chart as $timestamp => $chart_data) {
		if ($chart_data['event'] > 0) {
			$chart[$timestamp]['event'] = $event_max;
		}
		if ($chart_data['alert'] > 0) {
			$chart[$timestamp]['alert'] = $event_max;
		}
	}
	
	
	/////////////////////////////////////////////////////////////////////////////////////////
	$color = array();
	$color['sum'] = array('border' => '#000000', 'color' => $config['graph_color2'], 'alpha' => 50);
	$color['event'] = array('border' => '#ff7f00', 'color' => '#ff7f00', 'alpha' => 50);
	$color['alert'] = array('border' => '#ff0000', 'color' => '#ff0000', 'alpha' => 50);
	$color['max'] = array('border' => '#000000', 'color' => $config['graph_color3'], 'alpha' => 50);
	$color['min'] = array('border' => '#000000', 'color' => $config['graph_color1'], 'alpha' => 50);
	//$color['baseline'] = array('border' => null, 'color' => '#0097BD', 'alpha' => 10);
	/////////////////////////////////////////////////////////////////////////////////////////
	
	$flash_chart = $config['flash_charts'];
	if ($only_image) {
		$flash_chart = false;
	}
	
	$legend = null;
	
	return vbar_graph($flash_chart, $chart, $width, $height, $color,
		$legend, "", "", $homeurl,
		$config['homedir'] .  "/images/logo_vertical_water.png",
		$config['fontpath'], $config['font_size'], '', true, 1, true);
}

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

	$one_second = 1;
	$one_minute = 60 * $one_second;
	$one_hour = 60 * $one_minute;
	$one_day = 24 * $one_hour;
	$one_week = 7 * $one_day;

	$adjust_time = $one_minute; // 60 secs

	if ($periodo == 86400) // day
		$adjust_time = $one_hour;
	elseif ($periodo == 604800) // week
		$adjust_time =$one_day;
	elseif ($periodo == 3600) // hour
		$adjust_time = 10 * $one_minute;
	elseif ($periodo == 2419200) // month
		$adjust_time = $one_week;
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
	while ($row = get_db_all_row_by_steps_sql($first, $result, $sql1)){
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
	set_include_path(get_include_path() . PATH_SEPARATOR . getcwd() . "/../../include");

	require_once 'Image/Graph.php';

	grafico_modulo_log4x_trace(__LINE__);

	$Graph =& Image_Graph::factory('graph', array($width, $height));

	grafico_modulo_log4x_trace(__LINE__);

	// add a TrueType font
	$Font =& $Graph->addNew('font', $config['fontpath']); // C:\WINNT\Fonts\ARIAL.TTF
	$Font->setSize(7);

	$Graph->setFont($Font);

	if ($periodo == 86400)
		$title_period = $lang_label["last_day"];
	elseif ($periodo == 604800)
		$title_period = $lang_label["last_week"];
	elseif ($periodo == 3600)
		$title_period = $lang_label["last_hour"];
	elseif ($periodo == 2419200)
		$title_period = $lang_label["last_month"];
	else {
		$suffix = $lang_label["days"];
		$graph_extension = $periodo / (3600*24); // in days

		if ($graph_extension < 1) {
			$graph_extension = $periodo / (3600); // in hours
			$suffix = $lang_label["hours"];
		}
		//$title_period = "Last ";
		$title_period = format_numeric($graph_extension,2)." $suffix";
	}

	$title_period = html_entity_decode($title_period);

	grafico_modulo_log4x_trace(__LINE__);

	if ($pure == 0){
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
	} else { // Pure, without title and legends
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

		if (isset($valores[$severity])){
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
		} else {
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
	if ($show_event == 1){
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


         // Esta custion no sirve paaaaaaaaaaaa naaaaaaaaaaaaaaaadaaaaaaaaaaaaaa
         //Cementerio de formulas ... QEPD
        $a = pow(($r1 - $r0)/(pow($v2,1/4.0)-1),4);
        $b = $r0 - pow($a,1/4.0);

        return pow($a * $count, 1/4.0) + $b;

        $r = pow($count / pow(3.1415, 3), 0.25);

        $q = 0.9999;
        $x = $count;
        $x0 = $max_count;
        $y0 = $max_size;

        $y = 4 * $y0 * $x * (((1 - 2 * $q) / (2 * $x0))* $x + ((4 * $q - 1) / 4)) / $x0;

        return $y;

        return 3 * (0.3796434104 + pow($count * 0.2387394557, 0.333333333));

        return sqrt($count / 3.1415);
        return 5 + log($count);
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
        $n = "";

        switch($number) {
	        case 6: $n = "FATAL"; break;
	        case 5: $n = "ERROR"; break;
	        case 4: $n = "WARN"; break;
	        case 3: $n = "INFO"; break;
	        case 2: $n = "DEBUG"; break;
	        case 1: $n = "TRACE"; break;
        }

        return "$n";
}
?>
