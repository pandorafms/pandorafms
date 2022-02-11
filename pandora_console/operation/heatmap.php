<?php
/**
 * Tree view.
 *
 * @category   Operation
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
 * Copyright (c) 2005-2022 Artica Soluciones Tecnologicas
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

global $config;
// Login check.
check_login();

$agent_a = (bool) check_acl($config['id_user'], 0, 'AR');
$agent_w = (bool) check_acl($config['id_user'], 0, 'AW');

if ($agent_a === false && $agent_w === false) {
    db_pandora_audit('ACL Violation', 'Trying to access agent main list view');
    include 'general/noaccess.php';

    return;
}

require_once $config['homedir'].'/include/class/Heatmap.class.php';

$is_ajax = is_ajax();
if (!$is_ajax) {
    // Header.
    ui_print_standard_header(
        __('Heatmap view'),
        '',
        false,
        '',
        false,
        [],
        [
            [
                'link'  => '',
                'label' => __('Monitoring'),
            ],
            [
                'link'  => '',
                'label' => __('Views'),
            ],
        ]
    );
}

$type = get_parameter('type', 0);
$filter = get_parameter('filter', []);
$randomId = get_parameter('randomId', null);
$refresh = get_parameter('refresh', 300);

// Control call flow.
try {
    // Heatmap construct.
    $heatmap = new Heatmap($type, $filter, $randomId, $refresh);
} catch (Exception $e) {
    if (is_ajax() === true) {
        echo json_encode(['error' => '[Heatmap]'.$e->getMessage() ]);
        exit;
    } else {
        echo '[Heatmap]'.$e->getMessage();
    }

    // Stop this execution, but continue 'globally'.
    return;
}

// AJAX controller.
if ($is_ajax === true) {
    $method = get_parameter('method');

    if (method_exists($heatmap, $method) === true) {
        if ($heatmap->ajaxMethod($method) === true) {
            $heatmap->{$method}();
        } else {
            echo 'Unavailable method';
        }
    } else {
        echo 'Method not found';
    }

    // Stop any execution.
    exit;
} else {
    // Run.
    $heatmap->run();
}
