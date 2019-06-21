<?php
/**
 * Credentials management auxiliary functions.
 *
 * @category   Credentials management library.
 * @package    Pandora FMS
 * @subpackage Opensource
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
 * Please see http://pandorafms.org for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */

// Begin.


/**
 * Returns an array with all the credentials matching filter and ACL.
 *
 * @param array   $fields     Fields array or 'count' keyword to retrieve count.
 * @param array   $filter     Filters to be applied.
 * @param integer $offset     Offset (pagination).
 * @param integer $limit      Limit (pagination).
 * @param string  $order      Sort order.
 * @param string  $sort_field Sort field.
 *
 * @return array With all results or false if error.
 * @throws Exception On error.
 */
function credentials_get_all(
    $fields,
    array $filter,
    $offset=null,
    $limit=null,
    $order=null,
    $sort_field=null
) {
    $sql_filters = [];
    $group_by = '';
    $pagination = '';

    global $config;

    $user_is_admin = users_is_admin();

    if (!is_array($filter)) {
        error_log('[credential_get_all] Filter must be an array.');
        throw new Exception('[credential_get_all] Filter must be an array.');
    }

    $count = false;
    if (!is_array($fields) && $fields == 'count') {
        $fields = ['cs.*'];
        $count = true;
    } else if (!is_array($fields)) {
        error_log('[credential_get_all] Fields must be an array or "count".');
        throw new Exception('[credential_get_all] Fields must be an array or "count".');
    }

    $sql = sprintf(
        'SELECT %s
         FROM tcredential_store cs
         LEFT JOIN tgrupo tg
            ON tg.id_grupo = cs.id_group
         WHERE 1=1
         %s
         %s
         %s',
        join(',', $fields),
        join(',', $sql_filters),
        $group_by,
        $pagination
    );

    if ($count) {
        $sql = sprintf('SELECT count(*) as n FROM ( %s ) tt', $sql);

        return db_get_value_sql($sql);
    }

    return db_get_all_rows_sql($sql);
}
