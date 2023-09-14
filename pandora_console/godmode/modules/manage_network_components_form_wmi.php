<?php

// Pandora FMS - https://pandorafms.com
// ==================================================
// Copyright (c) 2005-2023 Pandora FMS
// Please see https://pandorafms.com/community/ for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// Load global variables
global $config;

check_login();

if (! check_acl($config['id_user'], 0, 'PM')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Agent Management'
    );
    include 'general/noaccess.php';
    return;
}

require_once $config['homedir'].'/include/functions_modules.php';

$data = [];
$data[0] = html_print_label_input_block(
    __('WMI query').' '.ui_print_help_icon('wmi_query_tab', true),
    html_print_input_text('snmp_oid', $snmp_oid, '', 25, 255, true)
);

$data[1] = html_print_label_input_block(
    __('Key string'),
    html_print_input_text('snmp_community', $snmp_community, '', 25, 255, true)
);

push_table_row($data, 'wmi_1');

$data = [];
$data[0] = html_print_label_input_block(
    __('Field number'),
    html_print_input_text('tcp_port', $tcp_port, '', 5, 25, true)
);

$data[1] = html_print_label_input_block(
    __('Namespace'),
    html_print_input_text('tcp_send', $tcp_send, '', 25, 255, true)
);

push_table_row($data, 'wmi_2');

$data = [];
$data[0] = html_print_label_input_block(
    __('Username'),
    html_print_input_text('plugin_user', $plugin_user, '', 15, 255, true)
);

$data[1] = html_print_label_input_block(
    __('Password'),
    html_print_input_password(
        'plugin_pass',
        $plugin_pass,
        '',
        25,
        255,
        true,
        false,
        false,
        '',
        'off',
        true
    )
);

push_table_row($data, 'wmi_3');

$data = [];
$data[0] = html_print_label_input_block(
    __('Post process'),
    html_print_extended_select_for_post_process(
        'post_process',
        $post_process,
        '',
        __('Empty'),
        '0',
        false,
        true,
        false,
        true
    )
);
$data[1] = '';
push_table_row($data, 'field_process');
