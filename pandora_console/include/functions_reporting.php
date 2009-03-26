<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2009 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License (LGPL)
// as published by the Free Software Foundation for version 2.
// 
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

require_once ($config["homedir"]."/include/functions.php");
require_once ($config["homedir"]."/include/functions_db.php");
require_once ($config["homedir"]."/include/functions_agents.php");

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
 * @return float SLA percentage of the requested module.
 */
function get_agentmodule_sla ($id_agentmodule, $period = 0, $min_value = 1, $max_value = false, $date = 0) {
	if (empty ($date)) {
		$date = get_system_time ();	
	}
	
	if (empty ($period)) {
		global $config;
		$period = $config["sla_period"];
	}
	
	$datelimit = $date - $period; // start date
	
	/* Get the total data entries in the interval */
	$sql = sprintf ('SELECT COUNT(*)
		FROM tagente_datos
		WHERE id_agente_modulo = %d
		AND utimestamp > %d
		AND utimestamp <= %d', $id_agentmodule, $datelimit, $date);
	$total = get_db_sql ($sql);
	
	if (empty ($total)) {
		//No data to calculate on so we return 100 (fail to good)
		return 100.0;
	}
	
	$sql = sprintf ('SELECT COUNT(*)
		FROM tagente_datos
		WHERE id_agente_modulo = %d
		AND utimestamp > %d
		AND utimestamp <= %d
		AND datos < %d', $id_agentmodule, $datelimit, $date, $min_value);
	if ($max_value > $min_value) {
		$sql .= sprintf (' AND datos > %d', $max_value);
	}
	$bad = get_db_sql ($sql);
	if (empty ($bad)) {
		$bad = 0;
	}
	
	//Calculate percentage
	$result = 100 - ($bad / $total) * 100;
	
	return (float) $result;
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
	$data["total_checks"] = 0;	
	$data["total_alerts"] = 0;
	$data["total_agents"] = 0;
	$data["total_alerts"] = 0;
	$data["total_checks"] = 0;
	$data["monitor_health"] = 100;
	$data["alert_level"] = 100;
	$data["module_sanity"] = 100;
	$data["server_sanity"] = 100;
	$cur_time = get_system_time ();
	
	//Check for access credentials using give_acl. More overhead, much safer
	if (!give_acl ($config["id_user"], $id_group, "AR")) {
		return $data;
	}
	
	if ($id_group == 0) {
		$id_group = array_keys (get_user_groups ());
	}
	$agents = array_keys (get_group_agents ($id_group));	
	
	if (empty ($agents)) {
		//No agents in this group, means no data
		return $data;
	}

	$filter = 'id_agente IN ('.implode (",", $agents).') ';
	
	$data["monitor_checks"] = (int) get_db_sql ("SELECT COUNT(*) FROM tagente_estado WHERE ".$filter);
	$data["monitor_not_init"] = (int) get_db_sql ("SELECT COUNT(*) FROM tagente_estado WHERE ".$filter."AND utimestamp = 0");
	$data["monitor_unknown"] = (int) get_db_sql ("SELECT COUNT(*) FROM tagente_estado WHERE ".$filter."AND UNIX_TIMESTAMP() - utimestamp >= current_interval * 2");	
	$data["monitor_critical"] = (int) get_db_sql ("SELECT COUNT(*) FROM tagente_estado WHERE ".$filter."AND estado = 1 AND UNIX_TIMESTAMP() - utimestamp < current_interval * 2");
	$data["monitor_warning"] = (int) get_db_sql ("SELECT COUNT(*) FROM tagente_estado WHERE ".$filter."AND estado = 2 AND UNIX_TIMESTAMP() - utimestamp < current_interval * 2");
	$data["monitor_ok"] = $data["monitor_checks"] - $data["monitor_not_init"] - $data["monitor_unknown"] - $data["monitor_critical"] - $data["monitor_warning"];
	
	$sql = sprintf ("SELECT times_fired FROM talert_template_modules WHERE id_agent_module IN (%s)", implode (",", array_keys ($agents))); 
	$result = get_db_all_rows_sql ($sql);
	
	if (empty ($result)) {
		$result = array (); //It's possible there are no alerts so we don't return
	}
	
	foreach ($result as $row) {
		$data["monitor_alerts"]++;
		if ($row["times_fired"] > 0) {
			$data["monitor_alerts_fired"]++;
			$data["monitor_alerts_fire_count"] += $row["times_fired"];
		}
	}	
	
	$data["total_agents"] = count ($agents);
	$data["total_checks"] = $data["monitor_checks"];
	$data["total_ok"] = $data["monitor_ok"];
	//TODO: count SNMP Alerts and Inventory alerts here
	$data["total_alerts"] = $data["monitor_alerts"] + $data["monitor_alerts_fired"];
	$data["total_alerts_fired"] =  $data["monitor_alerts_fired"];
	$data["total_alerts_fire_count"] =  $data["monitor_alerts_fire_count"];
	$data["monitor_bad"] = $data["monitor_critical"] + $data["monitor_unknown"] + $data["monitor_warning"];
	$data["total_bad"] =  $data["monitor_bad"];
	$data["total_not_init"] = $data["monitor_not_init"];
	$data["total_down"] = $data["monitor_critical"];

	/*
	 Monitor health (percentage)
	 Data health (percentage)
	 Global health (percentage)
	 Module sanity (percentage)
	 Alert level (percentage)
	 
	 Server Sanity	0% Uninitialized modules
	 
	 */
	if ($data["monitor_bad"] > 0 && $data["monitor_checks"] > 0) {
		$data["monitor_health"] = format_numeric (100 - ($data["monitor_bad"] / ($data["monitor_checks"] / 100)), 1);
	} else {
		$data["monitor_health"] = 100;
	}
	
	if ($data["total_bad"] > 0 && $data["total_checks"] > 0) {
		$data["global_health"] = format_numeric (100 - ($data["total_bad"] / ($data["total_checks"] / 100)), 1);
	} else {
		$data["global_health"] = 100;
	}

	if ($data["total_not_init"] > 0 && $data["total_checks"] > 0) {
		$data["module_sanity"] = format_numeric (100 - ($data["total_not_init"] / ($data["total_checks"] / 100)), 1);
	} else {
		$data["module_sanity"] = 100;
	}
	
	if ($data["total_alerts_fired"] > 0 && $data["total_alerts"] > 0) {
		$data["alert_level"] = format_numeric (100 - ($data["total_alerts_fired"] / ($data["total_alerts"] / 100)), 1);
	} else {
		$data["alert_level"] = 100;
	}
	
	$data["server_sanity"] = format_numeric (100 - $data["module_sanity"], 1);
	
	return $data;
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
	
	require_once ('include/functions_alerts.php');
	
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
	$table->head[2] = __('Times Fired');
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
	$output .= '<img src="reporting/fgraph.php?tipo=alerts_fired_pipe&height=150&width=280&fired='.
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
	$output .= '<img src="reporting/fgraph.php?tipo=monitors_health_pipe&height=150&width=280&down='.$down_percentage.'&amp;not_down='.$not_down_percentage.'" style="border: 1px solid black" />';
	
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
	$table->data = array ();
	$table->head = array ();
	$table->head[0] = __('Type');
	$table->head[1] = __('Description');
	$table->head[2] = __('Value');
	$table->head[3] = __('Threshold');
	$table->head[4] = __('Last fired');
	$table->head[5] = __('Times Fired');
	
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

?>
