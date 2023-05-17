<?php
/**
 * Network module manager editor.
 *
 * @category   Modules
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
require_once $config['homedir'].'/include/class/CredentialStore.class.php';
require_once $config['homedir'].'/operation/snmpconsole/snmp_browser.php';
require_once $config['homedir'].'/include/functions_snmp_browser.php';
$snmp_browser_path = (is_metaconsole() === true) ? '../../' : '';
$snmp_browser_path .= 'include/javascript/pandora_snmp_browser.js';
$array_credential_identifier = CredentialStore::getKeys('CUSTOM');

echo '<script type="text/javascript" src="'.$snmp_browser_path.'?v='.$config['current_package'].'"></script>';

// Define a custom action to save the OID selected
// in the SNMP browser to the form.
html_print_input_hidden(
    'custom_action',
    urlencode(
        base64_encode(
            '&nbsp;<a href="javascript:setOID()"><img src="'.ui_get_full_url('images').'/input_filter.disabled.png" title="'.__('Use this OID').'" class="vertical_middle"></img></a>'
        )
    ),
    false
);

$isFunctionPolicies = enterprise_include_once('include/functions_policies.php');

$disabledBecauseInPolicy = false;
$disabledTextBecauseInPolicy = '';
$classdisabledBecauseInPolicy = '';
$largeclassdisabledBecauseInPolicy = '';
$page = get_parameter('page', '');
if (strstr($page, 'policy_modules') === false) {
    if ($config['enterprise_installed']) {
        $disabledBecauseInPolicy = policies_is_module_in_policy($id_agent_module) && policies_is_module_linked($id_agent_module);
    } else {
        $disabledBecauseInPolicy = false;
    }

    if ($disabledBecauseInPolicy) {
        $disabledTextBecauseInPolicy = 'readonly = "yes"';
        $classdisabledBecauseInPolicy = 'readonly';
        $largeclassdisabledBecauseInPolicy = 'readonly';
    }
}

define('ID_NETWORK_COMPONENT_TYPE', 2);

if (empty($edit_module) === true) {
    // Function in module_manager_editor_common.php.
    add_component_selection(ID_NETWORK_COMPONENT_TYPE);
}

$extra_title = __('Network server module');

$data = [];
$data[0] = __('Target IP');
if ((int) $id_module_type !== 6 && $id_module_type !== 7) {
    $data[1] = __('Port');
}

$table_simple->rowclass['caption_target_ip'] = 'w50p';
push_table_simple($data, 'caption_target_ip');

$data = [];
// Show agent_for defect.
if ($page === 'enterprise/godmode/policies/policy_modules') {
    if (empty($ip_target) === false && $ip_target !== 'auto') {
        $custom_ip_target = $ip_target;
        $ip_target = 'custom';
    } else if (empty($ip_target) === true) {
        $ip_target = 'force_pri';
        $custom_ip_target = '';
    } else {
        $custom_ip_target = '';
    }

    $target_ip_values = [];
    $target_ip_values['auto']      = __('Auto');
    $target_ip_values['force_pri'] = __('Force primary key');
    $target_ip_values['custom']    = __('Custom');

    $data[0] = html_print_select(
        $target_ip_values,
        'ip_target',
        $ip_target,
        '',
        '',
        '',
        true,
        false,
        false,
        'w100p',
        false,
    );
    $data[0] .= html_print_input_text('custom_ip_target', $custom_ip_target, '', 0, 60, true, false, false, '', 'w100p');
} else {
    if ($ip_target === 'auto') {
        $ip_target = agents_get_address($id_agente);
    }

    $data[0] = html_print_input_text('ip_target', $ip_target, '', 0, 60, true, false, false, '', 'w100p');
}

// In ICMP modules, port is not configurable.
if ($id_module_type !== 6 && $id_module_type !== 7) {
    $tcp_port = (empty($tcp_port) === false) ? $tcp_port : get_parameter('tcp_port');
    $data[1] = html_print_input_text(
        'tcp_port',
        $tcp_port,
        '',
        0,
        20,
        true,
        $disabledBecauseInPolicy,
        false,
        '',
        $classdisabledBecauseInPolicy.' w100p',
    );
} else {
    $data[1] = '';
}

$table_simple->rowclass['target_ip'] = 'w50p';
push_table_simple($data, 'target_ip');

$user_groups = users_get_groups(false, 'AR');
if (users_is_admin() === true || isset($user_groups[0]) === true) {
    $credentials = db_get_all_rows_sql(
        'SELECT identifier FROM tcredential_store WHERE product LIKE "SNMP"'
    );
} else {
    $credentials = db_get_all_rows_sql(
        sprintf(
            'SELECT identifier FROM tcredential_store WHERE product LIKE "SNMP" AND id_group IN (%s)',
            implode(',', array_keys($user_groups))
        )
    );
}

if (empty($credentials) === false) {
    $fields = [];
    foreach ($credentials as $key => $value) {
        $fields[$value['identifier']] = $value['identifier'];
    }

    $data = [];
    $data[0] = __('Credential store');
    push_table_simple($data, 'caption_snmp_credentials');

    $data = [];
    $data[0] = html_print_select(
        $fields,
        'credentials',
        0,
        '',
        __('None'),
        0,
        true,
        false,
        false,
        '',
        false,
        false,
        '',
        false
    );
    push_table_simple($data, 'snmp_credentials');
}

$data = [];
$data[0] = __('SNMP community');
$data[1] = __('SNMP version');
$data[2] = __('SNMP OID');
$data[2] .= ui_print_help_icon('snmpwalk', true);
$table_simple->cellclass['snmp_1'][0] = 'w25p';
$table_simple->cellclass['snmp_1'][1] = 'w25p';
$table_simple->cellclass['snmp_1'][2] = 'w50p';
push_table_simple($data, 'snmp_1');

if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK && isset($id_agent_module) === true) {
    $adopt = policies_is_module_adopt($id_agent_module);
} else {
    $adopt = false;
}

if ($adopt === false) {
    $snmpCommunityInput = html_print_input_text(
        'snmp_community',
        $snmp_community,
        '',
        0,
        60,
        true,
        $disabledBecauseInPolicy,
        false,
        '',
        $classdisabledBecauseInPolicy.' w100p'
    );
} else {
    $snmpCommunityInput = html_print_input_text(
        'snmp_community',
        $snmp_community,
        '',
        0,
        60,
        true,
        false,
        false,
        '',
        'w100p'
    );
}

$snmp_versions['1'] = 'v. 1';
$snmp_versions['2'] = 'v. 2';
$snmp_versions['2c'] = 'v. 2c';
$snmp_versions['3'] = 'v. 3';

$snmpVersionsInput = html_print_select(
    $snmp_versions,
    'snmp_version',
    ($id_module_type >= 15 && $id_module_type <= 18) ? $snmp_version : 0,
    '',
    '',
    '',
    true,
    false,
    false,
    '',
    $disabledBecauseInPolicy,
    'width: 100%',
    '',
    $classdisabledBecauseInPolicy.' w100p'
);

if ($disabledBecauseInPolicy === true) {
    if ($id_module_type >= 15 && $id_module_type <= 18) {
        $snmpVersionsInput .= html_print_input_hidden('snmp_version', $tcp_send, true);
    }
}

$data = [];
$table_simple->cellclass['snmp_2'][0] = 'w25p';
$table_simple->cellclass['snmp_2'][1] = 'w25p';
$table_simple->cellclass['snmp_2'][2] = 'w50p';

$data[0] = $snmpCommunityInput;
$data[1] = $snmpVersionsInput;
$data[2] = html_print_input_text(
    'snmp_oid',
    $snmp_oid,
    '',
    0,
    255,
    true,
    $disabledBecauseInPolicy,
    false,
    '',
    $classdisabledBecauseInPolicy
);
$data[2] .= '<span class="invisible" id="oid">';
$data[2] .= html_print_select(
    [],
    'select_snmp_oid',
    $snmp_oid,
    '',
    '',
    0,
    true,
    false,
    false,
    '',
    $disabledBecauseInPolicy
);
$data[2] .= html_print_image(
    'images/edit.png',
    true,
    [
        'class' => 'invisible clickable',
        'id'    => 'edit_oid',
    ]
);
$data[2] .= '</span>';
$data[2] .= html_print_button(
    __('SNMP Walk'),
    'snmp_walk',
    false,
    'snmpBrowserWindow('.$id_agente.')',
    [ 'mode' => 'link' ],
    true
);

push_table_simple($data, 'snmp_2');

// Advanced stuff.
$data = [];
$data[0] = __('TCP send');
$data[1] = __('TCP receive');

push_table_simple($data, 'caption_tcp_send_receive');

$data = [];
$data[0] = html_print_textarea(
    'tcp_send',
    2,
    65,
    $tcp_send,
    $disabledTextBecauseInPolicy,
    true,
    $largeclassdisabledBecauseInPolicy
);
$data[1] = html_print_textarea(
    'tcp_rcv',
    2,
    65,
    $tcp_rcv,
    $disabledTextBecauseInPolicy,
    true,
    $largeclassdisabledBecauseInPolicy
);

push_table_simple($data, 'tcp_send_receive');

if ($id_module_type < 8 || $id_module_type > 11) {
    // NOT TCP.
    $table_simple->rowstyle['caption_tcp_send_receive'] = 'display: none;';
    $table_simple->rowstyle['tcp_send_receive'] = 'display: none;';
}

if ($id_module_type < 15 || $id_module_type > 18) {
    // NOT SNMP.
    $table_simple->rowstyle['snmp_1'] = 'display: none';
    $table_simple->rowstyle['snmp_2'] = 'display: none';
    $table_simple->rowstyle['snmp_credentials'] = 'display: none';
}

// For a policy.
if (isset($id_agent_module) === false || $id_agent_module === false) {
    $snmp3_auth_user = '';
    $snmp3_auth_pass = '';
    $snmp_version = 1;
    $snmp3_privacy_method = '';
    $snmp3_privacy_pass = '';
    $snmp3_auth_method = '';
    $snmp3_security_level = '';
    $command_text = '';
    $command_os = 'inherited';
    $command_credential_identifier = '';
}

$data = [];
$data[0] = __('Auth user');
$data[1] = html_print_input_text(
    'snmp3_auth_user',
    $snmp3_auth_user,
    '',
    15,
    60,
    true,
    $disabledBecauseInPolicy,
    false,
    '',
    $classdisabledBecauseInPolicy
);
$data[2] = __('Auth password').ui_print_help_tip(__('The pass length must be eight character minimum.'), true);
$data[3] = html_print_input_password(
    'snmp3_auth_pass',
    $snmp3_auth_pass,
    '',
    15,
    60,
    true,
    $disabledBecauseInPolicy,
    false,
    $largeclassdisabledBecauseInPolicy,
    'off',
    true
);
$data[3] .= html_print_input_hidden_extended('active_snmp_v3', 0, 'active_snmp_v3_mmen', true);
if ($snmp_version != 3) {
    $table_simple->rowstyle['field_snmpv3_row1'] = 'display: none;';
}

push_table_simple($data, 'field_snmpv3_row1');

$data = [];
$data[0] = __('Privacy method');
$data[1] = html_print_select(['DES' => __('DES'), 'AES' => __('AES')], 'snmp3_privacy_method', $snmp3_privacy_method, '', '', '', true, false, false, '', $disabledBecauseInPolicy);
$data[2] = __('Privacy pass').ui_print_help_tip(__('The pass length must be eight character minimum.'), true);
$data[3] = html_print_input_password(
    'snmp3_privacy_pass',
    $snmp3_privacy_pass,
    '',
    15,
    60,
    true,
    $disabledBecauseInPolicy,
    false,
    $largeclassdisabledBecauseInPolicy,
    'off',
    true
);

if ($snmp_version != 3) {
    $table_simple->rowstyle['field_snmpv3_row2'] = 'display: none;';
}

push_table_simple($data, 'field_snmpv3_row2');

$data = [];
$data[0] = __('Auth method');
$data[1] = html_print_select(
    [
        'MD5' => __('MD5'),
        'SHA' => __('SHA'),
    ],
    'snmp3_auth_method',
    $snmp3_auth_method,
    '',
    '',
    '',
    true,
    false,
    false,
    '',
    $disabledBecauseInPolicy
);
$data[2] = __('Security level');
$data[3] = html_print_select(
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
    true,
    false,
    false,
    '',
    $disabledBecauseInPolicy
);
if ($snmp_version != 3) {
    $table_simple->rowstyle['field_snmpv3_row3'] = 'display: none;';
}

push_table_simple($data, 'field_snmpv3_row3');

$data = [];
$data[0] = __('Command');
$data[0] .= ui_print_help_tip(
    __(
        'Please use single quotation marks when necessary. '."\n".'
If double quotation marks are needed, please escape them with a backslash (\&quot;)'
    ),
    true
);
push_table_simple($data, 'caption-row-cmd-row-1');

$data = [];
$data[0] = html_print_input_text_extended(
    'command_text',
    $command_text,
    'command_text',
    '',
    0,
    10000,
    $disabledBecauseInPolicy,
    '',
    $largeClassDisabledBecauseInPolicy.' class="w100p"',
    true
);
$table_simple->rowclass['row-cmd-row-1'] = 'w100p';
push_table_simple($data, 'row-cmd-row-1');

$data = [];
$data[0] = __('Credential identifier');
$data[1] = __('Connection method');
// $table_simple->rowclass['row-cmd-row-1'] = 'w100p';
$table_simple->cellclass['caption-row-cmd-row-2'][0] = 'w50p';
$table_simple->cellclass['caption-row-cmd-row-2'][1] = 'w50p';
push_table_simple($data, 'caption-row-cmd-row-2');

$data = [];
$data[0] = html_print_select(
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
    $disabledBecauseInPolicy,
    'width: 100%;'
);

$data[0] .= html_print_button(
    __('Manage credentials'),
    'manage_credentials_button',
    false,
    'window.location.assign("index.php?sec=gmodules&sec2=godmode/groups/group_list&tab=credbox")',
    [ 'mode' => 'link' ],
    true
);

$array_os = [
    'inherited' => __('Inherited'),
    'linux'     => __('SSH'),
    'windows'   => __('Windows remote'),
];

$data[1] = html_print_select(
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
    $disabledBecauseInPolicy,
    'width: 100%;'
);
$table_simple->cellclass['row-cmd-row-2'][0] = 'w50p';
$table_simple->cellclass['row-cmd-row-2'][1] = 'w50p';
push_table_simple($data, 'row-cmd-row-2');

if ($id_module_type !== 34
    && $id_module_type !== 35
    && $id_module_type !== 36
    && $id_module_type !== 37
) {
    $table_simple->rowstyle['caption-row-cmd-row-1'] = 'display: none;';
    $table_simple->rowstyle['row-cmd-row-1'] = 'display: none;';
    $table_simple->rowstyle['caption-row-cmd-row-2'] = 'display: none;';
    $table_simple->rowstyle['row-cmd-row-2'] = 'display: none;';
}

snmp_browser_print_container(false, '100%', '60%', 'display:none');

?>
<script type="text/javascript">
$(document).ready (function () {
    $("#id_module_type").change(function (){
        if ((this.value == "17") ||
            (this.value == "18") ||
            (this.value == "16") ||
            (this.value == "15")
        ) {
            if ($("#snmp_version").val() == "3"){
                $("#simple-field_snmpv3_row1").attr("style", "");
                $("#simple-field_snmpv3_row2").attr("style", "");
                $("#simple-field_snmpv3_row3").attr("style", "");
                $("input[name=active_snmp_v3]").val(1);
                $("input[name=snmp_community]").attr("disabled", true);
            }
        } else {
            $("#simple-field_snmpv3_row1").css("display", "none");
            $("#simple-field_snmpv3_row2").css("display", "none");
            $("#simple-field_snmpv3_row3").css("display", "none");
            $("input[name=active_snmp_v3]").val(0);
            $("input[name=snmp_community]").removeAttr('disabled');
        }

        if((this.value == "34") ||
            (this.value == "35") ||
            (this.value == "36") ||
            (this.value == "37")
        ) {
            $("#simple-row-cmd-row-1").attr("style", "");
            $("#simple-caption-row-cmd-row-1").attr("style", "");
            $("#simple-row-cmd-row-2").attr("style", "");
            $("#simple-caption-row-cmd-row-2").attr("style", "");
        } else {
            $("#simple-caption-row-cmd-row-1").css("display", "none");
            $("#simple-row-cmd-row-1").css("display", "none");
            $("#simple-caption-row-cmd-row-2").css("display", "none");
            $("#simple-row-cmd-row-2").css("display", "none");
        }
    });

    $("#snmp_version").change(function () {
        if (this.value == "3") {
            $("#simple-field_snmpv3_row1").attr("style", "");
            $("#simple-field_snmpv3_row2").attr("style", "");
            $("#simple-field_snmpv3_row3").attr("style", "");
            $("input[name=active_snmp_v3]").val(1);
        }
        else {
            $("#simple-field_snmpv3_row1").css("display", "none");
            $("#simple-field_snmpv3_row2").css("display", "none");
            $("#simple-field_snmpv3_row3").css("display", "none");
            $("input[name=active_snmp_v3]").val(0);
        }
    });

    $("#select_snmp_oid").click (
        function () {
            $(this).css ("width", "auto");
            $(this).css ("min-width", "180px");
        });

    $("#select_snmp_oid").blur (function () {
        $(this).css ("width", "180px");
    });

    $("#credentials").change (function() {
        if ($('#credentials').val() !== '0') {
            $.ajax({
                method: "post",
                url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
                data: {
                    page: "godmode/agentes/agent_wizard",
                    method: "getCredentials",
                    identifier: $('#credentials').val()
                },
                datatype: "json",
                success: function(data) {
                    data = JSON.parse(data);
                    extra = JSON.parse(data['extra_1']);
                    $('#snmp_version').val(extra['version']);
                    $('#snmp_version').trigger('change');
                    $('#text-snmp_community').val(extra['community']);

                    if (extra['version'] === '3') {
                        $('#snmp3_security_level').val(extra['securityLevelV3']);
                        $('#snmp3_security_level').trigger('change');
                        $('#text-snmp3_auth_user').val(extra['authUserV3']);

                        if (extra['securityLevelV3'] === 'authNoPriv' || extra['securityLevelV3'] === 'authPriv') {
                            $('#snmp3_auth_method').val(extra['authMethodV3']);
                            $('#snmp3_auth_method').trigger('change');
                            $('#password-snmp3_auth_pass').val(extra['authPassV3']);

                            if (extra['securityLevelV3'] === 'authPriv') {
                                $('#snmp3_privacy_method').val(extra['privacyMethodV3']);
                                $('#snmp3_privacy_method').trigger('change');
                                $('#password-snmp3_privacy_pass').val(extra['privacyPassV3']);
                            }
                        }
                    }
                },
                error: function(e) {
                    console.error(e);
                }
            });
        }
    });

    $("#id_module_type").click (
        function () {
            $(this).css ("width", "auto");
            $(this).css ("min-width", "180px");
        }
    );

    $("#id_module_type").blur (function () {
        $(this).css ("width", "180px");
    });

    // Keep elements in the form and the SNMP browser synced
    $('#text-ip_target').keyup(function() {
        $('#text-target_ip').val($(this).val());
    });

    $('#text-snmp_community').keyup(function() {
        $('#text-community').val($(this).val());
    });
    $('#snmp_version').change(function() {
        $('#snmp_browser_version').val($(this).val());
        // Display or collapse the SNMP browser's v3 options
        checkSNMPVersion ();
    });
    $('#snmp3_auth_user').keyup(function() {
        $('#snmp3_browser_auth_user').val($(this).val());
    });
    $('#snmp3_security_level').change(function() {
        $('#snmp3_browser_security_level').val($(this).val());
    });
    $('#snmp3_auth_method').change(function() {
        $('#snmp3_browser_auth_method').val($(this).val());
    });
    $('#snmp3_auth_pass').keyup(function() {
        $('#snmp3_browser_auth_pass').val($(this).val());
    });
    $('#snmp3_privacy_method').change(function() {
        $('#snmp3_browser_privacy_method').val($(this).val());
    });
    $('#snmp3_privacy_pass').keyup(function() {
        $('#snmp3_browser_privacy_pass').val($(this).val());
    });

    var custom_ip_target = "<?php echo $custom_ip_target; ?>";
    if(custom_ip_target == ''){
        $("#text-custom_ip_target").hide();
    }
    $('#ip_target').change(function() {
        if($(this).val() === 'custom') {
            $("#text-custom_ip_target").show();
        }
        else{
            $("#text-custom_ip_target").hide();
        }
    });
});


</script>
