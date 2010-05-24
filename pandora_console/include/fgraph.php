<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package Include
 * @subpackage Graphs
 */


if (isset($config)) {
/**#@+
 * If is set global var $config include this files
 */
	require_once ($config['homedir'].'/include/config.php');
	require_once ($config['homedir'].'/include/pandora_graph.php');
	require_once ($config['homedir'].'/include/functions_fsgraph.php');
/**#@-*/
}
else {
/**#@+
 * If is not set global var $config include this files
 */
	require_once ('../include/config.php');
	require_once ($config['homedir'].'/include/pandora_graph.php');
/**#@-*/
}



set_time_limit (0);
error_reporting (0);

if (! isset ($config["id_user"])) {
	session_start ();
	session_write_close ();
	$config["id_user"] = $_SESSION["id_usuario"];
}

//Fixed the graph for cron (that it's login)
if (($_SERVER['SERVER_ADDR'] != $_SERVER['REMOTE_ADDR'])
	&& ($_SERVER['REMOTE_ADDR'] != '127.0.0.1')) {
	// Session check
	check_login ();
}

/**
 * Show a brief error message in a PNG graph
 * 
 * @param string image File that show when has a problem.
 */
function graphic_error ($image = 'image_problem.png') {
	global $config;
	
	Header ('Content-type: image/png');
	$img = imagecreatefromPng ($config['homedir'].'/images/'.$image);
	imagealphablending ($img, true);
	imagesavealpha ($img, true);
	imagepng ($img);
	exit;
}

/**
 * Return a MySQL timestamp date, formatted with actual date MINUS X minutes, 
 *
 * @param int Date in unix format (timestamp)
 *
 * @return string Formatted date string (YY-MM-DD hh:mm:ss)
 */
function dame_fecha ($mh) {
	$mh *= 60;
	return date ("Y-m-d H:i:00", time() - $mh); 
}

/**
 * Return a short timestamp data, D/M h:m
 *
 * @param int Date in unix format (timestamp)
 *
 * @return string Formatted date string
 */
function dame_fecha_grafico_timestamp ($timestamp) {
	return date ('d/m H:i', $timestamp);
}

/**
 * Produces a combined/user defined PNG graph
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
function graphic_combined_module ($module_list, $weight_list, $period, $width, $height,
				$title, $unit_name, $show_events = 0, $show_alerts = 0, $pure = 0, $stacked = 0, $date = 0) {
	global $config;
	global $graphic_type;

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
	for ($iterator = 0; $iterator < $resolution; $iterator++) {
			$timestamp = $datelimit + ($interval * $iterator);
			
			$graph[$timestamp]['count'] = 0;
			$graph[$timestamp]['timestamp_bottom'] = $timestamp;
			$graph[$timestamp]['timestamp_top'] = $timestamp + $interval;
			$graph[$timestamp]['min'] = 0;
			$graph[$timestamp]['max'] = 0;
			$graph[$timestamp]['event'] = 0;
			$graph[$timestamp]['alert'] = 0;
	}

	// Calculate data for each module
	for ($i = 0; $i < $module_number; $i++) {

		$agent_module_id = $module_list[$i];
		$agent_name = get_agentmodule_agent_name ($agent_module_id);
		$agent_id = get_agent_id ($agent_name);
		$module_name = get_agentmodule_name ($agent_module_id);
		$module_name_list[$i] = $agent_name." / ".substr ($module_name, 0, 20);

		// Get event data (contains alert data too)
		if ($show_events == 1 || $show_alerts == 1) {
			$events = get_db_all_rows_filter ('tevento',
				array ('id_agentmodule' => $agent_module_id,
					"utimestamp > $datelimit",
					"utimestamp < $date",
					'order' => 'utimestamp ASC'),
				array ('evento', 'utimestamp'));
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
	
		// Check available data
		if (count ($data) < 2) {
			if (!$graphic_type) {
				return fs_error_image ();
			}
			graphic_error ();
		}
	
		// Calculate chart data
		$graph_values[$i] = array();
		$module_data = array_shift ($data);
		$event_data = array_shift ($events);
		if ($module_data['utimestamp'] == $datelimit) {
			$previous_data = $module_data['datos'];
			$min_value = $module_data['datos'];
			$max_value = $module_data['datos'];
		} else {
			$previous_data = 0;
			$min_value = 0;
			$max_value = 0;
		}

		for ($iterator = 0; $iterator < $resolution; $iterator++) {
			$timestamp = $datelimit + ($interval * $iterator);
	
			$total = 0;
			$count = 0;
			
			// Read data that falls in the current interval
			$interval_min = $previous_data;
			$interval_max = $previous_data;
			while ($module_data !== null && $module_data ['utimestamp'] >= $timestamp && $module_data ['utimestamp'] < ($timestamp + $interval)) {
				if ($module_data['datos'] > $interval_max) {
					$interval_max = $module_data['datos'];
				} else if ($module_data['datos'] < $interval_max) {
					$interval_min = $module_data['datos'];
				}
				$total += $module_data['datos'];
				$count++;
				$module_data = array_shift ($data);
			}
	
			// Average
			if ($count > 0) {
				$total /= $count;
			}
	
			// Min and max
			if ($interval_max > $max_value) {
				$max_value = $interval_max;
			} else if ($interval_min < $min_value) {
				$min_value = $interval_min;
			}
	
			// Read events and alerts that fall in the current interval
			$event_value = 0;
			$alert_value = 0;
			while ($event_data !== null && $event_data ['utimestamp'] >= $timestamp && $event_data ['utimestamp'] <= ($timestamp + $interval)) {
				if ($show_events == 1) {
					$event_value++;
				}
				if ($show_alerts == 1 && substr ($event_data['event_type'], 0, 5) == 'alert') {
					$alert_value++;
				}
				$event_data = array_shift ($events);
			}

			// Data
			if ($count > 0) {
				$graph_values[$i][$timestamp] = $total * $weight_list[$i];
				$previous_data = $total;
			// Compressed data
			} else {
				$graph_values[$i][$timestamp] = $previous_data * $weight_list[$i];
			}
		}
	}

	for ($i = 0; $i < $module_number; $i++) {
		if ($weight_list[$i] != 1)
			$module_name_list[$i] .= " (x". format_numeric ($weight_list[$i], 1).")";
	}
	
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
	
	if ($max_value <= 0) {
		if (! $graphic_type) {
			return fs_error_image ();
		}
		graphic_error ();
	}
	
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
	$engine->subtitle = '     '.__('Period').': '.$title_period;
	$engine->show_title = !$pure;
	$engine->stacked = $stacked;
	$engine->xaxis_interval = $resolution;
	$events = false;
	$alerts = false;
	$engine->combined_graph ($graph, $events, $alerts, $unit_name, $max_value, $stacked);
}

function grafico_modulo_sparseOLD ($id_agente_modulo, $period, $show_event,
				$width, $height , $title, $unit_name,
				$show_alert, $avg_only = 0, $pure = false,
				$date = 0) {
	global $config;
	global $graphic_type;
	
	if (empty ($date))
		$date = get_system_time ();
	
	$resolution = $config["graph_res"] * 50; // Number of "slices" we want in graph
	$datelimit = $date - $period;
	$real_event = array ();
	
	$interval = (int) ($period / $resolution); // Each interval is $interval seconds length
	$nombre_agente = get_agentmodule_agent_name ($id_agente_modulo);
	$id_agente = get_agent_id ($nombre_agente);
	$nombre_modulo = get_agentmodule_name ($id_agente_modulo);
	
	// Init tables
	for ($i = 0; $i <= $resolution; $i++) {
		$data[$i]['sum'] = 0;
		$data[$i]['count'] = 0;
		$data[$i]['timestamp_bottom'] = $datelimit + ($interval * $i);
		$data[$i]['timestamp_top'] = $datelimit + ($interval * ($i + 1));
		$data[$i]['min'] = 0;
		$data[$i]['max'] = 0;
		$data[$i]['last'] = 0;
		$data[$i]['events'] = 0;
	}
	
	$all_data = get_db_all_rows_filter ('tagente_datos',
		array ('id_agente_modulo' => (int) $id_agente_modulo,
			"utimestamp > $datelimit",
			"utimestamp < $date",
			'order' => 'utimestamp ASC'),
		array ('datos', 'utimestamp'), 'AND', true);
	
	if ($all_data === false) {
		if (! $graphic_type) {
			return fs_error_image ();
		}
		graphic_error ();
	}
	$max_value = 0;
	$min_value = 0;
	$start = 0;
	foreach ($all_data as $module_data) {
		$utimestamp = $module_data['utimestamp'];
		$real_data = $module_data['datos'];
		for ($i = $start; $i <= $resolution; $i++) {
			if ($utimestamp <= $data[$i]['timestamp_top'] && $utimestamp >= $data[$i]['timestamp_bottom']) {
				$start = $i;
				$data[$i]['sum'] += $real_data;
				$data[$i]['count']++;
				$data[$i]['last'] = $real_data;
				
				// Init min value
				if ($data[$i]['min'] == 0 || $real_data < $data[$i]['min'])
					$data[$i]['min'] = $real_data;
				
				// Check max value
				if ($real_data > $data[$i]['max'])
					$data[$i]['max'] = $real_data;
				
				// Get max value for all graph
				if ($data[$i]['max'] > $max_value)
					$max_value = $data[$i]['max'];
				
				// Get min value for all graph
				$max_value = max ($max_value, $data[$i]['max']);
				$min_value = min ($min_value, $data[$i]['min']);
				
				if ($show_alert == 1) {
					$alert_high = false;
					$alert_low = false;
					// If we want to show alerts limits
		
					$alert_high = get_db_value ('MAX(max_value)', 'talert_template_modules', 'id_agent_module', (int) $id_agente_modulo);
					$alert_low = get_db_value ('MIN(min_value)', 'talert_template_modules', 'id_agent_module', (int) $id_agente_modulo);
		
					// if no valid alert defined to render limits, disable it
					if (($alert_low === false || $alert_low === NULL) &&
						($alert_high === false || $alert_high === NULL)) {
						$show_alert = 0;
					}
				}
			}
			
			if ($show_event) {
				// If we want to show events in graphs
				$events = get_db_value_filter ('COUNT(*)', 'tevento',
					array ('id_agentmodule' => $id_agente_modulo,
						'utimestamp >= '.$data[$i]['timestamp_bottom'],
						'utimestamp < '.$data[$i]['timestamp_top']));
				
				if ($events)
					$data[$i]['events']++;
			}
		}
	}
	
	// Get the first data outsite (to the left---more old) of the interval given
	$previous = (float) get_previous_data ($id_agente_modulo, $datelimit);
	for ($i = 0; $i <= $resolution; $i++) {
		if ($data[$i]['count']) {
			$data[$i]['sum'] = $data[$i]['sum'] / $data[$i]['count'];
			$previous = $data[$i]['last'];
		} else {
			$data[$i]['sum'] = $previous;
			$data[$i]['min'] = $previous;
			$data[$i]['max'] = $previous;
			/* Previous does not change here*/
		}
	}
	
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

	if (! $graphic_type) {
		return fs_module_chart ($data, $width, $height, $avg_only, $resolution / 10, $time_format);
	}
	
	$engine = get_graph_engine ($period);
	$engine->width = $width;
	$engine->height = $height;
	$engine->data = &$data;
	$engine->xaxis_interval = $resolution;
	if ($title == '')
		$title = get_agentmodule_name ($id_agente_modulo);
	$engine->title = '   '.strtoupper ($nombre_agente)." - ".__('Module').' '.$title;
	$engine->subtitle = '     '.__('Period').': '.$title_period;
	$engine->show_title = !$pure;
	$engine->max_value = $max_value;
	$engine->min_value = $min_value;
	$engine->events = (bool) $show_event;
	$engine->alert_top = $show_alert ? $alert_high : false;
	$engine->alert_bottom = $show_alert ? $alert_low : false;;
	if (! $pure) {
		$engine->legend = &$legend;
	}
	$engine->fontpath = $config['fontpath'];
	
	$engine->sparse_graph ($period, $avg_only, $min_value, $max_value, $unit_name);
}

/**
 * Print a pie graph with module data of agents
 * 
 * @param integer id_agent Agent ID
 * @param integer width pie graph width
 * @param integer height pie graph height
 */
function graphic_agentmodules ($id_agent, $width, $height) {
	global $config;
	
	$data = array ();
	$sql = sprintf ('SELECT ttipo_modulo.nombre,COUNT(id_agente_modulo)
			FROM tagente_modulo,ttipo_modulo WHERE
			id_tipo_modulo = id_tipo AND id_agente = %d
			GROUP BY id_tipo_modulo', $id_agent);
	$modules = get_db_all_rows_sql ($sql);
	foreach ($modules as $module) {
		$data[$module['nombre']] = $module[1];
	}
	generic_pie_graph ($width, $height, $data);
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
		$data[$name] = (int) get_db_value_filter ('COUNT(*)',
			'tagent_access',
			array ('id_agent' => $id_agent,
				'utimestamp > '.$bottom,
				'utimestamp < '.$top));
	}

	if (! $graphic_type) {
		return fs_2d_area_chart ($data, $width, $height, $resolution / 1000, ';decimalPrecision=0');
	}

	$engine = get_graph_engine ($period);
	
	$engine->width = $width;
	$engine->height = $height;
	$engine->data = $data;
	$engine->max_value = max ($data);
	$engine->show_title = false;
	$engine->fontpath = $config['fontpath'];
	$engine->xaxis_interval = floor ($width / 72);
	$engine->xaxis_format = 'date';
	$engine->watermark = false;
	$engine->show_grid = false;
	
	$engine->single_graph ();
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
		return fs_agent_event_chart ($data, $width, $height, $resolution / 750);
	}
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
	
	if (! $graphic_type) {
		return fs_3d_pie_chart ($data, 370, 180);
	}

	generic_pie_graph (370, 180, $data);
}

/**
 * Print a pie graph with priodity incident
 */
function grafico_incidente_prioridad () {
	global $config;
	global $graphic_type;

	$data_tmp = array (0, 0, 0, 0, 0, 0);
	$sql = 'SELECT COUNT(id_incidencia), prioridad
		FROM tincidencia GROUP BY prioridad
		ORDER BY 2 DESC';
	$incidents = get_db_all_rows_sql ($sql);
	foreach ($incidents as $incident) {
		if ($incident['prioridad'] < 5)
			$data_tmp[$incident[1]] = $incident[0];
		else
			$data_tmp[5] += $incident[0];
	}
	$data = array (__('Informative') => $data_tmp[0],
			__('Low') => $data_tmp[1],
			__('Medium') => $data_tmp[2],
			__('Serious') => $data_tmp[3],
			__('Very serious') => $data_tmp[4],
			__('Maintenance') => $data_tmp[5]);
	
	if (! $graphic_type) {
		return fs_3d_pie_chart ($data, 320, 200);
	}

	generic_pie_graph (320, 200, $data);
}

/**
 * Print a pie graph with incident data by group
 */
function graphic_incident_group () {
	global $config;
	global $graphic_type;

	$data = array ();
	$max_items = 5;
	$sql = sprintf ('SELECT COUNT(id_incidencia), nombre
			FROM tincidencia,tgrupo
			WHERE tgrupo.id_grupo = tincidencia.id_grupo
			GROUP BY tgrupo.id_grupo ORDER BY 1 DESC LIMIT %d',
			$max_items);
	$incidents = get_db_all_rows_sql ($sql);
	foreach ($incidents as $incident) {
		$name = $incident[1].' ('.$incident[0].')';
		$data[$name] = $incident[0];
	}

	if (! $graphic_type) {
		return fs_3d_pie_chart ($data, 320, 200);
	}

	generic_pie_graph (320, 200, $data);
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
	$sql = sprintf ('SELECT COUNT(id_incidencia), id_usuario
			FROM tincidencia GROUP BY id_usuario
			ORDER BY 1 DESC LIMIT %d', $max_items);
	$incidents = get_db_all_rows_sql ($sql);
	foreach ($incidents as $incident) {
		$name = $incident[1].' ('.$incident[0].')';
		$data[$name] = $incident[0];
	}
	
	if (! $graphic_type) {
		return fs_3d_pie_chart ($data, 320, 200);
	}

	generic_pie_graph (320, 200, $data);
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
	$sql = sprintf ('SELECT COUNT(id_usuario), id_usuario
			FROM tsesion GROUP BY id_usuario
			ORDER BY 1 DESC LIMIT %d', $max_items);
	$logins = get_db_all_rows_sql ($sql);
	foreach ($logins as $login) {
		$data[$login[1]] = $login[0];
	}
	
	if (! $graphic_type) {
		return fs_3d_pie_chart ($data, $width, $height);
	}

 	generic_pie_graph ($width, $height, $data);
}

/**
 * Print a pie graph with access data of incidents source
 * 
 * @param integer width pie graph width
 * @param integer height pie graph height
 */
function graphic_incident_source ($width = 320, $height = 200) {
	global $config;
	global $graphic_type;

	$data = array ();
	$max_items = 5;
	$sql = sprintf ('SELECT COUNT(id_incidencia), origen 
			FROM tincidencia GROUP BY `origen`
			ORDER BY 1 DESC LIMIT %d', $max_items);
	$origins = get_db_all_rows_sql ($sql);
	foreach ($origins as $origin) {
		$data[$origin[1]] = $origin[0];
	}
	
	if (! $graphic_type) {
		return fs_3d_pie_chart ($data, $width, $height);
	}

	generic_pie_graph ($width, $height, $data);
}

/**
 * Print a horizontal bar graph with modules data of agents
 * 
 * @param integer height graph height
 * @param integer width graph width
 */
function graph_db_agentes_modulos ($width, $height) {
	global $config;
	global $graphic_type;

	$data = array ();
	
	$modules = get_db_all_rows_sql ('SELECT COUNT(id_agente_modulo),id_agente
					FROM tagente_modulo GROUP BY id_agente
					ORDER BY 1 DESC LIMIT 8');
	if ($modules === false)
		$modules = array ();
	
	foreach ($modules as $module) {
		$agent_name = get_agent_name ($module['id_agente'], "none");
		$data[$agent_name] = $module[0];
	}

	if (! $graphic_type) {
		return fs_3d_bar_chart ($data, $width, $height);
	}
	
	generic_horizontal_bar_graph ($width, $height, $data);
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
	$sql = sprintf ('SELECT COUNT(id_evento),id_usuario
			FROM tevento GROUP BY id_usuario
			ORDER BY 1 DESC LIMIT %d', $max_items);
	$events = get_db_all_rows_sql ($sql);
	foreach ($events as $event) {
		$data[$event[1]] = $event[0];
	}
	
	if (! $graphic_type) {
		return fs_3d_pie_chart ($data, $width, $height);
	}

	generic_pie_graph ($width, $height, $data);
}

/**
 * Print a pie graph with events data in 320x200 size
 * 
 * @param string filter Filter for query in DB
 */
function grafico_eventos_total ($filter = "") {
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
	
	if (! $graphic_type) {
		return fs_3d_pie_chart ($data, 320, 200);
	}

	generic_pie_graph (320, 200, $data);
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
	
	// Take only the first $max_items values
	if (sizeof ($data) >= $max_items) {
		$data = array_slice ($data, 0, $max_items);
	}
	
	if (! $graphic_type) {
		return fs_3d_pie_chart ($data, $width, $height);
	}

	generic_pie_graph ($width, $height, $data,
		array ('zoom' => 75,
			'show_legend' => false));
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
	define (NUM_PIECES_PIE, 6);	

	$badstrings = array (";", "SELECT ", "DELETE ", "UPDATE ", "INSERT ", "EXEC");	
	//remove bad strings from the query so queries like ; DELETE FROM  don't pass
	$url = str_ireplace ($badstrings, "", $url);
		
	//This will give the distinct id_agente, give the id_grupo that goes
	//with it and then the number of times it occured. GROUP BY statement
	//is required if both DISTINCT() and COUNT() are in the statement 
	$sql = sprintf ('SELECT DISTINCT(id_agente) AS id_agente, id_grupo, COUNT(id_agente) AS count
		FROM tevento WHERE 1=1 %s
		GROUP BY id_agente ORDER BY count DESC', $url); 
	
	$result = get_db_all_rows_sql ($sql);
	if ($result === false) {
		$result = array();
	}
 
	foreach ($result as $row) {
		if (!give_acl ($config["id_user"], $row["id_grupo"], "AR") == 1)
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
	
	if (! $graphic_type) {
		return fs_3d_pie_chart ($data, $width, $height);
	}

	error_reporting (0);
	generic_pie_graph ($width, $height, $data, array ('show_legend' => false));
}

/**
 * Print a single graph with data
 * 
 * @param integer width graph width
 * @param integer height graph height
 * @param mixed data Data for make the graph
 * @param integer interval interval to print
 */
function generic_single_graph ($width = 380, $height = 200, &$data, $interval = 1) {
	global $config;
	
	if (sizeof ($data) == 0)
		graphic_error ();
	
	$engine = get_graph_engine ();
	$engine->width = $width;
	$engine->height = $height;
	$engine->data = &$data;
	$engine->max_value = max ($data);
	$engine->fontpath = $config['fontpath'];
	$engine->xaxis_interval = $interval;
	
	$engine->single_graph ();
}

/**
 * Print a vertical bar graph with data
 * 
 * @param integer width graph width
 * @param integer height graph height
 * @param mixed data Data for make the graph
 * @param string legend Legend to show in graph
 */
function generic_vertical_bar_graph ($width = 380, $height = 200, &$data, &$legend) {
	global $config;
	
	if (sizeof ($data) == 0)
		graphic_error ();
	
	$engine = get_graph_engine ();
	
	$engine->width = $width;
	$engine->height = $height;
	$engine->data = &$data;
	$engine->max_value = max ($data);
	$engine->legend = &$legend;
	$engine->fontpath = $config['fontpath'];
	$engine->vertical_bar_graph ();
}

/**
 * Print a horizontal bar graph with data
 * 
 * @param integer width graph width
 * @param integer height graph height
 * @param mixed data Data for make the graph
 * @param string legend Legend to show in graph
 */
function generic_horizontal_bar_graph ($width = 380, $height = 200, &$data, $legend = false) {
	global $config;
	
	if (sizeof ($data) == 0)
		graphic_error ();
	
	$engine = get_graph_engine ();
	
	$engine->width = $width;
	$engine->height = $height;
	$engine->data = &$data;
	$engine->legend = &$legend;
	$engine->fontpath = $config['fontpath'];
	
	$engine->horizontal_bar_graph ();
}

/**
 * Print a pie graph with data
 * 
 * @param integer width pie graph width
 * @param integer height pie graph height
 * @param mixed data Data for make the graph
 * @param mixed options Options for show graph as 'show_title', 'show_legend' and 'zoom'
 */
function generic_pie_graph ($width = 300, $height = 200, &$data, $options = false) {
	global $config;
	
	if (sizeof ($data) == 0)
		graphic_error ();
	
	$show_title = false;
	$show_legend = true;
	$zoom = 85;
	if (is_array ($options)) {
		if (isset ($options['show_title']))
			$show_title = (bool) $options['show_title'];
		if (isset ($options['show_legend']))
			$show_legend = (bool) $options['show_legend'];
		if (isset ($options['zoom']))
			$zoom = (bool) $options['zoom'];
	}
	
	$engine = get_graph_engine ();
	
	$engine->width = $width;
	$engine->height = $height;
	$engine->data = &$data;
	$engine->zoom = $zoom;
	$engine->legend = array_keys ($data);
	$engine->show_legend = $show_legend;
	$engine->show_title = $show_title;
	$engine->zoom = 50;
	$engine->fontpath = $config['fontpath'];
	$engine->pie_graph ();
}

/**
 * Print a horizontal bar graph with packets data of agents
 * 
 * @param integer width pie graph width
 * @param integer height pie graph height
 */
function grafico_db_agentes_paquetes ($width = 380, $height = 300) {
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
		$data[$agents[$agent_id]] = $value;
	}

	if (! $graphic_type) {
		return fs_3d_bar_chart ($data, $width, $height);
	}
	
	generic_horizontal_bar_graph ($width, $height, $data, $legend);
}

/**
 * Print a pie graph with purge data of agent
 * 
 * @param integer id_agent ID of agent to show
 * @param integer width pie graph width
 * @param integer height pie graph height
 */
function grafico_db_agentes_purge ($id_agent, $width, $height) {
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

	if (! $graphic_type) {
		return fs_3d_pie_chart ($data, $width, $height);
	}
	
	generic_pie_graph ($width, $height, $data);
}

/**
 * Draw a dynamic progress bar using GDlib directly
 * 
 * @param integer progress bar progress
 * @param integer height pie graph height
 * @param integer width pie graph width
 * @param integer mode style of graph (0 or 1)
 */
function progress_bar ($progress, $width, $height, $mode = 1) {
	global $config;

	if ($progress > 100 || $progress < 0) {
		graphic_error ('outof.png');
	}
	
	$engine = get_graph_engine ();
	
	$engine->width = $width;
	$engine->height = $height;
	$engine->fontpath = $config['fontpath'];
	
	if ($mode == 0) {
		$engine->background_color = '#E6E6D2';
		$engine->show_title = false;
		if ($progress > 70) 
			$color = '#B0FF54';
		elseif ($progress > 50)
			$color = '#FFE654';
		elseif ($progress > 30)
			$color = '#FF9A54';
		else
			$color = '#EE0000';
	} else {
		$engine->background_color = '#FFFFFF';
		$engine->show_title = true;
		$engine->title = format_numeric ($progress).' %';
		$color = '#2C5196';
	}
	
	$engine->progress_bar ($progress, $color);
}

function grafico_modulo_sparse ($agent_module_id, $period, $show_events,
				$width, $height , $title, $unit_name,
				$show_alerts, $avg_only = 0, $pure = false,
				$date = 0) {
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

	// Get event data (contains alert data too)
	if ($show_events == 1 || $show_alerts == 1) {
		$events = get_db_all_rows_filter ('tevento',
			array ('id_agentmodule' => $agent_module_id,
				"utimestamp > $datelimit",
				"utimestamp < $date",
				'order' => 'utimestamp ASC'),
			array ('evento', 'utimestamp'));
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

	// Check available data
	if (count ($data) < 2) {
		if (!$graphic_type) {
			return fs_error_image ();
		}
		graphic_error ();
	}

	// Calculate chart data
	$chart = array();
	$module_data = array_shift ($data);
	$event_data = array_shift ($events);
	if ($module_data['utimestamp'] == $datelimit) {
		$previous_data = $module_data['datos'];
		$min_value = $module_data['datos'];
		$max_value = $module_data['datos'];
	} else {
		$previous_data = 0;
		$min_value = 0;
		$max_value = 0;
	}

	for ($iterator = 0; $iterator < $resolution; $iterator++) {
		$timestamp = $datelimit + ($interval * $iterator);

		$total = 0;
		$count = 0;
		
		// Read data that falls in the current interval
		$interval_min = $previous_data;
		$interval_max = $previous_data;
		while ($module_data !== null && $module_data ['utimestamp'] >= $timestamp && $module_data ['utimestamp'] < ($timestamp + $interval)) {
			if ($module_data['datos'] > $interval_max) {
				$interval_max = $module_data['datos'];
			} else if ($module_data['datos'] < $interval_max) {
				$interval_min = $module_data['datos'];
			}
			$total += $module_data['datos'];
			$count++;
			$module_data = array_shift ($data);
		}

		// Average
		if ($count > 0) {
			$total /= $count;
		}

		// Min and max
		if ($interval_max > $max_value) {
			$max_value = $interval_max;
		} else if ($interval_min < $min_value) {
			$min_value = $interval_min;
		}

		// Read events and alerts that fall in the current interval
		$event_value = 0;
		$alert_value = 0;
		while ($event_data !== null && $event_data ['utimestamp'] >= $timestamp && $event_data ['utimestamp'] <= ($timestamp + $interval)) {
			if ($show_events == 1) {
				$event_value++;
			}
			if ($show_alerts == 1 && substr ($event_data['event_type'], 0, 5) == 'alert') {
				$alert_value++;
			}
			$event_data = array_shift ($events);
		}

		// Data
		if ($count > 0) {
			$chart[$timestamp]['sum'] = $total;
			$previous_data = $total;
		// Compressed data
		} else {
			$chart[$timestamp]['sum'] = $previous_data;
		}

		$chart[$timestamp]['count'] = 0;
		$chart[$timestamp]['timestamp_bottom'] = $timestamp;
		$chart[$timestamp]['timestamp_top'] = $timestamp + $interval;
		$chart[$timestamp]['min'] = $interval_min;
		$chart[$timestamp]['max'] = $interval_max;
		$chart[$timestamp]['event'] = $event_value;
		$chart[$timestamp]['alert'] = $alert_value;
	}
	
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

    // Flash chart
	if (! $graphic_type) {	
		return fs_module_chart ($chart, $width, $height, $avg_only, $resolution / 10, $time_format, $show_events, $show_alerts);
	}
	
	$engine = get_graph_engine ($period);
	$engine->width = $width;
	$engine->height = $height;
	$engine->data = &$chart;
	$engine->xaxis_interval = $resolution;
	if ($title == '')
		$title = get_agentmodule_name ($id_agente_modulo);
	$engine->title = '   '.strtoupper ($nombre_agente)." - ".__('Module').' '.$title;
	$engine->subtitle = '     '.__('Period').': '.$title_period;
	$engine->show_title = !$pure;
	$engine->max_value = $max_value;
	$engine->min_value = $min_value;
	$engine->events = (bool) $show_event;
	$engine->alert_top = false;
	$engine->alert_bottom = false;;
	if (! $pure) {
		$engine->legend = &$legend;
	}
	$engine->fontpath = $config['fontpath'];
	
	$engine->sparse_graph ($period, $avg_only, $min_value, $max_value, $unit_name);
}

function grafico_modulo_boolean ($agent_module_id, $period, $show_events,
	 $width, $height , $title, $unit_name, $show_alerts, $avg_only = 0, $pure=0,
	 $date = 0 ) {
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
	
	// Get event data (contains alert data too)
	if ($show_events == 1 || $show_alerts == 1) {
		$events = get_db_all_rows_filter ('tevento',
			array ('id_agentmodule' => $agent_module_id,
				"utimestamp > $datelimit",
				"utimestamp < $date",
				'order' => 'utimestamp ASC'),
			array ('evento', 'utimestamp'));
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

	// Check available data
	if (count ($data) < 2) {
		if (!$graphic_type) {
			return fs_error_image ();
		}
		graphic_error ();
	}

	// Calculate chart data
	$chart = array();
	$module_data = array_shift ($data);
	$event_data = array_shift ($events);
	if ($module_data['utimestamp'] == $datelimit) {
		$previous_data = $module_data['datos'];
		$max_value = $module_data['datos'];
	} else {
		$previous_data = 0;
		$max_value = 0;
	}

	for ($iterator = 0; $iterator < $resolution; $iterator++) {
		$timestamp = $datelimit + ($interval * $iterator);

		$zero = 0;
		$total = 0;
		$count = 0;
		
		// Read data that falls in the current interval
		while ($module_data !== null && $module_data ['utimestamp'] >= $timestamp && $module_data ['utimestamp'] <= ($timestamp + $interval)) {
			if ($module_data['datos'] == 0) {
				$zero = 1;
			} else {
				$total += $module_data['datos'];
				$count++;
			}

			$module_data = array_shift ($data);
		}

		// Average
		if ($count > 0) {
			$total /= $count;
		}

		// Max
		if ($total > $max_value) {
			$max_value = $total;
		}

		// Read events and alerts that fall in the current interval
		$event_value = 0;
		$alert_value = 0;
		while ($event_data !== null && $event_data ['utimestamp'] >= $timestamp && $event_data ['utimestamp'] < ($timestamp + $interval)) {
			if ($show_events == 1) {
				$event_value++;
			}
			if ($show_alerts == 1 && substr ($event_data['event_type'], 0, 5) == 'alert') {
				$alert_value++;
			}
			$event_data = array_shift ($events);
		}

		// Data and zeroes (draw a step)
		if ($zero == 1 && $count > 0) {
			$chart[$timestamp]['sum'] = $total;
			$chart[$timestamp + 1] = array ('sum' => 0,
			                                'count' => 0,
			                                'timestamp_bottom' => $timestamp,
			                                'timestamp_top' => $timestamp + $interval,
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
			$chart[$timestamp]['sum'] = $previous_data;
		}

		$chart[$timestamp]['count'] = 0;
		$chart[$timestamp]['timestamp_bottom'] = $timestamp;
		$chart[$timestamp]['timestamp_top'] = $timestamp + $interval;
		$chart[$timestamp]['min'] = 0;
		$chart[$timestamp]['max'] = 0;
		$chart[$timestamp]['event'] = $event_value;
		$chart[$timestamp]['alert'] = $alert_value;
	}
	
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

    // Flash chart
	if (! $graphic_type) {	
		return fs_module_chart ($chart, $width, $height, $avg_only, $resolution / 10, $time_format, $show_events, $show_alerts);
	}

	$chart_data = array ();
	foreach ($chart as $chart_element) {
		$chart_data[$chart_element['timestamp_bottom']] = $chart_element['sum'];
	}

	// Image chart
	$engine = get_graph_engine ($period);
	
	$engine->width = $width;
	$engine->height = $height;
	$engine->data = &$chart_data;
	$engine->max_value = 1;
	$engine->legend = array ($module_name);
	$engine->title = '   '.strtoupper ($agent_name)." - ".__('Module').' '.$title;
	$engine->subtitle = '     '.__('Period').': '.$title_period;
	$engine->show_title = !$pure;
	$engine->events = false;
	$engine->fontpath = $config['fontpath'];
	$engine->alert_top = false;
	$engine->alert_bottom = false;;

	$engine->xaxis_interval = $resolution / 10; // Fixed to 10 ticks
	$engine->xaxis_format = 'date';
	
	$engine->single_graph ();
	
	return;
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
	 $date = 0) {
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
	
	// Get event data (contains alert data too)
	if ($show_events == 1 || $show_alerts == 1) {
		$events = get_db_all_rows_filter ('tevento',
			array ('id_agentmodule' => $agent_module_id,
				"utimestamp > $datelimit",
				"utimestamp < $date",
				'order' => 'utimestamp ASC'),
			array ('evento', 'utimestamp'));
		if ($events === false) {
			$events = array ();
		}
	}

	// Get module data
	$data = get_db_all_rows_filter ('tagente_datos_string',
		array ('id_agente_modulo' => $agent_module_id,
			"utimestamp > $datelimit",
			"utimestamp < $date",
			'order' => 'utimestamp ASC'),
		array ('datos', 'utimestamp'));
	if ($data === false) {
		$data = array ();
	}

	// Get previous data
	$previous_data = get_previous_data ($agent_module_id, $datelimit, 1);
	if ($previous_data !== false) {
		$previous_data['utimestamp'] = $datelimit;
		array_unshift ($data, $previous_data);
	}

	// Get next data
	$nextData = get_next_data ($agent_module_id, $date, 1);
	if ($nextData !== false) {
		array_push ($data, $nextData);
	} else if (count ($data) > 0) {
		// Propagate the last known data to the end of the interval
		$nextData = array_pop ($data);
		array_push ($data, $nextData);
		$nextData['utimestamp'] = $date;
		array_push ($data, $nextData);
	}

	// Check available data
	if (count ($data) < 2) {
		if (!$graphic_type) {
			return fs_error_image ();
		}
		graphic_error ();
	}

	// Calculate chart data
	$chart = array();
	$module_data = array_shift ($data);
	$event_data = array_shift ($events);
	if ($module_data['utimestamp'] == $datelimit) {
		$previous_data = 1;
		$max_value = 1;
	} else {
		$previous_data = 0;
		$max_value = 0;
	}

	for ($iterator = 0; $iterator < $resolution; $iterator++) {
		$timestamp = $datelimit + ($interval * $iterator);

		$count = 0;		
		// Read data that falls in the current interval
		while ($module_data !== null && $module_data ['utimestamp'] >= $timestamp && $module_data ['utimestamp'] <= ($timestamp + $interval)) {
			$count++;
			$module_data = array_shift ($data);
		}

		// Max
		if ($count > $max_value) {
			$max_value = $count;
		}

		// Read events and alerts that fall in the current interval
		$event_value = 0;
		$alert_value = 0;
		while ($event_data !== null && $event_data ['utimestamp'] >= $timestamp && $event_data ['utimestamp'] <= ($timestamp + $interval)) {
			if ($show_events == 1) {
				$event_value++;
			}
			if ($show_alerts == 1 && substr ($event_data['event_type'], 0, 5) == 'alert') {
				$alert_value++;
			}
			$event_data = array_shift ($events);
		}

		// Data in the interval
		if ($count > 0) {
			$chart[$timestamp]['sum'] = $count;
			$previous_data = $total;
		// Compressed data
		} else {
			$chart[$timestamp]['sum'] = $previous_data;
		}

		$chart[$timestamp]['count'] = 0;
		$chart[$timestamp]['timestamp_bottom'] = $timestamp;
		$chart[$timestamp]['timestamp_top'] = $timestamp + $interval;
		$chart[$timestamp]['min'] = 0;
		$chart[$timestamp]['max'] = 0;
		$chart[$timestamp]['event'] = $event_value;
		$chart[$timestamp]['alert'] = $alert_value;
	}

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

    // Flash chart
	if (! $graphic_type) {	
		return fs_module_chart ($chart, $width, $height, $avg_only, $resolution / 10, $time_format, $show_events, $show_alerts);
	}
	

	$chart_data = array ();
	foreach ($chart as $chart_element) {
		$chart_data[$chart_element['timestamp_bottom']] = $chart_element['sum'];
	}

	$engine = get_graph_engine ($period);
	
	$engine->width = $width;
	$engine->height = $height;
	$engine->data = &$chart_data;
	$engine->max_value = max ($chart_data);
	
	$engine->legend = array ($module_name);
	$engine->title = '   '.strtoupper ($agent_name)." - ".__('Module').' '.$title;
	$engine->subtitle = '     '.__('Period').': '.$title_period;
	$engine->show_title = !$pure;
	$engine->events = false;
	$engine->fontpath = $config['fontpath'];
	$engine->alert_top = false;
	$engine->alert_bottom = false;;
	
	$engine->xaxis_interval = $resolution/5;		
	$engine->yaxis_interval = 1;
	$engine->xaxis_format = 'datetime';

	$engine->single_graph ();
	return;
}

function grafico_modulo_log4x ($id_agente_modulo, $periodo, $show_event,
	 $width, $height , $title, $unit_name, $show_alert, $avg_only = 0, $pure=0,
	 $date = 0) 
{

        grafico_modulo_log4x_trace("<pre style='text-align:left;'>");

	if ($date == "")
		$now = time ();
	else
	        $now = $date;

        $fechatope = $now - $periodo; // limit date

	$nombre_agente = get_agentmodule_agent_name ($id_agente_modulo);
	$nombre_modulo = get_agentmodule_name ($id_agente_modulo);
	$id_agente = get_agent_id ($nombre_agente);


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
        $result=mysql_query($sql1);
        while ($row=mysql_fetch_array($result)){
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


	//$image = "../../include/fgraph.php?tipo=graphic_error";
        //grafico_modulo_log4x_trace(__LINE__);
	//print_image ($image, false, array ("border" => 0));
        //grafico_modulo_log4x_trace(__LINE__);
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

function myErrorHandler($errno, $errstr, $errfile, $errline)
{
    switch ($errno) {
    case E_USER_ERROR:
        echo "<b>My ERROR</b> [$errno] $errstr<br />\n";
        echo "  Fatal error on line $errline in file $errfile";
        echo ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
        echo "Aborting...<br />\n";
        exit(1);
        break;

    case E_USER_WARNING:
        echo "<b>My WARNING</b> [$errno] $errstr<br />\n";
        break;

    case E_USER_NOTICE:
        echo "<b>My NOTICE</b> [$errno] $errstr<br />\n";
        break;

    default:
        echo "[$errno] $errfile:$errline $errstr<br />\n";
        break;
    }

    /* Don't execute PHP internal error handler */
    return true;
}

// **************************************************************************
// **************************************************************************
//   MAIN Code - Parse get parameters
// **************************************************************************
// **************************************************************************

// Generic parameter handling
// **************************

$id_agent = (int) get_parameter ('id_agent');
$tipo = (string) get_parameter ('tipo');
$pure = (bool) get_parameter ('pure');
$period = (int) get_parameter ('period', 86400);
$interval = (int) get_parameter ('interval', 300);
$id = (string) get_parameter ('id');
$weight_l = (string) get_parameter ('weight_l');
$width = (int) get_parameter ('width', 450);
$height = (int) get_parameter ('height', 200);
$label = (string) get_parameter ('label', '');
$color = (string) get_parameter ('color', '#226677');
$percent = (int) get_parameter ('percent', 100);
$zoom = (int) get_parameter ('zoom', 100);
$zoom /= 100;
if ($zoom <= 0 || $zoom > 1)
	$zoom = 1;
$unit_name = (string) get_parameter ('unit_name');
$draw_events = (int) get_parameter ('draw_events');
$avg_only = (int) get_parameter ('avg_only');
$draw_alerts = (int) get_parameter ('draw_alerts');
$value1 = get_parameter ('value1');
$value2 = get_parameter ('value2');
$value3 = get_parameter("value3", 0);
$stacked = get_parameter ("stacked", 0);
$date = get_parameter ("date");
$graphic_type = (string) get_parameter ('tipo');
$mode = get_parameter ("mode", 1);
$url = get_parameter ("url");

if ($graphic_type) {
	switch ($graphic_type) {
	case 'sparse': 
		grafico_modulo_sparse ($id, $period, $draw_events, $width, $height,
		$label, $unit_name, $draw_alerts, $avg_only, $pure, $date);
		break;
	case "boolean":
		grafico_modulo_boolean ($id, $period, $draw_events, $width, $height , 
		$label, $unit_name, $draw_alerts, 1, $pure, $date);
		
		break;
	case "estado_incidente":
		graph_incidents_status ();
		
		break;
	case "prioridad_incidente":
		grafico_incidente_prioridad ();
		
		break;
	case "db_agente_modulo":
		graph_db_agentes_modulos ($width, $height);
		
		break;
	case "db_agente_paquetes":
		grafico_db_agentes_paquetes ($width, $height);
		
		break;
	case "db_agente_purge":
		grafico_db_agentes_purge ($id, $width, $height);
		
		break;
	case "event_module":
		graph_event_module ($width, $height, $id_agent);
		
		break;
	case "group_events":
		grafico_eventos_grupo ($width, $height,$url);
		
		break;
	case "user_events":
		grafico_eventos_usuario ($width, $height);
		
		break;
	case "total_events":
		grafico_eventos_total ();
		
		break;
	case "group_incident":
		graphic_incident_group ();
		
		break;
	case "user_incident":
		graphic_incident_user ();
		
		break;
	case "source_incident":
		graphic_incident_source ();
		
		break;
	case "user_activity":
		graphic_user_activity ($width, $height);
		
		break;
	case "agentaccess":
		graphic_agentaccess ($id, $width, $height, $period);
		
		break;
	case "agentmodules":
		graphic_agentmodules ($id, $width, $height);
		
		break;
	case "progress": 
		$percent = (int) get_parameter ('percent');
		progress_bar ($percent,$width,$height, $mode);
		
		break;
	case "combined":
		// Split id to get all parameters
		$module_list = array();
		$module_list = split (",", $id);
		$weight_list = array();
		$weight_list = split (",", $weight_l);
		graphic_combined_module ($module_list, $weight_list, $period, $width, $height,
					$label, $unit_name, $draw_events, $draw_alerts, $pure, $stacked, $date);
		
		break;
	case "alerts_fired_pipe":
		$data = array ();
		$data[__('Alerts fired')] = (float) get_parameter ('fired');
		$data[__('Alerts not fired')] = (float) get_parameter ('not_fired');
		generic_pie_graph ($width, $height, $data);
		
		break;
	case 'monitors_health_pipe':
		$data = array ();
		$data[__('Monitors OK')] = (float) get_parameter ('not_down');
		$data[__('Monitors BAD')] = (float) get_parameter ('down');
		generic_pie_graph ($width, $height, $data);
		
		break;
	case 'string':
		grafico_modulo_string ($id, $period, $draw_events, $width, $height, $label, $unit_name, $draw_alerts, $avg_only, $pure, $date);
		break;

	case 'log4x':
		grafico_modulo_log4x ($id, $period, $draw_events, $width, $height, $label, $unit_name, $draw_alerts, $avg_only, $pure, $date);
		break;

	case 'graphic_error':
	default:
		graphic_error ();
	}
}

// For / url has problems rendering http://xxxx//include/FusionCharts/fussionCharts.js
// just make a substitution in / case
if ($config["homeurl"] == "/")
	$pre_url = "";
else
	$pre_url = $config["homeurl"];
?>
<script language="JavaScript" src="<?php echo $pre_url?>/include/FusionCharts/FusionCharts.js"></script>



