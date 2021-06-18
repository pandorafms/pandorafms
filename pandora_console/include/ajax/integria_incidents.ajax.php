<?php
/**
 * Integria incidents management.
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

if (check_login()) {
    global $config;

    include_once $config['homedir'].'/include/functions_integriaims.php';

    $get_users = get_parameter('get_users');
    $search_term = get_parameter('search_term', '');

    if ($get_users) {
        $integria_users_csv = integria_api_call(null, null, null, null, 'get_users', []);

        $csv_array = explode("\n", $integria_users_csv);

        foreach ($csv_array as $csv_line) {
            if (!empty($csv_line)) {
                $integria_users_values[$csv_line] = $csv_line;
            }
        }

        $integria_users_filtered_values = array_filter(
            $integria_users_values,
            function ($item) use ($search_term) {
                if (strpos($item, $search_term) !== false) {
                    return true;
                }
            }
        );

        echo json_encode($integria_users_filtered_values);
        return;
    }
}
