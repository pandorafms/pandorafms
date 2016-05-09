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

if (! check_acl($config['id_user'], 0, 'PM') && ! is_user_admin($config['id_user'])) {
	db_pandora_audit('ACL Violation', 'Trying to access Setup Management');
	require ('general/noaccess.php');
	return;
}

// Check custom field
$custom_field_exists = false;
if (!empty($config['ehorus_custom_field'])) {
	$custom_field = db_get_value('name', 'tagent_custom_fields', 'name', $config['ehorus_custom_field']);
	$custom_field_exists = !empty($custom_field);
}
$create_custom_field = (bool) get_parameter('create_custom_field');
if ($create_custom_field) {
	$result = (bool) db_process_sql_insert('tagent_custom_fields', array('name' => $config['ehorus_custom_field']));
	ui_print_result_message($result, __('Custom field for eHorus ID created'), __('Error creating custom field'));
	$custom_field_exists = $result;
}

/* Enable table */

$table_enable = new StdClass();
$table_enable->data = array();
$table_enable->width = '100%';
$table_enable->id = 'ehorus-enable-setup';
$table_enable->class = 'databox filters';
$table_enable->size['name'] = '30%';
$table_enable->style['name'] = 'font-weight: bold';

// Enable eHorus
$row = array();
$row['name'] = __('Enable eHorus');
$row['control'] = __('Yes').'&nbsp;'.html_print_radio_button ('ehorus_enabled', 1, '', $config['ehorus_enabled'], true).'&nbsp;&nbsp;';
$row['control'] .= __('No').'&nbsp;'.html_print_radio_button ('ehorus_enabled', 0, '', $config['ehorus_enabled'], true);
$row['button'] = html_print_submit_button(__('Update'), 'update_button', false, 'class="sub upd"', true);
$table_enable->data['ehorus_enabled'] = $row;

/* Agents config table */

$table_agents = new StdClass();
$table_agents->data = array();
$table_agents->width = '100%';
$table_agents->styleTable = 'margin-bottom: 10px;';
$table_agents->id = 'ehorus-agents-setup';
$table_agents->class = 'databox filters';
$table_agents->size['name'] = '30%';
$table_agents->style['name'] = 'font-weight: bold';

// Custom Fields
$row = array();
$row['name'] = __('Custom field name');
$row['control'] = html_print_input_text('ehorus_custom_field', $config['ehorus_custom_field'], '', 30, 100, true);
$row['button'] = html_print_submit_button(__('Use'), 'use_custom_field', false, 'class="sub upd"', true);
$row['button'] .= '&nbsp;' . html_print_submit_button(__('Create and use'), 'create_custom_field', false, 'class="sub upd"', true);
$row['button'] .= ui_print_help_tip(__('The previous item will not be deleted or modified by performing this operations'), true);
$table_agents->data['ehorus_custom_field'] = $row;

/* Remote config table */

$table_remote = new StdClass();
$table_remote->data = array();
$table_remote->width = '100%';
$table_remote->styleTable = 'margin-bottom: 10px;';
$table_remote->id = 'ehorus-remote-setup';
$table_remote->class = 'databox filters';
$table_remote->size['name'] = '30%';
$table_remote->style['name'] = 'font-weight: bold';

// User
$row = array();
$row['name'] = __('User');
$row['control'] = html_print_input_text('ehorus_user', $config['ehorus_user'], '', 30, 100, true);
$table_remote->data['ehorus_user'] = $row;

// Pass
$row = array();
$row['name'] = __('Password');
$row['control'] = html_print_input_password('ehorus_pass', io_output_password($config['ehorus_pass']), '', 30, 100, true);
$table_remote->data['ehorus_pass'] = $row;

// Directory hostname
$row = array();
$row['name'] = __('API Hostname');
$row['control'] = html_print_input_text('ehorus_hostname', $config['ehorus_hostname'], '', 30, 100, true);
$row['control'] .= ui_print_help_tip(__('Hostname of the eHorus API') . '. ' . __('Without protocol and port') . '. ' . __('e.g., switch.ehorus.com'), true);
$table_remote->data['ehorus_hostname'] = $row;

// Directory port
$row = array();
$row['name'] = __('API Port');
$row['control'] = html_print_input_text('ehorus_port', $config['ehorus_port'], '', 6, 100, true);
$row['control'] .= ui_print_help_tip(__('e.g., 18080'), true);
$table_remote->data['ehorus_port'] = $row;

// Request timeout
$row = array();
$row['name'] = __('Request timeout');
$row['control'] = html_print_input_text('ehorus_req_timeout', $config['ehorus_req_timeout'], '', 3, 10, true);
$row['control'] .= ui_print_help_tip(__('Time in seconds to set the maximum time of the requests to the eHorus API') . '. ' . __('0 to disable'), true);
$table_remote->data['ehorus_req_timeout'] = $row;

// Test
$row = array();
$row['name'] = __('Test');
$row['control'] = html_print_button(__('Start'), 'test-ehorus', false, '', 'class="sub next"', true);
$row['control'] .= '<span id="test-ehorus-spinner" style="display:none;">&nbsp;' . html_print_image('images/spinner.gif', true) . '</span>';
$row['control'] .= '<span id="test-ehorus-success" style="display:none;">&nbsp;' . html_print_image('images/status_sets/default/severity_normal.png', true) . '</span>';
$row['control'] .= '<span id="test-ehorus-failure" style="display:none;">&nbsp;' . html_print_image('images/status_sets/default/severity_critical.png', true) . '</span>';
$row['control'] .= '&nbsp;<span id="test-ehorus-message" style="display:none;"></span>';
$table_remote->data['ehorus_test'] = $row;

/* Print */

// Form enable
echo '<form id="form_enable" method="post">';
if (!$config['ehorus_enabled']) {
	$info_page = "http://ehorus.com/";
	$link = '<a target="_blank" rel="noopener noreferrer" href="' . $info_page . '">this</a>';
	$info_messsage = __('eHorus is a web based remote management system which allows you to easily connect to machines that have internet connection');
	$info_messsage .= '. ' . __('Forget about firewalls and proxies');
	$info_messsage .= '. ' . sprintf(__('Check %s for more info'), $link) . '.';
	ui_print_info_message($info_messsage);
}
html_print_input_hidden('update_config', 1);
html_print_table($table_enable);
echo '</form>';

// Form agents
if ($config['ehorus_enabled']) {
	echo '<form id="form_agents" method="post">';
	$info_messsage = __('eHorus has his own agent identifiers');
	$info_messsage .= '. ' . __('To store them, it will be necessary to use an agent custom field');
	$info_messsage .= '.<br />' . __('Possibly the eHorus id will have to be filled in by hand for every agent') . '.';
	ui_print_info_message($info_messsage);
	
	if (!$custom_field_exists) {
		$error_message = __('The custom field does not exists already');
		ui_print_error_message($error_message);
	}
	echo "<fieldset>";
	echo "<legend>" . __('Pandora agents') . "</legend>";
	html_print_input_hidden('update_config', 1);
	html_print_table($table_agents);
	echo "</fieldset>";
	echo '</form>';
}

// Form remote
if ($config['ehorus_enabled']) {
	echo '<form id="form_remote" method="post">';
	echo "<fieldset>";
	echo "<legend>" . __('eHorus API') . "</legend>";
	html_print_input_hidden ('update_config', 1);
	html_print_table($table_remote);
	echo '<div class="action-buttons" style="width: '.$table_remote->width.'">';
	html_print_submit_button(__('Update'), 'update_button', false, 'class="sub upd"');
	echo '</div>';
	echo "</fieldset>";
	echo '</form>';
}

?>

<script type="text/javascript">
	var showFields = function () {
		$('form#form_agents').show();
		$('form#form_remote').show();
	}
	var hideFields = function () {
		$('form#form_agents').hide();
		$('form#form_remote').hide();
	}
	var handleEnable = function (event) {
		if (event.target.value == '1') showFields();
		else hideFields();
	}
	$('input:radio[name="ehorus_enabled"]').change(handleEnable);
	
	var handleTest = function (event) {
		var user = $('input#text-ehorus_user').val();
		var pass = $('input#password-ehorus_pass').val();
		var host = $('input#text-ehorus_hostname').val();
		var port = $('input#text-ehorus_port').val();
		var timeout = Number.parseInt($('input#text-ehorus_req_timeout').val(), 10);
		
		var timeoutMessage = '<?php echo __('Connection timeout'); ?>';
		var badRequestMessage = '<?php echo __('Empty user or password'); ?>';
		var notFoundMessage = '<?php echo __('User not found'); ?>';
		var invalidPassMessage = '<?php echo __('Invalid password'); ?>';
		
		var hideLoadingImage = function () {
			$('span#test-ehorus-spinner').hide();
		}
		var showLoadingImage = function () {
			$('span#test-ehorus-spinner').show();
		}
		var hideSuccessImage = function () {
			$('span#test-ehorus-success').hide();
		}
		var showSuccessImage = function () {
			$('span#test-ehorus-success').show();
		}
		var hideFailureImage = function () {
			$('span#test-ehorus-failure').hide();
		}
		var showFailureImage = function () {
			$('span#test-ehorus-failure').show();
		}
		var hideMessage = function () {
			$('span#test-ehorus-message').hide();
		}
		var showMessage = function () {
			$('span#test-ehorus-message').show();
		}
		var changeTestMessage = function (message) {
			$('span#test-ehorus-message').text(message);
		}
		
		hideSuccessImage();
		hideFailureImage();
		hideMessage();
		showLoadingImage();

		$.ajax({
			url: 'https://' + host + ':' + port + '/login',
			type: 'POST',
			dataType: 'json',
			timeout: timeout ? timeout * 1000 : 0,
			data: {
				user: user,
				pass: pass
			}
		})
		.done(function(data, textStatus, xhr) {
			showSuccessImage();
		})
		.fail(function(xhr, textStatus, errorThrown) {
			showFailureImage();
			
			if (xhr.status === 400) {
				changeTestMessage(badRequestMessage);
			}
			else if (xhr.status === 401 || xhr.status === 403) {
				changeTestMessage(invalidPassMessage);
			}
			else if (xhr.status === 404) {
				changeTestMessage(notFoundMessage);
			}
			else if (errorThrown === 'timeout') {
				changeTestMessage(timeoutMessage);
			}
			else {
				changeTestMessage(errorThrown);
			}
			showMessage();
		})
		.always(function(xhr, textStatus) {
			hideLoadingImage();
		});
	}
	$('input#button-test-ehorus').click(handleTest);
</script>
