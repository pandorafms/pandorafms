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

if($get_event_name) {	
	$event_id = get_parameter ('event_id');
	
	if($meta) {
		$name = events_meta_get_event_name($event_id, $history);
	}
	else {
		$name = db_get_value('evento','tevento','id_evento',$event_id);
	}
	
	if($name === false) {
		return;
	}
	
	echo io_safe_output($name);
	
	return;
}

if($get_response_description) {	
	$response_id = get_parameter ('response_id');
	
	$description = db_get_value('description','tevent_response','id',$response_id);
	
	if($description === false) {
		return;
	}
	
	$description = io_safe_output($description);
	$description = str_replace("\r\n", '<br>', $description);

	echo $description;
	
	return;
}

if($get_response_params) {	
	$response_id = get_parameter ('response_id');
	
	$params = db_get_value('params','tevent_response','id',$response_id);
	
	if($params === false) {
		return;
	}
	
	echo json_encode(explode(',',$params));
	
	return;
}

if($get_response_target) {	
	$response_id = get_parameter ('response_id');
	$event_id = get_parameter ('event_id');
	$server_id = get_parameter ('server_id', 0);
	
	$event_response = db_get_row('tevent_response','id',$response_id);
	
	if(empty($event_response)) {
		return;
	}
	
	echo events_get_response_target($event_id, $response_id, $server_id);
	
	return;
}

if($get_response) {	
	$response_id = get_parameter ('response_id');
	
	$event_response = db_get_row('tevent_response','id',$response_id);
	
	if(empty($event_response)) {
		return;
	}
	
	echo json_encode($event_response);
	return;
}

if($perform_event_response) {
	global $config;
		
	$command = get_parameter('target','');

	echo system('/usr/bin/timeout 10 '.io_safe_output($command).' 2>&1');
	
	return;
}

if($dialogue_event_response) {
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
			echo "<iframe src='$command' id='divframe' style='width:100%;height:90%;'></iframe>";
			break;
	}
}

if($add_comment) { 
	$comment = get_parameter ('comment');
	$event_id = get_parameter ('event_id');

	$return = events_comment ($event_id, $comment, 'Added comment', $meta, $history);

	if ($return)
		echo 'comment_ok';
	else
		echo 'comment_error';
		
	return;
}

if($change_status) { 
	$event_ids = get_parameter ('event_ids');
	$new_status = get_parameter ('new_status');
	
	$return = events_change_status (explode(',',$event_ids), $new_status, $meta, $history); 

	if ($return)
		echo 'status_ok';
	else
		echo 'status_error';
		
	return;
}

if($change_owner) { 
	$new_owner = get_parameter ('new_owner');
	$event_id = get_parameter ('event_id');
	$similars = true;
	
	if($new_owner == -1) {
		$new_owner = '';
	}

	$return = events_change_owner($event_id, $new_owner, true, $meta, $history);

	if ($return)
		echo 'owner_ok';
	else
		echo 'owner_error';
		
	return;
}

if($get_extended_event) {
	global $config;
	
	$event_id = get_parameter('event_id',false);

	if($meta) {
		$event = events_meta_get_event($event_id, false, $history);
	}
	else {
		$event = events_get_event($event_id);
	}
	
	// Clean url from events and store in array
	$event['clean_tags'] = events_clean_tags($event['tags']);

	// If the event is not found, we abort
	if(empty($event)) {
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
	else if($config["id_user"] == $event['owner_user']) { 
		//Do nothing if you're the owner user, you get access
	}
	else if($event['id_grupo'] == 0){
		//If the event has access to all groups, you get access
	}
	else {
		// Get your groups
		$groups = users_get_groups($config['id_user'], 'ER');
		
		if(in_array ($event['id_grupo'], array_keys ($groups))) {
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

	if($event === false) {
		return;
	}
	
	// Tabs
	$tabs = "<ul style='background:#eeeeee;border:0px'>";
    $tabs .= "<li><a href='#extended_event_general_page' id='link_general'>".html_print_image('images/lightning_go.png',true).__('General')."</a></li>";
    $tabs .= "<li><a href='#extended_event_details_page' id='link_details'>".html_print_image('images/zoom.png',true).__('Details')."</a></li>";
    $tabs .= "<li><a href='#extended_event_custom_fields_page' id='link_custom_fields'>".html_print_image('images/note.png',true).__('Agent fields')."</a></li>";
    $tabs .= "<li><a href='#extended_event_comments_page' id='link_comments'>".html_print_image('images/pencil.png',true).__('Comments')."</a></li>";
    if (tags_check_acl ($config['id_user'], $event['id_grupo'], "EW", $event['clean_tags']) || tags_check_acl ($config['id_user'], $event['id_grupo'], "EM", $event['clean_tags'])) {
		$tabs .= "<li><a href='#extended_event_responses_page' id='link_responses'>".html_print_image('images/cog.png',true).__('Responses')."</a></li>";
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
	
    if (tags_check_acl ($config['id_user'], $event['id_grupo'], "EW", $event['clean_tags']) || tags_check_acl ($config['id_user'], $event['id_grupo'], "EM", $event['clean_tags'])) {
		$responses = events_page_responses($event);
	}
	else {
		$responses = '';
	}
	
	$console_url = '';
	// If metaconsole switch to node to get details and custom fields
	if($meta) {
		$server = metaconsole_get_connection_by_id ($server_id);
		metaconsole_connect($server);
	}
	else {
		$server = "";
	}

	$details = events_page_details($event, $server);
	
	$custom_fields = events_page_custom_fields($event);

	if($meta) {
		metaconsole_restore_db_force();
	}
	
	$general = events_page_general($event);
	
	$comments = events_page_comments($event);
	
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
				html_print_input_hidden('id_event',$event['id_evento']).
			'</div>';
	
	$js = '<script>
	$(function() {
		$tabs = $( "#tabs" ).tabs({
		});
		';
	
	// Load the required tab
	switch($dialog_page) {
		case "general":
			$js .= '$tabs.tabs("select", 0);';
			break;
		case "details":
			$js .= '$tabs.tabs("select", 1);';
			break;
		case "custom_fields":
			$js .= '$tabs.tabs("select", 2);';
			break;
		case "comments":
			$js .= '$tabs.tabs("select", 3);';
			break;
		case "responses":
			$js .= '$tabs.tabs("select", 4);';
			break;
	}
	
	$js .= '
	});
	</script>';
	
	echo $out.$js;
}

if($get_events_details) {
	$event_ids = explode(',',get_parameter ('event_ids'));
	$events = db_get_all_rows_filter ('tevento',
		array ('id_evento' => $event_ids,
			'order' => 'utimestamp ASC'),
			array ('evento', 'utimestamp', 'estado', 'criticity'));

	$out = '<table class="eventtable" style="width:100%;height:100%;padding:0px 0px 0px 0px; border-spacing: 0px; margin: 0px 0px 0px 0px;">';
	$out .= '<tr style="font-size:0px; heigth: 0px; background: #ccc;"><td></td><td></td></tr>';
	foreach($events as $event) {
		switch($event["estado"]) {
			case 0:
				$img = "../../images/star.png";
				$title = __('New event');
				break;
			case 1:
				$img = "../../images/tick.png";
				$title = __('Event validated');
				break;
			case 2:
				$img = "../../images/hourglass.png";
				$title = __('Event in process');
				break;
		}
			
		$out .= '<tr class="'.get_priority_class ($event['criticity']).'"><td class="'.get_priority_class ($event['criticity']).'">';
		$out .= '<img src="'.$img.'" alt="'.$title.'" title="'.$title.'">';
		$out .= '</td><td class="'.get_priority_class ($event['criticity']).'" style="font-size:7pt">';
		$out .= io_safe_output($event['evento']);
		$out .= '</td></tr><tr style="font-size:0px; heigth: 0px; background: #999;"><td></td><td>';
		$out .= '</td></tr><tr style="font-size:0px; heigth: 0px; background: #ccc;"><td></td><td>';
		$out .= '</td></tr>';
	}
	$out .= '</table>';
	
	echo $out;
}
?>
