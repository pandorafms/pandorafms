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
function os_agents_critical($id_os)
{
    // TODO REVIEW ORACLE AND POSTGRES
    return db_get_sql(
        "
		SELECT COUNT(*)
		FROM tagente
		WHERE tagente.disabled=0 AND
			critical_count>0 AND id_os=$id_os"
    );
}


// Get ok agents by using the status code in modules.
function os_agents_ok($id_os)
{
    return db_get_sql(
        "
		SELECT COUNT(*)
		FROM tagente
		WHERE tagente.disabled=0 AND
			normal_count=total_count AND id_os=$id_os"
    );
}


// Get warning agents by using the status code in modules.
function os_agents_warning($id_os)
{
    return db_get_sql(
        "
		SELECT COUNT(*)
		FROM tagente
		WHERE tagente.disabled=0 AND
			critical_count=0 AND warning_count>0 AND id_os=$id_os"
    );
}


// Get unknown agents by using the status code in modules.
function os_agents_unknown($id_os)
{
    return db_get_sql(
        "
		SELECT COUNT(*)
		FROM tagente
		WHERE tagente.disabled=0 AND
			critical_count=0 AND warning_count=0 AND
			unknown_count>0 AND id_os=$id_os"
    );
}


// Get the name of a group given its id.
function os_get_name($id_os)
{
    return db_get_value('name', 'tconfig_os', 'id_os', (int) $id_os);
}


function os_get_os($hash=false)
{
    $result = [];
    $op_systems = db_get_all_rows_in_table('tconfig_os');
    if (empty($op_systems)) {
        $op_systems = [];
    }

    if ($hash) {
        foreach ($op_systems as $key => $value) {
            $result[$value['id_os']] = $value['name'];
        }
    } else {
        $result = $op_systems;
    }

    return $result;
}


function os_get_icon($id_os)
{
    return db_get_value('icon_name', 'tconfig_os', 'id_os', (int) $id_os);
}
