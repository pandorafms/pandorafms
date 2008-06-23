<?php

// Pandora FMS - the Free Monitoring System
// ========================================
// Copyright (c) 2008 Artica Soluciones Tecnológicas, http://www.artica.es
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

function return_module_SLA ($id_agent_module, $period, $min_value, $max_value, $date = 0) {
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
	$datas = get_db_all_rows_sqlfree ($sql);
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
	if (sizeof ($datas) == 0) {
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

function general_stats ( $id_user, $id_group = 0) {
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
	$ahora=date("Y/m/d H:i:s");
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
			$sql1 = "SELECT tagente.id_agente, tagente_estado.estado, tagente_estado.datos, tagente_estado.current_interval, tagente_estado.utimestamp, tagente_estado.id_agente_modulo FROM tagente, tagente_estado, tagente_modulo WHERE tagente.disabled = 0 AND tagente.id_grupo = $migrupo AND tagente.id_agente = tagente_estado.id_agente AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.disabled = 0 ";
			if ($result1 = mysql_query ($sql1)){
				while ($row1 = mysql_fetch_array ($result1)) {
					$id_agente = $row1[0];
					$estado = $row1[1];
					$datos = $row1[2];
					$module_interval = $row1[3];
					$utimestamp = $row1[4];
					$seconds = $ahora_sec - $utimestamp;
					$id_agente_modulo = $row1[5];
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
						elseif ($seconds >= ($module_interval*2))
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

function event_reporting ($id_agent, $period, $date = 0, $return = false) {
	require("config.php");
	require ("include/languages/language_".$config["language"].".php");

	$output = '';
	$id_user = $_SESSION["id_usuario"];
	global $REMOTE_ADDR;
	if (! $date)
		$date = time ();
	$mytimestamp = $date - $period;
	
	$output .= "<table cellpadding='4' cellspacing='4' width='100%' class='databox'>";
	$output .= "<tr>";
	$output .= "<th>".$lang_label["status"]."</th>";
	$output .= "<th>".$lang_label["event_name"]."</th>";
	$output .= "<th>".$lang_label["id_user"]."</th>";
	$output .= "<th>".$lang_label["timestamp"]."</th>";
	$color = 1;
	$id_evento = 0;
	
	$sql2="SELECT * FROM tevento WHERE id_agente = $id_agent AND utimestamp > '$mytimestamp'";
	
	// Make query for data (all data, not only distinct).
	$result2 = mysql_query($sql2);
	while ($row2 = mysql_fetch_array($result2)) {
		$id_grupo = $row2["id_grupo"];
		if (give_acl($id_user, $id_grupo, "IR") == 1) { // Only incident read access to view data !
			$id_group = $row2["id_grupo"];
			if ($color == 1){
				$tdcolor = "datos";
				$color = 0;
			}
			else {
				$tdcolor = "datos2";
				$color = 1;
			}
			$output .= "<tr><td class='$tdcolor' align='center'>";
			if ($row2["estado"] == 0)
				$output .= "<img src='images/dot_red.png'>";
			else
				$output .= "<img src='images/dot_green.png'>";
			$output .= "<td class='$tdcolor'>".$row2["evento"];
			$output .= "<td class='$tdcolor'>";
			if ($row2["estado"] <> 0)
				$output .= substr($row2["id_usuario"],0,8)."<a href='#' class='tip'> <span>".dame_nombre_real($row2["id_usuario"])."</span></a>";
			$output .= "<td class='".$tdcolor."f9'>".$row2["timestamp"];
			$output .= "</td></tr>";
		}
	}
	$output .= "</table>";

	if (!$return)
		echo $output;
	return $output;
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
 * @param $return Flag to return or echo the report (by default).
 */
function alert_reporting ($id_group, $period = 0, $date = 0, $return = false) {
	if (! $date)
		$date = time ();
	$datelimit = $date - $period;
	$output = '';
	$alerts = array ();
	
	$agents = get_agents_in_group ($id_group);
	foreach ($agents as $agent) {
		$agent_alerts = get_alerts_in_agent ($agent['id_agente']);
		$alerts = array_merge ($alerts, $agent_alerts);
	}
	if (sizeof ($alerts) == 0)
		return;

	$alerts_fired = array ();
	$agents = array ();
	foreach ($alerts as $alert) {
		$fires = get_alert_fires_in_period ($alert['id_agente_modulo'], $period, $date);
		if (! $fires) {
			continue;
		}
		$alerts_fired[$alert['id_aam']] = $fires;
		$data = array ();
		
		/* Add alerts fired to $agents_fired_alerts indexed by id_agent */
		$id_agent = $alert['id_agent'];
		if (!isset ($agents[$id_agent])) {
			$agents[$id_agent] = array ();
		}
		array_push ($agents[$id_agent], $alert);
	}
	$fired_percentage = round (sizeof ($alerts_fired) / sizeof ($alerts) * 100, 2);
	$not_fired_percentage = 100 - $fired_percentage;
	$output .= '<img src="reporting/fgraph.php?tipo=alerts_fired_pipe&height=150&width=280&fired='.
		$fired_percentage.'&not_fired='.$not_fired_percentage.'" style="float: right; border: 1px solid black">';
	
	$output .= '<strong>'.lang_string ('agents_with_fired_alerts').': '.sizeof ($agents).'</strong><br />';
	$output .= '<strong>'.lang_string ('fired_alerts').': '.sizeof ($alerts_fired).'</strong><br />';
	$output .= '<strong>'.lang_string ('total_alerts_monitored').': '.sizeof ($alerts).'</strong><br />';

	if ($alerts_fired) {
		$table->width = '100%';
		$table->class = 'databox';
		$table->size = array ();
		$table->size[0] = '100px';
		$table->data = array ();
		$table->head = array ();
		$table->head[0] = lang_string ('agent');
		$table->head[1] = lang_string ('alert_description');
		$table->head[2] = lang_string ('times_fired');
		$table->head[3] = lang_string ('priority');
		
		foreach ($agents as $alerts) {		
			$data = array ();
			foreach ($alerts as $alert) {
				if (! isset ($data[0]))
					$data[0] = '<strong>'.dame_nombre_agente_agentemodulo ($alert['id_agente_modulo']).'</strong>';
				else
					$data[0] = '';
				$data[1] = $alert['descripcion'];
				$data[2] = $alerts_fired[$alert['id_aam']];
				$data[3] = get_alert_priority ($alert['priority']);
				array_push ($table->data, $data);
			}
		}
		$output .= print_table ($table, true);
	}
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
 * @param $date Beggining date of the report (current date by default).
 * @param $return Flag to return or echo the report (by default).
 */
function monitor_health_reporting ($id_group, $period = 0, $date = 0, $return = false) {
	if (! $date)
		$date = time ();
	$datelimit = $date - $period;
	$output = '';
	
	$sql = sprintf ('SELECT * FROM tagente_modulo, ttipo_modulo, tagente
			WHERE id_tipo_modulo = id_tipo
			AND tagente.id_agente = tagente_modulo.id_agente
			AND ttipo_modulo.nombre like "%%_proc"
			AND tagente.id_grupo = %d', $id_group);
	$monitors = get_db_all_rows_sqlfree ($sql);
	if (sizeof ($monitors) == 0)
		return;

	$monitors_down = 0;
	$agents = array ();
	foreach ($monitors as $monitor) {
		$down = get_monitor_downs_in_period ($monitor['id_agente_modulo'], $period, $date);
		if (! $down) {
			continue;
		}
		$data = array ();
		
		/* Add monitors fired to $agents_fired_alerts indexed by id_agent */
		$id_agent = $monitor['id_agente'];
		if (!isset ($agents[$id_agent])) {
			$agents[$id_agent] = array ();
		}
		array_push ($agents[$id_agent], $monitor);
		
		$monitors_down++;
	}
	$down_percentage = round ($monitors_down / sizeof ($monitors) * 100, 2);
	$not_down_percentage = 100 - $down_percentage;
	$output .= '<img src="reporting/fgraph.php?tipo=monitors_health_pipe&height=150&width=280&down='.
		$down_percentage.'&not_down='.$not_down_percentage.'" style="float: right; border: 1px solid black">';
	
	$output .= '<strong>'.lang_string ('total_monitors').': '.sizeof ($monitors).'</strong><br />';
	$output .= '<strong>'.lang_string ('monitors_down_on_period').': '.$monitors_down.'</strong><br />';

	$table->width = '100%';
	$table->class = 'databox';
	$table->size = array ();
	$table->size[0] = '100px';
	$table->data = array ();
	$table->head = array ();
	$table->head[0] = lang_string ('agent');
	$table->head[1] = lang_string ('alert_description');
	
	foreach ($agents as $monitors) {		
		$data = array ();
		foreach ($monitors as $monitor) {
			if (! isset ($data[0]))
				$data[0] = '<strong>'.$monitor['nombre'].'</strong>';
			else
				$data[0] = '';
			$data[1] = $monitor['descripcion'];
			array_push ($table->data, $data);
		}
	}
	$output .= print_table ($table, true);
	
	if (!$return)
		echo $output;
	return $output;
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
	$output .= '<strong>'.lang_string ('agents_in_group').': '.sizeof ($agents).'</strong><br />';
	
	if (!$return)
		echo $output;
	return $output;
}

/**
 * Get a detailed report of agents in a group.
 *
 * It 
 *
 * @param $id_group Group to get the report
 * @param $return Flag to return or echo the report (by default).
 */
function agents_detailed_reporting ($id_group, $period = 0, $date = 0, $return = false) {
	$output = '';
	$agents = get_agents_in_group ($id_group);
	
	$table_modules->width = '750px';
	$table_alerts->width = '750px';
	$table_monitors->width = '750px';
	$table_monitors->align = array ();
	$table_monitors->align[1] = 'right';
	$table_monitors->head = array ();
	$table_monitors->head[0] = lang_string ('monitor');
	$table_monitors->head[1] = lang_string ('last_failure');
	$table_alerts->head = array ();
	$table_alerts->head[0] = lang_string ('type');
	$table_alerts->head[1] = lang_string ('description');
	$table_alerts->head[2] = lang_string ('min');
	$table_alerts->head[3] = lang_string ('max');
	$table_alerts->head[4] = lang_string ('threshold');
	$table_alerts->head[5] = lang_string ('last_fired');
	$table_alerts->head[6] = lang_string ('times_fired');

	$agents = get_agents_in_group ($id_group);
	$n_a_string = lang_string ('N/A').'(*)';
	foreach ($agents as $agent) {
		$monitors = array ();
		$table_modules->data = array ();
		$table_modules->head = array ();
		$table_alerts->data = array ();
		
		$modules = get_modules_in_agent ($agent['id_agente']);
		
		/* Show modules in agent */
		$output .= '<h3>'.lang_string ('agent').' - '.$agent['nombre'].'</h3>';
		$output .= '<h4>'.lang_string ('modules').'</h3>';
		$data = array ();
		foreach ($modules as $module) {
			if ($module['descripcion'] != $n_a_string && $module['descripcion'] != '')
				$data[0] = $module['descripcion'];
			else
				$data[0] = $module['nombre'];
			$module_name = giveme_module_type ($module['id_tipo_modulo']);
			if (is_module_proc ($module_name)) {
				array_push ($monitors, $module);
			}
			array_push ($table_modules->data, $data);
		}
		$output .= print_table ($table_modules, true);
		
		/* Show alerts in agent */
		$alerts = get_alerts_in_agent ($agent['id_agente']);
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
			
			array_push ($table_alerts->data, $data);
		}
		if (sizeof ($table_alerts->data)) {
			$output .= '<h4>'.lang_string ('alerts').'</h4>';
			$output .= print_table ($table_alerts, true);
		}
		
		/* Show monitor status in agent (if any) */
		if (sizeof ($monitors) == 0) {
			continue;
		}
		$table_monitors->data = array ();
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
			array_push ($table_monitors->data, $data);
		}
		if (sizeof ($table_monitors->data)) {
			$output .= '<h4>'.lang_string ('monitors').'</h4>';
			$output .= print_table ($table_monitors, true);
		}
	}
	
	if (!$return)
		echo $output;
	return $output;
}
?>
