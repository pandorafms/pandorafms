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
						foreach($data['data'] as $dataContent) {
							echo implode($separator, $dataContent) . "\n";
						}
					}
					break;
			}
			break;
	}
}

/**
 * 
 * @param $ip
 * @return unknown_type
 */
function isInACL($ip) {
	global $config;
	
	//If set * in the list ACL return true 
	if(in_array('*', $config['list_ACL_IPs_for_API']))
		return true;
	
	if (in_array($ip, $config['list_ACL_IPs_for_API']))
		return true;
	else
		return false;
}

//-------------------------DEFINED OPERATIONS FUNCTIONS-------------------------

function get_agent_module_name_last_value($agentName, $moduleName, $other = ';', $returnType)
{
	$idAgent = get_agent_id($agentName);
	$sql = sprintf('SELECT id_agente_modulo
		FROM tagente_modulo
		WHERE id_agente = %d AND nombre LIKE "%s"', $idAgent, $moduleName);
	
	$idModuleAgent = get_db_value_sql($sql);
	
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
	$value = get_db_value_sql($sql);
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
	
	$groups = get_db_all_rows_sql('SELECT * FROM tgrupo');
	if ($groups === false) $groups = array();
	$groups = str_replace('\n', $returnReplace, $groups);
	
	$agents = get_db_all_rows_sql('SELECT * FROM tagente');
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
				
				$modules = get_db_all_rows_sql('SELECT *
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
						'module_min_critical' => $module['min_critical'],
						'module_max_critical' => $module['max_critical'],
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
					
					$alerts = get_db_all_rows_sql('SELECT *,
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
				'module_min_critical',
				'module_max_critical',
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
	
	if (get_agent_id ($name)) {
		returnError('agent_name_exist', 'The name of agent yet exist in DB.');
	}
	else if (($idParent != 0) && 
		(get_db_value_sql('SELECT id_agente FROM tagente WHERE id_agente = ' . $idParent) === false)) {
			returnError('parent_agent_not_exist', 'The agent parent don`t exist.');
	}
	else if (get_db_value_sql('SELECT id_grupo FROM tgrupo WHERE id_grupo = ' . $idGroup) === false) {
		returnError('id_grupo_not_exist', 'The group don`t exist.');
	}
	else if (get_db_value_sql('SELECT id_os FROM tconfig_os WHERE id_os = ' . $idOS) === false) {
		returnError('id_os_not_exist', 'The OS don`t exist.');
	}
	else if (get_db_value_sql('SELECT name FROM tserver WHERE name LIKE "' . $nameServer . '"') === false) {
		returnError('server_not_exist', 'The Pandora Server don`t exist.');
	}
	else {
		$idAgente = process_sql_insert ('tagente', 
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
	$idAgent[0] = get_agent_id($agentName);
	if (!delete_agent ($idAgent, true))
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
 *  <id_module_group>;<min_warning>;<max_warning>;<min_critical>;<max_critical>;<ff_threshold>;
 *  <history_data>;<ip_target>;<tcp_port>;<snmp_community>;<snmp_oid>;<module_interval>;<post_process>;
 *  <min>;<max>;<custom_id>;<description> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=set&op2=create_network_module&id=pepito&other=prueba|0|7|1|0|0|0|0|0|1|127.0.0.1|0||0|180|0|0|0||latency%20ping&other_mode=url_encode_separator_|
 *  
 * @param $thrash3 Don't use
 */
function set_create_network_module($id, $thrash1, $other, $thrash3) {
	$agentName = $id;
	$idAgent = get_agent_id($agentName);

	$name = $other['data'][0];
	
	$values = array(
		'id_agente' => $idAgent,
		'disabled' => $other['data'][1],
		'id_tipo_modulo' => $other['data'][2],
		'id_module_group' => $other['data'][3],
		'min_warning' => $other['data'][4],
		'max_warning' => $other['data'][5],
		'min_critical' => $other['data'][6],
		'max_critical' => $other['data'][7],
		'min_ff_event' => $other['data'][8],
		'history_data' => $other['data'][9],
		'ip_target' => $other['data'][10],
		'tcp_port' => $other['data'][11],
		'snmp_community' => $other['data'][12],
		'snmp_oid' => $other['data'][13],
		'module_interval' => $other['data'][14],
		'post_process' => $other['data'][15],
		'min' => $other['data'][16],
		'max' => $other['data'][17],
		'custom_id' => $other['data'][18],
		'descripcion' => $other['data'][19],
	);
	
	$idModule = create_agent_module($idAgent, $name, $values, true);
	
	if ($idModule === false)
		returnError('error_create_network_module', 'Error in creation network module.');
	else
		returnData('string', array('type' => 'string', 'data' => $idModule));
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
	$data['data'] = get_db_all_rows_sql($sql);
	
	if ($data === false)
		returnError('error_query_module_data', 'Error in the query of module data.');
	else
		returnData('csv', $data, $separator);
}

/**
 * Return a image file of sparse graph of module data in a period time.
 * 
 * @param integer $id id of a module data.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <period>;<width>;<height>;<label>;<start_date>; in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=get&op2=graph_module_data&id=17&other=604800|555|245|pepito|2009-12-07&other_mode=url_encode_separator_|
 *  
 * @param $thrash2 Don't use.
 */
function get_graph_module_data($id, $thrash1, $other, $thrash2) {
	
	$period = $other['data'][0];
	$width = $other['data'][1];
	$height = $other['data'][2];
	$graph_type = 'sparse';
	$draw_alerts = 0;
	$draw_events = 0;
	$zoom = 1;
	$label = $other['data'][3];
	$avg_only = 0;
	$start_date = $other['data'][4];
	$date = strtotime($start_date);
	
	$image = "fgraph.php?tipo=" . $graph_type .
		"&draw_alerts=" . $draw_alerts . "&draw_events=" . $draw_events . 
		"&id=" . $id . "&zoom=" . $zoom . "&label=" . $label .
		"&height=" . $height . "&width=" . $width . "&period=" . $period .
		"&avg_only=" . $avg_only . "&date=" . $date;

	header('Location: ' . $image);
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

function otherParameter2Filter($other) {
	$filter = array();

	if (($other['data'][1] != null) && ($other['data'][1] != -1) && ($other['data'][1] != '')) {
		$filter['criticity'] = $other['data'][1];
	}
	
	$idAgent = null;
	if ($other['data'][2] != '') {
		$idAgent = get_agent_id($other['data'][2]);
		$filter['id_agente'] = $idAgent;
	}
	
	$idAgentModulo = null;
	if ($other['data'][3] != '') {
		$filterModule = array('nombre' => $other['data'][3]);
		if ($idAgent != null) {
			$filterModule['id_agente'] = $idAgent;
		}
		$idAgentModulo = get_db_value_filter('id_agente_modulo', 'tagente_modulo', $filterModule);
		if ($idAgentModulo !== false) {
			$filter['id_agentmodule'] = $idAgentModulo;
		}
	}
	
	if ($other['data'][4] != '') {
		$idTemplate = get_db_value_filter('id', 'talert_templates', array('name' => $other['data'][4]));
		if ($idTemplate !== false) {
			if ($idAgentModulo != null) {
				$idAlert = get_db_value_filter('id', 'talert_template_modules', array('id_agent_module' => $idAgentModulo,  'id_alert_template' => $idTemplate));
				if ($idAlert !== false) {
					$filter['id_alert_am'] = $idAlert;
				}
			}
		}
	}
	
	if ($other['data'][5] != '') {
		$filter['id_usuario'] = $other['data'][5];
	}
	
	$filterString = format_array_to_where_clause_sql ($filter);
	if ($filterString == '') {
		$filterString = '1 = 1';
	}
	
	if (($other['data'][6] != null) && ($other['data'][6] != -1)) {
		$filterString .= ' AND utimestamp => ' . $other['data'][6];
	}
	
	if (($other['data'][7] != null) && ($other['data'][7] != -1)) {
		$filterString .= 'AND utimestamp <= ' . $other['data'][7];
	}
	
	return $filterString;
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
		$idAgent = get_agent_id($id);
		
		$row = get_db_row_filter('talert_templates', array('name' => $id2));
		
		if ($row === false) {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		
		$idTemplate = $row['id'];
		$idActionTemplate = $row['id_alert_action'];
		
		$idAgentModule = get_db_value_filter('id_agente_modulo', 'tagente_modulo', array('id_agente' => $idAgent, 'nombre' => $other['data'][0]));
		
		if ($idAgentModule === false) {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		
		$values = array(
			'id_agent_module' => $idAgentModule,
			'id_alert_template' => $idActionTemplate);
		
		$return = process_sql_insert('talert_template_modules', $values);
		
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
		
		$idAgent = get_agent_id($id);
		
		$idAgentModule = get_db_value_filter('id_agente_modulo', 'tagente_modulo', array('id_agente' => $idAgent, 'nombre' => $id2));
		
		if ($idAgentModule === false) {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		
		if (!$simulate) {
			$return = process_sql_delete('tagente_modulo', array('id_agente_modulo' => $idAgentModule));
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
		
		$agentModule = get_db_row_filter('tagente_modulo', array('id_agente_modulo' => $idAgentModule));
		if ($agentModule === false) {
			returnError('error_parameter', 'Not found module agent.');
		}
		else {
			$agent = get_db_row_filter('tagente', array('id_agente' => $agentModule['id_agente']));
			
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
		
			$xml = sprintf($xmlTemplate, safe_output(get_os_name($agent['id_os'])),
				safe_output($agent['os_version']), $agent['intervalo'],
				safe_output($agent['agent_version']), date('Y/m/d h:i:s', $time),
				safe_output($agent['nombre']), $agent['timezone_offset'],
				safe_output($agentModule['nombre']), safe_output($agentModule['descripcion']), get_module_type_name($agentModule['id_tipo_modulo']), $data);
		
				
			if (false === @file_put_contents($config['remote_config'] . '/' . safe_output($agent['nombre']) . '.' . $time . '.data', $xml)) {
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
		$values['id_agente'] = get_agent_id($id);
		$values['nombre'] = $id2;
		
		$values['id_tipo_modulo'] = get_db_value_filter('id_tipo', 'ttipo_modulo', array('nombre' => $other['data'][0]));
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
			$values['min_critical'] = $other['data'][10];
		}
		
		if ($other['data'][11] != '') {
			$values['max_critical'] = $other['data'][11];
		}
		
		if ($other['data'][12] != '') {
			$values['history_data'] = $other['data'][12];
		}
		
		$values['id_modulo'] = 2; 
		
		$return = process_sql_insert('tagente_modulo', $values);
		
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
		$idAgent = get_agent_id($id);
		
		$row = get_db_row_filter('talert_templates', array('name' => $id2));
		if ($row === false) {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}		
		$idTemplate = $row['id'];
		
		$idAgentModule = get_db_value_filter('id_agente_modulo', 'tagente_modulo', array('id_agente' => $idAgent, 'nombre' => $other['data'][0]));
		if ($idAgentModule === false) {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		
		$idAlertTemplateModule = get_db_value_filter('id', 'talert_template_modules', array('id_alert_template' => $idTemplate, 'id_agent_module' => $idAgentModule));
		if ($idAlertTemplateModule === false) {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		
		if ($other['data'][1] != '') {
			$idAction = get_db_value_filter('id', 'talert_actions', array('name' => $other['data'][1]));
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
		
		$return = process_sql_insert('talert_template_module_actions', $values);
		
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
				'going_down_normal', 'going_down_critical', 'going_up_normal');
			
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
			$values['id_agente'] = get_agent_id($other['data'][3]);
		}
		
		if (($other['data'][4] == null) && ($other['data'][4] == '')) {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
		else {
			$idAgentModule = get_db_value_filter('id_agente_modulo', 'tagente_modulo',
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
				$idGroup = get_db_value_filter('id_grupo', 'tgrupo', array('nombre' => $other['data'][5]));
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
			$idAlert = get_db_value_sql("SELECT t1.id 
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
	
	$return = process_sql_insert('tevento', $values);
	
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
		
		$filterString = format_array_to_where_clause_sql ($filter);
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
		$rows = get_db_all_rows_filter('tevento', $filterString);
		if ($rows !== false) {
			returnData('string', count($rows));
			return;
		}
	}
	else {
		returnData('string', process_sql_update('tevento', array('estado' => 1), $filterString));
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
		$rows = get_db_all_rows_filter('tevento', $filterString);
		if ($rows !== false) {
			returnData('string', count($rows));
			return;
		}
	}
	else {
		returnData('string', process_sql_update('tevento', array('estado' => 1), $filterString));
		return;
	}
}

/**
 * 
 * @param $trash1
 * @param $trah2
 * @param $other
 * @param $returnType
 */
function get_events($trash1, $trash2, $other, $returnType) {
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
	
	$dataRows = get_db_all_rows_filter('tevento', $filterString);
	
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

	if (!create_user_profile ($id, $profile, $group,'API'))
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
	
	$sql = sprintf ('DELETE FROM tusuario_perfil WHERE id_usuario LIKE "%s" AND id_perfil = %d AND id_grupo = %d', $id, $profile, $group);
	$return = process_sql ($sql);
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
	$sql = sprintf("INSERT INTO tincidencia 
			(inicio, actualizacion, titulo, descripcion, id_usuario, origen, 
			estado, prioridad, id_grupo, id_creator) VALUES 
			(NOW(), NOW(), '%s', '%s', '%s', '%s', %d, %d, '%s', '%s')",
		$title, $description, 'API', $origin, $state, $priority, $group, $id_creator);
	$idIncident = process_sql ($sql, "insert_id");
	
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
	$sql = sprintf ("INSERT INTO tnota (id_usuario, id_incident, nota) VALUES ('%s', %d, '%s')", $id, $id, $other['data']);
	$idNote = process_sql ($sql, "insert_id");
	
	if ($idNote === false)
		returnError('error_new_incident', 'Error create new incident.');
	else
		returnData('string', array('type' => 'string', 'data' => $idNote));
}
?>
