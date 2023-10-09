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
 * |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 * |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 Pandora FMS
 * Please see https://pandorafms.com/community/ for full contribution list
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

if ($method === 'deleteOS') {
    global $config;

    $id_os = get_parameter('id_os', null);

    if (empty($id_os) === true || $id_os < 16) {
        echo json_encode(['deleted' => false]);
        return;
    }

    if (db_process_sql_delete(
        'tconfig_os',
        ['id_os' => $id_os]
    ) === false
    ) {
        echo json_encode(['deleted' => false]);
    } else {
        echo json_encode(['deleted' => true]);
    }
}

if ($method === 'deleteOSVersion') {
    global $config;

    $id_os_version = get_parameter('id_os_version', null);

    if (empty($id_os_version) === true || $id_os_version < 1) {
        echo json_encode(['deleted' => false]);
    }

    if (db_process_sql_delete(
        'tconfig_os_version',
        ['id_os_version' => $id_os_version]
    ) === false
    ) {
        echo json_encode(['deleted' => false]);
    } else {
        echo json_encode(['deleted' => true]);
    }
}

if ($method === 'drawOSTable') {
    // Datatables offset, limit and order.
    $filter = get_parameter('filter', []);
    $start = get_parameter('start', 0);
    $length = get_parameter('length', $config['block_size']);
    $orderBy = get_datatable_order(true);

    $sort_field = $orderBy['field'];
    $order = $orderBy['direction'];

    $pagination = '';

    $pagination = sprintf(
        ' LIMIT %d OFFSET %d ',
        $length,
        $start
    );

    try {
        ob_start();

        $fields = ['*'];
        $sql_filters = [];

        if (isset($filter['free_search']) === true
            && empty($filter['free_search']) === false
        ) {
            $sql_filters[] = sprintf(
                ' AND (`name` like "%%%s%%" OR `description` like "%%%s%%") ',
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
                    'id_os',
                    'name',
                    'description',
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
            FROM tconfig_os
            WHERE 1=1
            %s
            %s
            %s',
            join(',', $fields),
            join(' ', $sql_filters),
            $order_by,
            $pagination
        );

        $count_sql = sprintf(
            'SELECT id_os
            FROM tconfig_os
            WHERE 1=1
            %s',
            join(' ', $sql_filters)
        );

        $return = db_get_all_rows_sql($sql);
        if ($return === false) {
            $data = [];
        } else {
            $data = $return;
        }

        $data = array_map(
            function ($item) {
                $item['icon_img'] = ui_print_os_icon($item['id_os'], false, true);

                if (is_management_allowed() === true) {
                    if (is_metaconsole() === true) {
                        $osNameUrl = 'index.php?sec=advanced&sec2=advanced/component_management&tab=os_manage&action=edit&tab2=list&id_os='.$item['id_os'];
                    } else {
                        $osNameUrl = 'index.php?sec=gsetup&sec2=godmode/setup/os&action=edit&tab=manage_os&id_os='.$item['id_os'];
                    }

                    $item['name'] = html_print_anchor(
                        [
                            'href'    => $osNameUrl,
                            'content' => $item['name'],
                        ],
                        true
                    );
                } else {
                    $item['name'] = $item['name'];
                }

                $item['description'] = ui_print_truncate_text(
                    $item['description'],
                    'description',
                    true,
                    true
                );

                if (is_management_allowed() === true) {
                    $item['enable_delete'] = false;

                    if ($item['id_os'] > 16) {
                        $item['enable_delete'] = true;
                    }
                }

                return $item;
            },
            $data
        );

        // Retrieve counter.
        $count = db_get_value('count(*)', '('.$count_sql.') t');

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

if ($method === 'drawOSVersionTable') {
    // Datatables offset, limit and order.
    $filter = get_parameter('filter', []);
    $start = get_parameter('start', 0);
    $length = get_parameter('length', $config['block_size']);
    $orderBy = get_datatable_order(true);

    $sort_field = $orderBy['field'];
    $order = $orderBy['direction'];

    $pagination = '';

    $pagination = sprintf(
        ' LIMIT %d OFFSET %d ',
        $length,
        $start
    );

    try {
        ob_start();

        $fields = ['*'];
        $sql_filters = [];

        if (isset($filter['free_search']) === true
            && empty($filter['free_search']) === false
        ) {
            $sql_filters[] = sprintf(
                ' AND (`product` like "%%%s%%" OR `version` like "%%%s%%") ',
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
                    'product',
                    'version',
                    'end_of_support',
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
            FROM tconfig_os_version
            WHERE 1=1
            %s
            %s
            %s',
            join(',', $fields),
            join(' ', $sql_filters),
            $order_by,
            $pagination
        );

        $count_sql = sprintf(
            'SELECT id_os_version
            FROM tconfig_os_version
            WHERE 1=1
            %s',
            join(' ', $sql_filters)
        );

        $return = db_get_all_rows_sql($sql);

        if ($return === false) {
            $data = [];
        } else {
            // Format end of life date.
            $return = array_map(
                function ($item) {
                    $date_string = date_w_fixed_tz($item['end_of_support']);
                    $timestamp = strtotime($date_string);
                    $date_without_time = date('F j, Y', $timestamp);
                    $item['end_of_support'] = $date_without_time;
                    return $item;
                },
                $return
            );

            $data = $return;
        }

        // Retrieve counter.
        $count = db_get_value('count(*)', '('.$count_sql.') t');

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
