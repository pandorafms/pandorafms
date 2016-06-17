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
include_once ("include/functions_update_manager.php");
check_login ();

if (! check_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user'])) {
	db_pandora_audit("ACL Violation", "Trying to access Setup Management");
	require ("general/noaccess.php");
	return;
}

if (is_ajax()) {

	$not_read_single = get_parameter ('not_read_single', 0);
	
	if ($not_read_single) {
		$message_id = get_parameter ('message_id', 0);
		update_manger_set_read_message ($message_id, 1);
		update_manager_remote_read_messages ($message_id);
	}
	
	return;
}

$not_read_action = get_parameter('not_read_button', false);
$read_action = get_parameter('read_button', false);
$delete_action = get_parameter ('delete_button', false);

if ($not_read_action !== false) {
	
	$selected = get_parameter ('select_multiple', false);
	foreach ($selected as $k => $message_id) {
		
		update_manger_set_read_message ($message_id, 0);
	}
}

if ($read_action !== false) {
	
	$selected = get_parameter ('select_multiple', false);
	foreach ($selected as $k => $message_id) {
		
		update_manger_set_read_message ($message_id, 1);
	}
}

if ($delete_action !== false) {
	
	$selected = get_parameter ('select_multiple', false);
	foreach ($selected as $k => $message_id) {
		
		update_manger_set_deleted_message ($message_id);
	}
	
}

$offset = (int) get_parameter ('offset', 0);

$total_messages = update_manager_get_not_deleted_messages ();
if ($total_messages){

	// Get all messages
	$sql = 'SELECT data, svn_version, filename, data_rollback, db_field_value FROM tupdate ';
	$sql .= 'WHERE description NOT LIKE \'%"' . $config['id_user'] . '":1%\' ';
	$sql .= 'OR description IS NULL ';
	$sql .= 'ORDER BY svn_version DESC ';
	$sql .= 'LIMIT ' . $offset . ',' . $config['block_size'] . ' ';
	$um_messages = array ();
	$um_messages = db_get_all_rows_sql ($sql);

	echo '<form method="post" action="index.php?sec=gsetup&sec2=godmode/update_manager/update_manager&tab=setup">';

	html_print_input_hidden ('tab', 'messages');
	html_print_input_hidden ('offset', $offset);
	echo '<div class="action-buttons" style="float:right; padding: 10px 5px">';
	html_print_submit_button (__('Delete'), 'delete_button', false,
		'class="sub delete"');
	echo '</div>';

	echo '<div class="action-buttons" style="float:right; padding: 10px 5px">';
	html_print_submit_button (__('Mark as not read'), 'not_read_button', false,
		'class="sub wand"');
	echo '</div>';

	echo '<div class="action-buttons" style="float:right; padding: 10px 5px">';
	html_print_submit_button (__('Mark as read'), 'read_button', false,
		'class="sub upd"');
	echo '</div>';
	
	// Pagination
	if ($total_messages > $config['block_size']) {
		ui_pagination (update_manager_get_total_messages (), false, 0);
	}

	$table = new stdClass();
		$table->width = '100%';
		$table->class = 'databox data';
		$table->cellpadding = 4;
		$table->cellspacing = 4;
		$table->head = array ();
		$table->data = array ();
		$table->align = array ();
		$table->size = array ();
		$table->id = 'um_messages_table';
		
		$table->align[0] = "left";
		$table->align[1] = "left";
		$table->align[2] = "left";
		$table->align[3] = "left";
		$table->align[4] = "left";
		
		$table->size[0] = "30px";
		$table->size[1] = "100px";
		$table->size[3] = "80px";
		$table->size[4] = "60px";
		
		$table->style[0] = "padding-left: 20px";
		$table->style[1] = "display: none";
		
		$table->head[0] = html_print_checkbox_extended('all_selection[]', 0, false, false, '', '', true);
		$table->head[2] = __('Subject');
		

		$i = 0;
		foreach ($um_messages as $message) {
			$data[0] = html_print_checkbox_extended('select_multiple[]', $message['svn_version'], false, false, '', 'class="check_selection"', true);
			$table->cellclass[count($table->data)][0] = 'um_individual_check';
			
			$data[1] = $message['svn_version'];
			$table->cellclass[count($table->data)][1] = 'um_individual_info';
			
			$data[2] = $message['db_field_value'];
			$table->cellclass[count($table->data)][2] = 'um_individual_subject';
			
			
			// Change row class if message is read or not by this user
			if (update_manger_get_read_message ($message['svn_version'], $message['data_rollback'])) {
				$table->rowclass[count($table->data)] = "um_read_message";
					
			} else {
				$table->rowclass[count($table->data)] = "um_not_read_message";
				
			}
			array_push ($table->data, $data);
			
		}
	html_print_table($table);

	echo '<div class="action-buttons" style="float:right; padding: 0 5px;">';
	html_print_submit_button (__('Delete'), 'delete_button', false,
		'class="sub delete"');
	echo '</div>';

	echo '<div class="action-buttons" style="float:right; padding: 0 5px;">';
	html_print_submit_button (__('Mark as not read'), 'not_read_button', false,
		'class="sub wand"');
	echo '</div>';

	echo '<div class="action-buttons" style="float:right; padding: 0 5px;">';
	html_print_submit_button (__('Mark as read'), 'read_button', false,
		'class="sub upd"');
	echo '</div>';
	echo '</form>';
	
	// Get unread messages to update the notification ball.
	// Clean the cache because the unread messages can be different.
	db_clean_cache();
	$total_unread_messages = update_manager_get_unread_messages();
	
} else {
	ui_print_info_message ( array ( 'no_close' => true, 'message' => __('There is not any update manager messages.') ) );
}
?>

<script type="text/javascript">
	
	var total_unread_messages = <?php echo json_encode($total_unread_messages); ?>;
	
	$("#checkbox-all_selection").click( function() {
		if ($("#checkbox-all_selection").is(':checked')) {
			$(".check_selection").prop('checked', true);
			$(".check_selection").parent().parent().css('background', "#FFFFEE");
		} else {
			$(".check_selection").prop('checked', false);
			$(".check_selection").parent().parent().css('background', "inherit");
		}
	});
	
	$("#um_messages_table").click( function (event) {
		
		//Get all position information required
		var target = (event.target.id);
		
		//If header is clicked, return
		if (target == '') return;
		
		var raw_position = (event.target.id).replace(/.*table-/ig,"");
		var row = raw_position.replace(/-.*/ig, "");
		var column = raw_position.replace(/.*-/ig, "");
		
		// Delete and mark as not read column will do not open the message
		if (column == 0) return;
			
		// Class where object will be displayed
		var current_class = ".um_message_" + row;
		var message_id = $("#"+target).parent().find(":nth-child(2)").html();
		var className = $("#"+target).parent().attr('class');
		
		if (className == 'um_not_read_message'){
				
			jQuery.post ("ajax.php",
				{"page": "godmode/update_manager/update_manager.messages",
				 "not_read_single": 1,
				 "message_id": message_id},
				function (data) {}
			);
			
			$("#"+target).parent().children().each(function(){
				var full_class = $(this).attr('class');
				full_class = full_class.replace (/um_not_read_message/g, "um_read_message");
				$(this).attr('class', full_class);
			});
			
			var unread = $("#icon_god-um_messages").find(".notification_ball").html();
			unread--;
			if (unread == 0) {
					$("#icon_god-um_messages").find(".notification_ball").hide();
				}
				else {
					$("#icon_god-um_messages").find(".notification_ball").html(unread);
				}
		}
		
		// Display message
		$("#container").append('<div class="id_wizard"></div>');
		jQuery.get ("ajax.php",
			{"page": "general/last_message",
			 "message_id": message_id},
			function (data) {
				$(".id_wizard").hide ()
					.empty ()
					.append (data);
			},
			"html"
		);
		
	});
	
	$(".check_selection").click(function (event) {
		
		if ($("#" + event.target.id).is(':checked')) {
			$("#" + event.target.id).parent().parent().css('background', "#FFFFEE");
		} else {
			$("#" + event.target.id).parent().parent().css('background', 'inherit');
		}
	});
	
	$(".um_individual_info, .um_individual_subject").hover(
		function () {
			$(this).parent().css('background', '#F2F2F2');
		},
		function () {
			if ($(this).parent().find(":first-child").is(':checked')) {
				$(this).parent().css('background', "#FFFFEE");
			} else {
				$(this).parent().css('background', 'inherit');
			}
		}
	);
	
	$(document).ready (function () {
		
		// Rewrite the notification ball
		if (total_unread_messages == 0) {
			$("#icon_god-um_messages").find(".notification_ball").hide();
		}
		else {
			$("#icon_god-um_messages").find(".notification_ball").html(total_unread_messages);
		}
	});
	
</script>

<style type="text/css">

	.um_not_read_message{
		font-weight: 900;
	}
	.um_read_message{
		font-weight: 500;
		color: #909090;
	}
	
	.um_individual_info, .um_individual_subject {
		cursor: pointer;
	}
	
	.databox td {
		padding-top: 15px;
		padding-bottom: 15px;
	}
	
	td input[type=checkbox] {
		ms-transform: scale(1);
		moz-transform: scale(1);
		o-transform: scale(1);
		webkit-transform: scale(1);
		transform: scale(1);
	}
	
	.c0 {
		padding-left: 17px !important;
	}
	
</style>
