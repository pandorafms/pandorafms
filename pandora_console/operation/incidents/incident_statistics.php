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

if (! give_acl ($config['id_user'], 0, "IR") == 1) {
	audit_db ($config['id_user'], $config["remote_addr"], "ACL Violation", "Trying to access Incident section");
	require ("general/noaccess.php");
	exit;
}
echo "<h2>".__('Incident management')." &raquo; ".__('Statistics')."</h2>";

echo '<table width="90%">
	<tr><td valign="top"><h3>'.__('Incidents by status').'</h3>';
if ($config['flash_charts']) {
	echo graph_incidents_status ();
} else {
	echo '<img src="include/fgraph.php?tipo=estado_incidente" border="0"></td>';
}
echo '<td valign="top"><h3>'.__('Incidents by priority').'</h3>';
if ($config['flash_charts']) {
	echo grafico_incidente_prioridad ();
} else {
	echo '<img src="include/fgraph.php?tipo=prioridad_incidente" border="0"></td></tr>';
}
echo '<tr><td><h3>'.__('Incidents by group').'</h3>';
if ($config['flash_charts']) {
	echo grafico_incidente_prioridad ();
} else {
	echo '<img src="include/fgraph.php?tipo=group_incident" border="0"></td>';
}
echo '<td><h3>'.__('Incidents by user').'</h3>';
if ($config['flash_charts']) {
	echo grafico_incidente_prioridad ();
} else {
	echo '<img src="include/fgraph.php?tipo=user_incident" border="0"></td></tr>';
}
echo '<tr><td><h3>'.__('Incidents by source').'</h3>';
if ($config['flash_charts']) {
	echo grafico_incidente_prioridad ();
} else {
	echo '<img src="include/fgraph.php?tipo=source_incident" border="0"></td></tr>';
}
echo '</table>';
?>
