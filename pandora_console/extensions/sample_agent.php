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
require_once __DIR__.'/../include/config.php';
require_once __DIR__.'/../include/auth/mysql.php';
require_once __DIR__.'/../include/functions.php';
require_once __DIR__.'/../include/functions_db.php';
/*
 * Review if sample agent is active and deploys configuration for
 * visual consoles if necessary
 */
global $config;

// Deployment of sample agent for visual consoles.
if ($config['sample_agent'] == 1 && !isset($config['sample_agent_deployed'])) {
    $id_agente = db_get_sql('SELECT id_agente FROM tagente WHERE nombre = "Sample_Agent";');
    $modules = db_get_all_rows_filter('tagente_modulo', ['id_agente' => $id_agente], 'id_agente_modulo');
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
    $query = 'UPDATE `tlayout_data` SET `id_agent` = '.$id_agente.', `id_agente_modulo` = CASE ';
    for ($i = 0; $i < $count_modules; $i++) {
        $query .= 'WHEN `image` = "'.$images_rack_server[$i].'" THEN '.$modules[$i]['id_agente_modulo'].' ';
    }

    $query .= 'END WHERE `id_layout` = 1 AND `image` IN ("'.implode('","', $images_rack_server).'");';

    db_process_sql($query);
    // Update of layout 2 (Dashboard).
    $query = 'UPDATE `tlayout_data` SET `id_agent`= '.$id_agente.', `id_agente_modulo` = CASE ';
    $query .= 'WHEN `id` = 107 THEN '.$modules[0]['id_agente_modulo'].' ';
    $query .= 'WHEN `id` = 108 THEN '.$modules[1]['id_agente_modulo'].' ';
    $query .= 'WHEN `id` = 109 THEN '.$modules[2]['id_agente_modulo'].' ';
    $query .= 'WHEN `id` = 110 THEN '.$modules[2]['id_agente_modulo'].' ';
    $query .= 'WHEN `id` = 111 THEN '.$modules[3]['id_agente_modulo'].' ';
    $query .= 'WHEN `id` = 112 THEN '.$modules[4]['id_agente_modulo'].' ';
    $query .= 'WHEN `id` = 113 THEN '.$modules[5]['id_agente_modulo'].' ';
    $query .= 'WHEN `id` = 114 THEN '.$modules[6]['id_agente_modulo'].' ';
    $query .= 'END WHERE `id_layout` = 2 AND `id` IN (107,108,109,110,111,112,113,114);';

    db_process_sql($query);

    // This setting will avoid regenerate all the times the visual consoles.
    config_update_value('sample_agent_deployed', 1);
}

extensions_add_main_function('sample_agent_deployment');
