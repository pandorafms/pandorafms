<?php
/**
 * Cluster View: List
 *
 * @category   View
 * @package    Pandora FMS
 * @subpackage Cluster View
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
ui_print_standard_header(
    __('Cluster view'),
    'images/chart.png',
    false,
    '',
    true,
    [],
    [
        [
            'link'  => '',
            'label' => __('Monitoring'),
        ],
        [
            'link'  => '',
            'label' => __('Clusters'),
        ],
    ]
);

if (empty($message) === false) {
    echo $message;
}

// Datatables list.
try {
    $columns = [
        'name',
        'description',
        'group',
        'type',
        'nodes',
        'known_status',
        [
            'text'  => 'options',
            'class' => 'table_action_buttons',
        ],
    ];

    $column_names = [
        __('Name'),
        __('Description'),
        __('Group'),
        __('Type'),
        __('Nodes'),
        __('Status'),
        __('Options'),
    ];

    $tableId = 'clusters';

    // Load datatables user interface.
    ui_print_datatable(
        [
            'id'                  => $tableId,
            'class'               => 'info_table',
            'style'               => 'width: 99%',
            'columns'             => $columns,
            'column_names'        => $column_names,
            'ajax_url'            => $model->ajaxController,
            'ajax_data'           => ['method' => 'draw'],
            'no_sortable_columns' => [-1],
            'order'               => [
                'field'     => 'known_status',
                'direction' => 'asc',
            ],
            'search_button_class' => 'sub filter float-right',
            'form'                => [
                'inputs' => [
                    [
                        'label'          => __('Filter group'),
                        'name'           => 'id_group',
                        'returnAllGroup' => true,
                        'privilege'      => 'AR',
                        'type'           => 'select_groups',
                        'return'         => true,
                        'size'           => '250px',
                    ],
                    [
                        'label' => __('Free search'),
                        'type'  => 'text',
                        'class' => 'mw250px',
                        'id'    => 'free_search',
                        'name'  => 'free_search',
                    ],
                ],
            ],
            'filter_main_class'   => 'box-flat white_table_graph fixed_filter_bar',
        ]
    );
} catch (Exception $e) {
    echo $e->getMessage();
}

if (check_acl($config['id_user'], 0, 'AW')) {
    $buttons[] = html_print_submit_button(
        __('New cluster'),
        'submit',
        false,
        [
            'class' => 'sub ok',
            'icon'  => 'next',
        ],
        true
    );
    echo '<form action="'.ui_get_full_url($model->url.'&op=new').'" method="POST">';
    html_print_action_buttons(
        implode('', $buttons),
        ['type' => 'form_action']
    );
    echo '</form>';
}
