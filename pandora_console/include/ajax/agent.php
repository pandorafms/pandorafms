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


// Get list of agent + ip
// Params:
// * search_agents 1
// * id_agent 
// * q
// * id_group
$search_agents = (bool) get_parameter ('search_agents');

if ($search_agents) {

	require_once ('include/functions_agents.php');

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
    $filter[] = '(nombre COLLATE utf8_general_ci LIKE "%'.$string.'%" OR direccion LIKE "%'.$string.'%" OR comentarios LIKE "%'.$string.'%")';
    $filter['id_grupo'] = $id_group;

    $agents = get_agents ($filter, array ('id_agente','nombre', 'direccion'));
    if ($agents === false)
        return;

    foreach ($agents as $agent) {
        echo $agent['nombre']."|".$agent['id_agente']."|".$agent['direccion']."\n";
    }

    return;
}


?>
