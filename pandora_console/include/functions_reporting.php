<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

/**
 * @package Include
 * @subpackage Reporting
 */

/**
 * Include the usual functions
 */
require_once($config["homedir"] . "/include/functions.php");
require_once($config["homedir"] . "/include/functions_db.php");
require_once($config["homedir"] . "/include/functions_agents.php");
include_once($config["homedir"] . "/include/functions_groups.php");
require_once($config["homedir"] . '/include/functions_graph.php');
include_once($config['homedir'] . "/include/functions_modules.php");
include_once($config['homedir'] . "/include/functions_events.php");
include_once($config['homedir'] . "/include/functions_alerts.php");
include_once($config['homedir'] . '/include/functions_users.php');
enterprise_include_once('include/functions_metaconsole.php');
enterprise_include_once('include/functions_inventory.php');
include_once($config['homedir'] . "/include/functions_forecast.php");
include_once($config['homedir'] . "/include/functions_ui.php");
include_once($config['homedir'] . "/include/functions_netflow.php");

/** 
 * Get the average value of an agent module in a period of time.
 * 
 * @param int Agent module id
 * @param int Period of time to check (in seconds)
 * @param int Top date to check the values. Default current time.
 * 
 * @return float The average module value in the interval.
 */
function reporting_get_agentmodule_data_average ($id_agent_module, $period=0, $date = 0) {
	global $config;
	
	// Initialize variables
	if (empty ($date)) $date = get_system_time ();
	$datelimit = $date - $period;

	$search_in_history_db = db_search_in_history_db($datelimit);
	
	$id_module_type = modules_get_agentmodule_type ($id_agent_module);
	$module_type = modules_get_moduletype_name ($id_module_type);
	$uncompressed_module = is_module_uncompressed ($module_type);
	
	// Get module data
	$interval_data = db_get_all_rows_sql ('SELECT *
		FROM tagente_datos 
		WHERE id_agente_modulo = ' . (int) $id_agent_module .
			' AND utimestamp > ' . (int) $datelimit .
			' AND utimestamp < ' . (int) $date .
		' ORDER BY utimestamp ASC', $search_in_history_db);
	if ($interval_data === false) $interval_data = array ();
	
	// Uncompressed module data
	if ($uncompressed_module) {
		$min_necessary = 1;
	
	// Compressed module data
	}
	else {
		// Get previous data
		$previous_data = modules_get_previous_data ($id_agent_module, $datelimit);
		if ($previous_data !== false) {
			$previous_data['utimestamp'] = $datelimit;
			array_unshift ($interval_data, $previous_data);
		}
		
		// Get next data
		$next_data = modules_get_next_data ($id_agent_module, $date);
		if ($next_data !== false) {
			$next_data['utimestamp'] = $date;
			array_push ($interval_data, $next_data);
		}
		else if (count ($interval_data) > 0) {
			// Propagate the last known data to the end of the interval
			$next_data = array_pop ($interval_data);
			array_push ($interval_data, $next_data);
			$next_data['utimestamp'] = $date;
			array_push ($interval_data, $next_data);
		}
		
		$min_necessary = 2;
	}
	
	if (count ($interval_data) < $min_necessary) {
		return false;
	}
	
	// Set initial conditions
	$total = 0;
	$count = 0;
	if (! $uncompressed_module) {
		$previous_data = array_shift ($interval_data);
		
		// Do not count the empty start of an interval as 0
		if ($previous_data['utimestamp'] != $datelimit) {
			$period = $date - $previous_data['utimestamp'];
		}
	}
	foreach ($interval_data as $data) {
		if (! $uncompressed_module) {
			$total += $previous_data['datos'] * ($data['utimestamp'] - $previous_data['utimestamp']);
			$previous_data = $data;
		}
		else {
			$total += $data['datos'];
			$count++;
		}
	}
	
	// Compressed module data
	if (! $uncompressed_module) {
		if ($period == 0) {
			return 0;
		}
		
		return $total / $period;
	}
	
	// Uncompressed module data
	if ($count == 0) {
		return 0;
	}
	
	return $total / $count;	
}

/** 
 * Get the maximum value of an agent module in a period of time.
 * 
 * @param int Agent module id to get the maximum value.
 * @param int Period of time to check (in seconds)
 * @param int Top date to check the values. Default current time.
 * 
 * @return float The maximum module value in the interval.
 */
function reporting_get_agentmodule_data_max ($id_agent_module, $period=0, $date = 0) {
	global $config;
	
	// Initialize variables
	if (empty ($date)) $date = get_system_time ();
	$datelimit = $date - $period;

	$search_in_history_db = db_search_in_history_db($datelimit);

	$id_module_type = modules_get_agentmodule_type ($id_agent_module);
	$module_type = modules_get_moduletype_name ($id_module_type);
	$uncompressed_module = is_module_uncompressed ($module_type);
	
	// Get module data
	$interval_data = db_get_all_rows_sql ('SELECT *
		FROM tagente_datos 
		WHERE id_agente_modulo = ' . (int) $id_agent_module .
			' AND utimestamp > ' . (int) $datelimit .
			' AND utimestamp < ' . (int) $date .
		' ORDER BY utimestamp ASC', $search_in_history_db);
	if ($interval_data === false) $interval_data = array ();
	
	// Uncompressed module data
	if ($uncompressed_module) {
	
	// Compressed module data
	}
	else {
		// Get previous data
		$previous_data = modules_get_previous_data ($id_agent_module, $datelimit);
		if ($previous_data !== false) {
			$previous_data['utimestamp'] = $datelimit;
			array_unshift ($interval_data, $previous_data);
		}
		
		// Get next data
		$next_data = modules_get_next_data ($id_agent_module, $date);
		if ($next_data !== false) {
			$next_data['utimestamp'] = $date;
			array_push ($interval_data, $next_data);
		}
		else if (count ($interval_data) > 0) {
			// Propagate the last known data to the end of the interval
			$next_data = array_pop ($interval_data);
			array_push ($interval_data, $next_data);
			$next_data['utimestamp'] = $date;
			array_push ($interval_data, $next_data);
		}
	}
	
	// Set initial conditions
	if (empty($iterval_data)) {
		$max = 0;
	}
	else {
		if ($uncompressed_module || $interval_data[0]['utimestamp'] == $datelimit) {
			$max = $interval_data[0]['datos'];
		}
		else {
			$max = 0;
		}
	}
	
	foreach ($interval_data as $data) {
		if ($data['datos'] > $max) {
			$max = $data['datos'];
		}
	}
	
	return $max;
}

/** 
 * Get the minimum value of an agent module in a period of time.
 * 
 * @param int Agent module id to get the minimum value.
 * @param int Period of time to check (in seconds)
 * @param int Top date to check the values in Unix time. Default current time.
 * 
 * @return float The minimum module value of the module
 */
function reporting_get_agentmodule_data_min ($id_agent_module, $period=0, $date = 0) {
	global $config;
	
	// Initialize variables
	if (empty ($date)) $date = get_system_time ();
	$datelimit = $date - $period;

	$search_in_history_db = db_search_in_history_db($datelimit);
	
	$id_module_type = modules_get_agentmodule_type ($id_agent_module);
	$module_type = modules_get_moduletype_name ($id_module_type);
	$uncompressed_module = is_module_uncompressed ($module_type);
	
	// Get module data
	$interval_data = db_get_all_rows_sql ('SELECT *
		FROM tagente_datos 
		WHERE id_agente_modulo = ' . (int) $id_agent_module .
			' AND utimestamp > ' . (int) $datelimit .
			' AND utimestamp < ' . (int) $date .
		' ORDER BY utimestamp ASC', $search_in_history_db);
	if ($interval_data === false) $interval_data = array ();
	
	// Uncompressed module data
	if ($uncompressed_module) {
		$min_necessary = 1;
	
	// Compressed module data
	}
	else {
		// Get previous data
		$previous_data = modules_get_previous_data ($id_agent_module, $datelimit);
		if ($previous_data !== false) {
			$previous_data['utimestamp'] = $datelimit;
			array_unshift ($interval_data, $previous_data);
		}
	
		// Get next data
		$next_data = modules_get_next_data ($id_agent_module, $date);
		if ($next_data !== false) {
			$next_data['utimestamp'] = $date;
			array_push ($interval_data, $next_data);
		}
		else if (count ($interval_data) > 0) {
			// Propagate the last known data to the end of the interval
			$next_data = array_pop ($interval_data);
			array_push ($interval_data, $next_data);
			$next_data['utimestamp'] = $date;
			array_push ($interval_data, $next_data);
		}
	}
	
	if (count ($interval_data) < 1) {
		return false;
	}
	
	// Set initial conditions
	$min = $interval_data[0]['datos'];
	
	foreach ($interval_data as $data) {
		if ($data['datos'] < $min) {
			$min = $data['datos'];
		}
	}
	
	return $min;
}

/** 
 * Get the sum of values of an agent module in a period of time.
 * 
 * @param int Agent module id to get the sumatory.
 * @param int Period of time to check (in seconds)
 * @param int Top date to check the values. Default current time.
 * 
 * @return float The sumatory of the module values in the interval.
 */
function reporting_get_agentmodule_data_sum ($id_agent_module, $period=0, $date = 0) {
	global $config;
	// Initialize variables
	if (empty ($date)) $date = get_system_time ();
	$datelimit = $date - $period;

	$search_in_history_db = db_search_in_history_db($datelimit);
	
	$id_module_type = db_get_value ('id_tipo_modulo', 'tagente_modulo','id_agente_modulo', $id_agent_module);
	$module_name = db_get_value ('nombre', 'ttipo_modulo', 'id_tipo', $id_module_type);
	$module_interval = modules_get_interval ($id_agent_module);
	$uncompressed_module = is_module_uncompressed ($module_name);
	
	// Wrong module type
	if (is_module_data_string ($module_name)) {
		return 0;
	}
	
	// Incremental modules are treated differently
	$module_inc = is_module_inc ($module_name);
	
	// Get module data
	$interval_data = db_get_all_rows_sql ('SELECT * FROM tagente_datos 
			WHERE id_agente_modulo = ' . (int) $id_agent_module .
			' AND utimestamp > ' . (int) $datelimit .
			' AND utimestamp < ' . (int) $date .
			' ORDER BY utimestamp ASC', $search_in_history_db);
	if ($interval_data === false) $interval_data = array ();
	
	// Uncompressed module data
	if ($uncompressed_module) {
		$min_necessary = 1;
	
	// Compressed module data
	}
	else {
		// Get previous data
		$previous_data = modules_get_previous_data ($id_agent_module, $datelimit);
		if ($previous_data !== false) {
			$previous_data['utimestamp'] = $datelimit;
			array_unshift ($interval_data, $previous_data);
		}
		
		// Get next data
		$next_data = modules_get_next_data ($id_agent_module, $date);
		if ($next_data !== false) {
			$next_data['utimestamp'] = $date;
			array_push ($interval_data, $next_data);
		}
		else if (count ($interval_data) > 0) {
			// Propagate the last known data to the end of the interval
			$next_data = array_pop ($interval_data);
			array_push ($interval_data, $next_data);
			$next_data['utimestamp'] = $date;
			array_push ($interval_data, $next_data);
		}
		
		$min_necessary = 2;
	}
	
	if (count ($interval_data) < $min_necessary) {
		return false;
	}
	
	// Set initial conditions
	$total = 0;
	if (! $uncompressed_module) {
		$previous_data = array_shift ($interval_data);
	}
	
	foreach ($interval_data as $data) {
		if ($uncompressed_module) {
			$total += $data['datos'];
		}
		else if ($module_inc) {
			$total += $previous_data['datos'] * ($data['utimestamp'] - $previous_data['utimestamp']);
		}
		else {
			$total += $previous_data['datos'] * ($data['utimestamp'] - $previous_data['utimestamp']) / $module_interval;
		}
		$previous_data = $data;
	}
	
	return $total;
}

/** 
 * Get SLA of a module.
 * 
 * @param int Agent module to calculate SLA
 * @param int Period to check the SLA compliance.
 * @param int Minimum data value the module in the right interval
 * @param int Maximum data value the module in the right interval. False will
 * ignore max value
 * @param int Beginning date of the report in UNIX time (current date by default).
 * @param array $dayWeek  Array of days week to extract as array('monday' => false, 'tuesday' => true....), and by default is null.
 * @param string $timeFrom Time in the day to start to extract in mysql format, by default null.
 * @param string $timeTo Time in the day to end to extract in mysql format, by default null.
 * 
 * @return float SLA percentage of the requested module. False if no data were
 * found
 */
function reporting_get_agentmodule_sla ($id_agent_module, $period = 0, $min_value = 1, $max_value = false, $date = 0, $daysWeek = null, $timeFrom = null, $timeTo = null) {
	global $config;

	if (empty($id_agent_module))
		return false;
	
	// Initialize variables
	if (empty ($date)) {
		$date = get_system_time ();
	}
	if ($daysWeek === null) {
		$daysWeek = array();
	}
	// Limit date to start searching data
	$datelimit = $date - $period;

	$search_in_history_db = db_search_in_history_db($datelimit);
	
	// Get interval data
	$sql = sprintf ('SELECT *
		FROM tagente_datos
		WHERE id_agente_modulo = %d
			AND utimestamp > %d AND utimestamp <= %d',
		$id_agent_module, $datelimit, $date);
	
	//Add the working times (mon - tue - wed ...) and from time to time
	$days = array();
	//Translate to mysql week days
	if ($daysWeek) {
		foreach ($daysWeek as $key => $value) {
			if (!$value) {
				if ($key == 'monday') {
					$days[] = 2;
				}
				if ($key == 'tuesday') {
					$days[] = 3;
				}
				if ($key == 'wednesday') {
					$days[] = 4;
				}
				if ($key == 'thursday') {
					$days[] = 5;
				}
				if ($key == 'friday') {
					$days[] = 6;
				}
				if ($key == 'saturday') {
					$days[] = 7;
				}
				if ($key == 'sunday') {
					$days[] = 1;
				}
			}
		}
	}
	
	if (count($days) > 0) {
		$sql .= ' AND DAYOFWEEK(FROM_UNIXTIME(utimestamp)) NOT IN (' . implode(',', $days) . ')';
	}
	
	if ($timeFrom < $timeTo) {
		$sql .= ' AND (TIME(FROM_UNIXTIME(utimestamp)) >= "' . $timeFrom . '" AND TIME(FROM_UNIXTIME(utimestamp)) <= "'. $timeTo . '")';
	}
	elseif ($timeFrom > $timeTo) {
		$sql .= ' AND (TIME(FROM_UNIXTIME(utimestamp)) >= "' . $timeFrom . '" OR TIME(FROM_UNIXTIME(utimestamp)) <= "'. $timeTo . '")';
	}
	$sql .= ' ORDER BY utimestamp ASC';
	$interval_data = db_get_all_rows_sql ($sql, $search_in_history_db);
	
	if ($interval_data === false) {
		$interval_data = array ();
	}
	
	// Calculate planned downtime dates
	$downtime_dates = reporting_get_planned_downtimes_intervals($id_agent_module, $datelimit, $date);
	
	// Get previous data
	$previous_data = modules_get_previous_data ($id_agent_module, $datelimit);
	if ($previous_data !== false) {
		$previous_data['utimestamp'] = $datelimit;
		array_unshift ($interval_data, $previous_data);
	}
	
	// Get next data
	$next_data = modules_get_next_data ($id_agent_module, $date);
	if ($next_data !== false) {
		$next_data['utimestamp'] = $date;
		array_push ($interval_data, $next_data);
	}
	else if (count ($interval_data) > 0) {
		// Propagate the last known data to the end of the interval
		$next_data = array_pop ($interval_data);
		array_push ($interval_data, $next_data);
		$next_data['utimestamp'] = $date;
		array_push ($interval_data, $next_data);
	}
	
	if (count ($interval_data) < 2) {
		return false;
	}
	
	// Set initial conditions
	$bad_period = 0;
	$first_data = array_shift ($interval_data);
	
	// Do not count the empty start of an interval as 0
	if ($first_data['utimestamp'] != $datelimit) {
		$period = $date - $first_data['utimestamp'];
	}
	
	$previous_utimestamp = $first_data['utimestamp'];
	if ((($max_value > $min_value AND ($first_data['datos'] > $max_value OR $first_data['datos'] < $min_value))) OR
		($max_value <= $min_value AND $first_data['datos'] < $min_value)) {
		
		$previous_status = 1;
		foreach ($downtime_dates as $date_dt) {
			if (($date_dt['date_from'] <= $previous_utimestamp) AND ($date_dt['date_to'] >= $previous_utimestamp)) {
				$previous_status = 0;
			}
		}
	}
	else {
		$previous_status = 0;
	}
	
	foreach ($interval_data as $data) {
		// Previous status was critical
		if ($previous_status == 1) {
			$bad_period += $data['utimestamp'] - $previous_utimestamp;
		}
		
		if (array_key_exists('datos', $data)) {
			// Re-calculate previous status for the next data
			if ((($max_value > $min_value AND ($data['datos'] > $max_value OR $data['datos'] < $min_value))) OR
				($max_value <= $min_value AND $data['datos'] < $min_value)) {
				
				$previous_status = 1;
				foreach ($downtime_dates as $date_dt) {
					if (($date_dt['date_from'] <= $data['utimestamp']) AND ($date_dt['date_to'] >= $data['utimestamp'])) {
						$previous_status = 0;
					}
				}
			}
			else {
				$previous_status = 0;
			}
		}
		
		$previous_utimestamp = $data['utimestamp'];
	}
	
	// Return the percentage of SLA compliance
	return (float) (100 - ($bad_period / $period) * 100);
}
/** 
 * Get several SLA data for an agentmodule within a period divided on subperiods
 * 
 * @param int Agent module to calculate SLA
 * @param int Period to check the SLA compliance.
 * @param int Minimum data value the module in the right interval
 * @param int Maximum data value the module in the right interval. False will
 * ignore max value
 * @param array $days Array of days week to extract as array('monday' => false, 'tuesday' => true....), and by default is null.
 * @param string $timeFrom Time in the day to start to extract in mysql format, by default null.
 * @param string $timeTo Time in the day to end to extract in mysql format, by default null.
 * 
 * @return Array with values either 1, 2, 3 or 4 depending if the SLA percentage for this subperiod
 * is within the sla limits, on the edge, outside or with an unknown value.
 */
function reporting_get_agentmodule_sla_array ($id_agent_module, $period = 0, $min_value = 1, $max_value = false, $date = 0, $daysWeek = null, $timeFrom = null, $timeTo = null) {
	global $config;

	if (empty($id_agent_module))
		return false;
	
	// Initialize variables
	if (empty ($date)) {
		$date = get_system_time ();
	}
	if ($daysWeek === null) {
		$daysWeek = array();
	}

	// Hotfix: The edge values are confuse to the users
	$percent = 0;
	
	// Limit date to start searching data
	$datelimit = $date - $period;

	$search_in_history_db = db_search_in_history_db($datelimit);
	
	// Get interval data
	$sql = sprintf ('SELECT * FROM tagente_datos
		WHERE id_agente_modulo = %d
			AND utimestamp > %d AND utimestamp <= %d',
		$id_agent_module, $datelimit, $date);
	
	//Add the working times (mon - tue - wed ...) and from time to time
	$days = array();
	//Translate to mysql week days
	
	if ($daysWeek) {
		foreach ($daysWeek as $key => $value) {
			if (!$value) {
				if ($key == 'monday') {
					$days[] = 2;
				}
				if ($key == 'tuesday') {
					$days[] = 3;
				}
				if ($key == 'wednesday') {
					$days[] = 4;
				}
				if ($key == 'thursday') {
					$days[] = 5;
				}
				if ($key == 'friday') {
					$days[] = 6;
				}
				if ($key == 'saturday') {
					$days[] = 7;
				}
				if ($key == 'sunday') {
					$days[] = 1;
				}
			}
		}
	}
	
	if (count($days) > 0) {
		$sql .= ' AND DAYOFWEEK(FROM_UNIXTIME(utimestamp)) NOT IN (' . implode(',', $days) . ')';
	}
	
	if ($timeFrom != $timeTo) {
		if ($timeFrom < $timeTo) {
			$sql .= ' AND (TIME(FROM_UNIXTIME(utimestamp)) >= "' . $timeFrom . '" AND TIME(FROM_UNIXTIME(utimestamp)) <= "'. $timeTo . '")';
		}
		elseif ($timeFrom > $timeTo) {
			$sql .= ' AND (TIME(FROM_UNIXTIME(utimestamp)) >= "' . $timeFrom . '" OR TIME(FROM_UNIXTIME(utimestamp)) <= "'. $timeTo . '")';
		}
	}
	
	$sql .= ' ORDER BY utimestamp ASC';
	$interval_data = db_get_all_rows_sql ($sql, $search_in_history_db);
	
	if ($interval_data === false) {
		$interval_data = array ();
	}
	
	
	// Indexing data
	$interval_data_indexed = array();
	foreach($interval_data as $idata) {
		$interval_data_indexed[$idata['utimestamp']]['data'] = $idata['datos'];
	}
	
	//-----------Calculate unknown status events------------------------
	$events_unknown = db_get_all_rows_filter ('tevento',
		array ('id_agentmodule' => $id_agent_module,
			"utimestamp > $datelimit",
			"utimestamp < $date",
			'order' => 'utimestamp ASC'),
		array ('id_evento', 'evento', 'timestamp', 'utimestamp', 'event_type'));
	
	if ($events_unknown === false) {
		$events_unknown = array ();
	}
	
	// Add unknown periods to data
	for ($i = 0; isset($events_unknown[$i]); $i++) {
		$eu = $events_unknown[$i];

		if ($eu['event_type'] == 'going_unknown') {
			$interval_data_indexed[$eu['utimestamp']]['data'] = 0;
			$interval_data_indexed[$eu['utimestamp']]['status'] = 4;

			// Search the corresponding recovery event.
			for ($j = $i+1; isset($events_unknown[$j]); $j++) {
				$eu = $events_unknown[$j];

				if ($eu['event_type'] != 'going_unknown' && substr ($eu['event_type'], 0, 5) == 'going') {
					$interval_data_indexed[$eu['utimestamp']]['data'] = 0;
					$interval_data_indexed[$eu['utimestamp']]['status'] = 6;
					
					// Do not process read events again.
					$i = $j;
					break;
				}
			}
		}
	}
	
	// Get the last event before inverval to know if graph start on unknown
	$prev_event = db_get_row_filter ('tevento',
		array ('id_agentmodule' => $id_agent_module,
			"utimestamp <= $datelimit",
			'order' => 'utimestamp DESC'));
	if (isset($prev_event['event_type']) && $prev_event['event_type'] == 'going_unknown') {
		$start_unknown = true;
	}
	else {
		$start_unknown = false;
	}
	//------------------------------------------------------------------
	
	//-----------------Set limits of the interval-----------------------
	// Get previous data (This adds the first data if the begin of module data is after the begin time interval)
	$previous_data = modules_get_previous_data ($id_agent_module, $datelimit);
	if ($previous_data !== false ) {
		$previous_value = $previous_data['datos'];
		// if ((($previous_value > ($min_value - $percent)) && ($previous_value < ($min_value + $percent))) || 
		// 		(($previous_value > ($max_value - $percent)) && ($previous_value < ($max_value + $percent)))) {//2 when value is within the edges
		// 	$previous_known_status = 2;
		// }
		// else
		if (($previous_value >= ($min_value + $percent)) && ($previous_value <= ($max_value - $percent))) { //1 when value is OK
			$previous_known_status = 1;
		}
		elseif (($previous_value <= ($min_value - $percent)) || ($previous_value >= ($max_value + $percent))) { //3 when value is Wrong
			$previous_known_status = 3;
		}
	}

	// If the starting of the graph is unknown we set it
	if ($start_unknown) {
		$interval_data_indexed[$datelimit]['data'] = 0;
		$interval_data_indexed[$datelimit]['status'] = 4;
	}
	else {
		if ($previous_data !== false ) {
			$interval_data_indexed[$datelimit]['data'] = $previous_data['datos'];
		}
		else { // If there are not data befor interval set unknown
			$interval_data_indexed[$datelimit]['data'] = 0;
			$interval_data_indexed[$datelimit]['status'] = 4;
			$previous_known_status = 1; // Assume the module was in normal status if there is no previous data.
		}
	}
	
	// Get next data (This adds data before the interval of the report)
	$next_data = modules_get_next_data ($id_agent_module, $date);
	
	if ($next_data !== false) {
		$interval_data_indexed[$date]['data'] = $previous_data['datos'];
	}
	else if (count ($interval_data_indexed) > 0) {
		// Propagate the last known data to the end of the interval (if there is no module data at the end point)
		ksort($interval_data_indexed);
		$last_data = end($interval_data_indexed);
		$interval_data_indexed[$date] = $last_data;
	}
	
	//------------------------------------------------------------------
	
	//--------Calculate planned downtime dates--------------------------
	$downtime_dates = reporting_get_planned_downtimes_intervals($id_agent_module, $datelimit, $date);

	foreach ($downtime_dates as $downtime_date) {
		// Delete data of the planned downtime and put the last data on the upper limit
		$interval_data_indexed[$downtime_date['date_from']]['data'] = 0;
		$interval_data_indexed[$downtime_date['date_from']]['status'] = 5;
		$interval_data_indexed[$downtime_date['date_to']]['data'] = 0;
		$interval_data_indexed[$downtime_date['date_to']]['status'] = 4;
		
		$last_downtime_data = false;
		foreach ($interval_data_indexed as $idi_timestamp => $idi) {
			if ($idi_timestamp != $downtime_date['date_from'] && $idi_timestamp != $downtime_date['date_to'] && 
					$idi_timestamp >= $downtime_date['date_from'] && $idi_timestamp <= $downtime_date['date_to']) {
				$last_downtime_data = $idi['data'];
				unset($interval_data_indexed[$idi_timestamp]);
			}
		}
		
		// Set the last data of the interval as limit
		if ($last_downtime_data !== false) {
			$interval_data_indexed[$downtime_date['date_to']]['data'] = $last_downtime_data;
		}
	}
	//------------------------------------------------------------------
	
	// Sort the array
	ksort($interval_data_indexed);
	
	// We need more or equal two points
	if (count ($interval_data_indexed) < 2) {
		return false;
	}
	
	//Get the percentage for the limits
	$diff = $max_value - $min_value; 
	
	// Get module type
	$id_module_type = db_get_value('id_tipo_modulo', 'tagente_modulo', 'id_agente_modulo', $id_agent_module);
	// If module is boolean don't create translation intervals (on the edge intervals)
	// if ($id_module_type == 2 or $id_module_type == 6 or $id_module_type == 9 or $id_module_type == 18){
	//      $percent = 0;
	// }
	// else {
	//      // Getting 10% of $diff --> $percent = ($diff/100)*10, so...
	//      $percent = $diff / 10;
	// }
	
	//Set initial conditions
	$first_data = array_shift ($interval_data);
	$previous_utimestamp = $date - $period;
	
	$previous_value = $first_data ['datos'];
	$previous_status = 0;
	
	if (isset($first_data['status'])) {
		// 4 for the Unknown value and 5 for planned downtime
		$previous_status = $first_data['status'];
	}
	// elseif ((($previous_value > ($min_value - $percent)) && ($previous_value < ($min_value + $percent))) || 
	// 		(($previous_value > ($max_value - $percent)) && ($previous_value < ($max_value + $percent)))) {//2 when value is within the edges
	// 	$previous_status = 2;
	// }
	elseif (($previous_value >= ($min_value + $percent)) && ($previous_value <= ($max_value - $percent))) { //1 when value is OK
		$previous_status = 1;
	}
	elseif (($previous_value <= ($min_value - $percent)) || ($previous_value >= ($max_value + $percent))) { //3 when value is Wrong
		$previous_status = 3;
	}
	
	$data_colors = array();
	$i = 0;
	
	foreach ($interval_data_indexed as $utimestamp => $data) {
		$change = false;
		$value = $data['data'];
		if (isset($data['status'])) {
			// Leaving unkown status.
			if ($data['status'] == 6) {
				$status = $previous_known_status;
			}
			// 4 unknown, 5 planned downtime.
			else {
				$status = $data['status'];
			}
		}
		// elseif ((($value > ($min_value - $percent)) && ($value < ($min_value + $percent))) || 
		// 		(($value > ($max_value - $percent)) && ($value < ($max_value + $percent)))) { //2 when value is within the edges
		// 	$status = 2;
		// }
		elseif (($value >= ($min_value + $percent)) && ($value <= ($max_value - $percent))) { //1 when value is OK
			$status = 1;
		}
		elseif (($value <= ($min_value - $percent)) || ($value >= ($max_value + $percent))) { //3 when value is Wrong
			$status = 3;
		}
		
		if ($status != $previous_status) {
			$change = true;
			$data_colors[$i]['data'] = $previous_status;
			$data_colors[$i]['utimestamp'] = $utimestamp - $previous_utimestamp;
			$i++;
			$previous_status = $status;
			$previous_utimestamp = $utimestamp;
		}
		
		// Save the last known status.
		if ($status <= 3) {
			$previous_known_status = $status;
		}
	}
	if ($change == false) {
		$data_colors[$i]['data'] = $previous_status;
		$data_colors[$i]['utimestamp'] = $date - $previous_utimestamp;
	}
	
	return $data_colors;
}

/** 
 * Get the time intervals where an agentmodule is affected by the planned downtimes.
 * 
 * @param int Agent module to calculate planned downtimes intervals.
 * @param int Start date in utimestamp.
 * @param int End date in utimestamp.
 * @param bool Whether ot not to get the planned downtimes that affect the service associated with the agentmodule.
 * 
 * @return Array with time intervals.
 */
function reporting_get_planned_downtimes_intervals ($id_agent_module, $start_date, $end_date, $check_services = false) {
	global $config;

	if (empty($id_agent_module))
		return false;

	require_once ($config['homedir'] . '/include/functions_planned_downtimes.php');

	$malformed_planned_downtimes = planned_downtimes_get_malformed();
	if (empty($malformed_planned_downtimes))
		$malformed_planned_downtimes = array();

	$sql_downtime = "SELECT DISTINCT(tpdr.id), tpdr.*
					FROM (
							SELECT tpd.*
							FROM tplanned_downtime tpd, tplanned_downtime_agents tpda, tagente_modulo tam
							WHERE tpd.id = tpda.id_downtime
								AND tpda.all_modules = 1
								AND tpda.id_agent = tam.id_agente
								AND tam.id_agente_modulo = $id_agent_module
						UNION ALL
							SELECT tpd.*
							FROM tplanned_downtime tpd, tplanned_downtime_modules tpdm
							WHERE tpd.id = tpdm.id_downtime
								AND tpdm.id_agent_module = $id_agent_module
					) tpdr
					ORDER BY tpdr.id";

	$downtimes = db_get_all_rows_sql($sql_downtime);

	if ($downtimes == false) {
		$downtimes = array();
	}
	$downtime_dates = array();
	foreach ($downtimes as $downtime) {
		$downtime_id = $downtime['id'];
		$downtime_type = $downtime['type_execution'];
		$downtime_periodicity = $downtime['type_periodicity'];
		
		if ($downtime_type == 'once') {
			$dates = array();
			$dates['date_from'] = $downtime['date_from'];
			$dates['date_to'] = $downtime['date_to'];
			$downtime_dates[] = $dates;
		}
		else if ($downtime_type == 'periodically') {

			// If a planned downtime have malformed dates, its intervals aren't taken account
			$downtime_malformed = false;
			foreach ($malformed_planned_downtimes as $malformed_planned_downtime) {
				if ($downtime_id == $malformed_planned_downtime['id']) {
					$downtime_malformed = true;
					break;
				}
			}
			if ($downtime_malformed == true) {
				continue;
			}
			// If a planned downtime have malformed dates, its intervals aren't taken account

			$downtime_time_from = $downtime['periodically_time_from'];
			$downtime_time_to = $downtime['periodically_time_to'];

			$downtime_hour_from = date("H", strtotime($downtime_time_from));
			$downtime_minute_from = date("i", strtotime($downtime_time_from));
			$downtime_second_from = date("s", strtotime($downtime_time_from));
			$downtime_hour_to = date("H", strtotime($downtime_time_to));
			$downtime_minute_to = date("i", strtotime($downtime_time_to));
			$downtime_second_to = date("s", strtotime($downtime_time_to));

			if ($downtime_periodicity == "monthly") {
				$downtime_day_from = $downtime['periodically_day_from'];
				$downtime_day_to = $downtime['periodically_day_to'];

				$date_aux = strtotime(date("Y-m-01", $start_date));
				$year_aux = date("Y", $date_aux);
				$month_aux = date("m", $date_aux);

				$end_year = date("Y", $end_date);
				$end_month = date("m", $end_date);

				while ($year_aux < $end_year || ($year_aux == $end_year && $month_aux <= $end_month)) {
					
					if ($downtime_day_from > $downtime_day_to) {
						$dates = array();
						$dates['date_from'] = strtotime("$year_aux-$month_aux-$downtime_day_from $downtime_hour_from:$downtime_minute_from:$downtime_second_from");
						$dates['date_to'] = strtotime(date("Y-m-t H:i:s", strtotime("$year_aux-$month_aux-28 23:59:59")));
						$downtime_dates[] = $dates;

						$dates = array();
						if ($month_aux + 1 <= 12) {
							$dates['date_from'] = strtotime("$year_aux-".($month_aux + 1)."-01 00:00:00");
							$dates['date_to'] = strtotime("$year_aux-".($month_aux + 1)."-$downtime_day_to $downtime_hour_to:$downtime_minute_to:$downtime_second_to");
						}
						else {
							$dates['date_from'] = strtotime(($year_aux + 1)."-01-01 00:00:00");
							$dates['date_to'] = strtotime(($year_aux + 1)."-01-$downtime_day_to $downtime_hour_to:$downtime_minute_to:$downtime_second_to");
						}
						$downtime_dates[] = $dates;
					}
					else {
						if ($downtime_day_from == $downtime_day_to && strtotime($downtime_time_from) > strtotime($downtime_time_to)) {
							$date_aux_from = strtotime("$year_aux-$month_aux-$downtime_day_from $downtime_hour_from:$downtime_minute_from:$downtime_second_from");
							$max_day_num = date('t', $date_aux);

							$dates = array();
							$dates['date_from'] = strtotime("$year_aux-$month_aux-$downtime_day_from $downtime_hour_from:$downtime_minute_from:$downtime_second_from");
							$dates['date_to'] = strtotime("$year_aux-$month_aux-$downtime_day_from 23:59:59");
							$downtime_dates[] = $dates;

							if ($downtime_day_to + 1 > $max_day_num) {

								$dates = array();
								if ($month_aux + 1 <= 12) {
									$dates['date_from'] = strtotime("$year_aux-".($month_aux + 1)."-01 00:00:00");
									$dates['date_to'] = strtotime("$year_aux-".($month_aux + 1)."-01 $downtime_hour_to:$downtime_minute_to:$downtime_second_to");
								}
								else {
									$dates['date_from'] = strtotime(($year_aux + 1)."-01-01 00:00:00");
									$dates['date_to'] = strtotime(($year_aux + 1)."-01-01 $downtime_hour_to:$downtime_minute_to:$downtime_second_to");
								}
								$downtime_dates[] = $dates;
							}
							else {
								$dates = array();
								$dates['date_from'] = strtotime("$year_aux-$month_aux-".($downtime_day_to + 1)." 00:00:00");
								$dates['date_to'] = strtotime("$year_aux-$month_aux-".($downtime_day_to + 1)." $downtime_hour_to:$downtime_minute_to:$downtime_second_to");
								$downtime_dates[] = $dates;
							}
						}
						else {
							$dates = array();
							$dates['date_from'] = strtotime("$year_aux-$month_aux-$downtime_day_from $downtime_hour_from:$downtime_minute_from:$downtime_second_from");
							$dates['date_to'] = strtotime("$year_aux-$month_aux-$downtime_day_to $downtime_hour_to:$downtime_minute_to:$downtime_second_to");
							$downtime_dates[] = $dates;
						}
					}

					$month_aux++;
					if ($month_aux > 12) {
						$month_aux = 1;
						$year_aux++;
					}
				}
			}
			else if ($downtime_periodicity == "weekly") {
				$date_aux = $start_date;
				$active_days = array();
				$active_days[0] = ($downtime['sunday'] == 1) ? true : false;
				$active_days[1] = ($downtime['monday'] == 1) ? true : false;
				$active_days[2] = ($downtime['tuesday'] == 1) ? true : false;
				$active_days[3] = ($downtime['wednesday'] == 1) ? true : false;
				$active_days[4] = ($downtime['thursday'] == 1) ? true : false;
				$active_days[5] = ($downtime['friday'] == 1) ? true : false;
				$active_days[6] = ($downtime['saturday'] == 1) ? true : false;

				while ($date_aux <= $end_date) {
					$weekday_num = date('w', $date_aux);
					
					if ($active_days[$weekday_num]) {
						$day_num = date('d', $date_aux);
						$month_num = date('m', $date_aux);
						$year_num = date('Y', $date_aux);

						$max_day_num = date('t', $date_aux);

						if (strtotime($downtime_time_from) > strtotime($downtime_time_to)) {
							$dates = array();
							$dates['date_from'] = strtotime("$year_num-$month_num-$day_num $downtime_hour_from:$downtime_minute_from:$downtime_second_from");
							$dates['date_to'] = strtotime("$year_num-$month_num-$day_num 23:59:59");
							$downtime_dates[] = $dates;

							$dates = array();
							if ($day_num + 1 > $max_day_num) {
								if ($month_num + 1 > 12) {
									$dates['date_from'] = strtotime(($year_num + 1)."-01-01 00:00:00");
									$dates['date_to'] = strtotime(($year_num + 1)."-01-01 $downtime_hour_to:$downtime_minute_to:$downtime_second_to");
								}
								else {
									$dates['date_from'] = strtotime("$year_num-".($month_num + 1)."-01 00:00:00");
									$dates['date_to'] = strtotime("$year_num-".($month_num + 1)."-01 $downtime_hour_to:$downtime_minute_to:$downtime_second_to");
								}
							}
							else {
								$dates['date_from'] = strtotime("$year_num-$month_num-".($day_num + 1)." 00:00:00");
								$dates['date_to'] = strtotime("$year_num-$month_num-".($day_num + 1)." $downtime_hour_to:$downtime_minute_to:$downtime_second_to");
							}
							$downtime_dates[] = $dates;
						}
						else {
							$dates = array();
							$dates['date_from'] = strtotime("$year_num-$month_num-$day_num $downtime_hour_from:$downtime_minute_from:$downtime_second_from");
							$dates['date_to'] = strtotime("$year_num-$month_num-$day_num $downtime_hour_to:$downtime_minute_to:$downtime_second_to");
							$downtime_dates[] = $dates;
						}
					}

					$date_aux += SECONDS_1DAY;
				}
			}
		}
	}

	if ($check_services) {
		enterprise_include_once("include/functions_services.php");
		if (function_exists("services_get_planned_downtimes_intervals")) {
			services_get_planned_downtimes_intervals($downtime_dates, $start_date, $end_date, false, $id_agent_module);
		}
	}

	return $downtime_dates;
}

/** 
 * Get the planned downtimes that affect the passed modules on an specific datetime range.
 * 
 * @param int Start date in utimestamp.
 * @param int End date in utimestamp.
 * @param array The agent modules ids.
 * 
 * @return Array with the planned downtimes that are executed in any moment of the range selected and affect the
 * agent modules selected.
 */
function reporting_get_planned_downtimes ($start_date, $end_date, $id_agent_modules = false) {
	$start_time = date("H:i:s", $start_date);
	$end_time = date("H:i:s", $end_date);

	$start_day = date("d", $start_date);
	$end_day = date("d", $end_date);

	$start_month = date("m", $start_date);
	$end_month = date("m", $end_date);

	if ($start_date > $end_date) {
		return false;
	}

	if ($end_date - $start_date >= SECONDS_1MONTH) {
		// If the date range is larger than 1 month, every monthly planned downtime will be inside
		$periodically_monthly_w = "type_periodicity = 'monthly'";
	}
	else {
		// Check if the range is larger than the planned downtime execution, or if its start or end
		// is inside the planned downtime execution.
		// The start and end time is very important.
		$periodically_monthly_w = "type_periodicity = 'monthly'
									AND (((periodically_day_from > '$start_day'
												OR (periodically_day_from = '$start_day'
													AND periodically_time_from >= '$start_time'))
											AND (periodically_day_to < '$end_day'
												OR (periodically_day_to = '$end_day'
													AND periodically_time_to <= '$end_time')))
										OR ((periodically_day_from < '$start_day' 
												OR (periodically_day_from = '$start_day'
													AND periodically_time_from <= '$start_time'))
											AND (periodically_day_to > '$start_day'
												OR (periodically_day_to = '$start_day'
													AND periodically_time_to >= '$start_time')))
										OR ((periodically_day_from < '$end_day' 
												OR (periodically_day_from = '$end_day'
													AND periodically_time_from <= '$end_time'))
											AND (periodically_day_to > '$end_day'
												OR (periodically_day_to = '$end_day'
													AND periodically_time_to >= '$end_time'))))";
	}

	$periodically_weekly_days = array();
	$date_aux = $start_date;
	$i = 0;

	if (($end_date - $start_date) >= SECONDS_1WEEK) {
		// If the date range is larger than 7 days, every weekly planned downtime will be inside.
		for ($i = 0; $i < 7; $i++) {
			$weekday_actual = strtolower(date('l', $date_aux));
			$periodically_weekly_days[] = "($weekday_actual = 1)";
			$date_aux += SECONDS_1DAY;
		}
	}
	else if (($end_date - $start_date) <= SECONDS_1DAY && $start_day == $end_day) {
		// If the date range is smaller than 1 day, the start and end days can be equal or consecutive.
		// If they are equal, the execution times have to be contained in the date range times or contain
		// the start or end time of the date range.
		$weekday_actual = strtolower(date('l', $start_date));
		$periodically_weekly_days[] = "($weekday_actual = 1
			AND ((periodically_time_from > '$start_time' AND periodically_time_to < '$end_time')
				OR (periodically_time_from = '$start_time'
					OR (periodically_time_from < '$start_time'
						AND periodically_time_to >= '$start_time'))
				OR (periodically_time_from = '$end_time'
					OR (periodically_time_from < '$end_time'
						AND periodically_time_to >= '$end_time'))))";
	}
	else {
		while ($date_aux <= $end_date && $i < 7) {

			$weekday_actual = strtolower(date('l', $date_aux));
			$day_num_actual = date('d', $date_aux);

			if ($date_aux == $start_date) {
				$periodically_weekly_days[] = "($weekday_actual = 1 AND periodically_time_to >= '$start_time')";
			}
			else if ($day_num_actual == $end_day) {
				$periodically_weekly_days[] = "($weekday_actual = 1 AND periodically_time_from <= '$end_time')";
			}
			else {
				$periodically_weekly_days[] = "($weekday_actual = 1)";
			}
			
			$date_aux += SECONDS_1DAY;
			$i++;
		}
	}

	if (!empty($periodically_weekly_days)) {
		$periodically_weekly_w = "type_periodicity = 'weekly' AND (".implode(" OR ", $periodically_weekly_days).")";
		$periodically_condition = "(($periodically_monthly_w) OR ($periodically_weekly_w))";
	}
	else {
		$periodically_condition = "($periodically_monthly_w)";
	}

	if ($id_agent_modules !== false) {
		if (empty($id_agent_modules))
			return array();

		$id_agent_modules_str = implode(",", $id_agent_modules);

		$sql_downtime = "SELECT DISTINCT(tpdr.id), tpdr.*
						FROM (
								SELECT tpd.*
								FROM tplanned_downtime tpd, tplanned_downtime_agents tpda, tagente_modulo tam
								WHERE (tpd.id = tpda.id_downtime
										AND tpda.all_modules = 1
										AND tpda.id_agent = tam.id_agente
										AND tam.id_agente_modulo IN ($id_agent_modules_str))
									AND ((type_execution = 'periodically'
											AND $periodically_condition)
										OR (type_execution = 'once'
											AND ((date_from >= '$start_date' AND date_to <= '$end_date')
												OR (date_from <= '$start_date' AND date_to >= '$end_date')
												OR (date_from <= '$start_date' AND date_to >= '$start_date')
												OR (date_from <= '$end_date' AND date_to >= '$end_date'))))
							UNION ALL
								SELECT tpd.*
								FROM tplanned_downtime tpd, tplanned_downtime_modules tpdm
								WHERE (tpd.id = tpdm.id_downtime
										AND tpdm.id_agent_module IN ($id_agent_modules_str))
									AND ((type_execution = 'periodically'
											AND $periodically_condition)
										OR (type_execution = 'once'
											AND ((date_from >= '$start_date' AND date_to <= '$end_date')
												OR (date_from <= '$start_date' AND date_to >= '$end_date')
												OR (date_from <= '$start_date' AND date_to >= '$start_date')
												OR (date_from <= '$end_date' AND date_to >= '$end_date'))))
						) tpdr
						ORDER BY tpdr.id";
	}
	else {
		$sql_downtime = "SELECT *
						FROM tplanned_downtime tpd, tplanned_downtime_modules tpdm
						WHERE (type_execution = 'periodically'
									AND $periodically_condition)
								OR (type_execution = 'once'
									AND ((date_from >= '$start_date' AND date_to <= '$end_date')
										OR (date_from <= '$start_date' AND date_to >= '$end_date')
										OR (date_from <= '$start_date' AND date_to >= '$start_date')
										OR (date_from <= '$end_date' AND date_to >= '$end_date')))";
	}

	$downtimes = db_get_all_rows_sql($sql_downtime);
	if ($downtimes == false) {
		$downtimes = array();
	}

	return $downtimes;
}

function reporting_get_stats_servers($tiny = true) {
	global $config;
	
	$server_performance = servers_get_performance();
	
	// Alerts table
	$table_srv = html_get_predefined_table();
	
	$table_srv->style[0] = $table_srv->style[2] = 'text-align: right; padding: 5px;';
	$table_srv->style[1] = $table_srv->style[3] = 'text-align: left; padding: 5px;';
	
	$tdata = array();
	$tdata[0] = html_print_image('images/module.png', true, array('title' => __('Total running modules'), 'width' => '25px'));
	$tdata[1] = '<span class="big_data">' . format_numeric($server_performance ["total_modules"]) . '</span>';
	
	$tdata[2] = '<span class="med_data">' . format_numeric($server_performance ["total_modules_rate"], 2) . '</span>';
	$tdata[3] = html_print_image('images/module.png', true, array('title' => __('Ratio') . ': ' . __('Modules by second'), 'width' => '16px')) . '/sec </span>';
	
	$table_srv->rowclass[] = '';
	$table_srv->data[] = $tdata;
	
	$tdata = array();
	$tdata[0] = '<hr style="border: 0; height: 1px; background: #DDD">';
	$table_srv->colspan[count($table_srv->data)][0] = 4;
	$table_srv->rowclass[] = '';
	$table_srv->data[] = $tdata;
	
	$tdata = array();
	$tdata[0] = html_print_image('images/database.png', true, array('title' => __('Local modules'), 'width' => '25px'));
	$tdata[1] = '<span class="big_data">' . format_numeric($server_performance ["total_local_modules"]) . '</span>';
	
	$tdata[2] = '<span class="med_data">' .
		format_numeric($server_performance ["local_modules_rate"], 2) . '</span>';
	$tdata[3] = html_print_image('images/module.png', true, array('title' => __('Ratio') . ': ' . __('Modules by second'), 'width' => '16px')) . '/sec </span>';
	
	$table_srv->rowclass[] = '';
	$table_srv->data[] = $tdata;
	
	if ($tiny) {
		$tdata = array();
		$tdata[0] = html_print_image('images/network.png', true, array('title' => __('Remote modules'), 'width' => '25px'));
		$tdata[1] = '<span class="big_data">' . format_numeric($server_performance ["total_remote_modules"]) . '</span>';
		
		$tdata[2] = '<span class="med_data">' . format_numeric($server_performance ["remote_modules_rate"], 2) . '</span>';
		$tdata[3] = html_print_image('images/module.png', true, array('title' => __('Ratio') . ': ' . __('Modules by second'), 'width' => '16px')) . '/sec </span>';
		
		$table_srv->rowclass[] = '';
		$table_srv->data[] = $tdata;
	}
	else {
		if (isset($server_performance ["total_network_modules"])) {
			$tdata = array();
			$tdata[0] = html_print_image('images/network.png', true, array('title' => __('Network modules'), 'width' => '25px'));
			$tdata[1] = '<span class="big_data">' . format_numeric($server_performance ["total_network_modules"]) . '</span>';
			
			$tdata[2] = '<span class="med_data">' .
				format_numeric($server_performance["network_modules_rate"], 2) .
				'</span>';
			$tdata[3] = html_print_image('images/module.png', true, array('title' => __('Ratio') . ': ' . __('Modules by second'), 'width' => '16px')) . '/sec </span>';
			
			$table_srv->rowclass[] = '';
			$table_srv->data[] = $tdata;
		}
		
		if (isset($server_performance ["total_plugin_modules"])) {
			$tdata = array();
			$tdata[0] = html_print_image('images/plugin.png', true, array('title' => __('Plugin modules'), 'width' => '25px'));
			$tdata[1] = '<span class="big_data">' . format_numeric($server_performance ["total_plugin_modules"]) . '</span>';
			
			$tdata[2] = '<span class="med_data">' . format_numeric($server_performance ["plugin_modules_rate"], 2) . '</span>';
			$tdata[3] = html_print_image('images/module.png', true, array('title' => __('Ratio') . ': ' . __('Modules by second'), 'width' => '16px')) . '/sec </span>';
			
			$table_srv->rowclass[] = '';
			$table_srv->data[] = $tdata;
		}
		
		if (isset($server_performance ["total_prediction_modules"])) {
			$tdata = array();
			$tdata[0] = html_print_image('images/chart_bar.png', true, array('title' => __('Prediction modules'), 'width' => '25px'));
			$tdata[1] = '<span class="big_data">' . format_numeric($server_performance ["total_prediction_modules"]) . '</span>';
			
			$tdata[2] = '<span class="med_data">' . format_numeric($server_performance ["prediction_modules_rate"], 2) . '</span>';
			$tdata[3] = html_print_image('images/module.png', true, array('title' => __('Ratio') . ': ' . __('Modules by second'), 'width' => '16px')) . '/sec </span>';
			
			$table_srv->rowclass[] = '';
			$table_srv->data[] = $tdata;
		}
		
		if (isset($server_performance ["total_wmi_modules"])) {
			$tdata = array();
			$tdata[0] = html_print_image('images/wmi.png', true, array('title' => __('WMI modules'), 'width' => '25px'));
			$tdata[1] = '<span class="big_data">' . format_numeric($server_performance ["total_wmi_modules"]) . '</span>';
			
			$tdata[2] = '<span class="med_data">' . format_numeric($server_performance ["wmi_modules_rate"], 2) . '</span>';
			$tdata[3] = html_print_image('images/module.png', true, array('title' => __('Ratio') . ': ' . __('Modules by second'), 'width' => '16px')) . '/sec </span>';
			
			$table_srv->rowclass[] = '';
			$table_srv->data[] = $tdata;
		}
		
		if (isset($server_performance ["total_web_modules"])) {
			$tdata = array();
			$tdata[0] = html_print_image('images/world.png', true, array('title' => __('Web modules'), 'width' => '25px'));
			$tdata[1] = '<span class="big_data">' .
				format_numeric($server_performance ["total_web_modules"]) .
				'</span>';
			
			$tdata[2] = '<span class="med_data">' .
				format_numeric($server_performance ["web_modules_rate"], 2) .
				'</span>';
			$tdata[3] = html_print_image('images/module.png', true, array('title' => __('Ratio') . ': ' . __('Modules by second'), 'width' => '16px')) . '/sec </span>';
			
			$table_srv->rowclass[] = '';
			$table_srv->data[] = $tdata;
		}
		
		$tdata = array();
		$tdata[0] = '<hr style="border: 0; height: 1px; background: #DDD">';
		$table_srv->colspan[count($table_srv->data)][0] = 4;
		$table_srv->rowclass[] = '';
		$table_srv->data[] = $tdata;
		
		
		switch ($config["dbtype"]) {
			case "mysql":
				$system_events = db_get_value_sql(
					'SELECT SQL_NO_CACHE COUNT(id_evento)
					FROM tevento');
				break;
			case "postgresql":
			case "oracle":
				$system_events = db_get_value_sql(
					'SELECT COUNT(id_evento)
					FROM tevento');
				break;
		}
		
		
		
		$tdata = array();
		$tdata[0] = html_print_image('images/lightning_go.png', true,
			array('title' => __('Total events'), 'width' => '25px'));
		$tdata[1] = '<span class="big_data">' .
			format_numeric($system_events) . '</span>';
		
		$table_srv->colspan[count($table_srv->data)][1] = 3;
		$table_srv->rowclass[] = '';
		$table_srv->data[] = $tdata;
	}
	
	$output = '<fieldset class="databox tactical_set">
				<legend>' . 
					__('Server performance') . 
				'</legend>' . 
				html_print_table($table_srv, true) . '</fieldset>';
	
	return $output;
}

function reporting_get_stats_modules_status($data, $graph_width = 250, $graph_height = 150, $links = false, $data_agents=false) {
	global $config;
	
	// Link URLS
	if ($links === false) {
		$urls = array();
		$urls['monitor_critical'] = "index.php?" .
			"sec=estado&amp;sec2=operation/agentes/status_monitor&amp;" .
			"refr=60&amp;status=" . AGENT_MODULE_STATUS_CRITICAL_BAD . "&pure=" . $config['pure'];
		$urls['monitor_warning'] = "index.php?" .
			"sec=estado&amp;sec2=operation/agentes/status_monitor&amp;" .
			"refr=60&amp;status=" . AGENT_MODULE_STATUS_WARNING . "&pure=" . $config['pure'];
		$urls['monitor_ok'] = "index.php?" .
			"sec=estado&amp;sec2=operation/agentes/status_monitor&amp;" .
			"refr=60&amp;status=" . AGENT_MODULE_STATUS_NORMAL . "&pure=" . $config['pure'];
		$urls['monitor_unknown'] = "index.php?" .
			"sec=estado&amp;sec2=operation/agentes/status_monitor&amp;" .
			"refr=60&amp;status=" . AGENT_MODULE_STATUS_UNKNOWN . "&pure=" . $config['pure'];
		$urls['monitor_not_init'] = "index.php?" .
			"sec=estado&amp;sec2=operation/agentes/status_monitor&amp;" .
			"refr=60&amp;status=" . AGENT_MODULE_STATUS_NOT_INIT . "&pure=" . $config['pure'];
	}
	else {
		$urls = array();
		$urls['monitor_critical'] = $links['monitor_critical'];
		$urls['monitor_warning'] = $links['monitor_warning'];
		$urls['monitor_ok'] = $links['monitor_ok'];
		$urls['monitor_unknown'] = $links['monitor_unknown'];
		$urls['monitor_not_init'] = $links['monitor_not_init'];
	}
	
	// Modules by status table
	$table_mbs = html_get_predefined_table();
	
	$tdata = array();
	$tdata[0] = html_print_image('images/module_critical.png', true, array('title' => __('Monitor critical')));
	$tdata[1] = $data["monitor_critical"] <= 0 ? '-' : $data["monitor_critical"];
	$tdata[1] = '<a style="color: ' . COL_CRITICAL . ';" class="big_data" href="' . $urls['monitor_critical'] . '">' . $tdata[1] . '</a>';
	
	$tdata[2] = html_print_image('images/module_warning.png', true, array('title' => __('Monitor warning')));
	$tdata[3] = $data["monitor_warning"] <= 0 ? '-' : $data["monitor_warning"];
	$tdata[3] = '<a style="color: ' . COL_WARNING_DARK . ';" class="big_data" href="' . $urls['monitor_warning'] . '">' . $tdata[3] . '</a>';
	$table_mbs->rowclass[] = '';
	$table_mbs->data[] = $tdata;
	
	$tdata = array();
	$tdata[0] = html_print_image('images/module_ok.png', true, array('title' => __('Monitor normal')));
	$tdata[1] = $data["monitor_ok"] <= 0 ? '-' : $data["monitor_ok"];
	$tdata[1] = '<a style="color: ' . COL_NORMAL . ';" class="big_data" href="' . $urls["monitor_ok"] . '">' . $tdata[1] . '</a>';
	
	$tdata[2] = html_print_image('images/module_unknown.png', true, array('title' => __('Monitor unknown')));
	$tdata[3] = $data["monitor_unknown"] <= 0 ? '-' : $data["monitor_unknown"];
	$tdata[3] = '<a style="color: ' . COL_UNKNOWN . ';" class="big_data" href="' . $urls["monitor_unknown"] . '">' . $tdata[3] . '</a>';
	$table_mbs->rowclass[] = '';
	$table_mbs->data[] = $tdata;
	
	$tdata = array();
	$tdata[0] = html_print_image('images/module_notinit.png', true, array('title' => __('Monitor not init')));
	$tdata[1] = $data["monitor_not_init"] <= 0 ? '-' : $data["monitor_not_init"];
	$tdata[1] = '<a style="color: ' . COL_NOTINIT . ';" class="big_data" href="' . $urls["monitor_not_init"] . '">' . $tdata[1] . '</a>';
	
	$tdata[2] = $tdata[3] = '';
	$table_mbs->rowclass[] = '';
	$table_mbs->data[] = $tdata;

	if ($data["monitor_checks"] > 0) {
		$tdata = array();
		$table_mbs->colspan[count($table_mbs->data)][0] = 4;
		$table_mbs->cellstyle[count($table_mbs->data)][0] = 'text-align: center;';
		$tdata[0] = '<div id="outter_status_pie" style="height: ' . $graph_height . 'px">' .
			'<div id="status_pie" style="margin: auto; width: ' . $graph_width . 'px;">' .
				graph_agent_status(false, $graph_width, $graph_height, true, true, $data_agents) .
			'</div></div>';
		$table_mbs->rowclass[] = '';
		$table_mbs->data[] = $tdata;
	}
	
	$output = '
		<fieldset class="databox tactical_set">
			<legend>' . 
				__('Monitors by status') . 
			'</legend>' . 
			html_print_table($table_mbs, true) .
		'</fieldset>';
	
	return $output;
}

function reporting_get_stats_summary($data, $graph_width, $graph_height) {
	global $config;
	
	// Alerts table
	$table_sum = html_get_predefined_table();
	
	$tdata = array();
	$table_sum->colspan[count($table_sum->data)][0] = 2;
	$table_sum->colspan[count($table_sum->data)][2] = 2;
	$table_sum->cellstyle[count($table_sum->data)][0] = 'text-align: center;';
	$table_sum->cellstyle[count($table_sum->data)][2] = 'text-align: center;';
	$tdata[0] = '<span class="med_data" style="color: #666">' . __('Module status') . '</span>';
	$tdata[2] = '<span class="med_data" style="color: #666">' . __('Alert level') . '</span>';
	$table_sum->rowclass[] = '';
	$table_sum->data[] = $tdata;
	
	$tdata = array();
	$table_sum->colspan[count($table_sum->data)][0] = 2;
	$table_sum->colspan[count($table_sum->data)][2] = 2;
	$table_sum->cellstyle[count($table_sum->data)][0] = 'text-align: center;';
	$table_sum->cellstyle[count($table_sum->data)][2] = 'text-align: center;';
	
	if ($data["monitor_checks"] > 0) {
		$tdata[0] = '<div style="margin: auto; width: ' . $graph_width . 'px;">' . graph_agent_status (false, $graph_width, $graph_height, true, true) . '</div>';
	}
	else {
		$tdata[2] = html_print_image('images/image_problem.png', true, array('width' => $graph_width));
	}
	if ($data["monitor_alerts"] > 0) {
		$tdata[2] = '<div style="margin: auto; width: ' . $graph_width . 'px;">' . graph_alert_status ($data["monitor_alerts"], $data["monitor_alerts_fired"], $graph_width, $graph_height, true, true) . '</div>';
	}
	else {
		$tdata[2] = html_print_image('images/image_problem.png', true, array('width' => $graph_width));
	}
		$table_sum->rowclass[] = '';
		$table_sum->data[] = $tdata;
	
	$output = '<fieldset class="databox tactical_set">
				<legend>' . 
					__('Summary') . 
				'</legend>' . 
				html_print_table($table_sum, true) . '</fieldset>';
	
	return $output;
}

function reporting_get_stats_alerts($data, $links = false) {
	global $config;
	
	// Link URLS
	$mobile = false;
	if (isset($data['mobile'])) {
		if ($data['mobile']) {
			$mobile = true;
		}
	}
	
	if ($mobile) {
		$urls = array();
		$urls['monitor_alerts'] = "index.php?page=alerts&status=all_enabled";
		$urls['monitor_alerts_fired'] = "index.php?page=alerts&status=fired";
	}
	else {
		$urls = array();
		if ($links) {
			$urls['monitor_alerts'] = "index.php?sec=estado&sec2=operation/agentes/alerts_status&pure=" . $config['pure'];
			$urls['monitor_alerts_fired'] = "index.php?sec=estado&sec2=operation/agentes/alerts_status&filter=fired&pure=" . $config['pure'];
		} else {
			$urls['monitor_alerts'] = "index.php?sec=estado&amp;sec2=operation/agentes/alerts_status&amp;refr=60";
			$urls['monitor_alerts_fired'] = "index.php?sec=estado&amp;sec2=operation/agentes/alerts_status&amp;refr=60&filter=fired";
		}
	}
	
	// Alerts table
	$table_al = html_get_predefined_table();
	
	$tdata = array();
	$tdata[0] = html_print_image('images/bell.png', true, array('title' => __('Defined alerts')));
	$tdata[1] = $data["monitor_alerts"] <= 0 ? '-' : $data["monitor_alerts"];
	$tdata[1] = '<a class="big_data" href="' . $urls["monitor_alerts"] . '">' . $tdata[1] . '</a>';
	
	$tdata[2] = html_print_image('images/bell_error.png', true, array('title' => __('Fired alerts')));
	$tdata[3] = $data["monitor_alerts_fired"] <= 0 ? '-' : $data["monitor_alerts_fired"];
	$tdata[3] = '<a style="color: ' . COL_ALERTFIRED . ';" class="big_data" href="' . $urls["monitor_alerts_fired"] . '">' . $tdata[3] . '</a>';
	$table_al->rowclass[] = '';
	$table_al->data[] = $tdata;
	
	$output = '<fieldset class="databox tactical_set">
				<legend>' . 
					__('Defined and fired alerts') . 
				'</legend>' . 
				html_print_table($table_al, true) . '</fieldset>';
	
	return $output;
}

function reporting_get_stats_users($data) {
	global $config;
	
	// Link URLS
	$urls = array();
	if (check_acl ($config['id_user'], 0, "UM")) {
		$urls['defined_users'] = "index.php?sec=gusuarios&amp;sec2=godmode/users/user_list";
	}
	else {
		$urls['defined_users'] = 'javascript:';
	}
	
	// Users table
	$table_us = html_get_predefined_table();
	
	$tdata = array();
	$tdata[0] = html_print_image('images/user_green.png', true, array('title' => __('Defined users')));
	$tdata[1] = count (get_users ());
	$tdata[1] = '<a class="big_data" href="' . $urls["defined_users"] . '">' . $tdata[1] . '</a>';
	
	$tdata[2] = $tdata[3] = '&nbsp;';
	$table_us->rowclass[] = '';
	$table_us->data[] = $tdata;
	
	$output = '<fieldset class="databox tactical_set">
				<legend>' . 
					__('Users') . 
				'</legend>' . 
				html_print_table($table_us, true) . '</fieldset>';
	
	return $output;
}

function reporting_get_stats_agents_monitors($data) {
	global $config;
	
	// Link URLS
	$mobile = false;
	if (isset($data['mobile'])) {
		if ($data['mobile']) {
			$mobile = true;
		}
	}
	
	if ($mobile) {
		$urls = array();
		$urls['total_agents'] = "index.php?page=agents";
		$urls['monitor_checks'] = "index.php?page=modules";
	}
	else {
		$urls = array();
		$urls['total_agents'] = "index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=60";
		$urls['monitor_checks'] = "index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=60&amp;status=-1";
	}
	
	// Agents and modules table
	$table_am = html_get_predefined_table();
	
	$tdata = array();
	$tdata[0] = html_print_image('images/agent.png', true, array('title' => __('Total agents')));
	$tdata[1] = $data["total_agents"] <= 0 ? '-' : $data["total_agents"];
	$tdata[1] = '<a class="big_data" href="' . $urls['total_agents'] . '">' . $tdata[1] . '</a>';
	
	$tdata[2] = html_print_image('images/module.png', true, array('title' => __('Monitor checks')));
	$tdata[3] = $data["monitor_checks"] <= 0 ? '-' : $data["monitor_checks"];
	$tdata[3] = '<a class="big_data" href="' . $urls['monitor_checks'] . '">' . $tdata[3] . '</a>';
	$table_am->rowclass[] = '';
	$table_am->data[] = $tdata;
	
	$output = '<fieldset class="databox tactical_set">
				<legend>' . 
					__('Total agents and monitors') . 
				'</legend>' . 
				html_print_table($table_am, true) . '</fieldset>';
	
	return $output;
}

function reporting_get_stats_indicators($data, $width = 280, $height = 20, $html = true) {
	$table_ind = html_get_predefined_table();
	
	$servers = array();
	$servers["all"] = (int) db_get_value ('COUNT(id_server)','tserver');
	$servers["up"] = (int) servers_check_status ();
	$servers["down"] = $servers["all"] - $servers["up"];
	if ($servers["all"] == 0) {
		$servers["health"] = 0;
	}
	else {
		$servers["health"] = $servers["up"] / ($servers["all"] / 100);
	}
	
	if ($html) {
		$tdata[0] = '<fieldset class="databox tactical_set">
						<legend>' . 
							__('Server health') . ui_print_help_tip (sprintf(__('%d Downed servers'), $servers["down"]), true) . 
						'</legend>' . 
						progress_bar($servers["health"], $width, $height, '', 0) . '</fieldset>';
		$table_ind->rowclass[] = '';
		$table_ind->data[] = $tdata;
		
		$tdata[0] = '<fieldset class="databox tactical_set">
						<legend>' . 
							__('Monitor health') . ui_print_help_tip (sprintf(__('%d Not Normal monitors'), $data["monitor_not_normal"]), true) . 
						'</legend>' . 
						progress_bar($data["monitor_health"], $width, $height, $data["monitor_health"].'% '.__('of monitors up'), 0) . '</fieldset>';
		$table_ind->rowclass[] = '';
		$table_ind->data[] = $tdata;
		
		$tdata[0] = '<fieldset class="databox tactical_set">
						<legend>' . 
							__('Module sanity') . ui_print_help_tip (sprintf(__('%d Not inited monitors'), $data["monitor_not_init"]), true) .
						'</legend>' . 
						progress_bar($data["module_sanity"], $width, $height, $data["module_sanity"].'% '.__('of total modules inited'), 0) . '</fieldset>';
		$table_ind->rowclass[] = '';
		$table_ind->data[] = $tdata;
		
		$tdata[0] = '<fieldset class="databox tactical_set">
						<legend>' . 
							__('Alert level') . ui_print_help_tip (sprintf(__('%d Fired alerts'), $data["monitor_alerts_fired"]), true) . 
						'</legend>' . 
						progress_bar($data["alert_level"], $width, $height, $data["alert_level"].'% '.__('of defined alerts not fired'), 0) . '</fieldset>';
		$table_ind->rowclass[] = '';
		$table_ind->data[] = $tdata;
		
		
		return html_print_table($table_ind, true);
	}
	else {
		$return = array();
		
		$return['server_health'] = array(
			'title' => __('Server health'),
			'graph' => progress_bar($servers["health"], $width, $height, '', 0));
		$return['monitor_health'] = array(
			'title' => __('Monitor health'),
			'graph' => progress_bar($data["monitor_health"], $width, $height, $data["monitor_health"].'% '.__('of monitors up'), 0));
		$return['module_sanity'] = array(
			'title' => __('Module sanity'),
			'graph' => progress_bar($data["module_sanity"], $width, $height, $data["module_sanity"].'% '.__('of total modules inited'), 0));
		$return['alert_level'] = array(
			'title' => __('Alert level'),
			'graph' => progress_bar($data["alert_level"], $width, $height, $data["alert_level"].'% '.__('of defined alerts not fired'), 0));
		
		return $return;
	}
}

/** 
 * Get general statistical info on a group
 * 
 * @param int Group Id to get info from. 0 = all
 * 
 * @return array Group statistics
 */
function reporting_get_group_stats ($id_group = 0, $access = 'AR') {
	global $config;
	
	$data = array ();
	$data["monitor_checks"] = 0;
	$data["monitor_not_init"] = 0;
	$data["monitor_unknown"] = 0;
	$data["monitor_ok"] = 0;
	$data["monitor_bad"] = 0; // Critical + Unknown + Warning
	$data["monitor_warning"] = 0;
	$data["monitor_critical"] = 0;
	$data["monitor_not_normal"] = 0;
	$data["monitor_alerts"] = 0;
	$data["monitor_alerts_fired"] = 0;
	$data["monitor_alerts_fire_count"] = 0;
	$data["total_agents"] = 0;
	$data["total_alerts"] = 0;
	$data["total_checks"] = 0;
	$data["alerts"] = 0;
	$data["agents_unknown"] = 0;
	$data["monitor_health"] = 100;
	$data["alert_level"] = 100;
	$data["module_sanity"] = 100;
	$data["server_sanity"] = 100;
	$data["total_not_init"] = 0;
	$data["monitor_non_init"] = 0;
	$data["agent_ok"] = 0;
	$data["agent_warning"] = 0;
	$data["agent_critical"] = 0;
	$data["agent_unknown"] = 0;
	$data["agent_not_init"] = 0;
	
	$cur_time = get_system_time ();
	
	//Check for access credentials using check_acl. More overhead, much safer
	if (!check_acl ($config["id_user"], $id_group, $access)) {
		return $data;
	}
	
	if ($id_group == 0) {
		$id_group = array_keys(
			users_get_groups($config['id_user'], $access, false));
	}
	
	// -----------------------------------------------------------------
	// Server processed stats. NOT realtime (taken from tgroup_stat)
	// -----------------------------------------------------------------
	if ($config["realtimestats"] == 0) {
		
		if (!is_array($id_group)){
			$my_group = $id_group;
			$id_group = array();
			$id_group[0] = $my_group;
		}
		
		foreach ($id_group as $group) {
			$group_stat = db_get_all_rows_sql ("SELECT *
				FROM tgroup_stat, tgrupo
				WHERE tgrupo.id_grupo = tgroup_stat.id_group
					AND tgroup_stat.id_group = $group
				ORDER BY nombre");
			
			$data["monitor_checks"] += $group_stat[0]["modules"];
			$data["agent_not_init"] += $group_stat[0]["non-init"];
			$data["agent_unknown"] += $group_stat[0]["unknown"];
			$data["agent_ok"] += $group_stat[0]["normal"];
			$data["agent_warning"] += $group_stat[0]["warning"];
			$data["agent_critical"] += $group_stat[0]["critical"];
			$data["monitor_alerts"] += $group_stat[0]["alerts"];
			$data["monitor_alerts_fired"] += $group_stat[0]["alerts_fired"];
			$data["monitor_alerts_fire_count"] += $group_stat[0]["alerts_fired"];
			$data["total_checks"] += $group_stat[0]["modules"];
			$data["total_alerts"] += $group_stat[0]["alerts"];
			$data["total_agents"] += $group_stat[0]["agents"];
			$data["agents_unknown"] += $group_stat[0]["agents_unknown"];
			$data["utimestamp"] = $group_stat[0]["utimestamp"];
			
			// This fields are not in database
			$data["monitor_ok"] += groups_monitor_ok($group);
			$data["monitor_warning"] += groups_monitor_warning($group);
			$data["monitor_critical"] += groups_monitor_critical($group);
			$data["monitor_unknown"] += groups_monitor_unknown($group);
			$data["monitor_not_init"] += groups_monitor_not_init($group);
		}
		
	// -------------------------------------------------------------------
	// Realtime stats, done by PHP Console
	// -------------------------------------------------------------------
	}
	else {
		
		if (!is_array($id_group)){
			$my_group = $id_group;
			$id_group = array();
			$id_group[0] = $my_group;
		}
		
		// Store the groups where we are quering
		$covered_groups = array();
		$group_array = array();
		foreach ($id_group as $group) {
			$children = groups_get_childrens($group);
			
			//Show empty groups only if they have children with agents
			//$group_array = array();
			
			foreach ($children as $sub) {
				// If the group is quering previously, we ingore it
				if (!in_array($sub['id_grupo'],$covered_groups)){
					array_push($covered_groups, $sub['id_grupo']);
					array_push($group_array, $sub['id_grupo']);
				}
				
			}
			
			// Add id of this group to create the clause
			// If the group is quering previously, we ingore it
			if (!in_array($group,$covered_groups)){
				array_push($covered_groups, $group);
				array_push($group_array, $group);
			}
			
			// If there are not groups to query, we jump to nextone
			
			if (empty($group_array)) {
				continue;
			}
		}
		
		if (!empty($group_array)) {
			// FOR THE FUTURE: Split the groups into groups with tags restrictions and groups without it
			// To calculate in the light way the non tag restricted and in the heavy way the others
			/*
			$group_restricted_data = tags_get_acl_tags($config['id_user'], $group_array, $access, 'data');
			$tags_restricted_groups = array_keys($group_restricted_data);
			
			$no_tags_restricted_groups = $group_array;
			foreach ($no_tags_restricted_groups as $k => $v) {
				if (in_array($v, $tags_restricted_groups)) {
					unset($no_tags_restricted_groups[$k]);
				}
			}
			*/
			
			if (!empty($group_array)) {
				// Get unknown agents by using the status code in modules
				$data["agents_unknown"] += groups_agent_unknown ($group_array);
				
				// Get monitor NOT INIT, except disabled AND async modules
				$data["monitor_not_init"] += groups_monitor_not_init ($group_array);
				
				// Get monitor OK, except disabled and non-init
				$data["monitor_ok"] += groups_monitor_ok ($group_array);
				
				// Get monitor CRITICAL, except disabled and non-init
				$data["monitor_critical"] += groups_monitor_critical ($group_array);
				
				// Get monitor WARNING, except disabled and non-init
				$data["monitor_warning"] += groups_monitor_warning ($group_array);
				
				// Get monitor UNKNOWN, except disabled and non-init
				$data["monitor_unknown"] += groups_monitor_unknown ($group_array);
				
				// Get alerts configured, except disabled 
				$data["monitor_alerts"] += groups_monitor_alerts ($group_array) ;
				
				// Get alert configured currently FIRED, except disabled 
				$data["monitor_alerts_fired"] += groups_monitor_fired_alerts ($group_array);
				
				// Calculate totals using partial counts from above
				
				// Get TOTAL agents in a group
				$data["total_agents"] += groups_total_agents ($group_array);
				
				// Get TOTAL non-init modules, except disabled ones and async modules
				$data["total_not_init"] += $data["monitor_not_init"];
			
				// Get Agents OK
				$data["agent_ok"] += groups_agent_ok($group_array);
				
				// Get Agents Warning 
				$data["agent_warning"] += groups_agent_warning($group_array);
				
				// Get Agents Critical
				$data["agent_critical"] += groups_agent_critical($group_array);
				
				// Get Agents Unknown
				$data["agent_unknown"] += groups_agent_unknown($group_array);
				
				// Get Agents Not init
				$data["agent_not_init"] += groups_agent_not_init($group_array);
			}
			
			// Get total count of monitors for this group, except disabled.
			$data["monitor_checks"] = $data["monitor_not_init"] + $data["monitor_unknown"] + $data["monitor_warning"] + $data["monitor_critical"] + $data["monitor_ok"];
			
			// Calculate not_normal monitors
			$data["monitor_not_normal"] += $data["monitor_checks"] - $data["monitor_ok"];
		}
		
		// Get total count of monitors for this group, except disabled.
		
		$data["monitor_checks"] = $data["monitor_not_init"] + $data["monitor_unknown"] + $data["monitor_warning"] + $data["monitor_critical"] + $data["monitor_ok"];
		
		/*
		 Monitor health (percentage)
		 Data health (percentage)
		 Global health (percentage)
		 Module sanity (percentage)
		 Alert level (percentage)
		 
		 Server Sanity	0% Uninitialized modules
		 
		 */
	}
	
	if ($data["monitor_unknown"] > 0 && $data["monitor_checks"] > 0) {
		$data["monitor_health"] = format_numeric (100 - ($data["monitor_not_normal"] / ($data["monitor_checks"] / 100)), 1);
	}
	else {
		$data["monitor_health"] = 100;
	}
	
	if ($data["monitor_not_init"] > 0 && $data["monitor_checks"] > 0) {
		$data["module_sanity"] = format_numeric (100 - ($data["monitor_not_init"] / ($data["monitor_checks"] / 100)), 1);
	}
	else {
		$data["module_sanity"] = 100;
	}
	
	if (isset($data["alerts"])) {
		if ($data["monitor_alerts_fired"] > 0 && $data["alerts"] > 0) {
			$data["alert_level"] = format_numeric (100 - ($data	["monitor_alerts_fired"] / ($data["alerts"] / 100)), 1);
		}
		else {
			$data["alert_level"] = 100;
		}
	} 
	else {
		$data["alert_level"] = 100;
		$data["alerts"] = 0;
	}
	
	$data["monitor_bad"] = $data["monitor_critical"] + $data["monitor_warning"];
	
	if ($data["monitor_bad"] > 0 && $data["monitor_checks"] > 0) {
		$data["global_health"] = format_numeric (100 - ($data["monitor_bad"] / ($data["monitor_checks"] / 100)), 1);
	}
	else {
		$data["global_health"] = 100;
	}
	
	$data["server_sanity"] = format_numeric (100 - $data["module_sanity"], 1);
	
	return ($data);
}


/** 
 * Get an event reporting table.
 *
 * It construct a table object with all the events happened in a group
 * during a period of time.
 * 
 * @param int Group id to get the report.
 * @param int Period of time to get the report.
 * @param int Beginning date of the report
 * @param int Flag to return or echo the report table (echo by default).
 * 
 * @return object A table object
 */
function reporting_event_reporting ($id_group, $period, $date = 0, $return = false) {
	if (empty ($date)) {
		$date = get_system_time ();
	}
	elseif (!is_numeric ($date)) {
		$date = strtotime ($date);
	}
	
	$table->data = array ();
	$table->head = array ();
	$table->head[0] = __('Status');
	$table->head[1] = __('Event name');
	$table->head[2] = __('User ID');
	$table->head[3] = __('Timestamp');
	
	$events = events_get_group_events ($id_group, $period, $date);
	if (empty ($events)) {
		$events = array ();
	}
	foreach ($events as $event) {
		$data = array ();
		if ($event["estado"] == 0)
			$data[0] = html_print_image("images/dot_red.png", true);
		else
			$data[0] = html_print_image("images/dot_green.png", true);
		$data[1] = $event['evento'];
		$data[2] = $event['id_usuario'] != '0' ? $event['id_usuario'] : '';
		$data[3] = $event["timestamp"];
		array_push ($table->data, $data);
	}
	
	if (empty ($return))
		html_print_table ($table);
	
	return $table;
}

/** 
 * Get a table report from a alerts fired array.
 * 
 * @param array Alerts fired array. 
 * @see function get_alerts_fired ()
 * 
 * @return object A table object with a report of the fired alerts.
 */
function reporting_get_fired_alerts_table ($alerts_fired) {
	$agents = array ();
	global $config;
	
	require_once ($config["homedir"].'/include/functions_alerts.php');
	
	foreach (array_keys ($alerts_fired) as $id_alert) {
		$alert_module = alerts_get_alert_agent_module ($id_alert);
		$template = alerts_get_alert_template ($id_alert);
		
		/* Add alerts fired to $agents_fired_alerts indexed by id_agent */
		$id_agent = db_get_value ('id_agente', 'tagente_modulo',
			'id_agente_modulo', $alert_module['id_agent_module']);
		if (!isset ($agents[$id_agent])) {
			$agents[$id_agent] = array ();
		}
		array_push ($agents[$id_agent], array ($alert_module, $template));
	}
	
	$table->data = array ();
	$table->head = array ();
	$table->head[0] = __('Agent');
	$table->head[1] = __('Alert description');
	$table->head[2] = __('Times fired');
	$table->head[3] = __('Priority');
	
	foreach ($agents as $id_agent => $alerts) {
		$data = array ();
		foreach ($alerts as $tuple) {
			$alert_module = $tuple[0];
			$template = $tuple[1];
			if (! isset ($data[0]))
				$data[0] = agents_get_name ($id_agent);
			else
				$data[0] = '';
			$data[1] = $template['name'];
			$data[2] = $alerts_fired[$alert_module['id']];
			$data[3] = get_alert_priority ($alert_module['priority']);
			array_push ($table->data, $data);
		}
	}
	
	return $table;
}

/**
 * Get a report for alerts of agent.
 *
 * It prints the numbers of alerts defined, fired and not fired of agent.
 *
 * @param int $id_agent Agent to get info of the alerts.
 * @param int $period Period of time of the desired alert report.
 * @param int $date Beggining date of the report (current date by default).
 * @param bool $return Flag to return or echo the report (echo by default).
 * @param bool Flag to return the html or table object, by default html.
 * 
 * @return mixed A table object (XHTML) or object table is false the html.
 */
function reporting_alert_reporting_agent ($id_agent, $period = 0, $date = 0, $return = true, $html = true) {
	if (!is_numeric ($date)) {
		$date = strtotime ($date);
	}
	if (empty ($date)) {
		$date = get_system_time ();
	}	
	$table->width = '99%';
	$table->data = array ();
	$table->head = array ();
	$table->head[0] = __('Module');
	$table->head[1] = __('Template');
	$table->head[2] = __('Actions');
	$table->head[3] = __('Fired');
	
	$alerts = agents_get_alerts ($id_agent);
	
	if (isset($alerts['simple'])) {
		$i = 0;
		if ($alerts['simple'] === false)
			$alerts['simple'] = array();
		
		foreach ($alerts['simple'] as $alert) {
			$data = array();
			$data[0] = db_get_value_filter('nombre', 'tagente_modulo', array('id_agente_modulo' => $alert['id_agent_module']));
			$data[1] = db_get_value_filter('name', 'talert_templates', array('id' => $alert['id_alert_template']));
			$actions = db_get_all_rows_sql('SELECT name 
				FROM talert_actions 
				WHERE id IN (SELECT id_alert_action 
					FROM talert_template_module_actions 
					WHERE id_alert_template_module = ' . $alert['id'] . ');');
			$data[2] = '<ul class="action_list">';
			if ($actions === false) {
				$row = db_get_row_sql('SELECT id_alert_action
					FROM talert_templates
					WHERE id IN (SELECT id_alert_template
						FROM talert_template_modules
						WHERE id = ' . $alert['id'] . ')');
				$id_action = 0;
				if (!empty($row))
					$id_action = $row['id_alert_action'];
				
				// Prevent from void action
				if (empty($id_action))
					$id_action = 0;
					
				$actions = db_get_all_rows_sql('SELECT name 
					FROM talert_actions 
					WHERE id = ' . $id_action);
			}
			
			if ($actions === false)
				$actions = array();
			
			foreach ($actions as $action) {
				$data[2] .= '<li>' . $action['name'] . '</li>';
			}
			$data[2] .= '</ul>';
			
			$data[3] = '<ul style="list-style-type: disc; margin-left: 10px;">';
			
			$firedTimes = get_agent_alert_fired($id_agent, $alert['id'], (int) $period, (int) $date);
			if ($firedTimes === false) {
				$firedTimes = array();
			}
			
			if ($firedTimes === false)
				$firedTimes = array();
			
			foreach ($firedTimes as $fireTime) {
				$data[3] .= '<li>' . $fireTime['timestamp'] . '</li>';
			}
			$data[3] .= '</ul>';
			
			if ($alert['disabled']) {
				$table->rowstyle[$i] = 'color: grey; font-style: italic;';
			}
			$i++;
			
			array_push ($table->data, $data);
		}
	}
	
	if ($html) {
		return html_print_table ($table, $return);
	}
	else {
		return $table;
	}
}

/**
 * Get a report for alerts of group.
 *
 * It prints the numbers of alerts defined, fired and not fired of agent.
 *
 * @param int $id_agent_module Module to get info of the alerts.
 * @param int $period Period of time of the desired alert report.
 * @param int $date Beggining date of the report (current date by default).
 * @param bool $return Flag to return or echo the report (echo by default).
 * @param bool Flag to return the html or table object, by default html.
 * 
 * @return mixed A table object (XHTML) or object table is false the html.
 */
function reporting_alert_reporting_group ($id_group, $period = 0, $date = 0, $return = true, $html = true) {
	if (!is_numeric ($date)) {
		$date = strtotime ($date);
	}
	if (empty ($date)) {
		$date = get_system_time ();
	}
	
	$table->width = '99%';
	$table->data = array ();
	$table->head = array ();
	
	$table->head[0] = __('Agent');
	$table->head[1] = __('Module');
	$table->head[2] = __('Template');
	$table->head[3] = __('Actions');
	$table->head[4] = __('Fired');
	
	if ($id_group == 0) {
		$alerts = db_get_all_rows_sql('
			SELECT *
			FROM talert_template_modules
			WHERE disabled = 0
				AND id_agent_module IN (
					SELECT id_agente_modulo
					FROM tagente_modulo)');
	}
	else {
		$alerts = db_get_all_rows_sql('
			SELECT *
			FROM talert_template_modules
			WHERE disabled = 0
				AND id_agent_module IN (
					SELECT id_agente_modulo
					FROM tagente_modulo
					WHERE id_agente IN (
						SELECT id_agente
						FROM tagente WHERE id_grupo = ' . $id_group . '))');
	}
	
	if ($alerts === false) {
		$alerts = array();
	}
	
	$i = 0;
	foreach ($alerts as $alert) {
		$data = array();
		
		$data[] = io_safe_output(
			agents_get_name(
				agents_get_agent_id_by_module_id(
					$alert['id_agent_module'])));
		
		$data[] = io_safe_output(
			modules_get_agentmodule_name($alert['id_agent_module']));
		
		$data[] = db_get_value_filter('name',
			'talert_templates',
			array('id' => $alert['id_alert_template']));
		
		$actions = db_get_all_rows_sql('SELECT name 
			FROM talert_actions 
			WHERE id IN (SELECT id_alert_action 
				FROM talert_template_module_actions 
				WHERE id_alert_template_module = ' . $alert['id_agent_module'] . ');');
		$list = '<ul class="action_list">';
		if ($actions === false) {
			$row = db_get_row_sql('SELECT id_alert_action
				FROM talert_templates
				WHERE id IN (SELECT id_alert_template
					FROM talert_template_modules
					WHERE id = ' . $alert['id'] . ')');
			$id_action = 0;
			if (!empty($row))
				$id_action = $row['id_alert_action'];
			
			// Prevent from void action
			if (empty($id_action))
				$id_action = 0;
			
			$actions = db_get_all_rows_sql('SELECT name 
				FROM talert_actions 
				WHERE id = ' . $id_action);
		}
		
		if ($actions == false)
			$actions = array();
		
		foreach ($actions as $action) {
			$list .= '<li>' . $action['name'] . '</li>';
		}
		$list .= '</ul>';
		$data[] = $list;
		
		$list = '<ul style="list-style-type: disc; margin-left: 10px;">';
		
		$firedTimes = get_module_alert_fired(
			$alert['id_agent_module'],
			$alert['id'], (int) $period, (int) $date);
		
		if ($firedTimes === false) {
			$firedTimes = array();
		}
		foreach ($firedTimes as $fireTime) {
			$list .= '<li>' . $fireTime['timestamp'] . '</li>';
		}
		$list .= '</ul>';
		
		if ($alert['disabled']) {
			$table->rowstyle[$i] = 'color: grey; font-style: italic;';
		}
		$i++;
		$data[] = $list;
		
		array_push ($table->data, $data);
	}
	
	if ($html) {
		return html_print_table ($table, $return);
	}
	else {
		return $table;
	}
}

/**
 * Get a report for alerts of module.
 *
 * It prints the numbers of alerts defined, fired and not fired of agent.
 *
 * @param int $id_agent_module Module to get info of the alerts.
 * @param int $period Period of time of the desired alert report.
 * @param int $date Beggining date of the report (current date by default).
 * @param bool $return Flag to return or echo the report (echo by default).
 * @param bool Flag to return the html or table object, by default html.
 * 
 * @return mixed A table object (XHTML) or object table is false the html.
 */
function reporting_alert_reporting_module ($id_agent_module, $period = 0, $date = 0, $return = true, $html = true) {
	if (!is_numeric ($date)) {
		$date = strtotime ($date);
	}
	if (empty ($date)) {
		$date = get_system_time ();
	}
	
	$table->width = '99%';
	$table->data = array ();
	$table->head = array ();
	$table->head[1] = __('Template');
	$table->head[2] = __('Actions');
	$table->head[3] = __('Fired');
	
	
	$alerts = db_get_all_rows_sql('SELECT *, t1.id as id_alert_template_module
		FROM talert_template_modules AS t1
			INNER JOIN talert_templates AS t2 ON t1.id_alert_template = t2.id
		WHERE id_agent_module = ' . $id_agent_module);
	
	if ($alerts === false) {
		$alerts = array();
	}
	
	$i = 0;
	foreach ($alerts as $alert) {
		$data = array();
		$data[1] = db_get_value_filter('name', 'talert_templates', array('id' => $alert['id_alert_template']));
		$actions = db_get_all_rows_sql('SELECT name 
			FROM talert_actions 
			WHERE id IN (SELECT id_alert_action 
				FROM talert_template_module_actions 
				WHERE id_alert_template_module = ' . $alert['id_alert_template_module'] . ');');
		$data[2] = '<ul class="action_list">';
		
		if ($actions === false) {
			$row = db_get_row_sql('SELECT id_alert_action
				FROM talert_templates
				WHERE id IN (SELECT id_alert_template
					FROM talert_template_modules
					WHERE id = ' . $alert['id_alert_template_module'] . ')');
			$id_action = 0;
			if (!empty($row))
				$id_action = $row['id_alert_action'];
			
			// Prevent from void action
			if (empty($id_action))
				$id_action = 0;
			
			$actions = db_get_all_rows_sql('SELECT name 
				FROM talert_actions 
				WHERE id = ' . $id_action);
		}
		
		if ($actions === false) {
			$actions = array();
		}
		
		foreach ($actions as $action) {
			$data[2] .= '<li>' . $action['name'] . '</li>';
		}
		$data[2] .= '</ul>';
		
		$data[3] = '<ul style="list-style-type: disc; margin-left: 10px;">';
		$firedTimes = get_module_alert_fired($id_agent_module, $alert['id_alert_template_module'], (int) $period, (int) $date);
		if ($firedTimes === false) {
			$firedTimes = array();
		}
		foreach ($firedTimes as $fireTime) {
			$data[3] .= '<li>' . $fireTime['timestamp'] . '</li>';
		}
		$data[3] .= '</ul>';
		
		if ($alert['disabled']) {
			$table->rowstyle[$i] = 'color: grey; font-style: italic;';
		}
		$i++;
		
		array_push ($table->data, $data);
	}
	
	if ($html) {
		return html_print_table ($table, $return);
	}
	else {
		return $table;
	}
}

/**
 * Get a report for alerts in a group of agents.
 *
 * It prints the numbers of alerts defined, fired and not fired in a group.
 * It also prints all the alerts that were fired grouped by agents.
 *
 * @param int $id_group Group to get info of the alerts.
 * @param int $period Period of time of the desired alert report.
 * @param int $date Beggining date of the report (current date by default).
 * @param bool $return Flag to return or echo the report (echo by default).
 *
 * @return string
 */
function reporting_alert_reporting ($id_group, $period = 0, $date = 0, $return = false) {
	global $config;
	
	$output = '';
	$alerts = get_group_alerts ($id_group);
	$alerts_fired = get_alerts_fired ($alerts, $period, $date);
	
	$fired_percentage = 0;
	if (sizeof ($alerts) > 0)
		$fired_percentage = round (sizeof ($alerts_fired) / sizeof ($alerts) * 100, 2);
	$not_fired_percentage = 100 - $fired_percentage;
	
	$data = array ();
	$data[__('Alerts fired')] = $fired_percentage;
	$data[__('Alerts not fired')] = $not_fired_percentage;
	
	$output .= pie3d_graph(false, $data, 280, 150,
		__("other"), ui_get_full_url(false) . '/', $config['homedir'] .  "/images/logo_vertical_water.png",
		$config['fontpath'], $config['font_size']); 
	
	$output .= '<strong>'.__('Alerts fired').': '.sizeof ($alerts_fired).'</strong><br />';
	$output .= '<strong>'.__('Total alerts monitored').': '.sizeof ($alerts).'</strong><br />';
	
	if (! sizeof ($alerts_fired)) {
		if (!$return)
			echo $output;
		
		return $output;
	}
	$table = reporting_get_fired_alerts_table ($alerts_fired);
	$table->width = '100%';
	$table->class = 'databox';
	$table->size = array ();
	$table->size[0] = '100px';
	$table->style = array ();
	$table->style[0] = 'font-weight: bold';
	
	$output .= html_print_table ($table, true);
	
	if (!$return)
		echo $output;
	
	return $output;
}

/**
 * Get a report for monitors modules in a group of agents.
 *
 * It prints the numbers of monitors defined, showing those which went up and down, in a group.
 * It also prints all the down monitors in the group.
 *
 * @param int $id_group Group to get info of the monitors.
 * @param int $period Period of time of the desired monitor report.
 * @param int $date Beginning date of the report in UNIX time (current date by default).
 * @param bool $return Flag to return or echo the report (by default).
 *
 * @return string
 */
function reporting_monitor_health ($id_group, $period = 0, $date = 0, $return = false) {
	if (empty ($date)) //If date is 0, false or empty
		$date = get_system_time ();
	
	$datelimit = $date - $period;
	$output = '';
	
	$monitors = modules_get_monitors_in_group ($id_group);
	if (empty ($monitors)) //If monitors has returned false or an empty array
		return;
	$monitors_down = modules_get_monitors_down ($monitors, $period, $date);
	$down_percentage = round (count ($monitors_down) / count ($monitors) * 100, 2);
	$not_down_percentage = 100 - $down_percentage;
	
	$output .= '<strong>'.__('Total monitors').': '.count ($monitors).'</strong><br />';
	$output .= '<strong>'.__('Monitors down on period').': '.count ($monitors_down).'</strong><br />';
	
	$table = reporting_get_monitors_down_table ($monitors_down);
	$table->width = '100%';
	$table->class = 'databox';
	$table->size = array ();
	$table->size[0] = '100px';
	$table->style = array ();
	$table->style[0] = 'font-weight: bold';
	
	$table->size = array ();
	$table->size[0] = '100px';
	
	$output .= html_print_table ($table, true);
	
	$data = array();
	$data[__('Monitors OK')] = $down_percentage;
	$data[__('Monitors BAD')] = $not_down_percentage;
	
	$output .= pie3d_graph(false, $data, 280, 150,
		__("other"), ui_get_full_url(false) . '/', $config['homedir'] .  "/images/logo_vertical_water.png",
		$config['fontpath'], $config['font_size']); 
	
	if (!$return)
		echo $output;
	
	return $output;
}

/** 
 * Get a report table with all the monitors down.
 * 
 * @param array  An array with all the monitors down
 * @see function modules_get_monitors_down()
 * 
 * @return object A table object with a monitors down report.
 */
function reporting_get_monitors_down_table ($monitors_down) {
	$table->data = array ();
	$table->head = array ();
	$table->head[0] = __('Agent');
	$table->head[1] = __('Monitor');
	
	$agents = array ();
	if ($monitors_down) {
		foreach ($monitors_down as $monitor) {
			/* Add monitors fired to $agents_fired_alerts indexed by id_agent */
			$id_agent = $monitor['id_agente'];
			if (!isset ($agents[$id_agent])) {
				$agents[$id_agent] = array ();
			}
			array_push ($agents[$id_agent], $monitor);
			
			$monitors_down++;
		}
		foreach ($agents as $id_agent => $monitors) {
			$data = array ();
			foreach ($monitors as $monitor) {
				if (! isset ($data[0]))
					$data[0] = agents_get_name ($id_agent);
				else
					$data[0] = '';
				if ($monitor['descripcion'] != '') {
					$data[1] = $monitor['descripcion'];
				}
				else {
					$data[1] = $monitor['nombre'];
				}
				array_push ($table->data, $data);
			}
		}
	}
	
	return $table;
}

/**
 * Get a general report of a group of agents.
 *
 * It shows the number of agents and no more things right now. 
 *
 * @param int Group to get the report
 * @param bool Flag to return or echo the report (by default).
 * 
 * @return HTML string with group report
 */
function reporting_print_group_reporting ($id_group, $return = false) {
	$agents = agents_get_group_agents ($id_group, false, "none");
	$output = '<strong>' .
		sprintf(__('Agents in group: %s'), count($agents)) .
		'</strong><br />';
	
	if ($return === false)
		echo $output;
	
	return $output;
}

/** 
 * Get a report table of the fired alerts group by agents.
 * 
 * @param int Agent id to generate the report.
 * @param int Period of time of the report.
 * @param int Beginning date of the report in UNIX time (current date by default).
 * 
 * @return object A table object with the alert reporting..
 */
function reporting_get_agent_alerts_table ($id_agent, $period = 0, $date = 0) {
	global $config;
	$table->data = array ();
	$table->head = array ();
	$table->head[0] = __('Type');
	$table->head[1] = __('Description');
	$table->head[2] = __('Value');
	$table->head[3] = __('Threshold');
	$table->head[4] = __('Last fired');
	$table->head[5] = __('Times fired');
	
	require_once ($config["homedir"].'/include/functions_alerts.php');
	
	$alerts = agents_get_alerts ($id_agent);
	
	foreach ($alerts['simple'] as $alert) {
		$fires = get_alert_fires_in_period ($alert['id'], $period, $date);
		if (! $fires) {
			continue;
		}
		
		$template = alerts_get_alert_template ($alert['id_alert_template']);
		$data = array ();
		$data[0] = alerts_get_alert_templates_type_name ($template['type']);
		$data[1] = $template['name'];
		
		switch ($template['type']) {
		case 'regex':
			if ($template['matches_value'])
				$data[2] = '&#8771; "'.$template['value'].'"';
			else
				$data[2] = '&#8772; "'.$template['value'].'"';
			break;
		case 'equal':
		case 'not_equal':
			$data[2] = $template['value'];
			
			break;
		case 'max-min':
			$data[2] = __('Min.').': '.$template['min_value']. ' ';
			$data[2] .= __('Max.').': '.$template['max_value']. ' ';
			
			break;
		case 'max':
			$data[2] = $template['max_value'];
			
			break;
		case 'min':
			$data[2] = $template['min_value'];
			
			break;
		}
		$data[3] = $template['time_threshold'];
		$data[4] = ui_print_timestamp (get_alert_last_fire_timestamp_in_period ($alert['id'], $period, $date), true);
		$data[5] = $fires;
		
		array_push ($table->data, $data);
	}
	
	return $table;
}

/** 
 * Get a report of monitors in an agent.
 * 
 * @param int Agent id to get the report
 * @param int Period of time of the report.
 * @param int Beginning date of the report in UNIX time (current date by default).
 * 
 * @return object A table object with the report.
 */
function reporting_get_agent_monitors_table ($id_agent, $period = 0, $date = 0) {
	$n_a_string = __('N/A').'(*)';
	$table->head = array ();
	$table->head[0] = __('Monitor');
	$table->head[1] = __('Last failure');
	$table->data = array ();
	$monitors = modules_get_monitors_in_agent ($id_agent);
	
	if ($monitors === false) {
		return $table;
	}
	foreach ($monitors as $monitor) {
		$downs = modules_get_monitor_downs_in_period ($monitor['id_agente_modulo'], $period, $date);
		if (! $downs) {
			continue;
		}
		$data = array ();
		if ($monitor['descripcion'] != $n_a_string && $monitor['descripcion'] != '')
			$data[0] = $monitor['descripcion'];
		else
			$data[0] = $monitor['nombre'];
		$data[1] = modules_get_last_down_timestamp_in_period ($monitor['id_agente_modulo'], $period, $date);
		array_push ($table->data, $data);
	}
	
	return $table;
}

/** 
 * Get a report of all the modules in an agent.
 * 
 * @param int Agent id to get the report.
 * @param int Period of time of the report
 * @param int Beginning date of the report in UNIX time (current date by default).
 * 
 * @return object
 */
function reporting_get_agent_modules_table ($id_agent, $period = 0, $date = 0) {
	$table->data = array ();
	$n_a_string = __('N/A').'(*)';
	$modules = agents_get_modules ($id_agent, array ("nombre", "descripcion"));
	if ($modules === false)
		$modules = array();
	$data = array ();
	
	foreach ($modules as $module) {
		if ($module['descripcion'] != $n_a_string && $module['descripcion'] != '')
			$data[0] = $module['descripcion'];
		else
			$data[0] = $module['nombre'];
		array_push ($table->data, $data);
	}
	
	return $table;
}

/**
 * Get a detailed report of an agent
 *
 * @param int Agent to get the report.
 * @param int Period of time of the desired report.
 * @param int Beginning date of the report in UNIX time (current date by default).
 * @param bool Flag to return or echo the report (by default).
 *
 * @return string
 */
function reporting_get_agent_detailed ($id_agent, $period = 0, $date = 0, $return = false) {
	$output = '';
	$n_a_string = __('N/A(*)');
	
	/* Show modules in agent */
	$output .= '<div class="agent_reporting">';
	$output .= '<h3 style="text-decoration: underline">' .
		__('Agent') . ' - ' . agents_get_name ($id_agent) . '</h3>';
	$output .= '<h4>'.__('Modules').'</h3>';
	$table_modules = reporting_get_agent_modules_table ($id_agent, $period, $date);
	$table_modules->width = '99%';
	$output .= html_print_table ($table_modules, true);
	
	/* Show alerts in agent */
	$table_alerts = reporting_get_agent_alerts_table ($id_agent, $period, $date);
	$table_alerts->width = '99%';
	if (sizeof ($table_alerts->data)) {
		$output .= '<h4>'.__('Alerts').'</h4>';
		$output .= html_print_table ($table_alerts, true);
	}
	
	/* Show monitor status in agent (if any) */
	$table_monitors = reporting_get_agent_monitors_table ($id_agent, $period, $date);
	if (sizeof ($table_monitors->data) == 0) {
		$output .= '</div>';
		if (! $return)
			echo $output;
		return $output;
	}
	$table_monitors->width = '99%';
	$table_monitors->align = array ();
	$table_monitors->align[1] = 'right';
	$table_monitors->size = array ();
	$table_monitors->align[1] = '10%';
	$output .= '<h4>'.__('Monitors').'</h4>';
	$output .= html_print_table ($table_monitors, true);
	
	$output .= '</div>';
	
	if (! $return)
		echo $output;
	return $output;
}

/**
 * Get a detailed report of agents in a group.
 *
 * @param mixed Group(s) to get the report
 * @param int Period
 * @param int Timestamp to start from
 * @param bool Flag to return or echo the report (by default).
 *
 * @return string
 */
function reporting_agents_get_group_agents_detailed ($id_group, $period = 0, $date = 0, $return = false) {
	$agents = agents_get_group_agents ($id_group, false, "none");
	
	$output = '';
	foreach ($agents as $agent_id => $agent_name) {
		$output .= reporting_get_agent_detailed ($agent_id, $period, $date, true);
	}
	
	if ($return === false)
		echo $output;
	
	return $output;
}


/** 
 * Get a detailed report of summarized events per agent
 *
 * It construct a table object with all the grouped events happened in an agent
 * during a period of time.
 * 
 * @param mixed Agent id(s) to get the report from.
 * @param int Period of time (in seconds) to get the report.
 * @param int Beginning date (unixtime) of the report
 * @param bool Flag to return or echo the report table (echo by default).
 * 
 * @return A table object (XHTML)
 */
function reporting_get_agents_detailed_event ($id_agents, $period = 0,
	$date = 0, $return = false, $filter_event_validated = false,
	$filter_event_critical = false, $filter_event_warning = false, $filter_event_no_validated = false) {
	
	global $config;
	
	$id_agents = (array)safe_int ($id_agents, 1);
	
	if (!is_numeric ($date)) {
		$date = strtotime ($date);
	}
	if (empty ($date)) {
		$date = get_system_time ();
	}
	
	$table->width = '99%';
	
	$table->align = array();
	$table->align[0] = 'center';
	$table->align[1] = 'center';
	$table->align[3] = 'center';
	
	$table->data = array ();
	
	$table->head = array ();
	$table->head[0] = __('Status');
	$table->head[1] = __('Count');
	$table->head[2] = __('Name');
	$table->head[3] = __('Type');
	$table->head[4] = __('Criticity');
	$table->head[5] = __('Val. by');
	$table->head[6] = __('Timestamp');
	
	$events = array ();
	
	foreach ($id_agents as $id_agent) {
		$event = events_get_agent ($id_agent,
			(int)$period,
			(int)$date,
			$filter_event_validated, $filter_event_critical,
			$filter_event_warning, $filter_event_no_validated);
		
		if (!empty ($event)) {
			array_push ($events, $event);
		}
	}

	if ($events) {
		$note = '';
		if (count($events) >= 1000) {
			$note .= '* ' . __('Maximum of events shown') . ' (1000)<br>';
		}
		foreach ($events as $eventRow) {
			foreach ($eventRow as $k => $event) {
				//First pass along the class of this row
				$table->cellclass[$k][1] = $table->cellclass[$k][2] = 
				$table->cellclass[$k][4] = $table->cellclass[$k][5] =
				$table->cellclass[$k][6] =
					get_priority_class ($event["criticity"]);
				
				$data = array ();
				// Colored box
				switch ($event['estado']) {
					case 0:
						$img_st = "images/star.png";
						$title_st = __('New event');
						break;
					case 1:
						$img_st = "images/tick.png";
						$title_st = __('Event validated');
						break;
					case 2:
						$img_st = "images/hourglass.png";
						$title_st = __('Event in process');
						break;
				}
				$data[] = html_print_image ($img_st, true, 
					array ("class" => "image_status",
						"width" => 16,
						"title" => $title_st));
				
				$data[] = $event['event_rep'];
				
				$data[] = ui_print_truncate_text(
					io_safe_output($event['evento']),
					140, false, true);
				//$data[] = $event['event_type'];
				$data[] = events_print_type_img ($event["event_type"], true);
				
				$data[] = get_priority_name ($event['criticity']);
				if (empty($event['id_usuario']) && $event['estado'] == EVENT_VALIDATE) {
					$data[] = '<i>' . __('System') . '</i>';
				}
				else {
					$user_name = db_get_value ('fullname', 'tusuario', 'id_user', $event['id_usuario']);
					$data[] = io_safe_output($user_name);
				}
				$data[] = '<font style="font-size: 6pt;">' .
					date($config['date_format'], $event['timestamp_rep']) . '</font>';
				array_push ($table->data, $data);
			}
		}
	}
	
	if ($events)
		return html_print_table ($table, $return) . $note;
}

/**
 * Gets a detailed reporting of groups's events.  
 *
 * @param unknown_type $id_group Id of the group.
 * @param unknown_type $period Time period of the report.
 * @param unknown_type $date Date of the report.
 * @param unknown_type $return Whether to return or not.
 * @param unknown_type $html Whether to return HTML code or not.
 *
 * @return string Report of groups's events
 */
function reporting_get_group_detailed_event ($id_group, $period = 0,
	$date = 0, $return = false, $html = true,
	$filter_event_validated = false, $filter_event_critical = false,
	$filter_event_warning = false, $filter_event_no_validated = false) {
		
	global $config;
	
	if (!is_numeric ($date)) {
		$date = strtotime ($date);
	}
	if (empty ($date)) {
		$date = get_system_time ();
	}
	
	$table->width = '99%';
	
	$table->align = array();
	$table->align[0] = 'center';
	$table->align[2] = 'center';
	
	$table->data = array ();
	
	$table->head = array ();
	$table->head[0] = __('Status');
	$table->head[1] = __('Name');
	$table->head[2] = __('Type');
	$table->head[3] = __('Agent');
	$table->head[4] = __('Criticity');
	$table->head[5] = __('Val. by');
	$table->head[6] = __('Timestamp');
	
	$events = events_get_group_events($id_group, $period, $date,
		$filter_event_validated, $filter_event_critical,
		$filter_event_warning, $filter_event_no_validated);
	
	if ($events) {
		$note = '';
		if (count($events) >= 1000) {
			$note .= '* ' . __('Maximum of events shown') . ' (1000)<br>';
		}
		foreach ($events as $k => $event) {
			//First pass along the class of this row
			$table->cellclass[$k][1] = $table->cellclass[$k][3] =
			$table->cellclass[$k][4] = $table->cellclass[$k][5] =
			$table->cellclass[$k][6] =
				get_priority_class ($event["criticity"]);
			
			$data = array ();
			
			// Colored box
			switch ($event['estado']) {
				case 0:
					$img_st = "images/star.png";
					$title_st = __('New event');
					break;
				case 1:
					$img_st = "images/tick.png";
					$title_st = __('Event validated');
					break;
				case 2:
					$img_st = "images/hourglass.png";
					$title_st = __('Event in process');
					break;
			}
			$data[] = html_print_image ($img_st, true, 
				array ("class" => "image_status",
					"width" => 16,
					"title" => $title_st,
					"id" => 'status_img_' . $event["id_evento"]));
			
			$data[] = ui_print_truncate_text(
				io_safe_output($event['evento']),
				140, false, true);
			
			//$data[1] = $event['event_type'];
			$data[] = events_print_type_img ($event["event_type"], true);
			
			if (!empty($event['id_agente']))
				$data[] = agents_get_name($event['id_agente']);
			else
				$data[] = __('Pandora System');
			$data[] = get_priority_name ($event['criticity']);
			if (empty($event['id_usuario']) && $event['estado'] == EVENT_VALIDATE) {
				$data[] = '<i>' . __('System') . '</i>';
			}
			else {
				$user_name = db_get_value ('fullname', 'tusuario', 'id_user', $event['id_usuario']);
				$data[] = io_safe_output($user_name);
			}
			$data[] = '<font style="font-size: 6pt;">' .
				date($config['date_format'], $event['timestamp_rep']) .
				'</font>';
			array_push ($table->data, $data);
		}
		
		if ($html) {
			return html_print_table ($table, $return) . $note;
		}
		else {
			return $table;
		}
	}
	else {
		return false;
	}
}


/** 
 * Get a detailed report of summarized events per agent
 *
 * It construct a table object with all the grouped events happened in an agent
 * during a period of time.
 * 
 * @param mixed Module id to get the report from.
 * @param int Period of time (in seconds) to get the report.
 * @param int Beginning date (unixtime) of the report
 * @param bool Flag to return or echo the report table (echo by default).
 * @param bool Flag to return the html or table object, by default html.
 * 
 * @return mixed A table object (XHTML) or object table is false the html.
 */
function reporting_get_module_detailed_event ($id_modules, $period = 0, $date = 0, $return = false, $html = true) {
	global $config;
	
	$id_modules = (array)safe_int ($id_modules, 1);
	
	if (!is_numeric ($date)) {
		$date = strtotime ($date);
	}
	if (empty ($date)) {
		$date = get_system_time ();
	}
	
	$table->width = '99%';
	$table->data = array ();
	$table->head = array ();
	$table->head[0] = __('Status');
	$table->head[1] = __('Event name');
	$table->head[2] = __('Event type');
	$table->head[3] = __('Criticity');
	$table->head[4] = __('Count');
	$table->head[5] = __('Timestamp');
	$table->style[0] = 'text-align: center;';
	$table->style[4] = 'text-align: center;';
	
	$events = array ();
	
	foreach ($id_modules as $id_module) {
		$event = events_get_module ($id_module, (int) $period, (int) $date);
		if (!empty ($event)) {
			array_push ($events, $event);
		}
	}
	
	if ($events) {
		$note = '';
		if (count($events) >= 1000) {
			$note .= '* ' . __('Maximum of events shown') . ' (1000)<br>';
		}
		foreach ($events as $eventRow) {
			foreach ($eventRow as $k => $event) {
				//$k = count($table->data);
				$table->cellclass[$k][1] = $table->cellclass[$k][2] =
				$table->cellclass[$k][3] = $table->cellclass[$k][4] =
				$table->cellclass[$k][5] =  get_priority_class ($event["criticity"]);
				
				$data = array ();
				
				// Colored box
				switch ($event['estado']) {
					case 0:
						$img_st = "images/star.png";
						$title_st = __('New event');
						break;
					case 1:
						$img_st = "images/tick.png";
						$title_st = __('Event validated');
						break;
					case 2:
						$img_st = "images/hourglass.png";
						$title_st = __('Event in process');
						break;
				}
				$data[0] = html_print_image ($img_st, true, 
					array ("class" => "image_status",
						"width" => 16,
						"title" => $title_st,
						"id" => 'status_img_' . $event["id_evento"]));
						
				$data[1] = io_safe_output($event['evento']);
				$data[2] = $event['event_type'];
				$data[3] = get_priority_name ($event['criticity']);
				$data[4] = $event['event_rep'];
				$data[5] = date($config['date_format'], $event['timestamp_rep']);
				array_push ($table->data, $data);
			}
		}
		
		if ($html) {
			return html_print_table ($table, $return) . $note;
		}
		else {
			return $table;
		}
	}
	else {
		return false;
	}
}

/** 
 * Get a detailed report of the modules of the agent
 * 
 * @param int $id_agent Agent id to get the report for.
 * @param string $filter filter for get partial modules.
 * 
 * @return array An array
 */
function reporting_get_agent_module_info ($id_agent, $filter = false) {
	global $config;
	
	$return = array ();
	$return["last_contact"] = 0; //Last agent contact
	$return["status"] = STATUS_AGENT_NO_DATA;
	$return["status_img"] = ui_print_status_image (STATUS_AGENT_NO_DATA, __('Agent without data'), true);
	$return["alert_status"] = "notfired";
	$return["alert_value"] = STATUS_ALERT_NOT_FIRED;
	$return["alert_img"] = ui_print_status_image (STATUS_ALERT_NOT_FIRED, __('Alert not fired'), true);
	$return["agent_group"] = agents_get_agent_group ($id_agent);
	
	if (!check_acl ($config["id_user"], $return["agent_group"], "AR")) {
		return $return;
	}
	
	if ($filter != '') {
		$filter = 'AND ';
	}
	
	$filter = 'disabled = 0';
	
	$modules = agents_get_modules($id_agent, false, $filter, true, false);
	
	if ($modules === false) {
		return $return;
	}
	
	$now = get_system_time ();
	
	// Get modules status for this agent
	
	$agent = db_get_row ("tagente", "id_agente", $id_agent);

	$return["total_count"] = $agent["total_count"];
	$return["normal_count"] = $agent["normal_count"];
	$return["warning_count"] = $agent["warning_count"];
	$return["critical_count"] = $agent["critical_count"];
	$return["unknown_count"] = $agent["unknown_count"];
	$return["fired_count"] = $agent["fired_count"];
	$return["notinit_count"] = $agent["notinit_count"];
			
	if ($return["total_count"] > 0) {
		if ($return["critical_count"] > 0) {
			$return["status"] = STATUS_AGENT_CRITICAL;
			$return["status_img"] = ui_print_status_image (STATUS_AGENT_CRITICAL, __('At least one module in CRITICAL status'), true);
		}
		else if ($return["warning_count"] > 0) {
			$return["status"] = STATUS_AGENT_WARNING;
			$return["status_img"] = ui_print_status_image (STATUS_AGENT_WARNING, __('At least one module in WARNING status'), true);
		}
		else if ($return["unknown_count"] > 0) {
			$return["status"] = STATUS_AGENT_DOWN;
			$return["status_img"] = ui_print_status_image (STATUS_AGENT_DOWN, __('At least one module is in UKNOWN status'), true);	
		}
		else {
			$return["status"] = STATUS_AGENT_OK;
			$return["status_img"] = ui_print_status_image (STATUS_AGENT_OK, __('All Monitors OK'), true);
		}
	}
	
	//Alert not fired is by default
	if ($return["fired_count"] > 0) {
		$return["alert_status"] = "fired";
		$return["alert_img"] = ui_print_status_image (STATUS_ALERT_FIRED, __('Alert fired'), true);
		$return["alert_value"] = STATUS_ALERT_FIRED;
	}
	elseif (groups_give_disabled_group ($return["agent_group"])) {
		$return["alert_status"] = "disabled";
		$return["alert_value"] = STATUS_ALERT_DISABLED;
		$return["alert_img"] = ui_print_status_image (STATUS_ALERT_DISABLED, __('Alert disabled'), true);
	}
	
	return $return;
}

/**
 *  This is the callback sorting function for SLA values descending
 * 
 *  @param array $a Array element 1 to compare
 *  @param array $b Array element 2 to compare
 * 
 */
function sla_value_desc_cmp($a, $b) {
	// This makes 'Unknown' values the lastest
	if (preg_match('/^(.)*Unknown(.)*$/', $a[5]))
		$a[6] = -1;
	
	if (preg_match('/^(.)*Unknown(.)*$/', $b[5]))
		$b[6] = -1;
	
	return ($a[6] < $b[6])? 1 : 0;
}

/**
 *  This is the callback sorting function for SLA values ascending
 * 
 *  @param array $a Array element 1 to compare
 *  @param array $b Array element 2 to compare
 * 
 */
function sla_value_asc_cmp($a, $b) {
	// This makes 'Unknown' values the lastest
	if (preg_match('/^(.)*Unknown(.)*$/', $a[5]))
		$a[6] = -1;
	
	if (preg_match('/^(.)*Unknown(.)*$/', $b[5]))
		$b[6] = -1;
	
	return ($a[6] > $b[6])? 1 : 0;
}

/**
 * Make the header for each content.
 */
function reporting_header_content($mini, $content, $report, &$table, $title = false, $name = false, $period = false) {
	global $config;
	
	if ($mini) {
		$sizh = '';
		$sizhfin = '';
	}
	else {
		$sizh = '<h4>';
		$sizhfin = '</h4>';
	}
	
	$data = array();
	
	$count_empty = 0;
	
	if ($title !== false) {
		$data[] = $sizh . $title . $sizhfin;
	}
	else $count_empty++;
	
	if ($name !== false) {
		$data[] = $sizh . $name . $sizhfin;
	}
	else $count_empty++;
	
	if ($period !== false && $content['period'] > 0) {
		$data[] = $sizh . $period . $sizhfin;
	}
	else if ($content['period'] == 0) {
		$es = json_decode($content['external_source'], true);
		if ($es['date'] == 0) {
			$date = __('Last data');
		}
		else {
			$date = date($config["date_format"], $es['date']);
		}
		
		$data[] = "<div style='text-align: right;'>" . $sizh . $date . $sizhfin . "</div>";
	}
	else {
		$data[] = "<div style='text-align: right;'>" . $sizh .
			"(" . human_time_description_raw ($content['period']) . ") " .
			__("From:") . " " . date($config["date_format"], $report["datetime"] - $content['period']) . "<br />" .
			__("To:") . " " . date($config["date_format"], $report["datetime"]) . "<br />" .
			$sizhfin . "</div>";
	}
	
	$table->colspan[0][2 - $count_empty] = 1 + $count_empty;
	
	array_push ($table->data, $data);
}

/** 
 * This function is used once, in reporting_viewer.php, the HTML report render
 * file. This function proccess each report item and write the render in the
 * table record.
 * 
 * @param array $content Record of treport_content table for current item
 * @param array $table HTML Table row
 * @param array $report Report contents, with some added fields.
 * @param array $mini Mini flag for reduce the size.
 * 
 */

function reporting_render_report_html_item ($content, $table, $report, $mini = false) {
	global $config;
	global $graphic_type;

	$only_image = (bool)$config['flash_charts'] ? false : true;
	
	if ($mini) {
		$sizem = '1.5';
		$sizgraph_w = '450';
		$sizgraph_h = '100';
	}
	else {
		$sizem = '3';
		$sizgraph_w = '900';
		$sizgraph_h = '230';
	}
	
	// Disable remote connections for netflow report items
	if ($content['type'] != 'netflow_area' &&
		$content['type'] != 'netflow_pie' &&
		$content['type'] != 'netflow_data' &&
		$content['type'] != 'netflow_statistics' &&
		$content['type'] != 'netflow_summary') {
		
		$remote_connection = 1;
	}
	else {
		$remote_connection = 0;
	}
	
	$server_name = $content ['server_name'];
	if (($config ['metaconsole'] == 1) && $server_name != '' && defined('METACONSOLE') && $remote_connection == 1) {
		$connection = metaconsole_get_connection($server_name);
		if (metaconsole_load_external_db($connection) != NOERR) {
			//ui_print_error_message ("Error connecting to ".$server_name);
		}
	}
	
	$module_name = db_get_value ('nombre', 'tagente_modulo', 'id_agente_modulo', $content['id_agent_module']);
	if ($content['id_agent_module'] != 0) {
		$agent_name = modules_get_agentmodule_agent_name ($content['id_agent_module']);
	}
	else {
		$agent_name = agents_get_name($content['id_agent']);
	}
	
	$item_title = $content['name'];
	
	switch ($content["type"]) {
		case 1:
		case 'simple_graph':
			if (empty($item_title)) {
				$item_title = __('Simple graph');
			}
			reporting_header_content($mini, $content, $report, $table, $item_title,
				ui_print_truncate_text($agent_name, 'agent_medium', false).' <br> ' .
				ui_print_truncate_text($module_name, 'module_medium', false));
			
			//RUNNING
			
			$next_row = 1;
			
			// Put description at the end of the module (if exists)
			if ($content["description"] != "") {
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
				$table->colspan[$next_row][0] = 3;
				$next_row++;
			}
			
			$table->colspan[$next_row][0] = 3;
			$table->cellstyle[$next_row][0] = 'text-align: center;';
				
			$data = array ();
			
			$moduletype_name = modules_get_moduletype_name(
				modules_get_agentmodule_type(
					$content['id_agent_module']));

			$only_avg = true;
			// Due to database compatibility problems, the 'only_avg' value
			// is stored into the json contained into the 'style' column.
			if (isset($content['style'])) {
				$style_json = io_safe_output($content['style']);
				$style = json_decode($style_json, true);

				if (isset($style['only_avg'])) {
					$only_avg = (bool) $style['only_avg'];
				}
			}
			
			if (preg_match ("/string/", $moduletype_name)) {
				
				$urlImage = ui_get_full_url(false, false, false, false);
				
				$data[0] = grafico_modulo_string ($content['id_agent_module'], $content['period'],
					false, $sizgraph_w, $sizgraph_h, '', '', false, $only_avg, false,
					$report["datetime"], $only_image, $urlImage);
				
			}
			else {
				
				$data[0] = grafico_modulo_sparse(
					$content['id_agent_module'],
					$content['period'],
					false,
					$sizgraph_w,
					$sizgraph_h,
					'',
					'',
					false,
					$only_avg,
					true,
					$report["datetime"],
					'',
					0,
					0,
					true,
					$only_image,
					ui_get_full_url(false) . '/',
					1,
					false,
					'',
					false,
					true);
			}
			
			array_push ($table->data, $data);
			break;
		case 'projection_graph':
			if (empty($item_title)) {
				$item_title = __('Projection graph');
			}
			reporting_header_content($mini, $content, $report, $table, $item_title,
				ui_print_truncate_text($agent_name, 'agent_medium', false).' <br> ' .
				ui_print_truncate_text($module_name, 'module_medium', false));
			
			//RUNNING
			$table->colspan[1][0] = 4;
			
			set_time_limit(500);
			
			$next_row = 1;
			// Put description at the end of the module (if exists)
			if ($content["description"] != "") {
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
				$table->colspan[$next_row][0] = 3;
				$next_row++;
			}
			
			$table->colspan[$next_row][0] = 3;
			$table->cellstyle[$next_row][0] = 'text-align: center;';
			$data = array ();
			
			$output_projection = forecast_projection_graph($content['id_agent_module'], $content['period'], $content['top_n_value']);
			
			// If projection doesn't have data then don't draw graph
			if ($output_projection ==  NULL) {
				$output_projection = false;
			}
			
			$modules = array($content['id_agent_module']);
			$weights = array();
			$data[0] = 	graphic_combined_module(
				$modules,
				$weights,
				$content['period'],
				$sizgraph_w, $sizgraph_h,
				'Projection%20Sample%20Graph',
				'',
				0,
				0,
				0,
				0,
				$report["datetime"],
				true,
				ui_get_full_url(false) . '/',
				1,
				// Important parameter, this tell to graphic_combined_module function that is a projection graph
				$output_projection,
				$content['top_n_value']
				);
			array_push ($table->data, $data);
			break;
		case 'prediction_date':
			if (empty($item_title)) {
				$item_title = __('Prediction date');
			}
			reporting_header_content($mini, $content, $report, $table, $item_title,
				ui_print_truncate_text($agent_name, 'agent_medium', false).' <br> ' .
				ui_print_truncate_text($module_name, 'module_medium', false));
			
			//RUNNING
			$table->colspan[1][0] = 4;
			
			set_time_limit(500);
			
			// Put description at the end of the module (if exists)
			$table->colspan[2][0] = 4;
			if ($content["description"] != ""){
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			
			$data = array ();
			$table->colspan[2][0] = 3;
			$intervals_text = $content['text'];
			$max_interval = substr($intervals_text, 0, strpos($intervals_text, ';'));
			$min_interval = substr($intervals_text, strpos($intervals_text, ';') + 1);			
			$value = forecast_prediction_date ($content['id_agent_module'], $content['period'],  $max_interval, $min_interval);
			
			if ($value === false) {
				$value = __('Unknown');
			}
			else {
				$value = date ('d M Y H:i:s', $value);
			}
			$data[0] = '<p style="font: bold '.$sizem.'em Arial, Sans-serif; color: #000000;">'.$value.'</p>';
			array_push ($table->data, $data);
			break;
		case 'simple_baseline_graph':
			if (empty($item_title)) {
				$item_title = __('Simple baseline graph');
			}
			reporting_header_content($mini, $content, $report, $table, $item_title,
				ui_print_truncate_text($agent_name, 'agent_medium', false).' <br> ' .
				ui_print_truncate_text($module_name, 'module_medium', false));
			
			//RUNNING
			$table->colspan[1][0] = 4;
			
			// Put description at the end of the module (if exists)
			$table->colspan[2][0] = 4;
			if ($content["description"] != "") {
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			
			$data = array ();
			$data[0] = grafico_modulo_sparse($content['id_agent_module'], $content['period'],
				false, $sizgraph_w, $sizgraph_h, '', '', false, true, true,
				$report["datetime"], '', true, 0, true, $only_image, ui_get_full_url(false) . '/');
			
			/*$data[0] = 	graphic_combined_module(
				$modules,
				$weights,
				$content['period'],
				$sizgraph_w, $sizgraph_h,
				'Combined%20Sample%20Graph',
				'',
				0,
				0,
				0,
				$graph["stacked"],
				$report["datetime"]);	*/
			
			array_push ($table->data, $data);
			
			break;
			
		case 2:
		case 'custom_graph':
		case 'automatic_custom_graph':
			$graph = db_get_row ("tgraph", "id_graph", $content['id_gs']);
			
			if (empty($item_title)) {
				$item_title = __('Custom graph');
			}
			reporting_header_content($mini, $content, $report, $table, $item_title,
				ui_print_truncate_text($graph['name'], 'item_title', false));
			
			//RUNNING
			// Put description at the end of the module (if exists)
			
			$table->colspan[2][0] = 3;
			if ($content["description"] != "") {
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			
			$result = db_get_all_rows_field_filter ("tgraph_source",
				"id_graph", $content['id_gs']);
			$modules = array ();
			$weights = array ();
			if ($result === false)
				$result = array();
			
			foreach ($result as $content2) {
				array_push ($modules, $content2['id_agent_module']);
				array_push ($weights, $content2["weight"]);
			}
			
			// Increase the height to fix the leyend rise
			$sizgraph_h += count($modules) * 15;
			
			$table->colspan[1][0] = 3;
			$data = array();

			require_once ($config["homedir"] . '/include/functions_graph.php');
			$data[0] = 	graphic_combined_module(
				$modules,
				$weights,
				$content['period'],
				$sizgraph_w, $sizgraph_h,
				'Combined%20Sample%20Graph',
				'',
				0,
				0,
				0,
				$graph["stacked"],
				$report["datetime"],
				$only_image,
				ui_get_full_url(false) . '/');
			array_push ($table->data, $data);
			
			break;
		case 'SLA_monthly':
			if (function_exists("reporting_enterprise_sla_monthly"))
				reporting_enterprise_sla_monthly($mini, $content, $report, $table, $item_title);
			break;
		case 'SLA_services':
			if (function_exists("reporting_enterprise_sla_services"))
				reporting_enterprise_sla_services($mini, $content, $report, $table, $item_title);
			break;
		case 3:
		case 'SLA':
			if (empty($item_title)) {
				$item_title = __('S.L.A.');
			}
			reporting_header_content($mini, $content, $report, $table, $item_title);
			
			$edge_interval = 10;
			
			// What show?
			$show_table = $content['show_graph'] == 0 || $content['show_graph'] == 1;
			$show_graphs = $content['show_graph'] == 1 || $content['show_graph'] == 2;
			
			//RUNNING
			$table->style[1] = 'text-align: right';
			
			// Put description at the end of the module (if exists)
			
			$table->colspan[0][1] = 2;
			$next_row = 1;
			if ($content["description"] != "") {
				$table->colspan[$next_row][0] = 3;
				$next_row++;
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			
			$slas = db_get_all_rows_field_filter ('treport_content_sla_combined',
				'id_report_content', $content['id_rc']);
			
			if ($slas === false) {
				$data = array ();
				$table->colspan[$next_row][0] = 3;
				$next_row++;
				$data[0] = __('There are no SLAs defined');
				array_push ($table->data, $data);
				$slas = array ();
				break;
			}
			elseif ($show_table) {
				$table1->width = '99%';
				$table1->data = array ();
				$table1->head = array ();
				$table1->head[0] = __('Agent');
				$table1->head[1] = __('Module');
				$table1->head[2] = __('Max/Min Values');
				$table1->head[3] = __('SLA Limit');
				$table1->head[4] = __('SLA Compliance');
				$table1->head[5] = __('Status');
				// $table1->head[6] = __('Criticity');
				$table1->style[0] = 'text-align: left';
				$table1->style[1] = 'text-align: left';
				$table1->style[2] = 'text-align: right';
				$table1->style[3] = 'text-align: right';
				$table1->style[4] = 'text-align: right';
				$table1->style[5] = 'text-align: right';
				// $table1->style[6] = 'text-align: center';
			}

			// Table Planned Downtimes
			require_once ($config['homedir'] . '/include/functions_planned_downtimes.php');
			$metaconsole_on = ($config['metaconsole'] == 1) && defined('METACONSOLE');
			$downtime_malformed = false;

			$planned_downtimes_empty = true;
			$malformed_planned_downtimes_empty = true;

			if ($metaconsole_on) {
				$id_agent_modules_by_server = array();

				foreach ($slas as $sla) {
					$server = $sla['server_name'];
					if (empty($server))
						continue;

					if (!isset($id_agent_modules_by_server[$server]))
						$id_agent_modules_by_server[$server] = array();
					
					$id_agent_modules_by_server[$server][] = $sla['id_agent_module'];
				}

				$planned_downtimes_by_server = array();
				$malformed_planned_downtimes_by_server = array();
				foreach ($id_agent_modules_by_server as $server => $id_agent_modules) {
					//Metaconsole connection
					if (!empty($server)) {
						$connection = metaconsole_get_connection($server);
						if (!metaconsole_load_external_db($connection)) {
							continue;
						}

						$planned_downtimes_by_server[$server] = reporting_get_planned_downtimes(($report['datetime']-$content['period']), $report['datetime'], $id_agent_modules);
						$malformed_planned_downtimes_by_server[$server] = planned_downtimes_get_malformed();

						if (!empty($planned_downtimes_by_server[$server]))
							$planned_downtimes_empty = false;
						if (!empty($malformed_planned_downtimes_by_server[$server]))
							$malformed_planned_downtimes_empty = false;
						
						//Restore db connection
						metaconsole_restore_db();
					}
				}

				if (!$planned_downtimes_empty) {
					$table_planned_downtimes = new StdClass();
					$table_planned_downtimes->width = '100%';
					$table_planned_downtimes->title = __('This SLA has been affected by the following planned downtimes');
					$table_planned_downtimes->head = array();
					$table_planned_downtimes->head[0] = __('Server');
					$table_planned_downtimes->head[1] = __('Name');
					$table_planned_downtimes->head[2] = __('Description');
					$table_planned_downtimes->head[3] = __('Execution');
					$table_planned_downtimes->head[4] = __('Dates');
					$table_planned_downtimes->headstyle = array();
					$table_planned_downtimes->style = array();
					$table_planned_downtimes->cellstyle = array();
					$table_planned_downtimes->data = array();

					foreach ($planned_downtimes_by_server as $server => $planned_downtimes) {
						foreach ($planned_downtimes as $planned_downtime) {
							$data = array();
							$data[0] = $server;
							$data[1] = $planned_downtime['name'];
							$data[2] = $planned_downtime['description'];
							$data[3] = ucfirst($planned_downtime['type_execution']);
							$data[4] = "";
							switch ($planned_downtime['type_execution']) {
								case 'once':
									$data[3] = date ("Y-m-d H:i", $planned_downtime['date_from']) .
										"&nbsp;" . __('to') . "&nbsp;".
										date ("Y-m-d H:i", $planned_downtime['date_to']);
									break;
								case 'periodically':
									switch ($planned_downtime['type_periodicity']) {
										case 'weekly':
											$data[4] = __('Weekly:');
											$data[4] .= "&nbsp;";
											if ($planned_downtime['monday']) {
												$data[4] .= __('Mon');
												$data[4] .= "&nbsp;";
											}
											if ($planned_downtime['tuesday']) {
												$data[4] .= __('Tue');
												$data[4] .= "&nbsp;";
											}
											if ($planned_downtime['wednesday']) {
												$data[4] .= __('Wed');
												$data[4] .= "&nbsp;";
											}
											if ($planned_downtime['thursday']) {
												$data[4] .= __('Thu');
												$data[4] .= "&nbsp;";
											}
											if ($planned_downtime['friday']) {
												$data[4] .= __('Fri');
												$data[4] .= "&nbsp;";
											}
											if ($planned_downtime['saturday']) {
												$data[4] .= __('Sat');
												$data[4] .= "&nbsp;";
											}
											if ($planned_downtime['sunday']) {
												$data[4] .= __('Sun');
												$data[4] .= "&nbsp;";
											}
											$data[4] .= "&nbsp;(" . $planned_downtime['periodically_time_from']; 
											$data[4] .= "-" . $planned_downtime['periodically_time_to'] . ")";
											break;
										case 'monthly':
											$data[4] = __('Monthly:') . "&nbsp;";
											$data[4] .= __('From day') . "&nbsp;" . $planned_downtime['periodically_day_from'];
											$data[4] .= "&nbsp;" . strtolower(__('To day')) . "&nbsp;";
											$data[4] .= $planned_downtime['periodically_day_to'];
											$data[4] .= "&nbsp;(" . $planned_downtime['periodically_time_from'];
											$data[4] .= "-" . $planned_downtime['periodically_time_to'] . ")";
											break;
									}
									break;
							}

							if (!$malformed_planned_downtimes_empty
									&& isset($malformed_planned_downtimes_by_server[$server])
									&& isset($malformed_planned_downtimes_by_server[$server][$planned_downtime['id']])) {
								$next_row_num = count($table_planned_downtimes->data);
								$table_planned_downtimes->cellstyle[$next_row_num][0] = 'color: red';
								$table_planned_downtimes->cellstyle[$next_row_num][1] = 'color: red';
								$table_planned_downtimes->cellstyle[$next_row_num][2] = 'color: red';
								$table_planned_downtimes->cellstyle[$next_row_num][3] = 'color: red';
								$table_planned_downtimes->cellstyle[$next_row_num][4] = 'color: red';

								if (!$downtime_malformed)
									$downtime_malformed = true;
							}
							
							$table_planned_downtimes->data[] = $data;
						}
					}
				}
			}
			else {
				$id_agent_modules = array();
				foreach ($slas as $sla) {
					if (!empty($sla['id_agent_module']))
						$id_agent_modules[] = $sla['id_agent_module'];
				}

				$planned_downtimes = reporting_get_planned_downtimes(($report['datetime']-$content['period']), $report['datetime'], $id_agent_modules);
				$malformed_planned_downtimes = planned_downtimes_get_malformed();

				if (!empty($planned_downtimes))
					$planned_downtimes_empty = false;
				if (!empty($malformed_planned_downtimes))
					$malformed_planned_downtimes_empty = false;

				if (!$planned_downtimes_empty) {
					$table_planned_downtimes = new StdClass();
					$table_planned_downtimes->width = '100%';
					$table_planned_downtimes->title = __('This SLA has been affected by the following planned downtimes');
					$table_planned_downtimes->head = array();
					$table_planned_downtimes->head[0] = __('Name');
					$table_planned_downtimes->head[1] = __('Description');
					$table_planned_downtimes->head[2] = __('Execution');
					$table_planned_downtimes->head[3] = __('Dates');
					$table_planned_downtimes->headstyle = array();
					$table_planned_downtimes->style = array();
					$table_planned_downtimes->cellstyle = array();
					$table_planned_downtimes->data = array();

					foreach ($planned_downtimes as $planned_downtime) {

						$data = array();
						$data[0] = $planned_downtime['name'];
						$data[1] = $planned_downtime['description'];
						$data[2] = ucfirst($planned_downtime['type_execution']);
						$data[3] = "";
						switch ($planned_downtime['type_execution']) {
							case 'once':
								$data[3] = date ("Y-m-d H:i", $planned_downtime['date_from']) .
									"&nbsp;" . __('to') . "&nbsp;".
									date ("Y-m-d H:i", $planned_downtime['date_to']);
								break;
							case 'periodically':
								switch ($planned_downtime['type_periodicity']) {
									case 'weekly':
										$data[3] = __('Weekly:');
										$data[3] .= "&nbsp;";
										if ($planned_downtime['monday']) {
											$data[3] .= __('Mon');
											$data[3] .= "&nbsp;";
										}
										if ($planned_downtime['tuesday']) {
											$data[3] .= __('Tue');
											$data[3] .= "&nbsp;";
										}
										if ($planned_downtime['wednesday']) {
											$data[3] .= __('Wed');
											$data[3] .= "&nbsp;";
										}
										if ($planned_downtime['thursday']) {
											$data[3] .= __('Thu');
											$data[3] .= "&nbsp;";
										}
										if ($planned_downtime['friday']) {
											$data[3] .= __('Fri');
											$data[3] .= "&nbsp;";
										}
										if ($planned_downtime['saturday']) {
											$data[3] .= __('Sat');
											$data[3] .= "&nbsp;";
										}
										if ($planned_downtime['sunday']) {
											$data[3] .= __('Sun');
											$data[3] .= "&nbsp;";
										}
										$data[3] .= "&nbsp;(" . $planned_downtime['periodically_time_from']; 
										$data[3] .= "-" . $planned_downtime['periodically_time_to'] . ")";
										break;
									case 'monthly':
										$data[3] = __('Monthly:') . "&nbsp;";
										$data[3] .= __('From day') . "&nbsp;" . $planned_downtime['periodically_day_from'];
										$data[3] .= "&nbsp;" . strtolower(__('To day')) . "&nbsp;";
										$data[3] .= $planned_downtime['periodically_day_to'];
										$data[3] .= "&nbsp;(" . $planned_downtime['periodically_time_from'];
										$data[3] .= "-" . $planned_downtime['periodically_time_to'] . ")";
										break;
								}
								break;
						}

						if (!$malformed_planned_downtimes_empty && isset($malformed_planned_downtimes[$planned_downtime['id']])) {
							$next_row_num = count($table_planned_downtimes->data);
							$table_planned_downtimes->cellstyle[$next_row_num][0] = 'color: red';
							$table_planned_downtimes->cellstyle[$next_row_num][1] = 'color: red';
							$table_planned_downtimes->cellstyle[$next_row_num][2] = 'color: red';
							$table_planned_downtimes->cellstyle[$next_row_num][3] = 'color: red';

							if (!$downtime_malformed)
								$downtime_malformed = true;
						}
						
						$table_planned_downtimes->data[] = $data;
					}
				}
			}

			if ($downtime_malformed) {
				$info_malformed = ui_print_error_message(__('This item is affected by a malformed planned downtime') . ". " .
					__('Go to the planned downtimes section to solve this') . ".", '', true);
				
				$data = array();
				$data[0] = $info_malformed;
				$data[0] .= html_print_table($table_planned_downtimes, true);
				$table->colspan[$next_row][0] = 3;
				$next_row++;
				array_push ($table->data, $data);
				break;
			}
			
			$data_graph = array ();
			// $data_horin_graph = array();
			$data_graph[__('Inside limits')] = 0;
			$data_graph[__('Out of limits')] = 0;
			$data_graph[__('On the edge')] = 0;
			$data_graph[__('Unknown')] = 0;
			// $data_horin_graph[__('Inside limits')] = 0;
			// $data_horin_graph[__('Out of limits')] = 0;
			// $data_horin_graph[__('On the edge')] = 0;
			// $data_horin_graph[__('Unknown')] = 0;
			
			$data_graph[__('Plannified downtime')] = 0;
					
			$urlImage = ui_get_full_url(false, true, false, false);

			$sla_failed = false;
			$total_SLA = 0;
			$total_result_SLA = 'ok';
			$sla_showed = array();
			$sla_showed_values = array();
			
			foreach ($slas as $sla) {
				$server_name = $sla ['server_name'];
				//Metaconsole connection
				if (($config ['metaconsole'] == 1) && $server_name != '' && defined('METACONSOLE')) {
					$connection = metaconsole_get_connection($server_name);
					if (!metaconsole_load_external_db($connection)) {
						//ui_print_error_message ("Error connecting to ".$server_name);
						continue;
					}
				}
				
				//Get the sla_value in % and store it on $sla_value
				$sla_value = reporting_get_agentmodule_sla ($sla['id_agent_module'], $content['period'],
				$sla['sla_min'], $sla['sla_max'], $report["datetime"], $content, $content['time_from'],
				$content['time_to']);
				
				if (($config ['metaconsole'] == 1) && defined('METACONSOLE')) {
					//Restore db connection
					metaconsole_restore_db();
				}
				
				//Do not show right modules if 'only_display_wrong' is active
				if ($content['only_display_wrong'] == 1 && $sla_value >= $sla['sla_limit']) continue;
				
				$sla_showed[] = $sla;
				$sla_showed_values[] = $sla_value;
				
			}
			
			// SLA items sorted descending ()
			if ($content['top_n'] == 2) {
				arsort($sla_showed_values);
			}
			// SLA items sorted ascending
			else if ($content['top_n'] == 1) {
				asort($sla_showed_values);
			}
			
			// Slice graphs calculation
			if ($show_graphs && !empty($slas)) {
				$tableslice->width = '99%';
				$tableslice->style[0] = 'text-align: right';
				$tableslice->data = array ();
			}
			
			foreach ($sla_showed_values as $k => $sla_value) {
				$sla = $sla_showed[$k];
				
				$server_name = $sla ['server_name'];
				//Metaconsole connection
				if (($config ['metaconsole'] == 1) && ($server_name != '') && defined('METACONSOLE')) {
					$connection = metaconsole_get_connection($server_name);
					if (metaconsole_connect($connection) != NOERR) {
						//ui_print_error_message ("Error connecting to ".$server_name);
						continue;
					}
				}
				
				//Fill the array data_graph for the pie graph
				// if ($sla_value === false) {
				// 	$data_graph[__('Unknown')]++;
				// 	// $data_horin_graph[__('Unknown')]['g']++;
				// }
				// # Fix : 100% accurance is 'inside limits' although 10% was not overrun
				// else if (($sla_value == 100 && $sla_value >= $sla['sla_limit']) ) {
				// 	$data_graph[__('Inside limits')]++;
				// 	$data_horin_graph[__('Inside limits')]['g']++;
				// }
				// else if ($sla_value <= ($sla['sla_limit']+10) && $sla_value >= ($sla['sla_limit']-10)) {
				// 	$data_graph[__('On the edge')]++;
				// 	$data_horin_graph[__('On the edge')]['g']++;
				// }
				// else if ($sla_value > ($sla['sla_limit']+10)) {
				// 	$data_graph[__('Inside limits')]++;
				// 	$data_horin_graph[__('Inside limits')]['g']++;
				// }
				// else if ($sla_value < ($sla['sla_limit']-10)) {
				// 	$data_graph[__('Out of limits')]++;
				// 	$data_horin_graph[__('Out of limits')]['g']++;
				// }
				
				// if ($sla_value === false) {
				// 	if ($total_result_SLA != 'fail')
				// 		$total_result_SLA = 'unknown';
				// }
				// else if ($sla_value < $sla['sla_limit']) {
				// 	$total_result_SLA = 'fail';
				// }
				
				$total_SLA += $sla_value;
				
				if ($show_table) {
					$data = array ();
					$data[0] = modules_get_agentmodule_agent_name ($sla['id_agent_module']);
					$data[1] = modules_get_agentmodule_name ($sla['id_agent_module']);
					$data[2] = $sla['sla_max'].'/';
					$data[2] .= $sla['sla_min'];
					$data[3] = $sla['sla_limit'].'%';
					
					if ($sla_value === false) {
						$data[4] = '<span style="font: bold '.$sizem.'em Arial, Sans-serif; color: '.COL_UNKNOWN.';">';
						$data[5] = '<span style="font: bold '.$sizem.'em Arial, Sans-serif; color: '.COL_UNKNOWN.';">'.__('Unknown').'</span>';
						// $data[6] = html_print_image('images/status_sets/default/severity_maintenance.png',true,array('title'=>__('Unknown')));
					}
					else {
						$data[4] = '';
						$data[5] = '';
						// $data[6] = '';
						
						if ($sla_value >= $sla['sla_limit']) {
							$data[4] = '<span style="font: bold '.$sizem.'em Arial, Sans-serif; color: '.COL_NORMAL.';">';
							$data[5] = '<span style="font: bold '.$sizem.'em Arial, Sans-serif; color: '.COL_NORMAL.';">'.__('OK').'</span>';
						}
						else {
							$sla_failed = true;
							$data[4] = '<span style="font: bold '.$sizem.'em Arial, Sans-serif; color: '.COL_CRITICAL.';">';
							$data[5] = '<span style="font: bold '.$sizem.'em Arial, Sans-serif; color: '.COL_CRITICAL.';">'.__('Fail').'</span>';
						}
						
						// Print icon with status including edge
						# Fix : 100% accurance is 'inside limits' although 10% was not overrun
						// if (($sla_value == 100 && $sla_value >= $sla['sla_limit']) || ($sla_value > ($sla['sla_limit'] + $edge_interval))) {
						// 	$data[6] = html_print_image('images/status_sets/default/severity_normal.png',true,array('title'=>__('Inside limits')));
						// }
						// elseif (($sla_value <= $sla['sla_limit'] + $edge_interval)
						// 	&& ($sla_value >= $sla['sla_limit'] - $edge_interval)) {
						// 	$data[6] = html_print_image('images/status_sets/default/severity_warning.png',true,array('title'=>__('On the edge')));
						// }
						// else {
						// 	$data[6] = html_print_image('images/status_sets/default/severity_critical.png',true,array('title'=>__('Out of limits')));
						// }
						
						$data[4] .= format_numeric ($sla_value, 2). "%";
					}
					$data[4] .= "</span>";
					
					array_push ($table1->data, $data);
				}
				
				// Slice graphs calculation
				if ($show_graphs) {
					$dataslice = array();
					$dataslice[0] = modules_get_agentmodule_agent_name ($sla['id_agent_module']);
					$dataslice[0] .= "<br>";
					$dataslice[0] .= modules_get_agentmodule_name ($sla['id_agent_module']);
					
					$dataslice[1] = graph_sla_slicebar ($sla['id_agent_module'], $content['period'],
						$sla['sla_min'], $sla['sla_max'], $report['datetime'], $content, $content['time_from'],
						$content['time_to'], 650, 25, $urlImage, 1, false, false);
					
					array_push ($tableslice->data, $dataslice);
				}
				
				if ($config ['metaconsole'] == 1 && defined('METACONSOLE')) {
					//Restore db connection
					metaconsole_restore_db();
				}
			} 
			
			if ($show_table) {
				$data = array();
				$data[0] = html_print_table($table1, true);
				$table->colspan[$next_row][0] = 3;
				$next_row++;
				array_push ($table->data, $data);
			}
			
			// $data = array();
			// $data_pie_graph = json_encode ($data_graph);
			if ($show_graphs && !empty($slas)) {
				// $data[0] = pie3d_graph(false, $data_graph,
				// 	500, 150, __("other"),
				// 	ui_get_full_url(false, false, false, false),
				// 	$config['homedir'] .  "/images/logo_vertical_water.png",
				// 	$config['fontpath'], $config['font_size']);
				
				
				// //Print resume
				// $table_resume = null;
				// $table_resume->head[0] = __('Average Value');
				
				// $table_resume->data[0][0] = '<span style="font: bold '.$sizem.'em Arial, Sans-serif; color: #000000;">';
				// $table_resume->data[0][0] .= format_numeric($total_SLA / count($sla_showed), 2);
				// $table_resume->data[0][0] .= "%</span>";
				
				// $data[1] = html_print_table($table_resume, true);
				
				// $table_resume = null;
				// $table_resume->head[0] = __('SLA Compliance');
				
				// if ($total_result_SLA == 'ok') {
				// 	$table_resume->data[0][0] = '<span style="font: bold '.$sizem.'em Arial, Sans-serif; color: #000000;">'.__('OK').'</span>';
				// }
				// if ($total_result_SLA == 'fail') {
				// 	$table_resume->data[0][0] = '<span style="font: bold '.$sizem.'em Arial, Sans-serif; color: #ff0000;">'.__('Fail').'</span>';
				// }
				// if ($total_result_SLA == 'unknown') {
				// 	$table_resume->data[0][0] = '<span style="font: bold '.$sizem.'em Arial, Sans-serif; color: #736F6E;">'.__('Unknown').'</span>';
				// }
				
				// $data[2] = html_print_table($table_resume, true);
				// $next_row++;
				// array_push ($table->data, $data);
				
				$table->colspan[$next_row][0] = 3;
				$next_row++;
				$data = array();
				$data[0] = html_print_table($tableslice, true);
				array_push ($table->data, $data);
			}

			if (!empty($table_planned_downtimes)) {
				$data = array();
				$data[0] = html_print_table($table_planned_downtimes, true);
				$table->colspan[$next_row][0] = 3;
				$next_row++;
				array_push ($table->data, $data);
			}
			break;
		case 6:
		case 'monitor_report':
			if (empty($item_title)) {
				$item_title = __('Monitor report');
			}
			reporting_header_content($mini, $content, $report, $table, $item_title,
				ui_print_truncate_text($agent_name, 'agent_medium', false) .
				' <br> '.ui_print_truncate_text($module_name, 'module_medium', false));
			
			//RUNNING
			$next_row = 1;
			
			// Put description at the end of the module (if exists)
			if ($content["description"] != "") {
				$table->colspan[1][0] = 3;
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
				$next_row++;
			}
			
			$data = array ();
			$monitor_value = reporting_get_agentmodule_sla ($content['id_agent_module'], $content['period'], 1, false, $report["datetime"]);
			if ($monitor_value === false) {
				$monitor_value = __('Unknown');
			}
			else {
				$monitor_value = format_numeric ($monitor_value);
			}
			
			$table->colspan[$next_row][0] = 2;
			$data[0] = '<p style="font: bold '.$sizem.'em Arial, Sans-serif; color: ' . COL_NORMAL . ';">';
			$data[0] .= html_print_image("images/module_ok.png", true) . ' ' . __('OK') . ': ' . $monitor_value.' %</p>';
			if ($monitor_value !== __('Unknown')) {
				$monitor_value = format_numeric (100 - $monitor_value, 2) ;
			}
			$data[1] = '<p style="font: bold '.$sizem.'em Arial, Sans-serif; color: ' . COL_CRITICAL . ';">';
			$data[1] .= html_print_image("images/module_critical.png", true) . ' ' .__('Not OK') . ': ' .$monitor_value.' % ' . '</p>';
			array_push ($table->data, $data);
			
			break;
		case 7:
		case 'avg_value':
			if (empty($item_title)) {
				$item_title = __('Avg. Value');
			}
			reporting_header_content($mini, $content, $report, $table, $item_title,
				ui_print_truncate_text($agent_name, 'agent_medium', false) .
				' <br> '.ui_print_truncate_text($module_name, 'module_medium', false));
			
			//RUNNING
			
			// Put description at the end of the module (if exists)
			$table->colspan[1][0] = 3;
			if ($content["description"] != "") {
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			
			$data = array ();
			$table->colspan[2][0] = 3;
			$unit = db_get_value('unit', 'tagente_modulo', 'id_agente_modulo', $content['id_agent_module']); 
			$value = reporting_get_agentmodule_data_average ($content['id_agent_module'], $content['period'], $report["datetime"]);
			if ($value === false) {
				$value = __('Unknown');
			}
			else {
				$value = format_for_graph($value, 2) . " " . $unit;
			}
			$data[0] = '<p style="font: bold '.$sizem.'em Arial, Sans-serif; color: #000000;">'.$value.'</p>';
			array_push ($table->data, $data);
			
			break;
		case 8:
		case 'max_value':
			if (empty($item_title)) {
				$item_title = __('Max. Value');
			}
			reporting_header_content($mini, $content, $report, $table, $item_title,
				ui_print_truncate_text($agent_name, 'agent_medium', false) .
				' <br> ' . ui_print_truncate_text($module_name, 'module_medium', false));
			
			//RUNNING
			
			// Put description at the end of the module (if exists)
			$table->colspan[1][0] = 3;
			if ($content["description"] != ""){
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			
			$data = array ();
			$table->colspan[2][0] = 3;
			
			$value = reporting_get_agentmodule_data_max ($content['id_agent_module'], $content['period'], $report["datetime"]);
			
			$unit = db_get_value('unit', 'tagente_modulo', 'id_agente_modulo', $content ['id_agent_module']);
			$data[0] = '<p style="font: bold '.$sizem.'em Arial, Sans-serif; color: #000000;">' .
				format_for_graph($value, 2) . " " . $unit .'</p>';
			array_push ($table->data, $data);
			
			break;
		case 9:
		case 'min_value':
			if (empty($item_title)) {
				$item_title = __('Min. Value');
			}
			reporting_header_content($mini, $content, $report, $table, $item_title,
				ui_print_truncate_text($agent_name, 'agent_medium', false) .
				' <br> '.ui_print_truncate_text($module_name, 'module_medium', false));
			
			//RUNNING
			
			// Put description at the end of the module (if exists)
			$table->colspan[1][0] = 3;
			if ($content["description"] != "") {
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			
			$data = array ();
			$table->colspan[2][0] = 3;
			
			$value = reporting_get_agentmodule_data_min ($content['id_agent_module'], $content['period'], $report["datetime"]);
			$unit = db_get_value('unit', 'tagente_modulo', 'id_agente_modulo', $content ['id_agent_module']);
			if ($value === false) {
				$value = __('Unknown');
			}
			else {
				$value = format_for_graph($value, 2) . " " . $unit;
			}
			$data[0] = '<p style="font: bold '.$sizem.'em Arial, Sans-serif; color: #000000;">'.$value.'</p>';
			array_push ($table->data, $data);
			
			break;
		case 10:
		case 'sumatory':
			if (empty($item_title)) {
				$item_title = __('Summatory');
			}
			reporting_header_content($mini, $content, $report, $table, $item_title,
				ui_print_truncate_text($agent_name, 'agent_medium', false) .
				' <br> ' . ui_print_truncate_text($module_name, 'module_medium', false));
			
			//RUNNING
			
			$next_row = 1;
			
			// Put description at the end of the module (if exists)
			if ($content["description"] != "") {
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
				$table->colspan[$next_row][0] = 3;
				$next_row++;
			}
			
			$table->colspan[$next_row][0] = 3;

			$data = array ();
			$unit = db_get_value('unit', 'tagente_modulo', 'id_agente_modulo', $content['id_agent_module']);
			
			$value = reporting_get_agentmodule_data_sum ($content['id_agent_module'], $content['period'], $report["datetime"]);
			if ($value === false) {
				$value = __('Unknown');
			}
			else {
				$value = format_for_graph($value, 2) . " " . $unit;
			}
			
			$data[0] = '<p style="font: bold '.$sizem.'em Arial, Sans-serif; color: #000000;">'.$value.'</p>';
			array_push ($table->data, $data);
			
			break;
		case 'agent_detailed_event':
		case 'event_report_agent':
			if (empty($item_title)) {
				$item_title = __('Agent detailed event');
			}
			reporting_header_content($mini, $content, $report, $table, $item_title,
				ui_print_truncate_text(agents_get_name($content['id_agent']), 'agent_medium', false));
			
			$style = json_decode(io_safe_output($content['style']), true);
			
			$filter_event_no_validated = $style['filter_event_no_validated'];
			$filter_event_validated = $style['filter_event_validated'];
			$filter_event_critical = $style['filter_event_critical'];
			$filter_event_warning = $style['filter_event_warning'];
			
			$event_graph_by_agent = $style['event_graph_by_agent'];
			$event_graph_by_user_validator = $style['event_graph_by_user_validator'];
			$event_graph_by_criticity = $style['event_graph_by_criticity'];
			$event_graph_validated_vs_unvalidated = $style['event_graph_validated_vs_unvalidated'];
			
			$next_row = 1;
			
			// Put description at the end of the module (if exists)
			if ($content["description"] != "") {
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
				$table->colspan[$next_row][0] = 3;
				$next_row++;
			}
			
			$data = array ();
			$table->colspan[$next_row][0] = 3;
			$next_row++;
			$data[0] = reporting_get_agents_detailed_event(
				$content['id_agent'], $content['period'],
				$report["datetime"], true,
				$filter_event_validated,
				$filter_event_critical,
				$filter_event_warning,
				$filter_event_no_validated);
			
			if(!empty($data[0])) {
				array_push ($table->data, $data);
								
				$table->colspan[$next_row][0] = 3;
				$next_row++;
			}
			
			
			if ($event_graph_by_user_validator) {
				$data_graph = reporting_get_count_events_validated_by_user(
					array('id_agent' => $content['id_agent']), $content['period'],
					$report["datetime"],
					$filter_event_validated,
					$filter_event_critical,
					$filter_event_warning,
					$filter_event_no_validated);
				
				$table_event_graph = null;
				$table_event_graph->width = '100%';
				$table_event_graph->style[0] = 'text-align: center;';
				$table_event_graph->head[0] = __('Events validated by user');
				
				$table_event_graph->data[0][0] = pie3d_graph(
					false, $data_graph, 500, 150, __("other"), "",
					$config['homedir'] .  "/images/logo_vertical_water.png",
					$config['fontpath'], $config['font_size']);
				
				$data[0] = html_print_table($table_event_graph, true);
				
				$table->colspan[$next_row][0] = 3;
				$next_row++;
				array_push ($table->data, $data);
			}
			
			if ($event_graph_by_criticity) {
				$data_graph = reporting_get_count_events_by_criticity(
					array('id_agent' => $content['id_agent']), $content['period'],
					$report["datetime"],
					$filter_event_validated,
					$filter_event_critical,
					$filter_event_warning,
					$filter_event_no_validated);
						
				$colors = get_criticity_pie_colors($data_graph);
				
				$table_event_graph = null;
				$table_event_graph->width = '100%';
				$table_event_graph->style[0] = 'text-align: center;';
				$table_event_graph->head[0] = __('Events by criticity');
				
				$table_event_graph->data[0][0] = pie3d_graph(
					false, $data_graph, 500, 150, __("other"), "",
					$config['homedir'] .  "/images/logo_vertical_water.png",
					$config['fontpath'], $config['font_size'], 1, false, $colors);
				
				$data[0] = html_print_table($table_event_graph, true);
				
				$table->colspan[$next_row][0] = 3;
				$next_row++;
				array_push ($table->data, $data);
			}
			
			if ($event_graph_validated_vs_unvalidated) {
				$data_graph = reporting_get_count_events_validated(
					array('id_agent' => $content['id_agent']), $content['period'],
					$report["datetime"],
					$filter_event_validated,
					$filter_event_critical,
					$filter_event_warning,
					$filter_event_no_validated);
				
				$table_event_graph = null;
				$table_event_graph->width = '100%';
				$table_event_graph->style[0] = 'text-align: center;';
				$table_event_graph->head[0] = __('Amount events validated');
				
				$table_event_graph->data[0][0] = pie3d_graph(
					false, $data_graph, 500, 150, __("other"), "",
					$config['homedir'] .  "/images/logo_vertical_water.png",
					$config['fontpath'], $config['font_size']);
				
				$data[0] = html_print_table($table_event_graph, true);
				
				$table->colspan[$next_row][0] = 3;
				$next_row++;
				array_push ($table->data, $data);
			}
			break;
		case 'text':
			if (empty($item_title)) {
				$item_title = __('Text');
			}
			reporting_header_content($mini, $content, $report, $table, $item_title,
				"", "");
			
			$next_row = 1;
			
			if ($content["description"] != ""){
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
				$table->colspan[$next_row][0] = 3;
				$next_row++;
			}
			$data[0] = html_entity_decode($content['text']);
			array_push($table->data, $data);
			$table->colspan[$next_row][0] = 3;
			break;
		case 'sql':
			if (empty($item_title)) {
				$item_title = __('SQL');
			}
			reporting_header_content($mini, $content, $report, $table, $item_title,
				"", "");
			
			$next_row = 1;
			// Put description at the end of the module (if exists)
			if ($content["description"] != ""){
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
				
				$table->colspan[$next_row][0] = 3;
				$next_row++;
			}
			
			$table->colspan[$next_row][0] = 3;

			$table2->class = 'databox';
			$table2->width = '100%';
			
			//Create the head
			$table2->head = array();
			if ($content['header_definition'] != '') {
				$table2->head = explode('|', $content['header_definition']);
			}
			
			if ($content['treport_custom_sql_id'] != 0) {
				switch ($config["dbtype"]) {
					case "mysql":
						$sql = io_safe_output (db_get_value_filter('`sql`', 'treport_custom_sql', array('id' => $content['treport_custom_sql_id'])));
						break;
					case "postgresql":
						$sql = io_safe_output (db_get_value_filter('"sql"', 'treport_custom_sql', array('id' => $content['treport_custom_sql_id'])));
						break;
					case "oracle":
						$sql = io_safe_output (db_get_value_filter('sql', 'treport_custom_sql', array('id' => $content['treport_custom_sql_id'])));
						break;
				}
			}
			else {
				$sql = io_safe_output ($content['external_source']);
			}
			
			// Do a security check on SQL coming from the user
			$sql = check_sql ($sql);
			
			if ($sql != '') {
				$result = db_get_all_rows_sql($sql);
				if ($result === false) {
					$result = array();
				}
				
				if (isset($result[0])) {
					if (count($result[0]) > count($table2->head)) {
						$table2->head = array_pad($table2->head, count($result[0]), '&nbsp;');
					}
				}
				
				$table2->data = array();
				foreach ($result as $row) {
					array_push($table2->data, $row);
				}
			}
			else {
				$table2->data = array();
				array_push($table2->data, array("id_user" => "<div class='nf'>[".__('Illegal query')."]<br>".
				__('Due security restrictions, there are some tokens or words you cannot use').
				': *, delete, drop, alter, modify, union, password, pass, insert '.__('or')." update.</div>"));
			}
			
			$cellContent = html_print_table($table2, true);
			array_push($table->data, array($cellContent));
			break;
		case 'sql_graph_vbar':
		case 'sql_graph_hbar':
		case 'sql_graph_pie':
			$sizgraph_h = 300;
			
			if ($content['type'] == 'sql_graph_vbar') {
				$sizgraph_h = 400;
			}
			
			if ($config['metaconsole'] == 1 && defined('METACONSOLE'))
				metaconsole_restore_db();
			
			if (empty($item_title)) {
				$item_title = __('User defined graph') . " (".__($content["type"])  .")";
			}
			reporting_header_content($mini, $content, $report, $table, $item_title,
				"", "");
			
			// Put description at the end of the module (if exists)
			$next_row = 1;
			if ($content["description"] != "") {
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
				$table->colspan[$next_row][0] = 3;
				$next_row++;
			}
			
			$table->colspan[$next_row][0] = 3;
			
			$table2->class = 'databox';
			$table2->width = '100%';
			
			//Create the head
			$table2->head = array();
			if ($content['header_definition'] != '') {
				$table2->head = explode('|', $content['header_definition']);
			}
			
			$data = array ();
			
			$data[0] = graph_custom_sql_graph($content["id_rc"], $sizgraph_w, $sizgraph_h, $content["type"], true, ui_get_full_url(false) . '/');
			
			array_push($table->data, $data);
			break;
		case 'event_report_group':
			if (empty($item_title)) {
				$item_title = __('Group detailed event');
			}
			reporting_header_content($mini, $content, $report, $table, $item_title,
				ui_print_truncate_text(groups_get_name($content['id_group'], true), 60, false));
				
			$next_row = 1;
			
			// Put description at the end of the module (if exists)
			if ($content["description"] != "") {
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
				$table->colspan[$next_row][0] = 3;
				$next_row++;
			}
			
			$data = array ();
			
			$style = json_decode(io_safe_output($content['style']), true);
			
			$filter_event_no_validated = $style['filter_event_no_validated'];
			$filter_event_validated = $style['filter_event_validated'];
			$filter_event_critical = $style['filter_event_critical'];
			$filter_event_warning = $style['filter_event_warning'];
			
			$event_graph_by_agent = $style['event_graph_by_agent'];
			$event_graph_by_user_validator = $style['event_graph_by_user_validator'];
			$event_graph_by_criticity = $style['event_graph_by_criticity'];
			$event_graph_validated_vs_unvalidated = $style['event_graph_validated_vs_unvalidated'];
			
			$data[0] = reporting_get_group_detailed_event(
				$content['id_group'], $content['period'],
				$report["datetime"], true, true,
				$filter_event_validated,
				$filter_event_critical,
				$filter_event_warning,
				$filter_event_no_validated);
			if(!empty($data[0])) {
				array_push ($table->data, $data);
								
				$table->colspan[$next_row][0] = 3;
				$next_row++;
			}
			
			if ($event_graph_by_agent) {
				$data_graph = reporting_get_count_events_by_agent(
					$content['id_group'], $content['period'],
					$report["datetime"],
					$filter_event_validated,
					$filter_event_critical,
					$filter_event_warning,
					$filter_event_no_validated);
				
				$table_event_graph = null;
				$table_event_graph->width = '100%';
				$table_event_graph->style[0] = 'text-align: center;';
				$table_event_graph->head[0] = __('Events by agent');
				
				$table_event_graph->data[0][0] = pie3d_graph(
					false, $data_graph, 500, 150, __("other"), "",
					$config['homedir'] .  "/images/logo_vertical_water.png",
					$config['fontpath'], $config['font_size']);
				
				$data[0] = html_print_table($table_event_graph, true);
				
				$table->colspan[$next_row][0] = 3;
				$next_row++;
				array_push ($table->data, $data);
			}
			
			if ($event_graph_by_user_validator) {
				$data_graph = reporting_get_count_events_validated_by_user(
					array('id_group' => $content['id_group']), $content['period'],
					$report["datetime"],
					$filter_event_validated,
					$filter_event_critical,
					$filter_event_warning,
					$filter_event_no_validated);
				
				$table_event_graph = null;
				$table_event_graph->head[0] = __('Events validated by user');
				$table_event_graph->width = '100%';
				$table_event_graph->style[0] = 'text-align: center;';
				
				$table_event_graph->data[0][0] = pie3d_graph(
					false, $data_graph, 500, 150, __("other"), "",
					$config['homedir'] .  "/images/logo_vertical_water.png",
					$config['fontpath'], $config['font_size']);
				
				$data[0] = html_print_table($table_event_graph, true);
				
				$table->colspan[$next_row][0] = 3;
				$next_row++;
				array_push ($table->data, $data);
			}
			
			if ($event_graph_by_criticity) {
				$data_graph = reporting_get_count_events_by_criticity(
					array('id_group' => $content['id_group']), $content['period'],
					$report["datetime"],
					$filter_event_validated,
					$filter_event_critical,
					$filter_event_warning,
					$filter_event_no_validated);
								
				$colors = get_criticity_pie_colors($data_graph);

				$table_event_graph = null;
				$table_event_graph->head[0] = __('Events by criticity');
				$table_event_graph->width = '100%';
				$table_event_graph->style[0] = 'text-align: center;';
				
				$table_event_graph->data[0][0] = pie3d_graph(
					false, $data_graph, 500, 150, __("other"), "",
					$config['homedir'] .  "/images/logo_vertical_water.png",
					$config['fontpath'], $config['font_size'], 1, false, $colors);
				
				$data[0] = html_print_table($table_event_graph, true);
				
				$table->colspan[$next_row][0] = 3;
				$next_row++;
				array_push ($table->data, $data);
			}
			
			if ($event_graph_validated_vs_unvalidated) {
				$data_graph = reporting_get_count_events_validated(
					array('id_group' => $content['id_group']), $content['period'],
					$report["datetime"],
					$filter_event_validated,
					$filter_event_critical,
					$filter_event_warning,
					$filter_event_no_validated);
				
				$table_event_graph = null;
				$table_event_graph->head[0] = __('Amount events validated');
				$table_event_graph->width = '100%';
				$table_event_graph->style[0] = 'text-align: center;';
				
				$table_event_graph->data[0][0] = pie3d_graph(
					false, $data_graph, 500, 150, __("other"), "",
					$config['homedir'] .  "/images/logo_vertical_water.png",
					$config['fontpath'], $config['font_size']);
				
				$data[0] = html_print_table($table_event_graph, true);
				
				$table->colspan[$next_row][0] = 3;
				$next_row++;
				array_push ($table->data, $data);
			}
			break;
		case 'event_report_module':
			if (empty($item_title)) {
				$item_title = __('Module detailed event');
			}
			reporting_header_content($mini, $content, $report, $table, $item_title,
				ui_print_truncate_text($agent_name, 'agent_medium', false) .
				' <br> ' . ui_print_truncate_text($module_name, 'module_medium', false));
			
			// Put description at the end of the module (if exists)
			$table->colspan[1][0] = 3;
			if ($content["description"] != "") {
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			
			$data = array ();
			$table->colspan[2][0] = 3;
			$data[0] = reporting_get_module_detailed_event($content['id_agent_module'], $content['period'], $report["datetime"], true);
			array_push ($table->data, $data);
			break;
		case 'alert_report_group':
			if (empty($item_title)) {
				$item_title = __('Alert report group');
			}
			reporting_header_content($mini, $content, $report, $table, $item_title,
				ui_print_truncate_text(
					groups_get_name($content['id_group'], true),
				60, false));
			
			// Put description at the end of the module (if exists)
			$table->colspan[1][0] = 3;
			if ($content["description"] != "") {
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			
			$data = array ();
			$table->colspan[2][0] = 3;
			$data[0] = reporting_alert_reporting_group(
				$content['id_group'], $content['period'],
				$report["datetime"], true);
			array_push ($table->data, $data);
			break;
		case 'alert_report_module':
			if (empty($item_title)) {
				$item_title = __('Alert report module');
			}
			reporting_header_content($mini, $content, $report, $table, $item_title,
				ui_print_truncate_text($agent_name, 'agent_medium', false) .
				' <br> '.ui_print_truncate_text($module_name, 'module_medium', false));
			
			// Put description at the end of the module (if exists)
			$table->colspan[1][0] = 3;
			if ($content["description"] != "") {
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			
			$data = array ();
			$table->colspan[2][0] = 3;
			$data[0] = reporting_alert_reporting_module ($content['id_agent_module'], $content['period'], $report["datetime"], true);
			array_push ($table->data, $data);
			break;
		case 'alert_report_agent':
			if (empty($item_title)) {
				$item_title = __('Alert report agent');
			}
			reporting_header_content($mini, $content, $report, $table, $item_title,
				ui_print_truncate_text($agent_name, 'agent_medium', false));
			
			// Put description at the end of the module (if exists)
			$table->colspan[1][0] = 3;
			if ($content["description"] != "") {
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			
			$data = array ();
			$table->colspan[2][0] = 3;
			$data[0] = reporting_alert_reporting_agent ($content['id_agent'], $content['period'], $report["datetime"], true);
			array_push ($table->data, $data);
			break;
		case 'url':
			if (empty($item_title)) {
				$item_title = __('Import text from URL');
			}
			reporting_header_content($mini, $content, $report, $table, $item_title,
				ui_print_truncate_text($content["external_source"], 'description', false));
			
			$next_row = 1;
			// Put description at the end of the module (if exists)
			if ($content["description"] != "") {
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
				$table->colspan[$next_row][0] = 3;
				$next_row++;
			}
			
			$data = array();
			$table->colspan[$next_row][0] = 3;
			$data[0] = '<iframe id="item_' . $content['id_rc'] . '" src ="' . $content["external_source"] . '" width="100%" height="100%"></iframe>';
			// TODO: make this dynamic and get the height if the iframe to resize this item
			$data[0] .= '<script>
				$(document).ready (function () {
					$("#item_' . $content['id_rc'] . '").height(500);
			});</script>';
			
			array_push ($table->data, $data);
			break;
		case 'database_serialized':
			if (empty($item_title)) {
				$item_title = __('Serialize data');
			}
			reporting_header_content($mini, $content, $report, $table, $item_title,
				ui_print_truncate_text($module_name, 'module_medium', false));
			
			// Put description at the end of the module (if exists)
			$next_row = 1;
			if ($content["description"] != "") {
				$data_desc = array();
				$data_desc[0] = $content["description"];
				$table->colspan[$next_row][0] = 3;
				array_push ($table->data, $data_desc);
				$next_row++;
			}
			
			$table->colspan[$next_row][0] = 3;
			
			$table2->class = 'databox alternate';
			$table2->width = '100%';
			
			//Create the head
			$table2->head = array();
			if ($content['header_definition'] != '') {
				$table2->head = explode('|', $content['header_definition']);
			}
			else {
				$table2->head[] = __('Data');
			}
			array_unshift($table2->head, __('Date'));
			
			$datelimit = $report["datetime"] - $content['period'];
			$search_in_history_db = db_search_in_history_db($datelimit);
			
			// This query gets information from the default and the historic database
			$result = db_get_all_rows_sql('SELECT *
				FROM tagente_datos
				WHERE id_agente_modulo = ' . $content['id_agent_module'] . '
					AND utimestamp > ' . $datelimit . '
					AND utimestamp <= ' . $report["datetime"], $search_in_history_db);
			
			// Adds string data if there is no numeric data	
			if ((count($result) < 0) or (!$result)) {
				// This query gets information from the default and the historic database
				$result = db_get_all_rows_sql('SELECT *
					FROM tagente_datos_string
					WHERE id_agente_modulo = ' . $content['id_agent_module'] . '
						AND utimestamp > ' . $datelimit . '
						AND utimestamp <= ' . $report["datetime"], $search_in_history_db);
			} 
			if ($result === false) {
				$result = array();
			}
			
			$table2->data = array();
			foreach ($result as $row) {
				$date = date ($config["date_format"], $row['utimestamp']);
				$serialized = $row['datos'];
				if (empty($content['line_separator']) ||
					empty($serialized)) {
						$rowsUnserialize = array($row['datos']);
				}
				else {
					$rowsUnserialize = explode($content['line_separator'], $serialized);
				}
				foreach ($rowsUnserialize as $rowUnser) {
					if (empty($content['column_separator'])) {
						$columnsUnserialize = array($rowUnser);
					}
					else {
						$columnsUnserialize = explode($content['column_separator'], $rowUnser);
					}
					
					array_unshift($columnsUnserialize, $date);
					array_push($table2->data, $columnsUnserialize);
				} 
			}
			
			$cellContent = html_print_table($table2, true);
			array_push($table->data, array($cellContent));
			break;
		case 'TTRT':
			if (empty($item_title)) {
				$item_title = __('TTRT');
			}
			reporting_header_content($mini, $content, $report, $table, $item_title,
				ui_print_truncate_text($agent_name, 'agent_medium', false) .
				' <br> '.ui_print_truncate_text($module_name, 'module_medium', false));
			
			// Put description at the end of the module (if exists)
			$table->colspan[1][0] = 3;
			if ($content["description"] != "") {
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			
			$data = array ();
			$table->colspan[2][0] = 3;
			$ttr = reporting_get_agentmodule_ttr ($content['id_agent_module'], $content['period'], $report["datetime"]);
			if ($ttr === false) {
				$ttr = __('Unknown');
			}
			else if ($ttr != 0) {
				$ttr = human_time_description_raw ($ttr);
			}
			
			$data = array ();
			$table->colspan[2][0] = 3;
			$data[0] = '<p style="font: bold '.$sizem.'em Arial, Sans-serif; color: #000000;">'.$ttr.'</p>';
			array_push ($table->data, $data);
			break;
		case 'TTO':
			if (empty($item_title)) {
				$item_title = __('TTO');
			}
			reporting_header_content($mini, $content, $report, $table, $item_title,
				ui_print_truncate_text($agent_name, 'agent_medium', false) .
				' <br> '.ui_print_truncate_text($module_name, 'module_medium', false));
			
			// Put description at the end of the module (if exists)
			$table->colspan[1][0] = 3;
			if ($content["description"] != "") {
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			
			$data = array ();
			$table->colspan[2][0] = 3;
			$tto = reporting_get_agentmodule_tto ($content['id_agent_module'],
				$content['period'], $report["datetime"]);
			if ($tto === false) {
				$tto = __('Unknown');
			}
			else if ($tto != 0) {
				$tto = human_time_description_raw ($tto);
			}
			
			$data = array ();
			$table->colspan[2][0] = 3;
			$data[0] = '<p style="font: bold '.$sizem.'em Arial, Sans-serif; color: #000000;">'.$tto.'</p>';
			array_push ($table->data, $data);
			break;
		case 'MTBF':
			if (empty($item_title)) {
				$item_title = __('MTBF');
			}
			reporting_header_content($mini, $content, $report, $table, $item_title,
				ui_print_truncate_text($agent_name, 'agent_medium', false) .
				' <br> '.ui_print_truncate_text($module_name, 'module_medium', false));
			
			// Put description at the end of the module (if exists)
			$table->colspan[1][0] = 3;
			if ($content["description"] != "") {
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			
			$data = array ();
			$table->colspan[2][0] = 3;
			$mtbf = reporting_get_agentmodule_mtbf ($content['id_agent_module'], $content['period'], $report["datetime"]);
			if ($mtbf === false) {
				$mtbf = __('Unknown');
			}
			else if ($mtbf != 0) {
				$mtbf = human_time_description_raw ($mtbf);
			}
			
			$data = array ();
			$table->colspan[2][0] = 3;
			$data[0] = '<p style="font: bold '.$sizem.'em Arial, Sans-serif; color: #000000;">'.$mtbf.'</p>';
			array_push ($table->data, $data);
			break;
		case 'MTTR':
			if (empty($item_title)) {
				$item_title = __('MTTR');
			}
			reporting_header_content($mini, $content, $report, $table, $item_title,
				ui_print_truncate_text($agent_name, 'agent_medium', false) .
				' <br> '.ui_print_truncate_text($module_name, 'module_medium', false));
			
			// Put description at the end of the module (if exists)
			$table->colspan[1][0] = 3;
			if ($content["description"] != "") {
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			
			$data = array ();
			$table->colspan[2][0] = 3;
			$mttr = reporting_get_agentmodule_mttr ($content['id_agent_module'], $content['period'], $report["datetime"]);
			if ($mttr === false) {
				$mttr = __('Unknown');
			}
			else if ($mttr != 0) {
				$mttr = human_time_description_raw ($mttr);
			}
			
			$data = array ();
			$table->colspan[2][0] = 3;
			$data[0] = '<p style="font: bold '.$sizem.'em Arial, Sans-serif; color: #000000;">'.$mttr.'</p>';
			array_push ($table->data, $data);
			break;
		case 'group_report':
			$group_name = groups_get_name($content['id_group'], true);
			$group_stats = reporting_get_group_stats($content['id_group']);
			// Get events of the last 8 hours
			$events = events_get_group_events ($content['id_group'], 28800, $report['datetime']);
			
			if ($events === false) {
				$events = array();
			}
			
			if (empty($item_title)) {
				$item_title = __('Group report').': "'.$group_name.'"';
			}
			reporting_header_content($mini, $content, $report, $table, $item_title);
			
			$table->colspan[1][0] = 3;
			
			if ($content["description"] != "") {
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			
			$table->colspan[2][0] = 3;
			
			$table->data[2][0] = 
				"<table width='100%'>
					<tr>
						<td></td>
						<td colspan='3'><div class='cellBold cellCenter'>" .
							__('Total') . "</div></td>
						<td colspan='3'><div class='cellBold cellCenter'>" .
							__('Unknown') . "</div></td>
					</tr>
					<tr>
						<td><div class='cellBold cellCenter'>" .
							__('Agents') . "</div></td>
						<td colspan='3'><div class='cellBold cellCenter cellWhite cellBorder1 cellBig'>" .
							$group_stats['total_agents'] . "</div></td>
						<td colspan='3'><div class='cellBold cellCenter cellUnknown cellBorder1 cellBig'>" .
							$group_stats['agents_unknown'] . "</div></td>
					</tr>
					<tr>
						<td></td>
						<td><div class='cellBold cellCenter'>" .
							__('Total') . "</div></td>
						<td><div class='cellBold cellCenter'>" .
							__('Normal') . "</div></td>
						<td><div class='cellBold cellCenter'>" .
							__('Critical') . "</div></td>
						<td><div class='cellBold cellCenter'>" .
							__('Warning') . "</div></td>
						<td><div class='cellBold cellCenter'>" .
							__('Unknown') . "</div></td>
						<td><div class='cellBold cellCenter'>" .
							__('Not init') . "</div></td>
					</tr>
					<tr>
						<td><div class='cellBold cellCenter'>" .
							__('Monitors') . "</div></td>
						<td><div class='cellBold cellCenter cellWhite cellBorder1 cellBig'>" .
							$group_stats['monitor_checks'] . "</div></td>
						<td><div class='cellBold cellCenter cellNormal cellBorder1 cellBig'>" .
							$group_stats['monitor_ok'] ."</div></td>
						<td><div class='cellBold cellCenter cellCritical cellBorder1 cellBig'>" .
							$group_stats['monitor_critical'] . "</div></td>
						<td><div class='cellBold cellCenter cellWarning cellBorder1 cellBig'>" .
							$group_stats['monitor_warning'] . "</div></td>
						<td><div class='cellBold cellCenter cellUnknown cellBorder1 cellBig'>" .
							$group_stats['monitor_unknown'] . "</div></td>
						<td><div class='cellBold cellCenter cellNotInit cellBorder1 cellBig'>" .
							$group_stats['monitor_not_init'] . "</div></td>
					</tr>
					<tr>
						<td></td>
						<td colspan='3'><div class='cellBold cellCenter'>" .
							__('Defined') . "</div></td>
						<td colspan='3'><div class='cellBold cellCenter'>" .
							__('Fired') . "</div></td>
					</tr>
					<tr>
						<td><div class='cellBold cellCenter'>" .
							__('Alerts') . "</div></td>
						<td colspan='3'><div class='cellBold cellCenter cellWhite cellBorder1 cellBig'>" .
							$group_stats['monitor_alerts'] . "</div></td>
						<td colspan='3'><div class='cellBold cellCenter cellAlert cellBorder1 cellBig'>" .
							$group_stats['monitor_alerts_fired'] . "</div></td>
					</tr>
					<tr>
						<td></td>
						<td colspan='6'><div class='cellBold cellCenter'>" .
							__('Last 8 hours') . "</div></td>
					</tr>
					<tr>
						<td><div class='cellBold cellCenter'>" .
							__('Events') . "</div></td>
						<td colspan='6'><div class='cellBold cellCenter cellWhite cellBorder1 cellBig'>" .
							count($events)."</div></td>
					</tr>
				</table>";
			
			break;
		case 'network_interfaces_report':
			reporting_network_interfaces_table($content, $report, $mini, $item_title, $table);
			break;
		case 'general':
			if (empty($item_title)) {
				$item_title = __('General');
			}
			reporting_header_content($mini, $content, $report, $table, $item_title);
			
			$group_by_agent = $content['group_by_agent'];
			$order_uptodown = $content['order_uptodown'];
			
			$table->style[1] = 'text-align: right';
			
			// Put description at the end of the module (if exists)
			$table->colspan[1][0] = 3;
			if ($content["description"] != "") {
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push($table->data, $data_desc);
			}
			
			switch ($group_by_agent) {
				//0 means not group by agent
				case 0:
					$sql = sprintf("SELECT id_agent_module,
							server_name, operation
						FROM treport_content_item
						WHERE id_report_content = %d",
						$content['id_rc']);
					
					$generals = db_process_sql ($sql);
					if ($generals === false) {
						$data = array ();
						$table->colspan[2][0] = 3;
						$data[0] =
							__('There are no Agent/Modules defined');
						array_push ($table->data, $data);
						break;
					}
					
					$table1->width = '99%';
					$table1->data = array ();
					$table1->head = array ();
					$table1->head[0] = __('Agent');
					$table1->head[1] = __('Module');
					$table1->head[2] = __('Operation');
					$table1->head[3] = __('Value');
					$table1->style[0] = 'text-align: left';
					$table1->style[1] = 'text-align: left';
					$table1->style[2] = 'text-align: left';
					$table1->style[3] = 'text-align: left';
					
					$data_res = array();
					
					foreach ($generals as $key => $row) {
						//Metaconsole connection
						$server_name = $row ['server_name'];
						if (($config ['metaconsole'] == 1) && $server_name != '' && defined('METACONSOLE')) {
							$connection = metaconsole_get_connection($server_name);
							if (metaconsole_load_external_db($connection) != NOERR) {
								//ui_print_error_message ("Error connecting to ".$server_name);
								continue;
							}
						}
						
						$mod_name = modules_get_agentmodule_name ($row['id_agent_module']);
						$ag_name = modules_get_agentmodule_agent_name ($row['id_agent_module']);
						
						switch ($row['operation']) {
							case 'sum':
								$data_res[$key] =
									reporting_get_agentmodule_data_sum(
										$row['id_agent_module'], $content['period'], $report["datetime"]);
								break;
							case 'max':
								$data_res[$key] =
									reporting_get_agentmodule_data_max(
										$row['id_agent_module'], $content['period']);
								break;
							case 'min':
								$data_res[$key] =
									reporting_get_agentmodule_data_min(
										$row['id_agent_module'], $content['period']);
								break;
							case 'avg':
							default:
								$data_res[$key] =
									reporting_get_agentmodule_data_average(
										$row['id_agent_module'], $content['period']);
								break;
						}
						
						$unit = db_get_value('unit', 'tagente_modulo',
							'id_agente_modulo',
							$row['id_agent_module']);
						
						$id_agent_module[$key] = $row['id_agent_module'];
						$agent_name[$key] = $ag_name;
						$module_name[$key] = $mod_name;
						$units[$key] = $unit;
						$operations[$key] = $row['operation'];
						
						//Restore dbconnection
						if (($config ['metaconsole'] == 1) && $server_name != '' && defined('METACONSOLE')) {
							metaconsole_restore_db();
						}
					}
					//Order by data descending, ascending or without order
					if (($order_uptodown == 0) || ($order_uptodown == 1)
						|| ($order_uptodown == 2)) {
						
						
						switch ($order_uptodown) {
							//Descending
							case 1:
								array_multisort($data_res, SORT_DESC,
									$agent_name, SORT_ASC, $module_name,
									SORT_ASC, $id_agent_module,
									SORT_ASC, $operations, SORT_ASC);
								break;
							//Ascending
							case 2:
								array_multisort($data_res, SORT_ASC,
									$agent_name, SORT_ASC, $module_name,
									SORT_ASC, $id_agent_module,
									SORT_ASC, $operations, SORT_ASC);
								break;
						}
						
						
						$i = 0;
						foreach ($data_res as $d) {
							$data = array();
							$data[0] = $agent_name[$i];
							$data[1] = $module_name[$i];
							
							switch ($operations[$i]) {
								case 'sum':
									$op = __('Summatory');
									break;
								case 'min':
									$op = __('Minimal');
									break;
								case 'max':
									$op = __('Maximun');
									break;
								case 'avg':
								default:
									$op = __('Rate');
									break;
							}
							$data[2] = $op;
							
							if ($d === false) {
								$data[3] = '--';
							}
							else {
							$data[3] = format_for_graph($d, 2) . " " .
								$units[$i];
							}
							array_push ($table1->data, $data);
							$i++;
						}
					}
					//Order by agent name
					elseif ($order_uptodown == 3) {
						array_multisort($agent_name, SORT_ASC,
							$data_res, SORT_ASC, $module_name, SORT_ASC,
							$id_agent_module, SORT_ASC, $operations,
							SORT_ASC);
						$i=0;
						foreach ($agent_name as $a) {
							$data = array();
							$data[0] = $agent_name[$i];
							$data[1] = $module_name[$i];
							
							switch ($operations[$i]) {
								case 'sum':
									$op = __('Summatory');
									break;
								case 'min':
									$op = __('Minimal');
									break;
								case 'max':
									$op = __('Maximun');
									break;
								case 'avg':
								default:
									$op = __('Average');
									break;
							}
							$data[2] = $op;
							
							
							if ($data_res[$i] === false) {
								$data[3] = '--';
							}
							else {
								$data[3] =
									format_for_graph($data_res[$i], 2)
									. " " . $units[$i];
							}
							array_push ($table1->data, $data);
							$i++;
						}
					}
					
					$table->colspan[2][0] = 3;
					$data = array();
					$data[0] = html_print_table($table1, true);
					array_push ($table->data, $data);
					break;
				//1 means group by agent
				case 1:
					//Get the data
					$sql_data = sprintf("SELECT id_agent_module,
							server_name, operation
						FROM treport_content_item
						WHERE id_report_content = %d",
						$content['id_rc']);
					$generals = db_process_sql ($sql_data);
					
					if ($generals === false) {
						$data = array ();
						$table->colspan[2][0] = 3;
						$data[0] = __('There are no Agent/Modules defined');
						array_push ($table->data, $data);
						break;
					}
					
					$agent_list = array();
					$modules_list = array();
					$operation_list = array();
					foreach ($generals as $general) {
						//Metaconsole connection
						$server_name = $general ['server_name'];
						if (($config ['metaconsole'] == 1) && $server_name != '' && defined('METACONSOLE')) {
							$connection = metaconsole_get_connection($server_name);
							if (metaconsole_load_external_db($connection) != NOERR) {
								//ui_print_error_message ("Error connecting to ".$server_name);
								continue;
							}
						}
						
						$ag_name = modules_get_agentmodule_agent_name(
							$general ['id_agent_module']);
						if (!in_array ($ag_name, $agent_list)) {
							array_push ($agent_list, $ag_name);
						}
						
						$mod_name = modules_get_agentmodule_name(
							$general ['id_agent_module'])
							. ' (' . $general['operation'] . ')';
						if (!in_array ($mod_name, $modules_list)) {
							array_push ($modules_list, $mod_name);
						}
						
						array_push($operation_list, $general['operation']);
						
						//Restore dbconnection
						if (($config ['metaconsole'] == 1) && $server_name != '' && defined('METACONSOLE')) {
							metaconsole_restore_db();
						}
					}
					
					$table2->width = '99%';
					$table2->data = array ();
					$table2->head = array ();
					$table2->head[0] = __('Agent');
					$table2->style[0] = 'text-align: left';
					$i = 1;
					foreach ($modules_list as $m) {
						$table2->head[$i] =
							ui_print_truncate_text($m, 20, false);
						$table2->style[$i] = 'text-align: center';
						$i++;
					}
					
					foreach ($agent_list as $a) {
						$data = array();
						$data[0] = $a;
						$i = 1;
						foreach ($modules_list as $m) {
							foreach ($generals as $g) {
								//Metaconsole connection
								$server_name = $g ['server_name'];
								if (($config ['metaconsole'] == 1) && $server_name != '' && defined('METACONSOLE')) {
									$connection = metaconsole_get_connection($server_name);
									if (metaconsole_load_external_db($connection) != NOERR) {
										//ui_print_error_message ("Error connecting to ".$server_name);
										continue;
									}
								}
								
								$agent_name = modules_get_agentmodule_agent_name ($g['id_agent_module']);
								$module_name = modules_get_agentmodule_name ($g['id_agent_module']);
								$unit = db_get_value('unit', 'tagente_modulo', 'id_agente_modulo', $g['id_agent_module']);
								$found = false;
								if (strcmp($a, $agent_name) == 0
									&& strcmp($m, $module_name . ' (' . $g['operation'] . ')') == 0) {
									switch ($g['operation']) {
										case 'sum':
											$value_res = reporting_get_agentmodule_data_sum ($g['id_agent_module'], $content['period'], $report["datetime"]);
											break;
										case 'max':
											$value_res = reporting_get_agentmodule_data_max ($g['id_agent_module'], $content['period']);
											break;
										case 'min':
											$value_res = reporting_get_agentmodule_data_min ($g['id_agent_module'], $content['period']);
											break;
										case 'avg':
										default:
											$value_res = reporting_get_agentmodule_data_average ($g['id_agent_module'], $content['period']);
											break;
									}
									
									if ($value_res === false) {
										$data[$i] = '--';
									}
									else {
										$data[$i] = format_for_graph($value_res, 2) . " " . $unit;
									}
									$found = true;
								}
								else {
									$data[$i] = '--';
								}
								
								if ($found == true) {
									
									//Restore dbconnection
									if (($config ['metaconsole'] == 1) && $server_name != '' && defined('METACONSOLE')) {
										metaconsole_restore_db();
									}
									
									break;
								}
								
								//Restore dbconnection
								if (($config ['metaconsole'] == 1) && $server_name != '' && defined('METACONSOLE')) {
									metaconsole_restore_db();
								}
							}
							$i++;
						}
						array_push($table2->data, $data);
					}
					
					$table->colspan[2][0] = 3;
					$data = array();
					$data[0] = html_print_table($table2, true);
					array_push ($table->data, $data);
					break;
			}
			
			if ($content['show_resume'] && count($generals) > 0) {
				
				//Get the first valid value and assign it to $min & $max
				$min = false;
				$min_name_agent_module = '';
				$max_name_agent_module = '';
				$min_unit = '';
				$max_unit = '';
				$i=0;
				do {
					//Metaconsole connection
					$server_name = $generals[$i]['server_name'];
					if (($config ['metaconsole'] == 1) && $server_name != '' && defined('METACONSOLE')) {
						$connection = metaconsole_get_connection($server_name);
						if (metaconsole_load_external_db($connection) != NOERR) {
							//ui_print_error_message ("Error connecting to ".$server_name);
							continue;
						}
					}
					switch ($generals[$i]['operation']) {
						case 'sum':
							$min = reporting_get_agentmodule_data_sum ($generals[$i]['id_agent_module'], $content['period'], $report["datetime"]);
							break;
						case 'max':
							$min = reporting_get_agentmodule_data_max ($generals[$i]['id_agent_module'], $content['period']);
							break;
						case 'min':
							$min = reporting_get_agentmodule_data_min ($generals[$i]['id_agent_module'], $content['period']);
							break;
						case 'avg':
						default:
							$min = reporting_get_agentmodule_data_average ($generals[$i]['id_agent_module'], $content['period']);
							break;
					}
					$i++;
					
					$min_name_agent_module =
						modules_get_agentmodule_name($generals[$i]['id_agent_module']) .
						" - " .
						modules_get_agentmodule_agent_name($generals[$i]['id_agent_module']) .
						" (" . $generals[$i]['operation'] . ")";
					
					$min_unit = db_get_value('unit', 'tagente_modulo', 'id_agente_modulo', $generals[$i]['id_agent_module']);
					
					//Restore dbconnection
					if (($config ['metaconsole'] == 1) && $server_name != '' && defined('METACONSOLE')) {
						metaconsole_restore_db();
					}
				}
				while ($min === false && $i < count($generals));
				
				$max = $min;
				$max_name_agent_module = $min_name_agent_module;
				$max_unit = $min_unit;
				$avg = 0;
				$length = 0;
				
				if ($generals === false) {
					$generals = array();
				}
				
				foreach ($generals as $g) {
					//Metaconsole connection
					$server_name = $g['server_name'];
					if (($config ['metaconsole'] == 1) && $server_name != '' && defined('METACONSOLE')) {
						$connection = metaconsole_get_connection($server_name);
						if (metaconsole_load_external_db($connection) != NOERR) {
							//ui_print_error_message ("Error connecting to ".$server_name);
							continue;
						}
					}
					switch ($g['operation']) {
						case 'sum':
							$value = reporting_get_agentmodule_data_sum ($g['id_agent_module'], $content['period'], $report["datetime"]);
							break;
						case 'max':
							$value = reporting_get_agentmodule_data_max ($g['id_agent_module'], $content['period']);
							break;
						case 'min':
							$value = reporting_get_agentmodule_data_min ($g['id_agent_module'], $content['period']);
							break;
						case 'avg':
						default:
							$value = reporting_get_agentmodule_data_average ($g['id_agent_module'], $content['period']);
							break;
					}
					
					if ($value !== false) {
						if ($value > $max) {
							$max = $value;
							$max_name_agent_module =
								modules_get_agentmodule_name($g['id_agent_module']) .
								" - " .
								modules_get_agentmodule_agent_name($g['id_agent_module']) .
								" (" . $g['operation'] . ")";
							$max_unit = db_get_value('unit', 'tagente_modulo', 'id_agente_modulo', $g['id_agent_module']);
						}
						if ($value < $min ) {
							$min = $value;
							$min_name_agent_module =
								modules_get_agentmodule_name($g['id_agent_module']) .
								" - " .
								modules_get_agentmodule_agent_name($g['id_agent_module']) .
								" (" . $g['operation'] . ")";
							$min_unit = db_get_value('unit', 'tagente_modulo', 'id_agente_modulo', $g['id_agent_module']);
						}
						$avg += $value;
						$length++;
					}
					
					//Restore dbconnection
					if (($config ['metaconsole'] == 1)
						&& $server_name != ''
						&& defined('METACONSOLE')) {
						metaconsole_restore_db();
					}
				}
				if ($length == 0) {
					$avg = 0;
				}
				else {
					$avg = $avg / $length;
				}
				
				unset($table_summary);
				$table_summary->width = '99%';
				
				$table_summary->data = array ();
				$table_summary->head = array ();
				$table_summary->head_colspan = array ();
				$table_summary->align = array();
				
				$table_summary->align[0] = 'left';
				$table_summary->align[1] = 'left';
				$table_summary->align[2] = 'left';
				$table_summary->align[3] = 'left';
				$table_summary->align[4] = 'left';
				
				$table_summary->head_colspan[0] = 2;
				$table_summary->head[0] = __('Min Value');
				$table_summary->head[1] = __('Average Value');
				$table_summary->head_colspan[2] = 2;
				$table_summary->head[2] = __('Max Value');
				
				$table_summary->data[0][0] = $min_name_agent_module;
				$table_summary->data[0][1] = format_for_graph($min,2) . ' ' . $min_unit;
				$table_summary->data[0][2] = format_for_graph($avg,2);
				$table_summary->data[0][3] = $max_name_agent_module;
				$table_summary->data[0][4] = format_for_graph($max,2) . ' ' . $max_unit;
				
				$table->colspan[3][0] = 3;
				array_push ($table->data,
					array('<b>' . __('Summary') . '</b>'));
				$table->colspan[4][0] = 3;
				array_push ($table->data,
					array(html_print_table($table_summary, true)));
			}
			break;
		
		
		case 'top_n':
			$top_n = $content['top_n'];
			
			switch ($top_n) {
				case REPORT_TOP_N_MAX:
					$type_top_n = __('Max');
					break;
				case REPORT_TOP_N_MIN:
					$type_top_n = __('Min');
					break;
				case REPORT_TOP_N_AVG:
				default:
					//If nothing is selected then it will be shown the average data
					$type_top_n = __('Avg');
					break;
			}
			
			if (empty($item_title)) {
				$item_title = 'Top '.$content['top_n_value'] . '<br>' . $type_top_n;
			}
			reporting_header_content($mini, $content, $report, $table, $item_title);
			
			$order_uptodown = $content['order_uptodown'];
			
			$top_n_value = $content['top_n_value'];
			$show_graph = $content['show_graph'];
			
			$table->style[0] = 'padding: 8px 5px 8px 5px;';
			$table->style[1] = 'text-align: right';
			
			// Put description at the end of the module (if exists)
			$table->colspan[1][0] = 3;
			if ($content["description"] != "") {
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			//Get all the related data
			$sql = sprintf("SELECT id_agent_module, server_name
				FROM treport_content_item
				WHERE id_report_content = %d", $content['id_rc']);
			
			$tops = db_process_sql ($sql);
			
			if ($tops === false) {
				$data = array ();
				$table->colspan[2][0] = 3;
				$data[0] = __('There are no Agent/Modules defined');
				array_push ($table->data, $data);
				break;
			}
			
			if ($show_graph != REPORT_TOP_N_ONLY_GRAPHS) {
				$table1->width = '99%';
				$table1->data = array ();
				$table1->head = array ();
				$table1->head[0] = __('Agent');
				$table1->head[1] = __('Module');
				$table1->head[2] = __('Value');
				$table1->style[0] = 'text-align: left';
				$table1->style[1] = 'text-align: left';
				$table1->style[2] = 'text-align: left';
			}
			
			//Get data of all agents (before to slide to N values)
			$data_top = array();
			
			foreach ($tops as $key => $row) {
				
				//Metaconsole connection
				$server_name = $row['server_name'];
				if (($config ['metaconsole'] == 1) && $server_name != '' && defined('METACONSOLE')) {
					$connection = metaconsole_get_connection($server_name);
					if (metaconsole_load_external_db($connection) != NOERR) {
						//ui_print_error_message ("Error connecting to ".$server_name);
						continue;
					}
				}
				
				$ag_name = modules_get_agentmodule_agent_name($row ['id_agent_module']); 
				$mod_name = modules_get_agentmodule_name ($row ['id_agent_module']);
				$unit = db_get_value('unit', 'tagente_modulo',
					'id_agente_modulo', $row ['id_agent_module']); 
				
				
				switch ($top_n) {
					case REPORT_TOP_N_MAX:
						$value = reporting_get_agentmodule_data_max ($row['id_agent_module'], $content['period']);
						break;
					case REPORT_TOP_N_MIN:
						$value = reporting_get_agentmodule_data_min ($row['id_agent_module'], $content['period']);
						break;
					case REPORT_TOP_N_AVG:
					default:
						//If nothing is selected then it will be shown the average data
						$value = reporting_get_agentmodule_data_average ($row['id_agent_module'], $content['period']);
						break;
				}
				
				//If the returned value from modules_get_agentmodule_data_max/min/avg is false it won't be stored.
				if ($value !== false) {
					$data_top[$key] = $value;
					$id_agent_module[$key] = $row['id_agent_module'];
					$agent_name[$key] = $ag_name;
					$module_name[$key] = $mod_name;
					$units[$key] = $unit;
				}
				
				//Restore dbconnection
				if (($config ['metaconsole'] == 1) && $server_name != '' && defined('METACONSOLE')) {
					metaconsole_restore_db();
				}
			}
			
			if (empty($data_top)) {
				$data = array ();
				$table->colspan[2][0] = 3;
				$data[0] = __('Insuficient data');
				array_push ($table->data, $data);
				break;
			}
			
			//Order to show.
			switch ($order_uptodown) {
				//Descending
				case 1:
					array_multisort($data_top, SORT_DESC, $agent_name, SORT_ASC, $module_name, SORT_ASC, $id_agent_module, SORT_ASC, $units, SORT_ASC);
					break;
				//Ascending
				case 2:
					array_multisort($data_top, SORT_ASC, $agent_name, SORT_ASC, $module_name, SORT_ASC, $id_agent_module, SORT_ASC, $units, SORT_ASC);
					break;
				//By agent name or without selection
				case 0:
				case 3:
					array_multisort($agent_name, SORT_ASC, $data_top, SORT_ASC, $module_name, SORT_ASC, $id_agent_module, SORT_ASC, $units, SORT_ASC);
					break;
			}
			
			array_splice ($data_top, $top_n_value);
			array_splice ($agent_name, $top_n_value);
			array_splice ($module_name, $top_n_value);
			array_splice ($id_agent_module, $top_n_value);
			array_splice ($units, $top_n_value);
			
			$data_top_values = array ();
			$data_top_values['data_top'] = $data_top;
			$data_top_values['agent_name'] = $agent_name;
			$data_top_values['module_name'] = $module_name; 
			$data_top_values['id_agent_module'] = $id_agent_module;
			$data_top_values['units'] = $units;
			
			// Define truncate size depends the graph width
			$truncate_size = $sizgraph_w / (4 * ($config['font_size']))-1;
			
			if ($order_uptodown == 1 || $order_uptodown == 2) {
				$i = 0;
				$data_pie_graph = array();
				$data_hbar = array();
				foreach ($data_top as $dt) {
					$item_name = '';
					$item_name =
						ui_print_truncate_text($agent_name[$i], $truncate_size, false, true, false, "...") .
						' - ' . 
						ui_print_truncate_text($module_name[$i], $truncate_size, false, true, false, "...");
					
					
					
					//Dirty hack, maybe I am going to apply a job in Apple
					//https://www.imperialviolet.org/2014/02/22/applebug.html
					$item_name_key_pie = $item_name;
					$exist_key = true;
					while ($exist_key) {
						if (isset($data_pie_graph[$item_name_key_pie])) {
							$item_name_key_pie .= ' ';
						}
						else {
							$exist_key = false;
						}
					}
					$item_name_key_hbar = $item_name;
					$exist_key = true;
					while ($exist_key) {
						if (isset($data_hbar[$item_name_key_hbar])) {
							$item_name_key_hbar = ' ' . $item_name_key_hbar;
						}
						else {
							$exist_key = false;
						}
					}
					
					
					
					$data_hbar[$item_name]['g'] = $dt; 
					$data_pie_graph[$item_name] = $dt;
					
					if ($show_graph == 0 || $show_graph == 1) {
						$data = array();
						$data[0] = $agent_name[$i];
						$data[1] = $module_name[$i];
						
						$data[2] = format_for_graph($dt,2) . " " . $units[$i];
						array_push ($table1->data, $data);
					}
					$i++;
					if ($i >= $top_n_value) break;
				} 
			}
			else if ($order_uptodown == 0 || $order_uptodown == 3) {
				$i = 0;
				$data_pie_graph = array();
				$data_hbar = array();
				foreach ($agent_name as $an) {
					$item_name = '';
					$item_name =
						ui_print_truncate_text($agent_name[$i],
							$truncate_size, false, true, false, "...") .
						' - ' . 
						ui_print_truncate_text($module_name[$i],
							$truncate_size, false, true, false, "...");
					
					
					
					//Dirty hack, maybe I am going to apply a job in Apple
					//https://www.imperialviolet.org/2014/02/22/applebug.html
					$item_name_key_pie = $item_name;
					$exist_key = true;
					while ($exist_key) {
						if (isset($data_pie_graph[$item_name_key_pie])) {
							$item_name_key_pie .= ' ';
						}
						else {
							$exist_key = false;
						}
					}
					$item_name_key_hbar = $item_name;
					$exist_key = true;
					while ($exist_key) {
						if (isset($data_hbar[$item_name_key_hbar])) {
							$item_name_key_hbar = ' ' . $item_name_key_hbar;
						}
						else {
							$exist_key = false;
						}
					}
					
					
					
					$data_pie_graph[$item_name] = $data_top[$i];
					$data_hbar[$item_name]['g'] = $data_top[$i];
					if  ($show_graph == 0 || $show_graph == 1) {
						$data = array();
						$data[0] = $an;
						$data[1] = $module_name[$i];
						$data[2] = format_for_graph($data_top[$i],2) . " " . $units[$i];
						array_push ($table1->data, $data);
					}
					$i++;
					if ($i >= $top_n_value) break;
				}
			}
			
			unset($data);
			$table->colspan[2][0] = 3;
			$table->style[2] = 'text-align:center';
			if ($show_graph == 0 || $show_graph == 1) {
				$data = array();
				$data[0] = html_print_table($table1, true);
				array_push ($table->data, $data);
			}
			
			$table->colspan[3][0] = 3;
			$data = array();
			if ($show_graph != REPORT_TOP_N_ONLY_TABLE) {
				
				$data[0] = pie3d_graph(false, $data_pie_graph,
					$sizgraph_w, $sizgraph_h, __("other"),
					ui_get_full_url(false, true, false, false) . '/', $config['homedir'] .  "/images/logo_vertical_water.png",
					$config['fontpath'], $config['font_size']);
				
				
				array_push ($table->data, $data);
				//Display bars graph
				$table->colspan[4][0] = 3;
				$table->style[0] .= 'text-align:center';
				$height = count($data_pie_graph)*20+35;
				$data = array();
				$data[0] = hbar_graph(false, $data_hbar, $sizgraph_w,
					$height, array(), array(), "", "", true,
					ui_get_full_url(false, true, false, false) . '/', $config['homedir'] .  "/images/logo_vertical_water.png", $config['fontpath'], $config['font_size'], true, 1, true);
				
				array_push ($table->data, $data);
			}
			
			if ($content['show_resume'] && count($data_top_values) > 0) {
				//Get the very first not null value 
				$i=0;
				do {
					$min = $data_top_values['data_top'][$i];
					$i++;
				}
				while ($min === false && $i < count($data_top_values));
				$max = $min;
				$avg = 0;
				
				$i=0;
				foreach ($data_top_values['data_top'] as $key => $dtv) {
					if ($dtv < $min) $min = $dtv;
					if ($dtv > $max) $max = $dtv;
					$avg += $dtv;
					$i++;
				}
				$avg = $avg / $i;
				
				unset($table_summary);
				
				$table_summary->width = '99%';
				$table_summary->data = array ();
				$table_summary->head = array ();
				$table_summary->head[0] = __('Min Value');
				$table_summary->head[1] = __('Average Value');
				$table_summary->head[2] = __('Max Value');
				
				$table_summary->data[0][0] = format_for_graph($min, 2);
				$table_summary->data[0][1] = format_for_graph($avg, 2);
				$table_summary->data[0][2] = format_for_graph($max, 2);
				
				$table->colspan[5][0] = 3;
				array_push ($table->data, array('<b>'.__('Summary').'</b>'));
				$table->colspan[6][0] = 3;
				array_push ($table->data, array(html_print_table($table_summary, true)));
			}
			break;
		case 'exception':
			$order_uptodown = $content['order_uptodown'];
			$exception_condition = $content['exception_condition'];
			$exception_condition_value = $content['exception_condition_value'];
			$show_graph = $content['show_graph'];
			
			$table->style[1] = 'text-align: right';
			
			switch ($exception_condition) {
				case REPORT_EXCEPTION_CONDITION_EVERYTHING:
					$title_exeption = __('Exception - Everything');
					break;
				case REPORT_EXCEPTION_CONDITION_GE:
					$title_exeption =
						sprintf(__('Exception - Modules over or equal to %s'),
						$exception_condition_value);
					break;
				case REPORT_EXCEPTION_CONDITION_LE:
					$title_exeption =
						sprintf(__('Exception - Modules under or equal to %s'),
						$exception_condition_value);
					break;
				case REPORT_EXCEPTION_CONDITION_L:
					$title_exeption =
						sprintf(__('Exception - Modules under %s'),
						$exception_condition_value);
					break;
				case REPORT_EXCEPTION_CONDITION_G:
					$title_exeption =
						sprintf(__('Exception - Modules over %s'),
						$exception_condition_value);
					break;
				case REPORT_EXCEPTION_CONDITION_E:
					$title_exeption =
						sprintf(__('Exception - Equal to %s'),
						$exception_condition_value);
					break;
				case REPORT_EXCEPTION_CONDITION_NE:
					$title_exeption =
						sprintf(__('Exception - Not equal to %s'),
						$exception_condition_value);
					break;
				case REPORT_EXCEPTION_CONDITION_OK:
					$title_exeption =
						__('Exception - Modules at normal status');
					break;
				case REPORT_EXCEPTION_CONDITION_NOT_OK:
					$title_exeption =
						__('Exception - Modules at critical or warning status');
					break;
			}
			
			if (empty($item_title)) {
				$item_title = $title_exeption;
			}
			reporting_header_content($mini, $content, $report, $table, $item_title);
			
			// Put description at the end of the module (if exists)
			$table->colspan[1][0] = 3;
			if ($content["description"] != "") {
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			//Get all the related data
			$sql = sprintf("
				SELECT id_agent_module, server_name, operation
				FROM treport_content_item
				WHERE id_report_content = %d", $content['id_rc']);
			
			$exceptions = db_process_sql ($sql);
			if ($exceptions === false) {
				$data = array ();
				$table->colspan[2][0] = 3;
				$data[0] = __('There are no Agent/Modules defined');
				array_push ($table->data, $data);
				break;
			}
			
			if ($show_graph == 0 || $show_graph == 1) {
				$table1->width = '99%';
				$table1->data = array ();
				$table1->head = array ();
				$table1->head[0] = __('Agent');
				$table1->head[1] = __('Module');
				$table1->head[2] = __('Operation');
				$table1->head[3] = __('Value');
				$table1->style[0] = 'text-align: left';
				$table1->style[1] = 'text-align: left';
				$table1->style[2] = 'text-align: left';
				$table1->style[3] = 'text-align: left';
			}
			
			//Get the very first not null value 
			$i=0;
			do {
				//Metaconsole connection
				$server_name = $exceptions[$i]['server_name'];
				if (($config ['metaconsole'] == 1) && $server_name != '' && defined('METACONSOLE')) {
					$connection = metaconsole_get_connection($server_name);
					if (metaconsole_load_external_db($connection) != NOERR) {
						//ui_print_error_message ("Error connecting to ".$server_name);
						continue;
					}
				}
				
				switch ($exceptions[$i]['operation']) {
					case 'avg':
						$min = reporting_get_agentmodule_data_average($exceptions[$i]['id_agent_module'], $content['period']);
						break;
					case 'max':
						$min = reporting_get_agentmodule_data_max($exceptions[$i]['id_agent_module'], $content['period']);
						break;
					case 'min':
						$min = reporting_get_agentmodule_data_min($exceptions[$i]['id_agent_module'], $content['period']);
						break;
				}
				$i++;
				
				//Restore dbconnection
				if (($config ['metaconsole'] == 1) && $server_name != '' && defined('METACONSOLE')) {
					metaconsole_restore_db();
				}
			}
			while ($min === false && $i < count($exceptions));
			$max = $min;
			$avg = 0;
			
			$i=0;
			foreach ($exceptions as $exc) {
				//Metaconsole connection
				$server_name = $exc['server_name'];
				if (($config ['metaconsole'] == 1) && $server_name != '' && defined('METACONSOLE')) {
					$connection = metaconsole_get_connection($server_name);
					if (metaconsole_load_external_db($connection) != NOERR) {
						//ui_print_error_message ("Error connecting to ".$server_name);
						continue;
					}
				}
				
				$ag_name = modules_get_agentmodule_agent_name ($exc ['id_agent_module']);
				$mod_name = modules_get_agentmodule_name ($exc ['id_agent_module']);
				$unit = db_get_value('unit', 'tagente_modulo',
					'id_agente_modulo', $exc['id_agent_module']);
				
				switch ($exc['operation']) {
					case 'avg':
						$value = reporting_get_agentmodule_data_average ($exc['id_agent_module'], $content['period']);
						break;
					case 'max':
						$value = reporting_get_agentmodule_data_max ($exc['id_agent_module'], $content['period']);
						break;
					case 'min':
						$value = reporting_get_agentmodule_data_min ($exc['id_agent_module'], $content['period']);
						break;
				}
				
				if ($value !== false) {
					if ($value > $max) $max = $value;
					if ($value < $min) $min = $value;
					$avg += $value;
					
					//Skips
					switch ($exception_condition) {
						case REPORT_EXCEPTION_CONDITION_EVERYTHING:
							break;
						case REPORT_EXCEPTION_CONDITION_GE:
							if ($value < $exception_condition_value) {
								continue 2;
							}
							break;
						case REPORT_EXCEPTION_CONDITION_LE:
							if ($value > $exception_condition_value) {
								continue 2;
							}
							break;
						case REPORT_EXCEPTION_CONDITION_L:
							if ($value > $exception_condition_value) {
								continue 2;
							}
							break;
						case REPORT_EXCEPTION_CONDITION_G:
							if ($value < $exception_condition_value) {
								continue 2;
							}
							break;
						case REPORT_EXCEPTION_CONDITION_E:
							if ($value != $exception_condition_value) {
								continue 2;
							}
							break;
						case REPORT_EXCEPTION_CONDITION_NE:
							if ($value == $exception_condition_value) {
								continue 2;
							}
							break;
						case REPORT_EXCEPTION_CONDITION_OK:
							if (modules_get_agentmodule_status($exc['id_agent_module']) != 0) {
								continue 2;
							}
							break;
						case REPORT_EXCEPTION_CONDITION_NOT_OK:
							if (modules_get_agentmodule_status($exc['id_agent_module']) == 0) {
								continue 2;
							}
							break;
					}
					
					$i++;
					$data_exceptions[] = $value;
					$id_agent_module[] = $exc['id_agent_module'];
					$agent_name[] = $ag_name;
					$module_name[] = $mod_name;
					$units[] = $unit;
					if ($exc['operation'] == 'avg') {
						$operation[] = "rate";
					}
					else {
						$operation[] = $exc['operation'];
					}
				}
				//Restore dbconnection
				if (($config ['metaconsole'] == 1) && $server_name != '' && defined('METACONSOLE')) {
					metaconsole_restore_db();
				}
			}
			
			//$i <= 0 means that there are no rows on the table, therefore no modules under the conditions defined.
			if ($i <= 0) {
				$data = array ();
				$table->colspan[2][0] = 3;
				$data[0] = __('There are no');
				
				switch ($exception_condition) {
					case REPORT_EXCEPTION_CONDITION_EVERYTHING:
						$data[0] .= ' '.__('Modules under those conditions');
						break;
					case REPORT_EXCEPTION_CONDITION_GE:
						$data[0] .= ' '.__('Modules over or equal to').' '.$exception_condition_value;
						break;
					case REPORT_EXCEPTION_CONDITION_LE:
						$data[0] .= ' '.__('Modules less or equal to').' '.$exception_condition_value;
						break;
					case REPORT_EXCEPTION_CONDITION_L:
						$data[0] .= ' '.__('Modules less').' '.$exception_condition_value;
						break;
					case REPORT_EXCEPTION_CONDITION_G:
						$data[0] .= ' '.__('Modules over').' '.$exception_condition_value;
						break;
					case REPORT_EXCEPTION_CONDITION_E:
						$data[0] .= ' '.__('Modules equal to').' '.$exception_condition_value;
						break;
					case REPORT_EXCEPTION_CONDITION_NE:
						$data[0] .= ' '.__('Modules not equal to').' '.$exception_condition_value;
						break;
					case REPORT_EXCEPTION_CONDITION_OK:
						$data[0] .= ' '.__('Modules normal status');
						break;
					case REPORT_EXCEPTION_CONDITION_NOT_OK:
						$data[0] .= ' '.__('Modules at critial or warning status');
						break;
				}
				
				
				array_push ($table->data, $data);
				break;
			}
			//$i > 0 means that there is at least one row on the table
			elseif ($i > 0) {
				$avg = $avg / $i;
				
				switch ($order_uptodown) {
					//Order descending
					case 1:
						array_multisort($data_exceptions, SORT_DESC, $agent_name, SORT_ASC, $module_name, SORT_ASC, $id_agent_module, SORT_ASC);
						break;
					//Order ascending
					case 2:
						array_multisort($data_exceptions, SORT_ASC, $agent_name, SORT_ASC, $module_name, SORT_ASC, $id_agent_module, SORT_ASC);
						break;
					//Order by agent name or without selection
					case 0:
					case 3:
						array_multisort($agent_name, SORT_ASC, $data_exceptions, SORT_ASC, $module_name, SORT_ASC, $id_agent_module, SORT_ASC);
						break;
				}
				
				if ($order_uptodown == 1 || $order_uptodown == 2) {
					$j = 0;
					$data_pie_graph = array();
					$data_hbar = array();
					foreach ($data_exceptions as $dex) {
						$data_hbar[$agent_name[$j]]['g'] = $dex;
						$data_pie_graph[$agent_name[$j]] = $dex;
						if ($show_graph == 0 || $show_graph == 1) {
							$data = array();
							$data[0] = $agent_name[$j];
							$data[1] = $module_name[$j];
							$data[2] = __($operation[$j]);
							$data[3] = format_for_graph($dex, 2) . " " . $units[$j];
							array_push ($table1->data, $data);
						}
						$j++;
					}
				}
				else if ($order_uptodown == 0 || $order_uptodown == 3) {
					$j = 0;
					$data_pie_graph = array();
					$data_hbar = array();
					foreach ($agent_name as $an) {
						$data_hbar[$an]['g'] = $data_exceptions[$j];
						$data_pie_graph[$an] = $data_exceptions[$j];
						if ($show_graph == 0 || $show_graph == 1) {
							$data = array();
							$data[0] = $an;
							$data[1] = $module_name[$j];
							$data[2] = __($operation[$j]);
							$data[3] = format_for_graph($data_exceptions[$j], 2) . " " . $units[$j];
							array_push ($table1->data, $data);
						}
						$j++;
					}
				}
			}
			
			$table->colspan[2][0] = 3;
			$table->cellstyle[2][0] = 'text-align: center;';
			if ($show_graph == 0 || $show_graph == 1) {
				$data = array();
				$data[0] = html_print_table($table1, true);
				array_push ($table->data, $data);
			}
			
			$table->colspan[3][0] = 3;
			$table->cellstyle[3][0] = 'text-align: center;';

			$data = array();
			if ($show_graph == 1 || $show_graph == 2) {
				$data[0] = pie3d_graph(false, $data_pie_graph,
					600, 150, __("other"), ui_get_full_url(false) . '/', $config['homedir'] .  "/images/logo_vertical_water.png",
					$config['fontpath'], $config['font_size']); 
				array_push ($table->data, $data);
				//Display bars graph
				$table->colspan[4][0] = 3;
				$table->cellstyle[4][0] = 'text-align: center;';
				$height = count($data_pie_graph) * 20 + 85;
				$data = array();
				
				$data[0] = hbar_graph(false, $data_hbar, 600, $height, array(), array(), "", "", true, ui_get_full_url(false) . '/', $config['homedir'] .  "/images/logo_vertical_water.png", '', '', true, 1, true);
				
				array_push ($table->data, $data);
			}
			
			if ($content['show_resume'] && $i>0) {
				unset($table_summary);
				
				$table_summary->width = '99%';
				$table_summary->data = array ();
				$table_summary->head = array ();
				$table_summary->head[0] = __('Min Value');
				$table_summary->head[1] = __('Average Value');
				$table_summary->head[2] = __('Max Value');
				
				$table_summary->data[0][0] = format_for_graph($min,2);
				$table_summary->data[0][1] = format_for_graph($avg,2);
				$table_summary->data[0][2] = format_for_graph($max,2);
				
				$table->colspan[5][0] = 3;
				$table->cellstyle[5][0] = 'text-align: center;';
				array_push ($table->data, array('<b>'.__('Summary').'</b>'));
				$table->colspan[6][0] = 3;
				array_push ($table->data, array(html_print_table($table_summary, true)));
			}
			break;
		case 'agent_module':
			$group_name = groups_get_name($content['id_group']);
			if ($content['id_module_group'] == 0) {
				$module_group_name = __('All');
			}
			else {
				$module_group_name = db_get_value('name', 'tmodule_group',
					'id_mg',  $content['id_module_group']);
			}
			
			if (empty($item_title)) {
				$item_title = __('Agents/Modules');
			}
			reporting_header_content($mini, $content, $report, $table, $item_title,
				$group_name . ' - ' . $module_group_name);
			
			$id_group = $content['id_group'];
			$id_module_group = $content['id_module_group'];
			$offset = get_parameter('offset', 0);
			$block = 20; //Maximun number of modules displayed on the table
			$modulegroup = get_parameter('modulegroup', 0);
			$table->style[1] = 'text-align: right';
			
			// Put description at the end of the module (if exists)
			$table->colspan[1][0] = 3;
			if ($content["description"] != "") {
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			
			$agents = '';
			if ($id_group > 0) {
				$agents = agents_get_group_agents($id_group);
				$agents = array_keys($agents);
			}
			
			$filter_module_groups = false;	
			if ($id_module_group > 0) {
				$filter_module_groups['id_module_group'] = $id_module_group;
			}
			
			$all_modules = agents_get_modules ($agents, false, $filter_module_groups, true, false);
			
			$modules_by_name = array();
			$name = '';
			$cont = 0;
			
			foreach ($all_modules as $key => $module) {
				if ($module == $name) {
					$modules_by_name[$cont-1]['id'][] = $key;
				}
				else {
					$name = $module;
					$modules_by_name[$cont]['name'] = $name;
					$modules_by_name[$cont]['id'][] = $key;
					$cont ++;
				}
			}
			
			if ($config["pure"] == 1) {
				$block = count($modules_by_name);
			}
			
			$filter_groups = array ('offset' => (int) $offset,
				'limit' => (int) $config['block_size']);
				
			if ($id_group > 0) {
				$filter_groups['id_grupo'] = $id_group;
			}
			
			$agents = agents_get_agents ($filter_groups);
			$nagents = count($agents);
			
			if ($all_modules == false || $agents == false) {
				$data = array ();
				$table->colspan[2][0] = 3;
				$data[0] = __('There are no agents with modules');
				array_push ($table->data, $data);
				break;
			}
			$table_data = '<table cellpadding="1" cellspacing="4" cellspacing="0" border="0" style="background-color: #EEE;">';
			
			$table_data .= "<th>".__("Agents")." / ".__("Modules")."</th>";
			
			$nmodules = 0;
			foreach ($modules_by_name as $module) {
				$nmodules++;
				
				$file_name = string2image(ui_print_truncate_text($module['name'], 'module_small', false, true, false, '...'), false, false, 6, 270, '#B1B1B1', 'FFF', 4, 0);
				$table_data .= '<th width="22px">' . html_print_image($file_name, true, array('title' => $module['name']))."</th>";
			}
			// Dont use pagination
			/*if ($block < $nmodules) {
				$table_data .= "<th width='20px' style='vertical-align:top; padding-top: 35px;' rowspan='".($nagents+1)."'><b>...</b></th>";
			}*/
			
			$filter_agents = false;
			if ($id_group > 0) {
				$filter_agents = array('id_grupo' => $id_group);
			}
			// Prepare pagination
			ui_pagination ((int)count(agents_get_agents ($filter_agents)));
			$table_data .= "<br>";
			
			foreach ($agents as $agent) {
				// Get stats for this group
				$agent_status = agents_get_status($agent['id_agente']);
				
				switch($agent_status) {
					case 4: // Alert fired status
						$rowcolor = COL_ALERTFIRED;
						$textcolor = '#000';
						break;
					case 1: // Critical status
						$rowcolor = COL_CRITICAL;
						$textcolor = '#FFF';
						break;
					case 2: // Warning status
						$rowcolor = COL_WARNING;
						$textcolor = '#000';
						break;
					case 0: // Normal status
						$rowcolor = COL_NORMAL;
						$textcolor = '#FFF';
						break;
					case 3: 
					case -1: 
					default: // Unknown status
						$rowcolor = COL_UNKNOWN;
						$textcolor = '#FFF';
						break;
				}
				
				$table_data .= "<tr style='height: 35px;'>";
				
				$file_name = string2image(ui_print_truncate_text($agent['nombre'], 'agent_small', false, true, false, '...'), false, false, 6, 0, $rowcolor, $textcolor, 4, 0);
				$table_data .= "<td style='background-color: ".$rowcolor.";'>".html_print_image($file_name, true, array('title' => $agent['nombre']))."</td>";
				$agent_modules = agents_get_modules($agent['id_agente']);
				
				$nmodules = 0;
				
				foreach ($modules_by_name as $module) {
					$nmodules++;
					// Don't use pagination
					/*if ($nmodules > $block) {
						continue;
					}*/
					
					$match = false;
					foreach($module['id'] as $module_id){
						if (!$match && array_key_exists($module_id,$agent_modules)) {
							$status = modules_get_agentmodule_status($module_id);
							$table_data .= "<td style='text-align: center; background-color: #DDD;'>";
							$win_handle = dechex(crc32($module_id.$module["name"]));
							$graph_type = return_graphtype (modules_get_agentmodule_type($module_id));
							
							switch ($status) {
								case 0:
									$table_data .= ui_print_status_image ('module_ok.png', $module['name']." in ".$agent['nombre'].": ".__('NORMAL'), true, array('width' => '20px', 'height' => '20px'));
									break;
								case 1:
									$table_data .= ui_print_status_image ('module_critical.png', $module['name']." in ".$agent['nombre'].": ".__('CRITICAL'), true, array('width' => '20px', 'height' => '20px'));
									break;
								case 2:
									$table_data .= ui_print_status_image ('module_warning.png', $module['name']." in ".$agent['nombre'].": ".__('WARNING'), true, array('width' => '20px', 'height' => '20px'));
									break;
								case 3:
									$table_data .= ui_print_status_image ('module_unknown.png', $module['name']." in ".$agent['nombre'].": ".__('UNKNOWN'), true, array('width' => '20px', 'height' => '20px'));
									break;
								case 4:
									$table_data .= ui_print_status_image ('module_alertsfired.png', $module['name']." in ".$agent['nombre'].": ".__('ALERTS FIRED'), true, array('width' => '20px', 'height' => '20px'));
									break;
							}
							$table_data .= "</td>";
							$match = true;
						}
					}
					
					if (!$match) {
						$table_data .= "<td style='background-color: #DDD;'></td>";
					}
				}
				
				$table_data .= "</tr>";
			}
			
			$table_data .= "</table>";
			
			$table_data .= "<div class='legend_basic' style='width: 96%'>";

			$table_data .= "<table>";
			$table_data .= "<tr><td colspan='2' style='padding-bottom: 10px;'><b>" . __('Legend') . "</b></td></tr>";
			$table_data .= "<tr><td class='legend_square_simple'><div style='background-color: " . COL_ALERTFIRED . ";'></div></td><td>" . __("Orange cell when the module has fired alerts") . "</td></tr>";
			$table_data .= "<tr><td class='legend_square_simple'><div style='background-color: " . COL_CRITICAL . ";'></div></td><td>" . __("Red cell when the module has a critical status") . "</td></tr>";
			$table_data .= "<tr><td class='legend_square_simple'><div style='background-color: " . COL_WARNING . ";'></div></td><td>" . __("Yellow cell when the module has a warning status") . "</td></tr>";
			$table_data .= "<tr><td class='legend_square_simple'><div style='background-color: " . COL_NORMAL . ";'></div></td><td>" . __("Green cell when the module has a normal status") . "</td></tr>";
			$table_data .= "<tr><td class='legend_square_simple'><div style='background-color: " . COL_UNKNOWN . ";'></div></td><td>" . __("Grey cell when the module has an unknown status") . "</td></tr>";
			$table_data .= "</table>";
			$table_data .= "</div>";
			$data = array ();
			$table->colspan[2][0] = 3;
			$data[0] = $table_data;
			array_push ($table->data, $data);
			break;
		case 'inventory':
			if (empty($item_title)) {
				$item_title = __('Inventory');
			}
			reporting_header_content($mini, $content, $report, $table, $item_title);
			
			$es = json_decode($content['external_source'], true);
			
			$id_agent = $es['id_agents'];
			$module_name = $es['inventory_modules'];
			if (empty($module_name)) {
				$module_name = array(0 => 0);
			}
			$date = $es['date'];
			$description = $content['description'];
			
			$data = array ();
			$table->colspan[1][0] = 2;
			$table->colspan[2][0] = 2;
			if ($description != '') {
				$data[0] = $description;
				array_push ($table->data, $data);
			}
			
			$inventory_data = inventory_get_data((array)$id_agent,(array)$module_name,$date,'',false);
			
			if ($inventory_data == ERR_NODATA) {
				$inventory_data = "<div class='nf'>".__('No data found.')."</div>";
				$inventory_data .= "&nbsp;</td></tr><tr><td>";
			}
			
			$data[0] = $inventory_data;
			array_push ($table->data, $data);
			break;
		case 'inventory_changes':
			if (empty($item_title)) {
				$item_title = __('Inventory changes');
			}
			reporting_header_content($mini, $content, $report, $table, $item_title);
			
			$es = json_decode($content['external_source'], true);
			
			$id_agent = $es['id_agents'];
			$module_name = $es['inventory_modules'];
			$description = $content['description'];
			
			$inventory_changes = inventory_get_changes($id_agent, $module_name, $report["datetime"] - $content['period'], $report["datetime"]);
			
			if ($inventory_changes == ERR_NODATA) {
				$inventory_changes = "<div class='nf'>".__('No changes found.')."</div>";
				$inventory_changes .= "&nbsp;</td></tr><tr><td>";
			}
			
			$data = array ();
			
			$table->colspan[1][0] = 2;
			
			if ($description != '') {
				$data[0] = $description;
				array_push ($table->data, $data);
			}
			
			$data[0] = $inventory_changes;
			$table->colspan[2][0] = 2;
			
			array_push ($table->data, $data);
			break;
		case 'agent_configuration':
			
			if (empty($item_title)) {
				$item_title = __('Agent configuration: ').$agent_name;
			}
			reporting_header_content($mini, $content, $report, $table, $item_title);
			
			$agent_name = agents_get_name ($content['id_agent']);
			$modules = agents_get_modules ($content['id_agent']);
			
			$data= array ();
			$table->colspan[0][1] = 10;
			
			//Agent's data
			$data[0] = '<b>'.__('Agent name').'</b>';
			$data[1] = '<b>'.__('Group').'</b>';
			$data[2] = '<b>'.__('SO').'</b>';
			$data[3] = '<b>'.__('IP').'</b>';
			$data[4] = '<b>'.__('Description').'</b>';
			$data[5] = '<b>'.__('Status').'</b>';
			
			$table->colspan[1][3] = 2;
			$table->colspan[1][4] = 4;
			$table->colspan[1][5] = 2;
			$table->colspan[1][5] = 2;
			array_push ($table->data, $data);
			unset($data);
			
			$sql = "SELECT * FROM tagente WHERE id_agente=".$content['id_agent'];
			$agent_data = db_get_row_sql($sql);
			
			$data[0] = $agent_data['nombre'];
			$data[1] = ui_print_group_icon ($agent_data['id_grupo'], true, '', '', false);
			$data[2] = ui_print_os_icon ($agent_data["id_os"], true, true);
			$data[3] = $agent_data['direccion'];
			$agent_data_comentarios = strip_tags(ui_bbcode_to_html($agent_data['comentarios']));
			$data[4] = $agent_data_comentarios;
			
			if ($agent_data['disabled'] == 0)
				$data[5] = __('Enabled');
			else
				$data[5] = __('Disabled');
			
			$table->colspan[2][3] = 2;
			$table->colspan[2][4] = 4;
			$table->colspan[2][5] = 2;
			array_push ($table->data, $data);
			unset($data);
			
			//Agent's modules
			if ($modules == null) {
				$modules = array();
			}
			else {
				$data[0] = '';
				$data[1] = '<b>'.agents_get_name ($content['id_agent'], 'upper').__(' MODULES').'</b>';
				$table->colspan[3][1] = 10;
				
				array_push ($table->data, $data);
				unset($data);
			
				$data[0] = '';
				$data[1] = '<b>'.__('Name').'</b>';
				$data[2] = '<b>'.__('Type').'</b>';
				$data[3] = '<b>'.__('Warning').'/'.'<br>'.__('Critical').'</b>';
				$data[4] = '<b>'.__('Threshold').'</b>';
				$data[5] = '<b>'.__('Group').'</b>';
				$data[6] = '<b>'.__('Description').'</b>';
				$data[7] = '<b>'.__('Interval').'</b>';
				$data[8] = '<b>'.__('Unit').'</b>';
				$data[9] = '<b>'.__('Status').'</b>';
				$data[10] = '<b>'.__('Tags').'</b>';
				
				$table->style[0] = 'width:10px';
				$table->style[1] = 'text-align: left';
				$table->style[2] = 'text-align: center';
				$table->style[3] = 'text-align: center';
				$table->style[4] = 'text-align: center';
				$table->style[5] = 'text-align: center';
				$table->style[6] = 'text-align: left';
				$table->style[7] = 'text-align: center';
				$table->style[8] = 'text-align: center';
				$table->style[9] = 'text-align: left';
				$table->style[10] = 'text-align: left';
				
				array_push ($table->data, $data);
			}
			
			foreach ($modules as $id_agent_module=>$module) {
				$sql = "SELECT * FROM tagente_modulo WHERE id_agente_modulo=$id_agent_module"; 
				$data_module = db_get_row_sql($sql);
				
				$data = array();
				
				$data[0] = '';
				
				if ($data_module['disabled'] == 0)
					$disabled = '';
				else 
					$disabled = ' (Disabled)';
				$data[1] = $data_module['nombre'].$disabled;
				$data[2] = ui_print_moduletype_icon ($data_module['id_tipo_modulo'], true);
				$data[3] = $data_module['max_warning'].'/'.$data_module['min_warning'].' <br> '.$data_module['max_critical'].'/'.$data_module['min_critical'];
				$data[4] = $data_module['module_ff_interval'];
				$data[5] = groups_get_name ($content['id_group'], true);
				$data[6] = $data_module['descripcion'];
				
				if (($data_module['module_interval'] == 0) || ($data_module['module_interval'] == ''))
					$data[7] = db_get_value('intervalo', 'tagente', 'id_agente', $content['id_agent']);
				else
					$data[7] = $data_module['module_interval'];
				
				
				$data[8] = $data_module['unit'];
				
				$module_status = db_get_row('tagente_estado', 'id_agente_modulo', $id_agent_module);
				modules_get_status($id_agent_module, $module_status['estado'], $module_status['datos'], $status, $title);
				$data[9] = ui_print_status_image($status, $title, true);
				
				$sql_tag = "SELECT name
					FROM ttag
					WHERE id_tag IN (
						SELECT id_tag
						FROM ttag_module
						WHERE id_agente_modulo = $id_agent_module)";
				$tags = db_get_all_rows_sql($sql_tag);
				if ($tags === false)
					$tags = '';
				else
					$tags = implode (",", $tags);
				
				$data[10] = $tags;
				array_push ($table->data, $data);
			}
			
			break;
		case 'group_configuration':
			$group_name = groups_get_name($content['id_group']);
			if (empty($item_title)) {
				$item_title = __('Group configuration: ').$group_name;
			}
			reporting_header_content($mini, $content, $report, $table, $item_title);
			
			$sql = "SELECT * FROM tagente WHERE id_grupo=".$content['id_group'];
			$agents_list = db_get_all_rows_sql($sql);
			if ($agents_list === false)
				$agents_list = array();
			
			$table->colspan[0][1] = 10;
			
			$i = 1;
			foreach ($agents_list as $agent) {
				$data= array ();
				
				$table->colspan[$i][3] = 2;
				$table->colspan[$i][4] = 4;
				$table->colspan[$i][5] = 2;
				$table->colspan[$i][5] = 2;
				
				$i++;
				
				//Agent's data
				$data[0] = '<b>'.__('Agent name').'</b>';
				$data[1] = '<b>'.__('Group').'</b>';
				$data[2] = '<b>'.__('SO').'</b>';
				$data[3] = '<b>'.__('IP').'</b>';
				$data[4] = '<b>'.__('Description').'</b>';
				$data[5] = '<b>'.__('Status').'</b>';
				
				array_push ($table->data, $data);
				unset($data);
				
				$sql = "SELECT * FROM tagente WHERE id_agente=".$agent['id_agente'];
				$agent_data = db_get_row_sql($sql);
				
				$data[0] = $agent_data['nombre'];
				$data[1] = ui_print_group_icon ($agent_data['id_grupo'], true, '', '', false);
				$data[2] = ui_print_os_icon ($agent_data["id_os"], true, true);
				$data[3] = $agent_data['direccion'];
				$agent_data_comentarios = strip_tags(ui_bbcode_to_html($agent_data['comentarios']));
				$data[4] = $agent_data_comentarios;
				
				if ($agent_data['disabled'] == 0)
					$data[5] = __('Enabled');
				else
					$data[5] = __('Disabled');
				
				$table->colspan[$i][3] = 2;
				$table->colspan[$i][4] = 4;
				$table->colspan[$i][5] = 2;
				$table->colspan[$i][5] = 2;
				
				$i++;
				
				array_push ($table->data, $data);
				unset($data);
				
				
				
				$modules = agents_get_modules ($agent['id_agente']);
				
				if ($modules == null) {
					$modules = array();
				}
				else {
					
					//Agent's modules
					$data[0] = '';
					$data[1] = '<b>'.agents_get_name ($agent['id_agente'], 'upper').__(' MODULES').'</b>';
					$table->colspan[$i][1] = 10;
					
					$i++;
					
					array_push ($table->data, $data);
					unset($data);
				
					$data[0] = '';
					$data[1] = '<b>'.__('Name').'</b>';
					$data[2] = '<b>'.__('Type').'</b>';
					$data[3] = '<b>'.__('Warning').'/'.'<br>'.__('Critical').'</b>';
					$data[4] = '<b>'.__('Threshold').'</b>';
					$data[5] = '<b>'.__('Group').'</b>';
					$data[6] = '<b>'.__('Description').'</b>';
					$data[7] = '<b>'.__('Interval').'</b>';
					$data[8] = '<b>'.__('Unit').'</b>';
					$data[9] = '<b>'.__('Status').'</b>';
					$data[10] = '<b>'.__('Tags').'</b>';
					
					$table->style[0] = 'width:10px';
					$table->style[1] = 'text-align: left';
					$table->style[2] = 'text-align: center';
					$table->style[3] = 'text-align: center';
					$table->style[4] = 'text-align: center';
					$table->style[5] = 'text-align: center';
					$table->style[6] = 'text-align: left';
					$table->style[7] = 'text-align: center';
					$table->style[8] = 'text-align: center';
					$table->style[9] = 'text-align: left';
					$table->style[10] = 'text-align: left';
					
					array_push ($table->data, $data);
					
					$i++;
				}
				
				foreach ($modules as $id_agent_module=>$module) {
					$sql = "SELECT *
						FROM tagente_modulo
						WHERE id_agente_modulo=$id_agent_module"; 
					$data_module = db_get_row_sql($sql);
					
					$data = array();
					
					$data[0] = '';
					
					if ($data_module['disabled'] == 0)
						$disabled = '';
					else 
						$disabled = ' (Disabled)';
					$data[1] = $data_module['nombre'].$disabled;
					$data[2] = ui_print_moduletype_icon ($data_module['id_tipo_modulo'], true);
					$data[3] = $data_module['max_warning'].'/'.$data_module['min_warning'].' <br> '.$data_module['max_critical'].'/'.$data_module['min_critical'];
					$data[4] = $data_module['module_ff_interval'];
					$data[5] = groups_get_name ($content['id_group'], true);
					$data[6] = $data_module['descripcion'];
					
					if (($data_module['module_interval'] == 0) || ($data_module['module_interval'] == ''))
						$data[7] = db_get_value('intervalo', 'tagente', 'id_agente', $content['id_agent']);
					else
						$data[7] = $data_module['module_interval'];
					
					
					$data[8] = $data_module['unit'];
					
					$module_status = db_get_row('tagente_estado', 'id_agente_modulo', $id_agent_module);
					modules_get_status($id_agent_module, $module_status['estado'], $module_status['datos'], $status, $title);
					$data[9] = ui_print_status_image($status, $title, true);
					
					$sql_tag = "SELECT name
						FROM ttag
						WHERE id_tag IN (
							SELECT id_tag
							FROM ttag_module
							WHERE id_agente_modulo = $id_agent_module)";
					$tags = db_get_all_rows_sql($sql_tag);
					if ($tags === false)
						$tags = '';
					else
						$tags = implode (",", $tags);
					
					$data[10] = $tags;
					array_push ($table->data, $data);
					
					$i++;
				}
			
			}
			break;
		case 'netflow_area':
		case 'netflow_pie':
		case 'netflow_data':
		case 'netflow_statistics':
		case 'netflow_summary':
			
			// Read the report item
			$report_id = $report['id_report'];
			$content_id = $content['id_rc'];
			$max_aggregates= $content['top_n_value'];
			$type = $content['show_graph'];
			$description = $content['description'];
			$resolution = $content['top_n'];
			$type = $content['type'];
			$period = $content['period'];
			
			// Calculate the start and end dates
			$end_date = $report['datetime'];
			$start_date = $end_date - $period;
			
			// Get item filters
			$filter = db_get_row_sql("SELECT *
				FROM tnetflow_filter
				WHERE id_sg = '" . (int)$content['text'] . "'", false, true);
			if ($description == '') {
				$description = $filter['id_name'];
			}
			
			if (empty($item_title)) {
				$item_title = $description;
			}
			
			$table->colspan[0][0] = 4;
			$table->data[0][0] = '<h4>' . $item_title . '</h4>';
			$table->colspan[1][0] = 4;
			$table->data[1][0] = netflow_draw_item ($start_date, $end_date, $resolution, $type, $filter, $max_aggregates, $server_name, 'HTML');
			break;
	}
	//Restore dbconnection
	if (($config ['metaconsole'] == 1) && $server_name != '' && defined('METACONSOLE') && $remote_connection == 1) {
		metaconsole_restore_db_force();
	}
}

/** 
 * Get the MTBF value of an agent module in a period of time. See
 * http://en.wikipedia.org/wiki/Mean_time_between_failures
 * 
 * @param int Agent module id
 * @param int Period of time to check (in seconds)
 * @param int Top date to check the values. Default current time.
 * 
 * @return float The MTBF value in the interval.
 */
function reporting_get_agentmodule_mtbf ($id_agent_module, $period = 0, $date = 0) {
	
	// Initialize variables
	if (empty ($date)) $date = get_system_time ();
	
	// Read module configuration
	$datelimit = $date - $period;
	$search_in_history_db = db_search_in_history_db($datelimit);

	$module = db_get_row_sql ('SELECT max_critical, min_critical, id_tipo_modulo
		FROM tagente_modulo
		WHERE id_agente_modulo = ' . (int) $id_agent_module);
	if ($module === false) {
		return false;
	}
	
	$critical_min = $module['min_critical'];
	$critical_max = $module['max_critical'];
	$module_type = $module['id_tipo_modulo'];
	
	// Set critical_min and critical for proc modules
	$module_type_str = modules_get_type_name ($module_type);
	if (strstr ($module_type_str, 'proc') !== false &&
		($critical_min == 0 && $critical_max == 0)) {
		$critical_min = 1;
	}
	
	// Get module data
	$interval_data = db_get_all_rows_sql ('SELECT * FROM tagente_datos 
		WHERE id_agente_modulo = ' . (int) $id_agent_module .
		' AND utimestamp > ' . (int) $datelimit .
		' AND utimestamp < ' . (int) $date .
		' ORDER BY utimestamp ASC', $search_in_history_db);
	if ($interval_data === false) $interval_data = array ();
	
	// Get previous data
	$previous_data = modules_get_previous_data ($id_agent_module, $datelimit);
	if ($previous_data !== false) {
		$previous_data['utimestamp'] = $datelimit;
		array_unshift ($interval_data, $previous_data);
	}
	
	// Get next data
	$next_data = modules_get_next_data ($id_agent_module, $date);
	if ($next_data !== false) {
		$next_data['utimestamp'] = $date;
		array_push ($interval_data, $next_data);
	}
	else if (count ($interval_data) > 0) {
		// Propagate the last known data to the end of the interval
		$next_data = array_pop ($interval_data);
		array_push ($interval_data, $next_data);
		$next_data['utimestamp'] = $date;
		array_push ($interval_data, $next_data);
	}
	
	if (count ($interval_data) < 2) {
		return false;
	}
	
	// Set initial conditions
	$critical_period = 0;
	$first_data = array_shift ($interval_data);
	$previous_utimestamp = $first_data['utimestamp'];
	if ((($critical_max > $critical_min AND ($first_data['datos'] > $critical_max OR $first_data['datos'] < $critical_min))) OR
			($critical_max <= $critical_min AND $first_data['datos'] < $critical_min)) {
		$previous_status = 1;
		$critical_count = 1;
	}
	else {
		$previous_status = 0;
		$critical_count = 0;
	}
	
	foreach ($interval_data as $data) {
		// Previous status was critical
		if ($previous_status == 1) {
			$critical_period += $data['utimestamp'] - $previous_utimestamp;
		}
		
		// Re-calculate previous status for the next data
		if ((($critical_max > $critical_min AND ($data['datos'] > $critical_max OR $data['datos'] < $critical_min))) OR
			($critical_max <= $critical_min AND $data['datos'] < $critical_min)) {
			if ($previous_status == 0) {
				$critical_count++;
			}
			$previous_status = 1;
		}
		else {
			$previous_status = 0;
		}
		
		$previous_utimestamp = $data['utimestamp'];
	}
	
	if ($critical_count == 0) {
		return 0;
	}
	
	return ($period - $critical_period) / $critical_count;
}

/** 
 * Get the MTTR value of an agent module in a period of time. See
 * http://en.wikipedia.org/wiki/Mean_time_to_recovery
 * 
 * @param int Agent module id
 * @param int Period of time to check (in seconds)
 * @param int Top date to check the values. Default current time.
 * 
 * @return float The MTTR value in the interval.
 */
function reporting_get_agentmodule_mttr ($id_agent_module, $period = 0, $date = 0) {
	
	// Initialize variables
	if (empty ($date)) $date = get_system_time ();
	
	// Read module configuration
	$datelimit = $date - $period;
	$search_in_history_db = db_search_in_history_db($datelimit);

	$module = db_get_row_sql ('SELECT max_critical, min_critical, id_tipo_modulo
		FROM tagente_modulo
		WHERE id_agente_modulo = ' . (int) $id_agent_module);
	if ($module === false) {
		return false;
	}
	
	$critical_min = $module['min_critical'];
	$critical_max = $module['max_critical'];
	$module_type = $module['id_tipo_modulo'];
	
	// Set critical_min and critical for proc modules
	$module_type_str = modules_get_type_name ($module_type);
	if (strstr ($module_type_str, 'proc') !== false &&
		($critical_min == 0 && $critical_max == 0)) {
		$critical_min = 1;
	}
	
	// Get module data
	$interval_data = db_get_all_rows_sql ('SELECT * FROM tagente_datos 
		WHERE id_agente_modulo = ' . (int) $id_agent_module .
		' AND utimestamp > ' . (int) $datelimit .
		' AND utimestamp < ' . (int) $date .
		' ORDER BY utimestamp ASC', $search_in_history_db);
	if ($interval_data === false) $interval_data = array ();
	
	// Get previous data
	$previous_data = modules_get_previous_data ($id_agent_module, $datelimit);
	if ($previous_data !== false) {
		$previous_data['utimestamp'] = $datelimit;
		array_unshift ($interval_data, $previous_data);
	}
	
	// Get next data
	$next_data = modules_get_next_data ($id_agent_module, $date);
	if ($next_data !== false) {
		$next_data['utimestamp'] = $date;
		array_push ($interval_data, $next_data);
	}
	else if (count ($interval_data) > 0) {
		// Propagate the last known data to the end of the interval
		$next_data = array_pop ($interval_data);
		array_push ($interval_data, $next_data);
		$next_data['utimestamp'] = $date;
		array_push ($interval_data, $next_data);
	}
	
	if (count ($interval_data) < 2) {
		return false;
	}
	
	// Set initial conditions
	$critical_period = 0;
	$first_data = array_shift ($interval_data);
	$previous_utimestamp = $first_data['utimestamp'];
	if ((($critical_max > $critical_min AND ($first_data['datos'] > $critical_max OR $first_data['datos'] < $critical_min))) OR
		($critical_max <= $critical_min AND $first_data['datos'] < $critical_min)) {
		$previous_status = 1;
		$critical_count = 1;
	}
	else {
		$previous_status = 0;
		$critical_count = 0;
	}
	
	foreach ($interval_data as $data) {
		// Previous status was critical
		if ($previous_status == 1) {
			$critical_period += $data['utimestamp'] - $previous_utimestamp;
		}
		
		// Re-calculate previous status for the next data
		if ((($critical_max > $critical_min AND ($data['datos'] > $critical_max OR $data['datos'] < $critical_min))) OR
			($critical_max <= $critical_min AND $data['datos'] < $critical_min)) {
			if ($previous_status == 0) {
				$critical_count++;
			}
			$previous_status = 1;
		}
		else {
			$previous_status = 0;
		}
		
		$previous_utimestamp = $data['utimestamp'];
	}
	
	if ($critical_count == 0) {
		return 0;
	}
	
	return $critical_period / $critical_count;
}

/** 
 * Get the TTO value of an agent module in a period of time.
 * 
 * @param int Agent module id
 * @param int Period of time to check (in seconds)
 * @param int Top date to check the values. Default current time.
 * 
 * @return float The TTO value in the interval.
 */
function reporting_get_agentmodule_tto ($id_agent_module, $period = 0, $date = 0) {
	
	// Initialize variables
	if (empty ($date)) $date = get_system_time ();
	
	// Read module configuration
	$datelimit = $date - $period;
	$search_in_history_db = db_search_in_history_db($datelimit);

	$module = db_get_row_sql ('SELECT max_critical, min_critical, id_tipo_modulo
		FROM tagente_modulo
		WHERE id_agente_modulo = ' . (int) $id_agent_module);
	if ($module === false) {
		return false;
	}
	
	$critical_min = $module['min_critical'];
	$critical_max = $module['max_critical'];
	$module_type = $module['id_tipo_modulo'];
	
	// Set critical_min and critical for proc modules
	$module_type_str = modules_get_type_name ($module_type);
	if (strstr ($module_type_str, 'proc') !== false &&
		($critical_min == 0 && $critical_max == 0)) {
		$critical_min = 1;
	}
	
	// Get module data
	$interval_data = db_get_all_rows_sql ('SELECT * FROM tagente_datos 
		WHERE id_agente_modulo = ' . (int) $id_agent_module .
		' AND utimestamp > ' . (int) $datelimit .
		' AND utimestamp < ' . (int) $date .
		' ORDER BY utimestamp ASC', $search_in_history_db);
	if ($interval_data === false) $interval_data = array ();
	
	// Get previous data
	$previous_data = modules_get_previous_data ($id_agent_module, $datelimit);
	if ($previous_data !== false) {
		$previous_data['utimestamp'] = $datelimit;
		array_unshift ($interval_data, $previous_data);
	}
	
	// Get next data
	$next_data = modules_get_next_data ($id_agent_module, $date);
	if ($next_data !== false) {
		$next_data['utimestamp'] = $date;
		array_push ($interval_data, $next_data);
	}
	else if (count ($interval_data) > 0) {
		// Propagate the last known data to the end of the interval
		$next_data = array_pop ($interval_data);
		array_push ($interval_data, $next_data);
		$next_data['utimestamp'] = $date;
		array_push ($interval_data, $next_data);
	}
	
	if (count ($interval_data) < 2) {
		return false;
	}
	
	// Set initial conditions
	$critical_period = 0;
	$first_data = array_shift ($interval_data);
	$previous_utimestamp = $first_data['utimestamp'];
	if ((($critical_max > $critical_min AND ($first_data['datos'] > $critical_max OR $first_data['datos'] < $critical_min))) OR
			($critical_max <= $critical_min AND $first_data['datos'] < $critical_min)) {
		$previous_status = 1;
	}
	else {
		$previous_status = 0;
	}
	
	foreach ($interval_data as $data) {
		// Previous status was critical
		if ($previous_status == 1) {
			$critical_period += $data['utimestamp'] - $previous_utimestamp;
		}
		
		// Re-calculate previous status for the next data
		if ((($critical_max > $critical_min AND ($data['datos'] > $critical_max OR $data['datos'] < $critical_min))) OR
			($critical_max <= $critical_min AND $data['datos'] < $critical_min)) {
			$previous_status = 1;
		}
		else {
			$previous_status = 0;
		}
		
		$previous_utimestamp = $data['utimestamp'];
	}
	
	return $period - $critical_period;
}

/** 
 * Get the TTR value of an agent module in a period of time.
 * 
 * @param int Agent module id
 * @param int Period of time to check (in seconds)
 * @param int Top date to check the values. Default current time.
 * 
 * @return float The TTR value in the interval.
 */
function reporting_get_agentmodule_ttr ($id_agent_module, $period = 0, $date = 0) {
	
	// Initialize variables
	if (empty ($date)) $date = get_system_time ();
	
	// Read module configuration
	$datelimit = $date - $period;
	$search_in_history_db = db_search_in_history_db($datelimit);

	$module = db_get_row_sql ('SELECT max_critical, min_critical, id_tipo_modulo
		FROM tagente_modulo
		WHERE id_agente_modulo = ' . (int) $id_agent_module);
	if ($module === false) {
		return false;
	}
	
	$critical_min = $module['min_critical'];
	$critical_max = $module['max_critical'];
	$module_type = $module['id_tipo_modulo'];
	
	// Set critical_min and critical for proc modules
	$module_type_str = modules_get_type_name ($module_type);
	if (strstr ($module_type_str, 'proc') !== false &&
		($critical_min == 0 && $critical_max == 0)) {
		$critical_min = 1;
	}
	
	// Get module data
	$interval_data = db_get_all_rows_sql ('SELECT * FROM tagente_datos 
		WHERE id_agente_modulo = ' . (int) $id_agent_module .
		' AND utimestamp > ' . (int) $datelimit .
		' AND utimestamp < ' . (int) $date .
		' ORDER BY utimestamp ASC', $search_in_history_db);
	if ($interval_data === false) $interval_data = array ();
	
	// Get previous data
	$previous_data = modules_get_previous_data ($id_agent_module, $datelimit);
	if ($previous_data !== false) {
		$previous_data['utimestamp'] = $datelimit;
		array_unshift ($interval_data, $previous_data);
	}

	// Get next data
	$next_data = modules_get_next_data ($id_agent_module, $date);
	if ($next_data !== false) {
		$next_data['utimestamp'] = $date;
		array_push ($interval_data, $next_data);
	}
	else if (count ($interval_data) > 0) {
		// Propagate the last known data to the end of the interval
		$next_data = array_pop ($interval_data);
		array_push ($interval_data, $next_data);
		$next_data['utimestamp'] = $date;
		array_push ($interval_data, $next_data);
	}
	
	if (count ($interval_data) < 2) {
		return false;
	}
	
	// Set initial conditions
	$critical_period = 0;
	$first_data = array_shift ($interval_data);
	$previous_utimestamp = $first_data['utimestamp'];
	if ((($critical_max > $critical_min AND ($first_data['datos'] > $critical_max OR $first_data['datos'] < $critical_min))) OR
			($critical_max <= $critical_min AND $first_data['datos'] < $critical_min)) {
		$previous_status = 1;
	}
	else {
		$previous_status = 0;
	}
	
	foreach ($interval_data as $data) {
		// Previous status was critical
		if ($previous_status == 1) {
			$critical_period += $data['utimestamp'] - $previous_utimestamp;
		}
		
		// Re-calculate previous status for the next data
		if ((($critical_max > $critical_min AND ($data['datos'] > $critical_max OR $data['datos'] < $critical_min))) OR
			($critical_max <= $critical_min AND $data['datos'] < $critical_min)) {
			$previous_status = 1;
		}
		else {
			$previous_status = 0;
		}
		
		$previous_utimestamp = $data['utimestamp'];
	}
	
	return $critical_period;
}

/**
 * Get all the template graphs a user can see.
 *
 * @param $id_user User id to check.
 * @param $only_names Wheter to return only graphs names in an associative array
 * or all the values.
 * @param $returnAllGroup Wheter to return graphs of group All or not.
 * @param $privileges Privileges to check in user group
 *
 * @return template graphs of a an user. Empty array if none.
 */
function reporting_template_graphs_get_user ($id_user = 0, $only_names = false, $returnAllGroup = true, $privileges = 'RR') {
	global $config;
	
	if (!$id_user) {
		$id_user = $config['id_user'];
	}
	
	$groups = users_get_groups ($id_user, $privileges, $returnAllGroup);
	
	$all_templates = db_get_all_rows_in_table ('tgraph_template', 'name');
	if ($all_templates === false)
		return array ();
	
	$templates = array ();
	foreach ($all_templates as $template) {
		if (!in_array($template['id_group'], array_keys($groups)))
			continue;
		
		if ($template["id_user"] != $id_user && $template['private'])
			continue;
		
		if ($template["id_group"] > 0)
			if (!isset($groups[$template["id_group"]])){
				continue;
			}
		
		if ($only_names) {
			$templates[$template['id_graph_template']] = $template['name'];
		}
		else {
			$templates[$template['id_graph_template']] = $template;
			$templatesCount = db_get_value_sql("SELECT COUNT(id_gs_template) FROM tgraph_source_template WHERE id_template = " . $template['id_graph_template']);
			$templates[$template['id_graph_template']]['graphs_template_count'] = $templatesCount;
		}
	}
	
	return $templates;
}

/**
 * Gets a detailed reporting of groups's events.  
 *
 * @param unknown_type $id_group Id of the group.
 * @param unknown_type $period Time period of the report.
 * @param unknown_type $date Date of the report.
 * @param unknown_type $return Whether to return or not.
 * @param unknown_type $html Whether to return HTML code or not.
 *
 * @return string Report of groups's events
 */
function reporting_get_count_events_by_agent ($id_group, $period = 0,
	$date = 0,
	$filter_event_validated = false, $filter_event_critical = false,
	$filter_event_warning = false, $filter_event_no_validated = false) {
	
	if (!is_numeric ($date)) {
		$date = strtotime ($date);
	}
	if (empty ($date)) {
		$date = get_system_time ();
	}
	
	return events_get_count_events_by_agent($id_group, $period, $date,
		$filter_event_validated, $filter_event_critical,
		$filter_event_warning, $filter_event_no_validated);
}

/**
 * Gets a detailed reporting of groups's events.  
 *
 * @param unknown_type $filter.
 * @param unknown_type $period Time period of the report.
 * @param unknown_type $date Date of the report.
 * @param unknown_type $return Whether to return or not.
 * @param unknown_type $html Whether to return HTML code or not.
 *
 * @return string Report of groups's events
 */
function reporting_get_count_events_validated_by_user ($filter, $period = 0,
	$date = 0,
	$filter_event_validated = false, $filter_event_critical = false,
	$filter_event_warning = false, $filter_event_no_validated = false) {
	
	if (!is_numeric ($date)) {
		$date = strtotime ($date);
	}
	if (empty ($date)) {
		$date = get_system_time ();
	}
	
	return events_get_count_events_validated_by_user($filter, $period, $date,
		$filter_event_validated, $filter_event_critical,
		$filter_event_warning, $filter_event_no_validated);
}

/**
 * Gets a detailed reporting of groups's events.  
 *
 * @param unknown_type $id_group Id of the group.
 * @param unknown_type $period Time period of the report.
 * @param unknown_type $date Date of the report.
 * @param unknown_type $return Whether to return or not.
 * @param unknown_type $html Whether to return HTML code or not.
 *
 * @return string Report of groups's events
 */
function reporting_get_count_events_by_criticity ($filter, $period = 0,
	$date = 0,
	$filter_event_validated = false, $filter_event_critical = false,
	$filter_event_warning = false, $filter_event_no_validated = false) {
	
	if (!is_numeric ($date)) {
		$date = strtotime ($date);
	}
	if (empty ($date)) {
		$date = get_system_time ();
	}
	
	return events_get_count_events_by_criticity($filter, $period, $date,
		$filter_event_validated, $filter_event_critical,
		$filter_event_warning, $filter_event_no_validated);
}

/**
 * Gets a detailed reporting of groups's events.  
 *
 * @param unknown_type $id_group Id of the group.
 * @param unknown_type $period Time period of the report.
 * @param unknown_type $date Date of the report.
 * @param unknown_type $return Whether to return or not.
 * @param unknown_type $html Whether to return HTML code or not.
 *
 * @return string Report of groups's events
 */
function reporting_get_count_events_validated ($filter, $period = 0,
	$date = 0,
	$filter_event_validated = false, $filter_event_critical = false,
	$filter_event_warning = false, $filter_event_no_validated = false) {
	
	if (!is_numeric ($date)) {
		$date = strtotime ($date);
	}
	if (empty ($date)) {
		$date = get_system_time ();
	}
	
	return events_get_count_events_validated($filter, $period, $date,
		$filter_event_validated, $filter_event_critical,
		$filter_event_warning, $filter_event_no_validated);
}

/**
 * Print tiny statistics of the status of one agent, group, etc.
 * 
 * @param mixed Array with the counts of the total modules, normal modules, critical modules, warning modules, unknown modules and fired alerts
 * @param bool return or echo flag
 * 
 * @return string html formatted tiny stats of modules/alerts of an agent
 */
function reporting_tiny_stats ($counts_info, $return = false, $type = 'agent', $separator = ':') {
	$out = '';
	
	// Depend the type of object, the stats will refer agents, modules...
	switch ($type) {
		case 'modules':
			$template_title['total_count'] = __('%d Total modules');
			$template_title['normal_count'] = __('%d Normal modules');
			$template_title['critical_count'] = __('%d Critical modules');
			$template_title['warning_count'] = __('%d Warning modules');
			$template_title['unknown_count'] = __('%d Unknown modules');
			break;
		case 'agent':
			$template_title['total_count'] = __('%d Total modules');
			$template_title['normal_count'] = __('%d Normal modules');
			$template_title['critical_count'] = __('%d Critical modules');
			$template_title['warning_count'] = __('%d Warning modules');
			$template_title['unknown_count'] = __('%d Unknown modules');
			$template_title['fired_count'] = __('%d Fired alerts');
			break;
		default:
			$template_title['total_count'] = __('%d Total agents');
			$template_title['normal_count'] = __('%d Normal agents');
			$template_title['critical_count'] = __('%d Critical agents');
			$template_title['warning_count'] = __('%d Warning agents');
			$template_title['unknown_count'] = __('%d Unknown agents');
			$template_title['not_init_count'] = __('%d not init agents');
			$template_title['fired_count'] = __('%d Fired alerts');
			break;
	}
	
	// Store the counts in a data structure to print hidden divs with titles
	$stats = array();
	
	if (isset($counts_info['total_count'])) {
		$not_init = isset($counts_info['notinit_count']) ? $counts_info['notinit_count'] : 0;
		$total_count = $counts_info['total_count'] - $not_init;
		$stats[] = array('name' => 'total_count', 'count' => $total_count, 'title' => sprintf($template_title['total_count'], $total_count));
	}
	
	if (isset($counts_info['normal_count'])) {
		$normal_count = $counts_info['normal_count'];
		$stats[] = array('name' => 'normal_count', 'count' => $normal_count, 'title' => sprintf($template_title['normal_count'], $normal_count));
	}
	
	if (isset($counts_info['critical_count'])) {
		$critical_count = $counts_info['critical_count'];
		$stats[] = array('name' => 'critical_count', 'count' => $critical_count, 'title' => sprintf($template_title['critical_count'], $critical_count));
	}
	
	if (isset($counts_info['warning_count'])) {
		$warning_count = $counts_info['warning_count'];
		$stats[] = array('name' => 'warning_count', 'count' => $warning_count, 'title' => sprintf($template_title['warning_count'], $warning_count));
	}
	
	if (isset($counts_info['unknown_count'])) {
		$unknown_count = $counts_info['unknown_count'];
		$stats[] = array('name' => 'unknown_count', 'count' => $unknown_count, 'title' => sprintf($template_title['unknown_count'], $unknown_count));
	}
	
	if (isset($counts_info['not_init_count'])) {
		$not_init_count = $counts_info['not_init_count'];
		$stats[] = array('name' => 'not_init_count',
			'count' => $not_init_count,
			'title' => sprintf($template_title['not_init_count'], $not_init_count));
	}
	
	if (isset($template_title['fired_count'])) {
		if (isset($counts_info['fired_count'])) {
			$fired_count = $counts_info['fired_count'];
			$stats[] = array('name' => 'fired_count', 'count' => $fired_count, 'title' => sprintf($template_title['fired_count'], $fired_count));
		}
	}
	
	$uniq_id = uniqid();
	
	foreach ($stats as $stat) {
		$params = array('id' => 'forced_title_' . $stat['name'] . '_' . $uniq_id, 
						'class' => 'forced_title_layer', 
						'content' => $stat['title'],
						'hidden' => true);
		$out .= html_print_div($params, true);
	}
	
	// If total count is less than 0, is an error. Never show negative numbers
	if ($total_count < 0) {
		$total_count = 0;
	}
	
	$out .= '<b>' . '<span id="total_count_' . $uniq_id . '" class="forced_title" style="font-size: 7pt">' . $total_count . '</span>';
	if (isset($fired_count) && $fired_count > 0)
		$out .= ' ' . $separator . ' <span class="orange forced_title" id="fired_count_' . $uniq_id . '" style="font-size: 7pt">' . $fired_count . '</span>';
	if (isset($critical_count) && $critical_count > 0)
		$out .= ' ' . $separator . ' <span class="red forced_title" id="critical_count_' . $uniq_id . '" style="font-size: 7pt">' . $critical_count . '</span>';
	if (isset($warning_count) && $warning_count > 0)
		$out .= ' ' . $separator . ' <span class="yellow forced_title" id="warning_count_' . $uniq_id . '" style="font-size: 7pt">' . $warning_count . '</span>';
	if (isset($unknown_count) && $unknown_count > 0)
		$out .= ' ' . $separator . ' <span class="grey forced_title" id="unknown_count_' . $uniq_id . '" style="font-size: 7pt">' . $unknown_count . '</span>';
	if (isset($not_init_count) && $not_init_count > 0)
		$out .= ' ' . $separator . ' <span class="blue forced_title" id="not_init_count_' . $uniq_id . '" style="font-size: 7pt">' . $not_init_count . '</span>';
	if (isset($normal_count) && $normal_count > 0)
		$out .= ' ' . $separator . ' <span class="green forced_title" id="normal_count_' . $uniq_id . '" style="font-size: 7pt">' . $normal_count . '</span>';
	
	$out .= '</b>';
	
	if ($return) {
		return $out;
	}
	else {
		echo $out;
	}
}


function reporting_network_interfaces_table ($content, $report, $mini, $item_title = "", &$table = null, &$pdf = null) {
	global $config;

	include_once($config['homedir'] . "/include/functions_custom_graphs.php");

	if (empty($item_title)) {
		$group_name = groups_get_name($content['id_group']);
		$item_title = __('Network interfaces') . " - " . sprintf(__('Group "%s"'), $group_name);
	}

	$is_html = $table !== null;
	$is_pdf = $pdf !== null;

	$ttl = $is_pdf ? 2 : 1;

	$graph_width = 900;
	$graph_height = 200;

	$datetime = $report['datetime'];
	$period = $content['period'];

	if ($is_pdf) {
		$graph_width = 800;
		$graph_height = 200;
		pdf_header_content($pdf, $content, $report, $item_title, false, $content["description"]);
	}
	else if ($is_html) {
		reporting_header_content($mini, $content, $report, $table, $item_title);

		//RUNNING
		$table->style[1] = 'text-align: right';
		
		// Put description at the end of the module (if exists)
		$table->colspan[0][1] = 2;
		$next_row = 1;
		if ($content["description"] != "") {
			$table->colspan[$next_row][0] = 3;
			$next_row++;
			$data_desc = array();
			$data_desc[0] = $content["description"];
			array_push ($table->data, $data_desc);
		}
	}

	$filter = array(
			'id_grupo' => $content['id_group'],
			'disabled' => 0
		);
	$network_interfaces_by_agents = agents_get_network_interfaces(false, $filter);

	if (empty($network_interfaces_by_agents)) {
		if ($is_pdf) {
			$pdf->addHTML(__('The group has no agents or none of the agents has any network interface'));
		}
		else if ($is_html) {
			$data = array();
			$data[0] = __('The group has no agents or none of the agents has any network interface');
			$table->colspan[$next_row][0] = 3;
			array_push ($table->data, $data);
		}
		return;
	}
	else {
		foreach ($network_interfaces_by_agents as $agent_id => $agent) {

			$table_agent = new StdCLass();
			$table_agent->width = '100%';
			$table_agent->data = array();
			$table_agent->head = array();
			$table_agent->head[0] = sprintf(__("Agent '%s'"), $agent['name']);
			$table_agent->headstyle = array();
			$table_agent->headstyle[0] = 'font-size: 16px;';
			$table_agent->style[0] = 'text-align: center';

			if ($is_pdf) {
				$table_agent->class = 'table_sla table_beauty';
				$table_agent->headstyle[0] = 'background: #373737; color: #FFF; display: table-cell; font-size: 16px; border: 1px solid grey';
			}

			$table_agent->data['interfaces'] = "";

			foreach ($agent['interfaces'] as $interface_name => $interface) {
				$table_interface = new StdClass();
				$table_interface->width = '100%';
				$table_interface->data = array();
				$table_interface->rowstyle = array();
				$table_interface->head = array();
				$table_interface->cellstyle = array();
				$table_interface->title = sprintf(__("Interface '%s' throughput graph"), $interface_name);
				$table_interface->head['ip'] = __('IP');
				$table_interface->head['mac'] = __('Mac');
				$table_interface->head['status'] = __('Actual status');
				$table_interface->style['ip'] = 'text-align: left';
				$table_interface->style['mac'] = 'text-align: left';
				$table_interface->style['status'] = 'width: 150px; text-align: center';

				if ($is_pdf) {
					$table_interface->class = 'table_sla table_beauty';
					$table_interface->titlestyle = 'background: #373737; color: #FFF; display: table-cell; font-size: 12px; border: 1px solid grey';

					$table_interface->headstyle['ip'] = 'text-align: left; background: #666; color: #FFF; display: table-cell; font-size: 11px; border: 1px solid grey';
					$table_interface->headstyle['mac'] = 'text-align: left; background: #666; color: #FFF; display: table-cell; font-size: 11px; border: 1px solid grey';
					$table_interface->headstyle['status'] = 'background: #666; color: #FFF; display: table-cell; font-size: 11px; border: 1px solid grey';

					$table_interface->style['ip'] = 'text-align: left; display: table-cell; font-size: 10px;';
					$table_interface->style['mac'] = 'text-align: left; display: table-cell; font-size: 10px;';
					$table_interface->style['status'] = 'text-align: center; display: table-cell; font-size: 10px;';
				}

				$data = array();
				$data['ip'] = !empty($interface['ip']) ? $interface['ip'] : "--";
				$data['mac'] = !empty($interface['mac']) ? $interface['mac'] : "--";
				$data['status'] = $interface['status_image'];
				$table_interface->data['data'] = $data;

				if (!empty($interface['traffic'])) {

					$only_image = !(bool)$config['flash_charts'] || $is_pdf ? true : false;
					
					$graph = custom_graphs_print(0,
						$graph_height,
						$graph_width,
						$period,
						null,
						true,
						$date,
						$only_image,
						'white',
						array_values($interface['traffic']),
						$config['homeurl'],
						array_keys($interface['traffic']),
						array_fill(0, count($interface['traffic']),"bytes/s"),
						false,
						true,
						true,
						true,
						$ttl);
					
					$table_interface->data['graph'] = $graph;
					$table_interface->colspan['graph'][0] = count($table_interface->head);
					$table_interface->cellstyle['graph'][0] = 'text-align: center;';
				}

				$table_agent->data['interfaces'] .= html_print_table($table_interface, true);
				$table_agent->colspan[$interface_name][0] = 3;
			}

			if ($is_html) {
				$table->data[$agent_id] = html_print_table($table_agent, true);
				$table->colspan[$agent_id][0] = 3;
			}
			else if ($is_pdf) {
				$html = html_print_table($table_agent, true);
				$pdf->addHTML($html);
			}
		}
	}
}

function reporting_get_agents_by_status ($data, $graph_width = 250, $graph_height = 150, $links = false) {
	global $config;
	
	if ($links == false) {
		$links = array();
	}
	
	$table_agent = html_get_predefined_table();
		
	$agent_data = array();
	$agent_data[0] = html_print_image('images/agent_critical.png', true, array('title' => __('Agents critical')));
	$agent_data[1] = "<a style='color: #bc0000;' href='" . $links['agents_critical'] . "'><b><span style='font-size: 12pt; font-weight: bold; color: #bc0000;'>".format_numeric($data['agent_critical'])."</span></b></a>";
	$agent_data[2] = html_print_image('images/agent_warning.png', true, array('title' => __('Agents warning')));
	$agent_data[3] = "<a style='color: #aba900;' href='" . $links['agents_warning'] . "'><b><span style='font-size: 12pt; font-weight: bold; color: #aba900;'>".format_numeric($data['agent_warning'])."</span></b></a>";

	$table_agent->data[] = $agent_data;
	
	$agent_data = array();
	$agent_data[0] = html_print_image('images/agent_ok.png', true, array('title' => __('Agents ok')));
	$agent_data[1] = "<a style='color: #6ec300;' href='" . $links['agents_ok'] . "'><b><span style='font-size: 12pt; font-weight: bold; color: #6ec300;'>".format_numeric($data['agent_ok'])."</span></b></a>";
	$agent_data[2] = html_print_image('images/agent_unknown.png', true, array('title' => __('Agents unknown')));
	$agent_data[3] = "<a style='color: #886666;' href='" . $links['agents_unknown'] . "'><b><span style='font-size: 12pt; font-weight: bold; color: #886666;'>".format_numeric($data['agent_unknown'])."</span></b></a>";
	$table_agent->data[] = $agent_data;
	
	$agent_data = array();
	$agent_data[0] = html_print_image('images/agent_notinit.png', true, array('title' => __('Agents not init')));
	$agent_data[1] = "<a style='color: #729fcf;' href='" . $links['agents_not_init'] . "'><b><span style='font-size: 12pt; font-weight: bold; color: #729fcf;'>".format_numeric($data['agent_not_init'])."</span></b></a>";
	$table_agent->data[] = $agent_data;
	
	$agents_data = '<fieldset class="databox tactical_set">
					<legend>' . 
						__('Agents by status') . 
					'</legend>' . 
					html_print_table($table_agent, true) . '</fieldset>';
					
	return $agents_data;
}

function reporting_get_total_agents_and_monitors ($data, $graph_width = 250, $graph_height = 150) {
	global $config;
	
	$total_agent = $data['agent_ok'] + $data['agent_warning'] + $data['agent_critical'] + $data['gent_unknown'] + $data['agent_not_init'];
	$total_module = $data['monitor_ok'] + $data['monitor_warning'] + $data['monitor_critical'] + $data['monitor_unknown'] + $data['monitor_not_init'];
			
	$table_total = html_get_predefined_table();
		
	$total_data = array();
	$total_data[0] = html_print_image('images/agent.png', true, array('title' => __('Total agents')));
	$total_data[1] = $total_agent <= 0 ? '-' : $total_agent;
	$total_data[2] = html_print_image('images/module.png', true, array('title' => __('Monitor checks')));
	$total_data[3] = $total_module <= 0 ? '-' : $total_module;
	$table_total->data[] = $total_data;
	$total_agent_module = '<fieldset class="databox tactical_set">
					<legend>' . 
						__('Total agents and monitors') . 
					'</legend>' . 
					html_print_table($table_total, true) . '</fieldset>';
					
	return $total_agent_module;	
}

function reporting_get_total_servers ($num_servers) {
	global $config;
	
	$table_node = html_get_predefined_table();
		
	$node_data = array();
	$node_data[0] = html_print_image('images/server_export.png', true, array('title' => __('Nodes')));
	$node_data[1] = "<b><span style='font-size: 12pt; font-weight: bold; color: black;'>".format_numeric($num_servers)."</span></b>";
	$table_node->data[] = $node_data;
	$node_overview = '<fieldset class="databox tactical_set">
					<legend>' . 
						__('Node overview') . 
					'</legend>' . 
					html_print_table($table_node, true) . '</fieldset>';
	return $node_overview;
}

function reporting_get_events ($data, $links = false) {
	global $config;

	$table_events->width = "100%";
	
	$table_events->data[0][0] = html_print_image('images/agent_critical.png', true, array('title' => __('Critical events')));
	$table_events->data[0][0] .= "&nbsp;&nbsp;&nbsp;"."<a style='color: #bc0000;' href='" . $links['critical'] . "'><b><span style='font-size: 12pt; font-weight: bold; color: #bc0000;'>".format_numeric($data['critical'])."</span></b></a>";

	$table_events->data[0][1] = html_print_image('images/agent_warning.png', true, array('title' => __('Warning events')));
	$table_events->data[0][1] .= "&nbsp;&nbsp;&nbsp;"."<a style='color: #aba900;' href='" . $links['warning'] . "'><b><span style='font-size: 12pt; font-weight: bold; color: #aba900;'>".format_numeric($data['warning'])."</span></b></a>";
	$table_events->data[0][2] = html_print_image('images/agent_ok.png', true, array('title' => __('OK events')));
	$table_events->data[0][2] .= "&nbsp;&nbsp;&nbsp;"."<a style='color: #6ec300;' href='" . $links['normal'] . "'><b><span style='font-size: 12pt; font-weight: bold; color: #6ec300;'>".format_numeric($data['normal'])."</span></b></a>";
	$table_events->data[0][3] = html_print_image('images/agent_unknown.png', true, array('title' => __('Unknown events')));
	$table_events->data[0][3] .= "&nbsp;&nbsp;&nbsp;"."<a style='color: #886666;' href='" . $links['unknown'] . "'><b><span style='font-size: 12pt; font-weight: bold; color: #886666;'>".format_numeric($data['unknown'])."</span></b></a>";
	$table_events->data[0][4] = html_print_image('images/agent_notinit.png', true, array('title' => __('Not init events')));
	$table_events->data[0][4] .= "&nbsp;&nbsp;&nbsp;"."<a style='color: #729fcf;' href='" . $links['not_init'] . "'><b><span style='font-size: 12pt; font-weight: bold; color: #729fcf;'>".format_numeric($data['not_init'])."</span></b></a>";
	
	$event_view = '<fieldset class="databox tactical_set">
					<legend>' . 
						__('Events by criticity') . 
					'</legend>' . 
					html_print_table($table_events, true) . '</fieldset>';
					
	return $event_view;
}

function reporting_get_last_activity() {
	global $config;
			
	// Show last activity from this user
	
	$table->width = '100%';
	$table->data = array ();
	$table->size = array ();
	$table->size[2] = '150px';
	$table->size[3] = '130px';
	$table->size[5] = '200px';
	$table->head = array ();
	$table->head[0] = __('User');
	$table->head[1] = '';
	$table->head[2] = __('Action');
	$table->head[3] = __('Date');
	$table->head[4] = __('Source IP');
	$table->head[5] = __('Comments');
	$table->title = '<span>' . __('Last activity in Pandora FMS console') . '</span>';
	
	switch ($config["dbtype"]) {
		case "mysql":
			$sql = sprintf ("SELECT id_usuario,accion,fecha,ip_origen,descripcion,utimestamp
				FROM tsesion
				WHERE (`utimestamp` > UNIX_TIMESTAMP(NOW()) - " . SECONDS_1WEEK . ") 
					AND `id_usuario` = '%s' ORDER BY `utimestamp` DESC LIMIT 5", $config["id_user"]);
			break;
		case "postgresql":
			$sql = sprintf ("SELECT \"id_usuario\", accion, fecha, \"ip_origen\", descripcion, utimestamp
				FROM tsesion
				WHERE (\"utimestamp\" > ceil(date_part('epoch', CURRENT_TIMESTAMP)) - " . SECONDS_1WEEK . ") 
					AND \"id_usuario\" = '%s' ORDER BY \"utimestamp\" DESC LIMIT 5", $config["id_user"]);
			break;
		case "oracle":
			$sql = sprintf ("SELECT id_usuario, accion, fecha, ip_origen, descripcion, utimestamp
				FROM tsesion
				WHERE ((utimestamp > ceil((sysdate - to_date('19700101000000','YYYYMMDDHH24MISS')) * (" . SECONDS_1DAY . ")) - " . SECONDS_1WEEK . ") 
					AND id_usuario = '%s') AND rownum <= 10 ORDER BY utimestamp DESC", $config["id_user"]);
			break;
	}
	
	$sessions = db_get_all_rows_sql ($sql);
	
	if ($sessions === false)
		$sessions = array (); 
	
	foreach ($sessions as $session) {
		$data = array ();
		
		switch ($config["dbtype"]) {
			case "mysql":
			case "oracle":
				$session_id_usuario = $session['id_usuario'];
				$session_ip_origen = $session['ip_origen'];
				break;
			case "postgresql":
				$session_id_usuario = $session['id_usuario'];
				$session_ip_origen = $session['ip_origen'];
				break;
		}
		
		
		$data[0] = '<strong>' . $session_id_usuario . '</strong>';
		$data[1] = ui_print_session_action_icon ($session['accion'], true);
		$data[2] = $session['accion'];
		$data[3] =  ui_print_help_tip($session['fecha'], true) . human_time_comparation($session['utimestamp'], 'tiny');
		$data[4] = $session_ip_origen;
		$data[5] = io_safe_output ($session['descripcion']);
		
		array_push ($table->data, $data);
	}

	 return html_print_table ($table, true);
	
}

function reporting_get_event_histogram ($events) {
	global $config;
	include_once ('../../include/graphs/functions_gd.php');
	$max_value = count($events);

	$ttl = 1;
	$urlImage = ui_get_full_url(false, true, false, false);

	$colors = array(
		EVENT_CRIT_MAINTENANCE => COL_MAINTENANCE,
		EVENT_CRIT_INFORMATIONAL => COL_INFORMATIONAL,
		EVENT_CRIT_NORMAL => COL_MINOR,
		EVENT_CRIT_MINOR => COL_NORMAL,
		EVENT_CRIT_WARNING => COL_WARNING,
		EVENT_CRIT_MAJOR => COL_MAJOR,
		EVENT_CRIT_CRITICAL => COL_CRITICAL
	);
					
	foreach ($events as $data) {
	
		switch ($data['criticity']) {
			case 0:
				$color = EVENT_CRIT_MAINTENANCE;
			break;
			case 1:
				$color = EVENT_CRIT_INFORMATIONAL;
			break;
			case 2:
				$color = EVENT_CRIT_NORMAL;
			break;
			case 3:
				$color = EVENT_CRIT_WARNING;
			break;
			case 4:
				$color = EVENT_CRIT_CRITICAL;
			break;
			case 5:
				$color = EVENT_CRIT_MINOR;
			break;
			case 6:
				$color = EVENT_CRIT_MAJOR;
			break;
			case 20:
				$color = EVENT_CRIT_NOT_NORMAL;
			break;
			case 34:
				$color = EVENT_CRIT_WARNING_OR_CRITICAL;
			break;
		}
		$graph_data[] = array(
			'data' => $color,
			'utimestamp' => 1
		);
	}

	$table->width = '100%';
	$table->data = array ();
	$table->size = array ();
	$table->head = array ();
	$table->title = '<span>' . __('Events info (1hr.)') . '</span>';
	$table->data[0][0] = "" ;
	
	if (!empty($graph_data)) {
		$slicebar = slicesbar_graph($graph_data, $max_value, 700, 25, $colors, $config['fontpath'], $config['round_corner'], $urlImage, $ttl);
		$table->data[0][0] = $slicebar;
	} else {
		$table->data[0][0] = __('No events');
	}
	
	$event_graph = '<fieldset class="databox tactical_set">
					<legend>' . 
						__('Events info (1hr)') . 
					'</legend>' . 
					html_print_table($table, true) . '</fieldset>';
					
	return $event_graph;
}
?>
