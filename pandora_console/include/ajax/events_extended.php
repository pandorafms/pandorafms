<?php
/**
 * Manage AJAX response for event pages.
 *
 * @category   Ajax
 * @package    Pandora FMS
 * @subpackage Events extended
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

require_once 'include/functions_events.php';


enterprise_include_once('meta/include/functions_events_meta.php');
enterprise_include_once('include/functions_metaconsole.php');


global $config;

// Check ACLs.
if (is_user_admin($config['id_user']) === true) {
    // Do nothing if you're admin, you get full access.
    $allowed = true;
} else if ($config['id_user'] == $event['owner_user']) {
    // Do nothing if you're the owner user, you get access.
    $allowed = true;
} else if ($event['id_grupo'] == 0) {
    // If the event has access to all groups, you get access.
    $allowed = true;
} else {
    // Get your groups.
    $groups = users_get_groups($config['id_user'], 'ER');

    if (in_array($event['id_grupo'], array_keys($groups))) {
        // If event group is among the groups of the user, you get access.
        $__ignored_line = true;
    } else {
        // If all the access types fail, abort.
        $allowed = false;
    }
}

if ($allowed === false) {
    echo 'Access denied';
    exit;
}

$id_event = get_parameter('id_event', null);
$get_extended_info = get_parameter('get_extended_info', 0);


if ($get_extended_info == 1) {
    if (isset($id_event) === false) {
        echo 'Internal error. Invalid event.';
        exit;
    }

    $extended_info = events_get_extended_events($id_event);

    $table = new StdClass();
    //
    // Details.
    //
    $table->width = '100%';
    $table->data = [];
    $table->head = [];
    $table->cellspacing = 2;
    $table->cellpadding = 2;
    $table->class = 'table_modal_alternate';

    $output = [];
    $output[] = '<b>'.__('Timestamp').'</b>';
    $output[] = '<b>'.__('Description').'</b>';
    $table->data[] = $output;

    foreach ($extended_info as $data) {
        $output = [];
        $output[] = date('Y/m/d H:i:s', $data['utimestamp']);
        $output[] = io_safe_output($data['description']);
        $table->data[] = $output;
    }

    html_print_table($table);
}
