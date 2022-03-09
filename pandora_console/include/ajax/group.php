<?php
/**
 * Ajax secondary controller for groups.
 *
 * @category   Ajax secondary controller page.
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

use PandoraFMS\Group;

// Only logged users have access to this endpoint.
check_login();
if (! check_acl($config['id_user'], 0, 'AR')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access credential store'
    );

    if (is_ajax()) {
        echo json_encode(['error' => 'noaccess']);
    } else {
        include 'general/noaccess.php';
    }

    exit;
}


// AJAX controller.
if (is_ajax()) {
    $method = get_parameter('method');

    if (method_exists('PandoraFMS\Group', $method) === true) {
        if (Group::ajaxMethod($method) === true) {
            Group::{$method}();
        } else {
            Group::error('Unavailable method.');
        }
    } else {
        Group::error('Method not found. ['.$method.']');
    }

    // Stop any execution.
    exit;
} else {
    // Run.
    $cs->run();
}
