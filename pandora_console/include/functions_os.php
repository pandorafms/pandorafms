<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2011-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// Get critical agents by using the status code in modules.
function os_agents_critical($id_os)
{
    global $config;

    $table = (is_metaconsole() === true) ? 'tmetaconsole_agent' : 'tagente';

    if (users_is_admin() === true) {
        return db_get_sql(
            sprintf(
                'SELECT COUNT(*)
                FROM %s
                WHERE %s.disabled=0 AND
                critical_count>0 AND id_os=%d',
                $table,
                $table,
                $id_os
            )
        );
    } else {
        $groups = array_keys(users_get_groups($config['id_user'], 'AR', false));

        return db_get_sql(
            sprintf(
                'SELECT COUNT(*)
                FROM %s
                WHERE %s.disabled=0 AND
                critical_count>0 AND
                id_os=%d AND id_grupo IN (%s)',
                $table,
                $table,
                $id_os,
                implode(',', $groups)
            )
        );
    }
}


// Get ok agents by using the status code in modules.
function os_agents_ok($id_os)
{
    global $config;

    $table = (is_metaconsole() === true) ? 'tmetaconsole_agent' : 'tagente';

    if (users_is_admin() === true) {
        return db_get_sql(
            sprintf(
                'SELECT COUNT(*)
                FROM %s
                WHERE %s.disabled=0 AND
                normal_count=total_count AND id_os=%d',
                $table,
                $table,
                $id_os
            )
        );
    } else {
        $groups = array_keys(users_get_groups($config['id_user'], 'AR', false));

        return db_get_sql(
            sprintf(
                'SELECT COUNT(*)
                FROM %s
                WHERE %s.disabled=0 AND
                normal_count=total_count AND
                id_os=%d AND id_grupo IN (%s)',
                $table,
                $table,
                $id_os,
                implode(',', $groups)
            )
        );
    }
}


// Get warning agents by using the status code in modules.
function os_agents_warning($id_os)
{
    global $config;

    $table = (is_metaconsole() === true) ? 'tmetaconsole_agent' : 'tagente';

    if (users_is_admin() === true) {
        return db_get_sql(
            sprintf(
                'SELECT COUNT(*)
                FROM %s
                WHERE %s.disabled=0 AND
                critical_count=0 AND warning_count>0
                AND id_os=%d',
                $table,
                $table,
                $id_os
            )
        );
    } else {
        $groups = array_keys(users_get_groups($config['id_user'], 'AR', false));

        return db_get_sql(
            sprintf(
                'SELECT COUNT(*)
                FROM %s
                WHERE %s.disabled=0 AND
                critical_count=0 AND warning_count>0 AND
                id_os=%d AND id_grupo IN (%s)',
                $table,
                $table,
                $id_os,
                implode(',', $groups)
            )
        );
    }
}


// Get unknown agents by using the status code in modules.
function os_agents_unknown($id_os)
{
    global $config;

    $table = (is_metaconsole() === true) ? 'tmetaconsole_agent' : 'tagente';

    if (users_is_admin() === true) {
        return db_get_sql(
            sprintf(
                'SELECT COUNT(*)
                FROM %s
                WHERE %s.disabled=0 AND
                critical_count=0 AND warning_count=0 AND
                unknown_count>0 AND id_os=%d',
                $table,
                $table,
                $id_os
            )
        );
    } else {
        $groups = array_keys(users_get_groups($config['id_user'], 'AR', false));

        return db_get_sql(
            sprintf(
                'SELECT COUNT(*)
                FROM %s
                WHERE %s.disabled=0 AND
                critical_count=0 AND warning_count=0 AND
                unknown_count>0 AND id_os=%d AND id_grupo IN (%s)',
                $table,
                $table,
                $id_os,
                implode(',', $groups)
            )
        );
    }
}


/**
 * Get total agents
 *
 * @param integer $id_os OS id.
 *
 * @return array|boolean
 */
function os_agents_total(int $id_os)
{
    global $config;

    $table = (is_metaconsole() === true) ? 'tmetaconsole_agent' : 'tagente';

    if (users_is_admin() === true) {
        return db_get_sql(
            sprintf(
                'SELECT COUNT(*)
                FROM %s
                WHERE %s.disabled=0 AND id_os=%d',
                $table,
                $table,
                $id_os
            )
        );
    } else {
        $groups = array_keys(users_get_groups($config['id_user'], 'AR', false));

        return db_get_sql(
            sprintf(
                'SELECT COUNT(*)
                FROM %s
                WHERE %s.disabled=0 AND id_os=%d AND id_grupo IN (%s)',
                $table,
                $table,
                $id_os,
                implode(',', $groups)
            )
        );
    }
}


// Get the name of a group given its id.
function os_get_name($id_os)
{
    return db_get_value('name', 'tconfig_os', 'id_os', (int) $id_os);
}


function os_get_os($hash=false)
{
    $result = [];
    $op_systems = db_get_all_rows_in_table('tconfig_os');
    if (empty($op_systems)) {
        $op_systems = [];
    }

    if ($hash) {
        foreach ($op_systems as $key => $value) {
            $result[$value['id_os']] = $value['name'];
        }
    } else {
        $result = $op_systems;
    }

    return $result;
}


function os_get_icon($id_os)
{
    return db_get_value('icon_name', 'tconfig_os', 'id_os', (int) $id_os);
}


/**
 * Transform the old icon url.
 *
 * @param string $url_icon Icon url .
 *
 * @return string
 */
function os_transform_url_icon($url_icon)
{
    $return = substr($url_icon, 0, strpos($url_icon, basename($url_icon)));
    switch (basename($url_icon)) {
        case 'android.png':
            $return .= 'android@os.svg';
        break;

        case 'so_mac.png':
            $return .= 'apple@os.svg';
        break;

        case 'so_cisco.png':
            $return .= 'cisco@os.svg';
        break;

        case 'so_aix.png':
            $return .= 'aix@os.svg';
        break;

        case 'so_win.png':
            $return .= 'windows@os.svg';
        break;

        case 'so_vmware.png':
            $return .= 'vmware@os.svg';
        break;

        case 'so_solaris.png':
            $return .= 'solaris@os.svg';
        break;

        case 'so_linux.png':
            $return .= 'linux@os.svg';
        break;

        case 'so_bsd.png':
            $return .= 'freebsd@os.svg';
        break;

        case 'so_cluster.png':
            $return .= 'cluster@os.svg';
        break;

        case 'so_other.png':
            $return .= 'other-OS@os.svg';
        break;

        case 'so_switch.png':
            $return .= 'switch@os.svg';
        break;

        case 'so_mainframe.png':
            $return .= 'mainframe@os.svg';
        break;

        case 'so_hpux.png':
        case 'server_hpux.png':
            $return .= 'HP@os.svg';
        break;

        case 'so_router.png':
        case 'router.png':
            $return .= 'routers@os.svg';
        break;

        case 'embedded.png':
            $return .= 'embedded@os.svg';
        break;

        case 'network.png':
            $return .= 'network-server@os.svg';
        break;

        case 'satellite.png':
            $return .= 'satellite@os.svg';
        break;

        default:
            $return = $url_icon;
        break;
    }

    return $return;
}
