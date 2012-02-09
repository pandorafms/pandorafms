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

check_login ();

if (! check_acl($config['id_user'], 0, "PM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Group Management");
	require ("general/noaccess.php");
	return;
}

$update = get_parameter('update_config', 0);
$default = get_parameter('default', 0);

$fields_selected = array();
$event_fields = '';
$fields_selected = explode (',', $config['event_fields']);

if ($default) {
	$event_fields = io_safe_input('evento,id_agente,estado,timestamp');
	$fields_selected = explode (',', $event_fields);
}

if ($update) {
	$fields_selected = (array)get_parameter('fields_selected');
	
	if ($fields_selected[0] == '') {
		$event_fields = io_safe_input('evento,id_agente,estado,timestamp');
		$fields_selected = explode (',', $event_fields);
	} else {
		$event_fields = implode (',', $fields_selected);
	}

	$values = array(
		'token' => 'event_fields',
		'value' => $event_fields
	);
	//update 'event_fields' in tconfig table to keep the value at update.
	$result = db_process_sql_update('tconfig', $values, array ('token' => 'event_fields'));
}

$result_selected = array();

//show list of fields selected.
if ($fields_selected[0]!='') {
	foreach ($fields_selected as $field_selected) {
		switch ($field_selected) {
			case 'id_evento':
				$result = __('Event id');
				break;
			case 'evento':
				$result = __('Event name');
				break;
			case 'id_agente':
				$result = __('Agent name');
				break;
			case 'id_usuario':
				$result = __('User');
				break;
			case 'id_grupo':
				$result = __('Group');
				break;
			case 'estado':
				$result = __('Status');
				break;
			case 'timestamp':
				$result = __('Timestamp');
				break;
			case 'event_type':
				$result = __('Event type');
				break;
			case 'id_agentmodule':
				$result = __('Agent module');
				break;
			case 'id_alert_am':
				$result = __('Alert');
				break;
			case 'criticity':
				$result = __('Criticity');
				break;
			case 'user_comment':
				$result = __('Comment');
				break;
			case 'tags':
				$result = __('Tags');
				break;
			case 'source':
				$result = __('Source');
				break;
			case 'id_extra':
				$result = __('Extra id');
				break;
		}
		$result_selected[$field_selected] = $result;
	}
}

$event = array();

echo '<h3>'.__('Show event fields').'</h3>';

$table->width = '90%';

$table->size = array();
$table->size[0] = '20%';
$table->size[2] = '20%';

$table->data = array();

$fields_available = array();

$fields_available['id_evento'] = __('Event id');
$fields_available['evento'] = __('Event name');
$fields_available['id_agente'] = __('Agent name');
$fields_available['id_usuario'] = __('User');
$fields_available['id_grupo'] = __('Group');
$fields_available['estado'] = __('Status');
$fields_available['timestamp'] = __('Timestamp');
$fields_available['event_type'] = __('Event type');
$fields_available['id_agentmodule'] = __('Agent module');
$fields_available['id_alert_am'] = __('Alert');
$fields_available['criticity'] = __('Criticity');
$fields_available['user_comment'] = __('Comment');
$fields_available['tags'] = __('Tags');
$fields_available['source'] = __('Source');
$fields_available['id_extra'] = __('Extra id');

//remove fields already selected
foreach ($fields_available as $key=>$available) {
	foreach ($result_selected as $selected) {
		if ($selected == $available) {
			unset($fields_available[$key]);
		}
	}
}

$table->data[1][0] =  '<b>' . __('Fields available').'</b>';
$table->data[1][1] = html_print_select ($fields_available, 'fields_available[]', true, '', '', '', true, true, false, '', false, 'width: 200px');
$table->data[1][2] =  html_print_image('images/darrowright.png', true, array('id' => 'right', 'title' => __('Add fields to select'))); //html_print_input_image ('add', 'images/darrowright.png', 1, '', true, array ('title' => __('Add tags to module')));
$table->data[1][2] .= '<br><br><br><br>' . html_print_image('images/darrowleft.png', true, array('id' => 'left', 'title' => __('Delete fields to select'))); //html_print_input_image ('add', 'images/darrowleft.png', 1, '', true, array ('title' => __('Delete tags to module')));
	
$table->data[1][3] = '<b>' . __('Fields selected') . '</b>';
$table->data[1][4] =  html_print_select($result_selected, 'fields_selected[]', true, '', '', '', true, true, false, '', false, 'width: 200px');	

echo '<form id="custom_events" method="post" action="index.php?sec=geventos&sec2=godmode/events/events&section=fields">';
echo '<div class="action-buttons" style="width: '.$table->width.'">';
		html_print_input_hidden ('default', 1);
		html_print_submit_button (__('Default'), 'default_fields', false, 'class="sub upd"');
echo '</div>';
html_print_table($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
		html_print_input_hidden ('update_config', 1);
		html_print_submit_button (__('Update'), 'upd_button', false, 'class="sub upd"');
echo '</form>';
echo '</div>';
?>

<script type="text/javascript">
/* <![CDATA[ */
$(document).ready (function () {

	$("#right").click (function () {
		jQuery.each($("select[name='fields_available[]'] option:selected"), function (key, value) {
			field_name = $(value).html();
			if (field_name != <?php echo "'".__('None')."'"; ?>){
				id_field = $(value).attr('value');
				$("select[name='fields_selected[]']").append($("<option></option>").html(field_name).attr("value", id_field));
				$("#fields_available").find("option[value='" + id_field + "']").remove();
			}
		});			
	});

	$("#left").click (function () {
		jQuery.each($("select[name='fields_selected[]'] option:selected"), function (key, value) {
				field_name = $(value).html();
				if (field_name != <?php echo "'".__('None')."'"; ?>){
					id_field = $(value).attr('value');
					$("select[name='fields_available[]']").append($("<option></option>").val(field_name).html('<i>' + id_field + '</i>'));
					$("#fields_selected").find("option[value='" + id_field + "']").remove();
				}
		});			
	});
	
	$("#submit-upd_button").click(function () {
		$('#fields_selected option').map(function(){
			$(this).attr('selected','selected');
		});
	});
});

</script>

