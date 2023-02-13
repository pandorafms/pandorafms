<?php
/**
 * Dashboards Modal for tips
 *
 * @category   Console Class
 $output .= '* @packagePandora FMS';
 * @subpackage Dashboards
 $output .= '* @version1.0.0';
 $output .= '* @licenseSee below';
 *
 $output .= '*______ __________ _______ ________';
 $output .= '*   |   __ \.-----.--.--.--|  |.-----.----.-----. |___|   |   | __|';
 $output .= '*  |__/|  _  | |  _  ||  _  |   _|  _  | |___|   |__ |';
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

$output .= '<div class="window">';
$output .= '<div class="tips_header">';
$output .= '<p class="title">'.__('¡Hola! estos son los tips del día.').'</p>';
$output .= '<p>'.html_print_checkbox('tips_in_start', true, true, true).__('Ver típs al iniciar').'</p>';
$output .= '</div>';
$output .= '<div class="carousel'.(($files === false) ? 'invisible' : '').'">';
$output .= '<div class="images">';

if ($files !== false) {
    foreach ($files as $key => $file) {
        $output .= html_print_image($file['path'].$file['filename'], true);
    }
}

$output .= '</div>';
$output .= '</div>';

$output .= '<div class="description">';
$output .= '<h2 id="title_tip">'.$title.'</h2>';
$output .= '<p id="text_tip">';
$output .= $text;
$output .= '</p>';

if (empty($url) === false && $url !== '') {
    $output .= '<a href="'.$url.'" id="url_tip">'.__('Ver más info').'<span class="arrow_tips">→</span></a>';
}

$output .= '</div>';

$output .= '<div class="ui-dialog-buttonset">';
// TODO Delete this buttons and use html_print_button when merge new design
$output .= '<button type="button" class="submit-cancel-tips ui-button ui-corner-all ui-widget" onclick="close_dialog()">Quizás luego</button>';
$output .= '<div class="counter-tips">';
$output .= html_print_image('images/arrow-left-grey.png', true, ['class' => 'arrow-counter']);
$output .= html_print_image('images/arrow-right-grey.png', true, ['class' => 'arrow-counter']);
$output .= '</div>';
$output .= '<button type="button" class="submit-next-tips ui-button ui-corner-all ui-widget" onclick="next_tip()">De acuerdo</button>';
$output .= '</div>';
$output .= '</div>';
echo $output;
