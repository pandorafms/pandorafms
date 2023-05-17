<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
enterprise_include_once('include/functions_policies.php');

$disabledBecauseInPolicy = false;
$disabledTextBecauseInPolicy = '';
$classdisabledBecauseInPolicy = '';
$page = get_parameter('page', '');
if (strstr($page, 'policy_modules') === false) {
    if ($config['enterprise_installed']) {
        $disabledBecauseInPolicy = policies_is_module_in_policy($id_agent_module) && policies_is_module_linked($id_agent_module);
    } else {
        $disabledBecauseInPolicy = false;
    }

    if ($disabledBecauseInPolicy) {
        $disabledTextBecauseInPolicy = 'readonly = "readonly"';
        $classdisabledBecauseInPolicy = 'readonly';
    }
}

$extra_title = __('WMI server module');

define('ID_NETWORK_COMPONENT_TYPE', 6);

if (empty($edit_module)) {
    // Function in module_manager_editor_common.php
    add_component_selection(ID_NETWORK_COMPONENT_TYPE);
} else {
    // TODO: Print network component if available
}

$data = [];
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

    $inputs = html_print_select(
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
        'width: 100%; margin-top: 10px;'
    );

    $inputs .= html_print_input_text('custom_ip_target', $custom_ip_target, '', 15, 60, true);
} else {
    if ($ip_target == 'auto') {
        $ip_target = agents_get_address($id_agente);
    }

    $inputs = html_print_input_text(
        'ip_target',
        $ip_target,
        '',
        15,
        60,
        true,
        false,
        false,
        '',
        'mrgn_top_10px w100p'
    );
}

$data[0] = html_print_label_input_block(
    __('Target IP').' <span class="help_icon_15px">'.ui_print_help_icon('wmi_module_tab', true),
    $inputs,
    [
        'label_class' => 'font-title-font',
        'div_class'   => 'w100p mrgn_right_20px',
    ]
);

$data[2] = html_print_label_input_block(
    __('Namespace').ui_print_help_tip(__('Optional. WMI namespace. If unsure leave blank.'), true),
    html_print_input_text(
        'tcp_send',
        $tcp_send,
        '',
        5,
        20,
        true,
        $disabledBecauseInPolicy,
        false,
        '',
        $classdisabledBecauseInPolicy.' mrgn_top_10px w100p'
    ),
    [
        'label_class' => 'font-title-font',
        'div_class'   => 'w100p mrgn_right_20px',
    ]
);
push_table_simple($data, 'target_ip');

$data = [];
$data[0] = html_print_label_input_block(
    __('Username'),
    html_print_input_text(
        'plugin_user',
        $plugin_user,
        '',
        15,
        60,
        true,
        $disabledBecauseInPolicy,
        false,
        '',
        $classdisabledBecauseInPolicy.' w100p'
    ),
    [
        'label_class' => 'font-title-font',
        'div_class'   => 'w100p display-grid mrgn_right_20px',
    ]
);

$data[2] = html_print_label_input_block(
    __('Password'),
    html_print_input_password(
        'plugin_pass',
        $plugin_pass,
        '',
        15,
        60,
        true,
        $disabledBecauseInPolicy,
        false,
        $classdisabledBecauseInPolicy.' w100p',
        'new-password',
        true
    ),
    [
        'label_class' => 'font-title-font',
        'div_class'   => 'w100p display-grid mrgn_right_20px',
    ]
);
$table_simple->rowclass['user_pass'] = 'w100p mrgn_top_10px';

push_table_simple($data, 'user_pass');

$data = [];
$data[0] = html_print_label_input_block(
    __('WMI query'),
    html_print_input_text(
        'snmp_oid',
        $snmp_oid,
        '',
        35,
        255,
        true,
        $disabledBecauseInPolicy,
        false,
        '',
        $classdisabledBecauseInPolicy
    ),
    [
        'label_class' => 'font-title-font',
        'div_class'   => 'w100p display-grid mrgn_right_20px',
    ]
);

$data[2] = html_print_label_input_block(
    __('Key string').ui_print_help_tip(__('Optional. Substring to look for in the WQL query result. The module returns 1 if found, 0 if not.'), true),
    html_print_input_text(
        'snmp_community',
        $snmp_community,
        '',
        20,
        60,
        true,
        $disabledBecauseInPolicy,
        false,
        '',
        $classdisabledBecauseInPolicy
    ),
    [
        'label_class' => 'font-title-font',
        'div_class'   => 'w100p display-grid mrgn_right_20px',
    ]
);
$table_simple->rowclass['wmi_query'] = 'w100p mrgn_top_10px';

push_table_simple($data, 'wmi_query');

$data = [];
$data[0] = html_print_label_input_block(
    __('Field number').ui_print_help_tip(__('Column number to retrieve from the WQL query result (starting from zero).'), true),
    html_print_input_text(
        'tcp_port',
        $tcp_port,
        '',
        5,
        15,
        true,
        $disabledBecauseInPolicy,
        false,
        '',
        $classdisabledBecauseInPolicy.' mrgn_right_20px'
    ),
    [
        'label_class' => 'font-title-font',
        'div_class'   => 'w50p display-grid',
    ]
);

$table_simple->rowclass['key_field'] = 'w100p mrgn_top_10px';

push_table_simple($data, 'key_field');
?>
<script type="text/javascript">
$(document).ready (function () {
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
