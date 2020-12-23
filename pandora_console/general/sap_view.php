<?php
/**
 * Monitoring SAP View
 *
 * @category   Operations
 * @package    Pandora FMS
 * @subpackage Monitoring
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

/**
 * Class sap_views.
 */

global $config;

enterprise_include_once('/include/class/SAPView.class.php');

$ajaxPage = 'general/sap_view';

// Control call flow.
try {
    // User access and validation is being processed on class constructor.
    $sap_views = new SAPView($ajaxPage);
} catch (Exception $e) {
    if (is_ajax()) {
        echo json_encode(['error' => '[sap_views]'.$e->getMessage() ]);
        exit;
    } else {
        echo '[sap_views]'.$e->getMessage();
    }

    // Stop this execution, but continue 'globally'.
    return;
}

// Ajax controller.
if (is_ajax()) {
    $method = get_parameter('method', '');

    if (method_exists($sap_views, $method) === true) {
        if ($sap_views->ajaxMethod($method) === true) {
            $sap_views->{$method}();
        } else {
            $sap_views->error('Unavailable method.');
        }
    } else {
        $sap_views->error('Method not found. ['.$method.']');
    }


    // Stop any execution.
    exit;
} else {
    // Run.
    $sap_views->run();
}
