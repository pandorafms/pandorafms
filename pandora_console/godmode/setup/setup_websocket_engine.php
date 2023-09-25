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
    'index.php?sec=gsetup&sec2=godmode/setup/setup&amp;section=websocket_engine&amp;pure='.$config['pure']
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

echo '<script>';
echo 'var server_addr = "'.$_SERVER['SERVER_ADDR'].'";';
$handle_test_js = "var handleTest = function (event) {
    
    var ws_proxy_url = $('input#text-ws_proxy_url').val();
    var ws_port = $('input#text-ws_port').val();
    var httpsEnabled = window.location.protocol == 'https' ? true : false;
    if (ws_proxy_url == '') {
        ws_url = (httpsEnabled ? 'wss://' : 'ws://')  + window.location.host + ':' + ws_port;    
    } else {
        ws_url = ws_proxy_url;
    }

    var showLoadingImage = function () {
        $('#button-test-gotty').children('div').attr('class', 'subIcon cog rotation secondary mini');
    }

    var showSuccessImage = function () {
        $('#button-test-gotty').children('div').attr('class', 'subIcon tick secondary mini');
    }

    var showFailureImage = function () {
        $('#button-test-gotty').children('div').attr('class', 'subIcon fail secondary mini');
    }

    var hideMessage = function () {
        $('span#test-gotty-message').hide();
    }
    var showMessage = function () {
        $('span#test-gotty-message').show();
    }
    var changeTestMessage = function (message) {
        $('span#test-gotty-message').text(message);
    }

    var errorMessage = '".__('WebService engine has not been started, please check documentation.')."';


    hideMessage();
    showLoadingImage();
    
    var ws = new WebSocket(ws_url);
    // Catch errors.

    ws.onerror = () => {
        showFailureImage();
        changeTestMessage(errorMessage);
        showMessage();
        ws.close();
    };
      
    ws.onopen = () => {
        showSuccessImage();
        hideMessage();   
        ws.close();
    };

    ws.onclose = (event) => {
        changeTestMessage(errorMessage);
        hideLoadingImage();
        showMessage();
    };
}";

echo $handle_test_js;
echo '</script>';

