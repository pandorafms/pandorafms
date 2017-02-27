<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
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
 * @subpackage Agents
 */

require_once($config['homedir'] . '/include/functions.php');
require_once($config['homedir'] . "/include/functions_modules.php");
require_once($config['homedir'] . '/include/functions_users.php');

/**
 * Check the agent exists in the DB.
 * 
 * @param int $id_agent The agent id.
 * @param boolean $show_disabled Show the agent found althought it is disabled. By default false.
 * 
 * @return boolean The result to check if the agent is in the DB.
 */
function agents_check_agent_exists($id_agent, $show_disabled = true) {
	$agent = db_get_value_filter('id_agente', 'tagente',
		array('id_agente' => $id_agent, 'disabled' => !$show_disabled));
	
	if (!empty($agent)) {
		return true;
	}
	else {
		return false;
	}
}

/**
 * Get agent id from a module id that it has.
 *
 * @param int $id_module Id module is list modules this agent.
 *
 * @return int Id from the agent of the given id module.
 */
function agents_get_agent_id_by_module_id ($id_agente_modulo) {
	return (int) db_get_value ('id_agente', 'tagente_modulo',
		'id_agente_modulo', $id_agente_modulo);
}

/**
 * Creates an agent
 *
 * @param string Agent name.
 * @param string Group to be included.
 * @param int Agent interval
 * @param string Agent IP
 *
 * @return int New agent id if created. False if it could not be created.
 */
function agents_create_agent ($name, $id_group, $interval, $ip_address, $values = false) {
	if (empty ($name))
		return false;
	
	if (empty ($id_group))
		return false;
	
	if (empty ($ip_address))
		return false;
	
	// Check interval greater than zero
	if ($interval < 0)
		$interval = false;
	if (empty ($interval))
		return false;
	
	if (! is_array ($values))
		$values = array ();
	$values['nombre'] = $name;
	$values['id_grupo'] = $id_group;
	$values['intervalo'] = $interval;
	$values['direccion'] = $ip_address;
	
	$id_agent = db_process_sql_insert ('tagente', $values);
	if ($id_agent === false) {
		return false;
	}
	
	// Create address for this agent in taddress
	agents_add_address ($id_agent, $ip_address);
	
	
	db_pandora_audit ("Agent management", "New agent '$name' created");
	
	return $id_agent;
}

/**
 * Get all the simple alerts of an agent.
 *
 * @param int Agent id
 * @param string Filter on "fired", "notfired" or "disabled". Any other value
 * will not do any filter.
 * @param array Extra filter options in an indexed array. See
 * db_format_array_where_clause_sql()
 * @param boolean $allModules
 *
 * @return array All simple alerts defined for an agent. Empty array if no
 * alerts found.
 */
function agents_get_alerts_simple ($id_agent = false, $filter = '', $options = false, $where = '', $allModules = false, $orderby = false, $idGroup = false, $count = false, $strict_user = false, $tag = false) {
	global $config;
	
	if (is_array($filter)) {
		$disabled = $filter['disabled'];
		if (isset($filter['standby'])) {
			$filter = ' AND talert_template_modules.standby = "'.$filter['standby'].'"';
		}
		else {
			$filter = '';
		}
	}
	else {
		$filter = '';
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
	
	if (isset($options['offset'])) {
		$offset = $options['offset'];
		unset($options['offset']);
	}
	
	if (isset($options['limit'])) {
		$limit = $options['limit'];
		unset($options['limit']);
	}
	
	if (is_array ($options)) {
		$filter .= db_format_array_where_clause_sql ($options);
	}
	
	if (($id_agent !== false) && ($idGroup !== false)) {
		$groups = users_get_groups($config["id_user"]);
		
		if ($idGroup != 0) { //All group
			$subQuery = 'SELECT id_agente_modulo
				FROM tagente_modulo
				WHERE delete_pending = 0 AND id_agente IN (SELECT id_agente FROM tagente WHERE id_grupo = ' . $idGroup . ')';
		}
		else {
			$subQuery = 'SELECT id_agente_modulo
				FROM tagente_modulo WHERE delete_pending = 0';
		}
		
		if ($strict_user) {
			$where_tags = tags_get_acl_tags($config['id_user'], $groups, 'AR', 'module_condition', 'AND', 'tagente_modulo'); 
			// If there are any errors add imposible condition
			if(in_array($where_tags, array(ERR_WRONG_PARAMETERS, ERR_ACL))) {
				$subQuery .= ' AND 1 = 0';
			} 
			else {
				$subQuery .= $where_tags;
			}
		}
	}
	else if ($id_agent === false || empty($id_agent)) {
		if ($allModules)
			$disabled = '';
		else
			$disabled = 'WHERE disabled = 0';
		
		$subQuery = 'SELECT id_agente_modulo
			FROM tagente_modulo ' . $disabled;
	}
	else {
		$id_agent = (array) $id_agent;
		$id_modules = array_keys (agents_get_modules ($id_agent, false, array('delete_pending' => 0)));
		
		if (empty ($id_modules))
			return array ();
		
		$subQuery = implode (",", $id_modules);
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
	
	$selectText = 'talert_template_modules.*, t2.nombre AS agent_module_name, t3.alias AS agent_name, t4.name AS template_name';
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
	
	switch ($config["dbtype"]) {
		case "mysql":
			$limit_sql = '';
			if (isset($offset) && isset($limit)) {
				$limit_sql = " LIMIT $offset, $limit "; 
			}
			$sql = sprintf("%s %s", $sql, $limit_sql);
			
			$alerts = db_get_all_rows_sql($sql);
			break;
		case "postgresql":
			$limit_sql = '';
			if (isset($offset) && isset($limit)) {
				$limit_sql = " OFFSET $offset LIMIT $limit ";
			}
			$sql = sprintf("%s %s", $sql, $limit_sql);
			
			$alerts = db_get_all_rows_sql($sql);
			
			break;
		case "oracle":
			$set = array();
			if (isset($offset) && isset($limit)) {
				$set['limit'] = $limit;
				$set['offset'] = $offset;
			}
			
			$alerts = oracle_recode_query ($sql, $set, 'AND', false);
			break;
	}
	
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
 * Get a list of agents.
 *
 * By default, it will return all the agents where the user has reading access.
 * 
 * @param array filter options in an indexed array. See
 * db_format_array_where_clause_sql()
 * @param array Fields to get.
 * @param string Access needed in the agents groups.
 * @param array $order The order of agents, by default is upward for field nombre.
 * @param bool $return Whether to return array with agents or false, or sql string statement
 * 
 * @return mixed An array with all alerts defined for an agent or false in case no allowed groups are specified.
 */
function agents_get_agents ($filter = false, $fields = false,
	$access = 'AR',
	$order = array('field' => 'nombre', 'order' => 'ASC'),
	$return = false,
	$disabled_agent = 0) {
	
	global $config;
	
	if (! is_array ($filter)) {
		$filter = array ();
	}
	
	if (isset($filter['search'])) {
		$search = $filter['search'];
		unset($filter['search']);
	}
	else {
		$search = '';
	}
	
	if (isset($filter['offset'])) {
		$offset = $filter['offset'];
		unset($filter['offset']);
	}
	
	if (isset($filter['limit'])) {
		$limit = $filter['limit'];
		unset($filter['limit']);
	}
	
	$status_sql = ' 1 = 1';
	if (isset($filter['status'])) {
		switch ($filter['status']) {
			case AGENT_STATUS_NORMAL:
				$status_sql =
					"normal_count = total_count 
						AND notinit_count <> total_count";
				break;
			case AGENT_STATUS_WARNING:
				$status_sql =
					"critical_count = 0 AND warning_count > 0";
				break;
			case AGENT_STATUS_CRITICAL:
				$status_sql =
					"critical_count > 0";
				break;
			case AGENT_STATUS_UNKNOWN:
				$status_sql =
					"critical_count = 0 AND warning_count = 0
						AND unknown_count > 0";
				break;
			case AGENT_STATUS_NOT_NORMAL:
				$status_sql =
					"(
						normal_count <> total_count
						AND
						(normal_count + notinit_count) <> total_count)";
				break;
			case AGENT_STATUS_NOT_INIT:
				$status_sql = "notinit_count = total_count";
				break;
		}
		unset($filter['status']);
	}
	
	
	unset($filter['order']);
	
	$filter_nogroup = $filter;
	
	//Get user groups
	$groups = array_keys (users_get_groups ($config["id_user"], $access, false));
	
	//If no group specified, get all user groups
	if (empty ($filter['id_grupo'])) {
		$all_groups = true;
		$filter['id_grupo'] = $groups;
	}
	elseif (! is_array ($filter['id_grupo'])) {
		$all_groups = false;
		//If group is specified but not allowed, return false
		if (! in_array ($filter['id_grupo'], $groups)) {
			return false;
		}
		$filter['id_grupo'] = (array) $filter['id_grupo']; //Make an array
	}
	else {
		$all_groups = true;
		//Check each group specified to the user groups, remove unwanted groups
		foreach ($filter['id_grupo'] as $key => $id_group) {
			if (! in_array ($id_group, $groups)) {
				unset ($filter['id_grupo'][$key]);
			}
		}
		//If no allowed groups are specified return false
		if (count ($filter['id_grupo']) == 0) {
			return false;
		}
	}
	
	if (in_array (0, $filter['id_grupo'])) {
		unset ($filter['id_grupo']);
	}
	
	if (!is_array ($fields)) {
		$fields = array ();
		$fields[0] = "id_agente";
		$fields[1] = "nombre";
	}
	
	if (isset($order['field'])) {
		if(!isset($order['order'])) {
			$order['order'] = 'ASC';
		}
		if (!isset($order['field2'])) {
			$order = 'ORDER BY '.$order['field'] . ' ' . $order['order'];
		}
		else {
			$order = 'ORDER BY '.$order['field'] . ' ' . $order['order'] . ', ' . $order['field2'];
		}
	}
	
	//Fix for postgresql
	if (empty($filter['id_agente'])) {
		unset($filter['id_agente']);
	}
	
	$where = db_format_array_where_clause_sql ($filter, 'AND', '');
	
	$where_nogroup = db_format_array_where_clause_sql(
		$filter_nogroup, 'AND', '');
	
	if ($where_nogroup == '') {
		$where_nogroup = '1 = 1';
	}

	if ($disabled_agent == 1){
		$disabled = 'disabled = 0';
	}
	else{
		$disabled = '1 = 1';	
	}
	
	$extra = false;
	
	// TODO: CLEAN extra_sql
	$sql_extra = '';
	if ($all_groups) {
		$where_nogroup = '1 = 1';
	}
	
	if ($extra) { 
		$where = sprintf('(%s OR (%s)) AND (%s) AND (%s) %s AND %s',
			$sql_extra, $where, $where_nogroup, $status_sql, $search, $disabled);	
	}
	else {
		$where = sprintf('%s AND %s AND (%s) %s AND %s',
			$where, $where_nogroup, $status_sql, $search, $disabled);
	}
	$sql = sprintf('SELECT %s
		FROM tagente
		WHERE %s %s', implode(',',$fields), $where, $order);
	
	switch ($config["dbtype"]) {
		case "mysql":
			$limit_sql = '';
			if (isset($offset) && isset($limit)) {
				$limit_sql = " LIMIT $offset, $limit "; 
			}
			$sql = sprintf("%s %s", $sql, $limit_sql);
			
			if ($return)
				return $sql;
			else
				$agents = db_get_all_rows_sql($sql);
			break;
		case "postgresql":
			$limit_sql = '';
			if (isset($offset) && isset($limit)) {
				$limit_sql = " OFFSET $offset LIMIT $limit ";
			}
			$sql = sprintf("%s %s", $sql, $limit_sql);
			
			if ($return)
				return $sql;
			else
				$agents = db_get_all_rows_sql($sql);
			
			break;
		case "oracle":
			$set = array();
			if (isset($offset) && isset($limit)) {
				$set['limit'] = $limit;
				$set['offset'] = $offset;
			}
			
			if ($return)
				return $sql;
			else
				$agents = oracle_recode_query ($sql, $set, 'AND', false);
			break;
	}
	
	return $agents;
}

/**
 * Get all the alerts of an agent, simple and combined.
 *
 * @param int $id_agent Agent id
 * @param string Special filter. Can be: "notfired", "fired" or "disabled".
 * @param array Extra filter options in an indexed array. See
 * db_format_array_where_clause_sql()
 *
 * @return array An array with all alerts defined for an agent.
 */
function agents_get_alerts ($id_agent = false, $filter = false, $options = false) {
	$simple_alerts = agents_get_alerts_simple ($id_agent, $filter, $options);
	
	return array ('simple' => $simple_alerts);
}

/**
 * Copy the agents config from one agent to the other
 *
 * @param int Agent id
 * @param mixed Agent id or id's (array) to copy to
 * @param bool Whether to copy modules as well (defaults to $_REQUEST['copy_modules'])
 * @param bool Whether to copy alerts as well
 * @param array Which modules to copy.
 * @param array Which alerts to copy. Only will be used if target_modules is empty.
 *
 * @return bool True in case of good, false in case of bad
 */
function agents_process_manage_config ($source_id_agent, $destiny_id_agents, $copy_modules = false, $copy_alerts = false, $target_modules = false, $target_alerts = false) {
	global $config;
	
	if (empty ($source_id_agent)) {
		ui_print_error_message(__('No source agent to copy'));
		return false;
	}
	
	if (empty ($destiny_id_agents)) {
		ui_print_error_message(__('No destiny agent(s) to copy'));
		return false;
	}
	
	if ($copy_modules == false) {
		$copy_modules = (bool) get_parameter ('copy_modules', $copy_modules);
	}
	
	if ($copy_alerts == false) {
		$copy_alerts = (bool) get_parameter ('copy_alerts', $copy_alerts);
	}
	
	if (! $copy_modules && ! $copy_alerts)
		return false;
	
	if (empty ($target_modules)) {
		$target_modules = (array) get_parameter ('target_modules', array ());
	}
	
	if (empty ($target_alerts)) {
		$target_alerts = (array) get_parameter ('target_alerts', array ());
	}
	
	if (empty ($target_modules)) {
		if (! $copy_alerts) {
			ui_print_error_message(__('No modules have been selected'));
			return false;
		}
		$target_modules = array ();
		
		foreach ($target_alerts as $id_alert) {
			$alert = alerts_get_alert_agent_module ($id_alert);
			if ($alert === false)
				continue;
			/* Check if some alerts which doesn't belong to the agent was given */
			if (modules_get_agentmodule_agent ($alert['id_agent_module']) != $source_id_agent)
				continue;
			array_push ($target_modules, $alert['id_agent_module']);
		}
	}
	
	switch ($config['dbtype']) {
		case "mysql":
		case "postgresql":
			db_process_sql ('SET AUTOCOMMIT = 0');
			db_process_sql ('START TRANSACTION');
			break;
		case "oracle":
			db_process_sql_begin();
			break;
	}
	$error = false;
	
	$repeated_modules = array();
	foreach ($destiny_id_agents as $id_destiny_agent) {
		foreach ($target_modules as $id_agent_module) {
			
			// Check the module name exists in target
			$module = modules_get_agentmodule ($id_agent_module);
			if ($module === false)
				return false;
			
			$modules = agents_get_modules ($id_destiny_agent, false,
				array ('nombre' => $module['nombre'], 'disabled' => false));
			
			// Keep all modules repeated
			if (! empty ($modules)) {
				$modules_repeated = array_pop (array_keys ($modules));
				$result = $modules_repeated;
				$repeated_modules[] = $modules_repeated;
			}
			else {
				
				$result = modules_copy_agent_module_to_agent ($id_agent_module,
					$id_destiny_agent);
				
				if ($result === false) {
					$error = true;
					break;
				}
			}
			
			// Check if all modules are repeated and no alerts are copied, if YES then error
			if (empty($target_alerts) and count($repeated_modules) == count($target_modules)) {
				$error = true;
				break;
			}
			
			$id_destiny_module = $result;
			
			if (! $copy_alerts)
				continue;
			
			/* If the alerts were given, copy afterwards. Otherwise, all the
			alerts for the module will be copied */
			if (! empty ($target_alerts)) {
				foreach ($target_alerts as $id_alert) {
					$alert = alerts_get_alert_agent_module ($id_alert);
					if ($alert === false)
						continue;
					if ($alert['id_agent_module'] != $id_agent_module)
						continue;
					$result = alerts_copy_alert_module_to_module ($alert['id'],
						$id_destiny_module);
					if ($result === false) {
						$error = true;
						break;
					}
				}
				continue;
			}
			
			$alerts = alerts_get_alerts_agent_module ($id_agent_module, true);
			
			if ($alerts === false)
				continue;
			
			foreach ($alerts as $alert) {
				$result = alerts_copy_alert_module_to_module ($alert['id'],
					$id_destiny_module);
				if ($result === false) {
					$error = true;
					break;
				}
			}
		}
		if ($error)
			break;
	}
	
	if ($error) {
		ui_print_error_message(
			__('There was an error copying the agent configuration, the copy has been cancelled'));
		switch ($config['dbtype']) {
			case "mysql":
			case "postgresql":
				db_process_sql ('ROLLBACK');
				break;
			case "oracle":
				db_process_sql_rollback();
				break;
		}
	}
	else {
		ui_print_success_message(__('Successfully copied'));
		switch ($config['dbtype']) {
			case "mysql":
			case "postgresql":
				db_process_sql ('COMMIT');
				break;
			case "oracle":
				db_process_sql_commit();
				break;
		}
	}
	switch ($config['dbtype']) {
		case "mysql":
		case "postgresql":
			db_process_sql ('SET AUTOCOMMIT = 1');
			break;
	}

	if ($error)
		return false;
	else
		return true;
}

function agents_get_next_contact($idAgent, $maxModules = false) {
	$agent = db_get_row_sql("SELECT *
		FROM tagente
		WHERE id_agente = " . $idAgent);
	
	
	$difference = get_system_time () - strtotime ($agent["ultimo_contacto"]);
	
	
	if ($agent["intervalo"] > 0 && strtotime($agent["ultimo_contacto"]) > 0) {
		return round ($difference / ($agent["intervalo"] / 100));
	}
	else {
		return 0;
	}
}

/**
 * Get all the modules common in various agents. If an empty list is passed it will select all
 *
 * @param mixed Agent id to get modules. It can also be an array of agent id's.
 * @param mixed Array, comma delimited list or singular value of rows to
 * select. If nothing is specified, nombre will be selected. A special
 * character "*" will select all the values.
 * @param mixed Aditional filters to the modules. It can be an indexed array
 * (keys would be the field name and value the expected value, and would be
 * joined with an AND operator) or a string, including any SQL clause (without
 * the WHERE keyword).
 * @param bool Wheter to return the modules indexed by the id_agente_modulo or
 * not. Default is indexed.
 * Example:
 <code>
 Both are similars:
 $modules = agents_get_modules ($id_agent, false, array ('disabled' => 0));
 $modules = agents_get_modules ($id_agent, false, 'disabled = 0');

 Both are similars:
 $modules = agents_get_modules ($id_agent, '*', array ('disabled' => 0, 'history_data' => 0));
 $modules = agents_get_modules ($id_agent, '*', 'disabled = 0 AND history_data = 0');
 </code>
 *
 * @return array An array with all modules in the agent.
 * If multiple rows are selected, they will be in an array
 */
function agents_common_modules ($id_agent, $filter = false, $indexed = true, $get_not_init_modules = true) {
	$id_agent = safe_int ($id_agent, 1);
	
	$where = '';
	if (! empty ($id_agent)) {
		$where = sprintf (' WHERE delete_pending = 0 AND id_agente IN (%s)
			AND (
				SELECT count(nombre)
				FROM tagente_modulo t2
				WHERE delete_pending = 0 AND t1.nombre = t2.nombre
					AND id_agente IN (%s)) = (%s)', implode (",", (array) $id_agent), implode (",", (array) $id_agent), count($id_agent));
	}
	
	if (! empty ($filter)) {
		$where .= ' AND ';
		if (is_array ($filter)) {
			$fields = array ();
			foreach ($filter as $field => $value) {
				array_push ($fields, $field.'="'.$value.'"');
			}
			$where .= implode (' AND ', $fields);
		}
		else {
			$where .= $filter;
		}
	}
	
	$sql = sprintf ('SELECT DISTINCT(t1.id_agente_modulo) as id_agente_modulo
		FROM tagente_modulo t1, talert_template_modules t2
		%s
		ORDER BY nombre',
		$where);
	$result = db_get_all_rows_sql ($sql);
	
	if (empty ($result)) {
		return array ();
	}
	
	if (! $indexed)
		return $result;
	
	$modules = array ();
	foreach ($result as $module) {
		if ($get_not_init_modules || modules_get_agentmodule_is_init($module['id_agente_modulo'])) {
			$modules[$module['id_agente_modulo']] = $module['id_agente_modulo'];
		}
	}
	return $modules;
}

/**
 * Get all the agents within a group(s).
 *
 * @param mixed $id_group Group id or an array of ID's. If nothing is selected, it will select all
 * @param mixed $search to add Default: False. If True will return disabled agents as well. If searching array (disabled => (bool), string => (string))
 * @param string $case Which case to return the agentname as (lower, upper, none)
 * @param boolean $noACL jump the ACL test.
 * @param boolean $childGroups The flag to get agents in the child group of group parent passed. By default false.
 * @param boolean $serialized Only in metaconsole. Return the key as <server id><SEPARATOR><agent id>. By default false.
 * @param string $separator Only in metaconsole. Separator for the serialized data. By default |.
 *
 * @return array An array with all agents in the group or an empty array
 */
function agents_get_group_agents ($id_group = 0, $search = false,
	$case = "lower", $noACL = false, $childGroups = false, $serialized = false, $separator = '|', $add_alert_bulk_op = false) {

	global $config;
	
	$filter = array();
	
	if (!$noACL) {
		$id_group = groups_safe_acl($config["id_user"], $id_group, "AR");
		
		if (empty ($id_group)) {
			//An empty array means the user doesn't have access
			return array ();
		}
	}
	
	if ($childGroups) {
		if (is_array($id_group)) {
			foreach ($id_group as $parent) {
				$id_group = array_merge($id_group,
					groups_get_id_recursive($parent, true));
			}
		}
		else {
			$id_group = groups_get_id_recursive($id_group, true);
		}
		
		if (!$noACL) {
			$id_group = array_keys(
				users_get_groups(false, "AR", true, false, (array)$id_group));
		}
	}
	
	if (!empty($id_group)) {
		$filter['id_grupo'] = $id_group;
	}
	
	if ($search === true) {
		//No added search. Show both disabled and non-disabled
	}
	else if (is_array ($search)) {
		$filter['disabled'] = 0;
		if (isset ($search["disabled"])) {
			$filter['disabled'] = (int) $search["disabled"];
			
			unset ($search["disabled"]);
		}
		
		if (isset ($search["string"])) {
			$string = io_safe_input ($search["string"]);
			switch ($config["dbtype"]) {
				case "mysql":
				case "postgresql":
					$filter[] = "(nombre COLLATE utf8_general_ci LIKE '%$string%' OR direccion LIKE '%$string%')";
					break;
				case "oracle":
					$filter[] = "(UPPER(nombre) LIKE UPPER('%$string%') OR direccion LIKE upper('%$string%'))";
					break;
			}
			
			unset ($search["string"]);
		}
		
		if (isset ($search["name"])) {
			$name = io_safe_input ($search["name"]);
			switch ($config["dbtype"]) {
				case "mysql":
				case "postgresql":
					$filter[] = "nombre COLLATE utf8_general_ci LIKE '$name'";
					break;
				case "oracle":
					$filter[] = "UPPER(nombre) LIKE UPPER('$name')";
					break;
			}
			
			unset ($search["name"]);
		}
		
		if (isset ($search["alias"])) {
			$name = io_safe_input ($search["alias"]);
			switch ($config["dbtype"]) {
				case "mysql":
				case "postgresql":
					$filter[] = "alias COLLATE utf8_general_ci LIKE '$name'";
					break;
				case "oracle":
					$filter[] = "UPPER(alias) LIKE UPPER('$name')";
					break;
			}
			
			unset ($search["alias"]);
		}
		
		if (isset($search['status'])) {
			switch ($search['status']) {
				case AGENT_STATUS_NORMAL:
					$filter[] = "normal_count = total_count";
					break;
				case AGENT_STATUS_WARNING:
					$filter[] = "(critical_count = 0 AND warning_count > 0)";
					break;
				case AGENT_STATUS_CRITICAL:
					$filter[] = "critical_count > 0";
					break;
				case AGENT_STATUS_UNKNOWN:
					$filter[] = "(critical_count = 0 AND warning_count = 0 AND unknown_count > 0)";
					break;
				case AGENT_STATUS_NOT_NORMAL:
					$filter[] = "normal_count <> total_count
							AND critical_count = 0 AND warning_count = 0";
					break;
				case AGENT_STATUS_NOT_INIT:
					$filter[] = "notinit_count = total_count";
					break;
			}
			unset($search['status']);
		}
		if ($add_alert_bulk_op) {
			if (isset($search['id_agente'])) {
				$filter['id_agente'] = $search['id_agente'];
			}
		}

		if (is_metaconsole() && isset($search['id_server'])) {
			$filter['id_tmetaconsole_setup'] = $search['id_server'];
			
			if ($filter['id_tmetaconsole_setup'] == 0)	{
				// All nodes
				unset ($filter['id_tmetaconsole_setup']);
			}
			
			unset ($search["id_server"]);
		}
		if (!$add_alert_bulk_op) {
			// Add the rest of the filter from the search array
			foreach ($search as $key => $value) {
				$filter[] = $value;
			}
		}
	}
	else {
		$filter['disabled'] = 0;
	}
	
	$filter['order'] = 'alias';
	
	if (is_metaconsole()) {
		$table_name = 'tmetaconsole_agent';
		
		$fields = array(
				'id_tagente AS id_agente',
				'alias',
				'id_tmetaconsole_setup AS id_server'
			);
	}
	else {
		$table_name = 'tagente';
		
		$fields = array(
				'id_agente',
				'alias'
			);
	}
	
	$result = db_get_all_rows_filter($table_name, $filter, $fields);
	
	if ($result === false)
		return array (); //Return an empty array
	
	$agents = array ();
	foreach ($result as $row) {
		if (!isset($row["id_agente"]) || !isset($row["alias"]))
			continue;
		
		if ($serialized && isset($row["id_server"])) {
			$key = $row["id_server"] . $separator . $row["id_agente"];
		}
		else {
			$key = $row["id_agente"];
		}
		
		switch ($case) {
			case "lower":
				$value = mb_strtolower ($row["alias"], "UTF-8");
				break;
			case "upper":
				$value = mb_strtoupper ($row["alias"], "UTF-8");
				break;
			default:
				$value = $row["alias"];
				break;
		}
		
		$agents[$key] = $value;
	}
	return ($agents);
}

/**
 * Get all the modules in an agent. If an empty list is passed it will select all
 *
 * @param mixed Agent id to get modules. It can also be an array of agent id's, by default is null and this mean that use the ids of agents in user's groups.
 * @param mixed Array, comma delimited list or singular value of rows to
 * select. If nothing is specified, nombre will be selected. A special
 * character "*" will select all the values.
 * @param mixed Aditional filters to the modules. It can be an indexed array
 * (keys would be the field name and value the expected value, and would be
 * joined with an AND operator) or a string, including any SQL clause (without
 * the WHERE keyword).
 * @param bool Wheter to return the modules indexed by the id_agente_modulo or
 * not. Default is indexed.
 * Example:
 <code>
 Both are similars:
 $modules = agents_get_modules ($id_agent, false, array ('disabled' => 0));
 $modules = agents_get_modules ($id_agent, false, 'disabled = 0');

 Both are similars:
 $modules = agents_get_modules ($id_agent, '*', array ('disabled' => 0, 'history_data' => 0));
 $modules = agents_get_modules ($id_agent, '*', 'disabled = 0 AND history_data = 0');
 </code>
 *
 * @return array An array with all modules in the agent.
 * If multiple rows are selected, they will be in an array
 */
function agents_get_modules ($id_agent = null, $details = false,
	$filter = false, $indexed = true, $get_not_init_modules = true,
	$noACLs = false) {
	
	global $config;
	
	$userGroups = users_get_groups($config['id_user'], 'AR', false);
	if (empty($userGroups)) {
		return array();
	}
	$id_userGroups = $id_groups = array_keys($userGroups);
	
	// =================================================================
	// When there is not a agent id. Get a agents of groups that the
	// user can read the agents.
	// =================================================================
	if ($id_agent === null) {
		
		$sql = "SELECT id_agente
			FROM tagente
			WHERE id_grupo IN (" . implode(',', $id_groups) . ")";
		$id_agent = db_get_all_rows_sql($sql);
		
		if ($id_agent == false) {
			$id_agent = array();
		}
		
		$temp = array();
		foreach ($id_agent as $item) {
			$temp[] = $item['id_agente'];
		}
		$id_agent = $temp;
	}
	
	// =================================================================
	// Fixed strange values. Only array of ids or id as int
	// =================================================================
	if (!is_array($id_agent)) {
		$id_agent = safe_int ($id_agent, 1);
	}
	
	
	$where = "(
			1 = (
				SELECT is_admin
				FROM tusuario
				WHERE id_user = '" . $config['id_user'] . "'
			)
			OR 
			tagente_modulo.id_agente IN (
				SELECT id_agente
				FROM tagente
				WHERE id_grupo IN (
						" . implode(',', $id_userGroups) . "
					)
			)
			OR 0 IN (
				SELECT id_grupo
				FROM tusuario_perfil
				WHERE id_usuario = '" . $config['id_user'] . "'
					AND id_perfil IN (
						SELECT id_perfil
						FROM tperfil WHERE agent_view = 1
					)
				)
		)";
	
	if (! empty ($id_agent)) {
		$where .= sprintf (' AND id_agente IN (%s)', implode (",", (array) $id_agent));
	}
	
	$where .= ' AND delete_pending = 0 ';
	
	if (! empty ($filter)) {
		$where .= ' AND ';
		if (is_array ($filter)) {
			$fields = array ();
			
			
			//----------------------------------------------------------
			// Code for filters as array of arrays
			//  for example:
			//    $filter =  array(
			//      'id_modulo' => 2, // networkmap type
			//      'id_tipo_modulo' => array(
			//        '<>2', // != generic_proc
			//        '<>6', // != remote_icmp_proc
			//        '<>9'));
			//----------------------------------------------------------
			$list_filter = array();
			foreach ($filter as $field => $value) {
				if (is_array($value)) {
					foreach ($value as $v) {
						$list_filter[] = array('field' => $field,
							'value' => $v);
					}
				}
				else {
					$list_filter[] = array('field' => $field,
						'value' => $value);
				}
			}
			//----------------------------------------------------------
			
			foreach ($list_filter as $item) {
				$field = $item['field'];
				$value = $item['value'];
				
				//Check <> operator
				$operatorDistin = false;
				if (strlen($value) > 2) {
					if ($value[0] . $value[1] == '<>') {
						$operatorDistin = true;
					}
				}
				
				if ($value[0] == '%') {
					switch ($config['dbtype']) {
						case "mysql":
						case "postgresql":
							array_push ($fields,
								$field . ' LIKE "' . $value . '"');
							break;
						case "oracle":
							array_push ($fields,
								$field . ' LIKE \'' . $value . '\'');
							break;
					}
				}
				else if ($operatorDistin) {
					array_push($fields, $field.' <> ' . substr($value, 2));
				}
				else if (substr($value, -1) == '%') {
					switch ($config['dbtype']) {
						case "mysql":
						case "postgresql":
							array_push ($fields, $field.' LIKE "'.$value.'"');
							break;
						case "oracle":
							array_push ($fields, $field.' LIKE \''.$value.'\'');
							break;
					}
				}
				//else if (strstr($value, '666=666', true) == '') {
				else if (strncmp($value, '666=666', 7) == 0) {
					switch ($config['dbtype']) {
						case "mysql":
						case "postgresql":
							array_push ($fields, ' '.$value);
							break;
						case "oracle":
							array_push ($fields, ' '.$value);
							break;
					}
				}
				else if (preg_match('/\bin\b/i',$field)) {
					array_push ($fields, $field.' '.$value);
				}
				else {
					switch ($config["dbtype"]) {
						case "mysql":
							array_push ($fields, $field.' = "'.$value.'"');
							break;
						case "postgresql":
							array_push ($fields, $field.' = \''.$value.'\'');
							break;
						case "oracle":
							if (is_int ($value) || is_float ($value) || is_double ($value))
								array_push ($fields, $field.' = '.$value.'');
							else
								array_push ($fields, $field.' = \''.$value.'\'');
							break;
					}
				}
			}
			$where .= implode (' AND ', $fields); 
		}
		else {
			$where .= $filter;
		}
	}
	
	if (empty ($details)) {
		$details = "nombre";
	}
	else { 
		$details = io_safe_input ($details);
	}
	
	//$where .= " AND id_policy_module = 0 ";
	
	if (tags_has_user_acl_tags($config['id_user'])){
		$where_tags = tags_get_acl_tags($config['id_user'], $id_groups, 'AR',
			'module_condition', 'AND', 'tagente_modulo', false, array(),
			true); 
		
		$where .= "\n\n" . $where_tags;
	}
	
	$sql = sprintf ('SELECT %s%s
					FROM tagente_modulo
					WHERE
						%s
					ORDER BY nombre',
					($details != '*' && $indexed) ? 'id_agente_modulo,' : '',
					io_safe_output(implode (",", (array) $details)),
					$where);
	
	$result = db_get_all_rows_sql ($sql);
	
	
	if (empty ($result)) {
		return array ();
	}
	
	if (! $indexed)
		return $result;
	
	$modules = array ();
	foreach ($result as $module) {
		if ($get_not_init_modules || modules_get_agentmodule_is_init($module['id_agente_modulo'])) {
			if (is_array ($details) || $details == '*') {
				//Just stack the information in array by ID
				$modules[$module['id_agente_modulo']] = $module;
			}
			else {
				$modules[$module['id_agente_modulo']] = $module[$details];
			}
		}
	}
	return $modules;
}

/**
 * Get agent id from a module id that it has.
 *
 * @param int $id_module Id module is list modules this agent.
 *
 * @return int Id from the agent of the given id module.
 */
function agents_get_module_id ($id_agente_modulo) {
	return (int) db_get_value ('id_agente', 'tagente_modulo', 'id_agente_modulo', $id_agente_modulo);
}

/**
 * Get agent id from an agent name.
 *
 * @param string $agent_name Agent name to get its id.
 * @param boolean $io_safe_input If it is true transform to safe string, by default false.
 *
 * @return int Id from the agent of the given name.
 */
function agents_get_agent_id ($agent_name, $io_safe_input = false) {
	if ($io_safe_input) {
		$agent_name = io_safe_input($agent_name);
	}
	return (int) db_get_value ('id_agente', 'tagente', 'nombre', $agent_name);
}

/**
 * Get name of an agent.
 *
 * @param int $id_agent Agent id.
 * @param string $case Case (upper, lower, none)
 *
 * @return string Name of the given agent.
 */
function agents_get_name ($id_agent, $case = "none") {
	$agent = (string) db_get_value ('nombre',
		'tagente', 'id_agente', (int) $id_agent);
	
	// Version 3.0 has enforced case sensitive agent names
	// so we always should show real case names.
	switch ($case) {
		case "upper":
			return mb_strtoupper ($agent,"UTF-8");
			break;
		case "lower":
			return mb_strtolower ($agent,"UTF-8");
			break;
		case "none":
		default:
			return ($agent);
			break;
	}
}

/**
 * Get alias of an agent.
 *
 * @param int $id_agent Agent id.
 * @param string $case Case (upper, lower, none)
 *
 * @return string Alias of the given agent.
 */
function agents_get_alias ($id_agent, $case = 'none') {
	$alias = (string) db_get_value ('alias', 'tagente', 'id_agente', (int) $id_agent);
	
	switch ($case) {
		case 'upper':
			return mb_strtoupper($alias, 'UTF-8');
		case 'lower':
			return mb_strtolower($alias, 'UTF-8');
		case 'none':
		default:
			return ($alias);
	}
}

/**
 * Get the number of pandora data packets in the database.
 *
 * In case an array is passed, it will have a value for every agent passed
 * incl. a total otherwise it will just return the total
 *
 * @param mixed Agent id or array of agent id's, 0 for all
 *
 * @return mixed The number of data in the database
 */
function agents_get_modules_data_count ($id_agent = 0) {
	$id_agent = safe_int ($id_agent, 1);
	
	if (empty ($id_agent)) {
		$id_agent = array ();
	}
	else {
		$id_agent = (array) $id_agent;
	}
	
	$count = array ();
	$count["total"] = 0;
	
	$query[0] = "SELECT COUNT(*) FROM tagente_datos";
	
	foreach ($id_agent as $agent_id) {
		//Init value
		$count[$agent_id] = 0;
		$modules = array_keys (agents_get_modules ($agent_id)); 
		foreach ($query as $sql) { 
			//Add up each table's data
			//Avoid the count over empty array
			if (!empty($modules))
				$count[$agent_id] += (int) db_get_sql ($sql .
					" WHERE id_agente_modulo IN (".implode (",", $modules).")", 0, true);
		}
		//Add total agent count to total count
		$count["total"] += $count[$agent_id];
	}
	
	if ($count["total"] == 0) {
		foreach ($query as $sql) {
			$count["total"] += (int) db_get_sql ($sql, 0, true);
		}
	}
	
	return $count; //Return the array
}

/**
 * Check if an agent has alerts fired.
 *
 * @param int Agent id.
 *
 * @return bool True if the agent has fired alerts.
 */
function agents_check_alert_fired ($id_agent) {
	$sql = sprintf ("SELECT COUNT(*)
		FROM talert_template_modules, tagente_modulo
		WHERE talert_template_modules.id_agent_module = tagente_modulo.id_agente_modulo
			AND times_fired > 0 AND id_agente = %d",
	$id_agent);
	
	$value = db_get_sql ($sql);
	if ($value > 0)
		return true;
	
	return false;
}

/**
 * Get the interval of an agent.
 *
 * @param int Agent id.
 *
 * @return int The interval value of a given agent
 */
function agents_get_interval ($id_agent) {
	return (int) db_get_value ('intervalo', 'tagente', 'id_agente', $id_agent);
}

/**
 * Get all data of agent.
 *
 * @param Agent object.
 *
 * @return The interval value and status of last contact
 */
function agents_get_interval_status ($agent) {
	
	$return = '';
	$last_time = strtotime ($agent["ultimo_contacto"]);
	$now = time ();
	$diferencia = $now - $last_time;
	$time = ui_print_timestamp ($last_time, true, array('style' => 'font-size:6.5pt'));
	$min_interval = modules_get_agentmodule_mininterval_no_async($agent['id_agente']);
	$return = $time;
	if ($diferencia > ($min_interval["min_interval"] * 2))
		$return = '<b><span style="color: #ff0000;">'.$time.'</span></b>';
	
	return $return;
}

/**
 * Get the operating system of an agent.
 *
 * @param int Agent id.
 *
 * @return int The interval value of a given agent
 */
function agents_get_os ($id_agent) {
	return (int) db_get_value ('id_os', 'tagente', 'id_agente', $id_agent);
}

/**
 * Get the flag value of an agent module.
 *
 * @param int Agent module id.
 *
 * @return bool The flag value of an agent module.
 */
function agents_give_agentmodule_flag ($id_agent_module) {
	return db_get_value ('flag', 'tagente_modulo', 'id_agente_modulo', $id_agent_module);
}

/**
 * Assign an IP address to an agent.
 *
 * @param int Agent id
 * @param string IP address to assign
 */
function agents_add_address ($id_agent, $ip_address) {
	global $config;
	
	// Check if already is attached to agent
	switch ($config["dbtype"]) {
		case "mysql":
			$sql = sprintf ("SELECT COUNT(`ip`)
				FROM taddress_agent, taddress
				WHERE taddress_agent.id_a = taddress.id_a
					AND ip = '%s' AND id_agent = %d",$ip_address,$id_agent);
			break;
		case "postgresql":
		case "oracle":
			$sql = sprintf ("SELECT COUNT(ip)
				FROM taddress_agent, taddress
				WHERE taddress_agent.id_a = taddress.id_a
					AND ip = '%s' AND id_agent = %d", $ip_address, $id_agent);
			break;
	}
	$current_address = db_get_sql ($sql);
	if ($current_address > 0)
		return;
	
	// Look for a record with this IP Address
	$id_address = (int) db_get_value ('id_a', 'taddress', 'ip', $ip_address);
	
	if ($id_address === 0) {
		// Create IP address in tadress table
		$id_address = db_process_sql_insert('taddress', array('ip' => $ip_address));
	}
	
	// Add address to agent
	$values = array('id_a' => $id_address, 'id_agent' => $id_agent);
	db_process_sql_insert('taddress_agent', $values);
}

/**
 * Unassign an IP address from an agent.
 *
 * @param int Agent id
 * @param string IP address to unassign
 */
function agents_delete_address ($id_agent, $ip_address) {
	global $config;
	
	$sql = sprintf ("SELECT id_ag
		FROM taddress_agent, taddress
		WHERE taddress_agent.id_a = taddress.id_a
			AND ip = '%s'
			AND id_agent = %d", $ip_address, $id_agent);
	$id_ag = db_get_sql ($sql);
	if ($id_ag !== false) {
		db_process_sql_delete('taddress_agent', array('id_ag' => $id_ag));
	}
	
	$agent_name = agents_get_name($id_agent, "");
	db_pandora_audit("Agent management",
		"Deleted IP $ip_address from agent '$agent_name'");
	
	// Need to change main address?
	if (agents_get_address($id_agent) == $ip_address) {
		$new_ips = agents_get_addresses ($id_agent);
		if (empty($new_ips)) {
			$new_ip = '';
		}
		else {
			$new_ip = reset($new_ips);
		}
		
		// Change main address in agent to first one in the list
		
		db_process_sql_update('tagente',
			array('direccion' => $new_ip),
			array('id_agente' => $id_agent));
		
		return $new_ip;
	}
}

/**
 * Get address of an agent.
 *
 * @param int Agent id
 *
 * @return string The address of the given agent
 */
function agents_get_address ($id_agent) {
	return (string) db_get_value ('direccion', 'tagente', 'id_agente', (int) $id_agent);
}

/**
 * Get description of an agent.
 *
 * @param int Agent id
 *
 * @return string The address of the given agent
 */
function agents_get_description ($id_agent) {
	return (string) db_get_value ('comentarios', 'tagente', 'id_agente', (int) $id_agent);
}

/**
 * Get the agent that matches an IP address
 *
 * @param string IP address to get the agents.
 *
 * @return mixed The agent that has the IP address given. False if none were found.
 */
function agents_get_agent_with_ip ($ip_address) {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			$sql = sprintf ('SELECT tagente.*
				FROM tagente, taddress, taddress_agent
				WHERE tagente.id_agente = taddress_agent.id_agent
					AND taddress_agent.id_a = taddress.id_a
					AND ip = "%s"', $ip_address);
			break;
		case "postgresql":
		case "oracle":
			$sql = sprintf ('SELECT tagente.*
				FROM tagente, taddress, taddress_agent
				WHERE tagente.id_agente = taddress_agent.id_agent
					AND taddress_agent.id_a = taddress.id_a
					AND ip = \'%s\'', $ip_address);
			break;
	}
	
	return db_get_row_sql ($sql);
}

/**
 * Get all IP addresses of an agent
 *
 * @param int Agent id
 *
 * @return array Array with the IP address of the given agent or an empty array.
 */
function agents_get_addresses ($id_agent) {
	if (is_array($id_agent)) {
		$sql = sprintf ("SELECT ip
			FROM taddress_agent, taddress
			WHERE taddress_agent.id_a = taddress.id_a
				AND id_agent IN (%s)", implode(",", $id_agent));
	}
	else {
		$sql = sprintf ("SELECT ip
			FROM taddress_agent, taddress
			WHERE taddress_agent.id_a = taddress.id_a
				AND id_agent = %d", $id_agent);
	}
	
	$ips = db_get_all_rows_sql ($sql);
	
	if ($ips === false) {
		$ips = array ();
	}
	
	$ret_arr = array ();
	foreach ($ips as $row) {
		$ret_arr[$row["ip"]] = $row["ip"];
	}
	
	return $ret_arr;
}

/**
 * Get the worst status of all modules of a given agent from the counts.
 *
 * @param array agent to check.
 *
 * @return int Worst status of an agent for all of its modules.
 * return -1 if the data are wrong
 */
function agents_get_status_from_counts($agent) {
	
	
	// Check if in the data there are all the necessary values
	if (!isset($agent['normal_count']) && 
		!isset($agent['warning_count']) &&
		!isset($agent['critical_count']) &&
		!isset($agent['unknown_count']) &&
		!isset($agent['notinit_count']) &&
		!isset($agent['total_count'])) {
		return -1;
	}
	
	# Juanma (05/05/2014) Fix:  This status is not init! 0 modules or all not init
	if ($agent['notinit_count'] == $agent['total_count']) {
		return AGENT_MODULE_STATUS_NOT_INIT;
	}
	if ($agent['critical_count'] > 0) {
		return AGENT_MODULE_STATUS_CRITICAL_BAD;
	}
	else if ($agent['warning_count'] > 0) {
		return AGENT_MODULE_STATUS_WARNING;
	}
	else if ($agent['unknown_count'] > 0) {
		return AGENT_MODULE_STATUS_UNKNOWN;
	}
	else if ($agent['normal_count'] == $agent['total_count']) {
		return AGENT_MODULE_STATUS_NORMAL;
	}
	else if ($agent['normal_count'] + $agent['notinit_count'] == $agent['total_count']) {
		return AGENT_MODULE_STATUS_NORMAL;
	}
	//~ else if($agent['notinit_count'] == $agent['total_count']) {
		//~ return AGENT_MODULE_STATUS_NORMAL;
	//~ }
	
	return -1;
}

/**
 * Get the worst status of all modules of a given agent.
 *
 * @param int Id agent to check.
 * @param bool Whether the call check ACLs or not 
 *
 * @return int Worst status of an agent for all of its modules.
 * The value -1 is returned in case the agent has exceed its interval.
 */
function agents_get_status($id_agent = 0, $noACLs = false) {
	global $config;
	
	if (!$noACLs) {
		$modules = agents_get_modules ($id_agent, 'id_agente_modulo',
			array('disabled' => 0), true, false);
	}
	else {
		$filter_modules['id_agente'] = $id_agent;
		$filter_modules['disabled'] = 0;
		$filter_modules['delete_pending'] = 0;
		// Get all non disabled modules of the agent
		$all_modules = db_get_all_rows_filter('tagente_modulo',
			$filter_modules, 'id_agente_modulo'); 
		
		$result_modules = array();
		// Skip non init modules
		foreach ($all_modules as $module) {
			if (modules_get_agentmodule_is_init($module['id_agente_modulo'])) {
				$modules[] = $module['id_agente_modulo'];
			}
		}
	}
	
	$modules_status = array();
	$modules_async = 0;
	foreach ($modules as $module) {
		$modules_status[] = modules_get_agentmodule_status($module);
		
		$module_type = modules_get_agentmodule_type($module);
		if (($module_type >= 21 && $module_type <= 23) ||
			$module_type == 100) {
			$modules_async++;
		}
	}
	
	// If all the modules are asynchronous or keep alive, the group cannot be unknown
	if ($modules_async < count($modules)) {
		$time = get_system_time ();
		
		switch ($config["dbtype"]) {
			case "mysql":
				$status = db_get_value_filter ('COUNT(*)',
					'tagente',
					array ('id_agente' => (int) $id_agent,
						'UNIX_TIMESTAMP(ultimo_contacto) + intervalo * 2 > '.$time));
				break;
			case "postgresql":
				$status = db_get_value_filter ('COUNT(*)',
					'tagente',
					array ('id_agente' => (int) $id_agent,
						'ceil(date_part(\'epoch\', ultimo_contacto)) + intervalo * 2 > '.$time));
				break;
			case "oracle":
				$status = db_get_value_filter ('count(*)',
					'tagente',
					array ('id_agente' => (int) $id_agent,
						'ceil((to_date(ultimo_contacto, \'YYYY-MM-DD HH24:MI:SS\') - to_date(\'19700101000000\',\'YYYYMMDDHH24MISS\')) * (' . SECONDS_1DAY . ')) > ' . $time));
				break;
		}
		
		if (! $status)
			return AGENT_MODULE_STATUS_UNKNOWN;
	}
	
	// Checking if any module has alert fired
	if (is_int(array_search(AGENT_MODULE_STATUS_CRITICAL_ALERT, $modules_status))) {
		return AGENT_MODULE_STATUS_CRITICAL_ALERT;
	}
	// Checking if any module has alert fired
	elseif (is_int(array_search(AGENT_MODULE_STATUS_WARNING_ALERT, $modules_status))) {
		return AGENT_MODULE_STATUS_WARNING_ALERT;
	}
	// Checking if any module has critical status
	elseif (is_int(array_search(AGENT_MODULE_STATUS_CRITICAL_BAD, $modules_status))) {
		return AGENT_MODULE_STATUS_CRITICAL_BAD;
	}
	// Checking if any module has critical status
	elseif (is_int(array_search(AGENT_MODULE_STATUS_NORMAL_ALERT, $modules_status))) {
		return AGENT_STATUS_ALERT_FIRED;
	}
	// Checking if any module has warning status
	elseif (is_int(array_search(AGENT_MODULE_STATUS_WARNING,$modules_status))) {
		return AGENT_MODULE_STATUS_WARNING;
	}
	// Checking if any module has unknown status
	elseif (is_int(array_search(AGENT_MODULE_STATUS_UNKNOWN, $modules_status))) {
		return AGENT_MODULE_STATUS_UNKNOWN;
	}
	else {
		return AGENT_MODULE_STATUS_NORMAL;
	}
}

/**
 * Delete an agent from the database.
 *
 * @param mixed An array of agents ids or a single integer id to be erased
 * @param bool Disable the ACL checking, for default false.
 *
 * @return bool False if error, true if success.
 */
function agents_delete_agent ($id_agents, $disableACL = false) {
	global $config;
	
	$error = false;
	
	//Convert single values to an array
	if (! is_array ($id_agents))
		$id_agents = (array) $id_agents;
	
	foreach ($id_agents as $id_agent) {
		$id_agent = (int) $id_agent; //Cast as integer
		if ($id_agent < 1)
		continue;
		
		$agent_name = agents_get_name($id_agent, "");
		
		/* Check for deletion permissions */
		$id_group = agents_get_agent_group ($id_agent);
		if ((! check_acl ($config['id_user'], $id_group, "AW")) && !$disableACL) {
			return false;
		}
		
		//A variable where we store that long subquery thing for
		//modules
		$where_modules = "ANY(SELECT id_agente_modulo FROM tagente_modulo WHERE id_agente = ".$id_agent.")";
		
		//IP address
		$sql = sprintf ("SELECT id_ag
			FROM taddress_agent, taddress
			WHERE taddress_agent.id_a = taddress.id_a
				AND id_agent = %d",
		$id_agent);
		$addresses = db_get_all_rows_sql ($sql);
		
		if ($addresses === false) {
			$addresses = array ();
		}
		foreach ($addresses as $address) {
			db_process_delete_temp ("taddress_agent",
				"id_ag", $address["id_ag"]);
		}
		
		// We cannot delete tagente_datos and tagente_datos_string here
		// because it's a huge ammount of time. tagente_module has a special
		// field to mark for delete each module of agent deleted and in
		// daily maintance process, all data for that modules are deleted
		
		//Alert
		db_process_delete_temp ("talert_template_modules",
			"id_agent_module", $where_modules, true);
		
		//Events (up/down monitors)
		// Dont delete here, could be very time-exausting, let the daily script
		// delete them after XXX days
		// db_process_delete_temp ("tevento", "id_agente", $id_agent);
		
		//Graphs, layouts, reports & networkmapenterprise
		db_process_delete_temp ("tgraph_source",
			"id_agent_module", $where_modules, true);
		db_process_delete_temp ("tlayout_data",
			"id_agente_modulo", $where_modules, true);
		db_process_delete_temp ("treport_content",
			"id_agent_module", $where_modules, true);
		if (enterprise_installed()) {
			$nodes = db_get_all_rows_filter(
				"titem",
				array("source_data" => $id_agent, "type" => 0));
			if (empty($nodes)) {
				$nodes = array();
			}
			
			foreach ($nodes as $node) {
				db_process_delete_temp ("tnetworkmap_ent_rel_nodes",
					"parent", $node['id']);
				db_process_delete_temp ("tnetworkmap_ent_rel_nodes",
					"child", $node['id']);
			}
			
			db_process_delete_temp ("titem",
				"source_data", $id_agent);
		}
		
		//Planned Downtime
		db_process_delete_temp ("tplanned_downtime_agents", "id_agent",
			$id_agent);
		
		//The status of the module
		db_process_delete_temp ("tagente_estado", "id_agente", $id_agent);
		
		//The actual modules, don't put anything based on
		// DONT Delete this, just mark for deletion
		// db_process_delete_temp ("tagente_modulo", "id_agente", $id_agent);
		
		db_process_sql_update ('tagente_modulo',
			array ('delete_pending' => 1, 'disabled' => 1, 'nombre' => 'pendingdelete'),
			'id_agente = '. $id_agent);
		
		// Access entries
		// Dont delete here, this records are deleted in daily script
		// db_process_delete_temp ("tagent_access", "id_agent", $id_agent);
		
		// Delete agent policies
		enterprise_include_once('include/functions_policies.php');
		enterprise_hook('policies_delete_agent', array($id_agent));
		
		// Delete agent in networkmap enterprise
		if (enterprise_installed()) {
			enterprise_include_once("include/functions_pandora_networkmap.php");
			networkmap_delete_nodes_by_agent(array($id_agent));
		}
		
		// tagente_datos_inc
		// Dont delete here, this records are deleted later, in database script
		// db_process_delete_temp ("tagente_datos_inc", "id_agente_modulo", $where_modules, true);
		
		// Delete remote configuration
		if (enterprise_installed()) {
			if (isset ($config["remote_config"])) {
				enterprise_include_once('include/functions_config_agents.php');
				if (enterprise_hook('config_agents_has_remote_configuration', array($id_agent))) {
					$agent_name = agents_get_name($id_agent);
					$agent_name = io_safe_output($agent_name);
					$agent_md5 = md5 ($agent_name, false);
					
					// Agent remote configuration editor
					$file_name = $config["remote_config"] . "/conf/" . $agent_md5 . ".conf";
					
					$error = !@unlink ($file_name);
					
					if (!$error) {
						$file_name = $config["remote_config"] . "/md5/" . $agent_md5 . ".md5";
						$error = !@unlink ($file_name);
					}
					
					if ($error) {
						db_pandora_audit( "Agent management",
							"Error: Deleted agent '$agent_name', the error is in the delete conf or md5.");
					}
				}
			}
		}
		
		//And at long last, the agent
		db_process_delete_temp ("tagente", "id_agente", $id_agent);
		
		db_process_sql ("delete from ttag_module where id_agente_modulo in (select id_agente_modulo from tagente_modulo where id_agente = ".$id_agent.")");
		
		db_pandora_audit( "Agent management",
			"Deleted agent '$agent_name'");
		
		// Delete the agent from the metaconsole cache
		enterprise_include_once('include/functions_agents.php');
		enterprise_hook('agent_delete_from_cache', array($id_agent));

		/* Break the loop on error */
		if ($error)
			break;
	}
	
	
	
	if ($error) {
		return false;
	}
	else {
		return true;
	}
}

/**
 * This function gets the agent group for a given agent module
 *
 * @param int The agent module id
 *
 * @return int The group id
 */
function agents_get_agentmodule_group ($id_module) {
	$agent = (int) modules_get_agentmodule_agent ((int) $id_module);
	return (int) agents_get_agent_group ($agent);
}

/**
 * This function gets the group for a given agent
 *
 * @param int The agent id
 *
 * @return int The group id
 */
function agents_get_agent_group ($id_agent) {
	return (int) db_get_value ('id_grupo', 'tagente', 'id_agente', (int) $id_agent);
}

/**
 * This function gets the count of incidents attached to the agent
 *
 * @param int The agent id
 *
 * @return mixed The incidents attached or false
 */
function agents_get_count_incidents ($id_agent) {
	if (empty($id_agent)) {
		return false;
	}
	
	return db_get_value('count(*)', 'tincidencia', 'id_agent',
		$id_agent);
}

/**
 * Get critical monitors by using the status code in modules.
 *
 * @param int The agent id
 * @param string Additional filters
 *
 * @return mixed The incidents attached or false
 */
function agents_monitor_critical ($id_agent, $filter="") {
	
	if ($filter) {
		$filter = " AND ".$filter;
	}
	
	return db_get_sql ("SELECT critical_count
		FROM tagente
		WHERE id_agente = $id_agent" . $filter);
}

// Get warning monitors by using the status code in modules.

function agents_monitor_warning ($id_agent, $filter="") {
	
	if ($filter) {
		$filter = " AND ".$filter;
	}
	
	return db_get_sql ("SELECT warning_count
		FROM tagente
		WHERE id_agente = $id_agent" . $filter);
}

// Get unknown monitors by using the status code in modules.

function agents_monitor_unknown ($id_agent, $filter="") {
	
	if ($filter) {
		$filter = " AND ".$filter;
	}
	
	return db_get_sql ("SELECT unknown_count
		FROM tagente
		WHERE id_agente = $id_agent" . $filter);
}

// Get ok monitors by using the status code in modules.
function agents_monitor_ok ($id_agent, $filter="") {
	
	if ($filter) {
		$filter = " AND ".$filter;
	}
	
	return db_get_sql ("SELECT normal_count
		FROM tagente
		WHERE id_agente = $id_agent" . $filter);
}

/**
 * Get all monitors disabled of an specific agent.
 *
 * @param int The agent id
 * @param string Additional filters
 *
 * @return mixed Total module count or false
 */
function agents_monitor_disabled ($id_agent, $filter="") {
	
	if ($filter) {
		$filter = " AND ".$filter;
	}
	
	return db_get_sql("
		SELECT COUNT( DISTINCT tagente_modulo.id_agente_modulo)
		FROM tagente, tagente_modulo
		WHERE tagente_modulo.id_agente = tagente.id_agente
			AND tagente_modulo.disabled = 1
			AND tagente.id_agente = $id_agent".$filter);
}

/**
 * Get all monitors notinit of an specific agent.
 *
 * @param int The agent id
 * @param string Additional filters
 *
 * @return mixed Total module count or false
 */
function agents_monitor_notinit ($id_agent, $filter="") {
	
	if (!empty($filter)) {
		$filter = " AND ".$filter;
	}
	
	return db_get_sql ("SELECT notinit_count
		FROM tagente
		WHERE id_agente = $id_agent" . $filter);
}

/**
 * Get all monitors of an specific agent.
 *
 * @param int The agent id
 * @param string Additional filters
 * @param bool Whether to retrieve disabled modules or not
 *
 * @return mixed Total module count or false
 */
function agents_monitor_total ($id_agent, $filter = '', $disabled = false) {
	
	if ($filter) {
		$filter = " AND ".$filter;
	}
	
	$sql = "SELECT COUNT( DISTINCT tagente_modulo.id_agente_modulo) 
		FROM tagente_estado, tagente, tagente_modulo 
		WHERE tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo 
			AND tagente_estado.id_agente = tagente.id_agente 
			AND tagente.id_agente = $id_agent".$filter;
	
	if (!$disabled)
		$sql .= " AND tagente.disabled = 0 AND tagente_modulo.disabled = 0";
	
	return db_get_sql ($sql);
}

//Get alert fired for this agent

function agents_get_alerts_fired ($id_agent, $filter="") {
	
	$modules_agent = agents_get_modules($id_agent, "id_agente_modulo", $filter);
	
	if (empty($modules_agent)) {
		return 0;
	}
	
	$mod_clause = "(".implode(",", $modules_agent).")";
	
	return db_get_sql ("SELECT COUNT(times_fired)
		FROM talert_template_modules
		WHERE times_fired != 0 AND id_agent_module IN ".$mod_clause);
}

//Returns the alert image to display tree view

function agents_tree_view_alert_img ($alert_fired) {
	
	if ($alert_fired) {
		return ui_print_status_image (STATUS_ALERT_FIRED, __('Alert fired'), true);
	}
	else {
		return ui_print_status_image (STATUS_ALERT_NOT_FIRED, __('Alert not fired'), true);
	}
}

//Returns the alert ball image to display tree view

function agents_tree_view_alert_img_ball ($alert_fired) {
	
	if ($alert_fired) {
		return ui_print_status_image (STATUS_ALERT_FIRED_BALL, __('Alert fired'), true);
	}
	else {
		return ui_print_status_image (STATUS_ALERT_NOT_FIRED_BALL, __('Alert not fired'), true);
	}
}

//Returns the status image to display tree view

function agents_tree_view_status_img ($critical, $warning, $unknown, $total, $notinit) {
	if ($total == 0 || $total == $notinit) {
		return ui_print_status_image (STATUS_AGENT_NO_MONITORS,
			__('No Monitors'), true);
	}
	if ($critical > 0) {
		return ui_print_status_image (STATUS_AGENT_CRITICAL,
			__('At least one module in CRITICAL status'), true);
	}
	else if ($warning > 0) {
		return ui_print_status_image (STATUS_AGENT_WARNING,
			__('At least one module in WARNING status'), true);
	}
	else if ($unknown > 0) {
		return ui_print_status_image (STATUS_AGENT_DOWN,
			__('At least one module is in UKNOWN status'), true);
	}
	else {
		return ui_print_status_image (STATUS_AGENT_OK,
			__('All Monitors OK'), true);
	}
}

//Returns the status ball image to display tree view

function agents_tree_view_status_img_ball ($critical, $warning, $unknown, $total, $notinit) {
	if ($total == 0 || $total == $notinit) {
		return ui_print_status_image (STATUS_AGENT_NO_MONITORS_BALL,
			__('No Monitors'), true);
	}
	if ($critical > 0) {
		return ui_print_status_image (STATUS_AGENT_CRITICAL_BALL,
			__('At least one module in CRITICAL status'), true);
	}
	else if ($warning > 0) {
		return ui_print_status_image (STATUS_AGENT_WARNING_BALL,
			__('At least one module in WARNING status'), true);
	}
	else if ($unknown > 0) {
		return ui_print_status_image (STATUS_AGENT_DOWN_BALL,
			__('At least one module is in UKNOWN status'), true);
	}
	else {
		return ui_print_status_image (STATUS_AGENT_OK_BALL,
			__('All Monitors OK'), true);
	}
}

//Returns the status image to display agent detail view

function agents_detail_view_status_img ($critical, $warning, $unknown, $total, $notinit) {
	if ($total == 0 || $total == $notinit) {
		return ui_print_status_image (STATUS_AGENT_NOT_INIT,
			__('No Monitors'), true, false, 'images');
	}
	else if ($critical > 0) {
		return ui_print_status_image (STATUS_AGENT_CRITICAL,
			__('At least one module in CRITICAL status'), true, false, 'images');
	}
	else if ($warning > 0) {
		return ui_print_status_image (STATUS_AGENT_WARNING,
			__('At least one module in WARNING status'), true, false, 'images');
	}
	else if ($unknown > 0) {
		return ui_print_status_image (STATUS_AGENT_UNKNOWN,
			__('At least one module is in UKNOWN status'), true, false, 'images');
	}
	else {
		return ui_print_status_image (STATUS_AGENT_OK,
			__('All Monitors OK'), true, false, 'images');
	}
}

function agents_update_gis($idAgente, $latitude, $longitude, $altitude,
	$ignore_new_gis_data, $manual_placement, $start_timestamp,
	$end_timestamp, $number_of_packages, $description_save_history,
	$description_update_gis, $description_first_insert) {
	
	$previusAgentGISData = db_get_row_sql("
		SELECT *
		FROM tgis_data_status
		WHERE tagente_id_agente = " . $idAgente);
	
	db_process_sql_update('tagente',
		array('update_gis_data' => $updateGisData),
		array('id_agente' => $idAgente));
	
	$return = false;
	
	if ($previusAgentGISData !== false) {
		$return = db_process_sql_insert('tgis_data_history', array(
			"longitude" => $previusAgentGISData['stored_longitude'],
			"latitude" => $previusAgentGISData['stored_latitude'],
			"altitude" => $previusAgentGISData['stored_altitude'],
			"start_timestamp" => $previusAgentGISData['start_timestamp'],
			"end_timestamp" => $end_timestamp,
			"description" => $description_save_history,
			"manual_placement" => $previusAgentGISData['manual_placement'],
			"number_of_packages" => $previusAgentGISData['number_of_packages'],
			"tagente_id_agente" => $previusAgentGISData['tagente_id_agente']
		));
		$return = db_process_sql_update('tgis_data_status', array(
				"tagente_id_agente" => $idAgente,
				"current_longitude" => $longitude,
				"current_latitude" => $latitude,
				"current_altitude" => $altitude,
				"stored_longitude" => $longitude,
				"stored_latitude" => $latitude,
				"stored_altitude" => $altitude,
				"start_timestamp" => $start_timestamp,
				"manual_placement" => $manual_placement,
				"description" => $description_update_gis,
				"number_of_packages" => $number_of_packages),
			array("tagente_id_agente" => $idAgente));
	}
	else {
		//The table "tgis_data_status" have not a autonumeric
		//then the mysql_insert_id function return 0
		
		$prev_count = db_get_num_rows("SELECT * FROM tgis_data_status");
		
		$return = db_process_sql_insert('tgis_data_status', array(
			"tagente_id_agente" => $idAgente,
			"current_longitude" => $longitude,
			"current_latitude" => $latitude,
			"current_altitude" => $altitude,
			"stored_longitude" => $longitude,
			"stored_latitude" => $latitude,
			"stored_altitude" => $altitude,
			"start_timestamp" => $start_timestamp,
			"manual_placement" => $manual_placement,
			"description" => $description_first_insert,
			"number_of_packages" => $number_of_packages
		));
		
		
		$count = db_get_num_rows("SELECT * FROM tgis_data_status");
		
		if ($return === 0) {
			if ($prev_count < $count) {
				$return = true;
			}
		}
	}
	
	return (bool)$return;
}

/**
 * Returns a list with network interfaces data by agent
 *
 * @param array Agents with the columns 'id_agente', 'nombre' and 'id_grupo'.
 * @param mixed A filter to search the agents if the first parameter is false.
 *
 * @return array A list of network interfaces information by agents.
 */
function agents_get_network_interfaces ($agents = false, $agents_filter = false) {
	global $config;

	if ($agents === false) {
		$filter = false;
		if ($agents_filter !== false) {
			$filter = $agents_filter;
		}
		$fields = array(
				'id_agente',
				'alias',
				'id_grupo'
			);
		$agents = agents_get_agents($filter, $fields);
	}
	
	$ni_by_agents = array();

	foreach ($agents as $agent) {
		$agent_id = $agent['id_agente'];
		$agent_group_id = $agent['id_grupo'];
		$agent_name = $agent['alias'];
		$agent_interfaces = array();
		
		$accepted_module_types = array();
		$remote_snmp_proc = (int) db_get_value("id_tipo", "ttipo_modulo", "nombre", "remote_snmp_proc");
		if ($remote_snmp_proc)
			$accepted_module_types[] = $remote_snmp_proc;
		$remote_icmp_proc = (int) db_get_value("id_tipo", "ttipo_modulo", "nombre", "remote_icmp_proc");
		if ($remote_icmp_proc)
			$accepted_module_types[] = $remote_icmp_proc;
		$remote_tcp_proc = (int) db_get_value("id_tipo", "ttipo_modulo", "nombre", "remote_tcp_proc");
		if ($remote_tcp_proc)
			$accepted_module_types[] = $remote_tcp_proc;
		$generic_proc = (int) db_get_value("id_tipo", "ttipo_modulo", "nombre", "generic_proc");
		if ($generic_proc)
			$accepted_module_types[] = $generic_proc;
		
		if (empty($accepted_module_types))
			$accepted_module_types[] = 0; // No modules will be returned
		
		$columns = array(
				"id_agente_modulo",
				"nombre",
				"ip_target"
			);

		if ($config['dbtype'] == 'oracle')
			$columns[] = 'TO_CHAR(descripcion) AS descripcion';
		else
			$columns[] = 'descripcion';

		$filter = " id_agente = $agent_id AND disabled = 0 AND id_tipo_modulo IN (".implode(",", $accepted_module_types).") AND nombre LIKE '%_ifOperStatus'";
		
		$modules = agents_get_modules($agent_id, $columns, $filter, true, false);
		
		if (!empty($modules)) {
			
			$interfaces = array();

			foreach ($modules as $module) {
				$module_name = (string) $module['nombre'];

				// Trying to get the interface name from the module name
				
				//if (preg_match ("/_(.+)$/", $module_name, $matches)) {
				if (preg_match ("/^(.+)_/", $module_name, $matches)) {
					
					if ($matches[1]) {
						$interface_name = $matches[1];
						$interface_name_escaped = str_replace("/", "\/", $interface_name);
						
						//if (preg_match ("/^$interface_name_escaped_ifOperStatus$/i", $module_name, $matches)) {
						if (preg_match ("/^".$interface_name_escaped."_ifOperStatus$/i", $module_name, $matches)) {
							$interfaces[$interface_name] = $module;
						}

					}
				}
			}
			unset($modules);
			foreach ($interfaces as $interface_name => $module) {
				$interface_name_escaped = str_replace("/", "\/", $interface_name);
				
				$module_id = $module['id_agente_modulo'];
				$module_name = $module['nombre'];
				$module_description = $module['descripcion'];
				$db_status = modules_get_agentmodule_status($module_id);
				$module_value = modules_get_last_value ($module_id);
				modules_get_status($module_id, $db_status, $module_value, $status, $title);
				$status_image = ui_print_status_image($status, $title, true);
				
				$ip_target = "--";
				// Trying to get something like an IP from the description
				if (preg_match ("/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/", $module_description, $matches)
						|| preg_match ("/(((?=(?>.*?(::))(?!.+\3)))\3?|([\dA-F]{1,4}(\3|:?)|\2))(?4){5}((?4){2}|(25[0-5]|
							(2[0-4]|1\d|[1-9])?\d)(\.(?7)){3})/i", $module_description, $matches) && $matches[0]) {
					
					$ip_target = $matches[0];
				}
				// else if (isset($module['ip_target']) && !empty($module['ip_target'])) {
				// 	$ip_target = $module['ip_target'];
				// }
				$mac = "--";
				// Trying to get something like a mac from the description
				if (preg_match ("/([0-9a-f]{1,2}[\.:-]){5}([0-9a-f]{1,2})/i", $module_description, $matches)) {
					if ($matches[0]) {
						$mac = $matches[0];
					}
				}

				// Get the ifInOctets and ifOutOctets modules of the interface
				$columns = array(
						"id_agente_modulo",
						"nombre"
					);
				$interface_traffic_modules = agents_get_modules($agent_id, $columns, "nombre LIKE 'if%Octets_$interface_name'");
				if (!empty($interface_traffic_modules) && count($interface_traffic_modules) >= 2) {
					$interface_traffic_modules_aux = array('in' => '', 'out' => '');
					foreach ($interface_traffic_modules as $interface_traffic_module) {
						$interface_name_escaped = str_replace("/", "\/", $interface_name);
						if (preg_match ("/^if(.+)Octets_$interface_name_escaped$/i", $interface_traffic_module['nombre'], $matches)) {
							if (strtolower($matches[1]) == 'in') {
								$interface_traffic_modules_aux['in'] = $interface_traffic_module['id_agente_modulo'];
							}
							elseif (strtolower($matches[1]) == 'out') {
								$interface_traffic_modules_aux['out'] = $interface_traffic_module['id_agente_modulo'];
							}
						}
					}
					if (!empty($interface_traffic_modules_aux['in']) && !empty($interface_traffic_modules_aux['out'])) {
						$interface_traffic_modules = $interface_traffic_modules_aux;
					}
					else {
						$interface_traffic_modules = false;
					}
				}
				else {
					$interface_traffic_modules = false;
				}

				$agent_interfaces[$interface_name] = array();
				$agent_interfaces[$interface_name]['status_image'] = $status_image;
				$agent_interfaces[$interface_name]['status_module_id'] = $module_id;
				$agent_interfaces[$interface_name]['status_module_name'] = $module_name;
				$agent_interfaces[$interface_name]['ip'] = $ip_target;
				$agent_interfaces[$interface_name]['mac'] = $mac;

				if ($interface_traffic_modules !== false) {
					$agent_interfaces[$interface_name]['traffic'] = array();
					$agent_interfaces[$interface_name]['traffic']['in'] = $interface_traffic_modules['in'];
					$agent_interfaces[$interface_name]['traffic']['out'] = $interface_traffic_modules['out'];
				}
			}
		}

		if (!empty($agent_interfaces)) {
			$ni_by_agents[$agent_id] = array();
			$ni_by_agents[$agent_id]['name'] = $agent_name;
			$ni_by_agents[$agent_id]['group'] = $agent_group_id;
			$ni_by_agents[$agent_id]['interfaces'] = $agent_interfaces;
		}
	}

	return $ni_by_agents;
}

/**
 * Returns the value of the custom field for the selected agent.
 *
 * @param integer Agent id.
 * @param string Name of the custom field.
 *
 * @return mixed The custom field value or false on error.
 */
function agents_get_agent_custom_field ($agent_id, $custom_field_name) {
	if (empty($agent_id) && empty($custom_field_name)) {
		return false;
	}
	
	$sql = sprintf("SELECT tacd.description AS value
					FROM tagent_custom_data tacd
					INNER JOIN tagent_custom_fields tacf
						ON tacd.id_field = tacf.id_field
							AND tacf.name LIKE '%s'
					WHERE tacd.id_agent = %d",
					$custom_field_name, $agent_id);
	return db_get_value_sql($sql);
}

function select_modules_for_agent_group($id_group, $id_agents, $selection, $return=true){
	$agents = implode(",", $id_agents);

	$filter_group = "";
	$filter_agent = "";

	if ($id_group != 0) {
		$filter_group = " AND id_module_group = ". $id_group;
	}
	if ($agents != null) {
		$filter_agent = " AND id_agente IN (" . $agents . ")";
	}

	if ($selection == 1 || (count($id_agents) == 1)) {
		$modules = db_get_all_rows_sql("SELECT DISTINCT nombre, id_agente_modulo FROM tagente_modulo WHERE 1 = 1" . $filter_agent . $filter_group);

		if (empty($modules)) $modules = array();

		$found = array();
		foreach ($modules as $i=>$row) {
		    $check = $row['nombre'];
		    if (@$found[$check]++) {
		        unset($modules[$i]);
		    }
		}
	}
	else {
		$modules = db_get_all_rows_sql("SELECT nombre, id_agente_modulo FROM tagente_modulo WHERE 1 = 1" . $filter_agent . $filter_group);

		if (empty($modules)) $modules = array();

		foreach ($modules as $m) {
			$is_in_all_agents = true;
			$module_name = modules_get_agentmodule_name($m['id_agente_modulo']);
			foreach ($id_agents as $a) {
				$module_in_agent = db_get_value_filter('id_agente_modulo',
					'tagente_modulo', array('id_agente' => $a, 'nombre' => $module_name));
				if (!$module_in_agent) {
					$is_in_all_agents = false;
				}
			}
			if ($is_in_all_agents) {
				$modules_to_report[] = $m;
			}
		}
		$modules = $modules_to_report;

		$found = array();
		if (is_array($modules) || is_object($modules)){
			foreach ($modules as $i=>$row) {
			    $check = $row['nombre'];
			    if (@$found[$check]++) {
			        unset($modules[$i]);
			    }
			}
		}
	}
	if (is_array($modules) || is_object($modules)){
		foreach ($modules as $k => $v) {
			$modules[$k] = io_safe_output($v);
		}
	}
	
	if($return == false){
		foreach ($modules as $value) {
			$modules_array[$value['id_agente_modulo']] = $value['nombre'];
		}
		return $modules_array;
	}
	else{
		echo json_encode($modules);
		return;
	}
}

?>
