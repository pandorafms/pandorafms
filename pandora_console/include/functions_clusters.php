<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

require_once($config['homedir'] . '/include/functions.php');

function clusters_get_name ($id_cluster, $case = 'none') {
	$name = (string) db_get_value ('name', 'tcluster', 'id', (int) $id_cluster);
	
	switch ($case) {
		case 'upper':
			return mb_strtoupper($name, 'UTF-8');
		case 'lower':
			return mb_strtolower($name, 'UTF-8');
		case 'none':
		default:
			return ($name);
	}
}

function clusters_get_description ($id_cluster, $case = 'none') {
	$description = (string) db_get_value ('description', 'tcluster', 'id', (int) $id_cluster);
	
	switch ($case) {
		case 'upper':
			return mb_strtoupper($description, 'UTF-8');
		case 'lower':
			return mb_strtolower($description, 'UTF-8');
		case 'none':
		default:
			return ($description);
	}
}

function clusters_get_group ($id_cluster) {
	$group = (int) db_get_value ('`group`', 'tcluster', 'id', (int) $id_cluster);
			return ($group);	
}

function items_get_name ($id, $case = 'none') {
	$name = (string) db_get_value ('name', 'tcluster_item', 'id', (int) $id);
	
	switch ($case) {
		case 'upper':
			return mb_strtoupper($name, 'UTF-8');
		case 'lower':
			return mb_strtolower($name, 'UTF-8');
		case 'none':
		default:
			return ($name);
	}
}

function agents_get_cluster_agents_alias ($id_cluster){
  $agents = db_get_all_rows_filter("tcluster_agent", array("id_cluster" => $id_cluster), "id_agent");
	
	$post_agent = array();
	
	foreach ($agents as $key => $value) {
		
		$post_agent[$value['id_agent']] =  agents_get_alias($value['id_agent']);
	}
	
  return ($post_agent);
}

function agents_get_cluster_agents_id ($id_cluster){
  $agents = db_get_all_rows_filter("tcluster_agent", array("id_cluster" => $id_cluster), "id_agent");
	
	$post_agent = array();
	
	foreach ($agents as $key => $value) {
		
		$post_agent[] =  $value['id_agent'];
	}
	
	return ($post_agent);
	
}

// function items_get_cluster_items_id ($id_cluster){
//   $items = db_get_all_rows_filter("tcluster_item", array("id_cluster" => $id_cluster), array("id"));
// 	
// 	$post_items = array();
// 	
// 	foreach ($items as $key => $value) {
// 		
// 		$post_items[$value['id']] =  items_get_name($value['id']);
// 	}
// 	
//   return ($post_items);
// }

function items_get_cluster_items_id_critical ($id_cluster){
  $items = db_get_all_rows_filter("tcluster_item", array("id_cluster" => $id_cluster,"item_type" => "AP"), array("id","is_critical"));
	
	$post_items = array();
	
	foreach ($items as $key => $value) {
		
		$post_items[$value['id']] =  $value['is_critical'];
	}
	
  return ($post_items);
}

function items_get_cluster_items_name ($id_cluster,$item_type = 'AA'){
  $items = db_get_all_rows_filter("tcluster_item", array("id_cluster" => $id_cluster,"item_type" => $item_type), array("name","id"));
	
	$post_items = array();
	
	foreach ($items as $key => $value) {
		
		$post_items[$value['name']] =  items_get_name($value['id']);
	}
	
  return ($post_items);
}

function items_get_cluster_items_id ($id_cluster,$item_type = 'AA'){
  $items = db_get_all_rows_filter("tcluster_item", array("id_cluster" => $id_cluster,"item_type" => $item_type), array("id"));
	
	$post_items = array();
	
	foreach ($items as $key => $value) {
		
		$post_items[] =  $value['id'];
	}
	
  return ($post_items);
}

function items_get_cluster_items_id_name ($id_cluster,$item_type = 'AA',$is_critical = '%%'){
  $items = db_get_all_rows_filter("tcluster_item", array("id_cluster" => $id_cluster,"item_type" => $item_type,"is_critical" => $is_critical), array("id"));
	
	$post_items = array();
	
	foreach ($items as $key => $value) {
		
		$post_items[$value['id']] =  items_get_name($value['id']);
	}
	
  return ($post_items);
}

function get_item_critical_limit_by_item_id ($id){
	
	$critical_limit = (string) db_get_value ('critical_limit', 'tcluster_item', 'id', (int) $id);
	
  return $critical_limit;
}

function get_item_warning_limit_by_item_id ($id){
	
	$warning_limit = (string) db_get_value ('warning_limit', 'tcluster_item', 'id', (int) $id);
	
  return $warning_limit;
}

function clusters_get_cluster_id_type ($id){
  $clusters = db_get_all_rows_filter("tcluster", array("id" => $id), array("id","cluster_type"));
	
	$post_clusters = array();
	
	foreach ($clusters as $key => $value) {
		
		$post_clusters[$value['id']] =  $value['cluster_type'];
	}
	
  return ($post_clusters);
}

function clusters_get_user ($id_user = 0, $only_names = false, $returnAllGroup = true, $privileges = 'RR') {
	global $config;
	
	if (!$id_user) {
		$id_user = $config['id_user'];
	}
	
	$groups = users_get_groups ($id_user, $privileges, $returnAllGroup);
	
	$all_clusters = db_get_all_rows_in_table ('tcluster', 'name');
	if ($all_clusters === false)
		return array ();
	
	$clusters = array ();
	foreach ($all_clusters as $cluster) {
		if (!in_array($cluster['id_group'], array_keys($groups)))
			continue;
		
		if ($cluster["id_user"] != $id_user && $cluster['private'])
			continue;
		
		if ($cluster["id_group"] > 0)
			if (!isset($groups[$cluster["id_group"]])) {
				continue;
			}
		
		if ($only_names) {
			$clusters[$cluster['id_cluster']] = $cluster['name'];
		}
		else {
			$clusters[$cluster['id_cluster']] = $cluster;
			$clustersCount = db_get_value_sql("SELECT COUNT(id_gs)
				FROM tcluster_source
				WHERE id_cluster = " . $cluster['id_cluster']);
			$clusters[$cluster['id_cluster']]['clusters_count'] = $clustersCount;
		}
	}
	
	return $clusters;
}

function cluster_get_status ($id_agente){
	$sql = sprintf("SELECT known_status FROM tagente_estado ae 
		INNER JOIN tagente_modulo am ON ae.id_agente_modulo = am.id_agente_modulo
		WHERE am.nombre = 'Cluster status' AND  am.id_agente = %d",$id_agente);
	
	$status = db_get_all_rows_sql($sql);
	return $status;
}

/**
 * Get the worst status of all modules of a given cluster agent.
 *
 * @param int Id agent to check.
 * @param bool Whether the call check ACLs or not 
 *
 * @return int Worst status of an cluster agent for all of its modules.
 * The value -1 is returned in case the agent has exceed its interval.
 */
function cluster_agents_get_status($id_agent = 0, $noACLs = false, $id_cluster = 0) {
	global $config;
	
	if (!$noACLs) {
		$sql_module_cluster = "SELECT am.nombre,am.id_agente_modulo, ae.datos, ae.estado  FROM tagente_modulo am 
			INNER JOIN tagente_estado ae ON ae.id_agente_modulo = am.id_agente_modulo 
			WHERE nombre IN (SELECT name FROM tcluster_item WHERE id_cluster = $id_cluster) AND am.id_agente = $id_agent";
		
		$modules = db_get_all_rows_sql ($sql_module_cluster);
	}
	
	if (!isset($modules) || empty($modules) || count($modules) == 0) {
		return AGENT_MODULE_STATUS_NOT_INIT;
	}
	
	$modules_status = array();
	$modules_async = 0;
	foreach ($modules as $module) {
		
		$modules_status[] = $module['estado'];
		
		$module_type = modules_get_agentmodule_type($module['id_agente_modulo']);
		if (($module_type >= 21 && $module_type <= 23) || $module_type == 100) {
			$modules_async++;
		}
	}
	
	// If all the modules are asynchronous or keep alive, the group cannot be unknown
	if ($modules_async < count($modules)) {
		$time = get_system_time ();
		
		switch ($config["dbtype"]) {
			case "mysql":
				$status = db_get_value_filter ('COUNT(*)',
					'tagente',
					array ('id_agente' => (int) $id_agent,
						'UNIX_TIMESTAMP(ultimo_contacto) + intervalo * 2 > '.$time));
				break;
			case "postgresql":
				$status = db_get_value_filter ('COUNT(*)',
					'tagente',
					array ('id_agente' => (int) $id_agent,
						'ceil(date_part(\'epoch\', ultimo_contacto)) + intervalo * 2 > '.$time));
				break;
			case "oracle":
				$status = db_get_value_filter ('count(*)',
					'tagente',
					array ('id_agente' => (int) $id_agent,
						'ceil((to_date(ultimo_contacto, \'YYYY-MM-DD HH24:MI:SS\') - to_date(\'19700101000000\',\'YYYYMMDDHH24MISS\')) * (' . SECONDS_1DAY . ')) > ' . $time));
				break;
		}
		
		if (! $status)
			return AGENT_MODULE_STATUS_UNKNOWN;
	}
	
	// Checking if any module has alert fired
	if (is_int(array_search(AGENT_MODULE_STATUS_CRITICAL_ALERT, $modules_status))) {
		return AGENT_MODULE_STATUS_CRITICAL_ALERT;
	}
	// Checking if any module has alert fired
	elseif (is_int(array_search(AGENT_MODULE_STATUS_WARNING_ALERT, $modules_status))) {
		return AGENT_MODULE_STATUS_WARNING_ALERT;
	}
	// Checking if any module has critical status
	elseif (is_int(array_search(AGENT_MODULE_STATUS_CRITICAL_BAD, $modules_status))) {
		return AGENT_MODULE_STATUS_CRITICAL_BAD;
	}
	// Checking if any module has critical status
	elseif (is_int(array_search(AGENT_MODULE_STATUS_NORMAL_ALERT, $modules_status))) {
		return AGENT_STATUS_ALERT_FIRED;
	}
	// Checking if any module has warning status
	elseif (is_int(array_search(AGENT_MODULE_STATUS_WARNING,$modules_status))) {
		return AGENT_MODULE_STATUS_WARNING;
	}
	// Checking if any module has unknown status
	elseif (is_int(array_search(AGENT_MODULE_STATUS_UNKNOWN, $modules_status))) {
		return AGENT_MODULE_STATUS_UNKNOWN;
	}
	else {
		return AGENT_MODULE_STATUS_NORMAL;
	}
}



?>