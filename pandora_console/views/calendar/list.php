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

\ui_require_css_file('wizard');
\enterprise_include_once('meta/include/functions_alerts_meta.php');

if (\is_metaconsole() === true) {
    \alerts_meta_print_header($tabs);
} else {
    // Header.
    ui_print_standard_header(
        __('Alerts'),
        'images/gm_alerts.png',
        false,
        'alert_special_days',
        true,
        $tabs,
        [
            [
                'link'  => '',
                'label' => __('Alerts'),
            ],
            [
                'link'  => '',
                'label' => __('Special days'),
            ],
        ]
    );
}

$is_management_allowed = \is_management_allowed();
if ($is_management_allowed === false) {
    if (\is_metaconsole() === false) {
        $url_link = '<a target="_blank" href="'.ui_get_meta_url($url).'">';
        $url_link .= __('metaconsole');
        $url_link .= '</a>';
    } else {
        $url_link = __('any node');
    }

    \ui_print_warning_message(
        __(
            'This node is configured with centralized mode. All alert calendar information is read only. Go to %s to manage it.',
            $url_link
        )
    );
}

if (empty($message) === false) {
    echo $message;
}

// Datatables list.
try {
    $columns = [
        [
            'text'  => 'id',
            'class' => 'invisible',
        ],
        'name',
        'id_group',
        'description',
        [
            'text'  => 'options',
            'class' => 'w150px table_action_buttons',
        ],
    ];

    $column_names = [
        __('ID'),
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
            'style'               => 'width: 99%',
            'columns'             => $columns,
            'column_names'        => $column_names,
            'ajax_url'            => $ajax_url,
            'ajax_data'           => ['method' => 'drawListCalendar'],
            'no_sortable_columns' => [-1],
            'order'               => [
                'field'     => 'id',
                'direction' => 'asc',
            ],
            'search_button_class' => 'sub filter float-right',
            'form'                => [
                'inputs' => [
                    [
                        'label' => __('Free search'),
                        'type'  => 'text',
                        'class' => 'w25p',
                        'id'    => 'free_search',
                        'name'  => 'free_search',
                    ],
                ],
            ],
            'filter_main_class'   => 'box-flat white_table_graph fixed_filter_bar',
            'dom_elements'        => 'lftpB',
        ]
    );
} catch (Exception $e) {
    echo $e->getMessage();
}

if ((bool) check_acl($config['id_user'], 0, 'LM') === true) {
    $form_create = HTML::printForm(
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
                        'attributes' => ['icon' => 'wand'],
                    ],
                ],
            ],
        ],
        true
    );
    html_print_action_buttons($form_create);
}
