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

$get_events_details = (bool) get_parameter ('get_events_details');
$get_extended_event = (bool) get_parameter ('get_extended_event');
$change_status = (bool) get_parameter ('change_status');
$change_owner = (bool) get_parameter ('change_owner');
$add_comment = (bool) get_parameter ('add_comment');

if($add_comment) { 
	$comment = get_parameter ('comment');
	$event_id = get_parameter ('event_id');
	$similars = true;
	
	$return = events_comment_event ($event_id, $similars, $comment);

	if ($return)
		echo 'comment_ok';
	else
		echo 'comment_error';
		
	return;
}

if($change_status) { 
	$new_status = get_parameter ('new_status');
	$event_id = get_parameter ('event_id');
	$similars = true;
	
	$return = events_validate_event ($event_id, $similars, $new_status);

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

	$return = events_change_owner_event($event_id, $similars, $new_owner, true);

	if ($return)
		echo 'owner_ok';
	else
		echo 'owner_error';
		
	return;
}

if($get_extended_event) {
	global $config;
	
	$dialog_page = get_parameter('dialog_page','general');
	$event_id = get_parameter('event_id',false);
	
	$event = events_get_event($event_id);
	
	$group_rep = get_parameter('group_rep',false);
	
	// Print group_rep in a hidden field to recover it from javascript
	html_debug_print('group_rep',(int)$group_rep,true);
	
	if($event === false) {
		return;
	}
	
	// Tabs
	$tabs = "<ul style='background:#eeeeee;border:0px'>
      <li><a href='#extended_event_general_page' id='link_general'>".__('General')."</a></li>
      <li><a href='#extended_event_details_page' id='link_details'>".__('Details')."</a></li>
      <li><a href='#extended_event_custom_fields_page' id='link_custom_fields'>".__('Agent custom fields')."</a></li>
      <li><a href='#extended_event_comments_page' id='link_comments'>".__('Comments')."</a></li>
      <li><a href='#extended_event_actions_page' id='link_actions'>".__('Actions')."</a></li>
   </ul>";
	
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

	
	$actions = events_page_actions($event);
	
	$details = events_page_details($event);
	
	$custom_fields = events_page_custom_fields($event);

	$general = events_page_general($event);
	
	$comments = events_page_comments($event);
	
	$notifications = '<div id="notification_comment_error" style="display:none">'.ui_print_error_message(__('Error adding comment'),'',true).'</div>';
	$notifications .= '<div id="notification_comment_success" style="display:none">'.ui_print_success_message(__('Comment added successfully'),'',true).'</div>';
	$notifications .= '<div id="notification_status_error" style="display:none">'.ui_print_success_message(__('Error changing event status'),'',true).'</div>';
	$notifications .= '<div id="notification_status_success" style="display:none">'.ui_print_success_message(__('Event status changed successfully'),'',true).'</div>';
	$notifications .= '<div id="notification_owner_error" style="display:none">'.ui_print_success_message(__('Error changing event owner'),'',true).'</div>';
	$notifications .= '<div id="notification_owner_success" style="display:none">'.ui_print_success_message(__('Event owner changed successfully'),'',true).'</div>';

	$out = '<div id="tabs" style="height:95%; overflow: auto">'.$tabs.$notifications.$general.$details.$custom_fields.$comments.$actions.html_print_input_hidden('id_event',$event['id_evento']).'</div>';
	
	$js = '<script>
	$(function() {
		$tabs = $( "#tabs" ).tabs({
		
		});
		';
	
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
		case "actions":
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

function events_page_actions ($event) {
	global $config;
	/////////
	// Actions
	/////////
	
	$table_actions->width = '100%';
	$table_actions->data = array ();
	$table_actions->head = array ();
	$table_actions->style[0] = 'width:35%; font-weight: bold; text-align: left;';
	$table_actions->style[1] = 'text-align: left;';
	$table_actions->class = "databox alternate";

	// Owner
	$data = array();
	$data[0] = __('Change owner');
	
	$user_name = db_get_value('fullname', 'tusuario', 'id_user', $config['id_user']);

	$owners = array($config['id_user'] => $user_name);
	
	if($event['owner_user'] == '') {
		$owner_name = __('None');
	}
	else {
		$owner_name = db_get_value('fullname', 'tusuario', 'id_user', $event['owner_user']);
		$owners[$event['owner_user']] = $owner_name;
	}

	$data[1] = html_print_select($owners, 'id_owner', $event['owner_user'], '', __('None'), -1, true);
	$data[1] .= html_print_button(__('Update'),'owner_button',false,'event_change_owner();','class="sub next"',true);
	
	$table_actions->data[] = $data;
	
	// Status
	$data = array();
	$data[0] = __('Change status');
	
	$status = array(0 => __('New'), 2 => __('In process'), 1 => __('Validated'));
	
	$data[1] = html_print_select($status, 'estado', $event['estado'], '', '', 0, true);
	$data[1] .= html_print_button(__('Update'),'status_button',false,'event_change_status();','class="sub next"',true);

	$table_actions->data[] = $data;
	
	// Comments
	$data = array();
	$data[0] = __('Comment');
	$data[1] = html_print_button(__('Add comment'),'comment_button',false,'$(\'#link_comments\').trigger(\'click\');','class="sub next"',true);

	$table_actions->data[] = $data;
	
	// Delete
	$data = array();
	$data[0] = __('Delete event');
	$data[1] = '<form method="post" action="index.php?sec=eventos&sec2=operation/events/events&section=list&delete=1&eventid='.$event['id_evento'].'">';
	$data[1] .= html_print_button(__('Delete event'),'delete_button',false,'if(!confirm(\''.__('Are you sure?').'\')) { return false; } this.form.submit();','class="sub cancel"',true);
	$data[1] .= '</form>';

	$table_actions->data[] = $data;
	
	$actions = '<div id="extended_event_actions_page" class="extended_event_pages">'.html_print_table($table_actions, true).'</div>';

	return $actions;
}

function events_page_custom_fields ($event) {
	global $config;
	/////////
	// Custom fields
	/////////
	
	$table->width = '100%';
	$table->data = array ();
	$table->head = array ();
	$table->style[0] = 'width:35%; font-weight: bold; text-align: left;';
	$table->style[1] = 'text-align: left;';
	$table->class = "databox alternate";
	
	$fields = db_get_all_rows_filter('tagent_custom_fields');
	
	if($event['id_agente'] == 0) {
		$fields_data = array();
	}
	else {
		$fields_data = db_get_all_rows_filter('tagent_custom_data', array('id_agent' => $event['id_agente']));
		if(is_array($fields_data)) {
			$fields_data_aux = array();
			foreach($fields_data as $fd) {
				$fields_data_aux[$fd['id_field']] = $fd['description'];
			}
			$fields_data = $fields_data_aux;
		}
	}
	
	foreach($fields as $field) {
		// Owner
		$data = array();
		$data[0] = $field['name'];
		
		$data[1] = isset($fields_data[$field['id_field']]) ? $fields_data[$field['id_field']] : '<i>'.__('N/A').'</i>';
		
		$field['id_field'];
		
		$table->data[] = $data;
	}
	
	$custom_fields = '<div id="extended_event_custom_fields_page" class="extended_event_pages">'.html_print_table($table, true).'</div>';

	return $custom_fields;
}

function events_page_details ($event) {
	global $img_sev;
	
	/////////
	// Details
	/////////
	
	$table_details->width = '100%';
	$table_details->data = array ();
	$table_details->head = array ();
	$table_details->style[0] = 'width:35%; font-weight: bold; text-align: left;';
	$table_details->style[1] = 'text-align: left;';
	$table_details->class = "databox alternate";
	
	switch($event['event_type']) {
		case 'going_unknown':
		case 'going_up_warning':
		case 'going_down_warning':
		case 'going_up_critical':
		case 'going_down_critical':
			
			break;
	}
	
	if ($event["id_agente"] != 0) {
		$agent = db_get_row('tagente','id_agente',$event["id_agente"]);
	}
	else {
		$agent = array();
	}
	
	$data = array();
	$data[0] = __('Agent details');
	$data[1] = empty($agent) ? '<i>' . __('N/A') . '</i>' : '';
	$table_details->data[] = $data;
	
	if (!empty($agent)) {
		$data = array();
		$data[0] = '<div style="font-weight:normal; margin-left: 20px;">'.__('Name').'</div>';
		$data[1] = ui_print_agent_name ($event["id_agente"], true);
		$table_details->data[] = $data;
		
		$data = array();
		$data[0] = '<div style="font-weight:normal; margin-left: 20px;">'.__('IP Address').'</div>';
		$data[1] = empty($agent['url_address']) ? '<i>'.__('N/A').'</i>' : $agent['url_address'];
		$table_details->data[] = $data;
		
		$data = array();
		$data[0] = '<div style="font-weight:normal; margin-left: 20px;">'.__('OS').'</div>';
		$data[1] = ui_print_os_icon ($agent["id_os"], true, true).' ('.$agent["os_version"].')';
		$table_details->data[] = $data;

		$data = array();
		$data[0] = '<div style="font-weight:normal; margin-left: 20px;">'.__('Last contact').'</div>';
		$data[1] = $agent["ultimo_contacto"];
		$table_details->data[] = $data;
		
		$data = array();
		$data[0] = '<div style="font-weight:normal; margin-left: 20px;">'.__('Last remote contact').'</div>';
		if ($agent["ultimo_contacto_remoto"] == "01-01-1970 00:00:00") { 
			$data[1] .= __('Never');
		}
		else {
			$data[1] .= $agent["ultimo_contacto_remoto"];
		}
		$table_details->data[] = $data;
		
		$data = array();
		$data[0] = '<div style="font-weight:normal; margin-left: 20px;">'.__('Custom fields').'</div>';
		$data[1] = html_print_button(__('View custom fields'),'custom_button',false,'$(\'#link_custom_fields\').trigger(\'click\');','class="sub next"',true);
		$table_details->data[] = $data;
	}
	
	if ($event["id_agentmodule"] != 0) {
		$module = db_get_row_filter('tagente_modulo',array('id_agente_modulo' => $event["id_agentmodule"], 'delete_pending' => 0));
	}
	else {
		$module = array();
	}
		
	$data = array();
	$data[0] = __('Module details');
	$data[1] = empty($module) ? '<i>' . __('N/A') . '</i>' : '';
	$table_details->data[] = $data;
		
	if (!empty($module)) {
		// Module name
		$data = array();
		$data[0] = '<div style="font-weight:normal; margin-left: 20px;">'.__('Name').'</div>';
		$data[1] = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$event["id_agente"].'&amp;tab=data"><b>';
		$data[1] .= $module['nombre'];
		$data[1] .= '</b></a>';
		$table_details->data[] = $data;
		
		// Module group
		$data = array();
		$data[0] = '<div style="font-weight:normal; margin-left: 20px;">'.__('Module group').'</div>';
		$id_module_group = $module['id_module_group'];
		if($id_module_group == 0) {
			$data[1] = __('No assigned');
		}
		else {
			$module_group = db_get_value('name', 'tmodule_group', 'id_mg', $id_module_group);
			$data[1] = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;status=-1&amp;modulegroup=' . $id_module_group . '">';
			$data[1] .= $module_group;
			$data[1] .= '</a>';
		}
		$table_details->data[] = $data;
		
		$data = array();
		$data[0] = '<div style="font-weight:normal; margin-left: 20px;">'.__('Graph').'</div>';
		$data[1] = '<a href="javascript:winopeng(\'operation/agentes/stat_win.php?type=sparse&period=86400&id='.$event["id_agentmodule"].'&label=L2Rldi9zZGE2&refresh=600\',\'day_5f80228c\')">';
		$data[1] .= html_print_image('images/chart_curve.png',true);
		$data[1] .= '</a>';
		$table_details->data[] = $data;
	}

	$data = array();
	$data[0] = __('Alert details');
	$data[1] = $event["id_alert_am"] == 0 ? '<i>' . __('N/A') . '</i>' : '';
	$table_details->data[] = $data;
	
	if($event["id_alert_am"] != 0) {
		$data = array();
		$data[0] = '<div style="font-weight:normal; margin-left: 20px;">'.__('Source').'</div>';
		$data[1] = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$event["id_agente"].'&amp;tab=alert">';
		$standby = db_get_value('standby', 'talert_template_modules', 'id', $event["id_alert_am"]);
		if(!$standby) {
			$data[1] .= html_print_image ("images/bell.png", true,
				array ("title" => __('Go to data overview')));
		}
		else {
			$data[1] .= html_print_image ("images/bell_pause.png", true,
				array ("title" => __('Go to data overview')));
		}
		
		$sql = 'SELECT name
			FROM talert_templates
			WHERE id IN (SELECT id_alert_template
					FROM talert_template_modules
					WHERE id = ' . $event["id_alert_am"] . ');';
		
		$templateName = db_get_sql($sql);
		
		$data[1] .= $templateName;
		
		$data[1] .= '</a>';			

		$table_details->data[] = $data;
		
		$data = array();
		$data[0] = '<div style="font-weight:normal; margin-left: 20px;">'.__('Priority').'</div>';
		
		$priority_code = db_get_value('priority', 'talert_template_modules', 'id', $event["id_alert_am"]);
		$alert_priority = get_priority_name ($priority_code);
		$data[1] = html_print_image ($img_sev, true, 
			array ("class" => "image_status",
				"width" => 12,
				"height" => 12,
				"title" => $alert_priority));
		$data[1] .= ' '.$alert_priority;
		
		$table_details->data[] = $data;
	}
	
	switch($event['event_type']) {
		case 'going_unknown':
			$data = array();
			$data[0] = __('Instructions');
			if ($event["unknown_instructions"] != '') {
				$data[1] = $event["unknown_instructions"];
			}
			else {
				$data[1] = '<i>' . __('N/A') . '</i>';
			}
			$table_details->data[] = $data;
			break;
		case 'going_up_warning':
		case 'going_down_warning':
			$data = array();
			$data[0] = __('Instructions');
			if ($event["warning_instructions"] != '') {
				$data[1] = $event["warning_instructions"];
			}
			else {
				$data[1] = '<i>' . __('N/A') . '</i>';
			}
			$table_details->data[] = $data;
			break;
		case 'going_up_critical':
		case 'going_down_critical':
			$data = array();
			$data[0] = __('Instructions');
			if ($event["critical_instructions"] != '') {
				$data[1] = $event["critical_instructions"];
			}
			else {
				$data[1] = '<i>' . __('N/A') . '</i>';
			}
			$table_details->data[] = $data;
			break;
	}
		
	$data = array();
	$data[0] = __('Extra id');
	if ($event["id_extra"] != '') {
		$data[1] = $event["id_extra"];
	}
	else {
		$data[1] = '<i>' . __('N/A') . '</i>';
	}
	$table_details->data[] = $data;
	
	$data = array();
	$data[0] = __('Source');
	if ($event["source"] != '') {
		$data[1] = $event["source"];
	}
	else {
		$data[1] = '<i>' . __('N/A') . '</i>';
	}
	$table_details->data[] = $data;
	
	$details = '<div id="extended_event_details_page" class="extended_event_pages">'.html_print_table($table_details, true).'</div>';

	return $details;
}

function events_page_general ($event) {
	global $img_sev;
	global $config;
	global $group_rep;
	/////////
	// General
	/////////
	
	$table_general->width = '100%';
	$table_general->data = array ();
	$table_general->head = array ();
	$table_general->style[0] = 'width:35%; font-weight: bold; text-align: left;';
	$table_general->style[1] = 'text-align: left;';
	$table_general->class = "databox alternate";
	
	$data = array();
	$data[0] = __('Event ID');
	$data[1] = "#".$event["id_evento"];
	$table_general->data[] = $data;
	
	$data = array();
	$data[0] = __('Event name');
	$data[1] = io_safe_output(io_safe_output($event["evento"]));
	$table_general->data[] = $data;
	
	$data = array();
	$data[0] = __('Timestamp');
	if ($group_rep == 1 && $event["event_rep"] > 0) {
		$data[1] = __('First event').': '.date ($config["date_format"], $event['timestamp_rep_min']).'<br>'.__('Last event').': '.date ($config["date_format"], $event['timestamp_rep']);
	}
	else {
		$data[1] = date ($config["date_format"], strtotime($event["timestamp"]));
	}
	$table_general->data[] = $data;
	
	$data = array();
	$data[0] = __('Type');
	$data[1] = events_print_type_img ($event["event_type"], true).' '.events_print_type_description($event["event_type"], true);
	$table_general->data[] = $data;
	
	$data = array();
	$data[0] = __('Repeated');
	if ($group_rep != 0) {
		if($event["event_rep"] == 0) {
			$data[1] = __('No');
		}
		else {
			$data[1] = sprintf("%d Times",$event["event_rep"]);
		}
	}
	else {
		$data[1] = __('No');
	}
	$table_general->data[] = $data;
	
	$data = array();
	$data[0] = __('Severity');
	$event_criticity = get_priority_name ($event["criticity"]);
	$data[1] = html_print_image ($img_sev, true, 
		array ("class" => "image_status",
			"width" => 12,
			"height" => 12,
			"title" => $event_criticity));
	$data[1] .= ' '.$event_criticity;
	$table_general->data[] = $data;
	
	// Get Status
	switch($event['estado']) {
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
	
	$data = array();
	$data[0] = __('Status');
	$data[1] = html_print_image($img_st,true).' '.$title_st;
	$table_general->data[] = $data;
	
	// If event is validated, show who and when acknowleded it
	$data = array();
	$data[0] = __('Acknowledged by');
		
	if($event['estado'] == 1) {
		$user_ack = db_get_value('fullname', 'tusuario', 'id_user', $event['id_usuario']);
		$date_ack = date ($config["date_format"], $event['ack_utimestamp']);
		$data[1] = $user_ack.' ('.$date_ack.')';	
	}
	else {
		$data[1] = '<i>'.__('N/A').'</i>';
	}
	$table_general->data[] = $data;
	
	$data = array();
	$data[0] = __('Group');
	$data[1] = ui_print_group_icon ($event["id_grupo"], true);
	$data[1] .= groups_get_name ($event["id_grupo"]);
	$table_general->data[] = $data;
	
	$data = array();
	$data[0] = __('Tags');

	if ($event["tags"] != '') {
		$tag_array = explode(',', $event["tags"]);
		$data[1] = '';
		foreach ($tag_array as $tag_element){
			$blank_char_pos = strpos($tag_element, ' ');
			$tag_name = substr($tag_element, 0, $blank_char_pos);
			$tag_url = substr($tag_element, $blank_char_pos + 1);
			$data[1] .= ' ' .$tag_name;
			if (!empty($tag_url)){
				$data[1] .= ' <a href="javascript: openURLTagWindow(\'' . $tag_url . '\');">' . html_print_image('images/lupa.png', true, array('title' => __('Click here to open a popup window with URL tag'))) . '</a> ';
			}
			$data[1] .= ',';
		}
		$data[1] = rtrim($table_general, ',');
	}
	else {
		$data[1] = '<i>' . __('N/A') . '</i>';
	}
	$table_general->data[] = $data;
	 
	$general = '<div id="extended_event_general_page" class="extended_event_pages">'.html_print_table($table_general,true).'</div>';
	
	return $general;
}
	
function events_page_comments ($event) {
	/////////
	// Comments
	/////////
	
	$table_comments->width = '100%';
	$table_comments->data = array ();
	$table_comments->head = array ();
	$table_comments->style[0] = 'width:35%; vertical-align: top; text-align: left;';
	$table_comments->style[1] = 'text-align: left;';
	$table_comments->class = "databox alternate";	
	
	$comments_array = explode('<br>',io_safe_output($event["user_comment"]));

	// Split comments and put in table
	$col = 0;
	$data = array();
	foreach($comments_array as $c) {	
		switch($col) {
			case 0:
				$row_text = preg_replace('/\s*--\s*/',"",$c);
				html_debug_print($row_text,true);
				$row_text = preg_replace('/\<\/b\>/',"</i>",$row_text);
				html_debug_print($row_text,true);
				$row_text = preg_replace('/\[/',"</b><br><br><i>[",$row_text);
				$row_text = preg_replace('/[\[|\]]/',"",$row_text);
				break;
			case 1:
				$row_text = preg_replace("/\r\n/","<br>",io_safe_output(strip_tags($c)));
				break;
		}
		
		$data[$col] = $row_text;
		
		$col++;
		
		if($col == 2) {
			$col = 0;
			$table_comments->data[] = $data;
			$data = array();
		}
	}
	
	if(count($comments_array) == 1 && $comments_array[0] == '') {
		$table_comments->style[0] = 'text-align:center;';
		$table_comments->colspan[0][0] = 2;
		$data = array();
		$data[0] = __('There are no comments');
		$table_comments->data[] = $data;
	}
	
	$comments_form = '<br><div id="comments_form" style="width:98%;">'.html_print_textarea("comment", 3, 10, '', 'style="min-height: 15px; width: 100%;"', true);
	$comments_form .= '<br><div style="text-align:right;">'.html_print_button(__('Add comment'),'comment_button',false,'event_comment();','class="sub next"',true).'</div><br></div>';
	
	$comments = '<div id="extended_event_comments_page" class="extended_event_pages">'.$comments_form.html_print_table($table_comments, true).'</div>';
	
	return $comments;
}
?>
