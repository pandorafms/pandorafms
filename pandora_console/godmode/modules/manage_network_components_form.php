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

global $config;

check_login();

if (! check_acl($config['id_user'], 0, 'PM') && ! check_acl($config['id_user'], 0, 'AW')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Agent Management'
    );
    include 'general/noaccess.php';
    exit;
}

$create_network_from_module = get_parameter('create_network_from_module');
$create_network_from_snmp_browser = get_parameter(
    'create_network_from_snmp_browser',
    0
);
$pure = get_parameter('pure', 0);

if ($create_network_from_module) {
    $id_agentmodule = get_parameter('create_module_from');
    $data_module = db_get_row_filter(
        'tagente_modulo',
        ['id_agente_modulo' => $id_agentmodule]
    );

    $name = $data_module['nombre'];
    $description = $data_module['descripcion'];
    $type = $data_module['id_tipo_modulo'];
    $max = $data_module['max'];
    $min = $data_module['min'];
    $module_interval = $data_module['module_interval'];
    $target_ip = $data_module['target_ip'];
    $tcp_port = $data_module['tcp_port'];
    $tcp_rcv = $data_module['tcp_rcv'];
    $tcp_send = $data_module['tcp_send'];
    $snmp_community = $data_module['snmp_community'];
    $snmp_oid = $data_module['snmp_oid'];
    $id_module_group = $data_module['id_module_group'];
    $id_plugin = $data_module['id_plugin'];
    $plugin_user = $data_module['plugin_user'];
    $plugin_pass = io_output_password($data_module['plugin_pass']);
    $plugin_parameter = $data_module['plugin_parameter'];
    $macros = $data_module['macros'];
    $max_timeout = $data_module['max_timeout'];
    $max_retries = $data_module['max_retries'];
    $dynamic_interval = $data_module['dynamic_interval'];
    $dynamic_max = $data_module['dynamic_max'];
    $dynamic_min = $data_module['dynamic_min'];
    $dynamic_two_tailed = $data_module['dynamic_two_tailed'];
    $min_warning = $data_module['min_warning'];
    $max_warning = $data_module['max_warning'];
    $str_warning = $data_module['str_warning'];
    $max_critical = $data_module['max_critical'];
    $min_critical = $data_module['min_critical'];
    $str_critical = $data_module['str_critical'];
    $ff_event = $data_module['min_ff_event'];
    $history_data = $data_module['history_data'];
    $post_process = $data_module['post_process'];
    $unit = $data_module['unit'];
    $wizard_level = $data_module['wizard_level'];
    $critical_instructions = $data_module['critical_instructions'];
    $warning_instructions = $data_module['warning_instructions'];
    $unknown_instructions = $data_module['unknown_instructions'];
    $critical_inverse = $data_module['critical_inverse'];
    $warning_inverse = $data_module['warning_inverse'];
    $percentage_critical = $data_module['percentage_critical'];
    $percentage_warning = $data_module['percentage_warning'];
    $id_category = $data_module['id_category'];
    $ff_event_normal = $data_module['min_ff_event_normal'];
    $ff_event_warning = $data_module['min_ff_event_warning'];
    $ff_event_critical = $data_module['min_ff_event_critical'];
    $ff_type = $data_module['ff_type'];
    $each_ff = $data_module['each_ff'];
    $warning_time = $data_module['warning_time'];
}

$id_component_type = (int) get_parameter('id_component_type');
if ($create_network_from_module) {
    $id_component_type = 2;
}


if (isset($id)) {
    $component = network_components_get_network_component((int) $id);
    if ($component !== false) {
        $id_component_type       = $component['id_modulo'];
        $name                    = io_safe_output($component['name']);
        $type                    = $component['type'];
        $description             = $component['description'];
        $max                     = $component['max'];
        $min                     = $component['min'];
        $module_interval         = $component['module_interval'];
        $target_ip               = $component['target_ip'];
        $tcp_port                = $component['tcp_port'];
        $tcp_rcv                 = $component['tcp_rcv'];
        $tcp_send                = $component['tcp_send'];
        $snmp_community          = $component['snmp_community'];
        $snmp_oid                = $component['snmp_oid'];
        $id_module_group         = $component['id_module_group'];
        $id_group                = $component['id_group'];
        $id_plugin               = $component['id_plugin'];
        $plugin_user             = $component['plugin_user'];
        $plugin_pass             = io_output_password($component['plugin_pass']);
        $plugin_parameter        = $component['plugin_parameter'];
        $macros                  = $component['macros'];
        $max_timeout             = $component['max_timeout'];
        $max_retries             = $component['max_retries'];
        $dynamic_interval        = $component['dynamic_interval'];
        $dynamic_max             = $component['dynamic_max'];
        $dynamic_min             = $component['dynamic_min'];
        $dynamic_two_tailed      = $component['dynamic_two_tailed'];
        $min_warning             = $component['min_warning'];
        $max_warning             = $component['max_warning'];
        $str_warning             = $component['str_warning'];
        $max_critical            = $component['max_critical'];
        $min_critical            = $component['min_critical'];
        $str_critical            = $component['str_critical'];
        $ff_event                = $component['min_ff_event'];
        $history_data            = $component['history_data'];
        $post_process            = $component['post_process'];
        $unit                    = $component['unit'];
        $wizard_level            = $component['wizard_level'];
        $critical_instructions   = $component['critical_instructions'];
        $warning_instructions    = $component['warning_instructions'];
        $unknown_instructions    = $component['unknown_instructions'];
        $critical_inverse        = $component['critical_inverse'];
        $percentage_critical     = $component['percentage_critical'];
        $warning_inverse         = $component['warning_inverse'];
        $percentage_warning     = $component['percentage_warning'];
        $id_category             = $component['id_category'];
        $tags                    = $component['tags'];
        $ff_event_normal         = $component['min_ff_event_normal'];
        $ff_event_warning        = $component['min_ff_event_warning'];
        $ff_event_critical       = $component['min_ff_event_critical'];
        $ff_type                 = $component['ff_type'];
        $each_ff                 = $component['each_ff'];
        $manufacturer_id         = $component['manufacturer_id'];
        $module_protocol         = $component['protocol'];
        $scan_type               = $component['scan_type'];
        $execution_type          = $component['execution_type'];
        $value                   = $component['value'];
        $wmi_class               = $component['query_class'];
        $query_key_field         = $component['query_key_field'];
        $query_Key_wmi           = $component['key_string'];
        $name_oid                = $component['name_oid'];
        $value_oid               = $component['value_oid'];
        $query_filter            = $component['query_filters'];
        $module_enabled          = $component['module_enabled'];
        $enabled                 = $component['enabled'];
        $warning_time             = $component['warning_time'];

        if ($type >= MODULE_TYPE_REMOTE_SNMP && $type <= MODULE_TYPE_REMOTE_SNMP_PROC) {
            // New support for snmp v3.
            $snmp_version = $component['tcp_send'];
            $snmp3_auth_user = $component['plugin_user'];
            $snmp3_auth_pass = io_output_password($component['plugin_pass']);
            $snmp3_auth_method = $component['plugin_parameter'];
            $snmp3_privacy_method = $component['custom_string_1'];
            $snmp3_privacy_pass = io_output_password(
                $component['custom_string_2']
            );
            unset($tcp_send);
            $snmp3_security_level = $component['custom_string_3'];
        } else if ($type >= MODULE_TYPE_REMOTE_CMD && $type <= MODULE_TYPE_REMOTE_CMD_INC) {
            $command_text = $component['tcp_send'];
            $command_credential_identifier = $component['custom_string_1'];
            $command_os = $component['custom_string_2'];
        }
    } else if (isset($new_component)
        && $new_component
        && !$create_network_from_snmp_browser
    ) {
        $name = '';
        $snmp_oid = '';
        $description = '';
        $id_group = 1;
        $oid = '';
        $max = '0';
        $min = '0';
        $module_interval = '300';
        $target_ip = '';
        $tcp_port = '';
        $tcp_rcv = '';
        $tcp_send = '';
        $snmp_community = '';
        $id_module_group = '';
        if ($id_component_type == COMPONENT_TYPE_WMI) {
            $id_group = 14;
        } else {
            $id_group = '';
        }

        $type = 0;
        $min_warning = 0;
        $max_warning = 0;
        $str_warning = '';
        $dynamic_interval = 0;
        $dynamic_min = 0;
        $dynamic_max = 0;
        $dynamic_two_tailed = 0;
        $max_critical = 0;
        $min_critical = 0;
        $str_critical = '';
        $ff_event = 0;
        $history_data = true;
        $post_process = 0;
        $unit = '';
        $wizard_level = 'nowizard';
        $critical_instructions = '';
        $warning_instructions = '';
        $unknown_instructions = '';
        $critical_inverse = 0;
        $warning_inverse = 0;
        $percentage_critical = 0;
        $percentage_warning = 0;
        $id_category = 0;
        $tags = '';
        $ff_event_normal = 0;
        $ff_event_warning = 0;
        $ff_event_critical = 0;
        $ff_type = 0;
        $each_ff = 0;
        $warning_time = 0;

        $snmp_version = 1;
        $snmp3_auth_user = '';
        $snmp3_auth_pass = '';
        $snmp3_privacy_method = '';
        $snmp3_privacy_pass = '';
        $snmp3_auth_method = '';
        $snmp3_security_level = '';

        $command_text = '';
        $command_os = 'inherited';
        $command_credential_identifier = '';

        $macros            = '';
        $manufacturer_id   = '';
        $module_protocol   = 'snmp';
        $scan_type         = SCAN_TYPE_FIXED;
        $execution_type    = EXECUTION_TYPE_NETWORK;
        $value             = '';
        $wmi_class         = '';
        $query_key_field   = '';
        $query_Key_wmi     = '';
        $name_oid          = '';
        $value_oid         = '';
        $query_filter      = '';
        $module_enabled    = true;
        $enabled           = true;
    }
}

$table = new stdClass();


/**
 * Common function for adding rows to main table
 *
 * @param array $row Array with the data for add.
 * @param mixed $id  If added, the DOM id for this block.
 *
 * @return void
 */
function push_table_row($row, $id=false)
{
    global $table;

    if ($id) {
        $data = [$id => $row];
    } else {
        $data = [$row];
    }

    $table->data = array_merge($table->data, $data);
}


$remote_components_path = $config['homedir'].'/godmode/modules/';
if ($id_component_type == COMPONENT_TYPE_WMI) {
    $categories = [
        0,
        1,
        2,
    ];
    include $remote_components_path.'manage_network_components_form_common.php';
    include $remote_components_path.'manage_network_components_form_wmi.php';
} else if ($id_component_type == COMPONENT_TYPE_PLUGIN) {
    $categories = [
        0,
        1,
        2,
    ];
    include $remote_components_path.'manage_network_components_form_common.php';
    include $remote_components_path.'manage_network_components_form_plugin.php';
} else if ($id_component_type == COMPONENT_TYPE_WIZARD) {
    $categories = [
        3,
        4,
        5,
    ];
    if (enterprise_installed() === true) {
        $categories[] = 10;
    }

    include $remote_components_path.'manage_network_components_form_wizard.php';
} else if ($id_component_type == COMPONENT_TYPE_NETWORK || $create_network_from_module) {
    $categories = [
        3,
        4,
        5,
    ];
    if (enterprise_installed() === true) {
        $categories[] = 10;
    }

    include $remote_components_path.'manage_network_components_form_common.php';
    include $remote_components_path.'manage_network_components_form_network.php';
} else {
    return;
}

echo '<form name="component" method="post">';

$table->width = '100%';
$table->class = 'databox filters';

if (is_metaconsole() === true) {
    if ($id) {
        $table->head[0] = __('Update Network Component');
    } else {
        $table->head[0] = __('Create Network Component');
    }

    $table->head_colspan[0] = 5;
    $table->headstyle[0] = 'text-align: center';
}

html_print_table($table);

$buttons = html_print_input_hidden('id_component_type', $id_component_type);
if ($id) {
    $buttons .= html_print_input_hidden('update_component', 1, true);
    $buttons .= html_print_input_hidden('id', $id, true);
    $buttonCaption = __('Update');
    $buttonIcon = 'update';
    $buttonName = 'upd';
} else {
    $buttons .= html_print_input_hidden('create_component', 1, true);
    $buttons .= html_print_input_hidden('create_network_from_module', 0, true);
    $buttonCaption = __('Create');
    $buttonIcon = 'wand';
    $buttonName = 'crt';
}

$buttons .= html_print_submit_button(
    $buttonCaption,
    $buttonName,
    false,
    ['icon' => $buttonIcon],
    true
);

$buttons .= html_print_button(
    __('Go back'),
    'go_back',
    false,
    '',
    [
        'icon' => 'back',
        'mode' => 'secondary',
    ],
    true
);

html_print_action_buttons(
    $buttons
);

echo '</form>';

ui_require_javascript_file('pandora_modules');
?>
<script language="JavaScript" type="text/javascript">

$('#button-go_back').click(function () {
    window.location.href = "<?php echo ui_get_full_url('index.php?sec=templates&sec2=godmode/modules/manage_network_components'); ?>";
});

function type_change () {
    // type 1-4 - Generic_xxxxxx
    if ((document.component.type.value > 0) && (document.component.type.value < 5)) {
        $("input[name=snmp_community]")
            .css({backgroundColor: '#ddd '});
        $("input[name=snmp_community]").attr("disabled", true);

        $("input[name=tcp_rcv]").css({backgroundColor: '#ddd '});
        $("input[name=tcp_rcv]").attr("disabled", true);

        <?php
        if ($id_component_type != MODULE_WMI) {
            ?>
            $("input[name=snmp_oid]")
                .css({backgroundColor: '#ddd '});
            $("input[name=snmp_oid]").attr("disabled", true);

            $("input[name=tcp_send]")
                .css({backgroundColor: '#ddd '});
            $("input[name=tcp_send]").attr("disabled", true);

            $("input[name=tcp_port]")
                .css({backgroundColor: '#ddd '});
            $("input[name=tcp_port]").attr("disabled", true);
            <?php
        }
        ?>

        $("input[name=snmp3_auth_user]")
            .css({backgroundColor: '#ddd '});
        $("input[name=snmp3_auth_user]").attr("disabled", true);

        $("input[name=snmp3_auth_pass]")
            .css({backgroundColor: '#ddd '});
        $("input[name=snmp3_auth_pass]").attr("disabled", true);

        $("#snmp3_privacy_method").css({backgroundColor: '#ddd '});
        $("#snmp3_privacy_method").attr("disabled", true);

        $("input[name=snmp3_privacy_pass]")
            .css({backgroundColor: '#ddd '});
        $("input[name=snmp3_privacy_pass]").attr("disabled", true);

        $("#snmp3_auth_method").css({backgroundColor: '#ddd '});
        $("#snmp3_auth_method").attr("disabled", true);

        $("#snmp3_security_level").css({backgroundColor: '#ddd '});
        $("#snmp3_security_level").attr("disabled", true);

        $("#command_text").css({backgroundColor: '#ddd '});
        $("#command_text").attr("disabled", true);

        $("#command_credential_identifier")
            .css({backgroundColor: '#ddd '});
        $("#command_credential_identifier").attr("disabled", true);

        $("#command_os").css({backgroundColor: '#ddd '});
        $("#command_os").attr("disabled", true);
    }
    // type 15-18- SNMP
    if ((document.component.type.value > 14) && (document.component.type.value < 19 )) {
        document.component.snmp_oid.style.background="#fff";
        document.component.snmp_oid.disabled=false;

        document.getElementById('text-snmp_community').style.background="#fff";
        document.getElementById('text-snmp_community').disabled=false;
        document.component.snmp_oid.style.background="#fff";
        document.component.snmp_oid.disabled=false;
        document.component.tcp_send.style.background="#ddd ";
        document.component.tcp_send.disabled=true;
        document.component.tcp_rcv.style.background="#ddd ";
        document.component.tcp_rcv.disabled=true;
        document.component.tcp_port.style.background="#fff";
        document.component.tcp_port.disabled=false;

        document.component.snmp_version.style.background="#fff";
        document.component.snmp_version.disabled=false;
        document.component.snmp3_auth_user.style.background="#fff";
        document.component.snmp3_auth_user.disabled=false;
        document.component.snmp3_auth_pass.background="#fff";
        document.component.snmp3_auth_pass.disabled=false;
        document.component.snmp3_privacy_method.style.background="#fff";
        document.component.snmp3_privacy_method.disabled=false;
        document.component.snmp3_privacy_pass.style.background="#fff";
        document.component.snmp3_privacy_pass.disabled=false;
        document.component.snmp3_auth_method.style.background="#fff";
        document.component.snmp3_auth_method.disabled=false;
        document.component.snmp3_security_level.style.background="#fff";
        document.component.snmp3_security_level.disabled=false;

        document.component.command_text.style.background="#ddd";
        document.component.command_text.style.disabled=true;
        document.component.command_credential_identifier.style.background="#ddd";
        document.component.command_credential_identifier.disabled=true;
        document.component.command_os.style.background="#ddd";
        document.component.command_os.disabled=true;

        $("#snmp_version" ).trigger("change");
    }

    if ((document.component.type.value >= 34) && (document.component.type.value <= 37 )) {
        document.component.snmp_oid.style.background="#ddd";
        document.component.snmp_oid.disabled=true;
        document.getElementById('text-snmp_community').style.background="#ddd";
        document.getElementById('text-snmp_community').disabled=true;
        document.component.snmp_oid.style.background="#ddd";
        document.component.snmp_oid.disabled=true;
        document.component.snmp_version.style.background="#ddd";
        document.component.snmp_version.disabled=true;

        document.component.tcp_send.style.background="#ddd";
        document.component.tcp_send.disabled=true;
        document.component.tcp_rcv.style.background="#ddd";
        document.component.tcp_rcv.disabled=true;
        document.component.tcp_port.style.background="#fff";
        document.component.tcp_port.disabled=false;

        document.component.snmp3_auth_user.style.background="#ddd ";
        document.component.snmp3_auth_user.disabled=true;
        document.component.snmp3_auth_pass.background="#ddd ";
        document.component.snmp3_auth_pass.disabled=true;
        document.component.snmp3_privacy_method.style.background="#ddd ";
        document.component.snmp3_privacy_method.disabled=true;
        document.component.snmp3_privacy_pass.style.background="#ddd ";
        document.component.snmp3_privacy_pass.disabled=true;
        document.component.snmp3_auth_method.style.background="#ddd ";
        document.component.snmp3_auth_method.disabled=true;
        document.component.snmp3_security_level.style.background="#ddd ";
        document.component.snmp3_security_level.disabled=true;

        document.component.command_text.style.background="#fff";
        document.component.command_text.style.disabled=false;
        document.component.command_credential_identifier.style.background="#fff";
        document.component.command_credential_identifier.disabled=false;
        document.component.command_os.style.background="#fff";
        document.component.command_os.disabled=false;
    }

    // type 6-7 - ICMP
    if ((document.component.type.value == 6) || (document.component.type.value == 7)) {
        document.component.snmp_oid.style.background="#ddd ";
        document.component.snmp_oid.disabled=true;
        document.getElementById('text-snmp_community').style.background="#ddd";
        document.getElementById('text-snmp_community').disabled=true;
        document.component.snmp_oid.style.background="#ddd ";
        document.component.snmp_oid.disabled=true;
        document.component.tcp_send.style.background="#ddd ";
        document.component.tcp_send.disabled=true;
        document.component.tcp_rcv.style.background="#ddd ";
        document.component.tcp_rcv.disabled=true;
        document.component.tcp_port.style.background="#ddd ";
        document.component.tcp_port.disabled=true;

        document.component.snmp_version.style.background="#ddd ";
        document.component.snmp_version.disabled=true;
        document.component.snmp3_auth_user.style.background="#ddd ";
        document.component.snmp3_auth_user.disabled=true;
        document.component.snmp3_auth_pass.background="#ddd ";
        document.component.snmp3_auth_pass.disabled=true;
        document.component.snmp3_privacy_method.style.background="#ddd ";
        document.component.snmp3_privacy_method.disabled=true;
        document.component.snmp3_privacy_pass.style.background="#ddd ";
        document.component.snmp3_privacy_pass.disabled=true;
        document.component.snmp3_auth_method.style.background="#ddd ";
        document.component.snmp3_auth_method.disabled=true;
        document.component.snmp3_security_level.style.background="#ddd ";
        document.component.snmp3_security_level.disabled=true;

        document.component.command_text.style.background="#ddd";
        document.component.command_text.style.disabled=true;
        document.component.command_credential_identifier.style.background="#ddd";
        document.component.command_credential_identifier.disabled=true;
        document.component.command_os.style.background="#ddd";
        document.component.command_os.disabled=true;
    }
    // type 8-11 - TCP
    if ((document.component.type.value > 7) && (document.component.type.value < 12)) {
        document.component.snmp_oid.style.background="#ddd ";
        document.component.snmp_oid.disabled=true;
        document.getElementById('text-snmp_community').style.background="#ddd ";
        document.getElementById('text-snmp_community').disabled=true;
        document.component.tcp_send.style.background="#fff";
        document.component.tcp_send.disabled=false;
        document.component.tcp_rcv.style.background="#fff";
        document.component.tcp_rcv.disabled=false;
        document.component.tcp_port.style.background="#fff";
        document.component.tcp_port.disabled=false;

        document.component.snmp_version.style.background="#ddd ";
        document.component.snmp_version.disabled=true;
        document.component.snmp3_auth_user.style.background="#ddd ";
        document.component.snmp3_auth_user.disabled=true;
        document.component.snmp3_auth_pass.background="#ddd ";
        document.component.snmp3_auth_pass.disabled=true;
        document.component.snmp3_privacy_method.style.background="#ddd ";
        document.component.snmp3_privacy_method.disabled=true;
        document.component.snmp3_privacy_pass.style.background="#ddd ";
        document.component.snmp3_privacy_pass.disabled=true;
        document.component.snmp3_auth_method.style.background="#ddd ";
        document.component.snmp3_auth_method.disabled=true;
        document.component.snmp3_security_level.style.background="#ddd ";
        document.component.snmp3_security_level.disabled=true;

        document.component.command_text.style.background="#ddd";
        document.component.command_text.style.disabled=true;
        document.component.command_credential_identifier.style.background="#ddd";
        document.component.command_credential_identifier.disabled=true;
        document.component.command_os.style.background="#ddd";
        document.component.command_os.disabled=true;
    }
    // type 12 - UDP
    if (document.component.type.value == 12) {
        document.component.snmp_oid.style.background="#ddd ";
        document.component.snmp_oid.disabled=true;
        document.getElementById('text-snmp_community').style.background="#ddd ";
        document.getElementById('text-snmp_community').disabled=true;
        document.component.tcp_send.style.background="#fff";
        document.component.tcp_send.disabled=false;
        document.component.tcp_rcv.style.background="#fff";
        document.component.tcp_rcv.disabled=false;
        document.component.tcp_port.style.background="#fff";
        document.component.tcp_port.disabled=false;

        document.component.snmp_version.style.background="#ddd ";
        document.component.snmp_version.disabled=true;
        document.component.snmp3_auth_user.style.background="#ddd ";
        document.component.snmp3_auth_user.disabled=true;
        document.component.snmp3_auth_pass.background="#ddd ";
        document.component.snmp3_auth_pass.disabled=true;
        document.component.snmp3_privacy_method.style.background="#ddd ";
        document.component.snmp3_privacy_method.disabled=true;
        document.component.snmp3_privacy_pass.style.background="#ddd ";
        document.component.snmp3_privacy_pass.disabled=true;
        document.component.snmp3_auth_method.style.background="#ddd ";
        document.component.snmp3_auth_method.disabled=true;
        document.component.snmp3_security_level.style.background="#ddd ";
        document.component.snmp3_security_level.disabled=true;

        document.component.command_text.style.background="#ddd";
        document.component.command_text.style.disabled=true;
        document.component.command_credential_identifier.style.background="#ddd";
        document.component.command_credential_identifier.disabled=true;
        document.component.command_os.style.background="#ddd";
        document.component.command_os.disabled=true;
    }
}

$(document).ready (function () {
    $("#right").click (function () {
        jQuery.each($("select[name='id_tag_available[]'] option:selected"), function (key, value) {
            tag_name = $(value).html();
            if (tag_name != <?php echo "'".__('None')."'"; ?>) {
                id_tag = $(value).attr('value');
                $("select[name='id_tag_selected[]']").append($("<option></option>").val(id_tag).html('<i>' + tag_name + '</i>'));
                $("#id_tag_available").find("option[value='" + id_tag + "']").remove();
                $("#id_tag_selected").find("option[value='']").remove();
                if($("#id_tag_available option").length == 0) {
                    $("select[name='id_tag_available[]']").append($("<option></option>").val('').html('<i><?php echo __('None'); ?></i>'));
                }
            }
        });
    });

    $("#left").click (function () {
        jQuery.each($("select[name='id_tag_selected[]'] option:selected"), function (key, value) {
                tag_name = $(value).html();
                if (tag_name != <?php echo "'".__('None')."'"; ?>) {
                    id_tag = $(value).attr('value');
                    $("select[name='id_tag_available[]']").append($("<option>").val(id_tag).html('<i>' + tag_name + '</i>'));
                    $("#id_tag_selected").find("option[value='" + id_tag + "']").remove();
                    $("#id_tag_available").find("option[value='']").remove();
                    if($("#id_tag_selected option").length == 0) {
                        $("select[name='id_tag_selected[]']").append($("<option></option>").val('').html('<i><?php echo __('None'); ?></i>'));
                    }
                }
        });
    });

    $("#submit-crt").click(function () {
        $('#id_tag_selected option').map(function() {
            $(this).prop('selected', true);
        });
    });

    $("#submit-upd").click(function () {
        $('#id_tag_selected option').map(function() {
            $(this).prop('selected', true);
        });
    });

    if ($("#snmp_version").val() == "3") {
        $("input[name=snmp3_auth_user]").css({backgroundColor: '#fff'});
        $("input[name=snmp3_auth_user]").removeAttr('disabled');

        $("input[name=snmp3_auth_pass]").css({backgroundColor: '#fff'});
        $("input[name=snmp3_auth_pass]").removeAttr('disabled');

        $("#snmp3_privacy_method").css({backgroundColor: '#fff'});
        $("#snmp3_privacy_method").removeAttr('disabled');

        $("input[name=snmp3_privacy_pass]").css({backgroundColor: '#fff'});
        $("input[name=snmp3_privacy_pass]").removeAttr('disabled');

        $("#snmp3_auth_method").css({backgroundColor: '#fff'});
        $("#snmp3_auth_method").removeAttr('disabled');

        $("#snmp3_security_level").css({backgroundColor: '#fff'});
        $("#snmp3_security_level").removeAttr('disabled');

        $("input[name=active_snmp_v3]").val(1);
        $("input[name=snmp_community]").css({backgroundColor: '#ddd'});
        $("input[name=snmp_community]").attr("disabled",true);
    }
    else {
        $("input[name=snmp3_auth_user]").val("");
        $("input[name=snmp3_auth_user]").css({backgroundColor: '#ddd'});
        $("input[name=snmp3_auth_user]").attr("disabled", true);

        $("input[name=snmp3_auth_pass]").val("");
        $("input[name=snmp3_auth_pass]").css({backgroundColor: '#ddd'});
        $("input[name=snmp3_auth_pass]").attr("disabled", true);

        $("#snmp3_privacy_method").css({backgroundColor: '#ddd'});
        $("#snmp3_privacy_method").attr("disabled", true);

        $("input[name=snmp3_privacy_pass]").val("");
        $("input[name=snmp3_privacy_pass]").css({backgroundColor: '#ddd'});
        $("input[name=snmp3_privacy_pass]").attr("disabled", true);

        $("#snmp3_auth_method").css({backgroundColor: '#ddd'});
        $("#snmp3_auth_method").attr("disabled", true);

        $("#snmp3_security_level").css({backgroundColor: '#ddd'});
        $("#snmp3_security_level").attr("disabled", true);

        $("input[name=active_snmp_v3]").val(0);
    }

    $("#snmp_version").change(function () {
        if (this.value == "3") {
            $("input[name=snmp3_auth_user]").css({backgroundColor: '#fff'});
            $("input[name=snmp3_auth_user]").removeAttr('disabled');

            $("input[name=snmp3_auth_pass]").css({backgroundColor: '#fff'});
            $("input[name=snmp3_auth_pass]").removeAttr('disabled');

            $("#snmp3_privacy_method").css({backgroundColor: '#fff'});
            $("#snmp3_privacy_method").removeAttr('disabled');

            $("input[name=snmp3_privacy_pass]").css({backgroundColor: '#fff'});
            $("input[name=snmp3_privacy_pass]").removeAttr('disabled');

            $("#snmp3_auth_method").css({backgroundColor: '#fff'});
            $("#snmp3_auth_method").removeAttr('disabled');

            $("#snmp3_security_level").css({backgroundColor: '#fff'});
            $("#snmp3_security_level").removeAttr('disabled');

            $("input[name=active_snmp_v3]").val(1);
            $("input[name=snmp_community]").css({backgroundColor: '#ddd'});
            $("input[name=snmp_community]").attr("disabled",true);
        }
        else {
            $("input[name=snmp3_auth_user]").val("");
            $("input[name=snmp3_auth_user]").css({backgroundColor: '#ddd'});
            $("input[name=snmp3_auth_user]").attr("disabled", true);

            $("input[name=snmp3_auth_pass]").val("");
            $("input[name=snmp3_auth_pass]").css({backgroundColor: '#ddd'});
            $("input[name=snmp3_auth_pass]").attr("disabled", true);

            $("#snmp3_privacy_method").css({backgroundColor: '#ddd'});
            $("#snmp3_privacy_method").attr("disabled", true);

            $("input[name=snmp3_privacy_pass]").val("");
            $("input[name=snmp3_privacy_pass]").css({backgroundColor: '#ddd'});
            $("input[name=snmp3_privacy_pass]").attr("disabled", true);

            $("#snmp3_auth_method").css({backgroundColor: '#ddd'});
            $("#snmp3_auth_method").attr("disabled", true);

            $("#snmp3_security_level").css({backgroundColor: '#ddd'});
            $("#snmp3_security_level").attr("disabled", true);

            $("input[name=active_snmp_v3]").val(0);
            $("input[name=snmp_community]").css({backgroundColor: '#fff'});
            $("input[name=snmp_community]").removeAttr('disabled');
        }
    });

    $("#type"). change(function () {
        if ($("#snmp_version").val() == "3") {
            $("input[name=snmp3_auth_user]").css({backgroundColor: '#fff'});
            $("input[name=snmp3_auth_user]").removeAttr('disabled');

            $("input[name=snmp3_auth_pass]").css({backgroundColor: '#fff'});
            $("input[name=snmp3_auth_pass]").removeAttr('disabled');

            $("#snmp3_privacy_method").css({backgroundColor: '#fff'});
            $("#snmp3_privacy_method").removeAttr('disabled');

            $("input[name=snmp3_privacy_pass]").css({backgroundColor: '#fff'});
            $("input[name=snmp3_privacy_pass]").removeAttr('disabled');

            $("#snmp3_auth_method").css({backgroundColor: '#fff'});
            $("#snmp3_auth_method").removeAttr('disabled');

            $("#snmp3_security_level").css({backgroundColor: '#fff'});
            $("#snmp3_security_level").removeAttr('disabled');

            $("input[name=active_snmp_v3]").val(1);
            $("input[name=snmp_community]").css({backgroundColor: '#ddd'});
            $("input[name=snmp_community]").attr("disabled",true);
        }
        else {
            $("input[name=snmp3_auth_user]").val("");
            $("input[name=snmp3_auth_user]").css({backgroundColor: '#ddd'});
            $("input[name=snmp3_auth_user]").attr("disabled", true);

            $("input[name=snmp3_auth_pass]").val("");
            $("input[name=snmp3_auth_pass]").css({backgroundColor: '#ddd'});
            $("input[name=snmp3_auth_pass]").attr("disabled", true);

            $("#snmp3_privacy_method").css({backgroundColor: '#ddd'});
            $("#snmp3_privacy_method").attr("disabled", true);

            $("input[name=snmp3_privacy_pass]").val("");
            $("input[name=snmp3_privacy_pass]").css({backgroundColor: '#ddd'});
            $("input[name=snmp3_privacy_pass]").attr("disabled", true);

            $("#snmp3_auth_method").css({backgroundColor: '#ddd'});
            $("#snmp3_auth_method").attr("disabled", true);

            $("#snmp3_security_level").css({backgroundColor: '#ddd'});
            $("#snmp3_security_level").attr("disabled", true);

            $("input[name=active_snmp_v3]").val(0);
        }
    });

    $("#snmp_version" ).trigger("change");

    if ($('#checkbox-warning_inverse').prop('checked') === true) {
    $('#percentage_warning').hide();
    }

    if ($('#checkbox-critical_inverse').prop('checked') === true) {
        $('#percentage_critical').hide();
    }

    if ($('#checkbox-percentage_warning').prop('checked') === true) {
        $('#warning_inverse').hide();
    }

    if ($('#checkbox-percentage_critical').prop('checked') === true) {
        $('#critical_inverse').hide();
    }

    $('#checkbox-warning_inverse').change (function() {
        if ($('#checkbox-warning_inverse').prop('checked') === true){
            $('#checkbox-percentage_warning').prop('checked', false);
            $('#percentage_warning').hide();
        } else {
            $('#percentage_warning').show();
        }
    }); 

    $('#checkbox-critical_inverse').change (function() {
        if ($('#checkbox-critical_inverse').prop('checked') === true){
            $('#checkbox-percentage_critical').prop('checked', false);
            $('#percentage_critical').hide();
        } else {
            $('#percentage_critical').show();
        }
    });

    $('#checkbox-percentage_warning').change (function() {
        if ($('#checkbox-percentage_warning').prop('checked') === true){
            $('#checkbox-warning_inverse').prop('checked', false);
            $('#warning_inverse').hide();
        } else {
            $('#warning_inverse').show();
        }
    });

    $('#checkbox-percentage_critical').change (function() {
        if ($('#checkbox-percentage_critical').prop('checked') === true){
            $('#checkbox-critical_inverse').prop('checked', false);
            $('#critical_inverse').hide();
        }
            else {
            $('#critical_inverse').show();
        }   
    });

});

<?php
if ($id_component_type == 2) {
    ?>
    type_change ();
    <?php
}
?>
//-->
</script>
