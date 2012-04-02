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

if (! check_acl ($config['id_user'], 0, "IR") == 1) {
	db_pandora_audit("ACL Violation", "Trying to access Incident section");
	require ("general/noaccess.php");
	exit;
}
include_flash_chart_script();

ui_print_page_header (__('Incidents')." &raquo; ".__('Statistics'), "images/book_edit.png", false, "", false, "");

echo '<table width="90%">
	<tr><td valign="top"><h3>'.__('Incidents by status').'</h3>';
echo graph_incidents_status ();

echo '<td valign="top"><h3>'.__('Incidents by priority').'</h3>';
echo grafico_incidente_prioridad ();

echo '<tr><td><h3>'.__('Incidents by group').'</h3>';
echo graphic_incident_group();

echo '<td><h3>'.__('Incidents by user').'</h3>';
echo graphic_incident_user();

echo '<tr><td><h3>'.__('Incidents by source').'</h3>';
echo graphic_incident_source();

echo '</table>';
?>
