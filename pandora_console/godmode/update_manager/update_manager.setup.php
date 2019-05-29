<?php
/**
 * Update manager client options.
 *
 * @category   Update Manager
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
 * Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
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

// Begin.
global $config;

require_once __DIR__.'/../../include/functions_users.php';

check_login();

if (! check_acl($config['id_user'], 0, 'PM') && ! is_user_admin($config['id_user'])) {
    db_pandora_audit('ACL Violation', 'Trying to access Setup Management');
    include 'general/noaccess.php';
    return;
}

$identification_reminder = get_parameter('identification_reminder', 1);
$action_update_url_update_manager = (bool) get_parameter(
    'action_update_url_update_manager',
    0
);

if (users_is_admin()) {
    $update_manager_disconnect = get_parameter(
        'um_disconnect_console',
        0
    );

    if ($update_manager_disconnect) {
        config_update_value(
            'pandora_uid',
            'OFFLINE'
        );
    }
}

if (!$action_update_url_update_manager) {
    $url_update_manager = get_parameter('url_update_manager', $config['url_update_manager']);
    $update_manager_proxy_server = get_parameter(
        'update_manager_proxy_server',
        $config['update_manager_proxy_server']
    );
    $update_manager_proxy_port = get_parameter(
        'update_manager_proxy_port',
        $config['update_manager_proxy_port']
    );
    $update_manager_proxy_user = get_parameter(
        'update_manager_proxy_user',
        $config['update_manager_proxy_user']
    );
    $update_manager_proxy_password = get_parameter(
        'update_manager_proxy_password',
        $config['update_manager_proxy_password']
    );


    if ($action_update_url_update_manager) {
        $result = config_update_value(
            'url_update_manager',
            $url_update_manager
        );
        if ($result) {
            $result = config_update_value(
                'update_manager_proxy_server',
                $update_manager_proxy_server
            );
        }

        if ($result) {
            $result = config_update_value(
                'update_manager_proxy_port',
                $update_manager_proxy_port
            );
        }

        if ($result) {
            $result = config_update_value(
                'update_manager_proxy_user',
                $update_manager_proxy_user
            );
        }

        if ($result) {
            $result = config_update_value(
                'update_manager_proxy_password',
                $update_manager_proxy_password
            );
        }

        if ($result && license_free()) {
            $result = config_update_value(
                'identification_reminder',
                $identification_reminder
            );
        }

        ui_print_result_message(
            $result,
            __('Succesful Update the url config vars.'),
            __('Unsuccesful Update the url config vars.')
        );
    }
} else {
    $url_update_manager = get_parameter('url_update_manager', '');
    $update_manager_proxy_server = get_parameter('update_manager_proxy_server', '');
    $update_manager_proxy_port = get_parameter('update_manager_proxy_port', '');
    $update_manager_proxy_user = get_parameter('update_manager_proxy_user', '');
    $update_manager_proxy_password = get_parameter('update_manager_proxy_password', '');


    if ($action_update_url_update_manager) {
        $result = config_update_value(
            'url_update_manager',
            $url_update_manager
        );
        if ($result) {
            $result = config_update_value(
                'update_manager_proxy_server',
                $update_manager_proxy_server
            );
        }

        if ($result) {
            $result = config_update_value(
                'update_manager_proxy_port',
                $update_manager_proxy_port
            );
        }

        if ($result) {
            $result = config_update_value(
                'update_manager_proxy_user',
                $update_manager_proxy_user
            );
        }

        if ($result) {
            $result = config_update_value(
                'update_manager_proxy_password',
                $update_manager_proxy_password
            );
        }

        if ($result && license_free()) {
            $result = config_update_value('identification_reminder', $identification_reminder);
        }

        ui_print_result_message(
            $result,
            __('Succesful Update the url config vars.'),
            __('Unsuccesful Update the url config vars.')
        );
    }
}

echo '<form method="post" action="index.php?sec=gsetup&sec2=godmode/update_manager/update_manager&tab=setup">';

$table = new stdClass();
$table->width = '100%';
$table->class = 'databox filters';

$table->style[0] = 'font-weight: bolder;width:250px';

$table->data[0][0] = __('URL update manager:');
$table->data[0][1] = html_print_input_text(
    'url_update_manager',
    $url_update_manager,
    __('URL update manager'),
    80,
    60,
    true
);

$table->data[1][0] = __('Proxy server:');
$table->data[1][1] = html_print_input_text(
    'update_manager_proxy_server',
    $update_manager_proxy_server,
    __('Proxy server'),
    80,
    60,
    true
);

$table->data[2][0] = __('Proxy port:');
$table->data[2][1] = html_print_input_text(
    'update_manager_proxy_port',
    $update_manager_proxy_port,
    __('Proxy port'),
    80,
    60,
    true
);

$table->data[3][0] = __('Proxy user:');
$table->data[3][1] = html_print_input_text(
    'update_manager_proxy_user',
    $update_manager_proxy_user,
    __('Proxy user'),
    80,
    60,
    true
);

$table->data[4][0] = __('Proxy password:');
$table->data[4][1] = html_print_input_password(
    'update_manager_proxy_password',
    $update_manager_proxy_password,
    __('Proxy password'),
    80,
    60,
    true
);


$table->data[5][0] = __('Registration ID:');
$table->data[5][1] = '<i>'.$config['pandora_uid'].'</i>';

if (update_manager_verify_registration() === true && users_is_admin()) {
    $table->data[6][0] = __('Cancel registration:');
    $table->data[6][1] = '<a href="';
    $table->data[6][1] .= ui_get_full_url(
        'index.php?sec=messages&sec2=godmode/update_manager/update_manager&tab=setup&um_disconnect_console=1'
    );
    $table->data[6][1] .= '" onclick="if(confirm(\'Are you sure?\')) {return true;} else { return false; }">'.__('Unregister').'</a>';
}



if (license_free()) {
    $config['identification_reminder'] = isset($config['identification_reminder']) ? $config['identification_reminder'] : 1;
    $table->data[7][0] = __('Pandora FMS community reminder').ui_print_help_tip(__('Every 8 days, a message is displayed to admin users to remember to register this Pandora instance'), true);
    $table->data[7][1] = __('Yes').'&nbsp;&nbsp;&nbsp;'.html_print_radio_button('identification_reminder', 1, '', $config['identification_reminder'], true).'&nbsp;&nbsp;';
    $table->data[7][1] .= __('No').'&nbsp;&nbsp;&nbsp;'.html_print_radio_button('identification_reminder', 0, '', $config['identification_reminder'], true);
}

html_print_input_hidden('action_update_url_update_manager', 1);
html_print_table($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
html_print_submit_button(
    __('Update'),
    'update_button',
    false,
    'class="sub upd"'
);
echo '</div>';
echo '</form>';
