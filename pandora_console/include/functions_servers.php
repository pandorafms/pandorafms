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
 * @subpackage Servers
 */

/**
 * Get a server.
 *
 * @param int Server id to get.
 * @param array Extra filter.
 * @param array Fields to get.
 *
 * @return Server with the given id. False if not available.
 */
function servers_get_server ($id_server, $filter = false, $fields = false) {
	if (empty ($id_server))
		return false;
	if (! is_array ($filter))
		$filter = array ();
	$filter['id_server'] = $id_server;
	
	return @db_get_row_filter ('tserver', $filter, $fields);
}

/**
 * Get all the server availables.
 *
 * @return All the servers available.
 */
function servers_get_names () {
	$all_servers = @db_get_all_rows_filter ('tserver', false, array ('DISTINCT(name) as name'));
	if ($all_servers === false)
		return array ();
	
	$servers = array ();
	foreach ($all_servers as $server) {
		$servers[$server['name']] = $server['name'];
	}
	return $servers;
}

/**
 * This function forces a recon task to be queued by the server asap
 */
function servers_force_recon_task($id_recon_task) {
	$values = array('utimestamp' => 0, 'status' => 1);
	db_process_sql_update('trecon_task', $values, array('id_rt' => $id_recon_task));
}

/**
 * This function will get several metrics from the database to get info about server performance
 * @return array with several data 
 */
function servers_get_performance () {
	
	global $config;
	
	$data = array();
	$data["total_modules"] = 0;
	$data["total_remote_modules"] = 0;
	$data["total_local_modules"] = 0;
	$data["avg_interval_total_modules"] = array();
	$data["avg_interval_remote_modules"] = array();
	$data["avg_interval_local_modules"] = 0;
	
	if ($config["realtimestats"] == 1){
		$counts = db_get_all_rows_sql ("SELECT tagente_modulo.id_modulo, COUNT(tagente_modulo.id_agente_modulo) modules
			FROM tagente_modulo, tagente_estado, tagente
			WHERE tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
				AND tagente_modulo.disabled = 0 AND delete_pending = 0 AND (utimestamp > 0 OR (id_tipo_modulo = 100 OR (id_tipo_modulo > 21 AND id_tipo_modulo < 23)))
				AND tagente.disabled = 0 AND tagente.id_agente = tagente_estado.id_agente GROUP BY tagente_modulo.id_modulo");

		if(empty($counts)) {
			$counts = array();
		}

		foreach($counts as $c) {
			switch($c['id_modulo']) {
				case MODULE_DATA:
					$data["total_local_modules"] = $c['modules'];
					break;
				case MODULE_NETWORK:
					$data["total_network_modules"] = $c['modules'];
					break;
				case MODULE_PLUGIN:
					$data["total_plugin_modules"] = $c['modules'];
					break;
				case MODULE_PREDICTION:
					$data["total_prediction_modules"] = $c['modules'];
					break;
				case MODULE_WMI:
					$data["total_wmi_modules"] = $c['modules'];
					break;
				case MODULE_WEB:
					$data["total_web_modules"] = $c['modules'];
					break;
			}
			
			if($c['id_modulo'] != MODULE_DATA) {
				$data["total_remote_modules"] += $c['modules'];
			}
			
			$data["total_modules"] += $c['modules'];
		}
	}
	else {
		$counts = db_get_all_rows_sql ("SELECT server_type, my_modules modules FROM tserver GROUP BY server_type");

		if(empty($counts)) {
			$counts = array();
		}
		
		foreach($counts as $c) {
			switch ($c['server_type']) {
				case SERVER_TYPE_DATA:
					$data["total_local_modules"] = $c['modules'];
					break;
				case SERVER_TYPE_NETWORK:
				case SERVER_TYPE_SNMP:
				case SERVER_TYPE_ENTERPRISE_ICMP:
				case SERVER_TYPE_ENTERPRISE_SNMP:
					$data["total_network_modules"] = $c['modules'];
					break;
				case SERVER_TYPE_PLUGIN:
					$data["total_plugin_modules"] = $c['modules'];
					break;
				case SERVER_TYPE_PREDICTION:
					$data["total_prediction_modules"] = $c['modules'];
					break;
				case SERVER_TYPE_WMI:
					$data["total_wmi_modules"] = $c['modules'];
					break;
				case SERVER_TYPE_WEB:
					$data["total_web_modules"] = $c['modules'];
					break;
				case SERVER_TYPE_EXPORT:
				case SERVER_TYPE_INVENTORY:
				case SERVER_TYPE_EVENT:
				case SERVER_TYPE_RECON:
					break;
			}
			
			if($c['server_type'] != SERVER_TYPE_DATA) {
				$data["total_remote_modules"] += $c['modules'];
			}
			
			$data["total_modules"] += $c['modules'];
		}
	}
	
	// Avg of modules interval when modules have module_interval > 0
	$interval_avgs_modules = db_get_all_rows_sql ("SELECT count(tagente_modulo.id_modulo) modules , tagente_modulo.id_modulo, AVG(tagente_modulo.module_interval) avg_interval
					FROM tagente_modulo, tagente_estado, tagente
				WHERE tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
					AND tagente_modulo.disabled = 0 AND module_interval > 0 AND (utimestamp > 0 OR (id_tipo_modulo = 100 OR (id_tipo_modulo > 21 AND id_tipo_modulo < 23)))
					AND delete_pending = 0
					AND tagente.disabled = 0 AND tagente.id_agente = tagente_estado.id_agente GROUP BY tagente_modulo.id_modulo");
	
	if(empty($interval_avgs_modules)) {
		$interval_avgs_modules = array();
	}
	
	// Transform into a easily format
	foreach($interval_avgs_modules as $iamodules) {
		$interval_avgs[$iamodules['id_modulo']]['avg_interval'] = $iamodules['avg_interval'];
		$interval_avgs[$iamodules['id_modulo']]['modules'] = $iamodules['modules'];
	}
	
	// Avg of agents interval when modules have module_interval == 0
	$interval_avgs_agents = db_get_all_rows_sql ("SELECT count(tagente_modulo.id_modulo) modules , tagente_modulo.id_modulo, AVG(tagente.intervalo) avg_interval
					FROM tagente_modulo, tagente_estado, tagente
				WHERE tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
					AND tagente_modulo.disabled = 0 AND module_interval = 0 AND (utimestamp > 0 OR (id_tipo_modulo = 100 OR (id_tipo_modulo >= 21 AND id_tipo_modulo <= 23)))
					AND delete_pending = 0
					AND tagente.disabled = 0 AND tagente.id_agente = tagente_estado.id_agente GROUP BY tagente_modulo.id_modulo");
	
	if(empty($interval_avgs_agents)) {
		$interval_avgs_agents = array();
	}
	
	// Merge with the previous calculated array
	foreach($interval_avgs_agents as $iaagents) {
		if(!isset($interval_avgs[$iaagents['id_modulo']]['modules'])) {
			$interval_avgs[$iaagents['id_modulo']]['avg_interval'] = $iaagents['avg_interval'];		
			$interval_avgs[$iaagents['id_modulo']]['modules'] = $iaagents['modules'];
		}
		else {
			$interval_avgs[$iaagents['id_modulo']]['avg_interval'] = servers_get_avg_interval($interval_avgs[$iaagents['id_modulo']], $iaagents);
			$interval_avgs[$iaagents['id_modulo']]['modules'] += $iaagents['modules'];
		}
	}
	
	foreach($interval_avgs as $id_modulo => $ia) {
		switch($id_modulo) {
			case MODULE_DATA:
				$data["avg_interval_local_modules"] = $ia['avg_interval'];
				$data["local_modules_rate"] = servers_get_rate($data["avg_interval_local_modules"], $data["total_local_modules"]);
				break;
			case MODULE_NETWORK:
				$data["avg_interval_network_modules"] = $ia['avg_interval'];
				$data["network_modules_rate"] = servers_get_rate($data["avg_interval_network_modules"], $data["total_network_modules"]);
				break;
			case MODULE_PLUGIN:
				$data["avg_interval_plugin_modules"] = $ia['avg_interval'];
				$data["plugin_modules_rate"] = servers_get_rate($data["avg_interval_plugin_modules"], $data["total_plugin_modules"]);
				break;
			case MODULE_PREDICTION:
				$data["avg_interval_prediction_modules"] = $ia['avg_interval'];
				$data["prediction_modules_rate"] = servers_get_rate($data["avg_interval_prediction_modules"], $data["total_prediction_modules"]);
				break;
			case MODULE_WMI:
				$data["avg_interval_wmi_modules"] = $ia['avg_interval'];
				$data["wmi_modules_rate"] = servers_get_rate($data["avg_interval_wmi_modules"], $data["total_wmi_modules"]);
				break;
			case MODULE_WEB:
				$data["avg_interval_web_modules"] = $ia['avg_interval'];
				$data["web_modules_rate"] = servers_get_rate($data["avg_interval_web_modules"], $data["total_web_modules"]);
				break;
		}
		
		if($id_modulo != MODULE_DATA) {
			$data["avg_interval_remote_modules"][] = $ia['avg_interval'];
		}
		
		$data["avg_interval_total_modules"][] = $ia['avg_interval'];
	}
	
	if (empty($data["avg_interval_remote_modules"])) {
		$data["avg_interval_remote_modules"] = 0;
	}
	else {
		$data["avg_interval_remote_modules"] = array_sum($data["avg_interval_remote_modules"]) / count($data["avg_interval_remote_modules"]);
	}
	
	if (empty($data["avg_interval_total_modules"])) {
		$data["avg_interval_total_modules"] = 0;
	}
	else {
		$data["avg_interval_total_modules"] = array_sum($data["avg_interval_total_modules"]) / count($data["avg_interval_total_modules"]);
	}
	
	$data["remote_modules_rate"] = servers_get_rate($data["avg_interval_remote_modules"], $data["total_remote_modules"]);
	$data["total_modules_rate"] = servers_get_rate($data["avg_interval_total_modules"], $data["total_modules"]);

	return ($data);
}

/**
 * Get avg interval
 *
 * @param mixed Array with avg and count data of first part
 * @param mixed Array with avg and count data of second part
 *
 * @return float number of avg modules between two parts
 */
 
function servers_get_avg_interval($modules_avg_interval1, $modules_avg_interval2) {
	$total_modules = $modules_avg_interval1['modules'] + $modules_avg_interval2['modules'];
	
	$parcial1 = $modules_avg_interval1['avg_interval'] * $modules_avg_interval1['modules'];
	$parcial2 = $modules_avg_interval2['avg_interval'] * $modules_avg_interval2['modules'];
	
	return ($parcial1 + $parcial2) / $total_modules;
}
/**
 * Get server rate
 *
 * @param float avg of interval of these modules
 * @param int number of modules
 *
 * @return float number of modules processed by second
 */
function servers_get_rate($avg_interval, $num_modules) {
	return $avg_interval > 0 ? ($num_modules / $avg_interval) : 0;
}

/**
 * This function will get all the server information in an array or a specific server
 *
 * @param mixed An optional integer or array of integers to select specific servers
 *
 * @return mixed False in case the server doesn't exist or an array with info.
 */
function servers_get_info ($id_server = -1) {
	global $config;

	if (is_array ($id_server)) {
		$select_id = " WHERE id_server IN (".implode (",", $id_server).")";
	}
	elseif ($id_server > 0) {
		$select_id = " WHERE id_server IN (".(int) $id_server.")";
	}
	else {
		$select_id = "";
	}
	
	$sql = "SELECT * FROM tserver".$select_id . " ORDER BY server_type";
	$result = db_get_all_rows_sql ($sql);
	$time = get_system_time ();
	
	if (empty ($result)) {
		return false;
	}
	
	$return = array ();
	foreach ($result as $server) {
		switch ($server['server_type']) {
			case SERVER_TYPE_DATA:
				$server["img"] = html_print_image ("images/data.png", true, array ("title" => __('Data server')));
				$server["type"] = "data";
				$id_modulo = 1;
				break;
			case SERVER_TYPE_NETWORK:
				$server["img"] = html_print_image ("images/network.png", true, array ("title" => __('Network server')));
				$server["type"] = "network";
				$id_modulo = 2;
				break;
			case SERVER_TYPE_SNMP:
				$server["img"] = html_print_image ("images/snmp.png", true, array ("title" => __('SNMP Trap server')));
				$server["type"] = "snmp";
				$id_modulo = 0;
				break;
			case SERVER_TYPE_RECON:
				$server["img"] = html_print_image ("images/recon.png", true, array ("title" => __('Recon server')));
				$server["type"] = "recon";
				$id_modulo = 0;
				break;
			case SERVER_TYPE_PLUGIN:
				$server["img"] = html_print_image ("images/plugin.png", true, array ("title" => __('Plugin server')));
				$server["type"] = "plugin";
				$id_modulo = 4;
				break;
			case SERVER_TYPE_PREDICTION:
				$server["img"] = html_print_image ("images/chart_bar.png", true, array ("title" => __('Prediction server')));
				$server["type"] = "prediction";
				$id_modulo = 5;
				break;
			case SERVER_TYPE_WMI:
				$server["img"] = html_print_image ("images/wmi.png", true, array ("title" => __('WMI server')));
				$server["type"] = "wmi";
				$id_modulo = 6;
				break;
			case SERVER_TYPE_EXPORT:
				$server["img"] = html_print_image ("images/server_export.png", true, array ("title" => __('Export server')));
				$server["type"] = "export";
				$id_modulo = 0;
				break;
			case SERVER_TYPE_INVENTORY:
				$server["img"] = html_print_image ("images/page_white_text.png", true, array ("title" => __('Inventory server')));
				$server["type"] = "inventory";
				$id_modulo = 0;
				break;
			case SERVER_TYPE_WEB:
				$server["img"] = html_print_image ("images/world.png", true, array ("title" => __('Web server')));
				$server["type"] = "web";
				$id_modulo = 0;
				break;
			case SERVER_TYPE_EVENT:
				$server["img"] = html_print_image ("images/lightning_go.png", true, array ("title" => __('Event server')));
				$server["type"] = "event";
				$id_modulo = 2;
				break;
			case SERVER_TYPE_ENTERPRISE_ICMP:
				$server["img"] = html_print_image ("images/network.png", true, array ("title" => __('Enterprise ICMP server')));
				$server["type"] = "enterprise icmp";
				$id_modulo = 2;
				break;
			case SERVER_TYPE_ENTERPRISE_SNMP:
				$server["img"] = html_print_image ("images/network.png", true, array ("title" => __('Enterprise SNMP server')));
				$server["type"] = "enterprise snmp";
				$id_modulo = 2;
				break;
			default:
				$server["img"] = '';
				$server["type"] = "unknown";
				$id_modulo = 0;
				break;
		}
		
		if ($config["realtimestats"] == 0) {
			// ---------------------------------------------------------------
			// Take data from database if not realtime stats
			// ---------------------------------------------------------------
			
			$server["lag"] = db_get_sql ("SELECT lag_time FROM tserver WHERE id_server = ".$server["id_server"]);
			$server["module_lag"] = db_get_sql ("SELECT lag_modules FROM tserver WHERE id_server = ".$server["id_server"]);
			$server["modules"] = db_get_sql ("SELECT my_modules FROM tserver WHERE id_server = ".$server["id_server"]);
			$server["modules_total"] = db_get_sql ("SELECT total_modules_running FROM tserver WHERE id_server = ".$server["id_server"]);
			
		}
		else {
			
			// ---------------------------------------------------------------
			// Take data in realtime
			// ---------------------------------------------------------------
			
			
			
			$server["module_lag"] = 0;
			$server["lag"] = 0;
			
			// Export server
			if ($server["server_type"] == 8) {
				
				// Get modules exported by this server
				$server["modules"] = db_get_sql ("SELECT COUNT(tagent_module_inventory.id_agent_module_inventory) FROM tagente, tagent_module_inventory WHERE tagente.disabled=0 AND tagent_module_inventory.id_agente = tagente.id_agente AND tagente.server_name = '" . $server["name"] . "'");
				
				// Get total exported modules
				$server["modules_total"] = db_get_sql ("SELECT COUNT(tagent_module_inventory.id_agent_module_inventory) FROM tagente, tagent_module_inventory WHERE tagente.disabled=0 AND tagent_module_inventory.id_agente = tagente.id_agente");
				
				// Get the module lag
				$server["module_lag"] = db_get_sql ("SELECT COUNT(tagent_module_inventory.id_agent_module_inventory) AS module_lag
					FROM tagente, tagent_module_inventory
					WHERE utimestamp > 0
					AND tagent_module_inventory.id_agente = tagente.id_agente
					AND tagent_module_inventory.interval > 0
					AND tagente.server_name = '" . $server["name"] . "'
					AND (UNIX_TIMESTAMP() - utimestamp) < (tagent_module_inventory.interval * 10)
					AND (UNIX_TIMESTAMP() - utimestamp) > tagent_module_inventory.interval");
				
				// Get the lag
				$server["lag"] = db_get_sql ("SELECT AVG(UNIX_TIMESTAMP() - utimestamp - tagent_module_inventory.interval)
					FROM tagente, tagent_module_inventory
					WHERE utimestamp > 0
					AND tagent_module_inventory.id_agente = tagente.id_agente
					AND tagent_module_inventory.interval > 0
					AND tagente.server_name = '" . $server["name"] . "'
					AND (UNIX_TIMESTAMP() - utimestamp) < (tagent_module_inventory.interval * 10)
					AND (UNIX_TIMESTAMP() - utimestamp) > tagent_module_inventory.interval");
			// Export server
			}
			else if ($server["server_type"] == 7) {
				
				// Get modules exported by this server
				$server["modules"] = db_get_sql ("SELECT COUNT(tagente_modulo.id_agente_modulo) FROM tagente, tagente_modulo, tserver_export WHERE tagente.disabled=0 AND tagente_modulo.id_agente = tagente.id_agente AND tagente_modulo.id_export = tserver_export.id AND tserver_export.id_export_server = " . $server["id_server"]);
				
				// Get total exported modules
				$server["modules_total"] = db_get_sql ("SELECT COUNT(tagente_modulo.id_agente_modulo) FROM tagente, tagente_modulo WHERE tagente.disabled=0 AND tagente_modulo.id_agente = tagente.id_agente AND tagente_modulo.id_export != 0");
				
				$server["lag"] = 0;
				$server["module_lag"] = 0;
			
			}
			// Recon server
			else if ($server["server_type"] == 3) {
				
				$server["name"] = '<a href="index.php?sec=estado&amp;sec2=operation/servers/recon_view&amp;server_id='.$server["id_server"].'">'.$server["name"].'</a>';
				
				//Total jobs running on this recon server
				$server["modules"] = db_get_sql ("SELECT COUNT(id_rt)
					FROM trecon_task
					WHERE id_recon_server = ".$server["id_server"]);
				
				//Total recon jobs (all servers)
				$server["modules_total"] = db_get_sql ("SELECT COUNT(status) FROM trecon_task");
				
				//Lag (take average active time of all active tasks)
				$server["module_lag"] = 0;
				
				switch ($config["dbtype"]) {
					case "mysql":
						$server["lag"] = db_get_sql ("SELECT UNIX_TIMESTAMP() - utimestamp from trecon_task WHERE UNIX_TIMESTAMP()  > (utimestamp + interval_sweep) AND id_recon_server = ".$server["id_server"]);
						
						$server["module_lag"] = db_get_sql ("SELECT COUNT(id_rt) FROM trecon_task WHERE UNIX_TIMESTAMP()  > (utimestamp + interval_sweep) AND id_recon_server = ".$server["id_server"]);
						break;
					case "postgresql":
						$server["lag"] = db_get_sql ("SELECT ceil(date_part('epoch', CURRENT_TIMESTAMP)) - utimestamp from trecon_task WHERE ceil(date_part('epoch', CURRENT_TIMESTAMP))  > (utimestamp + interval_sweep) AND id_recon_server = ".$server["id_server"]);
						
						$server["module_lag"] = db_get_sql ("SELECT COUNT(id_rt) FROM trecon_task WHERE ceil(date_part('epoch', CURRENT_TIMESTAMP))  > (utimestamp + interval_sweep) AND id_recon_server = ".$server["id_server"]);
						break;
					case "oracle":
						$server["lag"] = db_get_sql ("SELECT ceil((sysdate - to_date('19700101000000','YYYYMMDDHH24MISS')) * (86400)) - utimestamp from trecon_task WHERE ceil((sysdate - to_date('19700101000000','YYYYMMDDHH24MISS')) * (86400))  > (utimestamp + interval_sweep) AND id_recon_server = ".$server["id_server"]);
						
						$server["module_lag"] = db_get_sql ("SELECT COUNT(id_rt) FROM trecon_task WHERE ceil((sysdate - to_date('19700101000000','YYYYMMDDHH24MISS')) * (86400))  > (utimestamp + interval_sweep) AND id_recon_server = ".$server["id_server"]);
						break;
				}
			}
			else {
				
				// ---------------------------------------------------------------
				// Data, Plugin, WMI, Network and Others
				
				$server["modules"] = db_get_sql ("SELECT count(tagente_estado.id_agente_modulo) FROM tagente_estado, tagente_modulo, tagente WHERE tagente.disabled=0 AND tagente_modulo.id_agente = tagente.id_agente AND tagente_modulo.disabled = 0 AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo AND tagente_estado.running_by = ".$server["id_server"]);
				
				$server["modules_total"] = db_get_sql ("SELECT count(tagente_estado.id_agente_modulo) FROM tserver, tagente_estado, tagente_modulo, tagente WHERE tagente.disabled=0 AND tagente_modulo.id_agente = tagente.id_agente AND tagente_modulo.disabled = 0 AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo AND tagente_estado.running_by = tserver.id_server AND tserver.server_type = ".$server["server_type"]);
				
				// Remote servers LAG Calculation (server_type != 0)
				if ($server["server_type"] != 0) {
					switch ($config["dbtype"]) {
						case "mysql":
							$result = db_get_row_sql ("SELECT COUNT(tagente_modulo.id_agente_modulo) AS module_lag, AVG(UNIX_TIMESTAMP() - utimestamp - current_interval) AS lag FROM tagente_estado, tagente_modulo, tagente
								WHERE utimestamp > 0
								AND tagente.disabled = 0
								AND tagente.id_agente = tagente_estado.id_agente
								AND tagente_modulo.disabled = 0
								AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
								AND current_interval > 0
								AND running_by = ".$server["id_server"]."
								AND (UNIX_TIMESTAMP() - utimestamp) < ( current_interval * 10)
								AND (UNIX_TIMESTAMP() - utimestamp) > current_interval");
							break;
						case "postgresql":
							$result = db_get_row_sql ("SELECT COUNT(tagente_modulo.id_agente_modulo) AS module_lag, AVG(ceil(date_part('epoch', CURRENT_TIMESTAMP)) - utimestamp - current_interval) AS lag FROM tagente_estado, tagente_modulo, tagente
								WHERE utimestamp > 0
								AND tagente.disabled = 0
								AND tagente.id_agente = tagente_estado.id_agente
								AND tagente_modulo.disabled = 0
								AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
								AND current_interval > 0
								AND running_by = ".$server["id_server"]."
								AND (ceil(date_part('epoch', CURRENT_TIMESTAMP)) - utimestamp) < ( current_interval * 10)
								AND (ceil(date_part('epoch', CURRENT_TIMESTAMP)) - utimestamp) > current_interval");
							break;
						case "oracle":
							$result = db_get_row_sql ("SELECT COUNT(tagente_modulo.id_agente_modulo) AS module_lag, AVG(ceil((sysdate - to_date('19700101000000','YYYYMMDDHH24MISS')) * (86400)) - utimestamp - current_interval) AS lag FROM tagente_estado, tagente_modulo, tagente
								WHERE utimestamp > 0
								AND tagente.disabled = 0
								AND tagente.id_agente = tagente_estado.id_agente
								AND tagente_modulo.disabled = 0
								AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
								AND current_interval > 0
								AND running_by = ".$server["id_server"]."
								AND (ceil((sysdate - to_date('19700101000000','YYYYMMDDHH24MISS')) * (86400)) - utimestamp) < ( current_interval * 10)
								AND (ceil((sysdate - to_date('19700101000000','YYYYMMDDHH24MISS')) - utimestamp) * (86400)) > current_interval");
							break;
					}
				}
				else {
					// Local/Dataserver server LAG calculation:
					switch ($config["dbtype"]) {
						case "mysql":
							$result = db_get_row_sql ("SELECT COUNT(tagente_modulo.id_agente_modulo) AS module_lag, AVG(UNIX_TIMESTAMP() - utimestamp - current_interval) AS lag FROM tagente_estado, tagente_modulo, tagente
								WHERE utimestamp > 0
								AND tagente.disabled = 0
								AND tagente.id_agente = tagente_estado.id_agente
								AND tagente_modulo.disabled = 0
								AND tagente_modulo.id_tipo_modulo < 5
								AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
								AND current_interval > 0
								AND (UNIX_TIMESTAMP() - utimestamp) < ( current_interval * 10)
								AND running_by = ".$server["id_server"]."
								AND (UNIX_TIMESTAMP() - utimestamp) > (current_interval * 1.1)");
							break;
						case "postgresql":
							$result = db_get_row_sql ("SELECT COUNT(tagente_modulo.id_agente_modulo) AS module_lag, AVG(ceil(date_part('epoch', CURRENT_TIMESTAMP)) - utimestamp - current_interval) AS lag FROM tagente_estado, tagente_modulo, tagente
								WHERE utimestamp > 0
								AND tagente.disabled = 0
								AND tagente.id_agente = tagente_estado.id_agente
								AND tagente_modulo.disabled = 0
								AND tagente_modulo.id_tipo_modulo < 5 
								AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
								AND current_interval > 0
								AND (ceil(date_part('epoch', CURRENT_TIMESTAMP)) - utimestamp) < ( current_interval * 10)
								AND running_by = ".$server["id_server"]."
								AND (ceil(date_part('epoch', CURRENT_TIMESTAMP)) - utimestamp) > (current_interval * 1.1)");
							break;
						case "oracle":
							$result = db_get_row_sql ("SELECT COUNT(tagente_modulo.id_agente_modulo) AS module_lag, AVG(ceil((sysdate - to_date('19700101000000','YYYYMMDDHH24MISS')) * (86400)) - utimestamp - current_interval) AS lag FROM tagente_estado, tagente_modulo, tagente
								WHERE utimestamp > 0
								AND tagente.disabled = 0
								AND tagente.id_agente = tagente_estado.id_agente
								AND tagente_modulo.disabled = 0
								AND tagente_modulo.id_tipo_modulo < 5 
								AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
								AND current_interval > 0
								AND (ceil((sysdate - to_date('19700101000000','YYYYMMDDHH24MISS')) * (86400)) - utimestamp) < ( current_interval * 10)
								AND running_by = ".$server["id_server"]."
								AND (ceil((sysdate - to_date('19700101000000','YYYYMMDDHH24MISS')) * (86400)) - utimestamp) > (current_interval * 1.1)");
							break;
					}
				}
				
				// Lag over current_interval * 2 is not lag, it's a timed out module
				
				if (!empty ($result["lag"])) {
					$server["lag"] = $result["lag"];
				} 
				
				if (!empty ($result["module_lag"])) {
					$server["module_lag"] = $result["module_lag"];
				}
			}
		} // Take data for realtime mode
		
		if (isset($server["module_lag"]))
			$server["lag_txt"] = ($server["lag"] == 0 ? '-' : human_time_description_raw ($server["lag"])) . " / ". $server["module_lag"];
		else
			$server["lag_txt"] = "";
		
		if ($server["modules_total"] > 0) {
			$server["load"] = round ($server["modules"] / $server["modules_total"] * 100); 
		}
		else {
			$server["load"] = 0;
		}
		
		//Push the raw data on the return stack
		$return[$server["id_server"]] = $server;
	} // Main foreach
	
	return $return;
}

/**
 * Get the server name.
 *
 * @param int Server id.
 *
 * @return string Name of the given server
 */
function servers_get_name ($id_server) {
	return (string) db_get_value ('name', 'tserver', 'id_server', (int) $id_server);
}

/**
 * Return a string containing image tag for a given target id (server)
 * TODO: Make this print_servertype_icon and move to functions_ui.php. Make XHTML compatible. Make string translatable
 *
 * @deprecated Use print_servertype_icon instead
 *
 * @param int Server type id
 *
 * @return string Fully formatted IMG HTML tag with icon
 */
function servers_show_type ($id) {
	global $config;
	
	switch ($id) {
		case 1:
			return html_print_image("images/database.png", true, array("title" => "Pandora FMS Data server"));
			break;
		case 2:
			return html_print_image("images/network.png", true, array("title" => "Pandora FMS Network server"));
			break;
		case 4:
			return html_print_image("images/plugin.png", true, array("title" => "Pandora FMS Plugin server"));
			break;
		case 5:
			return html_print_image("images/chart_bar.png", true, array("title" => "Pandora FMS Prediction server"));
			break;
		case 6:
			return html_print_image("images/wmi.png", true, array("title" => "Pandora FMS WMI server"));
			break;
		case 7:
			return html_print_image("images/server_web.png", true, array("title" => "Pandora FMS WEB server"));
			break;
		default:
			return "--";
			break;
	}
}

/**
 * Get the numbers of servers up.
 *
 * This check assumes that server_keepalive should be at least 15 minutes.
 *
 * @return int The number of servers alive.
 */
function servers_check_status () {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			$sql = "SELECT COUNT(id_server)
				FROM tserver
				WHERE status = 1
					AND keepalive > NOW() - INTERVAL 15 MINUTE";
			break;
		case "postgresql":
			$sql = "SELECT COUNT(id_server)
				FROM tserver
				WHERE status = 1
					AND keepalive > NOW() - INTERVAL '15 MINUTE'";
			break;
		case "oracle":
			$sql = "SELECT COUNT(id_server)
				FROM tserver
				WHERE status = 1
					AND keepalive > systimestamp - INTERVAL '15' MINUTE";
			break;
	}
	$status = (int) db_get_sql ($sql); //Cast as int will assure a number value
	// This function should just ack of server down, not set it down.
	return $status;
}

/**
 * @deprecated use servers_get_info instead
 * Get statistical information for a given server
 *
 * @param int Server id to get status.
 *
 * @return array Server info array
 */
function servers_get_status ($id_server) {
	$serverinfo = servers_get_info ($id_server);
	return $serverinfo[$id_server];
}
?>
