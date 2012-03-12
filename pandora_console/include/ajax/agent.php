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

if ($get_agents_group) {
	$id_group = (int)get_parameter('id_group', -1);
	$mode = (string)get_parameter('mode', 'json');
	
	$return = array();
	if ($id_group != -1) {
		$return = agents_get_group_agents($id_group);
	}
	
	switch ($mode) {
		case 'json':
			echo json_encode($return);
			break;
	}
	
	return;
}

if ($search_agents && ($config['metaconsole'] == 0)) {

	require_once ('include/functions_agents.php');

    $id_agent = (int) get_parameter ('id_agent');
    $string = (string) get_parameter ('q'); /* q is what autocomplete plugin gives */
    $id_group =  get_parameter('id_group', -1);
    $addedItems = html_entity_decode((string) get_parameter('add'));
    $addedItems = json_decode($addedItems);

    if ($addedItems != null) {
        foreach ($addedItems as $item) {
            echo $item . "|\n";
        }
    }

    $filter = array ();
	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":	
	    		$filter[] = '(nombre COLLATE utf8_general_ci LIKE "%'.$string.'%" OR direccion LIKE "%'.$string.'%" OR comentarios LIKE "%'.$string.'%")';
			break;
		case "oracle":
	    		$filter[] = '(UPPER(nombre)  LIKE UPPER(\'%'.$string.'%\') OR UPPER(direccion) LIKE UPPER(\'%'.$string.'%\') OR UPPER(comentarios) LIKE UPPER(\'%'.$string.'%\'))';
			break;
	}			
	
	if ($id_group != -1)	
		$filter['id_grupo'] = $id_group;

    $agents = agents_get_agents ($filter, array ('id_agente','nombre', 'direccion'));
    if ($agents === false)
        return;

    foreach ($agents as $agent) {
        echo io_safe_output($agent['nombre']) . "|" . io_safe_output($agent['id_agente']) . "|" . io_safe_output($agent['direccion']) . "\n";
    }

    return;
}
elseif ($search_agents && ($config['metaconsole'] == 1)) {
	$servers = db_get_all_rows_sql ("SELECT * FROM tmetaconsole_setup");
	if (!isset($servers)) {
		return;
	}
	
	foreach ($servers as $server) {
		if (!metaconsole_load_external_db ($server)) {
			continue;
		}
		
		$id_agent = (int) get_parameter ('id_agent');
		$string = (string) get_parameter ('q'); /* q is what autocomplete plugin gives */
		$id_group = (int) get_parameter('id_group');
		$addedItems = html_entity_decode((string) get_parameter('add'));
		$addedItems = json_decode($addedItems);

		if ($addedItems != null) {
			foreach ($addedItems as $item) {
				echo $item . "|\n";
			}
		}

		$filter = array ();
		switch ($config["dbtype"]) {
			case "mysql":
			case "postgresql":	
					$filter[] = '(nombre COLLATE utf8_general_ci LIKE "%'.$string.'%" OR direccion LIKE "%'.$string.'%" OR comentarios LIKE "%'.$string.'%")';
				break;
			case "oracle":
					$filter[] = '(UPPER(nombre)  LIKE UPPER(\'%'.$string.'%\') OR UPPER(direccion) LIKE UPPER(\'%'.$string.'%\') OR UPPER(comentarios) LIKE UPPER(\'%'.$string.'%\'))';
				break;
		}			
			
		$filter['id_grupo'] = $id_group;

		$agents = agents_get_agents ($filter, array ('id_agente','nombre', 'direccion'));
		if ($agents === false)
			return;
		foreach ($agents as $agent) {
			echo io_safe_output($agent['nombre']) . " (" . io_safe_output($server['server_name']) . ") " . "|" . io_safe_output($agent['id_agente']) . "|" . io_safe_output($server['server_name']) . "|" . io_safe_output($agent['direccion']) . "|". "\n";
		}
		//Restore db connection
		metaconsole_restore_db();
	}
	return;
}


?>
