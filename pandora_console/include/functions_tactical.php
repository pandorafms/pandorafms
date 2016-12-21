<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


function tactical_get_data ($id_user = false, $user_strict = false, $acltags, $returnAllGroup = false, $mode = 'group', $agent_filter = array(), $module_filter = array()) {
	global $config;
	if ($id_user == false) {
		$id_user = $config['id_user'];
	}
	
	$user_groups = array();
	$user_tags = array();
	$groups_without_tags = array();
	foreach ($acltags as $group => $tags) {
		if ($user_strict) { //Remove groups with tags
			$groups_without_tags[$group] = $group;
		}
		if ($tags != '') {
			$tags_group = explode(',', $tags);

			foreach ($tags_group as $tag) {
				$user_tags[$tag] = tags_get_name($tag);
			}
		}
	}
	
	if ($user_strict) {
		$user_groups_ids = implode(',', array_keys($groups_without_tags));
	}
	else {
		$user_groups_ids = implode(',', array_keys($acltags));
	}
	
	if (empty($user_groups_ids)) {
		$user_groups_ids = 'null';
	}
	
	if (!empty($user_groups_ids)) {
		switch ($config["dbtype"]) {
			case "mysql":
				$list_groups = db_get_all_rows_sql("
					SELECT *
					FROM tgrupo
					WHERE id_grupo IN (" . $user_groups_ids . ")
					ORDER BY nombre COLLATE utf8_general_ci ASC");
				break;
			case "postgresql":
				$list_groups = db_get_all_rows_sql("
					SELECT *
					FROM tgrupo
					WHERE id_grupo IN (" . $user_groups_ids . ")
					ORDER BY nombre ASC");
				break;
			case "oracle":
				$list_groups = db_get_all_rows_sql("
					SELECT *
					FROM tgrupo
					WHERE id_grupo IN (" . $user_groups_ids . ")
					ORDER BY nombre ASC");
				break;
		}
	}
	
	$list = array();
	$list['_monitors_critical_'] = 0;
	$list['_monitors_warning_'] = 0;
	$list['_monitors_unknown_'] = 0;
	$list['_monitors_not_init_'] = 0;
	$list['_monitors_ok_'] = 0;
	
	
	if (empty($list_groups)) {
		$list_groups = array();
	}
	
	/* 
	 * Agent cache for metaconsole.
	 * Retrieve the statistic data from the cache table.
	 */
	if (!$user_strict && is_metaconsole() && !empty($list_groups)) {
		$cache_table = 'tmetaconsole_agent';
		
		$sql_stats = "SELECT id_grupo, COUNT(id_agente) AS agents_total,
						SUM(total_count) AS monitors_total,
						SUM(normal_count) AS monitors_ok,
						SUM(warning_count) AS monitors_warning,
						SUM(critical_count) AS monitors_critical,
						SUM(unknown_count) AS monitors_unknown,
						SUM(notinit_count) AS monitors_not_init,
						SUM(fired_count) AS alerts_fired
					  FROM $cache_table
					  WHERE disabled = 0
					  	AND id_grupo IN ($user_groups_ids)
					  GROUP BY id_grupo";
		$data_stats = db_get_all_rows_sql($sql_stats);
		
		$sql_stats_unknown = "SELECT id_grupo, COUNT(id_agente) AS agents_unknown
							  FROM $cache_table
							  WHERE disabled = 0
							  	AND id_grupo IN ($user_groups_ids)
							  	AND critical_count = 0
							  	AND warning_count = 0
							  	AND unknown_count > 0
							  GROUP BY id_grupo";
		$data_stats_unknown = db_get_all_rows_sql($sql_stats_unknown);
		
		$sql_stats_not_init = "SELECT id_grupo, COUNT(id_agente) AS agents_not_init
							  FROM $cache_table
							  WHERE disabled = 0
							  	AND id_grupo IN ($user_groups_ids)
							  	AND (total_count = 0 OR total_count = notinit_count)
							  GROUP BY id_grupo";
		$data_stats_not_init = db_get_all_rows_sql($sql_stats_not_init);
		
		$sql_stats_ok = "SELECT id_grupo, COUNT(id_agente) AS agents_ok
						 FROM $cache_table
						 WHERE disabled = 0
						 	AND id_grupo IN ($user_groups_ids)
						 	AND critical_count = 0
						 	AND warning_count = 0
						 	AND unknown_count = 0
						 	AND normal_count > 0
						 GROUP BY id_grupo";
		$data_stats_ok = db_get_all_rows_sql($sql_stats_ok);
		
		$sql_stats_warning = "SELECT id_grupo, COUNT(id_agente) AS agents_warning
							  FROM $cache_table
							  WHERE disabled = 0
							  	AND id_grupo IN ($user_groups_ids)
							  	AND critical_count = 0
							  	AND warning_count > 0
							  GROUP BY id_grupo";
		$data_stats_warning = db_get_all_rows_sql($sql_stats_warning);
		
		$sql_stats_critical = "SELECT id_grupo, COUNT(id_agente) AS agents_critical
								FROM $cache_table
								WHERE disabled = 0
									AND id_grupo IN ($user_groups_ids)
									AND critical_count > 0
								GROUP BY id_grupo";
		$data_stats_critical = db_get_all_rows_sql($sql_stats_critical);
		
		if (!empty($data_stats)) {
			foreach ($data_stats as $value) {
				$list['_total_agents_'] += (int) $value['agents_total'];
				$list['_monitors_ok_'] += (int) $value['monitors_ok'];
				$list['_monitors_critical_'] += (int) $value['monitors_critical'];
				$list['_monitors_warning_'] += (int) $value['monitors_warning'];
				$list['_monitors_unknown_'] += (int) $value['monitors_unknown'];
				$list['_monitors_not_init_'] += (int) $value['monitors_not_init'];
				$list["_monitor_alerts_fire_count_"] += (int) $value['alerts_fired'];
			}
			
			if (!empty($data_stats_unknown)) {
				
				foreach ($data_stats_unknown as $value) {
					$list['_agents_unknown_'] += (int) $value['agents_unknown'];
				}
			}
			if (!empty($data_stats_not_init)) {
			
				foreach ($data_stats_not_init as $value) {
					$list["_agents_not_init_"] += (int) $value['agents_not_init'];
				}
			}
			if (!empty($data_stats_ok)) {
				
				foreach ($data_stats_ok as $value) {
					$list["_agents_ok_"] += (int) $value['agents_ok'];
				}
			}
			if (!empty($data_stats_warning)) {
				
				foreach ($data_stats_warning as $value) {
					$list["_agents_warning_"] += (int) $value['agents_warning'];
				}
			}
			if (!empty($data_stats_critical)) {
				
				foreach ($data_stats_critical as $value) {
					$list["_agents_critical_"] += (int) $value['agents_critical'];
				}
			}
		}
	}
	
	if (!$user_strict && is_metaconsole()) { // Agent cache
		// Get total count of monitors for this group, except disabled.
		$list["_monitor_checks_"] = $list["_monitors_not_init_"] + $list["_monitors_unknown_"] + $list["_monitors_warning_"] + $list["_monitors_critical_"] + $list["_monitors_ok_"];
		
		// Calculate not_normal monitors
		$list["_monitor_not_normal_"] = $list[$i]["_monitor_checks_"] - $list["_monitors_ok_"];
		
		if ($list["_monitor_not_normal_"] > 0 && $list["_monitor_checks_"] > 0) {
			$list["_monitor_health_"] = format_numeric (100 - ($list["_monitor_not_normal_"] / ($list["_monitor_checks_"] / 100)), 1);
		}
		else {
			$list["_monitor_health_"] = 100;
		}
		
		if ($list["_monitors_not_init_"] > 0 && $list["_monitor_checks_"] > 0) {
			$list["_module_sanity_"] = format_numeric (100 - ($list["_monitors_not_init_"] / ($list["_monitor_checks_"] / 100)), 1);
		}
		else {
			$list["_module_sanity_"] = 100;
		}
		
		if (isset($list[$i]["_alerts_"])) {
			if ($list["_monitors_alerts_fired_"] > 0 && $list["_alerts_"] > 0) {
				$list["_alert_level_"] = format_numeric (100 - ($list["_monitors_alerts_fired_"] / ($list["_alerts_"] / 100)), 1);
			}
			else {
				$list["_alert_level_"] = 100;
			}
		}
		else {
			$list["_alert_level_"] = 100;
			$list["_alerts_"] = 0;
		}
		
		$list["_monitor_bad_"] = $list["_monitors_critical_"] + $list["_monitors_warning_"];
		
		if ($list["_monitor_bad_"] > 0 && $list["_monitor_checks_"] > 0) {
			$list["_global_health_"] = format_numeric (100 - ($list["_monitor_bad_"] / ($list["_monitor_checks_"] / 100)), 1);
		}
		else {
			$list["_global_health_"] = 100;
		}
		
		$list["_server_sanity_"] = format_numeric (100 - $list["_module_sanity_"], 1);
	
	}
	else if (($config["realtimestats"] == 0) && !$user_strict) {
		
		$group_stat = db_get_all_rows_sql ("SELECT
			SUM(ta.normal_count) as normal, SUM(ta.critical_count) as critical,
			SUM(ta.warning_count) as warning,SUM(ta.unknown_count) as unknown,
			SUM(ta.notinit_count) as not_init, SUM(fired_count) as alerts_fired
			FROM tagente ta
			WHERE id_grupo IN ($user_groups_ids)");
		
		$list['_agents_unknown_'] = $group_stat[0]["unknown"];
		$list['_monitors_alerts_fired_'] = $group_stat[0]["alerts_fired"];		
		
		$list['_monitors_ok_'] = $group_stat[0]["normal"];
		$list['_monitors_warning_'] = $group_stat[0]["warning"];
		$list['_monitors_critical_'] = $group_stat[0]["critical"];
		$list['_monitors_unknown_'] = $group_stat[0]["unknown"];
		$list['_monitors_not_init_'] = $group_stat[0]["not_init"];
		$total_agentes = agents_get_agents (false, array('count(*) as total_agents'), 'AR',false, false);
		$list['_total_agents_'] = $total_agentes[0]['total_agents'];
		$list["_monitor_alerts_fire_count_"] = $group_stat[0]["alerts_fired"];
		
		$list['_monitors_alerts_'] = tactical_monitor_alerts (explode(',',$user_groups_ids), $user_strict,explode(',',$user_groups_ids));
		// Get total count of monitors for this group, except disabled.	
		$list["_monitor_checks_"] = $list["_monitors_not_init_"] + $list["_monitors_unknown_"] + $list["_monitors_warning_"] + $list["_monitors_critical_"] + $list["_monitors_ok_"];
		
		// Calculate not_normal monitors
		$list["_monitor_not_normal_"] = $list["_monitor_checks_"] - $list["_monitors_ok_"];
		
		if ($list["_monitor_not_normal_"] > 0 && $list["_monitor_checks_"] > 0) {
			$list["_monitor_health_"] = format_numeric (100 - ($list["_monitor_not_normal_"] / ($list["_monitor_checks_"] / 100)), 1);
		}
		else {
			$list["_monitor_health_"] = 100;
		}
		
		if ($list["_monitors_not_init_"] > 0 && $list["_monitor_checks_"] > 0) {
			$list["_module_sanity_"] = format_numeric (100 - ($list["_monitors_not_init_"] / ($list["_monitor_checks_"] / 100)), 1);
		}
		else {
			$list["_module_sanity_"] = 100;
		}
		
		if (isset($list["_alerts_"])) {
			if ($list["_monitors_alerts_fired_"] > 0 && $list["_alerts_"] > 0) {
				$list["_alert_level_"] = format_numeric (100 - ($list["_monitors_alerts_fired_"] / ($list["_alerts_"] / 100)), 1);
			}
			else {
				$list["_alert_level_"] = 100;
			}
		} 
		else {
			$list["_alert_level_"] = 100;
			$list["_alerts_"] = 0;
		}
		
		$list["_monitor_bad_"] = $list["_monitors_critical_"] + $list["_monitors_warning_"];
		
		if ($list["_monitor_bad_"] > 0 && $list["_monitor_checks_"] > 0) {
			$list["_global_health_"] = format_numeric (100 - ($list["_monitor_bad_"] / ($list["_monitor_checks_"] / 100)), 1);
		}
		else {
			$list["_global_health_"] = 100;
		}
		
		$list["_server_sanity_"] = format_numeric (100 - $list["_module_sanity_"], 1);
		
	}
	else {
		
		if ($user_strict) {
			if (empty($acltags)) {
				$_tag_condition = '';
			}
			else {
				$_tag_condition = 'AND ' . tags_get_acl_tags_module_condition($acltags,'tae');
			}
		}
		else {
			$_tag_condition = '';
		}
		
		$result_list = db_get_all_rows_sql("SELECT COUNT(*) as contado, estado
					FROM tagente_estado tae INNER JOIN tagente ta
						ON tae.id_agente = ta.id_agente
							AND ta.disabled = 0
							AND ta.id_grupo IN ( $user_groups_ids )	
					INNER JOIN tagente_modulo tam
						ON tae.id_agente_modulo = tam.id_agente_modulo
							AND tam.disabled = 0
					$_tag_condition
					GROUP BY estado");
		
		if (empty($result_list))
			$result_list = array();
		
		foreach ($result_list as $result) {
			switch ($result['estado']) {
				case AGENT_MODULE_STATUS_CRITICAL_ALERT:
					
					break;
				case AGENT_MODULE_STATUS_CRITICAL_BAD:
					$list['_monitors_critical_'] += (int)$result['contado'];
					break;
				case AGENT_MODULE_STATUS_WARNING_ALERT:
					break;
				case AGENT_MODULE_STATUS_WARNING:
					$list['_monitors_warning_'] += (int)$result['contado'];
					break;
				case AGENT_MODULE_STATUS_UNKNOWN:
					$list['_monitors_unknown_'] += (int)$result['contado'];
					break;
				case AGENT_MODULE_STATUS_NO_DATA:
				case AGENT_MODULE_STATUS_NOT_INIT:
					$list['_monitors_not_init_'] += (int)$result['contado'];
					break;
				case AGENT_MODULE_STATUS_NORMAL_ALERT:
					
					break;
				case AGENT_MODULE_STATUS_NORMAL:
					$list['_monitors_ok_'] += (int)$result['contado'];
					break;
			}
		}
		
		$list['_monitors_alerts_fired_'] = tactical_monitor_fired_alerts (explode(',',$user_groups_ids), $user_strict,explode(',',$user_groups_ids));
		$list['_monitors_alerts_'] = tactical_monitor_alerts (explode(',',$user_groups_ids), $user_strict,explode(',',$user_groups_ids));
		
		$total_agentes = agents_get_agents (false, array('count(*) as total_agents'), 'AR',false, false, 1);
		$list['_total_agents_'] = $total_agentes[0]['total_agents'];
		
		$list["_monitor_checks_"] = $list["_monitors_not_init_"] + $list["_monitors_unknown_"] + $list["_monitors_warning_"] + $list["_monitors_critical_"] + $list["_monitors_ok_"];
		
		// Calculate not_normal monitors
		$list["_monitor_not_normal_"] = $list["_monitor_checks_"] - $list["_monitors_ok_"];
	}

	if ($user_strict) {
		$i = 1;
		$list = array();
		foreach ($user_tags as $group_id => $tag_name) {
			$id = db_get_value('id_tag', 'ttag', 'name', $tag_name);

			$list[$i]['_id_'] = $id;
			$list[$i]['_name_'] = $tag_name;
			$list[$i]['_iconImg_'] = html_print_image ("images/tag_red.png", true, array ("style" => 'vertical-align: middle;'));
			$list[$i]['_is_tag_'] = 1;

			$list[$i]['_total_agents_'] = (int) tags_get_total_agents ($id, $acltags, $agent_filter, $module_filter, $config["realtimestats"]);
			$list[$i]['_agents_ok_'] = (int) tags_get_normal_agents ($id, $acltags, $agent_filter, $module_filter, $config["realtimestats"]);
			$list[$i]['_agents_warning_'] = (int) tags_get_warning_agents ($id, $acltags, $agent_filter, $module_filter, $config["realtimestats"]);
			$list[$i]['_agents_critical_'] = (int) tags_get_critical_agents ($id, $acltags, $agent_filter, $module_filter, $config["realtimestats"]);
			$list[$i]['_agents_unknown_'] = (int) tags_get_unknown_agents ($id, $acltags, $agent_filter, $module_filter, $config["realtimestats"]);
			$list[$i]['_agents_not_init_'] = (int) tags_get_not_init_agents ($id, $acltags, $agent_filter, $module_filter, $config["realtimestats"]);
			$list[$i]['_monitors_ok_'] = (int) tags_get_normal_monitors ($id, $acltags, $agent_filter, $module_filter);
			$list[$i]['_monitors_critical_'] = (int) tags_get_critical_monitors ($id, $acltags, $agent_filter, $module_filter);
			$list[$i]['_monitors_warning_'] = (int) tags_get_warning_monitors ($id, $acltags, $agent_filter, $module_filter);
			$list[$i]['_monitors_not_init_'] = (int) tags_get_not_init_monitors ($id, $acltags, $agent_filter, $module_filter);
			$list[$i]['_monitors_unknown_'] = (int) tags_get_unknown_monitors ($id, $acltags, $agent_filter, $module_filter);
			$list[$i]['_monitors_alerts_fired_'] = tags_monitors_fired_alerts($id, $acltags);

			if (! defined ('METACONSOLE')) {
				if (($list[$i]['_agents_unknown_'] == 0) && ($list[$i]['_monitors_alerts_fired_'] == 0) && ($list[$i]['_total_agents_'] == 0) && ($list[$i]['_monitors_ok_'] == 0) && ($list[$i]['_monitors_critical_'] == 0) && ($list[$i]['_monitors_warning_'] == 0) && ($list[$i]['_monitors_unknown_'] == 0) && ($list[$i]['_monitors_not_init_'] == 0) && ($list[$i]['_agents_not_init_'] == 0)) {
					unset($list[$i]);
				}
			}
			else {
				if (($list[$i]['_agents_unknown_'] == 0) && ($list[$i]['_monitors_alerts_fired_'] == 0) && ($list[$i]['_total_agents_'] == 0) && ($list[$i]['_monitors_ok_'] == 0) && ($list[$i]['_monitors_critical_'] == 0) && ($list[$i]['_monitors_warning_'] == 0)) {
					unset($list[$i]);
				}
			}
			$i++;
		}
	}

	return $list;
}

function tactical_status_modules_agents($id_user = false, $user_strict = false, $access = 'AR', $force_group_and_tag = true) {
	global $config;
	
	if ($id_user == false) {
		$id_user = $config['id_user'];
	}
	
	$acltags = tags_get_user_module_and_tags ($id_user, $access, $user_strict);
	
	// If using metaconsole, the strict users will use the agent table of every node
	if (is_metaconsole() && $user_strict) {
		$servers = metaconsole_get_servers();
		
		$result_list = array ();
		foreach ($servers as $server) {
			if (metaconsole_connect($server) != NOERR) {
				continue;
			}
			$result_list = tactical_get_data ($id_user, $user_strict,
				$acltags);
	
			if (!isset ($result_list[$server_item['_name_']])) {
				$result_list[$server_item['_name_']] = $server_item;
			}
			else {
				$result_list[$server_item['_name_']]['_monitors_ok_'] += $server_item['_monitors_ok_'];
				$result_list[$server_item['_name_']]['_monitors_critical_'] += $server_item['_monitors_critical_'];
				$result_list[$server_item['_name_']]['_monitors_warning_'] += $server_item['_monitors_warning_'];
				$result_list[$server_item['_name_']]['_agents_unknown_'] += $server_item['_agents_unknown_'];
				$result_list[$server_item['_name_']]['_total_agents_'] += $server_item['_total_agents_'];
				$result_list[$server_item['_name_']]['_monitors_alerts_fired_'] += $server_item['_monitors_alerts_fired_'];
				
				$result_list[$server_item['_name_']]['_agents_ok_'] += $server_item['_agents_ok_'];
				$result_list[$server_item['_name_']]['_agents_critical_'] += $server_item['_agents_critical_'];
				$result_list[$server_item['_name_']]['_agents_warning_'] += $server_item['_agents_warning_'];
				$result_list[$server_item['_name_']]['_monitors_alerts_'] += $server_item['_monitors_alerts_'];
				
				$result_list[$server_item['_name_']]["_monitor_checks_"] += $server_item["_monitor_checks_"];
				$result_list[$server_item['_name_']]["_monitor_not_normal_"] += $server_item["_monitor_not_normal_"];
				$result_list[$server_item['_name_']]["_monitor_health_"] += $server_item["_monitor_health_"];
				$result_list[$server_item['_name_']]["_module_sanity_"] += $server_item["_module_sanity_"];
				$result_list[$server_item['_name_']]["_alerts_"] += $server_item["_alerts_"];
				$result_list[$server_item['_name_']]["_alert_level_"] += $server_item["_alert_level_"];
				$result_list[$server_item['_name_']]["_monitor_bad_"] += $server_item["_monitor_bad_"];
				$result_list[$server_item['_name_']]["_global_health_"] += $server_item["_global_health_"];
				$result_list[$server_item['_name_']]["_server_sanity_"] += $server_item["_server_sanity_"];
				$result_list[$server_item['_name_']]["_monitor_alerts_fire_count_"] += $server_item["_monitor_alerts_fire_count_"];
				$result_list[$server_item['_name_']]["_total_checks_"] += $server_item["_total_checks_"];
				$result_list[$server_item['_name_']]["_total_alerts_"] += $server_item["_total_alerts_"];
			}
			
		}
		metaconsole_restore_db();
		return $result_list;
	}
	else {
		
		$result_list = tactical_get_data ($id_user, $user_strict,
			$acltags);
		
		return $result_list;
	}
}

function tactical_monitor_alerts ($group_array, $strict_user = false, $id_group_strict = false) {
	
	// If there are not groups to query, we jump to nextone
	
	if (empty ($group_array)) {
		return 0;
	}
	else if (!is_array ($group_array)) {
		$group_array = array($group_array);
	}
	
	$group_clause = implode (",", $group_array);
	$group_clause = "(" . $group_clause . ")";
	
	if ($strict_user) {
		$group_clause_strict = implode (",", $id_group_strict);
		$group_clause_strict = "(" . $group_clause_strict . ")";
		$sql = "SELECT COUNT(talert_template_modules.id)
			FROM talert_template_modules, tagente_modulo, tagente_estado, tagente
			WHERE tagente.id_grupo IN $group_clause_strict AND tagente_modulo.id_agente = tagente.id_agente
				AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
				AND talert_template_modules.id_agent_module = tagente_modulo.id_agente_modulo";
		$count = db_get_sql ($sql);
		return $count;
	}
	else {
		//TODO REVIEW ORACLE AND POSTGRES
		return db_get_sql ("SELECT COUNT(talert_template_modules.id)
			FROM talert_template_modules, tagente_modulo, tagente_estado, tagente
			WHERE tagente.id_grupo IN $group_clause AND tagente_modulo.id_agente = tagente.id_agente
				AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
				AND talert_template_modules.id_agent_module = tagente_modulo.id_agente_modulo");
	}
}

function tactical_monitor_fired_alerts ($group_array, $strict_user = false, $id_group_strict = false) {
	
	// If there are not groups to query, we jump to nextone
	
	if (empty ($group_array)) {
		return 0;
	}
	else if (!is_array ($group_array)) {
		$group_array = array($group_array);
	}
	
	$group_clause = implode (",", $group_array);
	$group_clause = "(" . $group_clause . ")";
	
	
	if ($strict_user) {
		$group_clause_strict = implode (",", $id_group_strict);
		$group_clause_strict = "(" . $group_clause_strict . ")";
		$sql = "SELECT COUNT(talert_template_modules.id)
		FROM talert_template_modules, tagente_modulo, tagente_estado, tagente
		WHERE tagente.id_grupo IN $group_clause_strict AND tagente_modulo.id_agente = tagente.id_agente
			AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
			AND talert_template_modules.id_agent_module = tagente_modulo.id_agente_modulo 
			AND times_fired > 0 ";
		
		$count = db_get_sql ($sql);
		return $count;
	}
	else {
		//TODO REVIEW ORACLE AND POSTGRES
		return db_get_sql ("SELECT COUNT(talert_template_modules.id)
			FROM talert_template_modules, tagente_modulo, tagente_estado, tagente
			WHERE tagente.id_grupo IN $group_clause AND tagente_modulo.id_agente = tagente.id_agente
				AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
				AND talert_template_modules.id_agent_module = tagente_modulo.id_agente_modulo 
				AND times_fired > 0");
	}
	
}
?>
