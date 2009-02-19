<?php
// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// Load global vars
require_once ("include/config.php");
require_once ("include/functions_messages.php");

//First Queries - also inits the variables so it can be passed along
$dest_user = get_parameter ("dest_user");
$dest_group = get_parameter ("dest_group");
$subject = urldecode (get_parameter ("subject"));
$message = urldecode (get_parameter ("message"));

if (isset ($_POST["delete_message"])) {
	$id = (int) get_parameter_post ("delete_message");
	$result = delete_message ($id); //Delete message function will actually check the credentials
	
	print_error_message ($result, __('Message successfully deleted'), __('There was a problem deleting the message'));
}

if (!empty ($dest_user) && isset ($_GET["send_message"])) {
	// Create message
	$return = create_message ($config["id_user"], $dest_user, $subject, $message);
	print_error_message ($return, __('Message successfully sent to user: ').get_user_fullname ($dest_user), __('Error sending message to user: ').get_user_fullname ($dest_user));
}

if (!empty ($dest_group) && isset ($_GET["send_message"])) {
	// Create message to groups
	$return = create_message_group ($config["id_user"], $dest_group, $subject, $message);
	print_error_message ($return, __('Message successfully sent'), __('Error sending message to group: ').get_group_name ($dest_group));
}

if (isset ($_GET["mark_read"]) || isset ($_GET["mark_unread"])) {
	$id_r = (int) get_parameter ("mark_read");
	$id_u = (int) get_parameter ("mark_unread");
	if (!empty ($id_r)) {
		//Set to read
		process_message_read ($id_r);
	} elseif (!empty ($id_u)) {
		//Set to unread
		process_message_read ($id_u, 0);
	}
}

if (isset ($_GET["new_msg"])) { //create message
	echo "<h2>".__('Messages')." &gt; ".__('New message').'</h2>';
	echo '<form method="POST" action="index.php?sec=messages&amp;sec2=operation/messages/message&amp;send_message=1">
	<table width="600" class="databox_color" cellpadding="4" cellspacing="4">
	<tr>
		<td class="datos">'.__('From').':</td>
		<td class="datos"><b>'.print_username ($config["id_user"], true).'</b></td>
	</tr><tr>
		<td class="datos2">'.__('To').':</td>
		<td class="datos2">';
		
	$users = get_users_info (); //Get a list of all users
	$groups = get_user_groups ($config["id_user"], "AR"); //Get a list of all groups
		
	print_select ($users, "dest_user", $dest_user, '', __('-Select user-'), false, false, false, '', false);
	echo ' - '.__('OR').' - ';
	print_select ($groups, "dest_group", $dest_group, '', __('-Select group-'), false, false, false, '', false);
	
	echo '</td></tr><tr><td class="datos">'.__('Subject').':</td><td class="datos">';
	print_input_text ("subject", $subject, '', 50, 70, false);
	
	echo '</td></tr><tr><td class="datos2">'.__('Message').':</td><td class="datos">';
	
	print_textarea ("mensaje", 15, 70, $message, '', false);
	
	echo '</td></tr><tr><td></td><td colspan="3">';
	
	print_submit_button (__('Send message'), 'send_mes', false, 'class="sub wand"', false);
	
	echo '</td></tr></table></form>';

} elseif (isset ($_GET["read_message"])) {
	echo "<h2>".__('Messages')." &gt; ".__('Read message').'</h2>';
	
	$message_id = (int) get_parameter ("read_message");
	$message = get_message ($message_id);
	
	if ($message == false) {
		echo '<div>'.__('This message does not exist in the system').'</div>';
		return; //Move out of this page and go processing other pages
	}
	
	process_message_read ($message_id);
	
	echo '<form method="post" action="index.php?sec=messages&amp;sec2=operation/messages/message&amp;new_msg=1">
			<table class="databox_color" width="650" cellpadding="4" cellspacing="4">
			<tr><td class="datos">'.__('From').':</td>
			<td class="datos"><b>'.print_username ($message["sender"], true).' '.__('at').' '.print_timestamp ($message["timestamp"], true, array ("prominent" => "timestamp")).'</b></td></tr>';
	
	// Subject
	echo '<tr><td class="datos2">'.__('Subject').':</td>
	<td class="datos2" valign="top"><b>'.$message["subject"].'</b></td></tr>';
	
	// text
	echo '<tr><td class="datos" valign="top">'.__('Message').':</td>
	<td class="datos"><pre>'.$message["message"].'</pre></td></tr></table>';
	
	//Prevent RE: RE: RE:
	if (strstr ($message["subject"], "RE:")) {
		$new_subj = $message["subject"];
	} else {
		$new_subj = "RE: ".$message["subject"];
	}
	
	//Start the message much like an e-mail reply 
	$new_msg = "\n\n\nOn ".date ($config["date_format"], $message["timestamp"]).' '.get_user_fullname ($message["sender"]).' '.__('wrote').":\n\n".$message["message"];
	
	print_input_hidden ("dest_user", $message["sender"]);
	print_input_hidden ("subject", urlencode ($new_subj));
	print_input_hidden ("message", urlencode ($new_msg));
	
	echo '<div style="text-align:right; width:600px;">';
	print_submit_button (__('Reply'), "reply_btn", false, 'class="sub next"'); 
	echo '</div></form>';
} 

if (isset ($_GET["read_message"]) || !isset ($_GET["new_msg"])) {	
	if (empty ($config["pure"]) && !defined ('AJAX')) {
		echo "<h2>".__('Messages')." &gt; ".__('Message overview').'</h2>';
	}

	//Get number of messages
	$num_messages = get_message_count ($config["id_user"]);

	$order = get_parameter ("msg_overview_order", "status");
	$order_dir = get_parameter ("msg_overview_orddir", "ASC");
	
	$messages = get_message_overview ($order, $order_dir);
	
	if ($num_messages > 0 && empty ($config["pure"]) && !defined ('AJAX')) {
		echo '<p>'.__('You have').' <b>'.$num_messages.'</b> '.print_image ("images/email.png", true).' '.__('unread message(s)').'.</p>';
	}
	
	if (empty ($messages)) {
		echo '<div class="nf">'.__('There are no messages').'</div>';
	} else {
		$table->width = "100%";
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
				$data[0] .= '<a href="index.php?sec=messages&amp;sec2=operation/messages/message&amp;mark_unread='.$message_id.'">';
				$data[0] .= print_image ("images/email_open.png", true, array ("border" => 0, "title" => __('Mark as unread')));
				$data[0] .= '</a>';
			} else {
				$data[0] .= '<a href="index.php?sec=messages&amp;sec2=operation/messages/message&amp;read_message='.$message_id.'">';
				$data[0] .= print_image ("images/email.png", true, array ("border" => 0, "title" => __('Message unread - click to read')));
				$data[0] .= '</a>';
			}
			
			$data[1] = print_username ($message["sender"], true);
			
			$data[2] = '<a href="index.php?sec=messages&amp;sec2=operation/messages/message&amp;read_message='.$message_id.'">';
			if ($message["subject"] == "") {
				$data[2] .= __('No Subject');
			} else {
				$data[2] .= $message["subject"];
			}
			$data[2] .= '</a>';
			
			$data[3] = print_timestamp ($message["timestamp"], true, array ("prominent" => "timestamp"));
			
			$data[4] = print_input_image ("delete_message", "images/cross.png", $message_id, 'border:0px;', true);
			array_push ($table->data, $data);
		}

		echo '<form method="post" action="index.php?sec=messages&amp;sec2=operation/messages/message&amp;new_msg=1">';
		print_table ($table);
		echo '</form>';
	}
	echo '<div class="action-buttons" style="width:100%">';
	echo '<form method="post" action="index.php?sec=messages&amp;sec2=operation/messages/message&amp;new_msg=1">';
	print_submit_button (__('New message'), "send_mes", false, 'class="sub next"');
	echo '</form></div>';
}
?>
