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

include_once ('include/functions_messages.php');

$delete_msg = get_parameter('delete_message',0);
$multiple_delete = get_parameter('multiple_delete',0);
$show_sent = get_parameter('show_sent', 0);
$mark_unread = get_parameter('mark_unread', 0);

$active_list = true;
$active_sent = false;
if ($show_sent) {
	$active_list = false;
	$active_sent = true;
}

$buttons['message_list'] = array('active' => $active_list,
		'text' => '<a href="index.php?sec=workspace&sec2=operation/messages/message_list">' .
		html_print_image("images/email.png", true, array ("title" => __('Message list'))) .'</a>');
		
$buttons['sent_messages'] = array('active' => $active_sent,
		'text' => '<a href="index.php?sec=workspace&sec2=operation/messages/message_list&amp;show_sent=1">' .
		html_print_image("images/email_go.png", true, array ("title" => __('Sent messages'))) .'</a>');
		
$buttons['create_message'] = array('active' => false,
		'text' => '<a href="index.php?sec=workspace&sec2=operation/messages/message_edit">' .
		html_print_image("images/email_edit.png", true, array ("title" => __('Create message'))) .'</a>');

if (!is_ajax ()) {
	ui_print_page_header (__('Messages'), "images/email.png", false, "", false, $buttons);
}

if ($mark_unread) {
	$message_id = get_parameter('id_message');
	messages_process_read ($message_id, false);
}

if ($delete_msg) {
	$id = (int) get_parameter ("id");
	$result = messages_delete_message ($id); //Delete message function will actually check the credentials
	
	ui_print_result_message ($result,
		__('Successfully deleted'),
		__('Could not be deleted'));
}

if ($multiple_delete) {
	$ids = (array)get_parameter('delete_multiple', array());
	
	db_process_sql_begin();
	
	foreach ($ids as $id) {
		$result = db_process_sql_delete ('tmensajes',
			array ('id_mensaje' => $id));
	
		if ($result === false) {
			db_process_sql_rollback();
			break;
		}
	}
	
	if ($result !== false) {
		db_process_sql_commit();
	}
	
	ui_print_result_message ($result,
		__('Successfully deleted'),
		__('Not deleted. Error deleting messages'));
}

if ($show_sent) { //sent view
	$num_messages = messages_get_count_sent($config['id_user']);
	if ($num_messages > 0 && !is_ajax()) {
		echo '<p>'.__('You have').' <b>'.$num_messages.'</b> '.' '.__('sent message(s)').'.</p>';
	}
	$messages = messages_get_overview_sent ('', 'DESC');
} else { //messages received
	$num_messages = messages_get_count ($config["id_user"]);
	if ($num_messages > 0 && !is_ajax()) {
		echo '<p>'.__('You have').' <b>'.$num_messages.'</b> '.' '.__('unread message(s)').'.</p>';
	}
	$messages = messages_get_overview ();
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
	
	$table->align[0] = "center";
	$table->align[1] = "center";
	$table->align[2] = "center";
	$table->align[3] = "center";
	$table->align[4] = "center";
	
	$table->size[0] = "20px";
	$table->size[1] = "100px";
	$table->size[3] = "80px";
	$table->size[4] = "60px";
	
	$table->head[0] = __('Status');
	if ($show_sent)
		$table->head[1] = __('To');
	else
		$table->head[1] = __('From');
	$table->head[2] = __('Subject');
	$table->head[3] = __('Timestamp');
	$table->head[4] = __('Delete'). html_print_checkbox('all_delete', 0, false, true, false, 'check_all_checkboxes();');
	
	foreach ($messages as $message_id => $message) {
		$data = array ();
		$data[0] = '';
		if ($message["status"] == 1) {
			if ($show_sent) {
				$data[0] .= '<a href="index.php?sec=workspace&amp;sec2=operation/messages/message_edit&read_message=1&amp;show_sent=1&amp;id_message='.$message_id.'">';
				$data[0] .= html_print_image ("images/email_open.png", true, array ("border" => 0, "title" => __('Click to read')));
				$data[0] .= '</a>';
			} else { 
				$data[0] .= '<a href="index.php?sec=workspace&amp;sec2=operation/messages/message_list&amp;mark_unread=1&amp;id_message='.$message_id.'">';
				$data[0] .= html_print_image ("images/email_open.png", true, array ("border" => 0, "title" => __('Mark as unread')));
				$data[0] .= '</a>';
			}
		} else {
			if ($show_sent) {
				$data[0] .= '<a href="index.php?sec=workspace&amp;sec2=operation/messages/message_edit&amp;read_message=1&amp;show_sent=1&amp;id_message='.$message_id.'">';
				$data[0] .= html_print_image ("images/email.png", true, array ("border" => 0, "title" => __('Message unread - click to read')));
				$data[0] .= '</a>';
			} else {
				$data[0] .= '<a href="index.php?sec=workspace&amp;sec2=operation/messages/message_edit&amp;read_message=1&amp;id_message='.$message_id.'">';
				$data[0] .= html_print_image ("images/email.png", true, array ("border" => 0, "title" => __('Message unread - click to read')));
				$data[0] .= '</a>';
			}
		}
		
		if ($show_sent) {
			$dest_user = get_user_fullname ($message["dest"]);
			if (!$dest_user) {
				$dest_user = $message["dest"];
			}
			$data[1] = $dest_user;
		} else {
			$orig_user = get_user_fullname ($message["sender"]);
			if (!$orig_user) {
				$orig_user = $message["sender"];
			}
			$data[1] = $orig_user;
		}
		
		if ($show_sent) {
			$data[2] = '<a href="index.php?sec=workspace&amp;sec2=operation/messages/message_edit&amp;read_message=1&show_sent=1&amp;id_message='.$message_id.'">';
		} else {
			$data[2] = '<a href="index.php?sec=workspace&amp;sec2=operation/messages/message_edit&amp;read_message=1&amp;id_message='.$message_id.'">';
		}
		if ($message["subject"] == "") {
			$data[2] .= __('No Subject');
		} else {
			$data[2] .= $message["subject"];
		}
		$data[2] .= '</a>';
		
		$data[3] = ui_print_timestamp ($message["timestamp"], true, array ("prominent" => "timestamp"));
		
		if ($show_sent) {
			$data[4] = '<a href="index.php?sec=workspace&amp;sec2=operation/messages/message_list&show_sent=1&delete_message=1&id='.$message_id.'"
						onClick="javascript:if (!confirm(\''.__('Are you sure?').'\')) return false;">' .
						html_print_image ('images/cross.png', true, array("title" => __('Delete'))) . '</a>'.
						html_print_checkbox_extended ('delete_multiple[]', $message_id, false, false, '', 'class="check_delete"', true);
		} else {
			$data[4] = '<a href="index.php?sec=workspace&amp;sec2=operation/messages/message_list&delete_message=1&id='.$message_id.'"
						onClick="javascript:if (!confirm(\''.__('Are you sure?').'\')) return false;">' .
						html_print_image ('images/cross.png', true, array("title" => __('Delete'))) . '</a>'.
						html_print_checkbox_extended ('delete_multiple[]', $message_id, false, false, '', 'class="check_delete"', true);
		}
		array_push ($table->data, $data);
	}
	if ($show_sent)
		echo '<form method="post" action="index.php?sec=workspace&amp;sec2=operation/messages/message_list&show_sent=1&amp;multiple_delete=1">';
	else
		echo '<form method="post" action="index.php?sec=workspace&amp;sec2=operation/messages/message_list&amp;multiple_delete=1">';
			html_print_table($table);
			echo "<div style='padding-bottom: 20px; text-align: right; width:" . $table->width . "'>";
				html_print_submit_button(__('Delete'), 'delete_btn', false, 'class="sub delete"');
			echo "</div>";
	echo "</form>";	
}

echo '<form method="post" action="index.php?sec=workspace&sec2=operation/messages/message_edit">';
echo '<div class="action-buttons" style="width: 98%;">';
html_print_submit_button (__('Create message'), 'create', false, 'class="sub next"');
echo "</div>";
echo "</form>";
?>

<script type="text/javascript">

function check_all_checkboxes() {
	if ($("input[name=all_delete]").attr('checked')) {
		$(".check_delete").attr('checked', true);
	}
	else {
		$(".check_delete").attr('checked', false);
	}
}

</script>
