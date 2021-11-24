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

require_once $config['homedir'].'/include/functions_profile.php';
require_once $config['homedir'].'/include/functions_users.php';
require_once $config['homedir'].'/include/functions_groups.php';

$searchUsers = check_acl($config['id_user'], 0, 'UM');
if (!$searchUsers) {
    $totalUsers = 0;
    return;
}

$selectUserIDUp = '';
$selectUserIDDown = '';
$selectNameUp = '';
$selectNameDown = '';
$selectEmailUp = '';
$selectEmailDown = '';
$selectLastContactUp = '';
$selectLastContactDown = '';
$selectProfileUp = '';
$selectProfileDown = '';

switch ($sortField) {
    case 'id_user':
        switch ($sort) {
            case 'up':
                $selectUserIDUp = $selected;
                $order = [
                    'field' => 'id_user',
                    'order' => 'ASC',
                ];
            break;

            case 'down':
                $selectUserIDDown = $selected;
                $order = [
                    'field' => 'id_user',
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
                    'field' => 'fullname',
                    'order' => 'ASC',
                ];
            break;

            case 'down':
                $selectNameDown = $selected;
                $order = [
                    'field' => 'fullname',
                    'order' => 'DESC',
                ];
            break;
        }
    break;

    case 'email':
        switch ($sort) {
            case 'up':
                $selectLastContactUp = $selected;
                $order = [
                    'field' => 'email',
                    'order' => 'ASC',
                ];
            break;

            case 'down':
                $selectEmailDown = $selected;
                $order = [
                    'field' => 'email',
                    'order' => 'DESC',
                ];
            break;
        }
    break;

    case 'last_contact':
        switch ($sort) {
            case 'up':
                $selectLastContactUp = $selected;
                $order = [
                    'field' => 'last_connect',
                    'order' => 'ASC',
                ];
            break;

            case 'down':
                $selectLastContactDown = $selected;
                $order = [
                    'field' => 'last_connect',
                    'order' => 'DESC',
                ];
            break;
        }
    break;

    case 'last_contact':
        switch ($sort) {
            case 'up':
                $selectLastContactUp = $selected;
                $order = [
                    'field' => 'last_connect',
                    'order' => 'ASC',
                ];
            break;

            case 'down':
                $selectLastContactDown = $selected;
                $order = [
                    'field' => 'last_connect',
                    'order' => 'DESC',
                ];
            break;
        }
    break;

    case 'profile':
        switch ($sort) {
            case 'up':
                $selectProfileUp = $selected;
                $order = [
                    'field' => 'is_admin',
                    'order' => 'ASC',
                ];
            break;

            case 'down':
                $selectProfileDown = $selected;
                $order = [
                    'field' => 'is_admin',
                    'order' => 'DESC',
                ];
            break;
        }
    break;

    default:
        $selectUserIDUp = $selected;
        $selectUserIDDown = '';
        $selectNameUp = '';
        $selectNameDown = '';
        $selectEmailUp = '';
        $selectEmailDown = '';
        $selectLastContactUp = '';
        $selectLastContactDown = '';
        $selectProfileUp = '';
        $selectProfileDown = '';

        $order = [
            'field' => 'id_user',
            'order' => 'ASC',
        ];
    break;
}

if ($searchUsers) {
    switch ($config['dbtype']) {
        case 'mysql':
        case 'postgresql':
            $sql = "SELECT id_user, fullname, firstname, lastname, middlename, email, last_connect, is_admin, comments FROM tusuario
				WHERE fullname LIKE '%".$stringSearchSQL."%' OR
					id_user LIKE '%".$stringSearchSQL."%' OR
					firstname LIKE '%".$stringSearchSQL."%' OR
					lastname LIKE '%".$stringSearchSQL."%' OR
					middlename LIKE '%".$stringSearchSQL."%' OR
					email LIKE '%".$stringSearchSQL."%'
				ORDER BY ".$order['field'].' '.$order['order'];
        break;

        case 'oracle':
            $sql = "SELECT id_user, fullname, firstname, lastname, middlename, email, last_connect, is_admin, comments FROM tusuario
				WHERE upper(fullname) LIKE '%".strtolower($stringSearchSQL)."%' OR
					upper(id_user) LIKE '%".strtolower($stringSearchSQL)."%' OR
					upper(firstname) LIKE '%".strtolower($stringSearchSQL)."%' OR
					upper(lastname) LIKE '%".strtolower($stringSearchSQL)."%' OR
					upper(middlename) LIKE '%".strtolower($stringSearchSQL)."%' OR
					upper(email) LIKE '%".strtolower($stringSearchSQL)."%'
					ORDER BY ".$order['field'].' '.$order['order'];
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

    $users = db_process_sql($sql);

    if ($users !== false) {
        // Check ACLs
        $users_id = [];
        foreach ($users as $key => $user) {
            $user_can_manage_all = users_can_manage_group_all('UM');

            $user_groups = users_get_groups(
                $user['id_user'],
                false,
                $user_can_manage_all
            );

            // Get group IDs.
            $user_groups = array_keys($user_groups);

            if (check_acl_one_of_groups($config['id_user'], $user_groups, 'UM') === false
                && $config['id_user'] != $user['id_user']
                || (users_is_admin($config['id_user']) === false
                && users_is_admin($user['id_user']) === true)
            ) {
                unset($users[$key]);
            } else {
                $users_id[] = $user['id_user'];
            }
        }

        if ($only_count) {
            $totalUsers = count($users);
            unset($users);
        }
    } else {
        $totalUsers = 0;
    }
}
