<?php
/**
 * Public access to dashboard.
 *
 * @category   Dashboards
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

// Begin.
require_once __DIR__.'/../../include/config.php';

global $config;

chdir($config['homedir']);
ob_start('ui_process_page_head');
ob_start();

// Fullscreen by default.
$config['pure'] = get_parameter('pure', 1);

require_once 'dashboard.php';

// Clean session to avoid direct access.
if ($config['force_instant_logout'] === true) {
    // Force user logout.
    $iduser = $_SESSION['id_usuario'];
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $_SESSION = [];
    session_destroy();
    header_remove('Set-Cookie');
    setcookie(session_name(), $_COOKIE[session_name()], (time() - 4800), '/');
}

while (ob_get_length() > 0) {
    ob_end_flush();
}
