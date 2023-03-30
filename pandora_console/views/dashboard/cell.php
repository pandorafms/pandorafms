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

$output = '';
if ($redraw === false) {
    $output .= '<div>';
    $output .= '<div id="widget-'.$cellData['id'].'" class="grid-stack-item-content">';
}

$output .= '<div class="header-widget">';
$output .= '<div>';

if ((int) $cellData['id_widget'] !== 0) {
    $options = json_decode($cellData['options'], true);
    $output .= $options['title'];
} else {
    $output .= __('New widget');
}

$output .= '</div>';
$output .= '<div class="header-options">';

if ($manageDashboards !== 0 || $writeDashboards !== 0) {
    if ((int) $cellData['id_widget'] !== 0) {
        $output .= '<a id="configure-widget-'.$cellData['id'].'" class="">';
        $output .= html_print_image(
            'images/configuration@svg.svg',
            true,
            [
                'width' => '16px',
                'title' => __('Configure widget'),
                'class' => 'invert_filter',
            ]
        );
        $output .= '</a> ';
    }

    $output .= '<a id="delete-widget-'.$cellData['id'].'" class="">';
    $output .= html_print_image(
        'images/delete.svg',
        true,
        [
            'width' => '16px',
            'title' => __('Delete widget'),
            'class' => 'invert_filter',
        ]
    );
    $output .= '</a>';
}

$output .= '</div>';
$output .= '</div>';
if (empty($options['background']) === true) {
    if ($config['style'] === 'pandora') {
        $options['background'] = '#ffffff';
    }

    if ($config['style'] === 'pandora_black' && !is_metaconsole()) {
        $options['background'] = '#222222';
    }
} else if ($options['background'] === '#ffffff'
    && $config['style'] === 'pandora_black'
    && !is_metaconsole()
) {
    $options['background'] = '#222222';
} else if ($options['background'] === '#222222'
    && $config['style'] === 'pandora'
) {
    $options['background'] = '#ffffff';
}

if ((int) $cellData['id_widget'] !== 0) {
    $style = 'style="background-color:'.$options['background'].';"';
    $output .= '<div class="content-widget" '.$style.'>';
} else {
    $output .= '<div class="content-widget">';
}

$output .= '</div>';

if ($redraw === false) {
    $output .= '</div>';
    $output .= '</div>';
}

echo $output;
