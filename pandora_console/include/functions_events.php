<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

include_once($config['homedir'] . "/include/functions_ui.php");

/**
 * @package Include
 * @subpackage Events
 */

/** 
 * Get all rows of events from the database, that
 * pass the filter, and can get only some fields.
 * 
 * @param mixed Filters elements. It can be an indexed array
 * (keys would be the field name and value the expected value, and would be
 * joined with an AND operator) or a string, including any SQL clause (without
 * the WHERE keyword). Example:
 * <code>
 * Both are similars:
 * db_get_all_rows_filter ('table', array ('disabled', 0));
 * db_get_all_rows_filter ('table', 'disabled = 0');
 * 
 * Both are similars:
 * db_get_all_rows_filter ('table', array ('disabled' => 0, 'history_data' => 0), 'name', 'OR');
 * db_get_all_rows_filter ('table', 'disabled = 0 OR history_data = 0', 'name');
 * </code>
 * @param mixed Fields of the table to retrieve. Can be an array or a coma
 * separated string. All fields are retrieved by default
 * 
 * 
 * @return mixed False in case of error or invalid values passed. Affected rows otherwise
 */
function events_get_events ($filter = false, $fields = false) {
	return db_get_all_rows_filter ('tevento', $filter, $fields);
}

function events_get_event ($id, $fields = false) {
	if (empty ($id))
		return false;
	global $config;
	
	if (is_array ($fields)) {
		if (! in_array ('id_grupo', $fields))
			$fields[] = 'id_grupo';
	}
	
	$event = db_get_row ('tevento', 'id_evento', $id, $fields);
	if (! check_acl ($config['id_user'], $event['id_grupo'], 'IR'))
		return false;
	return $event;
}

/**
 * Get all the events ids similar to a given event id.
 *
 * An event is similar then the event text (evento) and the id_agentmodule are
 * the same.
 *
 * @param int Event id to get similar events.
 *
 * @return array A list of events ids.
 */
function events_get_similar_ids ($id) {
	$ids = array ();
	$event = events_get_event ($id, array ('evento', 'id_agentmodule'));
	if ($event === false)
		return $ids;
	
	$events = db_get_all_rows_filter ('tevento',
		array ('evento' => $event['evento'],
			'id_agentmodule' => $event['id_agentmodule']),
		array ('id_evento'));
	if ($events === false)
		return $ids;
	
	foreach ($events as $event)
		$ids[] = $event['id_evento'];
	
	return $ids;
}

/**
 * Delete events in a transaction
 *
 * @param mixed Event ID or array of events
 * @param bool Whether to delete similar events too.
 *
 * @return bool Whether or not it was successful
 */
function events_delete_event ($id_event, $similar = true) {
	global $config;
	
	//Cleans up the selection for all unwanted values also casts any single values as an array 
	$id_event = (array) safe_int ($id_event, 1);
	
	/* We must delete all events like the selected */
	if ($similar) {
		foreach ($id_event as $id) {
			$id_event = array_merge ($id_event, events_get_similar_ids ($id));
		}
		$id_event = array_unique($id_event);
	}
	
	db_process_sql_begin ();
	$errors = 0;
	
	foreach ($id_event as $event) {
		$ret = db_process_sql_delete('tevento', array('id_evento' => $event));
		
		if (check_acl ($config["id_user"], events_get_group ($event), "IM") == 0) {
			//Check ACL
			db_pandora_audit("ACL Violation", "Attempted deleting event #".$event);
		}
		elseif ($ret !== false) {
			db_pandora_audit("Event deleted", "Deleted event #".$event);
			//ACL didn't fail nor did return
			continue;

		}
		
		$errors++;
		break;
	}
	
	if ($errors > 1) {
		db_process_sql_rollback ();
		return false;
	} else {
		db_process_sql_commit ();
		return true;
	}
}

/**
 * Validate events in a transaction
 *
 * @param mixed Event ID or array of events
 * @param bool Whether to validate similar events or not.
 *
 * @return bool Whether or not it was successful
 */	
function events_validate_event ($id_event, $similars = true, $new_status = 1) {
	global $config;
	
	//Cleans up the selection for all unwanted values also casts any single values as an array 
	$id_event = (array) safe_int ($id_event, 1);
	
	if($new_status) {
		$ack_utimestamp = time();
		$ack_user = $config['id_user'];
	}
	else {
		$acl_utimestamp = 0;
		$ack_user = '';
	}
	
	/* We must validate all events like the selected */
	if ($similars && $new_status == 1) {
		foreach ($id_event as $id) {
			$id_event = array_merge ($id_event, events_get_similar_ids ($id));
		}
		$id_event = array_unique($id_event);
	}
	
	switch($new_status) {
		case 0:
			$status_string = 'New';
			break;
		case 1:
			$status_string = 'Validated';
			break;
		case 2:
			$status_string = 'In process';
			break;
		default:
			$status_string = '';
			break;
	}
	
	events_comment_event($id_event, $similars, '', "Change status to $status_string");
	
	db_process_sql_begin ();
	
	foreach ($id_event as $event) {
		if (check_acl ($config["id_user"], events_get_group ($event), "IW") == 0) {
			db_pandora_audit("ACL Violation", "Attempted updating event #".$event);
			
			return false;
		}
		
		$values = array(
			'estado' => $new_status,
			'id_usuario' => $ack_user,
			'ack_utimestamp' => $ack_utimestamp);
			
		$ret = db_process_sql_update('tevento', $values,
			array('id_evento' => $event), 'AND', false);
		
		if (($ret === false) || ($ret === 0)) {
			db_process_sql_rollback ();
			return false;
		}
	}
	
	db_process_sql_commit ();
	
	return true;
}

/**
 * Change the owner of an event if the event hasn't owner
 *
 * @param mixed Event ID or array of events
 * @param bool Whether to change owner on similar events or not.
 * @param string id_user of the new owner. If is false, the current owner will be setted
 * @param bool flag to force the change or not (not force is change only when it hasn't owner)
 *
 * @return bool Whether or not it was successful
 */	
function events_change_owner_event ($id_event, $similars = true, $new_owner = false, $force = false) {
	global $config;
	
	//Cleans up the selection for all unwanted values also casts any single values as an array 
	$id_event = (array) safe_int ($id_event, 1);

	/* We must validate all events like the selected */
	if ($similars) {
		foreach ($id_event as $id) {
			$id_event = array_merge ($id_event, events_get_similar_ids ($id));
		}
		$id_event = array_unique($id_event);
	}
	
	// Only generate comment when is forced (sometimes is changed the owner when comment)
	if($force) {
		events_comment_event($event, $similars, '', 'Change owner');
	}
	
	if($new_owner === false) {
		$new_owner = $config['id_user'];
	}
		
	db_process_sql_begin ();
	
	foreach ($id_event as $event) {
		if (check_acl ($config["id_user"], events_get_group ($event), "IW") == 0) {
			db_pandora_audit("ACL Violation", "Attempted updating event #".$event);
			return false;
		}
		
		if($owner) {
			$owner_user = db_get_value('owner_user', 'tevento', 'id_evento', $event);
		}
		
		if(!empty($owner_user) && $force === false) {
			continue;
		}
				
		$values = array('owner_user' => $new_owner);

		$ret = db_process_sql_update('tevento', $values,
			array('id_evento' => $event), 'AND', false);
		
		if (($ret === false) || ($ret === 0)) {
			db_process_sql_rollback ();
			return false;
		}
	}
	
	db_process_sql_commit ();
	
	return true;
}

/**
 * Comment events in a transaction
 *
 * @param mixed Event ID or array of events
 * @param bool Whether to validate similar events or not.
 *
 * @return bool Whether or not it was successful
 */	
function events_comment_event ($id_event, $similars = true, $comment = '', $action = 'Added comment') {
	global $config;

	//Cleans up the selection for all unwanted values also casts any single values as an array 
	$id_event = (array) safe_int ($id_event, 1);
	
	/* We must validate all events like the selected */
	if ($similars) {
		foreach ($id_event as $id) {
			$id_event = array_merge ($id_event, events_get_similar_ids ($id));
		}
		$id_event = array_unique($id_event);
	}
	
	// If the event hasn't owner, assign the user as owner
	events_change_owner_event ($id_event, $similars);
	
	db_process_sql_begin ();
		
	$comment = str_replace(array("\r\n", "\r", "\n"), '<br>', $comment);
	
	if ($comment != '') {
		$commentbox = '<div style="border:1px dotted #CCC; min-height: 10px;">'.$comment.'</div>';
	}
	else {
		$commentbox = '';
	}
	
	foreach ($id_event as $event) {
		if (check_acl ($config["id_user"], events_get_group ($event), "IW") == 0) {
			db_pandora_audit("ACL Violation", "Attempted updating event #".$event);
			
			return false;
		}
		
		$comment = '<b>-- '.$action.' by '.$config['id_user'].' '.'['.date ($config["date_format"]).'] --</b><br>'.$commentbox;
		$fullevent = events_get_event($event);
		if ($fullevent['user_comment'] != '') {
			$comment .= '<br>'.$fullevent['user_comment'];
		}
		
		$values = array(
			'id_usuario' => $config['id_user'],
			'user_comment' => $comment);
		
		$ret = db_process_sql_update('tevento', $values,
			array('id_evento' => $event), 'AND', false);
		
		if (($ret === false) || ($ret === 0)) {
			db_process_sql_rollback ();
			return false;
		}
	}
	
/*
	foreach ($id_event as $event) {
		db_pandora_audit("Event validated", "Validated event #".$event);
	}
*/
	db_process_sql_commit ();
	
	return true;
}

/** 
 * Get group id of an event.
 * 
 * @param int $id_event Event id
 * 
 * @return int Group id of the given event.
 */
function events_get_group ($id_event) {
	return (int) db_get_value ('id_grupo', 'tevento', 'id_evento', (int) $id_event);
}

/** 
 * Get description of an event.
 * 
 * @param int $id_event Event id.
 * 
 * @return string Description of the given event.
 */
function events_get_description ($id_event) {
	return (string) db_get_value ('evento', 'tevento', 'id_evento', (int) $id_event);
}

/** 
 * Insert a event in the event log system.
 * 
 * @param int $event 
 * @param int $id_group 
 * @param int $id_agent 
 * @param int $status 
 * @param string $id_user 
 * @param string $event_type 
 * @param int $priority 
 * @param int $id_agent_module 
 * @param int $id_aam 
 *
 * @return int event id
 */
function events_create_event ($event, $id_group, $id_agent, $status = 0, $id_user = "", $event_type = "unknown", $priority = 0, $id_agent_module = 0, $id_aam = 0, $critical_instructions = '', $warning_instructions = '', $unknown_instructions = '') {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			$sql = sprintf ('INSERT INTO tevento (id_agente, id_grupo, evento, timestamp, 
				estado, utimestamp, id_usuario, event_type, criticity,
				id_agentmodule, id_alert_am, critical_instructions, warning_instructions, unknown_instructions) 
				VALUES (%d, %d, "%s", NOW(), %d, UNIX_TIMESTAMP(NOW()), "%s", "%s", %d, %d, %d)',
				$id_agent, $id_group, $event, $status, $id_user, $event_type,
				$priority, $id_agent_module, $id_aam, $critical_instructions, $warning_instructions, $unknown_instructions);
			break;
		case "postgresql":
			$sql = sprintf ('INSERT INTO tevento (id_agente, id_grupo, evento, timestamp, 
				estado, utimestamp, id_usuario, event_type, criticity,
				id_agentmodule, id_alert_am) 
				VALUES (%d, %d, "%s", NOW(), %d, ceil(date_part(\'epoch\', CURRENT_TIMESTAMP)), "%s", "%s", %d, %d, %d)',
				$id_agent, $id_group, $event, $status, $id_user, $event_type,
				$priority, $id_agent_module, $id_aam);
			break;
		case "oracle":
			$sql = sprintf ('INSERT INTO tevento (id_agente, id_grupo, evento, timestamp, 
				estado, utimestamp, id_usuario, event_type, criticity,
				id_agentmodule, id_alert_am) 
				VALUES (%d, %d, "%s", CURRENT_TIMESTAMP, %d, ceil((sysdate - to_date(\'19700101000000\',\'YYYYMMDDHH24MISS\')) * (86400)), "%s", "%s", %d, %d, %d)',
				$id_agent, $id_group, $event, $status, $id_user, $event_type,
				$priority, $id_agent_module, $id_aam);
			break;
	}
	
	return (int) db_process_sql ($sql, "insert_id");
}

/** 
 * Prints a small event table
 * 
 * @param string $filter SQL WHERE clause 
 * @param int $limit How many events to show
 * @param int $width How wide the table should be 
 * @param bool $return Prints out HTML if false
 * 
 * @return string HTML with table element 
 */
function events_print_event_table ($filter = "", $limit = 10, $width = 440, $return = false) {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":
			$sql = sprintf ("SELECT * FROM tevento %s ORDER BY timestamp DESC LIMIT %d", $filter, $limit);
			break;
		case "oracle":
			if ($filter == "") {
				$sql = sprintf ("SELECT * FROM tevento WHERE rownum <= %d ORDER BY timestamp DESC", $limit);
			}	
			else {
				$sql = sprintf ("SELECT * FROM tevento %s AND rownum <= %d ORDER BY timestamp DESC", $filter, $limit);
			}		
			break;
	}
	$result = db_get_all_rows_sql ($sql);
	
	if ($result === false) {
		echo '<div class="nf">'.__('No events').'</div>';
	} else {
		$table->cellpadding = 4;
		$table->cellspacing = 4;
		$table->width = $width;
		$table->class = "databox";
		$table->title = __('Latest events');
		$table->headclass = array ();
		$table->head = array ();
		$table->rowclass = array ();
		$table->data = array ();
		$table->align = array ();
		
		$table->head[0] = "<span title='" . __('Validate') . "'>" . __('V.') . "</span>";
		$table->align[0] = 'center';
		
		$table->head[1] = "<span title='" . __('Severity') . "'>" . __('S.') . "</span>";
		$table->align[1] = 'center';
		
		$table->head[2] = __('Type');
		$table->headclass[2] = "datos3 f9";
		$table->align[2] = "center";
		
		$table->head[3] = __('Event name');
		
		$table->head[4] = __('Agent name');
				
		$table->head[5] = __('Timestamp');
		$table->headclass[5] = "datos3 f9";
		$table->align[5] = "right";
		
		foreach ($result as $event) {
			if (! check_acl ($config["id_user"], $event["id_grupo"], "AR")) {
				continue;
			}
			$data = array ();
			
			// Colored box
			switch($event["estado"]) {
				case 0:
					$img = "images/star.png";
					$title = __('New event');
					break;
				case 1:
					$img = "images/tick.png";
					$title = __('Event validated');
					break;
				case 2:
					$img = "images/hourglass.png";
					$title = __('Event in process');
					break;
			}
	
			$data[0] = html_print_image ($img, true, 
				array ("class" => "image_status",
					"width" => 16,
					"height" => 16,
					"title" => $title));
			
			switch ($event["criticity"]) {
				default:
				case 0: 
					$img = "images/status_sets/default/severity_maintenance.png";
					break;
				case 1:
					$img = "images/status_sets/default/severity_informational.png";
					break;
				case 2:
					$img = "images/status_sets/default/severity_normal.png";
					break;
				case 3:
					$img = "images/status_sets/default/severity_warning.png";
					break;
				case 4:
					$img = "images/status_sets/default/severity_critical.png";
					break;
			}
			
			$data[1] = html_print_image ($img, true, 
				array ("class" => "image_status",
					"width" => 12,
					"height" => 12,
					"title" => get_priority_name ($event["criticity"])));
			
			/* Event type */
			$data[2] = events_print_type_img ($event["event_type"], true);
					
			$data[3] = ui_print_string_substr (io_safe_output($event["evento"]), 75, true, '9');

			if ($event["id_agente"] > 0) {
				// Agent name
				// Get class name, for the link color...
				$myclass =  get_priority_class ($event["criticity"]);
				
				$data[4] = "<a class='$myclass' href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=".$event["id_agente"]."'>".agents_get_name ($event["id_agente"]). "</A>";
				
//				ui_print_agent_name ($event["id_agente"], true, 25, '', true);
			// for System or SNMP generated alerts
			}
			elseif ($event["event_type"] == "system") {
				$data[4] = __('System');
			}
			else {
				$data[4] = __('Alert')."SNMP";
			}
						
			// Timestamp
			$data[5] = ui_print_timestamp ($event["timestamp"], true, array('style' => 'font-size: 7px'));
			
			array_push ($table->rowclass, get_priority_class ($event["criticity"]));
			array_push ($table->data, $data);
		}
		
		$return = html_print_table ($table, $return);
		unset ($table);
		return $return;
	}
}


/** 
 * Prints the event type image
 * 
 * @param string $type Event type from SQL 
 * @param bool $return Whether to return or print
 * @param bool $only_url Flag to return only url of image, by default false.
 * 
 * @return string HTML with img 
 */
function events_print_type_img ($type, $return = false, $only_url = false) {
	global $config;
	
	$output = '';
	
	$urlImage = ui_get_full_url(false);
	
	switch ($type) {
		case "alert_recovered":
			if ($only_url) {
				$output = $urlImage . "/" . "images/error.png";
			}
			else {
				$output .= html_print_image ("images/error.png", true,
					array ("title" => events_print_type_description($type, true)));
			}
			break;
		case "alert_manual_validation":
			if ($only_url) {
				$output = $urlImage . "/" . "images/eye.png";
			}
			else {
				$output .= html_print_image ("images/eye.png", true,
					array ("title" => events_print_type_description($type, true)));
			}
			break;
		case "going_up_warning":
			if ($only_url) {
				$output = $urlImage . "/" . "images/b_yellow.png";
			}
			else {
				$output .= html_print_image ("images/b_yellow.png", true,
					array ("title" => events_print_type_description($type, true)));
			}
			break;
		case "going_down_critical":
		case "going_up_critical": //This is to be backwards compatible
			if ($only_url) {
				$output = $urlImage . "/" . "images/b_red.png";
			}
			else {
				$output .= html_print_image ("images/b_red.png", true,
					array ("title" => events_print_type_description($type, true)));
			}
			break;
		case "going_up_normal":
		case "going_down_normal": //This is to be backwards compatible
			if ($only_url) {
				$output = $urlImage . "/" . "images/b_green.png";
			}
			else {
				$output .= html_print_image ("images/b_green.png", true,
					array ("title" => events_print_type_description($type, true)));
			}
			break;
		case "going_down_warning":
			if ($only_url) {
				$output = $urlImage . "/" . "images/b_yellow.png";
			}
			else {
				$output .= html_print_image ("images/b_yellow.png", true,
					array ("title" => events_print_type_description($type, true)));
			}
			break;
		case "going_unknown":
			if ($only_url) {
				$output = $urlImage . "/" . "images/b_blue.png";
			}
			else {
				$output .= html_print_image ("images/b_blue.png", true,
					array ("title" => events_print_type_description($type, true)));
			}
			break;
		case "alert_fired":
			if ($only_url) {
				$output = $urlImage . "/" . "images/bell.png";
			}
			else { 
				$output .= html_print_image ("images/bell.png", true,
					array ("title" => events_print_type_description($type, true)));
			}
			break;
		case "system":
			if ($only_url) {
				$output = $urlImage . "/" . "images/cog.png";
			}
			else {
				$output .= html_print_image ("images/cog.png", true,
					array ("title" => events_print_type_description($type, true)));
			}
			break;
		case "recon_host_detected":
			if ($only_url) {
				$output = $urlImage . "/" . "images/network.png";
			}
			else {
				$output .= html_print_image ("images/network.png", true,
					array ("title" => events_print_type_description($type, true)));
			}
			break;
		case "new_agent":
			if ($only_url) {
				$output = $urlImage . "/" . "images/wand.png";
			}
			else {
				$output .= html_print_image ("images/wand.png", true,
					array ("title" => events_print_type_description($type, true)));
			}
			break;
		case "unknown": 
		default:
			if ($only_url) {
				$output = $urlImage . "/" . "images/err.png";
			}
			else {
				$output .= html_print_image ("images/err.png", true,
					array ("title" => events_print_type_description($type, true)));
			}
			break;
	}
	
	if ($return)
		return $output;
	echo $output;
}

/** 
 * Prints the event type description
 * 
 * @param string $type Event type from SQL 
 * @param bool $return Whether to return or print
 * 
 * @return string HTML with img 
 */
function events_print_type_description ($type, $return = false) {
	$output = '';
	
	switch ($type) {
	case "going_unknown": 
		$output .= __('Going to unknown');
		break;
	case "alert_recovered": 
		$output .= __('Alert recovered');
		break;
	case "alert_manual_validation": 
		$output .= __('Alert manually validated');
		break;
	case "going_up_warning":
		$output .= __('Going from critical to warning');
		break;
	case "going_down_critical":
	case "going_up_critical": //This is to be backwards compatible
		$output .= __('Going down to critical state');
		break;
	case "going_up_normal":
	case "going_down_normal": //This is to be backwards compatible
		$output .= __('Going up to normal state');
		break;
	case "going_down_warning":
		$output .= __('Going down from normal to warning');
		break;
	case "alert_fired":
		$output .= __('Alert fired');
		break;
	case "system";
		$output .= __('SYSTEM');
		break;
	case "recon_host_detected";
		$output .= __('Recon server detected a new host');
		break;
	case "new_agent";
		$output .= __('New agent created');
		break;
	case "configuration_change";
		$output .= __('Configuration change');
		break;
	case "alert_ceased";
		$output .= __('Alert ceased');
		break;
	case "error";
		$output .= __('Error');
		break;
	case "unknown": 
	default:
		$output .= __('Unknown type:').': '.$type;
		break;
	}
	
	if ($return)
		return $output;
	echo $output;
}

/**
 * Get all the events happened in a group during a period of time.
 *
 * The returned events will be in the time interval ($date - $period, $date]
 *
 * @param mixed $id_group Group id to get events for.
 * @param int $period Period of time in seconds to get events.
 * @param int $date Beginning date to get events.
 *
 * @return array An array with all the events happened.
 */
function events_get_group_events ($id_group, $period, $date) {
	global $config;

	$id_group = groups_safe_acl ($config["id_user"], $id_group, "AR");

	if (empty ($id_group)) {
		//An empty array means the user doesn't have access
		return false;
	}

	$datelimit = $date - $period;

	$sql = sprintf ('SELECT * FROM tevento
		WHERE utimestamp > %d AND utimestamp <= %d
		AND id_grupo IN (%s)
		ORDER BY utimestamp ASC',
		$datelimit, $date, implode (",", $id_group));

	return db_get_all_rows_sql ($sql);
}

/**
 * Get all the events happened in an Agent during a period of time.
 *
 * The returned events will be in the time interval ($date - $period, $date]
 *
 * @param int $id_agent Agent id to get events.
 * @param int $period Period of time in seconds to get events.
 * @param int $date Beginning date to get events.
 *
 * @return array An array with all the events happened.
 */
function events_get_agent ($id_agent, $period, $date = 0) {
	if (!is_numeric ($date)) {
		$date = strtotime ($date);
	}
	if (empty ($date)) {
		$date = get_system_time ();
	}

	$datelimit = $date - $period;

	$sql = sprintf ('SELECT evento, event_type, criticity, count(*) as count_rep,
			max(timestamp) AS time2
		FROM tevento
		WHERE id_agente = %d AND utimestamp > %d AND utimestamp <= %d 
		GROUP BY id_agentmodule, evento
		ORDER BY time2 DESC', $id_agent, $datelimit, $date);

	return db_get_all_rows_sql ($sql);
}

/**
 * Get all the events happened in an Agent during a period of time.
 *
 * The returned events will be in the time interval ($date - $period, $date]
 *
 * @param int $id_agent_module Module id to get events.
 * @param int $period Period of time in seconds to get events.
 * @param int $date Beginning date to get events.
 *
 * @return array An array with all the events happened.
 */
function events_get_module ($id_agent_module, $period, $date = 0) {
	if (!is_numeric ($date)) {
		$date = strtotime ($date);
	}
	if (empty ($date)) {
		$date = get_system_time ();
	}

	$datelimit = $date - $period;

	$sql = sprintf ('SELECT evento, event_type, criticity, count(*) as count_rep, max(timestamp) AS time2
		FROM tevento
		WHERE id_agentmodule = %d AND utimestamp > %d AND utimestamp <= %d 
		GROUP BY id_agentmodule, evento ORDER BY time2 DESC', $id_agent_module, $datelimit, $date);

	return db_get_all_rows_sql ($sql);
}

/**
 * Decode a numeric type into type description.
 *
 * @param int $type_id Numeric type.
 *
 * @return string Type description.
 */
function events_get_event_types ($type_id){
	
	$diferent_types = get_event_types ();

	$type_desc = '';
	switch($type_id) {
		case 'unknown': $type_desc = __('Unknown');
				break;
		case 'critical': $type_desc = __('Monitor Critical');
				break;
		case 'warning': $type_desc = __('Monitor Warning');
				break;
		case 'normal': $type_desc = __('Monitor Normal');
				break;
		case 'alert_fired': $type_desc = __('Alert fired');
				break;	
		case 'alert_recovered': $type_desc = __('Alert recovered');
				break;	
		case 'alert_ceased': $type_desc = __('Alert ceased');
				break;
		case 'alert_manual_validation': $type_desc = __('Alert manual validation');
				break;
		case 'recon_host_detected': $type_desc = __('Recon host detected');
				break;
		case 'system': $type_desc = __('System');
				break;
		case 'error': $type_desc = __('Error');
				break;
		case 'configuration_change': $type_desc = __('Configuration change');
				break;
		case 'not_normal': $type_desc = __('Not normal');
				break;
		default:
				if (isset($config['text_char_long'])) {
					foreach ($diferent_types as $key => $type) {
						if ($key == $type_id){
							$type_desc = ui_print_truncate_text($type, $config['text_char_long'], false, true, false);
						}
					}
				}
				break;
	}
		
	return $type_desc;	
} 


/**
 * Decode a numeric severity into severity description.
 *
 * @param int $severity_id Numeric severity.
 *
 * @return string Severity description.
 */
function events_get_severity_types ($severity_id){
	
	$diferent_types = get_priorities ();

	$severity_desc = '';
	switch ($severity_id) {
		case 0: $severity_desc = __('Maintenance');
				break;
		case 1: $severity_desc = __('Informational');
				break;
		case 2: $severity_desc = __('Normal');
				break;
		case 3: $severity_desc = __('Warning');
				break;
		case 4: $severity_desc = __('Critical');
				break;	
		default:
				if (isset($config['text_char_long'])) {
					foreach ($diferent_types as $key => $type) {
						if ($key == $severity_id){
							$severity_desc = ui_print_truncate_text($type, $config['text_char_long'], false, true, false);
						}
					}
				}
				break;
	}
		
	return $severity_desc;	
} 

/**
 * Return all descriptions of event status.
 *
 * @return array Status description array.
 */
function events_get_all_status (){
	$fields = array ();
	$fields[-1] = __('All event');
	$fields[0] = __('Only new');
	$fields[1] = __('Only validated');
	$fields[2] = __('Only in process');
	$fields[3] = __('Only not validated');	
	
	return $fields;
} 

/**
 * Decode a numeric status into status description.
 *
 * @param int $status_id Numeric status.
 *
 * @return string Status description.
 */
function events_get_status ($status_id){
	switch($status_id) {
		case -1: $status_desc = __('All event');
				break;
		case 0: $status_desc = __('Only new');
				break;
		case 1: $status_desc = __('Only validated');
				break;
		case 2: $status_desc = __('Only in process');
				break;
		case 3: $status_desc = __('Only not validated');
				break;
	}
	
	return $status_desc;
}

/**
 * Checks if a user has permissions to see an event filter.
 *
 * @param int $id_filter Id of the event filter.
 *
 * @return bool True if the user has permissions or false otherwise.
 */
function events_check_event_filter_group ($id_filter) {
	global $config;
	
	$id_group = db_get_value('id_group', 'tevent_filter', 'id_filter', $id_filter);	
	$own_info = get_user_info ($config['id_user']);
	// Get group list that user has access
	$groups_user = users_get_groups ($config['id_user'], "IW", $own_info['is_admin'], true);
	$groups_id = array();
	$has_permission = false;
	
	foreach($groups_user as $key => $groups){
		if ($groups['id_grupo'] == $id_group)
			return true;
	}
	
	return false;
}

/**
 *  Get a event filter.
 * 
 * @param int Filter id to be fetched.
 * @param array Extra filter.
 * @param array Fields to be fetched.
 *
 * @return array A event filter matching id and filter or false.
 */
function events_get_event_filter ($id_filter, $filter = false, $fields = false) {

		if (empty($id_filter)){
			return false;
		}

		if (! is_array ($filter)){
			$filter = array ();
			$filter['id_filter'] = (int) $id_filter;
		}
	
		return db_get_row_filter ('tevent_filter', $filter, $fields);
}

/**
 *  Get a event filters in select format.
 *
 * @return array A event filter matching id and filter or false.
 */
function events_get_event_filter_select(){
	global $config;
	
	$user_groups = users_get_groups ($config['id_user'], "AW", true, true);
	$sql = "SELECT id_filter, id_name FROM tevent_filter WHERE id_group IN (".implode(',', array_keys ($user_groups)).")";

	$event_filters = db_get_all_rows_sql($sql);

	if ($event_filters === false){
		return array();
	}
	else{
		$result = array();
		foreach ($event_filters as $event_filter){
			$result[$event_filter['id_filter']] = $event_filter['id_name'];
		}
	}
	
	return $result;
}

?>
