<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
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


/** 
 * Get the average value of an agent module in a period of time.
 * 
 * @param int Agent module id
 * @param int Period of time to check (in seconds)
 * @param int Top date to check the values. Default current time.
 * 
 * @return float The average module value in the interval.
 */
function get_agentmodule_data_average ($id_agent_module, $period, $date = 0) {
	if ($date < 1) {
		$date = get_system_time ();
	}
	
	$datelimit = $date - $period;
	
	$sql = sprintf ("SELECT * FROM tagente_datos 
			WHERE id_agente_modulo = %d 
			AND utimestamp > %d AND utimestamp <= %d 
			ORDER BY utimestamp ASC",
			$id_agent_module, $datelimit, $date);
	
	$values = get_db_all_rows_sql ($sql, true);
	if ($values === false) {
		$values = array ();
	}

	/* Get also the previous data before the selected interval. */
	$sum = 0;
	$total = 0;
	$module_interval = get_module_interval ($id_agent_module);
	$previous_data = get_previous_data ($id_agent_module, $datelimit);
	if ($previous_data !== false) {
		$values = array_merge (array ($previous_data), $values);
	}

	foreach ($values as $data) {
		$interval_total = 1;
		$interval_sum = $data['datos'];
		if ($previous_data !== false && $data['utimestamp'] - $previous_data['utimestamp'] > $module_interval) {
			$interval_total = round (($data['utimestamp'] - $previous_data['utimestamp']) / $module_interval, 0);
			$interval_sum *= $interval_total;
			
		}
		$total += $interval_total;
		$sum += $interval_sum;
		$previous_data = $data;
	}

	if ($total == 0) {
		return 0;
	}

	return $sum / $total;
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
function get_agentmodule_data_max ($id_agent_module, $period, $date = 0) {
	if (! $date)
		$date = get_system_time ();
	$datelimit = $date - $period;
	
	$sql = sprintf ("SELECT MAX(datos) FROM tagente_datos 
			WHERE id_agente_modulo = %d 
			AND utimestamp > %d  AND utimestamp <= %d",
			$id_agent_module, $datelimit, $date);
	$max = (float) get_db_sql ($sql, 0, true);
	
	/* Get also the previous report before the selected interval. */
	$previous_data = get_previous_data ($id_agent_module, $datelimit);
	if ($previous_data !== false)
		return max ($previous_data['datos'], $max);
	
	return max ((float) $previous_data, $max);
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
function get_agentmodule_data_min ($id_agent_module, $period, $date = 0) {
	if (! $date)
		$date = get_system_time ();
	$datelimit = $date - $period;
	
	$sql = sprintf ("SELECT MIN(datos) FROM tagente_datos 
			WHERE id_agente_modulo = %d 
			AND utimestamp > %d AND utimestamp <= %d",
			$id_agent_module, $datelimit, $date);
	$min = (float) get_db_sql ($sql, 0, true);
	
	/* Get also the previous data before the selected interval. */
	$previous_data = get_previous_data ($id_agent_module, $datelimit);
	if ($previous_data)
		return min ($previous_data['datos'], $min);
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
function get_agentmodule_data_sum ($id_agent_module, $period, $date = 0) {

	if (! $date)
		$date = get_system_time ();

	$datelimit = $date - $period; // limit date
	$id_module_type = get_db_value ('id_tipo_modulo', 'tagente_modulo','id_agente_modulo', $id_agent_module);
	$module_name = get_db_value ('nombre', 'ttipo_modulo', 'id_tipo', $id_module_type);

	if (is_module_data_string ($module_name)) {
		return 0;
        // Wrong module type, we cannot sum alphanumerical data !
	}

	// Get the whole interval of data
	$sql = sprintf ('SELECT utimestamp, datos FROM tagente_datos 
			WHERE id_agente_modulo = %d 
			AND utimestamp > %d AND utimestamp <= %d 
			ORDER BY utimestamp ASC',
			$id_agent_module, $datelimit, $date);
	$datas = get_db_all_rows_sql ($sql, true);
	
	/* Get also the previous data before the selected interval. */
	$previous_data = get_previous_data ($id_agent_module, $datelimit);
	if ($previous_data) {
		/* Add data to the beginning */
		array_unshift ($datas, $previous_data);
	}
	if ($datas === false) {
		return 0;
	}

	$last_data = "";
	$total_badtime = 0;
	$module_interval = get_module_interval ($id_agent_module);
	$timestamp_begin = $datelimit + $module_interval;
	$timestamp_end = 0;
	$sum = 0;
	$data_value = 0;
    $elapsed = 1;
	
	foreach ($datas as $data) {
		$timestamp_end = $data["utimestamp"];
        if ($timestamp_begin < $timestamp_end)
    		$elapsed = $timestamp_end - $timestamp_begin;
		$times =  $elapsed / $module_interval;

		if (is_module_inc ($module_name)) {
			$data_value = $data['datos'] * $module_interval;
		} else {
			$data_value = $data['datos'];
		}

		$sum += $times * $data_value;
		$timestamp_begin = $data["utimestamp"];
	}

	/* The last value must be get from tagente_estado, but
	   it will count only if it's not older than date demanded
	*/
	$timestamp_end = get_db_value ('utimestamp', 'tagente_estado', 'id_agente_modulo', $id_agent_module);
	if ($timestamp_end <= $datelimit) {
		$elapsed = $timestamp_end - $timestamp_begin;
		$times = intval ($elapsed / $module_interval);
		if (is_module_inc ($module_name)) {
			$data_value = $data['datos'] * $module_interval;
		} else {
			$data_value = $data['datos'];
		}
		$sum += $times * $data_value;
	}
	
	return (float) $sum;
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
 * 
 * @return float SLA percentage of the requested module. False if no data were
 * found
 */
function get_agentmodule_sla ($id_agentmodule, $period = 0, $min_value = 1, $max_value = false, $date = 0) {
	global $config;
	
	// Initialize variables
	if (empty ($date)) $date = get_system_time ();
	if ((empty ($period)) OR ($period == 0)) $period = $config["sla_period"];
	
	// Limit date to start searching data
	$datelimit = $date - $period;
	
	// Get interval data
	$sql = sprintf ('SELECT * FROM tagente_datos
	                 WHERE id_agente_modulo = %d
	                 AND utimestamp > %d AND utimestamp <= %d',
	                 $id_agentmodule, $datelimit, $date);
	$interval_data = get_db_all_rows_sql ($sql, true);
	if ($interval_data === false) $interval_data = array ();
	
	// Calculate for how long the module has not met the SLA
	$mark = 0;
	$bad_period = 0;
	foreach ($interval_data as $data) {
		// bad data
		if ((($max_value > $min_value AND ($data['datos'] > $max_value OR  $data['datos'] < $min_value))) OR
		     ($max_value <= $min_value AND $data['datos'] < $min_value)) {
			// good data turns bad
			if ($mark == 0) {
				$mark = $data['utimestamp'];
			}
		// good data
		} else {
			// bad data turns good
			if ($mark != 0) {
				$bad_period += $data['utimestamp'] - $mark;
				$mark = 0;
			}
		}
	}
	
	// Return the percentage of SLA compliance
	return (float) (100 - ($bad_period / $period) * 100);
}

/** 
 * Get general statistical info on a group
 * 
 * @param int Group Id to get info from. 0 = all
 * 
 * @return array Group statistics
 */
function get_group_stats ($id_group = 0) {
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

	//Check for access credentials using give_acl. More overhead, much safer
	if (!give_acl ($config["id_user"], $id_group, "AR")) {
		return $data;
	}
	
	if ($id_group == 0) {
		$id_group = array_keys (get_user_groups ());
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
			$group_stat = get_db_all_rows_sql ("SELECT * FROM tgroup_stat, tgrupo WHERE tgrupo.id_grupo = tgroup_stat.id_group AND tgroup_stat.id_group = $group ORDER BY nombre");
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
	} else {

		if (!is_array($id_group)){
			$my_group = $id_group;
			$id_group = array();
			$id_group[0] = $my_group;
		}

		foreach ($id_group as $group){


			$data["agents_unknown"] += get_db_sql ("SELECT COUNT(*) FROM tagente WHERE id_grupo = $group AND disabled = 0 AND ultimo_contacto < NOW() - (intervalo *2)");

			$data["total_agents"] += get_db_sql ("SELECT COUNT(*) FROM tagente WHERE id_grupo = $group AND disabled = 0");

			$data["monitor_checks"] += get_db_sql ("SELECT COUNT(tagente_estado.id_agente_estado) FROM tagente_estado, tagente, tagente_modulo WHERE tagente.id_grupo = $group AND tagente.disabled = 0 AND tagente_estado.id_agente = tagente.id_agente AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.disabled = 0");

			$data["total_not_init"] += get_db_sql ("SELECT COUNT(tagente_estado.id_agente_estado) FROM tagente_estado, tagente, tagente_modulo WHERE tagente.id_grupo = $group AND tagente.disabled = 0 AND tagente_estado.id_agente = tagente.id_agente AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.disabled = 0
	 AND tagente_modulo.id_tipo_modulo NOT IN (21,22,23,30,100) AND tagente_estado.utimestamp = 0");

			$data["monitor_ok"] += get_db_sql ("SELECT COUNT(tagente_estado.id_agente_estado) FROM tagente_estado, tagente, tagente_modulo WHERE tagente.id_grupo = $group AND tagente.disabled = 0 AND tagente_estado.id_agente = tagente.id_agente AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.disabled = 0 AND estado = 0");

			$data["monitor_critical"] += get_db_sql ("SELECT COUNT(tagente_estado.id_agente_estado) FROM tagente_estado, tagente, tagente_modulo WHERE tagente.id_grupo = $group AND tagente.disabled = 0 AND tagente_estado.id_agente = tagente.id_agente AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.disabled = 0 AND estado = 1");

			$data["monitor_warning"] += get_db_sql ("SELECT COUNT(tagente_estado.id_agente_estado) FROM tagente_estado, tagente, tagente_modulo WHERE tagente.id_grupo = $group AND tagente.disabled = 0 AND tagente_estado.id_agente = tagente.id_agente AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.disabled = 0 AND estado = 2 ");

			$data["monitor_unknown"] += get_db_sql ("SELECT COUNT(tagente_estado.id_agente_estado) FROM tagente_estado, tagente, tagente_modulo WHERE tagente.id_grupo = $group AND tagente.disabled = 0 AND tagente.id_agente = tagente_estado.id_agente  AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.disabled = 0 AND tagente_modulo.id_tipo_modulo NOT IN (21,22,23,30,100) AND utimestamp < ( UNIX_TIMESTAMP() - (current_interval * 2))");


			$data["monitor_alerts"] += get_db_sql ("SELECT COUNT(talert_template_modules.id) FROM talert_template_modules, tagente_modulo, tagente_estado, tagente WHERE tagente.id_grupo = $group AND tagente_modulo.id_agente = tagente.id_agente AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.disabled = 0 AND tagente.disabled = 0 AND talert_template_modules.id_agent_module = tagente_modulo.id_agente_modulo");

			$data["monitor_alerts_fired"] += get_db_sql ("SELECT COUNT(talert_template_modules.id) FROM talert_template_modules, tagente_modulo, tagente_estado, tagente WHERE tagente.id_grupo = $group AND tagente_modulo.id_agente = tagente.id_agente AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.disabled = 0 AND tagente.disabled = 0 AND talert_template_modules.id_agent_module = tagente_modulo.id_agente_modulo AND times_fired > 0");
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
	} else {
		$data["monitor_health"] = 100;
	}

	if ($data["monitor_not_init"] > 0 && $data["monitor_checks"] > 0) {
		$data["module_sanity"] = format_numeric (100 - ($data["monitor_not_init"] / ($data["monitor_checks"] / 100)), 1);
	} else {
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
	} else {
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
function event_reporting ($id_group, $period, $date = 0, $return = false) {
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
	
	$events = get_group_events ($id_group, $period, $date);
	if (empty ($events)) {
		$events = array ();
	}
	foreach ($events as $event) {
		$data = array ();
		if ($event["estado"] == 0)
			$data[0] = '<img src="images/dot_red.png" />';
		else
			$data[0] = '<img src="images/dot_green.png" />';
		$data[1] = $event['evento'];
		$data[2] = $event['id_usuario'] != '0' ? $event['id_usuario'] : '';
		$data[3] = $event["timestamp"];
		array_push ($table->data, $data);
	}

	if (empty ($return))
		print_table ($table);
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
function get_fired_alerts_reporting_table ($alerts_fired) {
	$agents = array ();
	global $config;

	require_once ($config["homedir"].'/include/functions_alerts.php');
	
	foreach (array_keys ($alerts_fired) as $id_alert) {
		$alert_module = get_alert_agent_module ($id_alert);
		$template = get_alert_template ($id_alert);
		
		/* Add alerts fired to $agents_fired_alerts indexed by id_agent */
		$id_agent = get_db_value ('id_agente', 'tagente_modulo',
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
				$data[0] = get_agent_name ($id_agent);
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
function alert_reporting ($id_group, $period = 0, $date = 0, $return = false) {
	$output = '';
	$alerts = get_group_alerts ($id_group);
	$alerts_fired = get_alerts_fired ($alerts, $period, $date);
	
	$fired_percentage = 0;
	if (sizeof ($alerts) > 0)
		$fired_percentage = round (sizeof ($alerts_fired) / sizeof ($alerts) * 100, 2);
	$not_fired_percentage = 100 - $fired_percentage;
	$output .= '<img src="include/fgraph.php?tipo=alerts_fired_pipe&height=150&width=280&fired='.
		$fired_percentage.'&not_fired='.$not_fired_percentage.'" style="float: right; border: 1px solid black">';
	
	$output .= '<strong>'.__('Alerts fired').': '.sizeof ($alerts_fired).'</strong><br />';
	$output .= '<strong>'.__('Total alerts monitored').': '.sizeof ($alerts).'</strong><br />';

	if (! sizeof ($alerts_fired)) {
		if (!$return)
			echo $output;
		return $output;
	}
	$table = get_fired_alerts_reporting_table ($alerts_fired);
	$table->width = '100%';
	$table->class = 'databox';
	$table->size = array ();
	$table->size[0] = '100px';
	$table->style = array ();
	$table->style[0] = 'font-weight: bold';
	
	$output .= print_table ($table, true);
	
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
function monitor_health_reporting ($id_group, $period = 0, $date = 0, $return = false) {
	if (empty ($date)) //If date is 0, false or empty
		$date = get_system_time ();
		
	$datelimit = $date - $period;
	$output = '';
	
	$monitors = get_monitors_in_group ($id_group);
	if (empty ($monitors)) //If monitors has returned false or an empty array
		return;
	$monitors_down = get_monitors_down ($monitors, $period, $date);
	$down_percentage = round (count ($monitors_down) / count ($monitors) * 100, 2);
	$not_down_percentage = 100 - $down_percentage;
	
	$output .= '<strong>'.__('Total monitors').': '.count ($monitors).'</strong><br />';
	$output .= '<strong>'.__('Monitors down on period').': '.count ($monitors_down).'</strong><br />';
	
	$table = get_monitors_down_reporting_table ($monitors_down);
	$table->width = '100%';
	$table->class = 'databox';
	$table->size = array ();
	$table->size[0] = '100px';
	$table->style = array ();
	$table->style[0] = 'font-weight: bold';
	
	$table->size = array ();
	$table->size[0] = '100px';
	
	$output .= print_table ($table, true);
	
	//Floating it was ugly, moved it to the bottom
	$output .= '<img src="include/fgraph.php?tipo=monitors_health_pipe&height=150&width=280&down='.$down_percentage.'&amp;not_down='.$not_down_percentage.'" style="border: 1px solid black" />';
	
	if (!$return)
		echo $output;
	return $output;
}

/** 
 * Get a report table with all the monitors down.
 * 
 * @param array  An array with all the monitors down
 * @see function get_monitors_down()
 * 
 * @return object A table object with a monitors down report.
 */
function get_monitors_down_reporting_table ($monitors_down) {
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
					$data[0] = get_agent_name ($id_agent);
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
function print_group_reporting ($id_group, $return = false) {
	$agents = get_group_agents ($id_group, false, "none");
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
function get_agent_alerts_reporting_table ($id_agent, $period = 0, $date = 0) {
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
	
	$alerts = get_agent_alerts ($id_agent);
	/* FIXME: Add compound alerts to the report. Some extra code is needed here */
	foreach ($alerts['simple'] as $alert) {
		$fires = get_alert_fires_in_period ($alert['id'], $period, $date);
		if (! $fires) {
			continue;
		}
		
		$template = get_alert_template ($alert['id_alert_template']);
		$data = array ();
		$data[0] = get_alert_templates_type_name ($template['type']);
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
		$data[4] = print_timestamp (get_alert_last_fire_timestamp_in_period ($alert['id'], $period, $date), true);
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
function get_agent_monitors_reporting_table ($id_agent, $period = 0, $date = 0) {
	$n_a_string = __('N/A').'(*)';
	$table->head = array ();
	$table->head[0] = __('Monitor');
	$table->head[1] = __('Last failure');
	$table->data = array ();
	$monitors = get_monitors_in_agent ($id_agent);
	
	if ($monitors === false) {
		return $table;
	}
	foreach ($monitors as $monitor) {
		$downs = get_monitor_downs_in_period ($monitor['id_agente_modulo'], $period, $date);
		if (! $downs) {
			continue;
		}
		$data = array ();
		if ($monitor['descripcion'] != $n_a_string && $monitor['descripcion'] != '')
			$data[0] = $monitor['descripcion'];
		else
			$data[0] = $monitor['nombre'];
		$data[1] = get_monitor_last_down_timestamp_in_period ($monitor['id_agente_modulo'], $period, $date);
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
function get_agent_modules_reporting_table ($id_agent, $period = 0, $date = 0) {
	$table->data = array ();
	$n_a_string = __('N/A').'(*)';
	$modules = get_agent_modules ($id_agent, array ("nombre", "descripcion"));
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
function get_agent_detailed_reporting ($id_agent, $period = 0, $date = 0, $return = false) {
	$output = '';
	$n_a_string = __('N/A').'(*)';
	
	/* Show modules in agent */
	$output .= '<div class="agent_reporting">';
	$output .= '<h3 style="text-decoration: underline">'.__('Agent').' - '.get_agent_name ($id_agent).'</h3>';
	$output .= '<h4>'.__('Modules').'</h3>';
	$table_modules = get_agent_modules_reporting_table ($id_agent, $period, $date);
	$table_modules->width = '99%';
	$output .= print_table ($table_modules, true);
	
	/* Show alerts in agent */
	$table_alerts = get_agent_alerts_reporting_table ($id_agent, $period, $date);
	$table_alerts->width = '99%';
	if (sizeof ($table_alerts->data)) {
		$output .= '<h4>'.__('Alerts').'</h4>';
		$output .= print_table ($table_alerts, true);
	}
	
	/* Show monitor status in agent (if any) */
	$table_monitors = get_agent_monitors_reporting_table ($id_agent, $period, $date);
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
	$output .= print_table ($table_monitors, true);
	
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
function get_group_agents_detailed_reporting ($id_group, $period = 0, $date = 0, $return = false) {
	$agents = get_group_agents ($id_group, false, "none");
	
	$output = '';
	foreach ($agents as $agent_id => $agent_name) {
		$output .= get_agent_detailed_reporting ($agent_id, $period, $date, true);
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
function get_agents_detailed_event_reporting ($id_agents, $period = 0, $date = 0, $return = false) {
	$id_agents = safe_int ($id_agents, 1);
	
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
	if ($events)
	foreach ($id_agents as $id_agent) {
		$event = get_agent_events ($id_agent, (int) $period, (int) $date);
		if (!empty ($event)) {
			array_push ($events, $event);
		}
	}

	if ($events)
	foreach ($events as $event) {
		$data = array ();
		$data[0] = $event['evento'];
		$data[1] = $event['event_type'];
		$data[2] = get_priority_name ($event['criticity']);
		$data[3] = $event['count_rep'];
		$data[4] = $event['time2'];
		array_push ($table->data, $data);
	}

	if ($events)	
		return print_table ($table, $return);
}

/** 
 * Get a detailed report of the modules of the agent
 * 
 * @param int $id_agent Agent id to get the report for.
 * 
 * @return array An array
 */
function get_agent_module_info ($id_agent) {
	global $config;
	
	$return = array ();
	$return["modules"] = 0; //Number of modules
	$return["monitor_normal"] = 0; //Number of 'good' monitors
	$return["monitor_warning"] = 0; //Number of 'warning' monitors
	$return["monitor_critical"] = 0; //Number of 'critical' monitors
	$return["monitor_down"] = 0; //Number of 'down' monitors
	$return["last_contact"] = 0; //Last agent contact 
	$return["interval"] = get_agent_interval ($id_agent); //How often the agent gets contacted
	$return["status_img"] = print_status_image (STATUS_AGENT_NO_DATA, __('Agent without data'), true);
	$return["alert_status"] = "notfired";
	$return["alert_img"] = print_status_image (STATUS_ALERT_NOT_FIRED, __('Alert not fired'), true);
	$return["agent_group"] = get_agent_group ($id_agent);
	
	if (!give_acl ($config["id_user"], $return["agent_group"], "AR")) {
		return $return;
	} 
	
	$sql = sprintf ("SELECT * FROM tagente_estado, tagente_modulo 
		WHERE tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo 
		AND tagente_modulo.disabled = 0 
		AND tagente_estado.utimestamp > 0 
		AND tagente_modulo.id_agente = %d", $id_agent);
	
	$modules = get_db_all_rows_sql ($sql);
	
	if ($modules === false) {
		return $return;
	}
	
	$now = get_system_time ();
	
	// Calculate modules for this agent
	foreach ($modules as $module) {
		$return["modules"]++;
		
		if ($module["module_interval"] > $return["interval"]) {
			$return["interval"] = $module["module_interval"];
		} elseif ($module["module_interval"] == 0) {
			$module["module_interval"] = $return["interval"];
		}
		
		if ($module["utimestamp"] > $return["last_contact"]) {
			$return["last_contact"] = $module["utimestamp"];
		}
		
		if (($module["id_tipo_modulo"] < 21 || $module["id_tipo_modulo"] > 23 ) AND ($module["id_tipo_modulo"] != 100)) {
			$async = 0;
		} else {
			$async = 1;
		}
		
		if ($async == 0 && ($module["utimestamp"] < ($now - $module["module_interval"] * 2))) {
			$return["monitor_down"]++;
		} elseif ($module["estado"] == 2) {
			$return["monitor_warning"]++;
		} elseif ($module["estado"] == 1) {
			$return["monitor_critical"]++;
		} else {
			$return["monitor_normal"]++;
		}
	}
		
	if ($return["modules"] > 0) {
		if ($return["modules"] == $return["monitor_down"])
			$return["status_img"] = print_status_image (STATUS_AGENT_DOWN, __('At least one module is in UKNOWN status'), true);	
		else if ($return["monitor_critical"] > 0)
			$return["status_img"] = print_status_image (STATUS_AGENT_CRITICAL, __('At least one module in CRITICAL status'), true);
		else if ($return["monitor_warning"] > 0)
			$return["status_img"] = print_status_image (STATUS_AGENT_WARNING, __('At least one module in WARNING status'), true);
		else
			$return["status_img"] = print_status_image (STATUS_AGENT_OK, __('All Monitors OK'), true);
	}
	
	//Alert not fired is by default
	if (give_disabled_group ($return["agent_group"])) {
		$return["alert_status"] = "disabled";
		$return["alert_img"] = print_status_image (STATUS_ALERT_DISABLED, __('Alert disabled'), true);
	} elseif (check_alert_fired ($id_agent) == 1) {
		$return["alert_status"] = "fired";
		$return["alert_img"] = print_status_image (STATUS_ALERT_FIRED, __('Alert fired'), true);	
	}
	
	return $return;
}	

/** 
 * Get a detailed report of the modules of the agent
 * 
 * @param int $id_agent Agent id to get the report for.
 * 
 * @return array An array
 */
function get_agent_module_info_with_filter ($id_agent,$filter = '') {
	global $config;
	
	$return = array ();
	$return["modules"] = 0; //Number of modules
	$return["monitor_normal"] = 0; //Number of 'good' monitors
	$return["monitor_warning"] = 0; //Number of 'warning' monitors
	$return["monitor_critical"] = 0; //Number of 'critical' monitors
	$return["monitor_down"] = 0; //Number of 'down' monitors
	$return["last_contact"] = 0; //Last agent contact 
	$return["interval"] = get_agent_interval ($id_agent); //How often the agent gets contacted
	$return["status_img"] = print_status_image (STATUS_AGENT_NO_DATA, __('Agent without data'), true);
	$return["alert_status"] = "notfired";
	$return["alert_img"] = print_status_image (STATUS_ALERT_NOT_FIRED, __('Alert not fired'), true);
	$return["agent_group"] = get_agent_group ($id_agent);
	
	if (!give_acl ($config["id_user"], $return["agent_group"], "AR")) {
		return $return;
	} 
	
	$sql = sprintf ("SELECT * FROM tagente_estado, tagente_modulo 
		WHERE tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo 
		AND tagente_modulo.disabled = 0	
		AND tagente_estado.utimestamp > 0 
		AND tagente_modulo.id_agente = %d", $id_agent);
		
	$sql .= $filter;
	
	$modules = get_db_all_rows_sql ($sql);
	
	if ($modules === false) {
		return $return;
	}
	
	$now = get_system_time ();
	
	// Calculate modules for this agent
	foreach ($modules as $module) {
		$return["modules"]++;
		
		if ($module["module_interval"] > $return["interval"]) {
			$return["interval"] = $module["module_interval"];
		} elseif ($module["module_interval"] == 0) {
			$module["module_interval"] = $return["interval"];
		}
		
		if ($module["utimestamp"] > $return["last_contact"]) {
			$return["last_contact"] = $module["utimestamp"];
		}
		
		if (($module["id_tipo_modulo"] < 21 || $module["id_tipo_modulo"] > 23 ) AND  ($module["id_tipo_modulo"] != 100)) {
			$async = 0;
		} else {
			$async = 1;
		}
		
		if ($async == 0 && ($module["utimestamp"] < ($now - $module["module_interval"] * 2))) {
			$return["monitor_down"]++;
		} elseif ($module["estado"] == 2) {
			$return["monitor_warning"]++;
		} elseif ($module["estado"] == 1) {
			$return["monitor_critical"]++;
		} else {
			$return["monitor_normal"]++;
		}
	}
		
	if ($return["modules"] > 0) {
		if ($return["modules"] == $return["monitor_down"])
			$return["status_img"] = print_status_image (STATUS_AGENT_DOWN, __('At least one module is in UKNOWN status'), true);	
		else if ($return["monitor_critical"] > 0)
			$return["status_img"] = print_status_image (STATUS_AGENT_CRITICAL, __('At least one module in CRITICAL status'), true);
		else if ($return["monitor_warning"] > 0)
			$return["status_img"] = print_status_image (STATUS_AGENT_WARNING, __('At least one module in WARNING status'), true);
		else
			$return["status_img"] = print_status_image (STATUS_AGENT_OK, __('All Monitors OK'), true);
	}
	
	//Alert not fired is by default
	if (give_disabled_group ($return["agent_group"])) {
		$return["alert_status"] = "disabled";
		$return["alert_img"] = print_status_image (STATUS_ALERT_DISABLED, __('Alert disabled'), true);
	} elseif (check_alert_fired ($id_agent) == 1) {
		$return["alert_status"] = "fired";
		$return["alert_img"] = print_status_image (STATUS_ALERT_FIRED, __('Alert fired'), true);	
	}
	
	return $return;
}

/** 
 * This function is used once, in reporting_viewer.php, the HTML report render
 * file. This function proccess each report item and write the render in the
 * table record.
 * 
 * @param array $content Record of treport_content table for current item
 * @param array $table HTML Table row
 * @param array $report Report contents, with some added fields.
 * 
 */

function render_report_html_item ($content, $table, $report){
    global $config;

	$module_name = get_db_value ('nombre', 'tagente_modulo', 'id_agente_modulo', $content['id_agent_module']);
	$agent_name = get_agentmodule_agent_name ($content['id_agent_module']);

	switch ($content["type"]) {
	case 1:
	case 'simple_graph':
		$table->colspan[1][0] = 4;
		$data = array ();
		$data[0] = '<h4>'.__('Simple graph').'</h4>';
		$data[1] = '<h4>'.$agent_name.' - '.$module_name.'</h4>';
		$data[2] = '<h4>'.human_time_description ($content['period']).'</h4>';
		array_push ($table->data, $data);
		
		// Put description at the end of the module (if exists)
		if ($content["description"] != ""){
			$table->colspan[2][0] = 4;
			$data_desc = array();
			$data_desc[0] = $content["description"];
			array_push ($table->data, $data_desc);
		}
		
		$data = array ();
		$data[0] = '<img src="include/fgraph.php?tipo=sparse&id='.$content['id_agent_module'].'&height=230&width=750&period='.$content['period'].'&date='.$report["datetime"].'&avg_only=1&pure=1" border="0" alt="">';
		array_push ($table->data, $data);
		
		break;
	case 2:
	case 'custom_graph':
		$graph = get_db_row ("tgraph", "id_graph", $content['id_gs']);
		$data = array ();
		$data[0] = '<h4>'.__('Custom graph').'</h4>';
		$data[1] = "<h4>".$graph["name"]."</h4>";
		$data[2] = "<h4>".human_time_description ($content['period'])."</h4>";
		array_push ($table->data, $data);
		
		// Put description at the end of the module (if exists)
		if ($content["description"] != ""){
			$table->colspan[1][0] = 3;
			$data_desc = array();
			$data_desc[0] = $content["description"];
			array_push ($table->data, $data_desc);
		}
		
		$result = get_db_all_rows_field_filter ("tgraph_source", "id_graph", $content['id_gs']);
		$modules = array ();
		$weights = array ();
		if ($result === false)
			$result = array();
		
		foreach ($result as $content2) {
			array_push ($modules, $content2['id_agent_module']);
			array_push ($weights, $content2["weight"]);
		}
		
		$graph_width = get_db_sql ("SELECT width FROM tgraph WHERE id_graph = ".$content["id_gs"]);
		$graph_height= get_db_sql ("SELECT height FROM tgraph WHERE id_graph = ".$content["id_gs"]);


		$table->colspan[2][0] = 3;
		$data = array ();
		$data[0] = '<img src="include/fgraph.php?tipo=combined&id='.implode (',', $modules).'&weight_l='.implode (',', $weights).'&height=235&width=750&period='.$content['period'].'&date='.$report["datetime"].'&stacked='.$graph["stacked"].'&pure=1" border="1" alt="">';
		array_push ($table->data, $data);

		break;
	case 3:
	case 'SLA':

		$table->style[1] = 'text-align: right';
		$data = array ();
		$data[0] = '<h4>'.__('S.L.A.').'</h4>';
		$data[1] = '<h4>'.human_time_description ($content['period']).'</h4>';;
		$n = array_push ($table->data, $data);
		
		// Put description at the end of the module (if exists)
		if ($content["description"] != ""){
			$table->colspan[1][0] = 3;
			$data_desc = array();
			$data_desc[0] = $content["description"];
			array_push ($table->data, $data_desc);
		}
		
		$slas = get_db_all_rows_field_filter ('treport_content_sla_combined',
							'id_report_content', $content['id_rc']);
		if ($slas === false) {
			$data = array ();
			$table->colspan[2][0] = 3;
			$data[0] = __('There are no SLAs defined');
			array_push ($table->data, $data);
			$slas = array ();
		}
		
		$sla_failed = false;
		foreach ($slas as $sla) {
			$data = array ();
			
			$data[0] = '<strong>'.__('Agent')."</strong> : ";
			$data[0] .= get_agentmodule_agent_name ($sla['id_agent_module'])."<br />";
			$data[0] .= '<strong>'.__('Module')."</strong> : ";
			$data[0] .= get_agentmodule_name ($sla['id_agent_module'])."<br />";
			$data[0] .= '<strong>'.__('SLA Max. (value)')."</strong> : ";
			$data[0] .= $sla['sla_max']."<br />";
			$data[0] .= '<strong>'.__('SLA Min. (value)')."</strong> : ";
			$data[0] .= $sla['sla_min']."<br />";
			$data[0] .= '<strong>'.__('SLA Limit')."</strong> : ";
			$data[0] .= $sla['sla_limit'];
			$sla_value = get_agentmodule_sla ($sla['id_agent_module'], $content['period'],
				$sla['sla_min'], $sla['sla_max'], $report["datetime"]);
			if ($sla_value === false) {
				$data[1] = '<span style="font: bold 3em Arial, Sans-serif; color: #0000FF;">';
				$data[1] .= __('Unknown');
			} else {
				if ($sla_value >= $sla['sla_limit'])
					$data[1] = '<span style="font: bold 3em Arial, Sans-serif; color: #000000;">';
				else {
					$sla_failed = true;
					$data[1] = '<span style="font: bold 3em Arial, Sans-serif; color: #ff0000;">';
				}
				$data[1] .= format_numeric ($sla_value). " %";
			}
			$data[1] .= "</span>";
			
			$n = array_push ($table->data, $data);
		}
		if (!empty ($slas)) {
			$data = array ();
			if ($sla_failed == false)
				$data[0] = '<span style="font: bold 3em Arial, Sans-serif; color: #000000;">'.__('OK').'</span>';
			else
				$data[0] = '<span style="font: bold 3em Arial, Sans-serif; color: #ff0000;">'.__('Fail').'</span>';
			$n = array_push ($table->data, $data);
			$table->colspan[$n - 1][0] = 3;
			$table->rowstyle[$n - 1] = 'text-align: right';
		}
		
		break;
	case 4:
	case 'event_report':
		$id_agent = get_agent_id ($agent_name);
		$data = array ();
		$data[0] = "<h4>".__('Event report')."</h4>";
		$data[1] = "<h4>".human_time_description ($content['period'])."</h4>";
		array_push ($table->data, $data);
		
		// Put description at the end of the module (if exists)
		if ($content["description"] != ""){
			$table->colspan[1][0] = 3;
			$data_desc = array();
			$data_desc[0] = $content["description"];
			array_push ($table->data, $data_desc);
		}
		
		$table->colspan[2][0] = 3;
		$data = array ();
		$table_report = event_reporting ($report['id_group'], $content['period'], $report["datetime"], true);

		$table_report->class = 'databox';
		$table_report->width = '100%';
		$data[0] = print_table ($table_report, true);
		array_push ($table->data, $data);
		
		break;
	case 5:
	case 'alert_report':
		$data = array ();
		$data[0] = "<h4>".__('Alert report')."</h4>";
		$data[1] = "<h4>".$report["group_name"]."</h4>";
		$data[2] = "<h4>".human_time_description ($content['period'])."</h4>";
		array_push ($table->data, $data);
		
		// Put description at the end of the module (if exists)
		if ($content["description"] != ""){
			$table->colspan[1][0] = 3;
			$data_desc = array();
			$data_desc[0] = $content["description"];
			array_push ($table->data, $data_desc);
		}
		
		$data = array ();
		$table->colspan[2][0] = 3;
		$data[0] = alert_reporting ($report['id_group'], $content['period'], $report["datetime"], true);
		array_push ($table->data, $data);
		
		break;
	case 6:
	case 'monitor_report':
		$data = array ();
		$data[0] = "<h4>".__('Monitor report')."</h4>";
		$data[1] = "<h4>$agent_name - $module_name</h4>";
		$data[2] = "<h4>".human_time_description ($content['period'])."</h4>";
		array_push ($table->data, $data);
		
		// Put description at the end of the module (if exists)
		if ($content["description"] != ""){
			$table->colspan[1][0] = 3;
			$data_desc = array();
			$data_desc[0] = $content["description"];
			array_push ($table->data, $data_desc);
		}
		
		$data = array ();
		$monitor_value = format_numeric (get_agentmodule_sla ($content['id_agent_module'], $content['period'], 1, false, $report["datetime"]));
		$data[0] = '<p style="font: bold 3em Arial, Sans-serif; color: #000000;">';
		$data[0] .= $monitor_value.' % <img src="images/b_green.png" height="32" width="32" /></p>';
		$monitor_value = format_numeric (100 - $monitor_value, 2) ;
		$data[1] = '<p style="font: bold 3em Arial, Sans-serif; color: #ff0000;">';
		$data[1] .= $monitor_value.' % <img src="images/b_red.png" height="32" width="32" /></p>';
		array_push ($table->data, $data);
		
		break;
	case 7:
	case 'avg_value':
		$data = array ();
		$data[0] = "<h4>".__('Avg. Value')."</h4>";
		$data[1] = "<h4>$agent_name - $module_name</h4>";
		$data[2] = "<h4>".human_time_description ($content['period'])."</h4>";
		array_push ($table->data, $data);
		
		// Put description at the end of the module (if exists)
		if ($content["description"] != ""){
			$table->colspan[1][0] = 3;
			$data_desc = array();
			$data_desc[0] = $content["description"];
			array_push ($table->data, $data_desc);
		}
		
		$data = array ();
		$table->colspan[2][0] = 3;
		$value = format_numeric (get_agentmodule_data_average ($content['id_agent_module'], $content['period'], $report["datetime"]));
		$data[0] = '<p style="font: bold 3em Arial, Sans-serif; color: #000000;">'.$value.'</p>';
		array_push ($table->data, $data);
		
		break;
	case 8:
	case 'max_value':
		$data = array ();
		$data[0] = "<h4>".__('Max. Value')."</h4>";
		$data[1] = "<h4>$agent_name - $module_name</h4>";
		$data[2] = "<h4>".human_time_description ($content['period'])."</h4>";
		array_push ($table->data, $data);
		
		// Put description at the end of the module (if exists)
		if ($content["description"] != ""){
			$table->colspan[1][0] = 3;
			$data_desc = array();
			$data_desc[0] = $content["description"];
			array_push ($table->data, $data_desc);
		}
		
		$data = array ();
		$table->colspan[2][0] = 3;
		$value = format_numeric (get_agentmodule_data_max ($content['id_agent_module'], $content['period'], $report["datetime"]));
		$data[0] = '<p style="font: bold 3em Arial, Sans-serif; color: #000000;">'.$value.'</p>';
		array_push ($table->data, $data);
		
		break;
	case 9:
	case 'min_value':
		$data = array ();
		$data[0] = "<h4>".__('Min. Value')."</h4>";
		$data[1] = "<h4>$agent_name - $module_name</h4>";
		$data[2] = "<h4>".human_time_description ($content['period'])."</h4>";
		array_push ($table->data, $data);
		
		// Put description at the end of the module (if exists)
		if ($content["description"] != ""){
			$table->colspan[0][0] = 2;
			$data_desc = array();
			$data_desc[0] = $content["description"];
			array_push ($table->data, $data_desc);
		}
		
		$data = array ();
		$table->colspan[1][0] = 2;
		$value = format_numeric (get_agentmodule_data_min ($content['id_agent_module'], $content['period'], $report["datetime"]));
		$data[0] = '<p style="font: bold 3em Arial, Sans-serif; color: #000000;">'.$value.'</p>';
		array_push ($table->data, $data);
		
		break;
	case 10:
	case 'sumatory':
		$data = array ();
		$data[0] = "<h4>".__('Sumatory')."</h4>";
		$data[1] = "<h4>$agent_name - $module_name</h4>";
		$data[2] = "<h4>".human_time_description ($content['period'])."</h4>";
		array_push ($table->data, $data);
		
		// Put description at the end of the module (if exists)
		if ($content["description"] != ""){
			$table->colspan[0][0] = 2;
			$data_desc = array();
			$data_desc[0] = $content["description"];
			array_push ($table->data, $data_desc);
		}
		
		$data = array ();
		$table->colspan[1][0] = 2;
		$value = format_numeric (get_agentmodule_data_sum ($content['id_agent_module'], $content['period'], $report["datetime"]));
		$data[0] = '<p style="font: bold 3em Arial, Sans-serif; color: #000000;">'.$value.'</p>';
		array_push ($table->data, $data);
		
		break;
	case 11:
	case 'general_group_report':
		$data = array ();
		$data[0] = "<h4>".__('Group')."</h4>";
		$data[1] = "<h4>".$report["group_name"]."</h4>";
		array_push ($table->data, $data);
		
		// Put description at the end of the module (if exists)
		if ($content["description"] != ""){
			$table->colspan[0][0] = 2;
			$data_desc = array();
			$data_desc[0] = $content["description"];
			array_push ($table->data, $data_desc);
		}
		
		$data = array ();
		$table->colspan[1][0] = 2;
		$data[0] = print_group_reporting ($report['id_group'], true);
		array_push ($table->data, $data);
		
		break;
	case 12:
	case 'monitor_health':
		$data = array ();
		$data[0] = "<h4>".__('Monitor health')."</h4>";
		$data[1] = "<h4>".$report["group_name"]."</h4>";
		$data[2] = "<h4>".human_time_description ($content['period'])."</h4>";
		array_push ($table->data, $data);
		
		// Put description at the end of the module (if exists)
		if ($content["description"] != ""){
			$table->colspan[0][0] = 4;
			$data_desc = array();
			$data_desc[0] = $content["description"];
			array_push ($table->data, $data_desc);
		}
		
		$data = array ();
		$table->colspan[1][0] = 4;
		$data[0] = monitor_health_reporting ($report['id_group'], $content['period'], $report["datetime"], true);
		array_push ($table->data, $data);
		
		break;
	case 13:
	case 'agents_detailed':
		$data = array ();
		$data[0] = "<h4>".__('Agents detailed view')."</h4>";
		$data[1] = "<h4>".$report["group_name"]."</h4>";
		array_push ($table->data, $data);
		
		// Put description at the end of the module (if exists)
		if ($content["description"] != ""){
			$table->colspan[0][0] = 2;
			$data_desc = array();
			$data_desc[0] = $content["description"];
			array_push ($table->data, $data_desc);
		}
		
		$table->colspan[0][0] = 2;
		$data = array ();
		$table->colspan[1][0] = 3;
		$data[0] = get_group_agents_detailed_reporting ($report['id_group'], $content['period'], $report["datetime"], true);
		array_push ($table->data, $data);
		break;

	case 'agent_detailed_event':
		$data = array ();
		$data[0] = "<h4>".__('Agent detailed event')."</h4>";
		$data[1] = "<h4>".get_agent_name($content['id_agent'])."</h4>";
		array_push ($table->data, $data);
		
		// Put description at the end of the module (if exists)
		if ($content["description"] != ""){
			$table->colspan[1][0] = 3;
			$data_desc = array();
			$data_desc[0] = $content["description"];
			array_push ($table->data, $data_desc);
		}
		
		$data = array ();
		$table->colspan[2][0] = 3;
		$data[0] = get_agents_detailed_event_reporting ($content['id_agent'], $content['period'], $report["datetime"]);
		array_push ($table->data, $data);
		break;
	}
}

?>
