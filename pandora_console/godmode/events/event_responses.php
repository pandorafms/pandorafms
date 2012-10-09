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

check_login ();

if (! check_acl($config['id_user'], 0, "PM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Group Management");
	require ("general/noaccess.php");
	return;
}

$mode = get_parameter('mode','list');
$action = get_parameter('action');

switch($action) {
	case 'create_response':
		$values = array();
		$values['name'] = get_parameter('name');
		$values['description'] = get_parameter('description');
		$values['target'] = get_parameter('target');
		$values['type'] = get_parameter('type');
		$values['id_group'] = get_parameter('id_group',0);
		$values['modal_width'] = get_parameter('modal_width');
		$values['modal_height'] = get_parameter('modal_height');
		$values['new_window'] = get_parameter('new_window');
		$values['params'] = get_parameter('params');
		
		if($values['new_window'] == 1) {
			$values['modal_width'] = 0;
			$values['modal_height'] = 0;
		}
		
		$result = db_process_sql_insert('tevent_response', $values);

		if($result) {
			ui_print_success_message(__('Response added succesfully'));
		}
		else {
			ui_print_error_message(__('Response cannot be added'));
		}
		
		break;
	case 'update_response':
		$values = array();
		$values['name'] = get_parameter('name');
		$values['description'] = get_parameter('description');
		$values['target'] = get_parameter('target');
		$values['type'] = get_parameter('type');
		$values['id_group'] = get_parameter('id_group',0);
		$values['modal_width'] = get_parameter('modal_width');
		$values['modal_height'] = get_parameter('modal_height');
		$values['new_window'] = get_parameter('new_window');
		$values['params'] = get_parameter('params');
		
		if($values['new_window'] == 1) {
			$values['modal_width'] = 0;
			$values['modal_height'] = 0;
		}
		
		$response_id = get_parameter('id_response',0);
		
		$result = db_process_sql_update('tevent_response', $values, array('id' => $response_id));

		if($result) {
			ui_print_success_message(__('Response updated succesfully'));
		}
		else {
			ui_print_error_message(__('Response cannot be updated'));
		}
		break;
	case 'delete_response':
		$response_id = get_parameter('id_response',0);
		
		$result = db_process_sql_delete('tevent_response', array('id' => $response_id));

		if($result) {
			ui_print_success_message(__('Response deleted succesfully'));
		}
		else {
			ui_print_error_message(__('Response cannot be deleted'));
		}
		break;
}

switch($mode) {
	case 'list':
		require('event_responses.list.php');
		break;
	case 'editor':
		require('event_responses.editor.php');
		break;
}
?>
