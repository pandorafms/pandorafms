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

echo "<h4 style='margin-top:0px !important;'>".__('Latest events for this agent')."</h4>";

$tags_condition = tags_get_acl_tags($config['id_user'], $agent['id_grupo'], 'ER', 'event_condition', 'AND');

events_print_event_table ("estado <> 1 $tags_condition", 10, '100%', false, $id_agente);

?>
