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
	
	$charge_message = get_parameter ('charge_message', 0);
	$not_read_single = get_parameter ('not_read_single', 0);
	
	if ($charge_message) {
		$message_id = get_parameter ('message_id', 0);
		if ($message_id == 0) return;
		$message_html = db_get_value ('data', 'tupdate', 'svn_version', $message_id);
		echo $message_html;
	}
	
	if ($not_read_single) {
		$message_id = get_parameter ('message_id', 0);
		update_manger_set_read_message ($message_id, 1);
	}
	
	return;
}

$not_read_action = get_parameter('not_read_button', false);
$delete_action = get_parameter ('delete_button', false);

if ($not_read_action !== false) {
	
	$selected = get_parameter ('select_multiple', false);
	foreach ($selected as $k => $message_id) {
		
		update_manger_set_read_message ($message_id, 0);
	}
}

if ($delete_action !== false) {
	
	$selected = get_parameter ('select_multiple', false);
	foreach ($selected as $k => $message_id) {
		
		db_process_sql_delete ('tupdate', array('svn_version' => $message_id));
	}
	
}

// Get all messages
$sql = 'SELECT data, svn_version, filename, data_rollback, description FROM tupdate';
$um_messages = array ();
$um_messages = db_get_all_rows_sql ($sql);

echo '<form method="post" action="index.php?sec=gsetup&sec2=godmode/update_manager/update_manager&tab=setup">';

html_print_input_hidden ('tab', 'messages');
echo '<div class="action-buttons" style="float:right; padding: 10px 5px">';
html_print_submit_button (__('Delete'), 'delete_button', false,
	'class="sub upd"');
echo '</div>';

echo '<div class="action-buttons" style="float:right; padding: 10px 5px">';
html_print_submit_button (__('Mark as not read'), 'not_read_button', false,
	'class="sub upd"');
echo '</div>';

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
	
	$table->size[0] = "20px";
	$table->size[1] = "100px";
	$table->size[3] = "80px";
	$table->size[4] = "60px";
	
	$table->head[0] = __('Message Id');
	$table->head[1] = __('Expiration date');
	$table->head[2] = __('Subject');
	$table->head[3] = html_print_checkbox_extended('all_selection[]', 0, false, false, '', '', true);

	$i = 0;
	foreach ($um_messages as $message) {
		$data[0] = $message['svn_version'];
		
		$data[1] = $message['filename'];
		
		$data[2] = $message['description'];
		
		//~ $delete_link = 'index.php?sec=gsetup&sec2=godmode/update_manager/update_manager&amp;tab=messages&amp;delete_single=1&amp;message_id=' . $message['svn_version'];
		$data[3] = html_print_checkbox_extended('select_multiple[]', $message['svn_version'], false, false, '', 'class="check_selection"', true);
		
		
		// Change row class if message is read or not by this user
		if (update_manger_get_read_message ($message['svn_version'], $message['data_rollback'])) {
			$table->rowclass[count($table->data)] = "um_read_message";
				
		} else {
			$table->rowclass[count($table->data)] = "um_not_read_message";
			
		}
		array_push ($table->data, $data);
		
		// Insert an empty row too. Here the message will be displayed
		$empty[0] = "";
		$table->colspan[count($table->data)][0] = 4;
		$table->cellclass[count($table->data)][0] = "um_message_" . $i;
		$table->cellstyle[count($table->data)][0] = "display: none;";
		array_push ($table->data, $empty);
		
		$i++;
	}
html_print_table($table);

echo '<div class="action-buttons" style="float:right"; padding: 0 5px>';
html_print_submit_button (__('Delete'), 'delete_button', false,
	'class="sub upd"');
echo '</div>';

echo '<div class="action-buttons" style="float:right; padding: 0 5px">';
html_print_submit_button (__('Mark as not read'), 'not_read_button', false,
	'class="sub upd"');
echo '</div>';
echo '</form>';
?>

<script type="text/javascript">
	
	$("#checkbox-all_selection").click( function() {
		if ($("#checkbox-all_selection").is(':checked')) {
			$(".check_selection").prop('checked', true);
		} else {
			$(".check_selection").prop('checked', false);
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
		if (column > 2) return;
		
		if (row%2 == 0) {
			// Clicking a tittle
			
			// Class where object will be displayed
			var current_class = ".um_message_" + row/2;
			var message_id = $("#"+target).parent().find(":first-child").html();
			var div_id = 'um_individual_message' + row/2;
			
			// Get the message via Ajax (only if it is not checked now
			$(current_class).append('<div class="' + div_id + '"></div>');
			if ($("." + div_id).length == 1) {
				jQuery.get ("ajax.php",
					{"page": "godmode/update_manager/update_manager.messages",
					 "charge_message": 1,
					 "message_id": message_id},
					function (data) {
						$("." + div_id).hide ()
							.empty ()
							.append (data)
							.show ();
					},
					"html"
				);
				
				// Update message if it is not readed
				var className = $("#"+target).parent().attr('class');
				if (className == 'um_not_read_message'){
					
					jQuery.post ("ajax.php",
						{"page": "godmode/update_manager/update_manager.messages",
						 "not_read_single": 1,
						 "message_id": message_id},
						function (data) {}
					);
					$("#"+target).parent().find(".um_not_read_message").attr('class', 'um_read_message');
				}
			}			
			
			// Display message
			$(current_class).toggle ();
			
		} else {
			// Clicking a message
			$(".um_message_" + Math.floor(row/2)).hide();
		}
		
	});
</script>
