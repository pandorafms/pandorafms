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
use \PandoraFMS\WebSockets\WSProxy;

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
require_once 'include/config.php';
require_once 'include/functions.php';
require_once 'include/functions_db.php';
require_once 'include/auth/mysql.php';

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
    config_update_value('ws_port', 8081);
}

if (isset($config['gotty']) === false) {
    config_update_value('gotty', '/usr/bin/gotty');
}


ini_set('display_errors', 1);
error_reporting(E_ALL);

$os = strtolower(PHP_OS);
if (substr($os, 0, 3) !== 'win') {
    // Launch gotty.
    $cmd = 'nohup "'.$config['gotty'].'" -a 127.0.0.1 -w /bin/bash';
    $cmd .= ' >> '.__DIR__.'/pandora_console.log 2>&1 &';
    shell_exec($cmd);
}

// Start Web SocketProxy.
$wsproxy = new WSProxy(
    '0.0.0.0',
    $config['ws_port'],
    '127.0.0.1',
    '8080',
    '/ws',
    $bufferSize,
    $debug
);

try {
    $wsproxy->run();
} catch (Exception $e) {
    $wsproxy->stdout($e->getMessage());
}
