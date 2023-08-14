<?php
/**
 * ITSM View dashboard
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


/**
 * Draw chart.
 *
 * @param string $title Title.
 * @param array  $data  Data for chart.
 *
 * @return string Output.
 */
function draw_graph(string $title, ?array $data): string
{
    global $config;
    if (is_array($data) === false) {
        return 'N/A';
    }

    $water_mark = [];

    $output = '<div class="white_box pdd_15px">';
    $output .= '<span class="breadcrumbs-title">'.$title.'</span>';
    $labels = array_keys($data);
    $options = [
        'width'     => 320,
        'height'    => 200,
        'waterMark' => $water_mark,
        'legend'    => [
            'display'  => true,
            'position' => 'right',
            'align'    => 'center',
        ],
        'labels'    => $labels,
    ];

    $output .= '<div style="width:inherit;margin: 0 auto;">';
    $output .= pie_graph(
        array_values($data),
        $options
    );
    $output .= '</div>';
    $output .= '</div>';

    return $output;
}


// Header tabs.
ui_print_standard_header(
    __('ITSM Dashboard'),
    '',
    false,
    'ITSM_tab',
    false,
    $headerTabs,
    [
        [
            'link'  => 'index.php?sec=ITSM&sec2=operation/ITSM/itsm',
            'label' => __('ITSM'),
        ],
        [
            'link'  => 'index.php?sec=ITSM&sec2=operation/ITSM/itsm',
            'label' => __('ITSM Dashboard'),
        ],
    ]
);

if (empty($error) === false) {
    ui_print_error_message($error);
}

if (empty($incidencesByStatus) === true) {
    ui_print_info_message(
        [
            'no_close' => true,
            'message'  => __('Not found incidences'),
        ]
    );
} else {
    $output = '<div class="container-statistics">';
    $output .= draw_graph(__('Incidents by status'), $incidencesByStatus);
    $output .= draw_graph(__('Incidents by priority'), $incidencesByPriorities);
    $output .= draw_graph(__('Incidents by group'), $incidencesByGroups);
    $output .= draw_graph(__('Incidents by user'), $incidencesByOwners);
    $output .= '</div>';
    echo $output;
}
