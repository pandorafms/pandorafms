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
require_once ("include/config.php");
require_once ("include/fgraph.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "AR")) {
	audit_db ($config['id_user'],$REMOTE_ADDR, "ACL Violation","Trying to access event viewer");
	require ("general/noaccess.php");
	return;
}
echo "<table width=95%>";
echo "<tr><td valign='top'>";
echo "<h3>".__('Event graph')."</h3>";
if ($config['flash_charts']) {
	echo grafico_eventos_total ();
} else {
	echo '<img src="include/fgraph.php?tipo=total_events&width=300&height=200" border=0>';
}
echo "</td><td valign='top'>";
echo "<h3>".__('Event graph by user')."</h3>";
if ($config['flash_charts']) {
	echo grafico_eventos_usuario (300, 200);
} else {
	echo '<img src="include/fgraph.php?tipo=user_events&width=300&height=200" border=0>';
}
echo "</td></tr>";
echo "<tr><td>";
echo "<h3>".__('Event graph by group')."</h3>";
if ($config['flash_charts']) {
	echo grafico_eventos_grupo (300, 200);
} else {
	echo '<img src="include/fgraph.php?tipo=group_events&width=300&height=200" border=0>';
}
echo '</td></tr>';
echo "</table>";
?>
