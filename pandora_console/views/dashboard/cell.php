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

$output = '';
if ($redraw === false) {
    $output .= '<div>';
    $output .= '<div id="widget-'.$cellData['id'].'" class="grid-stack-item-content">';
}

$output .= '<div class="header-widget">';
$output .= '<div>';
if (isset($cellData['options']) === true) {
    $options = json_decode($cellData['options'], true);
} else {
    $options = [];
}

if ($cellData['id_widget'] !== '0') {
    $output .= $options['title'];
} else {
    $output .= __('New widget');
}

$output .= '</div>';
$output .= '<div class="header-options">';
if ($manageDashboards !== 0 || $writeDashboards !== 0) {
    if ((int) $cellData['id_widget'] !== 0) {
        $count_options = count(json_decode($cellData['options'], true));
        $invisible = '';
        if ($count_options <= 2 && $options['copy'] == 0) {
            $invisible = 'invisible';
        }

        $output .= '<a id="copy-widget-'.$cellData['id'].'" class="'.$invisible.'" >';
        $output .= html_print_image(
            'images/copy.svg',
            true,
            [
                'width' => '16px',
                'title' => __('Copy widget'),
                'class' => 'invert_filter',
            ]
        );
        $output .= '</a> ';

        $output .= '<a id="configure-widget-'.$cellData['id'].'" class="">';
        $widget_description = db_get_value_sql('SELECT description FROM twidget WHERE id ='.$cellData['id_widget']);
        $output .= html_print_input_hidden('widget_name_'.$cellData['id'], $widget_description, true);
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
