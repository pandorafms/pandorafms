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
global $config;

check_login();

if (! check_acl ($config['id_user'], 0, "DM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Database Management Info");
	require ("general/noaccess.php");
	return;
}

require_once ($config['homedir'] . '/include/functions_graph.php');

ui_print_page_header (__('Database maintenance').' &raquo; '.__('Database information'), "images/gm_db.png", false, "", true);

echo '<h4>'.__('Module data received').'</h4>';
echo grafico_db_agentes_purge(0, 600, 400);
?>