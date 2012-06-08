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
 * @subpackage Modules
 */

include_once($config['homedir'] . "/include/functions_agents.php");
include_once($config['homedir'] . '/include/functions_users.php');
include_once($config['homedir'] . '/include/functions_tags.php');

/**
 * Copy a module defined in an agent to other agent.
 * 
 * This function avoid duplicated by comparing module names.
 * 
 * @param int Source agent module id.
 * @param int Detiny agent id.
 * @param string Forced name to the new module.
 *
 * @return New agent module id on success. Existing module id if it already exists.
 * False on error.
 */
function modules_copy_agent_module_to_agent ($id_agent_module, $id_destiny_agent, $forced_name = false) {
	global $config;

	$module = modules_get_agentmodule ($id_agent_module);
	if ($module === false)
		return false;
	
	if($forced_name !== false)
		$module['nombre'] = $forced_name;
		
	$modules = agents_get_modules ($id_destiny_agent, false,
		array ('nombre' => $module['nombre'], 'disabled' => false));
	
	if (! empty ($modules))
		return array_pop (array_keys ($modules));
	
	$modulesDisabled = agents_get_modules ($id_destiny_agent, false,
		array ('nombre' => $module['nombre'], 'disabled' => true));
	
	if (!empty($modulesDisabled)) {
		//the foreach have only one loop but extract the array index, and it's id_agente_modulo
		foreach ($modulesDisabled as $id => $garbage) {
			$id_module = $id;
			switch ($config['dbtype']) {
				case "mysql":
				case "postgresql":
					db_process_sql_update('tagente_modulo', array('disabled' => false, 'delete_pending' => false),
					array('id_agente_modulo' => $id_module, 'disabled' => true));
					break;
				case "oracle":
					db_process_sql_update('tagente_modulo', array('disabled' => false, 'delete_pending' => false),
					array('id_agente_modulo' => $id_module, 'disabled' => true), 'AND', false);
					break;
			}
		}
		
		$values = array ();
		$values['id_agente_modulo'] = $id_module;
		
		/* PHP copy arrays on assignment */
		$new_module = $module;
		
		/* Rewrite different values */
		$new_module['id_agente'] = $id_destiny_agent;
		$new_module['ip_target'] = agents_get_address ($id_destiny_agent);
		$new_module['policy_linked'] = 0;
		$new_module['id_policy_module'] = 0;
		
		/* Unset numeric indexes or SQL would fail */
		$len = count ($new_module) / 2;
		for ($i = 0; $i < $len; $i++)
			unset ($new_module[$i]);
		/* Unset original agent module id */
		unset ($new_module['id_agente_modulo']);
		
		$id_new_module = $id_module;
	}
	else {
		/* PHP copy arrays on assignment */
		$new_module = $module;
		
		/* Rewrite different values */
		$new_module['id_agente'] = $id_destiny_agent;
		$new_module['ip_target'] = agents_get_address ($id_destiny_agent);
		$new_module['policy_linked'] = 0;
		$new_module['id_policy_module'] = 0;
		
		/* Unset numeric indexes or SQL would fail */
		$len = count ($new_module) / 2;
		for ($i = 0; $i < $len; $i++)
			unset ($new_module[$i]);
		/* Unset original agent module id */
		unset ($new_module['id_agente_modulo']);
		
		switch ($config['dbtype']) {
			case "mysql":
			case "postgresql":
				$id_new_module = db_process_sql_insert ('tagente_modulo',
					$new_module);
				break;
			case "oracle":
				$id_new_module = db_process_sql_insert ('tagente_modulo',
					$new_module, false);
				break;				
		}
		if ($id_new_module === false) {
			return false;
		}
		
		$values = array ();
		$values['id_agente_modulo'] = $id_new_module;
	}
	
	$values['id_agente'] = $id_destiny_agent;

	switch ($config['dbtype']) {
		case "mysql":
		case "postgresql":	
			$result = db_process_sql_insert ('tagente_estado', $values);
			break;
		case "oracle":
			$result = db_process_sql_insert ('tagente_estado', $values, false);
			break;
	}
	
	if ($result !== false) { 
		// If the module is synthetic we duplicate the operations too
		if($module['id_modulo'] == 5) {
			$synth_ops = db_get_all_rows_field_filter('tmodule_synth','id_agent_module_target',$module['id_agente_modulo']);
			
			if($synth_ops === false) {
				$synth_ops = array();
			}
			
			foreach($synth_ops as $synth_op) {
				unset($synth_op['id']);
				$synth_op['id_agent_module_target'] = $id_new_module;
				switch ($config['dbtype']) {
					case "mysql":
					case "postgresql":
						db_process_sql_insert ('tmodule_synth',
							$synth_op);
						break;
					case "oracle":
						db_process_sql_insert ('tmodule_synth',
							$synth_op, false);
						break;
				}
			}
		}
		
		//Added the config data if necesary
		enterprise_include_once('include/functions_config_agents.php');
		
		$id_agente = modules_get_agentmodule_agent($id_agent_module);
		
		$agent_md5 = md5 (agents_get_name($id_agente), false);
		$remote_conf = file_exists ($config["remote_config"]."/md5/".$agent_md5.".md5");
		
		if ($remote_conf) {
			$result = enterprise_hook('config_agents_copy_agent_module_to_agent',
				array($id_agent_module, $id_new_module));
		}
	}
	
	if ($result === false)
		return false;
	
	return $id_new_module;
}

/**
 * Deletes a module from an agent.
 *
 * @param mixed Agent module id to be deleted. Accepts an array with ids.
 *
 * @return True if the module was deleted. False if not.
 */
function modules_delete_agent_module ($id_agent_module) {
	if(!$id_agent_module) 
		return false;
		
	$where = array ('id_agent_module' => $id_agent_module);
	
	enterprise_hook('config_agents_delete_module_in_conf', array(modules_get_agentmodule_agent($id_agent_module), modules_get_agentmodule_name($id_agent_module)));
	
	db_process_sql_delete ('talert_template_modules', $where);
	db_process_sql_delete ('tgraph_source', $where);
	db_process_sql_delete ('treport_content', $where);
	db_process_sql_delete ('tevento', array ('id_agentmodule' => $id_agent_module));
	$where = array ('id_agente_modulo' => $id_agent_module);
	db_process_sql_delete ('tlayout_data', $where);
	db_process_sql_delete ('tagente_estado', $where);
	db_process_sql_update ('tagente_modulo',
		array ('nombre' => 'delete_pending', 'delete_pending' => 1, 'disabled' => 1),
		$where);
	db_process_sql_delete('ttag_module', $where);
	
	return true;
}

/**
 * Updates a module from an agent.
 *
 * @param mixed Agent module id to be deleted. Accepts an array with ids.
 * @param array Values to update.
 * @param mixed Tag's module array or false.
 * 
 * @return True if the module was updated. False if not.
 */
function modules_update_agent_module ($id, $values, $onlyNoDeletePending = false, $tags = false) {
	if (!is_array ($values) || empty ($values)) {
		return ERR_GENERIC;
	}
		
	if (isset ($values['nombre'])) {
		if(empty ($values['nombre'])) {
			return ERR_INCOMPLETE;
		}
		
		$id_agent = modules_get_agentmodule_agent($id);
		
		$exists = (bool)db_get_value_filter('id_agente_modulo', 'tagente_modulo', array('nombre' => $values['nombre'], 'id_agente' => $id_agent, 'id_agente_modulo' => "<>$id"));

		if($exists) {
			return ERR_EXIST;
		}
	}
	
	$return_tag = true;
	if ($tags !== false) {
		$return_tag = tags_update_module_tag ($id, $tags);
	}

	if ($return_tag === false){
			return ERR_DB;
	}
		
	$where = array();
	$where['id_agente_modulo'] = $id;
	if ($onlyNoDeletePending) {
		$where['delete_pending'] = 0;
	}	
	
	$result = @db_process_sql_update ('tagente_modulo', $values, $where);

	if($result === false) {
		return ERR_DB;
	}
	else {
		return true;
	}
}

/**
 * Creates a module in an agent.
 *
 * @param int Agent id.
 * @param int Module name id.
 * @param array Extra values for the module.
 * @param bool Disable the ACL checking, for default false.
 * @param mixed Array with tag's ids or false.
 * 
 * @return New module id if the module was created. False if not.
 */
function modules_create_agent_module ($id_agent, $name, $values = false, $disableACL = false,
	$tags = false) {
	global $config;

	if (!$disableACL) {
		if (empty ($id_agent) || ! users_access_to_agent ($id_agent, 'AW'))
			return false;
	}

	if (empty ($name)) {
		return ERR_INCOMPLETE;
	}
	
	if (! is_array ($values))
		$values = array ();
	$values['nombre'] = $name;
	$values['id_agente'] = (int) $id_agent;
	
	$exists = (bool)db_get_value_filter('id_agente_modulo', 'tagente_modulo', array('nombre' => $name, 'id_agente' => (int)$id_agent));

	if($exists) {
		return ERR_EXIST;
	}
	
	$id_agent_module = db_process_sql_insert ('tagente_modulo', $values);
	
	if ($id_agent_module === false)
		return ERR_DB;

	$return_tag = true;
	if (($tags !== false) || (empty($tags)))
		$return_tag = tags_insert_module_tag ($id_agent_module, $tags);

	if ($return_tag === false){
		db_process_sql_delete ('tagente_modulo',
			array ('id_agente_modulo' => $id_agent_module));

		return ERR_DB;
	}

	switch ($config["dbtype"]) {
		case "mysql":
			$result = db_process_sql_insert ('tagente_estado',
				array ('id_agente_modulo' => $id_agent_module,
					'datos' => 0,
					'timestamp' => '01-01-1970 00:00:00',
					'estado' => 0,
					'id_agente' => (int) $id_agent,
					'utimestamp' => 0,
					'status_changes' => 0,
					'last_status' => 0
				));
			break;
		case "postgresql":
			$result = db_process_sql_insert ('tagente_estado',
				array ('id_agente_modulo' => $id_agent_module,
					'datos' => 0,
					'timestamp' => null,
					'estado' => 0,
					'id_agente' => (int) $id_agent,
					'utimestamp' => 0,
					'status_changes' => 0,
					'last_status' => 0
				));
			break;
		case "oracle":
			$result = db_process_sql_insert ('tagente_estado',
				array ('id_agente_modulo' => $id_agent_module,
					'datos' => 0,
					'timestamp' => '#to_date(\'1970-01-01 00:00:00\', \'YYYY-MM-DD HH24:MI:SS\')',
					'estado' => 0,
					'id_agente' => (int) $id_agent,
					'utimestamp' => 0,
					'status_changes' => 0,
					'last_status' => 0
				));
			break;
	}
	
	if ($result === false) {
		db_process_sql_delete ('tagente_modulo',
			array ('id_agente_modulo' => $id_agent_module));
		
		return ERR_DB;
	}
	
	return $id_agent_module;
}

/**
 * Gets all the agents that have a module with a name given.
 *
 * @param string Module name.
 * @param int Group id of the agents. False will be any group.
 * @param array Extra filter.
 * @param mixed Fields to be returned. All agents field by default
 * @param bool Flag to search agents in child groups.
 *
 * @return array All the agents which have a module with the name given.
 */
function modules_get_agents_with_module_name ($module_name, $id_group, $filter = false, $fields = 'tagente.*', $childGroups = false) {
	if (empty ($module_name))
		return false;
	
	if (! is_array ($filter))
		$filter = array ();
	$filter[] = 'tagente_modulo.id_agente = tagente.id_agente';
	$filter['tagente_modulo.nombre'] = $module_name;
	$filter['tagente.id_agente'] = array_keys (agents_get_group_agents ($id_group, false, "none", false, $childGroups));
	
	return db_get_all_rows_filter ('tagente, tagente_modulo',
		$filter, $fields);
}

//
// This are functions to format the data
//

/**
 * Formats time data to tiemstamp format.
 *
 * @param numeric Numeric data.
 *
 * @return string HTML Code with data time with timestamp format.
 */
function modules_format_time($ts)
{
	return ui_print_timestamp ($ts, true, array("prominent" => "comparation"));
}

/**
 * Formats module data.
 *
 * @param variant Numeric or string data.
 *
 * @return variant Module data formated.
 */
function modules_format_data($data)
{
	if (is_numeric ($data)) {
		$data = format_numeric($data, 2);
	} else {
		$data = io_safe_input ($data);
	}
	return $data;
}

/**
 * Formats verbatim to string data.
 *
 * @param string String data.
 *
 * @return string HTML string data with verbatim format.
 */
function modules_format_verbatim($data){
	// We need to replace \n by <br> to create a "similar" output to
	// information recolected in logs.
	$data2 = preg_replace ("/\\n/", "<br>", $data);
	return "<span style='font-size:10px;'>" . $data2 . "</span>";
}

/**
 * Formats data time to timestamp format.
 *
 * @param int Data time.
 *
 * @return int Data time with timestamp format.
 */
function modules_format_timestamp($ts)
{
	global $config;

	// This returns data with absolute user-defined timestamp format
	// and numeric by data managed with 2 decimals, and not using Graph format 
	// (replacing 1000 by K and 1000000 by G, like version 2.x
	return date ($config["date_format"], $ts);
}

/**
 * Writes HTML code to perform delete module action for a particular module.
 *
 * @param int Id of the module.
 *
 * @return string HTML code to perform delete action.
 */
function modules_format_delete($id)
{
	global $period, $module_id, $config, $group;

	$txt = "";

	if (check_acl ($config['id_user'], $group, "AW") ==1) {
		$txt = '<a href="index.php?sec=estado&sec2=operation/agentes/datos_agente&period='.$period.'&id='.$module_id.'&delete='.$id.'">' . html_print_image("images/cross.png", true, array("border" => '0')) . '</a>';
	}
	return $txt;
}

/**
 * Writes HTML code to perform delete string module action for a particular module.
 *
 * @param int Id of the module.
 *
 * @return string HTML code to perform delete action.
 */
function modules_format_delete_string($id)
{
	global $period, $module_id, $config, $group;

	$txt = "";

	if (check_acl ($config['id_user'], $group, "AW") ==1) {
		$txt = '<a href="index.php?sec=estado&sec2=operation/agentes/datos_agente&period='.$period.'&id='.$module_id.'&delete_string='.$id.'">' . html_print_image("images/cross.png", true, array("border" => '0')) . '</a>';
	}
	return $txt;
}

/**
 * Writes HTML code to perform delete log4x module action for a particular module.
 *
 * @param int Id of the module.
 *
 * @return string HTML code to perform delete action.
 */
function modules_format_delete_log4x($id)
{
	global $period, $module_id, $config, $group;

	$txt = "";

	if (check_acl ($config['id_user'], $group, "AW") ==1) {
		$txt = '<a href="index.php?sec=estado&sec2=operation/agentes/datos_agente&period='.$period.'&id='.$module_id.'&delete_log4x='.$id.'">' . html_print_image("images/cross.png", true, array("border" => '0')) . '</a>';
	}
	return $txt;
}

/**
 * Get a single module information.
 *
 * @param int agentmodule id to get.
 *
 * @return array An array with module information
 */
function modules_get_agentmodule ($id_agentmodule) {
	global $config;
		
	switch ($config['dbtype']){
		case "mysql":
		case "postgresql": 
			return db_get_row ('tagente_modulo', 'id_agente_modulo', (int) $id_agentmodule);
			break;
		case "oracle":
			$fields = db_get_all_rows_filter('USER_TAB_COLUMNS', 'TABLE_NAME = \'TAGENTE_MODULO\' AND COLUMN_NAME <> \'MAX_CRITICAL\' AND COLUMN_NAME <> \'MIN_CRITICAL\' AND COLUMN_NAME <> \'POST_PROCESS\' AND COLUMN_NAME <> \'MAX_WARNING\' AND COLUMN_NAME <> \'MIN_WARNING\'', 'COLUMN_NAME');
			foreach ($fields as $field){
				$fields_[] = $field['column_name'];
			}
			$fields = implode(',', $fields_);
			$result = db_process_sql("SELECT TO_NUMBER(MAX_CRITICAL) as max_critical, TO_NUMBER(MIN_CRITICAL) as min_critical, TO_NUMBER(MAX_WARNING) as max_warning, TO_NUMBER(MIN_WARNING) as  min_warning, TO_NUMBER(POST_PROCESS) as post_process, " . $fields . " FROM tagente_modulo WHERE id_agente_modulo = " . $id_agentmodule);
			return $result[0];
			break;
	}
}


/**
 * Get a id of module from his name and the agent id
 *
 * @param string agentmodule name to get.
 * @param int agent id.
 *
 * @return int the agentmodule id
 */
function modules_get_agentmodule_id ($agentmodule_name, $agent_id) {
	return db_get_row_filter ('tagente_modulo', array('nombre' => $agentmodule_name, 'id_agente' => $agent_id, 'delete_pending' => 0));
}

/**
 * Get a if a module is init.
 *
 * @param int agentmodule id to get.
 *
 * @return bool true if is init and false if is not init
 */
function modules_get_agentmodule_is_init ($id_agentmodule) {
	$result = db_get_row_filter ('tagente_estado', array('id_agente_modulo' => $id_agentmodule), 'utimestamp');
	return (bool)$result['utimestamp'];
}

/**
 * Get the number of all agent modules in the database
 *
 * @param mixed Array of integers with agent(s) id or a single agent id. Default
 * value will select all.
 *
 * @return int The number of agent modules
 */
function modules_get_agent_modules_count ($id_agent = 0) {
	//Make sure we're all int's and filter out bad stuff
	$id_agent = safe_int ($id_agent, 1);

	if (empty ($id_agent)) {
		//If the array proved empty or the agent is less than 1 (eg. -1)
		$filter = '';
	}
	else {
		$filter = sprintf (" WHERE id_agente IN (%s)", implode (",", (array) $id_agent));
	}

	return (int) db_get_sql ("SELECT COUNT(*) FROM tagente_modulo" . $filter);
}

/**
 * Get the name of a module type
 *
 * @param int $id_type Type id
 *
 * @return string The name of the given type.
 */
function modules_get_type_name ($id_type) {
	return (string) db_get_value ('nombre', 'ttipo_modulo', 'id_tipo', (int) $id_type);
}

/**
 * Get the icon of a module type
 *
 * @param int $id_type Type id
 *
 * @return string The name of the icon.
 */
function modules_get_type_icon ($id_type) {
	return (string) db_get_value ('icon', 'ttipo_modulo', 'id_tipo', (int) $id_type);
}

/**
 * Get agent id of an agent module.
 *
 * @param int $id_agentmodule Agent module id.
 *
 * @return int The id of the agent of given agent module
 */
function modules_get_agentmodule_agent ($id_agentmodule) {
	return (int) db_get_value ('id_agente', 'tagente_modulo',
		'id_agente_modulo', (int) $id_agentmodule);
}

/**
 * Get agent name of an agent module.
 *
 * @param int $id_agente_modulo Agent module id.
 *
 * @return string The name of the given agent module.
 */
function modules_get_agentmodule_agent_name ($id_agentmodule) {
	// Since this is a helper function we don't need to do casting
	return (string) agents_get_name (modules_get_agentmodule_agent ($id_agentmodule));
}

/**
 * Get the module name of an agent module.
 *
 * @param int $id_agente_modulo Agent module id.
 *
 * @return string Name of the given agent module.
 */
function modules_get_agentmodule_name ($id_agente_modulo) {
	return (string) db_get_value ('nombre', 'tagente_modulo', 'id_agente_modulo', (int) $id_agente_modulo);
}

/**
 * Get the module type of an agent module.
 *
 * @param int $id_agentmodule Agent module id.
 *
 * @return string Module type of the given agent module.
 */
function modules_get_agentmodule_type ($id_agentmodule) {
	return (int) db_get_value ('id_tipo_modulo', 'tagente_modulo', 'id_agente_modulo', (int) $id_agentmodule);
}

/**
 * Get the unit of an agent module.
 *
 * @param int $id_agente_module Agent module id.
 *
 * @return string Module unit of the given agent module.
 */
function modules_get_unit ($id_agente_modulo) {
	return $unit = (string) db_get_value ('unit', 'tagente_modulo', 'id_agente_modulo', (int) $id_agente_modulo);
}

/**
 * Get all the times a monitor went down during a period.
 *
 * @param int $id_agent_module Agent module of the monitor.
 * @param int $period Period timed to check from date
 * @param int $date Date to check (now by default)
 *
 * @return int The number of times a monitor went down.
 */
function modules_get_monitor_downs_in_period ($id_agent_module, $period, $date = 0) {
	global $config;
	
	if ($date == 0) {
		$date = get_system_time ();
	}
	$datelimit = $date - $period;

	switch ($config["dbtype"]) {
		case "mysql":
			$sql = sprintf ("SELECT COUNT(`id_agentmodule`) FROM `tevento` WHERE
				`event_type` = 'monitor_down' 
				AND `id_agentmodule` = %d 
				AND `utimestamp` > %d 
				AND `utimestamp` <= %d",
				$id_agent_module, $datelimit, $date);
			break;
		case "postgresql":
			$sql = sprintf ("SELECT COUNT(\"id_agentmodule\") FROM \"tevento\" WHERE
				\"event_type\" = 'monitor_down' 
				AND \"id_agentmodule\" = %d 
				AND \"utimestamp\" > %d 
				AND \"utimestamp\" <= %d",
				$id_agent_module, $datelimit, $date);
			break;
		case "oracle":
			$sql = sprintf ("SELECT COUNT(id_agentmodule) FROM tevento WHERE
				event_type = 'monitor_down' 
				AND id_agentmodule = %d 
				AND utimestamp > %d 
				AND utimestamp <= %d",
				$id_agent_module, $datelimit, $date);
			break;
	}

	return db_get_sql ($sql);
}

/**
 * Get the last time a monitor went down during a period.
 *
 * @param int $id_agent_module Agent module of the monitor.
 * @param int $period Period timed to check from date
 * @param int $date Date to check (now by default)
 *
 * @return int The last time a monitor went down.
 */
function modules_get_last_down_timestamp_in_period ($id_agent_module, $period, $date = 0) {
	global $config;	

	if ($date == 0) {
		$date = get_system_time ();
	}
	$datelimit = $date - $period;
	
	switch ($config["dbtype"]) {
		case "mysql":
			$sql = sprintf ("SELECT MAX(`timestamp`) FROM `tevento` WHERE
				event_type = 'monitor_down' 
				AND `id_agentmodule` = %d 
				AND `utimestamp` > %d 
				AND `utimestamp` <= %d",
				$id_agent_module, $datelimit, $date);
			break;
		case "postgresql":
			$sql = sprintf ("SELECT MAX(\"timestamp\") FROM \"tevento\" WHERE
				event_type = 'monitor_down' 
				AND \"id_agentmodule\" = %d 
				AND \"utimestamp\" > %d 
				AND \"utimestamp\" <= %d",
				$id_agent_module, $datelimit, $date);
			break;
		case "oracle":
			$sql = sprintf ("SELECT MAX(timestamp) FROM tevento WHERE
				event_type = 'monitor_down' 
				AND id_agentmodule = %d 
				AND utimestamp > %d 
				AND utimestamp <= %d",
				$id_agent_module, $datelimit, $date);
			break;
	}

	return db_get_sql ($sql);
}

/**
 * Get all the monitors defined in an group.
 *
 * @param int $id_group Group id to get all the monitors.
 *
 * @return array An array with all the monitors defined in the group (tagente_modulo).
 */
function modules_get_monitors_in_group ($id_group) {
	global $config;
	
	if ($id_group <= 0) {
		//We select all groups the user has access to if it's 0 or -1
		global $config;
		$id_group = array_keys (users_get_groups ($config['id_user']));
	}

	if (is_array ($id_group)) {
		$id_group = implode (",",$id_group);
	}

	switch ($config["dbtype"]) {
		case "mysql":
			$sql = sprintf ("SELECT `tagente_modulo`.* FROM `tagente_modulo`, `ttipo_modulo`, `tagente` WHERE
					`id_tipo_modulo` = `id_tipo` 
					AND `tagente`.`id_agente` = `tagente_modulo`.`id_agente` 
					AND `ttipo_modulo`.`nombre` LIKE '%%_proc' 
					AND `tagente`.`id_grupo` IN (%s) ORDER BY `tagente`.`nombre`", $id_group);
			break;
		case "postgresql":
		case "oracle":
			$sql = sprintf ("SELECT tagente_modulo.* FROM tagente_modulo, ttipo_modulo, tagente WHERE
					id_tipo_modulo = id_tipo 
					AND tagente.id_agente = tagente_modulo.id_agente 
					AND ttipo_modulo.nombre LIKE '%%_proc' 
					AND tagente.id_grupo IN (%s) ORDER BY tagente.nombre", $id_group);
			break;
	}
				
	return db_get_all_rows_sql ($sql);
}

/**
 * Get all the monitors defined in an agent.
 *
 * @param int $id_agent Agent id to get all the monitors.
 *
 * @return array An array with all the monitors defined (tagente_modulo).
 */
function modules_get_monitors_in_agent ($id_agent) {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			$sql = sprintf ("SELECT `tagente_modulo`.*
				FROM `tagente_modulo`, `ttipo_modulo`, `tagente`
				WHERE `id_tipo_modulo` = `id_tipo`
					AND `tagente`.`id_agente` = `tagente_modulo`.`id_agente`
					AND `ttipo_modulo`.`nombre` LIKE '%%_proc'
					AND `tagente`.`id_agente` = %d", $id_agent);
			break;
		case "postgresql":
		case "oracle":
			$sql = sprintf ("SELECT tagente_modulo.*
				FROM tagente_modulo, ttipo_modulo, tagente
				WHERE id_tipo_modulo = id_tipo
					AND tagente.id_agente = tagente_modulo.id_agente
					AND ttipo_modulo.nombre LIKE '%%_proc'
					AND tagente.id_agente = %d", $id_agent);
			break;
	}	
		
	return db_get_all_rows_sql ($sql);
}

/**
 * Get all the monitors down during a period of time.
 *
 * @param array $monitors An array with all the monitors to check. Each
 * element of the array must be a dictionary.
 * @param int $period Period of time to check the monitors.
 * @param int $date Beginning date to check the monitors.
 *
 * @return array An array with all the monitors that went down in that
 * period of time.
 */
function modules_get_monitors_down ($monitors, $period = 0, $date = 0) {
	$monitors_down = array ();
	if (empty ($monitors))
	return $monitors_down;

	foreach ($monitors as $monitor) {
		$down = modules_get_monitor_downs_in_period ($monitor['id_agente_modulo'], $period, $date);
		if ($down > 0)
		array_push ($monitors_down, $monitor);
	}
	return $monitors_down;
}

/**
 * Get the module type name (type = generic_data, remote_snmp, ...)
 *
 * @param int $id_type Type id
 *
 * @return string Name of the given type.
 */
function modules_get_moduletype_name ($id_type) {
	return (string) db_get_value ('nombre', 'ttipo_modulo', 'id_tipo', (int) $id_type);
}

/**
 * Get the module type description
 *
 * @param int $id_type Type id
 *
 * @return string Description of the given type.
 */
function modules_get_moduletype_description ($id_type) {
	return (string) db_get_value ('descripcion', 'ttipo_modulo', 'id_tipo', (int) $id_type);
}

/**
 * Returns an array with all module types (default) or if "remote" or "agent"
 * is passed it will return only remote (ICMP, SNMP, TCP...) module types
 * otherwise the full list + the column you specify
 *
 * @param string Specifies which type to return (will return an array with id's)
 * @param string Which rows to select (defaults to nombre)
 *
 * @return array Either the full table or if a type is specified, an array with id's
 */
function modules_get_moduletypes ($type = "all", $rows = "nombre") {
	$return = array ();
	$rows = (array) $rows; //Cast as array
	$row_cnt = count ($rows);
	if ($type == "remote") {
		return array_merge (range (6,18), (array) 100);
	}
	elseif ($type == "agent") {
		return array_merge (range (1,4), range (19,24));
	}

	$sql = sprintf ("SELECT id_tipo, %s FROM ttipo_modulo", implode (",", $rows));
	$result = db_get_all_rows_sql ($sql);
	if ($result === false) {
		return $return;
	}

	foreach ($result as $type) {
		if ($row_cnt > 1) {
			$return[$type["id_tipo"]] = $type;
		}
		else {
			$return[$type["id_tipo"]] = $type[reset ($rows)];
		}
	}
	return $return;
}

/**
 * Get the interval value of an agent module.
 *
 * If the module interval is not set, the agent interval is returned
 *
 * @param int Id agent module to get the interval value.
 *
 * @return int Module interval or agent interval if no module interval
 */
function modules_get_interval ($id_agent_module) {
	$interval = (int) db_get_value ('module_interval', 'tagente_modulo', 'id_agente_modulo', (int) $id_agent_module);
	if ($interval > 0)
	return $interval;

	$id_agent = modules_give_agent_id_from_module_id ($id_agent_module);
	return (int) agents_get_interval ($id_agent);
}

/**
 * Get module type icon.
 *
 * TODO: Create ui_print_moduletype_icon and print the full tag including hover etc.
 * @deprecated Use ui_print_moduletype_icon instead
 *
 * @param int Module type id
 *
 * @return string Icon filename of the given group
 */
function modules_show_icon_type ($id_type) {
	return (string) db_get_value ('icon', 'ttipo_modulo', 'id_tipo', $id_type);
}

/**
 * Get a module category name
 *
 * @param int Id category
 *
 * @return Name of the given category
 */
function modules_give_modulecategory_name ($id_category) {
	switch ($id_category) {
		case 0:
			return __('Software agent data');
			break;
		case 1:
			return __('Software agent monitor');
			break;
		case 2:
			return __('Network agent data');
			break;
		case 3:
			return __('Network agent monitor');
			break;
	}

	return __('Unknown');
}

/**
 * Get agent id from an agent module.
 *
 * @param int Id of the agent module.
 *
 * @return int The agent id of the given module.
 */
function modules_give_agent_id_from_module_id ($id_agent_module) {
	return (int) db_get_value ('id_agente', 'tagente_modulo', 'id_agente_modulo', $id_agent_module);
}

/**
 * Get the status of an agent module.
 *
 * @param int Id agent module to check.
 * @param bool $without_alerts The flag to check only the module, by default false.
 *
 * @return int Module status. Value 4 means that some alerts assigned to the
 * module were fired.
 */
function modules_get_agentmodule_status($id_agentmodule = 0, $without_alerts = false) {
	$current_timestamp = get_system_time ();

	if (!$without_alerts) {
		$times_fired = db_get_value ('SUM(times_fired)', 'talert_template_modules', 'id_agent_module', $id_agentmodule);
		if ($times_fired > 0) {
			return 4; // Alert fired
		}
	}

	$status_row = db_get_row ("tagente_estado", "id_agente_modulo", $id_agentmodule);

	return $status_row['estado'];
}

/**
 * Get the last status of an agent module.
 *
 * @param int Id agent module to check.
 *
 * @return int Module last status.
 */
function modules_get_agentmodule_last_status($id_agentmodule = 0) {
	$status_row = db_get_row ("tagente_estado", "id_agente_modulo", $id_agentmodule);

	return $status_row['last_status'];
}

/**
 * Get the current value of an agent module.
 *
 * @param int Agent module id.
 *
 * @return int a numerically formatted value
 */
function modules_get_last_value ($id_agentmodule) {
	return db_get_value ('datos', 'tagente_estado',
		'id_agente_modulo', $id_agentmodule);
}

/**
 * Get the previous data to the timestamp provided.
 *
 * It's useful to know the first value of a module in an interval,
 * since it will be the last value in the table which has a timestamp
 * before the beginning of the interval. All this calculation is due
 * to the data compression algorithm.
 *
 * @param int Agent module id
 * @param int The timestamp to look backwards from and get the data.
 * @param int 1 if the module has a string type.
 *
 * @return mixed The row of tagente_datos of the last period. False if there were no data.
 */
function modules_get_previous_data ($id_agent_module, $utimestamp = 0, $string = 0) {
	if (empty ($utimestamp))
		$utimestamp = time ();

	if ($string == 1) {
		$table = 'tagente_datos_string';
	}
	else {
		$table = 'tagente_datos';
	}

	// 172800 = 60×60×24*2 Search up to 2 days before utimestamp
	$sql = sprintf ('SELECT *
		FROM ' . $table . '
		WHERE id_agente_modulo = %d
			AND utimestamp <= %d 
			AND utimestamp >= %d 
		ORDER BY utimestamp DESC',
		$id_agent_module, $utimestamp, $utimestamp - 172800);

	return db_get_row_sql ($sql, true);
}

/**
 * Get the next data to the timestamp provided.
 *
 * @param int Agent module id
 * @param int The timestamp to look backwards from and get the data.
 * @param int 1 if the module has a string type.
 *
 * @return mixed The row of tagente_datos of the last period. False if there were no data.
 */
function modules_get_next_data ($id_agent_module, $utimestamp = 0, $string = 0) {
	if (empty ($utimestamp))
		$utimestamp = time ();

	if ($string == 1) {
		$table = 'tagente_datos_string';
	}
	else {
		$table = 'tagente_datos';
	}

	$interval = modules_get_interval ($id_agent_module);
	$sql = sprintf ('SELECT *
		FROM tagente_datos
		WHERE id_agente_modulo = %d 
			AND utimestamp <= %d 
			AND utimestamp >= %d
		ORDER BY utimestamp ASC',
		$id_agent_module, $utimestamp + $interval, $utimestamp);

	return db_get_row_sql ($sql, true);
}

/**
 * Get all the values of an agent module in a period of time.
 *
 * @param int Agent module id
 * @param int Period of time to check (in seconds)
 * @param int Top date to check the values. Default current time.
 *
 * @return array The module value and the timestamp
 */
function modules_get_agentmodule_data ($id_agent_module, $period, $date = 0) {
	if ($date < 1) {
		$date = get_system_time ();
	}

	$datelimit = $date - $period;

	$sql = sprintf ("SELECT datos AS data, utimestamp
		FROM tagente_datos
		WHERE id_agente_modulo = %d
			AND utimestamp > %d AND utimestamp <= %d
		ORDER BY utimestamp ASC",
	$id_agent_module, $datelimit, $date);

	$values = db_get_all_rows_sql ($sql, true, false);

	if ($values === false) {
		return array ();
	}

	$module_name = modules_get_agentmodule_name ($id_agent_module);
	$agent_id = modules_get_agentmodule_agent ($id_agent_module);
	$agent_name = modules_get_agentmodule_agent_name ($id_agent_module);

	foreach ($values as $key => $data) {
		$values[$key]["module_name"] = $module_name;
		$values[$key]["agent_id"] = $agent_id;
		$values[$key]["agent_name"] = $agent_name;
	}

	return $values;
}

/**
 * This function gets the modulegroup for a given group
 *
 * @param int The group id
 *
 * @return int The modulegroup id
 */
function modules_get_agentmodule_modulegroup ($id_module) {
	return (int) db_get_value ('id_module_group', 'tagente_modulo', 'id_agente_modulo', (int) $id_module);
}

/**
 * Gets all module groups. (General, Networking, System).
 *
 * Module groups are merely for sorting frontend
 *
 * @return array All module groups
 */
function modules_get_modulegroups () {
	$result = db_get_all_fields_in_table ("tmodule_group");
	$return = array ();

	if (empty ($result)) {
		return $return;
	}

	foreach ($result as $modulegroup) {
		$return[$modulegroup["id_mg"]] = $modulegroup["name"];
	}

	return $return;
}

/**
 * Gets a modulegroup name based on the id
 *
 * @param int The id of the modulegroup
 *
 * @return string The modulegroup name
 */
function modules_get_modulegroup_name ($modulegroup_id) {
	if($modulegroup_id == 0)
		return false;
	else
		return (string) db_get_value ('name', 'tmodule_group', 'id_mg', (int) $modulegroup_id);
}

/**
 * Gets a module status an modify the status and title reference variables
 *
 * @param mixed The module data (Necessary $module['datos'] and $module['estado']
 * @param int status reference variable
 * @param string title reference variable
 *
 */	
function modules_get_status($id_agent_module, $db_status, $data, &$status, &$title) {
	$status = STATUS_MODULE_WARNING;
	$title = "";

	if ($db_status == 1) {
		$status = STATUS_MODULE_CRITICAL;
		$title = __('CRITICAL');
	}
	elseif ($db_status == 2) {
		$status = STATUS_MODULE_WARNING;
		$title = __('WARNING');
	}
	elseif ($db_status == 0) {
		$status = STATUS_MODULE_OK;
		$title = __('NORMAL');
	}
	elseif ($db_status == 3) {
		$last_status =  modules_get_agentmodule_last_status($id_agent_module);
		switch($last_status) {
			case 0:
				$status = STATUS_AGENT_DOWN;
				$title = __('UNKNOWN')." - ".__('Last status')." ".__('NORMAL');
				break;
			case 1:
				$status = STATUS_AGENT_DOWN;
				$title = __('UNKNOWN')." - ".__('Last status')." ".__('CRITICAL');
				break;
			case 2:
				$status = STATUS_AGENT_DOWN;
				$title = __('UNKNOWN')." - ".__('Last status')." ".__('WARNING');
				break;
		}
	}
	
	if (is_numeric($data)) {
		$title .= ": " . format_for_graph($data);
	}
	else {
		$title .= ": " . substr(io_safe_output($data),0,42);
	}
}

// Get unknown agents by using the status code in modules

function modules_agents_unknown ($module_name) {
	
	//TODO REVIEW ORACLE AND POSTGRES
	return db_get_sql ("SELECT COUNT( DISTINCT tagente.id_agente) FROM tagente_estado, tagente, tagente_modulo WHERE tagente.disabled = 0 AND tagente_estado.utimestamp != 0 AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo AND tagente_modulo.disabled = 0 AND tagente_estado.id_agente = tagente.id_agente AND tagente_estado.estado = 3 AND tagente_modulo.nombre = '$module_name'");
	
}

// Get ok agents by using the status code in modules.

function modules_agents_ok ($module_name) {
		
	//!!!Query explanation!!!
	//An agent is OK if all its modules are OK
	//The status values are: 0 OK; 1 Critical; 2 Warning; 3 Unkown
	//This query grouped all modules by agents and select the MAX value for status which has the value 0 
	//If MAX(estado) is 0 it means all modules has status 0 => OK
	//Then we count the agents of the group selected to know how many agents are in OK status
	
	//TODO REVIEW ORACLE AND POSTGRES
	
	return db_get_sql ("SELECT COUNT(max_estado) FROM (SELECT MAX(tagente_estado.estado) as max_estado FROM tagente_estado, tagente, tagente_modulo WHERE tagente.disabled = 0 AND tagente_estado.utimestamp != 0 AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo AND tagente_modulo.disabled = 0 AND tagente_estado.id_agente = tagente.id_agente AND tagente.id_agente IN (SELECT id_agente FROM tagente_modulo WHERE nombre = '$module_name') GROUP BY tagente.id_agente HAVING max_estado = 0) AS S1");
	
}

// Get critical agents by using the status code in modules.

function modules_agents_critical ($module_name) {
		
	//!!!Query explanation!!!
	//An agent is Warning when has at least one module in warning status and nothing more in critical status
	//The status values are: 0 OK; 1 Critical; 2 Warning; 3 Unkown
	//If estado = 1 it means at leas 1 module is in critical status so the agent is critical
	//Then we count the agents of the group selected to know how many agents are in critical status	
	
	//TODO REVIEW ORACLE AND POSTGRES
	
	return db_get_sql ("SELECT COUNT( DISTINCT tagente_estado.id_agente) FROM tagente_estado, tagente, tagente_modulo WHERE tagente.disabled = 0 AND tagente_estado.utimestamp != 0 AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo AND tagente_modulo.disabled = 0 AND estado = 1 AND tagente_estado.id_agente = tagente.id_agente AND tagente.id_agente IN (SELECT id_agente FROM tagente_modulo WHERE nombre = '$module_name')");
	
}

// Get warning agents by using the status code in modules.

function modules_agents_warning ($module_name) {
	
	//!!!Query explanation!!!
	//An agent is Warning when has at least one module in warning status and nothing more in critical status
	//The status values are: 0 OK; 1 Critical; 2 Warning; 3 Unkown
	//This query grouped all modules by agents and select the MIN value for status which has the value 0 
	//If MIN(estado) is 2 it means at least one module is warning and there is no critical modules
	//Then we count the agents of the group selected to know how many agents are in warning status
	
	//TODO REVIEW ORACLE AND POSTGRES
	
	return db_get_sql ("SELECT COUNT(min_estado) FROM (SELECT MIN(tagente_estado.estado) as min_estado FROM tagente_estado, tagente, tagente_modulo WHERE tagente.disabled = 0 AND tagente_estado.utimestamp != 0 AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo AND tagente_modulo.disabled = 0 AND tagente_estado.id_agente = tagente.id_agente AND tagente.id_agente IN (SELECT id_agente FROM tagente_modulo WHERE nombre = '$module_name') GROUP BY tagente.id_agente HAVING min_estado = 2) AS S1");
	
}


?>
