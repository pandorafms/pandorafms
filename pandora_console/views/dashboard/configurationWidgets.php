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

// Includes.
require_once $config['homedir'].'/include/class/HTML.class.php';
ui_require_javascript_file('tinymce', 'vendor/tinymce/tinymce/', true);
ui_require_javascript_file('pandora', 'include/javascript/', true);

$output = '';

$form = [
    'action'   => '#',
    'method'   => 'POST',
    'id'       => 'form-config-widget',
    'onsubmit' => 'return false;',
    'class'    => 'modal-dashboard',
    'enctype'  => 'multipart/form-data',
    'extra'    => 'novalidate',
];

$js .= ' tinymce.init({
    selector: "#textarea_text",
    plugins: "preview, searchreplace, table, nonbreaking, link, image",
    promotion: false,
    branding: false,
    setup: function (editor) {
        editor.on("change", function () {
            tinymce.triggerSave();
        })
    }
});';

HTML::printForm(
    [
        'form'   => $form,
        'blocks' => $blocks,
        'inputs' => $htmlInputs,
        'js'     => $js,
    ]
);

echo $output;
