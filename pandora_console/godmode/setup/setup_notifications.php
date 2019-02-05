<?php 

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Warning: This file may be required into the metaconsole's setup

// Load global vars
global $config;

require_once ($config['homedir'] . '/include/functions_notifications.php');

check_login ();

if (! check_acl($config['id_user'], 0, 'PM') && ! is_user_admin($config['id_user'])) {
    db_pandora_audit('ACL Violation', 'Trying to access Setup Management');
    require ('general/noaccess.php');
    return;
}

// Actions
// AJAX
if (get_parameter('get_selection_two_ways_form', 0)) {
	$source_id = get_parameter('source_id', '');
	$users = get_parameter('users', '');
	$id = get_notification_source_id($source_id);
	$info_selec = $users === "users"
		? notifications_get_user_source_not_configured($id)
		: notifications_get_group_source_not_configured($id);

	echo notifications_print_two_ways_select(
		$info_selec,
		$users,
		$source_id
	);
	return;
}
if (get_parameter('update_config', 0)) {
	$res_global = array_reduce(notifications_get_all_sources(), function($carry, $source){
		$id = notifications_desc_to_id($source['description']);
		if (empty($id)) return false;
		$enable_value = switch_to_int(get_parameter("enable-$id"));
		$mail_value = (int)get_parameter("mail-{$id}", 0);
		$user_value = (int)get_parameter("user-{$id}", 0);
		$postpone_value = (int)get_parameter("postpone-{$id}", 0);
		$all_users = (int)get_parameter("all-{$id}", 0);
		$res = db_process_sql_update(
			'tnotification_source',
			array(
				'enabled' => $enable_value,
				'user_editable' => $user_value,
				'also_mail' => $mail_value,
				'max_postpone_time' => $postpone_value
			),
			array('id' => $source['id'])
		);
		$all_users_res = $all_users
			? notifications_add_group_to_source($source['id'], array(0))
			: notifications_remove_group_from_source($source['id'], array(0));
		return $all_users_res && $res && $carry;
	}, true);
}

// Notification table. It is just a wrapper.
$table_content = new StdClass();
$table_content->data = array();
$table_content->width = '100%';
$table_content->id = 'notifications-wrapper';
$table_content->class = 'databox filters';
$table_content->size['name'] = '30%';
$table_remote->style['name'] = 'font-weight: bold';

// Print each source configuration
$table_content->data = array_map(function ($source) {
	return notifications_print_global_source_configuration($source);
}, notifications_get_all_sources());
$table_content->data[] = html_print_submit_button(
	__('Update'), 'update_button', false, 'class="sub upd" style="display: flex; "', true
);

echo '<form id="form_enable" method="post">';
html_print_input_hidden('update_config', 1);
html_print_table($table_content);
echo '</form>';

?>
<script>

// Get the source id
function notifications_get_source_id(id) {
	var matched = id.match(/.*-(.*)/);
	if (matched == null) return '';
	return matched[1];
}

// Disable or enable the select seeing the checked value of notify all users
function notifications_disable_source(event) {
	var id = notifications_get_source_id(event.target.id);
	var is_checked = document.getElementById(event.target.id).checked;
	var selectors = ['groups', 'users'];
	selectors.map(function (select) {
		document.getElementById('multi-' + select + '-' + id).disabled = is_checked;
	});
}

// Open a dialog with selector of source elements.
function add_source_dialog(users, source_id) {
	// Display the dialog
	var dialog_id = 'global_config_notifications_dialog_add-' + users + '-' + source_id;
	var not_dialog = document.createElement('div');
	not_dialog.setAttribute('class', 'global_config_notifications_dialog_add');
	not_dialog.setAttribute('id', dialog_id);
	document.body.appendChild(not_dialog);
	$("#" + dialog_id).dialog({
		resizable: false,
		draggable: true,
		modal: true,
		dialogClass: "global_config_notifications_dialog_add_wrapper",
		overlay: {
			opacity: 0.5,
			background: "black"
		},
		closeOnEscape: true,
		modal: true
	});

	jQuery.post ("ajax.php",
		{"page" : "godmode/setup/setup_notifications",
			"get_selection_two_ways_form" : 1,
			"users" : users,
			"source_id" : source_id
		},
		function (data, status) {
			not_dialog.innerHTML = data
		},
		"html"
	);
}

// Move from selected and not selected source elements.
function notifications_modify_two_ways_element (id, source_id, operation) {
	var index_sufix = 'multi-' + id + '-' + source_id;
	var start_id = operation === 'add' ? 'all-' : 'selected-';
	var end_id = operation !== 'add' ? 'all-' : 'selected-';
	var select = document.getElementById(
		start_id + index_sufix
	);
	var select_end = document.getElementById(
		end_id + index_sufix
	);
	for (var i = 0; i < select.options.length; i++) {
		if(select.options[i].selected ==true){
			select_end.appendChild(select.options[i]);
		}
	}
}
</script>
