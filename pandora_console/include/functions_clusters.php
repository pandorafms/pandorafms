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



?>