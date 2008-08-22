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

if (! give_acl ($config['id_user'], 0, "IR")==1) {
	require ("general/noaccess.php");
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation", "Trying to access Incident section");
	return;
}
echo "<h2>".__('Incident management')." &gt; ";
echo __('Statistics')."</h2>";

echo "<table width = 90%>";
echo "<tr><td valign='top'>";
echo '<h3>'.__('Incidents by status').'</h3>';
echo '<img src="reporting/fgraph.php?tipo=estado_incidente" border=0>';
echo "<td valign='top'>";
echo '<h3>'.__('Incidents by priority').'</h3>';
echo '<img src="reporting/fgraph.php?tipo=prioridad_incidente" border=0>';
echo "<tr><td>";
echo '<h3>'.__('Incidents by group').'</h3>';
echo '<img src="reporting/fgraph.php?tipo=group_incident" border=0>';
echo "<td>";
echo '<h3>'.__('Incidents by user').'</h3>';
echo '<img src="reporting/fgraph.php?tipo=user_incident" border=0>';
echo "<tr><td>";
echo '<h3>'.__('Incidents by source').'</h3>';
echo '<img src="reporting/fgraph.php?tipo=source_incident" border=0>';
echo "<td>";
echo "</table>";
?>
