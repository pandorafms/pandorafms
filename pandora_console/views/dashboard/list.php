<?php
/**
 * Dashboards View List Table Pandora FMS Console
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Dashboards
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

// Includes.
require_once $config['homedir'].'/include/class/HTML.class.php';

global $config;

ui_require_css_file('dashboards');
if ((bool) \is_metaconsole() === true) {
    \ui_require_css_file('meta_dashboards');
}

ui_print_standard_header(
    __('Dashboards'),
    '',
    false,
    '',
    true,
    [],
    [
        [
            'link'  => '',
            'label' => __('Dashboards'),
        ],
    ]
);

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

// Filter table.
$filter_id_group = \get_parameter_post('id_group', '');
$filter_name = \get_parameter_post('name', '');

$filterTable = new stdClass();
$filterTable->class = 'filter-table-adv w100p';
$filterTable->size[0] = '20%';
$filterTable->size[1] = '20%';
$filterTable->data = [];

$filterTable->data[0][0] = html_print_label_input_block(
    __('Group'),
    html_print_select_groups(
        false,
        'AR',
        true,
        'id_group',
        $filter_id_group,
        '',
        '',
        '',
        true,
        false,
        true,
        'w100p',
        false,
        ''
    )
);

$filterTable->data[0][1] = html_print_label_input_block(
    __('Name'),
    html_print_input_text('name', $filter_name, '', 25, 80, true)
);

$form_html = '<form id="form_dashboard_search" method="post" action="'.$urlDashboard.'">';
$form_html .= html_print_table($filterTable, true);
$form_html .= html_print_div(
    [
        'class'   => 'action-buttons',
        'content' => html_print_submit_button(
            __('Filter'),
            'uptbutton',
            false,
            [
                'icon' => 'search',
                'mode' => 'mini',
            ],
            true
        ),
    ],
    true
);
$form_html .= '</form>';

ui_toggle(
    $form_html,
    '<span class="subsection_header_title">'.__('Filter').'</span>',
    'dashboard_search',
    false,
    true,
    false,
    '',
    'white-box-content',
    'box-flat white_table_graph fixed_filter_bar'
);


if (empty($dashboards) === true) {
    ui_print_info_message(
        [
            'no_close' => true,
            'message'  => __('There are no dashboards defined.'),
        ]
    );
} else {
    $id_table = 'dashboards_list';
    $columns = [
        'name',
        'cells',
        'groups',
        'favorite',
        'full_screen',
    ];

    $column_names = [
        __('Name'),
        __('Cells'),
        __('Group'),
        __('Favorite'),
        __('Full screen'),
    ];
    if ($manageDashboards === 1) {
        $columns[] = 'copy';
        $columns[] = 'delete';
        $column_names[] = __('Copy');
        $column_names[] = __('Delete');
    }

    ui_print_datatable(
        [
            'id'                  => $id_table,
            'class'               => 'info_table',
            'style'               => 'width: 100%',
            'columns'             => $columns,
            'column_names'        => $column_names,
            'ajax_url'            => 'include/ajax/dashboard.ajax',
            'ajax_data'           => [
                'method'           => 'draw',
                'urlDashboard'     => $urlDashboard,
                'manageDashboards' => $manageDashboards,
            ],
            'default_pagination'  => $config['block_size'],
            'no_sortable_columns' => [],
            'order'               => [
                'field'     => 'name',
                'direction' => 'desc',
            ],
            'search_button_class' => 'sub filter float-right',
            'form'                => [
                'inputs' => [
                    [
                        'label' => __('Free search'),
                        'type'  => 'text',
                        'class' => 'w400px',
                        'id'    => 'free_search',
                        'name'  => 'free_search',
                    ],
                ],
            ],
            'filter_main_class'   => 'box-flat white_table_graph fixed_filter_bar ',
            'csv'                 => false,
        ]
    );
}

$input_button = '';
if ($writeDashboards === 1) {
    $text = __('Create a new dashboard');

    // Button for display modal options dashboard.
    $onclick = 'show_option_dialog('.json_encode(
        [
            'title'      => $text,
            'btn_text'   => __('Ok'),
            'btn_cancel' => __('Cancel'),
            'url'        => $ajaxController,
            'url_ajax'   => ui_get_full_url('ajax.php'),
        ]
    );
    $onclick .= ')';

    $input_button = html_print_button(
        __('New dashboard'),
        '',
        false,
        $onclick,
        ['icon' => 'add'],
        true
    );

    $output .= '</div>';

    echo $output;

    // Div for modal update dashboard.
    echo '<div id="modal-update-dashboard" class="invisible"></div>';
}

html_print_action_buttons(
    $input_button,
    [
        'type'          => 'form_action',
        'right_content' => $tablePagination,
    ]
);

ui_require_javascript_file('pandora_dashboards');
