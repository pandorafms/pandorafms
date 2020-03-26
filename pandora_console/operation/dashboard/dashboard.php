<?php
/**
 * Extension to manage a list of gateways and the node address where they should
 * point to.
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

global $config;

require 'vendor/autoload.php';

use PandoraFMS\Dashboard\Manager;

$ajaxPage = 'operation/dashboard/dashboard';

// Control call flow.
try {
    // User access and validation is being processed on class constructor.
    $cs = new Manager($ajaxPage);
} catch (Exception $e) {
    if (is_ajax() === true) {
        echo json_encode(['error' => '[Dashboards]'.$e->getMessage() ]);
        exit;
    } else {
        echo '[Dashboards]'.$e->getMessage();
    }

    // Stop this execution, but continue 'globally'.
    return;
}

// AJAX controller.
if (is_ajax() === true) {
    $method = get_parameter('method');

    if (method_exists($cs, $method) === true) {
        if ($cs->ajaxMethod($method) === true) {
            $cs->{$method}();
        } else {
            $cs->error('Unavailable method.');
        }
    } else {
        $cs->error('Method not found. ['.$method.']');
    }

    // Stop any execution.
    exit;
} else {
    // Run.
    $cs->run();
}
