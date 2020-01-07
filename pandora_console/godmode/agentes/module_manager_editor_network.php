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

global $config;

require_once $config['homedir'].'/include/functions_snmp_browser.php';
$snmp_browser_path = (is_metaconsole()) ? '../../' : '';
$snmp_browser_path .= 'include/javascript/pandora_snmp_browser.js';
echo "<script type='text/javascript' src='".$snmp_browser_path."'></script>";

// Define a custom action to save the OID selected
// in the SNMP browser to the form.
html_print_input_hidden(
    'custom_action',
    urlencode(
        base64_encode(
            '&nbsp;<a href="javascript:setOID()"><img src="'.ui_get_full_url('images').'/input_filter.disabled.png" title="'.__('Use this OID').'" style="vertical-align: middle;"></img></a>'
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
        $largeclassdisabledBecauseInPolicy = 'class = readonly';
    }
}

define('ID_NETWORK_COMPONENT_TYPE', 2);

if (empty($update_module_id)) {
    // Function in module_manager_editor_common.php.
    add_component_selection(ID_NETWORK_COMPONENT_TYPE);
}

$extra_title = __('Network server module');

$data = [];
$data[0] = __('Target IP');
// Show agent_for defect.
if ($page == 'enterprise/godmode/policies/policy_modules') {
    if ($ip_target != 'auto' && $ip_target != '') {
        $custom_ip_target = $ip_target;
        $ip_target = 'custom';
    } else if ($ip_target == '') {
        $ip_target = 'force_pri';
        $custom_ip_target = '';
    } else {
        $custom_ip_target = '';
    }

    $target_ip_values = [];
    $target_ip_values['auto']      = __('Auto');
    $target_ip_values['force_pri'] = __('Force primary key');
    $target_ip_values['custom']    = __('Custom');

    $data[1] = html_print_select(
        $target_ip_values,
        'ip_target',
        $ip_target,
        '',
        '',
        '',
        true,
        false,
        false,
        '',
        false,
        'width:200px;'
    );
    $data[1] .= html_print_input_text('custom_ip_target', $custom_ip_target, '', 15, 60, true);
} else {
    if ($ip_target == 'auto') {
        $ip_target = agents_get_address($id_agente);
    }

    $data[1] = html_print_input_text('ip_target', $ip_target, '', 15, 60, true);
}

// In ICMP modules, port is not configurable.
if ($id_module_type >= 6 && $id_module_type <= 7) {
    $data[2] = '';
    $data[3] = '';
} else {
    $data[2] = __('Port');
    $data[3] = html_print_input_text(
        'tcp_port',
        $tcp_port,
        '',
        5,
        20,
        true,
        $disabledBecauseInPolicy,
        false,
        '',
        $classdisabledBecauseInPolicy
    );
}

push_table_simple($data, 'target_ip');

$snmp_versions['1'] = 'v. 1';
$snmp_versions['2'] = 'v. 2';
$snmp_versions['2c'] = 'v. 2c';
$snmp_versions['3'] = 'v. 3';

$data = [];
$data[0] = __('SNMP community');
$adopt = false;
if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK && isset($id_agent_module)) {
    $adopt = policies_is_module_adopt($id_agent_module);
}

if (!$adopt) {
    $data[1] = html_print_input_text(
        'snmp_community',
        $snmp_community,
        '',
        15,
        60,
        true,
        $disabledBecauseInPolicy,
        false,
        '',
        $classdisabledBecauseInPolicy
    );
} else {
    $data[1] = html_print_input_text(
        'snmp_community',
        $snmp_community,
        '',
        15,
        60,
        true,
        false
    );
}

$data[2] = _('SNMP version');

if ($id_module_type >= 15 && $id_module_type <= 18) {
    $data[3] = html_print_select(
        $snmp_versions,
        'snmp_version',
        $tcp_send,
        '',
        '',
        '',
        true,
        false,
        false,
        '',
        $disabledBecauseInPolicy,
        false,
        '',
        $classdisabledBecauseInPolicy
    );
} else {
    $data[3] = html_print_select(
        $snmp_versions,
        'snmp_version',
        0,
        '',
        '',
        '',
        true,
        false,
        false,
        '',
        $disabledBecauseInPolicy,
        false,
        '',
        $classdisabledBecauseInPolicy
    );
}

if ($disabledBecauseInPolicy) {
    if ($id_module_type >= 15 && $id_module_type <= 18) {
        $data[3] .= html_print_input_hidden('snmp_version', $tcp_send, true);
    }
}

push_table_simple($data, 'snmp_1');

$data = [];
$data[0] = __('SNMP OID');
$data[1] = '<span class="left"; style="width: 50%">';
$data[1] .= html_print_input_text(
    'snmp_oid',
    $snmp_oid,
    '',
    30,
    255,
    true,
    $disabledBecauseInPolicy,
    false,
    '',
    $classdisabledBecauseInPolicy
);
$data[1] .= '<span class="invisible" id="oid">';
$data[1] .= html_print_select(
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
$data[1] .= html_print_image(
    'images/edit.png',
    true,
    [
        'class' => 'invisible clickable',
        'id'    => 'edit_oid',
    ]
);
$data[1] .= '</span>';
$data[1] .= '</span><span class="right" style="width: 50%; text-align: right">';
$data[1] .= html_print_button(
    __('SNMP walk'),
    'snmp_walk',
    false,
    'snmpBrowserWindow()',
    'class="sub next"',
    true
);
$data[1] .= ui_print_help_icon('snmpwalk', true);
$data[1] .= '</span>';
$table_simple->colspan['snmp_2'][1] = 3;

push_table_simple($data, 'snmp_2');

// Advanced stuff.
$data = [];
$data[0] = __('TCP send');
$data[1] = html_print_textarea(
    'tcp_send',
    2,
    65,
    $tcp_send,
    $disabledTextBecauseInPolicy,
    true,
    $largeclassdisabledBecauseInPolicy
);
$table_simple->colspan['tcp_send'][1] = 3;

push_table_simple($data, 'tcp_send');

$data[0] = __('TCP receive');
$data[1] = html_print_textarea(
    'tcp_rcv',
    2,
    65,
    $tcp_rcv,
    $disabledTextBecauseInPolicy,
    true,
    $largeclassdisabledBecauseInPolicy
);
$table_simple->colspan['tcp_receive'][1] = 3;

push_table_simple($data, 'tcp_receive');

if ($id_module_type < 8 || $id_module_type > 11) {
    // NOT TCP.
    $table_simple->rowstyle['tcp_send'] = 'display: none;';
    $table_simple->rowstyle['tcp_receive'] = 'display: none;';
}

if ($id_module_type < 15 || $id_module_type > 18) {
    // NOT SNMP.
    $table_simple->rowstyle['snmp_1'] = 'display: none';
    $table_simple->rowstyle['snmp_2'] = 'display: none';
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
    $largeclassdisabledBecauseInPolicy
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
    $largeclassdisabledBecauseInPolicy
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
$data[1] = html_print_input_text_extended(
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
);
$table_simple->colspan['row-cmd-row-1'][1] = 3;
push_table_simple($data, 'row-cmd-row-1');

require_once $config['homedir'].'/include/class/CredentialStore.class.php';
$array_credential_identifier = CredentialStore::getKeys('CUSTOM');

$data[0] = __('Credential identifier');
$data[1] = html_print_select(
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
);

$array_os = [
    'inherited' => __('Inherited'),
    'linux'     => __('SSH'),
    'windows'   => __('Windows remote'),
];

$data[2] = __('Connection method');
$data[3] = html_print_select(
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
);

push_table_simple($data, 'row-cmd-row-2');

if ($id_module_type !== 34
    && $id_module_type !== 35
    && $id_module_type !== 36
    && $id_module_type !== 37
) {
    $table_simple->rowstyle['row-cmd-row-1'] = 'display: none;';
    $table_simple->rowstyle['row-cmd-row-2'] = 'display: none;';
}

snmp_browser_print_container(false, '100%', '60%', 'none');

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
            $("#simple-row-cmd-row-2").attr("style", "");
        } else {
            $("#simple-row-cmd-row-1").css("display", "none");
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
    $('#text-target_ip').keyup(function() {
        $('#text-ip_target').val($(this).val());
    });
    $('#text-community').keyup(function() {
        $('#text-snmp_community').val($(this).val());
    });
    $('#text-snmp_community').keyup(function() {
        $('#text-community').val($(this).val());
    });
    $('#snmp_version').change(function() {
        $('#snmp_browser_version').val($(this).val());
        // Display or collapse the SNMP browser's v3 options
        checkSNMPVersion ();
    });
    $('#snmp_browser_version').change(function() {
        $('#snmp_version').val($(this).val());

        // Display or collapse the SNMP v3 options in the main window
        if ($(this).val() == "3") {
            $("#simple-field_snmpv3_row1").attr("style", "");
            $("#simple-field_snmpv3_row2").attr("style", "");
            $("#simple-field_snmpv3_row3").attr("style", "");
            $("input[name=active_snmp_v3]").val(1);
            $("input[name=snmp_community]").attr("disabled", true);
        }
        else {
            $("#simple-field_snmpv3_row1").css("display", "none");
            $("#simple-field_snmpv3_row2").css("display", "none");
            $("#simple-field_snmpv3_row3").css("display", "none");
            $("input[name=active_snmp_v3]").val(0);
            $("input[name=snmp_community]").removeAttr('disabled');
        }
    });
    $('#snmp3_auth_user').keyup(function() {
        $('#snmp3_browser_auth_user').val($(this).val());
    });
    $('#snmp3_browser_auth_user').keyup(function() {
        $('#snmp3_auth_user').val($(this).val());
    });
    $('#snmp3_security_level').change(function() {
        $('#snmp3_browser_security_level').val($(this).val());
    });
    $('#snmp3_browser_security_level').change(function() {
        $('#snmp3_security_level').val($(this).val());
    });
    $('#snmp3_auth_method').change(function() {
        $('#snmp3_browser_auth_method').val($(this).val());
    });
    $('#snmp3_browser_auth_method').change(function() {
        $('#snmp3_auth_method').val($(this).val());
    });
    $('#snmp3_auth_pass').keyup(function() {
        $('#snmp3_browser_auth_pass').val($(this).val());
    });
    $('#snmp3_browser_auth_pass').keyup(function() {
        $('#snmp3_auth_pass').val($(this).val());
    });
    $('#snmp3_privacy_method').change(function() {
        $('#snmp3_browser_privacy_method').val($(this).val());
    });
    $('#snmp3_browser_privacy_method').change(function() {
        $('#snmp3_privacy_method').val($(this).val());
    });
    $('#snmp3_privacy_pass').keyup(function() {
        $('#snmp3_browser_privacy_pass').val($(this).val());
    });
    $('#snmp3_browser_privacy_pass').keyup(function() {
        $('#snmp3_privacy_pass').val($(this).val());
    });
    var custom_ip_target = "<?php echo $custom_ip_target; ?>";
    if(custom_ip_target == ''){
        $("#text-custom_ip_target").hide();
    }
    $('#ip_target').change(function() {
        if($(this).val() == 'custom') {
            $("#text-custom_ip_target").show();
        }
        else{
            $("#text-custom_ip_target").hide();
        }
    });
});


</script>
