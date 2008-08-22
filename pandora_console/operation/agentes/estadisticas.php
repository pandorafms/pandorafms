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
require("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "AR")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Agent estatistics");
	require ("general/noaccess.php");
	return;
}
echo "<h2>".__('Pandora Agents')." &gt; ";
echo __('Database Statistics per Agent')."</h2>";
echo "<table border=0>";
echo "<tr><td><img src='reporting/fgraph.php?tipo=db_agente_modulo'><br>";
echo "<tr><td><br>";
echo "<tr><td><img src='reporting/fgraph.php?tipo=db_agente_paquetes'><br>";
echo "</table>";
?>
