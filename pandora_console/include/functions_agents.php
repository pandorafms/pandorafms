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

require_once($config['homedir'] . "/include/functions_modules.php");
require_once($config['homedir'] . '/include/functions_users.php');

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
	$interval = safe_int ($interval,1, 300);
	if (empty ($interval))
		return false;
	if (! is_array ($values))
		$values = array ();
	$values['nombre'] = $name;
	$values['id_grupo'] = $id_group;
	$values['intervalo'] = $interval;
	$values['direccion'] = $ip_address;
	
	db_process_sql_begin ();
	
	$id_agent = db_process_sql_insert ('tagente', $values);
	if ($id_agent === false) {
		db_process_sql_rollback ();
		return false;
	}
	
	// Create address for this agent in taddress
	agents_add_address ($id_agent, $ip_address);
	
	// Create special module agent_keepalive
	$id_agent_module = db_process_sql_insert ('tagente_modulo', 
		array ('nombre' => 'agent_keepalive',
			'id_agente' => $id_agent,
			'id_tipo_modulo' => 100,
			'descripcion' => __('Agent keepalive monitor'),
			'id_modulo' => 1,
			'min_warning' => 0,
			'max_warning' => 1));
	
	if ($id_agent_module === false) {
		db_process_sql_rollback ();
		return false;
	}
	
	$result = db_process_sql_insert ('tagente_estado', 
			array ('id_agente_modulo' => $id_agent_module,
				'datos' => '',
				'timestamp' => 0,
				'estado' => 0,
				'id_agente' => $id_agent,
				'last_try' => 0,
				'utimestamp' => 0,
				'current_interval' => 0,
				'running_by' => 0,
				'last_execution_try' => 0));
	
	if ($result === false) {
		db_process_sql_rollback ();
		return false;
	}
	
	db_process_sql_commit ();
	
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
function agents_get_alerts_simple ($id_agent = false, $filter = '', $options = false, $where = '', 
	$allModules = false, $orderby = false, $idGroup = false, $count = false) {
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
	}

	if (is_array ($options)) {
		$filter .= db_format_array_where_clause_sql ($options);
	}
	if (($id_agent !== false) && ($idGroup !== false)) {
		if ($idGroup != 0) { //All group
			$subQuery = 'SELECT id_agente_modulo
				FROM tagente_modulo
				WHERE delete_pending = 0 AND id_agente IN (SELECT id_agente FROM tagente WHERE id_grupo = ' . $idGroup . ')';
		}
		else {
			$subQuery = 'SELECT id_agente_modulo
				FROM tagente_modulo WHERE delete_pending = 0';
		}
	}
	else if ($id_agent === false) {
		if ($allModules) $disabled = '';
		else $disabled = 'WHERE disabled = 0';
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
	
	$selectText = 'talert_template_modules.*, t2.nombre AS agent_module_name, t3.nombre AS agent_name, t4.name AS template_name';
	if ($count !== false) {
		$selectText = 'COUNT(talert_template_modules.id) AS count';
	}

	if(is_array($id_agent)) {
		$extra_sql = enterprise_hook('policies_get_modules_sql_condition', array(reset($id_agent), 't3.', false));
	}
	else {
		$extra_sql = '';
	}
	
	if ($extra_sql === ENTERPRISE_NOT_HOOK) {
		$extra_sql = '';
	}else if ($extra_sql != '') {
		$extra_sql .= ' OR ';
	}
	
	$sql = sprintf ("SELECT %s
	FROM talert_template_modules
		INNER JOIN tagente_modulo t2
			ON talert_template_modules.id_agent_module = t2.id_agente_modulo
		INNER JOIN tagente t3
			ON t2.id_agente = t3.id_agente
		INNER JOIN talert_templates t4
			ON talert_template_modules.id_alert_template = t4.id
	WHERE (%s id_agent_module in (%s)) %s %s %s",
	$selectText, $extra_sql, $subQuery, $where, $filter, $orderbyText);
	
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
 * Get all the combined alerts of an agent.
 *
 * @param int $id_agent Agent id
 * @param string Special filter. Can be: "notfired", "fired" or "disabled".
 * @param array Extra filter options in an indexed array. See
 * db_format_array_where_clause_sql()
 *
 * @return array An array with all combined alerts defined for an agent.
 */
function agents_get_alerts_compound ($id_agent = false, $filter = '', $options = false, $idGroup = false, $count = false, $where = '') {
	switch ($filter) {
		case "notfired":
			$filter = ' AND times_fired = 0 AND disabled = 0';
			break;
		case "fired":
			$filter = ' AND times_fired > 0 AND disabled = 0';
			break;
		case "disabled":
			$filter = ' AND disabled = 1';
			break;
		case 'all_enabled':
			$filter = ' AND disabled = 0';
			break;
		default:
			$filter = '';
			break;
	}
	
	if (is_array ($options)) {
		$filter .= db_format_array_where_clause_sql ($options);
	}
	
	if (($id_agent !== false) && ($idGroup !== false)) {
		if ($idGroup != 0) { //All group
			$subQuery = 'SELECT id_agente FROM tagente WHERE id_grupo = ' . $idGroup;
		}
		else {
			$subQuery = 'SELECT id_agente FROM tagente';
		}
	}
	else if ($id_agent == false) {
		$subQuery = 'SELECT id_agente
			FROM tagente WHERE disabled = 0';
	}
	else {
		$id_agent = (array) $id_agent;
		
		$subQuery = implode (',', $id_agent);
	}
	
	$selectText = '*';
	if ($count !== false) {
		$selectText = 'COUNT(id) AS count';
	}
	
	$sql = sprintf ("SELECT %s FROM talert_compound
		WHERE id_agent IN (%s) %s %s",
		$selectText, $subQuery, $where, $filter);
	
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
 * Get a list of agents.
 *
 * By default, it will return all the agents where the user has reading access.
 * 
 * @param array filter options in an indexed array. See
 * db_format_array_where_clause_sql()
 * @param array Fields to get.
 * @param string Access needed in the agents groups.
 * @param array $order The order of agents, by default is upward for field nombre.
 * 
 * @return mixed An array with all alerts defined for an agent or false in case no allowed groups are specified.
 */
function agents_get_agents ($filter = false, $fields = false, $access = 'AR', $order = array('field' => 'nombre', 'order' => 'ASC')) {
    global $config;
	
	if (! is_array ($filter)) {
		$filter = array ();
	}

	if(isset($filter['search'])) {
		$search = $filter['search'];
		unset($filter['search']);
	}
	else{
		$search = '';
	}
	
	if(isset($filter['offset'])) {
		$offset = $filter['offset'];
		unset($filter['offset']);
	}
	
	if(isset($filter['limit'])) {
		$limit = $filter['limit'];
		unset($filter['limit']);
	}
	
	$status_sql = ' 1 = 1';
	if(isset($filter['status'])) {
		$normal_modules = 'SELECT tagente.id_agente FROM tagente_estado, tagente, tagente_modulo 
				WHERE tagente.disabled = 0 AND tagente_estado.id_agente = tagente.id_agente 
				AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo 
				AND tagente_modulo.disabled = 0 AND estado = 0 
				AND (utimestamp != 0 OR tagente_modulo.id_tipo_modulo IN (21,22,23)) 
				AND (utimestamp >= ( UNIX_TIMESTAMP() - (current_interval * 2)) 
				OR tagente_modulo.id_tipo_modulo IN (21,22,23,100))';
				
		$warning_modules = 'SELECT tagente.id_agente FROM tagente_estado, tagente, tagente_modulo 
				WHERE tagente.disabled = 0 AND tagente_estado.id_agente = tagente.id_agente 
				AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo 
				AND tagente_modulo.disabled = 0 AND estado = 2 
				AND (utimestamp >= ( UNIX_TIMESTAMP() - (current_interval * 2)) 
				OR tagente_modulo.id_tipo_modulo IN (21,22,23,100))';
				
		$critical_modules = 'SELECT tagente.id_agente FROM tagente_estado, tagente, tagente_modulo 
				WHERE tagente.disabled = 0 AND tagente_estado.id_agente = tagente.id_agente 
				AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo 
				AND tagente_modulo.disabled = 0 AND estado = 1 
				AND (utimestamp >= ( UNIX_TIMESTAMP() - (current_interval * 2)) 
				OR tagente_modulo.id_tipo_modulo IN (21,22,23,100))';
				
		$unknown_modules = 'SELECT tagente.id_agente FROM tagente_estado, tagente, tagente_modulo 
				WHERE tagente.disabled = 0 AND tagente.id_agente = tagente_estado.id_agente 
				AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo 
				AND tagente_modulo.disabled = 0 AND tagente_modulo.id_tipo_modulo NOT IN (21,22,23,100) 
				AND utimestamp < ( UNIX_TIMESTAMP() - (current_interval * 2)) AND utimestamp != 0';
				
		$notinit_modules = 'SELECT tagente_estado.id_agente FROM tagente_estado, tagente, tagente_modulo 
				WHERE tagente.disabled = 0 AND tagente.id_agente = tagente_estado.id_agente 
				AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo 
				AND tagente_modulo.disabled = 0 
				AND tagente_modulo.id_tipo_modulo NOT IN (21,22,23) 
				AND utimestamp = 0';
				
		switch ($filter['status']) {
			// Normal
			case 0: 
				$status_sql = "id_agente IN ($normal_modules) && id_agente NOT IN ($warning_modules) &&
				 id_agente NOT IN ($critical_modules) && id_agente NOT IN ($unknown_modules)"; //&& id_agente NOT IN ($notinit_modules)";
				break;
			// Warning
			case 1:
				$status_sql = "id_agente IN ($warning_modules) &&
				 id_agente NOT IN ($critical_modules)"; //&& id_agente NOT IN ($notinit_modules)";
				break;
			// Critical
			case 2: 
				$status_sql = "id_agente IN ($critical_modules)";
				break;
			// Unknown
			case 3:	
				$status_sql = "id_agente IN ($unknown_modules) &&
				 id_agente NOT IN ($critical_modules) && id_agente NOT IN ($warning_modules)";
				break;
			// Not normal
			case 4:
				//$status_sql = "id_agente NOT IN ($normal_modules)";
				$status_sql = "id_agente NOT IN ($normal_modules) || id_agente IN ($warning_modules) ||
				 id_agente IN ($critical_modules) || id_agente IN ($unknown_modules)";
				break;
			// Not init
			case 5:	
				$status_sql = "id_agente NOT IN ($warning_modules) &&
				 id_agente NOT IN ($critical_modules) && id_agente NOT IN ($unknown_modules) && id_agente NOT IN ($normal_modules)";
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
	
	if(isset($order['field'])) {
		if(!isset($order['order'])) {
			$order['order'] = 'ASC';
		}
		$order = 'ORDER BY '.$order['field'] . ' ' . $order['order'];
	}
	
	$where = db_format_array_where_clause_sql ($filter, 'AND', '');

	$where_nogroup = db_format_array_where_clause_sql ($filter_nogroup, 'AND', '');
	
	if ($where_nogroup == '') {
		$where_nogroup = '1 = 1';
	}

	$extra = false;

	$sql_extra = '';
	if ($all_groups){
		$where_nogroup = '1 = 1';
		
		$sql_extra = enterprise_hook('policies_get_agents_sql_condition');
		
		if($sql_extra != ENTERPRISE_NOT_HOOK) {
			if (!empty($sql_extra)) {
				$extra = true;
			}
		}
	}

	if($extra) { 
		$where = sprintf('(%s OR (%s)) AND (%s) AND (%s) %s', $sql_extra, $where, $where_nogroup, $status_sql, $search);	
	} else {			
		$where = sprintf('%s AND %s AND (%s) %s', $where, $where_nogroup, $status_sql, $search);
	}
	$sql = sprintf('SELECT %s FROM tagente WHERE %s %s', implode(',',$fields), $where, $order);

	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":
			$limit_sql = '';
			if(isset($offset) && isset($limit)) {
				$limit_sql = " LIMIT $offset, $limit "; 
			}
			$sql = sprintf("%s %s", $sql, $limit_sql);

			$agents = db_get_all_rows_sql($sql);
			break;
		case "oracle":	
			$set = array();
			if(isset($offset) && isset($limit)) {
				$set['limit'] = $limit;
				$set['offset'] = $offset;
			}

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
	$combined_alerts = agents_get_alerts_compound ($id_agent, $filter, $options);
	$simple_alerts = agents_get_alerts_simple ($id_agent, $filter, $options);
	
	return array ('simple' => $simple_alerts, 'compounds' => $combined_alerts);
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
		echo '<h3 class="error">'.__('No source agent to copy').'</h3>';
		return false;
	}
	
	if (empty ($destiny_id_agents)) {
		echo '<h3 class="error">'.__('No destiny agent(s) to copy').'</h3>';
		return false;
	}
	
	if ($copy_modules == false) {
		$copy_modules = (bool) get_parameter ('copy_modules', $copy_modules);
	}
	
	if ($copy_alerts == false) {
		$copy_alerts = (bool) get_parameter ('copy_alerts', $copy_alerts);
	}
	
	if (! $copy_modules && ! $copy_alerts)
		return;
	
	if (empty ($target_modules)) {
		$target_modules = (array) get_parameter ('target_modules', array ());
	}
	
	if (empty ($target_alerts)) {
		$target_alerts = (array) get_parameter ('target_alerts', array ());
	}
	
	if (empty ($target_modules)) {
		if (! $copy_alerts) {
			echo '<h3 class="error">'.__('No modules have been selected').'</h3>';
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
	
	foreach ($destiny_id_agents as $id_destiny_agent) {
		foreach ($target_modules as $id_agent_module) {
			$result = modules_copy_agent_module_to_agent ($id_agent_module,
				$id_destiny_agent);
		
			if ($result === false) {
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
		echo '<h3 class="error">'.__('There was an error copying the agent configuration, the copy has been cancelled').'</h3>';
		switch ($config['dbtype']) {
			case "mysql":
			case "postgresql":
				db_process_sql ('ROLLBACK');
				break;
			case "oracle":
				db_process_sql_rollback();
				break;
		}
	} else {
		echo '<h3 class="suc">'.__('Successfully copied').'</h3>';
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
}

function agents_get_next_contact($idAgent, $maxModules = false) {
	
	$agent = db_get_row_sql("SELECT * FROM tagente WHERE id_agente = " . $idAgent);
	
	
	$difference = get_system_time () - strtotime ($agent["ultimo_contacto"]);
	
	
	$max = $agent["intervalo"];
	if ($maxModules) {
		$sql = sprintf ("SELECT MAX(module_interval) FROM tagente_modulo WHERE id_agente = %d", $id_agente);
		$maxModules = (int) db_get_sql ($sql);
		if ($maxModules > 0)
			$max = $maxModules;
	}
	
	if ($max > 0)
		return round ($difference / (($max * 2) / 100));
	else
		return false;
}

/**
 * Get all the modules common in various agents that have associated alerts. If an empty list is passed it will select all
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
function agents_common_modules_with_alerts ($id_agent, $filter = false, $indexed = true, $get_not_init_modules = true) {
	$id_agent = safe_int ($id_agent, 1);

	$where = '';
	if (! empty ($id_agent)) {
		$where = sprintf (' WHERE t2.id_agent_module = t1.id_agente_modulo AND delete_pending = 0
			AND id_agente IN (%s) AND (
				SELECT count(nombre)
				FROM tagente_modulo t3, talert_template_modules t4
				WHERE t4.id_agent_module = t3.id_agente_modulo AND delete_pending = 0
					AND t1.nombre = t3.nombre AND id_agente IN (%s)) = (%s)', implode (",", (array) $id_agent), implode (",", (array) $id_agent), count($id_agent));
	}
	
	if (! empty ($filter)) {
		$where .= ' AND ';
		if (is_array ($filter)) {
			$fields = array ();
			foreach ($filter as $field => $value) {
				array_push ($fields, $field.'="'.$value.'"');
			}
			$where .= implode (' AND ', $fields);
		} else {
			$where .= $filter;
		}
	}
	
	$sql = sprintf ('SELECT DISTINCT(t1.id_agente_modulo)
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
		if($get_not_init_modules || modules_get_agentmodule_is_init($module['id_agente_modulo'])) {
			$modules[$module['id_agente_modulo']] = $module['id_agente_modulo'];
		}
	}
	return $modules;
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
		if($get_not_init_modules || modules_get_agentmodule_is_init($module['id_agente_modulo'])) {
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
 * @param boolean $extra_access The flag to get agents of extra access policies.
 *
 * @return array An array with all agents in the group or an empty array
 */
function agents_get_group_agents ($id_group = 0, $search = false, $case = "lower", $noACL = false, $childGroups = false, $extra_access = true) {
	global $config;

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
				$id_group = array_merge($id_group, groups_get_id_recursive($parent, true));
			}
		}
		else {
			$id_group = groups_get_id_recursive($id_group, true);
		}
		$id_group = array_keys(users_get_groups(false, "AR", true, false, (array)$id_group));
	}

	if (is_array($id_group)) {
		$all_groups = false;
		$search_group_sql = sprintf ('id_grupo IN (%s)', implode (",", $id_group));
	}
	else if ($id_group == 0) { //All group
		$all_groups = true;
		$search_group_sql = '1 = 1';
	}
	else {
		$all_groups = false;
		$search_group_sql = sprintf ('id_grupo = %d', $id_group);
	}

	$search_sql = '1 = 1';
	
	if ($search === true) {
		//No added search. Show both disabled and non-disabled
	}
	elseif (is_array ($search)) {
		if (isset ($search["disabled"])) {
			$search_sql .= ' AND disabled = '.($search["disabled"] ? 1 : 0); //Bool, no cleanup necessary
		}
		else {
			$search_sql .= ' AND disabled = 0';
		}
		unset ($search["disabled"]);
		if (isset ($search["string"])) {
			$string = io_safe_input ($search["string"]);
			switch ($config["dbtype"]) {
				case "mysql":
					$search_sql .= ' AND (nombre COLLATE utf8_general_ci LIKE "%'.$string.'%" OR direccion LIKE "%'.$string.'%")';
					break;
				case "postgresql":
					$search_sql .= ' AND (nombre COLLATE utf8_general_ci LIKE \'%'.$string.'%\' OR direccion LIKE \'%'.$string.'%\')';
					break;
				case "oracle":
					$search_sql .= ' AND (UPPER(nombre)  LIKE UPPER(\'%'.$string.'%\') OR direccion LIKE upper(\'%'.$string.'%\'))';
					break;
			}
				
			unset ($search["string"]);
		}

		if (isset ($search["name"])) {
			$name = io_safe_input ($search["name"]);
			switch ($config["dbtype"]) {
				case "mysql":
					$search_sql .= ' AND nombre COLLATE utf8_general_ci LIKE "' . $name . '" ';
					break;
				case "postgresql":
					$search_sql .= ' AND nombre COLLATE utf8_general_ci LIKE \'' . $name . '\' ';
					break;
				case "oracle":
					$search_sql .= ' AND UPPER(nombre) LIKE UPPER(\'' . $name . '\') ';
					break;
			}
				
			unset ($search["name"]);
		}

		if (! empty ($search)) {
			$search_sql .= ' AND '.db_format_array_where_clause_sql ($search);
		}
	}
	else {
		$search_sql .= ' AND disabled = 0';
	}
	
	enterprise_include_once ('include/functions_policies.php');
	
	if ($extra_access && $all_groups) { //if you have all group, search extra policies.
		$extra_sql = enterprise_hook('policies_get_agents_sql_condition');
		if ($extra_sql === ENTERPRISE_NOT_HOOK) {
			$extra_sql = '';
		}else if ($extra_sql != '') {
			$extra_sql .= ' OR ';
		}
	}
	else{
		$extra_sql = '';
	}
	
	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":
			$sql = sprintf ("SELECT id_agente, nombre FROM tagente WHERE (%s %s) AND (%s) ORDER BY nombre", $extra_sql, $search_group_sql, $search_sql);
			break;
		case "oracle":
			$sql = sprintf ("SELECT id_agente, nombre FROM tagente WHERE (%s %s) AND (%s) ORDER BY dbms_lob.substr(nombre,4000,1)", $extra_sql, $search_group_sql, $search_sql);
			break;
	}
	
	$result = db_get_all_rows_sql ($sql);
	
	if ($result === false)
		return array (); //Return an empty array

	$agents = array ();
	foreach ($result as $row) {
		switch ($case) {
			case "lower":
				$agents[$row["id_agente"]] = mb_strtolower ($row["nombre"], "UTF-8");
				break;
			case "upper":
				$agents[$row["id_agente"]] = mb_strtoupper ($row["nombre"], "UTF-8");
				break;
			default:
				$agents[$row["id_agente"]] = $row["nombre"];
				break;
		}
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
function agents_get_modules ($id_agent = null, $details = false, $filter = false, $indexed = true, $get_not_init_modules = true, $noACLs = false) {
	global $config;
	
	$policy_sql = '';
	
	if ($id_agent === null) {
		//Extract the agents of group user.
		$groups = users_get_groups(false, 'AR', false);
		if(empty($groups)) {
			return array();
		}
		
		$id_groups = array_keys($groups);

		$sql = "SELECT id_agente FROM tagente WHERE id_grupo IN (" . implode(',', $id_groups) . ")";
		$id_agent = db_get_all_rows_sql($sql);

		if($id_agent == false) {
			$id_agent = array();
		}

		$temp = array();
		foreach ($id_agent as $item) {
			$temp[] = $item['id_agente'];
		}
		$id_agent = $temp;
		
		if (!empty($id_agent)) {
			$extra_policy_sql = enterprise_hook('policies_get_modules_sql_condition', array($id_agent));
			if ($policy_sql === ENTERPRISE_NOT_HOOK) {
				$policy_sql = '';
			}
			else if ($policy_sql != '') {
				//It is AND instead OR, because It is necesary apply the filter.
				$policy_sql = ' OR ' . $policy_sql; 
			}
		}
	}
	
	if (!is_array($id_agent)) {
		$id_agent = safe_int ($id_agent, 1);
	}
	
	$userGroups = users_get_groups($config['id_user'], 'AR', false);
	
	if(empty($userGroups)) {
		return array();
	}
	
	$id_userGroups = array_keys($userGroups);

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
			" . $policy_sql . "
		)";
	
	if (! empty ($id_agent)) {
		$where .= sprintf (' AND id_agente IN (%s)', implode (",", (array) $id_agent));
	}

	$where .= ' AND delete_pending = 0 ';
	
	if (! empty ($filter)) {
		$where .= ' AND ';
		if (is_array ($filter)) {
			$fields = array (); 
			foreach ($filter as $field => $value) { 
				//Check <> operator
				$operatorDistin = false;
				if (strlen($value) > 2) {
					if ($value[0] . $value[1] == '<>') {
						$operatorDistin = true;
					}
				}

				if ($value[0] == '%') {
					switch ($config['dbtype']){
						case "mysql":
						case "postgresql":
							array_push ($fields, $field.' LIKE "'.$value.'"');
							break;
						case "oracle":
							array_push ($fields, $field.' LIKE \''.$value.'\'');
							break;
					}
				}
				else if ($operatorDistin) {
					array_push($fields, $field.' <> ' . substr($value, 2));
				}
				else if (substr($value, -1) == '%') {
					switch ($config['dbtype']){
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
				else if (strncmp($value, '666=666', 7) == 0){
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
				else {
					switch ($config["dbtype"]) {
						case "mysql":
							array_push ($fields, $field.' = "'.$value.'"');
							break;
						case "postgresql":
							array_push ($fields, $field.' = \''.$value.'\'');
							break;
						case "oracle":
							if (is_int ($value) ||is_float ($value)||is_double ($value))
								array_push ($fields, $field.' = '.$value.'');
							else
								array_push ($fields, $field.' = "'.$value.'"');
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
		if ($config['dbtype'] == 'oracle'){
			$details_new = array();
			if (is_array($details)) {
				foreach ($details as $detail) {
					if ($detail == 'nombre')
						$details_new[] = 'dbms_lob.substr(nombre,4000,1) as nombre';
					else
						$details_new[] = $detail;
				}
			}
			else {
				if ($details == 'nombre')
					$details_new = 'dbms_lob.substr(nombre,4000,1) as nombre';
				else
					$details_new = $details;
			}
			
			$details = io_safe_input ($details);
		}
		else
			$details = io_safe_input ($details);
	}
	
	//$where .= " AND id_policy_module = 0 ";
	
	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":
			$sql = sprintf ('SELECT %s%s
				FROM tagente_modulo WHERE
				%s
				ORDER BY nombre',
				($details != '*' && $indexed) ? 'id_agente_modulo,' : '',
				io_safe_output(implode (",", (array) $details)),
				$where);
			break;
		case "oracle":
			$sql = sprintf ('SELECT %s%s
				FROM tagente_modulo WHERE 
				%s
				ORDER BY dbms_lob.substr(nombre, 4000, 1)',
				($details != '*' && $indexed) ? 'id_agente_modulo,' : '',
				io_safe_output(implode (",", (array) $details)),
				$where);
			break;
	}
	
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
 *
 * @return int Id from the agent of the given name.
 */
function agents_get_agent_id ($agent_name) {
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
	$agent = (string) db_get_value ('nombre', 'tagente', 'id_agente', (int) $id_agent);
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
				$count[$agent_id] += (int) db_get_sql ($sql." WHERE id_agente_modulo IN (".implode (",", $modules).")", 0, true);
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
			$sql = sprintf ("SELECT COUNT(`ip`) FROM taddress_agent, taddress
				WHERE taddress_agent.id_a = taddress.id_a
				AND ip = '%s' AND id_agent = %d",$ip_address,$id_agent);
			break;
		case "postgresql":
		case "oracle":
			$sql = sprintf ("SELECT COUNT(ip) FROM taddress_agent, taddress
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

	$sql = sprintf ("SELECT id_ag FROM taddress_agent, taddress
		WHERE taddress_agent.id_a = taddress.id_a AND ip = '%s'
		AND id_agent = %d", $ip_address, $id_agent);
	$id_ag = db_get_sql ($sql);
	if ($id_ag !== false) {
		db_process_sql_delete('taddress_agent', array('id_ag' => $id_ag));
	}
	$agent_name = agents_get_name($id_agent, "");
	db_pandora_audit("Agent management",
		"Deleted IP $ip_address from agent '$agent_name'");

	// Need to change main address?
	if (agents_get_address ($id_agent) == $ip_address) {
		$new_ips = agents_get_addresses ($id_agent);
		// Change main address in agent to first one in the list
		
		db_process_sql_update('tagente', array('direccion' => current ($new_ips)),
			array('id_agente' => $id_agent));
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
	$sql = sprintf ("SELECT ip
		FROM taddress_agent, taddress
		WHERE taddress_agent.id_a = taddress.id_a
			AND id_agent = %d", $id_agent);

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
	
	if (!$noACLs){
		$modules = agents_get_modules ($id_agent, 'id_agente_modulo', array('disabled' => 0), true, false);
	}
	else{
		$filter_modules['id_agente'] = $id_agent;
		$filter_modules['disabled'] = 0;
		$filter_modules['delete_pending'] = 0;
		// Get all non disabled modules of the agent
		$all_modules = db_get_all_rows_filter('tagente_modulo', $filter_modules, 'id_agente_modulo'); 

		$result_modules = array(); 
		// Skip non init modules
		foreach ($all_modules as $module){
			if (modules_get_agentmodule_is_init($module['id_agente_modulo'])){
				$modules[] = $module['id_agente_modulo'];
			}	
		}
	}

	$modules_status = array();
	$modules_async = 0;
	foreach($modules as $module) {
		$modules_status[] = modules_get_agentmodule_status($module);

		$module_type = modules_get_agentmodule_type($module);
		if(($module_type >= 21 && $module_type <= 23) || $module_type == 100) {
			$modules_async++;
		}
	}	

	// If all the modules are asynchronous or keep alive, the group cannot be unknown
	if($modules_async < count($modules)) {
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
						'ceil((to_date(ultimo_contacto, \'YYYY-MM-DD HH24:MI:SS\') - to_date(\'19700101000000\',\'YYYYMMDDHH24MISS\')) * (86400)) > ' . $time));
				break;
		}
			
		if (! $status)
			return -1;
	}

	// Status is 0 for normal, 1 for critical, 2 for warning and 3 for unknown. 4 for alert fired
	// Checking if any module has alert fired (4)
	if(is_int(array_search(4,$modules_status))){
		return 4;
	}
	// Checking if any module has critical status (1)
	elseif(is_int(array_search(1,$modules_status))){
		return 1;
	}
	// Checking if any module has warning status (2)
	elseif(is_int(array_search(2,$modules_status))){
		return 2;
	}
	// Checking if any module has unknown status (3)
	elseif(is_int(array_search(3,$modules_status))){
		return 3;
	}
	else {
		return 0;
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

	//Start transaction
	db_process_sql_begin ();

	foreach ($id_agents as $id_agent) {
		$id_agent = (int) $id_agent; //Cast as integer
		if ($id_agent < 1)
		continue;

		$agent_name = agents_get_name($id_agent, "");

		/* Check for deletion permissions */
		$id_group = agents_get_agent_group ($id_agent);
		if ((! check_acl ($config['id_user'], $id_group, "AW")) && !$disableACL) {
			db_process_sql_rollback ();
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
			db_process_delete_temp ("taddress_agent", "id_ag", $address["id_ag"]);
		}

		// We cannot delete tagente_datos and tagente_datos_string here
		// because it's a huge ammount of time. tagente_module has a special
		// field to mark for delete each module of agent deleted and in
		// daily maintance process, all data for that modules are deleted

		//Alert
		db_process_delete_temp ("talert_compound", "id_agent", $id_agent);
		db_process_delete_temp ("talert_template_modules", "id_agent_module", $where_modules);

		//Events (up/down monitors)
		// Dont delete here, could be very time-exausting, let the daily script
		// delete them after XXX days
		// db_process_delete_temp ("tevento", "id_agente", $id_agent);

		//Graphs, layouts & reports
		db_process_delete_temp ("tgraph_source", "id_agent_module", $where_modules);
		db_process_delete_temp ("tlayout_data", "id_agente_modulo", $where_modules);
		db_process_delete_temp ("treport_content", "id_agent_module", $where_modules);

		//Planned Downtime
		db_process_delete_temp ("tplanned_downtime_agents", "id_agent", $id_agent);

		//The status of the module
		db_process_delete_temp ("tagente_estado", "id_agente", $id_agent);

		//The actual modules, don't put anything based on
		// DONT Delete this, just mark for deletion
		// db_process_delete_temp ("tagente_modulo", "id_agente", $id_agent);

		db_process_sql_update ('tagente_modulo',
		array ('delete_pending' => 1, 'disabled' => 1),
			'id_agente = '. $id_agent);

		// Access entries
		// Dont delete here, this records are deleted in daily script
		// db_process_delete_temp ("tagent_access", "id_agent", $id_agent);

		// Delete agent policies
		enterprise_hook('policies_delete_agent', array($id_agent));

		// tagente_datos_inc
		// Dont delete here, this records are deleted later, in database script
		// db_process_delete_temp ("tagente_datos_inc", "id_agente_modulo", $where_modules);

		// Delete remote configuration
		if (isset ($config["remote_config"])) {
			$agent_md5 = md5 ($agent_name, FALSE);
				
			if (file_exists ($config["remote_config"]."/md5/".$agent_md5.".md5")) {
				// Agent remote configuration editor
				$file_name = $config["remote_config"]."/conf/".$agent_md5.".conf";
				
				$error = !@unlink ($file_name);

				if (!$error) {
					$file_name = $config["remote_config"]."/md5/".$agent_md5.".md5";
					$error = !@unlink ($file_name);
				}
				
				if ($error) {
					db_pandora_audit( "Agent management",
						"Error: Deleted agent '$agent_name', the error is in the delete conf or md5.");
				}
			}
		}

		//And at long last, the agent
		db_process_delete_temp ("tagente", "id_agente", $id_agent);

		db_pandora_audit( "Agent management",
		"Deleted agent '$agent_name'");


		/* Break the loop on error */
		if ($error)
			break;
	}

	if ($error) {
		db_process_sql_rollback ();
		return false;
	}
	else {
		db_process_sql_commit ();
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
	if (empty($id_agent)){
		return false;
	}
	
	return db_get_value('count(*)', 'tincidencia', 'id_agent', $id_agent);
}

?>
