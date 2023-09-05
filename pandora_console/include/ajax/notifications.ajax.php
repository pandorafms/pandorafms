<?php
/**
 * Extension to manage a list of gateways and the node address where they should
 * point to.
 *
 * @category   Users
 * @package    Pandora FMS
 * @subpackage Community
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

global $config;

// Includes.
require_once $config['homedir'].'/include/functions_notifications.php';

$change_label = get_parameter('change_label', 0);
if ($change_label === '1') {
    $label = get_parameter('label', '');
    $source = get_parameter('source', 0);
    $user = get_parameter('user', '');
    $value = get_parameter('value', 0) ? 1 : 0;

    // Update the label value.
    ob_clean();
    $json = json_encode(
        [
            'result' => notifications_set_user_label_status(
                $source,
                $user,
                $label,
                $value
            ),
        ]
    );

    echo $json;
    return;
}
