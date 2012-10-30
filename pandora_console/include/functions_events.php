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

function events_get_events_grouped($sql_post, $offset = 0, $pagination = 1) {
	global $config; 
	
	switch ($config["dbtype"]) {
		case "mysql":
			db_process_sql ('SET group_concat_max_len = 9999999');
			$sql = "SELECT *, MAX(id_evento) AS id_evento,
					GROUP_CONCAT(DISTINCT user_comment SEPARATOR '<br>') AS user_comment,
					GROUP_CONCAT(DISTINCT id_evento SEPARATOR ',') AS similar_ids,
					COUNT(*) AS event_rep, MAX(utimestamp) AS timestamp_rep, 
					MIN(utimestamp) AS timestamp_rep_min
				FROM tevento
				WHERE 1=1 ".$sql_post."
				GROUP BY estado, evento, id_agentmodule
				ORDER BY timestamp_rep DESC LIMIT ".$offset.",".$pagination;
			break;
		case "postgresql":
			$sql = "SELECT *, MAX(id_evento) AS id_evento, array_to_string(array_agg(DISTINCT user_comment), '<br>') AS user_comment,
					array_to_string(array_agg(DISTINCT id_evento), ',') AS similar_ids,
					COUNT(*) AS event_rep, MAX(utimestamp) AS timestamp_rep, 
					MIN(utimestamp) AS timestamp_rep_min
				FROM tevento
				WHERE 1=1 ".$sql_post."
				GROUP BY estado, evento, id_agentmodule, id_evento, id_agente, id_usuario, id_grupo, estado, timestamp, utimestamp, event_type, id_alert_am, criticity, user_comment, tags, source, id_extra
				ORDER BY timestamp_rep DESC LIMIT ".$pagination." OFFSET ".$offset;
			break;
		case "oracle":
			$set = array();
			$set['limit'] = $pagination;
			$set['offset'] = $offset;
			// TODO: Remove duplicate user comments
			$sql = "SELECT a.*, b.event_rep, b.timestamp_rep
				FROM (SELECT * FROM tevento WHERE 1=1 ".$sql_post.") a, 
				(SELECT MAX (id_evento) AS id_evento,  to_char(evento) AS evento, 
				id_agentmodule, COUNT(*) AS event_rep,
				LISTAGG(user_comment, '') AS user_comment, MAX(utimestamp) AS timestamp_rep, 
				LISTAGG(id_evento, '') AS similar_ids,
				MIN(utimestamp) AS timestamp_rep_min
				FROM tevento 
				WHERE 1=1 ".$sql_post." 
				GROUP BY estado, to_char(evento), id_agentmodule) b 
				WHERE a.id_evento=b.id_evento AND 
				to_char(a.evento)=to_char(b.evento) 
				AND a.id_agentmodule=b.id_agentmodule";
			$sql = oracle_recode_query ($sql, $set);
			break;
	}
	
	//Extract the events by filter (or not) from db
	$events = db_get_all_rows_sql ($sql);
	
	return $events;
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
 * Delete events in a transresponse
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
		return false;
	} else {
		return true;
	}
}

/**
 * Validate events in a transresponse
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
	
	events_comment($id_event, '', "Change status to $status_string");
	
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
 * Change the status of one or various events
 *
 * @param mixed Event ID or array of events
 * @param bool Whether to change owner on similar events or not.
 * @param string id_user of the new owner. If is false, the current owner will be setted
 * @param bool flag to force the change or not (not force is change only when it hasn't owner)
 *
 * @return bool Whether or not it was successful
 */	
function events_change_status ($id_event, $new_status) { 
	global $config;
	
	//Cleans up the selection for all unwanted values also casts any single values as an array 
	$id_event = (array) safe_int ($id_event, 1);
	
	// Update ack info if the new status is validated
	if($new_status == EVENT_STATUS_VALIDATED) {
		$ack_utimestamp = time();
		$ack_user = $config['id_user'];
	}
	else {
		$acl_utimestamp = 0;
		$ack_user = '';
	}
	
	switch($new_status) {
		case EVENT_STATUS_NEW:
			$status_string = 'New';
			break;
		case EVENT_STATUS_VALIDATED:
			$status_string = 'Validated';
			break;
		case EVENT_STATUS_INPROCESS:
			$status_string = 'In process';
			break;
		default:
			$status_string = '';
			break;
	}
		
	foreach ($id_event as $k => $id) {
		if (check_acl ($config["id_user"], events_get_group ($id), "IW") == 0) {
			db_pandora_audit("ACL Violation", "Attempted updating event #".$id);
			
			unset($id_event[$k]);
		}
	}
	
	if(empty($id_event)) {
		return false;
	}
	
	$values = array(
		'estado' => $new_status,
		'id_usuario' => $ack_user,
		'ack_utimestamp' => $ack_utimestamp);
		
	$ret = db_process_sql_update('tevento', $values,
		array('id_evento' => $id_event));
	
	if (($ret === false) || ($ret === 0)) {
		return false;
	}
	
	events_comment($id_event, '', "Change status to $status_string");
	
	return true;
}

/**
 * Change the owner of an event if the event hasn't owner
 *
 * @param mixed Event ID or array of events
 * @param string id_user of the new owner. If is false, the current owner will be setted
 * @param bool flag to force the change or not (not force is change only when it hasn't owner)
 *
 * @return bool Whether or not it was successful
 */	
function events_change_owner ($id_event, $new_owner = false, $force = false) {
	global $config;
	
	//Cleans up the selection for all unwanted values also casts any single values as an array 
	$id_event = (array) safe_int ($id_event, 1);
			
	foreach ($id_event as $k => $id) {
		if (check_acl ($config["id_user"], events_get_group ($id), "IW") == 0) {
			db_pandora_audit("ACL Violation", "Attempted updating event #".$id);
			unset($id_event[$k]);
		}
	}
	
	if(empty($id_event)) {
		return false;
	}
	
	// Only generate comment when is forced (sometimes is changed the owner when comment)
	if($force) {
		events_comment($id_event, '', 'Change owner');
	}
	
	// If no new_owner is provided, the current user will be the owner
	if($new_owner === false) {
		$new_owner = $config['id_user'];
	}
	
	$values = array('owner_user' => $new_owner);

	$where = array('id_evento' => $id_event);
	
	// If not force, add to where if owner_user <> ''
	if(!$force) {
		$where['owner_user'] = '<>';
	}
	
	$ret = db_process_sql_update('tevento', $values,
		$where, 'AND', false);
	
	if (($ret === false) || ($ret === 0)) {
		return false;
	}
		
	return true;
}

/**
 * Comment events in a transresponse
 *
 * @param mixed Event ID or array of events
 * @param string comment to be registered
 * @param string action performed with the comment. Bu default just Added comment
 *
 * @return bool Whether or not it was successful
 */	
function events_comment ($id_event, $comment = '', $action = 'Added comment') {
	global $config;

	//Cleans up the selection for all unwanted values also casts any single values as an array 
	$id_event = (array) safe_int ($id_event, 1);
	
	foreach ($id_event as $k => $id) {
		if (check_acl ($config["id_user"], events_get_group ($id), "IW") == 0) {
			db_pandora_audit("ACL Violation", "Attempted updating event #".$id);
			
			unset($id_event[$k]);
		}
	}
	
	if(empty($id_event)) {
		return false;
	}
	
	// If the event hasn't owner, assign the user as owner
	events_change_owner ($id_event, $similars);

	// Give old ugly format to comment. TODO: Change this method for aux table or json
	$comment = str_replace(array("\r\n", "\r", "\n"), '<br>', $comment);
	
	if ($comment != '') {
		$commentbox = '<div style="border:1px dotted #CCC; min-height: 10px;">'.$comment.'</div>';
	}
	else {
		$commentbox = '';
	}
	
	$comment = '<b>-- ' . $action . ' by '.$config['id_user'].' '.'['.date ($config["date_format"]).'] --</b><br>'.$commentbox.'<br>';
	
	// Update comment
	switch ($config['dbtype']) {
		// Oldstyle SQL to avoid innecesary PHP foreach
		case 'mysql':
			$sql_validation = "UPDATE tevento 
								   SET user_comment = concat('" . $comment . "', user_comment) 
								   WHERE id_evento in (" . implode(',', $id_event) . ")";
			   
			$ret = db_process_sql($sql_validation);	
			break;				
		case 'postgresql':
		case 'oracle':
			$sql_validation = "UPDATE tevento 
								   SET user_comment='" . $comment . "' || user_comment) 
								   WHERE id_evento in (" . implode(',', $id_event) . ")";	
								   
			$ret = db_process_sql($sql_validation);							   					
			break;
	}
	
	if (($ret === false) || ($ret === 0)) {
		return false;
	}
		
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
			max(timestamp) AS time2, id_agentmodule, estado, user_comment, tags, source, id_extra, owner_user
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
 * Return an array with all the possible macros in event responses
 *
 * @return array
 */
function events_get_macros() {
	return array('_agent_address_' => __('Agent address'), '_agent_id_' => __('Agent id'), '_event_id_' => __('Event id'));
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


// Events pages functions to load modal window with advanced view of an event.
// Called from include/ajax/events.php

function events_page_responses ($event) {
	global $config;
	/////////
	// Responses
	/////////
	
	$table_responses->id = 'responses_table';
	$table_responses->width = '100%';
	$table_responses->data = array ();
	$table_responses->head = array ();
	$table_responses->style[0] = 'width:35%; font-weight: bold; text-align: left;';
	$table_responses->style[1] = 'text-align: left;';
	$table_responses->class = "databox alternate";

	// Owner
	$data = array();
	$data[0] = __('Change owner');
		
	$users = groups_get_users(array_keys(users_get_groups(false, "AR", false)));
	
	foreach($users as $u) {
		$owners[$u['id_user']] = $u['fullname'];
	}
	
	if($event['owner_user'] == '') {
		$owner_name = __('None');
	}
	else {
		$owner_name = db_get_value('fullname', 'tusuario', 'id_user', $event['owner_user']);
		$owners[$event['owner_user']] = $owner_name;
	}
	
	$data[1] = html_print_select($owners, 'id_owner', $event['owner_user'], '', __('None'), -1, true);
	$data[1] .= html_print_button(__('Update'),'owner_button',false,'event_change_owner();','class="sub next"',true);
	
	$table_responses->data[] = $data;
	
	// Status
	$data = array();
	$data[0] = __('Change status');
	
	$status = array(0 => __('New'), 2 => __('In process'), 1 => __('Validated'));

	$data[1] = html_print_select($status, 'estado', $event['estado'], '', '', 0, true, false, false);
	$data[1] .= html_print_button(__('Update'),'status_button',false,'event_change_status(\''.$event['similar_ids'] .'\');','class="sub next"',true);

	$table_responses->data[] = $data;
	
	// Comments
	$data = array();
	$data[0] = __('Comment');
	$data[1] = html_print_button(__('Add comment'),'comment_button',false,'$(\'#link_comments\').trigger(\'click\');','class="sub next"',true);

	$table_responses->data[] = $data;
	
	// Delete
	$data = array();
	$data[0] = __('Delete event');
	$data[1] = '<form method="post" response="index.php?sec=eventos&sec2=operation/events/events&section=list&delete=1&eventid='.$event['id_evento'].'">';
	$data[1] .= html_print_button(__('Delete event'),'delete_button',false,'if(!confirm(\''.__('Are you sure?').'\')) { return false; } this.form.submit();','class="sub cancel"',true);
	$data[1] .= '</form>';

	$table_responses->data[] = $data;
	
	// Custom responses
	$data = array();
	$data[0] = __('Custom responses');
	$event_responses = db_get_all_rows_in_table('tevent_response');

	if(empty($event_responses)) {
		$data[1] .= '<i>'.__('N/A').'</i>';
	}
	else {
		$responses = array();
		foreach($event_responses as $v) {
			$responses[$v['id']] = $v['name'];
		}
		$data[1] .= html_print_select($responses,'select_custom_response','','','','',true, false, false);
		$data[1] .= html_print_button(__('Execute'),'custom_response_button',false,'execute_response('.$event['id_evento'].')',"class='sub next'",true);
	}

	$table_responses->data[] = $data;
	
	$responses_js = "<script>
			$('#select_custom_response').change(function() {
				var id_response = $('#select_custom_response').val();
				var params = get_response_params(id_response);
				var description = get_response_description(id_response);
				$('.params_rows').remove();

				$('#responses_table').append('<tr class=\"params_rows\"><td style=\"text-align:left; padding-left:20px;\">".__('Description')."</td><td style=\"text-align:left;\">'+description+'</td></tr>');

				if(params.length == 1 && params[0] == '') {
					return;
				}
				
				$('#responses_table').append('<tr class=\"params_rows\"><td style=\"text-align:left; padding-left:20px;\" colspan=\"2\">".__('Parameters')."</td></tr>');

				for(i=0;i<params.length;i++) {
					add_row_param('responses_table',params[i]);
				}
			});
			$('#select_custom_response').trigger('change');
			</script>";
	
	$responses = '<div id="extended_event_responses_page" class="extended_event_pages">'.html_print_table($table_responses, true).$responses_js.'</div>';

	return $responses;
}

// Replace macros in the target of a response and return it
function events_get_response_target($event_id, $response_id) {
	$event_response = db_get_row('tevent_response','id',$response_id);
		
	$event = db_get_row('tevento','id_evento',$event_id);

	$macros = array_keys(events_get_macros());
	
	$target = io_safe_output($event_response['target']);
	foreach($macros as $macro) {
		$subst = '';
		switch($macro) {
			case '_agent_address_':
				$subst = agents_get_address($event['id_agente']);
				break;
			case '_agent_id_':
				$subst = $event['id_agente'];
				break;
			case '_event_id_':
				$subst = $event['id_evento'];
				break;
		}

		$target = str_replace($macro,$subst,$target);
	}
	
	return $target;
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
		
		$data[1] = empty($fields_data[$field['id_field']]) ? '<i>'.__('N/A').'</i>' : $fields_data[$field['id_field']];
		
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
		$data[1] = empty($agent['direccion']) ? '<i>'.__('N/A').'</i>' : $agent['direccion'];
		$table_details->data[] = $data;
		
		$data = array();
		$data[0] = '<div style="font-weight:normal; margin-left: 20px;">'.__('OS').'</div>';
		$data[1] = ui_print_os_icon ($agent["id_os"], true, true).' ('.$agent["os_version"].')';
		$table_details->data[] = $data;

		$data = array();
		$data[0] = '<div style="font-weight:normal; margin-left: 20px;">'.__('Last contact').'</div>';
		$data[1] = $agent["ultimo_contacto"] == "1970-01-01 00:00:00" ? '<i>'.__('N/A').'</i>' : $agent["ultimo_contacto"];
		$table_details->data[] = $data;

		$data = array();
		$data[0] = '<div style="font-weight:normal; margin-left: 20px;">'.__('Last remote contact').'</div>';
		$data[1] = $agent["ultimo_contacto_remoto"] == "1970-01-01 00:00:00" ? '<i>'.__('N/A').'</i>' : $agent["ultimo_contacto_remoto"];
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
	
	//$group_rep = $event['similar_ids'] == -1 ? 1 : count(explode(',',$event['similar_ids']));
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
	if ($group_rep == 1 && $event["event_rep"] > 1) {
		$data[1] = __('First event').': '.date ($config["date_format"], $event['timestamp_first']).'<br>'.__('Last event').': '.date ($config["date_format"], $event['timestamp_last']);
	}
	else {
		$data[1] = date ($config["date_format"], strtotime($event["timestamp"]));
	}
	$table_general->data[] = $data;
	
	$data = array();
	$data[0] = __('Owner');
	if(empty($event["owner_user"])) {
		$data[1] = '<i>'.__('N/A').'</i>';
	}
	else {
		$user_owner = db_get_value('fullname', 'tusuario', 'id_user', $event["owner_user"]);
		$data[1] = $user_owner;
	}
	$table_general->data[] = $data;
	
	$data = array();
	$data[0] = __('Type');
	$data[1] = events_print_type_img ($event["event_type"], true).' '.events_print_type_description($event["event_type"], true);
	$table_general->data[] = $data;
	
	$data = array();
	$data[0] = __('Repeated');
	if ($group_rep != 0) {
		if($event["event_rep"] <= 1) {
			$data[1] = '<i>'.__('No').'</i>';
		}
		else {
			$data[1] = sprintf("%d Times",$event["event_rep"]);
		}
	}
	else {
		$data[1] = '<i>'.__('No').'</i>';
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
		$tags = str_replace(' ','',$event["tags"]);
		$tags = str_replace(',',' , ',$tags);
		$data[1] = $tags;
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
				$row_text = preg_replace('/\<\/b\>/',"</i>",$row_text);
				$row_text = preg_replace('/\[/',"</b><br><br><i>[",$row_text);
				$row_text = preg_replace('/[\[|\]]/',"",$row_text);
				break;
			case 1:
				$row_text = preg_replace("/[\r\n|\r|\n]/","<br>",io_safe_output(strip_tags($c)));
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
