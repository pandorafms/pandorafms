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

// Begin.
global $config;

require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/godmode/wizards/Wizard.main.php';


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
            'ACL Violation',
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
    $address = agents_get_address($agent_id);

    ui_require_css_file('wizard');
    ui_require_css_file('discovery');

    // Settings.
    // WebSocket host, where to connect.
    if (isset($config['ws_host']) === false) {
        config_update_value('ws_host', $_SERVER['SERVER_ADDR']);
    }

    if (isset($config['ws_port']) === false) {
        config_update_value('ws_port', 8080);
    }

    if (empty($config['ws_proxy_url']) === true) {
        $ws_url = 'http://'.$config['ws_host'].':'.$config['ws_port'];
    } else {
        preg_match('/\/\/(.*)/', $config['ws_proxy_url'], $matches);
        if (isset($_SERVER['HTTPS']) === true) {
            $ws_url = 'https://'.$matches[1];
        } else {
            $ws_url = 'http://'.$matches[1];
        }
    }

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

    // Username. Retrieve from form.
    if (empty($username) === true) {
        // No username provided, ask for it.
        $wiz = new Wizard();

        $test = file_get_contents($ws_url);
        if ($test === false) {
            ui_print_error_message(__('WebService engine has not been started, please check documentation.'));
            $wiz->printForm(
                [
                    'form'   => [
                        'method' => 'POST',
                        'action' => '#',
                    ],
                    'inputs' => [
                        [
                            'class'     => 'w100p',
                            'arguments' => [
                                'name'       => 'submit',
                                'label'      => __('Retry'),
                                'type'       => 'submit',
                                'attributes' => 'class="sub next"',
                                'return'     => true,
                            ],
                        ],
                    ],
                ]
            );

            return;
        }

        $wiz->printForm(
            [
                'form'   => [
                    'action' => '#',
                    'class'  => 'wizard',
                    'method' => 'post',
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
                    [
                        'arguments' => [
                            'type'       => 'submit',
                            'label'      => __('Connect'),
                            'attributes' => 'class="sub next"',
                        ],
                    ],
                ],
            ],
            false,
            true
        );

        return;
    }

    // Initialize Gotty Client.
    $host = $config['gotty_host'];
    if ($method == 'ssh') {
        // SSH.
        $port = $config['gotty_ssh_port'];
        $command_arguments = "var args = '?arg=".$username.'@'.$address;
        $command_arguments .= '&arg=-p '.$method_port."';";
    } else if ($method == 'telnet') {
        // Telnet.
        $port = $config['gotty_telnet_port'];
        $command_arguments = "var args = '?arg=-l ".$username;
        $command_arguments .= '&arg='.$address;
        $command_arguments .= '&arg='.$method_port."';";
    } else {
        ui_print_error_message(__('Please use SSH or Telnet.'));
        return;
    }

    // If rediretion is enabled, we will try to connect to http:// or https:// endpoint.
    $test = get_headers($ws_url);
    if ($test === false) {
        if (empty($wiz) === true) {
            $wiz = new Wizard();
        }

        ui_print_error_message(__('WebService engine has not been started, please check documentation.'));
        echo $wiz->printGoBackButton('#');
        return;
    }

    // Check credentials.
    $auth_str = '';
    $gotty_url = $host.':'.$port;
    if (empty($config['gotty_user']) === false
        && empty($config['gotty_pass']) === false
    ) {
        $auth_str = io_safe_output($config['gotty_user']);
        $auth_str .= ':'.io_output_password($config['gotty_pass']);
        $gotty_url = $auth_str.'@'.$host.':'.$port;
    }

    $r = file_get_contents('http://'.$gotty_url.'/js/hterm.js');
    if (empty($r) === true) {
        if (empty($wiz) === true) {
            $wiz = new Wizard();
        }

        ui_print_error_message(__('WebService engine is not working properly, please check documentation.'));
        echo $wiz->printGoBackButton('#');
        return;
    }

    // Override gotty client settings.
    if (empty($auth_str) === true) {
        $r .= "var gotty_auth_token = '';";
    } else {
        $r .= "var gotty_auth_token = '";
        $r .= $auth_str."';";
    }

    // Set websocket target and method.
    $gotty = file_get_contents('http://'.$gotty_url.'/js/gotty.js');
    $url = "var url = (httpsEnabled ? 'wss://' : 'ws://') + window.location.host + window.location.pathname + 'ws';";
    if (empty($config['ws_proxy_url']) === true) {
        $new = "var url = (httpsEnabled ? 'wss://' : 'ws://')";
        $new .= " + window.location.host + ':";
        $new .= $config['ws_port'].'/'.$method."';";
    } else {
        $new = "var url = '";
        $new .= $config['ws_proxy_url'].'/'.$method."';";
    }

    // Update url.
    $gotty = str_replace($url, $new, $gotty);

    // Update websocket arguments.
    $args = 'var args = window.location.search;';
    $new = $command_arguments;

    // Update arguments.
    $gotty = str_replace($args, $new, $gotty);

    ?>
    <style>#terminal {
        height: 650px;
        width: 100%;
        margin: 0px;
        padding: 0;
      }
      #terminal > iframe {
        position: relative!important;
      }
    </style>
    <div id="terminal"></div>
    <script type="text/javascript">
    <?php echo $r; ?>
    </script>
    <script type="text/javascript">
    <?php echo $gotty; ?>
    </script>
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

    // Parser.
    if (get_parameter('qs_update', false) !== false) {
        // Gotty settings. Internal communication (WS).
        $gotty = get_parameter(
            'gotty',
            ''
        );
        $gotty_host = get_parameter(
            'gotty_host',
            $config['gotty_host']
        );
        $gotty_ssh_port = get_parameter(
            'gotty_ssh_port',
            $config['gotty_ssh_port']
        );
        $gotty_telnet_port = get_parameter(
            'gotty_telnet_port',
            $config['gotty_telnet_port']
        );

        $gotty_user = get_parameter(
            'gotty_user',
            ''
        );

        $gotty_pass = get_parameter(
            'gotty_pass',
            ''
        );

        $gotty_pass = io_input_password($gotty_pass);

        $changes = 0;
        $critical = 0;
        if ($config['gotty'] != $gotty) {
            config_update_value('gotty', $gotty);
            $changes++;
            $critical++;
        }

        if ($config['gotty_host'] != $gotty_host) {
            config_update_value('gotty_host', $gotty_host);
            $changes++;
        }

        if ($config['gotty_telnet_port'] != $gotty_telnet_port) {
            config_update_value('gotty_telnet_port', $gotty_telnet_port);
            $changes++;
        }

        if ($config['gotty_ssh_port'] != $gotty_ssh_port) {
            config_update_value('gotty_ssh_port', $gotty_ssh_port);
            $changes++;
        }

        if ($config['gotty_user'] != $gotty_user) {
            config_update_value('gotty_user', $gotty_user);
            $changes++;
            $critical++;
        }

        if ($config['gotty_pass'] != $gotty_pass) {
            $gotty_pass = io_input_password($gotty_pass);
            config_update_value('gotty_pass', $gotty_pass);
            $changes++;
            $critical++;
        }
    }

    // Interface.
    ui_print_page_header(
        __('QuickShell settings'),
        '',
        false,
        'quickshell_settings'
    );

    if ($changes > 0) {
        $msg = __('%d Updated', $changes);
        if ($critical > 0) {
            $msg = __(
                '%d Updated, please restart WebSocket engine service',
                $changes
            );
        }

        ui_print_success_message($msg);
    }

    // Form.
    $wiz = new Wizard();

    $wiz->printForm(
        [
            'form'   => [
                'action' => '#',
                'class'  => 'wizard',
                'method' => 'post',
            ],
            'inputs' => [
                [
                    'label'     => __('Gotty path').ui_print_help_tip(
                        __('Leave blank if using an external Gotty service'),
                        true
                    ),
                    'arguments' => [
                        'type'  => 'text',
                        'name'  => 'gotty',
                        'value' => $config['gotty'],
                    ],
                ],
                [
                    'label'     => __('Gotty host'),
                    'arguments' => [
                        'type'  => 'text',
                        'name'  => 'gotty_host',
                        'value' => $config['gotty_host'],
                    ],
                ],
                [
                    'label'     => __('Gotty ssh port'),
                    'arguments' => [
                        'type'  => 'text',
                        'name'  => 'gotty_ssh_port',
                        'value' => $config['gotty_ssh_port'],
                    ],
                ],
                [
                    'label'     => __('Gotty telnet port'),
                    'arguments' => [
                        'type'  => 'text',
                        'name'  => 'gotty_telnet_port',
                        'value' => $config['gotty_telnet_port'],
                    ],
                ],
                [
                    'toggle'        => true,
                    'toggle_name'   => 'Advanced',
                    'block_content' => [
                        [
                            'label'     => __('Gotty user').ui_print_help_tip(
                                __('Optional, set a user to access gotty service'),
                                true
                            ),
                            'arguments' => [
                                'type'  => 'text',
                                'name'  => 'gotty_user',
                                'value' => $config['gotty_user'],
                            ],
                        ],
                        [
                            'label'     => __('Gotty password').ui_print_help_tip(
                                __('Optional, set a password to access gotty service'),
                                true
                            ),
                            'arguments' => [
                                'type'  => 'password',
                                'name'  => 'gotty_pass',
                                'value' => io_output_password($config['gotty_pass']),
                            ],
                        ],
                    ],
                ],
                [
                    'arguments' => [
                        'type'  => 'hidden',
                        'name'  => 'qs_update',
                        'value' => 1,
                    ],
                ],
                [
                    'arguments' => [
                        'type'       => 'submit',
                        'label'      => __('Update'),
                        'attributes' => 'class="sub next"',
                    ],
                ],
            ],
        ],
        false,
        true
    );

}


// This extension is usefull only if the agent has associated IP.
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
            'images/ehorus/terminal.png',
            // TabFunction.
            'quickShell',
            // Version.
            'N/A',
            // Acl.
            'PM'
        );
    }
}

extensions_add_godmode_menu_option(
    // Name.
    __('QuickShell settings'),
    // Acl.
    'PM',
    // FatherId.
    'gextensions',
    // Icon.
    'images/ehorus/terminal.png',
    // Version.
    'N/A',
    // SubfatherId.
    null
);

extensions_add_godmode_function('quickShellSettings');
