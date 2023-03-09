<?php
/**
 * External Tools view for agents.
 *
 * @category   External tools for agents.
 * @package    Pandora FMS
 * @subpackage Classic agent management view.
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

require_once 'include/functions_agents.php';

// Require needed class.
require_once $config['homedir'].'/include/class/ExternalTools.class.php';

// Control call flow for debug window.
try {
    // User access and validation is being processed on class constructor.
    $obj = new ExternalTools('agent');
} catch (Exception $e) {
    echo '[ExternalTools]'.$e->getMessage();

    // Stop this execution, but continue 'globally'.
    return;
}

$obj->run();
