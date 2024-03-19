<?php
/**
 * Tokens ajax.
 *
 * @category   Users
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
 * Copyright (c) 2005-2024 Pandora FMS
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

$list_user_tokens = (bool) get_parameter('list_user_tokens');

if ($list_user_tokens === true) {
    global $config;

    // Datatables offset, limit and order.
    $filter = get_parameter('filter', []);
    $page = (int) get_parameter('start', 0);
    $pageSize = (int) get_parameter('length', $config['block_size']);
    $orderBy = get_datatable_order(true);

    $sortField = ($orderBy['field'] ?? null);
    $sortDirection = ($orderBy['direction'] ?? null);

    try {
        ob_start();

        include_once $config['homedir'].'/include/functions_token.php';
        if (isset($filter['form_token_table_search_bt']) === true) {
            unset($filter['form_token_table_search_bt']);
        }

        $return = list_user_tokens(
            ($page / $pageSize),
            $pageSize,
            $sortField,
            strtoupper($sortDirection),
            $filter
        );

        if (empty($return['data']) === false) {
            // Format end of life date.
            $return['data'] = array_map(
                function ($item) use ($config) {
                    $itemArray = $item->toArray();

                    $sec = 'gusuarios';
                    if (is_metaconsole() === true) {
                        $sec = 'advanced';
                    }

                    $edit_url = 'index.php?sec='.$sec;
                    $edit_url .= '&sec2=godmode/users/configure_token&pure=0';
                    $edit_url .= '&id_token='.$itemArray['idToken'];

                    $delete_url = 'index.php?sec='.$sec;
                    $delete_url .= '&sec2=godmode/users/token_list';
                    $delete_url .= '&pure=0&delete_token=1';
                    $delete_url .= '&id_token='.$itemArray['idToken'];

                    $itemArray['label'] = html_print_anchor(
                        [
                            'href'    => $edit_url,
                            'content' => $itemArray['label'],
                        ],
                        true
                    );

                    if (empty($itemArray['validity']) === true) {
                        $itemArray['validity'] = __('Never');
                    } else {
                        $itemArray['validity'] = date($config['date_format'], strtotime($itemArray['validity']));
                    }

                    if (empty($itemArray['lastUsage']) === true) {
                        $itemArray['lastUsage'] = __('Never');
                    } else {
                        $itemArray['lastUsage'] = human_time_comparation($itemArray['lastUsage']);
                    }

                    $itemArray['options'] = '<div class="table_action_buttons float-right">';
                    $itemArray['options'] .= html_print_anchor(
                        [
                            'href'    => $edit_url,
                            'content' => html_print_image(
                                'images/edit.svg',
                                true,
                                [
                                    'title' => __('Show'),
                                    'class' => 'main_menu_icon invert_filter',
                                ]
                            ),
                        ],
                        true
                    );
                    $itemArray['options'] .= html_print_anchor(
                        [
                            'href'    => $delete_url,
                            'onClick' => 'if (!confirm(\' '.__('Are you sure?').'\')) return false;',
                            'content' => html_print_image(
                                'images/delete.svg',
                                true,
                                [
                                    'title' => __('Delete'),
                                    'class' => 'invert_filter main_menu_icon',
                                ]
                            ),
                        ],
                        true
                    );
                    $itemArray['options'] .= '</div>';

                    return $itemArray;
                },
                $return['data']
            );
        }

        // Datatables format: RecordsTotal && recordsfiltered.
        echo json_encode(
            [
                'data'            => $return['data'],
                'recordsTotal'    => $return['paginationData']['totalRegisters'],
                'recordsFiltered' => $return['paginationData']['totalRegisters'],
            ]
        );
        // Capture output.
        $response = ob_get_clean();
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
        return;
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

    return;
}
