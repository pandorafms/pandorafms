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
require_once ("include/functions_messages.php");
require_once ('include/functions_users.php');
require_once ('include/functions_groups.php');

//First Queries - also inits the variables so it can be passed along
$dest_user = get_parameter ("dest_user");
$dest_group = get_parameter ("dest_group");
$subject = get_parameter ("subject");
$message = get_parameter ("mensaje");
$send_message = (bool)get_parameter('send_mes', false);

if (isset ($_GET["new_msg"])) 
	ui_print_page_header (__('Messages'). " &raquo;  ".__('New message'), "images/email.png", false, "", false, "" );
elseif (isset ($_GET["read_message"]))
	ui_print_page_header (__('Messages'). " &raquo;  ".__('Read message'), "images/email.png", false, "", false, "" );
else
	if (empty ($config["pure"]) && !is_ajax ())
		ui_print_page_header (__('Messages'). " &raquo;  ".__('Message overview'), "images/email.png", false, "", false, "" );
	
if (isset ($_GET["delete_message"])) {
	$id = (int) get_parameter ("id");
	$result = messages_delete_message ($id); //Delete message function will actually check the credentials
	
	ui_print_result_message ($result,
		__('Successfully deleted'),
		__('Could not be deleted'));
}

$message_sended = false;

if (!empty ($dest_user) && isset ($_GET["send_message"])) {
	// Create message
	$return = messages_create_message ($config["id_user"], $dest_user, $subject, $message);
	
	if ($return) {
		$message_sended = true;
	}
	
	ui_print_result_message ($return,
		__('Message successfully sent to user %s', get_user_fullname ($dest_user)),
		__('Error sending message to user %s', get_user_fullname ($dest_user)));
}

if (!empty ($dest_group) && isset ($_GET["send_message"])) {
	// Create message to groups
	$return = messages_create_group ($config["id_user"], $dest_group, $subject, $message);
	
	if ($return) {
		$message_sended = true;
	}
	
	ui_print_result_message ($return,
		__('Message successfully sent'),
		__('Error sending message to group %s', groups_get_name ($dest_group)));
}

$new_msg = (bool) get_parameter("new_msg");

if ($send_message && !$message_sended && !$new_msg) {
	ui_print_error_message("Error sending message, please choose group or user.");
}

if (isset ($_GET["mark_read"]) || isset ($_GET["mark_unread"])) {
	$id_r = (int) get_parameter ("mark_read");
	$id_u = (int) get_parameter ("mark_unread");
	if (!empty ($id_r)) {
		//Set to read
		messages_process_read ($id_r);
	} elseif (!empty ($id_u)) {
		//Set to unread
		messages_process_read ($id_u, 0);
	}
}

if (isset ($_GET["new_msg"]) || ($send_message && !$message_sended)) { //create message

// Header
//	ui_print_page_header (__('Messages'). " &raquo;  ".__('New message'), "images/email.png", false, "", false, "" );

	echo '<form method="POST" action="index.php?sec=workspace&amp;sec2=operation/messages/message&amp;send_message=1">
	<table width="98%" class="databox_color" cellpadding="4" cellspacing="4">
	<tr>
		<td class="datos">'.__('Message from').':</td>
		<td class="datos"><b>' . ui_print_username ($config["id_user"], true).'</b></td>
	</tr><tr>
		<td class="datos2">'.__('Message to').':</td>
		<td class="datos2">';
	
	$users_full = groups_get_users(array_keys(users_get_groups()));

	$users = array();
	foreach ($users_full as $user_id => $user_info) {
		$users[$user_info['id_user']] = $user_info['fullname'];
	}
		
	$groups = users_get_groups ($config["id_user"], "AR"); //Get a list of all groups
		
	html_print_select ($users, "dest_user", $dest_user, '', __('-Select user-'), false, false, false, '', false);
	echo ' - '.__('OR').' - ';
	html_print_select_groups($config["id_user"], "AR", true, "dest_group", $dest_group, '', __('-Select group-'), false, false, false, '', false);
	
	echo '</td></tr><tr><td class="datos">'.__('Subject').':</td><td class="datos">';
	html_print_input_text ("subject", urldecode($subject), '', 50, 70, false);
	
	echo '</td></tr><tr><td class="datos2">'.__('Message').':</td><td class="datos">';
	
	html_print_textarea ("mensaje", 15, 70, $message, '', false);
	
	echo '</td></tr><tr><td></td><td colspan="3">';
	
	html_print_submit_button (__('Send message'), 'send_mes', false, 'class="sub wand"', false);
	
	echo '</td></tr></table></form>';

} elseif (isset ($_GET["read_message"])) {

//	ui_print_page_header (__('Messages'). " &raquo;  ".__('Read message'), "images/email.png", false, "", false, "" );

	$message_id = (int) get_parameter ("read_message");
	$message = messages_get_message ($message_id);
	
	if ($message == false) {
		echo '<div>'.__('This message does not exist in the system').'</div>';
		return; //Move out of this page and go processing other pages
	}
	
	messages_process_read ($message_id);
	
	echo '<form method="post" action="index.php?sec=workspace&amp;sec2=operation/messages/message&amp;new_msg=1">
			<table class="databox_color" width="98%" cellpadding="4" cellspacing="4">
			<tr><td class="datos">'.__('Message from').':</td>
			<td class="datos"><b>' . ui_print_username ($message["sender"], true).' '.__('at').' ' . ui_print_timestamp ($message["timestamp"], true, array ("prominent" => "timestamp")).'</b></td></tr>';
	
	// Subject
	echo '<tr><td class="datos2">'.__('Subject').':</td>
	<td class="datos2" valign="top"><b>'.$message["subject"].'</b></td></tr>';
	
	// text
	
	$order   = array("\r\n", "\n", "\r");
	$replace = '<br />';
	$parsed_message = str_replace($order, $replace, $message["message"]);

	echo '<tr><td class="datos" valign="top">'.__('Message').':</td>
	<td class="datos">'.$parsed_message.'</td></tr></table>';
	
	//Prevent RE: RE: RE:
	if (strstr ($message["subject"], "RE:")) {
		$new_subj = $message["subject"];
	} else {
		$new_subj = "RE: ".$message["subject"];
	}


	//Start the message much like an e-mail reply 
	$new_msg = "\n\n\nOn ".date ($config["date_format"], $message["timestamp"]).' '.get_user_fullname ($message["sender"]).' '.__('wrote').":\n\n".$message["message"];
	
	html_print_input_hidden ("dest_user", $message["sender"]);
	html_print_input_hidden ("subject", urlencode ($new_subj));
	html_print_input_hidden ("message", urlencode ($new_msg));

	echo '<div style="text-align:right; width:98%;">';
	html_print_submit_button (__('Reply'), "reply_btn", false, 'class="sub next"'); 
	echo '</div></form>';
	return;
} 

if (isset ($_GET["read_message"]) || !isset ($_GET["new_msg"]) && !($send_message && !$message_sended)) {	
//	if (empty ($config["pure"]) && !is_ajax ()) {
//		ui_print_page_header (__('Messages'). " &raquo;  ".__('Message overview'), "images/email.png", false, "", false, "" );
//	}

	//Get number of messages
	$num_messages = messages_get_count ($config["id_user"]);

	$order = get_parameter ("msg_overview_order", "status");
	$order_dir = get_parameter ("msg_overview_orddir", "ASC");
	
	$messages = messages_get_overview ($order, $order_dir);
	
	if ($num_messages > 0 && empty ($config["pure"]) && !is_ajax ()) {
		echo '<p>'.__('You have').' <b>'.$num_messages.'</b> '.html_print_image ("images/email.png", true).' '.__('unread message(s)').'.</p>';
	}
	
	if (empty ($messages)) {
		echo '<div class="nf">'.__('There are no messages').'</div>';
	} else {
		$table->width = "98%";
		$table->class = "databox";
		$table->cellpadding = 4;
		$table->cellspacing = 4;
		$table->head = array ();
		$table->data = array ();
		$table->align = array ();
		$table->size = array ();
		
		$table->head[0] = __('Status');
		$table->head[1] = __('Sender');
		$table->head[2] = __('Subject');
		$table->head[3] = __('Timestamp');
		$table->head[4] = __('Delete');
		
		$table->align[0] = "center";
		$table->align[1] = "center";
		$table->align[2] = "center";
		$table->align[3] = "center";
		$table->align[4] = "center";
		
		$table->size[0] = "20px";
		$table->size[1] = "120px";
		$table->size[3] = "80px";
		$table->size[4] = "20px";
		
		foreach ($messages as $message_id => $message) {
			$data = array ();
			$data[0] = '';
			if ($message["status"] == 1) {
				$data[0] .= '<a href="index.php?sec=workspace&amp;sec2=operation/messages/message&amp;mark_unread='.$message_id.'">';
				$data[0] .= html_print_image ("images/email_open.png", true, array ("border" => 0, "title" => __('Mark as unread')));
				$data[0] .= '</a>';
			} else {
				$data[0] .= '<a href="index.php?sec=workspace&amp;sec2=operation/messages/message&amp;read_message='.$message_id.'">';
				$data[0] .= html_print_image ("images/email.png", true, array ("border" => 0, "title" => __('Message unread - click to read')));
				$data[0] .= '</a>';
			}
			
			$data[1] = ui_print_username ($message["sender"], true);
			
			$data[2] = '<a href="index.php?sec=workspace&amp;sec2=operation/messages/message&amp;read_message='.$message_id.'">';
			if ($message["subject"] == "") {
				$data[2] .= __('No Subject');
			} else {
				$data[2] .= $message["subject"];
			}
			$data[2] .= '</a>';
			
			$data[3] = ui_print_timestamp ($message["timestamp"], true, array ("prominent" => "timestamp"));
			
			$data[4] = '<a href="index.php?sec=workspace&amp;sec2=operation/messages/message&delete_message=1&id='.$message_id.'"
		onClick="javascript:if (!confirm(\''.__('Are you sure?').'\')) return false;">' .
			html_print_image ('images/cross.png', true, array("title" => __('Delete'))) . '</a>'; //"delete_message", "images/cross.png", $message_id, 'border:0px;', true);
			array_push ($table->data, $data);
		}

		echo '<form method="post" action="index.php?sec=workspace&amp;sec2=operation/messages/message">';
		html_print_table ($table);
		echo '</form>';
	}
	echo '<div class="action-buttons" style="width:98%">';
	echo '<form method="post" action="index.php?sec=workspace&amp;sec2=operation/messages/message&amp;new_msg=1">';
	html_print_submit_button (__('New message'), "send_mes", false, 'class="sub next"');
	echo '</form></div>';
}
?>
