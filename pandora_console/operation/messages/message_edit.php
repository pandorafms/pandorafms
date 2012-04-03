<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Load global vars
global $config;

require_once ('include/functions_users.php');
require_once ('include/functions_groups.php');
require_once ('include/functions_io.php');

//params
$new_msg = get_parameter('new_msg', 0);
$dst_user = get_parameter('dst_user');
$dst_group = get_parameter('dst_group');
$subject = get_parameter('subject', '');
$message = get_parameter('message');
$read_message = get_parameter('read_message', 0);
$reply = get_parameter('reply', 0);
$show_sent = get_parameter('show_sent', 0);

$buttons['message_list'] = array('active' => false,
		'text' => '<a href="index.php?sec=workspace&sec2=operation/messages/message_list">' .
		html_print_image("images/email.png", true, array ("title" => __('Message list'))) .'</a>');

$buttons['sent_messages'] = array('active' => false,
		'text' => '<a href="index.php?sec=workspace&sec2=operation/messages/message_list&amp;show_sent=1">' .
		html_print_image("images/email_go.png", true, array ("title" => __('Sent messages'))) .'</a>');
			
$buttons['create_message'] = array('active' => true,
		'text' => '<a href="index.php?sec=workspace&sec2=operation/messages/message_edit">' .
		html_print_image("images/email_edit.png", true, array ("title" => __('Create message'))) .'</a>');
		
// Header
ui_print_page_header (__('Messages'), "images/email.png", false, "", false, $buttons);

//read a message
if ($read_message) {
	$message_id = (int) get_parameter ("id_message");
	if ($show_sent) {
		$message = messages_get_message_sent ($message_id);
	} else {
		$message = messages_get_message ($message_id);
		messages_process_read ($message_id);
	}
	
	if ($message == false) {
		echo '<div>'.__('This message does not exist in the system').'</div>';
		return; //Move out of this page and go processing other pages
	}
	
	$user_name = get_user_fullname ($message["id_usuario_origen"]);
	if (!$user_name) {
		$user_name = $message["id_usuario_origen"];
	}
	
	$dst_name = get_user_fullname ($message["id_usuario_destino"]);
	if (!$dst_name) {
		$dst_name = $message["id_usuario_destino"];
	}
	
	$table->width = '98%';
	$table->data = array();
	
	$table->data[0][0] = __('From:');
	$table->data[0][1] = $user_name.' '.__('at').' ' . ui_print_timestamp ($message["timestamp"], true, array ("prominent" => "timestamp"));
	
	$table->data[1][0] = __('To:');
	$table->data[1][1] = $dst_name;
	
	$table->data[2][0] = __('Subject');
	$table->data[2][1] = html_print_input_text_extended ("subject", $message["subject"], 'text-subject', '', 50, 70, true, false, '', 'readonly');

	$order   = array("\r\n", "\n", "\r");
	$replace = '<br />';
	$parsed_message = str_replace($order, $replace, $message["mensaje"]);
	
	$table->data[3][0] = __('Message');
	$table->data[3][1] = html_print_textarea ("message", 15, 255, $message["mensaje"], 'readonly', true);

	//Prevent RE: RE: RE:
	if (strstr ($message["subject"], "RE:")) {
		$new_subj = $message["subject"];
	} else {
		$new_subj = "RE: ".$message["subject"];
	}
	
	//Start the message much like an e-mail reply 
	$new_msg = "\n\n\nOn ".date ($config["date_format"], $message["timestamp"]).' '.$user_name.' '.__('wrote').":\n\n".$message["mensaje"];
	
	echo '<form method="post" action="index.php?sec=workspace&amp;sec2=operation/messages/message_list&show_sent=1&amp;delete_message=1&amp;id='.$message_id.'">';
		html_print_table($table);	
		echo "<div style='padding-bottom: 20px; text-align: right; width:" . $table->width . "'>";
			html_print_submit_button(__('Delete'), 'delete_btn', false, 'class="sub delete"');
		echo "</div>";
	echo "</form>";	
	
	echo '<form method="post" action="index.php?sec=workspace&sec2=operation/messages/message_edit&amp;new_msg=1&amp;reply=1">';
		html_print_input_hidden ("dst_user", $message["id_usuario_origen"]);
		html_print_input_hidden ("subject", $new_subj);
		html_print_input_hidden ("message", $new_msg);
		html_print_input_hidden ("orig_user", $message["id_usuario_destino"]);
		echo "<div style='padding-bottom: 20px; text-align: right; width:" . $table->width . "'>";
			html_print_submit_button (__('Reply'), 'reply', false, 'class="sub next"');
		echo '</div>';
	echo '</form>';
	
	return;
}

// Create message (destination user)
if (($new_msg) && (!empty ($dst_user)) && (!$reply)) {
	$return = messages_create_message ($config["id_user"], $dst_user, $subject, $message);

	$user_name = get_user_fullname ($dst_user);
	if (!$user_name) {
		$user_name = $dst_user;
	}
	ui_print_result_message ($return,
		__('Message successfully sent to user %s', $user_name),
		__('Error sending message to user %s', $user_name));
}

// Create message (destination group)
if (($new_msg) && ($dst_group!='') && (!$reply)) {
	$return = messages_create_group ($config["id_user"], $dst_group, $subject, $message);

	ui_print_result_message ($return,
		__('Message successfully sent'),
		__('Error sending message to group %s', groups_get_name ($dst_group)));
}	

//message creation form

//user info
$own_info = get_user_info ($config['id_user']);

$table->width = '98%';

$table->data = array();

$table->data[0][0] = __('From:');

if (!empty($own_info['fullname'])) {
	$table->data[0][1] = $own_info['fullname'];
} else {
	$table->data[0][1] = $config['id_user'];
}

$table->data[1][0] = __('To:');

$users_full = groups_get_users (array_keys(users_get_groups()));
$users = array();
foreach ($users_full as $user_id => $user_info) {
	$users[$user_info['id_user']] = $user_info['fullname'];
}

if ($own_info['is_admin'] || check_acl ($config['id_user'], 0, "PM"))
	$return_all_groups = true;
else	
	$return_all_groups = false;	
		
$groups = users_get_groups ($config["id_user"], "AR"); //Get a list of all groups

$table->data[1][1] = html_print_select ($users, "dst_user", $dst_user, '', __('Select user'), false, true, false, '', false);
$table->data[1][1] .= '&nbsp;&nbsp;'.__('OR').'&nbsp;&nbsp;';
$table->data[1][1] .= html_print_select_groups($config['id_user'], "AR", $return_all_groups, 'dst_group', $dst_group, '', __('Select group'), '', true);

$table->data[2][0] = __('Subject');
$table->data[2][1] = html_print_input_text ("subject", $subject, '', 50, 70, true);

$table->data[3][0] = __('Message');
$table->data[3][1] = html_print_textarea ("message", 15, 255, $message, '', true);

echo '<form method="post" action="index.php?sec=workspace&amp;sec2=operation/messages/message_edit&amp;new_msg=1">';
html_print_table($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
		html_print_submit_button (__('Send message'), 'send_mes', false, 'class="sub wand"');
echo '</form>';
echo '</div>';
?>
