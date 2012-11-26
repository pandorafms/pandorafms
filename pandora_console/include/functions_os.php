<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Get critical agents by using the status code in modules.

function os_agents_critical ($id_os) {
	
	//TODO REVIEW ORACLE AND POSTGRES
	
		return db_get_sql ("SELECT COUNT(*) FROM tagente WHERE critical_count>0 AND id_os=$id_os");
}

// Get ok agents by using the status code in modules.

function os_agents_ok($id_os) {

		return db_get_sql ("SELECT COUNT(*) FROM tagente WHERE normal_count=total_count AND id_os=$id_os");
}

// Get warning agents by using the status code in modules.

function os_agents_warning ($id_os) {

	return db_get_sql ("SELECT COUNT(*) FROM tagente WHERE critical_count=0 AND warning_count>0 AND id_os=$id_os");
}

// Get unknown agents by using the status code in modules.

function os_agents_unknown ($id_os) {

	return db_get_sql ("SELECT COUNT(*) FROM tagente WHERE critical_count=0 AND warning_count=0 AND unknown_count>0 AND id_os=$id_os");
}

// Get the name of a group given its id.
function os_get_name ($id_os) {
	return db_get_value ('name', 'tconfig_os', 'id_os', (int) $id_os);
}

?>
