<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// Evi Vanoost <vanooste@rcbi.rochester.edu> 2008

// Load global vars
require_once ("include/config.php");

check_login ();
	
if (! give_acl ($config['id_user'], 0, "DM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
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

echo "<h2>".__('Database Maintenance')." &gt; ";
echo __('Database Information')."</h2>";
echo '<div id="db_info_graph">';
echo '<table border=0>';
echo '<tr><td>';
echo '<h3>'.__('Modules per agent').'</h3>';
echo '<img src="reporting/fgraph.php?tipo=db_agente_modulo&width=600&height=200"><br />';
echo '</td></tr><tr><td><br /></tr></td>';
echo '<tr><td>';
echo '<h3>'.__('Packets per agent').'</h3>';
echo '<img src="reporting/fgraph.php?tipo=db_agente_paquetes&width=600&height=200"><br />';
echo '</table>';
echo '<a href="#" onClick="toggleDiv(\'db_info_data\'); toggleDiv(\'db_info_graph\'); return false;">'.__('Press here to get database information as text').'</a></div>';
echo '<div id="db_info_data" style="display:none">';
//Merged from db_info_data.php because the queries are the same, so the cache
//will kick in.

$table->data = array ();
$table->head = array ();
$table->head[0] = __('Agent name');
$table->head[1] = __('Assigned modules');
$table->head[2] = __('Total data');

$sql = "SELECT DISTINCT(id_agente), COUNT(id_agente_datos) AS count FROM tagente_datos GROUP BY id_agente ORDER BY count ASC";

// Not implemented yet
//$sql2 = "SELECT DISTINCT(id_agente), COUNT(id_agente_datos_string) AS count FROM tagente_datos_string GROUP BY id_agente ORDER BY count ASC";

//This query takes 1s on a 1 million entry database. Merging it with tagente
//costs 7 seconds so we rely on the functions to return information on
//id_agente.
$result = get_db_all_rows_sql ($sql);
if ($result === false) {
	$result = array();
}

foreach ($result as $row) {
	$data = array ();

	//First row is a link to the agent
	$data[0] = '<strong><a href="index.php?sec=gagente&sec2=operation/agentes/ver_agente&id_agente='.
	$row["id_agente"].'">'.dame_nombre_agente ($row["id_agente"]).'</a></strong>';
	//Second row is a number of modules for the agent
	$data[1] = count (get_modules_in_agent ($row["id_agente"]));
	//Then the number of data packets for the agent
	$data[2] = $row["count"];

	array_unshift ($table->data, $data);
}
print_table ($table);

echo '<a href="#" onClick="toggleDiv(\'db_info_graph\'); toggleDiv(\'db_info_data\'); return false;">'.__('Press here to get database information as a graph').'</a></div>';
?>
