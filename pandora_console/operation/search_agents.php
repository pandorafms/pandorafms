<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
global $config;

/*
    enterprise_include_once('include/functions_policies.php');
require_once $config['homedir'].'/include/functions_users.php';*/

// Datatables list.
try {
    $columns = [
        'agent',
        'comentarios',
        'os',
        'interval',
        'group_icon',
        'module',
        'status',
        'alert',
        'last_contact',
    ];

    $column_names = [
        __('Agent'),
        __('Description'),
        __('OS'),
        __('Interval'),
        __('Group'),
        __('Modules'),
        __('Status'),
        __('Alerts'),
        __('Last contact'),
    ];

    $tableId = 'agents_search';

    // Load datatables user interface.
    ui_print_datatable(
        [
            'id'                  => $tableId,
            'class'               => 'info_table',
            'style'               => 'width: 99%',
            'columns'             => $columns,
            'column_names'        => $column_names,
            'ajax_url'            => 'operation/search_agents.getdata',
            'ajax_postprocces'    => 'process_datatables_item(item)',
            'ajax_data'           => ['search_agents' => 1],
            'order'               => [
                'field'     => 'alias',
                'direction' => 'asc',
            ],
            'search_button_class' => 'sub filter float-right',
            'filter_main_class'   => 'box-flat white_table_graph fixed_filter_bar',
        ]
    );
} catch (Exception $e) {
    echo $e->getMessage();
}
