#!/usr/bin/env php
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

ini_set('display_errors', 1);
error_reporting(E_ALL);
$debug = false;

// 1MB.
$bufferSize = 1048576;

$wsproxy = new WSProxy(
    '0.0.0.0',
    '8081',
    '127.0.0.1',
    '8080',
    '/ws',
    $bufferSize,
    $debug
);

try {
    echo "Server running \n";
    $wsproxy->run();
} catch (Exception $e) {
    $wsproxy->stdout($e->getMessage());
}
