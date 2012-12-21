<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
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

require_once ($config["homedir"] . '/include/functions_graph.php');

check_login ();

if (! check_acl ($config['id_user'], 0, "AR")) {
	db_pandora_audit("ACL Violation","Trying to access event viewer");
	require ("general/noaccess.php");
	return;
}
//header
ui_print_page_header (__('Statistics'), "images/lightning_go.png",false, false);
echo "<table width=95%>";
echo "<tr>";

echo "<td valign='top'>";
echo "<h3>" . __('Event graph') . "</h3>";
echo grafico_eventos_total();
echo "</td>";

echo "<td valign='top'>";
echo "<h3>" . __('Event graph by user') . "</h3>";
echo grafico_eventos_usuario(300, 200);
echo "</td>";

echo "</tr>";

echo "<tr>";

echo "<td>";
echo "<h3>" . __('Event graph by group') . "</h3>";
echo grafico_eventos_grupo(300, 200);
echo "</td>";

echo "<td>";
echo "<h3>" . __('Amount events validated') . "</h3>";
echo graph_events_validated(300, 200);
echo "</td>";

echo "</tr>";
echo "</table>";
?>
