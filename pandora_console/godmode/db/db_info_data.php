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

// Load global vars
require_once ("include/config.php");
check_login ();

if (! give_acl ($config['id_user'], 0, "DM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Database Management Info data");
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
echo  __('Database Statistics per Agent')."</h2>";

$table->data = array ();
$table->head = array ();
$table->head[0] = __('Agent name');
$table->head[1] = __('Assigned module');
$table->head[2] = __('Total data');

$sql = "SELECT `id_agente`, `nombre` FROM `tagente`";
$result = get_db_all_rows_sql ($sql);
foreach ($result as $agent) {
	$data = array ();
	
	$sql = sprintf("SELECT COUNT(`id_agente_modulo`)
			FROM `tagente_modulo` WHERE `id_agente` = '%d'",
			$agent["id_agente"]);
	$assigned = get_db_sql ($sql);
	
	// for all data_modules belongs to an agent -- simplified, made
	// faster
	$sql = sprintf ("SELECT COUNT(`id_agente_datos`)
			FROM `tagente_datos` WHERE `id_agente` = '%d'",
			$agent["id_agente"]);
	$total_agente = get_db_sql ($sql);
	
	$data[0] = '<strong><a href="index.php?sec=gagente&sec2=operation/agentes/ver_agente&id_agente='.
		$agent["id_agente"].'">'.$agent["nombre"].'</a></strong>';
	$data[1] = $assigned;
	$data[2] = $total_agente;
	
	array_push ($table->data, $data);
}
print_table ($table);
?>
