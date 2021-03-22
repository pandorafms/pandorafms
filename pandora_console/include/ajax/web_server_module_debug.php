<?php
/**
 * Web Server Module Debug ajax controller.
 *
 * @category   Web Server Module Debug
 * @package    Pandora FMS
 * @subpackage Module Debug
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
global $config;
global $id_agent_module;
// Module Debug Class.
require_once $config['homedir'].'/include/class/WebServerModuleDebug.class.php';

// This page.
$ajaxPage = $config['homedir'].'/include/ajax/web_server_module_debug';

// Control call flow for debug window.
try {
    // Return of id of the agent module in AJAX.
    if (is_ajax()) {
        $id_agent_module = get_parameter('idAgentModule');
    }

    // User access and validation is being processed on class constructor.
    $obj = new WebServerModuleDebug($ajaxPage, $id_agent_module);
} catch (Exception $e) {
    if (is_ajax()) {
        echo json_encode(['error' => '[WebServerModuleDebug]'.$e->getMessage() ]);
        exit;
    } else {
        echo '[WebServerModuleDebug]'.$e->getMessage();
    }

    // Stop this execution, but continue 'globally'.
    return;
}

// AJAX controller.
if (is_ajax()) {
    $method = get_parameter('method');

    if (method_exists($obj, $method) === true) {
        $obj->{$method}();
    } else {
        $obj->error('Method not found. ['.$method.']');
    }

    // Stop any execution.
    exit;
} else {
    // Run.
    $obj->run();
}
