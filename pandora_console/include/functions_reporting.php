<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

/** 
 * Get SLA of a module.
 * 
 * @param id_agent_module Agent module to calculate SLA
 * @param period Period to check the SLA compliance.
 * @param min_value Minimum data value the module in the right interval
 * @param max_value Maximum data value the module in the right interval
 * @param date Beginning date of the report in UNIX time (current date by default).
 * 
 * @return SLA percentage of the requested module.
 */
function get_agent_module_sla ($id_agent_module, $period, $min_value, $max_value, $date = 0) {
	require("config.php");
	if (! $date)
		$date = time ();
	$datelimit = $date - $period; // limit date
	$id_agent = get_db_value ('id_agente', 'tagente_modulo', 'id_agente_modulo', $id_agent_module);
	/* Get all the data in the interval */
	$sql = sprintf ('SELECT * FROM tagente_datos 
			WHERE id_agente = %d AND id_agente_modulo = %d 
			AND utimestamp > %d AND utimestamp <= %d 
			ORDER BY utimestamp ASC',
			$id_agent, $id_agent_module, $datelimit, $date);
	$datas = get_db_all_rows_sql ($sql);
	$last_data = "";
	$total_badtime = 0;
	$interval_begin = 0;
	$interval_last = $date;
	$previous_data_timestamp = 0;
	
	/* Get also the previous data before the selected interval. */
	$previous_data = get_previous_data ($id_agent_module, $datelimit);
	if ($previous_data) {
		/* Add data to the beginning */
		array_unshift ($datas, $previous_data);
		$previous_data_timestamp = $previous_data['utimestamp'];
	}
	if ($datas === false) {
		return false;
	}
	
	foreach ($datas as $data) {
		if ($data["datos"] > $max_value || $data["datos"] < $min_value) {
			if ($interval_begin == 0) {
				$interval_begin = $data["utimestamp"];
			}
		} elseif ($interval_begin != 0) {
			// Here ends interval with data outside valid values,
			// Need to add this time to counter
			$interval_last = $data["utimestamp"];
			$temp_time = $interval_last - $interval_begin;
			$total_badtime += $temp_time;
			$interval_begin = 0;
			$interval_last = 0;
		}
	}
	
	/* Check the last interval, if any */
	if ($interval_begin != 0) {
		/* The last time was the time of the previous data in the 
		interval. That means that in all the interval, the data was 
		not between the expected values, so the SLA is zero. */
		if ($interval_begin = $previous_data_timestamp)
			return 0;
		$total_badtime += $interval_last - $interval_begin;
	}
	
	$result = 100 - ($total_badtime / $period) * 100;
	return max ($result, 0);
}

/** 
 * Get a general stats info.
 * 
 * @param id_user 
 * @param id_group 
 * 
 * @return 
 */
function general_stats ($id_user, $id_group = 0) {
	if ($id_group <= 0)
		// Get group list that user has access
		$mis_grupos = list_group2 ($id_user);
	else
		$mis_grupos[0] = $id_group;
		
	$contador_grupo  = 0;
	$contador_agente = 0;
	$array_index     = 0;
	
	$monitor_checks = 0;
	$monitor_ok = 0;
	$monitor_bad = 0;
	$monitor_unknown =0;
	$monitor_alert = 0;
	$monitor_not_init=0;
	$total_agents = 0;
	$data_checks = 0;
	$data_unknown =0;
	$data_not_init = 0;
	$data_alert = 0;
	$data_alert_total = 0;
	$monitor_alert_total = 0;
	$ahora=date("Y-m-d H:i:s");
	$ahora_sec = strtotime($ahora);
	
	// Prepare data to show
	// For each valid group for this user, take data from agent and modules
	foreach ($mis_grupos as $migrupo) {
		if ($migrupo != "") {
			$existen_agentes = 0;
			$sql0 = "SELECT COUNT(id_agente) FROM tagente WHERE id_grupo = $migrupo AND disabled = 0";
			$result0 = mysql_query ($sql0);
			$row0 = mysql_fetch_array ($result0);
			$total_agents = $total_agents + $row0[0];
			if ($row0[0] > 0)
				$existen_agentes = 1;

			// SQL Join to get monitor status for agents belong this group
			$sql1 = "SELECT tagente.id_agente, tagente_estado.estado, tagente_estado.datos, tagente_estado.current_interval, tagente_estado.utimestamp, tagente_estado.id_agente_modulo, tagente_modulo.id_tipo_modulo FROM tagente, tagente_estado, tagente_modulo WHERE tagente.disabled = 0 AND tagente.id_grupo = $migrupo AND tagente.id_agente = tagente_estado.id_agente AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.disabled = 0 ";
			if ($result1 = mysql_query ($sql1)){
				while ($row1 = mysql_fetch_array ($result1)) {
					$id_agente = $row1[0];
					$estado = $row1[1];
					$datos = $row1[2];
					$module_interval = $row1[3];
					$utimestamp = $row1[4];
					$seconds = $ahora_sec - $utimestamp;
					$id_agente_modulo = $row1[5];
					$module_type = $row1[6];
					if (($module_type < 21) OR ($module_type == 100))
						$async = 0;
					else
						$async = 1;
					if ($estado != 100){
						// Monitor check
						$monitor_checks++;
						if ($utimestamp == 0)
							$monitor_not_init++;
						elseif ($seconds >= ($module_interval*2))
							$monitor_unknown++;
						elseif ($datos != 0) {
							$monitor_ok++;
						} else {
							$monitor_bad++;
						}
						// Alert
						if ($utimestamp != 0){
							$sql2 = "SELECT times_fired FROM talerta_agente_modulo WHERE id_agente_modulo = $id_agente_modulo";
							if ($result2 = mysql_query ($sql2)){
								if ($row2 = mysql_fetch_array ($result2)){
									$monitor_alert_total++;
									if ($row2[0] > 0)
										$monitor_alert++;
								}
							}
						}
					} else {
						// Data check
						if ($utimestamp == 0)
							$data_not_init++;
						elseif (($seconds >= ($module_interval*2)) AND ($async == 0))
							$data_unknown++;
						$data_checks++;
						// Alert
						if ($utimestamp != 0){
							$sql2 = "SELECT times_fired FROM talerta_agente_modulo WHERE id_agente_modulo = $id_agente_modulo";
							if ($result2 = mysql_query ($sql2)){
								if ($row2 = mysql_fetch_array ($result2)) {
									$data_alert_total++;
									if ($row2[0] > 0)
										$data_alert++;
								}
							}
						}
					}
				}
			}
		}
	}

	$data =  array();
	$data[0] = $monitor_checks;
	$data[1] = $monitor_ok;
	$data[2] = $monitor_bad;
	$data[3] = $monitor_unknown;
	$data[4] = $monitor_alert;
	$data[5] = $total_agents;
	$data[6] = $data_checks;
	$data[7] = $data_unknown;
	$data[8] = $data_alert;
	$data[9] = $data_alert_total;
	$data[10] = $monitor_alert_total;
	$data[11] = $data_not_init;
	$data[12] = $monitor_not_init;
	return $data;
}

/** 
 * Get an event reporting table.
 *
 * It construct a table object with all the events happened in a group
 * during a period of time.
 * 
 * @param id_group Group id to get the report.
 * @param period Period of time to get the report.
 * @param date Beginning date of the report in UNIX time (current date by default).
 * @param return Flag to return or echo the report table (echo by default).
 * 
 * @return A table object if return variable is true.
 */
function event_reporting ($id_group, $period, $date = 0, $return = false) {
	global $config;

	if (! $date)
		$date = time ();
	$datelimit = $date - $period;
	
	$table->data = array ();
	$table->head = array ();
	$table->head[0] = __('Status');
	$table->head[1] = __('Event name');
	$table->head[2] = __('User ID');
	$table->head[3] = __('Timestamp');
	
	$sql = sprintf ('SELECT * FROM tevento 
			WHERE id_agente = %d
			AND utimestamp > %d AND utimestamp <= %d
			AND id_grupo = %d
			ORDER BY utimestamp ASC',
			$id_group, $datelimit, $date, $id_group);
	$events = get_db_all_rows_sql ($sql);
	if ($events === false) {
		if (!$return)
		print_table ($table);
		return $table;
	}
	foreach ($events as $event) {
		$data = array ();
		if ($event["estado"] == 0)
			$data[0] = '<img src="images/dot_red.png">';
		else
			$data[0] = '<img src="images/dot_green.png">';
		$data[1] = $event['evento'];
		$data[2] = $event['id_usuario'] != '0' ? $event['id_usuario'] : '';
		$data[3] = $event["timestamp"];
		array_push ($table->data, $data);
	}

	if (!$return)
		print_table ($table);
	return $table;
}

/** 
 * Get a table report from a alerts fired array.
 * 
 * @param alerts_fired Alerts fired array. See get_alerts_fired()
 * 
 * @return A table object with a report of the fired alerts.
 */
function get_fired_alerts_reporting_table ($alerts_fired) {
	$agents = array ();
	
	foreach (array_keys ($alerts_fired) as $id_alert) {
		$alert = get_db_row ('talerta_agente_modulo', 'id_aam', $id_alert);
		
		/* Add alerts fired to $agents_fired_alerts indexed by id_agent */
		$id_agent = $alert['id_agent'];
		if (!isset ($agents[$id_agent])) {
			$agents[$id_agent] = array ();
		}
		array_push ($agents[$id_agent], $alert);
	}
	
	$table->data = array ();
	$table->head = array ();
	$table->head[0] = __('Agent');
	$table->head[1] = __('Alert description');
	$table->head[2] = __('Times Fired');
	$table->head[3] = __('Priority');
	
	foreach ($agents as $alerts) {
		$data = array ();
		foreach ($alerts as $alert) {
			if (! isset ($data[0]))
				$data[0] = dame_nombre_agente_agentemodulo ($alert['id_agente_modulo']);
			else
				$data[0] = '';
			$data[1] = $alert['descripcion'];
			$data[2] = $alerts_fired[$alert['id_aam']];
			$data[3] = get_alert_priority ($alert['priority']);
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
 * @param $id_group Group to get info of the alerts.
 * @param $period Period of time of the desired alert report.
 * @param $date Beggining date of the report (current date by default).
 * @param $return Flag to return or echo the report (echo by default).
 */
function alert_reporting ($id_group, $period = 0, $date = 0, $return = false) {
	$output = '';
	$alerts = get_alerts_in_group ($id_group);
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
 * @param $id_group Group to get info of the monitors.
 * @param $period Period of time of the desired monitor report.
 * @param $date Beginning date of the report in UNIX time (current date by default).
 * @param $return Flag to return or echo the report (by default).
 */
function monitor_health_reporting ($id_group, $period = 0, $date = 0, $return = false) {
	if (! $date)
		$date = time ();
	$datelimit = $date - $period;
	$output = '';
	
	$monitors = get_monitors_in_group ($id_group);
	if (sizeof ($monitors) == 0)
		return;
	$monitors_down = get_monitors_down ($monitors, $period, $date);
	$down_percentage = round (sizeof ($monitors_down) / sizeof ($monitors) * 100, 2);
	$not_down_percentage = 100 - $down_percentage;
	$output .= '<img src="reporting/fgraph.php?tipo=monitors_health_pipe&height=150&width=280&down='.
		$down_percentage.'&not_down='.$not_down_percentage.'" style="float: right; border: 1px solid black">';
	
	$output .= '<strong>'.__('Total monitors').': '.sizeof ($monitors).'</strong><br />';
	$output .= '<strong>'.__('Monitors down on period').': '.sizeof ($monitors_down).'</strong><br />';
	
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
	
	if (!$return)
		echo $output;
	return $output;
}

/** 
 * Get a report table with all the monitors down.
 * 
 * @param monitors_down An array with all the monitors down. See
 * get_monitors_down()
 * 
 * @return A table object with a monitors down report.
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
					$data[0] = dame_nombre_agente ($id_agent);
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
 * @param $id_group Group to get the report
 * @param $return Flag to return or echo the report (by default).
 */
function general_group_reporting ($id_group, $return = false) {
	$output = '';
	$agents = get_agents_in_group ($id_group);
	$output .= '<strong>'.__('Agents in group').': '.sizeof ($agents).'</strong><br />';
	
	if (!$return)
		echo $output;
	return $output;
}

/** 
 * Get a report table of the fired alerts group by agents.
 * 
 * @param id_agent Agent id to generate the report.
 * @param period Period of time of the report.
 * @param date Beginning date of the report in UNIX time (current date by default).
 * 
 * @return A table object with the alert reporting..
 */
function get_agent_alerts_reporting_table ($id_agent, $period = 0, $date = 0) {
	$table->data = array ();
	$table->head = array ();
	$table->head[0] = __('Type');
	$table->head[1] = __('Description');
	$table->head[2] = __('Minimum');
	$table->head[3] = __('Maximum');
	$table->head[4] = __('Threshold');
	$table->head[5] = __('Last fired');
	$table->head[6] = __('Times Fired');
	
	$alerts = get_alerts_in_agent ($id_agent);
	foreach ($alerts as $alert) {
		$fires = get_alert_fires_in_period ($alert['id_agente_modulo'], $period, $date);
		if (! $fires) {
			continue;
		}
		$alert_type = get_db_row ('talerta', 'id_alerta', $alert['id_alerta']);
		$data = array ();
		$data[0] = $alert_type['nombre'];
		$data[1] = $alert['descripcion'];
		$data[2] = $alert['dis_min'];
		$data[3] = $alert['dis_max'];
		$data[4] = $alert['time_threshold'];
		$data[5] = get_alert_last_fire_timestamp_in_period ($alert['id_agente_modulo'], $period, $date);
		$data[6] = $fires;
		
		array_push ($table->data, $data);
	}
	return $table;
}

/** 
 * Get a report of monitors in an agent.
 * 
 * @param id_agent Agent id to get the report
 * @param period Period of time of the report.
 * @param date Beginning date of the report in UNIX time (current date by default).
 * 
 * @return A table object with the report.
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
 * @param id_agent Agent id to get the report.
 * @param period Period of time of the report
 * @param date Beginning date of the report in UNIX time (current date by default).
 * 
 * @return 
 */
function get_agent_modules_reporting_table ($id_agent, $period = 0, $date = 0) {
	$table->data = array ();
	$n_a_string = __('N/A').'(*)';
	$modules = get_modules_in_agent ($id_agent);
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
 * @param $id_agent Agent to get the report.
 * @param $period Period of time of the desired report.
 * @param $date Beginning date of the report in UNIX time (current date by default).
 * @param $return Flag to return or echo the report (by default).
 */
function get_agent_detailed_reporting ($id_agent, $period = 0, $date = 0, $return = false) {
	$output = '';
	$n_a_string = __('N/A').'(*)';
	
	/* Show modules in agent */
	$output .= '<div class="agent_reporting">';
	$output .= '<h3 style="text-decoration: underline">'.__('Agent').' - '.dame_nombre_agente ($id_agent).'</h3>';
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
 * @param $id_group Group to get the report
 * @param $return Flag to return or echo the report (by default).
 */
function get_agents_detailed_reporting ($id_group, $period = 0, $date = 0, $return = false) {
	$output = '';
	$agents = get_agents_in_group ($id_group);
	
	foreach ($agents as $agent) {
		$output .= get_agent_detailed_reporting ($agent['id_agente'], $period, $date, true);
		if (!$return) {
			echo $output;
			$output = '';
			flush ();
		}
	}
	
	if (!$return)
		echo $output;
	return $output;
}
?>
