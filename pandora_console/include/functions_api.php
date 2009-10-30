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
				$returnVar = array('type' => 'array', 'data' => explode($separator,$other));
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
				array('type' => 'string', 'data' => __('No exist this operation.')));
			break;
		case 'id_not_found':
			returnData($returnType,
				array('type' => 'string', 'data' => __('No exist id in BD.')));
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
 * @return unknown_type
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
						ON t1.id_agente = t2.id_agente');
				
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

?>