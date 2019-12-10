<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
global $config;

require_once $config['homedir'].'/include/functions_event_responses.php';

check_login();

if (! check_acl($config['id_user'], 0, 'PM')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access Group Management'
    );
    include 'general/noaccess.php';
    return;
}

$mode = get_parameter('mode', 'list');
$action = get_parameter('action');

switch ($action) {
    case 'create_response':
        $values = [];
        $values['name'] = get_parameter('name');
        $values['description'] = get_parameter('description');
        $values['target'] = get_parameter('target');
        $values['type'] = get_parameter('type');
        $values['id_group'] = get_parameter('id_group', 0);
        $values['modal_width'] = get_parameter('modal_width');
        $values['modal_height'] = get_parameter('modal_height');
        $values['new_window'] = get_parameter('new_window');
        $values['params'] = get_parameter('params');
        $values['server_to_exec'] = get_parameter('server_to_exec');
        $values['command_timeout'] = get_parameter('command_timeout', 90);

        $result = event_responses_create_response($values);

        if ($result) {
            ui_print_success_message(__('Response added succesfully'));
        } else {
            ui_print_error_message(__('Response cannot be added'));
        }
    break;

    case 'update_response':
        $values = [];
        $values['name'] = get_parameter('name');
        $values['description'] = get_parameter('description');
        $values['target'] = get_parameter('target');
        $values['type'] = get_parameter('type');
        $values['id_group'] = get_parameter('id_group', 0);
        $values['modal_width'] = get_parameter('modal_width');
        $values['modal_height'] = get_parameter('modal_height');
        $values['new_window'] = get_parameter('new_window');
        $values['params'] = get_parameter('params');
        $values['server_to_exec'] = get_parameter('server_to_exec');
        $response_id = get_parameter('id_response', 0);
        $values['command_timeout'] = get_parameter('command_timeout', '90');


        $result = event_responses_update_response($response_id, $values);

        if ($result) {
            ui_print_success_message(__('Response updated succesfully'));
        } else {
            ui_print_error_message(__('Response cannot be updated'));
        }
    break;

    case 'delete_response':
        $response_id = get_parameter('id_response', 0);

        $result = db_process_sql_delete('tevent_response', ['id' => $response_id]);

        if ($result) {
            ui_print_success_message(__('Response deleted succesfully'));
        } else {
            ui_print_error_message(__('Response cannot be deleted'));
        }
    break;
}

switch ($mode) {
    case 'list':
        include 'event_responses.list.php';
    break;

    case 'editor':
        include 'event_responses.editor.php';
    break;
}
