<?php

// Pandora FMS - https://pandorafms.com
// ==================================================
// Copyright (c) 2005-2023 Pandora FMS
// Please see https://pandorafms.com/community/ for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
global $config;

require_once 'include/functions_reports.php';

$linkReport = false;
$searchReports = check_acl($config['id_user'], 0, 'RR');

if (check_acl($config['id_user'], 0, 'RW')) {
    $linkReport = true;
}

$reports = false;

// Check ACL
$userreports = reports_get_reports();

$userreports_id = [];
foreach ($userreports as $userreport) {
    $userreports_id[] = $userreport['id_report'];
}

if (!$userreports_id) {
    $reports_condition = ' AND 1<>1';
} else {
    $reports_condition = ' AND id_report IN ('.implode(',', $userreports_id).')';
}

$reports = false;

if ($searchReports) {
    switch ($config['dbtype']) {
        case 'mysql':
        case 'postgresql':
            $sql = "SELECT id_report, name, description
				FROM treport
				WHERE (REPLACE(name, '&#x20;', ' ') LIKE '%".$stringSearchSQL."%' OR REPLACE(description, '&#x20;', ' ')  LIKE '%".$stringSearchSQL."%')".$reports_condition;
        break;

        case 'oracle':
            $sql = "SELECT id_report, name, description
				FROM treport
				WHERE (upper(REPLACE(name, '&#x20;', ' ') ) LIKE '%".strtolower($stringSearchSQL)."%' OR REPLACE(description, '&#x20;', ' ')  LIKE '%".strtolower($stringSearchSQL)."%')".$reports_condition;
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

    switch ($config['dbtype']) {
        case 'mysql':
        case 'postgresql':
            $sql_count = "SELECT COUNT(id_report) AS count
			FROM treport
			WHERE (REPLACE(name, '&#x20;', ' ')  LIKE '%".$stringSearchSQL."%' OR REPLACE(description, '&#x20;', ' ')  LIKE '%".$stringSearchSQL."%')".$reports_condition;
        break;

        case 'oracle':
            $sql_count = "SELECT COUNT(id_report) AS count
			FROM treport
			WHERE (upper(REPLACE(name, '&#x20;', ' ') ) LIKE '%".strtolower($stringSearchSQL)."%' OR upper(REPLACE(description, '&#x20;', ' ') ) LIKE '%".strtolower($stringSearchSQL)."%')".$reports_condition;
        break;
    }

    if ($only_count) {
        $totalReports = db_get_value_sql($sql_count);
    } else {
        $reports = db_process_sql($sql);
        $totalReports = db_get_value_sql($sql_count);
    }
}
