<?php
/**
 * ITSM View List Tickets
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage ITSM
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 Artica Soluciones Tecnologicas
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

// Includes.
require_once $config['homedir'].'/include/class/HTML.class.php';

global $config;

ui_require_css_file('integriaims');

// Header tabs.
ui_print_standard_header(
    __('ITSM Tickets'),
    '',
    false,
    'integria_tab',
    false,
    [],
    [
        [
            'link'  => 'index.php?sec=ITSM&sec2=operation/ITSM/itsm',
            'label' => __('ITSM'),
        ],
        [
            'link'  => 'index.php?sec=ITSM&sec2=operation/ITSM/itsm&operation=list',
            'label' => __('ITSM Tickets'),
        ],
    ]
);

/*
    if (isset($resultDelete) === true) {
    \ui_print_result_message(
        $resultDelete,
        __('Successfully deleted'),
        __('Could not be deleted')
    );
    }

    if (isset($resultCopy) === true) {
    \ui_print_result_message(
        $resultCopy,
        __('Successfully duplicate'),
        __('Could not be duplicate')
    );
    }
*/


try {
    $columns = [
        'id',
        'title',
        'groupCompany',
        'statusResolution',
        'priority',
        'updated',
        'started',
        'creator',
        'owner',
    ];

    $column_names = [
        __('ID'),
        __('Title'),
        __('Group').'/'.__('Company'),
        __('Status').'/'.__('Resolution'),
        __('Priority'),
        __('Updated'),
        __('Started'),
        __('Creator'),
        __('Owner'),
    ];

    ui_print_datatable(
        [
            'id'                  => 'itms_list_tickets',
            'class'               => 'info_table',
            'style'               => 'width: 99%',
            'columns'             => $columns,
            'column_names'        => $column_names,
            'ajax_url'            => $ajaxController,
            'ajax_data'           => ['method' => 'getListTickets'],
            'no_sortable_columns' => [
                2,
                3,
            ],
            'search_button_class' => 'sub filter float-right',
            'filter_main_class'   => 'box-flat white_table_graph fixed_filter_bar',
        ]
    );
} catch (Exception $e) {
    echo $e->getMessage();
}

$input_button = '';
$input_button = html_print_button(
    __('Create'),
    '',
    false,
    '',
    ['icon' => 'next'],
    true
);

html_print_action_buttons($input_button);
