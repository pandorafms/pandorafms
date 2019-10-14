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
require_once 'include/functions_config.php';
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


function confWetty()
{
    global $config;

    if (get_parameter('wetty_ip')) {
        config_update_value('wetty_ip', get_parameter('wetty_ip'));
    } else {
        if (!isset($config['wetty_ip'])) {
            $config['wetty_ip'] = $_SERVER['SERVER_ADDR'];
        }
    }

    if ($config['wetty_ip'] == '127.0.0.1') {
        config_update_value('wetty_ip', $_SERVER['SERVER_ADDR']);
    }

    if (get_parameter('wetty_port')) {
        config_update_value('wetty_port', get_parameter('wetty_port'));
    } else {
        if (!isset($config['wetty_port'])) {
            $config['wetty_port'] = '3000';
        }
    }

    $buttons['maps'] = [
        'active' => false,
        'text'   => '<a href="index.php?login=1&extension_in_menu=gextensions&sec=gextensions&sec2=extensions/wetty">'.html_print_image('images/groups_small/application_osx_terminal.png', true, ['title' => __('Wetty')]).'</a>',
    ];

    ui_print_page_header(__('Wetty'), 'images/extensions.png', false, '', true, $buttons);

    $row = 0;

    echo '<form id="form_setup" action="'.$_SERVER['REQUEST_URI'].'" method="post">';

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

    $table->head[0] = __('Wetty Configuration');
    $table->head[1] = __('');

    $table->data[$row][0] = __('Wetty ip address connection');
    $table->data[$row][1] = html_print_input_text('wetty_ip', $config['wetty_ip'], '', 25, 25, true);
    $row++;

    $table->data[$row][0] = __('Wetty port connection');
    $table->data[$row][1] = html_print_input_text('wetty_port', $config['wetty_port'], '', 25, 25, true);
    $row++;

    array_push($table->data, $data);

    html_print_table($table);

    echo '<div class="action-buttons" style="width: '.$table_other->width.'">';
    html_print_submit_button(__('Update'), 'update_button', false, 'class="sub upd"');
    echo '</div>';

    echo '</form>';

}


extensions_add_godmode_function('confWetty');

