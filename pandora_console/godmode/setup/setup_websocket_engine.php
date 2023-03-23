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

global $config;

$url = ui_get_full_url(
    'index.php?sec=gsetup&sec2=godmode/setup/setup&amp;section=websocket_engine&amp;pure='.$config['pure']
);

echo '<form class="max_floating_element_size" id="form_setup" method="post" action="'.$url.'">';

echo '<fieldset class="margin-bottom-10">';
echo '<legend>'.__('WebSocket settings').'</legend>';

$t = new StdClass();
$t->data = [];
$t->width = '100%';
$t->class = 'databox filter-table-adv';
$t->data = [];

$t->data[0][] = html_print_label_input_block(
    __('Bind address'),
    html_print_input_text(
        'ws_bind_address',
        $config['ws_bind_address'],
        '',
        30,
        100,
        true
    )
);

$t->data[0][] = html_print_label_input_block(
    __('Bind port'),
    html_print_input_text(
        'ws_port',
        $config['ws_port'],
        '',
        30,
        100,
        true
    )
);

$t->data[1][] = html_print_label_input_block(
    __('WebSocket proxy url'),
    html_print_input_text(
        'ws_proxy_url',
        $config['ws_proxy_url'],
        '',
        30,
        100,
        true
    )
);

html_print_input_hidden('update_config', 1);
html_print_table($t);

echo '</fieldset>';

if (function_exists('quickShellSettings') === true) {
    quickShellSettings();
}

html_print_action_buttons(
    html_print_submit_button(
        __('Update'),
        'update_button',
        false,
        [ 'icon' => 'update' ],
        true
    )
);

echo '</form>';
