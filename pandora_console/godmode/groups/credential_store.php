<?php
/**
 * Credentials management view.
 *
 * @category   Credentials management
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
global $config;

// Check access.
check_login();

if (! check_acl($config['id_user'], 0, 'PM')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access event viewer'
    );

    if (is_ajax()) {
        return ['error' => 'noaccess'];
    }

    include 'general/noaccess.php';
    return;
}

// Required files.
ui_require_css_file('credential_store');
require_once $config['homedir'].'/include/functions_credential_store.php';

if (is_ajax()) {
    $draw = get_parameter('draw', 0);
    $filter = get_parameter('filter', []);

    if ($draw) {
        // Datatables offset, limit and order.
        $start = get_parameter('start', 0);
        $length = get_parameter('length', $config['block_size']);
        $order = get_datatable_order(true);
        try {
            ob_start();

            $fields = [
                'cs.*',
                'tg.nombre as `group`',
            ];

            // Retrieve data.
            $data = credentials_get_all(
                // Fields.
                $fields,
                // Filter.
                $filter,
                // Offset.
                $start,
                // Limit.
                $length,
                // Order.
                $order['direction'],
                // Sort field.
                $order['field']
            );

            // Retrieve counter.
            $count = credentials_get_all(
                'count',
                $filter
            );

            if ($data) {
                $data = array_reduce(
                    $data,
                    function ($carry, $item) {
                        // Transforms array of arrays $data into an array
                        // of objects, making a post-process of certain fields.
                        $tmp = (object) $item;
                        $tmp->username = io_safe_output($tmp->username);
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
            return json_encode(['error' => $e->getMessage()]);
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

    return;
}



// Load interface.
try {
    $columns = [
        'group',
        'identifier',
        'product',
        'username',
        'options',
    ];

    $column_names = [
        __('Group'),
        __('Identifier'),
        __('Product'),
        __('User'),
        [
            'text'  => __('Options'),
            'class' => 'action_buttons',
        ],
    ];

    // Load datatables user interface.
    ui_print_datatable(
        [
            'class'               => 'info_table events',
            'style'               => 'width: 100%',
            'columns'             => $columns,
            'column_names'        => $column_names,
            'ajax_url'            => 'godmode/groups/credential_store',
            'ajax_postprocess'    => 'process_datatables_item(item)',
            'no_sortable_columns' => [-1],
        ]
    );
} catch (Exception $e) {
    echo $e->getMessage();
}

?>

<script type="text/javascript">
    function process_datatables_item(item) {
        item.options = 'aa';
    }

</script>
