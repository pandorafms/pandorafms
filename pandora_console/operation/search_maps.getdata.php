<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
global $config;

$searchMaps = check_acl($config['id_user'], 0, 'IR');

$maps = false;
$totalMaps = 0;

if ($searchMaps) {
    $user_groups = users_get_groups($config['id_user'], 'AR', false);
    $id_user_groups = array_keys($user_groups);
    $id_user_groups_str = implode(',', $id_user_groups);

    if (empty($id_user_groups)) {
        return;
    }

    switch ($config['dbtype']) {
        case 'mysql':
        case 'postgresql':
            $sql = "SELECT tl.id, tl.name, tl.id_group, COUNT(tld.id_layout) AS count
					FROM tlayout tl
					LEFT JOIN tlayout_data tld
						ON tl.id = tld.id_layout
					WHERE tl.name LIKE '%$stringSearchSQL%'
						AND tl.id_group IN ($id_user_groups_str)
					GROUP BY tl.id, tl.name, tl.id_group";
        break;

        case 'oracle':
            $sql = "SELECT tl.id, tl.name, tl.id_group, COUNT(tld.id_layout) AS count
					FROM tlayout tl
					LEFT JOIN tlayout_data tld
						ON tl.id = tld.id_layout
					WHERE upper(tl.name) LIKE '%".strtolower($stringSearchSQL)."%'
						AND tl.id_group IN ($id_user_groups_str)
					GROUP BY tl.id, tl.name, tl.id_group";
        break;
    }


    switch ($config['dbtype']) {
        case 'mysql':
        case 'postgresql':
            $sql .= ' LIMIT '.$config['block_size'].' OFFSET '.get_parameter('offset', 0);
        break;

        case 'oracle':
            $set = [];
            $set['limit'] = $config['block_size'];
            $set['offset'] = (int) get_parameter('offset');

            $sql = oracle_recode_query($sql, $set);
        break;
    }

    $maps = db_process_sql($sql);

    if ($maps !== false) {
        $totalMaps = count($maps);

        if ($only_count) {
            unset($maps);
        }
    }
}
