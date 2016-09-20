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

require_once ('include/functions_events.php');
require_once ('include/functions_agents.php');
require_once ('include/functions_ui.php');
require_once ('include/functions_db.php');
require_once ('include/functions_io.php');
require_once ('include/functions.php');
enterprise_include_once ('meta/include/functions_events_meta.php');
enterprise_include_once ('include/functions_metaconsole.php');

$get_events_details = (bool) get_parameter ('get_events_details');
$get_list_events_agents = (bool) get_parameter ('get_list_events_agents');
$get_extended_event = (bool) get_parameter ('get_extended_event');
$change_status = (bool) get_parameter ('change_status');
$change_owner = (bool) get_parameter ('change_owner');
$add_comment = (bool) get_parameter ('add_comment');
$dialogue_event_response = (bool) get_parameter ('dialogue_event_response');
$perform_event_response = (bool) get_parameter ('perform_event_response');
$get_response = (bool) get_parameter ('get_response');
$get_response_target = (bool) get_parameter ('get_response_target');
$get_response_params = (bool) get_parameter ('get_response_params');
$get_response_description = (bool) get_parameter ('get_response_description');
$get_event_name = (bool) get_parameter ('get_event_name');
$meta = get_parameter ('meta', 0);
$history = get_parameter ('history', 0);
$table_events = get_parameter('table_events', 0);

if ($get_event_name) {
	$event_id = get_parameter ('event_id');
	
	if ($meta) {
		$name = events_meta_get_event_name($event_id, $history);
	}
	else {
		$name = db_get_value('evento','tevento','id_evento',$event_id);
	}
	
	if ($name === false) {
		return;
	}
	
	ui_print_truncate_text(io_safe_output($name), 75, false, false, false, '...');
	
	return;
}

if ($get_response_description) {
	$response_id = get_parameter ('response_id');
	
	$description = db_get_value('description','tevent_response','id',$response_id);
	
	if ($description === false) {
		return;
	}
	
	$description = io_safe_output($description);
	$description = str_replace("\r\n", '<br>', $description);
	
	echo $description;
	
	return;
}

if ($get_response_params) {
	$response_id = get_parameter ('response_id');
	
	$params = db_get_value('params','tevent_response','id',$response_id);
	
	if ($params === false) {
		return;
	}
	
	echo json_encode(explode(',',$params));
	
	return;
}

if ($get_response_target) {
	$response_id = get_parameter ('response_id');
	$event_id = get_parameter ('event_id');
	$server_id = get_parameter ('server_id', 0);
	
	$event_response = db_get_row('tevent_response','id',$response_id);
	
	if (empty($event_response)) {
		return;
	}
	
	echo events_get_response_target($event_id, $response_id, $server_id);
	
	return;
}

if ($get_response) {
	$response_id = get_parameter ('response_id');
	
	$event_response = db_get_row('tevent_response','id',$response_id);
	
	if (empty($event_response)) {
		return;
	}
	
	echo json_encode($event_response);
	return;
}

if ($perform_event_response) {
	global $config;
	
	$command = get_parameter('target','');
	
	switch (PHP_OS) {
		case "FreeBSD":
			$timeout_bin = '/usr/local/bin/gtimeout';
			break;
		case "NetBSD":
			$timeout_bin = '/usr/pkg/bin/gtimeout';
			break;
		default:
			$timeout_bin = '/usr/bin/timeout';
			break;
	}
	echo system($timeout_bin . ' 9 '.io_safe_output($command).' 2>&1');
	
	return;
}

if ($dialogue_event_response) {
	global $config;
	
	$event_id = get_parameter ('event_id');
	$response_id = get_parameter ('response_id');
	$command = get_parameter ('target');
	
	$event_response = db_get_row('tevent_response','id',$response_id);
	
	$event = db_get_row('tevento','id_evento',$event_id);
	
	$prompt = "<br>> ";
	
	switch($event_response['type']) {
		case 'command':
			echo "<div style='text-align:left'>";
			echo $prompt.sprintf(__('Executing command: %s',$command));
			echo "</div><br>";
			
			echo "<div id='response_loading_command' style='display:none'>".html_print_image('images/spinner.gif', true)."</div>";
			echo "<br><div id='response_out' style='text-align:left'></div>";
			
			echo "<br><div id='re_exec_command' style='display:none;'>";
			html_print_button(__('Execute again'),'btn_str',false,'perform_response(\''.$command.'\');', "class='sub next'");
			echo "</div>";
			break;
		case 'url':
			$command = str_replace("localhost",$_SERVER['SERVER_NAME'],$command);
			echo "<iframe src='$command' id='divframe' style='width:100%;height:90%;'></iframe>";
			break;
	}
}

if ($add_comment) { 
	$comment = get_parameter ('comment');
	$event_id = get_parameter ('event_id');
	
	$return = events_comment ($event_id, $comment, 'Added comment', $meta, $history);
	
	if ($return)
		echo 'comment_ok';
	else
		echo 'comment_error';
	
	return;
}

if ($change_status) { 
	$event_ids = get_parameter ('event_ids');
	$new_status = get_parameter ('new_status');
	
	$return = events_change_status (explode(',',$event_ids), $new_status, $meta, $history); 
	
	if ($return)
		echo 'status_ok';
	else
		echo 'status_error';
	
	return;
}

if ($change_owner) { 
	$new_owner = get_parameter ('new_owner');
	$event_id = get_parameter ('event_id');
	$similars = true;
	
	if ($new_owner == -1) {
		$new_owner = '';
	}
	
	$return = events_change_owner($event_id, $new_owner, true, $meta, $history);
	
	if ($return)
		echo 'owner_ok';
	else
		echo 'owner_error';
	
	return;
}

if ($get_extended_event) {
	global $config;
	
	$event_id = get_parameter('event_id',false);
	$childrens_ids = get_parameter('childrens_ids');
	$childrens_ids = json_decode($childrens_ids);
	
	if ($meta) {
		$event = events_meta_get_event($event_id, false, $history, "ER");
	}
	else {
		$event = events_get_event($event_id);
	}
	
	$readonly = false;
	if (!$meta &&
		isset($config['event_replication']) &&  
		$config['event_replication'] == 1 && 
		$config['show_events_in_local'] == 1) {
			$readonly = true;
	}
	
	// Clean url from events and store in array
	$event['clean_tags'] = events_clean_tags($event['tags']);
	
	// If the event is not found, we abort
	if (empty($event)) {
		ui_print_error_message('Event not found');
		return false;
	}
	
	$dialog_page = get_parameter('dialog_page','general');
	$similar_ids = get_parameter('similar_ids', $event_id);
	$group_rep = get_parameter('group_rep',false);
	$event_rep = get_parameter('event_rep',1);
	$timestamp_first = get_parameter('timestamp_first', $event['utimestamp']);
	$timestamp_last = get_parameter('timestamp_last', $event['utimestamp']);
	$server_id = get_parameter('server_id', 0);
	
	$event['similar_ids'] = $similar_ids;
	$event['timestamp_first'] = $timestamp_first;
	$event['timestamp_last'] = $timestamp_last;
	$event['event_rep'] = $event_rep;
	
	// Check ACLs
	if (is_user_admin ($config["id_user"])) {
		//Do nothing if you're admin, you get full access
	}
	else if ($config["id_user"] == $event['owner_user']) { 
		//Do nothing if you're the owner user, you get access
	}
	else if ($event['id_grupo'] == 0) {
		//If the event has access to all groups, you get access
	}
	else {
		// Get your groups
		$groups = users_get_groups($config['id_user'], 'ER');
		
		if (in_array ($event['id_grupo'], array_keys ($groups))) {
			//If the event group is among the groups of the user, you get access
		}
		else {
			// If all the access types fail, abort
			echo 'Access denied';
			return false;
		}
	}
	
	// Print group_rep in a hidden field to recover it from javascript
	html_print_input_hidden('group_rep',(int)$group_rep);
	
	if ($event === false) {
		return;
	}
	
	// Tabs
	$tabs = "<ul style='background:#ffffff !important; border-top: 0px; border-left: 0px; border-right: 0px; border-top-left-radius: 0px; border-top-right-radius: 0px; border-bottom-right-radius: 0px; border-bottom-left-radius: 0px; border-color: #D3D3D3;'>";
	$tabs .= "<li><a href='#extended_event_general_page' id='link_general'>".html_print_image('images/lightning_go.png',true)."<span style='position:relative;top:-6px;left:5px;margin-right:10px;'>".__('General')."</span></a></li>";
	$tabs .= "<li><a href='#extended_event_details_page' id='link_details'>".html_print_image('images/zoom.png',true)."<span style='position:relative;top:-6px;left:5px;margin-right:10px;'>".__('Details')."</span></a></li>";
	$tabs .= "<li><a href='#extended_event_custom_fields_page' id='link_custom_fields'>".html_print_image('images/custom_field_col.png',true)."<span style='position:relative;top:-6px;left:5px;margin-right:10px;'>".__('Agent fields')."</span></a></li>";
	$tabs .= "<li><a href='#extended_event_comments_page' id='link_comments'>".html_print_image('images/pencil.png',true)."<span style='position:relative;top:-6px;left:5px;margin-right:10px;'>".__('Comments')."</span></a></li>";

	if (!$readonly &&
		(tags_checks_event_acl($config["id_user"], $event["id_grupo"], "EM", $event['clean_tags'], $childrens_ids)) || (tags_checks_event_acl($config["id_user"], $event["id_grupo"], "EW", $event['clean_tags'],$childrens_ids))) {
		$tabs .= "<li><a href='#extended_event_responses_page' id='link_responses'>".html_print_image('images/event_responses_col.png',true)."<span style='position:relative;top:-6px;left:3px;margin-right:10px;'>".__('Responses')."</span></a></li>";
	}
	if ($event['custom_data'] != '') {
		$tabs .= "<li><a href='#extended_event_custom_data_page' id='link_custom_data'>".html_print_image('images/custom_field_col.png',true)."<span style='position:relative;top:-6px;left:3px;margin-right:10px;'>".__('Custom data')."</span></a></li>";
	}
	$tabs .= "</ul>";
	
	// Get criticity image
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
	
	if (!$readonly && 
	(tags_checks_event_acl($config["id_user"], $event["id_grupo"], "EM", $event['clean_tags'], $childrens_ids)) || (tags_checks_event_acl($config["id_user"], $event["id_grupo"], "EW", $event['clean_tags'],$childrens_ids))) {
		$responses = events_page_responses($event, $childrens_ids);
	}
	else {
		$responses = '';
	}
	
	$console_url = '';
	// If metaconsole switch to node to get details and custom fields
	if ($meta) {
		$server = metaconsole_get_connection_by_id ($server_id);
		metaconsole_connect($server);
	}
	else {
		$server = "";
	}
	
	$details = events_page_details($event, $server);
	
	// Juanma (09/05/2014) Fix: Needs to reconnect to node, in previous funct node connection was lost
	if ($meta) {
		$server = metaconsole_get_connection_by_id ($server_id);
			metaconsole_connect($server);
	}
	
	$custom_fields = events_page_custom_fields($event);
	
	$custom_data = events_page_custom_data($event);
	
	if ($meta) {
		metaconsole_restore_db_force();
	}
	
	$general = events_page_general($event);
	
	$comments = events_page_comments($event, $childrens_ids);
	
	$notifications = '<div id="notification_comment_error" style="display:none">'.ui_print_error_message(__('Error adding comment'),'',true).'</div>';
	$notifications .= '<div id="notification_comment_success" style="display:none">'.ui_print_success_message(__('Comment added successfully'),'',true).'</div>';
	$notifications .= '<div id="notification_status_error" style="display:none">'.ui_print_error_message(__('Error changing event status'),'',true).'</div>';
	$notifications .= '<div id="notification_status_success" style="display:none">'.ui_print_success_message(__('Event status changed successfully'),'',true).'</div>';
	$notifications .= '<div id="notification_owner_error" style="display:none">'.ui_print_error_message(__('Error changing event owner'),'',true).'</div>';
	$notifications .= '<div id="notification_owner_success" style="display:none">'.ui_print_success_message(__('Event owner changed successfully'),'',true).'</div>';
	
	$loading = '<div id="response_loading" style="display:none">'.html_print_image('images/spinner.gif',true).'</div>';
	
	$out = '<div id="tabs" style="height:95%; overflow: auto">'.
				$tabs.
				$notifications.
				$loading.
				$general.
				$details.
				$custom_fields.
				$comments.
				$responses.
				$custom_data.
				html_print_input_hidden('id_event',$event['id_evento']).
			'</div>';
	
	$js = '<script>
	$(function() {
		$tabs = $( "#tabs" ).tabs({
		});
		';
	
	// Load the required tab
	switch ($dialog_page) {
		case "general":
			$js .= '$tabs.tabs( "option", "active", 0);';
			break;
		case "details":
			$js .= '$tabs.tabs( "option", "active", 1);';
			break;
		case "custom_fields":
			$js .= '$tabs.tabs( "option", "active", 2);';
			break;
		case "comments":
			$js .= '$tabs.tabs( "option", "active", 3);';
			break;
		case "responses":
			$js .= '$tabs.tabs( "option", "active", 4);';
			break;
		case "custom_data":
			$js .= '$tabs.tabs( "option", "active", 5);';
			break;
	}
	
	$js .= '
	});
	</script>';
	
	echo $out.$js;
}

if ($get_events_details) {
	$event_ids = explode(',',get_parameter ('event_ids'));
	$events = db_get_all_rows_filter ('tevento',
		array ('id_evento' => $event_ids,
			'order' => 'utimestamp ASC'),
			array ('evento', 'utimestamp', 'estado', 'criticity', 'id_usuario'));
	
	$out = '<table class="eventtable" style="width:100%;height:100%;padding:0px 0px 0px 0px; border-spacing: 0px; margin: 0px 0px 0px 0px;">';
	$out .= '<tr style="font-size:0px; heigth: 0px; background: #ccc;"><td></td><td></td></tr>';
	foreach ($events as $event) {
		switch ($event["estado"]) {
			case 0:
				$img = ui_get_full_url("images/star.png", false, false, false);
				$title = __('New event');
				break;
			case 1:
				$img = ui_get_full_url("images/tick.png", false, false, false);
				$title = __('Event validated');
				break;
			case 2:
				$img = ui_get_full_url("images/hourglass.png", false, false, false);
				$title = __('Event in process');
				break;
		}
		
		$out .= '<tr class="'.get_priority_class ($event['criticity']).'" style="height: 25px;">';
		$out .= '<td class="'.get_priority_class ($event['criticity']).'" style="font-size:7pt" colspan=2>';
		$out .= io_safe_output($event['evento']);
		$out .= '</td></tr>';
		
		$out .= '<tr class="'.get_priority_class ($event['criticity']).'" style="font-size:0px; height: 25px;">';
		$out .= '<td class="'.get_priority_class ($event['criticity']).'" style="width: 18px; text-align:center;">';
		$out .= html_print_image(ui_get_full_url('images/clock.png', false, false, false), true, array('title' => __('Timestamp')), false, true);
		
		$out .= '</td>';
		$out .= '<td class="'.get_priority_class ($event['criticity']).'" style="font-size:7pt">';
		$out .= date($config['date_format'], $event['utimestamp']);
		$out .= '</td></tr>';
		
		$out .= '<tr class="'.get_priority_class ($event['criticity']).'" style="font-size:0px; height: 25px;">';
		$out .= '<td class="'.get_priority_class ($event['criticity']).'" style="width: 18px; text-align:center;">';
		$out .= html_print_image($img, true, array('title' => $title), false, true);
		$out .= '</td>';
		$out .= '<td class="'.get_priority_class ($event['criticity']).'" style="font-size:7pt">';
		$out .= $title;
		if ($event["estado"] == 1) {
			if (empty($event['id_usuario'])) {
				$ack_user = '<i>' . __('Auto') . '</i>';
			}
			else {
				$ack_user = $event['id_usuario'];
			}
			
			$out .= ' (' . $ack_user . ')';
		}
		
		$out .= '</td></tr>';
		
		$out .= '<tr style="font-size:0px; heigth: 0px; background: #999;"><td></td><td>';
		$out .= '</td></tr><tr style="font-size:0px; heigth: 0px; background: #ccc;"><td></td><td>';
		$out .= '</td></tr>';
	}
	$out .= '</table>';
	
	echo $out;
}

if ($table_events) {
	require_once ("include/functions_events.php");
	require_once ("include/functions_graph.php");
	
	$id_agente = (int)get_parameter('id_agente', 0);
	
	// Fix: for tag functionality groups have to be all user_groups (propagate ACL funct!)
	$groups = users_get_groups($config["id_user"]);
	
	$tags_condition = tags_get_acl_tags($config['id_user'],
		array_keys($groups), 'ER', 'event_condition', 'AND');
	
	events_print_event_table ("estado <> 1 $tags_condition", 10, '100%',
		false, $id_agente,true);
}

if ($get_list_events_agents) {
	global $config;
	
	$id_agent = get_parameter('id_agent');
	$server_id = get_parameter('server_id');
	$event_type = get_parameter("event_type");
	$severity = get_parameter("severity");
	$status = get_parameter("status");
	$search = get_parameter("search");
	$id_agent_module = get_parameter('id_agent_module');
	$event_view_hr = get_parameter("event_view_hr");
	$id_user_ack = get_parameter("id_user_ack");
	$tag_with = get_parameter("tag_with");
	$tag_without = get_parameter("tag_without");
	$filter_only_alert = get_parameter("filter_only_alert");
	$date_from = get_parameter("date_from");
	$date_to = get_parameter("date_to");
	$id_user = $config["id_user"];
	$server_id = get_parameter("server_id");
	
	$returned_sql = events_sql_events_grouped_agents($id_agent, $server_id, 
						$event_type,$severity, $status, $search, 
						$id_agent_module, $event_view_hr, $id_user_ack, 
						$tag_with, $tag_without, $filter_only_alert, 
						$date_from, $date_to, $id_user);
	
	$returned_list = events_list_events_grouped_agents($returned_sql);
	
	echo $returned_list;
	return;
}
?>
