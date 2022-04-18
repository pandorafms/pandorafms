<?php
/**
 * Update manager client historical updates backend.
 *
 * @category   Update Manager
 * @package    Pandora FMS
 * @subpackage Community
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
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
global $config;

check_login();
if ((bool) check_acl($config['id_user'], 0, 'PM') === false
    && (bool) is_user_admin($config['id_user']) === false
) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Setup Management'
    );
    include 'general/noaccess.php';
    return;
}

$method = get_parameter('method', null);

if ($method === 'draw') {
    // Datatables offset, limit and order.
    $filter = get_parameter('filter', []);
    $start = get_parameter('start', 0);
    $length = get_parameter('length', $config['block_size']);
    $orderBy = get_datatable_order(true);

    $sort_field = $orderBy['field'];
    $order = $orderBy['direction'];

    $pagination = '';
    if (isset($start) && $start > 0
        && isset($length) && $length >= 0
    ) {
        $pagination = sprintf(
            ' LIMIT %d OFFSET %d ',
            $start,
            $length
        );
    }

    try {
        ob_start();

        $fields = ['*'];
        $sql_filters = [];

        if (isset($filter['free_search']) === true
            && empty($filter['free_search']) === false
        ) {
            $sql_filters[] = sprintf(
                ' AND (`id_user` like "%%%s%%" OR `version` like "%%%s%%") ',
                $filter['free_search'],
                $filter['free_search']
            );
        }

        if (isset($order) === true) {
            $dir = 'asc';
            if ($order == 'desc') {
                $dir = 'desc';
            };

            if (in_array(
                $sort_field,
                [
                    'version',
                    'type',
                    'id_user',
                    'utimestamp',
                ]
            ) === true
            ) {
                $order_by = sprintf(
                    'ORDER BY `%s` %s',
                    $sort_field,
                    $dir
                );
            }
        }

        // Retrieve data.
        $sql = sprintf(
            'SELECT %s
            FROM tupdate_journal
            WHERE 1=1
            %s
            %s
            %s',
            join(',', $fields),
            join(' ', $sql_filters),
            $order_by,
            $pagination
        );

        $return = db_get_all_rows_sql($sql);
        if ($return === false) {
            $data = [];
        } else {
            $data = $return;
        }

        // Retrieve counter.
        $count = db_get_value('count(*)', '('.$sql.') t');

        if ($data) {
            $data = array_reduce(
                $data,
                function ($carry, $item) {
                    // Transforms array of arrays $data into an array
                    // of objects, making a post-process of certain fields.
                    $tmp = (object) $item;

                    $tmp->utimestamp = human_time_comparation($tmp->utimestamp);

                    $carry[] = $tmp;
                    return $carry;
                }
            );
        }

        // Datatables format: RecordsTotal && recordsfiltered.
        echo json_encode(
            [
                'data'            => $data,
                'recordsTotal'    => $count,
                'recordsFiltered' => $count,
            ]
        );
        // Capture output.
        $response = ob_get_clean();
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }

    // If not valid, show error with issue.
    json_decode($response);
    if (json_last_error() == JSON_ERROR_NONE) {
        // If valid dump.
        echo $response;
    } else {
        echo json_encode(
            ['error' => $response]
        );
    }

    exit;
}
