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


$table->width = '98%';
$table->id = "eventtable";
$table->cellpadding = 4;
$table->cellspacing = 4;
$table->class = "databox";
$table->head = array ();
$table->data = array ();

//fields that the user has selected to show
$show_fields = explode (',', $config['event_fields']);

//headers
$i = 0;
$table->head[$i] = __('ID');
$table->align[$i] = 'center';
$i++;
if (in_array('server_name', $show_fields)) {
	$table->head[$i] = __('Server');
	$table->align[$i] = 'center';
	$i++;
}
if (in_array('estado', $show_fields)) {
	$table->head[$i] = __('Status');
	$table->align[$i] = 'center';
	$i++;
}
if (in_array('id_evento', $show_fields)) {
	$table->head[$i] = __('Event ID');
	$table->align[$i] = 'center';
	$i++;
}
if (in_array('evento', $show_fields)) {
	$table->head[$i] = __('Event Name');
	$table->align[$i] = 'left';
	$i++;
}
if (in_array('id_agente', $show_fields)) {
	$table->head[$i] = __('Agent name');
	$table->align[$i] = 'center';
	$i++;
}
if (in_array('timestamp', $show_fields)) {
	$table->head[$i] = __('Timestamp');
	$table->align[$i] = 'center';
	$i++;
}
if (in_array('id_usuario', $show_fields)) {
	$table->head[$i] = __('User');
	$table->align[$i] = 'center';
	$i++;
}
if (in_array('owner_user', $show_fields)) {
	$table->head[$i] = __('Owner');
	$table->align[$i] = 'center';
	$i++;
}
if (in_array('id_grupo', $show_fields)) {
	$table->head[$i] = __('Group');
	$table->align[$i] = 'center';
	$i++;
}
if (in_array('event_type', $show_fields)) {
	$table->head[$i] = __('Event type');
	$table->align[$i] = 'center';
	$i++;
}
if (in_array('id_agentmodule', $show_fields)) {
	$table->head[$i] = __('Agent Module');
	$table->align[$i] = 'center';
	$i++;
}
if (in_array('id_alert_am', $show_fields)) {
	$table->head[$i] = __('Alert');
	$table->align[$i] = 'center';
	$i++;
}

if (in_array('criticity', $show_fields)) {
	$table->head[$i] = __('Severity');
	$table->align[$i] = 'center';
	$i++;
}
if (in_array('user_comment', $show_fields)) {
	$table->head[$i] = __('Comment');
	$table->align[$i] = 'center';
	$i++;
}
if (in_array('tags', $show_fields)) {
	$table->head[$i] = __('Tags');
	$table->align[$i] = 'center';
	$i++;
}
if (in_array('source', $show_fields)) {
	$table->head[$i] = __('Source');
	$table->align[$i] = 'center';
	$i++;
}
if (in_array('id_extra', $show_fields)) {
	$table->head[$i] = __('Extra ID');
	$table->align[$i] = 'center';
	$i++;
}
if (in_array('ack_utimestamp', $show_fields)) {
	$table->head[$i] = __('ACK Timestamp');
	$table->align[$i] = 'center';
	$i++;
}
if (in_array('instructions', $show_fields)) {
	$table->head[$i] = __('Instructions');
	$table->align[$i] = 'left';
	$i++;
}
if ($i != 0 && $allow_action) {
	$table->head[$i] = __('Action');
	$table->align[$i] = 'center';
	$table->size[$i] = '80px';
	$i++;
	if (check_acl ($config["id_user"], 0, "EW") == 1) {
		$table->head[$i] = html_print_checkbox ("all_validate_box", "1", false, true);
		$table->align[$i] = 'center';
	}
}

if($meta) {
	// Get info of the all servers to use it on hash auth
	$servers_url_hash = metaconsole_get_servers_url_hash();
	$servers = metaconsole_get_servers();
}

$idx = 0;
//Arrange data. We already did ACL's in the query
foreach ($result as $event) {
	$data = array ();
	
	if ($meta) {
		$event['server_url_hash'] = $servers_url_hash[$event['server_id']];
		$event['server_url'] = $servers[$event['server_id']]['server_url'];
		$event['server_name'] = $servers[$event['server_id']]['server_name'];
	}
	
	// Clean url from events and store in array
	$event['clean_tags'] = events_clean_tags($event['tags']);
	
	//First pass along the class of this row
	$myclass = get_priority_class ($event["criticity"]);
	$table->rowclass[] = $myclass;
	
	//print status
	$estado = $event["estado"];
	
	// Colored box
	switch($estado) {
		case 0:
			$img_st = "images/star.png";
			$title_st = __('New event');
			break;
		case 1:
			$img_st = "images/tick.png";
			$title_st = __('Event validated');
			break;
		case 2:
			$img_st = "images/hourglass.png";
			$title_st = __('Event in process');
			break;
	}
	
	$i = 0;
	
	$data[$i] = "#".$event["id_evento"];
	
	// Pass grouped values in hidden fields to use it from modal window
	if($group_rep) {
		$similar_ids = $event['similar_ids'];
		$timestamp_first = $event['timestamp_rep_min'];
		$timestamp_last = $event['timestamp_rep'];
	}
	else {
		$similar_ids = $event["id_evento"];
		$timestamp_first = $event['utimestamp'];
		$timestamp_last = $event['utimestamp'];
	}
	
	// Store group data to show in extended view
	$data[$i] .= html_print_input_hidden('similar_ids_' . $event["id_evento"], $similar_ids, true);
	$data[$i] .= html_print_input_hidden('timestamp_first_' . $event["id_evento"], $timestamp_first, true);
	$data[$i] .= html_print_input_hidden('timestamp_last_' . $event["id_evento"], $timestamp_last, true);
	
	// Store server id if is metaconsole. 0 otherwise
	if ($meta) {
		$server_id = $event['server_id'];
		
		// If meta activated, propagate the id of the event on node (source id)
		$data[$i] .= html_print_input_hidden('source_id_' . $event["id_evento"], $event['id_source_event'], true);
	}
	else {
		$server_id = 0;
	}
	
	$data[$i] .= html_print_input_hidden('server_id_' . $event["id_evento"], $server_id, true);
	
	if (empty($event['event_rep'])) {
		$event['event_rep'] = 0;
	}
	$data[$i] .= html_print_input_hidden('event_rep_'.$event["id_evento"], $event['event_rep'], true);
	// Store concat comments to show in extended view
	$data[$i] .= html_print_input_hidden('user_comment_'.$event["id_evento"], base64_encode($event['user_comment']), true);		
	
	$i++;
	
	if (in_array('server_name',$show_fields)) {
		if($meta) {
			$data[$i] = "<a href='" . $event["server_url"] . "/index.php?sec=estado&sec2=operation/agentes/group_view" . $event['server_url_hash'] . "'>" . $event["server_name"] . "</a>";
		}
		else {
			$data[$i] = db_get_value('name','tserver');
		}
		$i++;
	}
	if (in_array('estado',$show_fields)) {
		$data[$i] = html_print_image ($img_st, true, 
			array ("class" => "image_status",
				"width" => 16,
				"height" => 16,
				"title" => $title_st,
				"id" => 'status_img_'.$event["id_evento"]));
		$i++;
	}
	if (in_array('id_evento',$show_fields)) {
		$data[$i] = $event["id_evento"];
		$i++;
	}
	
	switch ($event["criticity"]) {
		default:
		case 0:
			$img_sev = "images/status_sets/default/severity_maintenance.png";
			break;
		case 1:
			$img_sev = "images/status_sets/default/severity_informational.png";
			break;
		case 2:
			$img_sev = "images/status_sets/default/severity_normal.png";
			break;
		case 3:
			$img_sev = "images/status_sets/default/severity_warning.png";
			break;
		case 4:
			$img_sev = "images/status_sets/default/severity_critical.png";
			break;
		case 5:
			$img_sev = "images/status_sets/default/severity_minor.png";
			break;
		case 6:
			$img_sev = "images/status_sets/default/severity_major.png";
			break;
	}
	
	if (in_array('evento', $show_fields)) {
		// Event description
		$data[$i] = '<span title="'.$event["evento"].'" class="f9">';
		if($allow_action) {
			$data[$i] .= '<a href="javascript:" onclick="show_event_dialog(' . $event["id_evento"] . ', '.$group_rep.');">';
		}
		$data[$i] .= '<span class="'.$myclass.'" style="font-size: 7.5pt;">' . ui_print_truncate_text (io_safe_output($event["evento"]), 160) . '</span>';
		if($allow_action) {
			$data[$i] .= '</a>';
		}
		$data[$i] .= '</span>';
		$i++;
	}
	
	if (in_array('id_agente', $show_fields)) {
		$data[$i] = '<span class="'.$myclass.'">';
		
		if ($event["id_agente"] > 0) {
			// Agent name
			if($meta) {
				$data[$i] = '<b><a href="'.$event["server_url"].'/index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente=' . $event["id_agente"] . $event["server_url_hash"] . '">';
				$data[$i] .= $event["agent_name"];
				$data[$i] .= "</a></b>";
			}
			else {
				$data[$i] .= ui_print_agent_name ($event["id_agente"], true);
			}
		}
		else {
			$data[$i] .= '';
		}
		$data[$i] .= '</span>';
		$i++;
	}
	
	if (in_array('timestamp', $show_fields)) {
		//Time
		$data[$i] = '<span class="'.$myclass.'">';
		if ($group_rep == 1) {
			$data[$i] .= ui_print_timestamp ($event['timestamp_rep'], true);
		}
		else {
			$data[$i] .= ui_print_timestamp ($event["timestamp"], true);
		}
		$data[$i] .= '</span>';
		$i++;
	}
	
	if (in_array('id_usuario',$show_fields)) {
		$user_name = db_get_value('fullname', 'tusuario', 'id_user', $event['id_usuario']);
		if(empty($user_name)) {
			$user_name = $event['id_usuario'];
		}
		$data[$i] = $user_name;
		$i++;
	}
	
	if (in_array('owner_user',$show_fields)) {
		$owner_name = db_get_value('fullname', 'tusuario', 'id_user', $event['owner_user']);
		if(empty($owner_name)) {
			$owner_name = $event['owner_user'];
		}
		$data[$i] = $owner_name;
		$i++;
	}
	
	if (in_array('id_grupo',$show_fields)) {
		if($meta) {
			$data[$i] = $event['group_name'];
		}
		else {
			$id_group = $event["id_grupo"];
			$group_name = db_get_value('nombre', 'tgrupo', 'id_grupo', $id_group);
			if ($id_group == 0) {
				$group_name = __('All');
			}
			$data[$i] = $group_name;
		}
		$i++;
	}
	
	if (in_array('event_type',$show_fields)) {
		$data[$i] = events_print_type_description($event["event_type"], true);
		$i++;
	}
	
	if (in_array('id_agentmodule',$show_fields)) {
		if($meta) {
			$data[$i] = '<b><a href="'.$event["server_url"].'/index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente=' . $event["id_agente"] . $event["server_url_hash"] . '">';
			$data[$i] .= $event["module_name"];
			$data[$i] .= "</a></b>";
		}
		else {
			$data[$i] = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$event["id_agente"].'&amp;tab=data">'
				. db_get_value('nombre', 'tagente_modulo', 'id_agente_modulo', $event["id_agentmodule"]).'</a>';
		}
		$i++;
	}
	
	if (in_array('id_alert_am',$show_fields)) {
		if($meta) {
			$data[$i] = $event["alert_template_name"];
		}
		else {
			if ($event["id_alert_am"] != 0) {
				$sql = 'SELECT name
					FROM talert_templates
					WHERE id IN (SELECT id_alert_template
					FROM talert_template_modules
					WHERE id = ' . $event["id_alert_am"] . ');';
				
				$templateName = db_get_sql($sql);
				$data[$i] = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$event["id_agente"].'&amp;tab=alert">'.$templateName.'</a>';
			}
			else {
				$data[$i] = '';
			}
		}
		$i++;
	}
	
	if (in_array('criticity',$show_fields)) {
		$data[$i] = get_priority_name ($event["criticity"]);
		$i++;
	}
	
	if (in_array('user_comment',$show_fields)) {
		$data[$i] = '<span id="comment_header_' . $event['id_evento'] . '">' .ui_print_truncate_text(strip_tags($event["user_comment"])) . '</span>';
		$i++;
	}
	
	if (in_array('tags',$show_fields)) {
		$data[$i] = tags_get_tags_formatted($event['tags']);
		$i++;
	}
	
	if (in_array('source',$show_fields)) {
		$data[$i] = $event["source"];
		$i++;
	}
	
	if (in_array('id_extra',$show_fields)) {
		$data[$i] = $event["id_extra"];
		$i++;
	}
	
	if (in_array('ack_utimestamp',$show_fields)) {
		if($event["ack_utimestamp"] == 0){
			$data[$i] = '';
		}
		else {
			$data[$i] = date ($config["date_format"], $event['ack_utimestamp']);
		}
		$i++;
	}
	
	if (in_array('instructions',$show_fields)) {
		switch($event['event_type']) {
			case 'going_unknown':
				$data[$i] = ui_print_truncate_text(str_replace("\n","<br>", io_safe_output($event["unknown_instructions"])));
				break;
			case 'going_up_critical':
			case 'going_down_critical':
				$data[$i] = ui_print_truncate_text(str_replace("\n","<br>", io_safe_output($event["critical_instructions"])));
				break;
			case 'going_down_warning':
				$data[$i] = ui_print_truncate_text(str_replace("\n","<br>", io_safe_output($event["warning_instructions"])));
				break;
			default:
				$data[$i] = '';
		}
		$i++;
	}
	
	if ($i != 0 && $allow_action) {
		//Actions
		$data[$i] = '';
		// Validate event
		if (($event["estado"] != 1) && (tags_check_acl ($config["id_user"], $event["id_grupo"], "EW", $event['clean_tags']) == 1)) {
			$data[$i] .= '<a href="javascript:validate_event_advanced('.$event["id_evento"].', 1)" id="validate-'.$event["id_evento"].'">';
			$data[$i] .= html_print_image ("images/ok.png", true,
				array ("title" => __('Validate event')));
			$data[$i] .= '</a>&nbsp;';
		}
		
		// Delete event
		if (tags_check_acl ($config["id_user"], $event["id_grupo"], "EM", $event['clean_tags']) == 1) {
			if($event['estado'] != 2) {
				$data[$i] .= '<a class="delete_event" href="javascript:" id="delete-'.$event['id_evento'].'">';
				$data[$i] .= html_print_image ("images/cross.png", true,
					array ("title" => __('Delete event'), "id" => 'delete_cross_' . $event['id_evento']));
				$data[$i] .= '</a>&nbsp;';
			}
			else {
				$data[$i] .= html_print_image ("images/cross.disabled.png", true,
					array ("title" => __('Is not allowed delete events in process'))).'&nbsp;';
			}
		}
		
		$data[$i] .= '<a href="javascript:" onclick="show_event_dialog(' . $event["id_evento"] . ', '.$group_rep.');">';
		$data[$i] .= html_print_input_hidden('event_title_'.$event["id_evento"], "#".$event["id_evento"]." - ".$event["evento"], true);
		$data[$i] .= html_print_image ("images/eye.png", true,
			array ("title" => __('Show more')));	
		$data[$i] .= '</a>&nbsp;';
		$i++;
		
		if (tags_check_acl ($config["id_user"], $event["id_grupo"], "EM", $event['clean_tags']) == 1) {
			//Checkbox
			// Class 'candeleted' must be the fist class to be parsed from javascript. Dont change
			$data[$i] = html_print_checkbox_extended ("validate_ids[]", $event['id_evento'], false, false, false, 'class="candeleted chk_val"', true);
		}
		else if (tags_check_acl ($config["id_user"], $event["id_grupo"], "EW", $event['clean_tags']) == 1) {
			//Checkbox
			$data[$i] = html_print_checkbox_extended ("validate_ids[]", $event['id_evento'], false, false, false, 'class="chk_val"', true);
		}
		else if (isset($table->header[$i]) || true) {
			$data[$i] = '';
		}
	}
	
	array_push ($table->data, $data);
	
	$idx++;
}

echo '<div id="events_list">';
if (!empty ($table->data)) {
	echo '<div style="clear:both"></div>';
	if ($allow_pagination) {
		ui_pagination ($total_events, $url, $offset, $pagination);
	}
	
	if ($allow_action) {
		echo '<form method="post" id="form_events" action="'.$url.'">';	
		echo "<input type='hidden' name='delete' id='hidden_delete_events' value='0' />";
	}
	
	html_print_table ($table);
	
	if ($allow_action) {
		echo '<div style="width:'.$table->width.';" class="action-buttons">';
		if (tags_check_acl ($config["id_user"], 0, "EW", $event['clean_tags']) == 1) {
			html_print_button(__('Validate selected'), 'validate_button', false, 'validate_selected();', 'class="sub ok"');
		}
		if (tags_check_acl ($config["id_user"], 0,"EM", $event['clean_tags']) == 1) {
			html_print_button(__('Delete selected'), 'delete_button', false, 'delete_selected();', 'class="sub delete"');
			?>
			<script type="text/javascript">
			function delete_selected() {
				if(confirm('<?php echo __('Are you sure?'); ?>')) {
					$("#hidden_delete_events").val(1);
					$("#form_events").submit();
				}
			}
			function validate_selected() {
				$(".chk_val").each(function() { 
					if($(this).is(":checked")) {
						validate_event_advanced($(this).val(),1);
					}
				});  
			}
			</script>
			<?php
		}
		echo '</div>';
		echo '</form>';
	}
}
else {
	echo '<div class="nf">'.__('No events').'</div>';
}
echo '</div>';

?>
