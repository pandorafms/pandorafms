<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package    Include
 * @subpackage Alerts
 */

require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_modules.php';
require_once $config['homedir'].'/include/functions_users.php';


function alerts_get_alerts($id_group=0, $free_search='', $status='all', $standby=-1, $acl=false, $total=false, $id_agent=0)
{
    $sql = '';
    $alerts = [];

    // ----------- Group ------------------------------------------------
    if ($id_group != 0) {
        if ($acl !== false) {
            $groups = users_get_groups(false, $acl, false);

            if (array_key_exists($id_group, $groups)) {
                $group_query = ' AND t3.id_grupo = '.$id_group.' ';
            } else {
                // Set to fail the query
                $group_query = ' AND 1=0 ';
            }
        } else {
            $group_query = ' AND t3.id_grupo = '.$id_group.' ';
        }
    } else {
        if ($acl !== false) {
            $groups = users_get_groups(false, $acl, false);

            $id_groups = array_keys($groups);

            $group_query = ' AND (
				t3.id_grupo IN ('.implode(',', $id_groups).')
				OR tasg.id_group IN ('.implode(',', $id_groups).')
			)';
        } else {
            $group_query = '';
        }
    }

    // ------------ Status ----------------------------------------------
    switch ($status) {
        case 'notfired':
            $status_query = ' AND t0.times_fired = 0 AND t0.disabled = 0';
        break;

        case 'fired':
            $status_query = ' AND t0.times_fired > 0 AND t0.disabled = 0';
        break;

        case 'disabled':
            $status_query = ' AND t0.disabled = 1';
        break;

        case 'all_enabled':
            $status_query = ' AND t0.disabled = 0';
        break;

        default:
            $status_query = '';
        break;
    }

    // ----------- Standby ----------------------------------------------
    $standby_query = '';
    if ($standby != -1) {
        $status_query .= ' AND t0.standby = '.$standby.' ';
    }

    // ----------- Free search ------------------------------------------
    $free_search = io_safe_input($free_search);

    // ----------- Make the query ---------------------------------------
    if ($total) {
        $sql = 'SELECT COUNT(*)';
    } else {
        $sql = 'SELECT *, t2.nombre AS module_name,
			t3.nombre AS agent_name, t3.alias AS agent_alias,
			t1.name AS template_name,
			t0.disabled AS alert_disabled ';
    }

    $sql .= '
		FROM talert_template_modules AS t0
		INNER JOIN talert_templates t1
			ON t0.id_alert_template = t1.id
		INNER JOIN tagente_modulo t2
			ON t0.id_agent_module = t2.id_agente_modulo
		INNER JOIN tagente t3
			ON t2.id_agente = t3.id_agente
		LEFT JOIN tagent_secondary_group tasg
			ON tasg.id_agent = t3.id_agente
		WHERE 1=1
			'.$status_query.' '.$standby_query.' '.$group_query.'
			AND (t1.name LIKE "%'.$free_search.'%"
				OR t2.nombre LIKE "%'.$free_search.'%"
				OR t3.nombre LIKE "%'.$free_search.'%")';

    if ($id_agent != 0) {
        $sql .= ' AND t3.id_agente = '.$id_agent;
    }

    // Only enabled agent.
    $sql .= ' AND t3.disabled = 0';

    $row_alerts = db_get_all_rows_sql($sql);

    if ($total) {
        return reset($row_alerts[0]);
    } else {
        return $row_alerts;
    }
}


/**
 * Get fired status from any alert of agent in group.
 *
 * @param integer $idGroup The ID of group.
 * @param mixed   $type    The list of types to search or type. By default "alert_fired".
 *
 * @return mixed Return id if the group have any alert is fired or false is not.
 */
function alerts_get_event_status_group($idGroup, $type='alert_fired', $query='AND 1=1', $agents=null)
{
    global $config;

    $return = false;

    $typeWhere = '';

    if (!is_array($type)) {
        $typeWhere = ' AND event_type = "'.$type.'" ';
    } else {
        $temp = [];
        foreach ($type as $item) {
            array_push($temp, $item);
        }

        $typeWhere = ' AND event_type IN (';

        foreach ($temp as $ele) {
            $typeWhere .= "'".$ele."'";

            if ($ele != end($temp)) {
                $typeWhere .= ',';
            }
        }

        $typeWhere .= ')';
    }

    if ($agents == null) {
        $agents = agents_get_group_agents($idGroup, false, 'lower', false);

        $idAgents = array_keys($agents);
    } else {
        $idAgents = array_values($agents);
    }

    $sql = sprintf(
        'SELECT id_evento
        FROM tevento
        WHERE estado = 0
            AND id_agente IN (0, %s)
            %s
            %s
        ORDER BY id_evento DESC
        LIMIT 1',
        implode(',', $idAgents),
        $typeWhere,
        $query
    );

    $result = db_get_all_rows_sql($sql);

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
function alerts_create_alert_command($name, $command, $values=false)
{
    if (empty($name)) {
        return false;
    }

    if (empty($command)) {
        return false;
    }

    if (! is_array($values)) {
        $values = [];
    }

    $values['name'] = $name;
    $values['command'] = $command;

    return @db_process_sql_insert('talert_commands', $values);
}


/**
 * Update a command in talert_commands.
 *
 * @param int Alert command Id.
 * @param mixed Array of values to update.
 *
 * @return mixed False in case of error or invalid values passed. Affected rows otherwise
 */
function alerts_update_alert_command($id_alert_command, $values)
{
    $id_alert_command = safe_int($id_alert_command, 1);
    if (empty($id_alert_command)) {
        return false;
    }

    if (! is_array($values)) {
        return false;
    }

    return (@db_process_sql_update(
        'talert_commands',
        $values,
        ['id' => $id_alert_command]
    )) !== false;
}


/**
 * Delete a command in talert_commands.
 *
 * @param int Alert command Id.
 *
 * @return mixed False in case of error or invalid values passed. Affected rows otherwise
 */
function alerts_delete_alert_command($id_alert_command)
{
    $id_alert_command = safe_int($id_alert_command, 1);
    if (empty($id_alert_command)) {
        return false;
    }

    return (@db_process_sql_delete(
        'talert_commands',
        ['id' => $id_alert_command]
    )) !== false;
}


/**
 * Get a command in talert_commands.
 *
 * @param int Alert command Id.
 *
 * @return mixed False in case of error or invalid values passed. All row of the selected command otherwise
 */
function alerts_get_alert_command($id_alert_command)
{
    $id_alert_command = safe_int($id_alert_command, 1);
    if (empty($id_alert_command)) {
        return false;
    }

    return db_get_row('talert_commands', 'id', $id_alert_command);
}


/**
 * Get name of a command in talert_commands.
 *
 * @param int Alert command Id.
 *
 * @return mixed False in case of error or invalid values passed. Command name otherwise
 */
function alert_get_alert_command_name($id_alert_command)
{
    $id_alert_command = safe_int($id_alert_command, 1);
    if (empty($id_alert_command)) {
        return false;
    }

    return db_get_value('name', 'talert_commands', 'id', $id_alert_command);
}


/**
 * Get command field of a command in talert_commands.
 *
 * @param int Alert command Id.
 *
 * @return mixed False in case of error or invalid values passed. Command field otherwise
 */
function alerts_get_alert_command_command($id_alert_command)
{
    $id_alert_command = safe_int($id_alert_command, 1);
    if (empty($id_alert_command)) {
        return false;
    }

    return db_get_value('command', 'talert_commands', 'id', $id_alert_command);
}


/**
 * Get internal field of a command in talert_commands.
 *
 * @param int Alert command Id.
 *
 * @return mixed False in case of error or invalid values passed. Internal field otherwise
 */
function alerts_get_alert_command_internal($id_alert_command)
{
    $id_alert_command = safe_int($id_alert_command, 1);
    if (empty($id_alert_command)) {
        return false;
    }

    return (bool) db_get_value('internal', 'talert_commands', 'id', $id_alert_command);
}


/**
 * Get description field of a command in talert_commands.
 *
 * @param int Alert command Id.
 *
 * @return mixed False in case of error or invalid values passed. Description field otherwise
 */
function alerts_get_alert_command_description($id_alert_command)
{
    $id_alert_command = safe_int($id_alert_command, 1);
    if (empty($id_alert_command)) {
        return false;
    }

    return db_get_value('description', 'talert_commands', 'id', $id_alert_command);
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
function alerts_create_alert_action($name, $id_alert_command, $values=false)
{
    $id_alert_command = safe_int($id_alert_command, 1);
    if (empty($id_alert_command)) {
        return false;
    }

    if (empty($name)) {
        return false;
    }

    if (! is_array($values)) {
        $values = [];
    }

    $values['name'] = $name;
    $values['id_alert_command'] = (int) $id_alert_command;

    return @db_process_sql_insert('talert_actions', $values);
}


/**
 * Updates an alert action.
 *
 * @param int Id of the alert action
 * @param array Values to update.
 *
 * @return mixed Returns affected rows or false in case of fail.
 */
function alerts_update_alert_action($id_alert_action, $values)
{
    $id_alert_action = safe_int($id_alert_action, 1);
    if (empty($id_alert_action)) {
        return false;
    }

    if (! is_array($values)) {
        return false;
    }

    return (@db_process_sql_update(
        'talert_actions',
        $values,
        ['id' => $id_alert_action]
    )) !== false;
}


/**
 * Delete an alert action.
 *
 * @param int Id of the alert action
 *
 * @return mixed Returns affected rows or false in case of fail.
 */
function alerts_delete_alert_action($id_alert_action)
{
    $id_alert_action = safe_int($id_alert_action, 1);
    if (empty($id_alert_action)) {
        return false;
    }

    return (@db_process_sql_delete(
        'talert_actions',
        ['id' => $id_alert_action]
    )) !== false;
}


/**
 * Clone an alert action.
 *
 * @param int Id of the original alert action
 * @param int Agent group id if it wants to be changed when clone.
 *
 * @return mixed Id of the cloned action or false in case of fail.
 */
function alerts_clone_alert_action($id_alert_action, $id_group)
{
    $id_alert_action = safe_int($id_alert_action, 1);
    if (empty($id_alert_action)) {
        return false;
    }

    $action = alerts_get_alert_action($id_alert_action);

    if (empty($action)) {
        return false;
    }

    if ($id_group != '') {
        $action['id_group'] = $id_group;
    }

    unset($action['id']);

    return alerts_create_alert_action($action['name'].' '.__('copy'), $action['id_alert_command'], $action);
}


/**
 * Get all alert actions in Pandora DB.
 *
 * @param boolean $only_names Return only names, by default is true.
 * @param boolean $acl        Check the ACL, by default is false
 *
 * @return array The list of actions.
 */
function alerts_get_alert_actions($only_names=true, $acl=false)
{
    $groups = users_get_groups(false, 'AR', true);

    if ($groups === false) {
        $groups = [];
    }

    $id_groups = array_keys($groups);

    $all_actions = db_get_all_rows_filter('talert_actions', ['id_group' => $id_groups]);

    if ($all_actions === false) {
        return [];
    }

    if (! $only_names) {
        return $all_actions;
    }

    $actions = [];
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
function alerts_get_alert_actions_filter($only_names=true, $filter=false)
{
    if (!$filter) {
        $all_actions = db_get_all_rows_in_table('talert_actions');
    } else if (is_string($filter)) {
        $all_actions = db_get_all_rows_filter('talert_actions', $filter);
    } else {
        $all_actions = false;
    }

    if ($all_actions === false) {
        return [];
    }

    if (! $only_names) {
        return $all_actions;
    }

    $actions = [];
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
function alerts_get_alert_action($id_alert_action)
{
    $id_alert_action = safe_int($id_alert_action, 1);
    if (empty($id_alert_action)) {
        return false;
    }

    return db_get_row('talert_actions', 'id', $id_alert_action);
}


/**
 * Get Id of the alert command associated with an alert action.
 *
 * @param int Id of the action alert.
 *
 * @return mixed Id of the action alert or
 * false in case of empty result
 */
function alerts_get_alert_action_alert_command_id($id_alert_action)
{
    return db_get_value(
        'id_alert_command',
        'talert_actions',
        'id',
        $id_alert_action
    );
}


/**
 * Get alert command associated with an alert action.
 *
 * @param int Id of the action alert.
 *
 * @return mixed Result set of the action alert or
 * false in case of empty result
 */
function alerts_get_alert_action_alert_command($id_alert_action)
{
    $id_command = alerts_get_alert_action_alert_command_id($id_alert_action);

    return alerts_get_alert_command($id_command);
}


/**
 * Get field1 of an alert action.
 *
 * @param int Id of the action alert.
 *
 * @return mixed Field1 of the action alert or
 * false in case of empty result
 */
function alerts_get_alert_action_field1($id_alert_action)
{
    return db_get_value('field1', 'talert_actions', 'id', $id_alert_action);
}


/**
 * Get field2 of an alert action.
 *
 * @param int Id of the action alert.
 *
 * @return mixed Field2 of the action alert or
 * false in case of empty result
 */
function alerts_get_alert_action_field2($id_alert_action)
{
    return db_get_value('field2', 'talert_actions', 'id', $id_alert_action);
}


/**
 * Get field3 of an alert action.
 *
 * @param int Id of the action alert.
 *
 * @return mixed Field3 of the action alert or
 * false in case of empty result
 */
function alerts_get_alert_action_field3($id_alert_action)
{
    return db_get_value('field3', 'talert_actions', 'id', $id_alert_action);
}


/**
 * Get name of an alert action.
 *
 * @param int Id of the action alert.
 *
 * @return mixed Name of the action alert or
 * false in case of empty result
 */
function alerts_get_alert_action_name($id_alert_action)
{
    return db_get_value('name', 'talert_actions', 'id', $id_alert_action);
}


/**
 * Get types of alert templates.
 *
 * @return array Types of alert templates.
 */
function alerts_get_alert_templates_types()
{
    $types = [];

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
    $types['not_normal'] = __('Not normal status');

    return $types;
}


/**
 * Get type name of an alert template.
 *
 * @param string alert template type.
 *
 * @return string name of the alert template.
 */
function alerts_get_alert_templates_type_name($type)
{
    $types = alerts_get_alert_templates_types();

    if (!isset($types[$type])) {
        return __('Unknown');
    }

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
function alerts_create_alert_template($name, $type, $values=false)
{
    if (empty($name)) {
        return false;
    }

    if (empty($type)) {
        return false;
    }

    if (! is_array($values)) {
        $values = [];
    }

    $values['name'] = $name;
    $values['type'] = $type;

    switch ($type) {
        // TODO: Check values based on type, return false if failure
    }

    return @db_process_sql_insert('talert_templates', $values);
}


/**
 * Updates an alert template.
 *
 * @param int Id of the alert template.
 * @param array Array of alert template values.
 *
 * @return mixed Number of rows affected or false if something goes wrong.
 */
function alerts_update_alert_template($id_alert_template, $values)
{
    $id_alert_template = safe_int($id_alert_template, 1);

    if (empty($id_alert_template)) {
        return false;
    }

    if (! is_array($values)) {
        return false;
    }

    return (@db_process_sql_update(
        'talert_templates',
        $values,
        ['id' => $id_alert_template]
    )) !== false;
}


/**
 * Deletes an alert template.
 *
 * @param int Id of the alert template.
 *
 * @return mixed Number of rows affected or false if something goes wrong.
 */
function alerts_delete_alert_template($id_alert_template)
{
    $id_alert_template = safe_int($id_alert_template, 1);

    if (empty($id_alert_template)) {
        return false;
    }

    return @db_process_sql_delete('talert_templates', ['id' => $id_alert_template]);
}


/**
 * Get a set of alert templates.
 *
 * @param mixed Array with filter conditions or false.
 * @param mixed Array with a set of fields to retrieve or false.
 *
 * @return mixed Array with selected alert templates or false if something goes wrong.
 */
function alerts_get_alert_templates($filter=false, $fields=false, $total=false)
{
    global $config;

    if (isset($filter['offset'])) {
        $offset = $filter['offset'];
        unset($filter['offset']);
    }

    if (isset($filter['limit'])) {
        $limit = $filter['limit'];
        unset($filter['limit']);
    }

    $templates_sql = @db_get_all_rows_filter('talert_templates', $filter, $fields, 'AND', false, true);

    $limit_sql = '';
    if (isset($offset) && isset($limit) && $total === false) {
        $limit_sql = " LIMIT $offset, $limit ";
    } else {
        $limit_sql = '';
    }

    $sql = sprintf('%s %s', $templates_sql, $limit_sql);

    $alert_templates = db_get_all_rows_sql($sql);

    return $alert_templates;
}


/**
 * Get one alert template.
 *
 * @param int Id of an alert template.
 *
 * @return mixed Selected alert template or false if something goes wrong.
 */
function alerts_get_alert_template($id_alert_template)
{
    global $config;

    $alert_templates = false;
    $id_alert_template = safe_int($id_alert_template, 1);

    if (!empty($id_alert_template)) {
        switch ($config['dbtype']) {
            case 'mysql':
            case 'postgresql':
                $alert_templates = db_get_row('talert_templates', 'id', $id_alert_template);
            break;

            case 'oracle':
                $sql = "SELECT column_name
						FROM user_tab_columns
						WHERE table_name = 'TALERT_TEMPLATES'
							AND column_name NOT IN ('TIME_FROM','TIME_TO')";
                $fields_select = db_get_all_rows_sql($sql);

                $column_names = array_map(
                    function ($item) {
                        return $item['column_name'];
                    },
                    $fields_select
                );
                $column_names_str = implode(',', $column_names);

                $sql = "SELECT $column_names_str,
							to_char(time_from, 'hh24:mi:ss') AS time_from,
							to_char(time_to, 'hh24:mi:ss') AS time_to
						FROM talert_templates
						WHERE id = $id_alert_template";
                $alert_templates = db_get_row_sql($sql);
            break;
        }
    }

    return $alert_templates;
}


/**
 * Get field1 of talert_templates table.
 *
 * @param int Id of an alert template.
 *
 * @return mixed Field1 field or false if something goes wrong.
 */
function alerts_get_alert_template_field1($id_alert_template)
{
    return db_get_value('field1', 'talert_templates', 'id', $id_alert_template);
}


/**
 * Get field2 of talert_templates table.
 *
 * @param int Id of an alert template.
 *
 * @return mixed Field2 field or false if something goes wrong.
 */
function alerts_get_alert_template_field2($id_alert_template)
{
    return db_get_value('field2', 'talert_templates', 'id', $id_alert_template);
}


/**
 * Get field3 of talert_templates table.
 *
 * @param int Id of an alert template.
 *
 * @return mixed Field3 field or false if something goes wrong.
 */
function alerts_get_alert_template_field3($id_alert_template)
{
    return db_get_value('field3', 'talert_templates', 'id', $id_alert_template);
}


/**
 * Get name of talert_templates table.
 *
 * @param int Id of an alert template.
 *
 * @return mixed Name field or false if something goes wrong.
 */
function alerts_get_alert_template_name($id_alert_template)
{
    return db_get_value('name', 'talert_templates', 'id', $id_alert_template);
}


/**
 * Get description of talert_templates table.
 *
 * @param int Id of an alert template.
 *
 * @return mixed Description field or false if something goes wrong.
 */
function alerts_get_alert_template_description($id_alert_template)
{
    return db_get_value('description', 'talert_templates', 'id', $id_alert_template);
}


/**
 * Get type of talert_templates table.
 *
 * @param int Id of an alert template.
 *
 * @return mixed Type field or false if something goes wrong.
 */
function alerts_get_alert_template_type($id_alert_template)
{
    return db_get_value('type', 'talert_templates', 'id', $id_alert_template);
}


/**
 * Get type's name of alert template.
 *
 * @param int Id of an alert template.
 *
 * @return mixed Type's name of an alert template or false if something goes wrong.
 */
function alerts_get_alert_template_type_name($id_alert_template)
{
    $type = alerts_get_alert_template_type($id_alert_template);

    return alerts_get_alert_templates_type_name($type);
}


/**
 * Get value of talert_templates table.
 *
 * @param int Id of an alert template.
 *
 * @return mixed Value field or false if something goes wrong.
 */
function alerts_get_alert_template_value($id_alert_template)
{
    return db_get_value('value', 'talert_templates', 'id', $id_alert_template);
}


/**
 * Get max_value of talert_templates table.
 *
 * @param int Id of an alert template.
 *
 * @return mixed Max_value field or false if something goes wrong.
 */
function alerts_get_alert_template_max_value($id_alert_template)
{
    return db_get_value('max_value', 'talert_templates', 'id', $id_alert_template);
}


/**
 * Get min_value of talert_templates table.
 *
 * @param int Id of an alert template.
 *
 * @return mixed Min_value field or false if something goes wrong.
 */
function alerts_get_alert_template_min_value($id_alert_template)
{
    return db_get_value('min_value', 'talert_templates', 'id', $id_alert_template);
}


/**
 * Get alert_text of talert_templates table.
 *
 * @param int Id of an alert template.
 *
 * @return mixed Alert_text field or false if something goes wrong.
 */
function alerts_get_alert_template_alert_text($id_alert_template)
{
    return db_get_value('alert_text', 'talert_templates', 'id', $id_alert_template);
}


/**
 * Get time_from of talert_templates table.
 *
 * @param int Id of an alert template.
 *
 * @return mixed Time_from field or false if something goes wrong.
 */
function alerts_get_alert_template_time_from($id_alert_template)
{
    return db_get_value('time_from', 'talert_templates', 'id', $id_alert_template);
}


/**
 * Get time_to of talert_templates table.
 *
 * @param int Id of an alert template.
 *
 * @return mixed Time_to field or false if something goes wrong.
 */
function alerts_get_alert_template_time_to($id_alert_template)
{
    return db_get_value('time_to', 'talert_templates', 'id', $id_alert_template);
}


/**
 * Get recovery_notify of talert_templates table.
 *
 * @param int Id of an alert template.
 *
 * @return mixed Recovery_notify field or false if something goes wrong.
 */
function alerts_get_alert_template_recovery_notify($id_alert_template)
{
    return db_get_value('recovery_notify', 'talert_templates', 'id', $id_alert_template);
}


/**
 * Get field2_recovery of talert_templates table.
 *
 * @param int Id of an alert template.
 *
 * @return mixed Field2_recovery field or false if something goes wrong.
 */
function alerts_get_alert_template_field2_recovery($id_alert_template)
{
    return db_get_value('field2_recovery', 'talert_templates', 'id', $id_alert_template);
}


/**
 * Get field3_recovery of talert_templates table.
 *
 * @param int Id of an alert template.
 *
 * @return mixed Field3_recovery field or false if something goes wrong.
 */
function alerts_get_alert_template_field3_recovery($id_alert_template)
{
    return db_get_value('field3_recovery', 'talert_templates', 'id', $id_alert_template);
}


/**
 * Duplicates an alert template.
 *
 * @param int Id of an alert template.
 * @param int Agent group id if it wants to be changed when duplicate.
 *
 * @return mixed Duplicates an alert template or false if something goes wrong.
 */
function alerts_duplicate_alert_template($id_alert_template, $id_group)
{
    $template = alerts_get_alert_template($id_alert_template);

    if ($template === false) {
        return false;
    }

    if ($id_group != '') {
        $template['id_group'] = $id_group;
    }

    $name = io_safe_input(__('Copy of').' ').$template['name'];
    $type = $template['type'];

    $size = (count($template) / 2);
    for ($i = 0; $i < $size; $i++) {
        unset($template[$i]);
    }

    unset($template['name']);
    unset($template['id']);
    unset($template['type']);

    return alerts_create_alert_template($name, $type, $template);
}


/**
 * Creates an alert associated to a module.
 *
 * @param int Id of an alert template.
 *
 * @return mixed Alert associated to a module or false if something goes wrong.
 */
function alerts_create_alert_agent_module($id_agent_module, $id_alert_template, $values=false)
{
    if (empty($id_agent_module)) {
        return false;
    }

    if (empty($id_alert_template)) {
        return false;
    }

    if (! is_array($values)) {
        $values = [];
    }

    $values['id_agent_module'] = (int) $id_agent_module;
    $values['id_alert_template'] = (int) $id_alert_template;
    $values['last_reference'] = time();

    $exist = db_get_value_sql(
        sprintf(
            'SELECT COUNT(id)
            FROM talert_template_modules
            WHERE id_agent_module = %d
                AND id_alert_template = %d
                AND id_policy_alerts = 0
            ',
            $id_agent_module,
            $id_alert_template
        )
    );

    $result = false;
    if ((int) $exist === 0) {
        $sql = sprintf(
            'INSERT INTO talert_template_modules(%s) VALUES(%s)',
            implode(', ', array_keys($values)),
            implode(', ', array_values($values))
        );

        $result = db_process_sql($sql, 'insert_id');
    }

    return $result;
}


/**
 * Updates an alert associated to a module.
 *
 * @param int Id of an alert template.
 * @param array Values of the update.
 *
 * @return mixed Affected rows or false if something goes wrong.
 */
function alerts_update_alert_agent_module($id_alert_agent_module, $values)
{
    if (empty($id_alert_agent_module)) {
        return false;
    }

    if (! is_array($values)) {
        return false;
    }

    return (@db_process_sql_update(
        'talert_template_modules',
        $values,
        ['id' => $id_alert_agent_module]
    )) !== false;
}


/**
 * Deletes an alert associated to a module.
 *
 * @param int Id of an alert template.
 * @param mixed Array with filter conditions to delete.
 *
 * @return mixed Affected rows or false if something goes wrong.
 */
function alerts_delete_alert_agent_module($id_alert_agent_module, $filter=false)
{
    if (empty($id_alert_agent_module) && ! is_array($filter)) {
        return false;
    }

    if (! is_array($filter)) {
        $filter = [];
    }

    if ($id_alert_agent_module) {
        $filter['id'] = $id_alert_agent_module;
    }

    // Get the id agent to update the fired alert counts
    $agent_id = false;
    if (isset($filter['id_agent_module'])) {
        $agent_id = modules_get_agentmodule_agent($filter['id_agent_module']);
    } else if (isset($filter['id'])) {
        $alert = alerts_get_alert_agent_module($id_alert_agent_module);
        $agent_id = modules_get_agentmodule_agent($alert['id_agent_module']);
    }

    /*
        The deletion of actions from talert_template_module_actions,
        it is automatily because the data base this table have
        a foreing key and delete on cascade.
    */
    if (@db_process_sql_delete('talert_template_modules', $filter) !== false) {
        // Update fired alert count on the agent
        // It will only occur if is specified the alert id or the id_agent_module
        if ($agent_id !== false) {
            db_process_sql(sprintf('UPDATE tagente SET update_alert_count=1 WHERE id_agente = %d', $agent_id));
        }

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
function alerts_get_alert_agent_module($id_alert_agent_module)
{
    $id_alert_agent_module = safe_int($id_alert_agent_module, 0);

    if (empty($id_alert_agent_module)) {
        return false;
    }

    return db_get_row('talert_template_modules', 'id', $id_alert_agent_module);
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
function alerts_get_alerts_agent_module($id_agent_module, $disabled=false, $filter=false, $fields=false)
{
    $id_alert_agent_module = safe_int($id_agent_module, 0);

    if (! is_array($filter)) {
        $filter = [];
    }

    if (! $disabled) {
        $filter['disabled'] = 0;
    }

    $filter['id_agent_module'] = (int) $id_agent_module;

    return db_get_all_rows_filter(
        'talert_template_modules',
        $filter,
        $fields
    );
}


/**
 * Get alert associated to a module (only id and name fields).
 *
 * @param int Id of an alert template.
 * @param bool Disabled or not.
 *
 * @return mixed Affected rows or false if something goes wrong.
 */
function alerts_get_alerts_module_name($id_agent_module, $disabled=false)
{
    $id_alert_agent_module = safe_int($id_agent_module, 0);

    $sql = sprintf(
        'SELECT a.id, b.name 
		FROM talert_template_modules AS a, talert_templates AS b
		WHERE a.id=b.id AND a.id_agent_module = %d AND a.disabled = %d',
        $id_agent_module,
        (int) $disabled
    );

    return db_process_sql($sql);
}


/**
 * Get disabled field of talert_template_modules table.
 *
 * @param int Id of an alert associated to a module.
 *
 * @return mixed Disabled field or false if something goes wrong.
 */
function alerts_get_alert_agent_module_disabled($id_alert_agent_module)
{
    $id_alert_agent_module = safe_int($id_alert_agent_module, 0);

    return db_get_value(
        'disabled',
        'talert_template_modules',
        'id',
        $id_alert_agent_module
    );
}


/**
 * Force execution of an alert associated to a module.
 *
 * @param int Id of an alert associated to a module.
 *
 * @return mixed Affected rows or false if something goes wrong.
 */
function alerts_agent_module_force_execution($id_alert_agent_module)
{
    $id_alert_agent_module = safe_int($id_alert_agent_module, 0);

    return (@db_process_sql_update(
        'talert_template_modules',
        ['force_execution' => 1],
        ['id' => $id_alert_agent_module]
    )) !== false;
}


/**
 * Disable/Enable an alert associated to a module.
 *
 * @param int Id of an alert associated to a module.
 * @param bool Whether to enable or disable an alert.
 *
 * @return mixed Affected rows or false if something goes wrong.
 */
function alerts_agent_module_disable($id_alert_agent_module, $disabled)
{
    $id_alert_agent_module = safe_int($id_alert_agent_module, 0);

    return (@db_process_sql_update(
        'talert_template_modules',
        ['disabled' => (bool) $disabled],
        ['id' => $id_alert_agent_module]
    )) !== false;
}


/**
 * Disable/Enable stanby of an alert associated to a module.
 *
 * @param int Id of an alert associated to a module.
 * @param bool Whether to enable or disable stanby of an alert.
 *
 * @return mixed Affected rows or false if something goes wrong.
 */
function alerts_agent_module_standby($id_alert_agent_module, $standby)
{
    $id_alert_agent_module = safe_int($id_alert_agent_module, 0);

    return (@db_process_sql_update(
        'talert_template_modules',
        ['standby' => (bool) $standby],
        ['id' => $id_alert_agent_module]
    )) !== false;
}


/**
 * Get last fired of an alert associated to a module.
 *
 * @param int Id of an alert associated to a module.
 *
 * @return mixed Affected rows or false if something goes wrong.
 */
function alerts_get_alerts_agent_module_last_fired($id_alert_agent_module)
{
    $id_alert_agent_module = safe_int($id_alert_agent_module, 1);

    return db_get_value(
        'last_fired',
        'talert_template_modules',
        'id',
        $id_alert_agent_module
    );
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
function alerts_add_alert_agent_module_action($id_alert_template_module, $id_alert_action, $options=false)
{
    global $config;

    if (empty($id_alert_template_module)) {
        return false;
    }

    if (empty($id_alert_action)) {
        return false;
    }

    $values = [];
    $values['id_alert_template_module'] = (int) $id_alert_template_module;
    $values['id_alert_action'] = (int) $id_alert_action;
    $values['fires_max'] = 0;
    $values['fires_min'] = 0;
    $values['module_action_threshold'] = 0;
    if ($options) {
        $max = 0;
        $min = 0;
        if (isset($options['fires_max'])) {
            $values['fires_max'] = $options['fires_max'];
        }

        if (isset($options['fires_min'])) {
            $values['fires_min'] = $options['fires_min'];
        }

        if (isset($options['module_action_threshold'])) {
            $values['module_action_threshold'] = (int) $options['module_action_threshold'];
        }
    }

    switch ($config['dbtype']) {
        case 'mysql':
        case 'postgresql':
        return (@db_process_sql_insert(
            'talert_template_module_actions',
            $values
        )) !== false;

            break;
        case 'oracle':
        return (@db_process_sql_insert(
            'talert_template_module_actions',
            $values,
            false
        )) !== false;

            break;
    }
}


/**
 * Update an action to an alert associated to a module.
 *
 * @param int Id of register.
 * @param mixed Options of the action.
 *
 * @return mixed Affected rows or false if something goes wrong.
 */
function alerts_update_alert_agent_module_action($id_module_action, $options=false)
{
    global $config;

    $values = [];
    $values['fires_max'] = 0;
    $values['fires_min'] = 0;
    $values['module_action_threshold'] = 0;
    if ($options) {
        $max = 0;
        $min = 0;
        if (isset($options['fires_max'])) {
            $values['fires_max'] = $options['fires_max'];
        }

        if (isset($options['fires_min'])) {
            $values['fires_min'] = $options['fires_min'];
        }

        if (isset($options['module_action_threshold'])) {
            $values['module_action_threshold'] = (int) $options['module_action_threshold'];
        }

        if (isset($options['id_alert_action'])) {
            $values['id_alert_action'] = (int) $options['id_alert_action'];
        }
    }

    return (@db_process_sql_update(
        'talert_template_module_actions',
        $values,
        ['id' => $id_module_action]
    )) !== false;
}


/**
 * Delete an action to an alert associated to a module.
 *
 * @param int Id of an alert associated to a module.
 *
 * @return mixed Affected rows or false if something goes wrong.
 */
function alerts_delete_alert_agent_module_action($id_alert_agent_module_action)
{
    if (empty($id_alert_agent_module_action)) {
        return false;
    }

    return (@db_process_sql_delete(
        'talert_template_module_actions',
        ['id' => $id_alert_agent_module_action]
    )) !== false;
}


/**
 * Get actions of an alert associated to a module.
 *
 * @param int Id of an alert associated to a module.
 * @param mixed Array with fields to retrieve or false.
 *
 * @return mixed Actions associated or false if something goes wrong.
 */
function alerts_get_alert_agent_module_actions($id_alert_agent_module, $fields=false, $server_id=-1)
{
    if (empty($id_alert_agent_module)) {
        return false;
    }

    if (defined('METACONSOLE')) {
        $server = db_get_row('tmetaconsole_setup', 'id', $server_id);

        if (metaconsole_connect($server) == NOERR) {
            $actions = db_get_all_rows_filter(
                'talert_template_module_actions',
                ['id_alert_template_module' => $id_alert_agent_module],
                $fields
            );

            metaconsole_restore_db();
        }
    } else {
        $actions = db_get_all_rows_filter(
            'talert_template_module_actions',
            ['id_alert_template_module' => $id_alert_agent_module],
            $fields
        );
    }

    if ($actions === false) {
        return [];
    }

    if ($fields !== false) {
        return $actions;
    }

    $retval = [];
    foreach ($actions as $element) {
        $action = alerts_get_alert_action($element['id_alert_action']);
        $action['fires_min'] = $element['fires_min'];
        $action['fires_max'] = $element['fires_max'];
        $action['module_action_threshold'] = $element['module_action_threshold'];

        if (isset($element['id'])) {
            $retval[$element['id']] = $action;
        }
    }

    return $retval;
}


/**
 *  Returns the actions applied to an alert assigned to a module in a hash.
 *
 * @param unsigned int id_agent_module
 *
 * @return hash with the actions
 *
 *  hash[template1][action1] <- fired
 *  hash[template1][action2] <- fired
 *  hash[template1][action3] <- fired
 *  hash[template2][action1] <- fired
 */
function alerts_get_effective_alert_actions($id_agent_module)
{
    if (empty($id_agent_module)) {
        return false;
    }

    $default_sql = 'select tm.id, t.name as template, a.name as action, tm.last_fired as last_execution from talert_templates t, talert_actions a, talert_template_modules tm where tm.id_alert_template=t.id and t.id_alert_action=a.id and tm.id_agent_module='.$id_agent_module;
    $actions = db_get_all_rows_sql($default_sql);

    $custom_sql = 'select tm.id, t.name as template, a.name as action, tma.last_execution from talert_actions a, talert_template_module_actions tma, talert_template_modules tm, talert_templates t where tma.id_alert_template_module=tm.id and tma.id_alert_action=a.id and tm.id_alert_template = t.id and tm.id_agent_module='.$id_agent_module;
    $custom_actions = db_get_all_rows_sql($custom_sql);

    $no_actions_sql = 'select tm.id, t.name as template from talert_templates t, talert_template_modules tm where tm.id_alert_template=t.id and tm.id_agent_module='.$id_agent_module;
    $no_actions = db_get_all_rows_sql($no_actions_sql);

    $nactions = 0;
    $return = [];

    if ($actions !== false) {
        foreach ($actions as $a) {
            if (!isset($return[$a['template']]['id'])) {
                $return[$a['template']]['id'] = $a['id'];
            }

            if (!isset($return[$a['template']]['default'])) {
                $return[$a['template']]['default'] = [];
            }

            $return[$a['template']]['default'][$nactions]['fired'] = $a['last_execution'];
            $return[$a['template']]['default'][$nactions]['name']  = $a['action'];
            $nactions++;
        }
    }

    if ($custom_actions !== false) {
        foreach ($custom_actions as $a) {
            if (!isset($return[$a['template']]['id'])) {
                $return[$a['template']]['id'] = $a['id'];
            }

            if (!isset($return[$a['template']]['custom'])) {
                $return[$a['template']]['custom'] = [];
            }

            $return[$a['template']]['custom'][$nactions]['fired'] = $a['last_execution'];
            $return[$a['template']]['custom'][$nactions]['name']  = $a['action'];
            $nactions++;
        }
    }

    if ($no_actions !== false) {
        foreach ($no_actions as $a) {
            if (!isset($return[$a['template']]['id'])) {
                $return[$a['template']]['id'] = $a['id'];
            }

            if (!isset($return[$a['template']]['unavailable'])) {
                $return[$a['template']]['unavailable'] = [];
            }

            $return[$a['template']]['unavailable'][$nactions]['fired'] = 0;
            $return[$a['template']]['unavailable'][$nactions]['name']  = __('No actions defined');
            $nactions++;
        }
    }

    if ($nactions == 0) {
        return false;
    }

    return $return;
}


/**
 *  Validate alerts for the given module.
 *
 * @param int agent_module_id ID of the module
 */
function alerts_validate_alert_module($agent_module_id)
{
    db_process_sql(
        sprintf(
            'UPDATE talert_template_modules
		SET times_fired=0, internal_counter=0
		WHERE id_agent_module = %d',
            $agent_module_id
        )
    );
}


/**
 *  Validate alerts for the given agent.
 *
 * @param int agent_id ID of the agent
 */
function alerts_validate_alert_agent($agent_id)
{
    db_process_sql(
        sprintf(
            'UPDATE talert_template_modules tm
		INNER JOIN tagente_modulo am ON tm.id_agent_module = am.id_agente_modulo
		SET tm.times_fired=0, tm.internal_counter=0
		WHERE am.id_agente = %d',
            $agent_id
        )
    );
}


/**
 *  Validates an alert id or an array of alert id's.
 *
 * @param mixed Array of alerts ids or single id.
 * @param bool Whether to check ACLs
 *
 * @return boolean True if it was successful, false otherwise.
 */
function alerts_validate_alert_agent_module($id_alert_agent_module, $noACLs=false)
{
    global $config;

    include_once 'include/functions_events.php';

    $alerts = safe_int($id_alert_agent_module, 1);

    if (empty($alerts)) {
        return false;
    }

    $alerts = (array) $alerts;

    foreach ($alerts as $id) {
        $alert = alerts_get_alert_agent_module($id);
        $agent_id = modules_get_agentmodule_agent($alert['id_agent_module']);
        $group_id = agents_get_agent_group($agent_id);
        $critical_instructions = db_get_value('critical_instructions', 'tagente_modulo', 'id_agente_modulo', $agent_id);
        $warning_instructions = db_get_value('warning_instructions', 'tagente_modulo', 'id_agente_modulo', $agent_id);
        $unknown_instructions = db_get_value('unknown_instructions', 'tagente_modulo', 'id_agente_modulo', $agent_id);

        if (!$noACLs) {
            if (! check_acl($config['id_user'], $group_id, 'AW')) {
                continue;
            }
        }

        $result = db_process_sql_update(
            'talert_template_modules',
            [
                'times_fired'      => 0,
                'internal_counter' => 0,
            ],
            ['id' => $id]
        );

        $template_name = io_safe_output(db_get_value('name', 'talert_templates', 'id', $alert['id_alert_template']));
        $module_name = io_safe_output(db_get_value('nombre', 'tagente_modulo', 'id_agente_modulo', $alert['id_agent_module']));
        if ($result > 0) {
            // Update fired alert count on the agent
            db_process_sql(sprintf('UPDATE tagente SET update_alert_count=1 WHERE id_agente = %d', $agent_id));

            events_create_event(
                'Manual validation of alert '.$template_name.' assigned to '.$module_name.'',
                $group_id,
                $agent_id,
                1,
                $config['id_user'],
                'alert_manual_validation',
                1,
                $alert['id_agent_module'],
                $id,
                $critical_instructions,
                $warning_instructions,
                $unknown_instructions
            );
        } else if ($result === false) {
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
function alerts_copy_alert_module_to_module($id_agent_alert, $id_destiny_module)
{
    global $config;

    $alert = alerts_get_alert_agent_module($id_agent_alert);
    if ($alert === false) {
        return false;
    }

    $alerts = alerts_get_alerts_agent_module(
        $id_destiny_module,
        false,
        ['id_alert_template' => $alert['id_alert_template']]
    );
    if (! empty($alerts)) {
        return $alerts[0]['id'];
    }

    // PHP copy arrays on assignment
    $new_alert = [];
    $new_alert['id_agent_module'] = (int) $id_destiny_module;
    $new_alert['id_alert_template'] = $alert['id_alert_template'];

    switch ($config['dbtype']) {
        case 'mysql':
        case 'postgresql':
            $id_new_alert = @db_process_sql_insert(
                'talert_template_modules',
                $new_alert
            );
        break;

        case 'oracle':
            $id_new_alert = @db_process_sql_insert(
                'talert_template_modules',
                $new_alert,
                false
            );
        break;
    }

    if ($id_new_alert === false) {
        return false;
    }

    $actions = alerts_get_alert_agent_module_actions($id_agent_alert);
    if (empty($actions)) {
        return $id_new_alert;
    }

    foreach ($actions as $action) {
        $result = alerts_add_alert_agent_module_action(
            $id_new_alert,
            $action['id'],
            [
                'fires_min' => $action['fires_min'],
                'fires_max' => $action['fires_max'],
            ]
        );
        if ($result === false) {
            return false;
        }
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
function alerts_get_agents_with_alert_template($id_alert_template, $id_group, $filter=false, $fields=false, $id_agents=false)
{
    global $config;

    if (empty($id_alert_template)) {
        return false;
    }

    if (! is_array($filter)) {
        $filter = [];
    }

    $filter[] = 'tagente_modulo.id_agente_modulo = talert_template_modules.id_agent_module';
    $filter[] = 'tagente_modulo.id_agente = tagente.id_agente';
    $filter['id_alert_template'] = $id_alert_template;
    $filter['delete_pending'] = '<> 1';

    if (empty($id_agents)) {
        switch ($config['dbtype']) {
            case 'mysql':
                $filter['`tagente`.id_agente'] = array_keys(agents_get_group_agents($id_group, false, 'none'));
            break;

            case 'postgresql':
            case 'oracle':
                $filter['tagente.id_agente'] = array_keys(agents_get_group_agents($id_group, false, 'none'));
            break;
        }
    } else {
        switch ($config['dbtype']) {
            case 'mysql':
                $filter['`tagente`.id_agente'] = $id_agents;
            break;

            case 'postgresql':
            case 'oracle':
                $filter['tagente.id_agente'] = $id_agents;
            break;
        }
    }

    return db_get_all_rows_filter(
        'tagente, tagente_modulo, talert_template_modules',
        $filter,
        $fields
    );
}


/**
 * Get type name for alerts (e-mail, text, internal, ...) based on type number
 *
 * @param int id_alert Alert type id.
 *
 * @return string Type name of the alert.
 */
function get_alert_type($id_type)
{
    return (string) db_get_value('name', 'talert_templates', 'id', (int) $id_type);
}


/**
 * Get all the fired of alerts happened in an Agent during a period of time.
 *
 * The returned alerts will be in the time interval ($date - $period, $date]
 *
 * @param integer $id_agent Agent id to get events.
 * @param integer $period   Period of time in seconds to get events.
 * @param integer $date     Beginning date to get events.
 *
 * @return array An array with all the events happened.
 */
function get_agent_alert_fired($id_agent, $id_alert, $period, $date=0)
{
    if (!is_numeric($date)) {
        $date = time_w_fixed_tz($date);
    }

    if (empty($date)) {
        $date = get_system_time();
    }

    $datelimit = ($date - $period);

    $sql = sprintf(
        'SELECT timestamp
		FROM tevento
		WHERE id_agente = %d AND utimestamp > %d
			AND utimestamp <= %d
			AND id_alert_am = %d 
		ORDER BY timestamp DESC',
        $id_agent,
        $datelimit,
        $date,
        $id_alert
    );

    return db_get_all_rows_sql($sql);
}


/**
 * Get all the fired of alerts happened in an Agent module during a period of time.
 *
 * The returned alerts will be in the time interval ($date - $period, $date]
 *
 * @param integer $id_agent_module Agent module id to get events.
 * @param integer $period          Period of time in seconds to get events.
 * @param integer $date            Beginning date to get events.
 *
 * @return array An array with all the events happened.
 */
function get_module_alert_fired($id_agent_module, $id_alert)
{
    $sql = sprintf(
        'SELECT *
		FROM tevento
		WHERE id_agentmodule = %d
			AND id_alert_am = %d 
		ORDER BY timestamp DESC',
        $id_agent_module,
        $id_alert
    );

    return db_get_all_rows_sql($sql);
}


/**
 * Get all the times an alerts fired during a period.
 *
 * @param int Alert module id.
 * @param int Period timed to check from date
 * @param int Date to check (current time by default)
 *
 * @return integer The number of times an alert fired.
 */
function get_alert_fires_in_period($id_alert_module, $period, $date=0)
{
    global $config;

    if (!$date) {
        $date = get_system_time();
    }

    $datelimit = ($date - $period);

    switch ($config['dbtype']) {
        case 'mysql':
            $sql = sprintf(
                "SELECT COUNT(`id_agentmodule`)
				FROM `tevento`
				WHERE `event_type` = 'alert_fired'
					AND `id_alert_am` = %d
					AND `utimestamp` > %d 
					AND `utimestamp` <= %d",
                $id_alert_module,
                $datelimit,
                $date
            );
        break;

        case 'postgresql':
        case 'oracle':
            $sql = sprintf(
                "SELECT COUNT(id_agentmodule)
				FROM tevento
				WHERE event_type = 'alert_fired'
					AND id_alert_am = %d
					AND utimestamp > %d 
					AND utimestamp <= %d",
                $id_alert_module,
                $datelimit,
                $date
            );
        break;
    }

    return (int) db_get_sql($sql);
}


/**
 * Get all the alerts defined in a group.
 *
 * It gets all the alerts of all the agents on a given group.
 *
 * @param integer $id_group Group id to check.
 *
 * @return array An array with alerts dictionaries defined in a group.
 */
function get_group_alerts(
    $id_group,
    $filter='',
    $options=false,
    $where='',
    $allModules=false,
    $orderby=false,
    $idGroup=false,
    $count=false,
    $strict_user=false,
    $tag=false,
    $action_filter=false,
    $alert_action=true
) {
    global $config;

    $group_query = '';
    if (!empty($idGroup)) {
        $group_query = ' AND id_grupo = '.$idGroup;
    }

    if (is_array($filter)) {
        $disabled = $filter['disabled'];
        if ((isset($filter['standby']) === true) && ($filter['standby'] !== '')) {
            $filter = $group_query.' AND talert_template_modules.standby = "'.$filter['standby'].'"';
        } else {
            $filter = $group_query;
        }
    } else {
        $filter = $group_query;
        $disabled = $filter;
    }

    switch ($disabled) {
        case 'notfired':
            $filter .= ' AND times_fired = 0 AND talert_template_modules.disabled = 0';
        break;

        case 'fired':
            $filter .= ' AND times_fired > 0 AND talert_template_modules.disabled = 0';
        break;

        case 'disabled':
            $filter .= ' AND talert_template_modules.disabled = 1';
        break;

        case 'all_enabled':
            $filter .= ' AND talert_template_modules.disabled = 0';
        break;

        case 'all':
            $filter .= '';
        break;

        default:
            $filter .= ' AND talert_template_modules.disabled = 0 ';
        break;
    }

    // WHEN SELECT ALL TAGS TO FILTER ALERTS.
    $modules_tag_query = db_process_sql('select * from ttag');
    $modules_tags = ($modules_tag_query !== false) ? (count($modules_tag_query)) : false;

    $modules_user_tags = count(explode(',', $tag));

    if ($modules_tags != $modules_user_tags) {
        if ($tag) {
            $filter .= ' AND (id_agent_module IN (SELECT id_agente_modulo FROM ttag_module WHERE id_tag IN ('.$tag.')))';
        }
    }

    // WHEN SELECT ALL TAGS TO FILTER ALERTS
    if ($action_filter) {
        $filter .= ' AND (talert_template_modules.id IN (SELECT id_alert_template_module FROM talert_template_module_actions where id_alert_action = '.$action_filter.'))';
        if ($alert_action) {
            $filter .= ' OR talert_template_modules.id_alert_template IN (SELECT talert_templates.id FROM talert_templates where talert_templates.id_alert_action = '.$action_filter.')';
        }
    }

    if (is_array($options)) {
        $filter .= db_format_array_where_clause_sql($options);
    }

    if ($id_group !== false) {
        $groups = users_get_groups($config['id_user'], 'AR');

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
                } else {
                    $subQuery = 'SELECT id_agente_modulo
						FROM tagente_modulo tam
						WHERE delete_pending = 0 
                        AND tam.disabled = 0
                        AND id_agente IN (
                            SELECT ta.id_agente
                            FROM tagente ta
                            WHERE ta.disabled = 0
                            AND ta.id_grupo IN ('.implode(',', $id_group).')
                        )
                        OR tam.id_agente IN (
                            SELECT DISTINCT(tasg.id_agent)
                            FROM tagent_secondary_group tasg
                            WHERE tasg.id_group IN ('.implode(',', $id_group).')
                        )';
                }
            } else {
                $subQuery = 'SELECT id_agente_modulo
					FROM tagente_modulo
					WHERE delete_pending = 0
						AND id_agente IN (SELECT id_agente
							FROM tagente WHERE id_grupo = '.$idGroup.' AND tagente.disabled = 0)';
            }
        } else {
            // ALL GROUP
            $subQuery = 'SELECT id_agente_modulo
				FROM tagente_modulo WHERE delete_pending = 0';
        }
    } else {
        if ($allModules) {
            $disabled = '';
        } else {
            $disabled = 'WHERE disabled = 0';
        }

        $subQuery = 'SELECT id_agente_modulo
			FROM tagente_modulo '.$disabled;
    }

    $orderbyText = '';
    if ($orderby !== false) {
        if (is_array($orderby)) {
            $orderbyText = sprintf('ORDER BY %s', $orderby['field'], $orderby['order']);
        } else {
            $orderbyText = sprintf('ORDER BY %s', $orderby);
        }
    }

    $selectText = 'DISTINCT talert_template_modules.*, t2.nombre AS agent_module_name, t3.alias AS agent_name, t4.name AS template_name';
    if ($count !== false) {
        $selectText = 'COUNT(DISTINCT talert_template_modules.id) AS count';
    }

    $sql = sprintf(
        'SELECT %s
		FROM talert_template_modules
			INNER JOIN tagente_modulo t2
				ON talert_template_modules.id_agent_module = t2.id_agente_modulo
			INNER JOIN tagente t3
				ON t2.id_agente = t3.id_agente
			LEFT JOIN tagent_secondary_group tasg
				ON tasg.id_agent = t2.id_agente
			INNER JOIN talert_templates t4
				ON talert_template_modules.id_alert_template = t4.id
		WHERE id_agent_module in (%s) %s %s %s',
        $selectText,
        $subQuery,
        $where,
        $filter,
        $orderbyText
    );

    $alerts = db_get_all_rows_sql($sql);

    if ($alerts === false) {
        return [];
    }

    if ($count !== false) {
        return $alerts[0]['count'];
    } else {
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
function get_alerts_fired($alerts, $period=0, $date=0)
{
    if (! $date) {
        $date = get_system_time();
    }

    $datelimit = ($date - $period);

    $alerts_fired = [];
    $agents = [];

    foreach ($alerts as $alert) {
        if (isset($alert['id'])) {
            $fires = get_alert_fires_in_period(
                $alert['id'],
                $period,
                $date
            );
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
 * @return integer The last time an alert fired. It's an UNIX timestamp.
 */
function get_alert_last_fire_timestamp_in_period($id_alert_module, $period, $date=0)
{
    global $config;

    if ($date == 0) {
        $date = get_system_time();
    }

    $datelimit = ($date - $period);

    switch ($config['dbtype']) {
        case 'mysql':
            $sql = sprintf(
                "SELECT MAX(`utimestamp`)
				FROM `tevento`
				WHERE `event_type` = 'alert_fired'
					AND `id_alert_am` = %d
					AND `utimestamp` > %d 
					AND `utimestamp` <= %d",
                $id_alert_module,
                $datelimit,
                $date
            );
        break;

        case 'postgresql':
        case 'oracle':
            $sql = sprintf(
                "SELECT MAX(utimestamp)
				FROM tevento
				WHERE event_type = 'alert_fired'
					AND id_alert_am = %d
					AND utimestamp > %d 
					AND utimestamp <= %d",
                $id_alert_module,
                $datelimit,
                $date
            );
        break;
    }

    return db_get_sql($sql);
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
function alerts_get_action_escalation($action)
{
    $escalation = [];

    if ($action['fires_min'] == 0 && $action['fires_max'] == 0) {
        $escalation = 'everytime';
    } else if ($action['fires_min'] == $action['fires_max']) {
        for ($i = 1; $i < $action['fires_min']; $i++) {
            $escalation[$i] = 0;
        }

        $escalation[$action['fires_max']] = 1;
    } else if ($action['fires_min'] < $action['fires_max']) {
        for ($i = 1; $i <= $action['fires_max']; $i++) {
            if ($i < $action['fires_min']) {
                $escalation[$i] = 0;
            } else {
                $escalation[$i] = 1;
            }
        }
    } else if ($action['fires_min'] > $action['fires_max']) {
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
function alerts_get_actions_escalation($actions, $default_action=0)
{
    $escalation = [];
    foreach ($actions as $kaction => $action) {
        $escalation[$kaction] = alerts_get_action_escalation($action);
    }

    $default_escalation = alerts_get_default_action_escalation($default_action, $escalation);
    $escalation = ([0 => $default_escalation] + $escalation);

    $escalation = alerts_normalize_actions_escalation($escalation);

    // Join the actions with the default action
    $actions = ([0 => $default_action] + $actions);

    // Add the escalation values to the actions array
    foreach (array_keys($actions) as $kaction) {
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
function alerts_get_default_action_escalation($default_action, $escalation)
{
    if ($default_action === 0) {
        return [];
    }

    $busy_times = [];
    $busy_greater_than = -1;
    foreach ($escalation as $action_escalation) {
        if ($action_escalation == 'everytime') {
            return 'never';
        } else if (isset($action_escalation['greater_than'])) {
            if ($busy_greater_than == -1 || $action_escalation['greater_than'] < $busy_greater_than) {
                $busy_greater_than = $action_escalation['greater_than'];
            }
        } else {
            foreach ($action_escalation as $k => $v) {
                if (!isset($busy_times[$k])) {
                    $busy_times[$k] = 0;
                }

                $busy_times[$k] += $v;
            }
        }
    }

    // Set to 1 the busy executions
    // Set to 2 the min - infinite situations
    foreach ($busy_times as $k => $v) {
        if ($busy_greater_than != -1) {
            if ($k == ($busy_greater_than + 1)) {
                $busy_times[$k] = 2;
            } else if ($k > ($busy_greater_than + 1)) {
                unset($busy_times[$k]);
            }
        } else if ($v > 1) {
            $busy_times[$k] = 1;
        }
    }

    // Fill gaps from last busy to greater than
    if ($busy_greater_than != -1) {
        for ($i = (count($busy_times) + 1); $i <= $busy_greater_than; $i++) {
            $busy_times[$i] = 0;
        }

        $busy_times[$i] = 2;
    }

    // Set as default execution the not busy times
    $default_escalation = [];
    foreach ($busy_times as $k => $v) {
        switch ($v) {
            case 0:
                $default_escalation[$k] = 1;
            break;

            default:
                $default_escalation[$k] = 0;
            break;
        }

        // Last element
        if ($k == count($busy_times)) {
            switch ($v) {
                case 2:
                    if ($default_escalation[$k] == 0) {
                        unset($default_escalation[$k]);
                    }
                break;

                default:
                    $default_escalation[($k + 1)] = 1;
                break;
            }
        }
    }

    if (empty($busy_times)) {
        if ($busy_greater_than == -1) {
            $default_escalation = 'everytime';
        } else {
            for ($i = 1; $i <= $busy_greater_than; $i++) {
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
function alerts_normalize_actions_escalation($escalation)
{
    $max_elements = 0;
    $any_greater_than = false;
    foreach ($escalation as $k => $v) {
        if (is_array($v) && isset($v['greater_than'])) {
            $escalation[$k] = [];
            for ($i = 1; $i < $v['greater_than']; $i++) {
                $escalation[$k][$i] = 0;
            }

            $escalation[$k][$v['greater_than']] = 2;
            $any_greater_than = true;
        }

        if (isset($escalation[$k]) === true
            && empty($escalation[$k]) === false
            && is_array($escalation[$k]) === true
        ) {
            $n = count($escalation[$k]);
            if ($n > $max_elements) {
                $max_elements = $n;
            }
        }
    }

    if ($max_elements == 1 || !$any_greater_than) {
        $nelements = $max_elements;
    } else {
        $nelements = ($max_elements + 1);
    }

    foreach ($escalation as $k => $v) {
        if ($v == 'everytime') {
            $escalation[$k] = array_fill(1, $nelements, 1);
            $escalation[$k][$max_elements] = 2;
        } else if ($v == 'never') {
            $escalation[$k] = array_fill(1, $nelements, 0);
        } else {
            $fill_value = 0;
            for ($i = 1; $i <= $nelements; $i++) {
                if (!isset($escalation[$k][$i])) {
                    $escalation[$k][$i] = $fill_value;
                } else if ($escalation[$k][$i] == 2) {
                    $fill_value = 1;
                    $escalation[$k][$i] = 0;
                }
            }
        }
    }

    return $escalation;
}


/**
 * Check if a command can be added to an action.
 *
 * @param int Action group id
 * @param int Command group id
 *
 * @return False if command group and alert group are distint of 0 and they are not equal
 */
function alerts_validate_command_to_action($action_group, $command_group)
{
    // If action group or command group is All, all commands can be applicated.
    if ($action_group == 0 || $command_group == 0) {
        return true;
    }

    return $action_group == $command_group;
}


/**
 * Print the UI update actions
 *
 * @param bool Update or create
 */
function alerts_ui_update_or_create_actions($update=true)
{
    global $config;
    $id = (string) get_parameter('id');

    // Check ACL of existing aler action
    if ($update) {
        $al_action = alerts_get_alert_action($id);
        if ($al_action !== false) {
            if ($al_action['id_group'] == 0) {
                if (! check_acl($config['id_user'], 0, 'PM') && ! check_acl($config['id_user'], 0, 'LM')) {
                    db_pandora_audit(
                        AUDIT_LOG_ACL_VIOLATION,
                        'Trying to access Alert Management'
                    );
                    include 'general/noaccess.php';
                    exit;
                }
            }
        }
    }

    $name = (string) get_parameter('name');
    $id_alert_command = (int) get_parameter('id_command');
    $group = get_parameter('group');
    $action_threshold = (int) get_parameter('action_threshold');
    $create_wu_integria = (int) get_parameter('create_wu_integria');

    // Validate some values
    if (!$id_alert_command) {
        ui_print_error_message(__('No command specified'));
        return;
    }

    if (!$name) {
        ui_print_error_message(__('No name specified'));
        return;
    }

    $comamnd_group = db_get_value('id_group', 'talert_commands', 'id', $id_alert_command);
    if (!alerts_validate_command_to_action($group, $comamnd_group)) {
        ui_print_error_message(__('Alert and command group does not match'));
        return;
    }

    // Fill fields info
    $info_fields = '';
    $values = [];
    for ($i = 1; $i <= $config['max_macro_fields']; $i++) {
        $field_value = get_parameter('field'.$i.'_value');

        if (is_array($field_value)) {
            $field_value = reset(array_filter($field_value));

            if ($field_value === false) {
                $field_value = '';
            }
        }

        $values['field'.$i] = (string) $field_value;
        $info_fields .= ' Field'.$i.': '.$values['field'.$i];

        $field_recovery_value = get_parameter('field'.$i.'_recovery_value');

        if (is_array($field_recovery_value)) {
            $field_recovery_value = reset(array_filter($field_recovery_value));

            if ($field_recovery_value === false) {
                $field_recovery_value = '';
            }
        }

        $values['field'.$i.'_recovery'] = (string) $field_recovery_value;
        $info_fields .= ' Field'.$i.'Recovery: '.$values['field'.$i.'_recovery'];
    }

    $values['id_group'] = $group;
    $values['action_threshold'] = $action_threshold;
    $values['create_wu_integria'] = $create_wu_integria;

    // If this alert has the same name, not valid.
    $name_check = db_get_row('talert_actions', 'name', $name);
    if (empty($name_check) === false && (int) $name_check['id'] !== (int) $id) {
        $result = '';
    } else {
        if ($update) {
            $values['name'] = $name;
            $values['id_alert_command'] = $id_alert_command;
            // Only for Metaconsole, save the previous name for synchronization.
            if (is_metaconsole()) {
                $values['previous_name'] = db_get_value('name', 'talert_actions', 'id', $id);
            }

            $result = (!$name) ? '' : alerts_update_alert_action($id, $values);
        } else {
            $result = alerts_create_alert_action(
                $name,
                $id_alert_command,
                $values
            );
            $values = [
                'Name'              => $name,
                'ID alert Command'  => $id_alert_command,
                'Field information' => $info_fields,
                'Group'             => $values['id_group'],
                'Action threshold'  => $values['action_threshold'],
            ];
        }
    }

    if ($result) {
        db_pandora_audit(
            AUDIT_LOG_ALERT_MANAGEMENT,
            $update ? 'Update alert action #'.$id : 'Create alert action #'.$result,
            false,
            false,
            json_encode($values)
        );
    } else {
        db_pandora_audit(
            AUDIT_LOG_ALERT_MANAGEMENT,
            $update ? 'Fail try to update alert action #'.$id : 'Fail try to create alert action',
            false,
            false,
            $update ? json_encode($values) : ''
        );
    }

    ui_print_result_message(
        $result,
        $update ? __('Successfully updated') : __('Successfully created'),
        $update ? __('Could not be updated') : __('Could not be created')
    );
}


/**
 * Retrieve all agent_modules with configured alerts filtered by group.
 *
 * @param integer|null $id_grupo  Filter by group.
 * @param boolean      $recursion Filter by group recursive.
 *
 * @return array With agent module ids.
 */
function alerts_get_agent_modules(
    ?int $id_grupo,
    bool $recursion=false
) : array {
    if ($id_grupo === null) {
        $agent_modules = db_get_all_rows_sql(
            'SELECT distinct(atm.id_agent_module)
             FROM talert_template_modules atm
             INNER JOIN tagente_modulo am
                ON am.id_agente_modulo = atm.id_agent_module
             WHERE atm.disabled = 0'
        );
    } else if ($recursion !== true) {
        $sql = sprintf(
            'SELECT distinct(atm.id_agent_module)
                FROM talert_template_modules atm
                INNER JOIN tagente_modulo am
                ON am.id_agente_modulo = atm.id_agent_module
                INNER JOIN tagente ta
                ON am.id_agente = ta.id_agente
                LEFT JOIN tagent_secondary_group tasg
                ON tasg.id_agent = ta.id_agente
                WHERE atm.disabled = 0
                AND (tasg.id_group = %d
                OR ta.id_grupo = %d)
            ',
            $id_grupo,
            $id_grupo
        );
        $agent_modules = db_get_all_rows_sql($sql);
    } else {
        $groups = groups_get_children_ids($id_grupo, true);
        if (empty($groups) === false) {
            $sql = sprintf(
                'SELECT distinct(atm.id_agent_module)
                    FROM talert_template_modules atm
                    INNER JOIN tagente_modulo am
                    ON am.id_agente_modulo = atm.id_agent_module
                    INNER JOIN tagente ta
                    ON am.id_agente = ta.id_agente
                    LEFT JOIN tagent_secondary_group tasg
                    ON tasg.id_agent = ta.id_agente
                    WHERE atm.disabled = 0
                    AND (tasg.id_group IN (%s)
                    OR ta.id_grupo IN (%s))
                ',
                implode(',', $groups),
                implode(',', $groups)
            );
        }

        $agent_modules = db_get_all_rows_sql($sql);
    }

    if ($agent_modules === false) {
        return [];
    }

    return $agent_modules;

}


/**
 * Return the id_agent of the alert
 *
 * @param  integer $id_agent_module
 * @return integer id_agent
 */
function alerts_get_agent_by_alert($id_agent_module)
{
    $sql = sprintf(
        'SELECT id_agente FROM talert_template_modules atm INNER JOIN tagente_modulo am ON am.id_agente_modulo = atm.id_agent_module WHERE atm.id = %d
        ',
        $id_agent_module
    );
    $id_agente = db_get_row_sql($sql)['id_agente'];

    return $id_agente;
}


function alerts_get_actions_names($actions, $reduce=false)
{
    $where = '';
    if (empty($actions) === false) {
        if (is_array($actions) === true) {
            $where = sprintf(
                'WHERE id IN (%s)',
                implode(',', $actions)
            );
        } else {
            $where = sprintf('WHERE id = %d', $actions);
        }
    }

    $sql = sprintf(
        'SELECT id, `name`
        FROM talert_actions
        %s',
        $where
    );

    $result = db_get_all_rows_sql($sql);

    if ($result === false) {
        $result = [];
    }

    if ($reduce === true) {
        $result = array_reduce(
            $result,
            function ($carry, $item) {
                $carry[$item['id']] = $item['name'];
                return $carry;
            },
            []
        );
    }

    return $result;
}


/**
 * Alert fired.
 *
 * @param array $filters  Filters.
 * @param array $groupsBy Groupby and lapse.
 *
 * @return array Result data.
 */
function alerts_get_alert_fired($filters=[], $groupsBy=[])
{
    global $config;

    $table = 'tevento';

    $filter_date = '';
    if (isset($filters['period']) === true
        && empty($filters['period']) === false
    ) {
        $filter_date = sprintf(
            'AND %s.utimestamp > %d',
            $table,
            (time() - $filters['period'])
        );
    }

    $filter_group = '';
    if (isset($filters['group']) === true
        && empty($filters['group']) === false
    ) {
        $filter_group = sprintf(
            'AND %s.id_grupo = %d',
            $table,
            $filters['group']
        );
    }

    $filter_agents = '';
    if (isset($filters['agents']) === true
        && empty($filters['agents']) === false
    ) {
        if (is_metaconsole() === true) {
            $agents = array_reduce(
                $filters['agents'],
                function ($carry, $item) {
                    $explode = explode('|', $item);

                    $carry[$explode[0]][] = $explode[1];
                    return $carry;
                }
            );

            $filter_agents .= ' AND ( ';
            $i = 0;
            foreach ($agents as $tserver => $agent) {
                if ($i !== 0) {
                    $filter_agents .= ' OR ';
                }

                $filter_agents .= sprintf(
                    '( %s.id_agente IN (%s) AND %s.server_id = %d )',
                    $table,
                    implode(',', $agent),
                    $table,
                    (int) $tserver
                );

                $i++;
            }

            $filter_agents .= ' )';
        } else {
            $filter_agents = sprintf(
                'AND %s.id_agente IN (%s)',
                $table,
                implode(',', $filters['agents'])
            );
        }
    }

    $filter_modules = '';
    if (isset($filters['modules']) === true
        && empty($filters['modules']) === false
    ) {
        if (is_metaconsole() === true) {
            $modules = array_reduce(
                $filters['modules'],
                function ($carry, $item) {
                    $explode = explode('|', $item);

                    $carry[$explode[0]][] = $explode[1];
                    return $carry;
                }
            );

            $filter_modules .= ' AND ( ';
            $i = 0;
            foreach ($modules as $tserver => $module) {
                if ($i !== 0) {
                    $filter_modules .= ' OR ';
                }

                $filter_modules .= sprintf(
                    '( %s.id_agentmodule IN (%s) AND %s.server_id = %d )',
                    $table,
                    implode(',', $module),
                    $table,
                    (int) $tserver
                );

                $i++;
            }

            $filter_modules .= ' )';
        } else {
            $filter_modules = sprintf(
                'AND %s.id_agentmodule IN (%s)',
                $table,
                implode(',', $filters['modules'])
            );
        }
    }

    $filter_templates = '';
    if (isset($filters['templates']) === true
        && empty($filters['templates']) === false
    ) {
        if (is_metaconsole() === false) {
            $filter_templates = sprintf(
                'AND talert_template_modules.id_alert_template IN (%s)',
                implode(',', $filters['templates'])
            );
        }
    }

    $total = (bool) $filters['show_summary'];
    $only_data = (bool) $filters['only_data'];

    $actions_names = alerts_get_actions_names($filters['actions'], true);

    $group_array = [];

    $filter_actions = '';
    $fields_actions = [];
    if (isset($filters['actions']) === true
        && empty($filters['actions']) === false
    ) {
        $filter_actions .= 'AND ( ';
        $first = true;
        foreach ($actions_names as $name_action) {
            if ($first === false) {
                $filter_actions .= ' OR ';
            }

            $filter_actions .= sprintf(
                "JSON_CONTAINS(%s.custom_data, '\"%s\"', '\$.actions')",
                $table,
                io_safe_output($name_action)
            );

            $fields_actions[$name_action] = sprintf(
                "SUM(JSON_CONTAINS(%s.custom_data, '\"%s\"', '\$.actions')) as '%s'",
                $table,
                io_safe_output($name_action),
                io_safe_output($name_action)
            );

            $first = false;
        }

        $filter_actions .= ' ) ';
    } else {
        foreach ($actions_names as $name_action) {
            $fields[] = sprintf(
                "SUM(JSON_CONTAINS(%s.custom_data, '\"%s\"', '\$.actions')) as '%s'",
                $table,
                io_safe_output($name_action),
                io_safe_output($name_action)
            );
        }
    }

    if (is_array($fields_actions) === true
        && empty($fields_actions) === false
    ) {
        foreach ($fields_actions as $name => $field) {
            $fields[] = $field;
        }
    }

    $names_search = [];
    $names_server = [];
    if (isset($groupsBy['group_by']) === true) {
        switch ($groupsBy['group_by']) {
            case 'module':
                $fields[] = $table.'.id_agentmodule as module';
                $group_array[] = $table.'.id_agentmodule';
                $names_search = modules_get_agentmodule_name_array(
                    array_values($filters['modules'])
                );

                if (is_metaconsole() === true) {
                    $fields[] = $table.'.server_id as server';
                    $group_array[] = $table.'.server_id';
                    $names_server = metaconsole_get_names();
                }
            break;

            case 'template':
                if (is_metaconsole() === false) {
                    $fields[] = 'talert_template_modules.id_alert_template as template';
                    $group_array[] = 'talert_template_modules.id_alert_template';
                    $names_search = alerts_get_templates_name_array(
                        array_values($filters['templates'])
                    );
                }
            break;

            case 'agent':
                $fields[] = $table.'.id_agente as agent';
                $group_array[] = $table.'.id_agente';
                $names_search = agents_get_alias_array(
                    array_values($filters['agents'])
                );

                if (is_metaconsole() === true) {
                    $fields[] = $table.'.server_id as server';
                    $group_array[] = $table.'.server_id';
                    $names_server = metaconsole_get_names();
                }
            break;

            case 'group':
                $fields[] = $table.'.id_grupo as `group`';
                $group_array[] = $table.'.id_grupo';
                $names_search = users_get_groups($config['user'], 'AR', false);
            break;

            default:
                // Nothing.
            break;
        }
    }

    if (isset($groupsBy['lapse']) === true
        && empty($groupsBy['lapse']) === false
    ) {
        $fields[] = sprintf(
            '%s.utimestamp AS Period',
            $table
        );
        $group_array[] = 'period';
    }

    $group_by = '';
    if (is_array($group_array) === true && empty($group_array) === false) {
        $group_by = sprintf(' GROUP BY %s', implode(", \n", $group_array));
    }

    $innerJoin = '';
    if (is_metaconsole() === false) {
        $innerJoin = sprintf(
            'INNER JOIN talert_template_modules
                ON talert_template_modules.id = %s.id_alert_am',
            $table
        );
    }

    $query = sprintf(
        'SELECT
            %s
        FROM %s
        %s
        WHERE custom_data != ""
            AND %s.event_type="alert_fired"
            %s
            %s
            %s
            %s
            %s
            %s
            %s',
        implode(", \n", $fields),
        $table,
        $innerJoin,
        $table,
        $filter_date,
        $filter_group,
        $filter_agents,
        $filter_modules,
        $filter_actions,
        $filter_templates,
        $group_by
    );

    $data_query = db_get_all_rows_sql($query);

    if ($data_query === false) {
        $data_query = [];
    }

    if (empty($data_query) === false) {
        $data = array_reduce(
            $data_query,
            function ($carry, $item) use ($groupsBy) {
                $period = (isset($item['Period']) === true) ? (int) $item['Period'] : 0;
                if (is_metaconsole() === true
                    && ($groupsBy['group_by'] === 'agent'
                    || $groupsBy['group_by'] === 'module')
                ) {
                    $grby = $item[$groupsBy['group_by']];
                    $server = $item['server'];
                    unset($item['Period']);
                    unset($item[$groupsBy['group_by']]);
                    unset($item['server']);
                    $carry[$period][$server][$grby] = $item;
                } else {
                    $grby = $item[$groupsBy['group_by']];
                    unset($item['Period']);
                    unset($item[$groupsBy['group_by']]);
                    $carry[$period][$grby] = $item;
                }

                return $carry;
            },
            []
        );

        $intervals = [];
        if (isset($groupsBy['lapse']) === true
            && empty($groupsBy['lapse']) === false
        ) {
            $tend = time();
            $tstart = ($tend - (int) $filters['period']);
            for ($current_time = $tstart; $current_time < $tend; ($current_time += $groupsBy['lapse'])) {
                $intervals[] = (int) $current_time;
            }
        }

        $first_element = reset($data);
        $first_element = reset($first_element);
        if (is_metaconsole() === true
            && ($groupsBy['group_by'] === 'agent'
            || $groupsBy['group_by'] === 'module')
        ) {
                $first_element = reset($first_element);
        }

        $clone = [];
        foreach ($first_element as $key_clone => $value_clone) {
            $clone[$key_clone] = 0;
        }

        $result = [];
        if (empty($intervals) === true) {
            foreach ($data as $period => $array_data) {
                if (is_metaconsole() === true
                    && ($groupsBy['group_by'] === 'agent'
                    || $groupsBy['group_by'] === 'module')
                ) {
                    foreach ($names_search as $server => $names) {
                        foreach ($names as $id => $name) {
                            $name = $names_server[$server].' &raquo; '.$name;
                            if (isset($array_data[$server][$id]) === true) {
                                $result[$period][$server.'|'.$id] = $array_data[$server][$id];
                                $result[$period][$server.'|'.$id][$groupsBy['group_by']] = $name;
                            } else {
                                if ($only_data === false) {
                                    $clone[$groupsBy['group_by']] = $name;
                                    $result[$period][$server.'|'.$id] = $clone;
                                }
                            }
                        }
                    }
                } else {
                    foreach ($names_search as $id => $name) {
                        if (isset($array_data[$id]) === true) {
                            $result[$period][$id] = $array_data[$id];
                            $result[$period][$id][$groupsBy['group_by']] = $name;
                        } else {
                            if ($only_data === false) {
                                $clone[$groupsBy['group_by']] = $name;
                                $result[$period][$id] = $clone;
                            }
                        }
                    }
                }
            }
        } else {
            $period_lapse = (int) $groupsBy['lapse'];
            foreach ($intervals as $interval) {
                $start_interval = $interval;
                $end_interval = ($interval + $period_lapse);

                if ($only_data === false) {
                    if (is_metaconsole() === true
                        && ($groupsBy['group_by'] === 'agent'
                        || $groupsBy['group_by'] === 'module')
                    ) {
                        foreach ($names_search as $server => $names) {
                            foreach ($names as $id => $name) {
                                $result_name = $names_server[$server].' &raquo; '.$name;
                                $result[$start_interval][$server.'|'.$id] = $clone;
                                $result[$start_interval][$server.'|'.$id][$groupsBy['group_by']] = $result_name;
                            }
                        }
                    } else {
                        foreach ($names_search as $id => $name) {
                            $result[$start_interval][$id] = $clone;
                            $result[$start_interval][$id][$groupsBy['group_by']] = $name;
                        }
                    }
                } else {
                    foreach ($data as $period => $array_data) {
                        if (is_metaconsole() === true
                            && ($groupsBy['group_by'] === 'agent'
                            || $groupsBy['group_by'] === 'module')
                        ) {
                            foreach ($array_data as $server => $datas) {
                                foreach ($datas as $id_data => $value_data) {
                                    $name = $names_server[$server].' &raquo; '.$names_search[$server][$id_data];
                                    $result[$start_interval][$server.'|'.$id_data] = $clone;
                                    $result[$start_interval][$server.'|'.$id_data][$groupsBy['group_by']] = $name;
                                }
                            }
                        } else {
                            foreach ($array_data as $id_data => $value_data) {
                                $name = $names_search[$id_data];
                                $result[$start_interval][$id_data] = $clone;
                                $result[$start_interval][$id_data][$groupsBy['group_by']] = $name;
                            }
                        }
                    }
                }

                foreach ($data as $period => $array_data) {
                    $period_time = (int) $period;
                    if ($start_interval < $period_time && $period_time <= $end_interval) {
                        if (is_metaconsole() === true
                            && ($groupsBy['group_by'] === 'agent'
                            || $groupsBy['group_by'] === 'module')
                        ) {
                            foreach ($array_data as $server => $datas) {
                                foreach ($datas as $id_data => $value_data) {
                                    foreach ($value_data as $key_data => $v) {
                                        if ($key_data !== $groupsBy['group_by']) {
                                            if (isset($result[$start_interval][$server.'|'.$id_data][$key_data])) {
                                                $result[$start_interval][$server.'|'.$id_data][$key_data] += $v;
                                            } else {
                                                $result[$start_interval][$server.'|'.$id_data][$key_data] = $v;
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            foreach ($array_data as $id_data => $value_data) {
                                foreach ($value_data as $key_data => $v) {
                                    if ($key_data !== $groupsBy['group_by']) {
                                        if (isset($result[$start_interval][$id_data][$key_data])) {
                                            $result[$start_interval][$id_data][$key_data] += $v;
                                        } else {
                                            $result[$start_interval][$id_data][$key_data] = $v;
                                        }
                                    }
                                }
                            }
                        }

                        unset($data[$period]);
                    }
                }
            }
        }
    }

    $result['data'] = $result;

    if ($total === true) {
        $total_values = [];
        foreach ($data_query as $key => $array_data) {
            foreach ($array_data as $key_value => $v) {
                $total_values[$key_value] = ($total_values[$key_value] + $v);
            }
        }

        if (is_metaconsole() === true
            && ($groupsBy['group_by'] === 'agent'
            || $groupsBy['group_by'] === 'module')
        ) {
            unset($total_values['server']);
        }

        unset($total_values['Period']);
        $result['summary']['total'] = $total_values;
        $result['summary']['total'][$groupsBy['group_by']] = __('Total');
    }

    return $result;
}


/**
 * Get the templates names of an agent.
 *
 * @param array $array_ids Templates ids.
 *
 * @return array Id => name.
 */
function alerts_get_templates_name_array($array_ids)
{
    if (is_array($array_ids) === false || empty($array_ids) === true) {
        return [];
    }

    $sql = sprintf(
        'SELECT id, `name`
        FROM talert_templates
        WHERE id IN (%s)',
        implode(',', $array_ids)
    );

    $result = db_get_all_rows_sql($sql);

    if ($result === false) {
        $result = [];
    }

    $result = array_reduce(
        $result,
        function ($carry, $item) {
            $carry[$item['id']] = $item['name'];
            return $carry;
        },
        []
    );

    return $result;
}


/**
 * Default values events calendar templates.
 *
 * @param integer $id    ID.
 * @param string  $table Name table.
 *
 * @return array Data Events.
 */
function default_events_calendar($id, $table)
{
    $result = [
        'monday'    => [
            [
                'start' => '00:00:00',
                'end'   => '00:00:00',
            ],
        ],
        'tuesday'   => [
            [
                'start' => '00:00:00',
                'end'   => '00:00:00',
            ],
        ],
        'wednesday' => [
            [
                'start' => '00:00:00',
                'end'   => '00:00:00',
            ],
        ],
        'thursday'  => [
            [
                'start' => '00:00:00',
                'end'   => '00:00:00',
            ],
        ],
        'friday'    => [
            [
                'start' => '00:00:00',
                'end'   => '00:00:00',
            ],
        ],
        'saturday'  => [
            [
                'start' => '00:00:00',
                'end'   => '00:00:00',
            ],
        ],
        'sunday'    => [
            [
                'start' => '00:00:00',
                'end'   => '00:00:00',
            ],
        ],
    ];

    $days = [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
    ];

    // Check Exists.
    if (empty($id) === false) {
        $sql_default_alert = sprintf(
            'SELECT `id`,
                `name`,
                `time_from`,
                `time_to`,
                `monday`,
                `tuesday`,
                `wednesday`,
                `thursday`,
                `friday`,
                `saturday`,
                `sunday`,
                `schedule`
            FROM %s
            WHERE id = %d',
            $table,
            $id
        );

        $r = db_get_row_sql($sql_default_alert);
        if ($r != false) {
            // Check Exist schedule.
            if (empty($r['schedule']) === false) {
                $result = json_decode(io_safe_output($r['schedule']), true);
            } else {
                // Compatibility mode old.
                $result = [];
                foreach ($days as $day) {
                    if ((int) $r[$day] === 1) {
                        $start = $r['time_from'];
                        $to = $r['time_to'];
                        if ($r['time_from'] === $r['time_to']) {
                            $start = '00:00:00';
                            $to = '00:00:00';
                        }

                        $result[$day][0] = [
                            'start' => $start,
                            'end'   => $to,
                        ];
                    }
                }
            }
        }
    }

    return $result;
}
