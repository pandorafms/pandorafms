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

    $agent_id = get_parameter('id_agente', 0);
    $username = get_parameter('username', null);
    $method = get_parameter('method', null);
    $method_port = get_parameter('port', null);

    // Retrieve main IP Address.
    $agent_address = agents_get_address($agent_id);

    ui_require_css_file('wizard');
    ui_require_css_file('discovery');

    // Initialize Gotty Client.
    if ($method === 'ssh') {
        // SSH.
        $args = '?arg='.$username.'@'.$agent_address;
        //$args = '?arg='.$username.'@172.16.0.1';
        $args .= '&arg=-p%20'.$method_port;
    } else if ($method == 'telnet') {
        // Telnet.
        $username = preg_replace('/[^a-zA-Z0-9\-\.]/', '', $username);
        $args = '?arg=-l%20'.$username;
        $args .= '&arg='.$agent_address;
        $args .= '&arg='.$method_port.'&arg=-E';
    }

    $method_addr = ($method === 'ssh') ? $config['gotty_ssh_addr'] : $config['gotty_telnet_addr'];
    $address = (empty($method_addr) === true) ? $_SERVER['SERVER_ADDR'] : $method_addr;
    $use_ssl = ($method === 'ssh') ? $config['gotty_ssh_use_ssl'] : $config['gotty_telnet_use_ssl'];
    $protocol = ((bool) $use_ssl === true) ? 'https://' : 'http://';
    $port = ($method === 'ssh') ? $config['gotty_ssh_port'] : $config['gotty_telnet_port'];
    $connection_hash = ($method === 'ssh') ? $config['gotty_ssh_connection_hash'] : $config['gotty_telnet_connection_hash'];
    $gotty_addr = $protocol.$address.':'.$port.'/'.$connection_hash.'/'.$args;

    // Username. Retrieve from form.
    if (empty($username) === true) {
        // No username provided, ask for it.
        $wiz = new Wizard();


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
                            'type' => 'text',
                            'name' => 'username',
                        ],
                    ],
                    [
                        'label'     => __('Port'),
                        'arguments' => [
                            'type'  => 'text',
                            'id'    => 'port',
                            'name'  => 'port',
                            'value' => 22,
                        ],
                    ],
                    [
                        'label'     => __('Method'),
                        'arguments' => [
                            'type'   => 'select',
                            'name'   => 'method',
                            'fields' => [
                                'ssh'    => __('SSH'),
                                'telnet' => __('Telnet'),
                            ],
                            'script' => "p=22; if(this.value == 'telnet') { p=23; } $('#text-port').val(p);",
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
      }
    </style>

    <div id="terminal"><iframe id="gotty-iframe" src="<?php echo $gotty_addr; ?>"></iframe></div>

    <?php

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
    if (isset($config['gotty_host']) === false) {
        config_update_value('gotty_host', '127.0.0.1');
    }

    if (isset($config['gotty_telnet_port']) === false) {
        config_update_value('gotty_telnet_port', 8082);
    }

    if (isset($config['gotty_ssh_port']) === false) {
        config_update_value('gotty_ssh_port', 8081);
    }

    if (isset($config['gotty_ssh_user']) === false) {
        config_update_value('gotty_ssh_user', 'pandora');
    }

    if (isset($config['gotty_telnet_user']) === false) {
        config_update_value('gotty_telnet_user', 'pandora');
    }

    if (isset($config['gotty_ssh_pass']) === false) {
        config_update_value('gotty_ssh_pass', 'Pandor4!');
    }

    if (isset($config['gotty_telnet_pass']) === false) {
        config_update_value('gotty_telnet_pass', 'Pandor4!');
    }

    $changes = 0;
    $critical = 0;

    // Parser.
    if (get_parameter('update_config', false) !== false) {
        $gotty_ssh_addr = get_parameter(
            'gotty_ssh_addr',
            ''
        );

        $gotty_telnet_addr = get_parameter(
            'gotty_telnet_addr',
            ''
        );

        $gotty_ssh_port = get_parameter(
            'gotty_ssh_port',
            ''
        );
        $gotty_telnet_port = get_parameter(
            'gotty_telnet_port',
            ''
        );

        $gotty_ssh_user = get_parameter(
            'gotty_ssh_user',
            'pandora'
        );

        $gotty_telnet_user = get_parameter(
            'gotty_telnet_user',
            'pandora'
        );

        $gotty_ssh_use_ssl = get_parameter(
            'gotty_ssh_use_ssl',
            false
        );

        $gotty_telnet_use_ssl = get_parameter(
            'gotty_telnet_use_ssl',
            false
        );

        $gotty_ssh_pass = get_parameter(
            'gotty_ssh_pass',
            'Pandor4!'
        );

        $gotty_ssh_pass = io_input_password($gotty_ssh_pass);

        $gotty_telnet_pass = get_parameter(
            'gotty_telnet_pass',
            'Pandor4!'
        );

        $gotty_telnet_pass = io_input_password($gotty_telnet_pass);

        if ($config['gotty_ssh_addr'] != $gotty_ssh_addr) {
            config_update_value('gotty_ssh_addr', $gotty_ssh_addr);
        }

        if ($config['gotty_telnet_addr'] != $gotty_telnet_addr) {
            config_update_value('gotty_telnet_addr', $gotty_telnet_addr);
        }

        if ($config['gotty_ssh_port'] != $gotty_ssh_port) {
            // Mark ssh gotty for restart (should kill the process in the current port).
            if ($config['restart_gotty_ssh_next_cron_port'] === ''
                || $config['restart_gotty_ssh_next_cron_port'] === null
            ) {
                config_update_value('restart_gotty_ssh_next_cron_port', $config['gotty_ssh_port']);
            }

            config_update_value('gotty_ssh_port', $gotty_ssh_port);
        }

        if ($config['gotty_telnet_port'] != $gotty_telnet_port) {
            // Mark telnet gotty for restart (should kill the process in the current port).
            if ($config['restart_gotty_telnet_next_cron_port'] === ''
                || $config['restart_gotty_telnet_next_cron_port'] === null
            ) {
                config_update_value('restart_gotty_telnet_next_cron_port', $config['gotty_telnet_port']);
            }

            config_update_value('gotty_telnet_port', $gotty_telnet_port);
        }

        if ($config['gotty_ssh_user'] != $gotty_ssh_user) {
            config_update_value('gotty_ssh_user', $gotty_ssh_user);
        }

        if ($config['gotty_telnet_user'] != $gotty_telnet_user) {
            config_update_value('gotty_telnet_user', $gotty_telnet_user);
        }

        if ($config['gotty_ssh_use_ssl'] != $gotty_ssh_use_ssl) {
            config_update_value('gotty_ssh_use_ssl', $gotty_ssh_use_ssl);
        }

        if ($config['gotty_telnet_use_ssl'] != $gotty_telnet_use_ssl) {
            config_update_value('gotty_telnet_use_ssl', $gotty_telnet_use_ssl);
        }

        if ($config['gotty_ssh_pass'] != $gotty_ssh_pass) {
            $gotty_ssh_pass = io_input_password($gotty_ssh_pass);
            config_update_value('gotty_ssh_pass', $gotty_ssh_pass);
        }

        if ($config['gotty_telnet_pass'] != $gotty_telnet_pass) {
            $gotty_telnet_pass = io_input_password($gotty_telnet_pass);
            config_update_value('gotty_telnet_pass', $gotty_telnet_pass);
        }
    }

    echo '<fieldset class="margin-bottom-10">';
    echo '<legend>'.__('SSH connection parameters').'</legend>';

    $test_start = '<span id="test-gotty-spinner" class="invisible">&nbsp;'.html_print_image('images/spinner.gif', true).'</span>';
    $test_start .= '&nbsp;<span id="test-gotty-message" class="invisible"></span>';

    $ssh_table = new StdClass();
    $ssh_table->data = [];
    $ssh_table->width = '100%';
    $ssh_table->class = 'filter-table-adv';
    $ssh_table->data = [];
    $ssh_table->style = [];
    $ssh_table->style[0] = 'width: 50%;';

    $ssh_table->data[0][] = html_print_label_input_block(
        __('Gotty address'),
        html_print_input_text(
            'gotty_ssh_addr',
            $config['gotty_ssh_addr'],
            '',
            30,
            100,
            true
        )
    );

    $ssh_table->data[0][] = html_print_label_input_block(
        __('Gotty port'),
        html_print_input_text(
            'gotty_ssh_port',
            $config['gotty_ssh_port'],
            '',
            30,
            100,
            true
        )
    );

    $ssh_table->data[1][] = html_print_label_input_block(
        __('Gotty user'),
        html_print_input_text(
            'gotty_ssh_user',
            $config['gotty_ssh_user'],
            '',
            30,
            100,
            true
        )
    );

    $ssh_table->data[1][] = html_print_label_input_block(
        __('Gotty password'),
        html_print_input_password(
            'gotty_ssh_pass',
            io_output_password($config['gotty_ssh_pass']),
            '',
            30,
            100,
            true
        )
    );

    $ssh_table->data[2][] = html_print_label_input_block(
        __('Use SSL'),
        html_print_checkbox_switch(
            'gotty_ssh_use_ssl',
            1,
            $config['gotty_ssh_use_ssl'],
            true,
            $disable_agentaccess
        )
    );

    // Test.
    $row = [];
    $test_start = '<span id="test-gotty-spinner-ssh" class="invisible">&nbsp;'.html_print_image('images/spinner.gif', true).'</span>';
    $test_start .= '&nbsp;<span id="test-gotty-message-ssh" class="invisible"></span>';

    $ssh_table->data[2][] = html_print_button(
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
    echo '<legend>'.__('Telnet connection parameters').'</legend>';

    $telnet_table = new StdClass();
    $telnet_table->data = [];
    $telnet_table->width = '100%';
    $telnet_table->class = 'filter-table-adv';
    $telnet_table->data = [];
    $telnet_table->style = [];
    $telnet_table->style[0] = 'width: 50%;';

    $telnet_table->data[0][] = html_print_label_input_block(
        __('Gotty address'),
        html_print_input_text(
            'gotty_telnet_addr',
            $config['gotty_telnet_addr'],
            '',
            30,
            100,
            true
        )
    );

    $telnet_table->data[0][] = html_print_label_input_block(
        __('Gotty port'),
        html_print_input_text(
            'gotty_telnet_port',
            $config['gotty_telnet_port'],
            '',
            30,
            100,
            true
        )
    );

    $telnet_table->data[1][] = html_print_label_input_block(
        __('Gotty user'),
        html_print_input_text(
            'gotty_telnet_user',
            $config['gotty_telnet_user'],
            '',
            30,
            100,
            true
        )
    );

    $telnet_table->data[1][] = html_print_label_input_block(
        __('Gotty password'),
        html_print_input_password(
            'gotty_telnet_pass',
            io_output_password($config['gotty_telnet_pass']),
            '',
            30,
            100,
            true
        )
    );

    $telnet_table->data[2][] = html_print_label_input_block(
        __('Use SSL'),
        html_print_checkbox_switch(
            'gotty_telnet_use_ssl',
            1,
            $config['gotty_telnet_use_ssl'],
            true
        )
    );

    // Test.
    $row = [];
    $test_start = '<span id="test-gotty-spinner-telnet" class="invisible">&nbsp;'.html_print_image('images/spinner.gif', true).'</span>';
    $test_start .= '&nbsp;<span id="test-gotty-message-telnet" class="invisible"></span>';

    $telnet_table->data[2][] = html_print_button(
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
    $address = get_parameter('address');

    if (isset($address) === true) {
        $ch = curl_init($address);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Maximum time for the entire request.
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);

        // Maximum time to establish a connection.
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);

        curl_exec($ch);
        $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response_code === 200 || $response_code === 401) {
            $result = ['status' => 'success'];
        } else {
            $result = ['status' => 'error'];
        }

        echo json_encode($result);
        return;
    }
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
echo "function checkAddressReachability(address, callback) {
    $.ajax({
        url: 'ajax.php',
        data: {
            page: 'extensions/quick_shell',
            address
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
    var gotty_telnet_addr = $('input#text-gotty_telnet_addr').val();
    var gotty_telnet_port = $('input#text-gotty_telnet_port').val();
    var gotty_telnet_user = $('input#text-gotty_telnet_user').val();
    var gotty_telnet_password = $('input#password-gotty_telnet_pass').val();
    var gotty_telnet_use_ssl = $('input#checkbox-gotty_telnet_use_ssl').is(':checked');

    if (gotty_telnet_addr === '') {
        url = (gotty_telnet_use_ssl ? 'https://' : 'http://') + server_addr + ':' + gotty_telnet_port;    
    } else {
        url = (gotty_telnet_use_ssl ? 'https://' : 'http://') + gotty_telnet_addr + ':' + gotty_telnet_port;
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

    checkAddressReachability(url, function(isReachable) {
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
    var gotty_ssh_addr = $('input#text-gotty_ssh_addr').val();
    var gotty_ssh_port = $('input#text-gotty_ssh_port').val();
    var gotty_ssh_user = $('input#text-gotty_ssh_user').val();
    var gotty_ssh_password = $('input#password-gotty_ssh_pass').val();
    var gotty_ssh_use_ssl = $('input#checkbox-gotty_ssh_use_ssl').is(':checked');

    if (gotty_ssh_addr === '') {
        url = (gotty_ssh_use_ssl ? 'https://' : 'http://') + server_addr + ':' + gotty_ssh_port;    
    } else {
        url = (gotty_ssh_use_ssl ? 'https://' : 'http://') + gotty_ssh_addr + ':' + gotty_ssh_port;
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

    checkAddressReachability(url, function(isReachable) {
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
