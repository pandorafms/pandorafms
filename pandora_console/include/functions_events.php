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

/**
 * Delete events in a transaction
 *
 * @param mixed $id_event Event ID or array of events
 *
 * @return bool Whether or not it was successful
 */
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

/**
 * Validate events in a transaction
 *
 * @param mixed $id_event Event ID or array of events
 *
 * @return bool Whether or not it was successful
 */	
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
 * @param int $id_event Event id
 * 
 * @return int Group id of the given event.
 */
function get_event_group ($id_event) {
	return (int) get_db_value ('id_grupo', 'tevento', 'id_evento', (int) $id_event);
}

/** 
 * Get description of an event.
 * 
 * @param int $id_event Event id.
 * 
 * @return string Description of the given event.
 */
function get_event_description ($id_event) {
	return (string) get_db_value ('evento', 'tevento', 'id_evento', (int) $id_event);
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
function create_event ($event, $id_group, $id_agent, $status = 0, $id_user = "", $event_type = "unknown", $priority = 0, $id_agent_module = 0, $id_aam = 0) {
	$sql = sprintf ('INSERT INTO tevento (id_agente, id_grupo, evento, timestamp, 
					estado, utimestamp, id_usuario, event_type, criticity,
					id_agentmodule, id_alert_am) 
					VALUES (%d, %d, "%s", NOW(), %d, NOW(), "%s", "%s", %d, %d, %d)',
					$id_agent, $id_group, $event, $status, $id_user, $event_type,
					$priority, $id_agent_module, $id_aam);
	
	return (int) process_sql ($sql, "insert_id");
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
function print_events_table ($filter = "", $limit = 10, $width = 440, $return = false) {
	global $config;
	
	$sql = sprintf ("SELECT * FROM tevento %s ORDER BY timestamp DESC LIMIT %d", $filter, $limit);
	$result = get_db_all_rows_sql ($sql);
	
	if ($result === false) {
		$return = '<div class="nf">'.__('No events').'</div>';
		if ($return === false) {
			echo $return;
		}
		return $return;
	} else {
		$table->cellpadding = 4;
		$table->cellspacing = 4;
		$table->width = $width;
		$table->class = "databox";
		$table->title = __('Latest events');
		$table->titlestyle = "background-color:#799E48;";
		$table->headclass = array ();
		$table->head = array ();
		$table->rowclass = array ();
		$table->data = array ();
		$table->align = array ();
		
		$table->head[0] = __('St');
		$table->align[0] = "center";
		
		$table->head[1] = __('Type');
		$table->headclass[1] = "datos3 f9";
		$table->align[1] = "center";
		
		$table->head[2] = __('Event name');
		
		$table->head[3] = __('Agent name');
		
		$table->head[4] = __('User ID');
		$table->headclass[4] = "datos3 f9";
		$table->align[4] = "center";
		
		$table->head[5] = __('Timestamp');
		$table->headclass[5] = "datos3 f9";
		$table->align[5] = "right";
		
		foreach ($result as $event) {
			if (! give_acl ($config["id_user"], $event["id_grupo"], "AR")) {
				continue;
			}
			$data = array ();
			
			/* Colored box */
			if ($event["estado"] == 0) {
				$data[0] = '<img src="images/pixel_red.png" width="20" height="20" title="'.get_priority_name ($event["criticity"]).'" />';
			} else {
				$data[0] = '<img src="images/pixel_green.png" width="20" height="20" title="'.get_priority_name ($event["criticity"]).'" />';
			}
			
			/* Event type */
			switch ($event["event_type"]) {
				case "alert_recovered":
					$data[1] = '<img src="images/error.png" title="'.__('Alert recovered').'" />';
					break;
				case "alert_manual_validation": 
					$data[1] = '<img src="images/eye.png" title="'.__('Alert manually validated').'" />';
					break;
				case "monitor_up":
					$data[1] = '<img src="images/lightbulb.png" title="'.__('Monitor up').'" />';
					break;
				case "monitor_down":
					$data[1] = '<img src="images/lightbulb_off.png" title="'.__('Monitor down').'" />';
					break;
				case "alert_fired":
					$data[1] = '<img src="images/bell.png" title="'.__('Alert fired').'" />';
					break;
				case "system";
					$data[1] = '<img src="images/cog.png" title="'.__('System').'" />';
					break;
				case "recon_host_detected";
					$data[1] = '<img src="images/network.png" title="'.__('Host detected by recon server').'" />';
					break;
				default: 
					$data[1] = '<img src="images/err.png" title="'.$event["event_type"].'" />';
					break;
			}
			
			// Event description wrap around by default at 44 or ~3 lines (10 seems to be a good ratio to wrap around for most sizes. Smaller number gets longer strings)
			$data[2] = '<span class="'.get_priority_class ($event["criticity"]).'f9" title="'.safe_input ($event["evento"]).'">'.safe_input (substr ($event["evento"],0, floor ($width / 10)));
			
			if (strlen ($event["evento"]) > floor ($width / 10)) {
				$data[2] .= "...";
			}
			$data[2] .= '</span>';
			
			if ($event["id_agente"] > 0) {
				// Agent name
				$data[3] = print_agent_name ($event["id_agente"], true, floor ($width / 20)); //At 440 this would be be 22
			// for System or SNMP generated alerts
			} elseif ($event["event_type"] == "system") {
				$data[3] = __('System');
			} else {
				$data[3] = __('Alert')."SNMP";
			}
			
			// User who validated event
			if ($event["estado"] != 0) {
				$data[4] = print_username ($event["id_usuario"], true);
			} else {
				$data[4] = '';
			}
			
			// Timestamp
			$data[5] = print_timestamp ($event["timestamp"], true);
			
			array_push ($table->rowclass, get_priority_class ($event["criticity"]));
			array_push ($table->data, $data);
		}
		
		$return = print_table ($table, $return);
		unset ($table);
		return $return;
	}
}
?>