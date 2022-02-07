<?php
/**
 * Agents/Alerts Monitoring view.
 *
 * @category   Operations
 * @package    Pandora FMS
 * @subpackage Opensource
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
// Require needed class.
require_once $config['homedir'].'/include/class/AgentsAlerts.class.php';
// Get the parameter.
$sec2 = get_parameter_get('sec2');
// Add operation menu option.
extensions_add_operation_menu_option(
    __('Agents/Alerts view'),
    'estado',
    null,
    'v1r1',
    'view'
);

// If sec2 parameter come with this page info.
if ($sec2 === 'extensions/agents_alerts') {
    extensions_add_main_function('mainAgentsAlerts');
}


/**
 * Function for load the controller.
 *
 * @return void
 */
function mainAgentsAlerts()
{
    // Ajax variables.
    $pageName    = '[AgentsAlerts]';
    // Control call flow.
    try {
        // User access and validation is being processed on class constructor.
        $obj = new AgentsAlerts();
    } catch (Exception $e) {
        if (is_ajax() === true) {
            echo json_encode(['error' => $pageName.$e->getMessage() ]);
            exit;
        } else {
            echo $pageName.$e->getMessage();
        }

        // Stop this execution, but continue 'globally'.
        return;
    }

    // AJAX controller.
    if (is_ajax() === true) {
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
}
