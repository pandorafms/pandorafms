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
check_login();

if (!isset($id_agente)){
    require ("general/noaccess.php");
    exit;
}

require_once ("include/functions_events.php");

echo "<h3>".__('Latest events for this agent')."</h3>";
print_events_table ("WHERE id_agente = $id_agente", $limit = 10, $width=750);

?>
