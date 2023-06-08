<?php
/**
 * Update Manager Client API for MC distributed updates.
 *
 * This is an atomic package, this file must be referenced from general product
 * menu entries in order to give Update Manager Client work.
 *
 * DO NOT EDIT THIS FILE. ONLY SETTINGS SECTION.
 *
 * @category   Class
 * @package    Update Manager
 * @subpackage Client
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
 * Please see http://pandorafms.com/community/ for full contribution list
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

if (file_exists(__DIR__.'/../../include/config.php') === true) {
    include_once __DIR__.'/../../include/config.php';
}

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/resources/helpers.php';

use UpdateManager\API\Server;

$puid = null;
$repo_path = null;

if (is_array($config) === true) {
    $puid = $config['pandora_uid'];
    $repo_path = $config['remote_config'].'/updates/repo';
    if (is_dir($repo_path) === false) {
        mkdir($repo_path, 0777, true);
    }
}

if (function_exists('db_get_value') === true) {
    $license = db_get_value(
        db_escape_key_identifier('value'),
        'tupdate_settings',
        db_escape_key_identifier('key'),
        'customer_key'
    );
}

if (empty($license) === true) {
    $license = 'PANDORA-FREE';
}

try {
    $server = new Server(
        [
            'registration_code' => $puid,
            'repo_path'         => $repo_path,
            'license'           => $license,
        ]
    );

    $server->run();
} catch (Exception $e) {
    echo json_encode(
        ['error' => $e->getMessage()]
    );
}
