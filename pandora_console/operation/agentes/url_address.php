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

check_login ();

if (! check_acl ($config["id_user"], $id_grupo, "AW", $id_agente)) {
	db_pandora_audit("ACL Violation",
		"Trying to access agent manager");
	require ("general/noaccess.php");
	return;
}

echo "<iframe src=\"" . $agent['url_address'] . "\" width='100%' height=550>";
echo "</iframe>";

?>
