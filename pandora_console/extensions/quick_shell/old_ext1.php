<?php
// ______                 __                     _______ _______ _______
// |   __ \.---.-.-----.--|  |.-----.----.---.-. |    ___|   |   |     __|
// |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
// |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
//
// ============================================================================
// Copyright (c) 2007-2018 Artica Soluciones Tecnologicas, http://www.artica.es
// This code is NOT free software. This code is NOT licenced under GPL2 licence
// You cannnot redistribute it without written permission of copyright holder.
// ============================================================================
require_once 'include/functions.php';
require_once 'include/functions_groupview.php';
require_once 'include/auth/mysql.php';

check_login();

if (! check_acl($config['id_user'], 0, 'PM')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access Profile Management'
    );
    include 'general/noaccess.php';
    return;
}


function mainWetty()
{
    global $config;

    if (!isset($config['wetty_ip'])) {
        config_update_value('wetty_ip', $_SERVER['SERVER_ADDR']);
    }

    if ($config['wetty_ip'] == '127.0.0.1') {
        config_update_value('wetty_ip', $_SERVER['SERVER_ADDR']);
    }

    if (!isset($config['wetty_port'])) {
        config_update_value('wetty_port', '3000');
    }

    $buttons['maps'] = [
        'active' => false,
        'text'   => '<a href="index.php?login=1&extension_in_menu=gextensions&sec=gextensions&sec2=extensions/wetty_conf">'.html_print_image('images/setup.png', true, ['title' => __('Wetty settings')]).'</a>',
    ];

    ui_print_page_header(__('Wetty'), 'images/extensions.png', false, '', true, $buttons);

    $table->width = '100%';
    $table->class = 'databox data';
    $table->data = [];
    $table->head = [];
    $table->align = [];
    // $table->align[3] = 'left';
    $table->style = [];
    $table->size = [];
    // $table->size[3] = '10%';
    $table->style[0] = 'font-weight: bold';

    $table->head[0] = __('Wetty');

    // $data[0] = '<iframe scrolling="auto" frameborder="0" width="100%" height="600px" src="http://192.168.70.64:3000/"></iframe>';
    $data[0] = '<iframe scrolling="auto" frameborder="0" width="100%" height="600px" src="http://'.$config['wetty_ip'].':'.$config['wetty_port'].'/"></iframe>';

    // $data[0] .= '<div id="terminal" style="background-color:black;width:100%;height:600px;overflow: hidden;"><div>';
    array_push($table->data, $data);

    html_print_table($table);

}


extensions_add_godmode_menu_option(__('Wetty'), 'AW', 'gextensions', null, 'v1');
extensions_add_godmode_function('mainWetty');
