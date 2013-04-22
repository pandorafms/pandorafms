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
 * @param int Target status of the event. If = 0 (select all), if = 1 (not select validated), if = 2 (not select in process-validated)
 * @param int Limitation in time to get events. If it's zero without limit.
 *
 * @return array A list of events ids.
 */
function events_get_similar_ids ($id, $status = 0, $validated_limit_time = 0) {
	$ids = array ();
	$event = events_get_event ($id, array ('evento', 'id_agentmodule'));
	if ($event === false)
		return $ids;
	
	$sql_filters = array();	
	if ($validated_limit_time > 0) {
		$unixtime = get_system_time () - ($validated_limit_time * 3600); //Put hours in seconds
		$sql_filters['utimestamp'] = "> " . $unixtime;
	}
	
	$sql_filters['evento'] = $event['evento'];
	$sql_filters['id_agentmodule'] = $event['id_agentmodule'];		
	
	switch ($status) {
		// Select all
		case 0:
			$events = db_get_all_rows_filter ('tevento',
				$sql_filters,
				array ('id_evento'));
			break;
		// Not select validated
		case 1:
			$sql_filters['estado'] = '<> 1';
			$events = db_get_all_rows_filter ('tevento',
				$sql_filters,
				array ('id_evento'));
			break;
		// Not select in process and validated
		case 2:
			$sql_filters['estado'] = '<> 1';
			$sql_filters['estado'] = '<> 2';
			$events = db_get_all_rows_filter ('tevento',
				$sql_filters,
				array ('id_evento'));
			break;
	}
	
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
 * @param int Time limit for the get events.
 * @param mixed Event repeated counts given when are provided various ids
 *
 * @return bool Whether or not it was successful
 */
function events_delete_event ($id_event, $similar = true, $validated_limit_time = 0, $events_rep = array()) {
	global $config;
	
	//Cleans up the selection for all unwanted values also casts any single values as an array 
	$id_event = (array) safe_int ($id_event, 1);
	
	/* We must delete all events like the selected */
	if ($similar) {
		foreach ($id_event as $k => $id) {
			// Check if is provided $events_rep and if is 1 to avoid get similar ids
			if(!empty($events_rep) && $events_rep[$k] == 1) {
				continue;
			}
			$id_event = array_merge ($id_event, events_get_similar_ids ($id,0 ,$validated_limit_time));
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
	}
	else {
		return true;
	}
}

/**
 * Validate events in a transaction
 *
 * @param mixed Event ID or array of events
 * @param bool Whether to validate similar events or not.
 * @param string Comment in the validated event.
 * @param int New status.
 * @param int Time limit for the get events.
 * @param mixed Event repeated counts given when are provided various ids
 *
 * @return bool Whether or not it was successful
 */	
function events_validate_event ($id_event, $similars = true, $comment = '', $new_status = 1, $validated_limit_time = 0, $events_rep = array()) {
	global $config;
	
	//Cleans up the selection for all unwanted values also casts any single values as an array 
	$id_event = (array) safe_int ($id_event, 1);
	
	/* We must validate all events like the selected */
	if ($similars && $new_status == 1) {
		foreach ($id_event as $k => $id) {
			// Check if is provided $events_rep and if is 1 to avoid get similar ids
			if(!empty($events_rep) && $events_rep[$k] == 1) {
				continue;
			}
			
			$id_event = array_merge ($id_event, events_get_similar_ids ($id, $new_status, $validated_limit_time));
		}
		$id_event = array_unique($id_event);
	}
	
	$events_similar = array();
	
	// Now check ACLs, discart no valid ACL events
	if (!empty($id_event)) {
		
		foreach ($id_event as $event) {
			
			if (check_acl ($config["id_user"], events_get_group ($event), "IW") == 0) {
				db_pandora_audit("ACL Violation", "Attempted updating event #".$event);
				
				continue;
			}
			
			$events_similar[] = $event;
		
		}
		
		$id_event = $events_similar;
		
	}
	
	switch ($new_status) {
		case 1:
			$new_status_string = 'Validated';
			break;
		case 2:
			$new_status_string = 'Setted in process';
			break;
	}
	
	$comment = str_replace(array("\r\n", "\r", "\n"), '<br>', $comment);
	
	if ($comment != '') {
		$commentbox = '<div style="border:1px dotted #CCC; min-height: 10px;">'.$comment.'</div>';
	}
	else {
		$commentbox = '';
	}
	
	$comment = '<b>-- ' . $new_status_string . ' ' . __('by') . ' '.$config['id_user'].' '.'['.date ($config["date_format"]).'] --</b><br>'.$commentbox.'<br>';
	
	// If we are validating then only select => not validated
	// If we are setting in process only select => not validated and not setted in process
	switch ($config['dbtype']) {
		// Oldstyle SQL to avoid innecesary PHP foreach
		case 'mysql':
			$sql_validation = "UPDATE tevento 
				SET estado = " . $new_status .", 
					id_usuario = '" . $config['id_user'] . "', 
					user_comment=concat(user_comment, '" . $comment . "') 
				WHERE id_evento in (" . implode(',', $id_event) . ")";
			
			if ($new_status == 1)
				$sql_validation .= " AND estado <> 1";
			else if ($new_status == 2)
				$sql_validation .= " AND estado NOT IN (1,2)";
			
			$ret = db_process_sql($sql_validation);
			break;
		case 'postgresql':
		case 'oracle':
			$sql_validation = "UPDATE tevento 
				SET estado = " . $new_status . ", 
					id_usuario = '" . $config['id_user'] . "', 
					user_comment=user_comment || '" . $comment . "') 
				WHERE id_evento in (" . implode(',', $id_event) . ")";	
			
			if ($new_status == 1)
				$sql_validation .= " AND estado <> 1";
			else if ($new_status == 2)
				$sql_validation .= " AND estado NOT IN (1,2)";
			
			$ret = db_process_sql($sql_validation);
			break;
	}
	
	if (($ret === false) || ($ret === 0)) {
		return false;
	}
	
	foreach ($id_event as $event) {
		db_pandora_audit("Event validated", "Validated event #".$event);
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
function events_create_event ($event, $id_group, $id_agent, $status = 0, $id_user = "", $event_type = "unknown", $priority = 0, $id_agent_module = 0, $id_aam = 0) {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			$sql = sprintf ('INSERT INTO tevento (id_agente, id_grupo, evento, timestamp, 
				estado, utimestamp, id_usuario, event_type, criticity,
				id_agentmodule, id_alert_am) 
				VALUES (%d, %d, "%s", NOW(), %d, UNIX_TIMESTAMP(NOW()), "%s", "%s", %d, %d, %d)',
				$id_agent, $id_group, $event, $status, $id_user, $event_type,
				$priority, $id_agent_module, $id_aam);
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
	}
	else {
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
		
		$table->head[0] = "<span title='" . __('Validated') . "'>" . __('V.') . "</span>";
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
function events_get_group_events ($id_group, $period, $date,
	$filter_event_validated = false, $filter_event_critical = false,
	$filter_event_warning = false, $filter_event_no_validated = false) {
	global $config;
	
	$id_group = groups_safe_acl ($config["id_user"], $id_group, "AR");
	
	if (empty ($id_group)) {
		//An empty array means the user doesn't have access
		return false;
	}
	
	$datelimit = $date - $period;
	
	$sql_where = ' AND 1 = 1 ';
	if ($filter_event_critical) {
		$sql_where .= ' AND criticity = 4 ';
	}
	if ($filter_event_warning) {
		$sql_where .= ' AND criticity = 3 ';
	}
	if ($filter_event_validated) {
		$sql_where .= ' AND estado = 1 ';
	}
	if ($filter_event_no_validated) {
		$sql_where .= ' AND estado = 0 ';
	}
	
	$sql = sprintf ('SELECT *,
		(SELECT t2.nombre
			FROM tagente AS t2
			WHERE t2.id_agente = t3.id_agente) AS agent_name,
		(SELECT t2.fullname
			FROM tusuario AS t2
			WHERE t2.id_user = t3.id_usuario) AS user_name
		FROM tevento AS t3
		WHERE utimestamp > %d AND utimestamp <= %d
			AND id_grupo IN (%s) ' . $sql_where . '
		ORDER BY utimestamp ASC',
		$datelimit, $date, implode (",", $id_group));
	
	return db_get_all_rows_sql ($sql);
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
function events_get_count_events_by_agent ($id_group, $period, $date,
	$filter_event_validated = false, $filter_event_critical = false,
	$filter_event_warning = false, $filter_event_no_validated = false) {
	global $config;
	
	$id_group = groups_safe_acl ($config["id_user"], $id_group, "AR");
	
	if (empty ($id_group)) {
		//An empty array means the user doesn't have access
		return false;
	}
	
	$datelimit = $date - $period;
	
	$sql_where = ' AND 1 = 1 ';
	if ($filter_event_critical) {
		$sql_where .= ' AND criticity = 4 ';
	}
	if ($filter_event_warning) {
		$sql_where .= ' AND criticity = 3 ';
	}
	if ($filter_event_validated) {
		$sql_where .= ' AND estado = 1 ';
	}
	if ($filter_event_no_validated) {
		$sql_where .= ' AND estado = 0 ';
	}
	
	$sql = sprintf ('SELECT id_agente,
		(SELECT t2.nombre
			FROM tagente AS t2
			WHERE t2.id_agente = t3.id_agente) AS agent_name,
		COUNT(*) AS count
		FROM tevento AS t3
		WHERE utimestamp > %d AND utimestamp <= %d
			AND id_grupo IN (%s) ' . $sql_where . '
		GROUP BY id_agente',
		$datelimit, $date, implode (",", $id_group));
	
	$rows = db_get_all_rows_sql ($sql);
	
	if ($rows == false)
		$rows = array();
	
	$return = array();
	foreach ($rows as $row) {
		$agent_name = $row['agent_name'];
		if (empty($row['agent_name'])) {
			$agent_name = __('Pandora System');
		}
		$return[$agent_name] = $row['count'];
	}
	
	return $return;
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
function events_get_count_events_validated_by_user ($filter, $period, $date,
	$filter_event_validated = false, $filter_event_critical = false,
	$filter_event_warning = false, $filter_event_no_validated = false) {
	global $config;
	
	$sql_filter = ' AND 1=1 ';
	if (isset($filter['id_group'])) {
		$id_group = groups_safe_acl ($config["id_user"], $filter['id_group'], "AR");
		
		if (empty ($id_group)) {
			//An empty array means the user doesn't have access
			return false;
		}
		
		$sql_filter .= 
			sprintf(' AND id_grupo IN (%s) ', implode (",", $id_group));
	}
	if (!empty($filter['id_agent'])) {
		$sql_filter .= 
			sprintf(' AND id_agente = %d ', $filter['id_agent']);
	}
	
	$datelimit = $date - $period;
	
	$sql_where = ' AND 1 = 1 ';
	if ($filter_event_critical) {
		$sql_where .= ' AND criticity = 4 ';
	}
	if ($filter_event_warning) {
		$sql_where .= ' AND criticity = 3 ';
	}
	if ($filter_event_validated) {
		$sql_where .= ' AND estado = 1 ';
	}
	if ($filter_event_no_validated) {
		$sql_where .= ' AND estado = 0 ';
	}
	
	$sql = sprintf ('SELECT id_usuario,
		(SELECT t2.fullname
			FROM tusuario AS t2
			WHERE t2.id_user = t3.id_usuario) AS user_name,
		COUNT(*) AS count
		FROM tevento AS t3
		WHERE utimestamp > %d AND utimestamp <= %d
			%s ' . $sql_where . '
		GROUP BY id_usuario',
		$datelimit, $date, $sql_filter);
	
	$rows = db_get_all_rows_sql ($sql);
	
	if ($rows == false)
		$rows = array();
	
	$return = array();
	foreach ($rows as $row) {
		$user_name = $row['user_name'];
		if (empty($row['user_name'])) {
			$user_name = __('Unknown');
		}
		$return[$user_name] = $row['count'];
	}
	
	return $return;
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
function events_get_count_events_by_criticity ($filter, $period, $date,
	$filter_event_validated = false, $filter_event_critical = false,
	$filter_event_warning = false, $filter_event_no_validated = false) {
	global $config;
	
	$sql_filter = ' AND 1=1 ';
	if (isset($filter['id_group'])) {
		$id_group = groups_safe_acl ($config["id_user"], $filter['id_group'], "AR");
		
		if (empty ($id_group)) {
			//An empty array means the user doesn't have access
			return false;
		}
		
		$sql_filter .= 
			sprintf(' AND id_grupo IN (%s) ', implode (",", $id_group));
	}
	if (!empty($filter['id_agent'])) {
		$sql_filter .= 
			sprintf(' AND id_agente = %d ', $filter['id_agent']);
	}
	
	$datelimit = $date - $period;
	
	$sql_where = ' AND 1 = 1 ';
	if ($filter_event_critical) {
		$sql_where .= ' AND criticity = 4 ';
	}
	if ($filter_event_warning) {
		$sql_where .= ' AND criticity = 3 ';
	}
	if ($filter_event_validated) {
		$sql_where .= ' AND estado = 1 ';
	}
	if ($filter_event_no_validated) {
		$sql_where .= ' AND estado = 0 ';
	}
	
	$sql = sprintf ('SELECT criticity,
		COUNT(*) AS count
		FROM tevento
		WHERE utimestamp > %d AND utimestamp <= %d
			%s ' . $sql_where . '
		GROUP BY criticity',
		$datelimit, $date, $sql_filter);
	
	$rows = db_get_all_rows_sql ($sql);
	
	if ($rows == false)
		$rows = array();
	
	$return = array();
	foreach ($rows as $row) {
		$return[get_priority_name($row['criticity'])] = $row['count'];
	}
	
	return $return;
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
function events_get_count_events_validated ($filter, $period, $date,
	$filter_event_validated = false, $filter_event_critical = false,
	$filter_event_warning = false, $filter_event_no_validated = false) {
	global $config;
	
	$sql_filter = ' AND 1=1 ';
	if (isset($filter['id_group'])) {
		$id_group = groups_safe_acl ($config["id_user"], $filter['id_group'], "AR");
		
		if (empty ($id_group)) {
			//An empty array means the user doesn't have access
			return false;
		}
		
		$sql_filter .= 
			sprintf(' AND id_grupo IN (%s) ', implode (",", $id_group));
	}
	if (!empty($filter['id_agent'])) {
		$sql_filter .= 
			sprintf(' AND id_agente = %d ', $filter['id_agent']);
	}
	
	$datelimit = $date - $period;
	
	$sql_where = ' AND 1 = 1 ';
	if ($filter_event_critical) {
		$sql_where .= ' AND criticity = 4 ';
	}
	if ($filter_event_warning) {
		$sql_where .= ' AND criticity = 3 ';
	}
	if ($filter_event_validated) {
		$sql_where .= ' AND estado = 1 ';
	}
	if ($filter_event_no_validated) {
		$sql_where .= ' AND estado = 0 ';
	}
	
	$sql = sprintf ('SELECT estado,
		COUNT(*) AS count
		FROM tevento
		WHERE utimestamp > %d AND utimestamp <= %d
			%s ' . $sql_where . '
		GROUP BY estado',
		$datelimit, $date, $sql_filter);
	
	$rows = db_get_all_rows_sql ($sql);
	
	if ($rows == false)
		$rows = array();
	
	$return = array();
	$return[__('Validated')] = 0;
	$return[__('Not validated')] = 0;
	foreach ($rows as $row) {
		if ($row['estado'] == 1) {
			$return[__('Validated')] += $row['count'];
		}
		else {
			$return[__('Not validated')] += $row['count'];
		}
	}
	
	return $return;
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
function events_get_agent ($id_agent, $period, $date = 0,
	$filter_event_validated = false, $filter_event_critical = false,
	$filter_event_warning = false, $filter_event_no_validated = false) {
	
	if (!is_numeric ($date)) {
		$date = strtotime ($date);
	}
	if (empty ($date)) {
		$date = get_system_time ();
	}
	
	$datelimit = $date - $period;
	
	$sql_where = ' AND 1 = 1 ';
	if ($filter_event_critical) {
		$sql_where .= ' AND criticity = 4 ';
	}
	if ($filter_event_warning) {
		$sql_where .= ' AND criticity = 3 ';
	}
	if ($filter_event_validated) {
		$sql_where .= ' AND estado = 1 ';
	}
	if ($filter_event_no_validated) {
		$sql_where .= ' AND estado = 0 ';
	}
	
	$sql = sprintf ('SELECT id_usuario,
			(SELECT t2.fullname
				FROM tusuario AS t2
				WHERE t2.id_user = t3.id_usuario) AS user_name,
			estado, id_agentmodule, evento, event_type, criticity,
			count(*) AS count_rep, max(timestamp) AS time2
		FROM tevento as t3
		WHERE id_agente = %d AND utimestamp > %d
			AND utimestamp <= %d ' . $sql_where . '
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
 * Return all descriptions of event status.
 *
 * @return array Status description array.
 */
function events_get_all_status () {
	$fields = array ();
	$fields[-1] = __('All event');
	$fields[0] = __('Only new');
	$fields[1] = __('Only validated');
	$fields[2] = __('Only in process');
	$fields[3] = __('Only not validated');
	
	return $fields;
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

function events_get_total_events_grouped($sql_post, $meta = false, $history = false) {
	return events_get_events_grouped($sql_post, 0, 0, $meta, $history, true);
}

/**
 * Decode a numeric status into status description.
 *
 * @param int $status_id Numeric status.
 *
 * @return string Status description.
 */
function events_get_status ($status_id) {
	switch ($status_id) {
		case -1:
			$status_desc = __('All event');
			break;
		case 0:
			$status_desc = __('Only new');
			break;
		case 1:
			$status_desc = __('Only validated');
			break;
		case 2:
			$status_desc = __('Only in process');
			break;
		case 3:
			$status_desc = __('Only not validated');
			break;
	}
	
	return $status_desc;
}
?>