<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package    Include
 * @subpackage ExportServer
 */


/**
 * Gets all export servers out of the database
 *
 * @param (bool) $active Whether or not to exclude inactive servers (defaults to 1 => no inactive servers)
 *
 * @return (array) An array of server information (similar to server_info) but without the other servers
 **/
function exportserver_get_exportservers($active=1)
{
    $query = 'SELECT * FROM tserver WHERE export_server = 1';
    $return = [];

    if ($active == 1) {
        $servers = db_get_all_rows_sql($query.' AND status = 1');
    } else {
        $servers = db_get_all_rows_sql($query);
    }

    if (empty($servers)) {
        return $return;
    }

    foreach ($servers as $server) {
        $return[$server['id_server']] = $server;
    }

    return $return;
}


/**
 * Gets a specific piece of info on the export servers table (defaults to name)
 *
 * @param (bool)   $active (bool) Whether or not to exclude inactive servers (defaults to 1 => no inactive servers)
 * @param (string) $row    What row to select from the server info table
 *
 * @return (array) An array of server information (similar to exportserver_get_exportservers) but without the extra data
 **/
function exportserver_get_info($active=1, $row='name')
{
    $exportservers = exportserver_get_exportservers();
    $return = [];

    foreach ($exportservers as $server_id => $server_info) {
        $return[$server_id] = $server_info[$row];
    }

    return $return;
}


/**
 * Get the name of an exporting server
 *
 * @param integer $id_server Server id
 *
 * @return string The name of given server.
 */
function exportserver_get_name($id_server)
{
    return (string) db_get_value('name', 'tserver_export', 'id', (int) $id_server);
}
