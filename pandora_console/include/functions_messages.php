<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package Include
 * @subpackage Messages
 */

require_once($config['homedir'] . "/include/functions_users.php");
require_once ($config['homedir'].'/include/functions_groups.php');

/** 
 * Creates a private message to be forwarded to other people
 * 
 * @param string $usuario_origen The sender of the message
 * @param string $usuario_destino The receiver of the message
 * @param string $subject Subject of the message (much like E-Mail)
 * @param string $mensaje The actual message. This message will be cleaned by io_safe_input 
 * (html is allowed but loose html chars will be translated)
 *
 * @return bool true when delivered, false in case of error
 */
function messages_create_message ($usuario_origen, $usuario_destino, $subject, $mensaje) {
	$users = users_get_info ();
	
	if (!array_key_exists ($usuario_origen, $users) || !array_key_exists ($usuario_destino, $users)) {
		return false; //Users don't exist so don't send to them
	}
	
	$values = array ();
	$values["id_usuario_origen"] = $usuario_origen;
	$values["id_usuario_destino"] = $usuario_destino;
	$values["subject"] = $subject;
	$values["mensaje"] = $mensaje;
	$values["timestamp"] = get_system_time ();
	
	$return = db_process_sql_insert ("tmensajes", $values);
	
	if ($return === false) {
		return false;
	} else {
		return true;
	}
}

/** 
 * Creates private messages to be forwarded to groups
 * 
 * @param string The sender of the message
 * @param string The receivers (group) of the message
 * @param string Subject of the message (much like E-Mail)
 * @param string The actual message. This message will be cleaned by io_safe_input 
 * (html is allowed but loose html chars will be translated)
 *
 * @return bool true when delivered, false in case of error
 */
function messages_create_group ($usuario_origen, $dest_group, $subject, $mensaje) {
	$users = users_get_info ();
	$group_users = groups_get_users ($dest_group);
	
	if (! array_key_exists ($usuario_origen, $users)) {
		//Users don't exist in the system
		return false;
	}
	elseif (empty ($group_users)) {
		//There are no users in the group, so it hasn't failed although it hasn't done anything.
		return true;
	}
	
	foreach ($group_users as $user) {
		$return = messages_create_message ($usuario_origen, get_user_id ($user), $subject, $mensaje);
		if ($return === false) {
			return false;
		}
	}
	
	return true;
}

/** 
 * Deletes a private message
 * 
 * @param int $id_message
 *
 * @return bool true when deleted, false in case of error
 */
function messages_delete_message ($id_message) {
	global $config;
	
	$where = array(
		//'id_usuario_destino' => $config["id_user"],
		'id_mensaje' => $id_message);
	return (bool)db_process_sql_delete('tmensajes', $where);
}

/** 
 * Marks a private message as read/unread
 * 
 * @param int $message_id The message to modify
 * @param bool $read To set unread pass 0, false or empty value
 *
 * @return bool true when marked, false in case of error
 */
function messages_process_read ($message_id, $read = true) {
	if (empty ($read)) {
		$read = 0;
	}
	else {
		$read = 1;
	}
	
	return (bool) db_process_sql_update('tmensajes', array('estado' => $read), array('id_mensaje' => $message_id));
}

/** 
 * Gets a private message
 *
 * This function abstracts the database backend so it can simply be replaced with another system
 * 
 * @param int $message_id
 *
 * @return mixed False if it doesn't exist or a filled array otherwise
 */
function messages_get_message ($message_id) {
	global $config;

	$sql = sprintf("SELECT id_usuario_origen, id_usuario_destino, subject, mensaje, timestamp
		FROM tmensajes
		WHERE id_usuario_destino='%s' AND id_mensaje=%d" , $config["id_user"], $message_id);
    $row = db_get_row_sql ($sql);
	
	if (empty ($row)) {
		return false;
	}
	
	return $row;
}

/** 
 * Gets a sent message
 *
 * This function abstracts the database backend so it can simply be replaced with another system
 * 
 * @param int $message_id
 *
 * @return mixed False if it doesn't exist or a filled array otherwise
 */
function messages_get_message_sent ($message_id) {
	global $config;

	$sql = sprintf("SELECT id_usuario_origen, id_usuario_destino, subject, mensaje, timestamp
		FROM tmensajes
		WHERE id_usuario_origen='%s' AND id_mensaje=%d" , $config["id_user"], $message_id);
    $row = db_get_row_sql ($sql);
	
	if (empty ($row)) {
		return false;
	}
	
	return $row;
}


/** 
 * Counts private messages
 *
 * @param string $user
 * @param bool $incl_read Whether or not to include read messages
 *
 * @return int The number of messages this user has
 */
function messages_get_count ($user = false, $incl_read = false) {
	if (empty ($user)) {
		global $config;
		$user = $config["id_user"];
	}
	if (empty ($incl_read)) {
		$filter = "AND estado = 0";
	} else {
		$filter = "";
	}
	$sql = sprintf("SELECT COUNT(*)
		FROM tmensajes WHERE id_usuario_destino='%s' %s", $user, $filter);
    
	return (int) db_get_sql ($sql);
}

/** 
 * Counts sended messages
 *
 * @param string $user
 *
 * @return int The number of messages this user has sent
 */
function messages_get_count_sent ($user = false) {
	if (empty ($user)) {
		global $config;
		$user = $config["id_user"];
	}
	$sql = sprintf("SELECT COUNT(*)
		FROM tmensajes WHERE id_usuario_origen='%s'", $user);
    
	return (int) db_get_sql ($sql);
}


/** 
 * Get message overview in array
 *
 * @param string $order How to order them valid: 
 * (status (default), subject, timestamp, sender)
 * @param string $order_dir Direction of order (ASC = Ascending, DESC = Descending)
 *
 * @return int The number of messages this user has
 */
function messages_get_overview ($order = "status", $order_dir = "ASC") {
	global $config;
	
	switch ($order) {
		case "timestamp":
		case "sender":
		case "subject":
		break;
		case "status":
		default:
			$order = "estado, timestamp";
			break;
	}
	
	if ($order_dir != "ASC") {
		$order .= " DESC";
	}
	
	$result = array ();
	$return = db_get_all_rows_field_filter ('tmensajes', 'id_usuario_destino', $config["id_user"], $order);
	
	if ($return === false) {
		return $result;
	}
	
	foreach ($return as $message) {
		$result[$message["id_mensaje"]]["sender"] = $message["id_usuario_origen"];
		$result[$message["id_mensaje"]]["subject"] = $message["subject"];
		$result[$message["id_mensaje"]]["timestamp"] = $message["timestamp"];
		$result[$message["id_mensaje"]]["status"] = $message["estado"];
	}
	
	return $result;
}

/** 
 * Get sent message overview in array
 *
 * @param string $order How to order them valid: 
 * (status (default), subject, timestamp, sender)
 * @param string $order_dir Direction of order (ASC = Ascending, DESC = Descending)
 *
 * @return int The number of messages this user has
 */
function messages_get_overview_sent ($order = "timestamp", $order_dir = "ASC") {
	global $config;
	
	switch ($order) {
		case "timestamp":
		case "sender":
		case "subject":
		break;
		case "status":
		default:
			$order = "estado, timestamp";
			break;
	}
	
	if ($order_dir != "ASC") {
		$order .= " DESC";
	}
	
	$result = array ();
	$return = db_get_all_rows_field_filter ('tmensajes', 'id_usuario_origen', $config["id_user"], $order);
	
	if ($return === false) {
		return $result;
	}
	
	foreach ($return as $message) {
		$result[$message["id_mensaje"]]["dest"] = $message["id_usuario_destino"];
		$result[$message["id_mensaje"]]["subject"] = $message["subject"];
		$result[$message["id_mensaje"]]["timestamp"] = $message["timestamp"];
		$result[$message["id_mensaje"]]["status"] = $message["estado"];
	}
	
	return $result;
}

?>
