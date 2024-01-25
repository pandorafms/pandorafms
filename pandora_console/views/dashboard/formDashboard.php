<?php
/**
 * Dashboards View From Dashboard Pandora FMS Console
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

if (empty($arrayDashboard) === true) {
    $arrayDashboard['name'] = 'Default';
    $arrayDashboard['id_user'] = '';
    $private = 0;
    $arrayDashboard['id_group'] = null;
    $arrayDashboard['active'] = 0;
    $arrayDashboard['cells_slideshow'] = 0;
} else {
    $private = 1;
    if (empty($arrayDashboard['id_user']) === true) {
        $private = 0;
    }
}

$return_all_group = false;

if (users_can_manage_group_all('RW') === true) {
    $return_all_group = true;
}

$dataQuery = ['dashboardId' => $dashboardId];

$url = ui_get_full_url(
    'index.php?sec=reporting&sec2=operation/dashboard/dashboard'
);

$url .= '&'.http_build_query($dataQuery);
$form = [
    'id'       => 'form-update-dashboard',
    'action'   => $url,
    'onsubmit' => 'return false;',
    'class'    => 'filter-list-adv',
    'enctype'  => 'multipart/form-data',
    'method'   => 'POST',
];

$inputs = [
    [
        'arguments' => [
            'type'  => 'hidden',
            'name'  => 'dashboardId',
            'value' => $dashboardId,
        ],
    ],
    [
        'label'     => __('Name'),
        'arguments' => [
            'type'      => 'text',
            'name'      => 'name',
            'value'     => $arrayDashboard['name'],
            'size'      => '',
            'maxlength' => 35,
        ],
    ],
    [
        'block_id'      => 'group_form',
        'direct'        => 1,
        'hidden'        => $private,
        'block_content' => [
            [
                'label'     => __('Group'),
                'arguments' => [
                    'name'           => 'id_group',
                    'id'             => 'id_group',
                    'type'           => 'select_groups',
                    'returnAllGroup' => $return_all_group,
                    'selected'       => $arrayDashboard['id_group'],
                    'return'         => true,
                    'required'       => true,
                ],
            ],
        ],
    ],
    [
        'label'     => __('Date range'),
        'arguments' => [
            'name'     => 'date_range',
            'id'       => 'date_range',
            'type'     => 'switch',
            'value'    => $arrayDashboard['date_range'],
            'onchange' => 'handle_date_range(this)',
        ],
    ],
    [
        'label'     => __('Select range'),
        'style'     => 'display: none;',
        'class'     => 'row_date_range',
        'arguments' => [
            'name'      => 'range',
            'id'        => 'range',
            'selected'  => ($arrayDashboard['date_from'] === '0' && $arrayDashboard['date_to'] === '0') ? 300 : 'chose_range',
            'type'      => 'date_range',
            'date_init' => date('Y/m/d', $arrayDashboard['date_from']),
            'time_init' => date('H:i:s', $arrayDashboard['date_from']),
            'date_end'  => date('Y/m/d', $arrayDashboard['date_to']),
            'time_end'  => date('H:i:s', $arrayDashboard['date_to']),
        ],
    ],
    [
        'block_id'      => 'private',
        'direct'        => 1,
        'block_content' => [
            [
                'label'     => __('Private'),
                'arguments' => [
                    'name'    => 'private',
                    'id'      => 'private',
                    'type'    => 'switch',
                    'value'   => $private,
                    'onclick' => 'showGroup()',
                ],
            ],
        ],
    ],
    [
        'label'     => __('Favourite'),
        'arguments' => [
            'name'  => 'favourite',
            'id'    => 'favourite',
            'type'  => 'switch',
            'value' => $arrayDashboard['active'],
        ],
    ],
];

HTML::printForm(
    [
        'form'   => $form,
        'inputs' => $inputs,
    ]
);

?>

<script>
function handle_date_range(element){
    if(element.checked) {
        $(".row_date_range").show();
        var def_state_range = $('#range_range').is(':visible');
        var def_state_default = $('#range_default').is(':visible');
        var def_state_extend = $('#range_extend').is(':visible');
        if (
            def_state_range === false
            && def_state_default === false
            && def_state_extend === false
            && $('#range').val() !== 'chose_range'
        ) {
            $('#range_default').show();
        } else if ($('#range').val() === 'chose_range') {
            $('#range_range').show();
        }
    } else {
        $(".row_date_range").hide();
    }
}
var date_range = $("#date_range")[0];
handle_date_range(date_range);
</script>