<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Evi Vanoost, <vanooste@rcbi.rochester.edu>
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

function delete_event ($id_event) {
	global $config;
	
	$id_event = (array) safe_int ($id_event, 1); //Cleans up the selection for all unwanted values also casts any single values as an array 
	
	process_sql ("SET AUTOCOMMIT = 0;");
	process_sql ("START TRANSACTION;");
	$errors = 0;
	
	foreach ($id_event as $event) {
		$sql = sprintf ("DELETE FROM tevento WHERE id_evento = %d", $event);
		$ret = process_sql ($sql);
		
		if (give_acl ($config["id_user"], get_event_group ($event), "IM") == 0) {
			//Check ACL
			audit_db ($config["id_user"], $config["remote_addr"], "ACL Violation", "Attempted deleting event #".$event);
		} elseif ($ret !== false) {
			//ACL didn't fail nor did return
			continue;
		}
		
		$errors++;
	}
	
	if ($errors > 1) {
		process_sql ("ROLLBACK;");
		process_sql ("SET AUTOCOMMIT = 1;");
		return false;
	} else {
		foreach ($id_event as $event) {
			audit_db ($config["id_user"], $config["remote_addr"], "Event deleted", "Deleted event #".$event);
		}
		process_sql ("COMMIT;");
		process_sql ("SET AUTOCOMMIT = 1;");
		return true;
	}
}

function process_event_validate ($id_event) {
	global $config;
	
	$id_event = (array) safe_int ($id_event, 1); //Cleans up the selection for all unwanted values also casts any single values as an array 
	
	process_sql ("SET AUTOCOMMIT = 0;");
	process_sql ("START TRANSACTION;");
	$errors = 0;
	
	foreach ($id_event as $event) {
		$sql = sprintf ("UPDATE tevento SET estado = 1, id_usuario = '%s' WHERE id_evento = %d", $config['id_user'], $event);
		$ret = process_sql ($sql);
		
		if (give_acl ($config["id_user"], get_event_group ($event), "IW") == 0) {
			//Check ACL
			audit_db ($config["id_user"], $config["remote_addr"], "ACL Violation", "Attempted updating event #".$event);
		} elseif ($ret !== false) {
			//ACL didn't fail nor did return
			continue;
		}
		
		$errors++;
	}
	
	if ($errors > 1) {
		process_sql ("ROLLBACK;");
		process_sql ("SET AUTOCOMMIT = 1;");
		return false;
	} else {
		foreach ($id_event as $event) {
			audit_db ($config["id_user"], $config["remote_addr"], "Event validated", "Validated event #".$event);
		}
		process_sql ("COMMIT;");
		process_sql ("SET AUTOCOMMIT = 1;");
		return true;
	}
}

/** 
 * Get group id of an event.
 * 
 * @param id_event Event id
 * 
 * @return Group id of the given event.
 */
function get_event_group ($id_event) {
	return (int) get_db_value ('id_grupo', 'tevento', 'id_evento', (int) $id_event);
}

/** 
 * Get description of an event.
 * 
 * @param id_event Event id.
 * 
 * @return Description of the given event.
 */
function get_event_description ($id_event) {
	return (string) get_db_value ('evento', 'tevento', 'id_evento', (int) $id_event);
}

/** 
 * Insert a event in the event log system.
 * 
 * @param event 
 * @param id_group 
 * @param id_agent 
 * @param status 
 * @param id_user 
 * @param event_type 
 * @param priority 
 * @param id_agent_module 
 * @param id_aam 
 *
 * @return event_id
 */
function create_event ($event, $id_group, $id_agent, $status = 0, $id_user = "", $event_type = "unknown", $priority = 0, $id_agent_module = 0, $id_aam = 0) {
	$sql = sprintf ('INSERT INTO tevento (id_agente, id_grupo, evento, timestamp, 
					estado, utimestamp, id_usuario, event_type, criticity,
					id_agentmodule, id_alert_am) 
					VALUES (%d, %d, "%s", NOW(), %d, NOW(), "%s", "%s", %d, %d, %d)',
					$id_agent, $id_group, $event, $status, $id_user, $event_type,
					$priority, $id_agent_module, $id_aam);
	
	return (int) process_sql ($sql, "insert_id");
}
?>