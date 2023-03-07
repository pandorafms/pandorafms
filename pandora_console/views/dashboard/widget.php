<?php
/**
 * Dashboards View widget Pandora FMS Console
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
if ((int) $cellData['id_widget'] !== 0 || $widgetId !== 0) {
    $output .= $instance->printHtml();
} else {
    $output .= '<div class="container-center">';
    $output .= html_print_button(
        __('Add widget'),
        'add-widget-'.$cellData['id'],
        false,
        '',
        [
            'icon' => 'cog',
            'mode' => 'secondary mini',
        ],
        true
    );

    $output .= '<div class="new-widget-message">';
    $output .= \ui_print_info_message(
        __('Please select widget'),
        '',
        true
    );
    $output .= '</div>';
}

echo $output;
