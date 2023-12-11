<?php

// Pandora FMS - https://pandorafms.com
// ==================================================
// Copyright (c) 2005-2023 Pandora FMS
// Please see https://pandorafms.com/community/ for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


/**
 * Return array with macros of agent like core.pm
 *
 * @param interger $id_agente Id agent to return data.
 *
 * @return array Array with all macros.
 */
function return_agent_macros($id_agente)
{
    global $config;
    $array_macros = [];
    $grupo = [];
    $agente = db_get_row_sql(
        'SELECT * FROM tagente WHERE id_agente = '.$id_agente
    );
    if (isset($agente['id_grupo'])) {
        $grupo = db_get_row_sql(
            'SELECT * FROM tgrupo WHERE id_grupo = '.$agente['id_grupo']
        );
    }

    if (isset($agente['server_name'])) {
        $server_ip = db_get_row_sql(
            'SELECT ip_address FROM tserver WHERE name = "'.$agente['server_name'].'"'
        )['id_address'];
    }

    $array_macros = [
        '_agentname_'        => ($agente['nombre']) ?: '',
        '_agentalias_'       => ($agente['alias']) ?: '',
        '_agent_'            => ($agente['alias']) ?: (($agente['nombre']) ?: ''),
        '_agentcustomid_'    => ($agente['custom_id']) ?: '',
        '_agentdescription_' => ($agente['comentarios']) ?: '',
        '_agentgroup_'       => ($grupo['nombre']) ?: '',
        '_agentos_'          => ($agente['id_os']) ?: '',
        '_address_'          => ($agente['direccion']) ?: '',
        '_homeurl_'          => ($config['public_url']) ?: '',
        '_groupcontact_'     => ($agente['contact']) ?: '',
        '_groupcustomid_'    => ($agente['custom_id']) ?: '',
        '_groupother_'       => ($agente['other']) ?: '',
        '_server_ip_'        => ($server_ip) ?: '',
        '_server_name_'      => ($agente['server_name']) ?: '',
    ];

    return $array_macros;
}
