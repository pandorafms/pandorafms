<?php
/**
 * Settings for Pandora Websocket engine.
 *
 * @category   UI file
 * @package    Pandora FMS
 * @subpackage Community
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

global $config;

$url = ui_get_full_url(
    'index.php?sec=gsetup&sec2=godmode/setup/setup&amp;section=quickshell&amp;pure='.$config['pure']
);

echo '<form class="max_floating_element_size" id="form_setup" method="post" action="'.$url.'">';

if (function_exists('quickShellSettings') === true) {
    quickShellSettings();
}

$action_btns = html_print_submit_button(
    __('Update'),
    'update_button',
    false,
    [ 'icon' => 'update' ],
    true
);

html_print_action_buttons(
    $action_btns
);

echo '</form>';
