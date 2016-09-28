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
include_once($config['homedir'] . "/include/functions_tags.php");
include_once($config['homedir'] . "/include/functions.php");
enterprise_include_once ('meta/include/functions_events_meta.php');
enterprise_include_once ('meta/include/functions_agents_meta.php');
enterprise_include_once ('meta/include/functions_modules_meta.php');


/**
 * @package Include
 * @subpackage Events
 */

function events_get_all_fields() {
	
	$columns = array();
	
	$columns['id_evento'] = __('Event id');
	$columns['evento'] = __('Event name');
	$columns['id_agente'] = __('Agent name');
	$columns['id_usuario'] = __('User');
	$columns['id_grupo'] = __('Group');
	$columns['estado'] = __('Status');
	$columns['timestamp'] = __('Timestamp');
	$columns['event_type'] = __('Event type');
	$columns['id_agentmodule'] = __('Agent module');
	$columns['id_alert_am'] = __('Alert');
	$columns['criticity'] = __('Severity');
	$columns['user_comment'] = __('Comment');
	$columns['tags'] = __('Tags');
	$columns['source'] = __('Source');
	$columns['id_extra'] = __('Extra id');
	$columns['owner_user'] = __('Owner');
	$columns['ack_utimestamp'] = __('ACK Timestamp');
	$columns['instructions'] = __('Instructions');
	$columns['server_name'] = __('Server name');
	
	return $columns;
}

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
	if ($filter['criticity'] == EVENT_CRIT_WARNING_OR_CRITICAL) {
		$filter['criticity'] = array(EVENT_CRIT_WARNING, EVENT_CRIT_CRITICAL);
	}
	
	return db_get_all_rows_filter ('tevento', $filter, $fields);
}

/**
 * Get the event with the id pass as parameter.
 * 
 * @param int $id Event id
 * @param mixed $fields The fields to show or by default all with false.
 * 
 * @return mixed False in case of error or invalid values passed. Event row otherwise
 */
function events_get_event ($id, $fields = false, $meta = false) {
	if (empty ($id))
		return false;
	global $config;
	
	if (is_array ($fields)) {
		if (! in_array ('id_grupo', $fields))
			$fields[] = 'id_grupo';
	}
	
	if($meta) {
		$event = events_meta_get_event($id, array ('evento', 'id_agentmodule'), $history);
	}
	else {
		$event = events_get_event ($id, array ('evento', 'id_agentmodule'));
	}
	
	if (! check_acl ($config['id_user'], $event['id_grupo'], 'ER'))
		return false;
	
	return $event;
}

function events_get_events_grouped($sql_post, $offset = 0,
	$pagination = 1, $meta = false, $history = false, $total = false) {
	
	global $config; 
	
	$table = events_get_events_table($meta, $history);
	
	if ($meta) {
		$groupby_extra = ', server_id';
	}
	else {
		$groupby_extra = '';
	}
	
	switch ($config["dbtype"]) {
		case "mysql":
			db_process_sql ('SET group_concat_max_len = 9999999');
			if ($total) {
				$sql = "SELECT COUNT(*) FROM (SELECT *
					FROM $table te
					WHERE 1=1 " . $sql_post . "
					GROUP BY estado, evento, id_agentmodule" . $groupby_extra . ") AS t";
			}
			else {
				$sql = "SELECT *, MAX(id_evento) AS id_evento,
						GROUP_CONCAT(DISTINCT user_comment SEPARATOR '<br>') AS user_comment,
						GROUP_CONCAT(DISTINCT id_evento SEPARATOR ',') AS similar_ids,
						COUNT(*) AS event_rep, MAX(utimestamp) AS timestamp_rep, 
						MIN(utimestamp) AS timestamp_rep_min,
						(SELECT owner_user FROM $table WHERE id_evento = MAX(te.id_evento)) owner_user,
						(SELECT id_usuario FROM $table WHERE id_evento = MAX(te.id_evento)) id_usuario,
						(SELECT id_agente FROM $table WHERE id_evento = MAX(te.id_evento)) id_agente,
						(SELECT criticity FROM $table WHERE id_evento = MAX(te.id_evento)) AS criticity,
						(SELECT ack_utimestamp FROM $table WHERE id_evento = MAX(te.id_evento)) AS ack_utimestamp
					FROM $table te
					WHERE 1=1 " . $sql_post . "
					GROUP BY estado, evento, id_agentmodule" . $groupby_extra . "
					ORDER BY timestamp_rep DESC LIMIT " . $offset . "," . $pagination;
			}
			break;
		case "postgresql":
			if ($total) {
				$sql = "SELECT COUNT(*)
					FROM $table te
					WHERE 1=1 " . $sql_post . "
					GROUP BY estado, evento, id_agentmodule, id_evento, id_agente, id_usuario, id_grupo, estado, timestamp, utimestamp, event_type, id_alert_am, criticity, user_comment, tags, source, id_extra" . $groupby_extra;
			}
			else {
				$sql = "SELECT *, MAX(id_evento) AS id_evento, array_to_string(array_agg(DISTINCT user_comment), '<br>') AS user_comment,
						array_to_string(array_agg(DISTINCT id_evento), ',') AS similar_ids,
						COUNT(*) AS event_rep, MAX(utimestamp) AS timestamp_rep, 
						MIN(utimestamp) AS timestamp_rep_min,
						(SELECT owner_user FROM $table WHERE id_evento = MAX(te.id_evento)) owner_user,
						(SELECT id_usuario FROM $table WHERE id_evento = MAX(te.id_evento)) id_usuario,
						(SELECT id_agente FROM $table WHERE id_evento = MAX(te.id_evento)) id_agente,
						(SELECT criticity FROM $table WHERE id_evento = MAX(te.id_evento)) AS criticity,
						(SELECT ack_utimestamp FROM $table WHERE id_evento = MAX(te.id_evento)) AS ack_utimestamp
					FROM $table te
					WHERE 1=1 " . $sql_post . "
					GROUP BY estado, evento, id_agentmodule, id_evento,
						id_agente, id_usuario, id_grupo, estado,
						timestamp, utimestamp, event_type, id_alert_am,
						criticity, user_comment, tags, source, id_extra,
						te.critical_instructions,
						te.warning_instructions,
						te.unknown_instructions,
						te.owner_user,
						te.ack_utimestamp,
						te.custom_data " . $groupby_extra . "
					ORDER BY timestamp_rep DESC LIMIT " . $pagination . " OFFSET " . $offset;
			}
			break;
		case "oracle":
			if ($total) {
				$sql = "SELECT COUNT(*)
						FROM $table te
						WHERE 1=1 $sql_post
						GROUP BY estado, to_char(evento), id_agentmodule" . $groupby_extra . ") b ";
			}
			else {
				$set = array();
				$set['limit'] = $pagination;
				$set['offset'] = $offset;
				
				$sql = "SELECT ta.*, tb.event_rep, tb.timestamp_rep, tb.timestamp_rep_min, tb.user_comments, tb.similar_ids
						FROM $table ta
						INNER JOIN (SELECT MAX(id_evento) AS id_evento, COUNT(id_evento) AS event_rep,
										MAX(utimestamp) AS timestamp_rep, MIN(utimestamp) AS timestamp_rep_min,
										TAB_TO_STRING(CAST(COLLECT(TO_CHAR(user_comment) ORDER BY id_evento ASC) AS t_varchar2_tab), '<br>') AS user_comments,
										TAB_TO_STRING(CAST(COLLECT(CAST(id_evento AS VARCHAR2(4000)) ORDER BY id_evento ASC) AS t_varchar2_tab)) AS similar_ids
									FROM $table te
									WHERE 1=1 $sql_post
									GROUP BY estado, to_char(evento), id_agentmodule$groupby_extra) tb
							ON ta.id_evento = tb.id_evento
						ORDER BY tb.timestamp_rep DESC";
				$sql = oracle_recode_query ($sql, $set);
			}
			break;
	}
	
	//Extract the events by filter (or not) from db
	$events = db_get_all_rows_sql ($sql);
	
	if ($total) {
		return reset($events[0]);
	}
	else {
		// Override the column 'user_comment' with the column 'user_comments' when oracle
		if (!empty($events) && $config["dbtype"] == "oracle") {
			array_walk($events, function(&$value, $key) {
				set_if_defined($value['user_comments'], $value['user_comments']);
			});
		}
		
		return $events;
	}
}

function events_get_total_events_grouped($sql_post, $meta = false, $history = false) {
	return events_get_events_grouped($sql_post, 0, 0, $meta, $history, true);
}

/**
 * Get all the events ids similar to a given event id.
 *
 * An event is similar then the event text (evento) and the id_agentmodule are
 * the same.
 *
 * @param int Event id to get similar events.
 * @param bool Metaconsole mode flag
 * @param bool History mode flag
 *
 * @return array A list of events ids.
 */
function events_get_similar_ids ($id, $meta = false, $history = false) {
	$events_table = events_get_events_table($meta, $history);
	
	$ids = array ();
	if($meta) {
		$event = events_meta_get_event($id, array ('evento', 'id_agentmodule'), $history);
	}
	else {
		$event = events_get_event ($id, array ('evento', 'id_agentmodule'));
	}
	if ($event === false)
		return $ids;
	
	$events = db_get_all_rows_filter ($events_table,
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
 * @param bool Metaconsole mode flag
 * @param bool History mode flag
 *
 * @return bool Whether or not it was successful
 */
function events_delete_event ($id_event, $similar = true, $meta = false, $history = false) {
	global $config;
	
	$table_event = events_get_events_table($meta, $history);
	
	//Cleans up the selection for all unwanted values also casts any single values as an array 
	$id_event = (array) safe_int ($id_event, 1);
	
	/* We must delete all events like the selected */
	if ($similar) {
		foreach ($id_event as $id) {
			$id_event = array_merge ($id_event, events_get_similar_ids ($id, $meta, $history));
		}
		$id_event = array_unique($id_event);
	}
	
	$errors = 0;
	
	foreach ($id_event as $event) {
		if ($meta) {
			$event_group = events_meta_get_group ($event, $history);
		}
		else {
			$event_group = events_get_group ($event);
		}
		
		if (check_acl ($config["id_user"], $event_group, "EM") == 0) {
			//Check ACL
			db_pandora_audit("ACL Violation", "Attempted deleting event #".$event);
			$errors++;
		}
		else {
			$ret = db_process_sql_delete($table_event, array('id_evento' => $event));
			
			if(!$ret) {
				$errors++;
			}
			else {
				db_pandora_audit("Event deleted", "Deleted event #".$event);
				//ACL didn't fail nor did return
				continue;
			}
		}
		
		break;
	}
	
	if ($errors > 0) {
		return false;
	}
	else {
		return true;
	}
}

/**
 * Change the status of one or various events
 *
 * @param mixed Event ID or array of events
 * @param int new status of the event
 * @param bool metaconsole mode flag
 * @param bool history mode flag
 *
 * @return bool Whether or not it was successful
 */
function events_change_status ($id_event, $new_status, $meta = false, $history = false) { 
	global $config;
	
	$event_table = events_get_events_table($meta, $history);
	
	//Cleans up the selection for all unwanted values also casts any single values as an array 
	$id_event = (array) safe_int ($id_event, 1);
	
	// Update ack info if the new status is validated
	if ($new_status == EVENT_STATUS_VALIDATED) {
		$ack_utimestamp = time();
		$ack_user = $config['id_user'];
	}
	else {
		$acl_utimestamp = 0;
		$ack_user = '';
	}
	
	switch ($new_status) {
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
	
	$alerts = array();
	
	foreach ($id_event as $k => $id) {
		if ($meta) {
			$event_group = events_meta_get_group ($id, $history);
			$event = events_meta_get_event ($id, false, $history);
			$server_id = $event['server_id'];
		}
		else {
			$event_group = events_get_group ($id);
			$event = events_get_event ($id);
		}
		
		if ($event['id_alert_am'] > 0 && !in_array($event['id_alert_am'], $alerts)) {
			$alerts[] = $event['id_alert_am'];
		}
		
		if (check_acl ($config["id_user"], $event_group, "EW") == 0) {
			db_pandora_audit("ACL Violation", "Attempted updating event #".$id);
			
			unset($id_event[$k]);
		}
	}
	
	if (empty($id_event)) {
		return false;
	}
	
	$values = array(
		'estado' => $new_status,
		'id_usuario' => $ack_user,
		'ack_utimestamp' => $ack_utimestamp);
	
	$ret = db_process_sql_update($event_table, $values,
		array('id_evento' => $id_event));
	
	if (($ret === false) || ($ret === 0)) {
		return false;
	}
	
	events_comment($id_event, '', "Change status to $status_string", $meta, $history);
	
	if ($meta && !empty($alerts)) {
		$server = metaconsole_get_connection_by_id ($server_id);
		metaconsole_connect($server);
	}
	
	// Put the alerts in standby or not depends the new status
	foreach ($alerts as $alert) {
		switch ($new_status) {
			case EVENT_NEW:
			case EVENT_VALIDATE:
				alerts_agent_module_standby ($alert, 0);
				break;
			case EVENT_PROCESS:
				alerts_agent_module_standby ($alert, 1);
				break;
		}
	}
	
	if ($meta && !empty($alerts)) {
		metaconsole_restore_db();
	}
	
	return true;
}

/**
 * Change the owner of an event if the event hasn't owner
 *
 * @param mixed Event ID or array of events
 * @param string id_user of the new owner. If is false, the current owner will be setted
 * @param bool flag to force the change or not (not force is change only when it hasn't owner)
 * @param bool metaconsole mode flag
 * @param bool history mode flag
 * 
 * @return bool Whether or not it was successful
 */	
function events_change_owner ($id_event, $new_owner = false, $force = false, $meta = false, $history = false) {
	global $config;
	
	$event_table = events_get_events_table($meta, $history);
	
	//Cleans up the selection for all unwanted values also casts any single values as an array 
	$id_event = (array) safe_int ($id_event, 1);
	
	foreach ($id_event as $k => $id) {
		if ($meta) {
			$event_group = events_meta_get_group ($id, $history);
		}
		else {
			$event_group = events_get_group ($id);
		}
		if (check_acl ($config["id_user"], $event_group, "EW") == 0) {
			db_pandora_audit("ACL Violation", "Attempted updating event #".$id);
			unset($id_event[$k]);
		}
	}
	
	if (empty($id_event)) {
		return false;
	}
	
	// If no new_owner is provided, the current user will be the owner
	// ** Comment this lines because if possible selected None owner in owner event. TIQUET: #2250***
	//if (empty($new_owner)) {
	//	$new_owner = $config['id_user'];
	//}
	
	// Only generate comment when is forced (sometimes is changed the owner when comment)
	if ($force) {
		events_comment($id_event, '', "Change owner to $new_owner", $meta, $history);
	}
	
	$values = array('owner_user' => $new_owner);
	
	$where = array('id_evento' => $id_event);
	
	// If not force, add to where if owner_user = ''
	if (!$force) {
		$where['owner_user'] = '';
	}
	
	$ret = db_process_sql_update($event_table, $values,
		$where, 'AND', false);
	
	if (($ret === false) || ($ret === 0)) {
		return false;
	}
	
	return true;
}

function events_get_events_table($meta, $history) {
	if ($meta) {
		if ($history) {
			$event_table = 'tmetaconsole_event_history';
		}
		else {
			$event_table = 'tmetaconsole_event';
		}
	}
	else {
		$event_table = 'tevento';
	}
	
	return $event_table;
}

/**
 * Comment events in a transresponse
 *
 * @param mixed Event ID or array of events
 * @param string comment to be registered
 * @param string action performed with the comment. Bu default just Added comment
 * @param bool Flag of metaconsole mode
 * @param bool Flag of history mode
 *
 * @return bool Whether or not it was successful
 */	
function events_comment ($id_event, $comment = '', $action = 'Added comment', $meta = false, $history = false, $similars = true) {
	global $config;
	
	$event_table = events_get_events_table($meta, $history);
	
	//Cleans up the selection for all unwanted values also casts any single values as an array 
	$id_event = (array) safe_int ($id_event, 1);
	
	foreach ($id_event as $k => $id) {
		if ($meta) {
			$event_group = events_meta_get_group ($id, $history);
		}
		else {
			$event_group = events_get_group ($id);
		}
		if (check_acl ($config["id_user"], $event_group, "EW") == 0) {
			db_pandora_audit("ACL Violation", "Attempted updating event #".$id);
			
			unset($id_event[$k]);
		}
	}
	
	if (empty($id_event)) {
		return false;
	}
	
	// If the event hasn't owner, assign the user as owner
	events_change_owner ($id_event);
	
	// Get the current event comments
	$first_event = $id_event;
	if (is_array($id_event)) {
		$first_event = reset($id_event);
	}
	$event_comments = db_get_value('user_comment', $event_table, 'id_evento', $first_event);
	$event_comments_array = array();
	
	if ($event_comments == '') {
		$comments_format = 'new';
	}
	else {
		// If comments are not stored in json, the format is old
		$event_comments_array = json_decode($event_comments);
		
		if (is_null($event_comments_array)) {
			$comments_format = 'old';
		}
		else {
			$comments_format = 'new';
		}
	}
	
	switch($comments_format) {
		case 'new':
			$comment_for_json['comment'] = $comment;
			$comment_for_json['action'] = $action;
			$comment_for_json['id_user'] = $config['id_user'];
			$comment_for_json['utimestamp'] = time();
			
			$event_comments_array[] = $comment_for_json;
			
			$event_comments = io_json_mb_encode($event_comments_array);
			
			// Update comment
			$ret = db_process_sql_update($event_table,  array('user_comment' => $event_comments), array('id_evento' => implode(',', $id_event)));
		break;
		case 'old':
			// Give old ugly format to comment. TODO: Change this method for aux table or json
			$comment = str_replace(array("\r\n", "\r", "\n"), '<br>', $comment);
			
			if ($comment != '') {
				$commentbox = '<div style="border:1px dotted #CCC; min-height: 10px;">'.$comment.'</div>';
			}
			else {
				$commentbox = '';
			}
			
			// Don't translate 'by' word because if various users with different languages 
			// make comments in the same console will be a mess
			$comment = '<b>-- ' . $action . ' by '.$config['id_user'].' '.'['.date ($config["date_format"]).'] --</b><br>'.$commentbox.'<br>';
			
			// Update comment
			switch ($config['dbtype']) {
				// Oldstyle SQL to avoid innecesary PHP foreach
				case 'mysql':
					$sql_validation = "UPDATE $event_table 
						SET user_comment = concat('" . $comment . "', user_comment) 
						WHERE id_evento in (" . implode(',', $id_event) . ")";
					
					$ret = db_process_sql($sql_validation);
					break;
				case 'postgresql':
				case 'oracle':
					$sql_validation = "UPDATE $event_table 
						SET user_comment='" . $comment . "' || user_comment) 
						WHERE id_evento in (" . implode(',', $id_event) . ")";
					
					$ret = db_process_sql($sql_validation);
					break;
			}
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
function events_create_event ($event, $id_group, $id_agent, $status = 0,
	$id_user = "", $event_type = "unknown", $priority = 0,
	$id_agent_module = 0, $id_aam = 0, $critical_instructions = '',
	$warning_instructions = '', $unknown_instructions = '',
	$source="Pandora", $tags="", $custom_data="", $server_id = 0) {
	
	global $config;
	
	$table_events = 'tevento';
	if (defined ('METACONSOLE')) {
		$table_events = 'tmetaconsole_event';
		
		switch ($config["dbtype"]) {
			case "mysql":
				$sql = sprintf ('
					INSERT INTO ' . $table_events . ' (id_agente, id_grupo, evento,
						timestamp, estado, utimestamp, id_usuario,
						event_type, criticity, id_agentmodule, id_alert_am,
						critical_instructions, warning_instructions,
						unknown_instructions, source, tags, custom_data,
						server_id) 
					VALUES (%d, %d, "%s", NOW(), %d, UNIX_TIMESTAMP(NOW()),
						"%s", "%s", %d, %d, %d, "%s", "%s", "%s", "%s",
						"%s", "%s", %d)',
					$id_agent, $id_group, $event, $status, $id_user,
					$event_type, $priority, $id_agent_module, $id_aam,
					$critical_instructions, $warning_instructions,
					$unknown_instructions, $source, $tags, $custom_data,
					$server_id);
				break;
			case "postgresql":
				$sql = sprintf ('
					INSERT INTO ' . $table_events . ' (id_agente, id_grupo, evento,
						timestamp, estado, utimestamp, id_usuario,
						event_type, criticity, id_agentmodule, id_alert_am,
						critical_instructions, warning_instructions,
						unknown_instructions, source, tags, custom_data,
						server_id) 
					VALUES (%d, %d, "%s", NOW(), %d,
						ceil(date_part(\'epoch\', CURRENT_TIMESTAMP)), "%s",
						"%s", %d, %d, %d, "%s", "%s", "%s", "%s", "%s",
						"%s", %d)',
					$id_agent, $id_group, $event, $status, $id_user,
					$event_type, $priority, $id_agent_module, $id_aam,
					$critical_instructions, $warning_instructions,
					$unknown_instructions, $source, $tags, $custom_data,
					$server_id);
				break;
			case "oracle":
				$sql = sprintf ('
					INSERT INTO ' . $table_events . ' (id_agente, id_grupo, evento,
						timestamp, estado, utimestamp, id_usuario,
						event_type, criticity, id_agentmodule, id_alert_am,
						critical_instructions, warning_instructions,
						unknown_instructions, source, tags, custom_data,
						server_id) 
					VALUES (%d, %d, "%s", CURRENT_TIMESTAMP, %d, UNIX_TIMESTAMP,
						"%s", "%s", %d, %d, %d, "%s", "%s", "%s", "%s",
						"%s", "%s", %d)',
					$id_agent, $id_group, $event, $status, $id_user,
					$event_type, $priority, $id_agent_module, $id_aam,
					$critical_instructions, $warning_instructions,
					$unknown_instructions, $source, $tags, $custom_data,
					$server_id);
				break;
		}
	}
	else {
		switch ($config["dbtype"]) {
			case "mysql":
				$sql = sprintf ('
					INSERT INTO ' . $table_events . ' (id_agente, id_grupo, evento,
						timestamp, estado, utimestamp, id_usuario,
						event_type, criticity, id_agentmodule, id_alert_am,
						critical_instructions, warning_instructions,
						unknown_instructions, source, tags, custom_data) 
					VALUES (%d, %d, "%s", NOW(), %d, UNIX_TIMESTAMP(NOW()),
						"%s", "%s", %d, %d, %d, "%s", "%s", "%s", "%s", "%s", "%s")',
					$id_agent, $id_group, $event, $status, $id_user,
					$event_type, $priority, $id_agent_module, $id_aam,
					$critical_instructions, $warning_instructions,
					$unknown_instructions, $source, $tags, $custom_data);
				break;
			case "postgresql":
				$sql = sprintf ('
					INSERT INTO ' . $table_events . ' (id_agente, id_grupo, evento,
						timestamp, estado, utimestamp, id_usuario,
						event_type, criticity, id_agentmodule, id_alert_am,
						critical_instructions, warning_instructions,
						unknown_instructions, source, tags, custom_data) 
					VALUES (%d, %d, "%s", NOW(), %d,
						ceil(date_part(\'epoch\', CURRENT_TIMESTAMP)), "%s",
						"%s", %d, %d, %d, "%s", "%s", "%s", "%s", "%s", "%s")',
					$id_agent, $id_group, $event, $status, $id_user,
					$event_type, $priority, $id_agent_module, $id_aam,
					$critical_instructions, $warning_instructions,
					$unknown_instructions, $source, $tags, $custom_data);
				break;
			case "oracle":
				$sql = sprintf ("
					INSERT INTO " . $table_events . " (id_agente, id_grupo, evento,
						timestamp, estado, utimestamp, id_usuario,
						event_type, criticity, id_agentmodule, id_alert_am,
						critical_instructions, warning_instructions,
						unknown_instructions, source, tags, custom_data) 
					VALUES (%d, %d, '%s', CURRENT_TIMESTAMP, %d, UNIX_TIMESTAMP,
						'%s', '%s', %d, %d, %d, '%s', '%s', '%s', '%s', '%s', '%s')",
					$id_agent, $id_group, $event, $status, $id_user,
					$event_type, $priority, $id_agent_module, $id_aam,
					$critical_instructions, $warning_instructions,
					$unknown_instructions, $source, $tags, $custom_data);
				break;
		}
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
 * @param int agent id if is the table of one agent. 0 otherwise
 * 
 * @return string HTML with table element 
 */
function events_print_event_table ($filter = "", $limit = 10, $width = 440, $return = false, $agent_id = 0, $tactical_view = false) {
	global $config;
	
	if ($agent_id == 0) {
		$agent_condition = '';
	}
	else {
		$agent_condition = " id_agente = $agent_id AND ";
	}
	
	if ($filter == '') {
		$filter = '1 = 1';
	}
	
	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":
				$sql = sprintf ("SELECT *
					FROM tevento
					WHERE %s %s
					ORDER BY utimestamp DESC LIMIT %d", $agent_condition, $filter, $limit);
			break;
		case "oracle":
				$sql = sprintf ("SELECT *
					FROM tevento
					WHERE %s %s AND rownum <= %d
					ORDER BY utimestamp DESC", $agent_condition, $filter, $limit);
			break;
	}
	
	$result = db_get_all_rows_sql ($sql);
	
	if ($result === false) {
		if ($return) {
			$returned = ui_print_info_message (__('No events'), '', true);
			return $returned;
		}
		else {
			echo ui_print_info_message (__('No events'));
		}
	}
	else {
		$table = new stdClass();
		$table->id = 'latest_events_table';
		$table->cellpadding = 0;
		$table->cellspacing = 0;
		$table->width = $width;
		$table->class = "databox data";
		if (!$tactical_view)
			$table->title = __('Latest events');
		$table->titleclass = 'tabletitle';
		$table->titlestyle = 'text-transform:uppercase;';
		$table->headclass = array ();
		$table->head = array ();
		$table->rowclass = array ();
		$table->cellclass = array ();
		$table->data = array ();
		$table->align = array ();
		$table->style[0] = $table->style[1] = $table->style[2] = 'width:25px;';
		if ($agent_id == 0) {
			$table->style[3] = 'word-break: break-all;';
		}
		$table->style[4] = 'width:120px; word-break: break-all;';
		
		$table->head[0] = "<span title='" . __('Validated') . "'>" . __('V.') . "</span>";
		$table->align[0] = 'center';
		
		$table->head[1] = "<span title='" . __('Severity') . "'>" . __('S.') . "</span>";
		$table->align[1] = 'center';
		
		$table->head[2] = __('Type');
		$table->headclass[2] = "datos3 f9";
		$table->align[2] = "center";
		
		$table->head[3] = __('Event name');
		
		if ($agent_id == 0) {
			$table->head[4] = __('Agent name');
			$table->size[4] = "15%";
		}
		
		$table->head[5] = __('Timestamp');
		$table->headclass[5] = "datos3 f9";
		$table->align[5] = "left";
		$table->size[5] = "15%";
		
		foreach ($result as $event) {
			if (! check_acl ($config["id_user"], $event["id_grupo"], "ER")) {
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
					"title" => $title));
			
			switch ($event["criticity"]) {
				default:
				case EVENT_CRIT_MAINTENANCE: 
					$img = "images/status_sets/default/severity_maintenance.png";
					break;
				case EVENT_CRIT_INFORMATIONAL:
					$img = "images/status_sets/default/severity_informational.png";
					break;
				case EVENT_CRIT_NORMAL:
					$img = "images/status_sets/default/severity_normal.png";
					break;
				case EVENT_CRIT_WARNING:
					$img = "images/status_sets/default/severity_warning.png";
					break;
				case EVENT_CRIT_CRITICAL:
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
			
			/* Event text */
			$data[3] = ui_print_string_substr (io_safe_output($event["evento"]), 75, true, '7.5');
			
			if($agent_id == 0) {
				if ($event["id_agente"] > 0) {
					// Agent name
					// Get class name, for the link color...
					$myclass =  get_priority_class ($event["criticity"]);
					
					$data[4] = "<a class='$myclass' href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=".$event["id_agente"]."'>".
								agents_get_name ($event["id_agente"]). "</A>";
					
				// ui_print_agent_name ($event["id_agente"], true, 25, '', true);
				// for System or SNMP generated alerts
				}
				elseif ($event["event_type"] == "system") {
					$data[4] = __('System');
				}
				else {
					$data[4] = __('Alert')."SNMP";
				}
			}
			
			// Timestamp
			$data[5] = ui_print_timestamp ($event["timestamp"], true, array('style' => 'font-size: 7.5pt; letter-spacing: 0.3pt;'));
			
			$class = get_priority_class ($event["criticity"]);
			$cell_classes[3] = $cell_classes[4] = $cell_classes[5] = $class;
			array_push ($table->cellclass, $cell_classes);
			//array_push ($table->rowclass, get_priority_class ($event["criticity"]));
			array_push ($table->data, $data);
		}
		
		$events_table = html_print_table ($table, true);
		$out = '<table width="100%"><tr><td style="width: 90%; vertical-align: top; padding-top: 0px;">';
		$out .= $events_table;
		
		if (!$tactical_view) {
			if ($agent_id != 0) {
				$out .= '</td><td style="width: 200px; vertical-align: top;">';
				$out .= '<table cellpadding=0 cellspacing=0 class="databox"><tr><td>';
				$out .= '<fieldset class="databox tactical_set">
						<legend>' . 
							__('Events -by module-') . 
						'</legend>' . 
						graph_event_module (180, 100, $event['id_agente']) . '</fieldset>';
				$out .= '</td></tr></table>';
			}
			else {
				$out .= '</td><td style="width: 200px; vertical-align: top;">';
				$out .= '<table cellpadding=0 cellspacing=0 class="databox"><tr><td>';
				$out .= '<fieldset class="databox tactical_set">
						<legend>' . 
							__('Event graph') . 
						'</legend>' . 
						grafico_eventos_total("", 180, 60) . '</fieldset>';
				$out .= '<fieldset class="databox tactical_set">
						<legend>' . 
							__('Event graph by agent') . 
						'</legend>' . 
						grafico_eventos_grupo(180, 60) . '</fieldset>';
				$out .= '</td></tr></table>';
			}
		}
		$out .= '</td></tr></table>';
		
		unset ($table);
		
		if ($return) {
			return $out;
		}
		else {
			echo $out;
		}
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
			$icon = "bell.png";
			break;
		case "alert_manual_validation":
			$icon = "ok.png";
			break;
		case "going_down_critical":
		case "going_up_critical": //This is to be backwards compatible
			$icon = "module_critical.png";
			break;
		case "going_up_normal":
		case "going_down_normal": //This is to be backwards compatible
			$icon = "module_ok.png";
			break;
		case "going_up_warning":
		case "going_down_warning":
			$icon = "module_warning.png";
			break;
		case "going_unknown":
			$icon = "module_unknown.png";
			break;
		case "alert_fired":
			$icon = "bell_error.png";
			break;
		case "system":
			$icon = "cog.png";
			break;
		case "recon_host_detected":
			$icon = "recon.png";
			break;
		case "new_agent":
			$icon = "agent.png";
			break;
		case "configuration_change":
			$icon = "config.png";
			break;
		case "unknown": 
		default:
			$icon = "lightning_go.png";
			break;
	}
	
	if ($only_url) {
		$output = $urlImage . "/" . "images/" . $icon;
	}
	else {
		$output .= html_print_image ("images/" . $icon, true,
			array ("title" => events_print_type_description($type, true)));
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
function events_get_group_events ($id_group, $period, $date,
	$filter_event_validated = false, $filter_event_critical = false,
	$filter_event_warning = false, $filter_event_no_validated = false,
	$filter_event_search = false, $meta = false) {
	
	global $config;
	
	$id_group = groups_safe_acl ($config["id_user"], $id_group, "ER");
	
	if (empty ($id_group)) {
		//An empty array means the user doesn't have access
		return false;
	}
	
	$datelimit = $date - $period;
	
	$sql_where = ' AND 1 = 1 ';
	$criticities = array();
	if ($filter_event_critical) {
		$criticities[] = 4;
	}
	if ($filter_event_warning) {
		$criticities[] = 3;
	}
	if (!empty($criticities)) {
		$sql_where .= ' AND criticity IN (' . implode(', ', $criticities) . ')';
	}
	if ($filter_event_validated) {
		$sql_where .= ' AND estado = 1 ';
	}
	if ($filter_event_no_validated) {
		$sql_where .= ' AND estado = 0 ';
	}
	
	if (!empty($filter_event_search)) {
		$sql_where .= ' AND (evento LIKE "%'. io_safe_input($filter_event_search) . '%"'.
			' OR id_evento LIKE "%' . io_safe_input($filter_event_search) . '%")';
	}
	
	$sql_where .= sprintf('
		AND id_grupo IN (%s)
		AND utimestamp > %d
		AND utimestamp <= %d ',
		implode (",", $id_group), $datelimit, $date);
	
	return events_get_events_grouped($sql_where, 0, 1000, $meta);
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
function events_get_group_events_steps ($begin, &$result, $id_group, $period, $date,
	$filter_event_validated = false, $filter_event_critical = false,
	$filter_event_warning = false, $filter_event_no_validated = false) {
	
	global $config;
	
	$id_group = groups_safe_acl ($config["id_user"], $id_group, "ER");
	
	if (empty ($id_group)) {
		//An empty array means the user doesn't have access
		return false;
	}
	
	$datelimit = $date - $period;
	
	$sql_where = ' AND 1 = 1 ';
	$criticities = array();
	if ($filter_event_critical) {
		$criticities[] = 4;
	}
	if ($filter_event_warning) {
		$criticities[] = 3;
	}
	if (!empty($criticities)) {
		$sql_where .= ' AND criticity IN (' . implode(', ', $criticities) . ')';
	}
	if ($filter_event_validated) {
		$sql_where .= ' AND estado = 1 ';
	}
	if ($filter_event_no_validated) {
		$sql_where .= ' AND estado = 0 ';
	}
	
	
	$sql = sprintf ('SELECT *,
			(SELECT t2.nombre
				FROM tagente t2
				WHERE t2.id_agente = t3.id_agente) AS agent_name,
			(SELECT t2.fullname
				FROM tusuario t2
				WHERE t2.id_user = t3.id_usuario) AS user_name
		FROM tevento t3
		WHERE utimestamp > %d AND utimestamp <= %d
			AND id_grupo IN (%s) ' . $sql_where . '
		ORDER BY utimestamp ASC',
		$datelimit, $date, implode (",", $id_group));
	
	return db_get_all_row_by_steps_sql($begin, $result, $sql);
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
	
	$sql_where = '';
	
	$criticities = array();
	if ($filter_event_critical) {
		$criticities[] = 4;
	}
	if ($filter_event_warning) {
		$criticities[] = 3;
	}
	if (!empty($criticities)) {
		$sql_where .= ' AND criticity IN (' . implode(', ', $criticities) . ')';
	}
	
	if ( $filter_event_validated && $filter_event_no_validated ) {
		$sql_where .= " AND (estado = 1 OR estado = 0)";
	}
	else {
		if ($filter_event_validated) {
			$sql_where .= ' AND estado = 1 ';
		} else {
			if ($filter_event_no_validated) {
				$sql_where .= ' AND estado = 0 ';
			}
		}
	}
	
	$sql_where .= sprintf(' AND id_agente = %d AND utimestamp > %d
			AND utimestamp <= %d ', $id_agent, $datelimit, $date);
	
	return events_get_events_grouped($sql_where, 0, 1000, is_metaconsole());
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
	
	$sql_where = sprintf(' AND id_agentmodule = %d AND utimestamp > %d
			AND utimestamp <= %d ', $id_agent_module, $datelimit, $date);
	
	return events_get_events_grouped($sql_where, 0, 1000);
	
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
function events_get_event_types ($type_id) {
	
	$diferent_types = get_event_types ();
	
	$type_desc = '';
	switch ($type_id) {
		case 'unknown':
			$type_desc = __('Unknown');
			break;
		case 'critical':
			$type_desc = __('Monitor Critical');
			break;
		case 'warning':
			$type_desc = __('Monitor Warning');
			break;
		case 'normal':
			$type_desc = __('Monitor Normal');
			break;
		case 'alert_fired':
			$type_desc = __('Alert fired');
			break;
		case 'alert_recovered':
			$type_desc = __('Alert recovered');
			break;
		case 'alert_ceased':
			$type_desc = __('Alert ceased');
			break;
		case 'alert_manual_validation':
			$type_desc = __('Alert manual validation');
			break;
		case 'recon_host_detected':
			$type_desc = __('Recon host detected');
			break;
		case 'system':
			$type_desc = __('System');
			break;
		case 'error':
			$type_desc = __('Error');
			break;
		case 'configuration_change':
			$type_desc = __('Configuration change');
			break;
		case 'not_normal':
			$type_desc = __('Not normal');
			break;
		default:
			if (isset($config['text_char_long'])) {
				foreach ($diferent_types as $key => $type) {
					if ($key == $type_id) {
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
function events_get_severity_types ($severity_id) {
	
	$diferent_types = get_priorities ();
	
	$severity_desc = '';
	switch ($severity_id) {
		case EVENT_CRIT_MAINTENANCE:
			$severity_desc = __('Maintenance');
			break;
		case EVENT_CRIT_INFORMATIONAL:
			$severity_desc = __('Informational');
			break;
		case EVENT_CRIT_NORMAL:
			$severity_desc = __('Normal');
			break;
		case EVENT_CRIT_WARNING:
			$severity_desc = __('Warning');
			break;
		case EVENT_CRIT_CRITICAL:
			$severity_desc = __('Critical');
			break;
		default:
			if (isset($config['text_char_long'])) {
				foreach ($diferent_types as $key => $type) {
					if ($key == $severity_id) {
						$severity_desc = ui_print_truncate_text($type,
							$config['text_char_long'], false, true, false);
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
function events_get_all_status () {
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

/**
 * Checks if a user has permissions to see an event filter.
 *
 * @param int $id_filter Id of the event filter.
 *
 * @return bool True if the user has permissions or false otherwise.
 */
function events_check_event_filter_group ($id_filter) {
	global $config;
	
	$id_group = db_get_value('id_group_filter', 'tevent_filter', 'id_filter', $id_filter);
	$own_info = get_user_info ($config['id_user']);
	// Get group list that user has access
	$groups_user = users_get_groups ($config['id_user'], "EW", $own_info['is_admin'], true);
	
	// Permissions in any group allow to edit "All group" filters
	if($id_group == 0 && !empty($groups_user)) {
		return true;
	}
	
	$groups_id = array();
	$has_permission = false;
	
	foreach ($groups_user as $key => $groups) {
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
	return array('_agent_address_' => __('Agent address'),
		'_agent_id_' => __('Agent id'),
		'_event_id_' => __('Event id'),
		'_module_address_' => __('Module Agent address'),);
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
	
	if (empty($id_filter)) {
		return false;
	}
	
	if (! is_array ($filter)) {
		$filter = array ();
		$filter['id_filter'] = (int) $id_filter;
	}
	
	return db_get_row_filter ('tevent_filter', $filter, $fields);
}

/**
 *  Get a event filters in select format.
 *
 * @param boolean If event filters are used for manage/view operations (non admin users can see group ALL for manage) # Fix
 * @return array A event filter matching id and filter or false.
 */
function events_get_event_filter_select($manage = true) {
	global $config;
	
	$strict_acl = db_get_value('strict_acl', 'tusuario', 'id_user', $config['id_user']);
	
	if ($strict_acl) {
		$user_groups = users_get_strict_mode_groups($config['id_user'],
			users_can_manage_group_all());
	}
	else {
		$user_groups = users_get_groups ($config['id_user'], "EW",
			users_can_manage_group_all(), true);
	}
	
	if(empty($user_groups)) {
		return array();
	}
	$sql = "
		SELECT id_filter, id_name
		FROM tevent_filter
		WHERE id_group_filter IN (" . implode(',', array_keys ($user_groups)) . ")";
	
	$event_filters = db_get_all_rows_sql($sql);
	
	if ($event_filters === false) {
		return array();
	}
	else {
		$result = array();
		foreach ($event_filters as $event_filter) {
			$result[$event_filter['id_filter']] = $event_filter['id_name'];
		}
	}
	
	return $result;
}


// Events pages functions to load modal window with advanced view of an event.
// Called from include/ajax/events.php

function events_page_responses ($event, $childrens_ids = array()) {
	global $config;
	/////////
	// Responses
	/////////
	
	$table_responses->cellspacing = 2;
	$table_responses->cellpadding = 2;
	$table_responses->id = 'responses_table';
	$table_responses->width = '100%';
	$table_responses->data = array ();
	$table_responses->head = array ();
	$table_responses->style[0] = 'width:35%; font-weight: bold; text-align: left; height: 23px;';
	$table_responses->style[1] = 'text-align: left; height: 23px; text-align: right;';
	$table_responses->class = "alternate rounded_cells";
	
	if (tags_checks_event_acl ($config["id_user"], $event["id_grupo"], "EM", $event['clean_tags'], $childrens_ids)) {
		// Owner
		$data = array();
		$data[0] = __('Change owner');
		// Owner change can be done to users that belong to the event group with ER permission
		$profiles_view_events = db_get_all_rows_filter('tperfil', array('event_view' => '1'), 'id_perfil');
		foreach($profiles_view_events as $k => $v) {
			$profiles_view_events[$k] = reset($v);
		}
		// Juanma (05/05/2014) Fix : Propagate ACL hell!
		$_user_groups = array_keys(users_get_groups($config['id_user'], 'ER', users_can_manage_group_all()));
		$strict_user = db_get_value('strict_acl', 'tusuario', 'id_user', $config['id_user']);
		if ($strict_user) {
			$user_name = db_get_value('fullname', 'tusuario', 'id_user', $config['id_user']);

			$users = array();
			$users[0]['id_user'] = $config['id_user'];
			$users[0]['fullname'] = $user_name;
		} else {
			$users = groups_get_users($_user_groups, array('id_perfil' => $profiles_view_events), true, true);
		}
	
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
	}
	
	// Status
	$data = array();
	$data[0] = __('Change status');
	
	$status_blocked = false;
	
	if (tags_checks_event_acl ($config["id_user"], $event["id_grupo"], "EM", $event['clean_tags'], $childrens_ids)) {
		// If the user has manager acls, the status can be changed to all possibilities always
		$status = array(0 => __('New'), 2 => __('In process'), 1 => __('Validated'));
	}
	else {
		switch($event['estado']) {
			case 0:
				// If the user hasnt manager acls and the event is new. The status can be changed
				$status = array(2 => __('In process'), 1 => __('Validated'));
				break;
			case 1:
				// If the user hasnt manager acls and the event is validated. The status cannot be changed
				$status = array(1 => __('Validated'));
				$status_blocked = true;
				break;
			case 2:
				// If the user hasnt manager acls and the event is in process. The status only can be changed to validated
				$status = array(1 => __('Validated'));
				break;
		}
		
	}
	
	// The change status option will be enabled only when is possible change the status
	$data[1] = html_print_select($status, 'estado', $event['estado'], '', '', 0, true, false, false, '', $status_blocked);
	
	if(!$status_blocked) {
		$data[1] .= html_print_button(__('Update'),'status_button',false,'event_change_status(\''.$event['similar_ids'] .'\');','class="sub next"',true);
	}
	
	$table_responses->data[] = $data;
	
	// Comments
	$data = array();
	$data[0] = __('Comment');
	$data[1] = html_print_button(__('Add comment'),'comment_button',false,'$(\'#link_comments\').trigger(\'click\');','class="sub next"',true);
	
	$table_responses->data[] = $data;
	
	if (tags_checks_event_acl($config["id_user"], $event["id_grupo"], "EM", $event['clean_tags'], $childrens_ids)) {
		// Delete
		$data = array();
		$data[0] = __('Delete event');
		$data[1] = '<form method="post">';
		$data[1] .= html_print_button(__('Delete event'),'delete_button',false,'if(!confirm(\''.__('Are you sure?').'\')) { return false; } this.form.submit();','class="sub cancel"',true);
		$data[1] .= html_print_input_hidden('delete', 1, true);
		$data[1] .= html_print_input_hidden('validate_ids', $event['id_evento'], true);
		$data[1] .= '</form>';
		
		$table_responses->data[] = $data;
	}
	
	// Custom responses
	$data = array();
	$data[0] = __('Custom responses');
	
	$id_groups = array_keys(users_get_groups(false, "EW"));
	$event_responses = db_get_all_rows_filter('tevent_response',
		array('id_group' => $id_groups));
	
	if (empty($event_responses)) {
		$data[1] = '<i>'.__('N/A').'</i>';
	}
	else {
		$responses = array();
		foreach ($event_responses as $v) {
			$responses[$v['id']] = $v['name'];
		}
		$data[1] = html_print_select(
			$responses,
			'select_custom_response','','','','',true, false, false);
		
		if (isset($event['server_id'])) {
			$server_id = $event['server_id'];
		}
		else {
			$server_id = 0;
		}
		
		$data[1] .= html_print_button(__('Execute'),'custom_response_button',false,'execute_response('.$event['id_evento'].','.$server_id.')',"class='sub next'",true);
	}
	
	$table_responses->data[] = $data;
	
	
	$responses_js = "<script>
			$('#select_custom_response').change(function() {
				var id_response = $('#select_custom_response').val();
				var params = get_response_params(id_response);
				var description = get_response_description(id_response);
				$('.params_rows').remove();
				
				$('#responses_table')
					.append('<tr class=\"params_rows\"><td style=\"text-align:left; font-weight: bolder;\">".__('Description')."</td><td style=\"text-align:left;\">'+description+'</td></tr>');
				
				if (params.length == 1 && params[0] == '') {
					return;
				}
				
				$('#responses_table')
					.append('<tr class=\"params_rows\"><td style=\"text-align:left; padding-left:20px;\" colspan=\"2\">".__('Parameters')."</td></tr>');
				
				for (i = 0; i < params.length; i++) {
					add_row_param('responses_table',params[i]);
				}
			});
			$('#select_custom_response').trigger('change');
			</script>";
	
	$responses = '<div id="extended_event_responses_page" class="extended_event_pages">' .
		html_print_table($table_responses, true) .
		$responses_js .
		'</div>';
	
	return $responses;
}

// Replace macros in the target of a response and return it
// If server_id > 0, is a metaconsole query
function events_get_response_target($event_id, $response_id, $server_id, $history = false) {
	global $config;
	
	$event_response = db_get_row('tevent_response','id',$response_id);
	
	if ($server_id > 0) {
		$meta = true;
	}
	else {
		$meta = false;
	}
	
	$event_table = events_get_events_table($meta, $history);
	
	$event = db_get_row($event_table,'id_evento', $event_id);
	
	$macros = array_keys(events_get_macros());
	
	$target = io_safe_output($event_response['target']);
	
	foreach($macros as $macro) {
		$subst = '';
		switch($macro) {
			case '_agent_address_':
				if ($meta) {
					$server = metaconsole_get_connection_by_id ($server_id);
					metaconsole_connect($server);
				}
				
				$subst = agents_get_address($event['id_agente']);
				
				if($meta) {
					metaconsole_restore_db_force();
				}
				break;
			case '_agent_id_':
				$subst = $event['id_agente'];
				break;
			case '_event_id_':
				$subst = $event['id_evento'];
				break;
			case '_module_address_':
				if($meta) {
					$server = metaconsole_get_connection_by_id ($server_id);
					metaconsole_connect($server);
				}

				$module = db_get_row("tagente_modulo",'id_agente_modulo', $event['id_agentmodule']);
				if ($module['ip_target'] != false)
					$subst = $module['ip_target'];
				
				if($meta) {
					metaconsole_restore_db_force();
				}
				break;
		}
		
		$target = str_replace($macro,$subst,$target);
	}
	
	return $target;
}

function events_page_custom_fields ($event) {
	global $config;
	
	////////////////////////////////////////////////////////////////////
	// Custom fields
	////////////////////////////////////////////////////////////////////
	
	$table->cellspacing = 2;
	$table->cellpadding = 2;
	$table->width = '100%';
	$table->data = array ();
	$table->head = array ();
	$table->style[0] = 'width:35%; font-weight: bold; text-align: left; height: 23px;';
	$table->style[1] = 'text-align: left; height: 23px;';
	$table->class = "alternate rounded_cells";
	
	$all_customs_fields = (bool)check_acl($config["id_user"],
	$event["id_grupo"], "AW");
	
	if ($all_customs_fields) {
		$fields = db_get_all_rows_filter('tagent_custom_fields');
	}
	else {
		$fields = db_get_all_rows_filter('tagent_custom_fields',
			array('display_on_front' => 1));
	}
	
	if ($event['id_agente'] == 0) {
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
	
	foreach ($fields as $field) {
		// Owner
		$data = array();
		$data[0] = $field['name'];
		
		$data[1] = empty($fields_data[$field['id_field']])
			? '<i>'.__('N/A').'</i>'
			: ui_bbcode_to_html($fields_data[$field['id_field']]);
		
		$field['id_field'];
		
		$table->data[] = $data;
	}
	
	$custom_fields = '<div id="extended_event_custom_fields_page" class="extended_event_pages">'.html_print_table($table, true).'</div>';
	
	return $custom_fields;
}

function events_page_details ($event, $server = "") {
	global $img_sev;
	global $config;
	
	// If server is provided, get the hash parameters
	if (!empty($server) && defined("METACONSOLE")) { 
		$hashdata = metaconsole_get_server_hashdata($server);
		$hashstring = "&amp;" .
			"loginhash=auto&" .
			"loginhash_data=" . $hashdata . "&" .
			"loginhash_user=" . str_rot13($config["id_user"]);
		$serverstring = $server['server_url'] . "/";
		
		if (metaconsole_connect($server) !== NOERR) {
			return ui_print_error_message(__('There was an error connecting to the node'), '', true);
		}
	}
	else {
		$hashstring = "";
		$serverstring = "";
	}
	
	////////////////////////////////////////////////////////////////////
	// Details
	////////////////////////////////////////////////////////////////////
	
	$table_details->width = '100%';
	$table_details->data = array ();
	$table_details->head = array ();
	$table_details->cellspacing = 2;
	$table_details->cellpadding = 2;
	$table_details->style[0] = 'width:35%; font-weight: bold; text-align: left; height: 23px;';
	$table_details->style[1] = 'text-align: left; height: 23px;';
	$table_details->class = "alternate rounded_cells";
	
	switch ($event['event_type']) {
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
		if (can_user_access_node ()) {
			$data[1] = ui_print_agent_name ($event["id_agente"], true, 'agent_medium', '', false, $serverstring, $hashstring, $agent['nombre']);
		}
		else {
			$data[1] = ui_print_truncate_text($agent['nombre'], 'agent_medium', true, true, true);
		}
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
		$data[1] = $module['nombre'];
		$table_details->data[] = $data;
		
		// Module group
		$data = array();
		$data[0] = '<div style="font-weight:normal; margin-left: 20px;">' .
			__('Module group') . '</div>';
		$id_module_group = $module['id_module_group'];
		if ($id_module_group == 0) {
			$data[1] = __('No assigned');
		}
		else {
			$module_group = db_get_value('name', 'tmodule_group', 'id_mg', $id_module_group);
			$data[1] = '<a href="'.$serverstring . 'index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;status=-1&amp;modulegroup=' . $id_module_group . $hashstring.'">';
			$data[1] .= $module_group;
			$data[1] .= '</a>';
		}
		$table_details->data[] = $data;
		
		// ACL
		$acl_graph = false;
		$strict_user = (bool) db_get_value("strict_acl", "tusuario", "id_user", $config['id_user']);
		
		if (!empty($agent['id_grupo'])) {
			if ($strict_user) {
				$acl_graph = tags_check_acl_by_module($module["id_agente_modulo"], $config['id_user'], 'RR') === true;
			}
			else {
				$acl_graph = check_acl($config['id_user'], $agent['id_grupo'], "RR");
			}
		}
		
		if ($acl_graph) {
			$data = array();
			$data[0] = '<div style="font-weight:normal; margin-left: 20px;">'.__('Graph').'</div>';
			
			$module_type = -1;
			if (isset($module["module_type"])) {
				$module_type = $module["module_type"];
			}
			$graph_type = return_graphtype ($module_type);
			$url = ui_get_full_url("operation/agentes/stat_win.php", false, false, false);
			$handle = dechex(crc32($module["id_agente_modulo"].$module["nombre"]));
			$win_handle = "day_$handle";
			
			$graph_params = array(
					"type" => $graph_type,
					"period" => SECONDS_1DAY,
					"id" => $module["id_agente_modulo"],
					"label" => rawurlencode(urlencode(base64_encode($module["nombre"]))),
					"refresh" => SECONDS_10MINUTES
				);
			
			if (defined('METACONSOLE')) {
				$graph_params["avg_only"] = 1;
				// Set the server id
				$graph_params["server"] = $server["id"];
			}
			
			$graph_params_str = http_build_query($graph_params);
			
			$link = "winopeng('$url?$graph_params_str','$win_handle')";
			
			$data[1] = '<a href="javascript:'.$link.'">';
			$data[1] .= html_print_image('images/chart_curve.png',true);
			$data[1] .= '</a>';
			$table_details->data[] = $data;
		}
	}
	
	$data = array();
	$data[0] = __('Alert details');
	$data[1] = $event["id_alert_am"] == 0 ? '<i>' . __('N/A') . '</i>' : '';
	$table_details->data[] = $data;
	
	if ($event["id_alert_am"] != 0) {
		$data = array();
		$data[0] = '<div style="font-weight:normal; margin-left: 20px;">'.__('Source').'</div>';
		$data[1] = '<a href="'.$serverstring.'index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$event["id_agente"].'&amp;tab=alert'.$hashstring.'">';
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
				$data[1] = str_replace("\n","<br>", io_safe_output($event["unknown_instructions"]));
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
				$data[1] = str_replace("\n","<br>", io_safe_output($event["warning_instructions"]));
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
				$data[1] = str_replace("\n","<br>", io_safe_output($event["critical_instructions"]));
			}
			else {
				$data[1] = '<i>' . __('N/A') . '</i>';
			}
			$table_details->data[] = $data;
			break;
		case 'system':
			$data = array();
			if ($event["critical_instructions"] != '') {
				$data[0] = __('Instructions');
				$data[1] = str_replace("\n","<br>", io_safe_output($event["critical_instructions"]));
			}
			else {
				if ($event["warning_instructions"] != '') {
					$data[0] = __('Instructions');
					$data[1] = str_replace("\n","<br>", io_safe_output($event["warning_instructions"]));
				}
				else {
					if ($event["unknown_instructions"] != '') {
						$data[0] = __('Instructions');
						$data[1] = str_replace("\n","<br>", io_safe_output($event["unknown_instructions"]));
					}
					else {
						$data[0] = __('Instructions');
						$data[1] = '<i>' . __('N/A') . '</i>';
						
					}
				}
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
	
	if (!empty($server) && defined("METACONSOLE"))
		metaconsole_restore_db();
	
	return $details;
}

function events_page_custom_data ($event) {
	global $config;
	
	////////////////////////////////////////////////////////////////////
	// Custom data
	////////////////////////////////////////////////////////////////////
	if ($event['custom_data'] == '') {
		return '';
	}
	
	$table->width = '100%';
	$table->data = array ();
	$table->head = array ();
	$table->style[0] = 'width:35%; font-weight: bold; text-align: left;';
	$table->style[1] = 'text-align: left;';
	$table->class = "alternate rounded_cells";
	
	$json_custom_data = base64_decode ($event['custom_data']);
	$custom_data = json_decode ($json_custom_data);
	if ($custom_data === NULL) {
		return '<div id="extended_event_custom_data_page" class="extended_event_pages">'.__('Invalid custom data: %s', $json_custom_data).'</div>';
	}
	
	$i = 0;
	foreach ($custom_data as $field => $value) {
		$table->data[$i][0] = io_safe_output ($field);
		$table->data[$i][1] = io_safe_output ($value);
		$i++;
	}
	
	$custom_data = '<div id="extended_event_custom_data_page" class="extended_event_pages">'.html_print_table($table, true).'</div>';
	
	return $custom_data;
}

function events_page_general ($event) {
	global $img_sev;
	global $config;
	
	//$group_rep = $event['similar_ids'] == -1 ? 1 : count(explode(',',$event['similar_ids']));
	global $group_rep;
	
	////////////////////////////////////////////////////////////////////
	// General
	////////////////////////////////////////////////////////////////////
	$table_general->cellspacing = 2;
	$table_general->cellpadding = 2;
	$table_general->width = '100%';
	$table_general->data = array ();
	$table_general->head = array ();
	$table_general->style[0] = 'width:35%; font-weight: bold; text-align: left; height: 23px;';
	$table_general->style[1] = 'text-align: left; height: 23px;';
	$table_general->class = "alternate rounded_cells";
	
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
	if (empty($event["owner_user"])) {
		$data[1] = '<i>'.__('N/A').'</i>';
	}
	else {
		$user_owner = db_get_value('fullname', 'tusuario', 'id_user', $event["owner_user"]);
		if(empty($user_owner)) {
			$user_owner = $event['owner_user'];
		}
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
	
	if ($event['estado'] == 1) {
		$user_ack = db_get_value('fullname', 'tusuario', 'id_user', $event['id_usuario']);
		if(empty($user_ack)) {
			$user_ack = $event['id_usuario'];
		}
		$date_ack = date ($config["date_format"], $event['ack_utimestamp']);
		$data[1] = $user_ack.' ('.$date_ack.')';
	}
	else {
		$data[1] = '<i>'.__('N/A').'</i>';
	}
	$table_general->data[] = $data;
	
	$data = array();
	$data[0] = __('Group');
	$data[1] = "";
	if (!$config['show_group_name']) {
		$data[1] = ui_print_group_icon ($event["id_grupo"], true);
	}
	$data[1] .= groups_get_name ($event["id_grupo"]);
	$table_general->data[] = $data;
	
	$data = array();
	$data[0] = __('Tags');
	
	if ($event["tags"] != '') {
		$tags = tags_get_tags_formatted($event["tags"]);
		
		$data[1] = $tags;
	}
	else {
		$data[1] = '<i>' . __('N/A') . '</i>';
	}
	$table_general->data[] = $data;
	 
	$data = array();
	$data[0] = __('ID extra');
	if ($event["id_extra"] != '') {
		$data[1] = $event["id_extra"];
	}
	else {
		$data[1] = '<i>' . __('N/A') . '</i>';
	}
	$table_general->data[] = $data;

	$general = '<div id="extended_event_general_page" class="extended_event_pages">' .
		html_print_table($table_general,true) .
		'</div>';
	
	return $general;
}

function events_page_comments ($event, $childrens_ids = array()) {
	////////////////////////////////////////////////////////////////////
	// Comments
	////////////////////////////////////////////////////////////////////
	global $config;
	
	$table_comments->width = '100%';
	$table_comments->data = array ();
	$table_comments->head = array ();
	$table_comments->style[0] = 'width:35%; vertical-align: top; text-align: left;';
	$table_comments->style[1] = 'text-align: left;';
	$table_comments->class = "alternate rounded_cells";
	
	$event_comments = $event["user_comment"];
	$event_comments = str_replace( array("\n", '&#x0a;'), "<br>", $event_comments);
	
	// If comments are not stored in json, the format is old
	$event_comments_array = json_decode($event_comments, true);
	
	// Show the comments more recent first
	$event_comments_array = array_reverse($event_comments_array);
	
	if (is_null($event_comments_array)) {
		$comments_format = 'old';
	}
	else {
		$comments_format = 'new';
	}
	
	switch($comments_format) {
		case 'new':
			if (empty($event_comments_array)) {
				$table_comments->style[0] = 'text-align:center;';
				$table_comments->colspan[0][0] = 2;
				$data = array();
				$data[0] = __('There are no comments');
				$table_comments->data[] = $data;
			}
			
			foreach($event_comments_array as $c) {
				$data[0] = '<b>' . $c['action'] . ' by ' . $c['id_user'] . '</b>';
				$data[0] .= '<br><br><i>' . date ($config["date_format"], $c['utimestamp']) . '</i>';
				$data[1] = $c['comment'];
				$table_comments->data[] = $data;
			}
			break;
		case 'old':
			$comments_array = explode('<br>',$event_comments);

			// Split comments and put in table
			$col = 0;
			$data = array();
			
			foreach ($comments_array as $c) {
				switch ($col) {
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
			
			if (count($comments_array) == 1 && $comments_array[0] == '') {
				$table_comments->style[0] = 'text-align:center;';
				$table_comments->colspan[0][0] = 2;
				$data = array();
				$data[0] = __('There are no comments');
				$table_comments->data[] = $data;
			}
			break;
	}
	
	if ((tags_checks_event_acl($config["id_user"], $event["id_grupo"], "EM", $event['clean_tags'], $childrens_ids)) || (tags_checks_event_acl($config["id_user"], $event["id_grupo"], "EW", $event['clean_tags'],$childrens_ids))) {
		$comments_form = '<br><div id="comments_form" style="width:98%;">'.html_print_textarea("comment", 3, 10, '', 'style="min-height: 15px; width: 100%;"', true);
		$comments_form .= '<br><div style="text-align:right;">'.html_print_button(__('Add comment'),'comment_button',false,'event_comment();','class="sub next"',true).'</div><br></div>';
	}
	else {
		$comments_form = '';
	}
	
	$comments = '<div id="extended_event_comments_page" class="extended_event_pages">'.$comments_form.html_print_table($table_comments, true).'</div>';
	
	return $comments;
}

function events_clean_tags ($tags) {
	if(empty($tags)) {
		return array();
	}
	
	$event_tags = tags_get_tags_formatted ($tags, false);
	return explode(',',str_replace(' ','',$event_tags));
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
	$filter_event_warning = false, $filter_event_no_validated = false,
	$filter_event_search = false) {
	
	global $config;
	
	$id_group = groups_safe_acl ($config["id_user"], $id_group, "AR");
	
	if (empty ($id_group)) {
		//An empty array means the user doesn't have access
		return false;
	}
	
	$datelimit = $date - $period;
	
	$sql_where = ' AND 1 = 1 ';
	$criticities = array();
	if ($filter_event_critical) {
		$criticities[] = 4;
	}
	if ($filter_event_warning) {
		$criticities[] = 3;
	}
	if (!empty($criticities)) {
		$sql_where .= ' AND criticity IN (' . implode(', ', $criticities) . ')';
	}
	
	if ($filter_event_validated) {
		$sql_where .= ' AND estado = 1 ';
	}
	if ($filter_event_no_validated) {
		$sql_where .= ' AND estado = 0 ';
	}
	
	if (!empty($filter_event_search)) {
		$sql_where .= ' AND (evento LIKE "%%'. io_safe_input($filter_event_search) . '%%"'.
			' OR id_evento LIKE "%%' . io_safe_input($filter_event_search) . '%%")';
	}
	
	$sql = sprintf ('SELECT id_agente,
		(SELECT t2.nombre
			FROM tagente t2
			WHERE t2.id_agente = t3.id_agente) AS agent_name,
		COUNT(*) AS count
		FROM tevento t3
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
	$filter_event_warning = false, $filter_event_no_validated = false,
	$filter_event_search = false) {
	
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
	$criticities = array();
	if ($filter_event_critical) {
		$criticities[] = 4;
	}
	if ($filter_event_warning) {
		$criticities[] = 3;
	}
	if (!empty($criticities)) {
		$sql_where .= ' AND criticity IN (' . implode(', ', $criticities) . ')';
	}
	
	if ($filter_event_validated) {
		$sql_where .= ' AND estado = 1 ';
	}
	if ($filter_event_no_validated) {
		$sql_where .= ' AND estado = 0 ';
	}
	
	if (!empty($filter_event_search)) {
		$sql_where .= ' AND (evento LIKE "%%'. io_safe_input($filter_event_search) . '%%"'.
			' OR id_evento LIKE "%%' . io_safe_input($filter_event_search) . '%%")';
	}
	
	$sql = sprintf ('SELECT id_usuario,
		(SELECT t2.fullname
			FROM tusuario t2
			WHERE t2.id_user = t3.id_usuario) AS user_name,
		COUNT(*) AS count
		FROM tevento t3
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
	$filter_event_warning = false, $filter_event_no_validated = false,
	$filter_event_search = false) {
	
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
	$criticities = array();
	if ($filter_event_critical) {
		$criticities[] = 4;
	}
	if ($filter_event_warning) {
		$criticities[] = 3;
	}
	if (!empty($criticities)) {
		$sql_where .= ' AND criticity IN (' . implode(', ', $criticities) . ')';
	}
	
	if ($filter_event_validated) {
		$sql_where .= ' AND estado = 1 ';
	}
	if ($filter_event_no_validated) {
		$sql_where .= ' AND estado = 0 ';
	}
	
	if (!empty($filter_event_search)) {
		$sql_where .= ' AND (evento LIKE "%%'. io_safe_input($filter_event_search) . '%%"'.
			' OR id_evento LIKE "%%' . io_safe_input($filter_event_search) . '%%")';
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
function events_get_count_events_validated ($filter, $period = null, $date = null,
	$filter_event_validated = false, $filter_event_critical = false,
	$filter_event_warning = false, $filter_event_no_validated = false,
	$filter_event_search = false) {
	
	global $config;

	$sql_filter = " 1=1 ";
	if (isset($filter['id_group'])) {
		$id_group = groups_safe_acl ($config["id_user"], $filter['id_group'], "AR");
		
		if (empty ($id_group)) {
			//An empty array means the user doesn't have access
			return false;
		}

		$sql_filter .=
			sprintf(" AND id_grupo IN (%s) ", implode (",", $id_group));
	}
	if (!empty($filter['id_agent'])) {
		$sql_filter .=
			sprintf(" AND id_agente = %d ", $filter['id_agent']);
	}
	
	$date_filter = '';
	if (!empty($date) && !empty($period)) {
		$datelimit = $date - $period;

		$date_filter .= sprintf (" AND utimestamp > %d AND utimestamp <= %d ",
			$datelimit, $date);
	}
	else if (!empty($period)) {
		$date = time();
		$datelimit = $date - $period;

		$date_filter .= sprintf (" AND utimestamp > %d AND utimestamp <= %d ",
			$datelimit, $date);
	}
	else if (!empty($date)) {
		$date_filter .= sprintf (" AND utimestamp <= %d ", $date);
	}

	$sql_where = " AND 1=1 ";
	$criticities = array();
	if ($filter_event_critical) {
		$criticities[] = 4;
	}
	if ($filter_event_warning) {
		$criticities[] = 3;
	}
	if (!empty($criticities)) {
		$sql_where .= " AND criticity IN (" . implode(",", $criticities) . ")";
	}
	
	if ($filter_event_validated) {
		$sql_where .= " AND estado = 1 ";
	}
	if ($filter_event_no_validated) {
		$sql_where .= " AND estado = 0 ";
	}
	
	if (!empty($filter_event_search)) {
		$sql_where .= " AND (evento LIKE '%%" . io_safe_input($filter_event_search) . "%%'" .
			" OR id_evento LIKE '%%" . io_safe_input($filter_event_search) . "%%')";
	}

	$sql = sprintf ("SELECT estado, COUNT(*) AS count FROM tevento WHERE %s " . $sql_where . " GROUP BY estado", $sql_filter);

	$rows = db_get_all_rows_sql ($sql);
	
	if ($rows == false)
		$rows = array();
	
	$return = array_reduce($rows, function($carry, $item) {
		$status = (int) $item['estado'];
		$count = (int) $item['count'];
		
		if ($status === 1) {
			$carry[__('Validated')] += $count;
		}
		else if ($status === 0) {
			$carry[__('Not validated')] += $count;
		}
		
		return $carry;
		
	}, array(__('Validated') => 0, __('Not validated') => 0));
	
	return $return;
}

function events_checks_event_tags($event_data, $acltags) {
	global $config;
	
	if (empty($acltags[$event_data['id_grupo']])) {
			return true;
	} else {
		$tags_arr_acl = explode(',',$acltags[$event_data['id_grupo']]);
		$tags_arr_event = explode(',',$event_data['tags']);

		foreach ($tags_arr_acl as $tag) {
			$tag_name = tags_get_name($tag);
			if (in_array($tag_name, $tags_arr_event)) {
				return true;
			} else {
				$has_tag = false;
			}
		}
		if (!$has_tag) {
			return false;
		}
	}
	return false;
}

function events_get_events_grouped_by_agent($sql_post, $offset = 0,
	$pagination = 1, $meta = false, $history = false, $total = false) {
	global $config;
	
	$table = events_get_events_table($meta, $history);
	
	if ($meta) {
		$fields_extra = ', agent_name, server_id';
		$groupby_extra = ', server_id';
	}
	else {
		$groupby_extra = '';
		$fields_extra = '';
	}
	
	switch ($config["dbtype"]) {
		case "mysql":
			if ($total) {
				$sql = "SELECT COUNT(*) FROM (select id_agente from $table WHERE 1=1 
						$sql_post GROUP BY id_agente, event_type$groupby_extra ORDER BY id_agente ) AS t";
			}
			else {
				$sql = "select id_agente, count(*) as total$fields_extra from $table 
					WHERE id_agente > 0 $sql_post GROUP BY id_agente$groupby_extra ORDER BY id_agente LIMIT $offset,$pagination";
			}
			break;
		case 'postgresql':
			if ($total) {
				
			}
			else {
				$sql = "select id_agente, count(*) as total$fields_extra from $table 
					WHERE id_agente > 0 $sql_post GROUP BY id_agente$groupby_extra ORDER BY id_agente LIMIT $offset,$pagination";
			}
			break;
		case 'oracle':
			if ($total) {
				
			}
			else {
				$set = array();
				$set['limit'] = $pagination;
				$set['offset'] = $offset;
				
				$sql = "select id_agente, count(*) as total$fields_extra from $table 
					WHERE id_agente > 0 $sql_post GROUP BY id_agente, event_type$groupby_extra ORDER BY id_agente ";
				$sql = oracle_recode_query ($sql, $set);
			}
			break;
	}
	
	$result = array();
	//Extract the events by filter (or not) from db
	
	$events = db_get_all_rows_sql ($sql);
	$result = array();
	
	if ($events) {
		foreach ($events as $event) {
			
			if ($meta) {
				$sql = "select event_type from $table 
								WHERE agent_name = '".$event['agent_name']."' $sql_post ORDER BY utimestamp DESC ";
				$resultado = db_get_row_sql($sql);
				
				$id_agente = $event['agent_name'];
				$result[] = array('total' => $event['total'],
									'id_server' => $event['server_id'],
									'id_agent' => $id_agente,
									'event_type' => $resultado['event_type']);
			}
			else {
				$sql = "select event_type from $table 
					WHERE id_agente = ".$event['id_agente']." $sql_post ORDER BY utimestamp DESC ";
				$resultado = db_get_row_sql($sql);
				
				$id_agente = $event['id_agente'];
				$result[] = array('total' => $event['total'],
									'id_agent' => $id_agente,
									'event_type' => $resultado['event_type']);
			}
		}
	}
	return $result;
}

function events_sql_events_grouped_agents($id_agent, $server_id = -1, 
	$event_type = '', $severity = -1, $status = 3, $search = '', 
	$id_agent_module = 0, $event_view_hr = 8, $id_user_ack = false, 
	$tag_with = array(), $tag_without = array(), $filter_only_alert = false, 
	$date_from = '', $date_to = '', $id_user = false, $server_id_search = false) {
	global $config;
	
	$sql_post = ' 1 = 1 ';
	
	$meta = false;
	if (is_metaconsole())
		$meta = true;
	
	switch ($status) {
		case 0:
		case 1:
		case 2:
			$sql_post .= " AND estado = " . $status;
			break;
		case 3:
			$sql_post .= " AND (estado = 0 OR estado = 2)";
			break;
	}

	if ($search != "") {
		$sql_post .= " AND (evento LIKE '%". io_safe_input($search) . "%' OR id_evento LIKE '%$search%')";
	}

	if ($event_type != "") {
		// If normal, warning, could be several (going_up_warning, going_down_warning... too complex 
		// for the user so for him is presented only "warning, critical and normal"
		if ($event_type == "warning" || $event_type == "critical" || $event_type == "normal") {
			$sql_post .= " AND event_type LIKE '%$event_type%' ";
		}
		else if ($event_type == "not_normal") {
			$sql_post .= " AND (event_type LIKE '%warning%' OR event_type LIKE '%critical%' OR event_type LIKE '%unknown%') ";
		}
		else if ($event_type != "all") {
			$sql_post .= " AND event_type = '" . $event_type."'";
		}
	}

	if ($severity != -1) {
		switch ($severity) {
			case EVENT_CRIT_WARNING_OR_CRITICAL:
				$sql_post .= "
					AND (criticity = " . EVENT_CRIT_WARNING . " OR 
						criticity = " . EVENT_CRIT_CRITICAL . ")";
				break;
			case EVENT_CRIT_OR_NORMAL:
				$sql_post .= "
					AND (criticity = " . EVENT_CRIT_NORMAL . " OR 
						criticity = " . EVENT_CRIT_CRITICAL . ")";
				break;
			case EVENT_CRIT_NOT_NORMAL:
				$sql_post .= " AND criticity != " . EVENT_CRIT_NORMAL;
				break;
			default:
				$sql_post .= " AND criticity = $severity";
				break;
		}
	}

	// In metaconsole mode the agent search is performed by name
	if ($meta) {
		if ($id_agent != __('All')) {
			$sql_post .= " AND agent_name LIKE '%$id_agent%'";
		}
	}
	else {
		switch ($id_agent) {
			case 0:
				break;
			case -1:
				// Agent doesnt exist. No results will returned
				$sql_post .= " AND 1 = 0";
				break;
			default:
				$sql_post .= " AND id_agente = " . $id_agent;
				break;
		}
	}
	
	if ($meta) {
		//There is another filter.
	}
	else {
		if (!empty($text_module)) {
			$sql_post .= " AND id_agentmodule IN (
					SELECT id_agente_modulo
					FROM tagente_modulo
					WHERE nombre = '$text_module'
				)";
		}
	}

	if ($id_user_ack != "0")
		$sql_post .= " AND id_usuario = '" . $id_user_ack . "'";

	if (!isset($date_from)) {
		$date_from = "";
	}
	if (!isset($date_to)) {
		$date_to = "";
	}

	if (($date_from == '') && ($date_to == '')) {
		if ($event_view_hr > 0) {
			$unixtime = get_system_time () - ($event_view_hr * SECONDS_1HOUR);
			$sql_post .= " AND (utimestamp > " . $unixtime . ")";
		}
	}
	else {
		if ($date_from != '') {
			$udate_from = strtotime($date_from . " 00:00:00");
			$sql_post .= " AND (utimestamp >= " . $udate_from . ")";
		}
		if ($date_to != '') {
			$udate_to = strtotime($date_to . " 23:59:59");
			$sql_post .= " AND (utimestamp <= " . $udate_to . ")";
		}
	}

	//Search by tag
	if (!empty($tag_with)) {
		$sql_post .= ' AND ( ';
		$first = true;
		foreach ($tag_with as $id_tag) {
			if ($first) $first = false;
			else $sql_post .= " OR ";
			$sql_post .= "tags = '" . tags_get_name($id_tag) . "'";
		}
		$sql_post .= ' ) ';
	}
	if (!empty($tag_without)) {
		$sql_post .= ' AND ( ';
		$first = true;
		foreach ($tag_without as $id_tag) {
			if ($first) $first = false;
			else $sql_post .= " AND ";
			
			$sql_post .= "tags <> '" . tags_get_name($id_tag) . "'";
		}
		$sql_post .= ' ) ';
	}

	// Filter/Only alerts
	if (isset($filter_only_alert)) {
		if ($filter_only_alert == 0)
			$sql_post .= " AND event_type NOT LIKE '%alert%'";
		else if ($filter_only_alert == 1)
			$sql_post .= " AND event_type LIKE '%alert%'";
	}

	// Tags ACLS
	if ($id_group > 0 && in_array ($id_group, array_keys ($groups))) {
		$group_array = (array) $id_group;
	}
	else {
		$group_array = array_keys($groups);
	}

	$tags_acls_condition = tags_get_acl_tags($id_user, $group_array, 'ER',
		'event_condition', 'AND', '', $meta, array(), true); //FORCE CHECK SQL "(TAG = tag1 AND id_grupo = 1)"

	if (($tags_acls_condition != ERR_WRONG_PARAMETERS) && ($tags_acls_condition != ERR_ACL)&& ($tags_acls_condition != -110000)) {
		$sql_post .= $tags_acls_condition;
	}

	// Metaconsole fitlers
	if ($meta) {
		if ($server_id_search) {
			$sql_post .= " AND server_id = " . $server_id_search;
		}
		else {
			$enabled_nodes = db_get_all_rows_sql('
				SELECT id
				FROM tmetaconsole_setup
				WHERE disabled = 0');
			
			if (empty($enabled_nodes)) {
				$sql_post .= ' AND 1 = 0';
			}
			else {
				if ($strict_user == 1) {
					$enabled_nodes_id = array();
				} else {
					$enabled_nodes_id = array(0);
				}
				foreach ($enabled_nodes as $en) {
					$enabled_nodes_id[] = $en['id'];
				}
				$sql_post .= ' AND server_id IN (' .
					implode(',',$enabled_nodes_id) . ')';
			}
		}
	}
	
	return $sql_post;
}

function events_list_events_grouped_agents($sql) {
	global $config;
	
	$table = events_get_events_table(is_metaconsole(), $history);
	
	$sql = "select * from $table 
				WHERE $sql";
	
	$result = db_get_all_rows_sql ($sql);
	$group_rep = 0;
	$meta = is_metaconsole();
	
	//fields that the user has selected to show
	if ($meta) {
		$show_fields = events_meta_get_custom_fields_user();
	}
	else {
		$show_fields = explode (',', $config['event_fields']);
	}

	
	//headers
	$i = 0;
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
	
	return html_print_table($table,true);
}




?>
