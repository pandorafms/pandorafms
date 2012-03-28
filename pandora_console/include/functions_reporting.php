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
require_once ($config["homedir"]."/include/functions.php");
require_once ($config["homedir"]."/include/functions_db.php");
require_once ($config["homedir"]."/include/functions_agents.php");
include_once($config["homedir"] . "/include/functions_groups.php");
require_once ('functions_graph.php');
include_once($config['homedir'] . "/include/functions_modules.php");
include_once($config['homedir'] . "/include/functions_events.php");
include_once($config['homedir'] . "/include/functions_alerts.php");
include_once($config['homedir'] . '/include/functions_users.php');
enterprise_include_once ('include/functions_metaconsole.php');
enterprise_include_once ('include/functions_inventory.php');
include_once($config['homedir'] . "/include/functions_forecast.php");
include_once($config['homedir'] . "/include/functions_ui.php");

/** 
 * Get the average value of an agent module in a period of time.
 * 
 * @param int Agent module id
 * @param int Period of time to check (in seconds)
 * @param int Top date to check the values. Default current time.
 * 
 * @return float The average module value in the interval.
 */
function reporting_get_agentmodule_data_average ($id_agent_module, $period, $date = 0) {
	global $config;
	
	// Initialize variables
	if (empty ($date)) $date = get_system_time ();
	if ((empty ($period)) OR ($period == 0)) $period = $config["sla_period"];
	$datelimit = $date - $period;	

	$id_module_type = modules_get_agentmodule_type ($id_agent_module);
	$module_type = modules_get_moduletype_name ($id_module_type);
	$uncompressed_module = is_module_uncompressed ($module_type);

	// Get module data
	$interval_data = db_get_all_rows_sql ('SELECT * FROM tagente_datos 
			WHERE id_agente_modulo = ' . (int) $id_agent_module .
			' AND utimestamp > ' . (int) $datelimit .
			' AND utimestamp < ' . (int) $date .
			' ORDER BY utimestamp ASC', true);
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
function reporting_get_agentmodule_data_max ($id_agent_module, $period, $date = 0) {
	global $config;
	
	// Initialize variables
	if (empty ($date)) $date = get_system_time ();
	if ((empty ($period)) OR ($period == 0)) $period = $config["sla_period"];
	$datelimit = $date - $period;	

	$id_module_type = modules_get_agentmodule_type ($id_agent_module);
	$module_type = modules_get_moduletype_name ($id_module_type);
	$uncompressed_module = is_module_uncompressed ($module_type);

	// Get module data
	$interval_data = db_get_all_rows_sql ('SELECT * FROM tagente_datos 
			WHERE id_agente_modulo = ' . (int) $id_agent_module .
			' AND utimestamp > ' . (int) $datelimit .
			' AND utimestamp < ' . (int) $date .
			' ORDER BY utimestamp ASC', true);
	if ($interval_data === false) $interval_data = array ();

	// Uncompressed module data
	if ($uncompressed_module) {
	
	// Compressed module data
	} else {
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
		} else if (count ($interval_data) > 0) {
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
function reporting_get_agentmodule_data_min ($id_agent_module, $period, $date = 0) {
	global $config;
	
	// Initialize variables
	if (empty ($date)) $date = get_system_time ();
	if ((empty ($period)) OR ($period == 0)) $period = $config["sla_period"];
	$datelimit = $date - $period;	

	$id_module_type = modules_get_agentmodule_type ($id_agent_module);
	$module_type = modules_get_moduletype_name ($id_module_type);
	$uncompressed_module = is_module_uncompressed ($module_type);

	// Get module data
	$interval_data = db_get_all_rows_sql ('SELECT * FROM tagente_datos 
			WHERE id_agente_modulo = ' . (int) $id_agent_module .
			' AND utimestamp > ' . (int) $datelimit .
			' AND utimestamp < ' . (int) $date .
			' ORDER BY utimestamp ASC', true);
	if ($interval_data === false) $interval_data = array ();

	// Uncompressed module data
	if ($uncompressed_module) {
		$min_necessary = 1;
	
	// Compressed module data
	} else {
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
		} else if (count ($interval_data) > 0) {
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
function reporting_get_agentmodule_data_sum ($id_agent_module, $period, $date = 0) {

	// Initialize variables
	if (empty ($date)) $date = get_system_time ();
	if ((empty ($period)) OR ($period == 0)) $period = $config["sla_period"];
	$datelimit = $date - $period;
	
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
			' ORDER BY utimestamp ASC', true);
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
	
	// Initialize variables
	if (empty ($date)) {
		$date = get_system_time ();
	}
	if ((empty ($period)) OR ($period == 0)) {
		$period = $config["sla_period"];
	}
	if ($daysWeek === null) {
		$daysWeek = array();
	}
	// Limit date to start searching data
	$datelimit = $date - $period;
	
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
	
	if ($timeFrom < $timeTo) {
		$sql .= ' AND (TIME(FROM_UNIXTIME(utimestamp)) >= "' . $timeFrom . '" AND TIME(FROM_UNIXTIME(utimestamp)) <= "'. $timeTo . '")';
	}
	elseif ($timeFrom > $timeTo) {
		$sql .= ' AND (TIME(FROM_UNIXTIME(utimestamp)) >= "' . $timeFrom . '" OR TIME(FROM_UNIXTIME(utimestamp)) <= "'. $timeTo . '")';
	}
	$sql .= ' ORDER BY utimestamp ASC';
	$interval_data = db_get_all_rows_sql ($sql, true);

	if ($interval_data === false) {
		$interval_data = array ();
	}
	
	//calculate planned downtime dates
	$id_agent = db_get_value('id_agente', 'tagente_modulo', 'id_agente_modulo', $id_agent_module);
	$sql_downtime = "SELECT id_downtime FROM tplanned_downtime_agents WHERE id_agent=$id_agent";
	$downtimes = db_get_all_rows_sql($sql_downtime); 
	if ($downtimes == false) {
		$downtimes = array();
	}
	$i = 0;
	$downtime_dates = array();
	foreach ($downtimes as $downtime) {
		$id_downtime = $downtime['id_downtime'];
		$sql_date = "SELECT date_from, date_to FROM tplanned_downtime WHERE id=$id_downtime";
		$date_downtime = db_get_row_sql($sql_date);
	
		if ($date_downtime != false) {
			$downtime_dates[$i]['date_from'] = $date_downtime['date_from'];
			$downtime_dates[$i]['date_to'] = $date_downtime['date_to'];
			$i++;
		}
	}
	/////
	
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
	
	// Initialize variables
	if (empty ($date)) {
		$date = get_system_time ();
	}
	if ((empty ($period)) OR ($period == 0)) {
		$period = $config["sla_period"];
	}
	if ($daysWeek === null) {
		$daysWeek = array();
	}
	// Limit date to start searching data
	$datelimit = $date - $period;
	
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
	$interval_data = db_get_all_rows_sql ($sql, true);

	//calculate planned downtime dates
	$id_agent = db_get_value('id_agente', 'tagente_modulo', 'id_agente_modulo', $id_agent_module);
	$sql_downtime = "SELECT id_downtime FROM tplanned_downtime_agents WHERE id_agent=$id_agent";
	$downtimes = db_get_all_rows_sql($sql_downtime); 
	if ($downtimes == false) {
		$downtimes = array();
	}
	$i = 0;
	$downtime_dates = array();
	foreach ($downtimes as $downtime) {
		$id_downtime = $downtime['id_downtime'];
		$sql_date = "SELECT date_from, date_to FROM tplanned_downtime WHERE id=$id_downtime";
		$date_downtime = db_get_row_sql($sql_date);
	
		if ($date_downtime != false) {
			$downtime_dates[$i]['date_from'] = $date_downtime['date_from'];
			$downtime_dates[$i]['date_to'] = $date_downtime['date_to'];
			$i++;
		}
	}
	/////
	
	if ($interval_data === false) {
		$interval_data = array ();
	}
	
	// Get previous data (This adds the first data if the begin of module data is after the begin time interval)
	$previous_data = modules_get_previous_data ($id_agent_module, $datelimit);
	if ($previous_data !== false) {
		$previous_data['utimestamp'] = $datelimit;
		array_unshift ($interval_data, $previous_data);
	}

	// Get next data (This adds data before the interval of the report)
	$next_data = modules_get_next_data ($id_agent_module, $date);
	if ($next_data !== false) {
		$next_data['utimestamp'] = $date;
		array_push ($interval_data, $next_data);
	}
	else if (count ($interval_data) > 0) {
		// Propagate the last known data to the end of the interval (if there is no module data at the end point)
		$next_data = array_pop ($interval_data);
		array_push ($interval_data, $next_data);
		$next_data['utimestamp'] = $date;
		array_push ($interval_data, $next_data);
	}

	// We need more or equal two points
	if (count ($interval_data) < 2) {
		return false;
	}

	//Get the percentage for the limits
	$diff = $max_value - $min_value; 
	
	// Get module type
	$id_module_type = db_get_value('id_tipo_modulo', 'tagente_modulo', 'id_agente_modulo', $id_agent_module);
	// If module is boolean don't create translation intervals (on the edge intervals)
	if ($id_module_type == 2 or $id_module_type == 6 or $id_module_type == 9 or $id_module_type == 18){
		$percent = 0;
	}
	else {
		// Getting 10% of $diff --> $percent = ($diff/100)*10, so...
		$percent = $diff / 10;
	}
	
	//Set initial conditions
	$first_data = array_shift ($interval_data);
	$previous_utimestamp = $date - $period;
	
	$previous_value = $first_data ['datos'];
	$previous_status = 0;
	
	if ($previous_value < 0) {// 4 for the Unknown value
		$previous_status = 4;
	}
	elseif ((($previous_value > ($min_value - $percent)) && ($previous_value < ($min_value + $percent))) || 
			(($previous_value > ($max_value - $percent)) && ($previous_value < ($max_value + $percent)))) {//2 when value is within the edges
		$previous_status = 2;
	}
	elseif (($previous_value >= ($min_value + $percent)) && ($previous_value <= ($max_value - $percent))) { //1 when value is OK
		$previous_status = 1;
	}
	elseif (($previous_value <= ($min_value - $percent)) || ($previous_value >= ($max_value + $percent))) { //3 when value is Wrong
		$previous_status = 3;
	}
	
	foreach ($downtime_dates as $date_dt) {
		if (($date_dt['date_from'] <= $first_data['utimestamp']) AND ($date_dt['date_to'] >= $first_data['utimestamp'])) {
			$previous_status = 1;
		}
	}

	$data_colors = array();
	$i = 0;

	foreach ($interval_data as $data) {
		$change = false;
		$value = $data['datos'];
		if ($value < 0) {// 4 for the Unknown value
			$status = 4;
		}
		elseif ((($value > ($min_value - $percent)) && ($value < ($min_value + $percent))) || 
				(($value > ($max_value - $percent)) && ($value < ($max_value + $percent)))) { //2 when value is within the edges
			$status = 2;
		}
		elseif (($value >= ($min_value + $percent)) && ($value <= ($max_value - $percent))) { //1 when value is OK
			$status = 1;
		}
		elseif (($value <= ($min_value - $percent)) || ($value >= ($max_value + $percent))) { //3 when value is Wrong
			$status = 3;
		}
		
		foreach ($downtime_dates as $date_dt) {
			if (($date_dt['date_from'] <= $data['utimestamp']) AND ($date_dt['date_to'] >= $data['utimestamp'])) {
				$status = 1;
			}
		}
		
		if ($status != $previous_status) {
			$change = true;
			$data_colors[$i]['data'] = $previous_status;
			$data_colors[$i]['utimestamp'] = $data['utimestamp'] - $previous_utimestamp;
			$i++;
			$previous_status = $status;
			$previous_utimestamp = $data['utimestamp'];
		}
	}
	if ($change == false) {
		$data_colors[$i]['data'] = $previous_status;
		$data_colors[$i]['utimestamp'] = $data['utimestamp'] - $previous_utimestamp;
	}
	
	return $data_colors;
}

/** 
 * Get general statistical info on a group
 * 
 * @param int Group Id to get info from. 0 = all
 * 
 * @return array Group statistics
 */
function reporting_get_group_stats ($id_group = 0) {
	global $config;

	$data = array ();
	$data["monitor_checks"] = 0;
	$data["monitor_not_init"] = 0;
	$data["monitor_unknown"] = 0;
	$data["monitor_ok"] = 0;
	$data["monitor_bad"] = 0; // Critical + Unknown + Warning
	$data["monitor_warning"] = 0;
	$data["monitor_critical"] = 0;
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

	$cur_time = get_system_time ();

	//Check for access credentials using check_acl. More overhead, much safer
	if (!check_acl ($config["id_user"], $id_group, "AR")) {
		return $data;
	}
	
	if ($id_group == 0) {
		$id_group = array_keys (users_get_groups ());
	}

	// -------------------------------------------------------------------
	// Server processed stats. NOT realtime (taken from tgroup_stat)
	// -------------------------------------------------------------------
	if ($config["realtimestats"] == 0){

		if (!is_array($id_group)){
			$my_group = $id_group;
			$id_group = array();
			$id_group[0] = $my_group;
		}

		foreach ($id_group as $group){
			$group_stat = db_get_all_rows_sql ("SELECT *
				FROM tgroup_stat, tgrupo
				WHERE tgrupo.id_grupo = tgroup_stat.id_group AND tgroup_stat.id_group = $group
				ORDER BY nombre");
			$data["monitor_checks"] += $group_stat[0]["modules"];
			$data["monitor_not_init"] += $group_stat[0]["non-init"];
			$data["monitor_unknown"] += $group_stat[0]["unknown"];
			$data["monitor_ok"] += $group_stat[0]["normal"];
			$data["monitor_warning"] += $group_stat[0]["warning"];
			$data["monitor_critical"] += $group_stat[0]["critical"];
			$data["monitor_alerts"] += $group_stat[0]["alerts"];
			$data["monitor_alerts_fired"] += $group_stat[0]["alerts_fired"];
			$data["monitor_alerts_fire_count"] += $group_stat[0]["alerts_fired"];
			$data["total_checks"] += $group_stat[0]["modules"];
			$data["total_alerts"] += $group_stat[0]["alerts"];
			$data["total_agents"] += $group_stat[0]["agents"];
			$data["agents_unknown"] += $group_stat[0]["agents_unknown"];
			$data["utimestamp"] = $group_stat[0]["utimestamp"];
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
		
		foreach ($id_group as $group){	
			$children = groups_get_childrens($group);

			//Show empty groups only if they have children with agents
			$group_array = array();

			foreach($children as $sub) {
				// If the group is quering previously, we ingore it
				if(!in_array($sub['id_grupo'],$covered_groups)){
					array_push($covered_groups, $sub['id_grupo']);
					array_push($group_array, $sub['id_grupo']);
				}

			}

			// Add id of this group to create the clause
			// If the group is quering previously, we ingore it
			if(!in_array($group,$covered_groups)){
				array_push($covered_groups, $group);
				array_push($group_array, $group);
			}
			
			// If there are not groups to query, we jump to nextone
			if(empty($group_array)) {
				continue;
			}
			
			$group_clause = implode(",",$group_array);

			$group_clause = "(".$group_clause.")";
			
			switch ($config["dbtype"]) {
				case "mysql":
					$data["agents_unknown"] += db_get_sql ("SELECT COUNT(*)
						FROM tagente
						WHERE id_grupo IN $group_clause AND disabled = 0 AND ultimo_contacto < NOW() - (intervalo * 2)");
					break;
				case "postgresql":
					$data["agents_unknown"] += db_get_sql ("SELECT COUNT(*)
						FROM tagente
						WHERE id_grupo IN $group_clause AND disabled = 0 AND ceil(date_part('epoch', ultimo_contacto)) < ceil(date_part('epoch', NOW())) - (intervalo * 2)");
					break;
				case "oracle":
					$data["agents_unknown"] += db_get_sql ("SELECT COUNT(*)
						FROM tagente
						WHERE id_grupo IN $group_clause AND disabled = 0 AND ultimo_contacto < CURRENT_TIMESTAMP - (intervalo * 2)");
					break;
			}

			$data["total_agents"] += db_get_sql ("SELECT COUNT(*)
					FROM tagente WHERE id_grupo IN $group_clause AND disabled = 0");

			$data["monitor_checks"] += db_get_sql ("SELECT COUNT(tagente_estado.id_agente_estado)
				FROM tagente_estado, tagente, tagente_modulo
				WHERE tagente.id_grupo IN $group_clause AND tagente.disabled = 0
					AND tagente_estado.id_agente = tagente.id_agente
					AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.disabled = 0");

			$data["total_not_init"] += db_get_sql ("SELECT COUNT(tagente_estado.id_agente_estado)
				FROM tagente_estado, tagente, tagente_modulo
				WHERE tagente.id_grupo IN $group_clause AND tagente.disabled = 0
					AND tagente_estado.id_agente = tagente.id_agente
					AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
					AND tagente_modulo.disabled = 0 AND tagente_modulo.id_tipo_modulo NOT IN (21,22,23,24)
					AND tagente_estado.utimestamp = 0");

			switch ($config["dbtype"]) {
				case "mysql":
					$data["monitor_ok"] += db_get_sql ("SELECT COUNT(tagente_estado.id_agente_estado)
						FROM tagente_estado, tagente, tagente_modulo
						WHERE tagente.id_grupo IN $group_clause AND tagente.disabled = 0
							AND tagente_estado.id_agente = tagente.id_agente
							AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
							AND tagente_modulo.disabled = 0 AND estado = 0
							AND ((UNIX_TIMESTAMP(NOW()) - tagente_estado.utimestamp) < (tagente_estado.current_interval * 2)
							OR (tagente_modulo.id_tipo_modulo IN(21,22,23,24,100)))
							AND (utimestamp > 0 OR (tagente_modulo.id_tipo_modulo IN(21,22,23,24)))");
					break;
				case "postgresql":
					$data["monitor_ok"] += db_get_sql ("SELECT COUNT(tagente_estado.id_agente_estado)
						FROM tagente_estado, tagente, tagente_modulo
						WHERE tagente.id_grupo IN $group_clause AND tagente.disabled = 0
							AND tagente_estado.id_agente = tagente.id_agente
							AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
							AND tagente_modulo.disabled = 0 AND estado = 0
							AND ((ceil(date_part('epoch', CURRENT_TIMESTAMP)) - tagente_estado.utimestamp) < (tagente_estado.current_interval * 2)
							OR (tagente_modulo.id_tipo_modulo IN(21,22,23,24,100)))
							AND (utimestamp > 0 OR (tagente_modulo.id_tipo_modulo IN(21,22,23,24)))");
					break;
				case "oracle":
					$data["monitor_ok"] += db_get_sql ("SELECT COUNT(tagente_estado.id_agente_estado)
						FROM tagente_estado, tagente, tagente_modulo
						WHERE tagente.id_grupo IN $group_clause AND tagente.disabled = 0
							AND tagente_estado.id_agente = tagente.id_agente
							AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
							AND tagente_modulo.disabled = 0 AND estado = 0
							AND ((ceil((sysdate - to_date('19700101000000','YYYYMMDDHH24MISS')) * (86400)) - tagente_estado.utimestamp) < (tagente_estado.current_interval * 2)
							OR (tagente_modulo.id_tipo_modulo IN(21,22,23,24,100)))
							AND (utimestamp > 0 OR (tagente_modulo.id_tipo_modulo IN(21,22,23,24)))");
					break;
			}

			switch ($config["dbtype"]) {
				case "mysql":
					$data["monitor_critical"] += db_get_sql ("SELECT COUNT(tagente_estado.id_agente_estado)
						FROM tagente_estado, tagente, tagente_modulo
						WHERE tagente.id_grupo IN $group_clause AND tagente.disabled = 0
							AND tagente_estado.id_agente = tagente.id_agente
							AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
							AND tagente_modulo.disabled = 0 AND estado = 1
							AND ((UNIX_TIMESTAMP(NOW()) - tagente_estado.utimestamp) < (tagente_estado.current_interval * 2) OR (tagente_modulo.id_tipo_modulo IN(21,22,23,24,100))) AND utimestamp > 0");
					break;
				case "postgresql":
					$data["monitor_critical"] += db_get_sql ("SELECT COUNT(tagente_estado.id_agente_estado)
						FROM tagente_estado, tagente, tagente_modulo
						WHERE tagente.id_grupo IN $group_clause AND tagente.disabled = 0
							AND tagente_estado.id_agente = tagente.id_agente AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
							AND tagente_modulo.disabled = 0 AND estado = 1 AND ((ceil(date_part('epoch', CURRENT_TIMESTAMP)) - tagente_estado.utimestamp) < (tagente_estado.current_interval * 2) OR (tagente_modulo.id_tipo_modulo IN(21,22,23,24,100))) AND utimestamp > 0");
					break;
				case "oracle":
					$data["monitor_critical"] += db_get_sql ("SELECT COUNT(tagente_estado.id_agente_estado)
						FROM tagente_estado, tagente, tagente_modulo
						WHERE tagente.id_grupo IN $group_clause AND tagente.disabled = 0
							AND tagente_estado.id_agente = tagente.id_agente AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
							AND tagente_modulo.disabled = 0 AND estado = 1 AND ((ceil((sysdate - to_date('19700101000000','YYYYMMDDHH24MISS')) * (86400)) - tagente_estado.utimestamp) < (tagente_estado.current_interval * 2) OR (tagente_modulo.id_tipo_modulo IN(21,22,23,24,100))) AND utimestamp > 0");
					break;
			}
			
			switch ($config["dbtype"]) {
				case "mysql":
					$data["monitor_warning"] += db_get_sql ("SELECT COUNT(tagente_estado.id_agente_estado)
						FROM tagente_estado, tagente, tagente_modulo
						WHERE tagente.id_grupo IN $group_clause AND tagente.disabled = 0 AND tagente_estado.id_agente = tagente.id_agente
							AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.disabled = 0
							AND estado = 2 AND ((UNIX_TIMESTAMP(NOW()) - tagente_estado.utimestamp) < (tagente_estado.current_interval * 2)
							OR (tagente_modulo.id_tipo_modulo IN(21,22,23,24,100))) AND utimestamp > 0");
					break;
				case "postgresql":
					$data["monitor_warning"] += db_get_sql ("SELECT COUNT(tagente_estado.id_agente_estado)
						FROM tagente_estado, tagente, tagente_modulo
						WHERE tagente.id_grupo IN $group_clause AND tagente.disabled = 0 AND tagente_estado.id_agente = tagente.id_agente
							AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.disabled = 0
							AND estado = 2 AND ((ceil(date_part('epoch', CURRENT_TIMESTAMP)) - tagente_estado.utimestamp) < (tagente_estado.current_interval * 2)
							OR (tagente_modulo.id_tipo_modulo IN(21,22,23,24,100))) AND utimestamp > 0");
					break;
				case "oracle":
					$data["monitor_warning"] += db_get_sql ("SELECT COUNT(tagente_estado.id_agente_estado)
						FROM tagente_estado, tagente, tagente_modulo
						WHERE tagente.id_grupo IN $group_clause AND tagente.disabled = 0 AND tagente_estado.id_agente = tagente.id_agente
							AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.disabled = 0
							AND estado = 2 AND ((ceil((sysdate - to_date('19700101000000','YYYYMMDDHH24MISS')) * (86400)) - tagente_estado.utimestamp) < (tagente_estado.current_interval * 2)
							OR (tagente_modulo.id_tipo_modulo IN(21,22,23,24,100))) AND utimestamp > 0");
					break;
			}

			switch ($config["dbtype"]) {
				case "mysql":
					$data["monitor_unknown"] += db_get_sql ("SELECT COUNT(tagente_estado.id_agente_estado)
						FROM tagente_estado, tagente, tagente_modulo
						WHERE tagente.id_grupo IN $group_clause AND tagente.disabled = 0 AND tagente.id_agente = tagente_estado.id_agente
							AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.disabled = 0
							AND utimestamp > 0 AND tagente_modulo.id_tipo_modulo NOT IN(21,22,23,24,100)
							AND (UNIX_TIMESTAMP(NOW()) - tagente_estado.utimestamp) >= (tagente_estado.current_interval * 2)");
					break;
				case "postgresql":
					$data["monitor_unknown"] += db_get_sql ("SELECT COUNT(tagente_estado.id_agente_estado)
						FROM tagente_estado, tagente, tagente_modulo
						WHERE tagente.id_grupo IN $group_clause AND tagente.disabled = 0 AND tagente.id_agente = tagente_estado.id_agente
							AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.disabled = 0
							AND utimestamp > 0 AND tagente_modulo.id_tipo_modulo NOT IN(21,22,23,24,100)
							AND (ceil(date_part('epoch', CURRENT_TIMESTAMP)) - tagente_estado.utimestamp) >= (tagente_estado.current_interval * 2)");
					break;
				case "oracle":
					$data["monitor_unknown"] += db_get_sql ("SELECT COUNT(tagente_estado.id_agente_estado)
						FROM tagente_estado, tagente, tagente_modulo
						WHERE tagente.id_grupo IN $group_clause AND tagente.disabled = 0 AND tagente.id_agente = tagente_estado.id_agente
							AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.disabled = 0
							AND utimestamp > 0 AND tagente_modulo.id_tipo_modulo NOT IN(21,22,23,24,100)
							AND (ceil((sysdate - to_date('19700101000000','YYYYMMDDHH24MISS')) * (86400)) - tagente_estado.utimestamp) >= (tagente_estado.current_interval * 2)");
					break;
			}

			$data["monitor_not_init"] += db_get_sql ("SELECT COUNT(tagente_estado.id_agente_estado)
				FROM tagente_estado, tagente, tagente_modulo
				WHERE tagente.id_grupo IN $group_clause AND tagente.disabled = 0 AND tagente.id_agente = tagente_estado.id_agente
					AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.disabled = 0
					AND tagente_modulo.id_tipo_modulo NOT IN (21,22,23,24) AND utimestamp = 0");

			$data["monitor_alerts"] += db_get_sql ("SELECT COUNT(talert_template_modules.id)
				FROM talert_template_modules, tagente_modulo, tagente_estado, tagente
				WHERE tagente.id_grupo IN $group_clause AND tagente_modulo.id_agente = tagente.id_agente
					AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
					AND tagente_modulo.disabled = 0 AND tagente.disabled = 0
					AND talert_template_modules.id_agent_module = tagente_modulo.id_agente_modulo");

			$data["monitor_alerts_fired"] += db_get_sql ("SELECT COUNT(talert_template_modules.id)
				FROM talert_template_modules, tagente_modulo, tagente_estado, tagente
				WHERE tagente.id_grupo IN $group_clause AND tagente_modulo.id_agente = tagente.id_agente
					AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
					AND tagente_modulo.disabled = 0 AND tagente.disabled = 0
					AND talert_template_modules.id_agent_module = tagente_modulo.id_agente_modulo AND times_fired > 0");
		}
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
		$data["monitor_health"] = format_numeric (100 - ($data["monitor_unknown"] / ($data["monitor_checks"] / 100)), 1);
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

	if (isset($data["alerts"])){
		if ($data["monitor_alerts_fired"] > 0 && $data["alerts"] > 0) {
			$data["alert_level"] = format_numeric (100 - ($data	["monitor_alerts_fired"] / ($data["alerts"] / 100)), 1);
		} else {
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
	} elseif (!is_numeric ($date)) {
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
	if (empty ($period)) {
		global $config;
		$period = $config["sla_period"];
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
				$actions = array();
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
	if (empty ($period)) {
		global $config;
		$period = $config["sla_period"];
	}
	
	$table->width = '99%';
	$table->data = array ();
	$table->head = array ();
	$table->head[1] = __('Template');
	$table->head[2] = __('Actions');
	$table->head[3] = __('Fired');
	
	
	$alerts = db_get_all_rows_sql('SELECT *
		FROM talert_template_modules AS t1
			INNER JOIN talert_templates AS t2 ON t1.id = t2.id
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
				WHERE id_alert_template_module = ' . $alert['id'] . ');');
		$data[2] = '<ul class="action_list">';
		if ($actions === false) {
			$actions = array();
		}
		foreach ($actions as $action) {
			$data[2] .= '<li>' . $action['name'] . '</li>';
		}
		$data[2] .= '</ul>';
		
		$data[3] = '<ul style="list-style-type: disc; margin-left: 10px;">';
		$firedTimes = get_module_alert_fired($id_agent_module, $alert['id'], (int) $period, (int) $date);
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
		__("other"), "", $config['homedir'] .  "/images/logo_vertical_water.png",
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
		__("other"), "", $config['homedir'] .  "/images/logo_vertical_water.png",
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
	if ($monitors_down){
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
				} else {
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
	$output = '<strong>'.__('Agents in group').': '.count ($agents).'</strong><br />';
	
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
	/* FIXME: Add compound alerts to the report. Some extra code is needed here */
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
	$n_a_string = __('N/A').'(*)';
	
	/* Show modules in agent */
	$output .= '<div class="agent_reporting">';
	$output .= '<h3 style="text-decoration: underline">'.__('Agent').' - '.agents_get_name ($id_agent).'</h3>';
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
function reporting_get_agents_detailed_event ($id_agents, $period = 0, $date = 0, $return = false) {
	$id_agents = (array)safe_int ($id_agents, 1);
	
	if (!is_numeric ($date)) {
		$date = strtotime ($date);
	}
	if (empty ($date)) {
		$date = get_system_time ();
	}
	if (empty ($period)) {
		global $config;
		$period = $config["sla_period"];
	}
	
	$table->width = '99%';
	$table->data = array ();
	$table->head = array ();
	$table->head[0] = __('Event name');
	$table->head[1] = __('Event type');
	$table->head[2] = __('Criticity');
	$table->head[3] = __('Count');
	$table->head[4] = __('Timestamp');
	
	$events = array ();
	
	foreach ($id_agents as $id_agent) {
		$event = events_get_agent ($id_agent, (int) $period, (int) $date);
		if (!empty ($event)) {
			array_push ($events, $event);
		}
	}
	
	if ($events)
	foreach ($events as $eventRow) {
		foreach ($eventRow as $event) { 
			$data = array ();
			$data[0] = io_safe_output($event['evento']);
			$data[1] = $event['event_type'];
			$data[2] = get_priority_name ($event['criticity']);
			$data[3] = $event['count_rep'];
			$data[4] = $event['time2'];
			array_push ($table->data, $data);
		}
	}

	if ($events)	
		return html_print_table ($table, $return);
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
function reporting_get_group_detailed_event ($id_group, $period = 0, $date = 0, $return = false, $html = true) {
	if (!is_numeric ($date)) {
		$date = strtotime ($date);
	}
	if (empty ($date)) {
		$date = get_system_time ();
	}
	if (empty ($period)) {
		global $config;
		$period = $config["sla_period"];
	}
	
	$table->width = '99%';
	$table->data = array ();
	$table->head = array ();
	$table->head[0] = __('Event name');
	$table->head[1] = __('Event type');
	$table->head[2] = __('Criticity');
	$table->head[3] = __('Timestamp');
	
	$events = events_get_group_events($id_group, $period, $date);
	
	if ($events) {
		foreach ($events as $event) {
			$data = array ();
			$data[0] = io_safe_output($event['evento']);
			$data[1] = $event['event_type'];
			$data[2] = get_priority_name ($event['criticity']);
			$data[3] = $event['timestamp'];
			array_push ($table->data, $data);
		}
		
		if ($html) {
			return html_print_table ($table, $return);
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
	$id_modules = (array)safe_int ($id_modules, 1);
	
	if (!is_numeric ($date)) {
		$date = strtotime ($date);
	}
	if (empty ($date)) {
		$date = get_system_time ();
	}
	if (empty ($period)) {
		global $config;
		$period = $config["sla_period"];
	}
	
	$table->width = '99%';
	$table->data = array ();
	$table->head = array ();
	$table->head[0] = __('Event name');
	$table->head[1] = __('Event type');
	$table->head[2] = __('Criticity');
	$table->head[3] = __('Count');
	$table->head[4] = __('Timestamp');
	
	$events = array ();
	
	foreach ($id_modules as $id_module) {
		$event = events_get_module ($id_module, (int) $period, (int) $date);
		if (!empty ($event)) {
			array_push ($events, $event);
		}
	}

	if ($events) {
		foreach ($events as $eventRow) {
			foreach ($eventRow as $event) {
				$data = array ();
				$data[0] = io_safe_output($event['evento']);
				$data[1] = $event['event_type'];
				$data[2] = get_priority_name ($event['criticity']);
				$data[3] = $event['count_rep'];
				$data[4] = $event['time2'];
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
	$return["modules"] = 0; //Number of modules
	$return["monitor_normal"] = 0; //Number of 'good' monitors
	$return["monitor_warning"] = 0; //Number of 'warning' monitors
	$return["monitor_critical"] = 0; //Number of 'critical' monitors
	$return["monitor_unknown"] = 0; //Number of 'unknown' monitors
	$return["monitor_alertsfired"] = 0; //Number of monitors with fired alerts
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
	
	if($filter != ''){
		$filter = 'AND ';
	}
	
	$filter = 'disabled = 0';
	
	$modules = agents_get_modules($id_agent, false, $filter, true, false);
	
	if ($modules === false) {
		return $return;
	}
	
	$now = get_system_time ();
	
	// Calculate modules for this agent
	foreach ($modules as $key => $module) {
		$return["modules"]++;
		
		$alert_status = modules_get_agentmodule_status($key, false);
		$module_status = modules_get_agentmodule_status($key, true);
		
		switch ($module_status) {
			case 0:
				$return["monitor_normal"]++;
				break;
			case 1:
				$return["monitor_critical"]++;
				break;
			case 2:
				$return["monitor_warning"]++;
				break;
			case 3:
				$return["monitor_unknown"]++;
				break;
		}
		
		if ($alert_status == 4) {
			$return["monitor_alertsfired"]++;
		}
		
	}
		
	if ($return["modules"] > 0) {
		if ($return["monitor_critical"] > 0) {
			$return["status"] = STATUS_AGENT_CRITICAL;
			$return["status_img"] = ui_print_status_image (STATUS_AGENT_CRITICAL, __('At least one module in CRITICAL status'), true);
		}
		else if ($return["monitor_warning"] > 0) {
			$return["status"] = STATUS_AGENT_WARNING;
			$return["status_img"] = ui_print_status_image (STATUS_AGENT_WARNING, __('At least one module in WARNING status'), true);
		}
		else if ($return["monitor_unknown"] > 0) {
			$return["status"] = STATUS_AGENT_DOWN;
			$return["status_img"] = ui_print_status_image (STATUS_AGENT_DOWN, __('At least one module is in UKNOWN status'), true);	
		}
		else {
			$return["status"] = STATUS_AGENT_OK;
			$return["status_img"] = ui_print_status_image (STATUS_AGENT_OK, __('All Monitors OK'), true);
		}
	}
	
	//Alert not fired is by default
	if ($return["monitor_alertsfired"] > 0) {
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
function sla_value_desc_cmp($a, $b)
{	
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
function sla_value_asc_cmp($a, $b)
{
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
	else if($content['period'] == 0) {
		$es = json_decode($content['external_source'], true);
		if($es['date'] == 0) {
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
			__("From:") . " " . date($config["date_format"], $report["datetime"]) . "<br />" .
			__("To:") . " " . date($config["date_format"], $report["datetime"] - $content['period']) . "<br />" .
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
	
	if ($mini) {
		$sizem = '1.5';
		$sizgraph_w = '350';
		$sizgraph_h = '100';
	}
	else {
		$sizem = '3';
		$sizgraph_w = '750';
		$sizgraph_h = '230';
	}
	
	$server_name = $content ['server_name'];
	if (($config ['metaconsole'] == 1) && $server_name != '') {
		$connection = metaconsole_get_connection($server_name);
		if (!metaconsole_load_external_db($connection)) {
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
	
	
	switch ($content["type"]) {
		case 1:
		case 'simple_graph':
			reporting_header_content($mini, $content, $report, $table, __('Simple graph'),
				ui_print_truncate_text($agent_name, 75, false).' <br> ' . ui_print_truncate_text($module_name, 75, false));
			
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
				$report["datetime"], '', 0, 0, true, true);
			
			array_push ($table->data, $data);
			
			break;		
		case 'projection_graph':
			reporting_header_content($mini, $content, $report, $table, __('Projection graph'),
				ui_print_truncate_text($agent_name, 75, false).' <br> ' . ui_print_truncate_text($module_name, 75, false));
			
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
			
			$output_projection = forecast_projection_graph($content['id_agent_module'], $content['period'], $content['top_n_value']);
			
			// If projection doesn't have data then don't draw graph
			if ($output_projection ==  NULL){
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
				'',
				1,
				// Important parameter, this tell to graphic_combined_module function that is a projection graph
				$output_projection,
				$content['top_n_value']
				);			
			array_push ($table->data, $data);			
			break;
		case 'prediction_date':
			reporting_header_content($mini, $content, $report, $table, __('Prediction date'),
				ui_print_truncate_text($agent_name, 75, false).' <br> ' . ui_print_truncate_text($module_name, 75, false));
			
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
			reporting_header_content($mini, $content, $report, $table, __('Simple baseline graph'),
				ui_print_truncate_text($agent_name, 65, false).' <br> ' . ui_print_truncate_text($module_name, 65, false));
			
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
				$report["datetime"], '', true, 0, true, true);
				
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
			$graph = db_get_row ("tgraph", "id_graph", $content['id_gs']);
			
			reporting_header_content($mini, $content, $report, $table, __('Custom graph'),
				ui_print_truncate_text($graph['name'], 25, false));
			
			//RUNNING
			// Put description at the end of the module (if exists)
			
			$table->colspan[2][0] = 3;
			if ($content["description"] != ""){
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			
			$result = db_get_all_rows_field_filter ("tgraph_source", "id_graph", $content['id_gs']);
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
			$data = array ();
			
			$urlImage = ui_get_full_url(false);
			
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
				true,
				$urlImage);
			array_push ($table->data, $data);
	
			break;
		case 3:
		case 'SLA':
			reporting_header_content($mini, $content, $report, $table, __('S.L.A.'));
			
			$show_graph = $content['show_graph'];
			//RUNNING
			$table->style[1] = 'text-align: right';
			
			// Put description at the end of the module (if exists)

			$table->colspan[1][0] = 3;
			if ($content["description"] != ""){
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			
			$slas = db_get_all_rows_field_filter ('treport_content_sla_combined',
				'id_report_content', $content['id_rc']);
			if ($slas === false) {
				$data = array ();
				$table->colspan[2][0] = 3;
				$data[0] = __('There are no SLAs defined');
				array_push ($table->data, $data);
				$slas = array ();
				break;
			}
			elseif ($show_graph == 0 || $show_graph == 1) {
				$table1->width = '99%';
				$table1->data = array ();
				$table1->head = array ();
				$table1->head[0] = __('Agent');
				$table1->head[1] = __('Module');
				$table1->head[2] = __('Max/Min Values');
				$table1->head[3] = __('SLA Limit');
				$table1->head[4] = __('Value');
				$table1->head[5] = __('Status');
				$table1->style[0] = 'text-align: left';
				$table1->style[1] = 'text-align: left';
				$table1->style[2] = 'text-align: right';
				$table1->style[3] = 'text-align: right';
				$table1->style[4] = 'text-align: right';
				$table1->style[5] = 'text-align: right';
			}
			
			$data_graph = array ();
			$data_graph[__('Inside limits')] = 0;
			$data_graph[__('Out of limits')] = 0;
			$data_graph[__('On the edge')] = 0;
			$data_graph[__('Unknown')] = 0;
			
			$data_horin_graph = array ();
			$data_horin_graph[__('Inside limits')]['g'] = 0;
			$data_horin_graph[__('Out of limits')]['g'] = 0;
			$data_horin_graph[__('On the edge')]['g'] = 0;
			$data_horin_graph[__('Unknown')]['g'] = 0;
			
			$sla_failed = false;
			$total_SLA = 0;
			$total_result_SLA = 'ok';
			foreach ($slas as $sla) {
				$server_name = $sla ['server_name'];
				//Metaconsole connection
				if (($config ['metaconsole'] == 1) && $server_name != '') {
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
				
				if ($sla_value === false) {
					if ($total_result_SLA != 'fail')
						$total_result_SLA = 'unknown';
				}
				else if ($sla_value >= $sla['sla_limit']) {
					if ($total_result_SLA == 'ok')
						$total_result_SLA = 'ok';
				}
				else {
					$total_result_SLA = 'fail';
				}
				
				//Fill the array data_graph for the pie graph
				if ($sla_value === false) {
					$data_graph[__('Unknown')]++;
					$data_horin_graph[__('Unknown')]['g']++;
				}
				else if ($sla_value <= ($sla['sla_limit']+10) && $sla_value >= ($sla['sla_limit']-10)) {
					$data_graph[__('On the edge')]++;
					$data_horin_graph[__('On the edge')]['g']++;
				}
				else if ($sla_value > ($sla['sla_limit']+10)) {
					$data_graph[__('Inside limits')]++;
					$data_horin_graph[__('Inside limits')]['g']++;
				}
				else if ($sla_value < ($sla['sla_limit']-10)) {
					$data_graph[__('Out of limits')]++;
					$data_horin_graph[__('Out of limits')]['g']++;
				}
				
				//Do not show right modules if 'only_display_wrong' is active
				if ($content['only_display_wrong'] == 1 && $sla_value >= $sla['sla_limit']) continue;
				
				$total_SLA += $sla_value;
				
				if ($show_graph == 0 || $show_graph == 1) {
					$data = array ();
					$data[0] = modules_get_agentmodule_agent_name ($sla['id_agent_module']);
					$data[1] = modules_get_agentmodule_name ($sla['id_agent_module']);
					$data[2] = $sla['sla_max'].'/';
					$data[2] .= $sla['sla_min'];
					$data[3] = $sla['sla_limit'];
					
					if ($sla_value === false) {
						$data[4] = '<span style="font: bold '.$sizem.'em Arial, Sans-serif; color: #0000FF;">';
						$data[5] = '<span style="font: bold '.$sizem.'em Arial, Sans-serif; color: #736F6E;">'.__('Unknown').'</span>';
					}
					else {
						if ($sla_value >= $sla['sla_limit']) {
							$data[4] = '<span style="font: bold '.$sizem.'em Arial, Sans-serif; color: #000000;">';
							$data[5] = '<span style="font: bold '.$sizem.'em Arial, Sans-serif; color: #000000;">'.__('OK').'</span>';
						}
						else {
							$sla_failed = true;
							$data[4] = '<span style="font: bold '.$sizem.'em Arial, Sans-serif; color: #ff0000;">';
							$data[5] = '<span style="font: bold '.$sizem.'em Arial, Sans-serif; color: #ff0000;">'.__('Fail').'</span>';
						}
						$data[4] .= format_numeric ($sla_value, 2). "%";
					}
					$data[4] .= "</span>";
					// This column will be used temporary for sort data
					$data[6] = format_numeric ($sla_value, 2);
					
					array_push ($table1->data, $data);
				}
				if ($config ['metaconsole'] == 1) {
					//Restore db connection
					metaconsole_restore_db();
				}
			} 

			// SLA items sorted descending ()
			if ($content['top_n'] == 2){
				usort($table1->data, "sla_value_desc_cmp");
			}
			// SLA items sorted ascending
			else if ($content['top_n'] == 1){
				usort($table1->data, "sla_value_asc_cmp");				
			}
			
			// Delete temporary column used to sort SLA data
			for ($i=0; $i < count($table1->data); $i++) {
				unset($table1->data[$i][6]);		
			}						
			
			$table->colspan[2][0] = 3;
			if ($show_graph == 0 || $show_graph == 1) {
				$data = array();
				$data[0] = html_print_table($table1, true);
				array_push ($table->data, $data);
			}

			$table->colspan[3][0] = 2;
			$data = array();
			$data_pie_graph = json_encode ($data_graph);
			if (($show_graph == 1 || $show_graph == 2) && !empty($slas)) {
				$data[0] = pie3d_graph(false, $data_graph,
					500, 150, __("other"), "", $config['homedir'] .  "/images/logo_vertical_water.png",
		$config['fontpath'], $config['font_size']); 
				
				
				//Print resume
				$table_resume = null;
				$table_resume->head[0] = __('Resume Value');
				$table_resume->head[1] = __('Status');
				if ($total_result_SLA == 'ok') {
					$table_resume->data[0][0] = '<span style="font: bold '.$sizem.'em Arial, Sans-serif; color: #000000;">';
					$table_resume->data[0][1] = '<span style="font: bold '.$sizem.'em Arial, Sans-serif; color: #000000;">'.__('OK').'</span>';
				}
				if ($total_result_SLA == 'fail') {
					$table_resume->data[0][0] = '<span style="font: bold '.$sizem.'em Arial, Sans-serif; color: #ff0000;">';
					$table_resume->data[0][1] = '<span style="font: bold '.$sizem.'em Arial, Sans-serif; color: #ff0000;">'.__('Fail').'</span>';
				}
				if ($total_result_SLA == 'unknown') {
					$table_resume->data[0][0] = '<span style="font: bold '.$sizem.'em Arial, Sans-serif; color: #0000FF;">';
					$table_resume->data[0][1] = '<span style="font: bold '.$sizem.'em Arial, Sans-serif; color: #736F6E;">'.__('Unknown').'</span>';
				}
				$table_resume->data[0][0] .= (int)($total_SLA / count($slas));
				$table_resume->data[0][0] .= "%</span>";
				
				$data[1] = html_print_table($table_resume, true);
				
				
				array_push ($table->data, $data);
				
				//Display horizontal bar graphs
				$days = array ('monday' => $content['monday'], 'tuesday' => $content['tuesday'],
				'wednesday' => $content['wednesday'], 'thursday' => $content['thursday'],
				'friday' => $content['friday'], 'saturday' => $content['saturday'], 'sunday' => $content['sunday']);
				$daysWeek = json_encode ($days);
				
				$table2->width = '99%';
				$table2->style[0] = 'text-align: right';
				$table2->data = array ();
				foreach ($slas as $sla) {
					$data = array();
					$data[0] = modules_get_agentmodule_agent_name ($sla['id_agent_module']);
					$data[0] .= "<br>";
					$data[0] .= modules_get_agentmodule_name ($sla['id_agent_module']);
					
					$data[1] = graph_sla_slicebar ($sla['id_agent_module'], $content['period'],
						$sla['sla_min'], $sla['sla_max'], $report["datetime"], $content, $content['time_from'],
						$content['time_to'], 550, 25,'');
					
					array_push ($table2->data, $data);
				}
				$table->colspan[4][0] = 3;
				$data = array();
				$data[0] = html_print_table($table2, true);
				array_push ($table->data, $data);
			}
			break;
		case 6:
		case 'monitor_report':
			reporting_header_content($mini, $content, $report, $table, __('Monitor report'),
				ui_print_truncate_text($agent_name, 70, false).' <br> '.ui_print_truncate_text($module_name, 70, false));
			
			//RUNNING
			
			// Put description at the end of the module (if exists)
			if ($content["description"] != ""){
				$table->colspan[1][0] = 3;
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			
			$data = array ();
			$monitor_value = reporting_get_agentmodule_sla ($content['id_agent_module'], $content['period'], 1, false, $report["datetime"]);
			if ($monitor_value === false) {
				$monitor_value = __('Unknown');
			}
			else {
				$monitor_value = format_numeric ($monitor_value);
			}
			$data[0] = '<p style="font: bold '.$sizem.'em Arial, Sans-serif; color: #000000;">';
			$data[0] .= $monitor_value.' % ' . html_print_image("images/b_green.png", true, array("height" => "32", "width" => "32")) . '</p>';
			if ($monitor_value !== __('Unknown')) {
				$monitor_value = format_numeric (100 - $monitor_value, 2) ;
			}
			$data[1] = '<p style="font: bold '.$sizem.'em Arial, Sans-serif; color: #ff0000;">';
			$data[1] .= $monitor_value.' % ' . html_print_image("images/b_red.png", true, array("height" => "32", "width" => "32")) . '</p>';
			array_push ($table->data, $data);
			
			break;
		case 7:
		case 'avg_value':
			reporting_header_content($mini, $content, $report, $table, __('Avg. Value'),
				ui_print_truncate_text($agent_name, 75, false).' <br> '.ui_print_truncate_text($module_name, 75, false));
			
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
			reporting_header_content($mini, $content, $report, $table, __('Max. Value'),
				ui_print_truncate_text($agent_name, 75, false).' <br> ' . ui_print_truncate_text($module_name, 75, false));
			
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
			$value = format_numeric (reporting_get_agentmodule_data_max ($content['id_agent_module'], $content['period'], $report["datetime"]));
			$unit = db_get_value('unit', 'tagente_modulo', 'id_agente_modulo', $content ['id_agent_module']);
			$data[0] = '<p style="font: bold '.$sizem.'em Arial, Sans-serif; color: #000000;">' .
				format_for_graph($value, 2) . " " . $unit .'</p>';
			array_push ($table->data, $data);
			
			break;
		case 9:
		case 'min_value':
			reporting_header_content($mini, $content, $report, $table, __('Min. Value'),
				ui_print_truncate_text($agent_name, 75, false).' <br> '.ui_print_truncate_text($module_name, 75, false));
			
			//RUNNING
			
			// Put description at the end of the module (if exists)
			$table->colspan[0][0] = 2;
			if ($content["description"] != ""){
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			
			$data = array ();
			$table->colspan[1][0] = 2;
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
			reporting_header_content($mini, $content, $report, $table, __('Summatory'),
				ui_print_truncate_text($agent_name, 75, false).' <br> '.ui_print_truncate_text($module_name, 75, false));
			
			//RUNNING
			
			// Put description at the end of the module (if exists)
			$table->colspan[0][0] = 2;
			if ($content["description"] != ""){
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			
			$data = array ();
			$table->colspan[1][0] = 2;
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
			reporting_header_content($mini, $content, $report, $table, __('Agent detailed event'),
				ui_print_truncate_text(agents_get_name($content['id_agent']), 75, false));
			
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
			$data[0] = reporting_get_agents_detailed_event ($content['id_agent'], $content['period'], $report["datetime"], true);
			array_push ($table->data, $data);
			break;
		case 'text':
			reporting_header_content($mini, $content, $report, $table, __('Text'),
				"", "");
			
			if ($content["description"] != ""){
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			$data[0] = html_entity_decode($content['text']);
			array_push($table->data, $data);
			$table->colspan[2][0] = 2;
			break;
		case 'sql':
			reporting_header_content($mini, $content, $report, $table, __('SQL'),
				"", "");
			
			// Put description at the end of the module (if exists)
			$table->colspan[0][0] = 2;
			if ($content["description"] != ""){
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			
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
			
			if($sql != '') {
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
		case 'sql_graph_pie':
		case 'sql_graph_vbar':
		case 'sql_graph_hbar':
			reporting_header_content($mini, $content, $report, $table, __('User defined graph') . " (".__($content["type"])  .")",
				"", "");
			
			// Put description at the end of the module (if exists)
			$table->colspan[0][0] = 2;
			if ($content["description"] != ""){
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			
			$table2->class = 'databox';
			$table2->width = '100%';
			
			//Create the head
			$table2->head = array();
			if ($content['header_definition'] != '') {
				$table2->head = explode('|', $content['header_definition']);
			}
			
			$data = array ();
			
			$data[0] = graph_custom_sql_graph($content["id_rc"], $sizgraph_w, 200, $content["type"], true);

			array_push($table->data, $data);
			break;

		case 'event_report_group':
			reporting_header_content($mini, $content, $report, $table, __('Group detailed event'),
				ui_print_truncate_text(groups_get_name($content['id_group'], true), 60, false));
			
			// Put description at the end of the module (if exists)
			$table->colspan[1][0] = 3;
			if ($content["description"] != "") {
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			
			$data = array ();
			$table->colspan[2][0] = 3;
			$data[0] = reporting_get_group_detailed_event($content['id_group'], $content['period'], $report["datetime"], true);
			array_push ($table->data, $data);
			break;

		case 'event_report_module':
			reporting_header_content($mini, $content, $report, $table, __('Module detailed event'),
				ui_print_truncate_text($agent_name, 70, false).' <br> ' . ui_print_truncate_text($module_name, 70, false));
			
			// Put description at the end of the module (if exists)
			$table->colspan[1][0] = 3;
			if ($content["description"] != ""){
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			
			$data = array ();
			$table->colspan[2][0] = 3;
			$data[0] = reporting_get_module_detailed_event($content['id_agent_module'], $content['period'], $report["datetime"], true);
			array_push ($table->data, $data);
			break;
		case 'alert_report_module':
			reporting_header_content($mini, $content, $report, $table, __('Alert report module'),
				ui_print_truncate_text($agent_name, 70, false).' <br> '.ui_print_truncate_text($module_name, 70, false));
			
			// Put description at the end of the module (if exists)
			$table->colspan[1][0] = 3;
			if ($content["description"] != ""){
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
			reporting_header_content($mini, $content, $report, $table, __('Alert report agent'),
				ui_print_truncate_text($agent_name, 70, false));
			
			// Put description at the end of the module (if exists)
			$table->colspan[1][0] = 3;
			if ($content["description"] != ""){
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
			reporting_header_content($mini, $content, $report, $table, __('Import text from URL'),
				ui_print_truncate_text($content["external_source"], 70, false));
			
			// Put description at the end of the module (if exists)
			$table->colspan[1][0] = 3;
			if ($content["description"] != ""){
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			
			$data = array();
			$table->colspan[2][0] = 3;
			$data[0] = '<iframe id="item_' . $content['id_rc'] . '" src ="' . $content["external_source"] . '" width="100%" height="100%"></iframe>';
			$data[0] .= '<script>
				$(document).ready (function () {
					$("#item_' . $content['id_rc'] . '").height($(document.body).height() + 0);
			});</script>';
			
			array_push ($table->data, $data);
			break;
		case 'database_serialized':
			reporting_header_content($mini, $content, $report, $table, __('Serialize data'),
				ui_print_truncate_text($module_name, 75, false));
			
			// Put description at the end of the module (if exists)
			$table->colspan[1][0] = 3;
			if ($content["description"] != ""){
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			
			$table2->class = 'databox';
			$table2->width = '100%';
			
			//Create the head
			$table2->head = array();
			if ($content['header_definition'] != '') {
				$table2->head = explode('|', $content['header_definition']);
			}
			array_unshift($table2->head, __('Date'));
			
			$datelimit = $report["datetime"] - $content['period'];
		
			$result = db_get_all_rows_sql('SELECT *
                                FROM tagente_datos
                                WHERE id_agente_modulo = ' . $content['id_agent_module'] . '
                                        AND utimestamp > ' . $datelimit . '
                                        AND utimestamp <= ' . $report["datetime"]);
			
			// Adds string data if there is no numeric data	
			if ((count($result) < 0) or (!$result)){ 
				$result = db_get_all_rows_sql('SELECT *
					FROM tagente_datos_string
					WHERE id_agente_modulo = ' . $content['id_agent_module'] . '
						AND utimestamp > ' . $datelimit . '
						AND utimestamp <= ' . $report["datetime"]);
			} 
			if ($result === false) {
				$result = array();
			}
			
			$table2->data = array();
			foreach ($result as $row) {
				$date = date ($config["date_format"], $row['utimestamp']);
				$serialized = $row['datos']; 
				$rowsUnserialize = explode($content['line_separator'], $serialized);
				foreach ($rowsUnserialize as $rowUnser) {
					$columnsUnserialize = explode($content['column_separator'], $rowUnser);
					array_unshift($columnsUnserialize, $date);
					array_push($table2->data, $columnsUnserialize);
				} 
			}
			
			$cellContent = html_print_table($table2, true);
			array_push($table->data, array($cellContent));
			$table->colspan[1][0] = 2;
			break;
		case 'TTRT':
			reporting_header_content($mini, $content, $report, $table, __('TTRT'),
				ui_print_truncate_text($agent_name, 70, false).' <br> '.ui_print_truncate_text($module_name, 70, false));
			
			// Put description at the end of the module (if exists)
			$table->colspan[1][0] = 3;
			if ($content["description"] != ""){
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			
			$data = array ();
			$table->colspan[2][0] = 3;
			$ttr = reporting_get_agentmodule_ttr ($content['id_agent_module'], $content['period'], $report["datetime"]);
			if ($ttr === false) {
				$ttr = __('Unknown');
			} else if ($ttr != 0) {
				$ttr = human_time_description_raw ($ttr);
			}
			
			$data = array ();
			$table->colspan[2][0] = 3;
			$data[0] = '<p style="font: bold '.$sizem.'em Arial, Sans-serif; color: #000000;">'.$ttr.'</p>';
			array_push ($table->data, $data);
			break;
		case 'TTO':
			reporting_header_content($mini, $content, $report, $table, __('TTO'),
				ui_print_truncate_text($agent_name, 70, false).' <br> '.ui_print_truncate_text($module_name, 70, false));
			
			// Put description at the end of the module (if exists)
			$table->colspan[1][0] = 3;
			if ($content["description"] != ""){
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			
			$data = array ();
			$table->colspan[2][0] = 3;
			$tto = reporting_get_agentmodule_tto ($content['id_agent_module'], $content['period'], $report["datetime"]);
			if ($tto === false) {
				$tto = __('Unknown');
			} else if ($tto != 0) {
				$tto = human_time_description_raw ($tto);
			}
			
			$data = array ();
			$table->colspan[2][0] = 3;
			$data[0] = '<p style="font: bold '.$sizem.'em Arial, Sans-serif; color: #000000;">'.$tto.'</p>';
			array_push ($table->data, $data);
			break;
		case 'MTBF':
			reporting_header_content($mini, $content, $report, $table, __('MTBF'),
				ui_print_truncate_text($agent_name, 70, false).' <br> '.ui_print_truncate_text($module_name, 70, false));
			
			// Put description at the end of the module (if exists)
			$table->colspan[1][0] = 3;
			if ($content["description"] != ""){
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			
			$data = array ();
			$table->colspan[2][0] = 3;
			$mtbf = reporting_get_agentmodule_mtbf ($content['id_agent_module'], $content['period'], $report["datetime"]);
			if ($mtbf === false) {
				$mtbf = __('Unknown');
			} else if ($mtbf != 0) {
				$mtbf = human_time_description_raw ($mtbf);
			}
			
			$data = array ();
			$table->colspan[2][0] = 3;
			$data[0] = '<p style="font: bold '.$sizem.'em Arial, Sans-serif; color: #000000;">'.$mtbf.'</p>';
			array_push ($table->data, $data);
			break;
		case 'MTTR':
			reporting_header_content($mini, $content, $report, $table, __('MTTR'),
				ui_print_truncate_text($agent_name, 70, false).' <br> '.ui_print_truncate_text($module_name, 70, false));
			
			// Put description at the end of the module (if exists)
			$table->colspan[1][0] = 3;
			if ($content["description"] != ""){
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			
			$data = array ();
			$table->colspan[2][0] = 3;
			$mttr = reporting_get_agentmodule_mttr ($content['id_agent_module'], $content['period'], $report["datetime"]);
			if ($mttr === false) {
				$mttr = __('Unknown');
			} else if ($mttr != 0) {
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
			
			reporting_header_content($mini, $content, $report, $table, __('Group report').': "'.$group_name.'"');
			
			$data = array ();
			$table->colspan[0][0] = 7;
			$table->colspan[1][1] = 3;
			$table->colspan[1][4] = 3;
			$table->colspan[2][1] = 3;
			$table->colspan[2][4] = 3;
			$table->colspan[5][1] = 3;
			$table->colspan[5][4] = 3;
			$table->colspan[6][1] = 3;
			$table->colspan[6][4] = 3;
			$table->colspan[7][1] = 6;
			$table->colspan[8][1] = 6;
			
			$data = array();
			$data[0] = '';
			$data[1] = "<div class='cellBold cellCenter'>".__('Total')."</div>";
			$data[4] = "<div class='cellBold cellCenter'>".__('Unknown')."</div>";
			array_push ($table->data, $data);
			
			$data = array();
			$data[0] = "<div class='cellBold'>".__('Agents')."</div>";
			$data[1] = "<div class='cellBold cellCenter cellWhite cellBorder1 cellBig'>".$group_stats['total_agents']."</div>";
			$data[4] = "<div class='cellBold cellCenter cellUnknown cellBorder1 cellBig'>".$group_stats['agents_unknown']."</div>";
			array_push ($table->data, $data);	
			
			$data = array();
			$data[0] = '';
			$data[1] = "<div class='cellBold cellCenter'>".__('Total')."</div>";
			$data[2] = "<div class='cellBold cellCenter'>".__('Normal')."</div>";
			$data[3] = "<div class='cellBold cellCenter'>".__('Critical')."</div>";
			$data[4] = "<div class='cellBold cellCenter'>".__('Warning')."</div>";
			$data[5] = "<div class='cellBold cellCenter'>".__('Unknown')."</div>";
			$data[6] = "<div class='cellBold cellCenter'>".__('Not init')."</div>";
			array_push ($table->data, $data);
			
			$data = array();
			$data[0] = "<div class='cellBold'>".__('Monitors')."</div>";
			$data[1] = "<div class='cellBold cellCenter cellWhite cellBorder1 cellBig'>".$group_stats['monitor_checks']."</div>";
			$data[2] = "<div class='cellBold cellCenter cellNormal cellBorder1 cellBig'>".$group_stats['monitor_ok']."</div>";
			$data[3] = "<div class='cellBold cellCenter cellCritical cellBorder1 cellBig'>".$group_stats['monitor_critical']."</div>";
			$data[4] = "<div class='cellBold cellCenter cellWarning cellBorder1 cellBig'>".$group_stats['monitor_warning']."</div>";
			$data[5] = "<div class='cellBold cellCenter cellUnknown cellBorder1 cellBig'>".$group_stats['monitor_unknown']."</div>";
			$data[6] = "<div class='cellBold cellCenter cellNotInit cellBorder1 cellBig'>".$group_stats['monitor_not_init']."</div>";
			array_push ($table->data, $data);
			
			$data = array();
			$data[0] = '';
			$data[1] = "<div class='cellBold cellCenter'>".__('Defined')."</div>";
			$data[4] = "<div class='cellBold cellCenter'>".__('Fired')."</div>";
			array_push ($table->data, $data);
			
			$data = array();
			$data[0] = "<div class='cellBold'>".__('Alerts')."</div>";
			$data[1] = "<div class='cellBold cellCenter cellWhite cellBorder1 cellBig'>".$group_stats['monitor_alerts']."</div>";
			$data[4] = "<div class='cellBold cellCenter cellAlert cellBorder1 cellBig'>".$group_stats['monitor_alerts_fired']."</div>";
			array_push ($table->data, $data);
			
			$data = array();
			$data[0] = '';
			$data[1] = "<div class='cellBold cellCenter'>".__('Last 8 hours')."</div>";
			array_push ($table->data, $data);
			
			$data = array();
			$data[0] = "<div class='cellBold'>".__('Events')."</div>";
			$data[1] = "<div class='cellBold cellCenter cellWhite cellBorder1 cellBig'>".count($events)."</div>";
			array_push ($table->data, $data);
			
			break;
		case 'general':
			reporting_header_content($mini, $content, $report, $table, __('General'));
			
			$group_by_agent = $content['group_by_agent'];
			$order_uptodown = $content['order_uptodown'];
		
			$table->style[1] = 'text-align: right';
			
			// Put description at the end of the module (if exists)
			$table->colspan[1][0] = 3;
			if ($content["description"] != ""){
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			
			switch ($group_by_agent) {
				//0 means not group by agent
				case 0:
					$sql = sprintf("select id_agent_module, server_name from treport_content_item
						where id_report_content = %d", $content['id_rc']);
					
					$generals = db_process_sql ($sql);
					if ($generals === false) {
						$data = array ();
						$table->colspan[2][0] = 3;
						$data[0] = __('There are no Agent/Modules defined');
						array_push ($table->data, $data);
						break;
					}
					
					$table1->width = '99%';
					$table1->data = array ();
					$table1->head = array ();
					$table1->head[0] = __('Agent');
					$table1->head[1] = __('Module');
					$table1->head[2] = __('Value');
					$table1->style[0] = 'text-align: left';
					$table1->style[1] = 'text-align: left';
					$table1->style[2] = 'text-align: right';
					
					$data_avg = array();
					foreach ($generals as $key => $row) {
						//Metaconsole connection
						$server_name = $row ['server_name'];
						if (($config ['metaconsole'] == 1) && $server_name != '') {
							$connection = metaconsole_get_connection($server_name);
							if (!metaconsole_load_external_db($connection)) {
								//ui_print_error_message ("Error connecting to ".$server_name);
								continue;
							}
						}
						
						$mod_name = modules_get_agentmodule_name ($row['id_agent_module']);
						$ag_name = modules_get_agentmodule_agent_name ($row['id_agent_module']);
						$unit = db_get_value('unit', 'tagente_modulo', 'id_agente_modulo', $row ['id_agent_module']);
						
						$data_avg[$key] = reporting_get_agentmodule_data_average ($row['id_agent_module'], $content['period']);
						$id_agent_module[$key] = $row['id_agent_module'];
						$agent_name[$key] = $ag_name;
						$module_name[$key] = $mod_name;
						$units[$key] = $unit;
						
						//Restore dbconnection
						if (($config ['metaconsole'] == 1) && $server_name != '') {
							metaconsole_restore_db();
						}
					}
					//Order by data descending, ascending or without order
					if ($order_uptodown == 0 || $order_uptodown == 1 || $order_uptodown == 2) {
						switch ($order_uptodown) {
							//Descending
							case 1:
								array_multisort($data_avg, SORT_DESC, $agent_name, SORT_ASC, $module_name, SORT_ASC, $id_agent_module, SORT_ASC);
								break;
							//Ascending
							case 2:
								array_multisort($data_avg, SORT_ASC, $agent_name, SORT_ASC, $module_name, SORT_ASC, $id_agent_module, SORT_ASC);
								break;
						}
						$i=0;
						foreach ($data_avg as $d) {
							$data = array();
							$data[0] = $agent_name[$i];
							$data[1] = $module_name[$i];
							if ($d === false) {
								$data[2] = '--';
							}
							else {
							$data[2] = format_for_graph($d, 2) . " " . $units[$i];
							}
							array_push ($table1->data, $data);
							$i++;
						}
					}
					//Order by agent name
					elseif ($order_uptodown == 3) {
						array_multisort($agent_name, SORT_ASC, $data_avg, SORT_ASC, $module_name, SORT_ASC, $id_agent_module, SORT_ASC);
						$i=0;
						foreach ($agent_name as $a) {
							$data = array();
							$data[0] = $agent_name[$i];
							$data[1] = $module_name[$i];
							if ($data_avg[$i] === false) {
								$data[2] = '--';
							}
							else {
								$data[2] = format_for_graph($data_avg[$i], 2) . " " . $units[$i];
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
					$sql_data = sprintf("select id_agent_module, server_name from treport_content_item
						where id_report_content = %d", $content['id_rc']);
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
					foreach ($generals as $general) {
						
						//Metaconsole connection
						$server_name = $general ['server_name'];
						if (($config ['metaconsole'] == 1) && $server_name != '') {
							$connection = metaconsole_get_connection($server_name);
							if (!metaconsole_load_external_db($connection)) {
								//ui_print_error_message ("Error connecting to ".$server_name);
								continue;
							}
						}
						
						$ag_name = modules_get_agentmodule_agent_name ($general ['id_agent_module']);
						if (!in_array ($ag_name, $agent_list)) {
							array_push ($agent_list, $ag_name);
						}
						$mod_name = modules_get_agentmodule_name ($general ['id_agent_module']);
						if (!in_array ($mod_name, $modules_list)) {
							array_push ($modules_list, $mod_name);
						}
						
						//Restore dbconnection
						if (($config ['metaconsole'] == 1) && $server_name != '') {
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
						$table2->head[$i] = ui_print_truncate_text($m, 20, false);
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
								if (($config ['metaconsole'] == 1) && $server_name != '') {
									$connection = metaconsole_get_connection($server_name);
									if (!metaconsole_load_external_db($connection)) {
										//ui_print_error_message ("Error connecting to ".$server_name);
										continue;
									}
								}
								
								$agent_name = modules_get_agentmodule_agent_name ($g['id_agent_module']);
								$module_name = modules_get_agentmodule_name ($g['id_agent_module']);
								$unit = db_get_value('unit', 'tagente_modulo', 'id_agente_modulo', $g['id_agent_module']);
								$found = false;
								if (strcmp($a, $agent_name) == 0 && strcmp($m, $module_name) == 0) {
									$value_avg = reporting_get_agentmodule_data_average($g['id_agent_module'], $content['period']);
									
									if ($value_avg === false) {
										$data[$i] = '--';
									} else {
										$data[$i] = format_for_graph($value_avg, 2) . " " . $unit;
									}
									$found = true;
								}
								else {
									$data[$i] = '--';
								}
								if ($found == true) break;
								
								//Restore dbconnection
								if (($config ['metaconsole'] == 1) && $server_name != '') {
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
				$i=0;
				do {
					//Metaconsole connection
					$server_name = $generals[$i]['server_name'];
					if (($config ['metaconsole'] == 1) && $server_name != '') {
						$connection = metaconsole_get_connection($server_name);
						if (!metaconsole_load_external_db($connection)) {
							//ui_print_error_message ("Error connecting to ".$server_name);
							continue;
						}
					}
					
					$min = reporting_get_agentmodule_data_average($generals[$i]['id_agent_module'], $content['period']);
					$i++;
					
					//Restore dbconnection
					if (($config ['metaconsole'] == 1) && $server_name != '') {
						metaconsole_restore_db();
					}
				} while ($min === false && $i < count($generals));
				$max = $min;
				$avg = 0;
				$length = 0;
				
				if ($generals === false) {
					$generals = array();
				}
				
				foreach ($generals as $g) {
					//Metaconsole connection
					$server_name = $g['server_name'];
					if (($config ['metaconsole'] == 1) && $server_name != '') {
						$connection = metaconsole_get_connection($server_name);
						if (!metaconsole_load_external_db($connection)) {
							//ui_print_error_message ("Error connecting to ".$server_name);
							continue;
						}
					}
					
					$value = reporting_get_agentmodule_data_average ($g['id_agent_module'], $content['period']);
					if ($value !== false) {
						if ($value > $max) {
							$max = $value;
						}
						if ($value < $min ) {
							$min = $value;
						}
						$avg += $value;
						$length++;
					}
					
					//Restore dbconnection
					if (($config ['metaconsole'] == 1) && $server_name != '') {
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
				$table_summary->head[0] = __('Min Value');
				$table_summary->head[1] = __('Average Value');
				$table_summary->head[2] = __('Max Value');
				
				$table_summary->data[0][0] = format_for_graph($min,2);
				$table_summary->data[0][1] = format_for_graph($avg,2);
				$table_summary->data[0][2] = format_for_graph($max,2);
							
				$table->colspan[3][0] = 3;
				array_push ($table->data, array('<b>'.__('Summary').'</b>'));
				$table->colspan[4][0] = 3;
				array_push ($table->data, array(html_print_table($table_summary, true)));
			}
			break;
		case 'top_n':
			reporting_header_content($mini, $content, $report, $table, __('Top').' '.$content['top_n_value']);
			
			$order_uptodown = $content['order_uptodown'];
			$top_n = $content['top_n'];
			$top_n_value = $content['top_n_value'];
			$show_graph = $content['show_graph'];
			
			$table->style[0] = 'padding: 8px 5px 8px 5px;';
			$table->style[1] = 'text-align: right';
			
			// Put description at the end of the module (if exists)
			$table->colspan[1][0] = 3;
			if ($content["description"] != ""){
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			//Get all the related data
			$sql = sprintf("select id_agent_module, server_name
				from treport_content_item
				where id_report_content = %d", $content['id_rc']);
			
			$tops = db_process_sql ($sql);
			
			if ($tops === false) {
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
				$table1->head[2] = __('Value');
				$table1->style[0] = 'text-align: left';
				$table1->style[1] = 'text-align: left';
				$table1->style[2] = 'text-align: left';
			}
			
			$data_top = array();
			foreach ($tops as $key => $row) {
				
				//Metaconsole connection
				$server_name = $row['server_name'];
				if (($config ['metaconsole'] == 1) && $server_name != '') {
					$connection = metaconsole_get_connection($server_name);
					if (!metaconsole_load_external_db($connection)) {
						//ui_print_error_message ("Error connecting to ".$server_name);
						continue;
					}
				}
				
				$ag_name = modules_get_agentmodule_agent_name($row ['id_agent_module']); 
				$mod_name = modules_get_agentmodule_name ($row ['id_agent_module']);
				$unit = db_get_value('unit', 'tagente_modulo', 'id_agente_modulo', $row ['id_agent_module']); 
				
				
				switch ($top_n) {
					//Max
					case 1:
						$value = reporting_get_agentmodule_data_max ($row['id_agent_module'], $content['period']);
						break;
					//Min
					case 2:
						$value = reporting_get_agentmodule_data_min ($row['id_agent_module'], $content['period']);
						break;
					//Nothing or Average
					case 0: //If nothing is selected then it will be shown the average data
					case 3:
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
				if (($config ['metaconsole'] == 1) && $server_name != '') {
					metaconsole_restore_db();
				}
			}
			if(empty($data_top)) {
				$data = array ();
				$table->colspan[2][0] = 3;
				$data[0] = __('Insuficient data');
				array_push ($table->data, $data);
				break;
			}
			switch ($top_n) {
				//Max
				case 1:
					array_multisort($data_top, SORT_DESC, $agent_name, SORT_ASC, $module_name, SORT_ASC, $id_agent_module, SORT_ASC, $units, SORT_ASC);
					break;
				//Min
				case 2:
					array_multisort($data_top, SORT_ASC, $agent_name, SORT_ASC, $module_name, SORT_ASC, $id_agent_module, SORT_ASC, $units, SORT_ASC);
					break;
				//By agent name or without selection
				case 0:
				case 3:
					array_multisort($agent_name, SORT_ASC, $data_top, SORT_ASC, $module_name, SORT_ASC, $id_agent_module, SORT_ASC, $units, SORT_ASC);
					break;
			}
			
			$data_top_values = array ();
			$data_top_values['data_top'] = $data_top;
			$data_top_values['agent_name'] = $agent_name;
			$data_top_values['module_name'] = $module_name; 
			$data_top_values['id_agent_module'] = $id_agent_module;
			$data_top_values['units'] = $units;
			
			array_splice ($data_top, $top_n_value);
			array_splice ($agent_name, $top_n_value);
			array_splice ($module_name, $top_n_value);
			array_splice ($id_agent_module, $top_n_value);
			array_splice ($units, $top_n_value);
			
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
					
					$data_hbar[$item_name]['g'] = $dt; 
					$data_pie_graph[$item_name] = $dt;
					
					if  ($show_graph == 0 || $show_graph == 1) {
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
						ui_print_truncate_text($agent_name[$i], $truncate_size, false, true, false, "...") .
						' - ' . 
						ui_print_truncate_text($module_name[$i], $truncate_size, false, true, false, "...");
					
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
			if ($show_graph == 1 || $show_graph == 2) {
				$data[0] = pie3d_graph(false, $data_pie_graph,
					$sizgraph_w, $sizgraph_h, __("other"),"", $config['homedir'] .  "/images/logo_vertical_water.png",
					$config['fontpath'], $config['font_size']); 
				
				array_push ($table->data, $data);
				//Display bars graph
				$table->colspan[4][0] = 3;
				$table->style[0] .= 'text-align:center';
				$height = count($data_pie_graph)*20+35;
				$data = array();
				$data[0] = hbar_graph(false, $data_hbar, $sizgraph_w, $height, array(), array(), "", "", true, "", $config['homedir'] .  "/images/logo_vertical_water.png", $config['fontpath'], $config['font_size'], true, 1, true);
				
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
			
			$title_exeption = __('Exception');
			switch ($exception_condition) {
				case 0:
					$title_exeption .= ' - '.__('Everything');
					break;
				case 1:
					$title_exeption .= ' - '.__('Modules over or equal to').' '.$exception_condition_value;
					break;
				case 2:
					$title_exeption .= ' - '.__('Modules under').' '.$exception_condition_value;
					break;
				case 3:
					$title_exeption .= ' - '.__('Modules at normal status');
					break;
				case 4:
					$title_exeption .= ' - '.__('Modules at critical or warning status');
					break;
			}
			reporting_header_content($mini, $content, $report, $table, $title_exeption);
			
			// Put description at the end of the module (if exists)
			$table->colspan[1][0] = 3;
			if ($content["description"] != ""){
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			//Get all the related data
			$sql = sprintf("select id_agent_module, server_name from treport_content_item
							where id_report_content = %d", $content['id_rc']);
			
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
				$table1->head[2] = __('Value');
				$table1->style[0] = 'text-align: left';
				$table1->style[1] = 'text-align: left';
				$table1->style[2] = 'text-align: right';
			}
			
			//Get the very first not null value 
			$i=0;
			do {
				//Metaconsole connection
				$server_name = $exceptions[$i]['server_name'];
				if (($config ['metaconsole'] == 1) && $server_name != '') {
					$connection = metaconsole_get_connection($server_name);
					if (!metaconsole_load_external_db($connection)) {
						//ui_print_error_message ("Error connecting to ".$server_name);
						continue;
					}
				}
				
				$min = reporting_get_agentmodule_data_average ($exceptions[$i]['id_agent_module'], $content['period']);
				$i++;
				
				//Restore dbconnection
				if (($config ['metaconsole'] == 1) && $server_name != '') {
					metaconsole_restore_db();
				}
			} while ($min === false && $i < count($exceptions));
			$max = $min;
			$avg = 0;
			
			$i=0;
			foreach ($exceptions as $exc) {
				//Metaconsole connection
				$server_name = $exc['server_name'];
				if (($config ['metaconsole'] == 1) && $server_name != '') {
					$connection = metaconsole_get_connection($server_name);
					if (!metaconsole_load_external_db($connection)) {
						//ui_print_error_message ("Error connecting to ".$server_name);
						continue;
					}
				}
				
				$ag_name = modules_get_agentmodule_agent_name ($exc ['id_agent_module']);
				$mod_name = modules_get_agentmodule_name ($exc ['id_agent_module']);
				$unit = db_get_value('unit', 'tagente_modulo', 'id_agente_modulo', $exc ['id_agent_module']);
				
				$value = reporting_get_agentmodule_data_average ($exc['id_agent_module'], $content['period']);
				if ($value !== false) {
					if ($value > $max) $max = $value;
					if ($value < $min) $min = $value;
					$avg += $value;
					switch ($exception_condition) {
						//Display everything
						case 0:
							break;
						//Display modules over or equal to certain value
						case 1:
							//Skip modules under 'value'
							if ($value < $exception_condition_value) {
								continue 2;
							}
							break;
						//Display modules under a certain value
						case 2:
							//Skip modules over or equal to 'value'
							if ($value >= $exception_condition_value) {
								continue 2;
							}
							break;
						//Display modules at Normal status
						case 3:
							//Skip modules without normal status
							if (modules_get_agentmodule_status($exc['id_agent_module']) != 0) {
								continue 2;
							}
							break;
						//Display modules at critical, warning or unknown status
						case 4:
							//Skip modules at normal status
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
				}
				//Restore dbconnection
				if (($config ['metaconsole'] == 1) && $server_name != '') {
					metaconsole_restore_db();
				}
			}
			//$i <= 0 means that there are no rows on the table, therefore no modules under the conditions defined.
			if ($i<=0) {
				$data = array ();
				$table->colspan[2][0] = 3;
				$data[0] = __('There are no');
				switch ($exception_condition) {
					case 1:
						$data[0] .= ' '.__('Modules over or equal to').' '.$exception_condition_value;
						break;
					case 2:
						$data[0] .= ' '.__('Modules under').' '.$exception_condition_value;
						break;
					case 3:
						$data[0] .= ' '.__('Modules at normal status');
						break;
					case 4:
						$data[0] .= ' '.__('Modules at critial or warning status');
						break;
					default:
						$data[0] .= ' '.__('Modules under those conditions');
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
					$j=0;
					$data_pie_graph = array();
					$data_hbar = array();
					foreach ($data_exceptions as $dex) {
						$data_hbar[$agent_name[$j]]['g'] = $dex;
						$data_pie_graph[$agent_name[$j]] = $dex;
						if  ($show_graph == 0 || $show_graph == 1) {
							$data = array();
							$data[0] = $agent_name[$j];
							$data[1] = $module_name[$j];
							$data[2] = format_for_graph($dex, 2) . " " . $units[$j];
							array_push ($table1->data, $data);
						}
						$j++;
					}
				}
				else if ($order_uptodown == 0 || $order_uptodown == 3) {
					$j=0;
					$data_pie_graph = array();
					$data_hbar = array();
					foreach ($agent_name as $an) {
						$data_hbar[$an]['g'] = $data_exceptions[$j];
						$data_pie_graph[$an] = $data_exceptions[$j];
						if  ($show_graph == 0 || $show_graph == 1) {
							$data = array();
							$data[0] = $an;
							$data[1] = $module_name[$j];
							$data[2] = format_for_graph($data_exceptions[$j], 2) . " " . $units[$j];
							array_push ($table1->data, $data);
						}
						$j++;
					}
				}
			}
			
			$table->colspan[2][0] = 3;
			if ($show_graph == 0 || $show_graph == 1) {
				$data = array();
				$data[0] = html_print_table($table1, true);
				array_push ($table->data, $data);
			}
			
			$table->colspan[3][0] = 3;
			
			$data = array();
			if ($show_graph == 1 || $show_graph == 2) {
				$data[0] = pie3d_graph(false, $data_pie_graph,
					600, 150, __("other"), "", $config['homedir'] .  "/images/logo_vertical_water.png",
					$config['fontpath'], $config['font_size']); 
				array_push ($table->data, $data);
				//Display bars graph
				$table->colspan[4][0] = 3;
				$height = count($data_pie_graph)*20+35;
				$data = array();
				
				$data[0] = hbar_graph(false, $data_hbar, 600, $height, array(), array(), "", "", true, "", $config['homedir'] .  "/images/logo_vertical_water.png", '', '', true, 1, true);
				
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
				array_push ($table->data, array('<b>'.__('Summary').'</b>'));
				$table->colspan[6][0] = 3;
				array_push ($table->data, array(html_print_table($table_summary, true)));
			}
			break;
		case 'agent_module':
			reporting_header_content($mini, $content, $report, $table, __('Agents/Modules'));
		
			$id_group = $content['id_group'];
			$id_module_group = $content['id_module_group'];
			$offset = get_parameter('offset', 0);
			$block = 20; //Maximun number of modules displayed on the table
			$modulegroup = get_parameter('modulegroup', 0);
			$table->style[1] = 'text-align: right';
			
			// Put description at the end of the module (if exists)
			$table->colspan[1][0] = 3;
			if ($content["description"] != ""){
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			
			$agents = '';
			if($id_group > 0) {
				$agents = agents_get_group_agents($id_group);
				$agents = array_keys($agents);
			}
			
			$filter_module_groups = false;	
			if($id_module_group > 0) {
				$filter_module_groups['id_module_group'] = $id_module_group;
			}
			
			$all_modules = agents_get_modules ($agents, false, $filter_module_groups, true, false);
			
			$modules_by_name = array();
			$name = '';
			$cont = 0;
			
			foreach($all_modules as $key => $module) {
				if($module == $name) {
					$modules_by_name[$cont-1]['id'][] = $key;
				}
				else {
					$name = $module;
					$modules_by_name[$cont]['name'] = $name;
					$modules_by_name[$cont]['id'][] = $key;
					$cont ++;
				}
			}

			if($config["pure"] == 1) {
				$block = count($modules_by_name);
			}
			
			$filter_groups = array ('offset' => (int) $offset,
				'limit' => (int) $config['block_size']);
				
			if($id_group > 0) {
				$filter_groups['id_grupo'] = $id_group;
			}
			
			$agents = agents_get_agents ($filter_groups);
			$nagents = count($agents);
			
			if($all_modules == false || $agents == false) {
				$data = array ();
				$table->colspan[2][0] = 3;
				$data[0] = __('There are no agents with modules');
				array_push ($table->data, $data);
				break;
			}
			$table_data = '<table cellpadding="1" cellspacing="4" cellspacing="0" border="0" style="background-color: #EEE;">';

			$table_data .= "<th style='background-color: #799E48;'>".__("Agents")." / ".__("Modules")."</th>";

			$nmodules = 0;
			foreach($modules_by_name as $module) {
				$nmodules++;
				
				if($nmodules > $block) { //Will show only the (block) first modules
					continue;
				}
				
				$file_name = string2image(ui_print_truncate_text($module['name'], 30, false, true, false, '...'), false, false, 6, 270, '#90B165', 'FFF', 4, 0);
				$table_data .= '<th width="22px">'.html_print_image($file_name, true, array('title' => $module['name']))."</th>";
			}
			
			if ($block < $nmodules) {
				$table_data .= "<th width='20px' style='vertical-align:top; padding-top: 35px;' rowspan='".($nagents+1)."'><b>...</b></th>";
			}

			$filter_agents = false;
			if($id_group > 0) {
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
						$rowcolor = '#ffa300';
						$textcolor = '#000';
						break;
					case 1: // Critical status
						$rowcolor = '#bc0000';
						$textcolor = '#FFF';
						break;
					case 2: // Warning status
						$rowcolor = '#f2ef00';
						$textcolor = '#000';
						break;
					case 0: // Normal status
						$rowcolor = '#8ae234';
						$textcolor = '#000';
						break;
					case 3: 
					case -1: 
					default: // Unknown status
						$rowcolor = '#babdb6';
						$textcolor = '#000';
						break;
				}
				
				$table_data .= "<tr style='height: 35px;'>";
				
				$file_name = string2image(ui_print_truncate_text($agent['nombre'], 35, false, true, false, '...'), false, false, 6, 0, $rowcolor, $textcolor, 4, 0);
				$table_data .= "<td style='background-color: ".$rowcolor.";'>".html_print_image($file_name, true, array('title' => $agent['nombre']))."</td>";
				$agent_modules = agents_get_modules($agent['id_agente']);
				
				$nmodules = 0;

				foreach($modules_by_name as $module) {
					$nmodules++;
					
					if($nmodules > $block) {
						continue;
					}
					
					$match = false;
					foreach($module['id'] as $module_id){
						if(!$match && array_key_exists($module_id,$agent_modules)) {
							$status = modules_get_agentmodule_status($module_id);
							$table_data .= "<td style='text-align: center; background-color: #DDD;'>";
							$win_handle = dechex(crc32($module_id.$module["name"]));
							$graph_type = return_graphtype (modules_get_agentmodule_type($module_id));

							switch($status){
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
								
					if(!$match) {
						$table_data .= "<td style='background-color: #DDD;'></td>";
					}
				}
				
				$table_data .= "</tr>";
			}

			$table_data .= "</table>";
			
			$table_data .= "<br><br><p>" . __("The colours meaning:") .
				"<ul style='float: left;'>" .
				'<li style="clear: both;">
					<div style="float: left; background: #ffa300; height: 14px; width: 26px;margin-right: 5px; margin-bottom: 5px;">&nbsp;</div>' .
					__("Orange cell when the module has fired alerts") .
				'</li>' .
				'<li style="clear: both;">
					<div style="float: left; background: #cc0000; height: 14px; width: 26px;margin-right: 5px; margin-bottom: 5px;">&nbsp;</div>' .
					__("Red cell when the module has a critical status") .
				'</li>' .
				'<li style="clear: both;">
					<div style="float: left; background: #fce94f; height: 14px; width: 26px;margin-right: 5px; margin-bottom: 5px;">&nbsp;</div>' .
					__("Yellow cell when the module has a warning status") .
				'</li>' .
				'<li style="clear: both;">
					<div style="float: left; background: #8ae234; height: 14px; width: 26px;margin-right: 5px; margin-bottom: 5px;">&nbsp;</div>' .
					__("Green cell when the module has a normal status") .
				'</li>' .
				'<li style="clear: both;">
					<div style="float: left; background: #babdb6; height: 14px; width: 26px;margin-right: 5px; margin-bottom: 5px;">&nbsp;</div>' .
					__("Grey cell when the module has an unknown status") .
				'</li>' .
				"</ul>" .
			"</p>";
			$data = array ();
			$table->colspan[2][0] = 3;
			$data[0] = $table_data;
			array_push ($table->data, $data);
			break;
		case 'inventory':
			reporting_header_content($mini, $content, $report, $table, __('Inventory'));
			
			$es = json_decode($content['external_source'], true);
			
			$id_agent = $es['id_agents'];
			$module_name = $es['inventory_modules'];
			$date = $es['date'];
			$description = $content['description'];
						
			$data = array ();
			$table->colspan[1][0] = 2;
			$table->colspan[2][0] = 2;
			if($description != '') {
				$data[0] = $description;
				array_push ($table->data, $data);
			}

			$inventory_data = inventory_get_data((array)$id_agent,0,$date,'',false,(array)$module_name);

			if ($inventory_data == ERR_NODATA) {
				$inventory_data = "<div class='nf'>".__('No data found.')."</div>";
				$inventory_data .= "&nbsp;</td></tr><tr><td>";				
			}
			
			$data[0] = $inventory_data;
			array_push ($table->data, $data);
			break;
		case 'inventory_changes':
			reporting_header_content($mini, $content, $report, $table, __('Inventory changes'));
			
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

			if($description != '') {
				$data[0] = $description;
				array_push ($table->data, $data);
			}

			$data[0] = $inventory_changes;
			$table->colspan[2][0] = 2;

			array_push ($table->data, $data);
			break;
	}
	//Restore dbconnection
	if (($config ['metaconsole'] == 1) && $server_name != '') {
		metaconsole_restore_db();
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
function reporting_get_agentmodule_mtbf ($id_agent_module, $period, $date = 0) {

	// Initialize variables
	if (empty ($date)) $date = get_system_time ();
	if ((empty ($period)) OR ($period == 0)) $period = $config["sla_period"];
	
	// Read module configuration
	$datelimit = $date - $period;	
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
		' ORDER BY utimestamp ASC', true);
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
function reporting_get_agentmodule_mttr ($id_agent_module, $period, $date = 0) {

	// Initialize variables
	if (empty ($date)) $date = get_system_time ();
	if ((empty ($period)) OR ($period == 0)) $period = $config["sla_period"];
	
	// Read module configuration
	$datelimit = $date - $period;	
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
		' ORDER BY utimestamp ASC', true);
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
function reporting_get_agentmodule_tto ($id_agent_module, $period, $date = 0) {

	// Initialize variables
	if (empty ($date)) $date = get_system_time ();
	if ((empty ($period)) OR ($period == 0)) $period = $config["sla_period"];
	
	// Read module configuration
	$datelimit = $date - $period;	
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
		' ORDER BY utimestamp ASC', true);
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
function reporting_get_agentmodule_ttr ($id_agent_module, $period, $date = 0) {

	// Initialize variables
	if (empty ($date)) $date = get_system_time ();
	if ((empty ($period)) OR ($period == 0)) $period = $config["sla_period"];
	
	// Read module configuration
	$datelimit = $date - $period;	
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
		' ORDER BY utimestamp ASC', true);
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

?>
