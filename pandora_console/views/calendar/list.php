<?php
/**
 * Calendar: Calendar list page.
 *
 * @category   View
 * @package    Pandora FMS
 * @subpackage Alert
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

// Header.
\ui_print_page_header(
    // Title.
    __('Calendars'),
    // Icon.
    'images/gm_alerts.png',
    // Return.
    false,
    // Help.
    'alert_special_days',
    // Godmode.
    true,
    // Options.
    $tabs
);

if (empty($message) === false) {
    echo $message;
}

// Datatables list.
try {
    $columns = [
        'name',
        'id_group',
        'description',
        [
            'text'  => 'options',
            'class' => 'w150px action_buttons',
        ],
    ];

    $column_names = [
        __('Name'),
        __('Group'),
        __('Description'),
        __('Options'),
    ];

    $tableId = 'calendar_list';
    // Load datatables user interface.
    ui_print_datatable(
        [
            'id'                  => $tableId,
            'class'               => 'info_table',
            'style'               => 'width: 100%',
            'columns'             => $columns,
            'column_names'        => $column_names,
            'ajax_url'            => $ajax_url,
            'ajax_data'           => ['method' => 'drawListCalendar'],
            'no_sortable_columns' => [-1],
            'order'               => [
                'field'     => 'name',
                'direction' => 'asc',
            ],
            'search_button_class' => 'sub filter float-right',
            'form'                => [
                'inputs' => [
                    [
                        'label' => __('Free search'),
                        'type'  => 'text',
                        'class' => 'mw250px',
                        'id'    => 'free_search',
                        'name'  => 'free_search',
                    ],
                ],
            ],
        ]
    );
} catch (Exception $e) {
    echo $e->getMessage();
}

if ((bool) check_acl($config['id_user'], 0, 'LM') === true) {
    HTML::printForm(
        [
            'form'   => [
                'action' => $url.'&op=edit',
                'method' => 'POST',
            ],
            'inputs' => [
                [
                    'arguments' => [
                        'name'       => 'button',
                        'label'      => __('Create'),
                        'type'       => 'submit',
                        'attributes' => 'class="sub next"',
                    ],
                ],
            ],
        ]
    );
}
