<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2009 Artica Soluciones Tecnologicas, http://www.artica.es
// Copyright (c) 2009 Evi Vanoost, vanooste@rcbi.rochester.edu
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License (LGPL)
// as published by the Free Software Foundation for version 2.
// 
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

/** 
 * Creates a private message to be forwarded to other people
 * 
 * @param string $usuario_origen The sender of the message
 * @param string $usuario_destino The receiver of the message
 * @param string $subject Subject of the message (much like E-Mail)
 * @param string $mensaje The actual message. This message will be cleaned by safe_input 
 * (html is allowed but loose html chars will be translated)
 *
 * @return bool true when delivered, false in case of error
 */
function create_message ($usuario_origen, $usuario_destino, $subject, $mensaje) {
	$users = get_users_info ();
	
	if (!array_key_exists ($usuario_origen, $users) || !array_key_exists ($usuario_destino, $users)) {
		return false; //Users don't exist so don't send to them
	}
	
	$values = array ();
	$values["id_usuario_origen"] = $usuario_origen;
	$values["id_usuario_destino"] = $usuario_destino;
	$values["subject"] = safe_input ($subject);
	$values["mensaje"] = safe_input ($mensaje);
	$values["timestamp"] = get_system_time ();
	
	$return = process_sql_insert ("tmensajes", $values);
	
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
 * @param string The actual message. This message will be cleaned by safe_input 
 * (html is allowed but loose html chars will be translated)
 *
 * @return bool true when delivered, false in case of error
 */
function create_message_group ($usuario_origen, $dest_group, $subject, $mensaje) {
	$users = get_users_info ();
	$group_users = get_group_users ($dest_group);
	
	if (! array_key_exists ($usuario_origen, $users)) {
		//Users don't exist in the system
		return false;
	} elseif (empty ($group_users)) {
		//There are no users in the group, so it hasn't failed although it hasn't done anything.
		return true;
	}
	
	//Start transaction so that if it fails somewhere along the way, we roll back
	process_sql_begin ();
	
	foreach ($group_users as $user) {
		$return = create_message ($usuario_origen, get_user_id ($user), $subject, $mensaje);
		if ($return === false) {
			//Error sending message, rollback and return false
			process_sql_rollback ();
			return false;
		}
	}
	
	//We got here, so we can commit - if this function gets extended, make sure to do SQL above these lines
	process_sql_commit ();
	
	return true;
}

/** 
 * Deletes a private message
 * 
 * @param int $id_message
 *
 * @return bool true when deleted, false in case of error
 */
function delete_message ($id_message) {
	global $config;
	
	$sql = sprintf ("DELETE FROM tmensajes WHERE id_usuario_destino='%s' AND id_mensaje=%d", $config["id_user"], $id_message);
	return (bool) process_sql ($sql);
}

/** 
 * Marks a private message as read/unread
 * 
 * @param int $message_id The message to modify
 * @param bool $read To set unread pass 0, false or empty value
 *
 * @return bool true when marked, false in case of error
 */
function process_message_read ($message_id, $read = true) {
	if (empty ($read)) {
		$read = 0;
	} else {
		$read = 1;
	}
	
	return (bool) process_sql ("UPDATE tmensajes SET estado = ".$read." WHERE id_mensaje = ".$message_id);
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
function get_message ($message_id) {
	global $config;

	$sql = sprintf("SELECT id_usuario_origen, subject, mensaje, timestamp FROM tmensajes WHERE id_usuario_destino='%s' AND id_mensaje=%d" , $config["id_user"], $message_id);
    $row = get_db_row_sql ($sql);
	
	if (empty ($row)) {
		return false;
	}
	
	$return["sender"] = $row["id_usuario_origen"];
	$return["subject"] = safe_input ($row["subject"]); //Although not strictly necessary, we don't know what other systems might dump in this. So we clean up
	$return["message"] = safe_input ($row["mensaje"]);
	$return["timestamp"] = $row["timestamp"];
	
	return $return;
}

/** 
 * Counts private messages
 *
 * @param string $user
 * @param bool $incl_read Whether or not to include read messages
 *
 * @return int The number of messages this user has
 */
function get_message_count ($user = false, $incl_read = false) {
	if (empty ($user)) {
		global $config;
		$user = $config["id_user"];
	}
	if (empty ($incl_read)) {
		$filter = "AND estado = 0";
	} else {
		$filter = "";
	}
	$sql = sprintf("SELECT COUNT(*) FROM tmensajes WHERE id_usuario_destino='%s' %s", $user, $filter);
    
	return (int) get_db_sql ($sql);
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
function get_message_overview ($order = "status", $order_dir = "ASC") {
	global $config;
	
	switch ($order) {
		case "timestamp":
		case "sender":
		case "subject":
		break;
		case "status":
		default:
			$order = "estado";
	}
	
	if ($order_dir != "ASC") {
		$order .= " DESC";
	}
	
	$result = array ();
	$return = get_db_all_rows_field_filter ('tmensajes', 'id_usuario_destino', $config["id_user"], $order);
	
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

?>
