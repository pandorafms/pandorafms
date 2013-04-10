<?php


// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


// Load global vars
global $config;

require_once ($config['homedir'] . '/include/functions_graph.php');
require_once($config['homedir'] . '/include/functions_agents.php');
require_once($config['homedir'] . '/include/functions_modules.php');

ui_print_page_header (__('Database maintenance').' &raquo; '.__('Database information'), "images/gm_db.png", false, "", true);

check_login ();
	
if (! check_acl ($config['id_user'], 0, "DM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Database Management Info");
	require ("general/noaccess.php");
	return;
}
// Todo for a good DB maintenance 
/* 
	- Delete too on datos_string and and datos_inc tables 
	
	- A function to "compress" data, and interpolate big chunks of data (1 month - 60000 registers) 
 	  onto a small chunk of interpolated data (1 month - 600 registers)
 	
	- A more powerful selection (by Agent, by Module, etc).
 */

echo grafico_db_agentes_purge(0, 600, 400);

echo '<div id="db_info_graph">';
echo '<table border=0 width="98%">';
echo '<tr><td>';
echo '<h4>'.__('Modules per agent').'</h4>';

echo graph_db_agentes_modulos(600, 250);

echo '</td></tr><tr><td><br /></tr></td>';
echo '<tr><td>';
echo '<h4>'.__('Packets per agent').'</h4>';

echo grafico_db_agentes_paquetes(700, 300);

echo '</table>';
echo '<a href="#" onClick="toggleDiv(\'db_info_data\'); toggleDiv(\'db_info_graph\'); return false;"><b>'.__('Press here to get database information as text').'</b>&nbsp;' . html_print_image("images/down.png", true, array("alt" => "down", "title" => "down")) . '</a></div>';
echo '<div id="db_info_data" style="display:none">';

//Merged from db_info_data.php because the queries are the same, so the cache
//will kick in.

$table->data = array ();
$table->head = array ();
$table->head[0] = __('Agent name');
$table->head[1] = __('Assigned modules');
$table->head[2] = __('Total data');

$agents = agents_get_group_agents (0);

$count = agents_get_modules_data_count (array_keys ($agents));

unset ($count["total"]); //Not interested in total
asort ($count, SORT_NUMERIC);

foreach ($count as $agent_id => $value) {
	$data = array ();

	//First row is a link to the agent
	$data[0] = '<strong><a href="index.php?sec=gagente&sec2=operation/agentes/ver_agente&id_agente='.$agent_id.'">'.$agents[$agent_id].'</a></strong>';
	//Second row is a number of modules for the agent
	$data[1] = modules_get_agent_modules_count ($agent_id);
	//Then the number of data packets for the agent
	$data[2] = $value;
	
	array_unshift ($table->data, $data);
}
html_print_table ($table);

echo '<a href="#" onClick="toggleDiv(\'db_info_graph\'); toggleDiv(\'db_info_data\'); return false;">'.__('Press here to get database information as a graph').'</a></div>';
?>
