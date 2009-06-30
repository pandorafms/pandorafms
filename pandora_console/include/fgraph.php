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



if ($config) {
	require_once ($config['homedir'].'/include/config.php');
	require_once ($config['homedir'].'/include/pandora_graph.php');
	require_once ($config['homedir'].'/include/functions_fsgraph.php');
} else {
	require_once ('../include/config.php');
	require_once ($config['homedir'].'/include/pandora_graph.php');
}

set_time_limit (0);
error_reporting (0);

if (! isset ($config["id_user"])) {
	session_start ();
	session_write_close ();
	$config["id_user"] = $_SESSION["id_usuario"];
}

// Session check
check_login ();

/**
 * Show a brief error message in a PNG graph
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
 */
function graphic_combined_module ($module_list, $weight_list, $period, $width, $height,
				$title, $unit_name, $show_event = 0, $show_alert = 0, $pure = 0, $stacked = 0, $date = 0) {

	global $config;
	global $graphic_type;
	
	$resolution = $config['graph_res'] * 50; // Number of "slices" we want in graph
	
	if (! $date)
		$date = get_system_time ();
	
	$datelimit = $date - $period; // limit date
	$interval = (int) ($period / $resolution); // Each interval is $interval seconds length
	$module_number = count ($module_list);

	// interval - This is the number of "rows" we are divided the time to fill data.
	//	     more interval, more resolution, and slower.
	// periodo - Gap of time, in seconds. This is now to (now-periodo) secs
	
	// Init tables
	for ($i = 0; $i < $module_number; $i++) {
		$real_data[$i] = array();
		$mod_data[$i] = 1; // Data multiplier to get the same scale on all modules
		if ($show_event == 1)
			$real_event[$i] = array ();
		if (isset ($weight_list[$i])) {
			if ($weight_list[$i] == 0)
				$weight_list[$i] = 1;
		} else
			$weight_list[$i] = 1;
	}

	$max_value = 0;
	$min_value = 0;
	// FOR EACH MODULE IN module_list....
	for ($i = 0; $i < $module_number; $i++) {
		$id_agente_modulo = $module_list[$i];
		$agent_name = get_agentmodule_agent_name ($id_agente_modulo);
		$module_name = get_agentmodule_name ($id_agente_modulo);
		$module_list_name[$i] = $agent_name." / ".substr ($module_name, 0, 20);
		for ($j = 0; $j <= $resolution; $j++) {
			$data[$j]['sum'] = 0; // SUM of all values for this interval
			$data[$j]['count'] = 0; // counter
			$data[$j]['timestamp_bottom'] = $datelimit + ($interval * $j); // [2] Top limit for this range
			$data[$j]['timestamp_top'] = $datelimit + ($interval*($j+1)); // [3] Botom limit
			$data[$j]['min'] = 0; // MIN
			$data[$j]['max'] = 0; // MAX
			$data[$j]['events'] = 0; // Event
		}
		
		// Init other general variables
		if ($show_event == 1) {
			// If we want to show events in graphs
			$sql = "SELECT utimestamp FROM tevento WHERE id_agentmodule = $id_agente_modulo AND utimestamp > $datelimit";
			$result = get_db_all_rows_sql ($sql);
			if ($result === false)
				$result = array ();
			foreach ($result as $row) {
				$utimestamp = $row['utimestamp'];
				for ($i = 0; $i <= $resolution; $i++) {
					if ($utimestamp <= $data[$i]['timestamp_top'] && $utimestamp >= $data[$i]['timestamp_bottom']) {
						$real_event[$i] = 1;
					}
				}
			}
		}
		
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
		
		// Get the first data outsite (to the left---more old) of the interval given
		$previous = (float) get_previous_data ($id_agente_modulo, $datelimit);
		
		$result = get_db_all_rows_filter ('tagente_datos',
			array ('id_agente_modulo' => $id_agente_modulo,
				"utimestamp > $datelimit",
				"utimestamp < $date",
				'order' => 'utimestamp ASC'),
			array ('datos', 'utimestamp'));
		
		if ($result === false) {
			if (! $graphic_type) {
				return fs_error_image ();
			}
			graphic_error ();
		}
		
		foreach ($result as $row) {
			$datos = $row["datos"];
			$utimestamp = $row["utimestamp"];
			for ($j = 0; $j <= $resolution; $j++) {
				if ($utimestamp <= $data[$j]['timestamp_top'] && $utimestamp > $data[$j]['timestamp_bottom']) {
					$data[$j]['sum']=$data[$j]['sum']+$datos;
					$data[$j]['count']++;
					// Init min value
					if ($data[$j]['min'] == 0)
						$data[$j]['min'] = $datos;
					else {
						// Check min value
						if ($datos < $data[$j]['min'])
						$data[$j]['min'] = $datos;
					}
					// Check max value
					if ($datos > $data[$j]['max'])
						$data[$j]['max'] = $datos;
					break;
				}
			}
		}
		
		// Calculate Average value for $data[][0]
		for ($j = 0; $j <= $resolution; $j++) {
			if ($data[$j]['count'] > 0){
				$real_data[$i][$j] =  $weight_list[$i] * ($data[$j]['sum']/$data[$j]['count']);
				$data[$j]['sum'] = $data[$j]['sum']/$data[$j]['count'];
			} else {
				$data[$j]['sum'] = $previous;
				$real_data[$i][$j] = $previous * $weight_list[$i];
				$data[$j]['min'] = $previous;
				$data[$j]['max'] = $previous;
			}
			// Get max value for all graph
			if ($data[$j]['max'] > $max_value) {
				$max_value = $data[$j]['max'];
			}
			// This stores in mod_data max values for each module
			if ($mod_data[$i] < $data[$j]['max']) {
				$mod_data[$i] = $data[$j]['max'];
			}
			// Take prev. value
			// TODO: CHeck if there are more than 24hours between
			// data, if there are > 24h, module down.
			$previous = $data[$j]['sum'];
		}
	}

	for ($i = 0; $i < $module_number; $i++) {
		if ($weight_list[$i] != 1)
			$module_list_name[$i] .= " (x". format_numeric ($weight_list[$i], 1).")";
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
		return fs_combined_chart ($real_data, $data, $module_list_name, $width, $height, $stacked, $resolution / 10, $time_format);
	}

	$engine = get_graph_engine ($period);
	
	$engine->width = $width;
	$engine->height = $height;
	$engine->data = &$real_data;
	$engine->legend = $module_list_name;
	$engine->fontpath = $config['fontpath'];
	$engine->title = '   '.strtoupper ($nombre_agente)." - ".__('Module').' '.$title;
	$engine->subtitle = '     '.__('Period').': '.$title_period;
	$engine->show_title = !$pure;
	$engine->stacked = $stacked;
	$engine->xaxis_interval = $resolution;
	$events = $show_event ? $real_event : false;
	$alerts = $show_alert ? array ('low' => $alert_low, 'high' => $alert_high) : false;
	$engine->combined_graph ($data, $events, $alerts, $unit_name, $max_value, $stacked);
}

function grafico_modulo_sparse ($id_agente_modulo, $period, $show_event,
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
		array ('datos', 'utimestamp'));
	
	if ($all_data === false) {
		if (! $graphic_type) {
			return fs_error_image ('../images');
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
	$engine->max_value = $engine->max_value = max ($data);
	$engine->show_title = false;
	$engine->fontpath = $config['fontpath'];
	$engine->xaxis_interval = floor ($width / 72);
	$engine->xaxis_format = 'date';
	$engine->watermark = false;
	$engine->show_grid = false;
	
	$engine->single_graph ();
}

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

function graph_db_agentes_modulos ($width, $height) {
	global $config;
	global $graphic_type;

	$data = array ();
	
	$modules = get_db_all_rows_sql ('SELECT COUNT(id_agente_modulo),id_agente
					FROM tagente_modulo GROUP BY id_agente
					ORDER BY 1 DESC');
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

function graph_event_module ($width = 300, $height = 200, $id_agent) {
	global $config;
	global $graphic_type;

	$data = array ();
	$max_items = 6;
	$sql = sprintf ('SELECT COUNT(id_evento),nombre
			FROM tevento, tagente_modulo
			WHERE id_agentmodule = id_agente_modulo
			AND disabled = 0 AND tevento.id_agente = %d
			GROUP BY id_agentmodule LIMIT %d', $id_agent, $max_items);
	$events = get_db_all_rows_sql ($sql);
	if ($events === false) {
		graphic_error ();
		return;
	}
	foreach ($events as $event) {
		$data[$event['nombre'].' ('.$event[0].')'] = $event[0];
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
	
	$data[__("Today")] = get_db_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos WHERE utimestamp > %d %s", $time["1day"], $query));
	$data["1 ".__("Week")] = get_db_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos WHERE utimestamp > %d %s", $time["1week"], $query));
	$data["1 ".__("Month")] = get_db_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos WHERE utimestamp > %d %s", $time["1month"], $query));
	$data["3 ".__("Months")] = get_db_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos WHERE utimestamp > %d %s", $time["3month"], $query));
	$data[__("Older")] = get_db_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos WHERE 1=1 %s", $query));
	
	$data[__("Today")] += get_db_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_string WHERE utimestamp > %d %s", $time["1day"], $query));
	$data["1 ".__("Week")] += get_db_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_string WHERE utimestamp > %d %s", $time["1week"], $query));
	$data["1 ".__("Month")] += get_db_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_string WHERE utimestamp > %d %s", $time["1month"], $query));
	$data["3 ".__("Months")] += get_db_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_string WHERE utimestamp > %d %s", $time["3month"], $query));
	$data[__("Older")] += get_db_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_string WHERE 1=1 %s", $query));

	$data[__("Older")] = $data[__("Older")] - $data["3 ".__("Months")];

	if (! $graphic_type) {
		return fs_3d_pie_chart ($data, $width, $height);
	}
	
	generic_pie_graph ($width, $height, $data);
}

// ***************************************************************************
// Draw a dynamic progress bar using GDlib directly
// ***************************************************************************

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

function grafico_modulo_boolean ($id_agente_modulo, $period, $show_event,
	 $width, $height , $title, $unit_name, $show_alert, $avg_only = 0, $pure=0,
	 $date = 0 ) {
	global $config;
	global $graphic_type;

	$resolution = $config['graph_res'] * 50; // Number of "slices" we want in graph
	
	if (! $date)
		$date = get_system_time ();
	
	$datelimit = $date - $period; // limit date
	$interval = (int) ($period / $resolution); // Each interval is $interval seconds length
	$nombre_agente = get_agentmodule_agent_name ($id_agente_modulo);
	$id_agente = get_agent_id ($nombre_agente);
	$nombre_modulo = get_agentmodule_name ($id_agente_modulo);

	if ($show_event == 1)
		$real_event = array ();

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

	// interval - This is the number of "rows" we are divided the time
	 // to fill data. more interval, more resolution, and slower.
	// periodo - Gap of time, in seconds. This is now to (now-periodo) secs
	
	// Init tables
	for ($i = 0; $i <= $resolution; $i++) {
		$data[$i]['sum'] = 0; // SUM of all values for this interval
		$data[$i]['count'] = 0; // counter
		$data[$i]['timestamp_bottom'] = $datelimit + ($interval * $i); // [2] Top limit for this range
		$data[$i]['timestamp_top'] = $datelimit + ($interval * ($i + 1)); // [3] Botom limit
		$data[$i]['min'] = 1; // MIN
		$data[$i]['max'] = 0; // MAX
		$data[$i]['last'] = 0; // Last value
		$data[$i]['events'] = 0; // Event
	}
	// Init other general variables
	if ($show_event == 1) {
		// If we want to show events in graphs
		$sql = "SELECT utimestamp FROM tevento WHERE id_agente = $id_agente AND utimestamp > $datelimit";
		$result = get_db_all_rows_sql ($sql);
		foreach ($result as $row) {
			$utimestamp = $row['utimestamp'];
			for ($i = 0; $i <= $resolution; $i++) {
				if ($utimestamp <= $data[$i]['timestamp_top'] && $utimestamp >= $data[$i]['timestamp_bottom']) {
					$data['events']++;
				}
			}
		}
	}
	// Init other general variables
	$max_value = 0;
	
	$all_data = get_db_all_rows_filter ('tagente_datos',
		array ('id_agente_modulo' => $id_agente_modulo,
			"utimestamp > $datelimit",
			"utimestamp < $date",
			'order' => 'utimestamp ASC'),
		array ('datos', 'utimestamp'));
	
	if ($all_data === false) {
		$all_data = array ();
	}
	
	foreach ($all_data as $module_data) {
		$real_data = intval ($module_data["datos"]) ? 1 : 0;
		$utimestamp = $module_data["utimestamp"];
		for ($i = 0; $i <= $resolution; $i++) {
			if ($utimestamp <= $data[$i]['timestamp_top'] && $utimestamp >= $data[$i]['timestamp_bottom']) {
				$data[$i]['sum'] += $real_data;
				$data[$i]['count']++;
				
				$data[$i]['last'] = $real_data;
				
				$data[$i]['min'] = min ($data[$i]['min'], $real_data);
				$data[$i]['max'] = max ($data[$i]['max'], $real_data);
			}
		}
		
	}
	
	$previous = (float) get_previous_data ($id_agente_modulo, $datelimit);
	// Calculate Average value for $data[][0]
	for ($i = 0; $i <= $resolution; $i++) {
		if ($data[$i]['count'] == 0) {
			$data[$i]['sum'] = $previous;
			$data[$i]['min'] = $previous;
			$data[$i]['max'] = $previous;
			$data[$i]['last'] = $previous;
			
			$previous = $data[$i]['sum'];
		} else {
			$previous = $data[$i]['sum'];
			if ($data[$i]['count'] > 1) {
				$previous = $data[$i]['last'];
				$data[$i]['sum'] = floor ($data[$i]['sum'] / $data[$i]['count']);
			}
		}
		
		// Get max value for all graph
		$max_value = max ($max_value, $data[$i]['max']);
	}
	
	$grafica = array ();
	foreach ($data as $d) {
		$grafica[$d['timestamp_bottom']] = $d['sum'];
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
	$engine->data = &$grafica;
	$engine->max_value = $max_value;
	$engine->legend = array ($nombre_modulo);
	$engine->title = '   '.strtoupper ($nombre_agente)." - ".__('Module').' '.$title;
	$engine->subtitle = '     '.__('Period').': '.$title_period;
	$engine->show_title = !$pure;
	$engine->events = $show_event ? $real_event : false;
	$engine->fontpath = $config['fontpath'];
	$engine->alert_top = $show_alert ? $alert_high : false;
	$engine->alert_bottom = $show_alert ? $alert_low : false;;
	
	if ($period < 10000)
		$engine->xaxis_interval = 20;
	else
		$engine->xaxis_interval = $resolution * 4;
	$engine->yaxis_interval = 1;
	$engine->xaxis_format = 'date';
	
	$engine->single_graph ();
	
	return;
}

function grafico_modulo_string ($id_agente_modulo, $period, $show_event,
	 $width, $height , $title, $unit_name, $show_alert, $avg_only = 0, $pure=0,
	 $date = 0) {
	global $config;
	global $graphic_type;

	$resolution = $config['graph_res'] * 50; // Number of "slices" we want in graph
	
	if (! $date)
		$date = get_system_time ();
	
	$datelimit = $date - $period; // limit date
	$interval = (int) ($period / $resolution); // Each interval is $interval seconds length
	$nombre_agente = get_agentmodule_agent_name ($id_agente_modulo);
	$id_agente = get_agent_id ($nombre_agente);
	$nombre_modulo = get_agentmodule_name ($id_agente_modulo);

	if ($show_event == 1)
		$real_event = array ();

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

	// interval - This is the number of "rows" we are divided the time
	 // to fill data. more interval, more resolution, and slower.
	// periodo - Gap of time, in seconds. This is now to (now-periodo) secs
	
	// Init tables
	for ($i = 0; $i <= $resolution; $i++) {
		$data[$i]['sum'] = 0; // SUM of all values for this interval
		$data[$i]['count'] = 0; // counter
		$data[$i]['timestamp_bottom'] = $datelimit + ($interval * $i); // [2] Top limit for this range
		$data[$i]['timestamp_top'] = $datelimit + ($interval * ($i + 1)); // [3] Botom limit
		$data[$i]['min'] = 1; // MIN
		$data[$i]['max'] = 0; // MAX
		$data[$i]['last'] = 0; // Last value
		$data[$i]['events'] = 0; // Event
	}
	// Init other general variables
	if ($show_event == 1) {
		// If we want to show events in graphs
		$sql = "SELECT utimestamp FROM tevento WHERE id_agente = $id_agente AND utimestamp > $datelimit";
		$result = get_db_all_rows_sql ($sql);
		foreach ($result as $row) {
			$utimestamp = $row['utimestamp'];
			for ($i = 0; $i <= $resolution; $i++) {
				if ($utimestamp <= $data[$i]['timestamp_top'] && $utimestamp >= $data[$i]['timestamp_bottom']) {
					$data['events']++;
				}
			}
		}
	}
	// Init other general variables
	$max_value = 0;
	
	$all_data = get_db_all_rows_filter ('tagente_datos_string',
		array ('id_agente_modulo' => $id_agente_modulo,
			"utimestamp > $datelimit",
			"utimestamp < $date",
			'order' => 'utimestamp ASC'),
		array ('datos', 'utimestamp'));
	
	if ($all_data === false) {
		$all_data = array ();
	}
	
	foreach ($all_data as $module_data) {
		$real_data = 1;
		$utimestamp = $module_data["utimestamp"];
		for ($i = 0; $i <= $resolution; $i++) {
			if ($utimestamp <= $data[$i]['timestamp_top'] && $utimestamp >= $data[$i]['timestamp_bottom']) {
				$data[$i]['sum'] += $real_data;
				$data[$i]['count']++;
				
				$data[$i]['last'] = $real_data;
				
				$data[$i]['min'] = min ($data[$i]['min'], $real_data);
				$data[$i]['max'] = max ($data[$i]['max'], $real_data);
			}
		}
		
	}
	
	$previous = (float) get_previous_data ($id_agente_modulo, $datelimit);
	// Calculate Average value for $data[][0]
	for ($i = 0; $i <= $resolution; $i++) {
		if ($data[$i]['count'] == 0) {
			$data[$i]['sum'] = $previous;
			$data[$i]['min'] = $previous;
			$data[$i]['max'] = $previous;
			$data[$i]['last'] = $previous;
			
			$previous = $data[$i]['sum'];
		} else {
			$previous = $data[$i]['sum'];
			if ($data[$i]['count'] > 1) {
				$previous = $data[$i]['last'];
				$data[$i]['sum'] = floor ($data[$i]['sum'] / $data[$i]['count']);
			}
		}
		
		// Get max value for all graph
		$max_value = max ($max_value, $data[$i]['max']);
	}
	
	$grafica = array ();
	foreach ($data as $d) {
		$grafica[$d['timestamp_bottom']] = $d['sum'];
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
	$engine->data = &$grafica;
	$engine->max_value = $max_value;
	$engine->legend = array ($nombre_modulo);
	$engine->title = '   '.strtoupper ($nombre_agente)." - ".__('Module').' '.$title;
	$engine->subtitle = '     '.__('Period').': '.$title_period;
	$engine->show_title = !$pure;
	$engine->events = $show_event ? $real_event : false;
	$engine->fontpath = $config['fontpath'];
	$engine->alert_top = $show_alert ? $alert_high : false;
	$engine->alert_bottom = $show_alert ? $alert_low : false;;
	
	if ($period < 10000)
		$engine->xaxis_interval = 20;
	else
		$engine->xaxis_interval = $resolution * 4;
	$engine->yaxis_interval = 1;
	$engine->xaxis_format = 'date';
	
	$engine->single_graph ();
	
	return;
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
		grafico_modulo_boolean ($id, $period, $draw_events, $width, $height , $label, $unit_name, $draw_alerts, 1, $pure, $date);
		
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
	default:
		graphic_error ();
	}
}
?>
<script language="JavaScript" src="include/FusionCharts/FusionCharts.js"></script>
