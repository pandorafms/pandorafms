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

// Begin.
global $config;

require_once __DIR__.'/../../include/functions_users.php';
require_once __DIR__.'/../../include/functions_update_manager.php';

check_login();

if (! check_acl($config['id_user'], 0, 'PM') && ! is_user_admin($config['id_user'])) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Setup Management'
    );
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
    $url_update_manager = get_parameter(
        'url_update_manager',
        $config['url_update_manager']
    );
    $secure_update_manager = get_parameter(
        'secure_update_manager',
        ($config['secure_update_manager'] ?? 1)
    );

    if ($secure_update_manager === '') {
        $secure_update_manager = 0;
    }

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
    $allow_offline_patches = get_parameter_switch(
        'allow_offline_patches',
        $config['allow_offline_patches']
    );
    $lts_updates = get_parameter_switch(
        'lts_updates',
        $config['lts_updates']
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
                'secure_update_manager',
                $secure_update_manager
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

        if ($result) {
            $result = config_update_value(
                'allow_offline_patches',
                $allow_offline_patches
            );
        }

        if ($result) {
            $result = config_update_value(
                'lts_updates',
                $lts_updates
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
    $secure_update_manager = get_parameter_switch('secure_update_manager', null);
    $update_manager_proxy_server = get_parameter('update_manager_proxy_server', '');
    $update_manager_proxy_port = get_parameter('update_manager_proxy_port', '');
    $update_manager_proxy_user = get_parameter('update_manager_proxy_user', '');
    $update_manager_proxy_password = get_parameter('update_manager_proxy_password', '');
    $allow_offline_patches = get_parameter_switch('allow_offline_patches', false);
    $lts_updates = get_parameter_switch('lts_updates', false);


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
                io_input_password($update_manager_proxy_password)
            );
        }

        if ($result) {
            $result = config_update_value(
                'secure_update_manager',
                io_safe_input($secure_update_manager ?? 0)
            );
        }

        if ($result) {
            $result = config_update_value(
                'allow_offline_patches',
                $allow_offline_patches
            );
        }

        if ($result) {
            $result = config_update_value(
                'lts_updates',
                $lts_updates
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

if ((bool) is_metaconsole() === true) {
    $action = ui_get_full_url(
        'index.php?sec=advanced&sec2=advanced/metasetup&pure=0&tab=update_manager_setup'
    );
} else {
    $action = ui_get_full_url(
        'index.php?sec=gsetup&sec2=godmode/update_manager/update_manager&tab=setup'
    );
}

echo '<form method="post" action="'.$action.'" class="max_floating_element_size">';
html_print_input_hidden('update_config', 1);

$table = new stdClass();
$table->width = '100%';
$table->class = 'databox filters filter-table-adv';
$table->size[0] = '50%';
$table->size[1] = '50%';

$url_update_manager = update_manager_get_url();

$table->data[0][0] = html_print_label_input_block(
    __('Warp Update URL'),
    html_print_input_text(
        'url_update_manager',
        $url_update_manager,
        __('URL update manager'),
        80,
        255,
        true,
        true
    )
);

$table->data[0][1] = html_print_label_input_block(
    __('Use secured Warp Update'),
    html_print_input(
        [
            'type'  => 'switch',
            'name'  => 'secure_update_manager',
            'value' => ($secure_update_manager ?? 1),
        ]
    )
);

$table->data[1][0] = html_print_label_input_block(
    __('Proxy server'),
    html_print_input_text(
        'update_manager_proxy_server',
        $update_manager_proxy_server,
        __('Proxy server'),
        80,
        60,
        true
    )
);

$table->data[1][1] = html_print_label_input_block(
    __('Proxy port'),
    html_print_input_text(
        'update_manager_proxy_port',
        $update_manager_proxy_port,
        __('Proxy port'),
        80,
        60,
        true
    )
);

$table->data[2][0] = html_print_label_input_block(
    __('Proxy user'),
    html_print_input_text(
        'update_manager_proxy_user',
        $update_manager_proxy_user,
        __('Proxy user'),
        80,
        60,
        true
    )
);

$table->data[2][1] = html_print_label_input_block(
    __('Proxy password'),
    html_print_input_password(
        'update_manager_proxy_password',
        $update_manager_proxy_password,
        __('Proxy password'),
        80,
        60,
        true
    )
);

$table->data[3][0] = html_print_label_input_block(
    __('Allow no-consecutive patches'),
    html_print_switch(
        [
            'name'   => 'allow_offline_patches',
            'value'  => $allow_offline_patches,
            'return' => true,
        ]
    )
);

$table->data[3][1] = html_print_label_input_block(
    __('Limit to LTS updates'),
    html_print_switch(
        [
            'name'   => 'lts_updates',
            'value'  => $lts_updates,
            'return' => true,
        ]
    )
);

$table->data[4][0] = html_print_label_input_block(
    __('Registration ID'),
    '<i>'.($config['pandora_uid'] ?? __('Not registred yet')).'</i>'
);

if (update_manager_verify_registration() === true && users_is_admin()) {
    $url = '<a href="';
    if ((bool) is_metaconsole() === true) {
        $url .= ui_get_full_url(
            'index.php?sec=advanced&sec2=advanced/metasetup&pure=0&tab=update_manager_setup&um_disconnect_console=1'
        );
    } else {
        $url .= ui_get_full_url(
            'index.php?sec=messages&sec2=godmode/update_manager/update_manager&tab=setup&um_disconnect_console=1'
        );
    }

    $url .= '" onclick="if(confirm(\'Are you sure?\')) {return true;} else { return false; }">'.__('Unregister').'</a>';

    $table->data[4][1] = html_print_label_input_block(
        __('Cancel registration'),
        $url
    );
}

if (license_free()) {
    $config['identification_reminder'] = isset($config['identification_reminder']) ? $config['identification_reminder'] : 1;

    $table->data[4][1] = html_print_label_input_block(
        __('%s community reminder', get_product_name()).ui_print_help_tip(__('Every 8 days, a message is displayed to admin users to remember to register this %s instance', get_product_name()), true),
        '<div class="inline-radio-button">
        '.__('Yes').html_print_radio_button('realtimestats', 1, '', $config['realtimestats'], true).'&nbsp;&nbsp;
        '.__('No').html_print_radio_button('realtimestats', 0, '', $config['realtimestats'], true).'</div>'
    );
}

html_print_input_hidden('action_update_url_update_manager', 1);
html_print_input_hidden('update_config', 1);
html_print_table($table);

html_print_action_buttons(
    html_print_submit_button(
        __('Update'),
        'update_button',
        false,
        ['icon' => 'wand'],
        true
    )
);
echo '</form>';
