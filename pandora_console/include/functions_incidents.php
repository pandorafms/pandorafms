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
 */

/**
 * Gets all the possible priorities for incidents in an array
 *
 * @return array The several priorities with their values
 */
function get_incidents_priorities () {
	$fields = array();
	$fields[0] = __('Informative');
	$fields[1] = __('Low');
	$fields[2] = __('Medium');
	$fields[3] = __('Serious');
	$fields[4] = __('Very serious');
	$fields[10] = __('Maintenance');
	
	return $fields;
}

/**
 * Prints the image tag for passed status
 *
 * @param int $id_status Which status to return the image to
 *
 * @return string The string with the image tag
 */
function print_incidents_priority_img ($id_priority, $return = false) {
	switch ($id_priority) {
	case 0:
		$img = print_image ("images/dot_green.png", true).print_image ("images/dot_green.png", true).print_image ("images/dot_yellow.png", true);
		break;
	case 1:
		$img = print_image ("images/dot_green.png", true).print_image ("images/dot_yellow.png", true).print_image ("images/dot_yellow.png", true);
		break;
	case 2:
		$img = print_image ("images/dot_yellow.png", true).print_image ("images/dot_yellow.png", true).print_image ("images/dot_red.png", true);
		break;
	case 3:
		$img = print_image ("images/dot_yellow.png", true).print_image ("images/dot_red.png", true).print_image ("images/dot_red.png", true);
		break;
	case 4:
		$img = print_image ("images/dot_red.png", true).print_image ("images/dot_red.png", true).print_image ("images/dot_red.png", true);
		break;
	case 10:
		$img = print_image ("images/dot_green.png", true).print_image ("images/dot_green.png", true).print_image ("images/dot_green.png", true);
		break;
	}
	
	if ($return === false) {
		echo $img;
	}
	return $img;
}
	

/**
 * Gets all the possible status for incidents in an array
 *
 * @return array The several status with their values
 */
function get_incidents_status () {
	$fields = array ();
	$fields[0] = __('Active incidents');
	$fields[1] = __('Active incidents, with comments');
	$fields[2] = __('Rejected incidents');
	$fields[3] = __('Expired incidents');
	$fields[13] = __('Closed incidents');
	
	return $fields;
}

/**
 * Prints the image tag for passed status
 *
 * @param int $id_status: Which status to return the image to
 *
 * @return string The string with the image tag
 */
function print_incidents_status_img ($id_status, $return = false) {
	switch ($id_status) {
		case 0:
			$img = print_image ("images/dot_red.png", true);
		break;
		case 1:
			$img = print_image ("images/dot_yellow.png", true);
		break;
		case 2:
			$img = print_image ("images/dot_blue.png", true);
		break;
		case 3:
			$img = print_image ("images/dot_green.png", true);
		break;
		case 13:
			$img = print_image ("images/dot_white.png", true);
		break;
	}
	
	if ($return === false) {
		echo $img;
	}
	return $img;
}

/**
 * Updates the last user (either by adding an attachment, note or the incident itself)
 * Named after the UNIX touch utility
 *
 * @param int $id_incident: A single incident or an array of incidents
 *
 * @return bool True if it was done, false if it wasn't
 */
function process_incidents_touch ($id_incident) {
	global $config;
	
	$id_incident = (array) safe_int ($id_incident, 1); //Make sure we have all positive int's
	if (empty ($id_incident)) {
		return false;
	}
	$id_incident = implode (",", $id_incident);
	$sql = sprintf ("UPDATE tincidencia SET id_lastupdate = '%s' WHERE id_incidencia IN (%s)", $config["id_user"], $id_incident);
	return process_sql ($sql);
}

/**
 * Updates the owner (named after the UNIX utility chown)
 *
 * @param int $id_incident: A single incident or an array of incidents
 *
 * @return bool True if it was done, false if it wasn't
 */
function process_incidents_chown ($id_incident, $owner = false) {
	if ($owner === false) {
		global $config;
		$owner = $config["id_user"];
	}
		
	$id_incident = (array) safe_int ($id_incident, 1); //Make sure we have all positive int's
	if (empty ($id_incident)) {
		return false;
	}
	$id_incident = implode (",", $id_incident);
	$sql = sprintf ("UPDATE tincidencia SET id_usuario = '%s' WHERE id_incidencia IN (%s)", $owner, $id_incident);
	return process_sql ($sql);
}
	

/** 
 * Get the author of an incident.
 * 
 * @param int $id_incident Incident id.
 * 
 * @return string The author of an incident
 */
function get_incidents_author ($id_incident) {
	if ($id_incident < 1) {
		return "";
	}
	return (string) get_db_value ('id_creator', 'tincidencia', 'id_incidencia', (int) $id_incident);
}

/** 
 * Get the owner of an incident.
 * 
 * @param int $id_incident Incident id.
 * 
 * @return string The last updater of an incident
 */
function get_incidents_owner ($id_incident) {
	if ($id_incident < 1) {
		return "";
	}
	return (string) get_db_value ('id_usuario', 'tincidencia', 'id_incidencia', (int) $id_incident);
}

/** 
 * Get the last updater of an incident.
 * 
 * @param int $id_incident Incident id.
 * 
 * @return string The last updater of an incident
 */
function get_incidents_lastupdate ($id_incident) {
	if ($id_incident < 1) {
		return "";
	}
	return (string) get_db_value ('id_lastupdate', 'tincidencia', 'id_incidencia', (int) $id_incident);
}
	

/** 
 * Get the group id of an incident.
 * 
 * @param int $id_incident Incident id.
 * 
 * @return int The group id of an incident
 */
function get_incidents_group ($id_incident) {
	if ($id_incident < 1) {
		return 0;
	}
	return (int) get_db_value ('id_grupo', 'tincidencia', 'id_incidencia', (int) $id_incident);
}

/** 
 * Delete an incident out the database.
 * 
 * @param mixed $id_inc An int or an array of ints to be deleted
 *
 * @return bool True if incident was succesfully deleted, false if not
 */
function delete_incidents ($id_incident) {
	global $config;
	$ids = (array) safe_int ($id_incident, 1); //Make the input an array
	$notes = array ();
	$attachments = array ();
	$errors = 0;
	
	//Start transaction
	process_sql_begin ();
		
	foreach ($ids as $id_inc) {
		//Delete incident
		$sql = sprintf ("DELETE FROM tincidencia WHERE id_incidencia = %d", $id_inc);
		$ret = process_sql ($sql);
		if ($ret === false) {
			$errors++;
		}
		//We only need the ID's
		$notes = array_merge ($notes, array_keys (get_incidents_notes ($id_inc)));
		$attachments = array_merge ($attachments, array_keys (get_incidents_attach ($id_inc)));
		
		audit_db ($config['id_user'], $config["remote_addr"], "Incident deleted", $config['id_user']." deleted incident #".$id_inc);
	}
	
	//Delete notes
	$note_err = delete_incidents_note ($notes, false);
	$attach_err = delete_incidents_attach ($attachments, false);
	
	if ($note_err === false || $attach_err === false) {
		$errors++;
	}
	
	if ($errors > 0) {
		//This will also rollback the audit log
		process_sql_rollback ();
		return false;
	}
	process_sql_commit ();
	
	return true;
}

/** 
 * Delete notes out the database.
 * 
 * @param mixed $id_note An int or an array of ints to be deleted
 * @param bool $transact true if a transaction should be started, false if not
 *
 * @return bool True if note was succesfully deleted, false if not
 */
function delete_incidents_note ($id_note, $transact = true) {
	$id_note = (array) safe_int ($id_note, 1); //cast as array
	$errors = 0;
	
	//Start transaction
	if ($transact == true){
		process_sql_begin ();
		process_sql_commit ();
	}
	
	//Delete notes
	foreach ($id_note as $id) {
		$ret = process_sql_delete ('tnota', array ('id_nota' => $id));
		if ($ret === false) {
			$errors++;
		}
	}

	if ($transact == true && $errors > 0) {
		process_sql_rollback ();
		return false;
	} elseif ($transact == true) {
		process_sql_commit ();
		return true;
	} elseif ($errors > 0) {
		return false;
	} else {
		return true;
	}
}

/** 
 * Delete attachments out the database and from the machine.
 * 
 * @param mixed $id_attach An int or an array of ints to be deleted
 * @param bool $transact true if a transaction should be started, false if not
 *
 * @return bool True if attachment was succesfully deleted, false if not
 */
function delete_incidents_attach ($id_attach, $transact = true) {
	global $config;
	
	$id_attach = (array) safe_int ($id_attach, 1); //cast as array
	$errors = 0;
	
	//Start transaction
	if ($transact == true) {
		process_sql_begin ();
	}
	
	//Delete attachment
	foreach ($id_attach as $id) {
		$filename = get_db_value ("filename", "tattachment", "id_attachment", $id);
		$sql = sprintf ("DELETE FROM tattachment WHERE id_attachment = %d", $id);
		$ret = process_sql ($sql);
		if ($ret === false) {
			$errors++;
		}
		unlink ($config["attachment_store"]."/pand".$id."_".$filename);
	}
		
	if ($transact == true && $errors > 0) {
		process_sql_rollback ();
		return false;
	} elseif ($transact == true) {
		process_sql_commit ();
		return true;
	} elseif ($errors > 0) {
		return false;
	} else {
		return true;
	}
}

/** 
 * Get notes based on the incident id.
 * 
 * @param int $id_incident An int with the incident id
 *
 * @return array An array of all the notes for that incident
 */
function get_incidents_notes ($id_incident) {
	$return = get_db_all_rows_field_filter ("tnota", "id_incident", (int) $id_incident);
	
	if ($return === false) {
		$return = array ();
	}
	
	$notes = array ();
	foreach ($return as $row) {
		$notes[$row["id_nota"]] = $row;
	}
	
	return $notes;
}

/** 
 * Get attachments based on the incident id.
 * 
 * @param int $id_incident An int with the incident id
 *
 * @return array An array of all the notes for that incident
 */
function get_incidents_attach ($id_incident) {
	$return = get_db_all_rows_field_filter ("tattachment", "id_incidencia", (int) $id_incident);
	
	if ($return === false) {
		$return = array ();
	}
	
	$attach = array ();
	foreach ($return as $row) {
		$attach[$row["id_attachment"]] = $row;
	}
	
	return $attach;
}

	
/** 
 * Get user id of a note.
 * 
 * @param int $id_note Note id.
 * 
 * @return string User id of the given note.
 */
function get_incidents_notes_author ($id_note) {
	return (string) get_db_value ('id_usuario', 'tnota', 'id_nota', (int) $id_note);
}
?>
