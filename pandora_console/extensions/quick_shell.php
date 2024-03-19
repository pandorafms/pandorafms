<?php
/**
 * Quick Shell extension.
 *
 * @category   Extension
 * @package    Pandora FMS
 * @subpackage QuickShell
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

// Begin.
global $config;

require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/godmode/wizards/Wizard.main.php';
require_once $config['homedir'].'/include/functions_cron_task.php';


/**
 * Undocumented function
 *
 * @param string $url    Url.
 * @param array  $params Params.
 *
 * @return mixed Result
 */
function curl(string $url, array $params)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    $get_result = curl_exec($ch);

    curl_close($ch);

    return $get_result;
}


/**
 * Show Quick Shell interface.
 *
 * @return void
 */
function quickShell()
{
    global $config;

    check_login();

    if (check_acl($config['id_user'], 0, 'PM') === false) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access Profile Management'
        );
        include 'general/noaccess.php';
        return;
    }

    $form_sent = get_parameter('form-sent', false);
    $method = get_parameter('method', null);

    $setup_anchor = html_print_anchor(
        [
            'href'    => 'index.php?sec=gsetup&sec2=godmode/setup/setup&section=quickshell',
            'content' => __('GoTTY setup'),
        ],
        true
    );

    if ((bool) $config['gotty_ssh_enabled'] === false
        && (bool) $config['gotty_telnet_enabled'] === false
    ) {
        ui_print_warning_message(__('Please, enable GoTTY in %s', $setup_anchor));
        return;
    }

    $agent_id = get_parameter('id_agente', 0);
    $username = get_parameter('username', null);
    $method_port = get_parameter('port', null);

    // Retrieve main IP Address.
    $agent_address = agents_get_address($agent_id);

    ui_require_css_file('wizard');
    ui_require_css_file('discovery');

    // Build URL args.
    if ($method === 'ssh') {
        // SSH.
        $args .= '&arg='.$agent_address.'&arg='.$method_port.'&arg='.$username;
    } else if ($method == 'telnet') {
        // Telnet.
        $args .= '&arg='.$agent_address.'&arg='.$method_port;
    }

    $connectionURL = buildConnectionURL($method);
    $gotty_addr = $connectionURL.$args;

    // Username. Retrieve from form.
    if ($form_sent === false) {
        // No username provided, ask for it.
        $wiz = new Wizard();

        $method_fields = [];

        if ($config['gotty_telnet_enabled']) {
            $method_fields['telnet'] = __('Telnet');
            $port_value = 23;
        }

        if ($config['gotty_ssh_enabled']) {
            $method_fields['ssh'] = __('SSH');
            $port_value = 22;
        }

        $method_script = "
            var wizard = document.querySelector('.wizard');
            p=22;
            wizard.querySelector('ul > li').classList.remove('invisible_important');
            wizard.querySelector('ul > li').classList.add('visible');
            if(this.value == 'telnet') {
                p=23;
                wizard.querySelector('ul > li').classList.remove('visible');
                wizard.querySelector('ul > li').classList.add('invisible_important');
                $('#text-username').prop('required', false);
            } else {
                $('#text-username').prop('required', true);
            }
            $('#text-port').val(p);";

        $wiz->printForm(
            [
                'form'   => [
                    'action' => '#',
                    'class'  => 'wizard',
                    'method' => 'post',
                    'id'     => 'connect_form',
                ],
                'inputs' => [
                    [
                        'label'     => __('Username'),
                        'arguments' => [
                            'type'     => 'text',
                            'name'     => 'username',
                            'required' => true,
                        ],
                    ],
                    [
                        'label'     => __('Port'),
                        'arguments' => [
                            'type'  => 'text',
                            'id'    => 'port',
                            'name'  => 'port',
                            'value' => $port_value,
                        ],
                    ],
                    [
                        'label'     => __('Method'),
                        'arguments' => [
                            'type'   => 'select',
                            'name'   => 'method',
                            'fields' => $method_fields,
                            'script' => $method_script,
                        ],
                    ],
                    [
                        'arguments' => [
                            'type'  => 'hidden',
                            'name'  => 'form-sent',
                            'value' => true,
                        ],
                    ],
                ],
            ],
            false,
            true
        );

        html_print_action_buttons(
            html_print_submit_button(
                __('Connect'),
                'submit',
                false,
                [
                    'icon' => 'cog',
                    'form' => 'connect_form',
                ],
                true
            )
        );
        return;
    }

    // Check gotty connection before trying to load iframe.
    $ch = curl_init($gotty_addr);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Maximum time for the entire request.
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    // Maximum time to establish a connection.
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

    curl_close($ch);

    if ($responseCode !== 200) {
        ui_print_error_message(__('Connection error. Please check your settings at %s', $setup_anchor));
        exit;
    }

    ?>
    <style>#terminal {
        width: 100%;
        margin: 0px;
        padding: 0;
        display: flex;
        flex-direction: column;
        min-height: calc(100vh - 205px);
      }
      #terminal > iframe {
        width:100%;
        height:100%;
        position: relative!important;
        flex-grow: 1;
        border: 0px;
      }
    </style>

    <div id="terminal"><iframe id="gotty-iframe" src="<?php echo $gotty_addr; ?>"></iframe></div>

    <?php

}


/**
 * Build Connection URL based on provided connection method.
 *
 * @param string $method Connection method (SSH/Telnet).
 *
 * @return string
 */
function buildConnectionURL($method)
{
    global $config;

    if (isset($config['gotty_ssh_use_ssl']) === false) {
        $config['gotty_ssh_use_ssl'] = '';
    }

    if (isset($config['gotty_telnet_use_ssl']) === false) {
        $config['gotty_telnet_use_ssl'] = '';
    }

    $address = (empty($config['gotty_addr']) === true) ? $_SERVER['SERVER_ADDR'] : $config['gotty_addr'];
    $use_ssl = ($method === 'ssh') ? $config['gotty_ssh_use_ssl'] : $config['gotty_telnet_use_ssl'];
    $protocol = ((bool) $use_ssl === true) ? 'https://' : 'http://';

    return $protocol.$address.':'.$config['gotty_port'].'/'.$config['gotty_connection_hash'].'/?arg='.$method;
}


/**
 * Provide an interface where configure all settings.
 *
 * @return void
 */
function quickShellSettings()
{
    global $config;

    ui_require_css_file('wizard');
    ui_require_css_file('discovery');

    // Gotty settings. Internal communication (WS).
    if (isset($config['gotty_ssh_enabled']) === false) {
        config_update_value('gotty_ssh_enabled', 1);
    }

    if (isset($config['gotty_telnet_enabled']) === false) {
        config_update_value('gotty_telnet_enabled', 0);
    }

    if (isset($config['gotty_host']) === false) {
        config_update_value('gotty_host', '127.0.0.1');
    }

    if (isset($config['gotty_port']) === false) {
        config_update_value('gotty_port', 8080);
    }

    $changes = 0;
    $critical = 0;

    // Parser.
    if (get_parameter('update_config', false) !== false) {
        $gotty_ssh_enabled = get_parameter(
            'gotty_ssh_enabled',
            0
        );

        $gotty_telnet_enabled = get_parameter(
            'gotty_telnet_enabled',
            0
        );

        $gotty_addr = get_parameter(
            'gotty_addr',
            ''
        );

        $gotty_port = get_parameter(
            'gotty_port',
            ''
        );

        $gotty_ssh_use_ssl = get_parameter(
            'gotty_ssh_use_ssl',
            false
        );

        $gotty_telnet_use_ssl = get_parameter(
            'gotty_telnet_use_ssl',
            false
        );

        if ($config['gotty_ssh_enabled'] != $gotty_ssh_enabled) {
            config_update_value('gotty_ssh_enabled', $gotty_ssh_enabled);
        }

        if ($config['gotty_telnet_enabled'] != $gotty_telnet_enabled) {
            config_update_value('gotty_telnet_enabled', $gotty_telnet_enabled);
        }

        if (isset($config['gotty_addr']) === false) {
            $config['gotty_addr'] = '';
        }

        if (isset($config['gotty_ssh_use_ssl']) === false) {
            $config['gotty_ssh_use_ssl'] = '';
        }

        if (isset($config['gotty_telnet_use_ssl']) === false) {
            $config['gotty_telnet_use_ssl'] = '';
        }

        if ($config['gotty_addr'] != $gotty_addr) {
            config_update_value('gotty_addr', $gotty_addr);
        }

        if ($config['gotty_port'] != $gotty_port) {
            // Mark gotty for restart (should kill the process in the current port).
            if ($config['restart_gotty_next_cron_port'] === ''
                || $config['restart_gotty_next_cron_port'] === null
            ) {
                config_update_value('restart_gotty_next_cron_port', $config['gotty_port']);
            }

            config_update_value('gotty_port', $gotty_port);
        }

        if ($config['gotty_ssh_use_ssl'] != $gotty_ssh_use_ssl) {
            config_update_value('gotty_ssh_use_ssl', $gotty_ssh_use_ssl);
        }

        if ($config['gotty_telnet_use_ssl'] != $gotty_telnet_use_ssl) {
            config_update_value('gotty_telnet_use_ssl', $gotty_telnet_use_ssl);
        }

        cron_task_start_gotty();
    }

    echo '<fieldset class="margin-bottom-10">';
    echo '<legend>'.__('GoTTY general parameters').'</legend>';

    $general_table = new StdClass();
    $general_table->data = [];
    $general_table->width = '100%';
    $general_table->class = 'filter-table-adv';
    $general_table->data = [];
    $general_table->style = [];
    $general_table->style[0] = 'width: 50%;';
    if (isset($config['gotty_addr']) === false) {
        $config['gotty_addr'] = '';
    }

    if (isset($config['gotty_ssh_enabled']) === false) {
        $config['gotty_ssh_enabled'] = '';
    }

    if (isset($config['gotty_ssh_use_ssl']) === false) {
        $config['gotty_ssh_use_ssl'] = '';
    }

    if (isset($disable_agentaccess) === false) {
        $disable_agentaccess = '';
    }

    if (isset($config['gotty_telnet_use_ssl']) === false) {
        $config['gotty_telnet_use_ssl'] = '';
    }

    $general_table->data[0][] = html_print_label_input_block(
        __('Address'),
        html_print_input_text(
            'gotty_addr',
            ($config['gotty_addr'] ?? ''),
            '',
            30,
            100,
            true
        )
    );

    $general_table->data[0][] = html_print_label_input_block(
        __('Port'),
        html_print_input_text(
            'gotty_port',
            $config['gotty_port'],
            '',
            30,
            100,
            true
        )
    );

    html_print_table($general_table);
    echo '</fieldset>';

    echo '<fieldset class="margin-bottom-10">';
    echo '<legend>'.__('GoTTY SSH connection parameters').'</legend>';

    $ssh_table = new StdClass();
    $ssh_table->data = [];
    $ssh_table->width = '100%';
    $ssh_table->class = 'filter-table-adv';
    $ssh_table->data = [];
    $ssh_table->style = [];
    $ssh_table->style[0] = 'width: 50%;';

    $ssh_table->data[0][] = html_print_label_input_block(
        __('Enable SSH method'),
        html_print_checkbox_switch(
            'gotty_ssh_enabled',
            1,
            $config['gotty_ssh_enabled'],
            true
        )
    );

    $ssh_table->data[1][] = html_print_label_input_block(
        __('Use SSL'),
        html_print_checkbox_switch(
            'gotty_ssh_use_ssl',
            1,
            ($config['gotty_ssh_use_ssl'] ?? false),
            true
        )
    );

    // Test.
    $row = [];
    $test_start = '<span id="test-gotty-spinner-ssh" class="invisible">&nbsp;'.html_print_image('images/spinner.gif', true).'</span>';
    $test_start .= '&nbsp;<span id="test-gotty-message-ssh" class="invisible"></span>';

    $ssh_table->data[3][] = html_print_button(
        __('Test'),
        'test-gotty-ssh',
        false,
        'handleTestSSH()',
        [
            'icon'  => 'cog',
            'mode'  => 'secondary',
            'style' => 'width: 115px;',
        ],
        true
    ).$test_start;

    html_print_table($ssh_table);

    echo '</fieldset>';

    echo '<fieldset class="margin-bottom-10">';
    echo '<legend>'.__('GoTTY telnet connection parameters').'</legend>';

    $telnet_table = new StdClass();
    $telnet_table->data = [];
    $telnet_table->width = '100%';
    $telnet_table->class = 'filter-table-adv';
    $telnet_table->data = [];
    $telnet_table->style = [];
    $telnet_table->style[0] = 'width: 50%;';

    $telnet_table->data[0][] = html_print_label_input_block(
        __('Enable telnet method'),
        html_print_checkbox_switch(
            'gotty_telnet_enabled',
            1,
            $config['gotty_telnet_enabled'],
            true
        )
    );

    $telnet_table->data[1][] = html_print_label_input_block(
        __('Use SSL'),
        html_print_checkbox_switch(
            'gotty_telnet_use_ssl',
            1,
            ($config['gotty_telnet_use_ssl'] ?? false),
            true
        )
    );

    // Test.
    $row = [];
    $test_start = '<span id="test-gotty-spinner-telnet" class="invisible">&nbsp;'.html_print_image('images/spinner.gif', true).'</span>';
    $test_start .= '&nbsp;<span id="test-gotty-message-telnet" class="invisible"></span>';

    $telnet_table->data[3][] = html_print_button(
        __('Test'),
        'test-gotty-telnet',
        false,
        'handleTestTelnet()',
        [
            'icon'  => 'cog',
            'mode'  => 'secondary',
            'style' => 'width: 115px;',
        ],
        true
    ).$test_start;

    html_print_table($telnet_table);
    html_print_input_hidden('update_config', 1);

    echo '</fieldset>';
}


if (is_ajax() === true) {
    $method = (string) get_parameter('method', '');

    if (empty($method) === false) {
        $address = buildConnectionURL($method);

        $ch = curl_init($address);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Maximum time for the entire request.
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);

        // Maximum time to establish a connection.
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        curl_exec($ch);
        $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response_code === 200) {
            $result = ['status' => 'success'];
        } else {
            $result = ['status' => 'error'];
        }

        echo json_encode($result);
        return;
    }

    $result = ['status' => 'error'];
    return;
}

// This extension is useful only if the agent has associated IP.
$agent_id = get_parameter('id_agente');
if (empty($agent_id) === false
    && get_parameter('sec2', '') == 'operation/agentes/ver_agente'
) {
    $address = agents_get_address($agent_id);
    if (empty($address) === false) {
        // Extension registration.
        extensions_add_opemode_tab_agent(
            // TabId.
            'quick_shell',
            // TabName.
            __('QuickShell'),
            // TabIcon.
            'images/quick-shell@svg.svg',
            // TabFunction.
            'quickShell',
            // Version.
            'N/A',
            // Acl.
            'PM'
        );
    }
}

echo '<script>';

echo 'var server_addr = "'.$_SERVER['SERVER_ADDR'].'";';
echo "function checkAddressReachability(method, callback) {
    $.ajax({
        url: 'ajax.php',
        data: {
            page: 'extensions/quick_shell',
            method
        },
        type: 'GET',
        async: false,
        dataType: 'json',
        success: function (data) {
            if (data.status === 'success') {
                callback(true);
            } else {
                callback(false);
            }
        },
        error: function () {
            callback(false);
        }
    });
}";

$handle_test_telnet = "var handleTestTelnet = function (event) {
    var gotty_addr = $('input#text-gotty_addr').val();
    var gotty_port = $('input#text-gotty_port').val();
    var gotty_telnet_use_ssl = $('input#checkbox-gotty_telnet_use_ssl').is(':checked');

    if (gotty_addr === '') {
        url = (gotty_telnet_use_ssl ? 'https://' : 'http://') + server_addr + ':' + gotty_port;    
    } else {
        url = (gotty_telnet_use_ssl ? 'https://' : 'http://') + gotty_addr + ':' + gotty_port;
    }

    var showLoadingImage = function () {
        $('#button-test-gotty-telnet').children('div').attr('class', 'subIcon cog rotation secondary mini');
    }

    var showSuccessImage = function () {
        $('#button-test-gotty-telnet').children('div').attr('class', 'subIcon tick secondary mini');
    }

    var showFailureImage = function () {
        $('#button-test-gotty-telnet').children('div').attr('class', 'subIcon fail secondary mini');
    }

    var hideMessage = function () {
        $('span#test-gotty-message-telnet').hide();
    }
    var showMessage = function () {
        $('span#test-gotty-message-telnet').show();
    }
    var changeTestMessage = function (message) {
        $('span#test-gotty-message-telnet').text(message);
    }

    var errorMessage = '".__('Unable to connect.')."';

    hideMessage();
    showLoadingImage();

    checkAddressReachability('telnet', function(isReachable) {
        if (isReachable) {
            showSuccessImage();
            hideMessage();
        } else {
            showFailureImage();
            changeTestMessage(errorMessage);
            showMessage();
        }
    });

};";

$handle_test_ssh = "var handleTestSSH = function (event) {
    var gotty_addr = $('input#text-gotty_addr').val();
    var gotty_port = $('input#text-gotty_port').val();
    var gotty_ssh_use_ssl = $('input#checkbox-gotty_ssh_use_ssl').is(':checked');

    if (gotty_addr === '') {
        url = (gotty_ssh_use_ssl ? 'https://' : 'http://') + server_addr + ':' + gotty_port;    
    } else {
        url = (gotty_ssh_use_ssl ? 'https://' : 'http://') + gotty_addr + ':' + gotty_port;
    }

    var showLoadingImage = function () {
        $('#button-test-gotty-ssh').children('div').attr('class', 'subIcon cog rotation secondary mini');
    }

    var showSuccessImage = function () {
        $('#button-test-gotty-ssh').children('div').attr('class', 'subIcon tick secondary mini');
    }

    var showFailureImage = function () {
        $('#button-test-gotty-ssh').children('div').attr('class', 'subIcon fail secondary mini');
    }

    var hideMessage = function () {
        $('span#test-gotty-message-ssh').hide();
    }
    var showMessage = function () {
        $('span#test-gotty-message-ssh').show();
    }
    var changeTestMessage = function (message) {
        $('span#test-gotty-message-ssh').text(message);
    }

    var errorMessage = '".__('Unable to connect.')."';


    hideMessage();
    showLoadingImage();

    checkAddressReachability('ssh', function(isReachable) {
        if (isReachable) {
            showSuccessImage();
            hideMessage();
        } else {
            showFailureImage();
            changeTestMessage(errorMessage);
            showMessage();
        }
    });
};";

echo $handle_test_ssh;
echo $handle_test_telnet;
echo '</script>';

extensions_add_godmode_function('quickShellSettings');
