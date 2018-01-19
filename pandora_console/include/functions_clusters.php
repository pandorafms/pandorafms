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

function agents_get_cluster_agents ($id_cluster){
  $agents = db_get_all_rows_filter("tcluster_agent", array("id_cluster" => $id_cluster), "id_agent");
	
	$post_agent = array();
	
	foreach ($agents as $key => $value) {
		
		$post_agent[$value['id_agent']] =  agents_get_alias($value['id_agent']);
	}
	
  return ($post_agent);
}

function items_get_cluster_items_id ($id_cluster){
  $items = db_get_all_rows_filter("tcluster_item", array("id_cluster" => $id_cluster), array("id"));
	
	$post_items = array();
	
	foreach ($items as $key => $value) {
		
		$post_items[$value['id']] =  items_get_name($value['id']);
	}
	
  return ($post_items);
}

function items_get_cluster_items_name ($id_cluster){
  $items = db_get_all_rows_filter("tcluster_item", array("id_cluster" => $id_cluster), array("name","id"));
	
	$post_items = array();
	
	foreach ($items as $key => $value) {
		
		$post_items[$value['name']] =  items_get_name($value['id']);
	}
	
  return ($post_items);
}


?>