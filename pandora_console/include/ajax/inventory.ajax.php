<?php
/**
 * Ajax script for Inventory
 *
 * @category   Inventory
 * @package    Pandora FMS
 * @subpackage Enterprises
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
 * Please see https://pandorafms.com/community/ for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */

require_once $config['homedir'].'/include/functions_inventory.php';
check_login();

if (is_ajax() === true) {
    $id_agent = get_parameter('id_agent', '0');
    $id_server = get_parameter('id_server', '0');
    if (is_metaconsole() === true) {
        $agent_modules = [];
        $server_name = metaconsole_get_names(['id' => $id_server]);
        if (is_array($server_name) === true && count($server_name) > 0) {
            $agent_modules = inventory_get_agent_modules($id_agent, 'all', $id_server, reset($server_name));
        }
    } else {
        $agent_modules = inventory_get_agent_modules($id_agent);
    }

    echo json_encode($agent_modules);
}
