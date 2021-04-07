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

$output = '';

$output .= ui_require_javascript_file(
    'tiny_mce',
    'include/javascript/tiny_mce/',
    true
);

$form = [
    'action'   => '#',
    'method'   => 'POST',
    'id'       => 'form-config-widget',
    'onsubmit' => 'return false;',
    'class'    => 'modal-dashboard',
    'enctype'  => 'multipart/form-data',
    'extra'    => 'novalidate',
];

HTML::printForm(
    [
        'form'   => $form,
        'inputs' => $htmlInputs,
        'js'     => $js,
    ]
);

$output .= '<script>';
$output .= 'dashboardInitTinyMce("'.ui_get_full_url(false, false, false, false).'")';
$output .= '</script>';

echo $output;
