<?php
/**
 * Dashboards View tips in modal
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
$output .= '<script>var idTips = ['.$id.'];</script>';
$output .= '<div class="window">';
$output .= '<div class="tips_header">';
$output .= '<p class="title">'.__('Hello! These are the tips of the day.').'</p>';
$output .= '<p>'.html_print_checkbox(
    'show_tips_startup',
    true,
    true,
    true,
    false,
    'show_tips_startup(this)',
    false,
    '',
    ($preview === true) ? '' : 'checkbox_tips_startup'
).__('Show usage tips at startup').'</p>';
$output .= '</div>';
$output .= '<div class="carousel '.((empty($files) === true && empty($files64) === true) ? 'invisible' : '').'">';
$output .= '<div class="images">';

if ($files !== false) {
    if ($preview === true) {
        foreach ($files as $key => $file) {
            $output .= html_print_image($file, true, ['class' => 'main_menu_icon']);
        }
    } else {
        foreach ($files as $key => $file) {
            $output .= html_print_image($file['path'].$file['filename'], true, ['class' => 'main_menu_icon']);
        }
    }
}

if ($files64 !== false) {
    foreach ($files64 as $key => $file) {
        $output .= '<img src="'.$file.'" />';
    }
}

$output .= '</div>';
$output .= '</div>';

$output .= '<div class="description">';
$output .= '<h2 id="title_tip">'.$title.'</h2>';
$output .= '<p id="text_tip">';
$output .= $text;
$output .= '</p>';

$link_class = 'invisible';
if (empty($url) === false && $url !== '') {
    $link_class = '';
}

$output .= '<a href="'.$url.'" class="'.$link_class.'" target="_blank" id="url_tip">'.__('See more info').'</a>';

$output .= '</div>';

$output .= '<div class="ui-dialog-buttonset">';

$output .= html_print_button(
    __('Maybe later'),
    '',
    false,
    '',
    [
        'onclick' => 'close_dialog()',
        'class'   => 'secondary mini',
    ],
    true
);
$output .= '<div class="counter-tips">';
$output .= html_print_image('images/arrow-left-grey.png', true, ['class' => 'arrow_counter']);
$output .= html_print_image('images/arrow-right-grey.png', true, ['class' => 'arrow_counter']);
$output .= '</div>';
if ($preview === true) {
    $output .= html_print_button(
        __('Ok'),
        'next_tip',
        false,
        '',
        [
            'onclick' => 'close_dialog()',
            'class'   => 'mini',
        ],
        true
    );
} else {
    $output .= html_print_button(
        __('Ok'),
        'next_tip',
        false,
        '',
        [
            'onclick' => 'next_tip()',
            'class'   => ($totalTips === '1') ? 'mini hide-button' : 'mini',
        ],
        true
    );
}

$output .= '</div>';
$output .= '</div>';
echo $output;
