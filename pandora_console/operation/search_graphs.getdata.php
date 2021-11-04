<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
global $config;

require_once 'include/functions_custom_graphs.php';

// Check ACL
$searchGraphs = check_acl($config['id_user'], 0, 'RR');

$graphs = false;

if ($searchGraphs) {
    // Check ACL
    $usergraphs = custom_graphs_get_user($config['id_user'], true);
    $usergraphs_id = array_keys($usergraphs);

    if (empty($usergraphs_id)) {
        $totalGraphs = 0;
        return;
    }

    $filter = [];
    $filter[] = "(upper(name) LIKE '%".strtolower($stringSearchSQL)."%' OR upper(description) LIKE '%$".strtolower($stringSearchSQL)."%')";
    $filter['id_graph'] = $usergraphs_id;

    $columns = [
        'id_graph',
        'name',
        'description',

    ];

    $totalGraphs = (int) db_get_value_filter('COUNT(id_graph) AS count', 'tgraph', $filter);

    if ($totalGraphs > 0) {
        $filter['limit'] = $config['block_size'];
        $filter['offset'] = (int) get_parameter('offset');
        $graphs = db_get_all_rows_filter('tgraph', $filter, $columns);
    } else {
        $totalGraphs = 0;
    }
}
