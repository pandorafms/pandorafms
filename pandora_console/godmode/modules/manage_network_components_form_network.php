<?php
/**
 * Extension to manage a list of gateways and the node address where they should
 * point to.
 *
 * @category   Extensions
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

check_login();

if (! check_acl($config['id_user'], 0, 'PM') && ! check_acl($config['id_user'], 0, 'AW')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Agent Management'
    );
    include 'general/noaccess.php';
    return;
}

if (!$id && !isset($snmp_community)) {
    $snmp_community = 'public';
}

$snmp_versions['1'] = 'v. 1';
$snmp_versions['2c'] = 'v. 2c';
$snmp_versions['3'] = 'v. 3';

$data = [];
$data[0] = html_print_label_input_block(
    __('Target IP'),
    html_print_input_text_extended(
        'target_ip',
        $target_ip,
        'target_ip',
        '',
        30,
        10000,
        '',
        '',
        '',
        true
    )
);

$data[1] = html_print_label_input_block(
    __('SNMP version'),
    html_print_select(
        $snmp_versions,
        'snmp_version',
        $snmp_version,
        '',
        '',
        '',
        true,
        false,
        false,
        ''
    )
);

push_table_row($data, 'row1');

$data = [];
$data[0] = html_print_label_input_block(
    __('Port'),
    html_print_input_text('tcp_port', $tcp_port, '', 5, 20, true)
);

$data[1] = html_print_label_input_block(
    __('SNMP community'),
    html_print_input_text(
        'snmp_community',
        $snmp_community,
        '',
        15,
        60,
        true
    )
);

push_table_row($data, 'snmp_port');

$data = [];
$data[0] = html_print_label_input_block(
    __('SNMP Enterprise String'),
    html_print_input_text(
        'snmp_oid',
        $snmp_oid,
        '',
        30,
        400,
        true
    )
);

$data[1] = html_print_label_input_block(
    __('Auth password'),
    html_print_input_password(
        'snmp3_auth_pass',
        $snmp3_auth_pass,
        '',
        15,
        60,
        true,
        false,
        false,
        '',
        'off',
        true
    ).html_print_input_hidden_extended(
        'active_snmp_v3',
        0,
        'active_snmp_v3_mncfn',
        true
    )
);

push_table_row($data, 'snmp_2');


$data = [];
$data[0] = html_print_label_input_block(
    __('Auth user'),
    html_print_input_text(
        'snmp3_auth_user',
        $snmp3_auth_user,
        '',
        15,
        60,
        true
    )
);

$data[1] = html_print_label_input_block(
    __('Privacy pass'),
    html_print_input_password(
        'snmp3_privacy_pass',
        $snmp3_privacy_pass,
        '',
        15,
        60,
        true,
        false,
        false,
        '',
        'off',
        true
    )
);

push_table_row($data, 'field_snmpv3_row1');

$data = [];
$data[0] = html_print_label_input_block(
    __('Privacy method'),
    html_print_select(
        [
            'DES' => __('DES'),
            'AES' => __('AES'),
        ],
        'snmp3_privacy_method',
        $snmp3_privacy_method,
        '',
        '',
        '',
        true
    )
);

$data[1] = html_print_label_input_block(
    __('Security level'),
    html_print_select(
        [
            'noAuthNoPriv' => __('Not auth and not privacy method'),
            'authNoPriv'   => __('Auth and not privacy method'),
            'authPriv'     => __('Auth and privacy method'),
        ],
        'snmp3_security_level',
        $snmp3_security_level,
        '',
        '',
        '',
        true
    )
);

push_table_row($data, 'field_snmpv3_row2');

$data = [];
$data[0] = html_print_label_input_block(
    __('Auth method'),
    html_print_select(
        [
            'MD5' => __('MD5'),
            'SHA' => __('SHA'),
        ],
        'snmp3_auth_method',
        $snmp3_auth_method,
        '',
        '',
        '',
        true
    )
);

$data[1] = html_print_label_input_block(
    __('Name OID').'&nbsp;'.ui_print_help_icon('xxx', true),
    html_print_input_text_extended(
        'name_oid',
        $name_oid,
        'name_oid',
        '',
        30,
        10000,
        '',
        '',
        '',
        true
    )
);

push_table_row($data, 'field_snmpv3_row3');

$data = [];
$data[0] = html_print_label_input_block(
    __('Post process'),
    html_print_extended_select_for_post_process(
        'post_process',
        $post_process,
        '',
        '',
        '0',
        false,
        true,
        false,
        true
    )
);

$data[1] = html_print_label_input_block(
    '',
    ''
);

push_table_row($data, 'field_process');

// Advanced stuff.
$data = [];
$data[0] = html_print_label_input_block(
    __('TCP send'),
    html_print_textarea('tcp_send', 2, 65, $tcp_send, '', true)
);
$table->colspan['tcp_send'][0] = 2;

push_table_row($data, 'tcp_send');

$data = [];
$data[0] = html_print_label_input_block(
    __('TCP receive'),
    html_print_textarea('tcp_rcv', 2, 65, $tcp_rcv, '', true)
);
$table->colspan['tcp_receive'][0] = 2;

push_table_row($data, 'tcp_receive');

$data = [];
$data[0] = html_print_label_input_block(
    __('Command'),
    html_print_input_text_extended(
        'command_text',
        $command_text,
        'command_text',
        '',
        100,
        10000,
        $disabledBecauseInPolicy,
        '',
        $largeClassDisabledBecauseInPolicy,
        true
    )
);
$table->colspan['row-cmd-row-1'][0] = 2;
push_table_row($data, 'row-cmd-row-1');

require_once $config['homedir'].'/include/class/CredentialStore.class.php';
$array_credential_identifier = CredentialStore::getKeys('CUSTOM');

$data[0] = html_print_label_input_block(
    __('Credential identifier'),
    html_print_select(
        $array_credential_identifier,
        'command_credential_identifier',
        $command_credential_identifier,
        '',
        __('None'),
        '',
        true,
        false,
        false,
        '',
        $disabledBecauseInPolicy
    )
);

$array_os = [
    'inherited' => __('Inherited'),
    'linux'     => __('Linux'),
    'windows'   => __('Windows'),
];

$data[1] = html_print_label_input_block(
    __('Target OS'),
    html_print_select(
        $array_os,
        'command_os',
        $command_os,
        '',
        '',
        '',
        true,
        false,
        false,
        '',
        $disabledBecauseInPolicy
    )
);

push_table_row($data, 'row-cmd-row-2');
?>

<script type="text/javascript">
    $(document).ready (function () {
        $("#submit-upd").click (function () {
            validate_post_process();
        });
        $("#submit-crt").click (function () {
            validate_post_process();
        });
    });

    function validate_post_process() {
        var post_process = $("#text-post_process").val();
        if (post_process != undefined){
            var new_post_process = post_process.replace(',','.');
            $("#text-post_process").val(new_post_process);
        }
    }
</script>
