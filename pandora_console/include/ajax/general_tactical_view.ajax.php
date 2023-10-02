<?php
/**
 * Ajax secondary controller for general tactival view.
 *
 * @category   Ajax general tactical view page.
 * @package    Pandora FMS
 * @subpackage Opensource
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

// Begin.
global $config;

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
    $dir = $config['homedir'].'/include/lib/TacticalView/elements/';
    $method = get_parameter('method');
    $class = get_parameter('class');

    $filepath = realpath($dir.'/'.$class.'.php');
    if (is_readable($filepath) === false
        || is_dir($filepath) === true
        || preg_match('/.*\.php$/', $filepath) === false
    ) {
        exit;
    }

    include_once $filepath;

    if (class_exists($class) === true) {
        $instance = new $class();
        if ($instance->ajaxMethod($method) === true) {
            echo $instance->{$method}();
        } else {
            $instance->error('Unavailable method.');
        }
    } else {
        $class->error('Class not found. ['.$class.']');
    }

    exit;
}
