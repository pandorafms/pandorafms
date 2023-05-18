<?php
/**
 * Web module manager editor.
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

// Begin.
enterprise_include_once('include/functions_policies.php');

$disabledBecauseInPolicy = false;
$disabledTextBecauseInPolicy = '';
$classdisabledBecauseInPolicy = '';
$page             = get_parameter('page', '');
$id_policy_module = (int) get_parameter('id_policy_module');

if (strstr($page, 'policy_modules') === false) {
    if ((bool) $config['enterprise_installed'] === true) {
        if (policies_is_module_linked($id_agent_module) == 1) {
            $disabledBecauseInPolicy = 1;
        } else {
            $disabledBecauseInPolicy = 0;
        }
    } else {
        $disabledBecauseInPolicy = false;
    }

    if ((bool) $disabledBecauseInPolicy === true) {
        $disabledTextBecauseInPolicy = 'disabled = "disabled"';
        $classdisabledBecauseInPolicy = 'readonly';
    }
}

global $id_agente;

$extra_title = __('Web server module');

// Div for modal.
html_print_div(
    [
        'id'    => 'modal',
        'style' => 'display: none;',
    ]
);

if (is_int($id_agent_module) === true && $id_agent_module !== 0) {
    include_once $config['homedir'].'/include/ajax/web_server_module_debug.php';
}

define('ID_NETWORK_COMPONENT_TYPE', 7);

if (empty($tcp_port) === true && $id_agent_module !== 0) {
    $tcp_port = 80;
}

// Plugin_server is the browser id.
if (empty($plugin_user) === true && $id_agent_module !== 0) {
    $plugin_user = get_product_name().' / Webcheck';
}

// Plugin_server is the referer.
if (empty($plugin_pass) === true && $id_agent_module !== 0) {
    $plugin_pass = 1;
}

if (empty($edit_module) === true) {
    add_component_selection(ID_NETWORK_COMPONENT_TYPE);
}

$data = [];
$data[0] = __('Web checks');
$suc_err_check = ' <span id="check_conf_suc" class="checks invisible">'.html_print_image('/images/ok.png', true).'</span>';
$suc_err_check .= ' <span id="check_conf_err" class="checks invisible">'.html_print_image('/images/error_red.png', true).'</span>';
$data[1] = $suc_err_check;
push_table_simple($data, 'header_web_checks');

$adopt = false;
if (isset($id_agent_module) === true && $id_agent_module !== 0) {
    $adopt = enterprise_hook('policies_is_module_adopt', [$id_agent_module]);
}

if ($id_policy_module > 0) {
    $module = enterprise_hook('policies_get_module', [$id_policy_module]);
    $plugin_parameter = $module['plugin_parameter'];
} else if ($id_agent_module > 0) {
    $module = modules_get_agentmodule($id_agent_module);
    $plugin_parameter = $module['plugin_parameter'];
} else {
    $plugin_parameter = '';
}

$plugin_parameter_split = explode('&#x0a;', $plugin_parameter);
$plugin_parameter_final_split = '';

foreach ($plugin_parameter_split as $key => $value) {
    if (strpos($value, 'http_auth_user') === false && strpos($value, 'http_auth_pass') === false) {
        $plugin_parameter_final_split .= $value.'&#x0a;';
    }

    if (strpos($value, 'http_auth_user') !== false) {
        $plugin_parameter_http_user = str_replace('http_auth_user&#x20;', '', $value);
    }

    if (strpos($value, 'http_auth_pass') !== false) {
        $plugin_parameter_http_pass = str_replace('http_auth_pass&#x20;', '', $value);
    }
}

if ((bool) $adopt === false) {
    $textareaPluginParameter = html_print_textarea(
        'plugin_parameter',
        15,
        65,
        $plugin_parameter_final_split,
        $disabledTextBecauseInPolicy,
        true,
        'resizev'
    );
} else {
    $textareaPluginParameter = html_print_textarea(
        'plugin_parameter',
        15,
        65,
        $plugin_parameter_final_split,
        false,
        true
    );
}

$data = [];
$data[0] = $textareaPluginParameter;
push_table_simple($data, 'textarea_web_checks');

// Disable debug button if module has not started.
if ($id_agent_module > 0
    && db_get_value_filter(
        'debug_content',
        'tagente_modulo',
        ['id_agente_modulo' => $id_agent_module]
    ) !== null
) {
    $disableDebug = false;
    $hintDebug = __('Debug remotely this module');
} else {
    $disableDebug = true;
    $hintDebug = __('Debug this module once it has been initialized');
}

$actionButtons = html_print_button(
    __('Load basic'),
    'btn_loadbasic',
    false,
    '',
    [
        'icon' => 'cog',
        'mode' => 'mini secondary',
    ],
    true
).ui_print_help_tip(__('Load a basic structure on Web Checks'), true);
$actionButtons .= html_print_button(
    __('Check'),
    'btn_checkconf',
    false,
    '',
    [
        'icon' => 'update',
        'mode' => 'mini secondary',
    ],
    true
).ui_print_help_tip(__('Check the correct structure of the WebCheck'), true);
$actionButtons .= html_print_button(
    __('Debug'),
    'btn_debugModule',
    $disableDebug,
    '',
    [
        'icon' => 'cog',
        'mode' => 'mini secondary ',
    ],
    true
).ui_print_help_tip($hintDebug, true);

$data = [];
$data[0] = $actionButtons;
push_table_simple($data, 'buttons_web_checks');

$http_checks_type = [
    0 => 'Anyauth',
    1 => 'NTLM',
    2 => 'DIGEST',
    3 => 'BASIC',
];

$data = [];
$data[0] = __('Check type');
$data[1] = html_print_select($http_checks_type, 'tcp_port', $tcp_port, false, '', '', true, false, false);

push_table_advanced($data, 'web_0');

$data = [];
$data[0] = __('Requests');
$data[1] = html_print_input_text('plugin_pass', $plugin_pass, '', 10, 0, true, $disabledBecauseInPolicy, false, '', $classdisabledBecauseInPolicy);
$data[2] = '';
$data[3] = __('Agent browser id');
$data[4] = html_print_input_text('plugin_user', $plugin_user, '', 30, 0, true, $disabledBecauseInPolicy, false, '', $classdisabledBecauseInPolicy);

push_table_advanced($data, 'web_1');

$data = [];
$data[0] = __('HTTP auth (login)');
$data[1] = html_print_input_text('http_user', $plugin_parameter_http_user, '', 10, 0, true, $disabledBecauseInPolicy, false, '', $classdisabledBecauseInPolicy);
$data[2] = '';
$data[3] = __('HTTP auth (password)');
$data[4] = html_print_input_password('http_pass', $plugin_parameter_http_pass, '', 30, 0, true, $disabledBecauseInPolicy, false, '', $classdisabledBecauseInPolicy);

push_table_advanced($data, 'web_2');

$data = [];

$data[0] = __('Proxy URL');
$data[1] = html_print_input_text('snmp_oid', $snmp_oid, '', 30, 0, true, $disabledBecauseInPolicy, false, '', $classdisabledBecauseInPolicy);
$data[2] = $data[3] = $data[4] = '';
push_table_advanced($data, 'web_3');

$data = [];

$data[0] = __('Proxy auth (login)');
$data[1] = html_print_input_text('tcp_send', $tcp_send, '', 30, 0, true, $disabledBecauseInPolicy, false, '', $classdisabledBecauseInPolicy);

$data[2] = '';
$data[3] = __('Proxy auth (pass)');
$data[4] = html_print_input_password('tcp_rcv', $tcp_rcv, '', 30, 0, true, $disabledBecauseInPolicy, false, '', $classdisabledBecauseInPolicy);

push_table_advanced($data, 'web_4');

$data = [];

$data[0] = __('Proxy auth (server)');
$data[1] = html_print_input_text('ip_target', $ip_target, '', 30, 100, true, $disabledBecauseInPolicy, false, '', $classdisabledBecauseInPolicy);

$data[2] = '';
$data[3] = __('Proxy auth (realm)');
$data[4] = html_print_input_text('snmp_community', $snmp_community, '', 30, 100, true, $disabledBecauseInPolicy, false, '', $classdisabledBecauseInPolicy);

push_table_advanced($data, 'web_5');

// Add some strings to be used from javascript
$texts = [
    'lines_before_begin' => __('First line must be "task_begin"'),
    'missed_begin'       => __('Webchecks configuration is empty'),
    'missed_end'         => __('Last line must be "task_end"'),
    'lines_after_end'    => __('Last line must be "task_end"'),
    'unknown_token'      => __("There is a line with a unknown token 'token_fail'."),
    'missed_get_post'    => __("There isn't get or post"),
    'correct'            => __('Web checks are built correctly'),
];

foreach ($texts as $code => $text) {
    echo '<span class="invisible" id="'.$code.'">'.$text.'</span>';
}
?>
<script type="text/javascript">
    var supported_tokens = [
        "task_begin",
        "post",
        "variable_name",
        "variable_value",
        "cookie",
        "resource",
        "get",
        "check_string",
        "check_not_string",
        "get_content_advanced",
        "get_content",
        "debug",
        "task_end",
        "head"
    ];

    $(document).ready(function() {

        var plugin_parameter = $("#textarea_plugin_parameter");

        $(plugin_parameter).keyup(function() {

            // Check and fill textbox. 
            if ($(plugin_parameter).val() == '') {
                $('#button-btn_loadbasic').removeAttr('disabled');
            } else {
                $('#button-btn_loadbasic').attr('disabled', 'disabled');
            }
        });

        $('#button-btn_loadbasic').click(function() {
            if ($(plugin_parameter).val() != '') {
                return;
            }

            $(plugin_parameter).val(
                'task_begin\ncookie 0\nresource 0\ntask_end');

            $('#button-btn_loadbasic').attr('disabled', 'disabled');

            // Hide success and error indicators
            $('.checks').hide();
        });

        $('#button-btn_checkconf').click(function() {
            var msg_error = '';

            if (plugin_parameter.val() == '') {
                msg_error = 'missed_begin';
            } else {
                var lines = plugin_parameter.val().split("\n");

                var started = false;
                var ended = false;
                var lines_after_end = false;
                var lines_before_begin = false;
                var token_fail = false;
                var token_get_post = false;
                var token_check = true;
                var str_token_fail = '';

                for (i = 0; i < lines.length; i++) {
                    if (lines[i].match(/^\s*$/)) {
                        // Empty line
                        continue;
                    } else if (!started) {
                        if (lines[i].match(/^task_begin\s*$/)) {
                            started = true;
                        } else {
                            // Found a not empty line before task_begin
                            lines_before_begin = true;
                            break;
                        }
                    }

                    if (lines[i].match(/^task_end\s*$/)) {
                        ended = true;
                        continue;
                    }

                    //Check token is correct
                    if (!lines[i].match(/^([\s])*[#]/)) {

                        var token = lines[i].match(/^([^\s]+)\s*/);

                        if (typeof(token) == 'object') {
                            token = token[1];

                            if ((!token_get_post) && (token == 'get' || token == 'post' || token == 'header')) {
                                token_get_post = true;
                                continue;
                            }
                            if (token == 'check_string') {
                                if (token_get_post) {
                                    token_check = true;
                                    continue;
                                } else {
                                    token_check = false;
                                    continue;
                                }
                            }
                            if ($.inArray(token, supported_tokens) == -1) {
                                token_fail = true;
                                str_token_fail = token;
                                break;
                            }
                        }
                    }
                }
            }

            var msg_error = '';

            if (token_fail) {
                var temp_msg = $("#unknown_token").html();
                temp_msg = temp_msg.replace(/['](.*)[']/, "'" + str_token_fail + "'");

                $("#unknown_token").html(temp_msg);

                msg_error = 'unknown_token';
            } else if (lines_before_begin) {
                msg_error = 'lines_before_begin';
            } else if (!started) {
                msg_error = 'missed_begin';
            } else if (!ended) {
                msg_error = 'missed_end';
            } else if (lines_after_end) {
                msg_error = 'lines_after_end';
            } else if (!token_check) {
                msg_error = 'missed_get_post';
            } else {
                msg_error = 'correct';
            }


            if (msg_error == 'correct') {
                $('#check_conf_suc').find('img').eq(0)
                    .attr('title', $('#' + msg_error).html());

                $('#check_conf_err').hide();
                $('#check_conf_suc').show();
            } else {
                $('#check_conf_err').find('img').eq(0)
                    .attr('title', $('#' + msg_error).html());

                $('#check_conf_suc').hide();
                $('#check_conf_err').show();
            }
        });

        $(plugin_parameter).trigger('keyup');
    });

</script>