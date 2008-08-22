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
echo "<table border=0>";
echo "<tr><td>";
echo '<h3>'.__('Modules per agent').'</h3>';
echo "<img src='reporting/fgraph.php?tipo=db_agente_modulo&width=600&height=200'><br>";
echo "<tr><td><br>";
echo "<tr><td>";
echo '<h3>'.__('Packets per agent').'</h3>';
echo "<img src='reporting/fgraph.php?tipo=db_agente_paquetes&width=600&height=200'><br>";
echo "<br><br><a href='index.php?sec=gdbman&sec2=godmode/db/db_info_data'>".__('Press here to get DB Info as text')."</a>";
echo "</table>";
?>
