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

define("GRAPH_AREA", 0);
define("GRAPH_STACKED_AREA", 1);
define("GRAPH_LINE", 2);
define("GRAPH_STACKED_LINE", 3);

function grafico_modulo_sparse2 ($agent_module_id, $period, $show_events,
				$width, $height , $title = '', $unit_name = null,
				$show_alerts = false, $avg_only = 0, $pure = false,
				$date = 0, $baseline = 0, $return_data = 0, $show_title = true,
				$only_image = false, $homeurl = '') {
	global $config;
	global $graphic_type;
	
	include_flash_chart_script($homeurl);
	
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
	}
	else {
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
	
	$flash_chart = $config['flash_charts'];
	if ($only_image) {
		$flash_chart = false;
	}
	
	return area_graph($flash_chart, $chart, $width, $height, $color,$legend,
		$long_index, "images/image_problem.opaque.png", "", "", $homeurl);
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
		$title, $unit_name, $show_events = 0, $show_alerts = 0, $pure = 0,
		$stacked = 0, $date = 0, $only_image = false, $homeurl = '') {
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
	
	$flash_charts = $config['flash_charts'];
	
	if ($only_image) {
		$flash_charts = false;
	}
	
	switch ($stacked) {
		case GRAPH_AREA:
			$color = null;
			return area_graph($flash_charts, $graph_values, $width, $height,
				$color, $module_name_list, $long_index, "images/image_problem.opaque.png",
				"", "", $homeurl);
			break;
		default:
		case GRAPH_STACKED_AREA:
			$color = null;
			return stacked_area_graph($flash_charts, $graph_values, $width, $height,
				$color, $module_name_list, $long_index, "images/image_problem.opaque.png");
			break;
		case GRAPH_LINE:
			$color = null;
			return line_graph($flash_charts, $graph_values, $width, $height,
				$color, $module_name_list, $long_index, "images/image_problem.opaque.png");
			break;
		case GRAPH_STACKED_LINE:
			$color = null;
			return stacked_line_graph($flash_charts, $graph_values, $width, $height,
				$color, $module_name_list, $long_index, "images/image_problem.opaque.png");
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
function graphic_agentaccess2 ($id_agent, $width, $height, $period = 0) {
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
	
	for ($i = 0; $i < $interval; $i++) {
		$bottom = $datelimit + ($periodtime * $i);
		if (! $graphic_type) {
			$name = date('G:i', $bottom);
		} else {
			$name = $bottom;
		}

		$top = $datelimit + ($periodtime * ($i + 1));
		$data[$name]['data'] = (int) get_db_value_filter ('COUNT(*)',
			'tagent_access',
			array ('id_agent' => $id_agent,
				'utimestamp > '.$bottom,
				'utimestamp < '.$top));
	}
	
	echo area_graph($config['flash_charts'], $data, $width, $height,
		null, null, null, "images/image_problem.opaque.png");
}

/**
 * Print a pie graph with events data of agent
 * 
 * @param integer width pie graph width
 * @param integer height pie graph height
 * @param integer id_agent Agent ID
 */
function graph_event_module2 ($width = 300, $height = 200, $id_agent) {
	global $config;
	global $graphic_type;

	$data = array ();
	$max_items = 6;
	$sql = sprintf ('SELECT COUNT(id_evento) as count_number, nombre
		FROM tevento, tagente_modulo
		WHERE id_agentmodule = id_agente_modulo
			AND disabled = 0 AND tevento.id_agente = %d
		GROUP BY id_agentmodule LIMIT %d', $id_agent, $max_items);
	$events = get_db_all_rows_sql ($sql);
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
	$value = get_db_sql ($sql);
	if ($value > 0) {
		$data[__('System').' ('.$value.')'] = $value;
	}
	asort ($data);
	
	return pie3d_graph($config['flash_charts'], $data, $width, $height, __("other"));
}

function progress_bar2($progress, $width, $height, $title = '', $mode = 1) {
	global $config;
	
	$out_of_lim_str = __("Out of limits");
	$title = "";
	return "<img title='" . $title . "' alt='" . $title . "' src='include/graphs/fgraph.php?graph_type=progressbar&width=".$width."&height=".$height."&progress=".$progress.
		"&mode=" . $mode . "&out_of_lim_str=".$out_of_lim_str."&title=".$title."&font=".$config['fontpath']."' />";
}

function graph_sla_slicebar ($id, $period, $sla_min, $sla_max, $daysWeek, $time_from, $time_to, $sla_limit, $width, $height) {
	global $config;
	
	$days = json_decode ($daysWeek, true);
	$data = get_agentmodule_sla_array ($id, $period, $sla_min, $sla_max, $sla_limit, $days, $time_from, $time_to);
	$colors = 	array(1 => '#38B800', 2 => '#FFFF00', 3 => '#FF0000', 4 => '#C3C3C3');

	return slicesbar_graph($data, $width, $height, $colors, $config['fontpath'], $config['round_corner']);
}

/**
 * Print a pie graph with purge data of agent
 * 
 * @param integer id_agent ID of agent to show
 * @param integer width pie graph width
 * @param integer height pie graph height
 */
function grafico_db_agentes_purge2 ($id_agent, $width, $height) {
	global $config;
	global $graphic_type;

	if ($id_agent < 1) {
		$id_agent = -1;
		$query = "";
	} else {
		$modules = get_agent_modules ($id_agent);
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
	
	$data[__("Today")]        = get_db_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos WHERE utimestamp > %d %s", $time["1day"], $query), 0, true);
	$data["1 ".__("Week")]    = get_db_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos WHERE utimestamp > %d %s", $time["1week"], $query), 0, true);
	$data["1 ".__("Month")]   = get_db_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos WHERE utimestamp > %d %s", $time["1month"], $query), 0, true);
	$data["3 ".__("Months")]  = get_db_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos WHERE utimestamp > %d %s", $time["3month"], $query), 0, true);
	$data[__("Older")]        = get_db_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos WHERE 1=1 %s", $query));
	
	$data[__("Today")]       += get_db_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_string WHERE utimestamp > %d %s", $time["1day"], $query), 0, true);
	$data["1 ".__("Week")]   += get_db_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_string WHERE utimestamp > %d %s", $time["1week"], $query), 0, true);
	$data["1 ".__("Month")]  += get_db_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_string WHERE utimestamp > %d %s", $time["1month"], $query), 0, true);
	$data["3 ".__("Months")] += get_db_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_string WHERE utimestamp > %d %s", $time["3month"], $query), 0, true);
	$data[__("Older")]       += get_db_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_string WHERE 1=1 %s", $query), 0, true);

	$data[__("Today")]       += get_db_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_log4x WHERE utimestamp > %d %s", $time["1day"], $query), 0, true);
	$data["1 ".__("Week")]   += get_db_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_log4x WHERE utimestamp > %d %s", $time["1week"], $query), 0, true);
	$data["1 ".__("Month")]  += get_db_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_log4x WHERE utimestamp > %d %s", $time["1month"], $query), 0, true);
	$data["3 ".__("Months")] += get_db_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_log4x WHERE utimestamp > %d %s", $time["3month"], $query), 0, true);
	$data[__("Older")]       += get_db_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_log4x WHERE 1=1 %s", $query), 0, true);

	$data[__("Older")] = $data[__("Older")] - $data["3 ".__("Months")];

	
	return pie3d_graph($config['flash_charts'], $data, $width, $height);
}

/**
 * Print a horizontal bar graph with packets data of agents
 * 
 * @param integer width pie graph width
 * @param integer height pie graph height
 */
function grafico_db_agentes_paquetes2($width = 380, $height = 300) {
	global $config;
	global $graphic_type;

	$data = array ();
	$legend = array ();
	
	$agents = get_group_agents (array_keys (get_user_groups ()), false, "none");
	$count = get_agent_modules_data_count (array_keys ($agents));
	unset ($count["total"]);
	arsort ($count, SORT_NUMERIC);
	$count = array_slice ($count, 0, 8, true);
	
	foreach ($count as $agent_id => $value) {
		$data[$agents[$agent_id]]['g'] = $value;
	}
	
	return hbar_graph($config['flash_charts'], $data, $width, $height, array(), $legend);
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

	$data = array ();
	
	switch ($config['dbtype']){
		case "mysql":
		case "postgresql":
			$modules = get_db_all_rows_sql ('SELECT COUNT(id_agente_modulo), id_agente
				FROM tagente_modulo
				GROUP BY id_agente
				ORDER BY 1 DESC LIMIT 10');
			break;
		case "oracle":
			$modules = get_db_all_rows_sql ('SELECT COUNT(id_agente_modulo), id_agente
				FROM tagente_modulo
				WHERE rownum <= 10
				GROUP BY id_agente
				ORDER BY 1 DESC');
			break;
	}
	if ($modules === false)
		$modules = array ();
	
	foreach ($modules as $module) {
		$agent_name = get_agent_name ($module['id_agente'], "none");
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
	
	return hbar_graph($config['flash_charts'], $data, $width, $height);
}

/**
 * Print a pie graph with users activity in a period of time
 * 
 * @param integer width pie graph width
 * @param integer height pie graph height
 * @param integer period time period
 */
function graphic_user_activity2 ($width = 350, $height = 230) {
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
	$logins = get_db_all_rows_sql ($sql);
	
	if($logins == false) {
		$logins = array();
	}
	foreach ($logins as $login) {
		$data[$login['id_usuario']] = $login['n_incidents'];
	}
	
	return pie3d_graph($config['flash_charts'], $data, $width, $height);
}

/**
 * Print a pie graph with priodity incident
 */
function grafico_incidente_prioridad2 () {
	global $config;
	global $graphic_type;

	$data_tmp = array (0, 0, 0, 0, 0, 0);
	$sql = 'SELECT COUNT(id_incidencia) n_incidents, prioridad
		FROM tincidencia
		GROUP BY prioridad
		ORDER BY 2 DESC';
	$incidents = get_db_all_rows_sql ($sql);
	
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
	
	return pie3d_graph($config['flash_charts'], $data, 320, 200);
}

/**
 * Print a pie graph with incidents data
 */
function graph_incidents_status2 () {
	global $config;
	global $graphic_type;
	$data = array (0, 0, 0, 0);
	
	$data = array ();
	$data[__('Open incident')] = 0;
	$data[__('Closed incident')] = 0;
	$data[__('Outdated')] = 0;
	$data[__('Invalid')] = 0;
	
	$incidents = get_db_all_rows_filter ('tincidencia',
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
	
	return pie3d_graph($config['flash_charts'], $data, 370, 180);
}

/**
 * Print a pie graph with incident data by group
 */
function graphic_incident_group2 () {
	global $config;
	global $graphic_type;

	$data = array ();
	$max_items = 5;
	$sql = sprintf ('SELECT COUNT(id_incidencia) n_incidents, nombre
		FROM tincidencia,tgrupo
		WHERE tgrupo.id_grupo = tincidencia.id_grupo
		GROUP BY tgrupo.id_grupo ORDER BY 1 DESC LIMIT %d',
		$max_items);
	$incidents = get_db_all_rows_sql ($sql);
	
	if($incidents == false) {
		$incidents = array();
	}
	foreach ($incidents as $incident) {		
		$data[$incident['nombre']] = $incident['n_incidents'];
	}
	
	return pie3d_graph($config['flash_charts'], $data, 320, 200);
}

/**
 * Print a graph with access data of agents
 * 
 * @param integer id_agent Agent ID
 * @param integer width pie graph width
 * @param integer height pie graph height
 * @param integer period time period
 */
function graphic_incident_user2 () {
	global $config;
	global $graphic_type;

	$data = array ();
	$max_items = 5;
	$sql = sprintf ('SELECT COUNT(id_incidencia) n_incidents, id_usuario
		FROM tincidencia
		GROUP BY id_usuario
		ORDER BY 1 DESC LIMIT %d', $max_items);
	$incidents = get_db_all_rows_sql ($sql);
	
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
	
	return pie3d_graph($config['flash_charts'], $data, 320, 200);
}

/**
 * Print a pie graph with access data of incidents source
 * 
 * @param integer width pie graph width
 * @param integer height pie graph height
 */
function graphic_incident_source2($width = 320, $height = 200) {
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
	$origins = get_db_all_rows_sql ($sql);
	
	if($origins == false) {
		$origins = array();
	}
	foreach ($origins as $origin) {
		$data[$origin['origen']] = $origin['n_incident'];
	}
	
	return pie3d_graph($config['flash_charts'], $data, $width, $height);
}

/**
 * Print a pie graph with events data of group
 * 
 * @param integer width pie graph width
 * @param integer height pie graph height
 * @param string url
 */
function grafico_eventos_grupo2 ($width = 300, $height = 200, $url = "") {
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
	
	$result = get_db_all_rows_sql ($sql);
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
				$name = mb_substr (get_agent_name ($row["id_agente"], "lower"), 0, 14)." (".$row["count"].")";
			}
			$data[$name] = $row["count"];
		}
		$loop++;
	}
	
	return pie3d_graph($config['flash_charts'], $data, $width, $height);
}

/**
 * Print a pie graph with events data in 320x200 size
 * 
 * @param string filter Filter for query in DB
 */
function grafico_eventos_total2($filter = "") {
	global $config;
	global $graphic_type;

	$filter = str_replace  ( "\\" , "", $filter);
	$data = array ();
	$legend = array ();
	$total = 0;
	
	$sql = "SELECT COUNT(id_evento) FROM tevento WHERE criticity = 0 $filter";
	$data[__('Maintenance')] = get_db_sql ($sql);
	
	$sql = "SELECT COUNT(id_evento) FROM tevento WHERE criticity = 1 $filter";
	$data[__('Informational')] = get_db_sql ($sql);

	$sql = "SELECT COUNT(id_evento) FROM tevento WHERE criticity = 2 $filter";
	$data[__('Normal')] = get_db_sql ($sql);

	$sql = "SELECT COUNT(id_evento) FROM tevento WHERE criticity = 3 $filter";
	$data[__('Warning')] = get_db_sql ($sql);

	$sql = "SELECT COUNT(id_evento) FROM tevento WHERE criticity = 4 $filter";
	$data[__('Critical')] = get_db_sql ($sql);
	
	asort ($data);
	
	return pie3d_graph($config['flash_charts'], $data, 320, 200);
}

/**
 * Print a pie graph with events data of users
 * 
 * @param integer height pie graph height
 * @param integer period time period
 */
function grafico_eventos_usuario2 ($width, $height) {
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
	$events = get_db_all_rows_sql ($sql);

	if ($events === false) {
		$events = array();
	}
	
	return pie3d_graph($config['flash_charts'], $data, $width, $height);
}

/**
 * Print a custom SQL-defined graph 
 * 
 * @param integer ID of report content, used to get SQL code to get information for graph
 * @param integer height graph height
 * @param integer width graph width
 * @param integer Graph type 1 vbar, 2 hbar, 3 pie
 */
function graph_custom_sql_graph2 ($id, $width, $height, $type = 'sql_graph_vbar', $only_image = false, $homeurl = '') {
	global $config;

    $report_content = get_db_row ('treport_content', 'id_rc', $id);
    if ($report_content["external_source"] != ""){
        $sql = safe_output ($report_content["external_source"]);
    }
    else {
    	$sql = get_db_row('treport_custom_sql', 'id', $report_content["treport_custom_sql_id"]);
    	$sql = safe_output($sql['sql']);
    }
    
	$data_result = get_db_all_rows_sql ($sql);

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
	
	if ($only_image) {
		$flash_charts = false;
	}
	
    switch ($type) {
        case 'sql_graph_vbar': // vertical bar
        	return hbar_graph($flash_charts, $data, $width, $height, array(), array(), "", "", false, $homeurl);
            break;
        case 'sql_graph_hbar': // horizontal bar
        	return vbar_graph($flash_charts, $data, $width, $height, array(), array(), "", "", $homeurl);
            break;
        case 'sql_graph_pie': // Pie
            return pie3d_graph($flash_charts, $data, $width, $height, __("other"), $homeurl);
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
function graphic_agentevents2 ($id_agent, $width, $height, $period = 0) {
	global $config;
	global $graphic_type;
	
	include_flash_chart_script();

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
		$criticity = (int) get_db_value_filter ('criticity',
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
		return fs_agent_event_chart2 ($data, $width, $height, $resolution / 750);
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
function fs_agent_event_chart2 ($data, $width, $height, $step = 1) {
	global $config;

	if (sizeof ($data) == 0) {
		return fs_error_image ();
	}

	// Generate the XML
	$chart = new FusionCharts('Area2D', $width, $height);
	
	$count = 0;
	$num_vlines = 0;
	foreach ($data as $name => $value) {
		if ($count++ % $step == 0) {
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

// Returns the code needed to display the chart
function get_chart_code ($chart, $width, $height, $swf) {
	$random_number = rand ();
	$div_id = 'chart_div_' . $random_number;
	$chart_id = 'chart_' . $random_number;
	$output = '<div id="' . $div_id. '"></div>';
	$output .= '<script type="text/javascript">
			<!--
				$(document).ready(function pie_' . $chart_id . ' () {
					var myChart = new FusionCharts("' . $swf . '", "' . $chart_id . '", "' . $width. '", "' . $height. '", "0", "1");
					myChart.setDataXML("' . addslashes($chart->getXML ()) . '");
					myChart.addParam("WMode", "Transparent");
					myChart.render("' . $div_id . '");
				})
			-->
			</script>';
	return $output;
}

// Prints an error image
function fs_error_image () {
	global $config;

	return print_image("images/image_problem.png", true, array("border" => '0'));
}
?>