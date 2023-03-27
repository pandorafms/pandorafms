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

if (empty($dashboards) === true) {
    ui_print_info_message(
        [
            'no_close' => true,
            'message'  => __('There are no dashboards defined.'),
        ]
    );
} else {
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

    \html_print_table($table);
    $tablePagination = \ui_pagination(
        $count,
        false,
        $offset,
        0,
        true,
        'offset',
        false,
        ''
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
