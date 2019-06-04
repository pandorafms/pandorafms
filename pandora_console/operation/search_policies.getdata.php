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

enterprise_include_once('include/functions_policies.php');


$searchpolicies = check_acl($config['id'], 0, 'UM');

$selectpolicieIDUp = '';
$selectpolicieIDDown = '';
$selectNameUp = '';
$selectNameDown = '';
$selectDescriptionUp = '';
$selectDescriptionDown = '';
$selectId_groupUp = '';
$selectId_groupDown = '';
$selectStatusUp = '';
$selectStatusDown = '';

switch ($sortField) {
    case 'id':
        switch ($sort) {
            case 'up':
                $selectpolicieIDUp = $selected;
                $order = [
                    'field' => 'id',
                    'order' => 'ASC',
                ];
            break;

            case 'down':
                $selectpolicieIDDown = $selected;
                $order = [
                    'field' => 'id',
                    'order' => 'DESC',
                ];
            break;
        }
    break;

    case 'name':
        switch ($sort) {
            case 'up':
                $selectNameUp = $selected;
                $order = [
                    'field' => 'name',
                    'order' => 'ASC',
                ];
            break;

            case 'down':
                $selectNameDown = $selected;
                $order = [
                    'field' => 'name',
                    'order' => 'DESC',
                ];
            break;
        }
    break;

    case 'description':
        switch ($sort) {
            case 'up':
                $selectId_groupUp = $selected;
                $order = [
                    'field' => 'description',
                    'order' => 'ASC',
                ];
            break;

            case 'down':
                $selectDescriptionDown = $selected;
                $order = [
                    'field' => 'description',
                    'order' => 'DESC',
                ];
            break;
        }
    break;

    case 'last_contact':
        switch ($sort) {
            case 'up':
                $selectId_groupUp = $selected;
                $order = [
                    'field' => 'last_connect',
                    'order' => 'ASC',
                ];
            break;

            case 'down':
                $selectId_groupDown = $selected;
                $order = [
                    'field' => 'last_connect',
                    'order' => 'DESC',
                ];
            break;
        }
    break;

    case 'id_group':
        switch ($sort) {
            case 'up':
                $selectId_groupUp = $selected;
                $order = [
                    'field' => 'last_connect',
                    'order' => 'ASC',
                ];
            break;

            case 'down':
                $selectId_groupDown = $selected;
                $order = [
                    'field' => 'last_connect',
                    'order' => 'DESC',
                ];
            break;
        }
    break;

    case 'status':
        switch ($sort) {
            case 'up':
                $selectStatusUp = $selected;
                $order = [
                    'field' => 'is_admin',
                    'order' => 'ASC',
                ];
            break;

            case 'down':
                $selectStatusDown = $selected;
                $order = [
                    'field' => 'is_admin',
                    'order' => 'DESC',
                ];
            break;
        }
    break;

    default:
        $selectpolicieIDUp = $selected;
        $selectpolicieIDDown = '';
        $selectNameUp = '';
        $selectNameDown = '';
        $selectDescriptionUp = '';
        $selectDescriptionDown = '';
        $selectId_groupUp = '';
        $selectId_groupDown = '';
        $selectStatusUp = '';
        $selectStatusDown = '';

        $order = [
            'field' => 'id',
            'order' => 'ASC',
        ];
    break;
}

if ($searchpolicies == 0) {
            $sql = "SELECT id, name, description, id_group, status FROM tpolicies
				    WHERE   name LIKE '%".$stringSearchSQL."%' OR
					        description LIKE '%".$stringSearchSQL."%'
				    ORDER BY ".$order['field'].' '.$order['order'];
}


        $sql .= ' LIMIT '.$config['block_size'].' OFFSET '.get_parameter('offset', 0);


    $policies = db_process_sql($sql);

if ($policies !== false) {
    if ($only_count) {
        unset($policies);
    }

    $sql = "SELECT COUNT(id) AS count FROM tpolicies
				WHERE name LIKE '%".$stringSearchSQL."%' OR
					  description LIKE '%".$stringSearchSQL."%'";


    $totalPolicies = db_get_value_sql($sql);
} else {
    $totalPolicies = 0;
}
