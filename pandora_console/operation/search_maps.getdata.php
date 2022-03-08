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

$searchMaps = check_acl($config['id_user'], 0, 'VR');

$maps = false;
$totalMaps = 0;

if ((bool) $searchMaps === true) {
    $user_groups = users_get_groups($config['id_user'], 'AR', true);
    $id_user_groups = array_keys($user_groups);
    $id_user_groups_str = implode(',', $id_user_groups);

    if (empty($id_user_groups) === true) {
        return;
    }

    $sql = sprintf(
        'SELECT tl.id, tl.name, tl.id_group, COUNT(tld.id_layout) AS count
         FROM tlayout tl
         LEFT JOIN tlayout_data tld
           ON tl.id = tld.id_layout
         WHERE tl.name LIKE "%%%s%%" 
           AND tl.id_group IN (%s)
           GROUP BY tl.id, tl.name, tl.id_group',
        $stringSearchSQL,
        $id_user_groups_str
    );


    $sql .= ' LIMIT '.$config['block_size'].' OFFSET '.get_parameter('offset', 0);

    $maps = db_process_sql($sql);

    if ($maps !== false) {
        $totalMaps = count($maps);

        if ($only_count) {
            unset($maps);
        }
    }
}
