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

global $config;

require_once ($config["homedir"] . "/include/functions_ui.php");


$table = new stdClass();
if(!isset($table->width)) {
	$table->width = '100%';
}
$table->id = "eventtable";
$table->cellpadding = 4;
$table->cellspacing = 4;
if(!isset($table->class)) {
	$table->class = "databox data";
}
$table->head = array ();
$table->data = array ();

if ($group_rep == 2) {
	$table->class = "databox filters data";
	$table->head[1] = __('Agent');
	$table->head[5] = __('More detail');
	
	$params = "search=" . rawurlencode(io_safe_input($search)) . 
		"&amp;severity=" . $severity . 
		"&amp;status=" . $status . 
		"&amp;id_group=" . $id_group . 
		"&amp;recursion=" . $recursion . 
		"&amp;refr=" . (int)get_parameter("refr", 0) . 
		"&amp;id_agent_module=" . $id_agent_module . 
		"&amp;pagination=" . $pagination . 
		"&amp;group_rep=2" . 
		"&amp;event_view_hr=" . $event_view_hr . 
		"&amp;id_user_ack=" . $id_user_ack .
		"&amp;tag_with=". $tag_with_base64 . 
		"&amp;tag_without=" . $tag_without_base64 . 
		"&amp;filter_only_alert" . $filter_only_alert .
		"&amp;offset=" . $offset .
		"&amp;toogle_filter=no" .
		"&amp;filter_id=" . $filter_id .
		"&amp;id_name=" . $id_name .
		"&amp;history=" . (int)$history .
		"&amp;section=" . $section .
		"&amp;open_filter=" . $open_filter .
		"&amp;date_from=" . $date_from .
		"&amp;date_to=" . $date_to .
		"&amp;pure=" . $config["pure"];

	$url = "index.php?sec=eventos&amp;sec2=operation/events/events&amp;" . $params;
	foreach ($result as $key => $res) {
		
		if ($res['event_type'] == 'alert_fired') {
			$table->rowstyle[$key] = 'background: #FFA631;';
		}
		elseif ($res['event_type'] == 'going_up_critical' || $res['event_type'] == 'going_down_critical'){
			$table->rowstyle[$key] = 'background: #FC4444;';
		}
		elseif ($res['event_type'] == 'going_up_warning' || $res['event_type'] == 'going_down_warning'){
			$table->rowstyle[$key] = 'background: #FAD403;';
		}
		elseif ($res['event_type'] == 'going_up_normal' || $res['event_type'] == 'going_down_normal'){
			$table->rowstyle[$key] = 'background: #80BA27;';
		}
		elseif ($res['event_type'] == 'going_unknown'){
			$table->rowstyle[$key] = 'background: #B2B2B2;';
		}
		
		
		if  ($meta)
			$table->data[$key][1] = __('The Agent: ') . '"' . 
				$res['id_agent'] . '", ' . __(' has ') . 
					$res['total'] . __(' events.');
		else
			$table->data[$key][1] = __('The Agent: ') . '"' . 
				agents_get_name ($res['id_agent']) . '", ' . __(' has ') . 
					$res['total'] . __(' events.');
		
		$uniq = uniqid();
		if  ($meta) {
			$table->data[$key][2] = '<img id="open_agent_groups" src=images/zoom_mc.png data-id="'.$table->id.'-'.$uniq.'-0" data-open="false"
				onclick=\'show_events_group_agent("'.$table->id.'-'.$uniq.'-0","'.$res['id_agent'].'",'.$res['id_server'].');\' />';
		}
		else {
			$table->data[$key][2] = '<img id="open_agent_groups" src="images/zoom_mc.png" data-id="'.$table->id.'-'.$uniq.'-0" data-open="false"
				onclick=\'show_events_group_agent("'.$table->id.'-'.$uniq.'-0",'.$res['id_agent'].',false);\'/>';
		}
		$table->cellstyle[$uniq][0] = "display:none;";
		$table->data[$uniq][0] = false;
	}
	
	if ($result) {
		if ($allow_pagination) {
			ui_pagination ($total_events, $url, $offset, $pagination);
		}
			
		html_print_table ($table);
		
		if ($allow_pagination) {
			ui_pagination ($total_events, $url, $offset, $pagination);
		}
	}
	else {
		echo '<div class="nf">' . __('No events') . '</div>';
	}
}
else {
	
	//fields that the user has selected to show
	if ($meta) {
		$show_fields = events_meta_get_custom_fields_user();
	}
	else {
		$show_fields = explode (',', $config['event_fields']);
	}

	//headers
	$i = 0;
	$table->head[$i] = __('ID');

	$table->align[$i] = 'left';

	$i++;
	if (in_array('server_name', $show_fields)) {
		$table->head[$i] = __('Server');
		$table->align[$i] = 'left';
		$i++;
	}
	if (in_array('estado', $show_fields)) {
		$table->head[$i] = __('Status');
		$table->align[$i] = 'left';
		$i++;
	}
	if (in_array('id_evento', $show_fields)) {
		$table->head[$i] = __('Event ID');
		$table->align[$i] = 'left';

		$i++;
	}
	if (in_array('evento', $show_fields)) {
		$table->head[$i] = __('Event Name');
		$table->align[$i] = 'left';
		$table->style[$i] = 'min-width: 200px; max-width: 350px; word-break: break-all;';
		$i++;
	}
	if (in_array('id_agente', $show_fields)) {
		$table->head[$i] = __('Agent name');
		$table->align[$i] = 'left';
		$table->style[$i] = 'max-width: 350px; word-break: break-all;';
		$i++;
	}
	if (in_array('timestamp', $show_fields)) {
		$table->head[$i] = __('Timestamp');
		$table->align[$i] = 'left';
		
		$i++;
	}
	if (in_array('id_usuario', $show_fields)) {
		$table->head[$i] = __('User');
		$table->align[$i] = 'left';
		
		$i++;
	}
	if (in_array('owner_user', $show_fields)) {
		$table->head[$i] = __('Owner');
		$table->align[$i] = 'left';
		
		$i++;
	}
	if (in_array('id_grupo', $show_fields)) {
		$table->head[$i] = __('Group');
		$table->align[$i] = 'left';
		
		$i++;
	}
	if (in_array('event_type', $show_fields)) {
		$table->head[$i] = __('Event type');
		$table->align[$i] = 'left';
		
		$table->style[$i] = 'min-width: 85px;';
		$i++;
	}
	if (in_array('id_agentmodule', $show_fields)) {
		$table->head[$i] = __('Agent Module');
		$table->align[$i] = 'left';
		
		$i++;
	}
	if (in_array('id_alert_am', $show_fields)) {
		$table->head[$i] = __('Alert');
		$table->align[$i] = 'left';
		
		$i++;
	}

	if (in_array('criticity', $show_fields)) {
		$table->head[$i] = __('Severity');
		$table->align[$i] = 'left';
		
		$i++;
	}
	if (in_array('user_comment', $show_fields)) {
		$table->head[$i] = __('Comment');
		$table->align[$i] = 'left';
		
		$i++;
	}
	if (in_array('tags', $show_fields)) {
		$table->head[$i] = __('Tags');
		$table->align[$i] = 'left';
		
		$i++;
	}
	if (in_array('source', $show_fields)) {
		$table->head[$i] = __('Source');
		$table->align[$i] = 'left';
		
		$i++;
	}
	if (in_array('id_extra', $show_fields)) {
		$table->head[$i] = __('Extra ID');
		$table->align[$i] = 'left';
		
		$i++;
	}
	if (in_array('ack_utimestamp', $show_fields)) {
		$table->head[$i] = __('ACK Timestamp');
		$table->align[$i] = 'left';
		
		$i++;
	}
	if (in_array('instructions', $show_fields)) {
		$table->head[$i] = __('Instructions');
		$table->align[$i] = 'left';
		
		$i++;
	}
	if ($i != 0 && $allow_action) {
		$table->head[$i] = __('Action');
		$table->align[$i] = 'left';
		
		$table->size[$i] = '90px';
		$i++;
		if (check_acl ($config["id_user"], 0, "EW") == 1 && !$readonly) {
			$table->head[$i] = html_print_checkbox ("all_validate_box", "1", false, true);
			$table->align[$i] = 'left';
		}
	}

	if ($meta) {
		// Get info of the all servers to use it on hash auth
		$servers_url_hash = metaconsole_get_servers_url_hash();
		$servers = metaconsole_get_servers();
	}

	$show_delete_button = false;
	$show_validate_button = false;

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
		
		//print status
		$estado = $event["estado"];
		
		// Colored box
		switch($estado) {
			case EVENT_NEW:
				$img_st = "images/star.png";
				$title_st = __('New event');
				break;
			case EVENT_VALIDATE:
				$img_st = "images/tick.png";
				$title_st = __('Event validated');
				break;
			case EVENT_PROCESS:
				$img_st = "images/hourglass.png";
				$title_st = __('Event in process');
				break;
		}
		
		$i = 0;
		
		$data[$i] = "#".$event["id_evento"];
		$table->cellstyle[count($table->data)][$i] = 'background: #F3F3F3; color: #111 !important;';
		
		// Pass grouped values in hidden fields to use it from modal window
		if ($group_rep) {
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
		$data[$i] .= html_print_input_hidden('childrens_ids', json_encode($childrens_ids), true);
		
		// Store server id if is metaconsole. 0 otherwise
		if ($meta) {
			$server_id = $event['server_id'];
			
			// If meta activated, propagate the id of the event on node (source id)
			$data[$i] .= html_print_input_hidden('source_id_' . $event["id_evento"], $event['id_source_event'], true);
			$table->cellclass[count($table->data)][$i] = $myclass;
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
			if ($meta) {
				if (can_user_access_node ()) {
					$data[$i] = "<a href='" . $event["server_url"] . "/index.php?sec=estado&sec2=operation/agentes/group_view" . $event['server_url_hash'] . "'>" . $event["server_name"] . "</a>";
				}
				else {
					$data[$i] = $event["server_name"];
				}
			}
			else {
				$data[$i] = db_get_value('name','tserver');
			}
			$table->cellclass[count($table->data)][$i] = $myclass;
			$i++;
		}
		if (in_array('estado',$show_fields)) {
			$data[$i] = html_print_image ($img_st, true, 
				array ("class" => "image_status",
					"title" => $title_st,
					"id" => 'status_img_'.$event["id_evento"]));
			$table->cellstyle[count($table->data)][$i] = 'background: #F3F3F3;';
			$i++;
		}
		if (in_array('id_evento',$show_fields)) {
			$data[$i] = $event["id_evento"];
			$table->cellclass[count($table->data)][$i] = $myclass;
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
			$event_with_formated_data = get_data_formated_in_event_name(io_safe_output($event["evento"]));
			
			// Event description
			$data[$i] = '<span title="'.$event["evento"].'" class="f9">';
			if($allow_action) {
				$data[$i] .= '<a href="javascript:" onclick="show_event_dialog(' . $event["id_evento"] . ', '.$group_rep.');">';
			}
			$data[$i] .= '<span class="'.$myclass.'" style="font-size: 7.5pt;">' . ui_print_truncate_text ($event_with_formated_data, 160) . '</span>';
			if($allow_action) {
				$data[$i] .= '</a>';
			}
			$data[$i] .= '</span>';
			$table->cellclass[count($table->data)][$i] = $myclass;
			$i++;
		}
		
		if (in_array('id_agente', $show_fields)) {
			$data[$i] = '<span class="'.$myclass.'">';
			
			if ($event["id_agente"] > 0) {
				// Agent name
				if ($meta) {
					$agent_link = '<a href="'.$event["server_url"].'/index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente=' . $event["id_agente"] . $event["server_url_hash"] . '">';
					if (can_user_access_node ()) {
						$data[$i] = '<b>' . $agent_link . $event["agent_name"] . '</a></b>';
					}
					else {
						$data[$i] = $event["agent_name"];
					}
				}
				else {
					$data[$i] .= ui_print_agent_name ($event["id_agente"], true);
				}
			}
			else {
				$data[$i] .= '';
			}
			$data[$i] .= '</span>';
			$table->cellclass[count($table->data)][$i] = $myclass;
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
			$table->cellclass[count($table->data)][$i] = $myclass;
			$i++;
		}
		
		if (in_array('id_usuario',$show_fields)) {
			$user_name = db_get_value('fullname', 'tusuario', 'id_user', $event['id_usuario']);
			if(empty($user_name)) {
				$user_name = $event['id_usuario'];
			}
			$data[$i] = $user_name;
			$table->cellclass[count($table->data)][$i] = $myclass;
			$i++;
		}
		
		if (in_array('owner_user',$show_fields)) {
			$owner_name = db_get_value('fullname', 'tusuario', 'id_user', $event['owner_user']);
			if(empty($owner_name)) {
				$owner_name = $event['owner_user'];
			}
			$data[$i] = $owner_name;
			$table->cellclass[count($table->data)][$i] = $myclass;
			$i++;
		}
		
		if (in_array('id_grupo',$show_fields)) {
			if ($meta) {
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
			$table->cellclass[count($table->data)][$i] = $myclass;
			$i++;
		}
		
		if (in_array('event_type',$show_fields)) {
			$data[$i] = events_print_type_description($event["event_type"], true);
			$table->cellclass[count($table->data)][$i] = $myclass;
			$i++;
		}
		
		if (in_array('id_agentmodule',$show_fields)) {
			if ($meta) {
				$module_link = '<a href="'.$event["server_url"].'/index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente=' . $event["id_agente"] . $event["server_url_hash"] . '">';
				if (can_user_access_node ()) {
					$data[$i] = '<b>' . $module_link . $event["module_name"] . '</a></b>';
				}
				else {
					$data[$i] = $event["module_name"];
				}
			}
			else {
				$module_name = db_get_value('nombre', 'tagente_modulo', 'id_agente_modulo', $event["id_agentmodule"]);
				$data[$i] = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$event["id_agente"].'&amp;status_text_monitor=' . io_safe_output($module_name) . '#monitors">'
					. $module_name . '</a>';
			}
			$table->cellclass[count($table->data)][$i] = $myclass;
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
			$table->cellclass[count($table->data)][$i] = $myclass;
			$i++;
		}
		
		if (in_array('criticity',$show_fields)) {
			$data[$i] = get_priority_name ($event["criticity"]);
			$table->cellclass[count($table->data)][$i] = $myclass;
			$i++;
		}
		
		if (in_array('user_comment',$show_fields)) {
			$safe_event_user_comment = strip_tags(io_safe_output($event["user_comment"]));
			$line_breaks = array("\r\n", "\n", "\r");
			$safe_event_user_comment = str_replace($line_breaks, '<br>', $safe_event_user_comment);
			$event_user_comments = json_decode($safe_event_user_comment, true);
			$event_user_comment_str = "";
			
			if (!empty($event_user_comments)) {
				$last_key = key(array_slice($event_user_comments, -1, 1, true));
				$date_format = $config['date_format'];
				
				foreach ($event_user_comments as $key => $event_user_comment) {
					$event_user_comment_str .= sprintf('%s: %s<br>%s: %s<br>%s: %s<br>',
						__('Date'), date($date_format, $event_user_comment['utimestamp']),
						__('User'), $event_user_comment['id_user'],
						__('Comment'), $event_user_comment['comment']);
					if ($key != $last_key) {
						$event_user_comment_str .= '<br>';
					}
				}
			}
			$comments_help_tip = "";
			if (!empty($event_user_comment_str)) {
				$comments_help_tip = ui_print_help_tip($event_user_comment_str, true);
			}
			
			$data[$i] = '<span id="comment_header_' . $event['id_evento'] . '">' . $comments_help_tip . '</span>';
			$table->cellclass[count($table->data)][$i] = $myclass;
			$i++;
		}
		
		if (in_array('tags',$show_fields)) {
			$data[$i] = tags_get_tags_formatted($event['tags']);
			$table->cellclass[count($table->data)][$i] = $myclass;
			$i++;
		}
		
		if (in_array('source',$show_fields)) {
			$data[$i] = $event["source"];
			$table->cellclass[count($table->data)][$i] = $myclass;
			$i++;
		}
		
		if (in_array('id_extra',$show_fields)) {
			$data[$i] = $event["id_extra"];
			$table->cellclass[count($table->data)][$i] = $myclass;
			$i++;
		}
		
		if (in_array('ack_utimestamp',$show_fields)) {
			if ($event["ack_utimestamp"] == 0) {
				$data[$i] = '';
			}
			else {
				$data[$i] = date ($config["date_format"], $event['ack_utimestamp']);
			}
			$table->cellclass[count($table->data)][$i] = $myclass;
			$i++;
		}
		
		if (in_array('instructions',$show_fields)) {
			switch($event['event_type']) {
				case 'going_unknown':
					if(!empty($event["unknown_instructions"])) {
						$data[$i] = html_print_image('images/page_white_text.png', true, array('title' => str_replace("\n","<br>", io_safe_output($event["unknown_instructions"]))));
					}
					break;
				case 'going_up_critical':
				case 'going_down_critical':
					if(!empty($event["critical_instructions"])) {
						$data[$i] = html_print_image('images/page_white_text.png', true, array('title' => str_replace("\n","<br>", io_safe_output($event["critical_instructions"]))));
					}
					break;
				case 'going_down_warning':
					if(!empty($event["warning_instructions"])) {
						$data[$i] = html_print_image('images/page_white_text.png', true, array('title' => str_replace("\n","<br>", io_safe_output($event["warning_instructions"]))));
					}
					break;
				case 'system':
					if(!empty($event["critical_instructions"])) {
						$data[$i] = html_print_image('images/page_white_text.png', true, array('title' => str_replace("\n","<br>", io_safe_output($event["critical_instructions"]))));
					}
					elseif(!empty($event["warning_instructions"])) {
						$data[$i] = html_print_image('images/page_white_text.png', true, array('title' => str_replace("\n","<br>", io_safe_output($event["warning_instructions"]))));
					}
					elseif(!empty($event["unknown_instructions"])) {
						$data[$i] = html_print_image('images/page_white_text.png', true, array('title' => str_replace("\n","<br>", io_safe_output($event["unknown_instructions"]))));
					}
					break;
			}
			
			if (!isset($data[$i])) {
				$data[$i] = '';
			}
			
			$table->cellclass[count($table->data)][$i] = $myclass;
			$i++;
		}
		
		if ($i != 0 && $allow_action) {
			//Actions
			$data[$i] = '';
			
			if(!$readonly) {
				// Validate event
				if (($event["estado"] != 1) && (tags_checks_event_acl ($config["id_user"], $event["id_grupo"], "EW", $event['clean_tags'], $childrens_ids))) {
					$show_validate_button = true;
					$data[$i] .= '<a href="javascript:validate_event_advanced('.$event["id_evento"].', 1)" id="validate-'.$event["id_evento"].'">';
					$data[$i] .= html_print_image ("images/ok.png", true,
						array ("title" => __('Validate event')));
					$data[$i] .= '</a>';
				}
				
				// Delete event
				if ((tags_checks_event_acl($config["id_user"], $event["id_grupo"], "EM", $event['clean_tags'],$childrens_ids) == 1)) {
					if($event['estado'] != 2) {
						$show_delete_button = true;
						$data[$i] .= '<a class="delete_event" href="javascript:" id="delete-'.$event['id_evento'].'">';
						$data[$i] .= html_print_image ("images/cross.png", true,
							array ("title" => __('Delete event'), "id" => 'delete_cross_' . $event['id_evento']));
						$data[$i] .= '</a>';
					}
					else {
						$data[$i] .= html_print_image ("images/cross.disabled.png", true,
							array ("title" => __('Is not allowed delete events in process'))).'&nbsp;';
					}
				}
			}
			
			$data[$i] .= '<a href="javascript:" onclick="show_event_dialog(' . $event["id_evento"] . ', '.$group_rep.');">';
			$data[$i] .= html_print_input_hidden('event_title_'.$event["id_evento"], "#".$event["id_evento"]." - ".$event["evento"], true);
			$data[$i] .= html_print_image ("images/eye.png", true,
				array ("title" => __('Show more')));
			$data[$i] .= '</a>';
			
			$table->cellstyle[count($table->data)][$i] = 'background: #F3F3F3;';
			
			$i++;
			
			if(!$readonly) {
				if (tags_checks_event_acl ($config["id_user"], $event["id_grupo"], "EM", $event['clean_tags'], $childrens_ids) == 1) {
					//Checkbox
					// Class 'candeleted' must be the fist class to be parsed from javascript. Dont change
					$data[$i] = html_print_checkbox_extended ("validate_ids[]", $event['id_evento'], false, false, false, 'class="candeleted chk_val"', true);
				}
				else if (tags_checks_event_acl ($config["id_user"], $event["id_grupo"], "EW", $event['clean_tags'], $childrens_ids) == 1) {
					//Checkbox
					$data[$i] = html_print_checkbox_extended ("validate_ids[]", $event['id_evento'], false, false, false, 'class="chk_val"', true);
				}
				else if (isset($table->header[$i]) || true) {
					$data[$i] = '';
				}
			}
				
			$table->cellstyle[count($table->data)][$i] = 'background: #F3F3F3;';
		}
		
		array_push ($table->data, $data);
		
		$idx++;
	}

	echo '<div id="events_list">';
	if (!empty ($table->data)) {
		
		if ($allow_pagination) {
			ui_pagination ($total_events, $url, $offset, $pagination);
		}
		
		if ($allow_action) {
			echo '<form method="post" id="form_events" action="'.$url.'">';
			echo "<input type='hidden' name='delete' id='hidden_delete_events' value='0' />";
		}
		
		if (defined("METACONSOLE"))
			echo '<div style="width: ' . $table->width . ';">';
		else
			echo '<div style="width: ' . $table->width . '; overflow-x: auto;">';
		html_print_table ($table);
		echo '</div>';
		
		if ($allow_action) {
			
			echo '<div style="width:' . $table->width . ';" class="action-buttons">';
			//~ if (!$readonly && tags_check_acl ($config["id_user"], 0, "EW", $event['clean_tags']) == 1) {
			if (!$readonly && $show_validate_button) {
				html_print_button(__('Validate selected'), 'validate_button', false, 'validate_selected();', 'class="sub ok"');
				// Fix: validated_selected JS function has to be included with the proper user ACLs 
				?>
				<script type="text/javascript">
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
			//~ if (!$readonly && tags_check_acl ($config["id_user"], 0,"EM", $event['clean_tags']) == 1) {
			if (!$readonly && ($show_delete_button)) {
				html_print_button(__('Delete selected'), 'delete_button', false, 'delete_selected();', 'class="sub delete"');
				?>
				<script type="text/javascript">
					function delete_selected() {
						if(confirm('<?php echo __('Are you sure?'); ?>')) {
							$("#hidden_delete_events").val(1);
							$("#form_events").submit();
						}
					}
				</script>
				<?php
			}
			echo '</div>';
			echo '</form>';
		}
	}
	else {
		echo '<div class="nf">' . __('No events') . '</div>';
	}
	echo '</div>';
}
?>
