<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.


/**
 * Review if sample agent is active and deploys configuration for
 * visual consoles if necessary
 *
 * @return void
 */
function sample_agent_deployment()
{
    global $config;
    // Deployment of sample agent for visual consoles.
    if ($config['sample_agent'] == 1 && !isset($config['sample_agent_deployed'])) {
        $id_agente = db_get_value_filter('tagente', 'nombre', 'Sample_Agent');
        $modules = db_get_all_rows_filter('tagente_modulo', ['id_agente' => '$id_agente'], 'id_agente_modulo');
        $count_modules = count($modules);

        // Update of layout 1 (Rack sample).
        $images_rack_server = [
            'rack_server_rack',
            'rack_server',
            'rack_switch',
            'rack_firewall',
            'rack_double_server',
            'rack_frame',
            'rack_pdu',
        ];
        $query = 'UPDATE `tlayout_data` SET `id_agent` = '.$id_agente.', `id_agente_modulo` = CASE `image` ';
        for ($i = 0; $i < $count_modules; $i++) {
            $query .= 'WHEN "'.$images_rack_server[$i].'" THEN '.$modules[$i].' ';
        }

        $query .= 'END ';
        $query .= 'WHERE `id_layout` = 1 AND `image` IN ("'.implode('","', $images_rack_server).'");';
        db_process_sql($query);
        // Update of layout 2 (Dashboard).
        $query = 'UPDATE `tlayout_data` SET `id_agent`= '.$id_agente.', CASE ';
        $query .= 'WHEN `id` = 99 THEN '.$modules[0].' ';
        $query .= 'WHEN `id` = 100 THEN '.$modules[1].' ';
        $query .= 'WHEN `id` = 101 THEN '.$modules[2].' ';
        $query .= 'WHEN `id` = 102 THEN '.$modules[3].' ';
        $query .= 'WHEN `id` = 103 THEN '.$modules[4].' ';
        $query .= 'WHEN `id` = 112 THEN '.$modules[5].' ';
        $query .= 'WHEN `id` = 113 THEN '.$modules[6].' ';
        $query .= 'WHEN `id` = 114 THEN '.$modules[7].' ';
        $query .= 'END ';
        $query .= 'WHERE `id_layout` = 2 AND `id` IN (99,100,101,102,103,112,113,114);';
        db_process_sql($query);

        // This setting will avoid regenerate all the times the visual consoles.
        $values = [
            'token' => 'sample_agent_deployed',
            'value' => '1',
        ];
        db_process_sql_insert('tconfig', $values);
    }
}


extensions_add_main_function('sample_agent_deployment');
