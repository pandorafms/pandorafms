<?php
/**
 * Tips pandora
 *
 * @category   Ajax library.
 * @package    Pandora FMS
 * @subpackage Modules.
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

require_once $config['homedir'].'/include/class/TipsWindow.class.php';
if (is_ajax() === false) {
    exit;
}

// Control call flow.
try {
    // User access and validation is being processed on class constructor.
    $actions = new TipsWindow();
} catch (Exception $e) {
    exit;
}

// Ajax controller.
$method = get_parameter('method', '');

if (method_exists($actions, $method) === true) {
    if ($actions->ajaxMethod($method) === true) {
        $actions->{$method}();
    } else {
        $actions->error('Unavailable method.');
    }
} else {
    $actions->error('Method not found. ['.$method.']');
}


// Stop any execution.
exit;
