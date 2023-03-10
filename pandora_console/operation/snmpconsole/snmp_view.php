<?php
/**
 * SNMP Console.
 *
 * @category   SNMP
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
 * Copyright (c) 2005-2023 Artica Soluciones Tecnologicas
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

require_once $config['homedir'].'/include/class/SnmpConsole.class.php';

$ajaxPage = $config['homedir'].'/operation/snmpconsole/snmp_view';

$filter_alert = get_parameter('filter_alert', -1);
$filter_severity = get_parameter('filter_severity', -1);
$filter_free_search = get_parameter('filter_free_search', '');
$filter_status = get_parameter('filter_status', 0);
$filter_group_by = get_parameter('filter_group_by', 0);
$filter_hours_ago = get_parameter('filter_hours_ago', 8);
$filter_trap_type = get_parameter('filter_trap_type', -1);
$refr = get_parameter('refr', 300);

// Control call flow.
try {
    // User access and validation is being processed on class constructor.
    $controller = new SnmpConsole(
        $ajaxPage,
        $filter_alert,
        $filter_severity,
        $filter_free_search,
        $filter_status,
        $filter_group_by,
        $filter_hours_ago,
        $filter_trap_type,
        $refr
    );
} catch (Exception $e) {
    if ((bool) is_ajax() === true) {
        echo json_encode(['error' => '[SnmpConsole]'.$e->getMessage() ]);
        exit;
    } else {
        echo '[SnmpConsole]'.$e->getMessage();
    }

    // Stop this execution, but continue 'globally'.
    return;
}

// AJAX controller.
if ((bool) is_ajax() === true) {
    $method = get_parameter('method');

    if (method_exists($controller, $method) === true) {
        if ($controller->ajaxMethod($method) === true) {
            $controller->{$method}();
        } else {
            $controller->error('Unavailable method.');
        }
    } else {
        $controller->error('Method not found. ['.$method.']');
    }

    // Stop any execution.
    exit;
} else {
    // Run.
    $controller->run();
}
