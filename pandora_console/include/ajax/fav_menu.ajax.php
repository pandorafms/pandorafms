<?php
/**
 * Fav menu
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

// Login check
check_login();

if (is_ajax() === false) {
    exit;
}

$id_element = get_parameter('id_element', '');
$url = io_safe_output(get_parameter('url', ''));
$label = get_parameter('label', '');
$section = get_parameter('section', '');
$id_user = $config['id_user'];

$exist = db_get_row_filter(
    'tfavmenu_user',
    [
        'url'     => $url,
        'id_user' => $config['id_user'],
    ],
    ['*']
);
$res = false;
$action = '';
if ($exist !== false) {
    $res = db_process_sql_delete(
        'tfavmenu_user',
        [
            'url'     => $url,
            'id_user' => $config['id_user'],
        ]
    );
    $action = 'delete';
} else {
    $res = db_process_sql_insert(
        'tfavmenu_user',
        [
            'id_element' => $id_element,
            'url'        => $url,
            'label'      => $label,
            'section'    => $section,
            'id_user'    => $id_user,
        ]
    );
    $action = 'create';
}

if ($res !== false) {
    echo json_encode(['success' => true, 'action' => $action]);
} else {
    echo json_encode(['success' => false, 'action' => $action]);
}
