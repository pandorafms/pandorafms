<?php
/**
 * Dashboards.
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
global $config;

require $config['homedir'].'/vendor/autoload.php';

use PandoraFMS\Dashboard\Manager;

if ((bool) is_metaconsole() === true) {
    ui_require_css_file('meta_dashboards');
}

$ajaxPage = 'operation/dashboard/dashboard';

// Control call flow.
try {
    // User access and validation is being processed on class constructor.
    $cs = new Manager($ajaxPage);
} catch (Exception $e) {
    if (is_ajax() === true) {
        echo json_encode(['error' => '[Dashboards]'.$e->getMessage() ]);
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
        if ($cs->callWidgetMethod($method) === false) {
            $cs->error('Method not found. ['.$method.']');
        }
    }
} else {
    // Run.
    $cs->run();
}
