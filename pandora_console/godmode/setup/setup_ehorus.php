<?php 

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
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

if (! check_acl($config['id_user'], 0, "PM") && ! is_user_admin($config['id_user'])) {
	db_pandora_audit("ACL Violation", "Trying to access Setup Management");
	require ("general/noaccess.php");
	return;
}

$table = new StdClass();
$table->data = array();
$table->width = '100%';
$table->id = 'ehorus-setup';
$table->class = 'databox filters';
$table->size['name'] = '30%';
$table->style['name'] = "font-weight: bold";

if (!$config['ehorus_enabled']) {
	$table->rowstyle = array();
	$table->rowstyle['ehorus_user'] = 'display: none';
	$table->rowstyle['ehorus_pass'] = 'display: none';
	$table->rowstyle['ehorus_hostname'] = 'display: none';
	$table->rowstyle['ehorus_port'] = 'display: none';
	$table->rowstyle['ehorus_req_timeout'] = 'display: none';
}

// Enable eHorus
$row = array();
$row['name'] = __('Enable eHorus');
$row['control'] = __('Yes').'&nbsp;'.html_print_radio_button ('ehorus_enabled', 1, '', $config['ehorus_enabled'], true).'&nbsp;&nbsp;';
$row['control'] .= __('No').'&nbsp;'.html_print_radio_button ('ehorus_enabled', 0, '', $config['ehorus_enabled'], true);
$table->data['ehorus_enabled'] = $row;

// User
$row = array();
$row['name'] = __('User');
$row['control'] = html_print_input_text('ehorus_user', $config['ehorus_user'], '', 30, 100, true);
$table->data['ehorus_user'] = $row;

// Pass
$row = array();
$row['name'] = __('Password');
$row['control'] = html_print_input_password('ehorus_pass', io_output_password($config['ehorus_pass']), '', 30, 100, true);
$table->data['ehorus_pass'] = $row;

// Directory hostname
$row = array();
$row['name'] = __('API Hostname');
$row['control'] = html_print_input_text('ehorus_hostname', $config['ehorus_hostname'], '', 30, 100, true);
$row['control'] .= ui_print_help_tip(__('Hostname of the eHorus API') . '. ' . __('Without protocol and port') . '. ' . __('e.g., switch.ehorus.com'), true);
$table->data['ehorus_hostname'] = $row;

// Directory port
$row = array();
$row['name'] = __('API Port');
$row['control'] = html_print_input_text('ehorus_port', $config['ehorus_port'], '', 6, 100, true);
$row['control'] .= ui_print_help_tip(__('e.g., 18080'), true);
$table->data['ehorus_port'] = $row;

// Request timeout
$row = array();
$row['name'] = __('Request timeout');
$row['control'] = html_print_input_text('ehorus_req_timeout', $config['ehorus_req_timeout'], '', 3, 10, true);
$row['control'] .= ui_print_help_tip(__('Time in seconds to set the maximum time of the requests to the eHorus API') . '. ' . __('0 to disable'), true);
$table->data['ehorus_req_timeout'] = $row;

// Form
echo '<form id="form_setup" method="post">';
html_print_input_hidden('update_config', 1);
html_print_table($table);
echo '<div class="action-buttons" style="width: '.$table->width.'">';
html_print_submit_button(__('Update'), 'update_button', false, 'class="sub upd"');
echo '</div>';
echo '</form>';
?>

<script type="text/javascript">
	var showFields = function () {
		$('table#ehorus-setup tr:not(:first-child)').show();
	}
	var hideFields = function () {
		$('table#ehorus-setup tr:not(:first-child)').hide();
	}
	var handleEnable = function (event) {
		if (event.target.value == '1') showFields();
		else hideFields();
	}
	$('input:radio[name="ehorus_enabled"]').change(handleEnable);
</script>
