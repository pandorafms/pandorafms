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

include_once($config['homedir'] . "/include/functions_agents.php");

$search_modules = get_parameter('search_modules');

if ($search_modules) {
	$id_agents = json_decode(io_safe_output(get_parameter('id_agents')));
	$filter = get_parameter('q', '') . '%';
	$other_filter = json_decode(io_safe_output(get_parameter('other_filter')), true);
	
	$modules = agents_get_modules($id_agents, false,
		(array('nombre' => $filter) + $other_filter));
	
	if ($modules === false) $modules = array();
	
	$modules = array_unique($modules);
	
	$modules = io_safe_output($modules);
	
	echo json_encode($modules);
}

$get_plugin_macros = get_parameter('get_plugin_macros');
if ($get_plugin_macros) {
	$plugin_macros = db_get_value('macros','tplugin','id',get_parameter('id_plugin',0));
	
	$macros = array();
	
	$macros['base64'] = base64_encode($plugin_macros);
	$macros['array'] = json_decode($plugin_macros,true);
	
	echo json_encode($macros);
}

?>
