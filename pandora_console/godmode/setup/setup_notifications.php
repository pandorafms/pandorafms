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

check_login ();

if (! check_acl($config['id_user'], 0, 'PM') && ! is_user_admin($config['id_user'])) {
    db_pandora_audit('ACL Violation', 'Trying to access Setup Management');
    require ('general/noaccess.php');
    return;
}

// Actions
if (get_parameter('update_config', 0)) {
	$res_global = array_reduce(notifications_get_all_sources(), function($carry, $source){
		$id = notifications_desc_to_id($source['description']);
		if (empty($id)) return false;
		$enable_value = switch_to_int(get_parameter("enable-$id"));
		$mail_value = (int)get_parameter("mail-{$id}", 0);
		$user_value = (int)get_parameter("user-{$id}", 0);
		$res = mysql_db_process_sql_update(
			'tnotification_source',
			array(
				'enabled' => $enable_value,
				'user_editable' => $user_value,
				'also_mail' => $mail_value),
			array('id' => $source['id'])
		);
		return $res && $carry;
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

