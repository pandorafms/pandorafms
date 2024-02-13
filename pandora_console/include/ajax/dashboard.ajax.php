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

$method = get_parameter('method', null);
$filter = get_parameter('filter', '');
$urlDashboard = get_parameter('urlDashboard', '');
$manageDashboards = get_parameter('manageDashboards', 1);

if ($method === 'draw') {
    // Datatables offset, limit and order.
    $filter = get_parameter('filter', []);
    $start = get_parameter('start', 0);
    $length = get_parameter('length', $config['block_size']);
    $orderBy = get_datatable_order(true);

    switch ($orderBy['field']) {
        case 'groups':
            $sort_field = 'nombre';
        break;

        case 'favorite':
            $sort_field = 'active';
        break;

        default:
            $sort_field = $orderBy['field'];
        break;
    }

    $order = $orderBy['direction'];

    $pagination = '';
    $pagination = sprintf(
        ' LIMIT %d OFFSET %d ',
        $length,
        $start,
    );

    try {
        $table = new stdClass();
        $table->width = '100%';
        $table->class = 'info_table';
        $table->headstyle['name'] = 'text-align: left;';
        $table->headstyle['cells'] = 'text-align: center;';
        $table->headstyle['groups'] = 'text-align: center;';
        $table->headstyle['favorite'] = 'text-align: center;';
        $table->headstyle['full_screen'] = 'text-align: center;';

        $table->style = [];
        $table->style['name'] = 'text-align: left;';
        $table->style['cells'] = 'text-align: center;';
        $table->style['groups'] = 'text-align: center;';
        $table->style['favorite'] = 'text-align: center;';
        $table->style['full_screen'] = 'text-align: center;';

        $table->size = [];
        $table->size['name'] = '40%';
        $table->size['full_screen'] = '30px';

        $table->head = [];
        $table->head['name'] = __('Name');
        $table->head['cells'] = __('Cells');
        $table->head['groups'] = __('Group');
        $table->head['favorite'] = __('Favorite');
        $table->head['full_screen'] = __('Full screen');

        if ($manageDashboards === 1) {
            $table->head['copy'] = __('Copy');
            $table->head['delete'] = __('Delete');
            $table->headstyle['copy'] = 'text-align: center;';
            $table->headstyle['delete'] = 'text-align: center;';
            $table->style['copy'] = 'text-align: center;';
            $table->style['delete'] = 'text-align: center;';
            $table->size['cells'] = '30px';
            $table->size['groups'] = '30px';
            $table->size['favorite'] = '30px';
            $table->size['copy'] = '30px';
            $table->size['delete'] = '30px';
        } else {
            $table->size['cells'] = '60px';
            $table->size['groups'] = '60px';
            $table->size['favorite'] = '60px';
        }

        $table->data = [];

        $where_name = '';
        if (strlen($filter['free_search']) > 0) {
            $where_name = 'name LIKE "%'.$filter['free_search'].'%"';
        }

        if (is_user_admin($config['id_user']) === false) {
            $group_list = \users_get_groups(
                $config['id_ser'],
                'RR',
                true
            );
        }

        $where_group = '';
        if (empty($filter['group']) === false && $filter['group'] !== '0') {
            $where_group = sprintf('id_group = %s', $filter['group']);
            if (empty($where_name) === false) {
                $where_group = 'AND '.$where_group;
            }
        } else if (empty($group_list) === false) {
            $where_group = sprintf('id_group IN (%s)', implode(',', array_keys($group_list)));
            if (empty($where_name) === false) {
                $where_group = 'AND '.$where_group;
            }
        }

        $where = '';
        if (empty($where_name) === false || empty($where_group) === false) {
            $where = sprintf(
                'WHERE %s %s',
                $where_name,
                $where_group
            );
        }

        $sql = 'SELECT * FROM tdashboard LEFT JOIN tgrupo ON tgrupo.id_grupo = tdashboard.id_group '.$where.' ORDER BY '.$sort_field.' '.$order.$pagination;
        $dashboards = db_get_all_rows_sql($sql);
        $count = db_get_value_sql('SELECT COUNT(*) FROM tdashboard '.$where);
        foreach ($dashboards as $dashboard) {
            $data = [];

            $dataQuery = ['dashboardId' => $dashboard['id']];

            $url = $urlDashboard.'&'.http_build_query($dataQuery);
            $data['name'] = '<a href="'.$url.'">';
            $data['name'] .= $dashboard['name'];
            $data['name'] .= '</a>';

            $data['cells'] = $dashboard['cells'];

            if (empty($dashboard['id_user']) === false) {
                $data['groups'] = __(
                    'Private for (%s)',
                    $dashboard['id_user']
                );
            } else {
                $data['groups'] = ui_print_group_icon(
                    $dashboard['id_group'],
                    true
                );
            }

            $data['favorite'] = $dashboard['active'];

            $dataQueryFull = [
                'dashboardId' => $dashboard['id'],
                'pure'        => 1,
            ];

            $urlFull = $urlDashboard;
            $urlFull .= '&'.\http_build_query($dataQueryFull);
            $data['full_screen'] = '<a href="'.$urlFull.'">';
            $data['full_screen'] .= \html_print_image(
                'images/fullscreen@svg.svg',
                true,
                ['class' => 'main_menu_icon invert_filter']
            );
            $data['full_screen'] .= '</a>';

            if ($manageDashboards === 1) {
                $data['copy'] = '';
                $data['delete'] = '';
            }

            if (check_acl_restricted_all($config['id_user'], $dashboard['id_group'], 'RM')) {
                $dataQueryCopy = [
                    'dashboardId'   => $dashboard['id'],
                    'copyDashboard' => 1,
                ];
                $urlCopy = $urlDashboard.'&'.\http_build_query($dataQueryCopy);
                $data['copy'] = '<a href="'.$urlCopy.'">';
                $data['copy'] .= html_print_image('images/copy.svg', true, ['class' => 'main_menu_icon invert_filter']);
                $data['copy'] .= '</a>';

                $dataQueryDelete = [
                    'dashboardId'     => $dashboard['id'],
                    'deleteDashboard' => 1,
                ];
                $urlDelete = $urlDashboard;
                $urlDelete .= '&'.\http_build_query($dataQueryDelete);
                $data['delete'] = '<a href="'.$urlDelete;
                $data['delete'] .= '" onclick="javascript: if (!confirm(\''.__('Are you sure?').'\')) return false;">';
                $data['delete'] .= \html_print_image(
                    'images/delete.svg',
                    true,
                    ['class' => 'main_menu_icon invert_filter']
                );
                $data['delete'] .= '</a>';
            }

            $table->cellclass[] = [
                'full_screen' => 'table_action_buttons',
                'copy'        => 'table_action_buttons',
                'delete'      => 'table_action_buttons',
            ];

            $table->data[] = $data;
        }

        // Datatables format: RecordsTotal && recordsfiltered.
        echo json_encode(
            [
                'data'            => $table->data,
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
