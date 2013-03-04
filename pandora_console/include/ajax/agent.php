<?php

//Pandora FMS- http://pandorafms.com
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

global $config;
require_once ('include/functions_agents.php');
include_once ('include/functions_reporting.php');
enterprise_include_once ('include/functions_metaconsole.php');

// Get list of agent + ip
// Params:
// * search_agents 1
// * id_agent 
// * q
// * id_group
$search_agents = (bool) get_parameter ('search_agents');
$get_agents_group = (bool) get_parameter('get_agents_group', false);
$force_local = (bool) get_parameter('force_local', false);

if ($get_agents_group) {
	$id_group = (int)get_parameter('id_group', -1);
	$mode = (string)get_parameter('mode', 'json');
	$id_server = (int)get_parameter('id_server', 0);
	
	$return = array();
	if ($id_group != -1) {
		if (defined('METACONSOLE')) {
			
			if ($id_server == 0) {
				$servers = $servers = db_get_all_rows_sql ("SELECT *
					FROM tmetaconsole_setup
					WHERE disabled = 0");
			}
			else {
				$servers = db_get_all_rows_sql ("SELECT *
					FROM tmetaconsole_setup
					WHERE id = " . $id_server . "
						AND disabled = 0");
			}
			
			foreach ($servers as $server) {
				if (metaconsole_load_external_db ($server) != NOERR) {
					continue;
				}
				
				$return = agents_get_group_agents($id_group);
				
				//Restore db connection
				metaconsole_restore_db();
			}
		}
		else {
			$return = agents_get_group_agents($id_group);
		}
	}
	
	switch ($mode) {
		case 'json':
			echo json_encode($return);
			break;
	}
	
	return;
}

if ($search_agents && ((!defined('METACONSOLE')) || $force_local)) {
	require_once ('include/functions_agents.php');
	
	$id_agent = (int) get_parameter ('id_agent');
	$string = (string) get_parameter ('q'); /* q is what autocomplete plugin gives */
	$id_group = (int) get_parameter('id_group', -1);
	$addedItems = html_entity_decode((string) get_parameter('add'));
	$addedItems = json_decode($addedItems);
	$all = (string)get_parameter('all', 'all');
	
	if ($addedItems != null) {
		foreach ($addedItems as $item) {
			echo $item . "|\n";
		}
	}
	
	$filter = array ();
	
	if ($id_group != -1) {
		if($id_group == 0) {
			$user_groups = users_get_groups ($config['id_user'], "AR", true);
			
			$filter['id_grupo'] = array_keys ($user_groups);
		}
		else {
			$filter['id_grupo'] = $id_group;
		}
	}
	
	switch ($all) {
		case 'enabled':
			$filter['disabled'] = 0;
			break;
	}
	
	$data = array();
	//Get agents for only the name.
	$filter_agents = $filter;
	switch ($config['dbtype']) {
		case "mysql":
			$filter_agents[] = '(nombre COLLATE utf8_general_ci LIKE "%'.$string.'%")';
			break;
		case "postgresql":
			$filter_agents[] = '(nombre LIKE \'%'.$string.'%\')';
			break;
		case "oracle":
			$filter_agents[] = '(UPPER(nombre) LIKE UPPER(\'%'.$string.'%\')';
			break;
	}
	$agents = agents_get_agents($filter_agents, array ('id_agente', 'nombre', 'direccion'));
	if ($agents !== false) {
		foreach ($agents as $agent) {
			$data[] = array('id' => $agent['id_agente'],
				'name' => io_safe_output($agent['nombre']),
				'ip' => io_safe_output($agent['direccion']),
				'filter' => 'agent');
		}
	}
	
	//Get agents for only the address
	$filter_address = $filter;
	switch ($config['dbtype']) {
		case "mysql":
			$filter_address[] = '(nombre COLLATE utf8_general_ci NOT LIKE "%'.$string.'%" AND direccion LIKE "%'.$string.'%")';
			break;
		case "postgresql":
			$filter_address[] = '(nombre NOT LIKE \'%'.$string.'%\' AND direccion LIKE \'%'.$string.'%\')';
			break;
		case "oracle":
			$filter_address[] = '(UPPER(nombre) NOT LIKE UPPER(\'%'.$string.'%\') AND UPPER(direccion) LIKE UPPER(\'%'.$string.'%\'))';
			break;
	}
	$agents = agents_get_agents($filter_address, array ('id_agente', 'nombre', 'direccion'));
	if ($agents !== false) {
		foreach ($agents as $agent) {
			$data[] = array('id' => $agent['id_agente'],
				'name' => io_safe_output($agent['nombre']),
				'ip' => io_safe_output($agent['direccion']),
				'filter' => 'address');
		}
	}
	
	//Get agents for only the description
	$filter_description = $filter;
	switch ($config['dbtype']) {
		case "mysql":
			$filter_description[] = '(nombre COLLATE utf8_general_ci NOT LIKE "%'.$string.'%" AND direccion NOT LIKE "%'.$string.'%" AND comentarios LIKE "%'.$string.'%")';
			break;
		case "postgresql":
			$filter_description[] = '(nombre NOT LIKE \'%'.$string.'%\' AND direccion NOT LIKE \'%'.$string.'%\' AND comentarios LIKE \'%'.$string.'%\')';
			break;
		case "oracle":
			$filter_description[] = '(UPPER(nombre) NOT LIKE UPPER(\'%'.$string.'%\') AND UPPER(direccion) NOT LIKE UPPER(\'%'.$string.'%\') AND UPPER(comentarios) LIKE UPPER(\'%'.$string.'%\'))';
			break;
	}
	$agents = agents_get_agents($filter_description, array ('id_agente', 'nombre', 'direccion'));
	if ($agents !== false) {
		foreach ($agents as $agent) {
			$data[] = array('id' => $agent['id_agente'],
				'name' => io_safe_output($agent['nombre']),
				'ip' => io_safe_output($agent['direccion']),
				'filter' => 'description');
		}
	}
	
	echo json_encode($data);
	
	return;
}
elseif ($search_agents && ($config['metaconsole'] == 1) && defined('METACONSOLE')) {
	$servers = db_get_all_rows_sql ("SELECT *
		FROM tmetaconsole_setup
		WHERE disabled = 0");
	if (!isset($servers)) {
		return;
	}
	
	$id_agent = (int) get_parameter ('id_agent');
	$string = (string) get_parameter ('q'); /* q is what autocomplete plugin gives */
	$id_group = (int) get_parameter('id_group', -1);
	$addedItems = html_entity_decode((string) get_parameter('add'));
	$addedItems = json_decode($addedItems);
	
	if ($addedItems != null) {
		foreach ($addedItems as $item) {
			echo $item . "|\n";
		}
	}
	
	$filter = array ();
	
	if ($id_group != -1) {
		if ($id_group == 0) {
			$user_groups = users_get_groups ($config['id_user'], "AR", true);
			
			$filter['id_grupo'] = array_keys ($user_groups);
		}
		else {
			$filter['id_grupo'] = $id_group;
		}
	}
	
	$data = array();
	foreach ($servers as $server) {
		if (metaconsole_load_external_db ($server) != NOERR) {
			continue;
		}
		
		//Get agents for only the name.
		$filter_agents = $filter;
		switch ($config['dbtype']) {
			case "mysql":
				$filter_agents[] = '(nombre COLLATE utf8_general_ci LIKE "%'.$string.'%")';
				break;
			case "postgresql":
				$filter_agents[] = '(nombre LIKE \'%'.$string.'%\')';
				break;
			case "oracle":
				$filter_agents[] = '(UPPER(nombre) LIKE UPPER(\'%'.$string.'%\')';
				break;
		}
		$agents = agents_get_agents($filter_agents, array ('id_agente', 'nombre', 'direccion'));
		if ($agents !== false) {
			foreach ($agents as $agent) {
				$data[] = array('id' => $agent['id_agente'],
					'name' => io_safe_output($agent['nombre']),
					'ip' => io_safe_output($agent['direccion']),
					'filter' => 'agent',
					'id_server' => $server['id']);
			}
		}
		
		//Get agents for only the address
		$filter_address = $filter;
		switch ($config['dbtype']) {
			case "mysql":
				$filter_address[] = '(nombre COLLATE utf8_general_ci NOT LIKE "%'.$string.'%" AND direccion LIKE "%'.$string.'%")';
				break;
			case "postgresql":
				$filter_address[] = '(nombre NOT LIKE \'%'.$string.'%\' AND direccion LIKE \'%'.$string.'%\')';
				break;
			case "oracle":
				$filter_address[] = '(UPPER(nombre) NOT LIKE UPPER(\'%'.$string.'%\') AND UPPER(direccion) LIKE UPPER(\'%'.$string.'%\'))';
				break;
		}
		$agents = agents_get_agents($filter_address, array ('id_agente', 'nombre', 'direccion'));
		if ($agents !== false) {
			foreach ($agents as $agent) {
				$data[] = array('id' => $agent['id_agente'],
					'name' => io_safe_output($agent['nombre']),
					'ip' => io_safe_output($agent['direccion']),
					'filter' => 'address',
					'id_server' => $server['id']);
			}
		}
		
		//Get agents for only the description
		$filter_description = $filter;
		switch ($config['dbtype']) {
			case "mysql":
				$filter_description[] = '(nombre COLLATE utf8_general_ci NOT LIKE "%'.$string.'%" AND direccion NOT LIKE "%'.$string.'%" AND comentarios LIKE "%'.$string.'%")';
				break;
			case "postgresql":
				$filter_description[] = '(nombre NOT LIKE \'%'.$string.'%\' AND direccion NOT LIKE \'%'.$string.'%\' AND comentarios LIKE \'%'.$string.'%\')';
				break;
			case "oracle":
				$filter_description[] = '(UPPER(nombre) NOT LIKE UPPER(\'%'.$string.'%\') AND UPPER(direccion) NOT LIKE UPPER(\'%'.$string.'%\') AND UPPER(comentarios) LIKE UPPER(\'%'.$string.'%\'))';
				break;
		}
		$agents = agents_get_agents($filter_description,
			array ('id_agente', 'nombre', 'direccion'));
		if ($agents !== false) {
			foreach ($agents as $agent) {
				$data[] = array('id' => $agent['id_agente'],
					'name' => io_safe_output($agent['nombre']),
					'ip' => io_safe_output($agent['direccion']),
					'filter' => 'description',
					'id_server' => $server['id']);
			}
		}
		//Restore db connection
		metaconsole_restore_db();
	
	}
	
	echo json_encode($data);
	return;
}
?>