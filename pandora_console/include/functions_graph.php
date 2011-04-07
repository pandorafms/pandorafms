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

function grafico_modulo_sparse2 ($agent_module_id, $period, $show_events,
				$width, $height , $title, $unit_name,
				$show_alerts, $avg_only = 0, $pure = false,
				$date = 0, $baseline = 0, $return_data = 0, $show_title = true) {
	global $config;
	global $graphic_type;
	
	// Set variables
	if ($date == 0) $date = get_system_time();
	$datelimit = $date - $period;
	$resolution = $config['graph_res'] * 50; //Number of points of the graph
	$interval = (int) ($period / $resolution);
	$agent_name = get_agentmodule_agent_name ($agent_module_id);
	$agent_id = get_agent_id ($agent_name);
	$module_name = get_agentmodule_name ($agent_module_id);
	$id_module_type = get_agentmodule_type ($agent_module_id);
	$module_type = get_moduletype_name ($id_module_type);
	$uncompressed_module = is_module_uncompressed ($module_type);
	if ($uncompressed_module) {
		$avg_only = 1;
	}

	// Get event data (contains alert data too)
	if ($show_events == 1 || $show_alerts == 1) {
		$events = get_db_all_rows_filter ('tevento',
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
	$data = get_db_all_rows_filter ('tagente_datos',
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
	} else {
		// Get previous data
		$previous_data = get_previous_data ($agent_module_id, $datelimit);
		if ($previous_data !== false) {
			$previous_data['utimestamp'] = $datelimit;
			array_unshift ($data, $previous_data);
		}
	
		// Get next data
		$nextData = get_next_data ($agent_module_id, $date);
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

	// Get baseline data
	$baseline_data = array ();
	if ($baseline == 1) {
		$baseline_data = enterprise_hook ('enterprise_get_baseline', array ($agent_module_id, $period, $width, $height , $title, $unit_name, $date));
		if ($baseline_data === ENTERPRISE_NOT_HOOK) {
			$baseline_data = array ();
		}
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
		
		// Set the title and time format
		if ($period <= 3600) {
			$title_period = __('Last hour');
			$time_format = 'G:i:s';
		}
		elseif ($period <= 86400) {
			$title_period = __('Last day');
			$time_format = 'G:i';
		}
		elseif ($period <= 604800) {
			$title_period = __('Last week');
			$time_format = 'M j';
		}
		elseif ($period <= 2419200) {
			$title_period = __('Last month');
			$time_format = 'M j';
		} 
		else {
			$title_period = __('Last %s days', format_numeric (($period / (3600 * 24)), 2));
			$time_format = 'M j';
		}

		$timestamp_short = date($time_format, $timestamp);
		$long_index[$timestamp_short] = date(
			html_entity_decode($config['date_format'], ENT_QUOTES, "UTF-8"), $timestamp);
		$timestamp = $timestamp_short;
		
		// Data
		if ($count > 0) {
			$chart[$timestamp]['sum'] = $total;
			$chart[$timestamp]['min'] = $interval_min;
			$chart[$timestamp]['max'] = $interval_max;
			$previous_data = $total;
		// Compressed data
		} else {
			if ($uncompressed_module || ($timestamp > time ())) {
				$chart[$timestamp]['sum'] = 0;
				$chart[$timestamp]['min'] = 0;
				$chart[$timestamp]['max'] = 0;
			} else {
				$chart[$timestamp]['sum'] = $previous_data;
				$chart[$timestamp]['min'] = $previous_data;
				$chart[$timestamp]['max'] = $previous_data;
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
		$chart[$timestamp]['baseline'] = array_shift ($baseline_data);
		if ($chart[$timestamp]['baseline'] == NULL) {
			$chart[$timestamp]['baseline'] = 0;
		}
	}
	
	// Return chart data and don't draw
	if ($return_data == 1) {
		return $chart;
	}	
	
	// Get min, max and avg (less efficient but centralized for all modules and reports)
	$min_value = round(get_agentmodule_data_min ($agent_module_id, $period, $date), 2);
	$max_value = round(get_agentmodule_data_max ($agent_module_id, $period, $date), 2);
	$avg_value = round(get_agentmodule_data_average ($agent_module_id, $period, $date), 2);

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
		$caption = __('Max. Value') . ': ' . $max_value . '    ' . __('Avg. Value') . ': ' . $avg_value . '    ' . __('Min. Value') . ': ' . $min_value;
    else
		$caption = array();
	
	///////
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
	
	$legend = array();
	$legend['sum'] = __('Avg') . ' (' . $avg_value . ')';
	if($show_events) {
		$legend['event'] = __('Events');
	}
	if($show_alerts) {
		$legend['alert'] = __('Alerts');
	}
	$legend['max'] = __('Max') . ' (' . $max_value . ')';
	$legend['min'] = __('Min') . ' (' . $min_value . ')';
	$legend['baseline'] = __('Baseline');
	
	return area_graph($config['flash_charts'], $chart, $width, $height, $color,$legend, $long_index);
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
 * 
 * @return Mixed 
 */
function graphic_combined_module2 ($module_list, $weight_list, $period, $width, $height,
				$title, $unit_name, $show_events = 0, $show_alerts = 0, $pure = 0, $stacked = 0, $date = 0) {
	global $config;
	global $graphic_type;
	
	// Set the title and time format
	
	if ($period <= 3600) {
		$title_period = __('Last hour');
		$time_format = 'G:i:s';
	}
	elseif ($period <= 86400) {
		$title_period = __('Last day');
		$time_format = 'G:i';
	}
	elseif ($period <= 604800) {
		$title_period = __('Last week');
		$time_format = 'M j';
	}
	elseif ($period <= 2419200) {
		$title_period = __('Last month');
		$time_format = 'M j';
	} 
	else {
		$title_period = __('Last %s days', format_numeric (($period / (3600 * 24)), 2));
		$time_format = 'M j';
	}

	// Set variables
	if ($date == 0) $date = get_system_time();
	$datelimit = $date - $period;
	$resolution = $config['graph_res'] * 50; //Number of points of the graph
	$interval = (int) ($period / $resolution);
	$module_number = count ($module_list);

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
	
	// Calculate data for each module
	for ($i = 0; $i < $module_number; $i++) {

		$agent_module_id = $module_list[$i];
		$agent_name = get_agentmodule_agent_name ($agent_module_id);
		$agent_id = get_agent_id ($agent_name);
		$module_name = get_agentmodule_name ($agent_module_id);
		$module_name_list[$i] = $agent_name." / ".substr ($module_name, 0, 40);
		$id_module_type = get_agentmodule_type ($agent_module_id);
		$module_type = get_moduletype_name ($id_module_type);
		$uncompressed_module = is_module_uncompressed ($module_type);
		if ($uncompressed_module) {
			$avg_only = 1;
		}

		// Get event data (contains alert data too)
		if ($show_events == 1 || $show_alerts == 1) {
			$events = get_db_all_rows_filter ('tevento',
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
		$data = get_db_all_rows_filter ('tagente_datos',
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
			$previous_data = get_previous_data ($agent_module_id, $datelimit);
			if ($previous_data !== false) {
				$previous_data['utimestamp'] = $datelimit;
				array_unshift ($data, $previous_data);
			}
		
			// Get next data
			$nextData = get_next_data ($agent_module_id, $date);
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
			$timestamp_short = date($time_format, $timestamp);
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
			
			$graph_values[$i] = $temp_graph_values;
		}
		
		//Add the max, min and avg in the legend
		$avg = round($avg / $countAvg, 1);
		$module_name_list[$i] .= " (".__("Max"). ":$max, ".__("Min"). ":$min, ". __("Avg"). ": $avg)";
		
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
	
	if ($period <= 3600) {
		$title_period = __('Last hour');
		$time_format = 'G:i:s';
	} elseif ($period <= 86400) {
		$title_period = __('Last day');
		$time_format = 'G:i';
	} elseif ($period <= 604800) {
		$title_period = __('Last week');
		$time_format = 'M j';
	} elseif ($period <= 2419200) {
		$title_period = __('Last month');
		$time_format = 'M j';
	} else {
		$title_period = __('Last %s days', format_numeric (($period / (3600 * 24)), 2));
		$time_format = 'M j';
	}
	////////////////////////////////////////////////////////////////////////////
	switch ($stacked) {
		case 0:
			/*$color = array(
				0 => array('alpha' => 50),
				1 => array('alpha' => 50),
				2 => array('alpha' => 50)
			);*/
			$color = null;
			return area_graph($config['flash_charts'], $graph_values, $width, $height,
				$color, $module_name_list, $long_index);
			return;
			break;
		default:
		case 1:
			//TODO
			$chart_type = 'StackedArea2D';
			break;
		case 2:
			$chart_type = 'MSLine';
			//TODO
			break;
		case 3:
			$chart_type = 'MSLine';
			//TODO
			break;
	}	
	
	
	////////////////////////////////////////////////////////////////////////////
	if (! $graphic_type) {
		return fs_combined_chart ($graph_values, $graph, $module_name_list, $width, $height, $stacked, $resolution / 10, $time_format);
	}

	$engine = get_graph_engine ($period);
	
	$engine->width = $width;
	$engine->height = $height;
	$engine->data = &$graph_values;
	$engine->legend = $module_name_list;
	$engine->fontpath = $config['fontpath'];
	$engine->title = "";
	$engine->subtitle = "";
	$engine->show_title = !$pure;
	$engine->stacked = $stacked;
	$engine->xaxis_interval = $resolution;
	$events = false;
	$alerts = false;
	if (!isset($max_value)) {
		$max_value = 0;
	}
	
	$engine->combined_graph ($graph, $events, $alerts, $unit_name, $max_value, $stacked);
}
?>