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

/**
 * @package Include
 * @subpackage Alerts
 */

require_once($config['homedir'] . "/include/functions_agents.php");
require_once($config['homedir'] . '/include/functions_modules.php');
require_once($config['homedir'] . '/include/functions_users.php');

function alerts_get_alerts($id_group = 0, $free_search = "", $status = "all", $standby = -1, $acl = false, $total = false, $id_agent = 0) {
	$sql = "";
	$alerts = array();
	
	//----------- Group ------------------------------------------------
	if ($id_group != 0) {
		if ($acl !== false) {
			$groups = users_get_groups (false, $acl, false);
			
			if (array_key_exists($id_group, $groups)) {
				$group_query = " AND t3.id_grupo = " . $id_group . " ";
			}
			else {
				//Set to fail the query
				$group_query = " AND 1=0 ";
			}
		}
		else {
			$group_query = " AND t3.id_grupo = " . $id_group . " ";
		}
	}
	else {
		if ($acl !== false) {
			$groups = users_get_groups (false, $acl, false);
			
			$id_groups = array_keys($groups);
			
			$group_query = " AND t3.id_grupo IN (" . implode(',', $id_groups) . ") ";
		}
		else {
			$group_query = "";
		}
	}
	
	//------------ Status ----------------------------------------------
	switch ($status) {
		case "notfired":
			$status_query = ' AND t0.times_fired = 0 AND t0.disabled = 0';
			break;
		case "fired":
			$status_query = ' AND t0.times_fired > 0 AND t0.disabled = 0';
			break;
		case "disabled":
			$status_query = ' AND t0.disabled = 1';
			break;
		case "all_enabled":
			$status_query = ' AND t0.disabled = 0';
			break;
		default:
			$status_query = '';
			break;
	}
	
	//----------- Standby ----------------------------------------------
	$standby_query = '';
	if ($standby != -1) {
		$status_query .= ' AND t0.standby = ' . $standby . ' ';
	}
	
	//----------- Free search ------------------------------------------
	$free_search = io_safe_input($free_search);
	
	//----------- Make the query ---------------------------------------
	if ($total) {
		$sql = 'SELECT COUNT(*)';
	}
	else {
		$sql = 'SELECT *, t2.nombre AS module_name,
			t3.nombre AS agent_name, t1.name AS template_name,
			t0.disabled AS alert_disabled ';
	}
	$sql .= '
		FROM talert_template_modules AS t0
		INNER JOIN talert_templates AS t1
			ON t0.id_alert_template = t1.id
		INNER JOIN tagente_modulo AS t2
			ON t0.id_agent_module = t2.id_agente_modulo
		INNER JOIN tagente AS t3
			ON t2.id_agente = t3.id_agente
		WHERE 1=1
			' . $status_query . ' ' . $standby_query . ' ' . $group_query . '
			AND (t1.name LIKE "%' . $free_search . '%"
				OR t2.nombre LIKE "%' . $free_search . '%"
				OR t3.nombre LIKE "%' . $free_search . '%")';
	
	if ($id_agent != 0) {
		$sql .= ' AND t3.id_agente = ' . $id_agent;
	}
	
	$row_alerts = db_get_all_rows_sql($sql);
	
	if ($total) {
		return reset($row_alerts[0]);
	}
	else {
		
		
		return $row_alerts;
	}
}

/**
 * Get fired status from any alert of agent in group.
 * 
 * @param integer $idGroup The ID of group.
 * @param mixed $type The list of types to search or type. By default "alert_fired".
 * 
 * @return mixed Return id if the group have any alert is fired or false is not.
 */
function alerts_get_event_status_group($idGroup, $type = "alert_fired", $query = 'AND 1=1') {
	global $config;
	
	$return = false;
	
	$typeWhere = '';
	
	if (!is_array($type)) {
		$typeWhere = ' AND event_type = "' . $type . '" ';
	}
	else {
		$temp = array();
		foreach ($type as $item) {
			$temp[] = '"' . $item . '"';
		}
		
		$typeWhere = ' AND event_type IN (' . implode(',', $temp) . ')';
	}
	
	$agents = agents_get_group_agents($idGroup, false, "lower", false);
	
	$idAgents = array_keys($agents);
	
	$result = db_get_all_rows_sql('SELECT id_evento
		FROM tevento
		WHERE estado = 0 AND id_agente IN (' . implode(',', $idAgents) . ') ' . $typeWhere . $query . '
		ORDER BY id_evento DESC LIMIT 1');
	
	if ($result === false) {
		return false;
	}
	
	return $result[0]['id_evento'];
}

/**
 * Insert in talert_commands a new command.
 *
 * @param string name command name to save in DB.
 * @param string command String of command.
 * @param mixed A single value or array of values to insert (can be a multiple amount of rows).
 * 
 * @return mixed False in case of error or invalid values passed. Affected rows otherwise.
 */
function alerts_create_alert_command ($name, $command, $values = false) {
	if (empty ($name))
		return false;
	if (empty ($command))
		return false;
	if (! is_array ($values))
		$values = array ();
	$values['name'] = $name;
	$values['command'] = $command;
	
	return @db_process_sql_insert ('talert_commands', $values);
}

/**
 * Update a command in talert_commands.
 *
 * @param int Alert command Id.
 * @param mixed Array of values to update.
 * 
 * @return mixed False in case of error or invalid values passed. Affected rows otherwise
 */
function alerts_update_alert_command ($id_alert_command, $values) {
	$id_alert_command = safe_int ($id_alert_command, 1);
	if (empty ($id_alert_command))
		return false;
	if (! is_array ($values))
		return false;
	
	return (@db_process_sql_update ('talert_commands',
		$values,
		array ('id' => $id_alert_command))) !== false;
}

/**
 * Delete a command in talert_commands.
 *
 * @param int Alert command Id.
 * 
 * @return mixed False in case of error or invalid values passed. Affected rows otherwise
 */
function alerts_delete_alert_command ($id_alert_command) {
	$id_alert_command = safe_int ($id_alert_command, 1);
	if (empty ($id_alert_command))
		return false;
	
	return (@db_process_sql_delete ('talert_commands',
		array ('id' => $id_alert_command))) !== false;
}

/**
 * Get a command in talert_commands.
 *
 * @param int Alert command Id.
 * 
 * @return mixed False in case of error or invalid values passed. All row of the selected command otherwise
 */
function alerts_get_alert_command ($id_alert_command) {
	$id_alert_command = safe_int ($id_alert_command, 1);
	if (empty ($id_alert_command))
		return false;
	
	return db_get_row ('talert_commands', 'id', $id_alert_command);
}

/**
 * Get name of a command in talert_commands.
 *
 * @param int Alert command Id.
 * 
 * @return mixed False in case of error or invalid values passed. Command name otherwise
 */
function alert_get_alert_command_name ($id_alert_command) {
	$id_alert_command = safe_int ($id_alert_command, 1);
	if (empty ($id_alert_command))
		return false;
	
	return db_get_value ('name', 'talert_commands', 'id', $id_alert_command);
}

/**
 * Get command field of a command in talert_commands.
 *
 * @param int Alert command Id.
 * 
 * @return mixed False in case of error or invalid values passed. Command field otherwise
 */
function alerts_get_alert_command_command ($id_alert_command) {
	$id_alert_command = safe_int ($id_alert_command, 1);
	if (empty ($id_alert_command))
		return false;
	
	return db_get_value ('command', 'talert_commands', 'id', $id_alert_command);
}

/**
 * Get internal field of a command in talert_commands.
 *
 * @param int Alert command Id.
 * 
 * @return mixed False in case of error or invalid values passed. Internal field otherwise
 */
function alerts_get_alert_command_internal ($id_alert_command) {
	$id_alert_command = safe_int ($id_alert_command, 1);
	if (empty ($id_alert_command))
		return false;
	
	return (bool) db_get_value ('internal', 'talert_commands', 'id', $id_alert_command);
}

/**
 * Get description field of a command in talert_commands.
 *
 * @param int Alert command Id.
 * 
 * @return mixed False in case of error or invalid values passed. Description field otherwise
 */
function alerts_get_alert_command_description ($id_alert_command) {
	$id_alert_command = safe_int ($id_alert_command, 1);
	if (empty ($id_alert_command))
		return false;
	
	return db_get_value ('description', 'talert_commands', 'id', $id_alert_command);
}

/**
 * Creates a new alert action. 
 *
 * @param string Name of the alert action 
 * @param int Id of the alert command associated
 * @param mixed Other fields of the new alert or false. 
 * 
 * @return mixed Returns the id if success or false in case of fail. 
 */
function alerts_create_alert_action ($name, $id_alert_command, $values = false) {
	$id_alert_command = safe_int ($id_alert_command, 1);
	if (empty ($id_alert_command))
		return false;
	if (empty ($name))
		return false;
	
	if (! is_array ($values))
		$values = array ();
	$values['name'] = $name;
	$values['id_alert_command'] = (int) $id_alert_command;
	
	return @db_process_sql_insert ('talert_actions', $values);
}

/**
 * Updates an alert action. 
 *
 * @param int Id of the alert action
 * @param array Values to update. 
 * 
 * @return mixed Returns affected rows or false in case of fail. 
 */
function alerts_update_alert_action ($id_alert_action, $values) {
	$id_alert_action = safe_int ($id_alert_action, 1);
	if (empty ($id_alert_action))
		return false;
	if (! is_array ($values))
		return false;
	
	return (@db_process_sql_update ('talert_actions',
		$values,
		array ('id' => $id_alert_action))) !== false;
}

/**
 * Delete an alert action. 
 *
 * @param int Id of the alert action
 * 
 * @return mixed Returns affected rows or false in case of fail. 
 */
function alerts_delete_alert_action ($id_alert_action) {
	$id_alert_action = safe_int ($id_alert_action, 1);
	if (empty ($id_alert_action))
		return false;
	
	return (@db_process_sql_delete ('talert_actions',
		array ('id' => $id_alert_action))) !== false;
}

/**
 * Clone an alert action. 
 *
 * @param int Id of the original alert action
 * 
 * @return mixed Id of the cloned action or false in case of fail. 
 */
function alerts_clone_alert_action ($id_alert_action) {
	$id_alert_action = safe_int ($id_alert_action, 1);
	if (empty ($id_alert_action))
		return false;
	
	$action = alerts_get_alert_action($id_alert_action);
	
	if (empty ($action))
		return false;
	
	unset($action['id']);
	
	return alerts_create_alert_action ($action['name']." ".__('copy'), $action['id_alert_command'], $action);
}

/**
 * Get all alert actions in Pandora DB.
 * 
 * @param bool $only_names Return only names, by default is true.
 * @param bool $acl Check the ACL, by default is false
 * 
 * @return array The list of actions.
 */
function alerts_get_alert_actions ($only_names = true, $acl = false) {
	$groups = users_get_groups(false, "AR", true);
	
	if ($groups === false) {
		$groups = array();
	}
	$id_groups = array_keys($groups);
	
	$all_actions = db_get_all_rows_filter('talert_actions', array('id_group' => $id_groups));
	
	if ($all_actions === false)
		return array ();
	
	if (! $only_names)
		return $all_actions;
	
	$actions = array ();
	foreach ($all_actions as $action) {
		$actions[$action['id']] = $action['name'];
	}
	
	return $actions;
}

/**
 * Get actions alerts filtered.
 * 
 * @param bool Return all fields or not.
 * @param variant String with SQL filter or false in case you don't want to filter.
 *
 * @return mixed A matrix with all the values returned from the SQL statement or
 * false in case of empty result
 */
function alerts_get_alert_actions_filter ($only_names = true, $filter = false) {
	
	if (!$filter)
		$all_actions = db_get_all_rows_in_table ('talert_actions');
	elseif (is_string($filter))
		$all_actions = db_get_all_rows_filter ('talert_actions', $filter);
	else
		$all_actions = false;
	
	if ($all_actions === false)
		return array ();
	
	if (! $only_names)
		return $all_actions;
	
	$actions = array ();
	foreach ($all_actions as $action) {
		$actions[$action['id']] = $action['name'];
	}
	
	return $actions;
}

/**
 * Get action alert.
 * 
 * @param int Id of the action alert.
 *
 * @return mixed An array with the result set of the action alert or
 * false in case of empty result
 */
function alerts_get_alert_action ($id_alert_action) {
	$id_alert_action = safe_int ($id_alert_action, 1);
	if (empty ($id_alert_action))
		return false;
	
	return db_get_row ('talert_actions', 'id', $id_alert_action);
}

/**
 * Get Id of the alert command associated with an alert action.
 * 
 * @param int Id of the action alert.
 *
 * @return mixed Id of the action alert or
 * false in case of empty result
 */
function alerts_get_alert_action_alert_command_id ($id_alert_action) {
	return db_get_value ('id_alert_command', 'talert_actions',
		'id', $id_alert_action);
}

/**
 * Get alert command associated with an alert action.
 * 
 * @param int Id of the action alert.
 *
 * @return mixed Result set of the action alert or
 * false in case of empty result
 */
function alerts_get_alert_action_alert_command ($id_alert_action) {
	$id_command = alerts_get_alert_action_alert_command_id ($id_alert_action);
	
	return alerts_get_alert_command ($id_command);
}

/**
 * Get field1 of an alert action.
 * 
 * @param int Id of the action alert.
 *
 * @return mixed Field1 of the action alert or
 * false in case of empty result
 */
function alerts_get_alert_action_field1 ($id_alert_action) {
	return db_get_value ('field1', 'talert_actions', 'id', $id_alert_action);
}

/**
 * Get field2 of an alert action.
 * 
 * @param int Id of the action alert.
 *
 * @return mixed Field2 of the action alert or
 * false in case of empty result
 */
function alerts_get_alert_action_field2 ($id_alert_action) {
	return db_get_value ('field2', 'talert_actions', 'id', $id_alert_action);
}

/**
 * Get field3 of an alert action.
 * 
 * @param int Id of the action alert.
 *
 * @return mixed Field3 of the action alert or
 * false in case of empty result
 */
function alerts_get_alert_action_field3 ($id_alert_action) {
	return db_get_value ('field3', 'talert_actions', 'id', $id_alert_action);
}

/**
 * Get name of an alert action.
 * 
 * @param int Id of the action alert.
 *
 * @return mixed Name of the action alert or
 * false in case of empty result
 */
function alerts_get_alert_action_name ($id_alert_action) {
	return db_get_value ('name', 'talert_actions', 'id', $id_alert_action);
}

/**
 * Get types of alert templates.
 * 
 * @return array Types of alert templates.
 */
function alerts_get_alert_templates_types () {
	$types = array ();
	
	$types['regex'] = __('Regular expression');
	$types['max_min'] = __('Max and min');
	$types['max'] = __('Max.');
	$types['min'] = __('Min.');
	$types['equal'] = __('Equal to');
	$types['not_equal'] = __('Not equal to');
	$types['warning'] = __('Warning status');
	$types['critical'] = __('Critical status');
	$types['unknown'] = __('Unknown status');
	$types['onchange'] = __('On Change');
	$types['always'] = __('Always');
	
	return $types;
}

/**
 * Get type name of an alert template.
 * 
 * @param string alert template type.
 *
 * @return string name of the alert template.
 */
function alerts_get_alert_templates_type_name ($type) {
	$types = alerts_get_alert_templates_types ();
	
	if (! isset ($type[$type]))
		return __('Unknown');
	
	return $types[$type];
}

/**
 * Creates an alert template.
 * 
 * @param string Name of the alert template.
 * @param string Type of the alert template.
 * @param mixed Array of alert template values or false.
 *
 * @return string name of the alert template.
 */
function alerts_create_alert_template ($name, $type, $values = false) {
	if (empty ($name))
		return false;
	if (empty ($type))
		return false;
	
	if (! is_array ($values))
		$values = array ();
	$values['name'] = $name;
	$values['type'] = $type;
	
	switch ($type) {
	/* TODO: Check values based on type, return false if failure */
	}
	
	return @db_process_sql_insert ('talert_templates', $values);
}

/**
 * Updates an alert template.
 * 
 * @param int Id of the alert template.
 * @param array Array of alert template values.
 *
 * @return mixed Number of rows affected or false if something goes wrong.
 */
function alerts_update_alert_template ($id_alert_template, $values) {
	$id_alert_template = safe_int ($id_alert_template, 1);
	
	if (empty ($id_alert_template))
		return false;
	if (! is_array ($values))
		return false;
	
	return (@db_process_sql_update ('talert_templates',
		$values,
		array ('id' => $id_alert_template))) !== false;
}

/**
 * Deletes an alert template.
 * 
 * @param int Id of the alert template.
 *
 * @return mixed Number of rows affected or false if something goes wrong.
 */
function alerts_delete_alert_template ($id_alert_template) {
	$id_alert_template = safe_int ($id_alert_template, 1);
	
	if (empty ($id_alert_template))
		return false;
	
	return @db_process_sql_delete ('talert_templates', array ('id' => $id_alert_template));
}

/**
 * Get a set of alert templates.
 * 
 * @param mixed Array with filter conditions or false.
 * @param mixed Array with a set of fields to retrieve or false.
 *
 * @return mixed Array with selected alert templates or false if something goes wrong.
 */
function alerts_get_alert_templates ($filter = false, $fields = false) {
	return @db_get_all_rows_filter ('talert_templates', $filter, $fields);
}

/**
 * Get one alert template.
 * 
 * @param int Id of an alert template.
 *
 * @return mixed Selected alert template or false if something goes wrong.
 */
function alerts_get_alert_template ($id_alert_template) {
	global $config;
	
	$id_alert_template = safe_int ($id_alert_template, 1);
	if (empty ($id_alert_template))
		return false;
	
	switch ($config['dbtype']) {
		case "mysql":
		case "postgresql":
			return db_get_row ('talert_templates', 'id', $id_alert_template);
			break;
		case "oracle":
			$fields_select = db_get_all_rows_sql('SELECT column_name
				FROM user_tab_columns
				WHERE table_name = \'TALERT_TEMPLATES\'
					AND column_name NOT IN (\'TIME_FROM\',\'TIME_TO\')');
			foreach ($fields_select as $field_select){
				$select_field[] = $field_select['column_name'];
			}
			$select_stmt = implode(',', $select_field);
			return db_get_row_sql("SELECT $select_stmt,
					to_char(time_from, 'hh24:mi:ss') AS time_from,
					to_char(time_to, 'hh24:mi:ss') AS time_to
				FROM talert_templates");	
			break;
	}
}

/**
 * Get field1 of talert_templates table.
 * 
 * @param int Id of an alert template.
 *
 * @return mixed Field1 field or false if something goes wrong.
 */
function alerts_get_alert_template_field1 ($id_alert_template) {
	return db_get_value ('field1', 'talert_templates', 'id', $id_alert_template);
}

/**
 * Get field2 of talert_templates table.
 * 
 * @param int Id of an alert template.
 *
 * @return mixed Field2 field or false if something goes wrong.
 */
function alerts_get_alert_template_field2 ($id_alert_template) {
	return db_get_value ('field2', 'talert_templates', 'id', $id_alert_template);
}

/**
 * Get field3 of talert_templates table.
 * 
 * @param int Id of an alert template.
 *
 * @return mixed Field3 field or false if something goes wrong.
 */
function alerts_get_alert_template_field3 ($id_alert_template) {
	return db_get_value ('field3', 'talert_templates', 'id', $id_alert_template);
}

/**
 * Get name of talert_templates table.
 * 
 * @param int Id of an alert template.
 *
 * @return mixed Name field or false if something goes wrong.
 */
function alerts_get_alert_template_name ($id_alert_template) {
	return db_get_value ('name', 'talert_templates', 'id', $id_alert_template);
}

/**
 * Get description of talert_templates table.
 * 
 * @param int Id of an alert template.
 *
 * @return mixed Description field or false if something goes wrong.
 */
function alerts_get_alert_template_description ($id_alert_template) {
	return db_get_value ('description', 'talert_templates', 'id', $id_alert_template);
}

/**
 * Get type of talert_templates table.
 * 
 * @param int Id of an alert template.
 *
 * @return mixed Type field or false if something goes wrong.
 */
function alerts_get_alert_template_type ($id_alert_template) {
	return db_get_value ('type', 'talert_templates', 'id', $id_alert_template);
}

/**
 * Get type's name of alert template.
 * 
 * @param int Id of an alert template.
 *
 * @return mixed Type's name of an alert template or false if something goes wrong.
 */
function alerts_get_alert_template_type_name ($id_alert_template) {
	$type = alerts_get_alert_template_type ($id_alert_template);
	
	return alerts_get_alert_templates_type_name ($type);
}

/**
 * Get value of talert_templates table.
 * 
 * @param int Id of an alert template.
 *
 * @return mixed Value field or false if something goes wrong.
 */
function alerts_get_alert_template_value ($id_alert_template) {
	return db_get_value ('value', 'talert_templates', 'id', $id_alert_template);
}

/**
 * Get max_value of talert_templates table.
 * 
 * @param int Id of an alert template.
 *
 * @return mixed Max_value field or false if something goes wrong.
 */
function alerts_get_alert_template_max_value ($id_alert_template) {
	return db_get_value ('max_value', 'talert_templates', 'id', $id_alert_template);
}

/**
 * Get min_value of talert_templates table.
 * 
 * @param int Id of an alert template.
 *
 * @return mixed Min_value field or false if something goes wrong.
 */
function alerts_get_alert_template_min_value ($id_alert_template) {
	return db_get_value ('min_value', 'talert_templates', 'id', $id_alert_template);
}

/**
 * Get alert_text of talert_templates table.
 * 
 * @param int Id of an alert template.
 *
 * @return mixed Alert_text field or false if something goes wrong.
 */
function alerts_get_alert_template_alert_text ($id_alert_template) {
	return db_get_value ('alert_text', 'talert_templates', 'id', $id_alert_template);
}

/**
 * Get time_from of talert_templates table.
 * 
 * @param int Id of an alert template.
 *
 * @return mixed Time_from field or false if something goes wrong.
 */
function alerts_get_alert_template_time_from ($id_alert_template) {
	return db_get_value ('time_from', 'talert_templates', 'id', $id_alert_template);
}

/**
 * Get time_to of talert_templates table.
 * 
 * @param int Id of an alert template.
 *
 * @return mixed Time_to field or false if something goes wrong.
 */
function alerts_get_alert_template_time_to ($id_alert_template) {
	return db_get_value ('time_to', 'talert_templates', 'id', $id_alert_template);
}

/**
 * Get alert template in weekday format.
 * 
 * @param int Id of an alert template.
 *
 * @return mixed Alert template in weekday format or false if something goes wrong.
 */
function alerts_get_alert_template_weekdays ($id_alert_template) {
	$alert = alerts_get_alert_template ($id_alert_template);
	
	if ($alert === false)
		return false;
	
	$retval = array ();
	$days = array ('monday', 'tuesday', 'wednesday', 'thursday',
		'friday', 'saturday', 'sunday');
	foreach ($days as $day)
		$retval[$day] = (bool) $alert[$day];
	
	return $retval;
}

/**
 * Get recovery_notify of talert_templates table.
 * 
 * @param int Id of an alert template.
 *
 * @return mixed Recovery_notify field or false if something goes wrong.
 */
function alerts_get_alert_template_recovery_notify ($id_alert_template) {
	return db_get_value ('recovery_notify', 'talert_templates', 'id', $id_alert_template);
}

/**
 * Get field2_recovery of talert_templates table.
 * 
 * @param int Id of an alert template.
 *
 * @return mixed Field2_recovery field or false if something goes wrong.
 */
function alerts_get_alert_template_field2_recovery ($id_alert_template) {
	return db_get_value ('field2_recovery', 'talert_templates', 'id', $id_alert_template);
}

/**
 * Get field3_recovery of talert_templates table.
 * 
 * @param int Id of an alert template.
 *
 * @return mixed Field3_recovery field or false if something goes wrong.
 */
function alerts_get_alert_template_field3_recovery ($id_alert_template) {
	return db_get_value ('field3_recovery', 'talert_templates', 'id', $id_alert_template);
}

/**
 * Duplicates an alert template.
 * 
 * @param int Id of an alert template.
 *
 * @return mixed Duplicates an alert template or false if something goes wrong.
 */
function alerts_duplicate_alert_template ($id_alert_template) {
	$template = alerts_get_alert_template ($id_alert_template);
	
	if ($template === false)
		return false;
	
	$name = io_safe_input(__('Copy of') . ' ') . $template['name'];
	$type = $template['type'];
	
	$size = count ($template) / 2;
	for ($i = 0; $i < $size; $i++) {
		unset ($template[$i]);
	}
	unset ($template['name']);
	unset ($template['id']);
	unset ($template['type']);
	$template['value'] = safe_sql_string ($template['value']);
	
	return alerts_create_alert_template ($name, $type, $template);
}

/**
 * Creates an alert associated to a module.
 * 
 * @param int Id of an alert template.
 *
 * @return mixed Alert associated to a module or false if something goes wrong.
 */
function alerts_create_alert_agent_module ($id_agent_module, $id_alert_template, $values = false) {
	if (empty ($id_agent_module))
		return false;
	if (empty ($id_alert_template))
		return false;
	
	if (! is_array ($values))
		$values = array ();
	$values['id_agent_module'] = (int) $id_agent_module;
	$values['id_alert_template'] = (int) $id_alert_template;
	$values['last_reference'] = time();
	
	return @db_process_sql_insert ('talert_template_modules', $values);
}

/**
 * Updates an alert associated to a module.
 * 
 * @param int Id of an alert template.
 * @param array Values of the update.
 *
 * @return mixed Affected rows or false if something goes wrong.
 */
function alerts_update_alert_agent_module ($id_alert_agent_module, $values) {
	if (empty ($id_agent_module))
		return false;
	if (! is_array ($values))
		return false;
	
	return (@db_process_sql_update ('talert_template_modules',
		$values,
		array ('id' => $id_alert_template))) !== false;
}

/**
 * Deletes an alert associated to a module.
 * 
 * @param int Id of an alert template.
 * @param mixed Array with filter conditions to delete.
 *
 * @return mixed Affected rows or false if something goes wrong.
 */
function alerts_delete_alert_agent_module ($id_alert_agent_module, $filter = false) {
	if (empty ($id_alert_agent_module) && ! is_array ($filter))
		return false;
	
	if (! is_array ($filter))
		$filter = array ();
	if ($id_alert_agent_module)
		$filter['id'] = $id_alert_agent_module;
	
	// Get the modules of the fired alerts that will be deleted to update counts
	$filter_get = $filter;
	
	$filter_get['group'] = 'id_agent_module';
	$filter_get['times_fired'] = '>0';
	
	$fired_alert_modules = db_get_all_rows_filter('talert_template_modules', $filter_get, array('id_agent_module', 'COUNT(*) alerts'));
	
	/*
	The deletion of actions from talert_template_module_actions,
	it is automatily because the data base this table have
	a foreing key and delete on cascade.
	*/
	if (@db_process_sql_delete ('talert_template_modules', $filter) !== false) {	
		return true;
	}
	
	return false;
}

/**
 * Get alert associated to a module.
 * 
 * @param int Id of an alert template.
 * @param mixed Array with filter conditions to delete.
 *
 * @return mixed Affected rows or false if something goes wrong.
 */
function alerts_get_alert_agent_module ($id_alert_agent_module) {
	$id_alert_agent_module = safe_int ($id_alert_agent_module, 0);
	
	if (empty ($id_alert_agent_module))
		return false;
	
	return db_get_row ('talert_template_modules', 'id', $id_alert_agent_module);
}

/**
 * Get alert associated to a module.
 * 
 * @param int Id of an alert template.
 * @param bool Disabled or not.
 * @param mixed Filter conditions or false.
 * @param mixed Array with fields to retrieve or false.
 *
 * @return mixed Affected rows or false if something goes wrong.
 */
function alerts_get_alerts_agent_module ($id_agent_module, $disabled = false, $filter = false, $fields = false) {
	$id_alert_agent_module = safe_int ($id_agent_module, 0);
	
	if (! is_array ($filter))
		$filter = array ();
	if (! $disabled)
		$filter['disabled'] = 0;
	$filter['id_agent_module'] = (int) $id_agent_module;
	
	return db_get_all_rows_filter ('talert_template_modules',
		$filter, $fields);
}

/**
 * Get alert associated to a module (only id and name fields).
 * 
 * @param int Id of an alert template.
 * @param bool Disabled or not.
 *
 * @return mixed Affected rows or false if something goes wrong.
 */
function alerts_get_alerts_module_name ($id_agent_module, $disabled = false) {
	$id_alert_agent_module = safe_int ($id_agent_module, 0);
	
	$sql = sprintf ('SELECT a.id, b.name 
		FROM talert_template_modules AS a, talert_templates AS b
		WHERE a.id=b.id AND a.id_agent_module = %d AND a.disabled = %d', 
		$id_agent_module, (int)$disabled);
	
	return db_process_sql($sql);
}


/**
 * Get disabled field of talert_template_modules table.
 * 
 * @param int Id of an alert associated to a module.
 *
 * @return mixed Disabled field or false if something goes wrong.
 */
function alerts_get_alert_agent_module_disabled ($id_alert_agent_module) {
	$id_alert_agent_module = safe_int ($id_alert_agent_module, 0);
	
	return db_get_value ('disabled', 'talert_template_modules', 'id',
		$id_alert_agent_module);
}

/**
 * Force execution of an alert associated to a module.
 * 
 * @param int Id of an alert associated to a module.
 *
 * @return mixed Affected rows or false if something goes wrong.
 */
function alerts_agent_module_force_execution ($id_alert_agent_module) {
	$id_alert_agent_module = safe_int ($id_alert_agent_module, 0);
	
	return (@db_process_sql_update ('talert_template_modules',
		array ('force_execution' => 1),
		array ('id' => $id_alert_agent_module))) !== false;
}

/**
 * Disable/Enable an alert associated to a module.
 * 
 * @param int Id of an alert associated to a module.
 * @param bool Whether to enable or disable an alert.
 *
 * @return mixed Affected rows or false if something goes wrong.
 */
function alerts_agent_module_disable ($id_alert_agent_module, $disabled) {
	$id_alert_agent_module = safe_int ($id_alert_agent_module, 0);
	
	return (@db_process_sql_update ('talert_template_modules',
		array ('disabled' => (bool) $disabled),
		array ('id' => $id_alert_agent_module))) !== false;
}

/**
 * Disable/Enable stanby of an alert associated to a module.
 * 
 * @param int Id of an alert associated to a module.
 * @param bool Whether to enable or disable stanby of an alert.
 *
 * @return mixed Affected rows or false if something goes wrong.
 */
function alerts_agent_module_standby ($id_alert_agent_module, $standby) {
	$id_alert_agent_module = safe_int ($id_alert_agent_module, 0);
	
	return (@db_process_sql_update ('talert_template_modules',
		array ('standby' => (bool) $standby),
		array ('id' => $id_alert_agent_module))) !== false;
}

/**
 * Get last fired of an alert associated to a module.
 * 
 * @param int Id of an alert associated to a module.
 *
 * @return mixed Affected rows or false if something goes wrong.
 */
function alerts_get_alerts_agent_module_last_fired ($id_alert_agent_module) {
	$id_alert_agent_module = safe_int ($id_alert_agent_module, 1);
	
	return db_get_value ('last_fired', 'talert_template_modules', 'id',
		$id_alert_agent_module);
}

/**
 * Add an action to an alert associated to a module.
 * 
 * @param int Id of an alert associated to a module.
 * @param int Id of an alert.
 * @param mixed Options of the action.
 *
 * @return mixed Affected rows or false if something goes wrong.
 */
function alerts_add_alert_agent_module_action ($id_alert_template_module, $id_alert_action, $options = false) {
	global $config;
	
	if (empty ($id_alert_template_module))
		return false;
	if (empty ($id_alert_action))
		return false;
	
	$values = array ();
	$values['id_alert_template_module'] = (int) $id_alert_template_module;
	$values['id_alert_action'] = (int) $id_alert_action;
	$values['fires_max'] = 0;
	$values['fires_min'] = 0;
	$values['module_action_threshold'] = 0;
	if ($options) {
		$max = 0;
		$min = 0;
		if (isset ($options['fires_max']))
			$values['fires_max'] = $options['fires_max'];
		if (isset ($options['fires_min']))
			$values['fires_min'] = $options['fires_min'];
		if (isset ($options['module_action_threshold']))
			$values['module_action_threshold'] = (int) $options['module_action_threshold'];
	}
	
	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":
			return (@db_process_sql_insert(
				'talert_template_module_actions', $values)) !== false;
			break;
		case "oracle":
			return (@db_process_sql_insert(
				'talert_template_module_actions', $values, false)) !== false;
			break;
	}
}

/**
 * Delete an action to an alert associated to a module.
 * 
 * @param int Id of an alert associated to a module.
 *
 * @return mixed Affected rows or false if something goes wrong.
 */
function alerts_delete_alert_agent_module_action ($id_alert_agent_module_action) {
	if (empty ($id_alert_agent_module_action))
		return false;
	
	return (@db_process_sql_delete ('talert_template_module_actions',
		array ('id' => $id_alert_agent_module_action))) !== false;
}

/**
 * Get actions of an alert associated to a module.
 * 
 * @param int Id of an alert associated to a module.
 * @param mixed Array with fields to retrieve or false.
 *
 * @return mixed Actions associated or false if something goes wrong.
 */
function alerts_get_alert_agent_module_actions ($id_alert_agent_module, $fields = false) {
	if (empty ($id_alert_agent_module))
		return false;
	
	$actions = db_get_all_rows_filter ('talert_template_module_actions',
		array ('id_alert_template_module' => $id_alert_agent_module),
		$fields);
	
	if ($actions === false)
		return array ();
	if ($fields !== false)
		return $actions;
	
	$retval = array ();
	foreach ($actions as $element) {
		$action = alerts_get_alert_action ($element['id_alert_action']);
		$action['fires_min'] = $element['fires_min'];
		$action['fires_max'] = $element['fires_max'];
		$action['module_action_threshold'] = $element['module_action_threshold'];
		
		if (isset($element['id']))
			$retval[$element['id']] = $action;
	}
	
	return $retval;
}

/**
 *  Validates an alert id or an array of alert id's.
 *
 * @param mixed Array of alerts ids or single id.
 * @param bool Whether to check ACLs
 *
 * @return bool True if it was successful, false otherwise.
 */
function alerts_validate_alert_agent_module ($id_alert_agent_module, $noACLs = false) {
	global $config;
	
	include_once ("include/functions_events.php");
	
	$alerts = safe_int ($id_alert_agent_module, 1);
	
	if (empty ($alerts)) {
		return false;
	}
	
	$alerts = (array) $alerts;
	
	foreach ($alerts as $id) {
		$alert = alerts_get_alert_agent_module ($id);
		$agent_id = modules_get_agentmodule_agent ($alert["id_agent_module"]);
		$group_id = agents_get_agent_group ($agent_id);
		$critical_instructions = db_get_value('critical_instructions', 'tagente_modulo', 'id_agente_modulo', $agent_id);
		$warning_instructions = db_get_value('warning_instructions', 'tagente_modulo', 'id_agente_modulo', $agent_id);
		$unknown_instructions = db_get_value('unknown_instructions', 'tagente_modulo', 'id_agente_modulo', $agent_id);
		
		if (!$noACLs) {
			if (! check_acl ($config['id_user'], $group_id, "AW")) {
				continue; 
			}
		}
		$result = db_process_sql_update ('talert_template_modules',
			array ('times_fired' => 0,
				'internal_counter' => 0),
			array ('id' => $id));
		
		
		if ($result > 0) {
			// Update fired alert count on the agent
			db_process_sql(sprintf('UPDATE tagente SET update_alert_count=1 WHERE id_agente = %d', $agent_id));
			
			events_create_event ("Manual validation of alert for ".
				alerts_get_alert_template_description ($alert["id_alert_template"]),
				$group_id, $agent_id, 1, $config["id_user"],
				"alert_manual_validation", 1, $alert["id_agent_module"],
				$id, $critical_instructions, $warning_instructions, $unknown_instructions);
		}
		elseif ($result === false) {
			return false;
		}
	}
	return true;
}

/**
 * Copy an alert defined in a module agent to other module agent.
 * 
 * This function avoid duplicated insertion.
 * 
 * @param int Source agent module id.
 * @param int Detiny agent module id.
 *
 * @return New alert id on success. Existing alert id if it already exists.
 * False on error.
 */
function alerts_copy_alert_module_to_module ($id_agent_alert, $id_destiny_module) {
	global $config;
	
	$alert = alerts_get_alert_agent_module ($id_agent_alert);
	if ($alert === false)
		return false;
	
	$alerts = alerts_get_alerts_agent_module ($id_destiny_module, false,
		array ('id_alert_template' => $alert['id_alert_template']));
	if (! empty ($alerts)) {
		return $alerts[0]['id'];
	}
	
	/* PHP copy arrays on assignment */
	$new_alert = array ();
	$new_alert['id_agent_module'] = (int) $id_destiny_module;
	$new_alert['id_alert_template'] = $alert['id_alert_template'];
	
	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":
			$id_new_alert = @db_process_sql_insert (
				'talert_template_modules', $new_alert);
			break;
		case "oracle":
			$id_new_alert = @db_process_sql_insert (
				'talert_template_modules', $new_alert, false);
			break;
	}
	
	if ($id_new_alert === false) {
		return false;
	}
	
	$actions = alerts_get_alert_agent_module_actions ($id_agent_alert);
	if (empty ($actions))
		return $id_new_alert;
	
	foreach ($actions as $action) {
		$result = alerts_add_alert_agent_module_action ($id_new_alert, $action['id'],
			array ('fires_min' => $action['fires_min'],
				'fires_max' => $action['fires_max']));
		if ($result === false)
			return false;
	}
	
	return $id_new_alert;
}

/**
 * Get agents with an specific alert template.
 *
 * @param int Id of the alert template.
 * @param int Id of the group of agents.
 * @param mixed Array with filter conditions or false.
 * @param mixed Array with fields to retrieve or false.
 *
 * @return mixed Affected rows or false is something goes wrong.
 */
function alerts_get_agents_with_alert_template ($id_alert_template, $id_group, $filter = false, $fields = false, $id_agents = false) {
	global $config;
	
	if (empty ($id_alert_template))
		return false;
	
	if (! is_array ($filter))
		$filter = array ();
	$filter[] = 'tagente_modulo.id_agente_modulo = talert_template_modules.id_agent_module';
	$filter[] = 'tagente_modulo.id_agente = tagente.id_agente';
	$filter['id_alert_template'] = $id_alert_template;
	$filter['tagente_modulo.disabled'] = '<> 1';
	$filter['delete_pending'] = '<> 1';
	
	if (empty ($id_agents)) {
		switch ($config["dbtype"]) {
			case "mysql":
				$filter['`tagente`.id_agente'] = array_keys (agents_get_group_agents ($id_group, false, "none"));
				break;
			case "postgresql":
			case "oracle":
				$filter['tagente.id_agente'] = array_keys (agents_get_group_agents ($id_group, false, "none"));
				break;
		}
	}
	else {
		switch ($config["dbtype"]) {
			case "mysql":
				$filter['`tagente`.id_agente'] = $id_agents;
				break;
			case "postgresql":
			case "oracle":
				$filter['tagente.id_agente'] = $id_agents;
				break;
		}
	}
	
	return db_get_all_rows_filter ('tagente, tagente_modulo, talert_template_modules',
		$filter, $fields);
}

/**
 * Get type name for alerts (e-mail, text, internal, ...) based on type number
 *
 * @param int id_alert Alert type id.
 *
 * @return string Type name of the alert.
 */
function get_alert_type ($id_type) {
	return (string) db_get_value ('name', 'talert_templates', 'id', (int) $id_type);
}

/**
 * Get all the fired of alerts happened in an Agent during a period of time.
 *
 * The returned alerts will be in the time interval ($date - $period, $date]
 *
 * @param int $id_agent Agent id to get events.
 * @param int $period Period of time in seconds to get events.
 * @param int $date Beginning date to get events.
 *
 * @return array An array with all the events happened.
 */
function get_agent_alert_fired ($id_agent, $id_alert, $period, $date = 0) {
	if (!is_numeric ($date)) {
		$date = strtotime ($date);
	}
	if (empty ($date)) {
		$date = get_system_time ();
	}
	
	$datelimit = $date - $period;
	
	$sql = sprintf ('SELECT timestamp
		FROM tevento
		WHERE id_agente = %d AND utimestamp > %d
			AND utimestamp <= %d
			AND id_alert_am = %d 
		ORDER BY timestamp DESC', $id_agent, $datelimit, $date, $id_alert);
	
	return db_get_all_rows_sql ($sql);
}

/**
 * Get all the fired of alerts happened in an Agent module during a period of time.
 *
 * The returned alerts will be in the time interval ($date - $period, $date]
 *
 * @param int $id_agent_module Agent module id to get events.
 * @param int $period Period of time in seconds to get events.
 * @param int $date Beginning date to get events.
 *
 * @return array An array with all the events happened.
 */
function get_module_alert_fired ($id_agent_module, $id_alert, $period, $date = 0) {
	
	if (!is_numeric ($date)) {
		$date = strtotime ($date);
	}
	if (empty ($date)) {
		$date = get_system_time ();
	}
	
	$datelimit = $date - $period;
	
	$sql = sprintf ('SELECT timestamp
		FROM tevento
		WHERE id_agentmodule = %d AND utimestamp > %d
			AND utimestamp <= %d
			AND id_alert_am = %d 
		ORDER BY timestamp DESC', $id_agent_module, $datelimit, $date, $id_alert);
	
	return db_get_all_rows_sql ($sql);
}

/**
 * Get all the times an alerts fired during a period.
 *
 * @param int Alert module id.
 * @param int Period timed to check from date
 * @param int Date to check (current time by default)
 *
 * @return int The number of times an alert fired.
 */
function get_alert_fires_in_period ($id_alert_module, $period, $date = 0) {
	global $config;
	
	if (!$date)
		$date = get_system_time ();
	
	$datelimit = $date - $period;
	
	switch ($config["dbtype"]) {
		case "mysql":
			$sql = sprintf ("SELECT COUNT(`id_agentmodule`)
				FROM `tevento`
				WHERE `event_type` = 'alert_fired'
					AND `id_alert_am` = %d
					AND `utimestamp` > %d 
					AND `utimestamp` <= %d",
				$id_alert_module, $datelimit, $date);
			break;
		case "postgresql":
		case "oracle":
			$sql = sprintf ("SELECT COUNT(id_agentmodule)
				FROM tevento
				WHERE event_type = 'alert_fired'
					AND id_alert_am = %d
					AND utimestamp > %d 
					AND utimestamp <= %d",
				$id_alert_module, $datelimit, $date);
			break;
	}
	
	return (int) db_get_sql ($sql);
}

/**
 * Get all the alerts defined in a group.
 *
 * It gets all the alerts of all the agents on a given group.
 *
 * @param int $id_group Group id to check.
 *
 * @return array An array with alerts dictionaries defined in a group.
 */
function get_group_alerts($id_group, $filter = '', $options = false,
	$where = '', $allModules = false, $orderby = false,
	$idGroup = false, $count = false, $strict_user = false, $tag = false) {

	global $config;
	
	$group_query = '';
	if (!empty($idGroup)) {
		$group_query = ' AND id_grupo = ' . $idGroup;
	}
		
	if (is_array($filter)) {
		$disabled = $filter['disabled'];
		if (isset($filter['standby'])) {
			$filter = $group_query . ' AND talert_template_modules.standby = "'.$filter['standby'].'"';
		}
		else {
			$filter = $group_query;
		}
	}
	else {
		$filter = $group_query;
		$disabled = $filter;
	}
	
	switch ($disabled) {
		case "notfired":
			$filter .= ' AND times_fired = 0 AND talert_template_modules.disabled = 0';
			break;
		case "fired":
			$filter .= ' AND times_fired > 0 AND talert_template_modules.disabled = 0';
			break;
		case "disabled":
			$filter .= ' AND talert_template_modules.disabled = 1';
			break;
		case "all_enabled":
			$filter .= ' AND talert_template_modules.disabled = 0';
			break;
		default:
			$filter .= '';
			break;
	}

	if ($tag) {
		$filter .= ' AND (id_agent_module IN (SELECT id_agente_modulo FROM ttag_module WHERE id_tag IN ('.$tag.')))';
	}
	if (is_array ($options)) {
		$filter .= db_format_array_where_clause_sql ($options);
	}
	
	
	if ($id_group !== false) {
		$groups = users_get_groups($config["id_user"], "AR");

		//$where_tags = tags_get_acl_tags($config['id_user'], array_keys($groups), 'AR', 'module_condition', 'AND', 'tagente_modulo'); 

		if ($id_group != 0) {
			if (is_array($id_group)) {
				if (in_array(0, $id_group)) {
					$id_group = 0;
				}
			}
			
			if (is_array($id_group)) {
				if (empty($id_group)) {
					$subQuery = 'SELECT id_agente_modulo
						FROM tagente_modulo
						WHERE 1 = 0';
				}
				else {
					$subQuery = 'SELECT id_agente_modulo
						FROM tagente_modulo
						WHERE delete_pending = 0
							AND id_agente IN (SELECT id_agente
								FROM tagente
								WHERE
									id_grupo IN (' . implode(',', $id_group) . '))';
				}
			}
			else {
				$subQuery = 'SELECT id_agente_modulo
					FROM tagente_modulo
					WHERE delete_pending = 0
						AND id_agente IN (SELECT id_agente
							FROM tagente WHERE id_grupo = ' . $idGroup . ')';
			}
		}
		else {
			//ALL GROUP
			$subQuery = 'SELECT id_agente_modulo
				FROM tagente_modulo WHERE delete_pending = 0';
		}

		if ($strict_user) {
			$groups = users_get_groups($config["id_user"]);

			if ($idGroup !== 0) {
				$where_tags = tags_get_acl_tags($config['id_user'], $idGroup, 'AR', 'module_condition', 'AND', 'tagente_modulo', true, array(), true); 
			} else {
				$where_tags = tags_get_acl_tags($config['id_user'], array_keys($groups), 'AR', 'module_condition', 'AND', 'tagente_modulo', true, array(), true); 
			}
		
			// If there are any errors add imposible condition
			if (in_array($where_tags, array(ERR_WRONG_PARAMETERS, ERR_ACL))) {
				$subQuery .= ' AND 1 = 0';
			} 
			else {
				$subQuery .= $where_tags;
			}
		}
	}
	else {
		if ($allModules)
			$disabled = '';
		else
			$disabled = 'WHERE disabled = 0';
		
		$subQuery = 'SELECT id_agente_modulo
			FROM tagente_modulo ' . $disabled;
	}
	
	$orderbyText = '';
	if ($orderby !== false) {
		if (is_array($orderby)) {
			$orderbyText = sprintf("ORDER BY %s", $orderby['field'], $orderby['order']);
		}
		else {
			$orderbyText = sprintf("ORDER BY %s", $orderby);
		}
	}
	
	$selectText = 'talert_template_modules.*, t2.nombre AS agent_module_name, t3.nombre AS agent_name, t4.name AS template_name';
	if ($count !== false) {
		$selectText = 'COUNT(talert_template_modules.id) AS count';
	}
	
	$sql = sprintf ("SELECT %s
		FROM talert_template_modules
			INNER JOIN tagente_modulo t2
				ON talert_template_modules.id_agent_module = t2.id_agente_modulo
			INNER JOIN tagente t3
				ON t2.id_agente = t3.id_agente
			INNER JOIN talert_templates t4
				ON talert_template_modules.id_alert_template = t4.id
		WHERE id_agent_module in (%s) %s %s %s",
	$selectText, $subQuery, $where, $filter, $orderbyText);
	$alerts = db_get_all_rows_sql ($sql);
	
	if ($alerts === false)
		return array ();
	
	if ($count !== false) {
		return $alerts[0]['count'];
	}
	else {
		return $alerts;	
	}
}

/**
 * Get all the alerts fired during a period, given a list of alerts.
 *
 * @param array A list of alert modules to check. See get_alerts_in_group()
 * @param int Period of time to check fired alerts.
 * @param int Beginning date to check fired alerts in UNIX format (current date by default)
 *
 * @return array An array with the alert id as key and the number of times
 * the alert was fired (only included if it was fired).
 */
function get_alerts_fired ($alerts, $period = 0, $date = 0) {
	if (! $date)
		$date = get_system_time ();
	
	$datelimit = $date - $period;
	
	$alerts_fired = array ();
	$agents = array ();
	
	foreach ($alerts as $alert) {
		if (isset($alert['id'])) {
			$fires = get_alert_fires_in_period($alert['id'],
				$period, $date);
			if (! $fires) {
				continue;
			}
			$alerts_fired[$alert['id']] = $fires;
		}
	}
	
	return $alerts_fired;
}

/**
 * Get the last time an alert fired during a period.
 *
 * @param int Alert agent module id.
 * @param int Period timed to check from date
 * @param int Date to check (current date by default)
 *
 * @return int The last time an alert fired. It's an UNIX timestamp.
 */
function get_alert_last_fire_timestamp_in_period ($id_alert_module, $period, $date = 0) {
	global $config;
	
	if ($date == 0) {
		$date = get_system_time ();
	}
	$datelimit = $date - $period;
	
	switch ($config["dbtype"]) {
		case "mysql":
			$sql = sprintf ("SELECT MAX(`utimestamp`)
				FROM `tevento`
				WHERE `event_type` = 'alert_fired'
					AND `id_alert_am` = %d
					AND `utimestamp` > %d 
					AND `utimestamp` <= %d",
				$id_alert_module, $datelimit, $date);
			break;
		case "postgresql":
		case "oracle":
			$sql = sprintf ("SELECT MAX(utimestamp)
				FROM tevento
				WHERE event_type = 'alert_fired'
					AND id_alert_am = %d
					AND utimestamp > %d 
					AND utimestamp <= %d",
				$id_alert_module, $datelimit, $date);
			break;
	}
	
	return db_get_sql ($sql);
}

/**
 * Insert in talert_special_days a new special day.
 *
 * @param date of special day.
 * @param same day of the week.
 * @param mixed A single value or array of values to insert (can be a multiple a mount of rows).
 * 
 * @return mixed False in case of error or invalid values passed. Affected rows otherwise.
 */
function alerts_create_alert_special_day ($date, $same_day, $values = false) {
	if (empty ($date))
		return false;
	if (empty ($same_day))
		return false;
	
	if (! is_array ($values))
		$values = array ();
	$values['date'] = $date;
	$values['same_day'] = $same_day;
	
	return @db_process_sql_insert ('talert_special_days', $values);
}  

/**
 * Update a special day in talert_special_days.
 *
 * @param int special day Id.
 * @param mixed Array of values to update.
 * 
 * @return mixed False in case of error or invalid values passed. Affected rows otherwise
 */
function alerts_update_alert_special_day ($id_special_day, $values) { 
	$id_special_day = safe_int ($id_special_day, 1);
	
	if (empty ($id_special_day))
		return false;
	if (! is_array ($values))
		return false;
	
	return (@db_process_sql_update ('talert_special_days',
		$values,
		array ('id' => $id_special_day))) !== false;
}

/**
 * Delete a special day in talert_special_days.
 *
 * @param int special day Id.
 *
 * @return mixed False in case of error or invalid values passed. Affected rows otherwise
 */
function alerts_delete_alert_special_day ($id_special_day) {
	$id_special_day = safe_int ($id_special_day, 1);
	
	if (empty ($id_special_day))
		return false;
	
	return (@db_process_sql_delete ('talert_special_days',
		array ('id' => $id_special_day))) !== false;
}

/**
 * Get a special day in talert_special_days.
 *
 * @param int special day Id.
 * 
 * @return mixed False in case of error or invalid values passed. All row of the selected command otherwise
 */
function alerts_get_alert_special_day ($id_special_day) {
	$id_special_day = safe_int ($id_special_day, 1);
	
	if (empty ($id_special_day))
		return false;
	
	return db_get_row ('talert_special_days', 'id', $id_special_day);
}

/**
 * Get number of alert fired that an action is executed. Only fot non default alerts
 * 
 * @param mixed action
 * 
 * @return mixed array with numeric indexes and 0|1 values for not executing or executing. 
 * Returned 'everytime' for always situations
 * Returned $escalation['greater_than'] = VALUE for 'min - infinite' situations
 */
function alerts_get_action_escalation($action) {
	$escalation = array();
	
	if ($action['fires_min'] == 0 && $action['fires_max'] == 0) {
		$escalation = 'everytime';
	}
	else if($action['fires_min'] == $action['fires_max']) {
		for($i=1;$i<$action['fires_min'];$i++) {
			$escalation[$i] = 0;
		}
		$escalation[$action['fires_max']] = 1;
	}
	else if($action['fires_min'] < $action['fires_max']) {
		for($i=1;$i<=$action['fires_max'];$i++) {
			if ($i <= $action['fires_min']) {
				$escalation[$i] = 0;
			}
			else {
				$escalation[$i] = 1;
			}
		}
	}
	else if($action['fires_min'] > $action['fires_max']) {
		$escalation['greater_than'] = $action['fires_min'];
	}
	
	return $escalation;
}


/**
 * Get escalation of all the actions
 * 
 * @param mixed Actions of an alert
 * @param mixed Default action of an alert
 * 
 * @return mixed Actions array including the default action and the escalation of each action
 */
function alerts_get_actions_escalation($actions, $default_action = 0) {
	$escalation = array();
	foreach($actions as $kaction => $action) {
		$escalation[$kaction] = alerts_get_action_escalation($action);
	}
	
	$default_escalation = alerts_get_default_action_escalation($default_action, $escalation);
	$escalation = array(0 => $default_escalation) + $escalation;
	
	$escalation = alerts_normalize_actions_escalation($escalation);

	// Join the actions with the default action
	$actions = array(0 => $default_action) + $actions;
	
	// Add the escalation values to the actions array
	foreach(array_keys($actions) as $kaction) {
		$actions[$kaction]['escalation'] = $escalation[$kaction];
	}
	
	return $actions;
}

/**
 * Get escalation of default action. A default action will be executed when the alert is fired and
 * no other action is executed
 * 
 * @param mixed Default action of the alert
 * @param mixed Escalation of all the other actions
 * 
 * @return mixed Array with the escalation of the default alert
 */
function alerts_get_default_action_escalation($default_action, $escalation) {
	if ($default_action === 0) {
		return array();
	}
	
	$busy_times = array();
	$busy_greater_than = -1;
	foreach($escalation as $action_escalation) {
		if ($action_escalation == 'everytime') {
			return 'never';
		}
		else if (isset($action_escalation['greater_than'])) {
			if ($busy_greater_than == -1 || $action_escalation['greater_than'] < $busy_greater_than) {
				$busy_greater_than = $action_escalation['greater_than'];
			}
		}
		else {
			foreach($action_escalation as $k => $v) {
				if (!isset($busy_times[$k])) {
					$busy_times[$k] = 0;
				}
				
				$busy_times[$k] += $v;
			}
		}
	}
	
	// Set to 1 the busy executions
	// Set to 2 the min - infinite situations
	foreach($busy_times as $k => $v) {
		if ($busy_greater_than != -1) {
			if ($k == ($busy_greater_than + 1)) {
				$busy_times[$k] = 2;
			}
			else if ($k > ($busy_greater_than + 1)) {
				unset($busy_times[$k]);
			}
		}
		else if ($v > 1) {
			$busy_times[$k] = 1;
		}
	}
	
	// Fill gaps from last busy to greater than
	if ($busy_greater_than != -1) {
		for($i=(count($busy_times)+1);$i<=$busy_greater_than;$i++) {
			$busy_times[$i] = 0;
		}
		$busy_times[$i] = 2;
	}

	// Set as default execution the not busy times
	$default_escalation = array();
	foreach($busy_times as $k => $v) {
		switch($v) {
			case 0:
				$default_escalation[$k] = 1;
				break;
			default:
				$default_escalation[$k] = 0;
				break;
		}
		
		// Last element
		if ($k == count($busy_times)) {
			switch($v) {
				case 2:
					if ($default_escalation[$k] == 0) {
						unset($default_escalation[$k]);
					}
					break;
				default:
					$default_escalation[$k+1] = 1;
					break;
			}
		}
	}
	
	if (empty($busy_times)) {
		if ($busy_greater_than == -1) {
			$default_escalation = 'everytime';
		}
		else {
			for($i=1;$i<=$busy_greater_than;$i++) {
				$default_escalation[$i] = 1;
			}
		}
	}
	
	return $default_escalation;
}

/**
 * Normalize escalation to have same number of elements setting all 
 * of them the same number of elements
 * 
 * @param mixed Escalation of the alerts
 * 
 * @return mixed Escalation of the alerts with same number of elements
 * */
function alerts_normalize_actions_escalation($escalation) {
	$max_elements = 0;
	$any_greater_than = false;
	foreach($escalation as $k => $v) {
		if (is_array($v) && isset($v['greater_than'])) {
			$escalation[$k] = array();
			for($i=1;$i<$v['greater_than'];$i++) {
				$escalation[$k][$i] = 0;
			}
			$escalation[$k][$v['greater_than']] = 2;
			$any_greater_than = true;
		}
		
		$n = count($escalation[$k]);
		if ($n > $max_elements) {
			$max_elements = $n;
		}
	}

	if ($max_elements == 1 || !$any_greater_than) {
		$nelements = $max_elements;
	}
	else {
		$nelements = $max_elements+1;
	}
	
	foreach($escalation as $k => $v) {
		if ($v == 'everytime') {
			$escalation[$k] = array_fill(1, $nelements, 1);
			$escalation[$k][$max_elements] = 2;
		}
		else if ($v == 'never') {
			$escalation[$k] = array_fill(1, $nelements, 0);
		}
		else {	
			$fill_value = 0;
			for($i=1;$i<=$nelements;$i++) {
				if (!isset($escalation[$k][$i])) {
					$escalation[$k][$i] = $fill_value;
				}
				else if ($escalation[$k][$i] == 2) {
					$fill_value = 1;
					$escalation[$k][$i] = 0;
				}
			}
		}
	}
		
	return $escalation;
}

?>
