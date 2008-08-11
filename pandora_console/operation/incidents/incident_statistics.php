<?php

// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@openideas.info
// Copyright (c) 2005-2007 Artica Soluciones Tecnologicas
// Copyright (c) 2004-2007 Raul Mateos Martin, raulofpandora@gmail.com
// Copyright (c) 2006-2007 Jose Navarro jose@jnavarro.net
// Copyright (c) 2006-2007 Jonathan Barajas, jonathan.barajas[AT]gmail[DOT]com

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation version 2
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
echo "<h2>".__('incident_manag')." &gt; ";
echo __('statistics')."</h2>";

echo "<table width = 90%>";
echo "<tr><td valign='top'>";
echo '<h3>'.__('inc_stat_status').'</h3>';
echo '<img src="reporting/fgraph.php?tipo=estado_incidente" border=0>';
echo "<td valign='top'>";
echo '<h3>'.__('inc_stat_priority').'</h3>';
echo '<img src="reporting/fgraph.php?tipo=prioridad_incidente" border=0>';
echo "<tr><td>";
echo '<h3>'.__('inc_stat_group').'</h3>';
echo '<img src="reporting/fgraph.php?tipo=group_incident" border=0>';
echo "<td>";
echo '<h3>'.__('inc_stat_user').'</h3>';
echo '<img src="reporting/fgraph.php?tipo=user_incident" border=0>';
echo "<tr><td>";
echo '<h3>'.__('inc_stat_source').'</h3>';
echo '<img src="reporting/fgraph.php?tipo=source_incident" border=0>';
echo "<td>";
echo "</table>";
?>
