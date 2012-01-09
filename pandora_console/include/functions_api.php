<?php

//Pandora FMS- http://pandorafms.com
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

//Set character encoding to UTF-8 - fixes a lot of multibyte character headaches

require_once('functions_agents.php');
require_once('functions_modules.php');
include_once($config['homedir'] . "/include/functions_profile.php");
include_once($config['homedir'] . "/include/functions.php");
include_once($config['homedir'] . "/include/functions_events.php");

/**
 * Parse the "other" parameter.
 * 
 * @param string $other
 * @param mixed $otherType
 * @return mixed
 */
function parseOtherParameter($other, $otherType) {
	
	switch ($otherType) {
		case 'url_encode':
			$returnVar = array('type' => 'string', 'data' => urldecode($other));
			break;
		default:
			if (strpos($otherType, 'url_encode_separator_') !== false) {
				$separator = str_replace('url_encode_separator_', '', $otherType);
				$returnVar = array('type' => 'array', 'data' => explode($separator, $other));
				foreach ($returnVar['data'] as $index => $element)
					$returnVar['data'][$index] = urldecode($element); 
			}
			else {
				$returnVar = array('type' => 'string', 'data' => urldecode($other));
			}
			break;
	}
	
	return $returnVar;
}

/**
 * 
 * @param $typeError
 * @param $returnType
 * @return unknown_type
 */
function returnError($typeError, $returnType) {	
	switch ($typeError) {
		case 'no_set_no_get_no_help':
			returnData($returnType,
				array('type' => 'string', 'data' => __('No set or get or help operation.')));
			break;
		case 'no_exist_operation':
			returnData($returnType,
				array('type' => 'string', 'data' => __('This operation does not exist.')));
			break;
		case 'id_not_found':
			returnData($returnType,
				array('type' => 'string', 'data' => __('Id does not exist in BD.')));
			break;
		default:
			returnData('string',
				array('type' => 'string', 'data' => __($returnType)));
			break;
	}
}

/**
 * 
 * @param $returnType
 * @param $data
 * @param $separator
 * 
 * @return
 */
function returnData($returnType, $data, $separator = ';') {
	switch ($returnType) {
		case 'string':
			if ($data['type'] == 'string')
				echo $data['data'];
			else
				;//TODO
			break;
		case 'csv':
		case 'csv_head':
			switch ($data['type']) {
				case 'array':
					if (array_key_exists('list_index', $data))
					{
						if ($returnType == 'csv_head') {
							foreach($data['list_index'] as $index) {
								echo $index;
								if (end($data['list_index']) == $index)
									echo "\n";
								else
									echo $separator;
							}
						}
						foreach($data['data'] as $dataContent) {
							foreach($data['list_index'] as $index) {
								if (array_key_exists($index, $dataContent))
									echo $dataContent[$index];
								if (end($data['list_index']) == $index)
									echo "\n";
								else
									echo $separator;
							}
						}
					}
					else {
						if (!empty($data['data'])) {
							foreach($data['data'] as $dataContent) {
								$clean = array_map("array_apply_io_safe_output", $dataContent);
								echo implode($separator, $clean) . "\n";
							}
						}
					}
					break;
			}
			break;
	}
}

function array_apply_io_safe_output($item) {
	return io_safe_output($item);
}

/**
 * 
 * @param $ip
 * @return unknown_type
 */
function isInACL($ip) {
	global $config;
	
	if (in_array($ip, $config['list_ACL_IPs_for_API']))
		return true;
	
	// If the IP is not in the list, we check one by one, all the wildcard registers
	foreach($config['list_ACL_IPs_for_API'] as $acl_ip) {
		if(preg_match('/\*$/', $acl_ip)) {
			// Remove the final wildcard
			$acl_ip = substr($acl_ip,0,strlen($acl_ip)-1);
			
			// Scape for protection
			$acl_ip = str_replace('*','\*',$acl_ip);
			$acl_ip = str_replace('.','\.',$acl_ip);
			
			// If the string match with the beginning of the IP give it access
			if(preg_match('/^'.$acl_ip.'/', $ip)) {
				return true;
			}
		}
	}
	
	return false;
}

//-------------------------DEFINED OPERATIONS FUNCTIONS-------------------------
function get_groups($thrash1, $thrash2, $other, $returnType, $user_in_db) {
	if ($other['type'] == 'string') {
		if ($other['data'] != '') {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		else {//Default values
			$separator = ';';
		}
	}
	else if ($other['type'] == 'array') {
		$separator = $other['data'][0];
	}
	
	$groups = users_get_groups ($user_in_db, "IR");
	
	$data_groups = array();
	foreach ($groups as $id => $group) {
		$data_groups[] = array($id, $group);
	}
	
	$data['type'] = 'array';
	$data['data'] = $data_groups;
	
	returnData($returnType, $data, $separator);
}

function get_agent_module_name_last_value($agentName, $moduleName, $other = ';', $returnType)
{
	$idAgent = agents_get_agent_id($agentName);
	switch ($config["dbtype"]) {
		case "mysql":
			$sql = sprintf('SELECT id_agente_modulo
				FROM tagente_modulo
				WHERE id_agente = %d AND nombre LIKE "%s"', $idAgent, $moduleName);
			break;
		case "postgresql":
		case "oracle":
			$sql = sprintf('SELECT id_agente_modulo
				FROM tagente_modulo
				WHERE id_agente = %d AND nombre LIKE \'%s\'', $idAgent, $moduleName);
			break;
	}
	
	$idModuleAgent = db_get_value_sql($sql);
	
	if ($idModuleAgent === false) {
		switch ($other['type']) {
			case 'string':
				switch ($other['data']) {
					case 'error_message':
					default:
						returnError('id_not_found', $returnType);
					break;
				}
				break;
			case 'array':
				switch ($other['data'][0]) {
					case 'error_value':
						returnData($returnType, array('type' => 'string', 'data' => $other['data'][1]));
						break;
				}
				break;
		}
	}
	else {
			get_module_last_value($idModuleAgent, null, $other, $returnType);
	}
}

function get_module_last_value($idAgentModule, $trash1, $other = ';', $returnType)
{
	$sql = sprintf('SELECT datos FROM tagente_estado WHERE id_agente_modulo = %d', $idAgentModule);
	$value = db_get_value_sql($sql);
	if ($value === false) {
		switch ($other['type']) {
			case 'string':
				switch ($other['data']) {
					case 'error_message':
					default:
						returnError('id_not_found', $returnType);
					break;
				}
				break;
			case 'array':
				switch ($other['data'][0]) {
					case 'error_value':
						returnData($returnType, array('type' => 'string', 'data' => $other['data'][1]));
						break;
				}
				break;
		}
	}
	else {
		$data = array('type' => 'string', 'data' => $value);
		returnData($returnType, $data);
	}
}

/**
 * 
 * @param $trash1
 * @param $trahs2
 * @param mixed $other If $other is string is only the separator,
 *  but if it's array, $other as param is <separator>;<replace_return>;(<field_1>,<field_2>...<field_n>) in this order
 *  and separator char (after text ; ) must be diferent that separator (and other) url (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  return csv with fields type_row,group_id and agent_name, separate with ";" and the return of the text replace for " "
 *  api.php?op=get&op2=tree_agents&return_type=csv&other=;| |type_row,group_id,agent_name&other_mode=url_encode_separator_|
 *   
 *  	
 * @param $returnType
 * @return unknown_type
 */
function get_tree_agents($trash1, $trahs2, $other, $returnType)
{
	if ($other['type'] == 'array') {
		$separator = $other['data'][0];
		$returnReplace = $other['data'][1];
		if (trim($other['data'][2]) == '')
			$fields = false;
		else {
			$fields = explode(',', $other['data'][2]);
			foreach($fields as $index => $field)
				$fields[$index] = trim($field);
		}
		
	}
	else {
		if (strlen($other['data']) == 0)
			$separator = ';'; //by default
		else
			$separator = $other['data'];
		$returnReplace = ' ';
		$fields = false;
	}
	
	$returnVar = array();
	
	$groups = db_get_all_rows_sql('SELECT * FROM tgrupo');
	if ($groups === false) $groups = array();
	$groups = str_replace('\n', $returnReplace, $groups);
	
	$agents = db_get_all_rows_sql('SELECT * FROM tagente');
	if ($agents === false) $agents = array();
	$agents = str_replace('\n', $returnReplace, $agents);
	
	foreach ($groups as $group) {
		$returnVar[] = array(
			'type_row' => 'group',
			'group_id' =>  $group['id_grupo'],
			'group_name' => $group['nombre'],
			'group_parent' => $group['parent'],
			'disabled' => $group['disabled'],
			'custom_id' => $group['custom_id']
		);
		foreach ($agents as $index => $agent) {
			if ($agent['id_grupo'] == $group['id_grupo']) {
				$returnVar[] = array(
					'type_row' => 'agent',
					'agent_id' => $agent['id_agente'],
					'agent_name' => $agent['nombre'],
					'agent_direction' => $agent['direccion'],
					'agent_comentary' => $agent['comentarios'],
					'agent_id_group' => $agent['id_grupo'],
					'agent_last_contant' => $agent['ultimo_contacto'],
					'agent_mode' => $agent['modo'],
					'agent_interval' => $agent['intervalo'],
					'agent_id_os' => $agent['id_os'],
					'agent_os_version' => $agent['os_version'],
					'agent_version' => $agent['agent_version'],
					'agent_last_remote_contact' => $agent['ultimo_contacto_remoto'],
					'agent_disabled' => $agent['disabled'],
					'agent_id_parent' => $agent['id_parent'],
					'agent_custom_id' => $agent['custom_id'],
					'agent_server_name' => $agent['server_name'],
					'agent_cascade_protection' => $agent['cascade_protection']
				);
				
				$modules = db_get_all_rows_sql('SELECT *
					FROM (SELECT *
							FROM tagente_modulo 
							WHERE id_agente = ' . $agent['id_agente'] . ') AS t1 
						INNER JOIN (SELECT *
							FROM tagente_estado
							WHERE id_agente = ' . $agent['id_agente'] . ') AS t2
						ON t1.id_agente_modulo = t2.id_agente_modulo');
				
				if ($modules === false) $modules = array();
				$modules = str_replace('\n', $returnReplace, $modules);
				
				foreach ($modules as $module) {
					$returnVar[] = array(
						'type_row' => 'module',
						'module_id_agent_modulo' => $module['id_agente_modulo'],
						'module_id_agent' => $module['id_agente'],
						'module_id_module_type' => $module['id_tipo_modulo'],
						'module_description' => $module['descripcion'],
						'module_name' => $module['nombre'],
						'module_max' => $module['max'],
						'module_min' => $module['min'],
						'module_interval' => $module['module_interval'],
						'module_tcp_port' => $module['tcp_port'],
						'module_tcp_send' => $module['tcp_send'],
						'module_tcp_rcv' => $module['tcp_rcv'],
						'module_snmp_community' => $module['snmp_community'],
						'module_snmp_oid' => $module['snmp_oid'],
						'module_ip_target' => $module['ip_target'],
						'module_id_module_group' => $module['id_module_group'],
						'module_flag' => $module['flag'],
						'module_id_module' => $module['id_modulo'],
						'module_disabled' => $module['disabled'],
						'module_id_export' => $module['id_export'],
						'module_plugin_user' => $module['plugin_user'],
						'module_plugin_pass' => $module['plugin_pass'],
						'module_plugin_parameter' => $module['plugin_parameter'],
						'module_id_plugin' => $module['id_plugin'],
						'module_post_process' => $module['post_process'],
						'module_prediction_module' => $module['prediction_module'],
						'module_max_timeout' => $module['max_timeout'],
						'module_custom_id' => $module['custom_id'],
						'module_history_data' => $module['history_data'],
						'module_min_warning' => $module['min_warning'],
						'module_max_warning' => $module['max_warning'],
						'module_str_warning' => $module['str_warning'],
						'module_min_critical' => $module['min_critical'],
						'module_max_critical' => $module['max_critical'],
						'module_str_critical' => $module['str_critical'],
						'module_min_ff_event' => $module['min_ff_event'],
						'module_delete_pending' => $module['delete_pending'],
						'module_id_agent_state' => $module['id_agente_estado'],
						'module_data' => $module['datos'],
						'module_timestamp' => $module['timestamp'],
						'module_state' => $module['estado'],
						'module_last_try' => $module['last_try'],
						'module_utimestamp' => $module['utimestamp'],
						'module_current_interval' => $module['current_interval'],
						'module_running_by' => $module['running_by'],
						'module_last_execution_try' => $module['last_execution_try'],
						'module_status_changes' => $module['status_changes'],
						'module_last_status' => $module['last_status']
					);
					
					$alerts = db_get_all_rows_sql('SELECT *,
							t1.id AS alert_template_modules_id,
							t2.id AS alert_templates_id,
							t3.id AS alert_template_module_actions_id,
							t4.id AS alert_actions_id,
							t5.id AS alert_commands_id,
							
							t2.name AS alert_templates_name,
							t4.name AS alert_actions_name,
							t5.name AS alert_commands_name,
							
							t2.description AS alert_templates_description,
							t5.description AS alert_commands_description,
							
							t1.priority AS alert_template_modules_priority,
							t2.priority AS alert_templates_priority,
							
							t2.field1 AS alert_templates_field1,
							t4.field1 AS alert_actions_field1,
							
							t2.field2 AS alert_templates_field2,
							t4.field2 AS alert_actions_field2,
							
							t2.field3 AS alert_templates_field3,
							t4.field3 AS alert_actions_field3,
							
							t2.id_group AS alert_templates_id_group,
							t4.id_group AS alert_actions_id_group
						FROM (SELECT * 
								FROM talert_template_modules 
								WHERE id_agent_module = ' . $module['id_agente_modulo'] . ') AS t1 
							INNER JOIN talert_templates AS t2
								ON t1.id_alert_template = t2.id
							LEFT JOIN talert_template_module_actions AS t3
								ON t1.id = t3.id_alert_template_module
							LEFT JOIN talert_actions AS t4
								ON t3.id_alert_action = t4.id
							LEFT JOIN talert_commands AS t5
								ON t4.id_alert_command = t5.id');
					if ($alerts === false) $alerts = array();
					$alerts = str_replace('\n', $returnReplace, $alerts);
					
					foreach ($alerts as $alert) {
						$returnVar[] = array(
							'type_row' => 'alert',
							'alert_id_agent_module' => $alert['id_agent_module'],
							'alert_id_alert_template' => $alert['id_alert_template'],
							'alert_internal_counter' => $alert['internal_counter'],
							'alert_last_fired' => $alert['last_fired'],
							'alert_last_reference' => $alert['last_reference'],
							'alert_times_fired' => $alert['times_fired'],
							'alert_disabled' => $alert['disabled'],
							'alert_force_execution' => $alert['force_execution'],
							'alert_id_alert_action' => $alert['id_alert_action'],
							'alert_type' => $alert['type'],
							'alert_value' => $alert['value'],
							'alert_matches_value' => $alert['matches_value'],
							'alert_max_value' => $alert['max_value'],
							'alert_min_value' => $alert['min_value'],
							'alert_time_threshold' => $alert['time_threshold'],
							'alert_max_alerts' => $alert['max_alerts'],
							'alert_min_alerts' => $alert['min_alerts'],
							'alert_time_from' => $alert['time_from'],
							'alert_time_to' => $alert['time_to'],
							'alert_monday' => $alert['monday'],
							'alert_tuesday' => $alert['tuesday'],
							'alert_wednesday' => $alert['wednesday'],
							'alert_thursday' => $alert['thursday'],
							'alert_friday' => $alert['friday'],
							'alert_saturday' => $alert['saturday'],
							'alert_sunday' => $alert['sunday'],
							'alert_recovery_notify' => $alert['recovery_notify'],
							'alert_field2_recovery' => $alert['field2_recovery'],
							'alert_field3_recovery' => $alert['field3_recovery'],
							'alert_id_alert_template_module' => $alert['id_alert_template_module'],
							'alert_fires_min' => $alert['fires_min'],
							'alert_fires_max' => $alert['fires_max'],
							'alert_id_alert_command' => $alert['id_alert_command'],
							'alert_command' => $alert['command'],
							'alert_internal' => $alert['internal'],
							'alert_template_modules_id' => $alert['alert_template_modules_id'],
							'alert_templates_id' => $alert['alert_templates_id'],
							'alert_template_module_actions_id' => $alert['alert_template_module_actions_id'],
							'alert_actions_id' => $alert['alert_actions_id'],
							'alert_commands_id' => $alert['alert_commands_id'],
							'alert_templates_name' => $alert['alert_templates_name'],
							'alert_actions_name' => $alert['alert_actions_name'],
							'alert_commands_name' => $alert['alert_commands_name'],
							'alert_templates_description' => $alert['alert_templates_description'],
							'alert_commands_description' => $alert['alert_commands_description'],
							'alert_template_modules_priority' => $alert['alert_template_modules_priority'],
							'alert_templates_priority' => $alert['alert_templates_priority'],
							'alert_templates_field1' => $alert['alert_templates_field1'],
							'alert_actions_field1' => $alert['alert_actions_field1'],
							'alert_templates_field2' => $alert['alert_templates_field2'],
							'alert_actions_field2' => $alert['alert_actions_field2'],
							'alert_templates_field3' => $alert['alert_templates_field3'],
							'alert_actions_field3' => $alert['alert_actions_field3'],
							'alert_templates_id_group' => $alert['alert_templates_id_group'],
							'alert_actions_id_group' => $alert['alert_actions_id_group'],
						);
					}
				}
				unset($agents[$index]);
			}
		}
	}
	$data = array('type' => 'array', 'data' => $returnVar);
	if ($fields !== false)
		$data['list_index'] = $fields;
	else
		$data['list_index'] = array(
				'type_row',

				'group_id',
				'group_name',
				'group_parent',
				'disabled',
				'custom_id',
	
				'agent_id',
				'agent_name',
				'agent_direction',
				'agent_comentary',
				'agent_id_group',
				'agent_last_contant',
				'agent_mode',
				'agent_interval',
				'agent_id_os',
				'agent_os_version',
				'agent_version',
				'agent_last_remote_contact',
				'agent_disabled',
				'agent_id_parent',
				'agent_custom_id',
				'agent_server_name',
				'agent_cascade_protection',
				
				'module_id_agent_modulo',
				'module_id_agent',
				'module_id_module_type',
				'module_description',
				'module_name',
				'module_max',
				'module_min',
				'module_interval',
				'module_tcp_port',
				'module_tcp_send',
				'module_tcp_rcv',
				'module_snmp_community',
				'module_snmp_oid',
				'module_ip_target',
				'module_id_module_group',
				'module_flag',
				'module_id_module',
				'module_disabled',
				'module_id_export',
				'module_plugin_user',
				'module_plugin_pass',
				'module_plugin_parameter',
				'module_id_plugin',
				'module_post_process',
				'module_prediction_module',
				'module_max_timeout',
				'module_custom_id',
				'module_history_data',
				'module_min_warning',
				'module_max_warning',
				'module_str_warning',
				'module_min_critical',
				'module_max_critical',
				'module_str_critical',
				'module_min_ff_event',
				'module_delete_pending',
				'module_id_agent_state',
				'module_data',
				'module_timestamp',
				'module_state',
				'module_last_try',
				'module_utimestamp',
				'module_current_interval',
				'module_running_by',
				'module_last_execution_try',
				'module_status_changes',
				'module_last_status',
	
				'alert_id_agent_module',
				'alert_id_alert_template',
				'alert_internal_counter',
				'alert_last_fired',
				'alert_last_reference',
				'alert_times_fired',
				'alert_disabled',
				'alert_force_execution',
				'alert_id_alert_action',
				'alert_type',
				'alert_value',
				'alert_matches_value',
				'alert_max_value',
				'alert_min_value',
				'alert_time_threshold',
				'alert_max_alerts',
				'alert_min_alerts',
				'alert_time_from',
				'alert_time_to',
				'alert_monday',
				'alert_tuesday',
				'alert_wednesday',
				'alert_thursday',
				'alert_friday',
				'alert_saturday',
				'alert_sunday',
				'alert_recovery_notify',
				'alert_field2_recovery',
				'alert_field3_recovery',
				'alert_id_alert_template_module',
				'alert_fires_min',
				'alert_fires_max',
				'alert_id_alert_command',
				'alert_command',
				'alert_internal',
				'alert_template_modules_id',
				'alert_templates_id',
				'alert_template_module_actions_id',
				'alert_actions_id',
				'alert_commands_id',
				'alert_templates_name',
				'alert_actions_name',
				'alert_commands_name',
				'alert_templates_description',
				'alert_commands_description',
				'alert_template_modules_priority',
				'alert_templates_priority',
				'alert_templates_field1',
				'alert_actions_field1',
				'alert_templates_field2',
				'alert_actions_field2',
				'alert_templates_field3',
				'alert_actions_field3',
				'alert_templates_id_group',
				'alert_actions_id_group'
			);
	
		returnData($returnType, $data, $separator);
}

/**
 * Create a new agent, and print the id for new agent.
 * 
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array $other it's array, $other as param is <agent_name>;<ip>;<id_parent>;<id_group>;
 *  <cascade_protection>;<interval_sec>;<id_os>;<name_server>;<custom_id>;<learning_mode>;<disabled>;<description> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=set&op2=new_agent&other=pepito|1.1.1.1|0|4|0|30|8|miguel-portatil||0|0|nose%20nose&other_mode=url_encode_separator_|
 * 
 * @param $thrash3 Don't use.
 */
function set_new_agent($thrash1, $thrash2, $other, $thrash3) {
	$name = $other['data'][0];
	$ip = $other['data'][1];
	$idParent = $other['data'][2];
	$idGroup = $other['data'][3];
	$cascadeProtection = $other['data'][4];
	$intervalSeconds = $other['data'][5];
	$idOS = $other['data'][6];
	$nameServer = $other['data'][7];
	$customId = $other['data'][8];
	$learningMode = $other['data'][9];
	$disabled = $other['data'][10];
	$description = $other['data'][11];
	
	switch ($config["dbtype"]) {
		case "mysql":
			$sql1 = 'SELECT name FROM tserver WHERE name LIKE "' . $nameServer . '"';
			break;
		case "postgresql":
		case "oracle":
			$sql1 = 'SELECT name FROM tserver WHERE name LIKE \'' . $nameServer . '\'';
			break;
	}
	
	if (agents_get_agent_id ($name)) {
		returnError('agent_name_exist', 'The name of agent yet exist in DB.');
	}
	else if (($idParent != 0) && 
		(db_get_value_sql('SELECT id_agente FROM tagente WHERE id_agente = ' . $idParent) === false)) {
			returnError('parent_agent_not_exist', 'The agent parent don`t exist.');
	}
	else if (db_get_value_sql('SELECT id_grupo FROM tgrupo WHERE id_grupo = ' . $idGroup) === false) {
		returnError('id_grupo_not_exist', 'The group don`t exist.');
	}
	else if (db_get_value_sql('SELECT id_os FROM tconfig_os WHERE id_os = ' . $idOS) === false) {
		returnError('id_os_not_exist', 'The OS don`t exist.');
	}
	else if (db_get_value_sql($sql1) === false) {
		returnError('server_not_exist', 'The Pandora Server don`t exist.');
	}
	else {
		$idAgente = db_process_sql_insert ('tagente', 
			array ('nombre' => $name,
				'direccion' => $ip,
				'id_grupo' => $idGroup,
				'intervalo' => $intervalSeconds,
				'comentarios' => $description,
				'modo' => $learningMode,
				'id_os' => $idOS,
				'disabled' => $disabled,
				'cascade_protection' => $cascadeProtection,
				'server_name' => $nameServer,
				'id_parent' => $idParent,
				'custom_id' => $customId));
		
		returnData('string',
				array('type' => 'string', 'data' => $idAgente));
	}
}

/**
 * Delete a agent with the name pass as parameter.
 * 
 * @param string $id Name of agent to delete.
 * @param $thrash1 Don't use.
 * @param $thrast2 Don't use.
 * @param $thrash3 Don't use.
 */
function set_delete_agent($id, $thrash1, $thrast2, $thrash3) {
	$agentName = $id;
	$idAgent[0] = agents_get_agent_id($agentName);
	if (!agents_delete_agent ($idAgent, true))
		returnError('error_delete', 'Error in delete operation.');
	else
		returnData('string', array('type' => 'string', 'data' => __('Correct Delete')));
}

/**
 * Create a module in agent. And return the id_agent_module of new module.
 * 
 * @param string $id Name of agent to add the module.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <name_module>;<disabled>;<id_module_type>;
 *  <id_module_group>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;<ff_threshold>;
 *  <history_data>;<ip_target>;<tcp_port>;<snmp_community>;<snmp_oid>;<module_interval>;<post_process>;
 *  <min>;<max>;<custom_id>;<description>;<id_modulo> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=set&op2=create_network_module&id=pepito&other=prueba|0|7|1|0|0|0|0|0|1|127.0.0.1|0||0|180|0|0|0||latency%20ping|2&other_mode=url_encode_separator_|
 *  
 * @param $thrash3 Don't use
 */
function set_create_network_module($id, $thrash1, $other, $thrash3) {
	$agentName = $id;
	$idAgent = agents_get_agent_id($agentName);

	$name = $other['data'][0];
	
	$values = array(
		'id_agente' => $idAgent,
		'disabled' => $other['data'][1],
		'id_tipo_modulo' => $other['data'][2],
		'id_module_group' => $other['data'][3],
		'min_warning' => $other['data'][4],
		'max_warning' => $other['data'][5],
		'str_warning' => $other['data'][6],
		'min_critical' => $other['data'][7],
		'max_critical' => $other['data'][8],
		'str_critical' => $other['data'][9],
		'min_ff_event' => $other['data'][10],
		'history_data' => $other['data'][11],
		'ip_target' => $other['data'][12],
		'tcp_port' => $other['data'][13],
		'snmp_community' => $other['data'][14],
		'snmp_oid' => $other['data'][15],
		'module_interval' => $other['data'][16],
		'post_process' => $other['data'][17],
		'min' => $other['data'][18],
		'max' => $other['data'][19],
		'custom_id' => $other['data'][20],
		'descripcion' => $other['data'][21],
		'id_modulo' => $other['data'][22]
	);
	
	$idModule = modules_create_agent_module($idAgent, $name, $values, true);
	
	if (is_error($idModule)) {
		// TODO: Improve the error returning more info
		returnError('error_create_network_module', __('Error in creation network module.'));
	}
	else {
		returnData('string', array('type' => 'string', 'data' => $idModule));
	}
}

/**
 * Get module data in CSV format.
 * 
 * @param integer $id The ID of module in DB. 
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <separator>;<period> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=get&op2=module_data&id=17&other=;|604800&other_mode=url_encode_separator_|
 *  
 * @param $returnType Don't use.
 */
function get_module_data($id, $thrash1, $other, $returnType) {
	
	$separator = $other['data'][0];
	$periodSeconds = $other['data'][1];
	
	$sql = sprintf ("SELECT utimestamp, datos 
		FROM tagente_datos 
		WHERE id_agente_modulo = %d AND utimestamp > %d 
		ORDER BY utimestamp DESC", $id, get_system_time () - $periodSeconds);
	
	$data['type'] = 'array';
	$data['list_index'] = array('utimestamp', 'datos');
	$data['data'] = db_get_all_rows_sql($sql);
	
	if ($data === false)
		returnError('error_query_module_data', 'Error in the query of module data.');
	else
		returnData('csv', $data, $separator);
}

/**
 * Create new user.
 * 
 * @param string $id String username for user login in Pandora
 * @param $thrash2 Don't use.
 * @param array $other it's array, $other as param is <fullname>;<firstname>;<lastname>;<middlename>;
 *  <email>;<phone>;<languages>;<comments> in this order and separator char
 *  (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=set&op2=new_user&id=md&other=miguel|de%20dios|matias|kkk|pandora|md@md.com|666|es|descripcion%20y%20esas%20cosas&other_mode=url_encode_separator_|
 *  
 * @param $thrash3 Don't use.
 */
function set_new_user($id, $thrash2, $other, $thrash3) {
	$values = array ();
	$values['fullname'] = $other['data'][0];
	$values['firstname'] = $other['data'][1];
	$values['lastname'] = $other['data'][2];
	$values['middlename'] = $other['data'][3];
	$password = $other['data'][4];
	$values['email'] = $other['data'][5];
	$values['phone'] = $other['data'][6];
	$values['language'] = $other['data'][7];
	$values['comments'] = $other['data'][8];
	
	if (!create_user ($id, $password, $values))
		returnError('error_create_user', 'Error create user');
	else
		returnData('string', array('type' => 'string', 'data' => __('Create user.')));
}

function otherParameter2Filter($other, $array = false) {
	$filter = array();

	if (($other['data'][1] != null) && ($other['data'][1] != -1) && ($other['data'][1] != '')) {
		$filter['criticity'] = $other['data'][1];
	}
	
	$idAgent = null;
	if ($other['data'][2] != '') {
		$idAgent = agents_get_agent_id($other['data'][2]);
		$filter['id_agente'] = $idAgent;
	}
	
	$idAgentModulo = null;
	if ($other['data'][3] != '') {
		$filterModule = array('nombre' => $other['data'][3]);
		if ($idAgent != null) {
			$filterModule['id_agente'] = $idAgent;
		}
		$idAgentModulo = db_get_value_filter('id_agente_modulo', 'tagente_modulo', $filterModule);
		if ($idAgentModulo !== false) {
			$filter['id_agentmodule'] = $idAgentModulo;
		}
	}
	
	if ($other['data'][4] != '') {
		$idTemplate = db_get_value_filter('id', 'talert_templates', array('name' => $other['data'][4]));
		if ($idTemplate !== false) {
			if ($idAgentModulo != null) {
				$idAlert = db_get_value_filter('id', 'talert_template_modules', array('id_agent_module' => $idAgentModulo,  'id_alert_template' => $idTemplate));
				if ($idAlert !== false) {
					$filter['id_alert_am'] = $idAlert;
				}
			}
		}
	}
	
	if ($other['data'][5] != '') {
		$filter['id_usuario'] = $other['data'][5];
	}
	
	$filterString = db_format_array_where_clause_sql ($filter);
	if ($filterString == '') {
		$filterString = '1 = 1';
	}
	
	if (($other['data'][6] != null) && ($other['data'][6] != -1)) {
		if ($array) {
			$filter['utimestamp']['>'] = $other['data'][6];
		}
		else {
			$filterString .= ' AND utimestamp >= ' . $other['data'][6];
		}
	}
	
	if (($other['data'][7] != null) && ($other['data'][7] != -1)) {
		if ($array) {
			$filter['utimestamp']['<'] = $other['data'][7];
		}
		else {
			$filterString .= ' AND utimestamp <= ' . $other['data'][7];
		}
	}
	
	if (($other['data'][8] != null) && ($other['data'][8] != -1)) {
		if ($array) {
			$filter['estado'] = $other['data'][8];
		}
		else {
			$filterString .= ' AND estado = ' . $other[8];
		}
	}
	
	if (($other['data'][9] != null) && ($other['data'][9] != "")) {
		if ($array) {
			$filter['evento'] = $other['data'][9];
		}
		else {
			$filterString .= ' AND evento like "%' . $other[9] . '%"';
		}
	}
	
	if ($other['data'][10] != null) {
		if ($array) {
			$filter['limit'] = $other['data'][10];
		}
		else {
			$filterString .= ' LIMIT ' . $other['data'][10];
		}
	}
	
	if ($other['data'][11] != null) {
		if ($array) {
			$filter['offset'] = $other['data'][11];
		}
		else {
			$filterString .= ' OFFSET ' . $other['data'][11];
		}
	}
	
	if (isset($other['data'][12]) && ($other['data'][12] != null)) {
		if ($array) {
			$filter['total'] = false;
			$filter['more_criticity'] = false;
			
			if ($other['data'][12] == 'total') {
				$filter['total'] = true;
			}
			
			if ($other['data'][12] == 'more_criticity') {
				$filter['more_criticity'] = true;
			}
		}
		else {
			
		}
	}
	else {
		if ($array) {
			$filter['total'] = false;
			$filter['more_criticity'] = false;
		}
		else {
			
		}
	}
	
	if ($array) {
		return $filter;
	}
	else {
		return $filterString;
	}
}

/**
 * 
 * @param $id
 * @param $id2
 * @param $other
 * @param $trash1
 */
function set_new_alert_template($id, $id2, $other, $trash1) {
	if ($other['type'] == 'string') {
		returnError('error_parameter', 'Error in the parameters.');
		return;
	}
	else if ($other['type'] == 'array') {
		$idAgent = agents_get_agent_id($id);
		
		$row = db_get_row_filter('talert_templates', array('name' => $id2));
		
		if ($row === false) {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		
		$idTemplate = $row['id'];
		$idActionTemplate = $row['id_alert_action'];
		
		$idAgentModule = db_get_value_filter('id_agente_modulo', 'tagente_modulo', array('id_agente' => $idAgent, 'nombre' => $other['data'][0]));
		
		if ($idAgentModule === false) {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		
		$values = array(
			'id_agent_module' => $idAgentModule,
			'id_alert_template' => $idTemplate);
		
		$return = db_process_sql_insert('talert_template_modules', $values);
		
		$data['type'] = 'string';
		if ($return === false) {
			$data['data'] = 0;
		}
		else {
			$data['data'] = $return;
		}
		returnData('string', $data);
		return;
	}
}

function set_delete_module($id, $id2, $other, $trash1) {
	if ($other['type'] == 'string') {
		$simulate = false;
		if ($other['data'] == 'simulate') {
			$simulate = true;
		}
		
		$idAgent = agents_get_agent_id($id);
		
		$idAgentModule = db_get_value_filter('id_agente_modulo', 'tagente_modulo', array('id_agente' => $idAgent, 'nombre' => $id2));
		
		if ($idAgentModule === false) {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		
		if (!$simulate) {
			$return = db_process_sql_delete('tagente_modulo', array('id_agente_modulo' => $idAgentModule));
		}
		else {
			$return = true;
		}

		$data['type'] = 'string';
		if ($return === false) {
			$data['data'] = 0;
		}
		else {
			$data['data'] = $return;
		}
		returnData('string', $data);
		return;		
	}
	else {
		returnError('error_parameter', 'Error in the parameters.');
		return;
	}
}

function set_module_data($id, $thrash2, $other, $trash1) {
	global $config;
	
	if ($other['type'] == 'array') {
		$idAgentModule = $id;
		$data = $other['data'][0];
		$time = $other['data'][1];
		if ($time == 'now') $time = time();
		
		$agentModule = db_get_row_filter('tagente_modulo', array('id_agente_modulo' => $idAgentModule));
		if ($agentModule === false) {
			returnError('error_parameter', 'Not found module agent.');
		}
		else {
			$agent = db_get_row_filter('tagente', array('id_agente' => $agentModule['id_agente']));
			
			$xmlTemplate = "<?xml version='1.0' encoding='ISO-8859-1'?>
				<agent_data description='' group='' os_name='%s' " .
				" os_version='%s' interval='%d' version='%s' timestamp='%s' agent_name='%s' timezone_offset='%d'>
					<module>
						<name><![CDATA[%s]]></name>
						<description><![CDATA[%s]]></description>
						<type><![CDATA[%s]]></type>
						<data><![CDATA[%s]]></data>
					</module>
				</agent_data>";
		
			$xml = sprintf($xmlTemplate, io_safe_output(get_os_name($agent['id_os'])),
				io_safe_output($agent['os_version']), $agent['intervalo'],
				io_safe_output($agent['agent_version']), date('Y/m/d h:i:s', $time),
				io_safe_output($agent['nombre']), $agent['timezone_offset'],
				io_safe_output($agentModule['nombre']), io_safe_output($agentModule['descripcion']), modules_get_type_name($agentModule['id_tipo_modulo']), $data);
		
				
			if (false === @file_put_contents($config['remote_config'] . '/' . io_safe_output($agent['nombre']) . '.' . $time . '.data', $xml)) {
				returnError('error_file', 'Can save agent data xml.');
			}
			else {
				returnData('string', array('type' => 'string', 'data' => $xml));
				return;		
			}
		}
	}
	else {
		returnError('error_parameter', 'Error in the parameters.');
		return;
	}
}

function set_new_module($id, $id2, $other, $trash1) {
	if ($other['type'] == 'string') {
		returnError('error_parameter', 'Error in the parameters.');
		return;
	}
	else if ($other['type'] == 'array') {
		$values = array();
		$values['id_agente'] = agents_get_agent_id($id);
		$values['nombre'] = $id2;
		
		$values['id_tipo_modulo'] = db_get_value_filter('id_tipo', 'ttipo_modulo', array('nombre' => $other['data'][0]));
		if ($values['id_tipo_modulo'] === false) {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		
		if ($other['data'][1] == '') {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		
		$values['ip_target'] = $other['data'][1];
		
		if (strstr($other['data'][0], 'icmp') === false) {
			if (($other['data'][2] == '') || ($other['data'][2] <= 0 || $other['data'][2] > 65535)) {
				returnError('error_parameter', 'Error in the parameters.');
				return;
			}
			
			$values['tcp_port'] = $other['data'][2];
		}
		
		$values['descripcion'] = $other['data'][3];
		
		if ($other['data'][4] != '') {
			$values['min'] = $other['data'][4];
		}
		
		if ($other['data'][5] != '') {
			$values['max'] = $other['data'][5];
		}
		
		if ($other['data'][6] != '') {
			$values['post_process'] = $other['data'][6];
		}
		
		if ($other['data'][7] != '') {
			$values['module_interval'] = $other['data'][7];
		}
		
		if ($other['data'][8] != '') {
			$values['min_warning'] = $other['data'][8];
		}
		
		if ($other['data'][9] != '') {
			$values['max_warning'] = $other['data'][9];
		}

		if ($other['data'][10] != '') {
			$values['str_warning'] = $other['data'][10];
		}
		
		if ($other['data'][11] != '') {
			$values['min_critical'] = $other['data'][11];
		}
		
		if ($other['data'][12] != '') {
			$values['max_critical'] = $other['data'][12];
		}

		if ($other['data'][13] != '') {
			$values['str_critical'] = $other['data'][13];
		}
		
		if ($other['data'][14] != '') {
			$values['history_data'] = $other['data'][14];
		}
		
		$values['id_modulo'] = 2; 
		
		$return = db_process_sql_insert('tagente_modulo', $values);
		
		$data['type'] = 'string';
		if ($return === false) {
			$data['data'] = 0;
		}
		else {
			$data['data'] = $return;
		}
		returnData('string', $data);
		return;		
	}
}

/**
 * 
 * @param unknown_type $id
 * @param unknown_type $id2
 * @param unknown_type $other
 * @param unknown_type $trash1
 */
function set_alert_actions($id, $id2, $other, $trash1) {
	if ($other['type'] == 'string') {
		returnError('error_parameter', 'Error in the parameters.');
		return;
	}
	else if ($other['type'] == 'array') {
		$idAgent = agents_get_agent_id($id);
		
		$row = db_get_row_filter('talert_templates', array('name' => $id2));
		if ($row === false) {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}		
		$idTemplate = $row['id'];
		
		$idAgentModule = db_get_value_filter('id_agente_modulo', 'tagente_modulo', array('id_agente' => $idAgent, 'nombre' => $other['data'][0]));
		if ($idAgentModule === false) {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		
		$idAlertTemplateModule = db_get_value_filter('id', 'talert_template_modules', array('id_alert_template' => $idTemplate, 'id_agent_module' => $idAgentModule));
		if ($idAlertTemplateModule === false) {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		
		if ($other['data'][1] != '') {
			$idAction = db_get_value_filter('id', 'talert_actions', array('name' => $other['data'][1]));
			if ($idAction === false) {
				returnError('error_parameter', 'Error in the parameters.');
				return;
			}
		}
		else {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		
		$firesMin = $other['data'][2];
		$firesMax = $other['data'][3];
		
		$values = array('id_alert_template_module' => $idAlertTemplateModule,
			'id_alert_action' => $idAction, 'fires_min' => $firesMin, 'fires_max' => $firesMax);
		
		$return = db_process_sql_insert('talert_template_module_actions', $values);
		
		$data['type'] = 'string';
		if ($return === false) {
			$data['data'] = 0;
		}
		else {
			$data['data'] = $return;
		}
		returnData('string', $data);
		return;
	}
}

function set_new_event($trash1, $trash2, $other, $trash3) {
	$simulate = false;
	$time = get_system_time();
	
	if ($other['type'] == 'string') {
		if ($other['data'] != '') {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
	}
	else if ($other['type'] == 'array') {
		$values = array();
		
		if (($other['data'][0] == null) && ($other['data'][0] == '')) {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		else {
			$values['evento'] = $other['data'][0];
		}
		
		if (($other['data'][1] == null) && ($other['data'][1] == '')) {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		else {
			$valuesAvaliable = array('unknown', 'alert_fired', 'alert_recovered',
				'alert_ceased', 'alert_manual_validation',
				'recon_host_detected', 'system','error', 'new_agent',
				'going_up_warning', 'going_up_critical', 'going_down_warning',
				'going_down_normal', 'going_down_critical', 'going_up_normal','configuration_change');
			
			if (in_array($other['data'][1], $valuesAvaliable)) {
				$values['event_type'] = $other['data'][1];
			}
			else {
				returnError('error_parameter', 'Error in the parameters.');
				return;
			}
		}
		
		if (($other['data'][2] == null) && ($other['data'][2] == '')) {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		else {
			$values['estado'] = $other['data'][2];
		}
		
		if (($other['data'][3] == null) && ($other['data'][3] == '')) {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		else {
			$values['id_agente'] = agents_get_agent_id($other['data'][3]);
		}
		
		if (($other['data'][4] == null) && ($other['data'][4] == '')) {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		else {
			$idAgentModule = db_get_value_filter('id_agente_modulo', 'tagente_modulo',
				array('nombre' => $other['data'][4], 'id_agente' => $values['id_agente']));
		}
			
		if ($idAgentModule === false) {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		else {
			$values['id_agentmodule'] = $idAgentModule;
		}
		
		if (($other['data'][5] == null) && ($other['data'][5] == '')) {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		else {
			if ($other['data'][5] != 'all') {
				$idGroup = db_get_value_filter('id_grupo', 'tgrupo', array('nombre' => $other['data'][5]));
			}
			else {
				$idGroup = 0;
			}
			
			if ($idGroup === false) {
				returnError('error_parameter', 'Error in the parameters.');
				return;
			}
			else {
				$values['id_grupo'] = $idGroup;
			}
		}
		
		if (($other['data'][6] == null) && ($other['data'][6] == '')) {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		else {
			if (($other['data'][6] >= 0) && ($other['data'][6] <= 4)) {
				$values['criticity'] = $other['data'][6];
			}
			else {
				returnError('error_parameter', 'Error in the parameters.');
				return;
			}
		}
		
		if (($other['data'][7] == null) && ($other['data'][7] == '')) {
			//its optional parameter
		}
		else {
			$idAlert = db_get_value_sql("SELECT t1.id 
				FROM talert_template_modules AS t1 
					INNER JOIN talert_templates AS t2 
						ON t1.id_alert_template = t2.id 
				WHERE t1.id_agent_module = 1 AND t2.name LIKE '" . $other['data'][7] . "'");
			
			if ($idAlert === false) {
				returnError('error_parameter', 'Error in the parameters.');
				return;
			}
			else {
				$values['id_alert_am'] = $idAlert;
			}
		}
	}
	
	$values['timestamp'] = date("Y-m-d H:i:s", $time);
	$values['utimestamp'] = $time;
	
	$return = db_process_sql_insert('tevento', $values);
	
	$data['type'] = 'string';
	if ($return === false) {
		$data['data'] = 0;
	}
	else {
		$data['data'] = $return;
	}
	returnData('string', $data);
	return;
}

function set_event_validate_filter_pro($trash1, $trash2, $other, $trash3) {
	$simulate = false;
	
	if ($other['type'] == 'string') {
		if ($other['data'] != '') {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
	}
	else if ($other['type'] == 'array') {
		$filter = array();

		if (($other['data'][1] != null) && ($other['data'][1] != -1) && ($other['data'][1] != '')) {
			$filter['criticity'] = $other['data'][1];
		}
		
		if (($other['data'][2] != null) && ($other['data'][2] != -1) && ($other['data'][2] != '')) {
			$filter['id_agente'] = $other['data'][2];
		}
		
		if (($other['data'][3] != null) && ($other['data'][3] != -1) && ($other['data'][3] != '')) {
			$filter['id_agentmodule'] = $other['data'][3];
		}
		
		if (($other['data'][4] != null) && ($other['data'][4] != -1) && ($other['data'][4] != '')) {
			$filter['id_alert_am'] = $other['data'][4];
		}
		
		if (($other['data'][5] != null) && ($other['data'][5] != '')) {
			$filter['id_usuario'] = $other['data'][5];
		}
		
		$filterString = db_format_array_where_clause_sql ($filter);
		if ($filterString == '') {
			$filterString = '1 = 1';
		}
		
		if (($other['data'][6] != null) && ($other['data'][6] != -1)) {
			$filterString .= ' AND utimestamp > ' . $other['data'][6];
		}
		
		if (($other['data'][7] != null) && ($other['data'][7] != -1)) {
			$filterString .= 'AND utimestamp < ' . $other['data'][7];
		}
	}
	
	if ($simulate) {
		$rows = db_get_all_rows_filter('tevento', $filterString);
		if ($rows !== false) {
			returnData('string', count($rows));
			return;
		}
	}
	else {
		returnData('string', db_process_sql_update('tevento', array('estado' => 1), $filterString));
		return;
	}
}

function set_event_validate_filter($trash1, $trash2, $other, $trash3) {
	$simulate = false;
	
	if ($other['type'] == 'string') {
		if ($other['data'] != '') {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
	}
	else if ($other['type'] == 'array') {
		$separator = $other['data'][0];
		
		if (($other['data'][8] != null) && ($other['data'][8] != '')) {
			if ($other['data'][8] == 'simulate') {
				$simulate = true;
			}
		}

		$filterString = otherParameter2Filter($other);
		
	}
	
	if ($simulate) {
		$rows = db_get_all_rows_filter('tevento', $filterString);
		if ($rows !== false) {
			returnData('string', count($rows));
			return;
		}
	}
	else {
		returnData('string', db_process_sql_update('tevento', array('estado' => 1), $filterString));
		return;
	}
}

function set_validate_events($id_event, $trash1, $other, $return_type, $user_in_db) {
	$text = $other['data'];
	
	// Set off the standby mode when close an event
	$event = events_get_event ($id_event);
	alerts_agent_module_standby ($event['id_alert_am'], 0);
	
	$result = events_validate_event ($id_event, false, $text);
	
	if ($result) {
		returnData('string', array('type' => 'string', 'data' => 'Correct validation'));
	}
	else {
		returnError('error_validate_event', 'Error in validation operation.');
	}
}

function get_events__with_user($trash1, $trash2, $other, $returnType, $user_in_db) {
	global $config;
	
	//By default.
	$status = 3;
	$search = '';
	$event_type = '';
	$severity = -1;
	$id_agent = -1;
	$id_agentmodule = -1;
	$id_alert_am = -1;
	$id_event = -1;
	$id_user_ack = 0;
	$event_view_hr = 0;
	$tag = '';
	$group_rep = 0;
	$offset = 0;
	$pagination = 40;
	$utimestamp_upper = 0;
	$utimestamp_bottom = 0;
	
	$filter = otherParameter2Filter($other, true);
	//html_debug_print($filter, true);
	
	if (isset($filter['criticity']))
		$severity = $filter['criticity'];
	if (isset($filter['id_agente']))
		$id_agent = $filter['id_agente'];
	if (isset($filter['id_agentmodule']))
		$id_agentmodule = $filter['id_agentmodule'];
	if (isset($filter['id_alert_am']))
		$id_alert_am = $filter['id_alert_am'];
	if (isset($filter['id_usuario']))
		$id_user_ack = $filter['id_usuario'];
	if (isset($filter['estado']))
		$status = $filter['estado'];
	if (isset($filter['evento']))
		$search = $filter['evento'];
	if (isset($filter['limit']))
		$pagination = $filter['limit'];
	if (isset($filter['offset']))
		$offset = $filter['offset'];
	if ($filter['utimestamp']) {
		if (isset($filter['utimestamp']['>'])) {
			$utimestamp_upper = $filter['utimestamp']['>'];
		}
		if (isset($filter['utimestamp']['<'])) {
			$utimestamp_bottom = $filter['utimestamp']['<'];
		}
	}
	
	
	//TODO MOVE THIS CODE AND THE CODE IN pandora_console/operation/events/events_list.php
	//to a function.
	
	$groups = users_get_groups ($user_in_db, "IR");
	
	$sql_post = " AND id_grupo IN (".implode (",", array_keys ($groups)).")";
	
	// Skip system messages if user is not PM
	if (!check_acl ($user_in_db, 0, "PM")) {
		$sql_post .= " AND id_grupo != 0";
	}
	
	switch($status) {
		case 0:
		case 1:
		case 2:
			$sql_post .= " AND estado = ".$status;
			break;
		case 3:
			$sql_post .= " AND (estado = 0 OR estado = 2)";
			break;
	}
	
	if ($search != "") {
		$sql_post .= " AND evento LIKE '%".io_safe_input($search)."%'";
	}
	
	if ($event_type != "") {
		// If normal, warning, could be several (going_up_warning, going_down_warning... too complex
		// for the user so for him is presented only "warning, critical and normal"
		if ($event_type == "warning" || $event_type == "critical" || $event_type == "normal") {
			$sql_post .= " AND event_type LIKE '%$event_type%' ";
		}
		elseif ($event_type == "not_normal") {
			$sql_post .= " AND event_type LIKE '%warning%' OR event_type LIKE '%critical%' OR event_type LIKE '%unknown%' ";
		}
		else {
			$sql_post .= " AND event_type = '".$event_type."'";
		}
	
	}
	
	if ($severity != -1)
		$sql_post .= " AND criticity = ".$severity;
	
	if ($id_agent != -1)
		$sql_post .= " AND id_agente = ".$id_agent;
	
	if ($id_agentmodule != -1)
		$sql_post .= " AND id_agentmodule = ".$id_agentmodule;
	
	if ($id_alert_am != -1)
		$sql_post .= " AND id_alert_am = ".$id_alert_am;
	
	if ($id_event != -1)
		$sql_post .= " AND id_evento = ".$id_event;
	
	if ($id_user_ack != "0")
		$sql_post .= " AND id_usuario = '".$id_user_ack."'";
	
	if ($utimestamp_upper != 0)
		$sql_post .= " AND utimestamp >= ".$utimestamp_upper;
	
	if ($utimestamp_bottom != 0)
		$sql_post .= " AND utimestamp <= ".$utimestamp_bottom;
	
	if ($event_view_hr > 0) {
		$unixtime = get_system_time () - ($event_view_hr * 3600); //Put hours in seconds
		$sql_post .= " AND (utimestamp > ".$unixtime . " OR estado = 2)";
	}
	
	//Search by tag
	if ($tag != "") {
		$sql_post .= " AND tags LIKE '%".io_safe_input($tag)."%'";
	}
	
	if ($group_rep == 0) {
		switch ($config["dbtype"]) {
			case "mysql":
				if ($filter['total']) {
					$sql = "SELECT COUNT(*)
						FROM tevento
						WHERE 1=1 ".$sql_post;
				}
				else if ($filter['more_criticity']) {
					$sql = "SELECT criticity
						FROM tevento
						WHERE 1=1 ".$sql_post." ORDER BY criticity DESC LIMIT 1";
				}
				else {
					$sql = "SELECT *,
						(SELECT t1.nombre FROM tagente AS t1 WHERE t1.id_agente = tevento.id_agente) AS agent_name,
						(SELECT t2.nombre FROM tgrupo AS t2 WHERE t2.id_grupo = tevento.id_grupo) AS group_name,
						(SELECT t2.icon FROM tgrupo AS t2 WHERE t2.id_grupo = tevento.id_grupo) AS group_icon
						FROM tevento" .
//FOR THE TEST THE API IN THE ANDROID
//						" WHERE 1=1 ".$sql_post." ORDER BY id_evento ASC LIMIT ".$offset.",".$pagination;
						" WHERE 1=1 ".$sql_post." ORDER BY utimestamp DESC LIMIT ".$offset.",".$pagination;
				}
				break;
			case "postgresql":
				//TODO TOTAL
				$sql = "SELECT *,
					(SELECT t1.nombre FROM tagente AS t1 WHERE t1.id_agente = tevento.id_agente) AS agent_name,
					(SELECT t2.nombre FROM tgrupo AS t2 WHERE t2.id_grupo = tevento.id_grupo) AS group_name,
					(SELECT t2.icon FROM tgrupo AS t2 WHERE t2.id_grupo = tevento.id_grupo) AS group_icon
					FROM tevento
					WHERE 1=1 ".$sql_post." ORDER BY utimestamp DESC LIMIT ".$pagination." OFFSET ".$offset;
				break;
			case "oracle":
				//TODO TOTAL
				$set = array();
				$set['limit'] = $pagination;
				$set['offset'] = $offset;
				$sql = "SELECT *,
					(SELECT t1.nombre FROM tagente AS t1 WHERE t1.id_agente = tevento.id_agente) AS agent_name,
					(SELECT t2.nombre FROM tgrupo AS t2 WHERE t2.id_grupo = tevento.id_grupo) AS group_name,
					(SELECT t2.icon FROM tgrupo AS t2 WHERE t2.id_grupo = tevento.id_grupo) AS group_icon
					FROM tevento
					WHERE 1=1 ".$sql_post." ORDER BY utimestamp DESC"; 
				$sql = oracle_recode_query ($sql, $set);
				break;
		}
	}
	else {
		switch ($config["dbtype"]) {
			case "mysql":
				db_process_sql ('SET group_concat_max_len = 9999999');
				$sql = "SELECT *, MAX(id_evento) AS id_evento, GROUP_CONCAT(DISTINCT user_comment SEPARATOR '') AS user_comment,
				        MIN(estado) AS min_estado, MAX(estado) AS max_estado, COUNT(*) AS event_rep, MAX(utimestamp) AS timestamp_rep
					FROM tevento
					WHERE 1=1 ".$sql_post."
					GROUP BY evento, id_agentmodule
					ORDER BY timestamp_rep DESC LIMIT ".$offset.",".$pagination;
				break;
			case "postgresql":
				$sql = "SELECT *, MAX(id_evento) AS id_evento, array_to_string(array_agg(DISTINCT user_comment), '') AS user_comment,
				        MIN(estado) AS min_estado, MAX(estado) AS max_estado, COUNT(*) AS event_rep, MAX(utimestamp) AS timestamp_rep
					FROM tevento
					WHERE 1=1 ".$sql_post."
					GROUP BY evento, id_agentmodule
					ORDER BY timestamp_rep DESC LIMIT ".$pagination." OFFSET ".$offset;
				break;
			case "oracle":
				$set = array();
				$set['limit'] = $pagination;
				$set['offset'] = $offset;
				// TODO: Remove duplicate user comments
				$sql = "SELECT a.*, b.event_rep, b.timestamp_rep
					FROM (SELECT * FROM tevento WHERE 1=1 ".$sql_post.") a, 
					(SELECT MAX (id_evento) AS id_evento,  to_char(evento) AS evento, 
					id_agentmodule, COUNT(*) AS event_rep, MIN(estado) AS min_estado, MAX(estado) AS max_estado,
					LISTAGG(user_comment, '') AS user_comment, MAX(utimestamp) AS timestamp_rep 
					FROM tevento 
					WHERE 1=1 ".$sql_post." 
					GROUP BY to_char(evento), id_agentmodule) b 
					WHERE a.id_evento=b.id_evento AND 
					to_char(a.evento)=to_char(b.evento) 
					AND a.id_agentmodule=b.id_agentmodule";
				$sql = oracle_recode_query ($sql, $set);
				break;
		}
	
	}
	
	if ($other['type'] == 'string') {
		if ($other['data'] != '') {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		else {//Default values
			$separator = ';';
		}
	}
	else if ($other['type'] == 'array') {
		$separator = $other['data'][0];
	}
	//html_debug_print($filter, true);
	$result = db_get_all_rows_sql ($sql);
	//html_debug_print($sql);
	
	if (($result !== false) && (!$filter['total']) && (!$filter['more_criticity'])) {
		//Add the description and image
		foreach ($result as $key => $row) {
			//FOR THE TEST THE API IN THE ANDROID
			//$row['evento'] = $row['id_evento'];
			
			$row['description_event'] = events_print_type_description($row["event_type"], true);
			$row['img_description'] = events_print_type_img ($row["event_type"], true, true);
			$row['criticity_name'] = get_priority_name ($row["criticity"]);
			if ($config['https']) {
				$urlImage = 'https://';
			}
			else {
				$urlImage = "http://";
			}
			
			$urlImage = $urlImage.$_SERVER['HTTP_HOST'].$config["homeurl"];
			switch ($row["criticity"]) {
				default:
				case 0:
					$img_sev = $urlImage . "/images/status_sets/default/severity_maintenance.png";
					break;
				case 1:
					$img_sev = $urlImage . "/images/status_sets/default/severity_informational.png";
					break;
				case 2:
					$img_sev = $urlImage . "/images/status_sets/default/severity_normal.png";
					break;
				case 3:
					$img_sev = $urlImage . "/images/status_sets/default/severity_warning.png";
					break;
				case 4:
					$img_sev = $urlImage . "/images/status_sets/default/severity_critical.png";
					break;
			}
			$row['img_criticy'] = $img_sev;
			
			$result[$key] = $row;
		}
	}
	
	//html_debug_print($result);
	
	$data['type'] = 'array';
	$data['data'] = $result;
	
	returnData($returnType, $data, $separator);
	
	return;
}

/**
 * 
 * @param $trash1
 * @param $trah2
 * @param $other
 * @param $returnType
 * @param $user_in_db
 */
function get_events($trash1, $trash2, $other, $returnType, $user_in_db = null) {
	if ($user_in_db !== null) {
		get_events__with_user($trash1, $trash2, $other, $returnType, $user_in_db);
		return;
	}
	
	if ($other['type'] == 'string') {
		if ($other['data'] != '') {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		else {//Default values
			$separator = ';';
		}
	}
	else if ($other['type'] == 'array') {
		$separator = $other['data'][0];

		$filterString = otherParameter2Filter($other);
	}
	
	$dataRows = db_get_all_rows_filter('tevento', $filterString);
	
	$data['type'] = 'array';
	$data['data'] = $dataRows;
	
	returnData($returnType, $data, $separator);
	return;
}

/**
 * Delete user.
 * 
 * @param $id string Username to delete.
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param $thrash3 Don't use.
 */
function set_delete_user($id, $thrash1, $thrash2, $thrash3) {
	if (!delete_user($id))
		returnError('error_delete_user', 'Error delete user');
	else
		returnData('string', array('type' => 'string', 'data' => __('Delete user.')));
}

/**
 * Add user to profile and group.
 * 
 * @param $id string Username to delete.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <group>;<profile> in this
 *  order and separator char (after text ; ) and separator (pass in param
 *  othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=set&op2=add_user_profile&id=md&other=12|4&other_mode=url_encode_separator_|
 *  
 * @param $thrash2 Don't use.

 */
function set_add_user_profile($id, $thrash1, $other, $thrash2) {
	$group = $other['data'][0];
	$profile = $other['data'][1];

	if (!profile_create_user_profile ($id, $profile, $group,'API'))
		returnError('error_add_user_profile', 'Error add user profile.');
	else
		returnData('string', array('type' => 'string', 'data' => __('Add user profile.')));
}

/**
 * Deattach user from group and profile.
 * 
 * @param $id string Username to delete.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <group>;<profile> in this
 *  order and separator char (after text ; ) and separator (pass in param
 *  othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=set&op2=delete_user_profile&id=md&other=12|4&other_mode=url_encode_separator_|
 *  
 * @param $thrash2 Don't use.
 */
function set_delete_user_profile($id, $thrash1, $other, $thrash2) {
	$group = $other['data'][0];
	$profile = $other['data'][1];
	
	$where = array(
		'id_usuario' => $id,
		'id_perfil' => $profile,
		'id_grupo' => $group);
	$result = db_process_sql_delete('tusuario_perfil', $where);
	if ($return === false)
		returnError('error_delete_user_profile', 'Error delete user profile.');
	else
		returnData('string', array('type' => 'string', 'data' => __('Delete user profile.')));
}

/**
 * Create new incident in Pandora.
 * 
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array $other it's array, $other as param is <title>;<description>;
 *  <origin>;<priority>;<state>;<group> in this order and separator char
 *  (after text ; ) and separator (pass in param othermode as
 *  othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=set&op2=new_incident&other=titulo|descripcion%20texto|Logfiles|2|10|12&other_mode=url_encode_separator_|
 *  
 * @param $thrash3 Don't use.
 */
function set_new_incident($thrash1, $thrash2, $other, $thrash3) {
	$title = $other['data'][0];
	$description = $other['data'][1];
	$origin = $other['data'][2];
	$priority = $other['data'][3];
	$id_creator = 'API';
	$state = $other['data'][4];
	$group = $other['data'][5];
	
	$values = array(
		'inicio' => 'NOW()',
		'actualizacion' => 'NOW()',
		'titulo' => $title,
		'descripcion' => $description,
		'id_usuario' => 'API',
		'origen' => $origin, 
		'estado' => $state,
		'prioridad' => $priority,
		'id_grupo' => $group,
		'id_creator' => $id_creator);
	$idIncident = db_process_sql_insert('tincidencia', $values);
	
	if ($return === false)
		returnError('error_new_incident', 'Error create new incident.');
	else
		returnData('string', array('type' => 'string', 'data' => $idIncident));
}

/**
 * Add note into a incident.
 * 
 * @param $id string Username author of note.
 * @param $id2 integer ID of incident.
 * @param $other string Note.
 * @param $thrash2 Don't use.
 */
function set_new_note_incident($id, $id2, $other, $thrash2) {
	$values = array(
		'id_usuario' => $id,
		'id_incident' => $id2,
		'nota' => $other['data']);
	
	$idNote = db_process_sql_insert('tnota', $values);
	
	if ($idNote === false)
		returnError('error_new_incident', 'Error create new incident.');
	else
		returnData('string', array('type' => 'string', 'data' => $idNote));
}


/**
 * Disable a module, given agent and module name.
 * 
 * @param string $agent_name Name of agent.
 * @param string $module_name Name of the module
 * @param $thrash3 Don't use.
 * @param $thrash4 Don't use.
// http://localhost/pandora_console/include/api.php?op=set&op2=enable_module&id=garfio&id2=Status
 */

function set_disable_module ($agent_name, $module_name, $thrast3, $thrash4) {
	$id_agent = agents_get_agent_id($agent_name);
	$id_agent_module = db_get_value_filter('id_agente_modulo', 'tagente_modulo', array('id_agente' => $id_agent, 'nombre' => $module_name));
    db_process_sql("UPDATE tagente_modulo SET disabled = 1 WHERE id_agente_modulo = $id_agent_module");
	returnData('string', array('type' => 'string', 'data' => __('Correct module disable')));
}


/**
 * Enable a module, given agent and module name.
 * 
 * @param string $agent_name Name of agent.
 * @param string $module_name Name of the module
 * @param $thrash3 Don't use.
 * @param $thrash4 Don't use.
 */

function set_enable_module ($agent_name, $module_name, $thrast3, $thrash4) {
	$id_agent = agents_get_agent_id($agent_name);
	$id_agent_module = db_get_value_filter('id_agente_modulo', 'tagente_modulo', array('id_agente' => $id_agent, 'nombre' => $module_name));
    db_process_sql("UPDATE tagente_modulo SET disabled = 0 WHERE id_agente_modulo = $id_agent_module");
	returnData('string', array('type' => 'string', 'data' => __('Correct module enable')));
}


/**
 * Disable an alert 
 * 
 * @param string $agent_name Name of agent (for example "myagent")
 * @param string $module_name Name of the module (for example "Host alive")
 * @param string $template_name Name of the alert template (for example, "Warning event")
 * @param $thrash4 Don't use.

// http://localhost/pandora_console/include/api.php?op=set&op2=disable_alert&id=garfio&id2=Status&other=Warning%20condition
 */

function set_disable_alert ($agent_name, $module_name, $template_name, $thrash4) {

	$id_agent = agents_get_agent_id($agent_name);
	$id_agent_module = db_get_value_filter('id_agente_modulo', 'tagente_modulo', array('id_agente' => $id_agent, 'nombre' => $module_name));
    $id_template = db_get_value_filter('id', 'talert_templates', array('name' => $template_name["data"]));

    db_process_sql("UPDATE talert_template_modules SET disabled = 1 WHERE id_agent_module = $id_agent_module AND id_alert_template = $id_template");
	returnData('string', array('type' => 'string', 'data' => "Correct alert disable"));
}

/**
 * Enable an alert
 * 
 * @param string $agent_name Name of agent (for example "myagent")
 * @param string $module_name Name of the module (for example "Host alive")
 * @param string $template_name Name of the alert template (for example, "Warning event")
 * @param $thrash4 Don't use.

// http://localhost/pandora_console/include/api.php?op=set&op2=enable_alert&id=garfio&id2=Status&other=Warning%20condition
 */

function set_enable_alert ($agent_name, $module_name, $template_name, $thrash4) {

	$id_agent = agents_get_agent_id($agent_name);
	$id_agent_module = db_get_value_filter('id_agente_modulo', 'tagente_modulo', array('id_agente' => $id_agent, 'nombre' => $module_name));
    $id_template = db_get_value_filter('id', 'talert_templates', array('name' => $template_name["data"]));

    db_process_sql("UPDATE talert_template_modules SET disabled = 0 WHERE id_agent_module = $id_agent_module AND id_alert_template = $id_template");
	returnData('string', array('type' => 'string', 'data' => "Correct alert enable"));
}

/**
 * Disable all the alerts of one module
 * 
 * @param string $agent_name Name of agent (for example "myagent")
 * @param string $module_name Name of the module (for example "Host alive")
 * @param $thrash3 Don't use.
 * @param $thrash4 Don't use.

// http://localhost/pandora_console/include/api.php?op=set&op2=disable_module_alerts&id=garfio&id2=Status
 */

function set_disable_module_alerts ($agent_name, $module_name, $thrash3, $thrash4) {

	$id_agent = agents_get_agent_id($agent_name);
	$id_agent_module = db_get_value_filter('id_agente_modulo', 'tagente_modulo', array('id_agente' => $id_agent, 'nombre' => $module_name));

    db_process_sql("UPDATE talert_template_modules SET disabled = 1 WHERE id_agent_module = $id_agent_module");
	returnData('string', array('type' => 'string', 'data' => "Correct alerts disable"));
}

/**
 * Enable all the alerts of one module
 * 
 * @param string $agent_name Name of agent (for example "myagent")
 * @param string $module_name Name of the module (for example "Host alive")
 * @param $thrash3 Don't use.
 * @param $thrash4 Don't use.

// http://localhost/pandora_console/include/api.php?op=set&op2=enable_module_alerts&id=garfio&id2=Status
 */

function set_enable_module_alerts ($agent_name, $module_name, $thrash3, $thrash4) {

	$id_agent = agents_get_agent_id($agent_name);
	$id_agent_module = db_get_value_filter('id_agente_modulo', 'tagente_modulo', array('id_agente' => $id_agent, 'nombre' => $module_name));

    db_process_sql("UPDATE talert_template_modules SET disabled = 0 WHERE id_agent_module = $id_agent_module");
	returnData('string', array('type' => 'string', 'data' => "Correct alerts enable"));
}

?>
