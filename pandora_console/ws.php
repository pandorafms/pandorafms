<?php
/**
 * PHP script to manage Pandora FMS websockets.
 *
 * @category   Websocket
 * @package    Pandora FMS
 * @subpackage Console
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
require_once __DIR__.'/vendor/autoload.php';
use \PandoraFMS\WebSockets\WSManager;

// Set to true to get full output.
$debug = false;

// 1MB.
$bufferSize = 1048576;

if (file_exists(__DIR__.'/include/config.php') === false
    || is_readable(__DIR__.'/include/config.php') === false
) {
    echo "Main console configuration file not found.\n";
    exit;
}

// Simulate.
$_SERVER['DOCUMENT_ROOT'] = __DIR__.'/../';

// Don't start a session before this import.
// The session is configured and started inside the config process.
require_once __DIR__.'/include/config.php';
require_once __DIR__.'/include/functions.php';
require_once __DIR__.'/include/functions_db.php';
require_once __DIR__.'/include/auth/mysql.php';
require_once __DIR__.'/include/websocket_registrations.php';

// Enterprise support.
if (file_exists(ENTERPRISE_DIR.'/load_enterprise.php') === true) {
    include_once ENTERPRISE_DIR.'/load_enterprise.php';
}

// Avoid direct access through browsers.
if (isset($_SERVER['REMOTE_ADDR']) === true) {
    // Force redirection.
    header('Location: '.ui_get_full_url('index.php'));
    exit;
}


if (isset($config['ws_port']) === false) {
    config_update_value('ws_port', 8080);
}

if (isset($config['ws_bind_address']) === false) {
    config_update_value('ws_bind_address', '0.0.0.0');
}

if (isset($config['gotty_host']) === false) {
    config_update_value('gotty_host', '127.0.0.1');
}

if (isset($config['gotty_telnet_port']) === false) {
    config_update_value('gotty_telnet_port', 8082);
}

if (isset($config['gotty_ssh_port']) === false) {
    config_update_value('gotty_ssh_port', 8081);
}

if (isset($config['gotty']) === false) {
    config_update_value('gotty', '/usr/bin/gotty');
}


ini_set('display_errors', 1);
error_reporting(E_ALL);

$os = strtolower(PHP_OS);
if (substr($os, 0, 3) !== 'win') {
    if (empty($config['gotty']) === false) {
        // Allow start without gotty binary. External service.
        if (is_executable($config['gotty']) === false) {
            echo 'Failed to execute gotty ['.$config['gotty']."]\n";
            exit(1);
        }

        $gotty_creds = '';
        if (empty($config['gotty_user']) === false
            && empty($config['gotty_pass']) === false
        ) {
            $gotty_pass = io_output_password($config['gotty_pass']);
            $gotty_creds = " -c '".$config['gotty_user'].':'.$gotty_pass."'";
        }

        // Kill previous gotty running.
        $clean_cmd = 'ps aux | grep "'.$config['gotty'].'"';
        $clean_cmd .= '| grep -v grep | awk \'{print $2}\'';
        $clean_cmd .= '| xargs kill -9 ';
        shell_exec($clean_cmd);

        // Common.
        $base_cmd = 'nohup "'.$config['gotty'].'" '.$gotty_creds;
        $base_cmd .= ' --permit-arguments -a 127.0.0.1 -w ';

        // Launch gotty - SSH.
        $cmd = $base_cmd.' --port '.$config['gotty_ssh_port'];
        $cmd .= ' ssh >> '.__DIR__.'/pandora_console.log 2>&1 &';
        shell_exec($cmd);

        // Launch gotty - telnet.
        $cmd = $base_cmd.' --port '.$config['gotty_telnet_port'];
        $cmd .= ' telnet >> '.__DIR__.'/pandora_console.log 2>&1 &';
        shell_exec($cmd);
    }
}

// Start Web SocketProxy.
$ws = new WSManager(
    // Bind address.
    $config['ws_bind_address'],
    // Bind port.
    (int) $config['ws_port'],
    // Connected handlers.
    ['gotty' => 'proxyConnected'],
    // Process handlers.
    [],
    // ProcessRaw handlers.
    ['gotty' => 'proxyProcessRaw'],
    // Tick handlers.
    [],
    $bufferSize,
    $debug
);

try {
    $ws->run();
} catch (Exception $e) {
    $ws->stdout($e->getMessage());
}
