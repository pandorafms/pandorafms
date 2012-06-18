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

if (is_ajax ()) {
	global $config;
	
	require_once("include/functions_users.php");
	
	$status = check_login(false);
	
	if ($status) {
		$get_last_messages = (bool)get_parameter('get_last_messages', 0);
		$send_message = (bool)get_parameter('send_message', 0);
		$send_login = (bool)get_parameter('send_login', 0);
		$send_logout = (bool)get_parameter('send_logout', 0);
		$long_polling_check_messages = (bool)get_parameter('long_polling_check_messages', 0);
		$get_last_global_counter = (bool)get_parameter('get_last_global_counter', 0);
		$check_users = (bool)get_parameter('check_users', 0);
		
		if ($get_last_messages) {
			$time = (int)get_parameter('time', 24 * 60 * 60);
			users_get_last_messages($time);
		}
		if ($send_message) {
			$message = get_parameter('message', false);
			if (!empty($message))
				users_save_text_message($message);
		}
		if ($send_login) {
			users_save_login();
		}
		if ($send_logout) {
			users_save_logout();
		}
		if ($long_polling_check_messages) {
			$global_counter = (int)get_parameter('global_counter', 0);
			users_long_polling_check_messages($global_counter);
		}
		if ($get_last_global_counter) {
			users_get_last_global_counter();
		}
		
		if ($check_users) {
			users_check_users();
		}
	}
	else {
		echo json_encode(array('correct' => false));
	}
	
	return;
}

global $config;

check_login ();

ui_print_page_header (__('Webchat'), "images/group.png", false, "", false, "");

$table = null;

$table->width = '95%';
$table->style[0][1] = 'text-align: right; vertical-align: top;';

$table->data[0][0] = '<div id="chat_box" style="width: 95%;
	height: 300px; background: #ffffff; border: 1px inset black;
	overflow: auto; padding: 10px;"></div>';
$table->data[0][1] = '<h4>' . __('Users Online') . '</h4>' .
	'<div id="userlist_box" style="width: 90% !important; height: 200px !important;
		height: 300px; background: #ffffff; border: 1px inset black;
		overflow: auto; padding: 10px;"></div>';
$table->data[1][0] = html_print_input_text('message_box', '', '',
	100, 150, true);
$table->data[1][1] = html_print_button('send', 'send', false, 'send_message()',
	'class="sub next" style="width: 100%"', true);

html_print_table($table);
?>
<script type="text/javascript">
	var global_counter_chat = 0;
	var chat_log = '';
	var user_move_scroll = false;
	var first_time = true;
	
	$(document).ready(function() {
		$("input[name=\"message_box\"]").keydown(function(e) {
			//Enter key.
			if (e.keyCode == 13) {
				send_message();
			}
		});
		
		init_webchat();
	});
	
	$(window).unload(function () {
		exit_webchat();
	});
	
	function init_webchat() {
		send_login_message();
		long_polling_check_messages();
		check_users();
	}
	
	function check_users() {
		var parameters = {};
		parameters['page'] = "operation/users/webchat";
		parameters['check_users'] = 1;
		
		$.ajax({
			type: 'POST',
			url: 'ajax.php',
			data: parameters,
			dataType: "json",
			success: function(data) {
				if (data['correct'] == 1) {
					$("#userlist_box").html(data['users']);
				}
			}
		});
	}
	
	function long_polling_check_messages() {
		var parameters = {};
		parameters['page'] = "operation/users/webchat";
		parameters['long_polling_check_messages'] = 1;
		parameters['global_counter'] = global_counter_chat;
		
		$.ajax({
			type: 'POST',
			url: 'ajax.php',
			data: parameters,
			dataType: "json",
			success: function(data) {
				if (data['correct'] == 1) {
					check_users();
					
					if (first_time) {
						print_messages({
							0: {'type' : 'notification',
								'text': '<?php echo __('Connection established...get last 24h messages...');?>'}
							}, true);
						first_time = false;
					}
					global_counter_chat = data['global_counter'];
					print_messages(data['log']);
				}
				else {
					if (data['error']) {
						print_messages({
							0: {'type' : 'error',
								'text': '<?php echo __('Error in connection.');?>'}
							}, false);
					}
				}
				long_polling_check_messages();
			},
			error: function(request, status, err) {
				long_polling_check_messages();
			}
		});
	}
	
	function print_messages(messages, clear_chat_box) {
		//event_add_text = true;
		
		if (typeof(clear_chat_box) == 'undefined') {
			clear_chat_box = false;
		}
		
		var html = '';
		
		$.each(messages, function(key, message) {
			html = html + '<div ';
			
			if (message['type'] == 'error') {
				html = html +  "style='color: red; font-style: italic;'";
			}
			else if (message['type'] == 'notification') {
				html = html +  "style='color: grey; font-style: italic;'";
			}
			else if (message['type'] == 'message') {
				html = html +  "style='color: black; font-style: normal;'";
			}
			html = html + '>';
			
			if (message['type'] != 'message') {
				html = html + message['text'];
			}
			else {
				html = html +
					'<span style="color: grey; font-style: italic;">' +
					message['human_time'] + '</span>';
				html = html + ' ' +
					'<span style="color: black; font-weight: bolder;">' +
					message['user_name'] + ':&gt; </span>';
				html = html + ' ' + message['text'];
			}
			
			html = html + '</div>';
		});
		
		
		
		if (clear_chat_box) {
			$("#chat_box").html(html);
		}
		else {
			$("#chat_box").append(html);
		}
		
		$("#chat_box").scrollTop($("#chat_box").attr('scrollHeight'));
	}
	
	function send_message() {
		var parameters = {};
		parameters['page'] = "operation/users/webchat";
		parameters['send_message'] = 1;
		parameters['message'] = $("input[name=\"message_box\"]").val();
		
		$.ajax({
			type: 'POST',
			url: 'ajax.php',
			data: parameters,
			dataType: "json",
			success: function(data) {
				if (data['correct'] == 1) {
					$("input[name=\"message_box\"]").val('');
				}
				else {
					print_messages({
						0: {'type' : 'error',
							'text': '<?php echo __('Error sendding message.');?>'}
						}, false);
				}
			}
		});
	}
	
	function exit_webchat() {
		send_logout_message();
		get_last_global_counter();
	}
	
	function send_login_message() {
		var parameters = {};
		parameters['page'] = "operation/users/webchat";
		parameters['send_login'] = 1;
		
		$.ajax({
			type: 'POST',
			url: 'ajax.php',
			data: parameters,
			dataType: "json",
			success: function(data) {
				if (data['correct'] == 1) {
				}
				else {
					print_messages({
						0: {'type' : 'error',
							'text': '<?php echo __('Error login.');?>'}
						}, false);
				}
			}
		});
	}
	
	function send_logout_message() {
		var parameters = {};
		parameters['page'] = "operation/users/webchat";
		parameters['send_logout'] = 1;
		
		$.ajax({
			type: 'POST',
			url: 'ajax.php',
			data: parameters,
			dataType: "json",
			success: function(data) {
			}
		});
	}
</script>
