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

$fields_selected = array();
$event_fields = '';
$fields_selected = explode (',', $config['event_fields']);

if ($update) {
	$fields_selected = (array)get_parameter('fields_selected');
	
	if ($fields_selected[0] == '') {
		$event_fields = 'evento,id_agente,estado,timestamp';
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
if ($fields_selected[0]!='') {
	foreach ($fields_selected as $field_selected) {
		$result_selected[$field_selected] = $field_selected;
	}
}

$event = array();

$table->width = '90%';

$table->size = array();
$table->size[0] = '20%';
$table->size[2] = '20%';

$table->data = array();
$table->data[0][0] = '<h3>'.__('Show event fields').'</h3>';

$fields_available = array();
$fields_available['id_evento'] = 'id_evento';
$fields_available['evento'] = 'evento';
$fields_available['id_agente'] = 'id_agente';
$fields_available['id_usuario'] = 'id_usuario';
$fields_available['id_grupo'] = 'id_grupo';
$fields_available['estado'] = 'estado';
$fields_available['timestamp'] = 'timestamp';
$fields_available['event_type'] = 'event_type';
$fields_available['id_agentmodule'] = 'id_agentmodule';
$fields_available['id_alert_am'] = 'id_alert_am';
$fields_available['criticity'] = 'criticity';
$fields_available['user_comment'] = 'user_comment';
$fields_available['tags'] = 'tags';
$fields_available['source'] = 'source';
$fields_available['id_extra'] = 'id_extra';
$fields_available['criticity_alert'] = 'criticity_alert';

//remove fields already selected
foreach ($fields_available as $available) {
	foreach ($result_selected as $selected) {
		if ($selected == $available) {
			unset($fields_available[$selected]);
		}
	}
}

$table->data[1][0] =  '<b>' . __('Fields available').'</b>';
$table->data[1][1] = html_print_select ($fields_available, 'fields_available[]', true, '', __('None'), '', true, true, false);
$table->data[1][2] =  html_print_image('images/darrowright.png', true, array('id' => 'right', 'title' => __('Add fields to select'))); //html_print_input_image ('add', 'images/darrowright.png', 1, '', true, array ('title' => __('Add tags to module')));
$table->data[1][2] .= '<br><br><br><br>' . html_print_image('images/darrowleft.png', true, array('id' => 'left', 'title' => __('Delete fields to select'))); //html_print_input_image ('add', 'images/darrowleft.png', 1, '', true, array ('title' => __('Delete tags to module')));
	
$table->data[1][3] = '<b>' . __('Fields selected') . '</b>';
$table->data[1][4] =  html_print_select($result_selected, 'fields_selected[]', true, '', __('None'), '', true, true, false);	

echo '<form id="custom_events" method="post" action="index.php?sec=geventos&sec2=godmode/events/events&section=fields">';

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
				$("select[name='fields_selected[]']").append($("<option selected='selected'></option>").html(field_name).attr("value", field_name));
				$("#fields_available").find("option[value='" + id_field + "']").remove();
			}
		});			
	});

	$("#left").click (function () {
		jQuery.each($("select[name='fields_selected[]'] option:selected"), function (key, value) {
				field_name = $(value).html();
				if (field_name != <?php echo "'".__('None')."'"; ?>){
					id_field = $(value).attr('value');
					$("select[name='fields_available[]']").append($("<option></option>").val(field_name).html('<i>' + field_name + '</i>'));
					$("#fields_selected").find("option[value='" + id_field + "']").remove();
				}
		});			
	});
});

</script>

