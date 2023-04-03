<?php
/**
 * Dashboards View header dashboard Pandora FMS Console
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

global $config;

$output = '<div id="dashboard-controls-slides">';

// Normal view button.
$output .= '<div>';
$normalQuery = http_build_query(['dashboardId' => $dashboardId]);
$output .= '<a href="'.$url.'&'.$normalQuery.'">';
$output .= html_print_image(
    'images/normal_screen.png',
    true,
    [
        'title' => __('Exit fullscreen'),
        'class' => 'invert_filter',
    ]
);
$output .= '</a>';
$output .= '</div>';


$next_slides_url = '';
$prev_slides_url = '';

if ($cellModeSlides === 0) {
    $index = 0;
    $items_num = count($slidesIds);
    $first_index = 0;
    $last_index = ($items_num - 1);
    foreach ($slidesIds as $key => $id) {
        if ($dashboardId === (int) $id) {
            $index = $key;
            break;
        }
    }

    if ($index === $first_index && $index === $last_index) {
        $next_id = $dashboardId;
        $prev_id = $dashboardId;
    } else if ($index === $first_index) {
        $prev_id = $slidesIds[$last_index];
        $next_id = $slidesIds[($index + 1)];
    } else if ($index === $last_index) {
        $prev_id = $slidesIds[($index - 1)];
        $next_id = $slidesIds[$first_index];
    } else {
        $prev_id = $slidesIds[($index - 1)];
        $next_id = $slidesIds[($index + 1)];
    }

    $next_common_query = [
        'dashboardId'    => $next_id,
        'slides'         => 1,
        'slidesIds'      => $slidesIds,
        'pure'           => 1,
        'cellModeSlides' => 0,
    ];
    $prev_common_query = [
        'dashboardId'    => $prev_id,
        'slides'         => 1,
        'slidesIds'      => $slidesIds,
        'pure'           => 1,
        'cellModeSlides' => 0,
    ];

    $cell_slides_query = [
        'dashboardId'    => $dashboardId,
        'slidesIds'      => $slidesIds,
        'pure'           => 1,
        'slides'         => 1,
        'cellModeSlides' => 1,
    ];

    $name = $dashboard['name'];
} else {
    // Cells slideshow mode.
    $prev_cell_id = null;
    $next_cell_id = null;

    // This mode is like the slides mode, but with cells.
    $cell_id_found = false;
    foreach ($cells as $cell) {
        if ($cell_id_found === true) {
            $next_cell_id = (int) $cell['id'];
            break;
        } else if ($cellId === (int) $cell['id']) {
            $cell_id_found = true;
        } else {
            $prev_cell_id = (int) $cell['id'];
        }
    }

    if (isset($next_cell_id) === true) {
        $next_id = $next_cell_id;
    } else {
        if ((int) $cellId !== (int) $cells[0]['id']) {
            $next_id = $cells[0]['id'];
        } else {
            $next_id = $cells[1]['id'];
        }
    }

    $next_common_query = [
        'dashboardId'    => $dashboardId,
        'cellId'         => $next_id,
        'pure'           => 1,
        'slides'         => 1,
        'cellModeSlides' => 1,
        'slidesIds'      => $slidesIds,
    ];
    $prev_id = (isset($prev_cell_id) === true) ? $prev_cell_id : (int) $cells[(count($cells) - 1)]['id'];
    $prev_common_query = [
        'dashboardId'    => $dashboardId,
        'cellId'         => $prev_id,
        'pure'           => 1,
        'slides'         => 1,
        'cellModeSlides' => 1,
        'slidesIds'      => $slidesIds,
    ];

    $cell_slides_query = [
        'dashboardId'    => $dashboardId,
        'pure'           => 1,
        'slides'         => 1,
        'cellModeSlides' => 0,
        'slidesIds'      => $slidesIds,
    ];

    $cells = array_reduce(
        $cells,
        function ($carry, $item) {
            $carry[$item['id']] = $item;
            return $carry;
        }
    );

    $options = json_decode($cells[$cellId]['options'], true);

    $name = '';
    if (isset($options) === true) {
        $name = $options['title'];
    }
}

$next_slides_url = $url.'&'.http_build_query($next_common_query);
$prev_slides_url = $url.'&'.http_build_query($prev_common_query);
$cell_slides_url = $url.'&'.http_build_query($cell_slides_query);

// Auto refresh control.
$output .= '<div id="dashboard-slides-form-countdown">';
$output .= '<div class="dashboard-refr">';
$output .= '<div class="dashboard-countdown"></div>';
$output .= '<form id="refr-form" method="POST" action="'.$next_slides_url.'">';
$output .= '<b>'.__('Change every').':</b>';
$output .= html_print_select(
    get_refresh_time_array(),
    'refr',
    $refr,
    '',
    '',
    0,
    true,
    false,
    false
);
$output .= '</form>';
$output .= '</div>';
$output .= '</div>';

// Prev slides button.
$output .= '<div>';
$output .= '<a id="prev-slide" href="'.$prev_slides_url.'">';
$output .= html_print_image(
    'images/control_prev.png',
    true,
    ['title' => __('Previous')]
);
$output .= '</a>';
$output .= '</div>';

// Stop slides button.
$stop_slides_url = http_build_query(
    ['dashboardId' => $dashboardId]
);

$output .= '<div>';
$output .= '<a href="'.$url.'&'.$stop_slides_url.'">';
$output .= html_print_image(
    'images/control_stop.png',
    true,
    ['title' => __('Stop')]
);
$output .= '</a>';
$output .= '</div>';

// Pause slides button.
$output .= '<div>';
$output .= '<a id="pause-btn" href="javascript:;">';
$output .= html_print_image(
    'images/control_pause.png',
    true,
    ['title' => __('Pause')]
);
$output .= '</a>';
$output .= '</div>';

// Next slides button.
$output .= '<div>';
$output .= '<a id="next-slide" href="'.$next_slides_url.'">';
$output .= html_print_image(
    'images/control_next.png',
    true,
    ['title' => __('Next')]
);
$output .= '</a>';
$output .= '</div>';

// Cell slides button view.
$output .= '<div class="dashboard-mode">';
$output .= '<a id="cell-slides-btn" href="'.$cell_slides_url.'">';
if ($cellModeSlides === 0) {
    $output .= html_print_image(
        'images/visual_console.png',
        true,
        ['title' => __('Boxed mode')]
    );
    $msg_tooltip = __('This mode will show the dashboard with all the widgets in the screen. Click to change to single screen mode.');
} else {
    $output .= html_print_image(
        'images/dashboard.png',
        true,
        ['title' => __('Single screen')]
    );
    $msg_tooltip = __('This mode will show each widget in a screen, rotating between elements in each dashboard. Click to change to boxed mode.');
}

$output .= '</a>';
$output .= ui_print_help_tip(
    $msg_tooltip,
    true
);

$output .= '</div>';

// Dashboard name.
$output .= '<div id="dashboard-slides-name">';
$output .= '<div class="dashboard-title"><b>'.$name.'</b></div>';
$output .= '</div>';

$output .= '</div>';

echo $output;
